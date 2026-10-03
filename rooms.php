<?php
/**
 * DANH SÁCH LOẠI PHÒNG & KẾT QUẢ TÌM KIẾM THEO NGÀY (TRUY VẤN Q3)
 * File: rooms.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$pageTitle = 'Phòng Nghỉ & Bảng Giá';

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$hasSearch = isset($_GET['check_in']) || isset($_GET['check_out']) || isset($_GET['guests']);

$checkIn  = trim((string)($_GET['check_in'] ?? ''));
$checkOut = trim((string)($_GET['check_out'] ?? ''));
$guests   = (int)($_GET['guests'] ?? 0);

$nights = 1;
$availableTypes = [];

// 1. NẾU CÓ THAM SỐ TÌM KIẾM -> VALIDATE SERVER-SIDE
if ($hasSearch) {
    if ($checkIn === '' || $checkOut === '' || $guests <= 0) {
        flash('error', 'Vui lòng cung cấp đầy đủ thông tin ngày nhận, ngày trả và số lượng khách.');
        redirect(base_url('index.php'));
    }

    if ($checkIn < $today) {
        flash('error', 'Ngày nhận phòng không thể nhỏ hơn ngày hôm nay.');
        redirect(base_url('index.php'));
    }

    if ($checkOut <= $checkIn) {
        flash('error', 'Ngày trả phòng phải sau ngày nhận phòng ít nhất 1 đêm.');
        redirect(base_url('index.php'));
    }

    if ($guests < 1 || $guests > 20) {
        flash('error', 'Số lượng khách phải từ 1 đến 20 khách.');
        redirect(base_url('index.php'));
    }

    // Tính số đêm
    $dIn = new DateTime($checkIn);
    $dOut = new DateTime($checkOut);
    $nights = max(1, $dIn->diff($dOut)->days);

    // THỰC THI TRUY VẤN Q3 CHUẨN ĐẶC TẢ
    $sqlQ3 = "SELECT rt.id, rt.name, rt.description, rt.price_per_night, rt.capacity, rt.image_url,
                     COUNT(r.id) AS available_rooms
              FROM room_types rt
              JOIN rooms r ON r.room_type_id = rt.id AND r.status = 'active'
              WHERE rt.capacity >= :guests
              AND NOT EXISTS (
                  SELECT 1 FROM bookings b
                  WHERE b.room_id = r.id AND b.status <> 'cancelled'
                  AND b.check_in < :check_out AND b.check_out > :check_in
              )
              GROUP BY rt.id, rt.name, rt.description, rt.price_per_night, rt.capacity, rt.image_url
              ORDER BY rt.price_per_night ASC";

    $stmt = $pdo->prepare($sqlQ3);
    $stmt->execute([
        'guests'    => $guests,
        'check_out' => $checkOut,
        'check_in'  => $checkIn
    ]);
    $availableTypes = $stmt->fetchAll();

} else {
    // KHÔNG CÓ THAM SỐ TÌM KIẾM -> HIỂN THỊ DANH MỤC LOẠI PHÒNG
    $stmtAll = $pdo->query("SELECT id, name, description, price_per_night, capacity, image_url FROM room_types ORDER BY price_per_night ASC");
    $availableTypes = $stmtAll->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hộp điều chỉnh ngày & số khách -->
<div class="card" style="margin-bottom: 2rem; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
    <div style="padding: 1.25rem 1.5rem;">
        <form action="<?= base_url('rooms.php') ?>" method="GET" class="search-form">
            <div class="search-field">
                <label for="check_in">
                    <span>Ngày nhận phòng</span>
                    <?= icon('calendar', '', 14) ?>
                </label>
                <input type="date" id="check_in" name="check_in" 
                       value="<?= e($hasSearch ? $checkIn : $today) ?>" min="<?= $today ?>" required>
            </div>

            <div class="search-field">
                <label for="check_out">
                    <span>Ngày trả phòng</span>
                    <span class="js-nights-badge"><?= $nights ?> đêm</span>
                </label>
                <input type="date" id="check_out" name="check_out" 
                       value="<?= e($hasSearch ? $checkOut : $tomorrow) ?>" min="<?= $tomorrow ?>" required>
            </div>

            <div class="search-field">
                <label for="guests">
                    <span>Số lượng khách</span>
                    <?= icon('users', '', 14) ?>
                </label>
                <select id="guests" name="guests" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>" <?= ($hasSearch && $guests === $i) || (!$hasSearch && $i === 2) ? 'selected' : '' ?>>
                            <?= $i ?> người lớn
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="search-btn btn--block" style="min-height: 48px;">
                    <?= icon('search', '', 15) ?> Cập nhật tìm kiếm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tiêu đề trang & Thanh tóm tắt kết quả -->
<div style="margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.65rem; color: var(--navy); margin-bottom: 0.35rem;">
            <?= $hasSearch ? 'Phòng khả dụng cho kỳ nghỉ' : 'Danh mục các hạng phòng nghỉ' ?>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            <?= $hasSearch 
                ? "Kết quả từ ngày <strong>" . date('d/m/Y', strtotime($checkIn)) . "</strong> đến <strong>" . date('d/m/Y', strtotime($checkOut)) . "</strong> ({$nights} đêm) cho <strong>{$guests} khách</strong>"
                : "Tất cả các hạng phòng tại Khách sạn Grand Oasis Đà Nẵng. Vui lòng chọn ngày để kiểm tra tình trạng phòng thực tế." ?>
        </p>
    </div>
</div>

<?php if (empty($availableTypes)): ?>
    <div class="card" style="text-align: center; padding: 4rem 2rem; background: #ffffff;">
        <div style="width: 48px; height: 48px; background: #fef2f2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
            <?= icon('info', '', 24) ?>
        </div>
        <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--navy); margin-bottom: 0.5rem;">
            Hiện không còn phòng phù hợp cho khoảng thời gian này
        </h3>
        <p style="color: var(--text-muted); max-width: 520px; margin: 0 auto 1.75rem; font-size: 0.92rem; line-height: 1.6;">
            Trong khoảng thời gian bạn đã chọn, các phòng phù hợp với số lượng khách này đã được đặt kín hoặc đang trong lịch bảo trì định kỳ. Quý khách vui lòng chọn ngày khác hoặc liên hệ hotline để được tư vấn.
        </p>
        <div>
            <a href="<?= base_url('index.php') ?>" class="btn btn--primary">
                &larr; Chọn lại ngày nhận phòng khác
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid--rooms">
        <?php foreach ($availableTypes as $rt): 
            $imgName = $rt['image_url'] ?: 'placeholder.jpg';
            $imgPath = base_url('uploads/' . $imgName);
            $pricePerNight = (float)$rt['price_per_night'];
            $totalPrice = $pricePerNight * $nights;
            $availableRooms = isset($rt['available_rooms']) ? (int)$rt['available_rooms'] : null;
            $area = (int)$rt['capacity'] * 15;
        ?>
            <article class="card room-card">
                <div class="room-card__img-wrap">
                    <img src="<?= $imgPath ?>" alt="Hạng phòng <?= e($rt['name']) ?>" class="room-card__img" loading="lazy">
                    <div class="room-card__badge-overlay">
                        Bao gồm ăn sáng
                    </div>
                </div>

                <div class="room-card__content">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.35rem;">
                        <h3 class="card__title" style="margin-bottom: 0; font-size: 1.15rem;"><?= e($rt['name']) ?></h3>
                        <?php if ($availableRooms !== null): ?>
                            <span class="badge badge--confirmed">
                                Còn <?= $availableRooms ?> phòng
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Thông số phòng -->
                    <div class="room-card__specs">
                        <span><?= icon('users', '', 14) ?> <?= (int)$rt['capacity'] ?> khách</span>
                        <span><?= icon('bed', '', 14) ?> 1 giường đôi</span>
                        <span><?= $area ?> m²</span>
                    </div>

                    <p class="card__text" style="font-size: 0.85rem; margin-bottom: 0.75rem;">
                        <?= e($rt['description'] ?? 'Thiết kế trang nhã, không gian thoáng đãng và nội thất cao cấp.') ?>
                    </p>
                    
                    <!-- Quyền lợi -->
                    <div class="room-card__amenities">
                        <div class="room-card__perk"><?= icon('check', '', 14) ?> Bữa sáng buffet miễn phí mỗi ngày</div>
                        <div class="room-card__perk"><?= icon('check', '', 14) ?> Miễn phí hủy phòng trước ngày nhận</div>
                        <div class="room-card__perk"><?= icon('check', '', 14) ?> Thanh toán tại khách sạn khi nhận phòng</div>
                    </div>

                    <div class="room-card__meta">
                        <div>
                            <?php if ($hasSearch): ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">
                                    Tổng tiền <?= $nights ?> đêm:
                                </span>
                                <span class="room-card__price">
                                    <?= number_format($totalPrice, 0, ',', '.') ?> ₫
                                </span>
                                <span class="room-card__price-sub">
                                    <?= number_format($pricePerNight, 0, ',', '.') ?> ₫ / đêm • Đã gồm thuế
                                </span>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Giá mỗi đêm từ</span>
                                <span class="room-card__price">
                                    <?= number_format($pricePerNight, 0, ',', '.') ?> ₫
                                </span>
                                <span class="room-card__price-sub">Đã bao gồm thuế & phí</span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 0.4rem; align-items: center;">
                            <button type="button" class="btn btn--outline btn--sm" data-open-modal="modal-room-<?= (int)$rt['id'] ?>">
                                Chi tiết
                            </button>
                            <?php if ($hasSearch): ?>
                                <a href="<?= base_url('book.php?room_type_id=' . (int)$rt['id'] . '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . urlencode((string)$guests)) ?>" 
                                   class="btn btn--primary btn--sm">
                                    Đặt phòng
                                </a>
                            <?php else: ?>
                                <a href="<?= base_url('rooms.php?check_in=' . urlencode($today) . '&check_out=' . urlencode($tomorrow) . '&guests=' . urlencode((string)$rt['capacity'])) ?>" 
                                   class="btn btn--primary btn--sm">
                                    Chọn ngày
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- MODALS CHI TIẾT CÁC HẠNG PHÒNG TRÊN TRANG ROOMS -->
<?php foreach ($availableTypes as $rt): 
    $imgName = $rt['image_url'] ?: 'placeholder.jpg';
    $imgPath = base_url('uploads/' . $imgName);
    $priceFormatted = number_format((float)$rt['price_per_night'], 0, ',', '.');
    $area = (int)$rt['capacity'] * 15;
?>
    <div id="modal-room-<?= (int)$rt['id'] ?>" class="modal-backdrop">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">Hạng phòng <?= e($rt['name']) ?></div>
                <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
            </div>
            <div class="modal-body">
                <div style="width: 100%; height: 240px; border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 1.25rem; background: #e2e8f0;">
                    <img src="<?= $imgPath ?>" alt="<?= e($rt['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>

                <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 0.5rem;">Không gian & Tiện nghi phòng</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    <?= e($rt['description'] ?? 'Thiết kế trang nhã, không gian thoáng mát và nội thất cao cấp.') ?>
                </p>

                <!-- Grid thông số kỹ thuật -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Sức chứa</div>
                        <div style="font-weight: 700; color: var(--text); font-size: 0.9rem;"><?= (int)$rt['capacity'] ?> người lớn</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Diện tích</div>
                        <div style="font-weight: 700; color: var(--text); font-size: 0.9rem;"><?= $area ?> m²</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Tầm nhìn</div>
                        <div style="font-weight: 700; color: var(--text); font-size: 0.9rem;">View biển Mỹ Khê</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Giường ngủ</div>
                        <div style="font-weight: 700; color: var(--text); font-size: 0.9rem;">1 giường đôi lớn</div>
                    </div>
                </div>

                <!-- Tiện nghi chi tiết -->
                <h4 style="font-size: 1rem; font-weight: 700; color: var(--navy); margin-bottom: 0.5rem;">Trang thiết bị trong phòng</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem; font-size: 0.85rem; color: var(--text); margin-bottom: 1.25rem;">
                    <div>✓ Wifi internet tốc độ cao</div>
                    <div>✓ Điều hòa nhiệt độ 2 chiều</div>
                    <div>✓ Smart TV màn hình phẳng</div>
                    <div>✓ Tủ lạnh mini & Ấm đun nước</div>
                    <div>✓ Phòng tắm riêng với vòi sen</div>
                    <div>✓ Két sắt bảo mật cá nhân</div>
                    <div>✓ Máy sấy tóc & Khăn tắm</div>
                    <div>✓ Ban công riêng thoáng mát</div>
                </div>

                <!-- Quy định giờ giấc -->
                <div style="font-size: 0.82rem; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 0.75rem;">
                    Nhận phòng từ <strong>14:00</strong> &bull; Trả phòng trước <strong>12:00</strong> trưa.<br>
                    Quy định: Phòng không hút thuốc lá.
                </div>
            </div>
            <div class="modal-footer">
                <div style="margin-right: auto;">
                    <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary);">
                        <?= $priceFormatted ?> ₫
                    </span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">/đêm</span>
                </div>
                <button type="button" class="btn btn--outline" data-close-modal>Đóng</button>
                <?php if ($hasSearch): ?>
                    <a href="<?= base_url('book.php?room_type_id=' . (int)$rt['id'] . '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . urlencode((string)$guests)) ?>" 
                       class="btn btn--primary">
                        Đặt phòng này
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('rooms.php?check_in=' . urlencode($today) . '&check_out=' . urlencode($tomorrow) . '&guests=' . urlencode((string)$rt['capacity'])) ?>" 
                       class="btn btn--primary">
                        Chọn ngày đặt
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
