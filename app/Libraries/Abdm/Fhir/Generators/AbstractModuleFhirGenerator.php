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

        return [
            'resourceType' => 'Encounter',
            'id' => 'encounter-' . $id,
            'status' => 'finished',
            'subject' => [
                'reference' => 'urn:uuid:patient-' . (string) ($source['patient']['id'] ?? ''),
            ],
            'period' => [
                'start' => (string) ($enc['start'] ?? $source['completed_at'] ?? date(DATE_ATOM)),
                'end' => (string) ($enc['end'] ?? $source['completed_at'] ?? date(DATE_ATOM)),
            ],
            'identifier' => [[
                'system' => 'https://hms.local/encounter-id',
                'value' => $id,
            ]],
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
            'name' => $name,
            'identifier' => $id !== '' ? [[
                'system' => 'https://hms.local/organization-id',
                'value' => $id,
            ]] : null,
        ];
    }
}
