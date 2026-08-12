<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class IpdOtForms extends BaseConfig
{
    public array $examinationForms = [
        'ophthalmology_preop_v1' => [
            'title' => 'Pre-operative Ophthalmology Examination',
            'schema_version' => 1,
            'department_aliases' => [
                'ophthalmology',
                'ophthalmic',
                'ophthalmic surgery',
                'eye',
                'eye department',
                'eye surgery',
                'department of eye',
                'department of ophthalmology',
                'ophthalmology department',
                'ophthalmology eye',
            ],
            'columns' => [
                'od' => 'Right Eye (OD)',
                'os' => 'Left Eye (OS)',
            ],
            'rows' => [
                'visual_acuity' => 'Visual Acuity',
                'refraction_findings' => 'Refraction Findings',
                'slit_lamp_examination' => 'Slit Lamp Examination',
                'fundus_examination' => 'Fundus Examination',
                'intraocular_pressure' => 'Intraocular Pressure',
            ],
        ],
    ];

    public function formsForDepartment(string $departmentName): array
    {
        $normalizedDepartment = $this->normalizeDepartmentName($departmentName);
        $matches = [];
        foreach ($this->examinationForms as $formKey => $form) {
            $aliases = array_map(
                fn ($alias): string => $this->normalizeDepartmentName((string) $alias),
                $form['department_aliases'] ?? []
            );
            if ($normalizedDepartment !== '' && in_array($normalizedDepartment, $aliases, true)) {
                $matches[(string) $formKey] = $form;
            }
        }

        return $matches;
    }

    private function normalizeDepartmentName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = (string) preg_replace('/[^a-z0-9]+/', ' ', $name);
        return trim((string) preg_replace('/\s+/', ' ', $name));
    }
}