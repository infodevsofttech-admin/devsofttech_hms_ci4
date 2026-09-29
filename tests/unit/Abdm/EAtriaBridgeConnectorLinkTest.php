<?php

use App\Libraries\Abdm\EAtriaBridgeConnector;
use CodeIgniter\Test\CIUnitTestCase;

final class EAtriaBridgeConnectorLinkTest extends CIUnitTestCase
{
    public function testHipLinkCareContextRetriesWhenTokenPending(): void
    {
        $connector = $this->getMockBuilder(EAtriaBridgeConnector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['post'])
            ->getMock();

        // 1st call returns token pending error, 2nd call returns success
        $connector->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls(
                [
                    'ok' => 0,
                    'http_code' => 400,
                    'error' => 'link_token or link_token_id is required (call /api/v3/hip/link-token first)',
                ],
                [
                    'ok' => 1,
                    'http_code' => 202,
                    'message' => 'Care-context link request sent to ABDM.',
                ]
            );

        $payload = [
            'abha_address' => 'user@sbx',
            'link_token_id' => 12345,
            'care_contexts' => [['ref' => 'CC-1', 'display' => 'Visit 1']],
        ];

        $refProp = new ReflectionProperty($connector, 'linkTokenRetryDelays');
        $refProp->setAccessible(true);
        $refProp->setValue($connector, [0]);

        $res = $connector->hipLinkCareContext($payload);

        $this->assertSame(1, $res['ok']);
        $this->assertSame(202, $res['http_code']);
        $this->assertSame('Care-context link request sent to ABDM.', $res['message']);
    }

    public function testHipLinkCareContextReturnsErrorAfterMaxRetries(): void
    {
        $connector = $this->getMockBuilder(EAtriaBridgeConnector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['post'])
            ->getMock();

        $refProp = new ReflectionProperty($connector, 'linkTokenRetryDelays');
        $refProp->setAccessible(true);
        $refProp->setValue($connector, [0, 0, 0]);

        // All 4 attempts return pending error
        $connector->expects($this->exactly(4))
            ->method('post')
            ->willReturn([
                'ok' => 0,
                'http_code' => 400,
                'error' => 'link_token or link_token_id is required (call /api/v3/hip/link-token first)',
            ]);

        $payload = [
            'abha_address' => 'user@sbx',
            'link_token_id' => 99999,
            'care_contexts' => [['ref' => 'CC-1', 'display' => 'Visit 1']],
        ];

        $res = $connector->hipLinkCareContext($payload);

        $this->assertSame(0, $res['ok']);
        $this->assertStringContainsString('ABDM authorization token callback is still pending', $res['error_text']);
    }
}
