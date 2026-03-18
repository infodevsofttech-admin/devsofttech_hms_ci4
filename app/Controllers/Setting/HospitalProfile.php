<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;

class HospitalProfile extends BaseController
{
    public function index()
    {
        if (! $this->canManageHospitalProfile()) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $logo = $this->readSettingValue('H_logo');
        $pharmacyLogo = $this->readSettingValue('H_Med_logo');

        return view('Setting/Admin/hospital_profile', [
            'hospital_name' => $this->readSettingValue('H_Name'),
            'hospital_address_1' => $this->readSettingValue('H_address_1'),
            'hospital_address_2' => $this->readSettingValue('H_address_2'),
            'hospital_phone' => $this->readSettingValue('H_phone_No'),
            'hospital_email' => $this->readSettingValue('H_Email'),
            'hospital_logo' => $logo,
            'hospital_logo_url' => $logo !== '' ? base_url('assets/images/' . rawurlencode($logo)) : '',
            'pharmacy_name' => $this->readSettingValue('H_Med_Name'),
            'pharmacy_address' => $this->readSettingValue('H_Med_address_1'),
            'pharmacy_phone' => $this->readSettingValue('H_Med_phone_No'),
            'pharmacy_gst' => $this->readSettingValue('H_Med_GST'),
            'pharmacy_logo' => $pharmacyLogo,
            'pharmacy_logo_url' => $pharmacyLogo !== '' ? base_url('assets/images/' . rawurlencode($pharmacyLogo)) : '',
        ]);
    }

