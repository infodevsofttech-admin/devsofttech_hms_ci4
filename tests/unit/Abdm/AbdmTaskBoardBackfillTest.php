<?php

use App\Controllers\AbdmTaskBoard;
use CodeIgniter\Test\CIUnitTestCase;

final class AbdmTaskBoardBackfillTest extends CIUnitTestCase
{
    public function testIsValidAbhaNumberAcceptsCleanAndFormattedAndAddress(): void
    {
        $controller = (new ReflectionClass(AbdmTaskBoard::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AbdmTaskBoard::class, 'isValidAbhaNumber');
        $method->setAccessible(true);

        // 14 digits clean
        $this->assertTrue($method->invoke($controller, '91747451787143'));
        // 14 digits with standard hyphens
        $this->assertTrue($method->invoke($controller, '91-7474-5178-7143'));
        // ABHA address
        $this->assertTrue($method->invoke($controller, 'singhkeshav301976@sbx'));
        $this->assertTrue($method->invoke($controller, 'patient@abdm'));

        // Invalid cases
        $this->assertFalse($method->invoke($controller, ''));
        $this->assertFalse($method->invoke($controller, '0'));
        $this->assertFalse($method->invoke($controller, '12345'));
        $this->assertFalse($method->invoke($controller, 'invalid_address'));
    }

    public function testEnrichTasksWithHealthRecordStateExtractsInvoiceAndCareContext(): void
    {
        $controller = (new ReflectionClass(AbdmTaskBoard::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AbdmTaskBoard::class, 'enrichTasksWithHealthRecordState');
        $method->setAccessible(true);

        $mockTasks = [
            [
                'id' => 686,
                'task_code' => 'ABDM-20261001151127-C8C055',
                'task_type' => 'radiology_report_publish',
                'entity_id' => '51450',
                'status' => 'completed',
                'payload_json' => json_encode([
                    'meta' => [
                        'lab_type' => 3,
                        'invoice_id' => 40291,
                        'invoice_code' => 'N26100040291',
                    ],
                ]),
                'last_action_result' => 'Linked by cron: REQ-20261001151202-7447b1c4',
            ],
            [
                'id' => 679,
                'task_code' => 'ABDM-20261001142918-25422B',
                'task_type' => 'radiology_report_publish',
                'entity_id' => '51450',
                'status' => 'completed',
                'payload_json' => json_encode([
                    'meta' => [
                        'invoice_id' => 40291,
                    ],
                ]),
                'last_action_result' => 'Care context already linked in ABDM: RAD-51450-20261001',
            ],
        ];

        $enriched = $method->invoke($controller, $mockTasks);

        $this->assertCount(2, $enriched);
        $this->assertSame(40291, $enriched[0]['invoice_id']);
        $this->assertSame('N26100040291', $enriched[0]['invoice_code']);
        $this->assertSame(40291, $enriched[1]['invoice_id']);
        // From last_action_result regex fallback
        $this->assertSame('RAD-51450-20261001', $enriched[1]['bridge_care_context_reference']);
    }

    public function testEnrichTasksWithHealthRecordStatePreventsCrossModuleCollision(): void
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('health_records')) {
            // Insert an IPD discharge health record with entity_id = 999 for patient 18
            $db->table('health_records')->insert([
                'id' => 9999,
                'patient_id' => 18,
                'entity_type' => 'ipd',
                'hi_type' => 'DischargeSummaryRecord',
                'entity_id' => '999',
                'push_status' => 'queued',
                'care_context_reference' => 'DISCHARGE-999-S999-2026',
            ]);

            $controller = (new ReflectionClass(AbdmTaskBoard::class))->newInstanceWithoutConstructor();
            $method = new ReflectionMethod(AbdmTaskBoard::class, 'enrichTasksWithHealthRecordState');
            $method->setAccessible(true);

            // Task is for patient 11 with entity_type = immunization and entity_id = 999
            $mockTasks = [
                [
                    'id' => 101,
                    'task_code' => 'ABDM-TEST-001',
                    'task_type' => 'immunization_record_publish',
                    'patient_id' => 11,
                    'entity_type' => 'immunization',
                    'entity_id' => '999',
                    'status' => 'pending',
                    'last_action_result' => '',
                ],
            ];

            $enriched = $method->invoke($controller, $mockTasks);

            // Should NOT match the IPD record for patient 18
            $this->assertSame(0, $enriched[0]['bridge_submitted']);
            $this->assertSame('', $enriched[0]['bridge_care_context_reference']);

            // Clean up
            $db->table('health_records')->where('id', 9999)->delete();
        } else {
            $this->assertTrue(true);
        }
    }

