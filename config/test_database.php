<?php
declare(strict_types=1);

$host = 'localhost';
$dbname = 'inuka_loan_app_test';
$username = 'root';
$password = '';

try {
    $testPdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    throw new RuntimeException(
        'Test database connection failed. Check your test database configuration.',
        0,
        $e
    );
}
