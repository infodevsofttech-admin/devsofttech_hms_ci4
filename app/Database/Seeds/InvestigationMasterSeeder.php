<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds investigation master tests, investigation profiles, and profile-test mappings.
 *
 * Tables targeted:
 *   investigation   (Code INT PK, Name VARCHAR, short_name?, is_favourite?, spec_ids?)
 *   invprofiles     (Code INT PK, Name VARCHAR)
 *   invtprofiles    (id INT PK, ProfileCode INT, InvestigationCode INT, printOrder INT)
 */
class InvestigationMasterSeeder extends Seeder
{
    // ---------------------------------------------------------------
    // Master test list
    // ---------------------------------------------------------------
    private const TESTS = [
        // Haematology
        ['name' => 'CBC (Complete Blood Count)',            'short' => 'CBC',       'fav' => 1],
        ['name' => 'Haemoglobin (Hb)',                     'short' => 'Hb',        'fav' => 1],
        ['name' => 'ESR (Erythrocyte Sedimentation Rate)', 'short' => 'ESR',       'fav' => 0],
        ['name' => 'Peripheral Blood Smear',               'short' => 'PBS',       'fav' => 0],
        ['name' => 'Platelet Count',                       'short' => 'PLT',       'fav' => 0],
        ['name' => 'Reticulocyte Count',                   'short' => 'Retic',     'fav' => 0],
        ['name' => 'PT/INR',                               'short' => 'PT-INR',    'fav' => 0],
        ['name' => 'APTT',                                 'short' => 'APTT',      'fav' => 0],
        ['name' => 'D-Dimer',                              'short' => 'D-Dimer',   'fav' => 0],

        // Biochemistry
        ['name' => 'Blood Sugar Fasting (FBS)',            'short' => 'FBS',       'fav' => 1],
        ['name' => 'Blood Sugar Post Prandial (PPBS)',     'short' => 'PPBS',      'fav' => 1],
        ['name' => 'Random Blood Sugar (RBS)',             'short' => 'RBS',       'fav' => 1],
        ['name' => 'HbA1c (Glycated Haemoglobin)',         'short' => 'HbA1c',     'fav' => 1],
        ['name' => 'Renal Function Test (RFT)',            'short' => 'RFT',       'fav' => 1],
        ['name' => 'Blood Urea',                           'short' => 'Urea',      'fav' => 0],
        ['name' => 'Serum Creatinine',                     'short' => 'Creat',     'fav' => 1],
        ['name' => 'Serum Uric Acid',                      'short' => 'Uric Acid', 'fav' => 0],
        ['name' => 'Liver Function Test (LFT)',            'short' => 'LFT',       'fav' => 1],
        ['name' => 'SGOT (AST)',                           'short' => 'SGOT',      'fav' => 0],
        ['name' => 'SGPT (ALT)',                           'short' => 'SGPT',      'fav' => 0],
        ['name' => 'Serum Bilirubin (Total/Direct)',       'short' => 'Bili',      'fav' => 0],
        ['name' => 'Serum Alkaline Phosphatase (ALP)',     'short' => 'ALP',       'fav' => 0],
        ['name' => 'Serum Albumin',                        'short' => 'Albumin',   'fav' => 0],
        ['name' => 'Serum Electrolytes (Na/K/Cl)',         'short' => 'Electro',   'fav' => 1],
        ['name' => 'Serum Sodium',                         'short' => 'Na',        'fav' => 0],
        ['name' => 'Serum Potassium',                      'short' => 'K',         'fav' => 0],
        ['name' => 'Serum Calcium',                        'short' => 'Ca',        'fav' => 0],
        ['name' => 'Serum Phosphorus',                     'short' => 'Phos',      'fav' => 0],
        ['name' => 'Serum Magnesium',                      'short' => 'Mg',        'fav' => 0],

        // Lipid Profile
        ['name' => 'Lipid Profile',                        'short' => 'Lipid',     'fav' => 1],
        ['name' => 'Total Cholesterol',                    'short' => 'Chol',      'fav' => 0],
        ['name' => 'HDL Cholesterol',                      'short' => 'HDL',       'fav' => 0],
        ['name' => 'LDL Cholesterol',                      'short' => 'LDL',       'fav' => 0],
        ['name' => 'Triglycerides',                        'short' => 'TG',        'fav' => 0],

        // Thyroid
        ['name' => 'Thyroid Function Test (TFT)',          'short' => 'TFT',       'fav' => 1],
        ['name' => 'TSH (Thyroid Stimulating Hormone)',    'short' => 'TSH',       'fav' => 1],
        ['name' => 'T3 (Triiodothyronine)',                'short' => 'T3',        'fav' => 0],
        ['name' => 'T4 (Thyroxine)',                       'short' => 'T4',        'fav' => 0],

        // Cardiac
        ['name' => 'Troponin I (cTnI)',                    'short' => 'Troponin',  'fav' => 1],
        ['name' => 'CK-MB',                                'short' => 'CKMB',      'fav' => 0],
        ['name' => 'BNP / NT-proBNP',                      'short' => 'BNP',       'fav' => 0],
        ['name' => 'CRP (C-Reactive Protein)',             'short' => 'CRP',       'fav' => 1],

        // Infectious / Serology
        ['name' => 'Widal Test',                           'short' => 'Widal',     'fav' => 0],
        ['name' => 'Dengue NS1 Antigen',                   'short' => 'Dengue NS1','fav' => 1],
        ['name' => 'Dengue IgG/IgM',                      'short' => 'Dengue Ab', 'fav' => 0],
        ['name' => 'Malaria Card Test',                    'short' => 'Malaria',   'fav' => 1],
        ['name' => 'HIV I & II (Elisa)',                   'short' => 'HIV',       'fav' => 0],
        ['name' => 'HBsAg',                                'short' => 'HBsAg',     'fav' => 0],
        ['name' => 'Anti-HCV',                             'short' => 'HCV',       'fav' => 0],
        ['name' => 'VDRL / RPR',                           'short' => 'VDRL',      'fav' => 0],
        ['name' => 'COVID-19 Antigen',                     'short' => 'COVID Ag',  'fav' => 0],
        ['name' => 'Typhoid IgM (Typhidot)',               'short' => 'Typhidot',  'fav' => 0],

        // Urine
        ['name' => 'Urine Routine & Microscopy',           'short' => 'Urine R/M', 'fav' => 1],
        ['name' => 'Urine Culture & Sensitivity',          'short' => 'Urine C/S', 'fav' => 0],
        ['name' => 'Urine Protein (24hr)',                 'short' => '24h Prot',  'fav' => 0],
        ['name' => 'Urine Creatinine Ratio',               'short' => 'UCR',       'fav' => 0],
        ['name' => 'Urine Pregnancy Test (UPT)',           'short' => 'UPT',       'fav' => 0],

        // Radiology
        ['name' => 'X-Ray Chest (PA View)',                'short' => 'CXR',       'fav' => 1],
        ['name' => 'X-Ray Spine (L-S)',                    'short' => 'X-Ray LS',  'fav' => 0],
        ['name' => 'X-Ray KUB',                            'short' => 'KUB X-Ray', 'fav' => 0],
        ['name' => 'USG Abdomen & Pelvis',                 'short' => 'USG Abd',   'fav' => 1],
        ['name' => 'USG KUB',                              'short' => 'USG KUB',   'fav' => 0],
        ['name' => 'ECHO (Echocardiography)',               'short' => 'ECHO',      'fav' => 0],
        ['name' => 'CT Scan Head Plain',                   'short' => 'CT Head',   'fav' => 0],
        ['name' => 'MRI Brain with Contrast',              'short' => 'MRI Brain', 'fav' => 0],

        // Cardiac Procedures
        ['name' => 'ECG (Electrocardiogram)',               'short' => 'ECG',       'fav' => 1],
        ['name' => 'Holter Monitoring (24hr)',              'short' => 'Holter',    'fav' => 0],
        ['name' => 'Stress Test (TMT)',                     'short' => 'TMT',       'fav' => 0],

        // Hormones / Vitamins
        ['name' => 'Serum Vitamin D (25-OH)',              'short' => 'Vit D',     'fav' => 1],
        ['name' => 'Serum Vitamin B12',                    'short' => 'Vit B12',   'fav' => 1],
        ['name' => 'Serum Ferritin',                       'short' => 'Ferritin',  'fav' => 0],
        ['name' => 'Serum Iron / TIBC',                    'short' => 'Iron/TIBC', 'fav' => 0],
        ['name' => 'FSH / LH',                             'short' => 'FSH/LH',    'fav' => 0],
        ['name' => 'Prolactin',                            'short' => 'PRL',       'fav' => 0],
        ['name' => 'Testosterone (Total)',                 'short' => 'Testo',     'fav' => 0],
        ['name' => 'PSA (Prostate-Specific Antigen)',       'short' => 'PSA',       'fav' => 0],

        // Stool
        ['name' => 'Stool Routine & Microscopy',           'short' => 'Stool R/M', 'fav' => 0],
        ['name' => 'Stool Culture & Sensitivity',          'short' => 'Stool C/S', 'fav' => 0],
        ['name' => 'H. Pylori Antigen (Stool)',            'short' => 'H.Pylori',  'fav' => 0],

        // Sputum / Microbiology
        ['name' => 'Sputum AFB (for TB)',                  'short' => 'Sputum AFB','fav' => 0],
        ['name' => 'Sputum Culture & Sensitivity',        'short' => 'Sputum C/S','fav' => 0],
        ['name' => 'Blood Culture & Sensitivity',          'short' => 'Blood C/S', 'fav' => 0],

        // Paediatric-specific
        ['name' => 'Neonatal Bilirubin',                   'short' => 'Bili Neo',  'fav' => 0],
        ['name' => 'G6PD Assay',                           'short' => 'G6PD',      'fav' => 0],
    ];

