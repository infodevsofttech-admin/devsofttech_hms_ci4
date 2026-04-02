<section class="content">
    <div class="pagetitle">
        <h1>Hospital Stock Management</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Dashboard</li>
                <li class="breadcrumb-item">Setting</li>
                <li class="breadcrumb-item active">Hospital Stock</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3">
        <?php if ($canReportView ?? false): ?>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/hospital-stock/dashboard') ?>','stockmaindiv','Stock Dashboard');">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($canMasterManage ?? false): ?>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/hospital-stock/masters') ?>','stockmaindiv','Stock Masters');">
                <i class="bi bi-boxes"></i>
                <span>Item Masters</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (($canIndentCreate ?? false) || ($canIndentApprove ?? false) || ($canIssue ?? false)): ?>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/hospital-stock/indents') ?>','stockmaindiv','Stock Indents');">
                <i class="bi bi-journal-check"></i>
                <span>Indent & Issue</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($canPurchaseManage ?? false): ?>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/hospital-stock/purchase') ?>','stockmaindiv','Purchase Management');">
                <i class="bi bi-cart4"></i>
                <span>Purchase</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($canReportView ?? false): ?>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/hospital-stock/reports') ?>','stockmaindiv','Stock Reports');">
                <i class="bi bi-file-earmark-text"></i>
                <span>Reports</span>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <hr/>
    <div class="row mt-3">
        <div class="col-12" id="stockmaindiv"></div>
    </div>
</section>

<script>
(function() {
    var defaultUrl = '<?= base_url('setting/admin/hospital-stock/dashboard') ?>';
    var defaultTitle = 'Stock Dashboard';

    <?php if (! ($canReportView ?? false) && ($canMasterManage ?? false)): ?>
    defaultUrl = '<?= base_url('setting/admin/hospital-stock/masters') ?>';
    defaultTitle = 'Stock Masters';
    <?php elseif (! ($canReportView ?? false) && (($canIndentCreate ?? false) || ($canIndentApprove ?? false) || ($canIssue ?? false))): ?>
    defaultUrl = '<?= base_url('setting/admin/hospital-stock/indents') ?>';
    defaultTitle = 'Stock Indents';
    <?php elseif (! ($canReportView ?? false) && ($canPurchaseManage ?? false)): ?>
    defaultUrl = '<?= base_url('setting/admin/hospital-stock/purchase') ?>';
    defaultTitle = 'Purchase Management';
    <?php endif; ?>

    load_form_div(defaultUrl, 'stockmaindiv', defaultTitle);
})();
</script>