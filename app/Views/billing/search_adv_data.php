<?php
$formatDate = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00') {
        return '';
    }

    $dt = \DateTime::createFromFormat('Y-m-d', $value);
    if (! $dt) {
        return '';
    }

    return $dt->format('d-m-Y');
};

$getAgeDisplay = static function ($row): string {
    $dob = trim((string) ($row->dob ?? ''));
    if ($dob !== '' && $dob !== '0000-00-00') {
        $dobDate = date_create($dob);
        if ($dobDate !== false) {
            $ageYears = date_diff($dobDate, new \DateTime('today'))->y;
            return (string) $ageYears;
        }
    }

    $years = trim((string) ($row->age ?? ''));
    $months = trim((string) ($row->age_in_month ?? ''));
    if ($years !== '') {
        return $months !== '' ? ($years . 'y ' . $months . 'm') : $years;
    }

    return '';
};

$genderLabel = static function ($gender): string {
    $gender = trim((string) $gender);
    if ($gender === '1') {
        return 'Male';
    }
    if ($gender === '2') {
        return 'Female';
    }

    return $gender !== '' ? $gender : 'NA';
};

$normalize = static function ($value): string {
    return strtoupper(trim((string) $value));
};

$filters = $filters ?? [];
$filterPhone = trim((string) ($filters['input_mphone1'] ?? ''));
$filterAadhaar = $normalize($filters['input_udai'] ?? '');
$filterAadhaarDigits = preg_replace('/\D/', '', (string) ($filters['input_udai'] ?? '')) ?? '';
$filterAadhaarLast4 = substr($filterAadhaarDigits, -4);

$filterAbha = trim((string) ($filters['input_abha_id'] ?? ''));
$filterAbhaDigits = preg_replace('/\D/', '', $filterAbha) ?? '';

$filterName = $normalize($filters['input_name'] ?? '');
$filterRelativeName = $normalize($filters['input_relative_name'] ?? '');

$isAbhaMatch = static function ($row) use ($filterAbha, $filterAbhaDigits): bool {
    if ($filterAbha === '') {
        return false;
    }
    $rowAbha = trim((string) ($row->abha_id ?? ''));
    if ($rowAbha === '') {
        return false;
    }
    if ($rowAbha === $filterAbha) {
        return true;
    }
    $rowAbhaDigits = preg_replace('/\D/', '', $rowAbha) ?? '';
    if ($filterAbhaDigits !== '' && strlen($filterAbhaDigits) === 14 && $rowAbhaDigits === $filterAbhaDigits) {
        return true;
    }
    return false;
};

$isAadhaarMatch = static function ($row) use ($filterAadhaarDigits, $filterAadhaarLast4): bool {
    if ($filterAadhaarDigits === '' || strlen($filterAadhaarDigits) !== 12) {
        return false;
    }
    $rowUdai = trim((string) ($row->udai ?? ''));
    $rowLast4 = trim((string) ($row->udai_last4 ?? ''));
    if ($rowLast4 !== '' && $rowLast4 === $filterAadhaarLast4) {
        return true;
    }
    $rowUdaiDigits = preg_replace('/\D/', '', $rowUdai) ?? '';
    if ($rowUdaiDigits === $filterAadhaarDigits) {
        return true;
    }
    return false;
};

$getMatchScore = static function ($row) use ($normalize, $filterPhone, $filterName, $filterRelativeName, $isAbhaMatch, $isAadhaarMatch): int {
    $score = 0;

    // Direct identifier matches carry massive weight
    if ($isAbhaMatch($row)) {
        $score += 10;
    }

    if ($isAadhaarMatch($row)) {
        $score += 10;
    }

    if ($filterPhone !== '' && trim((string) ($row->mphone1 ?? '')) === $filterPhone) {
        $score++;
    }

    if (
        $filterName !== ''
        && $filterRelativeName !== ''
        && $normalize($row->p_fname ?? '') === $filterName
        && $normalize($row->p_rname ?? '') === $filterRelativeName
    ) {
        $score++;
    }

    return $score;
};

$getConfidence = static function (int $score, bool $isIdentifierConflict): array {
    if ($isIdentifierConflict || $score >= 10) {
        return ['High (Duplicate Conflict)', 'bg-danger text-white'];
    }

    if ($score >= 3) {
        return ['High', 'bg-danger'];
    }

    if ($score === 2) {
        return ['Medium', 'bg-warning text-dark'];
    }

    return ['Low', 'bg-secondary'];
};

if (! empty($search_result) && is_array($search_result)) {
    usort($search_result, static function ($a, $b) use ($getMatchScore) {
        $scoreA = $getMatchScore($a);
        $scoreB = $getMatchScore($b);

        if ($scoreA !== $scoreB) {
            return $scoreB <=> $scoreA;
        }

        return ((int) ($b->id ?? 0)) <=> ((int) ($a->id ?? 0));
    });
}
?>

