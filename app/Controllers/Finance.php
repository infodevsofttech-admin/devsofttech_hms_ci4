<?php

namespace App\Controllers;

use App\Models\FinanceGrnModel;
use App\Models\FinanceCashTransactionModel;
use App\Models\FinanceBankDepositModel;
use App\Models\FinanceDoctorAgreementModel;
use App\Models\FinanceDoctorPayoutModel;
use App\Models\FinancePolicySettingModel;
use App\Models\FinancePoDocumentModel;
use App\Models\FinancePurchaseOrderModel;
use App\Models\FinanceBankPosSettlementModel;
use App\Models\FinanceBankReconciliationAuditModel;
use App\Models\FinanceBankSettlementEntryModel;
use App\Models\FinanceBankStatementEntryModel;
use App\Models\FinanceScrollSubmissionItemModel;
use App\Models\FinanceScrollSubmissionModel;
use App\Models\FinanceVendorInvoiceModel;
use App\Models\FinancePharmacyBillModel;
use App\Models\FinanceVendorModel;

class Finance extends BaseController
{
    private FinanceVendorModel $vendorModel;
    private FinancePurchaseOrderModel $poModel;
    private FinancePoDocumentModel $poDocumentModel;
    private FinanceGrnModel $grnModel;
    private FinanceVendorInvoiceModel $invoiceModel;
    private FinanceCashTransactionModel $cashTxnModel;
    private FinanceBankDepositModel $bankDepositModel;
    private FinanceBankStatementEntryModel $bankStatementEntryModel;
    private FinanceBankPosSettlementModel $bankPosSettlementModel;
    private FinanceBankReconciliationAuditModel $bankReconcileAuditModel;
    private FinanceBankSettlementEntryModel $bankSettlementEntryModel;
    private FinancePolicySettingModel $policySettingModel;
    private FinanceScrollSubmissionModel $scrollModel;
    private FinanceScrollSubmissionItemModel $scrollItemModel;
    private FinanceDoctorAgreementModel $doctorAgreementModel;
    private FinanceDoctorPayoutModel $doctorPayoutModel;
    private FinancePharmacyBillModel $pharmBillModel;

    public function __construct()
    {
        $this->vendorModel = new FinanceVendorModel();
        $this->poModel = new FinancePurchaseOrderModel();
        $this->poDocumentModel = new FinancePoDocumentModel();
        $this->grnModel = new FinanceGrnModel();
        $this->invoiceModel = new FinanceVendorInvoiceModel();
        $this->cashTxnModel = new FinanceCashTransactionModel();
        $this->bankDepositModel = new FinanceBankDepositModel();
        $this->bankStatementEntryModel = new FinanceBankStatementEntryModel();
        $this->bankPosSettlementModel = new FinanceBankPosSettlementModel();
        $this->bankReconcileAuditModel = new FinanceBankReconciliationAuditModel();
        $this->bankSettlementEntryModel = new FinanceBankSettlementEntryModel();
        $this->policySettingModel = new FinancePolicySettingModel();
        $this->scrollModel = new FinanceScrollSubmissionModel();
        $this->scrollItemModel = new FinanceScrollSubmissionItemModel();
        $this->doctorAgreementModel = new FinanceDoctorAgreementModel();
        $this->doctorPayoutModel = new FinanceDoctorPayoutModel();
        $this->pharmBillModel = new FinancePharmacyBillModel();
    }

    public function bankDeposits()
    {
        if (! $this->canFinanceAny(['finance.bank.deposit.create', 'finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/bank_deposits', [
            'deposits' => $this->fetchBankDeposits(),
            'scroll_options' => $this->scrollModel->orderBy('id', 'DESC')->findAll(100),
            'summary' => $this->buildDepositSummary(),
        ]);
    }

    public function bankDepositsTable()
    {
        if (! $this->canFinanceAny(['finance.bank.deposit.create', 'finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/bank_deposits_table', [
            'deposits' => $this->fetchBankDeposits(),
        ]);
    }

    public function bankDepositCreate()
    {
        if (! $this->canFinance('finance.bank.deposit.create')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $depositDate = trim((string) ($this->request->getPost('deposit_date') ?? ''));
        $department = trim((string) ($this->request->getPost('department') ?? ''));
        $bankName = trim((string) ($this->request->getPost('bank_name') ?? ''));
        $amount = (float) ($this->request->getPost('deposited_amount') ?? 0);
        $relatedScrollId = (int) ($this->request->getPost('related_scroll_id') ?? 0) ?: null;

        if ($depositDate === '' || $department === '' || $bankName === '' || $amount <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Date, department, bank name and amount are required.',
            ]);
        }

        $status = 'pending';
        if ($relatedScrollId !== null) {
            $scroll = $this->scrollModel->find($relatedScrollId);
            if ($scroll) {
                $variance = abs((float) ($scroll['submitted_amount'] ?? 0) - $amount);
                $status = $variance <= 0.01 ? 'matched' : 'pending';
            }
        }

        $this->bankDepositModel->insert([
            'deposit_date' => $depositDate,
            'department' => $department,
            'bank_name' => $bankName,
            'slip_no' => trim((string) ($this->request->getPost('slip_no') ?? '')),
            'deposited_amount' => $amount,
            'related_scroll_id' => $relatedScrollId,
            'reconciliation_status' => $status,
            'remarks' => trim((string) ($this->request->getPost('remarks') ?? '')),
            'created_by' => $this->currentUserName(),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Bank deposit saved. Reconciliation status: ' . $status . '.',
        ]);
    }

    public function complianceReport()
    {
        if (! $this->canFinance('finance.compliance.view')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $from = $this->normalizeDateInput((string) ($this->request->getGet('from') ?? ''));
        $to = $this->normalizeDateInput((string) ($this->request->getGet('to') ?? ''));
        $export = (string) ($this->request->getGet('export') ?? '') === '1';

        $invoiceQuery = $this->invoiceModel
            ->select('finance_vendor_invoices.*, finance_vendors.vendor_name')
            ->join('finance_vendors', 'finance_vendors.id = finance_vendor_invoices.vendor_id', 'left')
            ->where('finance_vendor_invoices.is_compliance_hold', 1);
        if ($from !== '') {
            $invoiceQuery->where('finance_vendor_invoices.invoice_date >=', $from);
        }
        if ($to !== '') {
            $invoiceQuery->where('finance_vendor_invoices.invoice_date <=', $to);
        }
        $invoiceExceptions = $invoiceQuery
            ->orderBy('finance_vendor_invoices.id', 'DESC')
            ->findAll($export ? 5000 : 20);

        $cashQuery = $this->cashTxnModel->where('is_compliance_hold', 1);
        if ($from !== '') {
            $cashQuery->where('txn_date >=', $from);
        }
        if ($to !== '') {
            $cashQuery->where('txn_date <=', $to);
        }
        $cashAlerts = $cashQuery
            ->orderBy('id', 'DESC')
            ->findAll($export ? 5000 : 20);

        $pendingPayoutQuery = $this->doctorPayoutModel->where('status !=', 'paid');
        if ($from !== '') {
            $pendingPayoutQuery->where('payout_date >=', $from);
        }
        if ($to !== '') {
            $pendingPayoutQuery->where('payout_date <=', $to);
        }
        $pendingPayouts = $pendingPayoutQuery->countAllResults();

        $invoiceHoldCountQuery = $this->invoiceModel->where('is_compliance_hold', 1);
        if ($from !== '') {
            $invoiceHoldCountQuery->where('invoice_date >=', $from);
        }
        if ($to !== '') {
            $invoiceHoldCountQuery->where('invoice_date <=', $to);
        }

        $cashHoldCountQuery = $this->cashTxnModel->where('is_compliance_hold', 1);
        if ($from !== '') {
            $cashHoldCountQuery->where('txn_date >=', $from);
        }
        if ($to !== '') {
            $cashHoldCountQuery->where('txn_date <=', $to);
        }

        $pendingDepositQuery = $this->bankDepositModel->where('reconciliation_status', 'pending');
        if ($from !== '') {
            $pendingDepositQuery->where('deposit_date >=', $from);
        }
        if ($to !== '') {
            $pendingDepositQuery->where('deposit_date <=', $to);
        }

        $summary = [
            'invoice_holds' => $invoiceHoldCountQuery->countAllResults(),
            'cash_holds' => $cashHoldCountQuery->countAllResults(),
            'pending_deposits' => $pendingDepositQuery->countAllResults(),
            'pending_payouts' => $pendingPayouts,
        ];

        if ($export) {
            $lines = [];
            $lines[] = $this->csvLine(['Finance Compliance Report']);
            $lines[] = $this->csvLine(['From', $from !== '' ? $from : 'NA', 'To', $to !== '' ? $to : 'NA']);
            $lines[] = '';
            $lines[] = $this->csvLine(['Summary']);
            $lines[] = $this->csvLine(['Invoice Holds', (string) $summary['invoice_holds']]);
            $lines[] = $this->csvLine(['Cash Holds', (string) $summary['cash_holds']]);
            $lines[] = $this->csvLine(['Pending Deposits', (string) $summary['pending_deposits']]);
            $lines[] = $this->csvLine(['Draft/Unpaid Payouts', (string) $summary['pending_payouts']]);
            $lines[] = '';
            $lines[] = $this->csvLine(['Cash Compliance Alerts']);
            $lines[] = $this->csvLine(['Date', 'Type', 'Department', 'Amount', 'Note']);
            foreach ($cashAlerts as $row) {
                $lines[] = $this->csvLine([
                    (string) ($row['txn_date'] ?? ''),
                    (string) ($row['txn_type'] ?? ''),
                    (string) ($row['department'] ?? ''),
                    number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                    (string) ($row['compliance_note'] ?? ''),
                ]);
            }
            $lines[] = '';
            $lines[] = $this->csvLine(['Invoice Match Exceptions']);
            $lines[] = $this->csvLine(['Date', 'Invoice', 'Vendor', 'Status', 'Variance', 'Note']);
            foreach ($invoiceExceptions as $row) {
                $lines[] = $this->csvLine([
                    (string) ($row['invoice_date'] ?? ''),
                    (string) ($row['invoice_no'] ?? ''),
                    (string) ($row['vendor_name'] ?? ''),
                    (string) ($row['match_status'] ?? ''),
                    number_format((float) ($row['variance_amount'] ?? 0), 2, '.', ''),
                    (string) ($row['match_note'] ?? ''),
                ]);
            }

            $filename = 'finance_compliance_' . date('Ymd_His') . '.csv';

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', 'attachment; filename=' . $filename)
                ->setBody(implode("\n", $lines));
        }

        return view('finance/compliance_report', [
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'invoice_exceptions' => $invoiceExceptions,
            'cash_alerts' => $cashAlerts,
        ]);
    }

    public function doctorPayout()
    {
        if (! $this->canFinance('finance.doctor_payout.manage')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/doctor_payout', [
            'agreements' => $this->fetchDoctorAgreements(),
            'payouts' => $this->fetchDoctorPayouts(),
            'doctor_options' => $this->doctorAgreementModel->where('status', 1)->orderBy('doctor_name', 'ASC')->findAll(),
            'summary' => $this->buildDoctorPayoutSummary(),
        ]);
    }

    public function doctorAgreementsTable()
    {
        if (! $this->canFinance('finance.doctor_payout.manage')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/doctor_agreements_table', [
            'agreements' => $this->fetchDoctorAgreements(),
        ]);
    }

    public function doctorPayoutsTable()
    {
        if (! $this->canFinance('finance.doctor_payout.manage')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/doctor_payouts_table', [
            'payouts' => $this->fetchDoctorPayouts(),
        ]);
    }

    public function doctorAgreementCreate()
    {
        if (! $this->canFinance('finance.doctor_payout.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $doctorCode = strtoupper(trim((string) ($this->request->getPost('doctor_code') ?? '')));
        $doctorName = trim((string) ($this->request->getPost('doctor_name') ?? ''));

        if ($doctorCode === '' || $doctorName === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Doctor code and name are required.',
            ]);
        }

        if ($this->doctorAgreementModel->where('doctor_code', $doctorCode)->countAllResults() > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Doctor code already exists.',
            ]);
        }

