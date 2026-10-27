<?php

namespace App\Database\Seeds\Data;

/**
 * Standard Radiology Templates & Services for USG, ECHO, X-Ray, CT-Scan, and MRI.
 */
class RadiologySeedData
{
    /**
     * Billing charge items to ensure exist in `hc_items`
     */
    public static function getServices(): array
    {
        return [
            // USG (itype = 1)
            ['itype' => 1, 'desc' => 'USG WHOLE ABDOMEN & PELVIS',               'amount' => 900.00],
            ['itype' => 1, 'desc' => 'USG KUB & PROSTATE',                       'amount' => 800.00],
            ['itype' => 1, 'desc' => 'USG PELVIS (TRANSVAGINAL / TVS)',          'amount' => 1000.00],
            ['itype' => 1, 'desc' => 'USG OBSTETRIC (EARLY DATING / 1ST TRIM)',  'amount' => 800.00],
            ['itype' => 1, 'desc' => 'USG OBSTETRIC (GROWTH & BIOMETRY)',        'amount' => 900.00],
            ['itype' => 1, 'desc' => 'USG OBSTETRIC (ANOMALY LEVEL-II / TIFFA)', 'amount' => 1500.00],
            ['itype' => 1, 'desc' => 'USG OBSTETRIC COLOR DOPPLER',              'amount' => 1400.00],
            ['itype' => 1, 'desc' => 'USG THYROID / NECK',                       'amount' => 850.00],
            ['itype' => 1, 'desc' => 'USG SCROTUM & TESTICULAR DOPPLER',         'amount' => 1100.00],
            ['itype' => 1, 'desc' => 'USG BREAST (SONOMAMMOGRAPHY)',             'amount' => 1200.00],
            ['itype' => 1, 'desc' => 'COLOR DOPPLER LOWER LIMB VENOUS (DVT)',     'amount' => 1800.00],
            ['itype' => 1, 'desc' => 'COLOR DOPPLER CAROTID ARTERIES',           'amount' => 1800.00],
            ['itype' => 1, 'desc' => 'USG THORAX & PLEURAL CAVITY',              'amount' => 900.00],
            ['itype' => 1, 'desc' => 'USG SOFT TISSUE / SWELLING',               'amount' => 750.00],
            ['itype' => 1, 'desc' => 'USG FOLLICULAR STUDY (SERIAL)',             'amount' => 1200.00],
            ['itype' => 1, 'desc' => 'USG INFANT HIP (GRAF DDH)',                'amount' => 1200.00],
            ['itype' => 1, 'desc' => 'USG PEDIATRIC PYLORUS (IHPS)',             'amount' => 1000.00],
            ['itype' => 1, 'desc' => 'USG PENILE DOPPLER',                       'amount' => 2500.00],
            ['itype' => 1, 'desc' => 'COLOR DOPPLER RENAL ARTERIES',             'amount' => 2000.00],
            ['itype' => 1, 'desc' => 'USG PERIANAL & FISTULOGRAM',               'amount' => 1400.00],

            // ECHO (itype = 6)
            ['itype' => 6, 'desc' => '2D ECHOCARDIOGRAPHY WITH COLOR DOPPLER',    'amount' => 1800.00],
            ['itype' => 6, 'desc' => 'PEDIATRIC 2D ECHOCARDIOGRAPHY (CHD SCREEN)','amount' => 2200.00],
            ['itype' => 6, 'desc' => 'ECHOCARDIOGRAPHY FOR CARDIAC TAMPONADE / EFFUSION', 'amount' => 1800.00],
            ['itype' => 6, 'desc' => 'ECHOCARDIOGRAPHY CARDIOTOXICITY PROTOCOL (GLS)', 'amount' => 2400.00],

            // X-Ray (itype = 3)
            ['itype' => 3, 'desc' => 'X-RAY CHEST PA VIEW',                      'amount' => 350.00],
            ['itype' => 3, 'desc' => 'X-RAY CHEST AP VIEW',                      'amount' => 350.00],
            ['itype' => 3, 'desc' => 'X-RAY BOTH KNEE JOINTS AP & LATERAL',       'amount' => 500.00],
            ['itype' => 3, 'desc' => 'X-RAY LS SPINE AP & LATERAL',               'amount' => 600.00],
            ['itype' => 3, 'desc' => 'X-RAY CERVICAL SPINE AP & LATERAL',         'amount' => 550.00],
            ['itype' => 3, 'desc' => 'X-RAY ABDOMEN ERECT (KUB)',                 'amount' => 450.00],
            ['itype' => 3, 'desc' => 'X-RAY PNS (WATER\'S VIEW)',                 'amount' => 400.00],
            ['itype' => 3, 'desc' => 'X-RAY PELVIS WITH BOTH HIPS AP',            'amount' => 500.00],
            ['itype' => 3, 'desc' => 'X-RAY SHOULDER JOINT AP VIEW',              'amount' => 400.00],
            ['itype' => 3, 'desc' => 'X-RAY SKULL AP & LATERAL',                  'amount' => 500.00],
            ['itype' => 3, 'desc' => 'X-RAY DORSAL (THORACIC) SPINE AP & LATERAL','amount' => 600.00],
            ['itype' => 3, 'desc' => 'X-RAY ELBOW JOINT AP & LATERAL',            'amount' => 400.00],
            ['itype' => 3, 'desc' => 'X-RAY WRIST JOINT AP & LATERAL',            'amount' => 400.00],
            ['itype' => 3, 'desc' => 'X-RAY HAND AP & OBLIQUE',                   'amount' => 400.00],
            ['itype' => 3, 'desc' => 'X-RAY HIP JOINT WITH FROG-LEG LATERAL',     'amount' => 500.00],
            ['itype' => 3, 'desc' => 'X-RAY ANKLE JOINT AP & LATERAL',            'amount' => 400.00],
            ['itype' => 3, 'desc' => 'X-RAY FOOT AP & OBLIQUE',                   'amount' => 400.00],
            ['itype' => 3, 'desc' => 'BARIUM SWALLOW / UPPER GI CONTRAST STUDY',  'amount' => 1500.00],
            ['itype' => 3, 'desc' => 'HYSTEROSALPINGOGRAPHY (HSG)',               'amount' => 2000.00],

            // CT-Scan (itype = 4)
            ['itype' => 4, 'desc' => 'NCCT HEAD / BRAIN',                         'amount' => 1800.00],
            ['itype' => 4, 'desc' => 'NCCT HEAD (STROKE PROTOCOL)',               'amount' => 2000.00],
            ['itype' => 4, 'desc' => 'HRCT CHEST / THORAX',                       'amount' => 2500.00],
            ['itype' => 4, 'desc' => 'CECT WHOLE ABDOMEN & PELVIS',               'amount' => 4500.00],
            ['itype' => 4, 'desc' => 'NCCT KUB (KIDNEYS, URETERS, BLADDER)',      'amount' => 2200.00],
            ['itype' => 4, 'desc' => 'CECT CHEST',                                'amount' => 3500.00],
            ['itype' => 4, 'desc' => 'CT PULMONARY ANGIOGRAPHY (CTPA)',           'amount' => 5500.00],
            ['itype' => 4, 'desc' => 'CT ANGIOGRAPHY BRAIN & NECK (CTA)',         'amount' => 6000.00],
            ['itype' => 4, 'desc' => 'CT UROGRAPHY (TRIPHASIC CTU)',              'amount' => 4800.00],
            ['itype' => 4, 'desc' => 'CT THORAX PLAIN & CONTRAST',                'amount' => 3800.00],
            ['itype' => 4, 'desc' => 'CT FACIAL BONES 3D RECONSTRUCTION',         'amount' => 2800.00],
            ['itype' => 4, 'desc' => 'CT ENTEROGRAPHY',                           'amount' => 5000.00],

            // MRI (itype = 2)
            ['itype' => 2, 'desc' => 'MRI BRAIN (PLAIN STUDY)',                   'amount' => 4000.00],
            ['itype' => 2, 'desc' => 'MRI BRAIN (STROKE / DWI PROTOCOL)',         'amount' => 4500.00],
            ['itype' => 2, 'desc' => 'MRI LUMBOSACRAL (LS) SPINE',                'amount' => 4200.00],
            ['itype' => 2, 'desc' => 'MRI CERVICAL SPINE',                        'amount' => 4200.00],
            ['itype' => 2, 'desc' => 'MRI KNEE JOINT (RIGHT / LEFT)',             'amount' => 4500.00],
            ['itype' => 2, 'desc' => 'MRI WHOLE SPINE SCREENING',                 'amount' => 5500.00],
            ['itype' => 2, 'desc' => 'MRI SHOULDER JOINT',                        'amount' => 4500.00],
            ['itype' => 2, 'desc' => 'MRI DORSAL (THORACIC) SPINE',               'amount' => 4200.00],
            ['itype' => 2, 'desc' => 'MRI PELVIS FEMALE (ENDOMETRIOSIS)',         'amount' => 5000.00],
            ['itype' => 2, 'desc' => 'MRI PROSTATE (mpMRI PI-RADS v2.1)',         'amount' => 5500.00],
            ['itype' => 2, 'desc' => 'MRCP (MAGNETIC RESONANCE CHOLANGIOPANCREATOGRAPHY)', 'amount' => 5000.00],
            ['itype' => 2, 'desc' => 'MRI BRAIN EPILEPSY PROTOCOL',               'amount' => 5200.00],
            ['itype' => 2, 'desc' => 'MRI SACROILIAC (SI) JOINTS',                'amount' => 4500.00],
            ['itype' => 2, 'desc' => 'MRI ANKLE & FOOT',                          'amount' => 4500.00],
            ['itype' => 2, 'desc' => 'MRI WRIST JOINT',                           'amount' => 4500.00],
        ];
    }

    /**
     * Master radiology templates list across all modalities
     */
    public static function getTemplates(): array
    {
        return array_merge(
            self::getUsgTemplates(),
            self::getEchoTemplates(),
            self::getXrayTemplates(),
            self::getCtTemplates(),
            self::getMriTemplates()
        );
    }

