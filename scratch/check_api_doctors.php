<?php
$conn = new mysqli('localhost', 'root', '', 'hms_ci4_2026');

$res = $conn->query("SELECT id, p_title, p_fname, p_mname, p_lname, email1, mphone1 FROM doctor_master");
echo "DOCTORS IN doctor_master:\n";
while ($d = $res->fetch_assoc()) {
    print_r($d);
}
