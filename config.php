<?php
session_start();

$dbHost = 'sql207.infinityfree.com';
$dbName = 'if0_42804269_db_Earl';
$dbUser = 'if0_42804269';
$dbPass = 'EarlJan123';

try {
    $pdo = new PDO(
        'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please update the database credentials in config.php.');
}

function redirectWithMessage($page, $status, $message)
{
    header('Location: ' . $page . '?status=' . $status . '&message=' . urlencode($message));
    exit;
}