<?php if (! empty($search_result)) : ?>
    <div class="list-group">
        <?php foreach ($search_result as $row) : ?>
            <?php
            $profileUrl = base_url('billing/patient/person_record') . '/' . (int) ($row->id ?? 0);
            $fullName = trim((string) ($row->p_fname ?? ''));
            $referBy = trim((string) ($row->referby ?? ''));
            $relative = trim((string) ($row->p_relative ?? '')) . ' ' . trim((string) ($row->p_rname ?? ''));
            $relative = trim($relative);
            $addressParts = array_filter([
                trim((string) ($row->add1 ?? '')),
                trim((string) ($row->city ?? '')),
                trim((string) ($row->district ?? '')),
                trim((string) ($row->state ?? '')),
                trim((string) ($row->zip ?? '')),
            ], static fn($v) => $v !== '');
            $addressText = implode(', ', $addressParts);
            $lastVisit = $formatDate($row->last_visit ?? '');
            $ageDisplay = $getAgeDisplay($row);
            $gender = $genderLabel($row->gender ?? '');

            $hasAbhaConflict = $isAbhaMatch($row);
            $hasAadhaarConflict = $isAadhaarMatch($row);
            $isIdentifierConflict = $hasAbhaConflict || $hasAadhaarConflict;

            $matchReasons = [];
            if ($filterPhone !== '' && trim((string) ($row->mphone1 ?? '')) === $filterPhone) {
                $matchReasons[] = 'Phone';
            }
            if ($hasAadhaarConflict) {
                $matchReasons[] = 'Aadhaar';
            }
            if ($hasAbhaConflict) {
                $matchReasons[] = 'ABHA ID';
            }
            if (
                $filterName !== ''
                && $filterRelativeName !== ''
                && $normalize($row->p_fname ?? '') === $filterName
                && $normalize($row->p_rname ?? '') === $filterRelativeName
            ) {
                $matchReasons[] = 'Name + Relative';
            }

            $isStrongDuplicate = count($matchReasons) > 1 || $isIdentifierConflict;
            $matchScore = $getMatchScore($row);
            [$confidenceText, $confidenceClass] = $getConfidence($matchScore, $isIdentifierConflict);
            ?>
            <div class="list-group-item list-group-item-action <?= $isIdentifierConflict ? 'border-2 border-danger shadow-sm' : ($isStrongDuplicate ? 'border border-warning' : '') ?>"
                 role="button"
                 tabindex="0"
                 onclick="load_form('<?= esc($profileUrl) ?>', 'Patient Record');"
                 onkeydown="if(event.key==='Enter' || event.key===' '){event.preventDefault();load_form('<?= esc($profileUrl) ?>', 'Patient Record');}">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="fw-semibold <?= $isIdentifierConflict ? 'text-danger' : '' ?>">
                        <?= esc($fullName !== '' ? $fullName : 'Unknown') ?>
                        <?php if ($isIdentifierConflict): ?>
                            <span class="badge bg-danger ms-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>ALREADY REGISTERED</span>
                        <?php endif; ?>
                    </div>
                    <button type="button"
                            class="btn btn-sm <?= $isIdentifierConflict ? 'btn-danger' : 'btn-outline-primary' ?>"
                            onclick="event.stopPropagation(); load_form('<?= esc($profileUrl) ?>', 'Patient Record');">
                        View Profile
                    </button>
                </div>
                <?php if ($referBy !== '') : ?>
                    <div class="small fst-italic text-muted">Refer By: <?= esc(ucwords(strtolower($referBy))) ?></div>
                <?php endif; ?>
                <div class="small text-muted"><?= esc($relative !== '' ? $relative : 'Relative: NA') ?></div>

                <?php if ($hasAbhaConflict): ?>
                    <div class="alert alert-danger py-1 px-2 mb-1 mt-2 small">
                        <i class="bi bi-shield-lock-fill me-1"></i><strong>ABHA Conflict:</strong> Already linked to this patient. Cannot register new person with same ABHA ID.
                    </div>
                <?php endif; ?>

                <?php if ($hasAadhaarConflict): ?>
                    <div class="alert alert-danger py-1 px-2 mb-1 mt-1 small">
                        <i class="bi bi-person-vcard-fill me-1"></i><strong>Aadhaar Conflict:</strong> Already linked to this patient. Cannot register new person with same Aadhaar.
                    </div>
                <?php endif; ?>

                <?php if (! empty($matchReasons)) : ?>
                    <div class="mt-1">
                        <span class="badge <?= esc($confidenceClass) ?> me-1">Confidence: <?= esc($confidenceText) ?></span>
                        <?php foreach ($matchReasons as $reason) : ?>
                            <?php $isCrit = ($reason === 'ABHA ID' || $reason === 'Aadhaar'); ?>
                            <span class="badge <?= $isCrit ? 'bg-danger text-white' : ($isStrongDuplicate ? 'bg-warning text-dark' : 'bg-info text-dark') ?> me-1">
                                <?= $isCrit ? '<i class="bi bi-exclamation-circle-fill me-1"></i>' : '' ?>Match: <?= esc($reason) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mt-1 small">
                    <span class="me-2"><strong>Gender:</strong> <?= esc($gender) ?></span>
                    <?php if ($ageDisplay !== '') : ?>
                        <span class="me-2"><strong>Age:</strong> <?= esc($ageDisplay) ?></span>
                    <?php endif; ?>
                    <?php if ($lastVisit !== '') : ?>
                        <span><strong>Last Visit:</strong> <?= esc($lastVisit) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($addressText !== '') : ?>
                    <div class="small mt-1"><strong>Address:</strong> <?= esc($addressText) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="text-muted">No matching records.</div>
<?php endif; ?>
