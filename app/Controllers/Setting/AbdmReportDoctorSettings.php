<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;

class AbdmReportDoctorSettings extends BaseController
{
    /**
     * @var array<string,string>
     */
    private array $mappingKeys = [
        'ABDM_DOC_PATHOLOGY' => 'Pathology / Lab Default',
        'ABDM_DOC_XRAY' => 'X-Ray',
        'ABDM_DOC_CTSCAN' => 'CT Scan',
        'ABDM_DOC_ULTRASOUND' => 'Ultrasound (USG)',
        'ABDM_DOC_MRI' => 'MRI',
        'ABDM_DOC_RADIOLOGY' => 'Radiology Default (fallback)',
    ];

    public function index()
    {
        if (! $this->canManageSettings()) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('Setting/Admin/abdm_report_doctor_settings', [
            'mapping_keys' => $this->mappingKeys,
            'selected' => $this->readExistingMappings(),
            'doctors' => $this->fetchDoctorOptions(),
        ]);
    }

    public function save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageSettings()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        if (! $this->db->tableExists('hospital_setting')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'hospital_setting table not found']);
        }

        foreach ($this->mappingKeys as $key => $label) {
            $doctorId = (int) ($this->request->getPost($key) ?? 0);
            if ($doctorId > 0) {
                $this->upsertSettingValue($key, (string) $doctorId);
            } else {
                $this->deleteSettingValue($key);
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'ABDM report doctor mapping saved.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * @return array<string,int>
     */
    private function readExistingMappings(): array
    {
        $output = [];
        foreach (array_keys($this->mappingKeys) as $key) {
            $output[$key] = 0;
        }

        if (! $this->db->tableExists('hospital_setting')) {
            return $output;
        }

        $rows = $this->db->table('hospital_setting')
            ->select('s_name, s_value')
            ->whereIn('s_name', array_keys($this->mappingKeys))
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $name = trim((string) ($row['s_name'] ?? ''));
            if ($name === '' || ! array_key_exists($name, $output)) {
                continue;
            }
            $output[$name] = (int) ($row['s_value'] ?? 0);
        }

        return $output;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchDoctorOptions(): array
    {
        if (! $this->db->tableExists('doctor_master')) {
            return [];
        }

        $fields = $this->db->getFieldNames('doctor_master') ?? [];
        $select = ['id'];
        foreach (['p_title', 'p_fname', 'p_lname', 'doctor_reg_no', 'registration_no', 'reg_no', 'doc_reg_no', 'nmc_reg_no', 'mci_reg_no'] as $f) {
            if (in_array($f, $fields, true)) {
                $select[] = $f;
            }
        }

        $builder = $this->db->table('doctor_master')->select(implode(',', array_unique($select)));
        if (in_array('is_active', $fields, true)) {
            $builder->where('is_active', 1);
        } elseif (in_array('status', $fields, true)) {
            $builder->where('status', 1);
        }

        if (in_array('p_fname', $fields, true)) {
            $builder->orderBy('p_fname', 'ASC');
        } else {
            $builder->orderBy('id', 'DESC');
        }

        $rows = $builder->get()->getResultArray();
        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $title = trim((string) ($row['p_title'] ?? ''));
            $first = trim((string) ($row['p_fname'] ?? ''));
            $last = trim((string) ($row['p_lname'] ?? ''));
            $name = trim($title . ' ' . $first . ' ' . $last);
            if ($name === '') {
                $name = 'Doctor #' . $id;
            }

            $regNo = '';
            foreach (['doctor_reg_no', 'registration_no', 'reg_no', 'doc_reg_no', 'nmc_reg_no', 'mci_reg_no'] as $rf) {
                $v = trim((string) ($row[$rf] ?? ''));
                if ($v !== '') {
                    $regNo = $v;
                    break;
                }
            }

            $label = $name;
            if ($regNo !== '') {
                $label .= ' (' . $regNo . ')';
            }

            $out[] = [
                'id' => $id,
                'name' => $name,
                'reg_no' => $regNo,
                'label' => $label,
            ];
        }

        return $out;
    }

    private function canManageSettings(): bool
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return false;
        }

        return true;
    }

    private function upsertSettingValue(string $name, string $value): bool
    {
        $existing = $this->db->table('hospital_setting')
            ->select('id')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        if ($existing) {
            return (bool) $this->db->table('hospital_setting')
                ->where('id', (int) $existing['id'])
                ->update(['s_value' => $value]);
        }

        return (bool) $this->db->table('hospital_setting')->insert([
            's_name' => $name,
            's_value' => $value,
        ]);
    }

    private function deleteSettingValue(string $name): bool
    {
        return (bool) $this->db->table('hospital_setting')->where('s_name', $name)->delete();
    }
}
