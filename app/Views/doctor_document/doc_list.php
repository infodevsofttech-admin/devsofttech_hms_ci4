<div class="pagetitle">
    <h1>Document Template List</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('doctor_work/document_workspace') ?>')">Doctor Documents</a></li>
            <li class="breadcrumb-item active">Template List</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Templates</h5>
                        <button onclick="load_form_div('<?= base_url('Doc_Admin/docedit_load/0') ?>','test_div');" type="button" class="btn btn-primary btn-sm">Add New Template</button>
                    </div>
                    <div class="table-responsive" style="max-height: 560px;">
                    <table id="report_list" class="table table-bordered table-striped align-middle">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($doc_master ?? []) as $row): ?>
                            <tr>
                                <td><?= esc((string) ($row['doc_name'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['doc_desc'] ?? '')) ?></td>
                                <td>
                                    <button onclick="load_form_div('<?= base_url('Doc_Admin/doc_input_list/' . (int) ($row['df_id'] ?? 0)) ?>','test_div');" type="button" class="btn btn-outline-primary btn-sm">Input List</button>
                                    <button onclick="load_form_div('<?= base_url('Doc_Admin/docedit_load/' . (int) ($row['df_id'] ?? 0)) ?>','test_div');" type="button" class="btn btn-primary btn-sm">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5" id="test_div"></div>
    </div>
</section>

<script>
if (window.jQuery && $.fn.DataTable) {
    $('#report_list').DataTable();
}
</script>
