<div id="Medical_invoice_final">
    <section class="content-header mb-3">
        <h5 class="mb-0">
            Medical Invoice Master Edit :
            <small>
                <a href="javascript:load_form_div('<?= base_url('Medical/invoice_edit/' . (int)($invoice->id ?? 0)) ?>','medical-main');">Back to Invoice</a>
            </small>
        </h5>
    </section>

    <?php if (! empty($message)): ?>
        <div class="alert alert-success py-2"><?= esc($message) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('Medical/edit_invoice_edit/' . (int)($invoice->id ?? 0)) ?>" class="form1" id="med-invoice-edit-form">
        <?= csrf_field() ?>
        <input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?= (int)($invoice->id ?? 0) ?>">
        <input type="hidden" id="pid" name="pid" value="0">
        <input type="hidden" id="ipd_id" name="ipd_id" value="0">
        <input type="hidden" id="org_id" name="org_id" value="0">
        <input type="hidden" id="customer_type" name="customer_type" value="0">

        <div class="card border-success mb-3">
            <div class="card-header">
                <p class="mb-0 small">
                    <strong>Name :</strong> <?= esc($invoice->inv_name ?? '-') ?>
                    <strong>/ P Code :</strong> <?= esc($invoice->patient_code ?? '-') ?>
                    <strong>/ Invoice No. :</strong> <?= esc($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) ((int)($invoice->id ?? 0)), -7, 7), 7, '0', STR_PAD_LEFT))) ?>
                    <strong>/ Date :</strong> <?= esc(! empty($invoice->inv_date) ? date('d/m/Y', strtotime((string) $invoice->inv_date)) : '-') ?>
                    <?php if (! empty($invoice->ipd_code)): ?>
                        <strong>/ IPD Code :</strong> <?= esc($invoice->ipd_code) ?>
                    <?php endif; ?>
                    <?php if (! empty($invoice->case_id)): ?>
                        <strong>/ Org. Case :</strong> <?= esc($invoice->case_id) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Change Date</label>
                        <input class="form-control" id="datepicker_invoicedate" name="inv_date" type="date" value="<?= esc(! empty($invoice->inv_date) ? date('Y-m-d', strtotime((string) $invoice->inv_date)) : '') ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary" onclick="update_invdate()">Update Invoice Date</button>
                    </div>
                </div>

                <div class="row g-2 mt-2 align-items-end">
                    <?php if ((int)($invoice->ipd_id ?? 0) > 0): ?>
                        <div class="col-md-4">
                            <label class="form-label d-block">Credit Type</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ipd_credit" value="0" id="options_credit1" <?= ((int)($invoice->ipd_credit ?? 0) === 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="options_credit1">Cash / Direct</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ipd_credit" value="1" id="options_credit2" <?= ((int)($invoice->ipd_credit ?? 0) === 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="options_credit2">Credit To Hospital</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary" onclick="update_ipd_credit_status()">Update Credit Type</button>
                        </div>
                    <?php elseif ((int)($invoice->case_id ?? 0) > 0): ?>
                        <div class="col-md-4">
                            <label class="form-label d-block">Credit Type</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="case_credit" value="0" id="options_case_credit1" <?= ((int)($invoice->case_credit ?? 0) === 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="options_case_credit1">Cash</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="case_credit" value="1" id="options_case_credit2" <?= ((int)($invoice->case_credit ?? 0) === 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="options_case_credit2">Credit</label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-info mb-3">
            <div class="card-header">
                <p class="mb-0" style="font-size: 14px;">Change Name : <span class="text-danger">Current Name : <?= esc($invoice->inv_name ?? '-') ?></span></p>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Patient Name</label>
                        <input class="form-control" id="P_Name" name="inv_name" value="<?= esc($invoice->inv_name ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patient Phone No.</label>
                        <input class="form-control" id="P_Phone" name="inv_phone_number" value="<?= esc($invoice->inv_phone_number ?? '') ?>" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary" onclick="update_name_phone()">Update Name</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-danger mb-3">
            <div class="card-header">
                <p class="mb-0" style="font-size: 14px;">Change UHID : <span class="text-danger">Current UHID : <?= esc($invoice->patient_code ?? '-') ?></span></p>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">UHID /Patient Code</label>
                        <input class="form-control" name="patient_code" id="input_uhid" placeholder="UHID /Patient Code" type="text" value="<?= esc($invoice->patient_code ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Patient Information</label>
                        <div class="form-control" id="P_Info"><?= esc($invoice->inv_name ?? '-') ?> / <?= esc($invoice->inv_phone_number ?? '-') ?></div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary" onclick="update_uhid()">Update UHID Record</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-warning mb-3">
            <div class="card-header">
                <p class="mb-0" style="font-size: 14px;">Change IPD : <?php if ((int)($invoice->ipd_credit ?? 0) > 0): ?><span class="text-danger">Current IPD : <?= esc($invoice->ipd_code ?? '-') ?></span><?php endif; ?></p>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">IPD No./Code</label>
                        <input class="form-control" name="ipd_code" id="input_ipd_no" placeholder="IPD Code" type="text" value="<?= esc($invoice->ipd_code ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Patient Information</label>
                        <div class="form-control" id="IPD_Info"><?= esc($invoice->inv_name ?? '-') ?></div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-warning" onclick="update_ipd()">Update IPD NO. Record</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-info mb-3">
            <div class="card-header">
                <p class="mb-0" style="font-size: 14px;">Change Org. Case : <?php if ((int)($invoice->case_credit ?? 0) > 0): ?><span class="text-danger">Current Org. Case : <?= esc($invoice->case_id ?? '-') ?></span><?php endif; ?></p>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Org.Case No./Code</label>
                        <input class="form-control" name="case_id" id="input_org_no" placeholder="ORG. Code" type="text" value="<?= esc($invoice->case_id ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Patient Information</label>
                        <div class="form-control" id="org_case_Info"><?= esc($invoice->inv_name ?? '-') ?></div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-info" onclick="update_org()">Update Org NO. Record</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-body d-flex flex-wrap gap-2">
                <div class="flex-grow-1">
                    <label class="form-label">Doctor</label>
                    <select class="form-select" id="doc-select">
                        <option value="">Select</option>
                        <?php foreach (($docList ?? []) as $doc): ?>
                            <option value="<?= esc($doc->id ?? '') ?>" data-name="<?= esc($doc->p_fname ?? '') ?>" <?= ((int)($invoice->doc_id ?? 0) === (int)($doc->id ?? 0)) ? 'selected' : '' ?>>
                                <?= esc($doc->p_fname ?? ('Doctor #' . ($doc->id ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="doc_id" id="doc_id" value="<?= (int)($invoice->doc_id ?? 0) ?>">
                    <input type="hidden" name="doc_name" id="doc_name" value="<?= esc($invoice->doc_name ?? '') ?>">
                </div>
                <div class="d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-success">Update Invoice Header</button>
                    <a class="btn btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/invoice_edit/' . (int)($invoice->id ?? 0)) ?>','medical-main');">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function csrfInput() {
        return document.querySelector('input[name="<?= csrf_token() ?>"]');
    }

    function withCsrf(data) {
        var csrfEl = csrfInput();
        if (csrfEl) {
            data['<?= csrf_token() ?>'] = csrfEl.value || '';
        }
        return data;
    }

    function updateCsrfFromResponse(data) {
        var csrfEl = csrfInput();
        if (csrfEl && data && data.csrfHash) {
            csrfEl.value = data.csrfHash;
        }
    }

    function postMedical(url, data, options) {
        var opts = options || {};
        var payload = new URLSearchParams(withCsrf(data));
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: payload.toString()
        }).then(function (res) {
            return res.json();
        }).then(function (json) {
            updateCsrfFromResponse(json);
            if (!opts.silent) {
                alert((json && json.remark) ? json.remark : 'Done');
            }
            return json;
        }).catch(function () {
            if (!opts.silent) {
                alert('Request failed');
            }
            return null;
        });
    }

    function onchange_uhid() {
        var inputUhid = document.getElementById('input_uhid');
        if (!inputUhid) {
            return;
        }
        postMedical('<?= base_url('Medical/patient_info') ?>', {
            input_uhid: inputUhid.value || ''
        }, { silent: true }).then(function (data) {
            if (!data) {
                return;
            }
            var pidEl = document.getElementById('pid');
            var infoEl = document.getElementById('P_Info');
            if (pidEl) {
                pidEl.value = data.Patient_id || 0;
            }
            if (infoEl) {
                infoEl.textContent = data.Patient_info || '';
            }
        });
    }

    function onchange_ipd() {
        var inputIpd = document.getElementById('input_ipd_no');
        if (!inputIpd) {
            return;
        }
        postMedical('<?= base_url('Medical/ipd_info') ?>', {
            input_ipd_no: inputIpd.value || ''
        }, { silent: true }).then(function (data) {
            if (!data) {
                return;
            }
            var ipdEl = document.getElementById('ipd_id');
            var infoEl = document.getElementById('IPD_Info');
            if (ipdEl) {
                ipdEl.value = data.IPD_id || 0;
            }
            if (infoEl) {
                infoEl.textContent = data.ipd_info || '';
            }
        });
    }

    function onchange_org() {
        var inputOrg = document.getElementById('input_org_no');
        if (!inputOrg) {
            return;
        }
        postMedical('<?= base_url('Medical/org_info') ?>', {
            input_org_no: inputOrg.value || ''
        }, { silent: true }).then(function (data) {
            if (!data) {
                return;
            }
            var orgEl = document.getElementById('org_id');
            var infoEl = document.getElementById('org_case_Info');
            if (orgEl) {
                orgEl.value = data.org_id || 0;
            }
            if (infoEl) {
                infoEl.textContent = data.org_info || '';
            }
        });
    }

    function update_uhid() {
        var pid = (document.getElementById('pid') || {}).value || 0;
        var medInvoiceId = (document.getElementById('med_invoice_id') || {}).value || 0;
        var inputUhid = (document.getElementById('input_uhid') || {}).value || '';
        postMedical('<?= base_url('Medical/update_uhid') ?>', {
            pid: pid,
            med_invoice_id: medInvoiceId,
            input_uhid: inputUhid
        });
    }

    function update_name_phone() {
        var medInvoiceId = (document.getElementById('med_invoice_id') || {}).value || 0;
        var pName = (document.getElementById('P_Name') || {}).value || '';
        var pPhone = (document.getElementById('P_Phone') || {}).value || '';
        pPhone = pPhone.replace(/\D/g, '');
        var docId = (document.getElementById('doc_id') || {}).value || 0;
        var docName = (document.getElementById('doc_name') || {}).value || '';

        if (pPhone !== '' && pPhone.length !== 10) {
            alert('Phone number must be exactly 10 digits.');
            var phoneEl = document.getElementById('P_Phone');
            if (phoneEl) {
                phoneEl.focus();
            }
            return;
        }

        if (!confirm('Are you Sure Update Patient Name')) {
            return;
        }

        postMedical('<?= base_url('Medical/update_name_phone') ?>', {
            pid: 0,
            customer_type: 0,
            P_Name: pName,
            P_Phone: pPhone,
            med_invoice_id: medInvoiceId,
            doc_id: docId,
            doc_name: docName
        });
    }

    function update_ipd() {
        var ipdId = (document.getElementById('ipd_id') || {}).value || 0;
        var medInvoiceId = (document.getElementById('med_invoice_id') || {}).value || 0;
        var inputIpdNo = (document.getElementById('input_ipd_no') || {}).value || '';
        postMedical('<?= base_url('Medical/update_ipd') ?>', {
            ipd_id: ipdId,
            med_invoice_id: medInvoiceId,
            input_ipd_no: inputIpdNo
        });
    }

    function update_org() {
        var orgId = (document.getElementById('org_id') || {}).value || 0;
        var medInvoiceId = (document.getElementById('med_invoice_id') || {}).value || 0;
        var inputOrgNo = (document.getElementById('input_org_no') || {}).value || '';
        postMedical('<?= base_url('Medical/update_org') ?>', {
            org_id: orgId,
            med_invoice_id: medInvoiceId,
            input_org_no: inputOrgNo
        });
    }

    function update_invdate() {
        var invDate = (document.getElementById('datepicker_invoicedate') || {}).value || '';
        var medInvoiceId = (document.getElementById('med_invoice_id') || {}).value || 0;
        postMedical('<?= base_url('Medical/update_invdate') ?>', {
            med_invoice_id: medInvoiceId,
            inv_date: invDate
        });
    }

    function update_ipd_credit_status() {
        var creditEl = document.querySelector("input[name='ipd_credit']:checked");
        var medInvoiceId = (document.getElementById('med_invoice_id') || {}).value || 0;
        var creditIpd = creditEl ? creditEl.value : '';
        if (creditIpd === '') {
            alert('Select Credit Type');
            return;
        }
        postMedical('<?= base_url('Medical/update_cr_status_ipd') ?>', {
            med_invoice_id: medInvoiceId,
            credit_ipd: creditIpd
        });
    }

    window.onchange_uhid = onchange_uhid;
    window.onchange_ipd = onchange_ipd;
    window.onchange_org = onchange_org;
    window.update_uhid = update_uhid;
    window.update_name_phone = update_name_phone;
    window.update_ipd = update_ipd;
    window.update_org = update_org;
    window.update_invdate = update_invdate;
    window.update_ipd_credit_status = update_ipd_credit_status;

    var uhidInput = document.getElementById('input_uhid');
    if (uhidInput) {
        uhidInput.addEventListener('change', onchange_uhid);
    }
    var ipdInput = document.getElementById('input_ipd_no');
    if (ipdInput) {
        ipdInput.addEventListener('change', onchange_ipd);
    }
    var orgInput = document.getElementById('input_org_no');
    if (orgInput) {
        orgInput.addEventListener('change', onchange_org);
    }
    var phoneInput = document.getElementById('P_Phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            var digits = (phoneInput.value || '').replace(/\D/g, '').slice(0, 10);
            phoneInput.value = digits;
        });
    }

    (function () {
        var select = document.getElementById('doc-select');
        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            var option = select.options[select.selectedIndex];
            var docIdInput = document.getElementById('doc_id');
            var docNameInput = document.getElementById('doc_name');
            if (docIdInput) {
                docIdInput.value = option ? (option.value || 0) : 0;
            }
            if (docNameInput) {
                docNameInput.value = option ? (option.getAttribute('data-name') || '') : '';
            }
        });
    })();
</script>
