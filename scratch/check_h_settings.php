<?php
require 'd:/Workplace/HMS_CI4_OLD/vendor/codeigniter4/framework/system/Test/bootstrap.php';
$db = \Config\Database::connect('default');
$rows = $db->table('hospital_setting')->select('s_name, s_value')->like('s_name', 'H_')->orLike('s_name', 'ABDM')->get()->getResultArray();
foreach ($rows as $r) {
    echo $r['s_name'] . ' = ' . $r['s_value'] . PHP_EOL;
}
