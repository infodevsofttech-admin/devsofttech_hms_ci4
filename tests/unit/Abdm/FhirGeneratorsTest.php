<?php

use App\Libraries\Abdm\Fhir\FhirGeneratorFactory;
use App\Libraries\Abdm\Fhir\FhirDocumentBuilder;
use App\Controllers\AbdmGateway;
use App\Controllers\Ipd_discharge;
use App\Libraries\FhirR4Builder;
use CodeIgniter\Test\CIUnitTestCase;

final class FhirGeneratorsTest extends CIUnitTestCase
{
    private FhirGeneratorFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new FhirGeneratorFactory();
    }

    public function testDocumentBuilderRemovesExactPlaceholderValues(): void
    {
        $bundle = (new FhirDocumentBuilder())
            ->buildBundleMeta('placeholder-test', '2026-08-24T10:00:00+05:30')
            ->buildComposition([
                'resourceType' => 'Composition',
                'id' => 'placeholder-composition',
                'status' => 'final',
                'title' => 'NA',
                'subject' => ['display' => 'N/A'],
                'note' => 'Nasal congestion',
            ])
            ->toBundle();

        $composition = $bundle['entry'][0]['resource'];
        $this->assertArrayNotHasKey('title', $composition);
        $this->assertArrayNotHasKey('subject', $composition);
        $this->assertSame('Nasal congestion', $composition['note']);
    }

    public function testLegacyBuilderRemovesExactPlaceholderValues(): void
    {
        $bundle = (new FhirR4Builder())->buildClaimBundle(
            ['id' => 'patient-1', 'name' => 'NA', 'gender' => 'female', 'birthDate' => '1990-01-01'],
            ['id' => 'encounter-1'],
            ['id' => 'claim-1', 'provider' => 'N/A']
        );

        $this->assertStringNotContainsString('"NA"', (string) json_encode($bundle));
        $this->assertStringNotContainsString('"N/A"', (string) json_encode($bundle));
    }

    public function testImmunizationCareContextReferenceIsStableAcrossClinicalDateChanges(): void
    {
        $method = new ReflectionMethod(AbdmGateway::class, 'resolveImmunizationCareContextReference');

        $first = $method->invoke(null, [[
            'id' => 47,
            'given_date' => '2026-08-10 09:00:00',
            'abdm_care_context_reference' => null,
        ]], 47, 12);
        $retry = $method->invoke(null, [[
            'id' => 47,
            'given_date' => '2026-08-15 11:30:00',
            'abdm_care_context_reference' => null,
        ]], 47, 12);

        $this->assertSame('IMM-47', $first);
        $this->assertSame($first, $retry);
        $this->assertSame('LEGACY-IMM-47', $method->invoke(null, [[
            'id' => 47,
            'abdm_care_context_reference' => 'LEGACY-IMM-47',
        ]], 47, 12));
    }

    public function testOpdGeneratorHappyPath(): void
    {
        $src = [
            'record_id' => 101,
            'session_id' => 1,
            'visit_date' => '2026-06-27',
            'completed_at' => '2026-06-27T10:00:00+05:30',
            'patient' => ['id' => 12, 'name' => 'Devender Singh', 'gender' => 'male', 'dob' => '1979-03-28', 'abha_id' => '1234-5678-9012'],
            'encounter' => ['id' => 'E101', 'start' => '2026-06-27T09:30:00+05:30', 'end' => '2026-06-27T10:00:00+05:30'],
            'doctor' => ['id' => 'D9', 'name' => 'Dr. R K Sundriyal'],
            'organization' => ['id' => 'H1', 'name' => 'Chamunda Hospital'],
            'diagnoses' => [['text' => 'Acute upper respiratory infection']],
            'medications' => [['name' => 'Eltroxin 50', 'dosage' => '1-0-0 for 5 days']],
            'vitals' => [['display' => 'Heart Rate', 'code' => '8867-4', 'value' => 82, 'unit' => 'beats/min']],
        ];

        $out = $this->factory->opd()->generate($src);
        $this->assertSame('OPConsultRecord', $out['hi_type']);
        $this->assertSame('Bundle', $out['fhir_bundle']['resourceType']);
        $this->assertSame('document', $out['fhir_bundle']['type']);
        $this->assertSame('Composition', $out['fhir_bundle']['entry'][0]['resource']['resourceType']);
        $this->assertTrue((bool) ($out['validation']['valid'] ?? false));
    }

    public function testLabGeneratorLargeObservationSet(): void
    {
        $obs = [];
        for ($i = 0; $i < 120; $i++) {
            $obs[] = ['name' => 'Glucose', 'code' => 'GLUCOSE', 'value' => 90 + $i, 'unit' => 'mg/dL', 'interpretation' => 'NORMAL'];
        }

        $src = [
            'record_id' => 4001,
            'session_id' => 9,
            'visit_date' => '2026-06-26',
            'completed_at' => '2026-06-26T18:10:00+05:30',
            'patient' => ['id' => 12, 'name' => 'Devender Singh', 'gender' => 'male', 'dob' => '1979-03-28'],
            'encounter' => ['id' => 'L4001', 'start' => '2026-06-26T16:00:00+05:30'],
            'panel_name' => 'Biochemistry',
            'panel_code' => 'GLUCOSE',
            'observations' => $obs,
        ];

        $out = $this->factory->lab()->generate($src);
        $this->assertSame('DiagnosticReportRecord', $out['hi_type']);
        $this->assertTrue((bool) ($out['validation']['valid'] ?? false));
        $this->assertGreaterThan(90, count($out['fhir_bundle']['entry']));
    }

    public function testDeterministicCareContextForSameInput(): void
    {
        $src = [
            'record_id' => 7001,
            'session_id' => 3,
            'visit_date' => '2026-06-25',
            'completed_at' => '2026-06-25T10:00:00+05:30',
            'patient' => ['id' => 1, 'name' => 'Test Patient', 'gender' => 'male', 'dob' => '1990-01-01'],
        ];

        $o1 = $this->factory->invoice()->generate($src);
        $o2 = $this->factory->invoice()->generate($src);

        $this->assertSame($o1['care_context_reference'], $o2['care_context_reference']);
        $this->assertSame($o1['hi_type'], $o2['hi_type']);
    }

    public function testDischargeDocumentHasValidStructureAndResolvableReferences(): void
    {
        $src = [
            'record_id' => 4,
            'bundle_identifier' => 'discharge-A26080000004',
            'session_id' => 4,
            'visit_date' => '2026-08-15',
            'completed_at' => '2026-08-15T12:00:00+05:30',
            'patient' => ['id' => 11, 'name' => 'Test Patient', 'gender' => 'male', 'dob' => '1979-03-28'],
            'encounter' => ['id' => 'A26080000004', 'class_code' => 'IMP', 'start' => '2026-08-14T09:00:00+05:30', 'end' => '2026-08-15T12:00:00+05:30'],
            'doctor' => ['id' => 9, 'name' => 'Test Doctor'],
            'organization' => ['id' => 'H1', 'name' => 'Test Hospital'],
            'chief_complaints' => [['text' => 'Fever and body ache']],
            'diagnoses' => [['text' => 'Viral fever']],
            'procedures' => [['text' => 'Supportive care']],
            'medications' => [['name' => 'Paracetamol', 'dosage' => 'As directed']],
            'investigations' => [['text' => 'Haemoglobin: 11', 'loinc_code' => '718-7']],
            'care_plans' => [['title' => 'Advice', 'description' => 'Review after three days']],
            'documents' => [[
                'title' => 'Discharge Summary PDF',
                'content_type' => 'application/pdf',
                'data' => base64_encode('%PDF-test'),
                'created_at' => '2026-08-15T12:00:00+05:30',
            ]],
        ];

        $bundle = $this->factory->discharge()->generate($src)['fhir_bundle'];
        $composition = $bundle['entry'][0]['resource'];
        $this->assertSame('discharge-A26080000004', $bundle['identifier']['value'] ?? null);
        $this->assertSame('IPD Discharge Summary', $composition['title'] ?? null);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle', $bundle['meta']['profile'] ?? []);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DischargeSummaryRecord', $composition['meta']['profile'] ?? []);
        $this->assertSame('http://snomed.info/sct', $composition['type']['coding'][0]['system'] ?? null);
        $this->assertSame('373942005', $composition['type']['coding'][0]['code'] ?? null);
        $this->assertNotEmpty($composition['author'] ?? []);
        $this->assertNotEmpty($composition['custodian'] ?? []);
        $this->assertNotEmpty($composition['section'] ?? []);
        $this->assertNotEmpty($composition['section'][0]['text']['div'] ?? '');

        $fullUrls = array_column($bundle['entry'], 'fullUrl');
        foreach ($fullUrls as $fullUrl) {
            $this->assertMatchesRegularExpression('/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-a[0-9a-f]{3}-[0-9a-f]{12}$/', $fullUrl);
        }

        $encounters = array_values(array_filter($bundle['entry'], static fn (array $entry): bool => ($entry['resource']['resourceType'] ?? '') === 'Encounter'));
        $patients = array_values(array_filter($bundle['entry'], static fn (array $entry): bool => ($entry['resource']['resourceType'] ?? '') === 'Patient'));
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient', $patients[0]['resource']['meta']['profile'] ?? []);
        $this->assertSame('IMP', $encounters[0]['resource']['class']['code'] ?? null);
        $this->assertSame('A26080000004', $encounters[0]['resource']['identifier'][0]['value'] ?? null);
        $documents = array_values(array_filter($bundle['entry'], static fn (array $entry): bool => ($entry['resource']['resourceType'] ?? '') === 'DocumentReference'));
        $this->assertCount(1, $documents);
        $this->assertSame('%PDF-test', base64_decode((string) ($documents[0]['resource']['content'][0]['attachment']['data'] ?? ''), true));
        $this->assertSame('final', $documents[0]['resource']['docStatus'] ?? null);
        $this->assertSame('373942005', $documents[0]['resource']['type']['coding'][0]['code'] ?? null);

        $sectionCodes = array_column(array_map(static fn (array $section): array => (array) ($section['code']['coding'][0] ?? []), $composition['section']), 'code');
        $this->assertSame(['422843007', '1003642006', '1003640003', '1003606003', '721981007', '734163000', '373942005'], $sectionCodes);
        $reports = array_values(array_filter($bundle['entry'], static fn (array $entry): bool => ($entry['resource']['resourceType'] ?? '') === 'DiagnosticReport'));
        $this->assertCount(1, $reports);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportLab', $reports[0]['resource']['meta']['profile'] ?? []);

        $encoded = json_encode($bundle, JSON_UNESCAPED_SLASHES);
        preg_match_all('/"reference":"(urn:uuid:[^"]+)"/', (string) $encoded, $matches);
        foreach ($matches[1] as $reference) {
            $this->assertContains($reference, $fullUrls, 'Unresolved document reference: ' . $reference);
        }
        $this->assertStringNotContainsString('UNMAPPED', (string) $encoded);
    }

    public function testInvoiceRecordHasNrcesProfilesAndResolvableReferences(): void
    {
        $controller = new AbdmGateway();
        $method = new ReflectionMethod($controller, 'buildSimpleInvoiceBundle');
        $bundle = $method->invoke($controller, [
            'resourceType' => 'Patient',
            'id' => 'patient-11',
            'identifier' => [[
                'system' => 'https://healthid.ndhm.gov.in/abha-address',
                'value' => 'testpatient@sbx',
            ]],
            'name' => [['text' => 'Test Patient']],
            'gender' => 'male',
            'birthDate' => '1990-01-01',
        ], [
            'id' => 19,
            'invoice_code' => 'OPD-19',
            'invoice_type_code' => '03',
            'invoice_type_display' => 'OPD',
            'encounter_class' => 'AMB',
            'practitioner_id' => 7,
            'practitioner_name' => 'Dr Test',
            'encounter_start' => '2026-08-14',
            'inv_date' => '2026-08-15',
            'net_amount' => 300,
            'total_amount' => 350,
        ], [[
            'item_name' => 'OPD Consultation Fee',
            'item_qty' => 1,
            'item_rate' => 300,
            'item_amount' => 300,
        ]]);

        $this->assertSame('document', $bundle['type'] ?? null);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle', $bundle['meta']['profile'] ?? []);
        $this->assertSame('OPD-19', $bundle['identifier']['value'] ?? null);
        $this->assertSame('Composition', $bundle['entry'][0]['resource']['resourceType'] ?? null);

        $resources = [];
        foreach ($bundle['entry'] as $entry) {
            $fullUrl = (string) ($entry['fullUrl'] ?? '');
            $this->assertMatchesRegularExpression('/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-a[0-9a-f]{3}-[0-9a-f]{12}$/', $fullUrl);
            $resources[(string) ($entry['resource']['resourceType'] ?? '')] = $entry['resource'];
        }

        $composition = $resources['Composition'];
        $invoice = $resources['Invoice'];
        $patient = $resources['Patient'];
        $organization = $resources['Organization'];
        $encounter = $resources['Encounter'];
        $practitioner = $resources['Practitioner'];
        $documentReference = $resources['DocumentReference'];
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/InvoiceRecord', $composition['meta']['profile'] ?? []);
        $this->assertSame('Invoice Record', $composition['type']['text'] ?? null);
        $this->assertSame('69705-9', $composition['type']['coding'][0]['code'] ?? null);
        $this->assertSame(
            'Invoice',
            $composition['section'][0]['entry'][0]['type'] ?? null,
            'NRCeS InvoiceRecord 6.5.0 requires Reference.type to be Invoice'
        );
        $this->assertCount(1, $composition['section'][0]['entry'] ?? []);
        $this->assertNotEmpty($composition['author'][0]['reference'] ?? '');
        $this->assertNotEmpty($composition['custodian']['reference'] ?? '');
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient', $patient['meta']['profile'] ?? []);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization', $organization['meta']['profile'] ?? []);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter', $encounter['meta']['profile'] ?? []);
        $this->assertSame('2026-08-14', $encounter['period']['start'] ?? null);
        $this->assertNotEmpty($encounter['serviceProvider']['reference'] ?? '');
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner', $practitioner['meta']['profile'] ?? []);
        $this->assertSame('MD', $practitioner['identifier'][0]['type']['coding'][0]['code'] ?? null);
        $this->assertSame('7', $practitioner['identifier'][0]['value'] ?? null);
        $this->assertSame('Dr Test', $practitioner['name'][0]['text'] ?? null);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/Invoice', $invoice['meta']['profile'] ?? []);
        $this->assertSame('Dr Test', $invoice['participant'][0]['actor']['display'] ?? null);
        $this->assertSame('03', $invoice['type']['coding'][0]['code'] ?? null);
        $this->assertSame('01', $invoice['lineItem'][0]['priceComponent'][0]['code']['coding'][0]['code'] ?? null);
        $this->assertArrayHasKey('chargeItemReference', $invoice['lineItem'][0]);
        $chargeItems = array_values(array_filter(
            $bundle['entry'],
            static fn (array $entry): bool => ($entry['resource']['resourceType'] ?? '') === 'ChargeItem'
        ));
        $this->assertCount(1, $chargeItems);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/ChargeItem', $chargeItems[0]['resource']['meta']['profile'] ?? []);
        $this->assertSame('OPD Consultation Fee', $chargeItems[0]['resource']['code']['text'] ?? null);
        $this->assertSame('OPD Consultation Fee', $chargeItems[0]['resource']['productCodeableConcept']['text'] ?? null);
        $this->assertSame(1.0, $chargeItems[0]['resource']['quantity']['value'] ?? null);
        $this->assertSame('unit', $chargeItems[0]['resource']['quantity']['unit'] ?? null);
        $this->assertSame(300.0, $invoice['totalNet']['value'] ?? null, 'NRCeS Invoice requires totalNet');
        $this->assertSame(350.0, $invoice['totalGross']['value'] ?? null, 'NRCeS Invoice requires totalGross');
        $this->assertSame(300.0, $invoice['totalPriceComponent'][0]['amount']['value'] ?? null);
        $this->assertContains(
            'https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference',
            $documentReference['meta']['profile'] ?? []
        );
        $attachment = $documentReference['content'][0]['attachment'] ?? [];
        $pdfBytes = base64_decode((string) ($attachment['data'] ?? ''), true);
        $this->assertIsString($pdfBytes);
        $this->assertSame('application/pdf', $attachment['contentType'] ?? null);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
        $this->assertStringNotContainsString('table.items{', $pdfBytes);
        $this->assertSame(strlen($pdfBytes), $attachment['size'] ?? null);
        $this->assertSame(base64_encode(sha1($pdfBytes, true)), $attachment['hash'] ?? null);
        $this->assertSame(
            $composition['section'][0]['entry'][0]['reference'] ?? null,
            $documentReference['context']['related'][0]['reference'] ?? null
        );

        $fullUrls = array_column($bundle['entry'], 'fullUrl');
        $encoded = json_encode($bundle, JSON_UNESCAPED_SLASHES);
        preg_match_all('/"reference":"(urn:uuid:[^"]+)"/', (string) $encoded, $matches);
        foreach ($matches[1] as $reference) {
            $this->assertContains($reference, $fullUrls, 'Unresolved invoice reference: ' . $reference);
        }
    }

    public function testImmunizationRecordHasM2DocumentStructureAndResolvableReferences(): void
    {
        $bundle = (new FhirR4Builder())->buildImmunizationRecordBundle([
            'id' => '91',
            'name' => 'Test Patient',
            'gender' => 'female',
            'birthDate' => '2020-01-10',
            'abhaAddress' => 'testpatient@sbx',
        ], [[
            'id' => '17',
            'vaccine_name' => 'BCG Vaccine',
            'vaccine_code_system' => 'https://hms.local/immunization/uip',
            'vaccine_code' => 'UIP-BCG',
            'given_date' => '2026-08-15',
            'status' => 'completed',
            'lot_number' => 'LOT-42',
            'dose_number' => '1',
            'series_name' => 'Infant Immunization Schedule',
            'target_disease_code' => '56717001',
            'target_disease_name' => 'Tuberculosis',
        ]], [
            'care_context_reference' => 'IMM-17-91-20260815',
            'practitioner' => [
                'id' => '7',
                'name' => 'Dr Test',
                'registration_number' => 'MED-7',
            ],
            'organization' => ['name' => 'Test Hospital', 'hfr_id' => 'IN0000000001'],
            'encounter' => [
                'id' => 'IMM-17',
                'status' => 'finished',
                'class_code' => 'AMB',
                'period_start' => '2026-08-15',
            ],
        ]);

        $this->assertSame('document', $bundle['type'] ?? null);
        $this->assertSame('IMM-17-91-20260815', $bundle['identifier']['value'] ?? null);
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle', $bundle['meta']['profile'] ?? []);

        $resources = [];
        $fullUrls = array_column($bundle['entry'], 'fullUrl');
        foreach ($bundle['entry'] as $entry) {
            $resource = $entry['resource'] ?? [];
            $resources[(string) ($resource['resourceType'] ?? '')] = $resource;
        }

        $composition = $resources['Composition'];
        $immunization = $resources['Immunization'];
        $this->assertContains('https://nrces.in/ndhm/fhir/r4/StructureDefinition/ImmunizationRecord', $composition['meta']['profile'] ?? []);
        $this->assertSame('41000179103', $composition['type']['coding'][0]['code'] ?? null);
        $this->assertSame('Dr Test', $composition['author'][0]['display'] ?? null);
        $this->assertSame('Test Hospital', $composition['custodian']['display'] ?? null);
        $this->assertSame('Immunization', $composition['section'][0]['entry'][0]['type'] ?? null);
        $this->assertSame('BCG Vaccine', $immunization['vaccineCode']['text'] ?? null);
        $this->assertSame('http://hl7.org/fhir/sid/cvx', $immunization['vaccineCode']['coding'][0]['system'] ?? null);
        $this->assertSame('19', $immunization['vaccineCode']['coding'][0]['code'] ?? null);
        $this->assertSame('2026-08-15T00:00:00+05:30', $immunization['occurrenceDateTime'] ?? null);
        $this->assertNotEmpty($immunization['performer'][0]['actor']['reference'] ?? '');
        $this->assertNotEmpty($immunization['encounter']['reference'] ?? '');
        $this->assertSame('Test Hospital', $immunization['location']['display'] ?? null);
        $this->assertSame('Infant Immunization Schedule', $immunization['protocolApplied'][0]['series'] ?? null);
        $this->assertSame(1, $immunization['protocolApplied'][0]['doseNumberPositiveInt'] ?? null);
        $this->assertSame('56717001', $immunization['protocolApplied'][0]['targetDisease'][0]['coding'][0]['code'] ?? null);

        $encoded = json_encode($bundle, JSON_UNESCAPED_SLASHES);
        preg_match_all('/"reference":"(urn:uuid:[^"]+)"/', (string) $encoded, $matches);
        foreach ($matches[1] as $reference) {
            $this->assertContains($reference, $fullUrls, 'Unresolved ImmunizationRecord reference: ' . $reference);
        }
    }

    public function testImmunizationBuilderFallbackReferenceIsStableOnRetry(): void
    {
        $builder = new FhirR4Builder();
        $patient = ['id' => '91', 'name' => 'Test Patient'];
        $first = $builder->buildImmunizationRecordBundle($patient, [[
            'id' => '17',
            'vaccine_name' => 'BCG Vaccine',
            'given_date' => '2026-08-10',
            'status' => 'completed',
        ]]);
        $retry = $builder->buildImmunizationRecordBundle($patient, [[
            'id' => '17',
            'vaccine_name' => 'BCG Vaccine',
            'given_date' => '2026-08-15',
            'status' => 'completed',
        ]]);

        $this->assertSame('IMM-17', $first['identifier']['value'] ?? null);
        $this->assertSame($first['identifier']['value'] ?? null, $retry['identifier']['value'] ?? null);
    }

    public function testLegacyNumericGenderMapsToFhirGender(): void
    {
        $controller = new AbdmGateway();
        $method = new ReflectionMethod($controller, 'normalizeFhirGender');

        $this->assertSame('male', $method->invoke($controller, '1'));
        $this->assertSame('female', $method->invoke($controller, '2'));
        $this->assertSame('other', $method->invoke($controller, '3'));
    }

    public function testBlankDischargeTemplateUsesDefaultHospitalHeader(): void
    {
        $controller = new Ipd_discharge();
        $blankMethod = new ReflectionMethod($controller, 'isDischargeHeaderBlank');
        $headerMethod = new ReflectionMethod($controller, 'buildDefaultDischargeHeader');

        $this->assertTrue($blankMethod->invoke($controller, '<div>&nbsp;</div>'));
        $this->assertFalse($blankMethod->invoke($controller, '<div>Custom Letterhead</div>'));

        $header = $headerMethod->invoke($controller, [
            'H_Name' => 'Test Hospital',
            'hospital_address' => 'Hospital Road',
            'H_phone_No' => '1234567890',
            'H_Email' => 'care@example.test',
            'H_logo_abs' => '',
        ]);
        $this->assertStringContainsString('Test Hospital', $header);
        $this->assertStringContainsString('Hospital Road', $header);
    }
}
