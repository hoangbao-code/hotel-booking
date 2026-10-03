<?php
/**
 * TRANG TỔNG QUAN QUẢN TRỊ (ADMIN DASHBOARD)
 * File: admin/index.php
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$pageTitle = 'Tổng quan quản trị';
$adminPage = 'dashboard';

// Truy vấn thống kê tổng thể
$statsSql = "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS count_pending,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) AS count_confirmed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS count_cancelled,
                COALESCE(SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END), 0) AS total_revenue
             FROM bookings";
$statsStmt = $pdo->query($statsSql);
$stats = $statsStmt->fetch();

$countPending   = (int)($stats['count_pending'] ?? 0);
$countConfirmed = (int)($stats['count_confirmed'] ?? 0);
$countCancelled = (int)($stats['count_cancelled'] ?? 0);
$totalRevenue   = (float)($stats['total_revenue'] ?? 0.0);

// Sơ đồ phòng thời gian thực (Room Matrix PMS)
$matrixSql = "SELECT r.id, r.room_number, r.status, rt.name AS room_type_name,
                     (
                         SELECT COUNT(*) FROM bookings b 
                         WHERE b.room_id = r.id 
                           AND b.status <> 'cancelled'
                           AND b.check_in <= CURDATE() 
                           AND b.check_out > CURDATE()
                     ) AS is_occupied_today
              FROM rooms r
              JOIN room_types rt ON r.room_type_id = rt.id
              ORDER BY r.room_number ASC";
$roomMatrix = $pdo->query($matrixSql)->fetchAll();

$countAvailableRooms = 0;
$countOccupiedRooms  = 0;
$countMaintRooms     = 0;

foreach ($roomMatrix as $rm) {
    if ($rm['status'] === 'maintenance') {
        $countMaintRooms++;
    } elseif ((int)$rm['is_occupied_today'] > 0) {
        $countOccupiedRooms++;
    } else {
        $countAvailableRooms++;
    }
}

// Lấy 5 đơn đặt phòng mới nhất
$recentSql = "SELECT b.id, b.check_in, b.check_out, b.guests, b.status, b.total_price, b.created_at,
                     u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                     r.room_number,
                     rt.name AS room_type_name
              FROM bookings b
              JOIN users u ON b.user_id = u.id
              JOIN rooms r ON b.room_id = r.id
              JOIN room_types rt ON r.room_type_id = rt.id
              ORDER BY b.created_at DESC
              LIMIT 5";
$recentStmt = $pdo->query($recentSql);
$recentBookings = $recentStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/_nav.php'; ?>

<!-- 4 Thẻ thống kê KPI -->
<div class="grid grid--stats" style="margin-bottom: 2rem;">
    <div class="stat-card" style="border-top: 3px solid #f59e0b;">
        <div class="stat-card__label" style="color: #92400e;">Đơn Chờ Xác Nhận</div>
        <div class="stat-card__value" style="color: #b45309;"><?= number_format($countPending) ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Cần kiểm tra và giữ phòng cho khách</div>
    </div>

    <div class="stat-card" style="border-top: 3px solid var(--success);">
        <div class="stat-card__label" style="color: #065f46;">Đơn Đã Xác Nhận</div>
        <div class="stat-card__value" style="color: var(--success);"><?= number_format($countConfirmed) ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Đã giữ chỗ, chuẩn bị đón khách</div>
    </div>

    <div class="stat-card" style="border-top: 3px solid var(--danger);">
        <div class="stat-card__label" style="color: #991b1b;">Đơn Đã Hủy</div>
        <div class="stat-card__value" style="color: var(--danger);"><?= number_format($countCancelled) ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Khách đã hủy hoặc admin hủy</div>
    </div>

    <div class="stat-card" style="border-top: 3px solid var(--primary);">
        <div class="stat-card__label" style="color: var(--primary);">Doanh Thu Dự Kiến</div>
        <div class="stat-card__value" style="color: var(--primary); font-size: 1.45rem;">
            <?= number_format($totalRevenue, 0, ',', '.') ?> ₫
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Tổng tiền từ các đơn đã xác nhận</div>
    </div>
</div>

<!-- SƠ ĐỒ BUỒNG PHÒNG THỜI GIAN THỰC (ROOM MATRIX PMS) -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card__body" style="border-bottom: 1px solid var(--border); padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 0.2rem;">
                Sơ Đồ Buồng Phòng Thời Gian Thực (Room Matrix)
            </h2>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">
                Tình trạng buồng phòng thực tế trong ngày hôm nay (<?= date('d/m/Y') ?>)
            </p>
        </div>
        <div style="display: flex; gap: 1rem; font-size: 0.8rem; font-weight: 600; align-items: center; flex-wrap: wrap;">
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #166534;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a;"></span>
                <?= $countAvailableRooms ?> Sẵn sàng đón khách
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #854d0e;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #ca8a04;"></span>
                <?= $countOccupiedRooms ?> Đang có khách
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #991b1b;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></span>
                <?= $countMaintRooms ?> Đang bảo trì
            </span>
            <a href="<?= base_url('admin/rooms.php') ?>" class="btn btn--outline btn--sm">
                Quản lý phòng &rarr;
            </a>
        </div>
    </div>

    <div style="padding: 1.25rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 0.75rem;">
            <?php foreach ($roomMatrix as $rm): 
                $isMaint = ($rm['status'] === 'maintenance');
                $isOccupied = ((int)$rm['is_occupied_today'] > 0);

                if ($isMaint) {
                    $borderTop = '#ef4444';
                    $bgTile = '#fef2f2';
                    $statusBadge = '<span class="badge badge--cancelled">Bảo trì</span>';
                } elseif ($isOccupied) {
                    $borderTop = '#f59e0b';
                    $bgTile = '#fffbeb';
                    $statusBadge = '<span class="badge badge--pending">Có khách</span>';
                } else {
                    $borderTop = '#10b981';
                    $bgTile = '#ffffff';
                    $statusBadge = '<span class="badge badge--confirmed">Sẵn sàng</span>';
                }
            ?>
                <div style="border: 1px solid var(--border); border-top: 3px solid <?= $borderTop ?>; background: <?= $bgTile ?>; border-radius: var(--radius-sm); padding: 0.75rem; text-align: center;">
                    <div style="font-weight: 700; font-size: 1.05rem; color: var(--navy);">Phòng <?= e($rm['room_number']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.4rem;"><?= e($rm['room_type_name']) ?></div>
                    <div><?= $statusBadge ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Bảng 5 đơn đặt phòng gần nhất -->
<div class="card">
    <div class="card__body" style="border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 0.2rem;">
                Đơn Đặt Phòng Mới Nhất
            </h2>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">5 lượt đặt phòng mới nhất được ghi nhận</p>
        </div>
        <a href="<?= base_url('admin/bookings.php') ?>" class="btn btn--outline btn--sm">
            Xem tất cả đơn &rarr;
        </a>
    </div>

    <div style="padding: 1.25rem;">
        <?php if (empty($recentBookings)): ?>
            <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                Chưa có đơn đặt phòng nào trong hệ thống.
            </div>
        <?php else: ?>
            <div class="table-responsive" style="margin-bottom: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Hạng phòng</th>
                            <th>Phòng</th>
                            <th>Thời gian lưu trú</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thời gian đặt</th>
                            <th style="text-align: center;">Voucher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $b): 
                            $status = (string)$b['status'];
                            $badgeClass = 'badge--pending';
                            $statusText = 'Chờ duyệt';
                            if ($status === 'confirmed') {
                                $badgeClass = 'badge--confirmed';
                                $statusText = 'Đã duyệt';
                            } elseif ($status === 'cancelled') {
                                $badgeClass = 'badge--cancelled';
                                $statusText = 'Đã hủy';
                            }
                        ?>
                            <tr>
                                <td><strong style="color: var(--primary-dark);">#BK-<?= (int)$b['id'] ?></strong></td>
                                <td>
                                    <strong><?= e($b['customer_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($b['customer_phone'] ?? $b['customer_email']) ?></small>
                                </td>
                                <td><?= e($b['room_type_name']) ?></td>
                                <td>
                                    <span class="badge" style="background:#f1f5f9; color:#334155; font-weight: 600;">
                                        Phòng <?= e($b['room_number']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><strong><?= date('d/m/Y', strtotime($b['check_in'])) ?></strong></div>
                                    <small style="color: var(--text-muted);">đến <?= date('d/m/Y', strtotime($b['check_out'])) ?></small>
                                </td>
                                <td style="font-weight: 700; color: var(--text);">
                                    <?= number_format((float)$b['total_price'], 0, ',', '.') ?> ₫
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                </td>
                                <td>
                                    <small style="color: var(--text-muted);"><?= date('H:i d/m/Y', strtotime($b['created_at'])) ?></small>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= base_url('booking_voucher.php?id=' . (int)$b['id']) ?>" 
                                       class="btn btn--outline btn--sm" 
                                       target="_blank" 
                                       title="In phiếu xác nhận đặt phòng">
                                        <?= icon('printer', '', 12) ?> In
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
