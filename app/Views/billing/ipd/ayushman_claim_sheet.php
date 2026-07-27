<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$items = $checklist_items ?? [];
$generatedAt = $generated_at ?? date('d-m-Y H:i:s');
$isExport = (int) ($output ?? 0) === 1;
$patientId = (int) (($person->id ?? null) ?: ($ipd->p_id ?? 0));
$abhaNumber = trim((string) (($person->abha_id ?? '') ?: ($person->abha_no ?? '') ?: ($person->abha ?? '')));
$abhaAddress = trim((string) ($person->abha_address ?? ''));
$abhaVerifiedRaw = strtolower(trim((string) (($person->abha_verified_status ?? '') ?: ($person->abha_status ?? '') ?: ($person->abha_verified ?? ''))));
$abhaLooksVerified = in_array($abhaVerifiedRaw, ['1', 'verified', 'yes', 'y', 'true'], true);
$canUseM3 = $patientId > 0 && $abhaAddress !== '' && $abhaLooksVerified;
?>

<section class="content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Ayushman Claim Sheet</h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-4"><strong>Patient:</strong> <?= esc($person->p_fname ?? '') ?></div>
                <div class="col-md-2"><strong>UHID:</strong> <?= esc($person->p_code ?? '') ?></div>
                <div class="col-md-2"><strong>IPD:</strong> <?= esc($ipd->ipd_code ?? '') ?></div>
                <div class="col-md-4"><strong>Case:</strong> <?= esc($ipd->case_id_code ?? '') ?></div>
                <div class="col-md-4"><strong>Insurance:</strong> <?= esc($ipd->ins_company_name ?? '') ?></div>
                <div class="col-md-4"><strong>Scheme:</strong> <?= esc($ipd->org_insurance_comp ?? '') ?></div>
                <div class="col-md-4"><strong>Generated:</strong> <?= esc($generatedAt) ?></div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-3"><strong>Preauth Sent:</strong> <?= (int) ($ipd->preauth_send ?? 0) === 1 ? 'Yes' : 'No' ?></div>
                <div class="col-md-3"><strong>Documents Received:</strong> <?= (int) ($ipd->doc_recd ?? 0) === 1 ? 'Yes' : 'No' ?></div>
                <div class="col-md-3"><strong>Final Bill Sent:</strong> <?= (int) ($ipd->final_bill_send ?? 0) === 1 ? 'Yes' : 'No' ?></div>
                <div class="col-md-3"><strong>Approval Status:</strong> <?= esc((string) ($ipd->org_approved_status ?? 'Under Process')) ?></div>
                <div class="col-md-12"><strong>Remark:</strong> <?= esc((string) ($ipd->remark ?? '')) ?></div>
            </div>

            <?php if (! $isExport) : ?>
                <div class="card border-info mb-3 d-print-none" id="ayushmanAbdmM3Panel">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">ABDM M3 Related Data</div>
                            <div class="small text-muted">
                                ABHA Number: <strong><?= $abhaNumber !== '' ? esc($abhaNumber) : 'Not available' ?></strong>
                                &nbsp;|&nbsp; ABHA Address: <strong><?= $abhaAddress !== '' ? esc($abhaAddress) : 'Not available' ?></strong>
                                &nbsp;|&nbsp; Status: <?= $abhaLooksVerified ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-secondary">Not verified in HMS</span>' ?>
                                &nbsp;|&nbsp; Last Consent: <span class="badge bg-secondary" id="ayushmanLastConsentBadge">Unknown</span>
                                <span
                                    class="ms-1 text-muted"
                                    role="button"
                                    tabindex="0"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Completed: data fetched. Granted: approved. Pending: waiting patient action. Failed: previous request failed. The system will restart automatically when needed."
                                    aria-label="Consent status help"
                                >?</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAyushmanLoadAbdmDocs" <?= $patientId > 0 ? '' : 'disabled' ?>>Load Fetched Data</button>
                            <button type="button" class="btn btn-sm btn-success" id="btnAyushmanFetchAbdmM3" <?= $canUseM3 ? '' : 'disabled' ?>>Fetch From ABDM M3</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="load_form('<?= site_url('AbdmHiu') ?>','ABDM HIU M3')">Open M3 Console</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-2" id="ayushmanAbdmStatus">
                            <?php if ($canUseM3) : ?>
                                Use Fetch From ABDM M3 to request consent and poll approved records, or load already fetched documents.
                            <?php elseif ($abhaAddress === '') : ?>
                                ABHA address is required before M3 fetch can be started.
                            <?php else : ?>
                                Verified ABHA status is required before M3 fetch can be started.
                            <?php endif; ?>
                        </div>
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control" id="ayushmanAbdmSearch" placeholder="Search title, care context, doctor">
                                    <button class="btn btn-outline-secondary" type="button" id="btnAyushmanSearchAbdmDocs">Search</button>
                                </div>
                                <div class="table-responsive border rounded" style="max-height: 260px;">
                                    <table class="table table-sm table-hover mb-0" id="ayushmanAbdmDocTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Title</th>
                                                <th>Care Context</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="3" class="text-muted text-center">No ABDM records loaded.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="border rounded p-3 bg-light h-100" id="ayushmanAbdmDetailBox">
                                    <div class="text-muted">Select a fetched ABDM document to view claim-supporting clinical summary.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($items)) : ?>
                <div class="alert alert-warning">No Ayushman procedures linked to this IPD package list.</div>
            <?php else : ?>
                <table class="table table-bordered table-sm table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 140px;">Procedure Code</th>
                            <th>Procedure Name</th>
                            <th style="width: 120px;" class="text-end">Amount</th>
                            <th style="width: 90px;">Preauth</th>
                            <th>Pre Investigations</th>
                            <th>Post Investigations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item) : ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= esc((string) ($item['procedure_code'] ?? '')) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($item['procedure_name'] ?? '')) ?></div>
                                    <div class="small text-muted"><?= esc((string) (($item['speciality_name'] ?? '') . ' [' . ($item['speciality_code'] ?? '') . ']')) ?></div>
                                </td>
                                <td class="text-end"><?= number_format((float) ($item['package_Amount'] ?? 0), 2) ?></td>
                                <td><?= (int) ($item['preauth_required'] ?? 0) === 1 ? 'Required' : 'No' ?></td>
                                <td><?= esc((string) ($item['pre_investigations'] ?? '')) ?></td>
                                <td><?= esc((string) ($item['post_investigations'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (! $isExport) : ?>
<script>
(function () {
    var patientId = <?= (int) $patientId ?>;
    var docsUrl = '<?= site_url('billing/patient/abdm_documents/' . (int) $patientId) ?>';
    var detailBaseUrl = '<?= site_url('billing/patient/abdm_document_detail/' . (int) $patientId) ?>';
    var autoFlowUrl = '<?= site_url('billing/patient/abdm_content_auto_flow/' . (int) $patientId) ?>';
    var currentRequestId = '';
    var pollTimer = null;
    var pollAttempts = 0;
    var maxPollAttempts = 15;
    var needsFreshRequest = false;

    function updateConsentBadge(phase, needsFresh) {
        var text = (phase || '').toString().toUpperCase();
        var $badge = $('#ayushmanLastConsentBadge');
        if (!$badge.length) {
            return;
        }

        $badge.removeClass('bg-secondary bg-success bg-info bg-warning bg-danger');
        if (needsFresh) {
            $badge.addClass('bg-danger').text('Failed - New Required');
            return;
        }

        if (text === 'COMPLETED') {
            $badge.addClass('bg-success').text('Completed');
        } else if (text === 'GRANTED') {
            $badge.addClass('bg-info').text('Granted');
        } else if (text === 'REQUESTED' || text === 'PENDING') {
            $badge.addClass('bg-warning').text('Pending');
        } else if (text === 'FAILED' || text === 'DENIED') {
            $badge.addClass('bg-danger').text('Failed');
        } else {
            $badge.addClass('bg-secondary').text('Unknown');
        }
    }

    function applyConsentButtons() {
        $('#btnAyushmanFetchAbdmM3').text(pollTimer ? 'Fetching...' : 'Fetch From ABDM M3');
    }

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function setStatus(message, isError) {
        $('#ayushmanAbdmStatus')
            .toggleClass('text-danger', !!isError)
            .toggleClass('text-muted', !isError)
            .text(message || '');
    }

    function formatDate(value) {
        var text = (value || '').toString().trim();
        return text !== '' ? text.replace('T', ' ') : '-';
    }

    function renderRows(rows) {
        var html = '';
        if (!rows || !rows.length) {
            $('#ayushmanAbdmDocTable tbody').html('<tr><td colspan="3" class="text-muted text-center">No ABDM fetched records found.</td></tr>');
            $('#ayushmanAbdmDetailBox').html('<div class="text-muted">No fetched ABDM documents are mapped to this patient yet.</div>');
            return;
        }

        rows.forEach(function (row) {
            html += '<tr class="ayushman-abdm-doc-row" data-id="' + esc(row.id) + '">' +
                '<td>' + esc(formatDate(row.document_date || row.created_at)) + '</td>' +
                '<td>' + esc(row.document_title || '-') + '</td>' +
                '<td>' + esc(row.care_context_reference || '-') + '</td>' +
            '</tr>';
        });
        $('#ayushmanAbdmDocTable tbody').html(html);
    }

    function renderDetail(item) {
        if (!item) {
            $('#ayushmanAbdmDetailBox').html('<div class="text-muted">Document detail unavailable.</div>');
            return;
        }

        var summary = item.summary || {};
        var conditions = Array.isArray(summary.conditions) ? summary.conditions : [];
        var vitals = Array.isArray(summary.vitals) ? summary.vitals : [];
        var meds = Array.isArray(summary.medications) ? summary.medications : [];
        var html = '';
        html += '<div class="fw-semibold mb-1">' + esc(item.document_title || 'ABDM Document') + '</div>';
        html += '<div class="small text-muted mb-2">' + esc(formatDate(item.document_date || item.created_at)) + ' | ' + esc(item.bundle_type || '-') + '</div>';
        html += '<div class="small mb-2">Care Context: <strong>' + esc(item.care_context_reference || '-') + '</strong></div>';
        html += '<div class="small mb-3">Doctor: <strong>' + esc(item.practitioner_name || '-') + '</strong> | Organization: <strong>' + esc(item.organization_name || '-') + '</strong></div>';
        html += '<div class="row g-2">';
        html += renderList('Diagnoses', conditions, function (row) { return row && row.text ? row.text : '-'; });
        html += renderList('Vitals', vitals, function (row) { return ((row && row.name) || '-') + ': ' + ((row && row.value) || '-'); });
        html += renderList('Medications', meds, function (row) { return ((row && row.name) || '-') + ((row && row.dose) ? (' | ' + row.dose) : ''); });
        html += '</div>';
        $('#ayushmanAbdmDetailBox').html(html);
    }

    function renderList(title, rows, formatter) {
        var list = Array.isArray(rows) ? rows : [];
        var html = '<div class="col-md-4"><div class="border rounded p-2 h-100 bg-white"><div class="fw-semibold small mb-1">' + esc(title) + '</div><ul class="mb-0 small ps-3">';
        if (!list.length) {
            html += '<li class="text-muted">No data</li>';
        } else {
            list.forEach(function (row) {
                html += '<li>' + esc(formatter(row)) + '</li>';
            });
        }
        html += '</ul></div></div>';
        return html;
    }

    function loadDocs() {
        if (patientId <= 0) {
            setStatus('Patient ID not available for ABDM document lookup.', true);
            return;
        }

        var q = ($('#ayushmanAbdmSearch').val() || '').toString().trim();
        var url = docsUrl + '?limit=100';
        if (q !== '') {
            url += '&q=' + encodeURIComponent(q);
        }
        setStatus('Loading fetched ABDM records...', false);

        fetch(url, { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || parseInt(data.ok || 0, 10) !== 1) {
                    throw new Error((data && data.error) || 'Unable to load ABDM records.');
                }
                var rows = Array.isArray(data.items) ? data.items : [];
                renderRows(rows);

                var lastSync = data.last_sync || null;
                if (lastSync) {
                    var lastPhase = (lastSync.phase || '').toString().toUpperCase();
                    needsFreshRequest = !!lastSync.restart_required;
                    if (!needsFreshRequest && (lastSync.request_id || '').toString().trim() !== '') {
                        currentRequestId = (lastSync.request_id || '').toString().trim();
                    }
                    updateConsentBadge(lastPhase, needsFreshRequest);
                    applyConsentButtons();
                }

                setStatus('Loaded ' + rows.length + ' fetched ABDM record(s).', false);
            })
            .catch(function (error) {
                setStatus('ABDM load failed: ' + (error.message || error), true);
            });
    }

    function loadDetail(docId) {
        if (!docId) {
            return;
        }
        $('#ayushmanAbdmDetailBox').html('<div class="text-muted">Loading document detail...</div>');
        fetch(detailBaseUrl + '/' + encodeURIComponent(docId), { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || parseInt(data.ok || 0, 10) !== 1) {
                    throw new Error((data && data.error) || 'Unable to load document detail.');
                }
                renderDetail(data.item || null);
            })
            .catch(function (error) {
                $('#ayushmanAbdmDetailBox').html('<div class="text-danger">' + esc('Detail load failed: ' + (error.message || error)) + '</div>');
            });
    }

    function stopPolling() {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
    }

    function runM3AutoFlow() {
        var url = autoFlowUrl;
        if (currentRequestId !== '') {
            url += '?request_id=' + encodeURIComponent(currentRequestId);
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || parseInt(data.ok || 0, 10) !== 1) {
                    throw new Error((data && data.error) || 'M3 fetch failed.');
                }

                currentRequestId = (data.request_id || currentRequestId || '').toString();
                var phase = (data.phase || '').toString();
                var message = (data.message || ('Phase: ' + (phase || 'UNKNOWN'))).toString();
                var resetRequestId = parseInt(data.reset_request_id || 0, 10) === 1;
                setStatus(message + (currentRequestId ? (' | Request ID: ' + currentRequestId) : ''), false);
                updateConsentBadge(phase, needsFreshRequest);

                if (resetRequestId) {
                    currentRequestId = '';
                    updateConsentBadge('FAILED', false);
                    setStatus('Previous consent request is stale/failed. HMS is starting a fresh request automatically.', false);
                    if (pollAttempts < maxPollAttempts) {
                        pollAttempts += 1;
                        pollTimer = setTimeout(runM3AutoFlow, 1200);
                        return;
                    }
                    stopPolling();
                    return;
                }

                if (parseInt(data.poll_again || 0, 10) === 1 && pollAttempts < maxPollAttempts) {
                    pollAttempts += 1;
                    pollTimer = setTimeout(runM3AutoFlow, 8000);
                    return;
                }

                $('#btnAyushmanFetchAbdmM3').prop('disabled', false);
                applyConsentButtons();
                if (phase === 'COMPLETED') {
                    loadDocs();
                }
            })
            .catch(function (error) {
                stopPolling();
                $('#btnAyushmanFetchAbdmM3').prop('disabled', false);
                applyConsentButtons();
                setStatus('M3 fetch failed: ' + (error.message || error), true);
            });
    }

    $(document).off('click.ayushmanAbdm', '#btnAyushmanLoadAbdmDocs').on('click.ayushmanAbdm', '#btnAyushmanLoadAbdmDocs', loadDocs);
    $(document).off('click.ayushmanAbdm', '#btnAyushmanSearchAbdmDocs').on('click.ayushmanAbdm', '#btnAyushmanSearchAbdmDocs', loadDocs);
    $(document).off('keypress.ayushmanAbdm', '#ayushmanAbdmSearch').on('keypress.ayushmanAbdm', '#ayushmanAbdmSearch', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            loadDocs();
        }
    });
    $(document).off('click.ayushmanAbdm', '#ayushmanAbdmDocTable .ayushman-abdm-doc-row').on('click.ayushmanAbdm', '#ayushmanAbdmDocTable .ayushman-abdm-doc-row', function () {
        $('#ayushmanAbdmDocTable .ayushman-abdm-doc-row').removeClass('table-active');
        $(this).addClass('table-active');
        loadDetail($(this).data('id'));
    });
    $(document).off('click.ayushmanAbdm', '#btnAyushmanFetchAbdmM3').on('click.ayushmanAbdm', '#btnAyushmanFetchAbdmM3', function () {
        var $button = $(this);
        if ($button.prop('disabled')) {
            return;
        }
        stopPolling();
        currentRequestId = '';
        pollAttempts = 0;
        $button.prop('disabled', true).text('Fetching...');
        setStatus('Starting ABDM M3 consent and data fetch flow...', false);
        runM3AutoFlow();
    });

    $('[data-bs-toggle="tooltip"]').tooltip();

    applyConsentButtons();
    updateConsentBadge('IDLE', false);
})();
</script>
<?php endif; ?>
