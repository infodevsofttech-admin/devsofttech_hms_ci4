<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use App\Database\Seeds\Data\PathologySeedData;
use App\Database\Seeds\Data\RadiologySeedData;
use App\Database\Seeds\Data\IpdDischargeSeedData;
use App\Database\Seeds\Data\IpdDocumentSeedData;
use App\Database\Seeds\Data\OpdClinicalSeedData;
use App\Database\Seeds\Data\OpdPrintTemplateSeedData;

/**
 * MedicalTemplatesLibrarySeeder
 *
 * Comprehensive out-of-the-box templates library for a General & Multispeciality Hospital:
 *   1. Pathology Panels, Tests, Reference Intervals, Units, LOINC codes (lab_repo, lab_tests, lab_repotests)
 *   2. Radiology Templates for USG, ECHO, X-Ray, CT-Scan, MRI (radiology_ultrasound_template, hc_items)
 *   3. IPD Discharge Summary Templates (ipd_discharge_templates)
 *   4. IPD Clinical Narrative Notes (ipd_discharge_narrative_templates)
 *   5. IPD Document Master Forms (ipd_document_templates)
 *   6. OPD Doctor Clinical Templates (opd_clinical_templates)
 *   7. OPD Prescription Print Templates (hospital_setting)
 *
 * Safe to re-run (Insert-If-Not-Exists):
 *   - Strictly checks if records already exist in the database.
 *   - If a record exists, it is NEVER overwritten/updated by default, preserving all
 *     doctor edits, customized normal ranges, and hospital modifications.
 *   - If a record is missing (new template added to Data files), it is inserted seamlessly.
 *   - Optional override: set SEED_FORCE_UPDATE=1 or call setForceUpdate(true).
 *
 * Usage:
 *   php spark db:seed MedicalTemplatesLibrarySeeder
 */
class MedicalTemplatesLibrarySeeder extends Seeder
{
    /**
     * When false (default), existing database records are preserved and never overwritten.
     */
    protected bool $forceUpdate = false;

    /**
     * Programmatically enable or disable force updating
     */
    public function setForceUpdate(bool $force): self
    {
        $this->forceUpdate = $force;
        return $this;
    }

    public function run(): void
    {
        $forceEnv = getenv('SEED_FORCE_UPDATE') ?: ($_ENV['SEED_FORCE_UPDATE'] ?? ($_SERVER['SEED_FORCE_UPDATE'] ?? null));
        if ($forceEnv === '1' || strtolower((string)$forceEnv) === 'true') {
            $this->forceUpdate = true;
        }

        CLI::write("=======================================================", 'cyan');
        CLI::write("  SEEDING COMPREHENSIVE MEDICAL TEMPLATES LIBRARY     ", 'yellow');
        CLI::write("=======================================================", 'cyan');

        if ($this->forceUpdate) {
            CLI::write("  [MODE] Force Update: ENABLED (Existing records will be updated)", 'yellow');
        } else {
            CLI::write("  [MODE] Safe Mode: Insert-If-Not-Exists (Existing records PRESERVED)", 'green');
        }

        $now = date('Y-m-d H:i:s');

        $this->seedPathology($now);
        $this->seedRadiology($now);
        $this->seedIpdDischarge($now);
        $this->seedIpdNarratives($now);
        $this->seedIpdDocuments($now);
        $this->seedOpdClinical($now);
        $this->seedOpdPrintTemplates($now);

        CLI::write("-------------------------------------------------------", 'cyan');
        CLI::write("Medical Templates Library Seeding Successfully Completed!", 'green');
    }

