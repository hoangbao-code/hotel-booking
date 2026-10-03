<?php
/**
 * CỔNG CHUYỂN HƯỚNG ĐĂNG NHẬP ADMIN
 * File: admin/login.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/functions.php';

// Tự động chuyển hướng sang trang đăng nhập chính với chế độ chuyển quyền (switch=1)
redirect(base_url('login.php?switch=1'));
