<?php

namespace Tests\Unit;

use App\Controllers\Diagnosis;
use PHPUnit\Framework\TestCase;

final class DiagnosisTemplateTokenTest extends TestCase
{
    private function buildTestController(): Diagnosis
    {
        return new class extends Diagnosis {
            public function __construct()
            {
            }

            public function exposeBuildPdfTokens(array $data): array
            {
                return $this->buildPdfTokens($data);
            }

            public function exposeApplyPdfTokens(string $html, array $tokens): string
            {
                return $this->applyPdfTokens($html, $tokens);
            }
        };
    }

    public function testBuildPdfTokensContainsHospitalAndPatientPlaceholders(): void
    {
        $controller = $this->buildTestController();

        $tokens = $controller->exposeBuildPdfTokens([
            'patient_name'        => 'John Doe',
            'uhid'                => 'UHID12345',
            'invoice_code'        => 'INV999',
            'age'                 => '35 Y',
            'gender'              => 'Male',
            'phone_no'            => '9876543210',
            'patient_address'     => '123 Main Street',
            'doctor_name'         => 'Dr. Smith',
            'doctor_education'    => 'MBBS, MD',
            'technician_name'     => 'Alex',
            'report_title'        => 'X-RAY CHEST',
        ]);

        $this->assertArrayHasKey('{{H_Name}}', $tokens);
        $this->assertArrayHasKey('{{H_address_1}}', $tokens);
        $this->assertArrayHasKey('{{H_phone_No}}', $tokens);
        $this->assertArrayHasKey('{{H_logo}}', $tokens);
        $this->assertArrayHasKey('assets/images/{{H_logo}}', $tokens);

        $this->assertSame('John Doe', $tokens['{{patient_name}}']);
        $this->assertSame('John Doe', $tokens['{{pName}}']);
        $this->assertSame('UHID12345', $tokens['{{uhid}}']);
        $this->assertSame('INV999', $tokens['{{invoice_code}}']);
        $this->assertSame('9876543210', $tokens['{{phoneno}}']);
        $this->assertSame('123 Main Street', $tokens['{{p_address}}']);
        $this->assertSame('Dr. Smith', $tokens['{{doctor_name}}']);
        $this->assertSame('X-RAY CHEST', $tokens['{{report_title}}']);
    }

    public function testApplyPdfTokensReplacesTemplatePlaceholders(): void
    {
        $controller = $this->buildTestController();

        $templateHtml = '<div class="header">'
            . '<img src="assets/images/{{H_logo}}" />'
            . '<h1>{{H_Name}}</h1>'
            . '<p>{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</p>'
            . '<div>Patient: {{pName}} ({{uhid}}) | Invoice: {{invoice_code}}</div>'
            . '<div>Report: {{report_title}}</div>'
            . '</div>';

        $tokens = $controller->exposeBuildPdfTokens([
            'patient_name' => 'Alice Wonder',
            'uhid'         => 'UHID-888',
            'invoice_code' => 'INV-777',
            'report_title' => 'CT SCAN BRAIN',
        ]);

        $output = $controller->exposeApplyPdfTokens($templateHtml, $tokens);

        $this->assertStringNotContainsString('{{H_Name}}', $output);
        $this->assertStringNotContainsString('assets/images/{{H_logo}}', $output);
        $this->assertStringNotContainsString('{{H_address_1}}', $output);
        $this->assertStringNotContainsString('{{H_phone_No}}', $output);
        $this->assertStringNotContainsString('{{pName}}', $output);
        $this->assertStringNotContainsString('{{uhid}}', $output);
        $this->assertStringNotContainsString('{{invoice_code}}', $output);
        $this->assertStringNotContainsString('{{report_title}}', $output);

        $this->assertStringContainsString('Alice Wonder', $output);
        $this->assertStringContainsString('UHID-888', $output);
        $this->assertStringContainsString('INV-777', $output);
        $this->assertStringContainsString('CT SCAN BRAIN', $output);
    }

    public function testApplyPdfTokensIsCaseInsensitive(): void
    {
        $controller = $this->buildTestController();

        $templateHtml = '<p>{{h_name}} - {{H_NAME}} - {{patient_NAME}} - {{UHID}}</p>';

        $tokens = $controller->exposeBuildPdfTokens([
            'patient_name' => 'Bob Marley',
            'uhid'         => 'UHID-999',
        ]);

        $output = $controller->exposeApplyPdfTokens($templateHtml, $tokens);

        $this->assertStringNotContainsString('{{h_name}}', $output);
        $this->assertStringNotContainsString('{{H_NAME}}', $output);
        $this->assertStringNotContainsString('{{patient_NAME}}', $output);
        $this->assertStringNotContainsString('{{UHID}}', $output);
        $this->assertStringContainsString('Bob Marley', $output);
        $this->assertStringContainsString('UHID-999', $output);
    }
}
