<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class WellnessFhirGenerator extends AbstractModuleFhirGenerator
{
    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public function generate(array $source): array
    {
        $timestamp = (string) ($source['completed_at'] ?? date(DATE_ATOM));
        $recordId = (string) ($source['record_id'] ?? '0');
        $patientId = (string) ($source['patient']['id'] ?? '0');
        $sessionId = (string) ($source['session_id'] ?? '0');
        $visitDate = (string) ($source['visit_date'] ?? date('Y-m-d'));

        $careContextReference = 'WELLNESS-' . $recordId . '-S' . $sessionId . '-' . $visitDate;
        $careContextDisplay = 'Wellness & Lifestyle Record ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta('wellness-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->buildComposition($this->buildBaseComposition($source, 'Wellness Record', '72198-1', 'Health record document'))
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
        }

        $practitioner = $this->buildPractitioner($source);
        if (is_array($practitioner)) {
            $builder->addPractitioner($practitioner);
        }

        $organization = $this->buildOrganization($source);
        if (is_array($organization)) {
            $builder->addOrganization($organization);
        }

        $patientRef = 'urn:uuid:patient-' . $patientId;
        $encounterRef = is_array($encounter) ? 'urn:uuid:' . (string) ($encounter['id'] ?? '') : null;

        // Vitals (Pulse, SpO2, BP, Temp, Height, Weight)
        foreach ((array) ($source['vitals'] ?? []) as $idx => $vital) {
            $display = trim((string) ($vital['display'] ?? ''));
            if (! $this->isMeaningfulValue($display)) {
                continue;
            }

            $loinc = $this->codingResolver->resolveLoincForLabTest((string) ($vital['code'] ?? ''), $display);
            $ucum = $this->codingResolver->resolveUnitUcUM((string) ($vital['unit'] ?? ''));
            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => 'vital-' . $recordId . '-' . $idx,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/ObservationVitalSigns']],
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'vital-signs',
                        'display' => 'Vital Signs',
                    ]],
                ]],
                'code' => [
                    'coding' => $loinc['coding'] ?? [],
                    'text' => $display,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueQuantity' => [
                    'value' => (float) ($vital['value'] ?? 0),
                    'unit' => (string) ($vital['unit'] ?? ''),
                    'system' => 'http://unitsofmeasure.org',
                    'code' => (string) ($ucum['code'] ?? ''),
                ],
            ]);
        }

        // General Advice / Diet & Lifestyle Observations
        $adviceItems = (array) ($source['advice'] ?? $source['general_advice'] ?? []);
        foreach ($adviceItems as $idx => $adviceText) {
            $text = trim((string) (is_array($adviceText) ? ($adviceText['text'] ?? '') : $adviceText));
            if (! $this->isMeaningfulValue($text)) {
                continue;
            }

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => 'advice-' . $recordId . '-' . $idx,
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'social-history',
                        'display' => 'Social History',
                    ]],
                ]],
                'code' => [
                    'text' => 'Lifestyle & Diet Advice',
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueString' => $text,
            ]);
        }

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'wellness', ['resolved' => 1, 'unresolved' => 0, 'fallback_used' => 0]);

        return [
            'hi_type' => 'WellnessRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}
