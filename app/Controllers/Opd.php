<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OpdModel;
use App\Models\PaymentModel;
use CodeIgniter\I18n\Time;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

class Opd extends BaseController
{
    private const OPD_PAPER_SETTING_KEY = 'OPD_PAPER_PRINT_SETTINGS';
    private const OPD_PAPER_SETTING_KEY_PREFIX = 'OPD_PAPER_PS__';
    private const OPD_PAPER_SETTING_KEY_PREFIX_LEGACY = 'OPD_PAPER_PRINT_SETTINGS__';
    private const OPD_TEMPLATE_KEY_PREFIX = 'OPD_PRINT_TEMPLATE__';

    /**
     * @var array<int, string>
     */
    private array $scanAiDebug = [];

    private function canAccessDoctorWorkPanel(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'can') && $user->can('opd.doctor-panel.access')) {
            return true;
        }

        return false;
    }

    private function canManageOpdPrintTemplates(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'can') && ($user->can('template.opd_print') || $user->can('billing.opd.edit'))) {
            return true;
        }

        if (method_exists($user, 'inGroup') && $user->inGroup('OPDEdit')) {
            return true;
        }

        return false;
    }

    public function print_template_builder()
    {
        if (! $this->canManageOpdPrintTemplates()) {
            return $this->response->setStatusCode(403)->setBody('Access denied: OPD print template builder is restricted.');
        }

        $mode = strtolower(trim((string) $this->request->getGet('mode')));
        if (!in_array($mode, ['list', 'edit'], true)) {
            $mode = 'list';
        }

        $templateNames = $this->collectOpdTemplateNames();
        $name = $this->normalizeOpdTemplateId((string) $this->request->getGet('name'));

        if ($mode === 'list') {
            return view('billing/opd_template_builder_list', [
                'template_names' => $templateNames,
            ]);
        }

        if ($name === '') {
            $name = !empty($templateNames) ? (string) $templateNames[0] : 'default';
        }

        if (!in_array($name, $templateNames, true)) {
            $templateNames[] = $name;
            $templateNames = array_values(array_unique($templateNames));
            sort($templateNames);
        }

        $content = $this->readOpdTemplateContentByName($name);

        $placeholders = [
            'hospital_name', 'hospital_address', 'hospital_phone', 'hospital_email', 'doctor_name', 'print_time',
            'pName', 'pRelative', 'age_sex', 'phoneno', 'p_address', 'uhid_no', 'opd_sr_no', 'opd_no',
            'opd_date', 'exp_date', 'SpecName', 'opd_fee_desc', 'total_no_visit', 'last_opdvisit_date', 'str_opd_book_date',
            'Complaint', 'diagnosis', 'Provisional_diagnosis', 'Finding_Examinations', 'medical', 'investigation',
            'Prescriber_Remarks', 'advice', 'next_visit', 'refer_to', 'vital_content', 'painscale_img',
            'hospital_section', 'patient_section', 'content_section',
        ];

        return view('billing/opd_template_builder', [
            'selected_name' => $name,
            'template_content' => $content,
            'template_names' => $templateNames,
            'placeholders' => $placeholders,
            'paper_settings' => $this->getOpdPaperPrintSetting($name),
        ]);
    }

    /**
     * @return array<int,string>
     */
    private function collectOpdTemplateNames(): array
    {
        return $this->collectStoredOpdTemplateNames();
    }

    private function readOpdTemplateContentByName(string $name): string
    {
        $name = $this->normalizeOpdTemplateId($name);
        if ($name === '') {
            return '';
        }

        return $this->readStoredOpdTemplateContent($name);
    }

    public function print_template_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageOpdPrintTemplates()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $allowedSections = ['full', 'layout', 'hospital', 'patient', 'content'];
        $section = strtolower(trim((string) $this->request->getPost('section')));
        if (!in_array($section, $allowedSections, true)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid section']);
        }

        $name = $this->normalizeOpdTemplateId((string) $this->request->getPost('name'));
        if ($name === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template name required']);
        }

        $content = (string) $this->request->getPost('content');
        if (trim($content) === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template content required']);
        }

        if (! $this->saveStoredOpdTemplateContent($name, $content)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to save template']);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Template saved',
            'section' => $section,
            'name' => $name,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function print_template_rename()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageOpdPrintTemplates()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $oldName = $this->normalizeOpdTemplateId((string) $this->request->getPost('old_name'));
        $newName = $this->normalizeOpdTemplateId((string) $this->request->getPost('new_name'));

        if ($oldName === '' || $newName === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Both old and new template names are required']);
        }
        if ($oldName === $newName) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'New template name must be different']);
        }

        if (! $this->renameStoredOpdTemplateContent($oldName, $newName)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template not found or rename failed']);
        }

        $this->renameOpdPaperSettingStorageKey($oldName, $newName);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Template renamed',
            'old_name' => $oldName,
            'new_name' => $newName,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function renameOpdPaperSettingStorageKey(string $oldName, string $newName): void
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return;
        }

        $newKey = $this->getOpdPaperSettingStorageKey($newName);
        $oldKeys = $this->getOpdPaperSettingCandidateKeys($oldName);
        if (empty($oldKeys)) {
            return;
        }

        $existsNew = $this->db->table('hospital_setting')
            ->select('id')
            ->where('s_name', $newKey)
            ->get(1)
            ->getRowArray();

        // If target key exists, just clean old aliases.
        if (! empty($existsNew['id'])) {
            foreach ($oldKeys as $oldKey) {
                if ($oldKey === $newKey) {
                    continue;
                }
                $this->db->table('hospital_setting')->where('s_name', $oldKey)->delete();
            }

            return;
        }

        foreach ($oldKeys as $oldKey) {
            if ($oldKey === $newKey) {
                continue;
            }

            $row = $this->db->table('hospital_setting')
                ->select('id,s_value')
                ->where('s_name', $oldKey)
                ->get(1)
                ->getRowArray();

            if (empty($row)) {
                continue;
            }

            $this->db->table('hospital_setting')->insert([
                's_name' => $newKey,
                's_value' => (string) ($row['s_value'] ?? ''),
            ]);

            $this->db->table('hospital_setting')->where('id', (int) ($row['id'] ?? 0))->delete();
            break;
        }
    }

    public function print_template_delete()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageOpdPrintTemplates()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $templateName = $this->normalizeOpdTemplateId((string) $this->request->getPost('template_name'));

        if ($templateName === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template name is required']);
        }

        if (! $this->deleteStoredOpdTemplateContent($templateName)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template not found or delete failed']);
        }

        $this->deleteOpdPaperSettingStorageKey($templateName);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Template deleted',
            'template_name' => $templateName,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function deleteOpdPaperSettingStorageKey(string $templateName): void
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return;
        }

        $keys = $this->getOpdPaperSettingCandidateKeys($templateName);
        foreach ($keys as $key) {
            $this->db->table('hospital_setting')->where('s_name', $key)->delete();
        }
    }

    private function normalizeOpdTemplateId(string $templateId): string
    {
        $templateId = strtolower(trim($templateId));
        $templateId = str_replace([' ', '.'], '_', $templateId);
        $templateId = preg_replace('/[^a-z0-9_\-]+/', '_', $templateId) ?? '';
        $templateId = preg_replace('/_{2,}/', '_', $templateId) ?? $templateId;

        return trim($templateId, '_-');
    }

    private function getOpdTemplateStorageKey(string $templateId): string
    {
        $templateId = $this->normalizeOpdTemplateId($templateId);
        if ($templateId === '') {
            return '';
        }

        return self::OPD_TEMPLATE_KEY_PREFIX . $templateId;
    }

    private function readStoredOpdTemplateContent(string $templateId): string
    {
        $storageKey = $this->getOpdTemplateStorageKey($templateId);
        if ($storageKey === '' || ! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $storageKey)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    /**
     * @return array<int, string>
     */
    private function collectStoredOpdTemplateNames(): array
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return [];
        }

        $rows = $this->db->table('hospital_setting')
            ->select('s_name')
            ->like('s_name', self::OPD_TEMPLATE_KEY_PREFIX, 'after')
            ->get()
            ->getResultArray();

        $names = [];
        foreach ($rows as $row) {
            $storageKey = (string) ($row['s_name'] ?? '');
            if (! str_starts_with($storageKey, self::OPD_TEMPLATE_KEY_PREFIX)) {
                continue;
            }

            $templateId = substr($storageKey, strlen(self::OPD_TEMPLATE_KEY_PREFIX));
            $templateId = $this->normalizeOpdTemplateId((string) $templateId);
            if ($templateId !== '') {
                $names[] = $templateId;
            }
        }

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    private function saveStoredOpdTemplateContent(string $templateId, string $content): bool
    {
        $storageKey = $this->getOpdTemplateStorageKey($templateId);
        if ($storageKey === '' || ! $this->db->tableExists('hospital_setting')) {
            return false;
        }

        $this->ensureHospitalSettingValueCanStoreLargePayload();

        $existing = $this->db->table('hospital_setting')
            ->select('id')
            ->where('s_name', $storageKey)
            ->get(1)
            ->getRowArray();

        if (! empty($existing['id'])) {
            return (bool) $this->db->table('hospital_setting')
                ->where('id', (int) $existing['id'])
                ->update(['s_value' => $content]);
        }

        return (bool) $this->db->table('hospital_setting')->insert([
            's_name' => $storageKey,
            's_value' => $content,
        ]);
    }

    private function renameStoredOpdTemplateContent(string $oldTemplateId, string $newTemplateId): bool
    {
        $oldKey = $this->getOpdTemplateStorageKey($oldTemplateId);
        $newKey = $this->getOpdTemplateStorageKey($newTemplateId);
        if ($oldKey === '' || $newKey === '' || ! $this->db->tableExists('hospital_setting')) {
            return false;
        }

        $existingOld = $this->db->table('hospital_setting')
            ->select('id')
            ->where('s_name', $oldKey)
            ->get(1)
            ->getRowArray();
        if (empty($existingOld['id'])) {
            return false;
        }

        $existingNew = $this->db->table('hospital_setting')
            ->select('id')
            ->where('s_name', $newKey)
            ->get(1)
            ->getRowArray();
        if (! empty($existingNew['id'])) {
            return false;
        }

        return (bool) $this->db->table('hospital_setting')
            ->where('id', (int) $existingOld['id'])
            ->update(['s_name' => $newKey]);
    }

    private function deleteStoredOpdTemplateContent(string $templateId): bool
    {
        $storageKey = $this->getOpdTemplateStorageKey($templateId);
        if ($storageKey === '' || ! $this->db->tableExists('hospital_setting')) {
            return false;
        }

        $row = $this->db->table('hospital_setting')
            ->select('id')
            ->where('s_name', $storageKey)
            ->get(1)
            ->getRowArray();
        if (empty($row['id'])) {
            return false;
        }

        return (bool) $this->db->table('hospital_setting')
            ->where('id', (int) $row['id'])
            ->delete();
    }

    public function paper_print_settings()
    {
        if (! $this->canManageOpdPrintTemplates()) {
            return $this->response->setStatusCode(403)->setBody('Access denied: OPD paper print settings are restricted.');
        }

        $templateName = $this->normalizeOpdTemplateId((string) ($this->request->getPost('template_name') ?? $this->request->getGet('template_name') ?? ''));

        $notice = '';
        $noticeType = 'success';
        $settings = $this->getOpdPaperPrintSetting($templateName);

        if (strtolower($this->request->getMethod()) === 'post') {
            $isReset = (int) ($this->request->getPost('reset') ?? 0) === 1;
            if ($isReset) {
                $cleared = $this->clearOpdPaperPrintSetting($templateName);
                $settings = $this->getOpdPaperPrintSetting($templateName);
                $notice = $cleared ? 'OPD paper print settings reset.' : 'Unable to reset OPD paper print settings.';
                $noticeType = $cleared ? 'success' : 'danger';

                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'status' => $cleared ? 'success' : 'error',
                        'notice' => $notice,
                        'notice_type' => $noticeType,
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                        'settings' => $settings,
                    ]);
                }
            }

            $pageSize = strtoupper(trim((string) ($this->request->getPost('page_size') ?? 'A4')));
            if (! in_array($pageSize, ['A4', 'A4-L', 'A5', 'A6', 'LETTER', 'LEGAL', 'CUSTOM'], true)) {
                $pageSize = 'A4';
            }

            $customWidthMm = (int) $this->normalizeOpdPrintMarginCm($this->request->getPost('custom_width_mm'), 210.0);
            $customHeightMm = (int) $this->normalizeOpdPrintMarginCm($this->request->getPost('custom_height_mm'), 297.0);
            if ($customWidthMm < 20) {
                $customWidthMm = 20;
            }
            if ($customWidthMm > 600) {
                $customWidthMm = 600;
            }
            if ($customHeightMm < 20) {
                $customHeightMm = 20;
            }
            if ($customHeightMm > 1000) {
                $customHeightMm = 1000;
            }

            $settings = [
                'page_size' => $pageSize,
                'custom_width_mm' => $customWidthMm,
                'custom_height_mm' => $customHeightMm,
                'page_margin_top_cm' => $this->normalizeOpdPrintMarginCm($this->request->getPost('page_margin_top_cm'), 6.1),
                'page_margin_bottom_cm' => $this->normalizeOpdPrintMarginCm($this->request->getPost('page_margin_bottom_cm'), 2.5),
                'page_margin_left_cm' => $this->normalizeOpdPrintMarginCm($this->request->getPost('page_margin_left_cm'), 0.7),
                'page_margin_right_cm' => $this->normalizeOpdPrintMarginCm($this->request->getPost('page_margin_right_cm'), 0.7),
                'margin_header_cm' => $this->normalizeOpdPrintMarginCm($this->request->getPost('margin_header_cm'), 0.5),
                'margin_footer_cm' => $this->normalizeOpdPrintMarginCm($this->request->getPost('margin_footer_cm'), 1.5),
                'paper_html_content' => (string) ($this->request->getPost('paper_html_content') ?? ''),
                'header_html' => (string) ($this->request->getPost('header_html') ?? ''),
                'footer_html' => (string) ($this->request->getPost('footer_html') ?? ''),
            ];

            $saved = $this->saveOpdPaperPrintSetting($settings, $templateName);
            if ($saved) {
                $notice = 'OPD paper print settings saved.';
                $noticeType = 'success';
            } else {
                $notice = 'Unable to save OPD paper print settings.';
                $noticeType = 'danger';
            }

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => $saved ? 'success' : 'error',
                    'notice' => $notice,
                    'notice_type' => $noticeType,
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }
        }

        return view('billing/opd_paper_print_settings', [
            'settings' => $settings,
            'notice' => $notice,
            'notice_type' => $noticeType,
        ]);
    }

    private function resolveTemplateSectionDirectory(string $section): string
    {
        $section = strtolower(trim($section));
        if ($section === 'full') {
            return APPPATH . 'Views/billing/opd_templates';
        }

        return APPPATH . 'Views/billing/opd_parts/' . $section;
    }

    private function resetScanAiDebug(): void
    {
        $this->scanAiDebug = [];
    }

    private function addScanAiDebug(string $message): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }
        $this->scanAiDebug[] = $message;
    }

    private function formatScanAiDebug(): string
    {
        if (empty($this->scanAiDebug)) {
            return '';
        }

        return implode(' | ', array_slice($this->scanAiDebug, 0, 6));
    }

    private function normalizeAzureEndpointBase(string $endpoint): string
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') {
            return '';
        }

        if (! str_starts_with(strtolower($endpoint), 'http://') && ! str_starts_with(strtolower($endpoint), 'https://')) {
            $endpoint = 'https://' . ltrim($endpoint, '/');
        }

        $parts = parse_url($endpoint);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return rtrim($endpoint, '/');
        }

        $base = strtolower((string) $parts['scheme']) . '://' . (string) $parts['host'];
        if (! empty($parts['port'])) {
            $base .= ':' . (int) $parts['port'];
        }

        return rtrim($base, '/');
    }

    private function shouldAllowInsecureSslForEndpoint(string $endpoint): bool
    {
        $toggle = strtolower($this->readScanAiSettingValue(
            ['DIAGNOSIS_AI_ALLOW_INSECURE_SSL', 'AI_DIAGNOSIS_AI_ALLOW_INSECURE_SSL', 'APP_DIAGNOSIS_AI_ALLOW_INSECURE_SSL', 'H_DIAGNOSIS_AI_ALLOW_INSECURE_SSL'],
            ['DIAGNOSIS_AI_ALLOW_INSECURE_SSL']
        ));
        if (in_array($toggle, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        $parts = parse_url($endpoint);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $host) === 1) {
                return true;
            }
        }

        if (str_ends_with($host, '.local')) {
            return true;
        }

        return false;
    }

    private function isSslCertificateError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        return str_contains($msg, 'ssl certificate problem')
            || str_contains($msg, 'unable to get local issuer certificate')
            || str_contains($msg, 'curl error 60')
            || str_contains($msg, 'error 60');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAzureCurlOptions(string $endpoint, int $timeout, bool $forceInsecure = false): array
    {
        $options = [
            'timeout' => $timeout,
            'http_errors' => false,
        ];

        if ($forceInsecure || $this->shouldAllowInsecureSslForEndpoint($endpoint)) {
            $options['verify'] = false;
            $options['curl'] = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ];
            $this->addScanAiDebug($forceInsecure ? 'ssl verify disabled after SSL error retry' : 'ssl verify disabled for intranet/local endpoint');
        }

        return $options;
    }

    public function appointment(string $opdDate = '')
    {
        return $this->get_appointment_data($opdDate);
    }

    /**
     * Load OPD doctor dashboard for the selected date.
     */
    public function get_appointment_data(string $opdDate = '')
    {
        if (! $this->canAccessDoctorWorkPanel()) {
            return $this->response->setStatusCode(403)->setBody('Access denied: OPD doctor work panel is restricted.');
        }

        if ($opdDate === '') {
            $opdDate = date('Y-m-d');
        }

        $opdDateSql = $this->db->escape($opdDate);

        $sql = "select
                d.id as doc_id,
                d.p_fname,
                coalesce(ds.Spec, '') as Spec,
                oa.No_opd,
                oa.count_booking,
                oa.count_wait,
                oa.count_visit,
                oa.count_cancel,
                MOD(d.id,5) as color_code
            from (
                select
                    o.doc_id,
                    count(*) as No_opd,
                    sum(case when o.opd_status = 1 and pr.id is null then 1 else 0 end) as count_booking,
                    sum(case when o.opd_status = 1 and pr.id is not null then 1 else 0 end) as count_wait,
                    sum(case when o.opd_status = 2 then 1 else 0 end) as count_visit,
                    sum(case when o.opd_status = 3 then 1 else 0 end) as count_cancel
                from opd_master o
                left join opd_prescription pr on pr.opd_id = o.opd_id
                where o.apointment_date = " . $opdDateSql . "
                group by o.doc_id
            ) oa
            join doctor_master d on d.id = oa.doc_id and d.active = 1
            left join (
                select s.doc_id, group_concat(DISTINCT m.SpecName) as Spec
                from doc_spec s
                join med_spec m on m.id = s.med_spec_id
                group by s.doc_id
            ) ds on ds.doc_id = d.id
            order by d.p_fname";

        $query = $this->db->query($sql);
        $docMaster = $query->getResult();

        $colorMap = [
            '0' => 'bg-warning',
            '1' => 'bg-danger',
            '2' => 'bg-success',
            '3' => 'bg-secondary',
            '4' => 'bg-info',
            '5' => 'bg-primary',
        ];

        return view('billing/opd_appointment_dashboard', [
            'opd_date' => $opdDate,
            'doc_master' => $docMaster,
            'color1' => $colorMap,
        ]);
    }

    /**
     * Load doctor-specific OPD queues split into booked, waiting, visited, and cancelled.
     */
    public function get_appointment_list(int $docId, string $opdDate = '')
    {
        if (! $this->canAccessDoctorWorkPanel()) {
            return $this->response->setStatusCode(403)->setBody('Access denied: OPD doctor work panel is restricted.');
        }

        if ($opdDate === '') {
            $opdDate = date('Y-m-d');
        }

        $docId = (int) $docId;
        $opdDateSql = $this->db->escape($opdDate);

        $sql = "select d.id as doc_id, d.p_fname,
                group_concat(DISTINCT m.SpecName) as Spec
            from doctor_master d
            left join doc_spec s on d.id=s.doc_id
            left join med_spec m on m.id=s.med_spec_id
            where d.id=" . $docId . " and d.active=1
            group by d.id";
        $query = $this->db->query($sql);
        $docMaster = $query->getResult();

        if (empty($docMaster)) {
            return $this->response->setStatusCode(404)->setBody('Doctor not found for selected date.');
        }

        $sql = "select o.opd_id,o.opd_code,o.opd_no,o.opd_status,
                p.id,p.p_code,p.p_fname as P_name,p.p_rname,
                o.opd_fee_desc,o.opd_fee_amount,o.payment_status,
                if(o.payment_mode=4,'Credit to ECHS',if(o.payment_mode=1,'Cash','Pending')) as opd_type,
                if(pr.id is null, 0, 1) as has_prescription,
                if(
                    coalesce(trim(pr.pulse),'')<>''
                    or coalesce(trim(pr.spo2),'')<>''
                    or coalesce(trim(pr.bp),'')<>''
                    or coalesce(trim(pr.diastolic),'')<>''
                    or coalesce(trim(pr.temp),'')<>''
                    or coalesce(trim(pr.rr_min),'')<>''
                    or coalesce(trim(pr.height),'')<>''
                    or coalesce(trim(pr.weight),'')<>''
                    or coalesce(trim(pr.waist),'')<>'',
                    1,0
                ) as has_vitals,
                coalesce(pr.queue_no,0) as queue_no
            from opd_master o
            join patient_master p on o.p_id=p.id
            left join opd_prescription pr on o.opd_id=pr.opd_id
            where o.apointment_date=" . $opdDateSql . " and o.doc_id=" . $docId;
        $query = $this->db->query($sql);
        $allRows = $query->getResult();

        $opdList0 = [];
        $opdList1 = [];
        $opdList2 = [];
        $opdList3 = [];

        foreach ($allRows as $row) {
            $status = (int) ($row->opd_status ?? 0);
            $hasPrescription = (int) ($row->has_prescription ?? 0) === 1;

            if ($status === 1 && ! $hasPrescription) {
                $opdList0[] = $row;
                continue;
            }

            if ($status === 1 && $hasPrescription) {
                $opdList1[] = $row;
                continue;
            }

            if ($status === 2) {
                $opdList2[] = $row;
                continue;
            }

            if ($status === 3) {
                $opdList3[] = $row;
            }
        }

        usort($opdList0, static function ($left, $right): int {
            return strcmp((string) ($right->opd_code ?? ''), (string) ($left->opd_code ?? ''));
        });

        usort($opdList1, static function ($left, $right): int {
            $leftVitals = (int) ($left->has_vitals ?? 0);
            $rightVitals = (int) ($right->has_vitals ?? 0);
            if ($leftVitals !== $rightVitals) {
                return $leftVitals <=> $rightVitals;
            }

            $leftQueue = (int) ($left->queue_no ?? 0);
            $rightQueue = (int) ($right->queue_no ?? 0);
            $leftQueueSort = $leftQueue > 0 ? $leftQueue : 999999;
            $rightQueueSort = $rightQueue > 0 ? $rightQueue : 999999;
            if ($leftQueueSort !== $rightQueueSort) {
                return $leftQueueSort <=> $rightQueueSort;
            }

            return ((int) ($left->opd_id ?? 0)) <=> ((int) ($right->opd_id ?? 0));
        });

        usort($opdList2, static function ($left, $right): int {
            return ((int) ($left->opd_id ?? 0)) <=> ((int) ($right->opd_id ?? 0));
        });

        usort($opdList3, static function ($left, $right): int {
            return ((int) ($left->opd_id ?? 0)) <=> ((int) ($right->opd_id ?? 0));
        });

        $docMaster[0]->No_opd = count($allRows);
        $docMaster[0]->count_booking = count($opdList0);
        $docMaster[0]->count_wait = count($opdList1);
        $docMaster[0]->count_visit = count($opdList2);
        $docMaster[0]->count_cancel = count($opdList3);

        return view('billing/opd_appointment_list', [
            'opd_date' => $opdDate,
            'doc_id' => $docId,
            'doc_master' => $docMaster,
            'opd_list_0' => $opdList0,
            'opd_list_1' => $opdList1,
            'opd_list_2' => $opdList2,
            'opd_list_3' => $opdList3,
        ]);
    }

    /**
     * Update OPD status from queue actions.
     */
    public function opd_status(int $opdId, int $opdStatus)
    {
        if (! $this->canAccessDoctorWorkPanel()) {
            return $this->response->setStatusCode(403)->setBody('Access denied: OPD doctor work panel is restricted.');
        }

        $allowed = [1, 2, 3];
        if (!in_array($opdStatus, $allowed, true)) {
            return $this->response->setStatusCode(400)->setBody('Invalid OPD status');
        }

        $sql = "select * from opd_master where opd_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        if (empty($opdMaster)) {
            return $this->response->setStatusCode(404)->setBody('OPD not found');
        }

        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $userId = $user->id ?? 0;
        $statusRemark = 'Update By:' . $userLabel . '[' . $userId . '] T-' . date('d-m-Y H:i:s');

        $this->db->table('opd_master')
            ->where('opd_id', (int) $opdId)
            ->update([
                'opd_status' => (string) $opdStatus,
                'opd_status_remark' => $statusRemark,
            ]);

        return $this->response->setJSON([
            'update' => 1,
            'opd_id' => (int) $opdId,
            'opd_status' => (int) $opdStatus,
            'message' => 'Status updated',
        ]);
    }

    public function addopd(int $pno, int $orgCaseId = 0)
    {
        $sql = "select *,if(gender=1,'Male','Female') as xgender
            from patient_master where id='" . (int) $pno . "'";
        $query = $this->db->query($sql);
        $data['person_info'] = $query->getResult();
        if (!empty($data['person_info'])) {
            $row = $data['person_info'][0];
            $row->age = get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '');
        }

        $sql = "select d.id, d.p_fname, group_concat(m.SpecName) as SpecName
            from doctor_master d
            join doc_spec s join med_spec m on d.id = s.doc_id and s.med_spec_id = m.id
            where d.active = 1
            group by d.id";
        $query = $this->db->query($sql);
        $data['doc_spec_l'] = $query->getResult();

        $data['org_case_id'] = $orgCaseId;
        if ($orgCaseId > 0) {
            $sql = "select * from organization_case_master where id=" . (int) $orgCaseId;
            $query = $this->db->query($sql);
            $data['org_case_master'] = $query->getResult();
        }

        return view('billing/opd_appointment_V', $data);
    }

    public function showfee()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'content' => '',
                'error_text' => 'Invalid request',
            ]);
        }

        $docId = (int) $this->request->getPost('doc_id');
        $pId = (int) $this->request->getPost('pid');

        $sql = "select * from doctor_master where id=" . $docId;
        $query = $this->db->query($sql);
        $doctorMaster = $query->getResult();

        $noOpdDays = 5 - 1;
        if (count($doctorMaster) > 0 && !empty($doctorMaster[0]->opd_valid_no_days)) {
            $noOpdDays = (int) $doctorMaster[0]->opd_valid_no_days - 1;
        }

        $sql = "select d.id, d.p_fname, group_concat(m.SpecName) as SpecName,
            if(d.gender=1,'Male','Female') as xGender
            from doctor_master d
            join doc_spec s join med_spec m on d.id = s.doc_id and s.med_spec_id = m.id
            where d.active = 1 and d.id=" . $docId . " group by d.id";
        $query = $this->db->query($sql);
        $docInfo = $query->getResult();

        $sql = "select * from opd_master where p_id=" . $pId . " and doc_id=" . $docId .
            " and apointment_date >= date_add(curdate(),interval -" . $noOpdDays . " day) and opd_fee_type<>3";
        $query = $this->db->query($sql);
        $opdRunning = $query->getResult();

        $sql = "select * from opd_master where p_id=" . $pId;
        $query = $this->db->query($sql);
        $opdOld = $query->getResult();

        $feeTypeList = '4,0';
        $feeTypeSelect = 1;

        if (count($opdOld) > 0) {
            if (count($opdRunning) > 0) {
                $feeTypeSelect = 3;
                $feeTypeList .= ',3';
            } else {
                $feeTypeSelect = 2;
                $feeTypeList .= ',2';
            }
        } else {
            $feeTypeSelect = 1;
            $feeTypeList .= ',1';
        }

        $content = '';
        $content .= '<h5><strong>Doctor Name :</strong> ' . esc($docInfo[0]->p_fname ?? '') .
            ' <strong>/ Specialization:</strong> ' . esc($docInfo[0]->SpecName ?? '') .
            ' <strong>/ Gender :</strong> ' . esc($docInfo[0]->xGender ?? '') . ' </h5>';
        $content .= '<input type="hidden" name="doc_id" id="doc_id" value="' . esc($docInfo[0]->id ?? 0) . '" />';

        $sql = "select d.*, if(d.doc_fee_desc='', t.fee_type, d.doc_fee_desc) as fee_desc
            from doc_opd_fee d join doc_fee_type t on d.doc_fee_type = t.id
            where doc_id=" . $docId . " and t.id in (" . $feeTypeList . ")";
        $query = $this->db->query($sql);
        $docFeeA = $query->getResult();

        $sql = "select d.*, if(d.doc_fee_desc='', t.fee_type, d.doc_fee_desc) as fee_desc
            from doc_opd_fee d join doc_fee_type t on d.doc_fee_type = t.id
            where doc_id=" . $docId . " and t.id not in (" . $feeTypeList . ")";
        $query = $this->db->query($sql);
        $docFeeB = $query->getResult();

        foreach ($docFeeA as $row) {
            $checked = ((int) $row->id === (int) $feeTypeSelect) ? 'checked' : '';
            $content .= '<label class="d-block">';
            $content .= '<input type="radio" name="fee_id" id="fee_id" class="form-check-input me-1" ' . $checked . ' value="' . (int) $row->id . '"> ';
            $content .= 'Rs. ' . esc($row->amount) . ' [<i>' . esc($row->fee_desc) . '</i>]';
            $content .= '</label>';
        }

        $content .= '<hr />';

        $otherContent = '';
        foreach ($docFeeB as $row) {
            $checked = ((int) $row->id === (int) $feeTypeSelect) ? 'checked' : '';
            $otherContent .= '<label class="d-block">';
            $otherContent .= '<input type="radio" name="fee_id" id="fee_id" class="form-check-input me-1" ' . $checked . ' value="' . (int) $row->id . '"> ';
            $otherContent .= 'Rs. ' . esc($row->amount) . ' [<i>' . esc($row->fee_desc) . '</i>]';
            $otherContent .= '</label>';
        }

        $content .= '<div class="accordion" id="opdFeeAccordion">'
            . '<div class="accordion-item">'
            . '<h2 class="accordion-header" id="headingOne">'
            . '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">'
            . 'Other Option'
            . '</button>'
            . '</h2>'
            . '<div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#opdFeeAccordion">'
            . '<div class="accordion-body">'
            . $otherContent
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';

        return $this->response->setJSON([
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
            'content' => $content,
        ]);
    }

    public function confirm_opd()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'insertid' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        $docId = (int) $this->request->getPost('doc_id');
        $feeId = (int) $this->request->getPost('fee_id');
        $pid = (int) $this->request->getPost('pid');
        $appointmentDate = (string) $this->request->getPost('datepicker_appointment');
        $abhaAddress = trim((string) $this->request->getPost('abha_address'));

        if ($docId <= 0 || $feeId <= 0 || $pid <= 0 || $appointmentDate === '') {
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => 'Missing required fields.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($abhaAddress !== '' && ! $this->isValidAbhaAddress($abhaAddress)) {
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => 'Invalid ABHA Address format.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $sql = "select * from patient_master where id=" . $pid;
        $query = $this->db->query($sql);
        $personInfo = $query->getResult();

        $sql = "select d.id, d.p_fname, group_concat(m.SpecName) as SpecName,
            if(d.gender=1,'Male','Female') as xGender
            from doctor_master d
            join doc_spec s join med_spec m on d.id = s.doc_id and s.med_spec_id = m.id
            where d.active=1 and d.id=" . $docId . " group by d.id";
        $query = $this->db->query($sql);
        $docInfo = $query->getResult();

        $sql = "select * from doc_opd_fee where id=" . $feeId;
        $query = $this->db->query($sql);
        $docFee = $query->getResult();

        if (count($personInfo) === 0 || count($docInfo) === 0 || count($docFee) === 0) {
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => 'Invalid patient, doctor, or fee selection.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $sql = "select * from opd_master where p_id=" . $pid . " order by opd_id desc";
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $userId = $user->id ?? '';
        $userNameInfo = $userLabel . ' [' . date('d-m-Y H:i:s') . '] ' . $userId;

        $insert = [
            'p_id' => $pid,
            'P_name' => strtoupper((string) $personInfo[0]->p_fname),
            'doc_id' => $docId,
            'insurance_id' => '0',
            'opd_fee_id' => $feeId,
            'opd_fee_amount' => $docFee[0]->amount,
            'opd_fee_gross_amount' => $docFee[0]->amount,
            'opd_fee_desc' => $docFee[0]->doc_fee_desc,
            'doc_name' => $docInfo[0]->p_fname,
            'opd_fee_type' => $docFee[0]->doc_fee_type,
            'apointment_date' => str_to_MysqlDate($appointmentDate),
            'doc_spec' => $docInfo[0]->SpecName,
            'prepared_by' => $userNameInfo,
        ];

        if (count($opdMaster) > 0) {
            $insert['last_opdvisit_date'] = $opdMaster[0]->apointment_date;
        }

        $opdModel = new OpdModel();
        $insertId = $opdModel->insertOpd($insert);

        if ($insertId > 0) {
            $this->db->table('patient_master')
                ->where('id', $pid)
                ->update(['last_visit' => str_to_MysqlDate($appointmentDate)]);

            $this->auditClinicalUpdate('opd_master', 'created', $insertId, null, $insert);

            $abhaField = $this->resolvePatientAbhaField();
            if ($abhaField !== null && $abhaAddress !== '') {
                $oldAbha = (string) ($personInfo[0]->{$abhaField} ?? '');
                if ($oldAbha !== $abhaAddress) {
                    $this->db->table('patient_master')
                        ->where('id', $pid)
                        ->update([$abhaField => $abhaAddress]);

                    $this->auditClinicalUpdate('patient_master', $abhaField, $pid, $oldAbha, $abhaAddress);
                }
            }
        }

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? 'OPD Register' : 'OPD already exists for today.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function isValidAbhaAddress(string $value): bool
    {
        $validation = service('validation');
        $validation->reset();

        return $validation->setRules([
            'abha_address' => 'valid_abha_address',
        ])->run([
            'abha_address' => $value,
        ]);
    }

    private function resolvePatientAbhaField(): ?string
    {
        if (! $this->db->tableExists('patient_master')) {
            return null;
        }

        $fields = $this->db->getFieldNames('patient_master');
        foreach (['abha_address', 'abha', 'abha_id', 'abha_no'] as $field) {
            if (in_array($field, $fields, true)) {
                return $field;
            }
        }

        return null;
    }

    public function invoice(int $opdId)
    {
        $sql = "select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
            (case payment_status when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str
            from opd_master where opd_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $data['opd_master'] = $query->getResult();

        if (empty($data['opd_master'])) {
            return $this->response->setStatusCode(404)->setBody('OPD not found');
        }

        $opdRow = $data['opd_master'][0];

        $sql = "select *,if(gender=1,'Male','Female') as xgender
            from patient_master where id='" . (int) $opdRow->p_id . "'";
        $query = $this->db->query($sql);
        $data['patient_master'] = $query->getResult();
        if (!empty($data['patient_master'])) {
            $pRow = $data['patient_master'][0];
            $pRow->age = get_age_1($pRow->dob ?? null, $pRow->age ?? '', $pRow->age_in_month ?? '', $pRow->estimate_dob ?? '', $opdRow->apointment_date ?? null);
        }

        $sql = "select * from organization_case_master where status=0 and case_type=0 and p_id=" . (int) $opdRow->p_id;
        $query = $this->db->query($sql);
        $data['case_master'] = $query->getResult();

        $sql = "select * from hc_insurance where id=" . (int) $opdRow->insurance_id;
        $query = $this->db->query($sql);
        $data['insurance'] = $query->getResult();

        $sql = "select * from refund_order where refund_process=0 and refund_type=1 and refund_type_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $data['refund_order'] = $query->getResult();
        $data['refund_status'] = count($data['refund_order']) > 0 ? 1 : 0;

        $sql = "select sum(if(credit_debit=0,amount,amount*-1)) as paid_amount
            from payment_history where payof_type=1 and payof_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $data['payment_history'] = $query->getResult();

        $sql = "select id,payment_date,insert_time,payment_mode,credit_debit,amount,remark,card_tran_id,update_by,
            (case payment_mode when 0 then 'Zero Amount' when 1 then 'Cash' when 2 then 'Bank/Online' when 3 then 'Return Cash' when 4 then 'Bank Return' else 'Other' end) as payment_mode_str
            from payment_history where payof_type=1 and payof_id=" . (int) $opdId . " order by id desc";
        $query = $this->db->query($sql);
        $data['payment_history_rows'] = $query->getResult();

        $paidAmount = 0;
        if (!empty($data['payment_history']) && $data['payment_history'][0]->paid_amount) {
            $paidAmount = (float) $data['payment_history'][0]->paid_amount;
        }

        $data['paid_amount'] = $paidAmount;
        $grossAmount = (float) $opdRow->opd_fee_amount;
        $data['pending_amount'] = ((int) $opdRow->payment_mode === 4) ? 0 : ($grossAmount - $paidAmount);

        $sql = "select d.id, d.p_fname, group_concat(m.SpecName) as SpecName
            from doctor_master d
            join doc_spec s join med_spec m on d.id = s.doc_id and s.med_spec_id = m.id
            where d.active=1 group by d.id";
        $query = $this->db->query($sql);
        $data['doc_spec_l'] = $query->getResult();

        $sql = "select s.id, s.pay_type, m.bank_name
            from hospital_bank m join hospital_bank_payment_source s on m.id=s.bank_id";
        $query = $this->db->query($sql);
        $data['bank_data'] = $query->getResult();

        return view('billing/opd_invoice_V', $data);
    }

    public function invoice_print(int $opdId)
    {
        $data = $this->buildOpdInvoicePrintData($opdId);
        if ($data === null) {
            return $this->response->setStatusCode(404)->setBody('OPD not found');
        }

        return view('billing/opd_invoice_print_v', $data);
    }

    public function invoice_print_pdf(int $opdId)
    {
        $data = $this->buildOpdInvoicePrintData($opdId);
        if ($data === null) {
            return $this->response->setStatusCode(404)->setBody('OPD not found');
        }

        $html = view('billing/opd_invoice_pdf_v', $data);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'dejavusans',
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $opdCode = (string) ($data['opd_master'][0]->opd_code ?? ('OPD-' . $opdId));
        $mpdf->SetTitle('OPD Invoice ' . $opdCode);
        $mpdf->WriteHTML($html);

        $fileName = 'OPD_Invoice_' . str_replace('/', '-', $opdCode) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($mpdf->Output($fileName, 'S'));
    }

    private function buildOpdInvoicePrintData(int $opdId): ?array
    {
        $data = [];
        $sql = "select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
            (case payment_status when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str
            from opd_master where opd_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $data['opd_master'] = $query->getResult();

        if (empty($data['opd_master'])) {
            return null;
        }

        $opdRow = $data['opd_master'][0];

        $sql = "select *,if(gender=1,'Male','Female') as xgender
            from patient_master where id='" . (int) $opdRow->p_id . "'";
        $query = $this->db->query($sql);
        $data['patient_master'] = $query->getResult();

        if (!empty($data['patient_master'])) {
            $pRow = $data['patient_master'][0];
            $pRow->age = get_age_1($pRow->dob ?? null, $pRow->age ?? '', $pRow->age_in_month ?? '', $pRow->estimate_dob ?? '', $opdRow->apointment_date ?? null);
        }

        $sql = "select * from hc_insurance where id=" . (int) ($opdRow->insurance_id ?? 0);
        $query = $this->db->query($sql);
        $data['insurance'] = $query->getResult();

        if ((int) ($opdRow->insurance_case_id ?? 0) > 0) {
            $sql = "select * from organization_case_master where id=" . (int) $opdRow->insurance_case_id;
            $query = $this->db->query($sql);
            $data['case_master'] = $query->getResult();
        } else {
            $data['case_master'] = [];
        }

        return $data;
    }

    public function opd_lettre_print(int $opdId)
    {
        $sessionId = (int) $this->request->getGet('session_id');
        $layoutMode = strtolower(trim((string) $this->request->getGet('layout')));
        if ($layoutMode === '') {
            $layoutMode = 'full';
        }

        $includeContent = in_array($layoutMode, ['full', 'meta_content', 'content_only'], true);

        $data = $this->buildOpdLetterPrintData($opdId, $sessionId, $includeContent);
        if ($data === null) {
            return $this->response->setStatusCode(404)->setBody('OPD not found');
        }

        $data['print_layout_mode'] = $layoutMode;

        return view('billing/opd_letter_head', $data);
    }

    public function opd_lettre_pdf(int $opdId)
    {
        $sessionId = (int) $this->request->getGet('session_id');
        $layoutMode = strtolower(trim((string) $this->request->getGet('layout')));
        if ($layoutMode === '') {
            $layoutMode = 'full';
        }

        $templateKey = $this->normalizeOpdTemplateId((string) $this->request->getGet('template'));

        $allowedModes = ['full', 'meta_content', 'content_only', 'header_meta', 'meta_only'];
        if (! in_array($layoutMode, $allowedModes, true)) {
            $layoutMode = 'full';
        }

        // If template is explicitly requested, always render full layout so header/footer are applied.
        if ($templateKey !== '') {
            $layoutMode = 'full';
        }

        $includeContent = in_array($layoutMode, ['full', 'meta_content', 'content_only'], true);

        $data = $this->buildOpdLetterPrintData($opdId, $sessionId, $includeContent);
        if ($data === null) {
            return $this->response->setStatusCode(404)->setBody('OPD not found');
        }

        $data['print_layout_mode'] = $layoutMode;

        $isComposedTemplate = $templateKey !== '' && str_starts_with($templateKey, 'compose_');
        if ($isComposedTemplate) {
            $html = $this->renderComposedOpdPrint($data, $templateKey);
        } else {
            $customHtml = '';
            if ($templateKey !== '') {
                $customHtml = $this->readStoredOpdTemplateContent($templateKey);
            }

            if ($customHtml !== '') {
                $legacyVars = $this->buildLegacyDashboardTemplateVars($data);
                $html = $this->renderCurlyTemplate($customHtml, $legacyVars);
            } else {
                $html = view('billing/opd_letter_head_pdf', $data);
            }
        }

        // SHORT-CIRCUIT: When DB template content is found, render it directly with token
        // substitution and skip all per-template paper settings entirely.
        if ($customHtml !== '') {
            $tokenVars = $this->buildOpdPdfTokenVars($data);
            $html = $this->applyOpdPdfTemplateTokens($customHtml, $tokenVars);
            $html = $this->sanitizeOpdNamedHeaderFooterReferences($html);

            $mpdf = new Mpdf([
                'mode'             => 'utf-8',
                'format'           => 'A4',
                'orientation'      => 'P',
                'margin_left'      => 5,
                'margin_right'     => 5,
                'margin_top'       => 5,
                'margin_bottom'    => 5,
                'margin_header'    => 5,
                'margin_footer'    => 5,
                'default_font'     => 'freeserif',
                'autoScriptToLang' => true,
                'autoLangToFont'   => true,
                'tempDir'          => WRITEPATH . 'cache',
            ]);

            $opdCode = (string) ($data['opd_master'][0]->opd_code ?? ('OPD-' . $opdId));
            $mpdf->SetTitle('OPD Prescription ' . $opdCode);
            $mpdf->WriteHTML($html);

            $fileName = 'OPD_Prescription_' . str_replace('/', '-', $opdCode) . '.pdf';
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->setBody($mpdf->Output($fileName, 'S'));
        }

        $docPrintSetting = $this->getOpdPaperPrintSetting($templateKey);
        $tokenVars = $this->buildOpdPdfTokenVars($data);

        $paper = strtolower(trim((string) $this->request->getGet('paper')));
        $format = strtoupper(trim((string) ($docPrintSetting['page_size'] ?? 'A4')));
        if ($format === '') {
            $format = 'A4';
        }
        if (in_array($paper, ['a5', 'a6', 'letter'], true)) {
            $format = strtoupper($paper);
        }

        $customWidthMm = (int) $this->normalizeOpdPrintMarginCm($docPrintSetting['custom_width_mm'] ?? null, 210.0);
        $customHeightMm = (int) $this->normalizeOpdPrintMarginCm($docPrintSetting['custom_height_mm'] ?? null, 297.0);
        if ($customWidthMm < 20) {
            $customWidthMm = 20;
        }
        if ($customWidthMm > 600) {
            $customWidthMm = 600;
        }
        if ($customHeightMm < 20) {
            $customHeightMm = 20;
        }
        if ($customHeightMm > 1000) {
            $customHeightMm = 1000;
        }
        $mpdfFormat = $format === 'CUSTOM' ? [$customWidthMm, $customHeightMm] : $format;

        $hasDocSetting = ! empty($docPrintSetting);
        if ($hasDocSetting) {
            $marginLeftCm = $this->normalizeOpdPrintMarginCm($docPrintSetting['page_margin_left_cm'] ?? null, 0.7);
            $marginRightCm = $this->normalizeOpdPrintMarginCm($docPrintSetting['page_margin_right_cm'] ?? null, 0.7);
            $marginTopCm = $this->normalizeOpdPrintMarginCm($docPrintSetting['page_margin_top_cm'] ?? null, 6.1);
            $marginBottomCm = $this->normalizeOpdPrintMarginCm($docPrintSetting['page_margin_bottom_cm'] ?? null, 2.5);
            $marginHeaderCm = $this->normalizeOpdPrintMarginCm($docPrintSetting['margin_header_cm'] ?? null, 0.5);
            $marginFooterCm = $this->normalizeOpdPrintMarginCm($docPrintSetting['margin_footer_cm'] ?? null, 1.5);
            $marginLeft = $marginLeftCm * 10;
            $marginRight = $marginRightCm * 10;
            $marginTop = $marginTopCm * 10;
            $marginBottom = $marginBottomCm * 10;
            $marginHeader = $marginHeaderCm * 10;
            $marginFooter = $marginFooterCm * 10;
        } else {
            $marginLeftCm = 0.7;
            $marginRightCm = 0.7;
            $marginTopCm = 6.1;
            $marginBottomCm = 2.5;
            $marginHeaderCm = 0.5;
            $marginFooterCm = 1.5;
            $marginLeft = 8;
            $marginRight = 8;
            $marginTop = 8;
            $marginBottom = 8;
            $marginHeader = 5;
            $marginFooter = 10;
        }

        if ($format === 'A6') {
            $marginLeft = 6;
            $marginRight = 6;
            $marginTop = 6;
            $marginBottom = 6;
            $marginLeftCm = 0.6;
            $marginRightCm = 0.6;
            $marginTopCm = 0.6;
            $marginBottomCm = 0.6;
        }

        // Add margin variables to token replacement
        $tokenVars['MarginTop'] = (string) $marginTopCm;
        $tokenVars['MarginBottom'] = (string) $marginBottomCm;
        $tokenVars['MarginLeft'] = (string) $marginLeftCm;
        $tokenVars['MarginRight'] = (string) $marginRightCm;
        $tokenVars['MarginHeader'] = (string) $marginHeaderCm;
        $tokenVars['MarginFooter'] = (string) $marginFooterCm;
        // Aliases for alternate naming
        $tokenVars['page_margin_top_cm'] = (string) $marginTopCm;
        $tokenVars['page_margin_bottom_cm'] = (string) $marginBottomCm;
        $tokenVars['page_margin_left_cm'] = (string) $marginLeftCm;
        $tokenVars['page_margin_right_cm'] = (string) $marginRightCm;
        $tokenVars['margin_header_cm'] = (string) $marginHeaderCm;
        $tokenVars['margin_footer_cm'] = (string) $marginFooterCm;

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $mpdfFormat,
            'orientation' => 'P',
            'margin_left' => $marginLeft,
            'margin_right' => $marginRight,
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'margin_header' => $marginHeader,
            'margin_footer' => $marginFooter,
            'default_font' => 'freeserif',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $rawHeaderHtml = trim((string) ($docPrintSetting['header_html'] ?? ''));
        $rawFooterHtml = trim((string) ($docPrintSetting['footer_html'] ?? ''));
        $rawPaperHtmlContent = trim((string) ($docPrintSetting['paper_html_content'] ?? ''));
        $prefixHtml = '';

        // Build base @page CSS block from margin settings.
        $buildPageStyleBlock = static function (bool $useHeader, bool $useFooter) use ($marginTopCm, $marginBottomCm, $marginLeftCm, $marginRightCm, $marginHeaderCm, $marginFooterCm): string {
            $css = '<style>@page {' . "\n"
                . 'margin-top: ' . $marginTopCm . 'cm;' . "\n"
                . 'margin-bottom: ' . $marginBottomCm . 'cm;' . "\n"
                . 'margin-left: ' . $marginLeftCm . 'cm;' . "\n"
                . 'margin-right: ' . $marginRightCm . 'cm;' . "\n"
                . 'margin-header: ' . $marginHeaderCm . 'cm;' . "\n"
                . 'margin-footer: ' . $marginFooterCm . 'cm;' . "\n";

            if ($useHeader) {
                $css .= 'header: html_myHeader;' . "\n";
            }
            if ($useFooter) {
                $css .= 'footer: html_myFooter;' . "\n";
            }

            $css .= '}' . "\n" . '</style>' . "\n";

            return $css;
        };

        // Resolve body content first.
        if ($rawPaperHtmlContent !== '') {
            $hasContentPlaceholder = preg_match('/<\?=\s*\$content\s*\?>|<\?=\$content\?>|\{\{content\}\}|\{content\}/i', $rawPaperHtmlContent) === 1;
            $paperHtml = $this->applyOpdPdfTemplateTokens($rawPaperHtmlContent, $tokenVars);
            $paperHtml = str_replace(['<?= $content ?>', '<?=$content?>', '{{content}}', '{content}'], $html, $paperHtml);
            if ($hasContentPlaceholder && strpos($paperHtml, $html) === false) {
                $paperHtml .= $html;
            }
            $html = $this->sanitizeOpdNamedHeaderFooterReferences($paperHtml);
        }

        // If separate header/footer fields are provided, always apply them around the resolved body.
        if ($rawHeaderHtml !== '' || $rawFooterHtml !== '') {
            $combinedHtml = $buildPageStyleBlock($rawHeaderHtml !== '', $rawFooterHtml !== '');

            if ($rawHeaderHtml !== '') {
                $renderedHeaderHtml = $this->applyOpdPdfTemplateTokens($rawHeaderHtml, $tokenVars);
                if (!preg_match('/<\s*htmlpageheader\b/i', $renderedHeaderHtml)) {
                    $combinedHtml .= '<htmlpageheader name="myHeader">' . "\n" . $renderedHeaderHtml . "\n" . '</htmlpageheader>' . "\n";
                } else {
                    $combinedHtml .= $renderedHeaderHtml . "\n";
                }
            }

            if ($rawFooterHtml !== '') {
                $renderedFooterHtml = $this->applyOpdPdfTemplateTokens($rawFooterHtml, $tokenVars);
                if (!preg_match('/<\s*htmlpagefooter\b/i', $renderedFooterHtml)) {
                    $combinedHtml .= '<htmlpagefooter name="myFooter">' . "\n" . $renderedFooterHtml . "\n" . '</htmlpagefooter>' . "\n";
                } else {
                    $combinedHtml .= $renderedFooterHtml . "\n";
                }
            }

            $combinedHtml .= $html;
            $html = $this->sanitizeOpdNamedHeaderFooterReferences($combinedHtml);
            $rawHeaderHtml = '';
            $rawFooterHtml = '';
        }

        if ($rawHeaderHtml !== '') {
            $renderedHeaderHtml = $this->applyOpdPdfTemplateTokens($rawHeaderHtml, $tokenVars);
            if (preg_match('/<\s*(htmlpageheader|sethtmlpageheader)\b/i', $renderedHeaderHtml) === 1) {
                $prefixHtml .= $renderedHeaderHtml;
            } else {
                $mpdf->SetHTMLHeader($renderedHeaderHtml, 'O');
                $mpdf->SetHTMLHeader($renderedHeaderHtml, 'E');
            }
        }

        if ($rawFooterHtml !== '') {
            $renderedFooterHtml = $this->applyOpdPdfTemplateTokens($rawFooterHtml, $tokenVars);
            if (preg_match('/<\s*(htmlpagefooter|sethtmlpagefooter)\b/i', $renderedFooterHtml) === 1) {
                $prefixHtml .= $renderedFooterHtml;
            } else {
                $mpdf->SetHTMLFooter($renderedFooterHtml, 'O');
                $mpdf->SetHTMLFooter($renderedFooterHtml, 'E');
            }
        }

        if ($prefixHtml !== '') {
            $mpdf->WriteHTML($prefixHtml, HTMLParserMode::HTML_HEADER_BUFFER);
        }

        $opdCode = (string) ($data['opd_master'][0]->opd_code ?? ('OPD-' . $opdId));
        $mpdf->SetTitle('OPD Prescription ' . $opdCode);
        $mpdf->WriteHTML($html);

        $fileName = 'OPD_Prescription_' . str_replace('/', '-', $opdCode) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($mpdf->Output($fileName, 'S'));
    }

    public function opd_pdf_print(int $opdId)
    {
        $printConfig = $this->resolveDoctorPrintConfigByField($opdId, 'opd_blank_print', 'meta_only');
        $url = base_url('Opd/opd_lettre_pdf/' . (int) $opdId);
        if ($printConfig['template'] !== '') {
            $url .= '?template=' . urlencode($printConfig['template']);
        } else {
            $url .= '?layout=' . urlencode($printConfig['layout']);
        }
        return redirect()->to($url);
    }

    public function opd_blank_print(int $opdId)
    {
        $printConfig = $this->resolveDoctorPrintConfigByField($opdId, 'opd_print_format', 'header_meta');
        $url = base_url('Opd/opd_lettre_pdf/' . (int) $opdId);
        if ($printConfig['template'] !== '') {
            $url .= '?template=' . urlencode($printConfig['template']);
        } else {
            $url .= '?layout=' . urlencode($printConfig['layout']);
        }
        return redirect()->to($url);
    }

    public function opd_cont_print(int $opdId)
    {
        $printConfig = $this->resolveDoctorPrintConfigByField($opdId, 'opd_print_format', 'content_only');
        $url = base_url('Opd/opd_lettre_pdf/' . (int) $opdId);
        if ($printConfig['template'] !== '') {
            $url .= '?template=' . urlencode($printConfig['template']);
        } else {
            $url .= '?layout=' . urlencode($printConfig['layout']);
        }
        return redirect()->to($url);
    }

    /**
     * @return array{layout:string,template:string}
     */
    private function resolveDoctorPrintConfigByField(int $opdId, string $fieldName, string $fallback): array
    {
        $allowed = ['full', 'meta_content', 'content_only', 'header_meta', 'meta_only'];

        $fallbackLayout = strtolower(trim($fallback));
        if (!in_array($fallbackLayout, $allowed, true)) {
            $fallbackLayout = 'full';
        }

        $query = $this->db->query(
            'SELECT d.' . $fieldName . ' AS layout_field
             FROM opd_master o
             LEFT JOIN doctor_master d ON d.id = o.doc_id
             WHERE o.opd_id = ?
             LIMIT 1',
            [(int) $opdId]
        );
        $row = $query->getRowArray();
        $raw = strtolower(trim((string) ($row['layout_field'] ?? '')));

        if ($raw === '') {
            return ['layout' => $fallbackLayout, 'template' => ''];
        }

        if (in_array($raw, $allowed, true)) {
            return ['layout' => $raw, 'template' => ''];
        }

        if ($raw === '0') {
            return ['layout' => 'content_only', 'template' => ''];
        }
        if ($raw === '1') {
            return ['layout' => 'meta_content', 'template' => ''];
        }
        if ($raw === '2') {
            return ['layout' => 'full', 'template' => ''];
        }

        $templateKey = $this->normalizeOpdTemplateId($raw);
        if ($templateKey !== '') {
            return ['layout' => $fallbackLayout, 'template' => $templateKey];
        }

        return ['layout' => $fallbackLayout, 'template' => ''];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildLegacyDashboardTemplateVars(array $data): array
    {
        $opd = $data['opd_master'][0] ?? null;
        $patient = $data['patient_master'][0] ?? null;
        $doctor = $data['doctor_master'][0] ?? null;
        $rx = is_array($data['opd_prescription'] ?? null) ? $data['opd_prescription'] : [];

        $pName = strtoupper(trim((string) (($patient->title ?? '') . ' ' . ($patient->p_fname ?? ''))));
        $pRelative = trim((string) (($patient->p_relative ?? '') . ' ' . ($patient->p_rname ?? '')));
        $ageSex = trim((string) (($patient->xgender ?? '') . ' / ' . ($patient->age ?? '')));
        $phone = (string) ($patient->mphone1 ?? '');
        $pAddress = trim((string) (($patient->add1 ?? '') . ', ' . ($patient->add2 ?? '') . ', ' . ($patient->city ?? '')), ' ,');

        $uhidNo = (string) ($patient->p_code ?? '');
        $opdSrNo = (string) ($opd->opd_no ?? '');
        $opdNo = (string) ($opd->opd_code ?? '');
        $opdDate = (string) ($opd->str_apointment_date ?? '');
        $expDate = trim((string) ($opd->opd_Exp_Date ?? ''));
        $expDateText = $expDate !== '' ? ('<b>Valid Upto : </b>' . esc($expDate)) : '';

        $specName = trim((string) ($doctor->SpecName ?? $opd->doc_spec ?? ''));
        $opdFeeDesc = trim((string) (($opd->opd_fee_amount ?? '') . ' ' . ($opd->opd_fee_desc ?? '')));
        $totalNoVisit = (string) ($opd->no_visit ?? '');
        $lastVisitText = '';
        if (!empty($data['old_opd'][0]->str_apointment_date ?? '')) {
            $lastVisitText = 'Last Visit : ' . (string) $data['old_opd'][0]->str_apointment_date;
        }

        $bookTime = (string) ($opd->apointment_date ?? '');

        $rxMeds = is_array($data['rx_medicines'] ?? null) ? $data['rx_medicines'] : [];
        $medicalHtml = '';
        if (!empty($rxMeds)) {
            $medicalHtml .= '<table width="100%" style="border-collapse:collapse;font-size:12px;">';
            $medicalHtml .= '<tr><th style="text-align:left;">#</th><th style="text-align:left;">Medicine</th><th style="text-align:left;">Dose/Freq</th><th style="text-align:left;">Day</th><th style="text-align:left;">Instruction</th></tr>';
            $i = 0;
            foreach ($rxMeds as $med) {
                $i++;
                $name = trim((string) ($med['med_name'] ?? $med['drug_name'] ?? $med['medicine_name'] ?? $med['item_name'] ?? ''));
                $generic = trim((string) ($med['genericname'] ?? ($med['generic_name'] ?? ($med['salt_name'] ?? ''))));
                $dose = trim((string) ($med['dosage_label'] ?? $med['dosage'] ?? $med['drug_dose'] ?? $med['dose'] ?? ''));
                $freq = trim((string) ($med['dosage_freq_label'] ?? $med['dosage_freq'] ?? $med['drug_freq'] ?? $med['frequency'] ?? ''));
                $days = trim((string) ($med['no_of_days'] ?? $med['drug_day'] ?? $med['days'] ?? ''));
                $when = trim((string) ($med['dosage_when_label'] ?? $med['dosage_when'] ?? ''));
                $remark = trim((string) ($med['remark'] ?? ''));
                $inst = trim(($when !== '' ? $when : '') . ($remark !== '' ? ($when !== '' ? ' | ' : '') . $remark : ''));
                $nameHtml = esc($name);
                if ($generic !== '') {
                    $nameHtml .= '<div style="font-size:10px;color:#444;">Salt/Generic: ' . esc($generic) . '</div>';
                }
                $medicalHtml .= '<tr>'
                    . '<td>' . $i . '</td>'
                    . '<td>' . $nameHtml . '</td>'
                    . '<td>' . esc(trim($dose . ' ' . $freq)) . '</td>'
                    . '<td>' . esc($days) . '</td>'
                    . '<td>' . esc($inst) . '</td>'
                    . '</tr>';
            }
            $medicalHtml .= '</table>';
        }

        $rxInvestigation = trim((string) ($rx['investigation'] ?? ''));
        if ($rxInvestigation === '' && !empty($data['rx_investigations']) && is_array($data['rx_investigations'])) {
            $parts = [];
            foreach ($data['rx_investigations'] as $inv) {
                $txt = trim((string) ($inv['investigation_name'] ?? $inv['investigation'] ?? ''));
                if ($txt !== '') {
                    $parts[] = $txt;
                }
            }
            $rxInvestigation = implode(', ', $parts);
        }

        $rxAdvice = trim((string) ($rx['advice'] ?? ''));
        if ($rxAdvice === '' && !empty($data['rx_advices']) && is_array($data['rx_advices'])) {
            $parts = [];
            foreach ($data['rx_advices'] as $adv) {
                $txt = trim((string) ($adv['advice_txt'] ?? $adv['advice'] ?? ''));
                if ($txt !== '') {
                    $parts[] = $txt;
                }
            }
            $rxAdvice = implode(', ', $parts);
        }

        $complaintText = (string) ($rx['complaints'] ?? '');
        $diagnosisText = (string) (($rx['Provisional_diagnosis'] ?? '') !== '' ? ($rx['Provisional_diagnosis'] ?? '') : ($rx['diagnosis'] ?? ''));
        $complaintLocal = $this->translateToLocalPatientText($complaintText);
        $diagnosisLocal = $this->translateToLocalPatientText($diagnosisText);
        $investigationLocal = $this->translateToLocalPatientText($rxInvestigation);
        $rxAdviceLocal = $this->translateToLocalPatientText($rxAdvice);

        if (strtolower(trim($complaintLocal)) === strtolower(trim($complaintText))) {
            $complaintLocal = '';
        }
        if (strtolower(trim($diagnosisLocal)) === strtolower(trim($diagnosisText))) {
            $diagnosisLocal = '';
        }
        if (strtolower(trim($investigationLocal)) === strtolower(trim($rxInvestigation))) {
            $investigationLocal = '';
        }
        if (strtolower(trim($rxAdviceLocal)) === strtolower(trim($rxAdvice))) {
            $rxAdviceLocal = '';
        }

        $bp = trim((string) ($rx['bp'] ?? ''));
        $diastolic = trim((string) ($rx['diastolic'] ?? ''));
        $pulse = trim((string) ($rx['pulse'] ?? ''));
        $temp = trim((string) ($rx['temp'] ?? ''));
        $spo2 = trim((string) ($rx['spo2'] ?? ''));

        $vitals = [];
        if ($bp !== '') {
            $vitals[] = 'BP: ' . $bp . ($diastolic !== '' ? ('/' . $diastolic) : '');
        }
        if ($pulse !== '') {
            $vitals[] = 'Pulse: ' . $pulse;
        }
        if ($temp !== '') {
            $vitals[] = 'Temp: ' . $temp;
        }
        if ($spo2 !== '') {
            $vitals[] = 'SpO2: ' . $spo2;
        }

        $printContent = '<table width="100%" border="0" style="font-size:10pt;">'
            . '<tr>'
            . '<td width="33.3%" valign="top">'
            . 'Name : <strong>' . esc($pName) . '</strong><br>'
            . esc($pRelative) . '<br>'
            . 'Gender/Age : <b>' . esc($ageSex) . '</b><br>'
            . 'Mob :' . esc($phone) . '<br>'
            . 'Address :' . esc($pAddress)
            . '</td>'
            . '<td width="33.3%" valign="top">'
            . 'UHID : ' . esc($uhidNo) . '<br>'
            . 'Sr No.: <b>' . esc($opdSrNo) . '</b><br>'
            . 'OPD No.: <b>' . esc($opdNo) . '</b><br>'
            . '<br><b>Date: ' . esc($opdDate) . '</b><br>' . $expDateText
            . '</td>'
            . '<td width="33.3%" valign="top">'
            . '<b>DEPARTMENT :</b><br>' . esc($specName) . '<br>'
            . esc($opdFeeDesc) . '<br>'
            . '<b>No. of Visit</b> : ' . esc($totalNoVisit) . '<br>'
            . esc($lastVisitText) . '<br>'
            . 'Book Time : ' . esc($bookTime)
            . '</td>'
            . '</tr>'
            . '</table>';

        return [
            'opd_master' => $data['opd_master'] ?? [],
            'patient_master' => $data['patient_master'] ?? [],
            'doctor_master' => $data['doctor_master'] ?? [],
            'old_opd' => $data['old_opd'] ?? [],
            'insurance' => $data['insurance'] ?? [],
            'case_master' => $data['case_master'] ?? [],
            'content' => $printContent,
            'content_2' => $printContent,
            'content_3' => $printContent,
            'content_4' => $printContent,
            'pName' => $pName,
            'pRelative' => $pRelative,
            'age_sex' => $ageSex,
            'phoneno' => $phone,
            'p_address' => $pAddress,
            'uhid_no' => $uhidNo,
            'opd_sr_no' => $opdSrNo,
            'opd_no' => $opdNo,
            'opd_date' => $opdDate,
            'exp_date' => $expDateText,
            'SpecName' => $specName,
            'opd_fee_desc' => $opdFeeDesc,
            'total_no_visit' => $totalNoVisit,
            'last_opdvisit_date' => $lastVisitText,
            'str_opd_book_date' => $bookTime,
            'Complaint' => $complaintText,
            'Complaint_local' => $complaintLocal,
            'diagnosis' => $diagnosisText,
            'diagnosis_local' => $diagnosisLocal,
            'Provisional_diagnosis' => (string) ($rx['Provisional_diagnosis'] ?? ''),
            'investigation' => $rxInvestigation,
            'investigation_local' => $investigationLocal,
            'medical' => $medicalHtml,
            'doctor' => '<p style="text-align:right;">Dr. ' . esc((string) ($opd->doc_name ?? '')) . '</p>',
            'top_content' => '',
            'vital_content' => implode(' | ', $vitals),
            'Finding_Examinations' => (string) ($rx['Finding_Examinations'] ?? ''),
            'Prescriber_Remarks' => (string) ($rx['Prescriber_Remarks'] ?? ''),
            'advice' => $rxAdvice,
            'advice_local' => $rxAdviceLocal,
            'next_visit' => (string) ($rx['next_visit'] ?? ''),
            'refer_to' => (string) ($rx['refer_to'] ?? ''),
            'painscale' => '',
            'painscale_img' => '',
            'morbidities' => implode(', ', is_array($data['selected_morbidities'] ?? null) ? ($data['selected_morbidities'] ?? []) : []),
            'Addiction' => '',
            'Complication' => '',
        ];
    }

    private function translateToLocalPatientText(string $input): string
    {
        $text = trim($input);
        if ($text === '') {
            return '';
        }

        $map = [
            'before food' => 'भोजन से पहले',
            'after food' => 'भोजन के बाद',
            'after breakfast' => 'नाश्ते के बाद',
            'before breakfast' => 'नाश्ते से पहले',
            'after lunch' => 'दोपहर के भोजन के बाद',
            'before lunch' => 'दोपहर के भोजन से पहले',
            'after dinner' => 'रात के भोजन के बाद',
            'before dinner' => 'रात के भोजन से पहले',
            'morning' => 'सुबह',
            'afternoon' => 'दोपहर',
            'evening' => 'शाम',
            'night' => 'रात',
            'once daily' => 'दिन में एक बार',
            'twice daily' => 'दिन में दो बार',
            'thrice daily' => 'दिन में तीन बार',
            'daily' => 'रोज',
            'weekly' => 'साप्ताहिक',
            'for' => 'के लिए',
            'days' => 'दिन',
            'day' => 'दिन',
            'tab' => 'टैबलेट',
            'cap' => 'कैप्सूल',
            'syp' => 'सिरप',
            'inj' => 'इंजेक्शन',
            'apply' => 'लगाएं',
            'continue' => 'जारी रखें',
            'rest' => 'आराम करें',
            'hydrate' => 'पर्याप्त पानी लें',
            'avoid oily food' => 'तैलीय भोजन से बचें',
            'avoid spicy food' => 'मसालेदार भोजन से बचें',
            'follow up' => 'फॉलो-अप करें',
            'review' => 'पुनः जांच',
            'advice' => 'सलाह',
            'investigation' => 'जांच',
            'diagnosis' => 'निदान',
            'fever' => 'बुखार',
            'cough' => 'खांसी',
            'cold' => 'जुकाम',
            'headache' => 'सिर दर्द',
            'abdominal pain' => 'पेट दर्द',
        ];

        uksort($map, static function ($a, $b) {
            return strlen((string) $b) <=> strlen((string) $a);
        });

        $translated = $text;
        foreach ($map as $en => $local) {
            $translated = preg_replace('/\b' . preg_quote($en, '/') . '\b/i', $local, $translated) ?? $translated;
        }

        $translated = preg_replace('/\s+/', ' ', trim($translated)) ?? trim($translated);
        return $translated;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderLegacyPhpTemplate(string $filePath, array $vars): string
    {
        ob_start();
        extract($vars, EXTR_SKIP);
        include $filePath;
        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderComposedOpdPrint(array $data, string $templateKey): string
    {
        $vars = $this->buildLegacyDashboardTemplateVars($data);

        $hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
        $hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
        $hospitalAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
        $hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
        $hospitalEmail = defined('H_Email') ? (string) constant('H_Email') : '';

        $vars['hospital_name'] = esc($hospitalName);
        $vars['hospital_address'] = esc(trim($hospitalAddress1 . ', ' . $hospitalAddress2, ', '));
        $vars['hospital_phone'] = esc($hospitalPhone);
        $vars['hospital_email'] = esc($hospitalEmail);
        $vars['print_time'] = esc(date('d-m-Y H:i:s'));

        $doctorName = trim((string) ($vars['doctor_name'] ?? ''));
        if ($doctorName === '') {
            $opd = $data['opd_master'][0] ?? null;
            $doctorName = (string) ($opd->doc_name ?? '');
        }
        $vars['doctor_name'] = esc($doctorName);

        $themeKey = trim((string) substr($templateKey, strlen('compose_')));
        if ($themeKey === '') {
            $themeKey = 'default';
        }

        $hospitalPart = strtolower(trim((string) $this->request->getGet('hospital_part')));
        if ($hospitalPart === '') {
            $hospitalPart = $themeKey;
        }

        $contentPart = strtolower(trim((string) $this->request->getGet('content_part')));
        if ($contentPart === '') {
            $contentPart = $themeKey;
        }

        $patientView = strtolower(trim((string) $this->request->getGet('patient_view')));
        if (!in_array($patientView, ['list', 'edit'], true)) {
            $patientView = 'list';
        }

        $layoutHtml = $this->readComposedPartTemplate('layout', $themeKey);
        $hospitalHtml = $this->readComposedPartTemplate('hospital', $hospitalPart);
        $contentHtml = $this->readComposedPartTemplate('content', $contentPart);
        $patientHtml = $this->readComposedPartTemplate('patient', $patientView);

        $hospitalHtml = $this->renderCurlyTemplate($hospitalHtml, $vars);
        $patientHtml = $this->renderCurlyTemplate($patientHtml, $vars);
        $contentHtml = $this->renderCurlyTemplate($contentHtml, $vars);

        $vars['hospital_section'] = $hospitalHtml;
        $vars['patient_section'] = $patientHtml;
        $vars['content_section'] = $contentHtml;

        return $this->renderCurlyTemplate($layoutHtml, $vars);
    }

    private function readComposedPartTemplate(string $section, string $name): string
    {
        $safeSection = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim($section))) ?? '';
        $safeName = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim($name))) ?? '';
        if ($safeSection === '') {
            $safeSection = 'layout';
        }
        if ($safeName === '') {
            $safeName = 'default';
        }

        $baseDir = APPPATH . 'Views/billing/opd_parts/' . $safeSection . '/';
        $target = $baseDir . $safeName . '.html';
        $fallback = $baseDir . 'default.html';

        if (is_file($target)) {
            return (string) file_get_contents($target);
        }
        if (is_file($fallback)) {
            return (string) file_get_contents($fallback);
        }

        return '{{content}}';
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderCurlyTemplate(string $html, array $vars): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', static function (array $m) use ($vars) {
            $key = (string) ($m[1] ?? '');
            $value = $vars[$key] ?? '';
            if (is_array($value) || is_object($value)) {
                return '';
            }
            return (string) $value;
        }, $html);
    }

    private function getOpdPaperSettingStorageKey(string $templateName = ''): string
    {
        $templateName = $this->normalizeOpdTemplateId($templateName);
        if ($templateName === '') {
            return self::OPD_PAPER_SETTING_KEY;
        }

        // Keep key short to avoid s_name length failures in legacy schemas.
        return self::OPD_PAPER_SETTING_KEY_PREFIX . substr(md5($templateName), 0, 20);
    }

    /**
     * @return array<int, string>
     */
    private function getOpdPaperSettingCandidateKeys(string $templateName = ''): array
    {
        $templateName = $this->normalizeOpdTemplateId($templateName);
        if ($templateName === '') {
            return [self::OPD_PAPER_SETTING_KEY];
        }

        $newKey = $this->getOpdPaperSettingStorageKey($templateName);
        $legacyKey = self::OPD_PAPER_SETTING_KEY_PREFIX_LEGACY . $templateName;

        $keys = [$newKey, $legacyKey];
        return array_values(array_unique($keys));
    }

    private function readOpdPaperSettingPayloadByKey(string $storageKey): string
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $storageKey)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function getOpdPaperPrintSetting(string $templateName = ''): array
    {
        $defaults = [
            'page_size' => 'A4',
            'custom_width_mm' => 210,
            'custom_height_mm' => 297,
            'page_margin_top_cm' => 6.1,
            'page_margin_bottom_cm' => 2.5,
            'page_margin_left_cm' => 0.7,
            'page_margin_right_cm' => 0.7,
            'margin_header_cm' => 0.5,
            'margin_footer_cm' => 1.5,
            'paper_html_content' => '',
            'header_html' => '',
            'footer_html' => '',
        ];

        $storageKey = $this->getOpdPaperSettingStorageKey($templateName);
        $raw = '';
        $candidateKeys = $this->getOpdPaperSettingCandidateKeys($templateName);
        error_log('[OPD_PAPER_READ_DEBUG] Template: ' . $templateName . ' | CandidateKeys: ' . implode(', ', $candidateKeys));
        
        foreach ($candidateKeys as $candidateKey) {
            $raw = $this->readOpdPaperSettingPayloadByKey($candidateKey);
            if ($raw !== '') {
                error_log('[OPD_PAPER_READ_DEBUG] Found data in key: ' . $candidateKey . ' | PayloadLen: ' . strlen($raw));
                break;
            }
        }

        if ($raw === '' && $storageKey !== self::OPD_PAPER_SETTING_KEY) {
            // Backward compatibility with legacy global settings.
            $raw = $this->readOpdPaperSettingPayloadByKey(self::OPD_PAPER_SETTING_KEY);
            if ($raw !== '') {
                error_log('[OPD_PAPER_READ_DEBUG] Fell back to global key | PayloadLen: ' . strlen($raw));
            }
        }
        if ($raw === '') {
            error_log('[OPD_PAPER_READ_DEBUG] No data found, using defaults');
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            error_log('[OPD_PAPER_READ_DEBUG] JSON decode failed');
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function saveOpdPaperPrintSetting(array $settings, string $templateName = ''): bool
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return false;
        }

        $payload = json_encode($settings, JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return false;
        }

        $storageKey = $this->getOpdPaperSettingStorageKey($templateName);
        
        // Debug logging - to be removed after testing
        error_log('[OPD_PAPER_SAVE_DEBUG] Template: ' . $templateName . ' | StorageKey: ' . $storageKey . ' | PayloadLen: ' . strlen($payload));

        $this->ensureHospitalSettingValueCanStoreLargePayload();

        $existing = $this->db->table('hospital_setting')
            ->select('id')
            ->where('s_name', $storageKey)
            ->get(1)
            ->getRowArray();

        if (! empty($existing['id'])) {
            $ok = $this->db->table('hospital_setting')
                ->where('id', (int) $existing['id'])
                ->update(['s_value' => $payload]);

            if (! $ok) {
                error_log('[OPD_PAPER_SAVE_DEBUG] UPDATE failed for key: ' . $storageKey);
                return false;
            }

            $persisted = $this->isOpdPaperSettingPayloadPersisted($payload, $storageKey);
            error_log('[OPD_PAPER_SAVE_DEBUG] UPDATE done, persisted check: ' . ($persisted ? 'true' : 'false'));
            return $persisted;
        }

        $ok = $this->db->table('hospital_setting')->insert([
            's_name' => $storageKey,
            's_value' => $payload,
        ]);

        if (! $ok) {
            error_log('[OPD_PAPER_SAVE_DEBUG] INSERT failed for key: ' . $storageKey);
            return false;
        }

        $persisted = $this->isOpdPaperSettingPayloadPersisted($payload, $storageKey);
        error_log('[OPD_PAPER_SAVE_DEBUG] INSERT done, persisted check: ' . ($persisted ? 'true' : 'false'));
        return $persisted;
    }

    private function ensureHospitalSettingValueCanStoreLargePayload(): void
    {
        try {
            $row = $this->db->query("SHOW COLUMNS FROM hospital_setting LIKE 's_value'")->getRowArray();
            $type = strtolower(trim((string) ($row['Type'] ?? '')));
            if ($type === '') {
                return;
            }

            // Legacy schema uses varchar(200), which truncates template HTML payloads.
            if (str_starts_with($type, 'varchar(')) {
                $this->db->query('ALTER TABLE hospital_setting MODIFY s_value LONGTEXT CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL');
            }
        } catch (\Throwable $e) {
            // Keep save flow non-fatal; caller validates persistence right after save.
        }
    }

    private function isOpdPaperSettingPayloadPersisted(string $payload, string $storageKey): bool
    {
        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $storageKey)
            ->get(1)
            ->getRowArray();

        return ((string) ($row['s_value'] ?? '')) === $payload;
    }

    private function clearOpdPaperPrintSetting(string $templateName = ''): bool
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return false;
        }

        $deleted = false;
        foreach ($this->getOpdPaperSettingCandidateKeys($templateName) as $storageKey) {
            $ok = (bool) $this->db->table('hospital_setting')
                ->where('s_name', $storageKey)
                ->delete();
            $deleted = $deleted || $ok;
        }

        return $deleted;
    }

    private function normalizeOpdPrintMarginCm($value, float $default): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $number = (float) $value;
        if (! is_finite($number)) {
            $number = $default;
        }

        $number = max(0.0, min(25.0, $number));

        return round($number, 2);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function buildOpdPdfTokenVars(array $data): array
    {
        $legacy = $this->buildLegacyDashboardTemplateVars($data);
        $tokens = [];
        foreach ($legacy as $key => $value) {
            if (is_scalar($value)) {
                $tokens[(string) $key] = (string) $value;
            }
        }

        // Load hospital settings from database
        $hName = $this->readSettingValueFromDb('H_Name');
        $hAddress1 = $this->readSettingValueFromDb('H_address_1');
        $hAddress2 = $this->readSettingValueFromDb('H_address_2');
        $hPhone = $this->readSettingValueFromDb('H_phone_No');
        $hEmail = $this->readSettingValueFromDb('H_Email');
        $hLogo = $this->readSettingValueFromDb('H_logo');

        // Try constants as fallback if database values are empty
        if ($hName === '') {
            $hName = defined('H_Name') ? (string) constant('H_Name') : '';
        }
        if ($hAddress1 === '') {
            $hAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
        }
        if ($hAddress2 === '') {
            $hAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
        }
        if ($hPhone === '') {
            $hPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
        }
        if ($hEmail === '') {
            $hEmail = defined('H_Email') ? (string) constant('H_Email') : '';
        }
        if ($hLogo === '') {
            $hLogo = defined('H_logo') ? (string) constant('H_logo') : '';
        }

        $tokens['H_Name'] = $hName;
        $tokens['H_address_1'] = $hAddress1;
        $tokens['H_address_2'] = $hAddress2;
        $tokens['H_phone_No'] = $hPhone;
        $tokens['H_Email'] = $hEmail;
        $tokens['H_logo'] = $hLogo;
        $tokens['H_logo_abs'] = $hLogo !== '' ? FCPATH . 'assets/images/' . $hLogo : '';

        $tokens['hospital_name'] = $hName;
        $tokens['hospital_address'] = trim($hAddress1 . ', ' . $hAddress2, ', ');
        $tokens['hospital_phone'] = $hPhone;
        $tokens['hospital_email'] = $hEmail;
        $tokens['print_time'] = date('d-m-Y H:i:s');
        $tokens['current_date'] = date('d-m-Y');
        $tokens['doctor_name'] = trim((string) ($tokens['doctor_name'] ?? (($data['opd_master'][0]->doc_name ?? '') ?? '')));
        $tokens['doctor_sign_html'] = nl2br((string) ($data['opd_master'][0]->doc_sign ?? ''));
        $tokens['content'] = (string) ($tokens['content'] ?? '');

        $adviceLocal = trim((string) ($tokens['advice_local'] ?? ''));
        $tokens['advice_local_line'] = $adviceLocal !== ''
            ? ('<br><strong>सलाह:</strong> ' . $adviceLocal)
            : '';

        return $tokens;
    }

    /**
     * @param array<string, string> $vars
     */
    private function applyOpdPdfTemplateTokens(string $templateHtml, array $vars): string
    {
        if ($templateHtml === '') {
            return '';
        }

        $templateHtml = $this->normalizeLegacyOpdPaperTemplate($templateHtml);

        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{{' . $key . '}}'] = $value;
            $replace['{' . $key . '}'] = $value;
        }

        return strtr($templateHtml, $replace);
    }

    private function normalizeLegacyOpdPaperTemplate(string $html): string
    {
        $out = str_replace(["\r\n", "\r"], "\n", $html);

        $mapPatterns = [
            '/<\?=\s*H_Name\s*\?>/i' => '{{H_Name}}',
            '/<\?=\s*H_address_1\s*\?>/i' => '{{H_address_1}}',
            '/<\?=\s*H_address_2\s*\?>/i' => '{{H_address_2}}',
            '/<\?=\s*H_phone_No\s*\?>/i' => '{{H_phone_No}}',
            '/<\?=\s*H_Email\s*\?>/i' => '{{H_Email}}',
            '/<\?=\s*H_logo\s*\?>/i' => '{{H_logo}}',
            '/<\?=\s*\$opd_master\[0\]->doc_name\s*\?>/i' => '{{doctor_name}}',
            '/<\?=\s*nl2br\(\s*\$opd_master\[0\]->doc_sign\s*\)\s*\?>/i' => '{{doctor_sign_html}}',
            '/<\?=\s*date\(\s*[\"\']d-m-Y\s+H:i:s[\"\']\s*\)\s*\?>/i' => '{{print_time}}',
            '/<\?=\s*\$content\s*\?>/i' => '{{content}}',
            '/<\?=\s*\$content\s*;?\s*\?>/i' => '{{content}}',
            '/<\?=\s*\$\w+\s*\?>/i' => '',
        ];

        foreach ($mapPatterns as $pattern => $replacement) {
            $out = (string) preg_replace($pattern, $replacement, $out);
        }

        // Legacy conditional phone block fallback.
        $out = (string) preg_replace(
            '/<\?php\s*if\s*\(\s*H_phone_No\s*!\s*=\s*[\"\']{0,1}\s*[\"\']{0,1}\s*\)\s*\{\s*echo\s*[\"\']Phone:\s*[\"\']\s*\.\s*H_phone_No\s*;\s*\}\s*\?>/is',
            'Phone: {{H_phone_No}}',
            $out
        );

        // Convert generic echo statements that match a known scalar token key.
        $out = (string) preg_replace('/<\?php\s+echo\s+\$content\s*;?\s*\?>/i', '{{content}}', $out);

        // Remove remaining PHP blocks that cannot be interpreted safely.
        $out = (string) preg_replace('/<\?(?:php|=)[\s\S]*?\?>/i', '', $out);

        return trim($out);
    }

    private function sanitizeOpdNamedHeaderFooterReferences(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $hasMyHeaderBlock = preg_match('/<\s*htmlpageheader\b[^>]*\bname\s*=\s*["\']myHeader["\']/i', $html) === 1;
        $hasMyFooterBlock = preg_match('/<\s*htmlpagefooter\b[^>]*\bname\s*=\s*["\']myFooter["\']/i', $html) === 1;

        if (! $hasMyHeaderBlock) {
            $html = (string) preg_replace('/^\s*header\s*:\s*html_myHeader\s*;\s*$/im', '', $html);
        }
        if (! $hasMyFooterBlock) {
            $html = (string) preg_replace('/^\s*footer\s*:\s*html_myFooter\s*;\s*$/im', '', $html);
        }

        return $html;
    }

    /**
     * Read a setting value from the hospital_setting table
     */
    private function readSettingValueFromDb(string $name): string
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    private function buildOpdLetterPrintData(int $opdId, int $sessionId = 0, bool $includeContent = true): ?array
    {
        $sql = "Select * from opd_master where opd_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        if (empty($opdMaster)) {
            return null;
        }

        $data = [];
        $docId = (int) ($opdMaster[0]->doc_id ?? 0);
        $noOpdDays = 5 - 1;

        $data['doctor_master'] = [];
        if ($docId > 0) {
            $sql = "select d.*, group_concat(DISTINCT m.SpecName) as SpecName
                from doctor_master d
                left join doc_spec s on d.id=s.doc_id
                left join med_spec m on m.id=s.med_spec_id
                where d.id=" . $docId . "
                group by d.id";
            $query = $this->db->query($sql);
            $data['doctor_master'] = $query->getResult();
            if (!empty($data['doctor_master']) && !empty($data['doctor_master'][0]->opd_valid_no_days)) {
                $noOpdDays = (int) $data['doctor_master'][0]->opd_valid_no_days - 1;
            }
        }

        $sql = "select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
            date_format(date_add(apointment_date,interval " . $noOpdDays . " day),'%d-%m-%Y') as opd_Exp_Date,
            (case payment_status when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str
            from opd_master where opd_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $data['opd_master'] = $query->getResult();

        $opdRow = $data['opd_master'][0];

        if (!isset($opdRow->doc_sign) || trim((string) $opdRow->doc_sign) === '') {
            $opdRow->doc_sign = (string) ($data['doctor_master'][0]->doc_sign ?? '');
        }

        $sql = "select *,if(gender=1,'M','F') as xgender
            from patient_master where id='" . (int) $opdRow->p_id . "'";
        $query = $this->db->query($sql);
        $data['patient_master'] = $query->getResult();

        if (!empty($data['patient_master'])) {
            $pRow = $data['patient_master'][0];
            $pRow->age = get_age_1($pRow->dob ?? null, $pRow->age ?? '', $pRow->age_in_month ?? '', $pRow->estimate_dob ?? '', $opdRow->apointment_date ?? null);
        }

        $sql = "select *,date_format(date_add(apointment_date,interval " . $noOpdDays . " day),'%d-%m-%Y') as opd_Exp_Date
            from opd_master where p_id=" . (int) $opdRow->p_id . " and opd_id < " . (int) $opdId . " order by opd_id desc limit 1";
        $query = $this->db->query($sql);
        $data['old_opd'] = $query->getResult();

        $sql = "select * from hc_insurance where id=" . (int) ($opdRow->insurance_id ?? 0);
        $query = $this->db->query($sql);
        $data['insurance'] = $query->getResult();

        if ((int) ($opdRow->insurance_case_id ?? 0) > 0) {
            $sql = "select * from organization_case_master where id=" . (int) $opdRow->insurance_case_id;
            $query = $this->db->query($sql);
            $data['case_master'] = $query->getResult();
        } else {
            $data['case_master'] = [];
        }

        $data['opd_prescription'] = null;
        if ($this->db->tableExists('opd_prescription')) {
            $rowBuilder = $this->db->table('opd_prescription')
                ->where('opd_id', (int) $opdId);

            if ($sessionId > 0) {
                $rowBuilder->where('id', $sessionId);
            } else {
                $rowBuilder->orderBy('id', 'DESC');
            }

            $row = $rowBuilder->get(1)->getRowArray();

            if (!empty($row)) {
                if (empty($row['women_related_problems'] ?? '')) {
                    $row['women_related_problems'] = $this->extractWomenRelatedProblemsFromRemarks((string) ($row['Prescriber_Remarks'] ?? ''));
                }

                $womenStructured = $this->parseWomenStructuredDetails((string) ($row['women_related_problems'] ?? ''));
                $row['women_related_problems'] = (string) ($womenStructured['women_related_problems'] ?? '');
                $row['women_lmp'] = trim((string) (
                    $row['women_lmp']
                    ?? $row['lmp']
                    ?? $row['lmp_date']
                    ?? ($womenStructured['women_lmp'] ?? '')
                ));
                $row['women_last_baby'] = trim((string) (
                    $row['women_last_baby']
                    ?? $row['last_baby']
                    ?? $row['last_baby_details']
                    ?? ($womenStructured['women_last_baby'] ?? '')
                ));
                $row['women_pregnancy_related'] = trim((string) (
                    $row['women_pregnancy_related']
                    ?? $row['pregnancy_related']
                    ?? $row['pregnancy_related_problem']
                    ?? ($womenStructured['women_pregnancy_related'] ?? '')
                ));

                $nabhInfo = $this->hydrateNabhPrintFields(
                    $row,
                    (string) ($row['Prescriber_Remarks'] ?? '')
                );
                $row['drug_allergy_status'] = (string) ($nabhInfo['drug_allergy_status'] ?? '');
                $row['drug_allergy_details'] = (string) ($nabhInfo['drug_allergy_details'] ?? '');
                $row['adr_history'] = (string) ($nabhInfo['adr_history'] ?? '');
                $row['current_medications'] = (string) ($nabhInfo['current_medications'] ?? '');
                $data['opd_prescription'] = $row;
            }
        }

        if (! $includeContent) {
            $data['selected_morbidities'] = [];
            $data['rx_medicines'] = [];
            $data['rx_investigations'] = [];
            $data['rx_advices'] = [];
            return $data;
        }

        $data['selected_morbidities'] = [];
        if ($this->db->tableExists('patient_morbidities') && $this->db->tableExists('morbidities_master')) {
            $pmFields = $this->db->getFieldNames('patient_morbidities');
            $mmFields = $this->db->getFieldNames('morbidities_master');

            $pmPatientField = $this->resolveFirstField($pmFields, ['p_id', 'patient_id']);
            $pmMorField = $this->resolveFirstField($pmFields, ['morbidities', 'mor_id', 'morbidity_id']);
            $mmMorField = $this->resolveFirstField($mmFields, ['mor_id', 'id']);
            $mmNameField = $this->resolveFirstField($mmFields, ['morbidities', 'name', 'title']);

            if ($pmPatientField !== null && $pmMorField !== null && $mmMorField !== null && $mmNameField !== null) {
                $rows = $this->db->table('patient_morbidities pm')
                    ->select('m.' . $mmNameField . ' as morbidities')
                    ->join('morbidities_master m', 'm.' . $mmMorField . '=pm.' . $pmMorField, 'inner')
                    ->where('pm.' . $pmPatientField, (int) ($opdRow->p_id ?? 0))
                    ->orderBy('m.' . $mmNameField, 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($rows as $morRow) {
                    $name = trim((string) ($morRow['morbidities'] ?? ''));
                    if ($name !== '') {
                        $data['selected_morbidities'][] = $name;
                    }
                }
            }
        }

        $data['rx_medicines'] = [];
        $data['rx_investigations'] = [];
        $data['rx_advices'] = [];

        $sessionId = (int) ($data['opd_prescription']['id'] ?? 0);

        $medTable = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($medTable !== null) {
            $medFields = $this->db->getFieldNames($medTable);
            $medBuilder = $this->db->table($medTable);
            $hasFilter = false;
            if (in_array('opd_pre_id', $medFields, true) && $sessionId > 0) {
                $medBuilder->where('opd_pre_id', $sessionId);
                $hasFilter = true;
            } elseif (in_array('opd_id', $medFields, true)) {
                $medBuilder->where('opd_id', (int) $opdId);
                $hasFilter = true;
            }
            if ($hasFilter) {
                if (in_array('id', $medFields, true)) {
                    $medBuilder->orderBy('id', 'ASC');
                }
                $data['rx_medicines'] = $medBuilder->get()->getResultArray();

                if (!empty($data['rx_medicines'])) {
                    $mapDose = [];
                    $mapWhen = [];
                    $mapFreq = [];
                    $medGenericMap = [];
                    $missingGenericMedIds = [];

                    if ($this->db->tableExists('opd_dose_shed')) {
                        $f = $this->db->getFieldNames('opd_dose_shed');
                        $idF = $this->resolveFirstField($f, ['dose_shed_id', 'id']);
                        $labelF = $this->resolveFirstField($f, ['dose_show_sign', 'dose_sign', 'dose_sign_desc', 'name']);
                        if ($idF !== null && $labelF !== null) {
                            $rows = $this->db->table('opd_dose_shed')->select($idF . ' as id,' . $labelF . ' as label')->where($labelF . ' !=', '')->get()->getResultArray();
                            foreach ($rows as $r) {
                                $id = trim((string) ($r['id'] ?? ''));
                                $label = trim((string) ($r['label'] ?? ''));
                                if ($id !== '' && $label !== '') {
                                    $mapDose[$id] = $label;
                                }
                            }
                        }
                    }

                    if ($this->db->tableExists('opd_dose_when')) {
                        $f = $this->db->getFieldNames('opd_dose_when');
                        $idF = $this->resolveFirstField($f, ['dose_when_id', 'id']);
                        $labelF = $this->resolveFirstField($f, ['dose_sign', 'dose_sign_desc', 'name']);
                        if ($idF !== null && $labelF !== null) {
                            $rows = $this->db->table('opd_dose_when')->select($idF . ' as id,' . $labelF . ' as label')->where($labelF . ' !=', '')->get()->getResultArray();
                            foreach ($rows as $r) {
                                $id = trim((string) ($r['id'] ?? ''));
                                $label = trim((string) ($r['label'] ?? ''));
                                if ($id !== '' && $label !== '') {
                                    $mapWhen[$id] = $label;
                                }
                            }
                        }
                    }

                    if ($this->db->tableExists('opd_dose_frequency')) {
                        $f = $this->db->getFieldNames('opd_dose_frequency');
                        $idF = $this->resolveFirstField($f, ['dose_freq_id', 'id']);
                        $labelF = $this->resolveFirstField($f, ['dose_sign', 'dose_sign_desc', 'name']);
                        if ($idF !== null && $labelF !== null) {
                            $rows = $this->db->table('opd_dose_frequency')->select($idF . ' as id,' . $labelF . ' as label')->where($labelF . ' !=', '')->get()->getResultArray();
                            foreach ($rows as $r) {
                                $id = trim((string) ($r['id'] ?? ''));
                                $label = trim((string) ($r['label'] ?? ''));
                                if ($id !== '' && $label !== '') {
                                    $mapFreq[$id] = $label;
                                }
                            }
                        }
                    }

                    foreach ($data['rx_medicines'] as &$medRow) {
                        $doseRaw = trim((string) ($medRow['dosage'] ?? ''));
                        $whenRaw = trim((string) ($medRow['dosage_when'] ?? ''));
                        $freqRaw = trim((string) ($medRow['dosage_freq'] ?? ''));
                        $medRow['dosage_label'] = ($doseRaw !== '' && isset($mapDose[$doseRaw])) ? $mapDose[$doseRaw] : $doseRaw;
                        $medRow['dosage_when_label'] = ($whenRaw !== '' && isset($mapWhen[$whenRaw])) ? $mapWhen[$whenRaw] : $whenRaw;
                        $medRow['dosage_freq_label'] = ($freqRaw !== '' && isset($mapFreq[$freqRaw])) ? $mapFreq[$freqRaw] : $freqRaw;

                        $genericInline = trim((string) ($medRow['genericname'] ?? ($medRow['salt_name'] ?? '')));
                        if ($genericInline === '') {
                            $medId = (int) ($medRow['med_id'] ?? 0);
                            if ($medId > 0) {
                                $missingGenericMedIds[$medId] = true;
                            }
                        }
                    }
                    unset($medRow);

                    if (!empty($missingGenericMedIds) && $this->db->tableExists('opd_med_master')) {
                        $masterFields = $this->db->getFieldNames('opd_med_master');
                        $masterIdField = $this->resolveFirstField($masterFields, ['id']);
                        $masterGenericField = $this->resolveFirstField($masterFields, ['genericname', 'generic_name']);
                        $masterSaltField = $this->resolveFirstField($masterFields, ['salt_name', 'sal_name', 'salt', 'saltname']);
                        $labelField = $masterSaltField ?? $masterGenericField;

                        if ($masterIdField !== null && $labelField !== null) {
                            $rows = $this->db->table('opd_med_master')
                                ->select($masterIdField . ' as med_id,' . $labelField . ' as generic_fallback')
                                ->whereIn($masterIdField, array_map('intval', array_keys($missingGenericMedIds)))
                                ->get()
                                ->getResultArray();

                            foreach ($rows as $r) {
                                $id = (int) ($r['med_id'] ?? 0);
                                $label = trim((string) ($r['generic_fallback'] ?? ''));
                                if ($id > 0 && $label !== '') {
                                    $medGenericMap[$id] = $label;
                                }
                            }
                        }
                    }

                    foreach ($data['rx_medicines'] as &$medRow) {
                        $genericInline = trim((string) ($medRow['genericname'] ?? ($medRow['salt_name'] ?? '')));
                        if ($genericInline === '') {
                            $fallback = $medGenericMap[(int) ($medRow['med_id'] ?? 0)] ?? '';
                            if ($fallback !== '') {
                                $medRow['genericname'] = $fallback;
                            }
                        }
                    }
                    unset($medRow);
                }
            } else {
                $data['rx_medicines'] = [];
            }
        }

        $invTable = $this->findExistingTable(['opd_prescription_investigation']);
        if ($invTable !== null) {
            $invFields = $this->db->getFieldNames($invTable);
            $invBuilder = $this->db->table($invTable);
            $hasFilter = false;
            if (in_array('opd_pre_id', $invFields, true) && $sessionId > 0) {
                $invBuilder->where('opd_pre_id', $sessionId);
                $hasFilter = true;
            } elseif (in_array('opd_id', $invFields, true)) {
                $invBuilder->where('opd_id', (int) $opdId);
                $hasFilter = true;
            }
            if ($hasFilter) {
                if (in_array('investigation_name', $invFields, true)) {
                    $invBuilder->orderBy('investigation_name', 'ASC');
                } elseif (in_array('id', $invFields, true)) {
                    $invBuilder->orderBy('id', 'ASC');
                }
                $data['rx_investigations'] = $invBuilder->get()->getResultArray();
            } else {
                $data['rx_investigations'] = [];
            }
        }

        $adviceTable = $this->findExistingTable(['opd_prescription_advice']);
        if ($adviceTable !== null) {
            $advFields = $this->db->getFieldNames($adviceTable);
            $advBuilder = $this->db->table($adviceTable);
            $hasFilter = false;
            if (in_array('opd_pre_id', $advFields, true) && $sessionId > 0) {
                $advBuilder->where('opd_pre_id', $sessionId);
                $hasFilter = true;
            } elseif (in_array('opd_id', $advFields, true)) {
                $advBuilder->where('opd_id', (int) $opdId);
                $hasFilter = true;
            }
            if ($hasFilter) {
                if (in_array('id', $advFields, true)) {
                    $advBuilder->orderBy('id', 'ASC');
                }
                $data['rx_advices'] = $advBuilder->get()->getResultArray();
            } else {
                $data['rx_advices'] = [];
            }
        }

        if (!empty($data['rx_advices']) && $this->db->tableExists('opd_advice')) {
            $masterFields = $this->db->getFieldNames('opd_advice');
            $masterIdField = $this->resolveFirstField($masterFields, ['id', 'advice_id']);
            $masterAdviceField = $this->resolveFirstField($masterFields, ['advice', 'advice_txt', 'advice_text']);
            $masterHindiField = $this->resolveFirstField($masterFields, ['advice_hindi', 'advice_local', 'hindi_advice', 'advice_hin']);

            if ($masterAdviceField !== null && $masterHindiField !== null) {
                $rxAdviceIds = [];
                $rxAdviceTexts = [];
                foreach ($data['rx_advices'] as $advRow) {
                    $advIdVal = trim((string) ($advRow['advice_id'] ?? ''));
                    if ($advIdVal !== '') {
                        $rxAdviceIds[] = $advIdVal;
                    }

                    $advTextVal = trim((string) ($advRow['advice_txt'] ?? ($advRow['advice'] ?? '')));
                    if ($advTextVal !== '') {
                        $rxAdviceTexts[] = $advTextVal;
                    }
                }

                $rxAdviceIds = array_values(array_unique($rxAdviceIds));
                $rxAdviceTexts = array_values(array_unique($rxAdviceTexts));

                $selectParts = [
                    $masterAdviceField . ' as advice_text',
                    $masterHindiField . ' as advice_hindi',
                ];
                if ($masterIdField !== null) {
                    $selectParts[] = $masterIdField . ' as advice_id';
                }

                $masterBuilder = $this->db->table('opd_advice')
                    ->select(implode(',', $selectParts))
                    ->where($masterAdviceField . ' !=', '');

                $hasScopedFilter = false;
                if ($masterIdField !== null && !empty($rxAdviceIds)) {
                    $masterBuilder->whereIn($masterIdField, $rxAdviceIds);
                    $hasScopedFilter = true;
                }

                if (!empty($rxAdviceTexts)) {
                    if ($hasScopedFilter) {
                        $masterBuilder->orWhereIn($masterAdviceField, $rxAdviceTexts);
                    } else {
                        $masterBuilder->whereIn($masterAdviceField, $rxAdviceTexts);
                    }
                    $hasScopedFilter = true;
                }

                if (!$hasScopedFilter) {
                    $masterRows = [];
                } else {
                    $masterRows = $masterBuilder->limit(500)->get()->getResultArray();
                }

                $byId = [];
                $byText = [];
                foreach ($masterRows as $mRow) {
                    $rawHindi = (string) ($mRow['advice_hindi'] ?? '');
                    if ($rawHindi === '') {
                        continue;
                    }

                    $hindi = trim((string) preg_replace('/\r\n|\r|\n/', "\n", str_replace(['<br/>', '<br />', '<br>'], "\n", strip_tags($rawHindi))));
                    if ($hindi === '') {
                        continue;
                    }

                    $adviceText = trim((string) ($mRow['advice_text'] ?? ''));
                    $textKey = mb_strtolower(preg_replace('/\s+/', ' ', $adviceText) ?? $adviceText);
                    if ($textKey !== '') {
                        $byText[$textKey] = $hindi;
                    }

                    $idKey = trim((string) ($mRow['advice_id'] ?? ''));
                    if ($idKey !== '') {
                        $byId[$idKey] = $hindi;
                    }
                }

                foreach ($data['rx_advices'] as $idx => $advRow) {
                    $currentHindi = trim((string) ($advRow['advice_hindi'] ?? ''));
                    if ($currentHindi !== '') {
                        continue;
                    }

                    $resolved = '';
                    $advIdKey = trim((string) ($advRow['advice_id'] ?? ''));
                    if ($advIdKey !== '' && isset($byId[$advIdKey])) {
                        $resolved = (string) $byId[$advIdKey];
                    }

                    if ($resolved === '') {
                        $advText = trim((string) ($advRow['advice_txt'] ?? ($advRow['advice'] ?? '')));
                        $advTextKey = mb_strtolower(preg_replace('/\s+/', ' ', $advText) ?? $advText);
                        if ($advTextKey !== '' && isset($byText[$advTextKey])) {
                            $resolved = (string) $byText[$advTextKey];
                        }
                    }

                    if ($resolved !== '') {
                        $data['rx_advices'][$idx]['advice_hindi'] = $resolved;
                    }
                }
            }
        }

        return $data;
    }

    public function confirm_payment()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        $mode = (int) $this->request->getPost('mode');
        $oid = (int) $this->request->getPost('oid');
        $spid = (string) $this->request->getPost('spid');

        if ($oid <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid OPD ID',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $sql = "select * from opd_master where opd_id='" . $oid . "'";
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        if (empty($opdMaster)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'OPD not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $sql = "select coalesce(sum(if(credit_debit=0,amount,amount*-1)),0) as opd_pay
            from payment_history where payof_id='" . $oid . "' and payof_type=1";
        $query = $this->db->query($sql);
        $chkPayment = $query->getResult();

        $paidAmount = (float) ($chkPayment[0]->opd_pay ?? 0);
        $pendingAmt = (float) $opdMaster[0]->opd_fee_amount - $paidAmount;

        $payRemark = '';
        if (!empty($opdMaster[0]->opd_discount) && (float) $opdMaster[0]->opd_discount > 0) {
            $payRemark = 'Dis.Amt.:' . $opdMaster[0]->opd_disc_remark . ' /Amount: ' . $opdMaster[0]->opd_discount .
                '/Update:' . $opdMaster[0]->opd_disc_update_by;
        }

        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $userId = $user->id ?? 0;
        $userNameInfo = $userLabel . '[' . date('d-m-Y H:i:s') . ']';

        $paymentModel = new PaymentModel();
        $opdModel = new OpdModel();

        if ($mode === 0) {
            $paydata = [
                'payment_mode' => '0',
                'payof_type' => '1',
                'payof_id' => $oid,
                'payof_code' => $opdMaster[0]->opd_code,
                'credit_debit' => '0',
                'amount' => $opdMaster[0]->opd_fee_amount,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $payRemark,
                'update_by' => $userNameInfo . ' [' . $userId . ']',
                'update_by_id' => $userId,
                'insert_code' => $spid,
            ];

            $insertId = $paymentModel->insertPayment($paydata);

            $data = [
                'payment_mode' => '0',
                'payment_status' => '1',
                'payment_mode_desc' => 'No Cost',
                'payment_id' => $insertId,
                'confirm_pay_opd' => date('Y-m-d H:i:s'),
                'prepared_by_id' => $userId,
            ];

            $this->db->table('opd_master')->where('opd_id', $oid)->update($data);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Zero Cost',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($mode === 1 && $pendingAmt > 0) {
            $paydata = [
                'payment_mode' => '1',
                'payof_type' => '1',
                'payof_id' => $oid,
                'payof_code' => $opdMaster[0]->opd_code,
                'credit_debit' => '0',
                'amount' => $opdMaster[0]->opd_fee_amount,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $payRemark,
                'update_by' => $userNameInfo . ' [' . $userId . ']',
                'update_by_id' => $userId,
                'insert_code' => $spid,
            ];

            $insertId = $paymentModel->insertPayment($paydata);

            $data = [
                'payment_mode' => '1',
                'payment_status' => '1',
                'payment_mode_desc' => 'Cash',
                'payment_id' => $insertId,
                'confirm_pay_opd' => date('Y-m-d H:i:s'),
                'prepared_by_id' => $userId,
            ];

            $this->db->table('opd_master')->where('opd_id', $oid)->update($data);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'CASH',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($mode === 2 && $pendingAmt > 0) {
            $cardTran = trim((string) $this->request->getPost('input_card_tran'));
            if ($cardTran === '' || strlen($cardTran) < 3 || strlen($cardTran) > 15) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Card Transaction ID is required (3-15 chars).',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $paydata = [
                'payment_mode' => '2',
                'payof_type' => '1',
                'payof_id' => $oid,
                'payof_code' => $opdMaster[0]->opd_code,
                'credit_debit' => '0',
                'amount' => $opdMaster[0]->opd_fee_amount,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $payRemark,
                'update_by' => $userNameInfo . ' [' . $userId . ']',
                'pay_bank_id' => (int) $this->request->getPost('cbo_pay_type'),
                'card_tran_id' => $cardTran,
                'update_by_id' => $userId,
            ];

            $insertId = $paymentModel->insertPayment($paydata);

            $data = [
                'payment_mode' => '2',
                'payment_status' => '1',
                'payment_mode_desc' => 'Bank/Online',
                'confirm_pay_opd' => date('Y-m-d H:i:s'),
                'payment_id' => $insertId,
                'prepared_by_id' => $userId,
            ];

            $this->db->table('opd_master')->where('opd_id', $oid)->update($data);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Bank Card',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($mode === 4) {
            $data = [
                'payment_mode' => '4',
                'payment_status' => '1',
                'payment_mode_desc' => 'Org.Case Credit',
                'confirm_pay_opd' => date('Y-m-d H:i:s'),
                'insurance_case_id' => (int) $this->request->getPost('case_id'),
                'prepared_by_id' => $userId,
            ];

            $this->db->table('opd_master')->where('opd_id', $oid)->update($data);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Org. Case Credit',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 0,
            'error_text' => 'Invalid payment request',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_discount_update(int $opdId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        $opdId = (int) $this->request->getPost('oid');
        if ($opdId <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid OPD ID',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $discount = (float) $this->request->getPost('input_dis_amt');
        $remark = (string) $this->request->getPost('input_dis_desc');

        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $userId = $user->id ?? 0;
        $userName = $userLabel . '[' . $userId . ']';

        $sql = "select * from opd_master where opd_id=" . $opdId;
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        if (empty($opdMaster)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'OPD not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $grossAmount = (float) ($opdMaster[0]->opd_fee_gross_amount ?? 0);
        $netAmount = $grossAmount - $discount;
        if ($netAmount < 0) {
            $netAmount = 0;
        }

        $dataUpdate = [
            'opd_discount' => $discount,
            'opd_disc_remark' => $remark,
            'opd_fee_amount' => $netAmount,
            'opd_disc_update_by' => $userName,
        ];

        $this->db->table('opd_master')->where('opd_id', $opdId)->update($dataUpdate);

        return $this->response->setJSON([
            'update' => 1,
            'showcontent' => 'Discount updated',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_cancel(int $opdId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        $opdId = (int) ($this->request->getPost('oid') ?? $opdId);
        if ($opdId <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid OPD ID',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $opdRow = $this->db->table('opd_master')->where('opd_id', $opdId)->get(1)->getRowArray();
        if (empty($opdRow)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'OPD not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $user = auth()->user();
        $userId = (int) ($user->id ?? 0);
        $userName = (string) ($user->username ?? $user->email ?? 'User');
        $userLabel = $userName . '[' . $userId . ']:T-' . date('d-m-Y H:i:s');

        $remarkInput = trim((string) $this->request->getPost('input_remark'));
        $remarkPrefix = $remarkInput !== '' ? $remarkInput : 'Cancel OPD';
        $statusRemark = $remarkPrefix . ' [Update By:' . $userLabel . ']';

        $sql = "select coalesce(sum(if(credit_debit>0,amount*-1,amount)),0) as paid_amount
            from payment_history where payof_type=1 and payof_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $paymentHistory = $query->getResult();
        $paidAmount = (float) ($paymentHistory[0]->paid_amount ?? 0);

        $refundPaymentId = 0;
        $this->db->transStart();

        if ($paidAmount > 0 && (int) ($opdRow['opd_status'] ?? 0) !== 3) {
            $paymentModel = new PaymentModel();
            $refundPaymentId = $paymentModel->insertPayment([
                'payment_mode' => '1',
                'payof_type' => '1',
                'payof_id' => $opdId,
                'payof_code' => (string) ($opdRow['opd_code'] ?? ''),
                'credit_debit' => '1',
                'amount' => $paidAmount,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => 'Refund: ' . $remarkPrefix,
                'update_by' => $userLabel,
                'update_by_id' => $userId,
            ]);
        }

        $this->db->table('opd_master')
            ->where('opd_id', $opdId)
            ->update([
                'opd_status' => '3',
                'opd_status_remark' => $statusRemark,
            ]);

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to cancel OPD. Please retry.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'showcontent' => 'OPD cancelled' . ($refundPaymentId > 0 ? ' and refund posted' : ''),
            'refund_payment_id' => $refundPaymentId,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Load scan/upload modal context for a specific OPD record.
     */
    public function opd_load_doc(int $opdid)
    {
        $sql = "select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
            (case payment_status when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str
            from opd_master where opd_id=" . (int) $opdid;
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        if (empty($opdMaster)) {
            return $this->response->setStatusCode(404)->setBody('OPD not found');
        }

        $opdRow = $opdMaster[0];
        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . (int) $opdRow->p_id . "'";
        $query = $this->db->query($sql);
        $personInfo = $query->getResult();
        if (!empty($personInfo)) {
            $personInfo[0]->age = get_age_1($personInfo[0]->dob ?? null, $personInfo[0]->age ?? '', $personInfo[0]->age_in_month ?? '', $personInfo[0]->estimate_dob ?? '', $opdRow->apointment_date ?? null);
        }

        return view('billing/opd_scan_modal_body', [
            'opdid' => $opdid,
            'opd_master' => $opdMaster,
            'person_info' => $personInfo,
        ]);
    }

    /**
     * Save uploaded or captured scan file and queue it for asynchronous AI analysis.
     */
    public function save_image(int $opdid)
    {
        $opdRow = $this->db->table('opd_master')->where('opd_id', $opdid)->get(1)->getRowArray();
        if (empty($opdRow)) {
            return $this->response->setStatusCode(404)->setJSON(['update' => 0, 'error_text' => 'OPD not found']);
        }

        $uploadsDir = rtrim(FCPATH, '/\\') . '/uploads/' . date('Ymd');
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0777, true);
        }

        $baseFilename = 'OPD-' . $opdid . '-' . time();
        $filename = $baseFilename . '.jpg';
        $fullPath = $uploadsDir . '/' . $filename;
        $detectedMimeType = 'image/jpeg';
        $isImageFile = 1;
        $detectedExt = '.jpg';

        $saved = false;

        // Source 1: direct webcam file upload.
        $uploadedFile = $this->request->getFile('webcam');
        if ($uploadedFile && $uploadedFile->isValid() && !$uploadedFile->hasMoved()) {
            $ext = strtolower((string) ($uploadedFile->getClientExtension() ?: $uploadedFile->guessExtension() ?: 'jpg'));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = $baseFilename . '.' . $ext;
            $fullPath = $uploadsDir . '/' . $filename;
            $uploadedFile->move($uploadsDir, $filename);
            $saved = is_file($fullPath);
            $detectedMimeType = (string) ($uploadedFile->getClientMimeType() ?: 'image/jpeg');
            $isImageFile = 1;
            $detectedExt = '.' . $ext;
        }

        // Source 2: manual file upload (PDF/image).
        if (!$saved) {
            $uploadedFile = $this->request->getFile('userfile');
            if ($uploadedFile && $uploadedFile->isValid() && !$uploadedFile->hasMoved()) {
                $ext = strtolower((string) ($uploadedFile->getClientExtension() ?: $uploadedFile->guessExtension() ?: pathinfo((string) $uploadedFile->getName(), PATHINFO_EXTENSION)));
                if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
                    return $this->response->setJSON([
                        'update' => 0,
                        'error_text' => 'Invalid file type. Upload PDF or image only.',
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
                }
                $filename = $baseFilename . '.' . $ext;
                $fullPath = $uploadsDir . '/' . $filename;
                $uploadedFile->move($uploadsDir, $filename);
                $saved = is_file($fullPath);
                $detectedMimeType = (string) ($uploadedFile->getClientMimeType() ?: ($ext === 'pdf' ? 'application/pdf' : 'image/jpeg'));
                $isImageFile = $ext === 'pdf' ? 0 : 1;
                $detectedExt = '.' . $ext;
            }
        }

        // Source 3: base64 image data fallback from older capture flows.
        if (!$saved) {
            $dataUri = (string) ($this->request->getPost('webcam_data') ?? $this->request->getPost('image') ?? '');
            if ($dataUri !== '' && preg_match('#^data:image/\w+;base64,#i', $dataUri) === 1) {
                preg_match('#^data:image/(\w+);base64,#i', $dataUri, $match);
                $imgExt = strtolower((string) ($match[1] ?? 'jpg'));
                if ($imgExt === 'jpeg') {
                    $imgExt = 'jpg';
                }
                if (!in_array($imgExt, ['jpg', 'png', 'webp'], true)) {
                    $imgExt = 'jpg';
                }
                $filename = $baseFilename . '.' . $imgExt;
                $fullPath = $uploadsDir . '/' . $filename;
                $raw = base64_decode((string) preg_replace('#^data:image/\w+;base64,#i', '', $dataUri));
                if ($raw !== false && $raw !== '') {
                    file_put_contents($fullPath, $raw);
                    $saved = is_file($fullPath);
                    $detectedMimeType = 'image/' . ($imgExt === 'jpg' ? 'jpeg' : $imgExt);
                    $isImageFile = 1;
                    $detectedExt = '.' . $imgExt;
                }
            }
        }

        if (!$saved) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to capture image. Please allow camera and retry.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $user = auth()->user();
        $userId = (int) ($user->id ?? 0);
        $userName = (string) ($user->username ?? $user->email ?? 'User');

        $publicPath = '/uploads/' . date('Ymd') . '/' . $filename;
        $insertId = $this->insertOpdFileUploadRecord(
            $fullPath,
            $publicPath,
            (int) ($opdRow['p_id'] ?? 0),
            $opdid,
            (int) ($opdRow['insurance_case_id'] ?? 0),
            $userName,
            $userId,
            $detectedMimeType,
            $isImageFile,
            $detectedExt
        );

        $scanType = $this->detectScanType((string) $filename);

        if ($insertId > 0 && $this->db->tableExists('file_upload_data')) {
            $fields = $this->db->getFieldNames('file_upload_data');
            $update = [];
            if (in_array('scan_type', $fields, true)) {
                $update['scan_type'] = $scanType;
            }
            if (in_array('document_type', $fields, true)) {
                $update['document_type'] = '';
            }
            if (in_array('content_description', $fields, true)) {
                $update['content_description'] = 'Queued for AI analysis';
            }
            if (in_array('ai_status', $fields, true)) {
                $update['ai_status'] = 'pending';
            }
            if (in_array('ai_alert_flag', $fields, true)) {
                $update['ai_alert_flag'] = 0;
            }
            if (!empty($update)) {
                $this->db->table('file_upload_data')->where('id', $insertId)->update($update);
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'filename' => $filename,
            'file_path' => $publicPath,
            'file_id' => $insertId,
            'scan_type' => $scanType,
            'extracted_text' => '',
            'auto_saved_to_prescription' => 0,
            'opd_session_id' => 0,
            'target_field' => '',
            'ai_status' => 'pending',
            'error_text' => 'File uploaded successfully',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_file_last_list(int $opdid = 0)
    {
        if (!$this->db->tableExists('file_upload_data')) {
            return $this->response->setBody('<div class="text-muted">No scan table found.</div>');
        }

        $fields = $this->db->getFieldNames('file_upload_data');
        $safeSelect = [
            'id',
            'full_path',
            'file_ext',
            'insert_date',
            'pid',
            'opd_id',
            'show_type',
        ];
        foreach (['scan_type', 'document_type', 'content_description', 'ai_status', 'ai_alert_flag', 'ai_alert_text'] as $optional) {
            if (in_array($optional, $fields, true)) {
                $safeSelect[] = $optional;
            }
        }
        if (in_array('ai_provider', $fields, true)) {
            $safeSelect[] = 'ai_provider';
        }

        $builder = $this->db->table('file_upload_data')
            ->select(implode(',', $safeSelect))
            ->where('show_type', 0)
            ->orderBy('id', 'DESC')
            ->limit(10);

        if ($opdid > 0) {
            $builder->where('opd_id', $opdid);
        }

        $rows = $builder->get()->getResultArray();
        $list = [];
        foreach ($rows as $row) {
            $raw = (string) ($row['full_path'] ?? '');
            $publicPath = $raw;
            $pos = strpos(str_replace('\\', '/', $raw), '/uploads/');
            if ($pos !== false) {
                $publicPath = substr(str_replace('\\', '/', $raw), $pos);
            }

            $ext = strtolower((string) ($row['file_ext'] ?? pathinfo($publicPath, PATHINFO_EXTENSION)));
            if ($ext !== '' && $ext[0] !== '.') {
                $ext = '.' . $ext;
            }

            $list[] = [
                'id' => (int) ($row['id'] ?? 0),
                'path' => $publicPath,
                'is_pdf' => $ext === '.pdf',
                'insert_date' => !empty($row['insert_date']) ? date('d/m/Y-H:i', strtotime((string) $row['insert_date'])) : '',
                'scan_type' => (string) ($row['scan_type'] ?? ''),
                'document_type' => (string) ($row['document_type'] ?? ''),
                'content_description' => (string) ($row['content_description'] ?? ''),
                'ai_status' => (string) ($row['ai_status'] ?? ''),
                'ai_alert_flag' => (int) ($row['ai_alert_flag'] ?? 0),
                'ai_alert_text' => (string) ($row['ai_alert_text'] ?? ''),
                'ai_provider' => (string) ($row['ai_provider'] ?? ''),
            ];
        }

        return view('billing/opd_scan_last_list', ['opd_file_list' => $list]);
    }

    public function scan_ai_assist()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        $opdid = (int) $this->request->getPost('opd_id');
        $scanType = trim((string) $this->request->getPost('scan_type'));
        if ($scanType === '') {
            $scanType = 'general';
        }

        $text = trim((string) $this->request->getPost('extracted_text'));
        if ($text === '' || strlen($text) < 20) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Extracted report text is too short for AI diagnosis assist.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $analysis = $this->analyzeScanForDiagnosisSupport($text, $scanType);
        if ($analysis === null) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'AI diagnosis assist unavailable right now.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $applyToOpd = (int) $this->request->getPost('apply_to_opd') === 1;
        $applied = false;
        $sessionId = 0;
        $updatedFields = [];

        if ($applyToOpd && $opdid > 0) {
            $applyResult = $this->applyAiScanAssistToPrescription($opdid, $analysis);
            $applied = (bool) ($applyResult['applied'] ?? false);
            $sessionId = (int) ($applyResult['session_id'] ?? 0);
            $updatedFields = is_array($applyResult['updated_fields'] ?? null) ? $applyResult['updated_fields'] : [];
        }

        return $this->response->setJSON([
            'update' => 1,
            'analysis' => $analysis,
            'applied' => $applied ? 1 : 0,
            'opd_session_id' => $sessionId,
            'updated_fields' => $updatedFields,
            'error_text' => $applied ? 'AI diagnosis assist prepared and added to consult fields.' : 'AI diagnosis assist prepared.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Process one uploaded scan file through AI and update per-file metadata.
     */
    public function scan_ai_process_file()
    {
        try {
            $fileId = (int) $this->request->getPost('file_id');
            if ($fileId <= 0) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Invalid file ID.',
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $applyToOpd = (int) $this->request->getPost('apply_to_opd') === 1;
            $result = $this->processAiForUploadedFile($fileId, $applyToOpd);

            return $this->response->setJSON([
                'update' => (int) ($result['update'] ?? 0),
                'file_id' => $fileId,
                'ai_status' => (string) ($result['ai_status'] ?? 'failed'),
                'document_type' => (string) ($result['document_type'] ?? ''),
                'content_description' => (string) ($result['content_description'] ?? ''),
                'ai_alert_flag' => (int) ($result['ai_alert_flag'] ?? 0),
                'ai_alert_text' => (string) ($result['ai_alert_text'] ?? ''),
                'error_text' => (string) ($result['error_text'] ?? ''),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'scan_ai_process_file failed: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(200)->setJSON([
                'update' => 0,
                'ai_status' => 'failed',
                'error_text' => 'Background AI processing failed.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    public function opd_file_hide(int $fileId)
    {
        if (!$this->db->tableExists('file_upload_data')) {
            return $this->response->setStatusCode(404)->setBody('File table not found');
        }

        $row = $this->db->table('file_upload_data')->select('id,opd_id')->where('id', $fileId)->get(1)->getRowArray();
        if (empty($row)) {
            return $this->response->setStatusCode(404)->setBody('File not found');
        }

        $user = auth()->user();
        $userId = (int) ($user->id ?? 0);

        $fields = $this->db->getFieldNames('file_upload_data');
        $update = ['show_type' => 1];
        if (in_array('upload_by_id', $fields, true)) {
            $update['upload_by_id'] = $userId;
        }
        $this->db->table('file_upload_data')->where('id', $fileId)->update($update);

        return $this->opd_file_last_list((int) ($row['opd_id'] ?? 0));
    }

    private function insertOpdFileUploadRecord(string $fullPath, string $publicPath, int $pid, int $opdid, int $caseId, string $uploadBy, int $uploadById, string $mimeType = 'image/jpeg', int $isImageFile = 1, string $fileExt = '.jpg'): int
    {
        if (!$this->db->tableExists('file_upload_data')) {
            return 0;
        }

        $fields = $this->db->getFieldNames('file_upload_data');
        $ext = $fileExt !== '' ? $fileExt : ('.' . strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION)));
        $mimeType = trim($mimeType) !== '' ? $mimeType : 'application/octet-stream';
        $isImageFile = $isImageFile > 0 ? 1 : 0;
        $insert = [];

        $fieldMap = [
            'name' => basename($fullPath),
            'file_name' => basename($fullPath),
            'orig_name' => basename($fullPath),
            'client_name' => basename($fullPath),
            'file_ext' => $ext,
            'file_type' => $mimeType,
            'full_path' => str_replace('\\', '/', $fullPath),
            'file_path' => str_replace('\\', '/', dirname($fullPath)) . '/',
            'raw_name' => pathinfo($fullPath, PATHINFO_FILENAME),
            'file_size' => @filesize($fullPath) ? round((float) filesize($fullPath) / 1024, 2) : 0,
            'is_image' => $isImageFile,
            'image_width' => 0,
            'image_height' => 0,
            'image_type' => ltrim($ext, '.'),
            'image_size_str' => '',
            'insert_date' => Time::now('Asia/Kolkata')->toDateTimeString(),
            'upload_by' => $uploadBy,
            'upload_by_id' => $uploadById,
            'pid' => $pid,
            'opd_id' => $opdid,
            'ipd_id' => 0,
            'case_id' => $caseId,
            'show_type' => 0,
            'public_path' => $publicPath,
        ];

        foreach ($fieldMap as $key => $value) {
            if (in_array($key, $fields, true)) {
                $insert[$key] = $value;
            }
        }

        $this->db->table('file_upload_data')->insert($insert);
        return (int) $this->db->insertID();
    }

    private function detectScanType(string $filename): string
    {
        $low = strtolower($filename);
        if (str_contains($low, 'ecg')) {
            return 'ecg';
        }
        if (str_contains($low, 'lab') || str_contains($low, 'report')) {
            return 'lab';
        }

        return 'general';
    }

    /**
     * Run the full AI pipeline for one uploaded scan file and persist per-file AI metadata.
     *
     * @return array<string, mixed>
     */
    private function processAiForUploadedFile(int $fileId, bool $applyToOpd = false): array
    {
        $this->resetScanAiDebug();

        if (! $this->db->tableExists('file_upload_data')) {
            return ['update' => 0, 'ai_status' => 'failed', 'error_text' => 'File table not found'];
        }

        $fields = $this->db->getFieldNames('file_upload_data');
        $select = ['id'];
        foreach (['opd_id', 'full_path', 'file_ext', 'file_type', 'name', 'file_name', 'scan_type'] as $col) {
            if (in_array($col, $fields, true)) {
                $select[] = $col;
            }
        }

        $row = $this->db->table('file_upload_data')
            ->select(implode(',', $select))
            ->where('id', $fileId)
            ->get(1)
            ->getRowArray();

        if (empty($row)) {
            return ['update' => 0, 'ai_status' => 'failed', 'error_text' => 'Scan file not found'];
        }

        // Mark row as processing so UI can reflect asynchronous progress.
        if (in_array('ai_status', $fields, true)) {
            $this->db->table('file_upload_data')->where('id', $fileId)->update(['ai_status' => 'processing']);
        }

        $fullPath = $this->resolveScanFilePath($row);
        if ($fullPath === '' || !is_file($fullPath)) {
            $this->updateScanAiColumns($fileId, [
                'ai_status' => 'failed',
                'content_description' => 'Scan file not accessible for AI analysis',
            ], $fields);

            return ['update' => 0, 'ai_status' => 'failed', 'error_text' => 'Scan file not accessible'];
        }

        $filename = (string) ($row['name'] ?? $row['file_name'] ?? basename($fullPath));
        $scanType = trim((string) ($row['scan_type'] ?? ''));
        if ($scanType === '') {
            $scanType = $this->detectScanType($filename);
        }

        $mimeType = $this->guessMimeTypeFromPath($fullPath, (string) ($row['file_type'] ?? ''));
        $readiness = $this->getScanAiReadiness();
        $this->addScanAiDebug('readiness: ' . $readiness['summary']);

        // Fail early when neither AI server diagnosis nor OCR endpoints are configured.
        if (! $readiness['ai_server'] && ! $readiness['ocr_server']) {
            $msg = 'AI not configured. Set DIAGNOSIS_AI_SERVER_URL and DIAGNOSIS_AI_OCR_ENDPOINT in AI Settings.';
            $this->updateScanAiColumns($fileId, [
                'scan_type' => $scanType,
                'document_type' => $this->classifyDocumentType('', $scanType, $filename),
                'content_description' => $msg,
                'ai_status' => 'failed',
                'ai_alert_flag' => 0,
                'ai_alert_text' => '',
            ], $fields);

            return [
                'update' => 0,
                'ai_status' => 'failed',
                'document_type' => $this->classifyDocumentType('', $scanType, $filename),
                'content_description' => $msg,
                'ai_alert_flag' => 0,
                'ai_alert_text' => '',
                'error_text' => $msg,
            ];
        }

        // First try visual interpretation and OCR extraction in parallel-friendly sequence.
        $visual = $this->analyzeScanVisualSupport($fullPath, $scanType, $mimeType);
        $text = $this->extractTextFromScanFile($fullPath, $scanType, $mimeType);
        if ($text === null || trim($text) === '') {
            // If OCR text is weak, keep visual result as a safe fallback summary.
            if (is_array($visual) && !empty($visual)) {
                $documentType = trim((string) ($visual['document_type'] ?? ''));
                if ($documentType === '') {
                    $documentType = $this->classifyDocumentType('', $scanType, $filename);
                }

                $contentDescription = trim((string) ($visual['content_description'] ?? ''));
                $risk = strtolower(trim((string) ($visual['risk_level'] ?? '')));
                $aiAlertText = trim((string) ($visual['alert_text'] ?? ''));
                $detailLines = [];
                if ($contentDescription !== '') {
                    $detailLines[] = 'Summary: ' . $contentDescription;
                }
                if ($risk !== '') {
                    $detailLines[] = 'Risk level: ' . ucfirst($risk);
                }
                if ($aiAlertText !== '') {
                    $detailLines[] = 'Alert: ' . $aiAlertText;
                }
                if (empty($detailLines)) {
                    $detailLines[] = 'AI visual review completed.';
                }
                $contentDescription = mb_substr(implode("\n", $detailLines), 0, 2000);

                $aiAlertFlag = in_array($risk, ['high', 'critical'], true) ? 1 : 0;
                if ($aiAlertFlag === 1 && $aiAlertText === '') {
                    $aiAlertText = 'AI flagged possible abnormal finding';
                }

                $this->updateScanAiColumns($fileId, [
                    'scan_type' => $scanType,
                    'document_type' => $documentType,
                    'content_description' => $contentDescription,
                    'ai_status' => 'completed',
                    'ai_alert_flag' => $aiAlertFlag,
                    'ai_alert_text' => $aiAlertText,
                ], $fields);

                return [
                    'update' => 1,
                    'ai_status' => 'completed',
                    'document_type' => $documentType,
                    'content_description' => $contentDescription,
                    'ai_alert_flag' => $aiAlertFlag,
                    'ai_alert_text' => $aiAlertText,
                    'error_text' => 'AI visual analysis completed',
                ];
            }

            $documentType = $this->classifyDocumentType('', $scanType, $filename);
            $debugInfo = $this->formatScanAiDebug();
            $failMsg = 'AI server could not read usable text from this file. ' . $readiness['summary'] . '. Ensure OCR endpoint and token settings are valid.';
            if ($debugInfo !== '') {
                $failMsg .= ' Debug: ' . $debugInfo;
            }
            log_message('warning', 'scan_ai_process_file extraction failed fileId={id}; {summary}', [
                'id' => $fileId,
                'summary' => $readiness['summary'],
            ]);
            $this->updateScanAiColumns($fileId, [
                'scan_type' => $scanType,
                'document_type' => $documentType,
                'content_description' => $failMsg,
                'ai_status' => 'failed',
                'ai_alert_flag' => 0,
                'ai_alert_text' => '',
            ], $fields);

            return [
                'update' => 0,
                'ai_status' => 'failed',
                'document_type' => $documentType,
                'content_description' => $failMsg,
                'ai_alert_flag' => 0,
                'ai_alert_text' => '',
                'error_text' => $failMsg,
            ];
        }

        // Build structured diagnosis-support output from extracted text.
        $analysis = $this->analyzeScanForDiagnosisSupport($text, $scanType);
        $documentType = trim((string) ($visual['document_type'] ?? ''));
        if ($documentType === '') {
            $documentType = $this->classifyDocumentType($text, $scanType, $filename);
        }

        $visualSummary = trim((string) ($visual['content_description'] ?? ''));
        $contentDescription = $this->buildScanContentDescription($analysis, $text, $documentType);
        if ($visualSummary !== '') {
            $contentDescription = $contentDescription !== ''
                ? mb_substr($visualSummary . "\n" . $contentDescription, 0, 2000)
                : mb_substr($visualSummary, 0, 2000);
        }

        if (strtoupper($documentType) === 'ECG') {
            $contentDescription = $this->buildEcgDetailedDescription($text, $analysis, $contentDescription);
        }

        [$aiAlertFlag, $aiAlertText] = $this->buildScanAlert($analysis, $text);

        $visualRisk = strtolower(trim((string) ($visual['risk_level'] ?? '')));
        if (in_array($visualRisk, ['high', 'critical'], true)) {
            $aiAlertFlag = 1;
            $visualAlertText = trim((string) ($visual['alert_text'] ?? ''));
            if ($visualAlertText !== '') {
                $aiAlertText = $visualAlertText;
            }
        }

        // Optionally append AI support to OPD prescription notes.
        if ($applyToOpd && !empty($analysis) && ((string) ($analysis['diagnosis_suitable'] ?? 'no')) === 'yes') {
            $this->applyAiScanAssistToPrescription((int) ($row['opd_id'] ?? 0), $analysis);
        }

        $this->updateScanAiColumns($fileId, [
            'scan_type' => $scanType,
            'document_type' => $documentType,
            'content_description' => $contentDescription,
            'extract_text' => $text,
            'extracted_text' => $text,
            'ai_status' => 'completed',
            'ai_alert_flag' => $aiAlertFlag,
            'ai_alert_text' => $aiAlertText,
        ], $fields);

        return [
            'update' => 1,
            'ai_status' => 'completed',
            'document_type' => $documentType,
            'content_description' => $contentDescription,
            'ai_alert_flag' => $aiAlertFlag,
            'ai_alert_text' => $aiAlertText,
            'error_text' => 'AI analysis completed',
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveScanFilePath(array $row): string
    {
        // Accept both stored absolute path and recovered path under /public/uploads.
        $fullPath = str_replace('\\', '/', (string) ($row['full_path'] ?? ''));
        if ($fullPath !== '' && is_file($fullPath)) {
            return $fullPath;
        }

        if ($fullPath !== '') {
            $uploadsPos = strpos($fullPath, '/uploads/');
            if ($uploadsPos !== false) {
                $relative = substr($fullPath, $uploadsPos + 1);
                $candidate = rtrim(FCPATH, '/\\') . '/' . str_replace('\\', '/', (string) $relative);
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return '';
    }

    /**
     * Infer MIME type from saved extension when upload metadata is missing.
     */
    private function guessMimeTypeFromPath(string $path, string $existingMime = ''): string
    {
        $existingMime = trim($existingMime);
        if ($existingMime !== '') {
            return $existingMime;
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return 'application/pdf';
        }
        if ($ext === 'png') {
            return 'image/png';
        }
        if ($ext === 'webp') {
            return 'image/webp';
        }

        return 'image/jpeg';
    }

    /**
     * @param array<string, mixed>|null $analysis
     */
    private function buildScanContentDescription(?array $analysis, string $text, string $documentType): string
    {
        if (!empty($analysis)) {
            $summary = trim((string) ($analysis['clinical_summary'] ?? ''));
            $dx = trim((string) ($analysis['probable_diagnosis'] ?? ''));
            $reason = trim((string) ($analysis['suitability_reason'] ?? ''));
            $keyFindings = $this->sanitizeAiStringList($analysis['key_findings'] ?? []);
            $suggestedTests = $this->sanitizeAiStringList($analysis['suggested_tests'] ?? []);
            $redFlags = $this->sanitizeAiStringList($analysis['red_flags'] ?? []);
            $confidence = trim((string) ($analysis['confidence_note'] ?? ''));
            $parts = [];
            if ($summary !== '') {
                $parts[] = 'Summary: ' . $summary;
            }
            if ($dx !== '') {
                $parts[] = 'Impression: ' . $dx;
            }
            if (!empty($keyFindings)) {
                $parts[] = 'Findings: ' . implode('; ', array_slice($keyFindings, 0, 6));
            }
            if (!empty($redFlags)) {
                $parts[] = 'Red flags: ' . implode('; ', array_slice($redFlags, 0, 4));
            }
            if (!empty($suggestedTests)) {
                $parts[] = 'Suggested tests: ' . implode('; ', array_slice($suggestedTests, 0, 5));
            }
            if ($reason !== '') {
                $parts[] = 'Suitability: ' . $reason;
            }
            if ($confidence !== '') {
                $parts[] = 'Confidence: ' . $confidence;
            }

            if (!empty($parts)) {
                return mb_substr(implode("\n", $parts), 0, 2000);
            }
        }

        $plain = trim((string) preg_replace('/\s+/', ' ', $text));
        if ($plain === '') {
            return $documentType . ' report uploaded.';
        }

        return mb_substr($plain, 0, 500);
    }

    /**
     * @param array<string, mixed>|null $analysis
     */
    private function buildEcgDetailedDescription(string $text, ?array $analysis, string $baseSummary): string
    {
        $flat = trim((string) preg_replace('/\s+/', ' ', $text));
        $metrics = [];

        $patterns = [
            'Ventricular rate' => '/(?:vent\.?\s*rate|heart\s*rate|hr)\s*[:=]?\s*(\d{2,3})\s*(?:bpm)?/i',
            'PR interval' => '/pr\s*(?:int|interval)?\s*[:=]?\s*(\d{2,4})\s*(?:ms)?/i',
            'QRS duration' => '/qrs\s*(?:int|duration)?\s*[:=]?\s*(\d{2,4})\s*(?:ms)?/i',
            'QT/QTc' => '/qt\/?qtc\s*(?:int)?\s*[:=]?\s*([\d\.\-\s\/]{3,20})/i',
            'Axis' => '/axis\s*(?:deg)?\s*[:=]?\s*([\d\-\+\s\/]{1,20})/i',
        ];

        foreach ($patterns as $label => $regex) {
            if (preg_match($regex, $flat, $match) === 1) {
                $value = trim((string) ($match[1] ?? ''));
                if ($value !== '') {
                    $metrics[] = $label . ': ' . $value;
                }
            }
        }

        $findings = [];
        if (is_array($analysis)) {
            $summary = trim((string) ($analysis['clinical_summary'] ?? ''));
            $dx = trim((string) ($analysis['probable_diagnosis'] ?? ''));
            $keyFindings = $this->sanitizeAiStringList($analysis['key_findings'] ?? []);
            if ($summary !== '') {
                $findings[] = $summary;
            }
            if ($dx !== '') {
                $findings[] = 'Impression: ' . $dx;
            }
            if (!empty($keyFindings)) {
                $findings[] = 'Findings: ' . implode('; ', array_slice($keyFindings, 0, 4));
            }
        }

        if (empty($findings) && trim($baseSummary) !== '') {
            $findings[] = trim($baseSummary);
        }

        $chunks = [];
        if (!empty($metrics)) {
            $chunks[] = implode(' | ', array_slice($metrics, 0, 5));
        }
        if (!empty($findings)) {
            $chunks[] = implode(' | ', array_slice($findings, 0, 3));
        }

        if (empty($chunks)) {
            return 'ECG uploaded. AI review completed.';
        }

        return mb_substr(implode("\n", $chunks), 0, 1000);
    }

    /**
     * @param array<string, mixed>|null $analysis
     * @return array{0:int,1:string}
     */
    private function buildScanAlert(?array $analysis, string $text): array
    {
        if (!empty($analysis)) {
            $redFlags = $this->sanitizeAiStringList($analysis['red_flags'] ?? []);
            if (!empty($redFlags)) {
                return [1, mb_substr(implode('; ', $redFlags), 0, 250)];
            }

            $probableDx = strtolower(trim((string) ($analysis['probable_diagnosis'] ?? '')));
            foreach (['abnormal', 'acute', 'critical', 'infarct', 'ischemia', 'stroke', 'sepsis', 'hemorrhage', 'emergency'] as $keyword) {
                if ($probableDx !== '' && str_contains($probableDx, $keyword)) {
                    return [1, 'AI flagged possible ' . $keyword . ' risk'];
                }
            }
        }

        $txt = strtolower($text);
        foreach (['critical', 'urgent', 'abnormal', 'positive for', 'high risk'] as $keyword) {
            if (str_contains($txt, $keyword)) {
                return [1, 'Report text suggests ' . $keyword];
            }
        }

        return [0, ''];
    }

    /**
     * Classify broad document type using scan hints, filename and text keywords.
     */
    private function classifyDocumentType(string $text, string $scanType, string $filename): string
    {
        $pool = strtolower($scanType . ' ' . $filename . ' ' . $text);
        $map = [
            'ECG' => ['ecg', 'electrocardio', 'st segment', 'qrs'],
            'Pathology' => ['pathology', 'haemoglobin', 'hemoglobin', 'cbc', 'blood', 'urine', 'biochemistry', 'platelet'],
            'XRay' => ['xray', 'x-ray', 'radiograph', 'chest pa'],
            'CT' => ['ct scan', 'computed tomography'],
            'MRI' => ['mri', 'magnetic resonance'],
            'Ultrasound' => ['usg', 'ultrasound', 'sonography'],
        ];

        foreach ($map as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($pool, $keyword)) {
                    return $type;
                }
            }
        }

        return strtoupper($scanType) !== 'GENERAL' ? strtoupper($scanType) : 'General';
    }

    /**
     * @return array<string, string>|null
     */
    private function analyzeScanVisualSupport(string $filePath, string $scanType, string $mimeType): ?array
    {
        if (!is_file($filePath)) {
            return null;
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false || $raw === '') {
            return null;
        }

        $maxBytes = $mimeType === 'application/pdf' ? (12 * 1024 * 1024) : (8 * 1024 * 1024);
        if (strlen($raw) > $maxBytes) {
            return null;
        }

        $prompt = "You are a clinical assistant. Analyze this medical report image/document visually (even if OCR text is unclear).\n"
            . "Return ONLY valid JSON object with keys: document_type, content_description, risk_level, alert_text.\n"
            . "Rules:\n"
            . "- document_type must be one of: ECG, Pathology, XRay, CT, MRI, Ultrasound, General.\n"
            . "- risk_level must be one of: low, moderate, high, critical.\n"
            . "- content_description should be 1 short cautious summary line.\n"
            . "- alert_text should be short and only when risk is high/critical; else empty string.\n"
            . "- If uncertain, set document_type based on visible format pattern and keep conservative language.\n"
            . "Scan type hint: " . strtoupper($scanType);

        // Ask vision-capable model for a conservative JSON summary.
        $azureRaw = $this->callAzureOpenAiVision($filePath, $mimeType, $prompt, 500, true);
        $azureParsed = $this->parseJsonFromAiRaw($azureRaw);
        if (is_array($azureParsed)) {
            $documentType = trim((string) ($azureParsed['document_type'] ?? 'General'));
            if (!in_array($documentType, ['ECG', 'Pathology', 'XRay', 'CT', 'MRI', 'Ultrasound', 'General'], true)) {
                $documentType = 'General';
            }

            $riskLevel = strtolower(trim((string) ($azureParsed['risk_level'] ?? 'low')));
            if (!in_array($riskLevel, ['low', 'moderate', 'high', 'critical'], true)) {
                $riskLevel = 'low';
            }

            return [
                'document_type' => $documentType,
                'content_description' => trim((string) ($azureParsed['content_description'] ?? '')),
                'risk_level' => $riskLevel,
                'alert_text' => trim((string) ($azureParsed['alert_text'] ?? '')),
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $fields
     */
    private function updateScanAiColumns(int $fileId, array $payload, array $fields): void
    {
        $update = [];
        foreach ($payload as $key => $value) {
            if (in_array($key, $fields, true)) {
                $update[$key] = $value;
            }
        }

        if (!empty($update)) {
            $this->db->table('file_upload_data')->where('id', $fileId)->update($update);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function analyzeScanForDiagnosisSupport(string $extractedText, string $scanType): ?array
    {
            // No text means there is nothing reliable to assess.
            if (trim($extractedText) === '') {
                return null;
            }

            $prompt = "You are a clinical decision-support assistant for OPD doctors.\n"
                . "Input is OCR text from scanned/uploaded patient reports (lab/xray/ecg/general).\n"
                . "Determine if this report text is suitable for diagnosis support.\n"
                . "Return ONLY valid JSON object with keys:\n"
                . "diagnosis_suitable (yes/no), suitability_reason, probable_diagnosis, clinical_summary, key_findings, suggested_tests, red_flags, confidence_note.\n"
                . "Rules:\n"
                . "- Keep content concise, medically cautious, non-final.\n"
                . "- key_findings/suggested_tests/red_flags must be JSON arrays of short strings.\n"
                . "- Do not invent values not present in text.\n"
                . "Scan Type: " . strtoupper($scanType) . "\n"
                . "Report Text:\n" . $extractedText;

            $azureRaw = $this->callAzureOpenAiText($prompt, 900, true);
            $azureParsed = $this->parseJsonFromAiRaw($azureRaw);
            if (is_array($azureParsed)) {
                return [
                    'diagnosis_suitable' => strtolower(trim((string) ($azureParsed['diagnosis_suitable'] ?? 'no'))) === 'yes' ? 'yes' : 'no',
                    'suitability_reason' => trim((string) ($azureParsed['suitability_reason'] ?? '')),
                    'probable_diagnosis' => trim((string) ($azureParsed['probable_diagnosis'] ?? '')),
                    'clinical_summary' => trim((string) ($azureParsed['clinical_summary'] ?? '')),
                    'key_findings' => $this->sanitizeAiStringList($azureParsed['key_findings'] ?? []),
                    'suggested_tests' => $this->sanitizeAiStringList($azureParsed['suggested_tests'] ?? []),
                    'red_flags' => $this->sanitizeAiStringList($azureParsed['red_flags'] ?? []),
                    'confidence_note' => trim((string) ($azureParsed['confidence_note'] ?? '')),
                ];
            }

            return null;
        }

        /**
         * @return array{applied:bool,session_id:int,updated_fields:array<int,string>}
         */
        private function applyAiScanAssistToPrescription(int $opdid, array $analysis): array
        {
            if (! $this->db->tableExists('opd_prescription')) {
                return ['applied' => false, 'session_id' => 0, 'updated_fields' => []];
            }

            $fields = $this->db->getFieldNames('opd_prescription');
            $diagnosisField = $this->resolveFirstField($fields, ['diagnosis', 'provisional_diagnosis']);
            $findingField = $this->resolveFirstField($fields, ['Finding_Examinations', 'finding_examinations']);
            $investigationField = $this->resolveFirstField($fields, ['investigation']);

            $row = $this->db->table('opd_prescription')
                ->where('opd_id', $opdid)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (empty($row)) {
                $opdMaster = $this->db->table('opd_master')->where('opd_id', $opdid)->get(1)->getRowArray();
                if (empty($opdMaster)) {
                    return ['applied' => false, 'session_id' => 0, 'updated_fields' => []];
                }

                $insert = [];
                foreach ([
                    'opd_id' => $opdid,
                    'p_id' => (int) ($opdMaster['p_id'] ?? 0),
                    'doc_id' => (int) ($opdMaster['doc_id'] ?? 0),
                    'date_opd_visit' => date('Y-m-d'),
                    'visit_status' => 0,
                    'session_id' => 0,
                ] as $k => $v) {
                    if (in_array($k, $fields, true)) {
                        $insert[$k] = $v;
                    }
                }

                $this->db->table('opd_prescription')->insert($insert);
                $newId = (int) $this->db->insertID();
                $row = $this->db->table('opd_prescription')->where('id', $newId)->get(1)->getRowArray();
            }

            if (empty($row)) {
                return ['applied' => false, 'session_id' => 0, 'updated_fields' => []];
            }

            $sessionId = (int) ($row['id'] ?? 0);
            $prefix = '[AI-SCAN ' . date('d-m-Y H:i') . ']';
            $summary = trim((string) ($analysis['clinical_summary'] ?? ''));
            $probableDx = trim((string) ($analysis['probable_diagnosis'] ?? ''));
            $confidence = trim((string) ($analysis['confidence_note'] ?? ''));
            $findings = $this->sanitizeAiStringList($analysis['key_findings'] ?? []);
            $tests = $this->sanitizeAiStringList($analysis['suggested_tests'] ?? []);
            $redFlags = $this->sanitizeAiStringList($analysis['red_flags'] ?? []);

            $updateData = [];
            $updatedFields = [];

            if ($diagnosisField !== null && $probableDx !== '') {
                $chunk = $prefix . ' Probable diagnosis support: ' . $probableDx;
                if ($confidence !== '') {
                    $chunk .= ' (' . $confidence . ')';
                }
                $old = trim((string) ($row[$diagnosisField] ?? ''));
                if (! str_contains($old, $chunk)) {
                    $updateData[$diagnosisField] = $old === '' ? $chunk : ($old . "\n" . $chunk);
                    $updatedFields[] = $diagnosisField;
                }
            }

            if ($findingField !== null) {
                $parts = [];
                if ($summary !== '') {
                    $parts[] = 'Summary: ' . $summary;
                }
                if (! empty($findings)) {
                    $parts[] = 'Findings: ' . implode('; ', $findings);
                }
                if (! empty($redFlags)) {
                    $parts[] = 'Red flags: ' . implode('; ', $redFlags);
                }

                if (! empty($parts)) {
                    $chunk = $prefix . ' ' . implode(' | ', $parts);
                    $old = trim((string) ($row[$findingField] ?? ''));
                    if (! str_contains($old, $chunk)) {
                        $updateData[$findingField] = $old === '' ? $chunk : ($old . "\n" . $chunk);
                        $updatedFields[] = $findingField;
                    }
                }
            }

            if ($investigationField !== null && ! empty($tests)) {
                $chunk = $prefix . ' Suggested tests: ' . implode('; ', $tests);
                $old = trim((string) ($row[$investigationField] ?? ''));
                if (! str_contains($old, $chunk)) {
                    $updateData[$investigationField] = $old === '' ? $chunk : ($old . "\n" . $chunk);
                    $updatedFields[] = $investigationField;
                }
            }

            if (! empty($updateData)) {
                $this->db->table('opd_prescription')->where('id', $sessionId)->update($updateData);
            }

            return [
                'applied' => ! empty($updateData),
                'session_id' => $sessionId,
                'updated_fields' => $updatedFields,
            ];
        }

        /**
         * @param mixed $raw
         * @return array<int, string>
         */
        private function sanitizeAiStringList($raw): array
        {
            // Normalize AI arrays into unique, non-empty strings.
            if (! is_array($raw)) {
                return [];
            }

            $out = [];
            foreach ($raw as $item) {
                $txt = trim((string) $item);
                if ($txt !== '') {
                    $out[] = $txt;
                }
            }

            return array_values(array_unique($out));
        }

        /**
         * @return array<string, mixed>|null
         */
        private function parseJsonFromAiRaw(string $raw): ?array
        {
            $text = trim($raw);
            if ($text === '') {
                return null;
            }

            // First attempt: raw response is already valid JSON.
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            // Second attempt: extract JSON object embedded in model prose.
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $match) === 1) {
                $decoded = json_decode((string) ($match[0] ?? ''), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            return null;
        }

    /**
     * @param array<int, string> $constCandidates
     * @param array<int, string> $settingCandidates
     */
    private function readScanAiSettingValue(array $constCandidates, array $settingCandidates): string
    {
        foreach ($constCandidates as $constName) {
            if (! defined($constName)) {
                continue;
            }

            $value = trim((string) constant($constName));
            if ($value !== '') {
                return $value;
            }
        }

        if ($this->db->tableExists('hospital_setting') && ! empty($settingCandidates)) {
            $row = $this->db->table('hospital_setting')
                ->select('s_value')
                ->whereIn('s_name', $settingCandidates)
                ->where('s_value !=', '')
                ->orderBy('s_name', 'ASC')
                ->get(1)
                ->getRowArray();

            if (! empty($row['s_value'])) {
                return trim((string) $row['s_value']);
            }
        }

        return '';
    }

    /**
     * @return array{ai_server:bool,ocr_server:bool,summary:string}
     */
    private function getScanAiReadiness(): array
    {
        $base = $this->resolveAiServerBaseUrlForScan();
        $aiServer = $base !== '';

        $ocrEndpoint = rtrim($this->readScanAiSettingValue([], ['DIAGNOSIS_AI_OCR_ENDPOINT']), '/');
        $ocrReady = $ocrEndpoint !== '';

        $parts = [];
        $parts[] = 'AIServer=' . ($aiServer ? 'set' : 'missing base URL');
        $parts[] = 'OCR=' . ($ocrReady ? 'set' : 'missing endpoint');

        return [
            'ai_server' => $aiServer,
            'ocr_server' => $ocrReady,
            'summary' => implode(', ', $parts),
        ];
    }

    private function callAzureOpenAiVision(string $filePath, string $mimeType, string $prompt, int $maxTokens = 1200, bool $jsonObject = false): string
    {
        unset($filePath, $mimeType, $prompt, $maxTokens, $jsonObject);
        $this->addScanAiDebug('visual interpretation via Azure is disabled; using AI server OCR/diagnosis flow');
        return '';
    }

    private function callAzureOpenAiText(string $prompt, int $maxTokens = 1200, bool $jsonObject = false): string
    {
        unset($prompt, $maxTokens, $jsonObject);
        $this->addScanAiDebug('text generation via Azure is disabled; using AI server flow');
        return '';
    }

    private function extractTextWithAzureDocumentIntelligence(string $filePath): string
    {
        $endpoint = rtrim($this->readScanAiSettingValue([], ['DIAGNOSIS_AI_OCR_ENDPOINT']), '/');
        $token = trim($this->readScanAiSettingValue([], ['DIAGNOSIS_AI_PARSE_TOKEN']));

        if ($endpoint === '' || !is_file($filePath)) {
            $this->addScanAiDebug('ai server OCR skipped: endpoint/file missing');
            return '';
        }

        try {
            $headers = ['Accept' => 'application/json'];
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $client = service('curlrequest', $this->buildAzureCurlOptions($endpoint, 40, false));
            $mime = $this->guessMimeTypeFromPath($filePath);
            if (! str_starts_with($mime, 'image/')) {
                return '';
            }

            $curlFile = new \CURLFile($filePath, $mime, basename($filePath));
            $response = $client->post($endpoint, [
                'headers' => $headers,
                'multipart' => [
                    'file' => $curlFile,
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return '';
            }

            $decoded = json_decode((string) $response->getBody(), true);
            if (! is_array($decoded)) {
                return '';
            }

            foreach (['ocr_text', 'text', 'extracted_text'] as $key) {
                $text = trim((string) ($decoded[$key] ?? ''));
                if ($text !== '') {
                    return $text;
                }
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }

    private function resolveAiServerBaseUrlForScan(): string
    {
        $base = rtrim($this->readScanAiSettingValue([], ['DIAGNOSIS_AI_SERVER_URL']), '/');
        if ($base !== '') {
            return $base;
        }

        $candidates = [
            trim($this->readScanAiSettingValue([], ['DIAGNOSIS_AI_PARSE_ENDPOINT'])),
            trim($this->readScanAiSettingValue([], ['DIAGNOSIS_AI_OCR_ENDPOINT'])),
        ];

        foreach ($candidates as $url) {
            if ($url === '') {
                continue;
            }

            $parts = @parse_url($url);
            if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
                continue;
            }

            $base = (string) $parts['scheme'] . '://' . (string) $parts['host'];
            if (! empty($parts['port'])) {
                $base .= ':' . (int) $parts['port'];
            }

            return rtrim($base, '/');
        }

        return '';
    }

    private function extractTextFromDocIntelPayload(array $payload): string
    {
        // Prefer native concatenated content when present.
        $content = trim((string) ($payload['analyzeResult']['content'] ?? ''));
        if ($content !== '') {
            return $content;
        }

        // Fallback: rebuild text from line entries.
        $lines = [];
        $pages = $payload['analyzeResult']['pages'] ?? [];
        foreach ($pages as $page) {
            $pageLines = $page['lines'] ?? [];
            foreach ($pageLines as $line) {
                $txt = trim((string) ($line['content'] ?? ''));
                if ($txt !== '') {
                    $lines[] = $txt;
                }
            }
        }

        return trim(implode("\n", $lines));
    }

    private function extractTextFromScanFile(string $filePath, string $scanType, ?string $mimeType = null): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false || $raw === '') {
            return null;
        }

        $mimeType = trim((string) ($mimeType ?? ''));
        if ($mimeType === '') {
            $mimeType = $this->guessMimeTypeFromPath($filePath);
        }

        $maxBytes = $mimeType === 'application/pdf' ? (12 * 1024 * 1024) : (8 * 1024 * 1024);
        if (strlen($raw) > $maxBytes) {
            return null;
        }

        // OCR priority: Document Intelligence first, then vision model fallback.
        $prompt = 'Extract readable clinical text from this ' . strtoupper($scanType) . ' medical document. Return plain text with sections: Findings, Impression, Values. Do not invent data.';

        $docIntelText = $this->extractTextWithAzureDocumentIntelligence($filePath);
        if ($docIntelText !== '') {
            $clean = $this->sanitizeExtractedScanText($docIntelText, $scanType);
            return $clean !== '' ? $clean : (preg_replace('/\s+/', ' ', trim($docIntelText)) ?: trim($docIntelText));
        }

        $azurePrompt = 'Read and extract all visible clinical text from this ' . strtoupper($scanType) . ' medical image. Return plain text only. If unclear, return best readable lines.';
        $azureText = $this->callAzureOpenAiVision($filePath, $mimeType, $azurePrompt, 1800, false);
        if ($azureText !== '') {
            $clean = $this->sanitizeExtractedScanText($azureText, $scanType);
            return $clean !== '' ? $clean : (preg_replace('/\s+/', ' ', trim($azureText)) ?: trim($azureText));
        }

        return null;
    }

    /**
     * Clean OCR output so OPD UI stores readable clinical text only.
     */
    private function sanitizeExtractedScanText(string $text, string $scanType): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Remove control and non-ASCII characters that usually come from OCR noise.
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', ' ', $text) ?? $text;

        // Drop repeated-char junk words like "ffffff".
        $text = preg_replace('/\b([A-Za-z])\1{4,}\b/', ' ', $text) ?? $text;

        // Collapse very long repeated words into one token.
        $text = preg_replace('/\b([A-Za-z]{2,})\b(?:\s+\1\b){2,}/i', '$1', $text) ?? $text;

        $isEcg = strtolower(trim($scanType)) === 'ecg' || stripos($text, 'ecg') !== false;
        if ($isEcg) {
            // Common OCR fixes for ECG templates.
            $replacements = [
                '/\bKECG\b/i' => 'ECG',
                '/\bUniconfirmed\b/i' => 'Unconfirmed',
                '/\bavt\b/i' => 'aVL',
                '/\baVR\b/i' => 'aVR',
                '/\baVF\b/i' => 'aVF',
                '/\baVL\b/i' => 'aVL',
                '/\bVT\b/' => 'V1',
            ];

            foreach ($replacements as $pattern => $replacement) {
                $text = preg_replace($pattern, $replacement, $text) ?? $text;
            }
        }

        // Normalize whitespace for compact storage/response.
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);

        return $text;
    }

    /**
     * @return array{updated:bool,session_id:int,target_field:string}
     */
    private function appendExtractedTextToOpdPrescription(int $opdid, string $scanType, string $text): array
    {
        if (!$this->db->tableExists('opd_prescription')) {
            return ['updated' => false, 'session_id' => 0, 'target_field' => ''];
        }

        $fields = $this->db->getFieldNames('opd_prescription');
        $targetField = in_array('Finding_Examinations', $fields, true) ? 'Finding_Examinations' : '';

        if (in_array($scanType, ['ecg', 'lab'], true) && in_array('investigation', $fields, true)) {
            $targetField = 'investigation';
        } elseif ($targetField === '' && in_array('diagnosis', $fields, true)) {
            $targetField = 'diagnosis';
        }

        if ($targetField === '') {
            return ['updated' => false, 'session_id' => 0, 'target_field' => ''];
        }

        $row = $this->db->table('opd_prescription')
            ->where('opd_id', $opdid)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (empty($row)) {
            $opdMaster = $this->db->table('opd_master')->where('opd_id', $opdid)->get(1)->getRowArray();
            if (empty($opdMaster)) {
                return ['updated' => false, 'session_id' => 0, 'target_field' => ''];
            }

            $insert = [];
            foreach ([
                'opd_id' => $opdid,
                'p_id' => (int) ($opdMaster['p_id'] ?? 0),
                'doc_id' => (int) ($opdMaster['doc_id'] ?? 0),
                'date_opd_visit' => date('Y-m-d'),
                'visit_status' => 0,
                'session_id' => 0,
            ] as $k => $v) {
                if (in_array($k, $fields, true)) {
                    $insert[$k] = $v;
                }
            }

            $this->db->table('opd_prescription')->insert($insert);
            $newId = (int) $this->db->insertID();
            $row = $this->db->table('opd_prescription')->where('id', $newId)->get(1)->getRowArray();
        }

        if (empty($row)) {
            return ['updated' => false, 'session_id' => 0, 'target_field' => ''];
        }

        $sessionId = (int) ($row['id'] ?? 0);
        $oldValue = trim((string) ($row[$targetField] ?? ''));
        $prefix = '[SCAN-' . strtoupper($scanType) . ' ' . date('d-m-Y H:i') . ']';
        $newChunk = $prefix . ' ' . trim($text);

        if ($oldValue !== '' && str_contains($oldValue, $newChunk)) {
            return ['updated' => true, 'session_id' => $sessionId, 'target_field' => $targetField];
        }

        $newValue = $oldValue === '' ? $newChunk : ($oldValue . "\n" . $newChunk);
        $this->db->table('opd_prescription')->where('id', $sessionId)->update([$targetField => $newValue]);

        return ['updated' => true, 'session_id' => $sessionId, 'target_field' => $targetField];
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

    private function findExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $table) {
            if ($this->db->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    private function hydrateNabhPrintFields(array $row, string $remarks): array
    {
        $pick = function (array $candidates) use ($row): string {
            foreach ($candidates as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
            return '';
        };

        $data = [
            'drug_allergy_status' => $pick(['drug_allergy_status', 'allergy_status', 'drug_allergy']),
            'drug_allergy_details' => $pick(['drug_allergy_details', 'allergy_details', 'drug_allergy_note', 'allergy_note']),
            'adr_history' => $pick(['adr_history', 'adverse_drug_reaction', 'adr_details', 'adverse_reaction_history']),
            'current_medications' => $pick(['current_medications', 'current_medication', 'current_medication_history', 'ongoing_medications']),
        ];

        $parsed = $this->extractNabhFieldsFromRemarks($remarks);
        foreach ($data as $key => $value) {
            if ($value === '' && !empty($parsed[$key])) {
                $data[$key] = (string) $parsed[$key];
            }
        }

        return $data;
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
        ];
    }

    private function extractWomenRelatedProblemsFromRemarks(string $remarks): string
    {
        if ($remarks === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $remarks) ?: [];
        $capture = [];
        $started = false;
        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '') {
                if ($started) {
                    break;
                }
                continue;
            }

            if (preg_match('/^Women Related Problems\s*:/i', $trimmed) === 1) {
                $started = true;
                $capture[] = $trimmed;
                continue;
            }

            if ($started && preg_match('/^(LMP(?:\s*Days\s*Before)?|Last Baby|Pregnancy Related)\s*:/i', $trimmed) === 1) {
                $capture[] = $trimmed;
                continue;
            }

            if ($started) {
                break;
            }
        }

        if (!empty($capture)) {
            return trim(implode("\n", $capture));
        }

        return '';
    }

    /**
     * @return array{women_related_problems:string,women_lmp:string,women_last_baby:string,women_pregnancy_related:string}
     */
    private function parseWomenStructuredDetails(string $text): array
    {
        $remaining = trim($text);

        $extract = static function (string $pattern, string &$haystack): string {
            $value = '';
            if (preg_match($pattern, $haystack, $match) === 1) {
                $value = trim((string) ($match[1] ?? ''));
            }
            $haystack = trim((string) (preg_replace($pattern, '', $haystack) ?? $haystack));
            return $value;
        };

        $womenProblems = $extract('/^\s*Women Related Problems\s*:\s*(.+)$/im', $remaining);
        $lmp = $extract('/^\s*LMP(?:\s*Days\s*Before)?\s*:\s*(.+)$/im', $remaining);
        $lastBaby = $extract('/^\s*Last Baby\s*:\s*(.+)$/im', $remaining);
        $pregnancyRelated = $extract('/^\s*Pregnancy Related\s*:\s*(.+)$/im', $remaining);

        if ($womenProblems === '') {
            $womenProblems = trim($remaining);
        }

        return [
            'women_related_problems' => $womenProblems,
            'women_lmp' => $lmp,
            'women_last_baby' => $lastBaby,
            'women_pregnancy_related' => $pregnancyRelated,
        ];
    }
}
