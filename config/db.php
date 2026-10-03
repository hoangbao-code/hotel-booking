<?php
/**
 * KẾT NỐI CƠ SỞ DỮ LIỆU MYSQL (PDO)
 * File: config/db.php
 * Thiết lập: ERRMODE_EXCEPTION, FETCH_ASSOC, EMULATE_PREPARES = false, charset utf8mb4
 */

declare(strict_types=1);

$dbHost = '127.0.0.1';
$dbName = 'hotel_db';
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbCharset}"
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Không echo lỗi PDO chi tiết ra màn hình người dùng
    error_log('Database Connection Error: ' . $e->getMessage());
    http_response_code(500);
    die('<h3>Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra lại dịch vụ MySQL trong XAMPP!</h3>');
}
