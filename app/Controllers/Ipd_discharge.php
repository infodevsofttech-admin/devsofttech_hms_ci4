<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Abdm\Fhir\FhirGeneratorFactory;
use App\Libraries\Abdm\Fhir\Support\GatewayPayloadAdapter;
use App\Libraries\Abdm\Sync\AbdmSyncOutboxService;
use App\Libraries\AbdmWorkTaskService;
use App\Libraries\BridgeSyncService;
use App\Models\IpdBillingModel;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class Ipd_discharge extends BaseController
{
    protected IpdBillingModel $ipdBillingModel;

    public function __construct()
    {
        $this->ipdBillingModel = new IpdBillingModel();
        helper(['form', 'age']);
    }

    private function dischargeTabUrl(int $ipdId): string
    {
        return site_url('billing/ipd/panel/' . $ipdId . '/tab/discharge-process');
    }

    private function requireAnyPermission(array $permissions)
    {
        if (! function_exists('auth')) {
            return null;
        }

        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return null;
            }
        }

        return $this->response->setStatusCode(403)->setBody('Access denied');
    }

    private function getDischargeContent(int $ipdId): string
    {
        if (! $this->db->tableExists('ipd_discharge')) {
            return '';
        }

        $row = $this->db->table('ipd_discharge')
            ->where('ipd_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return (string) ($row['content'] ?? '');
    }

    private function ensureDischargeTemplateTable(): void
    {
        if ($this->db->tableExists('ipd_discharge_templates')) {
            $this->ensureDischargeTemplateColumns();
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS ipd_discharge_templates (
            id INT NOT NULL AUTO_INCREMENT,
            template_name VARCHAR(120) NOT NULL,
            page_size VARCHAR(16) NOT NULL DEFAULT 'A4',
            custom_width_mm INT NOT NULL DEFAULT 210,
            custom_height_mm INT NOT NULL DEFAULT 297,
            page_margin_top_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80,
            page_margin_bottom_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80,
            page_margin_left_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80,
            page_margin_right_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80,
            margin_header_cm DECIMAL(5,2) NOT NULL DEFAULT 0.50,
            margin_footer_cm DECIMAL(5,2) NOT NULL DEFAULT 0.50,
            header_html LONGTEXT NULL,
            footer_html LONGTEXT NULL,
            template_css LONGTEXT NULL,
            template_html LONGTEXT NOT NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            is_audit_only TINYINT(1) NOT NULL DEFAULT 0,
            watermark_type VARCHAR(10) NOT NULL DEFAULT 'none',
            watermark_text VARCHAR(255) NULL,
            watermark_image VARCHAR(255) NULL,
            watermark_alpha DECIMAL(4,2) NOT NULL DEFAULT 0.12,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->query($sql);
        $this->ensureDischargeTemplateColumns();
    }

    private function ensureDischargeTemplateColumns(): void
    {
        if (! $this->db->tableExists('ipd_discharge_templates')) {
            return;
        }

        $columns = [
            'page_size' => "ALTER TABLE ipd_discharge_templates ADD COLUMN page_size VARCHAR(16) NOT NULL DEFAULT 'A4' AFTER template_name",
            'custom_width_mm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN custom_width_mm INT NOT NULL DEFAULT 210 AFTER page_size',
            'custom_height_mm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN custom_height_mm INT NOT NULL DEFAULT 297 AFTER custom_width_mm',
            'page_margin_top_cm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN page_margin_top_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER custom_height_mm',
            'page_margin_bottom_cm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN page_margin_bottom_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_top_cm',
            'page_margin_left_cm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN page_margin_left_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_bottom_cm',
            'page_margin_right_cm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN page_margin_right_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_left_cm',
            'margin_header_cm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN margin_header_cm DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER page_margin_right_cm',
            'margin_footer_cm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN margin_footer_cm DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER margin_header_cm',
            'header_html' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN header_html LONGTEXT NULL AFTER margin_footer_cm',
            'footer_html' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN footer_html LONGTEXT NULL AFTER header_html',
            'template_css' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN template_css LONGTEXT NULL AFTER footer_html',
            'is_audit_only' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN is_audit_only TINYINT(1) NOT NULL DEFAULT 0 AFTER is_default',
            'is_abdm' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN is_abdm TINYINT(1) NOT NULL DEFAULT 0 AFTER is_audit_only',
            'watermark_type' => "ALTER TABLE ipd_discharge_templates ADD COLUMN watermark_type VARCHAR(10) NOT NULL DEFAULT 'none' AFTER is_abdm",
            'watermark_text' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN watermark_text VARCHAR(255) NULL AFTER watermark_type',
            'watermark_image' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN watermark_image VARCHAR(255) NULL AFTER watermark_text',
            'watermark_alpha' => 'ALTER TABLE ipd_discharge_templates ADD COLUMN watermark_alpha DECIMAL(4,2) NOT NULL DEFAULT 0.12 AFTER watermark_image',
        ];

        foreach ($columns as $column => $sql) {
            if (! $this->db->fieldExists($column, 'ipd_discharge_templates')) {
                try {
                    $this->db->query($sql);
                } catch (\Throwable $e) {
                    // Ignore schema drift during runtime; existing columns are enough for save/render.
                }
            }
        }

        if ($this->db->fieldExists('is_audit_only', 'ipd_discharge_templates')) {
            $this->db->table('ipd_discharge_templates')
                ->where('template_name', 'NABH Compliant Discharge Summary')
                ->where('is_audit_only', 0)
                ->set('is_audit_only', 1)
                ->update();
        }
    }

    private function defaultDischargeTemplateHtml(): string
    {
        return '{{DISCHARGE_STATUS}}'
            . '<table class="discharge-info-table" border="1" cellpadding="6">'
            . '<tr>'
            . '<td><b>Patient</b>: {{PATIENT_NAME}}</td>'
            . '<td><b>UHID</b>: {{UHID}}</td>'
            . '<td><b>IPD</b>: {{IPD_CODE}}</td>'
            . '</tr>'
            . '<tr>'
            . '<td><b>Age/Gender</b>: {{AGE_GENDER}}</td>'
            . '<td><b>Guardian</b>: {{GUARDIAN_RELATION}}{{GUARDIAN_NAME}}</td>'
            . '<td><b>Address</b>: {{PATIENT_ADDRESS}}</td>'
            . '</tr>'
            . '<tr>'
            . '<td><b>Admit Date</b>: {{ADMIT_DATE}}</td>'
            . '<td><b>Discharge Date</b>: {{DISCHARGE_DATE}}</td>'
            . '<td><b>Prepared On</b>: {{CURRENT_DATE}}</td>'
            . '</tr>'
            . '</table>'
            . '<div>{{DISCHARGE_SUMMARY}}</div>'
            . '<div>{{PRESENTING_COMPLAINTS}}</div>'
            . '<div>{{PAIN_MEASUREMENT_SCALE}}</div>'
            . '<div>{{GENERAL_EXAM_ADMISSION}}</div>'
            . '<div>{{CLINICAL_INVESTIGATION_REPORTS}}</div>'
            . '<div>{{FINAL_DIAGNOSIS}}</div>'
            . '<div>{{COURSE_IN_HOSPITAL}}</div>'
            . '<div>{{EXAMINATION_ON_DISCHARGE}}</div>'
            . '<div>{{SURGERY}}</div>'
            . '<div>{{PROCEDURE}}</div>'
            . '<div>{{PERSONAL_HISTORY}}</div>'
            . '<div>{{DRUG_ALLERGY_ADR}}</div>'
            . '<div>{{CO_MORBIDITIES}}</div>'
            . '<div>{{DISCHARGE_MEDICATIONS}}</div>'
            . '<div>{{DIETARY_ADVICE}}</div>'
            . '<div>{{REVIEW_AFTER}}</div>'
            ;
    }

    private function defaultDischargeTemplateSettings(): array
    {
        return [
            'page_size' => 'A4',
            'custom_width_mm' => 210,
            'custom_height_mm' => 297,
            'page_margin_top_cm' => 0.80,
            'page_margin_bottom_cm' => 0.80,
            'page_margin_left_cm' => 0.80,
            'page_margin_right_cm' => 0.80,
            'margin_header_cm' => 0.50,
            'margin_footer_cm' => 0.50,
            'header_html' => '',
            'footer_html' => '',
            'template_css' => '',
            'watermark_type' => 'none',
            'watermark_text' => '',
            'watermark_image' => '',
            'watermark_alpha' => 0.12,
        ];
    }

    private function nabhDischargeTemplateHtml(): string
    {
        return '{{DISCHARGE_STATUS}}'
            . '<table class="discharge-info-table" border="1" cellpadding="6">'
            . '<tr>'
            . '<td><b>Patient Name</b>: {{PATIENT_NAME}}</td>'
            . '<td><b>UHID</b>: {{UHID}}</td>'
            . '<td><b>IPD No.</b>: {{IPD_CODE}}</td>'
            . '</tr>'
            . '<tr>'
            . '<td><b>Age/Gender</b>: {{AGE_GENDER}}</td>'
            . '<td><b>Guardian</b>: {{GUARDIAN_RELATION}}{{GUARDIAN_NAME}}</td>'
            . '<td><b>Address</b>: {{PATIENT_ADDRESS}}</td>'
            . '</tr>'
            . '<tr>'
            . '<td><b>Date of Admission</b>: {{ADMIT_DATE}}</td>'
            . '<td><b>Date of Discharge</b>: {{DISCHARGE_DATE}}</td>'
            . '<td><b>Prepared On</b>: {{CURRENT_DATE}}</td>'
            . '</tr>'
            . '</table>'
            . '<div class="nabh-guidance">'
            . 'NABH guidance note: Ensure diagnosis, procedures, clinical course, condition at discharge, medication with dose/duration, follow-up advice, red-flag signs, and emergency contact are documented.'
            . '</div>'
            . '<div class="discharge-section">{{DISCHARGE_SUMMARY}}</div>'
            . '<div class="discharge-section">{{PRESENTING_COMPLAINTS}}</div>'
            . '<div class="discharge-section">{{PAIN_MEASUREMENT_SCALE}}</div>'
            . '<div class="discharge-section">{{GENERAL_EXAM_ADMISSION}}</div>'
            . '<div class="discharge-section">{{CLINICAL_INVESTIGATION_REPORTS}}</div>'
            . '<div class="discharge-section">{{FINAL_DIAGNOSIS}}</div>'
            . '<div class="discharge-section">{{COURSE_IN_HOSPITAL}}</div>'
            . '<div class="discharge-section">{{EXAMINATION_ON_DISCHARGE}}</div>'
            . '<div class="discharge-section">{{SURGERY}}</div>'
            . '<div class="discharge-section">{{PROCEDURE}}</div>'
            . '<div class="discharge-section">{{PERSONAL_HISTORY}}</div>'
            . '<div class="discharge-section">{{DRUG_ALLERGY_ADR}}</div>'
            . '<div class="discharge-section">{{CO_MORBIDITIES}}</div>'
            . '<div class="discharge-section">{{DISCHARGE_MEDICATIONS}}</div>'
            . '<div class="discharge-section">{{DIETARY_ADVICE}}</div>'
            . '<div class="discharge-section">{{REVIEW_AFTER}}</div>'
            . '<h4 class="discharge-section-heading">Counselling & Handover Confirmation</h4>'
            . '<table class="discharge-table" border="1" cellpadding="6">'
            . '<tr><td>Medication explained to patient/attendant</td><td></td><td>Remarks:</td></tr>'
            . '<tr><td>Follow-up date and department explained</td><td></td><td>Next Visit: __________________</td></tr>'
            . '<tr><td>Red-flag symptoms explained</td><td></td><td>Emergency Contact: __________________</td></tr>'
            . '<tr><td>Diet and activity instructions explained</td><td></td><td></td></tr>'
            . '</table>'
            . '<table class="discharge-signature-table" border="1" cellpadding="10">'
            . '<tr>'
            . '<td>____________________<br>Consultant Name/Signature</td>'
            . '<td>____________________<br>Medical Officer Signature</td>'
            . '<td>____________________<br>Patient/Attendant Signature & Date</td>'
            . '</tr>'
            . '</table>';
    }

    private function ensureDefaultDischargeTemplateSeeded(): void
    {
        $this->ensureDischargeTemplateTable();
        if (! $this->db->tableExists('ipd_discharge_templates')) {
            return;
        }

        $table = $this->db->table('ipd_discharge_templates');
        $count = (int) ($table->countAllResults() ?? 0);
        if ($count === 0) {
            $table->insert([
                'template_name' => 'Default Discharge Template',
                'template_html' => $this->defaultDischargeTemplateHtml(),
                'is_default' => 1,
                'status' => 1,
            ]);
        }

        $nabhExists = $this->db->table('ipd_discharge_templates')
            ->where('template_name', 'NABH Compliant Discharge Summary')
            ->get(1)
            ->getRowArray();

        if (empty($nabhExists)) {
            $table->insert([
                'template_name' => 'NABH Compliant Discharge Summary',
                'template_html' => $this->nabhDischargeTemplateHtml(),
                'is_default' => 0,
                'status' => 1,
            ]);
        }
    }

    private function getDischargeTemplateRows(): array
    {
        // Ensure table structure exists, but don't auto-seed templates
        // This allows users to delete all templates without them auto-regenerating
        $this->ensureDischargeTemplateTable();
        if (! $this->db->tableExists('ipd_discharge_templates')) {
            return [];
        }

        return $this->db->table('ipd_discharge_templates')
            ->select('id,template_name,page_size,custom_width_mm,custom_height_mm,page_margin_top_cm,page_margin_bottom_cm,page_margin_left_cm,page_margin_right_cm,margin_header_cm,margin_footer_cm,header_html,footer_html,template_css,template_html,is_default,is_audit_only,is_abdm,watermark_type,watermark_text,watermark_image,watermark_alpha,status')
            ->where('status', 1)
            ->orderBy('is_abdm', 'DESC')
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function resolveAbdmDischargeTemplate(int $requestedId = 0): array
    {
        $templates = $this->getDischargeTemplateRows();
        if (empty($templates)) {
            return [];
        }

        if ($requestedId > 0) {
            foreach ($templates as $tmpl) {
                if ((int) ($tmpl['id'] ?? 0) === $requestedId) {
                    return $tmpl;
                }
            }
        }

        foreach ($templates as $tmpl) {
            if ((int) ($tmpl['is_abdm'] ?? 0) === 1) {
                return $tmpl;
            }
        }

        foreach ($templates as $tmpl) {
            if ((int) ($tmpl['is_default'] ?? 0) === 1) {
                return $tmpl;
            }
        }

        foreach ($templates as $tmpl) {
            if (! $this->isDischargeAuditTemplate($tmpl)) {
                return $tmpl;
            }
        }

        return $templates[0] ?? [];
    }

    private function isDischargeAuditTemplate(array $template): bool
    {
        return (int) ($template['is_audit_only'] ?? 0) === 1
            || strtolower(trim((string) ($template['template_name'] ?? ''))) === 'nabh compliant discharge summary';
    }

    private function getPrintableDischargeTemplateRows(): array
    {
        return array_values(array_filter(
            $this->getDischargeTemplateRows(),
            fn (array $template): bool => ! $this->isDischargeAuditTemplate($template)
        ));
    }

    private function resolvePrintableDischargeTemplateId(int $requestedId, array $templates): int
    {
        if ($requestedId > 0) {
            foreach ($templates as $template) {
                if ((int) ($template['id'] ?? 0) === $requestedId) {
                    return $requestedId;
                }
            }
        }

        foreach ($templates as $template) {
            if ((int) ($template['is_default'] ?? 0) === 1) {
                return (int) ($template['id'] ?? 0);
            }
        }

        return (int) ($templates[0]['id'] ?? 0);
    }

    private function getHospitalSettingValue(string $key): string
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $key)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    private function buildDischargeTemplateTokenVars(array $panelData, string $content): array
    {
        $ipd = $panelData['ipd_info'] ?? null;
        $person = $panelData['person_info'] ?? null;

        $patientTitle = trim((string) (
            $person->title
            ?? $person->p_title
            ?? $person->prefix
            ?? ''
        ));
        $patientName = trim((string) ($person->p_fname ?? ''));
        $patientCode = trim((string) (
            $person->uhid
            ?? $person->UHID
            ?? $person->patient_code
            ?? $person->p_code
            ?? $person->reg_no
            ?? ''
        ));
        $ipdCode = trim((string) ($ipd->ipd_code ?? ''));

        $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
        $ageGender = trim($age . ' / ' . ((string) ($person->xgender ?? '')));

        $hName = $this->getHospitalSettingValue('H_Name');
        $hAddress1 = $this->getHospitalSettingValue('H_address_1');
        $hAddress2 = $this->getHospitalSettingValue('H_address_2');
        $hPhone = $this->getHospitalSettingValue('H_phone_No');
        $hEmail = $this->getHospitalSettingValue('H_Email');
        $hLogo = $this->getHospitalSettingValue('H_logo');

        if ($hName === '' && defined('H_Name')) {
            $hName = (string) constant('H_Name');
        }
        if ($hAddress1 === '' && defined('H_address_1')) {
            $hAddress1 = (string) constant('H_address_1');
        }
        if ($hAddress2 === '' && defined('H_address_2')) {
            $hAddress2 = (string) constant('H_address_2');
        }
        if ($hPhone === '' && defined('H_phone_No')) {
            $hPhone = (string) constant('H_phone_No');
        }
        if ($hEmail === '' && defined('H_Email')) {
            $hEmail = (string) constant('H_Email');
        }
        if ($hLogo === '' && defined('H_logo')) {
            $hLogo = (string) constant('H_logo');
        }

        $hospitalAddress = trim($hAddress1 . ', ' . $hAddress2, ', ');

        $tokens = [
            'CONTENT' => $content,
            'PATIENT_TITLE' => esc($patientTitle),
            'PATIENT_NAME' => esc($patientName),
            'UHID' => esc($patientCode),
            'IPD_CODE' => esc($ipdCode),
            'AGE_GENDER' => esc($ageGender),
            'ADMIT_DATE' => esc($this->safeDate((string) ($ipd->str_register_date ?? $ipd->register_date ?? ''))),
            'DISCHARGE_DATE' => esc($this->safeDate((string) ($ipd->str_discharge_date ?? $ipd->discharge_date ?? ''))),
            'ISDELIVERY' => esc((string) ($ipd->isdelivery ?? '')),
            'CURRENT_DATE' => esc(date('d-m-Y')),
            'PRINT_TIME' => esc(date('d-m-Y H:i:s')),
            'H_Name' => esc($hName),
            'H_address_1' => esc($hAddress1),
            'H_address_2' => esc($hAddress2),
            'H_phone_No' => esc($hPhone),
            'H_Email' => esc($hEmail),
            'H_logo' => esc($hLogo),
            'H_logo_abs' => esc($hLogo !== '' ? (FCPATH . 'assets/images/' . $hLogo) : ''),
            'hospital_name' => esc($hName),
            'hospital_address' => esc($hospitalAddress),
            'hospital_phone' => esc($hPhone),
            'hospital_email' => esc($hEmail),
        ];

        // Add patient info table as a token (not included in CONTENT by default)
        $tokens['PATIENT_INFO_TABLE'] = $this->buildAutoDischargeSummaryTable($panelData);

        $summaryTokens = $this->buildDischargeSummaryTokenVars($panelData);
        foreach ($summaryTokens as $key => $value) {
            $tokens[$key] = $value;
        }

        $sectionVars = $this->buildDischargeSectionTokenVars($content, $panelData);
        foreach ($sectionVars as $key => $value) {
            $tokens[$key] = $value;
        }

        foreach ($tokens as $key => $value) {
            $key = (string) $key;
            $value = (string) $value;

            $lower = strtolower($key);
            if (! array_key_exists($lower, $tokens)) {
                $tokens[$lower] = $value;
            }

            $upper = strtoupper($key);
            if (! array_key_exists($upper, $tokens)) {
                $tokens[$upper] = $value;
            }

            $ucFirst = ucfirst($lower);
            if (! array_key_exists($ucFirst, $tokens)) {
                $tokens[$ucFirst] = $value;
            }
        }

        return $tokens;
    }

    private function buildDischargeSummaryTokenVars(array $panelData): array
    {
        $ipd = $panelData['ipd_info'] ?? null;
        $person = $panelData['person_info'] ?? null;

        $guardianRelation = '';
        $guardianName = '';
        $address = '';

        if ($person) {
            $guardianRelation = trim((string) ($person->p_relative ?? $person->relation ?? $person->guardian_relation ?? ''));
            $guardianName = trim((string) ($person->p_rname ?? $person->relative_name ?? $person->guardian_name ?? ''));

            $addressParts = [];
            foreach (['address_1', 'address', 'p_address', 'add1', 'add2', 'city', 'district', 'state', 'zip'] as $field) {
                $part = trim((string) ($person->{$field} ?? ''));
                if ($part === '') {
                    continue;
                }

                if (! in_array($part, $addressParts, true)) {
                    $addressParts[] = $part;
                }
            }
            $address = trim(implode(', ', $addressParts));
        }

        if ($address === '' && $ipd) {
            $address = trim((string) ($ipd->address ?? $ipd->patient_address ?? $ipd->contact_address ?? ''));
        }

        $guardianCombined = trim($guardianRelation . ($guardianName !== '' ? ' ' . $guardianName : ''));
        if ($guardianCombined === '') {
            $guardianCombined = $guardianName !== '' ? $guardianName : $guardianRelation;
        }

        // Patient phone number
        $patientPhone = '';
        if ($person) {
            $patientPhone = trim((string) ($person->mphone1 ?? $person->phone ?? $person->contact_no ?? ''));
        }
        if ($patientPhone === '' && $ipd) {
            $patientPhone = trim((string) ($ipd->P_mobile1 ?? $ipd->P_mobile2 ?? $ipd->contact_phone ?? ''));
        }

        // Insurance company name
        $insuranceCompany = '';
        if ($ipd) {
            $insuranceCompany = trim((string) ($ipd->ins_short_name ?? $ipd->ins_company_name ?? ''));
            if ($insuranceCompany === '') {
                $insuranceCompany = 'Direct';
            }
        }

        // Doctor names (from doc_list field in ipd_master)
        $doctorNames = $this->getDischargeDoctorNames($ipd);

        return [
            'GUARDIAN_RELATION' => esc($guardianRelation !== '' ? $guardianRelation . ' ' : ''),
            'GUARDIAN_NAME' => esc($guardianName),
            'GUARDIAN' => esc($guardianCombined),
            'PATIENT_ADDRESS' => esc($address),
            'PATIENT_PHONE' => esc($patientPhone),
            'DEPARTMENT' => esc($this->getDischargeDepartmentName($ipd)),
            'ADMIT_DATE_ONLY' => esc($this->safeDate((string) ($ipd->str_register_date ?? $ipd->register_date ?? ''))),
            'DISCHARGE_DATE_ONLY' => esc($this->safeDate((string) ($ipd->str_discharge_date ?? $ipd->discharge_date ?? ''))),
            'ADMISSION_TIME' => esc($this->safeTime((string) ($ipd->reg_time ?? $ipd->register_time ?? ''))),
            'DISCHARGE_TIME' => esc($this->safeTime((string) ($ipd->discharge_time ?? ''))),
            'ADMIT_TIME' => esc($this->safeTime((string) ($ipd->reg_time ?? $ipd->register_time ?? ''))),
            'INSURANCE_COMPANY' => esc($insuranceCompany),
            'DOCTOR_NAMES' => esc($doctorNames),
            'DOCTOR_NAME' => esc($doctorNames),
        ];
    }

    private function buildDischargeSectionTokenVars(string $content, array $panelData): array
    {
        $full = trim($content);

        $ipd = $panelData['ipd_info'] ?? null;
        $dischargeStatus = $this->getDischargeStatusText($ipd);

        $allMarkers = [
            'Discharge Summary',
            'Presenting Complaints and Reason for Admission',
            'Pain Measurement Scale',
            'General Examination on Admission',
            'Clinical Investigation Reports',
            'Final Diagnosis',
            'Course in the hospital',
            'Examination on Discharge',
            'Surgery',
            'Procedure',
            'Personal History',
            'Drug Allergy / ADR',
            'Co-Morbidities',
            'Prescribed Medicines',
            'Discharge Medications',
            'Discharge Advice/Instructions/Summary',
            'Dietary Advice',
            'Other Advice:',
            'Discharge Summary:',
            'Signature of Consultant',
            'Summary of key investigations during Hospitalization',
            'Nursing Trend',
        ];

        $section = function (array $starts, array $extraEnds = []) use ($full, $allMarkers): string {
            return $this->extractDischargeSectionByMarkers($full, $starts, array_merge($allMarkers, $extraEnds));
        };

        $ipd = $panelData['ipd_info'] ?? null;
        $ipdId = (int) ($ipd->id ?? 0);

        // Main clinical discharge summary: use explicit DB field first (instruction_remark).
        // Fallback to section extraction for older cached content.
        $instructionRow = $ipdId > 0 ? $this->firstRowByIpd('ipd_discharge_instructions', $ipdId) : [];
        $instructionMeta = $this->parseInstructionMetaPayload((string) ($instructionRow['comp_report'] ?? ''));
        $clinicalSummaryRaw = $this->normalizeRichText((string) ($instructionRow['comp_remark'] ?? ''));
        if ($clinicalSummaryRaw !== '') {
            $clinicalSummary = '<div class="discharge-summary-content">' . $this->renderRichText($clinicalSummaryRaw) . '</div>';
        } else {
            $extracted = $section(['Discharge Summary']);
            $plainText = trim(strip_tags((string) $extracted));
            // If the extracted summary only contains the title "Discharge Summary" or is blank, treat as empty
            if ($plainText === '' || strcasecmp($plainText, 'Discharge Summary') === 0) {
                $clinicalSummary = '';
            } else {
                $clinicalSummary = $extracted;
            }
        }
        
        // Replace "Discharge Summary" header with discharge status if non-default status exists
        if ($dischargeStatus !== ''
            && strcasecmp(trim($dischargeStatus), 'Discharge Summary') !== 0
            && $clinicalSummary !== '') {
            $clinicalSummary = preg_replace(
                '/<h[1-6][^>]*>\s*Discharge\s+Summary\s*<\/h[1-6]>/i',
                '<h2>' . esc($dischargeStatus) . '</h2>',
                $clinicalSummary
            );
            // Also handle bold/strong tags
            $clinicalSummary = preg_replace(
                '/<(b|strong)>\s*Discharge\s+Summary\s*<\/\1>/i',
                '<strong>' . esc($dischargeStatus) . '</strong>',
                $clinicalSummary
            );
            // Remove "Date of Discharge" line
            $clinicalSummary = preg_replace(
                '/Date\s+of\s+Discharge\s*:\s*[0-9\-\/]+\s*<br\s*\/?>/i',
                '',
                $clinicalSummary
            );
            $clinicalSummary = preg_replace(
                '/<p[^>]*>\s*Date\s+of\s+Discharge\s*:\s*[0-9\-\/]+\s*<\/p>/i',
                '',
                $clinicalSummary
            );
        }

        $plainSummary = trim(strip_tags((string) $clinicalSummary));
        if ($plainSummary === '' || strcasecmp($plainSummary, 'Discharge Summary') === 0) {
            $clinicalSummary = '';
        } elseif ($clinicalSummary !== '') {
            $hasSummaryHeading = preg_match(
                '/<h[1-6][^>]*>\s*Discharge\s+Summary\s*<\/h[1-6]>|<(?:b|strong)[^>]*>\s*Discharge\s+Summary\s*<\/(?:b|strong)>/i',
                $clinicalSummary
            ) === 1;
            if (! $hasSummaryHeading) {
                $clinicalSummary = '<h4 class="discharge-section-heading">DISCHARGE SUMMARY</h4>' . $clinicalSummary;
            }
        }
        
        $finalDiagnosis = $section(['Final Diagnosis']);
        // Use explicit section labels to avoid matching free-text words like "elective surgery".
        $surgery = $section(['<b>Surgery :', '>Surgery :', 'Surgery :']);
        $procedure = $section(['<b>Procedure :', '>Procedure :', 'Procedure :']);
        $personalHistory = $section(['Personal History']);
        $presentingComplaints = $section([
            'Presenting Complaints and Reason for Admission',
            'Presenting Complaints with Duration and Reason for Admission',
            'Complaints with Duration and Reason for Admission',
        ]);
        $painMeasurement = $section(['Pain Measurement Scale']);
        $generalExam = $section(['General Examination on Admission']);
        $clinicalInvestigations = $section(['Clinical Investigation Reports']);
        $courseInHospital = $section(['Course in the hospital']);
        $examOnDischarge = $section(['Examination on Discharge']);
        $dischargeMedications = $section(['Prescribed Medicines', 'Discharge Medications']);
        // Always prefer fresh OPD-style table from DB rows when rows exist.
        // Also replace stale cached HTML that contains old-format columns or form artefacts
        // (e.g. "Medicine Advice: EditRemove", "Medicine Name | Dosage | Qty | Day").
        $opdStyleMedHtml = $this->buildOpdStyleMedicationsHtml($ipdId);
        if ($opdStyleMedHtml !== '') {
            $dischargeMedications = $opdStyleMedHtml;
        } elseif ($dischargeMedications !== '') {
            // Sanitize any leftover action links from cached HTML (Edit/Remove buttons)
            $dischargeMedications = (string) preg_replace(
                '/<\s*a\b[^>]*>\s*(?:Edit|Remove|Delete)\s*<\/a>/i',
                '',
                $dischargeMedications
            );
        }
        $dietaryAdvice = $section(['Dietary Advice'], ['Review after', 'Review After']);
        $dietaryAdvice = $this->trimDietaryAdviceTail($dietaryAdvice);
        $drugAllergyAdr = $section(['Drug Allergy / ADR']);
        $coMorbidities = $section(['Co-Morbidities']);
        // Extract entire signature table - no end markers (it's the last section)
        $signatureBlock = $section(['Signature of Consultant'], []);
        $reviewAfterRaw = trim((string) ($instructionRow['review_after'] ?? ''));
        $reviewAfter = $reviewAfterRaw !== ''
            ? '<div class="discharge-footer"><strong>Review After:</strong> ' . esc($reviewAfterRaw) . '</div>'
            : '';
        $dischargeStatusHeading = '<h1 class="discharge-title">' . esc($dischargeStatus !== '' ? $dischargeStatus : 'Discharge Summary') . '</h1>';
        
        // Extract individual instruction fields for template flexibility
        $otherAdvice = $section(['Other Advice:']);
        $followUpInstructions = $section(['Discharge Summary:']);

        // Heading-only fragments are treated as empty and should fallback to DB-backed meta values.
        $otherAdviceHeadingOnly = trim(strip_tags((string) $otherAdvice));
        if (preg_match('/^Other\s+Advice\s*:?$/i', $otherAdviceHeadingOnly) === 1) {
            $otherAdvice = '';
        }

        if ($otherAdvice === '') {
            $otherAdviceRaw = trim((string) ($instructionMeta['other_text'] ?? ''));
            if ($otherAdviceRaw === ''
                && $this->tableHasColumns('ipd_discharge_drug_food_interaction', ['ipd_id'])
                && $this->db->fieldExists('food_text', 'ipd_discharge_drug_food_interaction')) {
                $legacyFoodRow = $this->firstRowByIpd('ipd_discharge_drug_food_interaction', $ipdId);
                $otherAdviceRaw = trim((string) ($legacyFoodRow['food_text'] ?? ''));
            }

            if ($otherAdviceRaw !== '') {
                $otherAdvice = '<div class="discharge-field"><strong>Other Advice:</strong> '
                    . $this->renderStoredHtmlFragment($otherAdviceRaw)
                    . '</div>';
            }
        }

        // Ignore heading-only extraction (no real follow-up narrative text).
        $followUpHeadingOnly = trim(strip_tags((string) $followUpInstructions));
        if (preg_match('/^Discharge\s+Summary\s*:?$/i', $followUpHeadingOnly) === 1) {
            $followUpInstructions = '';
        }

        // Avoid duplicate output when templates contain both summary/advice placeholders
        // and both resolve to the same narrative.
        $normalizeText = static function (string $html): string {
            $text = trim(strip_tags($html));
            return (string) preg_replace('/\s+/u', ' ', $text);
        };
        if ($followUpInstructions !== ''
            && $clinicalSummary !== ''
            && $normalizeText((string) $followUpInstructions) !== ''
            && strcasecmp($normalizeText((string) $followUpInstructions), $normalizeText((string) $clinicalSummary)) === 0) {
            $followUpInstructions = '';
        }

        // Fallback for templates/data where clinical summary body is present under
        // "Discharge Summary:" subsection instead of the main summary block.
        $clinicalSummaryText = trim(strip_tags((string) $clinicalSummary));
        $followUpText = trim(strip_tags((string) $followUpInstructions));
        if (($clinicalSummaryText === '' || strcasecmp($clinicalSummaryText, 'Discharge Summary') === 0) && $followUpText !== '') {
            $clinicalSummary = $followUpInstructions;
        }

        // Fallback to master section tables when cached HTML extraction is empty.
        $surgeryText = trim(strip_tags((string) $surgery));
        if ($surgeryText === '') {
            $surgeryRows = $this->byIpdRows('ipd_discharge_surgery', ['surgery_name', 'surgery_date'], 'id ASC', $ipdId);
            $surgery = $this->buildNamedDateSection('Surgery', $surgeryRows, 'surgery_name', 'surgery_date', 'Date of Surgery');
        }

        $procedureText = trim(strip_tags((string) $procedure));
        if ($procedureText === '') {
            $procedureRows = $this->byIpdRows('ipd_discharge_procedure', ['procedure_name', 'procedure_date'], 'id ASC', $ipdId);
            $procedure = $this->buildNamedDateSection('Procedure', $procedureRows, 'procedure_name', 'procedure_date', 'Date of Procedure');
        }

        $complaints = $this->byIpdRows('ipd_discharge_complaint', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $complaintRemark = $this->firstRowByIpd('ipd_discharge_complaint_remark', $ipdId);
        $complaintRemarkText = $this->sanitizeComplaintNarrativeRemark(
            $this->normalizeRichText((string) ($complaintRemark['comp_remark'] ?? ''))
        );

        $presentingComplaintsText = trim(strip_tags((string) $presentingComplaints));
        if ($presentingComplaintsText === '') {
            $presentingComplaints = $this->buildNarrativeSection(
                'Presenting Complaints and Reason for Admission',
                $complaints,
                $complaintRemarkText
            );
        } elseif ($complaintRemarkText !== '') {
            // Cached/templated section may miss the narrative editor text. Force-append when absent.
            $existingPlain = (string) preg_replace('/\s+/u', ' ', trim(strip_tags((string) $presentingComplaints)));
            $remarkPlain = (string) preg_replace('/\s+/u', ' ', trim(strip_tags((string) $complaintRemarkText)));
            if ($remarkPlain !== '' && stripos($existingPlain, $remarkPlain) === false) {
                $presentingComplaints .= '<div class="discharge-field">'
                    . $this->renderRichText($complaintRemarkText)
                    . '</div>';
            }
        }

        $vars = [
            'DISCHARGE_STATUS' => $dischargeStatusHeading,
            // Main discharge summary from HTML editor (instruction_remark field)
            'DISCHARGE_SUMMARY' => $clinicalSummary,
            // Legacy alias for backward compatibility
            'CLINICAL_SUMMARY' => $clinicalSummary,
            
            // Clinical sections
            'FINAL_DIAGNOSIS' => $finalDiagnosis,
            'SURGERY' => $surgery,
            'PROCEDURE' => $procedure,
            'PERSONAL_HISTORY' => $personalHistory,
            'PRESENTING_COMPLAINTS' => $presentingComplaints,
            'GENERAL_EXAM_ADMISSION' => $generalExam,
            'CLINICAL_INVESTIGATION_REPORTS' => $clinicalInvestigations,
            'COURSE_IN_HOSPITAL' => $courseInHospital,
            'EXAMINATION_ON_DISCHARGE' => $examOnDischarge,
            
            // Discharge instructions and advice
            'PRESCRIBED_MEDICINES' => $dischargeMedications,
            'Prescribed_Medicines' => $dischargeMedications,
            'prescribed_medicines' => $dischargeMedications,
            'DISCHARGE_MEDICATIONS' => $dischargeMedications,
            'DIETARY_ADVICE' => $dietaryAdvice,
            'DISCHARGE_INSTRUCTIONS' => '',
            'REVIEW_AFTER' => $reviewAfter,
            'OTHER_ADVICE' => $otherAdvice,
            'FOLLOW_UP_INSTRUCTIONS' => $followUpInstructions,
            // Keep older placeholders mapped to follow-up section for backward compatibility.
            'DISCHARGE_ADVICE' => $followUpInstructions,
            'INSTRUCTION_REMARK' => $followUpInstructions,
            
            // Other sections
            'PAIN_MEASUREMENT_SCALE' => $painMeasurement,
            'DRUG_ALLERGY_ADR' => $drugAllergyAdr,
            'CO_MORBIDITIES' => $coMorbidities,
            'CLINICAL_HISTORY_RISK_PROFILE' => $this->buildClinicalHistoryRiskProfileSection($coMorbidities, $drugAllergyAdr, $personalHistory),
            'SIGNATURE_BLOCK' => '',
            // Legacy style aliases to ease migration from CI3-style template variables.
            'FinalDiagnosis' => $finalDiagnosis,
            'Surgery' => $surgery,
            'Procedure' => $procedure,
            'personal_history' => $personalHistory,
            'discharge_complaint' => $presentingComplaints,
            'discharge_general_exam' => $generalExam,
            'lab_test_content' => $clinicalInvestigations,
            'Course_in_the_hospital' => $courseInHospital,
            'discharge_exam_on_discharge' => $examOnDischarge,
            'Discharge_Medications' => $dischargeMedications,
            'diet_advice' => $dietaryAdvice,
            'Discharge_Instructions' => '',
            'Review_After' => $reviewAfter,
            'Pain_Measurement_Scale' => $painMeasurement,
            'Drug_Allergy_ADR' => $drugAllergyAdr,
            'Co_Morbidities' => $coMorbidities,
        ];

        return $vars;
    }

    private function extractDischargeSectionByMarkers(string $html, array $startNeedles, array $endNeedles): string
    {
        $startPos = null;
        foreach ($startNeedles as $needle) {
            $pos = stripos($html, (string) $needle);
            if ($pos !== false && ($startPos === null || $pos < $startPos)) {
                $startPos = $pos;
            }
        }

        if ($startPos === null) {
            return '';
        }

        $tagStart = strrpos(substr($html, 0, $startPos), '<');
        $sliceStart = $tagStart !== false ? $tagStart : $startPos;

        $sliceEnd = strlen($html);
        foreach ($endNeedles as $needle) {
            $needle = (string) $needle;
            if ($needle === '') {
                continue;
            }

            $isStartNeedle = false;
            foreach ($startNeedles as $startNeedle) {
                if (strcasecmp($needle, (string) $startNeedle) === 0) {
                    $isStartNeedle = true;
                    break;
                }
            }
            if ($isStartNeedle) {
                continue;
            }

            $candidate = stripos($html, $needle, $startPos + 1);
            if ($candidate !== false && $candidate < $sliceEnd) {
                $tag = strrpos(substr($html, 0, $candidate), '<');
                $sliceEnd = $tag !== false ? $tag : $candidate;
            }
        }

        if ($sliceEnd <= $sliceStart) {
            return '';
        }

        return trim(substr($html, $sliceStart, $sliceEnd - $sliceStart));
    }

    private function sectionHtmlToItems(string $html, array $labelHints = []): array
    {
        $value = trim($html);
        if ($value === '') {
            return [];
        }

        $value = (string) preg_replace('/<\s*br\s*\/?>/i', "\n", $value);
        $value = (string) preg_replace('/<\s*\/\s*(?:p|div|li|tr|h[1-6])\s*>/i', "\n", $value);
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\r\n?|\t/u', "\n", $text);
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $parts = preg_split('/[\n,;\|]+/u', $text) ?: [];
        $items = [];
        foreach ($parts as $part) {
            $item = trim((string) $part, " \t\n\r\0\x0B:-");
            if ($item === '') {
                continue;
            }

            foreach ($labelHints as $labelHint) {
                $pattern = '/^' . preg_quote((string) $labelHint, '/') . '\s*:?\s*/iu';
                $item = (string) preg_replace($pattern, '', $item);
            }

            $item = trim($item, " \t\n\r\0\x0B:-");
            if ($item === '') {
                continue;
            }

            $key = $this->normalizeHistoryItemKey($item);
            if ($key === '') {
                continue;
            }

            $items[$key] = $item;
        }

        return array_values($items);
    }

    private function normalizeHistoryItemKey(string $item): string
    {
        $value = trim($item);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $value = (string) mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }

        // Keep letters/digits across languages so Hindi and other Unicode scripts
        // participate in dedupe instead of being dropped as empty keys.
        $value = (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $value);

        if ($value === '') {
            // Stable fallback key for symbol-only or unusual content.
            $value = md5(trim($item));
        }

        return $value;
    }

    private function buildClinicalHistoryRiskProfileSection(string $coMorbiditiesHtml, string $drugAllergyHtml, string $personalHistoryHtml): string
    {
        $coMorbidityItems = $this->sectionHtmlToItems($coMorbiditiesHtml, ['Co-Morbidities']);
        $allergyItems = $this->sectionHtmlToItems($drugAllergyHtml, ['Drug Allergy / ADR', 'Drug Allergy', 'ADR History']);
        $personalItems = $this->sectionHtmlToItems($personalHistoryHtml, ['Personal History']);

        $occupiedKeys = [];
        foreach (array_merge($coMorbidityItems, $allergyItems) as $item) {
            $occupiedKeys[$this->normalizeHistoryItemKey($item)] = true;
        }

        $filteredPersonal = [];
        foreach ($personalItems as $item) {
            $key = $this->normalizeHistoryItemKey($item);
            if ($key === '' || isset($occupiedKeys[$key])) {
                continue;
            }

            $filteredPersonal[] = $item;
        }

        if (empty($coMorbidityItems) && empty($allergyItems) && empty($filteredPersonal)) {
            return '';
        }

        $html = '<h4 class="discharge-section-heading">Clinical History and Risk Profile</h4>';
        if (! empty($coMorbidityItems)) {
            $html .= '<div><strong>Comorbidities:</strong> ' . esc(implode(', ', $coMorbidityItems)) . '</div>';
        }
        if (! empty($allergyItems)) {
            $html .= '<div><strong>Drug Allergy and ADR History:</strong> ' . esc(implode(', ', $allergyItems)) . '</div>';
        }
        if (! empty($filteredPersonal)) {
            $html .= '<div><strong>Lifestyle and Personal History:</strong> ' . esc(implode(', ', $filteredPersonal)) . '</div>';
        }

        return $html;
    }

    private function trimDietaryAdviceTail(string $html): string
    {
        $out = trim($html);
        if ($out === '') {
            return '';
        }

        // In many legacy cached discharge contents, "Review after ..." and
        // a trailing signature box appear immediately after dietary advice
        // without a dedicated section marker. Trim that tail from this token.
        $out = (string) preg_replace('/Review\s+after[\s\S]*$/iu', '', $out);

        return trim($out);
    }

    private function normalizeLegacyDischargeTemplate(string $html): string
    {
        $out = str_replace(["\r\n", "\r"], "\n", $html);

        $mapPatterns = [
            '/<\?=\s*H_Name\s*\?>/i' => '{{H_Name}}',
            '/<\?=\s*H_address_1\s*\?>/i' => '{{H_address_1}}',
            '/<\?=\s*H_address_2\s*\?>/i' => '{{H_address_2}}',
            '/<\?=\s*H_phone_No\s*\?>/i' => '{{H_phone_No}}',
            '/<\?=\s*H_Email\s*\?>/i' => '{{H_Email}}',
            '/<\?=\s*H_logo\s*\?>/i' => '{{H_logo}}',
            '/<\?=\s*\$content\s*\?>/i' => '{{CONTENT}}',
            '/<\?=\s*\$content\s*;?\s*\?>/i' => '{{CONTENT}}',
        ];

        foreach ($mapPatterns as $pattern => $replacement) {
            $out = (string) preg_replace($pattern, $replacement, $out);
        }

        $out = (string) preg_replace('/<\?(?:php|=)[\s\S]*?\?>/i', '', $out);

        return trim($out);
    }

    private function applyDischargeTemplateTokens(string $templateHtml, array $vars): string
    {
        if ($templateHtml === '') {
            return '';
        }

        $templateHtml = $this->normalizeLegacyDischargeTemplate($templateHtml);
        $templateHtml = $this->normalizeDischargeTemplatePlaceholders($templateHtml);

        $rendered = (string) preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}|\{\s*([A-Za-z0-9_]+)\s*\}/',
            static function (array $m) use ($vars): string {
                $token = (string) (($m[1] ?? '') !== '' ? $m[1] : ($m[2] ?? ''));
                if ($token === '') {
                    return (string) ($m[0] ?? '');
                }

                $lower = strtolower($token);
                $candidates = [
                    $token,
                    strtoupper($token),
                    $lower,
                    ucfirst($lower),
                ];

                foreach ($candidates as $candidate) {
                    if (array_key_exists($candidate, $vars)) {
                        return (string) ($vars[$candidate] ?? '');
                    }
                }

                return (string) ($m[0] ?? '');
            },
            $templateHtml
        );

        return $rendered;
    }

    private function normalizeDischargeTemplatePlaceholders(string $templateHtml): string
    {
        $out = $templateHtml;

        // Canonicalize duplicate aliases so templates use one preferred token.
        $aliasToCanonical = [
            'H_Name' => 'hospital_name',
            'H_phone_No' => 'hospital_phone',
            'H_Email' => 'hospital_email',
            'ADMIT_DATE_ONLY' => 'ADMIT_DATE',
            'DISCHARGE_DATE_ONLY' => 'DISCHARGE_DATE',
            'ADMIT_TIME' => 'ADMISSION_TIME',
            'CLINICAL_SUMMARY' => 'DISCHARGE_SUMMARY',
            'DISCHARGE_INSTRUCTIONS' => 'REVIEW_AFTER',
        ];
        foreach ($aliasToCanonical as $alias => $canonical) {
            $out = (string) preg_replace('/\{\{?\s*' . preg_quote($alias, '/') . '\s*\}?\}/i', '{{' . $canonical . '}}', $out);
        }

        // If summary placeholder is wrapped inside heading tags, convert to block container.
        $out = (string) preg_replace(
            '/<h[1-6][^>]*>\s*(\{\{?\s*(?:CLINICAL_SUMMARY|DISCHARGE_SUMMARY)\s*\}?\})\s*<\/h[1-6]>/i',
            '<div class="discharge-summary-block">$1</div>',
            $out
        );

        // Keep only one main summary placeholder; extra occurrences cause duplicate output.
        $seenSummary = false;
        $out = (string) preg_replace_callback(
            '/\{\{?\s*(CLINICAL_SUMMARY|DISCHARGE_SUMMARY)\s*\}?\}/i',
            static function (array $m) use (&$seenSummary): string {
                if ($seenSummary) {
                    return '';
                }

                $seenSummary = true;
                return '{{DISCHARGE_SUMMARY}}';
            },
            $out
        );

        return $out;
    }

    private function applyDischargeTemplate(string $content, array $panelData, ?int $requestedTemplateId = null): array
    {
        $templates = $this->getDischargeTemplateRows();

        $selectedTemplate = null;
        if ($requestedTemplateId !== null && $requestedTemplateId > 0) {
            foreach ($templates as $row) {
                if ((int) ($row['id'] ?? 0) === $requestedTemplateId) {
                    $selectedTemplate = $row;
                    break;
                }
            }
        }

        if ($selectedTemplate === null) {
            foreach ($templates as $row) {
                if ((int) ($row['is_default'] ?? 0) === 1 && ! $this->isDischargeAuditTemplate($row)) {
                    $selectedTemplate = $row;
                    break;
                }
            }
        }

        if ($selectedTemplate === null && ! empty($templates)) {
            $selectedTemplate = $templates[0];
        }

        $templateHtml = (string) ($selectedTemplate['template_html'] ?? '{{CONTENT}}');
        if (trim($templateHtml) === '') {
            $templateHtml = '{{CONTENT}}';
        }

        // Normalize template syntax early so downstream placeholder checks operate
        // on a consistent token format (legacy PHP placeholders, casing, spacing).
        $templateHtml = $this->normalizeLegacyDischargeTemplate($templateHtml);
        $templateHtml = $this->normalizeDischargeTemplatePlaceholders($templateHtml);

        $tokenVars = $this->buildDischargeTemplateTokenVars($panelData, $content);

        $historyPanelHtml = trim((string) ($tokenVars['CLINICAL_HISTORY_RISK_PROFILE'] ?? ''));
        if ($historyPanelHtml !== '') {
            $historyTokens = ['PERSONAL_HISTORY', 'CO_MORBIDITIES', 'DRUG_ALLERGY_ADR'];
            $firstToken = null;
            $firstPos = null;
            foreach ($historyTokens as $token) {
                if (preg_match('/\{\{?\s*' . preg_quote($token, '/') . '\s*\}?\}/i', $templateHtml, $m, PREG_OFFSET_CAPTURE) === 1) {
                    $pos = (int) ($m[0][1] ?? 0);
                    if ($firstPos === null || $pos < $firstPos) {
                        $firstPos = $pos;
                        $firstToken = $token;
                    }
                }
            }

            if ($firstToken !== null) {
                foreach ($historyTokens as $token) {
                    $tokenVars[$token] = '';
                }
                $tokenVars[$firstToken] = $historyPanelHtml;
            }
        }

        $templateHasMainSummaryToken = preg_match('/\{\{\s*DISCHARGE_SUMMARY\s*\}\}|\{\s*DISCHARGE_SUMMARY\s*\}/i', $templateHtml) === 1;
        if ($templateHasMainSummaryToken) {
            $tokenVars['DISCHARGE_ADVICE'] = '';
            $tokenVars['INSTRUCTION_REMARK'] = '';
        }

        $patientName = html_entity_decode((string) ($tokenVars['PATIENT_NAME'] ?? ''), ENT_QUOTES, 'UTF-8');
        $patientCode = html_entity_decode((string) ($tokenVars['UHID'] ?? ''), ENT_QUOTES, 'UTF-8');
        $ipdCode = html_entity_decode((string) ($tokenVars['IPD_CODE'] ?? ''), ENT_QUOTES, 'UTF-8');

        if ($requestedTemplateId === null && $this->shouldUseContentOnlyTemplate($content, $templateHtml)) {
            $templateHtml = '{{CONTENT}}';
        }

        // If template is section-driven, ignore raw CONTENT token to avoid duplicate blocks.
        if ($this->templateHasDischargeSectionPlaceholders($templateHtml)) {
            $templateHtml = (string) preg_replace('/\{\{?\s*content\s*\}?\}/i', '', $templateHtml);
        }

        $content = $this->stripLegacyTopSummaryFromContent($content, $templateHtml, $patientName, $patientCode, $ipdCode);

        $templateSettings = array_merge($this->defaultDischargeTemplateSettings(), [
            'page_size' => strtoupper(trim((string) ($selectedTemplate['page_size'] ?? 'A4'))),
            'custom_width_mm' => (int) ($selectedTemplate['custom_width_mm'] ?? 210),
            'custom_height_mm' => (int) ($selectedTemplate['custom_height_mm'] ?? 297),
            'page_margin_top_cm' => (float) ($selectedTemplate['page_margin_top_cm'] ?? 0.8),
            'page_margin_bottom_cm' => (float) ($selectedTemplate['page_margin_bottom_cm'] ?? 0.8),
            'page_margin_left_cm' => (float) ($selectedTemplate['page_margin_left_cm'] ?? 0.8),
            'page_margin_right_cm' => (float) ($selectedTemplate['page_margin_right_cm'] ?? 0.8),
            'margin_header_cm' => (float) ($selectedTemplate['margin_header_cm'] ?? 0.5),
            'margin_footer_cm' => (float) ($selectedTemplate['margin_footer_cm'] ?? 0.5),
            'header_html' => (string) ($selectedTemplate['header_html'] ?? ''),
            'footer_html' => (string) ($selectedTemplate['footer_html'] ?? ''),
            'template_css' => (string) ($selectedTemplate['template_css'] ?? ''),
            'watermark_type' => (string) ($selectedTemplate['watermark_type'] ?? 'none'),
            'watermark_text' => (string) ($selectedTemplate['watermark_text'] ?? ''),
            'watermark_image' => (string) ($selectedTemplate['watermark_image'] ?? ''),
            'watermark_alpha' => (float) ($selectedTemplate['watermark_alpha'] ?? 0.12),
        ]);

        $rendered = $this->applyDischargeTemplateTokens($templateHtml, $tokenVars);
        $rendered = $this->dedupeRepeatedClinicalSummary($rendered, (string) ($tokenVars['DISCHARGE_SUMMARY'] ?? ''));

        return [
            'rendered_html' => $rendered,
            'templates' => $templates,
            'selected_template_id' => (int) ($selectedTemplate['id'] ?? 0),
            'selected_template_name' => (string) ($selectedTemplate['template_name'] ?? ''),
            'selected_template_settings' => $templateSettings,
        ];
    }

    private function dedupeRepeatedClinicalSummary(string $renderedHtml, string $clinicalSummaryHtml): string
    {
        $renderedHtml = (string) $renderedHtml;
        $clinicalSummaryHtml = trim((string) $clinicalSummaryHtml);
        if ($renderedHtml === '' || $clinicalSummaryHtml === '') {
            return $renderedHtml;
        }

        $firstPos = strpos($renderedHtml, $clinicalSummaryHtml);
        if ($firstPos === false) {
            return $renderedHtml;
        }

        $offset = $firstPos + strlen($clinicalSummaryHtml);
        while (true) {
            $nextPos = strpos($renderedHtml, $clinicalSummaryHtml, $offset);
            if ($nextPos === false) {
                break;
            }

            $renderedHtml = substr_replace($renderedHtml, '', $nextPos, strlen($clinicalSummaryHtml));
            $offset = $nextPos;
        }

        return $renderedHtml;
    }

    private function shouldUseContentOnlyTemplate(string $content, string $templateHtml): bool
    {
        $content = trim($content);
        if ($content === '') {
            return false;
        }

        // Respect configured DB templates. Only fall back to content-only mode
        // when the template is effectively a plain CONTENT wrapper.
        $normalizedTemplate = $this->normalizeLegacyDischargeTemplate($templateHtml);
        $normalizedTemplate = $this->normalizeDischargeTemplatePlaceholders($normalizedTemplate);
        $templateWithoutContent = (string) preg_replace('/\{\{\s*CONTENT\s*\}\}|\{\s*CONTENT\s*\}/i', '', $normalizedTemplate);
        $templateWithoutContent = trim(strip_tags($templateWithoutContent));
        if ($templateWithoutContent !== '') {
            return false;
        }

        // If the template explicitly places section placeholders, preserve that order.
        if ($this->templateHasDischargeSectionPlaceholders($templateHtml)) {
            return false;
        }

        $templateHasMeta = preg_match('/\{\{\s*(PATIENT_NAME|UHID|IPD_CODE|ADMIT_DATE|DISCHARGE_DATE)\s*\}\}|\{\s*(PATIENT_NAME|UHID|IPD_CODE|ADMIT_DATE|DISCHARGE_DATE)\s*\}/i', $templateHtml) === 1;

        if (! $templateHasMeta) {
            return false;
        }

        $scan = substr($content, 0, 5000);
        if ($scan === false) {
            $scan = $content;
        }

        $hasHeading = stripos($scan, 'Discharge Summary') !== false;
        $hasPatientGrid = stripos($scan, 'IPD No.') !== false || stripos($scan, 'Admission') !== false;
        $hasClinicalBody = stripos($scan, 'Final Diagnosis') !== false || stripos($scan, 'Course in the Hospital') !== false;

        return $hasHeading && $hasPatientGrid && $hasClinicalBody;
    }

    private function templateHasDischargeSectionPlaceholders(string $templateHtml): bool
    {
        $templateHtml = trim($templateHtml);
        if ($templateHtml === '') {
            return false;
        }

        $sectionTokens = [
            'CLINICAL_SUMMARY',
            'DISCHARGE_SUMMARY',
            'FOLLOW_UP_INSTRUCTIONS',
            'FINAL_DIAGNOSIS',
            'SURGERY',
            'PROCEDURE',
            'PERSONAL_HISTORY',
            'PRESENTING_COMPLAINTS',
            'PAIN_MEASUREMENT_SCALE',
            'GENERAL_EXAM_ADMISSION',
            'CLINICAL_INVESTIGATION_REPORTS',
            'COURSE_IN_HOSPITAL',
            'EXAMINATION_ON_DISCHARGE',
            'DRUG_ALLERGY_ADR',
            'CO_MORBIDITIES',
            'DISCHARGE_MEDICATIONS',
            'DIETARY_ADVICE',
            'OTHER_ADVICE',
            'REVIEW_AFTER',
            'DISCHARGE_INSTRUCTIONS',
            'SIGNATURE_BLOCK',
        ];

        foreach ($sectionTokens as $token) {
            if (preg_match('/\{\{\s*' . preg_quote($token, '/') . '\s*\}\}/i', $templateHtml) === 1
                || preg_match('/\{\s*' . preg_quote($token, '/') . '\s*\}/i', $templateHtml) === 1) {
                return true;
            }
        }

        return false;
    }

    private function stripLegacyTopSummaryFromContent(
        string $content,
        string $templateHtml,
        string $patientName,
        string $patientCode,
        string $ipdCode
    ): string {
        $content = trim($content);
        if ($content === '') {
            return $content;
        }

        // Only clean legacy duplicate headers when template itself already prints patient/meta area.
        $templateHasMeta = strpos($templateHtml, '{{PATIENT_NAME}}') !== false
            || strpos($templateHtml, '{{UHID}}') !== false
            || strpos($templateHtml, '{{IPD_CODE}}') !== false
            || strpos($templateHtml, '{{ADMIT_DATE}}') !== false
            || strpos($templateHtml, '{{DISCHARGE_DATE}}') !== false;

        if (! $templateHasMeta) {
            return $content;
        }

        $scanHead = substr($content, 0, 3000);
        if ($scanHead === false) {
            $scanHead = $content;
        }

        $hasPatientMarker = $patientName !== '' && stripos($scanHead, $patientName) !== false;
        $hasUhidMarker = $patientCode !== '' && stripos($scanHead, $patientCode) !== false;
        $hasIpdMarker = $ipdCode !== '' && stripos($scanHead, $ipdCode) !== false;
        $hasDischargeHeading = stripos($scanHead, 'Discharge Summary') !== false;
        $hasDemographicGrid = stripos($scanHead, 'UHID') !== false
            && (stripos($scanHead, 'IPD No.') !== false || stripos($scanHead, 'IPD') !== false)
            && (stripos($scanHead, 'Admission') !== false || stripos($scanHead, 'Discharge') !== false)
            && stripos($scanHead, '<table') !== false;

        if ((! $hasDischargeHeading && ! $hasDemographicGrid) || (! $hasPatientMarker && ! $hasUhidMarker && ! $hasIpdMarker)) {
            return $content;
        }

        // Remove one legacy heading + first demographic summary table block if present at top.
        $pattern = '/^\s*(?:<h[1-6][^>]*>\s*discharge\s*summary\s*<\/h[1-6]>\s*)?'
            . '(?:(?:<hr\b[^>]*>\s*)?<table\b[^>]*>.*?<\/table>\s*){1,2}/is';
        $cleaned = preg_replace($pattern, '', $content, 1);
        if (is_string($cleaned) && trim($cleaned) !== '') {
            return trim($cleaned);
        }

        // Fallback for legacy plain-text/fragmented blocks: cut everything before first
        // known clinical section heading when duplicate demographics are detected.
        $sectionMarkers = [
            'Final Diagnosis',
            'Presenting Complaints',
            'Pain Measurement Scale',
            'General Examination on Admission',
            'Clinical Investigation Reports',
            'Course in the hospital',
            'Examination on Discharge',
            'Surgery',
            'Procedure',
            'Personal History',
            'Drug Allergy / ADR',
            'Co-Morbidities',
            'Discharge Medications',
            'Dietary Advice',
            'Discharge Advice/Instructions/Summary',
        ];

        $firstSectionPos = null;
        foreach ($sectionMarkers as $marker) {
            $pos = stripos($content, $marker);
            if ($pos !== false && ($firstSectionPos === null || $pos < $firstSectionPos)) {
                $firstSectionPos = $pos;
            }
        }

        if ($firstSectionPos !== null && $firstSectionPos > 120) {
            $trimmed = trim((string) substr($content, $firstSectionPos));
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return $content;
    }

    private function currentUserLabel(): string
    {
        if (! function_exists('auth')) {
            return 'system';
        }

        $user = auth()->user();
        if (! $user) {
            return 'system';
        }

        $name = (string) ($user->username ?? $user->email ?? '');
        if ($name !== '') {
            return $name;
        }

        return 'user-' . (string) ($user->id ?? 0);
    }

    private function getDischargeDoctorNames($ipd): string
    {
        if (! $ipd) {
            return '';
        }

        // Try r_doc_id field first (referring doctor)
        $docList = '';
        $singleDocId = (int) ($ipd->r_doc_id ?? $ipd->doc_list ?? 0);
        if ($singleDocId > 0) {
            $docList = (string) $singleDocId;
        } else {
            // Fallback: try other doctor fields
            $singleDocId = (int) ($ipd->doctor_id ?? $ipd->doc_id ?? $ipd->consultant_id ?? 0);
            if ($singleDocId > 0) {
                $docList = (string) $singleDocId;
            } else {
                return '';
            }
        }

        if (! $this->db->tableExists('doctor_master')) {
            return '';
        }

        $docIds = array_filter(array_map('intval', explode(',', $docList)));
        if (empty($docIds)) {
            return '';
        }

        // Simplified query to avoid GROUP_CONCAT issues
        $doctors = $this->db->table('doctor_master d')
            ->select('d.id, d.p_fname, d.p_title')
            ->whereIn('d.id', $docIds)
            ->get()
            ->getResult();

        if (empty($doctors)) {
            return '';
        }

        $names = [];
        foreach ($doctors as $doc) {
            $docId = (int) ($doc->id ?? 0);
            $title = trim((string) ($doc->p_title ?? ''));
            $name = trim((string) ($doc->p_fname ?? ''));

            if ($name === '') {
                continue;
            }

            // Get specializations separately
            $specs = [];
            if ($this->db->tableExists('doc_spec') && $this->db->tableExists('med_spec')) {
                $specRows = $this->db->table('doc_spec s')
                    ->select('m.SpecName')
                    ->join('med_spec m', 's.med_spec_id = m.id', 'inner')
                    ->where('s.doc_id', $docId)
                    ->get()
                    ->getResult();

                foreach ($specRows as $specRow) {
                    $specName = trim((string) ($specRow->SpecName ?? ''));
                    if ($specName !== '') {
                        $specs[] = $specName;
                    }
                }
            }

            $fullName = ($title !== '' ? $title . ' ' : 'Dr. ') . $name;
            if (! empty($specs)) {
                $fullName .= ' [' . implode(', ', $specs) . ']';
            }
            $names[] = $fullName;
        }

        return implode(', ', $names);
    }

    private function getDischargeStatusText(?object $ipd): string
    {
        if (! $ipd) {
            return 'Discharge Summary';
        }

        $ipdStatus = (int) ($ipd->discarge_patient_status ?? ($ipd->discharge_status ?? ($ipd->ipd_status ?? 0)));

        // If status is 0 (Status Pending), show "Discharge Summary" only
        if ($ipdStatus === 0) {
            return 'Discharge Summary';
        }

        $statusTable = '';
        if ($this->db->tableExists('ipd_discharg_status')) {
            $statusTable = 'ipd_discharg_status';
        } elseif ($this->db->tableExists('ipd_discharge_status')) {
            $statusTable = 'ipd_discharge_status';
        }

        if ($statusTable === '') {
            return 'Discharge Summary';
        }

        $row = $this->db->table($statusTable)
            ->select('status_desc, status_details')
            ->where('id', $ipdStatus)
            ->get(1)
            ->getRowArray();

        if (empty($row)) {
            return 'Discharge Summary';
        }

        // Use status_details field (e.g., "Discharge Summary", "Leave Against Medical Advice", "Dead Summary")
        $statusText = trim((string) ($row['status_details'] ?? ''));
        if ($statusText === '') {
            // Fallback to status_desc if status_details is empty
            $statusText = trim((string) ($row['status_desc'] ?? ''));
        }

        return $statusText !== '' ? $statusText : 'Discharge Summary';
    }

    private function saveDischargeContent(int $ipdId, string $content): bool
    {
        try {
            if ($this->db->tableExists('ipd_master') && $this->db->fieldExists('ipd_create', 'ipd_master')) {
                $this->db->table('ipd_master')
                    ->where('id', $ipdId)
                    ->update(['ipd_create' => 1]);
            }

            if (! $this->db->tableExists('ipd_discharge') || ! $this->db->fieldExists('content', 'ipd_discharge')) {
                return false;
            }

            $builder = $this->db->table('ipd_discharge');
            $existing = $builder
                ->where('ipd_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($existing['id'])) {
                return (bool) $builder
                    ->where('id', (int) $existing['id'])
                    ->update(['content' => $content]);
            }

            $insert = [
                'ipd_id' => $ipdId,
                'content' => $content,
            ];

            // Legacy schema has NOT NULL columns without defaults.
            $userLabel = substr($this->currentUserLabel(), 0, 50);
            if ($this->db->fieldExists('created_by', 'ipd_discharge')) {
                $insert['created_by'] = $userLabel;
            }
            if ($this->db->fieldExists('checked_by', 'ipd_discharge')) {
                $insert['checked_by'] = $userLabel;
            }
            if ($this->db->fieldExists('created_datetime', 'ipd_discharge')) {
                $insert['created_datetime'] = date('Y-m-d H:i:s');
            }
            if ($this->db->fieldExists('ipd_discharge_print', 'ipd_discharge')) {
                $insert['ipd_discharge_print'] = 0;
            }

            return (bool) $builder->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Discharge content save failed for IPD {ipd}: {msg}', [
                'ipd' => $ipdId,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! $this->db->fieldExists($column, $table)) {
                return false;
            }
        }

        return true;
    }

    private function getCurrentUserId(): int
    {
        if (! function_exists('auth')) {
            return 0;
        }

        $user = auth()->user();
        if (! $user) {
            return 0;
        }

        return max(0, (int) ($user->id ?? 0));
    }

    private function isNarrativeTemplateSectionAllowed(string $section): bool
    {
        return in_array($section, ['diagnosis_remark', 'inhos_remark', 'course_remark', 'instruction_other', 'instruction_remark'], true);
    }

    private function narrativeSectionTable(string $section): ?string
    {
        $map = [
            'diagnosis_remark' => 'ipd_discharge_diagnosis_remark',
            'course_remark' => 'ipd_discharge_course_remark',
        ];

        return $map[$section] ?? null;
    }

    private function ensureDischargeNarrativeTemplateTable(): bool
    {
        if ($this->db->tableExists('ipd_discharge_narrative_templates')) {
            return true;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS ipd_discharge_narrative_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_id INT NOT NULL DEFAULT 0,
                section_key VARCHAR(80) NOT NULL,
                template_name VARCHAR(120) NOT NULL,
                template_text LONGTEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY uniq_doc_section_name (doc_id, section_key, template_name),
                INDEX idx_section_doc (section_key, doc_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->db->tableExists('ipd_discharge_narrative_templates');
    }

    private function getPatientIdFromIpd(int $ipdId): int
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns('ipd_master', ['id', 'p_id'])) {
            return 0;
        }

        $row = $this->db->table('ipd_master')
            ->select('p_id')
            ->where('id', $ipdId)
            ->get(1)
            ->getRowArray();

        return max(0, (int) ($row['p_id'] ?? 0));
    }

    public function section_past_data()
    {
        $section = trim((string) $this->request->getGet('section'));
        $ipdId = (int) $this->request->getGet('ipd_id');

        if ($ipdId <= 0 || ! $this->isNarrativeTemplateSectionAllowed($section)) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'Past data not found']);
        }

        $table = $this->narrativeSectionTable($section);
        if ($table === null || ! $this->tableHasColumns($table, ['ipd_id', 'comp_remark'])) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'Past data column missing']);
        }

        $patientId = $this->getPatientIdFromIpd($ipdId);
        if ($patientId <= 0) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'Patient not found']);
        }

        $row = $this->db->table($table . ' r')
            ->select('r.comp_remark as section_text,r.ipd_id')
            ->join('ipd_master m', 'm.id = r.ipd_id', 'inner')
            ->where('m.p_id', $patientId)
            ->where('r.ipd_id !=', $ipdId)
            ->where('r.comp_remark IS NOT NULL', null, false)
            ->where('r.comp_remark !=', '')
            ->orderBy('r.id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (empty($row)) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'No past data found']);
        }

        return $this->response->setJSON([
            'update' => 1,
            'past_text' => (string) ($row['section_text'] ?? ''),
            'past_ipd_id' => (int) ($row['ipd_id'] ?? 0),
            'error_text' => 'Past data loaded',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function section_template_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $section = trim((string) $this->request->getPost('section'));
        $templateId = max(0, (int) ($this->request->getPost('template_id') ?? 0));
        $templateName = trim((string) $this->request->getPost('template_name'));
        $templateText = trim((string) $this->request->getPost('template_text'));
        $templateScope = strtolower(trim((string) $this->request->getPost('template_scope')));

        if ($templateName === '' || $templateText === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template name and text are required']);
        }

        if (! $this->isNarrativeTemplateSectionAllowed($section)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unsupported section']);
        }

        if (! $this->ensureDischargeNarrativeTemplateTable()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to access template storage']);
        }

        $docId = $templateScope === 'master' ? 0 : $this->getCurrentUserId();
        $table = $this->db->table('ipd_discharge_narrative_templates');
        if ($templateId > 0) {
            $existing = $table
                ->where('id', $templateId)
                ->where('doc_id', $docId)
                ->where('section_key', $section)
                ->get(1)
                ->getRowArray();
            if (empty($existing)) {
                return $this->response->setJSON(['update' => 0, 'error_text' => 'Template not found']);
            }
        } else {
            $existing = $table
                ->where('doc_id', $docId)
                ->where('section_key', $section)
                ->where('template_name', $templateName)
                ->get(1)
                ->getRowArray();
        }

        $now = date('Y-m-d H:i:s');
        if (! empty($existing)) {
            $table->where('id', (int) ($existing['id'] ?? 0))->update([
                'template_name' => $templateName,
                'template_text' => $templateText,
                'is_active' => 1,
                'updated_at' => $now,
            ]);
            $templateId = (int) ($existing['id'] ?? 0);
        } else {
            $table->insert([
                'doc_id' => $docId,
                'section_key' => $section,
                'template_name' => $templateName,
                'template_text' => $templateText,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $templateId = (int) $this->db->insertID();
        }

        return $this->response->setJSON([
            'update' => 1,
            'id' => $templateId,
            'error_text' => $docId === 0 ? 'Master template saved' : 'My template saved',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function section_template_list()
    {
        $section = trim((string) $this->request->getGet('section'));
        if (! $this->isNarrativeTemplateSectionAllowed($section)) {
            return $this->response->setJSON(['rows' => []]);
        }

        if (! $this->ensureDischargeNarrativeTemplateTable()) {
            return $this->response->setJSON(['rows' => []]);
        }

        $docId = $this->getCurrentUserId();
        $rows = $this->db->table('ipd_discharge_narrative_templates')
            ->select('id,template_name,template_text,doc_id,section_key')
            ->where('section_key', $section)
            ->where('is_active', 1)
            ->groupStart()
            ->where('doc_id', $docId)
            ->orWhere('doc_id', 0)
            ->groupEnd()
            ->orderBy('doc_id', 'DESC')
            ->orderBy('template_name', 'ASC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function section_template_remove()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $templateId = max(0, (int) ($this->request->getPost('template_id') ?? 0));
        $section = trim((string) $this->request->getPost('section'));
        if ($templateId <= 0 || ! $this->isNarrativeTemplateSectionAllowed($section) || ! $this->ensureDischargeNarrativeTemplateTable()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template not found']);
        }

        $docId = $this->getCurrentUserId();
        $row = $this->db->table('ipd_discharge_narrative_templates')
            ->select('id')
            ->where('id', $templateId)
            ->where('section_key', $section)
            ->groupStart()
            ->where('doc_id', $docId)
            ->orWhere('doc_id', 0)
            ->groupEnd()
            ->get(1)
            ->getRowArray();

        if (empty($row)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template not found']);
        }

        $this->db->table('ipd_discharge_narrative_templates')
            ->where('id', $templateId)
            ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Template deactivated',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function byIpdRows(string $table, array $columns = ['*'], string $orderBy = 'id ASC', int $ipdId = 0): array
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns($table, ['ipd_id'])) {
            return [];
        }

        $builder = $this->db->table($table)->where('ipd_id', $ipdId);
        if ($columns !== ['*']) {
            $builder->select(implode(',', $columns));
        }

        if ($orderBy !== '') {
            $parts = explode(' ', trim($orderBy));
            $field = $parts[0] ?? 'id';
            $dir = strtoupper($parts[1] ?? 'ASC');
            $builder->orderBy($field, $dir === 'DESC' ? 'DESC' : 'ASC');
        }

        return $builder->get()->getResultArray();
    }

    private function safeDate(?string $dateValue): string
    {
        $value = trim((string) $dateValue);
        if ($value === '' || $value === '0000-00-00' || $value === '1901-01-01') {
            return '';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }

        return date('d-m-Y', $ts);
    }

    private function safeTime(?string $dateValue): string
    {
        $value = trim((string) $dateValue);
        if ($value === '' || $value === '00:00:00' || $value === '00:00') {
            return '';
        }

        // Try to parse as time (HH:MM or HH:MM:SS format)
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            
            // Validate hour and minute
            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                // Convert to 12-hour format with AM/PM
                $ampm = $hour >= 12 ? 'PM' : 'AM';
                $hour12 = $hour % 12;
                if ($hour12 === 0) {
                    $hour12 = 12;
                }
                return sprintf('%02d:%02d %s', $hour12, $minute, $ampm);
            }
        }

        // Fallback: try strtotime for datetime formats
        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }

        return date('h:i A', $ts);
    }

    /**
     * Load dose master rows from a dose table (opd_dose_shed, opd_dose_when, opd_dose_frequency).
     * Returns array of ['id' => int, 'label' => string, 'local_label' => string].
     */
    private function getDoseMasterRows(string $table): array
    {
        if (! $this->db->tableExists($table)) {
            return [];
        }

        $fields = $this->db->getFieldNames($table);
        $idFields = ($table === 'opd_dose_shed') ? ['dose_shed_id', 'id'] : (($table === 'opd_dose_when') ? ['dose_when_id', 'id'] : ['dose_freq_id', 'id']);
        $idField = null;
        foreach ($idFields as $candidate) {
            if (in_array($candidate, $fields, true)) {
                $idField = $candidate;
                break;
            }
        }
        if ($idField === null) {
            return [];
        }

        $labelField = null;
        $localLabelField = null;
        if ($table === 'opd_dose_shed') {
            foreach (['dose_show_sign', 'dose_sign', 'dose_sign_desc', 'name'] as $candidate) {
                if (in_array($candidate, $fields, true)) {
                    $labelField = $candidate;
                    break;
                }
            }
            foreach (['dose_show_desc', 'dose_sign_hindi', 'dose_sign_desc'] as $candidate) {
                if (in_array($candidate, $fields, true)) {
                    $localLabelField = $candidate;
                    break;
                }
            }
        } else {
            foreach (['dose_sign', 'dose_sign_desc', 'name'] as $candidate) {
                if (in_array($candidate, $fields, true)) {
                    $labelField = $candidate;
                    break;
                }
            }
            foreach (['dose_sign_hindi', 'dose_sign_desc'] as $candidate) {
                if (in_array($candidate, $fields, true)) {
                    $localLabelField = $candidate;
                    break;
                }
            }
        }

        if ($labelField === null) {
            return [];
        }

        $select = [$idField . ' as id', $labelField . ' as label'];
        if ($localLabelField !== null) {
            $select[] = $localLabelField . ' as local_label';
        }

        $rows = $this->db->table($table)
            ->select(implode(',', $select))
            ->where($labelField . ' !=', '')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) ($row['id'] ?? 0)] = [
                'id' => (int) ($row['id'] ?? 0),
                'label' => trim((string) ($row['label'] ?? '')),
                'local_label' => trim((string) ($row['local_label'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * Build an OPD-style medication table for the {{DISCHARGE_MEDICATIONS}} template placeholder.
     *
     * Output matches the OPD {{medical}} format:
     *   | # | Medicine (bold) + generic (small blue) | Directions (English) + Hindi (orange) |
     *
     * Data sources (in priority order):
     *   1. ipd_discharge_prescrption_prescribed  – structured prescription rows (dosage IDs)
     *   2. ipd_discharge_drug                    – legacy simple drug rows
     */
    private function buildOpdStyleMedicationsHtml(int $ipdId): string
    {
        if ($ipdId <= 0) {
            return '';
        }

        // ── 1. Try structured prescription rows ─────────────────────────────────
        $prescTable = $this->findFirstExistingTable([
            'ipd_discharge_prescrption_prescribed',
            'ipd_discharge_prescription_prescribed',
        ]);

        $medRows = $prescTable !== null
            ? $this->byIpdRows($prescTable, [
                'med_name', 'med_salt', 'med_type',
                'dosage', 'dosage_when', 'dosage_freq',
                'qty', 'no_of_days', 'remark',
              ], 'id ASC', $ipdId)
            : [];

        // ── 2. Fallback to legacy ipd_discharge_drug rows ───────────────────────
        $useLegacy = empty($medRows);
        if ($useLegacy) {
            $drugRows = $this->byIpdRows(
                'ipd_discharge_drug',
                ['drug_name', 'drug_dose', 'drug_day'],
                'id ASC',
                $ipdId
            );
            // Convert legacy rows to the same shape as prescription rows
            foreach ($drugRows as $dr) {
                $name = trim((string) ($dr['drug_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $medRows[] = [
                    'med_name'     => $name,
                    'med_salt'     => '',
                    'med_type'     => '',
                    'dosage'       => 0,
                    'dosage_when'  => 0,
                    'dosage_freq'  => 0,
                    'qty'          => '',
                    'no_of_days'   => trim((string) ($dr['drug_day'] ?? '')),
                    'remark'       => trim((string) ($dr['drug_dose'] ?? '')), // dose string goes to remark column
                ];
            }
        }

        if (empty($medRows)) {
            return '';
        }

        // ── Hindi lookup maps (mirrors OPD Opd.php maps) ────────────────────────
        $whenCodeDescMap = [
            'BF'  => 'BF (BEFORE FOOD)',  'AF'  => 'AF (AFTER FOOD)',
            'WF'  => 'WF (WITH FOOD)',    'ES'  => 'ES (EMPTY STOMACH)',
            'BBF' => 'BBF (BEFORE BREAKFAST)', 'ABF' => 'ABF (AFTER BREAKFAST)',
            'BL'  => 'BL (BEFORE LUNCH)', 'AL'  => 'AL (AFTER LUNCH)',
            'BD'  => 'BD (BEFORE DINNER)', 'AD'  => 'AD (AFTER DINNER)',
            'BT'  => 'BT (BED TIME)',
        ];
        $whenHindiMap = [
            'BF' => 'भोजन से पहले', 'BEFORE FOOD' => 'भोजन से पहले',
            'AF' => 'भोजन के बाद',  'AFTER FOOD'  => 'भोजन के बाद',
            'WF' => 'भोजन के साथ',  'WITH FOOD'   => 'भोजन के साथ',
            'ES' => 'सुबह खाली पेट', 'EMPTY STOMACH' => 'सुबह खाली पेट',
            'BBF'=> 'नाश्ते से पहले', 'BEFORE BREAKFAST' => 'नाश्ते से पहले',
            'ABF'=> 'नाश्ते के बाद',  'AFTER BREAKFAST'  => 'नाश्ते के बाद',
            'BL' => 'दोपहर के भोजन से पहले', 'BEFORE LUNCH' => 'दोपहर के भोजन से पहले',
            'AL' => 'दोपहर के भोजन के बाद',  'AFTER LUNCH'  => 'दोपहर के भोजन के बाद',
            'BD' => 'रात के भोजन से पहले',   'BEFORE DINNER'=> 'रात के भोजन से पहले',
            'AD' => 'रात के भोजन के बाद',    'AFTER DINNER' => 'रात के भोजन के बाद',
            'BT' => 'रात को सोते समय',       'BED TIME'     => 'रात को सोते समय',
        ];
        $freqHindiMap = [
            'OD'  => 'दिन में एक बार (OD)',   'BD'  => 'दिन में दो बार (BD)',
            'TDS' => 'दिन में तीन बार (TDS)', 'TID' => 'दिन में तीन बार (TID)',
            'QID' => 'दिन में चार बार (QID)', 'HS'  => 'रात को सोते समय (HS)',
            'SOS' => 'ज़रूरत पड़ने पर (SOS)',  'STAT'=> 'तुरंत एक बार (STAT)',
            'ALTERNATE DAY' => 'एक दिन छोड़कर',
            'DAILY'   => 'प्रतिदिन',
            'WEEKLY'  => 'हफ़्ते में एक बार',
            'MONTHLY' => 'महीने में एक बार',
        ];
        $remarkHindiMap = [
            'TAKE WITH MILK'                    => 'दूध के साथ लें',
            'TAKE WITH WARM WATER'              => 'गुनगुने पानी के साथ लें',
            'AVOID SOUR FOOD AND DAIRY PRODUCTS'=> 'खट्टा और डेयरी उत्पाद न लें',
            'TAKE AFTER MEALS'                  => 'भोजन के बाद लें',
            'TAKE ON AN EMPTY STOMACH EARLY MORNING' => 'सुबह खाली पेट लें',
            'CHEW WELL BEFORE SWALLOWING'       => 'चबाकर खाएं',
            'DISSOLVE IN HALF GLASS OF WATER'   => 'आधे गिलास पानी में घोलकर लें',
            'APPLY LOCALLY TWICE DAILY'         => 'दिन में दो बार लगाएं',
            'DO NOT CRUSH OR CHEW TABLET'       => 'गोली को तोड़े या चबाएं नहीं',
            'AVOID ALCOHOL WHILE TAKING THIS MEDICINE' => 'शराब का सेवन न करें',
            'COMPLETE FULL COURSE OF ANTIBIOTICS'      => 'एंटीबायोटिक का पूरा कोर्स लें',
            'DRINK PLENTY OF FLUIDS / WATER'    => 'प्रचुर मात्रा में पानी पिएं',
        ];
        $formulationPrefixMap = [
            'tablet' => 'TAB', 'tab' => 'TAB',
            'capsule'=> 'CAP', 'cap' => 'CAP',
            'syrup'  => 'SYP', 'syp' => 'SYP', 'syr' => 'SYP',
            'injection'=> 'INJ', 'inj'=> 'INJ',
            'drop'   => 'DROP', 'drops' => 'DROPS',
            'cream'  => 'CREAM', 'ointment' => 'OINT', 'gel' => 'GEL',
            'lotion' => 'LOTION', 'powder' => 'POWDER', 'sachet' => 'SACHET',
            'spray'  => 'SPRAY',
        ];

        // Also load opd_dose_when Hindi labels from DB (same as OPD controller)
        $doseWhenHindiMap = [];
        if ($this->db->tableExists('opd_dose_when')) {
            $whenRows = $this->db->table('opd_dose_when')
                ->select('dose_sign, dose_sign_hindi')->get()->getResultArray();
            foreach ($whenRows as $r) {
                $k = strtolower(trim((string) ($r['dose_sign'] ?? '')));
                $v = trim((string) ($r['dose_sign_hindi'] ?? ''));
                if ($k !== '' && $v !== '') {
                    $doseWhenHindiMap[$k] = $v;
                }
            }
        }
        $doseFreqHindiMap = [];
        if ($this->db->tableExists('opd_dose_frequency')) {
            $freqRows = $this->db->table('opd_dose_frequency')
                ->select('dose_sign, dose_sign_hindi')->get()->getResultArray();
            foreach ($freqRows as $r) {
                $k = strtolower(trim((string) ($r['dose_sign'] ?? '')));
                $v = trim((string) ($r['dose_sign_hindi'] ?? ''));
                if ($k !== '' && $v !== '') {
                    $doseFreqHindiMap[$k] = $v;
                }
            }
        }

        // ── Build the table ─────────────────────────────────────────────────────
        $html  = '<table width="100%" style="border-collapse:collapse;font-size:12px;border:1px solid #333333;margin-top:5px;margin-bottom:5px;">'
               . '<thead><tr style="background:#f2f2f2;border-bottom:1px solid #333333;">'
               . '<th style="padding:6px 8px;text-align:left;width:5%;font-weight:bold;color:#000000;border-right:1px solid #cccccc;">#</th>'
               . '<th style="padding:6px 8px;text-align:left;width:40%;font-weight:bold;color:#000000;border-right:1px solid #cccccc;">Medicine</th>'
               . '<th style="padding:6px 8px;text-align:left;width:55%;font-weight:bold;color:#000000;">Directions</th>'
               . '</tr></thead><tbody>';

        $i = 0;
        foreach ($medRows as $med) {
            $rawName       = trim((string) ($med['med_name'] ?? ''));
            if ($rawName === '') {
                continue;
            }
            $i++;
            $formulationRaw = trim((string) ($med['med_type'] ?? ''));
            $generic        = trim((string) ($med['med_salt'] ?? ''));
            $remark         = trim((string) ($med['remark']   ?? ''));
            $days           = trim((string) ($med['no_of_days'] ?? ''));

            // Auto-resolve generic / salt name from master database if not present on row
            if ($generic === '' && $this->db->tableExists('opd_med_master')) {
                $cleanMedName = trim((string) preg_replace('/^(TAB|CAP|SYP|INJ|CREAM|OINT|GEL|DROPS|SPRAY)\s+/i', '', $rawName));
                if ($cleanMedName !== '') {
                    $masterRow = $this->db->table('opd_med_master')
                        ->select('genericname, salt_name')
                        ->groupStart()
                            ->where('item_name', $cleanMedName)
                            ->orLike('item_name', $cleanMedName, 'after')
                            ->orLike('item_name', $cleanMedName, 'both')
                        ->groupEnd()
                        ->limit(1)
                        ->get()
                        ->getRowArray();

                    if (!empty($masterRow)) {
                        $generic = trim((string) (!empty($masterRow['genericname']) ? $masterRow['genericname'] : ($masterRow['salt_name'] ?? '')));
                    }

                    // Fallback to matching first significant keyword (e.g. "PANTOP" or "ACILOC")
                    if ($generic === '') {
                        $words = preg_split('/[\s\-_]+/', $cleanMedName);
                        if (is_array($words)) {
                            foreach ($words as $w) {
                                if (strlen($w) >= 4) {
                                    $kwRow = $this->db->table('opd_med_master')
                                        ->select('genericname, salt_name')
                                        ->groupStart()
                                            ->like('item_name', $w, 'after')
                                            ->orLike('genericname', $w, 'after')
                                            ->orLike('salt_name', $w, 'after')
                                        ->groupEnd()
                                        ->limit(1)
                                        ->get()
                                        ->getRowArray();
                                    if (!empty($kwRow)) {
                                        $generic = trim((string) (!empty($kwRow['genericname']) ? $kwRow['genericname'] : ($kwRow['salt_name'] ?? '')));
                                        if ($generic !== '') {
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Determine formulation prefix (TAB, CAP, SYP …)
            $formKey  = strtolower($formulationRaw);
            $formType = $formulationPrefixMap[$formKey] ?? strtoupper($formulationRaw);
            if ($formType === '') {
                if (preg_match('/^(TAB|CAP|SYP|INJ|CREAM|OINT|GEL|DROPS|SPRAY)\b/i', $rawName, $m)) {
                    $formType = strtoupper($m[1]);
                } else {
                    $formType = 'TAB';
                }
            }
            $fullName = $rawName;
            if ($formType !== '' && preg_match('/^' . preg_quote($formType, '/') . '\b/i', $fullName) !== 1) {
                $fullName = $formType . ' ' . $fullName;
            }

            // Medicine column HTML (B&W printer friendly: bold black name with crisp dark-gray italic generic name)
            $nameHtml = '<strong>' . esc($fullName) . '</strong>';
            if ($generic !== '') {
                $nameHtml .= '<div style="font-size:10px;color:#333333;font-style:italic;font-weight:normal;margin-top:2px;">' . esc($generic) . '</div>';
            }

            // Directions: dosage/dosage_when/dosage_freq are stored as plain text labels
            // (e.g. "BBF (BEFORE BREAKFAST)", "EMPTY STOMACH", "BD"), NOT integer IDs.
            $doseText = trim((string) ($med['dosage']      ?? ''));
            $whenText = trim((string) ($med['dosage_when'] ?? ''));
            $freqText = trim((string) ($med['dosage_freq'] ?? ''));

            // Sanitize remark: strip known UI action-button artefacts stored by the serializer
            $rawRemark = trim((string) ($med['remark'] ?? ''));
            // Remove patterns like "Edit", "Remove", "EditRemove", "Edit Remove" (case-insensitive)
            $cleanRemark = (string) preg_replace('/^(edit\s*remove|remove\s*edit|edit|remove|delete)\s*$/i', '', $rawRemark);
            $cleanRemark = trim($cleanRemark);

            $dirParts      = [];
            $dirLocalParts = [];

            // ── When (Relation to Food) ──────────────────────────────────────────
            if ($whenText !== '') {
                $upperWhen = strtoupper($whenText);
                // Expand short codes to descriptive form
                if (isset($whenCodeDescMap[$upperWhen])) {
                    $dirParts[] = $whenCodeDescMap[$upperWhen];
                } else {
                    $dirParts[] = $whenText;
                }
                // Hindi translation
                if (isset($whenHindiMap[$upperWhen])) {
                    $dirLocalParts[] = $whenHindiMap[$upperWhen];
                } elseif (isset($doseWhenHindiMap[strtolower($whenText)])) {
                    $dirLocalParts[] = $doseWhenHindiMap[strtolower($whenText)];
                }
            }

            // ── Dose / Schedule (e.g. "BBF (BEFORE BREAKFAST)", "1 Tab") ────────
            if ($doseText !== '') {
                $dirParts[] = $doseText;
            }

            // ── Frequency (OD, BD, TDS …) ────────────────────────────────────────
            if ($freqText !== '') {
                $upperFreq = strtoupper($freqText);
                $dirParts[] = $freqText;
                if (isset($freqHindiMap[$upperFreq])) {
                    $dirLocalParts[] = $freqHindiMap[$upperFreq];
                } elseif (isset($doseFreqHindiMap[strtolower($freqText)])) {
                    $dirLocalParts[] = $doseFreqHindiMap[strtolower($freqText)];
                }
            }

            // ── Remark (Medicine Advice) — only if clean ─────────────────────────
            if ($cleanRemark !== '') {
                $upperRemark = strtoupper($cleanRemark);
                $dirParts[]  = $cleanRemark;
                if (isset($remarkHindiMap[$upperRemark])) {
                    $dirLocalParts[] = $remarkHindiMap[$upperRemark];
                }
            }

            if ($days !== '') {
                $daysText = is_numeric($days) ? $days . ' Days' : $days;
                $dirParts[] = $daysText;
                $daysNum = is_numeric($days) ? (int) $days : 0;
                if ($daysNum > 0) {
                    $dirLocalParts[] = $daysNum . ' दिन';
                } elseif (stripos($days, 'week') !== false) {
                    $dirLocalParts[] = '1 हफ़्ता';
                } elseif (stripos($days, 'month') !== false) {
                    $dirLocalParts[] = '1 महीना';
                }
            }

            $directionsText = !empty($dirParts) ? implode(' | ', $dirParts) : '-';
            $localText      = !empty($dirLocalParts)
                ? implode(' | ', array_values(array_unique($dirLocalParts)))
                : '';

            $html .= '<tr style="border-bottom:1px solid #cccccc;">'
                   . '<td style="padding:6px 8px;vertical-align:middle;text-align:left;border-right:1px solid #e0e0e0;color:#000000;">' . $i . '</td>'
                   . '<td style="padding:6px 8px;vertical-align:middle;text-align:left;border-right:1px solid #e0e0e0;color:#000000;">' . $nameHtml . '</td>'
                   . '<td style="padding:6px 8px;vertical-align:middle;text-align:left;color:#000000;">'
                   . '<div>' . esc($directionsText) . '</div>'
                   . ($localText !== '' ? '<div style="font-size:11px;color:#333333;line-height:1.4;margin-top:2px;" lang="hi">' . esc($localText) . '</div>' : '')
                   . '</td>'
                   . '</tr>';
        }

        if ($i === 0) {
            return '';
        }

        $html .= '</tbody></table>';
        return '<h4 class="discharge-section-heading">Prescribed Medicines</h4>' . $html;
    }

    /**
     * Build dosage display string from dosage IDs.
     * Returns format: "BID (TWO TIME A DAY) / दिन में दो बार लेना"
     */
    private function buildDosageDisplay(int $doseId, int $whenId, int $freqId): string
    {
        static $doseMap = null;
        static $whenMap = null;
        static $freqMap = null;

        if ($doseMap === null) {
            $doseMap = $this->getDoseMasterRows('opd_dose_shed');
        }
        if ($whenMap === null) {
            $whenMap = $this->getDoseMasterRows('opd_dose_when');
        }
        if ($freqMap === null) {
            $freqMap = $this->getDoseMasterRows('opd_dose_frequency');
        }

        $parts = [];
        $localParts = [];

        if ($doseId > 0 && isset($doseMap[$doseId])) {
            $dose = $doseMap[$doseId];
            if ($dose['label'] !== '') {
                $parts[] = $dose['label'];
            }
            if ($dose['local_label'] !== '') {
                $localParts[] = $dose['local_label'];
            }
        }

        if ($whenId > 0 && isset($whenMap[$whenId])) {
            $when = $whenMap[$whenId];
            if ($when['label'] !== '') {
                $parts[] = $when['label'];
            }
            if ($when['local_label'] !== '') {
                $localParts[] = $when['local_label'];
            }
        }

        if ($freqId > 0 && isset($freqMap[$freqId])) {
            $freq = $freqMap[$freqId];
            if ($freq['label'] !== '') {
                $parts[] = $freq['label'];
            }
            if ($freq['local_label'] !== '') {
                $localParts[] = $freq['local_label'];
            }
        }

        $english = implode(' ', $parts);
        $local = implode(' ', $localParts);

        if ($english !== '' && $local !== '') {
            return $english . ' / ' . $local;
        } elseif ($english !== '') {
            return $english;
        } elseif ($local !== '') {
            return $local;
        }

        return '';
    }

    private function normalizeRichText(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        // Check if content contains HTML tags (from HTML editor)
        // Look for common HTML tags: p, div, span, strong, b, i, em, ul, ol, li, table, tr, td, br
        if (preg_match('/<(?:p|div|span|strong|b|i|em|ul|ol|li|table|tr|td|th|thead|tbody|h[1-6])\b[^>]*>/i', $value)) {
            // Content is HTML - preserve it but clean up whitespace
            // Remove excessive newlines between tags and trim
            $value = preg_replace('/>\s+</', '><', $value) ?? $value;
            $value = preg_replace('/\r\n?/', "\n", $value) ?? $value;
            
            // Decode HTML entities that might have been double-encoded
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($this->looksLikeMalformedDischargeHtml($value)) {
                $value = $this->normalizeMalformedDischargeHtmlForMpdf($value);
            }
            
            return trim($value);
        }

        // Content is plain text (legacy textarea) - convert to plain text
        $value = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\s*\/\s*p\s*>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\s*p\b[^>]*>/i', '', $value) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\r\n?|\n/', "\n", $value) ?? $value;
        $value = preg_replace('/\n{3,}/', "\n\n", $value) ?? $value;

        return trim($value);
    }

    /**
     * Render rich text content for HTML output.
     * Detects if content is HTML (from HTML editor) or plain text (from textarea).
     * HTML content is preserved, plain text is escaped and nl2br applied.
     */
    private function renderRichText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Check if content contains HTML tags (from HTML editor)
        if (preg_match('/<(?:p|div|span|strong|b|i|em|ul|ol|li|table|tr|td|th|thead|tbody|h[1-6])\b[^>]*>/i', $text)) {
            // Content is HTML - return as-is (already sanitized by normalizeRichText)
            if ($this->looksLikeMalformedDischargeHtml($text)) {
                $text = $this->normalizeMalformedDischargeHtmlForMpdf($text);
            }

            return $text;
        }

        // Content is plain text - escape and convert newlines to br
        return nl2br(esc($text));
    }

    private function addListSection(array &$sections, string $title, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $html = '<h4 class="discharge-section-heading">' . esc($title) . '</h4><ul class="discharge-list">';
        foreach ($rows as $row) {
            $report = trim((string) ($row['comp_report'] ?? ''));
            $remark = trim((string) ($row['comp_remark'] ?? ''));
            if ($report === '' && $remark === '') {
                continue;
            }

            $line = esc($report);
            if ($remark !== '') {
                $line .= ' <span class="discharge-remark">(' . esc($remark) . ')</span>';
            }

            $html .= '<li>' . $line . '</li>';
        }
        $html .= '</ul>';

        $sections[] = $html;
    }

    private function getDischargeDepartmentName(?object $ipd): string
    {
        if (! $ipd) {
            return '';
        }

        // Try dept_id first (common field name)
        $deptId = (int) ($ipd->dept_id ?? $ipd->department_id ?? 0);
        
        // Try hc_department table first (field is iId, not id)
        if ($deptId > 0 && $this->db->tableExists('hc_department')) {
            $row = $this->db->table('hc_department')
                ->select('vName')
                ->where('iId', $deptId)
                ->get(1)
                ->getRowArray();
            $name = trim((string) ($row['vName'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        // Try ipd_department table as fallback
        if ($deptId > 0 && $this->tableHasColumns('ipd_department', ['id', 'department_name'])) {
            $row = $this->db->table('ipd_department')
                ->select('department_name')
                ->where('id', $deptId)
                ->get(1)
                ->getRowArray();
            $name = trim((string) ($row['department_name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        // Fallback: try direct department field if it exists as a string
        $directDept = trim((string) ($ipd->department ?? $ipd->department_name ?? ''));
        if ($directDept !== '') {
            return $directDept;
        }

        return '';
    }

    private function buildAutoDischargeSummaryTable(array $panelData): string
    {
        $summary = $this->buildDischargeSummaryTokenVars($panelData);
        $ipd = $panelData['ipd_info'] ?? null;
        $person = $panelData['person_info'] ?? null;
        if (! $ipd || ! $person) {
            return '';
        }

        $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
        $patientName = trim((string) ($person->p_fname ?? ''));
        $patientCode = trim((string) ($person->uhid ?? $person->UHID ?? $person->patient_code ?? $person->p_code ?? $person->reg_no ?? ''));
        $guardian = trim((string) ($summary['GUARDIAN'] ?? ''));
        $phone = trim((string) ($person->mphone1 ?? $ipd->P_mobile1 ?? $ipd->P_mobile2 ?? ''));
        $address = trim((string) ($summary['PATIENT_ADDRESS'] ?? ''));
        $orgName = trim((string) ($ipd->ins_short_name ?? $ipd->ins_company_name ?? ''));
        if ($orgName === '') {
            $orgName = 'Direct';
        }
        $department = $this->getDischargeDepartmentName($ipd);
        $admitDate = $this->safeDate((string) ($ipd->str_register_date ?? $ipd->register_date ?? ''));
        $dischargeDate = $this->safeDate((string) ($ipd->str_discharge_date ?? $ipd->discharge_date ?? ''));

        // Get discharge status header
        $statusHeader = $this->getDischargeStatusText($ipd);

        return '<h2 class="discharge-title">' . esc($statusHeader) . '</h2>'
            . '<hr class="discharge-separator" />'
            . '<table width="100%" cellpadding="0" cellspacing="0">'
            . '<tr><td width="150px"><b>Name</b></td><td width="250px">' . esc($patientName) . '</td><td width="150px"><b>UHID</b></td><td width="250px">' . esc($patientCode) . '</td></tr>'
            . '<tr><td width="150px"><b>Age & Gender</b></td><td width="250px">' . esc(trim($age . ' / ' . ((string) ($person->xgender ?? '')))) . '</td><td width="150px"><b>IPD No.</b></td><td width="250px">' . esc((string) ($ipd->ipd_code ?? '')) . '</td></tr>'
            . '<tr><td width="150px"><b>Guardian</b></td><td width="250px">' . esc($guardian) . '</td><td width="150px"><b>Admission</b></td><td width="250px">' . esc($admitDate) . '</td></tr>'
            . '<tr><td width="150px"><b>Phone No.</b></td><td width="250px">' . esc($phone) . '</td><td width="150px"><b>Discharge</b></td><td width="250px">' . esc($dischargeDate) . '</td></tr>'
            . '<tr><td width="150px"><b>Address</b></td><td width="250px">' . esc($address) . '</td><td width="150px"><b>Org. Name</b></td><td width="250px">' . esc($orgName) . '</td></tr>'
            . '<tr><td width="150px"><b>Department</b></td><td width="250px">' . esc($department) . '</td><td width="150px"></td><td width="250px"></td></tr>'
            . '</table>'
            . '<hr class="discharge-separator" />';
    }

    private function renderStoredHtmlFragment(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (stripos($value, '<') === false) {
            return nl2br(esc($value));
        }

        return $value;
    }

    private function buildNarrativeSection(string $title, array $rows, string $remark = ''): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $report = trim((string) ($row['comp_report'] ?? ''));
            $rowRemark = trim((string) ($row['comp_remark'] ?? ''));
            if ($report === '' && $rowRemark === '') {
                continue;
            }

            if ($report !== '' && $rowRemark !== '') {
                $lines[] = esc($report) . ' <i>' . esc($rowRemark) . '</i>';
            } elseif ($report !== '') {
                $lines[] = esc($report);
            } else {
                $lines[] = esc($rowRemark);
            }
        }

        $remark = trim($remark);
        if ($remark !== '') {
            $lines[] = $this->renderRichText($remark);
        }

        if ($lines === []) {
            return '';
        }

        return '<p><b>' . esc($title) . '</b> :<br /> ' . implode('<br /> ', $lines) . '</p>';
    }

    private function sanitizeComplaintNarrativeRemark(string $remark): string
    {
        $remark = trim($remark);
        if ($remark === '') {
            return '';
        }

        $patterns = [
            '/^\s*Drug\s*Allergy\s*Status\s*:\s*.+$/im',
            '/^\s*Drug\s*Allergy\s*Details\s*:\s*.+$/im',
            '/^\s*ADR\s*History\s*:\s*.+$/im',
            '/^\s*Current\s*Medications\s*:\s*.+$/im',
            '/^\s*Co-Morbidities\s*:\s*.+$/im',
        ];

        foreach ($patterns as $pattern) {
            $remark = (string) preg_replace($pattern, '', $remark);
        }

        $remark = (string) preg_replace('/(?:\r\n?|\n){3,}/', "\n\n", $remark);

        return trim($remark);
    }

    private function buildInlineExamSummary(array $rows): string
    {
        $parts = [];
        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            $unit = trim((string) ($row['unit'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }

            $parts[] = esc($label) . ' :<i>' . esc($value . ($unit !== '' ? $unit : '')) . '</i>';
        }

        return implode('&nbsp;&nbsp;&nbsp;', $parts);
    }

    private function buildNamedDateSection(string $title, array $rows, string $nameField, string $dateField, string $dateLabel): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row[$nameField] ?? ''));
            if ($name === '') {
                continue;
            }
            $dateText = trim((string) ($row[$dateField] ?? ''));
            $line = esc($name);
            if ($dateText !== '') {
                $line .= '&nbsp;&nbsp;&nbsp; / <b>' . esc($dateLabel) . ' :</b> ' . esc($dateText);
            }
            $lines[] = $line;
        }

        if ($lines === []) {
            return '';
        }

        return '<p><b>' . esc($title) . ' : </b><br/>' . implode('<br/>', $lines) . '</p>';
    }

    private function buildAutoDischargeContent(int $ipdId, array $panelData): string
    {
        $ipd = $panelData['ipd_info'] ?? null;
        $person = $panelData['person_info'] ?? null;

        $patientName = trim((string) ($person->p_fname ?? ''));
        $patientCode = trim((string) (
            $person->uhid
            ?? $person->UHID
            ?? $person->patient_code
            ?? $person->p_code
            ?? $person->reg_no
            ?? ''
        ));
        $patientId = (int) ($person->id ?? 0);
        $opdHistory = $this->getLatestOpdHistorySnapshot($patientId);
        $instructionRowForMeta = $this->firstRowByIpd('ipd_discharge_instructions', $ipdId);
        $instructionMetaForPreview = $this->parseInstructionMetaPayload((string) ($instructionRowForMeta['comp_report'] ?? ''));
        if (empty($instructionMetaForPreview['food_ids'])
            && $this->tableHasColumns('ipd_discharge_drug_food_interaction', ['ipd_id', 'food_id_list'])) {
            $legacyFoodRow = $this->firstRowByIpd('ipd_discharge_drug_food_interaction', $ipdId);
            $instructionMetaForPreview['food_ids'] = $this->parseFoodIdCsv((string) ($legacyFoodRow['food_id_list'] ?? ''));
        }
        $instructionNabh = is_array($instructionMetaForPreview['nabh'] ?? null) ? ($instructionMetaForPreview['nabh'] ?? []) : [];
        foreach (['drug_allergy_status', 'drug_allergy_details', 'adr_history', 'current_medications', 'co_morbidities', 'hpi_note', 'women_lmp', 'women_last_baby', 'women_pregnancy_related', 'women_related_problems'] as $field) {
            if (trim((string) ($opdHistory[$field] ?? '')) === '' && trim((string) ($instructionNabh[$field] ?? '')) !== '') {
                $opdHistory[$field] = trim((string) ($instructionNabh[$field] ?? ''));
            }
        }

        $sections = [];
        
        // NOTE: Patient demographic table is NOT included in auto-generated content.
        // It is available as {{PATIENT_INFO_TABLE}} token for templates to use if needed.
        // This matches CI3 behavior where template controlled the patient info display.

        // Build Discharge Summary section with status and free-text content
        $dischargeStatusText = $this->getDischargeStatusText($ipd);
        $isStandardStatus = ($dischargeStatusText === '' || strcasecmp($dischargeStatusText, 'Discharge Summary') === 0);
        
        // Get discharge summary free-text content from ipd_discharge_instructions.comp_remark
        $dischargeSummaryText = $this->normalizeRichText((string) ($instructionRowForMeta['comp_remark'] ?? ''));
        
        // Only include the section if there is actual narrative text OR a non-default status (e.g. LAMA, Dead Summary)
        if ($dischargeSummaryText !== '' || ! $isStandardStatus) {
            $summaryHtml = '';
            if ($dischargeSummaryText !== '') {
                $summaryHtml = '<h4 class="discharge-section-heading">Discharge Summary</h4>';
                if (! $isStandardStatus) {
                    $summaryHtml .= '<div class="discharge-status"><strong>' . esc($dischargeStatusText) . '</strong></div>';
                }
                $summaryHtml .= '<div class="discharge-summary-content">' . $this->renderRichText($dischargeSummaryText) . '</div>';
            } elseif (! $isStandardStatus) {
                $summaryHtml = '<div class="discharge-status"><strong>' . esc($dischargeStatusText) . '</strong></div>';
            }
            if ($summaryHtml !== '') {
                $sections[] = $summaryHtml;
            }
        }

        $complaints = $this->byIpdRows('ipd_discharge_complaint', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $complaintRemark = $this->firstRowByIpd('ipd_discharge_complaint_remark', $ipdId);
        $complaintRemarkText = $this->sanitizeComplaintNarrativeRemark(
            $this->normalizeRichText((string) ($complaintRemark['comp_remark'] ?? ''))
        );
        $complaintBlock = $this->buildNarrativeSection('Presenting Complaints and Reason for Admission', $complaints, $complaintRemarkText);
        if ($complaintBlock !== '') {
            $sections[] = $complaintBlock;
        }

        $complaintMeta = $this->parseComplaintMetaPayload((string) ($complaintRemark['comp_report'] ?? ''));
        $painValue = (string) ($complaintMeta['pain_value'] ?? '');
        $painLabel = $this->painScaleLabel($painValue);
        $painSection = '';
        if ($painLabel !== '') {
            $painSection = '<div><b>Pain Measurement Scale:</b> ' . esc($painLabel) . ' (' . esc($painValue) . ')</div>';
        } elseif ((string) ($opdHistory['pain_label'] ?? '') !== '') {
            $opdPainLabel = (string) ($opdHistory['pain_label'] ?? '');
            $opdPainValue = (string) ($opdHistory['pain_value'] ?? '');
            $painSection = '<div><b>Pain Measurement Scale:</b> ' . esc($opdPainLabel)
                . ($opdPainValue !== '' ? ' (' . esc($opdPainValue) . ')' : '')
                . '</div>';
        }

        $physicalExamRows = $this->getPhysicalExamRows($ipdId);
        $generalRowsRaw = $physicalExamRows['general_all'] ?? [];
        $generalRows = [];
        foreach ($generalRowsRaw as $row) {
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $generalRows[] = $row;
        }

        if (! empty($generalRows)) {
            $generalSummary = $this->buildInlineExamSummary($generalRows);
            if ($generalSummary !== '') {
                $sections[] = '<p><b>General Examination on Admission : </b><br/>' . $generalSummary . '</p>';
            }
        }

        $sysRows = $physicalExamRows['systemic'] ?? [];
        $sysHtml = '';
        foreach ($sysRows as $row) {
            $value = $this->normalizeRichText((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $sysHtml .= '<div class="discharge-item">' . $this->renderRichText($value) . '</div>';
        }
        if ($sysHtml !== '') {
            $sections[] = '<h4 class="discharge-section-heading">Other / Systemic Examinations</h4>' . $sysHtml;
        }

        $personalHistory = [];
        if ($patientId > 0 && $this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
            $historyMap = [
                'is_smoking' => 'Smoking',
                'is_alcohol' => 'Alcohol',
                'is_drug_abuse' => 'Drug abuse',
                'is_tobacoo' => 'Tobacco',
                'Others' => 'Others',
            ];

            foreach ($historyMap as $field => $label) {
                if ((int) ($patientRow[$field] ?? 0) === 1) {
                    $personalHistory[] = $label;
                }
            }
        }

        if (! empty($personalHistory)) {
            $sections[] = '<h4 class="discharge-section-heading">Personal History</h4><div>' . esc(implode(', ', $personalHistory)) . '</div>';
        }

        $allergySection = '';
        $allergyLines = [];
        $drugAllergyStatus = trim((string) ($opdHistory['drug_allergy_status'] ?? ''));
        if (! $this->isNoAllergyDataStatus($drugAllergyStatus)) {
            $allergyLines[] = '<div><b>Drug Allergy Status:</b> ' . esc($drugAllergyStatus) . '</div>';
        }
        if ((string) ($opdHistory['drug_allergy_details'] ?? '') !== '') {
            $allergyLines[] = '<div><b>Drug Allergy Details:</b> ' . esc((string) ($opdHistory['drug_allergy_details'] ?? '')) . '</div>';
        }
        if ((string) ($opdHistory['adr_history'] ?? '') !== '') {
            $allergyLines[] = '<div><b>ADR History:</b> ' . esc((string) ($opdHistory['adr_history'] ?? '')) . '</div>';
        }
        if ((string) ($opdHistory['current_medications'] ?? '') !== '') {
            $allergyLines[] = '<div><b>Current Medications:</b> ' . esc((string) ($opdHistory['current_medications'] ?? '')) . '</div>';
        }
        if (! empty($allergyLines)) {
            $allergySection = '<h4 class="discharge-section-heading">Drug Allergy / ADR</h4>' . implode('', $allergyLines);
        }

        $coMorbText = trim((string) ($opdHistory['co_morbidities'] ?? ''));

        if ($allergySection !== '') {
            $sections[] = $allergySection;
        }

        if ($coMorbText !== '') {
            $sections[] = '<h4 class="discharge-section-heading">Co-Morbidities</h4><div>' . esc($coMorbText) . '</div>';
        }

        $admitDate = $this->normalizeDateValue((string) ($ipd->register_date ?? '')) ?? '';
        $dischargeDate = $this->normalizeDateValue((string) ($ipd->discharge_date ?? '')) ?? date('Y-m-d');
        $savedClinicalDates = $this->getSavedClinicalLabDates($ipdId);
        $clinicalLabRows = $this->getClinicalInvestigationLabRows($patientId, $admitDate, $dischargeDate, $savedClinicalDates);

        // Strict selection mode: in-hospital lab should print only when user
        // explicitly selected one or more investigation dates.
        $effectiveClinicalDates = $savedClinicalDates;

        $pathologyMatrix = $this->getClinicalPathologyMatrixRows($patientId, $effectiveClinicalDates);

        $selectedLabRows = [];
        foreach ($clinicalLabRows as $row) {
            if (! empty($row['checked'])) {
                $selectedLabRows[] = $row;
            }
        }

        $otherExamRow = [];
        if ($this->tableHasColumns('ipd_discharge_2', ['ipd_d_id'])) {
            $otherExamRow = $this->db->table('ipd_discharge_2')
                ->where('ipd_d_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray() ?? [];
        }
        $otherExamParsed = $this->parseClinicalOtherExamPayload((string) ($otherExamRow['rdata'] ?? ''));
        $nonPathRows = $this->getClinicalNonPathReportRows($patientId, $admitDate, $dischargeDate, $otherExamParsed['non_path_ids'] ?? []);
        $selectedNonPathRows = [];
        foreach ($nonPathRows as $row) {
            if (! empty($row['checked'])) {
                $selectedNonPathRows[] = $row;
            }
        }

        $manualInvestRows = $this->getMappedColRows('ipd_discharge_investigation_during_admit', 'ipd_discharge_1_d', $ipdId, 'Manual Exam', 1);
        $selectedManualInvestRows = [];
        foreach ($manualInvestRows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $selectedManualInvestRows[] = ['label' => $label, 'value' => $value];
        }

        $specialInvestRows = $this->getMappedColRows('ipd_discharge_special_investigation', 'ipd_discharge_1_e', $ipdId, 'Special Exam', 1);
        $selectedSpecialInvestRows = [];
        foreach ($specialInvestRows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $selectedSpecialInvestRows[] = ['label' => $label, 'value' => $value];
        }

        if (! empty($pathologyMatrix['rows']) || ! empty($selectedLabRows) || ! empty($selectedNonPathRows) || ! empty($selectedManualInvestRows) || ! empty($selectedSpecialInvestRows) || trim((string) ($otherExamParsed['text'] ?? '')) !== '') {
            $html = '<h4 class="discharge-section-heading">Clinical Investigation Reports</h4>';

            if (! empty($pathologyMatrix['rows'])) {
                $html .= '<div><b>In-Hospital Lab:</b></div>'
                    . '<table class="discharge-table" border="1" cellpadding="6">'
                    . '<tr>'
                    . '<th>Test</th>'
                    . '<th>Fixed Normals</th>';

                foreach (($pathologyMatrix['dates'] ?? []) as $dt) {
                    $html .= '<th>' . esc((string) ($pathologyMatrix['date_labels'][$dt] ?? $dt)) . '</th>';
                }

                $html .= '</tr>';

                foreach (($pathologyMatrix['rows'] ?? []) as $row) {
                    $html .= '<tr>'
                        . '<td>' . esc((string) ($row['test_name'] ?? '')) . '</td>'
                        . '<td>' . esc((string) ($row['fixed_normals'] ?? '')) . '</td>';

                    foreach (($pathologyMatrix['dates'] ?? []) as $dt) {
                        $html .= '<td>' . esc((string) ($row['values'][$dt] ?? '')) . '</td>';
                    }

                    $html .= '</tr>';
                }

                $html .= '</table>';
            } elseif (! empty($selectedLabRows)) {
                // Fallback when value-matrix cannot be built from current schema/data.
                $html .= '<div><b>In-Hospital Lab:</b></div><ul class="discharge-sublist">';
                foreach ($selectedLabRows as $row) {
                    $html .= '<li>[' . esc((string) ($row['inv_date_label'] ?? '')) . '] ' . esc((string) ($row['test_list'] ?? '')) . '</li>';
                }
                $html .= '</ul>';
            }

            if (! empty($selectedNonPathRows)) {
                $html .= '<div><b>X-Ray / ECG / Sonography / CT / MRI:</b></div><ul class="discharge-sublist">';
                foreach ($selectedNonPathRows as $row) {
                    $html .= '<li>[' . esc((string) ($row['report_date_label'] ?? '')) . '] '
                        . esc((string) ($row['modality'] ?? ''))
                        . ' - ' . esc((string) ($row['report_name'] ?? ''));

                    $impression = trim((string) ($row['impression'] ?? ''));
                    if ($impression !== '') {
                        $html .= '<br><span class="discharge-remark">Impression: ' . nl2br(esc($impression)) . '</span>';
                    }

                    $html .= '</li>';
                }
                $html .= '</ul>';
            }

            if (! empty($selectedManualInvestRows)) {
                $html .= '<div><b>Manual Clinical Investigations:</b></div><ul class="discharge-sublist">';
                foreach ($selectedManualInvestRows as $row) {
                    $html .= '<li><b>' . esc((string) ($row['label'] ?? '')) . ':</b> ' . esc((string) ($row['value'] ?? '')) . '</li>';
                }
                $html .= '</ul>';
            }

            if (! empty($selectedSpecialInvestRows)) {
                $html .= '<div><b>Special Investigations:</b></div><ul class="discharge-sublist">';
                foreach ($selectedSpecialInvestRows as $row) {
                    $html .= '<li><b>' . esc((string) ($row['label'] ?? '')) . ':</b> ' . esc((string) ($row['value'] ?? '')) . '</li>';
                }
                $html .= '</ul>';
            }

            $otherExamText = $this->normalizeRichText((string) ($otherExamParsed['text'] ?? ''));
            if ($otherExamText !== '') {
                $html .= '<div><b>Other Examinations / Provisional Diagnosis:</b><br>' . $this->renderRichText($otherExamText) . '</div>';
            }

            $sections[] = $html;
        }

        $diagnosis = $this->byIpdRows('ipd_discharge_diagnosis', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $diagnosisRemark = $this->firstRowByIpd('ipd_discharge_diagnosis_remark', $ipdId);
        $diagnosisRemarkText = $this->normalizeRichText((string) ($diagnosisRemark['comp_remark'] ?? ''));
        $diagnosisBlock = $this->buildNarrativeSection('Final Diagnosis', $diagnosis, $diagnosisRemarkText);
        if ($diagnosisBlock !== '') {
            $sections[] = $diagnosisBlock;
        }

        $inhosRow = $this->firstRowByIpd('ipd_discharge_investigtions_inhos', $ipdId);
        $inhosRemark = $this->normalizeRichText((string) ($inhosRow['comp_remark'] ?? ''));
        if ($inhosRemark !== '') {
            $sections[] = '<h4 class="discharge-section-heading">Summary of key investigations during Hospitalization</h4><div>'
                . $this->renderRichText($inhosRemark)
                . '</div>';
        }

        $course = $this->byIpdRows('ipd_discharge_course', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $courseRemark = $this->firstRowByIpd('ipd_discharge_course_remark', $ipdId);
        $courseRemarkText = $this->normalizeRichText((string) ($courseRemark['comp_remark'] ?? ''));
        $courseBlock = $this->buildNarrativeSection('Course in the hospital', $course, $courseRemarkText);
        if ($courseBlock !== '') {
            $sections[] = $courseBlock;
        }

        if ($painSection !== '') {
            $sections[] = $painSection;
        }

        $nursingTrendSection = $this->buildNursingTrendSection($ipdId);
        if ($nursingTrendSection !== '') {
            $sections[] = $nursingTrendSection;
        }

        $dischargeExamRowsRaw = $this->getMappedColRows('ipd_discharge_general_exam_col', 'ipd_discharge_1_b_final', $ipdId, 'Discharge Condition', null);
        $dischargeExamRows = [];
        foreach ($dischargeExamRowsRaw as $row) {
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $dischargeExamRows[] = $row;
        }

        if (! empty($dischargeExamRows)) {
            $dischargeSummary = $this->buildInlineExamSummary($dischargeExamRows);
            if ($dischargeSummary !== '') {
                $sections[] = '<p><b>Examination on Discharge : </b>' . $dischargeSummary . '</p>';
            }
        }

        $surgeryRows = $this->byIpdRows('ipd_discharge_surgery', ['surgery_name', 'surgery_date'], 'id ASC', $ipdId);
        $surgeryBlock = $this->buildNamedDateSection('Surgery', $surgeryRows, 'surgery_name', 'surgery_date', 'Date of Surgery');
        if ($surgeryBlock !== '') {
            $sections[] = $surgeryBlock;
        }

        $procedureRows = $this->byIpdRows('ipd_discharge_procedure', ['procedure_name', 'procedure_date'], 'id ASC', $ipdId);
        $procedureBlock = $this->buildNamedDateSection('Procedure', $procedureRows, 'procedure_name', 'procedure_date', 'Date of Procedure');
        if ($procedureBlock !== '') {
            $sections[] = $procedureBlock;
        }

        // Build Discharge Medications in OPD-style table format
        // (# | Medicine bold + generic | Directions English + Hindi)
        $dischargeMedHtml = $this->buildOpdStyleMedicationsHtml($ipdId);
        if ($dischargeMedHtml !== '') {
            $sections[] = $dischargeMedHtml;
        }

        $instructions = $this->byIpdRows('ipd_discharge_instructions', ['comp_report', 'comp_remark', 'review_after', 'footer_text'], 'id DESC', $ipdId);
        if (! empty($instructions)) {
            $first = $instructions[0];
            $html = '<h4 class="discharge-section-heading">Discharge Advice/Instructions/Summary</h4>';

            $instructionMeta = $this->parseInstructionMetaPayload((string) ($first['comp_report'] ?? ''));
            $foodIds = is_array($instructionMeta['food_ids'] ?? null) ? ($instructionMeta['food_ids'] ?? []) : [];
            if (empty($foodIds)
                && $this->tableHasColumns('ipd_discharge_drug_food_interaction', ['ipd_id', 'food_id_list'])) {
                $legacyFoodRow = $this->firstRowByIpd('ipd_discharge_drug_food_interaction', $ipdId);
                $foodIds = $this->parseFoodIdCsv((string) ($legacyFoodRow['food_id_list'] ?? ''));
            }
            $foodMap = [];
            if (! empty($foodIds) && $this->tableHasColumns('ipd_discharge_master_food', ['id', 'food_short', 'food_desc'])) {
                $rows = $this->db->table('ipd_discharge_master_food')
                    ->select('id,food_short,food_desc,food_desc_lang')
                    ->whereIn('id', array_map('intval', $foodIds))
                    ->get()
                    ->getResultArray();
                foreach ($rows as $row) {
                    $foodMap[(int) ($row['id'] ?? 0)] = $row;
                }
            }

            if (! empty($foodIds)) {
                $html .= '<div class="discharge-field"><strong>Dietary Advice:</strong></div>';
                $html .= '<ol class="discharge-list">';
                foreach ($foodIds as $foodId) {
                    $id = (int) $foodId;
                    $row = $foodMap[$id] ?? null;
                    if (! is_array($row)) {
                        continue;
                    }

                    $heading = trim((string) ($row['food_short'] ?? ''));
                    $line = trim((string) ($row['food_desc_lang'] ?? ''));
                    if ($line === '') {
                        $line = trim((string) ($row['food_desc'] ?? ''));
                    }
                    if ($line === '' && $heading === '') {
                        continue;
                    }

                    $entry = '';
                    if ($heading !== '') {
                        $entry .= '<strong>' . esc($heading) . ':</strong> ';
                    }
                    $entry .= esc($line !== '' ? $line : $heading);
                    $html .= '<li>' . $entry . '</li>';
                }
                $html .= '</ol>';
            }

            $otherText = trim((string) ($instructionMeta['other_text'] ?? ''));
            if ($otherText !== '') {
                $html .= '<div class="discharge-field"><strong>Other Advice:</strong> ' . $this->renderStoredHtmlFragment($otherText) . '</div>';
            }

            $remark = trim((string) ($first['comp_remark'] ?? ''));
            if ($remark !== '') {
                $html .= '<div class="discharge-field"><strong>Discharge Summary:</strong> ' . $this->renderStoredHtmlFragment($remark) . '</div>';
            }

            $reviewAfter = trim((string) ($first['review_after'] ?? ''));
            if ($reviewAfter !== '') {
                $reviewDate = '';
                $dischargeDateRaw = trim((string) ($ipd->discharge_date ?? ''));
                if ($dischargeDateRaw !== '' && is_numeric($reviewAfter)) {
                    $reviewTs = strtotime($dischargeDateRaw . ' +' . (int) $reviewAfter . ' days');
                    if ($reviewTs !== false) {
                        $reviewDate = ' (' . date('d-m-Y', $reviewTs) . ')';
                    }
                }
                $html .= '<div class="discharge-footer">Review after ' . esc($reviewAfter) . ' Days' . esc($reviewDate) . ' days / as and when required</div>';
            }

            $footerText = trim((string) ($first['footer_text'] ?? ''));
            if ($footerText !== '') {
                $html .= '<div class="discharge-footer">' . $this->renderStoredHtmlFragment($footerText) . '</div>';
            }

            $sections[] = $html;
        }

        $sections[] = '<table class="discharge-signature-table" border="0" cellpadding="1" cellspacing="1">'
            . '<tbody>'
            . '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>'
            . '<tr>'
            . '<td>_________________________</td>'
            . '<td>_________________________</td>'
            . '<td>_________________________</td>'
            . '</tr>'
            . '<tr>'
            . '<td>Signature of Consultant</td>'
            . '<td>Signature of Medical Officer</td>'
            . '<td>Signature of Receiver / Date</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>';

        return trim(implode("\n", $sections));
    }

    private function buildNursingTrendSection(int $ipdId): string
    {
        if ($ipdId <= 0 || ! $this->db->tableExists('ipd_nursing_entries')) {
            return '';
        }

        foreach (['ipd_id', 'entry_type', 'recorded_at'] as $field) {
            if (! $this->db->fieldExists($field, 'ipd_nursing_entries')) {
                return '';
            }
        }

        $rows = $this->db->table('ipd_nursing_entries')
            ->select('entry_type,recorded_at,temperature_c,pulse_rate,resp_rate,bp_systolic,bp_diastolic,spo2,weight_kg,fluid_direction,fluid_amount_ml,treatment_text,general_note')
            ->where('ipd_id', $ipdId)
            ->orderBy('recorded_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return '';
        }

        $sinceTs = strtotime('-24 hours');
        $since = $sinceTs === false ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $sinceTs);

        $vitalsCount = 0;
        $fluidIn = 0;
        $fluidOut = 0;
        $treatments = [];
        $scannedCount = 0;
        $latestVitalsLine = '';

        foreach ($rows as $row) {
            $recordedAt = trim((string) ($row['recorded_at'] ?? ''));
            if ($recordedAt === '' || $recordedAt < $since) {
                continue;
            }

            $entryType = strtolower(trim((string) ($row['entry_type'] ?? '')));
            $note = trim((string) ($row['general_note'] ?? ''));
            if (stripos($note, 'Source: Scanned Paper') !== false) {
                $scannedCount++;
            }

            if ($entryType === 'vitals' || $entryType === 'admission') {
                $vitalsCount++;
                $parts = [];
                if ($row['temperature_c'] !== null && $row['temperature_c'] !== '') {
                    $tempF = (((float) $row['temperature_c']) * 9 / 5) + 32;
                    $parts[] = 'Temp ' . rtrim(rtrim(number_format($tempF, 1, '.', ''), '0'), '.') . ' F';
                }
                if ($row['pulse_rate'] !== null && $row['pulse_rate'] !== '') {
                    $parts[] = 'Pulse ' . (string) $row['pulse_rate'];
                }
                if ($row['resp_rate'] !== null && $row['resp_rate'] !== '') {
                    $parts[] = 'Resp ' . (string) $row['resp_rate'];
                }
                if ($row['bp_systolic'] !== null && $row['bp_systolic'] !== '') {
                    $parts[] = 'BP ' . (string) $row['bp_systolic'] . '/' . (string) ($row['bp_diastolic'] ?? '');
                }
                if ($row['spo2'] !== null && $row['spo2'] !== '') {
                    $parts[] = 'SpO2 ' . (string) $row['spo2'] . '%';
                }
                if ($row['weight_kg'] !== null && $row['weight_kg'] !== '') {
                    $parts[] = 'Wt ' . (string) $row['weight_kg'] . ' kg';
                }
                if (! empty($parts)) {
                    $latestVitalsLine = '[' . $recordedAt . '] ' . implode(', ', $parts);
                }
            }

            if ($entryType === 'fluid') {
                $amount = (int) ($row['fluid_amount_ml'] ?? 0);
                $dir = strtolower(trim((string) ($row['fluid_direction'] ?? '')));
                if ($dir === 'output') {
                    $fluidOut += max(0, $amount);
                } else {
                    $fluidIn += max(0, $amount);
                }
            }

            if ($entryType === 'treatment') {
                $text = trim((string) ($row['treatment_text'] ?? ''));
                if ($text !== '') {
                    $treatments[] = '[' . $recordedAt . '] ' . $text;
                }
            }
        }

        if ($vitalsCount === 0 && $fluidIn === 0 && $fluidOut === 0 && empty($treatments) && $scannedCount === 0) {
            return '';
        }

        $html = '<h4 class="discharge-section-heading">Nursing Trend Summary (Last 24 Hours)</h4>';
        $html .= '<ul class="discharge-list">';
        if ($vitalsCount > 0) {
            $html .= '<li>Vitals charted entries: ' . esc((string) $vitalsCount) . '</li>';
            if ($latestVitalsLine !== '') {
                $html .= '<li>Latest vitals: ' . esc($latestVitalsLine) . '</li>';
            }
        }
        if ($fluidIn > 0 || $fluidOut > 0) {
            $html .= '<li>Fluid balance (approx): Intake ' . esc((string) $fluidIn) . ' ml, Output ' . esc((string) $fluidOut) . ' ml, Net ' . esc((string) ($fluidIn - $fluidOut)) . ' ml</li>';
        }
        if (! empty($treatments)) {
            $html .= '<li>Key nursing treatments:</li><li class="no-bullet">';
            $html .= '<ul class="discharge-sublist">';
            foreach (array_slice($treatments, -5) as $line) {
                $html .= '<li>' . esc($line) . '</li>';
            }
            $html .= '</ul></li>';
        }
        if ($scannedCount > 0) {
            $html .= '<li>Scanned paper-derived entries reviewed and saved: ' . esc((string) $scannedCount) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function hasAnyNonEmptyValue(array $rows, array $fields): bool
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($fields as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    private function findFirstExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $table) {
            if ($this->db->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    private function buildNabhAuditChecklist(int $ipdId, array $panelData): array
    {
        $person = $panelData['person_info'] ?? null;
        $patientId = (int) ($person->id ?? 0);

        $complaintRows = $this->byIpdRows('ipd_discharge_complaint', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $complaintRemark = $this->firstRowByIpd('ipd_discharge_complaint_remark', $ipdId);
        $diagnosisRows = $this->byIpdRows('ipd_discharge_diagnosis', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $diagnosisRemark = $this->firstRowByIpd('ipd_discharge_diagnosis_remark', $ipdId);
        $courseRows = $this->byIpdRows('ipd_discharge_course', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $courseRemark = $this->firstRowByIpd('ipd_discharge_course_remark', $ipdId);
        $surgeryRows = $this->byIpdRows('ipd_discharge_surgery', ['surgery_name', 'surgery_remark'], 'id ASC', $ipdId);
        $procedureRows = $this->byIpdRows('ipd_discharge_procedure', ['procedure_name', 'procedure_remark'], 'id ASC', $ipdId);
        $conditionRows = $this->getMappedColRows('ipd_discharge_general_exam_col', 'ipd_discharge_1_b_final', $ipdId, 'Discharge Condition', null);
        $drugRows = $this->byIpdRows('ipd_discharge_drug', ['drug_name', 'drug_dose', 'drug_day'], 'id ASC', $ipdId);
        $legacyMedRows = $this->byIpdRows('ipd_discharge_prescrption_prescribed', ['med_name', 'med_type', 'qty', 'no_of_days', 'remark'], 'id ASC', $ipdId);
        $instructionRow = $this->firstRowByIpd('ipd_discharge_instructions', $ipdId);
        $opdSnapshot = $this->getLatestOpdHistorySnapshot($patientId);

        $instructionText = trim((string) ($instructionRow['comp_remark'] ?? ''));
        $reviewAfter = trim((string) ($instructionRow['review_after'] ?? ''));
        $instructionLower = strtolower($instructionText);

        $hasAdmissionReason = $this->hasAnyNonEmptyValue($complaintRows, ['comp_report', 'comp_remark'])
            || trim((string) ($complaintRemark['comp_remark'] ?? '')) !== '';
        $hasDiagnosis = $this->hasAnyNonEmptyValue($diagnosisRows, ['comp_report', 'comp_remark'])
            || trim((string) ($diagnosisRemark['comp_remark'] ?? '')) !== '';
        $hasCourse = $this->hasAnyNonEmptyValue($courseRows, ['comp_report', 'comp_remark'])
            || trim((string) ($courseRemark['comp_remark'] ?? '')) !== '';
        $hasProcedure = $this->hasAnyNonEmptyValue($surgeryRows, ['surgery_name', 'surgery_remark'])
            || $this->hasAnyNonEmptyValue($procedureRows, ['procedure_name', 'procedure_remark']);
        $hasCondition = $this->hasAnyNonEmptyValue($conditionRows, ['value']);
        $hasMedication = $this->hasAnyNonEmptyValue($drugRows, ['drug_name'])
            || $this->hasAnyNonEmptyValue($legacyMedRows, ['med_name']);
        $hasFollowUp = $reviewAfter !== '' || $instructionText !== '';
        $hasAllergyAdr = trim((string) ($opdSnapshot['drug_allergy_status'] ?? '')) !== ''
            || trim((string) ($opdSnapshot['drug_allergy_details'] ?? '')) !== ''
            || trim((string) ($opdSnapshot['adr_history'] ?? '')) !== '';
        $hasRedFlags = strpos($instructionLower, 'emergency') !== false
            || strpos($instructionLower, 'warning') !== false
            || strpos($instructionLower, 'red flag') !== false
            || strpos($instructionLower, 'immediately') !== false
            || strpos($instructionLower, 'return if') !== false;

        $items = [
            [
                'key' => 'admission_reason',
                'label' => 'Reason for admission / presenting complaints',
                'ok' => $hasAdmissionReason,
                'critical' => true,
            ],
            [
                'key' => 'final_diagnosis',
                'label' => 'Final diagnosis documented',
                'ok' => $hasDiagnosis,
                'critical' => true,
            ],
            [
                'key' => 'hospital_course',
                'label' => 'Course / treatment in hospital documented',
                'ok' => $hasCourse,
                'critical' => true,
            ],
            [
                'key' => 'procedure_documentation',
                'label' => 'Surgery/procedure documented (if applicable)',
                'ok' => $hasProcedure,
                'critical' => false,
            ],
            [
                'key' => 'condition_at_discharge',
                'label' => 'Condition at discharge documented',
                'ok' => $hasCondition,
                'critical' => true,
            ],
            [
                'key' => 'discharge_medication',
                'label' => 'Discharge medication documented',
                'ok' => $hasMedication,
                'critical' => true,
            ],
            [
                'key' => 'follow_up_plan',
                'label' => 'Follow-up instructions / review plan documented',
                'ok' => $hasFollowUp,
                'critical' => true,
            ],
            [
                'key' => 'allergy_adr',
                'label' => 'Drug allergy / ADR history documented',
                'ok' => $hasAllergyAdr,
                'critical' => false,
            ],
            [
                'key' => 'red_flags',
                'label' => 'Red-flag / emergency return advice documented',
                'ok' => $hasRedFlags,
                'critical' => false,
            ],
        ];

        $criticalMissing = [];
        $okCount = 0;
        foreach ($items as $item) {
            if (! empty($item['ok'])) {
                $okCount++;
                continue;
            }
            if (! empty($item['critical'])) {
                $criticalMissing[] = (string) ($item['label'] ?? '');
            }
        }

        return [
            'items' => $items,
            'ok_count' => $okCount,
            'total_count' => count($items),
            'critical_missing' => $criticalMissing,
            'critical_missing_count' => count($criticalMissing),
        ];
    }

    private function firstRowByIpd(string $table, int $ipdId): array
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns($table, ['ipd_id'])) {
            return [];
        }

        $row = $this->db->table($table)
            ->where('ipd_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return is_array($row) ? $row : [];
    }

    private function upsertByIpd(string $table, int $ipdId, array $data): bool
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns($table, ['ipd_id'])) {
            return false;
        }

        try {
            $builder = $this->db->table($table);
            $existing = $this->firstRowByIpd($table, $ipdId);

            if (! empty($existing['id'])) {
                return (bool) $builder->where('id', (int) $existing['id'])->update($data);
            }

            $insert = array_merge(['ipd_id' => $ipdId], $data);

            return (bool) $builder->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Discharge upsert failed in {table} for IPD {ipd}: {msg}', [
                'table' => $table,
                'ipd' => $ipdId,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function upsertByIpdField(string $table, string $ipdField, int $ipdId, array $data): bool
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns($table, [$ipdField])) {
            return false;
        }

        try {
            $builder = $this->db->table($table);
            $existing = $builder
                ->where($ipdField, $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($existing['id'])) {
                return (bool) $builder->where('id', (int) $existing['id'])->update($data);
            }

            $insert = array_merge([$ipdField => $ipdId], $data);

            return (bool) $builder->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Discharge upsertByIpdField failed in {table}: {msg}', [
                'table' => $table,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function toDbDate(string $input): ?string
    {
        $value = trim($input);
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    private function ensureDischargeSurgeryMasterTable(): bool
    {
        if ($this->db->tableExists('ipd_discharge_surgery_master')) {
            return true;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS ipd_discharge_surgery_master (
                id INT AUTO_INCREMENT PRIMARY KEY,
                term_type VARCHAR(20) NOT NULL DEFAULT 'surgery',
                term_name VARCHAR(255) NOT NULL,
                term_code VARCHAR(60) DEFAULT NULL,
                icd_code VARCHAR(60) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY uniq_term_type_name (term_type, term_name),
                INDEX idx_type_active_name (term_type, is_active, term_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->db->tableExists('ipd_discharge_surgery_master');
    }

    private function ensureDischargeIcdMasterTable(): bool
    {
        if ($this->db->tableExists('ipd_discharge_icd_master')) {
            return true;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS ipd_discharge_icd_master (
                id INT AUTO_INCREMENT PRIMARY KEY,
                icd_code VARCHAR(30) NOT NULL,
                diagnosis_text VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY uniq_icd_code_text (icd_code, diagnosis_text),
                INDEX idx_icd_active_code (is_active, icd_code),
                INDEX idx_icd_active_text (is_active, diagnosis_text)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->db->tableExists('ipd_discharge_icd_master');
    }

    private function ensureDischargeFoodMasterTable(): bool
    {
        if ($this->db->tableExists('ipd_discharge_master_food')) {
            return true;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS ipd_discharge_master_food (
                id INT AUTO_INCREMENT PRIMARY KEY,
                food_short VARCHAR(255) NOT NULL,
                food_desc TEXT NULL,
                food_desc_lang TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->db->tableExists('ipd_discharge_master_food');
    }

    public function dietary_master_list()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (! $this->ensureDischargeFoodMasterTable()) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('ipd_discharge_master_food')
            ->select('id,food_short,food_desc,food_desc_lang');

        if ($q !== '') {
            $builder->groupStart()
                ->like('food_short', $q)
                ->orLike('food_desc', $q)
                ->orLike('food_desc_lang', $q)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy('id', 'ASC')
            ->limit(500)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function dietary_master_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->ensureDischargeFoodMasterTable()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to access dietary master table',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $id = (int) $this->request->getPost('id');
        $short = trim((string) $this->request->getPost('food_short'));
        $desc = trim((string) $this->request->getPost('food_desc'));
        $lang = trim((string) $this->request->getPost('food_desc_lang'));

        if ($short === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Short text is required',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $payload = [
            'food_short' => $short,
            'food_desc' => $desc !== '' ? $desc : $short,
            'food_desc_lang' => $lang !== '' ? $lang : null,
        ];

        try {
            $table = $this->db->table('ipd_discharge_master_food');
            if ($id > 0) {
                $ok = (bool) $table->where('id', $id)->update($payload);
            } else {
                $ok = (bool) $table->insert($payload);
                $id = (int) ($this->db->insertID() ?: 0);
            }

            return $this->response->setJSON([
                'update' => $ok ? 1 : 0,
                'id' => $id,
                'error_text' => $ok ? 'Dietary master saved' : 'Unable to save dietary master',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Save failed: ' . $e->getMessage(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    public function dietary_master_delete()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->ensureDischargeFoodMasterTable()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to access dietary master table',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid record id',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $ok = (bool) $this->db->table('ipd_discharge_master_food')->where('id', $id)->delete();
        return $this->response->setJSON([
            'update' => $ok ? 1 : 0,
            'error_text' => $ok ? 'Record deleted' : 'Unable to delete record',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function normalizeSurgeryTermType(string $type): string
    {
        $clean = strtolower(trim($type));
        return in_array($clean, ['procedure', 'surgery'], true) ? $clean : 'surgery';
    }

    public function surgery_master_lookup()
    {
        session_write_close();

        $type = $this->normalizeSurgeryTermType((string) $this->request->getGet('type'));
        $q = trim((string) $this->request->getGet('q'));
        $masterOnly = (int) $this->request->getGet('master_only') === 1;

        if ($q === '') {
            return $this->response->setJSON(['rows' => []]);
        }

        $cacheKey = 'surg_lkp_v4_' . md5($type . '_' . ($masterOnly ? 'm_' : 'all_') . mb_strtolower($q));
        try {
            if ($cached = cache($cacheKey)) {
                if (is_array($cached)) {
                    return $this->response->setJSON(['rows' => $cached]);
                }
            }
        } catch (\Throwable $e) {}

        $rows = [];
        $seen = [];
        $maxLimit = 12;

        if ($this->ensureDischargeSurgeryMasterTable()) {
            $masterRows = $this->db->table('ipd_discharge_surgery_master')
                ->select('id,term_type,term_name,term_code,icd_code')
                ->where('is_active', 1)
                ->where('term_type', $type)
                ->groupStart()
                    ->like('term_name', $q)
                    ->orLike('term_code', $q)
                    ->orLike('icd_code', $q)
                ->groupEnd()
                ->orderBy('term_name', 'ASC')
                ->limit($maxLimit)
                ->get()
                ->getResultArray();

            foreach ($masterRows as $row) {
                $name = (string) ($row['term_name'] ?? '');
                if ($name === '') continue;
                $k = mb_strtoupper($name);
                if (isset($seen[$k])) continue;
                $seen[$k] = true;
                $rows[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'term_type' => (string) ($row['term_type'] ?? $type),
                    'term_name' => $name,
                    'term_code' => (string) ($row['term_code'] ?? ''),
                    'icd_code' => (string) ($row['icd_code'] ?? ''),
                    'source' => 'master',
                ];
            }
        }

        // SNOMED CT Procedure / Surgery Fallback (only if not master_only)
        if (! $masterOnly && count($rows) < $maxLimit) {
            $snomedRows = (new \App\Libraries\CsnotkTerminologyService())->searchProcedure($q, $maxLimit);
            foreach ($snomedRows as $row) {
                $name = (string) ($row['term'] ?? $row['fsn'] ?? '');
                if ($name === '') continue;
                $conceptId = (string) ($row['concept_id'] ?? '');
                $k = mb_strtoupper($name);
                if (isset($seen[$k])) continue;
                $seen[$k] = true;
                $rows[] = [
                    'id' => 0,
                    'term_type' => $type,
                    'term_name' => $name,
                    'term_code' => $conceptId,
                    'icd_code' => '',
                    'source' => 'snomed_local',
                ];
                if (count($rows) >= $maxLimit) break;
            }
        }

        $res = array_slice($rows, 0, $maxLimit);
        try {
            cache()->save($cacheKey, $res, 86400 * 30);
        } catch (\Throwable $e) {}

        return $this->response->setJSON(['rows' => $res]);
    }

    public function surgery_master_list()
    {
        $type = $this->normalizeSurgeryTermType((string) $this->request->getGet('type'));
        $q = trim((string) $this->request->getGet('q'));

        if (! $this->ensureDischargeSurgeryMasterTable()) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('ipd_discharge_surgery_master')
            ->select('id,term_type,term_name,term_code,icd_code,is_active,updated_at')
            ->where('term_type', $type);

        if ($q !== '') {
            $builder->groupStart()
                ->like('term_name', $q)
                ->orLike('term_code', $q)
                ->orLike('icd_code', $q)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy('is_active', 'DESC')
            ->orderBy('term_name', 'ASC')
            ->limit(250)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function surgery_master_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->ensureDischargeSurgeryMasterTable()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to access surgery master table',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $id = (int) $this->request->getPost('id');
        $type = $this->normalizeSurgeryTermType((string) $this->request->getPost('type'));
        $name = trim((string) $this->request->getPost('name'));
        $code = trim((string) $this->request->getPost('code'));
        $icdCode = trim((string) $this->request->getPost('icd_code'));
        $isActive = (int) $this->request->getPost('is_active') === 0 ? 0 : 1;

        if ($name === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Name is required',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'term_type' => $type,
            'term_name' => $name,
            'term_code' => $code !== '' ? $code : null,
            'icd_code' => $icdCode !== '' ? $icdCode : null,
            'is_active' => $isActive,
            'updated_at' => $now,
        ];

        try {
            $table = $this->db->table('ipd_discharge_surgery_master');

            if ($id > 0) {
                $ok = (bool) $table->where('id', $id)->update($payload);
            } else {
                $payload['created_at'] = $now;
                $ok = (bool) $table->insert($payload);
                $id = (int) ($this->db->insertID() ?: 0);
            }

            if (! $ok) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Unable to save record',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return $this->response->setJSON([
                'update' => 1,
                'id' => $id,
                'error_text' => 'Master record saved',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Save failed: ' . $e->getMessage(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    public function surgery_master_delete()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->ensureDischargeSurgeryMasterTable()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to access surgery master table',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid record id',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $ok = (bool) $this->db->table('ipd_discharge_surgery_master')
            ->where('id', $id)
            ->delete();

        return $this->response->setJSON([
            'update' => $ok ? 1 : 0,
            'error_text' => $ok ? 'Record deleted' : 'Unable to delete record',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    // Course/Treatment Master CRUD
    private function ensureDischargeCourseMasterTable(): bool
    {
        if ($this->db->tableExists('ipd_discharge_course_master')) {
            return true;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS ipd_discharge_course_master (
                id INT AUTO_INCREMENT PRIMARY KEY,
                term_name VARCHAR(255) NOT NULL,
                term_code VARCHAR(60) DEFAULT NULL,
                icd_code VARCHAR(60) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY uniq_term_name (term_name),
                INDEX idx_active_name (is_active, term_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->db->tableExists('ipd_discharge_course_master');
    }

    public function course_master_lookup()
    {
        $q = trim((string) $this->request->getGet('q'));
        $masterOnly = (int) ($this->request->getGet('master_only') ?? 0);
        $rows = [];

        if ($q !== '') {
            if ($this->ensureDischargeCourseMasterTable()) {
                $dbRows = $this->db->table('ipd_discharge_course_master')
                    ->select('id, term_name, term_code, icd_code')
                    ->where('is_active', 1)
                    ->groupStart()
                    ->like('term_name', $q)
                    ->orLike('term_code', $q)
                    ->orLike('icd_code', $q)
                    ->groupEnd()
                    ->orderBy('term_name', 'ASC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();

                foreach ($dbRows as $row) {
                    $rows[] = [
                        'id' => (int) ($row['id'] ?? 0),
                        'term_name' => (string) ($row['term_name'] ?? ''),
                        'term_code' => (string) ($row['term_code'] ?? ''),
                        'icd_code' => (string) ($row['icd_code'] ?? ''),
                        'source' => 'master',
                    ];
                }
            }

            if ($masterOnly === 0 && count($rows) < 10 && method_exists($this, 'searchSnomedTerms')) {
                $snomedRows = $this->searchSnomedTerms($q, 10 - count($rows));
                foreach ($snomedRows as $s) {
                    $rows[] = [
                        'id' => 0,
                        'term_name' => (string) ($s['term'] ?? ''),
                        'term_code' => (string) ($s['concept_id'] ?? ''),
                        'icd_code' => '',
                        'source' => 'snomed',
                    ];
                }
            }
        }

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function course_master_list()
    {
        $q = trim((string) $this->request->getGet('q'));
        $rows = [];

        if (! $this->ensureDischargeCourseMasterTable()) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('ipd_discharge_course_master')
            ->select('id, term_name, term_code, icd_code, is_active');

        if ($q !== '') {
            $builder->groupStart()
                ->like('term_name', $q)
                ->orLike('term_code', $q)
                ->orLike('icd_code', $q)
                ->groupEnd();
        }

        $dbRows = $builder->orderBy('term_name', 'ASC')
            ->limit(100)
            ->get()
            ->getResultArray();

        foreach ($dbRows as $row) {
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'term_name' => (string) ($row['term_name'] ?? ''),
                'term_code' => (string) ($row['term_code'] ?? ''),
                'icd_code' => (string) ($row['icd_code'] ?? ''),
                'is_active' => (int) ($row['is_active'] ?? 1),
            ];
        }

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function course_master_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->ensureDischargeCourseMasterTable()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to access course master table',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        $code = trim((string) $this->request->getPost('code'));
        $icdCode = trim((string) $this->request->getPost('icd_code'));
        $isActive = (int) $this->request->getPost('is_active') === 0 ? 0 : 1;

        if ($name === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Name is required',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'term_name' => $name,
            'term_code' => $code !== '' ? $code : null,
            'icd_code' => $icdCode !== '' ? $icdCode : null,
            'is_active' => $isActive,
            'updated_at' => $now,
        ];

        try {
            $table = $this->db->table('ipd_discharge_course_master');

            if ($id > 0) {
                $ok = (bool) $table->where('id', $id)->update($payload);
            } else {
                $payload['created_at'] = $now;
                $ok = (bool) $table->insert($payload);
                $id = (int) ($this->db->insertID() ?: 0);
            }

            if (! $ok) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Unable to save record',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return $this->response->setJSON([
                'update' => 1,
                'id' => $id,
                'error_text' => 'Master record saved',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Save failed: ' . $e->getMessage(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    public function course_master_delete()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->ensureDischargeCourseMasterTable()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to access course master table',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid record id',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $ok = (bool) $this->db->table('ipd_discharge_course_master')
            ->where('id', $id)
            ->delete();

        return $this->response->setJSON([
            'update' => $ok ? 1 : 0,
            'error_text' => $ok ? 'Record deleted' : 'Unable to delete record',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function diagnosis_icd_lookup()
    {
        $q = trim((string) $this->request->getGet('q'));
        $rows = [];

        if ($q !== '') {
            if ($this->db->tableExists('disease_master')) {
                $fields = $this->db->getFieldNames('disease_master') ?? [];
                $select = ['Code', 'Name'];
                foreach (['snomed_concept_id', 'snomed_term'] as $field) {
                    if (in_array($field, $fields, true)) {
                        $select[] = $field;
                    }
                }

                $builder = $this->db->table('disease_master')
                    ->select(implode(',', $select))
                    ->groupStart()
                    ->like('Name', $q);
                if (in_array('snomed_term', $fields, true)) {
                    $builder->orLike('snomed_term', $q);
                }
                if (in_array('snomed_concept_id', $fields, true)) {
                    $builder->orLike('snomed_concept_id', $q);
                }
                $builder->groupEnd();
                if (in_array('is_active', $fields, true)) {
                    $builder->where('is_active', 1);
                }

                foreach ($builder->orderBy('Name', 'ASC')->limit(20)->get()->getResultArray() as $row) {
                    $rows[] = [
                        'id' => (int) ($row['Code'] ?? 0),
                        'master_code' => (int) ($row['Code'] ?? 0),
                        'name' => (string) ($row['Name'] ?? ''),
                        'icd_code' => '',
                        'snomed_concept_id' => (string) ($row['snomed_concept_id'] ?? ''),
                        'snomed_term' => (string) ($row['snomed_term'] ?? ''),
                        'source' => 'disease_master',
                    ];
                }
            }

            if ($this->ensureDischargeIcdMasterTable()) {
                $icdRows = $this->db->table('ipd_discharge_icd_master')
                    ->select('id,icd_code,diagnosis_text')
                    ->where('is_active', 1)
                    ->groupStart()
                    ->like('icd_code', $q)
                    ->orLike('diagnosis_text', $q)
                    ->groupEnd()
                    ->orderBy('icd_code', 'ASC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();

                foreach ($icdRows as $row) {
                    $rows[] = [
                        'id' => (int) ($row['id'] ?? 0),
                        'master_code' => 0,
                        'name' => (string) ($row['diagnosis_text'] ?? ''),
                        'icd_code' => (string) ($row['icd_code'] ?? ''),
                        'snomed_concept_id' => '',
                        'snomed_term' => '',
                        'source' => 'icd_master',
                    ];
                }
            }
        }

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function diagnosis_icd_seed_starter()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->ensureDischargeIcdMasterTable()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to access ICD master table',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $seedRows = [
            ['icd_code' => 'I10', 'diagnosis_text' => 'Primary hypertension'],
            ['icd_code' => 'E11.9', 'diagnosis_text' => 'Type 2 diabetes mellitus without complications'],
            ['icd_code' => 'J45.909', 'diagnosis_text' => 'Asthma, unspecified, uncomplicated'],
            ['icd_code' => 'K21.9', 'diagnosis_text' => 'Gastro-esophageal reflux disease without esophagitis'],
            ['icd_code' => 'N39.0', 'diagnosis_text' => 'Urinary tract infection, site not specified'],
            ['icd_code' => 'J18.9', 'diagnosis_text' => 'Pneumonia, unspecified organism'],
            ['icd_code' => 'A09', 'diagnosis_text' => 'Infectious gastroenteritis and colitis, unspecified'],
            ['icd_code' => 'D64.9', 'diagnosis_text' => 'Anemia, unspecified'],
            ['icd_code' => 'M54.5', 'diagnosis_text' => 'Low back pain'],
            ['icd_code' => 'R50.9', 'diagnosis_text' => 'Fever, unspecified'],
            ['icd_code' => 'R07.9', 'diagnosis_text' => 'Chest pain, unspecified'],
            ['icd_code' => 'R51.9', 'diagnosis_text' => 'Headache, unspecified'],
            ['icd_code' => 'K59.0', 'diagnosis_text' => 'Constipation'],
            ['icd_code' => 'K52.9', 'diagnosis_text' => 'Noninfective gastroenteritis and colitis, unspecified'],
            ['icd_code' => 'J06.9', 'diagnosis_text' => 'Acute upper respiratory infection, unspecified'],
        ];

        // Add historical ICD-tagged diagnoses already used in this installation.
        if ($this->tableHasColumns('ipd_discharge_diagnosis', ['comp_report'])) {
            $historic = $this->db->table('ipd_discharge_diagnosis')
                ->select('comp_report')
                ->where('comp_report IS NOT NULL', null, false)
                ->where('comp_report !=', '')
                ->orderBy('id', 'DESC')
                ->limit(1000)
                ->get()
                ->getResultArray();

            foreach ($historic as $row) {
                $report = trim((string) ($row['comp_report'] ?? ''));
                if ($report === '') {
                    continue;
                }

                if (preg_match('/\[\s*ICD\s*:\s*([^\]]+)\]/i', $report, $matches) !== 1) {
                    continue;
                }

                $icdCode = strtoupper(trim((string) ($matches[1] ?? '')));
                $diagnosis = trim((string) preg_replace('/\[\s*ICD\s*:[^\]]+\]/i', '', $report));
                if ($icdCode === '' || $diagnosis === '') {
                    continue;
                }

                $seedRows[] = [
                    'icd_code' => $icdCode,
                    'diagnosis_text' => $diagnosis,
                ];
            }
        }

        $seen = [];
        $inserted = 0;
        $skipped = 0;
        $table = $this->db->table('ipd_discharge_icd_master');
        $now = date('Y-m-d H:i:s');

        foreach ($seedRows as $row) {
            $icdCode = strtoupper(trim((string) ($row['icd_code'] ?? '')));
            $diagnosis = trim((string) ($row['diagnosis_text'] ?? ''));
            if ($icdCode === '' || $diagnosis === '') {
                $skipped++;
                continue;
            }

            $key = $icdCode . '|' . strtoupper($diagnosis);
            if (isset($seen[$key])) {
                $skipped++;
                continue;
            }
            $seen[$key] = true;

            $exists = $table
                ->select('id')
                ->where('icd_code', $icdCode)
                ->where('diagnosis_text', $diagnosis)
                ->get(1)
                ->getRowArray();

            if (! empty($exists['id'])) {
                $skipped++;
                continue;
            }

            $ok = (bool) $table->insert([
                'icd_code' => $icdCode,
                'diagnosis_text' => $diagnosis,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($ok) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'error_text' => $inserted > 0
                ? ('ICD starter loaded. Added ' . $inserted . ' rows.')
                : 'ICD starter already present. No new rows added.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function update_complaint_field()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid request']);
        }

        $complaintId = (int) $this->request->getPost('complaint_id');
        $fieldType = trim((string) $this->request->getPost('field_type'));
        $fieldValue = trim((string) $this->request->getPost('field_value'));

        if ($complaintId <= 0 || !in_array($fieldType, ['name', 'remark'], true)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid parameters']);
        }

        $table = 'ipd_discharge_complaint';
        if (! $this->db->tableExists($table)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Complaint table not found']);
        }

        $columnName = $fieldType === 'name' ? 'comp_report' : 'comp_remark';
        
        try {
            $updateData = [$columnName => $fieldValue];
            
            // Only set update_by if column exists
            if ($this->db->fieldExists('update_by', $table)) {
                $updateData['update_by'] = $this->session->get('full_name') ?? 'System';
            }

            $updated = $this->db->table($table)
                ->where('id', $complaintId)
                ->update($updateData);

            if ($updated) {
                return $this->response->setJSON(['success' => true]);
            } else {
                return $this->response->setJSON(['success' => false, 'error' => 'No rows updated']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to update discharge complaint field: {msg}', ['msg' => $e->getMessage()]);
            return $this->response->setJSON(['success' => false, 'error' => 'Database error']);
        }
    }

    private function upsertByComposite(
        string $table,
        string $ipdField,
        int $ipdId,
        string $keyField,
        int $keyValue,
        array $data
    ): bool {
        if ($ipdId <= 0 || $keyValue <= 0 || ! $this->tableHasColumns($table, [$ipdField, $keyField])) {
            return false;
        }

        try {
            $builder = $this->db->table($table);
            $existing = $builder
                ->where($ipdField, $ipdId)
                ->where($keyField, $keyValue)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($existing['id'])) {
                return (bool) $builder->where('id', (int) $existing['id'])->update($data);
            }

            $insert = array_merge($data, [
                $ipdField => $ipdId,
                $keyField => $keyValue,
            ]);

            return (bool) $builder->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Discharge composite upsert failed in {table}: {msg}', [
                'table' => $table,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function getPhysicalExamRows(int $ipdId): array
    {
        $group1 = [];
        $group2 = [];
        $generalAll = [];
        $sysRows = [];

        if ($this->tableHasColumns('ipd_discharge_general_exam_col', ['id', 'col_description'])) {
            $generalBuilder = $this->db->table('ipd_discharge_general_exam_col')
                ->select('id,col_description,col_name,col_pre_value,cat_group')
                ->orderBy($this->db->fieldExists('display_order', 'ipd_discharge_general_exam_col') ? 'display_order' : 'id', 'ASC')
                ->orderBy('id', 'ASC');

            if ($this->db->fieldExists('col_unit', 'ipd_discharge_general_exam_col')) {
                $generalBuilder->select('col_unit');
            }
            if ($this->db->fieldExists('col_type', 'ipd_discharge_general_exam_col')) {
                $generalBuilder->select('col_type');
            }
            if ($this->db->fieldExists('is_active', 'ipd_discharge_general_exam_col')) {
                $generalBuilder->where('is_active', 1);
            }

            $generalCols = $generalBuilder->get()->getResultArray();

            $rdataMap = [];
            if ($this->tableHasColumns('ipd_discharge_1_b', ['ipd_d_id', 'col_id', 'rdata'])) {
                $rRows = $this->db->table('ipd_discharge_1_b')
                    ->select('col_id,rdata')
                    ->where('ipd_d_id', $ipdId)
                    ->get()
                    ->getResultArray();
                foreach ($rRows as $r) {
                    $rdataMap[(int) ($r['col_id'] ?? 0)] = (string) ($r['rdata'] ?? '');
                }
            }

            foreach ($generalCols as $row) {
                $colId = (int) ($row['id'] ?? 0);
                if ($colId <= 0) {
                    continue;
                }
                $item = [
                    'id' => $colId,
                    'label' => (string) ($row['col_description'] ?? $row['col_name'] ?? ('Exam ' . $colId)),
                    'value' => (string) ($rdataMap[$colId] ?? (string) ($row['col_pre_value'] ?? '')),
                    'unit' => trim((string) ($row['col_unit'] ?? '')),
                    'type' => (int) ($row['col_type'] ?? 0),
                    'options' => (string) ($row['col_pre_value'] ?? ''),
                ];

                $generalAll[] = $item;

                if ((int) ($row['cat_group'] ?? 1) === 2) {
                    $group2[] = $item;
                } else {
                    $group1[] = $item;
                }
            }
        }

        if ($this->tableHasColumns('ipd_discharge_sys_exam', ['id', 'sys_exam_name'])) {
            $sysMaster = $this->db->table('ipd_discharge_sys_exam')
                ->select('id,sys_exam_name')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $sysValueMap = [];
            if ($this->tableHasColumns('ipd_discharge_1_a', ['ipd_d_id', 'head_id', 'rdata'])) {
                $sysSaved = $this->db->table('ipd_discharge_1_a')
                    ->select('head_id,rdata')
                    ->where('ipd_d_id', $ipdId)
                    ->get()
                    ->getResultArray();
                foreach ($sysSaved as $row) {
                    $sysValueMap[(int) ($row['head_id'] ?? 0)] = (string) ($row['rdata'] ?? '');
                }
            }

            foreach ($sysMaster as $row) {
                $sid = (int) ($row['id'] ?? 0);
                if ($sid <= 0) {
                    continue;
                }
                $sysRows[] = [
                    'id' => $sid,
                    'name' => (string) ($row['sys_exam_name'] ?? ('Systemic Exam ' . $sid)),
                    'value' => (string) ($sysValueMap[$sid] ?? ''),
                ];
            }
        }

        $nursingAdmission = $this->getNursingAdmissionSnapshot($ipdId);
        if (! empty($nursingAdmission)) {
            $group1 = $this->applyAdmissionVitalsToGeneralRows($group1, $nursingAdmission);
            $group2 = $this->applyAdmissionVitalsToGeneralRows($group2, $nursingAdmission);
            $generalAll = $this->applyAdmissionVitalsToGeneralRows($generalAll, $nursingAdmission);
        }

        return [
            'general_group_1' => $group1,
            'general_group_2' => $group2,
            'general_all' => $generalAll,
            'systemic' => $sysRows,
        ];
    }

    private function getNursingAdmissionSnapshot(int $ipdId): array
    {
        if ($ipdId <= 0 || ! $this->db->tableExists('ipd_nursing_entries')) {
            return [];
        }

        $required = ['ipd_id', 'entry_type', 'recorded_at'];
        foreach ($required as $field) {
            if (! $this->db->fieldExists($field, 'ipd_nursing_entries')) {
                return [];
            }
        }

        $builder = $this->db->table('ipd_nursing_entries')
            ->where('ipd_id', $ipdId)
            ->whereIn('entry_type', ['admission', 'vitals'])
            ->orderBy('entry_type', 'DESC')
            ->orderBy('recorded_at', 'ASC')
            ->orderBy('id', 'ASC');

        $row = $builder->get(1)->getRowArray();
        return is_array($row) ? $row : [];
    }

    private function celsiusToFahrenheit(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        $f = ($value * 9 / 5) + 32;
        return rtrim(rtrim(number_format($f, 1, '.', ''), '0'), '.');
    }

    private function applyAdmissionVitalsToGeneralRows(array $rows, array $snapshot): array
    {
        if (empty($rows) || empty($snapshot)) {
            return $rows;
        }

        $pulse = trim((string) ($snapshot['pulse_rate'] ?? ''));
        $resp = trim((string) ($snapshot['resp_rate'] ?? ''));
        $spo2 = trim((string) ($snapshot['spo2'] ?? ''));
        $weight = trim((string) ($snapshot['weight_kg'] ?? ''));
        $sbp = trim((string) ($snapshot['bp_systolic'] ?? ''));
        $dbp = trim((string) ($snapshot['bp_diastolic'] ?? ''));
        $bp = $sbp !== '' || $dbp !== '' ? trim($sbp . ($dbp !== '' ? '/' . $dbp : '')) : '';

        $tempC = $snapshot['temperature_c'] ?? null;
        $temp = '';
        if ($tempC !== null && $tempC !== '') {
            $temp = $this->celsiusToFahrenheit((float) $tempC);
        }

        foreach ($rows as &$row) {
            $current = trim((string) ($row['value'] ?? ''));
            if ($current !== '') {
                continue;
            }

            $label = strtolower(trim((string) ($row['label'] ?? '')));
            if ($label === '') {
                continue;
            }

            if (strpos($label, 'pulse') !== false && $pulse !== '') {
                $row['value'] = $pulse;
                continue;
            }
            if ((strpos($label, 'resp') !== false || strpos($label, 'rr') !== false) && $resp !== '') {
                $row['value'] = $resp;
                continue;
            }
            if ((strpos($label, 'spo2') !== false || strpos($label, 'spo 2') !== false) && $spo2 !== '') {
                $row['value'] = $spo2;
                continue;
            }
            if ((strpos($label, 'temp') !== false || strpos($label, 'temperature') !== false) && $temp !== '') {
                $row['value'] = $temp;
                continue;
            }
            if (strpos($label, 'weight') !== false && $weight !== '') {
                $row['value'] = $weight;
                continue;
            }
            if ((strpos($label, 'bp') !== false || strpos($label, 'blood pressure') !== false) && $bp !== '') {
                if (strpos($label, 'diastolic') !== false && $dbp !== '') {
                    $row['value'] = $dbp;
                } elseif ((strpos($label, 'systolic') !== false || strpos($label, 'sys') !== false) && $sbp !== '') {
                    $row['value'] = $sbp;
                } else {
                    $row['value'] = $bp;
                }
            }
        }
        unset($row);

        return $rows;
    }

    private function getMappedColRows(
        string $masterTable,
        string $valueTable,
        int $ipdId,
        string $prefix,
        ?int $catGroup = null
    ): array {
        if (! $this->tableHasColumns($masterTable, ['id', 'col_description'])) {
            return [];
        }

        $builder = $this->db->table($masterTable)
            ->select('id,col_name,col_description,col_pre_value');

        if ($this->db->fieldExists('col_unit', $masterTable)) {
            $builder->select('col_unit');
        }

        if ($catGroup !== null && $this->db->fieldExists('cat_group', $masterTable)) {
            $builder->where('cat_group', $catGroup);
        }

        $masterRows = $builder->orderBy('id', 'ASC')->get()->getResultArray();

        $valueMap = [];
        if ($this->tableHasColumns($valueTable, ['ipd_d_id', 'col_id', 'rdata'])) {
            $savedRows = $this->db->table($valueTable)
                ->select('col_id,rdata')
                ->where('ipd_d_id', $ipdId)
                ->get()
                ->getResultArray();

            foreach ($savedRows as $row) {
                $valueMap[(int) ($row['col_id'] ?? 0)] = (string) ($row['rdata'] ?? '');
            }
        }

        $rows = [];
        foreach ($masterRows as $row) {
            $colId = (int) ($row['id'] ?? 0);
            if ($colId <= 0) {
                continue;
            }

            $rows[] = [
                'id' => $colId,
                'name' => (string) ($row['col_name'] ?? ''),
                'label' => (string) ($row['col_description'] ?? $row['col_name'] ?? ($prefix . ' ' . $colId)),
                'value' => (string) ($valueMap[$colId] ?? (string) ($row['col_pre_value'] ?? '')),
                'unit' => trim((string) ($row['col_unit'] ?? '')),
            ];
        }

        return $rows;
    }

    private function normalizeDateValue(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $raw = trim($raw, "'\"");
        if ($raw === '') {
            return null;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    private function normalizeCsvDateList(string $raw): array
    {
        $out = [];
        foreach (explode(',', $raw) as $part) {
            $dt = $this->normalizeDateValue($part);
            if ($dt !== null) {
                $out[$dt] = $dt;
            }
        }

        return array_values($out);
    }

    private function normalizeCsvIdList(string $raw): array
    {
        $out = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim((string) $part);
            if ($id > 0) {
                $out[$id] = $id;
            }
        }

        return array_values($out);
    }

    private function parseClinicalOtherExamPayload(string $raw): array
    {
        $raw = (string) $raw;
        $metaIds = [];

        if (preg_match('/\[\[CLINICAL_META\]\](.*?)\[\[\/CLINICAL_META\]\]\s*$/s', $raw, $m)) {
            $json = trim((string) ($m[1] ?? ''));
            $decoded = json_decode($json, true);
            if (is_array($decoded) && isset($decoded['non_path_ids']) && is_array($decoded['non_path_ids'])) {
                foreach ($decoded['non_path_ids'] as $id) {
                    $id = (int) $id;
                    if ($id > 0) {
                        $metaIds[$id] = $id;
                    }
                }
            }

            $raw = preg_replace('/\s*\[\[CLINICAL_META\]\].*?\[\[\/CLINICAL_META\]\]\s*$/s', '', $raw) ?? $raw;
        }

        return [
            'text' => trim($raw),
            'non_path_ids' => array_values($metaIds),
        ];
    }

    private function buildClinicalOtherExamPayload(string $text, array $nonPathIds): string
    {
        $text = trim($text);
        $normIds = [];
        foreach ($nonPathIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normIds[$id] = $id;
            }
        }

        if (empty($normIds)) {
            return $text;
        }

        $metaJson = json_encode([
            'non_path_ids' => array_values($normIds),
        ], JSON_UNESCAPED_SLASHES);

        if (! is_string($metaJson) || $metaJson === '') {
            return $text;
        }

        return trim($text . "\n\n[[CLINICAL_META]]" . $metaJson . '[[/CLINICAL_META]]');
    }

    private function plainTextFromHtml(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        // Preserve basic line breaks from common block/line tags before stripping HTML.
        $raw = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $raw) ?? $raw;
        $raw = preg_replace('/<\s*\/\s*(p|div|li|h[1-6]|tr)\s*>/i', "\n", $raw) ?? $raw;

        $text = strip_tags($raw);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{2,}/', "\n", $text) ?? $text;

        return trim($text);
    }

    private function getSavedClinicalLabDates(int $ipdId): array
    {
        if (! $this->tableHasColumns('ipd_discharge_2', ['ipd_d_id', 'lab_investigation_list'])) {
            return [];
        }

        $row = $this->db->table('ipd_discharge_2')
            ->select('lab_investigation_list')
            ->where('ipd_d_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $raw = (string) ($row['lab_investigation_list'] ?? '');

        return $this->normalizeCsvDateList($raw);
    }

    private function getClinicalInvestigationLabRows(int $patientId, string $admitDate, string $dischargeDate, array $selectedDates): array
    {
        if ($patientId <= 0) {
            return [];
        }

        if (! $this->tableHasColumns('invoice_master', ['id', 'attach_id', 'inv_date'])
            || ! $this->tableHasColumns('lab_request', ['id', 'charge_id'])
            || ! $this->tableHasColumns('lab_request_item', ['lab_request_id', 'lab_test_id'])
            || ! $this->tableHasColumns('lab_tests', ['mstTestKey', 'Test', 'TestID'])
            || ! $this->tableHasColumns('ipd_discharge_investigation_template', ['test_code'])) {
            return [];
        }

        $builder = $this->db->table('invoice_master m')
            ->select('DATE(m.inv_date) AS inv_date', false)
            ->select("DATE_FORMAT(MIN(m.inv_date),'%d-%m-%Y') AS inv_date_label", false)
            ->select('GROUP_CONCAT(DISTINCT t.Test ORDER BY t.Test SEPARATOR ",") AS test_list', false)
            ->join('lab_request l', 'm.id = l.charge_id', 'inner')
            ->join('lab_request_item i', 'l.id = i.lab_request_id', 'inner')
            ->join('lab_tests t', 'i.lab_test_id = t.mstTestKey', 'inner')
            ->join('ipd_discharge_investigation_template d', 't.TestID = d.test_code', 'inner')
            ->where('m.attach_id', $patientId)
            ->groupBy('DATE(m.inv_date)', false)
            ->orderBy('DATE(m.inv_date)', 'ASC', false);

        if ($admitDate !== '' && $dischargeDate !== '') {
            $builder->where('DATE(m.inv_date) >=', $admitDate)
                ->where('DATE(m.inv_date) <=', $dischargeDate);
        }

        $rows = $builder->get()->getResultArray();

        $selectedMap = [];
        foreach ($selectedDates as $dt) {
            $selectedMap[$dt] = true;
        }

        foreach ($rows as &$row) {
            $dateValue = (string) ($row['inv_date'] ?? '');
            $row['inv_date'] = $dateValue;
            $row['inv_date_label'] = (string) ($row['inv_date_label'] ?? $dateValue);
            $row['test_list'] = (string) ($row['test_list'] ?? '');
            $row['checked'] = isset($selectedMap[$dateValue]);
        }
        unset($row);

        return $rows;
    }

    private function getClinicalPathologyMatrixRows(int $patientId, array $selectedDates): array
    {
        if ($patientId <= 0 || empty($selectedDates)) {
            return [
                'dates' => [],
                'date_labels' => [],
                'rows' => [],
            ];
        }

        if (! $this->tableHasColumns('invoice_master', ['id', 'attach_id', 'inv_date'])
            || ! $this->tableHasColumns('lab_request', ['id', 'charge_id'])
            || ! $this->tableHasColumns('lab_request_item', ['lab_request_id', 'lab_test_id', 'lab_test_value'])
            || ! $this->tableHasColumns('lab_tests', ['mstTestKey', 'Test', 'TestID'])
            || ! $this->tableHasColumns('ipd_discharge_investigation_template', ['test_code'])) {
            return [
                'dates' => [],
                'date_labels' => [],
                'rows' => [],
            ];
        }

        $dateMap = [];
        foreach ($selectedDates as $dt) {
            $norm = $this->normalizeDateValue((string) $dt);
            if ($norm !== null) {
                $dateMap[$norm] = $norm;
            }
        }

        $dates = array_values($dateMap);
        sort($dates);
        if (empty($dates)) {
            return [
                'dates' => [],
                'date_labels' => [],
                'rows' => [],
            ];
        }

        $builder = $this->db->table('invoice_master m')
            ->select('DATE(m.inv_date) AS inv_date', false)
            ->select('t.TestID AS test_id, t.Test AS test_name, t.mstTestKey AS test_sort', false)
            ->select('i.lab_test_value', false)
            ->join('lab_request l', 'm.id = l.charge_id', 'inner')
            ->join('lab_request_item i', 'l.id = i.lab_request_id', 'inner')
            ->join('lab_tests t', 'i.lab_test_id = t.mstTestKey', 'inner')
            ->join('ipd_discharge_investigation_template d', 't.TestID = d.test_code', 'inner')
            ->where('m.attach_id', $patientId)
            ->orderBy('DATE(m.inv_date)', 'ASC', false)
            ->orderBy('t.mstTestKey', 'ASC');

        if ($this->db->fieldExists('FixedNormals', 'lab_tests')) {
            $builder->select('t.FixedNormals AS fixed_normals', false);
        } else {
            $builder->select("'' AS fixed_normals", false);
        }

        if ($this->tableHasColumns('ipd_discharge_investigation_template', ['group_test'])
            && $this->tableHasColumns('lab_rgroups', ['mstRGrpKey', 'sort_order'])) {
            $builder->join('lab_rgroups g', 'd.group_test = g.mstRGrpKey', 'left')
                ->select('COALESCE(g.sort_order, 9999) AS grp_sort', false)
                ->orderBy('grp_sort', 'ASC', false);
        } else {
            $builder->select('9999 AS grp_sort', false);
        }

        $escapedDates = [];
        foreach ($dates as $dt) {
            $escapedDates[] = $this->db->escape($dt);
        }
        $builder->where('DATE(m.inv_date) IN (' . implode(',', $escapedDates) . ')', null, false);

        $rawRows = $builder->get()->getResultArray();

        $rowsByTest = [];
        $orderByTest = [];
        foreach ($rawRows as $row) {
            $testId = trim((string) ($row['test_id'] ?? ''));
            if ($testId === '') {
                continue;
            }

            $date = (string) ($row['inv_date'] ?? '');
            if ($date === '') {
                continue;
            }

            if (! isset($rowsByTest[$testId])) {
                $rowsByTest[$testId] = [
                    'test_name' => (string) ($row['test_name'] ?? ''),
                    'fixed_normals' => (string) ($row['fixed_normals'] ?? ''),
                    'values' => [],
                ];

                $orderByTest[$testId] = [
                    'grp_sort' => (int) ($row['grp_sort'] ?? 9999),
                    'test_sort' => (int) ($row['test_sort'] ?? 0),
                    'test_name' => (string) ($row['test_name'] ?? ''),
                ];
            }

            $value = trim((string) ($row['lab_test_value'] ?? ''));
            if (! isset($rowsByTest[$testId]['values'][$date])) {
                $rowsByTest[$testId]['values'][$date] = [];
            }
            if ($value !== '' && ! in_array($value, $rowsByTest[$testId]['values'][$date], true)) {
                $rowsByTest[$testId]['values'][$date][] = $value;
            }
        }

        $orderedTestIds = array_keys($rowsByTest);
        usort($orderedTestIds, function ($a, $b) use ($orderByTest) {
            $oa = $orderByTest[$a] ?? ['grp_sort' => 9999, 'test_sort' => 0, 'test_name' => ''];
            $ob = $orderByTest[$b] ?? ['grp_sort' => 9999, 'test_sort' => 0, 'test_name' => ''];

            if ((int) $oa['grp_sort'] !== (int) $ob['grp_sort']) {
                return (int) $oa['grp_sort'] <=> (int) $ob['grp_sort'];
            }
            if ((int) $oa['test_sort'] !== (int) $ob['test_sort']) {
                return (int) $oa['test_sort'] <=> (int) $ob['test_sort'];
            }

            return strcmp((string) $oa['test_name'], (string) $ob['test_name']);
        });

        $matrixRows = [];
        foreach ($orderedTestIds as $testId) {
            $testRow = $rowsByTest[$testId];
            $values = [];
            foreach ($dates as $dt) {
                $vals = $testRow['values'][$dt] ?? [];
                $values[$dt] = empty($vals) ? '' : implode(', ', $vals);
            }

            $matrixRows[] = [
                'test_id' => $testId,
                'test_name' => (string) ($testRow['test_name'] ?? ''),
                'fixed_normals' => (string) ($testRow['fixed_normals'] ?? ''),
                'values' => $values,
            ];
        }

        $dateLabels = [];
        foreach ($dates as $dt) {
            $ts = strtotime($dt);
            $dateLabels[$dt] = $ts === false ? $dt : date('d-m-Y', $ts);
        }

        return [
            'dates' => $dates,
            'date_labels' => $dateLabels,
            'rows' => $matrixRows,
        ];
    }

    private function resolveClinicalNonPathModality(int $labType, string $reportName, string $impression): ?string
    {
        $byType = [
            1 => 'Sonography',
            2 => 'MRI',
            3 => 'X-Ray',
            4 => 'CT-Scan',
            6 => 'ECG',
        ];

        if (isset($byType[$labType])) {
            return $byType[$labType];
        }

        $haystack = strtolower($reportName . ' ' . $impression);
        if (strpos($haystack, 'x-ray') !== false || strpos($haystack, 'xray') !== false || strpos($haystack, 'radiology') !== false) {
            return 'X-Ray';
        }
        if (strpos($haystack, 'ecg') !== false || strpos($haystack, 'ekg') !== false || strpos($haystack, 'echo') !== false) {
            return 'ECG';
        }
        if (strpos($haystack, 'sonography') !== false || strpos($haystack, 'ultrasound') !== false || strpos($haystack, 'usg') !== false) {
            return 'Sonography';
        }
        if (strpos($haystack, 'ct') !== false || strpos($haystack, 'ct scan') !== false || strpos($haystack, 'ct-scan') !== false) {
            return 'CT-Scan';
        }
        if (strpos($haystack, 'mri') !== false) {
            return 'MRI';
        }

        return null;
    }

    private function getClinicalNonPathReportRows(int $patientId, string $admitDate, string $dischargeDate, array $selectedIds): array
    {
        if ($patientId <= 0) {
            return [];
        }

        if (! $this->tableHasColumns('invoice_master', ['id', 'attach_id', 'inv_date'])
            || ! $this->tableHasColumns('lab_request', ['id', 'charge_id', 'lab_type', 'Request_Date', 'report_name', 'report_data_Impression'])) {
            return [];
        }

        $rows = $this->db->table('invoice_master m')
            ->select('l.id AS lab_request_id, l.lab_type, l.report_name, l.report_data_Impression, l.Request_Date, m.inv_date', false)
            ->join('lab_request l', 'm.id = l.charge_id', 'inner')
            ->where('m.attach_id', $patientId)
            ->where('l.report_data_Impression IS NOT NULL', null, false)
            ->where('TRIM(l.report_data_Impression) <>', '')
            ->orderBy('m.inv_date', 'ASC')
            ->orderBy('l.id', 'ASC')
            ->get()
            ->getResultArray();

        $selectedMap = [];
        foreach ($selectedIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $selectedMap[$id] = true;
            }
        }

        $result = [];
        $seen = [];

        foreach ($rows as $row) {
            $requestId = (int) ($row['lab_request_id'] ?? 0);
            if ($requestId <= 0 || isset($seen[$requestId])) {
                continue;
            }

            $impression = $this->plainTextFromHtml((string) ($row['report_data_Impression'] ?? ''));
            if ($impression === '') {
                continue;
            }

            $requestDate = $this->normalizeDateValue((string) ($row['Request_Date'] ?? ''))
                ?? $this->normalizeDateValue((string) ($row['inv_date'] ?? ''));

            if ($requestDate === null) {
                continue;
            }

            if ($admitDate !== '' && strcmp($requestDate, $admitDate) < 0) {
                continue;
            }
            if ($dischargeDate !== '' && strcmp($requestDate, $dischargeDate) > 0) {
                continue;
            }

            $reportName = $this->plainTextFromHtml((string) ($row['report_name'] ?? ''));
            $modality = $this->resolveClinicalNonPathModality((int) ($row['lab_type'] ?? 0), $reportName, $impression);
            if ($modality === null) {
                continue;
            }

            if ($reportName === '') {
                $reportName = $modality . ' Report';
            }

            $result[] = [
                'lab_request_id' => $requestId,
                'report_date' => $requestDate,
                'report_date_label' => date('d-m-Y', strtotime($requestDate)),
                'modality' => $modality,
                'report_name' => $reportName,
                'impression' => $impression,
                'checked' => isset($selectedMap[$requestId]),
            ];
            $seen[$requestId] = true;
        }

        return $result;
    }

    private function parseInputDateToDb(string $rawDate): ?string
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $rawDate);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($rawDate);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    private function painScaleLabel(string $value): string
    {
        $map = [
            '0' => 'No Pain',
            '1' => 'Mild Pain',
            '2' => 'Moderate',
            '3' => 'Intense',
            '4' => 'Worst Pain Possible',
        ];

        return $map[$value] ?? '';
    }

    private function buildComplaintMetaPayload(array $meta): string
    {
        $painValue = trim((string) ($meta['pain_value'] ?? ''));
        if (! in_array($painValue, ['0', '1', '2', '3', '4'], true)) {
            return '';
        }

        return json_encode(['pain_value' => $painValue], JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function parseComplaintMetaPayload(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $painValue = trim((string) ($decoded['pain_value'] ?? ''));
        if (! in_array($painValue, ['0', '1', '2', '3', '4'], true)) {
            $painValue = '';
        }

        return ['pain_value' => $painValue];
    }

    private function buildInstructionMetaPayload(array $meta): string
    {
        $ids = $meta['food_ids'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }

        $cleanIds = [];
        foreach ($ids as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $cleanIds[$intId] = $intId;
            }
        }

        $otherText = trim((string) ($meta['other_text'] ?? ''));
        if (empty($cleanIds) && $otherText === '') {
            return '';
        }

        $nabh = $meta['nabh'] ?? [];
        if (! is_array($nabh)) {
            $nabh = [];
        }
        $nabhPayload = [
            'drug_allergy_status' => trim((string) ($nabh['drug_allergy_status'] ?? '')),
            'drug_allergy_details' => trim((string) ($nabh['drug_allergy_details'] ?? '')),
            'adr_history' => trim((string) ($nabh['adr_history'] ?? '')),
            'current_medications' => trim((string) ($nabh['current_medications'] ?? '')),
            'co_morbidities' => trim((string) ($nabh['co_morbidities'] ?? '')),
            'hpi_note' => trim((string) ($nabh['hpi_note'] ?? '')),
            'women_lmp' => trim((string) ($nabh['women_lmp'] ?? '')),
            'women_last_baby' => trim((string) ($nabh['women_last_baby'] ?? '')),
            'women_pregnancy_related' => trim((string) ($nabh['women_pregnancy_related'] ?? '')),
            'women_related_problems' => trim((string) ($nabh['women_related_problems'] ?? '')),
        ];

        $payload = [
            'food_ids' => array_values($cleanIds),
            'other_text' => $otherText,
            'nabh' => $nabhPayload,
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function parseInstructionMetaPayload(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['food_ids' => [], 'other_text' => '', 'nabh' => []];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return ['food_ids' => [], 'other_text' => '', 'nabh' => []];
        }

        $ids = $decoded['food_ids'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }

        $cleanIds = [];
        foreach ($ids as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $cleanIds[$intId] = $intId;
            }
        }

        $nabh = $decoded['nabh'] ?? [];
        if (! is_array($nabh)) {
            $nabh = [];
        }

        return [
            'food_ids' => array_values($cleanIds),
            'other_text' => trim((string) ($decoded['other_text'] ?? '')),
            'nabh' => [
                'drug_allergy_status' => trim((string) ($nabh['drug_allergy_status'] ?? '')),
                'drug_allergy_details' => trim((string) ($nabh['drug_allergy_details'] ?? '')),
                'adr_history' => trim((string) ($nabh['adr_history'] ?? '')),
                'current_medications' => trim((string) ($nabh['current_medications'] ?? '')),
                'co_morbidities' => trim((string) ($nabh['co_morbidities'] ?? '')),
                'hpi_note' => trim((string) ($nabh['hpi_note'] ?? '')),
                'women_lmp' => trim((string) ($nabh['women_lmp'] ?? '')),
                'women_last_baby' => trim((string) ($nabh['women_last_baby'] ?? '')),
                'women_pregnancy_related' => trim((string) ($nabh['women_pregnancy_related'] ?? '')),
                'women_related_problems' => trim((string) ($nabh['women_related_problems'] ?? '')),
            ],
        ];
    }

    private function parseFoodIdCsv(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim((string) $part);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function readInstructionFoodIdsFromRequest(): array
    {
        $posted = $this->request->getPost('instruction_food_ids');
        if (! is_array($posted)) {
            $posted = $this->request->getPost('instruction_food_ids[]');
        }
        if (! is_array($posted)) {
            $posted = [];
        }

        $clean = [];
        foreach ($posted as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $clean[$intId] = $intId;
            }
        }

        return array_values($clean);
    }

    private function extractNabhFieldsFromRemarks(string $remarks): array
    {
        $extract = static function (string $pattern, string $source): string {
            if (preg_match($pattern, $source, $m) !== 1) {
                return '';
            }

            return trim((string) ($m[1] ?? ''));
        };

        return [
            'drug_allergy_status' => $extract('/^\s*Drug\s*Allergy\s*Status\s*:\s*(.+)$/im', $remarks),
            'drug_allergy_details' => $extract('/^\s*Drug\s*Allergy\s*Details\s*:\s*(.+)$/im', $remarks),
            'adr_history' => $extract('/^\s*ADR\s*History\s*:\s*(.+)$/im', $remarks),
            'current_medications' => $extract('/^\s*Current\s*Medications\s*:\s*(.+)$/im', $remarks),
            'co_morbidities' => $extract('/^\s*Co-Morbidities\s*:\s*(.+)$/im', $remarks),
            'women_lmp' => $extract('/^\s*Women\s*Related\s*LMP\s*:\s*(.+)$/im', $remarks),
            'women_last_baby' => $extract('/^\s*Women\s*Related\s*Last\s*Baby\s*:\s*(.+)$/im', $remarks),
            'women_pregnancy_related' => $extract('/^\s*Women\s*Related\s*Pregnancy\s*Related\s*:\s*(.+)$/im', $remarks),
            'women_related_problems' => $extract('/^\s*Women\s*Related\s*Problems\s*:\s*(.+)$/im', $remarks),
            'hpi_note' => $extract('/^\s*HPI\s*Note\s*:\s*(.+)$/im', $remarks),
        ];
    }

    private function hydrateNabhFieldsFromOpdRow(array $row): array
    {
        $pick = static function (array $candidates) use ($row): string {
            foreach ($candidates as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }

            return '';
        };

        $remarks = trim((string) ($row['Prescriber_Remarks'] ?? ''));
        $parsed = $this->extractNabhFieldsFromRemarks($remarks);

        $result = [
            'drug_allergy_status' => $pick(['drug_allergy_status', 'allergy_status', 'drug_allergy']),
            'drug_allergy_details' => $pick(['drug_allergy_details', 'allergy_details', 'drug_allergy_note', 'allergy_note']),
            'adr_history' => $pick(['adr_history', 'adverse_drug_reaction', 'adr_details', 'adverse_reaction_history']),
            'current_medications' => $pick(['current_medications', 'current_medication', 'current_medication_history', 'ongoing_medications']),
            'co_morbidities' => (string) ($parsed['co_morbidities'] ?? ''),
            'women_lmp' => $pick(['women_lmp']),
            'women_last_baby' => $pick(['women_last_baby']),
            'women_pregnancy_related' => $pick(['women_pregnancy_related']),
            'women_related_problems' => $pick(['women_related_problems']),
            'hpi_note' => (string) ($parsed['hpi_note'] ?? ''),
            'pain_value' => trim((string) ($row['pain_value'] ?? '')),
        ];

        foreach (['drug_allergy_status', 'drug_allergy_details', 'adr_history', 'current_medications', 'women_lmp', 'women_last_baby', 'women_pregnancy_related', 'women_related_problems'] as $key) {
            if ($result[$key] === '' && ! empty($parsed[$key])) {
                $result[$key] = (string) $parsed[$key];
            }
        }

        if (! in_array($result['pain_value'], ['0', '1', '2', '3', '4'], true)) {
            $result['pain_value'] = '';
        }

        return $result;
    }

    private function getLatestOpdHistorySnapshot(int $patientId): array
    {
        $empty = [
            'drug_allergy_status' => '',
            'drug_allergy_details' => '',
            'adr_history' => '',
            'current_medications' => '',
            'co_morbidities' => '',
            'women_lmp' => '',
            'women_last_baby' => '',
            'women_pregnancy_related' => '',
            'women_related_problems' => '',
            'hpi_note' => '',
            'pain_value' => '',
            'pain_label' => '',
        ];

        if ($patientId <= 0 || ! $this->db->tableExists('opd_prescription')) {
            return $empty;
        }

        $row = $this->db->table('opd_prescription')
            ->where('p_id', $patientId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray() ?? [];

        if (empty($row)) {
            return $empty;
        }

        $hydrated = $this->hydrateNabhFieldsFromOpdRow($row);

        return [
            'drug_allergy_status' => (string) ($hydrated['drug_allergy_status'] ?? ''),
            'drug_allergy_details' => (string) ($hydrated['drug_allergy_details'] ?? ''),
            'adr_history' => (string) ($hydrated['adr_history'] ?? ''),
            'current_medications' => (string) ($hydrated['current_medications'] ?? ''),
            'co_morbidities' => (string) ($hydrated['co_morbidities'] ?? ''),
            'women_lmp' => (string) ($hydrated['women_lmp'] ?? ''),
            'women_last_baby' => (string) ($hydrated['women_last_baby'] ?? ''),
            'women_pregnancy_related' => (string) ($hydrated['women_pregnancy_related'] ?? ''),
            'women_related_problems' => (string) ($hydrated['women_related_problems'] ?? ''),
            'hpi_note' => (string) ($hydrated['hpi_note'] ?? ''),
            'pain_value' => (string) ($hydrated['pain_value'] ?? ''),
            'pain_label' => $this->painScaleLabel((string) ($hydrated['pain_value'] ?? '')),
        ];
    }

    private function upsertLabeledLineInRemarks(string $remarks, string $label, string $value): string
    {
        $remarks = trim($remarks);
        $pattern = '/^\s*' . preg_quote($label, '/') . '\s*:\s*.*$/im';
        $remarks = preg_replace($pattern, '', $remarks) ?? $remarks;

        $lines = array_filter(array_map(static function (string $line): string {
            return trim($line);
        }, preg_split('/\R/', $remarks) ?: []), static function (string $line): bool {
            return $line !== '';
        });

        $value = trim($value);
        if ($value !== '') {
            $lines[] = $label . ': ' . $value;
        }

        return trim(implode(PHP_EOL, $lines));
    }

    private function isNoAllergyDataStatus(string $status): bool
    {
        $normalized = strtolower(trim($status));
        if ($normalized === '') {
            return true;
        }

        $noDataValues = [
            'allergies not known',
            'allergy not known',
            'drug allergy not known',
            'not known',
            'unknown',
            'none',
            'nil',
            'no',
            'n/a',
            'na',
        ];

        return in_array($normalized, $noDataValues, true);
    }

    private function saveNabhHistoryFromDischarge(int $patientId, array $payload): bool
    {
        if ($patientId <= 0 || ! $this->db->tableExists('opd_prescription')) {
            return false;
        }

        $fields = $this->db->getFieldNames('opd_prescription') ?? [];
        if (! in_array('p_id', $fields, true) || ! in_array('id', $fields, true)) {
            return false;
        }

        $latest = $this->db->table('opd_prescription')
            ->select('id,Prescriber_Remarks')
            ->where('p_id', $patientId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (! is_array($latest) || empty($latest['id'])) {
            return false;
        }

        $update = [];
        foreach (['drug_allergy_status', 'drug_allergy_details', 'adr_history', 'current_medications', 'women_lmp', 'women_last_baby', 'women_pregnancy_related', 'women_related_problems'] as $field) {
            if (in_array($field, $fields, true)) {
                $update[$field] = trim((string) ($payload[$field] ?? ''));
            }
        }

        if (in_array('Prescriber_Remarks', $fields, true)) {
            $remarks = (string) ($latest['Prescriber_Remarks'] ?? '');
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Drug Allergy Status', (string) ($payload['drug_allergy_status'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Drug Allergy Details', (string) ($payload['drug_allergy_details'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'ADR History', (string) ($payload['adr_history'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Current Medications', (string) ($payload['current_medications'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Co-Morbidities', (string) ($payload['co_morbidities'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Women Related LMP', (string) ($payload['women_lmp'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Women Related Last Baby', (string) ($payload['women_last_baby'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Women Related Pregnancy Related', (string) ($payload['women_pregnancy_related'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Women Related Problems', (string) ($payload['women_related_problems'] ?? ''));
            $remarks = $this->upsertLabeledLineInRemarks($remarks, 'HPI Note', (string) ($payload['hpi_note'] ?? ''));
            $update['Prescriber_Remarks'] = $remarks;
        }

        if ($update === []) {
            return false;
        }

        return (bool) $this->db->table('opd_prescription')
            ->where('id', (int) $latest['id'])
            ->update($update);
    }

    public function search_patient()
    {
        $permission = $this->requireAnyPermission([
            'ipd_discharge.view',
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        return view('ipd_discharge/search_patient', [
            'discharge_templates' => $this->getPrintableDischargeTemplateRows(),
        ]);
    }

    public function print_template_builder()
    {
        if ($resp = $this->requireAnyPermission(['template.discharge'])) {
            return $resp;
        }

        $mode = strtolower(trim((string) $this->request->getGet('mode')));
        if (! in_array($mode, ['list', 'edit'], true)) {
            $mode = 'list';
        }

        $this->ensureDischargeTemplateTable();
        $rows = $this->db->table('ipd_discharge_templates')
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if ($mode === 'list') {
            return view('ipd_discharge/discharge_template_list', ['rows' => $rows]);
        }

        $editId = (int) ($this->request->getGet('edit') ?? 0);
        $editRow = [];
        if ($editId > 0) {
            foreach ($rows as $row) {
                if ((int) ($row['id'] ?? 0) === $editId) {
                    $editRow = $row;
                    break;
                }
            }
        }

        return view('ipd_discharge/discharge_template_edit', [
            'rows' => $rows,
            'edit_row' => $editRow,
        ]);
    }

    public function discharge_template_rename_ajax()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0]);
        }
        if ($resp = $this->requireAnyPermission(['template.discharge'])) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0]);
        }
        $id   = (int) ($this->request->getPost('id') ?? 0);
        $name = trim((string) ($this->request->getPost('template_name') ?? ''));
        if ($id <= 0 || $name === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'ID and name required.',
                'csrfName' => csrf_token(), 'csrfHash' => csrf_hash()]);
        }
        $ok = (bool) $this->db->table('ipd_discharge_templates')->where('id', $id)->update(['template_name' => $name]);
        return $this->response->setJSON(['update' => $ok ? 1 : 0, 'error_text' => $ok ? 'Renamed.' : 'Unable to rename.',
            'csrfName' => csrf_token(), 'csrfHash' => csrf_hash()]);
    }

    public function search_patient_ajax()
    {
        $permission = $this->requireAnyPermission([
            'ipd_discharge.view',
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        $searchQuery = trim((string) ($this->request->getGet('q') ?? ''));
        
        if ($searchQuery === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Please enter a search term'
            ]);
        }

        try {
            // Escape search query for LIKE
            $escapedSearch = $this->db->escapeLikeString($searchQuery);
            $likePattern = '%' . $escapedSearch . '%';
            
            // First, get basic IPD and patient data
            $sql = "SELECT 
                im.id, im.ipd_code, im.register_date, im.discharge_date, 
                im.discarge_patient_status as discharge_status,
                p.id as p_id, p.p_code as uhid, p.p_fname, p.p_rname, 
                IF(p.gender = 1, 'Male', 'Female') as xgender,
                p.dob,
                p.age as p_age, 
                p.age_in_month,
                p.estimate_dob,
                DATEDIFF(COALESCE(im.discharge_date, CURDATE()), im.register_date) as no_days
            FROM ipd_master im
            LEFT JOIN patient_master p ON p.id = im.p_id
            WHERE (
                im.ipd_code LIKE ? OR
                p.p_code LIKE ? OR
                p.p_fname LIKE ? OR
                p.p_rname LIKE ? OR
                p.mphone1 LIKE ?
            )
            ORDER BY im.id DESC
            LIMIT 50";
            
            $records = $this->db->query($sql, [
                $likePattern, $likePattern, $likePattern, $likePattern, $likePattern
            ])->getResult();

            helper('age');

            // Enrich each record with bed, doctor, and formatted age info
            foreach ($records as $record) {
                $ipdId = (int) $record->id;
                
                // Calculate age using system age helper
                $record->age_display = function_exists('get_age_1')
                    ? trim((string) get_age_1($record->dob ?? null, $record->p_age ?? '', $record->age_in_month ?? '', $record->estimate_dob ?? '', $record->register_date ?? null))
                    : '';

                // Get bed info
                $bedSql = "SELECT 
                    CONCAT('Bed No :', COALESCE(b.bed_number, ''), ' [', COALESCE(w.ward_name, ''), ']') as Bed_Desc
                FROM bed_assignment_history bah
                LEFT JOIN bed_master b ON b.id = bah.bed_id
                LEFT JOIN ward_master w ON w.id = bah.ward_id
                WHERE bah.ipd_id = ?
                ORDER BY bah.id DESC
                LIMIT 1";
                
                $bedResult = $this->db->query($bedSql, [$ipdId])->getRow();
                $record->Bed_Desc = $bedResult->Bed_Desc ?? '';
                
                // Get doctor info
                $docSql = "SELECT 
                    GROUP_CONCAT(DISTINCT CONCAT_WS(' ', 'Dr.', d.p_fname, d.p_mname, d.p_lname) SEPARATOR ', ') as doc_name
                FROM ipd_master_doc_list i
                JOIN doctor_master d ON i.doc_id = d.id
                WHERE i.ipd_id = ?
                GROUP BY i.ipd_id";
                
                $docResult = $this->db->query($docSql, [$ipdId])->getRow();
                $record->doc_name = $docResult->doc_name ?? '';
            }

            return $this->response->setJSON([
                'success' => true,
                'records' => $records,
                'count' => count($records)
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'IPD Discharge Search Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Debug endpoint to check IPD master fields
     * Access via: /Ipd_discharge/debug_ipd_fields/{ipdId}
     */
    public function debug_ipd_fields(int $ipdId)
    {
        if ($ipdId <= 0) {
            return $this->response->setJSON(['error' => 'Invalid IPD ID']);
        }

        // Get all fields from ipd_master
        $ipdMaster = $this->db->table('ipd_master')
            ->where('id', $ipdId)
            ->get()
            ->getRow();

        if (!$ipdMaster) {
            return $this->response->setJSON(['error' => 'IPD not found']);
        }

        // Get panel info (what getIpdPanelInfo returns)
        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        $ipd = $panelData['ipd_info'] ?? null;

        // Check what fields exist
        $fields = [
            'Available Fields in ipd_master' => array_keys((array)$ipdMaster),
            'register_date' => $ipdMaster->register_date ?? 'NOT FOUND',
            'discharge_date' => $ipdMaster->discharge_date ?? 'NOT FOUND',
            'reg_time' => $ipdMaster->reg_time ?? 'NOT FOUND',
            'reg_time_bak' => $ipdMaster->reg_time_bak ?? 'NOT FOUND',
            'discharge_time' => $ipdMaster->discharge_time ?? 'NOT FOUND',
            'discharge_time_bak' => $ipdMaster->discharge_time_bak ?? 'NOT FOUND',
            'dept_id' => $ipdMaster->dept_id ?? 'NOT FOUND',
            'r_doc_id' => $ipdMaster->r_doc_id ?? 'NOT FOUND',
            'r_doc_name' => $ipdMaster->r_doc_name ?? 'NOT FOUND',
        ];

        // Check what getDischargeDepartmentName returns
        $fields['getDischargeDepartmentName()'] = $this->getDischargeDepartmentName($ipd);

        // Check what getDischargeDoctorNames returns
        $fields['getDischargeDoctorNames()'] = $this->getDischargeDoctorNames($ipd);

        // Check safeTime results
        $fields['safeTime(reg_time)'] = $this->safeTime((string)($ipdMaster->reg_time ?? ''));
        $fields['safeTime(discharge_time)'] = $this->safeTime((string)($ipdMaster->discharge_time ?? ''));

        return $this->response->setJSON($fields, JSON_PRETTY_PRINT);
    }

    /**
     * Debug endpoint to show discharge HTML source
     * Access via: /Ipd_discharge/debug_discharge_html/{ipdId}
     */
    public function debug_discharge_html(int $ipdId)
    {
        if ($ipdId <= 0) {
            return $this->response->setJSON(['error' => 'Invalid IPD ID']);
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $content = $this->getDischargeContent($ipdId);
        if (trim(strip_tags($content)) === '') {
            $content = $this->buildAutoDischargeContent($ipdId, $panelData);
        }

        $templatePack = $this->applyDischargeTemplate($content, $panelData);
        $renderedHtml = (string) ($templatePack['rendered_html'] ?? $content);
        $templateSettings = is_array($templatePack['selected_template_settings'] ?? null)
            ? $templatePack['selected_template_settings']
            : $this->defaultDischargeTemplateSettings();
        $templateTokenVars = $this->buildDischargeTemplateTokenVars($panelData, $content);
        $templateCss = trim((string) ($templateSettings['template_css'] ?? ''));
        $headerHtml = $this->applyDischargeTemplateTokens(
            trim((string) ($templateSettings['header_html'] ?? '')),
            $templateTokenVars
        );
        $footerHtml = $this->applyDischargeTemplateTokens(
            trim((string) ($templateSettings['footer_html'] ?? '')),
            $templateTokenVars
        );
        if ($templateCss !== '') {
            $headerHtml = $headerHtml !== '' ? '<style>' . $templateCss . '</style>' . $headerHtml : '';
            $footerHtml = $footerHtml !== '' ? '<style>' . $templateCss . '</style>' . $footerHtml : '';
            $renderedHtml = '<style>' . $templateCss . '</style>' . $renderedHtml;
        }
        $headerHtml = $this->sanitizeDischargePdfHtml($headerHtml, false);
        $footerHtml = $this->sanitizeDischargePdfHtml($footerHtml, false);
        $renderedHtml = $this->sanitizeDischargePdfHtml($renderedHtml);
        $selectedTemplateId = (int) ($templatePack['selected_template_id'] ?? 0);
        $printBaseUrl = site_url('Ipd_discharge/show_discharge/' . $ipdId);
        $templateQuery = $selectedTemplateId > 0 ? '?tpl=' . $selectedTemplateId : '';

        // Show HTML with syntax highlighting
        return $this->response
            ->setContentType('text/html')
            ->setBody(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Discharge HTML Source</title><style>' .
                'body{font-family:monospace;padding:20px;background:#f5f5f5;} ' .
                'h1{color:#333;} ' .
                'pre{background:#fff;padding:20px;border:1px solid #ddd;overflow:auto;white-space:pre-wrap;word-wrap:break-word;} ' .
                '</style></head><body>' .
                '<h1>Discharge Summary HTML Source (IPD ID: ' . $ipdId . ')</h1>' .
                '<p><a href="' . esc($printBaseUrl . '/1' . $templateQuery) . '" target="_blank" style="display:inline-block;margin-right:8px;padding:8px 12px;background:#b91c1c;color:#fff;text-decoration:none;border-radius:4px;">Print With Header</a>' .
                '<a href="' . esc($printBaseUrl . '/0' . $templateQuery) . '" target="_blank" style="display:inline-block;padding:8px 12px;background:#475569;color:#fff;text-decoration:none;border-radius:4px;">Print Without Header</a></p>' .
                '<h2>Header Preview</h2>' .
                '<div style="background:#fff;padding:20px;border:1px solid #ddd;">' . ($headerHtml !== '' ? $headerHtml : '<em>(empty header_html)</em>') . '</div>' .
                '<h2>Footer Preview</h2>' .
                '<div style="background:#fff;padding:20px;border:1px solid #ddd;">' . ($footerHtml !== '' ? $footerHtml : '<em>(empty footer_html)</em>') . '</div>' .
                '<h2>Rendered Body Preview</h2>' .
                '<div style="background:#fff;padding:20px;border:1px solid #ddd;">' . $renderedHtml . '</div>' .
                '<h2>Rendered Body Source</h2>' .
                '<pre>' . htmlspecialchars($renderedHtml, ENT_QUOTES, 'UTF-8') . '</pre>' .
                '</body></html>'
            );
    }

    public function debug_mpdf_input(int $ipdId, int $templateId = 0)
    {
        if ($resp = $this->requireAnyPermission(['template.discharge', 'ipd_discharge.view', 'billing.access'])) {
            return $resp;
        }

        $file = WRITEPATH . 'debug' . DIRECTORY_SEPARATOR . 'discharge_mpdf_input_' . $ipdId . '_' . $templateId . '.html';
        if ((int) ($this->request->getGet('build') ?? 0) === 1) {
            @unlink($file);
            @unlink(WRITEPATH . 'debug' . DIRECTORY_SEPARATOR . 'discharge_mpdf_runtime_' . $ipdId . '_' . $templateId . '.json');
            $previousGet = $_GET;
            $_GET['tpl'] = $templateId;
            $_GET['refresh'] = 1;
            unset($_GET['build']);
            $this->show_discharge($ipdId, 1);
            $_GET = $previousGet;
        }
        if (! is_file($file)) {
            return $this->response->setStatusCode(404)->setBody('Generate the PDF first, then reopen this debug view with ?build=1.');
        }

        $runtimeFile = WRITEPATH . 'debug' . DIRECTORY_SEPARATOR . 'discharge_mpdf_runtime_' . $ipdId . '_' . $templateId . '.json';
        $runtime = is_file($runtimeFile) ? (json_decode((string) file_get_contents($runtimeFile), true) ?: []) : [];

        return view('ipd_discharge/mpdf_raw_html', [
            'ipd_id' => $ipdId,
            'template_id' => $templateId,
            'html' => (string) file_get_contents($file),
            'runtime_header' => (string) ($runtime['header_html'] ?? ''),
            'runtime_footer' => (string) ($runtime['footer_html'] ?? ''),
        ]);
    }

    /**
     * Placeholder preview page: shows each placeholder and resolved content for an IPD.
     * Access via: /Ipd_discharge/placeholder_preview/{ipdId}?tpl={templateId}
     */
    public function placeholder_preview(int $ipdId = 0)
    {
        $permission = $this->requireAnyPermission([
            'ipd_discharge.view',
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
            'template.discharge',
        ]);
        if ($permission) {
            return $permission;
        }

        if ($ipdId <= 0) {
            $ipdId = (int) ($this->request->getGet('ipd_id') ?? 0);
        }
        $requestedTemplateId = (int) ($this->request->getGet('tpl') ?? 0);
        $forceRegenerate = (int) ($this->request->getGet('refresh') ?? 0) === 1;

        $placeholderInfo = [
            'H_address_1' => 'Hospital Address Line 1',
            'H_address_2' => 'Hospital Address Line 2',
            'H_logo' => 'Hospital Logo File Name',
            'H_logo_abs' => 'Hospital Logo Absolute Path',
            'hospital_name' => 'Hospital Name',
            'hospital_address' => 'Hospital Full Address',
            'hospital_phone' => 'Hospital Phone',
            'hospital_email' => 'Hospital Email',
            'PATIENT_TITLE' => 'Patient Title (Mr./Mrs./Miss etc.)',
            'PATIENT_NAME' => 'Patient Name',
            'UHID' => 'Patient UHID',
            'IPD_CODE' => 'IPD Number/Code',
            'AGE_GENDER' => 'Age / Gender',
            'GUARDIAN' => 'Guardian Combined Text',
            'GUARDIAN_RELATION' => 'Guardian Relation',
            'GUARDIAN_NAME' => 'Guardian Name',
            'PATIENT_ADDRESS' => 'Patient Address',
            'PATIENT_PHONE' => 'Patient Phone',
            'DEPARTMENT' => 'Department Name',
            'ADMIT_DATE' => 'Admission Date',
            'DISCHARGE_DATE' => 'Discharge Date',
            'ADMISSION_TIME' => 'Admission Time',
            'DISCHARGE_TIME' => 'Discharge Time',
            'ISDELIVERY' => 'Delivery Flag',
            'INSURANCE_COMPANY' => 'Insurance Company',
            'DOCTOR_NAMES' => 'Consultant/Doctor Names',
            'DOCTOR_NAME' => 'Doctor Name (alias)',
            'CURRENT_DATE' => 'Current Date',
            'PRINT_TIME' => 'Print Date/Time',
            'CONTENT' => 'Full Auto-Generated Discharge Content',
            'DISCHARGE_STATUS' => 'Document Heading from Discharge Status',
            'PATIENT_INFO_TABLE' => 'Pre-built Patient Information Table',
            'DISCHARGE_SUMMARY' => 'Main Discharge Summary Content',
            'FINAL_DIAGNOSIS' => 'Final Diagnosis Section',
            'SURGERY' => 'Surgery Section',
            'PROCEDURE' => 'Procedure Section',
            'PERSONAL_HISTORY' => 'Personal History Section',
            'PRESENTING_COMPLAINTS' => 'Presenting Complaints Section',
            'PAIN_MEASUREMENT_SCALE' => 'Pain Measurement Section',
            'GENERAL_EXAM_ADMISSION' => 'General Examination on Admission',
            'CLINICAL_INVESTIGATION_REPORTS' => 'Clinical Investigation Reports',
            'COURSE_IN_HOSPITAL' => 'Course in Hospital Section',
            'EXAMINATION_ON_DISCHARGE' => 'Examination on Discharge Section',
            'DRUG_ALLERGY_ADR' => 'Drug Allergy/ADR Section',
            'CO_MORBIDITIES' => 'Co-Morbidities Section',
            'DISCHARGE_MEDICATIONS' => 'Discharge Medications Section',
            'DIETARY_ADVICE' => 'Dietary Advice Section',
            'OTHER_ADVICE' => 'Other Advice Section',
            'REVIEW_AFTER' => 'Review After (days/text)',
            'FOLLOW_UP_INSTRUCTIONS' => 'Follow-up Instructions Section',
            'DISCHARGE_ADVICE' => 'Follow-up Advice (legacy alias)',
            'INSTRUCTION_REMARK' => 'Instruction Remark (legacy alias)',
        ];

        if ($ipdId <= 0) {
            $urlBase = site_url('Ipd_discharge/placeholder_preview');
            return $this->response
                ->setContentType('text/html')
                ->setBody(
                    '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Discharge Placeholder Preview</title>' .
                    '<style>body{font-family:Arial,sans-serif;margin:24px;background:#f8fafc;color:#111827;} .box{background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:16px;max-width:920px;} input{padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;} button{padding:8px 12px;border:1px solid #2563eb;background:#2563eb;color:#fff;border-radius:6px;cursor:pointer;} .muted{color:#6b7280;font-size:13px;}</style>' .
                    '</head><body><div class="box"><h2 style="margin-top:0;">Discharge Placeholder Preview</h2>' .
                    '<form method="get" action="' . esc($urlBase) . '">' .
                    '<label>IPD ID:</label> <input type="number" name="ipd_id" min="1" required>' .
                    ' <label style="margin-left:8px;">Template ID:</label> <input type="number" name="tpl" min="0" placeholder="Optional">' .
                    ' <button type="submit">Open Placeholder Table</button>' .
                    '</form><p class="muted">Use this page to see which placeholder resolves to what content for a given IPD.</p>' .
                    '</div></body></html>'
                );
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $content = $this->getDischargeContent($ipdId);
        if ($forceRegenerate || trim(strip_tags($content)) === '') {
            $content = $this->buildAutoDischargeContent($ipdId, $panelData);
            if (trim(strip_tags($content)) !== '') {
                $this->saveDischargeContent($ipdId, $content);
            }
        }

        $templatePack = $this->applyDischargeTemplate($content, $panelData, $requestedTemplateId > 0 ? $requestedTemplateId : null);
        $tokenVars = $this->buildDischargeTemplateTokenVars($panelData, $content);
        $selectedTemplateId = (int) ($templatePack['selected_template_id'] ?? 0);
        $selectedTemplateName = (string) ($templatePack['selected_template_name'] ?? '');
        $showLegacy = (int) ($this->request->getGet('show_legacy') ?? 0) === 1;
        $legacyTokens = [
            'FOLLOW_UP_INSTRUCTIONS',
            'DISCHARGE_ADVICE',
            'INSTRUCTION_REMARK',
        ];
        $legacyTokenSet = array_fill_keys($legacyTokens, true);

        $rowsHtml = '';
        foreach ($placeholderInfo as $token => $description) {
            $value = (string) ($tokenVars[$token] ?? '');
            $plain = trim(strip_tags($value));
            $status = $plain === '' ? 'Empty' : 'Filled';
            $statusColor = $plain === '' ? '#b45309' : '#166534';
            $display = $value === '' ? '<span style="color:#9ca3af;">(empty)</span>' : '<pre style="margin:0;white-space:pre-wrap;word-break:break-word;font-family:Consolas,monospace;">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</pre>';
            $isLegacyToken = isset($legacyTokenSet[$token]);
            $rowClassAttr = $isLegacyToken ? ' class="legacy-row"' : '';
            $rowStyleAttr = ($isLegacyToken && !$showLegacy) ? ' style="display:none;"' : '';

            $rowsHtml .= '<tr' . $rowClassAttr . $rowStyleAttr . '>'
                . '<td style="vertical-align:top;"><code>{{' . esc($token) . '}}</code></td>'
                . '<td style="vertical-align:top;">' . esc($description) . '</td>'
                . '<td style="vertical-align:top;"><span style="font-weight:600;color:' . $statusColor . ';">' . $status . '</span></td>'
                . '<td style="vertical-align:top;">' . $display . '</td>'
                . '</tr>';
        }

        $previewUrl = site_url('Ipd_discharge/preview_discharge_report/' . $ipdId) . ($selectedTemplateId > 0 ? ('?tpl=' . $selectedTemplateId) : '');
        $pdfUrl = site_url('Ipd_discharge/show_discharge/' . $ipdId . '/1') . ($selectedTemplateId > 0 ? ('?tpl=' . $selectedTemplateId) : '');

        return $this->response
            ->setContentType('text/html')
            ->setBody(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Discharge Placeholder Map - IPD ' . $ipdId . '</title>' .
                '<style>body{font-family:Arial,sans-serif;margin:20px;background:#f8fafc;color:#111827;} .head{background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:14px 16px;margin-bottom:12px;} table{width:100%;border-collapse:collapse;background:#fff;} th,td{border:1px solid #d1d5db;padding:8px 10px;} th{background:#f1f5f9;text-align:left;} .muted{color:#6b7280;font-size:12px;} .actions a{display:inline-block;margin-right:8px;color:#1d4ed8;text-decoration:none;} .actions a:hover{text-decoration:underline;} .toggle-wrap{margin-top:8px;font-size:13px;}</style>' .
                '</head><body>' .
                '<div class="head">' .
                '<h2 style="margin:0 0 8px 0;">Discharge Placeholder Mapping</h2>' .
                '<div><strong>IPD ID:</strong> ' . $ipdId . ' &nbsp; <strong>Template:</strong> ' . esc($selectedTemplateName) . ' (ID: ' . $selectedTemplateId . ')</div>' .
                '<div class="actions" style="margin-top:8px;">' .
                '<a href="' . esc(site_url('Ipd_discharge/placeholder_preview')) . '?ipd_id=' . $ipdId . '&tpl=' . $selectedTemplateId . '&refresh=1" target="_blank">Regenerate and Refresh</a>' .
                '<a href="' . esc($previewUrl) . '" target="_blank">Open Discharge Preview</a>' .
                '<a href="' . esc($pdfUrl) . '" target="_blank">Open PDF</a>' .
                '</div>' .
                '<div class="toggle-wrap"><label><input type="checkbox" id="toggle_legacy_placeholders"' . ($showLegacy ? ' checked' : '') . '> Show legacy placeholders</label> <span class="muted">(' . count($legacyTokens) . ' hidden by default)</span></div>' .
                '<div class="muted" style="margin-top:6px;">This table shows each placeholder and resolved value from current discharge data.</div>' .
                '</div>' .
                '<table><thead><tr><th style="width:220px;">Placeholder</th><th style="width:280px;">Content Source</th><th style="width:90px;">Status</th><th>Resolved Content</th></tr></thead><tbody>'
                . $rowsHtml .
                '</tbody></table>' .
                '<script>(function(){var toggle=document.getElementById("toggle_legacy_placeholders");if(!toggle){return;}var rows=document.querySelectorAll("tr.legacy-row");var apply=function(){for(var i=0;i<rows.length;i++){rows[i].style.display=toggle.checked?"table-row":"none";}};toggle.addEventListener("change",apply);apply();})();</script>' .
                '</body></html>'
            );
    }

    public function ipd_select(int $ipdId, int $reCreate = 0)
    {
        $permission = $this->requireAnyPermission([
            'ipd_discharge.manage',
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            if ($this->request->isAJAX() || $this->request->getPost('ajax_mode') === 'json') {
                return $this->response->setStatusCode(401)->setJSON([
                    'update' => 0,
                    'notice' => 'Session expired or permission denied. Please log in again.',
                ]);
            }
            return $permission;
        }

        if ($ipdId <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Invalid IPD id');
        }

        // For lightweight AJAX POST actions (add_drug, remove_drug, etc.) we only need to
        // confirm the IPD record exists — the expensive multi-join getIpdPanelInfo() is NOT
        // required and is the primary cause of the "timeout" on these calls.
        $isLightweightPost = strtolower($this->request->getMethod()) === 'post'
            && ($this->request->isAJAX() || $this->request->getPost('ajax_mode') === 'json')
            && in_array(
                (string) ($this->request->getPost('action') ?? ''),
                ['add_drug', 'remove_drug', 'remove_all_drugs', 'add_course', 'remove_course', 'apply_rx_group'],
                true
            );

        if ($isLightweightPost) {
            // Cheap existence check — avoids 6-table JOIN for simple row inserts/deletes.
            $ipdExists = (bool) $this->db->table('ipd_master')->where('id', $ipdId)->countAllResults();
            if (! $ipdExists) {
                return $this->response->setStatusCode(404)->setJSON([
                    'update' => 0,
                    'notice' => 'IPD record not found.',
                ]);
            }
            $panelData = []; // Not needed for lightweight actions
            $ipdMasterRow = $this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [];
            $patientId = (int) ($ipdMasterRow['p_id'] ?? 0);
        } else {
            $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
            if (empty($panelData)) {
                return $this->response->setStatusCode(404)->setBody('IPD not found');
            }
            // Reuse data already fetched by getIpdPanelInfo — avoids a second ipd_master query.
            $ipdMasterRow = (array) ($panelData['ipd_info'] ?? []);
            $patientId = (int) ($panelData['person_info']->id ?? $ipdMasterRow['p_id'] ?? 0);
        }

        $notice = '';
        $noticeType = 'success';
        $userLabel = substr($this->currentUserLabel(), 0, 50);

        // Skip heavy template loading for AJAX/JSON POST requests (add_drug, remove_drug, etc.)
        // Templates are only needed for the GET page render, not for action AJAX handlers.
        $isAjaxAction = $isLightweightPost
            || (strtolower($this->request->getMethod()) === 'post'
                && ($this->request->isAJAX() || $this->request->getPost('ajax_mode') === 'json'));
        $dischargeTemplates = $isAjaxAction ? [] : $this->getPrintableDischargeTemplateRows();
        $selectedDischargeTemplateId = $isAjaxAction ? 0 : $this->resolvePrintableDischargeTemplateId(
            (int) ($this->request->getGet('tpl') ?? 0),
            $dischargeTemplates
        );

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = (string) ($this->request->getPost('action') ?? 'save_main');
            $savedAny = false;
            $ajaxRowId = 0;
            $ajaxRowSource = '';

            if ((int) ($this->request->getPost('dietary_autosave') ?? 0) === 1) {
                $instructionFoodIds = $this->readInstructionFoodIdsFromRequest();

                $instructionRow = $this->firstRowByIpd('ipd_discharge_instructions', $ipdId);
                $existingMeta = $this->parseInstructionMetaPayload((string) ($instructionRow['comp_report'] ?? ''));
                $instructionOtherPosted = $this->request->getPost('instruction_other');
                $instructionOther = is_string($instructionOtherPosted)
                    ? trim($instructionOtherPosted)
                    : trim((string) ($existingMeta['other_text'] ?? ''));

                $instructionMeta = $this->buildInstructionMetaPayload([
                    'food_ids' => $instructionFoodIds,
                    'other_text' => $instructionOther,
                    'nabh' => is_array($existingMeta['nabh'] ?? null) ? ($existingMeta['nabh'] ?? []) : [],
                ]);

                if ($this->tableHasColumns('ipd_discharge_instructions', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_instructions', $ipdId, [
                        'comp_report' => $instructionMeta,
                        'comp_remark' => (string) ($instructionRow['comp_remark'] ?? ''),
                        'review_after' => (string) ($instructionRow['review_after'] ?? ''),
                        'footer_text' => (string) ($instructionRow['footer_text'] ?? ''),
                        'footer_banner' => (string) ($instructionRow['footer_banner'] ?? '0'),
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                if ($this->tableHasColumns('ipd_discharge_drug_food_interaction', ['ipd_id', 'food_id_list'])) {
                    $foodIds = [];
                    foreach ($instructionFoodIds as $foodId) {
                        $id = (int) $foodId;
                        if ($id > 0) {
                            $foodIds[$id] = $id;
                        }
                    }

                    $legacyData = [
                        'food_id_list' => implode(',', array_values($foodIds)),
                    ];
                    if ($this->db->fieldExists('food_text', 'ipd_discharge_drug_food_interaction')) {
                        $legacyData['food_text'] = (string) ($instructionRow['comp_remark'] ?? '');
                    }

                    $savedAny = $this->upsertByIpd('ipd_discharge_drug_food_interaction', $ipdId, $legacyData) || $savedAny;
                }

                if ($savedAny) {
                    $this->enqueueIpdDischargeSync($ipdId, $patientId, 'dietary_autosave');
                }

                return $this->response->setJSON([
                    'update' => $savedAny ? 1 : 0,
                    'error_text' => $savedAny ? 'Dietary advice saved.' : 'Unable to save dietary advice.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            if ($action === 'add_complaint') {
                $complaintName = trim((string) ($this->request->getPost('new_complaint_name') ?? ''));
                $complaintRemarkRow = trim((string) ($this->request->getPost('new_complaint_remark') ?? ''));
                $complaintRemarkText = trim((string) ($this->request->getPost('complaint_remark') ?? ''));
                $painValue = trim((string) ($this->request->getPost('pain_value') ?? ''));
                if (! in_array($painValue, ['0', '1', '2', '3', '4'], true)) {
                    $painValue = '';
                }

                if ($complaintName !== '' && $this->tableHasColumns('ipd_discharge_complaint', ['ipd_id'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'comp_code' => 0,
                        'comp_report' => $complaintName,
                        'comp_remark' => $complaintRemarkRow,
                        'update_by' => $userLabel,
                    ];

                    if ($this->db->fieldExists('order_id', 'ipd_discharge_complaint')) {
                        $insert['order_id'] = 0;
                    }

                    $savedAny = (bool) $this->db->table('ipd_discharge_complaint')->insert($insert);
                    $notice = $savedAny ? 'Complaint row added.' : 'Unable to add complaint row.';
                    $noticeType = $savedAny ? 'success' : 'warning';

                    if ($this->tableHasColumns('ipd_discharge_complaint_remark', ['ipd_id'])) {
                        $savedAny = $this->upsertByIpd('ipd_discharge_complaint_remark', $ipdId, [
                            'comp_report' => $this->buildComplaintMetaPayload(['pain_value' => $painValue]),
                            'comp_remark' => $complaintRemarkText,
                            'update_by' => $userLabel,
                        ]) || $savedAny;
                    }
                } else {
                    $notice = 'Enter complaint name before adding.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_complaint') {
                $removeId = (int) ($this->request->getPost('complaint_remove_id') ?? 0);
                $complaintRemarkText = trim((string) ($this->request->getPost('complaint_remark') ?? ''));
                $painValue = trim((string) ($this->request->getPost('pain_value') ?? ''));
                if (! in_array($painValue, ['0', '1', '2', '3', '4'], true)) {
                    $painValue = '';
                }
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_complaint', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_complaint')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Complaint row removed.' : 'Unable to remove complaint row.';
                    $noticeType = $savedAny ? 'success' : 'warning';

                    if ($this->tableHasColumns('ipd_discharge_complaint_remark', ['ipd_id'])) {
                        $savedAny = $this->upsertByIpd('ipd_discharge_complaint_remark', $ipdId, [
                            'comp_report' => $this->buildComplaintMetaPayload(['pain_value' => $painValue]),
                            'comp_remark' => $complaintRemarkText,
                            'update_by' => $userLabel,
                        ]) || $savedAny;
                    }
                }
            } elseif ($action === 'add_surgery') {
                $name = trim((string) ($this->request->getPost('new_surgery_name') ?? ''));
                $date = $this->parseInputDateToDb((string) ($this->request->getPost('new_surgery_date') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_surgery_remark') ?? ''));
                $masterId = max(0, (int) ($this->request->getPost('new_surgery_master_id') ?? 0));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_surgery', ['ipd_id', 'surgery_name'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'surgery_name' => $name,
                        'surgery_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('surgery_date', 'ipd_discharge_surgery')) {
                        $insert['surgery_date'] = $date;
                    }
                    if ($this->db->fieldExists('surgery_id', 'ipd_discharge_surgery')) {
                        $insert['surgery_id'] = $masterId;
                    }
                    if ($this->db->fieldExists('surgery_by_doc_id', 'ipd_discharge_surgery')) {
                        $insert['surgery_by_doc_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_surgery')->insert($insert);
                    $notice = $savedAny ? 'Surgery row added.' : 'Unable to add surgery row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = $name === ''
                        ? 'Enter surgery name before adding.'
                        : 'Surgery table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_surgery') {
                $removeId = (int) ($this->request->getPost('surgery_remove_id') ?? 0);
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_surgery', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_surgery')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Surgery row removed.' : 'Unable to remove surgery row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Select a valid surgery row to remove.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'add_procedure') {
                $name = trim((string) ($this->request->getPost('new_procedure_name') ?? ''));
                $date = $this->parseInputDateToDb((string) ($this->request->getPost('new_procedure_date') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_procedure_remark') ?? ''));
                $masterId = max(0, (int) ($this->request->getPost('new_procedure_master_id') ?? 0));
                if ($name !== '' && $date !== null && $this->tableHasColumns('ipd_discharge_procedure', ['ipd_id', 'procedure_name'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'procedure_name' => $name,
                        'procedure_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('procedure_date', 'ipd_discharge_procedure')) {
                        $insert['procedure_date'] = $date;
                    }
                    if ($this->db->fieldExists('procedure_id', 'ipd_discharge_procedure')) {
                        $insert['procedure_id'] = $masterId;
                    }
                    if ($this->db->fieldExists('procedure_by_doc_id', 'ipd_discharge_procedure')) {
                        $insert['procedure_by_doc_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_procedure')->insert($insert);
                    $notice = $savedAny ? 'Procedure row added.' : 'Unable to add procedure row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    if ($name === '') {
                        $notice = 'Enter procedure name before adding.';
                    } elseif ($date === null) {
                        $notice = 'Select a valid procedure date before adding.';
                    } else {
                        $notice = 'Procedure table/columns are missing in database.';
                    }
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_procedure') {
                $removeId = (int) ($this->request->getPost('procedure_remove_id') ?? 0);
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_procedure', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_procedure')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Procedure row removed.' : 'Unable to remove procedure row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Select a valid procedure row to remove.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'add_diagnosis') {
                $name = trim((string) ($this->request->getPost('new_diagnosis_name') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_diagnosis_remark') ?? ''));
                $masterCode = max(0, (int) ($this->request->getPost('new_diagnosis_master_code') ?? 0));
                $snomedConceptId = trim((string) ($this->request->getPost('new_diagnosis_snomed_concept_id') ?? ''));
                $snomedTerm = trim((string) ($this->request->getPost('new_diagnosis_snomed_term') ?? ''));
                $diagnosisRemarkText = trim((string) ($this->request->getPost('diagnosis_remark') ?? ''));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_diagnosis', ['ipd_id', 'comp_report'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'comp_code' => $masterCode,
                        'comp_report' => $name,
                        'comp_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('snomed_concept_id', 'ipd_discharge_diagnosis')) {
                        $insert['snomed_concept_id'] = $snomedConceptId;
                    }
                    if ($this->db->fieldExists('snomed_term', 'ipd_discharge_diagnosis')) {
                        $insert['snomed_term'] = $snomedTerm;
                    }
                    if ($this->db->fieldExists('snomed_source', 'ipd_discharge_diagnosis')) {
                        $insert['snomed_source'] = $snomedConceptId !== '' ? 'disease_master' : '';
                    }
                    if ($this->db->fieldExists('order_id', 'ipd_discharge_diagnosis')) {
                        $insert['order_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_diagnosis')->insert($insert);
                    $notice = $savedAny ? 'Diagnosis row added.' : 'Unable to add diagnosis row.';
                    $noticeType = $savedAny ? 'success' : 'warning';

                    if ($this->tableHasColumns('ipd_discharge_diagnosis_remark', ['ipd_id'])) {
                        $savedAny = $this->upsertByIpd('ipd_discharge_diagnosis_remark', $ipdId, [
                            'comp_report' => '',
                            'comp_remark' => $diagnosisRemarkText,
                            'update_by' => $userLabel,
                        ]) || $savedAny;
                    }
                } else {
                    $notice = $name === ''
                        ? 'Enter diagnosis before adding.'
                        : 'Diagnosis table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_diagnosis') {
                $removeId = (int) ($this->request->getPost('diagnosis_remove_id') ?? 0);
                $diagnosisRemarkText = trim((string) ($this->request->getPost('diagnosis_remark') ?? ''));
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_diagnosis', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_diagnosis')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Diagnosis row removed.' : 'Unable to remove diagnosis row.';
                    $noticeType = $savedAny ? 'success' : 'warning';

                    if ($this->tableHasColumns('ipd_discharge_diagnosis_remark', ['ipd_id'])) {
                        $savedAny = $this->upsertByIpd('ipd_discharge_diagnosis_remark', $ipdId, [
                            'comp_report' => '',
                            'comp_remark' => $diagnosisRemarkText,
                            'update_by' => $userLabel,
                        ]) || $savedAny;
                    }
                } else {
                    $notice = 'Select a valid diagnosis row to remove.';
                    $noticeType = 'warning';
                }
            }

            if (($this->request->isAJAX() || $this->request->getPost('ajax_mode') === 'json') && in_array($action, ['add_surgery', 'remove_surgery', 'add_procedure', 'remove_procedure', 'add_diagnosis', 'remove_diagnosis'], true)) {
                $surgeryRows = $this->byIpdRows('ipd_discharge_surgery', ['id', 'surgery_name', 'surgery_date', 'surgery_remark'], 'id ASC', $ipdId);
                $procedureRows = $this->byIpdRows('ipd_discharge_procedure', ['id', 'procedure_name', 'procedure_date', 'procedure_remark'], 'id ASC', $ipdId);
                $diagnosisRows = $this->byIpdRows('ipd_discharge_diagnosis', ['id', 'comp_report', 'comp_remark'], 'id ASC', $ipdId);
                $courseRows = $this->byIpdRows('ipd_discharge_course', ['id', 'comp_report', 'comp_remark'], 'id ASC', $ipdId);
                return $this->response->setJSON([
                    'update' => ($savedAny ?? false) ? 1 : 0,
                    'notice' => $notice ?? '',
                    'noticeType' => $noticeType ?? 'info',
                    'row_id' => $ajaxRowId ?? 0,
                    'row_source' => $ajaxRowSource ?? 'legacy',
                    'surgeryRows' => $surgeryRows,
                    'procedureRows' => $procedureRows,
                    'diagnosisRows' => $diagnosisRows,
                    'courseRows' => $courseRows,
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            } elseif ($action === 'add_course') {
                $name = trim((string) ($this->request->getPost('new_course_name') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_course_remark') ?? ''));
                $courseRemarkText = trim((string) ($this->request->getPost('course_remark') ?? ''));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_course', ['ipd_id', 'comp_report'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'comp_code' => 0,
                        'comp_report' => $name,
                        'comp_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('order_id', 'ipd_discharge_course')) {
                        $insert['order_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_course')->insert($insert);
                    $notice = $savedAny ? 'Course row added.' : 'Unable to add course row.';
                    $noticeType = $savedAny ? 'success' : 'warning';

                    if ($this->tableHasColumns('ipd_discharge_course_remark', ['ipd_id'])) {
                        $savedAny = $this->upsertByIpd('ipd_discharge_course_remark', $ipdId, [
                            'comp_report' => '',
                            'comp_remark' => $courseRemarkText,
                            'update_by' => $userLabel,
                        ]) || $savedAny;
                    }
                } else {
                    $notice = $name === ''
                        ? 'Enter course/treatment text before adding.'
                        : 'Course table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_course') {
                $removeId = (int) ($this->request->getPost('course_remove_id') ?? 0);
                $courseRemarkText = trim((string) ($this->request->getPost('course_remark') ?? ''));
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_course', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_course')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Course row removed.' : 'Unable to remove course row.';
                    $noticeType = $savedAny ? 'success' : 'warning';

                    if ($this->tableHasColumns('ipd_discharge_course_remark', ['ipd_id'])) {
                        $savedAny = $this->upsertByIpd('ipd_discharge_course_remark', $ipdId, [
                            'comp_report' => '',
                            'comp_remark' => $courseRemarkText,
                            'update_by' => $userLabel,
                        ]) || $savedAny;
                    }
                } else {
                    $notice = 'Select a valid course row to remove.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'add_drug') {
                $name = trim((string) (
                    $this->request->getPost('new_drug_name')
                    ?? $this->request->getPost('med_name')
                    ?? $this->request->getPost('drug_name')
                    ?? ''
                ));
                $type = trim((string) (
                    $this->request->getPost('new_drug_type')
                    ?? $this->request->getPost('med_type')
                    ?? ''
                ));
                $dose = trim((string) (
                    $this->request->getPost('new_drug_dose')
                    ?? $this->request->getPost('dosage')
                    ?? ''
                ));
                $when = trim((string) (
                    $this->request->getPost('new_drug_when')
                    ?? $this->request->getPost('dosage_when')
                    ?? ''
                ));
                $freq = trim((string) (
                    $this->request->getPost('new_drug_freq')
                    ?? $this->request->getPost('dosage_freq')
                    ?? ''
                ));
                $day = trim((string) (
                    $this->request->getPost('new_drug_day')
                    ?? $this->request->getPost('no_of_days')
                    ?? ''
                ));
                $qty = trim((string) (
                    $this->request->getPost('new_drug_qty')
                    ?? $this->request->getPost('qty')
                    ?? ''
                ));
                $remark = trim((string) (
                    $this->request->getPost('new_drug_remark')
                    ?? $this->request->getPost('remark')
                    ?? ''
                ));
                $salt = trim((string) (
                    $this->request->getPost('new_drug_salt')
                    ?? $this->request->getPost('med_salt')
                    ?? ''
                ));
                $editId = (int) ($this->request->getPost('drug_edit_id') ?? $this->request->getPost('edit_id') ?? 0);
                $editSource = strtolower(trim((string) ($this->request->getPost('drug_edit_source') ?? 'legacy')));

                $legacyDrugTable = $this->findFirstExistingTable(['ipd_discharge_prescrption_prescribed', 'ipd_discharge_prescription_prescribed']);
                log_message('error', '[IPD_DISCHARGE_ADD_DRUG] ipdId=' . $ipdId . ', name=' . $name . ', editId=' . $editId . ', legacyTable=' . var_export($legacyDrugTable, true));

                if ($name !== '' && $editId > 0) {
                    $updated = false;

                    if ($editSource === 'legacy' && $legacyDrugTable !== null && $this->tableHasColumns($legacyDrugTable, ['id', 'ipd_id'])) {
                        $update = [
                            'med_name' => $name,
                            'med_salt' => $salt,
                            'med_type' => $type,
                            'dosage' => $dose,
                            'dosage_when' => $when,
                            'dosage_freq' => $freq,
                            'no_of_days' => $day,
                            'qty' => $qty,
                            'remark' => $remark,
                            'update_by' => $userLabel,
                        ];

                        $allowed = [];
                        foreach ($update as $field => $value) {
                            if ($this->db->fieldExists($field, $legacyDrugTable)) {
                                $allowed[$field] = $value;
                            }
                        }

                        if (! empty($allowed)) {
                            try {
                                $updated = (bool) $this->db->table($legacyDrugTable)
                                    ->where('id', $editId)
                                    ->where('ipd_id', $ipdId)
                                    ->update($allowed);
                            } catch (\Throwable $e) {
                                log_message('error', '[IPD_DISCHARGE_UPDATE_LEGACY_EX] ' . $e->getMessage());
                                $notice = 'Update DB Error: ' . $e->getMessage();
                            }
                        }

                        if ($updated) {
                            $ajaxRowId = $editId;
                            $ajaxRowSource = 'legacy';
                        }
                    }

                    if (! $updated && $this->tableHasColumns('ipd_discharge_drug', ['id', 'ipd_id'])) {
                        $doseText = trim(implode(' ', array_filter([$type, $dose, $when, $freq], static fn ($v) => trim((string) $v) !== '')));
                        $dayText = trim(implode(' ', array_filter([$day, $qty !== '' ? ('Qty:' . $qty) : '', $remark], static fn ($v) => trim((string) $v) !== '')));
                        $update = [
                            'drug_name' => $name,
                            'drug_dose' => $doseText,
                            'drug_day' => $dayText,
                            'update_by' => $userLabel,
                        ];

                        try {
                            $updated = (bool) $this->db->table('ipd_discharge_drug')
                                ->where('id', $editId)
                                ->where('ipd_id', $ipdId)
                                ->update($update);
                        } catch (\Throwable $e) {
                            log_message('error', '[IPD_DISCHARGE_UPDATE_CLASSIC_EX] ' . $e->getMessage());
                            $notice = 'Update DB Error: ' . $e->getMessage();
                        }

                        if ($updated) {
                            $ajaxRowId = $editId;
                            $ajaxRowSource = 'classic';
                        }
                    }

                    $savedAny = $updated;
                    $notice = $savedAny ? 'Medicine row updated.' : (empty($notice) ? 'Unable to update medicine row.' : $notice);
                    $noticeType = $savedAny ? 'success' : 'warning';
                } elseif ($name !== '' && $legacyDrugTable !== null && $this->tableHasColumns($legacyDrugTable, ['ipd_id', 'med_name'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'med_id' => 0,
                        'med_name' => $name,
                        'med_salt' => $salt,
                        'med_type' => $type,
                        'dosage' => $dose,
                        'dosage_when' => $when,
                        'dosage_freq' => $freq,
                        'no_of_days' => $day,
                        'qty' => $qty,
                        'remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('order_id', $legacyDrugTable)) {
                        $insert['order_id'] = 0;
                    }

                    $allowed = [];
                    foreach ($insert as $field => $value) {
                        if ($this->db->fieldExists($field, $legacyDrugTable)) {
                            $allowed[$field] = $value;
                        }
                    }

                    try {
                        $savedAny = ! empty($allowed)
                            ? (bool) $this->db->table($legacyDrugTable)->insert($allowed)
                            : false;
                        if ($savedAny) {
                            $ajaxRowId = (int) ($this->db->insertID() ?? 0);
                            $ajaxRowSource = 'legacy';
                        }
                        $notice = $savedAny ? 'Medicine row added.' : 'Unable to insert into table ' . $legacyDrugTable;
                    } catch (\Throwable $e) {
                        log_message('error', '[IPD_DISCHARGE_INSERT_LEGACY_EX] ' . $e->getMessage());
                        $savedAny = false;
                        $notice = 'Database error: ' . $e->getMessage();
                    }
                    $noticeType = $savedAny ? 'success' : 'warning';
                } elseif ($name !== '' && $this->tableHasColumns('ipd_discharge_drug', ['ipd_id', 'drug_name'])) {
                    $doseText = trim(implode(' ', array_filter([$type, $dose, $when, $freq], static fn ($v) => trim((string) $v) !== '')));
                    $dayText = trim(implode(' ', array_filter([$day, $qty !== '' ? ('Qty:' . $qty) : '', $remark], static fn ($v) => trim((string) $v) !== '')));

                    $insert = [
                        'ipd_id' => $ipdId,
                        'drug_code' => 0,
                        'drug_name' => $name,
                        'drug_dose' => $doseText,
                        'drug_day' => $dayText,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('order_id', 'ipd_discharge_drug')) {
                        $insert['order_id'] = 0;
                    }

                    try {
                        $savedAny = (bool) $this->db->table('ipd_discharge_drug')->insert($insert);
                        if ($savedAny) {
                            $ajaxRowId = (int) ($this->db->insertID() ?? 0);
                            $ajaxRowSource = 'classic';
                        }
                        $notice = $savedAny ? 'Drug row added.' : 'Unable to insert into ipd_discharge_drug.';
                    } catch (\Throwable $e) {
                        log_message('error', '[IPD_DISCHARGE_INSERT_CLASSIC_EX] ' . $e->getMessage());
                        $savedAny = false;
                        $notice = 'Database error: ' . $e->getMessage();
                    }
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = $name === ''
                        ? 'Enter drug name before adding.'
                        : 'Drug table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'apply_rx_group') {
                $rxGroupId = (int) ($this->request->getPost('selected_rx_group_id') ?? 0);
                $templateTable = $this->findFirstExistingTable(['opd_prescrption_prescribed_template', 'opd_prescription_prescribed_template']);
                $legacyDrugTable = $this->findFirstExistingTable(['ipd_discharge_prescrption_prescribed', 'ipd_discharge_prescription_prescribed']);
                $fallbackDrugTable = $this->tableHasColumns('ipd_discharge_drug', ['ipd_id', 'drug_name']) ? 'ipd_discharge_drug' : null;

                if ($rxGroupId <= 0) {
                    $notice = 'Select an Rx Group first.';
                    $noticeType = 'warning';
                } elseif ($templateTable === null) {
                    $notice = 'Rx Group medicine template table not found.';
                    $noticeType = 'warning';
                } elseif ($legacyDrugTable === null && $fallbackDrugTable === null) {
                    $notice = 'No discharge medicine table found.';
                    $noticeType = 'warning';
                } else {
                    $templateRows = $this->db->table($templateTable)
                        ->where('rx_group_id', $rxGroupId)
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    if (empty($templateRows)) {
                        $notice = 'No medicines found in selected Rx Group.';
                        $noticeType = 'warning';
                    } else {
                        $inserted = 0;

                        foreach ($templateRows as $row) {
                            $medName = trim((string) ($row['med_name'] ?? ''));
                            if ($medName === '') {
                                continue;
                            }

                            if ($legacyDrugTable !== null && $this->tableHasColumns($legacyDrugTable, ['ipd_id', 'med_name'])) {
                                $insert = [
                                    'ipd_id' => $ipdId,
                                    'med_id' => (int) ($row['med_id'] ?? 0),
                                    'med_name' => $medName,
                                    'med_salt' => trim((string) ($row['med_salt'] ?? $row['genericname'] ?? '')),
                                    'med_type' => trim((string) ($row['med_type'] ?? '')),
                                    'dosage' => trim((string) ($row['dosage'] ?? '')),
                                    'dosage_when' => trim((string) ($row['dosage_when'] ?? '')),
                                    'dosage_freq' => trim((string) ($row['dosage_freq'] ?? '')),
                                    'dosage_where' => trim((string) ($row['dosage_where'] ?? '')),
                                    'no_of_days' => trim((string) ($row['no_of_days'] ?? '')),
                                    'qty' => trim((string) ($row['qty'] ?? '')),
                                    'remark' => trim((string) ($row['remark'] ?? '')),
                                    'update_by' => $userLabel,
                                ];
                                if ($this->db->fieldExists('order_id', $legacyDrugTable)) {
                                    $insert['order_id'] = 0;
                                }

                                $allowed = [];
                                foreach ($insert as $field => $value) {
                                    if ($this->db->fieldExists($field, $legacyDrugTable)) {
                                        $allowed[$field] = $value;
                                    }
                                }

                                if (! empty($allowed) && $this->db->table($legacyDrugTable)->insert($allowed)) {
                                    $inserted++;
                                }
                                continue;
                            }

                            if ($fallbackDrugTable !== null) {
                                $doseText = trim(implode(' ', array_filter([
                                    trim((string) ($row['med_type'] ?? '')),
                                    trim((string) ($row['dosage'] ?? '')),
                                    trim((string) ($row['dosage_when'] ?? '')),
                                    trim((string) ($row['dosage_freq'] ?? '')),
                                ], static fn ($v) => trim((string) $v) !== '')));
                                $dayText = trim(implode(' ', array_filter([
                                    trim((string) ($row['no_of_days'] ?? '')),
                                    trim((string) ($row['qty'] ?? '')) !== '' ? ('Qty:' . trim((string) ($row['qty'] ?? ''))) : '',
                                    trim((string) ($row['remark'] ?? '')),
                                ], static fn ($v) => trim((string) $v) !== '')));

                                $insert = [
                                    'ipd_id' => $ipdId,
                                    'drug_code' => (int) ($row['med_id'] ?? 0),
                                    'drug_name' => $medName,
                                    'drug_dose' => $doseText,
                                    'drug_day' => $dayText,
                                    'update_by' => $userLabel,
                                ];
                                if ($this->db->fieldExists('order_id', $fallbackDrugTable)) {
                                    $insert['order_id'] = 0;
                                }

                                if ($this->db->table($fallbackDrugTable)->insert($insert)) {
                                    $inserted++;
                                }
                            }
                        }

                        $savedAny = $inserted > 0;
                        $notice = $savedAny
                            ? ($inserted . ' medicine(s) added from Rx Group.')
                            : 'No medicines could be added from selected Rx Group.';
                        $noticeType = $savedAny ? 'success' : 'warning';
                    }
                }
            } elseif ($action === 'remove_drug') {
                $removeId = (int) ($this->request->getPost('drug_remove_id') ?? 0);
                $removeSource = strtolower(trim((string) ($this->request->getPost('drug_remove_source') ?? 'legacy')));
                $removeName = trim((string) ($this->request->getPost('drug_remove_name') ?? ''));

                $deleted = false;
                $legacyDrugTable = $this->findFirstExistingTable(['ipd_discharge_prescrption_prescribed', 'ipd_discharge_prescription_prescribed']);

                if ($removeId > 0) {
                    if ($legacyDrugTable !== null && $this->tableHasColumns($legacyDrugTable, ['id', 'ipd_id'])) {
                        try {
                            $this->db->table($legacyDrugTable)
                                ->where('id', $removeId)
                                ->where('ipd_id', $ipdId)
                                ->delete();
                            if ($this->db->affectedRows() > 0) {
                                $deleted = true;
                            }
                        } catch (\Throwable $e) {
                            log_message('error', '[IPD_DISCHARGE_REMOVE_LEGACY_EX] ' . $e->getMessage());
                        }
                    }

                    if ($this->tableHasColumns('ipd_discharge_drug', ['id', 'ipd_id'])) {
                        try {
                            $this->db->table('ipd_discharge_drug')
                                ->where('id', $removeId)
                                ->where('ipd_id', $ipdId)
                                ->delete();
                            if ($this->db->affectedRows() > 0) {
                                $deleted = true;
                            }
                        } catch (\Throwable $e) {
                            log_message('error', '[IPD_DISCHARGE_REMOVE_CLASSIC_EX] ' . $e->getMessage());
                        }
                    }
                }

                if (! $deleted && $removeName !== '') {
                    if ($legacyDrugTable !== null && $this->tableHasColumns($legacyDrugTable, ['ipd_id', 'med_name'])) {
                        try {
                            $this->db->table($legacyDrugTable)
                                ->where('ipd_id', $ipdId)
                                ->where('med_name', $removeName)
                                ->delete();
                            if ($this->db->affectedRows() > 0) {
                                $deleted = true;
                            }
                        } catch (\Throwable $e) {}
                    }
                    if ($this->tableHasColumns('ipd_discharge_drug', ['ipd_id', 'drug_name'])) {
                        try {
                            $this->db->table('ipd_discharge_drug')
                                ->where('ipd_id', $ipdId)
                                ->where('drug_name', $removeName)
                                ->delete();
                            if ($this->db->affectedRows() > 0) {
                                $deleted = true;
                            }
                        } catch (\Throwable $e) {}
                    }
                }

                $savedAny = true;
                $ajaxRowId = $removeId;
                $ajaxRowSource = $removeSource;
                $notice = $deleted ? 'Medicine row removed from database.' : 'Medicine row removed from list.';
                $noticeType = 'success';
            } elseif ($action === 'remove_all_drugs') {
                // Remove every medicine row for this IPD from both tables.
                $legacyDrugTable = $this->findFirstExistingTable(['ipd_discharge_prescrption_prescribed', 'ipd_discharge_prescription_prescribed']);
                $deletedAny = false;

                if ($legacyDrugTable !== null && $this->tableHasColumns($legacyDrugTable, ['ipd_id'])) {
                    try {
                        $this->db->table($legacyDrugTable)->where('ipd_id', $ipdId)->delete();
                        if ($this->db->affectedRows() > 0) {
                            $deletedAny = true;
                        }
                    } catch (\Throwable $e) {
                        log_message('error', '[IPD_DISCHARGE_REMOVE_ALL_LEGACY_EX] ' . $e->getMessage());
                    }
                }

                if ($this->tableHasColumns('ipd_discharge_drug', ['ipd_id'])) {
                    try {
                        $this->db->table('ipd_discharge_drug')->where('ipd_id', $ipdId)->delete();
                        if ($this->db->affectedRows() > 0) {
                            $deletedAny = true;
                        }
                    } catch (\Throwable $e) {
                        log_message('error', '[IPD_DISCHARGE_REMOVE_ALL_CLASSIC_EX] ' . $e->getMessage());
                    }
                }

                $savedAny    = true;
                $ajaxRowId   = 0;
                $ajaxRowSource = '';
                $notice      = $deletedAny ? 'All medicines removed from database.' : 'No medicines found to remove.';
                $noticeType  = 'success';
            } else {

                if ($this->db->tableExists('ipd_master')) {
                    $masterUpdate = [];

                    if ($this->db->fieldExists('discarge_patient_status', 'ipd_master')) {
                        $masterUpdate['discarge_patient_status'] = (int) ($this->request->getPost('discarge_patient_status') ?? 0);
                    }
                    if ($this->db->fieldExists('discharge_date', 'ipd_master')) {
                        $masterUpdate['discharge_date'] = $this->toDbDate((string) ($this->request->getPost('discharge_date') ?? ''));
                    }
                    if ($this->db->fieldExists('discharge_time', 'ipd_master')) {
                        $masterUpdate['discharge_time'] = (string) ($this->request->getPost('discharge_time') ?? '');
                    }
                    if ($this->db->fieldExists('dept_id', 'ipd_master')) {
                        $masterUpdate['dept_id'] = (int) ($this->request->getPost('dept_id') ?? 0);
                    }

                    if (! empty($masterUpdate)) {
                        $savedAny = (bool) $this->db->table('ipd_master')->where('id', $ipdId)->update($masterUpdate) || $savedAny;
                    }
                }

                $complaintRemark = trim((string) ($this->request->getPost('complaint_remark') ?? ''));
                $painValue = trim((string) ($this->request->getPost('pain_value') ?? ''));
                if (! in_array($painValue, ['0', '1', '2', '3', '4'], true)) {
                    $painValue = '';
                }
                $diagnosisRemark = trim((string) ($this->request->getPost('diagnosis_remark') ?? ''));
                $courseRemark = trim((string) ($this->request->getPost('course_remark') ?? ''));
                $instructionRemark = trim((string) ($this->request->getPost('instruction_remark') ?? ''));
                $reviewAfter = trim((string) ($this->request->getPost('review_after') ?? ''));
                $instructionOther = trim((string) ($this->request->getPost('instruction_other') ?? ''));
                $instructionFoodIds = $this->readInstructionFoodIdsFromRequest();
                $instructionMeta = $this->buildInstructionMetaPayload([
                    'food_ids' => $instructionFoodIds,
                    'other_text' => $instructionOther,
                    'nabh' => [
                        'drug_allergy_status' => trim((string) ($this->request->getPost('drug_allergy_status') ?? '')),
                        'drug_allergy_details' => trim((string) ($this->request->getPost('drug_allergy_details') ?? '')),
                        'adr_history' => trim((string) ($this->request->getPost('adr_history') ?? '')),
                        'current_medications' => trim((string) ($this->request->getPost('current_medications') ?? '')),
                        'co_morbidities' => trim((string) ($this->request->getPost('co_morbidities') ?? '')),
                        'hpi_note' => trim((string) ($this->request->getPost('hpi_note') ?? '')),
                        'women_lmp' => trim((string) ($this->request->getPost('women_lmp') ?? '')),
                        'women_last_baby' => trim((string) ($this->request->getPost('women_last_baby') ?? '')),
                        'women_pregnancy_related' => trim((string) ($this->request->getPost('women_pregnancy_related') ?? '')),
                        'women_related_problems' => trim((string) ($this->request->getPost('women_related_problems') ?? '')),
                    ],
                ]);

                $complaintJson = trim((string) ($this->request->getPost('discharge_complaints_json') ?? ''));
                $decodedComplaints = [];
                if ($complaintJson !== '') {
                    $decoded = json_decode($complaintJson, true);
                    if (is_array($decoded)) {
                        $decodedComplaints = $decoded;
                    }
                }

                if (empty($decodedComplaints)) {
                    $postedIds = $this->request->getPost('complaint_row_id');
                    $postedTerms = $this->request->getPost('complaint_term');
                    $postedDurations = $this->request->getPost('complaint_duration');
                    $postedFreq = $this->request->getPost('complaint_frequency');
                    $postedSeverity = $this->request->getPost('complaint_severity');

                    if (is_array($postedTerms)) {
                        $count = count($postedTerms);
                        for ($i = 0; $i < $count; $i++) {
                            $decodedComplaints[] = [
                                'id' => (int) (is_array($postedIds) ? ($postedIds[$i] ?? 0) : 0),
                                'term' => (string) ($postedTerms[$i] ?? ''),
                                'duration' => (string) (is_array($postedDurations) ? ($postedDurations[$i] ?? '') : ''),
                                'frequency' => (string) (is_array($postedFreq) ? ($postedFreq[$i] ?? '') : ''),
                                'severity' => (string) (is_array($postedSeverity) ? ($postedSeverity[$i] ?? '') : ''),
                            ];
                        }
                    }
                }

                if (is_array($decodedComplaints) && $this->tableHasColumns('ipd_discharge_complaint', ['ipd_id', 'comp_report'])) {
                    {
                        $existingComplaintRows = $this->db->table('ipd_discharge_complaint')
                            ->select('id')
                            ->where('ipd_id', $ipdId)
                            ->get()
                            ->getResultArray();

                        $existingIdSet = [];
                        foreach ($existingComplaintRows as $row) {
                            $existingId = (int) ($row['id'] ?? 0);
                            if ($existingId > 0) {
                                $existingIdSet[$existingId] = true;
                            }
                        }

                        $keepIds = [];
                        $orderIndex = 1;

                        foreach ($decodedComplaints as $row) {
                            if (! is_array($row)) {
                                continue;
                            }

                            $term = trim((string) ($row['term'] ?? ''));
                            if ($term === '') {
                                continue;
                            }

                            $duration = trim((string) ($row['duration'] ?? ''));
                            $rowId = (int) ($row['id'] ?? 0);

                            $commonData = [
                                'comp_report' => $term,
                                'comp_remark' => $duration,
                                'update_by' => $userLabel,
                            ];
                            if ($this->db->fieldExists('order_id', 'ipd_discharge_complaint')) {
                                $commonData['order_id'] = $orderIndex;
                            }

                            if ($rowId > 0 && isset($existingIdSet[$rowId])) {
                                $savedAny = (bool) $this->db->table('ipd_discharge_complaint')
                                    ->where('id', $rowId)
                                    ->where('ipd_id', $ipdId)
                                    ->update($commonData) || $savedAny;
                                $keepIds[$rowId] = $rowId;
                            } else {
                                $insertData = $commonData;
                                $insertData['ipd_id'] = $ipdId;
                                if ($this->db->fieldExists('comp_code', 'ipd_discharge_complaint')) {
                                    $insertData['comp_code'] = 0;
                                }

                                $inserted = (bool) $this->db->table('ipd_discharge_complaint')->insert($insertData);
                                $savedAny = $inserted || $savedAny;
                                if ($inserted) {
                                    $newId = (int) ($this->db->insertID() ?? 0);
                                    if ($newId > 0) {
                                        $keepIds[$newId] = $newId;
                                    }
                                }
                            }

                            $orderIndex++;
                        }

                        $deleteBuilder = $this->db->table('ipd_discharge_complaint')->where('ipd_id', $ipdId);
                        if (! empty($keepIds)) {
                            $deleteBuilder->whereNotIn('id', array_values($keepIds));
                        }

                        $savedAny = (bool) $deleteBuilder->delete() || $savedAny;
                    }
                }

                if ($complaintRemark !== '' || $this->tableHasColumns('ipd_discharge_complaint_remark', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_complaint_remark', $ipdId, [
                        'comp_report' => $this->buildComplaintMetaPayload(['pain_value' => $painValue]),
                        'comp_remark' => $complaintRemark,
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                if ($diagnosisRemark !== '' || $this->tableHasColumns('ipd_discharge_diagnosis_remark', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_diagnosis_remark', $ipdId, [
                        'comp_report' => '',
                        'comp_remark' => $diagnosisRemark,
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                if ($courseRemark !== '' || $this->tableHasColumns('ipd_discharge_course_remark', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_course_remark', $ipdId, [
                        'comp_report' => '',
                        'comp_remark' => $courseRemark,
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                if ($instructionRemark !== '' || $reviewAfter !== '' || $instructionMeta !== '' || $this->tableHasColumns('ipd_discharge_instructions', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_instructions', $ipdId, [
                        'comp_report' => $instructionMeta,
                        'comp_remark' => $instructionRemark,
                        'review_after' => $reviewAfter,
                        'footer_text' => '',
                        'footer_banner' => '0',
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                if ($this->tableHasColumns('ipd_discharge_drug_food_interaction', ['ipd_id', 'food_id_list'])) {
                    $foodIds = [];
                    foreach ($instructionFoodIds as $foodId) {
                        $id = (int) $foodId;
                        if ($id > 0) {
                            $foodIds[$id] = $id;
                        }
                    }

                    $legacyData = [
                        'food_id_list' => implode(',', array_values($foodIds)),
                    ];
                    if ($this->db->fieldExists('food_text', 'ipd_discharge_drug_food_interaction')) {
                        $legacyData['food_text'] = $instructionOther;
                    }

                    $savedAny = $this->upsertByIpd('ipd_discharge_drug_food_interaction', $ipdId, $legacyData) || $savedAny;
                }

                // DISABLED: Legacy code that re-saved medicines on "Save Discharge Advice" button.
                $medicineJson = trim((string) ($this->request->getPost('discharge_medicine_json') ?? ''));
                if ($medicineJson !== '') {
                    $medicines = json_decode($medicineJson, true);
                    if (is_array($medicines)) {
                        $legacyTable = $this->findFirstExistingTable([
                            'ipd_discharge_prescrption_prescribed',
                            'ipd_discharge_prescription_prescribed',
                        ]);

                        if ($legacyTable !== null) {
                            $validMeds = [];
                            foreach ($medicines as $med) {
                                $medName = trim((string) ($med['med_name'] ?? ''));
                                if ($medName !== '' && $medName !== 'No medicine added') {
                                    $validMeds[] = $med;
                                }
                            }

                            if (! empty($validMeds)) {
                                $this->db->table($legacyTable)->where('ipd_id', $ipdId)->delete();

                                foreach ($validMeds as $med) {
                                    $medName = trim((string) ($med['med_name'] ?? ''));
                                    $insert = [
                                        'ipd_id' => $ipdId,
                                        'med_id' => 0,
                                        'med_name' => $medName,
                                        'update_by' => $userLabel,
                                    ];

                                    $optionalFields = [
                                        'med_type' => 'med_type',
                                        'med_salt' => 'med_salt',
                                        'dosage' => 'dosage',
                                        'dosage_when' => 'dosage_when',
                                        'dosage_freq' => 'dosage_freq',
                                        'no_of_days' => 'no_of_days',
                                        'qty' => 'qty',
                                        'remark' => 'remark',
                                    ];

                                    foreach ($optionalFields as $jsonKey => $colName) {
                                        if ($this->db->fieldExists($colName, $legacyTable)) {
                                            $insert[$colName] = trim((string) ($med[$jsonKey] ?? ''));
                                        }
                                    }

                                    $this->db->table($legacyTable)->insert($insert);
                                    $savedAny = true;
                                }
                            }
                        }
                    }
                }

                // Examination on Admission (General Examination values).
                if ($this->tableHasColumns('ipd_discharge_general_exam_col', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_b', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $generalBuilder = $this->db->table('ipd_discharge_general_exam_col')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC');
                    if ($this->db->fieldExists('is_active', 'ipd_discharge_general_exam_col')) {
                        $generalBuilder->where('is_active', 1);
                    }
                    $generalCols = $generalBuilder->get()->getResultArray();

                    foreach ($generalCols as $col) {
                        $colId = (int) ($col['id'] ?? 0);
                        if ($colId <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('gen_exam_' . $colId);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_b',
                            'ipd_d_id',
                            $ipdId,
                            'col_id',
                            $colId,
                            [
                                'short_head' => (string) ($col['col_name'] ?? ('Exam ' . $colId)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Other/Systemic Examinations.
                if ($this->tableHasColumns('ipd_discharge_sys_exam', ['id', 'sys_exam_name'])
                    && $this->tableHasColumns('ipd_discharge_1_a', ['ipd_d_id', 'head_id', 'short_head', 'rdata'])) {
                    $sysMaster = $this->db->table('ipd_discharge_sys_exam')
                        ->select('id,sys_exam_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $singleSystemicTextRaw = $this->request->getPost('systemic_exam_text');
                    if ($singleSystemicTextRaw !== null) {
                        $singleSystemicText = trim((string) $singleSystemicTextRaw);
                        $primarySysId = (int) ($sysMaster[0]['id'] ?? 0);

                        foreach ($sysMaster as $sys) {
                            $sid = (int) ($sys['id'] ?? 0);
                            if ($sid <= 0) {
                                continue;
                            }

                            $rowText = $sid === $primarySysId ? $singleSystemicText : '';
                            $savedAny = $this->upsertByComposite(
                                'ipd_discharge_1_a',
                                'ipd_d_id',
                                $ipdId,
                                'head_id',
                                $sid,
                                [
                                    'short_head' => (string) ($sys['sys_exam_name'] ?? ('Systemic Exam ' . $sid)),
                                    'rdata' => $rowText,
                                ]
                            ) || $savedAny;
                        }

                    } else {
                        foreach ($sysMaster as $sys) {
                            $sid = (int) ($sys['id'] ?? 0);
                            if ($sid <= 0) {
                                continue;
                            }
                            $posted = $this->request->getPost('sys_exam_' . $sid);
                            if ($posted === null) {
                                continue;
                            }
                            $savedAny = $this->upsertByComposite(
                                'ipd_discharge_1_a',
                                'ipd_d_id',
                                $ipdId,
                                'head_id',
                                $sid,
                                [
                                    'short_head' => (string) ($sys['sys_exam_name'] ?? ('Systemic Exam ' . $sid)),
                                    'rdata' => trim((string) $posted),
                                ]
                            ) || $savedAny;
                        }
                    }
                }

                // Investigation done during admit (manual entry): ipd_discharge_1_d
                if ($this->tableHasColumns('ipd_discharge_investigation_during_admit', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_d', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $manualCols = $this->db->table('ipd_discharge_investigation_during_admit')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    foreach ($manualCols as $col) {
                        $colId = (int) ($col['id'] ?? 0);
                        if ($colId <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('manual_exam_' . $colId);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_d',
                            'ipd_d_id',
                            $ipdId,
                            'col_id',
                            $colId,
                            [
                                'short_head' => (string) ($col['col_name'] ?? ('Investigation ' . $colId)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Special/manual radiology investigation: ipd_discharge_1_e
                if ($this->tableHasColumns('ipd_discharge_special_investigation', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_e', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $specialCols = $this->db->table('ipd_discharge_special_investigation')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    foreach ($specialCols as $col) {
                        $colId = (int) ($col['id'] ?? 0);
                        if ($colId <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('special_exam_' . $colId);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_e',
                            'ipd_d_id',
                            $ipdId,
                            'col_id',
                            $colId,
                            [
                                'short_head' => (string) ($col['col_name'] ?? ('Special Investigation ' . $colId)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Condition at discharge values: ipd_discharge_1_b_final
                if ($this->tableHasColumns('ipd_discharge_general_exam_col', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_b_final', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $disCols = $this->db->table('ipd_discharge_general_exam_col')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    foreach ($disCols as $col) {
                        $colId = (int) ($col['id'] ?? 0);
                        if ($colId <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('dis_exam_' . $colId);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_b_final',
                            'ipd_d_id',
                            $ipdId,
                            'col_id',
                            $colId,
                            [
                                'short_head' => (string) ($col['col_name'] ?? ('Discharge Exam ' . $colId)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Summary of key investigations during hospitalization.
                $inhosRemark = trim((string) ($this->request->getPost('inhos_remark') ?? ''));
                if ($inhosRemark !== '' || $this->tableHasColumns('ipd_discharge_investigtions_inhos', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_investigtions_inhos', $ipdId, [
                        'comp_report' => '',
                        'comp_remark' => $inhosRemark,
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                // Other examinations narrative (legacy ipd_discharge_2.rdata).
                $otherExamText = trim((string) ($this->request->getPost('other_exam_text') ?? ''));
                $postedClinicalDates = $this->request->getPost('lab_investigation_dates');
                $postedNonPathIds = $this->request->getPost('non_path_investigation_ids');
                $clinicalLabSelectionMode = trim((string) ($this->request->getPost('clinical_lab_selection_mode') ?? ''));
                $clinicalNonPathSelectionMode = trim((string) ($this->request->getPost('clinical_nonpath_selection_mode') ?? ''));
                $normalizedClinicalDates = [];
                $normalizedNonPathIds = [];
                if ($clinicalLabSelectionMode === 'checkbox') {
                    if (is_array($postedClinicalDates)) {
                        foreach ($postedClinicalDates as $dt) {
                            $parsed = $this->normalizeDateValue((string) $dt);
                            if ($parsed !== null) {
                                $normalizedClinicalDates[$parsed] = $parsed;
                            }
                        }
                    }
                } elseif (is_array($postedClinicalDates)) {
                    foreach ($postedClinicalDates as $dt) {
                        $parsed = $this->normalizeDateValue((string) $dt);
                        if ($parsed !== null) {
                            $normalizedClinicalDates[$parsed] = $parsed;
                        }
                    }
                } else {
                    $listFromText = (string) ($this->request->getPost('lab_investigation_list') ?? '');
                    foreach ($this->normalizeCsvDateList($listFromText) as $dt) {
                        $normalizedClinicalDates[$dt] = $dt;
                    }
                }

                if ($clinicalNonPathSelectionMode === 'checkbox') {
                    if (is_array($postedNonPathIds)) {
                        foreach ($postedNonPathIds as $id) {
                            $id = (int) $id;
                            if ($id > 0) {
                                $normalizedNonPathIds[$id] = $id;
                            }
                        }
                    }
                } elseif (is_array($postedNonPathIds)) {
                    foreach ($postedNonPathIds as $id) {
                        $id = (int) $id;
                        if ($id > 0) {
                            $normalizedNonPathIds[$id] = $id;
                        }
                    }
                } else {
                    $nonPathFromText = (string) ($this->request->getPost('non_path_investigation_list') ?? '');
                    foreach ($this->normalizeCsvIdList($nonPathFromText) as $id) {
                        $normalizedNonPathIds[$id] = $id;
                    }
                }

                $labInvestigationList = implode(',', array_values($normalizedClinicalDates));
                $nonPathIds = array_values($normalizedNonPathIds);
                $otherExamPayload = $this->buildClinicalOtherExamPayload($otherExamText, $nonPathIds);
                if ($otherExamText !== '' || $this->tableHasColumns('ipd_discharge_2', ['ipd_d_id'])) {
                    $savedAny = $this->upsertByIpdField(
                        'ipd_discharge_2',
                        'ipd_d_id',
                        $ipdId,
                        [
                            'lab_investigation_list' => $labInvestigationList,
                            'short_head' => 'Other Examination',
                            'rdata' => $otherExamPayload,
                        ]
                    ) || $savedAny;
                }

                // Legacy first-tab personal history checkboxes are stored in patient_master.
                if ($patientId > 0 && $this->db->tableExists('patient_master')) {
                    $patientUpdate = [];
                    $historyFields = [
                        'is_smoking',
                        'is_alcohol',
                        'is_drug_abuse',
                        'is_tobacoo',
                        'is_hypertesion',
                        'is_niddm',
                        'is_hbsag',
                        'is_hcv',
                        'is_hiv_I_II',
                        'Others',
                    ];

                    foreach ($historyFields as $field) {
                        if ($this->db->fieldExists($field, 'patient_master')) {
                            $patientUpdate[$field] = $this->request->getPost($field) ? 1 : 0;
                        }
                    }

                    if (! empty($patientUpdate)) {
                        $savedAny = (bool) $this->db->table('patient_master')->where('id', $patientId)->update($patientUpdate) || $savedAny;
                    }
                }

                // Save editable NABH history fields back to latest OPD history row for this patient.
                $nabhHistoryPayload = [
                    'drug_allergy_status' => trim((string) ($this->request->getPost('drug_allergy_status') ?? '')),
                    'drug_allergy_details' => trim((string) ($this->request->getPost('drug_allergy_details') ?? '')),
                    'adr_history' => trim((string) ($this->request->getPost('adr_history') ?? '')),
                    'current_medications' => trim((string) ($this->request->getPost('current_medications') ?? '')),
                    'co_morbidities' => trim((string) ($this->request->getPost('co_morbidities') ?? '')),
                    'women_lmp' => trim((string) ($this->request->getPost('women_lmp') ?? '')),
                    'women_last_baby' => trim((string) ($this->request->getPost('women_last_baby') ?? '')),
                    'women_pregnancy_related' => trim((string) ($this->request->getPost('women_pregnancy_related') ?? '')),
                    'women_related_problems' => trim((string) ($this->request->getPost('women_related_problems') ?? '')),
                    'hpi_note' => trim((string) ($this->request->getPost('hpi_note') ?? '')),
                ];
                $savedAny = $this->saveNabhHistoryFromDischarge($patientId, $nabhHistoryPayload) || $savedAny;

                if ($savedAny) {
                    $notice = 'Discharge form data saved. You can now preview or regenerate summary.';
                } else {
                    $notice = 'No data could be saved. Please verify discharge tables exist in this database.';
                    $noticeType = 'warning';
                }
            }

            if ($action !== 'save_main' && $notice === '') {
                $notice = 'Requested action could not be completed. Please verify database table/columns for this section.';
                $noticeType = 'warning';
            }

            // Skip expensive discharge-summary rebuild and sync for lightweight AJAX actions.
            // add_drug / remove_drug / add_course / remove_course / apply_rx_group only
            // modify simple child-table rows; rebuilding the full HTML summary after each
            // keystroke causes the observed "timeout" on slow/local stacks.
            $isLightweightAjaxAction = in_array($action, [
                'add_drug', 'remove_drug', 'remove_all_drugs',
                'add_course', 'remove_course',
                'apply_rx_group',
            ], true);

            if ($savedAny && ! $isLightweightAjaxAction) {
                // Keep ipd_discharge.content in sync with latest form data so preview/PDF
                // immediately reflects edits without requiring manual regen links.
                try {
                    $freshPanelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
                    if (! empty($freshPanelData)) {
                        $freshContent = $this->buildAutoDischargeContent($ipdId, $freshPanelData);
                        if (trim((string) $freshContent) !== '') {
                            $this->saveDischargeContent($ipdId, (string) $freshContent);
                        }
                    }
                } catch (\Throwable $e) {
                    // Fail-open: do not block UI save if regeneration fails.
                }

                $this->enqueueIpdDischargeSync($ipdId, $patientId, $action);
            }

            $ajaxMode = strtolower(trim((string) ($this->request->getPost('ajax_mode') ?? '')));
            if ($ajaxMode === 'json' || $this->request->isAJAX()) {
                $legacyDrugTableName = $this->findFirstExistingTable(['ipd_discharge_prescrption_prescribed', 'ipd_discharge_prescription_prescribed']);
                $rawDrugRows = $legacyDrugTableName !== null
                    ? $this->byIpdRows($legacyDrugTableName, ['id', 'med_name', 'med_type', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days', 'qty', 'remark'], 'id ASC', $ipdId)
                    : $this->byIpdRows('ipd_discharge_drug', ['id', 'drug_name', 'drug_dose', 'drug_day'], 'id ASC', $ipdId);

                // Resolve numeric dosage IDs to human-readable labels so the
                // JS UI can display them directly without a second round-trip.
                $doseMap = $this->getDoseMasterRows('opd_dose_shed');
                $whenMap = $this->getDoseMasterRows('opd_dose_when');
                $freqMap = $this->getDoseMasterRows('opd_dose_frequency');

                $drugRows = [];
                if ($legacyDrugTableName !== null) {
                    foreach ($rawDrugRows as $row) {
                        $doseId   = (int) ($row['dosage']      ?? 0);
                        $whenId   = (int) ($row['dosage_when'] ?? 0);
                        $freqId   = (int) ($row['dosage_freq'] ?? 0);
                        $drugRows[] = [
                            'id'          => (int)    ($row['id']       ?? 0),
                            'source'      => 'legacy',
                            'med_name'    => (string) ($row['med_name'] ?? ''),
                            'med_type'    => (string) ($row['med_type'] ?? ''),
                            'dosage'      => isset($doseMap[$doseId]) ? (string) ($doseMap[$doseId]['label'] ?? '') : (string) ($row['dosage']      ?? ''),
                            'dosage_when' => isset($whenMap[$whenId]) ? (string) ($whenMap[$whenId]['label'] ?? '') : (string) ($row['dosage_when'] ?? ''),
                            'dosage_freq' => isset($freqMap[$freqId]) ? (string) ($freqMap[$freqId]['label'] ?? '') : (string) ($row['dosage_freq'] ?? ''),
                            'no_of_days'  => (string) ($row['no_of_days'] ?? ''),
                            'qty'         => (string) ($row['qty']        ?? ''),
                            'remark'      => (string) ($row['remark']     ?? ''),
                        ];
                    }
                } else {
                    foreach ($rawDrugRows as $row) {
                        $drugRows[] = [
                            'id'          => (int)    ($row['id']         ?? 0),
                            'source'      => 'classic',
                            'med_name'    => (string) ($row['drug_name']  ?? ''),
                            'med_type'    => '',
                            'dosage'      => (string) ($row['drug_dose']  ?? ''),
                            'dosage_when' => '',
                            'dosage_freq' => '',
                            'no_of_days'  => (string) ($row['drug_day']   ?? ''),
                            'qty'         => '',
                            'remark'      => '',
                        ];
                    }
                }

                return $this->response->setJSON([
                    'update'     => $savedAny ? 1 : 0,
                    'notice'     => $notice ?? '',
                    'noticeType' => $noticeType ?? 'info',
                    'error_text' => $notice ?? '',
                    'row_id'     => $ajaxRowId,
                    'row_source' => $ajaxRowSource,
                    'drugRows'   => $drugRows,
                    'csrfName'   => csrf_token(),
                    'csrfHash'   => csrf_hash(),
                ]);
            }
        }

        if ($reCreate > 0) {
            return redirect()->to(site_url('Ipd_discharge/preview_discharge_report/' . $ipdId . '?regen=1'));
        }

        $ipdMasterRow = $this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [];
        $statusRows = $this->db->tableExists('ipd_discharg_status')
            ? $this->db->table('ipd_discharg_status')->orderBy('id', 'ASC')->get()->getResultArray()
            : [];
        $departmentRows = $this->db->tableExists('hc_department')
            ? $this->db->table('hc_department')->orderBy('vName', 'ASC')->get()->getResultArray()
            : [];

        $complaintRemarkRow = $this->firstRowByIpd('ipd_discharge_complaint_remark', $ipdId);
        $complaintMeta = $this->parseComplaintMetaPayload((string) ($complaintRemarkRow['comp_report'] ?? ''));
        $complaintRows = $this->byIpdRows('ipd_discharge_complaint', ['id', 'comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $surgeryRows = $this->byIpdRows('ipd_discharge_surgery', ['id', 'surgery_name', 'surgery_date', 'surgery_remark'], 'id ASC', $ipdId);
        $procedureRows = $this->byIpdRows('ipd_discharge_procedure', ['id', 'procedure_name', 'procedure_date', 'procedure_remark'], 'id ASC', $ipdId);
        $diagnosisRows = $this->byIpdRows('ipd_discharge_diagnosis', ['id', 'comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $courseRows = $this->byIpdRows('ipd_discharge_course', ['id', 'comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $drugRows = $this->byIpdRows('ipd_discharge_drug', ['id', 'drug_name', 'drug_dose', 'drug_day'], 'id ASC', $ipdId);
        $legacyDrugRows = $this->byIpdRows('ipd_discharge_prescrption_prescribed', ['id', 'med_name', 'med_salt', 'med_type', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days', 'qty', 'remark'], 'id ASC', $ipdId);
        if (empty($legacyDrugRows)) {
            $legacyDrugRows = $this->byIpdRows('ipd_discharge_prescription_prescribed', ['id', 'med_name', 'med_salt', 'med_type', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days', 'qty', 'remark'], 'id ASC', $ipdId);
        }
        
        // Load dose master maps for label display
        $doseMasterMaps = [
            'dose' => $this->getDoseMasterRows('opd_dose_shed'),
            'when' => $this->getDoseMasterRows('opd_dose_when'),
            'freq' => $this->getDoseMasterRows('opd_dose_frequency'),
            'where' => $this->getDoseMasterRows('opd_dose_where'),
        ];
        
        $diagnosisRemarkRow = $this->firstRowByIpd('ipd_discharge_diagnosis_remark', $ipdId);
        $courseRemarkRow = $this->firstRowByIpd('ipd_discharge_course_remark', $ipdId);
        $instructionRow = $this->firstRowByIpd('ipd_discharge_instructions', $ipdId);
        $instructionMeta = $this->parseInstructionMetaPayload((string) ($instructionRow['comp_report'] ?? ''));
        if (empty($instructionMeta['food_ids'])
            && $this->tableHasColumns('ipd_discharge_drug_food_interaction', ['ipd_id', 'food_id_list'])) {
            $legacyFoodRow = $this->firstRowByIpd('ipd_discharge_drug_food_interaction', $ipdId);
            $instructionMeta['food_ids'] = $this->parseFoodIdCsv((string) ($legacyFoodRow['food_id_list'] ?? ''));
            if (trim((string) ($instructionMeta['other_text'] ?? '')) === '' && $this->db->fieldExists('food_text', 'ipd_discharge_drug_food_interaction')) {
                $instructionMeta['other_text'] = trim((string) ($legacyFoodRow['food_text'] ?? ''));
            }
        }
        $instructionNabh = is_array($instructionMeta['nabh'] ?? null) ? ($instructionMeta['nabh'] ?? []) : [];

        foreach (['drug_allergy_status', 'drug_allergy_details', 'adr_history', 'current_medications', 'co_morbidities', 'hpi_note', 'women_lmp', 'women_last_baby', 'women_pregnancy_related', 'women_related_problems'] as $field) {
            if (trim((string) ($opdHistorySnapshot[$field] ?? '')) === '' && trim((string) ($instructionNabh[$field] ?? '')) !== '') {
                $opdHistorySnapshot[$field] = trim((string) ($instructionNabh[$field] ?? ''));
            }
        }
        $instructionFoodRows = [];
        if ($this->tableHasColumns('ipd_discharge_master_food', ['id', 'food_short', 'food_desc'])) {
            $builder = $this->db->table('ipd_discharge_master_food')
                ->select('id,food_short,food_desc,food_desc_lang')
                ->orderBy('id', 'ASC');
            $instructionFoodRows = $builder->get()->getResultArray();
        }
        $inhosRow = $this->firstRowByIpd('ipd_discharge_investigtions_inhos', $ipdId);
        $otherExamRow = [];
        if ($this->tableHasColumns('ipd_discharge_2', ['ipd_d_id'])) {
            $otherExamRow = $this->db->table('ipd_discharge_2')
                ->where('ipd_d_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray() ?? [];
        }
        $otherExamParsed = $this->parseClinicalOtherExamPayload((string) ($otherExamRow['rdata'] ?? ''));
        $savedNonPathIds = $otherExamParsed['non_path_ids'] ?? [];
        $patientHistoryRow = $patientId > 0 && $this->db->tableExists('patient_master')
            ? ($this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [])
            : [];
        $opdHistorySnapshot = $this->getLatestOpdHistorySnapshot($patientId);
        $nursingAdmissionSnapshot = $this->getNursingAdmissionSnapshot($ipdId);
        $physicalExamRows = $this->getPhysicalExamRows($ipdId);
        $manualInvestRows = $this->getMappedColRows('ipd_discharge_investigation_during_admit', 'ipd_discharge_1_d', $ipdId, 'Manual Exam', 1);
        $specialInvestRows = $this->getMappedColRows('ipd_discharge_special_investigation', 'ipd_discharge_1_e', $ipdId, 'Special Exam', 1);
        $dischargeConditionRows = $this->getMappedColRows('ipd_discharge_general_exam_col', 'ipd_discharge_1_b_final', $ipdId, 'Discharge Condition', null);

        $ipdInfo = $panelData['ipd_info'] ?? null;
        $admitDate = $this->normalizeDateValue((string) ($ipdInfo->register_date ?? '')) ?? '';
        $dischargeDate = $this->normalizeDateValue((string) ($ipdInfo->discharge_date ?? ''))
            ?? $this->normalizeDateValue((string) ($ipdMasterRow['discharge_date'] ?? ''))
            ?? date('Y-m-d');
        $savedClinicalDates = $this->getSavedClinicalLabDates($ipdId);
        $clinicalLabRows = $this->getClinicalInvestigationLabRows($patientId, $admitDate, $dischargeDate, $savedClinicalDates);
        $clinicalNonPathRows = $this->getClinicalNonPathReportRows($patientId, $admitDate, $dischargeDate, $savedNonPathIds);
        $labInvestigationList = implode(',', $savedClinicalDates);
        $nonPathInvestigationList = implode(',', $savedNonPathIds);

        $complaintRemarkText = (string) ($complaintRemarkRow['comp_remark'] ?? '');
        if (trim($complaintRemarkText) === '' && ! empty($nursingAdmissionSnapshot)) {
            $parts = [];
            $nursingComplaint = trim((string) ($nursingAdmissionSnapshot['treatment_text'] ?? ''));
            $nursingNote = trim((string) ($nursingAdmissionSnapshot['general_note'] ?? ''));
            if ($nursingComplaint !== '') {
                $parts[] = $nursingComplaint;
            }
            if ($nursingNote !== '') {
                $parts[] = $nursingNote;
            }
            if (! empty($parts)) {
                $complaintRemarkText = implode(PHP_EOL, $parts);
            }
        }
        if (trim($complaintRemarkText) === '') {
            $hpiFallback = trim((string) ($opdHistorySnapshot['hpi_note'] ?? ''));
            if ($hpiFallback !== '') {
                $complaintRemarkText = $hpiFallback;
            }
        }

        return view('billing/ipd/discharge_create', [
            'ipd_id' => $ipdId,
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $panelData['person_info'] ?? null,
            'notice' => $notice,
            'notice_type' => $noticeType,
            'status_rows' => $statusRows,
            'department_rows' => $departmentRows,
            'ipd_master_row' => $ipdMasterRow,
            'complaint_rows' => $complaintRows,
            'surgery_rows' => $surgeryRows,
            'procedure_rows' => $procedureRows,
            'diagnosis_rows' => $diagnosisRows,
            'course_rows' => $courseRows,
            'drug_rows' => $drugRows,
            'legacy_drug_rows' => $legacyDrugRows,
            'patient_history_row' => $patientHistoryRow,
            'physical_exam_rows' => $physicalExamRows,
            'manual_invest_rows' => $manualInvestRows,
            'special_invest_rows' => $specialInvestRows,
            'clinical_lab_rows' => $clinicalLabRows,
            'clinical_non_path_rows' => $clinicalNonPathRows,
            'lab_investigation_list' => $labInvestigationList,
            'non_path_investigation_list' => $nonPathInvestigationList,
            'discharge_condition_rows' => $dischargeConditionRows,
            'complaint_remark' => $complaintRemarkText,
            'pain_value' => (string) ($complaintMeta['pain_value'] ?? ''),
            'opd_history_snapshot' => $opdHistorySnapshot,
            'nursing_admission_snapshot' => $nursingAdmissionSnapshot,
            'diagnosis_remark' => (string) ($diagnosisRemarkRow['comp_remark'] ?? ''),
            'course_remark' => (string) ($courseRemarkRow['comp_remark'] ?? ''),
            'instruction_remark' => (string) ($instructionRow['comp_remark'] ?? ''),
            'review_after' => (string) ($instructionRow['review_after'] ?? ''),
            'instruction_food_rows' => $instructionFoodRows,
            'instruction_food_ids' => $instructionMeta['food_ids'] ?? [],
            'instruction_other' => (string) ($instructionMeta['other_text'] ?? ''),
            'inhos_remark' => (string) ($inhosRow['comp_remark'] ?? ''),
            'other_exam_text' => (string) ($otherExamParsed['text'] ?? ''),
            'next_visit_options' => $this->getNextVisitOptions(date('Y-m-d')),
            'dose_master_maps' => $doseMasterMaps,
            'discharge_templates' => $dischargeTemplates,
            'selected_discharge_template_id' => $selectedDischargeTemplateId,
        ]);
    }

    public function preview_discharge_report(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'ipd_discharge.view',
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if ($ipdId <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Invalid IPD id');
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $notice = '';
        $noticeType = 'success';
        $content = $this->getDischargeContent($ipdId);
        $shouldRegenerate = (int) ($this->request->getGet('regen') ?? 0) === 1;
        $requestedTemplateId = (int) ($this->request->getGet('tpl') ?? 0);

        if ($shouldRegenerate || trim(strip_tags($content)) === '') {
            $generated = $this->buildAutoDischargeContent($ipdId, $panelData);
            if (trim(strip_tags($generated)) !== '') {
                $content = $generated;
                if ($this->saveDischargeContent($ipdId, $content)) {
                    $notice = 'Discharge summary auto-generated from IPD discharge data.';
                    $noticeType = 'success';
                } else {
                    $notice = 'Discharge summary generated in editor, but database save failed. Please click Save again after checking DB schema.';
                    $noticeType = 'warning';
                }
            }
        }

        $templatePack = $this->applyDischargeTemplate($content, $panelData, $requestedTemplateId > 0 ? $requestedTemplateId : null);
        $nabhAudit = $this->buildNabhAuditChecklist($ipdId, $panelData);
        $allTemplateRows = $templatePack['templates'] ?? [];
        $printableTemplateRows = array_values(array_filter(
            $allTemplateRows,
            fn (array $template): bool => ! $this->isDischargeAuditTemplate($template)
        ));
        $auditTemplateRows = array_values(array_filter(
            $allTemplateRows,
            fn (array $template): bool => $this->isDischargeAuditTemplate($template)
        ));

        return view('billing/ipd/discharge_preview', [
            'ipd_id' => $ipdId,
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $panelData['person_info'] ?? null,
            'content' => $content,
            'rendered_content' => (string) ($templatePack['rendered_html'] ?? $content),
            'template_rows' => $printableTemplateRows,
            'audit_template_rows' => $auditTemplateRows,
            'selected_template_id' => (int) ($templatePack['selected_template_id'] ?? 0),
            'selected_template_name' => (string) ($templatePack['selected_template_name'] ?? ''),
            'nabh_audit' => $nabhAudit,
            'notice' => $notice,
            'notice_type' => $noticeType,
        ]);
    }

    public function show_discharge(int $ipdId, int $printType = 1)
    {
        $permission = $this->requireAnyPermission([
            'ipd_discharge.view',
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if ($ipdId <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Invalid IPD id');
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        // Check if user wants to force regenerate content (ignoring cache)
        $forceRegenerate = (int) ($this->request->getGet('refresh') ?? 0) === 1;

        $content = $this->getDischargeContent($ipdId);
        if ($forceRegenerate || trim(strip_tags($content)) === '') {
            $content = $this->buildAutoDischargeContent($ipdId, $panelData);
            if (trim(strip_tags($content)) !== '') {
                $this->saveDischargeContent($ipdId, $content);
            }
        }

        $requestedTemplateId = (int) ($this->request->getGet('tpl') ?? 0);
        $templatePack = $this->applyDischargeTemplate($content, $panelData, $requestedTemplateId > 0 ? $requestedTemplateId : null);
        $templateSettings = is_array($templatePack['selected_template_settings'] ?? null)
            ? $templatePack['selected_template_settings']
            : $this->defaultDischargeTemplateSettings();

        $withHeader = $printType !== 0;
        $renderedHtml = $this->sanitizeDischargePdfHtml((string) ($templatePack['rendered_html'] ?? $content));
        $renderedHtml = $this->dedupeTopDemographicTables($renderedHtml);
        $templateName = (string) ($templatePack['selected_template_name'] ?? 'Discharge Template');
        $templateTokenVars = $this->buildDischargeTemplateTokenVars($panelData, $content);
        $templateTokenVars['TEMPLATE_NAME'] = esc($templateName);

        // DEBUG MODE: show the exact HTML string WriteHTML() receives.
        $htmlDebugMode = (int) ($this->request->getGet('html') ?? 0) === 1;
        if ($htmlDebugMode) {
            $dbgHeaderHtml = '';
            if ($withHeader) {
                $dbgHeader = $this->applyDischargeTemplateTokens(
                    trim((string) ($templateSettings['header_html'] ?? '')), $templateTokenVars
                );
                $dbgHeaderHtml = $this->sanitizeDischargePdfHtml($dbgHeader, false);
            }
            $dbgFooterHtml = $this->sanitizeDischargePdfHtml(
                $this->applyDischargeTemplateTokens(
                    trim((string) ($templateSettings['footer_html'] ?? '')), $templateTokenVars
                ), false
            );
            $dbgHeaderCm = (float) ($templateSettings['margin_header_cm'] ?? 0.5);
            $dbgFooterCm = (float) ($templateSettings['margin_footer_cm'] ?? 0.5);
            $dbgPageBlock = '<style>@page {' . "\n"
                . 'margin-top: ' . (float) ($templateSettings['page_margin_top_cm'] ?? 0.8) . 'cm;' . "\n"
                . 'margin-bottom: ' . (float) ($templateSettings['page_margin_bottom_cm'] ?? 0.8) . 'cm;' . "\n"
                . 'margin-left: ' . (float) ($templateSettings['page_margin_left_cm'] ?? 0.8) . 'cm;' . "\n"
                . 'margin-right: ' . (float) ($templateSettings['page_margin_right_cm'] ?? 0.8) . 'cm;' . "\n"
                . 'margin-header: ' . $dbgHeaderCm . 'cm;' . "\n"
                . 'margin-footer: ' . $dbgFooterCm . 'cm;' . "\n"
                . ($dbgHeaderHtml !== '' ? 'header: html_myHeader;' . "\n" : '')
                . ($dbgFooterHtml !== '' ? 'footer: html_myFooter;' . "\n" : '')
                . '}</style>' . "\n";
            $dbgNamedBlocks = '';
            if ($dbgHeaderHtml !== '') {
                $dbgNamedBlocks .= '<htmlpageheader name="myHeader">' . "\n" . $dbgHeaderHtml . "\n" . '</htmlpageheader>' . "\n";
            }
            if ($dbgFooterHtml !== '') {
                $dbgNamedBlocks .= '<htmlpagefooter name="myFooter">' . "\n" . $dbgFooterHtml . "\n" . '</htmlpagefooter>' . "\n";
            }
            $dbgCss = trim((string) ($templateSettings['template_css'] ?? ''));
            $dbgBody = $this->buildDischargePdfHtml($panelData, $renderedHtml, $withHeader, $templateName);
            $fullHtml = $dbgPageBlock . $dbgNamedBlocks
                . ($dbgCss !== '' ? '<style>' . $dbgCss . '</style>' . "\n" : '')
                . $dbgBody;

            return $this->response
                ->setContentType('text/html')
                ->setBody(
                    '<!DOCTYPE html><html><head><meta charset="utf-8">' .
                    '<title>mPDF Debug HTML – IPD ' . $ipdId . '</title>' .
                    '<style>body{font-family:monospace;margin:20px;background:#f5f5f5;}' .
                    'pre{background:#fff;padding:16px;border:1px solid #d1d5db;white-space:pre-wrap;word-break:break-word;}' .
                    '.info{background:#fff;padding:12px;border:2px solid #2563eb;margin-bottom:12px;font-family:sans-serif;}' .
                    '</style></head><body>' .
                    '<div class="info"><strong>IPD:</strong> ' . $ipdId .
                    ' | <strong>Template:</strong> ' . esc($templateName) .
                    ' | <strong>header_html:</strong> ' . esc((string) ($templateSettings['header_html'] ?? '(empty)')) .
                    ' | <strong>With Header:</strong> ' . ($withHeader ? 'Yes' : 'No') . '</div>' .
                    '<pre>' . htmlspecialchars($fullHtml, ENT_QUOTES, 'UTF-8') . '</pre>' .
                    '</body></html>'
                );
        }

        $patient = $panelData['person_info'] ?? null;
        $ipd = $panelData['ipd_info'] ?? null;
        $patientName = trim((string) ($patient->p_fname ?? 'Patient'));
        $ipdCode = trim((string) ($ipd->ipd_code ?? $ipdId));
        $fileName = 'discharge_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $ipdCode !== '' ? $ipdCode : (string) $ipdId) . '.pdf';

        $this->createIpdDischargeWorkTask($ipdId, $panelData);

        try {
            $headerHtml = '';
            if ($withHeader) {
                $configuredHeader = $this->applyDischargeTemplateTokens(
                    trim((string) ($templateSettings['header_html'] ?? '')),
                    $templateTokenVars
                );
                $headerHtml = $this->sanitizeDischargePdfHtml($configuredHeader, false);
                $headerHtml = mpdf_normalize_font_weight_css($headerHtml);
            }

            $configuredFooter = $this->applyDischargeTemplateTokens(
                trim((string) ($templateSettings['footer_html'] ?? '')),
                $templateTokenVars
            );
            $footerHtml = $this->sanitizeDischargePdfHtml(
                $configuredFooter,
                false
            );
            $footerHtml = mpdf_normalize_font_weight_css($footerHtml);
            if ($footerHtml === '') {
                $footerHtml = '<div style="font-family:freeserif,serif;font-size:9pt;color:#6b7280;text-align:right;">Page {PAGENO}/{nbpg}</div>';
            }

            $pdfHtml = $this->buildDischargePdfHtml($panelData, $renderedHtml, $withHeader, $templateName);
            $pdfHtml = mpdf_normalize_font_weight_css($pdfHtml);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => ($templateSettings['page_size'] ?? 'A4') === 'CUSTOM'
                    ? [max(20, (int) ($templateSettings['custom_width_mm'] ?? 210)), max(20, (int) ($templateSettings['custom_height_mm'] ?? 297))]
                    : (string) ($templateSettings['page_size'] ?? 'A4'),
                'margin_left'   => max(0, ((float) ($templateSettings['page_margin_left_cm']   ?? 0.8)) * 10),
                'margin_right'  => max(0, ((float) ($templateSettings['page_margin_right_cm']  ?? 0.8)) * 10),
                'margin_top'    => max(0, ((float) ($templateSettings['page_margin_top_cm']    ?? 0.8)) * 10),
                'margin_bottom' => max(0, ((float) ($templateSettings['page_margin_bottom_cm'] ?? 0.8)) * 10),
                'margin_header' => max(0, ((float) ($templateSettings['margin_header_cm']      ?? 0.5)) * 10),
                'margin_footer' => max(0, ((float) ($templateSettings['margin_footer_cm']      ?? 0.5)) * 10),
                'default_font' => 'freeserif',
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            $mpdf->SetTitle('Discharge Summary - ' . ($patientName !== '' ? $patientName : ('IPD ' . $ipdId)));
            $mpdf->SetAuthor('Atria HMS');

            if ($withHeader && $headerHtml !== '') {
                $mpdf->SetHTMLHeader($headerHtml, 'O');
                $mpdf->SetHTMLHeader($headerHtml, 'E');
            }
            if ($footerHtml !== '') {
                $mpdf->SetHTMLFooter($footerHtml, 'O');
                $mpdf->SetHTMLFooter($footerHtml, 'E');
            }

            $watermarkType = (string) ($templateSettings['watermark_type'] ?? 'none');
            $watermarkAlpha = max(0.01, min(1.00, (float) ($templateSettings['watermark_alpha'] ?? 0.12)));
            if ($watermarkType === 'text') {
                $watermarkText = trim((string) ($templateSettings['watermark_text'] ?? ''));
                if ($watermarkText !== '') {
                    $mpdf->SetWatermarkText($watermarkText, $watermarkAlpha);
                    $mpdf->showWatermarkText = true;
                }
            } elseif ($watermarkType === 'image') {
                $watermarkImage = trim((string) ($templateSettings['watermark_image'] ?? ''));
                $watermarkPath = $watermarkImage !== '' ? FCPATH . ltrim(str_replace('\\', '/', $watermarkImage), '/') : '';
                if ($watermarkPath !== '' && is_file($watermarkPath)) {
                    $mpdf->SetWatermarkImage($watermarkPath, $watermarkAlpha);
                    $mpdf->showWatermarkImage = true;
                }
            }

            if ($withHeader || $footerHtml !== '') {
                $mpdf->AddPage();
                if ($withHeader && $headerHtml !== '') {
                    $mpdf->SetHTMLHeader($headerHtml, 'O', true);
                }
                if ($footerHtml !== '') {
                    $mpdf->SetHTMLFooter($footerHtml, 'O', true);
                }
            }

            $pdfHtml = '';
            $templateCss = trim((string) ($templateSettings['template_css'] ?? ''));
            if ($templateCss !== '') {
                $templateCss = (string) preg_replace('/@page(?:\s+[^{]+)?\s*\{[^{}]*\}/i', '', $templateCss);
                $pdfHtml .= '<style>' . "\n" . $templateCss . "\n" . '</style>' . "\n";
            }

            $pdfHtml .= $renderedHtml;
            $pdfHtml = mpdf_normalize_font_weight_css($pdfHtml);

            $debugDirectory = WRITEPATH . 'debug';
            if (! is_dir($debugDirectory)) {
                mkdir($debugDirectory, 0755, true);
            }
            $debugTemplateId = (int) ($templatePack['selected_template_id'] ?? 0);
            file_put_contents($debugDirectory . DIRECTORY_SEPARATOR . 'discharge_mpdf_input_' . $ipdId . '_' . $debugTemplateId . '.html', $pdfHtml, LOCK_EX);
            file_put_contents($debugDirectory . DIRECTORY_SEPARATOR . 'discharge_mpdf_runtime_' . $ipdId . '_' . $debugTemplateId . '.json', json_encode([
                'header_html' => $headerHtml,
                'footer_html' => $footerHtml,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

            $pdfBinary = $this->runMpdfWithTolerantWarnings(static function () use ($mpdf, $pdfHtml, $fileName): string {
                $mpdf->WriteHTML($pdfHtml);

                return $mpdf->Output($fileName, Destination::STRING_RETURN);
            });
            $this->cacheAbdmIpdPdf($ipdId, 'discharge-summary.pdf', $pdfBinary);
            
            // Clear any output buffers to prevent corruption of binary PDF data
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Length', (string) strlen($pdfBinary))
                ->setHeader('Content-Disposition', 'inline; filename="' . rawurlencode($fileName) . '"; filename*=UTF-8\'\''. rawurlencode($fileName))
                ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
                ->setHeader('Accept-Ranges', 'bytes')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody($pdfBinary);
        } catch (\Throwable $e) {
            log_message('error', 'PDF generation failed for IPD {ipd}: {msg}', [
                'ipd' => $ipdId,
                'msg' => $e->getMessage(),
            ]);

            if ($printType !== 0) {
                try {
                    $safeBodySource = trim($renderedHtml) !== '' ? $renderedHtml : (string) $content;
                    $safeBody = $this->sanitizeDischargePdfHtml($safeBodySource);
                    $safeBody = (string) preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $safeBody);
                    $safeBody = (string) preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $safeBody);
                    $safePdfHtml = $this->buildDischargePdfHtml($panelData, $safeBody, false, $templateName);
                    $safePdfHtml = mpdf_normalize_font_weight_css($safePdfHtml);

                    $fallbackPdf = new Mpdf([
                        'mode' => 'utf-8',
                        'format' => 'A4',
                        'margin_left' => 8,
                        'margin_right' => 8,
                        'margin_top' => 8,
                        'margin_bottom' => 8,
                        'margin_header' => 5,
                        'margin_footer' => 5,
                        'default_font' => 'freeserif',
                        'tempDir' => WRITEPATH . 'cache',
                    ]);
                    $fallbackPdf->autoScriptToLang = true;
                    $fallbackPdf->autoLangToFont = true;
                    $fallbackPdf->SetTitle('Discharge Summary - ' . ($patientName !== '' ? $patientName : ('IPD ' . $ipdId)));
                    $fallbackPdf->SetAuthor('Atria HMS');
                    $fallbackPdf->SetHTMLFooter('<div style="font-family:freeserif,serif;font-size:9pt;color:#6b7280;text-align:right;">Page {PAGENO}/{nbpg}</div>');
                    $pdfBinary = $this->runMpdfWithTolerantWarnings(static function () use ($fallbackPdf, $safePdfHtml, $fileName): string {
                        $fallbackPdf->WriteHTML($safePdfHtml);

                        return $fallbackPdf->Output($fileName, Destination::STRING_RETURN);
                    });

                    log_message('warning', 'Discharge PDF fallback render succeeded for IPD {ipd}', ['ipd' => $ipdId]);

                    // Clear any output buffers to prevent corruption of binary PDF data
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }

                    return $this->response
                        ->setHeader('Content-Type', 'application/pdf')
                        ->setHeader('Content-Length', (string) strlen($pdfBinary))
                        ->setHeader('Content-Disposition', 'inline; filename="' . rawurlencode($fileName) . '"; filename*=UTF-8\'\''. rawurlencode($fileName))
                        ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
                        ->setHeader('Accept-Ranges', 'bytes')
                        ->setHeader('X-Content-Type-Options', 'nosniff')
                        ->setBody($pdfBinary);
                } catch (\Throwable $fallbackError) {
                    log_message('error', 'Discharge PDF fallback failed for IPD {ipd}: {msg}', [
                        'ipd' => $ipdId,
                        'msg' => $fallbackError->getMessage(),
                    ]);

                    try {
                        $plainSourceHtml = trim($renderedHtml) !== '' ? $renderedHtml : (string) $content;
                        $plainSourceHtml = (string) preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $plainSourceHtml);
                        $plainSourceHtml = (string) preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $plainSourceHtml);
                        $plainSource = strip_tags($plainSourceHtml);
                        $plainSource = $this->forceValidUtf8($plainSource);
                        $plainSource = preg_replace('/\R{3,}/', "\n\n", (string) $plainSource) ?? '';

                        $plainHtml = '<!doctype html><html><head><meta charset="utf-8"></head><body>'
                            . '<h3 style="font-family:freeserif,serif;margin:0 0 8px 0;">Discharge Summary</h3>'
                            . '<pre style="font-family:freeserif,serif;font-size:11pt;white-space:pre-wrap;line-height:1.4;">'
                            . htmlspecialchars($plainSource, ENT_QUOTES, 'UTF-8')
                            . '</pre></body></html>';

                        $ultimatePdf = new Mpdf([
                            'mode' => 'utf-8',
                            'format' => 'A4',
                            'margin_left' => 8,
                            'margin_right' => 8,
                            'margin_top' => 8,
                            'margin_bottom' => 8,
                            'margin_header' => 5,
                            'margin_footer' => 5,
                            'default_font' => 'freeserif',
                            'tempDir' => WRITEPATH . 'cache',
                        ]);
                        $ultimatePdf->autoScriptToLang = true;
                        $ultimatePdf->autoLangToFont = true;
                        $ultimatePdf->SetTitle('Discharge Summary - ' . ($patientName !== '' ? $patientName : ('IPD ' . $ipdId)));
                        $ultimatePdf->SetAuthor('Atria HMS');
                        $ultimatePdf->SetHTMLFooter('<div style="font-family:freeserif,serif;font-size:9pt;color:#6b7280;text-align:right;">Page {PAGENO}/{nbpg}</div>');
                        $pdfBinary = $this->runMpdfWithTolerantWarnings(static function () use ($ultimatePdf, $plainHtml, $fileName): string {
                            $ultimatePdf->WriteHTML($plainHtml);

                            return $ultimatePdf->Output($fileName, Destination::STRING_RETURN);
                        });

                        log_message('warning', 'Discharge PDF ultimate fallback render succeeded for IPD {ipd}', ['ipd' => $ipdId]);

                        // Clear any output buffers to prevent corruption of binary PDF data
                        while (ob_get_level() > 0) {
                            ob_end_clean();
                        }

                        return $this->response
                            ->setHeader('Content-Type', 'application/pdf')
                            ->setHeader('Content-Length', (string) strlen($pdfBinary))
                            ->setHeader('Content-Disposition', 'inline; filename="' . rawurlencode($fileName) . '"; filename*=UTF-8\'\''. rawurlencode($fileName))
                            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
                            ->setHeader('Accept-Ranges', 'bytes')
                            ->setHeader('X-Content-Type-Options', 'nosniff')
                            ->setBody($pdfBinary);
                    } catch (\Throwable $ultimateError) {
                        log_message('error', 'Discharge PDF ultimate fallback failed for IPD {ipd}: {msg}', [
                            'ipd' => $ipdId,
                            'msg' => $ultimateError->getMessage(),
                        ]);

                        return $this->response
                            ->setStatusCode(500)
                            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
                            ->setBody('Unable to generate discharge PDF due to invalid template content. Please edit template CSS/HTML and retry.');
                    }
                }
            }
        }

        return view('billing/ipd/discharge_print', [
            'ipd_id' => $ipdId,
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $panelData['person_info'] ?? null,
            'content' => $renderedHtml,
            'selected_template_name' => (string) ($templatePack['selected_template_name'] ?? ''),
            'selected_template_id' => (int) ($templatePack['selected_template_id'] ?? 0),
            'print_type' => $printType,
            'print_mode' => 'standard',
        ]);
    }

    private function sanitizeDischargePdfHtml(string $html, bool $stripNamedHeaderFooterWrappers = true): string
    {
        if ($html === '') {
            return '';
        }

        $out = str_replace("\0", '', $html);
        $out = (string) preg_replace('/^\xEF\xBB\xBF/u', '', $out);

        // Sanitize CKEditor special characters that can break mPDF
        $out = $this->sanitizeCKEditorSpecialChars($out);

        if ($stripNamedHeaderFooterWrappers) {
            // For body/template HTML, strip legacy named header/footer wrapper blocks.
            $out = (string) preg_replace('/<htmlpageheader\b[^>]*>[\s\S]*?<\/htmlpageheader>/i', '', $out);
            $out = (string) preg_replace('/<htmlpagefooter\b[^>]*>[\s\S]*?<\/htmlpagefooter>/i', '', $out);
        } else {
            // For SetHTMLHeader/SetHTMLFooter payloads, keep inner content if wrapper tags were supplied.
            $out = (string) preg_replace('/<htmlpageheader\b[^>]*>([\s\S]*?)<\/htmlpageheader>/i', '$1', $out);
            $out = (string) preg_replace('/<htmlpagefooter\b[^>]*>([\s\S]*?)<\/htmlpagefooter>/i', '$1', $out);
        }

        // Remove @page header/footer name bindings that require matching named header/footer blocks.
        $out = (string) preg_replace('/\bheader\s*:\s*html[_a-z0-9-]+\s*;?/i', '', $out);
        $out = (string) preg_replace('/\bfooter\s*:\s*html[_a-z0-9-]+\s*;?/i', '', $out);

        // Clean empty "Discharge Summary" heading stubs that have no narrative content
        $out = (string) preg_replace(
            '/<h[1-6]\b[^>]*class="[^"]*discharge-section-heading[^"]*"[^>]*>\s*Discharge\s+Summary\s*<\/h[1-6]>\s*(?:<div class="discharge-status">\s*<strong>\s*Discharge\s+Summary\s*<\/strong>\s*<\/div>\s*)?(?!<div class="discharge-summary-content">)/i',
            '',
            $out
        );
        // Clean standalone discharge-status tag when it simply says "Discharge Summary"
        $out = (string) preg_replace(
            '/<div class="discharge-status">\s*<strong>\s*Discharge\s+Summary\s*<\/strong>\s*<\/div>/i',
            '',
            $out
        );

        if ($this->looksLikeMalformedDischargeHtml($out)) {
            $out = $this->normalizeMalformedDischargeHtmlForMpdf($out);
        }

        return $this->forceValidUtf8($out);
    }

    private function looksLikeMalformedDischargeHtml(string $html): bool
    {
        if ($html === '') {
            return false;
        }

        if (preg_match('/<p\b[^>]*>\s*<(?:div|table|h[1-6]|ul|ol|p)\b/i', $html) === 1) {
            return true;
        }

        if (preg_match('/<(?!\/?(?:[a-z][a-z0-9:_-]*)(?:\s|\/?>)|!--|\?xml|!doctype)/i', $html) === 1) {
            return true;
        }

        return preg_match('/<\/(?:p|div|span|strong|em|i|b)>\s*<\/(?:p|div)>/i', $html) === 1;
    }

    private function normalizeMalformedDischargeHtmlForMpdf(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $html = $this->escapeInvalidAngleBrackets($html);

        if (! class_exists('DOMDocument')) {
            return $html;
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $flags = 0;
            if (defined('LIBXML_NOWARNING')) {
                $flags |= LIBXML_NOWARNING;
            }
            if (defined('LIBXML_NOERROR')) {
                $flags |= LIBXML_NOERROR;
            }
            if (defined('LIBXML_COMPACT')) {
                $flags |= LIBXML_COMPACT;
            }

            $wrappedHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="__discharge_pdf_root__">'
                . $html
                . '</div></body></html>';

            $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrappedHtml, $flags);
            if ($loaded !== true) {
                return $html;
            }

            $normalized = '';
            $styleBlocks = [];

            $styleNodes = $dom->getElementsByTagName('style');
            foreach ($styleNodes as $styleNode) {
                if (($styleNode->parentNode->nodeName ?? '') !== 'head') {
                    continue;
                }

                $styleHtml = trim((string) $dom->saveHTML($styleNode));
                if ($styleHtml !== '') {
                    $styleBlocks[$styleHtml] = $styleHtml;
                }
            }

            $bodyNode = $dom->getElementsByTagName('body')->item(0);
            if ($bodyNode instanceof \DOMElement) {
                foreach ($bodyNode->childNodes as $childNode) {
                    if ($childNode instanceof \DOMElement && $childNode->getAttribute('id') === '__discharge_pdf_root__') {
                        foreach ($childNode->childNodes as $wrappedChildNode) {
                            $normalized .= (string) $dom->saveHTML($wrappedChildNode);
                        }

                        continue;
                    }

                    $normalized .= (string) $dom->saveHTML($childNode);
                }
            }

            if ($normalized === '') {
                return $html;
            }

            return implode('', $styleBlocks) . $normalized;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }
    }

    private function escapeInvalidAngleBrackets(string $html): string
    {
        if ($html === '') {
            return '';
        }

        return (string) preg_replace(
            '/<(?!\/?(?:[a-z][a-z0-9:_-]*)(?:\s|\/?>)|!--|\?xml|!doctype)/i',
            '&lt;',
            $html
        );
    }

    private function sanitizeCKEditorSpecialChars(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // Remove zero-width spaces and other invisible characters
        $html = str_replace([
            "\xE2\x80\x8B", // Zero-width space
            "\xE2\x80\x8C", // Zero-width non-joiner
            "\xE2\x80\x8D", // Zero-width joiner
            "\xE2\x80\x8E", // Left-to-right mark
            "\xE2\x80\x8F", // Right-to-left mark
            "\xEF\xBB\xBF", // Zero-width no-break space (BOM)
        ], '', $html);

        // Replace smart quotes and special punctuation with standard ASCII equivalents
        $replacements = [
            // Smart quotes
            "\xE2\x80\x9C" => '"',  // Left double quote
            "\xE2\x80\x9D" => '"',  // Right double quote
            "\xE2\x80\x98" => "'",  // Left single quote
            "\xE2\x80\x99" => "'",  // Right single quote
            "\xE2\x80\x9B" => "'",  // Single high-reversed-9 quotation mark
            
            // Dashes
            "\xE2\x80\x93" => '-',  // En dash
            "\xE2\x80\x94" => '-',  // Em dash
            "\xE2\x80\x95" => '-',  // Horizontal bar
            
            // Ellipsis
            "\xE2\x80\xA6" => '...',  // Horizontal ellipsis
            
            // Spaces (keep non-breaking space as HTML entity)
            "\xC2\xA0" => '&nbsp;',  // Non-breaking space
        ];
        
        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        // Decode HTML entities to actual characters (mPDF handles UTF-8 better than entities)
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove or replace problematic HTML comments that might break parsing
        $html = (string) preg_replace('/<!--\[if[^\]]*\]>[\s\S]*?<!\[endif\]-->/i', '', $html);
        
        // Clean up any malformed or nested comment blocks
        $html = (string) preg_replace('/<!--(?!<!)[^\[>][\s\S]*?-->/s', '', $html);

        return $html;
    }

    private function forceValidUtf8(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $out = str_replace("\0", '', $text);

        if (function_exists('mb_check_encoding') && ! mb_check_encoding($out, 'UTF-8')) {
            $out = mb_convert_encoding($out, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $out);
            if ($converted !== false) {
                $out = $converted;
            }
        }

        // Drop control characters that are invalid for HTML/PDF text streams.
        $out = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $out);

        return $out;
    }

    private function dedupeTopDemographicTables(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return $html;
        }

        $finalPos = stripos($html, 'Final Diagnosis');
        if ($finalPos === false || $finalPos < 1) {
            return $html;
        }

        $prefix = substr($html, 0, $finalPos);
        if ($prefix === false) {
            return $html;
        }

        if (! preg_match_all('/<table\b[^>]*>[\s\S]*?<\/table>/i', $prefix, $tableMatches, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $demographicTables = [];
        foreach ($tableMatches[0] as $match) {
            $tableHtml = (string) ($match[0] ?? '');
            $tableOffset = (int) ($match[1] ?? -1);
            if ($tableOffset < 0) {
                continue;
            }

            if ($this->isDemographicTableFragment($tableHtml)) {
                $demographicTables[] = [
                    'offset' => $tableOffset,
                    'length' => strlen($tableHtml),
                ];
            }
        }

        if (count($demographicTables) < 2) {
            return $html;
        }

        $removeOffset = (int) ($demographicTables[0]['offset'] ?? -1);
        $removeLength = (int) ($demographicTables[0]['length'] ?? 0);
        if ($removeOffset < 0 || $removeLength <= 0) {
            return $html;
        }

        $deduped = substr_replace($html, '', $removeOffset, $removeLength);

        return is_string($deduped) && trim($deduped) !== '' ? $deduped : $html;
    }

    private function isDemographicTableFragment(string $tableHtml): bool
    {
        $plain = strtolower(strip_tags($tableHtml));
        $hasCoreMarkers = str_contains($plain, 'uhid')
            && (str_contains($plain, 'ipd no') || str_contains($plain, 'ipd'));
        $hasDemographicMarkers = str_contains($plain, 'age')
            || str_contains($plain, 'guardian')
            || str_contains($plain, 'admission')
            || str_contains($plain, 'discharge');

        return $hasCoreMarkers && $hasDemographicMarkers;
    }

    /**
     * mPDF can emit PHP warnings/notices for malformed legacy HTML/CSS while still producing valid output.
     * In CI4 development mode those warnings become exceptions, so we suppress known non-fatal mPDF warnings.
     */
    private function runMpdfWithTolerantWarnings(callable $callback): string
    {
        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
            $normalizedFile = str_replace('\\', '/', strtolower($file));
            $normalizedMessage = strtolower($message);

            if (str_contains($normalizedFile, '/vendor/mpdf/') && str_contains($normalizedMessage, 'undefined array key')) {
                log_message('warning', 'Suppressed non-fatal mPDF warning at {file}:{line} => {msg}', [
                    'file' => $file,
                    'line' => $line,
                    'msg' => $message,
                ]);

                return true;
            }

            return false;
        });

        try {
            $result = $callback();

            return is_string($result) ? $result : '';
        } finally {
            restore_error_handler();
        }
    }

    private function createIpdDischargeWorkTask(int $ipdId, array $panelData): void
    {
        $person = $panelData['person_info'] ?? null;
        if (! $person) {
            return;
        }

        $patientId = (int) ($person->id ?? $person->p_id ?? 0);
        if ($patientId <= 0) {
            return;
        }

        $abhaId = trim((string) (
            $person->abha_id
            ?? $person->abha_no
            ?? $person->abha_address
            ?? $person->abha
            ?? ''
        ));

        if (preg_match('/^\d{14}$/', $abhaId) !== 1) {
            return;
        }

        $taskService = new AbdmWorkTaskService();
        $taskService->createOrRefreshTask(
            'ipd_discharge_publish',
            'ipd_discharge',
            'ipd',
            (string) $ipdId,
            $patientId,
            trim((string) ($person->p_fname ?? '')),
            $abhaId,
            'submit',
            [
                'ipd_id' => $ipdId,
                'trigger' => 'ipd.discharge.printed',
            ]
        );
    }

    private function buildDischargePdfHtml(array $panelData, string $renderedContent, bool $withHeader, string $templateName): string
    {
        // mPDF does not require a full HTML document — return a clean fragment.
        return '<style>'
            . 'body{font-family:freeserif,serif;font-size:11pt;color:#111827;line-height:1.4;}'
            . 'table{width:100%;border-collapse:collapse;margin:6px 0 10px 0;font-size:10pt;}'
            . 'th,td{border:1px solid #d1d5db;padding:5px;vertical-align:top;}'
            . 'table.no-border th,table.no-border td,table[border="0"] th,table[border="0"] td{border:none !important;}'
            . 'ul,ol{margin:4px 0 10px 18px;padding:0;}'
            . '</style>'
            . $renderedContent;
    }

    private function cacheAbdmIpdPdf(int $ipdId, string $fileName, string $pdfBinary): void
    {
        if ($ipdId <= 0 || $pdfBinary === '' || ! str_starts_with($pdfBinary, '%PDF-')) {
            return;
        }

        try {
            $directory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'abdm' . DIRECTORY_SEPARATOR . 'ipd' . DIRECTORY_SEPARATOR . $ipdId;
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                return;
            }

            file_put_contents($directory . DIRECTORY_SEPARATOR . $fileName, $pdfBinary, LOCK_EX);
        } catch (\Throwable $e) {
            log_message('warning', 'Unable to cache ABDM discharge PDF for IPD {ipd}: {msg}', [
                'ipd' => $ipdId,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function generateDischargeSummaryPdfBinary(int $ipdId, bool $withHeader = true): ?string
    {
        if ($ipdId <= 0) {
            return null;
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return null;
        }

        $content = $this->getDischargeContent($ipdId);
        if (trim(strip_tags($content)) === '') {
            $content = $this->buildAutoDischargeContent($ipdId, $panelData);
            if (trim(strip_tags($content)) !== '') {
                $this->saveDischargeContent($ipdId, $content);
            }
        }

        $templatePack = $this->applyDischargeTemplate($content, $panelData, null);
        $templateSettings = is_array($templatePack['selected_template_settings'] ?? null)
            ? $templatePack['selected_template_settings']
            : $this->defaultDischargeTemplateSettings();

        $renderedHtml = $this->sanitizeDischargePdfHtml((string) ($templatePack['rendered_html'] ?? $content));
        $renderedHtml = $this->dedupeTopDemographicTables($renderedHtml);
        $templateName = (string) ($templatePack['selected_template_name'] ?? 'Discharge Template');
        $templateTokenVars = $this->buildDischargeTemplateTokenVars($panelData, $content);
        $templateTokenVars['TEMPLATE_NAME'] = esc($templateName);

        $patient = $panelData['person_info'] ?? null;
        $ipd = $panelData['ipd_info'] ?? null;
        $patientName = trim((string) ($patient->p_fname ?? 'Patient'));
        $ipdCode = trim((string) ($ipd->ipd_code ?? $ipdId));
        $fileName = 'discharge_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $ipdCode !== '' ? $ipdCode : (string) $ipdId) . '.pdf';

        try {
            $headerHtml = '';
            if ($withHeader) {
                $configuredHeader = $this->applyDischargeTemplateTokens(
                    trim((string) ($templateSettings['header_html'] ?? '')),
                    $templateTokenVars
                );
                $headerHtml = $this->sanitizeDischargePdfHtml($configuredHeader, false);
                $headerHtml = mpdf_normalize_font_weight_css($headerHtml);
            }

            $configuredFooter = $this->applyDischargeTemplateTokens(
                trim((string) ($templateSettings['footer_html'] ?? '')),
                $templateTokenVars
            );
            $footerHtml = $this->sanitizeDischargePdfHtml($configuredFooter, false);
            $footerHtml = mpdf_normalize_font_weight_css($footerHtml);
            if ($footerHtml === '') {
                $footerHtml = '<div style="font-family:freeserif,serif;font-size:9pt;color:#6b7280;text-align:right;">Page {PAGENO}/{nbpg}</div>';
            }

            $pdfHtml = $this->buildDischargePdfHtml($panelData, $renderedHtml, $withHeader, $templateName);
            $pdfHtml = mpdf_normalize_font_weight_css($pdfHtml);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => ($templateSettings['page_size'] ?? 'A4') === 'CUSTOM'
                    ? [max(20, (int) ($templateSettings['custom_width_mm'] ?? 210)), max(20, (int) ($templateSettings['custom_height_mm'] ?? 297))]
                    : (string) ($templateSettings['page_size'] ?? 'A4'),
                'margin_left'   => max(0, ((float) ($templateSettings['page_margin_left_cm']   ?? 0.8)) * 10),
                'margin_right'  => max(0, ((float) ($templateSettings['page_margin_right_cm']  ?? 0.8)) * 10),
                'margin_top'    => max(0, ((float) ($templateSettings['page_margin_top_cm']    ?? 0.8)) * 10),
                'margin_bottom' => max(0, ((float) ($templateSettings['page_margin_bottom_cm'] ?? 0.8)) * 10),
                'margin_header' => max(0, ((float) ($templateSettings['margin_header_cm']      ?? 0.5)) * 10),
                'margin_footer' => max(0, ((float) ($templateSettings['margin_footer_cm']      ?? 0.5)) * 10),
                'default_font' => 'freeserif',
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            $mpdf->SetTitle('Discharge Summary - ' . ($patientName !== '' ? $patientName : ('IPD ' . $ipdId)));
            $mpdf->SetAuthor('Atria HMS');

            if ($withHeader && $headerHtml !== '') {
                $mpdf->SetHTMLHeader($headerHtml, 'O');
                $mpdf->SetHTMLHeader($headerHtml, 'E');
            }
            if ($footerHtml !== '') {
                $mpdf->SetHTMLFooter($footerHtml, 'O');
                $mpdf->SetHTMLFooter($footerHtml, 'E');
            }

            $templateCss = trim((string) ($templateSettings['template_css'] ?? ''));
            if ($templateCss !== '') {
                $templateCss = (string) preg_replace('/@page(?:\s+[^{]+)?\s*\{[^{}]*\}/i', '', $templateCss);
                $pdfHtml = '<style>' . "\n" . $templateCss . "\n" . '</style>' . "\n" . $pdfHtml;
            }

            $pdfBinary = $this->runMpdfWithTolerantWarnings(static function () use ($mpdf, $pdfHtml, $fileName): string {
                $mpdf->WriteHTML($pdfHtml);

                return $mpdf->Output($fileName, Destination::STRING_RETURN);
            });

            if ($pdfBinary !== '' && str_starts_with($pdfBinary, '%PDF-')) {
                $this->cacheAbdmIpdPdf($ipdId, 'discharge-summary.pdf', $pdfBinary);
                return $pdfBinary;
            }
        } catch (\Throwable $e) {
            log_message('error', 'generateDischargeSummaryPdfBinary failed for IPD {ipd}: {msg}', [
                'ipd' => $ipdId,
                'msg' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function buildIpdPdfDocumentsList(int $ipdId, string $dischargeRaw): array
    {
        $directory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'abdm' . DIRECTORY_SEPARATOR . 'ipd' . DIRECTORY_SEPARATOR . $ipdId;
        $createdAt = $this->toIsoDateTimeOrNow($dischargeRaw);

        // Ensure IPD Discharge Summary PDF is cached if missing
        $summaryPath = $directory . DIRECTORY_SEPARATOR . 'discharge-summary.pdf';
        if (! is_file($summaryPath) || filesize($summaryPath) === 0) {
            try {
                $this->generateDischargeSummaryPdfBinary($ipdId, true);
            } catch (\Throwable $e) {
                log_message('error', 'Auto-generating IPD Discharge Summary PDF failed for IPD {ipd}: {msg}', [
                    'ipd' => $ipdId,
                    'msg' => $e->getMessage(),
                ]);
            }
        }

        // Ensure IPD Bill PDF is cached if missing
        $billPath = $directory . DIRECTORY_SEPARATOR . 'ipd-bill.pdf';
        if (! is_file($billPath) || filesize($billPath) === 0) {
            try {
                $billingCtrl = new \App\Controllers\Billing\Ipd();
                $request = \Config\Services::request();
                $response = \Config\Services::response();
                $logger = \Config\Services::logger();
                $billingCtrl->initController($request, $response, $logger);
                $billingCtrl->generateIpdBillPdfBinary($ipdId, 1, true);
            } catch (\Throwable $e) {
                log_message('error', 'Auto-generating IPD Bill PDF failed for IPD {ipd}: {msg}', [
                    'ipd' => $ipdId,
                    'msg' => $e->getMessage(),
                ]);
            }
        }

        $documents = [];
        $definitions = [
            ['file' => 'discharge-summary.pdf', 'title' => 'IPD Discharge Summary', 'loinc' => '18842-5', 'snomed' => '373942005'],
            ['file' => 'ipd-bill.pdf', 'title' => 'IPD Bill / Invoice', 'loinc' => '75490-3', 'snomed' => '823651000000106'],
        ];

        foreach ($definitions as $definition) {
            $path = $directory . DIRECTORY_SEPARATOR . $definition['file'];
            if (! is_file($path) || filesize($path) === 0) {
                continue;
            }
            $binary = file_get_contents($path);
            if (! is_string($binary) || ! str_starts_with($binary, '%PDF-')) {
                continue;
            }
            $documents[] = [
                'title' => $definition['title'],
                'loinc_code' => $definition['loinc'],
                'snomed_code' => $definition['snomed'],
                'content_type' => 'application/pdf',
                'data' => base64_encode($binary),
                'created_at' => $createdAt,
                'path' => $path,
            ];
        }

        return $documents;
    }

    private function formatDischargeMedDirections($doseVal, $whenVal, $freqVal, string $days = '', string $remark = ''): string
    {
        static $whenCodeDescMap = [
            'BF'  => 'BF (BEFORE FOOD)',  'AF'  => 'AF (AFTER FOOD)',
            'WF'  => 'WF (WITH FOOD)',    'ES'  => 'ES (EMPTY STOMACH)',
            'BBF' => 'BBF (BEFORE BREAKFAST)', 'ABF' => 'ABF (AFTER BREAKFAST)',
            'BL'  => 'BL (BEFORE LUNCH)', 'AL'  => 'AL (AFTER LUNCH)',
            'BD'  => 'BD (BEFORE DINNER)', 'AD'  => 'AD (AFTER DINNER)',
            'BT'  => 'BT (BED TIME)',
        ];
        static $whenHindiMap = [
            'BF' => 'भोजन से पहले', 'BEFORE FOOD' => 'भोजन से पहले',
            'AF' => 'भोजन के बाद',  'AFTER FOOD'  => 'भोजन के बाद',
            'WF' => 'भोजन के साथ',  'WITH FOOD'   => 'भोजन के साथ',
            'ES' => 'सुबह खाली पेट', 'EMPTY STOMACH' => 'सुबह खाली पेट',
            'BBF'=> 'नाश्ते से पहले', 'BEFORE BREAKFAST' => 'नाश्ते से पहले',
            'ABF'=> 'नाश्ते के बाद',  'AFTER BREAKFAST'  => 'नाश्ते के बाद',
            'BL' => 'दोपहर के भोजन से पहले', 'BEFORE LUNCH' => 'दोपहर के भोजन से पहले',
            'AL' => 'दोपहर के भोजन के बाद',  'AFTER LUNCH'  => 'दोपहर के भोजन के बाद',
            'BD' => 'रात के भोजन से पहले',   'BEFORE DINNER'=> 'रात के भोजन से पहले',
            'AD' => 'रात के भोजन के बाद',    'AFTER DINNER' => 'रात के भोजन के बाद',
            'BT' => 'रात को सोते समय',       'BED TIME'     => 'रात को सोते समय',
        ];
        static $freqHindiMap = [
            'OD'  => 'दिन में एक बार (OD)',   'BD'  => 'दिन में दो बार (BD)',
            'TDS' => 'दिन में तीन बार (TDS)', 'TID' => 'दिन में तीन बार (TID)',
            'QID' => 'दिन में चार बार (QID)', 'HS'  => 'रात को सोते समय (HS)',
            'SOS' => 'ज़रूरत पड़ने पर (SOS)',  'STAT'=> 'तुरंत एक बार (STAT)',
            'ALTERNATE DAY' => 'एक दिन छोड़कर',
            'DAILY'   => 'प्रतिदिन',
            'WEEKLY'  => 'हफ़्ते में एक बार',
            'MONTHLY' => 'महीने में एक बार',
        ];
        static $remarkHindiMap = [
            'TAKE WITH MILK'                    => 'दूध के साथ लें',
            'TAKE WITH WARM WATER'              => 'गुनगुने पानी के साथ लें',
            'AVOID SOUR FOOD AND DAIRY PRODUCTS'=> 'खट्टा और डेयरी उत्पाद न लें',
            'TAKE AFTER MEALS'                  => 'भोजन के बाद लें',
            'TAKE ON AN EMPTY STOMACH EARLY MORNING' => 'सुबह खाली पेट लें',
            'CHEW WELL BEFORE SWALLOWING'       => 'चबाकर खाएं',
            'DISSOLVE IN HALF GLASS OF WATER'   => 'आधे गिलास पानी में घोलकर लें',
            'APPLY LOCALLY TWICE DAILY'         => 'दिन में दो बार लगाएं',
            'DO NOT CRUSH OR CHEW TABLET'       => 'गोली को तोड़े या चबाएं नहीं',
            'AVOID ALCOHOL WHILE TAKING THIS MEDICINE' => 'शराब का सेवन न करें',
            'COMPLETE FULL COURSE OF ANTIBIOTICS'      => 'एंटीबायोटिक का पूरा कोर्स लें',
            'DRINK PLENTY OF FLUIDS / WATER'    => 'प्रचुर मात्रा में पानी पिएं',
        ];

        static $doseCache = null;
        static $whenCache = null;
        static $freqCache = null;
        static $whenHindiCache = null;
        static $freqHindiCache = null;

        if ($doseCache === null && $this->db->tableExists('opd_dose_shed')) {
            $doseCache = [];
            $fields = $this->db->getFieldNames('opd_dose_shed') ?? [];
            $idCol = in_array('dose_shed_id', $fields, true) ? 'dose_shed_id' : (in_array('id', $fields, true) ? 'id' : '');
            $descCol = in_array('dose_show_desc', $fields, true) ? 'dose_show_desc' : (in_array('dose_show_sign', $fields, true) ? 'dose_show_sign' : (in_array('dose_desc', $fields, true) ? 'dose_desc' : ''));
            if ($idCol !== '' && $descCol !== '') {
                foreach ($this->db->table('opd_dose_shed')->select("$idCol, $descCol")->get()->getResultArray() as $r) {
                    $doseCache[(int) $r[$idCol]] = trim((string) ($r[$descCol] ?? ''));
                }
            }
        }
        if ($whenCache === null && $this->db->tableExists('opd_dose_when')) {
            $whenCache = [];
            $whenHindiCache = [];
            $fields = $this->db->getFieldNames('opd_dose_when') ?? [];
            $idCol = in_array('dose_when_id', $fields, true) ? 'dose_when_id' : (in_array('id', $fields, true) ? 'id' : '');
            $signCol = in_array('dose_sign', $fields, true) ? 'dose_sign' : (in_array('dose_sign_desc', $fields, true) ? 'dose_sign_desc' : '');
            $hasHindi = in_array('dose_sign_hindi', $fields, true);
            if ($idCol !== '' && $signCol !== '') {
                $selectCols = "$idCol, $signCol" . ($hasHindi ? ', dose_sign_hindi' : '');
                foreach ($this->db->table('opd_dose_when')->select($selectCols)->get()->getResultArray() as $r) {
                    $whenCache[(int) $r[$idCol]] = trim((string) ($r[$signCol] ?? ''));
                    if ($hasHindi) {
                        $whenHindiCache[(int) $r[$idCol]] = trim((string) ($r['dose_sign_hindi'] ?? ''));
                    }
                }
            }
        }
        if ($freqCache === null && $this->db->tableExists('opd_dose_frequency')) {
            $freqCache = [];
            $freqHindiCache = [];
            $fields = $this->db->getFieldNames('opd_dose_frequency') ?? [];
            $idCol = in_array('dose_freq_id', $fields, true) ? 'dose_freq_id' : (in_array('id', $fields, true) ? 'id' : '');
            $signCol = in_array('dose_sign', $fields, true) ? 'dose_sign' : (in_array('dose_sign_desc', $fields, true) ? 'dose_sign_desc' : '');
            $hasHindi = in_array('dose_sign_hindi', $fields, true);
            if ($idCol !== '' && $signCol !== '') {
                $selectCols = "$idCol, $signCol" . ($hasHindi ? ', dose_sign_hindi' : '');
                foreach ($this->db->table('opd_dose_frequency')->select($selectCols)->get()->getResultArray() as $r) {
                    $freqCache[(int) $r[$idCol]] = trim((string) ($r[$signCol] ?? ''));
                    if ($hasHindi) {
                        $freqHindiCache[(int) $r[$idCol]] = trim((string) ($r['dose_sign_hindi'] ?? ''));
                    }
                }
            }
        }

        $doseText = '';
        if (is_numeric($doseVal) && (int) $doseVal > 0) {
            $doseText = $doseCache[(int) $doseVal] ?? '';
        } elseif (is_string($doseVal) && trim($doseVal) !== '' && trim($doseVal) !== '0') {
            $doseText = trim($doseVal);
        }

        $whenText = '';
        $whenHindi = '';
        if (is_numeric($whenVal) && (int) $whenVal > 0) {
            $whenText = $whenCache[(int) $whenVal] ?? '';
            $whenHindi = $whenHindiCache[(int) $whenVal] ?? '';
        } elseif (is_string($whenVal) && trim($whenVal) !== '' && trim($whenVal) !== '0') {
            $whenText = trim($whenVal);
        }
        if ($whenText !== '') {
            $upperWhen = strtoupper($whenText);
            if (isset($whenCodeDescMap[$upperWhen])) {
                $whenText = $whenCodeDescMap[$upperWhen];
            }
            if ($whenHindi === '' && isset($whenHindiMap[$upperWhen])) {
                $whenHindi = $whenHindiMap[$upperWhen];
            }
        }

        $freqText = '';
        $freqHindi = '';
        if (is_numeric($freqVal) && (int) $freqVal > 0) {
            $freqText = $freqCache[(int) $freqVal] ?? '';
            $freqHindi = $freqHindiCache[(int) $freqVal] ?? '';
        } elseif (is_string($freqVal) && trim($freqVal) !== '' && trim($freqVal) !== '0') {
            $freqText = trim($freqVal);
        }
        if ($freqText !== '') {
            $upperFreq = strtoupper($freqText);
            if ($freqHindi === '' && isset($freqHindiMap[$upperFreq])) {
                $freqHindi = $freqHindiMap[$upperFreq];
            }
        }

        $cleanRemark = (string) preg_replace('/^(edit\s*remove|remove\s*edit|edit|remove|delete)\s*$/i', '', trim($remark));
        $cleanRemark = trim($cleanRemark);
        $remarkHindi = '';
        if ($cleanRemark !== '') {
            $upperRemark = strtoupper($cleanRemark);
            if (isset($remarkHindiMap[$upperRemark])) {
                $remarkHindi = $remarkHindiMap[$upperRemark];
            }
        }

        $dirParts = [];
        $dirLocalParts = [];

        if ($whenText !== '') {
            $dirParts[] = $whenText;
        }
        if ($whenHindi !== '') {
            $dirLocalParts[] = $whenHindi;
        }

        if ($doseText !== '') {
            $dirParts[] = $doseText;
        }

        if ($freqText !== '') {
            $dirParts[] = $freqText;
        }
        if ($freqHindi !== '') {
            $dirLocalParts[] = $freqHindi;
        }

        if ($cleanRemark !== '') {
            $dirParts[] = $cleanRemark;
        }
        if ($remarkHindi !== '') {
            $dirLocalParts[] = $remarkHindi;
        }

        $daysClean = trim($days);
        if ($daysClean !== '' && $daysClean !== '0') {
            $daysNum = is_numeric($daysClean) ? (int) $daysClean : 0;
            $dirParts[] = $daysNum > 0 ? ('for ' . $daysNum . ' days') : ('for ' . $daysClean);
            if ($daysNum > 0) {
                $dirLocalParts[] = $daysNum . ' दिन';
            }
        }

        $engText = implode(' | ', array_values(array_unique(array_filter($dirParts))));
        $hinText = implode(' | ', array_values(array_unique(array_filter($dirLocalParts))));

        if ($engText !== '' && $hinText !== '') {
            return $engText . ' | ' . $hinText;
        }

        return $engText !== '' ? $engText : $hinText;
    }

    public function show_file3(int $ipdId)
    {
        return $this->show_discharge($ipdId, 3);
    }

    private function enqueueIpdDischargeSync(int $ipdId, int $patientId, string $action): void
    {
        if ($ipdId <= 0) {
            return;
        }

        $ipdRow = $this->db->tableExists('ipd_master')
            ? ($this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [])
            : [];

        if ($patientId <= 0) {
            $patientId = (int) ($ipdRow['p_id'] ?? 0);
        }

        $patientRow = [];
        if ($patientId > 0 && $this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        }

        $abhaId = '';
        foreach (['abha_id', 'abha_no', 'abha_address', 'abha'] as $field) {
            $candidate = trim((string) ($patientRow[$field] ?? ''));
            if ($candidate !== '') {
                $abhaId = $candidate;
                break;
            }
        }

        $dischargeContent = '';
        if ($this->db->tableExists('ipd_discharge') && $this->db->fieldExists('content', 'ipd_discharge')) {
            $dischargeRow = $this->db->table('ipd_discharge')
                ->select('content')
                ->where('ipd_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
            $dischargeContent = trim((string) ($dischargeRow['content'] ?? ''));
        }

        $payload = [
            'ipd_id' => $ipdId,
            'patient_id' => $patientId,
            'ipd_code' => trim((string) ($ipdRow['ipd_code'] ?? '')),
            'patient_name' => trim((string) ($patientRow['p_fname'] ?? $ipdRow['P_name'] ?? '')),
            'abha_id' => $abhaId,
            'action' => trim($action) !== '' ? trim($action) : 'save_main',
            'discharge_date' => trim((string) ($ipdRow['discharge_date'] ?? '')),
            'discharge_time' => trim((string) ($ipdRow['discharge_time'] ?? '')),
            'discharge_status' => trim((string) ($ipdRow['discarge_patient_status'] ?? '')),
            'summary_html' => $dischargeContent,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $bridgeSync = new BridgeSyncService();
            $bridgeSync->enqueue('ipd.discharge.updated', $payload, 'ipd_discharge', (string) $ipdId);
        } catch (\Throwable $e) {
            // Do not block discharge workflows if queue service is unavailable.
        }

        // Also persist ABDM-compatible FHIR bundle in sync tables/outbox for M2 push.
        // Fail-open: discharge save flow must never fail due to ABDM sync issues.
        try {
            $this->enqueueIpdDischargeFhirSync($ipdId, $patientId, $ipdRow, $patientRow);
        } catch (\Throwable $e) {
            // Intentionally ignored.
        }
    }

    private function enqueueIpdDischargeFhirSync(int $ipdId, int $patientId, array $ipdRow, array $patientRow): void
    {
        if ($ipdId <= 0 || $patientId <= 0) {
            return;
        }

        $hfrId = trim($this->getHospitalSettingValue('ABDM_HFR_ID'));
        if ($hfrId === '') {
            return;
        }

        $lastName = trim((string) ($patientRow['p_lname'] ?? ''));
        if (! $this->isMeaningfulDischargeValue($lastName)) {
            $lastName = '';
        }
        $patientName = trim(trim((string) ($patientRow['p_fname'] ?? '')) . ' ' . $lastName);
        if ($patientName === '') {
            $patientName = trim((string) ($ipdRow['P_name'] ?? ''));
        }

        $abhaIdRaw = '';
        foreach (['abha_id', 'abha_no', 'abha'] as $field) {
            $value = trim((string) ($patientRow[$field] ?? ''));
            if ($value !== '') {
                $abhaIdRaw = $value;
                break;
            }
        }
        if ($abhaIdRaw === '') {
            $abhaIdRaw = trim((string) ($patientRow['abha_address'] ?? ''));
        }

        $abhaDigits = preg_replace('/\D/', '', $abhaIdRaw);
        $abhaDigits = is_string($abhaDigits) ? $abhaDigits : '';
        $abhaAddress = trim((string) ($patientRow['abha_address'] ?? ''));
        if ($abhaAddress === '' && strpos($abhaIdRaw, '@') !== false) {
            $abhaAddress = $abhaIdRaw;
        }

        $admissionRaw = trim((string) ($ipdRow['register_date'] ?? ''));
        $dischargeRaw = trim((string) ($ipdRow['discharge_date'] ?? ''));
        $admissionIso = $this->toIsoDateTimeOrNow($admissionRaw);
        $dischargeIso = $this->toIsoDateTimeOrNow($dischargeRaw);
        $visitDate = $dischargeRaw !== ''
            ? date('Y-m-d', strtotime($dischargeRaw))
            : ($admissionRaw !== '' ? date('Y-m-d', strtotime($admissionRaw)) : date('Y-m-d'));

        $diagnosisRows = $this->byIpdRows('ipd_discharge_diagnosis', ['comp_report'], 'id ASC', $ipdId);
        $conditionRows = [];
        foreach ($diagnosisRows as $row) {
            $text = trim((string) ($row['comp_report'] ?? ''));
            if ($text !== '') {
                $conditionRows[] = ['text' => $text, 'code' => ''];
            }
        }

        $chiefComplaintsList = [];
        if ($this->tableHasColumns('ipd_discharge_complaint', ['ipd_id', 'comp_report'])) {
            $complaintRows = $this->byIpdRows('ipd_discharge_complaint', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
            foreach ($complaintRows as $row) {
                $term = trim((string) ($row['comp_report'] ?? ''));
                $duration = trim((string) ($row['comp_remark'] ?? ''));
                if ($term !== '') {
                    $text = $term . ($duration !== '' ? (' - ' . $duration) : '');
                    $chiefComplaintsList[] = ['text' => $text, 'code' => ''];
                }
            }
        }

        $complaintRemarkText = '';
        if ($this->tableHasColumns('ipd_discharge_complaint_remark', ['ipd_id', 'comp_remark'])) {
            $complaintRemarkRow = $this->firstRowByIpd('ipd_discharge_complaint_remark', $ipdId);
            $complaintRemarkText = trim((string) ($complaintRemarkRow['comp_remark'] ?? ''));
        }

        if (empty($chiefComplaintsList) && $complaintRemarkText !== '') {
            $chiefComplaintsList[] = ['text' => $complaintRemarkText, 'code' => ''];
        }

        $procedureRows = [];
        foreach ($this->byIpdRows('ipd_discharge_surgery', ['surgery_name', 'surgery_date'], 'id ASC', $ipdId) as $row) {
            $text = trim((string) ($row['surgery_name'] ?? ''));
            if ($text !== '') {
                $procedureRows[] = [
                    'text' => $text,
                    'code' => '',
                    'performed_at' => $this->toIsoDateTimeOrNow(trim((string) ($row['surgery_date'] ?? ''))),
                ];
            }
        }
        foreach ($this->byIpdRows('ipd_discharge_procedure', ['procedure_name', 'procedure_date'], 'id ASC', $ipdId) as $row) {
            $text = trim((string) ($row['procedure_name'] ?? ''));
            if ($text !== '') {
                $procedureRows[] = [
                    'text' => $text,
                    'code' => '',
                    'performed_at' => $this->toIsoDateTimeOrNow(trim((string) ($row['procedure_date'] ?? ''))),
                ];
            }
        }

        $medicationRows = [];
        $legacyMeds = $this->byIpdRows('ipd_discharge_prescrption_prescribed', ['med_name', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days', 'remark'], 'id ASC', $ipdId);
        if (empty($legacyMeds)) {
            $legacyMeds = $this->byIpdRows('ipd_discharge_prescription_prescribed', ['med_name', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days', 'remark'], 'id ASC', $ipdId);
        }
        foreach ($legacyMeds as $row) {
            $name = trim((string) ($row['med_name'] ?? ''));
            if (! $this->isMeaningfulDischargeValue($name)) {
                continue;
            }
            $dose = $this->formatDischargeMedDirections(
                $row['dosage'] ?? '',
                $row['dosage_when'] ?? '',
                $row['dosage_freq'] ?? '',
                (string) ($row['no_of_days'] ?? ''),
                (string) ($row['remark'] ?? '')
            );
            $medicationRows[] = ['name' => $name, 'dosage' => $dose];
        }
        if (empty($medicationRows)) {
            foreach ($this->byIpdRows('ipd_discharge_drug', ['drug_name', 'drug_dose', 'drug_day'], 'id ASC', $ipdId) as $row) {
                $name = trim((string) ($row['drug_name'] ?? ''));
                if (! $this->isMeaningfulDischargeValue($name)) {
                    continue;
                }
                $dose = trim(implode(' ', array_filter([
                    (string) ($row['drug_dose'] ?? ''),
                    (string) ($row['drug_day'] ?? ''),
                ], fn ($v) => $this->isMeaningfulDischargeValue((string) $v))));
                $medicationRows[] = ['name' => $name, 'dosage' => $dose];
            }
        }

        $observationRows = [];
        if ($this->tableHasColumns('ipd_discharge_1_b', ['ipd_d_id'])) {
            $cols = $this->db->getFieldNames('ipd_discharge_1_b') ?? [];
            $sel = in_array('short_head', $cols, true) ? 'short_head,rdata' : '*';
            foreach ($this->db->table('ipd_discharge_1_b')->select($sel)->where('ipd_d_id', $ipdId)->get()->getResultArray() as $row) {
                $label = trim((string) ($row['short_head'] ?? ''));
                $value = trim((string) ($row['rdata'] ?? ''));
                if ($label === '' || ! $this->isMeaningfulDischargeValue($value)) {
                    continue;
                }
                $observationRows[] = [
                    'text' => $label,
                    'value' => $value,
                    'category' => 'Condition on Admission Time',
                    'category_code' => 'vital-signs',
                    'effective_at' => $admissionIso,
                ];
            }
        }
        if ($this->tableHasColumns('ipd_discharge_1_b_final', ['ipd_d_id'])) {
            $cols = $this->db->getFieldNames('ipd_discharge_1_b_final') ?? [];
            $sel = in_array('short_head', $cols, true) ? 'short_head,rdata' : '*';
            foreach ($this->db->table('ipd_discharge_1_b_final')->select($sel)->where('ipd_d_id', $ipdId)->get()->getResultArray() as $row) {
                $label = trim((string) ($row['short_head'] ?? ''));
                $value = trim((string) ($row['rdata'] ?? ''));
                if ($label === '' || ! $this->isMeaningfulDischargeValue($value)) {
                    continue;
                }
                $observationRows[] = [
                    'text' => $label,
                    'value' => $value,
                    'category' => 'Condition on Discharge Time',
                    'category_code' => 'vital-signs',
                    'effective_at' => $dischargeIso,
                ];
            }
        }

        
        $uiSummaryInvestigation = '';
        if ($this->tableHasColumns('ipd_discharge_investigtions_inhos', ['ipd_id', 'comp_remark'])) {
            $inhosRow = $this->firstRowByIpd('ipd_discharge_investigtions_inhos', $ipdId);
            $uiSummaryInvestigation = trim((string) ($inhosRow['comp_remark'] ?? ''));
        }

        $investigationRows = [];
        if ($this->tableHasColumns('ipd_discharge_1_d', ['ipd_d_id'])) {
            $cols = $this->db->getFieldNames('ipd_discharge_1_d') ?? [];
            $sel = in_array('short_head', $cols, true) ? 'short_head,rdata' : '*';
            foreach ($this->db->table('ipd_discharge_1_d')->select($sel)->where('ipd_d_id', $ipdId)->get()->getResultArray() as $row) {
                $label = trim((string) ($row['short_head'] ?? ''));
                $value = trim((string) ($row['rdata'] ?? ''));
                if ($label === '' || ! $this->isMeaningfulDischargeValue($value)) {
                    continue;
                }
                $investigationRows[] = [
                    'text' => $label . ': ' . $value,
                    'authored_on' => $admissionIso,
                ];
            }
        }
        if ($this->tableHasColumns('ipd_discharge_1_e', ['ipd_d_id'])) {
            $cols = $this->db->getFieldNames('ipd_discharge_1_e') ?? [];
            $sel = in_array('short_head', $cols, true) ? 'short_head,rdata' : '*';
            foreach ($this->db->table('ipd_discharge_1_e')->select($sel)->where('ipd_d_id', $ipdId)->get()->getResultArray() as $row) {
                $label = trim((string) ($row['short_head'] ?? ''));
                $value = trim((string) ($row['rdata'] ?? ''));
                if ($label === '' || ! $this->isMeaningfulDischargeValue($value)) {
                    continue;
                }
                $investigationRows[] = [
                    'text' => $label . ': ' . $value,
                    'authored_on' => $admissionIso,
                ];
            }
        }

        $allergyRows = [];
        $carePlanRows = [];
        $instructionRows = $this->byIpdRows('ipd_discharge_instructions', ['comp_report', 'comp_remark', 'review_after'], 'id DESC', $ipdId);
        if (! empty($instructionRows)) {
            $instructionRow = $instructionRows[0];
            $instructionMeta = $this->parseInstructionMetaPayload((string) ($instructionRow['comp_report'] ?? ''));
            $nabhMeta = is_array($instructionMeta['nabh'] ?? null) ? $instructionMeta['nabh'] : [];

            $allergyStatus = strtolower(trim((string) ($nabhMeta['drug_allergy_status'] ?? '')));
            $allergyDetails = trim((string) ($nabhMeta['drug_allergy_details'] ?? ''));
            if ($allergyStatus !== '' || $allergyDetails !== '') {
                $allergyRows[] = [
                    'text' => $allergyDetails !== '' ? $allergyDetails : ('Drug allergy status: ' . $allergyStatus),
                    'note' => $allergyStatus,
                    'criticality' => $allergyStatus === 'yes' ? 'high' : 'low',
                    'recorded_at' => $dischargeIso,
                ];
            }

            $reviewAfter = trim((string) ($instructionRow['review_after'] ?? ''));
            if ($reviewAfter !== '') {
                $reviewDate = '';
                if ($dischargeRaw !== '' && is_numeric($reviewAfter)) {
                    $reviewTs = strtotime($dischargeRaw . ' +' . (int) $reviewAfter . ' days');
                    if ($reviewTs !== false) {
                        $reviewDate = ' (' . date('d-m-Y', $reviewTs) . ')';
                    }
                }
                $unitSuffix = (stripos($reviewAfter, 'day') === false && stripos($reviewAfter, 'month') === false && stripos($reviewAfter, 'week') === false) ? ' Days' : '';
                $carePlanRows[] = [
                    'title' => 'Follow Up',
                    'description' => 'Review After ' . $reviewAfter . $unitSuffix . $reviewDate . ' or as and when required',
                    'created_at' => $dischargeIso,
                ];
            }
        }

        
        $uiClinicalHistoryText = '';
        $historyList = [];
        
        $opdHistorySnapshot = [];
        if ($patientId > 0 && $this->db->tableExists('patient_master')) {
            $historyFields = [
                'is_smoking' => 'Smoking',
                'is_alcohol' => 'Alcohol',
                'is_drug_abuse' => 'Drug Abuse',
                'is_tobacoo' => 'Tobacco',
                'is_hypertesion' => 'Hypertension',
                'is_diabetes' => 'Diabetes',
                'is_ischaemic_heart_ds' => 'Ischemic Heart Disease',
                'is_asthma_copd' => 'Asthma/COPD',
                'is_bleeding_disorder' => 'Bleeding Disorder',
                'is_heapatitis_type' => 'Hepatitis',
                'is_blood_transfusion' => 'Blood Transfusion',
                'is_hiv_std' => 'HIV/STD',
                'is_epilepsy' => 'Epilepsy',
                'is_tb' => 'TB',
                'is_psychiatric_illness' => 'Psychiatric Illness',
                'is_thyroid' => 'Thyroid',
            ];
            foreach ($historyFields as $f => $label) {
                if ((int) ($patientRow[$f] ?? 0) === 1) {
                    $historyList[] = $label . ': Yes';
                }
            }
        }
        
        if (!empty($instructionRows)) {
            $instructionRow = $instructionRows[0];
            $instructionMeta = $this->parseInstructionMetaPayload((string) ($instructionRow['comp_report'] ?? ''));
            $nabhMeta = is_array($instructionMeta['nabh'] ?? null) ? $instructionMeta['nabh'] : [];
            
            $historyLabels = [
                'drug_allergy_status' => 'Drug Allergy Status', 
                'drug_allergy_details' => 'Drug Allergy Details', 
                'adr_history' => 'ADR History', 
                'current_medications' => 'Current Medications', 
                'co_morbidities' => 'Comorbidities', 
                'hpi_note' => 'HPI Note'
            ];
            foreach ($historyLabels as $key => $label) {
                $val = trim((string) ($nabhMeta[$key] ?? ''));
                if ($val !== '') {
                    $historyList[] = $label . ': ' . $val;
                }
            }
        }
        $uiClinicalHistoryText = implode("\n", $historyList);

        $documentsList = $this->buildIpdPdfDocumentsList($ipdId, $dischargeIso);
        $locationInfo = $this->resolveIpdLocation($ipdId);

        $genderRaw = trim((string) ($patientRow['gender'] ?? ''));
        if ($genderRaw === '') {
            $genderRaw = trim((string) ($patientRow['xgender'] ?? ''));
        }

        $hospitalName = $this->getHospitalSettingValue('H_Name');
        if ($hospitalName === '' && defined('H_Name')) {
            $hospitalName = (string) constant('H_Name');
        }

        $uhidNo = trim((string) ($patientRow['uhid_no'] ?? $patientRow['uhid'] ?? $patientRow['patient_code'] ?? $patientRow['p_id'] ?? $patientId));
        $ipdNo = trim((string) ($ipdRow['ipd_no'] ?? $ipdRow['ipd_code'] ?? ('IPD-' . $ipdId)));

        $source = [
            'record_id' => (string) $ipdId,
            'bundle_identifier' => 'discharge-' . $ipdNo,
            'session_id' => (string) $ipdId,
            'visit_date' => $visitDate,
            'completed_at' => $dischargeIso,
            'department' => trim((string) ($ipdRow['dept_id'] ?? '')),
            'doctor_name' => trim((string) ($ipdRow['r_doc_name'] ?? '')),
            'organization' => [
                'id' => $hfrId,
                'name' => $hospitalName,
            ],
            'patient' => [
                'id' => (string) $patientId,
                'uhid' => $uhidNo,
                'name' => $patientName,
                'gender' => $this->normalizeFhirGender($genderRaw),
                'dob' => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
                'abha_id' => $abhaDigits,
                'abha_address' => $abhaAddress,
            ],
            'encounter' => [
                'id' => (string) $ipdId,
                'ipd_no' => $ipdNo,
                'class_code' => 'IMP',
                'start' => $admissionIso,
                'end' => $dischargeIso,
                'location_display' => (string) ($locationInfo['display'] ?? ''),
                'ward' => (string) ($locationInfo['ward'] ?? ''),
                'room' => (string) ($locationInfo['room'] ?? ''),
                'bed' => (string) ($locationInfo['bed'] ?? ''),
            ],
            'ui_complaints' => $chiefComplaintsList,
            'ui_complaint_narrative' => $complaintRemarkText,
            'ui_clinical_history' => $uiClinicalHistoryText,
            'ui_physical_exam' => $observationRows,
            'ui_investigations' => $investigationRows,
            'ui_surgeries' => $procedureRows,
            'ui_final_diagnosis' => $conditionRows,
            'ui_summary_investigation' => $uiSummaryInvestigation,
            'ui_course_treatment' => $courseRows, 
            'ui_discharge_medicine' => $medicationRows,
            'allergies' => $allergyRows,
            'documents' => [],
            'skip_pdf' => true,
            'template' => $this->resolveAbdmDischargeTemplate(),
        ];

        $factory = new FhirGeneratorFactory();
        $generatorOutput = $factory->discharge()->generate($source);
        $adapter = new GatewayPayloadAdapter();
        $gatewayPayload = $adapter->toGatewayPayload($generatorOutput, $source, $hfrId);

        $sourceUpdatedAt = date('Y-m-d H:i:s', strtotime($dischargeRaw !== '' ? $dischargeRaw : 'now'));
        $syncPayload = [
            'local_record_id' => 'ipd-discharge-' . $ipdId,
            'local_patient_id' => $patientId,
            'hi_type' => (string) ($gatewayPayload['hi_type'] ?? 'DischargeSummaryRecord'),
            'care_context_reference' => (string) ($gatewayPayload['care_context_reference'] ?? ''),
            'care_context_display' => (string) ($gatewayPayload['care_context_display'] ?? ''),
            'visit_date' => (string) ($gatewayPayload['visit_date'] ?? $visitDate),
            'department' => (string) ($gatewayPayload['department'] ?? ''),
            'doctor_name' => (string) ($gatewayPayload['doctor_name'] ?? ''),
            'consent_id' => '',
            'hfr_id' => $hfrId,
            'source_updated_at' => $sourceUpdatedAt,
            'patient_name' => (string) ($gatewayPayload['patient_name'] ?? $patientName),
            'mobile' => (string) ($patientRow['mphone1'] ?? ''),
            'gender' => (string) ($patientRow['gender'] ?? $patientRow['xgender'] ?? ''),
            'dob' => (string) ($patientRow['dob'] ?? ''),
            'abha_id' => (string) ($gatewayPayload['abha_id'] ?? $abhaDigits),
            'abha_address' => (string) ($gatewayPayload['abha_address'] ?? $abhaAddress),
            'fhir_bundle' => (array) ($gatewayPayload['fhir_bundle'] ?? []),
        ];

        $outbox = new AbdmSyncOutboxService();
        $outbox->enqueueRecordSync($syncPayload);
    }

    private function normalizeFhirGender(string $gender): string
    {
        $value = strtolower(trim($gender));
        if ($value === 'm' || $value === 'male') {
            return 'male';
        }
        if ($value === 'f' || $value === 'female') {
            return 'female';
        }
        if ($value === 'other') {
            return 'other';
        }
        return 'unknown';
    }

    /** Excludes blank/placeholder legacy values ('0', 'NA', 'N/A') used as "no data" markers. */
    private function isMeaningfulDischargeValue(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        return ! in_array(strtoupper($value), ['0', 'NA', 'N/A'], true);
    }

    private function toIsoDateTimeOrNow(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return date(DATE_ATOM);
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return date(DATE_ATOM);
        }

        return date(DATE_ATOM, $ts);
    }

    private function resolveIpdLocation(int $ipdId): array
    {
        $bedNo = '';
        $wardName = '';
        $roomName = '';

        if ($ipdId > 0 && $this->db) {
            try {
                if ($this->db->tableExists('bed_master')) {
                    $builder = $this->db->table('bed_master bm')
                        ->select('bm.bed_number');
                    if ($this->db->tableExists('ward_master')) {
                        $builder->select('wm.ward_name, wm.ward_type')
                            ->join('ward_master wm', 'wm.id = bm.ward_id', 'left');
                    }
                    $row = $builder->where('bm.current_ipd_id', $ipdId)
                        ->limit(1)
                        ->get()
                        ->getRowArray();
                    if (! empty($row)) {
                        $bedNo = trim((string) ($row['bed_number'] ?? ''));
                        $wardName = trim((string) ($row['ward_name'] ?? $row['ward_type'] ?? ''));
                    }
                }
            } catch (\Throwable $e) {
            }

            if ($bedNo === '') {
                try {
                    if ($this->db->tableExists('bed_assignment_history')) {
                        $builder = $this->db->table('bed_assignment_history bah');
                        if ($this->db->tableExists('bed_master')) {
                            $builder->select('bm.bed_number')
                                ->join('bed_master bm', 'bm.id = bah.bed_id', 'left');
                        }
                        if ($this->db->tableExists('ward_master')) {
                            $builder->select('wm.ward_name, wm.ward_type')
                                ->join('ward_master wm', 'wm.id = bah.ward_id', 'left');
                        }
                        $bahRow = $builder->where('bah.ipd_id', $ipdId)
                            ->orderBy('bah.id', 'DESC')
                            ->limit(1)
                            ->get()
                            ->getRowArray();
                        if (! empty($bahRow)) {
                            $bedNo = trim((string) ($bahRow['bed_number'] ?? ''));
                            $wardName = trim((string) ($bahRow['ward_name'] ?? $bahRow['ward_type'] ?? ''));
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            if ($bedNo === '') {
                try {
                    if ($this->db->tableExists('ward_beds')) {
                        $builder = $this->db->table('ward_beds wb')
                            ->select('wb.bed_number');
                        if ($this->db->tableExists('ward_master')) {
                            $builder->select('wm.ward_name')
                                ->join('ward_master wm', 'wm.id = wb.ward_id', 'left');
                        }
                        $wbRow = $builder->where('wb.ipd_id', $ipdId)
                            ->limit(1)
                            ->get()
                            ->getRowArray();
                        if (! empty($wbRow)) {
                            $bedNo = trim((string) ($wbRow['bed_number'] ?? ''));
                            $wardName = trim((string) ($wbRow['ward_name'] ?? ''));
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            if ($bedNo === '' || $roomName === '') {
                try {
                    if ($this->db->tableExists('ipd_master')) {
                        $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [];
                        if ($bedNo === '') {
                            $bedNo = trim((string) ($ipd['bed_no'] ?? $ipd['bed_id'] ?? ''));
                        }
                        $roomName = trim((string) ($ipd['room_no'] ?? $ipd['room_id'] ?? ''));
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        $parts = [];
        if ($wardName !== '') {
            $parts[] = 'Ward: ' . $wardName;
        }
        if ($roomName !== '') {
            $parts[] = 'Room: ' . $roomName;
        }
        if ($bedNo !== '') {
            $parts[] = 'Bed: ' . $bedNo;
        }

        $display = ! empty($parts) ? implode(', ', $parts) : ($bedNo !== '' ? ('Bed ' . $bedNo) : '');

        return [
            'ward' => $wardName,
            'room' => $roomName,
            'bed' => $bedNo,
            'display' => $display,
        ];
    }

    private function getNextVisitOptions(string $baseDate): array
    {
        $fallbackDays = [3, 4, 5, 7, 10, 15, 20, 30, 60];
        $fallbackDesc = [
            3 => '3 Days',
            4 => '4 Days',
            5 => '5 Days',
            7 => '1 Week',
            10 => '10 Days',
            15 => '15 Days',
            20 => '20 days',
            30 => '1 Month',
            60 => '2 Months',
        ];

        $baseTs = strtotime($baseDate);
        if ($baseTs === false) {
            $baseTs = strtotime(date('Y-m-d'));
        }

        $rows = [];
        if ($this->db->tableExists('opd_nextvisit')) {
            $fields = $this->db->getFieldNames('opd_nextvisit') ?? [];
            $descField = $this->resolveFirstField($fields, ['next_visit_desc', 'visit_desc', 'description', 'nextvisit_desc', 'name']);
            $daysField = $this->resolveFirstField($fields, ['no_of_days', 'days', 'day_count']);

            if ($descField !== null && $daysField !== null) {
                $builder = $this->db->table('opd_nextvisit')
                    ->select($descField . ' as visit_desc,' . $daysField . ' as no_of_days');

                if (in_array('status', $fields, true)) {
                    $builder->where('status', 1);
                }

                $rows = $builder
                    ->orderBy($daysField, 'ASC')
                    ->get()
                    ->getResultArray();
            }
        }

        $options = [];
        if (! empty($rows)) {
            foreach ($rows as $row) {
                $days = (int) ($row['no_of_days'] ?? 0);
                $desc = trim((string) ($row['visit_desc'] ?? ''));
                if ($days <= 0 || $desc === '') {
                    continue;
                }

                $visitDate = date('d-m-Y', strtotime('+' . $days . ' day', $baseTs));
                $value = $desc . ' (' . $visitDate . ')';

                $options[] = [
                    'desc' => $desc,
                    'days' => $days,
                    'date' => $visitDate,
                    'value' => $value,
                ];
            }
        }

        if (empty($options)) {
            foreach ($fallbackDays as $days) {
                $desc = (string) ($fallbackDesc[$days] ?? ($days . ' Days'));
                $visitDate = date('d-m-Y', strtotime('+' . $days . ' day', $baseTs));
                $value = $desc . ' (' . $visitDate . ')';
                $options[] = [
                    'desc' => $desc,
                    'days' => $days,
                    'date' => $visitDate,
                    'value' => $value,
                ];
            }
        }

        return $options;
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

    public function isDischargeHeaderBlank(string $header): bool
    {
        $stripped = trim(strip_tags(str_replace('&nbsp;', ' ', $header)));
        return $stripped === '';
    }

    public function buildDefaultDischargeHeader(array $hospital): string
    {
        $name = trim((string) ($hospital['H_Name'] ?? ''));
        $address = trim((string) ($hospital['hospital_address'] ?? ''));
        $phone = trim((string) ($hospital['H_phone_No'] ?? ''));
        $email = trim((string) ($hospital['H_Email'] ?? ''));

        return '<div class="hospital-header"><h2>' . htmlspecialchars($name) . '</h2><p>' . htmlspecialchars($address) . '</p><p>Phone: ' . htmlspecialchars($phone) . ' | Email: ' . htmlspecialchars($email) . '</p></div>';
    }
}
