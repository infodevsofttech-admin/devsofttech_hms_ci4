<?php

require_once __DIR__ . '/../app/Helpers/age_helper.php';

$res = get_age_1('1979-03-28', '', '', 0);
echo "Result 1: '$res'\n";

$res2 = get_age_1('2026-07-16', '', '', 0);
echo "Result 2: '$res2'\n";

$res3 = get_age_1('2020-07-05', '', '', 0);
echo "Result 3: '$res3'\n";
