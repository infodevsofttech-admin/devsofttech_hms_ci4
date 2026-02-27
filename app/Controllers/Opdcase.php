<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OpdModel;

class Opdcase extends BaseController
{
    public function addopd(int $pno)
    {
        $sql = "select *, if(gender=1,'Male','Female') as xgender
            from patient_master where id='" . (int) $pno . "'";
        $query = $this->db->query($sql);
        $data['person_info'] = $query->getResult();

        if (empty($data['person_info'])) {
            return $this->response->setStatusCode(404)->setBody('Patient not found');
        }

        $row = $data['person_info'][0];
        $row->age = get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '');

        $sql = "select d.id, d.p_fname, group_concat(m.SpecName) as SpecName
            from doctor_master d
            join doc_spec s join med_spec m on d.id = s.doc_id and s.med_spec_id = m.id
            where d.active = 1
            group by d.id";
        $query = $this->db->query($sql);
        $data['doc_spec_l'] = $query->getResult();

        $cards = $this->db->table('hc_insurance_card')
            ->where('p_id', $pno)
            ->get()
            ->getResult();

        $insuranceId = 0;
        if (!empty($cards)) {
            $insuranceId = (int) ($cards[0]->insurance_id ?? 0);
        } elseif (!empty($row->insurance_id)) {
            $insuranceId = (int) $row->insurance_id;
        }

        return view('billing/opd_app_insurance_V', [
            'person_info' => $data['person_info'],
            'doc_spec_l' => $data['doc_spec_l'],
            'insurance_cards' => $cards,
            'insurance_id' => $insuranceId,
        ]);
    }

    public function showfee()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'content' => '',
                'error_text' => 'Invalid request',
            ]);
        }

        $docId = (int) $this->request->getPost('doc_id');
        $insuranceId = (int) $this->request->getPost('insurance_id');

        $sql = "select d.id, d.p_fname, group_concat(m.SpecName) as SpecName,
            if(d.gender=1,'Male','Female') as xGender
            from doctor_master d
            join doc_spec s join med_spec m on d.id = s.doc_id and s.med_spec_id = m.id
            where d.active = 1 and d.id=" . $docId . " group by d.id";
        $query = $this->db->query($sql);
        $docInfo = $query->getResult();

        $sql = "select * from hc_insurance where id=" . $insuranceId;
        $query = $this->db->query($sql);
        $opdFee = $query->getResult();

        $content = '';
        $content .= '<h5><strong>Name :</strong> ' . esc($docInfo[0]->p_fname ?? '') .
            ' <strong>/ Specialization:</strong> ' . esc($docInfo[0]->SpecName ?? '') .
            ' <strong>/ Gender :</strong> ' . esc($docInfo[0]->xGender ?? '') . ' </h5>';
        $content .= '<input type="hidden" name="doc_id" id="doc_id" value="' . esc($docInfo[0]->id ?? 0) . '" />';

        if (count($opdFee) > 0) {
            foreach ($opdFee as $row) {
                $content .= '<label class="d-block">';
                $content .= '<input type="radio" name="fee_id" id="fee_id" class="form-check-input me-1" checked value="' . (int) $row->id . '"> ';
                $content .= 'Rs. ' . esc($row->opd_fee) . ' [Insurance OPD Fee Apply]';
                $content .= '</label>';
            }
        } else {
            $content .= '<div class="alert alert-warning">No insurance fee found.</div>';
        }

        return $this->response->setJSON([
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
            'content' => $content,
        ]);
    }

    public function confirm_opd()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'insertid' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        $docId = (int) $this->request->getPost('doc_id');
        $feeId = (int) $this->request->getPost('fee_id');
        $pid = (int) $this->request->getPost('pid');
        $insuranceId = (int) $this->request->getPost('insurance_id');
        $appointmentDate = (string) $this->request->getPost('datepicker_appointment');

        if ($docId <= 0 || $feeId <= 0 || $pid <= 0 || $insuranceId <= 0 || $appointmentDate === '') {
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => 'Missing required fields.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $sql = "select * from patient_master where id=" . $pid;
        $query = $this->db->query($sql);
        $personInfo = $query->getResult();

        $sql = "select d.id, d.p_fname, group_concat(m.SpecName) as SpecName,
            if(d.gender=1,'Male','Female') as xGender
            from doctor_master d
            join doc_spec s join med_spec m on d.id = s.doc_id and s.med_spec_id = m.id
            where d.active=1 and d.id=" . $docId . " group by d.id";
        $query = $this->db->query($sql);
        $docInfo = $query->getResult();

        $sql = "select * from hc_insurance where id=" . $insuranceId;
        $query = $this->db->query($sql);
        $insurance = $query->getResult();

        if (count($personInfo) === 0 || count($docInfo) === 0 || count($insurance) === 0) {
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => 'Invalid patient, doctor, or insurance selection.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $sql = "select * from opd_master where p_id=" . $pid . " order by opd_id desc";
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $userId = $user->id ?? '';
        $userNameInfo = $userLabel . ' [' . date('d-m-Y H:i:s') . '] ' . $userId;

        $opdFeeAmount = $insurance[0]->opd_fee ?? 0;
        $opdFeeDesc = $insurance[0]->opd_desc ?? '';

        $insert = [
            'p_id' => $pid,
            'P_name' => strtoupper((string) $personInfo[0]->p_fname),
            'doc_id' => $docId,
            'insurance_id' => $insuranceId,
            'opd_fee_id' => $feeId,
            'opd_fee_amount' => $opdFeeAmount,
            'opd_fee_gross_amount' => $opdFeeAmount,
            'opd_fee_desc' => $opdFeeDesc,
            'doc_name' => $docInfo[0]->p_fname,
            'doc_spec' => $docInfo[0]->SpecName,
            'apointment_date' => str_to_MysqlDate($appointmentDate),
            'opd_fee_type' => '1',
            'prepared_by' => $userNameInfo,
        ];

        if (count($opdMaster) > 0) {
            $insert['last_opdvisit_date'] = $opdMaster[0]->apointment_date;
        }

        $opdModel = new OpdModel();
        $insertId = $opdModel->insertOpd($insert);

        if ($insertId > 0) {
            $this->db->table('patient_master')
                ->where('id', $pid)
                ->update(['last_visit' => str_to_MysqlDate($appointmentDate)]);
        }

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? 'OPD Register' : 'OPD already exists for today.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }
}
