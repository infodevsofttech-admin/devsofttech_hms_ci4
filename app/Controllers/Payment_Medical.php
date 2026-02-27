<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Payment_Medical extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    private function ensurePharmacyAccess()
    {
        $user = service('auth')->user();

        $allowed = false;
        if ($user && method_exists($user, 'can')) {
            $allowed = $user->can('pharmacy.access') || $user->can('billing.access');
        }

        if (! $allowed && $user && method_exists($user, 'inGroup')) {
            $allowed = $user->inGroup('superadmin', 'admin', 'developer');
        }

        if ($allowed) {
            return null;
        }

        return $this->response
            ->setStatusCode(403)
            ->setBody('<div class="alert alert-danger m-3">Access denied for Pharmacy module.</div>');
    }

    private function firstColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function rowValue(array $row, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        return $default;
    }

    private function buildBankSourceLabel(array $row): string
    {
        $sourceText = trim((string) $this->rowValue($row, [
            'pay_type',
            'source_name',
            'payment_source',
            'source',
            'name',
            'title',
        ], ''));

        $bankText = trim((string) $this->rowValue($row, [
            'bank_name',
            'bank',
            'bank_account_name',
            'account_name',
        ], ''));

        if ($sourceText !== '' && $bankText !== '') {
            return $sourceText . ' [' . $bankText . ']';
        }
        if ($sourceText !== '') {
            return $sourceText;
        }
        if ($bankText !== '') {
            return $bankText;
        }

        $id = (int) ($row['id'] ?? 0);
        return $id > 0 ? ('Source #' . $id) : 'Default Bank Source';
    }

    private function getActiveUsers(): array
    {
        if (! $this->db->tableExists('users')) {
            return [];
        }

        $fields = $this->db->getFieldNames('users') ?? [];
        $builder = $this->db->table('users');

        $select = ['id'];
        foreach (['username', 'first_name', 'last_name', 'active'] as $f) {
            if (in_array($f, $fields, true)) {
                $select[] = $f;
            }
        }

        $builder->select(implode(',', array_unique($select)));
        if (in_array('active', $fields, true)) {
            $builder->where('active', 1);
        }

        return $builder->orderBy('id', 'DESC')->get()->getResultArray();
    }

    private function getMedicalBankSources(): array
    {
        $sourceTable = null;
        if ($this->db->tableExists('medical_bank_source')) {
            $sourceTable = 'medical_bank_source';
        } elseif ($this->db->tableExists('medical_bank_payment_source')) {
            $sourceTable = 'medical_bank_payment_source';
        }

        if ($sourceTable === null) {
            return [];
        }

        $sourceFields = $this->db->getFieldNames($sourceTable) ?? [];
        $bankFk = $this->firstColumn($sourceFields, ['bank_id', 'medical_bank_id', 'bankid']);

        $builder = $this->db->table($sourceTable . ' s')->select('s.*');

        if ($this->db->tableExists('medical_bank') && $bankFk !== null) {
            $bankFields = $this->db->getFieldNames('medical_bank') ?? [];
            $bankPk = $this->firstColumn($bankFields, ['id', 'bank_id']);
            $bankNameCol = $this->firstColumn($bankFields, ['bank_name', 'name', 'bank']);

            if ($bankPk !== null && $bankNameCol !== null) {
                $builder
                    ->select('ifnull(m.' . $bankNameCol . ', "") as bank_name', false)
                    ->join('medical_bank m', 'm.' . $bankPk . '=s.' . $bankFk, 'left');
            } else {
                $builder->select('"" as bank_name', false);
            }
        } else {
            $builder->select('"" as bank_name', false);
        }

        $list = $builder->orderBy('s.id', 'ASC')->get()->getResultArray();

        foreach ($list as &$row) {
            $row['_label'] = $this->buildBankSourceLabel($row);
        }
        unset($row);

        return $list;
    }

    private function getActorName(): string
    {
        $user = service('auth')->user();
        if (! $user) {
            return 'system';
        }

        $parts = [];
        if (isset($user->first_name) && (string) $user->first_name !== '') {
            $parts[] = (string) $user->first_name;
        }
        if (isset($user->last_name) && (string) $user->last_name !== '') {
            $parts[] = (string) $user->last_name;
        }

        $name = trim(implode(' ', $parts));
        if ($name === '' && isset($user->username) && (string) $user->username !== '') {
            $name = (string) $user->username;
        }
        if ($name === '' && isset($user->email) && (string) $user->email !== '') {
            $name = (string) $user->email;
        }

        $id = isset($user->id) ? (string) $user->id : '';
        return $id !== '' ? ($name . '[' . $id . ']') : $name;
    }

    private function appendMedicalInvoiceLog(int $invoiceId, string $message): void
    {
        if ($invoiceId <= 0 || trim($message) === '') {
            return;
        }

        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->fieldExists('log', 'invoice_med_master')) {
            return;
        }

        $invoice = $this->db->table('invoice_med_master')->select('id,log')->where('id', $invoiceId)->get()->getRowArray();
        if (! $invoice) {
            return;
        }

        $oldLog = trim((string) ($invoice['log'] ?? ''));
        $newLine = trim($message) . ' | By ' . $this->getActorName() . '[' . date('Y-m-d H:i:s') . ']';
        $merged = $oldLog === '' ? $newLine : ($oldLog . PHP_EOL . $newLine);

        $this->db->table('invoice_med_master')->where('id', $invoiceId)->update([
            'log' => $merged,
        ]);
    }

    private function getMedicalInvoiceIdByPayId(int $payId): int
    {
        if ($payId <= 0 || ! $this->db->tableExists('payment_history_medical')) {
            return 0;
        }

        $fields = $this->db->getFieldNames('payment_history_medical') ?? [];
        $invoiceCol = null;
        foreach (['Medical_invoice_id', 'medical_invoice_id'] as $candidate) {
            if (in_array($candidate, $fields, true)) {
                $invoiceCol = $candidate;
                break;
            }
        }

        if ($invoiceCol === null) {
            return 0;
        }

        $row = $this->db->table('payment_history_medical')->select($invoiceCol)->where('id', $payId)->get()->getRowArray();
        return (int) ($row[$invoiceCol] ?? 0);
    }

    private function resolvePaymentLogTable(): ?string
    {
        if ($this->db->tableExists('paymentmedical_history_log')) {
            return 'paymentmedical_history_log';
        }

        if ($this->db->tableExists('payment_history_log')) {
            return 'payment_history_log';
        }

        return null;
    }

    private function insertPaymentLog(int $payId, string $updateType, string $updateBy, string $updateLog = '', string $updateRemark = ''): void
    {
        $logTable = $this->resolvePaymentLogTable();
        if ($logTable === null) {
            return;
        }

        $fields = $this->db->getFieldNames($logTable) ?? [];
        $insert = [];

        if (in_array('pay_id', $fields, true)) {
            $insert['pay_id'] = $payId;
        }
        if (in_array('update_type', $fields, true)) {
            $insert['update_type'] = $updateType;
        }
        if (in_array('update_by', $fields, true)) {
            $insert['update_by'] = $updateBy;
        }
        if (in_array('update_log', $fields, true) && $updateLog !== '') {
            $insert['update_log'] = $updateLog;
        }
        if (in_array('update_remark', $fields, true)) {
            if ($updateRemark !== '') {
                $insert['update_remark'] = $updateRemark;
            } elseif (! in_array('update_log', $fields, true) && $updateLog !== '') {
                $insert['update_remark'] = $updateLog;
            }
        }
        if (in_array('insert_datetime', $fields, true)) {
            $insert['insert_datetime'] = date('Y-m-d H:i:s');
        }

        if ($insert !== []) {
            $this->db->table($logTable)->insert($insert);
        }
    }

    private function renderPaymentRecord(int $recNo): string
    {
        if (! $this->db->tableExists('payment_history_medical')) {
            return 'No Record Found';
        }

        $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
        $idCol = $this->firstColumn($payFields, ['id']);
        if (! $idCol) {
            return 'No Record Found';
        }

        $payment = $this->db->table('payment_history_medical')
            ->where($idCol, $recNo)
            ->get()
            ->getRowArray();

        if (! $payment) {
            return 'No Record Found';
        }

        $customerType = (int) ($this->rowValue($payment, ['Customerof_type', 'customerof_type'], 0));
        $medicalInvoiceId = (int) ($this->rowValue($payment, ['Medical_invoice_id', 'medical_invoice_id'], 0));
        $ipdId = (int) ($this->rowValue($payment, ['ipd_id', 'IPD_ID'], 0));
        $paymentMode = (int) ($this->rowValue($payment, ['payment_mode'], 1));
        $payBankId = (int) ($this->rowValue($payment, ['pay_bank_id', 'bank_id'], 0));
        $amount = (float) ($this->rowValue($payment, ['amount'], 0));
        $creditDebit = (int) ($this->rowValue($payment, ['credit_debit'], 0));

        $invoiceNo = '';
        $invoiceToName = '';
        $invType = '';

        if (($customerType === 1 || $customerType === 3) && $this->db->tableExists('invoice_med_master')) {
            $inv = $this->db->table('invoice_med_master')->where('id', $medicalInvoiceId)->get()->getRowArray();
            if ($inv) {
                $invoiceToName = (string) ($inv['inv_name'] ?? '');
                $invoiceNo = (string) ($inv['inv_med_code'] ?? '');
                $invType = $customerType === 1 ? 'OPD' : 'CASH';
            }
        } elseif ($customerType === 2 && $this->db->tableExists('ipd_master')) {
            $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get()->getRowArray();
            if ($ipd) {
                $invoiceToName = (string) ($ipd['P_name'] ?? ($ipd['p_name'] ?? ''));
                $invoiceNo = (string) ($ipd['ipd_code'] ?? '');
                $invType = 'IPD Medical Bill';
            }
        }

        $amountStr = $creditDebit === 0 ? $amount : ($amount * -1);

        return view('medical/payment_edit', [
            'payment_history' => $payment,
            'all_user_list' => $this->getActiveUsers(),
            'invoice_no' => $invoiceNo,
            'invoice_to_name' => $invoiceToName,
            'inv_Type' => $invType,
            'rec_no' => $recNo,
            'amount_str' => $amountStr,
            'payment_mode' => $paymentMode,
            'pay_bank_id' => $payBankId,
            'bank_sources' => $this->getMedicalBankSources(),
        ]);
    }

    public function index()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/payment_search');
    }

    public function payment_record()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $raw = (string) ($this->request->getPost('txtsearch') ?? '');
        $recNo = (int) preg_replace('/\D+/', '', trim($raw));

        if ($recNo <= 0) {
            return $this->response->setBody('No Record Found');
        }

        return $this->response->setBody($this->renderPaymentRecord($recNo));
    }

    public function change_to_bank()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $payId = (int) ($this->request->getPost('pay_id') ?? 0);
        if ($payId <= 0 || ! $this->db->tableExists('payment_history_medical')) {
            return $this->response->setBody('No Record Found');
        }

        $oldRow = $this->db->table('payment_history_medical')->where('id', $payId)->get()->getRowArray();
        if (! $oldRow) {
            return $this->response->setBody('No Record Found');
        }

        $fields = $this->db->getFieldNames('payment_history_medical') ?? [];
        $payTypeId = (int) ($this->request->getPost('cbo_pay_type') ?? 0);
        $cardTranId = trim((string) ($this->request->getPost('input_card_tran') ?? ''));
        $sourceLabel = '';

        if ($payTypeId > 0) {
            foreach ($this->getMedicalBankSources() as $source) {
                if ((int) ($source['id'] ?? 0) === $payTypeId) {
                    $sourceLabel = (string) ($source['_label'] ?? $this->buildBankSourceLabel($source));
                    break;
                }
            }
        }

        $update = [];

        if (in_array('payment_mode', $fields, true)) {
            $update['payment_mode'] = 2;
        }
        if (in_array('payment_mode_desc', $fields, true)) {
            $update['payment_mode_desc'] = $sourceLabel !== '' ? $sourceLabel : 'Bank/Online';
        }
        if (in_array('pay_bank_id', $fields, true)) {
            $update['pay_bank_id'] = $payTypeId;
        }
        if (in_array('bank_id', $fields, true) && ! in_array('pay_bank_id', $fields, true)) {
            $update['bank_id'] = $payTypeId;
        }
        if (in_array('card_bank', $fields, true)) {
            $update['card_bank'] = $sourceLabel;
        }
        if (in_array('cust_card', $fields, true)) {
            $update['cust_card'] = '';
        }
        if (in_array('card_remark', $fields, true)) {
            $update['card_remark'] = '';
        }
        if (in_array('card_tran_id', $fields, true)) {
            $update['card_tran_id'] = $cardTranId;
        }

        if ($update !== []) {
            $this->db->table('payment_history_medical')->where('id', $payId)->update($update);
            $oldMode = ((int) ($oldRow['payment_mode'] ?? 1) === 1) ? 'CASH' : 'BANK';
            $newMode = 'BANK';
            $logText = 'Payment mode Change ' . $oldMode . ' to ' . $newMode;
            if ($sourceLabel !== '') {
                $logText .= ' [' . $sourceLabel . ']';
            }
            if ($cardTranId !== '') {
                $logText .= ' TranID:' . $cardTranId;
            }

            $this->insertPaymentLog($payId, '2', $this->getActorName(), $logText);
            $this->appendMedicalInvoiceLog($this->getMedicalInvoiceIdByPayId($payId), 'Payment Update: ' . $logText);
        }

        return $this->response->setBody($this->renderPaymentRecord($payId));
    }

    public function change_to_cash()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $payId = (int) ($this->request->getPost('pay_id') ?? 0);
        if ($payId <= 0 || ! $this->db->tableExists('payment_history_medical')) {
            return $this->response->setBody('No Record Found');
        }

        $oldRow = $this->db->table('payment_history_medical')->where('id', $payId)->get()->getRowArray();
        if (! $oldRow) {
            return $this->response->setBody('No Record Found');
        }

        $fields = $this->db->getFieldNames('payment_history_medical') ?? [];
        $update = [];

        if (in_array('payment_mode', $fields, true)) {
            $update['payment_mode'] = 1;
        }
        if (in_array('payment_mode_desc', $fields, true)) {
            $update['payment_mode_desc'] = 'Cash';
        }
        if (in_array('pay_bank_id', $fields, true)) {
            $update['pay_bank_id'] = 0;
        }
        if (in_array('bank_id', $fields, true) && ! in_array('pay_bank_id', $fields, true)) {
            $update['bank_id'] = 0;
        }
        if (in_array('card_bank', $fields, true)) {
            $update['card_bank'] = '';
        }
        if (in_array('cust_card', $fields, true)) {
            $update['cust_card'] = '';
        }
        if (in_array('card_remark', $fields, true)) {
            $update['card_remark'] = '';
        }
        if (in_array('card_tran_id', $fields, true)) {
            $update['card_tran_id'] = '';
        }

        if ($update !== []) {
            $this->db->table('payment_history_medical')->where('id', $payId)->update($update);
            $oldMode = ((int) ($oldRow['payment_mode'] ?? 1) === 1) ? 'CASH' : 'BANK';
            $logText = 'Payment mode Change ' . $oldMode . ' to CASH';
            $this->insertPaymentLog($payId, '1', $this->getActorName(), $logText);
            $this->appendMedicalInvoiceLog($this->getMedicalInvoiceIdByPayId($payId), 'Payment Update: ' . $logText);
        }

        return $this->response->setBody($this->renderPaymentRecord($payId));
    }

    public function update_amount()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $payId = (int) ($this->request->getPost('pay_id') ?? 0);
        $changeValue = (float) ($this->request->getPost('change_value') ?? 0);

        if ($payId <= 0 || $changeValue <= 0 || ! $this->db->tableExists('payment_history_medical')) {
            return $this->response->setBody('No Record Found');
        }

        $oldRow = $this->db->table('payment_history_medical')->where('id', $payId)->get()->getRowArray();
        if (! $oldRow) {
            return $this->response->setBody('No Record Found');
        }

        $oldAmt = (float) ($oldRow['amount'] ?? 0);

        $this->db->table('payment_history_medical')->where('id', $payId)->update([
            'amount' => $changeValue,
        ]);

        $logText = 'Amount value Change ' . number_format($oldAmt, 2, '.', '')
            . ' to '
            . number_format($changeValue, 2, '.', '');

        $this->insertPaymentLog($payId, '3', $this->getActorName(), $logText);
        $this->appendMedicalInvoiceLog($this->getMedicalInvoiceIdByPayId($payId), 'Payment Update: ' . $logText);

        return $this->response->setBody($this->renderPaymentRecord($payId));
    }

    public function change_user()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $payId = (int) ($this->request->getPost('pay_id') ?? 0);
        $userId = (int) ($this->request->getPost('user_list') ?? 0);

        if ($payId <= 0 || $userId <= 0 || ! $this->db->tableExists('payment_history_medical') || ! $this->db->tableExists('users')) {
            return $this->response->setBody('No Record Found');
        }

        $payment = $this->db->table('payment_history_medical')->where('id', $payId)->get()->getRowArray();
        $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();

        if (! $payment || ! $user) {
            return $this->response->setBody('No Record Found');
        }

        $userLabel = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        if ($userLabel === '') {
            $userLabel = (string) ($user['username'] ?? ('User ' . $userId));
        }

        $fields = $this->db->getFieldNames('payment_history_medical') ?? [];
        $update = [];

        if (in_array('update_by_id', $fields, true)) {
            $update['update_by_id'] = $userId;
        }
        if (in_array('update_by', $fields, true)) {
            $insertTime = (string) ($payment['insert_time'] ?? ($payment['insert_datetime'] ?? ''));
            $update['update_by'] = $userLabel . '[' . $insertTime . '][' . $userId . ']';
        }

        if ($update !== []) {
            $this->db->table('payment_history_medical')->where('id', $payId)->update($update);
            $newUserText = $userLabel . '[' . $userId . ']';
            $oldUserText = (string) ($payment['update_by'] ?? '');
            if ($oldUserText === '') {
                $oldUserText = '-';
            }

            $logText = 'Update User Change ' . $oldUserText . ' to ' . $newUserText;
            $this->insertPaymentLog(
                $payId,
                '3',
                $this->getActorName(),
                $logText,
                $logText
            );
            $this->appendMedicalInvoiceLog($this->getMedicalInvoiceIdByPayId($payId), 'Payment Update: ' . $logText);
        }

        return $this->response->setBody($this->renderPaymentRecord($payId));
    }

    private function parseLegacyDateRange(string $dateRange): array
    {
        $dateRange = trim($dateRange);
        if ($dateRange === '') {
            $today = date('Y-m-d');
            return [$today, $today];
        }

        $parts = explode('S', $dateRange);
        $fromRaw = str_replace('T', ' ', trim((string) ($parts[0] ?? '')));
        $toRaw = str_replace('T', ' ', trim((string) ($parts[1] ?? ($parts[0] ?? ''))));

        $from = date('Y-m-d', strtotime($fromRaw ?: 'today'));
        $to = date('Y-m-d', strtotime($toRaw ?: $from));

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    public function payment_log()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/payment_log', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function payment_log_data()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $dateRange = trim((string) ($this->request->getPost('opd_date_range') ?? $this->request->getGet('opd_date_range') ?? ''));
        [$dateFrom, $dateTo] = $this->parseLegacyDateRange($dateRange);

        $logTable = $this->resolvePaymentLogTable();

        if (! $this->db->tableExists('payment_history_medical') || $logTable === null) {
            return view('medical/payment_log_data', [
                'rows' => [],
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        $pFields = $this->db->getFieldNames('payment_history_medical') ?? [];
        $lFields = $this->db->getFieldNames($logTable) ?? [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            $fieldMap = [];
            foreach ($fields as $field) {
                $fieldMap[strtolower((string) $field)] = (string) $field;
            }

            foreach ($candidates as $candidate) {
                $key = strtolower((string) $candidate);
                if (isset($fieldMap[$key])) {
                    return $fieldMap[$key];
                }
            }

            return null;
        };

        $customerTypeField = $resolveField($pFields, ['Customerof_type', 'customerof_type']);
        $invoiceCodeField = $resolveField($pFields, ['Medical_invoice_code', 'medical_invoice_code']);
        $ipdIdField = $resolveField($pFields, ['ipd_id', 'IPD_ID']);
        $insertTimeField = $resolveField($pFields, ['insert_time', 'insert_datetime', 'created_at']);

        $logInsertField = $resolveField($lFields, ['insert_datetime', 'insert_time', 'created_at']);
        $updateByField = $resolveField($lFields, ['update_by']);
        $updateTypeField = $resolveField($lFields, ['update_type']);
        $updateLogField = $resolveField($lFields, ['update_log']);
        $updateRemarkField = $resolveField($lFields, ['update_remark']);
        $logIdField = $resolveField($lFields, ['id']);

        $customerTypeCol = $customerTypeField !== null ? ('p.' . $customerTypeField) : '0';
        $invoiceCodeCol = $invoiceCodeField !== null ? ('p.' . $invoiceCodeField) : "'-'";
        $ipdIdCol = $ipdIdField !== null ? ('p.' . $ipdIdField) : "'-'";
        $insertTimeCol = $insertTimeField !== null ? ('p.' . $insertTimeField) : 'null';

        $logInsertCol = $logInsertField !== null ? ('l.' . $logInsertField) : 'null';
        $updateByCol = $updateByField !== null ? ('l.' . $updateByField) : "'-'";
        $updateTypeCol = $updateTypeField !== null ? ('l.' . $updateTypeField) : '0';
        if ($updateLogField !== null && $updateRemarkField !== null) {
            $updateLogCol = 'COALESCE(NULLIF(l.' . $updateLogField . ',\'\'), l.' . $updateRemarkField . ', \'\')';
        } elseif ($updateLogField !== null) {
            $updateLogCol = 'l.' . $updateLogField;
        } elseif ($updateRemarkField !== null) {
            $updateLogCol = 'l.' . $updateRemarkField;
        } else {
            $updateLogCol = "''";
        }

        $select = [
            'p.id as id',
            'ifnull(' . $invoiceCodeCol . ", '-') as Inv_code",
            'ifnull(' . $insertTimeCol . ', null) as insert_time',
            'ifnull(' . $logInsertCol . ', null) as log_insert',
            'ifnull(' . $updateByCol . ", '-') as update_by",
            'ifnull(' . $updateTypeCol . ', 0) as update_type',
            'ifnull(' . $updateLogCol . ", '') as update_log",
            'ifnull(p.amount,0) as amount',
            "(case {$customerTypeCol} when 1 then ifnull({$invoiceCodeCol}, '-') when 2 then ifnull({$ipdIdCol}, '-') when 3 then ifnull({$invoiceCodeCol}, '-') else 'Unknown' end) as invoice_code",
            "(case {$customerTypeCol} when 1 then 'UHID' when 2 then 'IPD' when 3 then 'CASH' else 'Unknown' end) as bill_type",
            "(case {$updateTypeCol} when 3 then 'Change Amount' when 2 then 'Cash to Bank' when 1 then 'Bank to Cash' else 'Unknown' end) as log_type",
        ];

        $builder = $this->db->table('payment_history_medical p')
            ->select(implode(',', $select), false)
            ->join($logTable . ' l', 'p.id=l.pay_id', 'inner');

        if ($logInsertField !== null) {
            $fromEsc = $this->db->escape($dateFrom);
            $toEsc = $this->db->escape($dateTo);
            $builder->where("DATE(l.{$logInsertField}) BETWEEN {$fromEsc} AND {$toEsc}", null, false);
        }

        if ($logIdField !== null) {
            $builder->orderBy('l.' . $logIdField, 'DESC');
        } elseif ($logInsertField !== null) {
            $builder->orderBy('l.' . $logInsertField, 'DESC');
        } else {
            $builder->orderBy('p.id', 'DESC');
        }

        $rows = $builder->get()->getResultArray();

        if ($rows === []) {
            $fallbackBuilder = $this->db->table('payment_history_medical p')
                ->select(implode(',', $select), false)
                ->join($logTable . ' l', 'p.id=l.pay_id', 'inner');

            if ($logIdField !== null) {
                $fallbackBuilder->orderBy('l.' . $logIdField, 'DESC');
            } elseif ($logInsertField !== null) {
                $fallbackBuilder->orderBy('l.' . $logInsertField, 'DESC');
            } else {
                $fallbackBuilder->orderBy('p.id', 'DESC');
            }

            $rows = $fallbackBuilder->limit(500)->get()->getResultArray();
        }

        return view('medical/payment_log_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
