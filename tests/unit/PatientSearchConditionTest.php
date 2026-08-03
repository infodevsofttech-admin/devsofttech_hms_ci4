<?php

namespace Tests\Unit;

use App\Controllers\Patient;
use PHPUnit\Framework\TestCase;

final class PatientSearchConditionTest extends TestCase
{
    public function testNumericTermIncludesOldUhidCondition(): void
    {
        $controller = new class extends Patient {
            public function __construct()
            {
            }

            public function setDbStub(object $db): void
            {
                $this->db = $db;
            }

            public function exposeBuildPatientSearchCondition(string $rowData, ?string $abhaField = null, ?string $oldUhidField = null): string
            {
                return $this->buildPatientSearchCondition($rowData, $abhaField, $oldUhidField);
            }
        };

        $controller->setDbStub(new class {
            public function escape($value): string
            {
                return "'" . str_replace("'", "''", (string) $value) . "'";
            }
        });

        $condition = $controller->exposeBuildPatientSearchCondition('12345', 'abha_id', 'old_uhid');

        $this->assertStringContainsString('old_uhid', $condition);
        $this->assertStringContainsString('TRIM(COALESCE(p.old_uhid', $condition);
    }
}
