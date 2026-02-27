<div class="card">
    <div class="card-body pt-3">
        <div class="js-permission-alert"></div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h5 class="card-title mb-0">Already Created Today</h5>
            <a class="btn btn-sm btn-primary" href="javascript:load_form_div('<?= base_url('Medical/Invoice_counter_new/' . (int)$pno . '/' . (int)$ipd_id . '/' . (int)$case_id) ?>','medical-main','Medical Invoice');">Add New Invoice</a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Net Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $idx => $inv): ?>
                            <?php
                                $invId = (int)($inv->id ?? 0);
                                $editMeta = $invoiceEditMeta[$invId] ?? ['can_edit' => true, 'reason' => ''];
                                $canEditRow = !empty($editMeta['can_edit']);
                                $editReason = (string) ($editMeta['reason'] ?? 'Invoice is view-only.');
                            ?>
                            <tr class="table-warning">
                                <td><?= (int)$idx + 1 ?></td>
                                <td><?= esc($inv->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invId, -7, 7), 7, '0', STR_PAD_LEFT))) ?></td>
                                <td><?= esc($inv->inv_date ?? '-') ?></td>
                                <td class="text-end"><?= esc(number_format((float)($inv->net_amount ?? 0), 2)) ?></td>
                                <td>
                                    <?php if ($canEditRow): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="javascript:load_form_div('<?= base_url('Medical/invoice_edit/' . $invId) ?>','medical-main','Medical Invoice');">Edit</a>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-outline-primary" href="javascript:void(0);" title="<?= esc($editReason) ?>" onclick='return showMedicalPermissionAlert(this, <?= json_encode($editReason) ?>);'>Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5" class="p-0">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:50px;">#</th>
                                                <th>Item</th>
                                                <th>Batch/Exp.</th>
                                                <th class="text-end">Rate</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Net Amt.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $items = $itemsByInvoice[$invId] ?? []; ?>
                                            <?php if (!empty($items)): ?>
                                                <?php foreach ($items as $i => $item): ?>
                                                    <tr>
                                                        <td><?= (int)$i + 1 ?></td>
                                                        <td><?= esc($item->item_Name ?? '-') ?></td>
                                                        <td><?= esc($item->batch_no ?? '-') ?> / <?= esc(!empty($item->expiry) ? date('m-Y', strtotime((string)$item->expiry)) : '-') ?></td>
                                                        <td class="text-end"><?= esc(number_format((float)($item->price ?? 0), 2)) ?></td>
                                                        <td class="text-end"><?= esc(number_format((float)($item->qty ?? 0), 2)) ?></td>
                                                        <td class="text-end"><?= esc(number_format((float)($item->twdisc_amount ?? $item->tamount ?? 0), 2)) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">No items in this invoice.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No invoice found for today.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    if (typeof window.showMedicalPermissionAlert !== 'function') {
        window.showMedicalPermissionAlert = function (trigger, message) {
            var text = String(message || 'No permission to edit this invoice.');
            var root = trigger && trigger.closest ? trigger.closest('.card') : null;
            var host = root && root.querySelector ? root.querySelector('.js-permission-alert') : null;

            if (!host) {
                alert(text);
                return false;
            }

            host.innerHTML = '';

            var box = document.createElement('div');
            box.className = 'alert alert-warning alert-dismissible fade show py-2 mb-3';
            box.setAttribute('role', 'alert');

            var textNode = document.createElement('span');
            textNode.textContent = text;
            box.appendChild(textNode);

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('aria-label', 'Close');
            closeBtn.addEventListener('click', function () {
                if (box.parentNode) {
                    box.parentNode.removeChild(box);
                }
            });
            box.appendChild(closeBtn);

            host.appendChild(box);
            if (typeof host.scrollIntoView === 'function') {
                host.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            return false;
        };
    }
</script>
