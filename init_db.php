<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$dbName = getenv('DB_NAME') ?: 'secondlab';
$bootstrapPdo = getDbBootstrapConnection();
$bootstrapPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$pdo = getDb();
$sql = file_get_contents(__DIR__ . '/for database.sql');

if ($sql === false) {
    exit('Unable to read SQL file.');
}

$pdo->exec($sql);
echo 'Database initialized successfully.';
