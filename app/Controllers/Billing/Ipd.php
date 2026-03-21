<?php

namespace App\Controllers\Billing;

use App\Controllers\BaseController;
use App\Models\BedAssignmentHistoryModel;
use App\Models\ItemIpdModel;
use App\Models\IpdBillingModel;
use App\Models\IpdModel;
use App\Models\NursingBedsideItemModel;
use App\Models\PaymentModel;
use Mpdf\Mpdf;

class Ipd extends BaseController
{
    protected IpdBillingModel $ipdModel;
    protected BedAssignmentHistoryModel $bedAssignmentModel;
    protected ItemIpdModel $itemIpdModel;
    protected IpdModel $ipdEditModel;
    protected NursingBedsideItemModel $nursingBedsideItemModel;

    public function __construct()
    {
        $this->ipdModel = new IpdBillingModel();
        $this->bedAssignmentModel = new BedAssignmentHistoryModel();
        $this->itemIpdModel = new ItemIpdModel();
        $this->ipdEditModel = new IpdModel();
        $this->nursingBedsideItemModel = new NursingBedsideItemModel();
        helper(['common', 'form', 'age']);
    }

    public function index()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.access',
        ]);
        if ($permission) {
            return $permission;
        }

        return view('billing/ipd/dashboard');
    }

    public function currentAdmission()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        $data['records'] = $this->ipdModel->getCurrentAdmissions();

        return view('billing/ipd/current_admission', $data);
    }

    public function ipdInvoices()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $data['ipdCode'] = (string) ($this->request->getGet('ipd_code') ?? '');

        return view('billing/ipd/invoice_list', $data);
    }

    public function getIpdTable()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $request = $this->request->getPost();
        $payload = $this->ipdModel->getIpdTableData($request);

        return $this->response->setJSON($payload);
    }

    public function cashBalance()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.cash-balance',
        ]);
        if ($permission) {
            return $permission;
        }

        return view('billing/ipd/cash_balance');
    }

    public function cashBalanceReport()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.cash-balance',
        ]);
        if ($permission) {
            return $permission;
        }

        $range = (string) ($this->request->getGet('range') ?? '');
        [$start, $end] = $this->parseDateRange($range);

        $rows = $this->ipdModel->getCashBalanceReport($start, $end);
        $totals = $this->calculateCashBalanceTotals($rows);

        return view('billing/ipd/cash_balance_table', [
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    public function cashBalanceExport()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.export',
        ]);
        if ($permission) {
            return $permission;
        }

        $range = (string) ($this->request->getGet('range') ?? '');
        [$start, $end] = $this->parseDateRange($range);

        $rows = $this->ipdModel->getCashBalanceReport($start, $end);
        $totals = $this->calculateCashBalanceTotals($rows);

        $content = view('billing/ipd/cash_balance_table', [
            'rows' => $rows,
            'totals' => $totals,
        ]);

        ExportExcel($content, 'IPD_Balance_List');

        return $this->response->setBody('');
    }

    public function panel(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $panelData = $this->ipdModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        return view('billing/ipd/panel', $panelData);
    }

    public function legacyAddIpd(int $patientId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $patientId = (int) $patientId;
        if ($patientId <= 0) {
            return redirect()->to(base_url('billing/ipd/current-admission'));
        }

        $activeIpd = $this->db->table('ipd_master')
            ->select('id')
            ->where('p_id', $patientId)
            ->where('ipd_status', 0)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (! empty($activeIpd['id'])) {
            return redirect()->to(base_url('billing/ipd/panel/' . (int) $activeIpd['id']));
        }

        $data = $this->buildLegacyIpdRegistrationData($patientId);
        if (empty($data['person_info'])) {
            return $this->response->setStatusCode(404)->setBody('Patient not found');
        }

        return view('billing/ipd/legacy_registration', $data);
    }

    public function legacyAddNew()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'insertid' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        $patientId = (int) ($this->request->getPost('pid') ?? 0);
        $docList = $this->request->getPost('doc_id');
        $docList = is_array($docList) ? array_values(array_filter(array_map('intval', $docList), static fn ($id) => $id > 0)) : [];

        if ($patientId <= 0 || empty($docList)) {
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => 'Please select at least one doctor.',
            ]);
        }

        $registerDateInput = (string) ($this->request->getPost('res_date') ?? '');
        $registerDate = $registerDateInput !== '' ? str_to_MysqlDate($registerDateInput) : date('Y-m-d');

        $master = [
            'p_id' => $patientId,
            'P_name' => (string) ($this->request->getPost('pname') ?? ''),
            'relation' => (string) ($this->request->getPost('r_relation') ?? ''),
            'contact_person_Name' => (string) ($this->request->getPost('rp_name') ?? ''),
            'insurance_id' => (int) ($this->request->getPost('insur_list') ?? 0),
            'policy_no' => (string) ($this->request->getPost('insur_no') ?? ''),
            'register_date' => $registerDate,
            'problem' => (string) ($this->request->getPost('problem') ?? ''),
            'remark' => (string) ($this->request->getPost('remark') ?? ''),
            'P_mobile1' => (string) ($this->request->getPost('phone1') ?? ''),
            'P_mobile2' => (string) ($this->request->getPost('phone2') ?? ''),
            'case_type' => (int) ($this->request->getPost('optionsRadios_mlc') ?? 0),
            'case_id' => (int) ($this->request->getPost('optionsRadios_org') ?? 0),
            'reg_time' => (string) ($this->request->getPost('res_time') ?? date('H:i')),
            'refer_by' => (int) ($this->request->getPost('refer_by_list') ?? 0),
            'dept_id' => (int) ($this->request->getPost('dept_id') ?? 0),
        ];

        $insertId = $this->ipdEditModel->insertIpd([
            'master' => $master,
            'doc_list' => $docList,
        ]);

        if ($insertId > 0) {
            $roomId = (int) ($this->request->getPost('room_list') ?? 0);
            if ($roomId > 0) {
                $this->ipdEditModel->bedAssign([
                    'ipd_id' => $insertId,
                    'Fdate' => date('Y-m-d H:i:s'),
                    'TDate' => date('Y-m-d H:i:s'),
                    'bed_id' => $roomId,
                ]);
            }

            $caseId = (int) ($master['case_id'] ?? 0);
            if ($caseId > 0 && $this->db->tableExists('organization_case_master')) {
                $this->db->table('organization_case_master')
                    ->where('id', $caseId)
                    ->update(['ipd_id' => $insertId]);
            }
        }

        return $this->response->setJSON([
            'insertid' => (int) $insertId,
        ]);
    }

    private function buildLegacyIpdRegistrationData(int $patientId): array
    {
        $person = $this->db->table('patient_master')
            ->select("*, if(gender=1,'Male','FeMale') as xgender", false)
            ->where('id', $patientId)
            ->get()
            ->getRow();

        if ($person) {
            $person->age = get_age_1(
                $person->dob ?? null,
                $person->age ?? '',
                $person->age_in_month ?? '',
                $person->estimate_dob ?? ''
            );
        }

        $docSpecList = $this->db->query(
            "select d.id, d.p_fname, group_concat(m.SpecName) as SpecName
             from doctor_master d
             join doc_spec s on d.id = s.doc_id
             join med_spec m on s.med_spec_id = m.id
             where d.active = 1
             group by d.id
             order by d.p_fname"
        )->getResult();

        $ipdBedList = [];
        if ($this->db->tableExists('bed_master')) {
            $ipdBedList = $this->db->query(
                "select b.id,
                        concat('Bed No:', coalesce(b.bed_number, b.bed_code), '/', '[', coalesce(w.ward_name, ''), ']') as Bed_Desc
                 from bed_master b
                 left join ward_master w on w.id = b.ward_id
                 where b.bed_status = 'available'
                 order by b.bed_number, b.id"
            )->getResult();
        } elseif ($this->db->tableExists('hc_bed_master')) {
            $ipdBedList = $this->db->query(
                "select id, concat('Bed No:', coalesce(bed_no, bed_number), '/', '[', coalesce(room_name, ward_name), ']') as Bed_Desc
                 from hc_bed_master
                 where ifnull(bed_used_p_id,0)=0"
            )->getResult();
        }

        $caseMaster = [];
        if ($this->db->tableExists('organization_case_master')) {
            $caseMaster = $this->db->table('organization_case_master')
                ->where('status', 0)
                ->where('case_type', 1)
                ->where('ipd_id', 0)
                ->where('p_id', $patientId)
                ->get()
                ->getResult();
        }

        $referMaster = [];
        if ($this->db->tableExists('refer_master')) {
            $referMaster = $this->db->table('refer_master')
                ->where('active', 1)
                ->orderBy('f_name', 'ASC')
                ->get()
                ->getResult();
        }

        $hcDepartment = [];
        if ($this->db->tableExists('hc_department')) {
            $hcDepartment = $this->db->table('hc_department')
                ->orderBy('vName', 'ASC')
                ->get()
                ->getResult();
        }

        return [
            'person_info' => $person,
            'doc_spec_l' => $docSpecList,
            'ipd_bed_list' => $ipdBedList,
            'case_master' => $caseMaster,
            'refer_master' => $referMaster,
            'hc_department' => $hcDepartment,
        ];
    }

    public function panelTab(int $ipdId, string $tab)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $panelData = $this->ipdModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        if ($tab === 'admission') {
            return view('billing/ipd/panel_admission', $this->buildAdmissionTabData($panelData, $ipdId));
        }

        if ($tab === 'bed-assign') {
            $panelData['bed_assignments'] = $this->bedAssignmentModel->getByIpd($ipdId);

            return view('billing/ipd/panel_bed_assign', $panelData);
        }

        if ($tab === 'ipd-charges') {
            $this->syncNursingChargesToInvoice($ipdId);
            $panelData['ipd_charges_grouped'] = $this->ipdModel->getIpdChargesGrouped($ipdId);
            $panelData['ipd_charges_total'] = $this->ipdModel->getIpdChargesTotal($ipdId);
            $panelData['ipd_packages'] = $this->ipdModel->getIpdPackages($ipdId);

            $caseMeta = $this->ipdModel->getIpdCaseMeta($ipdId);
            $insCompId = (int) ($caseMeta['insurance_id'] ?? 0);
            if ($insCompId <= 0) {
                $insCompId = 1;
            }

            $panelData['bedside_items_by_category'] = $this->nursingBedsideItemModel->getBillableGroupedByCategory($insCompId);

            $panelData['ipd_insurance_id'] = $insCompId;
            $panelData['doc_list'] = $this->ipdModel->getIpdDoctorList();
            $doctorIds = array_map(static fn ($doc) => (int) ($doc->id ?? 0), $panelData['doc_list']);
            $doctorIds = array_values(array_filter($doctorIds, static fn ($id) => $id > 0));
            $panelData['doctor_visit_fee_types'] = $this->ipdModel->getDoctorVisitFeeTypes();
            $panelData['doctor_visit_fee_map'] = $this->ipdModel->getDoctorVisitFeeMap($doctorIds);
            $panelData['item_types'] = $this->itemIpdModel->getItemTypes();

            $itemLists = [];
            foreach ($panelData['item_types'] as $type) {
                $typeId = (int) ($type->itype_id ?? 0);
                if ($typeId <= 0) {
                    continue;
                }
                $itemLists[$typeId] = $this->itemIpdModel->getItemsByTypeWithInsurance($typeId, $insCompId);
            }
            $panelData['item_lists'] = $itemLists;

            return view('billing/ipd/panel_ipd_charges', $panelData);
        }

        if ($tab === 'package') {
            $panelData['ipd_packages'] = $this->ipdModel->getIpdPackages($ipdId);
            $caseMeta = $this->ipdModel->getIpdCaseMeta($ipdId);
            $insCompId = (int) ($caseMeta['insurance_id'] ?? 0);
            if ($insCompId <= 0) {
                $insCompId = 0;
            }
            $panelData['ipd_insurance_id'] = $insCompId;
            $panelData['package_list'] = $this->ipdModel->getPackageListForInsurance($insCompId);

            return view('billing/ipd/panel_package', $panelData);
        }

        if ($tab === 'diagnosis-charges') {
            $panelData['diagnosis_charges'] = $this->ipdModel->getDiagnosisCharges($ipdId);

            return view('billing/ipd/panel_diagnosis', $panelData);
        }

        if ($tab === 'payments') {
            $panelData['payments'] = $this->ipdModel->getPayments($ipdId);
            $panelData['medical_bills'] = $this->ipdModel->getMedicalBills($ipdId);

            return view('billing/ipd/panel_payments', $panelData);
        }

        if ($tab === 'medical-bills') {
            $panelData['medical_bills'] = $this->ipdModel->getMedicalBills($ipdId);

            return view('billing/ipd/panel_medical_bills', $panelData);
        }

        if ($tab === 'bill-details') {
            return view('billing/ipd/panel_bill_details', $this->buildBillDetailsTabData($panelData, $ipdId));
        }

        if ($tab === 'discharge-process') {
            $panelData['discharge_info'] = $this->ipdModel->getDischargeInfo($ipdId);
            $user = auth()->user();
            $panelData['can_edit_discharge'] = $user !== null && $user->can('billing.ipd.discharge.edit');

            return view('billing/ipd/panel_discharge', $panelData);
        }

        return view('billing/ipd/panel_placeholder', [
            'title' => ucwords(str_replace('-', ' ', $tab)),
        ]);
    }

    public function updateAdmission(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.ipd.admission.edit',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'message' => 'Invalid request']);
        }

        $panelData = $this->ipdModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setJSON(['update' => 0, 'message' => 'IPD not found']);
        }

        $registerDate = $this->normalizeAdmissionDate((string) ($this->request->getPost('register_date') ?? ''));
        if ($registerDate === null) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Enter a valid admission date']);
        }

        $regTime = $this->normalizeAdmissionTime((string) ($this->request->getPost('reg_time') ?? ''));
        if ($regTime === null) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Enter a valid admission time']);
        }

        $doctorIds = $this->request->getPost('doc_id');
        $doctorIds = is_array($doctorIds)
            ? array_values(array_unique(array_filter(array_map('intval', $doctorIds), static fn ($id) => $id > 0)))
            : [];

        $ipdFields = $this->db->getFieldNames('ipd_master') ?? [];
        $fieldMap = array_flip($ipdFields);

        $primaryDoctorId = $doctorIds[0] ?? 0;
        $primaryDoctorName = $primaryDoctorId > 0 ? $this->ipdModel->getDoctorNameById($primaryDoctorId) : '';

        $masterUpdate = [];
        if (isset($fieldMap['register_date'])) {
            $masterUpdate['register_date'] = $registerDate;
        }
        if (isset($fieldMap['reg_time'])) {
            $masterUpdate['reg_time'] = $regTime;
        }
        if (isset($fieldMap['reg_time_bak'])) {
            $masterUpdate['reg_time_bak'] = $regTime;
        }
        if (isset($fieldMap['case_type'])) {
            $masterUpdate['case_type'] = (int) ($this->request->getPost('case_type') ?? 0) === 1 ? 1 : 0;
        }
        if (isset($fieldMap['dept_id'])) {
            $masterUpdate['dept_id'] = (int) ($this->request->getPost('dept_id') ?? 0);
        }
        if (isset($fieldMap['refer_by'])) {
            $masterUpdate['refer_by'] = (int) ($this->request->getPost('refer_by') ?? 0);
        }
        if (isset($fieldMap['problem'])) {
            $masterUpdate['problem'] = substr((string) ($this->request->getPost('problem') ?? ''), 0, 200);
        }
        if (isset($fieldMap['remark'])) {
            $masterUpdate['remark'] = (string) ($this->request->getPost('remark') ?? '');
        }
        if (isset($fieldMap['contact_person_Name'])) {
            $masterUpdate['contact_person_Name'] = substr((string) ($this->request->getPost('contact_person_Name') ?? ''), 0, 50);
        }
        if (isset($fieldMap['P_mobile1'])) {
            $masterUpdate['P_mobile1'] = substr((string) ($this->request->getPost('P_mobile1') ?? ''), 0, 50);
        }
        if (isset($fieldMap['P_mobile2'])) {
            $masterUpdate['P_mobile2'] = substr((string) ($this->request->getPost('P_mobile2') ?? ''), 0, 50);
        }
        if (isset($fieldMap['relation'])) {
            $masterUpdate['relation'] = substr((string) ($this->request->getPost('relation') ?? ''), 0, 50);
        }
        if (isset($fieldMap['r_doc_id'])) {
            $masterUpdate['r_doc_id'] = $primaryDoctorId;
        }
        if (isset($fieldMap['r_doc_name'])) {
            $masterUpdate['r_doc_name'] = $primaryDoctorName;
        }

        $this->db->transStart();
        if (! empty($masterUpdate)) {
            $this->ipdEditModel->updateIpd($masterUpdate, $ipdId);
        }
        $this->ipdEditModel->replaceIpdDoctors($ipdId, $doctorIds);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['update' => 0, 'message' => 'Unable to update admission details']);
        }

        $freshPanelData = $this->ipdModel->getIpdPanelInfo($ipdId);
        $admissionData = $this->buildAdmissionTabData($freshPanelData, $ipdId);

        return $this->response->setJSON([
            'update' => 1,
            'message' => 'Admission details updated.',
            'html' => view('billing/ipd/panel_admission', $admissionData),
            'header' => $this->buildAdmissionHeaderPayload($freshPanelData),
        ]);
    }

    public function updateDischargeDiscount(int $ipdId, int $slot)
    {
        $permission = $this->requireAnyPermission([
            'billing.ipd.discharge.edit',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'message' => 'Invalid request']);
        }

        if (! in_array($slot, [1, 2, 3], true)) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Invalid discount slot']);
        }

        $fieldMap = $this->getIpdFieldMap();
        $amount = max(0, (float) ($this->request->getPost('amount') ?? 0));
        $remark = trim((string) ($this->request->getPost('remark') ?? ''));

        $amountKey = $slot === 1 ? 'Discount' : 'Discount' . $slot;
        $remarkKey = $slot === 1 ? 'Discount_Remark' : 'Discount_Remark' . $slot;

        $update = [];
        if (isset($fieldMap[$amountKey])) {
            $update[$amountKey] = $amount;
        }
        if (isset($fieldMap[$remarkKey])) {
            $update[$remarkKey] = substr($remark, 0, 200);
        }

        if (empty($update)) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Discount fields are not available in schema']);
        }

        $this->ipdEditModel->updateIpd($update, $ipdId);
        $this->ipdEditModel->calculateIPD($ipdId);

        return $this->buildDischargeProcessResponse($ipdId, 'Discount updated.');
    }

    public function updateDischargeCharge(int $ipdId, int $slot)
    {
        $permission = $this->requireAnyPermission([
            'billing.ipd.discharge.edit',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'message' => 'Invalid request']);
        }

        if (! in_array($slot, [1, 2], true)) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Invalid additional charge slot']);
        }

        $fieldMap = $this->getIpdFieldMap();
        $amount = max(0, (float) ($this->request->getPost('amount') ?? 0));
        $remark = trim((string) ($this->request->getPost('remark') ?? ''));

        $remarkKey = 'charge' . $slot;
        $amountKey = 'chargeamount' . $slot;
        $byKey = 'charge_by' . $slot;

        $update = [];
        if (isset($fieldMap[$remarkKey])) {
            $update[$remarkKey] = substr($remark, 0, 200);
        }
        if (isset($fieldMap[$amountKey])) {
            $update[$amountKey] = $amount;
        }
        if (isset($fieldMap[$byKey])) {
            $user = auth()->user();
            $update[$byKey] = (string) ($user->username ?? $user->email ?? 'system');
        }

        if (empty($update)) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Charge fields are not available in schema']);
        }

        $this->ipdEditModel->updateIpd($update, $ipdId);
        $this->ipdEditModel->calculateIPD($ipdId);

        return $this->buildDischargeProcessResponse($ipdId, 'Additional charge updated.');
    }

    public function updateDischargeProcess(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.ipd.discharge.edit',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'message' => 'Invalid request']);
        }

        $fieldMap = $this->getIpdFieldMap();

        $dischargeDate = $this->normalizeAdmissionDate((string) ($this->request->getPost('discharge_date') ?? ''));
        if ($dischargeDate === null) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Enter a valid discharge date']);
        }

        $dischargeTime = $this->normalizeAdmissionTime((string) ($this->request->getPost('discharge_time') ?? ''));
        if ($dischargeTime === null) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Enter a valid discharge time']);
        }

        $status = (int) ($this->request->getPost('discharge_status') ?? 0);
        if ($status < 1 || $status > 7) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Select valid discharge status']);
        }

        $update = [];
        if (isset($fieldMap['discarge_patient_status'])) {
            $update['discarge_patient_status'] = $status;
        }
        if (isset($fieldMap['discharge_date'])) {
            $update['discharge_date'] = $dischargeDate;
        }
        if (isset($fieldMap['discharge_time'])) {
            $update['discharge_time'] = $dischargeTime;
        }
        if (isset($fieldMap['discharge_time_bak'])) {
            $update['discharge_time_bak'] = $dischargeTime;
        }
        if (isset($fieldMap['discharge_remark'])) {
            $update['discharge_remark'] = (string) ($this->request->getPost('discharge_remark') ?? '');
        }
        if (isset($fieldMap['discharge_balance_user'])) {
            $update['discharge_balance_user'] = substr((string) ($this->request->getPost('balance_user') ?? ''), 0, 200);
        }
        if (isset($fieldMap['discharge_balance_remark'])) {
            $update['discharge_balance_remark'] = substr((string) ($this->request->getPost('balance_remark') ?? ''), 0, 200);
        }
        if (isset($fieldMap['discharge_by'])) {
            $user = auth()->user();
            $update['discharge_by'] = (string) ($user->username ?? $user->email ?? 'system');
        }
        if (isset($fieldMap['ipd_status'])) {
            $update['ipd_status'] = 1;
        }

        if (empty($update)) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'message' => 'Discharge fields are not available in schema']);
        }

        $this->ipdEditModel->updateIpd($update, $ipdId);
        $this->ipdEditModel->calculateIPD($ipdId);

        return $this->buildDischargeProcessResponse($ipdId, 'Discharge status updated.');
    }

    public function billPrint(int $ipdId, int $mode = 1)
    {
        $permission = $this->requireAnyPermission([
            'billing.ipd.bill.print',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $panelData = $this->ipdModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $mode = max(1, min(6, $mode));
        $data = $this->buildBillDetailsTabData($panelData, $ipdId);
        $data['show_print_actions'] = false;
        $data['show_payment_details'] = in_array($mode, [1, 6], true);
        $data['letterhead_mode'] = in_array($mode, [5, 6], true);
        $data['print_mode'] = $mode;

        $html = view('billing/ipd/bill_print', $data);

        $mpdfTempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
        if (! is_dir($mpdfTempDir)) {
            mkdir($mpdfTempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $mpdfTempDir,
            'format' => 'A4',
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_left' => 8,
            'margin_right' => 8,
        ]);

        $mpdf->WriteHTML($html);

        $ipdCode = (string) ($data['ipd_info']->ipd_code ?? ('IPD-' . $ipdId));
        $fileName = 'IPD_Bill_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $ipdCode) . '_M' . $mode . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($mpdf->Output($fileName, 'S'));
    }

    public function addIpdCharge(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $itemType = (int) ($this->request->getPost('item_type') ?? 0);
        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $rate = (float) ($this->request->getPost('item_rate') ?? 0);
        $qty = (float) ($this->request->getPost('item_qty') ?? 1);
        $itemDate = (string) ($this->request->getPost('item_date') ?? date('Y-m-d'));
        $comment = (string) ($this->request->getPost('comment') ?? '');
        $docId = (int) ($this->request->getPost('doc_id') ?? 0);
        $referName = (string) ($this->request->getPost('refer_name') ?? '');

        if ($itemType <= 0 || $itemId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid item selection']);
        }

        $itemRow = $this->itemIpdModel->getItemById($itemId);
        if (empty($itemRow)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Item not found']);
        }

        $item = $itemRow[0];
        if ($rate <= 0) {
            $rate = (float) ($item->amount ?? 0);
        }

        if ($qty <= 0) {
            $qty = 1;
        }

        $caseMeta = $this->ipdModel->getIpdCaseMeta($ipdId);
        $insId = (int) ($caseMeta['insurance_id'] ?? 0);
        $orgCode = (string) ($caseMeta['org_code'] ?? '');

        $docName = '';
        if ($docId > 0) {
            $docName = $this->ipdModel->getDoctorNameById($docId);
        } else {
            $docName = $referName;
        }

        $amount = $rate * $qty;

        $insertData = [
            'ipd_id' => $ipdId,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'item_name' => (string) ($item->idesc ?? ''),
            'item_rate' => $rate,
            'item_added_date' => date('Y-m-d H:i:s'),
            'item_qty' => $qty,
            'item_amount' => $amount,
            'doc_id' => $docId,
            'doc_name' => $docName,
            'date_item' => $itemDate,
            'comment' => $comment,
            'ins_id' => $insId,
            'org_code' => $orgCode,
        ];

        $insertId = $this->ipdEditModel->insertIpdItem($insertData);

        return $this->response->setJSON([
            'ok' => $insertId > 0,
            'id' => $insertId,
        ]);
    }

    public function updateIpdCharge(int $itemId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $qty = (float) ($this->request->getPost('item_qty') ?? 1);
        $rate = (float) ($this->request->getPost('item_rate') ?? 0);
        if ($qty <= 0) {
            $qty = 1;
        }

        $amount = $rate * $qty;

        $this->ipdEditModel->updateIpdItem([
            'item_qty' => $qty,
            'item_amount' => $amount,
        ], $itemId);

        return $this->response->setJSON(['ok' => true]);
    }

    public function addBedsideCharge(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $bedsideItemId = (int) ($this->request->getPost('bedside_item_id') ?? 0);
        $qty = (float) ($this->request->getPost('item_qty') ?? 1);
        $itemDate = (string) ($this->request->getPost('item_date') ?? date('Y-m-d'));
        $visitTime = (string) ($this->request->getPost('visit_time') ?? '');
        $comment = (string) ($this->request->getPost('comment') ?? '');
        $docId = (int) ($this->request->getPost('doc_id') ?? 0);
        $referName = (string) ($this->request->getPost('refer_name') ?? '');

        if ($bedsideItemId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Select a bedside item']);
        }

        $caseMeta = $this->ipdModel->getIpdCaseMeta($ipdId);
        $insId = (int) ($caseMeta['insurance_id'] ?? 0);

        $item = $this->nursingBedsideItemModel->getBillableItemForInsurance($bedsideItemId, $insId);
        if (! is_array($item) || empty($item)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Bedside item not found or not billable']);
        }

        if ($qty <= 0) {
            $qty = 1;
        }

        $rateInput = $this->request->getPost('item_rate');
        $insuranceRate = $item['insurance_rate'] ?? null;
        $rate = ($rateInput !== null && $rateInput !== '')
            ? (float) $rateInput
            : ($insuranceRate !== null ? (float) $insuranceRate : (float) ($item['default_rate'] ?? 0));
        if ($rate < 0) {
            $rate = 0;
        }

        $orgCode = trim((string) ($item['insurance_code'] ?? ''));

        $docName = '';
        if ($docId > 0) {
            $docName = $this->ipdModel->getDoctorNameById($docId);
        } else {
            $docName = $referName;
        }

        $typeId = $this->resolveBedsideItemTypeId((string) ($item['category'] ?? 'Bedside'));
        $amount = $rate * $qty;

        $nursingChargeId = $this->insertNursingIpdChargeRow([
            'ipd_id' => $ipdId,
            'item_id' => $bedsideItemId,
            'item_name' => (string) ($item['item_name'] ?? ''),
            'item_type' => (string) ($item['item_type'] ?? 'Bedside'),
            'doctor_id' => $docId > 0 ? $docId : null,
            'doctor_name' => $docName !== '' ? $docName : null,
            'visit_date' => $itemDate,
            'visit_time' => $visitTime !== '' ? $visitTime : null,
            'consultation_type' => null,
            'duration' => null,
            'specialization' => null,
            'hospital_clinic' => null,
            'is_outside_doctor' => 0,
            'rate' => $rate,
            'qty' => (int) $qty,
            'amount' => $amount,
            'performed_by' => null,
            'performed_date' => $itemDate,
            'performed_time' => $visitTime !== '' ? $visitTime : null,
            'remarks' => $comment,
            'include_in_bill' => 1,
            'updated_by' => null,
        ]);

        $invoiceComment = $comment;
        if ($nursingChargeId > 0) {
            $marker = '[NURSING_CHARGE_ID:' . $nursingChargeId . ']';
            $invoiceComment = trim($invoiceComment . ' ' . $marker);
        }

        $insertData = [
            'ipd_id' => $ipdId,
            'item_type' => $typeId,
            'item_id' => $bedsideItemId,
            'item_name' => (string) ($item['item_name'] ?? ''),
            'item_rate' => $rate,
            'item_added_date' => date('Y-m-d H:i:s'),
            'item_qty' => $qty,
            'item_amount' => $amount,
            'doc_id' => $docId,
            'doc_name' => $docName,
            'date_item' => $itemDate,
            'comment' => $invoiceComment,
            'ins_id' => $insId,
            'org_code' => $orgCode,
        ];

        $insertId = $this->ipdEditModel->insertIpdItem($insertData);

        return $this->response->setJSON([
            'ok' => $insertId > 0,
            'id' => $insertId,
        ]);
    }

    public function addDoctorVisitCharge(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $docId = (int) ($this->request->getPost('doc_id') ?? 0);
        $feeTypeId = (int) ($this->request->getPost('fee_type_id') ?? 0);
        $qty = (float) ($this->request->getPost('item_qty') ?? 1);
        $itemDate = (string) ($this->request->getPost('item_date') ?? date('Y-m-d'));
        $visitTime = (string) ($this->request->getPost('visit_time') ?? '');
        $duration = (int) ($this->request->getPost('duration') ?? 0);
        $hospitalClinic = trim((string) ($this->request->getPost('hospital_clinic') ?? ''));
        $isOutsideDoctor = (int) ($this->request->getPost('is_outside_doctor') ?? 0) === 1 ? 1 : 0;
        $comment = trim((string) ($this->request->getPost('comment') ?? ''));

        if ($docId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Select doctor']);
        }

        if ($qty <= 0) {
            $qty = 1;
        }

        $feeDetail = $this->ipdModel->getDoctorVisitFeeDetail($docId, $feeTypeId);

        $rateInput = $this->request->getPost('item_rate');
        $rate = ($rateInput !== null && $rateInput !== '')
            ? (float) $rateInput
            : (float) ($feeDetail['amount'] ?? 0);
        if ($rate < 0) {
            $rate = 0;
        }

        $docName = $this->ipdModel->getDoctorNameById($docId);
        if ($docName === '') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Doctor not found']);
        }

        $visitLabel = trim((string) ($feeDetail['fee_desc'] ?? 'Doctor Visit'));
        if ($visitLabel === '') {
            $visitLabel = 'Doctor Visit';
        }

        $itemName = 'Doctor Visit - ' . $visitLabel;
        $typeId = $this->resolveDoctorVisitItemTypeId();
        $amount = $rate * $qty;

        $caseMeta = $this->ipdModel->getIpdCaseMeta($ipdId);
        $insId = (int) ($caseMeta['insurance_id'] ?? 0);

        $nursingChargeId = $this->insertNursingIpdChargeRow([
            'ipd_id' => $ipdId,
            'item_id' => (int) ($feeDetail['id'] ?? $docId),
            'item_name' => $itemName,
            'item_type' => 'Doctor Visit',
            'doctor_id' => $docId,
            'doctor_name' => $docName,
            'visit_date' => $itemDate,
            'visit_time' => $visitTime !== '' ? $visitTime : null,
            'consultation_type' => (string) ($feeDetail['fee_type'] ?? ''),
            'duration' => $duration > 0 ? $duration : null,
            'specialization' => null,
            'hospital_clinic' => $hospitalClinic !== '' ? $hospitalClinic : null,
            'is_outside_doctor' => $isOutsideDoctor,
            'rate' => $rate,
            'qty' => (int) $qty,
            'amount' => $amount,
            'performed_by' => null,
            'performed_date' => $itemDate,
            'performed_time' => $visitTime !== '' ? $visitTime : null,
            'remarks' => $comment,
            'include_in_bill' => 1,
            'updated_by' => null,
        ]);

        $invoiceComment = $comment;
        if ($nursingChargeId > 0) {
            $marker = '[NURSING_CHARGE_ID:' . $nursingChargeId . ']';
            $invoiceComment = trim($invoiceComment . ' ' . $marker);
        }

        $insertData = [
            'ipd_id' => $ipdId,
            'item_type' => $typeId,
            'item_id' => (int) ($feeDetail['id'] ?? $docId),
            'item_name' => $itemName,
            'item_rate' => $rate,
            'item_added_date' => date('Y-m-d H:i:s'),
            'item_qty' => $qty,
            'item_amount' => $amount,
            'doc_id' => $docId,
            'doc_name' => $docName,
            'date_item' => $itemDate,
            'comment' => $invoiceComment,
            'ins_id' => $insId,
            'org_code' => '',
        ];

        $insertId = $this->ipdEditModel->insertIpdItem($insertData);

        return $this->response->setJSON([
            'ok' => $insertId > 0,
            'id' => $insertId,
        ]);
    }

    public function deleteIpdCharge(int $itemId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $result = $this->ipdEditModel->deleteIpdInvoiceItem($itemId);
        if ($result !== 1) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'Unable to remove charge item. Please refresh and try again.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'deleted_id' => $itemId,
        ]);
    }

    public function updateNursingCharge(int $ipdId, int $chargeId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        if ($chargeId <= 0 || $ipdId <= 0 || ! $this->db->tableExists('nursing_ipd_charges')) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid charge reference']);
        }

        $row = $this->db->table('nursing_ipd_charges')
            ->where('charge_id', $chargeId)
            ->where('ipd_id', $ipdId)
            ->get()
            ->getRowArray();

        if (! is_array($row) || empty($row)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Charge history row not found']);
        }

        $rate = (float) ($this->request->getPost('rate') ?? ($row['rate'] ?? 0));
        $qty = (float) ($this->request->getPost('qty') ?? ($row['qty'] ?? 1));
        $comment = trim((string) ($this->request->getPost('comment') ?? ($row['remarks'] ?? '')));
        $visitDate = (string) ($this->request->getPost('visit_date') ?? ($row['visit_date'] ?? date('Y-m-d')));
        $visitTime = (string) ($this->request->getPost('visit_time') ?? ($row['visit_time'] ?? ''));

        if ($qty <= 0) {
            $qty = 1;
        }
        if ($rate < 0) {
            $rate = 0;
        }

        $amount = $rate * $qty;
        $user = auth()->user();
        $updatedBy = ($user && isset($user->username))
            ? (string) $user->username
            : (($user && isset($user->email)) ? (string) $user->email : 'system');

        $this->db->table('nursing_ipd_charges')
            ->where('charge_id', $chargeId)
            ->where('ipd_id', $ipdId)
            ->update([
                'rate' => $rate,
                'qty' => $qty,
                'amount' => $amount,
                'remarks' => $comment,
                'visit_date' => $visitDate,
                'visit_time' => $visitTime,
                'updated_by' => $updatedBy . ' [' . date('Y-m-d H:i:s') . ']',
            ]);

        $marker = '[NURSING_CHARGE_ID:' . $chargeId . ']';
        $invoiceRows = $this->db->table('ipd_invoice_item')
            ->select('id')
            ->where('ipd_id', $ipdId)
            ->like('comment', $marker)
            ->get()
            ->getResultArray();

        $invoiceComment = trim($comment . ' ' . $marker);
        foreach ($invoiceRows as $invoiceRow) {
            $invoiceId = (int) ($invoiceRow['id'] ?? 0);
            if ($invoiceId <= 0) {
                continue;
            }
            $this->ipdEditModel->updateIpdItem([
                'item_rate' => $rate,
                'item_qty' => $qty,
                'item_amount' => $amount,
                'date_item' => $visitDate,
                'comment' => $invoiceComment,
            ], $invoiceId);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function deleteNursingCharge(int $ipdId, int $chargeId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        if ($chargeId <= 0 || $ipdId <= 0 || ! $this->db->tableExists('nursing_ipd_charges')) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid charge reference']);
        }

        $row = $this->db->table('nursing_ipd_charges')
            ->where('charge_id', $chargeId)
            ->where('ipd_id', $ipdId)
            ->get()
            ->getRowArray();

        if (! is_array($row) || empty($row)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Charge history row not found']);
        }

        $user = auth()->user();
        $updatedBy = ($user && isset($user->username))
            ? (string) $user->username
            : (($user && isset($user->email)) ? (string) $user->email : 'system');

        $this->db->table('nursing_ipd_charges')
            ->where('charge_id', $chargeId)
            ->where('ipd_id', $ipdId)
            ->update([
                'include_in_bill' => 0,
                'updated_by' => $updatedBy . ' [' . date('Y-m-d H:i:s') . ']',
            ]);

        $marker = '[NURSING_CHARGE_ID:' . $chargeId . ']';
        $invoiceRows = $this->db->table('ipd_invoice_item')
            ->select('id')
            ->where('ipd_id', $ipdId)
            ->like('comment', $marker)
            ->get()
            ->getResultArray();

        foreach ($invoiceRows as $invoiceRow) {
            $invoiceId = (int) ($invoiceRow['id'] ?? 0);
            if ($invoiceId <= 0) {
                continue;
            }
            $this->ipdEditModel->deleteIpdInvoiceItem($invoiceId);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function updateIpdChargePackage(int $itemId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $packageId = (int) ($this->request->getPost('package_id') ?? 0);

        $this->ipdEditModel->updateIpdItem([
            'package_id' => $packageId,
        ], $itemId);

        return $this->response->setJSON(['ok' => true]);
    }

    public function addIpdPackage(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $packageId = (int) ($this->request->getPost('package_id') ?? 0);
        $packageName = (string) ($this->request->getPost('package_name') ?? '');
        $comment = (string) ($this->request->getPost('comment') ?? '');
        $inputAmount = (float) ($this->request->getPost('input_amount') ?? 0);

        $caseMeta = $this->ipdModel->getIpdCaseMeta($ipdId);
        $insCompId = (int) ($caseMeta['insurance_id'] ?? 0);
        if ($insCompId <= 0) {
            $insCompId = 0;
        }

        $packageDesc = $comment;
        $amount = $inputAmount;
        $orgCode = '';

        if ($packageId > 1) {
            $package = $this->ipdModel->getPackageWithInsurance($packageId, $insCompId);
            if ($package) {
                $packageName = (string) ($package->ipd_pakage_name ?? $packageName);
                $packageDesc = (string) ($package->Pakage_description ?? $packageDesc);
                $amount = (float) ($package->amount1 ?? $amount);
                $orgCode = (string) ($package->code ?? '');
            }
        }

        if ($inputAmount > 0) {
            $amount = $inputAmount;
        }

        $userId = 0;
        $userName = 'system';
        if (function_exists('auth')) {
            $user = auth()->user();
            if ($user) {
                $userId = (int) ($user->id ?? 0);
                $userName = (string) ($user->username ?? $user->email ?? 'user');
            }
        }
        $updateBy = $userName . '[' . $userId . '] D:' . date('d-m-Y h:i:s');

        $insertData = [
            'ipd_id' => $ipdId,
            'package_id' => $packageId,
            'package_name' => $packageName,
            'package_desc' => $packageDesc,
            'package_Amount' => $amount,
            'comment' => $comment,
            'update_by' => $updateBy,
            'ins_id' => $insCompId,
            'org_code' => $orgCode,
        ];

        $insertId = $this->ipdEditModel->insertPackage($insertData);

        return $this->response->setJSON([
            'ok' => $insertId > 0,
            'id' => $insertId,
        ]);
    }

    public function updateIpdPackage(int $packageItemId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $amount = (float) ($this->request->getPost('input_amount') ?? 0);

        $this->ipdEditModel->updatePackage([
            'package_Amount' => $amount,
        ], $packageItemId);

        return $this->response->setJSON(['ok' => true]);
    }

    public function deleteIpdPackage(int $packageItemId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $result = $this->ipdEditModel->deletePackage($packageItemId);

        return $this->response->setJSON(['ok' => $result === 1]);
    }

    public function loadPaymentModal(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $panelData = $this->ipdModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $panelData['bank_data'] = $this->ipdModel->getBankPaymentSources();

        return view('billing/ipd/ipd_model_payment_box', $panelData);
    }

    public function loadDeductionModal(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        return view('billing/ipd/ipd_panel_deduction_payment', [
            'ipd_id' => $ipdId,
        ]);
    }

    public function loadTpaPaymentModal(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $panelData = $this->ipdModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $panelData['ipd_id'] = $ipdId;

        return view('billing/ipd/ipd_panel_tpa_payment', $panelData);
    }

    public function confirmPayment()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $ipdId = (int) ($this->request->getPost('ipd_id') ?? 0);
        $amount = (float) ($this->request->getPost('amount') ?? 0);
        $mode = (int) ($this->request->getPost('mode') ?? 0);
        $paymentDate = (string) ($this->request->getPost('date_payment') ?? '');

        if ($ipdId <= 0 || $amount <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Amount should be greater than zero.']);
        }

        $ipdRow = $this->db->table('ipd_master')
            ->select('id, ipd_code')
            ->where('id', $ipdId)
            ->get()
            ->getRow();

        if (! $ipdRow) {
            return $this->response->setStatusCode(404)->setJSON(['update' => 0, 'error_text' => 'IPD not found']);
        }

        $userId = 0;
        $userName = 'system';
        if (function_exists('auth')) {
            $user = auth()->user();
            if ($user) {
                $userId = (int) ($user->id ?? 0);
                $userName = (string) ($user->username ?? $user->email ?? 'user');
            }
        }

        $payDate = $paymentDate !== '' ? $paymentDate : date('Y-m-d');
        $payDatetime = $payDate . ' ' . date('H:i:s');
        $remark = (string) ($this->request->getPost('cash_remark') ?? '');

        $paymentModel = new PaymentModel();

        if ($mode === 1) {
            $insertId = $paymentModel->insertPayment([
                'payment_mode' => 1,
                'payof_type' => 4,
                'payof_id' => $ipdId,
                'payof_code' => $ipdRow->ipd_code ?? '',
                'credit_debit' => 0,
                'amount' => $amount,
                'payment_date' => $payDatetime,
                'remark' => $remark,
                'update_by' => $userName . '[' . $userId . ']',
                'update_by_id' => $userId,
            ]);

            $this->ipdEditModel->calculateIPD($ipdId);

            return $this->response->setJSON([
                'update' => 1,
                'ipd_id' => $ipdId,
                'payid' => $insertId,
                'pay_date' => $payDatetime,
            ]);
        }

        if ($mode === 2) {
            $cardTran = (string) ($this->request->getPost('input_card_tran') ?? '');
            if ($cardTran === '') {
                return $this->response->setJSON(['update' => 0, 'error_text' => 'Card transaction ID is required.']);
            }

            $insertId = $paymentModel->insertPayment([
                'payment_mode' => 2,
                'payof_type' => 4,
                'payof_id' => $ipdId,
                'payof_code' => $ipdRow->ipd_code ?? '',
                'credit_debit' => 0,
                'amount' => $amount,
                'payment_date' => $payDatetime,
                'remark' => $remark,
                'update_by' => $userName . '[' . $userId . ']',
                'pay_bank_id' => (int) ($this->request->getPost('cbo_pay_type') ?? 0),
                'card_tran_id' => $cardTran,
                'update_by_id' => $userId,
            ]);

            $this->ipdEditModel->calculateIPD($ipdId);

            return $this->response->setJSON([
                'update' => 1,
                'ipd_id' => $ipdId,
                'payid' => $insertId,
            ]);
        }

        return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid payment mode.']);
    }

    public function deductPayment()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $ipdId = (int) ($this->request->getPost('ipd_id') ?? 0);
        $amount = (float) ($this->request->getPost('amount') ?? 0);
        $mode = (int) ($this->request->getPost('mode') ?? 0);

        if ($ipdId <= 0 || $amount <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Amount should be greater than zero.']);
        }

        $ipdRow = $this->db->table('ipd_master')
            ->select('id, ipd_code')
            ->where('id', $ipdId)
            ->get()
            ->getRow();

        if (! $ipdRow) {
            return $this->response->setStatusCode(404)->setJSON(['update' => 0, 'error_text' => 'IPD not found']);
        }

        $userId = 0;
        $userName = 'system';
        if (function_exists('auth')) {
            $user = auth()->user();
            if ($user) {
                $userId = (int) ($user->id ?? 0);
                $userName = (string) ($user->username ?? $user->email ?? 'user');
            }
        }

        $remark = (string) ($this->request->getPost('cash_remark') ?? '');
        $paymentModel = new PaymentModel();

        if ($mode === 3 || $mode === 4) {
            $insertId = $paymentModel->insertPayment([
                'payment_mode' => $mode === 3 ? 1 : 2,
                'payof_type' => 4,
                'payof_id' => $ipdId,
                'payof_code' => $ipdRow->ipd_code ?? '',
                'credit_debit' => 1,
                'amount' => $amount,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $remark,
                'update_by' => $userName . '[' . $userId . ']',
                'update_by_id' => $userId,
            ]);

            $this->ipdEditModel->calculateIPD($ipdId);

            return $this->response->setJSON([
                'update' => 1,
                'ipd_id' => $ipdId,
                'payid' => $insertId,
            ]);
        }

        return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid payment mode.']);
    }

    public function tpaPayment()
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'show_text' => 'Invalid request']);
        }

        $ipdId = (int) ($this->request->getPost('hid_ipd_id') ?? 0);
        if ($ipdId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'show_text' => 'Invalid IPD']);
        }

        $payData = [
            'payable_by_tpa' => (float) ($this->request->getPost('input_payable_by_tpa') ?? 0),
            'discount_for_tpa' => (float) ($this->request->getPost('input_discount_for_tpa') ?? 0),
            'discount_by_hospital' => (float) ($this->request->getPost('input_discount_by_hospital') ?? 0),
            'discount_by_hospital_2' => (float) ($this->request->getPost('input_discount_by_hospital_2') ?? 0),
            'discount_by_hospital_remark' => (string) ($this->request->getPost('input_discount_by_hospital_remark') ?? ''),
            'discount_by_hospital_2_remark' => (string) ($this->request->getPost('input_discount_by_hospital_2_remark') ?? ''),
        ];

        $this->ipdEditModel->updateIpd($payData, $ipdId);
        $this->ipdEditModel->calculateIPD($ipdId);

        return $this->response->setJSON([
            'update' => 1,
            'ipd_id' => $ipdId,
        ]);
    }

    public function ipdCashPrintPdf(int $ipdId, int $payId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $payment = $this->db->table('payment_history')
            ->select("*,date_format(payment_date,'%d-%m-%Y %h:%i %p') as payment_date_str,if(credit_debit=0,'','Return') as Amount_str", false)
            ->where('payof_type', 4)
            ->where('payof_id', $ipdId)
            ->where('id', $payId)
            ->get()
            ->getResult();

        $ipdMaster = $this->db->table('ipd_master')
            ->where('id', $ipdId)
            ->get()
            ->getResult();

        if (empty($ipdMaster)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $patient = $this->db->table('patient_master')
            ->select("*,if(gender=1,'Male','Female') as xgender", false)
            ->where('id', $ipdMaster[0]->p_id)
            ->get()
            ->getResult();

        return view('billing/ipd/ipd_payment_receipt_print', [
            'ipd_payment' => $payment,
            'ipd_master' => $ipdMaster,
            'patient_master' => $patient,
            'ipd_id' => $ipdId,
            'payid' => $payId,
        ]);
    }

    private function parseDateRange(string $range): array
    {
        $start = date('Y-m-d');
        $end = $start;

        if ($range !== '') {
            $parts = explode('S', $range);
            if (count($parts) === 2) {
                $start = $parts[0];
                $end = $parts[1];
            }
        }

        return [$start, $end];
    }

    private function calculateCashBalanceTotals(array $rows): array
    {
        $totalPaid = 0.0;
        $totalBalance = 0.0;

        foreach ($rows as $row) {
            $totalPaid += (float) ($row->sum_of_paid ?? 0);
            $totalBalance += (float) ($row->balance_amount ?? 0);
        }

        return [
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
        ];
    }

    private function buildBillDetailsTabData(array $panelData, int $ipdId): array
    {
        $panelData['ipd_packages'] = $this->ipdModel->getIpdPackages($ipdId);
        $panelData['ipd_invoice_items'] = $this->ipdModel->getIpdInvoiceItems(
            $ipdId,
            ! empty($panelData['ipd_packages'])
        );
        $panelData['showinvoice'] = $this->ipdModel->getIpdInvoiceCharges($ipdId);
        $panelData['inv_med_list'] = $this->ipdModel->getIpdMedicalCredits($ipdId);
        $panelData['ipd_payment'] = $this->ipdModel->getPayments($ipdId);
        $panelData['insurance'] = $this->ipdModel->getIpdInsurance($ipdId);

        $grossTotal = 0.0;
        foreach ($panelData['ipd_packages'] as $row) {
            $grossTotal += (float) ($row->package_Amount ?? 0);
        }
        foreach ($panelData['ipd_invoice_items'] as $row) {
            $grossTotal += (float) ($row->item_amount ?? 0);
        }
        foreach ($panelData['showinvoice'] as $row) {
            $grossTotal += (float) ($row->amount ?? 0);
        }
        foreach ($panelData['inv_med_list'] as $row) {
            $grossTotal += (float) ($row->net_amount ?? 0);
        }

        $ipd = $panelData['ipd_info'] ?? null;
        $discountTotal = (float) ($ipd->Discount ?? 0) + (float) ($ipd->Discount2 ?? 0) + (float) ($ipd->Discount3 ?? 0);
        $extraCharge = (float) ($ipd->chargeamount1 ?? 0) + (float) ($ipd->chargeamount2 ?? 0);
        $netTotal = ($grossTotal + $extraCharge) - $discountTotal;

        $panelData['bill_totals'] = [
            'gross' => $grossTotal,
            'net' => $netTotal,
        ];

        $totalPaid = 0.0;
        foreach ($panelData['ipd_payment'] as $row) {
            $amount = (float) ($row->amount ?? 0);
            $creditDebit = (int) ($row->credit_debit ?? 0);
            $totalPaid += $creditDebit === 0 ? $amount : $amount * -1;
        }
        $panelData['bill_totals']['paid'] = $totalPaid;
        $panelData['bill_totals']['balance'] = $netTotal - $totalPaid;

        $user = auth()->user();
        $panelData['can_print_bill'] = $user !== null && $user->can('billing.ipd.bill.print');
        $panelData['show_print_actions'] = true;
        $panelData['show_payment_details'] = true;

        return $panelData;
    }

    private function buildDischargeProcessResponse(int $ipdId, string $message)
    {
        $panelData = $this->ipdModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setJSON(['update' => 0, 'message' => 'IPD not found']);
        }

        $panelData['discharge_info'] = $this->ipdModel->getDischargeInfo($ipdId);
        $user = auth()->user();
        $panelData['can_edit_discharge'] = $user !== null && $user->can('billing.ipd.discharge.edit');

        return $this->response->setJSON([
            'update' => 1,
            'message' => $message,
            'html' => view('billing/ipd/panel_discharge', $panelData),
        ]);
    }

    private function getIpdFieldMap(): array
    {
        $ipdFields = $this->db->getFieldNames('ipd_master') ?? [];

        return array_flip($ipdFields);
    }

    private function buildAdmissionTabData(array $panelData, int $ipdId): array
    {
        $ipd = $panelData['ipd_info'] ?? null;
        $panelData['admission_doctors'] = $this->getAdmissionDoctors($ipdId);
        $panelData['selected_doctor_ids'] = array_values(array_map(
            static fn ($doctor) => (int) ($doctor->id ?? 0),
            $panelData['admission_doctors']
        ));
        $panelData['available_doctors'] = $this->ipdModel->getIpdDoctorList();
        $panelData['departments'] = $this->getAdmissionDepartments();
        $panelData['refer_master'] = $this->getAdmissionReferMasters();
        $panelData['register_date_input'] = $this->formatHtmlDateInput((string) ($ipd->register_date ?? ''));
        $panelData['reg_time_input'] = $this->formatHtmlTimeInput((string) ($ipd->reg_time ?? ''));
        $user = auth()->user();
        $panelData['can_edit_admission'] = $user !== null && $user->can('billing.ipd.admission.edit');

        return $panelData;
    }

    private function getAdmissionDoctors(int $ipdId): array
    {
        if (! $this->db->tableExists('ipd_master_doc_list') || ! $this->db->tableExists('doctor_master')) {
            return [];
        }

        return $this->db->table('ipd_master_doc_list l')
            ->select("d.id, concat_ws(' ', 'Dr.', d.p_fname, d.p_mname, d.p_lname) as doctor_name", false)
            ->join('doctor_master d', 'd.id = l.doc_id', 'inner')
            ->where('l.ipd_id', $ipdId)
            ->groupBy('d.id, d.p_fname, d.p_mname, d.p_lname')
            ->orderBy('d.p_fname', 'ASC')
            ->get()
            ->getResult();
    }

    private function getAdmissionDepartments(): array
    {
        if (! $this->db->tableExists('hc_department')) {
            return [];
        }

        return $this->db->table('hc_department')
            ->orderBy('vName', 'ASC')
            ->get()
            ->getResult();
    }

    private function getAdmissionReferMasters(): array
    {
        if (! $this->db->tableExists('refer_master')) {
            return [];
        }

        return $this->db->table('refer_master')
            ->where('active', 1)
            ->orderBy('f_name', 'ASC')
            ->get()
            ->getResult();
    }

    private function buildAdmissionHeaderPayload(array $panelData): array
    {
        $ipd = $panelData['ipd_info'] ?? null;

        return [
            'admit_date' => (string) ($ipd->str_register_date ?? ''),
            'discharge_date' => (string) ($ipd->str_discharge_date ?? ''),
            'no_days' => (string) ($ipd->no_days ?? ''),
        ];
    }

    private function formatHtmlDateInput(string $dateValue): string
    {
        $dateValue = trim($dateValue);
        if ($dateValue === '') {
            return date('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue) === 1) {
            return $dateValue;
        }

        $timestamp = strtotime($dateValue);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : date('Y-m-d');
    }

    private function formatHtmlTimeInput(string $timeValue): string
    {
        $timeValue = trim($timeValue);
        if ($timeValue === '') {
            return date('H:i');
        }

        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $timeValue) === 1) {
            return substr($timeValue, 0, 5);
        }

        $timestamp = strtotime($timeValue);

        return $timestamp !== false ? date('H:i', $timestamp) : date('H:i');
    }

    private function normalizeAdmissionDate(string $dateValue): ?string
    {
        $dateValue = trim($dateValue);
        if ($dateValue === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue) === 1) {
            return $dateValue;
        }

        $timestamp = strtotime($dateValue);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    private function normalizeAdmissionTime(string $timeValue): ?string
    {
        $timeValue = trim($timeValue);
        if ($timeValue === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $timeValue) === 1) {
            return substr($timeValue, 0, 5);
        }

        $timestamp = strtotime($timeValue);

        return $timestamp !== false ? date('H:i', $timestamp) : null;
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

    private function resolveBedsideItemTypeId(string $category): int
    {
        $groupDesc = 'Bedside - ' . trim($category);
        if ($groupDesc === 'Bedside -') {
            $groupDesc = 'Bedside - General';
        }

        $row = $this->db->table('ipd_item_type')
            ->select('itype_id')
            ->where('group_desc', $groupDesc)
            ->get()
            ->getRow();

        if ($row) {
            return (int) ($row->itype_id ?? 0);
        }

        $this->db->table('ipd_item_type')->insert([
            'group_desc' => $groupDesc,
        ]);

        return (int) $this->db->insertID();
    }

    private function resolveDoctorVisitItemTypeId(): int
    {
        $groupDesc = 'Doctor Visit';

        $row = $this->db->table('ipd_item_type')
            ->select('itype_id')
            ->where('group_desc', $groupDesc)
            ->get()
            ->getRow();

        if ($row) {
            return (int) ($row->itype_id ?? 0);
        }

        $this->db->table('ipd_item_type')->insert([
            'group_desc' => $groupDesc,
        ]);

        return (int) $this->db->insertID();
    }

    private function insertNursingIpdChargeRow(array $data): int
    {
        if (! $this->db->tableExists('nursing_ipd_charges')) {
            return 0;
        }

        $this->db->table('nursing_ipd_charges')->insert($data);

        return (int) $this->db->insertID();
    }

    private function syncNursingChargesToInvoice(int $ipdId): void
    {
        if (! $this->db->tableExists('nursing_ipd_charges')) {
            return;
        }

        $rows = $this->db->table('nursing_ipd_charges')
            ->where('ipd_id', $ipdId)
            ->where('include_in_bill', 1)
            ->orderBy('charge_id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return;
        }

        $caseMeta = $this->ipdModel->getIpdCaseMeta($ipdId);
        $insId = (int) ($caseMeta['insurance_id'] ?? 0);

        foreach ($rows as $row) {
            $chargeId = (int) ($row['charge_id'] ?? 0);
            if ($chargeId <= 0) {
                continue;
            }

            $marker = '[NURSING_CHARGE_ID:' . $chargeId . ']';
            $existsByMarker = $this->db->table('ipd_invoice_item')
                ->where('ipd_id', $ipdId)
                ->like('comment', $marker)
                ->countAllResults();
            if ($existsByMarker > 0) {
                continue;
            }

            $itemName = trim((string) ($row['item_name'] ?? ''));
            $dateItem = (string) ($row['visit_date'] ?? date('Y-m-d'));
            $qty = (float) ($row['qty'] ?? 1);
            $rate = (float) ($row['rate'] ?? 0);
            $amount = (float) ($row['amount'] ?? ($rate * $qty));
            $docId = (int) ($row['doctor_id'] ?? 0);

            $existsByValue = $this->db->table('ipd_invoice_item')
                ->where('ipd_id', $ipdId)
                ->where('item_name', $itemName)
                ->where('date_item', $dateItem)
                ->where('item_qty', $qty)
                ->where('item_amount', $amount)
                ->where('doc_id', $docId)
                ->countAllResults();
            if ($existsByValue > 0) {
                continue;
            }

            $rawType = trim((string) ($row['item_type'] ?? ''));
            $typeId = stripos($rawType, 'doctor') !== false
                ? $this->resolveDoctorVisitItemTypeId()
                : $this->resolveBedsideItemTypeId($rawType !== '' ? $rawType : 'General');

            $remarks = trim((string) ($row['remarks'] ?? ''));
            $comment = trim($remarks . ' ' . $marker);

            $this->ipdEditModel->insertIpdItem([
                'ipd_id' => $ipdId,
                'item_type' => $typeId,
                'item_id' => (int) ($row['item_id'] ?? $chargeId),
                'item_name' => $itemName,
                'item_rate' => $rate,
                'item_added_date' => date('Y-m-d H:i:s'),
                'item_qty' => $qty,
                'item_amount' => $amount,
                'doc_id' => $docId,
                'doc_name' => (string) ($row['doctor_name'] ?? ''),
                'date_item' => $dateItem,
                'comment' => $comment,
                'ins_id' => $insId,
                'org_code' => '',
            ]);
        }
    }
}
