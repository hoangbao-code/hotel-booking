<?php
/**
 * QUẢN LÝ LOẠI PHÒNG PHÂN HỆ ADMIN (CRUD)
 * File: admin/room_types.php
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$pageTitle = 'Quản lý Loại phòng';
$adminPage = 'room_types';

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editItem = null;

// Nếu có yêu cầu chỉnh sửa, lấy thông tin loại phòng
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $editItem = $stmt->fetch();
    if (!$editItem) {
        flash('error', 'Loại phòng yêu cầu sửa không tồn tại.');
        redirect(base_url('admin/room_types.php'));
    }
}

// Xử lý các yêu cầu POST: Thêm, Sửa, Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'ID loại phòng không hợp lệ.');
            redirect(base_url('admin/room_types.php'));
        }

        // Quy tắc Q7: Kiểm tra ràng buộc khóa ngoại (Foreign Key Restrict)
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = :id");
        $checkStmt->execute(['id' => $id]);
        $roomCount = (int)$checkStmt->fetchColumn();

        if ($roomCount > 0) {
            flash('error', "Không thể xóa loại phòng này vì đang có {$roomCount} phòng trực thuộc. Vui lòng chuyển hoặc xóa các phòng liên quan trước.");
        } else {
            try {
                $delStmt = $pdo->prepare("DELETE FROM room_types WHERE id = :id");
                $delStmt->execute(['id' => $id]);
                if ($delStmt->rowCount() > 0) {
                    flash('success', 'Đã xóa loại phòng thành công.');
                } else {
                    flash('error', 'Loại phòng không tồn tại hoặc đã bị xóa trước đó.');
                }
            } catch (\PDOException $e) {
                flash('error', 'Lỗi ràng buộc dữ liệu: Không thể xóa loại phòng này.');
            }
        }
        redirect(base_url('admin/room_types.php'));
    }

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price_per_night'] ?? 0);
        $capacity    = (int)($_POST['capacity'] ?? 0);
        $imageUrl    = trim($_POST['image_url'] ?? '');

        // Validation
        $errors = [];
        if ($name === '') {
            $errors[] = 'Tên loại phòng không được để trống.';
        }
        if ($price <= 0) {
            $errors[] = 'Giá mỗi đêm phải lớn hơn 0.';
        }
        if ($capacity < 1 || $capacity > 10) {
            $errors[] = 'Sức chứa phải từ 1 đến 10 khách.';
        }
        if ($imageUrl === '') {
            $imageUrl = 'placeholder.jpg';
        }

        // Kiểm tra trùng tên loại phòng
        $checkNameSql = "SELECT id FROM room_types WHERE name = :name" . ($id > 0 ? " AND id != :id" : "");
        $checkNameStmt = $pdo->prepare($checkNameSql);
        $checkParams = ['name' => $name];
        if ($id > 0) {
            $checkParams['id'] = $id;
        }
        $checkNameStmt->execute($checkParams);
        if ($checkNameStmt->fetch()) {
            $errors[] = "Tên loại phòng '{$name}' đã tồn tại, vui lòng chọn tên khác.";
        }

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            if ($id > 0) {
                redirect(base_url('admin/room_types.php?edit=' . $id));
            } else {
                redirect(base_url('admin/room_types.php'));
            }
        }

        if ($id > 0) {
            // Cập nhật loại phòng
            $updateSql = "UPDATE room_types 
                          SET name = :name, description = :description, price_per_night = :price, 
                              capacity = :capacity, image_url = :image_url 
                          WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                'name'        => $name,
                'description' => $description,
                'price'       => $price,
                'capacity'    => $capacity,
                'image_url'   => $imageUrl,
                'id'          => $id,
            ]);
            flash('success', "Đã cập nhật loại phòng '{$name}' thành công.");
        } else {
            // Thêm mới loại phòng
            $insertSql = "INSERT INTO room_types (name, description, price_per_night, capacity, image_url) 
                          VALUES (:name, :description, :price, :capacity, :image_url)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                'name'        => $name,
                'description' => $description,
                'price'       => $price,
                'capacity'    => $capacity,
                'image_url'   => $imageUrl,
            ]);
            flash('success', "Đã thêm loại phòng mới '{$name}' thành công.");
        }

        redirect(base_url('admin/room_types.php'));
    }
}

// Truy vấn toàn bộ loại phòng kèm số lượng phòng con
$sql = "SELECT rt.*, COUNT(r.id) AS total_rooms
        FROM room_types rt
        LEFT JOIN rooms r ON rt.id = r.room_type_id
        GROUP BY rt.id
        ORDER BY rt.id ASC";
$roomTypes = $pdo->query($sql)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/_nav.php'; ?>

<div class="grid" style="grid-template-columns: 1fr; margin-bottom: 2.5rem;">
    <!-- Form Thêm / Sửa Loại Phòng -->
    <div class="card">
        <div class="card__body" style="border-bottom: 1px solid var(--border); padding: 1.5rem 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--navy); margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <?= $editItem ? icon('edit', '', 20) . ' Chỉnh Sửa Hạng Phòng' : icon('plus', '', 20) . ' Thêm Hạng Phòng Mới' ?>
                </h2>
                <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
                    <?= $editItem ? 'Cập nhật thông tin chi tiết hạng phòng #' . (int)$editItem['id'] : 'Tạo mới hạng phòng lưu trú và thiết lập đơn giá mỗi đêm' ?>
                </p>
            </div>
            <?php if ($editItem): ?>
                <a href="<?= base_url('admin/room_types.php') ?>" class="btn btn--secondary btn--sm">
                    Hủy chỉnh sửa
                </a>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?= base_url('admin/room_types.php') ?>" style="padding: 1.75rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $editItem ? (int)$editItem['id'] : 0 ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="name">Tên hạng phòng <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= e($editItem['name'] ?? '') ?>" required placeholder="VD: Premium Deluxe Ocean View">
                </div>

                <div class="form-group">
                    <label class="form-label" for="price_per_night">Đơn giá / đêm (VNĐ) <span style="color: var(--danger);">*</span></label>
                    <input type="number" id="price_per_night" name="price_per_night" class="form-control" 
                           min="1000" step="1000" value="<?= $editItem ? (int)$editItem['price_per_night'] : '500000' ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="capacity">Sức chứa tối đa (Khách) <span style="color: var(--danger);">*</span></label>
                    <input type="number" id="capacity" name="capacity" class="form-control" 
                           min="1" max="10" value="<?= $editItem ? (int)$editItem['capacity'] : 2 ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="image_url">File ảnh đại diện</label>
                    <input type="text" id="image_url" name="image_url" class="form-control" 
                           value="<?= e($editItem['image_url'] ?? 'deluxe.jpg') ?>" placeholder="VD: deluxe.jpg, standard.jpg, suite.jpg">
                    <div class="form-help">Ảnh có sẵn: <code>standard.jpg</code>, <code>deluxe.jpg</code>, <code>suite.jpg</code></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Mô tả tiện nghi & vị trí phòng</label>
                <textarea id="description" name="description" class="form-control" rows="3" 
                          placeholder="Mô tả không gian, tầm nhìn biển/phố, tiện nghi bồn tắm sục, ban công..."><?= e($editItem['description'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="submit" class="btn btn--primary">
                    <?= $editItem ? 'Cập Nhật Thay Đổi' : 'Lưu Hạng Phòng Mới' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Bảng Danh Sách Loại Phòng Hiện Tại -->
    <div class="card">
        <div class="card__body" style="border-bottom: 1px solid var(--border); padding: 1.5rem 1.75rem;">
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--navy); margin-bottom: 0.25rem;">
                Danh Sách Các Hạng Phòng Đang Phân Phối
            </h2>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
                Tổng số <?= count($roomTypes) ?> hạng phòng được thiết lập trên hệ thống
            </p>
        </div>

        <div style="padding: 1.5rem;">
            <div class="table-responsive" style="margin-bottom: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Tên loại phòng</th>
                            <th>Giá / Đêm</th>
                            <th>Sức chứa</th>
                            <th>Phòng trực thuộc</th>
                            <th>Mô tả</th>
                            <th style="text-align: center; min-width: 140px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roomTypes as $rt): ?>
                            <tr>
                                <td style="width: 90px;">
                                    <div style="width: 80px; height: 54px; border-radius: 8px; overflow: hidden; background: #0f172a;">
                                        <img src="<?= base_url('uploads/' . (!empty($rt['image_url']) ? $rt['image_url'] : 'placeholder.jpg')) ?>" 
                                             alt="<?= e($rt['name']) ?>" 
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </td>
                                <td><strong style="color: var(--navy); font-size: 1rem;"><?= e($rt['name']) ?></strong></td>
                                <td style="font-weight: 800; color: var(--primary-dark); font-size: 1.05rem;">
                                    <?= number_format((float)$rt['price_per_night'], 0, ',', '.') ?> ₫
                                </td>
                                <td><?= (int)$rt['capacity'] ?> khách</td>
                                <td>
                                    <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 700;">
                                        <?= (int)$rt['total_rooms'] ?> phòng
                                    </span>
                                </td>
                                <td style="max-width: 280px; font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                                    <?= e($rt['description'] ?? 'Chưa có mô tả') ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 0.5rem; align-items: center;">
                                        <a href="<?= base_url('admin/room_types.php?edit=' . (int)$rt['id']) ?>" 
                                           class="btn btn--secondary btn--sm">
                                            Sửa
                                        </a>

                                        <form method="POST" action="<?= base_url('admin/room_types.php') ?>" style="display: inline;" 
                                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa loại phòng \'<?= e($rt['name']) ?>\'? Thao tác này không thể hoàn tác!');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$rt['id'] ?>">
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
