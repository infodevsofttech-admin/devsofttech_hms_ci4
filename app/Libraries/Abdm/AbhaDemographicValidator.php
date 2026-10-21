<?php

namespace App\Libraries\Abdm;

use Config\Database;

/**
 * AbhaDemographicValidator
 *
 * Enforces strict integrity rules for ABHA linking in HMS:
 * 1. Global Uniqueness: Neither ABHA ID (14 digits) nor ABHA Address (@sbx/@abdm)
 *    can be linked to multiple patient records.
 * 2. Demographic Verification: ABHA identity can only be linked to a patient
 *    when Name, Age/Year-of-Birth, and Gender match within acceptable clinical thresholds.
 */
class AbhaDemographicValidator
{
    /**
     * Check if an ABHA ID or ABHA Address is already linked to another patient in patient_master.
     *
     * @param string   $abhaId           14-digit ABHA Number (formatted or raw)
     * @param string   $abhaAddress      ABHA Address (e.g. user@sbx)
     * @param int|null $excludePatientId Patient ID to exclude from conflict check
     * @return array{conflict_patient_id: int, p_code: string, p_name: string, error_text: string}|null
     */
    public static function checkUniqueness(
        string $abhaId = '',
        string $abhaAddress = '',
        ?int $excludePatientId = null,
        $db = null
    ): ?array {
        $db = $db ?? Database::connect();
        if (! $db->tableExists('patient_master')) {
            return null;
        }

        $fields = $db->getFieldNames('patient_master') ?? [];
        $cleanAbhaId = preg_replace('/\D/', '', $abhaId);
        $formattedAbhaId = (strlen($cleanAbhaId) === 14)
            ? substr($cleanAbhaId, 0, 2) . '-' . substr($cleanAbhaId, 2, 4) . '-' . substr($cleanAbhaId, 6, 4) . '-' . substr($cleanAbhaId, 10, 4)
            : '';
        $cleanAbhaAddress = trim($abhaAddress);

        $checkIdVals = array_values(array_filter([$cleanAbhaId, $formattedAbhaId, $abhaId]));
        $hasIdCheck = ! empty($checkIdVals);
        $hasAddressCheck = ($cleanAbhaAddress !== '' && str_contains($cleanAbhaAddress, '@'));

        if (! $hasIdCheck && ! $hasAddressCheck) {
            return null;
        }

        $builder = $db->table('patient_master')
            ->select('id, p_code, p_fname, p_lname, gender, dob, age, mphone1, abha_id, abha_address');

        $matchedAny = false;
        $builder->groupStart();

        // 1. Check ABHA ID columns
        if ($hasIdCheck) {
            foreach (['abha_id', 'abha_no', 'abha'] as $candCol) {
                if (in_array($candCol, $fields, true)) {
                    if (! $matchedAny) {
                        $builder->whereIn($candCol, $checkIdVals);
                        $matchedAny = true;
                    } else {
                        $builder->orWhereIn($candCol, $checkIdVals);
                    }
                }
            }
        }

        // 2. Check ABHA Address column
        if ($hasAddressCheck && in_array('abha_address', $fields, true)) {
            if (! $matchedAny) {
                $builder->where('abha_address', $cleanAbhaAddress);
                $matchedAny = true;
            } else {
                $builder->orWhere('abha_address', $cleanAbhaAddress);
            }
        }

        // Also check if abha_id column accidentally holds the abha_address string
        if ($hasAddressCheck && in_array('abha_id', $fields, true)) {
            $builder->orWhere('abha_id', $cleanAbhaAddress);
        }

        $builder->groupEnd();

        if (! $matchedAny) {
            return null;
        }

        if ($excludePatientId !== null && $excludePatientId > 0) {
            $builder->where('id !=', $excludePatientId);
        }

        $conflict = $builder->orderBy('id', 'DESC')->get(1)->getRowArray();
        if (! $conflict) {
            return null;
        }

        $conflictId   = (int) ($conflict['id'] ?? 0);
        $conflictCode = (string) ($conflict['p_code'] ?? ('P-' . $conflictId));
        $conflictName = trim(($conflict['p_fname'] ?? '') . ' ' . ($conflict['p_lname'] ?? ''));
        $matchedValue = $hasAddressCheck ? $cleanAbhaAddress : $cleanAbhaId;

        return [
            'conflict_patient_id' => $conflictId,
            'p_code'              => $conflictCode,
            'p_name'              => $conflictName,
            'error_text'          => "This ABHA ({$matchedValue}) is already linked to patient {$conflictCode} ({$conflictName}). ABHA ID and Address must be unique to one person.",
        ];
    }

