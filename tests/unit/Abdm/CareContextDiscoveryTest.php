<?php

namespace Tests\Unit\Abdm;

use App\Controllers\AbdmGateway;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

final class CareContextDiscoveryTest extends CIUnitTestCase
{
    private AbdmGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('lab_request') . " (
            id INTEGER PRIMARY KEY,
            patient_id INTEGER,
            patient_name TEXT,
            lab_type INTEGER,
            charge_id INTEGER,
            report_name TEXT,
            Report_Data TEXT,
            report_data_Impression TEXT,
            status INTEGER,
            Request_Date TEXT,
            reported_time TEXT,
            collected_time TEXT
        )");

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('charge_master') . " (
            id INTEGER PRIMARY KEY,
            charge_name TEXT,
            charge_type TEXT
        )");

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('opd_prescription') . " (
            id INTEGER PRIMARY KEY,
            p_id INTEGER,
            date_opd_visit TEXT,
            session_id INTEGER,
            p_datetime TEXT
        )");

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('opd_master') . " (
            opd_id INTEGER PRIMARY KEY,
            p_id INTEGER,
            apointment_date TEXT,
            opd_book_date TEXT,
            doc_name TEXT
        )");

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('health_records') . " (
            id INTEGER PRIMARY KEY,
            patient_id INTEGER,
            abha_id TEXT,
            hi_type TEXT,
            entity_type TEXT,
            entity_id TEXT,
            fhir_bundle TEXT,
            care_context_reference TEXT,
            consent_handle TEXT,
            push_status TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('patient_master') . " (
            id INTEGER PRIMARY KEY,
            p_code TEXT,
            p_fname TEXT,
            dob TEXT,
            age TEXT,
            gender INTEGER,
            abha_id TEXT,
            abha_address TEXT,
            mphone1 TEXT
        )");

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('ipd_master') . " (
            id INTEGER PRIMARY KEY,
            p_id INTEGER,
            discharge_date TEXT,
            register_date TEXT,
            ipd_status INTEGER
        )");

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('immunization_records') . " (
            id INTEGER PRIMARY KEY,
            patient_id INTEGER,
            vaccine_name TEXT,
            given_date TEXT,
            abdm_care_context_reference TEXT
        )");

        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('invoice_master') . " (
            id INTEGER PRIMARY KEY,
            invoice_code TEXT,
            attach_id INTEGER,
            attach_type INTEGER,
            inv_date TEXT,
            net_amount REAL
        )");

        $db->resetDataCache();

        $db->table('charge_master')->emptyTable();
        $db->table('lab_request')->emptyTable();
        $db->table('opd_prescription')->emptyTable();
        $db->table('opd_master')->emptyTable();
        $db->table('health_records')->emptyTable();
        $db->table('patient_master')->emptyTable();
        $db->table('ipd_master')->emptyTable();
        $db->table('immunization_records')->emptyTable();
        $db->table('invoice_master')->emptyTable();

        $db->table('patient_master')->insert([
            'id' => 12,
            'p_code' => 'P26071000012',
            'p_fname' => 'KESHAV SINGH',
            'dob' => '1976-06-30',
            'age' => null,
            'gender' => 1,
            'abha_id' => '91747451787143',
            'abha_address' => 'singhkeshav301976@sbx',
            'mphone1' => '7817828379',
        ]);

        $db->table('charge_master')->insert([
            'id' => 19,
            'charge_name' => 'USG WHOLE ABDOMEN',
        ]);

        $db->table('lab_request')->insert([
            'id' => 31,
            'patient_id' => 12,
            'lab_type' => 6,
            'charge_id' => 19,
            'report_name' => 'USG WHOLE ABDOMEN',
            'Report_Data' => 'Liver is normal. Gall bladder normal.',
            'report_data_Impression' => 'Normal study.',
            'status' => 1,
            'Request_Date' => '2026-09-29',
            'reported_time' => '2026-09-29 11:00:00',
        ]);

        $db->table('opd_prescription')->insert([
            'id' => 101,
            'p_id' => 12,
            'date_opd_visit' => '2026-09-28',
            'session_id' => 50,
            'p_datetime' => '2026-09-28 10:00:00',
        ]);

        $this->gateway = new AbdmGateway();
        $this->gateway->initController(
            \Config\Services::request(),
            \Config\Services::response(),
            \Config\Services::logger()
        );
    }

    public function testCareContextsDiscoveryWithTargetTask(): void
    {
        $reflector = new ReflectionClass($this->gateway);
        $method = $reflector->getMethod('findCareContextsForPatient');
        $method->setAccessible(true);

        // Test with patient 12 and task_type='radiology_report_publish', entity_id='31'
        [$v3List, $fullList] = $method->invoke(
            $this->gateway,
            12,
            'P-12',
            'KESHAV SINGH',
            'radiology_report_publish',
            '31',
            591
        );

        $this->assertNotEmpty($fullList, 'Care contexts list should not be empty for patient 12');

        // Verify the primary context is the radiology report #31
        $primaryCtx = $fullList[0];
        $this->assertTrue($primaryCtx['is_primary'] ?? false, 'First care context should be marked as primary');
        $this->assertSame('DiagnosticReportRecord', $primaryCtx['record_type']);
        $this->assertStringStartsWith('RAD-31-', $primaryCtx['careContextId']);
        $this->assertTrue($primaryCtx['is_fhir_ready']);

        // Verify that v3List also contains referenceNumber
        $this->assertSame($primaryCtx['careContextId'], $v3List[0]['referenceNumber']);

        // Verify secondary contexts (e.g. OPD prescription) are also discovered
        $hasOpd = false;
        foreach ($fullList as $ctx) {
            if ($ctx['record_type'] === 'OPConsultRecord') {
                $hasOpd = true;
                $this->assertFalse($ctx['is_primary']);
            }
        }
        $this->assertTrue($hasOpd, 'Patient OPD consultations should also be included in care contexts');
    }

    public function testCareContextsDiscoveryDraftStatus(): void
    {
        $db = \Config\Database::connect();
        // Insert incomplete lab request (status = 0 and empty findings)
        $db->table('lab_request')->insert([
            'id' => 99,
            'patient_id' => 12,
            'lab_type' => 1,
            'charge_id' => 0,
            'report_name' => 'Draft Blood Test',
            'Report_Data' => '',
            'report_data_Impression' => '',
            'status' => 0,
            'Request_Date' => '2026-09-29',
        ]);

        $reflector = new ReflectionClass($this->gateway);
        $method = $reflector->getMethod('findCareContextsForPatient');
        $method->setAccessible(true);

        [$v3List, $fullList] = $method->invoke(
            $this->gateway,
            12,
            'P-12',
            'KESHAV SINGH'
        );

        $draftCtx = null;
        foreach ($fullList as $ctx) {
            if (str_starts_with($ctx['careContextId'], 'LAB-99-')) {
                $draftCtx = $ctx;
                break;
            }
        }

        $this->assertNotNull($draftCtx);
        $this->assertFalse($draftCtx['is_fhir_ready'], 'Incomplete lab report should not be marked FHIR ready');
    }

    public function testHipPatientCareContextsEndpoint(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $_REQUEST['patient_id'] = '12';
        $_REQUEST['abha_address'] = '91747451787143';
        $_REQUEST['task_type'] = 'radiology_report_publish';
        $_REQUEST['entity_id'] = '31';
        $_REQUEST['task_id'] = '591';

        $req = service('request');
        $req->setHeader('X-Requested-With', 'XMLHttpRequest');
        $this->gateway->initController($req, service('response'), service('logger'));

        $res = $this->gateway->hipPatientCareContexts();
        $body = json_decode($res->getBody(), true);

        $this->assertSame(1, $body['ok'] ?? 0);
        $this->assertNotEmpty($body['care_contexts'] ?? []);
    }

    public function testCareContextsDiscoveryWithIpdAndImmunization(): void
    {
        $db = \Config\Database::connect();
        $db->table('ipd_master')->insert([
            'id' => 55,
            'p_id' => 12,
            'discharge_date' => '2026-09-20',
            'register_date' => '2026-09-15',
            'ipd_status' => 1,
        ]);
        $db->table('immunization_records')->insert([
            'id' => 77,
            'patient_id' => 12,
            'vaccine_name' => 'Covaxin',
            'given_date' => '2026-09-18',
            'abdm_care_context_reference' => 'IMM-77-20260918',
        ]);

        $reflector = new ReflectionClass($this->gateway);
        $method = $reflector->getMethod('findCareContextsForPatient');
        $method->setAccessible(true);

        [$v3List, $fullList] = $method->invoke(
            $this->gateway,
            12,
            'P-12',
            'KESHAV SINGH'
        );

        $foundIpd = false;
        $foundImm = false;
        foreach ($fullList as $ctx) {
            if ($ctx['record_type'] === 'DischargeSummaryRecord') {
                $foundIpd = true;
                $this->assertStringContainsString('DISCHARGE-55-', $ctx['careContextId']);
            }
            if ($ctx['record_type'] === 'ImmunizationRecord') {
                $foundImm = true;
                $this->assertSame('IMM-77-20260918', $ctx['careContextId']);
            }
        }

        $this->assertTrue($foundIpd, 'IPD DischargeSummaryRecord should be discovered');
        $this->assertTrue($foundImm, 'ImmunizationRecord should be discovered');
    }

    public function testImmunizationTaskReturnsPrimaryCareContextInDiscovery(): void
    {
        $db = \Config\Database::connect();
        $db->table('immunization_records')->insert([
            'id' => 11,
            'patient_id' => 11,
            'vaccine_name' => 'PCV',
            'given_date' => '2026-10-06 01:54:00',
            'abdm_care_context_reference' => 'IMM-11',
        ]);

        $reflector = new ReflectionClass($this->gateway);
        $method = $reflector->getMethod('findCareContextsForPatient');
        $method->setAccessible(true);

        [$v3List, $fullList] = $method->invoke(
            $this->gateway,
            11,
            'P-11',
            'DEVENDER SINGH',
            'immunization_record_publish',
            '11'
        );

        $this->assertNotEmpty($fullList);
        $primary = $fullList[0];
        $this->assertSame('IMM-11', $primary['careContextId']);
        $this->assertSame('ImmunizationRecord', $primary['record_type']);
        $this->assertTrue($primary['is_primary']);
        $this->assertTrue($primary['is_fhir_ready']);
        $this->assertStringContainsString('PCV', $primary['display']);
    }

    public function testShareDiagnosisReportBundleFormatsRadiologyCareContext(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $_POST['lab_req_id'] = '31';
        $_POST['patient_id'] = '12';
        $_POST['abha_id'] = 'singhkeshav301976@sbx';
        $_POST['care_context_reference'] = 'RAD-31-20260929';

        $req = service('request');
        $req->setHeader('X-Requested-With', 'XMLHttpRequest');
        $this->gateway->initController($req, service('response'), service('logger'));

        $mockConnector = $this->createMock(\App\Libraries\Abdm\AbdmConnectorInterface::class);
        $mockConnector->method('pushRecord')
            ->willReturnCallback(function (array $data) {
                return [
                    'ok' => 1,
                    'http_code' => 201,
                    'status' => 'queued',
                    'queue_id' => $data['care_context_reference'],
                    'record_id' => 999,
                ];
            });

        $refGateway = new ReflectionClass($this->gateway);
        $prop = $refGateway->getProperty('connector');
        $prop->setAccessible(true);
        $prop->setValue($this->gateway, $mockConnector);

        $res = $this->gateway->shareDiagnosisReportBundle();
        $body = json_decode($res->getBody(), true);

        $this->assertSame(1, $body['ok'] ?? 0);
        $this->assertSame('RAD-31-20260929', $body['care_context_reference'] ?? '');
    }

    public function testDiscoversOpdMasterVisitWhenPrescriptionNotCreated(): void
    {
        $db = \Config\Database::connect();
        $db->table('opd_master')->insert([
            'opd_id' => 77,
            'p_id' => 12,
            'apointment_date' => '2026-09-29 14:30:00',
            'opd_book_date' => '2026-09-29 14:00:00',
            'doc_name' => 'NIDHI PANDEY',
        ]);

        $refGateway = new ReflectionClass($this->gateway);
        $method = $refGateway->getMethod('findCareContextsForPatient');
        $method->setAccessible(true);

        [$v3, $full] = $method->invoke($this->gateway, 12, 'P26071000012', 'KESHAV SINGH');

        $refs = array_column($v3, 'referenceNumber');
        $this->assertContains('OPD-12-S77-20260929', $refs);
    }

    public function testDiscoversInvoiceCareContextWhenInvoiceTaskRequested(): void
    {
        $db = \Config\Database::connect();
        $db->table('invoice_master')->emptyTable();
        $db->table('invoice_master')->insert([
            'id' => 88,
            'invoice_code' => 'INV-88',
            'attach_id' => 12,
            'attach_type' => 0,
            'inv_date' => '2026-09-30',
            'net_amount' => 450.00,
        ]);

        $refGateway = new ReflectionClass($this->gateway);
        $method = $refGateway->getMethod('findCareContextsForPatient');
        $method->setAccessible(true);

        [$v3, $full] = $method->invoke(
            $this->gateway,
            12,
            'P26071000012',
            'KESHAV SINGH',
            'invoice_publish',
            '88',
            0,
            'charges_invoice'
        );

        $refs = array_column($v3, 'referenceNumber');
        $this->assertContains('INVOICE-CHG-88-2026-09-30', $refs);

        $invoiceCtx = null;
        foreach ($full as $ctx) {
            if ($ctx['careContextId'] === 'INVOICE-CHG-88-2026-09-30') {
                $invoiceCtx = $ctx;
                break;
            }
        }
        $this->assertNotNull($invoiceCtx);
        $this->assertSame('InvoiceRecord', $invoiceCtx['record_type']);
        $this->assertTrue($invoiceCtx['is_primary']);
        $this->assertTrue($invoiceCtx['is_fhir_ready']);
    }
}

