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
        * @param array<string, mixed> $context
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
        $attachments     = is_array($context['attachments'] ?? null) ? (array) $context['attachments'] : [];

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
        $documentRefs           = [];

        // Resource entries collected here; Composition prepended at the end.
        $resourceEntries = [];

        // ── Practitioner ─────────────────────────────────────────────────────
        if ($hasPractitioner) {
            $practRawName = trim((string) ($practitioner['name'] ?? ''));
            $practNameEntry = ['text' => $practRawName];
            // Split "Dr. Ramesh Sharma" → prefix=["Dr."], given=["Ramesh"], family="Sharma"
            $practParts = preg_split('/\s+/', $practRawName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($practParts) >= 2) {
                if (preg_match('/^(Dr\.?|Prof\.?|Mr\.?|Mrs\.?|Ms\.?)$/i', $practParts[0])) {
                    $practNameEntry['prefix'] = [array_shift($practParts)];
                }
                if (count($practParts) >= 1) {
                    $practNameEntry['family'] = (string) array_pop($practParts);
                }
                if (! empty($practParts)) {
                    $practNameEntry['given'] = $practParts;
                }
            }
            $practResource = [
                'resourceType' => 'Practitioner',
                'id'           => $practitionerUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner']],
                'name'         => [$practNameEntry],
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
                'display' => 'Ambulatory',
            ],
            'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            'period'  => ['start' => $this->normalizeIsoDateTime((string) ($encounter['period_start'] ?? ''), $issuedAt)],
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
                $routeEntry = ['text' => $routeText];
                // Add SNOMED route code when we can resolve it from text or a pre-supplied code
                $routeCode = trim((string) ($medication['route_code'] ?? ''));
                if ($routeCode === '') {
                    $routeCode = $this->resolveRouteSnomedCode($routeText);
                }
                if ($routeCode !== '') {
                    $routeEntry['coding'] = [[
                        'system'  => 'http://snomed.info/sct',
                        'code'    => $routeCode,
                        'display' => $routeText,
                    ]];
                }
                $dosageInstruction['route'] = $routeEntry;
            }
            $methodText = trim((string) ($medication['method_text'] ?? $medType));
            $methodCode = trim((string) ($medication['method_code'] ?? ''));
            if ($methodText !== '') {
                $methodEntry = ['text' => $methodText];
                if ($methodCode !== '') {
                    $methodEntry['coding'] = [[
                        'system'  => 'http://snomed.info/sct',
                        'code'    => $methodCode,
                        'display' => $methodText,
                    ]];
                }
                $dosageInstruction['method'] = $methodEntry;
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

        // ── OPD scan/upload attachments (DocumentReference + Binary) ─────────
        foreach ($attachments as $index => $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $dataBase64 = trim((string) ($attachment['data_base64'] ?? ''));
            if ($dataBase64 === '') {
                continue;
            }

            $contentType = trim((string) ($attachment['content_type'] ?? 'application/octet-stream'));
            $title = trim((string) ($attachment['title'] ?? 'OPD scanned document'));
            $date = $this->normalizeIsoDateTime((string) ($attachment['date'] ?? ''), $issuedAt);
            $attachmentId = preg_replace('/[^A-Za-z0-9\-\.]/', '-', trim((string) ($attachment['id'] ?? '')));
            if ($attachmentId === '' || $attachmentId === null) {
                $attachmentId = (string) ($index + 1);
            }

            $docUuid = 'opd-scan-' . $attachmentId . '-' . $this->generateUuid();
            $binaryUuid = 'binary-' . $docUuid;
            $docRef = 'urn:uuid:' . $docUuid;
            $binaryRef = 'urn:uuid:' . $binaryUuid;

            $documentRefs[] = ['reference' => $docRef, 'display' => 'DocumentReference'];
            $resourceEntries[] = ['fullUrl' => $binaryRef, 'resource' => [
                'resourceType' => 'Binary',
                'id' => $binaryUuid,
                'contentType' => $contentType,
                'data' => $dataBase64,
            ]];
            $resourceEntries[] = ['fullUrl' => $docRef, 'resource' => [
                'resourceType' => 'DocumentReference',
                'id' => $docUuid,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
                'status' => 'current',
                'type' => ['text' => $title],
                'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
                'date' => $date,
                'content' => [[
                    'attachment' => [
                        'contentType' => $contentType,
                        'url' => $binaryRef,
                        'data' => $dataBase64,
                        'title' => $title,
                    ],
                ]],
            ]];
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
        if (! empty($documentRefs)) {
            $compositionSections[] = [
                'title' => 'Document Reference',
                'code'  => ['coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '371530004',
                    'display' => 'Clinical consultation report',
                ]]],
                'entry' => $documentRefs,
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
        $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
        $bundleIdSystem = $hfrId !== ''
            ? 'https://' . strtolower(preg_replace('/[^A-Za-z0-9]/', '', $hfrId)) . '.hfr.abdm.gov.in'
            : 'https://hfr.abdm.gov.in';
        $bundleIdValue  = $hfrId !== ''
            ? 'OPD-' . ($encounter['id'] ?? $bundleUuid) . '-' . date('Y-m-d')
            : $bundleUuid;

        return $this->sanitizeBundle([
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
            'identifier' => ['system' => $bundleIdSystem, 'value' => $bundleIdValue],
            'type'       => 'document',
            'timestamp'  => $issuedAt,
            'entry'      => $allEntries,
        ]);
    }

    /**
     * Build a ABDM-compliant DiagnosticReportRecord FHIR DocumentBundle.
     *
     * Supports:
     *  - LOINC-coded observations (valueQuantity) for structured lab results
     *  - HTML report as presentedForm via Binary + DocumentReference
     *  - Optional Practitioner, Organization, Encounter
     *
     * @param array<string, mixed>              $patient         {id, name, given_name, family_name, gender, birthDate, abhaAddress, phone}
     * @param array<string, mixed>              $diagnosticReport {id, title, category_snomed_code, category_snomed_display,
     *                                                             loinc_code, loinc_display, status, conclusion,
     *                                                             reported_at, report_html, report_content_type}
     * @param array<int, array<string, mixed>>  $observations    Each: {test_name, loinc_code, value, value_type (quantity|string),
     *                                                             unit, ucum_code, ref_low, ref_high, interpretation, status}
     * @param array<string, mixed>|null         $practitioner    {name, registration_number}
     * @param array<string, mixed>|null         $organization    {name, hfr_id}
     * @param array<string, mixed>|null         $encounter       {id, status, class_code (AMB|IMP|EMER), period_start, period_end}
     * @param array<string, mixed>|null         $attachment      Optional PDF attachment {content_type, data_base64, title}
     *
     * @return array<string, mixed>
     */
    public function buildLabReportBundle(
        array  $patient,
        array  $diagnosticReport,
        array  $observations  = [],
        ?array $practitioner  = null,
        ?array $organization  = null,
        ?array $encounter     = null,
        ?array $attachment    = null
    ): array {
        $issuedAt        = $this->isoTimestamp();
        $bundleUuid      = $this->generateUuid();
        $compositionUuid = $this->generateUuid();
        $patientUuid     = $this->generateUuid();
        $reportUuid      = $this->generateUuid();

        $patientRef  = 'urn:uuid:' . $patientUuid;
        $reportRef   = 'urn:uuid:' . $reportUuid;
        $reportedAt  = $this->normalizeIsoDateTime((string) ($diagnosticReport['reported_at'] ?? ''), $issuedAt);
        $reportTitle = trim((string) ($diagnosticReport['title'] ?? 'Diagnostic Report'));
        $reportStatus = trim((string) ($diagnosticReport['status'] ?? 'final'));
        $isImaging   = (bool) ($diagnosticReport['is_imaging'] ?? false)
            || strtolower(trim((string) ($diagnosticReport['report_domain'] ?? ''))) === 'imaging';

        $hasPractitioner  = $practitioner !== null && trim((string) ($practitioner['name'] ?? '')) !== '';
        $hasOrganization  = $organization !== null && trim((string) ($organization['name'] ?? '')) !== '';
        $hasEncounter     = $encounter !== null;
        $practitionerUuid = $hasPractitioner ? $this->generateUuid() : '';
        $organizationUuid = $hasOrganization ? $this->generateUuid() : '';
        $encounterUuid    = $hasEncounter   ? $this->generateUuid() : '';

        $practitionerRef = $hasPractitioner ? ('urn:uuid:' . $practitionerUuid) : '';
        $organizationRef = $hasOrganization ? ('urn:uuid:' . $organizationUuid) : '';
        $encounterRef    = $hasEncounter    ? ('urn:uuid:' . $encounterUuid)    : '';

        $resourceEntries = [];

        // ── Practitioner ──────────────────────────────────────────────────────
        if ($hasPractitioner) {
            $practRes = [
                'resourceType' => 'Practitioner',
                'id'           => $practitionerUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner']],
                'name'         => [['text' => trim((string) ($practitioner['name'] ?? ''))]],
            ];
            $regNo = trim((string) ($practitioner['registration_number'] ?? ''));
            if ($regNo !== '') {
                $practRes['identifier'] = [[
                    'type'   => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0203', 'code' => 'MD', 'display' => 'Medical License number']]],
                    'system' => 'https://doctor.ndhm.gov.in',
                    'value'  => $regNo,
                ]];
            }
            $resourceEntries[] = ['fullUrl' => $practitionerRef, 'resource' => $practRes];
        }

        // ── Organization ──────────────────────────────────────────────────────
        if ($hasOrganization) {
            $orgRes = [
                'resourceType' => 'Organization',
                'id'           => $organizationUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization']],
                'name'         => trim((string) ($organization['name'] ?? '')),
            ];
            $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
            if ($hfrId !== '') {
                $orgRes['identifier'] = [[
                    'type'   => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0203', 'code' => 'PRN', 'display' => 'Provider number']]],
                    'system' => 'https://facility.ndhm.gov.in',
                    'value'  => $hfrId,
                ]];
            }
            $resourceEntries[] = ['fullUrl' => $organizationRef, 'resource' => $orgRes];
        }

        // ── Patient ───────────────────────────────────────────────────────────
        $patientResource       = $this->buildPatientResource($patient);
        $patientResource['id'] = $patientUuid;
        $resourceEntries[]     = ['fullUrl' => $patientRef, 'resource' => $patientResource];

        // ── Encounter ─────────────────────────────────────────────────────────
        if ($hasEncounter) {
            $encounterRes = [
                'resourceType' => 'Encounter',
                'id'           => $encounterUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter']],
                'identifier'   => [['system' => 'https://ndhm.in', 'value' => (string) ($encounter['id'] ?? $encounterUuid)]],
                'status'       => (string) ($encounter['status'] ?? 'finished'),
                'class'        => [
                    'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                    'code'    => (string) ($encounter['class_code'] ?? 'AMB'),
                    'display' => (string) ($encounter['class_code'] ?? 'AMB') === 'IMP' ? 'inpatient encounter' : 'Ambulatory',
                ],
                'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            ];
            $pStartRaw = trim((string) ($encounter['period_start'] ?? ''));
            if ($pStartRaw !== '') {
                $encounterRes['period'] = ['start' => $this->normalizeIsoDateTime($pStartRaw, $issuedAt)];
                $pEndRaw = trim((string) ($encounter['period_end'] ?? ''));
                if ($pEndRaw !== '') {
                    $pEndIso = $this->normalizeIsoDateTime($pEndRaw);
                    if ($pEndIso !== '') {
                        $encounterRes['period']['end'] = $pEndIso;
                    }
                }
            }
            if ($practitionerRef !== '') {
                $encounterRes['participant'] = [['individual' => ['reference' => $practitionerRef]]];
            }
            if ($organizationRef !== '') {
                $encounterRes['serviceProvider'] = ['reference' => $organizationRef];
            }
            $resourceEntries[] = ['fullUrl' => $encounterRef, 'resource' => $encounterRes];
        }

        // ── Observations (structured lab results) ─────────────────────────────
        $observationEntries = [];
        $observationRefs    = [];
        foreach ($observations as $obs) {
            $obsUuid           = $this->generateUuid();
            $obsRef            = 'urn:uuid:' . $obsUuid;
            $observationRefs[] = ['reference' => $obsRef];
            $testName = trim((string) ($obs['test_name'] ?? ''));
            $loincCode = trim((string) ($obs['loinc_code'] ?? ''));
            $codeEntry = ['text' => $testName];
            if ($loincCode !== '') {
                $codeEntry['coding'] = [['system' => 'http://loinc.org', 'code' => $loincCode, 'display' => $testName]];
            }
            $obsResource = [
                'resourceType'      => 'Observation',
                'id'                => $obsUuid,
                'meta'              => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation']],
                'status'            => (string) ($obs['status'] ?? 'final'),
                'code'              => $codeEntry,
                'subject'           => ['reference' => $patientRef],
                'effectiveDateTime' => $reportedAt,
            ];
            if ($encounterRef !== '') {
                $obsResource['encounter'] = ['reference' => $encounterRef];
            }
            if ($practitionerRef !== '') {
                $obsResource['performer'] = [['reference' => $practitionerRef]];
            } elseif ($organizationRef !== '') {
                $obsResource['performer'] = [['reference' => $organizationRef]];
            }
            // Value: quantity or string
            $valueType = trim((string) ($obs['value_type'] ?? 'string'));
            $rawValue  = $obs['value'] ?? '';
            if ($valueType === 'quantity' && is_numeric($rawValue)) {
                $ucum = trim((string) ($obs['ucum_code'] ?? ''));
                $unit = trim((string) ($obs['unit'] ?? ''));
                $obsResource['valueQuantity'] = [
                    'value'  => (float) $rawValue,
                    'unit'   => $unit !== '' ? $unit : $ucum,
                    'system' => 'http://unitsofmeasure.org',
                    'code'   => $ucum,
                ];
            } else {
                $obsResource['valueString'] = (string) $rawValue;
            }
            // Reference range
            $refLow  = $obs['ref_low'] ?? null;
            $refHigh = $obs['ref_high'] ?? null;
            if ($refLow !== null || $refHigh !== null) {
                $rr = [];
                $ucum = trim((string) ($obs['ucum_code'] ?? ''));
                $unit = trim((string) ($obs['unit'] ?? ''));
                if ($refLow !== null && is_numeric($refLow)) {
                    $rr['low'] = ['value' => (float) $refLow, 'unit' => $unit ?: $ucum, 'system' => 'http://unitsofmeasure.org', 'code' => $ucum];
                }
                if ($refHigh !== null && is_numeric($refHigh)) {
                    $rr['high'] = ['value' => (float) $refHigh, 'unit' => $unit ?: $ucum, 'system' => 'http://unitsofmeasure.org', 'code' => $ucum];
                }
                if (! empty($rr)) {
                    $obsResource['referenceRange'] = [$rr];
                }
            }
            // Interpretation (H/L/N)
            $interp = trim((string) ($obs['interpretation'] ?? ''));
            if ($interp !== '') {
                $interpCode = strtoupper($interp);
                $interpCodeMap = ['H' => 'H', 'HIGH' => 'H', 'L' => 'L', 'LOW' => 'L', 'N' => 'N', 'NORMAL' => 'N'];
                $obsResource['interpretation'] = [[
                    'coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation',
                        'code'    => $interpCodeMap[$interpCode] ?? $interpCode,
                        'display' => $interp,
                    ]],
                    'text' => $interp,
                ]];
            }
            $observationEntries[] = ['fullUrl' => $obsRef, 'resource' => $obsResource];
        }

        // ── Binary + DocumentReference for HTML report ─────────────────────────
        $docRefEntry = null;
        $docRefRef   = null;
        $pdfDocRefRef = null;
        $mediaRef = null;
        $reportHtml  = trim((string) ($diagnosticReport['report_html'] ?? ''));
        if ($reportHtml !== '') {
            $binaryUuid  = $this->generateUuid();
            $docRefUuid  = $this->generateUuid();
            $docRefRef   = 'urn:uuid:' . $docRefUuid;
            $contentType = trim((string) ($diagnosticReport['report_content_type'] ?? 'text/html; charset=utf-8'));

            $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $binaryUuid, 'resource' => [
                'resourceType' => 'Binary',
                'id'           => $binaryUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Binary']],
                'contentType'  => $contentType,
                'data'         => base64_encode($reportHtml),
            ]];

            $docRefEntry = [
                'resourceType'  => 'DocumentReference',
                'id'            => $docRefUuid,
                'meta'          => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
                'status'        => 'current',
                'type'          => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '721981007', 'display' => 'Diagnostic studies report']]],
                'subject'       => ['reference' => $patientRef],
                'content'       => [['attachment' => ['contentType' => $contentType, 'url' => 'urn:uuid:' . $binaryUuid, 'data' => base64_encode($reportHtml), 'title' => $reportTitle]]],
            ];
            $resourceEntries[] = ['fullUrl' => $docRefRef, 'resource' => $docRefEntry];
        }

        // ── Optional PDF attachment as Binary + DocumentReference ───────────
        if ($attachment !== null && ! empty($attachment['data_base64'])) {
            $pdfData = trim((string) ($attachment['data_base64'] ?? ''));
            if ($pdfData !== '') {
                $pdfBinaryUuid = $this->generateUuid();
                $pdfDocRefUuid = $this->generateUuid();
                $pdfDocRefRef = 'urn:uuid:' . $pdfDocRefUuid;
                $pdfContentType = trim((string) ($attachment['content_type'] ?? 'application/pdf')) ?: 'application/pdf';
                $pdfTitle = trim((string) ($attachment['title'] ?? 'Lab Report PDF')) ?: 'Lab Report PDF';
                $pdfBytes = base64_decode($pdfData, true);
                $pdfSize = (int) ($attachment['size'] ?? (is_string($pdfBytes) ? strlen($pdfBytes) : 0));
                $pdfHash = trim((string) ($attachment['hash'] ?? (is_string($pdfBytes) ? base64_encode(sha1($pdfBytes, true)) : '')));

                $resourceEntries[] = ['fullUrl' => 'urn:uuid:' . $pdfBinaryUuid, 'resource' => [
                    'resourceType' => 'Binary',
                    'id'           => $pdfBinaryUuid,
                    'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Binary']],
                    'contentType'  => $pdfContentType,
                    'data'         => $pdfData,
                ]];

                $resourceEntries[] = ['fullUrl' => $pdfDocRefRef, 'resource' => [
                    'resourceType' => 'DocumentReference',
                    'id'           => $pdfDocRefUuid,
                    'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
                    'status'       => 'current',
                    'type'         => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '721981007', 'display' => 'Diagnostic studies report']]],
                    'subject'      => ['reference' => $patientRef],
                    'content'      => [[
                        'attachment' => array_filter([
                            'contentType' => $pdfContentType,
                            'url' => 'urn:uuid:' . $pdfBinaryUuid,
                            'data' => $pdfData,
                            'title' => $pdfTitle,
                            'size' => $pdfSize > 0 ? $pdfSize : null,
                            'hash' => $pdfHash !== '' ? $pdfHash : null,
                        ], static fn ($value): bool => $value !== null),
                    ]],
                ]];

                if ($isImaging) {
                    $mediaUuid = $this->generateUuid();
                    $mediaRef = 'urn:uuid:' . $mediaUuid;
                    $resourceEntries[] = ['fullUrl' => $mediaRef, 'resource' => [
                        'resourceType' => 'Media',
                        'id'           => $mediaUuid,
                        'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Media']],
                        'status'       => 'completed',
                        'type'         => 'photo',
                        'subject'      => ['reference' => $patientRef],
                        'createdDateTime' => $reportedAt,
                        'content'      => [
                            'contentType' => $pdfContentType,
                            'url' => 'urn:uuid:' . $pdfBinaryUuid,
                            'data' => $pdfData,
                            'title' => $pdfTitle,
                        ],
                    ]];
                }
            }
        }

        // Pick a single DocumentReference for Composition.section slicing (0..1).
        $effectiveDocRefRef = $pdfDocRefRef !== null ? $pdfDocRefRef : $docRefRef;

        // ── DiagnosticReport ──────────────────────────────────────────────────
        $reportRes = [
            'resourceType' => 'DiagnosticReport',
            'id'           => $reportUuid,
            'meta'         => ['profile' => [$isImaging
                ? 'https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportImaging'
                : 'https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportLab'
            ]],
            'status'       => $reportStatus,
            'code'         => array_filter([
                'coding' => trim((string) ($diagnosticReport['loinc_code'] ?? '')) !== ''
                    ? [['system' => 'http://loinc.org', 'code' => (string) $diagnosticReport['loinc_code'], 'display' => $reportTitle]]
                    : null,
                'text' => $reportTitle,
            ]),
            'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            'issued'  => $reportedAt,
        ];
        if (!empty($observationRefs)) {
            $reportRes['result'] = $observationRefs;
        }
        if ($organizationRef !== '') {
            $reportRes['performer'] = [['reference' => $organizationRef]];
        } elseif ($practitionerRef !== '') {
            $reportRes['performer'] = [['reference' => $practitionerRef]];
        }
        if ($practitionerRef !== '') {
            $reportRes['resultsInterpreter'] = [['reference' => $practitionerRef]];
        } elseif ($organizationRef !== '') {
            $reportRes['resultsInterpreter'] = [['reference' => $organizationRef]];
        }
        if ($encounterRef !== '') {
            $reportRes['encounter'] = ['reference' => $encounterRef];
        }
        $catSnomedCode = trim((string) ($diagnosticReport['category_snomed_code'] ?? ''));
        if ($catSnomedCode !== '') {
            $reportRes['category'] = [['coding' => [['system' => 'http://snomed.info/sct', 'code' => $catSnomedCode, 'display' => trim((string) ($diagnosticReport['category_snomed_display'] ?? $catSnomedCode))]]]];
        }
        $conclusion = trim((string) ($diagnosticReport['conclusion'] ?? ''));
        $reportRes['conclusion'] = $conclusion !== '' ? $conclusion : 'Laboratory report generated';
        if ($isImaging && $mediaRef !== null) {
            $reportRes['media'] = [[
                'link' => ['reference' => $mediaRef],
            ]];
        }
        if ($attachment !== null && ! empty($attachment['data_base64'])) {
            $pdfPresentedFormUrl = $pdfDocRefRef !== null ? $pdfDocRefRef : null;
            $reportRes['presentedForm'] = [[
                'contentType' => trim((string) ($attachment['content_type'] ?? 'application/pdf')) ?: 'application/pdf',
                'title' => trim((string) ($attachment['title'] ?? 'Lab Report PDF')) ?: 'Lab Report PDF',
                'url' => $pdfPresentedFormUrl,
            ]];
        }
        $resourceEntries[] = ['fullUrl' => $reportRef, 'resource' => $reportRes];

        // ── Composition section entries ────────────────────────────────────────
        $sectionEntries = [[
            'reference' => $reportRef,
            'type'      => 'DiagnosticReport',
        ]];
        if ($effectiveDocRefRef !== null) {
            $sectionEntries[] = [
                'reference' => $effectiveDocRefRef,
                'type'      => 'DocumentReference',
            ];
        }

        $sectionTitle = trim((string) ($diagnosticReport['section_title'] ?? ($isImaging ? 'Computed tomography imaging report' : 'Hematology report')));
        $sectionCode = trim((string) ($diagnosticReport['section_snomed_code'] ?? ($isImaging ? '371531008' : '4321000179101')));
        $sectionDisplay = trim((string) ($diagnosticReport['section_snomed_display'] ?? $sectionTitle));

        // ── Composition ───────────────────────────────────────────────────────
        $authorEntry = $practitionerRef !== ''
            ? ['reference' => $practitionerRef]
            : ($organizationRef !== '' ? ['reference' => $organizationRef] : ['reference' => $patientRef]);
        $composition = [
            'resourceType' => 'Composition',
            'id'           => $compositionUuid,
            'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportRecord']],
            'language'     => 'en-IN',
            'identifier'   => ['system' => 'https://ndhm.in/phr', 'value' => $compositionUuid],
            'status'       => 'final',
            'type'         => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => $isImaging ? '721979004' : '721981007',
                    'display' => $isImaging ? 'Diagnostic Report- Imaging' : 'Diagnostic Report- Lab',
                ]],
                'text'   => $isImaging ? 'Diagnostic Report- Imaging' : 'Diagnostic Report- Lab',
            ],
            'subject'   => ['reference' => $patientRef, 'display' => 'Patient'],
            'date'      => $issuedAt,
            'author'    => [$authorEntry],
            'title'     => $reportTitle,
            'section'   => [[
                'title' => $sectionTitle,
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => $sectionCode, 'display' => $sectionDisplay]]],
                'entry' => $sectionEntries,
            ]],
        ];
        if ($encounterRef !== '') {
            $composition['encounter'] = ['reference' => $encounterRef];
        }
        if ($organizationRef !== '') {
            $composition['custodian'] = ['reference' => $organizationRef];
        }

        // ── Bundle identifier system: HFR ID based or fallback ────────────────
        $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
        $bundleIdSystem = $hfrId !== ''
            ? 'https://' . strtolower(preg_replace('/[^A-Za-z0-9]/', '', $hfrId)) . '.hfr.abdm.gov.in'
            : 'https://hfr.abdm.gov.in';
        $bundleIdValue = $hfrId !== ''
            ? 'LAB-' . (string) ($diagnosticReport['id'] ?? $bundleUuid)
            : $bundleUuid;

        return $this->sanitizeBundle([
            'resourceType' => 'Bundle',
            'id'           => $bundleUuid,
            'meta'         => [
                'profile'  => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle'],
                'security' => [['system' => 'http://terminology.hl7.org/CodeSystem/v3-Confidentiality', 'code' => 'V', 'display' => 'very restricted']],
            ],
            'identifier' => ['system' => $bundleIdSystem, 'value' => $bundleIdValue],
            'type'       => 'document',
            'timestamp'  => $issuedAt,
            'entry'      => array_merge(
                [['fullUrl' => 'urn:uuid:' . $compositionUuid, 'resource' => $composition]],
                $resourceEntries,
                $observationEntries
            ),
        ]);
    }

    /**
     * Build an ABDM-compliant DischargeSummaryRecord FHIR DocumentBundle.
     *
     * @param array<string, mixed>              $patient       {id, name, given_name, family_name, gender, birthDate, abhaAddress, phone}
     * @param array<string, mixed>              $encounter     {id, admission_date, discharge_date, ipd_code, discharge_disposition}
     * @param array<string, mixed>              $summary       {title, clinical_summary_html, chief_complaints, diagnosis_text,
     *                                                          investigations_text, procedures_text, medications_text, follow_up}
     * @param array<string, mixed>|null         $practitioner  {name, registration_number}
     * @param array<string, mixed>|null         $organization  {name, hfr_id}
     *
     * @return array<string, mixed>
     */
    public function buildDischargeSummaryBundle(
        array  $patient,
        array  $encounter,
        array  $summary,
        ?array $practitioner = null,
        ?array $organization = null
    ): array {
        $issuedAt        = $this->isoTimestamp();
        $bundleUuid      = $this->generateUuid();
        $compositionUuid = $this->generateUuid();
        $patientUuid     = $this->generateUuid();
        $encounterUuid   = $this->generateUuid();

        $patientRef   = 'urn:uuid:' . $patientUuid;
        $encounterRef = 'urn:uuid:' . $encounterUuid;

        $hasPractitioner  = $practitioner !== null && trim((string) ($practitioner['name'] ?? '')) !== '';
        $hasOrganization  = $organization !== null && trim((string) ($organization['name'] ?? '')) !== '';
        $practitionerUuid = $hasPractitioner ? $this->generateUuid() : '';
        $organizationUuid = $hasOrganization ? $this->generateUuid() : '';
        $practitionerRef  = $hasPractitioner ? ('urn:uuid:' . $practitionerUuid) : '';
        $organizationRef  = $hasOrganization ? ('urn:uuid:' . $organizationUuid) : '';

        $resourceEntries = [];

        // ── Practitioner ──────────────────────────────────────────────────────
        if ($hasPractitioner) {
            $practRes = [
                'resourceType' => 'Practitioner',
                'id'           => $practitionerUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner']],
                'name'         => [['text' => trim((string) ($practitioner['name'] ?? ''))]],
            ];
            $regNo = trim((string) ($practitioner['registration_number'] ?? ''));
            if ($regNo !== '') {
                $practRes['identifier'] = [[
                    'type'   => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0203', 'code' => 'MD', 'display' => 'Medical License number']]],
                    'system' => 'https://doctor.ndhm.gov.in',
                    'value'  => $regNo,
                ]];
            }
            $resourceEntries[] = ['fullUrl' => $practitionerRef, 'resource' => $practRes];
        }

        // ── Organization ──────────────────────────────────────────────────────
        if ($hasOrganization) {
            $orgRes = [
                'resourceType' => 'Organization',
                'id'           => $organizationUuid,
                'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization']],
                'name'         => trim((string) ($organization['name'] ?? '')),
            ];
            $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
            if ($hfrId !== '') {
                $orgRes['identifier'] = [[
                    'type'   => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0203', 'code' => 'PRN', 'display' => 'Provider number']]],
                    'system' => 'https://facility.ndhm.gov.in',
                    'value'  => $hfrId,
                ]];
            }
            $resourceEntries[] = ['fullUrl' => $organizationRef, 'resource' => $orgRes];
        }

        // ── Patient ───────────────────────────────────────────────────────────
        $patientResource       = $this->buildPatientResource($patient);
        $patientResource['id'] = $patientUuid;
        $resourceEntries[]     = ['fullUrl' => $patientRef, 'resource' => $patientResource];

        // ── Encounter (inpatient) ─────────────────────────────────────────────
        $admissionDate  = trim((string) ($encounter['admission_date'] ?? '')) ?: $issuedAt;
        $dischargeDate  = trim((string) ($encounter['discharge_date'] ?? '')) ?: $issuedAt;
        $encounterResource = [
            'resourceType' => 'Encounter',
            'id'           => $encounterUuid,
            'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter']],
            'identifier'   => [['system' => 'https://ndhm.in', 'value' => trim((string) ($encounter['ipd_code'] ?? $encounterUuid))]],
            'status'       => 'finished',
            'class'        => [
                'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code'    => 'IMP',
                'display' => 'inpatient encounter',
            ],
            'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            'period'  => ['start' => $admissionDate, 'end' => $dischargeDate],
        ];
        $dispText = trim((string) ($encounter['discharge_disposition'] ?? ''));
        if ($dispText !== '') {
            $encounterResource['hospitalization'] = [
                'dischargeDisposition' => [
                    'coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/discharge-disposition', 'code' => 'home', 'display' => 'Home']],
                    'text'   => $dispText,
                ],
            ];
        }
        if ($practitionerRef !== '') {
            $encounterResource['participant'] = [['individual' => ['reference' => $practitionerRef]]];
        }
        if ($organizationRef !== '') {
            $encounterResource['serviceProvider'] = ['reference' => $organizationRef];
        }

        // ABDM rejects a DischargeSummaryRecord without at least one Condition or
        // Procedure resource, so the diagnosis must be a resource and not just narrative.
        $diagnosisResourceText = trim((string) (
            $summary['diagnosis_text']
            ?? $summary['final_diagnosis']
            ?? $summary['chief_complaints']
            ?? ''
        ));
        $conditionRef = '';
        if ($diagnosisResourceText !== '') {
            $conditionUuid = $this->generateUuid();
            $conditionRef  = 'urn:uuid:' . $conditionUuid;

            $encounterResource['diagnosis'] = [[
                'condition' => ['reference' => $conditionRef, 'display' => 'Condition'],
            ]];

            $resourceEntries[] = ['fullUrl' => $conditionRef, 'resource' => [
                'resourceType'       => 'Condition',
                'id'                 => $conditionUuid,
                'meta'               => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition']],
                'clinicalStatus'     => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical', 'code' => 'active', 'display' => 'Active']]],
                'verificationStatus' => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status', 'code' => 'confirmed', 'display' => 'Confirmed']]],
                'category'           => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-category', 'code' => 'encounter-diagnosis', 'display' => 'Encounter Diagnosis']]]],
                'code'               => ['text' => $diagnosisResourceText],
                'subject'            => ['reference' => $patientRef, 'display' => 'Patient'],
                'encounter'          => ['reference' => $encounterRef],
            ]];
        }

        $resourceEntries[] = ['fullUrl' => $encounterRef, 'resource' => $encounterResource];

        // ── Composition sections per DischargeSummaryRecord spec ──────────────
        $sections = [];
        $summaryHtml = trim((string) ($summary['clinical_summary_html'] ?? ''));

        // Chief complaints section (SNOMED 422843007)
        $chiefComplaints = trim((string) ($summary['chief_complaints'] ?? ''));
        if ($chiefComplaints !== '' || $summaryHtml !== '') {
            $ccSection = [
                'title' => 'Chief complaints',
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '422843007', 'display' => 'Chief complaint section']]],
            ];
            if ($chiefComplaints !== '') {
                $ccSection['text'] = ['status' => 'generated', 'div' => '<div xmlns="http://www.w3.org/1999/xhtml">' . htmlspecialchars($chiefComplaints) . '</div>'];
            }
            $sections[] = $ccSection;
        }

        // Medical History section (SNOMED 1003642006)
        $diagnosisText = trim((string) ($summary['diagnosis_text'] ?? ''));
        if ($diagnosisText !== '') {
            $sections[] = [
                'title' => 'Medical History',
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '1003642006', 'display' => 'Past medical history section']]],
                'text'  => ['status' => 'generated', 'div' => '<div xmlns="http://www.w3.org/1999/xhtml">' . htmlspecialchars($diagnosisText) . '</div>'],
            ];
        }

        if ($conditionRef !== '') {
            $sections[] = [
                'title' => 'Diagnosis',
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '721981007', 'display' => 'Diagnosis']]],
                'entry' => [['reference' => $conditionRef, 'display' => 'Condition']],
                'text'  => ['status' => 'generated', 'div' => '<div xmlns="http://www.w3.org/1999/xhtml">' . htmlspecialchars($diagnosisResourceText) . '</div>'],
            ];
        }

        // Investigations section (SNOMED 721981007)
        $investigationsText = trim((string) ($summary['investigations_text'] ?? ''));
        if ($investigationsText !== '') {
            $sections[] = [
                'title' => 'Investigations',
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '721981007', 'display' => 'Diagnostic studies report']]],
                'text'  => ['status' => 'generated', 'div' => '<div xmlns="http://www.w3.org/1999/xhtml">' . htmlspecialchars($investigationsText) . '</div>'],
            ];
        }

        // Procedures section (SNOMED 1003640003)
        $proceduresText = trim((string) ($summary['procedures_text'] ?? ''));
        if ($proceduresText !== '') {
            $sections[] = [
                'title' => 'Procedures',
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '1003640003', 'display' => 'Procedure report']]],
                'text'  => ['status' => 'generated', 'div' => '<div xmlns="http://www.w3.org/1999/xhtml">' . htmlspecialchars($proceduresText) . '</div>'],
            ];
        }

        // Medications section (SNOMED 721912009)
        $medicationsText = trim((string) ($summary['medications_text'] ?? ''));
        if ($medicationsText !== '') {
            $sections[] = [
                'title' => 'Medications',
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '721912009', 'display' => 'Medication summary document']]],
                'text'  => ['status' => 'generated', 'div' => '<div xmlns="http://www.w3.org/1999/xhtml">' . htmlspecialchars($medicationsText) . '</div>'],
            ];
        }

        // Follow-up section (SNOMED 736271009)
        $followUp = trim((string) ($summary['follow_up'] ?? ''));
        if ($followUp !== '') {
            $sections[] = [
                'title' => 'Follow Up',
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '736271009', 'display' => 'Outpatient care plan']]],
                'text'  => ['status' => 'generated', 'div' => '<div xmlns="http://www.w3.org/1999/xhtml">' . htmlspecialchars($followUp) . '</div>'],
            ];
        }

        // Fallback: one summary section if nothing else provided
        if (empty($sections) && $summaryHtml !== '') {
            $sections[] = [
                'title' => 'Discharge Summary',
                'code'  => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '373942005', 'display' => 'Discharge summary']]],
                'text'  => ['status' => 'generated', 'div' => $summaryHtml],
            ];
        }

        // ── Composition ───────────────────────────────────────────────────────
        $authorEntry = $practitionerRef !== '' ? ['reference' => $practitionerRef] : ['display' => 'Unknown'];
        $composition = [
            'resourceType' => 'Composition',
            'id'           => $compositionUuid,
            'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DischargeSummaryRecord']],
            'language'     => 'en-IN',
            'identifier'   => ['system' => 'https://ndhm.in/phr', 'value' => $compositionUuid],
            'status'       => 'final',
            'type'         => [
                'coding' => [['system' => 'http://snomed.info/sct', 'code' => '373942005', 'display' => 'Discharge summary']],
                'text'   => 'Discharge Summary',
            ],
            'subject'   => ['reference' => $patientRef, 'display' => 'Patient'],
            'encounter' => ['reference' => $encounterRef, 'display' => 'Encounter'],
            'date'      => $issuedAt,
            'author'    => [[$authorEntry]],
            'title'     => (string) ($summary['title'] ?? 'Discharge Summary'),
            'section'   => $sections,
        ];
        if ($organizationRef !== '') {
            $composition['custodian'] = ['reference' => $organizationRef];
        }

        // ── Bundle identifier system: HFR ID based or fallback ────────────────
        $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
        $bundleIdSystem = $hfrId !== ''
            ? 'https://' . strtolower(preg_replace('/[^A-Za-z0-9]/', '', $hfrId)) . '.hfr.abdm.gov.in'
            : 'https://hfr.abdm.gov.in';
        $ipdCode = trim((string) ($encounter['ipd_code'] ?? ''));
        $bundleIdValue = $hfrId !== ''
            ? 'IPD-' . ($ipdCode !== '' ? $ipdCode : $bundleUuid)
            : $bundleUuid;

        return $this->sanitizeBundle([
            'resourceType' => 'Bundle',
            'id'           => $bundleUuid,
            'meta'         => [
                'profile'  => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle'],
                'security' => [['system' => 'http://terminology.hl7.org/CodeSystem/v3-Confidentiality', 'code' => 'V', 'display' => 'very restricted']],
            ],
            'identifier' => ['system' => $bundleIdSystem, 'value' => $bundleIdValue],
            'type'       => 'document',
            'timestamp'  => $issuedAt,
            'entry'      => array_merge(
                [['fullUrl' => 'urn:uuid:' . $compositionUuid, 'resource' => $composition]],
                $resourceEntries
            ),
        ]);
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

        // ABDM validates InvoiceRecord on a FHIR Invoice resource; Claim alone is rejected.
        $invoiceLineItems = [];
        foreach ($claimItems as $index => $claimItem) {
            $invoiceLineItems[] = [
                'sequence' => $index + 1,
                'chargeItemCodeableConcept' => $claimItem['productOrService'],
                'priceComponent' => [[
                    'type' => 'base',
                    'amount' => $claimItem['net'],
                ]],
            ];
        }

        $invoiceResource = [
            'resourceType' => 'Invoice',
            'id' => 'invoice-' . $claimId,
            'status' => (string) ($claim['invoice_status'] ?? 'issued'),
            'subject' => ['reference' => $patientRef],
            'date' => $issuedAt,
            'totalGross' => [
                'value' => (float) ($claim['total'] ?? 0),
                'currency' => (string) ($claim['currency'] ?? 'INR'),
            ],
            'totalNet' => [
                'value' => (float) ($claim['total'] ?? 0),
                'currency' => (string) ($claim['currency'] ?? 'INR'),
            ],
        ];

        $invoiceNo = trim((string) ($claim['invoice_no'] ?? ''));
        if ($invoiceNo !== '') {
            $invoiceResource['identifier'] = [['system' => 'https://ndhm.in/invoice', 'value' => $invoiceNo]];
        }
        if ($invoiceLineItems !== []) {
            $invoiceResource['lineItem'] = $invoiceLineItems;
        }

        // ABDM InvoiceRecord must be a document bundle led by a Composition.
        $composition = [
            'resourceType' => 'Composition',
            'id'           => 'comp-invoice-' . $claimId,
            'status'       => 'final',
            'type'         => [
                'coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '736762002',
                    'display' => 'Invoice record',
                ]],
                'text' => 'Invoice',
            ],
            'subject'  => ['reference' => $patientRef],
            'date'     => $issuedAt,
            'title'    => 'Invoice',
            'encounter' => ['reference' => $encounterRef],
            'section'  => [[
                'title' => 'Invoice',
                'entry' => [
                    ['reference' => 'Invoice/invoice-' . $claimId],
                    ['reference' => 'Claim/' . $claimId],
                ],
            ]],
        ];

        $provider = trim((string) ($claim['provider'] ?? ''));
        if ($provider !== '') {
            $composition['author'] = [['display' => $provider]];
        }

        return $this->sanitizeBundle([
            'resourceType' => 'Bundle',
            'type' => 'document',
            'timestamp' => $issuedAt,
            'entry' => [[
                'resource' => $composition,
            ], [
                'resource' => $this->buildPatientResource($patient),
            ], [
                'resource' => [
                    'resourceType' => 'Encounter',
                    'id' => (string) ($encounter['id'] ?? 'unknown'),
                    'status' => (string) ($encounter['status'] ?? 'finished'),
                    'subject' => ['reference' => $patientRef],
                ],
            ], [
                'resource' => $invoiceResource,
            ], [
                'resource' => $claimResource,
            ]],
        ]);
    }

    /**
     * Build an ABDM ImmunizationRecord FHIR DocumentBundle.
     *
     * @param array<string,mixed> $patient
     * @param array<int,array<string,mixed>> $immunizations
     * @param array<string,mixed> $context practitioner, organization, encounter, care_context_reference
     *
     * @return array<string,mixed>
     */
    public function buildImmunizationRecordBundle(array $patient, array $immunizations, array $context = []): array
    {
        $issuedAt = $this->isoTimestamp();
        $bundleUuid = $this->generateUuid();
        $compositionUuid = $this->generateUuid();
        $patientUuid = $this->generateUuid();
        $patientRef = 'urn:uuid:' . $patientUuid;

        $practitioner = is_array($context['practitioner'] ?? null) ? (array) $context['practitioner'] : [];
        $organization = is_array($context['organization'] ?? null) ? (array) $context['organization'] : [];
        $encounter = is_array($context['encounter'] ?? null) ? (array) $context['encounter'] : [];

        $hasPractitioner = trim((string) ($practitioner['id'] ?? '')) !== '' || trim((string) ($practitioner['name'] ?? '')) !== '';
        $hasOrganization = trim((string) ($organization['id'] ?? $organization['hfr_id'] ?? '')) !== '' || trim((string) ($organization['name'] ?? '')) !== '';
        $hasEncounter = trim((string) ($encounter['id'] ?? '')) !== '';

        $practitionerUuid = $hasPractitioner ? $this->generateUuid() : '';
        $organizationUuid = $hasOrganization ? $this->generateUuid() : '';
        $encounterUuid = $hasEncounter ? $this->generateUuid() : '';
        $practitionerRef = $hasPractitioner ? ('urn:uuid:' . $practitionerUuid) : '';
        $organizationRef = $hasOrganization ? ('urn:uuid:' . $organizationUuid) : '';
        $encounterRef = $hasEncounter ? ('urn:uuid:' . $encounterUuid) : '';

        $resourceEntries = [];

        $patientResource = $this->buildPatientResource($patient);
        $patientResource['id'] = $patientUuid;
        $patientResource['meta'] = ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient']];
        $resourceEntries[] = ['fullUrl' => $patientRef, 'resource' => $patientResource];

        if ($hasPractitioner) {
            $practitionerResource = [
                'resourceType' => 'Practitioner',
                'id' => $practitionerUuid,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner']],
                'name' => [['text' => trim((string) ($practitioner['name'] ?? ''))]],
            ];
            $registrationNumber = trim((string) ($practitioner['registration_number'] ?? ''));
            if ($registrationNumber !== '') {
                $practitionerResource['identifier'] = [[
                    'type' => ['coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                        'code' => 'MD',
                        'display' => 'Medical License number',
                    ]]],
                    'system' => 'https://doctor.ndhm.gov.in',
                    'value' => $registrationNumber,
                ]];
            }
            $resourceEntries[] = ['fullUrl' => $practitionerRef, 'resource' => $practitionerResource];
        }

        if ($hasOrganization) {
            $hfrId = trim((string) ($organization['hfr_id'] ?? $organization['id'] ?? ''));
            $organizationResource = [
                'resourceType' => 'Organization',
                'id' => $organizationUuid,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization']],
                'name' => trim((string) ($organization['name'] ?? '')),
            ];
            if ($hfrId !== '') {
                $organizationResource['identifier'] = [[
                    'type' => ['coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                        'code' => 'PRN',
                        'display' => 'Provider number',
                    ]]],
                    'system' => 'https://facility.ndhm.gov.in',
                    'value' => $hfrId,
                ]];
            }
            $resourceEntries[] = ['fullUrl' => $organizationRef, 'resource' => $organizationResource];
        }

        if ($hasEncounter) {
            $encounterResource = [
                'resourceType' => 'Encounter',
                'id' => $encounterUuid,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter']],
                'identifier' => [['system' => 'https://ndhm.in', 'value' => (string) ($encounter['id'] ?? $encounterUuid)]],
                'status' => (string) ($encounter['status'] ?? 'finished'),
                'class' => [
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                    'code' => (string) ($encounter['class_code'] ?? 'AMB'),
                    'display' => (string) ($encounter['class_display'] ?? 'Ambulatory'),
                ],
                'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            ];
            $periodStart = $this->normalizeIsoDateTime((string) ($encounter['period_start'] ?? ''), '');
            if ($periodStart !== '') {
                $encounterResource['period'] = ['start' => $periodStart];
            }
            if ($practitionerRef !== '') {
                $encounterResource['participant'] = [['individual' => ['reference' => $practitionerRef]]];
            }
            if ($organizationRef !== '') {
                $encounterResource['serviceProvider'] = ['reference' => $organizationRef];
            }
            $resourceEntries[] = ['fullUrl' => $encounterRef, 'resource' => $encounterResource];
        }

        $immunizationRefs = [];
        foreach ($immunizations as $immunization) {
            $vaccineName = trim((string) ($immunization['vaccine_name'] ?? $immunization['vaccine_display'] ?? ''));
            if ($vaccineName === '') {
                continue;
            }

            $immunizationUuid = $this->generateUuid();
            $immunizationRef = 'urn:uuid:' . $immunizationUuid;
            $status = $this->normalizeImmunizationStatus((string) ($immunization['status'] ?? 'completed'));
            $occurrence = $this->normalizeIsoDateTime(
                (string) ($immunization['given_date'] ?? $immunization['occurrenceDateTime'] ?? $immunization['due_date'] ?? ''),
                $issuedAt
            );
            [$vaccineCodeSystem, $vaccineCode] = $this->normalizeVaccineCoding(
                (string) ($immunization['vaccine_code_system'] ?? ''),
                (string) ($immunization['vaccine_code'] ?? '')
            );

            $resource = [
                'resourceType' => 'Immunization',
                'id' => $immunizationUuid,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Immunization']],
                'identifier' => [[
                    'system' => 'https://ndhm.in/immunization',
                    'value' => (string) ($immunization['id'] ?? $immunizationUuid),
                ]],
                'status' => $status,
                'vaccineCode' => $this->buildSimpleCodeableConcept(
                    $vaccineCodeSystem,
                    $vaccineCode,
                    $vaccineName,
                    $vaccineName
                ),
                'patient' => ['reference' => $patientRef, 'display' => 'Patient'],
                'occurrenceDateTime' => $occurrence,
                'recorded' => $issuedAt,
                'primarySource' => true,
            ];

            if ($encounterRef !== '') {
                $resource['encounter'] = ['reference' => $encounterRef];
            }
            if ($organizationRef !== '') {
                $resource['location'] = ['reference' => $organizationRef, 'display' => trim((string) ($organization['name'] ?? ''))];
            }
            $manufacturer = trim((string) ($immunization['manufacturer'] ?? ''));
            if ($manufacturer !== '') {
                $resource['manufacturer'] = ['display' => $manufacturer];
            }
            $lotNumber = trim((string) ($immunization['lot_number'] ?? ''));
            if ($lotNumber !== '') {
                $resource['lotNumber'] = $lotNumber;
            }
            $expiryDate = trim((string) ($immunization['expiry_date'] ?? $immunization['expirationDate'] ?? ''));
            if ($expiryDate !== '' && strtotime($expiryDate) !== false) {
                $resource['expirationDate'] = date('Y-m-d', strtotime($expiryDate));
            }
            $site = $this->buildSimpleCodeableConcept('http://snomed.info/sct', (string) ($immunization['site_code'] ?? ''), (string) ($immunization['site_name'] ?? ''), (string) ($immunization['site_name'] ?? ''));
            if (! empty($site['text']) || ! empty($site['coding'])) {
                $resource['site'] = $site;
            }
            $route = $this->buildSimpleCodeableConcept('http://snomed.info/sct', (string) ($immunization['route_code'] ?? ''), (string) ($immunization['route_name'] ?? ''), (string) ($immunization['route_name'] ?? ''));
            if (! empty($route['text']) || ! empty($route['coding'])) {
                $resource['route'] = $route;
            }
            if ($practitionerRef !== '') {
                $resource['performer'] = [[
                    'function' => ['text' => 'Administering provider'],
                    'actor' => ['reference' => $practitionerRef, 'display' => trim((string) ($practitioner['name'] ?? ''))],
                ]];
            } elseif ($organizationRef !== '') {
                $resource['performer'] = [[
                    'function' => ['text' => 'Administering organization'],
                    'actor' => ['reference' => $organizationRef, 'display' => trim((string) ($organization['name'] ?? ''))],
                ]];
            }

            $protocol = [];
            $series = trim((string) ($immunization['series_name'] ?? ''));
            if ($series !== '') {
                $protocol['series'] = $series;
            }
            $dose = $this->buildDoseNumberElement((string) ($immunization['dose_number'] ?? ''));
            if (! empty($dose)) {
                $protocol += $dose;
            }
            $seriesDoses = $this->buildDoseNumberElement((string) ($immunization['series_doses'] ?? ''), 'seriesDoses');
            if (! empty($seriesDoses)) {
                $protocol += $seriesDoses;
            }
            $targetDisease = $this->buildSimpleCodeableConcept('http://snomed.info/sct', (string) ($immunization['target_disease_code'] ?? ''), (string) ($immunization['target_disease_name'] ?? ''), (string) ($immunization['target_disease_name'] ?? ''));
            if (! empty($targetDisease['text']) || ! empty($targetDisease['coding'])) {
                $protocol['targetDisease'] = [$targetDisease];
            }
            if ($organizationRef !== '') {
                $protocol['authority'] = ['reference' => $organizationRef];
            }
            if (! empty($protocol)) {
                $resource['protocolApplied'] = [$protocol];
            }

            $note = trim((string) ($immunization['notes'] ?? ''));
            if ($note !== '') {
                $resource['note'] = [['text' => $note]];
            }

            $immunizationRefs[] = ['reference' => $immunizationRef, 'display' => $vaccineName];
            $immunizationRefs[array_key_last($immunizationRefs)]['type'] = 'Immunization';
            $resourceEntries[] = ['fullUrl' => $immunizationRef, 'resource' => $resource];
        }

        $composition = [
            'resourceType' => 'Composition',
            'id' => $compositionUuid,
            'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/ImmunizationRecord']],
            'language' => 'en-IN',
            'text' => [
                'status' => 'generated',
                'div' => '<div xmlns="http://www.w3.org/1999/xhtml">Immunization Record</div>',
            ],
            'identifier' => [
                'system' => 'https://ndhm.in/phr',
                'value' => $compositionUuid,
            ],
            'status' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '41000179103',
                    'display' => 'Immunization record',
                ]],
                'text' => 'Immunization record',
            ],
            'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
            'date' => $issuedAt,
            'title' => 'Immunization Record',
            'section' => [[
                'title' => 'Immunization record',
                'code' => ['coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '41000179103',
                    'display' => 'Immunization record',
                ]]],
                'entry' => $immunizationRefs,
            ]],
        ];
        if ($practitionerRef !== '') {
            $composition['author'] = [[
                'reference' => $practitionerRef,
                'display' => trim((string) ($practitioner['name'] ?? '')),
            ]];
        } elseif ($organizationRef !== '') {
            $composition['author'] = [[
                'reference' => $organizationRef,
                'display' => trim((string) ($organization['name'] ?? '')),
            ]];
        }
        if ($organizationRef !== '') {
            $composition['custodian'] = [
                'reference' => $organizationRef,
                'display' => trim((string) ($organization['name'] ?? '')),
            ];
        }

        $careContextReference = trim((string) ($context['care_context_reference'] ?? ''));
        if ($careContextReference === '') {
            $sourceIds = [];
            foreach ($immunizations as $immunization) {
                $sourceId = trim((string) ($immunization['id'] ?? ''));
                if ($sourceId !== '') {
                    $sourceIds[] = $sourceId;
                }
            }
            $sourceIds = array_values(array_unique($sourceIds));
            sort($sourceIds, SORT_STRING);
            if (count($sourceIds) === 1) {
                $careContextReference = 'IMM-' . $sourceIds[0];
            } elseif ($sourceIds !== []) {
                $careContextReference = 'IMM-SET-' . substr(hash('sha256', implode(',', $sourceIds)), 0, 20);
            } else {
                $careContextReference = 'IMM-PAT-' . trim((string) ($patient['id'] ?? 'unknown'));
            }
        }
        $hfrId = trim((string) ($organization['hfr_id'] ?? $organization['id'] ?? ''));

        return $this->sanitizeBundle([
            'resourceType' => 'Bundle',
            'id' => $bundleUuid,
            'meta' => [
                'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle'],
                'security' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-Confidentiality',
                    'code' => 'V',
                    'display' => 'very restricted',
                ]],
            ],
            'identifier' => [
                'system' => $hfrId !== '' ? ('https://' . $hfrId . '.hfr.abdm.gov.in') : 'urn:ietf:rfc:3986',
                'value' => $careContextReference,
            ],
            'type' => 'document',
            'timestamp' => $issuedAt,
            'entry' => array_merge([['fullUrl' => 'urn:uuid:' . $compositionUuid, 'resource' => $composition]], $resourceEntries),
        ]);
    }

    private function normalizeImmunizationStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return match ($status) {
            'completed', 'done', 'given' => 'completed',
            'entered-in-error', 'entered_in_error', 'error' => 'entered-in-error',
            default => 'not-done',
        };
    }

    /** @return array{0:string,1:string} */
    private function normalizeVaccineCoding(string $system, string $code): array
    {
        $system = trim($system);
        $code = trim($code);
        if ($system !== 'https://hms.local/immunization/uip') {
            return [$system, $code];
        }

        $cvxCodes = [
            'UIP-BCG' => '19',
            'UIP-OPV' => '02',
            'UIP-HEPB' => '45',
            'UIP-ROTA' => '122',
            'UIP-FIPV' => '10',
            'UIP-PCV' => '152',
            'UIP-MR' => '04',
            'UIP-JE' => '39',
            'UIP-DPT' => '01',
            'UIP-TD' => '09',
        ];
        if (! isset($cvxCodes[$code])) {
            return [$system, $code];
        }

        return ['http://hl7.org/fhir/sid/cvx', $cvxCodes[$code]];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildSimpleCodeableConcept(string $system, string $code, string $display, string $text): array
    {
        $concept = [];
        $text = trim($text);
        if ($text !== '') {
            $concept['text'] = $text;
        }

        $system = trim($system);
        $code = trim($code);
        $display = trim($display);
        if ($system !== '' && $code !== '' && $display !== '') {
            $concept['coding'] = [[
                'system' => $system,
                'code' => $code,
                'display' => $display,
            ]];
        }

        return $concept;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildDoseNumberElement(string $value, string $fieldPrefix = 'doseNumber'): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        if (preg_match('/^[1-9]\d*$/', $value) === 1) {
            return [$fieldPrefix . 'PositiveInt' => (int) $value];
        }

        return [$fieldPrefix . 'String' => $value];
    }

    /** Generate a cryptographically random UUID v4. */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /** @param array<string,mixed> $bundle
     * @return array<string,mixed>
     */
    private function sanitizeBundle(array $bundle): array
    {
        return $this->sanitizeFhirValue($bundle);
    }

    /** @param mixed $value
     * @return mixed
     */
    private function sanitizeFhirValue($value)
    {
        if (is_string($value)) {
            return in_array(strtoupper(trim($value)), ['NA', 'N/A', 'NULL', 'NOT AVAILABLE'], true) ? null : $value;
        }
        if (! is_array($value)) {
            return $value;
        }

        $clean = [];
        foreach ($value as $key => $nested) {
            $sanitized = $this->sanitizeFhirValue($nested);
            if ($sanitized === null || $sanitized === '' || $sanitized === []) {
                continue;
            }
            $clean[$key] = $sanitized;
        }

        return $clean;
    }

    /** ISO 8601 timestamp with India Standard Time offset (+05:30). */
    private function isoTimestamp(): string
    {
        return (new \DateTime('now', new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d\TH:i:sP');
    }

    /**
     * Normalize any datetime-like string into ISO 8601 with IST offset.
     */
    private function normalizeIsoDateTime(string $value, string $fallback = ''): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return trim($fallback);
        }

        try {
            $hasTimezone = preg_match('/([+-]\d{2}:\d{2}|Z)$/', $raw) === 1;
            $dt = $hasTimezone && strpos($raw, 'T') !== false
                ? new \DateTime($raw)
                : new \DateTime($raw, new \DateTimeZone('Asia/Kolkata'));

            return $dt->setTimezone(new \DateTimeZone('Asia/Kolkata'))->format('Y-m-d\TH:i:sP');
        } catch (\Throwable $e) {
            return trim($fallback);
        }
    }

    /**
     * @param array<string, mixed> $patient
     *
     * @return array<string, mixed>
     */
    private function buildPatientResource(array $patient): array
    {
        // Build structured name: prefer explicit given_name/family_name fields;
        // fall back to splitting the full 'name' string.
        $fullText   = trim((string) ($patient['name'] ?? ''));
        $givenName  = trim((string) ($patient['given_name']  ?? ''));
        $familyName = trim((string) ($patient['family_name'] ?? ''));

        if ($givenName === '' && $familyName === '' && $fullText !== '') {
            // Try splitting: last word → family, rest → given
            $parts      = preg_split('/\s+/', $fullText, -1, PREG_SPLIT_NO_EMPTY) ?: [$fullText];
            $familyName = count($parts) > 1 ? (string) array_pop($parts) : '';
            $givenName  = implode(' ', $parts);
        }

        $nameText  = $fullText !== '' ? $fullText : trim($givenName . ' ' . $familyName);
        $nameEntry = ['text' => $nameText];
        if ($givenName !== '') {
            $nameEntry['given'] = [$givenName];
        }
        if ($familyName !== '') {
            $nameEntry['family'] = $familyName;
        }

        $resource = [
            'resourceType' => 'Patient',
            'id'           => (string) ($patient['id'] ?? 'unknown'),
            'name'         => [$nameEntry],
        ];

        $gender = $this->normalizeAdministrativeGender($patient['gender'] ?? null);
        if ($gender !== '') {
            $resource['gender'] = $gender;
        }

        if (! empty($patient['birthDate'])) {
            $resource['birthDate'] = (string) $patient['birthDate'];
        }

        $abhaAddress = trim((string) ($patient['abhaAddress'] ?? ''));
        if ($abhaAddress !== '') {
            $resource['meta']       = ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient']];
            // NI = National unique individual identifier (ABHA number / address)
            $resource['identifier'] = [[
                'type'   => ['coding' => [[
                    'system'  => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                    'code'    => 'NI',
                    'display' => 'National unique individual identifier',
                ]]],
                'system' => 'https://healthid.ndhm.gov.in',
                'value'  => $abhaAddress,
            ]];
        }

        // Phone / mobile telecom
        $phone = trim((string) ($patient['phone'] ?? ''));
        if ($phone !== '') {
            $resource['telecom'] = [[
                'system' => 'phone',
                'value'  => $phone,
                'use'    => 'mobile',
            ]];
        }

        return $resource;
    }

    /**
     * Convert legacy/local gender values to FHIR AdministrativeGender.
     */
    private function normalizeAdministrativeGender($value): string
    {
        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return '';
        }

        return match ($raw) {
            'm', 'male', '1' => 'male',
            'f', 'female', '2' => 'female',
            'o', 'other', '3', 'transgender', 'trans' => 'other',
            'u', 'unknown', '0', 'na', 'n/a', 'not known' => 'unknown',
            default => in_array($raw, ['male', 'female', 'other', 'unknown'], true) ? $raw : 'unknown',
        };
    }

    /**
     * Map common route-of-administration text to SNOMED CT codes.
     * Returns empty string when no mapping is found.
     */
    private function resolveRouteSnomedCode(string $routeText): string
    {
        static $map = [
            // Oral
            'oral'             => '26643006',
            'by mouth'         => '26643006',
            'po'               => '26643006',
            'per oral'         => '26643006',
            // Intravenous
            'intravenous'      => '47625008',
            'iv'               => '47625008',
            'i.v.'             => '47625008',
            'intravenously'    => '47625008',
            // Intramuscular
            'intramuscular'    => '78421000',
            'im'               => '78421000',
            'i.m.'             => '78421000',
            // Subcutaneous
            'subcutaneous'     => '34206005',
            'sc'               => '34206005',
            's.c.'             => '34206005',
            'subcut'           => '34206005',
            // Topical
            'topical'          => '6064005',
            'locally'          => '6064005',
            // Inhaled / nebulisation
            'inhaled'          => '18679011000001101',
            'inhalation'       => '18679011000001101',
            'nebulisation'     => '18679011000001101',
            'nebulizer'        => '18679011000001101',
            // Sublingual
            'sublingual'       => '37839007',
            'sl'               => '37839007',
            // Rectal
            'rectal'           => '37161004',
            'per rectal'       => '37161004',
            'pr'               => '37161004',
            // Nasal
            'nasal'            => '46713006',
            'intranasal'       => '46713006',
            // Ophthalmic / eye
            'ophthalmic'       => '54485002',
            'eye'              => '54485002',
            'otic'             => '10547007',
            'ear'              => '10547007',
        ];
        $key = strtolower(trim($routeText));
        return $map[$key] ?? '';
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
        // ABDM requires the Composition to be the first bundle entry.
        array_unshift($entries, ['resource' => $composition]);

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
