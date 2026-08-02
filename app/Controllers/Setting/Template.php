<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\PathLabModel;

class Template extends BaseController
{

    private const PERMISSIONS = [
        'template.pathology',
        'template.ultrasound',
        'template.xray',
        'template.ct',
        'template.mri',
        'template.echo',
        'template.discharge',
        'template.opd_print',
        'template.pathology_print',
        'template.diagnosis_print',
        'template.document_print',
        'template.ipd_document',
    ];

    public function __construct()
    {
        $this->db = db_connect();
        helper(['form']);
    }

    private function requirePermission(string $permission)
    {
        $user = auth()->user();
        if (! $user || ! $user->can($permission)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'update' => 0,
                    'showcontent' => 'Permission denied',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        return null;
    }

    private function requireAnyPermission(array $permissions)
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return null;
            }
        }

        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'update' => 0,
                'showcontent' => 'Permission denied',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setStatusCode(403)->setBody('Forbidden');
    }

    private function permissionForModality(int $modality): ?string
    {
        $map = [
            1 => 'template.ultrasound',
            2 => 'template.mri',
            3 => 'template.xray',
            4 => 'template.ct',
            6 => 'template.echo',
        ];

        return $map[$modality] ?? null;
    }

        private function buildDefaultPathologyTemplate(string $reportName): string
        {
                $name = strtolower(trim($reportName));
                $isCbc = str_contains($name, 'cbc') || str_contains($name, 'complete blood count') || str_contains($name, 'hemogram');

                if (! $isCbc) {
                        return '';
                }

                return <<<HTML
<p><strong>COMPLETE BLOOD COUNT (CBC)</strong></p>
<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr style="background:#f4f6f8;">
            <th style="text-align:left;">Investigation</th>
            <th style="text-align:center;">Result</th>
            <th style="text-align:center;">Unit</th>
            <th style="text-align:center;">Reference Range</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Hemoglobin (Hb)</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">g/dL</td><td style="text-align:center;">12.0 - 16.0</td></tr>
        <tr><td>Total WBC Count (TLC)</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">/cumm</td><td style="text-align:center;">4,000 - 11,000</td></tr>
        <tr><td>Neutrophils</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">%</td><td style="text-align:center;">40 - 75</td></tr>
        <tr><td>Lymphocytes</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">%</td><td style="text-align:center;">20 - 40</td></tr>
        <tr><td>Eosinophils</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">%</td><td style="text-align:center;">1 - 6</td></tr>
        <tr><td>Monocytes</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">%</td><td style="text-align:center;">2 - 10</td></tr>
        <tr><td>Basophils</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">%</td><td style="text-align:center;">0 - 2</td></tr>
        <tr><td>RBC Count</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">million/cumm</td><td style="text-align:center;">4.0 - 5.5</td></tr>
        <tr><td>PCV (HCT)</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">%</td><td style="text-align:center;">36 - 46</td></tr>
        <tr><td>MCV</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">fL</td><td style="text-align:center;">80 - 100</td></tr>
        <tr><td>MCH</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">pg</td><td style="text-align:center;">27 - 32</td></tr>
        <tr><td>MCHC</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">g/dL</td><td style="text-align:center;">31 - 36</td></tr>
        <tr><td>Platelet Count</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">lakh/cumm</td><td style="text-align:center;">1.5 - 4.5</td></tr>
        <tr><td>ESR</td><td style="text-align:center;">&nbsp;</td><td style="text-align:center;">mm/hr</td><td style="text-align:center;">0 - 20</td></tr>
    </tbody>
</table>
<p><strong>Peripheral Smear:</strong> Normocytic normochromic RBCs. No hemoparasite seen.</p>
HTML;
        }

    private function requireModalityPermission(int $modality)
    {
        $permission = $this->permissionForModality($modality);
        if ($permission === null) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'update' => 0,
                    'showcontent' => 'Invalid modality',
                ]);
            }

            return $this->response->setStatusCode(400)->setBody('Invalid modality');
        }

        return $this->requirePermission($permission);
    }

    private function buildRadiologyTemplatePayload(int $modality): array
    {
        $payload = [
            'template_name' => trim((string) $this->request->getPost('input_Reportname')),
            'title' => trim((string) $this->request->getPost('group_id')),
            'charge_id' => (int) $this->request->getPost('charge_id'),
            'Findings' => (string) $this->request->getPost('HTMLData'),
            'Impression' => (string) $this->request->getPost('Impression'),
            'modality' => $modality,
        ];

        if ($this->db->fieldExists('keywords', 'radiology_ultrasound_template')) {
            $payload['keywords'] = trim((string) $this->request->getPost('keywords'));
        }

        if ($this->db->fieldExists('impression_cat', 'radiology_ultrasound_template')) {
            $payload['impression_cat'] = trim((string) $this->request->getPost('impression_cat'));
        }

        return $payload;
    }

    private function resolveBridgeGatewayConfig(): array
    {
        $gwUrl   = '';
        $gwToken = '';
        $hfrId   = '';

        if ($this->db->tableExists('hospital_setting')) {
            $hsRows = $this->db->table('hospital_setting')
                ->select('s_name, s_value')
                ->whereIn('s_name', ['EATRIA_BRIDGE_URL', 'EATRIA_BRIDGE_TOKEN', 'ABDM_HFR_ID', 'H_HFR_ID'])
                ->get()
                ->getResultArray();

            foreach ($hsRows as $hsRow) {
                $sName  = (string) ($hsRow['s_name'] ?? '');
                $sValue = trim((string) ($hsRow['s_value'] ?? ''));

                if ($sName === 'EATRIA_BRIDGE_URL') {
                    $gwUrl = $sValue;
                }
                if ($sName === 'EATRIA_BRIDGE_TOKEN') {
                    $gwToken = $sValue;
                }
                if ($sName === 'ABDM_HFR_ID') {
                    $hfrId = $sValue;
                }
                if ($sName === 'H_HFR_ID' && $hfrId === '') {
                    $hfrId = $sValue;
                }
            }
        }

        return [
            'url' => rtrim($gwUrl !== '' ? $gwUrl : 'https://abdm-bridge.e-atria.in/api', '/'),
            'token' => $gwToken,
            'hfr_id' => $hfrId,
        ];
    }

    private function bridgeGet(string $baseUrl, string $token, string $path, array $query = []): array
    {
        $base = rtrim($baseUrl, '/');
        $normalizedPath = '/' . ltrim($path, '/');

        $baseHasApiSuffix = (bool) preg_match('#/api$#i', $base);
        $pathHasApiPrefix = str_starts_with($normalizedPath, '/api/');

        // Support both config styles:
        // 1) baseUrl = https://host/api + path=/v3/...
        // 2) baseUrl = https://host     + path=/api/v3/...
        if ($baseHasApiSuffix && $pathHasApiPrefix) {
            $normalizedPath = substr($normalizedPath, 4); // drop leading /api
            if ($normalizedPath === '') {
                $normalizedPath = '/';
            }
        } elseif (! $baseHasApiSuffix && ! $pathHasApiPrefix) {
            $normalizedPath = '/api' . $normalizedPath;
        }

        $url = $base . $normalizedPath;
        if (! empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $raw = (string) curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = (string) curl_error($ch);
        curl_close($ch);

        $decoded = json_decode($raw, true);

        return [
            'url' => $url,
            'http_code' => $httpCode,
            'curl_error' => $curlErr,
            'raw' => $raw,
            'json' => is_array($decoded) ? $decoded : [],
        ];
    }

    private function escHtmlValue(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function buildPathologyMasterTemplateHtml(string $panelName, array $components): string
    {
        $safePanel = $this->escHtmlValue(trim($panelName) !== '' ? $panelName : 'Pathology Panel');

        if (empty($components)) {
            return '<p><strong>' . $safePanel . '</strong></p>'
                . '<p><em>No component rows returned from gateway master mapping.</em></p>';
        }

        $rows = '';
        foreach ($components as $component) {
            $componentName = $this->escHtmlValue((string) ($component['component_test_name'] ?? ''));
            $componentCode = $this->escHtmlValue((string) ($component['component_code'] ?? ''));
            $unit          = $this->escHtmlValue((string) ($component['unit'] ?? ''));
            $property      = $this->escHtmlValue((string) ($component['component_property'] ?? ''));
            $system        = $this->escHtmlValue((string) ($component['component_system'] ?? ''));
            $scale         = $this->escHtmlValue((string) ($component['component_scale'] ?? ''));

            $metaBits = [];
            if ($componentCode !== '') {
                $metaBits[] = 'LOINC ' . $componentCode;
            }
            if ($property !== '') {
                $metaBits[] = $property;
            }
            if ($system !== '') {
                $metaBits[] = $system;
            }
            if ($scale !== '') {
                $metaBits[] = $scale;
            }

            $metaText = $this->escHtmlValue(implode(' | ', $metaBits));

            $rows .= '<tr>'
                . '<td>' . ($componentName !== '' ? $componentName : '&nbsp;') . '</td>'
                . '<td style="text-align:center;">&nbsp;</td>'
                . '<td style="text-align:center;">' . ($unit !== '' ? $unit : '&nbsp;') . '</td>'
                . '<td style="text-align:center;">&nbsp;</td>'
                . '<td style="font-size:11px;">' . ($metaText !== '' ? $metaText : '&nbsp;') . '</td>'
                . '</tr>';
        }

        return '<p><strong>' . $safePanel . '</strong></p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="6" cellspacing="0">'
            . '<thead>'
            . '<tr style="background:#f4f6f8;">'
            . '<th style="text-align:left;">Investigation</th>'
            . '<th style="text-align:center;">Result</th>'
            . '<th style="text-align:center;">Unit</th>'
            . '<th style="text-align:center;">Reference Range</th>'
            . '<th style="text-align:left;">Code / Property</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    private function normalizePanelTokenCode(string $raw, int $fallbackIndex = 0): string
    {
        $code = strtoupper(trim($raw));
        $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';

        if ($code === '') {
            $code = 'T' . max(1, $fallbackIndex);
        }

        if (strlen($code) > 24) {
            $code = substr($code, 0, 24);
        }

        return $code;
    }

    private function buildPathologyPanelTokenTemplateHtml(string $panelName, array $tests): string
    {
        $safePanel = $this->escHtmlValue(trim($panelName) !== '' ? $panelName : 'Pathology Panel');

        $rows = '';
        foreach ($tests as $row) {
            $name = $this->escHtmlValue((string) ($row['test_name'] ?? ''));
            $tokenCode = $this->normalizePanelTokenCode((string) ($row['test_code'] ?? ''));
            $token = '{' . $tokenCode . '}';
            $unit = $this->escHtmlValue((string) ($row['unit'] ?? ''));

            $rows .= '<tr>'
                . '<td>' . ($name !== '' ? $name : '&nbsp;') . '</td>'
                . '<td>' . $this->escHtmlValue($token) . '</td>'
                . '<td>' . ($unit !== '' ? $unit : '&nbsp;') . '</td>'
                . '<td>&nbsp;</td>'
                . '</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="4"><em>No panel components available.</em></td></tr>';
        }

        return '<p><strong>' . $safePanel . '</strong></p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="6" cellspacing="0">'
            . '<thead>'
            . '<tr style="background:#f4f6f8;">'
            . '<th style="text-align:left;">Test Name</th>'
            . '<th style="text-align:left;">Result</th>'
            . '<th style="text-align:left;">Unit</th>'
            . '<th style="text-align:left;">Bio. Ref. Interval</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    public function index()
    {
        if ($resp = $this->requireAnyPermission(self::PERMISSIONS)) {
            return $resp;
        }

        return view('Setting/Template/index');
    }

    public function report_list()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $repoColumnsRows = $this->db->query('SHOW COLUMNS FROM lab_repo')->getResultArray();
        $itemColumnsRows = $this->db->query('SHOW COLUMNS FROM hc_items')->getResultArray();
        $repoColumns = array_column($repoColumnsRows, 'Field');
        $itemColumns = array_column($itemColumnsRows, 'Field');

        $updatedExpr = 'NULL';
        foreach (['updated_at', 'modified_at', 'loinc_synced_at', 'created_at'] as $column) {
            if (in_array($column, $repoColumns, true)) {
                $updatedExpr = 'r.' . $column;
                break;
            }
        }

        $activeExpr = '1';
        if (in_array('active', $repoColumns, true)) {
            $activeExpr = 'CASE WHEN COALESCE(r.active,1)=1 THEN 1 ELSE 0 END';
        } elseif (in_array('status', $repoColumns, true)) {
            $activeExpr = 'CASE WHEN COALESCE(r.status,1)=1 THEN 1 ELSE 0 END';
        } elseif (in_array('active', $itemColumns, true)) {
            $activeExpr = 'CASE WHEN COALESCE(i.active,1)=1 THEN 1 ELSE 0 END';
        }

        $sql = "select r.mstRepoKey as panel_id,
                       COALESCE(r.Title, '') as panel_name,
                       'PANEL' as panel_type,
                       COALESCE(g.RepoGrp, 'General') as test_type,
                       COALESCE(r.Title, '') as description,
                       COALESCE(x.items_count, 0) as items_count,
                       {$activeExpr} as is_active,
                       {$updatedExpr} as updated_on
                from lab_repo r
                left join hc_items i on r.charge_id = i.id
                left join lab_rgroups g on r.GrpKey = g.mstRGrpKey
                left join (
                    select mstRepoKey, count(*) as items_count
                    from lab_repotests
                    group by mstRepoKey
                ) x on x.mstRepoKey = r.mstRepoKey
                where r.mstRepoKey > 0
                  and (i.id is null or i.itype in (5,6))
                order by r.mstRepoKey asc";
        $query = $this->db->query($sql);
        $data['labReport_master'] = $query->getResult();

        $groupQuery = $this->db->query('select mstRGrpKey, RepoGrp from lab_rgroups order by RepoGrp asc');
        $data['lab_rgroups'] = $groupQuery->getResult();

                $chargeQuery = $this->db->query("select id, idesc, amount
                        from hc_items
                        where itype in (5,6)
                            and id not in (select charge_id from lab_repo where charge_id > 0)
                        order by idesc asc");
                $data['charge_items'] = $chargeQuery->getResult();

        return view('PathLab_Report/lab_report_list', $data);
    }

    public function report_ultrasound_list(int $modality = 2)
    {
        if ($resp = $this->requireModalityPermission($modality)) {
            return $resp;
        }

        $sql = "select * from radiology_ultrasound_template where modality=" . (int) $modality;
        $query = $this->db->query($sql);
        $data['labReport_master'] = $query->getResult();
        $data['modality'] = $modality;

        return view('PathLab_Report/ultrasound_template_list', $data);
    }

    public function report_test_list(int $repoId)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $testColumnsRows = $this->db->query('SHOW COLUMNS FROM lab_tests')->getResultArray();
        $testColumns = array_column($testColumnsRows, 'Field');
        $codeExpr = in_array('loinc_code', $testColumns, true) ? 'COALESCE(t.loinc_code, "")' : '""';

        $sql = "select j.id,
                       r.mstRepoKey,
                       COALESCE(r.Title, '') as panel_name,
                       t.mstTestKey,
                       t.Test,
                       COALESCE(t.TestID, '') as short_name,
                       {$codeExpr} as component_code,
                       t.Result,
                       j.EOrder
            from lab_repo r join lab_repotests j join lab_tests t
            on r.mstRepoKey=j.mstRepoKey and j.mstTestKey=t.mstTestKey
            where r.mstRepoKey=" . (int) $repoId . " order by j.EOrder";
        $query = $this->db->query($sql);
        $data['lab_Rep_Item_List'] = $query->getResult();
        $data['mstRepoKey'] = $repoId;
        $data['panel_name'] = count($data['lab_Rep_Item_List']) > 0
            ? (string) ($data['lab_Rep_Item_List'][0]->panel_name ?? '')
            : '';

        if ($data['panel_name'] === '') {
            $nameRow = $this->db->table('lab_repo')->select('Title')->where('mstRepoKey', $repoId)->get()->getRow();
            $data['panel_name'] = (string) ($nameRow->Title ?? '');
        }

        return view('PathLab_Report/lab_report_test_list', $data);
    }

    public function test_search_page(int $repoId)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        return view('PathLab_Report/lab_test_search', [
            'repo_id' => $repoId,
        ]);
    }

    public function test_parameter_load(int $mstTestKey, int $mstRepoKey)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $sql = "select * from lab_tests where mstTestKey=" . (int) $mstTestKey;
        $query = $this->db->query($sql);
        $data['lab_test_parameter'] = $query->getResult();

        $sql = "select *, if(option_bold=1,'Bold','') as option_bold_str
            from lab_tests_option where mstTestKey=" . (int) $mstTestKey . " order by sort_id";
        $query = $this->db->query($sql);
        $data['lab_test_option'] = $query->getResult();

        $data['mstRepoKey'] = $mstRepoKey;

        return view('PathLab_Report/lab_report_item_edit', $data);
    }

    public function reportedit_load(int $repoId = 0)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $sql = "select * from lab_repo where mstRepoKey=" . (int) $repoId;
        $query = $this->db->query($sql);
        $data['labReport_master'] = $query->getResult();

        if (! empty($data['labReport_master'])) {
            $existingHtml = trim((string) ($data['labReport_master'][0]->HTMLData ?? ''));
            if ($existingHtml === '') {
                $legacyHtml = trim((string) ($data['labReport_master'][0]->RTFData ?? ''));
                if ($legacyHtml !== '') {
                    $data['labReport_master'][0]->HTMLData = $legacyHtml;
                    $existingHtml = $legacyHtml;
                }
            }

            if ($existingHtml === '') {
                $defaultHtml = $this->buildDefaultPathologyTemplate((string) ($data['labReport_master'][0]->Title ?? ''));
                if ($defaultHtml !== '') {
                    $data['labReport_master'][0]->HTMLData = $defaultHtml;
                }
            }
        }

        $itemId = 0;
        if (! empty($data['labReport_master'])) {
            $itemId = (int) ($data['labReport_master'][0]->charge_id ?? 0);
        }

        $sql = "select * from hc_items where itype in (5,30,6)
            and id not in (select charge_id from lab_repo where charge_id>0 and charge_id<>" . (int) $itemId . ")
            order by idesc";
        $query = $this->db->query($sql);
        $data['hc_items'] = $query->getResult();

        $sql = "select * from lab_rgroups";
        $query = $this->db->query($sql);
        $data['lab_rgroups'] = $query->getResult();

        $sql = "select j.id, r.mstRepoKey, t.mstTestKey, t.Test, t.TestID, t.Result, j.EOrder
            from lab_repo r join lab_repotests j join lab_tests t
            on r.mstRepoKey=j.mstRepoKey and j.mstTestKey=t.mstTestKey
            where r.mstRepoKey=" . (int) $repoId . " order by j.EOrder";
        $query = $this->db->query($sql);
        $data['lab_Rep_Item_List'] = $query->getResult();

        $sql = "select * from color";
        $query = $this->db->query($sql);
        $data['color_name'] = $query->getResult();

        $data['repo_id'] = $repoId;

        // Pass LOINC code for pre-filling the field
        $data['repo_loinc_code'] = '';
        if (! empty($data['labReport_master'])) {
            $data['repo_loinc_code'] = (string) ($data['labReport_master'][0]->loinc_code ?? '');
        }

        return view('PathLab_Report/lab_report_edit', $data);
    }

    public function reportedit_ultrasound_load(int $modality = 2, int $repoId = 0)
    {
        if ($resp = $this->requireModalityPermission($modality)) {
            return $resp;
        }

        $sql = "select * from radiology_ultrasound_template where modality=" . (int) $modality . " and id=" . (int) $repoId;
        $query = $this->db->query($sql);
        $data['labReport_master'] = $query->getResult();

        $sql = "select * from hc_items where itype=" . (int) $modality . " order by idesc";
        $query = $this->db->query($sql);
        $data['hc_items'] = $query->getResult();

        $data['repo_id'] = $repoId;
        $data['modality'] = $modality;

        return view('PathLab_Report/ultrasound_report_edit', $data);
    }

    public function report_update()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update_record' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $repoId = (int) $this->request->getPost('repo_id');
        $inputReportName = trim((string) $this->request->getPost('input_Reportname'));
        $chargeId = (int) $this->request->getPost('charge_id');
        $groupId = (int) $this->request->getPost('group_id');
        $htmlData = (string) $this->request->getPost('HTMLData');

        if (trim($htmlData) === '') {
            $defaultHtml = $this->buildDefaultPathologyTemplate($inputReportName);
            if ($defaultHtml !== '') {
                $htmlData = $defaultHtml;
            }
        }

        if ($repoId <= 0 || $inputReportName === '') {
            return $this->response->setJSON([
                'update_record' => 0,
                'showcontent' => 'Report name is required.',
            ]);
        }

        $pathLab = new PathLabModel();
        $pathLab->updateReport([
            'Title'      => $inputReportName,
            'GrpKey'     => $groupId,
            'charge_id'  => $chargeId,
            'HTMLData'   => $htmlData,
            'RTFData'    => $htmlData,
            'loinc_code' => trim((string) $this->request->getPost('loinc_code')),
        ], $repoId);

        return $this->response->setJSON([
            'update_record' => 1,
            'showcontent' => 'Data Saved successfully',
        ]);
    }

    public function report_insert()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'insertid' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $inputReportName = trim((string) $this->request->getPost('input_Reportname'));
        $chargeId = (int) $this->request->getPost('charge_id');
        $groupId = (int) $this->request->getPost('group_id');
        $htmlData = (string) $this->request->getPost('HTMLData');

        if (trim($htmlData) === '') {
            $defaultHtml = $this->buildDefaultPathologyTemplate($inputReportName);
            if ($defaultHtml !== '') {
                $htmlData = $defaultHtml;
            }
        }

        if ($inputReportName === '') {
            return $this->response->setJSON([
                'insertid' => 0,
                'showcontent' => 'Report name is required.',
            ]);
        }

        $pathLab = new PathLabModel();
        $insertId = $pathLab->insertReport([
            'Title'      => $inputReportName,
            'GrpKey'     => $groupId,
            'charge_id'  => $chargeId,
            'HTMLData'   => $htmlData,
            'RTFData'    => $htmlData,
            'loinc_code' => trim((string) $this->request->getPost('loinc_code')),
        ]);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'showcontent' => $insertId > 0 ? 'Data Saved successfully' : 'Unable to save data',
        ]);
    }

    public function report_delete(int $repoId = 0)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Invalid request',
            ]);
        }

        if ($repoId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Invalid report ID',
            ]);
        }

        $this->db->transStart();
        $this->db->table('lab_repotests')->where('mstRepoKey', $repoId)->delete();
        $this->db->table('lab_repo')->where('mstRepoKey', $repoId)->delete();
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => 'Failed to delete panel',
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'message' => 'Panel deleted successfully',
        ]);
    }

    public function report_ultrasound_insert(int $modality)
    {
        if ($resp = $this->requireModalityPermission($modality)) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'insertid' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $inputReportName = trim((string) $this->request->getPost('input_Reportname'));

        if ($inputReportName === '') {
            return $this->response->setJSON([
                'insertid' => 0,
                'showcontent' => 'Report name is required.',
            ]);
        }

        $pathLab = new PathLabModel();
        $insertId = $pathLab->insertUltrasoundReport($this->buildRadiologyTemplatePayload($modality));

        return $this->response->setJSON([
            'insertid' => $insertId,
            'showcontent' => $insertId > 0 ? 'Data Saved successfully' : 'Unable to save data',
        ]);
    }

    public function report_ultrasound_update(int $modality = 2)
    {
        if ($resp = $this->requireModalityPermission($modality)) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update_record' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $repoId = (int) $this->request->getPost('repo_id');
        $inputReportName = trim((string) $this->request->getPost('input_Reportname'));
        if ($repoId <= 0 || $inputReportName === '') {
            return $this->response->setJSON([
                'update_record' => 0,
                'showcontent' => 'Report name is required.',
            ]);
        }

        $pathLab = new PathLabModel();
        $pathLab->updateUltrasoundReport($this->buildRadiologyTemplatePayload($modality), $repoId);

        return $this->response->setJSON([
            'update_record' => 1,
            'showcontent' => 'Data Saved successfully',
        ]);
    }

    public function report_ultrasound_delete(int $modality = 2, int $repoId = 0)
    {
        if ($resp = $this->requireModalityPermission($modality)) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Invalid request',
            ]);
        }

        if ($repoId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Invalid template ID',
            ]);
        }

        $builder = $this->db->table('radiology_ultrasound_template')
            ->where('id', $repoId)
            ->where('Modality', $modality);

        $exists = $builder->countAllResults(false);
        if ($exists <= 0) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => 0,
                'error' => 'Template not found for selected modality',
            ]);
        }

        $deleted = $builder->delete();
        if (! $deleted) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => 'Failed to delete template',
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'message' => 'Template deleted successfully',
        ]);
    }

    public function test_parameter_edit()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update_value' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $mstTestKey = (int) $this->request->getPost('mstTestKey');
        $pathLab = new PathLabModel();

        $data = [
            'Test' => (string) $this->request->getPost('input_Test_name'),
            'TestID' => (string) $this->request->getPost('input_test_code'),
            'Result' => (string) $this->request->getPost('input_Default'),
            'Formula' => (string) $this->request->getPost('input_Formula'),
            'VRule' => (string) $this->request->getPost('input_Validation'),
            'VMsg' => (string) $this->request->getPost('input_Message'),
            'Unit' => (string) $this->request->getPost('input_Unit'),
            'FixedNormals' => (string) $this->request->getPost('input_Fixed'),
            'isGenderSpecific' => (int) $this->request->getPost('input_isChecked'),
            'FixedNormalsWomen' => (string) $this->request->getPost('input_FixedNormalsWomen'),
        ];

        if ($this->db->fieldExists('loinc_code', 'lab_tests')) {
            $data['loinc_code'] = trim((string) $this->request->getPost('input_loinc_code'));
        }
        if ($this->db->fieldExists('loinc_property', 'lab_tests')) {
            $data['loinc_property'] = trim((string) $this->request->getPost('input_loinc_property'));
        }
        if ($this->db->fieldExists('loinc_system', 'lab_tests')) {
            $data['loinc_system'] = trim((string) $this->request->getPost('input_loinc_system'));
        }
        if ($this->db->fieldExists('loinc_scale', 'lab_tests')) {
            $data['loinc_scale'] = trim((string) $this->request->getPost('input_loinc_scale'));
        }

        $pathLab->updateItemParameter($data, $mstTestKey);

        return $this->response->setJSON([
            'update_value' => 1,
            'showcontent' => 'Data Saved successfully',
        ]);
    }

    public function test_parameter_add()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'insert_id' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $pathLab = new PathLabModel();
        $data = [
            'Test' => (string) $this->request->getPost('input_Test_name'),
            'TestID' => (string) $this->request->getPost('input_test_code'),
            'Result' => (string) $this->request->getPost('input_Default'),
            'Formula' => (string) $this->request->getPost('input_Formula'),
            'VRule' => (string) $this->request->getPost('input_Validation'),
            'VMsg' => (string) $this->request->getPost('input_Message'),
            'Unit' => (string) $this->request->getPost('input_Unit'),
            'FixedNormals' => (string) $this->request->getPost('input_Fixed'),
        ];

        if ($this->db->fieldExists('loinc_code', 'lab_tests')) {
            $data['loinc_code'] = trim((string) $this->request->getPost('input_loinc_code'));
        }
        if ($this->db->fieldExists('loinc_property', 'lab_tests')) {
            $data['loinc_property'] = trim((string) $this->request->getPost('input_loinc_property'));
        }
        if ($this->db->fieldExists('loinc_system', 'lab_tests')) {
            $data['loinc_system'] = trim((string) $this->request->getPost('input_loinc_system'));
        }
        if ($this->db->fieldExists('loinc_scale', 'lab_tests')) {
            $data['loinc_scale'] = trim((string) $this->request->getPost('input_loinc_scale'));
        }

        $insertId = $pathLab->insertItemParameter($data);

        return $this->response->setJSON([
            'insert_id' => $insertId,
            'showcontent' => $insertId > 0 ? 'Data Saved successfully' : 'Unable to save data',
        ]);
    }

    public function test_parameter_option_add()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'insert_id' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $mstTestKey = (int) $this->request->getPost('mstTestKey');
        $pathLab = new PathLabModel();

        $nextSort = 1;
        $row = $this->db->table('lab_tests_option')
            ->selectMax('sort_id', 'max_sort')
            ->where('mstTestKey', $mstTestKey)
            ->get()
            ->getRowArray();
        if (! empty($row['max_sort'])) {
            $nextSort = (int) $row['max_sort'] + 1;
        }

        $insertId = $pathLab->insertItemParameterOption([
            'mstTestKey' => $mstTestKey,
            'option_value' => (string) $this->request->getPost('input_op_value'),
            'option_bold' => (int) $this->request->getPost('chk_bold'),
            'sort_id' => $nextSort,
        ]);

        return $this->response->setJSON([
            'insert_id' => $insertId,
            'showcontent' => $insertId > 0 ? 'Data Saved successfully' : 'Unable to save data',
            'option_content' => $this->renderOptionTable($mstTestKey),
        ]);
    }

    public function remove_test_option(int $optionId, int $mstTestKey)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $this->db->table('lab_tests_option')->where('id', $optionId)->delete();

        return $this->response->setJSON([
            'insert_id' => 1,
            'showcontent' => 'Removed successfully',
            'option_content' => $this->renderOptionTable($mstTestKey),
        ]);
    }

    public function change_sort(int $mstTestKey, int $optionId, int $current, int $changeOptionId, int $change)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $pathLab = new PathLabModel();
        $pathLab->updateItemParameterOption(['sort_id' => 0], $optionId);
        $pathLab->updateItemParameterOption(['sort_id' => $current], $changeOptionId);
        $pathLab->updateItemParameterOption(['sort_id' => $change], $optionId);

        return $this->response->setJSON([
            'insert_id' => 1,
            'showcontent' => 'Updated successfully',
            'option_content' => $this->renderOptionTable($mstTestKey),
        ]);
    }

    public function change_sort_item(int $repoId, int $optionId, int $current, int $changeOptionId, int $change)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $pathLab = new PathLabModel();
        $pathLab->updateItemSortorder(['EOrder' => 0], $optionId);
        $pathLab->updateItemSortorder(['EOrder' => $current], $changeOptionId);
        $pathLab->updateItemSortorder(['EOrder' => $change], $optionId);

        return $this->report_test_list($repoId);
    }

    public function add_test_repo(int $repoId, int $testId)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $exists = $this->db->table('lab_repotests')
            ->where('mstRepoKey', $repoId)
            ->where('mstTestKey', $testId)
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON([
                'insertid' => 0,
                'showcontent' => 'Already Added',
            ]);
        }

        $row = $this->db->table('lab_repotests')
            ->selectMax('EOrder', 'max_order')
            ->where('mstRepoKey', $repoId)
            ->get()
            ->getRowArray();
        $nextOrder = (int) ($row['max_order'] ?? 0) + 1;

        $pathLab = new PathLabModel();
        $insertId = $pathLab->insertItemSortorder([
            'mstRepoKey' => $repoId,
            'mstTestKey' => $testId,
            'EOrder' => $nextOrder,
        ]);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'showcontent' => $insertId > 0 ? 'Add successfully' : 'Unable to add',
        ]);
    }

    public function remove_test_item(int $repoId, int $testId)
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $this->db->table('lab_repotests')
            ->where('mstRepoKey', $repoId)
            ->where('mstTestKey', $testId)
            ->delete();
    }

    public function test_item_search()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $testName = (string) $this->request->getPost('input_Test_name');
        $repoId = (int) $this->request->getPost('repo_id');

        $sql = "select * from lab_tests where Test like '%" . $this->db->escapeLikeString($testName) . "%'";
        $query = $this->db->query($sql);
        $searchResult = $query->getResult();

        $html = '<table class="table table-sm">';
        foreach ($searchResult as $row) {
            $html .= '<tr><td><a href="javascript:add_test(' . (int) $repoId . ',' . (int) $row->mstTestKey . ');">' . esc($row->Test) . '</a></td><td>[ ' . esc($row->TestID) . ' ]</td></tr>';
        }
        $html .= '</table>';

        return $this->response->setBody($html);
    }

    // -------------------------------------------------------------------------
    // LOINC Mapping Admin
    // -------------------------------------------------------------------------

    /**
     * GET Lab_Admin/loinc_panel
     * Show all lab panels (lab_repo) and their tests (lab_tests) with LOINC codes.
     */
    public function loincPanel()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $panels = $this->db->table('lab_repo')
            ->select('mstRepoKey, Title, loinc_code, loinc_synced_at')
            ->orderBy('Title', 'ASC')
            ->get()
            ->getResultArray();

        $tests = $this->db->table('lab_tests lt')
            ->select('lt.mstTestKey, lt.Test, lt.Unit, lt.loinc_code, lt.loinc_property, lt.loinc_system, lt.loinc_scale, lt.loinc_synced_at, lrt.mstRepoKey')
            ->join('lab_repotests lrt', 'lrt.mstTestKey = lt.mstTestKey', 'left')
            ->orderBy('lrt.mstRepoKey, lrt.EOrder', 'ASC')
            ->get()
            ->getResultArray();

        // Group tests by repo id
        $testsByRepo = [];
        foreach ($tests as $t) {
            $rid = (int) ($t['mstRepoKey'] ?? 0);
            $testsByRepo[$rid][] = $t;
        }

        $data = [
            'title'       => 'LOINC Mapping — Pathology',
            'panels'      => $panels,
            'testsByRepo' => $testsByRepo,
        ];

        return view('PathLab_Report/loinc_panel', $data);
    }

    /**
     * POST Lab_Admin/loinc_sync  (AJAX)
     * Trigger incremental LOINC sync from Bridge API via the Spark command.
     * Runs synchronously for up to 60 s — suitable for admin-initiated syncs.
     */
    public function loincSync()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'AJAX only']);
        }
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $since = trim((string) ($this->request->getPost('since') ?? ''));

        // Build command
        $sparkPath = ROOTPATH . 'spark';
        $cmd = PHP_BINARY . ' ' . escapeshellarg($sparkPath) . ' abdm:sync-pathology-loinc';
        if ($since !== '') {
            $cmd .= ' --since=' . escapeshellarg($since);
        }

        $output = [];
        $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);

        return $this->response->setJSON([
            'ok'       => $exitCode === 0 ? 1 : 0,
            'output'   => implode("\n", $output),
            'exitCode' => $exitCode,
        ]);
    }

    /**
     * POST Lab_Admin/loinc_update  (AJAX)
     * Manually update LOINC code for a single lab_test or lab_repo record.
     *
     * POST params:
     *   type      = 'test' | 'panel'
     *   id        = mstTestKey | mstRepoKey
     *   loinc_code, loinc_property, loinc_system, loinc_scale  (test only)
     */
    public function loincUpdate()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'AJAX only']);
        }
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $type      = trim((string) ($this->request->getPost('type') ?? ''));
        $id        = (int) ($this->request->getPost('id') ?? 0);
        $loincCode = trim((string) ($this->request->getPost('loinc_code') ?? ''));

        if ($id <= 0 || ! in_array($type, ['test', 'panel'], true)) {
            return $this->response->setJSON(['ok' => 0, 'error' => 'Invalid parameters']);
        }

        $now = date('Y-m-d H:i:s');

        if ($type === 'panel') {
            $this->db->table('lab_repo')->where('mstRepoKey', $id)->update([
                'loinc_code'      => $loincCode,
                'loinc_synced_at' => $now,
            ]);
        } else {
            $this->db->table('lab_tests')->where('mstTestKey', $id)->update([
                'loinc_code'      => $loincCode,
                'loinc_property'  => trim((string) ($this->request->getPost('loinc_property') ?? '')),
                'loinc_system'    => trim((string) ($this->request->getPost('loinc_system') ?? '')),
                'loinc_scale'     => trim((string) ($this->request->getPost('loinc_scale') ?? '')),
                'loinc_synced_at' => $now,
            ]);
        }

        return $this->response->setJSON(['ok' => 1]);
    }

    /**
     * GET Lab_Admin/pathology_masters_search?q=CBC
     * Returns JSON list of matching panel names (+ LOINC codes).
     * Uses the same direct-curl pattern as Medical::fetchAllGatewayDrugMasters().
     * Falls back to local lab_repo search if Bridge API is unavailable/unconfigured.
     */
    public function pathologyMastersSearch()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $subCategory = trim((string) ($this->request->getGet('sub_category') ?? ''));
        $source = strtolower(trim((string) ($this->request->getGet('source') ?? '')));
        $bridgeOnly = in_array($source, ['api', 'bridge', 'gateway', 'bridge_only'], true);
        $panelOnly = in_array(strtolower(trim((string) ($this->request->getGet('panel_only') ?? ''))), ['1', 'true', 'yes'], true);
        if (strlen($q) < 1) {
            return $this->response->setJSON([]);
        }

        $results = [];
        $bridgeAttempted = false;
        $bridgeError = '';
        $bridgeHttpCode = 0;
        $bridgeUrl = '';
        $bridgeRaw = '';
        $mastersSucceeded = false;

        $gw = $this->resolveBridgeGatewayConfig();
        $gwUrl = (string) ($gw['url'] ?? '');
        $gwToken = (string) ($gw['token'] ?? '');
        $hfrId = (string) ($gw['hfr_id'] ?? '');

        // ── Try Bridge API ────────────────────────────────────────────────────
        if ($gwToken !== '') {
            try {
                $bridgeAttempted = true;
                $query = [
                    'q'     => $q,
                    'limit' => 20,
                    'offset' => 0,
                    'include_inactive' => 0,
                ];
                if ($subCategory !== '') {
                    $query['sub_category'] = $subCategory;
                }
                if ($hfrId !== '') {
                    $query['hfr_id'] = $hfrId;
                }

                if ($panelOnly) {
                    $query['panel_type'] = 'PANEL';
                }

                $resp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/panels', $query);
                $code = (int) ($resp['http_code'] ?? 0);
                $cerr = (string) ($resp['curl_error'] ?? '');
                $raw  = (string) ($resp['raw'] ?? '');
                $body = (array) ($resp['json'] ?? []);
                $bridgeHttpCode = $code;
                $bridgeUrl = (string) ($resp['url'] ?? '');
                $bridgeRaw = $raw;

                if ($cerr === '' && $code >= 200 && $code < 300) {
                    if (is_array($body) && (int) ($body['ok'] ?? 0) === 1) {
                        $mastersSucceeded = true;
                        $panelNameSet = [];

                        $isLikelyPanelName = static function (string $name): bool {
                            $trim = trim($name);
                            if ($trim === '') {
                                return false;
                            }

                            // Exclude low-level analyte-style names commonly seen in component datasets.
                            if ((bool) preg_match('/-(mcnc|scnc|rto|srto|mrto|ccnc|arb|acnc)\b/i', $trim)) {
                                return false;
                            }

                            // Keep strong panel/meta-panel cues and common panel abbreviations.
                            if ((bool) preg_match('/\b(meta\s*panel|metapanel|panel|profile|screen|function|count|cbc|lft|kft|tft)\b/i', $trim)) {
                                return true;
                            }

                            // Names with explicit acronym in brackets are often panel labels.
                            if ((bool) preg_match('/\([A-Za-z0-9\-]{2,12}\)/', $trim)) {
                                return true;
                            }

                            return false;
                        };

                        foreach ((array) ($body['items'] ?? []) as $item) {
                            $name = trim((string) ($item['panel_name'] ?? $item['test_name'] ?? $item['display_name'] ?? $item['name'] ?? ''));
                            if ($name !== '') {
                                if ($panelOnly) {
                                    $itemType = strtolower(trim((string) ($item['panel_type'] ?? $item['type'] ?? $item['test_type'] ?? $item['entity_type'] ?? $item['master_type'] ?? '')));
                                    $isPanelType = in_array($itemType, ['panel', 'meta_panel', 'metapanel', 'meta-panel'], true);
                                    $isPanelName = $isLikelyPanelName($name);
                                    if (! $isPanelType && ! $isPanelName) {
                                        continue;
                                    }
                                    $panelNameSet[$name] = [
                                        'name' => $name,
                                        'test_name' => (string) ($item['panel_name'] ?? $item['test_name'] ?? $name),
                                        'display_name' => (string) ($item['description'] ?? $item['display_name'] ?? ''),
                                        'loinc_code' => (string) ($item['code'] ?? $item['loinc_code'] ?? ''),
                                        'code_system' => (string) ($item['code_system'] ?? ''),
                                        'sub_category' => (string) ($item['sub_category'] ?? ''),
                                        'standard_rate' => (string) ($item['standard_rate'] ?? ''),
                                        'master_id' => (int) ($item['id'] ?? 0),
                                        'updated_at' => (string) ($item['updated_at'] ?? ''),
                                        'source' => 'bridge',
                                    ];
                                    continue;
                                }

                                $results[] = [
                                    'name'         => $name,
                                    'test_name'    => (string) ($item['panel_name'] ?? $item['test_name'] ?? $name),
                                    'display_name' => (string) ($item['description'] ?? $item['display_name'] ?? ''),
                                    'loinc_code'   => (string) ($item['code'] ?? $item['loinc_code'] ?? ''),
                                    'code_system'  => (string) ($item['code_system'] ?? ''),
                                    'sub_category' => (string) ($item['sub_category'] ?? ''),
                                    'standard_rate' => (string) ($item['standard_rate'] ?? ''),
                                    'master_id'    => (int) ($item['id'] ?? 0),
                                    'updated_at'   => (string) ($item['updated_at'] ?? ''),
                                    'source'       => 'bridge',
                                ];
                            }
                        }

                        if ($panelOnly && !empty($panelNameSet)) {
                            $results = array_values($panelNameSet);
                        }
                    } else {
                        $bridgeError = trim((string) ($body['message'] ?? $body['error_code'] ?? 'Bridge returned ok=0'));
                        log_message('debug', '[pathologyMastersSearch] Bridge API ok=' . ($body['ok'] ?? '?') .
                            ' code=' . $code . ' body=' . substr($raw, 0, 300));
                    }
                } else {
                    $bridgeError = $cerr !== '' ? $cerr : 'HTTP ' . $code;
                    log_message('warning', '[pathologyMastersSearch] Bridge API curl_err=' . $cerr . ' http=' . $code .
                        ' body=' . substr($raw, 0, 300));
                }
            } catch (\Throwable $e) {
                $bridgeError = $e->getMessage();
                log_message('warning', '[pathologyMastersSearch] Bridge API exception: ' . $e->getMessage());
            }

            // If panel lookup returned nothing, try dedicated component master search for non-panel contexts.
            if (empty($results) && ! $panelOnly) {
                try {
                    $compQuery = [
                        'q' => $q,
                        'limit' => 50,
                        'offset' => 0,
                        'include_inactive' => 0,
                    ];
                    if ($hfrId !== '') {
                        $compQuery['hfr_id'] = $hfrId;
                    }

                    $compResp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/component-masters', $compQuery);
                    $compCode = (int) ($compResp['http_code'] ?? 0);
                    $compErr = (string) ($compResp['curl_error'] ?? '');
                    $compRaw = (string) ($compResp['raw'] ?? '');
                    $compBody = (array) ($compResp['json'] ?? []);

                    if ($compErr === '' && $compCode >= 200 && $compCode < 300 && (int) ($compBody['ok'] ?? 0) === 1) {
                        $componentsByName = [];
                        foreach ((array) ($compBody['items'] ?? []) as $item) {
                            $componentName = trim((string) ($item['component_name'] ?? $item['test_name'] ?? $item['name'] ?? ''));
                            if ($componentName !== '') {
                                $componentsByName[$componentName] = [
                                    'name' => $componentName,
                                    'test_name' => $componentName,
                                    'display_name' => '',
                                    'loinc_code' => (string) ($item['code'] ?? ''),
                                    'code_system' => (string) ($item['code_system'] ?? ''),
                                    'sub_category' => (string) ($item['sub_category'] ?? 'PATHOLOGY'),
                                    'standard_rate' => '',
                                    'master_id' => (int) ($item['id'] ?? 0),
                                    'updated_at' => (string) ($item['updated_at'] ?? ''),
                                    'source' => 'bridge',
                                ];
                            }
                        }

                        $existingNames = [];
                        foreach ($results as $r) {
                            $existingNames[strtolower(trim((string) ($r['name'] ?? '')))] = true;
                        }

                        foreach ($componentsByName as $componentName => $row) {
                            $lk = strtolower($componentName);
                            if (isset($existingNames[$lk])) {
                                continue;
                            }
                            $results[] = $row;
                        }
                    } elseif ($bridgeError === '') {
                        $bridgeError = $compErr !== '' ? $compErr : ('HTTP ' . $compCode);
                        $bridgeHttpCode = $compCode;
                        $bridgeUrl = (string) ($compResp['url'] ?? $bridgeUrl);
                        $bridgeRaw = $compRaw;
                    }
                } catch (\Throwable $e) {
                    if ($bridgeError === '') {
                        $bridgeError = $e->getMessage();
                    }
                    log_message('warning', '[pathologyMastersSearch] components fallback exception: ' . $e->getMessage());
                }
            }
        } else {
            $bridgeError = 'EATRIA_BRIDGE_TOKEN not configured';
        }

        // ── Fallback: local lab_repo search ───────────────────────────────────
        if (empty($results) && ! $bridgeOnly) {
            try {
                $localRows = $this->db->table('lab_repo r')
                    ->select("r.mstRepoKey, r.Title, COALESCE(g.RepoGrp, '') AS sub_category, IFNULL(r.loinc_code, '') AS loinc_code")
                    ->join('lab_rgroups g', 'g.mstRGrpKey = r.GrpKey', 'left')
                    ->like('r.Title', $q)
                    ->orderBy('Title', 'ASC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();
            } catch (\Throwable $e) {
                $localRows = $this->db->table('lab_repo r')
                    ->select("r.mstRepoKey, r.Title, COALESCE(g.RepoGrp, '') AS sub_category")
                    ->join('lab_rgroups g', 'g.mstRGrpKey = r.GrpKey', 'left')
                    ->like('r.Title', $q)
                    ->orderBy('Title', 'ASC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();
            }

            foreach ($localRows as $row) {
                $results[] = [
                    'name'         => (string) ($row['Title'] ?? ''),
                    'test_name'    => (string) ($row['Title'] ?? ''),
                    'display_name' => '',
                    'loinc_code'   => (string) ($row['loinc_code'] ?? ''),
                    'code_system'  => '',
                    'sub_category' => (string) ($row['sub_category'] ?? ''),
                    'standard_rate' => '',
                    'master_id'    => 0,
                    'updated_at'   => '',
                    'source'       => 'local',
                ];
            }
        }

        if (empty($results) && $bridgeOnly) {
            if ($panelOnly && $mastersSucceeded) {
                return $this->response->setJSON([
                    'ok' => 1,
                    'items' => [],
                    'source' => 'bridge',
                ]);
            }

            if ($bridgeError !== '' || ! $bridgeAttempted) {
                return $this->response->setStatusCode(502)->setJSON([
                    'ok' => 0,
                    'items' => [],
                    'source' => 'bridge',
                    'error' => $bridgeError !== '' ? $bridgeError : 'Bridge request not attempted',
                    'http_code' => $bridgeHttpCode,
                    'url' => $bridgeUrl,
                    'details' => substr($bridgeRaw, 0, 300),
                ]);
            }

            return $this->response->setJSON([
                'ok' => 1,
                'items' => [],
                'source' => 'bridge',
            ]);
        }

        return $this->response->setJSON($results);
    }

    /**
     * GET Lab_Admin/pathology_master_template?parent_test=CBC
     * Fetches panel components from Bridge master data and returns generated HTML template.
     */
    public function pathologyMasterTemplate()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $parentTest = trim((string) ($this->request->getGet('parent_test') ?? ''));
        if ($parentTest === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'parent_test is required',
            ]);
        }

        $gw = $this->resolveBridgeGatewayConfig();
        $gwUrl = (string) ($gw['url'] ?? '');
        $gwToken = (string) ($gw['token'] ?? '');
        $hfrId = (string) ($gw['hfr_id'] ?? '');

        if ($gwToken === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'EATRIA_BRIDGE_TOKEN not configured',
            ]);
        }

        $masterMeta = [
            'name' => $parentTest,
            'loinc_code' => '',
            'sub_category' => '',
            'code_system' => '',
            'standard_rate' => '',
            'master_id' => 0,
        ];

        try {
            $masterQuery = [
                'q' => $parentTest,
                'limit' => 25,
                'offset' => 0,
                'include_inactive' => 0,
            ];
            if ($hfrId !== '') {
                $masterQuery['hfr_id'] = $hfrId;
            }

            $masterQuery['panel_type'] = 'PANEL';
            $masterResp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/panels', $masterQuery);
            $masterBody = (array) ($masterResp['json'] ?? []);
            if ((int) ($masterBody['ok'] ?? 0) === 1) {
                foreach ((array) ($masterBody['items'] ?? []) as $item) {
                    $candidate = trim((string) ($item['panel_name'] ?? $item['test_name'] ?? $item['display_name'] ?? ''));
                    if ($candidate === '') {
                        continue;
                    }

                    $masterMeta = [
                        'name' => $candidate,
                        'loinc_code' => (string) ($item['code'] ?? $item['loinc_code'] ?? ''),
                        'sub_category' => (string) ($item['sub_category'] ?? ''),
                        'code_system' => (string) ($item['code_system'] ?? ''),
                        'standard_rate' => (string) ($item['standard_rate'] ?? ''),
                        'master_id' => (int) ($item['id'] ?? 0),
                    ];

                    if (strcasecmp($candidate, $parentTest) === 0) {
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', '[pathologyMasterTemplate] master lookup failed: ' . $e->getMessage());
        }

        $expandQuery = [
            'entity_type' => 'panel',
            'name' => $masterMeta['name'],
            'include_inactive' => 0,
            'max_depth' => 8,
        ];
        if ($hfrId !== '') {
            $expandQuery['hfr_id'] = $hfrId;
        }

        $expandResp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/expand', $expandQuery);
        $expandCode = (int) ($expandResp['http_code'] ?? 0);
        $expandErr = (string) ($expandResp['curl_error'] ?? '');
        $expandBody = (array) ($expandResp['json'] ?? []);

        if ($expandErr !== '' || $expandCode < 200 || $expandCode >= 300 || (int) ($expandBody['ok'] ?? 0) !== 1) {
            return $this->response->setStatusCode(502)->setJSON([
                'ok' => 0,
                'error' => 'Failed to expand pathology panel from gateway',
                'http_code' => $expandCode,
                'details' => substr((string) ($expandResp['raw'] ?? ''), 0, 300),
            ]);
        }

        $components = [];
        foreach ((array) ($expandBody['atomic_tests'] ?? []) as $atomic) {
            $components[] = [
                'component_test_name' => (string) ($atomic['test_name'] ?? ''),
                'component_code' => (string) ($atomic['code'] ?? ''),
                'unit' => (string) ($atomic['unit'] ?? ''),
                'component_property' => (string) ($atomic['property'] ?? ''),
                'component_system' => (string) ($atomic['specimen_system'] ?? ''),
                'component_scale' => (string) ($atomic['scale_type'] ?? ''),
                'sort_order' => (int) ($atomic['sort_order'] ?? 0),
            ];
        }
        usort($components, static function ($a, $b): int {
            return (int) ($a['sort_order'] ?? 99999) <=> (int) ($b['sort_order'] ?? 99999);
        });

        $templateHtml = $this->buildPathologyMasterTemplateHtml((string) $masterMeta['name'], $components);

        return $this->response->setJSON([
            'ok' => 1,
            'panel_name' => (string) $masterMeta['name'],
            'loinc_code' => (string) $masterMeta['loinc_code'],
            'sub_category' => (string) $masterMeta['sub_category'],
            'code_system' => (string) $masterMeta['code_system'],
            'standard_rate' => (string) $masterMeta['standard_rate'],
            'master_id' => (int) $masterMeta['master_id'],
            'components_count' => count($components),
            'components' => $components,
            'template_html' => $templateHtml,
            'request_id' => (string) ($expandBody['request_id'] ?? ''),
        ]);
    }

    /**
     * GET Lab_Admin/pathology_component_masters_search?q=hem
     * Gateway-only component search for Add Component modal.
     */
    public function pathologyComponentMastersSearch()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if (strlen($q) < 2) {
            return $this->response->setJSON(['ok' => 1, 'items' => []]);
        }

        $gw = $this->resolveBridgeGatewayConfig();
        $gwUrl = (string) ($gw['url'] ?? '');
        $gwToken = (string) ($gw['token'] ?? '');
        $hfrId = (string) ($gw['hfr_id'] ?? '');

        if ($gwToken === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'EATRIA_BRIDGE_TOKEN not configured',
                'items' => [],
            ]);
        }

        $query = [
            'q' => $q,
            'limit' => 25,
            'offset' => 0,
            'include_inactive' => 0,
        ];
        if ($hfrId !== '') {
            $query['hfr_id'] = $hfrId;
        }

        $resp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/component-masters', $query);
        $code = (int) ($resp['http_code'] ?? 0);
        $cerr = (string) ($resp['curl_error'] ?? '');
        $body = (array) ($resp['json'] ?? []);

        if ($cerr !== '' || $code < 200 || $code >= 300 || (int) ($body['ok'] ?? 0) !== 1) {
            return $this->response->setStatusCode(502)->setJSON([
                'ok' => 0,
                'items' => [],
                'error' => $cerr !== '' ? $cerr : 'Gateway component search failed',
                'http_code' => $code,
                'details' => substr((string) ($resp['raw'] ?? ''), 0, 300),
            ]);
        }

        $items = [];
        foreach ((array) ($body['items'] ?? []) as $item) {
            $name = trim((string) ($item['component_name'] ?? $item['test_name'] ?? $item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $items[] = [
                'name' => $name,
                'short_name' => (string) ($item['short_name'] ?? $item['test_id'] ?? ''),
                'code' => (string) ($item['code'] ?? $item['loinc_code'] ?? ''),
                'unit' => (string) ($item['unit'] ?? ''),
                'property' => (string) ($item['property'] ?? $item['component_property'] ?? ''),
                'specimen_system' => (string) ($item['specimen_system'] ?? $item['component_system'] ?? ''),
                'scale_type' => (string) ($item['scale_type'] ?? $item['component_scale'] ?? ''),
                'sub_category' => (string) ($item['sub_category'] ?? ''),
                'master_id' => (int) ($item['id'] ?? 0),
            ];
        }

        return $this->response->setJSON([
            'ok' => 1,
            'items' => $items,
            'source' => 'bridge',
            'request_id' => (string) ($body['request_id'] ?? ''),
        ]);
    }

    /**
     * POST Lab_Admin/pathology_master_add_component
     * Adds one gateway component into local lab_tests and maps it to the selected panel.
     */
    public function pathologyMasterAddComponent()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Invalid request',
            ]);
        }

        $repoId = (int) ($this->request->getPost('repo_id') ?? 0);
        $name = trim((string) ($this->request->getPost('component_name') ?? ''));
        $shortName = trim((string) ($this->request->getPost('short_name') ?? ''));
        $code = trim((string) ($this->request->getPost('code') ?? ''));
        $unit = trim((string) ($this->request->getPost('unit') ?? ''));
        $property = trim((string) ($this->request->getPost('property') ?? ''));
        $system = trim((string) ($this->request->getPost('specimen_system') ?? ''));
        $scale = trim((string) ($this->request->getPost('scale_type') ?? ''));

        if ($repoId <= 0 || $name === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'repo_id and component_name are required',
            ]);
        }

        $testColumnsRows = $this->db->query('SHOW COLUMNS FROM lab_tests')->getResultArray();
        $testColumns = array_column($testColumnsRows, 'Field');

        $find = $this->db->table('lab_tests')->select('mstTestKey');
        $find->groupStart();
        if ($code !== '' && in_array('loinc_code', $testColumns, true)) {
            $find->orGroupStart()
                ->where('loinc_code', $code)
                ->where('loinc_code !=', '')
                ->groupEnd();
        }
        if ($shortName !== '') {
            $find->orWhere('TestID', $shortName);
        }
        $find->orWhere('Test', $name);
        $find->groupEnd();

        $existing = $find->orderBy('mstTestKey', 'ASC')->get()->getRowArray();
        $mstTestKey = (int) ($existing['mstTestKey'] ?? 0);

        $testData = [
            'Test' => $name,
            'TestID' => $shortName !== '' ? $shortName : ($code !== '' ? $code : ''),
            'Unit' => $unit,
        ];

        if (in_array('loinc_code', $testColumns, true)) {
            $testData['loinc_code'] = $code;
        }
        if (in_array('loinc_property', $testColumns, true)) {
            $testData['loinc_property'] = $property;
        }
        if (in_array('loinc_system', $testColumns, true)) {
            $testData['loinc_system'] = $system;
        }
        if (in_array('loinc_scale', $testColumns, true)) {
            $testData['loinc_scale'] = $scale;
        }

        $pathLab = new PathLabModel();

        if ($mstTestKey > 0) {
            $pathLab->updateItemParameter($testData, $mstTestKey);
        } else {
            $mstTestKey = $pathLab->insertItemParameter($testData);
        }

        if ($mstTestKey <= 0) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => 'Unable to create component locally',
            ]);
        }

        $exists = $this->db->table('lab_repotests')
            ->where('mstRepoKey', $repoId)
            ->where('mstTestKey', $mstTestKey)
            ->countAllResults();

        if ($exists <= 0) {
            $row = $this->db->table('lab_repotests')
                ->selectMax('EOrder', 'max_order')
                ->where('mstRepoKey', $repoId)
                ->get()
                ->getRowArray();
            $nextOrder = (int) ($row['max_order'] ?? 0) + 1;

            $pathLab->insertItemSortorder([
                'mstRepoKey' => $repoId,
                'mstTestKey' => $mstTestKey,
                'EOrder' => $nextOrder,
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'mstTestKey' => $mstTestKey,
            'added' => $exists <= 0 ? 1 : 0,
            'message' => $exists <= 0 ? 'Component added' : 'Component already attached',
        ]);
    }

    /**
     * POST Lab_Admin/pathology_master_apply_panel
     * Gateway-style panel mapping import:
     * - expands panel to atomic tests (short/code aware)
     * - upserts local lab_tests
     * - maps tests to lab_repotests for the report template
     * - regenerates HTMLData with token placeholders ({HB}, {TLC}, ...)
     */
    public function pathologyMasterApplyPanel()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'AJAX only']);
        }
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $repoId = (int) ($this->request->getPost('repo_id') ?? 0);
        $panelName = trim((string) ($this->request->getPost('panel_name') ?? ''));
        $replaceExisting = in_array(strtolower(trim((string) ($this->request->getPost('replace_existing') ?? '1'))), ['1', 'true', 'yes'], true);

        if ($repoId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Save report first, then import panel components.',
            ]);
        }

        if ($panelName === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'panel_name is required',
            ]);
        }

        $gw = $this->resolveBridgeGatewayConfig();
        $gwUrl = (string) ($gw['url'] ?? '');
        $gwToken = (string) ($gw['token'] ?? '');
        $hfrId = (string) ($gw['hfr_id'] ?? '');

        if ($gwToken === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'EATRIA_BRIDGE_TOKEN not configured',
            ]);
        }

        // Resolve to canonical panel name from gateway so expand/components can match reliably.
        try {
            $panelQuery = [
                'q' => $panelName,
                'panel_type' => 'PANEL',
                'limit' => 50,
                'offset' => 0,
                'include_inactive' => 0,
            ];
            if ($hfrId !== '') {
                $panelQuery['hfr_id'] = $hfrId;
            }

            $panelResp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/panels', $panelQuery);
            $panelBody = (array) ($panelResp['json'] ?? []);
            if ((int) ($panelBody['ok'] ?? 0) === 1) {
                $panelItems = array_values((array) ($panelBody['items'] ?? []));
                foreach ($panelItems as $item) {
                    $candidate = trim((string) ($item['panel_name'] ?? $item['test_name'] ?? $item['display_name'] ?? ''));
                    if ($candidate === '') {
                        continue;
                    }

                    if (strcasecmp($candidate, $panelName) === 0) {
                        $panelName = $candidate;
                        break;
                    }

                    if (stripos($candidate, $panelName) !== false || stripos($panelName, $candidate) !== false) {
                        $panelName = $candidate;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', '[pathologyMasterApplyPanel] canonical panel resolve failed: ' . $e->getMessage());
        }

        $expandQuery = [
            'entity_type' => 'panel',
            'name' => $panelName,
            'include_inactive' => 0,
            'max_depth' => 8,
        ];
        if ($hfrId !== '') {
            $expandQuery['hfr_id'] = $hfrId;
        }

        $expandResp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/expand', $expandQuery);
        $expandCode = (int) ($expandResp['http_code'] ?? 0);
        $expandErr = (string) ($expandResp['curl_error'] ?? '');
        $expandBody = (array) ($expandResp['json'] ?? []);

        $atomic = [];
        if ($expandErr === '' && $expandCode >= 200 && $expandCode < 300 && (int) ($expandBody['ok'] ?? 0) === 1) {
            $atomic = array_values((array) ($expandBody['atomic_tests'] ?? []));
        } else {
            $compQuery = [
                'parent_test' => $panelName,
                'limit' => 500,
                'offset' => 0,
                'include_inactive' => 0,
            ];
            if ($hfrId !== '') {
                $compQuery['hfr_id'] = $hfrId;
            }

            $compResp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/components', $compQuery);
            $compCode = (int) ($compResp['http_code'] ?? 0);
            $compErr = (string) ($compResp['curl_error'] ?? '');
            $compBody = (array) ($compResp['json'] ?? []);

            if ($compErr !== '' || $compCode < 200 || $compCode >= 300 || (int) ($compBody['ok'] ?? 0) !== 1) {
                return $this->response->setStatusCode(502)->setJSON([
                    'ok' => 0,
                    'error' => 'Failed to expand panel from gateway',
                    'http_code' => $expandCode,
                    'details' => substr((string) ($expandResp['raw'] ?? ''), 0, 300),
                ]);
            }

            $components = array_values((array) ($compBody['items'] ?? []));
            foreach ($components as $row) {
                $atomic[] = [
                    'test_name' => (string) ($row['component_test_name'] ?? ''),
                    'short_name' => '',
                    'code' => (string) ($row['component_code'] ?? ''),
                    'property' => (string) ($row['component_property'] ?? ''),
                    'specimen_system' => (string) ($row['component_system'] ?? ''),
                    'scale_type' => (string) ($row['component_scale'] ?? ''),
                    'unit' => (string) ($row['unit'] ?? ''),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ];
            }
        }

        if ($atomic === []) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => 0,
                'error' => 'No atomic tests found for selected panel',
            ]);
        }

        usort($atomic, static function ($a, $b): int {
            return (int) ($a['sort_order'] ?? 99999) <=> (int) ($b['sort_order'] ?? 99999);
        });

        $testRowsForTemplate = [];
        $linkedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;

        $this->db->transStart();

        if ($replaceExisting) {
            $this->db->table('lab_repotests')->where('mstRepoKey', $repoId)->delete();
        }

        $order = 10;
        $seenCodes = [];

        foreach ($atomic as $index => $item) {
            $testName = trim((string) ($item['test_name'] ?? ''));
            if ($testName === '') {
                continue;
            }

            $short = trim((string) ($item['short_name'] ?? ''));
            $code = trim((string) ($item['code'] ?? ''));
            $testCode = $this->normalizePanelTokenCode($short !== '' ? $short : $code, $index + 1);

            if (isset($seenCodes[$testCode])) {
                $testCode = $this->normalizePanelTokenCode($testCode . ($index + 1), $index + 1);
            }
            $seenCodes[$testCode] = true;

            $unit = trim((string) ($item['unit'] ?? ''));
            $property = trim((string) ($item['property'] ?? ''));
            $system = trim((string) ($item['specimen_system'] ?? ''));
            $scale = trim((string) ($item['scale_type'] ?? ''));

            $existing = $this->db->table('lab_tests')
                ->select('mstTestKey')
                ->groupStart()
                    ->where('TestID', $testCode)
                    ->orGroupStart()
                        ->where('loinc_code', $code)
                        ->where('loinc_code !=', '')
                    ->groupEnd()
                    ->orWhere('Test', $testName)
                ->groupEnd()
                ->orderBy('mstTestKey', 'ASC')
                ->get(1)
                ->getRowArray();

            if (is_array($existing) && (int) ($existing['mstTestKey'] ?? 0) > 0) {
                $mstTestKey = (int) $existing['mstTestKey'];
                $this->db->table('lab_tests')->where('mstTestKey', $mstTestKey)->update([
                    'Test' => $testName,
                    'TestID' => $testCode,
                    'Unit' => $unit,
                    'loinc_code' => $code,
                    'loinc_property' => $property,
                    'loinc_system' => $system,
                    'loinc_scale' => $scale,
                    'loinc_synced_at' => date('Y-m-d H:i:s'),
                ]);
                $updatedCount++;
            } else {
                $this->db->table('lab_tests')->insert([
                    'Test' => $testName,
                    'TestID' => $testCode,
                    'Result' => '',
                    'Options' => null,
                    'Formula' => '',
                    'VRule' => '',
                    'VMsg' => '',
                    'Unit' => $unit,
                    'FixedNormals' => '',
                    'isGenderSpecific' => 0,
                    'FixedNormalsWomen' => '',
                    'loinc_code' => $code,
                    'loinc_property' => $property,
                    'loinc_system' => $system,
                    'loinc_scale' => $scale,
                    'loinc_synced_at' => date('Y-m-d H:i:s'),
                ]);
                $mstTestKey = (int) $this->db->insertID();
                $createdCount++;
            }

            if ($mstTestKey <= 0) {
                continue;
            }

            $mapExists = $this->db->table('lab_repotests')
                ->where('mstRepoKey', $repoId)
                ->where('mstTestKey', $mstTestKey)
                ->countAllResults();

            if ($mapExists <= 0) {
                $this->db->table('lab_repotests')->insert([
                    'mstRepoKey' => $repoId,
                    'mstTestKey' => $mstTestKey,
                    'EOrder' => $order,
                ]);
                $linkedCount++;
            }

            $order += 10;

            $testRowsForTemplate[] = [
                'test_name' => $testName,
                'test_code' => $testCode,
                'unit' => $unit,
            ];
        }

        $templateHtml = $this->buildPathologyPanelTokenTemplateHtml($panelName, $testRowsForTemplate);
        $panelLoinc = trim((string) ($expandBody['resolved']['code'] ?? ''));

        $updateData = [
            'Title' => $panelName,
            'HTMLData' => $templateHtml,
            'RTFData' => $templateHtml,
        ];
        if ($panelLoinc !== '') {
            $updateData['loinc_code'] = $panelLoinc;
            $updateData['loinc_synced_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('lab_repo')->where('mstRepoKey', $repoId)->update($updateData);

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => 'Failed to apply panel mapping in local database',
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'repo_id' => $repoId,
            'panel_name' => $panelName,
            'panel_loinc_code' => $panelLoinc,
            'components_count' => count($testRowsForTemplate),
            'created_tests' => $createdCount,
            'updated_tests' => $updatedCount,
            'linked_tests' => $linkedCount,
            'template_html' => $templateHtml,
        ]);
    }

    /**
    * GET Lab_Admin/pathology_bridge_debug
    * DEV-ONLY: Shows raw Bridge API response for /api/v3/pathology/panels
     * Remove this route from production!
     */
    public function pathologyBridgeDebug()
    {
        if ($resp = $this->requirePermission('template.pathology')) {
            return $resp;
        }

        $gw = $this->resolveBridgeGatewayConfig();
        $gwUrl = (string) ($gw['url'] ?? '');
        $gwToken = (string) ($gw['token'] ?? '');
        $hfrId = (string) ($gw['hfr_id'] ?? '');

        if ($gwToken === '') {
            return $this->response->setJSON(['error' => 'EATRIA_BRIDGE_TOKEN not configured', 'url' => $gwUrl]);
        }

        $query = ['limit' => 5, 'offset' => 0];
        if ($hfrId !== '') {
            $query['hfr_id'] = $hfrId;
        }

        $query['panel_type'] = 'PANEL';
        $resp = $this->bridgeGet($gwUrl, $gwToken, '/api/v3/pathology/panels', $query);
        $raw = (string) ($resp['raw'] ?? '');
        $parsed = (array) ($resp['json'] ?? []);

        return $this->response->setJSON([
            'url'         => (string) ($resp['url'] ?? ''),
            'token_set'   => $gwToken !== '' ? substr($gwToken, 0, 6) . '***' . substr($gwToken, -4) : '(empty)',
            'hfr_id_set'  => $hfrId !== '',
            'http_code'   => (int) ($resp['http_code'] ?? 0),
            'curl_error'  => (string) ($resp['curl_error'] ?? ''),
            'raw_first_500' => substr($raw, 0, 500),
            'parsed'      => $parsed,
        ]);
    }


    public function diagnosis_print_settings(int $modality = 3)
    {
        if ($resp = $this->requireAnyPermission([
            'template.diagnosis_print',
            'template.pathology_print',
            'template.pathology', 'template.ultrasound', 'template.xray', 'template.ct', 'template.mri', 'template.echo',
        ])) {
            return $resp;
        }

        $modalityList = [
            5 => 'Pathology',
            1 => 'Ultrasound',
            2 => 'MRI',
            3 => 'X-Ray',
            4 => 'CT-Scan',
            6 => 'Echo',
        ];

        if (! in_array($modality, [1, 2, 3, 4, 5, 6], true)) {
            $modality = 3;
        }

        $notice = '';
        $noticeType = 'success';

        if (! $this->db->tableExists('diagnosis_print_templates')) {
            return view('Setting/Template/diagnosis_print_settings', [
                'modality' => $modality,
                'row' => [],
                'templates' => [],
                'selected_template_id' => 0,
                'notice' => 'diagnosis_print_templates table not found. Please run migration.',
                'notice_type' => 'danger',
                'columns_ready' => false,
                'modality_list' => $modalityList,
            ]);
        }

        $columnsReady = true;
        $templateTable = $this->db->table('diagnosis_print_templates');
        $templateFields = $this->db->getFieldNames('diagnosis_print_templates');
        $hasSignatureImageColumn = in_array('signature_image', $templateFields, true);

        // Backward-compatible: if this DB has not yet received the signature column,
        // try to add it automatically so template-level signature upload can work.
        if (! $hasSignatureImageColumn) {
            try {
                $this->db->query("ALTER TABLE diagnosis_print_templates ADD COLUMN signature_image VARCHAR(255) NULL AFTER watermark_image");
                $templateFields = $this->db->getFieldNames('diagnosis_print_templates');
                $hasSignatureImageColumn = in_array('signature_image', $templateFields, true);
            } catch (\Throwable $e) {
                // Keep page usable even when ALTER permission is not available.
                $hasSignatureImageColumn = false;
            }
        }

        $selectedTemplateId = (int) ($this->request->getGet('template_id') ?? 0);
        $isNewTemplate = (int) ($this->request->getGet('new') ?? 0) === 1;
        $compileLetterheadTemplateId = (int) $this->readHospitalSettingValue('DIAG_COMPILE_TPL_LETTERHEAD_' . $modality);
        $compilePlainTemplateId = (int) $this->readHospitalSettingValue('DIAG_COMPILE_TPL_PLAIN_' . $modality);
        $currentTemplateType = strtolower(trim((string) ($this->request->getGet('template_type') ?? 'letterhead')));
        if (! in_array($currentTemplateType, ['letterhead', 'plain'], true)) {
            $currentTemplateType = 'letterhead';
        }

        $mappedTemplateIdForType = $currentTemplateType === 'plain'
            ? $compilePlainTemplateId
            : $compileLetterheadTemplateId;

        if ($selectedTemplateId <= 0) {
            if ($mappedTemplateIdForType > 0) {
                $selectedTemplateId = $mappedTemplateIdForType;
            } else {
                $isNewTemplate = true;
            }
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $modality = (int) ($this->request->getPost('modality') ?? $modality);
            if (! in_array($modality, [1, 2, 3, 4, 5, 6], true)) {
                $modality = 3;
            }

            $currentTemplateType = strtolower(trim((string) ($this->request->getPost('template_type') ?? $currentTemplateType)));
            if (! in_array($currentTemplateType, ['letterhead', 'plain'], true)) {
                $currentTemplateType = 'letterhead';
            }

            $compileLetterheadTemplateId = (int) ($this->request->getPost('compile_letterhead_template_id') ?? 0);
            $compilePlainTemplateId = (int) ($this->request->getPost('compile_plain_template_id') ?? 0);

            $selectedTemplateId = (int) ($this->request->getPost('template_id') ?? 0);

            $mappedTemplateIdForType = $currentTemplateType === 'plain'
                ? $compilePlainTemplateId
                : $compileLetterheadTemplateId;

            if ($mappedTemplateIdForType > 0) {
                $selectedTemplateId = $mappedTemplateIdForType;
            } elseif ($currentTemplateType === 'plain' && $compileLetterheadTemplateId > 0 && $selectedTemplateId === $compileLetterheadTemplateId) {
                $selectedTemplateId = 0;
            } elseif ($currentTemplateType === 'letterhead' && $compilePlainTemplateId > 0 && $selectedTemplateId === $compilePlainTemplateId) {
                $selectedTemplateId = 0;
            }

            if (! $columnsReady) {
                $notice = 'Print settings columns are missing. Please run migration first.';
                $noticeType = 'danger';
            } else {
                $existing = [];
                if ($selectedTemplateId > 0) {
                    $existing = $templateTable
                        ->where('id', $selectedTemplateId)
                        ->where('modality', $modality)
                        ->get(1)
                        ->getRowArray() ?? [];
                }

                $templateName = $currentTemplateType === 'plain' ? 'Plain Paper' : 'Letter Head';

                $removeBackground = (int) ($this->request->getPost('remove_background') ?? 0) === 1;
                $removeWatermarkImage = (int) ($this->request->getPost('remove_watermark_image') ?? 0) === 1;
                $removeSignatureImage = (int) ($this->request->getPost('remove_signature_image') ?? 0) === 1;

                $backgroundPath = (string) ($existing['page_background_image'] ?? '');
                $watermarkImagePath = (string) ($existing['watermark_image'] ?? '');
                $signatureImagePath = (string) ($existing['signature_image'] ?? '');

                if ($removeBackground) {
                    $backgroundPath = '';
                }
                if ($removeWatermarkImage) {
                    $watermarkImagePath = '';
                }
                if ($removeSignatureImage) {
                    $signatureImagePath = '';
                }

                $bgUpload = $this->request->getFile('page_background_image');
                if ($bgUpload && $bgUpload->isValid() && ! $bgUpload->hasMoved()) {
                    $ext = strtolower((string) $bgUpload->getExtension());
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                        $folder = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'diagnosis_print_bg';
                        if (! is_dir($folder)) {
                            @mkdir($folder, 0777, true);
                        }

                        $newName = 'diag_tpl_bg_' . $modality . '_' . date('Ymd_His') . '.' . $ext;
                        $bgUpload->move($folder, $newName, true);
                        $backgroundPath = 'uploads/diagnosis_print_bg/' . $newName;
                    } else {
                        $notice = 'Background image must be PNG/JPG/JPEG/WEBP.';
                        $noticeType = 'danger';
                    }
                }

                $wmUpload = $this->request->getFile('watermark_image');
                if ($wmUpload && $wmUpload->isValid() && ! $wmUpload->hasMoved()) {
                    $ext = strtolower((string) $wmUpload->getExtension());
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                        $folder = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'diagnosis_print_wm';
                        if (! is_dir($folder)) {
                            @mkdir($folder, 0777, true);
                        }

                        $newName = 'diag_tpl_wm_' . $modality . '_' . date('Ymd_His') . '.' . $ext;
                        $wmUpload->move($folder, $newName, true);
                        $watermarkImagePath = 'uploads/diagnosis_print_wm/' . $newName;
                    } else {
                        $notice = 'Watermark image must be PNG/JPG/JPEG/WEBP.';
                        $noticeType = 'danger';
                    }
                }

                if ($hasSignatureImageColumn) {
                    $sigUpload = $this->request->getFile('signature_image');
                    if ($sigUpload && $sigUpload->isValid() && ! $sigUpload->hasMoved()) {
                        $ext = strtolower((string) $sigUpload->getExtension());
                        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                            $folder = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'diagnosis_print_sign';
                            if (! is_dir($folder)) {
                                @mkdir($folder, 0777, true);
                            }

                            $newName = 'diag_tpl_sign_' . $modality . '_' . date('Ymd_His') . '.' . $ext;
                            $sigUpload->move($folder, $newName, true);
                            $signatureImagePath = 'uploads/diagnosis_print_sign/' . $newName;
                        } else {
                            $notice = 'Signature image must be PNG/JPG/JPEG/WEBP.';
                            $noticeType = 'danger';
                        }
                    }
                }

                $data = [
                    'modality' => $modality,
                    'template_name' => $templateName,
                    'page_size' => trim((string) ($this->request->getPost('page_size') ?? 'A4')) ?: 'A4',
                    'page_margin_top_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_top_cm'), 6.1),
                    'page_margin_bottom_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_bottom_cm'), 2.5),
                    'page_margin_left_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_left_cm'), 0.7),
                    'page_margin_right_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_right_cm'), 0.7),
                    'margin_header_cm' => $this->normalizeMarginCm($this->request->getPost('margin_header_cm'), 0.5),
                    'margin_footer_cm' => $this->normalizeMarginCm($this->request->getPost('margin_footer_cm'), 1.5),
                    'page_background_image' => $backgroundPath,
                    'watermark_type' => in_array((string) $this->request->getPost('watermark_type'), ['none', 'text', 'image'], true)
                        ? (string) $this->request->getPost('watermark_type')
                        : 'none',
                    'watermark_text' => (string) ($this->request->getPost('watermark_text') ?? ''),
                    'watermark_image' => $watermarkImagePath,
                    'signature_image' => $signatureImagePath,
                    'watermark_alpha' => $this->normalizeWatermarkAlpha($this->request->getPost('watermark_alpha')),
                    'header_html' => (string) ($this->request->getPost('header_html') ?? ''),
                    'first_page_header_html' => (string) ($this->request->getPost('first_page_header_html') ?? ''),
                    'content_prefix_html' => (string) ($this->request->getPost('content_prefix_html') ?? ''),
                    'content_suffix_html' => (string) ($this->request->getPost('content_suffix_html') ?? ''),
                    'footer_html' => (string) ($this->request->getPost('footer_html') ?? ''),
                    'last_page_footer_html' => (string) ($this->request->getPost('last_page_footer_html') ?? ''),
                    'patient_info_html' => (string) ($this->request->getPost('patient_info_html') ?? ''),
                    'mpdf_prefix_html' => (string) ($this->request->getPost('mpdf_prefix_html') ?? ''),
                    'mpdf_suffix_html' => (string) ($this->request->getPost('mpdf_suffix_html') ?? ''),
                    'is_default' => ! empty($existing) ? (int) ($existing['is_default'] ?? 0) : 0,
                    'status' => 1,
                ];

                if (! $hasSignatureImageColumn) {
                    unset($data['signature_image']);
                }

                if ($noticeType !== 'danger') {
                    if (empty($existing)) {
                        $hasDefaultTemplate = (bool) ($templateTable
                            ->select('id')
                            ->where('modality', $modality)
                            ->where('status', 1)
                            ->where('is_default', 1)
                            ->get(1)
                            ->getRowArray());

                        if (! $hasDefaultTemplate && $currentTemplateType === 'letterhead') {
                            $data['is_default'] = 1;
                        }
                    }

                    if (! empty($existing) && $selectedTemplateId > 0) {
                        $templateTable
                            ->where('id', $selectedTemplateId)
                            ->where('modality', $modality)
                            ->update($data);
                    } else {
                        $templateTable->insert($data);
                        $selectedTemplateId = (int) $this->db->insertID();
                    }

                    $notice = (($modalityList[$modality] ?? 'Diagnosis') . ' print template saved.');
                    $noticeType = 'success';

                    if ($currentTemplateType === 'plain') {
                        $compilePlainTemplateId = $selectedTemplateId;
                    } else {
                        $compileLetterheadTemplateId = $selectedTemplateId;
                    }

                    $letterheadSettingKey = 'DIAG_COMPILE_TPL_LETTERHEAD_' . $modality;
                    $plainSettingKey = 'DIAG_COMPILE_TPL_PLAIN_' . $modality;

                    if ($compileLetterheadTemplateId > 0) {
                        $this->upsertHospitalSettingValue($letterheadSettingKey, (string) $compileLetterheadTemplateId);
                    } else {
                        $this->deleteHospitalSettingValue($letterheadSettingKey);
                    }

                    if ($compilePlainTemplateId > 0) {
                        $this->upsertHospitalSettingValue($plainSettingKey, (string) $compilePlainTemplateId);
                    } else {
                        $this->deleteHospitalSettingValue($plainSettingKey);
                    }
                }
            }

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => $noticeType === 'success' ? 'success' : 'error',
                    'notice' => $notice,
                    'notice_type' => $noticeType,
                    'modality' => $modality,
                    'selected_template_id' => $selectedTemplateId,
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }
        }

        $templates = $templateTable
            ->select('id, template_name, is_default')
            ->where('modality', $modality)
            ->where('status', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('template_name', 'ASC')
            ->get()
            ->getResultArray();

        if (! $isNewTemplate && $selectedTemplateId <= 0 && ! empty($templates)) {
            $selectedTemplateId = (int) ($templates[0]['id'] ?? 0);
        }

        $row = [];
        if ($selectedTemplateId > 0) {
            $row = $templateTable
                ->where('id', $selectedTemplateId)
                ->where('modality', $modality)
                ->get(1)
                ->getRowArray() ?? [];
        }

        return view('Setting/Template/diagnosis_print_settings', [
            'modality' => $modality,
            'row' => $row,
            'templates' => $templates,
            'selected_template_id' => $selectedTemplateId,
            'compile_letterhead_template_id' => $compileLetterheadTemplateId,
            'compile_plain_template_id' => $compilePlainTemplateId,
            'current_template_type' => $currentTemplateType,
            'notice' => $notice,
            'notice_type' => $noticeType,
            'columns_ready' => $columnsReady,
            'modality_list' => $modalityList,
                'has_signature_image_column' => $hasSignatureImageColumn,
            'has_signature_image_column' => $hasSignatureImageColumn,
        ]);
    }

    private function readHospitalSettingValue(string $name): string
    {
        if ($name === '' || ! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    private function upsertHospitalSettingValue(string $name, string $value): bool
    {
        if ($name === '' || ! $this->db->tableExists('hospital_setting')) {
            return false;
        }

        $table = $this->db->table('hospital_setting');
        $existing = $table->select('id')->where('s_name', $name)->get(1)->getRowArray();

        if ($existing) {
            return (bool) $table->where('id', (int) ($existing['id'] ?? 0))->update(['s_value' => $value]);
        }

        return (bool) $table->insert([
            's_name' => $name,
            's_value' => $value,
        ]);
    }

    private function deleteHospitalSettingValue(string $name): bool
    {
        if ($name === '' || ! $this->db->tableExists('hospital_setting')) {
            return false;
        }

        return (bool) $this->db->table('hospital_setting')->where('s_name', $name)->delete();
    }

    public function document_print_settings()
    {
        if ($resp = $this->requireAnyPermission([
            'template.document_print',
            'doctor_work.template_workspace.access', 'doctor_work.access', 'template.pathology',
        ])) {
            return $resp;
        }

        $notice = '';
        $noticeType = 'success';
        $selectedTemplateId = (int) ($this->request->getGet('template_id') ?? 0);
        $isNewTemplate = (int) ($this->request->getGet('new') ?? 0) === 1;

        if (! $this->db->tableExists('doc_print_templates')) {
            return view('Setting/Template/document_print_settings', [
                'row' => [],
                'templates' => [],
                'selected_template_id' => 0,
                'notice' => 'doc_print_templates table not found. Please run migration.',
                'notice_type' => 'danger',
                'columns_ready' => false,
            ]);
        }

        $templateTable = $this->db->table('doc_print_templates');

        if (strtolower($this->request->getMethod()) === 'post') {
            $selectedTemplateId = (int) ($this->request->getPost('template_id') ?? 0);
            $existing = [];

            if ($selectedTemplateId > 0) {
                $existing = $templateTable
                    ->where('id', $selectedTemplateId)
                    ->get(1)
                    ->getRowArray() ?? [];
            }

            $templateName = trim((string) ($this->request->getPost('template_name') ?? ''));
            if ($templateName === '') {
                $templateName = 'Document Template ' . date('d-m-Y H:i');
            }

            $pageSize = strtoupper(trim((string) ($this->request->getPost('page_size') ?? 'A4')));
            if (! in_array($pageSize, ['A4', 'A4-L', 'LETTER', 'LEGAL'], true)) {
                $pageSize = 'A4';
            }

            $data = [
                'template_name' => $templateName,
                'page_size' => $pageSize,
                'page_margin_top_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_top_cm'), 6.1),
                'page_margin_bottom_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_bottom_cm'), 2.5),
                'page_margin_left_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_left_cm'), 0.7),
                'page_margin_right_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_right_cm'), 0.7),
                'margin_header_cm' => $this->normalizeMarginCm($this->request->getPost('margin_header_cm'), 0.5),
                'margin_footer_cm' => $this->normalizeMarginCm($this->request->getPost('margin_footer_cm'), 1.5),
                'header_html' => (string) ($this->request->getPost('header_html') ?? ''),
                'footer_html' => (string) ($this->request->getPost('footer_html') ?? ''),
                'is_default' => (int) ($this->request->getPost('is_default') ?? 0) === 1 ? 1 : 0,
                'status' => 1,
            ];

            if ($data['is_default'] === 1) {
                $templateTable->set('is_default', 0)->update();
            }

            if (! empty($existing) && $selectedTemplateId > 0) {
                $templateTable->where('id', $selectedTemplateId)->update($data);
            } else {
                $templateTable->insert($data);
                $selectedTemplateId = (int) $this->db->insertID();
            }

            $notice = 'Document print template saved.';
            $noticeType = 'success';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'notice' => $notice,
                    'notice_type' => $noticeType,
                    'selected_template_id' => $selectedTemplateId,
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }
        }

        $templates = $templateTable
            ->select('id, template_name, is_default, print_on_type')
            ->where('status', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('template_name', 'ASC')
            ->get()
            ->getResultArray();

        if (! $isNewTemplate && $selectedTemplateId <= 0 && ! empty($templates)) {
            $selectedTemplateId = (int) ($templates[0]['id'] ?? 0);
        }

        $row = [];
        if ($selectedTemplateId > 0) {
            $row = $templateTable
                ->where('id', $selectedTemplateId)
                ->get(1)
                ->getRowArray() ?? [];
        }

        return view('Setting/Template/document_print_settings', [
            'row' => $row,
            'templates' => $templates,
            'selected_template_id' => $selectedTemplateId,
            'notice' => $notice,
            'notice_type' => $noticeType,
            'columns_ready' => true,
        ]);
    }

    private function normalizeWatermarkAlpha($rawValue): float
    {
        if ($rawValue === null || $rawValue === '') {
            return 0.12;
        }

        $value = (float) $rawValue;
        if (! is_finite($value)) {
            $value = 0.12;
        }

        $value = max(0.01, min(1.0, $value));

        return round($value, 2);
    }

    private function normalizeMarginCm($rawValue, float $default): float
    {
        if ($rawValue === null || $rawValue === '') {
            return $default;
        }

        $value = (float) $rawValue;
        if (! is_finite($value)) {
            $value = $default;
        }

        // Allow large top margins like 6.1cm for heavy report headers.
        $value = max(0.0, min(25.0, $value));

        return round($value, 2);
    }

    private function normalizeMarginMm($rawValue, float $default): float
    {
        if ($rawValue === null || $rawValue === '') {
            return $default;
        }

        $value = (float) $rawValue;
        if (! is_finite($value)) {
            $value = $default;
        }

        $value = max(0.0, min(60.0, $value));

        return round($value, 2);
    }

    private function renderOptionTable(int $mstTestKey): string
    {
        $sql = "select *, if(option_bold=1,'Bold','') as option_bold_str
            from lab_tests_option where mstTestKey=" . (int) $mstTestKey . " order by sort_id";
        $query = $this->db->query($sql);
        $options = $query->getResult();

        return view('PathLab_Report/_test_option_table', [
            'lab_test_option' => $options,
            'mstTestKey' => $mstTestKey,
        ]);
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
            'custom_width_mm' => "ALTER TABLE ipd_discharge_templates ADD COLUMN custom_width_mm INT NOT NULL DEFAULT 210 AFTER page_size",
            'custom_height_mm' => "ALTER TABLE ipd_discharge_templates ADD COLUMN custom_height_mm INT NOT NULL DEFAULT 297 AFTER custom_width_mm",
            'page_margin_top_cm' => "ALTER TABLE ipd_discharge_templates ADD COLUMN page_margin_top_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER custom_height_mm",
            'page_margin_bottom_cm' => "ALTER TABLE ipd_discharge_templates ADD COLUMN page_margin_bottom_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_top_cm",
            'page_margin_left_cm' => "ALTER TABLE ipd_discharge_templates ADD COLUMN page_margin_left_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_bottom_cm",
            'page_margin_right_cm' => "ALTER TABLE ipd_discharge_templates ADD COLUMN page_margin_right_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_left_cm",
            'margin_header_cm' => "ALTER TABLE ipd_discharge_templates ADD COLUMN margin_header_cm DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER page_margin_right_cm",
            'margin_footer_cm' => "ALTER TABLE ipd_discharge_templates ADD COLUMN margin_footer_cm DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER margin_header_cm",
            'header_html' => "ALTER TABLE ipd_discharge_templates ADD COLUMN header_html LONGTEXT NULL AFTER margin_footer_cm",
            'footer_html' => "ALTER TABLE ipd_discharge_templates ADD COLUMN footer_html LONGTEXT NULL AFTER header_html",
            'template_css' => "ALTER TABLE ipd_discharge_templates ADD COLUMN template_css LONGTEXT NULL AFTER footer_html",
        ];

        foreach ($columns as $col => $sql) {
            try {
                $exists = $this->db->query("SHOW COLUMNS FROM ipd_discharge_templates LIKE '" . $col . "'")->getRowArray();
                if (empty($exists)) {
                    $this->db->query($sql);
                }
            } catch (\Throwable $e) {
                // Keep template screen usable even if schema alter fails in restricted env.
            }
        }
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

    public function discharge_templates()
    {
        if ($resp = $this->requireAnyPermission(['template.discharge'])) {
            return $resp;
        }

        // Ensure table structure exists, but don't auto-seed templates
        // This allows users to delete all templates without them auto-regenerating
        $this->ensureDischargeTemplateTable();

        $notice = '';
        $noticeType = 'success';

        if (strtolower($this->request->getMethod()) === 'post') {
            $id = (int) ($this->request->getPost('id') ?? 0);
            $templateName = trim((string) ($this->request->getPost('template_name') ?? ''));
            $templateHtml = (string) ($this->request->getPost('template_html') ?? '');
            $headerHtml = (string) ($this->request->getPost('header_html') ?? '');
            $footerHtml = (string) ($this->request->getPost('footer_html') ?? '');
            $templateCss = (string) ($this->request->getPost('template_css') ?? '');
            $pageSize = strtoupper(trim((string) ($this->request->getPost('page_size') ?? 'A4')));
            if (! in_array($pageSize, ['A4', 'A4-L', 'A5', 'A6', 'LETTER', 'LEGAL', 'CUSTOM'], true)) {
                $pageSize = 'A4';
            }
            $customWidthMm = max(20, min(600, (int) ($this->request->getPost('custom_width_mm') ?? 210)));
            $customHeightMm = max(20, min(1000, (int) ($this->request->getPost('custom_height_mm') ?? 297)));
            $marginTop = max(0, min(25, (float) ($this->request->getPost('page_margin_top_cm') ?? 0.8)));
            $marginBottom = max(0, min(25, (float) ($this->request->getPost('page_margin_bottom_cm') ?? 0.8)));
            $marginLeft = max(0, min(25, (float) ($this->request->getPost('page_margin_left_cm') ?? 0.8)));
            $marginRight = max(0, min(25, (float) ($this->request->getPost('page_margin_right_cm') ?? 0.8)));
            $marginHeader = max(0, min(25, (float) ($this->request->getPost('margin_header_cm') ?? 0.5)));
            $marginFooter = max(0, min(25, (float) ($this->request->getPost('margin_footer_cm') ?? 0.5)));
            $isDefault = (int) ($this->request->getPost('is_default') ?? 0) === 1 ? 1 : 0;
            $status = (int) ($this->request->getPost('status') ?? 1) === 1 ? 1 : 0;

            // In SPA/AJAX reload scenarios, CKEditor can occasionally post an empty
            // template field even when editing an existing row. Preserve existing
            // values for update requests instead of hard-failing.
            if ($id > 0 && ($templateName === '' || trim($templateHtml) === '')) {
                $existing = $this->db->table('ipd_discharge_templates')
                    ->select('template_name, template_html')
                    ->where('id', $id)
                    ->get(1)
                    ->getRowArray() ?? [];

                if ($templateName === '') {
                    $templateName = trim((string) ($existing['template_name'] ?? ''));
                }
                if (trim($templateHtml) === '') {
                    $templateHtml = (string) ($existing['template_html'] ?? '');
                }
            }

            if ($templateName === '' || trim($templateHtml) === '') {
                $notice = 'Template name and template HTML are required.';
                $noticeType = 'danger';

                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'update' => 0,
                        'error_text' => $notice,
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
                }
            } else {
                $table = $this->db->table('ipd_discharge_templates');
                $data = [
                    'template_name' => $templateName,
                    'page_size' => $pageSize,
                    'custom_width_mm' => $customWidthMm,
                    'custom_height_mm' => $customHeightMm,
                    'page_margin_top_cm' => $marginTop,
                    'page_margin_bottom_cm' => $marginBottom,
                    'page_margin_left_cm' => $marginLeft,
                    'page_margin_right_cm' => $marginRight,
                    'margin_header_cm' => $marginHeader,
                    'margin_footer_cm' => $marginFooter,
                    'header_html' => $headerHtml,
                    'footer_html' => $footerHtml,
                    'template_css' => $templateCss,
                    'template_html' => $templateHtml,
                    'is_default' => $isDefault,
                    'status' => $status,
                ];

                $ok = false;
                if ($id > 0) {
                    $ok = (bool) $table->where('id', $id)->update($data);
                    $notice = $ok ? 'Template updated.' : 'Unable to update template.';
                } else {
                    $ok = (bool) $table->insert($data);
                    $id = (int) $this->db->insertID();
                    $notice = $ok ? 'Template created.' : 'Unable to create template.';
                }

                $noticeType = $ok ? 'success' : 'danger';

                if ($isDefault === 1 && $id > 0) {
                    $this->db->table('ipd_discharge_templates')
                        ->where('id <>', $id)
                        ->update(['is_default' => 0]);
                }

                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'update' => $ok ? 1 : 0,
                        'id' => $id,
                        'error_text' => $notice,
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
                }
            }
        }

        $editId = (int) ($this->request->getGet('edit') ?? 0);
        $editRow = [];
        if ($editId > 0) {
            $editRow = $this->db->table('ipd_discharge_templates')
                ->where('id', $editId)
                ->get(1)
                ->getRowArray() ?? [];
        }

        $rows = $this->db->table('ipd_discharge_templates')
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return view('Setting/Template/discharge_templates', [
            'rows' => $rows,
            'edit_row' => $editRow,
            'notice' => $notice,
            'notice_type' => $noticeType,
        ]);
    }

    public function discharge_template_get(int $id)
    {
        if ($resp = $this->requireAnyPermission(['template.discharge'])) {
            return $resp;
        }

        $this->ensureDischargeTemplateTable();

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'error_text' => 'Invalid request',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $id = (int) $id;
        if ($id <= 0 || ! $this->db->tableExists('ipd_discharge_templates')) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Template not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $row = $this->db->table('ipd_discharge_templates')
            ->where('id', $id)
            ->get(1)
            ->getRowArray() ?? [];

        if (empty($row)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Template not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'row' => $row,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function discharge_templates_seed()
    {
        if ($resp = $this->requireAnyPermission(['template.discharge'])) {
            return $resp;
        }

        // Manually seed default templates (on user request)
        $this->ensureDefaultDischargeTemplateSeeded();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'update' => 1,
                'error_text' => 'Default templates created successfully.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return redirect()->to(base_url('setting/template/discharge_templates'));
    }

    public function discharge_template_delete(int $id)
    {
        if ($resp = $this->requireAnyPermission(['template.discharge'])) {
            return $resp;
        }

        $this->ensureDischargeTemplateTable();

        $id = (int) $id;
        $ok = false;
        $message = 'Unable to delete template.';
        
        log_message('debug', 'Delete request for template ID: ' . $id);
        
        if ($id > 0 && $this->db->tableExists('ipd_discharge_templates')) {
            $row = $this->db->table('ipd_discharge_templates')
                ->where('id', $id)
                ->get(1)
                ->getRowArray() ?? [];

            log_message('debug', 'Row fetched: ' . json_encode($row));

            if (! empty($row)) {
                try {
                    // Try to delete the record
                    $deleteQuery = $this->db->table('ipd_discharge_templates')->where('id', $id);
                    log_message('debug', 'Delete query: ' . $deleteQuery->getCompiledDelete(false));
                    
                    $deleteQuery->delete();
                    $affectedRows = $this->db->affectedRows();
                    log_message('debug', 'Delete affected rows: ' . $affectedRows);
                    
                    if ($affectedRows > 0) {
                        $ok = true;
                        $message = 'Template deleted.';
                        log_message('debug', 'Delete successful');
                    } else {
                        // Delete didn't affect any rows - try archive instead
                        log_message('error', 'Delete affected 0 rows for template ID: ' . $id);
                        
                        $updateQuery = $this->db->table('ipd_discharge_templates')->where('id', $id);
                        log_message('debug', 'Archive query: ' . $updateQuery->getCompiledUpdate(['status' => 0, 'is_default' => 0], false));
                        
                        $updateResult = $updateQuery->update(['status' => 0, 'is_default' => 0]);
                        $updateAffected = $this->db->affectedRows();
                        
                        log_message('debug', 'Archive affected rows: ' . $updateAffected);
                        
                        if ($updateResult && $updateAffected > 0) {
                            $ok = true;
                            $message = 'Template archived.';
                        } else {
                            log_message('error', 'Archive also affected 0 rows for template ID: ' . $id);
                            $dbError = $this->db->error();
                            log_message('error', 'Database error code: ' . $dbError['code'] . ', message: ' . $dbError['message']);
                            $message = 'Database error: ' . ($dbError['message'] ?? 'No rows affected');
                        }
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Exception during delete: ' . $e->getMessage());
                    log_message('error', 'Exception trace: ' . $e->getTraceAsString());
                    $message = 'Exception: ' . $e->getMessage();
                    $ok = false;
                }

                if ($ok && (int) ($row['is_default'] ?? 0) === 1) {
                    $nextDefault = $this->db->table('ipd_discharge_templates')
                        ->select('id')
                        ->where('status', 1)
                        ->orderBy('id', 'ASC')
                        ->get(1)
                        ->getRowArray();

                    if (! empty($nextDefault['id'])) {
                        $this->db->table('ipd_discharge_templates')
                            ->where('id', (int) $nextDefault['id'])
                            ->update(['is_default' => 1]);
                    }
                }

                if ($ok && $message === 'Unable to delete template.') {
                    $message = 'Template deleted.';
                }
            } else {
                log_message('error', 'Row not found for template ID: ' . $id);
            }
        } else {
            log_message('error', 'Invalid ID or table does not exist. ID: ' . $id . ', Table exists: ' . ($this->db->tableExists('ipd_discharge_templates') ? 'yes' : 'no'));
        }

        log_message('debug', 'Final result - OK: ' . ($ok ? 'true' : 'false') . ', Message: ' . $message);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'update' => $ok ? 1 : 0,
                'error_text' => $message,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return redirect()->to(base_url('setting/template/discharge_templates'));
    }

    private function ensureIpdDocumentTemplateTable(): void
    {
        if ($this->db->tableExists('ipd_document_templates')) {
            $this->ensureIpdDocumentTemplateColumns();
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS ipd_document_templates (
            id INT NOT NULL AUTO_INCREMENT,
            form_no INT NOT NULL,
            template_name VARCHAR(160) NOT NULL,
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
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_form_no (form_no),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->query($sql);
        $this->ensureIpdDocumentTemplateColumns();
    }

    private function ensureIpdDocumentTemplateColumns(): void
    {
        if (! $this->db->tableExists('ipd_document_templates')) {
            return;
        }

        $columns = [
            'page_size' => "ALTER TABLE ipd_document_templates ADD COLUMN page_size VARCHAR(16) NOT NULL DEFAULT 'A4' AFTER template_name",
            'custom_width_mm' => "ALTER TABLE ipd_document_templates ADD COLUMN custom_width_mm INT NOT NULL DEFAULT 210 AFTER page_size",
            'custom_height_mm' => "ALTER TABLE ipd_document_templates ADD COLUMN custom_height_mm INT NOT NULL DEFAULT 297 AFTER custom_width_mm",
            'page_margin_top_cm' => "ALTER TABLE ipd_document_templates ADD COLUMN page_margin_top_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER custom_height_mm",
            'page_margin_bottom_cm' => "ALTER TABLE ipd_document_templates ADD COLUMN page_margin_bottom_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_top_cm",
            'page_margin_left_cm' => "ALTER TABLE ipd_document_templates ADD COLUMN page_margin_left_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_bottom_cm",
            'page_margin_right_cm' => "ALTER TABLE ipd_document_templates ADD COLUMN page_margin_right_cm DECIMAL(5,2) NOT NULL DEFAULT 0.80 AFTER page_margin_left_cm",
            'margin_header_cm' => "ALTER TABLE ipd_document_templates ADD COLUMN margin_header_cm DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER page_margin_right_cm",
            'margin_footer_cm' => "ALTER TABLE ipd_document_templates ADD COLUMN margin_footer_cm DECIMAL(5,2) NOT NULL DEFAULT 0.50 AFTER margin_header_cm",
            'header_html' => "ALTER TABLE ipd_document_templates ADD COLUMN header_html LONGTEXT NULL AFTER margin_footer_cm",
            'footer_html' => "ALTER TABLE ipd_document_templates ADD COLUMN footer_html LONGTEXT NULL AFTER header_html",
            'template_css' => "ALTER TABLE ipd_document_templates ADD COLUMN template_css LONGTEXT NULL AFTER footer_html",
        ];

        foreach ($columns as $col => $sql) {
            try {
                $exists = $this->db->query("SHOW COLUMNS FROM ipd_document_templates LIKE '" . $col . "'")->getRowArray();
                if (empty($exists)) {
                    $this->db->query($sql);
                }
            } catch (\Throwable $e) {
                // Keep template screen usable even if schema alter fails in restricted env.
            }
        }
    }

    private function defaultIpdDocumentTemplates(): array
    {
        return [
            [
                'form_no' => 1,
                'template_name' => 'Legacy Face Form (IPD2)',
                'template_html' => <<<'HTML'
<style>
  .ipd-form { font-size: 12px; line-height: 1.35; }
  .ipd-form h3 { margin: 0 0 10px 0; text-align: center; }
  .ipd-form table { width: 100%; border-collapse: collapse; }
  .ipd-form .meta td { border: 1px solid #444; padding: 6px; vertical-align: top; }
</style>
<div class="ipd-form">
  <h3>BED HEAD TICKET</h3>
  <table class="meta">
    <tr>
      <td style="width:50%;">
        <b>Patient Information</b><br><br>
        <b>Patient Name:</b> {{PATIENT_NAME}}<br>
        <b>Age/Gender:</b> {{AGE_GENDER}}<br>
        <b>UHID:</b> {{UHID}}<br>
        <b>IPD Code:</b> {{IPD_CODE}}<br>
        <b>Date And Time of Admission:</b> {{ADMIT_DATE}}
      </td>
      <td style="width:50%;">
        <b>Hospital:</b> {{HOSPITAL_NAME}}<br>
        <b>Address:</b> {{HOSPITAL_ADDRESS}}<br>
        <b>Doctors:</b> {{DOCTORS}}<br>
        <b>Insurance:</b> {{INSURANCE_NAME}}
      </td>
    </tr>
  </table>

  <h3 style="font-size:18px;margin-top:12px;">सहमति पत्र</h3>
  <p style="font-size:15px;">
    मैं इलाज कराने का/की इच्छुक हूँ। मैं {{HOSPITAL_NAME}} के चिकित्सक और उनके सहायकों को अपना उपचार,
    परीक्षण, परामर्श, जांच, औषधि देने एवं आवश्यक चिकित्सा प्रक्रियाएं करने की स्वीकृति देता/देती हूँ।
    मुझे बीमारी, संभावित जटिलताओं और उपचार में होने वाले खर्च के बारे में समझा दिया गया है।
  </p>

  <p style="margin-top:18px;">हस्ताक्षर / अंगूठा निशान: __________________________</p>
  <p>नाम: __________________________ &nbsp;&nbsp; मरीज से संबंध: __________________________</p>
  <p>पता: __________________________ &nbsp;&nbsp; फ़ोन नंबर: __________________________</p>
</div>
HTML,
            ],
            [
                'form_no' => 3,
                'template_name' => 'Legacy Self Declaration (IPD2)',
                'template_html' => <<<'HTML'
<div style="font-size:12px;line-height:1.35;">
  <h3 style="text-align:center;margin:0 0 10px 0;">SELF DECLARATION FROM HEALTH INSURANCE CARD HOLDER</h3>
  <h3 style="text-align:center;margin:0 0 10px 0;">मेडिक्लेम बीमा कार्ड धारक द्वारा स्वघोषणा</h3>

  <table border="1" cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;">
    <tr>
      <td><b>Patient Name:</b> {{PATIENT_NAME}}</td>
      <td><b>UHID:</b> {{UHID}}</td>
      <td><b>IPD:</b> {{IPD_CODE}}</td>
    </tr>
    <tr>
      <td><b>Age/Gender:</b> {{AGE_GENDER}}</td>
      <td><b>Admission:</b> {{ADMIT_DATE}}</td>
      <td><b>Insurance:</b> {{INSURANCE_NAME}}</td>
    </tr>
  </table>

  <p style="margin-top:12px;">
    मैं __________________________ यह घोषणा करता/करती हूँ कि मरीज {{PATIENT_NAME}} का मेडिक्लेम {{INSURANCE_NAME}} में है,
    और भर्ती दिनांक {{ADMIT_DATE}} को हुई है। मैंने आवश्यक दस्तावेज जमा कर दिए हैं और यदि क्लेम पूर्ण/आंशिक स्वीकृत नहीं होता
    है तो शेष देय राशि का भुगतान स्वयं करूँगा/करूँगी।
  </p>

  <p>हस्ताक्षर / अंगूठा निशान: __________________________</p>
  <p>नाम: __________________________ &nbsp;&nbsp; मरीज से संबंध: __________________________</p>
  <p>पता: __________________________ &nbsp;&nbsp; फ़ोन नंबर: __________________________</p>
  <p>दिनांक: {{CURRENT_DATE}}</p>
</div>
HTML,
            ],
            [
                'form_no' => 5,
                'template_name' => 'Legacy Admission History & Physical Assessment',
                'template_html' => <<<'HTML'
<div style="font-size:11px;line-height:1.3;">
  <h3 style="text-align:center;margin:0 0 8px 0;">ADMISSION HISTORY AND PHYSICAL ASSESSMENT FORM</h3>
  <p><b>Patient:</b> {{PATIENT_NAME}} &nbsp; <b>Age/Gender:</b> {{AGE_GENDER}} &nbsp; <b>UHID:</b> {{UHID}} &nbsp; <b>IPD:</b> {{IPD_CODE}} &nbsp; <b>Admit Date:</b> {{ADMIT_DATE}}</p>
  <p><b>Diagnosis:</b> ______________________________________________</p>
  <p><b>Time of Patient Arrival:</b> ____________ &nbsp;&nbsp; <b>Time of Doctor Assessment:</b> ____________</p>
  <p><b>Consciousness:</b> [ ] Awake [ ] Alert [ ] In pain [ ] Response to verbal commands [ ] Unresponsive</p>
  <p><b>GCS:</b> E___ V___ M___ &nbsp;&nbsp; <b>Airway/Breathing:</b> [ ] Clear [ ] Noisy [ ] Stridor [ ] Obstruction</p>
  <p><b>Allergic To:</b> ______________________________________________</p>
  <p><b>Present Complaints:</b></p>
  <div style="border:1px solid #666;height:52px;"></div>

  <p style="margin-top:8px;"><b>Past Medical History:</b></p>
  <table border="1" cellpadding="4" cellspacing="0" style="width:100%;border-collapse:collapse;">
    <tr><td style="width:25%;">Diabetes / Metabolic</td><td style="width:25%;"></td><td style="width:25%;">Jaundice / CLD</td><td style="width:25%;"></td></tr>
    <tr><td>Hypertension / IHD</td><td></td><td>Osteoarthritis</td><td></td></tr>
    <tr><td>Asthma / COPD / TB</td><td></td><td>Tuberculosis</td><td></td></tr>
    <tr><td colspan="4">Others:</td></tr>
  </table>

  <p><b>Drug History:</b></p>
  <div style="border:1px solid #666;height:42px;"></div>
  <p><b>Past Surgical History:</b></p>
  <div style="border:1px solid #666;height:42px;"></div>

  <p><b>Declaration:</b> I hereby declare that the facts recorded above are accurate to the best of my knowledge.</p>
  <p>Name of Patient/Relative: ________________________ &nbsp; Signature: ________________________</p>
  <p>Date: {{CURRENT_DATE}}</p>
</div>
HTML,
            ],
            [
                'form_no' => 8,
                'template_name' => 'Legacy Progress Notes and Doctor Order',
                'template_html' => <<<'HTML'
<div style="font-size:11px;line-height:1.3;">
  <h3 style="text-align:center;margin:0 0 8px 0;">PROGRESS NOTES AND DOCTOR'S ORDER</h3>
  <p><b>Name:</b> {{PATIENT_NAME}} &nbsp; <b>IPD:</b> {{IPD_CODE}} &nbsp; <b>UHID:</b> {{UHID}} &nbsp; <b>Age/Gender:</b> {{AGE_GENDER}}</p>
  <hr>
  <table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;">
    <tr>
      <th style="width:18%;text-align:center;">DATE</th>
      <th style="text-align:center;">PROGRESS NOTES AND DOCTOR'S ORDER</th>
    </tr>
    <tr>
      <td style="height:420px;"></td>
      <td></td>
    </tr>
  </table>
</div>
HTML,
            ],
            [
                'form_no' => 9,
                'template_name' => 'Legacy Oral / Enteral Intake Output Chart',
                'template_html' => <<<'HTML'
<div style="font-size:11px;line-height:1.25;">
  <h3 style="text-align:center;margin:0 0 8px 0;">ORAL / ENTERAL INTAKE & OUTPUT CHART</h3>
  <p><b>Name:</b> {{PATIENT_NAME}} &nbsp; <b>IPD:</b> {{IPD_CODE}} &nbsp; <b>UHID:</b> {{UHID}} &nbsp; <b>Age/Gender:</b> {{AGE_GENDER}}</p>
  <hr>
  <table border="1" cellpadding="4" cellspacing="0" style="width:100%;border-collapse:collapse;">
    <tr>
      <th style="width:6%;">SR.No.</th>
      <th style="width:10%;">Date</th>
      <th style="width:8%;">Time</th>
      <th style="width:17%;">Description</th>
      <th style="width:7%;">Vol.</th>
      <th style="width:7%;">Vol.</th>
      <th style="width:7%;">Total</th>
      <th style="width:7%;">Urine</th>
      <th style="width:7%;">Stool</th>
      <th style="width:7%;">Other</th>
      <th style="width:7%;">Other</th>
      <th style="width:7%;">Other</th>
      <th style="width:7%;">Total</th>
    </tr>
    <tr><td>1</td><td style="height:24px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr><td>2</td><td style="height:24px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr><td>3</td><td style="height:24px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr><td>4</td><td style="height:24px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr><td>5</td><td style="height:24px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
  </table>
</div>
HTML,
            ],
            [
                'form_no' => 10,
                'template_name' => 'Legacy Sticker [2 x 6]',
                'template_html' => <<<'HTML'
<div style="font-size:9px;border:1px dashed #444;padding:4px;line-height:1.25;">
  <div><b>{{HOSPITAL_NAME}}</b></div>
  <div><b>Patient Name:</b> {{PATIENT_NAME}}</div>
  <div><b>Age / Gender:</b> {{AGE_GENDER}}</div>
  <div><b>UHID / Patient ID:</b> {{UHID}}</div>
  <div><b>IPD Code:</b> {{IPD_CODE}}</div>
  <div><b>Dept./Doctor:</b> {{DOCTORS}}</div>
  <div><b>Date of Admission:</b> {{ADMIT_DATE}}</div>
</div>
HTML,
            ],
            [
                'form_no' => 11,
                'template_name' => 'Legacy Sticker [2 x 8]',
                'template_html' => <<<'HTML'
<div style="font-size:9px;border:1px dashed #444;padding:4px;line-height:1.25;">
  <div><b>{{HOSPITAL_NAME}}</b></div>
  <div><b>{{HOSPITAL_ADDRESS}}</b></div>
  <div><b>Patient Name:</b> {{PATIENT_NAME}}</div>
  <div><b>Age / Gender:</b> {{AGE_GENDER}}</div>
  <div><b>UHID / Patient ID:</b> {{UHID}}</div>
  <div><b>IPD Code:</b> {{IPD_CODE}}</div>
  <div><b>Dept./Doctor:</b> {{DOCTORS}}</div>
  <div><b>Date of Admission:</b> {{ADMIT_DATE}}</div>
  <div><b>Insurance:</b> {{INSURANCE_NAME}}</div>
</div>
HTML,
            ],
        ];
    }

    private function ensureDefaultIpdDocumentTemplatesSeeded(): void
    {
        $this->ensureIpdDocumentTemplateTable();
        if (! $this->db->tableExists('ipd_document_templates')) {
            return;
        }

        $table = $this->db->table('ipd_document_templates');
        foreach ($this->defaultIpdDocumentTemplates() as $row) {
            $formNo = (int) ($row['form_no'] ?? 0);
            $templateName = (string) ($row['template_name'] ?? '');
            if ($formNo <= 0 || $templateName === '') {
                continue;
            }

            $exists = $this->db->table('ipd_document_templates')
                ->where('form_no', $formNo)
                ->where('template_name', $templateName)
                ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $table->insert([
                'form_no' => $formNo,
                'template_name' => $templateName,
                'page_size' => in_array($formNo, [10, 11], true) ? 'CUSTOM' : 'A4',
                'custom_width_mm' => $formNo === 10 ? 51 : ($formNo === 11 ? 51 : 210),
                'custom_height_mm' => $formNo === 10 ? 152 : ($formNo === 11 ? 203 : 297),
                'page_margin_top_cm' => in_array($formNo, [10, 11], true) ? 0.2 : 0.8,
                'page_margin_bottom_cm' => in_array($formNo, [10, 11], true) ? 0.2 : 0.8,
                'page_margin_left_cm' => in_array($formNo, [10, 11], true) ? 0.2 : 0.8,
                'page_margin_right_cm' => in_array($formNo, [10, 11], true) ? 0.2 : 0.8,
                'margin_header_cm' => 0.5,
                'margin_footer_cm' => 0.5,
                'template_html' => (string) ($row['template_html'] ?? ''),
                'status' => 1,
            ]);
        }

        // Upgrade legacy sticker templates created before page-size columns existed.
        $this->db->table('ipd_document_templates')
            ->whereIn('form_no', [10, 11])
            ->where('page_size', 'A4')
            ->where('custom_width_mm', 210)
            ->where('custom_height_mm', 297)
            ->where('template_name LIKE', 'Legacy Sticker%')
            ->set('page_size', 'CUSTOM')
            ->set('custom_width_mm', 'CASE WHEN form_no=10 THEN 51 ELSE 51 END', false)
            ->set('custom_height_mm', 'CASE WHEN form_no=10 THEN 152 ELSE 203 END', false)
            ->set('page_margin_top_cm', 0.2)
            ->set('page_margin_bottom_cm', 0.2)
            ->set('page_margin_left_cm', 0.2)
            ->set('page_margin_right_cm', 0.2)
            ->update();
    }

    public function ipd_document_templates()
    {
        if ($resp = $this->requireAnyPermission(['template.ipd_document', 'template.discharge'])) {
            return $resp;
        }

        $this->ensureDefaultIpdDocumentTemplatesSeeded();

        $notice = '';
        $noticeType = 'success';

        if (strtolower($this->request->getMethod()) === 'post') {
            $id = (int) ($this->request->getPost('id') ?? 0);
            $formNo = (int) ($this->request->getPost('form_no') ?? 0);
            $templateName = trim((string) ($this->request->getPost('template_name') ?? ''));
            $templateHtml = (string) ($this->request->getPost('template_html') ?? '');
            $headerHtml = (string) ($this->request->getPost('header_html') ?? '');
            $footerHtml = (string) ($this->request->getPost('footer_html') ?? '');
            $templateCss = (string) ($this->request->getPost('template_css') ?? '');
            $pageSize = strtoupper(trim((string) ($this->request->getPost('page_size') ?? 'A4')));
            if (! in_array($pageSize, ['A4', 'A4-L', 'A5', 'A6', 'LETTER', 'LEGAL', 'CUSTOM'], true)) {
                $pageSize = 'A4';
            }
            $customWidthMm = (int) ($this->request->getPost('custom_width_mm') ?? 210);
            $customHeightMm = (int) ($this->request->getPost('custom_height_mm') ?? 297);
            $customWidthMm = max(20, min(600, $customWidthMm));
            $customHeightMm = max(20, min(1000, $customHeightMm));

            $marginTop = max(0, min(25, (float) ($this->request->getPost('page_margin_top_cm') ?? 0.8)));
            $marginBottom = max(0, min(25, (float) ($this->request->getPost('page_margin_bottom_cm') ?? 0.8)));
            $marginLeft = max(0, min(25, (float) ($this->request->getPost('page_margin_left_cm') ?? 0.8)));
            $marginRight = max(0, min(25, (float) ($this->request->getPost('page_margin_right_cm') ?? 0.8)));
            $marginHeader = max(0, min(25, (float) ($this->request->getPost('margin_header_cm') ?? 0.5)));
            $marginFooter = max(0, min(25, (float) ($this->request->getPost('margin_footer_cm') ?? 0.5)));
            $status = (int) ($this->request->getPost('status') ?? 1) === 1 ? 1 : 0;

            if ($formNo <= 0) {
                $notice = 'Form number must be a positive integer.';
                $noticeType = 'danger';
            } elseif ($templateName === '' || trim($templateHtml) === '') {
                $notice = 'Form number, template name and HTML are required.';
                $noticeType = 'danger';
            } else {
                $table = $this->db->table('ipd_document_templates');
                $data = [
                    'form_no' => $formNo,
                    'template_name' => $templateName,
                    'page_size' => $pageSize,
                    'custom_width_mm' => $customWidthMm,
                    'custom_height_mm' => $customHeightMm,
                    'page_margin_top_cm' => $marginTop,
                    'page_margin_bottom_cm' => $marginBottom,
                    'page_margin_left_cm' => $marginLeft,
                    'page_margin_right_cm' => $marginRight,
                    'margin_header_cm' => $marginHeader,
                    'margin_footer_cm' => $marginFooter,
                    'header_html' => $headerHtml,
                    'footer_html' => $footerHtml,
                    'template_css' => $templateCss,
                    'template_html' => $templateHtml,
                    'status' => $status,
                ];

                if ($id > 0) {
                    $table->where('id', $id)->update($data);
                    $notice = 'Template updated.';
                } else {
                    $table->insert($data);
                    $notice = 'Template created.';
                }
            }
        }

        $editId = (int) ($this->request->getGet('edit') ?? 0);
        $editRow = [];
        if ($editId > 0) {
            $editRow = $this->db->table('ipd_document_templates')
                ->where('id', $editId)
                ->get(1)
                ->getRowArray() ?? [];
        }

        $rows = $this->db->table('ipd_document_templates')
            ->orderBy('form_no', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return view('Setting/Template/ipd_document_templates', [
            'rows' => $rows,
            'edit_row' => $editRow,
            'notice' => $notice,
            'notice_type' => $noticeType,
        ]);
    }

    public function ipd_document_template_delete(int $id)
    {
        if ($resp = $this->requireAnyPermission(['template.ipd_document', 'template.discharge'])) {
            return $resp;
        }

        $id = (int) $id;
        if ($id > 0 && $this->db->tableExists('ipd_document_templates')) {
            $this->db->table('ipd_document_templates')->where('id', $id)->delete();
        }

        return redirect()->to(base_url('setting/template/ipd_document_templates'));
    }
}
