<?php

namespace App\Libraries\Abdm\Fhir\Support;

class CodingResolver
{
    /** @var array<string,array{code:string,display:string}> */
    private array $labLoincMap = [
        'CBC' => ['code' => '58410-2', 'display' => 'CBC panel - Blood by Automated count'],
        'HB' => ['code' => '718-7', 'display' => 'Hemoglobin [Mass/volume] in Blood'],
        'GLUCOSE' => ['code' => '2345-7', 'display' => 'Glucose [Mass/volume] in Serum or Plasma'],
        'CREATININE' => ['code' => '2160-0', 'display' => 'Creatinine [Mass/volume] in Serum or Plasma'],
    ];

    /** @var array<string,array{code:string,display:string}> */
    private array $radiologySnomedFindingMap = [
        'PNEUMONIA' => ['code' => '233604007', 'display' => 'Pneumonia'],
        'PLEURAL EFFUSION' => ['code' => '60046008', 'display' => 'Pleural effusion'],
        'FRACTURE' => ['code' => '125605004', 'display' => 'Fracture of bone'],
    ];

    /** @var array<string,array{code:string,display:string}> */
    private array $bodySiteMap = [
        'CHEST' => ['code' => '51185008', 'display' => 'Thoracic structure'],
        'ABDOMEN' => ['code' => '818983003', 'display' => 'Abdominal structure'],
        'BRAIN' => ['code' => '12738006', 'display' => 'Brain structure'],
    ];

    /** @return array<string,mixed> */
    public function resolveLoincForLabTest(string $localCode, string $displayName, string $department = '', string $specimenType = ''): array
    {
        return $this->resolveByHeuristic($localCode, $displayName, $this->labLoincMap, 'http://loinc.org');
    }

    /** @return array<string,mixed> */
    public function resolveLoincForLabPanel(string $localCode, string $displayName, string $department = ''): array
    {
        return $this->resolveByHeuristic($localCode, $displayName, $this->labLoincMap, 'http://loinc.org');
    }

    /** @return array<string,mixed> */
    public function resolveSnomedForDiagnosisOrFinding(string $localCode, string $displayName, string $mappedIcd = ''): array
    {
        return $this->resolveByHeuristic($localCode, $displayName, $this->radiologySnomedFindingMap, 'http://snomed.info/sct');
    }

    /** @return array<string,mixed> */
    public function resolveSnomedForBodySite(string $localCode, string $displayName): array
    {
        return $this->resolveByHeuristic($localCode, $displayName, $this->bodySiteMap, 'http://snomed.info/sct');
    }

    /** @return array<string,mixed> */
    public function resolveUnitUcUM(string $unit): array
    {
        $normalized = strtoupper(trim($unit));
        $map = [
            'MG/DL' => 'mg/dL',
            'G/DL' => 'g/dL',
            'MMOL/L' => 'mmol/L',
            'CELLS/CUMM' => '/uL',
            'PERCENT' => '%',
        ];

        if (isset($map[$normalized])) {
            return [
                'code' => $map[$normalized],
                'system' => 'http://unitsofmeasure.org',
                'display' => $unit,
                'source' => 'direct_map',
                'confidence' => 1.0,
                'warnings' => [],
                'unresolved' => false,
            ];
        }

        return [
            'code' => trim($unit),
            'system' => 'http://unitsofmeasure.org',
            'display' => $unit,
            'source' => 'heuristic_fallback',
            'confidence' => 0.4,
            'warnings' => ['UCUM code unresolved; kept local unit'],
            'unresolved' => true,
        ];
    }

    /** @return array<string,mixed> */
    public function resolveInterpretationCode(string $value): array
    {
        $normalized = strtoupper(trim($value));
        $map = [
            'HIGH' => ['code' => 'H', 'display' => 'High'],
            'LOW' => ['code' => 'L', 'display' => 'Low'],
            'NORMAL' => ['code' => 'N', 'display' => 'Normal'],
            'ABNORMAL' => ['code' => 'A', 'display' => 'Abnormal'],
        ];

        if (isset($map[$normalized])) {
            return [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation',
                'code' => $map[$normalized]['code'],
                'display' => $map[$normalized]['display'],
                'source' => 'direct_map',
                'confidence' => 1.0,
                'warnings' => [],
                'unresolved' => false,
            ];
        }

        return [
            'system' => 'http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation',
            'code' => 'U',
            'display' => 'Significant change up',
            'source' => 'heuristic_fallback',
            'confidence' => 0.2,
            'warnings' => ['Interpretation unresolved; used fallback U'],
            'unresolved' => true,
        ];
    }

    /** @return array<string,mixed> */
    public function resolveFallbackCode(string $localCode, string $displayName, string $system): array
    {
        return [
            'coding' => [[
                'system' => $system,
                'code' => trim($localCode) !== '' ? trim($localCode) : 'LOCAL-UNMAPPED',
                'display' => trim($displayName),
            ]],
            'source' => 'heuristic_fallback',
            'confidence' => 0.1,
            'warnings' => ['Local code used as fallback'],
            'unresolved' => true,
        ];
    }

    /**
     * @param array<string,array{code:string,display:string}> $dictionary
     * @return array<string,mixed>
     */
    private function resolveByHeuristic(string $localCode, string $displayName, array $dictionary, string $system): array
    {
        $normalizedCode = strtoupper(trim($localCode));
        $normalizedDisplay = strtoupper(trim($displayName));

        if ($normalizedCode !== '' && isset($dictionary[$normalizedCode])) {
            $hit = $dictionary[$normalizedCode];
            return [
                'coding' => [[
                    'system' => $system,
                    'code' => $hit['code'],
                    'display' => $hit['display'],
                ]],
                'coding_source' => 'direct_map',
                'confidence' => 1.0,
                'warnings' => [],
                'unresolved' => false,
            ];
        }

        foreach ($dictionary as $key => $hit) {
            if ($normalizedDisplay !== '' && str_contains($normalizedDisplay, $key)) {
                return [
                    'coding' => [[
                        'system' => $system,
                        'code' => $hit['code'],
                        'display' => $hit['display'],
                    ]],
                    'coding_source' => 'terminology_match',
                    'confidence' => 0.8,
                    'warnings' => ['Mapped by display-name terminology match'],
                    'unresolved' => false,
                ];
            }
        }

        return [
            'coding' => [[
                'system' => $system,
                'code' => trim($localCode) !== '' ? trim($localCode) : 'UNMAPPED',
                'display' => trim($displayName) !== '' ? trim($displayName) : 'Unmapped',
            ]],
            'coding_source' => 'heuristic_fallback',
            'confidence' => 0.2,
            'warnings' => ['No standard map found; fallback code used'],
            'unresolved' => true,
        ];
    }
}
