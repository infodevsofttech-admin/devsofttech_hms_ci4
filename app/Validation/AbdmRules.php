<?php

namespace App\Validation;

class AbdmRules
{
    /**
     * ABHA Address constraints:
     * - Length: 8-18
     * - Allowed characters: alphanumeric, dot, underscore
     * - At most one dot/underscore in total
     */
    public function valid_abha_address(?string $value, ?string &$error = null): bool
    {
        $value = trim((string) $value);

        if ($value === '') {
            $error = 'ABHA Address is required.';

            return false;
        }

        if (! preg_match('/^[A-Za-z0-9._]{8,18}$/', $value)) {
            $error = 'ABHA Address must be 8-18 characters and contain only letters, numbers, dot or underscore.';

            return false;
        }

        preg_match_all('/[._]/', $value, $matches);
        $specialCount = isset($matches[0]) ? count($matches[0]) : 0;

        if ($specialCount > 1) {
            $error = 'ABHA Address can contain at most one dot or underscore.';

            return false;
        }

        return true;
    }
}
