<?php
$batchId = (int) ($batch_id ?? 0);
$title = trim((string) ($title ?? 'AI Diagnosis'));
$studyName = trim((string) ($study_name ?? ''));
$findingsHtml = (string) ($findings_html ?? '');
$impressionHtml = (string) ($impression_html ?? '');
$summaryText = trim((string) ($summary_text ?? ''));
$provider = trim((string) ($provider ?? 'azure-openai'));
$model = trim((string) ($model ?? ''));
$findingsInputId = 'ai_findings_' . $batchId;
$impressionInputId = 'ai_impression_' . $batchId;
?>

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
    <div>
        <h5 class="mb-1"><?= esc($title) ?></h5>
        <?php if ($studyName !== ''): ?>
            <div class="small text-muted">Study: <?= esc($studyName) ?></div>
        <?php endif; ?>
        <div class="small text-muted">Stored batch #<?= esc((string) $batchId) ?> | <?= esc($provider) ?><?= $model !== '' ? ' / ' . esc($model) : '' ?></div>
    </div>
    <div class="d-flex gap-2">
        <button
            type="button"
            class="btn btn-primary"
            data-findings-target="#<?= esc($findingsInputId) ?>"
            data-impression-target="#<?= esc($impressionInputId) ?>"
            onclick="pasteAiDiagnosisDraft(this)">
            Paste In Report
        </button>
        <button
            type="button"
            class="btn btn-success"
            data-findings-target="#<?= esc($findingsInputId) ?>"
            data-impression-target="#<?= esc($impressionInputId) ?>"
            onclick="pasteAiDiagnosisDraftAndSave(this)">
            Paste + Save
        </button>
    </div>
</div>

<?php if ($summaryText !== ''): ?>
    <div class="alert alert-info py-2"><?= esc($summaryText) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><strong>Findings Draft</strong></div>
            <div class="card-body">
                <?= $findingsHtml !== '' ? $findingsHtml : '<div class="text-muted">No findings generated.</div>' ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><strong>Impression Draft</strong></div>
            <div class="card-body">
                <?= $impressionHtml !== '' ? $impressionHtml : '<div class="text-muted">No impression generated.</div>' ?>
            </div>
        </div>
    </div>
</div>

<textarea id="<?= esc($findingsInputId) ?>" style="display:none;"><?= esc($findingsHtml) ?></textarea>
<textarea id="<?= esc($impressionInputId) ?>" style="display:none;"><?= esc($impressionHtml) ?></textarea>