    // ---------------------------------------------------------------
    // Profile definitions: [profile name => [test short names...]]
    // ---------------------------------------------------------------
    private const PROFILES = [
        'Fever Panel'             => ['CBC', 'ESR', 'CRP', 'Widal', 'Dengue NS1', 'Malaria', 'Typhidot', 'Urine R/M'],
        'Diabetic Monitoring'     => ['FBS', 'PPBS', 'HbA1c', 'RFT', 'Creat', 'Urine R/M', 'Lipid', 'Electro'],
        'Hypertension Panel'      => ['RFT', 'Electro', 'Lipid', 'Creat', 'Urine R/M', 'ECG', 'CXR'],
        'Renal Panel'             => ['RFT', 'Urea', 'Creat', 'Uric Acid', 'Electro', 'Urine R/M', 'Urine C/S', '24h Prot'],
        'Liver Panel'             => ['LFT', 'SGOT', 'SGPT', 'Bili', 'ALP', 'Albumin', 'HBsAg', 'HCV'],
        'Cardiac Risk Profile'    => ['CBC', 'Lipid', 'FBS', 'CRP', 'Troponin', 'ECG', 'ECHO'],
        'Thyroid Panel'           => ['TSH', 'T3', 'T4'],
        'Anaemia Panel'           => ['CBC', 'Hb', 'PBS', 'Retic', 'Ferritin', 'Iron/TIBC', 'Vit B12', 'Vit D'],
        'Pre-Operative Panel'     => ['CBC', 'RFT', 'LFT', 'PT-INR', 'APTT', 'Blood C/S', 'Urine R/M', 'ECG', 'CXR'],
        'Annual Health Check'     => ['CBC', 'FBS', 'HbA1c', 'Lipid', 'RFT', 'LFT', 'TFT', 'Urine R/M', 'CXR', 'ECG', 'Vit D', 'Vit B12'],
        'Vitamin Deficiency'      => ['Vit D', 'Vit B12', 'Ferritin', 'Iron/TIBC', 'Ca'],
        'Paediatric Fever'        => ['CBC', 'CRP', 'Dengue NS1', 'Malaria', 'Widal', 'Urine R/M'],
        'STI / Infectious Screen' => ['HIV', 'HBsAg', 'HCV', 'VDRL'],
        'Pregnancy Panel'         => ['CBC', 'FBS', 'TSH', 'HBsAg', 'HIV', 'VDRL', 'Urine R/M', 'UPT', 'Bili'],
        'Urinary Tract Infection' => ['Urine R/M', 'Urine C/S', 'Creat', 'RFT', 'USG KUB'],
    ];

