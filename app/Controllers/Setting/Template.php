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

        $sql = "select g.RepoGrp, r.Title, r.mstRepoKey
            from (lab_repo r join lab_rgroups g on r.GrpKey=g.mstRGrpKey)
            join hc_items i on r.charge_id=i.id and i.itype in (5,6)";
        $query = $this->db->query($sql);
        $data['labReport_master'] = $query->getResult();

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

        $sql = "select j.id, r.mstRepoKey, t.mstTestKey, t.Test, t.TestID, t.Result, j.EOrder
            from lab_repo r join lab_repotests j join lab_tests t
            on r.mstRepoKey=j.mstRepoKey and j.mstTestKey=t.mstTestKey
            where r.mstRepoKey=" . (int) $repoId . " order by j.EOrder";
        $query = $this->db->query($sql);
        $data['lab_Rep_Item_List'] = $query->getResult();
        $data['mstRepoKey'] = $repoId;

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
            'Title' => $inputReportName,
            'GrpKey' => $groupId,
            'charge_id' => $chargeId,
            'HTMLData' => $htmlData,
            'RTFData' => $htmlData,
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
            'Title' => $inputReportName,
            'GrpKey' => $groupId,
            'charge_id' => $chargeId,
            'HTMLData' => $htmlData,
            'RTFData' => $htmlData,
        ]);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'showcontent' => $insertId > 0 ? 'Data Saved successfully' : 'Unable to save data',
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
        $chargeId = (int) $this->request->getPost('charge_id');
        $groupId = (string) $this->request->getPost('group_id');
        $htmlData = (string) $this->request->getPost('HTMLData');
        $impression = (string) $this->request->getPost('Impression');

        if ($inputReportName === '') {
            return $this->response->setJSON([
                'insertid' => 0,
                'showcontent' => 'Report name is required.',
            ]);
        }

        $pathLab = new PathLabModel();
        $insertId = $pathLab->insertUltrasoundReport([
            'template_name' => $inputReportName,
            'title' => $groupId,
            'charge_id' => $chargeId,
            'Findings' => $htmlData,
            'Impression' => $impression,
            'modality' => $modality,
        ]);

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
        $chargeId = (int) $this->request->getPost('charge_id');
        $groupId = (string) $this->request->getPost('group_id');
        $htmlData = (string) $this->request->getPost('HTMLData');
        $impression = (string) $this->request->getPost('Impression');

        if ($repoId <= 0 || $inputReportName === '') {
            return $this->response->setJSON([
                'update_record' => 0,
                'showcontent' => 'Report name is required.',
            ]);
        }

        $pathLab = new PathLabModel();
        $pathLab->updateUltrasoundReport([
            'template_name' => $inputReportName,
            'title' => $groupId,
            'charge_id' => $chargeId,
            'Findings' => $htmlData,
            'Impression' => $impression,
            'modality' => $modality,
        ], $repoId);

        return $this->response->setJSON([
            'update_record' => 1,
            'showcontent' => 'Data Saved successfully',
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

        if (strtolower($this->request->getMethod()) === 'post') {
            $modality = (int) ($this->request->getPost('modality') ?? $modality);
            if (! in_array($modality, [1, 2, 3, 4, 5, 6], true)) {
                $modality = 3;
            }

            $selectedTemplateId = (int) ($this->request->getPost('template_id') ?? 0);

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

                $templateName = trim((string) ($this->request->getPost('template_name') ?? ''));
                if ($templateName === '') {
                    $templateName = 'Template ' . date('d-m-Y H:i');
                }

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
                    'is_default' => (int) ($this->request->getPost('is_default') ?? 0) === 1 ? 1 : 0,
                    'status' => 1,
                ];

                if (! $hasSignatureImageColumn) {
                    unset($data['signature_image']);
                }

                if ($noticeType !== 'danger') {
                    if ($data['is_default'] === 1) {
                        $templateTable
                            ->where('modality', $modality)
                            ->set('is_default', 0)
                            ->update();
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
            'notice' => $notice,
            'notice_type' => $noticeType,
            'columns_ready' => $columnsReady,
            'modality_list' => $modalityList,
                'has_signature_image_column' => $hasSignatureImageColumn,
            'has_signature_image_column' => $hasSignatureImageColumn,
        ]);
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

    public function discharge_templates()
    {
        if ($resp = $this->requireAnyPermission(['template.discharge'])) {
            return $resp;
        }

        $this->ensureDefaultDischargeTemplateSeeded();

        $notice = '';
        $noticeType = 'success';

        if (strtolower($this->request->getMethod()) === 'post') {
            $id = (int) ($this->request->getPost('id') ?? 0);
            $templateName = trim((string) ($this->request->getPost('template_name') ?? ''));
            $templateHtml = (string) ($this->request->getPost('template_html') ?? '');
            $isDefault = (int) ($this->request->getPost('is_default') ?? 0) === 1 ? 1 : 0;
            $status = (int) ($this->request->getPost('status') ?? 1) === 1 ? 1 : 0;

            if ($templateName === '' || trim($templateHtml) === '') {
                $notice = 'Template name and template HTML are required.';
                $noticeType = 'danger';
            } else {
                $table = $this->db->table('ipd_discharge_templates');
                $data = [
                    'template_name' => $templateName,
                    'template_html' => $templateHtml,
                    'is_default' => $isDefault,
                    'status' => $status,
                ];

                if ($id > 0) {
                    $table->where('id', $id)->update($data);
                    $notice = 'Template updated.';
                } else {
                    $table->insert($data);
                    $id = (int) $this->db->insertID();
                    $notice = 'Template created.';
                }

                if ($isDefault === 1 && $id > 0) {
                    $this->db->table('ipd_discharge_templates')
                        ->where('id <>', $id)
                        ->update(['is_default' => 0]);
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

    public function discharge_template_delete(int $id)
    {
        if ($resp = $this->requireAnyPermission(['template.discharge'])) {
            return $resp;
        }

        $id = (int) $id;
        if ($id > 0 && $this->db->tableExists('ipd_discharge_templates')) {
            $this->db->table('ipd_discharge_templates')->where('id', $id)->delete();
        }

        return redirect()->to(base_url('setting/template/discharge_templates'));
    }

    private function ensureIpdDocumentTemplateTable(): void
    {
        if ($this->db->tableExists('ipd_document_templates')) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS ipd_document_templates (
            id INT NOT NULL AUTO_INCREMENT,
            form_no INT NOT NULL,
            template_name VARCHAR(160) NOT NULL,
            template_html LONGTEXT NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_form_no (form_no),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->query($sql);
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
                'template_html' => (string) ($row['template_html'] ?? ''),
                'status' => 1,
            ]);
        }
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
            $status = (int) ($this->request->getPost('status') ?? 1) === 1 ? 1 : 0;

            if (! in_array($formNo, [1, 3, 5, 8, 9, 10, 11], true)) {
                $notice = 'Invalid form number.';
                $noticeType = 'danger';
            } elseif ($templateName === '' || trim($templateHtml) === '') {
                $notice = 'Form number, template name and HTML are required.';
                $noticeType = 'danger';
            } else {
                $table = $this->db->table('ipd_document_templates');
                $data = [
                    'form_no' => $formNo,
                    'template_name' => $templateName,
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
