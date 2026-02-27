<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OpdDemoLargeSeeder extends Seeder
{
    public function run()
    {
        $this->ensureSeedLogTable();
        $this->seedCompanyPool();
        $this->seedLargeMedicinePool(260);
        $this->seedLargeRxGroupPool(60);
    }

    private function seedCompanyPool(): void
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

        $companyNames = [
            'Apex Biocare',
            'Nova Remedies',
            'Aster Lifeline',
            'Helix Pharma',
            'VitaGen Labs',
            'CareNova Healthcare',
            'Pulse Therapeutics',
            'Zenith Medix',
            'PrimeCure',
            'BlueLeaf Pharma',
            'Medisphere',
            'Nexora Pharma',
            'Healmark',
            'Synovia Life',
            'Medqube',
            'Clariant Healthcare',
            'Biovance',
            'Orion Remedies',
            'TruMeds',
            'EverCure',
            'NeutraCare',
            'Axion Pharma',
            'Wellnest Labs',
            'Maxiva',
            'TheraWell',
        ];

        foreach ($companyNames as $companyName) {
            $exists = $this->db->table($table)->where($nameField, $companyName)->get(1)->getRowArray();
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

    private function seedLargeMedicinePool(int $targetInsertCount = 260): void
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

        $existingNames = [];
        $existingRows = $this->db->table($table)->select($nameField . ' as med_name')->get()->getResultArray();
        foreach ($existingRows as $existingRow) {
            $normalized = mb_strtolower(trim((string) ($existingRow['med_name'] ?? '')));
            if ($normalized !== '') {
                $existingNames[$normalized] = true;
            }
        }

        $companies = [
            'Sun Pharma', 'Cipla', 'Dr. Reddy\'s', 'Torrent Pharma', 'Mankind Pharma',
            'Lupin', 'Alkem', 'Abbott Healthcare', 'Zydus Lifesciences', 'Glenmark',
            'Apex Biocare', 'Nova Remedies', 'Aster Lifeline', 'Helix Pharma', 'VitaGen Labs',
        ];

        $catalog = [
            ['generic' => 'Paracetamol', 'formulation' => 'Tablet', 'strengths' => ['500', '650']],
            ['generic' => 'Ibuprofen', 'formulation' => 'Tablet', 'strengths' => ['200', '400']],
            ['generic' => 'Aceclofenac', 'formulation' => 'Tablet', 'strengths' => ['100']],
            ['generic' => 'Diclofenac', 'formulation' => 'Tablet', 'strengths' => ['50']],
            ['generic' => 'Pantoprazole', 'formulation' => 'Tablet', 'strengths' => ['20', '40']],
            ['generic' => 'Rabeprazole', 'formulation' => 'Tablet', 'strengths' => ['20']],
            ['generic' => 'Omeprazole', 'formulation' => 'Capsule', 'strengths' => ['20']],
            ['generic' => 'Esomeprazole', 'formulation' => 'Tablet', 'strengths' => ['40']],
            ['generic' => 'Domperidone', 'formulation' => 'Tablet', 'strengths' => ['10']],
            ['generic' => 'Ondansetron', 'formulation' => 'Tablet', 'strengths' => ['4', '8']],
            ['generic' => 'Levocetirizine', 'formulation' => 'Tablet', 'strengths' => ['5']],
            ['generic' => 'Cetirizine', 'formulation' => 'Tablet', 'strengths' => ['10']],
            ['generic' => 'Fexofenadine', 'formulation' => 'Tablet', 'strengths' => ['120']],
            ['generic' => 'Montelukast', 'formulation' => 'Tablet', 'strengths' => ['10']],
            ['generic' => 'Azithromycin', 'formulation' => 'Tablet', 'strengths' => ['250', '500']],
            ['generic' => 'Amoxicillin', 'formulation' => 'Capsule', 'strengths' => ['500']],
            ['generic' => 'Cefixime', 'formulation' => 'Tablet', 'strengths' => ['200']],
            ['generic' => 'Cefpodoxime', 'formulation' => 'Tablet', 'strengths' => ['200']],
            ['generic' => 'Doxycycline', 'formulation' => 'Capsule', 'strengths' => ['100']],
            ['generic' => 'Metformin', 'formulation' => 'Tablet', 'strengths' => ['500', '850']],
            ['generic' => 'Glimepiride', 'formulation' => 'Tablet', 'strengths' => ['1', '2']],
            ['generic' => 'Vildagliptin', 'formulation' => 'Tablet', 'strengths' => ['50']],
            ['generic' => 'Sitagliptin', 'formulation' => 'Tablet', 'strengths' => ['100']],
            ['generic' => 'Telmisartan', 'formulation' => 'Tablet', 'strengths' => ['40', '80']],
            ['generic' => 'Amlodipine', 'formulation' => 'Tablet', 'strengths' => ['5', '10']],
            ['generic' => 'Losartan', 'formulation' => 'Tablet', 'strengths' => ['50']],
            ['generic' => 'Metoprolol', 'formulation' => 'Tablet', 'strengths' => ['25', '50']],
            ['generic' => 'Atorvastatin', 'formulation' => 'Tablet', 'strengths' => ['10', '20']],
            ['generic' => 'Rosuvastatin', 'formulation' => 'Tablet', 'strengths' => ['10', '20']],
            ['generic' => 'Clopidogrel', 'formulation' => 'Tablet', 'strengths' => ['75']],
            ['generic' => 'Aspirin', 'formulation' => 'Tablet', 'strengths' => ['75']],
            ['generic' => 'Iron Folic Acid', 'formulation' => 'Tablet', 'strengths' => ['100']],
            ['generic' => 'Calcium Vitamin D3', 'formulation' => 'Tablet', 'strengths' => ['500']],
            ['generic' => 'Vitamin B Complex', 'formulation' => 'Capsule', 'strengths' => ['1']],
            ['generic' => 'Methylcobalamin', 'formulation' => 'Tablet', 'strengths' => ['1500']],
            ['generic' => 'ORS', 'formulation' => 'Sachet', 'strengths' => ['WHO']],
            ['generic' => 'Lactobacillus', 'formulation' => 'Capsule', 'strengths' => ['5B']],
            ['generic' => 'Dicyclomine', 'formulation' => 'Tablet', 'strengths' => ['20']],
            ['generic' => 'Loperamide', 'formulation' => 'Capsule', 'strengths' => ['2']],
            ['generic' => 'Albendazole', 'formulation' => 'Tablet', 'strengths' => ['400']],
            ['generic' => 'Fluconazole', 'formulation' => 'Tablet', 'strengths' => ['150']],
            ['generic' => 'Clotrimazole', 'formulation' => 'Cream', 'strengths' => ['1%']],
            ['generic' => 'Hydrocortisone', 'formulation' => 'Cream', 'strengths' => ['1%']],
            ['generic' => 'Mupirocin', 'formulation' => 'Ointment', 'strengths' => ['2%']],
        ];

        $brandTokens = ['Prime', 'Forte', 'Plus', 'Care', 'Advance', 'Max', 'Neo'];
        $insertedCount = 0;
        $companyIndex = 0;

        foreach ($catalog as $item) {
            $generic = (string) ($item['generic'] ?? '');
            $formulation = (string) ($item['formulation'] ?? 'Tablet');
            $strengths = is_array($item['strengths'] ?? null) ? $item['strengths'] : [];
            if ($generic === '' || empty($strengths)) {
                continue;
            }

            foreach ($strengths as $strength) {
                foreach ($brandTokens as $token) {
                    if ($insertedCount >= $targetInsertCount) {
                        break 3;
                    }

                    $name = trim($generic . ' ' . $strength . ' ' . $token);
                    $normalized = mb_strtolower($name);
                    if ($name === '' || isset($existingNames[$normalized])) {
                        continue;
                    }

                    $insert = [$nameField => $name];
                    if ($formulationField !== null) {
                        $insert[$formulationField] = $formulation;
                    }
                    if ($genericField !== null) {
                        $insert[$genericField] = $generic;
                    }
                    if ($saltField !== null) {
                        $insert[$saltField] = $generic . ' ' . $strength;
                    }
                    if ($companyField !== null) {
                        $insert[$companyField] = $companies[$companyIndex % count($companies)];
                        $companyIndex++;
                    }

                    $this->db->table($table)->insert($insert);
                    $this->logInsertedRow($table, (int) $this->db->insertID());
                    $existingNames[$normalized] = true;
                    $insertedCount++;
                }
            }
        }
    }

    private function seedLargeRxGroupPool(int $targetInsertCount = 60): void
    {
        if (! $this->db->tableExists('opd_prescription_template')) {
            return;
        }

        $table = 'opd_prescription_template';
        $fields = $this->db->getFieldNames($table);
        if (! in_array('rx_group_name', $fields, true)) {
            return;
        }

        $groups = [
            'Fever Adult Followup',
            'Fever Pediatric',
            'Acid Peptic Disease',
            'Gastritis Acute',
            'URTI Basic',
            'URTI Cough Focus',
            'Allergic Rhinitis',
            'Acute Diarrhea',
            'Viral Gastroenteritis',
            'Hypertension Stage 1',
            'Hypertension Followup Stable',
            'Diabetes New Case',
            'Diabetes Followup Stable',
            'Diabetes Neuropathy',
            'Migraine Acute',
            'Tension Headache',
            'Low Back Pain',
            'Knee Osteoarthritis',
            'UTI Uncomplicated',
            'Skin Fungal Infection',
            'Skin Allergy Itching',
            'Anemia Mild',
            'Vitamin Deficiency',
            'General Weakness',
            'Dyslipidemia Followup',
            'Acute Bronchitis',
            'Sinusitis',
            'Pharyngitis',
            'Post Viral Fatigue',
            'Monsoon Fever Bundle',
            'Winter Cough Bundle',
            'Summer Dehydration',
            'Geriatric BP Followup',
            'Geriatric DM Followup',
            'Women Health General',
            'Menstrual Pain Relief',
            'Pregnancy Nausea Support',
            'Pediatric URI Followup',
            'Constipation Adult',
            'Piles Symptomatic',
            'GERD Chronic',
            'Vertigo Symptomatic',
            'Insomnia Supportive',
            'Anxiety Mild Supportive',
            'Thyroid Followup',
            'Obesity Lifestyle Starter',
            'Smoking Cessation Support',
            'High Uric Acid Followup',
            'Renal Colic Initial',
            'Dental Pain Symptomatic',
            'Ear Pain Acute',
            'Eye Redness Basic',
            'Post Op Dressing Followup',
            'Injury Soft Tissue',
            'Travel Illness Starter',
            'Monsoon GI Prevention',
            'Flu Like Illness',
            'Seasonal Allergy Followup',
            'General OPD Starter',
            'General OPD Followup',
        ];

        $insertedCount = 0;
        foreach ($groups as $groupName) {
            if ($insertedCount >= $targetInsertCount) {
                break;
            }

            $name = trim($groupName);
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
                $insert['complaints'] = 'Synthetic demo template for testing';
            }
            if (in_array('diagnosis', $fields, true)) {
                $insert['diagnosis'] = 'Demo diagnosis profile';
            }
            if (in_array('investigation', $fields, true)) {
                $insert['investigation'] = 'CBC, RFT, LFT as clinically indicated';
            }
            if (in_array('Finding_Examinations', $fields, true)) {
                $insert['Finding_Examinations'] = 'Vitals stable, systemic exam as per case';
            }
            if (in_array('doc_id', $fields, true)) {
                $insert['doc_id'] = 0;
            }
            if (in_array('update_by', $fields, true)) {
                $insert['update_by'] = 'Seeder[0]:' . date('d-m-Y H:i:s');
            }

            $this->db->table($table)->insert($insert);
            $this->logInsertedRow($table, (int) $this->db->insertID());
            $insertedCount++;
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
