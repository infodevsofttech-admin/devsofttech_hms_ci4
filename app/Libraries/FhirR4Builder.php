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
        $issuedAt = Time::now('Asia/Kolkata')->toDateTimeString();
        $bundleId = 'bundle-opd-' . (string) ($encounter['id'] ?? 'unknown') . '-' . date('YmdHis');
        $patientRef = 'Patient/' . (string) ($patient['id'] ?? 'unknown');
        $encounterRef = 'Encounter/' . (string) ($encounter['id'] ?? 'unknown');
        $practitioner = is_array($context['practitioner'] ?? null) ? (array) $context['practitioner'] : [];
        $organization = is_array($context['organization'] ?? null) ? (array) $context['organization'] : [];
        $observations = is_array($context['observations'] ?? null) ? (array) $context['observations'] : [];
        $allergies = is_array($context['allergies'] ?? null) ? (array) $context['allergies'] : [];
        $complaints = is_array($context['complaints'] ?? null) ? (array) $context['complaints'] : [];
        $serviceRequests = is_array($context['service_requests'] ?? null) ? (array) $context['service_requests'] : [];
        $appointments = is_array($context['appointments'] ?? null) ? (array) $context['appointments'] : [];

        $practitionerId = trim((string) ($practitioner['id'] ?? ''));
        $organizationId = trim((string) ($organization['id'] ?? ''));
        $practitionerRef = $practitionerId !== '' ? ('Practitioner/' . $practitionerId) : '';
        $organizationRef = $organizationId !== '' ? ('Organization/' . $organizationId) : '';

        $encounterResource = [
            'resourceType' => 'Encounter',
            'id' => (string) ($encounter['id'] ?? 'unknown'),
            'status' => (string) ($encounter['status'] ?? 'finished'),
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => ['reference' => $patientRef],
        ];
        if ($practitionerRef !== '') {
            $encounterResource['participant'] = [[
                'individual' => ['reference' => $practitionerRef],
            ]];
        }
        if ($organizationRef !== '') {
            $encounterResource['serviceProvider'] = ['reference' => $organizationRef];
        }
        if (! empty($encounter['period_start']) || ! empty($encounter['period_end'])) {
            $encounterResource['period'] = [
                'start' => (string) ($encounter['period_start'] ?? $issuedAt),
                'end' => (string) ($encounter['period_end'] ?? $issuedAt),
            ];
        }

        $conditionRefs = [];
        $observationRefs = [];
        $allergyRefs = [];
        $medicationRefs = [];
        $complaintRefs = [];
        $serviceRequestRefs = [];
        $appointmentRefs = [];

        $entries = [[
            'resource' => $this->buildPatientResource($patient),
        ], [
            'resource' => $encounterResource,
        ]];

        if ($organizationRef !== '') {
            $organizationResource = [
                'resourceType' => 'Organization',
                'id' => $organizationId,
                'name' => trim((string) ($organization['name'] ?? '')),
            ];
            $hfrId = trim((string) ($organization['hfr_id'] ?? ''));
            if ($hfrId !== '') {
                $organizationResource['identifier'] = [[
                    'system' => 'https://facility.abdm.gov.in/hfr',
                    'value' => $hfrId,
                ]];
            }
            $entries[] = ['resource' => $organizationResource];
        }

        if ($practitionerRef !== '') {
            $practitionerResource = [
                'resourceType' => 'Practitioner',
                'id' => $practitionerId,
                'name' => [[
                    'text' => trim((string) ($practitioner['name'] ?? '')),
                ]],
            ];
            $regNumber = trim((string) ($practitioner['registration_number'] ?? ''));
            if ($regNumber !== '') {
                $practitionerResource['identifier'] = [[
                    'system' => 'https://hpr.abdm.gov.in/hpr-id',
                    'value' => $regNumber,
                ]];
            }
            $entries[] = ['resource' => $practitionerResource];
        }

        foreach ($conditions as $index => $condition) {
            $text = trim((string) ($condition['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $conditionId = 'cond-' . ($index + 1);
            $conditionRefs[] = [
                'condition' => [
                    'reference' => 'Condition/' . $conditionId,
                ],
            ];

            $verification = trim((string) ($condition['verification_status'] ?? 'provisional'));
            $verificationCode = strtolower($verification) === 'confirmed' ? 'confirmed' : 'provisional';

            $code = [
                'text' => $text,
            ];

            $snomedCode = trim((string) ($condition['snomed_code'] ?? ''));
            if ($snomedCode !== '') {
                $code['coding'] = [[
                    'system' => 'http://snomed.info/sct',
                    'code' => $snomedCode,
                    'display' => trim((string) ($condition['snomed_display'] ?? $text)),
                ]];
            }

            $entries[] = [
                'resource' => [
                    'resourceType' => 'Condition',
                    'id' => $conditionId,
                    'clinicalStatus' => [
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                            'code' => 'active',
                        ]],
                    ],
                    'verificationStatus' => [
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                            'code' => $verificationCode,
                        ]],
                    ],
                    'category' => [[
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => 'encounter-diagnosis',
                        ]],
                    ]],
                    'code' => $code,
                    'subject' => ['reference' => $patientRef],
                    'encounter' => ['reference' => $encounterRef],
                    'recordedDate' => $issuedAt,
                ],
            ];
        }

        foreach ($complaints as $index => $complaint) {
            $text = trim((string) ($complaint['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $complaintId = 'complaint-' . ($index + 1);
            $complaintRefs[] = ['reference' => 'Condition/' . $complaintId];
            $code = ['text' => $text];

            $snomedCode = trim((string) ($complaint['snomed_code'] ?? ''));
            if ($snomedCode !== '') {
                $code['coding'] = [[
                    'system' => 'http://snomed.info/sct',
                    'code' => $snomedCode,
                    'display' => trim((string) ($complaint['snomed_display'] ?? $text)),
                ]];
            }

            $entries[] = [
                'resource' => [
                    'resourceType' => 'Condition',
                    'id' => $complaintId,
                    'clinicalStatus' => [
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                            'code' => 'active',
                        ]],
                    ],
                    'verificationStatus' => [
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                            'code' => 'unconfirmed',
                        ]],
                    ],
                    'category' => [[
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => 'problem-list-item',
                        ]],
                        'text' => 'Chief Complaint',
                    ]],
                    'code' => $code,
                    'subject' => ['reference' => $patientRef],
                    'encounter' => ['reference' => $encounterRef],
                    'recordedDate' => $issuedAt,
                ],
            ];
        }

        foreach ($observations as $index => $observation) {
            $value = $observation['value'] ?? null;
            if (! is_numeric($value)) {
                continue;
            }

            $observationId = 'obs-' . ($index + 1);
            $observationRefs[] = ['reference' => 'Observation/' . $observationId];
            $entries[] = [
                'resource' => [
                    'resourceType' => 'Observation',
                    'id' => $observationId,
                    'status' => 'final',
                    'category' => [[
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                        ]],
                    ]],
                    'code' => [
                        'coding' => [[
                            'system' => 'http://loinc.org',
                            'code' => (string) ($observation['loinc'] ?? ''),
                            'display' => (string) ($observation['display'] ?? ''),
                        ]],
                        'text' => (string) ($observation['display'] ?? ''),
                    ],
                    'subject' => ['reference' => $patientRef],
                    'encounter' => ['reference' => $encounterRef],
                    'effectiveDateTime' => $issuedAt,
                    'valueQuantity' => [
                        'value' => (float) $value,
                        'unit' => (string) ($observation['unit'] ?? ''),
                        'system' => 'http://unitsofmeasure.org',
                        'code' => (string) ($observation['ucum'] ?? ''),
                    ],
                ],
            ];
        }

        foreach ($allergies as $index => $allergy) {
            $codeText = trim((string) ($allergy['code_text'] ?? ''));
            if ($codeText === '') {
                continue;
            }

            $allergyId = 'allergy-' . ($index + 1);
            $allergyRefs[] = ['reference' => 'AllergyIntolerance/' . $allergyId];

            $allergyResource = [
                'resourceType' => 'AllergyIntolerance',
                'id' => $allergyId,
                'clinicalStatus' => [
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                        'code' => (string) ($allergy['clinical_status'] ?? 'active'),
                    ]],
                ],
                'verificationStatus' => [
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification',
                        'code' => (string) ($allergy['verification_status'] ?? 'confirmed'),
                    ]],
                ],
                'patient' => ['reference' => $patientRef],
                'code' => [
                    'text' => $codeText,
                ],
                'recordedDate' => $issuedAt,
            ];

            $reaction = trim((string) ($allergy['reaction_text'] ?? ''));
            if ($reaction !== '') {
                $allergyResource['reaction'] = [[
                    'description' => $reaction,
                ]];
            }

            $entries[] = ['resource' => $allergyResource];
        }

        if (! empty($conditionRefs)) {
            $entries[1]['resource']['diagnosis'] = $conditionRefs;
        }

        foreach ($medications as $index => $medication) {
            $entryId = 'medreq-' . ($index + 1);
            $medicationRefs[] = ['reference' => 'MedicationRequest/' . $entryId];

            $drugName = trim((string) ($medication['drug_name'] ?? ''));
            $genericName = trim((string) ($medication['generic_name'] ?? ''));
            $medType = trim((string) ($medication['med_type'] ?? ''));
            $displayText = $drugName;
            if ($genericName !== '' && stripos($drugName, $genericName) === false) {
                $displayText = trim($drugName . ' (' . $genericName . ')');
            }

            $medicationCodeableConcept = [
                'text' => $displayText !== '' ? $displayText : (string) ($medication['drug_name'] ?? ''),
            ];

            $snomedCode = trim((string) ($medication['snomed_code'] ?? ''));
            $atcCode = strtoupper(trim((string) ($medication['atc_code'] ?? '')));
            if ($snomedCode !== '') {
                $medicationCodeableConcept['coding'] = [[
                    'system' => 'http://snomed.info/sct',
                    'code' => $snomedCode,
                    'display' => $displayText !== '' ? $displayText : $drugName,
                ]];
            } elseif ($atcCode !== '') {
                $medicationCodeableConcept['coding'] = [[
                    'system' => 'http://www.whocc.no/atc',
                    'code' => $atcCode,
                    'display' => $displayText !== '' ? $displayText : $drugName,
                ]];
            }

            $dosageInstruction = [
                'text' => (string) ($medication['dosage'] ?? ''),
            ];

            $routeText = trim((string) ($medication['route_text'] ?? ''));
            if ($routeText !== '') {
                $dosageInstruction['route'] = [
                    'text' => $routeText,
                ];
            }
            if ($medType !== '') {
                $dosageInstruction['method'] = [
                    'text' => $medType,
                ];
            }

            $entries[] = [
                'resource' => [
                    'resourceType' => 'MedicationRequest',
                    'id' => $entryId,
                    'status' => (string) ($medication['status'] ?? 'active'),
                    'intent' => 'order',
                    'subject' => ['reference' => $patientRef],
                    'encounter' => ['reference' => $encounterRef],
                    'authoredOn' => $issuedAt,
                    'medicationCodeableConcept' => $medicationCodeableConcept,
                    'dosageInstruction' => [$dosageInstruction],
                ],
            ];
        }

        foreach ($serviceRequests as $index => $serviceRequest) {
            $codeText = trim((string) ($serviceRequest['code_text'] ?? ''));
            if ($codeText === '') {
                continue;
            }

            $serviceRequestId = 'svc-' . ($index + 1);
            $serviceRequestRefs[] = ['reference' => 'ServiceRequest/' . $serviceRequestId];
            $entries[] = [
                'resource' => [
                    'resourceType' => 'ServiceRequest',
                    'id' => $serviceRequestId,
                    'status' => (string) ($serviceRequest['status'] ?? 'active'),
                    'intent' => (string) ($serviceRequest['intent'] ?? 'order'),
                    'subject' => ['reference' => $patientRef],
                    'encounter' => ['reference' => $encounterRef],
                    'authoredOn' => $issuedAt,
                    'code' => [
                        'text' => $codeText,
                    ],
                ],
            ];
        }

        foreach ($appointments as $index => $appointment) {
            $description = trim((string) ($appointment['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $appointmentId = 'appt-' . ($index + 1);
            $appointmentRefs[] = ['reference' => 'Appointment/' . $appointmentId];
            $appointmentResource = [
                'resourceType' => 'Appointment',
                'id' => $appointmentId,
                'status' => (string) ($appointment['status'] ?? 'proposed'),
                'description' => $description,
                'participant' => [[
                    'actor' => ['reference' => $patientRef],
                    'status' => 'accepted',
                ]],
            ];
            if ($practitionerRef !== '') {
                $appointmentResource['participant'][] = [
                    'actor' => ['reference' => $practitionerRef],
                    'status' => 'accepted',
                ];
            }
            $entries[] = ['resource' => $appointmentResource];
        }

        $compositionSections = [];
        if (! empty($conditionRefs) || ! empty($complaintRefs)) {
            $compositionSections[] = [
                'title' => 'Problems and Diagnoses',
                'entry' => array_values(array_merge($conditionRefs, $complaintRefs)),
            ];
        }
        if (! empty($observationRefs)) {
            $compositionSections[] = [
                'title' => 'Vitals',
                'entry' => $observationRefs,
            ];
        }
        if (! empty($allergyRefs)) {
            $compositionSections[] = [
                'title' => 'Allergies',
                'entry' => $allergyRefs,
            ];
        }
        if (! empty($medicationRefs)) {
            $compositionSections[] = [
                'title' => 'Medications',
                'entry' => $medicationRefs,
            ];
        }
        if (! empty($serviceRequestRefs)) {
            $compositionSections[] = [
                'title' => 'Investigations',
                'entry' => $serviceRequestRefs,
            ];
        }
        if (! empty($appointmentRefs)) {
            $compositionSections[] = [
                'title' => 'Follow Up',
                'entry' => $appointmentRefs,
            ];
        }

        $composition = [
            'resourceType' => 'Composition',
            'id' => 'composition-' . (string) ($encounter['id'] ?? 'unknown'),
            'status' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => '34133-9',
                    'display' => 'Summarization of Episode Note',
                ]],
                'text' => 'OP Consultation Record',
            ],
            'subject' => ['reference' => $patientRef],
            'encounter' => ['reference' => $encounterRef],
            'date' => $issuedAt,
            'title' => 'OP Consultation and Prescription',
            'section' => $compositionSections,
        ];
        if ($practitionerRef !== '') {
            $composition['author'] = [[
                'reference' => $practitionerRef,
            ]];
        }
        $entries[] = ['resource' => $composition];

        return [
            'resourceType' => 'Bundle',
            'identifier' => [
                'system' => 'urn:ietf:rfc:3986',
                'value' => 'urn:uuid:' . $bundleId,
            ],
            'type' => 'document',
            'timestamp' => $issuedAt,
            'entry' => $entries,
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
            $resource['identifier'] = [[
                'system' => 'https://healthid.abdm.gov.in/abha-address',
                'value' => $abhaAddress,
            ]];
        }

        return $resource;
    }
}
