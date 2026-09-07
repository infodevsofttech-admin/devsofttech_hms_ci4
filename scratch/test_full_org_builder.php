<?php

function buildOrganizationAddress(array $organization): array
{
    $addressText = trim((string) ($organization['address'] ?? ''));
    $line1 = trim((string) ($organization['address_1'] ?? $organization['line1'] ?? ''));
    $line2 = trim((string) ($organization['address_2'] ?? $organization['line2'] ?? ''));
    $city = trim((string) ($organization['city'] ?? ''));
    $state = trim((string) ($organization['state'] ?? ''));
    $pincode = trim((string) ($organization['pincode'] ?? $organization['postalCode'] ?? $organization['pin'] ?? ''));

    if ($addressText !== '' && ($city === '' || $state === '' || $pincode === '')) {
        if ($pincode === '' && preg_match('/\b(\d{6})\b/', $addressText, $pm)) {
            $pincode = $pm[1];
        }
        if ($state === '' && preg_match('/\b(Uttarakhand|Uttar Pradesh|Delhi|Haryana|Punjab|Maharashtra|Karnataka|Tamil Nadu|Gujarat|Rajasthan|Bihar|West Bengal|Madhya Pradesh|Kerala|Odisha|Telangana|Andhra Pradesh|Assam|Jharkhand)\b/i', $addressText, $sm)) {
            $state = $sm[1];
        }
        if ($city === '' && preg_match('/(?:,\s*|\b)([A-Z][a-zA-Z\s]+?)(?:\s*-\s*\d{6}|\s*,\s*(?:Uttarakhand|Uttar Pradesh|Delhi|Haryana|Punjab|India))/i', $addressText, $cm)) {
            $city = trim($cm[1]);
        }
    }

    $lines = [];
    if ($line1 !== '') {
        $lines[] = $line1;
    }
    if ($line2 !== '' && ($line1 === '' || ($city === '' && $state === ''))) {
        $lines[] = $line2;
    }

    if (empty($lines) && $addressText === '' && $city === '' && $state === '' && $pincode === '') {
        return [];
    }

    $address = [
        'line' => ! empty($lines) ? $lines : [$addressText !== '' ? $addressText : 'Hospital Facility'],
    ];
    if ($city !== '') {
        $address['city'] = $city;
    }
    if ($state !== '') {
        $address['state'] = $state;
    }
    if ($pincode !== '') {
        $address['postalCode'] = $pincode;
    }
    $address['country'] = 'IND';
    $address['text'] = $addressText !== '' ? $addressText : implode(', ', array_filter([implode(', ', $lines), $city, $state, $pincode, 'IND']));

    return $address;
}

function buildOrganizationResource(array $organization, string $organizationUuid): array
{
    $hfrId = trim((string) ($organization['hfr_id'] ?? $organization['id'] ?? ''));
    $name = trim((string) ($organization['name'] ?? ''));

    $resource = [
        'resourceType' => 'Organization',
        'id' => $organizationUuid,
        'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization']],
        'name' => $name,
    ];

    if ($hfrId !== '') {
        $resource['identifier'] = [[
            'type' => ['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                'code' => 'PRN',
                'display' => 'Provider number',
            ]]],
            'system' => 'https://facility.ndhm.gov.in',
            'value' => $hfrId,
        ]];
    }

    $telecom = [];
    if (isset($organization['telecom']) && is_array($organization['telecom'])) {
        $telecom = $organization['telecom'];
    } else {
        $phone = trim((string) ($organization['phone'] ?? $organization['phone_no'] ?? ''));
        if ($phone !== '') {
            $telecom[] = [
                'system' => 'phone',
                'value' => $phone,
                'use' => 'work',
            ];
        }
        $email = trim((string) ($organization['email'] ?? ''));
        if ($email !== '') {
            $telecom[] = [
                'system' => 'email',
                'value' => $email,
                'use' => 'work',
            ];
        }
    }
    if (! empty($telecom)) {
        $resource['telecom'] = $telecom;
    }

    if (isset($organization['address']) && is_array($organization['address'])) {
        $resource['address'] = $organization['address'];
    } else {
        $addr = buildOrganizationAddress($organization);
        if (! empty($addr)) {
            $resource['address'] = [$addr];
        }
    }

    return $resource;
}

// Test Case 1: minimal
$t1 = buildOrganizationResource(['id' => 'H1', 'name' => 'Test Hospital'], 'uuid-1');
echo "Case 1 (minimal):\n" . json_encode($t1, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Test Case 2: full live hospital data
$t2 = buildOrganizationResource([
    'name' => 'E-Atria Hospital',
    'hfr_id' => 'IN0510000871',
    'address' => 'Avas Vikas, Kashipur -244713, Uttarakhand',
    'address_1' => 'Avas Vikas',
    'address_2' => 'Kashipur -244713, Uttarakhand',
    'phone' => '90125 12505',
    'email' => '',
], 'uuid-2');
echo "Case 2 (live):\n" . json_encode($t2, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
