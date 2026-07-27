<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$rows = $db->query("SELECT s_name, s_value FROM hospital_setting WHERE s_name IN ('EATRIA_BRIDGE_TOKEN','ABDM_HFR_ID')");
$settings = [];
while ($r = $rows->fetch_assoc()) {
    $settings[$r['s_name']] = $r['s_value'];
}
echo "Token present: " . (isset($settings['EATRIA_BRIDGE_TOKEN']) && $settings['EATRIA_BRIDGE_TOKEN'] !== '' ? 'yes (len=' . strlen($settings['EATRIA_BRIDGE_TOKEN']) . ')' : 'NO') . "\n";
echo "HFR ID: " . ($settings['ABDM_HFR_ID'] ?? 'NOT SET') . "\n";
$db->close();
