<?php
/**
 * QUẢN LÝ DANH SÁCH VÀ TRẠNG THÁI PHÒNG (ADMIN ROOMS)
 * File: admin/rooms.php
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$pageTitle = 'Quản lý Phòng';
$adminPage = 'rooms';

// Lấy danh sách loại phòng để phục vụ dropdown thêm mới
$roomTypes = $pdo->query("SELECT id, name FROM room_types ORDER BY name ASC")->fetchAll();

// Xử lý các thao tác POST: Thêm phòng, Đổi trạng thái bảo trì, Xóa phòng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = trim($_POST['action'] ?? '');

    // 1. Thêm phòng mới
    if ($action === 'create') {
        $roomNumber = trim($_POST['room_number'] ?? '');
        $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
        $status     = trim($_POST['status'] ?? 'active');

        if (!in_array($status, ['active', 'maintenance'], true)) {
            $status = 'active';
        }

        $errors = [];
        if ($roomNumber === '') {
            $errors[] = 'Số phòng không được để trống.';
        }
        if ($roomTypeId <= 0) {
            $errors[] = 'Vui lòng chọn loại phòng hợp lệ.';
        }

        // Kiểm tra trùng số phòng
        if ($roomNumber !== '') {
            $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_number = :room_number");
            $stmt->execute(['room_number' => $roomNumber]);
            if ($stmt->fetch()) {
                $errors[] = "Số phòng '{$roomNumber}' đã tồn tại trong hệ thống. Vui lòng chọn số phòng khác.";
            }
        }

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
        } else {
            $sql = "INSERT INTO rooms (room_number, room_type_id, status) VALUES (:room_number, :room_type_id, :status)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'room_number'  => $roomNumber,
                'room_type_id' => $roomTypeId,
                'status'       => $status,
            ]);
            flash('success', "Đã thêm mới phòng {$roomNumber} thành công.");
        }
        redirect(base_url('admin/rooms.php'));
    }

    // 2. Chuyển đổi trạng thái Hoạt động <-> Bảo trì
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Mã phòng không hợp lệ.');
            redirect(base_url('admin/rooms.php'));
        }

        // Lấy trạng thái hiện tại
        $stmt = $pdo->prepare("SELECT room_number, status FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $room = $stmt->fetch();

        if (!$room) {
            flash('error', 'Phòng không tồn tại.');
            redirect(base_url('admin/rooms.php'));
        }

        $newStatus = ($room['status'] === 'active') ? 'maintenance' : 'active';
        $updateStmt = $pdo->prepare("UPDATE rooms SET status = :status WHERE id = :id");
        $updateStmt->execute(['status' => $newStatus, 'id' => $id]);

        $statusLabel = ($newStatus === 'active') ? 'Hoạt động (Sẵn sàng nhận khách)' : 'Bảo trì (Tạm ngưng đón khách)';
        flash('success', "Đã chuyển trạng thái phòng {$room['room_number']} sang: <strong>{$statusLabel}</strong>.");
        redirect(base_url('admin/rooms.php'));
    }

    // 3. Xóa phòng (Kiểm tra khóa ngoại với bookings)
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Mã phòng không hợp lệ.');
            redirect(base_url('admin/rooms.php'));
        }

        // Quy tắc Q7: Kiểm tra xem phòng đã có booking nào chưa
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = :id");
        $checkStmt->execute(['id' => $id]);
        $bookingCount = (int)$checkStmt->fetchColumn();

        if ($bookingCount > 0) {
            flash('error', "Không thể xóa phòng này vì đã có {$bookingCount} đơn đặt phòng liên kết. Bạn nên chuyển phòng sang trạng thái 'Bảo trì' thay vì xóa.");
        } else {
            try {
                $delStmt = $pdo->prepare("DELETE FROM rooms WHERE id = :id");
                $delStmt->execute(['id' => $id]);
                if ($delStmt->rowCount() > 0) {
                    flash('success', 'Đã xóa phòng thành công khỏi hệ thống.');
                } else {
                    flash('error', 'Phòng không tồn tại hoặc đã bị xóa trước đó.');
                }
            } catch (\PDOException $e) {
                flash('error', 'Lỗi ràng buộc dữ liệu: Không thể xóa phòng này.');
            }
        }
        redirect(base_url('admin/rooms.php'));
    }
}

// Lấy danh sách toàn bộ phòng kèm tên loại phòng
$sql = "SELECT r.id, r.room_number, r.status, rt.name AS room_type_name,
               (SELECT COUNT(*) FROM bookings b WHERE b.room_id = r.id) AS total_bookings
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        ORDER BY r.room_number ASC";
$rooms = $pdo->query($sql)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/_nav.php'; ?>

<div class="grid" style="grid-template-columns: 1fr; margin-bottom: 2.5rem;">
    <!-- Form Thêm Phòng Mới -->
    <div class="card">
        <div class="card__body" style="border-bottom: 1px solid var(--border); padding: 1.5rem 1.75rem;">
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--navy); margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <?= icon('plus', '', 20) ?> Thêm Phòng Mới Vào Hệ Thống
            </h2>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
                Khai báo mã số phòng vật lý và gán vào hạng phòng tương ứng
            </p>
        </div>

        <form method="POST" action="<?= base_url('admin/rooms.php') ?>" style="padding: 1.75rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="room_number">Số phòng <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="room_number" name="room_number" class="form-control" 
                           placeholder="VD: 105, 204, 305..." required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="room_type_id">Loại phòng trực thuộc <span style="color: var(--danger);">*</span></label>
                    <select id="room_type_id" name="room_type_id" class="form-control" required>
                        <option value="">-- Chọn hạng phòng --</option>
                        <?php foreach ($roomTypes as $rt): ?>
                            <option value="<?= (int)$rt['id'] ?>"><?= e($rt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="status">Trạng thái ban đầu</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active">Hoạt động (Sẵn sàng nhận khách)</option>
                        <option value="maintenance">Bảo trì (Tạm ngưng đón khách)</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn--primary btn--block" style="height: 48px;">
                        Lưu Phòng Mới
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Danh Sách Phòng -->
    <div class="card">
        <div class="card__body" style="border-bottom: 1px solid var(--border); padding: 1.5rem 1.75rem;">
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--navy); margin-bottom: 0.25rem;">
                Danh Sách Tất Cả Phòng Vật Lý
            </h2>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
                Tổng số <?= count($rooms) ?> phòng được quản lý trong resort
            </p>
        </div>

        <div style="padding: 1.5rem;">
            <div class="table-responsive" style="margin-bottom: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Số phòng</th>
                            <th>Hạng phòng</th>
                            <th>Trạng thái vận hành</th>
                            <th>Lịch sử đơn</th>
                            <th style="text-align: center; min-width: 200px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $r): 
                            $isActive = ($r['status'] === 'active');
                            $badgeClass = $isActive ? 'badge--active' : 'badge--maintenance';
                            $statusText = $isActive ? 'Hoạt động' : 'Bảo trì';
                        ?>
                            <tr>
                                <td>
                                    <strong style="font-size: 1.05rem; color: var(--navy); font-family: monospace;">
                                        Phòng <?= e($r['room_number']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong style="color: var(--navy); font-size: 0.95rem;"><?= e($r['room_type_name']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                </td>
                                <td>
                                    <small style="color: var(--text-muted);">
                                        <?= (int)$r['total_bookings'] ?> lượt đặt phòng
                                    </small>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 0.4rem; align-items: center; justify-content: center;">
                                        <!-- Nút đổi trạng thái bảo trì / hoạt động -->
                                        <form method="POST" action="<?= base_url('admin/rooms.php') ?>" style="display: inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button type="submit" class="btn <?= $isActive ? 'btn--warning' : 'btn--secondary' ?> btn--sm" 
                                                    title="<?= $isActive ? 'Chuyển sang bảo trì' : 'Kích hoạt hoạt động' ?>">
                                                <?= $isActive ? 'Bảo trì' : 'Kích hoạt' ?>
                                            </button>
                                        </form>

                                        <!-- Nút xóa phòng -->
                                        <form method="POST" action="<?= base_url('admin/rooms.php') ?>" style="display: inline;" 
                                              onsubmit="return confirm('Bạn có chắc muốn xóa phòng <?= e($r['room_number']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button type="submit" class="btn btn--danger btn--sm">
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
