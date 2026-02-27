<?php

namespace App\Models;

use CodeIgniter\Model;

class IpdBillingModel extends Model
{
    protected $table = 'v_ipd_list';

    public function getCurrentAdmissions(): array
    {
        $builder = $this->baseIpdListQuery();
        $builder->select("i.id,i.ipd_code,p.p_code,p.p_fname,p.p_rname,")
            ->select("concat('Bed No :',b.bed_number,'[',w.ward_name,']') as Bed_Desc,")
            ->select("date_format(i.register_date,'%d-%m-%Y') as str_register_date,")
            ->select("if(i.ipd_status = 0,(to_days(sysdate()) - to_days(i.register_date)),(to_days(i.discharge_date) - to_days(i.register_date))) as no_days,")
            ->select("ipd_doc_list.doc_name as doc_name,")
            ->select("if((o.id is not null),in_master.short_name,'Direct') as admit_type,")
            ->select("if((o.id is not null),concat(s.app_status,'/',o.org_approved_amount),'') as Org_Status,")
            ->select("o.insurance_no_1 as insurance_no_1,")
            ->select("i.charge_amount as charge_amount,i.med_amount as med_amount,i.total_paid_amount as paid_amount,")
            ->select("(i.total_paid_amount - (i.charge_amount + i.med_amount)) as balance,")
            ->select("s.color as color", false)
            ->where('i.ipd_status', 0);

        return $builder->get()->getResult();
    }

