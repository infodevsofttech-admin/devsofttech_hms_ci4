<?php
$addressText = 'Avas Vikas, Kashipur -244713, Uttarakhand';
$line1 = 'Avas Vikas';
$line2 = 'Kashipur -244713, Uttarakhand';
$city = '';
$state = '';
$pincode = '';

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
// If line2 contains city/state/pin and city was parsed from it, omit repeating line2 in line array if line1 exists
if ($line2 !== '' && ($line1 === '' || ($city === '' && $state === ''))) {
    $lines[] = $line2;
}

$address = [
    'line' => !empty($lines) ? $lines : [$addressText !== '' ? $addressText : 'Hospital Facility'],
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

print_r($address);
