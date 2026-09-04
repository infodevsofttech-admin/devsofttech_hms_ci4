<?php

namespace App\Libraries\Abdm\Fhir\Generators;

use App\Libraries\Abdm\Fhir\FhirDocumentBuilder;
use App\Libraries\Abdm\Fhir\Support\CodingResolver;
use App\Libraries\Abdm\Fhir\Support\FhirBundleValidator;

abstract class AbstractModuleFhirGenerator
{
    protected FhirDocumentBuilder $builder;
    protected CodingResolver $codingResolver;
    protected FhirBundleValidator $validator;

    public function __construct(
        ?FhirDocumentBuilder $builder = null,
        ?CodingResolver $codingResolver = null,
        ?FhirBundleValidator $validator = null
    ) {
        $this->builder = $builder ?? new FhirDocumentBuilder();
        $this->codingResolver = $codingResolver ?? new CodingResolver();
        $this->validator = $validator ?? new FhirBundleValidator();
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    abstract public function generate(array $source): array;

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    protected function buildBasePatient(array $source): array
    {
        $patientId = (string) ($source['patient']['id'] ?? '');
        $rawGender = strtolower(trim((string) ($source['patient']['gender'] ?? $source['patient']['xgender'] ?? $source['gender'] ?? '')));
        $gender = match ($rawGender) {
            'm', 'male', '1' => 'male',
            'f', 'female', '2' => 'female',
            'o', 'other', '3' => 'other',
            default => 'male',
        };

        $dob = trim((string) ($source['patient']['dob'] ?? $source['patient']['p_dob'] ?? $source['patient']['birth_date'] ?? ''));
        if ($dob === '' && ! empty($source['patient']['age'])) {
            $age = (int) $source['patient']['age'];
            if ($age > 0) {
                $dob = (date('Y') - $age) . '-01-01';
            }
        }

        $patient = [
            'resourceType' => 'Patient',
            'id' => 'patient-' . $patientId,
            'meta' => ['profile' => [
                'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient',
            ]],
            'identifier' => [
                [
                    'system' => 'https://hms.local/patient-id',
                    'value' => $patientId,
                ],
            ],
            'name' => [[
                'text' => (string) ($source['patient']['name'] ?? 'Patient'),
            ]],
            'gender' => $gender,
        ];

        if ($dob !== '') {
            $patient['birthDate'] = $dob;
        }

        $abhaId = preg_replace('/\D/', '', (string) ($source['patient']['abha_id'] ?? ''));
        if (is_string($abhaId) && $abhaId !== '') {
            $patient['identifier'][] = [
                'system' => 'https://healthid.ndhm.gov.in',
                'value' => $abhaId,
            ];
        }

        $uhid = trim((string) ($source['patient']['uhid'] ?? $source['patient']['hospital_patient_id'] ?? $source['patient']['patient_code'] ?? ''));
        if ($this->isMeaningfulValue($uhid)) {
            $patient['identifier'][] = [
                'type' => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                    'code' => 'MR',
                    'display' => 'Medical record number',
                ]], 'text' => 'UHID'],
                'system' => 'https://hms.local/uhid',
                'value' => $uhid,
            ];
        }

        $abhaAddress = trim((string) ($source['patient']['abha_address'] ?? ''));
        if ($abhaAddress !== '') {
            $patient['identifier'][] = [
                'system' => 'https://healthid.ndhm.gov.in/abha-address',
                'value' => $abhaAddress,
            ];
        }

        return $patient;
    }

    /**
     * @param array<string,mixed> $source
     */
    protected function buildBaseComposition(array $source, string $title, string $loincCode, string $loincDisplay): array
    {
        $recordId = (string) ($source['record_id'] ?? '0');
        $patientId = (string) ($source['patient']['id'] ?? '0');
        $patientName = trim((string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient'));
        if ($patientName === '' || strcasecmp($patientName, 'NA') === 0) {
            $patientName = 'Patient';
        }
        $encounterRef = isset($source['encounter']) ? 'urn:uuid:encounter-' . ((string) ($source['encounter']['id'] ?? $recordId)) : null;

        $drId = (string) ($source['practitioner']['id'] ?? $source['doctor']['id'] ?? '1');
        if ($drId === '' || $drId === '0') {
            $drId = '1';
        }
        $drName = trim((string) ($source['practitioner']['name'] ?? $source['doctor']['name'] ?? $source['doctor_name'] ?? 'Dr. Attending Medical Officer'));
        if ($drName === '' || strcasecmp($drName, 'Doctor') === 0 || strcasecmp($drName, 'NA') === 0) {
            $drName = 'Dr. Attending Medical Officer';
        } elseif (! str_starts_with(strtolower($drName), 'dr.') && ! str_starts_with(strtolower($drName), 'dr ')) {
            $drName = 'Dr. ' . $drName;
        }

        $orgId = (string) ($source['organization']['id'] ?? $source['hfr_id'] ?? 'IN0510000871');
        if ($orgId === '') {
            $orgId = 'IN0510000871';
        }

        $authorRef = 'urn:uuid:practitioner-' . $drId;
        $custodianRef = 'urn:uuid:organization-' . $orgId;

        $composition = [
            'resourceType' => 'Composition',
            'id' => 'composition-' . $recordId,
            'status' => 'final',
            'type' => [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => '419891008',
                        'display' => 'Record artifact',
                    ],
                    [
                        'system' => 'http://loinc.org',
                        'code' => $loincCode,
                        'display' => $loincDisplay,
                    ],
                ],
                'text' => 'Health Document Record',
            ],
            'title' => $title,
            'date' => (string) ($source['completed_at'] ?? date(DATE_ATOM)),
            'subject' => [
                'reference' => 'urn:uuid:patient-' . $patientId,
                'display' => $patientName,
            ],
            'author' => [[
                'reference' => $authorRef,
                'display' => $drName,
            ]],
            'custodian' => ['reference' => $custodianRef],
        ];

        if ($encounterRef !== null) {
            $composition['encounter'] = ['reference' => $encounterRef];
        }

        return $composition;
    }

    /**
     * @param array<string,mixed> $source
     */
    protected function buildEncounter(array $source): ?array
    {
        if (! isset($source['encounter']) || ! is_array($source['encounter'])) {
            return null;
        }

        $enc = $source['encounter'];
        $id = (string) ($enc['id'] ?? '');
        if ($id === '') {
            return null;
        }
        $classCode = strtoupper(trim((string) ($enc['class_code'] ?? 'IMP')));
        $classDisplay = $classCode === 'IMP' ? 'inpatient encounter' : 'ambulatory';

        $identifiers = [[
            'system' => 'https://hms.local/encounter-id',
            'value' => $id,
        ]];

        $ipdNo = trim((string) ($enc['ipd_no'] ?? $enc['ipd_number'] ?? $enc['ipd_code'] ?? $source['ipd_no'] ?? ''));
        if ($this->isMeaningfulValue($ipdNo)) {
            $identifiers[] = [
                'system' => 'https://hms.local/ipd-number',
                'value' => $ipdNo,
            ];
        }

        $start = trim((string) ($enc['start'] ?? $enc['admission_date'] ?? $source['admission_date'] ?? $source['completed_at'] ?? ''));
        if ($start === '') {
            $start = date(DATE_ATOM);
        } else {
            $ts = strtotime($start);
            $start = $ts !== false ? date(DATE_ATOM, $ts) : $start;
        }

        $end = trim((string) ($enc['end'] ?? $enc['discharge_date'] ?? $source['discharge_date'] ?? $source['completed_at'] ?? ''));
        if ($end === '') {
            $end = date(DATE_ATOM);
        } else {
            $ts = strtotime($end);
            $end = $ts !== false ? date(DATE_ATOM, $ts) : $end;
        }

        $locationList = [];
        $locDisplay = trim((string) ($enc['location_display'] ?? $enc['location'] ?? $source['location_display'] ?? $source['location'] ?? ''));
        $ward = trim((string) ($enc['ward'] ?? $enc['ward_name'] ?? $source['ward'] ?? $source['ward_name'] ?? ''));
        $room = trim((string) ($enc['room'] ?? $enc['room_name'] ?? $source['room'] ?? $source['room_name'] ?? ''));
        $bed = trim((string) ($enc['bed'] ?? $enc['bed_no'] ?? $enc['bed_number'] ?? $source['bed'] ?? $source['bed_no'] ?? $source['bed_number'] ?? ''));

        if ($locDisplay === '') {
            $locParts = [];
            if ($ward !== '') {
                $locParts[] = 'Ward: ' . $ward;
            }
            if ($room !== '') {
                $locParts[] = 'Room: ' . $room;
            }
            if ($bed !== '') {
                $locParts[] = 'Bed: ' . $bed;
            }
            $locDisplay = implode(', ', $locParts);
        }

        if ($locDisplay !== '') {
            $locItem = [
                'location' => [
                    'display' => $locDisplay,
                ],
                'status' => 'active',
                'period' => [
                    'start' => $start,
                    'end'   => $end,
                ],
            ];
            $locationList[] = $locItem;
        }

        $encounterResource = [
            'resourceType' => 'Encounter',
            'id' => 'encounter-' . $id,
            'meta' => ['profile' => [
                'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter',
            ]],
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => $classCode,
                'display' => $classDisplay,
            ],
            'subject' => [
                'reference' => 'urn:uuid:patient-' . (string) ($source['patient']['id'] ?? ''),
            ],
            'period' => [
                'start' => $start,
                'end' => $end,
            ],
            'identifier' => $identifiers,
        ];

        if (! empty($locationList)) {
            $encounterResource['location'] = $locationList;
        }

        return $encounterResource;
    }

    /** @param array<string,mixed> $source */
    protected function buildPractitioner(array $source): ?array
    {
        $doctor = $source['doctor'] ?? $source['practitioner'] ?? [];
        if (! is_array($doctor)) {
            $doctor = [];
        }

        $id = trim((string) ($doctor['id'] ?? '1'));
        if ($id === '' || $id === '0') {
            $id = '1';
        }

        $name = trim((string) ($doctor['name'] ?? $source['doctor_name'] ?? ''));
        if ($name === '' || strcasecmp($name, 'Doctor') === 0 || strcasecmp($name, 'NA') === 0) {
            $name = 'Dr. Attending Medical Officer';
        } elseif (! str_starts_with(strtolower($name), 'dr.') && ! str_starts_with(strtolower($name), 'dr ')) {
            $name = 'Dr. ' . $name;
        }

        $hprId = trim((string) ($doctor['hpr_id'] ?? $doctor['identifier'] ?? $source['hpr_id'] ?? ''));
        if ($hprId === '') {
            $hprId = 'HPR-' . $id;
        }

        return [
            'resourceType' => 'Practitioner',
            'id' => 'practitioner-' . $id,
            'meta' => ['profile' => [
                'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner',
            ]],
            'identifier' => [[
                'system' => 'https://doctor.ndhm.gov.in',
                'value' => $hprId,
            ]],
            'name' => [['text' => $name]],
        ];
    }

    /** @param array<string,mixed> $source */
    protected function buildOrganization(array $source): ?array
    {
        $org = $source['organization'] ?? [];
        if (! is_array($org)) {
            $org = [];
        }

        $id = trim((string) ($org['id'] ?? $source['hfr_id'] ?? 'IN0510000871'));
        if ($id === '') {
            $id = 'IN0510000871';
        }
        $name = trim((string) ($org['name'] ?? 'E-Atria Hospital'));
        if ($name === '') {
            $name = 'E-Atria Hospital';
        }

        return [
            'resourceType' => 'Organization',
            'id' => 'organization-' . $id,
            'meta' => ['profile' => [
                'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization',
            ]],
            'name' => $name,
            'identifier' => [[
                'system' => 'https://facility.ndhm.gov.in',
                'value' => $id,
            ]],
        ];
    }

    /** Excludes blank, 0, or NA placeholder values so they don't appear in FHIR bundle or PHR app. */
    protected function isMeaningfulValue(string $value): bool
    {
        $v = strtoupper(trim(strip_tags($value)));
        if ($v === '') {
            return false;
        }

        return ! in_array($v, ['0', 'NA', 'N/A', 'N / A', 'N/ A', 'NONE', 'NIL', 'NULL', 'UNDEFINED', 'UNSPECIFIED'], true);
    }

    /** Cleans HTML tags, entities, and line breaks into clean plain text for FHIR text fields. */
    protected function cleanPlainText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/<\/(p|div|h[1-6]|li|tr)>/i', "\n", $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, static fn($l) => $l !== '');

        return trim(implode("\n", $lines));
    }
}