    /**
     * 1. Seed Pathology Panels, Tests, and Junction Table
     */
    private function seedPathology(string $now): void
    {
        $db = $this->db;
        if (! $db->tableExists('lab_repo') || ! $db->tableExists('lab_tests') || ! $db->tableExists('lab_repotests')) {
            CLI::write("  [SKIP] Pathology tables missing.", 'yellow');
            return;
        }

        $panels = PathologySeedData::getPanels();
        $panelInserted = 0;
        $panelSkipped  = 0;
        $panelUpdated  = 0;
        $testInserted  = 0;
        $testSkipped   = 0;
        $testUpdated   = 0;

        foreach ($panels as $p) {
            // A. Ensure Billing Charge in `hc_items`
            $chargeId = 0;
            if ($db->tableExists('hc_items')) {
                $chargeRow = $db->table('hc_items')
                    ->where('itype', 5)
                    ->where('idesc', $p['charge_desc'])
                    ->get(1)
                    ->getRowArray();

                if (! empty($chargeRow['id'])) {
                    $chargeId = (int) $chargeRow['id'];
                } else {
                    $db->table('hc_items')->insert([
                        'itype'            => 5,
                        'idesc'            => $p['charge_desc'],
                        'amount'           => $p['charge_amount'],
                        'amount_r'         => 0.00,
                        'echs_sr_no'       => 0,
                        'update_date'      => $now,
                        'last_update_desc' => 'Seeded by MedicalTemplatesLibrarySeeder',
                    ]);
                    $chargeId = (int) $db->insertID();
                }
            }

            // B. Build HTML Data with {TESTID} tokens
            $htmlRows = '';
            foreach ($p['tests'] as $t) {
                $refInterval = $t['norm'];
                if (! empty($t['gender']) && ! empty($t['norm_w']) && $t['norm_w'] !== $t['norm']) {
                    $refInterval = "Male: {$t['norm']} | Female: {$t['norm_w']}";
                }
                $htmlRows .= "        <tr>\n";
                $htmlRows .= "            <td>{$t['name']}</td>\n";
                $htmlRows .= "            <td>{{$t['testid']}}</td>\n";
                $htmlRows .= "            <td>{$t['unit']}</td>\n";
                $htmlRows .= "            <td>{$refInterval}</td>\n";
                $htmlRows .= "        </tr>\n";
            }

            $htmlData = <<<HTML
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; font-size:11px;">
    <thead>
        <tr style="background:#f1f5f9;">
            <th style="text-align:left; width:36%;">Investigation / Test Name</th>
            <th style="text-align:left; width:18%;">Observed Result</th>
            <th style="text-align:left; width:14%;">Unit</th>
            <th style="text-align:left; width:32%;">Biological Ref. Interval</th>
        </tr>
    </thead>
    <tbody>
{$htmlRows}    </tbody>
</table>

<div style="font-size:10px; color:#475569; margin-top:8px; line-height:1.4;">
    <strong>CLINICAL INTERPRETATION / REMARKS:</strong> {$p['interpretation']}
</div>
HTML;

            // C. Insert or Skip `lab_repo`
            $existingPanel = $db->table('lab_repo')
                ->where('Title', $p['title'])
                ->get(1)
                ->getRowArray();

            $panelData = [
                'Title'         => $p['title'],
                'loinc_code'    => $p['loinc_code'],
                'GrpKey'        => $p['grp_key'],
                'IncludeHeader' => 1,
                'IncludeFooter' => 1,
                'charge_id'     => $chargeId,
                'HTMLData'      => $htmlData,
                'RTFData'       => $htmlData,
            ];

            if (! empty($existingPanel['mstRepoKey'])) {
                $repoKey = (int) $existingPanel['mstRepoKey'];
                if ($this->forceUpdate) {
                    $db->table('lab_repo')->where('mstRepoKey', $repoKey)->update($panelData);
                    $panelUpdated++;
                } else {
                    $panelSkipped++;
                }
            } else {
                $db->table('lab_repo')->insert($panelData);
                $repoKey = (int) $db->insertID();
                $panelInserted++;
            }

            // D. Upsert or Skip individual tests in `lab_tests` and map in `lab_repotests`
            $eOrder = 10;
            foreach ($p['tests'] as $t) {
                $existingTest = $db->table('lab_tests')
                    ->where('TestID', $t['testid'])
                    ->get(1)
                    ->getRowArray();

                $testData = [
                    'Test'              => $t['name'],
                    'TestID'            => $t['testid'],
                    'Result'            => '',
                    'Unit'              => $t['unit'],
                    'loinc_code'        => $t['loinc'],
                    'FixedNormals'      => $t['norm'],
                    'FixedNormalsWomen' => $t['norm_w'] ?? $t['norm'],
                    'isGenderSpecific'  => $t['gender'] ?? 0,
                ];

                if (! empty($existingTest['mstTestKey'])) {
                    $testKey = (int) $existingTest['mstTestKey'];
                    if ($this->forceUpdate) {
                        $db->table('lab_tests')->where('mstTestKey', $testKey)->update($testData);
                        $testUpdated++;
                    } else {
                        $testSkipped++;
                    }
                } else {
                    $db->table('lab_tests')->insert($testData);
                    $testKey = (int) $db->insertID();
                    $testInserted++;
                }

                // Check mapping in `lab_repotests`
                $existingLink = $db->table('lab_repotests')
                    ->where('mstRepoKey', $repoKey)
                    ->where('mstTestKey', $testKey)
                    ->get(1)
                    ->getRowArray();

                if (! empty($existingLink['id'])) {
                    if ($this->forceUpdate) {
                        $db->table('lab_repotests')
                            ->where('id', $existingLink['id'])
                            ->update(['EOrder' => $eOrder]);
                    }
                } else {
                    $db->table('lab_repotests')->insert([
                        'mstRepoKey' => $repoKey,
                        'mstTestKey' => $testKey,
                        'EOrder'     => $eOrder,
                    ]);
                }

                $eOrder += 10;
            }
        }

        CLI::write("  [OK] Pathology Library Seeded:", 'green');
        if ($this->forceUpdate) {
            CLI::write("       - Panels: {$panelInserted} added, {$panelUpdated} updated (Total: " . count($panels) . ")");
            CLI::write("       - Test Parameters: {$testInserted} added, {$testUpdated} updated");
        } else {
            CLI::write("       - Panels: {$panelInserted} added, {$panelSkipped} preserved (already existing) (Total: " . count($panels) . ")");
            CLI::write("       - Test Parameters: {$testInserted} added, {$testSkipped} preserved (already existing)");
        }
    }