    /**
     * Validate that patient demographics in HMS match the ABHA profile demographics.
     *
     * @param array<string, mixed> $patientRow  HMS patient record (from patient_master or input)
     * @param array<string, mixed> $abhaProfile ABHA profile data (from verification/gateway)
     * @param bool                 $strict      If true, requires phone number match if available
     * @return string|null                      Null if matching passes; error string on mismatch.
     */
    public static function validateDemographicsMatch(array $patientRow, array $abhaProfile, bool $strict = false): ?string
    {
        // 1. Gender Validation
        $patientGender = self::normalizeGender($patientRow['gender'] ?? ($patientRow['optionsRadios_gender'] ?? ''));
        $abhaGender    = self::normalizeGender($abhaProfile['gender'] ?? '');

        if ($patientGender !== '' && $abhaGender !== '' && $patientGender !== $abhaGender) {
            $pLabel = self::genderLabel($patientGender);
            $aLabel = self::genderLabel($abhaGender);
            return "Demographic Mismatch: Patient gender ({$pLabel}) does not match ABHA profile gender ({$aLabel}). ABHA cannot be linked to a different person.";
        }

        // 2. Age / Date of Birth / Year of Birth Validation
        $patientYob = self::extractYearOfBirth(
            $patientRow['dob'] ?? ($patientRow['datepicker_dob'] ?? ($patientRow['yob'] ?? '')),
            $patientRow['age'] ?? ($patientRow['input_age_year'] ?? '')
        );
        $abhaYob = self::extractYearOfBirth(
            $abhaProfile['year_of_birth'] ?? ($abhaProfile['yob'] ?? ($abhaProfile['date_of_birth'] ?? ($abhaProfile['dob'] ?? ''))),
            $abhaProfile['age'] ?? ''
        );

        if ($patientYob !== null && $abhaYob !== null) {
            $diffYears = abs($patientYob - $abhaYob);
            // Allow up to 2 years tolerance for DOB vs estimated age rounding
            if ($diffYears > 2) {
                return "Demographic Mismatch: Patient birth year ({$patientYob}) does not match ABHA profile birth year ({$abhaYob}, difference: {$diffYears} years). ABHA cannot be linked to a different person.";
            }
        }

        // 3. Name Similarity Validation
        $patientName = trim((string) ($patientRow['p_fname'] ?? ($patientRow['input_name'] ?? '')));
        if (! empty($patientRow['p_lname']) && $patientRow['p_lname'] !== '0') {
            $patientName .= ' ' . trim((string) $patientRow['p_lname']);
        }
        $abhaName = trim((string) ($abhaProfile['full_name'] ?? ($abhaProfile['name'] ?? '')));

        if ($patientName !== '' && $abhaName !== '') {
            $nameSimilarity = self::calculateNameSimilarity($patientName, $abhaName);
            // If similarity is below 0.35 and no significant tokens match, reject
            if ($nameSimilarity < 0.35) {
                return "Demographic Mismatch: Patient name '{$patientName}' does not match ABHA profile name '{$abhaName}'.";
            }
        }

        // 4. Strict Phone Match (if requested and both phones provided)
        if ($strict) {
            $patientPhone = substr(preg_replace('/\D/', '', (string) ($patientRow['mphone1'] ?? ($patientRow['input_mphone1'] ?? ''))), -10);
            $abhaPhone    = substr(preg_replace('/\D/', '', (string) ($abhaProfile['mobile'] ?? ($abhaProfile['phone'] ?? ''))), -10);
            if (strlen($patientPhone) === 10 && strlen($abhaPhone) === 10 && $patientPhone !== $abhaPhone) {
                return "Demographic Mismatch: Patient mobile ({$patientPhone}) does not match ABHA mobile ({$abhaPhone}).";
            }
        }

        return null;
    }

