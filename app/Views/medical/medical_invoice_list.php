<?php
    if (!function_exists('get_age_1')) {
        helper('age');
    }

    $ipdId = (int) ($ipd->id ?? 0);
    $ipdCode = (string) ($ipd->ipd_code ?? ('IPD-' . $ipdId));
    $patientName = (string) ($patient->p_fname ?? '-');
    $patientRelative = (string) ($patient->p_rname ?? '');
    $patientCode = (string) ($patient->p_code ?? '-');
    $age = '';
    if (!empty($patient)) {
        $age = get_age_1(
            $patient->dob ?? null,
            $patient->age ?? '',
            $patient->age_in_month ?? '',
            $patient->estimate_dob ?? '',
            $ipd->register_date ?? null
        );
    }
    $genderValue = (string) ($patient->xgender ?? ($patient->gender ?? ''));
    if ($genderValue === '1') {
        $genderValue = 'Male';
    } elseif ($genderValue === '0' || $genderValue === '2') {
        $genderValue = 'Female';
    }

    $admitDate = '';
    if (!empty($ipd->register_date)) {
        $ts = strtotime((string) $ipd->register_date);
        $admitDate = $ts ? date('d-m-Y H:i', $ts) : (string) $ipd->register_date;
    }

    $dischargeDate = '';
    if (!empty($ipd->discharge_date)) {
        $ts = strtotime((string) $ipd->discharge_date);
        $dischargeDate = $ts ? date('d-m-Y H:i', $ts) : (string) $ipd->discharge_date;
    }

    $noDays = 0;
    if (!empty($ipd->register_date)) {
        $noDays = max(1, (int) floor((time() - strtotime((string) $ipd->register_date)) / 86400));
    }

    $docName = (string) ($ipd->doc_name ?? '-');
    $status = ((int) ($ipd->ipd_status ?? 0) === 0) ? 'Admit' : 'Discharge';
    $lockMedical = (int) ($ipd->lock_medical ?? 0);
?>

