<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * RadiologyTemplatesSeeder
 *
 * Seeds standardized, professional clinical templates and corresponding charge items
 * for:
 *   - X-Ray   (Modality / itype = 3)
 *   - CT-Scan (Modality / itype = 4)
 *   - MRI     (Modality / itype = 2)
 *
 * Safe to re-run (idempotent — updates existing by template_name + Modality).
 *
 * Usage:
 *   php spark db:seed RadiologyTemplatesSeeder
 */
class RadiologyTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $db = $this->db;

        if (! $db->tableExists('radiology_ultrasound_template')) {
            CLI::write('Table radiology_ultrasound_template does not exist. Skipping.', 'red');
            return;
        }

        $now = date('Y-m-d H:i:s');

        // ---------------------------------------------------------------------
        // 1. Ensure Charge Services exist in `hc_items`
        // ---------------------------------------------------------------------
        $services = [
            // X-Ray (itype = 3)
            ['itype' => 3, 'desc' => 'X-RAY CHEST PA VIEW',                    'amount' => 350.00],
            ['itype' => 3, 'desc' => 'X-RAY CHEST AP VIEW',                    'amount' => 350.00],
            ['itype' => 3, 'desc' => 'X-RAY BOTH KNEE JOINTS AP & LATERAL',     'amount' => 500.00],
            ['itype' => 3, 'desc' => 'X-RAY LS SPINE AP & LATERAL',             'amount' => 600.00],
            ['itype' => 3, 'desc' => 'X-RAY CERVICAL SPINE AP & LATERAL',       'amount' => 550.00],
            ['itype' => 3, 'desc' => 'X-RAY ABDOMEN ERECT (KUB)',               'amount' => 450.00],
            ['itype' => 3, 'desc' => 'X-RAY PNS (WATER\'S VIEW)',               'amount' => 400.00],
            ['itype' => 3, 'desc' => 'X-RAY PELVIS WITH BOTH HIPS AP',          'amount' => 500.00],

            // CT-Scan (itype = 4)
            ['itype' => 4, 'desc' => 'NCCT HEAD / BRAIN',                       'amount' => 1800.00],
            ['itype' => 4, 'desc' => 'NCCT HEAD (STROKE PROTOCOL)',             'amount' => 2000.00],
            ['itype' => 4, 'desc' => 'HRCT CHEST / THORAX',                     'amount' => 2500.00],
            ['itype' => 4, 'desc' => 'CECT WHOLE ABDOMEN & PELVIS',             'amount' => 4500.00],
            ['itype' => 4, 'desc' => 'NCCT KUB (KIDNEYS, URETERS, BLADDER)',    'amount' => 2200.00],
            ['itype' => 4, 'desc' => 'CECT CHEST',                              'amount' => 3500.00],
            ['itype' => 4, 'desc' => 'NCCT CERVICAL / LUMBAR SPINE',            'amount' => 2500.00],

            // MRI (itype = 2)
            ['itype' => 2, 'desc' => 'MRI BRAIN (PLAIN STUDY)',                 'amount' => 4000.00],
            ['itype' => 2, 'desc' => 'MRI BRAIN (STROKE / DWI PROTOCOL)',       'amount' => 4500.00],
            ['itype' => 2, 'desc' => 'MRI LUMBOSACRAL (LS) SPINE',              'amount' => 4200.00],
            ['itype' => 2, 'desc' => 'MRI CERVICAL SPINE',                      'amount' => 4200.00],
            ['itype' => 2, 'desc' => 'MRI KNEE JOINT (RIGHT / LEFT)',           'amount' => 4500.00],
            ['itype' => 2, 'desc' => 'MRI WHOLE SPINE SCREENING',               'amount' => 5500.00],
            ['itype' => 2, 'desc' => 'MRI SHOULDER JOINT',                      'amount' => 4500.00],
        ];

        $chargeIdMap = [];
        if ($db->tableExists('hc_items')) {
            foreach ($services as $svc) {
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
                        'last_update_desc' => 'Seeded by RadiologyTemplatesSeeder',
                    ]);
                    $chargeIdMap[$svc['desc']] = (int) $db->insertID();
                }
            }
        }

        // ---------------------------------------------------------------------
        // 2. Master Templates List
        // ---------------------------------------------------------------------
        $templates = [

            // =================================================================
            // X-RAY TEMPLATES (Modality = 3)
            // =================================================================
            [
                'template_name'  => 'X-RAY CHEST PA VIEW (Normal)',
                'title'          => 'X-Ray Chest PA View',
                'keywords'       => 'chest, pa view, lungs, heart, normal, thorax, pulmonary, rib cage',
                'Modality'       => 3,
                'service_key'    => 'X-RAY CHEST PA VIEW',
                'Findings'       => <<<HTML
<p><strong>TRACHEA & MEDIASTINUM :</strong> Trachea is central in position. Mediastinum shows normal configuration with no shift or widening.</p>
<p><strong>CARDIAC SILHOUETTE :</strong> Heart size is within normal limits. Cardio-thoracic ratio (CTR) is less than 0.5. Aortic knuckle appears unremarkable.</p>
<p><strong>LUNG PARENCHYMA :</strong> Both lung fields are clear with normal bronchovascular arborization. No focal parenchymal consolidation, mass, cavitation, or active infiltration seen.</p>
<p><strong>HILAR REGIONS :</strong> Bilateral hilar shadows are normal in size, density, and configuration with no evidence of lymphadenopathy.</p>
<p><strong>COSTOPHRENIC & CARDIOPHRENIC ANGLES :</strong> Both CP and cardiophrenic angles are acute, clear, and sharp. No evidence of pleural effusion or pleural thickening.</p>
<p><strong>DIAPHRAGMS :</strong> Both domes of diaphragm are smooth, convex, and normal in position.</p>
<p><strong>BONES & SOFT TISSUES :</strong> Visualized thoracic rib cage, clavicles, and soft tissues appear unremarkable. No focal bone erosion or fracture identified.</p>
HTML
                ,
                'Impression'     => 'Normal study of Chest (PA View). No active cardiopulmonary lesion detected.',
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'X-RAY CHEST PA VIEW - Pneumonia / Consolidation',
                'title'          => 'X-Ray Chest PA View - Pneumonia',
                'keywords'       => 'chest, pneumonia, consolidation, air bronchogram, fever, cough, opacity',
                'Modality'       => 3,
                'service_key'    => 'X-RAY CHEST PA VIEW',
                'Findings'       => <<<HTML
<p><strong>TRACHEA & MEDIASTINUM :</strong> Trachea is central with no significant mediastinal shift.</p>
<p><strong>CARDIAC SILHOUETTE :</strong> Cardiac size is within normal limits for age.</p>
<p><strong>LUNG PARENCHYMA :</strong> Patchy inhomogeneous airspace opacity with air bronchograms noted in the right mid and lower lung zones, suggestive of consolidation. The left lung field appears clear with normal bronchovascular markings.</p>
<p><strong>COSTOPHRENIC ANGLES :</strong> Mild blunting of the right costophrenic angle noted, likely reactive minimal pleural fluid. The left costophrenic angle is clear and sharp.</p>
<p><strong>DIAPHRAGMS :</strong> Right dome of diaphragm is partially obscured by the overlying lower lobe opacity. Left dome is smooth and normal in contour.</p>
<p><strong>BONES & SOFT TISSUES :</strong> Visualized thoracic skeleton and surrounding soft tissues are unremarkable.</p>
HTML
                ,
                'Impression'     => "Features suggestive of Right Middle and Lower Lobe Pneumonia with minimal reactive pleural effusion.\nAdvice: Clinical correlation, sputum examination, and follow-up after antibiotic therapy.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY BOTH KNEE JOINTS AP & LATERAL - Osteoarthritis',
                'title'          => 'X-Ray Both Knee Joints AP and Lateral View',
                'keywords'       => 'knee, osteoarthritis, joint space narrowing, osteophytes, pain, arthritis',
                'Modality'       => 3,
                'service_key'    => 'X-RAY BOTH KNEE JOINTS AP & LATERAL',
                'Findings'       => <<<HTML
<p><strong>JOINT SPACES :</strong> Narrowing of the medial tibiofemoral joint space noted bilaterally (Right > Left). Lateral tibiofemoral joint space is relatively preserved.</p>
<p><strong>SUBCHONDRAL BONE :</strong> Subchondral sclerosis and flattening noted along the medial tibial plateau bilaterally.</p>
<p><strong>OSTEOPHYTES :</strong> Marginal osteophytes seen along the femoral and tibial condylar articular margins.</p>
<p><strong>PATELLOFEMORAL COMPARTMENT :</strong> Patellofemoral joint space shows mild narrowing with small patellar spurs.</p>
<p><strong>LIGAMENTS & SOFT TISSUES :</strong> Visualized soft tissues around the knee joint appear normal. No radiopaque loose body or chondrocalcinosis seen.</p>
<p><strong>BONE INTEGRITY :</strong> No fracture, dislocation, or lytic destructive bone lesion identified.</p>
HTML
                ,
                'Impression'     => "Bilateral Knee Osteoarthritis with medial compartment predominance (Grade II-III Kellgren-Lawrence changes).\nAdvice: Clinical correlation and orthopedic consultation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY LS SPINE AP & LATERAL - Lumbar Spondylosis',
                'title'          => 'X-Ray Lumbosacral (LS) Spine AP & Lateral View',
                'keywords'       => 'spine, lumbar, ls spine, back pain, spondylosis, osteophytes, disc reduction',
                'Modality'       => 3,
                'service_key'    => 'X-RAY LS SPINE AP & LATERAL',
                'Findings'       => <<<HTML
<p><strong>ALIGNMENT & CURVATURE :</strong> Mild straightening of the normal lumbar lordosis noted, likely due to paravertebral muscle spasm. No spondylolisthesis or scoliosis seen.</p>
<p><strong>VERTEBRAL BODIES :</strong> Vertebral body heights and bony architecture are maintained. Marginal osteophytic lipping noted along anterior and lateral borders of L3, L4, and L5 vertebrae.</p>
<p><strong>INTERVERTEBRAL DISC SPACES :</strong> Reduced intervertebral disc spaces noted at L4-L5 and L5-S1 levels with mild vacuum phenomenon / subchondral sclerosis.</p>
<p><strong>POSTERIOR ELEMENTS :</strong> Pedicles, laminae, and facet joints appear unremarkable. No lytic defect in pars interarticularis.</p>
<p><strong>SACROILIAC JOINTS :</strong> Both sacroiliac joints are clear with preserved joint spaces.</p>
<p><strong>PREVERTEBRAL TISSUES :</strong> Pre- and paravertebral soft tissue shadows are within normal limits.</p>
HTML
                ,
                'Impression'     => "Features suggestive of Lumbar Spondylosis with L4-L5 and L5-S1 disc degenerative disease.\nAdvice: MRI Lumbosacral Spine recommended for radicular symptoms or neural canal evaluation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY CERVICAL SPINE AP & LATERAL (Normal / Spondylosis)',
                'title'          => 'X-Ray Cervical Spine AP & Lateral View',
                'keywords'       => 'cervical, neck pain, c-spine, spondylosis, radiculopathy, disc space',
                'Modality'       => 3,
                'service_key'    => 'X-RAY CERVICAL SPINE AP & LATERAL',
                'Findings'       => <<<HTML
<p><strong>ALIGNMENT :</strong> Normal cervical lordosis is mildly straightened (likely muscle spasm). Atlantoaxial alignment is normal.</p>
<p><strong>VERTEBRAL BODIES :</strong> Vertebral body heights and densities are preserved. No bone destruction or compression fracture seen.</p>
<p><strong>DISC SPACES :</strong> Mild reduction of C5-C6 intervertebral disc space noted with small anterior marginal osteophytes. Remaining disc spaces are preserved.</p>
<p><strong>NEURAL FORAMINA & FACETS :</strong> Neural foramina and facet joints are within normal limits.</p>
<p><strong>SOFT TISSUES :</strong> Prevertebral soft tissue thickness is normal at all levels.</p>
HTML
                ,
                'Impression'     => "Features suggestive of Mild Cervical Spondylosis at C5-C6 level with muscle spasm.\nAdvice: Clinical correlation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY ABDOMEN ERECT (Plain KUB / Acute Abdomen)',
                'title'          => 'X-Ray Abdomen Erect (KUB)',
                'keywords'       => 'abdomen erect, air fluid levels, gas, bowel obstruction, perforation, kub, calculus',
                'Modality'       => 3,
                'service_key'    => 'X-RAY ABDOMEN ERECT (KUB)',
                'Findings'       => <<<HTML
<p><strong>BOWEL GAS PATTERN :</strong> Normal bowel gas distribution seen throughout small and large intestines. No pathologically dilated bowel loops.</p>
<p><strong>AIR-FLUID LEVELS :</strong> No step-ladder air-fluid levels seen to suggest dynamic intestinal obstruction.</p>
<p><strong>SUBDIAPHRAGMATIC AIR :</strong> No free air (crescent of gas) seen beneath either dome of the diaphragm (no pneumoperitoneum).</p>
<p><strong>URINARY TRACT (KUB) :</strong> No radiopaque shadow or calculus identified along the anatomical course of the kidneys, ureters, or bladder.</p>
<p><strong>PSOAS & FAT LINES :</strong> Bilateral psoas shadows and properitoneal fat lines are crisp and well preserved.</p>
<p><strong>SKELETON :</strong> Visualized lumbar spine, pelvis, and rib cage show no gross bony abnormality.</p>
HTML
                ,
                'Impression'     => 'Normal Plain X-Ray Abdomen (Erect View). No evidence of bowel obstruction, perforation, or radiopaque calculus.',
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'X-RAY PNS WATER\'S VIEW - Sinusitis',
                'title'          => 'X-Ray Paranasal Sinuses (PNS - Water\'s View)',
                'keywords'       => 'pns, water view, sinuses, maxillary, headache, sinusitis, polyps',
                'Modality'       => 3,
                'service_key'    => 'X-RAY PNS (WATER\'S VIEW)',
                'Findings'       => <<<HTML
<p><strong>MAXILLARY SINUSES :</strong> Mucosal thickening and mucosal haziness noted in the bilateral maxillary antra (Right > Left). No distinct air-fluid level or polypoid mass seen.</p>
<p><strong>FRONTAL & ETHMOID SINUSES :</strong> Frontal and ethmoidal sinuses appear normally pneumatized and aerated.</p>
<p><strong>NASAL SEPTUM & TURBINATES :</strong> Mild deviation of the nasal septum (DNS) noted towards the left with compensatory hypertrophy of the right inferior turbinate.</p>
<p><strong>BONY WALLS :</strong> Bony margins of all visualized paranasal sinuses are intact without cortical erosion.</p>
HTML
                ,
                'Impression'     => "Features suggestive of Bilateral Maxillary Sinusitis (Right > Left) with Mild DNS.\nAdvice: ENT correlation.",
                'impression_cat' => 1,
            ],

            // =================================================================
            // CT-SCAN TEMPLATES (Modality = 4)
            // =================================================================
            [
                'template_name'  => 'NCCT HEAD / BRAIN (Normal)',
                'title'          => 'Non-Contrast CT (NCCT) Head / Brain',
                'keywords'       => 'ct head, brain, normal, stroke, trauma, headache, ncct, ventricles',
                'Modality'       => 4,
                'service_key'    => 'NCCT HEAD / BRAIN',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Non-contrast axial CT sections of the brain were obtained from the base of the skull to the vertex without intravenous contrast.</p>
<p><strong>BRAIN PARENCHYMA :</strong> Both cerebral hemispheres, cerebellum, and brainstem show normal attenuation and preserved grey-white matter differentiation. No focal hypo- or hyperdense lesions noted. No evidence of acute intra-axial or extra-axial hemorrhage.</p>
<p><strong>VENTRICULAR SYSTEM :</strong> Lateral, third, and fourth ventricles are normal in size, shape, and position for age. No hydrocephalus.</p>
<p><strong>BASAL CISTERNS & SULCI :</strong> Basal cisterns, cortical sulci, and sylvian fissures are well visualized and symmetrical.</p>
<p><strong>MIDLINE STRUCTURES :</strong> Midline structures are central; no midline shift or herniation seen.</p>
<p><strong>SELLA & POSTERIOR FOSSA :</strong> Sella, parasellar regions, and cerebellopontine angles are unremarkable.</p>
<p><strong>CALVARIUM & ORBITS :</strong> Calvarial bones, skull base, orbits, and visualized paranasal sinuses show no fracture or gross pathology.</p>
HTML
                ,
                'Impression'     => 'Normal Non-Contrast CT Scan of the Brain. No acute intracranial hemorrhage, mass lesion, or midline shift.',
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'NCCT HEAD - Acute Ischemic Infarct',
                'title'          => 'NCCT Head - Acute Ischemic Infarct',
                'keywords'       => 'ct head, infarct, ischemic stroke, mca, paralysis, weakness, hypodensity',
                'Modality'       => 4,
                'service_key'    => 'NCCT HEAD (STROKE PROTOCOL)',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Non-contrast volumetric CT of the brain performed under acute stroke protocol.</p>
<p><strong>FOCAL LESION :</strong> Ill-defined, wedgeshaped area of parenchymal hypodensity noted involving the cortex and subcortical white matter of the left / right middle cerebral artery (MCA) territory.</p>
<p><strong>MASS EFFECT :</strong> Associated local sulcal effacement and mild loss of insular ribbon sign / grey-white differentiation noted. No significant midline shift or compression of lateral ventricle.</p>
<p><strong>HEMORRHAGE :</strong> No hyperdense area seen to suggest intracranial hemorrhage or hemorrhagic transformation.</p>
<p><strong>POSTERIOR FOSSA :</strong> Cerebellum, brainstem, and posterior fossa cisterns appear unremarkable.</p>
<p><strong>BONES & CISTERNS :</strong> Calvarial bones and visualized sinuses are intact.</p>
HTML
                ,
                'Impression'     => "Features suggestive of Acute Non-Hemorrhagic Ischemic Infarct in the Left / Right MCA territory.\nAdvice: Urgent neurological evaluation, MRI Brain with DWI, and MR Angiography.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'HRCT CHEST / THORAX (Normal)',
                'title'          => 'High Resolution Computed Tomography (HRCT) Thorax',
                'keywords'       => 'hrct chest, thorax, lungs, covid, interstitial, fibrosis, ggo, bronchiectasis',
                'Modality'       => 4,
                'service_key'    => 'HRCT CHEST / THORAX',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> High-resolution thin-section volumetric CT scan of the thorax was performed from lung apices to diaphragm during full inspiratory breath-hold.</p>
<p><strong>AIRWAYS :</strong> Trachea and major bronchi are normal in caliber, course, and branching pattern. No endobronchial lesion or bronchiectasis seen.</p>
<p><strong>LUNG PARENCHYMA :</strong> Both lung fields show normal parenchymal attenuation. No evidence of ground-glass opacities (GGO), consolidation, nodule, mass, or cavitation. Interlobular septa and bronchovascular interstitium are unremarkable. No honeycombing or reticulation.</p>
<p><strong>PLEURAL SPACES :</strong> Bilateral pleural spaces are clear. No pneumothorax, pleural effusion, or thickening noted.</p>
<p><strong>MEDIASTINUM & HILA :</strong> Mediastinum and hilar structures are normal. No significant mediastinal, hilar, or axillary lymphadenopathy seen.</p>
<p><strong>CARDIOVASCULAR :</strong> Heart size, ascending/descending aorta, and pulmonary trunk are within normal limits.</p>
<p><strong>CHEST WALL & BONES :</strong> Visualized thoracic skeleton, sternum, ribs, and soft tissues of the chest wall appear normal.</p>
HTML
                ,
                'Impression'     => 'Normal HRCT Thorax study. No active parenchymal infiltration, interstitial lung disease (ILD), or lymphadenopathy.',
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'CECT WHOLE ABDOMEN & PELVIS (Normal)',
                'title'          => 'Contrast Enhanced CT (CECT) Whole Abdomen and Pelvis',
                'keywords'       => 'cect abdomen, pelvis, liver, kidney, pancreas, spleen, contrast, ct abdomen',
                'Modality'       => 4,
                'service_key'    => 'CECT WHOLE ABDOMEN & PELVIS',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Contrast-enhanced helical CT of the whole abdomen and pelvis was acquired after administration of non-ionic IV contrast.</p>
<p><strong>LIVER :</strong> Normal in size and shape with homogenous parenchymal enhancement. No focal intrahepatic mass, cyst, or abscess. Intrahepatic biliary radicles (IHBR) and common bile duct are normal.</p>
<p><strong>GALLBLADDER :</strong> Well distended with normal, uniform wall enhancement. No radiopaque calculus or pericholecystic collection seen.</p>
<p><strong>PANCREAS :</strong> Normal in size, lobulation, and enhancement throughout head, body, and tail. Main pancreatic duct is not dilated. Peripancreatic fat planes are preserved.</p>
<p><strong>SPLEEN :</strong> Normal in size, configuration, and enhancement. No focal lesion.</p>
<p><strong>ADRENALS & KIDNEYS :</strong> Bilateral adrenals are normal. Both kidneys are normal in size, shape, and position with prompt symmetrical nephrogram and excretion. Corticomedullary differentiation is preserved. No calculus, hydronephrosis, or mass.</p>
<p><strong>URINARY BLADDER & PELVIS :</strong> Urinary bladder is well distended with smooth walls. Internal pelvic organs are normal for age.</p>
<p><strong>GASTROINTESTINAL TRACT :</strong> Stomach and small/large bowel loops show normal caliber, wall thickness, and mucosal enhancement. No bowel obstruction.</p>
<p><strong>VESSELS & LYMPH NODES :</strong> Abdominal aorta, IVC, and portal vein are patent. No retroperitoneal, mesenteric, or pelvic lymphadenopathy. No ascites.</p>
HTML
                ,
                'Impression'     => 'Normal Contrast-Enhanced CT (CECT) of Whole Abdomen and Pelvis. No focal pathology, organomegaly, or lymphadenopathy.',
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'NCCT KUB - Ureteric / Renal Calculus',
                'title'          => 'Non-Contrast CT (NCCT) Kidneys, Ureters, and Bladder (KUB)',
                'keywords'       => 'ncct kub, stone, calculus, kidney stone, ureteric stone, hydronephrosis, colic',
                'Modality'       => 4,
                'service_key'    => 'NCCT KUB (KIDNEYS, URETERS, BLADDER)',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Non-contrast helical CT scan of the abdomen and pelvis obtained from top of kidneys to pubic symphysis.</p>
<p><strong>KIDNEYS :</strong> Both kidneys are normal in size, shape, and anatomical position. Right kidney measures ~... cm and left kidney measures ~... cm. No focal parenchymal mass lesion.</p>
<p><strong>CALCULUS & HYDRONEPHROSIS :</strong> A dense radiopaque calculus measuring approximately ... mm (CT density ~ ... HU) is noted in the proximal / mid / distal right / left ureter. Associated mild-to-moderate upstream hydroureter and hydronephrosis seen with mild perinephric fat stranding.</p>
<p><strong>CONTRALATERAL SYSTEM :</strong> Contralateral kidney and ureter are unremarkable without calculus or hydronephrosis.</p>
<p><strong>URINARY BLADDER :</strong> Moderately distended with uniform contents. No calculus or focal wall thickening seen at the vesicoureteric junctions.</p>
<p><strong>BONES & SOFT TISSUES :</strong> Visualized lumbosacral skeleton and pelvic bones appear unremarkable.</p>
HTML
                ,
                'Impression'     => "Features suggestive of a ... mm Calculus in the Right / Left Ureter causing mild-to-moderate upstream hydronephrosis and hydroureter.\nAdvice: Urology consultation and management.",
                'impression_cat' => 1,
            ],

            // =================================================================
            // MRI TEMPLATES (Modality = 2)
            // =================================================================
            [
                'template_name'  => 'MRI BRAIN (PLAIN STUDY - Normal)',
                'title'          => 'MRI Brain (Plain Study)',
                'keywords'       => 'mri brain, normal, head, t1, t2, flair, dwi, stroke, seizure, headache',
                'Modality'       => 2,
                'service_key'    => 'MRI BRAIN (PLAIN STUDY)',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Multiplanar, multisequence MRI of the brain was performed on high-field system using T1WI, T2WI, FLAIR, Diffusion Weighted Imaging (DWI/ADC), and Gradient Echo (GRE/SWI) sequences.</p>
<p><strong>CEREBRAL PARENCHYMA :</strong> Both cerebral hemispheres show normal sulcation, gyration, and grey-white matter differentiation. No focal area of altered signal intensity noted in the cerebral cortex, subcortical white matter, or deep grey nuclei.</p>
<p><strong>DIFFUSION & HEMORRHAGE :</strong> No area of restricted diffusion on DWI/ADC to suggest acute ischemic infarction or cytotoxic edema. No blooming artifact on GRE/SWI sequences to suggest acute or chronic intracranial hemorrhage.</p>
<p><strong>BRAINSTEM & CEREBELLUM :</strong> Brainstem (midbrain, pons, medulla) and both cerebellar hemispheres appear normal in morphology and signal characteristics.</p>
<p><strong>VENTRICLES & CISTERNS :</strong> Ventricular system, basal cisterns, and sylvian fissures are normal in size, shape, and configuration for age. Midline structures are central.</p>
<p><strong>SELLA & CRANIAL NERVES :</strong> Sella, pituitary gland, optic chiasm, and bilateral CP angles are within normal limits.</p>
<p><strong>CALVARIUM & SINUSES :</strong> Visualized calvarial marrow and paranasal sinuses show normal signal intensity.</p>
HTML
                ,
                'Impression'     => 'Normal MRI Brain Study. No evidence of acute ischemia, hemorrhage, mass effect, or demyelinating lesion.',
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'MRI LUMBOSACRAL (LS) SPINE - Disc Herniation / Sciatica',
                'title'          => 'MRI Lumbosacral (LS) Spine',
                'keywords'       => 'mri spine, ls spine, lumbar, disc bulge, herniation, sciatica, radiculopathy, stenosis',
                'Modality'       => 2,
                'service_key'    => 'MRI LUMBOSACRAL (LS) SPINE',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Multiplanar MRI of the lumbosacral spine performed in T1W, T2W, and STIR sagittal and axial sequences.</p>
<p><strong>CURVATURE & ALIGNMENT :</strong> Mild straightening of the normal lumbar lordosis noted, consistent with paravertebral muscle spasm. Vertebral body alignment is preserved without listhesis.</p>
<p><strong>VERTEBRAL BODIES :</strong> Vertebral body heights are maintained. No acute compression fracture, bone marrow edema, or focal marrow infiltration seen on STIR.</p>
<p><strong>L4-L5 LEVEL :</strong> Shows diffuse disc bulge with a central/paracentral posterior disc protrusion compromising the anterior epidural space and indenting the thecal sac. Mild narrowing of bilateral neural foramina with abutment of traversing L5 nerve roots.</p>
<p><strong>L5-S1 LEVEL :</strong> Shows mild diffuse posterior disc bulge with preserved neural exit foramina and no significant canal compromise.</p>
<p><strong>OTHER DISC LEVELS :</strong> L1-L2, L2-L3, and L3-L4 intervertebral disc spaces and disc hydration are well preserved.</p>
<p><strong>SPINAL CORD & CAUDA EQUINA :</strong> Conus medullaris ends normally at the L1-L2 level with normal signal intensity. Cauda equina nerve roots are normal in distribution.</p>
<p><strong>PARASPINAL TISSUES :</strong> Visualized facet joints, posterior elements, and paraspinal soft tissues are unremarkable.</p>
HTML
                ,
                'Impression'     => "Features suggestive of L4-L5 Central/Paracentral Disc Protrusion causing thecal sac indentation and bilateral neural foraminal narrowing with nerve root abutment. Associated Lumbar Spondylodisco-degenerative changes.\nAdvice: Clinical and neurological correlation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'MRI CERVICAL SPINE - Cervical Spondylosis',
                'title'          => 'MRI Cervical Spine',
                'keywords'       => 'mri c-spine, cervical, neck pain, disc protrusion, myelopathy, cord compression',
                'Modality'       => 2,
                'service_key'    => 'MRI CERVICAL SPINE',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Multiplanar MRI of the cervical spine acquired in sagittal T1WI, T2WI, STIR, and axial T2WI sequences.</p>
<p><strong>ALIGNMENT :</strong> Straightening of normal cervical lordosis noted, suggestive of muscle spasm. Craniocervical junction is normal.</p>
<p><strong>VERTEBRAE & MARROW :</strong> Vertebral body heights and alignment are maintained. No evidence of fracture or marrow replacing lesion.</p>
<p><strong>C5-C6 LEVEL :</strong> Shows posterior disc-osteophyte complex with left paracentral disc herniation indenting the anterior thecal sac and narrowing the left neural foramen with abutment of exiting left C6 nerve root.</p>
<p><strong>C6-C7 LEVEL :</strong> Shows mild diffuse disc bulge without significant thecal sac indentation.</p>
<p><strong>CERVICAL CORD :</strong> Cervical spinal cord is normal in caliber throughout its course with uniform signal intensity. No focal T2/STIR hyperintensity to suggest compressive myelopathy, syrinx, or demyelination.</p>
<p><strong>SOFT TISSUES :</strong> Prevertebral and posterior paraspinal soft tissues are normal.</p>
HTML
                ,
                'Impression'     => "Features suggestive of Cervical Spondylosis with C5-C6 Posterior Disc Herniation causing left neural foraminal narrowing and root impingement.\nNo compressive cervical myelopathy.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'MRI KNEE JOINT - Meniscal & Ligament Assessment',
                'title'          => 'MRI Knee Joint (Right / Left)',
                'keywords'       => 'mri knee, acl, pcl, meniscus, tear, knee pain, effusion, cartilage, ligament',
                'Modality'       => 2,
                'service_key'    => 'MRI KNEE JOINT (RIGHT / LEFT)',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Multiplanar MRI of the knee joint was performed using PD, PD-FS, T1WI, and T2-GRE sequences in coronal, sagittal, and axial planes.</p>
<p><strong>MEDIAL MENISCUS :</strong> Grade II intrameniscal linear hyperintensity seen in the posterior horn of the medial meniscus, not extending to the superior or inferior articular surfaces (suggestive of intrasubstance degeneration without tear).</p>
<p><strong>LATERAL MENISCUS :</strong> Anterior and posterior horns of the lateral meniscus show normal triangular morphology and uniform low signal intensity. No tear seen.</p>
<p><strong>CRUCIATE LIGAMENTS :</strong> Anterior Cruciate Ligament (ACL) and Posterior Cruciate Ligament (PCL) show normal course, thickness, and signal intensity. Fibers are continuous and intact.</p>
<p><strong>COLLATERAL LIGAMENTS :</strong> Medial Collateral Ligament (MCL) and Lateral Collateral Ligament complex (LCL) are intact with normal appearances.</p>
<p><strong>EXTENSOR MECHANISM :</strong> Quadriceps tendon and patellar tendon show normal signal intensity. Patellofemoral cartilage is preserved.</p>
<p><strong>JOINT EFFUSION & BONE MARROW :</strong> Mild joint effusion noted in the suprapatellar pouch. No subchondral bone marrow edema or osteochondral defect noted in femoral condyles or tibial plateau.</p>
HTML
                ,
                'Impression'     => "Grade II Degenerative Signal Change in the Posterior Horn of the Medial Meniscus.\nMild knee joint effusion. Intact ACL, PCL, and collateral ligaments.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'MRI WHOLE SPINE SCREENING (Normal)',
                'title'          => 'MRI Whole Spine Screening',
                'keywords'       => 'mri whole spine, screening, metastasis, cord, sagittal t2, stir, normal spine',
                'Modality'       => 2,
                'service_key'    => 'MRI WHOLE SPINE SCREENING',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Sagittal T2WI and STIR screening images of the entire spine from craniocervical junction to coccyx.</p>
<p><strong>CURVATURE & ALIGNMENT :</strong> Normal cervical, thoracic, and lumbar spinal curvatures and vertebral alignment are preserved.</p>
<p><strong>VERTEBRAL BODIES :</strong> Vertebral body heights and marrow signal intensities are normal across all levels. No focal bone destruction, collapse, or STIR marrow hyperintensity to suggest metastasis or trauma.</p>
<p><strong>DISC SPACES :</strong> Intervertebral disc heights and signals are generally preserved with early mild degenerative disc dehydration at L4-L5 and L5-S1 levels. No gross disc herniation or spinal canal stenosis.</p>
<p><strong>SPINAL CORD :</strong> Spinal cord shows normal caliber and signal intensity throughout cervical and thoracic regions. Conus medullaris terminates normally at L1 level.</p>
<p><strong>PARASPINAL TISSUES :</strong> Pre- and paravertebral soft tissues are unremarkable.</p>
HTML
                ,
                'Impression'     => 'Normal MRI Whole Spine Screening study. No focal marrow infiltrative lesion, vertebral collapse, or spinal cord compression.',
                'impression_cat' => 0,
            ],
        ];

        // ---------------------------------------------------------------------
        // 3. Upsert Templates into `radiology_ultrasound_template`
        // ---------------------------------------------------------------------
        $insertedCount = 0;
        $updatedCount  = 0;

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
                $db->table('radiology_ultrasound_template')
                    ->where('id', $existing['id'])
                    ->update($data);
                $updatedCount++;
            } else {
                $db->table('radiology_ultrasound_template')->insert($data);
                $insertedCount++;
            }
        }

        CLI::write("Radiology Templates Seeding Complete!", 'green');
        CLI::write("  - Charge Services Verified/Added: " . count($chargeIdMap));
        CLI::write("  - Templates Inserted:             {$insertedCount}");
        CLI::write("  - Templates Updated:              {$updatedCount}");
        CLI::write("  - Total Templates:                " . (count($templates)));
    }
}
