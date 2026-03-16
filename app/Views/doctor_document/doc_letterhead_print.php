<?php
$printType = (int) ($print_on_type ?? 0);
$topMargin = number_format((float) ($print_top_margin ?? ($printType === 1 ? 6.10 : 2.20)), 2, '.', '');
$bottomMargin = number_format((float) ($print_bottom_margin ?? 2.50), 2, '.', '');
$leftMargin = number_format((float) ($print_left_margin ?? 0.70), 2, '.', '');
$rightMargin = number_format((float) ($print_right_margin ?? 0.70), 2, '.', '');
$headerMargin = number_format((float) ($print_header_margin ?? 0.50), 2, '.', '');
$footerMargin = number_format((float) ($print_footer_margin ?? 1.50), 2, '.', '');
$customHeaderHtml = (string) ($custom_header_html ?? '');
$customFooterHtml = (string) ($custom_footer_html ?? '');
?>
<style>
@page {
    margin-top: <?= $topMargin ?>cm;
    margin-bottom: <?= $bottomMargin ?>cm;
    margin-left: <?= $leftMargin ?>cm;
    margin-right: <?= $rightMargin ?>cm;
    margin-header: <?= $headerMargin ?>cm;
    margin-footer: <?= $footerMargin ?>cm;
    header: html_myHeader;
    footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
<?php if (trim($customHeaderHtml) !== ''): ?>
<?= $customHeaderHtml ?>
<?php elseif ($printType === 1): ?>
<table cellspacing="0" style="font-size:10px;width:100%;border-style:inset;">
    <tr>
        <td style="width:20%;vertical-align:top;">
            <img style="width:100px;vertical-align:top;" src="<?= esc(base_url('assets/images/' . (defined('H_logo') ? constant('H_logo') : 'logo.png'))) ?>" />
        </td>
        <td style="width:70%;vertical-align:top;">
            <p align="center" style="font-size:26px;"><?= esc(defined('H_Name') ? constant('H_Name') : 'Hospital') ?></p>
            <p align="center" style="font-size:14px;"><?= esc(defined('H_address_1') ? constant('H_address_1') : '') ?><br>
                <?= esc(defined('H_phone_No') ? 'Phone: ' . constant('H_phone_No') : '') ?></p>
        </td>
        <td style="width:10%;vertical-align:top;text-align:right;">
            <barcode code="<?= esc((string) ($bar_content ?? '')) ?>" size="0.8" type="QR" error="M" class="barcode" />
        </td>
    </tr>
</table>
<?php endif; ?>
</htmlpageheader>

<htmlpagefooter name="myFooter">
<?php if (trim($customFooterHtml) !== ''): ?>
<?= $customFooterHtml ?>
<?php else: ?>
<table width="100%" style="font-size:10px;">
    <tr>
        <td width="15%">Page : {PAGENO}/{nbpg}</td>
        <td width="85%" style="text-align:right;"><?= esc((string) ($report_title ?? 'Patient Document')) ?></td>
    </tr>
</table>
<?php endif; ?>
</htmlpagefooter>

<?= (string) ($content ?? '') ?>
