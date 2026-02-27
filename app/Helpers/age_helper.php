<?php

if (!function_exists('get_age_1')) {
    function get_age_1($inDob, $age, $ageInMonth, $estimateDob, $tillDate = null): string
    {
        $dob = null;
        if ($inDob instanceof DateTimeInterface) {
            $dob = $inDob;
        } elseif (is_string($inDob) && trim($inDob) !== '') {
            try {
                $dob = new DateTime($inDob);
            } catch (Exception $e) {
                $dob = null;
            }
        }

        if ($dob === null) {
            $dob = new DateTime('today');
        }

        $now = null;
        if ($tillDate instanceof DateTimeInterface) {
            $now = $tillDate;
        } elseif (is_string($tillDate) && trim($tillDate) !== '') {
            $tillDate = trim($tillDate);
            $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];
            foreach ($formats as $format) {
                $parsed = DateTime::createFromFormat($format, $tillDate);
                if ($parsed instanceof DateTime) {
                    $now = $parsed;
                    break;
                }
            }
            if ($now === null) {
                try {
                    $now = new DateTime($tillDate);
                } catch (Exception $e) {
                    $now = null;
                }
            }
        }
        if ($now === null) {
            $now = new DateTime('today');
        }
        $diff = $dob->diff($now);
        $years = (int) $diff->y;
        $months = (int) $diff->m;
        $days = (int) $diff->d;

        if ($years > 4) {
            $lAge = $years . ' Year ';
        } else {
            $lAge = $years . ' Year ' . $months . ' Month ' . $days . ' Days';
        }

        if ((int) $dob->format('Y') < 1915) {
            $lAge = '';
        }

        if ((string) $estimateDob === '1') {
            $age = trim((string) $age);
            $ageInMonth = trim((string) $ageInMonth);

            if ($ageInMonth === '' || $ageInMonth === '0') {
                $lAge = $age !== '' ? $age . ' Year' : '';
            } elseif ($age === '' || $age === '0') {
                $lAge = $ageInMonth . ' Month';
            } else {
                $lAge = $age . ' Year -' . $ageInMonth . ' Month';
            }
        }

        return $lAge;
    }
}
