<?php
$ipdId = (int) ($ipd_id ?? 0);
$templateId = (int) ($template_id ?? 0);
$templateName = (string) ($template_name ?? '');
$html = (string) ($html ?? '');
?>
<section class="content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Exact HTML passed to mPDF WriteHTML()</strong>
            <span class="small text-muted">IPD <?= $ipdId ?> | Template <?= $templateId ?>: <?= esc($templateName) ?></span>
        </div>
        <div class="card-body">
            <pre style="white-space:pre-wrap;word-break:break-word;font-family:Consolas,monospace;font-size:12px;margin:0;"><?= esc($html) ?></pre>
        </div>
    </div>
</section>
