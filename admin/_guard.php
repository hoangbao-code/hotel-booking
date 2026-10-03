<?php
/**
 * BẢO VỆ PHÂN HỆ QUẢN TRỊ (ADMIN GUARD)
 * File: admin/_guard.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// Kiểm tra phiên đăng nhập và vai trò quản trị viên (Quy chuẩn Server-Side 403 Forbidden)
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    $isCustomer = !empty($_SESSION['user_id']);
    $currentName = $_SESSION['user_name'] ?? '';
    $currentEmail = $_SESSION['user_email'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 - Quyền Truy Cập Bị Từ Chối | Grand Oasis Hotel</title>
        <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    </head>
    <body style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: var(--bg); font-family: var(--font); padding: 1.5rem;">
        <div class="card" style="max-width: 500px; width: 100%; text-align: center; padding: 2.5rem 2rem; border: 1px solid var(--border);">
            <div style="width: 48px; height: 48px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <?= icon('alert', '', 24) ?>
            </div>
            <h1 style="font-size: 1.45rem; font-weight: 700; color: var(--navy); margin-bottom: 0.5rem;">
                403 - Quyền Truy Cập Bị Từ Chối
            </h1>
            
            <?php if ($isCustomer): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.88rem; color: #991b1b; text-align: left;">
                    Bạn hiện đang đăng nhập với tư cách Khách hàng: <strong><?= e($currentName) ?></strong> (<code><?= e($currentEmail) ?></code>).<br>
                    Tài khoản khách hàng không thể truy cập phân hệ Quản trị.
                </div>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5; font-size: 0.9rem;">
                    Vui lòng bấm nút bên dưới để chuyển sang đăng nhập tài khoản Quản trị viên (Admin).
                </p>
            <?php else: ?>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5; font-size: 0.9rem;">
                    Khu vực này chỉ dành riêng cho Quản trị viên của khách sạn. Vui lòng đăng nhập bằng tài khoản Admin để tiếp tục.
                </p>
            <?php endif; ?>

            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?= base_url('login.php?switch=1') ?>" class="btn btn--primary btn--sm">
                    Đăng nhập tài khoản Admin
                </a>
                <?php if ($isCustomer): ?>
                    <a href="<?= base_url('logout.php') ?>" class="btn btn--outline btn--sm">
                        Đăng xuất
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('index.php') ?>" class="btn btn--secondary btn--sm">
                    Về trang chủ
                </a>
            </div>

            <!-- Gợi ý thông tin tài khoản demo -->
            <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed var(--border); font-size: 0.8rem; color: var(--text-muted);">
                Tài khoản Admin mẫu: <code>admin@hotel.vn</code> / <code>admin123</code>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
