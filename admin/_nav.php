<?php
/**
 * THANH ĐIỀU HƯỚNG PHÂN HỆ ADMIN
 * File: admin/_nav.php
 */

declare(strict_types=1);

$currentAdminPage = $adminPage ?? '';
?>
<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; background: #ffffff; padding: 1.25rem 1.5rem; border-radius: var(--radius); border: 1px solid var(--border);">
    <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">
            HỆ THỐNG QUẢN TRỊ KHÁCH SẠN (PMS)
        </div>
        <h1 style="font-size: 1.45rem; font-weight: 700; color: var(--navy); margin-top: 0.15rem; margin-bottom: 0.2rem;">
            Bảng Điều Khiển Lễ Tân & Quản Lý
        </h1>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 0;">
            Tiếp nhận đặt phòng, kiểm soát buồng phòng thời gian thực và quản lý loại phòng
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= base_url('index.php') ?>" class="btn btn--outline btn--sm" target="_blank">
            Xem trang khách hàng &rarr;
        </a>
        <a href="<?= base_url('logout.php') ?>" class="btn btn--danger btn--sm">
            Đăng xuất
        </a>
    </div>
</div>

<nav class="admin-nav" aria-label="Menu quản trị">
    <a href="<?= base_url('admin/index.php') ?>" class="admin-nav__link <?= $currentAdminPage === 'dashboard' ? 'active admin-nav__link--active' : '' ?>">
        <?= icon('calendar', '', 14) ?> Tổng quan & Sơ đồ phòng
    </a>
    <a href="<?= base_url('admin/bookings.php') ?>" class="admin-nav__link <?= $currentAdminPage === 'bookings' ? 'active admin-nav__link--active' : '' ?>">
        <?= icon('check-circle', '', 14) ?> Quản lý đặt phòng
    </a>
    <a href="<?= base_url('admin/room_types.php') ?>" class="admin-nav__link <?= $currentAdminPage === 'room_types' ? 'active admin-nav__link--active' : '' ?>">
        <?= icon('bed', '', 14) ?> Quản lý loại phòng
    </a>
    <a href="<?= base_url('admin/rooms.php') ?>" class="admin-nav__link <?= $currentAdminPage === 'rooms' ? 'active admin-nav__link--active' : '' ?>">
        <?= icon('settings', '', 14) ?> Quản lý phòng & bảo trì
    </a>
</nav>
