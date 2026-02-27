<?php

namespace App\Controllers\Billing;

use App\Controllers\BaseController;
use App\Models\InvoiceModel;

class Charges extends BaseController
{
    private function requirePermission(string $permission)
    {
        $user = auth()->user();
        if (!$user || !$user->can($permission)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'update' => 0,
                    'error_text' => 'Permission denied',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        return null;
    }

    public function add(int $pno, int $insId = 0, int $opdId = 0)
    {
        if ($resp = $this->requirePermission('billing.charges.edit')) {
            return $resp;
        }

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . (int) $pno . "'";
        $query = $this->db->query($sql);
        $data['person_info'] = $query->getResult();
        if (!empty($data['person_info'])) {
            $row = $data['person_info'][0];
            $row->age = get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '');
        }

        if ($opdId > 0) {
            $sql = "select * from opd_master where p_id='" . (int) $pno . "' and opd_id=" . (int) $opdId . " order by opd_id desc limit 1";
        } else {
            $sql = "select * from opd_master where p_id='" . (int) $pno . "' order by opd_id desc limit 1";
        }
        $query = $this->db->query($sql);
        $data['opd_master'] = $query->getResult();

        $selectDoc = 0;
        $opdCode = '';
        if (count($data['opd_master']) > 0) {
            $selectDoc = (int) $data['opd_master'][0]->doc_id;
            $opdCode = $data['opd_master'][0]->opd_code;
        }

        $sql = "select l.doc_id from ipd_master p join ipd_master_doc_list l on p.id=l.ipd_id
            where p.ipd_status=0 and p.p_id=" . (int) $pno;
        $query = $this->db->query($sql);
        $data['ipd_master_docid'] = $query->getResult();
        if (count($data['ipd_master_docid']) > 0) {
            $selectDoc = (int) $data['ipd_master_docid'][0]->doc_id;
        }

        $insCompId = 0;
        if ($insId > 0) {
            $sql = "select * from hc_item_type order by group_desc";
        } else {
            $sql = "select * from hc_item_type where itype_id<>33 order by group_desc";
        }
        $query = $this->db->query($sql);
        $data['labitemtype'] = $query->getResult();

        if ($insId > 0) {
            $sql = "select * from hc_insurance_card where id=" . (int) $insId;
            $query = $this->db->query($sql);
            $data['hc_insurance_card'] = $query->getResult();
            if (!empty($data['hc_insurance_card'])) {
                $insCompId = (int) $data['hc_insurance_card'][0]->insurance_id;
            }

            $sql = "select * from hc_insurance where id=" . $insCompId;
            $query = $this->db->query($sql);
            $data['insurance'] = $query->getResult();
            if (!empty($data['insurance'])) {
                $pathCash = (int) $data['insurance'][0]->path_cash;
                $pathCredit = (int) $data['insurance'][0]->path_credit;
            }
        }

        $pathCash = $pathCash ?? 1;
        $pathCredit = $pathCredit ?? 1;
        $data['pdata'] = $insCompId;

        $sql = "select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist'] = $query->getResult();

        if ($insCompId < 2) {
            $sql = "select id,Concat(idesc,' : {',amount,'}') as sdesc from hc_items where itype=1 order by idesc";
        } else {
            $sql = "select m.id,m.itype,m.idesc,m.amount,t.itype_id,t.is_ipd_opd,t.group_desc,
                i.hc_insurance_id,if(i.hc_insurance_id is null,m.amount,i.amount1) as amount1,
                Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',if(i.id is null,'',concat('[',i.code,']'))) as sdesc
                from (hc_items m join hc_item_type t on m.itype=t.itype_id)
                left join (select * from hc_items_insurance where hc_insurance_id=" . $insCompId . " and isdelete=0) as i
                on m.id=i.hc_items_id
                where itype_id=1 order by m.idesc";
        }
        $query = $this->db->query($sql);
        $data['labitem'] = $query->getResult();

        $invoiceModel = new InvoiceModel();

        if ($insId > 0) {
            $sql = "select * from invoice_master where invoice_status=0 and insurance_card_id>0 and attach_id=" . (int) $pno;
        } else {
            $sql = "select * from invoice_master where invoice_status=0 and insurance_card_id=0 and attach_id=" . (int) $pno;
        }
        $query = $this->db->query($sql);
        $data['chk_draft_invoice'] = $query->getResult();

        if (count($data['chk_draft_invoice']) < 1) {
            $data['insert_invoice'] = [
                'attach_type' => '0',
                'opd_code' => $opdCode,
                'attach_id' => $pno,
                'refer_by_id' => $selectDoc,
                'inv_date' => date('Y-m-d H:i:s'),
                'inv_name' => $data['person_info'][0]->p_fname ?? '',
                'insurance_id' => $insCompId,
                'insurance_card_id' => $insId,
                'insurance_cash' => $pathCash,
                'insurance_credit' => $pathCredit,
                'inv_a_code' => $data['person_info'][0]->p_code ?? '',
            ];
            $insertId = $invoiceModel->createInvoice($data['insert_invoice']);
        } else {
            $today = new \DateTime(date('Y-m-d'));
            $invDate = new \DateTime($data['chk_draft_invoice'][0]->inv_date);

            if ($invDate < $today) {
                $data['insert_invoice'] = [
                    'attach_type' => '0',
                    'attach_id' => $pno,
                    'opd_code' => $opdCode,
                    'refer_by_id' => $selectDoc,
                    'inv_date' => date('Y-m-d H:i:s'),
                    'inv_name' => $data['person_info'][0]->p_fname ?? '',
                    'insurance_id' => $insCompId,
                    'insurance_card_id' => $insId,
                    'insurance_cash' => $pathCash,
                    'insurance_credit' => $pathCredit,
                    'inv_a_code' => $data['person_info'][0]->p_code ?? '',
                ];
                $insertId = $invoiceModel->createInvoice($data['insert_invoice']);
            } else {
                $insertId = (int) $data['chk_draft_invoice'][0]->id;
            }
        }

        if ($insertId) {
            $sql = "select * from invoice_master where id=" . $insertId;
            $query = $this->db->query($sql);
            $data['invoiceMaster'] = $query->getResult();

            $sql = "select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=" . $insertId;
            $query = $this->db->query($sql);
            $data['invoiceDetails'] = $query->getResult();

            $sql = "select sum(item_amount) as Gtotal from invoice_item where inv_master_id=" . $insertId;
            $query = $this->db->query($sql);
            $data['invoiceGtotal'] = $query->getResult();
        }

        $sql = "select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days
            from ipd_master where ipd_status=0 and p_id=" . (int) ($data['invoiceMaster'][0]->attach_id ?? 0);
        $query = $this->db->query($sql);
        $data['ipd_master'] = $query->getResult();

        $data['show_ipd_credit'] = false;
        if (!empty($data['ipd_master']) && !empty($data['invoiceMaster'])) {
            $insuranceCaseId = (int) ($data['invoiceMaster'][0]->insurance_case_id ?? 0);
            $invoiceIpdId = (int) ($data['invoiceMaster'][0]->ipd_id ?? 0);
            $data['show_ipd_credit'] = ($insuranceCaseId === 0 && $invoiceIpdId === 0);
        }

        return view('billing/charges/charges_invoice_edit', $data);
    }

