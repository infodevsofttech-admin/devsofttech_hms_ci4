<?php
$doc = $doc_master[0] ?? null;

$canOpenPrescription = false;
$authUser = function_exists('auth') ? auth()->user() : null;
if ($authUser && method_exists($authUser, 'can') && $authUser->can('opd.doctor-panel.access')) {
    $canOpenPrescription = true;
}

$countBooked    = (int) ($doc->count_booking ?? 0);
$countWaiting   = (int) ($doc->count_wait    ?? 0);
$countVisited   = (int) ($doc->count_visit   ?? 0);
$countCancelled = (int) ($doc->count_cancel  ?? 0);
$countAll       = (int) ($doc->No_opd        ?? 0);

// Count waiting patients whose vitals are missing (for badge on filter button)
$missingVitalsCount = 0;
foreach (($opd_list_1 ?? []) as $_r) {
    if ((int) ($_r->has_prescription ?? 0) === 1 && (int) ($_r->has_vitals ?? 0) !== 1) {
        $missingVitalsCount++;
    }
}

// Build merged dataset for the unified table
$mergedGroups = [
    ['status' => 'booked',    'label' => 'Booked',    'badge' => 'bg-secondary',        'tabType' => 'booking',   'rows' => ($opd_list_0 ?? [])],
    ['status' => 'waiting',   'label' => 'Waiting',   'badge' => 'bg-primary',           'tabType' => 'waiting',   'rows' => ($opd_list_1 ?? [])],
    ['status' => 'visited',   'label' => 'Visited',   'badge' => 'bg-info text-dark',   'tabType' => 'visited',   'rows' => ($opd_list_2 ?? [])],
    ['status' => 'cancelled', 'label' => 'Cancelled', 'badge' => 'bg-danger',            'tabType' => 'cancelled', 'rows' => ($opd_list_3 ?? [])],
];
?>
<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Dr. <?= esc($doc->p_fname ?? '') ?> <small class="text-muted"><?= esc($doc->Spec ?? '') ?></small></h3>
        <a href="javascript:load_form('<?= base_url('Opd/get_appointment_data') ?>/<?= esc($opd_date) ?>','OPD Appointment List');" class="btn btn-link">Back to OPD</a>
    </div>
</section>

