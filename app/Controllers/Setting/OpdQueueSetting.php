<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;

class OpdQueueSetting extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function index()
    {
        $tickerText = $this->getSetting('TV_QUEUE_FOOTER_TICKER', 'Welcome to E-Atria Multispeciality Hospital. Please observe your token number on the screen. Voice announcements will call your turn. For assistance, contact the help desk. • Emergency Helpline: +91-9876543210 • 24/7 Pharmacy & Ambulance Services Available');
        
        $leftAdText = $this->getSetting('TV_QUEUE_LEFT_AD_TEXT', '24x7 Emergency & Trauma Center • ICU & Ventilator Available');
        $leftAdImage = $this->getSetting('TV_QUEUE_LEFT_AD_IMAGE', '');
        $leftAdEnabled = $this->getSetting('TV_QUEUE_LEFT_AD_ENABLED', '1');

        $adsJson = $this->getSetting('TV_QUEUE_ADS_JSON', '');
        $customAds = [];
        if ($adsJson !== '') {
            $customAds = json_decode($adsJson, true) ?: [];
        }

        return view('Setting/Admin/opd_queue_setting', [
            'footer_ticker' => $tickerText,
            'left_ad_text' => $leftAdText,
            'left_ad_image' => $leftAdImage,
            'left_ad_enabled' => $leftAdEnabled,
            'custom_ads' => $customAds
        ]);
    }

    public function save()
    {
        $post = $this->request->getPost();
        $ticker = trim((string) ($post['footer_ticker'] ?? ''));
        $leftAdText = trim((string) ($post['left_ad_text'] ?? ''));
        $leftAdEnabled = isset($post['left_ad_enabled']) ? '1' : '0';

        if ($ticker !== '') {
            $this->setSetting('TV_QUEUE_FOOTER_TICKER', $ticker);
        }
        $this->setSetting('TV_QUEUE_LEFT_AD_TEXT', $leftAdText);
        $this->setSetting('TV_QUEUE_LEFT_AD_ENABLED', $leftAdEnabled);

        // Handle Left Bottom Ad Image Upload
        $leftAdFile = $this->request->getFile('left_ad_image_file');
        if ($leftAdFile && $leftAdFile->isValid() && !$leftAdFile->hasMoved()) {
            $ext = strtolower($leftAdFile->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $targetDir = FCPATH . 'assets/images/tv_ads';
                if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);

                $newName = 'left_ad_' . date('Ymd_His') . '.' . $ext;
                $leftAdFile->move($targetDir, $newName);
                $this->setSetting('TV_QUEUE_LEFT_AD_IMAGE', 'assets/images/tv_ads/' . $newName);
            }
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'OPD Queue TV Display settings updated successfully!'
        ]);
    }

    public function uploadBanner()
    {
        $post = $this->request->getPost();
        $title = trim((string) ($post['title'] ?? ''));
        $tagline = trim((string) ($post['tagline'] ?? ''));
        $description = trim((string) ($post['description'] ?? ''));
        $enabled = isset($post['enabled']) ? 1 : 0;
        $slideType = trim((string) ($post['slide_type'] ?? 'hospital_ad'));

        $file = $this->request->getFile('banner_image');
        $imageUrl = '';

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $targetDir = FCPATH . 'assets/images/tv_ads';
                if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);

                $newName = 'ad_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $file->move($targetDir, $newName);
                $imageUrl = 'assets/images/tv_ads/' . $newName;
            }
        }

        if ($imageUrl === '' && !empty($post['existing_image_url'])) {
            $imageUrl = $post['existing_image_url'];
        }

        if ($title === '') {
            return $this->response->setJSON(['status' => 0, 'message' => 'Banner Title is required']);
        }

        $adsJson = $this->getSetting('TV_QUEUE_ADS_JSON', '');
        $customAds = json_decode($adsJson, true) ?: [];

        $newSlide = [
            'id' => time() . mt_rand(10, 99),
            'type' => $slideType,
            'title' => $title,
            'tagline' => $tagline,
            'description' => $description,
            'image_url' => $imageUrl !== '' ? base_url($imageUrl) : base_url('assets/img/slides-1.jpg'),
            'relative_image_url' => $imageUrl,
            'enabled' => $enabled
        ];

        $customAds[] = $newSlide;
        $this->setSetting('TV_QUEUE_ADS_JSON', json_encode($customAds, JSON_UNESCAPED_SLASHES));

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Right Side Advertisement Banner added successfully!'
        ]);
    }

    public function deleteBanner()
    {
        $post = $this->request->getPost();
        $bannerId = (int) ($post['banner_id'] ?? 0);

        $adsJson = $this->getSetting('TV_QUEUE_ADS_JSON', '');
        $customAds = json_decode($adsJson, true) ?: [];
        $updatedAds = [];

        foreach ($customAds as $ad) {
            if ((int) ($ad['id'] ?? 0) !== $bannerId) {
                $updatedAds[] = $ad;
            }
        }

        $this->setSetting('TV_QUEUE_ADS_JSON', json_encode($updatedAds, JSON_UNESCAPED_SLASHES));

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Advertisement slide removed successfully!'
        ]);
    }

    private function getSetting(string $name, string $default = ''): string
    {
        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? $default));
    }

    private function setSetting(string $name, string $value): bool
    {
        $existing = $this->db->table('hospital_setting')
            ->select('id')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        if ($existing) {
            return $this->db->table('hospital_setting')
                ->where('id', (int) $existing['id'])
                ->update(['s_value' => $value]);
        } else {
            return $this->db->table('hospital_setting')->insert([
                's_name' => $name,
                's_value' => $value
            ]);
        }
    }
}
