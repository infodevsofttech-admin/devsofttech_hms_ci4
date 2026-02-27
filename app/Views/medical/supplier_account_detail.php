<?php
$supplierId = (int) ($supplier->sid ?? 0);
$today = date('Y-m-d');
$defaultDateFrom = (string) ($default_date_from ?? $today);
$defaultDateTo = (string) ($default_date_to ?? $today);
?>

<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">
                Ledger Account: <?= esc($supplier->name_supplier ?? '-') ?>
                <small class="text-muted">Balance: <?= esc(number_format((float) ($supplier->Tot_Balance ?? 0), 2)) ?></small>
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-warning" id="open-add-entry">Add Entry</button>
                <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/supplier_account') ?>','medical-main','Supplier Account :Pharmacy');">Back to List</a>
            </div>
        </div>

        <form id="supplier-ledger-form" class="row g-2 mb-3" method="post" action="javascript:void(0)">
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= esc($defaultDateFrom) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= esc($defaultDateTo) ?>" required>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Show</button>
                <button type="button" class="btn btn-outline-dark" id="print-ledger">Print</button>
            </div>
        </form>

        <div id="supplier-ledger-flash"></div>
        <div id="supplier-ledger-result"></div>
    </div>
</div>

<div class="modal fade" id="supplierAddEntryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="supplier-add-entry-body">Loading...</div>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('supplier-ledger-form');
    const flashDiv = document.getElementById('supplier-ledger-flash');
    const resultDiv = document.getElementById('supplier-ledger-result');
    const addEntryBtn = document.getElementById('open-add-entry');
    const printBtn = document.getElementById('print-ledger');
    const modalEl = document.getElementById('supplierAddEntryModal');
    const modalBody = document.getElementById('supplier-add-entry-body');
    const modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;
    const addEntryUrl = '<?= base_url('Medical/supplier_account_add_entry/' . $supplierId) ?>';
    const editEntryBaseUrl = '<?= base_url('Medical/supplier_account_edit_entry') ?>';
    const addEntryModal = window.bootstrap && modalEl ? new bootstrap.Modal(modalEl) : null;
    let addEntrySaving = false;
    let editEntrySaving = false;

    function showFlash(type, message) {
        if (!flashDiv) {
            return;
        }

        const safeType = type === 'danger' ? 'danger' : 'success';
        const safeMsg = (message || '').toString().trim() || (safeType === 'danger' ? 'Unable to save ledger entry.' : 'Entry saved successfully.');

        flashDiv.innerHTML = '<div class="alert alert-' + safeType + ' py-2 mb-2">' + safeMsg + '</div>';
        setTimeout(function () {
            flashDiv.innerHTML = '';
        }, 2500);
    }

    function loadLedgerData() {
        if (!form) {
            return;
        }

        const payload = new URLSearchParams(new FormData(form));
        resultDiv.innerHTML = 'Loading...';

        $.post('<?= base_url('Medical/supplier_account_ledger_data/' . $supplierId) ?>', payload.toString(), function (html) {
            resultDiv.innerHTML = html;
        });
    }

    function printLedger() {
        const fromInput = form ? form.querySelector('[name="date_from"]') : null;
        const toInput = form ? form.querySelector('[name="date_to"]') : null;
        const dateFrom = fromInput ? fromInput.value : '';
        const dateTo = toInput ? toInput.value : '';

        if (!dateFrom || !dateTo) {
            alert('Please select From and To date.');
            return;
        }

        const pdfUrl = '<?= base_url('Medical/supplier_account_ledger_pdf/' . $supplierId) ?>/' + encodeURIComponent(dateFrom) + '/' + encodeURIComponent(dateTo);
        window.open(pdfUrl, '_blank');
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        loadLedgerData();
    });

    if (printBtn) {
        printBtn.addEventListener('click', printLedger);
    }

    if (addEntryBtn) {
        addEntryBtn.addEventListener('click', function () {
            modalBody.innerHTML = 'Loading...';
            if (modalTitle) {
                modalTitle.textContent = 'Add Entry';
            }
            $.get(addEntryUrl, function (html) {
                modalBody.innerHTML = html;
                if (addEntryModal) {
                    addEntryModal.show();
                }
            }).fail(function () {
                modalBody.innerHTML = '<div class="alert alert-danger">Unable to load entry form.</div>';
                if (addEntryModal) {
                    addEntryModal.show();
                }
            });
        });
    }

    $(document).off('click.supplierEditEntry', '.btn-edit-entry');
    $(document).on('click.supplierEditEntry', '.btn-edit-entry', function () {
        const tranId = Number($(this).data('tran-id') || 0);
        if (!tranId) {
            return;
        }

        modalBody.innerHTML = 'Loading...';
        if (modalTitle) {
            modalTitle.textContent = 'Edit Entry';
        }

        $.get(editEntryBaseUrl + '/' + tranId, function (html) {
            modalBody.innerHTML = html;
            if (addEntryModal) {
                addEntryModal.show();
            }
        }).fail(function () {
            modalBody.innerHTML = '<div class="alert alert-danger">Unable to load edit form.</div>';
            if (addEntryModal) {
                addEntryModal.show();
            }
        });
    });

    $(document).off('submit', '#supplier-ledger-add-form');
    $(document).off('submit.supplierAddEntry', '#supplier-ledger-add-form');
    $(document).on('submit.supplierAddEntry', '#supplier-ledger-add-form', function (event) {
        event.preventDefault();
        if (addEntrySaving) {
            return;
        }

        addEntrySaving = true;
        const saveUrl = $(this).data('save-url') || addEntryUrl;
        const payload = $(this).serialize();
        const saveBtn = $(this).find('button[type="submit"]');
        saveBtn.prop('disabled', true).text('Saving...');

        $.post(saveUrl, payload, function (response) {
            if (response && Number(response.status) === 1) {
                if (addEntryModal) {
                    addEntryModal.hide();
                }

                showFlash('success', response.msg || 'Entry saved successfully.');

                loadLedgerData();
                addEntrySaving = false;
                return;
            }

            if (response && response.html) {
                modalBody.innerHTML = response.html;
                addEntrySaving = false;
                return;
            }

            showFlash('danger', response && response.msg ? response.msg : 'Unable to save ledger entry.');
            modalBody.innerHTML = '<div class="alert alert-danger">Unable to save ledger entry.</div>';
            addEntrySaving = false;
        }, 'json').fail(function () {
            showFlash('danger', 'Unable to save ledger entry.');
            modalBody.innerHTML = '<div class="alert alert-danger">Unable to save ledger entry.</div>';
            addEntrySaving = false;
        }).always(function () {
            saveBtn.prop('disabled', false).text('Save');
        });
    });

    $(document).off('submit', '#supplier-ledger-edit-form');
    $(document).off('submit.supplierEditEntry', '#supplier-ledger-edit-form');
    $(document).on('submit.supplierEditEntry', '#supplier-ledger-edit-form', function (event) {
        event.preventDefault();
        if (editEntrySaving) {
            return;
        }

        editEntrySaving = true;
        const saveUrl = $(this).data('save-url') || '';
        const payload = $(this).serialize();
        const saveBtn = $(this).find('button[type="submit"]');
        saveBtn.prop('disabled', true).text('Updating...');

        $.post(saveUrl, payload, function (response) {
            if (response && Number(response.status) === 1) {
                if (addEntryModal) {
                    addEntryModal.hide();
                }

                showFlash('success', response.msg || 'Ledger entry updated successfully.');
                loadLedgerData();
                editEntrySaving = false;
                return;
            }

            if (response && response.html) {
                modalBody.innerHTML = response.html;
                editEntrySaving = false;
                return;
            }

            showFlash('danger', response && response.msg ? response.msg : 'Unable to update ledger entry.');
            modalBody.innerHTML = '<div class="alert alert-danger">Unable to update ledger entry.</div>';
            editEntrySaving = false;
        }, 'json').fail(function () {
            showFlash('danger', 'Unable to update ledger entry.');
            modalBody.innerHTML = '<div class="alert alert-danger">Unable to update ledger entry.</div>';
            editEntrySaving = false;
        }).always(function () {
            saveBtn.prop('disabled', false).text('Update');
        });
    });

    loadLedgerData();
})();
</script>
