<?php

namespace App\Database\Seeds\Data;

/**
 * Standard Pathology Panels, Tests, Reference Intervals, Units, and LOINC codes
 * for General and Multispeciality Hospitals.
 */
class PathologySeedData
{
    public static function getPanels(): array
    {
        return [
            // -------------------------------------------------------------
            // 1. COMPLETE BLOOD COUNT (CBC) WITH ESR
            // -------------------------------------------------------------
            [
                'title'          => 'Complete Blood Count (CBC) with ESR',
                'grp_key'        => 6, // Hematology
                'loinc_code'     => '58410-2',
                'charge_desc'    => 'COMPLETE BLOOD COUNT (CBC) WITH ESR',
                'charge_amount'  => 350.00,
                'interpretation' => 'Complete Blood Count (CBC) provides vital clinical information regarding red blood cells, white blood cells, and platelets. An automated 5-part hematology analyzer is utilized with peripheral blood smear microscopic examination. Correlate with clinical findings.',
                'tests'          => [
                    ['testid' => 'HB',       'name' => 'Hemoglobin',                             'unit' => 'g/dL',     'loinc' => '718-7',   'norm' => '13.0 - 17.0', 'norm_w' => '12.0 - 15.0', 'gender' => 1],
                    ['testid' => 'TLC',      'name' => 'Total Leukocyte Count (TLC)',             'unit' => '/cumm',    'loinc' => '6690-2',  'norm' => '4000 - 11000', 'norm_w' => '4000 - 11000', 'gender' => 0],
                    ['testid' => 'RBC',      'name' => 'Red Blood Cell (RBC) Count',              'unit' => 'mil/cumm', 'loinc' => '789-8',   'norm' => '4.5 - 5.5',   'norm_w' => '3.8 - 4.8',   'gender' => 1],
                    ['testid' => 'PCV',      'name' => 'Packed Cell Volume (PCV / Hematocrit)',   'unit' => '%',        'loinc' => '4544-3',  'norm' => '40.0 - 50.0', 'norm_w' => '36.0 - 46.0', 'gender' => 1],
                    ['testid' => 'MCV',      'name' => 'Mean Corpuscular Volume (MCV)',           'unit' => 'fL',       'loinc' => '8457-4',  'norm' => '80.0 - 100.0','norm_w' => '80.0 - 100.0','gender' => 0],
                    ['testid' => 'MCH',      'name' => 'Mean Corpuscular Hemoglobin (MCH)',       'unit' => 'pg',       'loinc' => '785-6',   'norm' => '27.0 - 32.0', 'norm_w' => '27.0 - 32.0', 'gender' => 0],
                    ['testid' => 'MCHC',     'name' => 'MCHC',                                    'unit' => 'g/dL',     'loinc' => '59260-0', 'norm' => '32.0 - 36.0', 'norm_w' => '32.0 - 36.0', 'gender' => 0],
                    ['testid' => 'RDW',      'name' => 'Red Cell Distribution Width (RDW-CV)',    'unit' => '%',        'loinc' => '788-0',   'norm' => '11.5 - 14.5', 'norm_w' => '11.5 - 14.5', 'gender' => 0],
                    ['testid' => 'PLATELET', 'name' => 'Platelet Count',                          'unit' => '/cumm',    'loinc' => '777-3',   'norm' => '150000 - 450000', 'norm_w' => '150000 - 450000', 'gender' => 0],
                    ['testid' => 'NEU',      'name' => 'Neutrophils',                             'unit' => '%',        'loinc' => '770-8',   'norm' => '40 - 75',     'norm_w' => '40 - 75',     'gender' => 0],
                    ['testid' => 'LYMP',     'name' => 'Lymphocytes',                             'unit' => '%',        'loinc' => '736-9',   'norm' => '20 - 45',     'norm_w' => '20 - 45',     'gender' => 0],
                    ['testid' => 'EOSIN',    'name' => 'Eosinophils',                             'unit' => '%',        'loinc' => '713-8',   'norm' => '1 - 6',       'norm_w' => '1 - 6',       'gender' => 0],
                    ['testid' => 'MONO',     'name' => 'Monocytes',                               'unit' => '%',        'loinc' => '5905-5',  'norm' => '2 - 10',      'norm_w' => '2 - 10',      'gender' => 0],
                    ['testid' => 'BASO',     'name' => 'Basophils',                               'unit' => '%',        'loinc' => '706-2',   'norm' => '0 - 1',       'norm_w' => '0 - 1',       'gender' => 0],
                    ['testid' => 'CBC_ESR',  'name' => 'Erythrocyte Sedimentation Rate (ESR)',    'unit' => 'mm/1st hr','loinc' => '30341-2', 'norm' => '0 - 15',      'norm_w' => '0 - 20',      'gender' => 1],
                ],
            ],

            // -------------------------------------------------------------
            // 2. LIVER FUNCTION TEST (LFT)
            // -------------------------------------------------------------
            [
                'title'          => 'Liver Function Test (LFT)',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24325-3',
                'charge_desc'    => 'LIVER FUNCTION TEST (LFT)',
                'charge_amount'  => 650.00,
                'interpretation' => 'Liver Function Tests evaluate hepatic synthetic capacity, biliary excretion, and hepatocellular integrity. Elevated transaminases (AST/ALT) suggest hepatocellular injury; elevated Alkaline Phosphatase with Bilirubin suggests cholestatic or infiltrative hepatobiliary pattern.',
                'tests'          => [
                    ['testid' => 'LFT_TBIL', 'name' => 'Bilirubin - Total',          'unit' => 'mg/dL', 'loinc' => '1975-2',  'norm' => '0.2 - 1.2',  'norm_w' => '0.2 - 1.2',  'gender' => 0],
                    ['testid' => 'LFT_DBIL', 'name' => 'Bilirubin - Direct',         'unit' => 'mg/dL', 'loinc' => '1968-7',  'norm' => '0.0 - 0.3',  'norm_w' => '0.0 - 0.3',  'gender' => 0],
                    ['testid' => 'LFT_IBIL', 'name' => 'Bilirubin - Indirect',       'unit' => 'mg/dL', 'loinc' => '1971-1',  'norm' => '0.2 - 0.8',  'norm_w' => '0.2 - 0.8',  'gender' => 0],
                    ['testid' => 'LFT_SGOT', 'name' => 'SGOT / AST',                 'unit' => 'U/L',   'loinc' => '1920-8',  'norm' => '5 - 40',     'norm_w' => '5 - 35',     'gender' => 1],
                    ['testid' => 'LFT_SGPT', 'name' => 'SGPT / ALT',                 'unit' => 'U/L',   'loinc' => '1742-6',  'norm' => '5 - 45',     'norm_w' => '5 - 35',     'gender' => 1],
                    ['testid' => 'LFT_ALP',  'name' => 'Alkaline Phosphatase (ALP)', 'unit' => 'U/L',   'loinc' => '6768-6',  'norm' => '40 - 130',   'norm_w' => '35 - 105',   'gender' => 1],
                    ['testid' => 'LFT_GGTP', 'name' => 'Gamma GT (GGT)',             'unit' => 'U/L',   'loinc' => '2324-2',  'norm' => '10 - 50',    'norm_w' => '7 - 35',     'gender' => 1],
                    ['testid' => 'LFT_TP',   'name' => 'Total Protein',              'unit' => 'g/dL',  'loinc' => '2885-2',  'norm' => '6.4 - 8.3',  'norm_w' => '6.4 - 8.3',  'gender' => 0],
                    ['testid' => 'LFT_ALB',  'name' => 'Albumin',                    'unit' => 'g/dL',  'loinc' => '1751-7',  'norm' => '3.5 - 5.2',  'norm_w' => '3.5 - 5.2',  'gender' => 0],
                    ['testid' => 'LFT_GLOB', 'name' => 'Globulin',                   'unit' => 'g/dL',  'loinc' => '10834-0', 'norm' => '2.0 - 3.5',  'norm_w' => '2.0 - 3.5',  'gender' => 0],
                    ['testid' => 'LFT_AG',   'name' => 'A/G Ratio',                  'unit' => 'Ratio', 'loinc' => '1759-0',  'norm' => '1.2 - 2.2',  'norm_w' => '1.2 - 2.2',  'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 3. KIDNEY FUNCTION TEST (KFT / RFT)
            // -------------------------------------------------------------
            [
                'title'          => 'Kidney Function Test (KFT / RFT)',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24362-6',
                'charge_desc'    => 'KIDNEY FUNCTION TEST (KFT / RFT)',
                'charge_amount'  => 650.00,
                'interpretation' => 'Serum Creatinine and Blood Urea are primary markers of renal filtration and glomerular function. Serum Electrolytes, Calcium, Phosphorus, and Uric Acid assist in assessing fluid-electrolyte and mineral homeostasis.',
                'tests'          => [
                    ['testid' => 'KFT_UREA',  'name' => 'Blood Urea',             'unit' => 'mg/dL', 'loinc' => '3094-0',  'norm' => '15 - 45',    'norm_w' => '15 - 40',    'gender' => 1],
                    ['testid' => 'KFT_BUN',   'name' => 'Blood Urea Nitrogen',    'unit' => 'mg/dL', 'loinc' => '12964-3', 'norm' => '7 - 20',     'norm_w' => '7 - 20',     'gender' => 0],
                    ['testid' => 'KFT_CREAT', 'name' => 'Serum Creatinine',       'unit' => 'mg/dL', 'loinc' => '2160-0',  'norm' => '0.7 - 1.3',  'norm_w' => '0.6 - 1.1',  'gender' => 1],
                    ['testid' => 'KFT_URIC',  'name' => 'Serum Uric Acid',        'unit' => 'mg/dL', 'loinc' => '3084-1',  'norm' => '3.5 - 7.2',  'norm_w' => '2.6 - 6.0',  'gender' => 1],
                    ['testid' => 'KFT_CA',    'name' => 'Serum Calcium (Total)',  'unit' => 'mg/dL', 'loinc' => '17861-6', 'norm' => '8.8 - 10.2', 'norm_w' => '8.8 - 10.2', 'gender' => 0],
                    ['testid' => 'KFT_PHOS',  'name' => 'Serum Phosphorus',       'unit' => 'mg/dL', 'loinc' => '2777-1',  'norm' => '2.5 - 4.5',  'norm_w' => '2.5 - 4.5',  'gender' => 0],
                    ['testid' => 'KFT_NA',    'name' => 'Serum Sodium (Na+)',     'unit' => 'mEq/L', 'loinc' => '2951-2',  'norm' => '135 - 145',  'norm_w' => '135 - 145',  'gender' => 0],
                    ['testid' => 'KFT_K',     'name' => 'Serum Potassium (K+)',   'unit' => 'mEq/L', 'loinc' => '2823-3',  'norm' => '3.5 - 5.1',  'norm_w' => '3.5 - 5.1',  'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 4. LIPID PROFILE (COMPLETE)
            // -------------------------------------------------------------
            [
                'title'          => 'Lipid Profile (Complete)',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '57698-3',
                'charge_desc'    => 'LIPID PROFILE (COMPLETE)',
                'charge_amount'  => 550.00,
                'interpretation' => 'Lipid profile assesses atherosclerotic cardiovascular risk according to NCEP ATP-III guidelines. Fasting of 10-12 hours is recommended. Optimal LDL: < 100 mg/dL; Desirable Cholesterol: < 200 mg/dL; Normal Triglycerides: < 150 mg/dL.',
                'tests'          => [
                    ['testid' => 'LIP_CHOL',  'name' => 'Total Cholesterol',       'unit' => 'mg/dL', 'loinc' => '2093-3',  'norm' => '< 200',      'norm_w' => '< 200',      'gender' => 0],
                    ['testid' => 'LIP_TRIG',  'name' => 'Serum Triglycerides',     'unit' => 'mg/dL', 'loinc' => '2571-8',  'norm' => '< 150',      'norm_w' => '< 150',      'gender' => 0],
                    ['testid' => 'LIP_HDL',   'name' => 'HDL Cholesterol',         'unit' => 'mg/dL', 'loinc' => '2085-9',  'norm' => '> 40',       'norm_w' => '> 50',       'gender' => 1],
                    ['testid' => 'LIP_LDL',   'name' => 'LDL Cholesterol',         'unit' => 'mg/dL', 'loinc' => '13457-7', 'norm' => '< 100',      'norm_w' => '< 100',      'gender' => 0],
                    ['testid' => 'LIP_VLDL',  'name' => 'VLDL Cholesterol',        'unit' => 'mg/dL', 'loinc' => '13458-5', 'norm' => '5 - 30',     'norm_w' => '5 - 30',     'gender' => 0],
                    ['testid' => 'LIP_CH_HD', 'name' => 'Total Cholesterol / HDL', 'unit' => 'Ratio', 'loinc' => '9830-1',  'norm' => '3.0 - 5.0',  'norm_w' => '3.0 - 4.5',  'gender' => 1],
                    ['testid' => 'LIP_LD_HD', 'name' => 'LDL / HDL Ratio',         'unit' => 'Ratio', 'loinc' => '11054-4', 'norm' => '1.5 - 3.5',  'norm_w' => '1.5 - 3.0',  'gender' => 1],
                ],
            ],

            // -------------------------------------------------------------
            // 5. THYROID PROFILE (TOTAL T3, T4, TSH)
            // -------------------------------------------------------------
            [
                'title'          => 'Thyroid Profile (Total T3, T4, TSH)',
                'grp_key'        => 13, // Endocrinology
                'loinc_code'     => '62243-1',
                'charge_desc'    => 'THYROID PROFILE (TOTAL T3, T4, TSH)',
                'charge_amount'  => 500.00,
                'interpretation' => 'TSH is the most sensitive screening parameter for thyroid dysfunction. High TSH with low T3/T4 indicates Primary Hypothyroidism; low TSH with high T3/T4 indicates Hyperthyroidism. In pregnancy, trimester-specific reference ranges apply.',
                'tests'          => [
                    ['testid' => 'THY_T3',  'name' => 'Total Triiodothyronine (T3)', 'unit' => 'ng/dL',  'loinc' => '3053-6', 'norm' => '80 - 200',   'norm_w' => '80 - 200',   'gender' => 0],
                    ['testid' => 'THY_T4',  'name' => 'Total Thyroxine (T4)',        'unit' => 'ug/dL',  'loinc' => '3026-2', 'norm' => '4.8 - 12.0', 'norm_w' => '4.8 - 12.0', 'gender' => 0],
                    ['testid' => 'THY_TSH', 'name' => 'Thyroid Stimulating Hormone', 'unit' => 'uIU/mL', 'loinc' => '3016-3', 'norm' => '0.40 - 4.50', 'norm_w' => '0.40 - 4.50', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 6. FREE THYROID PROFILE (FT3, FT4, TSH)
            // -------------------------------------------------------------
            [
                'title'          => 'Free Thyroid Profile (FT3, FT4, TSH)',
                'grp_key'        => 13, // Endocrinology
                'loinc_code'     => '74794-9',
                'charge_desc'    => 'FREE THYROID PROFILE (FT3, FT4, TSH)',
                'charge_amount'  => 750.00,
                'interpretation' => 'Free T3 and Free T4 represent the biologically active unbound thyroid hormones and are unaffected by alterations in thyroid binding globulin (TBG) seen in pregnancy, estrogen therapy, or nephrotic syndrome.',
                'tests'          => [
                    ['testid' => 'THY_FT3', 'name' => 'Free Triiodothyronine (FT3)', 'unit' => 'pg/mL',  'loinc' => '3051-0', 'norm' => '2.0 - 4.4',   'norm_w' => '2.0 - 4.4',   'gender' => 0],
                    ['testid' => 'THY_FT4', 'name' => 'Free Thyroxine (FT4)',        'unit' => 'ng/dL',  'loinc' => '2284-8', 'norm' => '0.93 - 1.70', 'norm_w' => '0.93 - 1.70', 'gender' => 0],
                    ['testid' => 'THY_FTSH','name' => 'TSH (Ultrasensitive)',        'unit' => 'uIU/mL', 'loinc' => '3016-3', 'norm' => '0.40 - 4.50', 'norm_w' => '0.40 - 4.50', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 7. DIABETIC PROFILE & GLYCATED HEMOGLOBIN (HbA1c)
            // -------------------------------------------------------------
            [
                'title'          => 'Diabetic Profile & Glycated Hemoglobin (HbA1c)',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '4548-4',
                'charge_desc'    => 'DIABETIC PROFILE & GLYCATED HEMOGLOBIN (HBA1C)',
                'charge_amount'  => 600.00,
                'interpretation' => 'ADA Criteria: HbA1c < 5.7% Normal; 5.7 - 6.4% Prediabetes (Impaired Glucose Tolerance); >= 6.5% Diagnostic of Diabetes Mellitus. In diagnosed diabetics, therapeutic target is generally < 7.0%.',
                'tests'          => [
                    ['testid' => 'DIA_FBS',   'name' => 'Fasting Blood Glucose',       'unit' => 'mg/dL', 'loinc' => '1558-6', 'norm' => '70 - 100',   'norm_w' => '70 - 100',   'gender' => 0],
                    ['testid' => 'DIA_PPBS',  'name' => 'Post-Prandial Blood Glucose', 'unit' => 'mg/dL', 'loinc' => '1521-4', 'norm' => '< 140',     'norm_w' => '< 140',     'gender' => 0],
                    ['testid' => 'DIA_RBS',   'name' => 'Random Blood Glucose',        'unit' => 'mg/dL', 'loinc' => '2345-7', 'norm' => '70 - 140',   'norm_w' => '70 - 140',   'gender' => 0],
                    ['testid' => 'DIA_HBA1C', 'name' => 'HbA1c (Glycated Hb)',        'unit' => '%',     'loinc' => '4548-4', 'norm' => '< 5.7',      'norm_w' => '< 5.7',      'gender' => 0],
                    ['testid' => 'DIA_EAG',   'name' => 'Estimated Average Glucose',   'unit' => 'mg/dL', 'loinc' => '2708-6', 'norm' => '90 - 120',   'norm_w' => '90 - 120',   'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 8. URINE ROUTINE & MICROSCOPIC EXAMINATION
            // -------------------------------------------------------------
            [
                'title'          => 'Urine Routine & Microscopic Examination',
                'grp_key'        => 8, // Urine
                'loinc_code'     => '24356-8',
                'charge_desc'    => 'URINE ROUTINE & MICROSCOPIC EXAMINATION',
                'charge_amount'  => 150.00,
                'interpretation' => 'Clean-catch midstream urine sample examined physically, chemically (dipstick/reagent), and sediment microscopically at high power field (HPF). Presence of > 5 pus cells/HPF indicates pyuria / urinary tract infection.',
                'tests'          => [
                    ['testid' => 'URN_COL',  'name' => 'Color',                 'unit' => '',      'loinc' => '5778-6',  'norm' => 'Pale Yellow',   'norm_w' => 'Pale Yellow',   'gender' => 0],
                    ['testid' => 'URN_APP',  'name' => 'Appearance / Clarity',  'unit' => '',      'loinc' => '5767-9',  'norm' => 'Clear',         'norm_w' => 'Clear',         'gender' => 0],
                    ['testid' => 'URN_SPG',  'name' => 'Specific Gravity',      'unit' => '',      'loinc' => '5811-5',  'norm' => '1.010 - 1.025', 'norm_w' => '1.010 - 1.025', 'gender' => 0],
                    ['testid' => 'URN_PH',   'name' => 'Reaction / pH',         'unit' => 'pH',    'loinc' => '5803-2',  'norm' => '5.0 - 7.5',     'norm_w' => '5.0 - 7.5',     'gender' => 0],
                    ['testid' => 'URN_PROT', 'name' => 'Protein / Albumin',     'unit' => '',      'loinc' => '20454-5', 'norm' => 'Nil (Negative)','norm_w' => 'Nil (Negative)','gender' => 0],
                    ['testid' => 'URN_SUG',  'name' => 'Glucose / Sugar',       'unit' => '',      'loinc' => '25428-4', 'norm' => 'Nil (Negative)','norm_w' => 'Nil (Negative)','gender' => 0],
                    ['testid' => 'URN_KET',  'name' => 'Ketone Bodies',         'unit' => '',      'loinc' => '2514-8',  'norm' => 'Negative',      'norm_w' => 'Negative',      'gender' => 0],
                    ['testid' => 'URN_BS',   'name' => 'Bile Salts',            'unit' => '',      'loinc' => '5769-5',  'norm' => 'Negative',      'norm_w' => 'Negative',      'gender' => 0],
                    ['testid' => 'URN_BP',   'name' => 'Bile Pigments',         'unit' => '',      'loinc' => '5770-3',  'norm' => 'Negative',      'norm_w' => 'Negative',      'gender' => 0],
                    ['testid' => 'URN_URO',  'name' => 'Urobilinogen',          'unit' => 'mg/dL', 'loinc' => '20405-7', 'norm' => 'Normal (0.2-1)','norm_w' => 'Normal (0.2-1)','gender' => 0],
                    ['testid' => 'URN_PUS',  'name' => 'Pus Cells (WBCs)',      'unit' => '/HPF',  'loinc' => '5821-4',  'norm' => '1 - 4',         'norm_w' => '1 - 5',         'gender' => 0],
                    ['testid' => 'URN_RBC',  'name' => 'Red Blood Cells (RBCs)','unit' => '/HPF',  'loinc' => '5822-2',  'norm' => '0 - 2',         'norm_w' => '0 - 2',         'gender' => 0],
                    ['testid' => 'URN_EPI',  'name' => 'Epithelial Cells',      'unit' => '/HPF',  'loinc' => '5788-5',  'norm' => 'Few (1 - 3)',   'norm_w' => 'Few (1 - 5)',   'gender' => 0],
                    ['testid' => 'URN_CAST', 'name' => 'Casts',                 'unit' => '/LPF',  'loinc' => '5777-8',  'norm' => 'Nil seen',      'norm_w' => 'Nil seen',      'gender' => 0],
                    ['testid' => 'URN_CRYS', 'name' => 'Crystals',              'unit' => '',      'loinc' => '5780-2',  'norm' => 'Nil seen',      'norm_w' => 'Nil seen',      'gender' => 0],
                    ['testid' => 'URN_BAC',  'name' => 'Bacteria / Others',     'unit' => '',      'loinc' => '5776-0',  'norm' => 'Absent',        'norm_w' => 'Absent',        'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 9. COAGULATION PROFILE (PT / INR / APTT)
            // -------------------------------------------------------------
            [
                'title'          => 'Coagulation Profile (PT / INR / APTT)',
                'grp_key'        => 6, // Hematology
                'loinc_code'     => '34714-6',
                'charge_desc'    => 'COAGULATION PROFILE (PT / INR / APTT)',
                'charge_amount'  => 550.00,
                'interpretation' => 'PT/INR assesses the extrinsic coagulation pathway; useful for pre-op clearance and monitoring Vitamin K antagonist (Warfarin) therapy. Target therapeutic INR on oral anticoagulation is typically 2.0 - 3.0. APTT evaluates intrinsic pathway.',
                'tests'          => [
                    ['testid' => 'COA_PT_P', 'name' => 'Prothrombin Time (Patient)',   'unit' => 'Seconds', 'loinc' => '5902-2', 'norm' => '11.0 - 15.0', 'norm_w' => '11.0 - 15.0', 'gender' => 0],
                    ['testid' => 'COA_PT_C', 'name' => 'Prothrombin Time (Control)',   'unit' => 'Seconds', 'loinc' => '5901-4', 'norm' => '11.5 - 13.5', 'norm_w' => '11.5 - 13.5', 'gender' => 0],
                    ['testid' => 'COA_INR',  'name' => 'INR',                          'unit' => 'Ratio',   'loinc' => '6301-6', 'norm' => '0.8 - 1.2',   'norm_w' => '0.8 - 1.2',   'gender' => 0],
                    ['testid' => 'COA_APTT', 'name' => 'APTT (Patient)',               'unit' => 'Seconds', 'loinc' => '3173-2', 'norm' => '26.0 - 38.0', 'norm_w' => '26.0 - 38.0', 'gender' => 0],
                    ['testid' => 'COA_APTC', 'name' => 'APTT (Control)',               'unit' => 'Seconds', 'loinc' => '3172-4', 'norm' => '28.0 - 32.0', 'norm_w' => '28.0 - 32.0', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 10. SERUM ELECTROLYTES PANEL
            // -------------------------------------------------------------
            [
                'title'          => 'Serum Electrolytes Panel',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24326-1',
                'charge_desc'    => 'SERUM ELECTROLYTES PANEL (NA, K, CL, HCO3)',
                'charge_amount'  => 450.00,
                'interpretation' => 'Electrolyte imbalances are critical in dehydration, renal dysfunction, cardiac arrhythmias, acid-base disorders, and ICU patients on diuretic/infusion therapy. Ion-selective electrode (ISE) method utilized.',
                'tests'          => [
                    ['testid' => 'ELY_NA',   'name' => 'Sodium (Na+)',       'unit' => 'mEq/L', 'loinc' => '2951-2', 'norm' => '136 - 145', 'norm_w' => '136 - 145', 'gender' => 0],
                    ['testid' => 'ELY_K',    'name' => 'Potassium (K+)',     'unit' => 'mEq/L', 'loinc' => '2823-3', 'norm' => '3.5 - 5.1', 'norm_w' => '3.5 - 5.1', 'gender' => 0],
                    ['testid' => 'ELY_CL',   'name' => 'Chloride (Cl-)',     'unit' => 'mEq/L', 'loinc' => '2075-0', 'norm' => '98 - 107',  'norm_w' => '98 - 107',  'gender' => 0],
                    ['testid' => 'ELY_HCO3', 'name' => 'Bicarbonate (HCO3-)','unit' => 'mEq/L', 'loinc' => '1963-8', 'norm' => '22 - 29',    'norm_w' => '22 - 29',    'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 11. CARDIAC BIOMARKERS PANEL
            // -------------------------------------------------------------
            [
                'title'          => 'Cardiac Biomarkers Panel (Troponin-I, CK-MB)',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '49563-0',
                'charge_desc'    => 'CARDIAC BIOMARKERS PANEL (TROPONIN-I, CK-MB)',
                'charge_amount'  => 1200.00,
                'interpretation' => 'High-sensitivity Troponin-I is the gold standard diagnostic biomarker for myocardial necrosis and Acute Coronary Syndrome (ACS / NSTEMI / STEMI). Levels rise 3-4 hours post injury and peak at 12-24 hours. Serial testing advised if initial value is borderline.',
                'tests'          => [
                    ['testid' => 'CAR_TROP', 'name' => 'Troponin-I (Quantitative)',  'unit' => 'ng/mL', 'loinc' => '10839-9', 'norm' => '< 0.04',    'norm_w' => '< 0.04',    'gender' => 0],
                    ['testid' => 'CAR_CKMB', 'name' => 'CK-MB (Creatine Kinase-MB)', 'unit' => 'U/L',   'loinc' => '13969-1', 'norm' => '< 25',      'norm_w' => '< 25',      'gender' => 0],
                    ['testid' => 'CAR_CPK',  'name' => 'CPK (Creatine Phosphokinase)', 'unit' => 'U/L',  'loinc' => '2157-6',  'norm' => '38 - 174',   'norm_w' => '26 - 140',   'gender' => 1],
                    ['testid' => 'CAR_LDH',  'name' => 'Lactate Dehydrogenase (LDH)', 'unit' => 'U/L',   'loinc' => '14804-9', 'norm' => '125 - 220', 'norm_w' => '125 - 220', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 12. ACUTE FEVER / INFECTION SEROLOGY PANEL
            // -------------------------------------------------------------
            [
                'title'          => 'Acute Fever & Infection Serology Panel',
                'grp_key'        => 4, // Infectious Disease
                'loinc_code'     => '94500-6',
                'charge_desc'    => 'ACUTE FEVER & INFECTION SEROLOGY PANEL',
                'charge_amount'  => 900.00,
                'interpretation' => 'Comprehensive panel for acute febrile illness: Dengue NS1 is positive in acute phase (days 1-5); IgM appears by day 5. Widal titers >= 1:160 for TO/TH with suggestive clinical history are indicative of Enteric Fever (Typhoid).',
                'tests'          => [
                    ['testid' => 'FEV_DNS1', 'name' => 'Dengue NS1 Antigen',        'unit' => '', 'loinc' => '68993-5', 'norm' => 'Negative', 'norm_w' => 'Negative', 'gender' => 0],
                    ['testid' => 'FEV_DIGM', 'name' => 'Dengue IgM Antibodies',     'unit' => '', 'loinc' => '11011-4', 'norm' => 'Negative', 'norm_w' => 'Negative', 'gender' => 0],
                    ['testid' => 'FEV_DIGG', 'name' => 'Dengue IgG Antibodies',     'unit' => '', 'loinc' => '11012-2', 'norm' => 'Negative', 'norm_w' => 'Negative', 'gender' => 0],
                    ['testid' => 'FEV_TYPH', 'name' => 'Typhidot (IgM / IgG)',       'unit' => '', 'loinc' => '40733-8', 'norm' => 'Negative', 'norm_w' => 'Negative', 'gender' => 0],
                    ['testid' => 'FEV_WTO',  'name' => 'Widal - S. typhi "O"',       'unit' => '', 'loinc' => '20573-2', 'norm' => '< 1:80',   'norm_w' => '< 1:80',   'gender' => 0],
                    ['testid' => 'FEV_WTH',  'name' => 'Widal - S. typhi "H"',       'unit' => '', 'loinc' => '20574-0', 'norm' => '< 1:80',   'norm_w' => '< 1:80',   'gender' => 0],
                    ['testid' => 'MAL_AG',   'name' => 'Malaria Antigen (Pf / Pv)',  'unit' => '', 'loinc' => '44883-7', 'norm' => 'Negative', 'norm_w' => 'Negative', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 13. PRE-OPERATIVE & VIRAL SCREENING MARKERS
            // -------------------------------------------------------------
            [
                'title'          => 'Pre-Operative Viral Screening Markers',
                'grp_key'        => 12, // Immunology-Serology
                'loinc_code'     => '56888-1',
                'charge_desc'    => 'PRE-OPERATIVE VIRAL SCREENING MARKERS (HIV, HBSAG, HCV, VDRL)',
                'charge_amount'  => 800.00,
                'interpretation' => 'Standard pre-surgical infectious safety screen. Reactive rapid immunochromatography results must be confirmed by ELISA, CLIA, or Western Blot / Molecular PCR testing prior to definitive diagnostic labeling.',
                'tests'          => [
                    ['testid' => 'VIR_HIV',   'name' => 'HIV 1 & 2 Antibodies',       'unit' => '', 'loinc' => '75622-1', 'norm' => 'Non-Reactive', 'norm_w' => 'Non-Reactive', 'gender' => 0],
                    ['testid' => 'VIR_HBSAG', 'name' => 'HBsAg (Hepatitis B Surface)','unit' => '', 'loinc' => '5196-1',  'norm' => 'Non-Reactive', 'norm_w' => 'Non-Reactive', 'gender' => 0],
                    ['testid' => 'VIR_HCV',   'name' => 'Anti-HCV (Hepatitis C)',      'unit' => '', 'loinc' => '13955-0', 'norm' => 'Non-Reactive', 'norm_w' => 'Non-Reactive', 'gender' => 0],
                    ['testid' => 'VIR_VDRL',  'name' => 'VDRL / RPR Syphilis Test',    'unit' => '', 'loinc' => '20507-0', 'norm' => 'Non-Reactive', 'norm_w' => 'Non-Reactive', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 14. IRON DEFICIENCY & FERRITIN PROFILE
            // -------------------------------------------------------------
            [
                'title'          => 'Iron Deficiency & Ferritin Profile',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24360-0',
                'charge_desc'    => 'IRON DEFICIENCY & FERRITIN PROFILE',
                'charge_amount'  => 950.00,
                'interpretation' => 'Serum Ferritin is the single best diagnostic indicator of total body iron stores. Low Ferritin (< 30 ng/mL) confirms iron deficiency anemia. High Ferritin with low TIBC and normal/low iron may reflect anemia of chronic disease or acute phase reaction.',
                'tests'          => [
                    ['testid' => 'IRN_SER',  'name' => 'Serum Iron',             'unit' => 'ug/dL', 'loinc' => '2498-4',  'norm' => '65 - 175',   'norm_w' => '50 - 170',   'gender' => 1],
                    ['testid' => 'IRN_TIBC', 'name' => 'TIBC (Total Binding)',   'unit' => 'ug/dL', 'loinc' => '2500-7',  'norm' => '250 - 425',  'norm_w' => '250 - 425',  'gender' => 0],
                    ['testid' => 'IRN_UIBC', 'name' => 'UIBC (Unsaturated)',     'unit' => 'ug/dL', 'loinc' => '2502-3',  'norm' => '150 - 375',  'norm_w' => '150 - 375',  'gender' => 0],
                    ['testid' => 'IRN_TSAT', 'name' => 'Transferrin Saturation', 'unit' => '%',     'loinc' => '25023-7', 'norm' => '20 - 50',    'norm_w' => '15 - 50',    'gender' => 1],
                    ['testid' => 'IRN_FERR', 'name' => 'Serum Ferritin',         'unit' => 'ng/mL', 'loinc' => '2276-4',  'norm' => '30 - 300',   'norm_w' => '15 - 150',   'gender' => 1],
                ],
            ],

            // -------------------------------------------------------------
            // 15. ARTHRITIS & INFLAMMATORY SCREEN
            // -------------------------------------------------------------
            [
                'title'          => 'Arthritis & Inflammatory Screen',
                'grp_key'        => 18, // Inflammatory Markers
                'loinc_code'     => '93766-4',
                'charge_desc'    => 'ARTHRITIS & INFLAMMATORY SCREEN (CRP, RA, ASO, URIC ACID)',
                'charge_amount'  => 850.00,
                'interpretation' => 'CRP is a sensitive acute-phase reactant indicating systemic inflammation. Rheumatoid Factor (RA) and Anti-CCP evaluate inflammatory arthritis / RA. Serum Uric Acid is indicated in suspected Gouty arthritis.',
                'tests'          => [
                    ['testid' => 'ART_CRP',  'name' => 'C-Reactive Protein (CRP)', 'unit' => 'mg/L',  'loinc' => '1988-5',  'norm' => '< 6.0',      'norm_w' => '< 6.0',      'gender' => 0],
                    ['testid' => 'ART_RA',   'name' => 'Rheumatoid Factor (RA)',   'unit' => 'IU/mL', 'loinc' => '11572-5', 'norm' => '< 14.0',     'norm_w' => '< 14.0',     'gender' => 0],
                    ['testid' => 'ART_ASO',  'name' => 'ASO Titre',                'unit' => 'IU/mL', 'loinc' => '5064-1',  'norm' => '< 200',      'norm_w' => '< 200',      'gender' => 0],
                    ['testid' => 'ART_URIC', 'name' => 'Serum Uric Acid',          'unit' => 'mg/dL', 'loinc' => '3084-1',  'norm' => '3.5 - 7.2',  'norm_w' => '2.6 - 6.0',  'gender' => 1],
                ],
            ],

            // -------------------------------------------------------------
            // 16. VITAMIN PROFILE (D3 & B12)
            // -------------------------------------------------------------
            [
                'title'          => 'Vitamin Profile (Vitamin D3 & B12)',
                'grp_key'        => 16, // Vitamins & Minerals
                'loinc_code'     => '53538-5',
                'charge_desc'    => 'VITAMIN PROFILE (VITAMIN D3 & B12)',
                'charge_amount'  => 1600.00,
                'interpretation' => 'Vitamin D3 (25-OH): < 20 ng/mL Deficient; 20 - 30 ng/mL Insufficient; 30 - 100 ng/mL Sufficient. Vitamin B12: < 200 pg/mL Deficient (causes megaloblastic anemia and peripheral neuropathy); 200 - 900 pg/mL Normal.',
                'tests'          => [
                    ['testid' => 'VIT_D3',  'name' => 'Vitamin D3 (25-Hydroxy)', 'unit' => 'ng/mL', 'loinc' => '62292-8', 'norm' => '30.0 - 100.0', 'norm_w' => '30.0 - 100.0', 'gender' => 0],
                    ['testid' => 'VIT_B12', 'name' => 'Vitamin B12 (Cyanocobalamin)', 'unit' => 'pg/mL', 'loinc' => '2132-9', 'norm' => '211 - 911',     'norm_w' => '211 - 911',     'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 17. STOOL ROUTINE & MICROSCOPIC EXAMINATION
            // -------------------------------------------------------------
            [
                'title'          => 'Stool Routine & Microscopic Examination',
                'grp_key'        => 3, // Clinical Pathology
                'loinc_code'     => '24357-6',
                'charge_desc'    => 'STOOL ROUTINE & MICROSCOPIC EXAMINATION',
                'charge_amount'  => 150.00,
                'interpretation' => 'Microscopic and chemical evaluation for intestinal pathogens, protozoa (E. histolytica, Giardia), helminth ova, pus cells, and occult blood.',
                'tests'          => [
                    ['testid' => 'STL_COL',  'name' => 'Color',                'unit' => '',     'loinc' => '5774-5', 'norm' => 'Brownish',  'norm_w' => 'Brownish',  'gender' => 0],
                    ['testid' => 'STL_CONS', 'name' => 'Consistency',          'unit' => '',     'loinc' => '5775-2', 'norm' => 'Formed',    'norm_w' => 'Formed',    'gender' => 0],
                    ['testid' => 'STL_MUC',  'name' => 'Mucus',                'unit' => '',     'loinc' => '5779-4', 'norm' => 'Absent',    'norm_w' => 'Absent',    'gender' => 0],
                    ['testid' => 'STL_BLD',  'name' => 'Gross Blood',          'unit' => '',     'loinc' => '5771-1', 'norm' => 'Absent',    'norm_w' => 'Absent',    'gender' => 0],
                    ['testid' => 'STL_PUS',  'name' => 'Pus Cells',            'unit' => '/HPF', 'loinc' => '5784-4', 'norm' => '0 - 2',     'norm_w' => '0 - 2',     'gender' => 0],
                    ['testid' => 'STL_RBC',  'name' => 'RBCs',                 'unit' => '/HPF', 'loinc' => '5785-1', 'norm' => 'Nil seen',  'norm_w' => 'Nil seen',  'gender' => 0],
                    ['testid' => 'STL_OVA',  'name' => 'Ova / Parasites',      'unit' => '',     'loinc' => '10701-1','norm' => 'Nil seen',  'norm_w' => 'Nil seen',  'gender' => 0],
                    ['testid' => 'STL_CYST', 'name' => 'Cysts / Trophozoites', 'unit' => '',     'loinc' => '10700-3','norm' => 'Nil seen',  'norm_w' => 'Nil seen',  'gender' => 0],
                    ['testid' => 'STL_OB',   'name' => 'Occult Blood',         'unit' => '',     'loinc' => '57905-2','norm' => 'Negative',  'norm_w' => 'Negative',  'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 18. SEMEN ANALYSIS (WHO CRITERIA)
            // -------------------------------------------------------------
            [
                'title'          => 'Semen Analysis (WHO Criteria)',
                'grp_key'        => 3, // Clinical Pathology
                'loinc_code'     => '10582-5',
                'charge_desc'    => 'SEMEN ANALYSIS (WHO CRITERIA)',
                'charge_amount'  => 300.00,
                'interpretation' => 'Standard examination in accordance with WHO laboratory manual (6th edition, 2021). Abstinence period of 2-7 days required. Normal: Volume >= 1.4 mL, Total motility >= 42%, Progressive motility >= 30%, Sperm concentration >= 16 mil/mL.',
                'tests'          => [
                    ['testid' => 'SEM_ABS',  'name' => 'Period of Abstinence',       'unit' => 'Days',    'loinc' => '10583-3', 'norm' => '2 - 7',     'norm_w' => '2 - 7',     'gender' => 0],
                    ['testid' => 'SEM_VOL',  'name' => 'Volume',                     'unit' => 'mL',      'loinc' => '3160-9',  'norm' => '>= 1.5',    'norm_w' => '>= 1.5',    'gender' => 0],
                    ['testid' => 'SEM_LIQ',  'name' => 'Liquefaction Time',          'unit' => 'Minutes', 'loinc' => '10585-8', 'norm' => '< 60',      'norm_w' => '< 60',      'gender' => 0],
                    ['testid' => 'SEM_PH',   'name' => 'pH / Reaction',              'unit' => 'pH',      'loinc' => '5804-0',  'norm' => '>= 7.2',    'norm_w' => '>= 7.2',    'gender' => 0],
                    ['testid' => 'SEM_CNT',  'name' => 'Sperm Concentration',        'unit' => 'mil/mL',  'loinc' => '9780-8',  'norm' => '>= 15',     'norm_w' => '>= 15',     'gender' => 0],
                    ['testid' => 'SEM_PROG', 'name' => 'Progressive Motility (PR)',  'unit' => '%',       'loinc' => '10588-2', 'norm' => '>= 32',     'norm_w' => '>= 32',     'gender' => 0],
                    ['testid' => 'SEM_NPR',  'name' => 'Non-Progressive Motility',   'unit' => '%',       'loinc' => '10589-0', 'norm' => '5 - 15',    'norm_w' => '5 - 15',    'gender' => 0],
                    ['testid' => 'SEM_IMM',  'name' => 'Immotile Spermatozoa',       'unit' => '%',       'loinc' => '10590-8', 'norm' => '< 40',      'norm_w' => '< 40',      'gender' => 0],
                    ['testid' => 'SEM_NORM', 'name' => 'Normal Morphology (Kruger)', 'unit' => '%',       'loinc' => '10592-4', 'norm' => '>= 4.0',    'norm_w' => '>= 4.0',    'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 19. ARTERIAL BLOOD GAS (ABG) ANALYSIS
            // -------------------------------------------------------------
            [
                'title'          => 'Arterial Blood Gas (ABG) Analysis',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24338-6',
                'charge_desc'    => 'ARTERIAL BLOOD GAS (ABG) ANALYSIS',
                'charge_amount'  => 650.00,
                'interpretation' => 'ABG evaluates ventilation (pCO2), oxygenation (pO2, sO2), and acid-base equilibrium (pH, HCO3-, Base Excess). Essential in ICU, ARDS, mechanical ventilation, shock, and diabetic ketoacidosis.',
                'tests'          => [
                    ['testid' => 'ABG_PH',   'name' => 'pH',                  'unit' => '',       'loinc' => '2744-1',  'norm' => '7.35 - 7.45', 'norm_w' => '7.35 - 7.45', 'gender' => 0],
                    ['testid' => 'ABG_PCO2', 'name' => 'pCO2',                'unit' => 'mmHg',   'loinc' => '2019-8',  'norm' => '35.0 - 45.0', 'norm_w' => '35.0 - 45.0', 'gender' => 0],
                    ['testid' => 'ABG_PO2',  'name' => 'pO2',                 'unit' => 'mmHg',   'loinc' => '2703-7',  'norm' => '80.0 - 100.0','norm_w' => '80.0 - 100.0','gender' => 0],
                    ['testid' => 'ABG_HCO3', 'name' => 'Bicarbonate (HCO3-)', 'unit' => 'mmol/L', 'loinc' => '1963-8',  'norm' => '22.0 - 26.0', 'norm_w' => '22.0 - 26.0', 'gender' => 0],
                    ['testid' => 'ABG_BE',   'name' => 'Base Excess (BE)',    'unit' => 'mmol/L', 'loinc' => '1925-7',  'norm' => '-2.0 to +2.0','norm_w' => '-2.0 to +2.0','gender' => 0],
                    ['testid' => 'ABG_SO2',  'name' => 'O2 Saturation (sO2)', 'unit' => '%',      'loinc' => '2708-6',  'norm' => '95.0 - 99.0', 'norm_w' => '95.0 - 99.0', 'gender' => 0],
                    ['testid' => 'ABG_LACT', 'name' => 'Lactate',             'unit' => 'mmol/L', 'loinc' => '2524-7',  'norm' => '0.5 - 2.2',   'norm_w' => '0.5 - 2.2',   'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 20. CEREBROSPINAL FLUID (CSF) ANALYSIS
            // -------------------------------------------------------------
            [
                'title'          => 'Cerebrospinal Fluid (CSF) Analysis',
                'grp_key'        => 1, // Biochemistry & Cytology
                'loinc_code'     => '24339-4',
                'charge_desc'    => 'CEREBROSPINAL FLUID (CSF) EXAMINATION',
                'charge_amount'  => 850.00,
                'interpretation' => 'CSF analysis differentiates bacterial, viral, tuberculous, and fungal meningitis, subarachnoid hemorrhage, and demyelinating diseases. Markedly low glucose and high protein with neutrophilic pleocytosis indicates pyogenic meningitis; lymphocytic predominance with high protein indicates viral or tubercular etiology.',
                'tests'          => [
                    ['testid' => 'CSF_APP',   'name' => 'Appearance / Color',        'unit' => '',       'loinc' => '9334-4',  'norm' => 'Clear, Colorless', 'norm_w' => 'Clear, Colorless', 'gender' => 0],
                    ['testid' => 'CSF_COAG',  'name' => 'Coagulum / Web',            'unit' => '',       'loinc' => '9335-1',  'norm' => 'Absent',           'norm_w' => 'Absent',           'gender' => 0],
                    ['testid' => 'CSF_GLU',   'name' => 'CSF Glucose',               'unit' => 'mg/dL',  'loinc' => '2342-4',  'norm' => '45 - 80',          'norm_w' => '45 - 80',          'gender' => 0],
                    ['testid' => 'CSF_PROT',  'name' => 'CSF Total Protein',         'unit' => 'mg/dL',  'loinc' => '2880-3',  'norm' => '15 - 45',          'norm_w' => '15 - 45',          'gender' => 0],
                    ['testid' => 'CSF_TLC',   'name' => 'CSF Total Cell Count',      'unit' => '/cumm',  'loinc' => '10594-0', 'norm' => '0 - 5',            'norm_w' => '0 - 5',            'gender' => 0],
                    ['testid' => 'CSF_POLY',  'name' => 'Polymorphs (Neutrophils)', 'unit' => '%',       'loinc' => '10595-7', 'norm' => '0%',               'norm_w' => '0%',               'gender' => 0],
                    ['testid' => 'CSF_LYMPH', 'name' => 'Lymphocytes',               'unit' => '%',       'loinc' => '10596-5', 'norm' => '100%',             'norm_w' => '100%',             'gender' => 0],
                    ['testid' => 'CSF_GRAM',  'name' => 'Gram Stain',                'unit' => '',       'loinc' => '664-3',   'norm' => 'No organism seen', 'norm_w' => 'No organism seen', 'gender' => 0],
                    ['testid' => 'CSF_AFB',   'name' => 'ZN Stain for AFB',          'unit' => '',       'loinc' => '11545-1', 'norm' => 'Negative for AFB', 'norm_w' => 'Negative for AFB', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 21. PLEURAL FLUID ANALYSIS
            // -------------------------------------------------------------
            [
                'title'          => 'Pleural Fluid Analysis',
                'grp_key'        => 1, // Biochemistry & Cytology
                'loinc_code'     => '24340-2',
                'charge_desc'    => 'PLEURAL FLUID ROUTINE & BIOCHEMICAL EXAMINATION',
                'charge_amount'  => 800.00,
                'interpretation' => 'Pleural fluid analysis utilizes Light\'s criteria to differentiate exudative from transudative effusions (Pleural/Serum Protein >0.5, Pleural/Serum LDH >0.6). Elevated Adenosine Deaminase (ADA >40 U/L) is highly sensitive for tuberculous pleurisy.',
                'tests'          => [
                    ['testid' => 'PLF_APP',   'name' => 'Appearance / Physical',     'unit' => '',      'loinc' => '9345-0',  'norm' => 'Straw / Pale yellow', 'norm_w' => 'Straw / Pale yellow', 'gender' => 0],
                    ['testid' => 'PLF_PROT',  'name' => 'Pleural Fluid Total Protein','unit' => 'g/dL',  'loinc' => '2887-8',  'norm' => '< 3.0 (Transudate)',  'norm_w' => '< 3.0 (Transudate)',  'gender' => 0],
                    ['testid' => 'PLF_ALB',   'name' => 'Pleural Fluid Albumin',     'unit' => 'g/dL',  'loinc' => '1752-5',  'norm' => '1.0 - 2.5',           'norm_w' => '1.0 - 2.5',           'gender' => 0],
                    ['testid' => 'PLF_LDH',   'name' => 'Pleural Fluid LDH',         'unit' => 'U/L',   'loinc' => '2531-2',  'norm' => '< 200 (Transudate)',  'norm_w' => '< 200 (Transudate)',  'gender' => 0],
                    ['testid' => 'PLF_GLU',   'name' => 'Pleural Fluid Glucose',     'unit' => 'mg/dL', 'loinc' => '2344-0',  'norm' => '60 - 100',            'norm_w' => '60 - 100',            'gender' => 0],
                    ['testid' => 'PLF_TLC',   'name' => 'Total Cell Count',          'unit' => '/cumm', 'loinc' => '10597-3', 'norm' => '< 1000',              'norm_w' => '< 1000',              'gender' => 0],
                    ['testid' => 'PLF_POLY',  'name' => 'Polymorphs',                'unit' => '%',      'loinc' => '10598-1', 'norm' => '< 50',                'norm_w' => '< 50',                'gender' => 0],
                    ['testid' => 'PLF_LYMPH', 'name' => 'Lymphocytes',               'unit' => '%',      'loinc' => '10599-9', 'norm' => '> 50',                'norm_w' => '> 50',                'gender' => 0],
                    ['testid' => 'PLF_ADA',   'name' => 'Adenosine Deaminase (ADA)', 'unit' => 'U/L',   'loinc' => '1747-5',  'norm' => '< 40.0',              'norm_w' => '< 40.0',              'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 22. ASCITIC / PERITONEAL FLUID ANALYSIS
            // -------------------------------------------------------------
            [
                'title'          => 'Ascitic / Peritoneal Fluid Analysis',
                'grp_key'        => 1, // Biochemistry & Cytology
                'loinc_code'     => '24341-0',
                'charge_desc'    => 'ASCITIC FLUID EXAMINATION (BIOCHEMISTRY & CYTOLOGY)',
                'charge_amount'  => 800.00,
                'interpretation' => 'Serum-Ascites Albumin Gradient (SAAG >= 1.1 g/dL) indicates portal hypertension (cirrhosis, cardiac failure, Budd-Chiari). SAAG < 1.1 g/dL indicates peritoneal causes (carcinomatosis, peritoneal tuberculosis, nephrotic syndrome, pancreatitis). Ascitic absolute neutrophil count >= 250/cumm diagnostic of spontaneous bacterial peritonitis (SBP).',
                'tests'          => [
                    ['testid' => 'ASC_APP',   'name' => 'Appearance / Clarity',       'unit' => '',      'loinc' => '9349-2',  'norm' => 'Clear / Pale Yellow', 'norm_w' => 'Clear / Pale Yellow', 'gender' => 0],
                    ['testid' => 'ASC_PROT',  'name' => 'Total Protein',              'unit' => 'g/dL',  'loinc' => '2889-4',  'norm' => '1.0 - 3.0',           'norm_w' => '1.0 - 3.0',           'gender' => 0],
                    ['testid' => 'ASC_ALB',   'name' => 'Ascitic Albumin',            'unit' => 'g/dL',  'loinc' => '1753-3',  'norm' => '0.5 - 2.0',           'norm_w' => '0.5 - 2.0',           'gender' => 0],
                    ['testid' => 'ASC_SAAG',  'name' => 'SAAG (Serum - Ascitic Alb)', 'unit' => 'g/dL',  'loinc' => '42232-9', 'norm' => '< 1.1 (Non-portal)', 'norm_w' => '< 1.1 (Non-portal)', 'gender' => 0],
                    ['testid' => 'ASC_LDH',   'name' => 'Ascitic Fluid LDH',          'unit' => 'U/L',   'loinc' => '2532-0',  'norm' => '< 200',               'norm_w' => '< 200',               'gender' => 0],
                    ['testid' => 'ASC_TLC',   'name' => 'Total Cell Count',           'unit' => '/cumm', 'loinc' => '10600-5', 'norm' => '< 500',               'norm_w' => '< 500',               'gender' => 0],
                    ['testid' => 'ASC_POLY',  'name' => 'Absolute Neutrophil Count',  'unit' => '/cumm', 'loinc' => '10601-3', 'norm' => '< 250 (SBP negative)', 'norm_w' => '< 250 (SBP negative)', 'gender' => 0],
                    ['testid' => 'ASC_CYTO',  'name' => 'Malignant Cells',            'unit' => '',      'loinc' => '10602-1', 'norm' => 'Absent',              'norm_w' => 'Absent',              'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 23. SYNOVIAL (JOINT) FLUID ANALYSIS
            // -------------------------------------------------------------
            [
                'title'          => 'Synovial (Joint) Fluid Analysis',
                'grp_key'        => 1, // Biochemistry & Cytology
                'loinc_code'     => '24342-8',
                'charge_desc'    => 'SYNOVIAL FLUID ROUTINE & CRYSTAL EXAMINATION',
                'charge_amount'  => 850.00,
                'interpretation' => 'Synovial fluid analysis classifies joint effusions into non-inflammatory (OA), inflammatory (RA), septic, or crystal-induced arthritis. Negatively birefringent needle-shaped crystals under polarized light indicate Monosodium Urate (Gout); positively birefringent rhomboid crystals indicate Calcium Pyrophosphate (Pseudogout).',
                'tests'          => [
                    ['testid' => 'SYN_COL',   'name' => 'Color',                     'unit' => '',       'loinc' => '9350-0',  'norm' => 'Colorless to straw', 'norm_w' => 'Colorless to straw', 'gender' => 0],
                    ['testid' => 'SYN_CLAR',  'name' => 'Clarity',                   'unit' => '',       'loinc' => '9351-8',  'norm' => 'Transparent',       'norm_w' => 'Transparent',       'gender' => 0],
                    ['testid' => 'SYN_VISC',  'name' => 'Viscosity (String test)',   'unit' => 'cm',     'loinc' => '9352-6',  'norm' => 'High (3 - 5 cm)',    'norm_w' => 'High (3 - 5 cm)',    'gender' => 0],
                    ['testid' => 'SYN_MUC',   'name' => 'Mucin Clot Test',           'unit' => '',       'loinc' => '9353-4',  'norm' => 'Good / Firm clot',   'norm_w' => 'Good / Firm clot',   'gender' => 0],
                    ['testid' => 'SYN_WBC',   'name' => 'Synovial WBC Count',        'unit' => '/cumm',  'loinc' => '10603-9', 'norm' => '< 200',             'norm_w' => '< 200',             'gender' => 0],
                    ['testid' => 'SYN_POLY',  'name' => 'Polymorphs',                'unit' => '%',      'loinc' => '10604-7', 'norm' => '< 25',             'norm_w' => '< 25',             'gender' => 0],
                    ['testid' => 'SYN_CRYS',  'name' => 'Polarized Microscopy (Crystals)','unit'=>'','loinc'=>'10605-4','norm' => 'No crystals seen',    'norm_w' => 'No crystals seen',    'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 24. SPUTUM ROUTINE & AFB ZN STAIN EXAMINATION
            // -------------------------------------------------------------
            [
                'title'          => 'Sputum Routine & AFB ZN Stain Examination',
                'grp_key'        => 3, // Microbiology
                'loinc_code'     => '24343-6',
                'charge_desc'    => 'SPUTUM ROUTINE & AFB ZN EXAMINATION',
                'charge_amount'  => 300.00,
                'interpretation' => 'Specimen adequacy evaluated by Bartlett score (Pus cells >25/LPF, Squamous epithelial cells <10/LPF indicates true lower respiratory specimen). Ziehl-Neelsen staining detects Acid-Fast Bacilli (Mycobacterium tuberculosis).',
                'tests'          => [
                    ['testid' => 'SPT_COL',   'name' => 'Color & Appearance',        'unit' => '',       'loinc' => '9354-2',  'norm' => 'Mucoid / White',   'norm_w' => 'Mucoid / White',   'gender' => 0],
                    ['testid' => 'SPT_CONS',  'name' => 'Consistency',               'unit' => '',       'loinc' => '9355-9',  'norm' => 'Viscid',           'norm_w' => 'Viscid',           'gender' => 0],
                    ['testid' => 'SPT_EPITH', 'name' => 'Squamous Epithelial Cells', 'unit' => '/LPF',   'loinc' => '10606-2', 'norm' => '< 10',             'norm_w' => '< 10',             'gender' => 0],
                    ['testid' => 'SPT_PUS',   'name' => 'Pus Cells / Leukocytes',    'unit' => '/HPF',   'loinc' => '10607-0', 'norm' => 'Occasional',       'norm_w' => 'Occasional',       'gender' => 0],
                    ['testid' => 'SPT_GRAM',  'name' => 'Gram Stain Microscopic',    'unit' => '',       'loinc' => '664-3',   'norm' => 'Normal flora flora','norm_w' => 'Normal flora flora','gender' => 0],
                    ['testid' => 'SPT_AFB',   'name' => 'Acid Fast Bacilli (ZN Stain)','unit'=>'','loinc'=>'11545-1','norm' => 'Negative for AFB', 'norm_w' => 'Negative for AFB', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 25. PANCREATIC ENZYMES PROFILE (AMYLASE & LIPASE)
            // -------------------------------------------------------------
            [
                'title'          => 'Pancreatic Enzymes Profile (Amylase & Lipase)',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24344-4',
                'charge_desc'    => 'PANCREATIC PROFILE (SERUM AMYLASE & LIPASE)',
                'charge_amount'  => 800.00,
                'interpretation' => 'Serum Lipase is more sensitive and specific than Amylase for acute pancreatitis. Enzyme elevations greater than three times upper reference limit are diagnostic of acute pancreatitis. Lipase remains elevated longer (7-14 days).',
                'tests'          => [
                    ['testid' => 'SER_AMYLASE', 'name' => 'Serum Amylase', 'unit' => 'U/L', 'loinc' => '1798-8', 'norm' => '28 - 100', 'norm_w' => '28 - 100', 'gender' => 0],
                    ['testid' => 'SER_LIPASE',  'name' => 'Serum Lipase',  'unit' => 'U/L', 'loinc' => '2575-9', 'norm' => '13 - 60',  'norm_w' => '13 - 60',  'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 26. BONE & MINERAL METABOLISM PROFILE
            // -------------------------------------------------------------
            [
                'title'          => 'Bone & Mineral Metabolism Profile',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24345-1',
                'charge_desc'    => 'BONE & MINERAL METABOLISM PANEL',
                'charge_amount'  => 850.00,
                'interpretation' => 'Bone profile evaluates calcium homeostasis, parathyroid function, and metabolic bone disorders (osteomalacia, osteoporosis, Paget disease). Corrected Calcium = Measured Calcium + 0.8 * (4.0 - Serum Albumin).',
                'tests'          => [
                    ['testid' => 'SER_CALCIUM', 'name' => 'Serum Calcium - Total',     'unit' => 'mg/dL', 'loinc' => '17861-6', 'norm' => '8.8 - 10.2',  'norm_w' => '8.8 - 10.2',  'gender' => 0],
                    ['testid' => 'SER_PHOS',    'name' => 'Serum Phosphorus / Phosphate','unit'=>'mg/dL', 'loinc' => '2777-1',   'norm' => '2.5 - 4.5',   'norm_w' => '2.5 - 4.5',   'gender' => 0],
                    ['testid' => 'SER_ALP_BN',  'name' => 'Alkaline Phosphatase (ALP)', 'unit' => 'U/L',   'loinc' => '6768-6',   'norm' => '40 - 130',    'norm_w' => '35 - 105',    'gender' => 1],
                    ['testid' => 'SER_ALB_BN',  'name' => 'Serum Albumin',              'unit' => 'g/dL',  'loinc' => '1751-7',   'norm' => '3.5 - 5.0',   'norm_w' => '3.5 - 5.0',   'gender' => 0],
                    ['testid' => 'SER_CA_CORR', 'name' => 'Albumin-Corrected Calcium',  'unit' => 'mg/dL', 'loinc' => '47596-2', 'norm' => '8.8 - 10.2',  'norm_w' => '8.8 - 10.2',  'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 27. EXTENDED CARDIAC RISK & HEART FAILURE PANEL
            // -------------------------------------------------------------
            [
                'title'          => 'Extended Cardiac Risk & Heart Failure Panel',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24346-9',
                'charge_desc'    => 'EXTENDED CARDIAC & HEART FAILURE BIOMARKERS',
                'charge_amount'  => 2800.00,
                'interpretation' => 'High-sensitivity Troponin T enables early detection of acute myocardial infarction. NT-proBNP is the gold-standard biomarker for diagnosing, stratifying, and monitoring congestive heart failure. Homocysteine is an independent cardiovascular risk factor.',
                'tests'          => [
                    ['testid' => 'HS_TROP_T',   'name' => 'High-Sensitivity Troponin-T (hs-cTnT)', 'unit' => 'ng/L',   'loinc' => '6598-7',  'norm' => '< 14.0',     'norm_w' => '< 14.0',     'gender' => 0],
                    ['testid' => 'NT_PROBNP',   'name' => 'NT-proBNP (N-Terminal pro-BNP)',        'unit' => 'pg/mL',  'loinc' => '33762-6', 'norm' => '< 125.0',    'norm_w' => '< 125.0',    'gender' => 0],
                    ['testid' => 'SER_HOMOCYS', 'name' => 'Serum Homocysteine',                    'unit' => 'umol/L', 'loinc' => '2428-1',  'norm' => '5.0 - 15.0', 'norm_w' => '5.0 - 12.0', 'gender' => 1],
                    ['testid' => 'HS_CRP_EX',   'name' => 'High-Sensitivity CRP (hs-CRP)',         'unit' => 'mg/L',   'loinc' => '30522-7', 'norm' => '< 1.0 (Low risk)','norm_w' => '< 1.0 (Low risk)','gender' => 0],
                    ['testid' => 'DDIMER_QNT',  'name' => 'D-Dimer (Quantitative FEU)',           'unit' => 'ng/mL',  'loinc' => '48065-7', 'norm' => '< 500',       'norm_w' => '< 500',       'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 28. SEPSIS & CRITICAL CARE BIOMARKERS
            // -------------------------------------------------------------
            [
                'title'          => 'Sepsis & Critical Care Biomarkers',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24347-7',
                'charge_desc'    => 'SEPSIS & INFLAMMATORY ICU PANEL',
                'charge_amount'  => 2500.00,
                'interpretation' => 'Serum Procalcitonin (PCT) is a highly specific marker for severe bacterial infection and sepsis; levels > 2.0 ng/mL indicate high risk of severe sepsis/septic shock and guide antibiotic escalation/de-escalation. Elevated lactate reflects tissue hypoperfusion.',
                'tests'          => [
                    ['testid' => 'PROCALCITONIN', 'name' => 'Procalcitonin (PCT)',     'unit' => 'ng/mL',  'loinc' => '33959-8', 'norm' => '< 0.50 (Low risk sepsis)','norm_w' => '< 0.50 (Low risk sepsis)','gender' => 0],
                    ['testid' => 'SER_LACTATE',   'name' => 'Serum Lactate (Venous)',  'unit' => 'mmol/L', 'loinc' => '2524-7',  'norm' => '0.5 - 2.0',               'norm_w' => '0.5 - 2.0',               'gender' => 0],
                    ['testid' => 'SEPSIS_CRP',    'name' => 'C-Reactive Protein (CRP)','unit' => 'mg/L',   'loinc' => '1988-5',  'norm' => '< 5.0',                   'norm_w' => '< 5.0',                   'gender' => 0],
                    ['testid' => 'SEPSIS_FERR',   'name' => 'Serum Ferritin',          'unit' => 'ng/mL',  'loinc' => '2276-4',  'norm' => '30 - 400',                'norm_w' => '15 - 150',                'gender' => 1],
                ],
            ],

            // -------------------------------------------------------------
            // 29. EXTENDED REPRODUCTIVE & FERTILITY HORMONE PANEL
            // -------------------------------------------------------------
            [
                'title'          => 'Extended Reproductive & Fertility Hormone Panel',
                'grp_key'        => 1, // Biochemistry & Immunoassay
                'loinc_code'     => '24348-5',
                'charge_desc'    => 'REPRODUCTIVE & ENDOCRINE HORMONE WORKUP',
                'charge_amount'  => 2400.00,
                'interpretation' => 'Comprehensive evaluation of hypothalamic-pituitary-gonadal axis for infertility, menstrual irregularities (PCOS, primary/secondary amenorrhea), hypogonadism, and ovarian reserve assessment (AMH).',
                'tests'          => [
                    ['testid' => 'SER_PROLACTIN', 'name' => 'Serum Prolactin',                  'unit' => 'ng/mL', 'loinc' => '2842-3',  'norm' => '4.0 - 15.0',  'norm_w' => '4.8 - 23.3',  'gender' => 1],
                    ['testid' => 'SER_FSH',       'name' => 'Follicle Stimulating Hormone (FSH)','unit' => 'mIU/mL','loinc' => '2282-2',  'norm' => '1.5 - 12.4',  'norm_w' => 'Follicular: 3.5 - 12.5','gender' => 1],
                    ['testid' => 'SER_LH',        'name' => 'Luteinizing Hormone (LH)',          'unit' => 'mIU/mL','loinc' => '2550-2',  'norm' => '1.7 - 8.6',   'norm_w' => 'Follicular: 2.4 - 12.6','gender' => 1],
                    ['testid' => 'SER_TESTOS',    'name' => 'Serum Testosterone - Total',        'unit' => 'ng/dL', 'loinc' => '2986-8',  'norm' => '280 - 1000',  'norm_w' => '15 - 70',     'gender' => 1],
                    ['testid' => 'BETA_HCG',      'name' => 'Beta-hCG (Total Quantitative)',    'unit' => 'mIU/mL','loinc' => '21198-7', 'norm' => '< 5.0 (Non-preg)','norm_w' => '< 5.0 (Non-preg)','gender' => 0],
                    ['testid' => 'SER_AMH',       'name' => 'Anti-Müllerian Hormone (AMH)',     'unit' => 'ng/mL', 'loinc' => '54347-0', 'norm' => '1.5 - 4.0 (Normal reserve)','norm_w' => '1.5 - 4.0 (Normal reserve)','gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 30. HEPATITIS & EXTENDED VIRAL SEROLOGY PANEL
            // -------------------------------------------------------------
            [
                'title'          => 'Hepatitis & Extended Viral Serology Panel',
                'grp_key'        => 2, // Serology
                'loinc_code'     => '24349-3',
                'charge_desc'    => 'COMPLETE VIRAL HEPATITIS PROFILE',
                'charge_amount'  => 1800.00,
                'interpretation' => 'Comprehensive screening and differential diagnosis of viral hepatitis (A, B, C, E). Anti-HBs titer >= 10 mIU/mL indicates protective immunity against Hepatitis B (vaccine-induced or resolved infection).',
                'tests'          => [
                    ['testid' => 'HBSAG_RAPID',  'name' => 'Hepatitis B Surface Antigen (HBsAg)',   'unit' => '',       'loinc' => '5196-1',  'norm' => 'Non-Reactive', 'norm_w' => 'Non-Reactive', 'gender' => 0],
                    ['testid' => 'ANTI_HCV_RPD', 'name' => 'Anti-HCV Antibody (Total)',             'unit' => '',       'loinc' => '13955-0', 'norm' => 'Non-Reactive', 'norm_w' => 'Non-Reactive', 'gender' => 0],
                    ['testid' => 'HAV_IGM',      'name' => 'Hepatitis A Virus IgM Antibody (HAV IgM)','unit'=> '',       'loinc' => '13950-1', 'norm' => 'Non-Reactive', 'norm_w' => 'Non-Reactive', 'gender' => 0],
                    ['testid' => 'HEV_IGM',      'name' => 'Hepatitis E Virus IgM Antibody (HEV IgM)','unit'=> '',       'loinc' => '5198-7',  'norm' => 'Non-Reactive', 'norm_w' => 'Non-Reactive', 'gender' => 0],
                    ['testid' => 'ANTI_HBS_TIT', 'name' => 'Anti-HBs Titer (Quantitative)',         'unit' => 'mIU/mL', 'loinc' => '22322-2', 'norm' => '>= 10.0 (Protected)','norm_w' => '>= 10.0 (Protected)','gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 31. EXTENDED COAGULATION & THROMBOSIS PROFILE
            // -------------------------------------------------------------
            [
                'title'          => 'Extended Coagulation & Thrombosis Profile',
                'grp_key'        => 6, // Hematology
                'loinc_code'     => '24350-1',
                'charge_desc'    => 'COMPREHENSIVE COAGULATION & THROMBOSIS WORKUP',
                'charge_amount'  => 1600.00,
                'interpretation' => 'Thorough pre-operative and bleeding diathesis assessment. Evaluates extrinsic (PT), intrinsic (APTT), and common pathways, fibrinogen availability, and common pathway/fibrinolytic activation.',
                'tests'          => [
                    ['testid' => 'COAG_PT',   'name' => 'Prothrombin Time (PT)',         'unit' => 'seconds', 'loinc' => '5902-2', 'norm' => '11.0 - 14.0', 'norm_w' => '11.0 - 14.0', 'gender' => 0],
                    ['testid' => 'COAG_INR',  'name' => 'International Normalized Ratio (INR)','unit'=>'',    'loinc' => '6301-6', 'norm' => '0.85 - 1.15', 'norm_w' => '0.85 - 1.15', 'gender' => 0],
                    ['testid' => 'COAG_APTT', 'name' => 'Activated Partial Thromboplastin Time (APTT)','unit'=>'seconds','loinc'=>'3173-2','norm'=>'26.0 - 36.0','norm_w'=>'26.0 - 36.0','gender'=>0],
                    ['testid' => 'COAG_FIB',  'name' => 'Plasma Fibrinogen (Clauss)',    'unit' => 'mg/dL',   'loinc' => '3255-7', 'norm' => '200 - 400',   'norm_w' => '200 - 400',   'gender' => 0],
                    ['testid' => 'COAG_TT',   'name' => 'Thrombin Time (TT)',            'unit' => 'seconds', 'loinc' => '3269-8', 'norm' => '14.0 - 19.0', 'norm_w' => '14.0 - 19.0', 'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 32. 24-HOUR URINE PROTEIN & CREATININE CLEARANCE
            // -------------------------------------------------------------
            [
                'title'          => '24-Hour Urine Protein & Creatinine Clearance',
                'grp_key'        => 1, // Biochemistry
                'loinc_code'     => '24351-9',
                'charge_desc'    => '24-HOUR URINE PROTEIN & CREATININE CLEARANCE',
                'charge_amount'  => 650.00,
                'interpretation' => 'Gold standard quantitative measurement for nephrotic syndrome (> 3.5 g/24h), preeclampsia (> 300 mg/24h), and precise GFR assessment via endogenous creatinine clearance.',
                'tests'          => [
                    ['testid' => 'U24_VOL',    'name' => '24-Hour Urine Volume',            'unit' => 'mL/24h',   'loinc' => '3167-4', 'norm' => '800 - 2000',  'norm_w' => '800 - 2000',  'gender' => 0],
                    ['testid' => 'U24_PROT',   'name' => '24-Hour Urine Total Protein',     'unit' => 'mg/24h',   'loinc' => '2888-6', 'norm' => '< 150',      'norm_w' => '< 150',      'gender' => 0],
                    ['testid' => 'U24_CREAT',  'name' => '24-Hour Urine Creatinine',        'unit' => 'g/24h',    'loinc' => '2162-6', 'norm' => '1.0 - 2.0',   'norm_w' => '0.8 - 1.6',   'gender' => 1],
                    ['testid' => 'U24_CR_CLR', 'name' => 'Creatinine Clearance (GFR est)', 'unit' => 'mL/min',   'loinc' => '2164-2', 'norm' => '95 - 140',    'norm_w' => '85 - 125',    'gender' => 1],
                ],
            ],

            // -------------------------------------------------------------
            // 33. AUTOIMMUNE & VASCULITIS SCREEN
            // -------------------------------------------------------------
            [
                'title'          => 'Autoimmune & Vasculitis Screen',
                'grp_key'        => 2, // Serology & Immunology
                'loinc_code'     => '24352-7',
                'charge_desc'    => 'AUTOIMMUNE & SYSTEMIC VASCULITIS WORKUP',
                'charge_amount'  => 2800.00,
                'interpretation' => 'Screening profile for Systemic Lupus Erythematosus (SLE), systemic vasculitides (Granulomatosis with polyangiitis / Microscopic polyangiitis), and systemic autoimmune rheumatic diseases.',
                'tests'          => [
                    ['testid' => 'ANA_IFA',    'name' => 'Antinuclear Antibodies (ANA by IFA)','unit' => 'Titer', 'loinc' => '8061-4',  'norm' => 'Negative (< 1:80)','norm_w' => 'Negative (< 1:80)','gender' => 0],
                    ['testid' => 'ANTI_DSDNA', 'name' => 'Anti-dsDNA Antibodies (Quantitative)','unit' => 'IU/mL', 'loinc' => '5130-0',  'norm' => '< 25.0 (Negative)','norm_w' => '< 25.0 (Negative)','gender' => 0],
                    ['testid' => 'C_ANCA',     'name' => 'c-ANCA / PR3 Antibodies',            'unit' => 'RU/mL', 'loinc' => '31448-4', 'norm' => '< 20.0 (Negative)','norm_w' => '< 20.0 (Negative)','gender' => 0],
                    ['testid' => 'P_ANCA',     'name' => 'p-ANCA / MPO Antibodies',            'unit' => 'RU/mL', 'loinc' => '31449-2', 'norm' => '< 20.0 (Negative)','norm_w' => '< 20.0 (Negative)','gender' => 0],
                    ['testid' => 'RF_QUANT',   'name' => 'Rheumatoid Factor (Turbidimetry)',   'unit' => 'IU/mL', 'loinc' => '11572-5', 'norm' => '< 14.0',           'norm_w' => '< 14.0',           'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 34. BLOOD CULTURE & ANTIMICROBIAL SENSITIVITY REPORTING STANDARD
            // -------------------------------------------------------------
            [
                'title'          => 'Blood Culture & Antimicrobial Sensitivity Reporting',
                'grp_key'        => 3, // Microbiology
                'loinc_code'     => '600-7',
                'charge_desc'    => 'BLOOD CULTURE & SENSITIVITY (AUTOMATED BACT/ALERT)',
                'charge_amount'  => 1200.00,
                'interpretation' => 'Standardized blood culture protocol utilizing automated continuous monitoring. Preliminary reports issued at 24 and 48 hours. Final report issued at 5-7 days if no microbial growth observed.',
                'tests'          => [
                    ['testid' => 'BC_INCUB_TIME', 'name' => 'Incubation Duration',               'unit' => 'Days',   'loinc' => '22634-0', 'norm' => '5 - 7 Days',       'norm_w' => '5 - 7 Days',       'gender' => 0],
                    ['testid' => 'BC_GROWTH_STAT','name' => 'Culture Growth Status',             'unit' => '',       'loinc' => '5799-2',  'norm' => 'Sterile / No Growth','norm_w' => 'Sterile / No Growth','gender' => 0],
                    ['testid' => 'BC_ORG_ISOL',   'name' => 'Organism Isolated',                 'unit' => '',       'loinc' => '600-7',   'norm' => 'None',              'norm_w' => 'None',              'gender' => 0],
                    ['testid' => 'BC_COLONY_CNT', 'name' => 'Colony Count / Morphology',         'unit' => '',       'loinc' => '23637-2', 'norm' => 'N/A',               'norm_w' => 'N/A',               'gender' => 0],
                    ['testid' => 'BC_GRAM_STAIN', 'name' => 'Gram Stain from Broth',             'unit' => '',       'loinc' => '664-3',   'norm' => 'No organism seen',  'norm_w' => 'No organism seen',  'gender' => 0],
                    ['testid' => 'BC_SENS_TABLE', 'name' => 'Antimicrobial Susceptibility Table','unit' => '',       'loinc' => '18769-0', 'norm' => 'Refer to AST Table','norm_w' => 'Refer to AST Table','gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 35. URINE CULTURE & ANTIMICROBIAL SENSITIVITY REPORTING STANDARD
            // -------------------------------------------------------------
            [
                'title'          => 'Urine Culture & Antimicrobial Sensitivity Reporting',
                'grp_key'        => 3, // Microbiology
                'loinc_code'     => '630-4',
                'charge_desc'    => 'URINE CULTURE & SENSITIVITY',
                'charge_amount'  => 800.00,
                'interpretation' => 'Colony counts >= 10^5 CFU/mL of a single uropathogen in clean-catch midstream urine indicates significant bacteriuria (UTI). Sensitivity reported according to CLSI guidelines.',
                'tests'          => [
                    ['testid' => 'UC_COLONY_CNT', 'name' => 'Colony Count (Quantitative)',       'unit' => 'CFU/mL', 'loinc' => '5799-2',  'norm' => '< 10000 (No signif growth)','norm_w' => '< 10000 (No signif growth)','gender' => 0],
                    ['testid' => 'UC_BACT_STAT',  'name' => 'Significant Bacteriuria Status',    'unit' => '',       'loinc' => '630-4',   'norm' => 'Negative',          'norm_w' => 'Negative',          'gender' => 0],
                    ['testid' => 'UC_ORG_ISOL',   'name' => 'Organism Identified',               'unit' => '',       'loinc' => '20796-9', 'norm' => 'None isolated',    'norm_w' => 'None isolated',    'gender' => 0],
                    ['testid' => 'UC_AST_PANEL',  'name' => 'Antimicrobial Sensitivity Results', 'unit' => '',       'loinc' => '18769-0', 'norm' => 'Refer to AST Table','norm_w' => 'Refer to AST Table','gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 36. SURGICAL PATHOLOGY / BIOPSY GROSS & MICROSCOPIC TEMPLATE
            // -------------------------------------------------------------
            [
                'title'          => 'Surgical Pathology / Biopsy Gross & Microscopic Reporting',
                'grp_key'        => 4, // Histopathology
                'loinc_code'     => '22637-3',
                'charge_desc'    => 'HISTOPATHOLOGY EXAMINATION (MEDIUM / LARGE BIOPSY)',
                'charge_amount'  => 1500.00,
                'interpretation' => 'Standardized CAP/WHO surgical pathology reporting. Correlate with clinical, radiological, and operative findings.',
                'tests'          => [
                    ['testid' => 'HISTO_SPEC',    'name' => 'Specimen Site & Type',            'unit' => '',       'loinc' => '31206-6', 'norm' => 'Tissue Biopsy',     'norm_w' => 'Tissue Biopsy',     'gender' => 0],
                    ['testid' => 'HISTO_GROSS',   'name' => 'Gross Examination Description',   'unit' => '',       'loinc' => '22634-0', 'norm' => 'Gross recorded',    'norm_w' => 'Gross recorded',    'gender' => 0],
                    ['testid' => 'HISTO_MICR',    'name' => 'Microscopic Histopathological Exam','unit'=>'',      'loinc' => '22635-7', 'norm' => 'Sections examined', 'norm_w' => 'Sections examined', 'gender' => 0],
                    ['testid' => 'HISTO_DIAG',    'name' => 'Histopathological Diagnosis',     'unit' => '',       'loinc' => '22637-3', 'norm' => 'Diagnostic Opinion','norm_w' => 'Diagnostic Opinion','gender' => 0],
                    ['testid' => 'HISTO_MARGIN',  'name' => 'Surgical Resection Margins',      'unit' => '',       'loinc' => '44652-6', 'norm' => 'Free of lesion',    'norm_w' => 'Free of lesion',    'gender' => 0],
                ],
            ],

            // -------------------------------------------------------------
            // 37. CYTOPATHOLOGY (FNAC & PAP SMEAR) REPORTING TEMPLATE
            // -------------------------------------------------------------
            [
                'title'          => 'Cytopathology (FNAC & Cervical Pap Smear) Reporting',
                'grp_key'        => 4, // Cytopathology
                'loinc_code'     => '10524-7',
                'charge_desc'    => 'FINE NEEDLE ASPIRATION CYTOLOGY (FNAC) / PAP SMEAR',
                'charge_amount'  => 900.00,
                'interpretation' => 'Cervical Pap smears classified according to The Bethesda System (TBS 2014). Thyroid aspirates classified according to The Bethesda System for Reporting Thyroid Cytopathology (TBSRTC). Breast aspirates classified by IAC Yokohama System.',
                'tests'          => [
                    ['testid' => 'CYTO_SITE',     'name' => 'Specimen Site & Sampling Technique','unit'=> '',       'loinc' => '31206-6', 'norm' => 'Cytology Sample',   'norm_w' => 'Cytology Sample',   'gender' => 0],
                    ['testid' => 'CYTO_ADEQ',     'name' => 'Specimen Adequacy',                 'unit' => '',       'loinc' => '10524-7', 'norm' => 'Satisfactory for evaluation','norm_w' => 'Satisfactory for evaluation','gender' => 0],
                    ['testid' => 'CYTO_FIND',     'name' => 'Microscopic Cytological Findings',  'unit' => '',       'loinc' => '22635-7', 'norm' => 'Cells examined',    'norm_w' => 'Cells examined',    'gender' => 0],
                    ['testid' => 'CYTO_BETHESDA', 'name' => 'Bethesda Categorization / Class',   'unit' => '',       'loinc' => '19762-4', 'norm' => 'NILM / Category II (Benign)','norm_w' => 'NILM / Category II (Benign)','gender' => 0],
                    ['testid' => 'CYTO_IMPR',     'name' => 'Cytological Impression',            'unit' => '',       'loinc' => '22637-3', 'norm' => 'Negative for malignancy','norm_w' => 'Negative for malignancy','gender' => 0],
                ],
            ],
        ];
    }
}

