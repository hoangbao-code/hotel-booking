<?php
/**
 * PHIẾU XÁC NHẬN ĐẶT PHÒNG CHÍNH THỨC (OFFICIAL BOOKING VOUCHER)
 * Hỗ trợ in trực tiếp / Lưu PDF qua @media print
 * File: booking_voucher.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

require_login();

$bookingId = (int)($_GET['id'] ?? 0);
if ($bookingId <= 0) {
    flash('error', 'Mã đơn đặt phòng không hợp lệ.');
    redirect(base_url('my_bookings.php'));
}

// Truy vấn thông tin chi tiết đơn
$sql = "SELECT b.id, b.user_id, b.room_id, b.check_in, b.check_out, b.guests, b.status, b.total_price, b.created_at,
               u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
               r.room_number,
               rt.name AS room_type_name, rt.description AS room_type_desc, rt.price_per_night, rt.image_url
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE b.id = :id
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    flash('error', 'Đơn đặt phòng không tồn tại.');
    redirect(base_url('my_bookings.php'));
}

// Kiểm tra quyền hạn: Chỉ Admin hoặc chính khách hàng mới được xem voucher này
$currentUserId = (int)$_SESSION['user_id'];
$currentUserRole = (string)($_SESSION['user_role'] ?? '');

if ($currentUserRole !== 'admin' && (int)$booking['user_id'] !== $currentUserId) {
    flash('error', 'Bạn không có quyền truy cập phiếu đặt phòng này.');
    redirect(base_url('my_bookings.php'));
}

$pageTitle = 'Phiếu Đặt Phòng #BK-' . $bookingId;

$dIn = new DateTime($booking['check_in']);
$dOut = new DateTime($booking['check_out']);
$nights = max(1, $dIn->diff($dOut)->days);

$status = (string)$booking['status'];
$badgeText = 'Chờ xác nhận';
$badgeColor = '#92400e';
$badgeBg = '#fef3c7';

if ($status === 'confirmed') {
    $badgeText = 'Đã xác nhận';
    $badgeColor = '#065f46';
    $badgeBg = '#d1fae5';
} elseif ($status === 'cancelled') {
    $badgeText = 'Đã hủy';
    $badgeColor = '#991b1b';
    $badgeBg = '#fee2e2';
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Thanh thao tác in ấn (Sẽ ẩn khi in) -->
<div class="no-print" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
    <div>
        <a href="<?= $currentUserRole === 'admin' ? base_url('admin/bookings.php') : base_url('my_bookings.php') ?>" 
           class="btn btn--outline btn--sm">
            &larr; Quay lại danh sách
        </a>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" onclick="window.print();" class="btn btn--primary btn--sm">
            <?= icon('printer', '', 14) ?> In phiếu đặt phòng
        </button>
    </div>
</div>

<!-- KHUNG VOUCHER XÁC NHẬN CHÍNH THỨC -->
<div class="card voucher-card" style="max-width: 800px; margin: 0 auto; background: #ffffff; border: 1px solid var(--border);">
    <!-- Header của Voucher -->
    <div style="background: var(--navy-dark); color: #ffffff; padding: 1.75rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 2px solid var(--primary);">
        <div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #ffffff; margin-bottom: 0.25rem;">
                KHÁCH SẠN GRAND OASIS ĐÀ NẴNG
            </div>
            <div style="font-size: 0.8rem; color: #cbd5e1; line-height: 1.5;">
                123 Võ Nguyên Giáp, Phước Mỹ, Sơn Trà, TP. Đà Nẵng<br>
                Hotline: 1900 6868 &bull; Email: reservations@grandoasis.vn
            </div>
        </div>

        <div style="text-align: right;">
            <div style="font-size: 0.72rem; text-transform: uppercase; color: #cbd5e1; font-weight: 600;">
                MÃ XÁC NHẬN ĐẶT PHÒNG
            </div>
            <div style="font-family: monospace; font-size: 1.5rem; font-weight: 700; color: #ffffff; margin-top: 0.15rem;">
                #BK-<?= str_pad((string)$booking['id'], 6, '0', STR_PAD_LEFT) ?>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8;">
                Ngày đặt: <?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?>
            </div>
        </div>
    </div>

    <div style="padding: 2rem;">
        <!-- Dải trạng thái đơn -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h1 style="font-size: 1.3rem; font-weight: 700; color: var(--navy); margin-bottom: 0.2rem;">
                    Phiếu Xác Nhận Đặt Chỗ Lưu Trú
                </h1>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                    Vui lòng xuất trình mã đơn này kèm CCCD/Hộ chiếu tại quầy lễ tân khi nhận phòng.
                </p>
            </div>
            <div>
                <span style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-weight: 600; font-size: 0.85rem; padding: 0.35rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid <?= $badgeColor ?>40;">
                    <?= $badgeText ?>
                </span>
            </div>
        </div>

        <!-- 2 Khối thông tin: Khách hàng & Kỳ nghỉ -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
            <!-- Khối 1: Thông tin khách hàng -->
            <div style="background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--navy); margin-bottom: 0.75rem;">
                    Thông Tin Khách Lưu Trú
                </h3>
                <div style="font-size: 0.88rem; display: flex; flex-direction: column; gap: 0.45rem;">
                    <div>
                        <span style="color: var(--text-muted);">Họ và tên:</span>
                        <strong style="color: var(--navy); margin-left: 0.5rem;"><?= e($booking['customer_name']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Số điện thoại:</span>
                        <strong style="color: var(--navy); margin-left: 0.5rem;"><?= e($booking['customer_phone'] ?? 'Chưa cập nhật') ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Email:</span>
                        <strong style="color: var(--navy); margin-left: 0.5rem;"><?= e($booking['customer_email']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Số lượng khách:</span>
                        <strong style="color: var(--navy); margin-left: 0.5rem;"><?= (int)$booking['guests'] ?> người lớn</strong>
                    </div>
                </div>
            </div>

            <!-- Khối 2: Thông tin phòng & thời gian -->
            <div style="background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--navy); margin-bottom: 0.75rem;">
                    Thông Tin Phòng & Lưu Trú
                </h3>
                <div style="font-size: 0.88rem; display: flex; flex-direction: column; gap: 0.45rem;">
                    <div>
                        <span style="color: var(--text-muted);">Hạng phòng:</span>
                        <strong style="color: var(--navy); margin-left: 0.5rem;"><?= e($booking['room_type_name']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Phòng phân bổ:</span>
                        <strong style="color: var(--navy); margin-left: 0.5rem;">Phòng <?= e($booking['room_number']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Nhận phòng:</span>
                        <strong style="color: var(--navy); margin-left: 0.5rem;"><?= date('d/m/Y', strtotime($booking['check_in'])) ?> (từ 14:00)</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Trả phòng:</span>
                        <strong style="color: var(--navy); margin-left: 0.5rem;"><?= date('d/m/Y', strtotime($booking['check_out'])) ?> (trước 12:00)</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng chi tiết chi phí -->
        <div style="margin-bottom: 1.5rem;">
            <table class="table" style="border: 1px solid var(--border);">
                <thead>
                    <tr>
                        <th>Khoản mục chi tiết</th>
                        <th style="text-align: center;">Số đêm</th>
                        <th style="text-align: right;">Đơn giá</th>
                        <th style="text-align: right;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Hạng phòng <?= e($booking['room_type_name']) ?></strong>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.15rem;">
                                Đã bao gồm bữa sáng buffet hàng ngày và quyền sử dụng hồ bơi tầng thượng
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: 600;"><?= $nights ?> đêm</td>
                        <td style="text-align: right;"><?= number_format((float)$booking['price_per_night'], 0, ',', '.') ?> ₫</td>
                        <td style="text-align: right; font-weight: 600;"><?= number_format((float)$booking['total_price'], 0, ',', '.') ?> ₫</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 600; background: #f8fafc;">
                            Thuế GTGT (VAT) & Phí dịch vụ:
                        </td>
                        <td style="text-align: right; color: var(--success); font-weight: 600; background: #f8fafc;">
                            Đã bao gồm
                        </td>
                    </tr>
                    <tr style="background: #f1f5f9;">
                        <td colspan="3" style="text-align: right; font-weight: 700; font-size: 1rem; color: var(--navy);">
                            TỔNG THANH TOÁN (PAY ON ARRIVAL):
                        </td>
                        <td style="text-align: right; font-weight: 700; font-size: 1.25rem; color: var(--primary);">
                            <?= number_format((float)$booking['total_price'], 0, ',', '.') ?> ₫
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Lưu ý nhận phòng -->
        <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; border-top: 1px solid var(--border); padding-top: 1rem;">
            <strong>Chính sách lưu trú & nhận phòng:</strong><br>
            &bull; Quý khách thanh toán 100% chi phí tại quầy lễ tân khi nhận phòng (chấp nhận tiền mặt, thẻ Visa/Mastercard hoặc chuyển khoản).<br>
            &bull; Giờ nhận phòng: 14:00 &bull; Giờ trả phòng: 12:00 trưa.<br>
            &bull; Mọi thắc mắc hoặc yêu cầu nhận phòng sớm/trễ, vui lòng liên hệ hotline: <strong>1900 6868</strong>.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
