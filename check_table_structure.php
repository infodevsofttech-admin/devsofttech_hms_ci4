<?php
// Quick script to check table structure
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

// Bootstrap CI4
require __DIR__ . '/vendor/autoload.php';
$pathsConfig = new Config\Paths();
$bootstrap = \CodeIgniter\Boot::bootWeb($pathsConfig);
$app = $bootstrap->getCodeIgniter();

// Get database connection
$db = \Config\Database::connect();

// Check table creation SQL
$query = $db->query("SHOW CREATE TABLE ipd_discharge_templates");
$row = $query->getRowArray();

echo "Table Structure:\n";
echo "================\n\n";
echo $row['Create Table'] . "\n\n";

// Check for foreign key references
$query = $db->query("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
        REFERENCED_TABLE_NAME = 'ipd_discharge_templates'
        AND TABLE_SCHEMA = DATABASE()
");

echo "\nForeign Keys Referencing This Table:\n";
echo "====================================\n\n";
$results = $query->getResultArray();
if (count($results) > 0) {
    foreach ($results as $row) {
        echo "Table: {$row['TABLE_NAME']}\n";
        echo "Column: {$row['COLUMN_NAME']}\n";
        echo "Constraint: {$row['CONSTRAINT_NAME']}\n";
        echo "References: {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n\n";
    }
} else {
    echo "No foreign keys reference this table.\n";
}

