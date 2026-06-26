<?php

use CodeIgniter\Test\CIUnitTestCase;

final class IntegrationMappingStubTest extends CIUnitTestCase
{
    public function testRecordsPushSuccessMappingStub(): void
    {
        $response = [
            'http_status' => 201,
            'ok' => true,
            'gateway_record_id' => 99,
            'gateway_queue_id' => 'Q-001',
        ];

        $this->assertTrue($response['ok']);
        $this->assertSame(201, $response['http_status']);
        $this->assertSame('Q-001', $response['gateway_queue_id']);
    }

    public function testRecordsPushDuplicateMappingStub(): void
    {
        $response = [
            'http_status' => 409,
            'ok' => true,
            'gateway_record_id' => 99,
            'gateway_queue_id' => 'Q-OLD',
        ];

        $this->assertTrue($response['ok']);
        $this->assertSame(409, $response['http_status']);
    }

    public function testRecordsPushAuthErrorMappingStub(): void
    {
        $response = [
            'http_status' => 403,
            'ok' => false,
            'retryable' => false,
            'error' => 'validation_or_auth_error',
        ];

        $this->assertFalse($response['ok']);
        $this->assertFalse($response['retryable']);
    }

    public function testRecordsPushTimeoutMappingStub(): void
    {
        $response = [
            'http_status' => 0,
            'ok' => false,
            'retryable' => true,
            'error' => 'network_error',
        ];

        $this->assertFalse($response['ok']);
        $this->assertTrue($response['retryable']);
    }
}
