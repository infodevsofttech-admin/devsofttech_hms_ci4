<?php

namespace App\Controllers;

class Report extends BaseController
{
    public function index()
    {
        return view('report/index');
    }

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

    public function billing_operations_report()
    {
        $doctors = $this->db->table('opd_master')
            ->select('doc_name')
            ->where('doc_name IS NOT NULL', null, false)
            ->where("doc_name != ''", null, false)
            ->groupBy('doc_name')
            ->orderBy('doc_name', 'ASC')
            ->get()
            ->getResult();

        return view('report/billing_operations_report', [
            'doctors' => $doctors,
        ]);
    }

    public function billing_operations_report_data(string $dateRange, string $doctor = '0', int $output = 0)
    {
        [$minRange, $maxRange] = $this->parseDateRange($dateRange);

        $doctorFilter = trim(urldecode($doctor));

        $opdBuilder = $this->db->table('opd_master o')
            ->select('o.doc_name')
            ->select('COUNT(o.opd_id) as total_opd', false)
            ->where('o.apointment_date >=', $minRange)
            ->where('o.apointment_date <=', $maxRange)
            ->where('o.doc_name IS NOT NULL', null, false)
            ->where("o.doc_name != ''", null, false);

        if ($doctorFilter !== '' && $doctorFilter !== '0') {
            $opdBuilder->where('o.doc_name', $doctorFilter);
        }

        $opdRows = $opdBuilder
            ->groupBy('o.doc_name')
            ->orderBy('total_opd', 'DESC')
            ->get()
            ->getResult();

        $invoiceRows = $this->db->table('invoice_master m')
            ->select('m.id, m.inv_name, m.inv_date, m.total_amount, m.discount_amount')
            ->where('m.inv_date >=', $minRange)
            ->where('m.inv_date <=', $maxRange)
            ->where('m.invoice_status', 1)
            ->orderBy('m.id', 'DESC')
            ->get()
            ->getResultArray();

        $invoiceIds = array_values(array_filter(array_map(static function (array $row): int {
            return (int) ($row['id'] ?? 0);
        }, $invoiceRows)));

        $testAgg = [];
        $paymentAgg = [];
        if (! empty($invoiceIds)) {
            $testRows = $this->db->table('invoice_item')
                ->select('inv_master_id')
                ->select('COUNT(id) as test_count', false)
                ->select('SUM(item_qty) as test_qty', false)
                ->select('SUM(item_amount) as test_amount', false)
                ->whereIn('inv_master_id', $invoiceIds)
                ->groupBy('inv_master_id')
                ->get()
                ->getResultArray();

            foreach ($testRows as $row) {
                $testAgg[(int) ($row['inv_master_id'] ?? 0)] = $row;
            }

            $payRows = $this->db->table('payment_history')
                ->select('payof_id')
                ->select("SUM(IF(credit_debit=0, amount, 0)) as total_received", false)
                ->select("SUM(IF(credit_debit=1, amount, 0)) as total_refund", false)
                ->where('payof_type', 2)
                ->whereIn('payof_id', $invoiceIds)
                ->groupBy('payof_id')
                ->get()
                ->getResultArray();

            foreach ($payRows as $row) {
                $paymentAgg[(int) ($row['payof_id'] ?? 0)] = $row;
            }
        }

        $invoiceDetailRows = [];
        $invoiceSummary = [
            'invoice_count' => 0,
            'total_invoice_amount' => 0.0,
            'total_discount' => 0.0,
            'total_test_count' => 0,
            'total_test_qty' => 0.0,
            'total_test_amount' => 0.0,
            'total_received' => 0.0,
            'total_refund' => 0.0,
        ];

        foreach ($invoiceRows as $row) {
            $invId = (int) ($row['id'] ?? 0);
            $test = $testAgg[$invId] ?? ['test_count' => 0, 'test_qty' => 0, 'test_amount' => 0];
            $pay = $paymentAgg[$invId] ?? ['total_received' => 0, 'total_refund' => 0];

            $invoiceAmount = (float) ($row['total_amount'] ?? 0);
            $discountAmount = (float) ($row['discount_amount'] ?? 0);
            $received = (float) ($pay['total_received'] ?? 0);
            $refund = (float) ($pay['total_refund'] ?? 0);
            $netReceived = $received - $refund;

            $invoiceSummary['invoice_count']++;
            $invoiceSummary['total_invoice_amount'] += $invoiceAmount;
            $invoiceSummary['total_discount'] += $discountAmount;
            $invoiceSummary['total_test_count'] += (int) ($test['test_count'] ?? 0);
            $invoiceSummary['total_test_qty'] += (float) ($test['test_qty'] ?? 0);
            $invoiceSummary['total_test_amount'] += (float) ($test['test_amount'] ?? 0);
            $invoiceSummary['total_received'] += $received;
            $invoiceSummary['total_refund'] += $refund;

            $invoiceDetailRows[] = (object) [
                'id' => $invId,
                'inv_name' => (string) ($row['inv_name'] ?? ''),
                'inv_date' => (string) ($row['inv_date'] ?? ''),
                'invoice_amount' => $invoiceAmount,
                'discount_amount' => $discountAmount,
                'test_count' => (int) ($test['test_count'] ?? 0),
                'test_qty' => (float) ($test['test_qty'] ?? 0),
                'test_amount' => (float) ($test['test_amount'] ?? 0),
                'received_amount' => $received,
                'refund_amount' => $refund,
                'net_received' => $netReceived,
                'pending_amount' => $invoiceAmount - $netReceived,
            ];
        }

        $ipdPaymentRows = $this->db->table('payment_history p')
            ->select('p.payof_id as ipd_id, i.ipd_code, pat.p_fname as patient_name')
            ->select("SUM(IF(p.credit_debit=0, p.amount, 0)) as total_received", false)
            ->select("SUM(IF(p.credit_debit=1, p.amount, 0)) as total_refund", false)
            ->join('ipd_master i', 'i.id = p.payof_id', 'left')
            ->join('patient_master pat', 'pat.id = i.p_id', 'left')
            ->where('p.payof_type', 4)
            ->where('p.payment_date >=', $minRange)
            ->where('p.payment_date <=', $maxRange)
            ->groupBy('p.payof_id')
            ->orderBy('total_received', 'DESC')
            ->get()
            ->getResult();

        $ipdSummary = [
            'total_received' => 0.0,
            'total_refund' => 0.0,
            'net_received' => 0.0,
            'ipd_count' => 0,
        ];

        foreach ($ipdPaymentRows as $row) {
            $received = (float) ($row->total_received ?? 0);
            $refund = (float) ($row->total_refund ?? 0);
            $ipdSummary['total_received'] += $received;
            $ipdSummary['total_refund'] += $refund;
            $ipdSummary['ipd_count']++;
        }
        $ipdSummary['net_received'] = $ipdSummary['total_received'] - $ipdSummary['total_refund'];

        $data = [
            'min_range' => $minRange,
            'max_range' => $maxRange,
            'doctor_filter' => $doctorFilter,
            'opd_rows' => $opdRows,
            'invoice_summary' => $invoiceSummary,
            'invoice_rows' => $invoiceDetailRows,
            'ipd_summary' => $ipdSummary,
            'ipd_payment_rows' => $ipdPaymentRows,
        ];

        $content = view('report/billing_operations_report_table', $data);
        if ($output === 1) {
            ExportExcel($content, 'Billing_Operations_Report');
            return $this->response->setBody('');
        }

        return $this->response->setBody($content);
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

    public function document_list()
    {
        $doctors = $this->db->table('doctor_master')
            ->select('id, p_fname')
            ->where('active', 1)
            ->orderBy('p_fname', 'ASC')
            ->get()
            ->getResult();

        return view('report/document_list', [
            'doclist' => $doctors,
        ]);
    }

    public function document_list_data(string $dateRange, int $drId, int $output = 0)
    {
        [$minRange, $maxRange] = $this->parseDateRange($dateRange);
        $uhidFilter = trim((string) $this->request->getGet('uhid'));

        if (! $this->db->tableExists('patient_doc') || ! $this->db->tableExists('doctor_master') || ! $this->db->tableExists('doc_format_master')) {
            $content = '<div class="alert alert-danger mb-0">Document report tables are not available in this database.</div>';
            return $this->response->setBody($content);
        }

        $patientTable = null;
        if ($this->db->tableExists('patient_master_exten')) {
            $patientTable = 'patient_master_exten';
        } elseif ($this->db->tableExists('patient_master')) {
            $patientTable = 'patient_master';
        }

        if ($patientTable === null) {
            $content = '<div class="alert alert-danger mb-0">Patient master table not found for Document Issue Report.</div>';
            return $this->response->setBody($content);
        }

        $docFields = $this->db->getFieldNames('patient_doc') ?? [];
        $patientFields = $this->db->getFieldNames($patientTable) ?? [];
        $doctorFields = $this->db->getFieldNames('doctor_master') ?? [];
        $formatFields = $this->db->getFieldNames('doc_format_master') ?? [];

        $dateIssueCol = $this->resolveExistingColumn($docFields, ['date_issue', 'issue_date', 'created_at']);
        if ($dateIssueCol === null) {
            $content = '<div class="alert alert-danger mb-0">Issue date column was not found in patient_doc table.</div>';
            return $this->response->setBody($content);
        }

        $docPatientFkCol = $this->resolveExistingColumn($docFields, ['p_id', 'patient_id']);
        $docDoctorFkCol = $this->resolveExistingColumn($docFields, ['dr_id', 'doc_id', 'doctor_id']);
        $docFormatFkCol = $this->resolveExistingColumn($docFields, ['doc_format_id', 'format_id', 'df_id']);

        if ($docPatientFkCol === null || $docDoctorFkCol === null || $docFormatFkCol === null) {
            $content = '<div class="alert alert-danger mb-0">Document link columns are missing in patient_doc table.</div>';
            return $this->response->setBody($content);
        }

        $patientPkCol = $this->resolveExistingColumn($patientFields, ['id']);
        $patientNameCol = $this->resolveExistingColumn($patientFields, ['p_fname', 'patient_name', 'name']);
        $patientRelativeCol = $this->resolveExistingColumn($patientFields, ['p_relative']);
        $patientRelativeNameCol = $this->resolveExistingColumn($patientFields, ['p_rname', 'relative_name']);
        $patientCodeCol = $this->resolveExistingColumn($patientFields, ['p_code', 'patient_code', 'uhid_no']);
        $doctorPkCol = $this->resolveExistingColumn($doctorFields, ['id']);
        $doctorNameCol = $this->resolveExistingColumn($doctorFields, ['p_fname', 'name', 'doc_name']);
        $formatPkCol = $this->resolveExistingColumn($formatFields, ['df_id', 'id']);
        $formatNameCol = $this->resolveExistingColumn($formatFields, ['doc_name', 'name', 'format_name']);

        if ($patientPkCol === null || $patientNameCol === null || $doctorPkCol === null || $doctorNameCol === null || $formatPkCol === null || $formatNameCol === null) {
            $content = '<div class="alert alert-danger mb-0">Required patient columns are missing for Document Issue Report.</div>';
            return $this->response->setBody($content);
        }

        try {
            $builder = $this->db->table('patient_doc d');
            $builder->select('d.id AS doc_id')
                ->select('f.' . $formatNameCol . ' AS doc_name', false)
                ->select('doc.' . $doctorNameCol . ' AS dr_name', false)
                ->select('doc.' . $doctorPkCol . ' AS dr_id', false)
                ->select('p.' . $patientNameCol . ' AS p_fname', false)
                ->select('p.' . $patientPkCol . ' AS id', false)
                ->select($patientRelativeNameCol !== null ? ('p.' . $patientRelativeNameCol . ' AS p_rname') : "'' AS p_rname", false)
                ->select($patientRelativeCol !== null ? ('p.' . $patientRelativeCol . ' AS p_relative') : "'' AS p_relative", false)
                ->select($patientCodeCol !== null ? ('p.' . $patientCodeCol . ' AS p_code') : "'' AS p_code", false)
                ->select('d.' . $dateIssueCol . ' AS date_issue', false)
                ->select("DATE_FORMAT(d." . $dateIssueCol . ", '%d-%m-%Y') AS str_date_issue", false)
                ->join($patientTable . ' p', 'p.' . $patientPkCol . '=d.' . $docPatientFkCol, 'inner')
                ->join('doctor_master doc', 'd.' . $docDoctorFkCol . '=doc.' . $doctorPkCol, 'inner')
                ->join('doc_format_master f', 'd.' . $docFormatFkCol . '=f.' . $formatPkCol, 'inner')
                ->where('d.' . $dateIssueCol . ' >=', $minRange)
                ->where('d.' . $dateIssueCol . ' <=', $maxRange)
                ->orderBy('d.' . $dateIssueCol, 'DESC')
                ->orderBy('d.id', 'DESC');

            if ($drId > 0) {
                $builder->where('doc.id', $drId);
            }

            if ($uhidFilter !== '' && $patientCodeCol !== null) {
                $builder->like('p.' . $patientCodeCol, $uhidFilter);
            }

            $rows = $builder->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', 'Document Issue Report query failed: {message}', ['message' => $e->getMessage()]);
            $content = '<div class="alert alert-danger mb-0">Unable to load Document Issue Report data. Please check database schema.</div>';
            return $this->response->setBody($content);
        }

        $data = [
            'rows' => $rows,
            'min_range' => $minRange,
            'max_range' => $maxRange,
            'doctor_id' => $drId,
            'uhid_filter' => $uhidFilter,
        ];

        $content = view('report/document_list_table', $data);
        if ($output === 1) {
            ExportExcel($content, 'document_List_' . date('YmdHis'));
            return $this->response->setBody('');
        }

        return $this->response->setBody($content);
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

    public function echs_ipd_list_main()
    {
        return $this->insurance_ipd_report();
    }

    public function echs_opd_list_main()
    {
        return $this->insurance_opd_report();
    }

    public function echs_ipd_list_data(
        string $dateRange,
        string $orgStatus = '-1',
        string $ipdType = '0',
        int $output = 0
    ) {
        $insuranceId = $this->mapLegacyInsuranceFilter($ipdType);
        return $this->insurance_ipd_report_data($dateRange, $insuranceId, $orgStatus, $output);
    }

    public function echs_opd_list_data(
        string $dateRange,
        string $orgStatus = '-1',
        string $ipdType = '0',
        int $output = 0
    ) {
        $insuranceId = $this->mapLegacyInsuranceFilter($ipdType);
        return $this->insurance_opd_report_data($dateRange, $insuranceId, $orgStatus, $output);
    }

    private function mapLegacyInsuranceFilter(string $legacyType): string
    {
        $legacyValue = (int) trim($legacyType);
        if ($legacyValue > 0) {
            return 'G' . $legacyValue;
        }
        if ($legacyValue < 0) {
            return (string) abs($legacyValue);
        }
        return '0';
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

    public function nabh_audit_report()
    {
        return view('report/nabh_audit_report');
    }

    public function nabh_audit_report_data(
        string $dateRange,
        string $module = 'all',
        string $status = 'all',
        int $output = 0
    ) {
        [$minRange, $maxRange] = $this->parseDateRange($dateRange);

        $module = strtolower(trim($module));
        if (! in_array($module, ['all', 'ipd', 'opd', 'radiology'], true)) {
            $module = 'all';
        }

        $status = strtolower(trim($status));
        if (! in_array($status, ['all', 'critical-missing', 'compliant'], true)) {
            $status = 'all';
        }

        $ipdRows = [];
        $opdRows = [];

        if ($module === 'all' || $module === 'ipd') {
            $ipdRows = $this->getIpdNabhAuditRows($minRange, $maxRange);
        }

        if ($module === 'all' || $module === 'opd') {
            $opdRows = $this->getOpdNabhAuditRows($minRange, $maxRange);
        }

        $radiologyRows = [];
        if ($module === 'all' || $module === 'radiology') {
            $radiologyRows = $this->getRadiologyNabhAuditRows($minRange, $maxRange);
        }

        $rows = array_merge($ipdRows, $opdRows, $radiologyRows);

        if ($status === 'critical-missing') {
            $rows = array_values(array_filter($rows, static function (array $row): bool {
                return (int) ($row['critical_missing_count'] ?? 0) > 0;
            }));
        } elseif ($status === 'compliant') {
            $rows = array_values(array_filter($rows, static function (array $row): bool {
                return (int) ($row['critical_missing_count'] ?? 0) === 0;
            }));
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) ($b['encounter_datetime'] ?? ''), (string) ($a['encounter_datetime'] ?? ''));
        });

        $summary = $this->buildNabhSummary($rows);

        $data = [
            'rows' => $rows,
            'summary' => $summary,
            'min_range' => $minRange,
            'max_range' => $maxRange,
            'module_filter' => $module,
            'status_filter' => $status,
        ];

        $content = view('report/nabh_audit_report_table', $data);
        if ($output === 1) {
            ExportExcel($content, 'NABH_Audit_Report');
            return $this->response->setBody('');
        }

        return $this->response->setBody($content);
    }

