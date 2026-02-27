<!DOCTYPE html>
<html>
<head>
    <title>IPD Packing List - <?= esc($packing->label_no) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            @page { margin: 1cm; }
        }
        body { font-size: 12px; }
        .header-section {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .info-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 15px;
        }
        table { font-size: 11px; }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 30%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print Button -->
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print
            </button>
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>

        <!-- Header -->
        <div class="header-section">
            <h4>IPD TPA PACKING LIST</h4>
            <p class="mb-0">Label No: <strong><?= esc($packing->label_no) ?></strong> | 
               Date: <strong><?= date('d-m-Y', strtotime($packing->date_of_create)) ?></strong></p>
        </div>

        <!-- Packing Info -->
        <div class="info-box">
            <div class="row">
                <div class="col-6">
                    <strong>Total Cases:</strong> <?= count($cases) ?>
                </div>
                <div class="col-6 text-end">
                    <strong>Type:</strong> IPD TPA Cases
                </div>
            </div>
        </div>

        <!-- Cases Table -->
        <?php if (!empty($cases)): ?>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th style="width: 40px;">Sr.</th>
                    <th>Case Code</th>
                    <th>IPD Code</th>
                    <th>UHID</th>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Admit Date</th>
                    <th>Discharge Date</th>
                    <th>Insurance No</th>
                    <th>Claim Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sr = 1;
                $totalClaim = 0;
                foreach ($cases as $case): 
                    $totalClaim += (float)$case->claim_amt;
                ?>
                <tr>
                    <td><?= $sr++ ?></td>
                    <td><?= esc($case->case_id_code) ?></td>
                    <td><?= esc($case->ipd_code ?? '-') ?></td>
                    <td><?= esc($case->p_code) ?></td>
                    <td><?= esc($case->p_fname) ?></td>
                    <td><?= esc($case->age) ?></td>
                    <td><?= esc($case->admit_date ?? '-') ?></td>
                    <td><?= esc($case->discharge_date ?? '-') ?></td>
                    <td>
                        <?= esc($case->insurance_no) ?>
                        <?php if (!empty($case->insurance_no_1)): ?>
                            <br><small><?= esc($case->insurance_no_1) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= number_format($case->claim_amt, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="9" class="text-end">Total Claim Amount:</th>
                    <th class="text-end"><?= number_format($totalClaim, 2) ?></th>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div class="alert alert-warning">No cases in this packing</div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <strong>Prepared By</strong><br>
                <small><?= date('d-m-Y H:i') ?></small>
            </div>
            <div class="signature-box">
                <strong>Verified By</strong>
            </div>
            <div class="signature-box">
                <strong>Approved By</strong>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
