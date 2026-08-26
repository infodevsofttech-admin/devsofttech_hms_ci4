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
                'text' => (string) ($source['patient']['name'] ?? ''),
            ]],
            'gender' => (string) ($source['patient']['gender'] ?? ''),
            'birthDate' => (string) ($source['patient']['dob'] ?? ''),
        ];

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
                'system' => 'https://abdm.gov.in/health-address',
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
        $encounterRef = isset($source['encounter']) ? 'urn:uuid:encounter-' . ((string) ($source['encounter']['id'] ?? $recordId)) : null;

        $composition = [
            'resourceType' => 'Composition',
            'id' => 'composition-' . $recordId,
            'status' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => $loincCode,
                    'display' => $loincDisplay,
                ]],
            ],
            'title' => $title,
            'date' => (string) ($source['completed_at'] ?? date(DATE_ATOM)),
            'subject' => ['reference' => 'urn:uuid:patient-' . $patientId],
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

        return [
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
                'start' => (string) ($enc['start'] ?? $source['completed_at'] ?? date(DATE_ATOM)),
                'end' => (string) ($enc['end'] ?? $source['completed_at'] ?? date(DATE_ATOM)),
            ],
            'identifier' => $identifiers,
        ];
    }

    /** @param array<string,mixed> $source */
    protected function buildPractitioner(array $source): ?array
    {
        if (! isset($source['doctor']) || ! is_array($source['doctor'])) {
            return null;
        }

        $doctor = $source['doctor'];
        $id = trim((string) ($doctor['id'] ?? ''));
        $name = trim((string) ($doctor['name'] ?? ''));
        if ($id === '' && $name === '') {
            return null;
        }

        return [
            'resourceType' => 'Practitioner',
            'id' => 'practitioner-' . ($id !== '' ? $id : md5($name)),
            'meta' => ['profile' => [
                'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner',
            ]],
            'name' => [[
                'text' => $name,
            ]],
            'identifier' => $id !== '' ? [[
                'system' => 'https://hms.local/doctor-id',
                'value' => $id,
            ]] : null,
        ];
    }

    /** @param array<string,mixed> $source */
    protected function buildOrganization(array $source): ?array
    {
        if (! isset($source['organization']) || ! is_array($source['organization'])) {
            return null;
        }

        $org = $source['organization'];
        $id = trim((string) ($org['id'] ?? ''));
        $name = trim((string) ($org['name'] ?? ''));
        if ($id === '' && $name === '') {
            return null;
        }

        return [
            'resourceType' => 'Organization',
            'id' => 'organization-' . ($id !== '' ? $id : md5($name)),
            'meta' => ['profile' => [
                'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization',
            ]],
            'name' => $name,
            'identifier' => $id !== '' ? [[
                'system' => 'https://hms.local/organization-id',
                'value' => $id,
            ]] : null,
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
