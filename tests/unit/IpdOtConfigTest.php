<?php

namespace Tests\Unit;

use Config\IpdOtForms;
use PHPUnit\Framework\TestCase;

final class IpdOtConfigTest extends TestCase
{
    public function testOphthalmologyAliasesResolvePreoperativeForm(): void
    {
        $config = new IpdOtForms();

        foreach (['Ophthalmology', 'EYE DEPARTMENT', 'Department-of-Ophthalmology', 'Ophthalmology Department'] as $departmentName) {
            $forms = $config->formsForDepartment($departmentName);
            $this->assertArrayHasKey('ophthalmology_preop_v1', $forms);
        }
    }

    public function testUnrelatedDepartmentDoesNotReceiveOphthalmologyForm(): void
    {
        $config = new IpdOtForms();

        $this->assertSame([], $config->formsForDepartment('Orthopedics'));
        $this->assertSame([], $config->formsForDepartment(''));
    }
}