    private function buildNabhSummary(array $rows): array
    {
        $summary = [
            'all' => ['label' => 'Overall', 'total' => 0, 'compliant' => 0, 'critical_missing' => 0, 'avg_completion' => 0.0],
            'ipd' => ['label' => 'IPD Discharge', 'total' => 0, 'compliant' => 0, 'critical_missing' => 0, 'avg_completion' => 0.0],
            'opd' => ['label' => 'OPD Consult', 'total' => 0, 'compliant' => 0, 'critical_missing' => 0, 'avg_completion' => 0.0],
            'radiology' => ['label' => 'Radiology Edit', 'total' => 0, 'compliant' => 0, 'critical_missing' => 0, 'avg_completion' => 0.0],
        ];

        $completionAccumulator = [
            'all' => 0.0,
            'ipd' => 0.0,
            'opd' => 0.0,
            'radiology' => 0.0,
        ];

        foreach ($rows as $row) {
            $module = strtolower((string) ($row['module_key'] ?? 'all'));
            if (! isset($summary[$module])) {
                $module = 'all';
            }

            $isCompliant = (int) ($row['critical_missing_count'] ?? 0) === 0;
            $completion = (float) ($row['completion_percent'] ?? 0);

            $summary['all']['total']++;
            $completionAccumulator['all'] += $completion;
            if ($isCompliant) {
                $summary['all']['compliant']++;
            } else {
                $summary['all']['critical_missing']++;
            }

            if ($module !== 'all') {
                $summary[$module]['total']++;
                $completionAccumulator[$module] += $completion;
                if ($isCompliant) {
                    $summary[$module]['compliant']++;
                } else {
                    $summary[$module]['critical_missing']++;
                }
            }
        }

        foreach ($summary as $key => $item) {
            $total = (int) ($item['total'] ?? 0);
            $summary[$key]['avg_completion'] = $total > 0
                ? round($completionAccumulator[$key] / $total, 1)
                : 0.0;
        }

        return $summary;
    }

