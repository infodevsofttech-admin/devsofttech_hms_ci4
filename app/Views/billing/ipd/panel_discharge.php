<?php
$discharge = $discharge_info[0] ?? null;
?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Discharge Status</h5>
                <p><strong>Status :</strong> <?= esc($discharge->status_desc ?? 'Pending') ?></p>
                <p><strong>Discharge Date :</strong> <?= esc($discharge->discharge_date ?? '') ?></p>
                <p><strong>Discharge Time :</strong> <?= esc($discharge->discharge_time ?? '') ?></p>
                <p><strong>Discharge By :</strong> <?= esc($discharge->discharge_by ?? '') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Remarks</h5>
                <p><strong>Remark :</strong> <?= esc($discharge->discharge_remark ?? '') ?></p>
                <p><strong>Balance User :</strong> <?= esc($discharge->discharge_balance_user ?? '') ?></p>
                <p><strong>Balance Remark :</strong> <?= esc($discharge->discharge_balance_remark ?? '') ?></p>
            </div>
        </div>
    </div>
</div>
