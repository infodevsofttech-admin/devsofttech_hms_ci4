<?php

use App\Libraries\Abdm\Sync\AbdmGatewayPushClient;
use App\Libraries\Abdm\EAtriaBridgeConnector;
use CodeIgniter\Test\CIUnitTestCase;

final class AbdmGatewayPushClientTest extends CIUnitTestCase
{
    public function testValidationFailsWhenBundleTypeNotDocument(): void
    {
        $client = new AbdmGatewayPushClient();
        $validation = $client->validatePayload([
            'hfr_id' => 'IN0510000870',
            'hi_type' => 'OPConsultRecord',
            'care_context_reference' => 'OPD-1-S1-2026-06-27',
            'fhir_bundle' => [
                'resourceType' => 'Bundle',
                'type' => 'collection',
            ],
        ]);

        $this->assertFalse((bool) ($validation['valid'] ?? true));
        $this->assertNotEmpty($validation['errors']);
    }

    public function testValidationAllowsValidShape(): void
    {
        $client = new AbdmGatewayPushClient();
        $validation = $client->validatePayload([
            'hfr_id' => 'IN0510000870',
            'hi_type' => 'OPConsultRecord',
            'care_context_reference' => 'OPD-1-S1-2026-06-27',
            'fhir_bundle' => [
                'resourceType' => 'Bundle',
                'type' => 'document',
            ],
        ]);

        $this->assertTrue((bool) ($validation['valid'] ?? false));
    }

    public function testDuplicateRecordConflictIsNormalizedAsAcceptedSuccess(): void
    {
        $method = new ReflectionMethod(AbdmGatewayPushClient::class, 'mapResponse');
        $client = (new ReflectionClass(AbdmGatewayPushClient::class))->newInstanceWithoutConstructor();
        $result = $method->invoke($client, 409, [
            'error' => 'DUPLICATE_RECORD',
            'existing_record_id' => 91,
            'existing_queue_id' => 'QUEUE-OLD',
            'first_pushed_at' => '2026-08-10T09:00:00+05:30',
        ], 'request-1');

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['duplicate']);
        $this->assertSame(91, $result['gateway_record_id']);
        $this->assertSame('QUEUE-OLD', $result['gateway_queue_id']);
        $this->assertSame('2026-08-10T09:00:00+05:30', $result['first_pushed_at']);
    }

    public function testCreatedRecordIsNormalizedAsSuccess(): void
    {
        $method = new ReflectionMethod(AbdmGatewayPushClient::class, 'mapResponse');
        $client = (new ReflectionClass(AbdmGatewayPushClient::class))->newInstanceWithoutConstructor();
        $result = $method->invoke($client, 201, [
            'record_id' => 92,
            'queue_id' => 'QUEUE-NEW',
        ], 'request-created');

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['submitted']);
        $this->assertSame(92, $result['gateway_record_id']);
    }

    public function testGenericConflictRemainsFailure(): void
    {
        $method = new ReflectionMethod(AbdmGatewayPushClient::class, 'mapResponse');
        $client = (new ReflectionClass(AbdmGatewayPushClient::class))->newInstanceWithoutConstructor();
        $result = $method->invoke($client, 409, ['error_code' => 'CONFLICT'], 'request-2');

        $this->assertFalse($result['ok']);
        $this->assertSame('failed', $result['status']);
    }

    public function testDirectConnectorNormalizesDuplicateMetadataWithoutHttpCall(): void
    {
        $method = new ReflectionMethod(EAtriaBridgeConnector::class, 'normalizePushRecordResponse');
        $result = $method->invoke(null, [
            'http_code' => 409,
            'error' => ['code' => 'DUPLICATE_RECORD'],
            'data' => [
                'existing_record_id' => 77,
                'existing_queue_id' => 'QUEUE-77',
                'first_pushed_at' => '2026-08-09T08:00:00+05:30',
            ],
        ]);

        $this->assertSame(1, $result['ok']);
        $this->assertSame(77, $result['record_id']);
        $this->assertSame('QUEUE-77', $result['queue_id']);
        $this->assertSame('2026-08-09T08:00:00+05:30', $result['first_pushed_at']);
    }

    public function testValidationFailureIncludesGatewayFieldErrors(): void
    {
        $method = new ReflectionMethod(EAtriaBridgeConnector::class, 'normalizePushRecordResponse');
        $result = $method->invoke(null, [
            'http_code' => 422,
            'message' => 'FHIR validation failed',
            'errors' => [['field' => 'fhir_bundle.entry', 'message' => 'must not be empty']],
        ]);

        $this->assertSame(0, $result['ok']);
        $this->assertStringContainsString('fhir_bundle.entry must not be empty', $result['error_text']);
    }
}
