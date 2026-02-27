<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">OPD Charges</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('item/search-itemtype') ?>','maindiv','OPD Charge Groups');">
                <i class="bi bi-list"></i>
                Charge Groups
            </button>
            <button onclick="load_form_div('<?= base_url('item/add') ?>','maindiv','New Charge');" type="button" class="btn btn-primary">Add New Charge</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Charge Type</label>
                <select class="form-select" id="itype_idv" name="itype_idv">
                    <?php foreach ($labitemtype as $row) : ?>
                        <option value="<?= esc($row->itype_id ?? '') ?>" <?= combo_checked($typeId ?? '', $row->itype_id ?? '') ?>>
                            <?= esc($row->group_desc ?? '') ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Insurance Company</label>
                <select class="form-select" id="insurance_id" name="insurance_id">
                    <option value="0">Base Rate</option>
                    <?php foreach ($insurance_list as $row) : ?>
                        <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->ins_company_name ?? '') ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" id="export_btn">
                    <i class="bi bi-file-earmark-excel"></i>
                    Export Excel
                </button>
                <button type="button" class="btn btn-outline-secondary" id="print_btn">
                    <i class="bi bi-printer"></i>
                    Print PDF
                </button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div id="search_div">
                    <?= view('Setting/Charges/OPD_Charges/item_search_adv', ['data' => $data, 'typeId' => $typeId]) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tallModal_item" tabindex="-1" aria-labelledby="tallModal_itemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tallModal_itemLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="tallModal_item-bodyc" id="tallModal_item-bodyc"></div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var searchAdvUrl = '<?= base_url('item/search-adv') ?>';
        var printUrl = '<?= base_url('item/search-print') ?>';
        var exportUrl = '<?= base_url('item/export-excel') ?>';

        window.refreshChargeList = function() {
            var typeId = $('#itype_idv').val() || 1;
            load_form_div(searchAdvUrl + '/' + typeId, 'search_div');
        };

        $('#itype_idv').on('change', function() {
            window.refreshChargeList();
        });

        $('#print_btn').on('click', function() {
            var typeId = $('#itype_idv').val() || 1;
            var insuranceId = $('#insurance_id').val() || 0;
            var url = printUrl + '/' + typeId + '?insurance=' + insuranceId;
            window.open(url, '_blank');
        });

        $('#export_btn').on('click', function() {
            var typeId = $('#itype_idv').val() || 1;
            var insuranceId = $('#insurance_id').val() || 0;
            var url = exportUrl + '/' + typeId + '?insurance=' + insuranceId;
            window.location.href = url;
        });

        $('#tallModal_item').on('shown.bs.modal', function(event) {
            $('#tallModal_item-bodyc').html('');
            var button = $(event.relatedTarget);
            var testid = button.data('testid');
            var testname = button.data('testname');

            $('#tallModal_itemLabel').text(testname || '');

            $.get('<?= base_url('item/item-record') ?>/' + testid, function(data) {
                $('#tallModal_item-bodyc').html(data);
            });
        });
    })();
</script>
