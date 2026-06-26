<?php

use App\Libraries\Abdm\Fhir\FhirGeneratorFactory;
use CodeIgniter\Test\CIUnitTestCase;

final class FhirGeneratorsTest extends CIUnitTestCase
{
    private FhirGeneratorFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new FhirGeneratorFactory();
    }

    public function testOpdGeneratorHappyPath(): void
    {
        $src = [
            'record_id' => 101,
            'session_id' => 1,
            'visit_date' => '2026-06-27',
            'completed_at' => '2026-06-27T10:00:00+05:30',
            'patient' => ['id' => 12, 'name' => 'Devender Singh', 'gender' => 'male', 'dob' => '1979-03-28', 'abha_id' => '1234-5678-9012'],
            'encounter' => ['id' => 'E101', 'start' => '2026-06-27T09:30:00+05:30', 'end' => '2026-06-27T10:00:00+05:30'],
            'doctor' => ['id' => 'D9', 'name' => 'Dr. R K Sundriyal'],
            'organization' => ['id' => 'H1', 'name' => 'Chamunda Hospital'],
            'diagnoses' => [['text' => 'Acute upper respiratory infection']],
            'medications' => [['name' => 'Eltroxin 50', 'dosage' => '1-0-0 for 5 days']],
            'vitals' => [['display' => 'Heart Rate', 'code' => '8867-4', 'value' => 82, 'unit' => 'beats/min']],
        ];

        $out = $this->factory->opd()->generate($src);
        $this->assertSame('OPConsultRecord', $out['hi_type']);
        $this->assertSame('Bundle', $out['fhir_bundle']['resourceType']);
        $this->assertSame('document', $out['fhir_bundle']['type']);
        $this->assertSame('Composition', $out['fhir_bundle']['entry'][0]['resource']['resourceType']);
        $this->assertTrue((bool) ($out['validation']['valid'] ?? false));
    }

    public function testLabGeneratorLargeObservationSet(): void
    {
        $obs = [];
        for ($i = 0; $i < 120; $i++) {
            $obs[] = ['name' => 'Glucose', 'code' => 'GLUCOSE', 'value' => 90 + $i, 'unit' => 'mg/dL', 'interpretation' => 'NORMAL'];
        }

        $src = [
            'record_id' => 4001,
            'session_id' => 9,
            'visit_date' => '2026-06-26',
            'completed_at' => '2026-06-26T18:10:00+05:30',
            'patient' => ['id' => 12, 'name' => 'Devender Singh', 'gender' => 'male', 'dob' => '1979-03-28'],
            'encounter' => ['id' => 'L4001', 'start' => '2026-06-26T16:00:00+05:30'],
            'panel_name' => 'Biochemistry',
            'panel_code' => 'GLUCOSE',
            'observations' => $obs,
        ];

        $out = $this->factory->lab()->generate($src);
        $this->assertSame('DiagnosticReportRecord', $out['hi_type']);
        $this->assertTrue((bool) ($out['validation']['valid'] ?? false));
        $this->assertGreaterThan(90, count($out['fhir_bundle']['entry']));
    }

    public function testDeterministicCareContextForSameInput(): void
    {
        $src = [
            'record_id' => 7001,
            'session_id' => 3,
            'visit_date' => '2026-06-25',
            'completed_at' => '2026-06-25T10:00:00+05:30',
            'patient' => ['id' => 1, 'name' => 'Test Patient', 'gender' => 'male', 'dob' => '1990-01-01'],
        ];

        $o1 = $this->factory->invoice()->generate($src);
        $o2 = $this->factory->invoice()->generate($src);

        $this->assertSame($o1['care_context_reference'], $o2['care_context_reference']);
        $this->assertSame($o1['hi_type'], $o2['hi_type']);
    }
}
