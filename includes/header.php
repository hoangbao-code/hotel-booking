<?php
/**
 * HEADER DÙNG CHUNG TOÀN HỆ THỐNG
 * File: includes/header.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/functions.php';

$isLoggedIn = !empty($_SESSION['user_id']);
$userName   = $_SESSION['user_name'] ?? '';
$userRole   = $_SESSION['user_role'] ?? '';
$flashes    = take_flashes();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>Khách Sạn Grand Oasis Đà Nẵng</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

<!-- Thanh Topbar thông tin liên hệ -->
<div class="top-bar">
    <div class="container top-bar__inner">
        <div class="top-bar__item">
            <?= icon('map-pin', '', 14) ?>
            <span>123 Võ Nguyên Giáp, Phước Mỹ, Sơn Trà, TP. Đà Nẵng</span>
        </div>
        <div style="display: flex; gap: 1.25rem; align-items: center;">
            <div class="top-bar__item">
                <?= icon('phone', '', 14) ?>
                <span>Hotline: <strong>1900 6868</strong> (24/7)</span>
            </div>
            <div class="top-bar__item" style="color: #fef08a;">
                <?= icon('check-circle', '', 14) ?>
                <span>Thanh toán tại khách sạn khi nhận phòng</span>
            </div>
        </div>
    </div>
</div>

<!-- Header & Menu Điều Hướng Chính -->
<header class="site-header">
    <div class="container header-container">
        <a href="<?= base_url('index.php') ?>" class="site-logo">
            <span class="site-logo__mark">GO</span>
            <div class="site-logo__text-wrap">
                <span class="site-logo__title">GRAND OASIS</span>
                <span class="site-logo__subtitle">HOTEL & RESORT • DA NANG</span>
            </div>
        </a>

        <nav class="site-nav" aria-label="Menu điều hướng">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?= base_url('index.php') ?>" class="nav-link">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('rooms.php') ?>" class="nav-link">Phòng & Bảng giá</a>
                </li>

                <?php if (!$isLoggedIn): ?>
                    <li class="nav-item">
                        <a href="<?= base_url('login.php') ?>" class="nav-link">Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('register.php') ?>" class="btn btn--warning btn--sm">
                            Đăng ký
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?= base_url('my_bookings.php') ?>" class="nav-link">
                            Đặt phòng của tôi
                        </a>
                    </li>
                    <?php if ($userRole === 'admin'): ?>
                        <li class="nav-item">
                            <a href="<?= base_url('admin/index.php') ?>" class="nav-link nav-link--admin">
                                <?= icon('settings', '', 14) ?> Quản trị hệ thống
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item user-pill">
                        <?= icon('user', '', 14) ?>
                        <span><strong><?= e($userName) ?></strong></span>
                        <?php if ($userRole === 'admin'): ?>
                            <span class="badge badge--admin" style="font-size: 0.68rem; padding: 2px 4px;">Admin</span>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('logout.php') ?>" class="btn btn--outline btn--sm" style="color: #ffffff; border-color: rgba(255,255,255,0.3);" title="Đăng xuất khỏi hệ thống">
                            Đăng xuất
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<?php if (!empty($flashes)): ?>
    <div class="container flash-container" aria-live="polite">
        <?php foreach ($flashes as $f): ?>
            <div class="flash flash--<?= e($f['type']) ?>" role="status">
                <span class="flash__icon">
                    <?= $f['type'] === 'success' ? icon('check-circle', '', 18) : icon('alert', '', 18) ?>
                </span>
                <span class="flash__message"><?= e($f['msg']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main class="container main-content">