    // ---------------------------------------------------------------
    // run()
    // ---------------------------------------------------------------
    public function run(): void
    {
        $this->ensureSeedLogTable();

        $insertedCodes = $this->seedInvestigations();
        $this->seedProfiles($insertedCodes);
    }

    // ---------------------------------------------------------------
    // Seed investigation master
    // Returns map: short_name => Code
    // ---------------------------------------------------------------
    private function seedInvestigations(): array
    {
        if (! $this->db->tableExists('investigation')) {
            return [];
        }

        $fields     = $this->db->getFieldNames('investigation');
        $codeField  = $this->resolveFirstField($fields, ['Code', 'code', 'id']);
        $nameField  = $this->resolveFirstField($fields, ['Name', 'name']);

        if ($codeField === null || $nameField === null) {
            return [];
        }

        $hasShort = in_array('short_name', $fields, true);
        $hasFav   = in_array('is_favourite', $fields, true);

        // Build short→Code map from existing rows so we can skip duplicates
        $existingRows = $this->db->table('investigation')
            ->select($codeField . ' as code, ' . $nameField . ' as name')
            ->get()->getResultArray();

        $existingByName = [];
        foreach ($existingRows as $row) {
            $existingByName[strtolower(trim((string) $row['name']))] = (int) $row['code'];
        }

        $shortToCode = [];

        foreach (self::TESTS as $test) {
            $normalised = strtolower(trim($test['name']));

            if (array_key_exists($normalised, $existingByName)) {
                // Already in DB — just record the code
                $shortToCode[$test['short']] = $existingByName[$normalised];
                continue;
            }

            $insert = [$nameField => $test['name']];
            if ($hasShort) {
                $insert['short_name'] = $test['short'];
            }
            if ($hasFav) {
                $insert['is_favourite'] = $test['fav'];
            }

            $this->db->table('investigation')->insert($insert);
            $newCode = (int) $this->db->insertID();
            $this->logInsertedRow('investigation', $newCode);
            $shortToCode[$test['short']] = $newCode;
        }

        return $shortToCode;
    }

