<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;

class IpdExaminationFields extends BaseController
{
    public function index()
    {
        $this->ensureColumns();
        return view('Setting/Admin/ipd_examination_fields', [
            'rows' => $this->db->table('ipd_discharge_general_exam_col')->orderBy('cat_group')->orderBy('display_order')->orderBy('id')->get()->getResultArray(),
        ]);
    }

    public function save()
    {
        $this->ensureColumns();
        $id = (int) ($this->request->getPost('id') ?? 0);
        $code = strtoupper(trim((string) ($this->request->getPost('col_code') ?? '')));
        $name = trim((string) ($this->request->getPost('col_name') ?? ''));
        $label = trim((string) ($this->request->getPost('col_description') ?? ''));
        if ($code === '' || ! preg_match('/^[A-Z][A-Z0-9_]{1,49}$/', $code) || $name === '' || $label === '') {
            return $this->reply(false, 'Code, field name, and label are required. Use letters, numbers, and underscore in code.');
        }

        $duplicate = $this->db->table('ipd_discharge_general_exam_col')->where('col_code', $code);
        if ($id > 0) {
            $duplicate->where('id !=', $id);
        }
        if ($duplicate->countAllResults() > 0) {
            return $this->reply(false, 'Field code already exists.');
        }

        $data = [
            'col_code' => $code,
            'col_name' => $name,
            'col_description' => $label,
            'col_unit' => trim((string) ($this->request->getPost('col_unit') ?? '')),
            'col_type' => max(0, min(4, (int) ($this->request->getPost('col_type') ?? 0))),
            'col_pre_value' => trim((string) ($this->request->getPost('col_pre_value') ?? '')),
            'cat_group' => (int) ($this->request->getPost('cat_group') ?? 1) === 2 ? 2 : 1,
            'is_active' => (int) ($this->request->getPost('is_active') ?? 0) === 1 ? 1 : 0,
            'display_order' => max(0, (int) ($this->request->getPost('display_order') ?? 0)),
        ];
        $saved = $id > 0
            ? (bool) $this->db->table('ipd_discharge_general_exam_col')->where('id', $id)->update($data)
            : (bool) $this->db->table('ipd_discharge_general_exam_col')->insert($data);

        return $this->reply($saved, $saved ? 'Examination field saved.' : 'Unable to save examination field.');
    }

    public function toggle(int $id)
    {
        $this->ensureColumns();
        $row = $this->db->table('ipd_discharge_general_exam_col')->select('id,is_active')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return $this->reply(false, 'Examination field not found.');
        }
        $active = (int) ($row['is_active'] ?? 0) === 1 ? 0 : 1;
        $saved = (bool) $this->db->table('ipd_discharge_general_exam_col')->where('id', $id)->update(['is_active' => $active]);
        return $this->reply($saved, $saved ? ($active ? 'Field enabled.' : 'Field disabled.') : 'Unable to update field status.');
    }

    private function ensureColumns(): void
    {
        if (! $this->db->fieldExists('is_active', 'ipd_discharge_general_exam_col')) {
            $this->db->query('ALTER TABLE ipd_discharge_general_exam_col ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER cat_group');
        }
        if (! $this->db->fieldExists('display_order', 'ipd_discharge_general_exam_col')) {
            $this->db->query('ALTER TABLE ipd_discharge_general_exam_col ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER is_active');
            $this->db->query('UPDATE ipd_discharge_general_exam_col SET display_order = id WHERE display_order = 0');
        }
    }

    private function reply(bool $ok, string $message)
    {
        return $this->response->setJSON(['ok' => $ok ? 1 : 0, 'message' => $message, 'csrfName' => csrf_token(), 'csrfHash' => csrf_hash()]);
    }
}