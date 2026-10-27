<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HospitalProfileWallpaperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('common');
        helper('setting');

        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS db_hospital_setting (id INTEGER PRIMARY KEY AUTOINCREMENT, s_name TEXT, s_value TEXT)");
        $db->query("CREATE TABLE IF NOT EXISTS db_settings (id INTEGER PRIMARY KEY AUTOINCREMENT, class TEXT, key TEXT, value TEXT, type TEXT, context TEXT, created_at TEXT, updated_at TEXT)");
    }

    public function testHospitalProfileViewContainsWallpaperControls(): void
    {
        $viewData = [
            'hospital_name' => 'City Hospital',
            'hospital_address_1' => '123 Medical Road',
            'hospital_address_2' => 'Civil Lines',
            'hospital_phone' => '9876543210',
            'hospital_email' => 'admin@cityhospital.in',
            'hospital_hfr_id' => 'IN0510000871',
            'footer_version' => '2026.01',
            'hospital_logo' => 'test_logo.png',
            'hospital_logo_url' => 'http://localhost:8080/assets/images/test_logo.png',
            'hospital_wallpaper' => 'test_wallpaper.jpg',
            'hospital_wallpaper_url' => 'http://localhost:8080/assets/images/test_wallpaper.jpg',
            'pharmacy_name' => 'City Hospital Pharmacy',
            'pharmacy_address' => 'Ground Floor',
            'pharmacy_phone' => '9876543211',
            'pharmacy_gst' => '05ABIPR7922F1ZJ',
            'pharmacy_logo' => '',
            'pharmacy_logo_url' => '',
            'sidebar_auto_hide_seconds' => '7',
            'allow_image_preupload_edit' => '0',
        ];

        $html = view('Setting/Admin/hospital_profile', $viewData);

        $this->assertStringContainsString('id="hospital_wallpaper"', $html);
        $this->assertStringContainsString('id="hospital_wallpaper_preview"', $html);
        $this->assertStringContainsString('id="btn_hospital_wallpaper_delete"', $html);
        $this->assertStringContainsString('test_wallpaper.jpg', $html);
        $this->assertStringContainsString('renderWallpaper', $html);
    }

    public function testLoginSettingsViewRendersAllConfigurationControls(): void
    {
        $viewData = [
            'login_panel_position' => 'left',
            'login_hero_theme' => 'light',
            'login_logo_style' => '3d_box',
            'login_hero_overlay' => 'dark',
            'login_show_subtitle' => '1',
            'login_show_contact_meta' => '1',
            'login_show_pwa_card' => '1',
            'hospital_wallpaper' => 'hospital_wallpaper_soft_teal_leaves.jpg',
            'hospital_wallpaper_url' => 'http://localhost:8080/assets/images/hospital_wallpaper_soft_teal_leaves.jpg',
            'presets' => [
                [
                    'file' => 'hospital_wallpaper_20261027_203745_bf800456.jpg',
                    'title' => 'Client Deep Teal',
                    'subtitle' => 'Original curated rich hospital theme',
                    'tag' => 'Active Default',
                    'url' => 'http://localhost:8080/assets/images/hospital_wallpaper_20261027_203745_bf800456.jpg',
                ],
            ],
            'hospital_logo' => 'logo.png',
            'hospital_logo_url' => 'http://localhost:8080/assets/images/logo.png',
            'hospital_name' => 'Johri Hospital',
        ];

        $html = view('Setting/Admin/login_settings', $viewData);

        // Layout position controls
        $this->assertStringContainsString('name="login_panel_position"', $html);
        $this->assertStringContainsString('value="left"', $html);
        $this->assertStringContainsString('value="center"', $html);
        $this->assertStringContainsString('value="right"', $html);

        // Logo style controls
        $this->assertStringContainsString('name="login_logo_style"', $html);
        $this->assertStringContainsString('value="3d_box"', $html);
        $this->assertStringContainsString('value="glass"', $html);
        $this->assertStringContainsString('value="transparent"', $html);

        // Typography theme & overlay
        $this->assertStringContainsString('name="login_hero_theme"', $html);
        $this->assertStringContainsString('name="login_hero_overlay"', $html);

        // Feature switches
        $this->assertStringContainsString('name="login_show_subtitle"', $html);
        $this->assertStringContainsString('name="login_show_contact_meta"', $html);
        $this->assertStringContainsString('name="login_show_pwa_card"', $html);

        // Presets and save button
        $this->assertStringContainsString('Client Deep Teal', $html);
        $this->assertStringContainsString('id="btn_save_login_settings"', $html);
    }
}

