<?php

namespace App\Libraries;

use CodeIgniter\I18n\Time;

class FhirR4Builder
{
    /**
     * @param array<string, mixed> $patient
     * @param array<string, mixed> $encounter
     * @param array<int, array<string, mixed>> $medications
     *
     * @return array<string, mixed>
     */
    public function buildPrescriptionBundle(array $patient, array $encounter, array $medications): array
    {
        $issuedAt = Time::now('Asia/Kolkata')->toDateTimeString();
        $patientRef = 'Patient/' . (string) ($patient['id'] ?? 'unknown');
        $encounterRef = 'Encounter/' . (string) ($encounter['id'] ?? 'unknown');

        $entries = [[
            'resource' => $this->buildPatientResource($patient),
        ], [
            'resource' => [
                'resourceType' => 'Encounter',
                'id' => (string) ($encounter['id'] ?? 'unknown'),
                'status' => (string) ($encounter['status'] ?? 'finished'),
                'subject' => ['reference' => $patientRef],
            ],
        ]];

        foreach ($medications as $index => $medication) {
            $entryId = 'medreq-' . ($index + 1);
            $entries[] = [
                'resource' => [
                    'resourceType' => 'MedicationRequest',
                    'id' => $entryId,
                    'status' => (string) ($medication['status'] ?? 'active'),
                    'intent' => 'order',
                    'subject' => ['reference' => $patientRef],
                    'encounter' => ['reference' => $encounterRef],
                    'authoredOn' => $issuedAt,
                    'medicationCodeableConcept' => [
                        'text' => (string) ($medication['drug_name'] ?? ''),
                    ],
                    'dosageInstruction' => [[
                        'text' => (string) ($medication['dosage'] ?? ''),
                    ]],
                ],
            ];
        }

        return [
            'resourceType' => 'Bundle',
            'type' => 'collection',
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
