<?php
/**
 * ĐĂNG XUẤT HỆ THỐNG
 * File: logout.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';

// Xóa sạch session
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Khởi tạo lại session mới để gửi thông báo flash
session_start();
flash('success', 'Bạn đã đăng xuất khỏi hệ thống.');
redirect(base_url('index.php'));
