<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($page_title) ?> — ABDM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .wrap { max-width: 1200px; margin: 24px auto; }
        .suggestion-card { border-left: 4px solid #dee2e6; }
        .suggestion-card.status-confirmed  { border-left-color: #198754; }
        .suggestion-card.status-rejected   { border-left-color: #dc3545; opacity: .65; }
        .suggestion-card.status-corrected  { border-left-color: #0d6efd; }
        .confidence-bar { height: 6px; border-radius: 3px; background: #e9ecef; }
        .confidence-fill { height: 100%; border-radius: 3px; background: #0d6efd; transition: width .3s; }
        .field-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; }
        .snomed-tag { font-size: 11px; background: #e7f1ff; color: #084298; border-radius: 3px; padding: 1px 6px; }
        .correct-form { display: none; }
    </style>
</head>
<body>
<div class="wrap px-3">

    <?php
    $consult    = $consult    ?? [];
    $suggestions = $suggestions ?? [];
    $sessionId  = (int)($session_id ?? 0);

    // Group suggestions by source_field
    $grouped = [];
    foreach ($suggestions as $s) {
        $grouped[$s['source_field']][] = $s;
    }

    $fieldLabels = [
        'complaints'            => 'Complaints',
        'diagnosis'             => 'Diagnosis',
        'Provisional_diagnosis' => 'Provisional Diagnosis',
        'Finding_Examinations'  => 'Findings / Examinations',
    ];

    $hasPending = count(array_filter($suggestions, fn($s) => $s['status'] === 'pending_review')) > 0;
    $csrfName   = csrf_token();
    $csrfHash   = csrf_hash();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Review SNOMED Codes</h4>
            <div class="small text-muted">
                <?= esc($consult['patient_name'] ?? '') ?>
                &mdash; OPD <?= esc($consult['opd_no'] ?? $consult['opd_id'] ?? '') ?>
                &mdash; <?= esc($consult['opd_date'] ?? '') ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if (!$hasPending): ?>
                <button type="button" class="btn btn-success btn-sm" id="btnMarkFhirReady">
                    Mark FHIR Ready &amp; Update Codes
                </button>
            <?php endif; ?>
            <a href="<?= site_url('AbdmCodingPanel') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
        </div>
    </div>

    <?php if (!empty($consult)): ?>
    <div class="card mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 small">
                <div class="col-md-6">
                    <span class="fw-semibold">Complaints:</span>
                    <?= esc($consult['complaints'] ?? '—') ?>
                </div>
                <div class="col-md-6">
                    <span class="fw-semibold">Diagnosis:</span>
                    <?= esc($consult['diagnosis'] ?? '—') ?>
                </div>
                <div class="col-md-6">
                    <span class="fw-semibold">Provisional Diagnosis:</span>
                    <?= esc($consult['Provisional_diagnosis'] ?? '—') ?>
                </div>
                <div class="col-md-6">
                    <span class="fw-semibold">Findings:</span>
                    <?= esc($consult['Finding_Examinations'] ?? '—') ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Suggestions grouped by field -->
    <?php foreach ($grouped as $field => $fieldSuggestions): ?>
    <div class="mb-4">
        <h6 class="field-label mb-2"><?= esc($fieldLabels[$field] ?? $field) ?></h6>

        <?php
        // Group by source_phrase within this field
        $byPhrase = [];
        foreach ($fieldSuggestions as $s) {
            $byPhrase[$s['source_phrase']][] = $s;
        }
        ?>

        <?php foreach ($byPhrase as $phrase => $phrSuggestions): ?>
        <div class="card mb-2">
            <div class="card-header py-2 bg-light">
                <span class="fw-semibold">"<?= esc($phrase) ?>"</span>
                <span class="ms-2 small text-muted">— <?= count($phrSuggestions) ?> candidate(s)</span>
            </div>
            <div class="card-body p-2">
                <?php foreach ($phrSuggestions as $s): ?>
                <?php
                    $statusClass = 'status-' . $s['status'];
                    $pct = min(100, round((float)$s['confidence'] * 100));
                ?>
                <div class="suggestion-card p-2 mb-2 rounded bg-white border <?= $statusClass ?>" data-id="<?= (int)$s['id'] ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1 me-3">
                            <div class="fw-semibold"><?= esc($s['snomed_term']) ?></div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <code class="small"><?= esc($s['concept_id']) ?></code>
                                <?php if ($s['semantic_tag']): ?>
                                    <span class="snomed-tag"><?= esc($s['semantic_tag']) ?></span>
                                <?php endif; ?>
                                <span class="small text-muted"><?= $pct ?>% match</span>
                            </div>
                            <div class="confidence-bar mt-1 w-25">
                                <div class="confidence-fill" style="width:<?= $pct ?>%"></div>
                            </div>

                            <?php if ($s['status'] === 'corrected'): ?>
                                <div class="mt-1 small text-primary">
                                    Corrected to: <strong><?= esc($s['corrected_term']) ?></strong>
                                    <code><?= esc($s['corrected_concept_id']) ?></code>
                                </div>
                            <?php endif; ?>

                            <!-- Correction form (hidden by default) -->
                            <div class="correct-form mt-2" id="correctForm<?= (int)$s['id'] ?>">
                                <div class="row g-1">
                                    <div class="col-4">
                                        <input type="text" class="form-control form-control-sm" placeholder="Concept ID (numeric)"
                                               id="corrConceptId<?= (int)$s['id'] ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" class="form-control form-control-sm" placeholder="SNOMED Term"
                                               id="corrTerm<?= (int)$s['id'] ?>">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-sm btn-primary w-100 btn-save-correction"
                                                data-id="<?= (int)$s['id'] ?>">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($s['status'] === 'pending_review'): ?>
                        <div class="d-flex flex-column gap-1 ms-2 flex-shrink-0">
                            <button type="button" class="btn btn-sm btn-success btn-confirm" data-id="<?= (int)$s['id'] ?>">✓ Confirm</button>
                            <button type="button" class="btn btn-sm btn-danger btn-reject" data-id="<?= (int)$s['id'] ?>">✗ Reject</button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-correct" data-id="<?= (int)$s['id'] ?>">✎ Correct</button>
                        </div>
                        <?php elseif ($s['status'] === 'confirmed'): ?>
                        <span class="badge bg-success align-self-start">Confirmed</span>
                        <?php elseif ($s['status'] === 'rejected'): ?>
                        <span class="badge bg-danger align-self-start">Rejected</span>
                        <?php elseif ($s['status'] === 'corrected'): ?>
                        <span class="badge bg-primary align-self-start">Corrected</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($grouped)): ?>
        <div class="alert alert-info">No suggestions for this session.</div>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-3 mb-5">
        <?php
        $allReviewed = !$hasPending && count($suggestions) > 0;
        ?>
        <button type="button" class="btn btn-success" id="btnMarkFhirReady"
            <?= $allReviewed ? '' : 'disabled' ?>>
            Mark FHIR Ready &amp; Update OPD Codes
            <?php if (!$allReviewed): ?>
                <span class="badge bg-warning text-dark ms-1">Pending review</span>
            <?php endif; ?>
        </button>
        <a href="<?= site_url('AbdmCodingPanel') ?>" class="btn btn-outline-secondary">← Back</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var sessionId = <?= $sessionId ?>;
    var csrfName  = '<?= $csrfName ?>';
    var csrfHash  = '<?= $csrfHash ?>';

    function apiPost(url, data, cb) {
        data[csrfName] = csrfHash;
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(j) {
            csrfName = j.csrfName || csrfName;
            csrfHash = j.csrfHash || csrfHash;
            cb(j);
        })
        .catch(function(e) { console.error(url, e); cb({ok:0}); });
    }

    function setSuggestionStatus(id, status) {
        var card = document.querySelector('.suggestion-card[data-id="' + id + '"]');
        if (!card) return;
        card.classList.remove('status-pending_review', 'status-confirmed', 'status-rejected', 'status-corrected');
        card.classList.add('status-' + status);

        // Replace action buttons with badge
        var btns = card.querySelector('.d-flex.flex-column');
        if (btns) {
            var labels = { confirmed: 'success', rejected: 'danger', corrected: 'primary' };
            var bc = labels[status] || 'secondary';
            var label = status.charAt(0).toUpperCase() + status.slice(1);
            btns.outerHTML = '<span class="badge bg-' + bc + ' align-self-start">' + label + '</span>';
        }

        checkAllReviewed();
    }

    function checkAllReviewed() {
        var pending = document.querySelectorAll('.suggestion-card.status-pending_review').length;
        var btn = document.getElementById('btnMarkFhirReady');
        if (btn) {
            btn.disabled = pending > 0;
            var badge = btn.querySelector('.badge');
            if (badge) badge.remove();
            if (pending > 0) {
                var b = document.createElement('span');
                b.className = 'badge bg-warning text-dark ms-1';
                b.textContent = pending + ' pending';
                btn.appendChild(b);
            }
        }
    }

    // Confirm
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-confirm');
        if (!btn) return;
        var id = btn.dataset.id;
        btn.disabled = true;
        apiPost('<?= site_url('AbdmCodingPanel/confirm') ?>', { suggestion_id: id }, function(j) {
            if (j.ok) { setSuggestionStatus(id, 'confirmed'); }
            else { btn.disabled = false; alert(j.error_text || 'Error'); }
        });
    });

    // Reject
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-reject');
        if (!btn) return;
        var id = btn.dataset.id;
        btn.disabled = true;
        apiPost('<?= site_url('AbdmCodingPanel/reject') ?>', { suggestion_id: id }, function(j) {
            if (j.ok) { setSuggestionStatus(id, 'rejected'); }
            else { btn.disabled = false; alert(j.error_text || 'Error'); }
        });
    });

    // Show correction form
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-correct');
        if (!btn) return;
        var id = btn.dataset.id;
        var form = document.getElementById('correctForm' + id);
        if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });

    // Save correction
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-save-correction');
        if (!btn) return;
        var id         = btn.dataset.id;
        var conceptId  = document.getElementById('corrConceptId' + id).value.trim();
        var term       = document.getElementById('corrTerm' + id).value.trim();
        if (!conceptId || !term) { alert('Concept ID and Term are required.'); return; }
        btn.disabled = true;
        apiPost('<?= site_url('AbdmCodingPanel/correct') ?>', {
            suggestion_id: id, corrected_concept_id: conceptId, corrected_term: term
        }, function(j) {
            if (j.ok) { setSuggestionStatus(id, 'corrected'); }
            else { btn.disabled = false; alert(j.error_text || 'Error'); }
        });
    });

    // Mark FHIR Ready
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('#btnMarkFhirReady');
        if (!btn || btn.disabled) return;
        if (!confirm('Mark all confirmed/corrected codes as FHIR-ready and update OPD prescription fields?')) return;
        btn.disabled = true;
        btn.textContent = 'Saving…';
        apiPost('<?= site_url('AbdmCodingPanel/mark_fhir_ready') ?>', { session_id: sessionId }, function(j) {
            if (j.ok) {
                btn.textContent = '✓ FHIR Ready';
                btn.className = 'btn btn-outline-success';
            } else {
                btn.disabled = false;
                btn.textContent = 'Mark FHIR Ready & Update OPD Codes';
                alert(j.error_text || 'Error');
            }
        });
    });

})();
</script>
</body>
</html>
