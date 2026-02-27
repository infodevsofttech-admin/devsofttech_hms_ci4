<?php

namespace App\Controllers\Billing;

use App\Controllers\BaseController;
use App\Models\InvoiceModel;
use App\Models\ItemModel;
use App\Models\OrganizationCaseModel;

class CaseMaster extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
        helper(['common', 'form']);
    }

    public function newcase(int $pno, int $insId, int $caseType = 0)
    {
        $data['case_type'] = $caseType;

        $data['data_insurance'] = $this->db->table('hc_insurance')
            ->where('id', $insId)
            ->get()
            ->getResult();

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')
            ->where('p_id', $pno)
            ->where('insurance_id', $insId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        $data['org_case_master'] = $this->db->table('organization_case_master')
            ->where('case_type', $caseType)
            ->where('p_id', $pno)
            ->where('status', 0)
            ->get()
            ->getResult();

        $newCase = count($data['org_case_master']) < 1;

        $data['data'] = $this->db->table('patient_master')
            ->where('id', $pno)
            ->get()
            ->getResult();

        $data['insurance_list'] = $this->db->table('hc_insurance')->get()->getResult();

        if ($newCase) {
            return view('Invoice/Case_Form_V', $data);
        }

        return view('Invoice/Case_Form_profile_V', $data);
    }

    public function open_case(int $orgId, int $caseType = 0)
    {
        $data['org_case_master'] = $this->db->table('organization_case_master')
            ->where('case_type', $caseType)
            ->where('id', $orgId)
            ->get()
            ->getResult();

        if (empty($data['org_case_master'])) {
            return $this->response->setStatusCode(404)->setBody('Case not found');
        }

        $pno = (int) $data['org_case_master'][0]->p_id;
        $insId = (int) $data['org_case_master'][0]->insurance_id;

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')
            ->where('p_id', $pno)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        $data['data_insurance'] = $this->db->table('hc_insurance')
            ->where('id', $insId)
            ->get()
            ->getResult();

        $data['insurance_list'] = $this->db->table('hc_insurance')->get()->getResult();

        $data['ipd_id'] = 0;
        $data['case_type'] = $caseType;
        if ($caseType === 1) {
            $ipd = $this->db->table('ipd_master')
                ->where('case_id', $orgId)
                ->get()
                ->getResult();
            if (! empty($ipd)) {
                $data['ipd_id'] = (int) $ipd[0]->id;
            }
        }

        $data['data'] = $this->db->table('patient_master')
            ->where('id', $pno)
            ->get()
            ->getResult();

        return view('Invoice/Case_Form_profile_V', $data);
    }

    public function create()
    {
        if (! $this->request->isAJAX()) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $rules = [
            'input_mphone1' => 'required|min_length[10]|max_length[30]',
            'input_name' => 'required|min_length[2]|max_length[30]',
            'input_insurance_id' => 'required|min_length[2]|max_length[30]',
            'input_card_holder_name' => 'required|min_length[2]|max_length[50]',
        ];

        $validation = service('validation');
        $validation->setRules($rules);
        if (! $validation->withRequest($this->request)->run()) {
            $errorText = implode("\n", $validation->getErrors());
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => $errorText,
            ]);
        }

        $insId = (int) $this->request->getPost('Insurance_id');
        $caseType = (int) $this->request->getPost('case_type');
        $pId = (int) $this->request->getPost('p_id');

        $insurance = $this->db->table('hc_insurance')
            ->where('id', $insId)
            ->get()
            ->getRow();
        $insName = $insurance->ins_company_name ?? '';

        $caseData = [
            'p_phone_number' => (string) $this->request->getPost('input_mphone1'),
            'p_name' => (string) $this->request->getPost('input_name'),
            'p_gender' => (string) $this->request->getPost('optionsRadios_gender'),
            'date_registration' => str_to_MysqlDate((string) $this->request->getPost('datepicker_dob')),
            'p_id' => $pId,
            'case_type' => $caseType,
            'insurance_card_id' => (int) $this->request->getPost('inc_card_id'),
            'insurance_no' => (string) $this->request->getPost('input_insurance_id'),
            'insurance_no_1' => (string) $this->request->getPost('input_insurance_no_1'),
            'insurance_no_2' => (string) $this->request->getPost('input_insurance_no_2'),
            'insurance_no_3' => (string) $this->request->getPost('input_insurance_no_3'),
            'insurance_card_name' => (string) $this->request->getPost('input_card_holder_name'),
            'insurance_id' => $insId,
            'insurance_company_name' => $insName,
        ];

        $existing = $this->db->table('organization_case_master')
            ->where('status', 0)
            ->where('case_type', $caseType)
            ->where('p_id', $pId)
            ->get()
            ->getResult();

        $caseModel = new OrganizationCaseModel();
        if (! empty($existing)) {
            $insertId = (int) $existing[0]->id;
        } else {
            $insertId = $caseModel->insertCase($caseData);
        }

        if ($caseType === 1 && $insertId > 0) {
            $ipd = $this->db->table('ipd_master')
                ->where('case_id', 0)
                ->where('ipd_status', 0)
                ->where('p_id', $pId)
                ->get()
                ->getResult();

            if (! empty($ipd)) {
                $ipdId = (int) $ipd[0]->id;
                $this->db->table('ipd_master')
                    ->where('id', $ipdId)
                    ->update(['case_id' => $insertId]);

                $caseModel->updateCase(['ipd_id' => $ipdId], $insertId);
            }
        }

        return $this->response->setJSON([
            'insertid' => $insertId,
            'p_id' => $pId,
            'ins_id' => $insId,
        ]);
    }

    public function update()
    {
        if (! $this->request->isAJAX()) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $rules = [
            'input_mphone1' => 'required|min_length[10]|max_length[30]',
            'input_name' => 'required|min_length[2]|max_length[30]',
            'input_insurance_id' => 'required|min_length[2]|max_length[30]',
            'datepicker_dob' => 'required|min_length[2]|max_length[30]',
            'input_card_holder_name' => 'required|min_length[2]|max_length[50]',
        ];

        $validation = service('validation');
        $validation->setRules($rules);
        if (! $validation->withRequest($this->request)->run()) {
            $errorText = implode("\n", $validation->getErrors());
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => $errorText,
            ]);
        }

        $insId = (int) $this->request->getPost('Insurance_id');
        $pId = (int) $this->request->getPost('patient_id');
        $caseType = (int) $this->request->getPost('case_type');
        $caseId = (int) $this->request->getPost('c_id');

        $insurance = $this->db->table('hc_insurance')
            ->where('id', $insId)
            ->get()
            ->getRow();
        $insName = $insurance->ins_company_name ?? '';

        $orgCase = $this->db->table('organization_case_master')
            ->where('id', $caseId)
            ->get()
            ->getResult();

        $caseData = [
            'p_phone_number' => (string) $this->request->getPost('input_mphone1'),
            'p_name' => (string) $this->request->getPost('input_name'),
            'p_gender' => (string) $this->request->getPost('optionsRadios_gender'),
            'date_registration' => str_to_MysqlDate((string) $this->request->getPost('datepicker_dob')),
            'remark' => (string) $this->request->getPost('remark'),
            'insurance_no' => (string) $this->request->getPost('input_insurance_id'),
            'insurance_no_1' => (string) $this->request->getPost('input_insurance_no_1'),
            'insurance_no_2' => (string) $this->request->getPost('input_insurance_no_2'),
            'insurance_no_3' => (string) $this->request->getPost('input_insurance_no_3'),
            'insurance_card_name' => (string) $this->request->getPost('input_card_holder_name'),
            'insurance_id' => $insId,
            'insurance_company_name' => $insName,
        ];

        $caseModel = new OrganizationCaseModel();
        $caseModel->updateCase($caseData, $caseId);

        if ($caseType === 0) {
            $this->updateOrgTotals($caseId);
        }

        if ($caseType === 1 && ! empty($orgCase) && (int) $orgCase[0]->ipd_id === 0) {
            $ipd = $this->db->table('ipd_master')
                ->where('ipd_status', 0)
                ->where('case_id', 0)
                ->where('p_id', $pId)
                ->get()
                ->getResult();

            if (! empty($ipd)) {
                $ipdId = (int) $ipd[0]->id;
                $this->db->table('ipd_master')
                    ->where('id', $ipdId)
                    ->update(['case_id' => $caseId]);

                $caseModel->updateCase(['ipd_id' => $ipdId], $caseId);
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'showcontent' => 'Data Saved successfully',
        ]);
    }

    public function addPathTest(int $caseId)
    {
        $data['or_case_master'] = $this->db->table('organization_case_master')
            ->where('id', $caseId)
            ->get()
            ->getResult();

        if (empty($data['or_case_master'])) {
            return $this->response->setStatusCode(404)->setBody('Case not found');
        }

        $pId = (int) $data['or_case_master'][0]->p_id;

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $pId . "'";
        $data['person_info'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['person_info'], 'age');

        $data['labitemtype'] = $this->db->table('hc_item_type')->get()->getResult();

        $sql = 'select id,Concat(idesc,\' : [\',amount1,\']\',if(hc_insurance_id>0,\'Ins.\',\'Cash\')) as sdesc '
            . 'from v_hc_items_with_insurance where itype=1 and hc_insurance_id in (0,' . (int) $data['or_case_master'][0]->insurance_id . ')';
        $data['labitem'] = $this->db->query($sql)->getResult();

        $chkDraft = $this->db->table('invoice_master')
            ->selectCount('id', 'no_invoice')
            ->where('invoice_status', 0)
            ->where('case_id', $caseId)
            ->get()
            ->getResult();

        $invoiceId = 0;
        if (! empty($chkDraft) && (int) ($chkDraft[0]->no_invoice ?? 0) < 1) {
            $insertInvoice = [
                'attach_type' => 0,
                'attach_id' => $pId,
                'case_id' => $caseId,
                'inv_date' => str_to_MysqlDate(date('d/m/Y')),
                'inv_name' => $data['person_info'][0]->p_fname ?? '',
                'inv_a_code' => $data['person_info'][0]->p_code ?? '',
            ];
            $invoiceModel = new InvoiceModel();
            $invoiceId = $invoiceModel->createInvoice($insertInvoice);
        } else {
            $invoiceRow = $this->db->table('invoice_master')
                ->select('id')
                ->where('invoice_status', 0)
                ->where('attach_id', $pId)
                ->get()
                ->getResult();
            $invoiceId = ! empty($invoiceRow) ? (int) $invoiceRow[0]->id : 0;
        }

        if ($invoiceId > 0) {
            $data['invoiceMaster'] = $this->db->table('invoice_master')
                ->where('id', $invoiceId)
                ->get()
                ->getResult();

            $sql = 'select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=' . $invoiceId;
            $data['invoiceDetails'] = $this->db->query($sql)->getResult();

            $data['invoiceGtotal'] = $this->db->table('invoice_item')
                ->selectSum('item_amount', 'Gtotal')
                ->where('inv_master_id', $invoiceId)
                ->get()
                ->getResult();
        }

        return view('Invoice/Organization_Invoice_PathLab', $data);
    }

    public function search()
    {
        $sdata = (string) $this->request->getPost('txtsearch');
        $sdata = preg_replace('/[^A-Za-z0-9]/', '', $sdata);

        $sql = "select c.id,c.p_id,c.case_id_code,sum(o.opd_fee_amount) as OPD_Amount,sum(i.net_amount) as Invoice_Amount,
            c.insurance_company_name,c.p_name,p.p_code,c.date_registration,insurance_card_name,
            (case c.status when 0 then 'Pending' when 1 then 'Ready for submission' when 2 then 'Submitted' when 3 then 'Payment Done' else 'Other' end) as str_status,
            Date_Format(c.date_registration,'%d-%m-%Y') as date_registration_in
            from (organization_case_master c join patient_master p  on c.p_id=p.id )
            left join opd_master o   on c.id=o.insurance_case_id
            left join invoice_master i on c.id=i.insurance_case_id
            group by c.id ";

        if ($sdata !== '') {
            $sql .= " WHERE case_id_code = '" . $sdata . "' or p_name like '%" . $sdata . "%' or
                    p_code = '" . $sdata . " order by  c.id desc   limit 20";
        } else {
            $sql .= ' order by  c.id desc   limit 20';
        }

        $data['searchdata'] = $this->db->query($sql)->getResult();

        return view('Invoice/Case_List', $data);
    }

    public function search_all()
    {
        return view('Invoice/case_list_all');
    }

    public function case_invoice(string $casecode, int $print = 0, int $caseid = 0, int $medInclude = 1)
    {
        if ($caseid < 1) {
            $sql = "select * from organization_case_master where case_id_code='" . $casecode . "'";
        } else {
            $sql = "select * from organization_case_master where id='" . $caseid . "'";
        }

        $data['orgcase'] = $this->db->query($sql)->getResult();
        if (empty($data['orgcase'])) {
            return $this->response->setStatusCode(404)->setBody('Case not found');
        }

        $pno = (int) $data['orgcase'][0]->p_id;
        $caseid = (int) $data['orgcase'][0]->id;

        $this->updateOrgTotals($caseid);

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $pno . "'";
        $data['person_info'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['person_info'], 'age');

        $insCompId = (int) $data['orgcase'][0]->insurance_id;
        $incCardId = (int) $data['orgcase'][0]->insurance_card_id;

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')
            ->where('id', $incCardId)
            ->get()
            ->getResult();

        $data['insurance'] = $this->db->table('hc_insurance')
            ->where('id', $insCompId)
            ->get()
            ->getResult();

        $sql = "select c.id AS id,c.case_id_code AS case_id_code,c.p_id AS p_id,
            o.apointment_date AS s_Date,'OPD' AS Charge_type,0 AS Charge_type_id,
            o.opd_id AS item_id,0 AS master_item_id,o.apointment_date AS Adate,
            o.opd_fee_amount AS item_rate,1 AS item_qty,
            date_format(o.apointment_date,'%d-%m-%Y') AS str_date,
            concat('OPD Charge: Dr. ',o.doc_name) AS Description,
            o.opd_code AS Code,o.opd_fee_amount AS Amount,'1' AS orgcode
            from opd_master o
            join organization_case_master c on o.insurance_case_id = c.id
            where o.opd_status in (1,2) and c.id=" . $caseid .
            " order by Charge_type,Adate";
        $data['showinvoice1'] = $this->db->query($sql)->getResult();

        $sql = "select c.id AS id,c.case_id_code AS case_id_code,c.p_id AS p_id,
            i.inv_date AS s_Date,l.group_desc AS Charge_type,l.itype_id AS Charge_type_id,
            t.id AS item_id,t.item_id AS master_item_id,i.inv_date AS Adate,
            t.item_rate AS item_rate,t.item_qty AS item_qty,
            date_format(i.inv_date,'%d-%m-%Y') AS str_date,
            concat(t.item_name) AS Description,i.invoice_code AS Code,
            t.item_amount AS Amount,t.org_code AS orgcode
            from invoice_master i
            join organization_case_master c on i.insurance_case_id = c.id
            join invoice_item t on t.inv_master_id = i.id
            join hc_item_type l on t.item_type = l.itype_id
            left join hc_items_insurance it on t.item_id = it.hc_items_id and i.insurance_id = it.hc_insurance_id
            where i.ipd_include = 1 and i.invoice_status = 1 and c.id=" . $caseid .
            " order by Charge_type,Adate";
        $data['showinvoice2'] = $this->db->query($sql)->getResult();

        $sql = 'select m.*,m.id as med_id
                from invoice_med_master m join ipd_master p join organization_case_master o
                on m.ipd_id=p.id and p.case_id=o.id and m.ipd_credit=1 and m.ipd_credit_type=1
                where o.id=' . $caseid;
        $data['MedInvoice_data'] = $this->db->query($sql)->getResult();

        $sql = 'select sum(m.net_amount) as Med_Amount,o.id ,count(m.id) as no_inv
                from invoice_med_master m join ipd_master p join organization_case_master o
                on m.ipd_id=p.id and p.case_id=o.id and m.ipd_credit=1 and m.ipd_credit_type=1
                where o.id=' . $caseid . ' group by o.id';
        $data['MedInvoice'] = $this->db->query($sql)->getResult();

        if (count($data['MedInvoice']) === 0) {
            $sql = 'select m.* ,m.id as med_id
                    from invoice_med_master m join organization_case_master o
                    on m.case_id=o.id and m.case_credit=1
                    where o.id=' . $caseid;
            $data['MedInvoice_data'] = $this->db->query($sql)->getResult();

            $sql = 'select sum(m.net_amount) as Med_Amount,o.id ,count(m.id) as no_inv
                    from invoice_med_master m join organization_case_master o
                    on m.case_id=o.id and m.case_credit=1
                    where o.id=' . $caseid . ' group by o.id';
            $data['MedInvoice'] = $this->db->query($sql)->getResult();
        }

        $data['med_include'] = $medInclude;

        $sql = "select p.* ,date_format(payment_date,'%d-%m-%Y') as str_date,
                if(credit_debit=0,if(payment_mode=1,'CASH','BANK'),if(payment_mode=1,'CASH RETURN','BANK RETURN')) as Pay_mode
                from payment_history p
                where p.payof_type=3 and  p.payof_id=" . $caseid;
        $data['payment_history'] = $this->db->query($sql)->getResult();

        if ($print > 0) {
            return view('Invoice/caseinvoice_print_V', $data);
        }

        return view('Invoice/caseinvoice_V', $data);
    }

    public function case_invoice_ipd(int $caseid)
    {
        $data['orgcase'] = $this->db->table('organization_case_master')
            ->where('id', $caseid)
            ->get()
            ->getResult();

        if (empty($data['orgcase'])) {
            return $this->response->setStatusCode(404)->setBody('Case not found');
        }

        $pno = (int) $data['orgcase'][0]->p_id;
        $caseid = (int) $data['orgcase'][0]->id;

        $this->updateOrgTotals($caseid);


        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $pno . "'";
        $data['person_info'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['person_info'], 'age');

        $insCompId = (int) $data['orgcase'][0]->insurance_id;
        $incCardId = (int) $data['orgcase'][0]->insurance_card_id;

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')
            ->where('id', $incCardId)
            ->get()
            ->getResult();

        $data['insurance'] = $this->db->table('hc_insurance')
            ->where('id', $insCompId)
            ->get()
            ->getResult();

        $sql = "select c.id AS id,c.case_id_code AS case_id_code,c.p_id AS p_id,
            o.apointment_date AS s_Date,'OPD' AS Charge_type,0 AS Charge_type_id,
            o.opd_id AS item_id,0 AS master_item_id,o.apointment_date AS Adate,
            o.opd_fee_amount AS item_rate,1 AS item_qty,
            date_format(o.apointment_date,'%d-%m-%Y') AS str_date,
            concat('OPD Charge: Dr. ',o.doc_name) AS Description,
            o.opd_code AS Code,o.opd_fee_amount AS Amount,'1' AS orgcode
            from opd_master o
            join organization_case_master c on o.insurance_case_id = c.id
            where o.opd_status in (1,2) and c.id=" . $caseid .
            " order by Charge_type,Adate";
        $data['showinvoice1'] = $this->db->query($sql)->getResult();

        $sql = "select c.id AS id,c.case_id_code AS case_id_code,c.p_id AS p_id,
            i.inv_date AS s_Date,l.group_desc AS Charge_type,l.itype_id AS Charge_type_id,
            t.id AS item_id,t.item_id AS master_item_id,i.inv_date AS Adate,
            t.item_rate AS item_rate,t.item_qty AS item_qty,
            date_format(i.inv_date,'%d-%m-%Y') AS str_date,
            concat(t.item_name) AS Description,i.invoice_code AS Code,
            t.item_amount AS Amount,t.org_code AS orgcode
            from invoice_master i
            join organization_case_master c on i.insurance_case_id = c.id
            join invoice_item t on t.inv_master_id = i.id
            join hc_item_type l on t.item_type = l.itype_id
            left join hc_items_insurance it on t.item_id = it.hc_items_id and i.insurance_id = it.hc_insurance_id
            where i.ipd_include = 1 and i.invoice_status = 1 and c.id=" . $caseid .
            " order by Charge_type,Adate";
        $data['showinvoice2'] = $this->db->query($sql)->getResult();

        return view('Invoice/caseinvoice_ipd_V', $data);
    }

    public function ORG_info()
    {
        $orgNo = (string) $this->request->getPost('input_org_no');

        $sql = "select * from organization_case_master where case_type=0 and case_id_code='" . $orgNo . "' ";
        $dataOrg = $this->db->query($sql)->getResult();

        $orgId = 0;
        $orgInfo = '';

        if (! empty($dataOrg)) {
            $uhid = (int) $dataOrg[0]->p_id;

            $sql = "select * from patient_master where id='" . $uhid . "' ";
            $dataPerson = $this->db->query($sql)->getResult();

            $patientInfo = $dataPerson[0]->p_code . '/' . $dataPerson[0]->p_fname . ' ' . $dataPerson[0]->p_relative . ' ' . $dataPerson[0]->p_rname . ' /Age :' . ($dataPerson[0]->str_age ?? '');

            $orgInfo = $patientInfo . '/' . $dataOrg[0]->insurance_company_name .
                '/Reg.:' . $dataOrg[0]->insurance_no_1 . '/Reg.Dt:' . $dataOrg[0]->date_registration;

            $orgId = (int) $dataOrg[0]->id;
        }

        return $this->response->setJSON([
            'org_id' => $orgId,
            'org_info' => $orgInfo,
        ]);
    }

    private function updateOrgTotals(int $caseId): void
    {
        $caseRow = $this->db->table('organization_case_master')
            ->select('case_type')
            ->where('id', $caseId)
            ->get()
            ->getRow();

        if (! $caseRow || (int) $caseRow->case_type !== 0) {
            return;
        }

        $opdFeeRow = $this->db->table('opd_master')
            ->selectSum('opd_fee_amount', 'total')
            ->whereIn('opd_status', [1, 2])
            ->where('insurance_case_id', $caseId)
            ->get()
            ->getRow();
        $opdFee = (float) ($opdFeeRow->total ?? 0);

        $medRow = $this->db->table('invoice_med_master')
            ->selectSum('net_amount', 'total')
            ->where('case_id', $caseId)
            ->get()
            ->getRow();
        $medTotal = (float) ($medRow->total ?? 0);

        $chargeRow = $this->db->table('invoice_master')
            ->selectSum('net_amount', 'total')
            ->where('invoice_status', 1)
            ->where('insurance_case_id', $caseId)
            ->get()
            ->getRow();
        $chargeTotal = (float) ($chargeRow->total ?? 0);

        $this->db->table('organization_case_master')
            ->where('id', $caseId)
            ->update([
                'inv_opd_amt' => $opdFee,
                'inv_opd_charge_amt' => $chargeTotal,
                'inv_opd_med_amt' => $medTotal,
            ]);
    }

    public function update_itemrateqty()
    {
        $itemTypeId = (int) $this->request->getPost('item_type_id');
        $itemId = (int) $this->request->getPost('item_id');
        $rate = (float) $this->request->getPost('rate');
        $orgcode = (string) $this->request->getPost('orgcode');
        $insuranceId = (int) $this->request->getPost('insurance_id');
        $masterItemId = (int) $this->request->getPost('master_item_id');

        $invoiceItem = $this->db->table('invoice_item')
            ->where('id', $itemId)
            ->get()
            ->getResult();

        $qty = 0;
        if (! empty($invoiceItem)) {
            $qty = (float) $invoiceItem[0]->item_qty;
        }

        $amountValue = $qty * $rate;

        $invoiceModel = new InvoiceModel();
        $invoiceModel->updateItem([
            'item_qty' => $qty,
            'item_rate' => $rate,
            'item_amount' => $amountValue,
            'org_code' => $orgcode,
        ], $itemId);

        if ($insuranceId === 2) {
            $existing = $this->db->table('hc_items_insurance')
                ->where('hc_items_id', $masterItemId)
                ->where('hc_insurance_id', $insuranceId)
                ->get()
                ->getResult();

            $insData = [
                'hc_items_id' => $masterItemId,
                'hc_insurance_id' => $insuranceId,
                'amount1' => $amountValue,
                'code' => $orgcode,
            ];

            if (! empty($existing)) {
                $this->db->table('hc_items_insurance')
                    ->where('id', $existing[0]->id)
                    ->update($insData);
            } else {
                $this->db->table('hc_items_insurance')->insert($insData);
            }
        }

        $invoiceId = 0;
        if (! empty($invoiceItem)) {
            $invoiceId = (int) $invoiceItem[0]->inv_master_id;
        }

        $data['invoiceDetails'] = $this->db->query('select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=' . $invoiceId)->getResult();
        $data['invoiceGtotal'] = $this->db->table('invoice_item')->selectSum('item_amount', 'Gtotal')->where('inv_master_id', $invoiceId)->get()->getResult();
        $data['invoice_master'] = $this->db->query("select *,(case payment_mode when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'IPD Credit' when 4 then 'Org. Credit' else 'Pending' end) as Payment_type_str from invoice_master where id=" . $invoiceId)->getResult();

        $discountAmount = (float) ($data['invoice_master'][0]->discount_amount ?? 0);
        $correctionAmount = (float) ($data['invoice_master'][0]->correction_amount ?? 0);
        $totalAmount = (float) ($data['invoiceGtotal'][0]->Gtotal ?? 0);
        $netAmount = $totalAmount - $discountAmount - $correctionAmount;

        $invoiceModel->updateInvoice([
            'total_amount' => $totalAmount,
            'net_amount' => $netAmount,
        ], $invoiceId);
    }

    public function update_status()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setBody('Invalid request');
        }

        $caseId = (int) $this->request->getPost('caseid');
        $status = (int) $this->request->getPost('status');

        $user = auth()->user();
        $userId = $user->id ?? '';
        $userName = $user->username ?? $user->email ?? 'User';

        $caseMaster = $this->db->table('organization_case_master')
            ->where('id', $caseId)
            ->get()
            ->getResult();

        $log = '';
        if (! empty($caseMaster)) {
            $log = (string) ($caseMaster[0]->log ?? '');
        }
        $log .= '\nUpdate Status:' . $status . '-Upd By:' . $userName . '[' . $userId . ']' . date('d/m/Y H:i:s');

        $data = [
            'status' => $status,
            'log' => $log,
        ];

        if ($status === 1) {
            $orgSubmitDate = str_to_MysqlDate((string) $this->request->getPost('org_submit_date'));
            $data['org_submit_date'] = $orgSubmitDate;
        }

        $caseModel = new OrganizationCaseModel();
        $caseModel->updateCase($data, $caseId);
    }

    public function org_contingent_update()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setBody('Invalid request');
        }

        $user = auth()->user();
        $userName = $user->username ?? $user->email ?? 'User';

        $htmlData = (string) $this->request->getPost('HTMLData');
        $caseId = (int) $this->request->getPost('case_id');

        $caseModel = new OrganizationCaseModel();
        $caseModel->updateCase([
            'contingent_bill' => $htmlData,
            'contingent_update_by' => $userName,
        ], $caseId);

        return $this->response->setJSON([
            'update' => 1,
            'msg' => 'Update Done ' . $caseId,
        ]);
    }

    public function case_invoice_print(int $caseid)
    {
        $data['orgcase'] = $this->db->table('organization_case_master')
            ->where('id', $caseid)
            ->get()
            ->getResult();

        if (empty($data['orgcase'])) {
            return $this->response->setStatusCode(404)->setBody('Case not found');
        }

        $pno = (int) $data['orgcase'][0]->p_id;

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $pno . "'";
        $data['person_info'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['person_info'], 'age');

        $insCompId = (int) $data['orgcase'][0]->insurance_id;
        $incCardId = (int) $data['orgcase'][0]->insurance_card_id;

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')
            ->where('id', $incCardId)
            ->get()
            ->getResult();

        $data['insurance'] = $this->db->table('hc_insurance')
            ->where('id', $insCompId)
            ->get()
            ->getResult();

        $data['showinvoice'] = $this->db->query('select * from v_case_invoice v where v.id=' . $caseid . ' order by s_Date')->getResult();
        $data['invoiceGtotal'] = $this->db->query('select sum(amount) as GTotal from v_case_invoice v where v.id=' . $caseid)->getResult();

        return view('Invoice/caseinvoice_V', $data);
    }

    public function getCaseTable()
    {
        $requestData = $_REQUEST;

        $columns = [
            0 => 'case_id_code',
            1 => 'Reg_No',
            2 => 'p_f_info',
            3 => 'date_registration',
            4 => 'insurance_card_name',
            5 => 'insurance_company_name',
            6 => 'str_status',
        ];

        $sqlAll = "select c.id,c.p_id,c.case_id_code,Concat(c.insurance_no,'/',c.insurance_no_1) as Reg_No,
        c.insurance_company_name,Concat(c.p_name,'<br/>','[',p_code,if(i.id is null,'',concat('/',i.ipd_code)),']') as p_f_info,p.p_code,
        c.date_registration,c.insurance_card_name,c.status,
        (case c.status when 0 then 'Pending' when 1 then 'Ready for submission' when 2 then 'submitted' else 'Other' end) as str_status,c.insurance_id,
        Date_Format(c.date_registration,'%d-%m-%Y') as date_registration_in ,i.ipd_code ";

        $sqlCount = 'Select count(*) as no_rec ';
        $sqlFrom = ' from ((organization_case_master c join patient_master p  on c.p_id=p.id ) left join ipd_master i on c.id=i.case_id  and   p.id=i.p_id) ';

        $totalSql = $sqlCount . $sqlFrom;
        $totalData = (int) ($this->db->query($totalSql)->getResult()[0]->no_rec ?? 0);
        $totalFiltered = $totalData;

        $sqlWhere = ' WHERE c.case_type=0 ';

        if (! empty($requestData['columns'][0]['search']['value'])) {
            $sqlWhere .= " AND case_id_code LIKE '%" . $requestData['columns'][0]['search']['value'] . "' ";
        }

        if (! empty($requestData['columns'][1]['search']['value'])) {
            $term = $requestData['columns'][1]['search']['value'];
            $sqlWhere .= " AND ( p.p_fname LIKE '%" . $term . "%' ";
            $sqlWhere .= " OR p.mphone1 LIKE '" . $term . "' ";
            $sqlWhere .= " OR i.ipd_code LIKE '%" . $term . "' ";
            $sqlWhere .= " OR p_code LIKE '%" . $term . "%' )";
        }

        if (! empty($requestData['columns'][2]['search']['value'])) {
            $sqlWhere .= " AND c.insurance_card_name LIKE '%" . $requestData['columns'][2]['search']['value'] . "%' ";
        }

        if (! empty($requestData['columns'][3]['search']['value'])) {
            if ($requestData['columns'][3]['search']['value'] < 0) {
                $sqlWhere .= ' AND c.insurance_id not in (1,2,53,63) ';
            }
            if ($requestData['columns'][3]['search']['value'] > 0) {
                $sqlWhere .= " AND c.insurance_id = '" . $requestData['columns'][3]['search']['value'] . "' ";
            }
        }

        if (! empty($requestData['columns'][4]['search']['value'])) {
            $sqlWhere .= " AND ( c.insurance_no LIKE '" . $requestData['columns'][4]['search']['value'] . "%' ";
            $sqlWhere .= " OR c.insurance_no_1 LIKE '" . $requestData['columns'][4]['search']['value'] . "%' ) ";
        }

        if (! empty($requestData['columns'][5]['search']['value'])) {
            $sqlWhere .= " AND c.status = '" . $requestData['columns'][5]['search']['value'] . "' ";
        }

        $totalFilterSql = $sqlCount . $sqlFrom . $sqlWhere;
        $totalFiltered = (int) ($this->db->query($totalFilterSql)->getResult()[0]->no_rec ?? 0);

        $orderSql = ' group by c.id ORDER BY ' . $columns[$requestData['order'][0]['column']] . ' ' . $requestData['order'][0]['dir']
            . ' LIMIT ' . $requestData['start'] . ' ,' . $requestData['length'];

        $resultSql = $sqlAll . $sqlFrom . $sqlWhere . $orderSql;
        $rdata = $this->db->query($resultSql)->getResultArray();

        $output = [
            'draw' => (int) $requestData['draw'],
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => [],
            'sql' => $resultSql,
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

    public function contingent_bill(int $caseid, int $print = 0)
    {
        $data['orgcase'] = $this->db->table('organization_case_master')
            ->where('id', $caseid)
            ->get()
            ->getResult();

        if (empty($data['orgcase'])) {
            return $this->response->setStatusCode(404)->setBody('Case not found');
        }

        $pno = (int) $data['orgcase'][0]->p_id;

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $pno . "'";
        $data['person_info'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['person_info'], 'age');

        $insCompId = (int) $data['orgcase'][0]->insurance_id;
        $incCardId = (int) $data['orgcase'][0]->insurance_card_id;

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')
            ->where('id', $incCardId)
            ->get()
            ->getResult();

        $data['insurance'] = $this->db->table('hc_insurance')
            ->where('id', $insCompId)
            ->get()
            ->getResult();

        if ($print === 1) {
            return $this->response->setBody((string) ($data['orgcase'][0]->contingent_bill ?? ''));
        }

        return view('Invoice/Org_Contingent_bill', $data);
    }

    public function create_contingent_bill(int $caseid)
    {
        $data['orgcase'] = $this->db->table('organization_case_master')
            ->where('id', $caseid)
            ->get()
            ->getResult();

        if (empty($data['orgcase'])) {
            return $this->response->setStatusCode(404)->setBody('Case not found');
        }

        $pno = (int) $data['orgcase'][0]->p_id;

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $pno . "'";
        $data['person_info'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['person_info'], 'age');

        $insCompId = (int) $data['orgcase'][0]->insurance_id;
        $incCardId = (int) $data['orgcase'][0]->insurance_card_id;

        $data['hc_insurance_card'] = $this->db->table('hc_insurance_card')
            ->where('id', $incCardId)
            ->get()
            ->getResult();

        $data['insurance'] = $this->db->table('hc_insurance')
            ->where('id', $insCompId)
            ->get()
            ->getResult();

        $sql = 'select * from v_case_invoice v where v.id=' . $caseid . ' order by Charge_type,Adate';
        $data['showinvoice'] = $this->db->query($sql)->getResult();

        $data['invoiceGtotal'] = $this->db->query('select sum(amount) as GTotal from v_case_invoice v where v.id=' . $caseid)->getResult();

        $claimId = (string) ($data['orgcase'][0]->insurance_no_1 ?? '');

        $content = '<style>@page {margin-top: 0.5cm;margin-bottom: 0.5cm;margin-left: 0.5cm;margin-right: 0.5cm;margin-header:0.5cm;margin-footer:0.5cm;header: html_myHeader;footer: html_myFooter;}</style>';
        $content .= '<h3 style="text-align:center; vertical-align:middle">CLAIM ID : ' . $claimId . '</h3>';
        $content .= '<p><b>Name of Hospital : </b>' . H_Name . '</p>';
        $content .= '<h3 style="text-align:center; vertical-align:middle">CONTINGENT BILL</h3>';

        $content .= '<table border="0" cellpadding="1" cellspacing="1" width="100%">';
        $content .= '<tr><td colspan="2">Voucher No. ______________________________</td><td>Date : ………………………</td></tr>';
        $content .= '<tr><td colspan="2">Amount of allotment : </td><td>Rs.……………………………</td></tr>';
        $content .= '<tr><td colspan="2">Amount expended and for which bills have already been submitted for payment :</td><td>Rs.……………………………</td></tr>';
        $content .= '<tr><td colspan="2">Balance of allotment excliding the amount of this bills :</td><td>Rs.……………………………</td></tr>';
        $content .= '</table>';
        $content .= '<p>Expenditure an account of : Treatment/ Super Treatment/ Special investigation / Consultant fee</p>';

        $content .= '<table border="0" cellpadding="1" cellspacing="1" width="100%">';
        $content .= '<tr>';
        $content .= '<td style="width:200px;">In respect of<br><b>Name of Patient: ' . strtoupper((string) $data['orgcase'][0]->p_name) . '</b></td>';
        $content .= '<td>Relation : ' . strtoupper((string) $data['orgcase'][0]->relation_with_cardholder) . '</td>';
        $content .= '<td>Service No.: <b>' . strtoupper((string) $data['orgcase'][0]->insurance_no_2) . '</b></td>';
        $content .= '</tr>';
        $content .= '<tr>';
        $content .= '<td>ESM Name : <b>' . strtoupper((string) $data['orgcase'][0]->insurance_card_name) . '</b></td>';
        $content .= '<td>ECHS CARD No. : <b>' . strtoupper((string) $data['orgcase'][0]->insurance_no) . '</b></td>';
        $content .= '<td>Rank : ' . strtoupper((string) $data['orgcase'][0]->card_remark) . '</td>';
        $content .= '</tr>';
        $content .= '</table>';

        $content .= '<p><B>Authority:</B><br/>';
        $content .= 'Government of India letter No. 24 (3)/03/US/(WE)/D (Res) (i) dated 08 Sep 2003 <br/>';
        $content .= 'Government of India letter No. 24 (8)/03/US/(WE)/D (Res) dated 19 Dec 2003 <br/>';
        $content .= 'Central Organization ECHS Letter NoB/49773/AG/ECHS dated 25 May 2004.</p>';

        $opd = $this->db->query('select c.id,c.case_id_code,c.p_id,sum(o.opd_fee_amount) as tamount,
            count(o.opd_id) as item_qty,Min(o.opd_fee_amount) as rate
            from opd_master o join organization_case_master c on o.insurance_case_id=c.id
            where opd_status in (1,2) and  id=' . $caseid . ' group by c.id')->getResult();

        $data['invoice_list_1'] = $this->db->query("select i.item_type,t.`desc` AS item_name,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,i.item_rate
                from invoice_master m join invoice_item i join hc_item_type t
                on m.id=i.inv_master_id and i.item_type=t.itype_id
                where item_type=34 and m.ipd_include=1 and invoice_status=1 and
                insurance_case_id=$caseid
                group by i.item_type")->getResult();

        $data['invoice_list_2'] = $this->db->query("select i.item_type,t.`desc` AS item_name,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,i.item_rate
                from invoice_master m join invoice_item i join hc_item_type t
                on m.id=i.inv_master_id and i.item_type=t.itype_id
                where item_type not in (34,35) and m.ipd_include=1 and invoice_status=1 and
                insurance_case_id=$caseid
                group by i.item_type")->getResult();

        $data['invoice_list_3'] = $this->db->query("select i.item_type,t.`desc` AS item_name,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,i.item_rate
                from invoice_master m join invoice_item i join hc_item_type t
                on m.id=i.inv_master_id and i.item_type=t.itype_id
                where item_type=35 and m.ipd_include=1 and invoice_status=1 and
                insurance_case_id=$caseid
                group by i.item_type")->getResult();

        $data['ipd_invoice_list_1'] = $this->db->query("select i.item_type,i.item_name,t.group_desc,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,
                i.item_rate
                    FROM (ipd_master m join ipd_invoice_item i on m.id=i.ipd_id)
                         join ipd_item_type t  ON i.item_type=t.itype_id
                     and i.item_type=t.itype_id
                    WHERE  i.package_id=0 and t.itype_id=2 AND  m.case_id=$caseid
                    group by i.item_type")->getResult();

        $data['ipd_invoice_list_2'] = $this->db->query("select i.item_type,i.item_name,t.group_desc,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,
                i.item_rate
                    FROM (ipd_master m join ipd_invoice_item i on m.id=i.ipd_id)
                         join ipd_item_type t  ON i.item_type=t.itype_id
                     and i.item_type=t.itype_id
                    WHERE  i.package_id=0 and t.itype_id not in (2,6) AND  m.case_id=$caseid
                    group by i.item_type")->getResult();

        $data['ipd_invoice_list_3'] = $this->db->query("select i.item_type,i.item_name,t.group_desc,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,
                i.item_rate
                    FROM (ipd_master m join ipd_invoice_item i on m.id=i.ipd_id)
                         join ipd_item_type t  ON i.item_type=t.itype_id
                     and i.item_type=t.itype_id
                    WHERE  i.package_id=0 and t.itype_id=6 AND  m.case_id=$caseid
                    group by i.item_type")->getResult();

        $data['MedInvoice'] = $this->db->query('select sum(m.net_amount) as Med_Amount,o.id ,count(m.id) as no_inv
                from invoice_med_master m join ipd_master p join organization_case_master o
                on m.ipd_id=p.id and p.case_id=o.id and m.ipd_credit=1 and m.ipd_credit_type=1
                where o.id=' . $caseid . ' group by o.id')->getResult();

        $data['ipd_package'] = $this->db->query('SELECT p.* FROM  ipd_package p JOIN ipd_master m ON p.ipd_id=m.id WHERE m.case_id=' . $caseid)->getResult();

        if (count($data['MedInvoice']) === 0) {
            $data['MedInvoice'] = $this->db->query('select sum(m.net_amount) as Med_Amount,o.id ,count(m.id) as no_inv
                from invoice_med_master m join organization_case_master o
                on m.case_id=o.id and m.case_credit=1
                where o.id=' . $caseid . ' group by o.id')->getResult();
        }

        $ipdMaster = $this->db->query('select p.* from ipd_master p join organization_case_master o on  p.case_id=o.id where o.id=' . $caseid)->getResult();

        $tableHead = '<table border="1" cellpadding="1" cellspacing="0" style="width:100%">'
            . '<tr><th style="width:50px">Sr.No.</th><th style="width:100px">Date</th><th>Details of Expenditure</th><th colspan="2">Number or Quantity</th><th style="width:100px;align:right;">Rate</th><th style="width:100px;align:right;">Per</th><th style="width:100px;align:right;">Amount</th></tr>';

        $tbody = '';
        $srno = 0;
        $totalGrossBill = 0.00;

        if (! empty($opd)) {
            $srno++;
            $tbody .= '<tr>';
            $tbody .= '<td>' . $srno . '</td><td>' . date('Y-m-d') . '</td><td>OPD</td>';
            $tbody .= '<td style="text-align:right">' . $opd[0]->rate . '</td><td style="text-align:right">' . $opd[0]->item_qty . '</td>';
            $tbody .= '<td style="text-align:right">' . $opd[0]->rate . '</td><td>CGHS</td><td style="text-align:right">' . $opd[0]->tamount . '</td>';
            $tbody .= '</tr>';
            $totalGrossBill += $opd[0]->tamount;
        }

        foreach ($data['ipd_package'] as $row) {
            $srno++;
            $tbody .= '<tr>';
            $tbody .= '<td>' . $srno . '</td><td>' . date('Y-m-d') . '</td><td>Package :' . $row->package_name . '</td>';
            $tbody .= '<td style="text-align:right">' . $row->package_Amount . '</td><td style="text-align:right">1</td>';
            $tbody .= '<td style="text-align:right">' . $row->package_Amount . '</td><td>CGHS</td><td style="text-align:right">' . $row->package_Amount . '</td>';
            $tbody .= '</tr>';
            $totalGrossBill += $row->package_Amount;
        }

        $lists = ['invoice_list_1', 'ipd_invoice_list_1', 'invoice_list_2', 'ipd_invoice_list_2', 'invoice_list_3', 'ipd_invoice_list_3'];
        foreach ($lists as $listKey) {
            foreach ($data[$listKey] as $row) {
                if ($row->item_type !== '') {
                    $srno++;
                    $tbody .= '<tr>';
                    $tbody .= '<td>' . $srno . '</td><td>' . date('Y-m-d') . '</td><td>' . $row->item_name . '</td>';
                    $tbody .= '<td style="text-align:right">' . $row->item_rate . '</td><td style="text-align:right">' . $row->unit . '</td>';
                    $tbody .= '<td style="text-align:right">' . $row->item_rate . '</td><td>CGHS</td><td style="text-align:right">' . $row->tamount . '</td>';
                    $tbody .= '</tr>';
                    $totalGrossBill += $row->tamount;
                }
            }
        }

        if (! empty($data['MedInvoice'])) {
            $srno++;
            $tbody .= '<tr>';
            $tbody .= '<td>' . $srno . '</td><td>' . date('Y-m-d') . '</td><td>Medicine</td>';
            $tbody .= '<td style="text-align:right">' . $data['MedInvoice'][0]->Med_Amount . '</td><td style="text-align:right">' . $data['MedInvoice'][0]->no_inv . '</td>';
            $tbody .= '<td style="text-align:right">' . $data['MedInvoice'][0]->Med_Amount . '</td><td></td><td style="text-align:right">' . $data['MedInvoice'][0]->Med_Amount . '</td>';
            $tbody .= '</tr>';
            $totalGrossBill += $data['MedInvoice'][0]->Med_Amount;
        }

        $tbody .= '<tr><td colspan="5"></td><td colspan="2">Gross Total </td><td style="text-align:right">' . round($totalGrossBill, 0) . '</td></tr>';

        if (! empty($ipdMaster)) {
            if ((float) $ipdMaster[0]->chargeamount1 > 0) {
                $tbody .= '<tr><td colspan="5"></td><td colspan="2">' . $ipdMaster[0]->charge1 . '</td><td style="text-align:right">' . $ipdMaster[0]->chargeamount1 . '</td></tr>';
                $totalGrossBill += (float) $ipdMaster[0]->chargeamount1;
            }
            if ((float) $ipdMaster[0]->chargeamount2 > 0) {
                $tbody .= '<tr><td colspan="5"></td><td colspan="2">' . $ipdMaster[0]->charge2 . '</td><td style="text-align:right">' . $ipdMaster[0]->chargeamount2 . '</td></tr>';
                $totalGrossBill += (float) $ipdMaster[0]->chargeamount2;
            }
            if ((float) $ipdMaster[0]->Discount > 0) {
                $tbody .= '<tr><td colspan="5"></td><td colspan="2">' . $ipdMaster[0]->Discount_Remark . '</td><td style="text-align:right">' . $ipdMaster[0]->Discount . '</td></tr>';
                $totalGrossBill -= (float) $ipdMaster[0]->Discount;
            }
            if ((float) $ipdMaster[0]->Discount2 > 0) {
                $tbody .= '<tr><td colspan="5"></td><td colspan="2">' . $ipdMaster[0]->Discount_Remark2 . '</td><td style="text-align:right">' . $ipdMaster[0]->Discount3 . '</td></tr>';
                $totalGrossBill -= (float) $ipdMaster[0]->Discount2;
            }
            if ((float) $ipdMaster[0]->Discount3 > 0) {
                $tbody .= '<tr><td colspan="5"></td><td colspan="2">' . $ipdMaster[0]->Discount_Remark3 . '</td><td style="text-align:right">' . $ipdMaster[0]->Discount3 . '</td></tr>';
                $totalGrossBill -= (float) $ipdMaster[0]->Discount3;
            }
        }

        $tableFooter = '<tr><td colspan="5"></td><td colspan="2"><b>Net Total </b></td><td style="text-align:right"><b>' . round($totalGrossBill, 0) . '</b></td></tr>';
        $tableFooter .= '</table>';

        $content .= $tableHead . $tbody . $tableFooter;
        $content .= '<p>Net Amount due(In words) : Rs. ' . number_to_word(round($totalGrossBill, 0)) . ' </p>';
        $content .= '<table border=0 width="100%"><tr><td> </td><td>Date : ......................</td><td></td><td>Pre Received Payment</td></tr>';
        $content .= '<tr><td> </td><td>From : ......................</td><td></td><td>.....................</td></tr></table>';
        $content .= '<p><B>Certified that</B><br/>';
        $content .= '<ol><li>Certified that above have been claimed on the basis of approved charges as noted in MOA signed for the treatment of ECHS member. </li>';
        $content .= '<li>The rate is/are fair and reasonable and less than CGHS rates</li>';
        $content .= '<li>The expenditure incurred is debatable to major Head 2076 Minor head 107, Sub Head E-Medical treatment related expenditure code  head 365/00.</li>';
        $content .= '<li>The expenditure has been incurred the interest of Ex-Serviceman</li></ol></p>';
        $content .= '<table border=0 width="100%"><tr><td width="150px">Vetted and Recommended / Not Recommended</td><td></td><td>Sig of OIC ECHS Polyclinic</td></tr>';
        $content .= '<tr><td><br>Date : ......................<br><br>Place: ......................</td><td></td><td>.....................</td></tr></table>';

        $caseModel = new OrganizationCaseModel();
        $caseModel->updateCase(['contingent_bill' => $content], $caseid);

        return $this->response->setBody('Created');
    }

    public function org_confirm_payment()
    {
        $amountR = (float) $this->request->getPost('amount_r');
        $amountD = (float) $this->request->getPost('amount_d');
        $amountClaim = (float) $this->request->getPost('amount_claim');
        $amountTds = (float) $this->request->getPost('amount_tds');
        $datePayment = str_to_MysqlDate((string) $this->request->getPost('date_payment'));
        $payInfo = (string) $this->request->getPost('pay_info');
        $orgId = (int) $this->request->getPost('org_id');

        $user = auth()->user();
        $userId = $user->id ?? '';
        $userName = $user->username ?? $user->email ?? 'User';
        $updateBy = $userName . '[' . $userId . ']' . date('d-m-Y H:i:s');

        $caseModel = new OrganizationCaseModel();
        $caseModel->updateCase([
            'pay_date_received' => $datePayment,
            'amount_recived' => $amountR,
            'amount_deduction' => $amountD,
            'amount_claim' => $amountClaim,
            'amount_tds' => $amountTds,
            'amount_payment_info' => $payInfo,
            'status' => '3',
            'amount_update_by' => $updateBy,
        ], $orgId);

        return $this->response->setJSON([
            'update' => 1,
            'showcontent' => 'Update',
        ]);
    }

    public function org_payment_request()
    {
        $data['org_info'] = $this->db->table('org_payment_request')->get()->getResult();
        if (empty($data['org_info'])) {
            return $this->response->setStatusCode(404)->setBody('No payment requests');
        }

        $orgId = (int) $data['org_info'][0]->id;

        $data['ipd_info'] = $this->db->query("select * from v_ipd_list where org_id='" . $orgId . "' ")->getResult();

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $data['org_info'][0]->p_id . "' ";
        $data['person_info'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['person_info'], 'str_age');

        return view('Invoice/org_case_payment', $data);
    }

    public function load_model_box(string $caseidcode)
    {
        $data['org_info'] = $this->db->query("select * from organization_case_master where case_id_code='" . $caseidcode . "' ")->getResult();
        if (empty($data['org_info'])) {
            return $this->response->setStatusCode(404)->setBody('Case not found');
        }

        $orgId = (int) $data['org_info'][0]->id;

        $data['ipd_info'] = $this->db->query("select * from v_ipd_list where org_id='" . $orgId . "' ")->getResult();

        $sql = "select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $data['org_info'][0]->p_id . "' ";
        $data['person_info'] = $this->db->query($sql)->getResult();
        $this->applyAge($data['person_info'], 'str_age');

        return view('Invoice/org_case_payment', $data);
    }

    public function payment_request()
    {
        $inputPayAmount = (float) $this->request->getPost('input_pay_amount');
        $caseidcode = (int) $this->request->getPost('caseid');
        $inputRemark = (string) $this->request->getPost('input_remark');

        $user = auth()->user();
        $userId = $user->id ?? '';
        $userName = $user->username ?? $user->email ?? 'User';
        $userLabel = $userName . '[' . $userId . ']' . date('d-m-Y H:i:s');

        $orgInfo = $this->db->table('organization_case_master')
            ->where('id', $caseidcode)
            ->get()
            ->getResult();

        $orgPaymentRequest = $this->db->table('org_payment_request')
            ->where('payment_process', 0)
            ->where('org_id', $caseidcode)
            ->get()
            ->getResult();

        $patientId = 0;
        $orgDate = date('Y-m-d H:i:s');
        $patientName = '';
        $orgCode = '';

        if (! empty($orgInfo)) {
            $patientId = (int) $orgInfo[0]->p_id;
            $personInfo = $this->db->query("select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $patientId . "' ")->getResult();
            $this->applyAge($personInfo, 'str_age');
            $orgDate = $orgInfo[0]->insert_datetime;
            $patientName = $personInfo[0]->p_fname ?? '';
            $orgCode = $orgInfo[0]->case_id_code ?? '';
        }

        $payData = [
            'org_id' => $caseidcode,
            'org_code' => $orgCode,
            'patient_id' => $patientId,
            'org_date' => $orgDate,
            'patient_name' => $patientName,
            'payment_amount' => $inputPayAmount,
            'payment_request_datetime' => date('Y-m-d H:i:s'),
            'request_by_id' => $userId,
            'request_by' => $userLabel,
            'remark' => $inputRemark,
        ];

        if (count($orgPaymentRequest) < 1) {
            $this->db->table('org_payment_request')->insert($payData);
            $insertId = (int) $this->db->insertID();
        } else {
            $this->db->table('org_payment_request')
                ->where('id', $orgPaymentRequest[0]->id)
                ->update($payData);
            $insertId = (int) $orgPaymentRequest[0]->id;
        }

        return $this->response->setBody('Request has been Created : Req. ID -> ' . $insertId);
    }

    public function refund_request()
    {
        $inputRefundAmount = (float) $this->request->getPost('input_refund_amount');
        $caseidcode = (int) $this->request->getPost('caseid');
        $inputRemark = (string) $this->request->getPost('input_refund_remark');

        $user = auth()->user();
        $userId = $user->id ?? '';
        $userName = $user->username ?? $user->email ?? 'User';
        $userLabel = $userName . '[' . $userId . ']' . date('d-m-Y H:i:s');

        $orgInfo = $this->db->table('organization_case_master')
            ->where('id', $caseidcode)
            ->get()
            ->getResult();

        $refundOrder = $this->db->table('refund_order')
            ->where('refund_process', 0)
            ->where('refund_type', 3)
            ->where('refund_type_id', $caseidcode)
            ->get()
            ->getResult();

        $patientId = 0;
        $orgDate = date('Y-m-d H:i:s');
        $patientName = '';
        $orgCode = '';

        if (! empty($orgInfo)) {
            $patientId = (int) $orgInfo[0]->p_id;
            $personInfo = $this->db->query("select *,if(gender=1,'Male','Female') as xgender from patient_master where id='" . $patientId . "' ")->getResult();
            $this->applyAge($personInfo, 'str_age');
            $orgDate = $orgInfo[0]->insert_datetime;
            $patientName = $personInfo[0]->p_fname ?? '';
            $orgCode = $orgInfo[0]->case_id_code ?? '';
        }

        $refundRequest = [
            'refund_type' => 3,
            'refund_type_id' => $caseidcode,
            'refund_type_code' => $orgCode,
            'refund_type_reason' => $inputRemark,
            'approved_by_id' => $userId,
            'approved_by' => $userLabel,
            'refund_amount' => $inputRefundAmount,
            'patient_id' => $patientId,
            'patient_name' => $patientName,
        ];

        if (count($refundOrder) < 1) {
            $this->db->table('refund_order')->insert($refundRequest);
            $insertId = (int) $this->db->insertID();
            return $this->response->setBody('Refund Request has been Created : Req. ID -> ' . $insertId);
        }

        return $this->response->setBody('Refund Request Pending, Please Refund or Cancel Last Refund Request');
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
