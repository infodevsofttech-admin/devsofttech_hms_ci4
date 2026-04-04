<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\DoctorModel;

class Doctor extends BaseController
{
    private const OPD_TEMPLATE_KEY_PREFIX = 'OPD_PRINT_TEMPLATE__';

    public function index(): string
    {
        $doctorModel = new DoctorModel();

        return view('Setting/Doctor/Doctor_Search_V', [
            'data' => $doctorModel->getDoctors(),
        ]);
    }

    public function create(): string
    {
        $templateContext = $this->getDoctorTemplateContext();

        return view('Setting/Doctor/Doctor_V', [
            'errors' => session('errors'),
            'template_options' => $templateContext['options'],
            'opd_print_template_options' => $templateContext['opd_print_options'],
            'template_fields' => $templateContext['fields'],
        ]);
    }

    public function store()
    {
        $rules = [
            'input_name' => 'required',
            'input_email' => 'permit_empty|valid_email',
            'input_mphone1' => 'permit_empty|numeric',
        ];

        if (! $this->validate($rules)) {
            if ($this->request->isAJAX()) {
                $templateContext = $this->getDoctorTemplateContext();
                return view('Setting/Doctor/Doctor_V', [
                    'errors' => $this->validator->getErrors(),
                    'formData' => $this->request->getPost(),
                    'template_options' => $templateContext['options'],
                    'opd_print_template_options' => $templateContext['opd_print_options'],
                    'template_fields' => $templateContext['fields'],
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $doctorModel = new DoctorModel();
        $regField = $this->resolveDoctorRegField();
        $hprField = $this->resolveDoctorHprField();
        $shortDescription = trim((string) $this->request->getPost('txt_doc_sign'));
        $regInput = trim((string) $this->request->getPost('input_doc_reg_no'));
        $hprInput = strtoupper(trim((string) $this->request->getPost('input_hpr_id')));
        if ($regInput === '' && $shortDescription !== '') {
            $regInput = $this->extractRegNoFromText($shortDescription);
        }

        if ($hprInput !== '' && ! $this->isValidHprId($hprInput)) {
            $errors = ['HPR ID format is invalid. Use alphanumeric characters with / or -'];
            if ($this->request->isAJAX()) {
                $templateContext = $this->getDoctorTemplateContext();
                return view('Setting/Doctor/Doctor_V', [
                    'errors' => $errors,
                    'formData' => $this->request->getPost(),
                    'template_options' => $templateContext['options'],
                    'opd_print_template_options' => $templateContext['opd_print_options'],
                    'template_fields' => $templateContext['fields'],
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data = [
            'p_title' => $this->request->getPost('select_title'),
            'mphone1' => $this->request->getPost('input_mphone1'),
            'p_fname' => $this->request->getPost('input_name'),
            'gender' => $this->request->getPost('optionsRadios_gender'),
            'dob' => str_to_MysqlDate((string) $this->request->getPost('datepicker_dob')),
            'zip' => $this->request->getPost('input_zip'),
            'email1' => $this->request->getPost('input_email'),
            'doc_sign' => $shortDescription,
        ];

        $templateContext = $this->getDoctorTemplateContext();
        $templateFieldMap = [
            'opd_print_format' => 'tmpl_opd_print_format',
            'opd_blank_print' => 'tmpl_opd_blank_print',
            'rx_pre_print_letter_head_format' => 'tmpl_rx_pre_print_letter_head_format',
            'rx_blank_letter_head' => 'tmpl_rx_blank_letter_head',
            'rx_plain_paper' => 'tmpl_rx_plain_paper',
        ];
        foreach ($templateFieldMap as $dbField => $inputField) {
            if (! in_array($dbField, $templateContext['fields'], true)) {
                continue;
            }
            $templateName = $this->normalizeTemplateId((string) $this->request->getPost($inputField));
            $data[$dbField] = $templateName;
        }

        if ($regField !== null) {
            $data[$regField] = $regInput;
        }
        if ($hprField !== null) {
            $data[$hprField] = $hprInput;
        }

        $insertId = $doctorModel->insert($data);

        if ($insertId <= 0) {
            $errors = ['Unable to create doctor.'];
            if ($this->request->isAJAX()) {
                $templateContext = $this->getDoctorTemplateContext();
                return view('Setting/Doctor/Doctor_V', [
                    'errors' => $errors,
                    'formData' => $this->request->getPost(),
                    'template_options' => $templateContext['options'],
                    'opd_print_template_options' => $templateContext['opd_print_options'],
                    'template_fields' => $templateContext['fields'],
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $errors);
        }

        return $this->doctorRecord($insertId);
    }

    public function doctorRecord(int $id): string
    {
        $doctorModel = new DoctorModel();
        $templateContext = $this->getDoctorTemplateContext();

        return view('Setting/Doctor/Doctor_profile_V', [
            'data' => $doctorModel->getDoctorById($id),
            'doc_spec_a' => $doctorModel->getDoctorSpecs($id),
            'doc_spec_l' => $doctorModel->getSpecsList(),
            'doc_fee_type' => $doctorModel->getFeeTypes(),
            'doc_fee_list' => $doctorModel->getDoctorFees($id),
            'doc_ipd_fee_list' => $doctorModel->getDoctorIpdFees($id),
            'doc_ipd_fee_type' => $doctorModel->getIpdFeeTypes(),
            'template_options' => $templateContext['options'],
            'opd_print_template_options' => $templateContext['opd_print_options'],
            'template_fields' => $templateContext['fields'],
        ]);
    }

    public function updateRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $doctorModel = new DoctorModel();
        $docId = (int) $this->request->getPost('doc_id');
        $regField = $this->resolveDoctorRegField();
        $hprField = $this->resolveDoctorHprField();
        $shortDescription = trim((string) $this->request->getPost('txt_doc_sign'));
        $regInput = trim((string) $this->request->getPost('input_doc_reg_no'));
        $hprInput = strtoupper(trim((string) $this->request->getPost('input_hpr_id')));
        if ($regInput === '' && $shortDescription !== '') {
            $regInput = $this->extractRegNoFromText($shortDescription);
        }

        if ($hprInput !== '' && ! $this->isValidHprId($hprInput)) {
            return $this->response->setJSON([
                'update' => 0,
                'showcontent' => '',
                'error_text' => 'HPR ID format is invalid. Use alphanumeric characters with / or -',
            ]);
        }

        $data = [
            'p_title' => $this->request->getPost('select_title'),
            'mphone1' => $this->request->getPost('input_mphone1'),
            'p_fname' => $this->request->getPost('input_name'),
            'gender' => $this->request->getPost('optionsRadios_gender'),
            'dob' => str_to_MysqlDate((string) $this->request->getPost('datepicker_dob')),
            'zip' => $this->request->getPost('input_zip'),
            'doc_sign' => $shortDescription,
            'email1' => $this->request->getPost('input_email'),
        ];

        $templateContext = $this->getDoctorTemplateContext();
        $templateFieldMap = [
            'opd_print_format' => 'tmpl_opd_print_format',
            'opd_blank_print' => 'tmpl_opd_blank_print',
            'rx_pre_print_letter_head_format' => 'tmpl_rx_pre_print_letter_head_format',
            'rx_blank_letter_head' => 'tmpl_rx_blank_letter_head',
            'rx_plain_paper' => 'tmpl_rx_plain_paper',
        ];
        foreach ($templateFieldMap as $dbField => $inputField) {
            if (! in_array($dbField, $templateContext['fields'], true)) {
                continue;
            }
            $templateName = $this->normalizeTemplateId((string) $this->request->getPost($inputField));
            $data[$dbField] = $templateName;
        }

        if ($regField !== null) {
            $data[$regField] = $regInput;
        }
        if ($hprField !== null) {
            $data[$hprField] = $hprInput;
        }

        $updated = $doctorModel->updateDoctor($data, $docId);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'showcontent' => $updated ? 'Data Saved successfully' : '',
            'error_text' => $updated ? '' : 'Please Check',
        ]);
    }

    public function doctorRecordSpec()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $doctorModel = new DoctorModel();
        $docId = (int) $this->request->getPost('doc_id');
        $isAdd = (int) $this->request->getPost('isadd') === 1;

        if ($isAdd) {
            $specId = (int) $this->request->getPost('doc_spec');
            if ($specId > 0 && ! $doctorModel->specExists($docId, $specId)) {
                $doctorModel->insertSpec([
                    'doc_id' => $docId,
                    'med_spec_id' => $specId,
                ]);
            }
        } else {
            $specId = (int) $this->request->getPost('doc_spec_id');
            if ($specId > 0) {
                $doctorModel->removeSpec($specId);
            }
        }

        $specListHtml = $this->renderSpecList($doctorModel->getDoctorSpecs($docId));

        return $this->response->setJSON([
            'update' => 1,
            'show_Specility_list' => $specListHtml,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function addFee()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['inser_id' => 0, 'error_text' => 'Invalid request']);
        }

        $doctorModel = new DoctorModel();
        $docId = (int) $this->request->getPost('doc_id');
        $feeType = (int) $this->request->getPost('fee_type');

        $existing = $doctorModel->getDoctorFeeByType($docId, $feeType);
        if ($existing !== null) {
            $doctorModel->removeFee((int) $existing->id, $this->getFeeAuditData());
        }

        $insertId = $doctorModel->insertFee([
            'doc_id' => $docId,
            'doc_fee_desc' => $this->request->getPost('input_fee_desc'),
            'doc_fee_type' => $feeType,
            'amount' => $this->request->getPost('input_fee_amount'),
        ]);

        $feeList = $doctorModel->getDoctorFees($docId);

        return $this->response->setJSON([
            'inser_id' => $insertId > 0 ? 1 : 0,
            'showcontent' => $insertId > 0 ? 'Fee added successfully' : '',
            'error_text' => $insertId > 0 ? '' : 'Please Check',
            'show_fee_list' => $this->renderFeeList($feeList),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function addIpdFee()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['inser_id' => 0, 'error_text' => 'Invalid request']);
        }

        $doctorModel = new DoctorModel();
        $docId = (int) $this->request->getPost('doc_id');
        $feeType = (int) $this->request->getPost('fee_type');

        $existing = $doctorModel->getDoctorIpdFeeByType($docId, $feeType);
        if ($existing !== null) {
            $doctorModel->removeIpdFee((int) $existing->id);
        }

        $insertId = $doctorModel->insertIpdFee([
            'doc_id' => $docId,
            'doc_fee_desc' => $this->request->getPost('input_fee_desc'),
            'doc_fee_type' => $feeType,
            'amount' => $this->request->getPost('input_fee_amount'),
        ]);

        $feeList = $doctorModel->getDoctorIpdFees($docId);

        return $this->response->setJSON([
            'inser_id' => $insertId > 0 ? 1 : 0,
            'showcontent' => $insertId > 0 ? 'IPD Fee added successfully' : '',
            'error_text' => $insertId > 0 ? '' : 'Please Check',
            'show_fee_list' => $this->renderFeeList($feeList),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function removeFee()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $doctorModel = new DoctorModel();
        $docId = (int) $this->request->getPost('doc_id');
        $removeId = (int) $this->request->getPost('rid');

        $deleted = $doctorModel->removeFee($removeId, $this->getFeeAuditData());

        $feeList = $doctorModel->getDoctorFees($docId);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'showcontent' => $deleted ? 'Fee Removed successfully' : '',
            'error_text' => $deleted ? '' : 'Please Check',
            'show_fee_list' => $this->renderFeeList($feeList),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function removeIpdFee()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $doctorModel = new DoctorModel();
        $docId = (int) $this->request->getPost('doc_id');
        $removeId = (int) $this->request->getPost('rid');

        $deleted = $doctorModel->removeIpdFee($removeId);

        $feeList = $doctorModel->getDoctorIpdFees($docId);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'showcontent' => $deleted ? 'IPD Fee Removed successfully' : '',
            'error_text' => $deleted ? '' : 'Please Check',
            'show_fee_list' => $this->renderFeeList($feeList),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function specs(): string
    {
        $doctorModel = new DoctorModel();

        return view('Setting/Doctor/Spec_List', [
            'specs' => $doctorModel->getMedSpecs(),
        ]);
    }

    public function ipdFeeTypes(): string
    {
        $doctorModel = new DoctorModel();

        return view('Setting/Doctor/Ipd_FeeType_List', [
            'feeTypes' => $doctorModel->getIpdFeeTypes(),
        ]);
    }

    public function storeIpdFeeType()
    {
        $name = trim((string) $this->request->getPost('fee_type'));
        if ($name === '') {
            return $this->renderIpdFeeTypeListWithErrors(['fee_type' => 'Fee type is required.']);
        }

        $doctorModel = new DoctorModel();
        $doctorModel->insertIpdFeeType($name);

        return $this->renderIpdFeeTypeListWithMessage('IPD fee type added successfully.');
    }

    public function updateIpdFeeType()
    {
        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('fee_type'));
        if ($id <= 0 || $name === '') {
            return $this->renderIpdFeeTypeListWithErrors(['fee_type' => 'Fee type is required.']);
        }

        $doctorModel = new DoctorModel();
        $doctorModel->updateIpdFeeType($id, $name);

        return $this->renderIpdFeeTypeListWithMessage('IPD fee type updated successfully.');
    }

    public function deleteIpdFeeType()
    {
        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->renderIpdFeeTypeListWithErrors(['fee_type' => 'Invalid fee type.']);
        }

        $doctorModel = new DoctorModel();
        $doctorModel->deleteIpdFeeType($id);

        return $this->renderIpdFeeTypeListWithMessage('IPD fee type deleted successfully.');
    }

    public function storeSpec()
    {
        $name = trim((string) $this->request->getPost('SpecName'));
        if ($name === '') {
            return $this->renderSpecListWithErrors(['SpecName' => 'Spec name is required.']);
        }

        $doctorModel = new DoctorModel();
        $doctorModel->insertMedSpec($name);

        return $this->renderSpecListWithMessage('Speciality added successfully.');
    }

    public function updateSpec()
    {
        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('SpecName'));
        if ($id <= 0 || $name === '') {
            return $this->renderSpecListWithErrors(['SpecName' => 'Spec name is required.']);
        }

        $doctorModel = new DoctorModel();
        $doctorModel->updateMedSpec($id, $name);

        return $this->renderSpecListWithMessage('Speciality updated successfully.');
    }

    public function deleteSpec()
    {
        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->renderSpecListWithErrors(['SpecName' => 'Invalid speciality.']);
        }

        $doctorModel = new DoctorModel();
        $doctorModel->deleteMedSpec($id);

        return $this->renderSpecListWithMessage('Speciality deleted successfully.');
    }

    private function renderSpecList(array $specs): string
    {
        $html = '';
        foreach ($specs as $row) {
            $name = esc($row->SpecName ?? '');
            $docSpecId = (int) ($row->doc_spec_id ?? 0);
            $html .= '<div class="input-group input-group-sm">';
            $html .= '<input class="form-control" type="text" value="' . $name . '" readonly />';
            $html .= '<span class="input-group-btn">';
            $html .= '<button type="button" class="btn btn-info btn-flat" onclick="remove_doc_spec(' . $docSpecId . ')">Remove -</button>';
            $html .= '</span>';
            $html .= '</div>';
        }

        return $html;
    }

    private function renderFeeList(array $feeList): string
    {
        $html = '<table class="table table-striped">';
        $html .= '<thead><tr><th>Fee Type</th><th>Description</th><th>Amount</th><th></th></tr></thead>';
        $html .= '<tbody>';
        foreach ($feeList as $row) {
            $feeType = esc($row->fee_type ?? '');
            $desc = esc($row->doc_fee_desc ?? '');
            $amount = esc($row->amount ?? '');
            $button = 'Not Define';
            if (! empty($row->id)) {
                $button = '<a href="javascript:remove_fees(' . (int) $row->id . ')">Remove</a>';
            }
            $html .= '<tr><td>' . $feeType . '</td><td>' . $desc . '</td><td>' . $amount . '</td><td>' . $button . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    private function renderSpecListWithMessage(string $message)
    {
        $doctorModel = new DoctorModel();

        return view('Setting/Doctor/Spec_List', [
            'specs' => $doctorModel->getMedSpecs(),
            'message' => $message,
        ]);
    }

    private function renderSpecListWithErrors(array $errors)
    {
        $doctorModel = new DoctorModel();

        return view('Setting/Doctor/Spec_List', [
            'specs' => $doctorModel->getMedSpecs(),
            'errors' => $errors,
        ]);
    }

    private function renderIpdFeeTypeListWithMessage(string $message)
    {
        $doctorModel = new DoctorModel();

        return view('Setting/Doctor/Ipd_FeeType_List', [
            'feeTypes' => $doctorModel->getIpdFeeTypes(),
            'message' => $message,
        ]);
    }

    private function renderIpdFeeTypeListWithErrors(array $errors)
    {
        $doctorModel = new DoctorModel();

        return view('Setting/Doctor/Ipd_FeeType_List', [
            'feeTypes' => $doctorModel->getIpdFeeTypes(),
            'errors' => $errors,
        ]);
    }

    private function getFeeAuditData(): array
    {
        $user = auth()->user();
        $name = 'System';
        if ($user !== null) {
            $name = trim(($user->username ?? '') . ' ' . ($user->email ?? ''));
        }

        return [
            'remove_update_by' => trim($name) . ' [' . date('d-m-Y H:i') . ']',
        ];
    }

    private function resolveDoctorRegField(): ?string
    {
        if (! $this->db->tableExists('doctor_master')) {
            return null;
        }

        $fields = $this->db->getFieldNames('doctor_master');
        foreach (['nmc_reg_no', 'mci_reg_no', 'registration_no', 'reg_no', 'doctor_reg_no', 'doc_reg_no', 'council_reg_no'] as $field) {
            if (in_array($field, $fields, true)) {
                return $field;
            }
        }

        return null;
    }

    private function resolveDoctorHprField(): ?string
    {
        if (! $this->db->tableExists('doctor_master')) {
            return null;
        }

        $fields = $this->db->getFieldNames('doctor_master');
        foreach (['hpr_id', 'hpr_no', 'hpr_number'] as $field) {
            if (in_array($field, $fields, true)) {
                return $field;
            }
        }

        return null;
    }

    private function isValidHprId(string $value): bool
    {
        return preg_match('/^[A-Z0-9\/-]{6,40}$/', $value) === 1;
    }

    private function extractRegNoFromText(string $text): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($text));
        if ($normalized === null || $normalized === '') {
            return '';
        }

        $patterns = [
            '/(?:reg(?:istration)?\s*(?:no|number)?\s*[:\-]?\s*)([A-Z0-9\/-]{4,})/i',
            '/(?:nmc|mci)\s*(?:reg(?:istration)?\s*(?:no|number)?\s*[:\-]?\s*)([A-Z0-9\/-]{4,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches) === 1 && ! empty($matches[1])) {
                return trim((string) $matches[1]);
            }
        }

        return '';
    }

    /**
     * @return array{fields:array<int,string>,options:array<int,string>}
     */
    private function getDoctorTemplateContext(): array
    {
        $allTemplateFields = [
            'opd_print_format',
            'opd_blank_print',
            'rx_pre_print_letter_head_format',
            'rx_blank_letter_head',
            'rx_plain_paper',
        ];

        if (! $this->db->tableExists('doctor_master')) {
            return ['fields' => [], 'options' => [], 'opd_print_options' => []];
        }

        $doctorFields = $this->db->getFieldNames('doctor_master') ?? [];
        $availableFields = array_values(array_intersect($allTemplateFields, $doctorFields));

        $options = $this->collectStoredTemplateOptions();
        $opdPrintOptions = $options;

        $options = array_values(array_unique($options));
        sort($options);
        $opdPrintOptions = array_values(array_unique($opdPrintOptions));
        sort($opdPrintOptions);

        return [
            'fields' => $availableFields,
            'options' => $options,
            'opd_print_options' => $opdPrintOptions,
        ];
    }

    private function normalizeTemplateId(string $templateId): string
    {
        $templateId = strtolower(trim($templateId));
        $templateId = str_replace([' ', '.'], '_', $templateId);
        $templateId = preg_replace('/[^a-z0-9_\-]+/', '_', $templateId) ?? '';
        $templateId = preg_replace('/_{2,}/', '_', $templateId) ?? $templateId;

        return trim($templateId, '_-');
    }

    /**
     * @return array<int, string>
     */
    private function collectStoredTemplateOptions(): array
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return [];
        }

        $rows = $this->db->table('hospital_setting')
            ->select('s_name')
            ->like('s_name', self::OPD_TEMPLATE_KEY_PREFIX, 'after')
            ->get()
            ->getResultArray();

        $options = [];
        foreach ($rows as $row) {
            $storageKey = (string) ($row['s_name'] ?? '');
            if (! str_starts_with($storageKey, self::OPD_TEMPLATE_KEY_PREFIX)) {
                continue;
            }

            $templateId = substr($storageKey, strlen(self::OPD_TEMPLATE_KEY_PREFIX));
            $templateId = $this->normalizeTemplateId((string) $templateId);
            if ($templateId !== '') {
                $options[] = $templateId;
            }
        }

        return $options;
    }

}