    public function save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageHospitalProfile()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        if (! $this->db->tableExists('hospital_setting')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'hospital_setting table not found']);
        }

        $name = trim((string) $this->request->getPost('hospital_name'));
        $address = trim((string) $this->request->getPost('hospital_address_1'));
        $address2 = trim((string) $this->request->getPost('hospital_address_2'));
        $phone = trim((string) $this->request->getPost('hospital_phone'));
        $email = trim((string) $this->request->getPost('hospital_email'));

        $pharmacyName = trim((string) $this->request->getPost('pharmacy_name'));
        $pharmacyAddress = trim((string) $this->request->getPost('pharmacy_address'));
        $pharmacyPhone = trim((string) $this->request->getPost('pharmacy_phone'));
        $pharmacyGst = trim((string) $this->request->getPost('pharmacy_gst'));

        if ($name === '' || $address === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Hospital name and address are required.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $savedCount = 0;
        if ($this->upsertSettingValue('H_Name', $name)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('H_address_1', $address)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('H_address_2', $address2)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('H_phone_No', $phone)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('H_Email', $email)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('H_Med_Name', $pharmacyName)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('H_Med_address_1', $pharmacyAddress)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('H_Med_phone_No', $pharmacyPhone)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('H_Med_GST', $pharmacyGst)) {
            $savedCount++;
        }

        $logoFile = $this->request->getFile('hospital_logo');
        if ($logoFile && $logoFile->isValid() && ! $logoFile->hasMoved()) {
            $ext = strtolower((string) $logoFile->getExtension());
            $allowed = ['png', 'jpg', 'jpeg', 'webp'];
            if (! in_array($ext, $allowed, true)) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Logo must be png, jpg, jpeg, or webp.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $targetDir = FCPATH . 'assets/images';
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }
            if (! is_dir($targetDir)) {
                return $this->response->setJSON([
                    'update'   => 0,
                    'error_text' => 'Cannot create upload directory. Check server write permissions.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $newName = 'hospital_logo_' . date('Ymd_His') . '_' . substr(md5((string) mt_rand()), 0, 8) . '.' . $ext;
            $logoFile->move($targetDir, $newName, true);

            $oldLogo = $this->readSettingValue('H_logo');
            if ($this->upsertSettingValue('H_logo', $newName)) {
                $savedCount++;
            }

            if ($oldLogo !== '' && $oldLogo !== $newName) {
                $oldPath = $targetDir . DIRECTORY_SEPARATOR . basename($oldLogo);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        $pharmacyLogoFile = $this->request->getFile('pharmacy_logo');
        if ($pharmacyLogoFile && $pharmacyLogoFile->isValid() && ! $pharmacyLogoFile->hasMoved()) {
            $ext = strtolower((string) $pharmacyLogoFile->getExtension());
            $allowed = ['png', 'jpg', 'jpeg', 'webp'];
            if (! in_array($ext, $allowed, true)) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Pharmacy logo must be png, jpg, jpeg, or webp.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $targetDir = FCPATH . 'assets/images';
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }
            if (! is_dir($targetDir)) {
                return $this->response->setJSON([
                    'update'   => 0,
                    'error_text' => 'Cannot create upload directory. Check server write permissions.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $newName = 'pharmacy_logo_' . date('Ymd_His') . '_' . substr(md5((string) mt_rand()), 0, 8) . '.' . $ext;
            $pharmacyLogoFile->move($targetDir, $newName, true);

            $oldLogo = $this->readSettingValue('H_Med_logo');
            if ($this->upsertSettingValue('H_Med_logo', $newName)) {
                $savedCount++;
            }

            if ($oldLogo !== '' && $oldLogo !== $newName) {
                $oldPath = $targetDir . DIRECTORY_SEPARATOR . basename($oldLogo);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => $savedCount > 0 ? 'Hospital profile saved successfully.' : 'No changes detected.',
            'logo_url' => ($logo = $this->readSettingValue('H_logo')) !== '' ? base_url('assets/images/' . rawurlencode($logo)) : '',
            'logo_name' => $this->readSettingValue('H_logo'),
            'pharmacy_logo_url' => ($logo = $this->readSettingValue('H_Med_logo')) !== '' ? base_url('assets/images/' . rawurlencode($logo)) : '',
            'pharmacy_logo_name' => $this->readSettingValue('H_Med_logo'),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteLogo()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageHospitalProfile()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $type = strtolower(trim((string) $this->request->getPost('type')));
        if ($type !== 'pharmacy') {
            $type = 'hospital';
        }

        $settingKey = $type === 'pharmacy' ? 'H_Med_logo' : 'H_logo';
        $oldLogo = $this->readSettingValue($settingKey);
        $ok = $this->upsertSettingValue($settingKey, '');

        if ($oldLogo !== '') {
            $oldPath = FCPATH . 'assets/images/' . basename($oldLogo);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return $this->response->setJSON([
            'update' => $ok ? 1 : 0,
            'error_text' => $ok ? ucfirst($type) . ' logo deleted.' : 'Unable to delete logo.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function reset()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageHospitalProfile()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $logo = $this->readSettingValue('H_logo');
        $pharmacyLogo = $this->readSettingValue('H_Med_logo');
        $this->db->table('hospital_setting')->whereIn('s_name', [
            'H_Name',
            'H_address_1',
            'H_address_2',
            'H_phone_No',
            'H_Email',
            'H_logo',
            'H_Med_Name',
            'H_Med_address_1',
            'H_Med_phone_No',
            'H_Med_GST',
            'H_Med_logo',
        ])->delete();

        if ($logo !== '') {
            $logoPath = FCPATH . 'assets/images/' . basename($logo);
            if (is_file($logoPath)) {
                @unlink($logoPath);
            }
        }

        if ($pharmacyLogo !== '') {
            $logoPath = FCPATH . 'assets/images/' . basename($pharmacyLogo);
            if (is_file($logoPath)) {
                @unlink($logoPath);
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Hospital profile settings reset.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function canManageHospitalProfile(): bool
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return false;
        }

        return true;
    }

    private function readSettingValue(string $name): string
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    private function upsertSettingValue(string $name, string $value): bool
    {
        $existing = $this->db->table('hospital_setting')
            ->select('id, s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        $oldValue = trim((string) ($existing['s_value'] ?? ''));

        if ($existing) {
            $ok = $this->db->table('hospital_setting')
                ->where('id', (int) ($existing['id'] ?? 0))
                ->update(['s_value' => $value]);
        } else {
            $ok = $this->db->table('hospital_setting')->insert([
                's_name' => $name,
                's_value' => $value,
            ]);
        }

        if ($ok) {
            $this->auditClinicalUpdate('hospital_setting', $name, $name, $oldValue, $value);
        }

        return (bool) $ok;
    }
}
