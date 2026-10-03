<?php
/**
 * TRANG XÁC NHẬN THÔNG TIN ĐẶT PHÒNG
 * File: book.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// 1. Bắt buộc đăng nhập trước khi vào đặt phòng
require_login();

$pageTitle = 'Xác Nhận Đặt Phòng';

// 2. Nhận và re-validate 4 tham số GET
$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$checkIn    = trim((string)($_GET['check_in'] ?? ''));
$checkOut   = trim((string)($_GET['check_out'] ?? ''));
$guests     = (int)($_GET['guests'] ?? 0);

$today = date('Y-m-d');

if ($roomTypeId <= 0 || $checkIn === '' || $checkOut === '' || $guests <= 0) {
    flash('error', 'Thiếu thông tin đặt phòng. Vui lòng tìm kiếm lại.');
    redirect(base_url('rooms.php'));
}

if ($checkIn < $today || $checkOut <= $checkIn || $guests < 1 || $guests > 20) {
    flash('error', 'Thông tin ngày hoặc số lượng khách không hợp lệ.');
    redirect(base_url('rooms.php'));
}

// 3. TRUY VẤN LẠI AVAILABILITY CHO LOẠI PHÒNG NÀY (Đề phòng vừa hết phòng)
$sqlCheck = "SELECT rt.id, rt.name, rt.description, rt.price_per_night, rt.capacity, rt.image_url,
                    COUNT(r.id) AS available_rooms
             FROM room_types rt
             JOIN rooms r ON r.room_type_id = rt.id AND r.status = 'active'
             WHERE rt.id = :room_type_id
               AND rt.capacity >= :guests
               AND NOT EXISTS (
                   SELECT 1 FROM bookings b
                   WHERE b.room_id = r.id AND b.status <> 'cancelled'
                   AND b.check_in < :check_out AND b.check_out > :check_in
               )
             GROUP BY rt.id, rt.name, rt.description, rt.price_per_night, rt.capacity, rt.image_url";

$stmt = $pdo->prepare($sqlCheck);
$stmt->execute([
    'room_type_id' => $roomTypeId,
    'guests'       => $guests,
    'check_out'    => $checkOut,
    'check_in'     => $checkIn
]);
$roomType = $stmt->fetch();

if (!$roomType || (int)$roomType['available_rooms'] <= 0) {
    flash('error', 'Rất tiếc, loại phòng này đã hết cho ngày bạn chọn. Vui lòng chọn loại phòng khác.');
    redirect(base_url('rooms.php?check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . urlencode((string)$guests)));
}

// 4. Tính số đêm và tổng tiền trên server
$dIn = new DateTime($checkIn);
$dOut = new DateTime($checkOut);
$nights = max(1, $dIn->diff($dOut)->days);
$pricePerNight = (float)$roomType['price_per_night'];
$totalPrice = $pricePerNight * $nights;

$imgName = $roomType['image_url'] ?: 'placeholder.jpg';
$imgPath = base_url('uploads/' . $imgName);

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">
        <a href="<?= base_url('index.php') ?>">Trang chủ</a> &rsaquo; 
        <a href="<?= base_url('rooms.php?check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . urlencode((string)$guests)) ?>">Chọn phòng</a> &rsaquo; 
        <strong>Xác nhận đặt phòng</strong>
    </div>
    <h1 style="font-size: 1.65rem; color: var(--navy); margin-bottom: 0.25rem;">
        Xác nhận thông tin đặt phòng
    </h1>
    <p style="color: var(--text-muted); font-size: 0.92rem;">
        Quý khách vui lòng kiểm tra thông tin đặt phòng trước khi hoàn tất giữ chỗ.
    </p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; align-items: flex-start; margin-bottom: 3rem;">
    <!-- CỘT TRÁI: THÔNG TIN KHÁCH HÀNG & CHÍNH SÁCH -->
    <div>
        <!-- Thẻ thông tin khách lưu trú -->
        <div class="card" style="margin-bottom: 1.25rem;">
            <div class="card__body">
                <h2 style="font-size: 1.15rem; color: var(--navy); margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.45rem;">
                    <?= icon('user', '', 18) ?> Thông tin người đặt phòng
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Họ và tên</div>
                        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);"><?= e($_SESSION['user_name']) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Email liên hệ</div>
                        <div style="font-size: 0.95rem; font-weight: 500; color: var(--text);"><?= e($_SESSION['user_email']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thẻ quyền lợi phòng & giờ giấc -->
        <div class="card" style="margin-bottom: 1.25rem;">
            <div class="card__body">
                <h2 style="font-size: 1.15rem; color: var(--navy); margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.45rem;">
                    <?= icon('check-circle', '', 18) ?> Quyền lợi đã bao gồm trong giá phòng
                </h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; font-size: 0.88rem; color: #166534;">
                    <div>✓ Bữa sáng buffet mỗi ngày</div>
                    <div>✓ Hồ bơi tầng thượng & Gym</div>
                    <div>✓ Wifi internet tốc độ cao</div>
                    <div>✓ Nước uống đón tiếp khi check-in</div>
                    <div>✓ Nhận phòng từ <strong>14:00</strong></div>
                    <div>✓ Trả phòng trước <strong>12:00</strong></div>
                </div>
            </div>
        </div>

        <!-- Thẻ chính sách thanh toán tại quầy -->
        <div class="card" style="margin-bottom: 1.5rem; background: #fffdf5; border: 1px solid #fde68a;">
            <div class="card__body">
                <h3 style="font-size: 1rem; font-weight: 700; color: #92400e; margin-bottom: 0.35rem;">
                    Thanh toán trực tiếp khi nhận phòng
                </h3>
                <p style="font-size: 0.88rem; color: #78350f; line-height: 1.5; margin-bottom: 0.5rem;">
                    Quý khách không cần trả trước hoặc cung cấp thông tin thẻ tín dụng. Toàn bộ tiền phòng sẽ được thanh toán trực tiếp tại quầy lễ tân khi làm thủ tục nhận phòng.
                </p>
                <div style="font-size: 0.82rem; color: #92400e;">
                    Chính sách hủy: Quý khách có thể hủy đặt phòng trực tuyến hoàn toàn miễn phí trước ngày nhận phòng.
                </div>
            </div>
        </div>

        <!-- Form POST xác nhận đặt phòng -->
        <form action="<?= base_url('process_booking.php') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="room_type_id" value="<?= $roomTypeId ?>">
            <input type="hidden" name="check_in" value="<?= e($checkIn) ?>">
            <input type="hidden" name="check_out" value="<?= e($checkOut) ?>">
            <input type="hidden" name="guests" value="<?= $guests ?>">

            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <button type="submit" class="btn btn--primary btn--lg" style="flex: 1; height: 48px;">
                    Hoàn tất đặt phòng &rarr;
                </button>
                <a href="<?= base_url('rooms.php?check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . urlencode((string)$guests)) ?>" 
                   class="btn btn--outline btn--lg">
                    Quay lại
                </a>
            </div>
        </form>
    </div>

    <!-- CỘT PHẢI: TÓM TẮT ĐẶT PHÒNG -->
    <div>
        <div class="card">
            <div style="height: 180px; overflow: hidden; position: relative;">
                <img src="<?= $imgPath ?>" alt="<?= e($roomType['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <div class="room-card__badge-overlay">
                    Còn <?= (int)$roomType['available_rooms'] ?> phòng trống
                </div>
            </div>

            <div class="card__body">
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--navy); margin-bottom: 0.25rem;">
                    <?= e($roomType['name']) ?>
                </h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">
                    <?= e($roomType['description']) ?>
                </p>

                <div style="border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 0.85rem 0; margin-bottom: 1rem; font-size: 0.88rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                        <span style="color: var(--text-muted);">Nhận phòng:</span>
                        <strong><?= date('d/m/Y', strtotime($checkIn)) ?> (từ 14:00)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                        <span style="color: var(--text-muted);">Trả phòng:</span>
                        <strong><?= date('d/m/Y', strtotime($checkOut)) ?> (trước 12:00)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                        <span style="color: var(--text-muted);">Thời lượng lưu trú:</span>
                        <strong><?= $nights ?> đêm</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Số khách:</span>
                        <strong><?= $guests ?> người lớn</strong>
                    </div>
                </div>

                <!-- Bảng tính tiền chi tiết -->
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem; font-size: 0.88rem;">
                        <span style="color: var(--text-muted);"><?= number_format($pricePerNight, 0, ',', '.') ?> ₫ × <?= $nights ?> đêm:</span>
                        <span><?= number_format($totalPrice, 0, ',', '.') ?> ₫</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.88rem;">
                        <span style="color: var(--text-muted);">Thuế & phí dịch vụ:</span>
                        <span style="color: var(--success); font-weight: 600;">Đã bao gồm</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 0.75rem; border-top: 1px dashed var(--border);">
                        <span style="font-weight: 700; color: var(--navy); font-size: 1rem;">TỔNG THANH TOÁN:</span>
                        <div style="text-align: right;">
                            <div style="font-size: 1.45rem; font-weight: 800; color: var(--primary); line-height: 1;">
                                <?= number_format($totalPrice, 0, ',', '.') ?> ₫
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.72rem;">(Thanh toán tại quầy lễ tân)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
