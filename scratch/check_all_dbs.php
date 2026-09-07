<?php
$conn = new mysqli('localhost', 'root', '');
$res = $conn->query("SHOW DATABASES");
echo "DATABASES ON LOCALHOST:\n";
while ($row = $res->fetch_assoc()) {
    $dbName = $row['Database'];
    echo "- " . $dbName;
    
    // Check if dbName has ipd_master
    $dbConn = @new mysqli('localhost', 'root', '', $dbName);
    if (!$dbConn->connect_error) {
        $check = $dbConn->query("SHOW TABLES LIKE 'ipd_master'");
        if ($check && $check->num_rows > 0) {
            $cnt = $dbConn->query("SELECT count(*) as c FROM ipd_master")->fetch_assoc()['c'];
            echo " (has ipd_master with $cnt rows)";
            $rec009 = $dbConn->query("SELECT id, ipd_code, p_id, r_doc_id, r_doc_name FROM ipd_master WHERE ipd_code LIKE '%0009%' OR ipd_code LIKE '%009%'");
            if ($rec009 && $rec009->num_rows > 0) {
                echo " *** FOUND 0009! ***\n";
                while ($r = $rec009->fetch_assoc()) {
                    print_r($r);
                }
            }
        }
    }
    echo "\n";
}