    // =========================================================================
    // 1. ULTRASOUND TEMPLATES (Modality = 1)
    // =========================================================================
    private static function getUsgTemplates(): array
    {
        return [
            [
                'template_name'  => 'USG WHOLE ABDOMEN & PELVIS (Normal Study)',
                'title'          => 'USG Whole Abdomen & Pelvis (Normal)',
                'keywords'       => 'usg abdomen, normal ultrasound, liver, gb, spleen, kidneys, pancreas, bladder',
                'Modality'       => 1,
                'service_key'    => 'USG WHOLE ABDOMEN & PELVIS',
                'Findings'       => <<<HTML
<p><strong>LIVER :</strong> Normal in size (13.8 cm), shape, and contour. Normal homogeneous parenchymal echotexture. No focal solid or cystic space occupying lesion (SOL). Intrahepatic biliary radicles (IHBR) and portal vein are normal in caliber. Hepatic veins show normal triphasic flow.</p>
<p><strong>GALL BLADDER :</strong> Well distended with smooth, thin walls (2.1 mm). Luminal contents are anechoic. No evidence of calculus, sludge, or mass lesion. Common Bile Duct (CBD) is normal in caliber (4.0 mm).</p>
<p><strong>PANCREAS :</strong> Head, body, and tail are normal in size and homogeneous in echotexture. Main pancreatic duct (MPD) is not dilated. No peripancreatic fluid collection.</p>
<p><strong>SPLEEN :</strong> Normal in size (9.8 cm) with homogeneous echotexture. Splenic vein at hilum is normal. No splenomegaly.</p>
<p><strong>KIDNEYS :</strong> Both kidneys are normal in size, shape, and anatomical position. Right kidney measures 10.2 x 4.5 cm and Left kidney measures 10.6 x 4.8 cm. Corticomedullary differentiation is well preserved. Renal parenchymal thickness is adequate. No calculus, hydronephrosis, or mass lesion seen bilaterally.</p>
<p><strong>URINARY BLADDER :</strong> Adequately distended with uniform, thin mucosal wall. Luminal contents are clear. No calculus, diverticulum, or intraluminal mass.</p>
<p><strong>PELVIC ORGANS :</strong> Uterus and bilateral ovaries (in females) / Prostate (in males) are normal in size and morphology. No adnexal mass or free pelvic fluid.</p>
<p><strong>RETROPERITONEUM & PERITONEAL CAVITY :</strong> Abdominal aorta and IVC are normal in caliber. No para-aortic lymphadenopathy. No free fluid (ascites) noted in Morrison's pouch, pelvis, or paracolic gutters.</p>
HTML
                ,
                'Impression'     => "Normal ultrasound study of whole abdomen and pelvis.\nNo evidence of hepatosplenomegaly, cholelithiasis, nephrolithiasis, hydronephrosis, or ascites.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'USG WHOLE ABDOMEN - Fatty Liver (Hepatosteatosis Grade I-II)',
                'title'          => 'USG Whole Abdomen - Fatty Liver',
                'keywords'       => 'fatty liver, hepatomegaly, hepatosteatosis, bright liver, usg abdomen',
                'Modality'       => 1,
                'service_key'    => 'USG WHOLE ABDOMEN & PELVIS',
                'Findings'       => <<<HTML
<p><strong>LIVER :</strong> Mildly enlarged in size measuring 15.6 cm in mid-clavicular line. Demonstrates diffusely increased parenchymal echogenicity with fine echoes ('bright liver') and slight acoustic attenuation of deep echoes. Periportal vessel wall definition is slightly obscured, consistent with Grade II Fatty Infiltration (Hepatosteatosis). No focal space occupying lesion.</p>
<p><strong>GALL BLADDER :</strong> Well distended with normal wall thickness. No intraluminal calculus or mass. CBD is normal in caliber (4.2 mm).</p>
<p><strong>PANCREAS :</strong> Visualized portions show normal size and echotexture. MPD is not dilated.</p>
<p><strong>SPLEEN :</strong> Normal in size (10.0 cm) and echotexture. No splenomegaly.</p>
<p><strong>KIDNEYS :</strong> Both kidneys are normal in size, location, and echotexture. CMD is maintained. No calculus or hydronephrosis bilaterally.</p>
<p><strong>URINARY BLADDER :</strong> Well distended with smooth margins. No calculus.</p>
<p><strong>RETROPERITONEUM :</strong> No ascites or retroperitoneal lymphadenopathy seen.</p>
HTML
                ,
                'Impression'     => "Hepatomegaly with Grade-II Fatty Liver (Diffuse Hepatosteatosis).\nRest of the abdominal and pelvic organs appear sonographically unremarkable.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'USG WHOLE ABDOMEN - Cholelithiasis (Gallstones)',
                'title'          => 'USG Whole Abdomen - Cholelithiasis',
                'keywords'       => 'cholelithiasis, gall stones, gb calculus, acoustic shadow, usg abdomen',
                'Modality'       => 1,
                'service_key'    => 'USG WHOLE ABDOMEN & PELVIS',
                'Findings'       => <<<HTML
<p><strong>LIVER :</strong> Normal in size (13.5 cm) and echotexture. No focal lesion. IHBR and portal vein are normal.</p>
<p><strong>GALL BLADDER :</strong> Distended. Lumen demonstrates multiple, mobile, echogenic foci measuring up to 12 mm in diameter in the body and neck regions, casting distinct posterior acoustic shadows. Gall bladder wall thickness is normal (2.3 mm) without edema, striation, or pericholecystic fluid collection. Sonographic Murphy's sign is negative.</p>
<p><strong>BILE DUCTS :</strong> Common Bile Duct (CBD) is normal in caliber measuring 4.5 mm. No intraductal calculus or dilatation.</p>
<p><strong>PANCREAS & SPLEEN :</strong> Unremarkable in size and echotexture.</p>
<p><strong>KIDNEYS & BLADDER :</strong> Both kidneys and urinary bladder appear normal. No calculus or hydronephrosis.</p>
<p><strong>PERITONEAL CAVITY :</strong> No free fluid in abdomen or pelvis.</p>
HTML
                ,
                'Impression'     => "Cholelithiasis (multiple mobile gallstones).\nNo evidence of acute cholecystitis (normal GB wall thickness, negative sonographic Murphy's sign, normal CBD caliber).",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'USG WHOLE ABDOMEN - Acute Calculus Cholecystitis',
                'title'          => 'USG Whole Abdomen - Acute Cholecystitis',
                'keywords'       => 'acute cholecystitis, gb stone, wall thickening, murphys sign, pericholecystic fluid',
                'Modality'       => 1,
                'service_key'    => 'USG WHOLE ABDOMEN & PELVIS',
                'Findings'       => <<<HTML
<p><strong>GALL BLADDER :</strong> Overdistended and tense. Diffuse, irregular wall thickening noted measuring 5.4 mm with submucosal edema and double-rim layered appearance. Single large impacted echogenic calculus measuring 16 mm seen at the neck / cystic duct junction casting sharp posterior acoustic shadowing. Minimal pericholecystic fluid rim noted. Probe tenderness (Sonographic Murphy's sign) is strongly positive.</p>
<p><strong>BILE DUCTS :</strong> CBD is mildly dilated measuring 6.2 mm. No obvious choledocholithiasis visualized.</p>
<p><strong>LIVER :</strong> Normal in size with mildly increased peri-gallbladder parenchymal perfusion/echogenicity. No focal abscess.</p>
<p><strong>PANCREAS, SPLEEN, KIDNEYS :</strong> Unremarkable. No significant abnormality.</p>
<p><strong>PERITONEUM :</strong> No generalized ascites.</p>
HTML
                ,
                'Impression'     => "Sonographic features of Acute Calculus Cholecystitis with impacted calculus at GB neck, gallbladder wall thickening, pericholecystic fluid, and positive Murphy's sign.\nAdvised: Clinical correlation and surgical evaluation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'USG WHOLE ABDOMEN - Acute Appendicitis / Appendicular Mass',
                'title'          => 'USG Whole Abdomen - Acute Appendicitis',
                'keywords'       => 'appendicitis, appendix, rif pain, appendicolith, appendicular lump, target sign',
                'Modality'       => 1,
                'service_key'    => 'USG WHOLE ABDOMEN & PELVIS',
                'Findings'       => <<<HTML
<p><strong>RIGHT ILIAC FOSSA (RIF) :</strong> Graded compression sonography reveals a blind-ended, non-compressible, tubular aperistaltic structure arising from the base of the cecum in the right iliac fossa. Outer-to-outer diameter of the appendix is enlarged measuring 8.5 mm (normal < 6 mm). Appendiceal wall demonstrates concentric thickening with hypervascularity on color Doppler interrogation ('ring of fire'). An echogenic shadowing appendicolith measuring 6 mm is identified at the proximal lumen. Significant hyperechoic peri-appendiceal inflammatory mesenteric fat stranding and localized turbid fluid noted.</p>
<p><strong>LIVER, GB, SPLEEN, PANCREAS :</strong> Normal in appearance. No abnormality.</p>
<p><strong>KIDNEYS & BLADDER :</strong> Normal bilaterally. No calculus or hydronephrosis.</p>
<p><strong>PELVIS :</strong> Minimal free fluid noted in the pouch of Douglas / pelvis.</p>
HTML
                ,
                'Impression'     => "Sonographic features diagnostic of Acute Appendicitis with appendicolith and localized peri-appendiceal inflammatory reaction.\nAdvised: Urgent surgical consultation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'USG KUB & PROSTATE - BPH & Nephrolithiasis',
                'title'          => 'USG KUB & Prostate - BPH / Stone',
                'keywords'       => 'usg kub, prostate, bph, kidney stone, hydronephrosis, bladder post void residual',
                'Modality'       => 1,
                'service_key'    => 'USG KUB & PROSTATE',
                'Findings'       => <<<HTML
<p><strong>RIGHT KIDNEY :</strong> Measures 10.4 x 4.6 cm. Corticomedullary differentiation is normal. Parenchymal thickness is 14 mm. No calculus, mass, or hydronephrosis.</p>
<p><strong>LEFT KIDNEY :</strong> Measures 10.8 x 4.8 cm. A distinct echogenic focus measuring 7.2 mm casting acoustic shadowing is seen in the mid-polar renal calyx. Mild dilatation of the pelvicalyceal system (Grade-I hydronephrosis) is noted with renal pelvis AP diameter measuring 12 mm. Left upper ureter is visualized and shows mild fullness.</p>
<p><strong>URINARY BLADDER :</strong> Well distended with pre-void volume of 380 mL. Bladder wall shows mild diffuse trabeculations (wall thickness 4.2 mm). No intravesical calculus or mass. Post-void residual (PVR) urine volume is significant measuring 85 mL.</p>
<p><strong>PROSTATE :</strong> Symmetrically enlarged in size with median lobe indentation onto the bladder base. Approximate prostate volume measures 48.0 cc (dimensions: 4.8 x 4.4 x 4.3 cm). Parenchyma shows homogeneous echotexture with a few benign corpora amylacea calcifications. No focal hypoechoic nodule in peripheral zone.</p>
HTML
                ,
                'Impression'     => "1. Benign Prostatic Hyperplasia (Grade-II BPH, prostate volume approx 48 cc) with significant post-void residue.\n2. Left Renal Calculus (7.2 mm in mid pole) with mild left hydronephrosis.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'USG PELVIS (TVS) - Polycystic Ovarian Disease (PCOD)',
                'title'          => 'USG Pelvis TVS - PCOD / PCOS',
                'keywords'       => 'tvs, pcod, pcos, polycystic ovaries, follicle count, endometrium, rotherham',
                'Modality'       => 1,
                'service_key'    => 'USG PELVIS (TRANSVAGINAL / TVS)',
                'Findings'       => <<<HTML
<p><strong>UTERUS :</strong> Anteverted, normal in size measuring 7.4 x 4.2 x 3.6 cm. Myometrium demonstrates uniform homogeneous echotexture. No focal leiomyoma or adenomyosis. Endometrial cavity is central with smooth trilaminar lining; endometrial thickness (ET) measures 7.8 mm.</p>
<p><strong>RIGHT OVARY :</strong> Enlarged in volume measuring 12.8 cc (dimensions: 3.8 x 2.8 x 2.3 cm). Demonstrates > 16 small subcentimeter peripheral follicles (2 to 8 mm) arranged around an enlarged, hyperechoic central central stroma ('string of pearls' appearance).</p>
<p><strong>LEFT OVARY :</strong> Enlarged in volume measuring 13.4 cc (dimensions: 4.0 x 2.9 x 2.2 cm). Demonstrates > 18 small subcentimeter peripheral follicles arranged peripherally with echogenic prominent stroma.</p>
<p><strong>POD & ADNEXA :</strong> No dominant follicle or corpus luteum seen. No adnexal mass. Pouch of Douglas is free of fluid.</p>
HTML
                ,
                'Impression'     => "Bilateral enlarged ovaries with typical sonographic features of Polycystic Ovarian Disease / Morphology (PCOM / PCOD, Rotterdam criteria).\nNormal uterine size and morphology.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'USG OBSTETRIC - 1st Trimester Early Pregnancy Dating',
                'title'          => 'USG Obstetric - 1st Trimester Dating',
                'keywords'       => 'early pregnancy, dating scan, crl, gestational sac, fetal pole, cardiac activity',
                'Modality'       => 1,
                'service_key'    => 'USG OBSTETRIC (EARLY DATING / 1ST TRIM)',
                'Findings'       => <<<HTML
<p><strong>SCAN TECHNIQUE :</strong> Transabdominal and transvaginal real-time high-resolution sonography.</p>
<p><strong>GESTATIONAL SAC :</strong> Single intrauterine gestational sac is visualized within the endometrial cavity with regular contour and a distinct double decidual sign. Mean Sac Diameter (MSD) measures 34 mm.</p>
<p><strong>YOLK SAC :</strong> Well visualized, rounded, thin-walled, measuring 4.2 mm.</p>
<p><strong>EMBRYO / FETAL POLE :</strong> Single live intrauterine embryo is identified. Crown-Rump Length (CRL) measures 22.0 mm, corresponding to a Gestational Age of 8 Weeks 6 Days (+/- 4 days).</p>
<p><strong>CARDIAC ACTIVITY :</strong> Regular, distinct fetal cardiac heart beats observed with M-Mode Fetal Heart Rate (FHR) measuring 162 beats/minute.</p>
<p><strong>CHORIONICITY & SUBCHORIONIC HEMORRHAGE :</strong> Monochorionic monoamniotic. No evidence of subchorionic hematoma or decidual bleeding.</p>
<p><strong>ADNEXA :</strong> Right ovary shows a normal functioning corpus luteum cyst measuring 22 mm. Left ovary is unremarkable. Cervix is closed and measures 38 mm in length.</p>
HTML
                ,
                'Impression'     => "Single Live Intrauterine Early Pregnancy of approximately 8 Weeks 6 Days with good fetal cardiac activity.\nExpected Date of Delivery (EDD) by CRL: [ENTER EDD].",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'USG OBSTETRIC - 2nd/3rd Trimester Growth & Biometry',
                'title'          => 'USG Obstetric - Growth & Biometry',
                'keywords'       => 'obstetric scan, 2nd trimester, 3rd trimester, bpd, fl, ac, afi, placenta, efw',
                'Modality'       => 1,
                'service_key'    => 'USG OBSTETRIC (GROWTH & BIOMETRY)',
                'Findings'       => <<<HTML
<p><strong>FETAL PRESENTATION & LIE :</strong> Single live intrauterine fetus in cephalic presentation, longitudinal lie, with fetal spine situated on maternal left.</p>
<p><strong>FETAL BIOMETRY :</strong></p>
<ul>
    <li>Biparietal Diameter (BPD) : [VALUE] mm (corresponds to [GA])</li>
    <li>Head Circumference (HC)   : [VALUE] mm (corresponds to [GA])</li>
    <li>Abdominal Circumf. (AC)   : [VALUE] mm (corresponds to [GA])</li>
    <li>Femur Length (FL)         : [VALUE] mm (corresponds to [GA])</li>
    <li>Estimated Fetal Wt (EFW) : [VALUE] g (+/- 10%) by Hadlock-4 formula</li>
</ul>
<p><strong>AVERAGE GESTATIONAL AGE :</strong> [VALUE] Weeks [VALUE] Days.</p>
<p><strong>FETAL CARDIAC ACTIVITY :</strong> Good regular fetal heart activity and active somatic body movements observed. Fetal Heart Rate (FHR): 144 bpm, regular rhythm.</p>
<p><strong>PLACENTA :</strong> Fundal and anterior in position, Grade-II maturity. Upper placental margin is well clear of internal cervical os (> 4.5 cm). No retroplacental clot or abruption.</p>
<p><strong>AMNIOTIC FLUID (AFI) :</strong> Adequate in volume. Four-quadrant Amniotic Fluid Index (AFI) measures 13.5 cm (Single deepest vertical pocket = 4.8 cm).</p>
<p><strong>CERVICAL LENGTH :</strong> Internal os is tightly closed. Cervical canal length measures 36 mm.</p>
HTML
                ,
                'Impression'     => "Single Live Intrauterine Fetus in Cephalic Presentation corresponding to approx [VALUE] Weeks of gestation.\nAdequate amniotic fluid volume (AFI 13.5 cm) and fundal anterior placenta clear of internal os.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'USG NECK / THYROID GLAND - Multinodular Goitre (ACR TI-RADS)',
                'title'          => 'USG Neck / Thyroid - Goitre',
                'keywords'       => 'thyroid usg, goitre, multinodular, ti-rads, thyroid nodule, isthmus, calcification',
                'Modality'       => 1,
                'service_key'    => 'USG THYROID / NECK',
                'Findings'       => <<<HTML
<p><strong>RIGHT THYROID LOBE :</strong> Enlarged measuring 5.2 x 2.4 x 2.2 cm. Echotexture is mildly heterogeneous. A well-defined mixed cystic-solid nodule measuring 18 x 14 mm is seen in the mid-to-lower pole. It demonstrates smooth margins, wider-than-tall orientation, no punctate echogenic foci, and peripheral halo with comet-tail artifacts in cystic areas (ACR TI-RADS 2 - Benign colloid nodule).</p>
<p><strong>LEFT THYROID LOBE :</strong> Measures 4.6 x 1.9 x 1.7 cm. Demonstrates a predominantly isoechoic solid nodule measuring 10 x 8 mm in the upper pole with smooth margins (ACR TI-RADS 3 - Mildly suspicious).</p>
<p><strong>ISTHMUS :</strong> Normal in thickness measuring 3.2 mm without nodule.</p>
<p><strong>VASCULARITY :</strong> Normal parenchymal vascularity on color Doppler. No thyroid inferno pattern.</p>
<p><strong>CERVICAL LYMPH NODES :</strong> No pathologically enlarged or suspicious cervical lymphadenopathy (Levels I to VI).</p>
HTML
                ,
                'Impression'     => "Multinodular Goitre with bilateral benign/low-suspicion colloid nodules (Right lobe ACR TI-RADS 2, Left lobe ACR TI-RADS 3).\nNo sonographic evidence of suspicious cervical lymphadenopathy.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'COLOR DOPPLER - Lower Limb Venous System (DVT Evaluation)',
                'title'          => 'Color Doppler Lower Limb - DVT',
                'keywords'       => 'doppler, dvt, deep vein thrombosis, compressibility, femoral vein, popliteal vein',
                'Modality'       => 1,
                'service_key'    => 'COLOR DOPPLER LOWER LIMB VENOUS (DVT)',
                'Findings'       => <<<HTML
<p><strong>COMMON FEMORAL VEIN (CFV) :</strong> Normal luminal caliber. Shows full wall-to-wall luminal compressibility under gentle probe pressure. Anechoic lumen with brisk spontaneous color flow and normal respiratory phasicity on spectral Doppler.</p>
<p><strong>FEMORAL VEIN (FV) & DEEP FEMORAL VEIN (DFV) :</strong> Completely compressible throughout proximal, mid, and distal thigh. Normal color filling and spontaneous flow.</p>
<p><strong>POPLITEAL VEIN :</strong> Shows completely compressible thin walls. Brisk flow augmentation on calf compression.</p>
<p><strong>CALF VEINS (POSTERIOR TIBIAL & PERONEAL VEINS) :</strong> Paired deep calf veins are patent, compressible, and show intact color Doppler signals without thrombus.</p>
<p><strong>SUPERFICIAL VENOUS SYSTEM :</strong> Great Saphenous Vein (GSV) and Small Saphenous Vein (SSV) are patent throughout their course. Saphenofemoral junction (SFJ) is competent with no reflux on Valsalva maneuver.</p>
HTML
                ,
                'Impression'     => "Normal Color Doppler study of the lower limb venous system.\nNo evidence of Acute or Chronic Deep Vein Thrombosis (DVT) in the common femoral, femoral, popliteal, or calf veins. Competent saphenofemoral junction.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'USG SCROTUM & TESTICULAR DOPPLER - Hydrocele / Orchitis',
                'title'          => 'USG Scrotum & Doppler - Hydrocele / Orchitis',
                'keywords'       => 'scrotum usg, hydrocele, varicocele, epididymitis, testis, testicular torsion',
                'Modality'       => 1,
                'service_key'    => 'USG SCROTUM & TESTICULAR DOPPLER',
                'Findings'       => <<<HTML
<p><strong>RIGHT TESTIS :</strong> Normal in size (4.2 x 2.6 x 2.2 cm), shape, and position within the scrotal sac. Shows homogeneous mid-gray parenchymal echotexture without focal mass, microcalcifications, or hematoma. Normal parenchymal vascularity on color Doppler.</p>
<p><strong>RIGHT EPIDIDYMIS :</strong> Normal in size and echotexture throughout head, body, and tail.</p>
<p><strong>LEFT TESTIS :</strong> Normal in size (4.1 x 2.5 x 2.2 cm) with homogeneous echotexture. Color and spectral Doppler show symmetrical intra-testicular arterial flow with low resistance waveforms.</p>
<p><strong>LEFT EPIDIDYMIS :</strong> Mildly enlarged epididymal head (12 mm) with hypervascularity on power Doppler, suggestive of mild epididymitis.</p>
<p><strong>SCROTAL SAC & FLUID :</strong> Mild clear fluid collection (hydrocele) noted between visceral and parietal layers of tunica vaginalis on the left side (approx 25 mL). No septations or internal debris.</p>
<p><strong>PAMPINIFORM PLEXUS :</strong> Bilateral spermatic cord veins show normal caliber (< 2.0 mm at rest). No significant venous dilatation or retrograde reflux on Valsalva maneuver to suggest varicocele.</p>
HTML
                ,
                'Impression'     => "Mild Left Epididymo-orchitis with associated mild reactive left Hydrocele.\nNormal testicular vascularity bilaterally. No evidence of testicular torsion or varicocele.",
                'impression_cat' => 1,
            ],
        ];
    }

    // =========================================================================
    // 2. ECHO TEMPLATES (Modality = 6)
    // =========================================================================
    private static function getEchoTemplates(): array
    {
        return [
            [
                'template_name'  => '2D ECHOCARDIOGRAPHY - Normal Adult Study (EF 60-65%)',
                'title'          => '2D ECHO - Normal Study (EF 60-65%)',
                'keywords'       => 'echo, normal 2d echo, ejection fraction, lv function, valves, doppler',
                'Modality'       => 6,
                'service_key'    => '2D ECHOCARDIOGRAPHY WITH COLOR DOPPLER',
                'Findings'       => <<<HTML
<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:12px;">
    <tr style="background:#f1f5f9;">
        <th>Chamber / Parameter</th><th>Measured Value</th><th>Normal Range</th>
    </tr>
    <tr><td>Left Ventricle Internal Diastole (LVIDd)</td><td>46 mm</td><td>35 - 53 mm</td></tr>
    <tr><td>Left Ventricle Internal Systole (LVIDs)</td><td>29 mm</td><td>22 - 35 mm</td></tr>
    <tr><td>Interventricular Septum Diastole (IVSd)</td><td>9.5 mm</td><td>6.0 - 11.0 mm</td></tr>
    <tr><td>Posterior Wall Diastole (LVPWd)</td><td>9.0 mm</td><td>6.0 - 11.0 mm</td></tr>
    <tr><td>Left Atrium (LA Diameter)</td><td>34 mm</td><td>28 - 40 mm</td></tr>
    <tr><td>Aortic Root Diameter (Ao)</td><td>30 mm</td><td>20 - 37 mm</td></tr>
    <tr><td>Ejection Fraction (LVEF - Teichholz / Simpson)</td><td><strong>63 %</strong></td><td>55 - 70 %</td></tr>
</table>
<p><strong>LEFT VENTRICLE :</strong> Normal internal dimensions with preserved wall thickness. No regional wall motion abnormality (RWMA) at rest. Global LV systolic function is preserved (LVEF: 63%).</p>
<p><strong>RIGHT VENTRICLE & ATRIUM :</strong> RV size and systolic function are normal (TAPSE: 22 mm). Right atrium is normal in size.</p>
<p><strong>VALVULAR EVALUATION :</strong></p>
<ul>
    <li>Mitral Valve: Normal leaflet thickness and mobility. Normal E/A ratio (1.2). No mitral stenosis or regurgitation.</li>
    <li>Aortic Valve: Trileaflet aortic valve with good systolic opening. Peak velocity: 1.2 m/s. No aortic stenosis or regurgitation.</li>
    <li>Tricuspid Valve: Normal structure. Trivial physiological TR. Estimated PASP: 22 mmHg (normal).</li>
    <li>Pulmonary Valve: Normal. No pulmonary hypertension.</li>
</ul>
<p><strong>PERICARDIUM & AORTA :</strong> No pericardial effusion. Normal aortic root and arch caliber. IVC is normal in caliber with > 50% inspiratory collapse.</p>
HTML
                ,
                'Impression'     => "Normal Transthoracic 2D Echocardiography study with Color Doppler.\nPreserved LV systolic function (LVEF: 63%) with no RWMA at rest. Normal cardiac chambers and valvular function.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => '2D ECHOCARDIOGRAPHY - Dilated Cardiomyopathy (Severe LV Dysfunction)',
                'title'          => '2D ECHO - Dilated Cardiomyopathy (DCM)',
                'keywords'       => 'echo, dcm, dilated cardiomyopathy, severe lv dysfunction, low ef, mr, chf',
                'Modality'       => 6,
                'service_key'    => '2D ECHOCARDIOGRAPHY WITH COLOR DOPPLER',
                'Findings'       => <<<HTML
<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:12px;">
    <tr style="background:#f1f5f9;">
        <th>Chamber / Parameter</th><th>Measured Value</th><th>Normal Range</th>
    </tr>
    <tr><td>Left Ventricle Internal Diastole (LVIDd)</td><td><strong>64 mm</strong> (Dilated)</td><td>35 - 53 mm</td></tr>
    <tr><td>Left Ventricle Internal Systole (LVIDs)</td><td><strong>52 mm</strong></td><td>22 - 35 mm</td></tr>
    <tr><td>Interventricular Septum Diastole (IVSd)</td><td>8.5 mm</td><td>6.0 - 11.0 mm</td></tr>
    <tr><td>Posterior Wall Diastole (LVPWd)</td><td>8.0 mm</td><td>6.0 - 11.0 mm</td></tr>
    <tr><td>Left Atrium (LA Diameter)</td><td><strong>46 mm</strong> (Dilated)</td><td>28 - 40 mm</td></tr>
    <tr><td>Ejection Fraction (LVEF)</td><td><strong>28 %</strong></td><td>55 - 70 %</td></tr>
</table>
<p><strong>LEFT VENTRICLE :</strong> Markedly dilated LV cavity with generalized diffuse global hypokinesia. Severe LV systolic dysfunction (LVEF: 28%). Interventricular septum is thin and hypokinetic.</p>
<p><strong>VALVES & DOPPLER :</strong> Functional Mitral Regurgitation (Grade II-III) secondary to mitral annular dilatation and papillary muscle displacement. Moderate Tricuspid Regurgitation (Grade II). Estimated PASP: 45 mmHg (moderate pulmonary arterial hypertension).</p>
<p><strong>DIASTOLIC FUNCTION :</strong> Restrictive LV filling pattern (Grade III Diastolic Dysfunction) with high E/A ratio and shortened deceleration time.</p>
<p><strong>RIGHT HEART & IVC :</strong> Dilated RA and RV with reduced RV systolic function (TAPSE: 14 mm). Dilated IVC (24 mm) with < 50% inspiratory collapse, indicating elevated right atrial pressure.</p>
<p><strong>PERICARDIUM :</strong> Minimal trivial pericardial effusion. No intracardiac thrombus visualized.</p>
HTML
                ,
                'Impression'     => "Features consistent with Dilated Cardiomyopathy (DCM):\n1. Dilated LV and LA with severe global LV systolic hypokinesia (LVEF: 28%).\n2. Moderate functional Mitral Regurgitation and moderate secondary Pulmonary Hypertension (PASP: 45 mmHg).\n3. Elevated RV filling pressures with plethoric IVC.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => '2D ECHOCARDIOGRAPHY - Hypertensive Heart Disease (Concentric LVH)',
                'title'          => '2D ECHO - Hypertensive Heart Disease (LVH)',
                'keywords'       => 'echo, lvh, hypertension, hypertensive heart disease, diastolic dysfunction, concentric',
                'Modality'       => 6,
                'service_key'    => '2D ECHOCARDIOGRAPHY WITH COLOR DOPPLER',
                'Findings'       => <<<HTML
<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:12px;">
    <tr style="background:#f1f5f9;">
        <th>Chamber / Parameter</th><th>Measured Value</th><th>Normal Range</th>
    </tr>
    <tr><td>Left Ventricle Internal Diastole (LVIDd)</td><td>44 mm</td><td>35 - 53 mm</td></tr>
    <tr><td>Interventricular Septum Diastole (IVSd)</td><td><strong>13.5 mm</strong> (Thickened)</td><td>6.0 - 11.0 mm</td></tr>
    <tr><td>Posterior Wall Diastole (LVPWd)</td><td><strong>13.0 mm</strong> (Thickened)</td><td>6.0 - 11.0 mm</td></tr>
    <tr><td>Left Atrium (LA Diameter)</td><td>41 mm (Mildly dilated)</td><td>28 - 40 mm</td></tr>
    <tr><td>Ejection Fraction (LVEF)</td><td><strong>62 %</strong></td><td>55 - 70 %</td></tr>
</table>
<p><strong>LEFT VENTRICLE :</strong> Concentric Left Ventricular Hypertrophy (LVH) with thickened septum and posterior wall. Preserved LV internal cavity dimensions. No resting regional wall motion abnormalities. Normal LV systolic function (LVEF: 62%).</p>
<p><strong>DIASTOLIC FUNCTION :</strong> Grade-I Diastolic Dysfunction (Impaired LV Relaxation pattern with reversed E/A ratio < 0.8 and prolonged deceleration time).</p>
<p><strong>VALVULAR ASSESSMENT :</strong> Sclerotic changes noted at aortic valve cusps without hemodynamically significant stenosis. Mild degenerative Mitral Regurgitation (Grade I). Tricuspid valve shows trace TR with normal estimated PASP (26 mmHg).</p>
<p><strong>AORTA & PERICARDIUM :</strong> Ascending aorta shows mild ectasia. No pericardial effusion.</p>
HTML
                ,
                'Impression'     => "Features suggestive of Hypertensive Heart Disease:\n1. Concentric Left Ventricular Hypertrophy (LVH) with Grade-I LV Diastolic Dysfunction.\n2. Preserved LV systolic ejection fraction (LVEF: 62%). No resting RWMA.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => '2D ECHOCARDIOGRAPHY - Ischemic Heart Disease (RWMA)',
                'title'          => '2D ECHO - Ischemic Heart Disease (RWMA)',
                'keywords'       => 'echo, ihd, rwma, anterior wall hypokinesia, inferior wall, post mi, cad',
                'Modality'       => 6,
                'service_key'    => '2D ECHOCARDIOGRAPHY WITH COLOR DOPPLER',
                'Findings'       => <<<HTML
<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:12px;">
    <tr style="background:#f1f5f9;">
        <th>Chamber / Parameter</th><th>Measured Value</th><th>Normal Range</th>
    </tr>
    <tr><td>Left Ventricle Internal Diastole (LVIDd)</td><td>53 mm</td><td>35 - 53 mm</td></tr>
    <tr><td>Left Ventricle Internal Systole (LVIDs)</td><td>38 mm</td><td>22 - 35 mm</td></tr>
    <tr><td>Interventricular Septum Diastole (IVSd)</td><td>9.0 mm</td><td>6.0 - 11.0 mm</td></tr>
    <tr><td>Posterior Wall Diastole (LVPWd)</td><td>10.0 mm</td><td>6.0 - 11.0 mm</td></tr>
    <tr><td>Left Atrium (LA Diameter)</td><td>39 mm</td><td>28 - 40 mm</td></tr>
    <tr><td>Ejection Fraction (LVEF)</td><td><strong>42 %</strong></td><td>55 - 70 %</td></tr>
</table>
<p><strong>REGIONAL WALL MOTION ABNORMALITY (RWMA) :</strong> Hypokinesia to akinesia noted in the mid and apical anterior wall, apical septum, and apex (LAD territory distribution). Basal segments show compensatory preserved contraction. Mild-to-moderate global LV systolic dysfunction with LVEF approximately 42%.</p>
<p><strong>APICAL ANEURYSM / THROMBUS :</strong> LV apex is rounded and dyskinetic. No laminated intraventricular thrombus visualized on harmonic imaging.</p>
<p><strong>VALVES :</strong> Mild ischemic Mitral Regurgitation (Grade I-II). Aortic and Tricuspid valves appear structurally normal. Estimated PASP: 32 mmHg.</p>
<p><strong>PERICARDIUM :</strong> No pericardial effusion.</p>
HTML
                ,
                'Impression'     => "Features consistent with Ischemic Heart Disease (Coronary Artery Disease):\n1. Regional wall motion abnormality: Akinesia of apex, apical anterior, and apical septal segments (LAD territory).\n2. Mild-to-moderate LV systolic dysfunction (LVEF: 42%).\n3. Mild ischemic Mitral Regurgitation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => '2D ECHOCARDIOGRAPHY - Rheumatic Heart Disease (Mitral Stenosis / MR)',
                'title'          => '2D ECHO - Rheumatic Heart Disease (MS / MR)',
                'keywords'       => 'rhd, rheumatic heart disease, mitral stenosis, hockey stick, ms, mr, pah',
                'Modality'       => 6,
                'service_key'    => '2D ECHOCARDIOGRAPHY WITH COLOR DOPPLER',
                'Findings'       => <<<HTML
<p><strong>MITRAL VALVE :</strong> Thickened anterior and posterior mitral valve leaflets with commissural fusion, subvalvular thickening, and calcification of tips. Characteristic diastolic doming of the anterior mitral leaflet ('hockey-stick appearance') with restricted posterior leaflet mobility. Mitral valve orifice area by planimetry: 1.2 cm2 (Moderate Mitral Stenosis). Mean transmitral gradient: 8.5 mmHg. Moderate Mitral Regurgitation (Grade II) with eccentric jet noted.</p>
<p><strong>LEFT ATRIUM :</strong> Markedly dilated Left Atrium measuring 52 mm with spontaneous echo contrast ('smoke') observed. No definitive thrombus seen in visualized LA cavity (TEE recommended to evaluate LA appendage).</p>
<p><strong>LEFT VENTRICLE :</strong> Normal internal cavity dimensions (LVIDd: 46 mm) with preserved LV systolic function (LVEF: 60%).</p>
<p><strong>PULMONARY HYPERTENSION :</strong> Significant Tricuspid Regurgitation (Grade II-III). Estimated RVSP / PASP: 58 mmHg, consistent with Moderate-to-Severe Pulmonary Arterial Hypertension.</p>
HTML
                ,
                'Impression'     => "Rheumatic Heart Disease (RHD):\n1. Moderate Mitral Stenosis (MVA: 1.2 cm2, mean gradient 8.5 mmHg) with Moderate Mitral Regurgitation.\n2. Severe Left Atrial Dilatation (LA: 52 mm) with spontaneous echo contrast.\n3. Moderate-to-Severe Pulmonary Arterial Hypertension (PASP: 58 mmHg).",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => '2D ECHOCARDIOGRAPHY - Pericardial Effusion',
                'title'          => '2D ECHO - Pericardial Effusion',
                'keywords'       => 'pericardial effusion, fluid, cardiac tamponade, rv collapse, swinging heart',
                'Modality'       => 6,
                'service_key'    => '2D ECHOCARDIOGRAPHY WITH COLOR DOPPLER',
                'Findings'       => <<<HTML
<p><strong>PERICARDIAL SPACE :</strong> Moderate-to-large circumferential anechoic pericardial effusion noted around the cardiac chambers. Maximum echo-free separation measures 18 mm posterior to the LV, 14 mm adjacent to the RV free wall, and 12 mm anterior to the right ventricle.</p>
<p><strong>HEMODYNAMIC TAMPONADE EVALUATION :</strong></p>
<ul>
    <li>Right Ventricle: No early diastolic inward collapse of the RV free wall.</li>
    <li>Right Atrium: Brief late-diastolic RA indentation noted (< 1/3 of cardiac cycle).</li>
    <li>Mitral / Tricuspid Doppler: Respiratory variation of mitral inflow velocity is 18% (tamponade threshold > 25%).</li>
    <li>Inferior Vena Cava (IVC): Mildly dilated (21 mm) with preserved > 50% inspiratory collapse.</li>
</ul>
<p><strong>CARDIAC CHAMBERS & SYSTOLIC FUNCTION :</strong> Cardiac chamber dimensions are within normal limits. LV systolic function is preserved with LVEF approximately 60%. No intracardiac mass or thrombus.</p>
HTML
                ,
                'Impression'     => "Moderate-to-large circumferential Pericardial Effusion without frank echocardiographic signs of cardiac tamponade at the time of examination.\nAdvised: Close clinical and serial echocardiographic monitoring.",
                'impression_cat' => 1,
            ],
        ];
    }

    // =========================================================================
    // 3. X-RAY TEMPLATES (Modality = 3)
    // =========================================================================
    private static function getXrayTemplates(): array
    {
        return [
            [
                'template_name'  => 'X-RAY CHEST PA VIEW (Normal)',
                'title'          => 'X-Ray Chest PA View (Normal)',
                'keywords'       => 'chest x-ray, cxr, normal pa view, lungs, heart, costophrenic angle',
                'Modality'       => 3,
                'service_key'    => 'X-RAY CHEST PA VIEW',
                'Findings'       => <<<HTML
<p><strong>TRACHEA & MEDIASTINUM :</strong> Trachea is central in position. Mediastinal contours, aortic knuckle, and superior vena caval margins are within normal anatomical limits.</p>
<p><strong>CARDIAC SILHOUETTE :</strong> Cardiac size, configuration, and cardiothoracic ratio (CTR: 46%) are normal (CTR < 50%).</p>
<p><strong>LUNG FIELDS :</strong> Both lung fields are clear and well-expanded. Vascular markings are normal in distribution. No focal parenchymal consolidation, infiltrate, cavity, nodule, or mass lesion seen.</p>
<p><strong>HILA :</strong> Bilateral hila are normal in position, size, and density without lymphadenopathy.</p>
<p><strong>PLEURAL SPACES :</strong> Bilateral costophrenic and cardiophrenic angles are acute, clear, and sharp. No pleural effusion, thickening, or pneumothorax.</p>
<p><strong>DIAPHRAGM & SKELETAL CAGE :</strong> Both domes of the diaphragm are smooth and normal in position. Ribs, clavicles, visualized scapulae, and soft tissues of the thoracic wall are unremarkable.</p>
HTML
                ,
                'Impression'     => "Normal Chest Radiograph (PA View).\nNo active parenchymal lesion, cardiomegaly, or pleural effusion seen.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'X-RAY CHEST PA VIEW - Lobar Pneumonia / Consolidation',
                'title'          => 'X-Ray Chest PA View - Pneumonia',
                'keywords'       => 'chest x-ray, pneumonia, consolidation, air bronchogram, consolidation right middle lobe',
                'Modality'       => 3,
                'service_key'    => 'X-RAY CHEST PA VIEW',
                'Findings'       => <<<HTML
<p><strong>LUNG FIELDS :</strong> An area of inhomogeneous, dense opacity with prominent air bronchograms is noted involving the right lower / middle lung zone, consistent with acute alveolar consolidation. The right hemidiaphragm and right heart border are partially obscured (positive silhouette sign). The left lung field is clear and adequately aerated.</p>
<p><strong>CARDIAC SILHOUETTE :</strong> Heart size is within normal limits. Trachea is central.</p>
<p><strong>PLEURAL SPACES :</strong> Mild blunting of the right lateral costophrenic angle suggesting minimal parapneumonic reactive effusion. Left CP angle is clear.</p>
<p><strong>THORACIC CAGE :</strong> Visualized bony ribs and soft tissues are unremarkable.</p>
HTML
                ,
                'Impression'     => "Radiological features of Acute Lobar Pneumonia involving Right Lower / Middle Lobe with minimal reactive pleural effusion.\nAdvised: Clinical and laboratory correlation (CBC, CRP, Sputum examination).",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY CHEST PA VIEW - Pleural Effusion',
                'title'          => 'X-Ray Chest PA View - Pleural Effusion',
                'keywords'       => 'chest x-ray, pleural effusion, meniscus sign, blunting cp angle, fluid',
                'Modality'       => 3,
                'service_key'    => 'X-RAY CHEST PA VIEW',
                'Findings'       => <<<HTML
<p><strong>PLEURAL SPACES & LUNGS :</strong> Homogeneous radiodense opacity with an upper lateral concave margin ('Ellis S-shaped meniscus sign') noted in the right hemithorax, causing complete obliteration of the right costophrenic and cardiophrenic angles, consistent with moderate Right-sided Pleural Effusion. Compressive atelectasis seen in the adjacent right lower lobe parenchyma. Left lung field is clear without active infiltrate or effusion.</p>
<p><strong>MEDIASTINUM & HEART :</strong> Trachea and cardiomediastinal silhouette show mild contralateral displacement to the left due to fluid mass effect.</p>
<p><strong>BONY CAGE :</strong> Visualized ribs and clavicles are intact.</p>
HTML
                ,
                'Impression'     => "Moderate Right Pleural Effusion with compressive subsegmental atelectasis of right lower lobe.\nAdvised: Diagnostic thoracocentesis and pleural fluid analysis.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY CHEST PA VIEW - Pulmonary Tuberculosis',
                'title'          => 'X-Ray Chest PA View - Tuberculosis',
                'keywords'       => 'chest x-ray, tb, tuberculosis, cavitation, apical infiltrates, fibro-cavitary',
                'Modality'       => 3,
                'service_key'    => 'X-RAY CHEST PA VIEW',
                'Findings'       => <<<HTML
<p><strong>LUNG FIELDS :</strong> Heterogeneous fibro-nodular opacities and patchy alveolar infiltrates noted involving bilateral apical and infraclavicular segments, predominantly on the right side. A thick-walled cavity measuring approximately 22 x 18 mm is identified in the right upper zone without air-fluid level. Volume loss noted in right upper lobe with mild elevation of right hilum and trachea shifted slightly to the right.</p>
<p><strong>CARDIAC SILHOUETTE :</strong> Normal in size and configuration.</p>
<p><strong>PLEURAL SPACES :</strong> Apical pleural thickening noted bilaterally. Costophrenic angles are clear.</p>
<p><strong>BONY SKELETON :</strong> No focal rib destruction or erosion.</p>
HTML
                ,
                'Impression'     => "Radiological features strongly suggestive of Active Post-Primary Pulmonary Tuberculosis (Fibro-cavitary disease involving Right Upper Zone with bilateral apical infiltrates).\nAdvised: Sputum for AFB / CBNAAT (GeneXpert) and clinical workup.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY CHEST PA VIEW - Cardiomegaly & Congestive Heart Failure',
                'title'          => 'X-Ray Chest PA View - Cardiomegaly & CHF',
                'keywords'       => 'chest x-ray, cardiomegaly, chf, pulmonary edema, kerley b lines, cephalization',
                'Modality'       => 3,
                'service_key'    => 'X-RAY CHEST PA VIEW',
                'Findings'       => <<<HTML
<p><strong>CARDIAC SILHOUETTE :</strong> Generalized enlargement of the cardiac silhouette with Cardiothoracic Ratio (CTR) elevated at 62% (normal < 50%), suggestive of gross cardiomegaly.</p>
<p><strong>PULMONARY VASCULATURE & LUNGS :</strong> Pulmonary venous congestion noted with prominent upper lobe vascular markings ('cephalization'). Perihilar haziness with bilateral symmetrical alveolar bat-wing infiltrates. Short horizontal linear opacities ('Kerley-B lines') visualized at the lung bases, indicative of interstitial pulmonary edema.</p>
<p><strong>PLEURAL SPACES :</strong> Bilateral blunting of costophrenic angles, suggestive of mild bilateral pleural effusions (right > left).</p>
HTML
                ,
                'Impression'     => "Gross Cardiomegaly with features of Congestive Cardiac Failure (Pulmonary Venous Congestion, Interstitial/Alveolar Edema, and Bilateral Pleural Effusions).\nAdvised: Clinical correlation and 2D Echocardiography.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY ABDOMEN ERECT - Pneumoperitoneum (Perforation)',
                'title'          => 'X-Ray Abdomen Erect - Pneumoperitoneum',
                'keywords'       => 'abdomen erect, gas under diaphragm, pneumoperitoneum, perforation, acute abdomen',
                'Modality'       => 3,
                'service_key'    => 'X-RAY ABDOMEN ERECT (KUB)',
                'Findings'       => <<<HTML
<p><strong>DIAPHRAGMATIC GAS :</strong> Plain radiograph of the abdomen in erect posture demonstrates a distinct crescent-shaped radiolucency of free air under the right dome of the diaphragm ('crescent sign'), clearly outlining the superior surface of the liver. A smaller sliver of free air is also noted under the left hemidiaphragm.</p>
<p><strong>BOWEL GAS PATTERN :</strong> Normal distribution of bowel gas in the pelvis and abdomen without signs of mechanical small bowel dilatation.</p>
<p><strong>PSOAS SHADOWS :</strong> Bilateral psoas shadows and renal outlines are grossly preserved.</p>
<p><strong>BONES & CALCIFICATIONS :</strong> Visualized spine and pelvic bones are intact. No radio-opaque calculus.</p>
HTML
                ,
                'Impression'     => "Pneumoperitoneum (Free Gas Under Bilateral Domes of Diaphragm), highly suggestive of Hollow Viscus Perforation.\nAdvised: Emergency surgical evaluation and CECT Abdomen.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY BOTH KNEE JOINTS AP & LATERAL - Osteoarthritis',
                'title'          => 'X-Ray Both Knee Joints - Osteoarthritis',
                'keywords'       => 'x-ray knee, osteoarthritis, joint space narrowing, osteophytes, kellgren-lawrence',
                'Modality'       => 3,
                'service_key'    => 'X-RAY BOTH KNEE JOINTS AP & LATERAL',
                'Findings'       => <<<HTML
<p><strong>ALIGNMENT :</strong> Mild bilateral genu varum deformity noted on weight-bearing AP view.</p>
<p><strong>JOINT SPACES :</strong> Moderate asymmetrical narrowing of the medial tibiofemoral joint compartments bilaterally (Right > Left). Lateral tibiofemoral joint spaces and patellofemoral compartments are relatively preserved.</p>
<p><strong>BONE MARGINS :</strong> Prominent marginal osteophytes noted along the medial femoral condyles and medial tibial plateaus bilaterally. Spiking of bilateral tibial spines.</p>
<p><strong>SUBCHONDRAL CHANGES :</strong> Subchondral sclerosis and tiny subchondral geode cyst formation noted in the medial tibial condyles bilaterally. No joint effusion, fracture, or dislocation.</p>
HTML
                ,
                'Impression'     => "Bilateral Knee Joint Primary Osteoarthritis (Kellgren-Lawrence Grade II-III), predominant medial compartment involvement (Right > Left).",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'X-RAY LS SPINE AP & LATERAL - Lumbar Spondylosis',
                'title'          => 'X-Ray LS Spine - Spondylosis',
                'keywords'       => 'ls spine, lumbar spondylosis, disc space narrowing, osteophytes, sciatica',
                'Modality'       => 3,
                'service_key'    => 'X-RAY LS SPINE AP & LATERAL',
                'Findings'       => <<<HTML
<p><strong>ALIGNMENT & LORDOSIS :</strong> Normal lumbar lordosis is partially straightened, likely secondary to muscular spasm. No spondylolisthesis or retrolisthesis.</p>
<p><strong>VERTEBRAL BODIES :</strong> Vertebral body heights and alignments are preserved. Prominent anterior and lateral marginal syndesmophytes/osteophytes seen at L3-L4, L4-L5, and L5-S1 vertebral bodies. No osteolytic or osteoblastic lesion.</p>
<p><strong>INTERVERTEBRAL DISC SPACES :</strong> Marked narrowing of L4-L5 and L5-S1 intervertebral disc spaces with endplate sclerosis.</p>
<p><strong>POSTERIOR ELEMENTS & SACROILIAC JOINTS :</strong> Pedicles, transverse processes, and spinous processes are intact. Facet joint arthrosis noted at lower lumbar levels. Bilateral sacroiliac joints appear normal.</p>
HTML
                ,
                'Impression'     => "Lumbar Spondylosis with degenerative disc space narrowing at L4-L5 and L5-S1 levels.\nMRI LS Spine recommended if radiculopathy or neurological deficit is present.",
                'impression_cat' => 1,
            ],
        ];
    }

    // =========================================================================
    // 4. CT-SCAN TEMPLATES (Modality = 4)
    // =========================================================================
    private static function getCtTemplates(): array
    {
        return [
            [
                'template_name'  => 'NCCT HEAD / BRAIN (Normal Study)',
                'title'          => 'NCCT Head / Brain (Normal)',
                'keywords'       => 'ncct head, ct brain, normal brain, ventricles, sulci, stroke, trauma',
                'Modality'       => 4,
                'service_key'    => 'NCCT HEAD / BRAIN',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Non-contrast helical CT scan of the brain performed from skull base to vertex with 5 mm contiguous axial slices and multiplanar reformations.</p>
<p><strong>BRAIN PARENCHYMA :</strong> Cerebral hemispheres show normal gray-white matter differentiation. No focal hypo- or hyperdense parenchymal lesion to suggest acute infarction or intracranial hemorrhage. Midbrain, pons, medulla, and cerebellar hemispheres are normal without posterior fossa lesion.</p>
<p><strong>VENTRICULAR SYSTEM :</strong> Lateral, third, and fourth ventricles are normal in size, shape, and anatomical symmetry. No hydrocephalus.</p>
<p><strong>SULCI & BASAL CISTERNS :</strong> Cerebral cortical sulcal spaces, Sylvian fissures, and basal cisterns are normal in caliber and configuration for age. No subarachnoid hemorrhage.</p>
<p><strong>MIDLINE SHIFT :</strong> Midline structures are central. No herniation or mass effect.</p>
<p><strong>BONY CALVARIUM :</strong> Calvarial vault and skull base are intact. Paranasal sinuses and mastoid air cells are clear.</p>
HTML
                ,
                'Impression'     => "Normal Non-Contrast CT Scan of Head / Brain.\nNo acute intracranial hemorrhage, territorial infarction, or mass effect.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'NCCT HEAD - Acute Ischemic Infarction (MCA Stroke Protocol)',
                'title'          => 'NCCT Head - Acute Ischemic Infarct',
                'keywords'       => 'ncct head, acute stroke, infarct, mca territory, insular ribbon, sulcal effacement',
                'Modality'       => 4,
                'service_key'    => 'NCCT HEAD (STROKE PROTOCOL)',
                'Findings'       => <<<HTML
<p><strong>BRAIN PARENCHYMA :</strong> An ill-defined, wedge-shaped area of parenchymal hypodensity is noted involving the cortical gray matter and underlying white matter of the left fronto-parietal region and left insular cortex, corresponding to the Left Middle Cerebral Artery (MCA) vascular territory. Loss of gray-white matter differentiation ('insular ribbon sign') and effacement of overlying cortical sulcal spaces are noted. Hyperdense MCA sign noted in the M1 segment of left middle cerebral artery.</p>
<p><strong>HEMORRHAGIC TRANSFORMATION :</strong> No hyperdense area within the hypodense zone to suggest hemorrhagic transformation.</p>
<p><strong>VENTRICLES & MIDLINE SHIFT :</strong> Mild compression of the body of the left lateral ventricle. Midline shift of 3.2 mm to the right side noted.</p>
<p><strong>POSTERIOR FOSSA :</strong> Brainstem and cerebellum are unremarkable.</p>
HTML
                ,
                'Impression'     => "Non-contrast CT features consistent with Acute Non-Hemorrhagic Ischemic Infarction in the Left MCA territory with early cytotoxic edema and mild midline shift (3.2 mm).\nAdvised: Immediate clinical management and neurological evaluation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'NCCT HEAD - Acute Intracranial Hemorrhage (EDH / SDH / Bleed)',
                'title'          => 'NCCT Head - Intracranial Hemorrhage',
                'keywords'       => 'ncct head, intracranial hemorrhage, sdh, edh, hematoma, midline shift',
                'Modality'       => 4,
                'service_key'    => 'NCCT HEAD / BRAIN',
                'Findings'       => <<<HTML
<p><strong>EXTRA-AXIAL SPACES :</strong> A well-defined, hyperdense (approx 65-75 HU) crescentic extra-axial collection is visualized overlying the right fronto-temporo-parietal convexities, crossing suture lines, measuring maximum 14 mm in thickness, consistent with an Acute Subdural Hematoma (SDH).</p>
<p><strong>MASS EFFECT & MIDLINE SHIFT :</strong> Significant mass effect noted in the form of marked effacement of ipsilateral cortical sulci and complete compression of the right lateral ventricle. Midline shift of 7.5 mm to the left side with early subfalcine herniation.</p>
<p><strong>BRAIN PARENCHYMA :</strong> Subcortical hypodensity noted in the right cerebral hemisphere secondary to vasogenic edema. No associated intraparenchymal contusion or intraventricular hemorrhage.</p>
<p><strong>BONY STRUCTURES :</strong> Visualized skull vault bones appear intact without calvarial fracture on bone window settings.</p>
HTML
                ,
                'Impression'     => "Acute Right Fronto-Temporo-Parietal Subdural Hematoma (thickness 14 mm) causing significant mass effect, subfalcine herniation, and 7.5 mm midline shift to the left.\nAdvised: Urgent neurosurgical consultation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'HRCT THORAX / CHEST (Normal Study)',
                'title'          => 'HRCT Thorax / Chest (Normal)',
                'keywords'       => 'hrct chest, hrct thorax, normal lung, parenchyma, mediastinum, pleura',
                'Modality'       => 4,
                'service_key'    => 'HRCT CHEST / THORAX',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> High-Resolution volumetric Non-Contrast CT of thorax performed on a multi-detector CT scanner with thin 1.0 mm axial reconstructions in full inspiration using high spatial frequency algorithm.</p>
<p><strong>LUNG PARENCHYMA :</strong> Both lungs demonstrate normal, uniform attenuation and aeration throughout. No evidence of ground glass opacities (GGO), consolidation, interstitial septal thickening, crazy paving, traction bronchiectasis, honeycombing, or fibrotic bands. No pulmonary nodule or mass lesion.</p>
<p><strong>AIRWAYS :</strong> Trachea, carina, main bronchi, and segmental bronchial branches are patent with normal lumen and wall thickness without bronchiectasis.</p>
<p><strong>MEDIASTINUM & HILA :</strong> Great vessels show normal anatomy. Pretracheal, subcarinal, and hilar lymph nodes are within normal limits (short axis < 10 mm).</p>
<p><strong>PLEURAL CAVITIES :</strong> Pleural surfaces are smooth. No pleural effusion or thickening. No pneumothorax.</p>
<p><strong>CHEST WALL & BONES :</strong> Visualized thoracic ribs, vertebrae, sternum, and soft tissues are unremarkable.</p>
HTML
                ,
                'Impression'     => "Normal High-Resolution Computed Tomography (HRCT) of the Thorax.\nNo evidence of active infection, interstitial lung disease (ILD), bronchiectasis, or mass lesion.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'HRCT THORAX - Interstitial Lung Disease (ILD / Fibrosis)',
                'title'          => 'HRCT Thorax - Interstitial Lung Disease (ILD)',
                'keywords'       => 'hrct thorax, ild, pulmonary fibrosis, honeycombing, reticulation, uip, nsip',
                'Modality'       => 4,
                'service_key'    => 'HRCT CHEST / THORAX',
                'Findings'       => <<<HTML
<p><strong>LUNG PARENCHYMA :</strong> Bilateral, predominantly peripheral, subpleural, and basilar distributed inter- and intralobular septal thickening with coarse reticular opacities noted. Multilayered subpleural cystic spaces with shared walls measuring 3 to 7 mm ('honeycombing') noted involving bilateral lower lobes and subpleural upper lobes. Extensive traction bronchiectasis and bronchiolectasis observed within the areas of fibrosis. Ground-glass opacities noted in anterior segments.</p>
<p><strong>AIRWAYS & MEDIASTINUM :</strong> Central tracheobronchial tree is patent. Mild reactive mediastinal lymphadenopathy noted (subcarinal node 12 mm).</p>
<p><strong>PLEURA & BONES :</strong> No pleural effusion or pneumothorax. Thoracic cage shows degenerative spinal osteophytes.</p>
HTML
                ,
                'Impression'     => "HRCT Thorax features consistent with Chronic Interstitial Lung Disease with definite Usual Interstitial Pneumonia (UIP) / Idiopathic Pulmonary Fibrosis pattern.\nAdvised: Pulmonology evaluation, spirometry/DLCO correlation.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'CECT WHOLE ABDOMEN & PELVIS - Acute Appendicitis',
                'title'          => 'CECT Abdomen - Acute Appendicitis',
                'keywords'       => 'cect abdomen, acute appendicitis, appendicolith, fat stranding, abscess',
                'Modality'       => 4,
                'service_key'    => 'CECT WHOLE ABDOMEN & PELVIS',
                'Findings'       => <<<HTML
<p><strong>CECUM & APPENDIX :</strong> The appendix is visualized in a retrocecal position, distinctly dilated with outer diameter measuring 10.5 mm with circumferential wall thickening and mucosal hyperenhancement. A hyperdense calcified appendicolith measuring 7 mm is noted at the appendiceal base. Prominent inflammatory mesenteric fat stranding and multiple reactive ileocecal lymph nodes (up to 8 mm) noted in the right lower quadrant. No loculated abscess or extraluminal gas.</p>
<p><strong>LIVER, SPLEEN, PANCREAS, KIDNEYS :</strong> Normal in size, parenchymal attenuation, and contrast enhancement pattern. No focal lesion.</p>
<p><strong>PERITONEAL CAVITY :</strong> Minimal free fluid noted in the pelvic pouch of Douglas. No pneumoperitoneum.</p>
HTML
                ,
                'Impression'     => "Contrast-Enhanced CT findings diagnostic of Acute Appendicitis with appendicolith and localized periappendiceal inflammatory fat stranding.\nNo evidence of perforation or organized phlegmon/abscess.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'NCCT KUB (STONE PROTOCOL) - Urolithiasis & Hydronephrosis',
                'title'          => 'NCCT KUB - Urolithiasis (Kidney / Ureter Stone)',
                'keywords'       => 'ncct kub, stone protocol, renal calculus, ureteric stone, hydroureteronephrosis',
                'Modality'       => 4,
                'service_key'    => 'NCCT KUB (KIDNEYS, URETERS, BLADDER)',
                'Findings'       => <<<HTML
<p><strong>RIGHT SYSTEM :</strong> Right kidney is normal in size and position. No calculus, hydronephrosis, or perinephric fat stranding. Right ureter is normal throughout its course.</p>
<p><strong>LEFT SYSTEM :</strong> A well-defined hyperdense calculus measuring 8.2 x 5.8 mm (mean attenuation 1150 HU, consistent with calcium oxalate) is lodged in the distal left ureter at the vesicoureteric junction (VUJ). Moderate dilatation of the left ureter (internal diameter 9 mm) and moderate left pelvicalyceal dilatation (Grade-II hydronephrosis) noted. Mild perinephric and periureteric fat stranding noted on the left side.</p>
<p><strong>URINARY BLADDER :</strong> Well distended with thin, smooth wall. No intravesical calculus.</p>
<p><strong>OTHER VISUALIZED ORGANS :</strong> Visualized abdominal organs, bowel loops, and bones are unremarkable.</p>
HTML
                ,
                'Impression'     => "Left Distal Ureteric Calculus (8.2 x 5.8 mm, 1150 HU at left VUJ) causing moderate Left Hydroureteronephrosis and acute perinephric inflammatory changes.\nNormal right kidney and urinary bladder.",
                'impression_cat' => 1,
            ],
        ];
    }

    // =========================================================================
    // 5. MRI TEMPLATES (Modality = 2)
    // =========================================================================
    private static function getMriTemplates(): array
    {
        return [
            [
                'template_name'  => 'MRI BRAIN (PLAIN STUDY - Normal)',
                'title'          => 'MRI Brain (Plain Study - Normal)',
                'keywords'       => 'mri brain, normal brain, t1, t2, flair, dwi, stroke, headache, epilepsy',
                'Modality'       => 2,
                'service_key'    => 'MRI BRAIN (PLAIN STUDY)',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Multiplanar, multisequence MRI of the brain performed using T1WI, T2WI, FLAIR, GRE/SWI, and Diffusion-Weighted Imaging (DWI) with ADC mapping.</p>
<p><strong>BRAIN PARENCHYMA :</strong> Bilateral cerebral hemispheres demonstrate normal gray-white matter signal intensity. No focal T2/FLAIR hyperintensity in cortical, subcortical, or periventricular white matter. Basal ganglia, thalami, and internal capsules are normal.</p>
<p><strong>DIFFUSION RESTRICTION :</strong> No area of restricted diffusion on DWI with corresponding low ADC values to suggest acute cerebral infarction.</p>
<p><strong>BLOOD PRODUCTS :</strong> GRE/SWI sequences show no focal blooming artifact to suggest parenchymal microbleeds, acute hemorrhage, or cavernoma.</p>
<p><strong>POSTERIOR FOSSA :</strong> Cerebellar hemispheres, vermis, and brainstem (midbrain, pons, medulla) show normal morphology and uniform signals.</p>
<p><strong>VENTRICLES & CISTERNS :</strong> Ventricular system, basal cisterns, and cortical sulcal spaces are normal for age. Midline structures are central.</p>
<p><strong>CRANIOCERVICAL JUNCTION & CALVARIUM :</strong> Craniocervical junction is intact. Visualized paranasal sinuses and mastoids are clear.</p>
HTML
                ,
                'Impression'     => "Normal MRI Brain (Plain Study).\nNo evidence of acute infarction, hemorrhage, space occupying lesion (SOL), or intracranial pathology.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'MRI BRAIN - Acute Ischemic Infarction (DWI Stroke Protocol)',
                'title'          => 'MRI Brain - Acute Ischemic Infarct (Stroke)',
                'keywords'       => 'mri brain, acute stroke, dwi restriction, adc hypointensity, infarct, mca',
                'Modality'       => 2,
                'service_key'    => 'MRI BRAIN (STROKE / DWI PROTOCOL)',
                'Findings'       => <<<HTML
<p><strong>DWI & ADC SEQUENCES :</strong> A well-defined geographic area of bright hyperintensity on Diffusion-Weighted Imaging (DWI, b=1000) with corresponding signal drop on Apparent Diffusion Coefficient (ADC) map (values < 550 x 10^-6 mm2/s) is identified in the right fronto-parietal cortex and underlying corona radiata, characteristic of true cytotoxic edema / Acute Ischemic Infarction in the Right MCA vascular distribution.</p>
<p><strong>T2 / FLAIR SEQUENCES :</strong> Corresponding mild hyperintensity noted on T2WI and FLAIR sequences with effacement of overlying cortical sulci, indicating early subacute changes.</p>
<p><strong>HEMORRHAGE / BLOOMING :</strong> No blooming on T2* GRE / SWI to suggest petechial hemorrhagic transformation.</p>
<p><strong>VENTRICLES & MIDLINE :</strong> Mild compression of the frontal horn of the right lateral ventricle. No midline shift.</p>
<p><strong>VESSELS (TOF-MRA) :</strong> Attenuated flow signal noted in M2 cortical branches of the right MCA.</p>
HTML
                ,
                'Impression'     => "Acute Non-Hemorrhagic Ischemic Infarction in the Right MCA territory (Fronto-parietal region) with marked restricted diffusion.\nAdvised: Immediate clinical management and stroke protocol therapy.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'MRI LUMBOSACRAL (LS) SPINE - Disc Herniation / Sciatica',
                'title'          => 'MRI Lumbosacral (LS) Spine - Disc Herniation',
                'keywords'       => 'mri ls spine, pivd, disc herniation, extrusion, canal stenosis, sciatica, l4-l5, l5-s1',
                'Modality'       => 2,
                'service_key'    => 'MRI LUMBOSACRAL (LS) SPINE',
                'Findings'       => <<<HTML
<p><strong>ALIGNMENT :</strong> Normal lumbar lordosis is partially straightened. Normal coronal alignment. No spondylolisthesis.</p>
<p><strong>VERTEBRAL BODIES :</strong> Vertebral body heights and marrow signals are preserved. Mild marginal osteophytosis at L4-L5 and L5-S1. No fracture or infiltrative lesion.</p>
<p><strong>L4-L5 DISC LEVEL :</strong> Intervertebral disc demonstrates desiccation with T2 hypointensity. A focal posterior central and left paracentral disc extrusion is noted measuring 5.8 mm, causing significant indentation on the thecal sac and severe left lateral recess stenosis with compression of the traversing left L5 nerve root.</p>
<p><strong>L5-S1 DISC LEVEL :</strong> Diffuse disc bulge with a small right foraminal protrusion causing mild right neural foraminal narrowing without definitive root impingement.</p>
<p><strong>OTHER LEVELS :</strong> L1-L2, L2-L3, and L3-L4 disc spaces, canal caliber, and neural foramina are well preserved.</p>
<p><strong>CONUS MEDULLARIS :</strong> Terminates normally at L1 level. Cauda equina nerve roots are normal in distribution.</p>
HTML
                ,
                'Impression'     => "L4-L5 Intervertebral Disc Extrusion (central and left paracentral) causing significant thecal sac indentation and left lateral recess stenosis with impingement of the traversing left L5 nerve root (clinical correlation with left L5 sciatica).\nL5-S1 mild diffuse disc bulge.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'MRI CERVICAL SPINE - Cervical Spondylosis',
                'title'          => 'MRI Cervical Spine - Cervical Spondylosis',
                'keywords'       => 'mri cervical spine, cervical spondylosis, myelopathy, disc bulge, radiculopathy, c5-c6',
                'Modality'       => 2,
                'service_key'    => 'MRI CERVICAL SPINE',
                'Findings'       => <<<HTML
<p><strong>TECHNIQUE :</strong> Multiplanar T1WI, T2WI, and STIR MR imaging of the cervical spine from craniovertebral junction to upper thoracic levels.</p>
<p><strong>VERTEBRAL BODIES :</strong> Loss of normal cervical lordosis with straightening of the cervical spine. Anterior and posterior osteophytes noted from C4 through C7 vertebrae. Vertebral body heights and bone marrow signals are within normal limits.</p>
<p><strong>C5-C6 LEVEL :</strong> Degenerative disc desiccation with disc space narrowing. Posterior osteodisc complex / focal left paracentral disc protrusion noted, causing indentation on anterior thecal sac and narrowing of the left neural foramen with abutment of left C6 exiting nerve root.</p>
<p><strong>C6-C7 LEVEL :</strong> Mild diffuse posterior disc bulge without significant thecal compression.</p>
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
                'Impression'     => "Normal MRI Whole Spine Screening study. No focal marrow infiltrative lesion, vertebral collapse, or spinal cord compression.",
                'impression_cat' => 0,
            ],

            // -------------------------------------------------------------
            // USG EXPANDED TEMPLATES (Modality = 1)
            // -------------------------------------------------------------
            [
                'template_name'  => 'USG THORAX & PLEURAL CAVITY',
                'title'          => 'Ultrasound Thorax & Pleural Cavity',
                'keywords'       => 'usg thorax, chest, pleural effusion, fluid, consolidation, lung ultrasound',
                'Modality'       => 1,
                'service_key'    => 'USG THORAX & PLEURAL CAVITY',
                'Findings'       => <<<HTML
<p>High-resolution real-time thoracic ultrasound performed in sitting and recumbent positions.</p>
<p><strong>RIGHT HEMITHORAX :</strong> Anechoic free fluid collection noted in the right costophrenic angle and pleural space measuring approximately [MEASUREMENT] mL. Diaphragmatic mobility is preserved. No obvious internal septations, debris, or pleural thickening noted. Subadjacent lung base shows compressive atelectasis without obvious consolidation.</p>
<p><strong>LEFT HEMITHORAX :</strong> No pleural effusion or fluid collection noted in the left pleural cavity. Normal lung sliding sign present. Normal pleural interface visualized.</p>
<p><strong>PARACENTESIS LANDMARK :</strong> Safe window for diagnostic/therapeutic thoracocentesis identified on the right side at [INTERCOSTAL SPACE] intercostal space in the posterior axillary line, [DEPTH] cm from skin surface.</p>
HTML
                ,
                'Impression'     => "Right-sided mild to moderate simple pleural effusion without loculations.\nSafe puncture site marked.",
                'impression_cat' => 1,
            ],
            [
                'template_name'  => 'USG SOFT TISSUE / SWELLING',
                'title'          => 'Ultrasound Soft Tissue / Subcutaneous Swelling',
                'keywords'       => 'usg soft tissue, swelling, lipoma, sebaceous cyst, abscess, hematoma',
                'Modality'       => 1,
                'service_key'    => 'USG SOFT TISSUE / SWELLING',
                'Findings'       => <<<HTML
<p>High-frequency linear probe (7-12 MHz) examination of the palpable swelling was performed.</p>
<p><strong>LOCATION & LAYER :</strong> Well-circumscribed, oblong mass identified in the subcutaneous tissue plane overlying [ANATOMICAL SITE].</p>
<p><strong>DIMENSIONS :</strong> Measures approximately [LENGTH] x [BREADTH] x [DEPTH] cm.</p>
<p><strong>ECHOTEXTURE :</strong> Shows homogeneous echogenicity with fine internal hyperechoic striations oriented parallel to the skin surface, compressible under transducer pressure, with no posterior acoustic shadowing or enhancement.</p>
<p><strong>VASCULARITY :</strong> Color Doppler interrogation reveals no abnormal internal vascularity or pathological feeding vessels.</p>
<p><strong>DEEP PLANES :</strong> Underlying muscular fascia is intact with no deep muscular invasion or breach.</p>
HTML
                ,
                'Impression'     => "Sonographic features are consistent with a benign subcutaneous Lipoma at the described site. No deep invasion or suspicious vascularity.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'USG FOLLICULAR STUDY (Serial Monitoring)',
                'title'          => 'Ultrasound Follicular Study & Ovulation Tracking',
                'keywords'       => 'usg follicular study, ovulation, dominant follicle, endometrium, tvs, infertility',
                'Modality'       => 1,
                'service_key'    => 'USG FOLLICULAR STUDY (SERIAL)',
                'Findings'       => <<<HTML
<p>Transvaginal Sonography (TVS) follicular monitoring performed on Day [DAY OF CYCLE] of menstrual cycle.</p>
<p><strong>RIGHT OVARY :</strong> Normal in size and position. Shows multiple non-dominant antral follicles measuring between 4 to 8 mm. Dominant follicle identified measuring [SIZE_R] mm in mean diameter with clear internal fluid and smooth wall.</p>
<p><strong>LEFT OVARY :</strong> Normal in size. Shows 5-6 antral follicles measuring 5 to 7 mm. No dominant follicle seen on the left side.</p>
<p><strong>ENDOMETRIUM :</strong> Endometrial thickness measures [ET_MM] mm. Shows distinct triple-line (trilaminar) multilayered pattern (Grade A/B), favorable for implantation.</p>
<p><strong>POUCH OF DOUGLAS :</strong> No free fluid in POD / mild physiological fluid streak seen.</p>
HTML
                ,
                'Impression'     => "Dominant follicle seen in Right/Left ovary measuring [SIZE] mm with favorable trilaminar endometrium ([ET_MM] mm).\nSuggested repeat scan after 24-48 hours / trigger as advised by clinician.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'USG INFANT HIP (Graf DDH Screen)',
                'title'          => 'Ultrasound Infant Hip - Developmental Dysplasia of Hip',
                'keywords'       => 'usg infant hip, ddh, graf classification, alpha angle, beta angle, pediatric',
                'Modality'       => 1,
                'service_key'    => 'USG INFANT HIP (GRAF DDH)',
                'Findings'       => <<<HTML
<p>Real-time high-resolution sonographic evaluation of both infant hip joints performed using Graf technique.</p>
<p><strong>RIGHT HIP :</strong> Femoral head is well seated in the acetabular fossa with good bony roof coverage (>50%). Bony rim is sharp/well-formed. Cartilaginous roof covers femoral head. Alpha angle measures [ALPHA_R]° (Normal > 60°), Beta angle measures [BETA_R]° (Normal < 55°). Barlow and Ortolani dynamic stress maneuvers show no subluxation or instability.</p>
<p><strong>LEFT HIP :</strong> Femoral head is centered within the acetabulum. Bony rim is angular. Alpha angle measures [ALPHA_L]° (>60°), Beta angle measures [BETA_L]° (<55°). Dynamic stress tests negative.</p>
HTML
                ,
                'Impression'     => "Bilateral Type Ia Hips according to Graf Classification (Normal, physiologically mature infant hips with no evidence of DDH or instability).",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'USG PEDIATRIC PYLORUS (IHPS Screen)',
                'title'          => 'Ultrasound Pediatric Pylorus - Infantile Pyloric Stenosis',
                'keywords'       => 'usg pylorus, ihps, hypertrophic pyloric stenosis, pediatric, vomiting',
                'Modality'       => 1,
                'service_key'    => 'USG PEDIATRIC PYLORUS (IHPS)',
                'Findings'       => <<<HTML
<p>Real-time sonographic evaluation of the pyloric region in supine and right lateral decubitus positions after ingestion of oral glucose water.</p>
<p><strong>PYLORIC CANAL :</strong> Pyloric canal length measures [PYL_LENGTH] mm (Normal < 15 mm). Single-wall pyloric muscle thickness in transverse plane measures [PYL_THICKNESS] mm (Normal < 3.0 mm; abnormal >= 3.0 mm). Transverse pyloric diameter measures [PYL_DIAMETER] mm.</p>
<p><strong>GASTRIC MOTILITY & FLUID TRANSIT :</strong> Peristaltic waves seen traversing the antrum with intermittent opening of the pyloric channel and normal passage of fluid into the duodenal bulb.</p>
<p><strong>STOMACH :</strong> Stomach is mildly distended with liquid content, no gastric over-distension or active retrograde peristalsis.</p>
HTML
                ,
                'Impression'     => "Normal sonographic study of the pyloric canal. Pyloric muscle thickness and canal length are within normal physiological limits for age. No sonographic evidence of Infantile Hypertrophic Pyloric Stenosis (IHPS).",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'COLOR DOPPLER RENAL ARTERIES',
                'title'          => 'Color Doppler Renal Arteries - Renovascular Hypertension Screen',
                'keywords'       => 'renal doppler, renal artery stenosis, psv, resistive index, renovascular hypertension',
                'Modality'       => 1,
                'service_key'    => 'COLOR DOPPLER RENAL ARTERIES',
                'Findings'       => <<<HTML
<p>Color and spectral Doppler examination of bilateral main renal arteries and intrarenal interlobar branches performed.</p>
<p><strong>KIDNEYS :</strong> Right kidney measures 10.2 x 4.4 cm; Left kidney measures 10.6 x 4.6 cm. Bilateral renal cortices are normal with clear corticomedullary differentiation. No focal scarring.</p>
<p><strong>MAIN RENAL ARTERIES :</strong> Bilateral main renal ostia and proximal-to-distal segments visualized with low-resistance laminar flow.</p>
<ul>
    <li>Right Main Renal Artery Peak Systolic Velocity (PSV): [PSV_R] cm/s (Normal < 180 cm/s). Renal-Aortic Ratio (RAR): [RAR_R] (Normal < 3.5).</li>
    <li>Left Main Renal Artery Peak Systolic Velocity (PSV): [PSV_L] cm/s (Normal < 180 cm/s). Renal-Aortic Ratio (RAR): [RAR_L] (Normal < 3.5).</li>
</ul>
<p><strong>INTRARENAL DOPPLER :</strong> Interlobar and segmental arterial waveforms show normal rapid systolic upstroke (Acceleration Time < 0.07 sec, no tardus-parvus waveform). Resistive Index (RI): Right: 0.62; Left: 0.64 (Normal 0.55 - 0.70).</p>
HTML
                ,
                'Impression'     => "Normal Color Doppler Renal Arterial study. No evidence of hemodynamically significant main or intrarenal renal artery stenosis. Normal resistive indices.",
                'impression_cat' => 0,
            ],

            // -------------------------------------------------------------
            // ECHO EXPANDED TEMPLATES (Modality = 6)
            // -------------------------------------------------------------
            [
                'template_name'  => 'PEDIATRIC 2D ECHOCARDIOGRAPHY (Congenital Screen)',
                'title'          => 'Pediatric 2D Echocardiography - Congenital Heart Disease Screen',
                'keywords'       => 'pediatric echo, congenital heart disease, chd, asd, vsd, pda, tetralogy, child',
                'Modality'       => 6,
                'service_key'    => 'PEDIATRIC 2D ECHOCARDIOGRAPHY (CHD SCREEN)',
                'Findings'       => <<<HTML
<p>Segmental sequential approach used for complete pediatric transthoracic echocardiogram.</p>
<p><strong>VISCEROATRIAL SITUS :</strong> Situs solitus, levocardia. Concordant atrioventricular and ventriculoarterial alignments.</p>
<p><strong>SYSTEMIC & PULMONARY VENOUS RETURN :</strong> Normal systemic venous drainage into right atrium via IVC and SVC. Normal drainage of all four pulmonary veins into the left atrium.</p>
<p><strong>INTERATRIAL SEPTUM :</strong> Interatrial septum is intact; no secundum or primum atrial septal defect. Patent Foramen Ovale (PFO) shows tiny left-to-right shunt measuring [PFO_MM] mm with no chamber dilation.</p>
<p><strong>INTERVENTRICULAR SEPTUM :</strong> Ventricular septum is intact from inlet, muscular, to perimembranous regions. No VSD seen.</p>
<p><strong>VALVES :</strong> Tricuspid and mitral valves are thin, mobile, and normal. Aortic valve is trileaflet without stenosis or regurgitation. Pulmonary valve is thin with normal forward flow velocity ([PV_VEL] m/s).</p>
<p><strong>GREAT ARTERIES & AORTIC ARCH :</strong> Normal left aortic arch with normal branching pattern. No coarctation of aorta. No patent ductus arteriosus (PDA) on ductal view.</p>
<p><strong>VENTRICULAR FUNCTION :</strong> LV systolic function is normal (LVEF: 64%). RV size and systolic function are normal.</p>
HTML
                ,
                'Impression'     => "Normal structural pediatric 2D Echocardiogram with Color Doppler.\nNo congenital heart defect, intracardiac shunt, coarctation, or ventricular dysfunction.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'ECHO - PERICARDIAL EFFUSION & TAMPONADE',
                'title'          => '2D Echo - Pericardial Effusion & Tamponade Protocol',
                'keywords'       => 'echo pericardial effusion, cardiac tamponade, rv collapse, pulsus paradoxus, pericardiocentesis',
                'Modality'       => 6,
                'service_key'    => 'ECHOCARDIOGRAPHY FOR CARDIAC TAMPONADE / EFFUSION',
                'Findings'       => <<<HTML
<p>Transthoracic echocardiogram dedicated to pericardial space and hemodynamic tamponade assessment.</p>
<p><strong>PERICARDIAL EFFUSION :</strong> Circumferential echo-free pericardial fluid collection noted surrounding the heart:</p>
<ul>
    <li>Posterior to LV: [POST_MM] mm</li>
    <li>Apical: [APEX_MM] mm</li>
    <li>Anterior to RV: [ANT_MM] mm</li>
    <li>Adjacent to RA: [RA_MM] mm</li>
</ul>
<p><strong>HEMODYNAMIC TAMPONADE SIGNS :</strong></p>
<ul>
    <li>Right Atrial (RA) systolic inversion / collapse: [RA_COLLAPSE] (Sign of early elevated intrapericardial pressure).</li>
    <li>Right Ventricular (RV) early diastolic free-wall collapse / indentation: [RV_COLLAPSE] (Highly specific for cardiac tamponade).</li>
    <li>Inferior Vena Cava (IVC): Dilated ([IVC_CM] cm) with < 50% inspiratory collapse (plethoric IVC).</li>
    <li>Mitral and Tricuspid Inflow Respiratory Variation: Pulsed-wave Doppler reveals [MITRAL_VAR]% respiratory variation in mitral E velocity (>25% suggestive of tamponade physiology).</li>
</ul>
<p><strong>VENTRICULAR CONTRACTILITY :</strong> LV systolic function appears hyperdynamic / preserved.</p>
HTML
                ,
                'Impression'     => "Moderate to Large circumferential Pericardial Effusion.\n[HEMODYNAMIC_CONCLUSION: Presence/Absence of early echocardiographic signs of cardiac tamponade].\nClinical correlation and immediate review recommended.",
                'impression_cat' => 2,
            ],

            // -------------------------------------------------------------
            // X-RAY EXPANDED TEMPLATES (Modality = 3)
            // -------------------------------------------------------------
            [
                'template_name'  => 'X-RAY PARANASAL SINUSES (PNS - Water\'s View)',
                'title'          => 'X-Ray Paranasal Sinuses (Water\'s View)',
                'keywords'       => 'xray pns, waters view, paranasal sinuses, maxillary sinusitis, air fluid level, mucosal thickening',
                'Modality'       => 3,
                'service_key'    => 'X-RAY PNS (WATER\'S VIEW)',
                'Findings'       => <<<HTML
<p>Standard Water's (occipitomental) projection of the paranasal sinuses was obtained in erect position.</p>
<p><strong>MAXILLARY SINUSES :</strong> Bilateral maxillary antra show normal aeration and radiolucency. Bony walls are intact. No air-fluid levels, mucosal thickening, or opacification noted.</p>
<p><strong>FRONTAL & ETHMOID SINUSES :</strong> Frontal sinuses are normally developed and well-aerated. Ethmoidal air cells appear clear.</p>
<p><strong>NASAL CAVITY & SEPTUM :</strong> Bony nasal septum is midline / shows mild deviation to the [SIDE]. Turbinates are within normal limits. Facial bony framework is intact.</p>
HTML
                ,
                'Impression'     => "Normal X-Ray Paranasal Sinuses (Water's view). No radiologic evidence of acute or chronic sinusitis.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'X-RAY SKULL AP & LATERAL',
                'title'          => 'X-Ray Skull AP & Lateral View',
                'keywords'       => 'xray skull, ap lateral, calvarium, fracture, trauma, sella turcica, suture',
                'Modality'       => 3,
                'service_key'    => 'X-RAY SKULL AP & LATERAL',
                'Findings'       => <<<HTML
<p>Anteroposterior and lateral radiographs of the skull were obtained.</p>
<p><strong>CALVARIAL VAULT :</strong> Calvarial contour, inner and outer tables, and diploic space are normal. Cranial sutures are anatomical. No radiopaque fracture lines, depressed fragments, or focal osteolytic/osteoblastic lesions.</p>
<p><strong>SELLA TURCICA :</strong> Sella turcica is normal in size, shape, and cortical outline. Anterior and posterior clinoid processes are intact.</p>
<p><strong>VASCULAR GROOVES :</strong> Normal middle meningeal vascular markings and convolutional impressions. No abnormal intracranial radiopaque calcification.</p>
<p><strong>BASE OF SKULL & FACIAL BONES :</strong> Petrous ridges and sphenoid wings are symmetrical. Mandible shows normal alignment.</p>
HTML
                ,
                'Impression'     => "Normal radiograph of the skull (AP and Lateral views). No bony fracture or focal calvarial lesion.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'X-RAY DORSAL (THORACIC) SPINE AP & LATERAL',
                'title'          => 'X-Ray Dorsal Spine AP & Lateral',
                'keywords'       => 'xray dorsal spine, thoracic spine, ap lateral, kyphosis, vertebral body, compression fracture',
                'Modality'       => 3,
                'service_key'    => 'X-RAY DORSAL (THORACIC) SPINE AP & LATERAL',
                'Findings'       => <<<HTML
<p>Anteroposterior and lateral views of the dorsal (thoracic) spine were obtained.</p>
<p><strong>ALIGNMENT & CURVATURE :</strong> Normal gentle thoracic kyphotic curvature is preserved. No scoliosis or listhesis.</p>
<p><strong>VERTEBRAL BODIES :</strong> Vertebral body heights are maintained throughout the thoracic spine. No anterior wedge compression or burst fracture. Pedicles and posterior spinous processes are intact and symmetrical.</p>
<p><strong>DISC SPACES :</strong> Intervertebral disc spaces show normal height. Mild early marginal osteophytic spurring noted at mid-dorsal levels.</p>
<p><strong>PARASPINAL STRIPE :</strong> Paraspinal soft tissue lines are normal with no paraspinal mass, widening, or ossification.</p>
HTML
                ,
                'Impression'     => "Normal study / Mild early degenerative spondylotic changes in mid-dorsal spine without vertebral collapse or disc space narrowing.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'X-RAY WRIST JOINT AP & LATERAL',
                'title'          => 'X-Ray Wrist Joint AP & Lateral View',
                'keywords'       => 'xray wrist, colles fracture, scaphoid, radius, ulna, carpal bones, trauma',
                'Modality'       => 3,
                'service_key'    => 'X-RAY WRIST JOINT AP & LATERAL',
                'Findings'       => <<<HTML
<p>Radiographs of the [RIGHT / LEFT] wrist joint in anteroposterior and lateral projections were examined.</p>
<p><strong>DISTAL RADIUS & ULNA :</strong> Distal radial and ulnar cortices are smooth and continuous. Radial inclination, radial length, and volar tilt are preserved. Distal radioulnar joint space is normal. No fracture line or articular step-off.</p>
<p><strong>CARPAL BONES :</strong> Carpal bones (scaphoid, lunate, triquetrum, pisiform, trapezium, trapezoid, capitate, hamate) show normal morphology, cortical integrity, and alignment. Gilula arcs I, II, and III are intact and continuous. Scaphoid waist shows no cortical disruption or radiolucent fracture line.</p>
<p><strong>METACARPAL BASES :</strong> Bases of visualized metacarpals are intact.</p>
<p><strong>SOFT TISSUES :</strong> Pronator quadratus fat stripe and surrounding periarticular soft tissues are normal with no swelling.</p>
HTML
                ,
                'Impression'     => "Normal radiograph of the [RIGHT / LEFT] wrist joint. No fracture, dislocation, or carpal instability.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'X-RAY ANKLE JOINT AP & LATERAL',
                'title'          => 'X-Ray Ankle Joint AP, Lateral & Mortise Views',
                'keywords'       => 'xray ankle, bimalleolar, pott fracture, mortise view, talocrural, syndesmosis, sprain',
                'Modality'       => 3,
                'service_key'    => 'X-RAY ANKLE JOINT AP & LATERAL',
                'Findings'       => <<<HTML
<p>Anteroposterior, lateral, and mortise views of the [RIGHT / LEFT] ankle joint were obtained.</p>
<p><strong>BONES & MALLEOLI :</strong> Distal tibia, medial malleolus, distal fibula, and lateral malleolus are intact with smooth cortices. No cortical disruption, avulsion fracture, or periosteal reaction. Posterior malleolus is normal.</p>
<p><strong>TALOCRURAL JOINT & MORTISE :</strong> Ankle mortise is symmetrical and congruent throughout. Medial clear space measures < 4 mm. Tibiofibular clear space and overlap are within normal limits, indicating intact syndesmosis.</p>
<p><strong>TALUS & CALCANEUS :</strong> Talus dome is smooth without osteochondral defect. Visualized calcaneus and navicular show no fracture.</p>
<p><strong>SOFT TISSUES :</strong> Pre-achilles and retrocalcaneal fat pads are preserved. Mild soft tissue swelling over [LATERAL / MEDIAL] malleolus noted.</p>
HTML
                ,
                'Impression'     => "No radiographic evidence of fracture or dislocation of the [RIGHT / LEFT] ankle joint. Ankle mortise is intact.",
                'impression_cat' => 0,
            ],

            // -------------------------------------------------------------
            // CT-SCAN EXPANDED TEMPLATES (Modality = 4)
            // -------------------------------------------------------------
            [
                'template_name'  => 'CT PULMONARY ANGIOGRAPHY (CTPA)',
                'title'          => 'CT Pulmonary Angiography - Pulmonary Embolism Protocol',
                'keywords'       => 'ctpa, pulmonary embolism, pe, filling defect, saddle embolus, rv strain, d-dimer',
                'Modality'       => 4,
                'service_key'    => 'CT PULMONARY ANGIOGRAPHY (CTPA)',
                'Findings'       => <<<HTML
<p>Computed tomographic pulmonary angiography performed with bolus tracking in the main pulmonary artery following rapid intravenous non-ionic iodinated contrast injection.</p>
<p><strong>PULMONARY ARTERIAL TREE :</strong> Excellent contrast opacification achieved throughout the main pulmonary artery, right and left main pulmonary arteries, lobar, segmental, and subsegmental branches.</p>
<ul>
    <li>No evidence of eccentric or central intraluminal filling defects to suggest acute pulmonary thromboembolism.</li>
    <li>Main pulmonary artery diameter measures [MPA_MM] mm (Normal < 29 mm).</li>
    <li>Right Ventricle to Left Ventricle (RV/LV) diameter ratio in axial plane measures [RV_LV_RATIO] (Normal < 1.0, indicating absence of right ventricular strain).</li>
    <li>Interventricular septum is normal in position without flattening or paradoxical bowing into the left ventricle.</li>
</ul>
<p><strong>LUNG PARENCHYMA & PLEURA :</strong> No wedge-shaped pleural-based consolidation (Hampton's hump), focal oligemia (Westermark sign), atelectasis, or pleural effusion.</p>
<p><strong>MEDIASTINUM :</strong> No significant mediastinal, hilar, or axillary lymphadenopathy. Aorta shows normal caliber.</p>
HTML
                ,
                'Impression'     => "Negative for Acute Pulmonary Embolism on CT Pulmonary Angiography.\nNo filling defects in pulmonary arterial tree and no CT signs of right ventricular strain.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'CT ANGIOGRAPHY BRAIN & NECK (Circle of Willis)',
                'title'          => 'CT Angiography Brain & Neck - Cerebral Vasculature Protocol',
                'keywords'       => 'cta brain, circle of willis, aneurysm, stenosis, carotid, basilar, stroke, avm',
                'Modality'       => 4,
                'service_key'    => 'CT ANGIOGRAPHY BRAIN & NECK (CTA)',
                'Findings'       => <<<HTML
<p>Multidetector CT Angiography from aortic arch to vertex was performed following IV contrast injection with 3D MIP and VR reconstructions.</p>
<p><strong>AORTIC ARCH & SUPRA-AORTIC VESSELS :</strong> Aortic arch has normal three-vessel branching pattern (brachiocephalic trunk, left common carotid, left subclavian). Origins and courses are normal.</p>
<p><strong>CAROTID ARTERIES :</strong> Bilateral Common Carotid Arteries (CCA), carotid bulbs, and External Carotid Arteries (ECA) are patent. Internal Carotid Arteries (ICA) show smooth luminal caliber through cervical, petrous, cavernous, and supraclinoid segments without significant stenosis, ulcerated plaque, dissection, or occlusion (NASCET < 30%).</p>
<p><strong>VERTEBROBASILAR SYSTEM :</strong> Bilateral vertebral arteries are patent (Right / Left dominant) with normal convergence into the basilar artery. Basilar artery is normal in caliber and bifurcation.</p>
<p><strong>CIRCLE OF WILLIS & INTRACRANIAL BRANCHES :</strong> Anterior Communicating Artery (ACom), bilateral ACA (A1 and A2 segments), MCA (M1, M2, M3 segments), and PCA (P1 and P2 segments) are widely patent. No saccular or fusiform aneurysm, arteriovenous malformation (AVM) nidus, or significant intracranial stenosis.</p>
HTML
                ,
                'Impression'     => "Normal CT Angiography of the Brain and Neck Vessels.\nNo hemodynamically significant stenosis, dissection, intracranial aneurysm, or vascular malformation.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'CT UROGRAPHY (Tri-phasic CTU)',
                'title'          => 'CT Urography - Non-contrast, Nephrographic & Excretory Protocol',
                'keywords'       => 'ct urography, ctu, hematuria, urolithiasis, urothelial carcinoma, hydronephrosis, ureter',
                'Modality'       => 4,
                'service_key'    => 'CT UROGRAPHY (TRIPHASIC CTU)',
                'Findings'       => <<<HTML
<p>Triphasic CT Urography was performed comprising non-contrast, nephrographic, and delayed excretory pyelogram phases.</p>
<p><strong>NON-CONTRAST PHASE :</strong> Both kidneys are normal in size, shape, and position. No radiopaque calculus or calcification identified in the renal parenchyma, pelvicalyceal systems, ureters, or bladder.</p>
<p><strong>NEPHROGRAPHIC PHASE (Cortical & Medullary) :</strong> Bilateral symmetric and prompt renal parenchymal enhancement. No focal hypodense renal cortical mass, cyst, or solid lesion.</p>
<p><strong>EXCRETORY DELAYED PHASE (Pyelogram) :</strong> Prompt, symmetrical excretion of contrast bilaterally. Pelvicalyceal systems are normal in architecture without dilatation or filling defects.</p>
<p><strong>URETERS :</strong> Bilateral ureters are completely opacified throughout their abdominal, pelvic, and intramural courses. No ureteric dilatation, stricture, or intraluminal filling defect.</p>
<p><strong>URINARY BLADDER :</strong> Well distended with homogeneous contrast filling. Mucosal wall is thin and smooth (< 3 mm) without focal vegetative mass, diverticulum, or intraluminal mass.</p>
HTML
                ,
                'Impression'     => "Normal Triphasic CT Urography study. No evidence of urolithiasis, hydronephrosis, or urothelial filling defect in the upper or lower urinary tract.",
                'impression_cat' => 0,
            ],

            // -------------------------------------------------------------
            // MRI EXPANDED TEMPLATES (Modality = 2)
            // -------------------------------------------------------------
            [
                'template_name'  => 'MRI DORSAL (THORACIC) SPINE',
                'title'          => 'MRI Dorsal (Thoracic) Spine',
                'keywords'       => 'mri dorsal spine, thoracic, cord signal, myelopathy, disc bulge, canal stenosis',
                'Modality'       => 2,
                'service_key'    => 'MRI DORSAL (THORACIC) SPINE',
                'Findings'       => <<<HTML
<p>MRI of the dorsal spine was performed using T1WI, T2WI, and STIR sequences in sagittal and axial planes.</p>
<p><strong>ALIGNMENT :</strong> Normal thoracic kyphosis is preserved without listhesis or subluxation.</p>
<p><strong>VERTEBRAL BODIES :</strong> Vertebral body heights are maintained across all thoracic levels. Bone marrow demonstrates normal signal intensity. No focal lytic, blastic, or STIR hyperintense marrow infiltrative lesion.</p>
<p><strong>INTERVERTEBRAL DISCS :</strong> Disc heights and hydration signals are preserved. Mild early posterior disc bulge noted at D7-D8 and D8-D9 indenting the thecal sac without significant cord abutment or neural foraminal narrowing.</p>
<p><strong>SPINAL CORD :</strong> Thoracic spinal cord shows normal caliber, course, and signal intensity throughout. No abnormal focal intramedullary T2/STIR hyperintensity to suggest myelopathy, demyelination, or syringomyelia.</p>
<p><strong>CONUS MEDULLARIS :</strong> Conus medullaris terminates normally at the L1 vertebral level.</p>
HTML
                ,
                'Impression'     => "Normal dorsal spinal cord caliber and signal intensity.\nMild degenerative mid-dorsal disc bulges without cord compression or canal stenosis.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'MRI PELVIS FEMALE (Endometriosis / Uterus)',
                'title'          => 'MRI Pelvis Female - Endometriosis, Adenomyosis & Myoma Protocol',
                'keywords'       => 'mri pelvis female, endometriosis, adenomyosis, fibroid, endometrioma, deep infiltrating endometriosis, die',
                'Modality'       => 2,
                'service_key'    => 'MRI PELVIS FEMALE (ENDOMETRIOSIS)',
                'Findings'       => <<<HTML
<p>High-resolution dedicated female pelvic MRI performed using thin-slice high-resolution T2WI (sagittal, coronal, oblique axial), T1WI, and T1-FS sequences.</p>
<p><strong>UTERUS :</strong> Anteverted, normal/mildly enlarged measuring [UT_L] x [UT_B] x [UT_AP] cm.</p>
<ul>
    <li>Endometrium is regular with uniform signal intensity, measuring [ENDOM_MM] mm in thickness.</li>
    <li>Junctional Zone (JZ): Junctional zone is sharp and measures [JZ_MM] mm (Normal < 12 mm; thickness > 12 mm diagnostic of adenomyosis).</li>
    <li>Myometrium: Homogeneous myometrial signal intensity / [FIBROID_DESC: Well-circumscribed low T2 intramural/subserosal leiomyoma measuring [LEIO_CM] cm].</li>
</ul>
<p><strong>OVARIES :</strong> Bilateral ovaries are normal in size and position with peripheral folicular follicles. No T1-hyperintense, T2-shading cyst to suggest chocolate cyst (endometrioma).</p>
<p><strong>POUCH OF DOUGLAS & PERITONEUM :</strong> Pouch of Douglas is free. Uterosacral ligaments, torus uterinus, and rectovaginal septum show normal thin contours without fibrotic nodularity or deep infiltrating endometriosis (DIE) implants.</p>
<p><strong>URINARY BLADDER & RECTUM :</strong> Bladder and rectum walls are unremarkable with preserved perivesical and perirectal fat planes.</p>
HTML
                ,
                'Impression'     => "Normal female pelvic MRI / [FINDING: Mild adenomyosis / small uterine intramural leiomyoma].\nNo endometrioma or evidence of Deep Infiltrating Endometriosis (DIE).",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'MRCP (Magnetic Resonance Cholangiopancreatography)',
                'title'          => 'MRCP - Biliary and Pancreatic Ductal Imaging Protocol',
                'keywords'       => 'mrcp, cbd, bile duct, choledocholithiasis, gallstone, pancreatic duct, jaundice, pancreatitis',
                'Modality'       => 2,
                'service_key'    => 'MRCP (MAGNETIC RESONANCE CHOLANGIOPANCREATOGRAPHY)',
                'Findings'       => <<<HTML
<p>Dedicated Magnetic Resonance Cholangiopancreatography (MRCP) performed using thin-slice and thick-slab 3D T2-weighted respiratory-triggered and breath-hold sequences.</p>
<p><strong>GALLBLADDER :</strong> Well distended with normal thin wall (< 3 mm). Normal bile signal. No intraluminal calculi or pericholecystic fluid.</p>
<p><strong>BILIARY DUCTAL SYSTEM :</strong></p>
<ul>
    <li>Intrahepatic Biliary Radicles (IHBR): Normal in caliber, no intrahepatic biliary dilatation.</li>
    <li>Common Hepatic Duct (CHD): Measures [CHD_MM] mm, smooth with normal taper.</li>
    <li>Common Bile Duct (CBD): Measures [CBD_MM] mm (Normal < 7 mm). Normal course and gradual tapering at the ampulla. No intraductal filling defect, stone, stricture, or mass.</li>
</ul>
<p><strong>PANCREAS & MAIN PANCREATIC DUCT (MPD) :</strong> Pancreas shows normal parenchymal bulk, lobulation, and T1 signal intensity. Main pancreatic duct (Wirsung duct) is smooth and non-dilated measuring [MPD_MM] mm (Normal < 3 mm) throughout the head, body, and tail without intraductal calculus.</p>
<p><strong>LIVER & SPLEEN :</strong> Normal in size and signal intensity. No focal focal hepatic mass lesion.</p>
HTML
                ,
                'Impression'     => "Normal MRCP study. No evidence of choledocholithiasis, biliary tree dilatation, or stricture. Main pancreatic duct is normal.",
                'impression_cat' => 0,
            ],
            [
                'template_name'  => 'MRI PROSTATE (mpMRI PI-RADS v2.1 Protocol)',
                'title'          => 'Multiparametric MRI Prostate - PI-RADS v2.1 Protocol',
                'keywords'       => 'mri prostate, pirads, mpmri, prostate cancer, psa, peripheral zone, transition zone, bph',
                'Modality'       => 2,
                'service_key'    => 'MRI PROSTATE (mpMRI PI-RADS v2.1)',
                'Findings'       => <<<HTML
<p>Multiparametric MRI of the prostate performed using dedicated T2WI (axial, sagittal, coronal), high b-value DWI (b=1500), ADC maps, and Dynamic Contrast Enhanced (DCE) imaging according to PI-RADS v2.1 guidelines.</p>
<p><strong>PROSTATE GLAND :</strong> Measures approximately [TRANSVERSE] x [AP] x [CRANIAL] cm. Approximate volume: [VOL_CC] cc.</p>
<p><strong>PERIPHERAL ZONE (PZ) :</strong> Uniformly high T2 signal intensity bilaterally. No focal low T2 hypointense lesion, no restricted diffusion on DWI/ADC, and no early focal focal focal enhancement on DCE. (PI-RADS Assessment: 1/5).</p>
<p><strong>TRANSITION ZONE (TZ) :</strong> Shows typical heterogeneous rounded encapsulated stromal and glandular hyperplasia nodules (BPH pattern). No non-circumscribed homogeneous low T2 "erased charcoal" lesion with diffusion restriction. (PI-RADS Assessment: 2/5).</p>
<p><strong>CAPSULE & NEUROVASCULAR BUNDLES :</strong> Prostatic capsule is intact throughout without capsular abutment, bulge, or extraprostatic extension (EPE).</p>
<p><strong>SEMINAL VESICLES & BLADDER BASE :</strong> Bilateral seminal vesicles show normal fluid content with no invasion. Bladder base is unremarkable.</p>
<p><strong>PELVIC LYMPH NODES & BONES :</strong> No suspicious pelvic lymphadenopathy. Pelvic bones demonstrate normal marrow signal without focal metastatic lesions.</p>
HTML
                ,
                'Impression'     => "Multiparametric MRI of Prostate shows Benign Prostatic Hyperplasia (BPH).\nNo suspicious focal lesion in peripheral or transition zones. Overall PI-RADS Score: Category 2 (Clinically significant cancer is unlikely to be present).",
                'impression_cat' => 0,
            ],
        ];
    }
}

