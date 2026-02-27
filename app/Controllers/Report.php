<?php

namespace App\Controllers;

class Report extends BaseController
{
    public function collection_report()
    {
        $employees = $this->db->table('users')
            ->select('id, username')
            ->where('active', 1)
            ->orderBy('username', 'ASC')
            ->get()
            ->getResult();

        $payModes = [
            (object) ['id' => 1, 'mode_desc' => 'Cash'],
            (object) ['id' => 2, 'mode_desc' => 'Bank'],
        ];

        return view('report/collection_report', [
            'employees' => $employees,
            'pay_modes' => $payModes,
        ]);
    }

    public function report_total_payment_app_show(
        string $dateRange,
        string $employeeIds,
        string $payModeId,
        string $orderFirst = '0',
        int $output = 0
    ) {
        [$minRange, $maxRange] = $this->parseDateRange($dateRange);

        $builder = $this->db->table('payment_history p');
        $builder->select("p.id as pay_id, p.payment_date,")
            ->select("date_format(p.payment_date,'%d-%m-%Y %H:%i') as str_payment_date", false)
            ->select("case p.payof_type when 1 then 'OPD' when 2 then 'Charges' when 3 then 'ORG' when 4 then 'IPD' else 'Other' end as pay_type", false)
            ->select('p.payof_id, p.payof_code')
            ->select("if(p.credit_debit=0,p.amount,0) as cr_amount", false)
            ->select("if(p.credit_debit=1,p.amount,0) as dr_amount", false)
            ->select('p.remark, p.update_by')
            ->select("case p.payof_type when 1 then o.P_name when 2 then inv.inv_name when 3 then oc.insurance_company_name when 4 then pat.p_fname else '' end as patient_name", false)
            ->select("case p.payment_mode when 1 then 'Cash' when 2 then 'Bank' else 'Other' end as pay_mode", false)
            ->select('b.bank_name as bank_name, s.pay_type as bank_type')
            ->join('opd_master o', 'p.payof_type = 1 and o.opd_id = p.payof_id', 'left')
            ->join('invoice_master inv', 'p.payof_type = 2 and inv.id = p.payof_id', 'left')
            ->join('organization_case_master oc', 'p.payof_type = 3 and oc.id = p.payof_id', 'left')
            ->join('ipd_master im', 'p.payof_type = 4 and im.id = p.payof_id', 'left')
            ->join('patient_master pat', 'pat.id = im.p_id', 'left')
            ->join('hospital_bank_payment_source s', 's.id = p.pay_bank_id', 'left')
            ->join('hospital_bank b', 'b.id = s.bank_id', 'left')
            ->where('p.payment_date >=', $minRange)
            ->where('p.payment_date <=', $maxRange);

        $empList = $this->normalizeIdList($employeeIds);
        if (! empty($empList)) {
            $builder->whereIn('p.update_by_id', $empList);
        }

        $payModeId = (int) $payModeId;
        if ($payModeId < 1) {
            $builder->whereIn('p.payment_mode', [1, 2]);
        } else {
            $builder->where('p.payment_mode', $payModeId);
        }

        $builder->orderBy('p.id', 'ASC');
        $orderFirst = (int) $orderFirst;
        if ($orderFirst === 1) {
            $builder->orderBy('p.update_by', 'ASC');
        } elseif ($orderFirst === 2) {
            $builder->orderBy('p.payment_mode', 'ASC');
        }

        $rows = $builder->get()->getResult();

        $totalCr = 0.0;
        $totalDr = 0.0;
        foreach ($rows as $row) {
            $totalCr += (float) ($row->cr_amount ?? 0);
            $totalDr += (float) ($row->dr_amount ?? 0);
        }

        $data = [
            'rows' => $rows,
            'min_range' => $minRange,
            'max_range' => $maxRange,
            'total_cr' => $totalCr,
            'total_dr' => $totalDr,
            'net_total' => $totalCr - $totalDr,
        ];

        $content = view('report/collection_report_table', $data);
        if ($output === 1) {
            ExportExcel($content, 'Payment');
            return $this->response->setBody('');
        }

        return $this->response->setBody($content);
    }

    private function parseDateRange(string $dateRange): array
    {
        $rangeParts = explode('S', $dateRange);
        $startRaw = $rangeParts[0] ?? '';
        $endRaw = $rangeParts[1] ?? '';

        $startRaw = str_replace('T', ' ', $startRaw);
        $endRaw = str_replace('T', ' ', $endRaw);

        $start = trim($startRaw);
        $end = trim($endRaw);

        if ($start === '' || $end === '') {
            $today = date('Y-m-d');
            $start = $today . ' 00:00:00';
            $end = $today . ' 23:59:59';
        }

        return [$start, $end];
    }

