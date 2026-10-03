<?php
/**
 * XỬ LÝ HỦY ĐẶT PHÒNG CỦA KHÁCH HÀNG
 * File: cancel_booking.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('my_bookings.php'));
}

// Bắt buộc đăng nhập
require_login();

// Kiểm tra CSRF token
verify_csrf();

$bookingId = (int)($_POST['booking_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if ($bookingId <= 0) {
    flash('error', 'Mã đơn đặt phòng không hợp lệ.');
    redirect(base_url('my_bookings.php'));
}

// Quy tắc Q5: Câu lệnh UPDATE nguyên tử (Atomic Update)
// Chỉ cho phép hủy nếu đơn thuộc về chính khách hàng đó, trạng thái đang là 'pending' hoặc 'confirmed'
// và ngày nhận phòng check_in >= CURDATE()
$sql = "UPDATE bookings 
        SET status = 'cancelled' 
        WHERE id = :id 
          AND user_id = :user_id 
          AND status IN ('pending', 'confirmed') 
          AND check_in >= CURDATE()";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'id' => $bookingId,
    'user_id' => $userId
]);

if ($stmt->rowCount() > 0) {
    flash('success', 'Đã hủy đơn đặt phòng #BK-' . $bookingId . ' thành công.');
} else {
    flash('error', 'Không thể hủy đơn đặt phòng này (đơn không tồn tại, đã qua ngày nhận phòng hoặc đã bị hủy trước đó).');
}

redirect(base_url('my_bookings.php'));
