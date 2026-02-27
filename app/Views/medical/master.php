<div class="pagetitle">
    <h1>Pharmacy Master Panel</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Medical Store</li>
            <li class="breadcrumb-item active">Master</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Master</div>
                <div class="card-body pt-3">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/purchase_invoice_report') ?>','medical-main','Purchase Invoice :Pharmacy');">Purchase Invoice</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/supplier_account') ?>','medical-main','Supplier Account :Pharmacy');">Supplier Account</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Payment_Medical') ?>','medical-main','Med. Payment Edit :Pharmacy');">Med. Payment Edit</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Payment_Medical/payment_log') ?>','medical-main','Med. Payment Edit Logs :Pharmacy');">Med. Payment Edit Logs</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/invoice_item_log') ?>','medical-main','Med. Invoice Item Update Logs :Pharmacy');">Med. Invoice Item Update Logs</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/store_stock') ?>','medical-main','Stock Report :Pharmacy');">Stock Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/stock_details') ?>','medical-main','Item Stock Statement :Pharmacy');">Item Stock Statement</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/ipd_discharge') ?>','medical-main','IPD Discharge Report :Pharmacy');">IPD Discharge Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/Report_daily_med_sale') ?>','medical-main','Day wise Medicine Sale :Pharmacy');">Day wise Medicine Sale</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/Report_daily_med_sale_doc') ?>','medical-main','Day wise & Doc Wise Medicine Sale :Pharmacy');">Day wise &amp; Doc Wise Medicine Sale</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/Report_company_med_sale') ?>','medical-main','Company Wise Medicine :Pharmacy');">Company Wise Medicine</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/expire_stock') ?>','medical-main','Expiry Medicine :Pharmacy');">Expiry Medicine</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/master_report/lost-medicine') ?>','medical-main','Lost Medicine :Pharmacy');">Lost Medicine</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/Short_medicine') ?>','medical-main','Short Medicine List :Pharmacy');">Short Medicine List</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/stocktransfer') ?>','medical-main','Stock Transfer :Pharmacy');">Stock Transfer</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/merge_product') ?>','medical-main','Product Merge :Pharmacy');">Product Merge</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Invoice Master</div>
                <div class="card-body pt-3">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/Invoice_Med_Draft') ?>','medical-main','Old Invoice :Pharmacy');">Old Invoice</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/Report_4') ?>','medical-main','IPD Sale Report :Pharmacy');">IPD Sale Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_backpanel/Report_IPD_CreditBills') ?>','medical-main','IPD Invoice Bill Type :Pharmacy');">IPD Invoice Bill Type</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_backpanel/Org_Bills') ?>','medical-main','OPD Org Report :Pharmacy');">OPD Org Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/master_report/opd-cash-pending') ?>','medical-main','OPD CASH Pending Report :Pharmacy');">OPD CASH Pending Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/master_report/opd-old-balance-paid') ?>','medical-main','OPD Old Balance Paid Report :Pharmacy');">OPD Old Balance Paid Report</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">GST</div>
                <div class="card-body pt-3">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/Report_5') ?>','medical-main','Invoice List for GST :Pharmacy');">Invoice List for GST</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/Report_3') ?>','medical-main','GST Report :Pharmacy');">GST Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical/master_report/bank-ledger') ?>','medical-main','Bank Ledger :Pharmacy');">Bank Ledger</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a href="javascript:load_form_div('<?= base_url('Medical_Report/Report_gst_1') ?>','medical-main','Purchase GST Report :Pharmacy');">Purchase GST Report</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div id="medical-main" class="mt-3"></div>
</section>