<section class="content">
    <!-- Filter buttons + Missing Vitals toggle -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="btn-group" role="group" aria-label="Filter by status">
            <button type="button" class="btn btn-sm btn-outline-secondary btn-opd-filter" data-filter="">
                All <span id="countBadgeAll" class="badge bg-secondary ms-1"><?= $countAll ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-opd-filter" data-filter="booked">
                Booked <span id="countBadgeBooked" class="badge bg-secondary ms-1"><?= $countBooked ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary active btn-opd-filter" data-filter="waiting">
                Waiting <span id="countBadgeWaiting" class="badge bg-primary ms-1"><?= $countWaiting ?></span>
                <span id="waitingMissingBadge" class="badge rounded-pill bg-warning text-dark ms-1<?= $missingVitalsCount > 0 ? '' : ' d-none' ?>" title="<?= $missingVitalsCount ?> patient(s) missing vitals"><?= $missingVitalsCount ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-info btn-opd-filter" data-filter="visited">
                Visited <span id="countBadgeVisited" class="badge bg-info text-dark ms-1"><?= $countVisited ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-opd-filter" data-filter="cancelled">
                Cancelled <span id="countBadgeCancelled" class="badge bg-danger ms-1"><?= $countCancelled ?></span>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <button type="button" id="btn_manual_refresh" class="btn btn-sm btn-outline-secondary" title="Refresh list">
                &#x21BB; Refresh
            </button>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggle_missing_vitals_only">
                <label class="form-check-label small" for="toggle_missing_vitals_only">Only Missing Vitals</label>
            </div>
        </div>
    </div>

    <!-- Single unified table -->
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0" id="opdAllTable">
            <thead>
            <tr>
                <th>Status</th>
                <th>OPD No.</th>
                <th>Current Patient</th>
                <th>Q No.</th>
                <th>UHID</th>
                <th>OPD Type</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($mergedGroups as $group) : ?>
                <?php foreach ($group['rows'] as $row) : ?>
                    <?php
                    $hasVitals          = (int) ($row->has_vitals          ?? 0) === 1;
                    $hasPrescription    = (int) ($row->has_prescription    ?? 0) === 1;
                    $isConfirmedForQueue= (int) ($row->is_confirmed_for_queue ?? 0) === 1;
                    $queueNo            = (int) (($row->display_queue_no ?? 0) ?: ($row->queue_no ?? 0));
                    $hasQueueNo         = $queueNo > 0;
                    $showFullActions    = $hasPrescription || $isConfirmedForQueue;
                    $opdId              = (int) ($row->opd_id ?? 0);
                    $patientRawName     = trim((string) ($row->P_name ?? '') . ' { ' . (string) ($row->p_rname ?? '') . ' }');
                    $patientName        = esc($patientRawName);
                    $patientAgeText     = function_exists('get_age_1')
                        ? trim((string) get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '', $opd_date ?? null))
                        : trim((string) ($row->age ?? ''));
                    $patientGenderCode  = (int) ($row->gender ?? 0);
                    $patientGenderText  = $patientGenderCode === 1 ? 'Male' : ($patientGenderCode === 2 ? 'Female' : ($patientGenderCode === 3 ? 'Other' : ''));
                    $patientAgeGender   = trim($patientAgeText . ($patientGenderText !== '' ? ' / ' . $patientGenderText : ''));
                    $patientDisplayName = esc($patientRawName . ($patientAgeGender !== '' ? ' (' . $patientAgeGender . ')' : ''));
                    $tabType            = $group['tabType'];
                    $status             = $group['status'];
                    ?>
                    <tr data-opd-id="<?= $opdId ?>"
                        data-opd-status="<?= esc($status) ?>"
                        data-has-vitals="<?= (int) ($row->has_vitals ?? 0) ?>"
                        data-has-prescription="<?= $showFullActions ? 1 : 0 ?>">
                        <td><span class="badge <?= esc($group['badge']) ?>"><?= esc($group['label']) ?></span></td>
                        <td><?= esc($row->opd_code ?? '') ?></td>
                        <td><?= $patientDisplayName ?></td>
                        <td data-order="<?= $hasQueueNo ? $queueNo : 9999 ?>"><?= $hasQueueNo ? $queueNo : '' ?></td>
                        <td><?= esc($row->p_code ?? '') ?></td>
                        <td><?= esc($row->opd_type ?? '') ?> / Amt: <?= esc($row->opd_fee_amount ?? '') ?></td>
                        <td>
                            <?php if ($tabType === 'booking') : ?>
                                <?php if ($isConfirmedForQueue) : ?>
                                    <button type="button" class="btn btn-outline-success btn-sm btn-opd-create-queue" data-opdid="<?= $opdId ?>" title="Add to Queue">
                                        Queue
                                    </button>
                                <?php else : ?>
                                    <a class="btn btn-outline-primary btn-sm" href="javascript:load_form('/Opd/invoice/<?= $opdId ?>','OPD Invoice');">
                                        Go For Payment
                                    </a>
                                <?php endif; ?>
                            <?php else : ?>
                                <?php if (!$hasQueueNo) : ?>
                                    <button type="button" class="btn btn-outline-success btn-sm btn-opd-create-queue" data-opdid="<?= $opdId ?>" title="Add to Queue">
                                        Queue
                                    </button>
                                <?php endif; ?>
                                <?php if ($canOpenPrescription && $showFullActions) : ?>
                                    <a class="btn btn-outline-primary btn-sm" href="javascript:load_form('/Opd_prescription/Prescription/<?= $opdId ?>','Consult');">
                                        Consult
                                    </a>
                                <?php endif; ?>
                                <?php if ($showFullActions) : ?>
                                    <button type="button"
                                            class="btn <?= $hasVitals ? 'btn-success' : 'btn-warning text-dark' ?> btn-sm btn-opd-vitals"
                                            title="<?= $hasVitals ? 'Vitals Filled' : 'Vitals' ?>"
                                            data-opdid="<?= $opdId ?>"
                                            data-patient="<?= $patientName ?>">
                                        <?= $hasVitals ? 'Vitals ✓' : 'Vitals' ?>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-info btn-sm btn-opd-scan" title="Scan" data-opdid="<?= $opdId ?>">Scan</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-opd-scan" title="Upload Scan" data-opdid="<?= $opdId ?>">Upload</button>
                                <button type="button" class="btn btn-outline-dark btn-sm btn-opd-scan-list" title="Scan Doc List" data-opdid="<?= $opdId ?>">Scan Doc List</button>
                                <?php if ($tabType === 'waiting' && $showFullActions) : ?>
                                    <button type="button" class="btn btn-outline-success btn-sm btn-opd-status"
                                            data-opd-id="<?= $opdId ?>" data-opd-status="2" title="Visit Done">
                                        Visit Done
                                    </button>
                                <?php elseif ($tabType === 'visited') : ?>
                                    <button type="button" class="btn btn-success btn-sm btn-opd-status"
                                            data-opd-id="<?= $opdId ?>" data-opd-status="2" title="Visit Done">
                                        Visit Done
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if ($countAll === 0) : ?>
                <tr><td colspan="7" class="text-muted text-center py-3">No OPD records found for this date.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="waitingNoRowsHint" class="small text-muted mt-2 d-none">All waiting patients already have vitals.</div>
</section>

