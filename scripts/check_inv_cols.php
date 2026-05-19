<?php
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__), 'env');
$dotenv->safeLoad();
$db = mysqli_connect(
    getenv('database.default.hostname') ?: 'localhost',
    getenv('database.default.username') ?: 'root',
    getenv('database.default.password') ?: '',
    getenv('database.default.database') ?: 'hms_data_ci4'
);
$r = mysqli_query($db, 'SHOW COLUMNS FROM investigation');
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
}
