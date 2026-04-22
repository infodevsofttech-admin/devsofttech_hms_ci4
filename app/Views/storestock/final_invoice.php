<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<div id="Medical_invoice_final">
<section class="content-header">
    <h1>Indent <small>No. : <?= esc($invoice_stock_master[0]->indent_code ?? '') ?></small></h1>
</section>
<section class="content">
    <?= csrf_field() ?>
    <div class="box box-danger">
        <div class="box-header">
            <div class="row">
                <input type="hidden" id="location_type" name="location_type" value="<?= $invoice_stock_master[0]->location_type ?? 0 ?>" />
                <input type="hidden" id="location_id"   name="location_id"   value="<?= $invoice_stock_master[0]->location_id ?? 0 ?>" />
                <input type="hidden" id="indent_id"     name="indent_id"     value="<?= $invoice_stock_master[0]->id ?? 0 ?>" />
                <?php if (($invoice_stock_master[0]->location_id ?? 0) > 0) { ?>
                <div class="col-md-12">
                    <p>
                        <strong>Location Name :</strong> <?= esc($invoice_stock_master[0]->issued_name) ?>
                        <strong>/ Indent No. :</strong> <?= esc($invoice_stock_master[0]->indent_code) ?>
                        <strong>/ Date :</strong> <?= date('d-m-Y', strtotime($invoice_stock_master[0]->indent_date)) ?>
                    </p>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="box-body">
            <div class="row" id="show_item_list">
                <div class="col-md-12">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Formulation</th>
                                <th>Batch No</th>
                                <th>Exp.</th>
                                <th>Rate</th>
                                <th>Qty.</th>
                                <th>Price</th>
                                <th>Disc.</th>
                                <th>HSNCODE</th>
                                <th>CGST</th>
                                <th>SGST</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $srno = 0;
                            foreach ($inv_items as $row) {
                                $srno++;
                                ?>
                                <tr>
                                    <td><?= $srno ?></td>
                                    <td><?= esc($row->item_code) ?></td>
                                    <td><?= esc($row->item_Name) ?></td>
                                    <td><?= esc($row->formulation) ?></td>
                                    <td><?= esc($row->batch_no) ?></td>
                                    <td><?= esc($row->expiry) ?></td>
                                    <td><?= $row->price ?></td>
                                    <td><?= $row->qty ?></td>
                                    <td><?= $row->amount ?></td>
                                    <td><?= $row->disc_amount ?></td>
                                    <td><?= esc($row->HSNCODE) ?></td>
                                    <td><?= $row->CGST ?? 0 ?></td>
                                    <td><?= $row->SGST ?? 0 ?></td>
                                    <td><?= $row->tamount ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="11">Gross Total</th>
                                <th><?= $invoiceGtotal[0]->Gtotal ?? 0 ?></th>
                                <th><?= $invoiceGtotal[0]->tamt ?? 0 ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="row no-print">
                <div class="col-xs-6">
                    <a href="/Storestock/print_single_indent/<?= $invoice_stock_master[0]->id ?>"
                       target="_blank" class="btn btn-default">
                        <i class="fa fa-print"></i> Print
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function () {
    document.title = 'Indent : <?= esc($invoice_stock_master[0]->issued_name ?? '') ?> / <?= esc($invoice_stock_master[0]->indent_code ?? '') ?>';
});
</script>
</div>
</div>
