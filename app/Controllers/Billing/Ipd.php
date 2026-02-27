<?php

namespace App\Controllers\Billing;

use App\Controllers\BaseController;
use App\Models\BedAssignmentHistoryModel;
use App\Models\ItemIpdModel;
use App\Models\IpdBillingModel;
use App\Models\IpdModel;
use App\Models\PaymentModel;

class Ipd extends BaseController
{
    protected IpdBillingModel $ipdModel;
    protected BedAssignmentHistoryModel $bedAssignmentModel;
    protected ItemIpdModel $itemIpdModel;
    protected IpdModel $ipdEditModel;

    public function __construct()
    {
        $this->ipdModel = new IpdBillingModel();
        $this->bedAssignmentModel = new BedAssignmentHistoryModel();
        $this->itemIpdModel = new ItemIpdModel();
        $this->ipdEditModel = new IpdModel();
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
            return view('billing/ipd/panel_admission', $panelData);
        }

        if ($tab === 'bed-assign') {
            $panelData['bed_assignments'] = $this->bedAssignmentModel->getByIpd($ipdId);

            return view('billing/ipd/panel_bed_assign', $panelData);
        }

        if ($tab === 'ipd-charges') {
            $panelData['ipd_charges_grouped'] = $this->ipdModel->getIpdChargesGrouped($ipdId);
            $panelData['ipd_charges_total'] = $this->ipdModel->getIpdChargesTotal($ipdId);
            $panelData['ipd_packages'] = $this->ipdModel->getIpdPackages($ipdId);

            $caseMeta = $this->ipdModel->getIpdCaseMeta($ipdId);
            $insCompId = (int) ($caseMeta['insurance_id'] ?? 0);
            if ($insCompId <= 0) {
                $insCompId = 1;
            }

            $panelData['ipd_insurance_id'] = $insCompId;
            $panelData['doc_list'] = $this->ipdModel->getIpdDoctorList();
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

            return view('billing/ipd/panel_bill_details', $panelData);
        }

        if ($tab === 'discharge-process') {
            $panelData['discharge_info'] = $this->ipdModel->getDischargeInfo($ipdId);

            return view('billing/ipd/panel_discharge', $panelData);
        }

        return view('billing/ipd/panel_placeholder', [
            'title' => ucwords(str_replace('-', ' ', $tab)),
        ]);
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

        return $this->response->setJSON(['ok' => $result === 1]);
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
}
