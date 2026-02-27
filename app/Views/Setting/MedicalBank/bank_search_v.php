<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Medical Bank & Payment Sources</h3>
    </div>
    <div class="card-body">
        <?= csrf_field() ?>
        <div class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label class="form-label">Medical Bank</label>
                <select class="form-select" id="bank_filter_id">
                    <option value="">Select bank</option>
                    <?php foreach ($banks as $bank) : ?>
                        <option value="<?= esc($bank->id ?? '') ?>"><?= esc($bank->bank_name ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-7 d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary" id="btn_bank_modal_open">Add Bank</button>
                <button type="button" class="btn btn-outline-primary" id="btn_source_modal_open">Add Payment Source</button>
            </div>
        </div>

        <hr />

        <div class="row g-3">
            <div class="col-12">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-3">Payment Sources</h6>
                    <div class="table-responsive">
                        <table class="table table-striped" id="sources_table">
                            <thead>
                                <tr>
                                    <th>Pay Type</th>
                                    <th style="width: 160px;"></th>
                                </tr>
                            </thead>
                            <tbody id="sources_table_body">
                                <tr>
                                    <td colspan="2" class="text-muted">Select a bank to view payment sources.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <hr />

        <div class="row mt-3">
            <div class="col-12">
                <h6 class="mb-2">Medical Banks</h6>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Bank</th>
                                <th style="width: 140px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($banks)) : ?>
                                <?php foreach ($banks as $bank) : ?>
                                    <tr>
                                        <td><?= esc($bank->bank_name ?? '') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="editBank('<?= esc($bank->id ?? '') ?>','<?= esc($bank->bank_name ?? '') ?>')">Edit</button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteBank('<?= esc($bank->id ?? '') ?>')">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="2" class="text-muted">No banks found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bankSourceModal" tabindex="-1" aria-labelledby="bankSourceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bankSourceModalLabel">Add Bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_bank_id" value="">
                <div class="mb-3">
                    <label class="form-label">Bank Name</label>
                    <input type="text" class="form-control" id="modal_bank_name" placeholder="Bank name">
                </div>
                <div class="mb-3" id="modal_bank_source_group">
                    <label class="form-label">Payment Source Name (optional)</label>
                    <input type="text" class="form-control" id="modal_pay_type" placeholder="Card/UPI/etc">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_modal_save">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentSourceModal" tabindex="-1" aria-labelledby="paymentSourceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentSourceModalLabel">Add Payment Source</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_source_id" value="">
                <div class="mb-3">
                    <label class="form-label">Payment Source Bank</label>
                    <select class="form-select" id="modal_source_bank_id">
                        <option value="">Select bank</option>
                        <?php foreach ($banks as $bank) : ?>
                            <option value="<?= esc($bank->id ?? '') ?>"><?= esc($bank->bank_name ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Source Name</label>
                    <input type="text" class="form-control" id="modal_pay_type" placeholder="Card/UPI/etc">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_modal_source_save">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
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

        function resetBankModal() {
            $('#modal_bank_id').val('');
            $('#modal_bank_name').val('');
            $('#modal_pay_type').val('');
            $('#modal_bank_source_group').show();
            $('#bankSourceModalLabel').text('Add Bank');
        }

        function resetSourceModal() {
            $('#modal_source_id').val('');
            $('#modal_source_bank_id').val('');
            $('#modal_pay_type').val('');
            $('#paymentSourceModalLabel').text('Add Payment Source');
        }

        function renderSourcesTable(list) {
            var tbody = $('#sources_table_body');
            tbody.empty();
            if (!list || list.length === 0) {
                tbody.append('<tr><td colspan="2" class="text-muted">No payment sources for this bank.</td></tr>');
                return;
            }
            list.forEach(function(row) {
                var tr = $('<tr></tr>');
                tr.append('<td>' + (row.pay_type || '') + '</td>');
                tr.append(
                    '<td>' +
                    '<button type="button" class="btn btn-sm btn-primary me-1" onclick="editSource(\'' + row.id + '\',\'' + row.bank_id + '\',\'' + row.pay_type + '\')">Edit</button>' +
                    '<button type="button" class="btn btn-sm btn-danger" onclick="deleteSource(\'' + row.id + '\')">Delete</button>' +
                    '</td>'
                );
                tbody.append(tr);
            });
        }

        function loadSourcesForBank(bankId) {
            if (!bankId) {
                renderSourcesTable([]);
                return;
            }
            $.getJSON('<?= base_url('setting/admin/medical-bank/sources') ?>/' + bankId, function(data) {
                updateCsrf(data);
                renderSourcesTable(data.sources || []);
            });
        }

        window.editBank = function(id, name) {
            $('#modal_bank_id').val(id);
            $('#modal_bank_name').val(name);
            $('#modal_bank_source_group').hide();
            $('#bankSourceModalLabel').text('Edit Bank');
            $('#bankSourceModal').modal('show');
        };

        window.editSource = function(id, bankId, payType) {
            $('#modal_source_id').val(id);
            $('#modal_source_bank_id').val(bankId);
            $('#modal_pay_type').val(payType);
            $('#paymentSourceModalLabel').text('Edit Payment Source');
            $('#paymentSourceModal').modal('show');
        };

        $('#btn_bank_modal_open').click(function() {
            resetBankModal();
            $('#bankSourceModal').modal('show');
        });

        window.deleteBank = function(id) {
            var csrf = getCsrfPair();
            if (!confirm('Delete this bank?')) {
                return;
            }
            $.post('<?= base_url('setting/admin/medical-bank/delete') ?>', {
                "bank_id": id,
                [csrf.name]: csrf.value
            }, function(data) {
                updateCsrf(data);
                if (data.update == 0) {
                    alert(data.error_text || 'Unable to delete');
                    return;
                }
                load_form_div('<?= base_url('setting/admin/medical-bank') ?>', 'maindiv', 'Medical Bank & Payment Sources');
             }, 'json');
        };

        window.deleteSource = function(id) {
            var csrf = getCsrfPair();
            if (!confirm('Delete this payment source?')) {
                return;
            }
            $.post('<?= base_url('setting/admin/medical-bank/source/delete') ?>', {
                "source_id": id,
                [csrf.name]: csrf.value
            }, function(data) {
                updateCsrf(data);
                if (data.update == 0) {
                    alert(data.error_text || 'Unable to delete');
                    return;
                }
                load_form_div('<?= base_url('setting/admin/medical-bank') ?>', 'maindiv', 'Medical Bank & Payment Sources');
             }, 'json');
        };

        $('#btn_modal_save').click(function() {
            var csrf = getCsrfPair();
            var bankId = $('#modal_bank_id').val();
            var url = bankId ? '<?= base_url('setting/admin/medical-bank/update') ?>' : '<?= base_url('setting/admin/medical-bank/create-with-source') ?>';
            var payload = {
                "bank_name": $('#modal_bank_name').val(),
                [csrf.name]: csrf.value
            };
            if (bankId) {
                payload.bank_id = bankId;
            } else {
                payload.pay_type = $('#modal_pay_type').val();
            }

            $.post(url, payload, function(data) {
                updateCsrf(data);
                if ((data.update ?? data.insertid) == 0) {
                    alert(data.error_text || 'Unable to save');
                    return;
                }
                $('#bankSourceModal').modal('hide');
                load_form_div('<?= base_url('setting/admin/medical-bank') ?>', 'maindiv', 'Medical Bank & Payment Sources');
            }, 'json');
        });

        $('#bankSourceModal').on('hidden.bs.modal', function() {
            resetBankModal();
        });

        $('#bank_filter_id').change(function() {
            loadSourcesForBank($(this).val());
        });

        $('#btn_source_modal_open').click(function() {
            resetSourceModal();
            var selectedBank = $('#bank_filter_id').val();
            if (!selectedBank) {
                alert('Please select a bank first.');
                return;
            }
            if (selectedBank) {
                $('#modal_source_bank_id').val(selectedBank);
            }
            $('#paymentSourceModal').modal('show');
        });

        $('#btn_modal_source_save').click(function() {
            var csrf = getCsrfPair();
            var sourceId = $('#modal_source_id').val();
            var url = sourceId ? '<?= base_url('setting/admin/medical-bank/source/update') ?>' : '<?= base_url('setting/admin/medical-bank/source/create') ?>';
            var payload = {
                "bank_id": $('#modal_source_bank_id').val(),
                "pay_type": $('#modal_pay_type').val(),
                [csrf.name]: csrf.value
            };
            if (sourceId) {
                payload.source_id = sourceId;
            }

            $.post(url, payload, function(data) {
                updateCsrf(data);
                if ((data.update ?? data.insertid) == 0) {
                    alert(data.error_text || 'Unable to save');
                    return;
                }
                $('#paymentSourceModal').modal('hide');
                load_form_div('<?= base_url('setting/admin/medical-bank') ?>', 'maindiv', 'Medical Bank & Payment Sources');
            }, 'json');
        });

        $('#paymentSourceModal').on('hidden.bs.modal', function() {
            resetSourceModal();
        });
    })();
</script>