    public function getIpdTableData(array $request): array
    {
        $columns = [
            'ipd_code',
            'p_code',
            'p_fname',
            'admit_type',
            'insurance_no_1',
            'doc_name',
            'Disstatus',
            'str_register_date',
            'str_discharge_date',
            'status_desc',
        ];

        $totalBuilder = $this->baseIpdListQuery();
        $totalData = $totalBuilder->countAllResults();

        $filteredBuilder = $this->baseIpdListQuery();
        $this->applyDataTableFilters($filteredBuilder, $request);
        $totalFiltered = $filteredBuilder->countAllResults();

        $dataBuilder = $this->baseIpdListQuery();
        $this->applyDataTableFilters($dataBuilder, $request);

        $orderColumn = $columns[0];
        $orderDir = 'desc';
        if (! empty($request['order'][0]['column']) && is_numeric($request['order'][0]['column'])) {
            $orderIndex = (int) $request['order'][0]['column'];
            $orderColumn = $columns[$orderIndex] ?? $columns[0];
            $orderDir = ($request['order'][0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        }

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 10;

        $rows = $dataBuilder
            ->select(
                "i.ipd_code,p.p_code,p.p_fname,
                if((o.id is not null),in_master.short_name,'Direct') as admit_type,
                o.insurance_no_1 as insurance_no_1,
                ipd_doc_list.doc_name as doc_name,
                if(i.ipd_status = 1,'Discharged','Admit') as Disstatus,
                date_format(i.register_date,'%d-%m-%Y') as str_register_date,
                date_format(i.discharge_date,'%d-%m-%Y') as str_discharge_date,
                d.status_desc as status_desc",
                false
            )
            ->orderBy($orderColumn, $orderDir)
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $data = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($columns as $column) {
                $item[] = $row[$column] ?? '';
            }
            $data[] = $item;
        }

        return [
            'draw' => (int) ($request['draw'] ?? 0),
            'recordsTotal' => (int) $totalData,
            'recordsFiltered' => (int) $totalFiltered,
            'data' => $data,
        ];
    }

    public function getCashBalanceReport(string $start, string $end): array
    {
        $builder = $this->baseIpdListQuery();
        $builder
            ->select("i.ipd_code,p.p_fname,date_format(i.register_date,'%d-%m-%Y') as str_register_date,")
            ->select("p.p_code,concat(i.contact_person_Name,'M:',i.P_mobile1,' ;',p.mphone1,' ;',i.P_mobile2) as Contact_info,")
            ->select("date_format(i.discharge_date,'%d-%m-%Y') as str_discharge_date,")
            ->select("if((o.id is not null),in_master.short_name,'Direct') as admit_type,")
            ->select("ipd_doc_list.doc_name as doc_name,")
            ->select("concat('Bed No :',b.bed_number,'[',w.ward_name,']') as Bed_Desc,")
            ->select("if((o.id is not null),'0','1') as Direct_Cust,")
            ->select("i.net_amount,i.balance_amount,(i.org_amount_recived+i.total_paid_amount) as sum_of_paid",
                false)
            ->where('i.balance_amount >', 0, false)
            ->where('i.ipd_status', 1)
            ->where('o.id', null)
            ->where("i.register_date >=", $start)
            ->where("i.register_date <=", $end)
            ->groupBy('p.id,ipd_doc_list.doc_list')
            ->orderBy('i.id');

        return $builder->get()->getResult();
    }

    public function getIpdPanelInfo(int $ipdId): array
    {
        $ipdInfo = $this->db->table('ipd_master i')
            ->select("i.*, date_format(i.register_date,'%d-%m-%Y') as str_register_date,")
            ->select("date_format(i.discharge_date,'%d-%m-%Y') as str_discharge_date,")
            ->select("if(i.ipd_status = 0,(to_days(sysdate()) - to_days(i.register_date)),(to_days(i.discharge_date) - to_days(i.register_date))) as no_days,")
            ->select("in_master.ins_company_name as ins_company_name", false)
            ->select("in_master.short_name as ins_short_name", false)
            ->select("o.case_id_code as case_id_code", false)
            ->select("o.insurance_no as insurance_no", false)
            ->select("o.insurance_no_1 as insurance_no_1", false)
            ->select("o.insurance_no_2 as insurance_no_2", false)
            ->join('organization_case_master o', 'i.case_id = o.id', 'left')
            ->join('hc_insurance in_master', 'in_master.id = o.insurance_id', 'left')
            ->where('i.id', $ipdId)
            ->get()
            ->getRow();

        if (! $ipdInfo) {
            return [];
        }

        $patient = $this->db->table('patient_master p')
            ->select("p.*, if(p.gender = 1, 'Male', 'Female') as xgender", false)
            ->where('p.id', $ipdInfo->p_id)
            ->get()
            ->getRow();

        return [
            'ipd_info' => $ipdInfo,
            'person_info' => $patient,
        ];
    }

    public function getIpdCharges(int $ipdId): array
    {
        return $this->db->table('ipd_invoice_item i')
            ->select('i.*, t.group_desc')
            ->join('ipd_item_type t', 'i.item_type = t.itype_id', 'left')
            ->where('i.ipd_id', $ipdId)
            ->orderBy('i.id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getIpdChargesGrouped(int $ipdId): array
    {
        return $this->db->table('ipd_invoice_item i')
            ->select('t.group_desc, i.*, sum(i.item_amount) as xAmount')
            ->join('ipd_item_type t', 'i.item_type = t.itype_id', 'left')
            ->where('i.ipd_id', $ipdId)
            ->groupBy('i.item_type,i.id')
            ->orderBy('i.item_type', 'ASC')
            ->orderBy('i.id', 'ASC')
            ->get()
            ->getResult();
    }

    public function getIpdChargesTotal(int $ipdId): float
    {
        $row = $this->db->table('ipd_invoice_item')
            ->select('sum(item_amount) as total_amount')
            ->where('ipd_id', $ipdId)
            ->get()
            ->getRow();

        return (float) ($row->total_amount ?? 0);
    }

    public function getIpdCaseMeta(int $ipdId): array
    {
        $row = $this->db->table('ipd_master i')
            ->select('o.insurance_id')
            ->join('organization_case_master o', 'i.case_id = o.id', 'left')
            ->where('i.id', $ipdId)
            ->get()
            ->getRowArray();

        return $row ?? [];
    }

    public function getIpdDoctorList(): array
    {
        return $this->db->table('doctor_master d')
            ->select("d.id, d.p_fname, concat(d.p_fname,' [',group_concat(m.SpecName),']') as DocSpecName", false)
            ->join('doc_spec s', 'd.id = s.doc_id', 'inner')
            ->join('med_spec m', 's.med_spec_id = m.id', 'inner')
            ->where('d.active', 1)
            ->groupBy('d.id')
            ->orderBy('d.p_fname', 'ASC')
            ->get()
            ->getResult();
    }

    public function getBankPaymentSources(): array
    {
        return $this->db->table('hospital_bank m')
            ->select('s.id,s.pay_type,m.bank_name')
            ->join('hospital_bank_payment_source s', 'm.id = s.bank_id')
            ->get()
            ->getResult();
    }

    public function getDoctorNameById(int $docId): string
    {
        $row = $this->db->table('doctor_master')
            ->select('p_fname')
            ->where('id', $docId)
            ->get()
            ->getRow();

        return (string) ($row->p_fname ?? '');
    }

    public function getIpdPackages(int $ipdId): array
    {
        return $this->db->table('ipd_package')
            ->where('ipd_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getIpdInvoiceItems(int $ipdId, bool $excludePackage = false): array
    {
        $builder = $this->db->table('ipd_invoice_item i')
            ->select('t.group_desc,i.*')
            ->join('ipd_item_type t', 'i.item_type = t.itype_id', 'left')
            ->where('i.ipd_id', $ipdId);

        if ($excludePackage) {
            $builder->where('i.package_id', 0);
        }

        return $builder
            ->orderBy('t.group_desc', 'ASC')
            ->orderBy('i.id', 'ASC')
            ->get()
            ->getResult();
    }

    public function getIpdInvoiceCharges(int $ipdId): array
    {
        return $this->db->table('invoice_master i')
            ->select('l.group_desc as Charge_type,t.item_name as idesc,t.item_rate,')
            ->select('t.org_code as orgcode,sum(t.item_qty) as no_qty,sum(t.item_amount) as amount', false)
            ->join('invoice_item t', 't.inv_master_id = i.id')
            ->join('hc_item_type l', 't.item_type = l.itype_id', 'left')
            ->where('i.ipd_id', $ipdId)
            ->where('i.ipd_include', 1)
            ->groupBy('i.ipd_id,t.item_id,t.item_rate,l.group_desc,t.org_code,t.item_name')
            ->orderBy('Charge_type', 'ASC')
            ->get()
            ->getResult();
    }

    public function getIpdMedicalCredits(int $ipdId): array
    {
        return $this->db->table('invoice_med_master')
            ->where('ipd_credit', 1)
            ->where('ipd_credit_type', 1)
            ->where('ipd_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getIpdInsurance(int $ipdId): array
    {
        $row = $this->db->table('ipd_master i')
            ->select('ins.*')
            ->join('organization_case_master o', 'i.case_id = o.id', 'left')
            ->join('hc_insurance ins', 'ins.id = o.insurance_id', 'left')
            ->where('i.id', $ipdId)
            ->get()
            ->getRowArray();

        return $row ?? [];
    }

    public function getPackageListForInsurance(int $insuranceId): array
    {
        return $this->db->table('package p')
            ->select('p.id,p.ipd_pakage_name,p.Pakage_Min_Amount,i.hc_insurance_id,')
            ->select('i.code,if(i.hc_insurance_id is null,p.Pakage_Min_Amount,i.i_amount) as amount1', false)
            ->select("if(i.hc_insurance_id is null,Concat(' Rs. ',p.Pakage_Min_Amount),Concat('[ Rs. ',i.i_amount,' /Org.Code ',i.code,']')) as org_code", false)
            ->join(
                'package_insurance i',
                'p.id = i.hc_items_id and i.hc_insurance_id = ' . (int) $insuranceId,
                'left'
            )
            ->orderBy('p.ipd_pakage_name', 'ASC')
            ->get()
            ->getResult();
    }

    public function getPackageWithInsurance(int $packageId, int $insuranceId): ?object
    {
        return $this->db->table('package p')
            ->select('p.id,p.ipd_pakage_name,p.Pakage_Min_Amount,p.Pakage_description,')
            ->select('i.hc_insurance_id,i.code,if(i.hc_insurance_id is null,p.Pakage_Min_Amount,i.i_amount) as amount1', false)
            ->join(
                'package_insurance i',
                'p.id = i.hc_items_id and i.hc_insurance_id = ' . (int) $insuranceId,
                'left'
            )
            ->where('p.id', $packageId)
            ->get()
            ->getRow();
    }

    public function getDiagnosisCharges(int $ipdId): array
    {
        return $this->db->table('invoice_master i')
            ->select("i.id as inv_id,i.invoice_code,sum(t.item_amount) as amount,i.refer_by_other,")
            ->select("date_format(i.inv_date,'%d-%m-%Y') as str_date,i.ipd_include,")
            ->select('group_concat(distinct c.group_desc) as charge_list', false)
            ->join('invoice_item t', 'i.id = t.inv_master_id', 'left')
            ->join('hc_item_type c', 't.item_type = c.itype_id', 'left')
            ->where('i.payment_status', 1)
            ->where('i.ipd_id', $ipdId)
            ->groupBy('i.id')
            ->orderBy('i.id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getPayments(int $ipdId): array
    {
        return $this->db->table('payment_history')
            ->select("*,date_format(payment_date,'%d-%M-%Y') as pay_date_str,")
            ->select("concat(if(payment_mode=1,'Cash','BANK'),if(credit_debit=0,'','-Return')) as pay_mode", false)
            ->where('payof_type', 4)
            ->where('payof_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getMedicalBills(int $ipdId): array
    {
        return $this->db->table('invoice_med_master')
            ->where('ipd_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getBillDetails(int $ipdId): array
    {
        return $this->db->table('ipd_master')
            ->where('id', $ipdId)
            ->get()
            ->getResult();
    }

    public function getDischargeInfo(int $ipdId): array
    {
        return $this->db->table('ipd_master i')
            ->select('i.*, d.status_desc')
            ->join('ipd_discharg_status d', 'i.discarge_patient_status = d.id', 'left')
            ->where('i.id', $ipdId)
            ->get()
            ->getResult();
    }

    private function baseIpdListQuery()
    {
        $builder = $this->db->table('ipd_master i');

        $builder->join('patient_master p', 'i.p_id = p.id');
        $builder->join('ipd_discharg_status d', 'i.discarge_patient_status = d.id');
        $builder->join(
            '(organization_case_master o join hc_insurance in_master on in_master.id = o.insurance_id'
                . ' join org_approved_status s on o.org_approved_status_id = s.id)',
            'i.case_id = o.id',
            'left',
            false
        );
        $builder->join(
            '(select max(id) as id, ipd_id from bed_assignment_history group by ipd_id) bah_latest',
            'bah_latest.ipd_id = i.id',
            'left',
            false
        );
        $builder->join('bed_assignment_history bah', 'bah.id = bah_latest.id', 'left');
        $builder->join('bed_master b', 'b.id = bah.bed_id', 'left');
        $builder->join('ward_master w', 'w.id = bah.ward_id', 'left');
        $builder->join(
            "(select i.ipd_id,
                group_concat(distinct concat_ws(' ', 'Dr.', d.p_fname, d.p_mname, d.p_lname)) as doc_name,
                group_concat(distinct d.id) as doc_list
            from ipd_master_doc_list i
            join doctor_master d on i.doc_id = d.id
            group by i.ipd_id) ipd_doc_list",
            'i.id = ipd_doc_list.ipd_id',
            'left',
            false
        );

        return $builder;
    }

    private function applyDataTableFilters($builder, array $request): void
    {
        $columns = $request['columns'] ?? [];

        $ipdCode = trim((string) ($columns[0]['search']['value'] ?? ''));
        if ($ipdCode !== '') {
            $builder->like('ipd_code', $ipdCode, 'both');
        }

        $pCode = trim((string) ($columns[1]['search']['value'] ?? ''));
        if ($pCode !== '') {
            $builder->like('p_code', $pCode, 'after');
        }

        $pName = trim((string) ($columns[2]['search']['value'] ?? ''));
        if ($pName !== '') {
            $builder->like('p_fname', $pName, 'both');
        }

        $admitType = trim((string) ($columns[3]['search']['value'] ?? ''));
        if ($admitType !== '') {
            $builder->groupStart()
            ->like('in_master.short_name', $admitType, 'both')
            ->orLike('in_master.ins_company_name', $admitType, 'both')
            ->orLike("if((o.id is not null),in_master.short_name,'Direct')", $admitType, 'both', null, false)
                ->groupEnd();
        }

        $claimNo = trim((string) ($columns[4]['search']['value'] ?? ''));
        if ($claimNo !== '') {
            $builder->like('insurance_no_1', $claimNo, 'both');
        }

        $docName = trim((string) ($columns[5]['search']['value'] ?? ''));
        if ($docName !== '') {
            $builder->like('doc_name', $docName, 'both');
        }

        $dateFilter = trim((string) ($columns[7]['search']['value'] ?? ''));
        if ($dateFilter !== '') {
            $rangeParts = explode('/', $dateFilter);
            if (count($rangeParts) === 3) {
                [$start, $end, $type] = $rangeParts;
                $escapedStart = $this->db->escape($start);
                $escapedEnd = $this->db->escape($end);
                if ($type === '0') {
                    $builder->where("Date(i.register_date) >=", $escapedStart, false)
                        ->where("Date(i.register_date) <=", $escapedEnd, false);
                } elseif ($type === '1') {
                    $builder->where("Date(i.discharge_date) >=", $escapedStart, false)
                        ->where("Date(i.discharge_date) <=", $escapedEnd, false);
                } else {
                    $builder
                        ->groupStart()
                        ->groupStart()
                        ->where("Date(i.register_date) >=", $escapedStart, false)
                        ->where("Date(i.register_date) <=", $escapedEnd, false)
                        ->groupEnd()
                        ->orGroupStart()
                        ->where("Date(i.discharge_date) >=", $escapedStart, false)
                        ->where("Date(i.discharge_date) <=", $escapedEnd, false)
                        ->groupEnd()
                        ->groupEnd();
                }
            }
        }
    }
}
