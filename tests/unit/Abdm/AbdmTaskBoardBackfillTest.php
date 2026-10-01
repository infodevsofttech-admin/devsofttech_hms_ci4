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
}
