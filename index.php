<?php
/**
 * TRANG CHỦ & TÌM KIẾM PHÒNG TRỐNG
 * File: index.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$pageTitle = 'Trang chủ';

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Lấy danh sách loại phòng để hiển thị giới thiệu
$stmt = $pdo->query("SELECT id, name, description, price_per_night, capacity, image_url FROM room_types ORDER BY price_per_night ASC");
$roomTypes = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Banner Khách Sạn -->
<section class="hero">
    <div class="hero__inner">
        <div class="hero__badge">
            <?= icon('star', '', 14) ?> Khách sạn tiêu chuẩn 5 sao bên biển Mỹ Khê
        </div>
        <h1 class="hero__title">Khách Sạn Grand Oasis Đà Nẵng</h1>
        <p class="hero__desc">
            Không gian nghỉ dưỡng tiện nghi, hiện đại ngay mặt tiền đường ven biển Võ Nguyên Giáp. Đặt phòng trực tiếp để nhận giá ưu đãi tốt nhất và thanh toán khi làm thủ tục nhận phòng.
        </p>
        <div class="hero__highlights">
            <span class="hero__highlight-item"><?= icon('check', '', 15) ?> Mặt tiền biển Mỹ Khê</span>
            <span class="hero__highlight-item"><?= icon('check', '', 15) ?> Bữa sáng buffet hàng ngày</span>
            <span class="hero__highlight-item"><?= icon('check', '', 15) ?> Hồ bơi vô cực tầng thượng</span>
            <span class="hero__highlight-item"><?= icon('check', '', 15) ?> Không cần thanh toán trước</span>
        </div>
    </div>
</section>

<!-- 2. Hộp tìm kiếm phòng trống (Booking style) -->
<section class="search-box">
    <form action="<?= base_url('rooms.php') ?>" method="GET" class="search-form">
        <div class="search-field">
            <label for="check_in">
                <span>Ngày nhận phòng</span>
                <?= icon('calendar', '', 14) ?>
            </label>
            <input type="date" id="check_in" name="check_in" 
                   value="<?= $today ?>" min="<?= $today ?>" required>
        </div>

        <div class="search-field">
            <label for="check_out">
                <span>Ngày trả phòng</span>
                <span class="js-nights-badge">1 đêm</span>
            </label>
            <input type="date" id="check_out" name="check_out" 
                   value="<?= $tomorrow ?>" min="<?= $tomorrow ?>" required>
        </div>

        <div class="search-field">
            <label for="guests">
                <span>Số lượng khách</span>
                <?= icon('users', '', 14) ?>
            </label>
            <select id="guests" name="guests" required>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?> người lớn</option>
                <?php endfor; ?>
            </select>
        </div>

        <div>
            <button type="submit" class="search-btn btn--block">
                <?= icon('search', '', 16) ?> Tìm phòng trống
            </button>
        </div>
    </form>
</section>

<!-- 3. Danh sách các hạng phòng nghỉ -->
<section style="margin-bottom: 4rem;">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.65rem; color: var(--navy); margin-bottom: 0.35rem;">
            Các hạng phòng nghỉ tại Grand Oasis
        </h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Tất cả phòng nghỉ đều có điều hòa nhiệt độ, TV màn hình phẳng, minibar, két sắt an toàn và wifi tốc độ cao miễn phí.
        </p>
    </div>

    <div class="grid grid--rooms">
        <?php foreach ($roomTypes as $rt): 
            $imgName = $rt['image_url'] ?: 'placeholder.jpg';
            $imgPath = base_url('uploads/' . $imgName);
            $area = (int)$rt['capacity'] * 15;
            $priceFormatted = number_format((float)$rt['price_per_night'], 0, ',', '.');
        ?>
            <article class="card room-card">
                <div class="room-card__img-wrap">
                    <img src="<?= $imgPath ?>" alt="Hạng phòng <?= e($rt['name']) ?>" class="room-card__img" loading="lazy">
                    <div class="room-card__badge-overlay">
                        Bao gồm ăn sáng
                    </div>
                </div>

                <div class="room-card__content">
                    <h3 class="card__title" style="font-size: 1.2rem;"><?= e($rt['name']) ?></h3>
                    
                    <!-- Thông số phòng -->
                    <div class="room-card__specs">
                        <span><?= icon('users', '', 14) ?> Tối đa <?= (int)$rt['capacity'] ?> khách</span>
                        <span><?= icon('bed', '', 14) ?> 1 giường lớn</span>
                        <span><?= $area ?> m²</span>
                        <span>Ban công view biển</span>
                    </div>

                    <p class="card__text" style="font-size: 0.85rem; margin-bottom: 0.75rem;">
                        <?= e($rt['description'] ?? 'Thiết kế trang nhã, không gian thoáng đãng và nội thất cao cấp.') ?>
                    </p>
                    
                    <!-- Các quyền lợi bao gồm -->
                    <div class="room-card__amenities">
                        <div class="room-card__perk"><?= icon('check', '', 14) ?> Bữa sáng buffet miễn phí mỗi ngày</div>
                        <div class="room-card__perk"><?= icon('check', '', 14) ?> Miễn phí hủy phòng trước ngày nhận</div>
                        <div class="room-card__perk"><?= icon('check', '', 14) ?> Không cần thanh toán trước</div>
                    </div>

                    <div class="room-card__meta">
                        <div>
                            <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Giá mỗi đêm từ</span>
                            <span class="room-card__price"><?= $priceFormatted ?> ₫</span>
                            <span class="room-card__price-sub">Đã bao gồm thuế & phí</span>
                        </div>
                        <div style="display: flex; gap: 0.4rem; align-items: center;">
                            <button type="button" class="btn btn--outline btn--sm" data-open-modal="modal-room-<?= (int)$rt['id'] ?>">
                                Chi tiết
                            </button>
                            <a href="<?= base_url('rooms.php?check_in=' . urlencode($today) . '&check_out=' . urlencode($tomorrow) . '&guests=' . urlencode((string)$rt['capacity'])) ?>" 
                               class="btn btn--primary btn--sm">
                                Chọn phòng
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- 4. Tiện ích & Dịch vụ khách sạn (Thay thế văn mẫu AI) -->
<section style="margin-bottom: 4rem; background: #ffffff; padding: 2.5rem 2rem; border-radius: var(--radius); border: 1px solid var(--border);">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; color: var(--navy); margin-bottom: 0.35rem;">
            Dịch vụ & Tiện ích tại khách sạn
        </h2>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            Đầy đủ các tiện ích phục vụ nhu cầu lưu trú, nghỉ dưỡng và công tác của quý khách.
        </p>
    </div>

    <div class="grid grid--features">
        <div class="feature-card">
            <div class="feature-card__icon"><?= icon('wifi', '', 20) ?></div>
            <h3 class="feature-card__title">Hồ Bơi Vô Cực Tầng Thượng</h3>
            <p class="feature-card__desc">Hồ bơi ngoài trời tại tầng 18 với tầm nhìn trực diện biển Mỹ Khê và toàn cảnh thành phố Đà Nẵng, mở cửa từ 6:00 - 21:00.</p>
        </div>

        <div class="feature-card">
            <div class="feature-card__icon"><?= icon('coffee', '', 20) ?></div>
            <h3 class="feature-card__title">Nhà Hàng & Buffet Sáng</h3>
            <p class="feature-card__desc">Nhà hàng Oasis phục vụ bữa sáng tự chọn phong phú với các món Á - Âu đa dạng, mở cửa hàng ngày từ 6:30 đến 10:00.</p>
        </div>

        <div class="feature-card">
            <div class="feature-card__icon"><?= icon('clock', '', 20) ?></div>
            <h3 class="feature-card__title">Lễ Tân Phục Vụ 24/7</h3>
            <p class="feature-card__desc">Nhân viên lễ tân túc trực 24/24 hỗ trợ làm thủ tục nhận phòng, trả phòng, lưu giữ hành lý và giải đáp mọi yêu cầu.</p>
        </div>

        <div class="feature-card">
            <div class="feature-card__icon"><?= icon('map-pin', '', 20) ?></div>
            <h3 class="feature-card__title">Vị Trí Ven Biển Thuận Lợi</h3>
            <p class="feature-card__desc">Cách bãi tắm Mỹ Khê chỉ 2 phút đi bộ. Dễ dàng di chuyển đến Cầu Rồng, Chợ Đêm Sơn Trà và Sân bay Quốc tế Đà Nẵng.</p>
        </div>
    </div>
</section>

<!-- 5. Vị trí & Khoảng cách di chuyển thực tế (Thay cho fake AI testimonials) -->
<section style="margin-bottom: 4rem;">
    <div style="margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; color: var(--navy); margin-bottom: 0.35rem;">
            Vị trí & Điểm tham quan lân cận
        </h2>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            Khoảng cách di chuyển từ Khách sạn Grand Oasis (123 Võ Nguyên Giáp, Sơn Trà, Đà Nẵng) đến các địa danh nổi tiếng:
        </p>
    </div>

    <div class="card" style="padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem 2.5rem;">
            <div>
                <div class="location-item">
                    <span>Bãi biển Mỹ Khê (Bãi tắm số 1)</span>
                    <span class="location-distance">100 m • 2 phút đi bộ</span>
                </div>
                <div class="location-item">
                    <span>Cầu Rồng & Cầu Tình Yêu</span>
                    <span class="location-distance">3.2 km • 6 phút lái xe</span>
                </div>
                <div class="location-item">
                    <span>Chợ Đêm Sơn Trà & Chợ Hàn</span>
                    <span class="location-distance">3.5 km • 7 phút lái xe</span>
                </div>
                <div class="location-item">
                    <span>Sân bay Quốc tế Đà Nẵng</span>
                    <span class="location-distance">6.5 km • 15 phút lái xe</span>
                </div>
            </div>

            <div>
                <div class="location-item">
                    <span>Bán đảo Sơn Trà & Chùa Linh Ứng</span>
                    <span class="location-distance">6.8 km • 12 phút lái xe</span>
                </div>
                <div class="location-item">
                    <span>Danh thắng Ngũ Hành Sơn</span>
                    <span class="location-distance">7.0 km • 12 phút lái xe</span>
                </div>
                <div class="location-item">
                    <span>Phố cổ Hội An (Quảng Nam)</span>
                    <span class="location-distance">25 km • 35 phút lái xe</span>
                </div>
                <div class="location-item">
                    <span>Sun World Bà Nà Hills</span>
                    <span class="location-distance">30 km • 45 phút lái xe</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. Thông tin lưu trú & Câu hỏi thường gặp -->
<section style="margin-bottom: 4rem;">
    <div style="margin-bottom: 1.5rem; text-align: center;">
        <h2 style="font-size: 1.5rem; color: var(--navy); margin-bottom: 0.35rem;">
            Thông tin lưu trú & Câu hỏi thường gặp
        </h2>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            Những điều cần lưu ý cho kỳ nghỉ của quý khách tại Grand Oasis
        </p>
    </div>

    <div class="faq-accordion">
        <details class="faq-item" open>
            <summary class="faq-summary">Khách sạn có yêu cầu đặt cọc hoặc thanh toán trước không?</summary>
            <div class="faq-content">
                Không. Quý khách có thể đặt phòng trực tuyến hoàn toàn miễn phí mà không cần trả trước. Toàn bộ tiền phòng sẽ được thanh toán trực tiếp tại quầy lễ tân khi quý khách làm thủ tục nhận phòng (bằng tiền mặt hoặc thẻ tín dụng/ATM).
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-summary">Giờ nhận phòng (Check-in) và trả phòng (Check-out) là mấy giờ?</summary>
            <div class="faq-content">
                Giờ nhận phòng tiêu chuẩn là từ <strong>14:00</strong> và giờ trả phòng là trước <strong>12:00</strong> trưa. Quý khách có nhu cầu nhận phòng sớm hoặc trả phòng trễ vui lòng thông báo trước cho quầy lễ tân để được hỗ trợ theo tình trạng phòng thực tế.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-summary">Giá phòng trên website đã bao gồm bữa sáng và thuế phí chưa?</summary>
            <div class="faq-content">
                Tất cả giá phòng niêm yết trên website đều đã bao gồm bữa sáng buffet hàng ngày cho số lượng khách tiêu chuẩn, quyền sử dụng hồ bơi vô cực tầng thượng và wifi miễn phí.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-summary">Chính sách hủy đặt phòng của khách sạn như thế nào?</summary>
            <div class="faq-content">
                Quý khách có thể chủ động hủy đặt phòng trực tuyến hoàn toàn miễn phí bất cứ lúc nào trước ngày nhận phòng ngay trên website trong mục <em>"Đặt phòng của tôi"</em>.
            </div>
        </details>
    </div>
</section>

<!-- MODALS CHI TIẾT CÁC HẠNG PHÒNG -->
<?php foreach ($roomTypes as $rt): 
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
                <a href="<?= base_url('rooms.php?check_in=' . urlencode($today) . '&check_out=' . urlencode($tomorrow) . '&guests=' . urlencode((string)$rt['capacity'])) ?>" 
                   class="btn btn--primary">
                    Chọn hạng phòng này
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