        $this->doctorAgreementModel->insert([
            'doctor_code' => $doctorCode,
            'doctor_name' => $doctorName,
            'specialization' => trim((string) ($this->request->getPost('specialization') ?? '')),
            'consultation_rate' => (float) ($this->request->getPost('consultation_rate') ?? 0),
            'surgery_rate' => (float) ($this->request->getPost('surgery_rate') ?? 0),
            'agreement_start_date' => trim((string) ($this->request->getPost('agreement_start_date') ?? '')) ?: null,
            'agreement_end_date' => trim((string) ($this->request->getPost('agreement_end_date') ?? '')) ?: null,
            'status' => 1,
            'created_by' => $this->currentUserName(),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Doctor agreement saved successfully.',
        ]);
    }

    public function doctorPayoutCreate()
    {
        if (! $this->canFinance('finance.doctor_payout.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $doctorId = (int) ($this->request->getPost('doctor_id') ?? 0);
        $payoutDate = trim((string) ($this->request->getPost('payout_date') ?? ''));
        $payoutType = trim((string) ($this->request->getPost('payout_type') ?? ''));
        $units = max(1, (int) ($this->request->getPost('units') ?? 1));

        if ($doctorId <= 0 || $payoutDate === '' || ! in_array($payoutType, ['consultation', 'surgery'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Doctor, payout date and payout type are required.',
            ]);
        }

        $agreement = $this->doctorAgreementModel->find($doctorId);
        if (! $agreement) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Doctor agreement not found.',
            ]);
        }

        $rate = $payoutType === 'consultation'
            ? (float) ($agreement['consultation_rate'] ?? 0)
            : (float) ($agreement['surgery_rate'] ?? 0);
        $calculatedAmount = round($rate * $units, 2);

        $this->doctorPayoutModel->insert([
            'payout_date' => $payoutDate,
            'doctor_id' => $doctorId,
            'case_reference' => trim((string) ($this->request->getPost('case_reference') ?? '')),
            'payout_type' => $payoutType,
            'units' => $units,
            'rate' => $rate,
            'calculated_amount' => $calculatedAmount,
            'approved_amount' => $calculatedAmount,
            'status' => 'draft',
            'remarks' => trim((string) ($this->request->getPost('remarks') ?? '')),
            'hr_submitted_by' => $this->currentUserName(),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Payout draft created. Current status: draft.',
        ]);
    }

    public function doctorPayoutApprove()
    {
        if (! $this->canFinance('finance.doctor_payout.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $payoutId = (int) ($this->request->getPost('payout_id') ?? 0);
        $level = trim((string) ($this->request->getPost('level') ?? ''));

        $row = $this->doctorPayoutModel->find($payoutId);
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Payout not found']);
        }

        $status = (string) ($row['status'] ?? 'draft');
        $now = date('Y-m-d H:i:s');
        $user = $this->currentUserName();
        $update = [];

        if ($level === 'finance' && $status === 'draft') {
            $update = [
                'status' => 'finance_approved',
                'finance_approved_by' => $user,
                'finance_approved_at' => $now,
            ];
        } elseif ($level === 'ceo' && $status === 'finance_approved') {
            $update = [
                'status' => 'ceo_approved',
                'ceo_approved_by' => $user,
                'ceo_approved_at' => $now,
            ];
        } elseif ($level === 'paid' && $status === 'ceo_approved') {
            $update = [
                'status' => 'paid',
                'paid_at' => $now,
            ];
        } else {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Invalid approval transition from ' . $status . ' using level ' . $level . '.',
            ]);
        }

        $this->doctorPayoutModel->update($payoutId, $update);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Payout status updated to ' . $update['status'] . '.',
        ]);
    }

    public function cashbook()
    {
        if (! $this->canFinanceAny(['finance.cash.accounts.accept', 'finance.cash.accounts.verify'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return $this->renderCashbook(false, true);
    }

    public function cashbookBilling()
    {
        if (! $this->canFinance('finance.cash.billing.submit')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return $this->renderCashbook(true, false);
    }

    public function cashbookAccounts()
    {
        if (! $this->canFinanceAny(['finance.cash.accounts.accept', 'finance.cash.accounts.verify'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return $this->renderCashbook(false, true);
    }

    private function renderCashbook(bool $showBillingPanel, bool $showAccountsPanel)
    {
        if (! $this->canFinanceAny(['finance.cash.billing.submit', 'finance.cash.accounts.accept', 'finance.cash.accounts.verify'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/cashbook', [
            'transactions' => $this->fetchCashTransactions(),
            'scrolls' => $this->fetchScrolls(),
            'summary' => $this->buildCashSummary(),
            'queue_summary' => $this->buildScrollQueueSummary(),
            'collector_options' => $this->fetchCollectorOptions(),
            'show_billing_panel' => $showBillingPanel,
            'show_accounts_panel' => $showAccountsPanel,
        ]);
    }

    public function cashTransactionsTable()
    {
        if (! $this->canFinanceAny(['finance.cash.billing.submit', 'finance.cash.accounts.accept', 'finance.cash.accounts.verify'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/cash_transactions_table', [
            'transactions' => $this->fetchCashTransactions(),
        ]);
    }

    public function scrollTable()
    {
        if (! $this->canFinanceAny(['finance.cash.billing.submit', 'finance.cash.accounts.accept', 'finance.cash.accounts.verify'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/scroll_table', [
            'scrolls' => $this->fetchScrolls(),
        ]);
    }

    public function fetchPaymentsForPeriod()
    {
        if (! $this->canFinanceAny(['finance.cash.billing.submit', 'finance.cash.accounts.accept', 'finance.cash.accounts.verify'])) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $startDateTime = trim((string) ($this->request->getPost('start_datetime') ?? $this->request->getGet('start_datetime') ?? ''));
        $endDateTime = trim((string) ($this->request->getPost('end_datetime') ?? $this->request->getGet('end_datetime') ?? ''));
        $collectedBy = trim((string) ($this->request->getPost('collected_by') ?? ''));
        $paymentMode = 'cash';

        if ($startDateTime === '' || $endDateTime === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Start and end date/time are required.',
            ]);
        }

        $startTs = strtotime($startDateTime);
        $endTs = strtotime($endDateTime);
        if ($startTs === false || $endTs === false || $startTs > $endTs) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Invalid collection period.',
            ]);
        }

        $startAt = date('Y-m-d H:i:s', $startTs);
        $endAt = date('Y-m-d H:i:s', $endTs);
        $rows = $this->fetchPaymentHistoryRows($startAt, $endAt, $collectedBy, $paymentMode);
        $totals = $this->summarizePaymentHistoryRows($rows);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Payments loaded for selected period.',
            'start_datetime' => $startAt,
            'end_datetime' => $endAt,
            'total_receipts' => (float) ($totals['total_receipts'] ?? 0),
            'payment_count' => (int) ($totals['payment_count'] ?? 0),
        ]);
    }

    public function paymentSelectionTable()
    {
        if (! $this->canFinanceAny(['finance.cash.billing.submit', 'finance.cash.accounts.accept', 'finance.cash.accounts.verify'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $startDateTime = trim((string) ($this->request->getGet('start_datetime') ?? ''));
        $endDateTime = trim((string) ($this->request->getGet('end_datetime') ?? ''));
        $collectedBy = trim((string) ($this->request->getGet('collected_by') ?? ''));
        $paymentMode = 'cash';

        $rows = [];
        $totals = ['total_receipts' => 0.0, 'payment_count' => 0];
        if ($startDateTime !== '' && $endDateTime !== '') {
            $startTs = strtotime($startDateTime);
            $endTs = strtotime($endDateTime);
            if ($startTs !== false && $endTs !== false && $startTs <= $endTs) {
                $rows = $this->fetchPaymentHistoryRows(
                    date('Y-m-d H:i:s', $startTs),
                    date('Y-m-d H:i:s', $endTs),
                    $collectedBy,
                    $paymentMode
                );
                $totals = $this->summarizePaymentHistoryRows($rows);
            }
        }

        return view('finance/partials/payment_selection_table', [
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    public function scrollItemsTable()
    {
        if (! $this->canFinanceAny(['finance.cash.billing.submit', 'finance.cash.accounts.accept', 'finance.cash.accounts.verify'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $scrollId = (int) ($this->request->getGet('scroll_id') ?? 0);
        if ($scrollId <= 0) {
            return $this->response->setStatusCode(422)->setBody('Invalid statement id.');
        }

        $rows = $this->scrollItemModel
            ->where('scroll_submission_id', $scrollId)
            ->orderBy('id', 'ASC')
            ->findAll(1000);

        return view('finance/partials/scroll_items_table', [
            'rows' => $rows,
            'scroll_id' => $scrollId,
        ]);
    }

    public function cashTransactionCreate()
    {
        if (! $this->canFinance('finance.cash.billing.submit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $txnDate = trim((string) ($this->request->getPost('txn_date') ?? ''));
        $txnType = trim((string) ($this->request->getPost('txn_type') ?? ''));
        $amount = (float) ($this->request->getPost('amount') ?? 0);
        $mode = trim((string) ($this->request->getPost('mode') ?? ''));

        if ($txnDate === '' || ! in_array($txnType, ['receipt', 'disbursement'], true) || $amount <= 0 || $mode === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Date, type, amount and mode are required.',
            ]);
        }

        $flag269st = 0;
        $flag40a3 = 0;
        $complianceHold = 0;
        $notes = [];

        if ($mode === 'cash' && $txnType === 'receipt' && $amount >= 200000) {
            $flag269st = 1;
            $complianceHold = 1;
            $notes[] = 'Potential Sec 269ST threshold breach (>= 200000 cash receipt).';
        }

        if ($mode === 'cash' && $txnType === 'disbursement' && $amount > 10000) {
            $flag40a3 = 1;
            $complianceHold = 1;
            $notes[] = 'Potential Sec 40A(3) breach (> 10000 cash disbursement).';
        }

        if ($mode === 'cash' && $txnType === 'disbursement') {
            $pettyLimit = $this->getPolicyLimit('petty_cash_daily_limit', 50000);
            $todayTotal = $this->cashTxnModel
                ->select('COALESCE(SUM(amount),0) as total')
                ->where('txn_date', $txnDate)
                ->where('txn_type', 'disbursement')
                ->where('mode', 'cash')
                ->where('department', trim((string) ($this->request->getPost('department') ?? '')))
                ->first();

            $projected = (float) ($todayTotal['total'] ?? 0) + $amount;
            if ($projected > $pettyLimit) {
                $complianceHold = 1;
                $notes[] = 'Petty cash daily limit exceeded. Limit: ' . number_format($pettyLimit, 2) . ', projected: ' . number_format($projected, 2) . '.';
            }
        }

        $this->cashTxnModel->insert([
            'txn_date' => $txnDate,
            'txn_type' => $txnType,
            'flow_type' => trim((string) ($this->request->getPost('flow_type') ?? 'other')),
            'department' => trim((string) ($this->request->getPost('department') ?? '')),
            'reference_no' => trim((string) ($this->request->getPost('reference_no') ?? '')),
            'amount' => $amount,
            'mode' => $mode,
            'party_name' => trim((string) ($this->request->getPost('party_name') ?? '')),
            'narration' => trim((string) ($this->request->getPost('narration') ?? '')),
            'flag_269st' => $flag269st,
            'flag_40a3' => $flag40a3,
            'is_compliance_hold' => $complianceHold,
            'compliance_note' => empty($notes) ? 'Compliant entry' : implode(' ', $notes),
            'created_by' => $this->currentUserName(),
        ]);

        $message = empty($notes)
            ? 'Cash transaction saved.'
            : 'Cash transaction saved with compliance alert: ' . implode(' ', $notes);

        return $this->response->setJSON([
            'status' => 1,
            'message' => $message,
        ]);
    }

    public function scrollCreate()
    {
        if (! $this->canFinance('finance.cash.billing.submit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $startDateTime = trim((string) ($this->request->getPost('start_datetime') ?? ''));
        $endDateTime = trim((string) ($this->request->getPost('end_datetime') ?? ''));
        $department = trim((string) ($this->request->getPost('department') ?? ''));
        $collectedBy = trim((string) ($this->request->getPost('collected_by') ?? ''));
        $paymentMode = 'cash';
        $submittedAmount = (float) ($this->request->getPost('submitted_amount') ?? 0);
        $selectedPaymentIds = $this->request->getPost('payment_ids');
        $paymentIds = [];

        if (is_array($selectedPaymentIds)) {
            foreach ($selectedPaymentIds as $pid) {
                $id = (int) $pid;
                if ($id > 0) {
                    $paymentIds[] = $id;
                }
            }
            $paymentIds = array_values(array_unique($paymentIds));
        }

        if (empty($paymentIds)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Select at least one payment to submit.',
            ]);
        }

        if ($startDateTime === '' || $endDateTime === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Start and end date/time are required.',
            ]);
        }

        $startTs = strtotime($startDateTime);
        $endTs = strtotime($endDateTime);
        if ($startTs === false || $endTs === false || $startTs > $endTs) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Invalid collection period.',
            ]);
        }

        $startAt = date('Y-m-d H:i:s', $startTs);
        $endAt = date('Y-m-d H:i:s', $endTs);
        $scrollDate = date('Y-m-d', $startTs);
        $department = $department !== '' ? $department : 'Billing';
        $selectedRows = $this->fetchPaymentHistoryRows($startAt, $endAt, $collectedBy, $paymentMode, $paymentIds);
        if (empty($selectedRows)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Selected payments are not valid for the chosen filter.',
            ]);
        }

        $totals = $this->summarizePaymentHistoryRows($selectedRows);

        $totalReceipts = (float) ($totals['total_receipts'] ?? 0);
        $paymentCount = (int) ($totals['payment_count'] ?? 0);
        $submittedAmount = $totalReceipts;
        $variance = round($submittedAmount - $totalReceipts, 2);
        $status = 'pending';

        $insertData = [
            'scroll_date' => $scrollDate,
            'department' => $department,
            'total_receipts' => $totalReceipts,
            'submitted_amount' => $submittedAmount,
            'variance_amount' => $variance,
            'reconciliation_status' => $status,
            'submitted_by' => trim((string) ($this->request->getPost('submitted_by') ?? $this->currentUserName())),
            'remarks' => trim((string) ($this->request->getPost('remarks') ?? '')),
        ];

        if ($this->tableHasField('finance_scroll_submissions', 'start_datetime')) {
            $insertData['start_datetime'] = $startAt;
        }
        if ($this->tableHasField('finance_scroll_submissions', 'end_datetime')) {
            $insertData['end_datetime'] = $endAt;
        }
        if ($this->tableHasField('finance_scroll_submissions', 'collected_by')) {
            $insertData['collected_by'] = $collectedBy;
        }
        if ($this->tableHasField('finance_scroll_submissions', 'payment_count')) {
            $insertData['payment_count'] = $paymentCount;
        }

        $this->db->transStart();
        $this->scrollModel->insert($insertData);
        $scrollId = (int) $this->scrollModel->getInsertID();

        foreach ($selectedRows as $paymentRow) {
            $this->scrollItemModel->insert([
                'scroll_submission_id' => $scrollId,
                'payment_history_id' => (int) ($paymentRow['id'] ?? 0),
                'payment_date' => (string) ($paymentRow['payment_date'] ?? ''),
                'amount' => (float) ($paymentRow['amount'] ?? 0),
                'payment_mode' => (int) ($paymentRow['payment_mode'] ?? 0),
                'payof_type' => (int) ($paymentRow['payof_type'] ?? 0),
                'payof_id' => (int) ($paymentRow['payof_id'] ?? 0),
                'payof_code' => (string) ($paymentRow['payof_code'] ?? ''),
                'update_by_id' => (int) ($paymentRow['update_by_id'] ?? 0),
                'update_by' => (string) ($paymentRow['update_by'] ?? ''),
                'snapshot_json' => json_encode($paymentRow),
            ]);
        }

        if ($this->tableHasField('payment_history', 'cash_submission_status')) {
            $paymentBuilder = $this->db->table('payment_history');
            $paymentBuilder->whereIn('id', $paymentIds)->update([
                'cash_submission_status' => 'submitted',
            ]);

            if ($this->tableHasField('payment_history', 'cash_submission_scroll_id')) {
                $this->db->table('payment_history')->whereIn('id', $paymentIds)->update([
                    'cash_submission_scroll_id' => $scrollId,
                ]);
            }

            if ($this->tableHasField('payment_history', 'cash_submission_updated_at')) {
                $this->db->table('payment_history')->whereIn('id', $paymentIds)->update([
                    'cash_submission_updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 0,
                'message' => 'Unable to save cash submission.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Cash submission sent to Accounts with ' . $paymentCount . ' payments selected.',
        ]);
    }

    public function scrollAccept()
    {
        if (! $this->canFinance('finance.cash.accounts.accept')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $scrollId = (int) ($this->request->getPost('scroll_id') ?? 0);
        if ($scrollId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Invalid statement id.']);
        }

        $row = $this->scrollModel->find($scrollId);
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Statement not found.']);
        }

        $currentStatus = (string) ($row['reconciliation_status'] ?? 'pending');
        if (! in_array($currentStatus, ['submitted', 'pending'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Only submitted statements can be accepted. Current status: ' . $currentStatus . '.',
            ]);
        }

        $remarks = $this->appendAuditRemark((string) ($row['remarks'] ?? ''), 'Accepted by Accounts: ' . $this->currentUserName());

        $this->scrollModel->update($scrollId, [
            'reconciliation_status' => 'received',
            'remarks' => $remarks,
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Cash submission marked as Received by Accounts.',
        ]);
    }

    public function scrollVerify()
    {
        if (! $this->canFinance('finance.cash.accounts.verify')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $scrollId = (int) ($this->request->getPost('scroll_id') ?? 0);
        if ($scrollId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Invalid statement id.']);
        }

        $row = $this->scrollModel->find($scrollId);
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Statement not found.']);
        }

        $currentStatus = (string) ($row['reconciliation_status'] ?? 'pending');
        if (! in_array($currentStatus, ['accepted', 'received', 'matched'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Only accepted statements can be verified. Current status: ' . $currentStatus . '.',
            ]);
        }

        $remarks = $this->appendAuditRemark((string) ($row['remarks'] ?? ''), 'Verified by Accounts: ' . $this->currentUserName());

        $this->db->transStart();
        $this->scrollModel->update($scrollId, [
            'reconciliation_status' => 'deposited',
            'remarks' => $remarks,
        ]);

        if ($this->tableHasField('payment_history', 'cash_submission_status')) {
            $itemRows = $this->scrollItemModel
                ->select('payment_history_id')
                ->where('scroll_submission_id', $scrollId)
                ->findAll();

            $paymentIds = [];
            foreach ($itemRows as $item) {
                $pid = (int) ($item['payment_history_id'] ?? 0);
                if ($pid > 0) {
                    $paymentIds[] = $pid;
                }
            }

            $paymentIds = array_values(array_unique($paymentIds));
            if (! empty($paymentIds)) {
                $this->db->table('payment_history')->whereIn('id', $paymentIds)->update([
                    'cash_submission_status' => 'deposited',
                ]);

                if ($this->tableHasField('payment_history', 'cash_submission_updated_at')) {
                    $this->db->table('payment_history')->whereIn('id', $paymentIds)->update([
                        'cash_submission_updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 0,
                'message' => 'Unable to mark submission as deposited.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Cash submission marked as Deposited.',
        ]);
    }

    public function bankDepositStatusUpdate()
    {
        $depositId = (int) ($this->request->getPost('deposit_id') ?? 0);
        $action = trim((string) ($this->request->getPost('action') ?? ''));

        if ($depositId <= 0 || ! in_array($action, ['audit', 'statement_update'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Invalid request for bank status update.',
            ]);
        }

        if ($action === 'audit' && ! $this->canFinance('finance.bank.audit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        if ($action === 'statement_update' && ! $this->canFinance('finance.bank.statement.update')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $row = $this->bankDepositModel->find($depositId);
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Bank deposit not found.']);
        }

        $nextStatus = $action === 'audit' ? 'audited' : 'statement_updated';
        $note = $action === 'audit'
            ? 'Bank transaction audited by Accounts: ' . $this->currentUserName()
            : 'Updated in bank statement by Accounts: ' . $this->currentUserName();

        $remarks = $this->appendAuditRemark((string) ($row['remarks'] ?? ''), $note);

        $this->bankDepositModel->update($depositId, [
            'reconciliation_status' => $nextStatus,
            'remarks' => $remarks,
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Bank deposit status updated to ' . $nextStatus . '.',
        ]);
    }

    // ─── Bank Audit: Main page ─────────────────────────────────────────────────

    public function bankAudit()
    {
        if (! $this->canFinanceAny(['finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $db = \Config\Database::connect();

        // Summary cards (direct bank payments + settlement entries)
        $directUnmatched = $db->table('payment_history')
            ->where('payment_mode', 2)
            ->where('credit_debit', 0)
            ->where("(bank_reconcile_status IS NULL OR bank_reconcile_status = '')")
            ->countAllResults();

        $directMatched = $db->table('payment_history')
            ->where('payment_mode', 2)
            ->where('credit_debit', 0)
            ->where('bank_reconcile_status', 'matched')
            ->countAllResults();

        $settlementUnmatched = $db->table('finance_bank_settlement_entries')
            ->where('reconciliation_status', 'unmatched')
            ->countAllResults();

        $settlementMatched = $db->table('finance_bank_settlement_entries')
            ->where('reconciliation_status', 'matched')
            ->countAllResults();

        // Use same bank source master as billing invoice screen.
        $bankSourceOptions = $db->table('hospital_bank_payment_source s')
            ->select('s.id, s.pay_type, m.bank_name')
            ->join('hospital_bank m', 'm.id = s.bank_id', 'left')
            ->orderBy('m.bank_name', 'ASC')
            ->orderBy('s.pay_type', 'ASC')
            ->get()->getResultArray();

        $bankOptions = $db->table('hospital_bank')
            ->select('id, bank_name')
            ->orderBy('bank_name', 'ASC')
            ->get()->getResultArray();

        // Distinct users by ID (avoids duplicate timestamped update_by strings).
        $acceptedByUsers = $db->table('payment_history p')
            ->select('p.update_by_id AS user_id, COALESCE(MAX(u.username), MAX(p.update_by)) AS user_name', false)
            ->join('users u', 'u.id = p.update_by_id', 'left')
            ->where('p.payment_mode', 2)
            ->where('p.credit_debit', 0)
            ->where('p.update_by_id >', 0)
            ->groupBy('p.update_by_id')
            ->orderBy('user_name', 'ASC')
            ->get()->getResultArray();

        return view('finance/bank_audit', [
            'direct_unmatched'   => $directUnmatched,
            'direct_matched'     => $directMatched,
            'sett_unmatched'     => $settlementUnmatched,
            'sett_matched'       => $settlementMatched,
            'bank_options'       => $bankOptions,
            'bank_source_options'=> $bankSourceOptions,
            'accepted_by_users'  => $acceptedByUsers,
        ]);
    }

    // ─── Bank Audit: Direct reconciliation tables ──────────────────────────────

    public function bankAuditDirectPaymentsTable()
    {
        if (! $this->canFinanceAny(['finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

                $fromDate   = $this->normalizeDateInput((string) ($this->request->getGet('from_date') ?? ''));
                $toDate     = $this->normalizeDateInput((string) ($this->request->getGet('to_date') ?? ''));
                $status     = trim((string) ($this->request->getGet('status') ?? 'unmatched'));
                $bankId     = (int) ($this->request->getGet('bank_id') ?? $this->request->getGet('bank_name') ?? 0);
                $acceptedBy = (int) ($this->request->getGet('accepted_by') ?? 0);

        $db = \Config\Database::connect();
        $q = $db->table('payment_history ph')
                        ->select("ph.id, ph.payment_date, ph.amount, ph.payment_mode, ph.card_tran_id, ph.card_remark, ph.cust_card, ph.remark, ph.update_by, ph.update_by_id, ph.bank_reconcile_status, ph.bank_statement_entry_id, ph.bank_reconcile_batch_ref, ph.bankcard_machine, ph.insert_code, ph.payof_type, ph.payof_id, ph.payof_code,
                                COALESCE(MAX(u.username), MAX(ph.update_by)) AS accepted_by_name,
                                MAX(CONCAT(COALESCE(s.pay_type,''), ' [', COALESCE(m.bank_name,''), ']')) AS bank_source_label,
                                MAX(se.id) AS bank_settlement_entry_id,
                                MAX(se.settlement_ref) AS settlement_ref,
                                (SELECT bra.action_by FROM finance_bank_reconciliation_audit bra
                                 WHERE bra.payment_history_id = ph.id
                                     AND bra.action_type IN ('single_match','batch_match','settlement_match')
                                 ORDER BY bra.id DESC LIMIT 1) AS matched_by,
                                (SELECT bra.action_at FROM finance_bank_reconciliation_audit bra
                                 WHERE bra.payment_history_id = ph.id
                                     AND bra.action_type IN ('single_match','batch_match','settlement_match')
                                 ORDER BY bra.id DESC LIMIT 1) AS matched_at", false)
                        ->join('users u', 'u.id = ph.update_by_id', 'left')
                        ->join('hospital_bank_payment_source s', 'CAST(s.id AS CHAR) = ph.insert_code', 'left')
                        ->join('hospital_bank m', 'm.id = s.bank_id', 'left')
                        ->join('finance_bank_settlement_entries se', 'se.id = ph.bank_settlement_entry_id', 'left')
            ->where('ph.payment_mode', 2)
                        ->where('ph.credit_debit', 0)
                        ->groupBy('ph.id');

        if ($status === 'unmatched') {
            $q->where("(ph.bank_reconcile_status IS NULL OR ph.bank_reconcile_status = '')");
        } elseif ($status === 'matched') {
            $q->where('ph.bank_reconcile_status', 'matched');
        }

        if ($fromDate !== '') {
            $q->where('DATE(ph.payment_date) >=', $fromDate);
        }
        if ($toDate !== '') {
            $q->where('DATE(ph.payment_date) <=', $toDate);
        }
        if ($bankId > 0) {
            $q->where('m.id', $bankId);
        }
        if ($acceptedBy > 0) {
            $q->where('ph.update_by_id', $acceptedBy);
        }

        $rows = $q->orderBy('ph.payment_date', 'DESC')->limit(200)->get()->getResultArray();

        return view('finance/partials/bank_audit_direct_table', ['rows' => $rows, 'status_filter' => $status]);
    }

    public function bankStatementEntriesTable()
    {
        if (! $this->canFinanceAny(['finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $fromDate = $this->normalizeDateInput((string) ($this->request->getGet('from_date') ?? ''));
        $toDate   = $this->normalizeDateInput((string) ($this->request->getGet('to_date') ?? ''));
        $status   = trim((string) ($this->request->getGet('status') ?? 'unmatched'));

        $q = $this->bankStatementEntryModel->orderBy('entry_date', 'DESC');

        if ($status !== 'all') {
            $q->where('reconciliation_status', $status);
        }
        if ($fromDate !== '') {
            $q->where('entry_date >=', $fromDate);
        }
        if ($toDate !== '') {
            $q->where('entry_date <=', $toDate);
        }

        $entries = $q->limit(200)->findAll();

        return view('finance/partials/bank_statement_entries_table', ['entries' => $entries]);
    }

    public function bankStatementEntryCreate()
    {
        if (! $this->canFinance('finance.bank.audit')) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 0, 'message' => 'Access denied',
            ]);
        }

        $entryDate  = trim((string) ($this->request->getPost('entry_date') ?? ''));
        $amount     = (float) ($this->request->getPost('amount') ?? 0);
        $refNo      = trim((string) ($this->request->getPost('reference_no') ?? ''));
        $narration  = trim((string) ($this->request->getPost('narration') ?? ''));
        $txnType    = trim((string) ($this->request->getPost('transaction_type') ?? 'credit'));
        $remarks    = trim((string) ($this->request->getPost('remarks') ?? ''));

        if ($entryDate === '' || $amount <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0, 'message' => 'Entry date and amount are required.',
            ]);
        }

        if (! in_array($txnType, ['credit', 'debit'], true)) {
            $txnType = 'credit';
        }

        $this->bankStatementEntryModel->insert([
            'entry_date'            => $entryDate,
            'reference_no'         => $refNo !== '' ? $refNo : null,
            'narration'             => $narration !== '' ? $narration : null,
            'amount'                => $amount,
            'transaction_type'     => $txnType,
            'reconciliation_status'=> 'unmatched',
            'remarks'              => $remarks !== '' ? $remarks : null,
            'created_by'           => $this->currentUserName(),
        ]);

        return $this->response->setJSON(['status' => 1, 'message' => 'Bank statement entry saved.']);
    }

    public function bankReconcileMatch()
    {
        if (! $this->canFinance('finance.bank.audit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $paymentId = (int) ($this->request->getPost('payment_id') ?? 0);
        $remarks = trim((string) ($this->request->getPost('remarks') ?? ''));

        if ($paymentId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Payment ID required.']);
        }

        $db = \Config\Database::connect();
        $payment = $db->table('payment_history')
            ->where('id', $paymentId)
            ->where('payment_mode', 2)
            ->where('credit_debit', 0)
            ->get()->getRowArray();
        if (! $payment) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Payment not found.']);
        }

        $oldStatus = (string) ($payment['bank_reconcile_status'] ?? '');
        if ($oldStatus === 'matched') {
            return $this->response->setStatusCode(409)->setJSON(['status' => 0, 'message' => 'Payment already matched.']);
        }

        $db->table('payment_history')->where('id', $paymentId)->update([
            'bank_reconcile_status'   => 'matched',
            'bank_statement_entry_id' => null,
            'bank_reconcile_batch_ref'=> null,
            'bank_settlement_entry_id'=> null,
        ]);

        $this->logBankReconcileAudit(
            $paymentId,
            'single_match',
            $oldStatus,
            'matched',
            null,
            $remarks
        );

        return $this->response->setJSON(['status' => 1, 'message' => 'Payment marked as matched (bank received).']);
    }

    public function bankReconcileBatchMatch()
    {
        if (! $this->canFinance('finance.bank.audit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $paymentIds = $this->request->getPost('payment_ids');
        $remarks = trim((string) ($this->request->getPost('remarks') ?? ''));

        if (! is_array($paymentIds)) {
            $paymentIds = [];
        }

        $ids = [];
        foreach ($paymentIds as $id) {
            $v = (int) $id;
            if ($v > 0) {
                $ids[$v] = $v;
            }
        }
        $ids = array_values($ids);

        if ($ids === []) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Select at least one payment.']);
        }

        $db = \Config\Database::connect();
        $rows = $db->table('payment_history')
            ->select('id, bank_reconcile_status')
            ->whereIn('id', $ids)
            ->where('payment_mode', 2)
            ->where('credit_debit', 0)
            ->get()->getResultArray();

        if ($rows === []) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'No eligible bank payments found.']);
        }

        $eligibleIds = [];
        foreach ($rows as $row) {
            $eligibleIds[] = (int) ($row['id'] ?? 0);
        }

        $batchRef = 'BR-' . date('YmdHis') . '-' . random_int(1000, 9999);
        $matchedCount = 0;

        $db->transStart();
        foreach ($rows as $row) {
            $pid = (int) ($row['id'] ?? 0);
            $oldStatus = (string) ($row['bank_reconcile_status'] ?? '');
            if ($oldStatus === 'matched') {
                continue;
            }

            $db->table('payment_history')->where('id', $pid)->update([
                'bank_reconcile_status'    => 'matched',
                'bank_statement_entry_id'  => null,
                'bank_reconcile_batch_ref' => $batchRef,
                'bank_settlement_entry_id' => null,
            ]);

            $this->logBankReconcileAudit(
                $pid,
                'batch_match',
                $oldStatus,
                'matched',
                $batchRef,
                $remarks
            );

            $matchedCount++;
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to update selected payments.']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Batch reconciliation completed for ' . $matchedCount . ' payment(s).',
            'batch_ref' => $batchRef,
            'updated_count' => $matchedCount,
            'selected_count' => count($eligibleIds),
        ]);
    }

    public function bankSettlementCreate()
    {
        if (! $this->canFinance('finance.bank.audit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $paymentIds = $this->request->getPost('payment_ids');
        $remarks = trim((string) ($this->request->getPost('remarks') ?? ''));

        if (! is_array($paymentIds)) {
            $paymentIds = [];
        }

        $ids = [];
        foreach ($paymentIds as $id) {
            $v = (int) $id;
            if ($v > 0) {
                $ids[$v] = $v;
            }
        }
        $ids = array_values($ids);

        if ($ids === []) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Select at least one payment.']);
        }

        $db = \Config\Database::connect();
        $rows = $db->table('payment_history')
            ->select('id, amount, bank_reconcile_status')
            ->whereIn('id', $ids)
            ->where('payment_mode', 2)
            ->where('credit_debit', 0)
            ->get()->getResultArray();

        if ($rows === []) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'No eligible bank payments found.']);
        }

        $settlementRef = 'SE-' . date('YmdHis') . '-' . random_int(1000, 9999);
        $totalAmount = 0.0;
        $matchedCount = 0;

        foreach ($rows as $row) {
            if ((string) ($row['bank_reconcile_status'] ?? '') === 'matched') {
                continue;
            }
            $totalAmount += (float) ($row['amount'] ?? 0);
            $matchedCount++;
        }

        if ($matchedCount === 0) {
            return $this->response->setStatusCode(409)->setJSON(['status' => 0, 'message' => 'All selected payments are already matched.']);
        }

        $userName = $this->currentUserName();
        $settlementId = 0;

        $db->transStart();

        $this->bankSettlementEntryModel->insert([
            'settlement_ref' => $settlementRef,
            'settlement_date' => date('Y-m-d'),
            'payment_count' => $matchedCount,
            'total_amount' => round($totalAmount, 2),
            'reconciliation_status' => 'unmatched',
            'remarks' => $remarks !== '' ? $remarks : null,
            'created_by' => $userName,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $settlementId = (int) ($this->bankSettlementEntryModel->getInsertID() ?? 0);

        foreach ($rows as $row) {
            $pid = (int) ($row['id'] ?? 0);
            $oldStatus = (string) ($row['bank_reconcile_status'] ?? '');
            if ($oldStatus === 'matched') {
                continue;
            }

            $db->table('payment_history')->where('id', $pid)->update([
                'bank_reconcile_status'    => 'matched',
                'bank_statement_entry_id'  => null,
                'bank_reconcile_batch_ref' => $settlementRef,
                'bank_settlement_entry_id' => $settlementId,
            ]);

            $this->logBankReconcileAudit(
                $pid,
                'settlement_match',
                $oldStatus,
                'matched',
                $settlementRef,
                $remarks
            );
        }

        $db->transComplete();

        if (! $db->transStatus() || $settlementId <= 0) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to create settlement entry.']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Settlement entry created and linked to ' . $matchedCount . ' payment(s).',
            'settlement_ref' => $settlementRef,
            'settlement_id' => $settlementId,
            'updated_count' => $matchedCount,
        ]);
    }

    public function bankSettlementEntriesTable()
    {
        if (! $this->canFinanceAny(['finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $fromDate = $this->normalizeDateInput((string) ($this->request->getGet('from_date') ?? ''));
        $toDate   = $this->normalizeDateInput((string) ($this->request->getGet('to_date') ?? ''));
        $status   = trim((string) ($this->request->getGet('status') ?? 'all'));
        $bankId   = (int) ($this->request->getGet('bank_id') ?? 0);

        $db = \Config\Database::connect();
        $q = $db->table('finance_bank_settlement_entries se')
            ->select("se.*, GROUP_CONCAT(DISTINCT m.bank_name ORDER BY m.bank_name SEPARATOR ', ') AS bank_names", false)
            ->join('payment_history ph', 'ph.bank_settlement_entry_id = se.id', 'left')
            ->join('hospital_bank_payment_source s', 'CAST(s.id AS CHAR) = ph.insert_code', 'left')
            ->join('hospital_bank m', 'm.id = s.bank_id', 'left')
            ->groupBy('se.id');
        if ($fromDate !== '') {
            $q->where('se.settlement_date >=', $fromDate);
        }
        if ($toDate !== '') {
            $q->where('se.settlement_date <=', $toDate);
        }
        if ($status === 'matched' || $status === 'unmatched') {
            $q->where('se.reconciliation_status', $status);
        }
        if ($bankId > 0) {
            $q->where('m.id', $bankId);
        }

        $entries = $q->orderBy('se.settlement_date', 'DESC')
            ->orderBy('se.id', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();

        return view('finance/partials/bank_settlement_entries_table', ['entries' => $entries]);
    }

    public function bankSettlementMatchStatement()
    {
        if (! $this->canFinanceAny(['finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $settlementId = (int) ($this->request->getPost('settlement_id') ?? 0);
        $remarks = trim((string) ($this->request->getPost('remarks') ?? ''));

        if ($settlementId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Settlement ID required.']);
        }

        $entry = $this->bankSettlementEntryModel->find($settlementId);
        if (! is_array($entry)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Settlement entry not found.']);
        }

        if ((string) ($entry['reconciliation_status'] ?? '') === 'matched') {
            return $this->response->setStatusCode(409)->setJSON(['status' => 0, 'message' => 'Settlement already matched with bank statement.']);
        }

        $this->bankSettlementEntryModel->update($settlementId, [
            'reconciliation_status' => 'matched',
            'statement_matched_by' => $this->currentUserName(),
            'statement_matched_at' => date('Y-m-d H:i:s'),
            'statement_match_remarks' => $remarks !== '' ? $remarks : null,
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Settlement matched with bank statement.',
        ]);
    }

    public function bankSettlementLinkedPaymentsTable()
    {
        if (! $this->canFinanceAny(['finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $settlementId = (int) ($this->request->getGet('settlement_id') ?? 0);
        if ($settlementId <= 0) {
            return $this->response->setStatusCode(422)->setBody('Invalid settlement id.');
        }

        $db = \Config\Database::connect();
        $rows = $db->table('payment_history ph')
            ->select("ph.id, ph.payment_date, ph.amount, ph.card_tran_id, ph.bank_reconcile_status, ph.bank_reconcile_batch_ref, ph.bankcard_machine,
                COALESCE(MAX(u.username), MAX(ph.update_by)) AS accepted_by_name,
                MAX(CONCAT(COALESCE(s.pay_type,''), ' [', COALESCE(m.bank_name,''), ']')) AS bank_source_label")
            ->join('users u', 'u.id = ph.update_by_id', 'left')
            ->join('hospital_bank_payment_source s', 'CAST(s.id AS CHAR) = ph.insert_code', 'left')
            ->join('hospital_bank m', 'm.id = s.bank_id', 'left')
            ->where('ph.payment_mode', 2)
            ->where('ph.credit_debit', 0)
            ->where('ph.bank_settlement_entry_id', $settlementId)
            ->groupBy('ph.id')
            ->orderBy('ph.payment_date', 'DESC')
            ->get()->getResultArray();

        $entry = $this->bankSettlementEntryModel->find($settlementId);

        return view('finance/partials/bank_settlement_linked_payments_table', [
            'rows' => $rows,
            'entry' => $entry,
            'settlement_id' => $settlementId,
        ]);
    }

    public function bankSettlementLinkedPaymentsExport()
    {
        if (! $this->canFinanceAny(['finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $settlementId = (int) ($this->request->getGet('settlement_id') ?? 0);
        if ($settlementId <= 0) {
            return $this->response->setStatusCode(422)->setBody('Invalid settlement id.');
        }

        $entry = $this->bankSettlementEntryModel->find($settlementId);
        if (! is_array($entry)) {
            return $this->response->setStatusCode(404)->setBody('Settlement entry not found.');
        }

        $db = \Config\Database::connect();
        $rows = $db->table('payment_history ph')
            ->select("ph.id, ph.payment_date, ph.amount, ph.card_tran_id, ph.bank_reconcile_status, ph.bank_reconcile_batch_ref, ph.bankcard_machine,
                COALESCE(MAX(u.username), MAX(ph.update_by)) AS accepted_by_name,
                MAX(CONCAT(COALESCE(s.pay_type,''), ' [', COALESCE(m.bank_name,''), ']')) AS bank_source_label")
            ->join('users u', 'u.id = ph.update_by_id', 'left')
            ->join('hospital_bank_payment_source s', 'CAST(s.id AS CHAR) = ph.insert_code', 'left')
            ->join('hospital_bank m', 'm.id = s.bank_id', 'left')
            ->where('ph.payment_mode', 2)
            ->where('ph.credit_debit', 0)
            ->where('ph.bank_settlement_entry_id', $settlementId)
            ->groupBy('ph.id')
            ->orderBy('ph.payment_date', 'DESC')
            ->get()->getResultArray();

        $lines = [];
        $lines[] = $this->csvLine(['Settlement Ref', (string) ($entry['settlement_ref'] ?? '')]);
        $lines[] = $this->csvLine(['Settlement Date', (string) ($entry['settlement_date'] ?? '')]);
        $lines[] = $this->csvLine(['Payment Count', (string) ($entry['payment_count'] ?? '0')]);
        $lines[] = $this->csvLine(['Total Amount', (string) ($entry['total_amount'] ?? '0.00')]);
        $lines[] = $this->csvLine(['Remarks', (string) ($entry['remarks'] ?? '')]);
        $lines[] = '';
        $lines[] = $this->csvLine(['HMS Ref', 'Payment Date', 'Amount', 'Txn Ref / UTR', 'Channel', 'Accepted By', 'Status', 'Batch / Settlement Ref']);

        foreach ($rows as $row) {
            $sourceLabel = trim((string) ($row['bank_source_label'] ?? ''));
            if ($sourceLabel === '[]') {
                $sourceLabel = '';
            }
            $isPos = stripos($sourceLabel, 'machine') !== false || stripos((string) ($row['bankcard_machine'] ?? ''), 'machine') !== false;
            $channel = $isPos ? 'POS' : 'UPI/Direct';
            if ($sourceLabel !== '') {
                $channel .= ' - ' . $sourceLabel;
            }

            $lines[] = $this->csvLine([
                'PH-' . (int) ($row['id'] ?? 0),
                (string) ($row['payment_date'] ?? ''),
                number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                (string) ($row['card_tran_id'] ?? ''),
                $channel,
                (string) ($row['accepted_by_name'] ?? ''),
                (string) ($row['bank_reconcile_status'] ?? ''),
                (string) ($row['bank_reconcile_batch_ref'] ?? ''),
            ]);
        }

        $filename = 'settlement-linked-payments-' . $settlementId . '-' . date('Ymd_His') . '.csv';
        $csv = implode("\r\n", $lines) . "\r\n";

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    public function bankReconcileUnmatch()
    {
        if (! $this->canFinance('finance.bank.audit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $paymentId = (int) ($this->request->getPost('payment_id') ?? 0);
        $remarks = trim((string) ($this->request->getPost('remarks') ?? ''));

        if ($paymentId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Payment ID required.']);
        }

        $db = \Config\Database::connect();
        $payment = $db->table('payment_history')->where('id', $paymentId)->get()->getRowArray();
        if (! $payment) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Payment not found.']);
        }

        $oldStatus = (string) ($payment['bank_reconcile_status'] ?? '');
        $entryId = (int) ($payment['bank_statement_entry_id'] ?? 0);

        $db->table('payment_history')->where('id', $paymentId)->update([
            'bank_reconcile_status'    => null,
            'bank_statement_entry_id'  => null,
            'bank_reconcile_batch_ref' => null,
            'bank_settlement_entry_id' => null,
        ]);

        if ($entryId > 0) {
            $this->bankStatementEntryModel->update($entryId, [
                'reconciliation_status' => 'unmatched',
                'matched_payment_id'   => null,
                'matched_by'           => null,
                'matched_at'           => null,
            ]);
        }

        $this->logBankReconcileAudit(
            $paymentId,
            'unmatch',
            $oldStatus,
            'unmatched',
            null,
            $remarks
        );

        return $this->response->setJSON(['status' => 1, 'message' => 'Match reversed.']);
    }

    // ─── Bank Audit: POS Settlement ────────────────────────────────────────────

    public function bankPosSettlementsTable()
    {
        if (! $this->canFinanceAny(['finance.bank.audit', 'finance.bank.statement.update'])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $status = trim((string) ($this->request->getGet('status') ?? 'all'));

        $q = $this->bankPosSettlementModel->orderBy('settlement_date', 'DESC')->orderBy('id', 'DESC');
        if ($status !== 'all') {
            $q->where('status', $status);
        }

        $rows = $q->limit(100)->findAll();

        return view('finance/partials/bank_pos_settlements_table', ['rows' => $rows]);
    }

    public function bankPosSettlementCreate()
    {
        if (! $this->canFinance('finance.bank.audit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $settlementDate = trim((string) ($this->request->getPost('settlement_date') ?? ''));
        $terminalId     = trim((string) ($this->request->getPost('terminal_id') ?? ''));
        $terminalName   = trim((string) ($this->request->getPost('terminal_name') ?? ''));
        $settlAmount    = (float) ($this->request->getPost('settlement_amount') ?? 0);
        $remarks        = trim((string) ($this->request->getPost('remarks') ?? ''));

        if ($settlementDate === '' || $terminalId === '' || $settlAmount <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0, 'message' => 'Settlement date, terminal ID, and amount are required.',
            ]);
        }

        $db = \Config\Database::connect();

        // Prevent duplicate settlement for same date + terminal
        $existing = $this->bankPosSettlementModel
            ->where('settlement_date', $settlementDate)
            ->where('terminal_id', $terminalId)
            ->first();
        if ($existing) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 0, 'message' => 'A settlement for this terminal and date already exists.',
            ]);
        }

        // Sum all POS transactions for this terminal and date from payment_history
        $row = $db->table('payment_history')
            ->select('COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt')
            ->where('payment_mode', 2)
            ->where('credit_debit', 0)
            ->where('bankcard_machine', $terminalId)
            ->where('DATE(payment_date)', $settlementDate)
            ->get()->getRowArray();

        $systemTotal  = (float) ($row['total'] ?? 0);
        $paymentCount = (int) ($row['cnt'] ?? 0);
        $variance     = round($settlAmount - $systemTotal, 2);

        $status = 'pending';
        if ($paymentCount === 0) {
            $status = 'pending'; // no transactions found yet
        } elseif (abs($variance) <= 0.01) {
            $status = 'matched';
        } else {
            $status = 'variance';
        }

        $this->bankPosSettlementModel->insert([
            'settlement_date'   => $settlementDate,
            'terminal_id'       => $terminalId,
            'terminal_name'     => $terminalName !== '' ? $terminalName : null,
            'settlement_amount' => $settlAmount,
            'system_total'      => $systemTotal,
            'variance'          => $variance,
            'payment_count'     => $paymentCount,
            'status'            => $status,
            'created_by'        => $this->currentUserName(),
            'remarks'           => $remarks !== '' ? $remarks : null,
        ]);

        return $this->response->setJSON([
            'status'  => 1,
            'message' => 'POS settlement recorded. Status: ' . $status . '.',
            'data'    => [
                'system_total'  => $systemTotal,
                'payment_count' => $paymentCount,
                'variance'      => $variance,
                'status'        => $status,
            ],
        ]);
    }

    public function bankPosSettlementAccept()
    {
        if (! $this->canFinance('finance.bank.audit')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $id      = (int) ($this->request->getPost('settlement_id') ?? 0);
        $remarks = trim((string) ($this->request->getPost('remarks') ?? ''));

        if ($id <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Settlement ID required.']);
        }

        $row = $this->bankPosSettlementModel->find($id);
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Settlement record not found.']);
        }

        if ((string) ($row['status'] ?? '') === 'accepted') {
            return $this->response->setStatusCode(409)->setJSON(['status' => 0, 'message' => 'Already accepted.']);
        }

        $existingRemarks = (string) ($row['remarks'] ?? '');
        if ($remarks !== '') {
            $existingRemarks = $existingRemarks !== '' ? $existingRemarks . ' | ' . $remarks : $remarks;
        }

        $this->bankPosSettlementModel->update($id, [
            'status'         => 'accepted',
            'reconciled_by'  => $this->currentUserName(),
            'reconciled_at'  => date('Y-m-d H:i:s'),
            'remarks'        => $existingRemarks !== '' ? $existingRemarks : null,
        ]);

        return $this->response->setJSON(['status' => 1, 'message' => 'POS settlement accepted.']);
    }

    public function index()
    {
        if (! $this->canFinanceAny([
            'finance.workflow.view',
            'finance.cash.billing.submit',
            'finance.cash.accounts.accept',
            'finance.cash.accounts.verify',
            'finance.bank.deposit.create',
            'finance.bank.audit',
            'finance.bank.statement.update',
        ])) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/index');
    }

    public function phase2()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/phase2', $this->buildPhase2Data());
    }

    public function sectionVendorMaster()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/sections/vendor_master', [
            'vendors' => $this->fetchVendors(),
        ]);
    }

    public function sectionPurchaseOrder()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/sections/purchase_order', [
            'purchase_orders' => $this->fetchPurchaseOrders(),
            'vendor_options' => $this->vendorModel->select('id, vendor_name, vendor_code')->orderBy('vendor_name', 'ASC')->findAll(),
        ]);
    }

    public function sectionGrnEntry()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/sections/grn_entry', [
            'grns' => $this->fetchGrns(),
            'po_options' => $this->poModel->select('id, po_no')->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    public function sectionVendorInvoice()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/sections/vendor_invoice', [
            'vendor_invoices' => $this->fetchVendorInvoices(),
            'vendor_options' => $this->vendorModel->select('id, vendor_name, vendor_code')->orderBy('vendor_name', 'ASC')->findAll(),
            'po_options' => $this->poModel->select('id, po_no')->orderBy('id', 'DESC')->findAll(),
            'grn_options' => $this->grnModel->select('id, grn_no')->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    public function vendorsTable()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/vendors_table', [
            'vendors' => $this->fetchVendors(),
        ]);
    }

    public function poTable()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/po_table', [
            'purchase_orders' => $this->fetchPurchaseOrders(),
        ]);
    }

    public function grnTable()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/grn_table', [
            'grns' => $this->fetchGrns(),
        ]);
    }

    public function invoiceTable()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/invoice_table', [
            'vendor_invoices' => $this->fetchVendorInvoices(),
        ]);
    }

    public function vendorCreate()
    {
        if (! $this->canFinance('finance.vendor.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $vendorCode = strtoupper(trim((string) ($this->request->getPost('vendor_code') ?? '')));
        $vendorName = trim((string) ($this->request->getPost('vendor_name') ?? ''));

        if ($vendorCode === '' || $vendorName === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Vendor code and vendor name are required.',
            ]);
        }

        if ($this->vendorModel->where('vendor_code', $vendorCode)->countAllResults() > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Vendor code already exists.',
            ]);
        }

        $this->vendorModel->insert([
            'vendor_code' => $vendorCode,
            'vendor_name' => $vendorName,
            'contact_person' => trim((string) ($this->request->getPost('contact_person') ?? '')),
            'phone' => trim((string) ($this->request->getPost('phone') ?? '')),
            'email' => trim((string) ($this->request->getPost('email') ?? '')),
            'gst_no' => trim((string) ($this->request->getPost('gst_no') ?? '')),
            'pan_no' => trim((string) ($this->request->getPost('pan_no') ?? '')),
            'address' => trim((string) ($this->request->getPost('address') ?? '')),
            'status' => (int) ($this->request->getPost('status') ?? 1) === 1 ? 1 : 0,
            'created_by' => $this->currentUserName(),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Vendor created successfully.',
        ]);
    }

    public function poCreate()
    {
        if (! $this->canFinance('finance.po.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $poNo = strtoupper(trim((string) ($this->request->getPost('po_no') ?? '')));
        $poDate = trim((string) ($this->request->getPost('po_date') ?? ''));
        $vendorId = (int) ($this->request->getPost('vendor_id') ?? 0);

        if ($poNo === '' || $poDate === '' || $vendorId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'PO no, date and vendor are required.',
            ]);
        }

        if ($this->poModel->where('po_no', $poNo)->countAllResults() > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'PO number already exists.',
            ]);
        }

        $uploadResult = $this->handlePoDocumentUploads($poNo);
        if (! $uploadResult['ok']) {
            return $this->response->setStatusCode($uploadResult['code'])->setJSON([
                'status' => 0,
                'message' => $uploadResult['message'],
            ]);
        }
        $uploadedDocs = $uploadResult['documents'];

        $insertData = [
            'po_no' => $poNo,
            'po_date' => $poDate,
            'vendor_id' => $vendorId,
            'department' => trim((string) ($this->request->getPost('department') ?? '')),
            'amount' => (float) ($this->request->getPost('amount') ?? 0),
            'approval_status' => trim((string) ($this->request->getPost('approval_status') ?? 'draft')),
            'remarks' => trim((string) ($this->request->getPost('remarks') ?? '')),
            'created_by' => $this->currentUserName(),
        ];

        $documentPath = '';
        $documentName = '';
        if (! empty($uploadedDocs)) {
            $documentPath = (string) ($uploadedDocs[0]['file_path'] ?? '');
            $documentName = (string) ($uploadedDocs[0]['file_name'] ?? '');
        }

        if ($documentPath !== '' && $this->tableHasField('finance_purchase_orders', 'po_document_path')) {
            $insertData['po_document_path'] = $documentPath;
        }
        if ($documentName !== '' && $this->tableHasField('finance_purchase_orders', 'po_document_name')) {
            $insertData['po_document_name'] = $documentName;
        }

        $this->poModel->insert($insertData);
        $poId = (int) $this->poModel->getInsertID();

        if ($poId > 0) {
            $this->savePoDocuments($poId, $uploadedDocs);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => $documentPath !== ''
                ? 'Purchase order created successfully with document upload.'
                : 'Purchase order created successfully.',
        ]);
    }

    public function poUpdate()
    {
        if (! $this->canFinance('finance.po.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $poId = (int) ($this->request->getPost('po_id') ?? 0);
        $poNo = strtoupper(trim((string) ($this->request->getPost('po_no') ?? '')));
        $poDate = trim((string) ($this->request->getPost('po_date') ?? ''));
        $vendorId = (int) ($this->request->getPost('vendor_id') ?? 0);

        if ($poId <= 0 || $poNo === '' || $poDate === '' || $vendorId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'PO id, no, date and vendor are required.',
            ]);
        }

        $existing = $this->poModel->find($poId);
        if (! $existing) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 0,
                'message' => 'Purchase order not found.',
            ]);
        }

        $duplicate = $this->poModel
            ->where('po_no', $poNo)
            ->where('id !=', $poId)
            ->countAllResults();

        if ($duplicate > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'PO number already exists.',
            ]);
        }

        $uploadResult = $this->handlePoDocumentUploads($poNo);
        if (! $uploadResult['ok']) {
            return $this->response->setStatusCode($uploadResult['code'])->setJSON([
                'status' => 0,
                'message' => $uploadResult['message'],
            ]);
        }

        $removeDocIds = $this->request->getPost('remove_document_ids');
        if (is_array($removeDocIds) && ! empty($removeDocIds)) {
            foreach ($removeDocIds as $docId) {
                $this->removePoDocument($poId, (int) $docId);
            }
        }

        $uploadedDocs = $uploadResult['documents'];
        if (! empty($uploadedDocs)) {
            $this->savePoDocuments($poId, $uploadedDocs);
        }

        $updateData = [
            'po_no' => $poNo,
            'po_date' => $poDate,
            'vendor_id' => $vendorId,
            'department' => trim((string) ($this->request->getPost('department') ?? '')),
            'amount' => (float) ($this->request->getPost('amount') ?? 0),
            'approval_status' => trim((string) ($this->request->getPost('approval_status') ?? 'draft')),
            'remarks' => trim((string) ($this->request->getPost('remarks') ?? '')),
        ];

        $latestDoc = $this->fetchLatestPoDocument($poId);
        if ($this->tableHasField('finance_purchase_orders', 'po_document_path')) {
            $updateData['po_document_path'] = (string) ($latestDoc['file_path'] ?? '');
        }
        if ($this->tableHasField('finance_purchase_orders', 'po_document_name')) {
            $updateData['po_document_name'] = (string) ($latestDoc['file_name'] ?? '');
        }

        $this->poModel->update($poId, $updateData);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Purchase order updated successfully.',
        ]);
    }

    public function poDocuments($poId)
    {
        if (! $this->canFinance('finance.po.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $poId = (int) $poId;
        if ($poId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Invalid PO id']);
        }

        $docs = $this->fetchPoDocuments($poId);

        return $this->response->setJSON([
            'status' => 1,
            'documents' => $docs,
        ]);
    }

    private function handlePoDocumentUploads(string $poNo): array
    {
        $files = $this->request->getFileMultiple('po_documents');
        if (! is_array($files) || empty($files)) {
            $single = $this->request->getFile('po_document');
            if ($single && $single->isValid() && ! $single->hasMoved()) {
                $files = [$single];
            }
        }

        if (! is_array($files) || empty($files)) {
            return [
                'ok' => true,
                'code' => 200,
                'message' => '',
                'documents' => [],
            ];
        }

        $canSaveDocument = $this->tableHasField('finance_purchase_orders', 'po_document_path')
            && $this->tableHasField('finance_purchase_orders', 'po_document_name');

        if (! $canSaveDocument) {
            return [
                'ok' => false,
                'code' => 500,
                'message' => 'PO document columns are missing. Please run latest migrations and retry upload.',
                'documents' => [],
            ];
        }

        $targetDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'finance_po_docs';
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        if (! is_dir($targetDir)) {
            return [
                'ok' => false,
                'code' => 500,
                'message' => 'Unable to create PO document upload directory.',
                'documents' => [],
            ];
        }

        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxBytes = 5 * 1024 * 1024;
        $safePoNo = preg_replace('/[^A-Z0-9_-]/i', '_', $poNo);
        $uploadedDocs = [];

        foreach ($files as $file) {
            if (! $file || ! $file->isValid() || $file->hasMoved()) {
                continue;
            }

            $ext = strtolower((string) $file->getExtension());
            if (! in_array($ext, $allowedExt, true)) {
                return [
                    'ok' => false,
                    'code' => 422,
                    'message' => 'Invalid PO document type. Allowed: PDF, JPG, JPEG, PNG.',
                    'documents' => [],
                ];
            }

            if ((int) $file->getSize() > $maxBytes) {
                return [
                    'ok' => false,
                    'code' => 422,
                    'message' => 'Each PO document size must be 5 MB or less.',
                    'documents' => [],
                ];
            }

            $newName = $safePoNo . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            try {
                $file->move($targetDir, $newName, true);
            } catch (\Throwable $e) {
                return [
                    'ok' => false,
                    'code' => 500,
                    'message' => 'Unable to upload PO document.',
                    'documents' => [],
                ];
            }

            $uploadedDocs[] = [
                'file_name' => (string) $file->getClientName(),
                'file_path' => 'uploads/finance_po_docs/' . $newName,
            ];
        }

        return [
            'ok' => true,
            'code' => 200,
            'message' => '',
            'documents' => $uploadedDocs,
        ];
    }

    private function savePoDocuments(int $poId, array $documents): void
    {
        if ($poId <= 0 || empty($documents) || ! $this->canUsePoDocumentTable()) {
            return;
        }

        $rows = [];
        foreach ($documents as $doc) {
            $path = trim((string) ($doc['file_path'] ?? ''));
            $name = trim((string) ($doc['file_name'] ?? ''));
            if ($path === '' || $name === '') {
                continue;
            }

            $rows[] = [
                'po_id' => $poId,
                'file_name' => $name,
                'file_path' => $path,
                'uploaded_by' => $this->currentUserName(),
            ];
        }

        if (! empty($rows)) {
            $this->poDocumentModel->insertBatch($rows);
        }
    }

    private function removePoDocument(int $poId, int $docId): void
    {
        if ($poId <= 0 || $docId <= 0 || ! $this->canUsePoDocumentTable()) {
            return;
        }

        $doc = $this->poDocumentModel
            ->where('id', $docId)
            ->where('po_id', $poId)
            ->first();

        if (! $doc) {
            return;
        }

        $path = trim((string) ($doc['file_path'] ?? ''));
        if ($path !== '') {
            $fullPath = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $this->poDocumentModel->delete((int) ($doc['id'] ?? 0));
    }

    private function fetchPoDocuments(int $poId): array
    {
        if ($poId <= 0) {
            return [];
        }

        $docs = [];
        if ($this->canUsePoDocumentTable()) {
            $rows = $this->poDocumentModel
                ->where('po_id', $poId)
                ->orderBy('id', 'DESC')
                ->findAll();

            foreach ($rows as $row) {
                $path = trim((string) ($row['file_path'] ?? ''));
                if ($path === '') {
                    continue;
                }
                $docs[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'file_name' => (string) ($row['file_name'] ?? 'Document'),
                    'file_path' => $path,
                    'url' => base_url($path),
                    'removable' => true,
                ];
            }
        }

        if (empty($docs)) {
            $po = $this->poModel->find($poId);
            $legacyPath = trim((string) ($po['po_document_path'] ?? ''));
            if ($legacyPath !== '') {
                $docs[] = [
                    'id' => 0,
                    'file_name' => (string) ($po['po_document_name'] ?? 'Legacy document'),
                    'file_path' => $legacyPath,
                    'url' => base_url($legacyPath),
                    'removable' => false,
                ];
            }
        }

        return $docs;
    }

    private function fetchLatestPoDocument(int $poId): array
    {
        if ($poId <= 0) {
            return [];
        }

        if ($this->canUsePoDocumentTable()) {
            $row = $this->poDocumentModel
                ->where('po_id', $poId)
                ->orderBy('id', 'DESC')
                ->first();
            if (! empty($row)) {
                return [
                    'file_name' => (string) ($row['file_name'] ?? ''),
                    'file_path' => (string) ($row['file_path'] ?? ''),
                ];
            }
        }

        $po = $this->poModel->find($poId);
        return [
            'file_name' => (string) ($po['po_document_name'] ?? ''),
            'file_path' => (string) ($po['po_document_path'] ?? ''),
        ];
    }

    private function canUsePoDocumentTable(): bool
    {
        if (! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('finance_po_documents')) {
            return false;
        }

        return $this->tableHasField('finance_po_documents', 'po_id')
            && $this->tableHasField('finance_po_documents', 'file_name')
            && $this->tableHasField('finance_po_documents', 'file_path');
    }

    public function grnCreate()
    {
        if (! $this->canFinance('finance.grn.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $grnNo = strtoupper(trim((string) ($this->request->getPost('grn_no') ?? '')));
        $grnDate = trim((string) ($this->request->getPost('grn_date') ?? ''));
        $poId = (int) ($this->request->getPost('po_id') ?? 0);

        if ($grnNo === '' || $grnDate === '' || $poId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'GRN no, date and PO are required.',
            ]);
        }

        if ($this->grnModel->where('grn_no', $grnNo)->countAllResults() > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'GRN number already exists.',
            ]);
        }

        $this->grnModel->insert([
            'grn_no' => $grnNo,
            'grn_date' => $grnDate,
            'po_id' => $poId,
            'received_amount' => (float) ($this->request->getPost('received_amount') ?? 0),
            'received_by' => trim((string) ($this->request->getPost('received_by') ?? '')),
            'remarks' => trim((string) ($this->request->getPost('remarks') ?? '')),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'GRN created successfully.',
        ]);
    }

    public function grnPrint($grnId)
    {
        if (! $this->canFinance('finance.grn.manage')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $grnId = (int) $grnId;
        if ($grnId <= 0) {
            return $this->response->setStatusCode(422)->setBody('Invalid GRN id');
        }

        $grn = $this->grnModel
            ->select('finance_grns.*, finance_purchase_orders.po_no, finance_purchase_orders.po_date, finance_vendors.vendor_name, finance_vendors.vendor_code, finance_vendors.phone as vendor_phone, finance_vendors.address as vendor_address')
            ->join('finance_purchase_orders', 'finance_purchase_orders.id = finance_grns.po_id', 'left')
            ->join('finance_vendors', 'finance_vendors.id = finance_purchase_orders.vendor_id', 'left')
            ->where('finance_grns.id', $grnId)
            ->first();

        if (! $grn) {
            return $this->response->setStatusCode(404)->setBody('GRN not found');
        }

        return view('finance/print/grn_vendor_copy', [
            'grn' => $grn,
            'printed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function invoiceCreate()
    {
        if (! $this->canFinance('finance.invoice.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $invoiceNo = strtoupper(trim((string) ($this->request->getPost('invoice_no') ?? '')));
        $invoiceDate = trim((string) ($this->request->getPost('invoice_date') ?? ''));
        $vendorId = (int) ($this->request->getPost('vendor_id') ?? 0);

        if ($invoiceNo === '' || $invoiceDate === '' || $vendorId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Invoice no, date and vendor are required.',
            ]);
        }

        $invoiceAmount = (float) ($this->request->getPost('invoice_amount') ?? 0);
        $poId = (int) ($this->request->getPost('po_id') ?? 0) ?: null;
        $grnId = (int) ($this->request->getPost('grn_id') ?? 0) ?: null;
        $match = $this->evaluateInvoiceMatch($vendorId, $poId, $grnId, $invoiceAmount);

        $this->invoiceModel->insert([
            'invoice_no' => $invoiceNo,
            'invoice_date' => $invoiceDate,
            'vendor_id' => $vendorId,
            'po_id' => $poId,
            'grn_id' => $grnId,
            'invoice_amount' => $invoiceAmount,
            'payment_status' => trim((string) ($this->request->getPost('payment_status') ?? 'pending')),
            'match_status' => $match['match_status'],
            'variance_amount' => $match['variance_amount'],
            'match_note' => $match['match_note'],
            'is_compliance_hold' => $match['is_compliance_hold'],
            'match_checked_at' => date('Y-m-d H:i:s'),
            'remarks' => trim((string) ($this->request->getPost('remarks') ?? '')),
            'created_by' => $this->currentUserName(),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Vendor invoice created successfully. Match status: ' . $match['match_status'] . '.',
        ]);
    }

    // ── Finance Phase 2 – Pharmacy Bills (Payable / Cr. to Hospital) ─────────────

    public function sectionPharmacyBills()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/sections/pharmacy_bills', [
            'pharmacy_bills' => $this->fetchPharmacyBills(),
        ]);
    }

    public function pharmacyBillsTable()
    {
        if (! $this->canFinance('finance.access')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('finance/partials/pharmacy_bills_table', [
            'pharmacy_bills' => $this->fetchPharmacyBills(),
        ]);
    }

    public function pharmacyBillCreate()
    {
        if (! $this->canFinance('finance.invoice.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $billNo    = strtoupper(trim((string) ($this->request->getPost('bill_no') ?? '')));
        $billDate  = trim((string) ($this->request->getPost('bill_date') ?? ''));
        $pharmName = trim((string) ($this->request->getPost('pharmacy_name') ?? ''));

        if ($billNo === '' || $billDate === '' || $pharmName === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 0,
                'message' => 'Bill number, date and pharmacy name are required.',
            ]);
        }

        $billAmount = (float) ($this->request->getPost('bill_amount') ?? 0);
        $taxAmount  = (float) ($this->request->getPost('tax_amount') ?? 0);
        $netAmount  = (float) ($this->request->getPost('net_amount') ?? 0);
        if ($netAmount <= 0) {
            $netAmount = $billAmount + $taxAmount;
        }

        $this->pharmBillModel->insert([
            'bill_no'        => $billNo,
            'bill_date'      => $billDate,
            'pharmacy_name'  => $pharmName,
            'description'    => trim((string) ($this->request->getPost('description') ?? '')),
            'bill_amount'    => $billAmount,
            'tax_amount'     => $taxAmount,
            'net_amount'     => $netAmount,
            'payment_status' => 'pending',
            'paid_amount'    => 0.00,
            'remarks'        => trim((string) ($this->request->getPost('remarks') ?? '')),
            'created_by'     => $this->currentUserName(),
        ]);

        return $this->response->setJSON([
            'status'  => 1,
            'message' => 'Pharmacy bill registered. Net payable: Rs. ' . number_format($netAmount, 2) . '.',
        ]);
    }

    public function pharmacyBillSettle()
    {
        if (! $this->canFinance('finance.invoice.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 0, 'message' => 'Access denied']);
        }

        $billId      = (int) ($this->request->getPost('bill_id') ?? 0);
        $paidAmount  = (float) ($this->request->getPost('paid_amount') ?? 0);
        $paymentDate = trim((string) ($this->request->getPost('payment_date') ?? ''));
        $paymentMode = trim((string) ($this->request->getPost('payment_mode') ?? ''));
        $paymentRef  = trim((string) ($this->request->getPost('payment_ref') ?? ''));

        if ($billId <= 0 || $paidAmount <= 0 || $paymentDate === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 0,
                'message' => 'Bill, paid amount, and payment date are required.',
            ]);
        }

        $bill = $this->pharmBillModel->find($billId);
        if (! $bill) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Bill not found.']);
        }

        $netAmount     = (float) ($bill['net_amount'] ?? 0);
        $alreadyPaid   = (float) ($bill['paid_amount'] ?? 0);
        $totalPaid     = $alreadyPaid + $paidAmount;
        $paymentStatus = $totalPaid >= $netAmount ? 'paid' : ($totalPaid > 0 ? 'part_paid' : 'pending');

        $this->pharmBillModel->update($billId, [
            'paid_amount'    => min($totalPaid, $netAmount),
            'payment_status' => $paymentStatus,
            'payment_date'   => $paymentDate,
            'payment_mode'   => $paymentMode,
            'payment_ref'    => $paymentRef,
        ]);

        return $this->response->setJSON([
            'status'  => 1,
            'message' => 'Payment recorded. Status: ' . $paymentStatus . '.',
        ]);
    }

    private function fetchPharmacyBills(): array
    {
        return $this->pharmBillModel
            ->orderBy('bill_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll(200);
    }

    // ── end Pharmacy Bills ────────────────────────────────────────────────────────

    private function buildDashboardData(): array
    {
        $matchSummary = [
            'matched' => 0,
            'minor_variance' => 0,
            'mismatch' => 0,
            'hold' => 0,
        ];

        $summaryRows = $this->invoiceModel
            ->select('match_status, is_compliance_hold, COUNT(*) as total')
            ->groupBy('match_status, is_compliance_hold')
            ->findAll();

        foreach ($summaryRows as $row) {
            $status = (string) ($row['match_status'] ?? 'not_checked');
            $count = (int) ($row['total'] ?? 0);

            if ($status === 'matched') {
                $matchSummary['matched'] += $count;
            }
            if ($status === 'minor_variance') {
                $matchSummary['minor_variance'] += $count;
            }
            if ($status === 'mismatch' || $status === 'not_checked') {
                $matchSummary['mismatch'] += $count;
            }
            if ((int) ($row['is_compliance_hold'] ?? 0) === 1) {
                $matchSummary['hold'] += $count;
            }
        }

        return [
            'vendors' => $this->fetchVendors(),
            'purchase_orders' => $this->fetchPurchaseOrders(),
            'grns' => $this->fetchGrns(),
            'vendor_invoices' => $this->fetchVendorInvoices(),
            'match_summary' => $matchSummary,
            'vendor_options' => $this->vendorModel->select('id, vendor_name, vendor_code')->orderBy('vendor_name', 'ASC')->findAll(),
            'po_options' => $this->poModel->select('id, po_no')->orderBy('id', 'DESC')->findAll(),
            'grn_options' => $this->grnModel->select('id, grn_no')->orderBy('id', 'DESC')->findAll(),
        ];
    }

    private function buildPhase2Data(): array
    {
        $opdSummary = $this->buildOpdReceivableSummary();
        $ipdSummary = $this->buildIpdReceivableSummary();
        $pharmacySummary = $this->buildPharmacyReceivableSummary();
        $orgSummary = $this->buildOrganizationExposureSummary();
        $refundSummary = $this->buildRefundLifecycleSummary();

        $receivableSummary = [
            'total_billed' => $opdSummary['billed_total'] + $ipdSummary['billed_total'] + $pharmacySummary['billed_total'],
            'total_collected' => $opdSummary['collected_total'] + $ipdSummary['collected_total'] + $pharmacySummary['collected_total'],
            'total_outstanding' => $opdSummary['outstanding_total'] + $ipdSummary['outstanding_total'] + $pharmacySummary['outstanding_total'],
            'pending_invoice_count' => $opdSummary['pending_count'] + $ipdSummary['pending_count'] + $pharmacySummary['pending_count'],
            'streams' => [
                'opd' => $opdSummary,
                'ipd' => $ipdSummary,
                'pharmacy' => $pharmacySummary,
            ],
        ];

        // Pharmacy Bills payable summary (hospital owes to pharmacy entity)
        $pharmBillSummary = ['total_bills' => 0, 'total_net' => 0.0, 'total_paid' => 0.0, 'total_pending' => 0.0, 'pending_count' => 0];
        if ($this->db->tableExists('finance_pharmacy_bills')) {
            try {
                $row = $this->db->table('finance_pharmacy_bills')
                    ->select('COUNT(*) as total_bills', false)
                    ->select('COALESCE(SUM(net_amount),0) as total_net', false)
                    ->select('COALESCE(SUM(paid_amount),0) as total_paid', false)
                    ->select('COALESCE(SUM(CASE WHEN payment_status != \'paid\' THEN net_amount - paid_amount ELSE 0 END),0) as total_pending', false)
                    ->select('COALESCE(SUM(CASE WHEN payment_status != \'paid\' THEN 1 ELSE 0 END),0) as pending_count', false)
                    ->get()->getRowArray();
                $pharmBillSummary = [
                    'total_bills'   => (int) ($row['total_bills'] ?? 0),
                    'total_net'     => (float) ($row['total_net'] ?? 0),
                    'total_paid'    => (float) ($row['total_paid'] ?? 0),
                    'total_pending' => (float) ($row['total_pending'] ?? 0),
                    'pending_count' => (int) ($row['pending_count'] ?? 0),
                ];
            } catch (\Throwable $e) {
                // leave defaults
            }
        }

        return [
            'phase_started_at' => '2026-03-17',
            'phase_plan' => [
                ['phase' => 1, 'name' => 'Procurement, cash controls, payouts, compliance', 'status' => 'live'],
                ['phase' => 2, 'name' => 'AR, refund, pharmacy and org-credit consolidation', 'status' => 'started'],
                ['phase' => 3, 'name' => 'Ledger hardening, audit trails, and statutory exports', 'status' => 'planned'],
            ],
            'receivable_summary'  => $receivableSummary,
            'organization_summary' => $orgSummary,
            'refund_summary'       => $refundSummary,
            'pharm_bill_summary'   => $pharmBillSummary,
            'bridge_metrics' => [
                'charge_invoices_total'          => $opdSummary['invoice_count'],
                'refund_requests_total'          => $refundSummary['total_requests'],
                'medical_purchase_invoices_total' => $this->countTableRows('purchase_invoice'),
                'supplier_ledger_entries_total'  => $this->countTableRows('med_supplier_ledger'),
                'organization_cases_total'       => $orgSummary['case_count'],
            ],
        ];
    }

    private function buildOpdReceivableSummary(): array
    {
        $summary = $this->defaultReceivableSummary('OPD Billing');
        if (! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('invoice_master')) {
            return $summary;
        }

        $netExpr = $this->resolveNetExpression('invoice_master', 'inv', ['correction_net_amount', 'net_amount']);
        $outstandingExpr = $this->resolveOutstandingExpression('invoice_master', 'inv', ['payment_part_balance', 'payment_balance'], $netExpr, ['payment_part_received', 'payment_received']);
        $collectedExpr = $this->resolveCollectedExpression('invoice_master', 'inv', ['payment_part_received', 'payment_received'], $netExpr, $outstandingExpr);

        if ($netExpr === null || $outstandingExpr === null || $collectedExpr === null) {
            return $summary;
        }

        try {
            $builder = $this->db->table('invoice_master inv')
                ->select('COUNT(*) as invoice_count', false)
                ->select('COALESCE(SUM(' . $netExpr . '),0) as billed_total', false)
                ->select('COALESCE(SUM(' . $collectedExpr . '),0) as collected_total', false)
                ->select('COALESCE(SUM(' . $outstandingExpr . '),0) as outstanding_total', false)
                ->select('COALESCE(SUM(CASE WHEN ' . $outstandingExpr . ' > 0 THEN 1 ELSE 0 END),0) as pending_count', false);

            if ($this->tableHasField('invoice_master', 'invoice_status')) {
                $builder->where('inv.invoice_status', 1);
            }
            if ($this->tableHasField('invoice_master', 'ipd_id')) {
                $builder->groupStart()->where('inv.ipd_id', 0)->orWhere('inv.ipd_id IS NULL', null, false)->groupEnd();
            }

            $row = (array) ($builder->get()->getRowArray() ?? []);

            $summary['invoice_count'] = (int) ($row['invoice_count'] ?? 0);
            $summary['pending_count'] = (int) ($row['pending_count'] ?? 0);
            $summary['billed_total'] = (float) ($row['billed_total'] ?? 0);
            $summary['collected_total'] = (float) ($row['collected_total'] ?? 0);
            $summary['outstanding_total'] = (float) ($row['outstanding_total'] ?? 0);
        } catch (\Throwable $e) {
            return $summary;
        }

        return $summary;
    }

    private function buildIpdReceivableSummary(): array
    {
        $summary = $this->defaultReceivableSummary('IPD Billing');
        if (! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('ipd_master')) {
            return $summary;
        }

        $netExpr = $this->resolveNetExpression('ipd_master', 'ipd', ['net_amount']);
        $outstandingExpr = $this->resolveOutstandingExpression('ipd_master', 'ipd', ['balance_amount'], $netExpr, ['total_paid_amount']);

        $collectedExpr = null;
        if ($this->tableHasField('ipd_master', 'total_paid_amount') && $this->tableHasField('ipd_master', 'org_amount_recived')) {
            $collectedExpr = 'GREATEST(IFNULL(ipd.total_paid_amount,0) + IFNULL(ipd.org_amount_recived,0),0)';
        }
        if ($collectedExpr === null) {
            $collectedExpr = $this->resolveCollectedExpression('ipd_master', 'ipd', ['total_paid_amount'], $netExpr, $outstandingExpr);
        }

        if ($netExpr === null || $outstandingExpr === null || $collectedExpr === null) {
            return $summary;
        }

        try {
            $builder = $this->db->table('ipd_master ipd')
                ->select('COUNT(*) as invoice_count', false)
                ->select('COALESCE(SUM(' . $netExpr . '),0) as billed_total', false)
                ->select('COALESCE(SUM(' . $collectedExpr . '),0) as collected_total', false)
                ->select('COALESCE(SUM(' . $outstandingExpr . '),0) as outstanding_total', false)
                ->select('COALESCE(SUM(CASE WHEN ' . $outstandingExpr . ' > 0 THEN 1 ELSE 0 END),0) as pending_count', false);

            if ($this->tableHasField('ipd_master', 'net_amount')) {
                $builder->where('ipd.net_amount >', 0);
            }

            $row = (array) ($builder->get()->getRowArray() ?? []);
            $summary['invoice_count'] = (int) ($row['invoice_count'] ?? 0);
            $summary['pending_count'] = (int) ($row['pending_count'] ?? 0);
            $summary['billed_total'] = (float) ($row['billed_total'] ?? 0);
            $summary['collected_total'] = (float) ($row['collected_total'] ?? 0);
            $summary['outstanding_total'] = (float) ($row['outstanding_total'] ?? 0);
        } catch (\Throwable $e) {
            return $summary;
        }

        return $summary;
    }

    private function buildPharmacyReceivableSummary(): array
    {
        $summary = $this->defaultReceivableSummary('Pharmacy Billing');
        if (! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('invoice_med_master')) {
            return $summary;
        }

        $netExpr = $this->resolveNetExpression('invoice_med_master', 'med', ['net_amount']);
        $outstandingExpr = $this->resolveOutstandingExpression('invoice_med_master', 'med', ['payment_balance'], $netExpr, ['payment_received']);
        $collectedExpr = $this->resolveCollectedExpression('invoice_med_master', 'med', ['payment_received'], $netExpr, $outstandingExpr);

        if ($netExpr === null || $outstandingExpr === null || $collectedExpr === null) {
            return $summary;
        }

        try {
            $builder = $this->db->table('invoice_med_master med')
                ->select('COUNT(*) as invoice_count', false)
                ->select('COALESCE(SUM(' . $netExpr . '),0) as billed_total', false)
                ->select('COALESCE(SUM(' . $collectedExpr . '),0) as collected_total', false)
                ->select('COALESCE(SUM(' . $outstandingExpr . '),0) as outstanding_total', false)
                ->select('COALESCE(SUM(CASE WHEN ' . $outstandingExpr . ' > 0 THEN 1 ELSE 0 END),0) as pending_count', false);

            if ($this->tableHasField('invoice_med_master', 'invoice_status')) {
                $builder->where('med.invoice_status', 1);
            }
            if ($this->tableHasField('invoice_med_master', 'sale_return')) {
                $builder->where('med.sale_return', 0);
            }

            $row = (array) ($builder->get()->getRowArray() ?? []);
            $summary['invoice_count'] = (int) ($row['invoice_count'] ?? 0);
            $summary['pending_count'] = (int) ($row['pending_count'] ?? 0);
            $summary['billed_total'] = (float) ($row['billed_total'] ?? 0);
            $summary['collected_total'] = (float) ($row['collected_total'] ?? 0);
            $summary['outstanding_total'] = (float) ($row['outstanding_total'] ?? 0);
        } catch (\Throwable $e) {
            return $summary;
        }

        return $summary;
    }

    private function buildOrganizationExposureSummary(): array
    {
        $summary = [
            'case_count' => $this->countTableRows('organization_case_master'),
            'invoice_outstanding' => 0.0,
            'ipd_outstanding' => 0.0,
            'total_outstanding' => 0.0,
        ];

        if (method_exists($this->db, 'tableExists') && $this->db->tableExists('invoice_master') && $this->tableHasField('invoice_master', 'insurance_case_id')) {
            $outstandingExpr = $this->resolveOutstandingExpression('invoice_master', 'inv', ['payment_part_balance', 'payment_balance'], $this->resolveNetExpression('invoice_master', 'inv', ['correction_net_amount', 'net_amount']), ['payment_part_received', 'payment_received']);
            if ($outstandingExpr !== null) {
                try {
                    $row = $this->db->table('invoice_master inv')
                        ->select('COALESCE(SUM(' . $outstandingExpr . '),0) as total', false)
                        ->where('inv.insurance_case_id >', 0)
                        ->get()
                        ->getRowArray();
                    $summary['invoice_outstanding'] = (float) ($row['total'] ?? 0);
                } catch (\Throwable $e) {
                    $summary['invoice_outstanding'] = 0.0;
                }
            }
        }

        if (method_exists($this->db, 'tableExists') && $this->db->tableExists('ipd_master') && $this->tableHasField('ipd_master', 'case_id') && $this->tableHasField('ipd_master', 'balance_amount')) {
            try {
                $row = $this->db->table('ipd_master ipd')
                    ->select('COALESCE(SUM(GREATEST(IFNULL(ipd.balance_amount,0),0)),0) as total', false)
                    ->where('ipd.case_id >', 0)
                    ->get()
                    ->getRowArray();
                $summary['ipd_outstanding'] = (float) ($row['total'] ?? 0);
            } catch (\Throwable $e) {
                $summary['ipd_outstanding'] = 0.0;
            }
        }

        $summary['total_outstanding'] = $summary['invoice_outstanding'] + $summary['ipd_outstanding'];

        return $summary;
    }

    private function buildRefundLifecycleSummary(): array
    {
        $summary = [
            'total_requests' => 0,
            'total_requested_amount' => 0.0,
            'pending_count' => 0,
            'pending_amount' => 0.0,
            'completed_count' => 0,
            'completed_amount' => 0.0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0.0,
            'by_type' => [],
        ];

        if (! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('refund_order')) {
            return $summary;
        }

        $hasType = $this->tableHasField('refund_order', 'refund_type');
        $hasProcess = $this->tableHasField('refund_order', 'refund_process');
        $hasAmount = $this->tableHasField('refund_order', 'refund_amount');

        if (! $hasType || ! $hasProcess || ! $hasAmount) {
            return $summary;
        }

        try {
            $row = (array) ($this->db->table('refund_order r')
                ->select('COUNT(*) as total_requests', false)
                ->select('COALESCE(SUM(IFNULL(r.refund_amount,0)),0) as total_requested_amount', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)=0 THEN 1 ELSE 0 END),0) as pending_count', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)=0 THEN IFNULL(r.refund_amount,0) ELSE 0 END),0) as pending_amount', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)=2 THEN 1 ELSE 0 END),0) as cancelled_count', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)=2 THEN IFNULL(r.refund_amount,0) ELSE 0 END),0) as cancelled_amount', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)>0 AND IFNULL(r.refund_process,0)<>2 THEN 1 ELSE 0 END),0) as completed_count', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)>0 AND IFNULL(r.refund_process,0)<>2 THEN IFNULL(r.refund_amount,0) ELSE 0 END),0) as completed_amount', false)
                ->get()
                ->getRowArray() ?? []);

            $summary['total_requests'] = (int) ($row['total_requests'] ?? 0);
            $summary['total_requested_amount'] = (float) ($row['total_requested_amount'] ?? 0);
            $summary['pending_count'] = (int) ($row['pending_count'] ?? 0);
            $summary['pending_amount'] = (float) ($row['pending_amount'] ?? 0);
            $summary['completed_count'] = (int) ($row['completed_count'] ?? 0);
            $summary['completed_amount'] = (float) ($row['completed_amount'] ?? 0);
            $summary['cancelled_count'] = (int) ($row['cancelled_count'] ?? 0);
            $summary['cancelled_amount'] = (float) ($row['cancelled_amount'] ?? 0);

            $typeRows = $this->db->table('refund_order r')
                ->select('IFNULL(r.refund_type,0) as refund_type', false)
                ->select('COUNT(*) as total_requests', false)
                ->select('COALESCE(SUM(IFNULL(r.refund_amount,0)),0) as total_amount', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)=0 THEN 1 ELSE 0 END),0) as pending_count', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)=0 THEN IFNULL(r.refund_amount,0) ELSE 0 END),0) as pending_amount', false)
                ->select('COALESCE(SUM(CASE WHEN IFNULL(r.refund_process,0)>0 AND IFNULL(r.refund_process,0)<>2 THEN IFNULL(r.refund_amount,0) ELSE 0 END),0) as completed_amount', false)
                ->groupBy('r.refund_type')
                ->orderBy('r.refund_type', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($typeRows as $typeRow) {
                $typeCode = (int) ($typeRow['refund_type'] ?? 0);
                $summary['by_type'][] = [
                    'type_code' => $typeCode,
                    'type_label' => match ($typeCode) {
                        1 => 'OPD',
                        2 => 'Charge',
                        3 => 'IPD',
                        default => 'Other',
                    },
                    'total_requests' => (int) ($typeRow['total_requests'] ?? 0),
                    'total_amount' => (float) ($typeRow['total_amount'] ?? 0),
                    'pending_count' => (int) ($typeRow['pending_count'] ?? 0),
                    'pending_amount' => (float) ($typeRow['pending_amount'] ?? 0),
                    'completed_amount' => (float) ($typeRow['completed_amount'] ?? 0),
                ];
            }
        } catch (\Throwable $e) {
            return $summary;
        }

        return $summary;
    }

    private function defaultReceivableSummary(string $label): array
    {
        return [
            'label' => $label,
            'invoice_count' => 0,
            'pending_count' => 0,
            'billed_total' => 0.0,
            'collected_total' => 0.0,
            'outstanding_total' => 0.0,
        ];
    }

    private function resolveNetExpression(string $table, string $alias, array $preferredColumns): ?string
    {
        $column = $this->firstExistingColumn($table, $preferredColumns);
        return $column === null ? null : 'GREATEST(IFNULL(' . $alias . '.' . $column . ',0),0)';
    }

    private function resolveOutstandingExpression(string $table, string $alias, array $balanceCandidates, ?string $netExpr, array $paidCandidates): ?string
    {
        $balanceColumn = $this->firstExistingColumn($table, $balanceCandidates);
        if ($balanceColumn !== null) {
            return 'GREATEST(IFNULL(' . $alias . '.' . $balanceColumn . ',0),0)';
        }

        $paidColumn = $this->firstExistingColumn($table, $paidCandidates);
        if ($netExpr !== null && $paidColumn !== null) {
            return 'GREATEST((' . $netExpr . ') - GREATEST(IFNULL(' . $alias . '.' . $paidColumn . ',0),0),0)';
        }

        return $netExpr;
    }

    private function resolveCollectedExpression(string $table, string $alias, array $paidCandidates, ?string $netExpr, ?string $outstandingExpr): ?string
    {
        $paidColumn = $this->firstExistingColumn($table, $paidCandidates);
        if ($paidColumn !== null) {
            return 'GREATEST(IFNULL(' . $alias . '.' . $paidColumn . ',0),0)';
        }

        if ($netExpr !== null && $outstandingExpr !== null) {
            return 'GREATEST((' . $netExpr . ') - (' . $outstandingExpr . '),0)';
        }

        return null;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if ($this->tableHasField($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function tableHasField(string $table, string $field): bool
    {
        return method_exists($this->db, 'fieldExists') && $this->db->fieldExists($field, $table);
    }

    private function countTableRows(string $table): int
    {
        if (! method_exists($this->db, 'tableExists') || ! $this->db->tableExists($table)) {
            return 0;
        }

        try {
            return (int) $this->db->table($table)->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function fetchVendors(): array
    {
        return $this->vendorModel->orderBy('id', 'DESC')->findAll(12);
    }

    private function fetchPurchaseOrders(): array
    {
        $rows = $this->poModel
            ->select('finance_purchase_orders.*, finance_vendors.vendor_name, finance_vendors.vendor_code')
            ->join('finance_vendors', 'finance_vendors.id = finance_purchase_orders.vendor_id', 'left')
            ->orderBy('finance_purchase_orders.id', 'DESC')
            ->findAll(12);

        if (empty($rows)) {
            return [];
        }

        $poIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $rows);
        $docCountMap = [];
        if ($this->canUsePoDocumentTable()) {
            $countRows = $this->poDocumentModel
                ->select('po_id, COUNT(*) as cnt')
                ->whereIn('po_id', $poIds)
                ->groupBy('po_id')
                ->findAll();

            foreach ($countRows as $countRow) {
                $docCountMap[(int) ($countRow['po_id'] ?? 0)] = (int) ($countRow['cnt'] ?? 0);
            }
        }

        foreach ($rows as &$row) {
            $id = (int) ($row['id'] ?? 0);
            $row['document_count'] = (int) ($docCountMap[$id] ?? 0);
        }
        unset($row);

        return $rows;
    }

    private function fetchGrns(): array
    {
        return $this->grnModel
            ->select('finance_grns.*, finance_purchase_orders.po_no, finance_vendors.vendor_name, finance_vendors.vendor_code')
            ->join('finance_purchase_orders', 'finance_purchase_orders.id = finance_grns.po_id', 'left')
            ->join('finance_vendors', 'finance_vendors.id = finance_purchase_orders.vendor_id', 'left')
            ->orderBy('finance_grns.id', 'DESC')
            ->findAll(12);
    }

    private function fetchVendorInvoices(): array
    {
        return $this->invoiceModel
            ->select('finance_vendor_invoices.*, finance_vendors.vendor_name, finance_purchase_orders.po_no, finance_grns.grn_no')
            ->join('finance_vendors', 'finance_vendors.id = finance_vendor_invoices.vendor_id', 'left')
            ->join('finance_purchase_orders', 'finance_purchase_orders.id = finance_vendor_invoices.po_id', 'left')
            ->join('finance_grns', 'finance_grns.id = finance_vendor_invoices.grn_id', 'left')
            ->orderBy('finance_vendor_invoices.id', 'DESC')
            ->findAll(12);
    }

    private function currentUserName(): string
    {
        $user = function_exists('auth') ? auth()->user() : null;

        return trim((string) ($user->username ?? 'System'));
    }

    private function fetchCashTransactions(): array
    {
        return $this->cashTxnModel->orderBy('id', 'DESC')->findAll(15);
    }

    private function fetchScrolls(): array
    {
        return $this->scrollModel->orderBy('id', 'DESC')->findAll(15);
    }

    private function buildCashSummary(): array
    {
        $today = date('Y-m-d');

        $receiptRow = $this->cashTxnModel
            ->select('COALESCE(SUM(amount),0) AS total')
            ->where('txn_date', $today)
            ->where('txn_type', 'receipt')
            ->first();

        $disbursementRow = $this->cashTxnModel
            ->select('COALESCE(SUM(amount),0) AS total')
            ->where('txn_date', $today)
            ->where('txn_type', 'disbursement')
            ->first();

        return [
            'today_receipts' => (float) ($receiptRow['total'] ?? 0),
            'today_disbursements' => (float) ($disbursementRow['total'] ?? 0),
            'hold_count' => $this->cashTxnModel->where('is_compliance_hold', 1)->countAllResults(),
            'pending_scroll' => $this->scrollModel->whereIn('reconciliation_status', ['pending', 'submitted', 'accepted', 'received'])->countAllResults(),
        ];
    }

    private function buildScrollQueueSummary(): array
    {
        return [
            'all' => $this->scrollModel->countAllResults(),
            'pending' => $this->scrollModel->whereIn('reconciliation_status', ['pending', 'submitted'])->countAllResults(),
            'received' => $this->scrollModel->whereIn('reconciliation_status', ['received', 'accepted'])->countAllResults(),
            'deposited' => $this->scrollModel->whereIn('reconciliation_status', ['deposited', 'verified', 'matched'])->countAllResults(),
        ];
    }

    private function fetchPaymentHistoryRows(string $startAt, string $endAt, string $collectedBy = '', string $paymentMode = 'all', array $paymentIds = []): array
    {
        if (! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('payment_history')) {
            return [];
        }

        $builder = $this->db->table('payment_history p');
        $builder->select('p.id, p.payment_date, p.amount, p.payment_mode, p.payof_type, p.payof_id, p.payof_code, p.update_by_id, p.update_by')
            ->where('p.payment_date >=', $startAt)
            ->where('p.payment_date <=', $endAt)
            ->where('p.credit_debit', 0)
            ->orderBy('p.payment_date', 'ASC')
            ->orderBy('p.id', 'ASC');

        // Cash submission module is cash-only.
        if ($this->tableHasField('payment_history', 'payment_mode')) {
            $builder->where('p.payment_mode', 1);
        }

        // Only open/unsubmitted payments are selectable for new submissions.
        if ($this->tableHasField('payment_history', 'cash_submission_status')) {
            $builder->groupStart()
                ->where('p.cash_submission_status', null)
                ->orWhere('p.cash_submission_status', '')
                ->orWhere('p.cash_submission_status', 'open')
                ->groupEnd();
        }

        if (! empty($paymentIds)) {
            $builder->whereIn('p.id', $paymentIds);
        }

        if ($collectedBy !== '') {
            if (ctype_digit($collectedBy) && $this->tableHasField('payment_history', 'update_by_id')) {
                $builder->where('p.update_by_id', (int) $collectedBy);
            } elseif ($this->tableHasField('payment_history', 'update_by')) {
                $builder->where('p.update_by', $collectedBy);
            }
        }

        return $builder->get()->getResultArray();
    }

    private function summarizePaymentHistoryRows(array $rows): array
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        return [
            'total_receipts' => round($total, 2),
            'payment_count' => count($rows),
        ];
    }

    private function fetchCollectorOptions(): array
    {
        if (! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('payment_history')) {
            return [];
        }

        if ($this->tableHasField('payment_history', 'update_by_id')) {
            $rows = $this->db->table('payment_history p')
                ->select('p.update_by_id AS user_id, COALESCE(MAX(u.username), MAX(p.update_by)) AS user_name', false)
                ->join('users u', 'u.id = p.update_by_id', 'left')
                ->where('p.credit_debit', 0)
                ->groupBy('p.update_by_id')
                ->orderBy('user_name', 'ASC')
                ->get()
                ->getResultArray();

            return array_values(array_filter($rows, static function (array $row): bool {
                return (int) ($row['user_id'] ?? 0) > 0;
            }));
        }

        if ($this->tableHasField('payment_history', 'update_by')) {
            $rows = $this->db->table('payment_history')
                ->select('update_by AS user_name')
                ->where('credit_debit', 0)
                ->where('update_by !=', '')
                ->groupBy('update_by')
                ->orderBy('update_by', 'ASC')
                ->get()
                ->getResultArray();

            return array_map(static function (array $row): array {
                return [
                    'user_id' => 0,
                    'user_name' => (string) ($row['user_name'] ?? ''),
                ];
            }, $rows);
        }

        return [];
    }

    private function appendAuditRemark(string $existing, string $entry): string
    {
        $existing = trim($existing);
        $prefix = $existing === '' ? '' : $existing . ' | ';

        return $prefix . date('Y-m-d H:i') . ' - ' . $entry;
    }

    private function fetchDoctorAgreements(): array
    {
        return $this->doctorAgreementModel->orderBy('id', 'DESC')->findAll(12);
    }

    private function fetchDoctorPayouts(): array
    {
        return $this->doctorPayoutModel
            ->select('finance_doctor_payouts.*, finance_doctor_agreements.doctor_name')
            ->join('finance_doctor_agreements', 'finance_doctor_agreements.id = finance_doctor_payouts.doctor_id', 'left')
            ->orderBy('finance_doctor_payouts.id', 'DESC')
            ->findAll(20);
    }

    private function buildDoctorPayoutSummary(): array
    {
        return [
            'draft' => $this->doctorPayoutModel->where('status', 'draft')->countAllResults(),
            'finance_approved' => $this->doctorPayoutModel->where('status', 'finance_approved')->countAllResults(),
            'ceo_approved' => $this->doctorPayoutModel->where('status', 'ceo_approved')->countAllResults(),
            'paid' => $this->doctorPayoutModel->where('status', 'paid')->countAllResults(),
        ];
    }

    private function fetchBankDeposits(): array
    {
        return $this->bankDepositModel->orderBy('id', 'DESC')->findAll(20);
    }

    private function buildDepositSummary(): array
    {
        $today = date('Y-m-d');
        $todayRow = $this->bankDepositModel
            ->select('COALESCE(SUM(deposited_amount),0) AS total')
            ->where('deposit_date', $today)
            ->first();

        return [
            'today_deposit' => (float) ($todayRow['total'] ?? 0),
            'pending_count' => $this->bankDepositModel->where('reconciliation_status', 'pending')->countAllResults(),
            'matched_count' => $this->bankDepositModel->where('reconciliation_status', 'matched')->countAllResults(),
        ];
    }

    private function getPolicyLimit(string $key, float $default): float
    {
        $row = $this->policySettingModel->where('setting_key', $key)->first();
        if (! $row) {
            return $default;
        }

        $value = (float) ($row['setting_value'] ?? $default);

        return $value > 0 ? $value : $default;
    }

    private function normalizeDateInput(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : '';
    }

    private function csvLine(array $cells): string
    {
        $escaped = array_map(static function ($value): string {
            $text = str_replace('"', '""', (string) $value);

            return '"' . $text . '"';
        }, $cells);

        return implode(',', $escaped);
    }

    private function logBankReconcileAudit(
        int $paymentId,
        string $actionType,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $batchRef,
        ?string $remarks
    ): void {
        $this->bankReconcileAuditModel->insert([
            'payment_history_id' => $paymentId,
            'action_type'        => $actionType,
            'old_status'         => $oldStatus !== '' ? $oldStatus : null,
            'new_status'         => $newStatus !== '' ? $newStatus : null,
            'batch_ref'          => $batchRef,
            'remarks'            => $remarks !== '' ? $remarks : null,
            'action_by'          => $this->currentUserName(),
            'action_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    private function canFinance(string $permission): bool
    {
        $user = function_exists('auth') ? auth()->user() : null;
        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        return $user->can($permission) || $user->can('finance.*');
    }

    private function canFinanceAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->canFinance((string) $permission)) {
                return true;
            }
        }

        return false;
    }

    private function evaluateInvoiceMatch(int $vendorId, ?int $poId, ?int $grnId, float $invoiceAmount): array
    {
        $poAmount = null;
        $grnAmount = null;
        $issues = [];

        if ($poId !== null) {
            $po = $this->poModel->find($poId);
            if (! $po) {
                $issues[] = 'PO not found';
            } else {
                $poAmount = (float) ($po['amount'] ?? 0);
                if ((int) ($po['vendor_id'] ?? 0) !== $vendorId) {
                    $issues[] = 'Vendor mismatch against PO';
                }
            }
        }

        if ($grnId !== null) {
            $grn = $this->grnModel->find($grnId);
            if (! $grn) {
                $issues[] = 'GRN not found';
            } else {
                $grnAmount = (float) ($grn['received_amount'] ?? 0);
                if ($poId !== null && (int) ($grn['po_id'] ?? 0) !== $poId) {
                    $issues[] = 'GRN does not belong to PO';
                }
            }
        }

        $referenceAmount = $grnAmount ?? $poAmount;
        if ($referenceAmount === null) {
            return [
                'match_status' => 'not_checked',
                'variance_amount' => 0,
                'match_note' => 'PO/GRN missing for 3-way check',
                'is_compliance_hold' => 1,
            ];
        }

        $variance = round($invoiceAmount - $referenceAmount, 2);
        $absVariance = abs($variance);
        $status = 'matched';
        $complianceHold = 0;

        if (! empty($issues)) {
            $status = 'mismatch';
            $complianceHold = 1;
        } elseif ($absVariance > 0.01 && $absVariance <= 100) {
            $status = 'minor_variance';
        } elseif ($absVariance > 100) {
            $status = 'mismatch';
            $complianceHold = 1;
            $issues[] = 'Invoice variance exceeds tolerance';
        }

        return [
            'match_status' => $status,
            'variance_amount' => $variance,
            'match_note' => empty($issues) ? 'Auto matched against reference amount' : implode('; ', $issues),
            'is_compliance_hold' => $complianceHold,
        ];
    }
}
