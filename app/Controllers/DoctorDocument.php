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

    /**
     * Replace {{PLACEHOLDER}} tokens in a custom header/footer HTML string
     * with live hospital constants and patient data before passing to mPDF.
     *
     * @param array<string, mixed> $doc  Row from patient_doc join query
     */
    private function resolveDocPrintTemplatePlaceholders(string $html, array $doc): string
    {
        if ($html === '') {
            return '';
        }

        $logoFile    = defined('H_logo') ? (string) constant('H_logo') : 'logo.png';
        $logoAbsPath = FCPATH . 'assets/images/' . $logoFile;
        $logoSrc = $this->buildImageDataUriFromPath($logoAbsPath);
        if ($logoSrc === '' && is_file($logoAbsPath)) {
            $logoSrc = str_replace('\\', '/', $logoAbsPath);
        }
        if ($logoSrc === '') {
            $globMatches = glob(FCPATH . 'assets/images/hospital_logo_*');
            if (is_array($globMatches) && ! empty($globMatches)) {
                usort($globMatches, fn($a, $b) => filemtime($b) <=> filemtime($a));
                $logoSrc = $this->buildImageDataUriFromPath($globMatches[0]);
            }
        }
        if ($logoSrc === '') {
            $logoSrc = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAA=';
        }
        $logoHtml    = '<img style="width:100px;vertical-align:top;" src="'
            . $logoSrc . '" />';

        $age    = (string) ($doc['age']    ?? '');
        $gender = (string) ($doc['gender'] ?? '');
        $ageSex = trim($age . ($gender !== '' ? '/' . $gender : ''));

        $drSignImg  = '';
        $drSignFile = (string) ($doc['dr_sign'] ?? $doc['sign_image'] ?? $doc['doctor_sign'] ?? '');
        if ($drSignFile !== '') {
            $drSignAbsPath = FCPATH . 'assets/images/' . ltrim($drSignFile, '/\\');
            $drSignSrc = $this->buildImageDataUriFromPath($drSignAbsPath);
            if ($drSignSrc === '' && is_file($drSignAbsPath)) {
                $drSignSrc = str_replace('\\', '/', $drSignAbsPath);
            }
            if ($drSignSrc === '') {
                $drSignSrc = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAA=';
            }

            $drSignImg = '<img style="height:40px;" src="' . $drSignSrc . '" />';
        }

        $map = [
            'assets/images/{{H_logo}}' => $logoSrc,
            'assets/images/{{h_logo}}' => $logoSrc,
            '{{H_Name}}'             => defined('H_Name')      ? (string) constant('H_Name')      : '',
            '{{H_address_1}}'        => defined('H_address_1') ? (string) constant('H_address_1') : '',
            '{{H_address_2}}'        => defined('H_address_2') ? (string) constant('H_address_2') : '',
            '{{H_phone_No}}'         => defined('H_phone_No')  ? (string) constant('H_phone_No')  : '',
            '{{H_Email}}'            => defined('H_Email')     ? (string) constant('H_Email')     : '',
            '{{H_logo}}'             => $logoSrc,
            '{{H_logo_abs}}'         => $logoSrc,
            '{{hospital_logo_html}}' => $logoHtml,
            '{{PATIENT_NAME}}'       => (string) ($doc['p_fname'] ?? ''),
            '{{pName}}'              => (string) ($doc['p_fname'] ?? ''),
            '{{UHID}}'               => (string) ($doc['p_code']  ?? ''),
            '{{AGE_GENDER}}'         => $ageSex,
            '{{age_sex}}'            => $ageSex,
            '{{phoneno}}'            => (string) ($doc['phoneno'] ?? $doc['phone_no'] ?? $doc['contact_no'] ?? ''),
            '{{doctor_name}}'        => (string) ($doc['dr_name'] ?? ''),
            '{{doctor_sign_html}}'   => $drSignImg,
            '{{CURRENT_DATE}}'       => date('d-m-Y'),
            '{{CURRENT_DATETIME}}'   => date('d-m-Y H:i'),
            '{{print_time}}'         => date('d-m-Y H:i:s'),
            '{{qr_content}}'         => (string) ($doc['id'] ?? ''),
        ];

        return str_replace(array_keys($map), array_values($map), $html);
    }

    private function buildImageDataUriFromPath(string $absolutePath): string
    {
        $absolutePath = trim($absolutePath);
        if ($absolutePath === '' || ! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return '';
        }

        $bytes = @file_get_contents($absolutePath);
        if ($bytes === false || $bytes === '') {
            return '';
        }

        $mime = 'image/png';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($absolutePath);
            if (is_string($detected) && str_starts_with($detected, 'image/')) {
                $mime = $detected;
            }
        }

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }

    private function resolveHospitalLogoDataUri(): string
    {
        $logoFile = trim($this->getHospitalSettingValue('H_logo'));
        if ($logoFile === '' && defined('H_logo')) {
            $logoFile = (string) constant('H_logo');
        }

        $logoAbsPath = '';
        if ($logoFile !== '') {
            $candidates = [
                FCPATH . 'assets/images/' . $logoFile,
                FCPATH . 'assets/images/' . ltrim($logoFile, '/\\'),
                FCPATH . $logoFile,
            ];
            foreach ($candidates as $cand) {
                if (is_file($cand)) {
                    $logoAbsPath = $cand;
                    break;
                }
            }
        }

        if ($logoAbsPath === '') {
            $globMatches = glob(FCPATH . 'assets/images/hospital_logo_*');
            if (is_array($globMatches) && ! empty($globMatches)) {
                usort($globMatches, fn($a, $b) => filemtime($b) <=> filemtime($a));
                $logoAbsPath = $globMatches[0];
            }
        }

        if ($logoAbsPath !== '') {
            $src = $this->buildImageDataUriFromPath($logoAbsPath);
            if ($src !== '') {
                return $src;
            }
            return str_replace('\\', '/', $logoAbsPath);
        }

        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAA=';
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }

        return $this->db->fieldExists($column, $table);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function filterPayloadByExistingColumns(string $table, array $payload): array
    {
        if (! $this->db->tableExists($table)) {
            return [];
        }

        $fields = $this->db->getFieldNames($table) ?? [];
        $out = [];
        foreach ($payload as $key => $value) {
            if (in_array($key, $fields, true)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public function index()
    {
        return $this->workspace();
    }

    public function workspace()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        return view('doctor_document/workspace');
    }

    public function health_document_fhir_preview(int $patientDocId = 0)
    {
        if ($patientDocId <= 0) {
            $patientDocId = (int) ($this->request->getGet('patient_doc_id') ?? $this->request->getGet('doc_id') ?? $this->request->getGet('health_doc_id') ?? $this->request->getGet('record_id') ?? 0);
        }

        $entityType = trim((string) ($this->request->getGet('entity_type') ?? ''));

        if ($patientDocId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'patient_doc_id is required']);
        }

        $source = $this->buildHealthDocumentSource($patientDocId, $entityType);
        if (empty($source)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Health document source data not found for ID: ' . $patientDocId]);
        }

        $factory = new \App\Libraries\Abdm\Fhir\FhirGeneratorFactory();
        $res = $factory->healthDocument()->generate($source);

        $bundle = $res['fhir_bundle'] ?? $res['bundle'] ?? null;

        return $this->response->setJSON([
            'status' => 'ok',
            'ok' => 1,
            'bundle' => $bundle,
            'fhir_bundle' => $bundle,
            'patient_id' => (int) ($source['patient']['id'] ?? 0),
            'abha_id' => (string) ($source['patient']['abha_id'] ?? ''),
            'document_title' => (string) ($source['document_title'] ?? ''),
            'hi_type' => 'HealthDocumentRecord',
            'care_context_reference' => $res['care_context_reference'] ?? '',
            'care_context_display' => $res['care_context_display'] ?? '',
            'validation' => $res['validation'] ?? [],
        ]);
    }

    private function ensureNabhIpdScansTable(): bool
    {
        if (! $this->db->tableExists('nabh_ipd_scans')) {
            $sql = "CREATE TABLE IF NOT EXISTS `nabh_ipd_scans` (
              `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `ipd_id` INT UNSIGNED NOT NULL DEFAULT 0,
              `patient_id` INT UNSIGNED NOT NULL DEFAULT 0,
              `nabh_category` VARCHAR(50) NOT NULL,
              `category_title` VARCHAR(150) NOT NULL,
              `file_name` VARCHAR(255) NOT NULL,
              `file_path` VARCHAR(255) NOT NULL,
              `file_type` VARCHAR(100) NOT NULL,
              `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
              `patient_doc_id` INT UNSIGNED NOT NULL DEFAULT 0,
              `abdm_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
              `care_context_reference` VARCHAR(100) NULL,
              `uploaded_by` INT UNSIGNED NOT NULL DEFAULT 1,
              `created_at` DATETIME NULL,
              `updated_at` DATETIME NULL,
              KEY `idx_ipd_id` (`ipd_id`),
              KEY `idx_patient_id` (`patient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            try {
                $this->db->query($sql);
            } catch (\Throwable $e) {
                log_message('error', 'Unable to create nabh_ipd_scans table: {msg}', ['msg' => $e->getMessage()]);
                return false;
            }
        }
        return true;
    }

    public function search_ipd_patient()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX only']);
        }

        $query = trim((string) ($this->request->getGet('ipd_key') ?? $this->request->getGet('query') ?? ''));
        if ($query === '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'IPD No. or Patient identifier is required']);
        }

        $builder = $this->db->table('ipd_master')
            ->select('ipd_master.*, patient_master.p_fname, patient_master.p_lname, patient_master.p_code, patient_master.age, patient_master.gender, patient_master.dob, patient_master.abha_id, patient_master.abha_address, patient_master.mphone1, doctor_master.p_fname as dr_fname, doctor_master.p_lname as dr_lname')
            ->join('patient_master', 'patient_master.id = ipd_master.p_id', 'left')
            ->join('doctor_master', 'doctor_master.id = ipd_master.r_doc_id', 'left');

        if (is_numeric($query)) {
            $builder->groupStart()
                ->where('ipd_master.id', (int) $query)
                ->orWhere('ipd_master.p_id', (int) $query)
                ->orWhere('ipd_master.ipd_code', $query)
                ->orWhere('patient_master.p_code', $query)
                ->groupEnd();
        } else {
            $builder->groupStart()
                ->like('ipd_master.ipd_code', $query)
                ->orLike('ipd_master.P_name', $query)
                ->orLike('patient_master.p_code', $query)
                ->orLike('patient_master.p_fname', $query)
                ->orLike('patient_master.p_lname', $query)
                ->groupEnd();
        }

        $ipdRecords = $builder->orderBy('ipd_master.id', 'DESC')->limit(10)->get()->getResultArray();

        if (empty($ipdRecords)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'No IPD admission found matching "' . esc($query) . '"']);
        }

        $this->ensureNabhIpdScansTable();

        $outputList = [];
        foreach ($ipdRecords as $row) {
            $ipdId = (int) $row['id'];
            $patientId = (int) $row['p_id'];
            $patientName = trim(trim((string) ($row['p_fname'] ?? '')) . ' ' . trim((string) ($row['p_lname'] ?? '')));
            if ($patientName === '') {
                $patientName = (string) ($row['P_name'] ?? ('Patient #' . $patientId));
            }
            $doctorName = trim(trim((string) ($row['dr_fname'] ?? '')) . ' ' . trim((string) ($row['dr_lname'] ?? '')));
            if ($doctorName === '') {
                $doctorName = (string) ($row['r_doc_name'] ?? 'Attending Doctor');
            }

            $scans = $this->db->table('nabh_ipd_scans')
                ->where('ipd_id', $ipdId)
                ->get()
                ->getResultArray();

            $scansByCategory = [];
            foreach ($scans as $scan) {
                $scansByCategory[$scan['nabh_category']] = [
                    'scan_id' => (int) $scan['id'],
                    'file_name' => $scan['file_name'],
                    'file_url' => base_url('uploads/nabh_ipd/' . $scan['file_name']),
                    'file_type' => $scan['file_type'],
                    'file_size' => (int) $scan['file_size'],
                    'patient_doc_id' => (int) $scan['patient_doc_id'],
                    'abdm_status' => $scan['abdm_status'],
                    'care_context_reference' => $scan['care_context_reference'],
                    'uploaded_at' => $scan['created_at'],
                ];
            }

            $admDate = ! empty($row['register_date']) ? date('d-m-Y', strtotime((string) $row['register_date'])) : (! empty($row['insert_date']) ? date('d-m-Y', strtotime((string) $row['insert_date'])) : date('d-m-Y'));
            $disDate = ! empty($row['discharge_date']) ? date('d-m-Y', strtotime((string) $row['discharge_date'])) : null;

            $outputList[] = [
                'ipd_id' => $ipdId,
                'ipd_no' => (string) (($row['ipd_code'] ?? '') !== '' ? $row['ipd_code'] : ('IPD-' . $ipdId)),
                'patient_id' => $patientId,
                'patient_name' => $patientName,
                'uhid' => (string) ($row['p_code'] ?? $patientId),
                'age' => (string) ($row['age'] ?? ''),
                'gender' => (string) ($row['gender'] ?? ''),
                'dob' => (string) ($row['dob'] ?? ''),
                'abha_id' => (string) ($row['abha_id'] ?? ''),
                'abha_address' => (string) ($row['abha_address'] ?? ''),
                'mobile' => (string) ($row['mphone1'] ?? $row['P_mobile1'] ?? ''),
                'doctor_name' => $doctorName,
                'doctor_id' => (int) ($row['r_doc_id'] ?? 1),
                'admission_date' => $admDate,
                'discharge_date' => $disDate,
                'scans' => $scansByCategory,
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'records' => $outputList,
        ]);
    }

    public function upload_ipd_nabh_document()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX only']);
        }

        $ipdId = (int) $this->request->getPost('ipd_id');
        $patientId = (int) $this->request->getPost('patient_id');
        $nabhCategory = trim((string) $this->request->getPost('nabh_category'));
        $categoryTitle = trim((string) $this->request->getPost('category_title'));

        if ($ipdId <= 0 || $patientId <= 0 || $nabhCategory === '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'ipd_id, patient_id, and nabh_category are required']);
        }

        $file = $this->request->getFile('scanned_file');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Valid scanned file (JPG, PNG, PDF) is required']);
        }

        $origName = $file->getClientName();
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION) ?: 'pdf');
        $mimeType = $file->getClientMimeType() ?: 'application/pdf';

        $uploadDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'nabh_ipd' . DIRECTORY_SEPARATOR;
        if (! is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $newFileName = 'NABH_IPD_' . $ipdId . '_' . str_replace('-', '_', $nabhCategory) . '_' . date('Ymd_His') . '.' . $ext;
        $file->move($uploadDir, $newFileName);

        $fileFullPath = $uploadDir . $newFileName;
        $pdfPath = $fileFullPath;

        if (function_exists('mime_content_type') && is_file($fileFullPath)) {
            $detected = @mime_content_type($fileFullPath);
            if (is_string($detected) && $detected !== '') {
                $mimeType = $detected;
            }
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && is_file($fileFullPath)) {
            try {
                $imgBytes = @file_get_contents($fileFullPath);
                if ($imgBytes !== false && $imgBytes !== '') {
                    $base64Img = 'data:' . $mimeType . ';base64,' . base64_encode($imgBytes);
                    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => WRITEPATH . 'cache']);
                    $mpdf->WriteHTML('<div style="text-align:center;"><h3 style="font-family:sans-serif;">' . htmlspecialchars($categoryTitle) . '</h3><img src="' . $base64Img . '" style="max-width:100%;height:auto;" /></div>');
                    $pdfFileName = pathinfo($newFileName, PATHINFO_FILENAME) . '.pdf';
                    $pdfPath = $uploadDir . $pdfFileName;
                    $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);
                }
            } catch (\Throwable $e) {
                log_message('warning', 'Unable to wrap image to PDF: {msg}', ['msg' => $e->getMessage()]);
            }
        }

        $docId = $this->db->table('patient_doc')->insert([
            'p_id' => $patientId,
            'doc_format_id' => 1,
            'dr_id' => (int) ($this->request->getPost('doctor_id') ?? 1),
            'date_issue' => date('Y-m-d'),
            'raw_data' => json_encode([
                'title' => $categoryTitle,
                'nabh_category' => $nabhCategory,
                'ipd_id' => $ipdId,
                'file_name' => basename($pdfPath),
                'scanned_at' => date('Y-m-d H:i:s'),
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $patientDocId = (int) $this->db->insertID();

        $this->enqueueHealthDocumentFhirSync($patientDocId);

        $this->db->table('nabh_ipd_scans')
            ->where('ipd_id', $ipdId)
            ->where('nabh_category', $nabhCategory)
            ->delete();

        $user = auth()->user();
        $this->db->table('nabh_ipd_scans')->insert([
            'ipd_id' => $ipdId,
            'patient_id' => $patientId,
            'nabh_category' => $nabhCategory,
            'category_title' => $categoryTitle,
            'file_name' => basename($pdfPath),
            'file_path' => 'uploads/nabh_ipd/' . basename($pdfPath),
            'file_type' => $mimeType,
            'file_size' => (int) filesize($pdfPath),
            'patient_doc_id' => $patientDocId,
            'abdm_status' => 'scanned',
            'care_context_reference' => 'DOC-' . $patientDocId,
            'uploaded_by' => $user ? (int) $user->id : 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Scanned document uploaded successfully for ' . $categoryTitle,
            'patient_doc_id' => $patientDocId,
            'file_name' => basename($pdfPath),
            'file_url' => base_url('uploads/nabh_ipd/' . basename($pdfPath)),
            'nabh_category' => $nabhCategory,
        ]);
    }

    public function push_ipd_nabh_to_abdm()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX only']);
        }

        $patientDocId = (int) $this->request->getPost('patient_doc_id');

        if ($patientDocId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'patient_doc_id is required']);
        }

        $source = $this->buildHealthDocumentSource($patientDocId);
        if (empty($source)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Source health document not found']);
        }

        $factory = new \App\Libraries\Abdm\Fhir\FhirGeneratorFactory();
        $generatorOutput = $factory->healthDocument()->generate($source);

        $hfrId = (string) ($source['hfr_id'] ?? 'HFR-IN-HMS');
        $adapter = new \App\Libraries\Abdm\Fhir\Support\GatewayPayloadAdapter();
        $payload = $adapter->toGatewayPayload($generatorOutput, $source, $hfrId);

        $patientId = (int) ($source['patient']['id'] ?? 0);
        $abhaId = (string) ($source['patient']['abha_address'] ?? $source['patient']['abha_id'] ?? '');

        $gatewayController = new \App\Controllers\AbdmGateway();
        $pushResponse = $gatewayController->pushAdditionalHiRecord($payload, $patientId, $abhaId);

        $responseArray = json_decode((string) $pushResponse->getBody(), true) ?? [];

        if (! empty($responseArray['ok']) || (! empty($responseArray['status']) && $responseArray['status'] === 'queued')) {
            $this->db->table('nabh_ipd_scans')
                ->where('patient_doc_id', $patientDocId)
                ->update([
                    'abdm_status' => 'pushed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return $pushResponse;
    }

    public function wellness_record_fhir_preview(int $patientId = 0, int $opdSessionId = 0)
    {
        if ($patientId <= 0) {
            $patientId = (int) ($this->request->getGet('patient_id') ?? $this->request->getGet('p_id') ?? $this->request->getGet('pid') ?? 0);
        }
        if ($opdSessionId <= 0) {
            $opdSessionId = (int) ($this->request->getGet('opd_id') ?? $this->request->getGet('session_id') ?? 0);
        }

        if ($patientId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'patient_id is required']);
        }

        $source = $this->buildWellnessRecordSource($patientId, $opdSessionId);
        if (empty($source)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Wellness source data (vitals/prescription) not found for patient #' . $patientId]);
        }

        $factory = new \App\Libraries\Abdm\Fhir\FhirGeneratorFactory();
        $res = $factory->wellness()->generate($source);

        $hfrId = (string) ($source['hfr_id'] ?? 'HFR-IN-HMS');
        $adapter = new \App\Libraries\Abdm\Fhir\Support\GatewayPayloadAdapter();
        $gatewayPayload = $adapter->toGatewayPayload($res, $source, $hfrId);

        return $this->response->setJSON([
            'ok' => 1,
            'source' => $source,
            'bundle' => $gatewayPayload['fhir_bundle'] ?? [],
            'fhir_bundle' => $gatewayPayload['fhir_bundle'] ?? [],
            'validation' => $gatewayPayload['fhir_validation'] ?? [],
            'care_context_reference' => $gatewayPayload['care_context_reference'] ?? '',
            'care_context_display' => $gatewayPayload['care_context_display'] ?? '',
        ]);
    }

    public function buildWellnessRecordSource(int $patientId, int $opdSessionId = 0): array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('patient_master')) {
            return [];
        }

        $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray();
        if (empty($patientRow)) {
            return [];
        }

        $rxRow = [];
        if ($this->db->tableExists('opd_prescription')) {
            $builder = $this->db->table('opd_prescription')->where('p_id', $patientId);
            if ($opdSessionId > 0) {
                $builder->groupStart()
                    ->where('id', $opdSessionId)
                    ->orWhere('opd_id', $opdSessionId)
                ->groupEnd();
            }
            $rxRow = $builder->orderBy('id', 'DESC')->get(1)->getRowArray() ?? [];
            if (empty($rxRow) && $opdSessionId > 0) {
                $rxRow = $this->db->table('opd_prescription')->where('p_id', $patientId)->orderBy('id', 'DESC')->get(1)->getRowArray() ?? [];
            }
        }

        $doctorRow = [];
        $drId = (int) ($rxRow['doc_id'] ?? $rxRow['dr_id'] ?? 0);
        if ($drId > 0 && $this->db->tableExists('doctor_master')) {
            $doctorRow = $this->db->table('doctor_master')->where('id', $drId)->get(1)->getRowArray() ?? [];
        }

        $vitals = [];
        $addVital = static function (string $code, string $display, mixed $val, string $unit, string $ucum) use (&$vitals): void {
            if ($val !== null && trim((string) $val) !== '') {
                $vitals[] = [
                    'loinc_code' => $code,
                    'code' => $code,
                    'display' => $display,
                    'value' => (float) $val,
                    'unit' => $unit,
                    'ucum_code' => $ucum,
                ];
            }
        };

        $addVital('8480-6', 'Systolic blood pressure', $rxRow['bp'] ?? null, 'mmHg', 'mm[Hg]');
        $addVital('8462-4', 'Diastolic blood pressure', $rxRow['diastolic'] ?? null, 'mmHg', 'mm[Hg]');
        $addVital('8867-4', 'Heart rate', $rxRow['pulse'] ?? null, '/min', '/min');
        $addVital('8302-2', 'Body height', $rxRow['height'] ?? null, 'cm', 'cm');
        $addVital('29463-7', 'Body weight', $rxRow['weight'] ?? null, 'kg', 'kg');
        $addVital('39156-5', 'Body Mass Index', $rxRow['bmi'] ?? null, 'kg/m2', 'kg/m2');

        $tempVal = $rxRow['temp'] ?? null;
        if ($tempVal !== null && is_numeric($tempVal) && (float) $tempVal > 45) {
            $tempVal = (((float) $tempVal - 32) * 5) / 9;
        }
        $addVital('8310-5', 'Body temperature', $tempVal, 'Cel', 'Cel');
        $addVital('9279-1', 'Respiratory rate', $rxRow['rr_min'] ?? null, '/min', '/min');
        $addVital('59408-5', 'Oxygen saturation in Arterial blood by Pulse oximetry', $rxRow['spo2'] ?? null, '%', '%');
        $addVital('2339-0', 'Blood Glucose', $rxRow['glucose'] ?? null, 'mg/dL', 'mg/dL');

        $physicalExam = [];
        foreach (['complaints', 'diagnosis', 'investigation'] as $f) {
            $val = trim((string) ($rxRow[$f] ?? ''));
            if ($val !== '') {
                $physicalExam[] = ucwords($f) . ': ' . $val;
            }
        }

        $womenWellness = [];
        $lmp = trim((string) ($rxRow['lmp'] ?? $patientRow['lmp'] ?? ''));
        if ($lmp !== '') {
            $womenWellness['lmp'] = $lmp;
        }
        foreach (['gravida', 'para', 'abortion', 'living_children', 'menstrual_history'] as $f) {
            $val = trim((string) ($rxRow[$f] ?? $patientRow[$f] ?? ''));
            if ($val !== '') {
                $womenWellness[$f] = $val;
            }
        }

        $lifestyle = [];
        foreach (['is_smoking' => 'Smoking status', 'is_alcohol' => 'Alcohol use', 'is_tobacoo' => 'Tobacco use'] as $field => $label) {
            $val = trim((string) ($patientRow[$field] ?? ''));
            if ($val !== '' && $val !== '0') {
                $lifestyle[] = $label . ': Yes';
            }
        }
        $adviceStr = trim((string) ($rxRow['advice'] ?? ''));
        if ($adviceStr !== '') {
            $lifestyle[] = 'Diet & Lifestyle Advice: ' . $adviceStr;
        }

        $patientName = trim(trim((string) ($patientRow['p_fname'] ?? '')) . ' ' . trim((string) ($patientRow['p_lname'] ?? '')));
        $abhaIdRaw = '';
        foreach (['abha_id', 'abha_no', 'abha'] as $field) {
            $candidate = trim((string) ($patientRow[$field] ?? ''));
            if ($candidate !== '') {
                $abhaIdRaw = $candidate;
                break;
            }
        }
        $abhaDigits = preg_replace('/\D/', '', $abhaIdRaw);
        $abhaDigits = is_string($abhaDigits) ? $abhaDigits : '';
        $abhaAddress = trim((string) ($patientRow['abha_address'] ?? ''));

        $hfrId = trim($this->getHospitalSettingValue('ABDM_HFR_ID'));
        if ($hfrId === '') {
            $hfrId = 'HFR-IN-HMS';
        }
        $hospitalName = $this->getHospitalSettingValue('H_Name');
        if ($hospitalName === '' && defined('H_Name')) {
            $hospitalName = (string) constant('H_Name');
        }

        $doctorName = trim(trim((string) ($doctorRow['p_fname'] ?? '')) . ' ' . trim((string) ($doctorRow['p_lname'] ?? '')));
        if ($doctorName === '') {
            $doctorName = 'Doctor';
        }

        $visitDate = ! empty($rxRow['date_opd_visit']) ? date('Y-m-d', strtotime((string) $rxRow['date_opd_visit'])) : date('Y-m-d');
        $completedAt = ! empty($rxRow['insert_date']) ? date(DATE_ATOM, strtotime((string) $rxRow['insert_date'])) : date(DATE_ATOM);

        return [
            'record_id' => (string) ($rxRow['id'] ?? $patientId),
            'session_id' => (string) $opdSessionId,
            'visit_date' => $visitDate,
            'completed_at' => $completedAt,
            'encounter' => [
                'id' => (string) ($rxRow['id'] ?? $patientId),
                'class_code' => 'AMB',
                'class_display' => 'ambulatory',
                'start' => $completedAt,
                'end' => $completedAt,
            ],
            'vitals' => $vitals,

            'physical_examination' => $physicalExam,
            'women_wellness' => $womenWellness,
            'advice' => $lifestyle,
            'doctor_name' => $doctorName,
            'hfr_id' => $hfrId,
            'organization' => [
                'id' => $hfrId,
                'name' => $hospitalName,
            ],
            'patient' => [
                'id' => (string) $patientId,
                'uhid' => (string) ($patientRow['uhid_no'] ?? $patientRow['uhid'] ?? $patientRow['patient_code'] ?? $patientId),
                'name' => $patientName,
                'gender' => strtolower((string) ($patientRow['gender'] ?? '')) === 'm' ? 'male' : (strtolower((string) ($patientRow['gender'] ?? '')) === 'f' ? 'female' : 'unknown'),
                'dob' => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
                'abha_id' => $abhaDigits,
                'abha_address' => $abhaAddress,
                'mobile' => (string) ($patientRow['mphone1'] ?? ''),
            ],
            'practitioner' => [
                'id' => (string) $drId,
                'name' => $doctorName,
            ],
        ];
    }

    public function patient_search()
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }

        $search = trim((string) $this->request->getGet('q'));
        $patients = $this->searchDoctorWorkPatients($search);
        $countsByPatient = $this->getDoctorWorkPatientCounts(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $patients));

        foreach ($patients as &$patientRow) {
            $patientId = (int) ($patientRow['id'] ?? 0);
            $patientCounts = $countsByPatient[$patientId] ?? [];
            $patientRow['opd_count'] = (int) ($patientCounts['opd_count'] ?? 0);
            $patientRow['lab_count'] = (int) ($patientCounts['lab_count'] ?? 0);
            $patientRow['ipd_count'] = (int) ($patientCounts['ipd_count'] ?? 0);
        }
        unset($patientRow);

        return view('doctor_document/patient_search', [
            'search' => $search,
            'patients' => $patients,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchDoctorWorkPatients(string $search): array
    {
        $search = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', trim($search)) ?? '';

        if ($search === '') {
            return [];
        }

        $searchTokens = preg_split('/\s+/', $search) ?: [];
        $whereParts = ['1=1'];

        foreach ($searchTokens as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            $escaped = $this->db->escape($token);
            $likeToken = $this->db->escapeLikeString($token);

            if (is_numeric($token)) {
                $clause = "(p.p_code LIKE '%{$likeToken}' ESCAPE '!' OR p.mphone1 = {$escaped}";
                // Aadhaar is stored encrypted; match on its deterministic hash instead.
                $aadhaarHash = (new \App\Libraries\AadhaarVaultService())->hash($token);
                if ($aadhaarHash !== '') {
                    $clause .= ' OR p.udai_hash = ' . $this->db->escape($aadhaarHash);
                }
                $whereParts[] = $clause . ')';
                continue;
            }

            if (ctype_alpha($token)) {
                $whereParts[] = "(p.p_fname LIKE '%{$likeToken}%' ESCAPE '!' OR p.email1 = {$escaped})";
                continue;
            }

            $whereParts[] = "(p.p_code LIKE '{$likeToken}' ESCAPE '!' OR p.email1 = {$escaped})";
        }

        $sql = "SELECT p.*, DATE_FORMAT(p.last_visit,'%d-%m-%Y') AS Last_Visit
            FROM patient_master p
            WHERE " . implode(' AND ', $whereParts) . "
            GROUP BY p.id
            ORDER BY p.last_visit DESC
            LIMIT 100";

        return $this->db->query($sql)->getResultArray();
    }

    /**
     * @param list<int> $patientIds
     * @return array<int, array<string, int>>
     */
    private function getDoctorWorkPatientCounts(array $patientIds): array
    {
        $patientIds = array_values(array_unique(array_filter(array_map('intval', $patientIds), static fn(int $id): bool => $id > 0)));
        if ($patientIds === []) {
            return [];
        }

        $countsByPatient = [];
        foreach ($patientIds as $patientId) {
            $countsByPatient[$patientId] = [
                'opd_count' => 0,
                'lab_count' => 0,
                'ipd_count' => 0,
            ];
        }

        $countSources = [
            ['table' => 'opd_master', 'column' => 'p_id', 'alias' => 'opd_count'],
            ['table' => 'lab_request', 'column' => 'patient_id', 'alias' => 'lab_count'],
            ['table' => 'ipd_master', 'column' => 'p_id', 'alias' => 'ipd_count'],
        ];

        foreach ($countSources as $source) {
            if (! $this->hasColumn($source['table'], $source['column'])) {
                continue;
            }

            $rows = $this->db->table($source['table'])
                ->select($source['column'] . ' AS patient_id, COUNT(*) AS total_count', false)
                ->whereIn($source['column'], $patientIds)
                ->groupBy($source['column'])
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $patientId = (int) ($row['patient_id'] ?? 0);
                if ($patientId <= 0 || ! array_key_exists($patientId, $countsByPatient)) {
                    continue;
                }

                $countsByPatient[$patientId][$source['alias']] = (int) ($row['total_count'] ?? 0);
            }
        }

        return $countsByPatient;
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

        $builder = $this->db->table('doc_format_master');
        if ($this->hasColumn('doc_format_master', 'active')) {
            $builder->where('active', 1);
        }
        $rows = $builder->orderBy('doc_name', 'ASC')->get()->getResultArray();

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
            $itemBuilder = $this->db->table('doc_format_sub')
                ->select('id as item_id,input_name,input_code,input_type,input_default_value,short_order')
                ->where('doc_format_id', $docId);
            if ($this->hasColumn('doc_format_sub', 'active')) {
                $itemBuilder->where('active', 1);
            }
            $items = $itemBuilder->orderBy('short_order', 'ASC')->get()->getResultArray();
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

        $payload = [
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
        ];

        $payload = $this->filterPayloadByExistingColumns('doc_format_master', $payload);
        $this->db->table('doc_format_master')->insert($payload);

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

        $payload = [
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
        ];
        $payload = $this->filterPayloadByExistingColumns('doc_format_master', $payload);

        $this->db->table('doc_format_master')
            ->where('df_id', $id)
            ->update($payload);

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

        $builder = $this->db->table('doc_format_sub')
            ->select('id as item_id,input_name,input_code,input_type,input_default_value,short_order')
            ->where('doc_format_id', $docId);
        if ($this->hasColumn('doc_format_sub', 'active')) {
            $builder->where('active', 1);
        }
        $items = $builder->orderBy('short_order', 'ASC')->get()->getResultArray();

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

        $existsBuilder = $this->db->table('doc_format_sub')
            ->where('doc_format_id', $docId)
            ->where('input_code', $inputCode);
        if ($this->hasColumn('doc_format_sub', 'active')) {
            $existsBuilder->where('active', 1);
        }
        $exists = $existsBuilder->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON(['insert_id' => 0, 'showcontent' => 'Input code already exists']);
        }

        $maxOrderRow = $this->db->table('doc_format_sub')->selectMax('short_order')->where('doc_format_id', $docId)->get()->getRowArray();
        $nextOrder = (int) ($maxOrderRow['short_order'] ?? 0) + 1;

        $payload = [
            'doc_format_id' => $docId,
            'input_name' => $inputName,
            'input_code' => $inputCode,
            'input_type' => $inputType !== '' ? $inputType : 'text',
            'input_default_value' => $defaultValue,
            'short_order' => max(1, $nextOrder),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $payload = $this->filterPayloadByExistingColumns('doc_format_sub', $payload);
        $this->db->table('doc_format_sub')->insert($payload);

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

        $existsBuilder = $this->db->table('doc_format_sub')
            ->where('doc_format_id', $docId)
            ->where('input_code', $inputCode)
            ->where('id !=', $subId);
        if ($this->hasColumn('doc_format_sub', 'active')) {
            $existsBuilder->where('active', 1);
        }
        $exists = $existsBuilder->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON(['update_value' => 0, 'showcontent' => 'Input code already exists']);
        }

        $payload = [
            'input_name' => $inputName,
            'input_code' => $inputCode,
            'input_type' => $inputType !== '' ? $inputType : 'text',
            'input_default_value' => $defaultValue,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $payload = $this->filterPayloadByExistingColumns('doc_format_sub', $payload);

        $this->db->table('doc_format_sub')
            ->where('id', $subId)
            ->where('doc_format_id', $docId)
            ->update($payload);

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

        $docFormatsBuilder = $this->db->table('doc_format_master');
        if ($this->hasColumn('doc_format_master', 'active')) {
            $docFormatsBuilder->where('active', 1);
        }
        $docFormats = $docFormatsBuilder->orderBy('doc_name', 'ASC')->get()->getResultArray();

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

        $patientDocPayload = [
            'raw_data' => $reportString,
            'doc_format_id' => $docFormatId,
            'p_id' => $patientId,
            'dr_id' => $doctorId,
            'date_issue' => $issueMysql,
            'update_pre_value' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $patientDocPayload = $this->filterPayloadByExistingColumns('patient_doc', $patientDocPayload);
        $this->db->table('patient_doc')->insert($patientDocPayload);
        $insertId = (int) $this->db->insertID();

        $inputBuilder = $this->db->table('doc_format_sub')
            ->where('doc_format_id', $docFormatId);
        if ($this->hasColumn('doc_format_sub', 'active')) {
            $inputBuilder->where('active', 1);
        }
        $inputs = $inputBuilder->orderBy('short_order', 'ASC')->get()->getResultArray();

        foreach ($inputs as $row) {
            $patientDocRawPayload = [
                'p_id' => $patientId,
                'p_doc_id' => $insertId,
                'p_doc_sub_id' => (int) ($row['id'] ?? 0),
                'p_doc_raw_value' => (string) ($row['input_default_value'] ?? ''),
                'update_data' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $patientDocRawPayload = $this->filterPayloadByExistingColumns('patient_doc_raw', $patientDocRawPayload);
            $this->db->table('patient_doc_raw')->insert($patientDocRawPayload);
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

        $patientDocUpdatePayload = [
            'raw_data' => $reportString,
            'update_pre_value' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $patientDocUpdatePayload = $this->filterPayloadByExistingColumns('patient_doc', $patientDocUpdatePayload);
        $this->db->table('patient_doc')->where('id', $docId)->update($patientDocUpdatePayload);

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

        $entryUpdatePayload = [
            'p_doc_raw_value' => $testValue,
            'update_data' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $entryUpdatePayload = $this->filterPayloadByExistingColumns('patient_doc_raw', $entryUpdatePayload);
        $this->db->table('patient_doc_raw')->where('id', $testId)->update($entryUpdatePayload);

        return $this->response->setBody($testValue);
    }

    public function update_doc_field(int $patientDocId)
    {
        if ($resp = $this->ensureAccess()) {
            return $resp;
        }
        try {
            if (! $this->db->tableExists('patient_doc') || ! $this->db->tableExists('patient_doc_raw')) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Document tables not available',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $pending = 0;
            if ($this->hasColumn('patient_doc_raw', 'update_data')) {
                $pending = $this->db->table('patient_doc_raw')
                    ->where('p_doc_id', $patientDocId)
                    ->where('update_data', 0)
                    ->countAllResults();
            }

            if ($pending > 0) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Some field pending',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $patientDoc = $this->db->table('patient_doc')->where('id', $patientDocId)->get(1)->getRowArray();
            if (! is_array($patientDoc)) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Document not found',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

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

            $updatePayload = [
                'raw_data' => $report,
                'update_pre_value' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $updatePayload = $this->filterPayloadByExistingColumns('patient_doc', $updatePayload);

            if (empty($updatePayload)) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Unable to compile due to schema mismatch',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $this->db->table('patient_doc')->where('id', $patientDocId)->update($updatePayload);

            try {
                $this->enqueueHealthDocumentFhirSync($patientDocId);
            } catch (\Throwable $e) {
                log_message('warning', 'Unable to enqueue ABDM HealthDocumentRecord FHIR sync for patient_doc {id}: {msg}', [
                    'id' => $patientDocId,
                    'msg' => $e->getMessage(),
                ]);
            }

            return $this->response->setJSON([
                'update' => 1,
                'error_text' => 'Compile done',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DoctorDocument update_doc_field failed for doc_id {doc_id}: {message}', [
                'doc_id' => $patientDocId,
                'message' => $e->getMessage(),
            ]);

            return $this->response->setStatusCode(500)->setJSON([
                'update' => 0,
                'error_text' => 'Compile failed due to server error',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
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

        $rawData = (string) ($patientDoc['raw_data'] ?? '');
        $rawData = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $rawData);
        $rawData = $this->resolveDocPrintTemplatePlaceholders($rawData, $patientDoc);
        $content .= $rawData;

        $customHeaderHtml = $this->resolveDocPrintTemplatePlaceholders(
            (string) ($selectedPrintTemplate['header_html'] ?? ''),
            $patientDoc
        );
        $customFooterHtml = $this->resolveDocPrintTemplatePlaceholders(
            (string) ($selectedPrintTemplate['footer_html'] ?? ''),
            $patientDoc
        );

        $hospitalLogoSrc = $this->resolveHospitalLogoDataUri();

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
            'custom_header_html' => $customHeaderHtml,
            'custom_footer_html' => $customFooterHtml,
            'has_selected_print_template' => is_array($selectedPrintTemplate),
            'hospital_logo_src' => $hospitalLogoSrc,
        ];

        $mpdfTempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
        if (! is_dir($mpdfTempDir)) {
            mkdir($mpdfTempDir, 0755, true);
        }

        // FreeSans (GNU FreeFont) ships with mPDF and covers Devanagari (Hindi) Unicode glyphs.
        $mpdfFontDir = realpath(__DIR__ . '/../../vendor/mpdf/mpdf/ttfonts');
        $mpdfFontDirs = $mpdfFontDir !== false ? [$mpdfFontDir] : [];

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
                'default_font' => 'freesans',
                'fontDir' => $mpdfFontDirs,
                'fontdata' => [
                    'freesans' => [
                        'R' => 'FreeSans.ttf',
                        'B' => 'FreeSansBold.ttf',
                        'I' => 'FreeSansOblique.ttf',
                        'BI' => 'FreeSansBoldOblique.ttf',
                        'useOTL' => 0xFF,
                        'useKashida' => 75,
                    ],
                    'freeserif' => [
                        'R' => 'FreeSerif.ttf',
                        'B' => 'FreeSerifBold.ttf',
                        'I' => 'FreeSerifItalic.ttf',
                        'BI' => 'FreeSerifBoldItalic.ttf',
                        'useOTL' => 0xFF,
                        'useKashida' => 75,
                    ],
                ],
                'autoScriptToLang' => true,
                'autoLanguageDetection' => true,
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
        $html = mpdf_normalize_font_weight_css($html);

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
            $this->enqueueHealthDocumentFhirSync($patientDocId);
        } catch (\Throwable $e) {
            log_message('error', '[DoctorDocument::create_final] Output or FHIR sync error: {msg}', ['msg' => $e->getMessage()]);
            if (! isset($pdfBytes) || $pdfBytes === '') {
                return $this->response
                    ->setStatusCode(500)
                    ->setHeader('Content-Type', 'text/plain')
                    ->setBody('PDF output failed: ' . $e->getMessage());
            }
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

    private function getHospitalSettingValue(string $key): string
    {
        if (function_exists('hospital_setting_value')) {
            $val = hospital_setting_value($key, '');
            if ($val !== '') {
                return $val;
            }
        }

        if (defined($key)) {
            return (string) constant($key);
        }

        return '';
    }

    public function buildHealthDocumentSource(int $patientDocId, string $entityType = ''): array
    {
        if ($patientDocId <= 0) {
            return [];
        }

        if ($entityType === 'patient_document') {
            if ($this->db->tableExists('file_upload_data')) {
                $fileRow = $this->db->table('file_upload_data')->where('id', $patientDocId)->get(1)->getRowArray();
                if (is_array($fileRow) && ! empty($fileRow)) {
                    return $this->buildFileUploadHealthDocumentSource($fileRow);
                }
            }
            return [];
        }

        $patientDoc = $this->db->table('patient_doc')->where('id', $patientDocId)->get(1)->getRowArray();
        if (! is_array($patientDoc) || empty($patientDoc)) {
            if ($this->db->tableExists('file_upload_data')) {
                $fileRow = $this->db->table('file_upload_data')->where('id', $patientDocId)->get(1)->getRowArray();
                if (is_array($fileRow) && ! empty($fileRow)) {
                    return $this->buildFileUploadHealthDocumentSource($fileRow);
                }
            }
            return [];
        }

        $patientId = (int) ($patientDoc['p_id'] ?? 0);
        $patientRow = $patientId > 0 ? ($this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? []) : [];
        $doctorRow = [];
        $drId = (int) ($patientDoc['dr_id'] ?? 0);
        if ($drId > 0 && $this->db->tableExists('doctor_master')) {
            $doctorRow = $this->db->table('doctor_master')->where('id', $drId)->get(1)->getRowArray() ?? [];
        }

        $templateRow = [];
        $docFormatId = (int) ($patientDoc['doc_format_id'] ?? 0);
        if ($docFormatId > 0 && $this->db->tableExists('doc_format_master')) {
            $templateRow = $this->db->table('doc_format_master')->where('df_id', $docFormatId)->get(1)->getRowArray() ?? [];
        }

        $docTitle = trim((string) ($templateRow['doc_name'] ?? ''));

        $rawDataStr = trim((string) ($patientDoc['raw_data'] ?? ''));
        $pdfBytes = null;
        $contentType = 'application/pdf';
        $existingDocPath = '';

        // Check if raw_data is a JSON payload describing an uploaded/scanned document (e.g. NABH scan)
        if (str_starts_with($rawDataStr, '{') && str_ends_with($rawDataStr, '}')) {
            $decoded = json_decode($rawDataStr, true);
            if (is_array($decoded)) {
                if (! empty($decoded['title'])) {
                    $docTitle = trim((string) $decoded['title']);
                }
                $fileName = trim((string) ($decoded['file_name'] ?? ''));
                if ($fileName !== '') {
                    $candidates = [
                        FCPATH . 'uploads/nabh_ipd/' . $fileName,
                        FCPATH . 'uploads/' . $fileName,
                        WRITEPATH . 'uploads/nabh_ipd/' . $fileName,
                        defined('ROOTPATH') ? ROOTPATH . 'public/uploads/nabh_ipd/' . $fileName : '',
                    ];
                    foreach ($candidates as $cand) {
                        if ($cand !== '' && is_file($cand) && is_readable($cand)) {
                            $existingDocPath = $cand;
                            break;
                        }
                    }
                }
            }
        }

        // Also check nabh_ipd_scans table if present
        if ($existingDocPath === '' && $this->db->tableExists('nabh_ipd_scans')) {
            $scanRow = $this->db->table('nabh_ipd_scans')
                ->where('patient_doc_id', $patientDocId)
                ->get(1)
                ->getRowArray();
            if (is_array($scanRow) && ! empty($scanRow['file_name'])) {
                if (! empty($scanRow['category_title'])) {
                    $docTitle = trim((string) $scanRow['category_title']);
                }
                $cand = FCPATH . 'uploads/nabh_ipd/' . trim((string) $scanRow['file_name']);
                if (is_file($cand) && is_readable($cand)) {
                    $existingDocPath = $cand;
                }
            }
        }

        if ($existingDocPath !== '') {
            $pdfBytes = @file_get_contents($existingDocPath);
            $ext = strtolower(pathinfo($existingDocPath, PATHINFO_EXTENSION));
            $contentType = match ($ext) {
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'application/pdf',
            };
        } elseif ($docFormatId > 0 && ! empty($rawDataStr)) {
            // It's a formatted certificate / letterhead document
            $pdfBytes = $this->generatePatientDocPdfBytes($patientDocId);
        }

        if ($docTitle === '') {
            $docTitle = 'Medical Certificate';
        }

        $patientName = trim(trim((string) ($patientRow['p_fname'] ?? '')) . ' ' . trim((string) ($patientRow['p_lname'] ?? '')));
        $abhaIdRaw = '';
        foreach (['abha_id', 'abha_no', 'abha'] as $field) {
            $candidate = trim((string) ($patientRow[$field] ?? ''));
            if ($candidate !== '') {
                $abhaIdRaw = $candidate;
                break;
            }
        }
        $abhaDigits = preg_replace('/\D/', '', $abhaIdRaw);
        $abhaDigits = is_string($abhaDigits) ? $abhaDigits : '';
        $abhaAddress = trim((string) ($patientRow['abha_address'] ?? ''));

        $issueDateRaw = trim((string) ($patientDoc['date_issue'] ?? $patientDoc['created_at'] ?? date('Y-m-d')));
        $issueIso = date(DATE_ATOM, strtotime($issueDateRaw) ?: time());
        $visitDate = date('Y-m-d', strtotime($issueDateRaw) ?: time());

        $genderRaw = trim((string) ($patientRow['gender'] ?? $patientRow['xgender'] ?? ''));
        $hfrId = trim($this->getHospitalSettingValue('ABDM_HFR_ID'));
        if ($hfrId === '') {
            $hfrId = 'HFR-IN-HMS';
        }
        $hospitalName = $this->getHospitalSettingValue('H_Name');
        if ($hospitalName === '' && defined('H_Name')) {
            $hospitalName = (string) constant('H_Name');
        }

        $doctorName = trim(trim((string) ($doctorRow['p_fname'] ?? '')) . ' ' . trim((string) ($doctorRow['p_lname'] ?? '')));
        if ($doctorName === '') {
            $doctorName = 'Doctor';
        }

        $docDataBase64 = ($pdfBytes !== null && $pdfBytes !== '') ? base64_encode($pdfBytes) : '';

        if ($docDataBase64 === '') {
            $rawHtml = trim((string) ($patientDoc['raw_data'] ?? ''));
            if ($rawHtml !== '') {
                $docDataBase64 = base64_encode('<div xmlns="http://www.w3.org/1999/xhtml">' . $rawHtml . '</div>');
                $contentType = 'text/html';
            } else {
                $docDataBase64 = base64_encode('<div xmlns="http://www.w3.org/1999/xhtml"><h3>' . esc($docTitle) . '</h3><p>Patient Document #' . $patientDocId . '</p></div>');
                $contentType = 'text/html';
            }
        }

        $encounterId = 'encounter-' . $patientDocId;
        $encounter = [
            'id' => $encounterId,
            'status' => 'finished',
            'class' => 'AMB',
            'start' => $visitDate . 'T09:00:00+05:30',
            'end' => $visitDate . 'T09:30:00+05:30',
            'period' => [
                'start' => $visitDate . 'T09:00:00+05:30',
                'end' => $visitDate . 'T09:30:00+05:30',
            ],
            'hospitalization' => false,
        ];

        return [
            'record_id' => (string) $patientDocId,
            'session_id' => (string) $patientDocId,
            'visit_date' => $visitDate,
            'completed_at' => $issueIso,
            'document_title' => $docTitle,
            'document_data_base64' => $docDataBase64,
            'content_type' => $contentType,
            'document_content_html' => (string) ($patientDoc['raw_data'] ?? ''),
            'doctor_name' => $doctorName,
            'hfr_id' => $hfrId,
            'organization' => [
                'id' => $hfrId,
                'name' => $hospitalName,
            ],
            'encounter' => $encounter,
            'encounter_reference' => 'urn:uuid:' . $encounterId,
            'patient' => [
                'id' => (string) $patientId,
                'uhid' => (string) ($patientRow['uhid_no'] ?? $patientRow['uhid'] ?? $patientRow['patient_code'] ?? $patientId),
                'name' => $patientName,
                'gender' => strtolower($genderRaw) === 'm' ? 'male' : (strtolower($genderRaw) === 'f' ? 'female' : 'unknown'),
                'dob' => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
                'abha_id' => $abhaDigits,
                'abha_address' => $abhaAddress,
                'mobile' => (string) ($patientRow['mphone1'] ?? ''),
            ],
            'practitioner' => [
                'id' => (string) $drId,
                'name' => $doctorName,
            ],
        ];
    }

    private function generatePatientDocPdfBytes(int $patientDocId): ?string
    {
        if ($patientDocId <= 0) {
            return null;
        }

        try {
            $this->ensurePrintTemplateColumns();
            $this->ensureDocumentPrintTemplateTable();

            $patientDoc = $this->db->table('patient_doc pd')
                ->select('pd.*,dm.doc_name,dm.doc_desc,dm.default_print_type,dm.print_top_margin,dm.print_bottom_margin,dm.print_left_margin,dm.print_right_margin,dm.print_header_margin,dm.print_footer_margin,p.p_fname,p.p_relative,p.p_rname,p.p_code,p.gender,p.age,p.age_in_month,p.estimate_dob,p.dob,dr.p_fname as dr_name')
                ->join('doc_format_master dm', 'pd.doc_format_id=dm.df_id', 'left')
                ->join('patient_master p', 'pd.p_id=p.id', 'left')
                ->join('doctor_master dr', 'pd.dr_id=dr.id', 'left')
                ->where('pd.id', $patientDocId)
                ->get(1)
                ->getRowArray();

            if (! is_array($patientDoc)) {
                return null;
            }

            $selectedPrintTemplate = null;
            if ($this->db->tableExists('doc_print_templates')) {
                $selectedPrintTemplate = $this->db->table('doc_print_templates')
                    ->where('status', 1)
                    ->where('is_default', 1)
                    ->orderBy('id', 'DESC')
                    ->get(1)
                    ->getRowArray();
            }

            $resolvedPrintType = is_array($selectedPrintTemplate)
                ? (int) ($selectedPrintTemplate['print_on_type'] ?? 1)
                : (int) ($patientDoc['default_print_type'] ?? 0);
            $resolvedPrintType = ((int) $resolvedPrintType === 1) ? 1 : 0;

            if (is_array($selectedPrintTemplate)) {
                $printTopMargin = $this->normalizeMarginValue($selectedPrintTemplate['page_margin_top_cm'] ?? null, 6.10);
                $printBottomMargin = $this->normalizeMarginValue($selectedPrintTemplate['page_margin_bottom_cm'] ?? null, 2.50);
                $printLeftMargin = $this->normalizeMarginValue($selectedPrintTemplate['page_margin_left_cm'] ?? null, 0.70);
                $printRightMargin = $this->normalizeMarginValue($selectedPrintTemplate['page_margin_right_cm'] ?? null, 0.70);
                $printHeaderMargin = $this->normalizeMarginValue($selectedPrintTemplate['margin_header_cm'] ?? null, 0.50);
                $printFooterMargin = $this->normalizeMarginValue($selectedPrintTemplate['margin_footer_cm'] ?? null, 1.50);
                $pageSize = trim((string) ($selectedPrintTemplate['page_size'] ?? 'A4')) ?: 'A4';
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

            $rawData = (string) ($patientDoc['raw_data'] ?? '');
            $rawData = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $rawData);
            $rawData = $this->resolveDocPrintTemplatePlaceholders($rawData, $patientDoc);
            $content .= $rawData;

            $customHeaderHtml = $this->resolveDocPrintTemplatePlaceholders((string) ($selectedPrintTemplate['header_html'] ?? ''), $patientDoc);
            $customFooterHtml = $this->resolveDocPrintTemplatePlaceholders((string) ($selectedPrintTemplate['footer_html'] ?? ''), $patientDoc);

            $hospitalLogoSrc = $this->resolveHospitalLogoDataUri();

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
                'custom_header_html' => $customHeaderHtml,
                'custom_footer_html' => $customFooterHtml,
                'has_selected_print_template' => is_array($selectedPrintTemplate),
                'hospital_logo_src' => $hospitalLogoSrc,
            ];

            $mpdfTempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
            if (! is_dir($mpdfTempDir)) {
                mkdir($mpdfTempDir, 0755, true);
            }

            $mpdfFontDir = realpath(__DIR__ . '/../../vendor/mpdf/mpdf/ttfonts');
            $mpdfFontDirs = $mpdfFontDir !== false ? [$mpdfFontDir] : [];

            $mpdf = new Mpdf([
                'format' => $pageSize,
                'margin_top' => $printTopMargin * 10,
                'margin_bottom' => $printBottomMargin * 10,
                'margin_left' => $printLeftMargin * 10,
                'margin_right' => $printRightMargin * 10,
                'margin_header' => $printHeaderMargin * 10,
                'margin_footer' => $printFooterMargin * 10,
                'tempDir' => $mpdfTempDir,
                'default_font' => 'freesans',
                'fontDir' => $mpdfFontDirs,
                'fontdata' => [
                    'freesans' => [
                        'R' => 'FreeSans.ttf',
                        'B' => 'FreeSansBold.ttf',
                        'I' => 'FreeSansOblique.ttf',
                        'BI' => 'FreeSansBoldOblique.ttf',
                        'useOTL' => 0xFF,
                        'useKashida' => 75,
                    ],
                ],
                'autoScriptToLang' => true,
                'autoLanguageDetection' => true,
            ]);

            if ($resolvedPrintType === 1) {
                $hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
                $mpdf->SetWatermarkText($hospitalName, 0.1);
                $mpdf->showWatermarkText = true;
            }

            $html = view('doctor_document/doc_letterhead_print', $data);
            $html = mpdf_normalize_font_weight_css($html);
            $mpdf->WriteHTML($html, HTMLParserMode::DEFAULT_MODE);

            return $mpdf->Output('', 'S');
        } catch (\Throwable $e) {
            log_message('warning', 'Unable to generate PDF for patient_doc {id}: {msg}', ['id' => $patientDocId, 'msg' => $e->getMessage()]);
            return null;
        }
    }

    public function buildFileUploadHealthDocumentSource(array $fileRow): array
    {
        $fileUploadId = (int) ($fileRow['id'] ?? 0);
        $pid = (int) ($fileRow['pid'] ?? 0);
        if ($pid <= 0 && ! empty($fileRow['opd_id']) && $this->db->tableExists('opd_master')) {
            $opdRow = $this->db->table('opd_master')->where('opd_id', (int) $fileRow['opd_id'])->get(1)->getRowArray();
            $pid = (int) ($opdRow['p_id'] ?? 0);
        }
        if ($pid <= 0) {
            return [];
        }

        $patientRow = $this->db->table('patient_master')->where('id', $pid)->get(1)->getRowArray();
        if (empty($patientRow)) {
            return [];
        }

        $filePath = $this->resolveFileUploadPath($fileRow);

        $bytes = '';
        $mimeType = 'application/pdf';
        if ($filePath !== '' && is_file($filePath) && is_readable($filePath)) {
            $bytes = (string) @file_get_contents($filePath);
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = match ($ext) {
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'jpg', 'jpeg' => 'image/jpeg',
                default => (string) (mime_content_type($filePath) ?: 'application/octet-stream'),
            };
        }

        $docTitle = trim((string) ($fileRow['document_type'] ?? $fileRow['scan_type'] ?? $fileRow['content_description'] ?? ''));
        if ($docTitle === '' || strcasecmp($docTitle, 'Queued for AI analysis') === 0) {
            $docTitle = 'Patient Scanned Document';
        }

        if ($bytes === '') {
            $bytes = '<div xmlns="http://www.w3.org/1999/xhtml"><h3>' . esc($docTitle) . '</h3><p>Uploaded Document ID: ' . $fileUploadId . '</p></div>';
            $mimeType = 'text/html';
        }

        $patientName = trim(trim((string) ($patientRow['p_fname'] ?? '')) . ' ' . trim((string) ($patientRow['p_lname'] ?? '')));
        $abhaIdRaw = '';
        foreach (['abha_id', 'abha_no', 'abha'] as $field) {
            $candidate = trim((string) ($patientRow[$field] ?? ''));
            if ($candidate !== '') {
                $abhaIdRaw = $candidate;
                break;
            }
        }
        $abhaDigits = preg_replace('/\D/', '', $abhaIdRaw);
        $abhaDigits = is_string($abhaDigits) ? $abhaDigits : '';
        $abhaAddress = trim((string) ($patientRow['abha_address'] ?? ''));

        $hfrId = trim($this->getHospitalSettingValue('ABDM_HFR_ID'));
        if ($hfrId === '') {
            $hfrId = 'HFR-IN-HMS';
        }
        $hospitalName = $this->getHospitalSettingValue('H_Name');
        if ($hospitalName === '' && defined('H_Name')) {
            $hospitalName = (string) constant('H_Name');
        }

        $visitDate = ! empty($fileRow['insert_date']) ? date('Y-m-d', strtotime((string) $fileRow['insert_date'])) : date('Y-m-d');
        $completedAt = ! empty($fileRow['insert_date']) ? date(DATE_ATOM, strtotime((string) $fileRow['insert_date'])) : date(DATE_ATOM);

        $drId = (int) ($fileRow['doc_id'] ?? $fileRow['upload_by_id'] ?? 1);
        $drName = 'Doctor';
        if ($drId > 0 && $this->db->tableExists('doctor_master')) {
            $docRow = $this->db->table('doctor_master')->where('id', $drId)->get(1)->getRowArray();
            if (is_array($docRow)) {
                $drName = trim(trim((string) ($docRow['p_fname'] ?? '')) . ' ' . trim((string) ($docRow['p_lname'] ?? '')));
            }
        }
        if ($drName === '' || $drName === 'Doctor') {
            $drName = 'Medical Officer';
        }

        $encounterId = 'encounter-file-' . $fileUploadId;
        $encounter = [
            'id' => $encounterId,
            'status' => 'finished',
            'class' => 'AMB',
            'start' => $visitDate . 'T09:00:00+05:30',
            'end' => $visitDate . 'T09:30:00+05:30',
            'period' => [
                'start' => $visitDate . 'T09:00:00+05:30',
                'end' => $visitDate . 'T09:30:00+05:30',
            ],
            'hospitalization' => false,
        ];

        return [
            'record_id' => 'file-' . $fileUploadId,
            'session_id' => (string) ($fileRow['opd_id'] ?? $fileUploadId),
            'visit_date' => $visitDate,
            'completed_at' => $completedAt,
            'document_title' => $docTitle,
            'document_data_base64' => $bytes !== '' ? base64_encode($bytes) : '',
            'content_type' => $mimeType,
            'doctor_name' => $drName,
            'hfr_id' => $hfrId,
            'organization' => [
                'id' => $hfrId,
                'name' => $hospitalName,
            ],
            'encounter' => $encounter,
            'encounter_reference' => 'urn:uuid:' . $encounterId,
            'patient' => [
                'id' => (string) $pid,
                'uhid' => (string) ($patientRow['uhid_no'] ?? $patientRow['uhid'] ?? $patientRow['patient_code'] ?? $pid),
                'name' => $patientName,
                'gender' => strtolower((string) ($patientRow['gender'] ?? '')) === 'm' ? 'male' : (strtolower((string) ($patientRow['gender'] ?? '')) === 'f' ? 'female' : 'unknown'),
                'dob' => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
                'abha_id' => $abhaDigits,
                'abha_address' => $abhaAddress,
                'mobile' => (string) ($patientRow['mphone1'] ?? ''),
            ],
            'practitioner' => [
                'id' => (string) ($drId > 0 ? $drId : 1),
                'name' => $drName,
            ],
        ];
    }

    private function enqueueHealthDocumentFhirSync(int $patientDocId): void
    {
        if ($patientDocId <= 0) {
            return;
        }

        $source = $this->buildHealthDocumentSource($patientDocId);
        if (empty($source)) {
            return;
        }

        $hfrId = (string) ($source['hfr_id'] ?? 'HFR-IN-HMS');
        $patientId = (int) ($source['patient']['id'] ?? 0);
        $patientName = (string) ($source['patient']['name'] ?? '');
        $abhaDigits = (string) ($source['patient']['abha_id'] ?? '');
        $abhaAddress = (string) ($source['patient']['abha_address'] ?? '');
        $docTitle = (string) ($source['document_title'] ?? 'Medical Certificate');
        $visitDate = (string) ($source['visit_date'] ?? date('Y-m-d'));
        $doctorName = (string) ($source['doctor_name'] ?? '');

        if ($patientId > 0 && preg_match('/^\d{14}$/', $abhaDigits) === 1 && class_exists('\App\Libraries\Abdm\Task\AbdmTaskService')) {
            try {
                $taskService = new \App\Libraries\Abdm\Task\AbdmTaskService();
                $taskService->createOrRefreshTask(
                    'health_document_publish',
                    'patient_doc',
                    'doctor_document',
                    (string) $patientDocId,
                    $patientId,
                    $patientName,
                    $abhaDigits,
                    'submit',
                    [
                        'patient_doc_id' => $patientDocId,
                        'trigger' => 'patient_doc.compiled',
                    ]
                );
            } catch (\Throwable $e) {
                log_message('warning', 'Unable to refresh task for patient_doc {id}: {msg}', ['id' => $patientDocId, 'msg' => $e->getMessage()]);
            }
        }

        $factory = new \App\Libraries\Abdm\Fhir\FhirGeneratorFactory();
        $generatorOutput = $factory->healthDocument()->generate($source);

        $adapter = new \App\Libraries\Abdm\Fhir\Support\GatewayPayloadAdapter();
        $gatewayPayload = $adapter->toGatewayPayload($generatorOutput, $source, $hfrId);

        $syncPayload = [
            'local_record_id' => 'patient-doc-' . $patientDocId,
            'local_patient_id' => $patientId,
            'hi_type' => 'HealthDocumentRecord',
            'care_context_reference' => (string) ($gatewayPayload['care_context_reference'] ?? ('DOC-' . $patientDocId)),
            'care_context_display' => (string) ($gatewayPayload['care_context_display'] ?? ($docTitle . ' ' . $visitDate)),
            'visit_date' => $visitDate,
            'department' => '',
            'doctor_name' => $doctorName,
            'consent_id' => '',
            'hfr_id' => $hfrId,
            'source_updated_at' => date('Y-m-d H:i:s'),
            'patient_name' => $patientName,
            'mobile' => (string) ($source['patient']['mobile'] ?? ''),
            'gender' => (string) ($source['patient']['gender'] ?? ''),
            'dob' => (string) ($source['patient']['dob'] ?? ''),
            'abha_id' => $abhaDigits,
            'abha_address' => $abhaAddress,
            'fhir_bundle' => (array) ($gatewayPayload['fhir_bundle'] ?? []),
        ];

        $outbox = new \App\Libraries\Abdm\Sync\AbdmSyncOutboxService();
        $outbox->enqueueRecordSync($syncPayload);
    }

    private function resolveFileUploadPath(array $fileRow): string
    {
        $fileName = trim((string) ($fileRow['file_name'] ?? $fileRow['orig_name'] ?? ''));
        $fullPath = trim((string) ($fileRow['full_path'] ?? ''));
        $dirPath = trim((string) ($fileRow['file_path'] ?? ''));

        $candidates = [];
        if ($fullPath !== '') {
            $candidates[] = $fullPath;
            $candidates[] = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
        }
        if ($dirPath !== '' && $fileName !== '') {
            $candidates[] = rtrim($dirPath, '/\\') . DIRECTORY_SEPARATOR . $fileName;
        }

        $baseDirs = [
            defined('FCPATH') ? FCPATH : '',
            defined('FCPATH') ? FCPATH . 'uploads' : '',
            defined('ROOTPATH') ? ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' : '',
        ];

        $insertDate = ! empty($fileRow['insert_date']) ? (string) $fileRow['insert_date'] : '';
        $dateSubdir = $insertDate !== '' ? date('Ymd', strtotime($insertDate)) : '';

        if ($fileName !== '') {
            foreach ($baseDirs as $base) {
                if ($base === '') continue;
                if ($dateSubdir !== '') {
                    $candidates[] = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $dateSubdir . DIRECTORY_SEPARATOR . $fileName;
                }
                $candidates[] = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $fileName;
            }
        }

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        if ($fileName !== '' && defined('FCPATH')) {
            $matches = glob(rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . $fileName);
            if (! empty($matches) && is_file($matches[0]) && is_readable($matches[0])) {
                return $matches[0];
            }
        }

        return '';
    }
}
