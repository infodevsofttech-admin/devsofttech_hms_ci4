<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #222; }
        .header { text-align: center; border-bottom: 2px solid #1a365d; padding-bottom: 8px; margin-bottom: 12px; }
        .header h2 { margin: 0; color: #1a365d; text-transform: uppercase; font-size: 18px; }
        .header p { margin: 2px 0 0; color: #555; font-size: 10px; }
        .title-bar { background-color: #f1f5f9; padding: 6px 10px; margin-bottom: 12px; border-left: 4px solid #1a365d; }
        .title-bar table { width: 100%; border-collapse: collapse; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11px; }
        .meta-table td { padding: 4px 6px; vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { background-color: #1a365d; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; border: 1px solid #1a365d; }
        .items-table td { padding: 6px 8px; border: 1px solid #cbd5e1; font-size: 10px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 45%; margin-left: auto; border-collapse: collapse; margin-bottom: 20px; }
        .totals-table td { padding: 4px 8px; border-bottom: 1px solid #cbd5e1; font-size: 11px; }
        .grand-total { font-size: 13px; font-weight: bold; background-color: #f8fafc; color: #1a365d; }
        .terms { border: 1px dashed #94a3b8; padding: 8px 12px; font-size: 9px; color: #475569; margin-bottom: 30px; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 33%; text-align: center; font-size: 10px; }
        .sig-line { border-top: 1px solid #333; margin-top: 35px; padding-top: 4px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Hospital Pharmacy Services</h2>
        <p>Central Medical Store &amp; Drug Supply Division | Drug Lic. 20B/21B</p>
    </div>

    <div class="title-bar">
        <table>
            <tr>
                <td><strong style="font-size: 14px; color: #1a365d;">PURCHASE ORDER</strong></td>
                <td class="text-end"><strong>PO NO:</strong> <span style="font-family: monospace; font-size: 13px; color: #1a365d;"><?= esc($po['po_number']) ?></span></td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 50%; border: 1px solid #e2e8f0; padding: 8px;">
                <strong style="color: #64748b; font-size: 9px; text-transform: uppercase;">VENDOR DETAILS</strong><br>
                <strong style="font-size: 12px; color: #0f172a;"><?= esc($po['name_supplier'] ?: 'Supplier') ?></strong><br>
                <?php $loc = implode(', ', array_filter([$po['city'] ?? '', $po['state'] ?? ''])); if (!empty($loc)): ?><?= esc($loc) ?><br><?php endif; ?>
                Phone: <?= esc($po['contact_no'] ?: '-') ?><br>
                <?php if (!empty($po['gst_no'])): ?>GSTIN: <?= esc($po['gst_no']) ?><br><?php endif; ?>
            </td>
            <td style="width: 50%; border: 1px solid #e2e8f0; padding: 8px;">
                <strong style="color: #64748b; font-size: 9px; text-transform: uppercase;">ORDER METADATA</strong><br>
                <strong>PO Date:</strong> <?= date('d-M-Y', strtotime($po['po_date'])) ?><br>
                <strong>Expected Delivery:</strong> <?= !empty($po['expected_delivery_date']) ? date('d-M-Y', strtotime($po['expected_delivery_date'])) : 'Immediate' ?><br>
                <strong>Payment Terms:</strong> <?= esc($po['payment_terms'] ?: 'Net 30 Days') ?><br>
                <strong>Status:</strong> <span style="text-transform: uppercase; font-weight: bold;"><?= esc($po['status']) ?></span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Item / Brand Name</th>
                <th>Salt Composition</th>
                <th class="text-center" style="width: 70px;">Pack Size</th>
                <th class="text-center" style="width: 70px;">Order Qty</th>
                <th class="text-end" style="width: 80px;">Rate/Pack</th>
                <th class="text-center" style="width: 50px;">GST %</th>
                <th class="text-end" style="width: 90px;">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td><strong><?= esc($it['item_name']) ?></strong></td>
                        <td><?= esc($it['generic_name'] ?: '-') ?></td>
                        <td class="text-center"><?= (int)$it['units_per_pack'] ?>s</td>
                        <td class="text-center"><strong><?= (float)$it['order_packs'] ?></strong></td>
                        <td class="text-end">₹<?= number_format((float)$it['estimated_ptr'], 2) ?></td>
                        <td class="text-center"><?= (float)$it['gst_rate'] ?>%</td>
                        <td class="text-end">₹<?= number_format((float)$it['estimated_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Estimated Subtotal:</td>
            <td class="text-end">₹<?= number_format((float)$po['total_estimated_amount'], 2) ?></td>
        </tr>
        <tr>
            <td>Estimated GST Tax:</td>
            <td class="text-end">₹<?= number_format((float)$po['tax_amount'], 2) ?></td>
        </tr>
        <tr class="grand-total">
            <td>Grand Total (INR):</td>
            <td class="text-end">₹<?= number_format((float)$po['grand_total'] ?: ((float)$po['total_estimated_amount'] + (float)$po['tax_amount']), 2) ?></td>
        </tr>
    </table>

    <div class="terms">
        <strong>TERMS &amp; CONDITIONS:</strong>
        <ol style="margin: 4px 0 0; padding-left: 15px;">
            <li>All supplies must match the specified brand, packing, and manufacturer specifications.</li>
            <li>Medicines must have at least 75% remaining shelf life from the date of manufacturing at the time of delivery.</li>
            <li>Deliveries must be accompanied by an original Tax Invoice and Delivery Challan referencing this PO number.</li>
            <li>Any broken, leaking, or damaged goods will be rejected at the time of Goods Received Note (GRN) verification.</li>
            <li>Cold chain items (2-8°C) must be delivered with unbroken temperature logs / ice packs.</li>
        </ol>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Prepared By (Pharmacist)</div>
            </td>
            <td>
                <div class="sig-line">Verified By (Store In-charge)</div>
            </td>
            <td>
                <div class="sig-line">Authorized Signatory</div>
            </td>
        </tr>
    </table>

</body>
</html>
