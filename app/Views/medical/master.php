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
    <div class="card mb-3">
        <div class="card-body pt-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input type="text" id="medical-master-search" class="form-control" placeholder="Search links (e.g. exp, gst, stock, invoice, bank)" list="medical-master-suggestions" autocomplete="off">
                    <datalist id="medical-master-suggestions"></datalist>
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="medical-master-clear">Clear</button>
                </div>
            </div>
            <small class="text-muted d-block mt-2">Type a keyword to filter links. Matching links are highlighted.</small>
            <div id="medical-master-search-empty" class="alert alert-warning mt-2 mb-0 py-2" style="display:none;">No matching links found.</div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Master</div>
                <div class="card-body pt-3">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/purchase_invoice_report') ?>','medical-main','Purchase Invoice :Pharmacy');">Purchase Invoice</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/supplier_account') ?>','medical-main','Supplier Account :Pharmacy');">Supplier Account</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Payment_Medical') ?>','medical-main','Med. Payment Edit :Pharmacy');">Med. Payment Edit</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Payment_Medical/payment_log') ?>','medical-main','Med. Payment Edit Logs :Pharmacy');">Med. Payment Edit Logs</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/invoice_item_log') ?>','medical-main','Med. Invoice Item Update Logs :Pharmacy');">Med. Invoice Item Update Logs</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/store_stock') ?>','medical-main','Stock Report :Pharmacy');">Stock Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/stock_details') ?>','medical-main','Item Stock Statement :Pharmacy');">Item Stock Statement</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/ipd_discharge') ?>','medical-main','IPD Discharge Report :Pharmacy');">IPD Discharge Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_daily_med_sale') ?>','medical-main','Day wise Medicine Sale :Pharmacy');">Day wise Medicine Sale</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_daily_med_sale_doc') ?>','medical-main','Day wise & Doc Wise Medicine Sale :Pharmacy');">Day wise &amp; Doc Wise Medicine Sale</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_company_med_sale') ?>','medical-main','Company Wise Medicine :Pharmacy');">Company Wise Medicine</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/expire_stock') ?>','medical-main','Expiry Medicine :Pharmacy');">Expiry Medicine</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/master_report/lost-medicine') ?>','medical-main','Lost Medicine :Pharmacy');">Lost Medicine</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/Short_medicine') ?>','medical-main','Short Medicine List :Pharmacy');">Short Medicine List</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/stocktransfer') ?>','medical-main','Stock Transfer :Pharmacy');">Stock Transfer</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/merge_product') ?>','medical-main','Product Merge :Pharmacy');">Product Merge</a>
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
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/Invoice_Med_Draft') ?>','medical-main','Old Invoice :Pharmacy');">Old Invoice</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_4') ?>','medical-main','IPD Sale Report :Pharmacy');">IPD Sale Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_backpanel/Report_IPD_CreditBills') ?>','medical-main','IPD Invoice Bill Type :Pharmacy');">IPD Invoice Bill Type</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_backpanel/Org_Bills') ?>','medical-main','OPD Org Report :Pharmacy');">OPD Org Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/master_report/opd-cash-pending') ?>','medical-main','OPD CASH Pending Report :Pharmacy');">OPD CASH Pending Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/master_report/opd-old-balance-paid') ?>','medical-main','OPD Old Balance Paid Report :Pharmacy');">OPD Old Balance Paid Report</a>
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
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_5') ?>','medical-main','Invoice List for GST :Pharmacy');">Invoice List for GST</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_3') ?>','medical-main','GST Report :Pharmacy');">GST Report</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical/master_report/bank-ledger') ?>','medical-main','Bank Ledger :Pharmacy');">Bank Ledger</a>
                        </li>
                        <li class="list-group-item px-0">
                            <a class="medical-master-link" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_gst_1') ?>','medical-main','Purchase GST Report :Pharmacy');">Purchase GST Report</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div id="medical-main" class="mt-3"></div>
</section>

<style>
    .medical-master-match {
        background-color: #fff8d9;
        border-radius: 4px;
    }
