<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #222; }
        .header { text-align: center; border-bottom: 2px solid #166534; padding-bottom: 8px; margin-bottom: 12px; }
        .header h2 { margin: 0; color: #166534; text-transform: uppercase; font-size: 17px; }
        .header p { margin: 2px 0 0; color: #555; font-size: 10px; }
        .title-bar { background-color: #f0fdf4; padding: 6px 10px; margin-bottom: 12px; border-left: 4px solid #166534; }
        .title-bar table { width: 100%; border-collapse: collapse; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10px; }
        .meta-table td { padding: 4px 6px; vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { background-color: #166534; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; text-transform: uppercase; border: 1px solid #166534; }
        .items-table td { padding: 5px 6px; border: 1px solid #cbd5e1; font-size: 9px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 40%; margin-left: auto; border-collapse: collapse; margin-bottom: 20px; }
        .totals-table td { padding: 4px 8px; border-bottom: 1px solid #cbd5e1; font-size: 10px; }
        .grand-total { font-size: 12px; font-weight: bold; background-color: #f0fdf4; color: #166534; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 33%; text-align: center; font-size: 10px; }
        .sig-line { border-top: 1px solid #333; margin-top: 30px; padding-top: 4px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Hospital Pharmacy Services</h2>
        <p>Inward Goods Physical Inspection &amp; Receiving Quality Control</p>
    </div>

    <div class="title-bar">
        <table>
            <tr>
                <td><strong style="font-size: 13px; color: #166534;">GOODS RECEIVED NOTE (GRN)</strong></td>
                <td class="text-end"><strong>GRN NO:</strong> <span style="font-family: monospace; font-size: 12px; color: #166534;"><?= esc($grn['grn_number']) ?></span></td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 50%; border: 1px solid #e2e8f0; padding: 6px;">
                <strong style="color: #64748b; font-size: 8px; text-transform: uppercase;">SUPPLIER DETAILS</strong><br>
                <strong style="font-size: 11px; color: #0f172a;"><?= esc($grn['name_supplier'] ?: 'Supplier') ?></strong><br>
                <?php $loc = implode(', ', array_filter([$grn['city'] ?? '', $grn['state'] ?? ''])); if (!empty($loc)): ?><?= esc($loc) ?><br><?php endif; ?>
                Phone: <?= esc($grn['contact_no'] ?: '-') ?><br>
                <?php if (!empty($grn['gst_no'])): ?>GSTIN: <?= esc($grn['gst_no']) ?><?php endif; ?>
            </td>
            <td style="width: 50%; border: 1px solid #e2e8f0; padding: 6px;">
                <strong style="color: #64748b; font-size: 8px; text-transform: uppercase;">DELIVERY METADATA</strong><br>
                <strong>Supplier Bill #:</strong> <?= esc($grn['supplier_invoice_no']) ?> | <strong>Bill Date:</strong> <?= date('d-M-Y', strtotime($grn['invoice_date'])) ?><br>
                <strong>GRN Receipt Date:</strong> <?= date('d-M-Y', strtotime($grn['grn_date'])) ?><br>
                <strong>Linked PO #:</strong> <?= esc($grn['po_number'] ?: 'Direct Delivery') ?><br>
                <strong>Status:</strong> <span style="text-transform: uppercase; font-weight: bold;"><?= esc($grn['status']) ?></span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 20px;">#</th>
                <th>Item Name</th>
                <th style="width: 65px;">Batch No</th>
                <th style="width: 55px;">Expiry</th>
                <th class="text-center" style="width: 35px;">Pack</th>
                <th class="text-center" style="width: 45px;">Rec. Qty</th>
                <th class="text-center" style="width: 35px;">Free</th>
                <th class="text-end" style="width: 55px;">Rate</th>
                <th class="text-end" style="width: 50px;">MRP</th>
                <th class="text-end" style="width: 55px;">Gross</th>
                <th class="text-center" style="width: 40px;">Disc%</th>
                <th class="text-end" style="width: 55px;">Taxable</th>
                <th class="text-center" style="width: 50px;">GST</th>
                <th class="text-end" style="width: 65px;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $pdfGross = 0.0;
                $pdfDisc = 0.0;
            ?>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $idx => $it): ?>
                    <?php 
                        $gross = (float)($it['gross_amount'] ?: ((float)$it['received_packs'] * (float)$it['unit_purchase_rate']));
                        $schPer = (float)($it['sch_disc_per'] ?? 0);
                        $schAmt = (float)($it['sch_disc_amt'] ?? 0);
                        $schTotal = ($schPer > 0 ? ($gross * $schPer / 100) : 0) + $schAmt;
                        $tradeDisc = (float)$it['discount_percent'];
                        $afterSch = max(0, $gross - $schTotal);
                        $tradeDiscAmt = ($tradeDisc > 0) ? ($afterSch * $tradeDisc / 100) : 0;
                        $rowDisc = $schTotal + $tradeDiscAmt;

                        $pdfGross += $gross;
                        $pdfDisc += $rowDisc;

                        $isInter = (!empty($grn['gst_type']) && $grn['gst_type'] === 'inter_state') || ((float)($it['igst_percent'] ?? 0) > 0);
                        $gstStr = $isInter
                            ? number_format((float)($it['igst_percent'] ?? 0), 0) . '% IGST'
                            : number_format((float)$it['cgst_percent'] + (float)$it['sgst_percent'], 0) . '%';
                    ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td><strong><?= esc($it['item_name']) ?></strong></td>
                        <td><?= esc($it['batch_no']) ?></td>
                        <td><?= date('d-M-Y', strtotime($it['expiry_date'])) ?></td>
                        <td class="text-center"><?= (int)$it['packing'] ?>s</td>
                        <td class="text-center"><strong><?= (float)$it['received_packs'] ?></strong></td>
                        <td class="text-center"><?= (float)$it['free_packs'] ?></td>
                        <td class="text-end">₹<?= number_format((float)$it['unit_purchase_rate'], 2) ?></td>
                        <td class="text-end">₹<?= number_format((float)$it['mrp'], 2) ?></td>
                        <td class="text-end">₹<?= number_format($gross, 2) ?></td>
                        <td class="text-center"><?= number_format($tradeDisc, 0) ?>%</td>
                        <td class="text-end">₹<?= number_format((float)$it['taxable_amount'], 2) ?></td>
                        <td class="text-center"><?= esc($gstStr) ?></td>
                        <td class="text-end">₹<?= number_format((float)$it['total_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal (Gross Amount):</td>
            <td class="text-end">₹<?= number_format($pdfGross, 2) ?></td>
        </tr>
        <tr>
            <td>Total Discount:</td>
            <td class="text-end">₹<?= number_format($pdfDisc, 2) ?></td>
        </tr>
        <tr>
            <td>Taxable Amount:</td>
            <td class="text-end">₹<?= number_format((float)$grn['total_received_amount'], 2) ?></td>
        </tr>
        <?php if (!empty($grn['gst_type']) && $grn['gst_type'] === 'inter_state'): ?>
            <tr>
                <td>IGST Amount:</td>
                <td class="text-end">₹<?= number_format((float)($grn['igst_amount'] ?? $grn['total_tax_amount']), 2) ?></td>
            </tr>
        <?php else: ?>
            <tr>
                <td>CGST Amount:</td>
                <td class="text-end">₹<?= number_format((float)($grn['cgst_amount'] ?? ((float)$grn['total_tax_amount'] / 2)), 2) ?></td>
            </tr>
            <tr>
                <td>SGST Amount:</td>
                <td class="text-end">₹<?= number_format((float)($grn['sgst_amount'] ?? ((float)$grn['total_tax_amount'] / 2)), 2) ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td>Total GST:</td>
            <td class="text-end">₹<?= number_format((float)$grn['total_tax_amount'], 2) ?></td>
        </tr>
        <?php if (isset($grn['round_off']) && (float)$grn['round_off'] != 0): ?>
            <tr>
                <td>Round Off:</td>
                <td class="text-end"><?= ((float)$grn['round_off'] > 0 ? '+' : '') . number_format((float)$grn['round_off'], 2) ?></td>
            </tr>
        <?php endif; ?>
        <tr class="grand-total">
            <td>Net Inward Bill Total:</td>
            <td class="text-end">₹<?= number_format((float)$grn['net_amount'], 2) ?></td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Physical Receiver (Store Assistant)</div>
            </td>
            <td>
                <div class="sig-line">Inspected &amp; Quality Checked (Pharmacist)</div>
            </td>
            <td>
                <div class="sig-line">Store In-Charge Approval</div>
            </td>
        </tr>
    </table>

</body>
</html>