    /**
     * 2. Seed Radiology Master Templates (USG, ECHO, X-Ray, CT, MRI)
     */
    private function seedRadiology(string $now): void
    {
        $db = $this->db;
        if (! $db->tableExists('radiology_ultrasound_template')) {
            CLI::write("  [SKIP] radiology_ultrasound_template table missing.", 'yellow');
            return;
        }

        // A. Ensure Billing Services in `hc_items`
        $chargeIdMap = [];
        if ($db->tableExists('hc_items')) {
            foreach (RadiologySeedData::getServices() as $svc) {
                $existing = $db->table('hc_items')
                    ->where('itype', $svc['itype'])
                    ->where('idesc', $svc['desc'])
                    ->get(1)
                    ->getRowArray();

                if (! empty($existing['id'])) {
                    $chargeIdMap[$svc['desc']] = (int) $existing['id'];
                } else {
                    $db->table('hc_items')->insert([
                        'itype'            => $svc['itype'],
                        'idesc'            => $svc['desc'],
                        'amount'           => $svc['amount'],
                        'amount_r'         => 0.00,
                        'echs_sr_no'       => 0,
                        'update_date'      => $now,
                        'last_update_desc' => 'Seeded by MedicalTemplatesLibrarySeeder',
                    ]);
                    $chargeIdMap[$svc['desc']] = (int) $db->insertID();
                }
            }
        }

        // B. Insert or Skip Master Templates
        $templates = RadiologySeedData::getTemplates();
        $inserted = 0;
        $skipped  = 0;
        $updated  = 0;

        foreach ($templates as $tpl) {
            $chargeId = 0;
            if (! empty($tpl['service_key']) && isset($chargeIdMap[$tpl['service_key']])) {
                $chargeId = $chargeIdMap[$tpl['service_key']];
            }

            $existing = $db->table('radiology_ultrasound_template')
                ->where('template_name', $tpl['template_name'])
                ->where('Modality', $tpl['Modality'])
                ->get(1)
                ->getRowArray();

            $data = [
                'template_name'  => $tpl['template_name'],
                'title'          => $tpl['title'],
                'keywords'       => $tpl['keywords'],
                'Modality'       => $tpl['Modality'],
                'charge_id'      => $chargeId,
                'Findings'       => $tpl['Findings'],
                'Impression'     => $tpl['Impression'],
                'impression_cat' => $tpl['impression_cat'],
            ];

            if (! empty($existing['id'])) {
                if ($this->forceUpdate) {
                    $db->table('radiology_ultrasound_template')
                        ->where('id', $existing['id'])
                        ->update($data);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $db->table('radiology_ultrasound_template')->insert($data);
                $inserted++;
            }
        }

        CLI::write("  [OK] Radiology Library Seeded (USG, ECHO, X-Ray, CT, MRI):", 'green');
        if ($this->forceUpdate) {
            CLI::write("       - Templates: {$inserted} added, {$updated} updated (Total: " . count($templates) . ")");
        } else {
            CLI::write("       - Templates: {$inserted} added, {$skipped} preserved (already existing) (Total: " . count($templates) . ")");
        }
    }

    /**
     * 3. Seed IPD Discharge Summary Templates
     */
    private function seedIpdDischarge(string $now): void
    {
        $db = $this->db;
        if (! $db->tableExists('ipd_discharge_templates')) {
            CLI::write("  [SKIP] ipd_discharge_templates table missing.", 'yellow');
            return;
        }

        $templates = IpdDischargeSeedData::getTemplates();
        $inserted = 0;
        $skipped  = 0;
        $updated  = 0;

        foreach ($templates as $tpl) {
            $existing = $db->table('ipd_discharge_templates')
                ->where('template_name', $tpl['template_name'])
                ->get(1)
                ->getRowArray();

            $data = [
                'template_name'       => $tpl['template_name'],
                'page_size'           => $tpl['page_size'],
                'custom_width_mm'     => $tpl['custom_width_mm'],
                'custom_height_mm'    => $tpl['custom_height_mm'],
                'page_margin_top_cm'  => $tpl['page_margin_top_cm'],
                'page_margin_bottom_cm'=> $tpl['page_margin_bottom_cm'],
                'page_margin_left_cm' => $tpl['page_margin_left_cm'],
                'page_margin_right_cm'=> $tpl['page_margin_right_cm'],
                'margin_header_cm'    => $tpl['margin_header_cm'],
                'margin_footer_cm'    => $tpl['margin_footer_cm'],
                'header_html'         => $tpl['header_html'],
                'footer_html'         => $tpl['footer_html'],
                'template_html'       => $tpl['template_html'],
                'is_default'          => $tpl['is_default'],
                'is_audit_only'       => $tpl['is_audit_only'],
                'is_abdm'             => $tpl['is_abdm'],
                'watermark_type'      => 'none',
                'watermark_alpha'     => 0.15,
                'status'              => $tpl['status'],
                'updated_at'          => $now,
            ];

            if (! empty($existing['id'])) {
                if ($this->forceUpdate) {
                    $db->table('ipd_discharge_templates')
                        ->where('id', $existing['id'])
                        ->update($data);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $data['created_at'] = $now;
                $db->table('ipd_discharge_templates')->insert($data);
                $inserted++;
            }
        }

        CLI::write("  [OK] IPD Discharge Templates Seeded:", 'green');
        if ($this->forceUpdate) {
            CLI::write("       - Templates: {$inserted} added, {$updated} updated (Total: " . count($templates) . ")");
        } else {
            CLI::write("       - Templates: {$inserted} added, {$skipped} preserved (already existing) (Total: " . count($templates) . ")");
        }
    }

    /**
     * 4. Seed Master Clinical Narrative Notes
     */
    private function seedIpdNarratives(string $now): void
    {
        $db = $this->db;
        if (! $db->tableExists('ipd_discharge_narrative_templates')) {
            CLI::write("  [SKIP] ipd_discharge_narrative_templates table missing.", 'yellow');
            return;
        }

        $narratives = IpdDischargeSeedData::getNarratives();
        $inserted = 0;
        $skipped  = 0;
        $updated  = 0;

        foreach ($narratives as $nar) {
            $existing = $db->table('ipd_discharge_narrative_templates')
                ->where('doc_id', 0)
                ->where('section_key', $nar['section_key'])
                ->where('template_name', $nar['template_name'])
                ->get(1)
                ->getRowArray();

            $data = [
                'doc_id'        => 0,
                'section_key'   => $nar['section_key'],
                'template_name' => $nar['template_name'],
                'template_text' => $nar['template_text'],
                'is_active'     => 1,
                'updated_at'    => $now,
            ];

            if (! empty($existing['id'])) {
                if ($this->forceUpdate) {
                    $db->table('ipd_discharge_narrative_templates')
                        ->where('id', $existing['id'])
                        ->update($data);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $data['created_at'] = $now;
                $db->table('ipd_discharge_narrative_templates')->insert($data);
                $inserted++;
            }
        }

        CLI::write("  [OK] IPD Clinical Narratives Seeded:", 'green');
        if ($this->forceUpdate) {
            CLI::write("       - Narratives: {$inserted} added, {$updated} updated (Total: " . count($narratives) . ")");
        } else {
            CLI::write("       - Narratives: {$inserted} added, {$skipped} preserved (already existing) (Total: " . count($narratives) . ")");
        }
    }

    /**
     * 5. Seed IPD Document Master Forms
     */
    private function seedIpdDocuments(string $now): void
    {
        $db = $this->db;
        if (! $db->tableExists('ipd_document_templates')) {
            CLI::write("  [SKIP] ipd_document_templates table missing.", 'yellow');
            return;
        }

        $forms = IpdDocumentSeedData::getForms();
        $inserted = 0;
        $skipped  = 0;
        $updated  = 0;

        foreach ($forms as $frm) {
            $existing = $db->table('ipd_document_templates')
                ->where('form_no', $frm['form_no'])
                ->where('template_name', $frm['template_name'])
                ->get(1)
                ->getRowArray();

            $data = [
                'form_no'             => $frm['form_no'],
                'template_name'       => $frm['template_name'],
                'page_size'           => $frm['page_size'],
                'custom_width_mm'     => $frm['custom_width_mm'],
                'custom_height_mm'    => $frm['custom_height_mm'],
                'page_margin_top_cm'  => $frm['page_margin_top_cm'],
                'page_margin_bottom_cm'=> $frm['page_margin_bottom_cm'],
                'page_margin_left_cm' => $frm['page_margin_left_cm'],
                'page_margin_right_cm'=> $frm['page_margin_right_cm'],
                'margin_header_cm'    => $frm['margin_header_cm'],
                'margin_footer_cm'    => $frm['margin_footer_cm'],
                'template_html'       => $frm['template_html'],
                'status'              => $frm['status'],
                'updated_at'          => $now,
            ];

            if (! empty($existing['id'])) {
                if ($this->forceUpdate) {
                    $db->table('ipd_document_templates')
                        ->where('id', $existing['id'])
                        ->update($data);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $data['created_at'] = $now;
                $db->table('ipd_document_templates')->insert($data);
                $inserted++;
            }
        }

        CLI::write("  [OK] IPD Document Master Forms Seeded:", 'green');
        if ($this->forceUpdate) {
            CLI::write("       - Forms: {$inserted} added, {$updated} updated (Total: " . count($forms) . ")");
        } else {
            CLI::write("       - Forms: {$inserted} added, {$skipped} preserved (already existing) (Total: " . count($forms) . ")");
        }
    }

    /**
     * 6. Seed OPD Doctor Clinical Templates (`opd_clinical_templates`)
     */
    private function seedOpdClinical(string $now): void
    {
        $db = $this->db;
        if (! $db->tableExists('opd_clinical_templates')) {
            $forge = \Config\Database::forge($db);
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'doc_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'section_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                ],
                'template_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'template_text' => [
                    'type' => 'TEXT',
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('id', true);
            $forge->addKey(['doc_id', 'section_key']);
            $forge->addKey(['section_key', 'is_active']);
            $forge->createTable('opd_clinical_templates', true);
        }

        $templates = OpdClinicalSeedData::getTemplates();
        $inserted = 0;
        $skipped  = 0;
        $updated  = 0;

        foreach ($templates as $t) {
            $existing = $db->table('opd_clinical_templates')
                ->where('doc_id', 0)
                ->where('section_key', $t['section_key'])
                ->where('template_name', $t['template_name'])
                ->get(1)
                ->getRowArray();

            $data = [
                'doc_id'        => 0,
                'section_key'   => $t['section_key'],
                'template_name' => $t['template_name'],
                'template_text' => $t['template_text'],
                'is_active'     => 1,
                'updated_at'    => $now,
            ];

            if (! empty($existing['id'])) {
                if ($this->forceUpdate) {
                    $db->table('opd_clinical_templates')
                        ->where('id', $existing['id'])
                        ->update($data);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $data['created_at'] = $now;
                $db->table('opd_clinical_templates')->insert($data);
                $inserted++;
            }
        }

        CLI::write("  [OK] OPD Doctor Clinical Templates Seeded:", 'green');
        if ($this->forceUpdate) {
            CLI::write("       - Templates: {$inserted} added, {$updated} updated (Total: " . count($templates) . ")");
        } else {
            CLI::write("       - Templates: {$inserted} added, {$skipped} preserved (already existing) (Total: " . count($templates) . ")");
        }
    }

    /**
     * 7. Seed OPD Prescription Print Templates (`hospital_setting`)
     */
    private function seedOpdPrintTemplates(string $now): void
    {
        $db = $this->db;
        if (! $db->tableExists('hospital_setting')) {
            CLI::write("  [SKIP] hospital_setting table missing.", 'yellow');
            return;
        }

        $templates = OpdPrintTemplateSeedData::getTemplates();
        $inserted = 0;
        $skipped  = 0;
        $updated  = 0;

        foreach ($templates as $key => $t) {
            $storageKey = 'OPD_PRINT_TEMPLATE__' . $t['name'];
            $existing = $db->table('hospital_setting')
                ->where('s_name', $storageKey)
                ->get(1)
                ->getRowArray();

            if (! empty($existing)) {
                if ($this->forceUpdate) {
                    $db->table('hospital_setting')
                        ->where('s_name', $storageKey)
                        ->update(['s_value' => $t['html']]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $db->table('hospital_setting')->insert([
                    's_name'  => $storageKey,
                    's_value' => $t['html'],
                ]);
                $inserted++;
            }
        }

        CLI::write("  [OK] OPD Print Templates Seeded:", 'green');
        if ($this->forceUpdate) {
            CLI::write("       - Templates: {$inserted} added, {$updated} updated (Total: " . count($templates) . ")");
        } else {
            CLI::write("       - Templates: {$inserted} added, {$skipped} preserved (already existing) (Total: " . count($templates) . ")");
        }
    }
}