<div class="modal fade" id="tallModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testentryLabel">OPD Scan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height:75vh;overflow:auto;">
                <div id="testentry-bodyc"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vitalsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vitalsModalLabel">Nursing Vitals Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= csrf_field() ?>
                <input type="hidden" id="vital_opd_id" value="0">
                <input type="hidden" id="vital_opd_session_id" value="0">

                <div class="small text-muted mb-2" id="vital_patient_label">Patient: -</div>
                <div class="row g-2">
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_pulse" placeholder="Pulse"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_spo2" placeholder="SPO2"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_bp" placeholder="BP Systolic"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_diastolic" placeholder="Diastolic"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_temp" placeholder="Temp"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_rr_min" placeholder="RR/min"></div>
                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" id="vital_height" placeholder="Height"></div>
                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" id="vital_weight" placeholder="Weight"></div>
                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" id="vital_waist" placeholder="Waist"></div>
                </div>

                <div class="small mt-2 text-muted" id="vital_msg">Fill vitals and click Save.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn_save_vitals">Save Vitals</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var modalEl = document.getElementById('tallModal');
    var modalObj = modalEl ? new bootstrap.Modal(modalEl) : null;
    var vitalsModalEl = document.getElementById('vitalsModal');
    var vitalsModalObj = vitalsModalEl ? new bootstrap.Modal(vitalsModalEl) : null;
    var opdDataTable = null;
    var activeStatusFilter = 'waiting';
    var canOpenPrescription = <?= $canOpenPrescription ? 'true' : 'false' ?>;
    var isUserUpdateInProgress = false;
    var liveDigest = '';
    var livePollInFlight = false;
    var livePollMs = 8000;
    var liveDigestUrl = '/Opd/get_appointment_digest/<?= esc((int) ($doc_id ?? 0)) ?>/<?= esc($opd_date ?? date('Y-m-d')) ?>';
    var liveRowsUrl = '/Opd/get_appointment_live_rows/<?= esc((int) ($doc_id ?? 0)) ?>/<?= esc($opd_date ?? date('Y-m-d')) ?>';

    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (!input) {
            return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function updateCsrf(data) {
        if (!data || !data.csrfName || !data.csrfHash) {
            return;
        }
        var input = document.querySelector('input[name="' + data.csrfName + '"]');
        if (input) {
            input.value = data.csrfHash;
        }
    }

    // --- DataTable status + missing-vitals custom search filter ---
    function registerOpdTableFilter() {
        if (!window.jQuery || !$.fn || !$.fn.dataTable) {
            return;
        }

        if (window.opdTableFilter) {
            var existingIndex = $.fn.dataTable.ext.search.indexOf(window.opdTableFilter);
            if (existingIndex >= 0) {
                $.fn.dataTable.ext.search.splice(existingIndex, 1);
            }
        }

        window.opdTableFilter = function(settings, data, dataIndex) {
            if (!settings || !settings.nTable || settings.nTable.id !== 'opdAllTable') {
                return true;
            }

            var rowNode = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            if (!rowNode) {
                return true;
            }

            var $row = $(rowNode);
            var rowStatus = ($row.attr('data-opd-status') || '').toLowerCase();

            // Status tab filter
            if (activeStatusFilter && rowStatus !== activeStatusFilter) {
                return false;
            }

            // "Only Missing Vitals" secondary filter
            if ($('#toggle_missing_vitals_only').is(':checked')) {
                if (rowStatus !== 'waiting') {
                    return false;
                }
                var hasVitals = parseInt($row.attr('data-has-vitals') || '0', 10) === 1;
                var hasPrescription = parseInt($row.attr('data-has-prescription') || '0', 10) === 1;
                if (!hasPrescription || hasVitals) {
                    return false;
                }
            }

            return true;
        };

        $.fn.dataTable.ext.search.push(window.opdTableFilter);
    }

    function initializeOpdTable() {
        if (!window.jQuery || !$.fn || !$.fn.DataTable) {
            return null;
        }

        if ($.fn.DataTable.isDataTable('#opdAllTable')) {
            $('#opdAllTable').DataTable().destroy();
        }

        return $('#opdAllTable').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            pageLength: 25,
            order: [[1, 'asc']],
            columnDefs: [
                { targets: [0], searchable: false },
                { targets: [6], orderable: false, searchable: false }
            ]
        });
    }

    function syncWaitingHintAndBadge() {
        var $waitingRows = opdDataTable
            ? $(opdDataTable.rows({ page: 'all', search: 'none' }).nodes()).filter('[data-opd-status="waiting"]')
            : $('#opdAllTable tbody tr[data-opd-status="waiting"]');
        var missingCount = 0;

        $waitingRows.each(function() {
            var hasVitals = parseInt($(this).attr('data-has-vitals') || '0', 10) === 1;
            var hasPrescription = parseInt($(this).attr('data-has-prescription') || '0', 10) === 1;
            if (hasPrescription && !hasVitals) {
                missingCount++;
            }
        });

        var $badge = $('#waitingMissingBadge');
        $badge.text(String(missingCount));
        $badge.toggleClass('d-none', missingCount <= 0);

        var onlyMissing = $('#toggle_missing_vitals_only').is(':checked');
        $('#waitingNoRowsHint').toggleClass('d-none', !(onlyMissing && $waitingRows.length > 0 && missingCount === 0));
    }

    function recalcStatusCounts() {
        var counts = {
            all: 0,
            booked: 0,
            waiting: 0,
            visited: 0,
            cancelled: 0
        };

        var $rows = opdDataTable
            ? $(opdDataTable.rows({ page: 'all', search: 'none' }).nodes()).filter('[data-opd-id]')
            : $('#opdAllTable tbody tr[data-opd-id]');

        $rows.each(function() {
            var status = (($(this).attr('data-opd-status') || '').toLowerCase());
            counts.all += 1;
            if (Object.prototype.hasOwnProperty.call(counts, status)) {
                counts[status] += 1;
            }
        });

        $('#countBadgeAll').text(String(counts.all));
        $('#countBadgeBooked').text(String(counts.booked));
        $('#countBadgeWaiting').text(String(counts.waiting));
        $('#countBadgeVisited').text(String(counts.visited));
        $('#countBadgeCancelled').text(String(counts.cancelled));
    }

    function applyStatusVisual($row, status) {
        var $statusBadge = $row.find('td:first .badge');
        if (!$statusBadge.length) {
            return;
        }

        $statusBadge.removeClass('bg-secondary bg-primary bg-info bg-danger text-dark');

        if (status === 'waiting') {
            $statusBadge.addClass('bg-primary').text('Waiting');
            return;
        }

        if (status === 'visited') {
            $statusBadge.addClass('bg-info text-dark').text('Visited');
            return;
        }

        if (status === 'cancelled') {
            $statusBadge.addClass('bg-danger').text('Cancelled');
            return;
        }

        $statusBadge.addClass('bg-secondary').text('Booked');
    }

    function ensureWaitingActionButtons($row, opdId) {
        var $actionCell = $row.find('td').eq(6);
        var patient = ($row.find('td').eq(2).text() || '').trim();

        $actionCell.find('.btn-opd-create-queue').remove();

        var $consultAnchor = $actionCell.find('a[href*="/Opd_prescription/Prescription/"], a[href*="Opd_prescription/Prescription/"]');
        if ($consultAnchor.length && $actionCell.find('.btn-opd-consult-open').length) {
            // Keep a single Consult action when a native anchor is already present.
            $actionCell.find('.btn-opd-consult-open').remove();
        }

        if (canOpenPrescription && $actionCell.find('.btn-opd-consult-open').length === 0 && $consultAnchor.length === 0) {
            var $consultBtn = $('<button type="button" class="btn btn-outline-primary btn-sm btn-opd-consult-open">Consult</button>');
            $consultBtn.attr('data-opdid', opdId);
            $actionCell.append($consultBtn).append(' ');
        }

        if ($actionCell.find('.btn-opd-vitals').length === 0) {
            var $vitalsBtn = $('<button type="button" class="btn btn-warning text-dark btn-sm btn-opd-vitals" title="Vitals">Vitals</button>');
            $vitalsBtn.attr('data-opdid', opdId);
            $vitalsBtn.attr('data-patient', patient);
            $actionCell.append($vitalsBtn).append(' ');
        }

        if ($actionCell.find('.btn-opd-scan[title="Scan"]').length === 0) {
            $actionCell.append('<button type="button" class="btn btn-outline-info btn-sm btn-opd-scan" title="Scan" data-opdid="' + opdId + '">Scan</button> ');
        }

        if ($actionCell.find('.btn-opd-scan[title="Upload Scan"]').length === 0) {
            $actionCell.append('<button type="button" class="btn btn-outline-secondary btn-sm btn-opd-scan" title="Upload Scan" data-opdid="' + opdId + '">Upload</button> ');
        }

        if ($actionCell.find('.btn-opd-scan-list').length === 0) {
            $actionCell.append('<button type="button" class="btn btn-outline-dark btn-sm btn-opd-scan-list" title="Scan Doc List" data-opdid="' + opdId + '">Scan Doc List</button> ');
        }

        if ($actionCell.find('.btn-opd-status').length === 0) {
            $actionCell.append('<button type="button" class="btn btn-outline-success btn-sm btn-opd-status" data-opd-id="' + opdId + '" data-opd-status="2" title="Visit Done">Visit Done</button>');
        } else {
            $actionCell.find('.btn-opd-status').removeClass('btn-success').addClass('btn-outline-success').attr('data-opd-status', '2').attr('title', 'Visit Done').text('Visit Done');
        }
    }

    function applyFilters() {
        if (opdDataTable) {
            opdDataTable.draw();
        }
        syncWaitingHintAndBadge();
    }

    function reloadAppointmentList() {
        load_form('/Opd/get_appointment_list/<?= esc((int) ($doc_id ?? 0)) ?>/<?= esc($opd_date ?? date('Y-m-d')) ?>', 'OPD Appointment List');
    }

    function shouldSkipLiveCheck() {
        if (document.hidden) {
            return true;
        }

        if (isUserUpdateInProgress) {
            return true;
        }

        if ($('#tallModal').hasClass('show') || $('#vitalsModal').hasClass('show')) {
            return true;
        }

        return false;
    }

    function checkLiveUpdates() {
        if (!document.getElementById('opdAllTable')) {
            if (window.opdLiveMonitorTimer) {
                clearInterval(window.opdLiveMonitorTimer);
                window.opdLiveMonitorTimer = null;
            }
            return;
        }

        if (livePollInFlight || shouldSkipLiveCheck()) {
            return;
        }

        livePollInFlight = true;
        $.getJSON(liveDigestUrl, function(resp) {
            if (!resp || parseInt(resp.ok || 0, 10) !== 1) {
                return;
            }

            if (resp.payload) {
                applyLiveCounts(resp.payload);
            }

            if (!liveDigest) {
                liveDigest = String(resp.digest || '');
                // On first poll, reconcile stale server-rendered rows against fresh DB snapshot.
                syncLiveSnapshot(false);
                return;
            }

            if (liveDigest !== String(resp.digest || '')) {
                liveDigest = String(resp.digest || '');
                syncLiveSnapshot(true);
            }
        }).always(function() {
            livePollInFlight = false;
        });
    }

    function signalRefreshAvailable() {
        var $btn = $('#btn_manual_refresh');
        if (!$btn.hasClass('btn-warning')) {
            $btn.removeClass('btn-outline-secondary').addClass('btn-warning text-dark');
            $btn.html('&#x21BB; Refresh <span class="badge bg-danger ms-1">New</span>');
        }
    }

    function clearRefreshSignal() {
        var $btn = $('#btn_manual_refresh');
        $btn.removeClass('btn-warning text-dark').addClass('btn-outline-secondary');
        $btn.html('&#x21BB; Refresh');
    }

    function applyLiveCounts(counts) {
        if (!counts || typeof counts !== 'object') {
            return;
        }

        $('#countBadgeAll').text(String(parseInt(counts.all || counts.total || 0, 10)));
        $('#countBadgeBooked').text(String(parseInt(counts.booked || 0, 10)));
        $('#countBadgeWaiting').text(String(parseInt(counts.waiting || 0, 10)));
        $('#countBadgeVisited').text(String(parseInt(counts.visited || 0, 10)));
        $('#countBadgeCancelled').text(String(parseInt(counts.cancelled || 0, 10)));
    }

    function applyLiveRowsSnapshot(rows) {
        if (!Array.isArray(rows)) {
            return;
        }

        var liveById = {};
        rows.forEach(function(item) {
            var id = parseInt(item.opd_id || 0, 10);
            if (id > 0) {
                liveById[id] = item;
            }
        });

        $('#opdAllTable tbody tr[data-opd-id]').each(function() {
            var $row = $(this);
            var opdId = parseInt($row.attr('data-opd-id') || '0', 10);
            if (!opdId || !liveById[opdId]) {
                return;
            }

            var live = liveById[opdId];
            var status = String(live.status || '').toLowerCase();
            if (!status) {
                return;
            }

            $row.attr('data-opd-status', status);
            $row.attr('data-has-prescription', parseInt(live.has_prescription || 0, 10) === 1 ? '1' : '0');
            $row.attr('data-has-vitals', parseInt(live.has_vitals || 0, 10) === 1 ? '1' : '0');

            applyStatusVisual($row, status);
            updateQueueCell($row, parseInt(live.display_queue_no || live.queue_no || 0, 10));
            updateVitalsButtonState($row, parseInt(live.has_prescription || 0, 10), parseInt(live.has_vitals || 0, 10));

            if (status === 'waiting') {
                ensureWaitingActionButtons($row, opdId);
            }

            if (status === 'visited') {
                var $statusBtn = $row.find('.btn-opd-status').first();
                if ($statusBtn.length) {
                    $statusBtn.removeClass('btn-outline-success').addClass('btn-success');
                    $statusBtn.attr('data-opd-status', '2').attr('title', 'Visit Done').text('Visit Done').prop('disabled', false);
                }
            }
        });

        if (opdDataTable) {
            opdDataTable.rows().invalidate().draw(false);
        }

        syncWaitingHintAndBadge();
    }

    function syncLiveSnapshot(fallbackToRefreshSignal) {
        $.getJSON(liveRowsUrl, function(snapshot) {
            if (!snapshot || parseInt(snapshot.ok || 0, 10) !== 1) {
                if (fallbackToRefreshSignal) {
                    signalRefreshAvailable();
                }
                return;
            }

            if (snapshot.counts) {
                applyLiveCounts(snapshot.counts);
            }
            applyLiveRowsSnapshot(snapshot.rows || []);
            clearRefreshSignal();
        }).fail(function() {
            if (fallbackToRefreshSignal) {
                signalRefreshAvailable();
            }
        });
    }

    function updateQueueCell($row, queueNo) {
        var qNo = parseInt(queueNo || '0', 10);
        var $queueCell = $row.find('td').eq(3);
        $queueCell.attr('data-order', qNo > 0 ? qNo : 9999);
        $queueCell.text(qNo > 0 ? String(qNo) : '');
    }

    function updateVitalsButtonState($row, hasPrescription, hasVitals) {
        var $btn = $row.find('.btn-opd-vitals').first();
        if (!$btn.length || parseInt(hasPrescription || 0, 10) !== 1) {
            return;
        }

        if (parseInt(hasVitals || 0, 10) === 1) {
            $btn.text('Vitals ✓');
            $btn.removeClass('btn-warning text-dark btn-outline-warning').addClass('btn-success').attr('title', 'Vitals Filled');
        } else {
            $btn.text('Vitals');
            $btn.removeClass('btn-success').addClass('btn-warning text-dark').attr('title', 'Vitals');
        }
    }

    function startLiveUpdates() {
        if (window.opdLiveMonitorTimer) {
            clearInterval(window.opdLiveMonitorTimer);
            window.opdLiveMonitorTimer = null;
        }

        checkLiveUpdates();
        window.opdLiveMonitorTimer = setInterval(checkLiveUpdates, livePollMs);
    }

    function stopLiveUpdates() {
        if (window.opdLiveMonitorTimer) {
            clearInterval(window.opdLiveMonitorTimer);
            window.opdLiveMonitorTimer = null;
        }
    }

    function setVitalMessage(type, text) {
        var $msg = $('#vital_msg');
        $msg.removeClass('text-muted text-success text-danger');
        if (type === 'ok') {
            $msg.addClass('text-success');
        } else if (type === 'err') {
            $msg.addClass('text-danger');
        } else {
            $msg.addClass('text-muted');
        }
        $msg.text(text || '');
    }

    function fillVitals(vitals) {
        vitals = vitals || {};
        $('#vital_pulse').val(vitals.pulse || '');
        $('#vital_spo2').val(vitals.spo2 || '');
        $('#vital_bp').val(vitals.bp || '');
        $('#vital_diastolic').val(vitals.diastolic || '');
        $('#vital_temp').val(vitals.temp || '');
        $('#vital_rr_min').val(vitals.rr_min || '');
        $('#vital_height').val(vitals.height || '');
        $('#vital_weight').val(vitals.weight || '');
        $('#vital_waist').val(vitals.waist || '');
    }

    // --- Filter button clicks ---
    $(document).off('click.opdfilter', '.btn-opd-filter').on('click.opdfilter', '.btn-opd-filter', function() {
        activeStatusFilter = ($(this).data('filter') || '').toLowerCase();
        $('.btn-opd-filter').removeClass('active');
        $(this).addClass('active');

        // Clear "Only Missing Vitals" when switching away from waiting
        if (activeStatusFilter !== 'waiting' && activeStatusFilter !== '') {
            $('#toggle_missing_vitals_only').prop('checked', false);
        }

        applyFilters();
    });

    // Open consult page from dynamically created queue-transition buttons.
    $(document).off('click.opdconsultopen', '.btn-opd-consult-open').on('click.opdconsultopen', '.btn-opd-consult-open', function() {
        var opdId = parseInt($(this).data('opdid') || '0', 10);
        if (!opdId) {
            return;
        }
        load_form('/Opd_prescription/Prescription/' + opdId, 'Consult');
    });

    // Missing vitals badge click: switch to Waiting + enable toggle
    $(document).off('click.missingBadge', '#waitingMissingBadge').on('click.missingBadge', '#waitingMissingBadge', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if ($(this).hasClass('d-none')) {
            return;
        }
        activeStatusFilter = 'waiting';
        $('.btn-opd-filter').removeClass('active');
        $('.btn-opd-filter[data-filter="waiting"]').addClass('active');
        $('#toggle_missing_vitals_only').prop('checked', true);
        applyFilters();
    });

    // Missing vitals toggle
    $(document).off('change.missingVitals', '#toggle_missing_vitals_only').on('change.missingVitals', '#toggle_missing_vitals_only', function() {
        // Auto-switch to Waiting filter when enabling this toggle
        if ($(this).is(':checked') && activeStatusFilter !== 'waiting') {
            activeStatusFilter = 'waiting';
            $('.btn-opd-filter').removeClass('active');
            $('.btn-opd-filter[data-filter="waiting"]').addClass('active');
        }
        applyFilters();
    });

    // --- Scan button ---
    $(document).off('click.opdscanopen', '.btn-opd-scan').on('click.opdscanopen', '.btn-opd-scan', function() {
        var opdid = parseInt($(this).data('opdid') || '0', 10);
        if (!opdid || !modalObj) {
            return;
        }
        $('#testentryLabel').text('OPD Scan');
        $('#testentry-bodyc').html('<div class="text-muted">Loading...</div>');
        modalObj.show();
        $.post('/Opd/opd_load_doc/' + opdid, {}, function(html) {
            $('#testentry-bodyc').html(html || '<div class="text-danger">Unable to load scan panel.</div>');
        }).fail(function() {
            $('#testentry-bodyc').html('<div class="text-danger">Unable to load scan panel.</div>');
        });
    });

    // --- Scan Doc List button ---
    $(document).off('click.opdscanlistopen', '.btn-opd-scan-list').on('click.opdscanlistopen', '.btn-opd-scan-list', function() {
        var opdid = parseInt($(this).data('opdid') || '0', 10);
        if (!opdid || !modalObj) {
            return;
        }
        $('#testentryLabel').text('Scan Document List');
        $('#testentry-bodyc').html('<div class="text-muted">Loading...</div>');
        modalObj.show();
        $.post('/Opd/opd_file_list/' + opdid, {}, function(html) {
            $('#testentry-bodyc').html(html || '<div class="text-danger">No scan documents found.</div>');
        }).fail(function() {
            $('#testentry-bodyc').html('<div class="text-danger">Unable to load scan document list.</div>');
        });
    });

    window.deleteOpdScanFromList = function(fileId, opdid) {
        if (!fileId || !opdid) {
            return;
        }
        if (!window.confirm('Delete this scan document?')) {
            return;
        }
        var csrf = getCsrfPair();
        var payload = {};
        payload[csrf.name] = csrf.value;
        $.post('/Opd/opd_file_delete/' + fileId, payload, function(resp) {
            if (resp && resp.csrfName && resp.csrfHash) {
                updateCsrf(resp);
            }
            if (!resp || parseInt(resp.update || 0, 10) !== 1) {
                var msg = (resp && resp.error_text) ? resp.error_text : 'Unable to delete document.';
                $('#testentry-bodyc').prepend('<div class="alert alert-danger py-1 mb-2">' + $('<div>').text(msg).html() + '</div>');
                return;
            }
            $.post('/Opd/opd_file_list/' + opdid, {}, function(html) {
                $('#testentry-bodyc').html(html || '<div class="text-muted">No scan documents found.</div>');
            }).fail(function() {
                $('#testentry-bodyc').prepend('<div class="alert alert-danger py-1 mb-2">Unable to refresh scan list.</div>');
            });
        }, 'json').fail(function() {
            $('#testentry-bodyc').prepend('<div class="alert alert-danger py-1 mb-2">Unable to delete document.</div>');
        });
    };

    // --- Visit Done / Status buttons ---
    $(document).off('click.opdstatus', '.btn-opd-status').on('click.opdstatus', '.btn-opd-status', function() {
        var $btn = $(this);
        var opdId = parseInt($btn.data('opd-id') || '0', 10);
        var opdStatus = parseInt($btn.data('opd-status') || '0', 10);
        if (!opdId || !opdStatus) {
            return;
        }

        var $row = $('#opdAllTable tbody tr[data-opd-id="' + opdId + '"]');
        if (!$row.length) {
            return;
        }

        var previousLabel = ($btn.text() || '').trim() || 'Visit Done';
        isUserUpdateInProgress = true;
        $btn.prop('disabled', true).text('Updating...');

        $.post('/Opd/opd_status/' + opdId + '/' + opdStatus, {}, function(resp) {
            if (!resp || parseInt(resp.update || 0, 10) !== 1) {
                window.alert((resp && resp.message) ? resp.message : 'Unable to update status');
                $btn.prop('disabled', false).text(previousLabel);
                isUserUpdateInProgress = false;
                return;
            }

            var nextStatus = 'booked';
            if (parseInt(resp.opd_status || 0, 10) === 2) {
                nextStatus = 'visited';
            } else if (parseInt(resp.opd_status || 0, 10) === 3) {
                nextStatus = 'cancelled';
            }

            $row.attr('data-opd-status', nextStatus);
            applyStatusVisual($row, nextStatus);

            // Keep row action state consistent after transition to visited.
            if (nextStatus === 'visited') {
                $btn.removeClass('btn-outline-success').addClass('btn-success').text('Visit Done');
                $btn.prop('disabled', false);
            } else {
                $btn.prop('disabled', false).text(previousLabel);
            }

            if (opdDataTable) {
                opdDataTable.row($row).invalidate();
            }

            recalcStatusCounts();
            applyFilters();
            isUserUpdateInProgress = false;
        }, 'json').fail(function() {
            window.alert('Unable to update status');
            $btn.prop('disabled', false).text(previousLabel);
            isUserUpdateInProgress = false;
        });
    });

    // --- Create Queue button ---
    $(document).off('click.opdcreatequeue', '.btn-opd-create-queue').on('click.opdcreatequeue', '.btn-opd-create-queue', function() {
        var opdId = parseInt($(this).data('opdid') || '0', 10);
        if (!opdId) {
            return;
        }

        var $row = $('#opdAllTable tbody tr[data-opd-id="' + opdId + '"]');
        if (!$row.length) {
            return;
        }

        var $btn = $(this);
        var previousText = ($btn.text() || '').trim() || 'Queue';
        isUserUpdateInProgress = true;
        $btn.prop('disabled', true).text('Queueing...');

        $.post('/Opd_prescription/create_opd_queue/' + opdId, {}, function(resp) {
            var message = (resp || '').toString().trim();
            if (message !== 'Queue Created') {
                window.alert(message || 'Unable to create queue');
                $btn.prop('disabled', false).text(previousText);
                isUserUpdateInProgress = false;
                return;
            }

            // Queue creation moves booked/confirmed patient to waiting workflow without reloading page.
            $row.attr('data-opd-status', 'waiting');
            $row.attr('data-has-prescription', '1');
            applyStatusVisual($row, 'waiting');
            ensureWaitingActionButtons($row, opdId);

            if (opdDataTable) {
                opdDataTable.row($row).invalidate();
            }

            recalcStatusCounts();
            applyFilters();
            isUserUpdateInProgress = false;
        }).fail(function(xhr) {
            window.alert((xhr && xhr.responseText) ? xhr.responseText : 'Unable to create queue');
            $btn.prop('disabled', false).text(previousText);
            isUserUpdateInProgress = false;
        });
    });

    // --- Vitals modal open ---
    $(document).off('click.opdvitalsopen', '.btn-opd-vitals').on('click.opdvitalsopen', '.btn-opd-vitals', function() {
        var opdid = parseInt($(this).data('opdid') || '0', 10);
        var patient = ($(this).data('patient') || '').toString();
        if (!opdid || !vitalsModalObj) {
            return;
        }
        $('#vital_opd_id').val(opdid);
        $('#vital_opd_session_id').val('0');
        $('#vital_patient_label').text('Patient: ' + (patient || '-'));
        fillVitals({});
        setVitalMessage('normal', 'Loading previous vitals...');
        vitalsModalObj.show();
        $.getJSON('/Opd_prescription/vitals_get/' + opdid, function(data) {
            updateCsrf(data);
            if (parseInt(data.update || 0, 10) !== 1) {
                setVitalMessage('err', data.error_text || 'Unable to load vitals');
                return;
            }
            $('#vital_opd_session_id').val(parseInt(data.opd_session_id || '0', 10));
            fillVitals(data.vitals || {});
            setVitalMessage('normal', 'Vitals loaded.');
        }).fail(function() {
            setVitalMessage('err', 'Unable to load vitals');
        });
    });

    // --- Vitals save ---
    $('#btn_save_vitals').on('click', function() {
        var opdId = parseInt($('#vital_opd_id').val() || '0', 10);
        if (opdId <= 0) {
            setVitalMessage('err', 'Invalid OPD selected');
            return;
        }
        var csrf = getCsrfPair();
        var payload = {
            opd_id: opdId,
            opd_session_id: parseInt($('#vital_opd_session_id').val() || '0', 10),
            pulse:     ($('#vital_pulse').val()     || '').trim(),
            spo2:      ($('#vital_spo2').val()      || '').trim(),
            bp:        ($('#vital_bp').val()        || '').trim(),
            diastolic: ($('#vital_diastolic').val() || '').trim(),
            temp:      ($('#vital_temp').val()      || '').trim(),
            rr_min:    ($('#vital_rr_min').val()    || '').trim(),
            height:    ($('#vital_height').val()    || '').trim(),
            weight:    ($('#vital_weight').val()    || '').trim(),
            waist:     ($('#vital_waist').val()     || '').trim()
        };
        payload[csrf.name] = csrf.value;

        var $btn = $(this);
        isUserUpdateInProgress = true;
        $btn.prop('disabled', true).text('Saving...');
        $.post('/Opd_prescription/vitals_save', payload, function(data) {
            updateCsrf(data);
            $btn.prop('disabled', false).text('Save Vitals');
            if (parseInt(data.update || 0, 10) !== 1) {
                setVitalMessage('err', data.error_text || 'Unable to save vitals');
                isUserUpdateInProgress = false;
                return;
            }
            $('#vital_opd_session_id').val(parseInt(data.opd_session_id || '0', 10));
            setVitalMessage('ok', data.error_text || 'Vitals saved successfully');

            // Update row in unified table (not tab-specific)
            var $row = $('#opdAllTable tbody tr[data-opd-id="' + opdId + '"]');
            if ($row.length) {
                $row.attr('data-has-vitals', '1');
                var $vitalBtn = $row.find('.btn-opd-vitals').first();
                if ($vitalBtn.length) {
                    if (($vitalBtn.text() || '').trim().indexOf('✓') === -1) {
                        $vitalBtn.text('Vitals ✓');
                    }
                    $vitalBtn.removeClass('btn-warning text-dark btn-outline-warning').addClass('btn-success');
                    $vitalBtn.attr('title', 'Vitals Filled');
                }
                if (opdDataTable) {
                    opdDataTable.rows().invalidate().draw(false);
                }
                syncWaitingHintAndBadge();
            }
            isUserUpdateInProgress = false;
        }, 'json').fail(function() {
            $btn.prop('disabled', false).text('Save Vitals');
            setVitalMessage('err', 'Unable to save vitals');
            isUserUpdateInProgress = false;
        });
    });

    // Manual refresh button
    $(document).off('click.opdrefresh', '#btn_manual_refresh').on('click.opdrefresh', '#btn_manual_refresh', function() {
        activeStatusFilter = 'waiting';
        $('#toggle_missing_vitals_only').prop('checked', false);
        $('.btn-opd-filter').removeClass('active');
        $('.btn-opd-filter[data-filter="waiting"]').addClass('active');
        clearRefreshSignal();
        reloadAppointmentList();
    });

    $(window).off('beforeunload.opdlive').on('beforeunload.opdlive', function() {
        stopLiveUpdates();
    });

    // Initialize
    registerOpdTableFilter();
    opdDataTable = initializeOpdTable();
    recalcStatusCounts();
    applyFilters();
    syncWaitingHintAndBadge();
    startLiveUpdates(); // lightweight digest poll — shows "Refresh" badge instead of auto-reloading
})();
</script>

