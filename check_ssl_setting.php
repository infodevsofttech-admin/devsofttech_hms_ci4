<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SELECT s_name, s_value FROM hospital_setting WHERE s_name = 'ABDM_BRIDGE_SSL_VERIFY'");
while ($row = $res->fetch_assoc()) { print_r($row); }
$db->close();
