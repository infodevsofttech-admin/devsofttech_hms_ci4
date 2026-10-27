<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\Files\UploadedFile;

class HospitalProfile extends BaseController
{
    public function index()
    {
        if (! $this->canManageHospitalProfile()) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $logo = $this->readSettingValue('H_logo');
        $pharmacyLogo = $this->readSettingValue('H_Med_logo');
        $wallpaper = $this->readSettingValue('H_login_wallpaper');
        helper('common');

        return view('Setting/Admin/hospital_profile', [
            'hospital_name' => $this->readSettingValue('H_Name'),
            'hospital_address_1' => $this->readSettingValue('H_address_1'),
            'hospital_address_2' => $this->readSettingValue('H_address_2'),
            'hospital_phone' => $this->readSettingValue('H_phone_No'),
            'hospital_email' => $this->readSettingValue('H_Email'),
            'hospital_hfr_id' => $this->readSettingValue('H_HFR_ID'),
            'footer_version' => $this->readSettingValue('HMS_UPDATE_ID') !== ''
                ? $this->readSettingValue('HMS_UPDATE_ID')
                : hms_default_version_id(),
            'hospital_logo' => $logo,
            'hospital_logo_url' => $logo !== '' ? base_url('assets/images/' . rawurlencode($logo)) : '',
            'hospital_wallpaper' => $wallpaper,
            'hospital_wallpaper_url' => $wallpaper !== '' ? base_url('assets/images/' . rawurlencode($wallpaper)) : '',
            'pharmacy_name' => $this->readSettingValue('H_Med_Name'),
            'pharmacy_address' => $this->readSettingValue('H_Med_address_1'),
            'pharmacy_phone' => $this->readSettingValue('H_Med_phone_No'),
            'pharmacy_gst' => $this->readSettingValue('H_Med_GST'),
            'pharmacy_logo' => $pharmacyLogo,
            'pharmacy_logo_url' => $pharmacyLogo !== '' ? base_url('assets/images/' . rawurlencode($pharmacyLogo)) : '',
            'sidebar_auto_hide_seconds' => $this->readSettingValue('SIDEBAR_AUTO_HIDE_SECONDS') !== ''
                ? $this->readSettingValue('SIDEBAR_AUTO_HIDE_SECONDS')
                : '7',
            'allow_image_preupload_edit' => $this->readSettingValue('ALLOW_IMAGE_PREUPLOAD_EDIT') === '1' ? '1' : '0',
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
        $hfrId = strtoupper(trim((string) $this->request->getPost('hospital_hfr_id')));
        $footerVersion = trim((string) $this->request->getPost('footer_version'));

        $pharmacyName = trim((string) $this->request->getPost('pharmacy_name'));
        $pharmacyAddress = trim((string) $this->request->getPost('pharmacy_address'));
        $pharmacyPhone = trim((string) $this->request->getPost('pharmacy_phone'));
        $pharmacyGst = trim((string) $this->request->getPost('pharmacy_gst'));
        $sidebarAutoHideSecondsRaw = trim((string) $this->request->getPost('sidebar_auto_hide_seconds'));
        $sidebarAutoHideSeconds = $sidebarAutoHideSecondsRaw === '' ? 7 : (int) $sidebarAutoHideSecondsRaw;
        $allowImagePreuploadEdit = $this->request->getPost('allow_image_preupload_edit') === '1' ? '1' : '0';

        if ($name === '' || $address === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Hospital name and address are required.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($hfrId !== '' && preg_match('/^[A-Z0-9\/-]{6,40}$/', $hfrId) !== 1) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'HFR ID format is invalid. Use alphanumeric characters with / or -',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($sidebarAutoHideSeconds < 0 || $sidebarAutoHideSeconds > 60) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Sidebar auto hide seconds must be between 0 and 60.',
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
        if ($this->upsertSettingValue('H_HFR_ID', $hfrId)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('HMS_UPDATE_ID', $footerVersion)) {
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
        if ($this->upsertSettingValue('SIDEBAR_AUTO_HIDE_SECONDS', (string) $sidebarAutoHideSeconds)) {
            $savedCount++;
        }
        if ($this->upsertSettingValue('ALLOW_IMAGE_PREUPLOAD_EDIT', $allowImagePreuploadEdit)) {
            $savedCount++;
        }

        $logoResult = $this->processLogoUpload(
            $this->request->getFile('hospital_logo'),
            'hospital_logo',
            'H_logo',
            'Logo'
        );
        if (! $logoResult['ok']) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => $logoResult['error'],
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
        if ($logoResult['changed']) {
            $savedCount++;
        }

        $wallpaperResult = $this->processLogoUpload(
            $this->request->getFile('hospital_wallpaper'),
            'hospital_wallpaper',
            'H_login_wallpaper',
            'Login wallpaper'
        );
        if (! $wallpaperResult['ok']) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => $wallpaperResult['error'],
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
        if ($wallpaperResult['changed']) {
            $savedCount++;
        }

        $pharmacyLogoResult = $this->processLogoUpload(
            $this->request->getFile('pharmacy_logo'),
            'pharmacy_logo',
            'H_Med_logo',
            'Pharmacy logo'
        );
        if (! $pharmacyLogoResult['ok']) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => $pharmacyLogoResult['error'],
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
        if ($pharmacyLogoResult['changed']) {
            $savedCount++;
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => $savedCount > 0 ? 'Hospital profile saved successfully.' : 'No changes detected.',
            'logo_url' => ($logo = $this->readSettingValue('H_logo')) !== '' ? base_url('assets/images/' . rawurlencode($logo)) : '',
            'logo_name' => $this->readSettingValue('H_logo'),
            'wallpaper_url' => ($wp = $this->readSettingValue('H_login_wallpaper')) !== '' ? base_url('assets/images/' . rawurlencode($wp)) : '',
            'wallpaper_name' => $this->readSettingValue('H_login_wallpaper'),
            'pharmacy_logo_url' => ($logo = $this->readSettingValue('H_Med_logo')) !== '' ? base_url('assets/images/' . rawurlencode($logo)) : '',
            'pharmacy_logo_name' => $this->readSettingValue('H_Med_logo'),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function loginSettings()
    {
        if (! $this->canManageHospitalProfile()) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $wallpaper = $this->readSettingValue('H_login_wallpaper');
        $logo = $this->readSettingValue('H_logo');

        $presets = [
            [
                'file' => 'hospital_wallpaper_20261027_203745_bf800456.jpg',
                'title' => 'Client Deep Teal (Current)',
                'desc' => 'Dark teal background with smooth geometric accents',
            ],
            [
                'file' => 'hospital_wallpaper_soft_teal_leaves.jpg',
                'title' => 'Johri Soft Teal Botanical',
                'desc' => 'Ivory canvas with organic soft teal curved leaves (Brand Guide Option 02)',
            ],
            [
                'file' => 'hospital_wallpaper_modern_facade.jpg',
                'title' => 'Modern Hospital Facade',
                'desc' => 'High-end architectural medical tower with landscaped grounds',
            ],
        ];

        return view('Setting/Admin/login_settings', [
            'hospital_name' => $this->readSettingValue('H_Name'),
            'hospital_logo' => $logo,
            'hospital_logo_url' => $logo !== '' ? base_url('assets/images/' . rawurlencode($logo)) : '',
            'hospital_wallpaper' => $wallpaper,
            'hospital_wallpaper_url' => $wallpaper !== '' ? base_url('assets/images/' . rawurlencode($wallpaper)) : '',
            'login_panel_position' => $this->readSettingValue('LOGIN_PANEL_POSITION') ?: 'right',
            'login_hero_theme' => $this->readSettingValue('LOGIN_HERO_THEME') ?: 'light',
            'login_logo_style' => $this->readSettingValue('LOGIN_LOGO_STYLE') ?: '3d_box',
            'login_hero_overlay' => $this->readSettingValue('LOGIN_HERO_OVERLAY') ?: 'dark',
            'login_show_subtitle' => $this->readSettingValue('LOGIN_SHOW_SUBTITLE') !== '' ? $this->readSettingValue('LOGIN_SHOW_SUBTITLE') : '1',
            'login_show_contact_meta' => $this->readSettingValue('LOGIN_SHOW_CONTACT_META') !== '' ? $this->readSettingValue('LOGIN_SHOW_CONTACT_META') : '1',
            'login_show_pwa_card' => $this->readSettingValue('LOGIN_SHOW_PWA_CARD') !== '' ? $this->readSettingValue('LOGIN_SHOW_PWA_CARD') : '1',
            'presets' => $presets,
            'is_standalone' => ! $this->request->isAJAX(),
        ]);
    }

    public function saveLoginSettings()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageHospitalProfile()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $panelPos = strtolower(trim((string) $this->request->getPost('login_panel_position')));
        if (! in_array($panelPos, ['left', 'center', 'right'], true)) {
            $panelPos = 'right';
        }

        $heroTheme = strtolower(trim((string) $this->request->getPost('login_hero_theme')));
        if (! in_array($heroTheme, ['light', 'dark'], true)) {
            $heroTheme = 'light';
        }

        $logoStyle = strtolower(trim((string) $this->request->getPost('login_logo_style')));
        if (! in_array($logoStyle, ['3d_box', 'glass', 'transparent'], true)) {
            $logoStyle = '3d_box';
        }

        $heroOverlay = strtolower(trim((string) $this->request->getPost('login_hero_overlay')));
        if (! in_array($heroOverlay, ['dark', 'light', 'none'], true)) {
            $heroOverlay = 'dark';
        }

        $showSubtitle = $this->request->getPost('login_show_subtitle') === '1' ? '1' : '0';
        $showContactMeta = $this->request->getPost('login_show_contact_meta') === '1' ? '1' : '0';
        $showPwaCard = $this->request->getPost('login_show_pwa_card') === '1' ? '1' : '0';

        $this->upsertSettingValue('LOGIN_PANEL_POSITION', $panelPos);
        $this->upsertSettingValue('LOGIN_HERO_THEME', $heroTheme);
        $this->upsertSettingValue('LOGIN_LOGO_STYLE', $logoStyle);
        $this->upsertSettingValue('LOGIN_HERO_OVERLAY', $heroOverlay);
        $this->upsertSettingValue('LOGIN_SHOW_SUBTITLE', $showSubtitle);
        $this->upsertSettingValue('LOGIN_SHOW_CONTACT_META', $showContactMeta);
        $this->upsertSettingValue('LOGIN_SHOW_PWA_CARD', $showPwaCard);

        // Check if preset selected
        $presetWallpaper = trim((string) $this->request->getPost('preset_wallpaper'));
        if ($presetWallpaper !== '') {
            $presetFile = basename($presetWallpaper);
            if (is_file(FCPATH . 'assets/images/' . $presetFile)) {
                $this->upsertSettingValue('H_login_wallpaper', $presetFile);
            }
        }

        // Process new wallpaper upload if present
        $wpFile = $this->request->getFile('hospital_wallpaper');
        if ($wpFile && $wpFile->isValid() && ! $wpFile->hasMoved()) {
            $wallpaperResult = $this->processLogoUpload(
                $wpFile,
                'hospital_wallpaper',
                'H_login_wallpaper',
                'Login wallpaper'
            );
            if (! $wallpaperResult['ok']) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => $wallpaperResult['error'],
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }
        }

        $currentWallpaper = $this->readSettingValue('H_login_wallpaper');

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Login page settings saved successfully.',
            'wallpaper_url' => $currentWallpaper !== '' ? base_url('assets/images/' . rawurlencode($currentWallpaper)) : '',
            'wallpaper_name' => $currentWallpaper,
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
        if ($type === 'pharmacy') {
            $settingKey = 'H_Med_logo';
        } elseif ($type === 'wallpaper') {
            $settingKey = 'H_login_wallpaper';
        } else {
            $type = 'hospital';
            $settingKey = 'H_logo';
        }

        $oldFile = $this->readSettingValue($settingKey);
        $ok = $this->upsertSettingValue($settingKey, '');

        if ($oldFile !== '') {
            $oldPath = FCPATH . 'assets/images/' . basename($oldFile);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $label = $type === 'wallpaper' ? 'Login wallpaper' : ucfirst($type) . ' logo';

        return $this->response->setJSON([
            'update' => $ok ? 1 : 0,
            'error_text' => $ok ? $label . ' deleted.' : 'Unable to delete ' . strtolower($label) . '.',
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
        $wallpaper = $this->readSettingValue('H_login_wallpaper');
        $this->db->table('hospital_setting')->whereIn('s_name', [
            'H_Name',
            'H_address_1',
            'H_address_2',
            'H_phone_No',
            'H_Email',
            'H_HFR_ID',
            'HMS_UPDATE_ID',
            'H_logo',
            'H_login_wallpaper',
            'H_Med_Name',
            'H_Med_address_1',
            'H_Med_phone_No',
            'H_Med_GST',
            'H_Med_logo',
            'SIDEBAR_AUTO_HIDE_SECONDS',
            'ALLOW_IMAGE_PREUPLOAD_EDIT',
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

        if ($wallpaper !== '') {
            $wpPath = FCPATH . 'assets/images/' . basename($wallpaper);
            if (is_file($wpPath)) {
                @unlink($wpPath);
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

    private function processLogoUpload(?UploadedFile $file, string $filePrefix, string $settingKey, string $label): array
    {
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'changed' => false, 'error' => ''];
        }

        if (! $file->isValid()) {
            return [
                'ok' => false,
                'changed' => false,
                'error' => $label . ' upload failed: ' . $file->getErrorString(),
            ];
        }

        if ($file->hasMoved()) {
            return ['ok' => true, 'changed' => false, 'error' => ''];
        }

        $ext = strtolower((string) $file->getExtension());
        $allowed = ['png', 'jpg', 'jpeg', 'webp'];
        if (! in_array($ext, $allowed, true)) {
            return [
                'ok' => false,
                'changed' => false,
                'error' => $label . ' must be png, jpg, jpeg, or webp.',
            ];
        }

        $targetDir = FCPATH . 'assets/images';
        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0775, true) && ! is_dir($targetDir)) {
            return [
                'ok' => false,
                'changed' => false,
                'error' => 'Cannot create upload directory. Check server write permissions.',
            ];
        }

        if (! is_writable($targetDir)) {
            return [
                'ok' => false,
                'changed' => false,
                'error' => 'Upload directory is not writable. Set write permission on public/assets/images.',
            ];
        }

        $newName = $filePrefix . '_' . date('Ymd_His') . '_' . substr(md5((string) mt_rand()), 0, 8) . '.' . $ext;

        try {
            $file->move($targetDir, $newName, true);
        } catch (\Throwable $e) {
            log_message('error', '[HospitalProfile] ' . $label . ' move failed: ' . $e->getMessage());

            return [
                'ok' => false,
                'changed' => false,
                'error' => $label . ' upload failed. Check write permission on public/assets/images and PHP upload temp directory.',
            ];
        }

        $oldLogo = $this->readSettingValue($settingKey);
        if (! $this->upsertSettingValue($settingKey, $newName)) {
            return [
                'ok' => false,
                'changed' => false,
                'error' => 'Unable to save ' . strtolower($label) . ' setting.',
            ];
        }

        if ($oldLogo !== '' && $oldLogo !== $newName) {
            $oldPath = $targetDir . DIRECTORY_SEPARATOR . basename($oldLogo);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return ['ok' => true, 'changed' => true, 'error' => ''];
    }
}
