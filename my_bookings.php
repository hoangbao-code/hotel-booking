<?php
/**
 * TRANG XEM LỊCH SỬ ĐẶT PHÒNG CỦA KHÁCH HÀNG
 * File: my_bookings.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Bắt buộc đăng nhập
require_login();

$userId = (int)$_SESSION['user_id'];
$pageTitle = 'Đặt Phòng Của Tôi';

$today = date('Y-m-d');

// Truy vấn toàn bộ lịch sử booking của chính user này
$sql = "SELECT b.id, b.check_in, b.check_out, b.guests, b.status, b.total_price, b.created_at,
               r.room_number,
               rt.name AS room_type_name, rt.image_url
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE b.user_id = :user_id
        ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $userId]);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.35rem;">
            <a href="<?= base_url('index.php') ?>">Trang chủ</a> &rsaquo; <strong>Đặt phòng của tôi</strong>
        </div>
        <h1 style="font-size: 1.65rem; color: var(--navy); margin-bottom: 0.25rem;">
            Lịch sử đặt phòng
        </h1>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            Theo dõi tình trạng đơn đặt phòng, xem phiếu xác nhận hoặc hủy phòng theo quy định.
        </p>
    </div>
    <a href="<?= base_url('rooms.php') ?>" class="btn btn--primary btn--sm">
        + Đặt thêm phòng mới
    </a>
</div>

<?php if (empty($bookings)): ?>
    <div class="card" style="text-align: center; padding: 3.5rem 1.5rem; background: #ffffff;">
        <div style="width: 48px; height: 48px; background: #f1f5f9; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
            <?= icon('calendar', '', 24) ?>
        </div>
        <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--navy); margin-bottom: 0.5rem;">
            Quý khách chưa có đơn đặt phòng nào
        </h3>
        <p style="color: var(--text-muted); max-width: 460px; margin: 0 auto 1.5rem; font-size: 0.9rem; line-height: 1.5;">
            Khám phá các hạng phòng nghỉ tiện nghi bên bãi biển Mỹ Khê và đặt ngay phòng cho chuyến đi của bạn.
        </p>
        <div>
            <a href="<?= base_url('rooms.php') ?>" class="btn btn--primary">
                Xem danh sách phòng nghỉ &rarr;
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Hạng phòng</th>
                    <th>Số phòng</th>
                    <th>Thời gian lưu trú</th>
                    <th>Số khách</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th style="text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): 
                    $status = (string)$b['status'];
                    $canCancel = in_array($status, ['pending', 'confirmed'], true) && ($b['check_in'] >= $today);

                    $badgeClass = 'badge--pending';
                    $statusText = 'Chờ xác nhận';
                    if ($status === 'confirmed') {
                        $badgeClass = 'badge--confirmed';
                        $statusText = 'Đã xác nhận';
                    } elseif ($status === 'cancelled') {
                        $badgeClass = 'badge--cancelled';
                        $statusText = 'Đã hủy';
                    }

                    $imgName = $b['image_url'] ?: 'placeholder.jpg';
                    $imgPath = base_url('uploads/' . $imgName);
                ?>
                    <tr>
                        <td>
                            <strong style="color: var(--primary-dark); font-size: 0.95rem;">#BK-<?= (int)$b['id'] ?></strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem;">
                                <?= date('d/m/Y H:i', strtotime($b['created_at'])) ?>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                <div style="width: 44px; height: 34px; border-radius: 4px; overflow: hidden; flex-shrink: 0; background: #eee;">
                                    <img src="<?= $imgPath ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <span style="font-weight: 600; color: var(--text); font-size: 0.9rem;"><?= e($b['room_type_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge" style="background:#f1f5f9; color:#334155; font-weight: 600;">
                                Phòng <?= e($b['room_number']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--text);"><?= date('d/m/Y', strtotime($b['check_in'])) ?></div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">đến <?= date('d/m/Y', strtotime($b['check_out'])) ?></div>
                        </td>
                        <td><?= (int)$b['guests'] ?> người lớn</td>
                        <td style="font-weight: 700; color: var(--text); font-size: 0.95rem;">
                            <?= number_format((float)$b['total_price'], 0, ',', '.') ?> ₫
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 0.35rem; align-items: center; justify-content: center; flex-wrap: wrap;">
                                <a href="<?= base_url('booking_voucher.php?id=' . (int)$b['id']) ?>" 
                                   class="btn btn--outline btn--sm" 
                                   title="Xem phiếu xác nhận đặt phòng và in voucher">
                                    <?= icon('printer', '', 13) ?> Xem phiếu
                                </a>

                                <?php if ($canCancel): ?>
                                    <form action="<?= base_url('cancel_booking.php') ?>" method="POST" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn đặt phòng #BK-<?= (int)$b['id'] ?> không? Thao tác này không thể hoàn tác.');" 
                                          style="display: inline-block;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">
                                            Hủy đặt
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