<div class="card border-0">
    <div  class="card-header bg-light border-bottom border-danger border-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
        <h4 class="mb-0"><strong>IPD Code :</strong> <?= esc($ipdCode) ?></h4>
        
        <div class="d-flex gap-2 flex-wrap mb-3">
            <a class="btn btn-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/Invoice_counter_new/' . (int)($patient->id ?? 0) . '/' . $ipdId . '/0') ?>','medical-main');">Add New</a>
            <button class="btn btn-primary btn-sm" type="button" id="btn_lock_ipd"><?= $lockMedical > 0 ? 'Unlock' : 'Lock IPD for Final' ?></button>
            <a class="btn btn-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/list_med_inv/' . $ipdId) ?>','medical-main');">Refresh</a>

        </div>
    </div>

    <div class="card-body">
        <div class="js-permission-alert"></div>
        <div class="mb-2 fw-semibold">
            Name : <?= esc($patientName) ?> <?= $patientRelative !== '' ? '{' . esc($patientRelative) . '}' : '' ?>
            / Age : <?= esc($age !== '' ? $age : '-') ?>
            / Gender : <?= esc($genderValue !== '' ? $genderValue : '-') ?>
            / P Code : <?= esc($patientCode) ?>
        </div>

        <div class="mb-3">
            <strong>IPD Code :</strong> <?= esc($ipdCode) ?>
            <strong> Admit Date :</strong> <?= esc($admitDate !== '' ? $admitDate : '-') ?>
            <strong> / Discharge Date :</strong> <?= esc($dischargeDate !== '' ? $dischargeDate : '-') ?>
            <strong> / No. of Days :</strong> <?= esc((string) $noDays) ?>
            <strong> / Status :</strong> <?= esc($status) ?>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-2">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invoice ID.</th>
                        <th>Inv.Date</th>
                        <th>Inv.Desc</th>
                        <th>Credit/Cash</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $i => $row): ?>
                            <?php
                                $invoiceId = (int) ($row->id ?? 0);
                                $ipdCredit = (int)($row->ipd_credit ?? 0);
                                $groupInvoiceId = (int)($row->group_invoice_id ?? 0);
                                $creditText = ($ipdCredit > 0) ? 'Credit' : 'CASH';
                                $packageText = ($ipdCredit > 0 && $groupInvoiceId > 0) ? 'Yes' : 'No';
                                $editMeta = $invoiceEditMeta[$invoiceId] ?? ['can_edit' => true, 'reason' => ''];
                                $canEditRow = !empty($editMeta['can_edit']);
                                $editReason = (string) ($editMeta['reason'] ?? 'Invoice is view-only.');
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= esc($row->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT))) ?></strong></td>
                                <td><?= esc(!empty($row->inv_date) ? date('Y-m-d', strtotime((string)$row->inv_date)) : '-') ?></td>
                                <td><?= esc($row->remark_ipd ?? '-') ?></td>
                                <td><?= esc($creditText) ?></td>
                                <td><?= esc($packageText) ?></td>
                                <td><?= esc(number_format((float)($row->net_amount ?? 0), 2)) ?></td>
                                <td>
                                    <?php if ($canEditRow): ?>
                                        <a class="btn btn-sm btn-primary" href="javascript:load_form_div('<?= base_url('Medical/open_invoice_edit/' . $invoiceId) ?>','medical-main');">Edit</a>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-primary" href="javascript:void(0);" title="<?= esc($editReason) ?>" onclick='return showMedicalPermissionAlert(this, <?= json_encode($editReason) ?>);'>Edit</a>
                                    <?php endif; ?>
                                    <?php if ($canEditRow): ?>
                                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= base_url('Medical/invoice_print/' . $invoiceId) ?>">PDF</a>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-outline-secondary" href="javascript:void(0);" title="<?= esc($editReason) ?>" onclick='return showMedicalPermissionAlert(this, <?= json_encode($editReason) ?>);'>PDF</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No invoices found for this IPD.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6"><i>Total Cash : </i><?= esc(number_format((float)($cashTotal ?? 0), 2)) ?> 
                                        
                                        / <i>Total Balance : </i><b><?= esc(number_format((float)($balanceTotal ?? 0), 2)) ?></b>
                                        / <i>Total CASH Discount : </i><b><?= esc(number_format((float)($discountTotal ?? 0), 2)) ?></b>

                        </th>
                        <th colspan="2">
                             Total Credit : <b><?= esc(number_format((float)($creditTotal ?? 0), 2)) ?></b>
                                        / Total Package : <b><?= esc(number_format((float)($packageTotal ?? 0), 2)) ?></b>
                                        / Total Amount : <b><?= esc(number_format((float)($amountTotal ?? 0), 2)) ?></b>
                        </th>
                    </tr>
                   
                </tfoot>
            </table>
        </div>

        <div class="table-responsive mb-2">
            <table class="table table-sm table-striped align-middle mb-0">
                <tr>
                    <td><a class="btn btn-warning btn-sm" href="javascript:load_form_div('<?= base_url('Medical/med_return/' . $ipdId) ?>','medical-main');">Medicine Reurn</a></td>
                    <td><a class="btn btn-success btn-sm" href="javascript:load_form_div('<?= base_url('Medical/med_return_new/' . $ipdId) ?>','medical-main');">Medicine Reurn New</a></td>
                    <td></td>
                </tr>
                <tr>
                    <td><a class="btn btn-danger btn-sm" href="javascript:load_form_div('<?= base_url('Medical/med_cash_payment/' . $ipdId) ?>','medical-main');">Payment Add</a></td>
                    <td>
                        <input class="form-control form-control-sm" id="input_discount" placeholder="Discount Amount" type="number" step="0.01" value="<?= esc(number_format((float)($currentDiscount ?? 0), 2, '.', '')) ?>">
                    </td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm" id="btn_update_group_discount">Update Discount</button>
                    </td>
                </tr>
            </table>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/cash') ?>">Print Cash</a>
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/cash-return') ?>">Print Cash With Return</a>
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/credit') ?>">Print IPD Credit</a>
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/package') ?>">Print IPD Package</a>
            <a class="btn btn-danger btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/med-list') ?>">Print Consolidated Medicine List</a>
            <a class="btn btn-danger btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/med-list-date') ?>">Print Medicine List Datewise</a>
            <a class="btn btn-danger btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/pagewise') ?>">Print Page Wise</a>
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/return-list') ?>">Print Return List</a>
            <a class="btn btn-outline-secondary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/Invoice_Med_Draft?status=all&q=' . rawurlencode($ipdCode)) ?>','medical-main');">Open Draft List</a>
        </div>
    </div>
