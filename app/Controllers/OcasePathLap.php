<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;

class OcasePathLap extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
        helper(['common', 'form']);
    }

    public function list_pathtest_bytype()
    {
        $typeId = (int) $this->request->getPost('itype_idv');
        $insId = (int) $this->request->getPost('ins_id');

        if ($insId > 1) {
            $sql = 'select id,Concat(idesc,\' : [\',amount1,\']\',if(hc_insurance_id=0,\'\',\'-INS\')) as sdesc from v_hc_items_with_insurance '
                . 'where itype=' . $typeId . ' and hc_insurance_id in (0,' . $insId . ') order by idesc';
        } else {
            $sql = 'select id,Concat(idesc,\' : [\',amount,\']\') as sdesc from hc_items where itype=' . $typeId . ' order by idesc';
        }

        $labitem = $this->db->query($sql)->getResult();

        $html = '<div class="form-group">\n<label>Charge Code</label>';
        $html .= '<select class="form-control" id="itype_name_id" name="itype_name_id">';
        foreach ($labitem as $row) {
            $html .= '<option value=' . $row->id . '>' . $row->sdesc . '</option>';
        }
        $html .= '</select></div>';

        return $this->response->setBody($html);
    }

    public function get_echs_id()
    {
        $srNo = (string) $this->request->getPost('input_charge_code');
        $typeId = (int) $this->request->getPost('itype_idv');

        $row = $this->db->table('hc_items')
            ->where('itype', $typeId)
            ->where('echs_sr_no', $srNo)
            ->get()
            ->getRow();

        return $this->response->setBody($row ? (string) $row->id : '0');
    }

    public function update_refer_doc()
    {
        $invoiceId = (int) $this->request->getPost('inv_id');
        $docId = (int) $this->request->getPost('doc_id');
        $referName = '';

        if ($docId < 1) {
            $referName = (string) $this->request->getPost('refername');
        } else {
            $doc = $this->db->table('doctor_master')->where('id', $docId)->get()->getRow();
            $referName = $doc->p_fname ?? '';
        }

        $this->db->table('invoice_master')
            ->where('id', $invoiceId)
            ->update([
                'refer_by_id' => $docId,
                'refer_by_other' => $referName,
            ]);

        return $this->response->setStatusCode(204);
    }

    public function showinvoice(int $invoiceId)
    {
        $sql = 'select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=' . $invoiceId;
        $data['invoiceDetails'] = $this->db->query($sql)->getResult();

        $data['invoiceGtotal'] = $this->db->table('invoice_item')
            ->selectSum('item_amount', 'Gtotal')
            ->where('inv_master_id', $invoiceId)
            ->get()
            ->getResult();

        $data['invoice_master'] = $this->db->table('invoice_master')
            ->where('id', $invoiceId)
            ->get()
            ->getResult();

        if (empty($data['invoice_master'])) {
            return $this->response->setStatusCode(404)->setBody('Invoice not found');
        }

        $sql = 'select *,if(gender=1,\'Male\',\'Female\') as xgender from patient_master where id=' . (int) $data['invoice_master'][0]->attach_id;
        $data['patient_master'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['patient_master']);

        $insCompId = (int) $data['invoice_master'][0]->insurance_id;
        $insId = (int) $data['invoice_master'][0]->insurance_card_id;

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')->where('id', $insId)->get()->getResult();
        $data['insurance'] = $this->db->table('hc_insurance')->where('id', $insCompId)->get()->getResult();

        $data['ipd_master'] = $this->db->table('ipd_master')
            ->where('ipd_status', 0)
            ->where('p_id', (int) $data['invoice_master'][0]->attach_id)
            ->get()
            ->getResult();

        $data['case_master'] = $this->db->table('organization_case_master')
            ->where('status', 0)
            ->where('p_id', (int) $data['invoice_master'][0]->attach_id)
            ->get()
            ->getResult();

        $data['payment_history'] = $this->db->query('select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=2 and payof_id=' . $invoiceId)->getResult();

        $discountAmount = (float) $data['invoice_master'][0]->discount_amount;
        $totalAmount = (float) ($data['invoiceGtotal'][0]->Gtotal ?? 0);
        $netAmount = $totalAmount - $discountAmount;
        $paidAmount = (float) ($data['payment_history'][0]->paid_amount ?? 0);
        $balanceAmount = $netAmount - $paidAmount;
        $paymentPart = $balanceAmount > 0 ? 1 : 0;

        $invoiceModel = new InvoiceModel();
        $invoiceModel->updateInvoice([
            'total_amount' => $totalAmount,
            'net_amount' => $netAmount,
            'payment_part_received' => $paidAmount,
            'payment_part_balance' => $balanceAmount,
            'payment_part' => $paymentPart,
        ], $invoiceId);

        $data['invoice_master'] = $this->db->table('invoice_master')->where('id', $invoiceId)->get()->getResult();

        return view('Invoice/Organization_Invoice_PathLab_final_V', $data);
    }

    public function showitem(int $type)
    {
        $invoiceId = (int) $this->request->getPost('lab_invoice_id');

        if ($type > 0) {
            $insId = (int) $this->request->getPost('ins_id');
            $itemId = (int) $this->request->getPost('itype_name_id');

            if ($insId > 1) {
                $sql = 'select id,Concat(idesc,\' : [\',amount1,\']\',if(hc_insurance_id=0,\'\',\'-INS\')) as sdesc,amount1 from v_hc_items_with_insurance '
                    . 'where id=' . $itemId . ' and hc_insurance_id in (0,' . $insId . ')';
            } else {
                $sql = 'select id,Concat(idesc,\' : [\',amount,\']\') as sdesc,amount from hc_items where id=' . $itemId;
            }

            $itemlist = $this->db->query($sql)->getResult();
            $itemRate = $insId > 1 ? (float) $itemlist[0]->amount1 : (float) $itemlist[0]->amount;
            $qty = (float) $this->request->getPost('input_qty');
            $amountValue = $qty * $itemRate;

            $invoiceModel = new InvoiceModel();
            $invoiceModel->addInvoiceItem([
                'inv_master_id' => $invoiceId,
                'item_type' => (int) $this->request->getPost('itype_idv'),
                'item_id' => $itemId,
                'item_name' => $itemlist[0]->sdesc,
                'item_rate' => $itemRate,
                'item_added_date' => str_to_MysqlDate(date('d/m/Y')),
                'item_qty' => $qty,
                'item_amount' => $amountValue,
            ]);
        } else {
            $itemId = (int) $this->request->getPost('itemid');
            $this->db->table('invoice_item')->where('id', $itemId)->delete();
        }

        $sql = 'select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=' . $invoiceId;
        $invoiceDetails = $this->db->query($sql)->getResult();

        $invoiceGtotal = $this->db->table('invoice_item')
            ->selectSum('item_amount', 'Gtotal')
            ->where('inv_master_id', $invoiceId)
            ->get()
            ->getResult();

        $html = '<table class="table table-striped ">';
        $html .= '<tr><th style="width: 10px">#</th><th>Item Group</th><th>Charge Name</th><th>Rate</th><th>Qty</th><th>Amount</th><th></th></tr>';

        $srno = 0;
        foreach ($invoiceDetails as $row) {
            $srno++;
            $html .= '<tr>';
            $html .= '<td>' . $srno . '</td>';
            $html .= '<td>' . $row->desc . '</td>';
            $html .= '<td>' . $row->item_name . '</td>';
            $html .= '<td>' . $row->item_rate . '</td>';
            $html .= '<td>' . $row->item_qty . '</td>';
            $html .= '<td>' . $row->item_amount . '</td>';
            $html .= '<td><button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice(' . $row->id . ')">-Remove</button></td>';
            $html .= '</tr>';
        }

        $html .= '<input type="hidden" id="srno" name="srno" value="' . $srno . '" />';
        $html .= '<tr><th style="width: 10px">#</th><th></th><th></th><th></th><th>Gross Total</th><th>' . ($invoiceGtotal[0]->Gtotal ?? 0) . '</th><th></th></tr>';
        $html .= '</table>';

        return $this->response->setBody($html);
    }

    public function confirm_payment()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setBody('Invalid request');
        }

        $invoiceId = (int) $this->request->getPost('lab_invoice_id');
        $mode = (int) $this->request->getPost('mode');
        $amountPaid = (float) $this->request->getPost('input_amount_paid');

        $invMaster = $this->db->table('invoice_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invMaster) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invoice not found']);
        }

        $user = auth()->user();
        $userId = $user->id ?? '';
        $userName = $user->username ?? $user->email ?? 'User';

        $payRemark = '';
        if ((float) ($invMaster->discount_amount ?? 0) > 0) {
            $payRemark = 'Dis.Amt.:' . ($invMaster->discount_desc ?? '') . ' /Amount: ' . ($invMaster->discount_amount ?? 0) . '/Update:' . ($invMaster->disc_update_by ?? '');
        }

        $paymentModel = new PaymentModel();
        $invoiceModel = new InvoiceModel();

        if ($mode === 1) {
            $payData = [
                'payment_mode' => 1,
                'payof_type' => 2,
                'payof_id' => $invoiceId,
                'payof_code' => $invMaster->invoice_code ?? '',
                'credit_debit' => 0,
                'amount' => $amountPaid,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $payRemark,
                'update_by' => $userName . '[' . $userId . ']',
            ];
            $paymentId = $paymentModel->insertPayment($payData);

            $invoiceModel->updateInvoice([
                'payment_mode' => 1,
                'payment_status' => 1,
                'invoice_status' => 1,
                'payment_mode_desc' => 'Cash',
                'payment_id' => $paymentId,
                'confirm_invoice' => date('Y-m-d H:i:s'),
                'prepared_by' => $userName . '[' . $userId . ']',
            ], $invoiceId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Cash',
            ]);
        }

        if ($mode === 2) {
            $payData = [
                'payment_mode' => 2,
                'payof_type' => 2,
                'payof_id' => $invoiceId,
                'payof_code' => $invMaster->invoice_code ?? '',
                'credit_debit' => 0,
                'amount' => $amountPaid,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $payRemark,
                'update_by' => $userName . '[' . $userId . ']',
                'card_bank' => (string) $this->request->getPost('input_card_mac'),
                'cust_card' => (string) $this->request->getPost('input_card_bank'),
                'card_remark' => (string) $this->request->getPost('input_card_digit'),
                'card_tran_id' => (string) $this->request->getPost('input_card_tran'),
            ];
            $paymentId = $paymentModel->insertPayment($payData);

            $invoiceModel->updateInvoice([
                'payment_mode' => 2,
                'payment_status' => 1,
                'invoice_status' => 1,
                'payment_mode_desc' => 'Bank Card',
                'payment_id' => $paymentId,
                'confirm_invoice' => date('Y-m-d H:i:s'),
                'card_bank' => (string) $this->request->getPost('input_card_mac'),
                'cust_card' => (string) $this->request->getPost('input_card_bank'),
                'card_remark' => (string) $this->request->getPost('input_card_digit'),
                'card_tran_id' => (string) $this->request->getPost('input_card_tran'),
                'prepared_by' => $userName . '[' . $userId . ']',
            ], $invoiceId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Bank Card',
            ]);
        }

        if ($mode === 3) {
            $invoiceModel->updateInvoice([
                'payment_mode' => 3,
                'payment_status' => 1,
                'invoice_status' => 1,
                'payment_mode_desc' => 'IPD Credit',
                'confirm_invoice' => date('Y-m-d H:i:s'),
                'ipd_id' => (int) $this->request->getPost('ipd_id'),
                'prepared_by' => $userName . '[' . $userId . ']',
            ], $invoiceId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'IPD Credit',
            ]);
        }

        if ($mode === 4) {
            $invoiceModel->updateInvoice([
                'payment_mode' => 3,
                'payment_status' => 1,
                'invoice_status' => 1,
                'payment_mode_desc' => 'Org.Case Credit',
                'confirm_invoice' => date('Y-m-d H:i:s'),
                'insurance_case_id' => (int) $this->request->getPost('case_id'),
                'prepared_by' => $userName . '[' . $userId . ']',
            ], $invoiceId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Org. Credit',
            ]);
        }

        return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid payment mode']);
    }

    public function invoice_print(int $invoiceId)
    {
        $sql = 'select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=' . $invoiceId;
        $data['invoiceDetails'] = $this->db->query($sql)->getResult();

        $data['invoiceGtotal'] = $this->db->table('invoice_item')
            ->selectSum('item_amount', 'Gtotal')
            ->where('inv_master_id', $invoiceId)
            ->get()
            ->getResult();

        $data['invoice_master'] = $this->db->table('invoice_master')
            ->where('id', $invoiceId)
            ->get()
            ->getResult();

        if (empty($data['invoice_master'])) {
            return $this->response->setStatusCode(404)->setBody('Invoice not found');
        }

        $sql = 'select *,if(gender=1,\'Male\',\'Female\') as xgender from patient_master where id=' . (int) $data['invoice_master'][0]->attach_id;
        $data['patient_master'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['patient_master']);

        $insCompId = (int) $data['invoice_master'][0]->insurance_id;
        $insId = (int) $data['invoice_master'][0]->insurance_card_id;

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')->where('id', $insId)->get()->getResult();
        $data['insurance'] = $this->db->table('hc_insurance')->where('id', $insCompId)->get()->getResult();

        $data['case_master'] = $this->db->table('organization_case_master')
            ->where('id', (int) $data['invoice_master'][0]->insurance_case_id)
            ->get()
            ->getResult();

        $data['payment_history'] = $this->db->query('select *,(case payment_mode when  1 Then \'cash\' when  2 then \'Bank Card\' when 3 then \'Org Credit\' else \'Pending\' end) as Payment_type_str from payment_history where payof_type=2 and payof_id=' . $invoiceId)->getResult();

        return view('Invoice/Organization_invoice_print_v', $data);
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