    private function getIpdNabhAuditRows(string $minRange, string $maxRange): array
    {
        if (! $this->db->tableExists('ipd_master')) {
            return [];
        }

        $ipdFields = $this->db->getFieldNames('ipd_master') ?? [];
        if (! in_array('id', $ipdFields, true)) {
            return [];
        }

        $dateCol = $this->resolveExistingColumn($ipdFields, [
            'discharge_date',
            'discharged_at',
            'discharge_datetime',
            'register_date',
            'admit_date',
            'created_at',
        ]);
        if ($dateCol === null) {
            return [];
        }

        $ipdCodeCol = $this->resolveExistingColumn($ipdFields, ['ipd_code', 'ipd_no', 'ipd_number']);
        $patientFkCol = $this->resolveExistingColumn($ipdFields, ['p_id', 'patient_id']);
        $statusCol = $this->resolveExistingColumn($ipdFields, ['ipd_status', 'discharge_status', 'discharg_status', 'is_discharged']);

        $builder = $this->db->table('ipd_master i')
            ->select('i.id as encounter_id')
            ->select('i.' . $dateCol . ' as encounter_datetime', false);

        if ($ipdCodeCol !== null) {
            $builder->select('i.' . $ipdCodeCol . ' as encounter_code', false);
        } else {
            $builder->select("'' as encounter_code", false);
        }

        if ($patientFkCol !== null) {
            $builder->select('i.' . $patientFkCol . ' as patient_id', false);
        } else {
            $builder->select('0 as patient_id', false);
        }

        $patientNameExpr = "''";
        $patientCodeExpr = "''";
        if ($patientFkCol !== null && $this->db->tableExists('patient_master')) {
            $patientFields = $this->db->getFieldNames('patient_master') ?? [];
            $nameCol = $this->resolveExistingColumn($patientFields, ['p_fname', 'patient_name', 'name']);
            $codeCol = $this->resolveExistingColumn($patientFields, ['p_code', 'patient_code', 'uhid_no']);
            $pkCol = $this->resolveExistingColumn($patientFields, ['id']);

            if ($pkCol !== null) {
                $builder->join('patient_master p', 'p.' . $pkCol . ' = i.' . $patientFkCol, 'left');
                if ($nameCol !== null) {
                    $patientNameExpr = 'p.' . $nameCol;
                }
                if ($codeCol !== null) {
                    $patientCodeExpr = 'p.' . $codeCol;
                }
            }
        }

        $builder->select($patientNameExpr . ' as patient_name', false)
            ->select($patientCodeExpr . ' as patient_code', false)
            ->where('i.' . $dateCol . ' >=', $minRange)
            ->where('i.' . $dateCol . ' <=', $maxRange)
            ->orderBy('i.' . $dateCol, 'DESC');

        if ($statusCol !== null) {
            $builder->where('i.' . $statusCol, 1);
        }

        $resultRows = $builder->get()->getResultArray();
        $finalRows = [];

        foreach ($resultRows as $row) {
            $ipdId = (int) ($row['encounter_id'] ?? 0);
            if ($ipdId <= 0) {
                continue;
            }

            $checklist = $this->buildIpdChecklistForAudit($ipdId, (int) ($row['patient_id'] ?? 0));
            $total = (int) ($checklist['total_count'] ?? 0);
            $okCount = (int) ($checklist['ok_count'] ?? 0);

            $finalRows[] = [
                'module_key' => 'ipd',
                'module_label' => 'IPD Discharge',
                'encounter_id' => $ipdId,
                'encounter_code' => (string) ($row['encounter_code'] ?? ''),
                'patient_code' => (string) ($row['patient_code'] ?? ''),
                'patient_name' => (string) ($row['patient_name'] ?? ''),
                'encounter_datetime' => (string) ($row['encounter_datetime'] ?? ''),
                'ok_count' => $okCount,
                'total_count' => $total,
                'completion_percent' => $total > 0 ? round(($okCount * 100) / $total, 1) : 0.0,
                'critical_missing_count' => (int) ($checklist['critical_missing_count'] ?? 0),
                'critical_missing_labels' => implode(', ', $checklist['critical_missing'] ?? []),
            ];
        }

        return $finalRows;
    }