    public function edit(int $invoiceId)
    {
        if ($resp = $this->requirePermission('billing.charges.edit')) {
            return $resp;
        }

        $invoiceMasterRow = $this->db->table('invoice_master')->where('id', $invoiceId)->get()->getRow();
        if (empty($invoiceMasterRow)) {
            return $this->response->setStatusCode(404)->setBody('Invoice not found');
        }

        $pno = (int) ($invoiceMasterRow->attach_id ?? 0);
        $insId = (int) ($invoiceMasterRow->insurance_card_id ?? 0);
        $insCompId = (int) ($invoiceMasterRow->insurance_id ?? 0);

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . (int) $pno . "'";
        $query = $this->db->query($sql);
        $data['person_info'] = $query->getResult();
        if (!empty($data['person_info'])) {
            $row = $data['person_info'][0];
            $row->age = get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '');
        }

        if ($insId > 0) {
            $sql = "select * from hc_item_type order by group_desc";
        } else {
            $sql = "select * from hc_item_type where itype_id<>33 order by group_desc";
        }
        $query = $this->db->query($sql);
        $data['labitemtype'] = $query->getResult();

        $sql = "select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist'] = $query->getResult();

        if ($insCompId < 2) {
            $sql = "select id,Concat(idesc,' : {',amount,'}') as sdesc from hc_items where itype=1 order by idesc";
        } else {
            $sql = "select m.id,m.itype,m.idesc,m.amount,t.itype_id,t.is_ipd_opd,t.group_desc,
                i.hc_insurance_id,if(i.hc_insurance_id is null,m.amount,i.amount1) as amount1,
                Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',if(i.id is null,'',concat('[',i.code,']'))) as sdesc
                from (hc_items m join hc_item_type t on m.itype=t.itype_id)
                left join (select * from hc_items_insurance where hc_insurance_id=" . $insCompId . " and isdelete=0) as i
                on m.id=i.hc_items_id
                where itype_id=1 order by m.idesc";
        }
        $query = $this->db->query($sql);
        $data['labitem'] = $query->getResult();

