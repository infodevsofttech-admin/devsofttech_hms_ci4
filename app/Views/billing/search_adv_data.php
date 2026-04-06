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
$filterAbha = trim((string) ($filters['input_abha_id'] ?? ''));
$filterName = $normalize($filters['input_name'] ?? '');
$filterRelativeName = $normalize($filters['input_relative_name'] ?? '');

$getMatchScore = static function ($row) use ($normalize, $filterPhone, $filterAadhaar, $filterAbha, $filterName, $filterRelativeName): int {
    $score = 0;

    if ($filterPhone !== '' && trim((string) ($row->mphone1 ?? '')) === $filterPhone) {
        $score++;
    }

    if ($filterAadhaar !== '' && $normalize($row->udai ?? '') === $filterAadhaar) {
        $score++;
    }

    if ($filterAbha !== '' && trim((string) ($row->abha_id ?? '')) === $filterAbha) {
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

$getConfidence = static function (int $score): array {
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

            $matchReasons = [];
            if ($filterPhone !== '' && trim((string) ($row->mphone1 ?? '')) === $filterPhone) {
                $matchReasons[] = 'Phone';
            }
            if ($filterAadhaar !== '' && $normalize($row->udai ?? '') === $filterAadhaar) {
                $matchReasons[] = 'Aadhaar';
            }
            if ($filterAbha !== '' && trim((string) ($row->abha_id ?? '')) === $filterAbha) {
                $matchReasons[] = 'ABHA';
            }
            if (
                $filterName !== ''
                && $filterRelativeName !== ''
                && $normalize($row->p_fname ?? '') === $filterName
                && $normalize($row->p_rname ?? '') === $filterRelativeName
            ) {
                $matchReasons[] = 'Name + Relative';
            }

            $isStrongDuplicate = count($matchReasons) > 1;
              $matchScore = $getMatchScore($row);
              [$confidenceText, $confidenceClass] = $getConfidence($matchScore);
            ?>
            <div class="list-group-item list-group-item-action <?= $isStrongDuplicate ? 'border border-warning' : '' ?>"
                 role="button"
                 tabindex="0"
                 onclick="load_form('<?= esc($profileUrl) ?>', 'Patient Record');"
                 onkeydown="if(event.key==='Enter' || event.key===' '){event.preventDefault();load_form('<?= esc($profileUrl) ?>', 'Patient Record');}">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="fw-semibold"><?= esc($fullName !== '' ? $fullName : 'Unknown') ?></div>
                    <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            onclick="event.stopPropagation(); load_form('<?= esc($profileUrl) ?>', 'Patient Record');">
                        View Profile
                    </button>
                </div>
                <div class="small text-muted"><?= esc($relative !== '' ? $relative : 'Relative: NA') ?></div>
                <?php if (! empty($matchReasons)) : ?>
                    <div class="mt-1">
                        <span class="badge <?= esc($confidenceClass) ?> me-1">Confidence: <?= esc($confidenceText) ?></span>
                        <?php foreach ($matchReasons as $reason) : ?>
                            <span class="badge <?= $isStrongDuplicate ? 'bg-warning text-dark' : 'bg-info text-dark' ?> me-1">Match: <?= esc($reason) ?></span>
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
