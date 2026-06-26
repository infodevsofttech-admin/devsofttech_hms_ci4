<?php

use App\Libraries\Abdm\Sync\AbdmSyncOutboxService;
use CodeIgniter\Test\CIUnitTestCase;

final class AbdmSyncOutboxServiceTest extends CIUnitTestCase
{
    public function testBuildRecordIdempotencyKeyIsDeterministic(): void
    {
        $svc = new AbdmSyncOutboxService();
        $k1 = $svc->buildRecordIdempotencyKey('IN0510000870', 'OPD-101-S1-2026-06-27', '2026-06-27 10:30:00');
        $k2 = $svc->buildRecordIdempotencyKey('IN0510000870', 'OPD-101-S1-2026-06-27', '2026-06-27 10:30:00');

        $this->assertSame($k1, $k2);
    }

    public function testRetryScheduleEndsInDeadAfterEightRetries(): void
    {
        $svc = new AbdmSyncOutboxService();

        $this->assertNotNull($svc->getNextRetryAt(1));
        $this->assertNotNull($svc->getNextRetryAt(8));
        $this->assertNull($svc->getNextRetryAt(9));
    }
}
