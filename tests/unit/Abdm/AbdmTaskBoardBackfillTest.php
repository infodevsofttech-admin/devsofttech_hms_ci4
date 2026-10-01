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
}
