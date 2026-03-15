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

    public function diagnosis_print_settings(int $modality = 3)
    {
        if ($resp = $this->requireAnyPermission(['template.pathology', 'template.ultrasound', 'template.xray', 'template.ct', 'template.mri', 'template.echo'])) {
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
                    'page_margin_top_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_top_cm'), 1.2),
                    'page_margin_bottom_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_bottom_cm'), 1.2),
                    'page_margin_left_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_left_cm'), 1.0),
                    'page_margin_right_cm' => $this->normalizeMarginCm($this->request->getPost('page_margin_right_cm'), 1.0),
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
}