    // ---------------------------------------------------------------
    // Seed invprofiles + invtprofiles
    // ---------------------------------------------------------------
    private function seedProfiles(array $shortToCode): void
    {
        if (! $this->db->tableExists('invprofiles') || ! $this->db->tableExists('invtprofiles')) {
            return;
        }

        $pFields    = $this->db->getFieldNames('invprofiles');
        $pCodeField = $this->resolveFirstField($pFields, ['Code', 'code', 'id']);
        $pNameField = $this->resolveFirstField($pFields, ['Name', 'name']);

        $tFields       = $this->db->getFieldNames('invtprofiles');
        $tProfField    = $this->resolveFirstField($tFields, ['ProfileCode', 'profile_code']);
        $tInvField     = $this->resolveFirstField($tFields, ['InvestigationCode', 'investigation_code']);
        $tOrderField   = $this->resolveFirstField($tFields, ['printOrder', 'print_order', 'order']);

        if ($pCodeField === null || $pNameField === null || $tProfField === null || $tInvField === null) {
            return;
        }

        foreach (self::PROFILES as $profileName => $testShorts) {
            // Check if profile already exists
            $existingProfile = $this->db->table('invprofiles')
                ->where($pNameField, $profileName)
                ->get(1)->getRowArray();

            if (! empty($existingProfile)) {
                continue; // Skip — already seeded
            }

            // Insert profile
            $this->db->table('invprofiles')->insert([$pNameField => $profileName]);
            $profileCode = (int) $this->db->insertID();
            $this->logInsertedRow('invprofiles', $profileCode);

            // Insert test links
            $order = 1;
            foreach ($testShorts as $short) {
                if (! isset($shortToCode[$short])) {
                    continue; // test not found — skip gracefully
                }

                $tInsert = [
                    $tProfField => $profileCode,
                    $tInvField  => $shortToCode[$short],
                ];
                if ($tOrderField !== null) {
                    $tInsert[$tOrderField] = $order;
                }
                $this->db->table('invtprofiles')->insert($tInsert);
                $this->logInsertedRow('invtprofiles', (int) $this->db->insertID());
                $order++;
            }
        }
    }

    // ---------------------------------------------------------------
    // Helpers (mirrors OpdDemoMasterSeeder pattern)
    // ---------------------------------------------------------------
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
            'seed_name'  => static::class,
            'table_name' => $tableName,
            'row_id'     => $rowId,
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