</style>

<script>
    (function () {
        var searchInput = document.getElementById('medical-master-search');
        var clearBtn = document.getElementById('medical-master-clear');
        var emptyBox = document.getElementById('medical-master-search-empty');
        var suggestBox = document.getElementById('medical-master-suggestions');
        if (!searchInput || !clearBtn || !emptyBox || !suggestBox) {
            return;
        }

        var keywordMap = {
            'Purchase Invoice': 'purchase bill buy vendor supplier invoice pi',
            'Supplier Account': 'supplier vendor ledger account payable',
            'Med. Payment Edit': 'payment edit correction cash bank',
            'Med. Payment Edit Logs': 'payment logs audit history',
            'Med. Invoice Item Update Logs': 'invoice item logs update history',
            'Stock Report': 'stock inventory qty quantity balance',
            'Item Stock Statement': 'stock statement item inventory detail',
            'IPD Discharge Report': 'ipd discharge in patient report',
            'Day wise Medicine Sale': 'day daily date wise sale',
            'Day wise & Doc Wise Medicine Sale': 'doctor doc day wise sale',
            'Company Wise Medicine': 'company manufacturer medicine sale',
            'Expiry Medicine': 'expiry exp expired near expiry',
            'Lost Medicine': 'lost missing damage wastage',
            'Short Medicine List': 'short shortage out of stock low stock',
            'Stock Transfer': 'transfer move stock branch',
            'Product Merge': 'merge duplicate combine product',
            'Old Invoice': 'old previous invoice',
            'IPD Sale Report': 'ipd sale report in patient',
            'IPD Invoice Bill Type': 'ipd bill type credit cash',
            'OPD Org Report': 'opd org organisation corporate',
            'OPD CASH Pending Report': 'opd cash pending due',
            'OPD Old Balance Paid Report': 'opd old balance paid receipt',
            'Invoice List for GST': 'gst invoice tax',
            'GST Report': 'gst tax summary',
            'Bank Ledger': 'bank ledger statement transaction',
            'Purchase GST Report': 'purchase gst tax input'
        };

        var links = Array.prototype.slice.call(document.querySelectorAll('.medical-master-link'));
        var cards = Array.prototype.slice.call(document.querySelectorAll('section.section .row.g-3 > .col-lg-4'));

        function normalize(text) {
            return (text || '').toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
        }

        function buildSuggestions() {
            var pool = {};
            links.forEach(function (link) {
                var title = link.textContent.trim();
                var keywords = keywordMap[title] || '';
                var hay = normalize(title + ' ' + keywords).split(' ');
                hay.forEach(function (word) {
                    if (word.length >= 3) {
                        pool[word] = true;
                    }
                });
            });

            Object.keys(pool).sort().slice(0, 120).forEach(function (term) {
                var option = document.createElement('option');
                option.value = term;
                suggestBox.appendChild(option);
            });
        }

        function applyFilter() {
            var q = normalize(searchInput.value);
            var hasAny = false;

            links.forEach(function (link) {
                var title = normalize(link.textContent);
                var keywords = normalize(keywordMap[link.textContent.trim()] || '');
                var haystack = title + ' ' + keywords;
                var matched = q === '' || haystack.indexOf(q) !== -1;

                var item = link.closest('li');
                if (!item) {
                    return;
                }

                item.style.display = matched ? '' : 'none';
                item.classList.toggle('medical-master-match', matched && q !== '');

                if (matched) {
                    hasAny = true;
                }
            });

            cards.forEach(function (cardCol) {
                var visibleItems = cardCol.querySelectorAll('li.list-group-item:not([style*="display: none"])');
                cardCol.style.display = visibleItems.length > 0 ? '' : 'none';
            });

            emptyBox.style.display = (!hasAny && q !== '') ? '' : 'none';
        }

        searchInput.addEventListener('input', applyFilter);
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            applyFilter();
            searchInput.focus();
        });

        buildSuggestions();
        applyFilter();
    })();
</script>