</div>

<div class="d-none" id="medical-ipd-list-csrf"><?= csrf_field() ?></div>

<script>
    if (typeof window.showMedicalPermissionAlert !== 'function') {
        window.showMedicalPermissionAlert = function (trigger, message) {
            var text = String(message || 'No permission to edit this invoice.');
            var root = trigger && trigger.closest ? trigger.closest('.card') : null;
            var host = root && root.querySelector ? root.querySelector('.js-permission-alert') : null;

            if (!host) {
                alert(text);
                return false;
            }

            host.innerHTML = '';

            var box = document.createElement('div');
            box.className = 'alert alert-warning alert-dismissible fade show py-2 mb-3';
            box.setAttribute('role', 'alert');

            var textNode = document.createElement('span');
            textNode.textContent = text;
            box.appendChild(textNode);

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('aria-label', 'Close');
            closeBtn.addEventListener('click', function () {
                if (box.parentNode) {
                    box.parentNode.removeChild(box);
                }
            });
            box.appendChild(closeBtn);

            host.appendChild(box);
            if (typeof host.scrollIntoView === 'function') {
                host.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            return false;
        };
    }

    (function () {
        var ipdId = <?= (int) $ipdId ?>;

        function refreshList() {
            if (typeof load_form_div === 'function') {
                load_form_div('<?= base_url('Medical/list_med_inv/' . (int) $ipdId) ?>', 'medical-main');
                return;
            }
            window.location.href = '<?= base_url('Medical/list_med_inv/' . (int) $ipdId) ?>';
        }

        function postJson(url, payload, done) {
            var formData = new FormData();
            Object.keys(payload || {}).forEach(function (key) {
                formData.append(key, payload[key]);
            });

            var csrfInput = document.querySelector('#medical-ipd-list-csrf input[type="hidden"]');
            if (csrfInput && csrfInput.name) {
                formData.append(csrfInput.name, csrfInput.value || '');
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (typeof done === 'function') {
                    done(data || {});
                }
            }).catch(function () {
                alert('Unable to process request.');
            });
        }

        var lockBtn = document.getElementById('btn_lock_ipd');
        if (lockBtn) {
            lockBtn.addEventListener('click', function () {
                var lockValue = lockBtn.textContent.indexOf('Unlock') >= 0 ? 0 : 1;
                postJson('<?= base_url('Medical/lock_ipd/' . (int) $ipdId) ?>/' + lockValue, {}, function (data) {
                    if (!parseInt((data && data.update) ? data.update : 0, 10)) {
                        alert((data && data.msg_text) ? data.msg_text : 'Something Wrong');
                        return;
                    }
                    refreshList();
                });
            });
        }

        var discountBtn = document.getElementById('btn_update_group_discount');
        if (discountBtn) {
            discountBtn.addEventListener('click', function () {
                postJson('<?= base_url('Medical/update_group_discount/' . (int) $ipdId) ?>', {
                    input_discount: (document.getElementById('input_discount') ? document.getElementById('input_discount').value : '0')
                }, function (data) {
                    if (!parseInt((data && data.update) ? data.update : 0, 10)) {
                        alert((data && data.msg_text) ? data.msg_text : 'Something Wrong');
                        return;
                    }
                    refreshList();
                });
            });
        }
    })();
</script>
