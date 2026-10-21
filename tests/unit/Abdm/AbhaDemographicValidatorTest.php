<?php

namespace Tests\Unit\Abdm;

use App\Libraries\Abdm\AbhaDemographicValidator;
use CodeIgniter\Test\CIUnitTestCase;

class AbhaDemographicValidatorTest extends CIUnitTestCase
{
    public function testGenderValidationMismatch(): void
    {
        // Sunita Bisht (Female = 2) vs Dhairya Singh Bisht (Male = 'M')
        $patient = [
            'p_fname' => 'SUNITA BISHT',
            'gender'  => 2,
            'age'     => 28,
        ];
        $abhaProfile = [
            'name'   => 'DHAIRYA SINGH BISHT',
            'gender' => 'M',
            'dob'    => '2020-07-05',
        ];

        $error = AbhaDemographicValidator::validateDemographicsMatch($patient, $abhaProfile);
        $this->assertNotNull($error);
        $this->assertStringContainsString('Demographic Mismatch: Patient gender (Female) does not match ABHA profile gender (Male)', $error);
    }

    public function testAgeValidationMismatch(): void
    {
        // Matching gender but 22-year age gap (adult vs child)
        $patient = [
            'p_fname' => 'DHAIRYA SINGH BISHT',
            'gender'  => 1,
            'age'     => 28, // adult YOB ~ 1998
        ];
        $abhaProfile = [
            'name'          => 'DHAIRYA SINGH BISHT',
            'gender'        => 'M',
            'year_of_birth' => '2020',
        ];

        $error = AbhaDemographicValidator::validateDemographicsMatch($patient, $abhaProfile);
        $this->assertNotNull($error);
        $this->assertStringContainsString('birth year', $error);
        $this->assertStringContainsString('does not match ABHA profile birth year', $error);
    }

    public function testNameSimilarityMismatch(): void
    {
        // Matching gender and age, but completely different names
        $patient = [
            'p_fname' => 'RAMESH KUMAR',
            'gender'  => 1,
            'dob'     => '2020-07-05',
        ];
        $abhaProfile = [
            'name'          => 'DHAIRYA SINGH BISHT',
            'gender'        => 'M',
            'year_of_birth' => '2020',
        ];

        $error = AbhaDemographicValidator::validateDemographicsMatch($patient, $abhaProfile);
        $this->assertNotNull($error);
        $this->assertStringContainsString("Patient name 'RAMESH KUMAR' does not match ABHA profile name 'DHAIRYA SINGH BISHT'", $error);
    }

    public function testValidDemographicsMatch(): void
    {
        // Dhairya Singh Bisht matching on Name, Age, and Gender
        $patient = [
            'p_fname' => 'DHAIRYA SINGH BISHT',
            'gender'  => 1,
            'dob'     => '2020-07-05',
            'mphone1' => '9720958717',
        ];
        $abhaProfile = [
            'name'          => 'Dhairya Singh Bisht',
            'gender'        => 'M',
            'year_of_birth' => '2020',
            'mobile'        => '9720958717',
        ];

        $error = AbhaDemographicValidator::validateDemographicsMatch($patient, $abhaProfile, true);
        $this->assertNull($error);
    }

    public function testExtractYearOfBirth(): void
    {
        $this->assertSame(2020, AbhaDemographicValidator::extractYearOfBirth('2020-07-05'));
        $this->assertSame(2020, AbhaDemographicValidator::extractYearOfBirth('05-07-2020'));
        $this->assertSame(2020, AbhaDemographicValidator::extractYearOfBirth('2020'));
        $currentYear = (int) date('Y');
        $this->assertSame($currentYear - 28, AbhaDemographicValidator::extractYearOfBirth('', '28'));
    }

    public function testCalculateNameSimilarity(): void
    {
        // Perfect match ignoring honorifics
        $score = AbhaDemographicValidator::calculateNameSimilarity('MR. DHAIRYA SINGH BISHT', 'Dhairya Singh Bisht');
        $this->assertGreaterThanOrEqual(0.8, $score);

        // Completely different names
        $scoreDiff = AbhaDemographicValidator::calculateNameSimilarity('SUNITA BISHT', 'DHAIRYA SINGH');
        $this->assertLessThan(0.35, $scoreDiff);
    }
}
