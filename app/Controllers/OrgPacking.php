<?php

namespace App\Controllers;

class OrgPacking extends BaseController
{
    public function index()
    {
        return view('org_packing/index');
    }

    public function search()
    {
        $search = $this->request->getPost('search') ?? '';

        $builder = $this->db->table('org_packing p');
        $builder->select('p.id')
            ->select("DATE_FORMAT(p.date_of_create, '%d-%m-%Y') as date_created", false)
            ->select('p.label_no, p.files_status')
            ->select('COUNT(c.id) as no_records', false)
            ->select("IF(p.org_type = 0, 'OPD', 'IPD') as list_type", false)
            ->join('organization_case_master c', 'p.id = c.packing_id', 'left')
            ->groupBy('p.id')
            ->orderBy('p.date_of_create', 'DESC')
            ->limit(50);

        if (!empty($search)) {
            if (is_numeric($search)) {
                $builder->where("(p.label_no LIKE '%" . $this->db->escapeLikeString($search) . "%' OR p.Invoice_no = " . (int)$search . ")", null, false);
            } else {
                $builder->like('p.label_no', $search);
            }
        }

        $packings = $builder->get()->getResult();

        return view('org_packing/search_result', ['packings' => $packings]);
    }

    public function create()
    {
        return view('org_packing/create');
    }

