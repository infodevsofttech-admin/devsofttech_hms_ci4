<?php

use App\Libraries\Abdm\M2LinkStatusResolver;
use CodeIgniter\Test\CIUnitTestCase;

final class M2LinkStatusResolverTest extends CIUnitTestCase
{
    public function testParsesCamelCaseCallbackIdentifiers(): void
    {
        $callback = M2LinkStatusResolver::parse([
            'requestId' => 'req-42',
            'abha_address' => 'patient@abdm',
            'careContextReference' => 'IMM-17-91-20260815',
            'linkedAt' => '2026-08-15 12:30:00',
            'status' => 'SUCCESS',
        ]);

        $this->assertSame('req-42', $callback['request_id']);
        $this->assertSame('IMM-17-91-20260815', $callback['care_context_reference']);
        $this->assertSame('linked', $callback['status']);
    }

    public function testHeaderRequestIdIsUsedAsFallback(): void
    {
        $callback = M2LinkStatusResolver::parse([
            'abha_id' => '12345678901234',
            'care_context_reference' => 'OPD-10-S1-2026-08-15',
            'status' => 'linked',
        ], 'header-request-id');

        $this->assertSame('header-request-id', $callback['request_id']);
    }

    public function testLinkedStateCannotBeDowngradedByLateCallback(): void
    {
        $this->assertSame('linked', M2LinkStatusResolver::merge('linked', 'failed'));
        $this->assertSame('linked', M2LinkStatusResolver::merge('linked', 'pending'));
        $this->assertSame('failed', M2LinkStatusResolver::merge('pending', 'rejected'));
        $this->assertSame('failed', M2LinkStatusResolver::merge('failed', 'pending'));
        $this->assertSame('linked', M2LinkStatusResolver::merge('failed', 'completed'));
    }
}
