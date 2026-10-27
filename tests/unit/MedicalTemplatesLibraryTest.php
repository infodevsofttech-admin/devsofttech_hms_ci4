<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Database\Seeds\Data\PathologySeedData;
use App\Database\Seeds\Data\RadiologySeedData;
use App\Database\Seeds\Data\IpdDischargeSeedData;
use App\Database\Seeds\Data\IpdDocumentSeedData;
use App\Database\Seeds\Data\OpdClinicalSeedData;
use App\Database\Seeds\Data\OpdPrintTemplateSeedData;

class MedicalTemplatesLibraryTest extends CIUnitTestCase
{
    public function testPathologySeedDataIntegrity(): void
    {
        $panels = PathologySeedData::getPanels();
        $this->assertGreaterThanOrEqual(35, count($panels));

        foreach ($panels as $panel) {
            $this->assertNotEmpty($panel['title']);
            $this->assertNotEmpty($panel['charge_desc']);
            $this->assertGreaterThan(0, $panel['charge_amount']);
            $this->assertNotEmpty($panel['interpretation']);
            $this->assertNotEmpty($panel['tests']);

            foreach ($panel['tests'] as $test) {
                $this->assertNotEmpty($test['testid'], "TestID should not be empty for {$test['name']}");
                $this->assertLessThanOrEqual(15, strlen($test['testid']), "TestID should be <= 15 chars for {$test['name']}");
                $this->assertNotEmpty($test['name'], "Test name should not be empty");
                $this->assertTrue(isset($test['norm']) && $test['norm'] !== '', "Normal value should not be empty for {$test['name']}");
            }
        }
    }

    public function testRadiologySeedDataCoverageAcrossModalities(): void
    {
        $templates = RadiologySeedData::getTemplates();
        $this->assertGreaterThanOrEqual(55, count($templates));

        $modalitiesSeen = [];
        foreach ($templates as $t) {
            $this->assertNotEmpty($t['template_name']);
            $this->assertNotEmpty($t['title']);
            $this->assertNotEmpty($t['Findings']);
            $this->assertNotEmpty($t['Impression']);
            $this->assertContains($t['Modality'], [1, 2, 3, 4, 6]);
            $modalitiesSeen[$t['Modality']] = true;
        }

        // Must cover all 5 imaging modalities
        $this->assertArrayHasKey(1, $modalitiesSeen, 'USG modality 1 missing');
        $this->assertArrayHasKey(2, $modalitiesSeen, 'MRI modality 2 missing');
        $this->assertArrayHasKey(3, $modalitiesSeen, 'X-Ray modality 3 missing');
        $this->assertArrayHasKey(4, $modalitiesSeen, 'CT-Scan modality 4 missing');
        $this->assertArrayHasKey(6, $modalitiesSeen, 'ECHO modality 6 missing');
    }

    public function testIpdDischargeAndNarrativeTemplates(): void
    {
        $dischargeTemplates = IpdDischargeSeedData::getTemplates();
        $this->assertGreaterThanOrEqual(7, count($dischargeTemplates));

        foreach ($dischargeTemplates as $d) {
            $this->assertNotEmpty($d['template_name']);
            $this->assertNotEmpty($d['template_html']);
            $this->assertStringContainsString('{{PATIENT_NAME}}', $d['template_html']);
        }

        $narratives = IpdDischargeSeedData::getNarratives();
        $this->assertGreaterThanOrEqual(30, count($narratives));

        $sections = array_column($narratives, 'section_key');
        $this->assertContains('diagnosis_remark', $sections);
        $this->assertContains('course_remark', $sections);
        $this->assertContains('instruction_other', $sections);
    }

    public function testIpdDocumentMasterForms(): void
    {
        $forms = IpdDocumentSeedData::getForms();
        $this->assertGreaterThanOrEqual(13, count($forms));

        foreach ($forms as $f) {
            $this->assertGreaterThan(0, $f['form_no']);
            $this->assertNotEmpty($f['template_name']);
            $this->assertNotEmpty($f['template_html']);
        }
    }

    public function testOpdClinicalTemplates(): void
    {
        $templates = OpdClinicalSeedData::getTemplates();
        $this->assertGreaterThanOrEqual(25, count($templates));

        $sections = array_column($templates, 'section_key');
        $this->assertContains('finding_examinations', $sections);
        $this->assertContains('provisional_diagnosis', $sections);
        $this->assertContains('diagnosis', $sections);
        $this->assertContains('prescriber_remarks', $sections);
        $this->assertContains('advice', $sections);

        foreach ($templates as $t) {
            $this->assertNotEmpty($t['template_name']);
            $this->assertNotEmpty($t['template_text']);
        }
    }

    public function testOpdPrintTemplates(): void
    {
        $templates = OpdPrintTemplateSeedData::getTemplates();
        $this->assertGreaterThanOrEqual(3, count($templates));

        foreach ($templates as $name => $t) {
            $this->assertNotEmpty($t['name']);
            $this->assertNotEmpty($t['title']);
            $this->assertNotEmpty($t['html']);
            $this->assertStringContainsString('{{PATIENT_NAME}}', $t['html']);
        }
    }

    public function testSeederPreservesExistingCustomizations(): void
    {
        $db = \Config\Database::connect('default');
        if (! $db->tableExists('opd_clinical_templates')) {
            $this->markTestSkipped('opd_clinical_templates table not present');
        }

        // 1. Pick or insert a custom clinical template simulating user edits
        $customName = 'TEST_CUSTOM_EXAM_TPL_' . uniqid();
        $customText = 'Patient exhibits custom examination notes that should never be overwritten.';
        
        $db->table('opd_clinical_templates')->insert([
            'doc_id'        => 0,
            'section_key'   => 'finding_examinations',
            'template_name' => $customName,
            'template_text' => $customText,
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        $customId = $db->insertID();

        // Also test an existing standard template from OpdClinicalSeedData
        $firstStandard = \App\Database\Seeds\Data\OpdClinicalSeedData::getTemplates()[0];
        $standardRowBefore = $db->table('opd_clinical_templates')
            ->where('doc_id', 0)
            ->where('section_key', $firstStandard['section_key'])
            ->where('template_name', $firstStandard['template_name'])
            ->get(1)
            ->getRowArray();

        // 2. Run Seeder in Safe Mode (default)
        $seeder = new \App\Database\Seeds\MedicalTemplatesLibrarySeeder(new \Config\Database());
        $seeder->setForceUpdate(false);
        $seeder->run();

        // 3. Verify custom user record is 100% untouched
        $customRowAfter = $db->table('opd_clinical_templates')->where('id', $customId)->get(1)->getRowArray();
        $this->assertNotEmpty($customRowAfter);
        $this->assertEquals($customText, $customRowAfter['template_text']);

        // 4. Verify standard existing template was not modified/overwritten
        if (! empty($standardRowBefore)) {
            $standardRowAfter = $db->table('opd_clinical_templates')
                ->where('id', $standardRowBefore['id'])
                ->get(1)
                ->getRowArray();
            $this->assertEquals($standardRowBefore['template_text'], $standardRowAfter['template_text']);
            $this->assertEquals($standardRowBefore['updated_at'], $standardRowAfter['updated_at']);
        }

        // Clean up test custom record
        $db->table('opd_clinical_templates')->where('id', $customId)->delete();
    }
}