    private function normalizeIdList(string $idList): array
    {
        $idList = trim($idList);
        if ($idList === '' || $idList === '0') {
            return [];
        }

        $idList = str_replace('S', ',', $idList);
        $parts = array_filter(array_map('trim', explode(',', $idList)));
        $ids = [];
        foreach ($parts as $part) {
            $value = (int) $part;
            if ($value > 0) {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }

    public function diagnosis_report()
    {
        $itemTypes = $this->db->table('hc_item_type')
            ->select('itype_id, group_desc')
            ->whereIn('is_ipd_opd', [0, 1])
            ->orderBy('group_desc', 'ASC')
            ->get()
            ->getResult();

        return view('report/diagnosis_report', [
            'item_types' => $itemTypes,
        ]);
    }

    public function diagnosis_report_data(
        string $dateRange,
        string $invoiceType = '0',
        string $diagnosisIds = '0',
        int $output = 0
    ) {
        [$minRange, $maxRange] = $this->parseDateRange($dateRange);

        $builder = $this->db->table('invoice_master m');
        $builder->select('y.group_desc, t.item_name')
            ->select('COUNT(t.id) as no_test', false)
            ->select('SUM(t.item_qty) as no_qty', false)
            ->select('SUM(ROUND(IF(m.invoice_status=1, (t.item_amount - (t.item_amount * m.discount_amount / m.total_amount)), 0), 2)) as total_amount', false)
            ->select('SUM(IF(m.invoice_status=1, t.item_amount, 0)) as total_act_amount', false)
            ->join('invoice_item t', 'm.id = t.inv_master_id', 'inner')
            ->join('hc_item_type y', 't.item_type = y.itype_id', 'inner')
            ->where('y.is_ipd_opd IN (0, 1)', null, false)
            ->where('m.invoice_status', 1)
            ->where('m.inv_date >=', $minRange)
            ->where('m.inv_date <=', $maxRange);

        // Invoice type filter
        $invoiceTypeInt = (int) $invoiceType;
        if ($invoiceTypeInt === 1) {
            // IPD
            $builder->where('m.ipd_id >', 0);
        } elseif ($invoiceTypeInt === 2) {
            // OPD
            $builder->where('m.ipd_id', 0)
                ->where('m.insurance_case_id', 0);
        } elseif ($invoiceTypeInt === 3) {
            // Organization
            $builder->where('m.insurance_case_id >', 0);
        }

        $diagList = $this->normalizeIdList($diagnosisIds);
        if (!empty($diagList)) {
            $builder->whereIn('t.item_type', $diagList);
        }

        $builder->groupBy(['y.group_desc', 't.item_id'])
            ->orderBy('y.group_desc', 'ASC')
            ->orderBy('t.item_name', 'ASC');

        $rows = $builder->get()->getResult();

        $invoiceTypeLabel = ['All Types', 'IPD', 'OPD', 'Organization'][$invoiceTypeInt] ?? 'All Types';

        $data = [
            'rows' => $rows,
            'min_range' => $minRange,
            'max_range' => $maxRange,
            'invoice_type_label' => $invoiceTypeLabel,
        ];

        $content = view('report/diagnosis_report_table', $data);

        if ($output === 1) {
            ExportExcel($content, 'Diagnosis_Report');
            return $this->response->setBody('');
        }

        return $this->response->setBody($content);
    }

    // ==================== Insurance Credit Reports ====================

    public function insurance_credit_main()
    {
        return view('report/insurance_credit_main');
    }

    public function insurance_opd_report()
    {
        $insuranceGroups = $this->db->table('h_insurance_group')
            ->orderBy('tpa_group', 'ASC')
            ->get()
            ->getResult();

        $insuranceCompanies = $this->db->table('hc_insurance')
            ->orderBy('short_name', 'ASC')
            ->get()
            ->getResult();

        return view('report/insurance_opd_report', [
            'insurance_groups' => $insuranceGroups,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    public function insurance_ipd_report()
    {
        $insuranceGroups = $this->db->table('h_insurance_group')
            ->orderBy('tpa_group', 'ASC')
            ->get()
            ->getResult();

        $insuranceCompanies = $this->db->table('hc_insurance')
            ->orderBy('short_name', 'ASC')
            ->get()
            ->getResult();

        return view('report/insurance_ipd_report', [
            'insurance_groups' => $insuranceGroups,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    public function insurance_combined_report()
    {
        $insuranceGroups = $this->db->table('h_insurance_group')
            ->orderBy('tpa_group', 'ASC')
            ->get()
            ->getResult();

        $insuranceCompanies = $this->db->table('hc_insurance')
            ->orderBy('short_name', 'ASC')
            ->get()
            ->getResult();

        return view('report/insurance_combined_report', [
            'insurance_groups' => $insuranceGroups,
            'insurance_companies' => $insuranceCompanies,
        ]);
    }

    public function insurance_opd_report_data(
        string $dateRange,
        string $insuranceId = '0',
        string $caseStatus = '-1',
        int $output = 0
    ) {
        [$minRange, $maxRange] = $this->parseDateRange($dateRange);

        $builder = $this->db->table('organization_case_master o');
        $builder->select('o.id, o.case_id_code')
            ->select("CONCAT(o.insurance_no, '/', o.insurance_no_1) as case_info", false)
            ->select('p.p_code, p.p_fname')
            ->select('ins.short_name as insurance_company')
            ->select("DATE_FORMAT(o.date_registration, '%d-%m-%Y') as str_date_registration", false)
            ->select("(CASE o.status WHEN 0 THEN 'Pending' WHEN 1 THEN 'Bill Complete' WHEN 2 THEN 'Submitted' ELSE 'Unknown' END) as org_submit_status", false)
            ->select('(o.inv_opd_amt + o.inv_opd_charge_amt) as charge_amount', false)
            ->select('o.inv_opd_med_amt as med_amount')
            ->select('((o.inv_opd_amt + o.inv_opd_charge_amt) + o.inv_opd_med_amt) as net_amount', false)
            ->join('patient_master p', 'o.p_id = p.id', 'inner')
            ->join('hc_insurance ins', 'ins.id = o.insurance_id', 'inner')
            ->where('o.case_type', 0)
            ->where('o.date_registration >=', $minRange)
            ->where('o.date_registration <=', $maxRange)
            ->where('((o.inv_opd_amt + o.inv_opd_charge_amt) + o.inv_opd_med_amt) >', 0);

        // Insurance filter
        if ($insuranceId !== '0') {
            if (strpos($insuranceId, 'G') === 0) {
                // Insurance Group
                $groupId = (int) substr($insuranceId, 1);
                $builder->where('ins.group_ins', $groupId);
            } else {
                // Individual Insurance
                $builder->where('o.insurance_id', (int) $insuranceId);
            }
        }

        // Status filter
        $statusInt = (int) $caseStatus;
        if ($statusInt >= 0) {
            $builder->where('o.status', $statusInt);
        }

        $builder->orderBy('o.id', 'ASC');
        $rows = $builder->get()->getResult();

        $data = [
            'rows' => $rows,
            'min_range' => $minRange,
            'max_range' => $maxRange,
            'case_type' => 'OPD',
        ];

        $content = view('report/insurance_opd_report_table', $data);

        if ($output === 1) {
            ExportExcel($content, 'Insurance_OPD_Report');
            return $this->response->setBody('');
        }

        return $this->response->setBody($content);
    }

    public function insurance_ipd_report_data(
        string $dateRange,
        string $insuranceId = '0',
        string $caseStatus = '-1',
        int $output = 0
    ) {
        [$minRange, $maxRange] = $this->parseDateRange($dateRange);

        $builder = $this->db->table('organization_case_master o');
        $builder->select('o.id, o.case_id_code')
            ->select("CONCAT(o.insurance_no, '/', o.insurance_no_1) as case_info", false)
            ->select('i.ipd_code, p.p_code, p.p_fname')
            ->select('ins.short_name as insurance_company')
            ->select("DATE_FORMAT(i.register_date, '%d-%m-%Y') as str_register_date", false)
            ->select("DATE_FORMAT(i.discharge_date, '%d-%m-%Y') as str_discharge_date", false)
            ->select("(CASE o.status WHEN 0 THEN 'Pending' WHEN 1 THEN 'Bill Complete' WHEN 2 THEN 'Submitted' ELSE 'Unknown' END) as org_submit_status", false)
            ->select('(i.charge_amount + i.chargeamount1 + i.chargeamount2) as charge_amount', false)
            ->select('i.med_amount')
            ->select('(i.Discount + i.Discount2 + i.Discount3) as discount', false)
            ->select('((i.charge_amount + i.chargeamount1 + i.chargeamount2 + i.med_amount) - (i.Discount + i.Discount2 + i.Discount3)) as net_amount', false)
            ->select('o.amount_recived, o.amount_deduction')
            ->join('ipd_master i', 'o.id = i.case_id', 'inner')
            ->join('patient_master p', 'i.p_id = p.id', 'inner')
            ->join('hc_insurance ins', 'ins.id = o.insurance_id', 'inner')
            ->where('o.case_type', 1)
            ->where('i.ipd_status', 1)
            ->where('i.register_date >=', $minRange)
            ->where('i.register_date <=', $maxRange);

        // Insurance filter
        if ($insuranceId !== '0') {
            if (strpos($insuranceId, 'G') === 0) {
                // Insurance Group
                $groupId = (int) substr($insuranceId, 1);
                $builder->where('ins.group_ins', $groupId);
            } else {
                // Individual Insurance
                $builder->where('o.insurance_id', (int) $insuranceId);
            }
        }

        // Status filter
        $statusInt = (int) $caseStatus;
        if ($statusInt >= 0) {
            $builder->where('o.status', $statusInt);
        }

        $builder->orderBy('i.id', 'ASC');
        $rows = $builder->get()->getResult();

        $data = [
            'rows' => $rows,
            'min_range' => $minRange,
            'max_range' => $maxRange,
            'case_type' => 'IPD',
        ];

        $content = view('report/insurance_ipd_report_table', $data);

        if ($output === 1) {
            ExportExcel($content, 'Insurance_IPD_Report');
            return $this->response->setBody('');
        }

        return $this->response->setBody($content);
    }

    public function insurance_combined_report_data(
        string $dateRange,
        string $caseType = '-1',
        string $insuranceId = '0',
        int $output = 0
    ) {
        [$minRange, $maxRange] = $this->parseDateRange($dateRange);

        $caseTypeInt = (int) $caseType;
        $data = [
            'min_range' => $minRange,
            'max_range' => $maxRange,
            'opd_rows' => [],
            'ipd_rows' => [],
        ];

        // Get OPD data if needed
        if ($caseTypeInt === -1 || $caseTypeInt === 0) {
            $builder = $this->db->table('organization_case_master o');
            $builder->select('ins.short_name as insurance_company')
                ->select('COUNT(o.id) as total_cases', false)
                ->select('SUM(o.inv_opd_amt + o.inv_opd_charge_amt) as total_charge', false)
                ->select('SUM(o.inv_opd_med_amt) as total_med', false)
                ->select('SUM((o.inv_opd_amt + o.inv_opd_charge_amt) + o.inv_opd_med_amt) as total_net', false)
                ->join('hc_insurance ins', 'ins.id = o.insurance_id', 'inner')
                ->where('o.case_type', 0)
                ->where('o.date_registration >=', $minRange)
                ->where('o.date_registration <=', $maxRange);

            if ($insuranceId !== '0') {
                if (strpos($insuranceId, 'G') === 0) {
                    $groupId = (int) substr($insuranceId, 1);
                    $builder->where('ins.group_ins', $groupId);
                } else {
                    $builder->where('o.insurance_id', (int) $insuranceId);
                }
            }

            $builder->groupBy('ins.short_name')->orderBy('ins.short_name', 'ASC');
            $data['opd_rows'] = $builder->get()->getResult();
        }

        // Get IPD data if needed
        if ($caseTypeInt === -1 || $caseTypeInt === 1) {
            $builder = $this->db->table('organization_case_master o');
            $builder->select('ins.short_name as insurance_company')
                ->select('COUNT(i.id) as total_cases', false)
                ->select('SUM(i.charge_amount + i.chargeamount1 + i.chargeamount2) as total_charge', false)
                ->select('SUM(i.med_amount) as total_med', false)
                ->select('SUM(i.Discount + i.Discount2 + i.Discount3) as total_discount', false)
                ->select('SUM((i.charge_amount + i.chargeamount1 + i.chargeamount2 + i.med_amount) - (i.Discount + i.Discount2 + i.Discount3)) as total_net', false)
                ->select('SUM(o.amount_recived) as total_received', false)
                ->select('SUM(o.amount_deduction) as total_deducted', false)
                ->join('ipd_master i', 'o.id = i.case_id', 'inner')
                ->join('hc_insurance ins', 'ins.id = o.insurance_id', 'inner')
                ->where('o.case_type', 1)
                ->where('i.ipd_status', 1)
                ->where('i.register_date >=', $minRange)
                ->where('i.register_date <=', $maxRange);

            if ($insuranceId !== '0') {
                if (strpos($insuranceId, 'G') === 0) {
                    $groupId = (int) substr($insuranceId, 1);
                    $builder->where('ins.group_ins', $groupId);
                } else {
                    $builder->where('o.insurance_id', (int) $insuranceId);
                }
            }

            $builder->groupBy('ins.short_name')->orderBy('ins.short_name', 'ASC');
            $data['ipd_rows'] = $builder->get()->getResult();
        }

        $content = view('report/insurance_combined_report_table', $data);

        if ($output === 1) {
            ExportExcel($content, 'Insurance_Combined_Report');
            return $this->response->setBody('');
        }

        return $this->response->setBody($content);
    }
}
