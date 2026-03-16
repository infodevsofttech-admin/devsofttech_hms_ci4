<?php

namespace App\Controllers;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

class DoctorDocument extends BaseController
{
    private array $doctorWorkspacePerms = [
        'doctor_work.access',
        'doctor_work.template_workspace.access',
        'template.pathology',
    ];

    public function __construct()
    {
        $this->db = db_connect();
        helper(['form']);
    }

    private function ensureAccess()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        foreach ($this->doctorWorkspacePerms as $perm) {
            if ($user->can($perm)) {
                return null;
            }
        }

        return $this->response->setStatusCode(403)->setBody('Forbidden');
    }

    private function ensureDocumentTables(): bool
    {
        return $this->db->tableExists('doc_format_master')
            && $this->db->tableExists('doc_format_sub')
            && $this->db->tableExists('patient_doc')
            && $this->db->tableExists('patient_doc_raw');
    }

    private function ensureDocumentPrintTemplateTable(): bool
    {
        if (! $this->db->tableExists('doc_print_templates')) {
            return false;
        }

        $columnSql = [
            'print_on_type' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `print_on_type` TINYINT(1) NOT NULL DEFAULT 1 AFTER `page_size`',
            'page_margin_top_cm' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `page_margin_top_cm` DECIMAL(5,2) NOT NULL DEFAULT 6.10 AFTER `print_on_type`',
            'page_margin_bottom_cm' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `page_margin_bottom_cm` DECIMAL(5,2) NOT NULL DEFAULT 2.50 AFTER `page_margin_top_cm`',
            'page_margin_left_cm' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `page_margin_left_cm` DECIMAL(5,2) NOT NULL DEFAULT 0.70 AFTER `page_margin_bottom_cm`',
            'page_margin_right_cm' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `page_margin_right_cm` DECIMAL(5,2) NOT NULL DEFAULT 0.70 AFTER `page_margin_left_cm`',
            'margin_header_cm' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `margin_header_cm` DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER `page_margin_right_cm`',
            'margin_footer_cm' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `margin_footer_cm` DECIMAL(5,2) NOT NULL DEFAULT 1.50 AFTER `margin_header_cm`',
            'header_html' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `header_html` LONGTEXT NULL AFTER `margin_footer_cm`',
            'footer_html' => 'ALTER TABLE `doc_print_templates` ADD COLUMN `footer_html` LONGTEXT NULL AFTER `header_html`',
        ];

        foreach ($columnSql as $column => $sql) {
            if ($this->db->fieldExists($column, 'doc_print_templates')) {
                continue;
            }
            try {
                $this->db->query($sql);
            } catch (\Throwable $e) {
                log_message('error', 'Unable to add {column} column in doc_print_templates: {message}', [
                    'column' => $column,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }

    private function getDocumentPrintTemplates(): array
    {
        if (! $this->ensureDocumentPrintTemplateTable()) {
            return [];
        }

        return $this->db->table('doc_print_templates')
            ->select('id, template_name, is_default, print_on_type')
            ->where('status', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('template_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function ensurePrintTemplateColumns(): void
    {
        if (! $this->db->tableExists('doc_format_master')) {
            return;
        }

        $columnSql = [
            'default_print_type' => 'ALTER TABLE `doc_format_master` ADD COLUMN `default_print_type` TINYINT(1) NOT NULL DEFAULT 0 AFTER `doc_raw_format`',
            'print_top_margin' => 'ALTER TABLE `doc_format_master` ADD COLUMN `print_top_margin` DECIMAL(5,2) NOT NULL DEFAULT 6.10 AFTER `default_print_type`',
            'print_bottom_margin' => 'ALTER TABLE `doc_format_master` ADD COLUMN `print_bottom_margin` DECIMAL(5,2) NOT NULL DEFAULT 2.50 AFTER `print_top_margin`',
            'print_left_margin' => 'ALTER TABLE `doc_format_master` ADD COLUMN `print_left_margin` DECIMAL(5,2) NOT NULL DEFAULT 0.70 AFTER `print_bottom_margin`',
            'print_right_margin' => 'ALTER TABLE `doc_format_master` ADD COLUMN `print_right_margin` DECIMAL(5,2) NOT NULL DEFAULT 0.70 AFTER `print_left_margin`',
            'print_header_margin' => 'ALTER TABLE `doc_format_master` ADD COLUMN `print_header_margin` DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER `print_right_margin`',
            'print_footer_margin' => 'ALTER TABLE `doc_format_master` ADD COLUMN `print_footer_margin` DECIMAL(5,2) NOT NULL DEFAULT 1.50 AFTER `print_header_margin`',
        ];

        foreach ($columnSql as $column => $sql) {
            if ($this->db->fieldExists($column, 'doc_format_master')) {
                continue;
            }
            try {
                $this->db->query($sql);
            } catch (\Throwable $e) {
                log_message('error', 'Unable to add {column} column: {message}', [
                    'column' => $column,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function normalizeMarginValue($value, float $default): float
    {
        $number = (float) $value;
        if ($number <= 0 || $number > 20) {
            return $default;
        }

        return round($number, 2);
    }

    public function workspace()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        return view('doctor_document/workspace');
    }

    public function open_by_key()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $rawKey = trim((string) $this->request->getGet('patient_key'));
        if ($rawKey === '') {
            return $this->response->setJSON([
                'status' => 0,
                'message' => 'Enter patient id',
            ]);
        }

        $key = strtoupper(preg_replace('/\s+/', '', $rawKey));
        $digits = preg_replace('/\D+/', '', $key);

        $patient = null;

        // Priority 1: exact record by numeric patient_master.id
        if (ctype_digit($key)) {
            $patient = $this->db->table('patient_master')
                ->select('id,p_code,old_uhid,p_fname')
                ->where('id', (int) $key)
                ->get(1)
                ->getRowArray();
        }

        // Priority 2: exact record by UHID / old UHID
        if (! is_array($patient)) {
            $patient = $this->db->table('patient_master')
                ->select('id,p_code,old_uhid,p_fname')
                ->groupStart()
                ->where('UPPER(TRIM(p_code))', $key)
                ->orWhere('UPPER(TRIM(old_uhid))', $key)
                ->groupEnd()
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        if (is_array($patient)) {
            return $this->response->setJSON([
                'status' => 1,
                'patient_id' => (int) ($patient['id'] ?? 0),
            ]);
        }

        // Priority 3: allow last digits search; open only when a single record is found.
        if ($digits !== '') {
            $builder = $this->db->table('patient_master')
                ->select('id,p_code,old_uhid,p_fname')
                ->groupStart()
                ->like('p_code', $digits, 'before')
                ->orLike('old_uhid', $digits, 'before')
                ->groupEnd();

            $matches = $builder
                ->orderBy('id', 'DESC')
                ->limit(6)
                ->get()
                ->getResultArray();

            $count = count($matches);
            if ($count === 1) {
                return $this->response->setJSON([
                    'status' => 1,
                    'patient_id' => (int) ($matches[0]['id'] ?? 0),
                ]);
            }

            if ($count > 1) {
                return $this->response->setJSON([
                    'status' => 0,
                    'message' => 'Multiple patients matched. Please enter complete UHID/Patient ID.',
                ]);
            }
        }

        return $this->response->setJSON([
            'status' => 0,
            'message' => 'Patient not found',
        ]);
    }

    public function doc_list()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        if (! $this->ensureDocumentTables()) {
            return $this->response->setStatusCode(500)->setBody('Document tables missing. Run migration: php spark migrate');
        }

        $this->ensurePrintTemplateColumns();

        $rows = $this->db->table('doc_format_master')
            ->where('active', 1)
            ->orderBy('doc_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('doctor_document/doc_list', ['doc_master' => $rows]);
    }

    public function docedit_load(int $docId = 0)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $this->ensurePrintTemplateColumns();

        $master = [];
        if ($docId > 0) {
            $master = $this->db->table('doc_format_master')->where('df_id', $docId)->get(1)->getRowArray() ?? [];
        }

        $items = [];
        if ($docId > 0) {
            $items = $this->db->table('doc_format_sub')
                ->select('id as item_id,input_name,input_code,input_type,input_default_value,short_order')
                ->where('doc_format_id', $docId)
                ->where('active', 1)
                ->orderBy('short_order', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('doctor_document/doc_master_edit', [
            'doc_master' => $master,
            'doc_Item_List' => $items,
            'doc_id' => $docId,
        ]);
    }

    public function report_insert()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['insertid' => 0, 'showcontent' => 'Invalid request']);
        }

        $name = trim((string) $this->request->getPost('input_docname'));
        $desc = trim((string) $this->request->getPost('input_doc_desc'));
        $html = (string) $this->request->getPost('HTMLData');
        $defaultPrintType = (int) $this->request->getPost('default_print_type');
        $printTopMargin = $this->normalizeMarginValue($this->request->getPost('print_top_margin'), 6.10);
        $printBottomMargin = $this->normalizeMarginValue($this->request->getPost('print_bottom_margin'), 2.50);
        $printLeftMargin = $this->normalizeMarginValue($this->request->getPost('print_left_margin'), 0.70);
        $printRightMargin = $this->normalizeMarginValue($this->request->getPost('print_right_margin'), 0.70);
        $printHeaderMargin = $this->normalizeMarginValue($this->request->getPost('print_header_margin'), 0.50);
        $printFooterMargin = $this->normalizeMarginValue($this->request->getPost('print_footer_margin'), 1.50);

        if ($name === '') {
            return $this->response->setJSON(['insertid' => 0, 'showcontent' => 'Document name is required']);
        }

        $this->ensurePrintTemplateColumns();

        $this->db->table('doc_format_master')->insert([
            'doc_name' => $name,
            'doc_desc' => $desc,
            'doc_raw_format' => $html,
            'default_print_type' => ($defaultPrintType === 1 ? 1 : 0),
            'print_top_margin' => $printTopMargin,
            'print_bottom_margin' => $printBottomMargin,
            'print_left_margin' => $printLeftMargin,
            'print_right_margin' => $printRightMargin,
            'print_header_margin' => $printHeaderMargin,
            'print_footer_margin' => $printFooterMargin,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'insertid' => (int) $this->db->insertID(),
            'showcontent' => 'Data saved successfully',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function report_update()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update_record' => 0, 'showcontent' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('df_id');
        $name = trim((string) $this->request->getPost('input_docname'));
        $desc = trim((string) $this->request->getPost('input_doc_desc'));
        $html = (string) $this->request->getPost('HTMLData');
        $defaultPrintType = (int) $this->request->getPost('default_print_type');
        $printTopMargin = $this->normalizeMarginValue($this->request->getPost('print_top_margin'), 6.10);
        $printBottomMargin = $this->normalizeMarginValue($this->request->getPost('print_bottom_margin'), 2.50);
        $printLeftMargin = $this->normalizeMarginValue($this->request->getPost('print_left_margin'), 0.70);
        $printRightMargin = $this->normalizeMarginValue($this->request->getPost('print_right_margin'), 0.70);
        $printHeaderMargin = $this->normalizeMarginValue($this->request->getPost('print_header_margin'), 0.50);
        $printFooterMargin = $this->normalizeMarginValue($this->request->getPost('print_footer_margin'), 1.50);

        if ($id <= 0 || $name === '') {
            return $this->response->setJSON(['update_record' => 0, 'showcontent' => 'Document id/name required']);
        }

        $this->ensurePrintTemplateColumns();

        $this->db->table('doc_format_master')
            ->where('df_id', $id)
            ->update([
                'doc_name' => $name,
                'doc_desc' => $desc,
                'doc_raw_format' => $html,
                'default_print_type' => ($defaultPrintType === 1 ? 1 : 0),
                'print_top_margin' => $printTopMargin,
                'print_bottom_margin' => $printBottomMargin,
                'print_left_margin' => $printLeftMargin,
                'print_right_margin' => $printRightMargin,
                'print_header_margin' => $printHeaderMargin,
                'print_footer_margin' => $printFooterMargin,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON([
            'update_record' => 1,
            'showcontent' => 'Data saved successfully',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function doc_input_list(int $docId)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $items = $this->db->table('doc_format_sub')
            ->select('id as item_id,input_name,input_code,input_type,input_default_value,short_order')
            ->where('doc_format_id', $docId)
            ->where('active', 1)
            ->orderBy('short_order', 'ASC')
            ->get()
            ->getResultArray();

        return view('doctor_document/doc_input_list', [
            'doc_Item_List' => $items,
            'doc_id' => $docId,
        ]);
    }

    public function input_parameter_load(int $itemId, int $docId)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $row = $this->db->table('doc_format_sub')
            ->where('id', $itemId)
            ->where('doc_format_id', $docId)
            ->get(1)
            ->getRowArray() ?? [];

        return $this->response->setJSON([
            'update' => ! empty($row) ? 1 : 0,
            'row' => $row,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function input_parameter_add()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['insert_id' => 0, 'showcontent' => 'Invalid request']);
        }

        $docId = (int) $this->request->getPost('doc_id');
        $inputName = trim((string) $this->request->getPost('input_input_name'));
        $inputCode = trim((string) $this->request->getPost('input_input_code'));
        $inputType = trim((string) $this->request->getPost('input_type'));
        $defaultValue = (string) $this->request->getPost('input_default_value');

        if ($docId <= 0 || $inputName === '' || $inputCode === '') {
            return $this->response->setJSON(['insert_id' => 0, 'showcontent' => 'Required fields missing']);
        }

        $exists = $this->db->table('doc_format_sub')
            ->where('doc_format_id', $docId)
            ->where('input_code', $inputCode)
            ->where('active', 1)
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON(['insert_id' => 0, 'showcontent' => 'Input code already exists']);
        }

        $maxOrderRow = $this->db->table('doc_format_sub')->selectMax('short_order')->where('doc_format_id', $docId)->get()->getRowArray();
        $nextOrder = (int) ($maxOrderRow['short_order'] ?? 0) + 1;

        $this->db->table('doc_format_sub')->insert([
            'doc_format_id' => $docId,
            'input_name' => $inputName,
            'input_code' => $inputCode,
            'input_type' => $inputType !== '' ? $inputType : 'text',
            'input_default_value' => $defaultValue,
            'short_order' => max(1, $nextOrder),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'insert_id' => (int) $this->db->insertID(),
            'showcontent' => 'Data saved successfully',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function input_parameter_edit()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update_value' => 0, 'showcontent' => 'Invalid request']);
        }

        $docId = (int) $this->request->getPost('doc_id');
        $subId = (int) $this->request->getPost('doc_sub_id');
        $inputName = trim((string) $this->request->getPost('input_input_name'));
        $inputCode = trim((string) $this->request->getPost('input_input_code'));
        $inputType = trim((string) $this->request->getPost('input_type'));
        $defaultValue = (string) $this->request->getPost('input_default_value');

        if ($docId <= 0 || $subId <= 0 || $inputName === '' || $inputCode === '') {
            return $this->response->setJSON(['update_value' => 0, 'showcontent' => 'Required fields missing']);
        }

        $exists = $this->db->table('doc_format_sub')
            ->where('doc_format_id', $docId)
            ->where('input_code', $inputCode)
            ->where('id !=', $subId)
            ->where('active', 1)
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON(['update_value' => 0, 'showcontent' => 'Input code already exists']);
        }

        $this->db->table('doc_format_sub')
            ->where('id', $subId)
            ->where('doc_format_id', $docId)
            ->update([
                'input_name' => $inputName,
                'input_code' => $inputCode,
                'input_type' => $inputType !== '' ? $inputType : 'text',
                'input_default_value' => $defaultValue,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON([
            'update_value' => 1,
            'showcontent' => 'Data saved successfully',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function p_doc_record(int $patientId)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $patient = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray();
        if (! is_array($patient)) {
            return $this->response->setStatusCode(404)->setBody('Patient not found');
        }

        $docFormats = $this->db->table('doc_format_master')
            ->where('active', 1)
            ->orderBy('doc_name', 'ASC')
            ->get()
            ->getResultArray();

        $doctorList = $this->db->table('doctor_master')
            ->select('id,p_fname')
            ->where('active', 1)
            ->orderBy('p_fname', 'ASC')
            ->get()
            ->getResultArray();

        $patientDocs = $this->db->table('patient_doc pd')
            ->select('pd.id,pd.date_issue,dm.doc_name')
            ->join('doc_format_master dm', 'pd.doc_format_id=dm.df_id', 'left')
            ->where('pd.p_id', $patientId)
            ->orderBy('pd.date_issue', 'DESC')
            ->orderBy('pd.id', 'DESC')
            ->get()
            ->getResultArray();

        return view('doctor_document/patient_doc_master', [
            'person_info' => $patient,
            'doc_format' => $docFormats,
            'doclist' => $doctorList,
            'patient_doc' => $patientDocs,
            'pno' => $patientId,
            'age_text' => $this->buildAgeText($patient),
        ]);
    }

    public function create_doc()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $patientId = (int) $this->request->getPost('patient_id');
        $docFormatId = (int) $this->request->getPost('document_format_id');
        $doctorId = (int) $this->request->getPost('doc_id');
        $issueDate = trim((string) $this->request->getPost('doc_issue_date'));

        $template = $this->db->table('doc_format_master')->where('df_id', $docFormatId)->get(1)->getRowArray();
        $patient = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray();
        $doctor = $this->db->table('doctor_master')->where('id', $doctorId)->get(1)->getRowArray();

        if (! is_array($template) || ! is_array($patient)) {
            return $this->response->setBody('0');
        }

        $reportString = (string) ($template['doc_raw_format'] ?? '');
        $reportString = $this->applyDocumentTokens($reportString, $patient, $doctor, $issueDate);

        $issueMysql = $this->normalizeIssueDate($issueDate);

        $this->db->table('patient_doc')->insert([
            'raw_data' => $reportString,
            'doc_format_id' => $docFormatId,
            'p_id' => $patientId,
            'dr_id' => $doctorId,
            'date_issue' => $issueMysql,
            'update_pre_value' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $insertId = (int) $this->db->insertID();

        $inputs = $this->db->table('doc_format_sub')
            ->where('doc_format_id', $docFormatId)
            ->where('active', 1)
            ->orderBy('short_order', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($inputs as $row) {
            $this->db->table('patient_doc_raw')->insert([
                'p_id' => $patientId,
                'p_doc_id' => $insertId,
                'p_doc_sub_id' => (int) ($row['id'] ?? 0),
                'p_doc_raw_value' => (string) ($row['input_default_value'] ?? ''),
                'update_data' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->response->setBody((string) $insertId);
    }

    public function re_create_doc(int $docId)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $patientDoc = $this->db->table('patient_doc')->where('id', $docId)->get(1)->getRowArray();
        if (! is_array($patientDoc)) {
            return $this->response->setStatusCode(404)->setBody('Document not found');
        }

        $template = $this->db->table('doc_format_master')->where('df_id', (int) ($patientDoc['doc_format_id'] ?? 0))->get(1)->getRowArray();
        $patient = $this->db->table('patient_master')->where('id', (int) ($patientDoc['p_id'] ?? 0))->get(1)->getRowArray();
        $doctor = $this->db->table('doctor_master')->where('id', (int) ($patientDoc['dr_id'] ?? 0))->get(1)->getRowArray();

        if (! is_array($template) || ! is_array($patient)) {
            if ($this->request->isAJAX()) {
                return $this->load_doc($docId);
            }
            return redirect()->to(base_url('Document_Patient/load_doc/' . $docId));
        }

        $issueDate = '';
        if (! empty($patientDoc['date_issue'])) {
            $issueDate = date('d/m/Y', strtotime((string) $patientDoc['date_issue']));
        }

        $reportString = $this->applyDocumentTokens((string) ($template['doc_raw_format'] ?? ''), $patient, $doctor, $issueDate);

        $this->db->table('patient_doc')->where('id', $docId)->update([
            'raw_data' => $reportString,
            'update_pre_value' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->request->isAJAX()) {
            return $this->Pre_Data($docId);
        }

        return redirect()->to(base_url('Document_Patient/Pre_Data/' . $docId));
    }

    public function Pre_Data(int $patientDocId)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $patientDoc = $this->db->table('patient_doc')->where('id', $patientDocId)->get(1)->getRowArray();
        if (! is_array($patientDoc)) {
            return $this->response->setStatusCode(404)->setBody('Document not found');
        }

        $patient = $this->db->table('patient_master')->where('id', (int) ($patientDoc['p_id'] ?? 0))->get(1)->getRowArray();

        $inputs = $this->db->table('patient_doc_raw r')
            ->select('r.id,r.p_doc_raw_value,r.update_data,s.input_name,s.input_type')
            ->join('doc_format_sub s', 'r.p_doc_sub_id=s.id', 'left')
            ->where('r.p_doc_id', $patientDocId)
            ->orderBy('s.short_order', 'ASC')
            ->get()
            ->getResultArray();

        return view('doctor_document/doc_pre_data', [
            'patient_doc' => $patientDoc,
            'person_info' => $patient,
            'doc_format_sub' => $inputs,
            'patient_doc_id' => $patientDocId,
        ]);
    }

    public function Entry_Update()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $testId = (int) $this->request->getPost('test_id');
        $testValue = (string) $this->request->getPost('test_value');

        $this->db->table('patient_doc_raw')->where('id', $testId)->update([
            'p_doc_raw_value' => $testValue,
            'update_data' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setBody($testValue);
    }

    public function update_doc_field(int $patientDocId)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $pending = $this->db->table('patient_doc_raw')
            ->where('p_doc_id', $patientDocId)
            ->where('update_data', 0)
            ->countAllResults();

        if ($pending > 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Some field pending',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $patientDoc = $this->db->table('patient_doc')->where('id', $patientDocId)->get(1)->getRowArray();
        $report = (string) ($patientDoc['raw_data'] ?? '');

        $values = $this->db->table('patient_doc_raw r')
            ->select('r.p_doc_raw_value,s.input_code')
            ->join('doc_format_sub s', 'r.p_doc_sub_id=s.id', 'left')
            ->where('r.p_doc_id', $patientDocId)
            ->get()
            ->getResultArray();

        foreach ($values as $row) {
            $code = trim((string) ($row['input_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $val = nl2br((string) ($row['p_doc_raw_value'] ?? ''));
            $report = str_replace('{' . $code . '}', $val, $report);
        }

        $this->db->table('patient_doc')->where('id', $patientDocId)->update([
            'raw_data' => $report,
            'update_pre_value' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Compile done',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function load_doc(int $patientDocId)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $patientDoc = $this->db->table('patient_doc')->where('id', $patientDocId)->get(1)->getRowArray();
        if (! is_array($patientDoc)) {
            return $this->response->setStatusCode(404)->setBody('Document not found');
        }

        $patient = $this->db->table('patient_master')->where('id', (int) ($patientDoc['p_id'] ?? 0))->get(1)->getRowArray();
        $printTemplates = $this->getDocumentPrintTemplates();

        $defaultTemplateId = 0;
        foreach ($printTemplates as $templateRow) {
            if ((int) ($templateRow['is_default'] ?? 0) === 1) {
                $defaultTemplateId = (int) ($templateRow['id'] ?? 0);
                break;
            }
        }
        if ($defaultTemplateId <= 0 && ! empty($printTemplates)) {
            $defaultTemplateId = (int) ($printTemplates[0]['id'] ?? 0);
        }

        return view('doctor_document/patient_doc_edit', [
            'patient_doc' => $patientDoc,
            'person_info' => $patient,
            'print_templates' => $printTemplates,
            'default_print_template_id' => $defaultTemplateId,
        ]);
    }

    public function update_doc()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $documentId = (int) $this->request->getPost('document_id');
        $htmlData = (string) $this->request->getPost('HTMLData');

        $updated = $this->db->table('patient_doc')->where('id', $documentId)->update([
            'raw_data' => $htmlData,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'update_value' => $updated ? 1 : 0,
            'showcontent' => $updated ? 'Data saved successfully' : 'Unable to save',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function create_final(int $patientDocId, ?int $printOnType = null)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        // Release session lock IMMEDIATELY so other requests from the same browser
        // are not blocked while mPDF generates the PDF (which can take 5-30 seconds).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // mPDF is memory-heavy; ensure enough headroom and execution time.
        $currentMemory = ini_get('memory_limit');
        if ((int) $currentMemory > 0 && (int) $currentMemory < 256) {
            ini_set('memory_limit', '256M');
        }
        set_time_limit(120);

        $this->ensurePrintTemplateColumns();
        $this->ensureDocumentPrintTemplateTable();

        $requestedTemplateId = (int) ($this->request->getGet('ptid') ?? 0);

        $patientDoc = $this->db->table('patient_doc pd')
            ->select('pd.*,dm.doc_name,dm.doc_desc,dm.default_print_type,dm.print_top_margin,dm.print_bottom_margin,dm.print_left_margin,dm.print_right_margin,dm.print_header_margin,dm.print_footer_margin,p.p_fname,p.p_relative,p.p_rname,p.p_code,p.gender,p.age,p.age_in_month,p.estimate_dob,p.dob,dr.p_fname as dr_name')
            ->join('doc_format_master dm', 'pd.doc_format_id=dm.df_id', 'left')
            ->join('patient_master p', 'pd.p_id=p.id', 'left')
            ->join('doctor_master dr', 'pd.dr_id=dr.id', 'left')
            ->where('pd.id', $patientDocId)
            ->get(1)
            ->getRowArray();

        if (! is_array($patientDoc)) {
            return $this->response->setStatusCode(404)->setBody('Document not found');
        }

        $selectedPrintTemplate = null;
        if ($requestedTemplateId > 0 && $this->db->tableExists('doc_print_templates')) {
            $selectedPrintTemplate = $this->db->table('doc_print_templates')
                ->where('id', $requestedTemplateId)
                ->where('status', 1)
                ->get(1)
                ->getRowArray();
        }

        if (! is_array($selectedPrintTemplate) && $this->db->tableExists('doc_print_templates')) {
            $selectedPrintTemplate = $this->db->table('doc_print_templates')
                ->where('status', 1)
                ->where('is_default', 1)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        $resolvedPrintType = $printOnType;
        if ($resolvedPrintType === null) {
            if (is_array($selectedPrintTemplate)) {
                $resolvedPrintType = (int) ($selectedPrintTemplate['print_on_type'] ?? 1);
            } else {
                $resolvedPrintType = (int) ($patientDoc['default_print_type'] ?? 0);
            }
        }
        $resolvedPrintType = ((int) $resolvedPrintType === 1) ? 1 : 0;

        if (is_array($selectedPrintTemplate)) {
            $printTopMargin = $this->normalizeMarginValue($selectedPrintTemplate['page_margin_top_cm'] ?? null, 6.10);
            $printBottomMargin = $this->normalizeMarginValue($selectedPrintTemplate['page_margin_bottom_cm'] ?? null, 2.50);
            $printLeftMargin = $this->normalizeMarginValue($selectedPrintTemplate['page_margin_left_cm'] ?? null, 0.70);
            $printRightMargin = $this->normalizeMarginValue($selectedPrintTemplate['page_margin_right_cm'] ?? null, 0.70);
            $printHeaderMargin = $this->normalizeMarginValue($selectedPrintTemplate['margin_header_cm'] ?? null, 0.50);
            $printFooterMargin = $this->normalizeMarginValue($selectedPrintTemplate['margin_footer_cm'] ?? null, 1.50);
            $pageSize = trim((string) ($selectedPrintTemplate['page_size'] ?? 'A4'));
            if ($pageSize === '') {
                $pageSize = 'A4';
            }
        } else {
            $printTopMargin = $this->normalizeMarginValue($patientDoc['print_top_margin'] ?? null, 6.10);
            $printBottomMargin = $this->normalizeMarginValue($patientDoc['print_bottom_margin'] ?? null, 2.50);
            $printLeftMargin = $this->normalizeMarginValue($patientDoc['print_left_margin'] ?? null, 0.70);
            $printRightMargin = $this->normalizeMarginValue($patientDoc['print_right_margin'] ?? null, 0.70);
            $printHeaderMargin = $this->normalizeMarginValue($patientDoc['print_header_margin'] ?? null, 0.50);
            $printFooterMargin = $this->normalizeMarginValue($patientDoc['print_footer_margin'] ?? null, 1.50);
            $pageSize = 'A4';
        }

        $issueDate = ! empty($patientDoc['date_issue']) ? date('d-m-Y', strtotime((string) $patientDoc['date_issue'])) : date('d-m-Y');
        $printNo = 1;
        if ($this->db->tableExists('file_upload_data')) {
            $printNo = (int) $this->db->table('file_upload_data')->where('doc_id', $patientDocId)->countAllResults() + 1;
        }

        $headerRef = 'Document Ref. No.' . date('Y') . '/' . $printNo . '/' . $patientDocId;
        $content = '<table border="0" cellpadding="1" cellspacing="1" style="width:100%"><tbody><tr><td>'
            . $headerRef . '</td><td style="text-align:right">Date : ' . $issueDate . '</td></tr></tbody></table>';
        $content .= (string) ($patientDoc['raw_data'] ?? '');

        $data = [
            'content' => $content,
            'print_on_type' => $resolvedPrintType,
            'bar_content' => $headerRef . '/' . $issueDate,
            'doctor_name' => (string) ($patientDoc['dr_name'] ?? ''),
            'report_title' => (string) ($patientDoc['doc_name'] ?? 'Patient Document'),
            'print_top_margin' => $printTopMargin,
            'print_bottom_margin' => $printBottomMargin,
            'print_left_margin' => $printLeftMargin,
            'print_right_margin' => $printRightMargin,
            'print_header_margin' => $printHeaderMargin,
            'print_footer_margin' => $printFooterMargin,
            'custom_header_html' => (string) ($selectedPrintTemplate['header_html'] ?? ''),
            'custom_footer_html' => (string) ($selectedPrintTemplate['footer_html'] ?? ''),
        ];

        $mpdfTempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
        if (! is_dir($mpdfTempDir)) {
            mkdir($mpdfTempDir, 0755, true);
        }

        try {
            $mpdf = new Mpdf([
                'format' => $pageSize,
                'margin_top' => $printTopMargin * 10,
                'margin_bottom' => $printBottomMargin * 10,
                'margin_left' => $printLeftMargin * 10,
                'margin_right' => $printRightMargin * 10,
                'margin_header' => $printHeaderMargin * 10,
                'margin_footer' => $printFooterMargin * 10,
                'tempDir' => $mpdfTempDir,
                'autoScriptToLang' => false,
                'autoLanguageDetection' => false,
                'autoArabic' => false,
                'autoVietnamese' => false,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[DoctorDocument::create_final] mPDF init failed: {msg}', ['msg' => $e->getMessage()]);
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Content-Type', 'text/plain')
                ->setBody('PDF init failed: ' . $e->getMessage());
        }

        if ($resolvedPrintType === 1) {
            $hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
            $mpdf->SetWatermarkText($hospitalName, 0.1);
            $mpdf->showWatermarkText = true;
        }

        $html = view('doctor_document/doc_letterhead_print', $data);

        try {
            $mpdf->WriteHTML($html, HTMLParserMode::DEFAULT_MODE);
        } catch (\Throwable $e) {
            log_message('error', '[DoctorDocument::create_final] mPDF WriteHTML failed: {msg}', ['msg' => $e->getMessage()]);
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Content-Type', 'text/plain')
                ->setBody('PDF generation failed: ' . $e->getMessage());
        }

        $fileName = 'Document-' . $patientDocId . '-' . date('YmdHis') . '.pdf';

        try {
            $pdfBytes = $mpdf->Output($fileName, 'S');
        } catch (\Throwable $e) {
            log_message('error', '[DoctorDocument::create_final] mPDF Output failed: {msg}', ['msg' => $e->getMessage()]);
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Content-Type', 'text/plain')
                ->setBody('PDF output failed: ' . $e->getMessage());
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($pdfBytes);
    }

    private function normalizeIssueDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return date('Y-m-d');
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d'];
        foreach ($formats as $fmt) {
            $obj = \DateTime::createFromFormat($fmt, $date);
            if ($obj instanceof \DateTime) {
                return $obj->format('Y-m-d');
            }
        }

        $ts = strtotime($date);
        if ($ts === false) {
            return date('Y-m-d');
        }

        return date('Y-m-d', $ts);
    }

    private function buildAgeText(array $patient): string
    {
        if (function_exists('get_age_1')) {
            return (string) get_age_1(
                $patient['dob'] ?? null,
                $patient['age'] ?? '',
                $patient['age_in_month'] ?? '',
                $patient['estimate_dob'] ?? '',
                date('Y-m-d H:i:s')
            );
        }

        return (string) ($patient['age'] ?? '-');
    }

    private function applyDocumentTokens(string $template, array $patient, ?array $doctor, string $issueDate): string
    {
        $gender = (int) ($patient['gender'] ?? 0);
        $genderText = $gender === 1 ? 'Male' : ($gender === 2 ? 'Female' : 'Other');
        $heShe = $gender === 2 ? 'she' : 'he';
        $hisHer = $gender === 2 ? 'her' : 'his';

        $address = trim(implode(', ', array_filter([
            (string) ($patient['add1'] ?? ''),
            (string) ($patient['add2'] ?? ''),
            (string) ($patient['city'] ?? ''),
            (string) ($patient['state'] ?? ''),
        ])));

        $replace = [
            '{p_code}' => (string) ($patient['p_code'] ?? ''),
            '{p_fname}' => (string) ($patient['p_fname'] ?? ''),
            '{p_rname}' => (string) ($patient['p_rname'] ?? ''),
            '{str_age}' => $this->buildAgeText($patient),
            '{p_relative}' => (string) ($patient['p_relative'] ?? ''),
            '{gender}' => $genderText,
            '{p_title}' => (string) ($patient['title'] ?? ''),
            '{p_address}' => $address,
            '{p_he_she}' => $heShe,
            '{p_his_her}' => $hisHer,
            '{dr_name}' => (string) ($doctor['p_fname'] ?? ''),
            '{dr_sign}' => nl2br((string) ($doctor['doc_sign'] ?? '')),
            '{current_date}' => date('d-m-Y'),
            '{issue_date}' => $issueDate !== '' ? str_replace('/', '-', $issueDate) : date('d-m-Y'),
        ];

        return strtr($template, $replace);
    }
}
