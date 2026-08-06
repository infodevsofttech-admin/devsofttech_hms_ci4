<?php

namespace Tests\Unit;

use App\Controllers\Patient;
use PHPUnit\Framework\TestCase;

final class PatientSearchConditionTest extends TestCase
{
    private function buildController(): Patient
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

            public function exposeBuildOldUhidSearchClause(string $rowData, ?string $oldUhidField): string
            {
                return $this->buildOldUhidSearchClause($rowData, $oldUhidField);
            }
        };

        $controller->setDbStub(new class {
            public function escape($value): string
            {
                return "'" . str_replace("'", "''", (string) $value) . "'";
            }
        });

        return $controller;
    }

    public function testNumericTermIncludesOldUhidCondition(): void
    {
        $controller = $this->buildController();

        $condition = $controller->exposeBuildPatientSearchCondition('12345', 'abha_id', 'old_uhid');

        $this->assertStringContainsString('old_uhid', $condition);
        $this->assertStringContainsString('TRIM(COALESCE(p.old_uhid', $condition);
    }

    public function testOldUhidClauseNormalizesSeparators(): void
    {
        $controller = $this->buildController();

        $condition = $controller->exposeBuildOldUhidSearchClause('G16/09/0015', 'old_uhid');

        $this->assertStringContainsString("p.old_uhid = 'G16/09/0015'", $condition);
        $this->assertStringContainsString("REPLACE(REPLACE(REPLACE(TRIM(COALESCE(p.old_uhid, '')), '/', ''), '-', ''), ' ', '')", $condition);
        $this->assertStringContainsString("= 'G16090015'", $condition);
    }
}
