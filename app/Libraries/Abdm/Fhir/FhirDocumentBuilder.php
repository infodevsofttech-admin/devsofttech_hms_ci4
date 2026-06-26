<?php

namespace App\Libraries\Abdm\Fhir;

class FhirDocumentBuilder
{
    /** @var array<int,array<string,mixed>> */
    private array $entries = [];

    /** @var array<string,bool> */
    private array $resourceIds = [];

    /** @var array<string,mixed> */
    private array $bundleMeta = [];

    /** @var array<string,mixed>|null */
    private ?array $composition = null;

    public function buildBundleMeta(string $identifierValue, string $timestamp): self
    {
        $this->bundleMeta = [
            'resourceType' => 'Bundle',
            'identifier' => [
                'system' => 'https://hms.local/fhir/document',
                'value' => $identifierValue,
            ],
            'type' => 'document',
            'timestamp' => $timestamp,
        ];

        return $this;
    }

    /**
     * @param array<string,mixed> $composition
     */
    public function buildComposition(array $composition): self
    {
        $this->composition = $composition;
        return $this;
    }

    /**
     * @param array<string,mixed> $resource
     */
    public function addPatient(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addEncounter(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addPractitioner(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addOrganization(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addCondition(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addObservation(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addDiagnosticReport(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addMedicationRequest(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addProcedure(array $resource): self
    {
        return $this->addResource($resource);
    }

    /** @param array<string,mixed> $resource */
    public function addClaim(array $resource): self
    {
        return $this->addResource($resource);
    }

    /**
     * @return array<string,mixed>
     */
    public function toBundle(): array
    {
        $bundle = $this->bundleMeta;
        $entries = [];

        if (is_array($this->composition)) {
            $entries[] = $this->wrapEntry($this->composition);
        }

        foreach ($this->entries as $entry) {
            $entries[] = $entry;
        }

        $bundle['entry'] = $entries;
        return $this->stripNulls($bundle);
    }

    /**
     * @param array<string,mixed> $resource
     */
    private function addResource(array $resource): self
    {
        $id = (string) ($resource['id'] ?? '');
        if ($id === '') {
            $resource['id'] = $this->newId((string) ($resource['resourceType'] ?? 'resource'));
            $id = (string) $resource['id'];
        }

        $fullRef = (string) ($resource['resourceType'] ?? '') . '/' . $id;
        if (isset($this->resourceIds[$fullRef])) {
            return $this;
        }

        $this->resourceIds[$fullRef] = true;
        $this->entries[] = $this->wrapEntry($resource);
        return $this;
    }

    /**
     * @param array<string,mixed> $resource
     * @return array<string,mixed>
     */
    private function wrapEntry(array $resource): array
    {
        $id = (string) ($resource['id'] ?? $this->newId((string) ($resource['resourceType'] ?? 'resource')));
        $resource['id'] = $id;

        return [
            'fullUrl' => 'urn:uuid:' . $id,
            'resource' => $this->stripNulls($resource),
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function stripNulls(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value)) {
                $clean = $this->stripNested($value);
                if ($clean === [] || $clean === null) {
                    continue;
                }
                $out[$key] = $clean;
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }

    /**
     * @param array<int|string,mixed> $value
     * @return array<int|string,mixed>|null
     */
    private function stripNested(array $value): ?array
    {
        $clean = [];
        foreach ($value as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            if (is_array($v)) {
                $nested = $this->stripNested($v);
                if ($nested === null || $nested === []) {
                    continue;
                }
                $clean[$k] = $nested;
                continue;
            }
            $clean[$k] = $v;
        }

        return $clean === [] ? null : $clean;
    }

    private function newId(string $prefix): string
    {
        $prefix = strtolower(preg_replace('/[^a-z0-9]/i', '-', $prefix) ?: 'resource');
        $bytes = bin2hex(random_bytes(6));
        return $prefix . '-' . $bytes;
    }
}
