<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;

class Invoice extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
        helper(['common', 'form']);
    }

    public function list_req_payment()
    {
        return view('Invoice/payment_request_list');
    }

    public function getRequestTable()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setBody('Invalid request');
        }

        $requestData = $_REQUEST;

        $columns = [
            0 => 'id',
            1 => 'org_code',
            2 => 'patient_name',
            3 => 'org_date_str',
            4 => 'payment_amount',
            5 => 'payment_process_str',
        ];

        $sqlAll = "select *,
            date_format(payment_request_datetime,'%d-%m-%Y %h:%i:%s') as org_date_str,
            (case payment_process when 0 then 'Pending' when 2 then 'Cancel' else 'Complete' end) as payment_process_str";

        $sqlCount = 'Select count(*) as no_rec ';
        $sqlFrom = ' from org_payment_request ';

        $totalSql = $sqlCount . $sqlFrom;
        $totalData = (int) ($this->db->query($totalSql)->getResult()[0]->no_rec ?? 0);
        $totalFiltered = $totalData;

        $sqlWhere = ' WHERE 1 = 1';

        if (! empty($requestData['columns'][0]['search']['value'])) {
            $idValueRaw = (string) $requestData['columns'][0]['search']['value'];
            $idValue = preg_replace('/\D+/', '', $idValueRaw);
            if ($idValue !== '') {
                $sqlWhere .= ' AND id like "%' . (int) $idValue . '%" ';
            }
        }

        if (! empty($requestData['columns'][1]['search']['value'])) {
            $sqlWhere .= " AND org_code LIKE '%" . $requestData['columns'][1]['search']['value'] . "' ";
        }

        if (! empty($requestData['columns'][2]['search']['value'])) {
            $sqlWhere .= " AND ( patient_name LIKE '" . $requestData['columns'][2]['search']['value'] . "%' ) ";
        }

        if (! empty($requestData['columns'][4]['search']['value'])) {
            $sqlWhere .= " AND payment_amount LIKE '%" . $requestData['columns'][4]['search']['value'] . "%' ";
        }

        if (! empty($requestData['columns'][5]['search']['value'])) {
            $statusValue = strtolower(trim((string) $requestData['columns'][5]['search']['value']));
            if ($statusValue === 'pending') {
                $sqlWhere .= ' AND payment_process = 0 ';
            } elseif ($statusValue === 'cancel') {
                $sqlWhere .= ' AND payment_process = 2 ';
            } elseif ($statusValue === 'complete') {
                $sqlWhere .= ' AND payment_process > 0 AND payment_process <> 2 ';
            } else {
                $sqlWhere .= " AND (case payment_process when 0 then 'Pending' when 2 then 'Cancel' else 'Complete' end) LIKE '%" . $requestData['columns'][5]['search']['value'] . "%' ";
            }
        }

        $totalFilterSql = $sqlCount . $sqlFrom . $sqlWhere;
        $totalFiltered = (int) ($this->db->query($totalFilterSql)->getResult()[0]->no_rec ?? 0);

        $orderSql = '  ORDER BY ' . $columns[$requestData['order'][0]['column']] . ' ' . $requestData['order'][0]['dir']
            . ' LIMIT ' . $requestData['start'] . ' ,' . $requestData['length'];

        $resultSql = $sqlAll . $sqlFrom . $sqlWhere . $orderSql;
        $rdata = $this->db->query($resultSql)->getResultArray();

        $output = [
            'draw' => (int) $requestData['draw'],
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => [],
        ];

        foreach ($rdata as $aRow) {
            $row = [];
            foreach ($columns as $col) {
                $row[] = $aRow[$col];
            }
            $output['data'][] = $row;
        }

        return $this->response->setJSON($output);
    }

    public function list_refund()
    {
        return view('Invoice/refund_invoice');
    }

    public function getRefundTable()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setBody('Invalid request');
        }

        $requestData = $_REQUEST;

        $columns = [
            0 => 'id',
            1 => 'refund_type_code',
            2 => 'refund_type_str',
            3 => 'patient_name',
            4 => 'approved_datetime',
            5 => 'refund_amount',
            6 => 'refund_process_str',
        ];

        $sqlAll = "select *,
            (case refund_type when 1 then 'OPD' when 2 then 'Charge' when 3 then 'Org.Inv.' else 'Other' end) as refund_type_str,
            (case refund_process when 0 then 'Pending' when 2 then 'Cancel' else 'Complete' end) as refund_process_str";

        $sqlCount = 'Select count(*) as no_rec ';
        $sqlFrom = ' from refund_order ';

        $totalSql = $sqlCount . $sqlFrom;
        $totalData = (int) ($this->db->query($totalSql)->getResult()[0]->no_rec ?? 0);
        $totalFiltered = $totalData;

        $sqlWhere = ' WHERE 1 = 1';

        if (! empty($requestData['columns'][0]['search']['value'])) {
            $idValueRaw = (string) $requestData['columns'][0]['search']['value'];
            $idValue = preg_replace('/\D+/', '', $idValueRaw);
            if ($idValue !== '') {
                $sqlWhere .= ' AND id like "%' . (int) $idValue . '%" ';
            }
        }

        if (! empty($requestData['columns'][1]['search']['value'])) {
            $sqlWhere .= " AND refund_type_code LIKE '%" . $requestData['columns'][1]['search']['value'] . "%' ";
        }

        if (! empty($requestData['columns'][2]['search']['value'])) {
            $typeValue = strtolower(trim((string) $requestData['columns'][2]['search']['value']));
            if ($typeValue === 'opd') {
                $sqlWhere .= ' AND refund_type = 1 ';
            } elseif ($typeValue === 'charge') {
                $sqlWhere .= ' AND refund_type = 2 ';
            } elseif ($typeValue === 'org.inv.' || $typeValue === 'org' || $typeValue === 'org.inv') {
                $sqlWhere .= ' AND refund_type = 3 ';
            } else {
                $sqlWhere .= " AND (case refund_type when 1 then 'OPD' when 2 then 'Charge' when 3 then 'Org.Inv.' else 'Other' end) LIKE '%" . $requestData['columns'][2]['search']['value'] . "%' ";
            }
        }

        if (! empty($requestData['columns'][3]['search']['value'])) {
            $sqlWhere .= " AND ( patient_name LIKE '%" . $requestData['columns'][3]['search']['value'] . "%') ";
        }

        if (! empty($requestData['columns'][5]['search']['value'])) {
            $sqlWhere .= " AND refund_amount LIKE '%" . $requestData['columns'][5]['search']['value'] . "%' ";
        }

        if (! empty($requestData['columns'][6]['search']['value'])) {
            $statusValue = strtolower(trim((string) $requestData['columns'][6]['search']['value']));
            if ($statusValue === 'pending') {
                $sqlWhere .= ' AND refund_process = 0 ';
            } elseif ($statusValue === 'cancel') {
                $sqlWhere .= ' AND refund_process = 2 ';
            } elseif ($statusValue === 'complete') {
                $sqlWhere .= ' AND refund_process > 0 AND refund_process <> 2 ';
            } else {
                $sqlWhere .= " AND (case refund_process when 0 then 'Pending' when 2 then 'Cancel' else 'Complete' end) LIKE '%" . $requestData['columns'][6]['search']['value'] . "%' ";
            }
        }

        $totalFilterSql = $sqlCount . $sqlFrom . $sqlWhere;
        $totalFiltered = (int) ($this->db->query($totalFilterSql)->getResult()[0]->no_rec ?? 0);

        $orderSql = '  ORDER BY ' . $columns[$requestData['order'][0]['column']] . ' ' . $requestData['order'][0]['dir']
            . ' LIMIT ' . $requestData['start'] . ' ,' . $requestData['length'];

        $resultSql = $sqlAll . $sqlFrom . $sqlWhere . $orderSql;
        $rdata = $this->db->query($resultSql)->getResultArray();

        $output = [
            'draw' => (int) $requestData['draw'],
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => [],
        ];

        foreach ($rdata as $aRow) {
            $row = [];
            foreach ($columns as $col) {
                $row[] = $aRow[$col];
            }
            $output['data'][] = $row;
        }

        return $this->response->setJSON($output);
    }

    public function refund_form(int $refundId)
    {
        $refundOrder = $this->db->table('refund_order')
            ->where('id', $refundId)
            ->get()
            ->getResult();

        if (empty($refundOrder)) {
            return $this->response->setBody('No Record Found');
        }

        return view('Invoice/refund_amount_panel', [
            'refund_order' => $refundOrder,
        ]);
    }

    public function refund_process()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $refundId = (int) $this->request->getPost('r_id');

        if ($refundId <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'showcontent' => 'Invalid refund request',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $refundOrder = $this->db->table('refund_order')
            ->where('id', $refundId)
            ->get()
            ->getResult();

        if (empty($refundOrder)) {
            return $this->response->setJSON([
                'update' => 0,
                'showcontent' => 'No Record Found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ((int) $refundOrder[0]->refund_process > 0) {
            return $this->response->setJSON([
                'update' => 0,
                'showcontent' => 'Already Done',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $refundType = (int) ($refundOrder[0]->refund_type ?? 0);
        $payofType = match ($refundType) {
            1 => '1',
            2 => '2',
            3 => '3',
            default => '0',
        };

        $user = auth()->user();
        $userId = $user->id ?? '';
        $userName = $user->username ?? $user->email ?? 'User';
        $userLabel = $userName . '[' . $userId . ']';

        $payData = [
            'payment_mode' => '1',
            'payof_type' => $payofType,
            'payof_id' => $refundOrder[0]->refund_type_id,
            'payof_code' => $refundOrder[0]->refund_type_code,
            'credit_debit' => '1',
            'amount' => $refundOrder[0]->refund_amount,
            'payment_date' => date('Y-m-d H:i:s'),
            'remark' => $refundOrder[0]->refund_type_reason,
            'update_by' => $userLabel,
            'update_by_id' => $userId,
        ];

        $paymentModel = new PaymentModel();
        $insertId = $paymentModel->insertPayment($payData);

        if ($insertId <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'showcontent' => 'Unable to process refund',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $this->db->table('refund_order')
            ->where('id', $refundId)
            ->update([
                'refund_process' => '1',
                'refund_by' => $userLabel,
                'refund_by_id' => $userId,
                'refund_datetime' => date('Y-m-d H:i:s'),
                'pay_id' => $insertId,
            ]);

        if ($refundType === 2) {
            $invoiceModel = new InvoiceModel();
            $invoiceModel->updateInvoiceFinal((int) $refundOrder[0]->refund_type_id);
        }

        return $this->response->setJSON([
            'update' => 1,
            'showcontent' => 'Refund Update',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function payment_form(int $reqId)
    {
        $reqPayment = $this->db->table('org_payment_request')
            ->where('id', $reqId)
            ->get()
            ->getResult();

        if (empty($reqPayment)) {
            return $this->response->setBody('No Record Found');
        }

        $data['req_payment_order'] = $reqPayment;

        $orgInfo = $this->db->table('organization_case_master')
            ->where('id', (int) $reqPayment[0]->org_id)
            ->get()
            ->getResult();
        $data['org_info'] = $orgInfo;

        $patientId = (int) $reqPayment[0]->patient_id;
        $personInfo = $this->db->table('patient_master')
            ->where('id', $patientId)
            ->get()
            ->getResult();
        $this->applyAge($personInfo, 'age');
        $data['person_info'] = $personInfo;

        $sql = "select s.id, s.pay_type, m.bank_name
            from hospital_bank m join hospital_bank_payment_source s on m.id=s.bank_id";
        $query = $this->db->query($sql);
        $data['bank_data'] = $query->getResult();

        if ((int) $reqPayment[0]->payment_process > 0) {
            $printUrl = base_url('Invoice/print_org_payment_invoice') . '/' . $reqPayment[0]->id;
            $showcontent = 'Amount Receipt : <a href="' . $printUrl . '" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Payment Reciept</a> ';
            return $this->response->setBody($showcontent);
        }

        return view('Invoice/payment_req_panel', $data);
    }

    public function req_payment_process()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'showcontent' => 'Invalid request',
            ]);
        }

        $mode = (string) $this->request->getPost('mode');
        $amount = (float) $this->request->getPost('amount');
        $reqPaymentId = (int) $this->request->getPost('req_payment_id');
        $cashRemark = (string) $this->request->getPost('cash_remark');

        $payBankId = (int) $this->request->getPost('cbo_pay_type');
        $inputCardMac = (string) $this->request->getPost('input_card_mac');
        $inputCardBank = (string) $this->request->getPost('input_card_bank');
        $inputCardDigit = (string) $this->request->getPost('input_card_digit');
        $inputCardTran = (string) $this->request->getPost('input_card_tran');

        $reqPayment = $this->db->table('org_payment_request')
            ->where('id', $reqPaymentId)
            ->get()
            ->getResult();

        if (empty($reqPayment)) {
            return $this->response->setJSON([
                'update' => 0,
                'showcontent' => 'No Record Found',
                'payid' => 0,
            ]);
        }

        if ((int) $reqPayment[0]->payment_process > 0) {
            $printUrl = base_url('Invoice/print_org_payment_invoice') . '/' . $reqPaymentId;
            $showcontent = 'Already Done : <a href="' . $printUrl . '" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Invoice</a> ';
            return $this->response->setJSON([
                'update' => 0,
                'showcontent' => $showcontent,
                'payid' => (int) ($reqPayment[0]->pay_id ?? 0),
            ]);
        }

        $user = auth()->user();
        $userId = $user->id ?? '';
        $userName = $user->username ?? $user->email ?? 'User';
        $userLabel = $userName . '[' . $userId . ']';

        $payData = [
            'payment_mode' => $mode,
            'payof_type' => '3',
            'payof_id' => $reqPayment[0]->org_id,
            'payof_code' => $reqPayment[0]->org_code,
            'credit_debit' => '0',
            'amount' => $reqPayment[0]->payment_amount,
            'payment_date' => date('Y-m-d H:i:s'),
            'remark' => $cashRemark,
            'update_by' => $userLabel,
            'update_by_id' => $userId,
            'pay_bank_id' => $payBankId,
            'card_bank' => $inputCardMac,
            'card_remark' => $inputCardBank,
            'cust_card' => $inputCardDigit,
            'card_tran_id' => $inputCardTran,
        ];

        $paymentModel = new PaymentModel();
        $insertId = $paymentModel->insertPayment($payData);

        if ($insertId > 0) {
            $this->db->table('org_payment_request')
                ->where('id', $reqPaymentId)
                ->update([
                    'payment_process' => '1',
                    'payment_accept_by' => $userLabel,
                    'payment_accept_by_id' => $userId,
                    'payment_datetime' => date('Y-m-d H:i:s'),
                    'pay_id' => $insertId,
                ]);

            $printUrl = base_url('Invoice/print_org_payment_invoice') . '/' . $reqPaymentId;
            $showcontent = 'Amount Receipt : <a href="' . $printUrl . '" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Invoice</a> ';

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => $showcontent,
                'payid' => $insertId,
            ]);
        }

        return $this->response->setJSON([
            'update' => 0,
            'showcontent' => 'Unable to process payment.',
            'payid' => 0,
        ]);
    }

    public function print_org_payment_invoice(int $reqId)
    {
        $reqPayment = $this->db->table('org_payment_request')
            ->where('id', $reqId)
            ->get()
            ->getResult();

        if (empty($reqPayment)) {
            return $this->response->setStatusCode(404)->setBody('No Record Found');
        }

        $data['req_payment_order'] = $reqPayment;

        $orgInfo = $this->db->table('organization_case_master')
            ->where('id', (int) $reqPayment[0]->org_id)
            ->get()
            ->getResult();
        $data['org_info'] = $orgInfo;

        $patientId = (int) $reqPayment[0]->patient_id;
        $patientMaster = $this->db->table('patient_master')
            ->where('id', $patientId)
            ->get()
            ->getResult();
        $this->applyAge($patientMaster, 'age');
        $data['patient_master'] = $patientMaster;

        $data['req_payment_order'][0]->payment_date_str = date('d-m-Y H:i:s', strtotime($reqPayment[0]->payment_datetime ?? 'now'));

        return view('Invoice/Invoice_org_payment', $data);
    }

    private function applyAge(array $rows, string $field = 'age'): void
    {
        foreach ($rows as $row) {
            $row->{$field} = get_age_1(
                $row->dob ?? null,
                $row->age ?? '',
                $row->age_in_month ?? '',
                $row->estimate_dob ?? ''
            );
        }
    }
}
