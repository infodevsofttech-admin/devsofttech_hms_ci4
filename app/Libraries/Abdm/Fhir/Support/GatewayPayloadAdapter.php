<?php

namespace App\Libraries\Abdm\Fhir\Support;

class GatewayPayloadAdapter
{
    /**
     * @param array<string,mixed> $generatorOutput
     * @param array<string,mixed> $source
     * @param string $hfrId
     * @return array<string,mixed>
     */
    public function toGatewayPayload(array $generatorOutput, array $source, string $hfrId): array
    {
        $patient = (array) ($source['patient'] ?? []);

        return [
            'hfr_id' => $hfrId,
            'hi_type' => (string) ($generatorOutput['hi_type'] ?? ''),
            'record_type' => (string) ($generatorOutput['hi_type'] ?? ''),
            'abha_id' => $this->normalizeAbhaDigits((string) ($patient['abha_id'] ?? '')),
            'abha_address' => trim((string) ($patient['abha_address'] ?? '')),
            'patient_name' => trim((string) ($patient['name'] ?? '')),
            'local_patient_id' => (string) ($patient['id'] ?? ''),
            'care_context_reference' => (string) ($generatorOutput['care_context_reference'] ?? ''),
            'care_context_display' => (string) ($generatorOutput['care_context_display'] ?? ''),
            'visit_date' => (string) ($source['visit_date'] ?? date('Y-m-d')),
            'department' => (string) ($source['department'] ?? ''),
            'doctor_name' => (string) (($source['doctor']['name'] ?? '') ?: ($source['doctor_name'] ?? '')),
            'fhir_bundle' => (array) ($generatorOutput['fhir_bundle'] ?? []),
        ];
    }

    private function normalizeAbhaDigits(string $abha): string
    {
        $digits = preg_replace('/\D/', '', $abha);
        return is_string($digits) ? $digits : '';
    }
}
