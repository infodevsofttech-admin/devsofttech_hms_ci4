<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query('SELECT store_id, store_code, store_slug, store_name, security_key, current_otp, otp_expiry FROM mst_stores');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
$db->close();