    private function buildIpdChecklistForAudit(int $ipdId, int $patientId): array
    {
        $hasAdmissionReason = $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_complaint',
            $ipdId,
            ['comp_report', 'comp_remark']
        ) || $this->tableHasAnyNonEmptyValueByIpd('ipd_discharge_complaint_remark', $ipdId, ['comp_remark']);

        $hasDiagnosis = $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_diagnosis',
            $ipdId,
            ['comp_report', 'comp_remark']
        ) || $this->tableHasAnyNonEmptyValueByIpd('ipd_discharge_diagnosis_remark', $ipdId, ['comp_remark']);

        $hasCourse = $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_course',
            $ipdId,
            ['comp_report', 'comp_remark']
        ) || $this->tableHasAnyNonEmptyValueByIpd('ipd_discharge_course_remark', $ipdId, ['comp_remark']);

        $hasProcedure = $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_surgery',
            $ipdId,
            ['surgery_name', 'surgery_remark']
        ) || $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_procedure',
            $ipdId,
            ['procedure_name', 'procedure_remark']
        );

        $hasCondition = $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_1_b_final',
            $ipdId,
            ['discharge_condition', 'condition_at_discharge', 'condition_remark', 'remark']
        ) || $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_general_exam_col',
            $ipdId,
            ['value']
        );

        $hasMedication = $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_drug',
            $ipdId,
            ['drug_name', 'drug_dose', 'drug_day']
        ) || $this->tableHasAnyNonEmptyValueByIpd(
            'ipd_discharge_prescrption_prescribed',
            $ipdId,
            ['med_name', 'med_type', 'qty', 'no_of_days', 'remark']
        );

        $instructionText = $this->firstNonEmptyValueByIpd(
            'ipd_discharge_instructions',
            $ipdId,
            ['comp_remark', 'footer_text']
        );
        $reviewAfter = $this->firstNonEmptyValueByIpd('ipd_discharge_instructions', $ipdId, ['review_after']);
        $hasFollowUp = trim($instructionText) !== '' || trim($reviewAfter) !== '';

        $allergySnapshot = $this->getLatestOpdAllergySnapshotByPatient($patientId);
        $hasAllergyAdr = trim((string) ($allergySnapshot['drug_allergy_status'] ?? '')) !== ''
            || trim((string) ($allergySnapshot['drug_allergy_details'] ?? '')) !== ''
            || trim((string) ($allergySnapshot['adr_history'] ?? '')) !== '';

        $instructionLower = strtolower((string) $instructionText);
        $hasRedFlags = strpos($instructionLower, 'emergency') !== false
            || strpos($instructionLower, 'warning') !== false
            || strpos($instructionLower, 'red flag') !== false
            || strpos($instructionLower, 'immediately') !== false
            || strpos($instructionLower, 'return if') !== false;

        $items = [
            ['label' => 'Reason for admission / presenting complaints', 'ok' => $hasAdmissionReason, 'critical' => true],
            ['label' => 'Final diagnosis documented', 'ok' => $hasDiagnosis, 'critical' => true],
            ['label' => 'Course / treatment in hospital documented', 'ok' => $hasCourse, 'critical' => true],
            ['label' => 'Surgery/procedure documented (if applicable)', 'ok' => $hasProcedure, 'critical' => false],
            ['label' => 'Condition at discharge documented', 'ok' => $hasCondition, 'critical' => true],
            ['label' => 'Discharge medication documented', 'ok' => $hasMedication, 'critical' => true],
            ['label' => 'Follow-up instructions / review plan documented', 'ok' => $hasFollowUp, 'critical' => true],
            ['label' => 'Drug allergy / ADR history documented', 'ok' => $hasAllergyAdr, 'critical' => false],
            ['label' => 'Red-flag / emergency return advice documented', 'ok' => $hasRedFlags, 'critical' => false],
        ];

        $criticalMissing = [];
        $okCount = 0;
        foreach ($items as $item) {
            if (! empty($item['ok'])) {
                $okCount++;
                continue;
            }
            if (! empty($item['critical'])) {
                $criticalMissing[] = (string) ($item['label'] ?? '');
            }
        }

        return [
            'ok_count' => $okCount,
            'total_count' => count($items),
            'critical_missing' => $criticalMissing,
            'critical_missing_count' => count($criticalMissing),
        ];
    }

    private function getOpdNabhAuditRows(string $minRange, string $maxRange): array
    {
        if (! $this->db->tableExists('opd_master')) {
            return [];
        }

        $opdFields = $this->db->getFieldNames('opd_master') ?? [];
        if (! in_array('opd_id', $opdFields, true)) {
            return [];
        }

        $dateCol = $this->resolveExistingColumn($opdFields, [
            'apointment_date',
            'appointment_date',
            'created_at',
            'entry_date',
        ]);
        if ($dateCol === null) {
            return [];
        }

        $opdCodeCol = $this->resolveExistingColumn($opdFields, ['opd_code', 'opd_no']);
        $patientFkCol = $this->resolveExistingColumn($opdFields, ['p_id', 'patient_id']);

        $builder = $this->db->table('opd_master o')
            ->select('o.opd_id as encounter_id')
            ->select('o.' . $dateCol . ' as encounter_datetime', false);

        if ($opdCodeCol !== null) {
            $builder->select('o.' . $opdCodeCol . ' as encounter_code', false);
        } else {
            $builder->select("'' as encounter_code", false);
        }

        if ($patientFkCol !== null) {
            $builder->select('o.' . $patientFkCol . ' as patient_id', false);
        } else {
            $builder->select('0 as patient_id', false);
        }

        $patientNameExpr = "''";
        $patientCodeExpr = "''";
        if ($patientFkCol !== null && $this->db->tableExists('patient_master')) {
            $patientFields = $this->db->getFieldNames('patient_master') ?? [];
            $nameCol = $this->resolveExistingColumn($patientFields, ['p_fname', 'patient_name', 'name']);
            $codeCol = $this->resolveExistingColumn($patientFields, ['p_code', 'patient_code', 'uhid_no']);
            $pkCol = $this->resolveExistingColumn($patientFields, ['id']);

            if ($pkCol !== null) {
                $builder->join('patient_master p', 'p.' . $pkCol . ' = o.' . $patientFkCol, 'left');
                if ($nameCol !== null) {
                    $patientNameExpr = 'p.' . $nameCol;
                }
                if ($codeCol !== null) {
                    $patientCodeExpr = 'p.' . $codeCol;
                }
            }
        }

        $prescriptionCols = [];
        if ($this->db->tableExists('opd_prescription')) {
            $prescriptionFields = $this->db->getFieldNames('opd_prescription') ?? [];
            if (in_array('id', $prescriptionFields, true) && in_array('opd_id', $prescriptionFields, true)) {
                $builder->join(
                    'opd_prescription pr',
                    'pr.id = (SELECT MAX(px.id) FROM opd_prescription px WHERE px.opd_id = o.opd_id)',
                    'left',
                    false
                );

                foreach ([
                    'complaints',
                    'diagnosis',
                    'Provisional_diagnosis',
                    'Finding_Examinations',
                    'Prescriber_Remarks',
                    'investigation',
                    'advice',
                    'next_visit',
                    'refer_to',
                    'bp',
                    'diastolic',
                    'pulse',
                    'temp',
                    'spo2',
                    'rr_min',
                    'height',
                    'weight',
                    'drug_allergy_status',
                    'drug_allergy_details',
                    'adr_history',
                    'current_medications',
                ] as $column) {
                    if (in_array($column, $prescriptionFields, true)) {
                        $builder->select('pr.' . $column, false);
                        $prescriptionCols[] = $column;
                    }
                }
            }
        }

        $builder->select($patientNameExpr . ' as patient_name', false)
            ->select($patientCodeExpr . ' as patient_code', false)
            ->where('o.' . $dateCol . ' >=', $minRange)
            ->where('o.' . $dateCol . ' <=', $maxRange)
            ->orderBy('o.' . $dateCol, 'DESC');

        $resultRows = $builder->get()->getResultArray();
        $finalRows = [];

        foreach ($resultRows as $row) {
            $checklist = $this->buildOpdChecklistForAudit($row, $prescriptionCols);
            $total = (int) ($checklist['total_count'] ?? 0);
            $okCount = (int) ($checklist['ok_count'] ?? 0);

            $finalRows[] = [
                'module_key' => 'opd',
                'module_label' => 'OPD Consult',
                'encounter_id' => (int) ($row['encounter_id'] ?? 0),
                'encounter_code' => (string) ($row['encounter_code'] ?? ''),
                'patient_code' => (string) ($row['patient_code'] ?? ''),
                'patient_name' => (string) ($row['patient_name'] ?? ''),
                'encounter_datetime' => (string) ($row['encounter_datetime'] ?? ''),
                'ok_count' => $okCount,
                'total_count' => $total,
                'completion_percent' => $total > 0 ? round(($okCount * 100) / $total, 1) : 0.0,
                'critical_missing_count' => (int) ($checklist['critical_missing_count'] ?? 0),
                'critical_missing_labels' => implode(', ', $checklist['critical_missing'] ?? []),
            ];
        }

        return $finalRows;
    }

    private function getRadiologyNabhAuditRows(string $minRange, string $maxRange): array
    {
        if (! $this->db->tableExists('lab_log')) {
            return [];
        }

        $logFields = $this->db->getFieldNames('lab_log') ?? [];
        foreach (['lab_repo_id', 'log_insert_time', 'log_type', 'log_Faults', 'comments'] as $requiredField) {
            if (! in_array($requiredField, $logFields, true)) {
                return [];
            }
        }

        $builder = $this->db->table('lab_log lg')
            ->select('lg.lab_repo_id as encounter_id')
            ->select('lg.log_insert_time as encounter_datetime')
            ->select('lg.log_by as edited_by')
            ->select('lg.comments as comments')
            ->where('lg.log_type', 'Report Edit')
            ->where('lg.log_Faults', 'Radiology')
            ->where('lg.log_insert_time >=', $minRange)
            ->where('lg.log_insert_time <=', $maxRange)
            ->orderBy('lg.id', 'DESC');

        if ($this->db->tableExists('lab_request')) {
            $labReqFields = $this->db->getFieldNames('lab_request') ?? [];
            if (in_array('id', $labReqFields, true)) {
                $builder->join('lab_request lr', 'lr.id = lg.lab_repo_id', 'left');

                if (in_array('report_name', $labReqFields, true)) {
                    $builder->select('lr.report_name as report_name');
                } else {
                    $builder->select("'' as report_name", false);
                }

                if (in_array('patient_id', $labReqFields, true) && $this->db->tableExists('patient_master')) {
                    $patientFields = $this->db->getFieldNames('patient_master') ?? [];
                    if (in_array('id', $patientFields, true)) {
                        $builder->join('patient_master p', 'p.id = lr.patient_id', 'left');
                        if (in_array('p_code', $patientFields, true)) {
                            $builder->select('p.p_code as patient_code');
                        } else {
                            $builder->select("'' as patient_code", false);
                        }
                        if (in_array('p_fname', $patientFields, true)) {
                            $builder->select('p.p_fname as patient_name');
                        } else {
                            $builder->select("'' as patient_name", false);
                        }
                    }
                } else {
                    $builder->select("'' as patient_code", false);
                    $builder->select("'' as patient_name", false);
                }
            }
        }

        $resultRows = $builder->get()->getResultArray();
        $finalRows = [];

        foreach ($resultRows as $row) {
            $reason = $this->extractEditReasonFromLabLogComment((string) ($row['comments'] ?? ''));
            $hasReason = $reason !== '' && strtolower($reason) !== 'na';
            $encounterCode = trim((string) ($row['report_name'] ?? ''));
            if ($encounterCode === '') {
                $encounterCode = 'REQ#' . (int) ($row['encounter_id'] ?? 0);
            }

            $finalRows[] = [
                'module_key' => 'radiology',
                'module_label' => 'Radiology Edit',
                'encounter_id' => (int) ($row['encounter_id'] ?? 0),
                'encounter_code' => $encounterCode,
                'patient_code' => (string) ($row['patient_code'] ?? ''),
                'patient_name' => (string) ($row['patient_name'] ?? ''),
                'encounter_datetime' => (string) ($row['encounter_datetime'] ?? ''),
                'ok_count' => $hasReason ? 1 : 0,
                'total_count' => 1,
                'completion_percent' => $hasReason ? 100.0 : 0.0,
                'critical_missing_count' => $hasReason ? 0 : 1,
                'critical_missing_labels' => $hasReason ? '-' : 'Edit reason missing',
            ];
        }

        return $finalRows;
    }

    private function extractEditReasonFromLabLogComment(string $comment): string
    {
        $comment = trim($comment);
        if ($comment === '') {
            return '';
        }

        if (preg_match('/\[reason:(.*?)(?:\]\s*\[|\]$)/i', $comment, $matches) === 1) {
            return trim((string) ($matches[1] ?? ''));
        }

        $startPos = stripos($comment, '[reason:');
        if ($startPos === false) {
            return '';
        }

        $raw = substr($comment, $startPos + 8);
        $beforePos = stripos($raw, '[before:');
        if ($beforePos !== false) {
            $raw = substr($raw, 0, $beforePos);
        }

        return trim(str_replace(']', '', $raw));
    }

    private function buildOpdChecklistForAudit(array $row, array $availableCols): array
    {
        $hasComplaints = $this->rowHasAnyNonEmptyValue($row, ['complaints']);
        $hasDiagnosis = $this->rowHasAnyNonEmptyValue($row, ['diagnosis', 'Provisional_diagnosis']);
        $hasClinicalCourse = $this->rowHasAnyNonEmptyValue($row, ['Finding_Examinations', 'Prescriber_Remarks']);
        $hasProcedure = $this->rowHasAnyNonEmptyValue($row, ['investigation']);
        $hasCondition = $this->rowHasAnyNonEmptyValue($row, ['bp', 'diastolic', 'pulse', 'temp', 'spo2', 'rr_min', 'height', 'weight']);
        $hasMedicationPlan = $this->rowHasAnyNonEmptyValue($row, ['advice', 'current_medications']);
        $hasFollowUp = $this->rowHasAnyNonEmptyValue($row, ['next_visit', 'refer_to']);

        $hasAllergyAdr = $this->rowHasAnyNonEmptyValue($row, ['drug_allergy_status', 'drug_allergy_details', 'adr_history']);

        $adviceText = strtolower(trim((string) ($row['advice'] ?? '')) . ' ' . trim((string) ($row['Prescriber_Remarks'] ?? '')));
        $hasRedFlags = strpos($adviceText, 'emergency') !== false
            || strpos($adviceText, 'warning') !== false
            || strpos($adviceText, 'red flag') !== false
            || strpos($adviceText, 'immediately') !== false
            || strpos($adviceText, 'return if') !== false;

        // If OPD prescription table is unavailable for the row, mark item as not applicable by treating as empty.
        if ($availableCols === []) {
            $hasComplaints = false;
            $hasDiagnosis = false;
            $hasClinicalCourse = false;
            $hasProcedure = false;
            $hasCondition = false;
            $hasMedicationPlan = false;
            $hasFollowUp = false;
            $hasAllergyAdr = false;
            $hasRedFlags = false;
        }

        $items = [
            ['label' => 'Reason for consultation / presenting complaints', 'ok' => $hasComplaints, 'critical' => true],
            ['label' => 'Diagnosis documented', 'ok' => $hasDiagnosis, 'critical' => true],
            ['label' => 'Clinical findings / assessment documented', 'ok' => $hasClinicalCourse, 'critical' => true],
            ['label' => 'Investigations/procedures documented (if applicable)', 'ok' => $hasProcedure, 'critical' => false],
            ['label' => 'Clinical condition / vitals documented', 'ok' => $hasCondition, 'critical' => true],
            ['label' => 'Treatment/medication plan documented', 'ok' => $hasMedicationPlan, 'critical' => true],
            ['label' => 'Follow-up or referral plan documented', 'ok' => $hasFollowUp, 'critical' => true],
            ['label' => 'Drug allergy / ADR history documented', 'ok' => $hasAllergyAdr, 'critical' => false],
            ['label' => 'Red-flag / emergency return advice documented', 'ok' => $hasRedFlags, 'critical' => false],
        ];

        $criticalMissing = [];
        $okCount = 0;
        foreach ($items as $item) {
            if (! empty($item['ok'])) {
                $okCount++;
                continue;
            }
            if (! empty($item['critical'])) {
                $criticalMissing[] = (string) ($item['label'] ?? '');
            }
        }

        return [
            'ok_count' => $okCount,
            'total_count' => count($items),
            'critical_missing' => $criticalMissing,
            'critical_missing_count' => count($criticalMissing),
        ];
    }

    private function resolveExistingColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function rowHasAnyNonEmptyValue(array $row, array $fields): bool
    {
        foreach ($fields as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    private function tableHasAnyNonEmptyValueByIpd(string $table, int $ipdId, array $preferredFields): bool
    {
        if ($ipdId <= 0 || ! $this->db->tableExists($table)) {
            return false;
        }

        $fields = $this->db->getFieldNames($table) ?? [];
        if (! in_array('ipd_id', $fields, true)) {
            return false;
        }

        $selected = [];
        foreach ($preferredFields as $field) {
            if (in_array($field, $fields, true)) {
                $selected[] = $field;
            }
        }

        if ($selected === []) {
            return false;
        }

        $rows = $this->db->table($table)
            ->select(implode(',', $selected))
            ->where('ipd_id', $ipdId)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            if ($this->rowHasAnyNonEmptyValue($row, $selected)) {
                return true;
            }
        }

        return false;
    }

    private function firstNonEmptyValueByIpd(string $table, int $ipdId, array $preferredFields): string
    {
        if ($ipdId <= 0 || ! $this->db->tableExists($table)) {
            return '';
        }

        $fields = $this->db->getFieldNames($table) ?? [];
        if (! in_array('ipd_id', $fields, true)) {
            return '';
        }

        $selected = [];
        foreach ($preferredFields as $field) {
            if (in_array($field, $fields, true)) {
                $selected[] = $field;
            }
        }

        if ($selected === []) {
            return '';
        }

        $builder = $this->db->table($table)->where('ipd_id', $ipdId);
        if (in_array('id', $fields, true)) {
            $builder->orderBy('id', 'DESC');
        }

        $row = $builder->get(1)->getRowArray() ?? [];
        foreach ($selected as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function getLatestOpdAllergySnapshotByPatient(int $patientId): array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('opd_master') || ! $this->db->tableExists('opd_prescription')) {
            return [];
        }

        $opdFields = $this->db->getFieldNames('opd_master') ?? [];
        $rxFields = $this->db->getFieldNames('opd_prescription') ?? [];

        if (! in_array('opd_id', $opdFields, true) || ! in_array('p_id', $opdFields, true) || ! in_array('opd_id', $rxFields, true)) {
            return [];
        }

        $selects = [];
        foreach (['drug_allergy_status', 'drug_allergy_details', 'adr_history'] as $field) {
            if (in_array($field, $rxFields, true)) {
                $selects[] = 'r.' . $field;
            }
        }

        if ($selects === []) {
            return [];
        }

        $builder = $this->db->table('opd_master o')
            ->select(implode(',', $selects), false)
            ->join('opd_prescription r', 'r.opd_id = o.opd_id', 'inner')
            ->where('o.p_id', $patientId);

        if (in_array('apointment_date', $opdFields, true)) {
            $builder->orderBy('o.apointment_date', 'DESC');
        }
        if (in_array('id', $rxFields, true)) {
            $builder->orderBy('r.id', 'DESC');
        }

        $row = $builder->get(1)->getRowArray();
        return is_array($row) ? $row : [];
    }
}
