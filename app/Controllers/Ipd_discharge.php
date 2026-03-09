<?php

namespace App\Controllers;

use App\Controllers\BaseController;
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
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS ipd_discharge_templates (
            id INT NOT NULL AUTO_INCREMENT,
            template_name VARCHAR(120) NOT NULL,
            template_html LONGTEXT NOT NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->query($sql);
    }

    private function defaultDischargeTemplateHtml(): string
    {
        return '<h3 style="margin:0 0 8px 0;">Discharge Summary</h3>'
            . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">'
            . '<tr>'
            . '<td><b>Patient</b>: {{PATIENT_NAME}}</td>'
            . '<td><b>UHID</b>: {{UHID}}</td>'
            . '<td><b>IPD</b>: {{IPD_CODE}}</td>'
            . '</tr>'
            . '<tr>'
            . '<td><b>Age/Gender</b>: {{AGE_GENDER}}</td>'
            . '<td><b>Admit Date</b>: {{ADMIT_DATE}}</td>'
            . '<td><b>Discharge Date</b>: {{DISCHARGE_DATE}}</td>'
            . '</tr>'
            . '</table>'
            . '<div>{{CONTENT}}</div>';
    }

    private function nabhDischargeTemplateHtml(): string
    {
        return '<h2 style="margin:0 0 10px 0;text-align:center;">DISCHARGE SUMMARY</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">'
            . '<tr>'
            . '<td><b>Patient Name</b>: {{PATIENT_NAME}}</td>'
            . '<td><b>UHID</b>: {{UHID}}</td>'
            . '<td><b>IPD No.</b>: {{IPD_CODE}}</td>'
            . '</tr>'
            . '<tr>'
            . '<td><b>Age/Gender</b>: {{AGE_GENDER}}</td>'
            . '<td><b>Date of Admission</b>: {{ADMIT_DATE}}</td>'
            . '<td><b>Date of Discharge</b>: {{DISCHARGE_DATE}}</td>'
            . '</tr>'
            . '<tr>'
            . '<td colspan="3"><b>Prepared On</b>: {{CURRENT_DATE}}</td>'
            . '</tr>'
            . '</table>'
            . '<div style="font-size:11px;color:#334155;margin-bottom:10px;">'
            . 'NABH guidance note: Ensure diagnosis, procedures, clinical course, condition at discharge, medication with dose/duration, follow-up advice, red-flag signs, and emergency contact are documented.'
            . '</div>'
            . '<div style="margin-bottom:10px;">{{CONTENT}}</div>'
            . '<h4 style="margin:12px 0 6px 0;">Counselling & Handover Confirmation</h4>'
            . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">'
            . '<tr><td style="width:32%;">Medication explained to patient/attendant</td><td style="width:8%;"></td><td style="width:60%;">Remarks:</td></tr>'
            . '<tr><td>Follow-up date and department explained</td><td></td><td>Next Visit: __________________</td></tr>'
            . '<tr><td>Red-flag symptoms explained</td><td></td><td>Emergency Contact: __________________</td></tr>'
            . '<tr><td>Diet and activity instructions explained</td><td></td><td></td></tr>'
            . '</table>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:20px;" border="1" cellpadding="10">'
            . '<tr>'
            . '<td style="width:33%;vertical-align:bottom;">____________________<br>Consultant Name/Signature</td>'
            . '<td style="width:33%;vertical-align:bottom;">____________________<br>Medical Officer Signature</td>'
            . '<td style="width:34%;vertical-align:bottom;">____________________<br>Patient/Attendant Signature & Date</td>'
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
        $this->ensureDefaultDischargeTemplateSeeded();
        if (! $this->db->tableExists('ipd_discharge_templates')) {
            return [];
        }

        return $this->db->table('ipd_discharge_templates')
            ->select('id,template_name,template_html,is_default,status')
            ->where('status', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
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
                if ((int) ($row['is_default'] ?? 0) === 1) {
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

        if (strpos($templateHtml, '{{CONTENT}}') === false) {
            $templateHtml .= "\n{{CONTENT}}";
        }

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

        $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
        $ageGender = trim($age . ' / ' . ((string) ($person->xgender ?? '')));

        $replacements = [
            '{{CONTENT}}' => $content,
            '{{PATIENT_NAME}}' => esc($patientName),
            '{{UHID}}' => esc($patientCode),
            '{{IPD_CODE}}' => esc((string) ($ipd->ipd_code ?? '')),
            '{{AGE_GENDER}}' => esc($ageGender),
            '{{ADMIT_DATE}}' => esc((string) ($ipd->str_register_date ?? '')),
            '{{DISCHARGE_DATE}}' => esc((string) ($ipd->str_discharge_date ?? '')),
            '{{CURRENT_DATE}}' => esc(date('d-m-Y')),
        ];

        $rendered = strtr($templateHtml, $replacements);

        return [
            'rendered_html' => $rendered,
            'templates' => $templates,
            'selected_template_id' => (int) ($selectedTemplate['id'] ?? 0),
            'selected_template_name' => (string) ($selectedTemplate['template_name'] ?? ''),
        ];
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
        return in_array($section, ['diagnosis_remark', 'course_remark'], true);
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
        $existing = $table
            ->where('doc_id', $docId)
            ->where('section_key', $section)
            ->where('template_name', $templateName)
            ->where('is_active', 1)
            ->get(1)
            ->getRowArray();

        $now = date('Y-m-d H:i:s');
        if (! empty($existing)) {
            $table->where('id', (int) ($existing['id'] ?? 0))->update([
                'template_text' => $templateText,
                'updated_at' => $now,
            ]);
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
        }

        return $this->response->setJSON([
            'update' => 1,
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

    private function normalizeRichText(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\s*\/\s*p\s*>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\s*p\b[^>]*>/i', '', $value) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\r\n?|\n/', "\n", $value) ?? $value;
        $value = preg_replace('/\n{3,}/', "\n\n", $value) ?? $value;

        return trim($value);
    }

    private function addListSection(array &$sections, string $title, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $html = '<h4 style="margin:16px 0 8px 0;">' . esc($title) . '</h4><ul style="margin:0 0 10px 20px;">';
        foreach ($rows as $row) {
            $report = trim((string) ($row['comp_report'] ?? ''));
            $remark = trim((string) ($row['comp_remark'] ?? ''));
            if ($report === '' && $remark === '') {
                continue;
            }

            $line = esc($report);
            if ($remark !== '') {
                $line .= ' <span style="color:#475569;">(' . esc($remark) . ')</span>';
            }

            $html .= '<li>' . $line . '</li>';
        }
        $html .= '</ul>';

        $sections[] = $html;
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

        $complaints = $this->byIpdRows('ipd_discharge_complaint', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $this->addListSection($sections, 'Presenting Complaints and Reason for Admission', $complaints);
        $complaintRemark = $this->firstRowByIpd('ipd_discharge_complaint_remark', $ipdId);
        $complaintRemarkText = $this->normalizeRichText((string) ($complaintRemark['comp_remark'] ?? ''));
        if ($complaintRemarkText !== '') {
            $sections[] = '<div>' . nl2br(esc($complaintRemarkText)) . '</div>';
        }

        $complaintMeta = $this->parseComplaintMetaPayload((string) ($complaintRemark['comp_report'] ?? ''));
        $painValue = (string) ($complaintMeta['pain_value'] ?? '');
        $painLabel = $this->painScaleLabel($painValue);
        if ($painLabel !== '') {
            $sections[] = '<div><b>Pain Measurement Scale:</b> ' . esc($painLabel) . ' (' . esc($painValue) . ')</div>';
        } elseif ((string) ($opdHistory['pain_label'] ?? '') !== '') {
            $opdPainLabel = (string) ($opdHistory['pain_label'] ?? '');
            $opdPainValue = (string) ($opdHistory['pain_value'] ?? '');
            $sections[] = '<div><b>Pain Measurement Scale:</b> ' . esc($opdPainLabel)
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
            $html = '<h4 style="margin:16px 0 8px 0;">General Examination on Admission</h4>'
                . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">';

            $col = 0;
            $perRow = 4;
            foreach ($generalRows as $row) {
                if ($col % $perRow === 0) {
                    $html .= '<tr>';
                }

                $label = (string) ($row['label'] ?? '');
                $value = trim((string) ($row['value'] ?? ''));
                $unit = trim((string) ($row['unit'] ?? ''));
                $html .= '<td><b>' . esc($label) . ':</b> ' . esc($value);
                if ($unit !== '') {
                    $html .= ' ' . esc($unit);
                }
                $html .= '</td>';

                if ($col % $perRow === $perRow - 1) {
                    $html .= '</tr>';
                }
                $col++;
            }

            while ($col % $perRow !== 0) {
                $html .= '<td>&nbsp;</td>';
                if ($col % $perRow === $perRow - 1) {
                    $html .= '</tr>';
                }
                $col++;
            }

            $html .= '</table>';
            $sections[] = $html;
        }

        $sysRows = $physicalExamRows['systemic'] ?? [];
        $sysHtml = '';
        foreach ($sysRows as $row) {
            $value = $this->normalizeRichText((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $sysHtml .= '<div style="margin-bottom:6px;">' . nl2br(esc($value)) . '</div>';
        }
        if ($sysHtml !== '') {
            $sections[] = '<h4 style="margin:16px 0 8px 0;">Other / Systemic Examinations</h4>' . $sysHtml;
        }

        $personalHistory = [];
        if ($patientId > 0 && $this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
            $historyMap = [
                'is_smoking' => 'Smoking',
                'is_alcohol' => 'Alcohol',
                'is_drug_abuse' => 'Drug abuse',
                'is_tobacoo' => 'Tobacco',
                'is_hypertesion' => 'Hypertension',
                'is_niddm' => 'Type 2 diabetes mellitus (DM)',
                'is_hbsag' => 'HBsAg',
                'is_hcv' => 'HCV',
                'is_hiv_I_II' => 'HIV I & II',
                'Others' => 'Others',
            ];

            foreach ($historyMap as $field => $label) {
                if ((int) ($patientRow[$field] ?? 0) === 1) {
                    $personalHistory[] = $label;
                }
            }
        }

        if (! empty($personalHistory)) {
            $sections[] = '<h4 style="margin:16px 0 8px 0;">Personal History</h4><div>' . esc(implode(', ', $personalHistory)) . '</div>';
        }

        $allergyLines = [];
        if ((string) ($opdHistory['drug_allergy_status'] ?? '') !== '') {
            $allergyLines[] = '<div><b>Drug Allergy Status:</b> ' . esc((string) ($opdHistory['drug_allergy_status'] ?? '')) . '</div>';
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
            $sections[] = '<h4 style="margin:16px 0 8px 0;">Drug Allergy / ADR</h4>' . implode('', $allergyLines);
        }

        $coMorbText = trim((string) ($opdHistory['co_morbidities'] ?? ''));
        if ($coMorbText !== '') {
            $sections[] = '<h4 style="margin:16px 0 8px 0;">Co-Morbidities</h4><div>' . esc($coMorbText) . '</div>';
        }

        $admitDate = $this->normalizeDateValue((string) ($ipd->register_date ?? '')) ?? '';
        $dischargeDate = $this->normalizeDateValue((string) ($ipd->discharge_date ?? '')) ?? date('Y-m-d');
        $savedClinicalDates = $this->getSavedClinicalLabDates($ipdId);
        $clinicalLabRows = $this->getClinicalInvestigationLabRows($patientId, $admitDate, $dischargeDate, $savedClinicalDates);

        $effectiveClinicalDates = $savedClinicalDates;
        if (empty($effectiveClinicalDates)) {
            foreach ($clinicalLabRows as $row) {
                $dt = $this->normalizeDateValue((string) ($row['inv_date'] ?? ''));
                if ($dt !== null) {
                    $effectiveClinicalDates[$dt] = $dt;
                }
            }
            $effectiveClinicalDates = array_values($effectiveClinicalDates);
        }

        $pathologyMatrix = $this->getClinicalPathologyMatrixRows($patientId, $effectiveClinicalDates);

        $selectedLabRows = [];
        foreach ($clinicalLabRows as $row) {
            if (! empty($savedClinicalDates)) {
                if (! empty($row['checked'])) {
                    $selectedLabRows[] = $row;
                }
                continue;
            }

            // No saved selection yet: include all available pathology dates as fallback.
            $selectedLabRows[] = $row;
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

        if (! empty($pathologyMatrix['rows']) || ! empty($selectedLabRows) || ! empty($selectedNonPathRows) || trim((string) ($otherExamParsed['text'] ?? '')) !== '') {
            $html = '<h4 style="margin:16px 0 8px 0;">Clinical Investigation Reports</h4>';

            if (! empty($pathologyMatrix['rows'])) {
                $html .= '<div><b>In-Hospital Lab:</b></div>'
                    . '<table style="width:100%;border-collapse:collapse;margin:4px 0 8px 0;" border="1" cellpadding="6">'
                    . '<tr>'
                    . '<th style="text-align:left;">Test</th>'
                    . '<th style="text-align:left;">Fixed Normals</th>';

                foreach (($pathologyMatrix['dates'] ?? []) as $dt) {
                    $html .= '<th style="text-align:left;">' . esc((string) ($pathologyMatrix['date_labels'][$dt] ?? $dt)) . '</th>';
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
                $html .= '<div><b>In-Hospital Lab:</b></div><ul style="margin:4px 0 8px 20px;">';
                foreach ($selectedLabRows as $row) {
                    $html .= '<li>[' . esc((string) ($row['inv_date_label'] ?? '')) . '] ' . esc((string) ($row['test_list'] ?? '')) . '</li>';
                }
                $html .= '</ul>';
            }

            if (! empty($selectedNonPathRows)) {
                $html .= '<div><b>X-Ray / ECG / Sonography / CT / MRI:</b></div><ul style="margin:4px 0 8px 20px;">';
                foreach ($selectedNonPathRows as $row) {
                    $html .= '<li>[' . esc((string) ($row['report_date_label'] ?? '')) . '] '
                        . esc((string) ($row['modality'] ?? ''))
                        . ' - ' . esc((string) ($row['report_name'] ?? ''));

                    $impression = trim((string) ($row['impression'] ?? ''));
                    if ($impression !== '') {
                        $html .= '<br><span style="color:#475569;">Impression: ' . nl2br(esc($impression)) . '</span>';
                    }

                    $html .= '</li>';
                }
                $html .= '</ul>';
            }

            $otherExamText = $this->normalizeRichText((string) ($otherExamParsed['text'] ?? ''));
            if ($otherExamText !== '') {
                $html .= '<div><b>Other Examinations / Provisional Diagnosis:</b><br>' . nl2br(esc($otherExamText)) . '</div>';
            }

            $sections[] = $html;
        }

        $diagnosis = $this->byIpdRows('ipd_discharge_diagnosis', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $this->addListSection($sections, 'Final Diagnosis', $diagnosis);
        $diagnosisRemark = $this->firstRowByIpd('ipd_discharge_diagnosis_remark', $ipdId);
        $diagnosisRemarkText = $this->normalizeRichText((string) ($diagnosisRemark['comp_remark'] ?? ''));
        if ($diagnosisRemarkText !== '') {
            $sections[] = '<div>' . nl2br(esc($diagnosisRemarkText)) . '</div>';
        }

        $inhosRow = $this->firstRowByIpd('ipd_discharge_investigtions_inhos', $ipdId);
        $inhosRemark = $this->normalizeRichText((string) ($inhosRow['comp_remark'] ?? ''));
        if ($inhosRemark !== '') {
            $sections[] = '<h4 style="margin:16px 0 8px 0;">Summary of key investigations during Hospitalization</h4><div>'
                . nl2br(esc($inhosRemark))
                . '</div>';
        }

        $course = $this->byIpdRows('ipd_discharge_course', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $this->addListSection($sections, 'Course in the Hospital', $course);
        $courseRemark = $this->firstRowByIpd('ipd_discharge_course_remark', $ipdId);
        $courseRemarkText = $this->normalizeRichText((string) ($courseRemark['comp_remark'] ?? ''));
        if ($courseRemarkText !== '') {
            $sections[] = '<div>' . nl2br(esc($courseRemarkText)) . '</div>';
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
            $html = '<h4 style="margin:16px 0 8px 0;">Examination on Discharge</h4>'
                . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">';

            $col = 0;
            $perRow = 4;
            foreach ($dischargeExamRows as $row) {
                if ($col % $perRow === 0) {
                    $html .= '<tr>';
                }

                $label = (string) ($row['label'] ?? '');
                $value = trim((string) ($row['value'] ?? ''));
                $unit = trim((string) ($row['unit'] ?? ''));
                $html .= '<td><b>' . esc($label) . ':</b> ' . esc($value);
                if ($unit !== '') {
                    $html .= ' ' . esc($unit);
                }
                $html .= '</td>';

                if ($col % $perRow === $perRow - 1) {
                    $html .= '</tr>';
                }
                $col++;
            }

            while ($col % $perRow !== 0) {
                $html .= '<td>&nbsp;</td>';
                if ($col % $perRow === $perRow - 1) {
                    $html .= '</tr>';
                }
                $col++;
            }

            $html .= '</table>';
            $sections[] = $html;
        }

        $surgeryRows = $this->byIpdRows('ipd_discharge_surgery', ['surgery_name', 'surgery_date'], 'id ASC', $ipdId);
        if (! empty($surgeryRows)) {
            $html = '<h4 style="margin:16px 0 8px 0;">Surgery</h4><ul style="margin:0 0 10px 20px;">';
            foreach ($surgeryRows as $row) {
                $name = trim((string) ($row['surgery_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $dateText = $this->safeDate((string) ($row['surgery_date'] ?? ''));
                $html .= '<li>' . esc($name);
                if ($dateText !== '') {
                    $html .= ' <span style="color:#475569;">(' . esc($dateText) . ')</span>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
            $sections[] = $html;
        }

        $procedureRows = $this->byIpdRows('ipd_discharge_procedure', ['procedure_name', 'procedure_date'], 'id ASC', $ipdId);
        if (! empty($procedureRows)) {
            $html = '<h4 style="margin:16px 0 8px 0;">Procedure</h4><ul style="margin:0 0 10px 20px;">';
            foreach ($procedureRows as $row) {
                $name = trim((string) ($row['procedure_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $dateText = $this->safeDate((string) ($row['procedure_date'] ?? ''));
                $html .= '<li>' . esc($name);
                if ($dateText !== '') {
                    $html .= ' <span style="color:#475569;">(' . esc($dateText) . ')</span>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
            $sections[] = $html;
        }

        $drugRows = $this->byIpdRows('ipd_discharge_drug', ['drug_name', 'drug_dose', 'drug_day'], 'id ASC', $ipdId);
        if (! empty($drugRows)) {
            $html = '<h4 style="margin:16px 0 8px 0;">Discharge Medications</h4><ol style="margin:0 0 10px 20px;">';
            foreach ($drugRows as $row) {
                $drugName = trim((string) ($row['drug_name'] ?? ''));
                if ($drugName === '') {
                    continue;
                }

                $line = esc($drugName);
                $dose = trim((string) ($row['drug_dose'] ?? ''));
                $days = trim((string) ($row['drug_day'] ?? ''));

                if ($dose !== '') {
                    $line .= ' <span style="color:#475569;">' . esc($dose) . '</span>';
                }
                if ($days !== '') {
                    $line .= ' <span style="color:#475569;">[' . esc($days) . ' days]</span>';
                }

                $html .= '<li>' . $line . '</li>';
            }
            $html .= '</ol>';
            $sections[] = $html;
        } else {
            $medRows = $this->byIpdRows('ipd_discharge_prescrption_prescribed', ['med_name', 'med_type', 'qty', 'no_of_days', 'remark'], 'id ASC', $ipdId);
            if (! empty($medRows)) {
                $html = '<h4 style="margin:16px 0 8px 0;">Discharge Medications</h4>'
                    . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">'
                    . '<tr><th style="width:40px;">#</th><th>Medicine</th><th style="width:90px;">Qty</th><th style="width:100px;">Days</th><th>Notes</th></tr>';
                $sr = 1;
                foreach ($medRows as $row) {
                    $medName = trim((string) ($row['med_name'] ?? ''));
                    if ($medName === '') {
                        continue;
                    }
                    $medType = trim((string) ($row['med_type'] ?? ''));
                    $label = trim($medType . ' ' . $medName);
                    $html .= '<tr>'
                        . '<td>' . $sr . '</td>'
                        . '<td>' . esc($label) . '</td>'
                        . '<td>' . esc((string) ($row['qty'] ?? '')) . '</td>'
                        . '<td>' . esc((string) ($row['no_of_days'] ?? '')) . '</td>'
                        . '<td>' . esc((string) ($row['remark'] ?? '')) . '</td>'
                        . '</tr>';
                    $sr++;
                }
                $html .= '</table>';
                $sections[] = $html;
            }
        }

        $instructions = $this->byIpdRows('ipd_discharge_instructions', ['comp_report', 'comp_remark', 'review_after', 'footer_text'], 'id DESC', $ipdId);
        if (! empty($instructions)) {
            $first = $instructions[0];
            $html = '<h4 style="margin:16px 0 8px 0;">Discharge Advice/Instructions/Summary</h4>';

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
                $html .= '<div style="margin-bottom:8px;"><strong>Dietary Advice:</strong></div>';
                $html .= '<ol style="margin:0 0 10px 20px;">';
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
                $html .= '<div style="margin-bottom:8px;"><strong>Other Advice:</strong> ' . nl2br(esc($otherText)) . '</div>';
            }

            $remark = trim((string) ($first['comp_remark'] ?? ''));
            if ($remark !== '') {
                $html .= '<div>' . nl2br(esc($remark)) . '</div>';
            }

            $reviewAfter = trim((string) ($first['review_after'] ?? ''));
            if ($reviewAfter !== '') {
                $html .= '<div style="margin-top:6px;">Review after ' . esc($reviewAfter) . ' days / as and when required.</div>';
            }

            $footerText = trim((string) ($first['footer_text'] ?? ''));
            if ($footerText !== '') {
                $html .= '<div style="margin-top:6px;">' . nl2br(esc($footerText)) . '</div>';
            }

            $sections[] = $html;
        }

        $sections[] = '<table style="width:100%;border-collapse:collapse;margin-top:24px;" border="1" cellpadding="10">'
            . '<tr>'
            . '<td style="width:33%;vertical-align:bottom;">____________________<br>Signature of Consultant</td>'
            . '<td style="width:33%;vertical-align:bottom;">____________________<br>Signature of Medical Officer</td>'
            . '<td style="width:34%;vertical-align:bottom;">____________________<br>Signature of Receiver / Date</td>'
            . '</tr>'
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

        $html = '<h4 style="margin:16px 0 8px 0;">Nursing Trend Summary (Last 24 Hours)</h4>';
        $html .= '<ul style="margin:0 0 10px 20px;">';
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
            $html .= '<li>Key nursing treatments:</li><li style="list-style:none;">';
            $html .= '<ul style="margin:4px 0 0 18px;">';
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
        $type = $this->normalizeSurgeryTermType((string) $this->request->getGet('type'));
        $q = trim((string) $this->request->getGet('q'));

        if (! $this->ensureDischargeSurgeryMasterTable()) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('ipd_discharge_surgery_master')
            ->select('id,term_type,term_name,term_code,icd_code')
            ->where('is_active', 1)
            ->where('term_type', $type);

        if ($q !== '') {
            $builder->groupStart()
                ->like('term_name', $q)
                ->orLike('term_code', $q)
                ->orLike('icd_code', $q)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy('term_name', 'ASC')
            ->limit(25)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['rows' => $rows]);
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

    public function diagnosis_icd_lookup()
    {
        $q = trim((string) $this->request->getGet('q'));
        $rows = [];

        if ($q !== '') {
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
                        'name' => (string) ($row['diagnosis_text'] ?? ''),
                        'icd_code' => (string) ($row['icd_code'] ?? ''),
                        'source' => 'icd_master',
                    ];
                }
            }

            if (count($rows) < 25 && $this->db->tableExists('complaints_master')) {
                $fallbackRows = $this->db->table('complaints_master')
                    ->select('Code as id, Name as name, name_hinglish')
                    ->groupStart()
                    ->like('Name', $q)
                    ->orLike('name_hinglish', $q)
                    ->groupEnd()
                    ->orderBy('Name', 'ASC')
                    ->limit(25 - count($rows))
                    ->get()
                    ->getResultArray();

                foreach ($fallbackRows as $row) {
                    $rows[] = [
                        'id' => (int) ($row['id'] ?? 0),
                        'name' => (string) ($row['name'] ?? ''),
                        'icd_code' => '',
                        'source' => 'complaints_master',
                        'name_hinglish' => (string) ($row['name_hinglish'] ?? ''),
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
                ->orderBy('id', 'ASC');

            if ($this->db->fieldExists('col_unit', 'ipd_discharge_general_exam_col')) {
                $generalBuilder->select('col_unit');
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
            ->select("DATE_FORMAT(m.inv_date,'%d-%m-%Y') AS inv_date_label", false)
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

    public function ipd_select(int $ipdId, int $reCreate = 0)
    {
        $permission = $this->requireAnyPermission([
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

        $ipdMasterRow = $this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [];
        $patientId = (int) ($ipdMasterRow['p_id'] ?? ($panelData['person_info']->id ?? 0));

        $notice = '';
        $noticeType = 'success';
        $userLabel = substr($this->currentUserLabel(), 0, 50);

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = (string) ($this->request->getPost('action') ?? 'save_main');
            $savedAny = false;

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
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_procedure', ['ipd_id', 'procedure_name'])) {
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
                    $notice = $name === ''
                        ? 'Enter procedure name before adding.'
                        : 'Procedure table/columns are missing in database.';
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
                $diagnosisRemarkText = trim((string) ($this->request->getPost('diagnosis_remark') ?? ''));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_diagnosis', ['ipd_id', 'comp_report'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'comp_code' => 0,
                        'comp_report' => $name,
                        'comp_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
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
                $name = trim((string) ($this->request->getPost('new_drug_name') ?? ''));
                $type = trim((string) ($this->request->getPost('new_drug_type') ?? ''));
                $dose = trim((string) ($this->request->getPost('new_drug_dose') ?? ''));
                $when = trim((string) ($this->request->getPost('new_drug_when') ?? ''));
                $freq = trim((string) ($this->request->getPost('new_drug_freq') ?? ''));
                $day = trim((string) ($this->request->getPost('new_drug_day') ?? ''));
                $qty = trim((string) ($this->request->getPost('new_drug_qty') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_drug_remark') ?? ''));

                $legacyDrugTable = $this->findFirstExistingTable(['ipd_discharge_prescrption_prescribed', 'ipd_discharge_prescription_prescribed']);
                if ($name !== '' && $legacyDrugTable !== null && $this->tableHasColumns($legacyDrugTable, ['ipd_id', 'med_name'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'med_id' => 0,
                        'med_name' => $name,
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

                    $savedAny = ! empty($allowed)
                        ? (bool) $this->db->table($legacyDrugTable)->insert($allowed)
                        : false;
                    $notice = $savedAny ? 'Medicine row added.' : 'Unable to add medicine row.';
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
                    $savedAny = (bool) $this->db->table('ipd_discharge_drug')->insert($insert);
                    $notice = $savedAny ? 'Drug row added.' : 'Unable to add drug row.';
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
                                    'med_type' => trim((string) ($row['med_type'] ?? '')),
                                    'dosage' => trim((string) ($row['dosage'] ?? '')),
                                    'dosage_when' => trim((string) ($row['dosage_when'] ?? '')),
                                    'dosage_freq' => trim((string) ($row['dosage_freq'] ?? '')),
                                    'dosage_where' => trim((string) ($row['dosage_where'] ?? '')),
                                    'no_of_days' => trim((string) ($row['no_of_days'] ?? '')),
                                    'qty' => trim((string) ($row['qty'] ?? '')),
                                    'remark' => trim((string) ($row['remark'] ?? '')),
                                    'genericname' => trim((string) ($row['genericname'] ?? '')),
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
                if ($removeId > 0) {
                    $deleted = false;

                    if ($removeSource === 'legacy') {
                        $legacyDrugTable = $this->findFirstExistingTable(['ipd_discharge_prescrption_prescribed', 'ipd_discharge_prescription_prescribed']);
                        if ($legacyDrugTable !== null && $this->tableHasColumns($legacyDrugTable, ['id', 'ipd_id'])) {
                            $deleted = (bool) $this->db->table($legacyDrugTable)
                                ->where('id', $removeId)
                                ->where('ipd_id', $ipdId)
                                ->delete();
                        }
                    }

                    if (! $deleted && $this->tableHasColumns('ipd_discharge_drug', ['id', 'ipd_id'])) {
                        $deleted = (bool) $this->db->table('ipd_discharge_drug')
                            ->where('id', $removeId)
                            ->where('ipd_id', $ipdId)
                            ->delete();
                    }

                    $savedAny = $deleted;
                    $notice = $savedAny ? 'Drug row removed.' : 'Unable to remove drug row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Select a valid drug row to remove.';
                    $noticeType = 'warning';
                }
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
                        $legacyData['food_text'] = $instructionRemark;
                    }

                    $savedAny = $this->upsertByIpd('ipd_discharge_drug_food_interaction', $ipdId, $legacyData) || $savedAny;
                }

                // Examination on Admission (General Examination values).
                if ($this->tableHasColumns('ipd_discharge_general_exam_col', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_b', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $generalCols = $this->db->table('ipd_discharge_general_exam_col')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

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
                $normalizedClinicalDates = [];
                $normalizedNonPathIds = [];
                if (is_array($postedClinicalDates)) {
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

                if (is_array($postedNonPathIds)) {
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

            $ajaxMode = strtolower(trim((string) ($this->request->getPost('ajax_mode') ?? '')));
            if ($ajaxMode === 'json') {
                return $this->response->setJSON([
                    'update' => $savedAny ? 1 : 0,
                    'error_text' => $notice,
                    'notice_type' => $noticeType,
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
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
        $legacyDrugRows = $this->byIpdRows('ipd_discharge_prescrption_prescribed', ['id', 'med_name', 'med_type', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days', 'qty', 'remark'], 'id ASC', $ipdId);
        if (empty($legacyDrugRows)) {
            $legacyDrugRows = $this->byIpdRows('ipd_discharge_prescription_prescribed', ['id', 'med_name', 'med_type', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days', 'qty', 'remark'], 'id ASC', $ipdId);
        }
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
        ]);
    }

    public function preview_discharge_report(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
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

        return view('billing/ipd/discharge_preview', [
            'ipd_id' => $ipdId,
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $panelData['person_info'] ?? null,
            'content' => $content,
            'rendered_content' => (string) ($templatePack['rendered_html'] ?? $content),
            'template_rows' => $templatePack['templates'] ?? [],
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

        $content = $this->getDischargeContent($ipdId);
        if (trim(strip_tags($content)) === '') {
            $content = $this->buildAutoDischargeContent($ipdId, $panelData);
            if (trim(strip_tags($content)) !== '') {
                $this->saveDischargeContent($ipdId, $content);
            }
        }

        $requestedTemplateId = (int) ($this->request->getGet('tpl') ?? 0);
        $templatePack = $this->applyDischargeTemplate($content, $panelData, $requestedTemplateId > 0 ? $requestedTemplateId : null);

        $withHeader = $printType !== 0;
        $renderedHtml = (string) ($templatePack['rendered_html'] ?? $content);
        $templateName = (string) ($templatePack['selected_template_name'] ?? 'Discharge Template');

        try {
            $patient = $panelData['person_info'] ?? null;
            $ipd = $panelData['ipd_info'] ?? null;

            $patientName = trim((string) ($patient->p_fname ?? 'Patient'));
            $ipdCode = trim((string) ($ipd->ipd_code ?? $ipdId));
            $fileName = 'discharge_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $ipdCode !== '' ? $ipdCode : (string) $ipdId) . '.pdf';

            $pdfHtml = $this->buildDischargePdfHtml($panelData, $renderedHtml, $withHeader, $templateName);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => $withHeader ? 28 : 12,
                'margin_bottom' => 12,
                'margin_header' => 8,
                'margin_footer' => 8,
                'default_font' => 'freeserif',
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            $mpdf->SetTitle('Discharge Summary - ' . ($patientName !== '' ? $patientName : ('IPD ' . $ipdId)));
            $mpdf->SetAuthor('Atria HMS');

            if ($withHeader) {
                $headerHtml = '<div style="font-family:freeserif,serif;font-size:11pt;border-bottom:1px solid #d1d5db;padding-bottom:6px;">'
                    . '<div style="font-size:14pt;font-weight:700;">Discharge Summary</div>'
                    . '<div style="font-size:9pt;color:#374151;">IPD: ' . esc($ipdCode) . ' | Template: ' . esc($templateName) . '</div>'
                    . '</div>';
                $mpdf->SetHTMLHeader($headerHtml);
            }

            $mpdf->SetHTMLFooter('<div style="font-family:freeserif,serif;font-size:9pt;color:#6b7280;text-align:right;">Page {PAGENO}/{nbpg}</div>');
            $mpdf->WriteHTML($pdfHtml);

            $pdfBinary = $mpdf->Output($fileName, Destination::STRING_RETURN);
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->setBody($pdfBinary);
        } catch (\Throwable $e) {
            log_message('error', 'PDF generation failed for IPD {ipd}: {msg}', [
                'ipd' => $ipdId,
                'msg' => $e->getMessage(),
            ]);
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

    private function buildDischargePdfHtml(array $panelData, string $renderedContent, bool $withHeader, string $templateName): string
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
        $ipdCode = trim((string) ($ipd->ipd_code ?? ''));
        $gender = trim((string) ($person->xgender ?? ''));
        $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
        $admitDate = trim((string) ($ipd->str_register_date ?? ''));
        $dischargeDate = trim((string) ($ipd->str_discharge_date ?? ''));

        $headerBlock = '';
        if ($withHeader) {
            $headerBlock = '<div class="pdf-header">'
                . '<div class="pdf-header-title">Discharge Summary</div>'
                . '<div class="pdf-header-sub">Template: ' . esc($templateName) . '</div>'
                . '</div>';
        }

        return '<!doctype html>'
            . '<html><head><meta charset="utf-8">'
            . '<style>'
            . 'body{font-family:freeserif,serif;font-size:11pt;color:#111827;line-height:1.4;}'
            . '.pdf-header{margin-bottom:10px;border-bottom:1px solid #d1d5db;padding-bottom:6px;}'
            . '.pdf-header-title{font-size:18pt;font-weight:700;}'
            . '.pdf-header-sub{font-size:9pt;color:#4b5563;}'
            . '.meta{border:1px solid #cbd5e1;padding:8px;margin-bottom:10px;}'
            . '.meta-table{width:100%;border-collapse:collapse;font-size:10pt;}'
            . '.meta-table td{padding:2px 6px;border-right:1px solid #e5e7eb;vertical-align:top;}'
            . '.meta-table td:last-child{border-right:none;}'
            . '.content{border:1px solid #d1d5db;padding:10px;}'
            . '.content h2,.content h3,.content h4{margin:12px 0 6px 0;color:#0f172a;}'
            . '.content table{width:100%;border-collapse:collapse;margin:6px 0 10px 0;font-size:10pt;}'
            . '.content th,.content td{border:1px solid #d1d5db;padding:5px;vertical-align:top;}'
            . '.content ul,.content ol{margin:4px 0 10px 18px;padding:0;}'
            . '</style></head><body>'
            . $headerBlock
            . '<div class="meta"><table class="meta-table"><tr>'
            . '<td><strong>Patient:</strong> ' . esc($patientName) . '</td>'
            . '<td><strong>UHID:</strong> ' . esc($patientCode) . '</td>'
            . '<td><strong>IPD:</strong> ' . esc($ipdCode) . '</td>'
            . '</tr><tr>'
            . '<td><strong>Age/Gender:</strong> ' . esc(trim($age . ' / ' . $gender)) . '</td>'
            . '<td><strong>Admit Date:</strong> ' . esc($admitDate) . '</td>'
            . '<td><strong>Discharge Date:</strong> ' . esc($dischargeDate) . '</td>'
            . '</tr></table></div>'
            . '<div class="content">' . $renderedContent . '</div>'
            . '</body></html>';
    }

    public function show_file3(int $ipdId)
    {
        return $this->show_discharge($ipdId, 3);
    }
}
