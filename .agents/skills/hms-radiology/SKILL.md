---
name: hms-radiology
description: Use when building, modifying, or testing radiology reporting, modality configurations (USG, X-Ray, CT, MRI, ECHO), structured clinical templates, findings, impressions, DICOM/PACS attachments, and radiologist sign-off workflows.
---

# HMS Radiology & Diagnostic Imaging Management Skill

This skill guides the agent through managing radiology reporting templates, imaging modalities, structured clinical findings, impressions, and report printing in E-Atria HMS.

---

## 1. Radiology Architecture & Modalities

The HMS categorizes diagnostic imaging under `radiology_modality` and structured templates in `opd_clinical_templates` / `doc_print_templates`:

| Modality Code | Modality Name | Common Procedures | Template Pattern |
| :--- | :--- | :--- | :--- |
| `USG` | Ultrasonography | Whole Abdomen, KUB, Pelvis, Obstetrics / Antenatal, Thyroid, Scrotum | Organ-by-organ evaluation (Liver, Gall Bladder, Spleen, Pancreas, Kidneys, Bladder, Uterus) |
| `XR` | Digital X-Ray | Chest PA, Spine (Cervical, Lumbar, Dorsal), Extremities, Skull | Bony cage, lung parenchyma, mediastinum, cardiothoracic ratio, soft tissues |
| `CT` | Computed Tomography | NCCT/CECT Head, CECT Abdomen & Pelvis, HRCT Chest, CT Angiography | Protocol, slice thickness, attenuation, parenchymal findings, 3D reconstructions |
| `MRI` | Magnetic Resonance | MRI Brain, MRI Spine (C/D/L), Musculoskeletal Joints (Knee, Shoulder) | Sequences (T1, T2, FLAIR, DWI), signal intensity, disc bulges, cord signals |
| `ECHO` | 2D Echocardiography | 2D Echo with Doppler | Chamber dimensions, ejection fraction (EF%), valve morphology, wall motion analysis |

---

## 2. Standard 5-Section Reporting Structure

Every radiology report generated in the HMS must follow a standard structured clinical format:

1. **Clinical History & Indication**:
   - Reason for scan (e.g. "Acute right lower quadrant pain, rule out appendicitis", "Dry cough with fever x 2 weeks").
2. **Technique / Protocol**:
   - Modality details (e.g. "Real-time high-resolution gray scale and color Doppler examination performed using 3.5 MHz convex and 7.5 MHz linear probes").
3. **Observations & Findings**:
   - Systematic description of all evaluated structures.
   - Normal organs must have standard negative findings (e.g. "Liver is normal in size and acoustic texture. No focal mass lesion or intrahepatic biliary radicle dilatation").
   - Pathological lesions must state exact anatomical location, size (in mm/cm), margins, echogenicity/attenuation, and vascularity.
4. **Impression / Conclusion**:
   - Concise diagnostic synthesis (e.g. "Ultrasonographic features suggestive of Acute Calculous Cholecystitis with mild pericholecystic fluid").
5. **Recommendations / Follow-up**:
   - Further clinical advice (e.g. "Clinical and surgical correlation suggested. MRCP recommended if common bile duct stone is suspected").

---

## 3. Database Tables & Template Storage

- `radiology_modality`: Modality master (`id`, `modality_code`, `modality_name`, `is_active`).
- `radiology_ultrasound_template`: Built-in organ narrative templates for rapid reporting.
- `opd_clinical_templates`: Customizable rich-text templates with placeholders (`{{PATIENT_NAME}}`, `{{AGE}}`, `{{GENDER}}`, `{{DATE}}`, `{{REFERRING_DOCTOR}}`).
- `doc_print_templates`: Printable formats with letterhead/plain paper configurations.

---

## 4. Radiologist Digital Authorization & Sign-off

- Draft reports are saved with `status = 'draft'` and editable by resident doctors / technicians.
- Only registered Radiologists (`doctor_master` with specialization in Radiology / Radio-diagnosis) can approve and publish (`status = 'final'`).
- The final PDF includes the Radiologist's name, qualifications (MD Radio-diagnosis / DMRD), Medical Council Registration Number, and digital signature watermark.
