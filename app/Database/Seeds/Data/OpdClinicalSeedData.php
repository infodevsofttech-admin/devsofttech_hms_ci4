<?php

namespace App\Database\Seeds\Data;

/**
 * Standard OPD Clinical Templates for Doctor Consultation Workspace (`opd_clinical_templates`)
 * Covers physical findings, provisional diagnoses, final diagnoses, prescriber remarks, and advice.
 */
class OpdClinicalSeedData
{
    public static function getTemplates(): array
    {
        return [
            // =================================================================
            // PHYSICAL EXAMINATION FINDINGS (`finding_examinations`)
            // =================================================================
            [
                'section_key'   => 'finding_examinations',
                'template_name' => 'General Physical Examination - Complete Normal',
                'template_text' => "Patient conscious, alert, oriented to time, place, and person. Cooperative for examination.\nNutrition: Well-nourished. Built: Moderate.\nNo pallor, icterus, cyanosis, clubbing, generalized lymphadenopathy, or pedal edema.\nVitals: Pulse: Regular, good volume. BP: Normotensive. RR: Regular, non-labored. Temp: Afebrile. SpO2: Normal on room air.\nCVS: S1, S2 normally heard, no added sounds or murmurs.\nRS: Normal vesicular breath sounds bilaterally, no rhonchi or crepitations.\nP/A: Soft, non-tender, no guarding, rigidity, or palpable organomegaly. Bowel sounds normal.\nCNS: Higher mental functions normal. Cranial nerves grossly intact. Motor and sensory examination normal.",
            ],
            [
                'section_key'   => 'finding_examinations',
                'template_name' => 'Cardiovascular (CVS) Examination - Heart Failure Workup',
                'template_text' => "Pulse: Tachycardic / irregularly irregular. BP: Recorded.\nJVP: Elevated [JVP_CM] cm above sternal angle with prominent v-wave.\nBilateral pitting pedal edema present up to mid-shin.\nCVS: Apex beat displaced laterally to 6th ICS anterior axillary line. S1, S2 heard with S3 gallop rhythm. Grade 3/6 pansystolic murmur audible at apex radiating to axilla.\nRS: Bilateral fine basal inspiratory crepitations present in lower zones.",
            ],
            [
                'section_key'   => 'finding_examinations',
                'template_name' => 'Respiratory (RS) Examination - Bronchospasm & Wheeze',
                'template_text' => "Patient in respiratory distress / comfortable at rest. Accessory muscles of respiration: active/inactive.\nChest expansion symmetrical bilaterally.\nPercussion note: Resonant throughout both lung fields.\nAuscultation: Vesicular breath sounds with prolonged expiratory phase. Bilateral diffuse polyphonic expiratory wheezing / rhonchi audible throughout all zones. No coarse crackles.",
            ],
            [
                'section_key'   => 'finding_examinations',
                'template_name' => 'Abdominal Examination - Acute Surgical Abdomen Screen',
                'template_text' => "Inspection: Abdomen mildly distended / scaphoid, moves symmetrically with respiration. Umbilicus central and inverted.\nPalpation: Tenderness elicited over [RIGHT ILIAC FOSSA / EPIGASTRIUM / RUQ]. Involuntary guarding and rebound tenderness (Blumberg sign): [POSITIVE / NEGATIVE]. Rovsing sign and Psoas sign: [NEGATIVE]. No palpable mass.\nPercussion: Tympanitic throughout. No shifting dullness or fluid thrill.\nAuscultation: Bowel sounds sluggish / hyperactive rushes audible.",
            ],
            [
                'section_key'   => 'finding_examinations',
                'template_name' => 'Neurological (CNS) Examination - Stroke & Focal Deficit Screen',
                'template_text' => "GCS: E4V5M6 = 15/15. Speech: Fluent / dysarthric. No aphasia.\nCranial Nerves: Pupils equal, round, and reactive to light. Extraocular movements full. Facial asymmetry: Left/Right nasolabial fold flattening present/absent. Tongue protrusion midline.\nMotor System: Bulk: Symmetrical. Tone: Normal / spastic in [LIMB]. Power: Right Upper Limb: 5/5, Right Lower Limb: 5/5, Left Upper Limb: 5/5, Left Lower Limb: 5/5.\nReflexes: Biceps ++, Triceps ++, Knee ++, Ankle ++. Plantar response: Flexor bilaterally / Extensor on [SIDE].\nSensory & Cerebellar: Light touch and pinprick intact. Finger-nose and heel-shin coordination normal. Romberg test negative.",
            ],
            [
                'section_key'   => 'finding_examinations',
                'template_name' => 'Musculoskeletal & Knee Joint Examination',
                'template_text' => "Gait: Antalgic / normal gait.\nInspection: Bilateral knees show mild varus deformity. No erythema or visible sinus. Quadriceps wasting: Mild on [SIDE].\nPalpation: Joint line tenderness present along medial joint line. No local warmth. Patellar tap test: Mild effusion present/absent.\nRange of Motion: Active flexion 0° to 120°, mild terminal pain. Extension full.\nLigamentous Stability: Anterior Drawer, Posterior Drawer, Lachman, and Collateral stress tests: Stable. McMurray test: Negative for mechanical locking.",
            ],
            [
                'section_key'   => 'finding_examinations',
                'template_name' => 'Pediatric General & Nutritional Assessment',
                'template_text' => "Child active, alert, making good eye contact with mother. Cry vigorous.\nWeight: [WT] kg ([PERCENTILE]th percentile). Height/Length: [HT] cm. Head Circumference: [HC] cm.\nAnterior fontanelle: Level, normotensive (neither sunken nor bulging).\nHydration: Tears present, mucous membranes moist, skin turgor brisk (< 2 sec).\nThroat: Tonsils normal, no erythema or exudates. Ears: Tympanic membranes pearly grey bilaterally.\nChest: Clear bilaterally, no retractions or grunting. Abdomen: Soft, non-tender, no hepatosplenomegaly.",
            ],
            [
                'section_key'   => 'finding_examinations',
                'template_name' => 'Antenatal Obstetric Examination (Third Trimester)',
                'template_text' => "General: Pallor absent, pedal edema nil / mild physiological.\nBP: ____/____ mmHg (recorded in sitting position). Urine albumin: Nil.\nPer Abdomen Examination:\n- Fundal Height: Corresponds to [WEEKS] weeks gestational age.\n- Symphysio-Fundal Height (SFH): [SFH_CM] cm.\n- Presentation & Lie: Longitudinal lie, Cephalic presentation. Head high / engaged.\n- Fetal Heart Rate (FHR): 140 bpm, regular, good variability.\n- Fetal Movements: Well perceived by mother. Uterine contractions: Absent.",
            ],

            // =================================================================
            // PROVISIONAL DIAGNOSIS (`provisional_diagnosis`)
            // =================================================================
            [
                'section_key'   => 'provisional_diagnosis',
                'template_name' => 'Provisional - Acute Febrile Illness (AFI)',
                'template_text' => "Provisional Diagnosis: Acute Febrile Illness (AFI) under evaluation. Differentials: Viral fever, Dengue fever, Enteric (Typhoid) fever, Malaria, Urinary Tract Infection. CBC, Dengue NS1/IgM, Widal, and Urine R/M advised.",
            ],
            [
                'section_key'   => 'provisional_diagnosis',
                'template_name' => 'Provisional - Dyspepsia & Acid Peptic Disorder',
                'template_text' => "Provisional Diagnosis: Uninvestigated Dyspepsia / Gastroesophageal Reflux Disease (GERD). Differentials: Peptic ulcer disease, functional dyspepsia, biliary colic. Red-flag alarm symptoms (dysphagia, weight loss, hematemesis) absent.",
            ],
            [
                'section_key'   => 'provisional_diagnosis',
                'template_name' => 'Provisional - Lumbar Spondylosis with Radiculopathy',
                'template_text' => "Provisional Diagnosis: Lumbar Spondylosis with Right/Left L5-S1 Radiculopathy (Sciatica). Differentials: Lumbar disc herniation, facet arthropathy, piriformis syndrome. Red flags (cauda equina / bladder incontinence) absent.",
            ],
            [
                'section_key'   => 'provisional_diagnosis',
                'template_name' => 'Provisional - Bronchial Asthma in Exacerbation',
                'template_text' => "Provisional Diagnosis: Bronchial Asthma with Mild to Moderate Acute Exacerbation. Differentials: Allergic bronchitis, viral reactive airway disease, GERD-associated bronchospasm.",
            ],
            [
                'section_key'   => 'provisional_diagnosis',
                'template_name' => 'Provisional - Essential Hypertension Follow-up',
                'template_text' => "Provisional Diagnosis: Essential Hypertension (Stage-1/2) on irregular/regular treatment. Assess for target organ damage and ambulatory BP control.",
            ],

            // =================================================================
            // FINAL DIAGNOSIS (`diagnosis`)
            // =================================================================
            [
                'section_key'   => 'diagnosis',
                'template_name' => 'Acute Upper Respiratory Tract Infection (URI)',
                'template_text' => "Acute Viral Upper Respiratory Tract Infection (Common Cold / Pharyngitis) (ICD-10: J06.9).",
            ],
            [
                'section_key'   => 'diagnosis',
                'template_name' => 'Essential Primary Hypertension',
                'template_text' => "Essential (Primary) Hypertension - Grade 1 (ICD-10: I10).",
            ],
            [
                'section_key'   => 'diagnosis',
                'template_name' => 'Type 2 Diabetes Mellitus - Controlled / Uncontrolled',
                'template_text' => "Type 2 Diabetes Mellitus without acute complications (ICD-10: E11.9).",
            ],
            [
                'section_key'   => 'diagnosis',
                'template_name' => 'Acute Gastroenteritis (Non-Infective / Infective)',
                'template_text' => "Acute Infective Gastroenteritis without significant dehydration (ICD-10: A09).",
            ],
            [
                'section_key'   => 'diagnosis',
                'template_name' => 'Primary Osteoarthritis of Bilateral Knees',
                'template_text' => "Primary Osteoarthritis of Bilateral Knees (Grade II-III) (ICD-10: M17.0).",
            ],
            [
                'section_key'   => 'diagnosis',
                'template_name' => 'Gastroesophageal Reflux Disease (GERD)',
                'template_text' => "Gastroesophageal Reflux Disease (GERD) with reflux esophagitis (ICD-10: K21.0).",
            ],
            [
                'section_key'   => 'diagnosis',
                'template_name' => 'Urinary Tract Infection (Uncomplicated Acute Cystitis)',
                'template_text' => "Acute Uncomplicated Cystitis / Lower Urinary Tract Infection (ICD-10: N30.0).",
            ],

            // =================================================================
            // PRESCRIBER REMARKS & SAFETY NET (`prescriber_remarks`)
            // =================================================================
            [
                'section_key'   => 'prescriber_remarks',
                'template_name' => 'Emergency Return & Red-Flag Warnings',
                'template_text' => "Patient and family were counseled regarding danger signs. Seek IMMEDIATE emergency medical care if experiencing: (a) High fever (> 101 F) not responding to paracetamol, (b) Sudden breathlessness, chest discomfort, or cyanosis, (c) Persistent vomiting, inability to drink fluids, or dark urine, (d) Altered sensorium, severe dizziness, or convulsions, (e) Active bleeding from any site.",
            ],
            [
                'section_key'   => 'prescriber_remarks',
                'template_name' => 'Antibiotic Stewardship & Adherence Counseling',
                'template_text' => "Antibiotic regimen explained thoroughly with exact dosage, timing, and duration. Emphasized importance of completing full prescribed course even if symptoms resolve earlier. Warned against self-medication, sharing antibiotics, or stopping early to prevent microbial resistance.",
            ],
            [
                'section_key'   => 'prescriber_remarks',
                'template_name' => 'Cardiometabolic Lifestyle Counseling',
                'template_text' => "Comprehensive counseling provided on non-pharmacological management: (1) Dietary sodium restriction (< 5 g/day table salt), (2) Refined sugar and saturated fat elimination, (3) Brisk walking 30-45 minutes at least 5 days a week, (4) Tobacco and alcohol cessation, (5) Stress reduction and 7-8 hours uninterrupted sleep.",
            ],
            [
                'section_key'   => 'prescriber_remarks',
                'template_name' => 'Pediatric Hydration & ORS Safety Precaution',
                'template_text' => "Counseling given to parents: Maintain adequate hydration using Oral Rehydration Salts (ORS) solution after each loose motion. Continue normal age-appropriate feeding including breast milk. Return immediately if child becomes excessively drowsy, irritable, refuses feeds, or passes blood in stools.",
            ],

            // =================================================================
            // PATIENT ADVICE & LIFESTYLE PROTOCOLS (`advice`)
            // =================================================================
            [
                'section_key'   => 'advice',
                'template_name' => 'Acute Fever & Viral Home Care Advice',
                'template_text' => "1. Plenty of oral fluids (water, ORS, coconut water, lemon water, clear soups) — minimum 2.5 to 3 liters daily.\n2. Light, freshly prepared, easily digestible soft diet (khichdi, dalia, curd rice, fruits).\n3. Tepid sponge bathing with room-temperature water if temperature exceeds 101 F.\n4. Complete physical rest. Maintain daily temperature log.\n5. Review in OPD after 3 days with requested blood investigation reports.",
            ],
            [
                'section_key'   => 'advice',
                'template_name' => 'Gastritis & Acid Reflux (GERD) Diet Advice',
                'template_text' => "1. Avoid excessively spicy, deep-fried, oily, and sour foods.\n2. Avoid excess tea, coffee, carbonated cold drinks, chocolates, and mints.\n3. Take small, frequent meals at fixed timings instead of large heavy meals.\n4. Have dinner at least 2 to 3 hours before going to sleep.\n5. Elevate head of bed by 6 inches if experiencing night-time acid regurgitation.\n6. Strictly avoid tobacco in any form (smoking/chewing) and alcohol.",
            ],
            [
                'section_key'   => 'advice',
                'template_name' => 'Hypertension Home Management Advice',
                'template_text' => "1. Strictly take prescribed anti-hypertensive medication daily at the same time without missing doses.\n2. Low salt diet: Do not add extra table salt to food; avoid pickles, papad, salted chips, and processed canned foods.\n3. Measure and record blood pressure at home twice weekly and bring BP diary on every visit.\n4. Daily 30 minutes of aerobic physical activity (brisk walking/swimming/cycling).\n5. Review in OPD after 2-4 weeks for BP review.",
            ],
            [
                'section_key'   => 'advice',
                'template_name' => 'Type 2 Diabetes Home Care & Foot Care Advice',
                'template_text' => "1. Take diabetic medications / insulin strictly as instructed in relation to meals.\n2. Diabetic Diet: Strictly avoid sugar, sweets, honey, jaggery, sweetened fruit juices, and potatoes. Increase green leafy vegetables, salads, and whole pulses.\n3. Foot Care: Inspect feet daily for cuts, blisters, redness, or cracks. Never walk barefoot. Wear comfortable, well-fitting footwear.\n4. Self-monitoring of Blood Glucose (SMBG): Check fasting and 2-hour post-meal blood sugar weekly.\n5. Annual screening advised for diabetic retinopathy (eye exam) and diabetic nephropathy (urine microalbumin).",
            ],
            [
                'section_key'   => 'advice',
                'template_name' => 'Low Back Pain & Ergonomic Spine Posture Advice',
                'template_text' => "1. Avoid prolonged continuous sitting (> 45 minutes); take short walking breaks.\n2. Maintain erect sitting posture with lumbar support cushion.\n3. Sleep on a firm, comfortable mattress; avoid very soft or sagging beds.\n4. Strictly avoid bending forward at the waist to lift weights; bend at knees and keep back straight.\n5. Start core-strengthening and back-extension exercises once acute pain subsides, as demonstrated by physiotherapist.",
            ],
        ];
    }
}
