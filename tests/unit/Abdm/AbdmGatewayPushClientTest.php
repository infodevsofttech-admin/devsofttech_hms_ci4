<?php

use App\Libraries\Abdm\Sync\AbdmGatewayPushClient;
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
}
