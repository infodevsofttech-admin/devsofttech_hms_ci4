<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ClinicalTemplateWorkspaceSeeder extends Seeder
{
    public function run()
    {
        $this->ensureClinicalTemplateTable();

        if (! $this->db->tableExists('opd_clinical_templates')) {
            return;
        }

        $this->seedMasterTemplates();
    }

    private function ensureClinicalTemplateTable(): void
    {
        if ($this->db->tableExists('opd_clinical_templates')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS opd_clinical_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doc_id INT NOT NULL DEFAULT 0,
            section_key VARCHAR(80) NOT NULL,
            template_name VARCHAR(255) NOT NULL,
            template_text TEXT NOT NULL,
            is_active TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_doc_section (doc_id, section_key),
            INDEX idx_section_active (section_key, is_active)
        )");
    }

    private function seedMasterTemplates(): void
    {
        $table = 'opd_clinical_templates';
        $fields = $this->db->getFieldNames($table);
        $now = date('Y-m-d H:i:s');

        $templates = [
            [
                'section_key' => 'finding_examinations',
                'template_name' => 'General OPD Exam - Stable',
                'template_text' => 'Patient conscious, oriented, cooperative. Vitals stable. No pallor/icterus/cyanosis/clubbing/edema. CVS S1S2 normal, RS clear bilaterally, P/A soft non-tender, CNS NAD.',
            ],
            [
                'section_key' => 'finding_examinations',
                'template_name' => 'Fever Exam - Mild Viral Pattern',
                'template_text' => 'Temp mildly elevated, pulse regular, BP stable. Throat mild congestion, chest clear, no focal neurological deficit, no neck rigidity. Hydration fair.',
            ],
            [
                'section_key' => 'finding_examinations',
                'template_name' => 'GI Symptom Exam - Non Acute',
                'template_text' => 'Afebrile, hemodynamically stable. Mild epigastric tenderness, no guarding/rigidity, bowel sounds present. No hepatosplenomegaly.',
            ],

            [
                'section_key' => 'diagnosis',
                'template_name' => 'Acute URI - Uncomplicated',
                'template_text' => 'Acute upper respiratory tract infection (uncomplicated).',
            ],
            [
                'section_key' => 'diagnosis',
                'template_name' => 'Acid Peptic Disorder',
                'template_text' => 'Acid peptic disease / gastritis without alarm features.',
            ],
            [
                'section_key' => 'diagnosis',
                'template_name' => 'Acute Febrile Illness',
                'template_text' => 'Acute febrile illness, likely viral etiology; monitor for warning signs.',
            ],

            [
                'section_key' => 'provisional_diagnosis',
                'template_name' => 'Provisional - Viral Fever',
                'template_text' => 'Provisional diagnosis: Viral fever. Differential: early bacterial infection if fever persists >72 hours.',
            ],
            [
                'section_key' => 'provisional_diagnosis',
                'template_name' => 'Provisional - Dyspepsia',
                'template_text' => 'Provisional diagnosis: Dyspepsia / gastritis. Differential: GERD, peptic ulcer disease.',
            ],
            [
                'section_key' => 'provisional_diagnosis',
                'template_name' => 'Provisional - Hypertension Follow-up',
                'template_text' => 'Provisional diagnosis: Essential hypertension on follow-up; control status to be assessed with home BP chart.',
            ],

            [
                'section_key' => 'prescriber_remarks',
                'template_name' => 'Standard Safety Net Advice',
                'template_text' => 'Counseled regarding hydration, medication compliance, and red-flag symptoms. If persistent high fever, breathlessness, chest pain, altered sensorium, or repeated vomiting, report immediately.',
            ],
            [
                'section_key' => 'prescriber_remarks',
                'template_name' => 'Antibiotic Stewardship Remark',
                'template_text' => 'Antibiotic use explained with dose, duration, and adherence importance. Do not self-stop medication early. Report adverse effects promptly.',
            ],
            [
                'section_key' => 'prescriber_remarks',
                'template_name' => 'Chronic Disease Counseling',
                'template_text' => 'Lifestyle counseling done: diet moderation, salt restriction, regular physical activity, sleep hygiene, and follow-up adherence.',
            ],

            [
                'section_key' => 'advice',
                'template_name' => 'Fever Home Care Advice',
                'template_text' => 'Adequate oral fluids, light diet, tepid sponging if required, rest. Maintain temperature chart. Review if fever not improving in 2-3 days or if warning signs appear.',
            ],
            [
                'section_key' => 'advice',
                'template_name' => 'Gastritis Diet Advice',
                'template_text' => 'Avoid spicy/oily food, tea/coffee excess, tobacco, and alcohol. Small frequent meals. Early dinner and avoid lying down immediately after meals.',
            ],
            [
                'section_key' => 'advice',
                'template_name' => 'Hypertension Follow-up Advice',
                'template_text' => 'Continue prescribed medicines regularly. Monitor BP at home and maintain log. Low-salt diet, regular walk, stress reduction. Follow-up in 2-4 weeks with BP chart.',
            ],
        ];

        foreach ($templates as $template) {
            $section = trim((string) ($template['section_key'] ?? ''));
            $name = trim((string) ($template['template_name'] ?? ''));
            $text = trim((string) ($template['template_text'] ?? ''));

            if ($section === '' || $name === '' || $text === '') {
                continue;
            }

            $existing = $this->db->table($table)
                ->where('doc_id', 0)
                ->where('section_key', $section)
                ->where('template_name', $name)
                ->get(1)
                ->getRowArray();

            if (! empty($existing)) {
                continue;
            }

            $insert = [
                'doc_id' => 0,
                'section_key' => $section,
                'template_name' => $name,
                'template_text' => $text,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (! in_array('is_active', $fields, true)) {
                unset($insert['is_active']);
            }
            if (! in_array('created_at', $fields, true)) {
                unset($insert['created_at']);
            }
            if (! in_array('updated_at', $fields, true)) {
                unset($insert['updated_at']);
            }

            $this->db->table($table)->insert($insert);
        }
    }
}
