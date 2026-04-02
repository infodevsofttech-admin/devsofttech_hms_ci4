<?php
/**
 * Bed Status — Graphic Ward/Floor Map
 * Variables: $floors (array), $stats (array), $wards (array)
 */
$floorLabel = static function (int $n): string {
    if ($n === 0) {
        return 'Ground Floor';
    }
    $suffixes = [1 => 'st', 2 => 'nd', 3 => 'rd'];
    return $n . ($suffixes[$n] ?? 'th') . ' Floor';
};

$statusMeta = [
    'available'         => ['label' => 'Available',   'css' => 'bed-available',   'badge' => 'badge-avail'],
    'occupied'          => ['label' => 'Occupied',    'css' => 'bed-occupied',    'badge' => 'badge-occ'],
    'reserved'          => ['label' => 'Reserved',    'css' => 'bed-reserved',    'badge' => 'badge-res'],
    'blocked'           => ['label' => 'Blocked',     'css' => 'bed-blocked',     'badge' => 'badge-blk'],
    'under_maintenance' => ['label' => 'Maintenance', 'css' => 'bed-maint',       'badge' => 'badge-maint'],
    'cleaning'          => ['label' => 'Cleaning',    'css' => 'bed-clean',       'badge' => 'badge-clean'],
];
$statusBorderColor = [
    'available' => '#22c55e', 'occupied' => '#ef4444', 'reserved' => '#f59e0b',
    'blocked' => '#475569', 'under_maintenance' => '#6366f1', 'cleaning' => '#0ea5e9',
];
?>
<style>
.bed-card{border-radius:10px;border:2px solid #e2e8f0;background:#fff;padding:10px 12px;width:152px;min-height:98px;cursor:default;transition:box-shadow .15s,transform .1s;position:relative}
.bed-card:hover{box-shadow:0 6px 18px rgba(0,0,0,.13);transform:translateY(-2px)}
.bed-available{border-color:#22c55e;background:rgba(34,197,94,.07)}
.bed-occupied{border-color:#ef4444;background:rgba(239,68,68,.07)}
.bed-reserved{border-color:#f59e0b;background:rgba(245,158,11,.07)}
.bed-blocked{border-color:#475569;background:rgba(71,85,105,.07)}
.bed-maint{border-color:#6366f1;background:rgba(99,102,241,.07)}
.bed-clean{border-color:#0ea5e9;background:rgba(14,165,233,.07)}
.badge-avail{background:#dcfce7;color:#166534}
.badge-occ{background:#fee2e2;color:#991b1b}
.badge-res{background:#fef3c7;color:#92400e}
.badge-blk{background:#e2e8f0;color:#334155}
.badge-maint{background:#ede9fe;color:#4c1d95}
.badge-clean{background:#e0f2fe;color:#0c4a6e}
.bed-num{font-weight:800;font-size:1.05rem;line-height:1}
.bed-sbadge{font-size:.6rem;padding:2px 6px;border-radius:20px;font-weight:600}
.bed-info{font-size:.76rem;color:#374151;margin-top:4px;line-height:1.4}
.bed-txt{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:126px}
.bed-feats{display:flex;gap:3px;margin-top:5px;flex-wrap:wrap}
.feat-tag{font-size:.58rem;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:3px;padding:1px 4px;color:#334155}
.bed-grid{display:flex;flex-wrap:wrap;gap:10px}
.ward-block{background:#f8fafc;border:1px solid #e2e8f0;border-radius:11px;padding:14px 16px;margin-bottom:14px}
.ward-hdr{display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap}
.floor-banner{background:linear-gradient(135deg,#1e293b,#334155);color:#fff;border-radius:9px;padding:9px 18px;margin-bottom:14px;display:flex;align-items:center;gap:10px}
.floor-section{margin-bottom:26px}
.stat-box{border-radius:11px;padding:14px 10px;text-align:center}
.stat-box .sn{font-size:1.75rem;font-weight:800;line-height:1}
.stat-box .sl{font-size:.75rem;margin-top:3px;opacity:.85}
.bed-card.smatch{outline:3px solid #6366f1;outline-offset:2px}
.fpill{border:none;border-radius:20px;padding:4px 12px;font-size:.8rem;font-weight:600;cursor:pointer;opacity:.7;transition:opacity .15s,box-shadow .15s}
.fpill:hover,.fpill.active{opacity:1;box-shadow:0 2px 8px rgba(0,0,0,.15)}
</style>

<div class="card">
<div class="card-header d-flex align-items-center flex-wrap gap-2 justify-content-between">
    <h3 class="card-title mb-0">
        <i class="bi bi-geo-alt-fill me-1 text-danger"></i>
        Bed Status &mdash; Ward &amp; Floor Map
    </h3>
    <button class="btn btn-sm btn-light" type="button"
            onclick="load_form_div('<?= base_url('setting/admin/bed-management') ?>','maindiv','Bed Management');">
        <i class="bi bi-arrow-left"></i> Back
    </button>
</div>
<div class="card-body">

    <!-- Summary stats -->
    <?php
    $sc = [
        ['key'=>'total',      'label'=>'Total Beds', 'bg'=>'#1e293b','clr'=>'#fff'],
        ['key'=>'available',  'label'=>'Available',  'bg'=>'#dcfce7','clr'=>'#166534'],
        ['key'=>'occupied',   'label'=>'Occupied',   'bg'=>'#fee2e2','clr'=>'#991b1b'],
        ['key'=>'reserved',   'label'=>'Reserved',   'bg'=>'#fef3c7','clr'=>'#92400e'],
        ['key'=>'maintenance','label'=>'Maint.',     'bg'=>'#ede9fe','clr'=>'#4c1d95'],
        ['key'=>'cleaning',   'label'=>'Cleaning',   'bg'=>'#e0f2fe','clr'=>'#0c4a6e'],
        ['key'=>'blocked',    'label'=>'Blocked',    'bg'=>'#f1f5f9','clr'=>'#334155'],
    ];
    ?>
    <div class="row g-2 mb-3">
        <?php foreach ($sc as $s): ?>
        <div class="col-6 col-sm-4 col-md-3 col-xl">
            <div class="stat-box" style="background:<?= esc($s['bg']) ?>;color:<?= esc($s['clr']) ?>">
                <div class="sn"><?= (int)(($stats ?? [])[$s['key']] ?? 0) ?></div>
                <div class="sl"><?= esc($s['label']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Controls -->
    <div class="row g-2 align-items-center mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="bedSearch"
                       placeholder="Search patient name…" autocomplete="off">
                <button class="btn btn-outline-secondary" type="button" id="bedSearchClear">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="bedWardFilter">
                <option value="">All Wards</option>
                <?php foreach (($wards ?? []) as $wId => $wName): ?>
                <option value="<?= (int)$wId ?>"><?= esc((string)$wName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto">
            <div class="d-flex gap-1 flex-wrap">
                <button class="fpill active" data-status="" style="background:#e2e8f0;color:#334155"
                        onclick="bedStatus2(this,'')">All</button>
                <button class="fpill" data-status="available" style="background:#dcfce7;color:#166534"
                        onclick="bedStatus2(this,'available')">Available</button>
                <button class="fpill" data-status="occupied" style="background:#fee2e2;color:#991b1b"
                        onclick="bedStatus2(this,'occupied')">Occupied</button>
                <button class="fpill" data-status="reserved" style="background:#fef3c7;color:#92400e"
                        onclick="bedStatus2(this,'reserved')">Reserved</button>
                <button class="fpill" data-status="under_maintenance" style="background:#ede9fe;color:#4c1d95"
                        onclick="bedStatus2(this,'under_maintenance')">Maint.</button>
                <button class="fpill" data-status="cleaning" style="background:#e0f2fe;color:#0c4a6e"
                        onclick="bedStatus2(this,'cleaning')">Cleaning</button>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="d-flex flex-wrap gap-3 mb-3 align-items-center" style="font-size:.8rem;color:#6b7280">
        <span>Legend:</span>
        <?php foreach ($statusMeta as $sk => $sm): ?>
        <span class="d-inline-flex align-items-center gap-1">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;border:2px solid <?= esc($statusBorderColor[$sk] ?? '#94a3b8') ?>"></span>
            <?= esc($sm['label']) ?>
        </span>
        <?php endforeach; ?>
    </div>

    <!-- Bed map -->
    <div id="bedMapContainer">
        <?php if (empty($floors ?? [])): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No active beds found. Add beds in <strong>Bed Master</strong>.
        </div>
        <?php endif; ?>

        <?php foreach (($floors ?? []) as $floorNo => $wardsData): ?>
        <?php
            $fTotal = 0; $fFree = 0;
            foreach ($wardsData as $wd) {
                $fTotal += $wd['count']['total'];
                $fFree  += $wd['count']['available'];
            }
        ?>
        <div class="floor-section" data-floor="<?= (int)$floorNo ?>">
            <div class="floor-banner">
                <i class="bi bi-building"></i>
                <strong><?= esc($floorLabel((int)$floorNo)) ?></strong>
                <span class="badge bg-light text-dark ms-1"><?= (int)$fTotal ?> beds</span>
                <span class="badge bg-success ms-1"><?= (int)$fFree ?> free</span>
                <span class="badge bg-danger ms-1"><?= (int)($fTotal - $fFree) ?> busy</span>
            </div>

            <?php foreach ($wardsData as $wardId => $wardData): ?>
            <div class="ward-block" data-wid="<?= (int)$wardId ?>">
                <div class="ward-hdr">
                    <i class="bi bi-diagram-3 text-muted"></i>
                    <strong><?= esc((string)$wardData['ward_name']) ?></strong>
                    <?php if (!empty($wardData['ward_type'])): ?>
                    <span class="badge bg-light text-dark border"><?= esc((string)$wardData['ward_type']) ?></span>
                    <?php endif; ?>
                    <span class="badge" style="background:#dcfce7;color:#166534"><?= (int)$wardData['count']['available'] ?> Free</span>
                    <span class="badge" style="background:#fee2e2;color:#991b1b"><?= (int)$wardData['count']['occupied'] ?> Occupied</span>
                    <span class="badge bg-secondary"><?= (int)$wardData['count']['total'] ?> Total</span>
                </div>

                <div class="bed-grid">
                    <?php foreach ($wardData['beds'] as $bed):
                        $st  = (string)($bed['bed_status'] ?? 'available');
                        $sm  = $statusMeta[$st] ?? ['label'=>ucfirst(str_replace('_',' ',$st)),'css'=>'bed-blocked','badge'=>'badge-blk'];
                        $pat = (string)($bed['patient_name'] ?? '');
                        $doc = (string)($bed['doctor_name'] ?? '');
                        $days = $bed['days_admitted'] ?? null;
                        $isOcc = ($st === 'occupied' && $pat !== '');
                    ?>
                    <div class="bed-card <?= esc($sm['css']) ?>"
                         data-status="<?= esc($st) ?>"
                         data-wid="<?= (int)$wardId ?>"
                         data-patient="<?= esc(mb_strtolower($pat)) ?>"
                         title="<?= esc($isOcc ? $pat . ($doc !== '' ? ' · Dr. '.$doc : '') : $sm['label']) ?>">

                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="bed-num"><?= esc((string)($bed['bed_number'] ?? '-')) ?></span>
                            <span class="bed-sbadge <?= esc($sm['badge']) ?>"><?= esc($sm['label']) ?></span>
                        </div>

                        <?php if ($isOcc): ?>
                        <div class="bed-info">
                            <span class="bed-txt"><i class="bi bi-person-fill" style="color:#ef4444"></i> <?= esc($pat) ?></span>
                            <?php if ($doc !== ''): ?>
                            <span class="bed-txt"><i class="bi bi-stethoscope" style="color:#3b82f6"></i> <?= esc($doc) ?></span>
                            <?php endif; ?>
                            <?php if ($days !== null): ?>
                            <span class="bed-txt"><i class="bi bi-calendar2-check text-muted"></i>
                                <?= (int)$days ?> day<?= $days !== 1 ? 's' : '' ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($st === 'available'): ?>
                        <div class="bed-info" style="color:#16a34a">
                            <span class="bed-txt"><i class="bi bi-check-circle-fill"></i> Ready</span>
                        </div>
                        <?php elseif ($st === 'reserved'): ?>
                        <div class="bed-info" style="color:#d97706">
                            <span class="bed-txt"><i class="bi bi-clock-fill"></i> Reserved</span>
                        </div>
                        <?php elseif ($st === 'cleaning'): ?>
                        <div class="bed-info" style="color:#0284c7">
                            <span class="bed-txt"><i class="bi bi-droplet-fill"></i> Cleaning</span>
                        </div>
                        <?php elseif ($st === 'under_maintenance'): ?>
                        <div class="bed-info" style="color:#6366f1">
                            <span class="bed-txt"><i class="bi bi-tools"></i> Maintenance</span>
                        </div>
                        <?php else: ?>
                        <div class="bed-info" style="color:#475569">
                            <span class="bed-txt"><i class="bi bi-x-octagon-fill"></i> Blocked</span>
                        </div>
                        <?php endif; ?>

                        <?php if ($bed['has_oxygen'] || $bed['has_monitor'] || $bed['has_ventilator'] || $bed['is_isolation']): ?>
                        <div class="bed-feats">
                            <?php if ($bed['has_oxygen']): ?>
                            <span class="feat-tag" title="Oxygen Supply">O&#8322;</span>
                            <?php endif; ?>
                            <?php if ($bed['has_monitor']): ?>
                            <span class="feat-tag" title="Bedside Monitor">MON</span>
                            <?php endif; ?>
                            <?php if ($bed['has_ventilator']): ?>
                            <span class="feat-tag" title="Ventilator">VENT</span>
                            <?php endif; ?>
                            <?php if ($bed['is_isolation']): ?>
                            <span class="feat-tag" title="Isolation Bed">ISO</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div><!-- .bed-card -->
                    <?php endforeach; ?>
                </div><!-- .bed-grid -->
            </div><!-- .ward-block -->
            <?php endforeach; ?>

        </div><!-- .floor-section -->
        <?php endforeach; ?>

    </div><!-- #bedMapContainer -->

    <div id="bedNoResults" class="alert alert-warning d-none mt-2">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No beds match your search / filter.
    </div>

</div><!-- .card-body -->
</div><!-- .card -->

<script>
(function () {
    'use strict';
    var activeStatus = '';

    function doFilter() {
        var q   = (document.getElementById('bedSearch').value || '').toLowerCase().trim();
        var wid = document.getElementById('bedWardFilter').value;
        var any = false;

        document.querySelectorAll('.bed-card').forEach(function (c) {
            var ok = (!q || c.dataset.patient.indexOf(q) !== -1)
                  && (!activeStatus || c.dataset.status === activeStatus)
                  && (!wid || c.dataset.wid === wid);
            c.style.display = ok ? '' : 'none';
            c.classList.toggle('smatch', ok && q !== '' && c.dataset.patient.indexOf(q) !== -1);
            if (ok) { any = true; }
        });

        document.querySelectorAll('.ward-block').forEach(function (wb) {
            var v = Array.from(wb.querySelectorAll('.bed-card')).some(function (c) { return c.style.display !== 'none'; });
            wb.style.display = v ? '' : 'none';
        });

        document.querySelectorAll('.floor-section').forEach(function (fs) {
            var v = Array.from(fs.querySelectorAll('.ward-block')).some(function (w) { return w.style.display !== 'none'; });
            fs.style.display = v ? '' : 'none';
        });

        var nr = document.getElementById('bedNoResults');
        if (nr) { nr.classList.toggle('d-none', any); }
    }

    window.bedStatus2 = function (btn, st) {
        activeStatus = st;
        document.querySelectorAll('.fpill').forEach(function (b) { b.classList.toggle('active', b === btn); });
        doFilter();
    };

    document.getElementById('bedSearch').addEventListener('input', doFilter);
    document.getElementById('bedWardFilter').addEventListener('change', doFilter);
    document.getElementById('bedSearchClear').addEventListener('click', function () {
        document.getElementById('bedSearch').value = '';
        doFilter();
    });
}());
</script>