    public function testWorkTaskServiceHasGetTasksMethod(): void
    {
        $serviceClass = new ReflectionClass(\App\Libraries\AbdmWorkTaskService::class);
        $this->assertTrue($serviceClass->hasMethod('getTasks'));
        $this->assertTrue($serviceClass->hasMethod('getOpenTasks'));

        $getTasksMethod = $serviceClass->getMethod('getTasks');
        $params = $getTasksMethod->getParameters();
        $this->assertSame('status', $params[0]->getName());
        $this->assertSame('all', $params[0]->getDefaultValue());
    }

    public function testGetAbhaPatientsListReturnsNormalizedArray(): void
    {
        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('patient_master') . " (
            id INTEGER PRIMARY KEY,
            p_code TEXT,
            p_fname TEXT,
            p_lname TEXT,
            dob TEXT,
            age TEXT,
            gender INTEGER,
            abha_id TEXT,
            abha_address TEXT,
            abha_no TEXT,
            mphone1 TEXT
        )");

        $testPatientId = 998876;
        $db->table('patient_master')->where('id', $testPatientId)->delete();
        $db->table('patient_master')->insert([
            'id'           => $testPatientId,
            'p_code'       => 'P-TEST-998876',
            'p_fname'      => 'Janvi',
            'p_lname'      => 'Bisht',
            'mphone1'      => '9876543212',
            'gender'       => 2,
            'abha_address' => 'janvibisht2506@sbx',
            'abha_id'      => '91747451787144',
        ]);

        try {
            $controller = (new ReflectionClass(AbdmTaskBoard::class))->newInstanceWithoutConstructor();
            $dbProp = new ReflectionProperty(AbdmTaskBoard::class, 'db');
            $dbProp->setAccessible(true);
            $dbProp->setValue($controller, $db);

            $method = new ReflectionMethod(AbdmTaskBoard::class, 'getAbhaPatientsList');
            $method->setAccessible(true);

            $patients = $method->invoke($controller);
            $this->assertIsArray($patients);
            $this->assertNotEmpty($patients);

            $found = false;
            foreach ($patients as $p) {
                if ($p['id'] === $testPatientId) {
                    $this->assertSame('janvibisht2506@sbx', $p['abha_address']);
                    $this->assertSame('91747451787144', $p['abha_number']);
                    $this->assertSame('P-TEST-998876', $p['p_code']);
                    $this->assertSame('Janvi Bisht', $p['name']);
                    $this->assertSame('F', $p['gender']);
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Inserted test patient was not found in getAbhaPatientsList');
        } finally {
            $db->table('patient_master')->where('id', $testPatientId)->delete();
        }
    }

    public function testEnrichTasksWithHealthRecordStateAttachesPatientAbha(): void
    {
        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS " . $db->prefixTable('patient_master') . " (
            id INTEGER PRIMARY KEY,
            p_code TEXT,
            p_fname TEXT,
            p_lname TEXT,
            dob TEXT,
            age TEXT,
            gender INTEGER,
            abha_id TEXT,
            abha_address TEXT,
            abha_no TEXT,
            mphone1 TEXT
        )");

        $testPatientId = 998877;
        $db->table('patient_master')->where('id', $testPatientId)->delete();
        $db->table('patient_master')->insert([
            'id' => $testPatientId,
            'p_code' => 'P-TEST-998877',
            'p_fname' => 'Test',
            'p_lname' => 'AbhaPatient',
            'mphone1' => '9876543210',
            'gender' => 1,
            'abha_address' => 'testpatient998877@sbx',
            'abha_id' => '91747451787143',
        ]);

        try {
            $controller = (new ReflectionClass(AbdmTaskBoard::class))->newInstanceWithoutConstructor();
            $dbProp = new ReflectionProperty(AbdmTaskBoard::class, 'db');
            $dbProp->setAccessible(true);
            $dbProp->setValue($controller, $db);

            $method = new ReflectionMethod(AbdmTaskBoard::class, 'enrichTasksWithHealthRecordState');
            $method->setAccessible(true);

            $mockTasks = [
                [
                    'id' => 1,
                    'task_code' => 'ABDM-TEST-ENRICH-1',
                    'task_type' => 'opd_prescription_publish',
                    'patient_id' => $testPatientId,
                    'entity_id' => '100',
                    'status' => 'pending',
                    'abha_id' => '',
                ]
            ];

            $enriched = $method->invoke($controller, $mockTasks);
            $this->assertCount(1, $enriched);
            $this->assertSame('testpatient998877@sbx', $enriched[0]['patient_abha_address']);
            $this->assertSame('91747451787143', $enriched[0]['patient_abha_number']);
            $this->assertSame('P-TEST-998877', $enriched[0]['patient_p_code']);
            $this->assertSame('9876543210', $enriched[0]['patient_phone']);
        } finally {
            $db->table('patient_master')->where('id', $testPatientId)->delete();
        }
    }
}

