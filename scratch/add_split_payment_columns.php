<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

// Check and add columns to mst_sales
$salesCols = [
    'bank_name' => "ALTER TABLE mst_sales ADD COLUMN bank_name varchar(100) DEFAULT '' AFTER payment_reference",
    'upi_ref_no' => "ALTER TABLE mst_sales ADD COLUMN upi_ref_no varchar(100) DEFAULT '' AFTER bank_name",
    'card_ref_no' => "ALTER TABLE mst_sales ADD COLUMN card_ref_no varchar(100) DEFAULT '' AFTER upi_ref_no",
    'is_bank_reconciled' => "ALTER TABLE mst_sales ADD COLUMN is_bank_reconciled tinyint(1) DEFAULT 0 AFTER card_ref_no",
    'reconciled_at' => "ALTER TABLE mst_sales ADD COLUMN reconciled_at datetime NULL AFTER is_bank_reconciled",
    'reconciled_by' => "ALTER TABLE mst_sales ADD COLUMN reconciled_by varchar(100) DEFAULT '' AFTER reconciled_at",
    'reconciled_notes' => "ALTER TABLE mst_sales ADD COLUMN reconciled_notes text NULL AFTER reconciled_by",
];

foreach ($salesCols as $col => $sql) {
    $res = $db->query("SHOW COLUMNS FROM mst_sales LIKE '$col'");
    if ($res->num_rows == 0) {
        $db->query($sql);
        echo "Added column $col to mst_sales.\n";
    } else {
        echo "Column $col already exists in mst_sales.\n";
    }
}

// Check and add columns to mst_ledger_entries
$ledgerCols = [
    'reconciled_flag' => "ALTER TABLE mst_ledger_entries ADD COLUMN reconciled_flag tinyint(1) DEFAULT 0 AFTER narration",
    'reconciled_at' => "ALTER TABLE mst_ledger_entries ADD COLUMN reconciled_at datetime NULL AFTER reconciled_flag",
    'reconciled_ref' => "ALTER TABLE mst_ledger_entries ADD COLUMN reconciled_ref varchar(100) DEFAULT '' AFTER reconciled_at",
];

foreach ($ledgerCols as $col => $sql) {
    $res = $db->query("SHOW COLUMNS FROM mst_ledger_entries LIKE '$col'");
    if ($res->num_rows == 0) {
        $db->query($sql);
        echo "Added column $col to mst_ledger_entries.\n";
    } else {
        echo "Column $col already exists in mst_ledger_entries.\n";
    }
}
