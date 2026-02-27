<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\InsuranceModel;

class Insurance extends BaseController
{
    public function index(): string
    {
        $model = new InsuranceModel();

        return view('Setting/Insurance/insurance_search_v', [
            'data' => $model->getAll(),
        ]);
    }

    public function create(): string
    {
        return view('Setting/Insurance/insurance_create_V', [
            'errors' => session('errors'),
        ]);
    }

    public function store()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $rules = [
            'input_comp_name' => 'required',
            'input_email' => 'permit_empty|valid_email',
            'input_mphone1' => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => implode(' ', $this->validator->getErrors()),
            ]);
        }

        $model = new InsuranceModel();
        $data = [
            'ins_company_name' => $this->request->getPost('input_comp_name'),
            'ins_contact_number' => $this->request->getPost('input_mphone1'),
            'ins_contact_person_name' => $this->request->getPost('input_cname'),
            'ins_email' => $this->request->getPost('input_email'),
            'gst_no' => $this->request->getPost('input_gst_no'),
            'agreement_start_date' => str_to_MysqlDate((string) $this->request->getPost('input_agreement_start_date')),
            'agreement_end_date' => str_to_MysqlDate((string) $this->request->getPost('input_agreement_end_date')),
        ];

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please Check',
        ]);
    }

    public function record(int $id): string
    {
        $model = new InsuranceModel();

        return view('Setting/Insurance/insurance_profile_V', [
            'data_insurance' => $model->getById($id),
        ]);
    }

    public function update()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new InsuranceModel();
        $id = (int) $this->request->getPost('p_id');

        $data = [
            'ins_company_name' => $this->request->getPost('input_comp_name'),
            'short_name' => $this->request->getPost('input_short_name'),
            'ins_contact_number' => $this->request->getPost('input_mphone1'),
            'ins_contact_person_name' => $this->request->getPost('input_cname'),
            'ins_email' => $this->request->getPost('input_email'),
            'gst_no' => $this->request->getPost('input_gst_no'),
            'agreement_start_date' => str_to_MysqlDate((string) $this->request->getPost('input_agreement_start_date')),
            'agreement_end_date' => str_to_MysqlDate((string) $this->request->getPost('input_agreement_end_date')),
            'active' => $this->request->getPost('chk_active') ? 1 : 0,
            'opd_allowed' => $this->request->getPost('chk_opd_allowed') ? 1 : 0,
            'opd_fee' => $this->request->getPost('input_opd_fee'),
            'opd_rate_direct' => $this->request->getPost('optionsRadios_opd_rate_direct'),
            'opd_desc' => $this->request->getPost('input_opd_fee_desc'),
            'opd_master_rate_discount' => $this->request->getPost('input_opd_master_rate_discount'),
            'opd_credit' => $this->request->getPost('chk_opd_credit') ? 1 : 0,
            'opd_cash' => $this->request->getPost('chk_opd_cash') ? 1 : 0,
            'charge_credit' => $this->request->getPost('chk_charge_credit') ? 1 : 0,
            'charge_rate_direct' => $this->request->getPost('optionsRadios_charge_rate_direct'),
            'charge_rate_dicount' => $this->request->getPost('input_charge_rate_dicount'),
            'med_credit' => $this->request->getPost('chk_med_credit') ? 1 : 0,
        ];

        $updated = $model->updateInsurance($data, $id);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'showcontent' => $updated ? 'Data Saved successfully' : '',
            'error_text' => $updated ? '' : 'Please Check',
        ]);
    }
}
