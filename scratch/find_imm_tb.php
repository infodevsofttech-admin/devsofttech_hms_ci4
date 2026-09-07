<?php
$lines = file(__DIR__ . '/../app/Views/abdm/task_board.php');
foreach ($lines as $i => $l) {
    if (stripos($l, 'immunization') !== false) {
        echo ($i + 1) . ': ' . trim($l) . PHP_EOL;
    }
}