    /**
     * Normalize gender string or numeric code to 'M', 'F', or 'O'.
     */
    public static function normalizeGender(mixed $gender): string
    {
        if ($gender === null || $gender === '') {
            return '';
        }
        $g = strtoupper(trim((string) $gender));
        if ($g === '1' || $g === 'M' || $g === 'MALE' || $g === 'BOY') {
            return 'M';
        }
        if ($g === '2' || $g === 'F' || $g === 'FEMALE' || $g === 'GIRL') {
            return 'F';
        }
        if ($g === '3' || $g === 'O' || $g === 'OTHER' || $g === 'TRANSGENDER') {
            return 'O';
        }
        return '';
    }

    public static function genderLabel(string $code): string
    {
        return match ($code) {
            'M' => 'Male',
            'F' => 'Female',
            'O' => 'Other',
            default => 'Unknown',
        };
    }

    /**
     * Extract or calculate 4-digit Year of Birth.
     */
    public static function extractYearOfBirth(mixed $dobString = '', mixed $ageString = ''): ?int
    {
        $currentYear = (int) date('Y');

        if (! empty($dobString)) {
            $dob = trim((string) $dobString);
            if ($dob !== '0000-00-00' && ! str_starts_with($dob, '0000')) {
                // Check standalone 4-digit year (e.g. "2020")
                if (preg_match('/^\d{4}$/', $dob, $m)) {
                    $y = (int) $m[0];
                    if ($y >= 1900 && $y <= $currentYear) {
                        return $y;
                    }
                }
                // Check YYYY-MM-DD
                if (preg_match('/^(\d{4})[-.\/]/', $dob, $m)) {
                    $y = (int) $m[1];
                    if ($y >= 1900 && $y <= $currentYear) {
                        return $y;
                    }
                }
                // Check DD-MM-YYYY
                if (preg_match('/[-.\/](\d{4})$/', $dob, $m)) {
                    $y = (int) $m[1];
                    if ($y >= 1900 && $y <= $currentYear) {
                        return $y;
                    }
                }
                // Fallback strtotime
                $ts = strtotime($dob);
                if ($ts !== false) {
                    $y = (int) date('Y', $ts);
                    if ($y >= 1900 && $y <= $currentYear) {
                        return $y;
                    }
                }
            }
        }

        if (! empty($ageString) && is_numeric($ageString)) {
            $num = (int) $ageString;
            if ($num >= 1900 && $num <= $currentYear) {
                return $num;
            }
            if ($num >= 0 && $num <= 125) {
                return $currentYear - $num;
            }
        }

        return null;
    }

    /**
     * Calculate name similarity between 0.0 and 1.0 after cleaning honorifics and titles.
     */
    public static function calculateNameSimilarity(string $name1, string $name2): float
    {
        $clean1 = self::cleanNameTokens($name1);
        $clean2 = self::cleanNameTokens($name2);

        if ($clean1 === [] || $clean2 === []) {
            return 0.0;
        }

        $str1 = implode(' ', $clean1);
        $str2 = implode(' ', $clean2);

        if ($str1 === $str2) {
            return 1.0;
        }

        // Check token intersection
        $intersect = array_intersect($clean1, $clean2);
        if (empty($intersect)) {
            // No matching word at all
            $maxTokenSim = 0.0;
            foreach ($clean1 as $t1) {
                foreach ($clean2 as $t2) {
                    similar_text($t1, $t2, $pct);
                    if ($pct > $maxTokenSim) {
                        $maxTokenSim = $pct;
                    }
                }
            }
            return (float) round(($maxTokenSim / 100.0) * 0.5, 2);
        }

        $tokenScore = (count($intersect) * 2) / (count($clean1) + count($clean2));
        return (float) round(min(1.0, $tokenScore), 2);
    }

    /**
     * Clean name tokens and remove common Indian and medical salutations/fillers.
     *
     * @return list<string>
     */
    private static function cleanNameTokens(string $name): array
    {
        $name = strtoupper(trim($name));
        $name = preg_replace('/[^A-Z0-9\s]/', ' ', $name);
        $tokens = preg_split('/\s+/', (string) $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $ignored = [
            'MR', 'MRS', 'MS', 'MISS', 'MASTER', 'BABY', 'DR', 'PROF', 'SMT', 'SHRI',
            'SHREE', 'KUMARI', 'LATE', 'W/O', 'S/O', 'D/O', 'C/O', 'WIFE', 'SON', 'DAUGHTER',
        ];

        $cleaned = [];
        foreach ($tokens as $t) {
            if (strlen($t) >= 2 && ! in_array($t, $ignored, true)) {
                $cleaned[] = $t;
            }
        }

        return $cleaned;
    }
}
