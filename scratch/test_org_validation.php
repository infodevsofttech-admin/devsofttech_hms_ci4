<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/package/package/StructureDefinition-Organization.json'), true);
$elements = [];
foreach ($sd['snapshot']['element'] as $el) {
    $elements[$el['id']] = $el;
}

$org = [
    'resourceType' => 'Organization',
    'id' => 'ef4594f9-eca5-4aa5-8251-9887030c7e9b',
    'meta' => [
        'profile' => [
            'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization'
        ]
    ],
    'name' => 'E-Atria Hospital',
    'identifier' => [
        [
            'type' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                        'code' => 'PRN',
                        'display' => 'Provider number'
                    ]
                ]
            ],
            'system' => 'https://facility.ndhm.gov.in',
            'value' => 'IN0510000871'
        ]
    ],
    'telecom' => [
        [
            'system' => 'phone',
            'value' => '90125 12505',
            'use' => 'work'
        ]
    ],
    'address' => [
        [
            'line' => ['Avas Vikas'],
            'city' => 'Kashipur',
            'state' => 'Uttarakhand',
            'postalCode' => '244713',
            'country' => 'IND',
            'text' => 'Avas Vikas, Kashipur -244713, Uttarakhand'
        ]
    ]
];

echo "Organization JSON:\n" . json_encode($org, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
