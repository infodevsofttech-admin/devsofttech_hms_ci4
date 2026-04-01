<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OpdDemoMasterSeeder extends Seeder
{
    public function run()
    {
        $this->ensureSeedLogTable();
        $this->seedMedCompany();
        $this->seedOpdMedicineMaster();
        $this->seedRxGroupTemplate();
        $this->call(ClinicalTemplateWorkspaceSeeder::class);
        $this->call(InvestigationMasterSeeder::class);
    }

    private function seedMedCompany(): void
    {
        if (! $this->db->tableExists('med_company')) {
            return;
        }

        $table = 'med_company';
        $fields = $this->db->getFieldNames($table);
        $nameField = $this->resolveFirstField($fields, ['company_name', 'name', 'short_name']);
        if ($nameField === null) {
            return;
        }

        $companies = [
            'Sun Pharma',
            'Cipla',
            'Dr. Reddy\'s',
            'Torrent Pharma',
            'Mankind Pharma',
            'Lupin',
            'Alkem',
            'Abbott Healthcare',
            'Zydus Lifesciences',
            'Glenmark',
        ];

        foreach ($companies as $companyName) {
            $exists = $this->db->table($table)
                ->where($nameField, $companyName)
                ->get(1)
                ->getRowArray();
            if (! empty($exists)) {
                continue;
            }

            $insert = [$nameField => $companyName];
            if (in_array('active', $fields, true)) {
                $insert['active'] = 1;
            }
            $this->db->table($table)->insert($insert);
            $this->logInsertedRow($table, (int) $this->db->insertID());
        }
    }

    private function seedOpdMedicineMaster(): void
    {
        if (! $this->db->tableExists('opd_med_master')) {
            return;
        }

        $table = 'opd_med_master';
        $fields = $this->db->getFieldNames($table);
        $nameField = $this->resolveFirstField($fields, ['item_name', 'med_name']);
        if ($nameField === null) {
            return;
        }

        $formulationField = $this->resolveFirstField($fields, ['formulation']);
        $genericField = $this->resolveFirstField($fields, ['genericname', 'generic_name']);
        $saltField = $this->resolveFirstField($fields, ['salt_name', 'sal_name', 'salt', 'saltname']);
        $companyField = $this->resolveFirstField($fields, ['company_name', 'company']);

        $medicines = [
            ['name' => 'Paracetamol 650', 'formulation' => 'Tablet', 'generic' => 'Paracetamol', 'salt' => 'Paracetamol 650 mg', 'company' => 'Sun Pharma'],
            ['name' => 'Pantoprazole 40', 'formulation' => 'Tablet', 'generic' => 'Pantoprazole', 'salt' => 'Pantoprazole 40 mg', 'company' => 'Cipla'],
            ['name' => 'Amoxicillin Clavulanate 625', 'formulation' => 'Tablet', 'generic' => 'Amoxicillin + Clavulanic Acid', 'salt' => 'Amoxicillin 500 mg + Clavulanate 125 mg', 'company' => 'Alkem'],
            ['name' => 'Azithromycin 500', 'formulation' => 'Tablet', 'generic' => 'Azithromycin', 'salt' => 'Azithromycin 500 mg', 'company' => 'Lupin'],
            ['name' => 'Levocetirizine 5', 'formulation' => 'Tablet', 'generic' => 'Levocetirizine', 'salt' => 'Levocetirizine 5 mg', 'company' => 'Mankind Pharma'],
            ['name' => 'Montelukast 10', 'formulation' => 'Tablet', 'generic' => 'Montelukast', 'salt' => 'Montelukast 10 mg', 'company' => 'Torrent Pharma'],
            ['name' => 'Metformin 500', 'formulation' => 'Tablet', 'generic' => 'Metformin', 'salt' => 'Metformin 500 mg', 'company' => 'Zydus Lifesciences'],
            ['name' => 'Telmisartan 40', 'formulation' => 'Tablet', 'generic' => 'Telmisartan', 'salt' => 'Telmisartan 40 mg', 'company' => 'Dr. Reddy\'s'],
            ['name' => 'Amlodipine 5', 'formulation' => 'Tablet', 'generic' => 'Amlodipine', 'salt' => 'Amlodipine 5 mg', 'company' => 'Cipla'],
            ['name' => 'Rabeprazole 20', 'formulation' => 'Tablet', 'generic' => 'Rabeprazole', 'salt' => 'Rabeprazole 20 mg', 'company' => 'Abbott Healthcare'],
            ['name' => 'ORS Powder', 'formulation' => 'Sachet', 'generic' => 'Oral Rehydration Salts', 'salt' => 'WHO ORS Formula', 'company' => 'Glenmark'],
            ['name' => 'Cefixime 200', 'formulation' => 'Tablet', 'generic' => 'Cefixime', 'salt' => 'Cefixime 200 mg', 'company' => 'Lupin'],
        ];

        foreach ($medicines as $medicine) {
            $name = trim((string) ($medicine['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $exists = $this->db->table($table)->where($nameField, $name)->get(1)->getRowArray();
            if (! empty($exists)) {
                continue;
            }

            $insert = [$nameField => $name];
            if ($formulationField !== null) {
                $insert[$formulationField] = (string) ($medicine['formulation'] ?? '');
            }
            if ($genericField !== null) {
                $insert[$genericField] = (string) ($medicine['generic'] ?? '');
            }
            if ($saltField !== null) {
                $insert[$saltField] = (string) ($medicine['salt'] ?? '');
            }
            if ($companyField !== null) {
                $insert[$companyField] = (string) ($medicine['company'] ?? '');
            }

            $this->db->table($table)->insert($insert);
            $this->logInsertedRow($table, (int) $this->db->insertID());
        }
    }

    private function seedRxGroupTemplate(): void
    {
        if (! $this->db->tableExists('opd_prescription_template')) {
            return;
        }

        $table = 'opd_prescription_template';
        $fields = $this->db->getFieldNames($table);
        if (! in_array('rx_group_name', $fields, true)) {
            return;
        }

        $templates = [
            [
                'rx_group_name' => 'Fever Basic',
                'complaints' => 'Fever, body ache, headache',
                'diagnosis' => 'Acute febrile illness',
                'investigation' => 'CBC, CRP if fever >3 days',
                'finding' => 'Temp elevated, pulse mildly raised',
            ],
            [
                'rx_group_name' => 'Acid Peptic',
                'complaints' => 'Acidity, epigastric burning, bloating',
                'diagnosis' => 'Acid peptic disorder',
                'investigation' => 'CBC, LFT if persistent symptoms',
                'finding' => 'Epigastric tenderness mild',
            ],
            [
                'rx_group_name' => 'URTI Adult',
                'complaints' => 'Sore throat, cough, cold',
                'diagnosis' => 'Upper respiratory tract infection',
                'investigation' => 'CBC if prolonged fever',
                'finding' => 'Throat congestion, no severe chest signs',
            ],
            [
                'rx_group_name' => 'Hypertension Followup',
                'complaints' => 'Routine follow-up, occasional headache',
                'diagnosis' => 'Essential hypertension',
                'investigation' => 'BP charting, RFT, lipid profile',
                'finding' => 'BP mildly elevated',
            ],
            [
                'rx_group_name' => 'Diabetes Followup',
                'complaints' => 'Polyuria, fatigue on exertion',
                'diagnosis' => 'Type 2 diabetes mellitus',
                'investigation' => 'FBS, PPBS, HbA1c, RFT',
                'finding' => 'Random glucose elevated',
            ],
        ];

        foreach ($templates as $template) {
            $name = trim((string) ($template['rx_group_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $existsBuilder = $this->db->table($table)->where('rx_group_name', $name);
            if (in_array('doc_id', $fields, true)) {
                $existsBuilder->where('doc_id', 0);
            }
            $exists = $existsBuilder->get(1)->getRowArray();
            if (! empty($exists)) {
                continue;
            }

            $insert = ['rx_group_name' => $name];
            if (in_array('complaints', $fields, true)) {
                $insert['complaints'] = (string) ($template['complaints'] ?? '');
            }
            if (in_array('diagnosis', $fields, true)) {
                $insert['diagnosis'] = (string) ($template['diagnosis'] ?? '');
            }
            if (in_array('investigation', $fields, true)) {
                $insert['investigation'] = (string) ($template['investigation'] ?? '');
            }
            if (in_array('Finding_Examinations', $fields, true)) {
                $insert['Finding_Examinations'] = (string) ($template['finding'] ?? '');
            }
            if (in_array('doc_id', $fields, true)) {
                $insert['doc_id'] = 0;
            }
            if (in_array('update_by', $fields, true)) {
                $insert['update_by'] = 'Seeder[0]:' . date('d-m-Y H:i:s');
            }

            $this->db->table($table)->insert($insert);
            $this->logInsertedRow($table, (int) $this->db->insertID());
        }
    }

    private function ensureSeedLogTable(): void
    {
        if ($this->db->tableExists('ai_demo_seed_log')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS ai_demo_seed_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seed_name VARCHAR(120) NOT NULL,
            table_name VARCHAR(120) NOT NULL,
            row_id INT NOT NULL,
            created_at DATETIME NULL,
            INDEX idx_seed_name (seed_name),
            INDEX idx_table_row (table_name, row_id)
        )");
    }

    private function logInsertedRow(string $tableName, int $rowId): void
    {
        if ($rowId <= 0 || ! $this->db->tableExists('ai_demo_seed_log')) {
            return;
        }

        $this->db->table('ai_demo_seed_log')->insert([
            'seed_name' => static::class,
            'table_name' => $tableName,
            'row_id' => $rowId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function resolveFirstField(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
