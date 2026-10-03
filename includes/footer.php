<?php
/**
 * FOOTER DÙNG CHUNG TOÀN HỆ THỐNG
 * File: includes/footer.php
 */

declare(strict_types=1);
?>
</main><!-- /.main-content -->

<footer class="site-footer">
    <div class="container footer-container">
        <!-- Cột 1: Thông tin khách sạn -->
        <div>
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.75rem;">
                <span class="site-logo__mark" style="background:#006ce4; color:#ffffff;">GO</span>
                <span style="font-size: 1.15rem; font-weight: 700; color: #ffffff;">
                    GRAND OASIS HOTEL
                </span>
            </div>
            <p class="footer-desc">
                Khách sạn tiêu chuẩn 5 sao quốc tế tại bãi biển Mỹ Khê, Đà Nẵng. Cung cấp phòng nghỉ sang trọng, ẩm thực phong phú và dịch vụ lưu trú chuyên nghiệp.
            </p>
            <div style="font-size: 0.8rem; color: #cbd5e1;">
                Giấy phép kinh doanh số: 0401889988 do Sở KH&ĐT TP. Đà Nẵng cấp.
            </div>
        </div>

        <!-- Cột 2: Điều hướng nhanh -->
        <div>
            <h3 class="footer-title">Khám Phá & Đặt Phòng</h3>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.88rem;">
                <li><a href="<?= base_url('index.php') ?>" style="color: #cbd5e1;">Trang chủ</a></li>
                <li><a href="<?= base_url('rooms.php') ?>" style="color: #cbd5e1;">Danh mục phòng & Bảng giá</a></li>
                <li><a href="<?= base_url('my_bookings.php') ?>" style="color: #cbd5e1;">Kiểm tra đặt phòng của tôi</a></li>
                <li><a href="<?= base_url('login.php') ?>" style="color: #cbd5e1;">Cổng đăng nhập</a></li>
            </ul>
        </div>

        <!-- Cột 3: Liên hệ & Lễ tân -->
        <div>
            <h3 class="footer-title">Thông Tin Liên Hệ</h3>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem; color: #cbd5e1;">
                <div><?= icon('map-pin', '', 14) ?> 123 Võ Nguyên Giáp, Phước Mỹ, Sơn Trà, TP. Đà Nẵng</div>
                <div><?= icon('phone', '', 14) ?> Đặt phòng: <strong>1900 6868</strong> • Lễ tân: <strong>(0236) 3888 999</strong></div>
                <div><?= icon('clock', '', 14) ?> Giờ nhận phòng: <strong>14:00</strong> • Trả phòng: <strong>12:00</strong></div>
                <div><?= icon('check-circle', '', 14) ?> Hình thức thanh toán: Tiền mặt, Thẻ tín dụng/ATM khi nhận phòng</div>
            </div>
        </div>

        <!-- Cột 4: Bản quyền & Điều khoản -->
        <div class="footer-bottom">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    &copy; <?= date('Y') ?> <strong>Khách sạn Grand Oasis Đà Nẵng</strong>. Hệ thống đặt phòng trực tiếp không qua trung gian.
                </div>
                <div style="display: flex; gap: 1rem; color: #64748b;">
                    <span>Chính sách bảo mật</span>
                    <span>&bull;</span>
                    <span>Quy chế lưu trú</span>
                    <span>&bull;</span>
                    <span>Chính sách hoàn hủy</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="<?= base_url('assets/js/main.js') ?>" defer></script>
</body>
</html>
