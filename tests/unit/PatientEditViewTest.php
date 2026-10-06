<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

final class PatientEditViewTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['common', 'form', 'url', 'age']);
    }

    private function makePatientData(array $overrides = []): array
    {
        $patient = (object) array_merge([
            'id' => 16,
            'p_fname' => 'John Doe',
            'p_code' => 'P16',
            'p_edit' => 1,
            'title' => 'Mr.',
            'gender' => 1,
            'udai_last4' => '1234',
            'abha_id' => '',
            'abha_no' => '',
            'abha' => '',
            'abha_address' => '',
            'abha_verified_status' => '',
            'abha_verification_type' => '',
            'abha_kyc_verified' => 0,
            'abha_mobile_verified' => 0,
            'abdm_linked_at' => '',
            'abha_profile_photo_base64' => '',
            'mphone1' => '9876543210',
            'estimate_dob' => 0,
            'age' => 30,
            'age_in_month' => 0,
            'dob' => '1995-01-01',
            'p_relative' => 'S/o',
            'p_rname' => 'Father Doe',
            'email1' => 'john@example.com',
            'blood_group' => 'O+',
            'referby' => '',
            'add1' => '123 Test St',
            'city' => 'City',
            'zip' => '123456',
            'district' => 'District',
            'state' => 'State',
        ], $overrides);

        return [
            'data' => [$patient],
            'blood_group' => [(object) ['blood_group' => 'O+'], (object) ['blood_group' => 'A+']],
            'refer_master' => [],
            'tag_master' => [],
            'patient_tag_list' => [],
        ];
    }

    public function testViewRendersWithoutAbhaPhotoAvailableVariableInViewData(): void
    {
        $viewData = $this->makePatientData();
        // Do not set $viewData['abhaPhotoAvailable'] explicitly to test fallback in Person_Edit_V
        $html = view('billing/Person_Edit_V', $viewData);

        $this->assertStringContainsString('ABHA Photo:', $html);
        $this->assertStringContainsString('NOT AVAILABLE', $html);
        $this->assertStringContainsString('Person Profile - John Doe P16', $html);
    }

    public function testViewRendersWithAbhaPhotoAvailableWhenBase64Present(): void
    {
        $viewData = $this->makePatientData([
            'abha_profile_photo_base64' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
        ]);
        $viewData['abhaPhotoAvailable'] = true;

        $html = view('billing/Person_Edit_V', $viewData);

        $this->assertStringContainsString('ABHA Photo:', $html);
        $this->assertStringContainsString('AVAILABLE (BASE64 STORED)', $html);
    }

    public function testViewRendersWithAbhaVerifiedLockedFields(): void
    {
        $viewData = $this->makePatientData([
            'abha_id' => '12345678901234',
            'abha_verified_status' => 'VERIFIED',
            'abha_verification_type' => 'VERIFIED',
            'abha_kyc_verified' => 1,
            'abha_mobile_verified' => 1,
        ]);
        $viewData['abhaPhotoAvailable'] = false;

        $html = view('billing/Person_Edit_V', $viewData);

        $this->assertStringContainsString('ABHA verified: name, gender, ABHA ID, and date of birth are locked.', $html);
        $this->assertStringContainsString('KYC Verified:</strong> YES', $html);
        $this->assertStringContainsString('Mobile Verified:</strong> YES', $html);
    }

    public function testProfileViewRendersWithAbhaPhoto(): void
    {
        $viewData = $this->makePatientData([
            'insurance_id' => 0,
            'insurance_card_id' => 0,
            'log' => '',
        ]);
        $viewData['profile_file_path'] = '/assets/images/no_image.svg';
        $viewData['case_master_opd'] = [];
        $viewData['case_master_ipd'] = [];
        $viewData['data_insurance_card'] = [];
        $viewData['opd_List'] = [];
        $viewData['invoice_list'] = [];
        $viewData['abhaPhotoAvailable'] = true;

        $html = view('billing/Person_profile_V', $viewData);

        $this->assertStringContainsString('Profile', $html);
        $this->assertStringContainsString('AVAILABLE (BASE64 STORED)', $html);
    }
}
