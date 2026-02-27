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
}