    public function store()
    {
        $rules = [
            'label_no' => 'required|min_length[3]|max_length[30]',
            'org_type' => 'required|in_list[0,1]',
            'date_of_create' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => implode('<br>', $this->validator->getErrors())
            ]);
        }

        $labelNo = $this->request->getPost('label_no');
        $orgType = (int) $this->request->getPost('org_type');
        $dateCreated = $this->request->getPost('date_of_create');

        // Check if label already exists
        $exists = $this->db->table('org_packing')
            ->where('label_no', $labelNo)
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Label No already exists'
            ]);
        }

        $data = [
            'date_of_create' => $dateCreated,
            'label_no' => $labelNo,
            'org_type' => $orgType,
        ];

        $insertId = $this->db->table('org_packing')->insert($data);

        if ($insertId) {
            return $this->response->setJSON([
                'success' => true,
                'insert_id' => $this->db->insertID(),
                'message' => 'Packing created successfully'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to create packing'
        ]);
    }

    public function edit(int $id)
    {
        $packing = $this->db->table('org_packing')
            ->where('id', $id)
            ->get()
            ->getRow();

        if (!$packing) {
            return redirect()->back()->with('error', 'Packing not found');
        }

        // Get cases in this packing
        $cases = $this->db->table('organization_case_master o')
            ->select('o.*, o.case_id_code, o.insurance_company_name')
            ->select('p.p_code, p.p_fname, p.age')
            ->select('o.insurance_no, o.insurance_no_1')
            ->select("IF(o.case_type = 0, 'OPD', 'IPD') as ipd_opd", false)
            ->join('patient_master p', 'o.p_id = p.id', 'inner')
            ->where('o.packing_id', $id)
            ->orderBy('o.packing_short', 'ASC')
            ->get()
            ->getResult();

        return view('org_packing/edit', [
            'packing' => $packing,
            'cases' => $cases,
            'packing_id' => $id
        ]);
    }

    public function update(int $id)
    {
        $rules = [
            'label_no' => 'required|min_length[3]|max_length[30]',
            'org_type' => 'required|in_list[0,1]',
            'date_of_create' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => implode('<br>', $this->validator->getErrors())
            ]);
        }

        $labelNo = $this->request->getPost('label_no');
        $orgType = (int) $this->request->getPost('org_type');
        $dateCreated = $this->request->getPost('date_of_create');

        // Check if label already exists for other records
        $exists = $this->db->table('org_packing')
            ->where('label_no', $labelNo)
            ->where('id !=', $id)
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Label No already exists'
            ]);
        }

        $data = [
            'date_of_create' => $dateCreated,
            'label_no' => $labelNo,
            'org_type' => $orgType,
        ];

        $updated = $this->db->table('org_packing')
            ->where('id', $id)
            ->update($data);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Packing updated successfully'
        ]);
    }

    public function addCase(int $orgId, int $packingId)
    {
        // Check if already added
        $exists = $this->db->table('organization_case_master')
            ->where('id', $orgId)
            ->where('packing_id', $packingId)
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Case already added to this packing'
            ]);
        }

        // Get max short number
        $maxShort = $this->db->table('organization_case_master')
            ->selectMax('packing_short', 'max_short')
            ->where('packing_id', $packingId)
            ->get()
            ->getRow();

        $nextShort = ((int) ($maxShort->max_short ?? 0)) + 1;

        $user = auth()->user();
        $userName = ($user->username ?? 'System') . ' [' . date('d-m-Y H:i:s') . ']';

        $data = [
            'packing_id' => $packingId,
            'packing_update_by' => $userName,
            'packing_short' => $nextShort
        ];

        $this->db->table('organization_case_master')
            ->where('id', $orgId)
            ->update($data);

        return $this->getCasesList($packingId);
    }

    public function removeCase(int $orgId, int $packingId)
    {
        $user = auth()->user();
        $userName = ($user->username ?? 'System') . ' [' . date('d-m-Y H:i:s') . ']';

        $data = [
            'packing_id' => 0,
            'packing_update_by' => $userName,
            'packing_short' => 0
        ];

        $this->db->table('organization_case_master')
            ->where('id', $orgId)
            ->update($data);

        return $this->getCasesList($packingId);
    }

    private function getCasesList(int $packingId)
    {
        $cases = $this->db->table('organization_case_master o')
            ->select('o.*, o.case_id_code, o.insurance_company_name')
            ->select('p.p_code, p.p_fname, p.age')
            ->select('o.insurance_no, o.insurance_no_1')
            ->select("IF(o.case_type = 0, 'OPD', 'IPD') as ipd_opd", false)
            ->join('patient_master p', 'o.p_id = p.id', 'inner')
            ->where('o.packing_id', $packingId)
            ->orderBy('o.packing_short', 'ASC')
            ->get()
            ->getResult();

        return view('org_packing/cases_list', [
            'cases' => $cases,
            'packing_id' => $packingId
        ]);
    }

    public function printList(int $packingId)
    {
        $packing = $this->db->table('org_packing')
            ->where('id', $packingId)
            ->get()
            ->getRow();

        if (!$packing) {
            return redirect()->back()->with('error', 'Packing not found');
        }

        if ($packing->org_type == 0) {
            return $this->printOpdList($packingId);
        } else {
            return $this->printIpdList($packingId);
        }
    }

    private function printOpdList(int $packingId)
    {
        $cases = $this->db->table('organization_case_master org')
            ->select('org.*, org.case_id_code')
            ->select('p.p_code, p.p_fname, p.age')
            ->select("IF(org.case_type = 0, 'OPD', 'IPD') as ipd_opd", false)
            ->select('(org.inv_opd_amt + org.inv_opd_charge_amt + org.inv_opd_med_amt) as claim_amt', false)
            ->select("DATE_FORMAT(MIN(i.inv_date), '%d-%m-%Y') as opd_date", false)
            ->join('patient_master p', 'org.p_id = p.id', 'inner')
            ->join('invoice_master i', 'org.id = i.insurance_case_id', 'left')
            ->where('org.packing_id', $packingId)
            ->groupBy('org.id')
            ->orderBy('org.packing_short', 'ASC')
            ->get()
            ->getResult();

        $packing = $this->db->table('org_packing')->where('id', $packingId)->get()->getRow();

        return view('org_packing/print_opd', [
            'cases' => $cases,
            'packing' => $packing
        ]);
    }

    private function printIpdList(int $packingId)
    {
        $cases = $this->db->table('organization_case_master org')
            ->select('org.*, org.case_id_code')
            ->select('p.p_code, p.p_fname, p.age')
            ->select('i.ipd_code')
            ->select("IF(org.case_type = 0, 'OPD', 'IPD') as ipd_opd", false)
            ->select("DATE_FORMAT(i.register_date, '%d-%m-%Y') as admit_date", false)
            ->select("DATE_FORMAT(i.discharge_date, '%d-%m-%Y') as discharge_date", false)
            ->select('((i.charge_amount + i.chargeamount1 + i.chargeamount2 + i.med_amount) - (i.Discount + i.Discount2 + i.Discount3)) as claim_amt', false)
            ->join('patient_master p', 'org.p_id = p.id', 'inner')
            ->join('ipd_master i', 'org.id = i.case_id', 'left')
            ->where('org.packing_id', $packingId)
            ->orderBy('org.packing_short', 'ASC')
            ->get()
            ->getResult();

        $packing = $this->db->table('org_packing')->where('id', $packingId)->get()->getRow();

        return view('org_packing/print_ipd', [
            'cases' => $cases,
            'packing' => $packing
        ]);
    }

    public function searchCases()
    {
        $search = $this->request->getPost('q');
        $orgType = (int) $this->request->getPost('org_type');
        $packingId = (int) $this->request->getPost('packing_id');

        if (empty($search) || strlen($search) < 2) {
            return $this->response->setJSON([]);
        }

        $builder = $this->db->table('organization_case_master o')
            ->select('o.id')
            ->select("CONCAT(o.case_id_code, ' - ', p.p_fname, ' (', p.p_code, ') - ', o.insurance_company_name) as text", false)
            ->join('patient_master p', 'o.p_id = p.id', 'inner')
            ->where('o.case_type', $orgType)
            ->where('(o.packing_id = 0 OR o.packing_id IS NULL)', null, false)
            ->groupStart()
                ->like('o.case_id_code', $search)
                ->orLike('p.p_fname', $search)
                ->orLike('p.p_code', $search)
                ->orLike('o.insurance_no', $search)
            ->groupEnd()
            ->limit(20);

        $results = $builder->get()->getResult();

        return $this->response->setJSON($results);
    }
}
