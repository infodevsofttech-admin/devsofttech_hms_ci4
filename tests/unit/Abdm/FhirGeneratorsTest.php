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

    public function testDischargeRecordWithDualVitalsAdviceAndMultiplePdfs(): void
    {
        $src = [
            'record_id' => 3208,
            'bundle_identifier' => 'discharge-A26090003208',
            'session_id' => 3208,
            'visit_date' => '2026-09-04',
            'completed_at' => '2026-09-04T04:58:00+05:30',
            'patient' => [
                'id' => 15355,
                'uhid' => 'P26091015355',
                'name' => 'JANVI BISHT',
                'gender' => 'female',
                'dob' => '2013-05-10',
                'abha_id' => '91407564383062',
                'abha_address' => 'janvibisht@sbx',
            ],
            'encounter' => [
                'id' => 'A26090003208',
                'class_code' => 'IMP',
                'start' => '2026-09-01T04:53:00+05:30',
                'end' => '2026-09-04T04:58:00+05:30',
            ],
            'doctor' => ['id' => 9, 'name' => 'Dr. Sanjay Kumar', 'registration_number' => 'DOC12345'],
            'organization' => ['id' => 'IN0510000000', 'name' => 'DevSoft Tech'],
            'chief_complaints' => [
                ['text' => 'Abdominal Pain'],
                ['text' => 'Breathlessness'],
                ['text' => 'Cough'],
                ['text' => 'Fever'],
            ],
            'diagnoses' => [['text' => 'VIRAL FEVER']],
            'procedures' => [['text' => 'Biphosphonate prophylaxis', 'performed_at' => '2026-09-03T00:00:00+05:30']],
            'medications' => [
                [
                    'name' => 'TAB PANTAKOOL DSR',
                    'dosage' => 'BF (BEFORE FOOD) | OD | भोजन से पहले | दिन में एक बार (OD)',
                ],
                [
                    'name' => 'TAB AZIK-500',
                    'dosage' => 'AF (AFTER FOOD) | BD | भोजन के बाद | दिन में दो बार (BD)',
                ],
            ],
            'observations' => [
                ['text' => 'Pulse /min', 'value' => '30/min', 'category' => 'Condition on Admission Time', 'effective_at' => '2026-09-01T04:53:00+05:30'],
                ['text' => 'Respiration /min', 'value' => '120/min', 'category' => 'Condition on Admission Time', 'effective_at' => '2026-09-01T04:53:00+05:30'],
                ['text' => 'BP mmHg', 'value' => '80mmHg', 'category' => 'Condition on Admission Time', 'effective_at' => '2026-09-01T04:53:00+05:30'],
                ['text' => 'Pallor', 'value' => 'Negative', 'category' => 'Condition on Admission Time', 'effective_at' => '2026-09-01T04:53:00+05:30'],
                ['text' => 'SPO2', 'value' => '95', 'unit' => '%', 'category' => 'Condition on Admission Time', 'effective_at' => '2026-09-01T04:53:00+05:30'],
                ['text' => 'RBS', 'value' => '95', 'unit' => 'mg/dL', 'category' => 'Condition on Admission Time', 'effective_at' => '2026-09-01T04:53:00+05:30'],
                ['text' => 'Temp F', 'value' => '99', 'unit' => 'degF', 'category' => 'Condition on Admission Time', 'effective_at' => '2026-09-01T04:53:00+05:30'],

                ['text' => 'Pulse /min', 'value' => '30/min', 'category' => 'Condition on Discharge Time', 'effective_at' => '2026-09-04T04:58:00+05:30'],
                ['text' => 'Respiration /min', 'value' => '110/min', 'category' => 'Condition on Discharge Time', 'effective_at' => '2026-09-04T04:58:00+05:30'],
                ['text' => 'BP mmHg', 'value' => '90mmHg', 'category' => 'Condition on Discharge Time', 'effective_at' => '2026-09-04T04:58:00+05:30'],
                ['text' => 'Pallor', 'value' => 'Negative', 'category' => 'Condition on Discharge Time', 'effective_at' => '2026-09-04T04:58:00+05:30'],
                ['text' => 'SPO2', 'value' => '95', 'unit' => '%', 'category' => 'Condition on Discharge Time', 'effective_at' => '2026-09-04T04:58:00+05:30'],
                ['text' => 'RBS', 'value' => '105', 'unit' => 'mg/dL', 'category' => 'Condition on Discharge Time', 'effective_at' => '2026-09-04T04:58:00+05:30'],
                ['text' => 'Temp F', 'value' => '97', 'unit' => 'degF', 'category' => 'Condition on Discharge Time', 'effective_at' => '2026-09-04T04:58:00+05:30'],
            ],
            'investigations' => [
                ['text' => 'Hb %: 11', 'loinc_code' => '718-7'],
            ],
            'care_plans' => [
                ['title' => 'Discharge Advice', 'description' => 'To Continue the other medication if any systmic condition like hypertension , diabetic etc, as per adivised by treating physician'],
                ['title' => 'Dietary Advice', 'description' => "1. Eat Nutritious diet,Stay away from smoking and alcohol altogether.\n2. Balanced Meals\n3. Fruits and Vegetables\n4. Avoid Processed Foods"],
                ['title' => 'Follow Up', 'description' => 'Review After: 5 Days (09-09-2026) / as and when required'],
            ],
            'documents' => [
                [
                    'title' => 'IPD Discharge Summary',
                    'content_type' => 'application/pdf',
                    'data' => base64_encode('%PDF-discharge-summary'),
                    'snomed_code' => '373942005',
                    'loinc_code' => '18842-5',
                    'created_at' => '2026-09-04T04:58:00+05:30',
                ],
                [
                    'title' => 'IPD Bill / Invoice',
                    'content_type' => 'application/pdf',
                    'data' => base64_encode('%PDF-ipd-bill'),
                    'snomed_code' => '823651000000106',
                    'loinc_code' => '75490-3',
                    'created_at' => '2026-09-04T04:58:00+05:30',
                ],
            ],
        ];

        $output = $this->factory->discharge()->generate($src);
        $bundle = $output['fhir_bundle'];
        $composition = $bundle['entry'][0]['resource'];

        // Verify sections: should contain both General Examination on Admission and Examination on Discharge
        $sectionTitles = array_column($composition['section'], 'title');
        $this->assertContains('General Examination on Admission', $sectionTitles);
        $this->assertContains('Examination on Discharge', $sectionTitles);
        $this->assertContains('Care plan', $sectionTitles);
        $this->assertContains('Document reference', $sectionTitles);

        // Verify MedicationRequests have dosageInstruction text
        $medRequests = array_values(array_filter($bundle['entry'], static fn (array $e): bool => ($e['resource']['resourceType'] ?? '') === 'MedicationRequest'));
        $this->assertCount(2, $medRequests);
        $this->assertSame('BF (BEFORE FOOD) | OD | भोजन से पहले | दिन में एक बार (OD)', $medRequests[0]['resource']['dosageInstruction'][0]['text']);
        $this->assertSame('AF (AFTER FOOD) | BD | भोजन के बाद | दिन में दो बार (BD)', $medRequests[1]['resource']['dosageInstruction'][0]['text']);

        // Verify CarePlans
        $carePlans = array_values(array_filter($bundle['entry'], static fn (array $e): bool => ($e['resource']['resourceType'] ?? '') === 'CarePlan'));
        $this->assertCount(3, $carePlans);
        $carePlanTitles = array_column(array_column($carePlans, 'resource'), 'title');
        $this->assertContains('Discharge Advice', $carePlanTitles);
        $this->assertContains('Dietary Advice', $carePlanTitles);
        $this->assertContains('Follow Up', $carePlanTitles);

        // Verify DocumentReferences (both Discharge Summary and Invoice)
        $docRefs = array_values(array_filter($bundle['entry'], static fn (array $e): bool => ($e['resource']['resourceType'] ?? '') === 'DocumentReference'));
        $this->assertCount(2, $docRefs);
        $this->assertSame('IPD Discharge Summary', $docRefs[0]['resource']['description']);
        $this->assertSame('IPD Bill / Invoice', $docRefs[1]['resource']['description']);
        $this->assertSame('823651000000106', $docRefs[1]['resource']['type']['coding'][0]['code']);

        // Verify Observation Categories on Admission vs Discharge
        $observations = array_values(array_filter($bundle['entry'], static fn (array $e): bool => ($e['resource']['resourceType'] ?? '') === 'Observation' && isset($e['resource']['category'])));
        $this->assertGreaterThanOrEqual(14, count($observations));
        $this->assertSame('General Examination on Admission', $observations[0]['resource']['category'][0]['text']);
        $this->assertSame('Examination on Discharge', $observations[7]['resource']['category'][0]['text']);

        // Verify all references resolve
        $fullUrls = array_column($bundle['entry'], 'fullUrl');
        $encoded = json_encode($bundle, JSON_UNESCAPED_SLASHES);
        preg_match_all('/"reference":"(urn:uuid:[^"]+)"/', (string) $encoded, $matches);
        foreach ($matches[1] as $reference) {
            $this->assertContains($reference, $fullUrls, 'Unresolved reference: ' . $reference);
        }
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
        $resourcesByType = [];
        $fullUrlByResourceType = [];
        foreach ($bundle['entry'] as $entry) {
            $fullUrl = (string) ($entry['fullUrl'] ?? '');
            $this->assertMatchesRegularExpression('/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-a[0-9a-f]{3}-[0-9a-f]{12}$/', $fullUrl);
            $resourceType = (string) ($entry['resource']['resourceType'] ?? '');
            $resources[$resourceType] = $entry['resource'];
            $resourcesByType[$resourceType][] = $entry['resource'];
            $fullUrlByResourceType[$resourceType][] = $fullUrl;
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
        $this->assertSame('34775-2', $composition['type']['coding'][0]['code'] ?? null);
        $this->assertSame('Patient/patient-11', $composition['subject']['reference'] ?? null);
        $this->assertSame('Organization/organization-invoice-issuer', $composition['author'][0]['reference'] ?? null);
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
        $this->assertArrayNotHasKey('url', $attachment);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
        $this->assertStringNotContainsString('table.items{', $pdfBytes);
        $this->assertSame(strlen($pdfBytes), $attachment['size'] ?? null);
        $this->assertSame(base64_encode(sha1($pdfBytes, true)), $attachment['hash'] ?? null);
        $this->assertSame(
            $composition['section'][0]['entry'][0]['reference'] ?? null,
            $documentReference['context']['related'][0]['reference'] ?? null
        );
        $this->assertSame('DocumentReference', $composition['section'][1]['entry'][0]['type'] ?? null);
        $this->assertSame($fullUrlByResourceType['DocumentReference'][0] ?? null, $composition['section'][1]['entry'][0]['reference'] ?? null, 'Document Reference section must point to the DocumentReference');
        $this->assertSame('725458005', $documentReference['type']['coding'][0]['code'] ?? null);
        $this->assertSame('Receipt', $documentReference['type']['coding'][0]['display'] ?? null);
        $this->assertSame('725458005', $composition['section'][1]['code']['coding'][0]['code'] ?? null);
        $this->assertSame('Receipt', $composition['section'][1]['code']['coding'][0]['display'] ?? null);
        $binaries = array_values(array_filter(
            $bundle['entry'],
            static fn (array $entry): bool => ($entry['resource']['resourceType'] ?? '') === 'Binary'
        ));
        $this->assertCount(1, $binaries);
        $this->assertSame('Binary', $composition['section'][2]['entry'][0]['type'] ?? null);
        $this->assertSame('Binary/invoice-19-pdf-content', $composition['section'][2]['entry'][0]['reference'] ?? null);
        $this->assertSame('application/pdf', $binaries[0]['resource']['contentType'] ?? null);
        $this->assertSame($attachment['data'] ?? null, $binaries[0]['resource']['data'] ?? null);

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

    public function testHealthDocumentGeneratorCreatesValidBundle(): void
    {
        $generator = $this->factory->healthDocument();
        $output = $generator->generate([
            'record_id' => '4',
            'visit_date' => '2026-08-26',
            'completed_at' => '2026-08-26T10:00:00+05:30',
            'document_title' => 'Fitness Certificate',
            'document_content_html' => '<p>Fit to resume work</p>',
            'patient' => [
                'id' => '11',
                'uhid' => 'P26061000011',
                'name' => 'Test Patient',
                'gender' => 'male',
                'dob' => '1990-01-01',
                'abha_id' => '91510165305101',
            ],
            'organization' => [
                'id' => 'IN0100000001',
                'name' => 'Test Hospital',
            ],
        ]);

        $this->assertSame('HealthDocumentRecord', $output['hi_type']);
        $this->assertStringStartsWith('DOC-4-2026-08-26', $output['care_context_reference']);
        $this->assertTrue($output['validation']['valid']);
        $this->assertSame(100, $output['validation']['score']);

        $bundle = $output['fhir_bundle'];
        $resourceTypes = array_column(array_column($bundle['entry'], 'resource'), 'resourceType');
        $this->assertContains('Composition', $resourceTypes);
        $this->assertContains('Patient', $resourceTypes);
        $this->assertContains('DocumentReference', $resourceTypes);
    }

    public function testHealthDocumentGeneratorSupportsCustomContentType(): void
    {
        $generator = $this->factory->healthDocument();
        $output = $generator->generate([
            'record_id' => '5',
            'visit_date' => '2026-08-31',
            'document_title' => 'Scanned X-Ray',
            'document_data_base64' => base64_encode('fake image data'),
            'content_type' => 'image/png',
            'patient' => [
                'id' => '11',
                'name' => 'DEVENDER SINGH',
                'abha_id' => '91510165305101',
            ],
        ]);

        $this->assertSame('HealthDocumentRecord', $output['hi_type']);
        $bundle = $output['fhir_bundle'];
        $this->assertSame('Bundle', $bundle['resourceType']);
        
        $docRef = null;
        foreach ($bundle['entry'] as $entry) {
            if (($entry['resource']['resourceType'] ?? '') === 'DocumentReference') {
                $docRef = $entry['resource'];
                break;
            }
        }
        $this->assertNotNull($docRef);
        $this->assertSame('application/pdf', $docRef['content'][0]['attachment']['contentType']);
    }

    public function testWellnessRecordGeneratorCreatesFullSections(): void
    {
        $generator = $this->factory->wellness();
        $output = $generator->generate([
            'record_id' => '101',
            'session_id' => '2',
            'visit_date' => '2026-09-01',
            'completed_at' => date(DATE_ATOM),
            'vitals' => [
                ['loinc_code' => '8480-6', 'display' => 'Systolic blood pressure', 'value' => 120, 'unit' => 'mmHg', 'ucum_code' => 'mm[Hg]'],
                ['loinc_code' => '8462-4', 'display' => 'Diastolic blood pressure', 'value' => 80, 'unit' => 'mmHg', 'ucum_code' => 'mm[Hg]'],
                ['loinc_code' => '8867-4', 'display' => 'Heart rate', 'value' => 72, 'unit' => '/min', 'ucum_code' => '/min'],
                ['loinc_code' => '8310-5', 'display' => 'Body temperature', 'value' => 36.8, 'unit' => 'Cel', 'ucum_code' => 'Cel'],
                ['loinc_code' => '59408-5', 'display' => 'Oxygen saturation', 'value' => 98, 'unit' => '%', 'ucum_code' => '%'],
            ],
            'physical_examination' => [
                'Complaints: Mild headache for 2 days',
                'Diagnosis: Essential hypertension (mild)',
            ],
            'women_wellness' => [
                'lmp' => '2026-08-15',
                'gravida' => '2',
                'para' => '1',
            ],
            'advice' => [
                'Smoking status: No',
                'Diet & Lifestyle Advice: Low salt diet, 30 min daily walk',
            ],
            'patient' => [
                'id' => '11',
                'name' => 'SUNITA DEVI',
                'abha_id' => '91510165305101',
                'gender' => 'female',
            ],
        ]);

        $this->assertSame('WellnessRecord', $output['hi_type']);
        $bundle = $output['fhir_bundle'];
        $this->assertSame('Bundle', $bundle['resourceType']);
        
        $resourceTypes = array_column(array_column($bundle['entry'], 'resource'), 'resourceType');
        $this->assertContains('Composition', $resourceTypes);
        $this->assertContains('Patient', $resourceTypes);
        $this->assertContains('Observation', $resourceTypes);

        $composition = $bundle['entry'][0]['resource'];
        $this->assertSame('https://nrces.in/ndhm/fhir/r4/StructureDefinition/WellnessRecord', $composition['meta']['profile'][0]);
        $this->assertGreaterThanOrEqual(1, count($composition['section']));
        $this->assertNotEmpty($composition['section'][0]['entry']);
    }
}