        $data['pdata'] = $insCompId;

        $sql = "select * from invoice_master where id=" . (int) $invoiceId;
        $query = $this->db->query($sql);
        $data['invoiceMaster'] = $query->getResult();

        $sql = "select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=" . (int) $invoiceId;
        $query = $this->db->query($sql);
        $data['invoiceDetails'] = $query->getResult();

        $sql = "select sum(item_amount) as Gtotal from invoice_item where inv_master_id=" . (int) $invoiceId;
        $query = $this->db->query($sql);
        $data['invoiceGtotal'] = $query->getResult();

        $sql = "select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days
            from ipd_master where ipd_status=0 and p_id=" . (int) ($data['invoiceMaster'][0]->attach_id ?? 0);
        $query = $this->db->query($sql);
        $data['ipd_master'] = $query->getResult();

        return view('billing/charges/charges_invoice_edit', $data);
    }

    public function listByType()
    {
        if ($resp = $this->requirePermission('billing.charges.edit')) {
            return $resp;
        }

        $tid = (int) $this->request->getPost('itype_idv');
        $insId = (int) $this->request->getPost('ins_id');

        if ($insId > 1) {
            $sql = "select m.id,m.itype,m.idesc,m.amount,t.itype_id,t.is_ipd_opd,t.group_desc,
                i.hc_insurance_id ,if(i.hc_insurance_id is null,m.amount,i.amount1) as amount1,
                Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',if(i.id is null,'',concat('[',i.code,']'))) as sdesc
                from (hc_items m join hc_item_type t on m.itype=t.itype_id)
                left join (select * from hc_items_insurance where hc_insurance_id=" . $insId . " and isdelete=0) as i
                on m.id=i.hc_items_id
                where itype_id=" . $tid . " order by m.idesc";
        } else {
            $sql = "select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=" . $tid . " order by idesc";
        }

        $query = $this->db->query($sql);
        $labitem = $query->getResult();

        echo '<div class="form-group">';
        echo '<label>Charge Name</label>';
        echo '<select class="form-control input-sm select2" id="itype_name_id" name="itype_name_id">';
        foreach ($labitem as $row) {
            echo '<option value=' . $row->id . '>' . $row->sdesc . '</option>';
        }
        echo '</select></div>';
    }

    public function updateReferDoc()
    {
        if ($resp = $this->requirePermission('billing.charges.edit')) {
            return $resp;
        }

        $invoiceId = (int) $this->request->getPost('inv_id');
        $docId = (int) $this->request->getPost('doc_id');
        $rawDate = (string) $this->request->getPost('inv_date');
        $invDate = str_to_MysqlDate($rawDate);
        if (strpos($rawDate, '-') !== false && strlen($rawDate) === 10) {
            $invDate = $rawDate;
        }

        $user = auth()->user();
        $canEditDate = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.date-edit') : false;
        if (!$canEditDate) {
            $existing = $this->db->table('invoice_master')->select('inv_date')->where('id', $invoiceId)->get()->getRowArray();
            if (!empty($existing['inv_date'])) {
                $invDate = $existing['inv_date'];
            }
        }

        if ($docId < 1) {
            $referName = (string) $this->request->getPost('refername');
        } else {
            $sql = "select * from doctor_master where id=" . $docId;
            $query = $this->db->query($sql);
            $docMaster = $query->getResult();
            $referName = $docMaster[0]->p_fname ?? '';
        }

        $dataUpdate = [
            'refer_by_id' => $docId,
            'inv_date' => $invDate,
            'refer_by_other' => $referName,
            'invoice_status' => '1',
        ];

        $invoiceModel = new InvoiceModel();
        $invoiceModel->updateInvoice($dataUpdate, $invoiceId);
    }

    public function item(int $type)
    {
        if ($resp = $this->requirePermission('billing.charges.edit')) {
            return $resp;
        }

        $invoiceModel = new InvoiceModel();

        if ($type === 1) {
            $insId = (int) $this->request->getPost('ins_id');
            $itemId = (int) $this->request->getPost('itype_name_id');

            if ($insId > 1) {
                $sql = "select m.id,m.itype,m.idesc as sdesc,m.amount,t.itype_id,t.is_ipd_opd,t.group_desc,i.isdelete,
                    if(i.hc_insurance_id is null,0,i.hc_insurance_id) as hc_insurance_id,
                    if(i.hc_insurance_id is null,m.amount,i.amount1) as amount1,
                    if(i.hc_insurance_id is null,'',i.code) as org_code
                    from (hc_items m left join hc_items_insurance i on m.id=i.hc_items_id and i.isdelete=0 and i.hc_insurance_id=" . $insId . ")
                    join hc_item_type t on m.itype=t.itype_id where m.id=" . $itemId;
                $query = $this->db->query($sql);
                $itemlist = $query->getResult();

                $itemRate = $itemlist[0]->amount1 ?? 0;
                $orgCode = $itemlist[0]->org_code ?? '';
                $customRate = (float) $this->request->getPost('input_rate');
                if ($customRate > 0) {
                    $itemRate = $customRate;
                }
                $amountValue = (float) $this->request->getPost('input_qty') * $itemRate;
            } else {
                $sql = "select id,idesc as sdesc,amount from hc_items where id=" . $itemId;
                $query = $this->db->query($sql);
                $itemlist = $query->getResult();

                $itemRate = $itemlist[0]->amount ?? 0;
                $orgCode = '';
                $customRate = (float) $this->request->getPost('input_rate');
                if ($customRate > 0) {
                    $itemRate = $customRate;
                }
                $amountValue = (float) $this->request->getPost('input_qty') * $itemRate;
            }

            $insertInvoiceItem = [
                'inv_master_id' => (int) $this->request->getPost('lab_invoice_id'),
                'item_type' => (int) $this->request->getPost('itype_idv'),
                'item_id' => $itemId,
                'item_name' => $itemlist[0]->sdesc ?? '',
                'item_rate' => $itemRate,
                'item_added_date' => str_to_MysqlDate(date('d/m/Y')),
                'item_qty' => (float) $this->request->getPost('input_qty'),
                'item_amount' => $amountValue,
                'ins_id' => $insId,
                'org_code' => $orgCode,
            ];

            $sql = "select * from invoice_item where inv_master_id=" . (int) $this->request->getPost('lab_invoice_id') . " and item_id=" . $itemId;
            $query = $this->db->query($sql);
            $itemCheck = $query->getResult();

            if (count($itemCheck) < 1) {
                $invoiceModel->addInvoiceItem($insertInvoiceItem);
            }
        } elseif ($type === 2) {
            $amountValue = (float) $this->request->getPost('update_qty') * (float) $this->request->getPost('item_rate');
            $updateInvoiceItem = [
                'item_added_date' => str_to_MysqlDate(date('d/m/Y')),
                'item_qty' => (float) $this->request->getPost('update_qty'),
                'item_amount' => $amountValue,
            ];
            $invoiceModel->updateItem($updateInvoiceItem, (int) $this->request->getPost('itemid'));
        } else {
            $invoiceModel->deleteInvoiceItem((int) $this->request->getPost('itemid'));
        }

        $invoiceId = (int) $this->request->getPost('lab_invoice_id');
        $sql = "select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=" . $invoiceId . " Order by i.id";
        $query = $this->db->query($sql);
        $invoiceDetails = $query->getResult();

        $sql = "select * from invoice_master where id=" . $invoiceId;
        $query = $this->db->query($sql);
        $invoiceMaster = $query->getResult();

        $sql = "select sum(item_amount) as Gtotal from invoice_item where inv_master_id=" . $invoiceId;
        $query = $this->db->query($sql);
        $invoiceGtotal = $query->getResult();

        $totalAmount = (float) ($invoiceGtotal[0]->Gtotal ?? 0);
        $discountAmount = (float) ($invoiceMaster[0]->discount_amount ?? 0);
        $netAmount = $totalAmount - $discountAmount;

        $invoiceModel->updateInvoice([
            'total_amount' => $totalAmount,
            'net_amount' => $netAmount,
        ], $invoiceId);

        echo '<table class="table table-striped ">
            <tr>
                <th style="width: 10px">#</th>
                <th>Charge Type</th>
                <th>Charge Name</th>
                <th>Rate</th>
                <th>Qty</th>
                <th>Updated Qty</th>
                <th>Amount</th>
                <th></th>
            </tr>';

        $srno = 0;
        foreach ($invoiceDetails as $row) {
            $srno++;
            echo '<tr>';
            echo '<td>' . $srno . '</td>';
            echo '<td>' . $row->group_desc . '</td>';
            echo '<td>' . $row->item_name . '</td>';
            if (in_array((int) $row->item_type, [1, 2, 3, 4, 5], true)) {
                echo '<td>' . $row->item_rate . '</td>';
                echo '<td>' . $row->item_qty . '</td>';
                echo '<td>' . $row->item_amount . '</td>';
                echo '<td>';
            } else {
                echo '<td><input type=hidden name="hidden_rate_' . $row->id . '" id="hidden_rate_' . $row->id . '" value="' . $row->item_rate . '" >' . $row->item_rate . '</td>';
                echo '<td><input class="form-control" style="width:100px" name="input_qty_' . $row->id . '" id="input_qty_' . $row->id . '" value="' . $row->item_qty . '" type="number" /></td>';
                echo '<td>' . $row->item_amount . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn btn-primary" id="btn_update" onclick="update_qty(' . $row->id . ')">Update</button>';
            }

            $sql = "select * from lab_request where charge_item_id=" . $row->id;
            $query = $this->db->query($sql);
            $labRequest = $query->getResult();

            if (count($labRequest) < 1) {
                echo '<td><button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice(' . $row->id . ')">-Remove</button>';
            }

            echo '</tr>';
        }

        echo '<input type="hidden" id="srno" name="srno" value="' . count($invoiceDetails) . '" />';

        echo '<tr>
                <th style="width: 10px">#</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th>Gross Total</th>
                <th>' . ($invoiceGtotal[0]->Gtotal ?? 0) . '</th>
                <th></th>
            </tr>
        </table>';
    }

    public function show(int $invoiceId)
    {
        if ($resp = $this->requirePermission('billing.charges.view')) {
            return $resp;
        }

        $invoiceModel = new InvoiceModel();
        $invoiceModel->updateInvoiceFinal($invoiceId);

        $sql = "select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id
            where i.inv_master_id=" . (int) $invoiceId;
        $query = $this->db->query($sql);
        $data['invoiceDetails'] = $query->getResult();

        $sql = "select *,
            (case payment_mode when 1 then 'Cash' when 2 then 'Bank Card/Online' when 3 then 'IPD Credit' when 4 then 'Org. Credit' else 'Pending' end) as Payment_type_str
            from invoice_master where id=" . (int) $invoiceId;
        $query = $this->db->query($sql);
        $data['invoice_master'] = $query->getResult();

        $insCompId = (int) ($data['invoice_master'][0]->insurance_id ?? 0);
        $insId = (int) ($data['invoice_master'][0]->insurance_card_id ?? 0);

        $sql = "select * from hc_insurance_card where id=" . $insId;
        $query = $this->db->query($sql);
        $data['hc_insurance_card'] = $query->getResult();

        $sql = "select * from hc_insurance where id=" . $insCompId;
        $query = $this->db->query($sql);
        $data['insurance'] = $query->getResult();

        $sql = "select p.*,f.full_path as profile_picture
            from patient_master p
            left join file_upload_data f on p.profile_file_id=f.id
            where p.id=" . (int) ($data['invoice_master'][0]->attach_id ?? 0);
        $query = $this->db->query($sql);
        $data['patient_master'] = $query->getResult();
        if (!empty($data['patient_master'])) {
            $row = $data['patient_master'][0];
            $row->age = get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '');
            $row->xgender = ($row->gender ?? 0) == 1 ? 'Male' : 'Female';
        }

        $sql = "select * from ipd_master where ipd_status=0 and p_id=" . (int) ($data['invoice_master'][0]->attach_id ?? 0);
        if (($data['invoice_master'][0]->ipd_id ?? 0) > 0) {
            $sql = "select * from ipd_master where id=" . (int) $data['invoice_master'][0]->ipd_id;
        }
        $query = $this->db->query($sql);
        $data['ipd_master'] = $query->getResult();

        $data['show_ipd_credit'] = false;
        if (! empty($data['ipd_master'])) {
            $insuranceCaseId = (int) ($data['invoice_master'][0]->insurance_case_id ?? 0);
            $invoiceIpdId = (int) ($data['invoice_master'][0]->ipd_id ?? 0);
            $data['show_ipd_credit'] = ($insuranceCaseId === 0 && $invoiceIpdId === 0);
        }

        $sql = "select * from invoice_item i join hc_item_type t on i.item_type=t.itype_id
            where t.is_ipd_opd=2 and i.inv_master_id=" . (int) $invoiceId;
        $query = $this->db->query($sql);
        $ipdCredit = $query->getResult();
        $data['IPD_Credit'] = count($ipdCredit) > 0 ? '1' : '0';

        $sql = "select * from organization_case_master where case_type=0 and status=0 and p_id=" . (int) ($data['invoice_master'][0]->attach_id ?? 0);
        if (($data['invoice_master'][0]->insurance_case_id ?? 0) > 0) {
            $sql = "select * from organization_case_master where case_type=0 and id=" . (int) $data['invoice_master'][0]->insurance_case_id;
        } elseif (count($data['ipd_master']) > 0) {
            if (($data['ipd_master'][0]->case_id ?? 0) > 0) {
                $sql = "select * from organization_case_master where case_type=0 and id=" . (int) $data['ipd_master'][0]->case_id;
            }
        }
        $query = $this->db->query($sql);
        $data['case_master'] = $query->getResult();

        $sql = "select s.id,s.pay_type,m.bank_name from hospital_bank m join hospital_bank_payment_source s on m.id=s.bank_id";
        $query = $this->db->query($sql);
        $data['bank_data'] = $query->getResult();

        $sql = "select sum(if(credit_debit=0,amount,amount*-1)) as paid_amount
            from payment_history where payof_type=2 and payof_id=" . (int) $invoiceId;
        $query = $this->db->query($sql);
        $data['payment_history'] = $query->getResult();

        $paidAmount = 0;
        if (!empty($data['payment_history']) && $data['payment_history'][0]->paid_amount) {
            $paidAmount = (float) $data['payment_history'][0]->paid_amount;
        }

        $data['paid_amount'] = $paidAmount;
        $netAmount = (float) ($data['invoice_master'][0]->net_amount ?? 0);
        $paymentMode = (int) ($data['invoice_master'][0]->payment_mode ?? 0);
        $data['pending_amount'] = ($paymentMode === 3 || $paymentMode === 4) ? 0 : ($netAmount - $paidAmount);

        return view('billing/charges/charges_invoice_show', $data);
    }

    public function confirm_payment()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        if ($resp = $this->requirePermission('billing.charges.pay')) {
            return $resp;
        }

        $mode = (int) $this->request->getPost('mode');
        $invId = (int) $this->request->getPost('inv_id');
        $spid = (string) $this->request->getPost('spid');
        $receivedAmountInput = (float) $this->request->getPost('received_amount');

        if ($invId <= 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid invoice ID',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $invoice = $this->db->table('invoice_master')->where('id', $invId)->get()->getRowArray();
        if (empty($invoice)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invoice not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $sql = "select coalesce(sum(if(credit_debit=0,amount,amount*-1)),0) as paid_amount
            from payment_history where payof_type=2 and payof_id=" . (int) $invId;
        $query = $this->db->query($sql);
        $paidRow = $query->getResult();

        $paidAmount = (float) ($paidRow[0]->paid_amount ?? 0);
        $netAmount = (float) ($invoice['net_amount'] ?? 0);
        $pendingAmt = $netAmount - $paidAmount;

        $payRemark = '';
        if (!empty($invoice['discount_amount']) && (float) $invoice['discount_amount'] > 0) {
            $payRemark = 'Dis.Amt.:' . ($invoice['discount_desc'] ?? '') . ' /Amount: ' . $invoice['discount_amount'];
        }

        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $userId = $user->id ?? 0;
        $userNameInfo = $userLabel . '[' . date('d-m-Y H:i:s') . ']';

        $paymentModel = new \App\Models\PaymentModel();
        $invoiceModel = new InvoiceModel();

        if ($mode === 0) {
            if ($pendingAmt > 0) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Zero amount allowed only when pending is zero.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $paydata = [
                'payment_mode' => '0',
                'payof_type' => '2',
                'payof_id' => $invId,
                'payof_code' => $invoice['invoice_code'] ?? '',
                'credit_debit' => '0',
                'amount' => $netAmount,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $payRemark,
                'update_by' => $userNameInfo . ' [' . $userId . ']',
                'update_by_id' => $userId,
                'insert_code' => $spid,
            ];

            $paymentModel->insertPayment($paydata);

            $this->db->table('invoice_master')->where('id', $invId)->update([
                'payment_mode' => '0',
                'payment_status' => '1',
            ]);

            $invoiceModel->updateInvoiceFinal($invId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Zero Amount',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($mode === 1 && $pendingAmt > 0) {
            $receivedAmount = $receivedAmountInput > 0 ? $receivedAmountInput : $pendingAmt;
            if ($receivedAmount <= 0 || $receivedAmount > $pendingAmt) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Received amount must be between 0 and pending amount.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $paydata = [
                'payment_mode' => '1',
                'payof_type' => '2',
                'payof_id' => $invId,
                'payof_code' => $invoice['invoice_code'] ?? '',
                'credit_debit' => '0',
                'amount' => $receivedAmount,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $payRemark,
                'update_by' => $userNameInfo . ' [' . $userId . ']',
                'update_by_id' => $userId,
                'insert_code' => $spid,
            ];

            $paymentModel->insertPayment($paydata);

            $newPending = $pendingAmt - $receivedAmount;

            $this->db->table('invoice_master')->where('id', $invId)->update([
                'payment_mode' => '1',
                'payment_status' => $newPending <= 0 ? '1' : '0',
            ]);

            $invoiceModel->updateInvoiceFinal($invId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Cash',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($mode === 2 && $pendingAmt > 0) {
            $cardTran = trim((string) $this->request->getPost('input_card_tran'));
            if ($cardTran === '' || strlen($cardTran) < 3 || strlen($cardTran) > 20) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Card Transaction ID is required (3-20 chars).',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $receivedAmount = $receivedAmountInput > 0 ? $receivedAmountInput : $pendingAmt;
            if ($receivedAmount <= 0 || $receivedAmount > $pendingAmt) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Received amount must be between 0 and pending amount.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $paydata = [
                'payment_mode' => '2',
                'payof_type' => '2',
                'payof_id' => $invId,
                'payof_code' => $invoice['invoice_code'] ?? '',
                'credit_debit' => '0',
                'amount' => $receivedAmount,
                'payment_date' => date('Y-m-d H:i:s'),
                'remark' => $payRemark,
                'update_by' => $userNameInfo . ' [' . $userId . ']',
                'pay_bank_id' => (int) $this->request->getPost('cbo_pay_type'),
                'card_tran_id' => $cardTran,
                'update_by_id' => $userId,
            ];

            $paymentModel->insertPayment($paydata);

            $newPending = $pendingAmt - $receivedAmount;

            $this->db->table('invoice_master')->where('id', $invId)->update([
                'payment_mode' => '2',
                'payment_status' => $newPending <= 0 ? '1' : '0',
            ]);

            $invoiceModel->updateInvoiceFinal($invId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Bank/Online',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($mode === 3) {
            $ipdId = (int) $this->request->getPost('ipd_id');
            if ($ipdId <= 0) {
                $ipdRow = $this->db->table('ipd_master')
                    ->select('id')
                    ->where('ipd_status', 0)
                    ->where('p_id', (int) ($invoice['attach_id'] ?? 0))
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRowArray();
                $ipdId = (int) ($ipdRow['id'] ?? 0);
            }
            if ($ipdId <= 0) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Active IPD admission not found.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }
            $this->db->table('invoice_master')->where('id', $invId)->update([
                'payment_mode' => '3',
                'payment_status' => '1',
                'ipd_id' => $ipdId,
                'ipd_include' => $ipdId > 0 ? 1 : 0,
            ]);

            $invoiceModel->updateInvoiceFinal($invId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'IPD Credit',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($mode === 4) {
            $this->db->table('invoice_master')->where('id', $invId)->update([
                'payment_mode' => '4',
                'payment_status' => '1',
                'insurance_case_id' => (int) $this->request->getPost('case_id'),
            ]);

            $invoiceModel->updateInvoiceFinal($invId);

            return $this->response->setJSON([
                'update' => 1,
                'showcontent' => 'Org. Case Credit',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 0,
            'error_text' => 'Invalid payment request',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function delete()
    {
        if ($resp = $this->requirePermission('billing.charges.edit')) {
            return $resp;
        }

        $invId = (int) $this->request->getPost('inv_id');
        if ($invId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid invoice ID']);
        }

        $this->db->table('invoice_master')->where('id', $invId)->delete();
        $this->db->table('invoice_item')->where('inv_master_id', $invId)->delete();

        return $this->response->setJSON(['update' => 1]);
    }

    public function updateDiscount()
    {
        if ($resp = $this->requirePermission('billing.charges.edit')) {
            return $resp;
        }

        $invId = (int) $this->request->getPost('inv_id');
        $discountDesc = (string) $this->request->getPost('discount_desc');
        $discountAmount = (float) $this->request->getPost('discount_amount');

        if ($invId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid invoice ID']);
        }

        $row = $this->db->table('invoice_master')->where('id', $invId)->get()->getRowArray();
        if (empty($row)) {
            return $this->response->setStatusCode(404)->setJSON(['update' => 0, 'error_text' => 'Invoice not found']);
        }

        $totalAmount = (float) ($row['total_amount'] ?? 0);
        $netAmount = $totalAmount - $discountAmount;

        $updated = (bool) $this->db->table('invoice_master')
            ->where('id', $invId)
            ->update([
                'discount_desc' => $discountDesc,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
            ]);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'net_amount' => $netAmount,
        ]);
    }

    public function cancel_invoice(int $invId)
    {
        if ($resp = $this->requirePermission('billing.charges.cancel')) {
            return $resp;
        }

        $invoice = $this->db->table('invoice_master')->where('id', $invId)->get()->getRowArray();
        if (empty($invoice)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invoice not found']);
        }

        $sql = "select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount
            from payment_history where payof_type=2 and payof_id=" . (int) $invId;
        $query = $this->db->query($sql);
        $paymentHistory = $query->getResult();
        $paidAmount = (float) ($paymentHistory[0]->paid_amount ?? 0);

        if ($paidAmount > 0 && ($invoice['invoice_status'] ?? 0) == 1 && $this->db->tableExists('refund_order')) {
            $user = auth()->user();
            $userId = $user->id ?? 0;
            $userName = ($user->username ?? $user->email ?? 'User') . '[' . $userId . ']:T-' . date('d-m-Y H:i:s');

            $refundRequest = [
                'refund_type' => 2,
                'refund_type_id' => $invId,
                'refund_type_code' => $invoice['invoice_code'] ?? '',
                'refund_type_reason' => 'Cancel Invoice',
                'approved_by_id' => $userId,
                'approved_by' => $userName,
                'refund_amount' => $paidAmount,
                'patient_id' => $invoice['attach_id'] ?? 0,
                'patient_name' => strtoupper((string) ($invoice['inv_name'] ?? '')),
            ];

            $this->db->table('refund_order')->insert($refundRequest);
        }

        $this->db->table('invoice_master')->where('id', $invId)->update([
            'payment_id' => 0,
            'invoice_status' => 2,
        ]);

        return $this->response->setJSON(['update' => 1]);
    }

    public function update_correction_charges()
    {
        if ($resp = $this->requirePermission('billing.charges.correct')) {
            return $resp;
        }

        $invoiceId = (int) $this->request->getPost('invoice_id');
        $corrDesc = (string) $this->request->getPost('input_corr_desc');
        $corrAmount = (float) $this->request->getPost('input_corr_amt');
        $corrCrdr = (int) $this->request->getPost('optionsRadios_crdr');

        $invoice = $this->db->table('invoice_master')->where('id', $invoiceId)->get()->getRowArray();
        if (empty($invoice)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invoice not found']);
        }

        if ($corrAmount <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Correction amount must be greater than 0']);
        }

        $netAmount = (float) ($invoice['net_amount'] ?? 0);
        if ($corrCrdr === 1 && $netAmount < $corrAmount) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Correction amount cannot exceed net amount']);
        }

        $correctionNetAmount = $corrCrdr === 1 ? ($netAmount - $corrAmount) : ($netAmount + $corrAmount);

        $user = auth()->user();
        $userName = $user ? ($user->username ?? $user->email ?? 'User') : 'User';

        $update = [];
        if ($this->db->fieldExists('correction_amount', 'invoice_master')) {
            $update['correction_amount'] = $corrAmount;
        }
        if ($this->db->fieldExists('correction_remark', 'invoice_master')) {
            $update['correction_remark'] = $corrDesc;
        }
        if ($this->db->fieldExists('correction_user', 'invoice_master')) {
            $update['correction_user'] = $userName;
        }
        if ($this->db->fieldExists('correction_crdr', 'invoice_master')) {
            $update['correction_crdr'] = $corrCrdr;
        }
        if ($this->db->fieldExists('correction_datetime', 'invoice_master')) {
            $update['correction_datetime'] = date('Y-m-d H:i:s');
        }
        if ($this->db->fieldExists('correction_net_amount', 'invoice_master')) {
            $update['correction_net_amount'] = $correctionNetAmount;
        }
        $update['invoice_status'] = 3;

        $this->db->table('invoice_master')->where('id', $invoiceId)->update($update);

        $paydata = [
            'payment_mode' => '3',
            'payof_type' => '2',
            'payof_id' => $invoiceId,
            'payof_code' => $invoice['invoice_code'] ?? '',
            'credit_debit' => $corrCrdr === 1 ? 1 : 0,
            'amount' => $corrAmount,
            'payment_date' => date('Y-m-d H:i:s'),
            'remark' => $corrDesc,
            'update_by' => $userName . '[' . ($user->id ?? 0) . ']',
            'update_by_id' => $user->id ?? 0,
        ];

        $paymentModel = new \App\Models\PaymentModel();
        $paymentModel->insertPayment($paydata);

        return $this->response->setJSON(['update' => 1]);
    }
}
