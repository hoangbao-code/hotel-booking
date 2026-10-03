<?php
/**
 * XỬ LÝ LƯU ĐƠN ĐẶT PHÒNG (QUY TẮC Q4: TRANSACTION + SELECT FOR UPDATE + TÍNH GIÁ SERVER-SIDE)
 * File: process_booking.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('index.php'));
}

// Bắt buộc đăng nhập
require_login();

// Kiểm tra CSRF token
verify_csrf();

$userId     = (int)$_SESSION['user_id'];
$roomTypeId = (int)($_POST['room_type_id'] ?? 0);
$checkIn    = trim((string)($_POST['check_in'] ?? ''));
$checkOut   = trim((string)($_POST['check_out'] ?? ''));
$guests     = (int)($_POST['guests'] ?? 0);

$today = date('Y-m-d');

// Validate dữ liệu đầu vào
if ($roomTypeId <= 0 || $checkIn === '' || $checkOut === '' || $guests <= 0) {
    flash('error', 'Dữ liệu đặt phòng không hợp lệ.');
    redirect(base_url('rooms.php'));
}

if ($checkIn < $today || $checkOut <= $checkIn || $guests < 1 || $guests > 20) {
    flash('error', 'Khoảng thời gian hoặc số lượng khách không hợp lệ.');
    redirect(base_url('rooms.php'));
}

// THỰC THI QUY TẮC Q4 TRONG GIAO DỊCH (TRANSACTION)
try {
    $pdo->beginTransaction();

    // BƯỚC 1: Khóa dòng phòng theo room_type_id để các request cùng loại xếp hàng
    $stmtLock = $pdo->prepare("SELECT id FROM rooms WHERE room_type_id = :type_id FOR UPDATE");
    $stmtLock->execute(['type_id' => $roomTypeId]);
    $lockedRooms = $stmtLock->fetchAll();

    if (empty($lockedRooms)) {
        throw new RuntimeException("Loại phòng này hiện không có phòng nào trong hệ thống.");
    }

    // BƯỚC 2: Đọc LẠI phòng trống trong transaction (cùng điều kiện Q2, LIMIT 1)
    $sqlFindFree = "SELECT r.id, r.room_number 
                    FROM rooms r
                    WHERE r.room_type_id = :type_id AND r.status = 'active'
                      AND NOT EXISTS (
                          SELECT 1 FROM bookings b
                          WHERE b.room_id = r.id AND b.status <> 'cancelled'
                            AND b.check_in < :check_out AND b.check_out > :check_in
                      )
                    LIMIT 1";

    $stmtFree = $pdo->prepare($sqlFindFree);
    $stmtFree->execute([
        'type_id'   => $roomTypeId,
        'check_out' => $checkOut,
        'check_in'  => $checkIn
    ]);
    $freeRoom = $stmtFree->fetch();

    if (!$freeRoom) {
        throw new RuntimeException("Rất tiếc, loại phòng này vừa hết phòng trống cho khoảng ngày bạn chọn.");
    }

    $assignedRoomId = (int)$freeRoom['id'];

    // BƯỚC 3: Lấy giá và capacity trực tiếp từ DB (TUYỆT ĐỐI KHÔNG TIN TỔNG TIỀN GỬI TỪ FORM)
    $stmtTypeInfo = $pdo->prepare("SELECT name, price_per_night, capacity FROM room_types WHERE id = :id LIMIT 1");
    $stmtTypeInfo->execute(['id' => $roomTypeId]);
    $typeInfo = $stmtTypeInfo->fetch();

    if (!$typeInfo) {
        throw new RuntimeException("Không tìm thấy thông tin loại phòng.");
    }

    if ($guests > (int)$typeInfo['capacity']) {
        throw new RuntimeException("Số lượng khách ({$guests}) vượt quá sức chứa tối đa của phòng ({$typeInfo['capacity']} người).");
    }

    // Tính số đêm và tổng tiền chuẩn xác từ server
    $dIn = new DateTime($checkIn);
    $dOut = new DateTime($checkOut);
    $nights = max(1, $dIn->diff($dOut)->days);
    $pricePerNight = (float)$typeInfo['price_per_night'];
    $totalPrice = $pricePerNight * $nights;

    // BƯỚC 4: INSERT booking status='pending'
    $sqlInsert = "INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, status, total_price, created_at)
                  VALUES (:user_id, :room_id, :check_in, :check_out, :guests, 'pending', :total_price, NOW())";
    
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        'user_id'     => $userId,
        'room_id'     => $assignedRoomId,
        'check_in'    => $checkIn,
        'check_out'   => $checkOut,
        'guests'      => $guests,
        'total_price' => $totalPrice
    ]);

    // Commit giao dịch
    $pdo->commit();

    flash('success', "Đặt phòng {$typeInfo['name']} thành công! Đơn đặt của bạn đang ở trạng thái Chờ xác nhận.");
    redirect(base_url('my_bookings.php'));

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $e->getMessage());
    redirect(base_url('rooms.php?check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . urlencode((string)$guests)));

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Process Booking Error: ' . $e->getMessage());
    flash('error', 'Có lỗi xảy ra trong quá trình ghi nhận đơn đặt phòng. Vui lòng thử lại sau.');
    redirect(base_url('rooms.php'));
}
