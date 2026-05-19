<?php

namespace App\Libraries;

use CodeIgniter\I18n\Time;

class FhirR4Builder
{
    /**
     * @param array<string, mixed> $patient
     * @param array<string, mixed> $encounter
     * @param array<int, array<string, mixed>> $medications
     * @param array<int, array<string, mixed>> $conditions
     *
     * @return array<string, mixed>
     */
    public function buildPrescriptionBundle(array $patient, array $encounter, array $medications, array $conditions = [], array $context = []): array
    {
        $issuedAt        = $this->isoTimestamp();
        $practitioner    = is_array($context['practitioner'] ?? null) ? (array) $context['practitioner'] : [];
        $organization    = is_array($context['organization'] ?? null) ? (array) $context['organization'] : [];
        $observations    = is_array($context['observations'] ?? null) ? (array) $context['observations'] : [];
        $allergies       = is_array($context['allergies'] ?? null) ? (array) $context['allergies'] : [];
        $complaints      = is_array($context['complaints'] ?? null) ? (array) $context['complaints'] : [];
        $serviceRequests = is_array($context['service_requests'] ?? null) ? (array) $context['service_requests'] : [];
        $appointments    = is_array($context['appointments'] ?? null) ? (array) $context['appointments'] : [];

        // UUID-based identity for every resource (ABDM IG v6.5.0 requirement)
        $bundleUuid      = $this->generateUuid();
        $compositionUuid = $this->generateUuid();
        $patientUuid     = $this->generateUuid();
        $encounterUuid   = $this->generateUuid();

        $hasPractitioner  = trim((string) ($practitioner['id'] ?? '')) !== '' || trim((string) ($practitioner['name'] ?? '')) !== '';
        $hasOrganization  = trim((string) ($organization['id'] ?? '')) !== '' || trim((string) ($organization['name'] ?? '')) !== '';
        $practitionerUuid = $hasPractitioner ? $this->generateUuid() : '';
        $organizationUuid = $hasOrganization ? $this->generateUuid() : '';

        $patientRef      = 'urn:uuid:' . $patientUuid;
        $encounterRef    = 'urn:uuid:' . $encounterUuid;
        $practitionerRef = $hasPractitioner ? ('urn:uuid:' . $practitionerUuid) : '';
        $organizationRef = $hasOrganization ? ('urn:uuid:' . $organizationUuid) : '';

        // ── Tracking arrays ──────────────────────────────────────────────────
        $encounterDiagnosisRefs = [];
        $complaintRefs          = [];
        $observationRefs        = [];
        $allergyRefs            = [];
        $medicationRefs         = [];
        $serviceRequestRefs     = [];
        $appointmentRefs        = [];

        // Resource entries collected here; Composition prepended at the end.
        $resourceEntries = [];

        // ── Practitioner ─────────────────────────────────────────────────────
        if ($hasPractitioner) {
            $practResource = [
                'resourceType' => 'Practitioner',
                'id'           => $practitionerUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner']],
                'name'         => [['text' => trim((string) ($practitioner['name'] ?? ''))]],
            ];
            $regNumber = trim((string) ($practitioner['registration_number'] ?? ''));
            if ($regNumber !== '') {
                $practResource['identifier'] = [[
                    'type'   => ['coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                        'code'    => 'MD',
                        'display' => 'Medical License number',
                    ]]],
                    'system' => 'https://doctor.ndhm.gov.in',
                    'value'  => $regNumber,
                ]];
            }
            $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $practitionerUuid, 'resource' => $practResource];
        }

        // ── Organization ─────────────────────────────────────────────────────
        if ($hasOrganization) {
            $orgResource = [
                'resourceType' => 'Organization',
                'id'           => $organizationUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization']],
                'name'         => trim((string) ($organization['name'] ?? '')),
            ];
            $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
            if ($hfrId !== '') {
                $orgResource['identifier'] = [[
                    'type'   => ['coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                        'code'    => 'PRN',
                        'display' => 'Provider number',
                    ]]],
                    'system' => 'https://facility.ndhm.gov.in',
                    'value'  => $hfrId,
                ]];
            }
            $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $organizationUuid, 'resource' => $orgResource];
        }

        // ── Patient ───────────────────────────────────────────────────────────
        $patientResource       = $this->buildPatientResource($patient);
        $patientResource['id'] = $patientUuid;  // override DB id with UUID
        $resourceEntries[]     = ['fullUrl' => $patientRef, 'resource' => $patientResource];

        // ── Encounter (built here, appended after conditions loop) ──────────
        $encounterResource = [
            'resourceType' => 'Encounter',
            'id'           => $encounterUuid,
            'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter']],
            'identifier'   => [['system' => 'https://ndhm.in', 'value' => (string) ($encounter['id'] ?? $encounterUuid)]],
            'status'       => (string) ($encounter['status'] ?? 'finished'),
            'class'        => [
                'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code'    => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            'period'  => ['start' => trim((string) ($encounter['period_start'] ?? '')) !== '' ? (string) $encounter['period_start'] : $issuedAt],
        ];
        if ($practitionerRef !== '') {
            $encounterResource['participant'] = [['individual' => ['reference' => $practitionerRef]]];
        }
        if ($organizationRef !== '') {
            $encounterResource['serviceProvider'] = ['reference' => $organizationRef];
        }

        // ── Conditions (diagnoses) ────────────────────────────────────────────
        foreach ($conditions as $index => $condition) {
            $text = trim((string) ($condition['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $condUuid         = $this->generateUuid();
            $condRef          = 'urn:uuid:' . $condUuid;
            $verification     = trim((string) ($condition['verification_status'] ?? 'provisional'));
            $verificationCode = strtolower($verification) === 'confirmed' ? 'confirmed' : 'provisional';
            $useSnomedCode    = $verificationCode === 'confirmed' ? '39154008' : '148006';
            $useSnomedDisplay = $verificationCode === 'confirmed' ? 'Clinical diagnosis' : 'Preliminary diagnosis';

            $encounterDiagnosisRefs[] = [
                'condition' => ['reference' => $condRef, 'display' => 'Condition'],
                'use'       => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $useSnomedCode,
                    'display' => $useSnomedDisplay,
                ]]],
            ];

            $code       = ['text' => $text];
            $snomedCode = trim((string) ($condition['snomed_code'] ?? ''));
            if ($snomedCode !== '') {
                $code['coding'] = [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $snomedCode,
                    'display' => trim((string) ($condition['snomed_display'] ?? $text)),
                ]];
            }

            $resourceEntries[] = ['fullUrl' => $condRef, 'resource' => [
                'resourceType'       => 'Condition',
                'id'                 => $condUuid,
                'meta'               => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition']],
                'clinicalStatus'     => ['coding' => [[
                    'system'  => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                    'code'    => 'active',
                    'display' => 'Active',
                ]]],
                'verificationStatus' => ['coding' => [[
                    'system'  => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                    'code'    => $verificationCode,
                    'display' => ucfirst($verificationCode),
                ]]],
                'code'    => $code,
                'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            ]];
        }

        // Append Encounter (now has diagnosis refs)
        if (! empty($encounterDiagnosisRefs)) {
            $encounterResource['diagnosis'] = $encounterDiagnosisRefs;
        }
        $resourceEntries[] = ['fullUrl' => $encounterRef, 'resource' => $encounterResource];

        // ── Complaints (Chief Complaints) ─────────────────────────────────
        foreach ($complaints as $index => $complaint) {
            $text = trim((string) ($complaint['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $complaintUuid   = $this->generateUuid();
            $complaintRef    = 'urn:uuid:' . $complaintUuid;
            $complaintRefs[] = ['reference' => $complaintRef, 'display' => 'Condition'];
            $code            = ['text' => $text];

            $snomedCode = trim((string) ($complaint['snomed_code'] ?? ''));
            if ($snomedCode !== '') {
                $code['coding'] = [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $snomedCode,
                    'display' => trim((string) ($complaint['snomed_display'] ?? $text)),
                ]];
            }

            $complaintResource = [
                'resourceType'   => 'Condition',
                'id'             => $complaintUuid,
                'meta'           => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition']],
                'clinicalStatus' => ['coding' => [[
                    'system'  => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                    'code'    => 'active',
                    'display' => 'Active',
                ]]],
                'code'    => $code,
                'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            ];

            // Severity (High/Moderate/Low → SNOMED coded)
            $severityText = trim((string) ($complaint['severity'] ?? ''));
            if ($severityText !== '') {
                $severityMap = [
                    'high'     => ['24484000', 'Severe'],
                    'severe'   => ['24484000', 'Severe'],
                    'moderate' => ['6736007',  'Moderate'],
                    'mild'     => ['255604002', 'Mild'],
                    'low'      => ['255604002', 'Mild'],
                ];
                $severityKey = strtolower($severityText);
                [$sevCode, $sevDisplay] = $severityMap[$severityKey] ?? ['', $severityText];
                $severityCoding = ['text' => ucfirst($severityText)];
                if ($sevCode !== '') {
                    $severityCoding['coding'] = [[
                        'system'  => 'http://snomed.info/sct',
                        'code'    => $sevCode,
                        'display' => $sevDisplay,
                    ]];
                }
                $complaintResource['severity'] = $severityCoding;
            }

            // Duration → note
            $durationText  = trim((string) ($complaint['duration'] ?? ''));
            $frequencyText = trim((string) ($complaint['frequency'] ?? ''));
            $noteParts     = array_filter([$durationText, $frequencyText]);
            if (! empty($noteParts)) {
                $complaintResource['note'] = [['text' => implode(' | ', $noteParts)]];
            }

            $resourceEntries[] = ['fullUrl' => $complaintRef, 'resource' => $complaintResource];
        }

        // ── Observations (Vitals / Physical Examination) ──────────────────────
        foreach ($observations as $index => $observation) {
            $value = $observation['value'] ?? null;
            if (! is_numeric($value)) {
                continue;
            }

            $obsUuid           = $this->generateUuid();
            $observationRefs[] = ['reference' => 'urn:uuid:' . $obsUuid, 'display' => 'Observation'];
            $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $obsUuid, 'resource' => [
                'resourceType'      => 'Observation',
                'id'                => $obsUuid,
                'meta'              => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/ObservationVitalSigns']],
                'status'            => 'final',
                'category'          => [['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                    'code'   => 'vital-signs',
                ]]]],
                'code'              => [
                    'coding' => [[
                        'system'  => 'http://loinc.org',
                        'code'    => (string) ($observation['loinc'] ?? ''),
                        'display' => (string) ($observation['display'] ?? ''),
                    ]],
                    'text' => (string) ($observation['display'] ?? ''),
                ],
                'subject'           => ['reference' => $patientRef, 'display' => 'Patient'],
                'encounter'         => ['reference' => $encounterRef],
                'effectiveDateTime' => $issuedAt,
                'valueQuantity'     => [
                    'value'  => (float) $value,
                    'unit'   => (string) ($observation['unit'] ?? ''),
                    'system' => 'http://unitsofmeasure.org',
                    'code'   => (string) ($observation['ucum'] ?? ''),
                ],
            ]];
        }

        foreach ($allergies as $index => $allergy) {
            $codeText = trim((string) ($allergy['code_text'] ?? ''));
            if ($codeText === '') {
                continue;
            }

            $allergyUuid   = $this->generateUuid();
            $allergyRefs[] = ['reference' => 'urn:uuid:' . $allergyUuid, 'display' => 'AllergyIntolerance'];

            $allergyResource = [
                'resourceType'       => 'AllergyIntolerance',
                'id'                 => $allergyUuid,
                'meta'               => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/AllergyIntolerance']],
                'clinicalStatus'     => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                    'code'   => (string) ($allergy['clinical_status'] ?? 'active'),
                ]]],
                'verificationStatus' => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification',
                    'code'   => (string) ($allergy['verification_status'] ?? 'confirmed'),
                ]]],
                'code'    => ['text' => $codeText],
                'patient' => ['reference' => $patientRef, 'display' => 'Patient'],
                'recordedDate' => $issuedAt,
            ];

            $reaction = trim((string) ($allergy['reaction_text'] ?? ''));
            if ($reaction !== '') {
                $allergyResource['reaction'] = [['description' => $reaction]];
            }

            $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $allergyUuid, 'resource' => $allergyResource];
        }

        // ── Medications ───────────────────────────────────────────────────────
        foreach ($medications as $index => $medication) {
            $medUuid          = $this->generateUuid();
            $medicationRefs[] = ['reference' => 'urn:uuid:' . $medUuid, 'display' => 'MedicationRequest'];

            $drugName    = trim((string) ($medication['drug_name'] ?? ''));
            $genericName = trim((string) ($medication['generic_name'] ?? ''));
            $medType     = trim((string) ($medication['med_type'] ?? ''));
            $displayText = $drugName;
            if ($genericName !== '' && stripos($drugName, $genericName) === false) {
                $displayText = trim($drugName . ' (' . $genericName . ')');
            }

            $medicationCodeableConcept = ['text' => $displayText !== '' ? $displayText : $drugName];

            $snomedCode = trim((string) ($medication['snomed_code'] ?? ''));
            $atcCode    = strtoupper(trim((string) ($medication['atc_code'] ?? '')));
            if ($snomedCode !== '') {
                $medicationCodeableConcept['coding'] = [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $snomedCode,
                    'display' => $displayText !== '' ? $displayText : $drugName,
                ]];
            } elseif ($atcCode !== '') {
                $medicationCodeableConcept['coding'] = [[
                    'system'  => 'http://www.whocc.no/atc',
                    'code'    => $atcCode,
                    'display' => $displayText !== '' ? $displayText : $drugName,
                ]];
            }

            $dosageInstruction = ['text' => (string) ($medication['dosage'] ?? '')];
            $routeText = trim((string) ($medication['route_text'] ?? ''));
            if ($routeText !== '') {
                $dosageInstruction['route'] = ['text' => $routeText];
            }
            if ($medType !== '') {
                $dosageInstruction['method'] = ['text' => $medType];
            }

            $medRes = [
                'resourceType'              => 'MedicationRequest',
                'id'                        => $medUuid,
                'meta'                      => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationRequest']],
                'status'                    => (string) ($medication['status'] ?? 'active'),
                'intent'                    => 'order',
                'medicationCodeableConcept' => $medicationCodeableConcept,
                'subject'                   => ['reference' => $patientRef, 'display' => 'Patient'],
                'encounter'                 => ['reference' => $encounterRef],
                'authoredOn'                => $issuedAt,
                'dosageInstruction'         => [$dosageInstruction],
            ];
            if ($practitionerRef !== '') {
                $medRes['requester'] = ['reference' => $practitionerRef];
            }
            $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $medUuid, 'resource' => $medRes];
        }

        // ── ServiceRequests (Investigation Advice) ────────────────────────────
        foreach ($serviceRequests as $index => $serviceRequest) {
            $codeText = trim((string) ($serviceRequest['code_text'] ?? ''));
            if ($codeText === '') {
                continue;
            }

            $svcUuid              = $this->generateUuid();
            $serviceRequestRefs[] = ['reference' => 'urn:uuid:' . $svcUuid, 'display' => 'ServiceRequest'];
            $svcRes = [
                'resourceType' => 'ServiceRequest',
                'id'           => $svcUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/ServiceRequest']],
                'status'       => (string) ($serviceRequest['status'] ?? 'active'),
                'intent'       => (string) ($serviceRequest['intent'] ?? 'order'),
                'code'         => ['text' => $codeText],
                'subject'      => ['reference' => $patientRef, 'display' => 'Patient'],
                'encounter'    => ['reference' => $encounterRef],
                'authoredOn'   => $issuedAt,
            ];
            if ($practitionerRef !== '') {
                $svcRes['requester'] = ['reference' => $practitionerRef];
            }
            $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $svcUuid, 'resource' => $svcRes];
        }

        // ── Appointments (Follow Up) ──────────────────────────────────────────
        foreach ($appointments as $index => $appointment) {
            $description = trim((string) ($appointment['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $apptUuid        = $this->generateUuid();
            $appointmentRefs[] = ['reference' => 'urn:uuid:' . $apptUuid, 'display' => 'Appointment'];
            $apptRes = [
                'resourceType' => 'Appointment',
                'id'           => $apptUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Appointment']],
                'status'       => (string) ($appointment['status'] ?? 'proposed'),
                'description'  => $description,
                'participant'  => [[
                    'actor'  => ['reference' => $patientRef, 'display' => 'Patient'],
                    'status' => 'accepted',
                ]],
            ];
            if ($practitionerRef !== '') {
                $apptRes['participant'][] = [
                    'actor'  => ['reference' => $practitionerRef],
                    'status' => 'accepted',
                ];
            }
            $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $apptUuid, 'resource' => $apptRes];
        }

        // ── Composition sections (ABDM SNOMED section codes) ─────────────────
        $compositionSections = [];
        if (! empty($complaintRefs)) {
            $compositionSections[] = [
                'title' => 'Chief complaints',
                'code'  => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '422843007',
                    'display' => 'Chief complaint section',
                ]]],
                'entry' => $complaintRefs,
            ];
        }
        if (! empty($allergyRefs)) {
            $compositionSections[] = [
                'title' => 'Allergies',
                'code'  => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '722446000',
                    'display' => 'Allergy record',
                ]]],
                'entry' => $allergyRefs,
            ];
        }
        if (! empty($observationRefs)) {
            $compositionSections[] = [
                'title' => 'Physical Examination',
                'code'  => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '425044008',
                    'display' => 'Physical exam section',
                ]]],
                'entry' => $observationRefs,
            ];
        }
        if (! empty($encounterDiagnosisRefs)) {
            // Diagnoses section — conditions referenced from Encounter.diagnosis
            $diagSectionEntries = array_map(static fn ($d) => $d['condition'], $encounterDiagnosisRefs);
            $compositionSections[] = [
                'title' => 'Problems and Diagnoses',
                'code'  => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '439401001',
                    'display' => 'Diagnosis',
                ]]],
                'entry' => $diagSectionEntries,
            ];
        }
        if (! empty($medicationRefs)) {
            $compositionSections[] = [
                'title' => 'Medications',
                'code'  => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '721912009',
                    'display' => 'Medication summary document',
                ]]],
                'entry' => $medicationRefs,
            ];
        }
        if (! empty($serviceRequestRefs)) {
            $compositionSections[] = [
                'title' => 'Investigation Advice',
                'code'  => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '721963009',
                    'display' => 'Order document',
                ]]],
                'entry' => $serviceRequestRefs,
            ];
        }
        if (! empty($appointmentRefs)) {
            $compositionSections[] = [
                'title' => 'Follow Up',
                'code'  => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '736271009',
                    'display' => 'Outpatient care plan',
                ]]],
                'entry' => $appointmentRefs,
            ];
        }

        // ── Composition (first entry per ABDM spec) ───────────────────────────
        $composition = [
            'resourceType' => 'Composition',
            'id'           => $compositionUuid,
            'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/OPConsultRecord']],
            'language'     => 'en-IN',
            'identifier'   => ['system' => 'https://ndhm.in/phr', 'value' => $compositionUuid],
            'status'       => 'final',
            'type'         => [
                'coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '371530004',
                    'display' => 'Clinical consultation report',
                ]],
                'text' => 'Clinical Consultation report',
            ],
            'subject'   => ['reference' => $patientRef, 'display' => 'Patient'],
            'encounter' => ['reference' => $encounterRef, 'display' => 'Encounter'],
            'date'      => $issuedAt,
            'author'    => $practitionerRef !== ''
                ? [['reference' => $practitionerRef, 'display' => 'Practitioner']]
                : [['display' => 'Unknown']],
            'title'     => 'Consultation Report',
            'section'   => $compositionSections,
        ];
        if ($organizationRef !== '') {
            $composition['custodian'] = ['reference' => $organizationRef, 'display' => 'Organization'];
        }

        // Composition first, then all resource entries
        $allEntries = array_merge(
            [['fullUrl' => 'urn:uuid:' . $compositionUuid, 'resource' => $composition]],
            $resourceEntries
        );

        // ── Bundle ────────────────────────────────────────────────────────────
        return [
            'resourceType' => 'Bundle',
            'id'           => $bundleUuid,
            'meta'         => [
                'profile'  => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle'],
                'security' => [[
                    'system'  => 'http://terminology.hl7.org/CodeSystem/v3-Confidentiality',
                    'code'    => 'V',
                    'display' => 'very restricted',
                ]],
            ],
            'identifier' => ['system' => 'http://hip.in', 'value' => $bundleUuid],
            'type'       => 'document',
            'timestamp'  => $issuedAt,
            'entry'      => $allEntries,
        ];
    }

    /**
     * @param array<string, mixed> $patient
     * @param array<string, mixed> $diagnosticReport
     * @param array<int, array<string, mixed>> $observations
     *
     * @return array<string, mixed>
     */
    public function buildLabReportBundle(array $patient, array $diagnosticReport, array $observations): array
    {
        $issuedAt = Time::now('Asia/Kolkata')->toDateTimeString();
        $patientRef = 'Patient/' . (string) ($patient['id'] ?? 'unknown');
        $reportId = (string) ($diagnosticReport['id'] ?? 'lab-report-1');

        $entries = [[
            'resource' => $this->buildPatientResource($patient),
        ]];

        $observationRefs = [];
        foreach ($observations as $index => $observation) {
            $observationId = 'obs-' . ($index + 1);
            $observationRefs[] = ['reference' => 'Observation/' . $observationId];
            $entries[] = [
                'resource' => [
                    'resourceType' => 'Observation',
                    'id' => $observationId,
                    'status' => (string) ($observation['status'] ?? 'final'),
                    'code' => [
                        'text' => (string) ($observation['test_name'] ?? ''),
                    ],
                    'subject' => ['reference' => $patientRef],
                    'valueString' => (string) ($observation['value'] ?? ''),
                    'interpretation' => [[
                        'text' => (string) ($observation['interpretation'] ?? ''),
                    ]],
                ],
            ];
        }

        $entries[] = [
            'resource' => [
                'resourceType' => 'DiagnosticReport',
                'id' => $reportId,
                'status' => (string) ($diagnosticReport['status'] ?? 'final'),
                'code' => [
                    'text' => (string) ($diagnosticReport['title'] ?? 'Laboratory Report'),
                ],
                'subject' => ['reference' => $patientRef],
                'issued' => $issuedAt,
                'result' => $observationRefs,
                'conclusion' => (string) ($diagnosticReport['conclusion'] ?? ''),
            ],
        ];

        return [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'timestamp' => $issuedAt,
            'entry' => $entries,
        ];
    }

    /**
     * @param array<string, mixed> $patient
     * @param array<string, mixed> $encounter
     * @param array<string, mixed> $summary
     *
     * @return array<string, mixed>
     */
    public function buildDischargeSummaryBundle(array $patient, array $encounter, array $summary): array
    {
        $issuedAt = Time::now('Asia/Kolkata')->toDateTimeString();
        $patientId = (string) ($patient['id'] ?? 'unknown');
        $encounterId = (string) ($encounter['id'] ?? 'unknown');

        return [
            'resourceType' => 'Bundle',
            'type' => 'document',
            'timestamp' => $issuedAt,
            'entry' => [[
                'resource' => $this->buildPatientResource($patient),
            ], [
                'resource' => [
                    'resourceType' => 'Composition',
                    'status' => 'final',
                    'type' => [
                        'text' => 'Discharge Summary',
                    ],
                    'subject' => [
                        'reference' => 'Patient/' . $patientId,
                    ],
                    'encounter' => [
                        'reference' => 'Encounter/' . $encounterId,
                    ],
                    'date' => $issuedAt,
                    'title' => (string) ($summary['title'] ?? 'Discharge Summary'),
                    'section' => [[
                        'title' => 'Clinical Summary',
                        'text' => [
                            'status' => 'generated',
                            'div' => (string) ($summary['clinical_summary_html'] ?? ''),
                        ],
                    ]],
                ],
            ]],
        ];
    }

    /**
     * @param array<string, mixed> $patient
     * @param array<string, mixed> $encounter
     * @param array<string, mixed> $claim
     *
     * @return array<string, mixed>
     */
    public function buildClaimBundle(array $patient, array $encounter, array $claim): array
    {
        $issuedAt = Time::now('Asia/Kolkata')->toDateTimeString();
        $patientRef = 'Patient/' . (string) ($patient['id'] ?? 'unknown');
        $encounterRef = 'Encounter/' . (string) ($encounter['id'] ?? 'unknown');

        $claimId = (string) ($claim['id'] ?? ('claim-' . time()));
        $items = is_array($claim['items'] ?? null) ? ($claim['items'] ?? []) : [];

        $claimItems = [];
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $claimItems[] = [
                'sequence' => $index + 1,
                'productOrService' => [
                    'text' => (string) ($item['name'] ?? $item['description'] ?? ''),
                ],
                'quantity' => [
                    'value' => (float) ($item['qty'] ?? 1),
                ],
                'unitPrice' => [
                    'value' => (float) ($item['unit_price'] ?? 0),
                    'currency' => (string) ($item['currency'] ?? 'INR'),
                ],
                'net' => [
                    'value' => (float) ($item['amount'] ?? 0),
                    'currency' => (string) ($item['currency'] ?? 'INR'),
                ],
            ];
        }

        $claimResource = [
            'resourceType' => 'Claim',
            'id' => $claimId,
            'status' => (string) ($claim['status'] ?? 'active'),
            'use' => (string) ($claim['use'] ?? 'claim'),
            'type' => [
                'text' => (string) ($claim['type'] ?? 'institutional'),
            ],
            'patient' => ['reference' => $patientRef],
            'created' => $issuedAt,
            'provider' => [
                'display' => (string) ($claim['provider'] ?? ''),
            ],
            'insurer' => [
                'display' => (string) ($claim['insurer'] ?? ''),
            ],
            'priority' => [
                'text' => (string) ($claim['priority'] ?? 'normal'),
            ],
            'item' => $claimItems,
            'total' => [
                'value' => (float) ($claim['total'] ?? 0),
                'currency' => (string) ($claim['currency'] ?? 'INR'),
            ],
            'encounter' => [[
                'reference' => $encounterRef,
            ]],
        ];

        return [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'timestamp' => $issuedAt,
            'entry' => [[
                'resource' => $this->buildPatientResource($patient),
            ], [
                'resource' => [
                    'resourceType' => 'Encounter',
                    'id' => (string) ($encounter['id'] ?? 'unknown'),
                    'status' => (string) ($encounter['status'] ?? 'finished'),
                    'subject' => ['reference' => $patientRef],
                ],
            ], [
                'resource' => $claimResource,
            ]],
        ];
    }

    /** Generate a cryptographically random UUID v4. */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /** ISO 8601 timestamp with India Standard Time offset (+05:30). */
    private function isoTimestamp(): string
    {
        return (new \DateTime('now', new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d\TH:i:sP');
    }

    /**
     * @param array<string, mixed> $patient
     *
     * @return array<string, mixed>
     */
    private function buildPatientResource(array $patient): array
    {
        $resource = [
            'resourceType' => 'Patient',
            'id' => (string) ($patient['id'] ?? 'unknown'),
            'name' => [[
                'text' => (string) ($patient['name'] ?? ''),
            ]],
        ];

        if (! empty($patient['gender'])) {
            $resource['gender'] = (string) $patient['gender'];
        }

        if (! empty($patient['birthDate'])) {
            $resource['birthDate'] = (string) $patient['birthDate'];
        }

        $abhaAddress = trim((string) ($patient['abhaAddress'] ?? ''));
        if ($abhaAddress !== '') {
            $resource['meta']       = ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient']];
            $resource['identifier'] = [[
                'type'   => ['coding' => [[
                    'system'  => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                    'code'    => 'MR',
                    'display' => 'Medical record number',
                ]]],
                'system' => 'https://healthid.ndhm.gov.in',
                'value'  => $abhaAddress,
            ]];
        }

        return $resource;
    }

    // =========================================================================
    // Enhanced FHIR Bundle Builders (ABDM M3 Compliant)
    // =========================================================================

    /**
     * Build a full DiagnosticReport FHIR Bundle (ABDM M3 — DiagnosticReportRecord).
     *
     * Supports LOINC-coded observations, specimen metadata, Practitioner performer,
     * and an optional scanned-document attachment via DocumentReference + Binary.
     *
     * @param array<string, mixed>              $patient          Patient demographics
     * @param array<string, mixed>              $diagnosticReport {id, title, category_loinc,
     *                                                             category_display, status, conclusion, reported_at}
     * @param array<int, array<string, mixed>>  $observations     Each: {test_name, loinc_code, value,
     *                                                             value_type (quantity|string), unit, ucum_code,
     *                                                             reference_range_low, reference_range_high,
     *                                                             interpretation, status}
     * @param array<string, mixed>|null         $specimen         {id, type_text, collection_date}
     * @param array<string, mixed>|null         $attachment       {content_type, data_base64, title}
     * @param array<string, mixed>|null         $practitioner     {id, name, registration_number}
     * @param array<string, mixed>|null         $organization     {id, name, hfr_id}
     *
     * @return array<string, mixed>
     */
    public function buildEnhancedLabReportBundle(
        array $patient,
        array $diagnosticReport,
        array $observations = [],
        ?array $specimen = null,
        ?array $attachment = null,
        ?array $practitioner = null,
        ?array $organization = null
    ): array {
        $issuedAt   = Time::now('Asia/Kolkata')->toDateTimeString();
        $patientRef = 'Patient/' . (string) ($patient['id'] ?? 'unknown');
        $reportId   = 'lab-rpt-' . (string) ($diagnosticReport['id'] ?? date('YmdHis'));

        $entries = [['resource' => $this->buildPatientResource($patient)]];

        $practitionerRef = '';
        $organizationRef = '';

        if ($practitioner !== null) {
            $practId         = trim((string) ($practitioner['id'] ?? ''));
            $practitionerRef = $practId !== '' ? ('Practitioner/' . $practId) : '';
            if ($practitionerRef !== '') {
                $practRes = [
                    'resourceType' => 'Practitioner',
                    'id'           => $practId,
                    'name'         => [['text' => trim((string) ($practitioner['name'] ?? ''))]],
                ];
                $regNo = trim((string) ($practitioner['registration_number'] ?? ''));
                if ($regNo !== '') {
                    $practRes['identifier'] = [[
                        'system' => 'https://hpr.abdm.gov.in/hpr-id',
                        'value'  => $regNo,
                    ]];
                }
                $entries[] = ['resource' => $practRes];
            }
        }

        if ($organization !== null) {
            $orgId           = trim((string) ($organization['id'] ?? ''));
            $organizationRef = $orgId !== '' ? ('Organization/' . $orgId) : '';
            if ($organizationRef !== '') {
                $orgRes = [
                    'resourceType' => 'Organization',
                    'id'           => $orgId,
                    'name'         => trim((string) ($organization['name'] ?? '')),
                ];
                $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
                if ($hfrId !== '') {
                    $orgRes['identifier'] = [[
                        'system' => 'https://facility.abdm.gov.in/hfr',
                        'value'  => $hfrId,
                    ]];
                }
                $entries[] = ['resource' => $orgRes];
            }
        }

        // Specimen (optional)
        $specimenRef = '';
        if ($specimen !== null) {
            $specimenId  = 'specimen-' . (string) ($specimen['id'] ?? '1');
            $specimenRef = 'Specimen/' . $specimenId;
            $specimenRes = [
                'resourceType' => 'Specimen',
                'id'           => $specimenId,
                'subject'      => ['reference' => $patientRef],
                'type'         => ['text' => (string) ($specimen['type_text'] ?? 'Blood')],
            ];
            $collDate = trim((string) ($specimen['collection_date'] ?? ''));
            if ($collDate !== '') {
                $specimenRes['collection'] = ['collectedDateTime' => $collDate];
            }
            $entries[] = ['resource' => $specimenRes];
        }

        // Observations
        $observationRefs = [];
        foreach ($observations as $index => $obs) {
            $obsId   = 'obs-' . ($index + 1);
            $observationRefs[] = ['reference' => 'Observation/' . $obsId];

            $loincCode = trim((string) ($obs['loinc_code'] ?? ''));
            $testName  = (string) ($obs['test_name'] ?? '');
            $valueType = trim((string) ($obs['value_type'] ?? 'string'));
            $rawValue  = $obs['value'] ?? '';
            $unit      = (string) ($obs['unit'] ?? '');
            $ucumCode  = (string) ($obs['ucum_code'] ?? $unit);
            $interpText = (string) ($obs['interpretation'] ?? '');

            $code = ['text' => $testName];
            if ($loincCode !== '') {
                $code['coding'] = [[
                    'system'  => 'http://loinc.org',
                    'code'    => $loincCode,
                    'display' => $testName,
                ]];
            }

            $obsResource = [
                'resourceType'     => 'Observation',
                'id'               => $obsId,
                'status'           => (string) ($obs['status'] ?? 'final'),
                'category'         => [[
                    'coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code'    => 'laboratory',
                        'display' => 'Laboratory',
                    ]],
                ]],
                'code'    => $code,
                'subject' => ['reference' => $patientRef],
                'issued'  => $issuedAt,
            ];

            if ($specimenRef !== '') {
                $obsResource['specimen'] = ['reference' => $specimenRef];
            }
            if ($practitionerRef !== '') {
                $obsResource['performer'] = [['reference' => $practitionerRef]];
            }

            if ($valueType === 'quantity' && is_numeric($rawValue)) {
                $obsResource['valueQuantity'] = [
                    'value'  => (float) $rawValue,
                    'unit'   => $unit,
                    'system' => 'http://unitsofmeasure.org',
                    'code'   => $ucumCode !== '' ? $ucumCode : $unit,
                ];
            } else {
                $obsResource['valueString'] = (string) $rawValue;
            }

            $refLow  = $obs['reference_range_low']  ?? null;
            $refHigh = $obs['reference_range_high'] ?? null;
            if ($refLow !== null || $refHigh !== null) {
                $refRange = [];
                if (is_numeric($refLow))  { $refRange['low']  = ['value' => (float) $refLow,  'unit' => $unit]; }
                if (is_numeric($refHigh)) { $refRange['high'] = ['value' => (float) $refHigh, 'unit' => $unit]; }
                if (! empty($refRange)) {
                    $obsResource['referenceRange'] = [$refRange];
                }
            }

            if ($interpText !== '') {
                $obsResource['interpretation'] = [['text' => $interpText]];
            }

            $entries[] = ['resource' => $obsResource];
        }

        // Optional scanned attachment
        $docRefRef = '';
        if ($attachment !== null && ! empty($attachment['data_base64'])) {
            $docEntries = $this->buildDocumentReferenceEntry(
                'doc-lab-' . $reportId,
                (string) ($attachment['title'] ?? 'Lab Report Document'),
                (string) ($attachment['content_type'] ?? 'application/pdf'),
                (string) $attachment['data_base64'],
                $patientRef,
                $issuedAt
            );
            $docRefRef = 'DocumentReference/doc-lab-' . $reportId;
            foreach ($docEntries as $entry) {
                $entries[] = $entry;
            }
        }

        $performers = [];
        if ($practitionerRef !== '') { $performers[] = ['reference' => $practitionerRef]; }
        if ($organizationRef  !== '') { $performers[] = ['reference' => $organizationRef]; }

        $categoryLoinc   = trim((string) ($diagnosticReport['category_loinc']   ?? '26436-6'));
        $categoryDisplay = trim((string) ($diagnosticReport['category_display'] ?? 'Laboratory studies (procedure)'));

        $drResource = [
            'resourceType' => 'DiagnosticReport',
            'id'           => $reportId,
            'status'       => (string) ($diagnosticReport['status'] ?? 'final'),
            'category'     => [[
                'coding' => [[
                    'system'  => 'http://loinc.org',
                    'code'    => $categoryLoinc,
                    'display' => $categoryDisplay,
                ]],
            ]],
            'code'       => ['text' => (string) ($diagnosticReport['title'] ?? 'Laboratory Report')],
            'subject'    => ['reference' => $patientRef],
            'issued'     => (string) ($diagnosticReport['reported_at'] ?? $issuedAt),
            'result'     => $observationRefs,
            'conclusion' => (string) ($diagnosticReport['conclusion'] ?? ''),
        ];

        if ($specimenRef !== '') {
            $drResource['specimen'] = [['reference' => $specimenRef]];
        }
        if (! empty($performers)) {
            $drResource['performer'] = $performers;
        }
        if ($docRefRef !== '') {
            $drResource['presentedForm'] = [[
                'contentType' => (string) ($attachment['content_type'] ?? 'application/pdf'),
                'title'       => (string) ($attachment['title'] ?? 'Lab Report'),
            ]];
        }
        $entries[] = ['resource' => $drResource];

        // Composition (LOINC 11502-2 = Laboratory report)
        $compositionSections = [
            ['title' => 'Lab Results', 'entry' => $observationRefs],
        ];
        if ($docRefRef !== '') {
            $compositionSections[] = [
                'title' => 'Report Document',
                'entry' => [['reference' => $docRefRef]],
            ];
        }

        $composition = [
            'resourceType' => 'Composition',
            'id'           => 'comp-' . $reportId,
            'status'       => 'final',
            'type'         => [
                'coding' => [[
                    'system'  => 'http://loinc.org',
                    'code'    => '11502-2',
                    'display' => 'Laboratory report',
                ]],
                'text' => 'Diagnostic Report - Lab',
            ],
            'subject' => ['reference' => $patientRef],
            'date'    => $issuedAt,
            'title'   => (string) ($diagnosticReport['title'] ?? 'Laboratory Report'),
            'section' => $compositionSections,
        ];
        if ($practitionerRef !== '') {
            $composition['author'] = [['reference' => $practitionerRef]];
        }
        $entries[] = ['resource' => $composition];

        return [
            'resourceType' => 'Bundle',
            'identifier'   => [
                'system' => 'urn:ietf:rfc:3986',
                'value'  => 'urn:uuid:bundle-lab-' . $reportId . '-' . date('YmdHis'),
            ],
            'type'      => 'document',
            'timestamp' => $issuedAt,
            'entry'     => $entries,
        ];
    }

    // -------------------------------------------------------------------------

    /**
     * Build a full Discharge Summary FHIR Bundle (ABDM M3 — DischargeSummaryRecord).
     *
     * Covers all mandatory composition sections: chief complaints, diagnosis,
     * allergies, procedures, medications, investigations, care plan, and a
     * free-text clinical notes narrative.
     *
     * @param array<string, mixed> $patient      Patient demographics
     * @param array<string, mixed> $practitioner {id, name, registration_number}
     * @param array<string, mixed> $organization {id, name, hfr_id}
     * @param array<string, mixed> $encounter    {id, admission_date, discharge_date}
     * @param array<string, mixed> $summary {
     *   title, chief_complaints[], conditions[], allergies[],
     *   procedures[], medications[], investigations[], care_plan,
     *   follow_up_date, clinical_notes_html
     * }
     *
     * @return array<string, mixed>
     */
    public function buildEnhancedDischargeSummaryBundle(
        array $patient,
        array $practitioner,
        array $organization,
        array $encounter,
        array $summary
    ): array {
        $issuedAt    = Time::now('Asia/Kolkata')->toDateTimeString();
        $encounterId = (string) ($encounter['id'] ?? 'enc-' . date('YmdHis'));
        $patientRef  = 'Patient/' . (string) ($patient['id'] ?? 'unknown');
        $encounterRef = 'Encounter/' . $encounterId;

        $practId         = trim((string) ($practitioner['id'] ?? ''));
        $orgId           = trim((string) ($organization['id'] ?? ''));
        $practitionerRef = $practId !== '' ? ('Practitioner/' . $practId) : '';
        $organizationRef = $orgId  !== '' ? ('Organization/' . $orgId)   : '';

        $entries = [['resource' => $this->buildPatientResource($patient)]];

        // Practitioner
        if ($practitionerRef !== '') {
            $practRes = [
                'resourceType' => 'Practitioner',
                'id'           => $practId,
                'name'         => [['text' => (string) ($practitioner['name'] ?? '')]],
            ];
            $regNo = trim((string) ($practitioner['registration_number'] ?? ''));
            if ($regNo !== '') {
                $practRes['identifier'] = [[
                    'system' => 'https://hpr.abdm.gov.in/hpr-id',
                    'value'  => $regNo,
                ]];
            }
            $entries[] = ['resource' => $practRes];
        }

        // Organization
        if ($organizationRef !== '') {
            $orgRes = [
                'resourceType' => 'Organization',
                'id'           => $orgId,
                'name'         => (string) ($organization['name'] ?? ''),
            ];
            $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
            if ($hfrId !== '') {
                $orgRes['identifier'] = [[
                    'system' => 'https://facility.abdm.gov.in/hfr',
                    'value'  => $hfrId,
                ]];
            }
            $entries[] = ['resource' => $orgRes];
        }

        // Encounter (inpatient)
        $encounterResource = [
            'resourceType' => 'Encounter',
            'id'           => $encounterId,
            'status'       => 'finished',
            'class'        => [
                'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code'    => 'IMP',
                'display' => 'inpatient encounter',
            ],
            'subject' => ['reference' => $patientRef],
            'period'  => [
                'start' => (string) ($encounter['admission_date'] ?? $encounter['period_start'] ?? $issuedAt),
                'end'   => (string) ($encounter['discharge_date'] ?? $encounter['period_end']   ?? $issuedAt),
            ],
        ];
        if ($practitionerRef !== '') {
            $encounterResource['participant'] = [['individual' => ['reference' => $practitionerRef]]];
        }
        if ($organizationRef !== '') {
            $encounterResource['serviceProvider'] = ['reference' => $organizationRef];
        }
        $entries[] = ['resource' => $encounterResource];

        $sections = [];

        // --- Chief Complaints ---
        $complaintRefs = [];
        foreach ((array) ($summary['chief_complaints'] ?? []) as $idx => $complaint) {
            $text = is_array($complaint)
                ? trim((string) ($complaint['text'] ?? ''))
                : trim((string) $complaint);
            if ($text === '') { continue; }

            $condId = 'cc-' . ($idx + 1);
            $code   = ['text' => $text];
            $snomedCode = is_array($complaint) ? trim((string) ($complaint['snomed_code'] ?? '')) : '';
            if ($snomedCode !== '') {
                $code['coding'] = [['system' => 'http://snomed.info/sct', 'code' => $snomedCode, 'display' => $text]];
            }

            $complaintRefs[] = ['reference' => 'Condition/' . $condId];
            $entries[] = ['resource' => [
                'resourceType'   => 'Condition',
                'id'             => $condId,
                'clinicalStatus' => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical', 'code' => 'active']]],
                'category'       => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-category', 'code' => 'problem-list-item']], 'text' => 'Chief Complaint']],
                'code'           => $code,
                'subject'        => ['reference' => $patientRef],
                'encounter'      => ['reference' => $encounterRef],
            ]];
        }
        if (! empty($complaintRefs)) {
            $sections[] = ['title' => 'Chief Complaints', 'entry' => $complaintRefs];
        }

        // --- Diagnosis / Conditions ---
        $conditionRefs    = [];
        $encounterDiagnosis = [];
        foreach ((array) ($summary['conditions'] ?? []) as $idx => $cond) {
            $text = is_array($cond) ? trim((string) ($cond['text'] ?? '')) : trim((string) $cond);
            if ($text === '') { continue; }

            $condId = 'diag-' . ($idx + 1);
            $code   = ['text' => $text];
            $snomedCode = is_array($cond) ? trim((string) ($cond['snomed_code'] ?? '')) : '';
            if ($snomedCode !== '') {
                $code['coding'] = [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $snomedCode,
                    'display' => is_array($cond) ? (string) ($cond['snomed_display'] ?? $text) : $text,
                ]];
            }

            $verStatus = is_array($cond) && strtolower((string) ($cond['verification_status'] ?? '')) === 'confirmed'
                ? 'confirmed'
                : 'provisional';

            $conditionRefs[]   = ['reference' => 'Condition/' . $condId];
            $encounterDiagnosis[] = ['condition' => ['reference' => 'Condition/' . $condId]];

            $entries[] = ['resource' => [
                'resourceType'       => 'Condition',
                'id'                 => $condId,
                'clinicalStatus'     => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical', 'code' => 'active']]],
                'verificationStatus' => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status', 'code' => $verStatus]]],
                'category'           => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-category', 'code' => 'encounter-diagnosis']]]],
                'code'               => $code,
                'subject'            => ['reference' => $patientRef],
                'encounter'          => ['reference' => $encounterRef],
            ]];
        }
        if (! empty($conditionRefs)) {
            $sections[] = ['title' => 'Diagnosis', 'entry' => $conditionRefs];
            // Back-patch encounter diagnosis references
            $encounterResource['diagnosis'] = $encounterDiagnosis;
        }

        // --- Allergies ---
        $allergyRefs = [];
        foreach ((array) ($summary['allergies'] ?? []) as $idx => $allergy) {
            $codeText = is_array($allergy)
                ? trim((string) ($allergy['code_text'] ?? ''))
                : trim((string) $allergy);
            if ($codeText === '') { continue; }

            $allergyId = 'allergy-ds-' . ($idx + 1);
            $allergyRefs[] = ['reference' => 'AllergyIntolerance/' . $allergyId];
            $allergyRes = [
                'resourceType'       => 'AllergyIntolerance',
                'id'                 => $allergyId,
                'clinicalStatus'     => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical', 'code' => 'active']]],
                'verificationStatus' => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification', 'code' => 'confirmed']]],
                'patient'            => ['reference' => $patientRef],
                'code'               => ['text' => $codeText],
            ];
            $reaction = is_array($allergy) ? trim((string) ($allergy['reaction_text'] ?? '')) : '';
            if ($reaction !== '') {
                $allergyRes['reaction'] = [['description' => $reaction]];
            }
            $entries[] = ['resource' => $allergyRes];
        }
        if (! empty($allergyRefs)) {
            $sections[] = ['title' => 'Allergies', 'entry' => $allergyRefs];
        }

        // --- Procedures ---
        $procedureRefs = [];
        foreach ((array) ($summary['procedures'] ?? []) as $idx => $procedure) {
            $text = is_array($procedure)
                ? trim((string) ($procedure['text'] ?? ''))
                : trim((string) $procedure);
            if ($text === '') { continue; }

            $procId = 'proc-' . ($idx + 1);
            $code   = ['text' => $text];
            $snomedCode = is_array($procedure) ? trim((string) ($procedure['snomed_code'] ?? '')) : '';
            if ($snomedCode !== '') {
                $code['coding'] = [['system' => 'http://snomed.info/sct', 'code' => $snomedCode, 'display' => $text]];
            }

            $procedureRefs[] = ['reference' => 'Procedure/' . $procId];
            $entries[] = ['resource' => [
                'resourceType' => 'Procedure',
                'id'           => $procId,
                'status'       => is_array($procedure) ? (string) ($procedure['status'] ?? 'completed') : 'completed',
                'code'         => $code,
                'subject'      => ['reference' => $patientRef],
                'encounter'    => ['reference' => $encounterRef],
            ]];
        }
        if (! empty($procedureRefs)) {
            $sections[] = ['title' => 'Procedures', 'entry' => $procedureRefs];
        }

        // --- Medications ---
        $medicationRefs = [];
        foreach ((array) ($summary['medications'] ?? []) as $idx => $med) {
            $drugName = is_array($med) ? trim((string) ($med['drug_name'] ?? '')) : trim((string) $med);
            if ($drugName === '') { continue; }

            $medId = 'medreq-ds-' . ($idx + 1);
            $mc    = ['text' => $drugName];
            $snomedCode = is_array($med) ? trim((string) ($med['snomed_code'] ?? '')) : '';
            $atcCode    = is_array($med) ? strtoupper(trim((string) ($med['atc_code'] ?? ''))) : '';
            if ($snomedCode !== '') {
                $mc['coding'] = [['system' => 'http://snomed.info/sct', 'code' => $snomedCode, 'display' => $drugName]];
            } elseif ($atcCode !== '') {
                $mc['coding'] = [['system' => 'http://www.whocc.no/atc', 'code' => $atcCode, 'display' => $drugName]];
            }

            $medicationRefs[] = ['reference' => 'MedicationRequest/' . $medId];
            $entries[] = ['resource' => [
                'resourceType'              => 'MedicationRequest',
                'id'                        => $medId,
                'status'                    => 'active',
                'intent'                    => 'order',
                'subject'                   => ['reference' => $patientRef],
                'encounter'                 => ['reference' => $encounterRef],
                'medicationCodeableConcept' => $mc,
                'dosageInstruction'         => [['text' => is_array($med) ? (string) ($med['dosage'] ?? '') : '']],
            ]];
        }
        if (! empty($medicationRefs)) {
            $sections[] = ['title' => 'Medications', 'entry' => $medicationRefs];
        }

        // --- Investigations ---
        $investigationRefs = [];
        foreach ((array) ($summary['investigations'] ?? []) as $idx => $inv) {
            $text = is_array($inv) ? trim((string) ($inv['text'] ?? '')) : trim((string) $inv);
            if ($text === '') { continue; }

            $svcId = 'svc-ds-' . ($idx + 1);
            $investigationRefs[] = ['reference' => 'ServiceRequest/' . $svcId];
            $entries[] = ['resource' => [
                'resourceType' => 'ServiceRequest',
                'id'           => $svcId,
                'status'       => 'completed',
                'intent'       => 'order',
                'code'         => ['text' => $text],
                'subject'      => ['reference' => $patientRef],
                'encounter'    => ['reference' => $encounterRef],
            ]];
        }
        if (! empty($investigationRefs)) {
            $sections[] = ['title' => 'Investigations', 'entry' => $investigationRefs];
        }

        // --- Care Plan ---
        $carePlanRefs = [];
        $carePlanText = trim((string) ($summary['care_plan'] ?? ''));
        if ($carePlanText !== '') {
            $cpId = 'careplan-1';
            $carePlanRefs[] = ['reference' => 'CarePlan/' . $cpId];
            $cpResource = [
                'resourceType' => 'CarePlan',
                'id'           => $cpId,
                'status'       => 'active',
                'intent'       => 'plan',
                'subject'      => ['reference' => $patientRef],
                'encounter'    => ['reference' => $encounterRef],
                'description'  => $carePlanText,
            ];
            $followUpDate = trim((string) ($summary['follow_up_date'] ?? ''));
            if ($followUpDate !== '') {
                $cpResource['period'] = ['start' => $issuedAt, 'end' => $followUpDate];
            }
            $entries[] = ['resource' => $cpResource];
            $sections[] = ['title' => 'Care Plan', 'entry' => $carePlanRefs];
        }

        // Clinical notes narrative (unstructured)
        $clinicalNotesHtml = (string) ($summary['clinical_notes_html'] ?? '');

        // Composition (LOINC 18842-5 = Discharge summary)
        $composition = [
            'resourceType' => 'Composition',
            'id'           => 'comp-discharge-' . $encounterId,
            'status'       => 'final',
            'type'         => [
                'coding' => [[
                    'system'  => 'http://loinc.org',
                    'code'    => '18842-5',
                    'display' => 'Discharge summary',
                ]],
                'text' => 'Discharge Summary',
            ],
            'subject'  => ['reference' => $patientRef],
            'encounter' => ['reference' => $encounterRef],
            'date'     => $issuedAt,
            'title'    => (string) ($summary['title'] ?? 'Discharge Summary'),
            'section'  => $sections,
        ];
        if ($practitionerRef !== '') {
            $composition['author'] = [['reference' => $practitionerRef]];
        }
        if ($clinicalNotesHtml !== '') {
            $composition['section'][] = [
                'title' => 'Clinical Summary Notes',
                'text'  => ['status' => 'generated', 'div' => $clinicalNotesHtml],
            ];
        }
        $entries[] = ['resource' => $composition];

        return [
            'resourceType' => 'Bundle',
            'identifier'   => [
                'system' => 'urn:ietf:rfc:3986',
                'value'  => 'urn:uuid:bundle-discharge-' . $encounterId . '-' . date('YmdHis'),
            ],
            'type'      => 'document',
            'timestamp' => $issuedAt,
            'entry'     => $entries,
        ];
    }

    // -------------------------------------------------------------------------

    /**
     * Build a WellnessRecord FHIR Bundle (ABDM M3 — WellnessRecord).
     *
     * Contains vital-sign / body-measurement Observations and
     * social-history lifestyle Observations.
     *
     * @param array<string, mixed>             $patient      Patient demographics
     * @param array<int, array<string, mixed>> $vitals       Each: {loinc_code, display, value,
     *                                                        value_type (quantity|string), unit, ucum_code}
     * @param array<int, array<string, mixed>> $lifestyle    Each: {code, display, value, system}
     * @param array<string, mixed>|null        $practitioner {id, name}
     *
     * @return array<string, mixed>
     */
    public function buildWellnessBundle(
        array $patient,
        array $vitals = [],
        array $lifestyle = [],
        ?array $practitioner = null
    ): array {
        $issuedAt   = Time::now('Asia/Kolkata')->toDateTimeString();
        $patientRef = 'Patient/' . (string) ($patient['id'] ?? 'unknown');
        $entries    = [['resource' => $this->buildPatientResource($patient)]];

        $practitionerRef = '';
        if ($practitioner !== null) {
            $practId         = trim((string) ($practitioner['id'] ?? ''));
            $practitionerRef = $practId !== '' ? ('Practitioner/' . $practId) : '';
            if ($practitionerRef !== '') {
                $entries[] = ['resource' => [
                    'resourceType' => 'Practitioner',
                    'id'           => $practId,
                    'name'         => [['text' => (string) ($practitioner['name'] ?? '')]],
                ]];
            }
        }

        $observationRefs = [];

        // Vital signs / body measurements
        foreach ($vitals as $idx => $vital) {
            $loincCode = trim((string) ($vital['loinc_code'] ?? ''));
            $display   = (string) ($vital['display'] ?? '');
            $value     = $vital['value'] ?? '';
            $unit      = (string) ($vital['unit'] ?? '');
            $ucumCode  = (string) ($vital['ucum_code'] ?? $unit);
            $valueType = (string) ($vital['value_type'] ?? 'quantity');

            if ($valueType === 'quantity' && ! is_numeric($value)) { continue; }
            if (trim((string) $value) === '') { continue; }

            $obsId = 'wellness-obs-' . ($idx + 1);
            $observationRefs[] = ['reference' => 'Observation/' . $obsId];

            $code = ['text' => $display];
            if ($loincCode !== '') {
                $code['coding'] = [['system' => 'http://loinc.org', 'code' => $loincCode, 'display' => $display]];
            }

            $obsResource = [
                'resourceType'     => 'Observation',
                'id'               => $obsId,
                'status'           => 'final',
                'category'         => [[
                    'coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code'    => 'vital-signs',
                        'display' => 'Vital Signs',
                    ]],
                ]],
                'code'              => $code,
                'subject'           => ['reference' => $patientRef],
                'effectiveDateTime' => $issuedAt,
            ];

            if ($practitionerRef !== '') {
                $obsResource['performer'] = [['reference' => $practitionerRef]];
            }

            if ($valueType === 'quantity' && is_numeric($value)) {
                $obsResource['valueQuantity'] = [
                    'value'  => (float) $value,
                    'unit'   => $unit,
                    'system' => 'http://unitsofmeasure.org',
                    'code'   => $ucumCode,
                ];
            } else {
                $obsResource['valueString'] = (string) $value;
            }

            $entries[] = ['resource' => $obsResource];
        }

        // Lifestyle / social-history observations
        foreach ($lifestyle as $idx => $ls) {
            $code    = trim((string) ($ls['code'] ?? ''));
            $display = (string) ($ls['display'] ?? '');
            $value   = (string) ($ls['value'] ?? '');
            $system  = (string) ($ls['system'] ?? 'http://snomed.info/sct');

            if ($value === '' || $display === '') { continue; }

            $obsId = 'wellness-ls-' . ($idx + 1);
            $observationRefs[] = ['reference' => 'Observation/' . $obsId];

            $obsCode = ['text' => $display];
            if ($code !== '') {
                $obsCode['coding'] = [['system' => $system, 'code' => $code, 'display' => $display]];
            }

            $entries[] = ['resource' => [
                'resourceType'      => 'Observation',
                'id'                => $obsId,
                'status'            => 'final',
                'category'          => [[
                    'coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code'    => 'social-history',
                        'display' => 'Social History',
                    ]],
                ]],
                'code'              => $obsCode,
                'subject'           => ['reference' => $patientRef],
                'effectiveDateTime' => $issuedAt,
                'valueString'       => $value,
            ]];
        }

        $composition = [
            'resourceType' => 'Composition',
            'id'           => 'comp-wellness-' . date('YmdHis'),
            'status'       => 'final',
            'type'         => [
                'coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '371529009',
                    'display' => 'Health maintenance report (record artifact)',
                ]],
                'text' => 'Wellness Record',
            ],
            'subject' => ['reference' => $patientRef],
            'date'    => $issuedAt,
            'title'   => 'Wellness Record',
            'section' => [
                ['title' => 'Vital Signs and Body Measurements', 'entry' => $observationRefs],
            ],
        ];
        if ($practitionerRef !== '') {
            $composition['author'] = [['reference' => $practitionerRef]];
        }
        $entries[] = ['resource' => $composition];

        return [
            'resourceType' => 'Bundle',
            'identifier'   => [
                'system' => 'urn:ietf:rfc:3986',
                'value'  => 'urn:uuid:bundle-wellness-' . date('YmdHis'),
            ],
            'type'      => 'document',
            'timestamp' => $issuedAt,
            'entry'     => $entries,
        ];
    }

    // -------------------------------------------------------------------------

    /**
     * Build DocumentReference + Binary resource entries for a scanned attachment.
     *
     * Usage: append the returned entries directly to an existing Bundle's entry array.
     *
     * @param string $id          Resource ID stem (without "DocumentReference/")
     * @param string $title       Document title (human-readable)
     * @param string $contentType MIME type — application/pdf | image/jpeg | image/png
     * @param string $dataBase64  Base64-encoded raw file content
     * @param string $subjectRef  Patient reference string (e.g. "Patient/123")
     * @param string $date        ISO 8601 date/datetime string
     *
     * @return array<int, array<string, mixed>>  [ [resource => Binary], [resource => DocumentReference] ]
     */
    public function buildDocumentReferenceEntry(
        string $id,
        string $title,
        string $contentType,
        string $dataBase64,
        string $subjectRef,
        string $date
    ): array {
        $binaryId  = 'binary-' . $id;
        $binaryRef = 'Binary/' . $binaryId;

        $binaryResource = [
            'resourceType' => 'Binary',
            'id'           => $binaryId,
            'contentType'  => $contentType,
            'data'         => $dataBase64,
        ];

        $docRefResource = [
            'resourceType' => 'DocumentReference',
            'id'           => $id,
            'status'       => 'current',
            'type'         => ['text' => $title],
            'subject'      => ['reference' => $subjectRef],
            'date'         => $date,
            'content'      => [[
                'attachment' => [
                    'contentType' => $contentType,
                    'url'         => $binaryRef,
                    'title'       => $title,
                ],
            ]],
        ];

        return [
            ['resource' => $binaryResource],
            ['resource' => $docRefResource],
        ];
    }

    /**
     * Read a file from disk and return its base64-encoded content.
     * Returns an empty string when the file is absent or unreadable.
     */
    public function encodeFileAsBase64(string $filePath): string
    {
        if ($filePath === '' || ! is_file($filePath) || ! is_readable($filePath)) {
            return '';
        }

        $content = file_get_contents($filePath);

        return $content !== false ? base64_encode($content) : '';
    }
}
