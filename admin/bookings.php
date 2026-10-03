<?php
/**
 * QUẢN LÝ ĐẶT PHÒNG PHÂN HỆ ADMIN
 * File: admin/bookings.php
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$pageTitle = 'Quản lý Đặt phòng';
$adminPage = 'bookings';

$today = date('Y-m-d');
$tab = $_GET['tab'] ?? 'all';
if (!in_array($tab, ['all', 'pending', 'confirmed', 'cancelled'], true)) {
    $tab = 'all';
}
$search = trim((string)($_GET['q'] ?? ''));

// Xử lý POST Thao tác: Xác nhận hoặc Hủy booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action     = trim($_POST['action'] ?? '');
    $bookingId  = (int)($_POST['booking_id'] ?? 0);
    $currentTab = $_POST['tab'] ?? 'all';
    $currentQ   = $_POST['q'] ?? '';

    if ($bookingId <= 0) {
        flash('error', 'Mã đơn đặt phòng không hợp lệ.');
        redirect(base_url('admin/bookings.php?tab=' . urlencode($currentTab) . '&q=' . urlencode($currentQ)));
    }

    if ($action === 'confirm') {
        // Quy tắc Q6: Conditional UPDATE phòng ngừa race condition khi 2 admin cùng duyệt
        $sql = "UPDATE bookings 
                SET status = 'confirmed' 
                WHERE id = :id AND status = 'pending'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $bookingId]);

        if ($stmt->rowCount() > 0) {
            flash('success', 'Đã duyệt và xác nhận đơn đặt phòng #BK-' . $bookingId . ' thành công.');
        } else {
            flash('error', 'Không thể xác nhận đơn #BK-' . $bookingId . ' (đơn không ở trạng thái chờ duyệt hoặc đã được xử lý trước đó).');
        }
    } elseif ($action === 'cancel') {
        // Quy tắc Q6: Admin hủy booking có điều kiện
        $sql = "UPDATE bookings 
                SET status = 'cancelled' 
                WHERE id = :id 
                  AND status IN ('pending', 'confirmed') 
                  AND check_in >= CURDATE()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $bookingId]);

        if ($stmt->rowCount() > 0) {
            flash('success', 'Đã hủy đơn đặt phòng #BK-' . $bookingId . ' thành công.');
        } else {
            flash('error', 'Không thể hủy đơn #BK-' . $bookingId . ' (đơn không tồn tại, đã hủy trước đó hoặc đã qua ngày nhận phòng).');
        }
    } else {
        flash('error', 'Thao tác không được hỗ trợ.');
    }

    redirect(base_url('admin/bookings.php?tab=' . urlencode($currentTab) . '&q=' . urlencode($currentQ)));
}

// Đếm số lượng booking cho từng tab
$countSql = "SELECT 
                COUNT(*) AS total_all,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS total_pending,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) AS total_confirmed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS total_cancelled
             FROM bookings";
$counts = $pdo->query($countSql)->fetch();

// Xây dựng điều kiện truy vấn
$whereParts = [];
$params = [];

if ($tab === 'pending') {
    $whereParts[] = "b.status = 'pending'";
} elseif ($tab === 'confirmed') {
    $whereParts[] = "b.status = 'confirmed'";
} elseif ($tab === 'cancelled') {
    $whereParts[] = "b.status = 'cancelled'";
}

if ($search !== '') {
    $whereParts[] = "(u.name LIKE :q OR u.phone LIKE :q OR u.email LIKE :q OR b.id = :qid)";
    $params['q'] = '%' . $search . '%';
    $params['qid'] = is_numeric($search) ? (int)$search : 0;
}

$whereClause = !empty($whereParts) ? "WHERE " . implode(" AND ", $whereParts) : "";

$sql = "SELECT b.id, b.check_in, b.check_out, b.guests, b.status, b.total_price, b.created_at,
               u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
               r.room_number,
               rt.name AS room_type_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        $whereClause
        ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/_nav.php'; ?>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card__body" style="border-bottom: 1px solid var(--border); padding: 1.5rem 1.75rem;">
        <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--navy); margin-bottom: 0.25rem;">
            Danh Sách Đơn Đặt Phòng
        </h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
            Theo dõi, kiểm tra tình trạng buồng phòng và thực hiện thao tác duyệt / hủy theo quy chuẩn Q6
        </p>
    </div>

    <div style="padding: 1.75rem;">
        <!-- Thanh Tìm kiếm & Lọc Đơn Hàng -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <!-- Tabs Bộ Lọc -->
            <div class="tabs" style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">
                <a href="<?= base_url('admin/bookings.php?tab=all' . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" 
                   class="tab-btn <?= $tab === 'all' ? 'tab-btn--active' : '' ?>">
                    Tất cả (<?= (int)($counts['total_all'] ?? 0) ?>)
                </a>
                <a href="<?= base_url('admin/bookings.php?tab=pending' . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" 
                   class="tab-btn <?= $tab === 'pending' ? 'tab-btn--active' : '' ?>">
                    Chờ duyệt (<?= (int)($counts['total_pending'] ?? 0) ?>)
                </a>
                <a href="<?= base_url('admin/bookings.php?tab=confirmed' . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" 
                   class="tab-btn <?= $tab === 'confirmed' ? 'tab-btn--active' : '' ?>">
                    Đã duyệt (<?= (int)($counts['total_confirmed'] ?? 0) ?>)
                </a>
                <a href="<?= base_url('admin/bookings.php?tab=cancelled' . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" 
                   class="tab-btn <?= $tab === 'cancelled' ? 'tab-btn--active' : '' ?>">
                    Đã hủy (<?= (int)($counts['total_cancelled'] ?? 0) ?>)
                </a>
            </div>

            <!-- Form Tìm kiếm nhanh -->
            <form action="<?= base_url('admin/bookings.php') ?>" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <input type="text" name="q" class="form-control" style="width: 250px; padding: 0.45rem 0.85rem; font-size: 0.88rem;" 
                       placeholder="Tìm mã đơn, tên, SĐT..." value="<?= e($search) ?>">
                <button type="submit" class="btn btn--primary btn--sm" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                    <?= icon('search', '', 14) ?> Tìm
                </button>
                <?php if ($search !== ''): ?>
                    <a href="<?= base_url('admin/bookings.php?tab=' . urlencode($tab)) ?>" class="btn btn--secondary btn--sm" title="Xóa bộ lọc tìm kiếm">
                        Xóa lọc
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($bookings)): ?>
            <div style="text-align: center; padding: 4rem 1rem; color: var(--text-muted);">
                <div style="margin-bottom: 0.75rem; color: #94a3b8;"><?= icon('info', '', 36) ?></div>
                Không tìm thấy đơn đặt phòng nào phù hợp với điều kiện tìm kiếm.
            </div>
        <?php else: ?>
            <div class="table-responsive" style="margin-bottom: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Hạng & Số phòng</th>
                            <th>Thời gian lưu trú</th>
                            <th>Khách</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th style="text-align: center; min-width: 200px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): 
                            $status = (string)$b['status'];
                            $badgeClass = 'badge--pending';
                            $statusText = 'Chờ duyệt';
                            if ($status === 'confirmed') {
                                $badgeClass = 'badge--confirmed';
                                $statusText = 'Đã duyệt';
                            } elseif ($status === 'cancelled') {
                                $badgeClass = 'badge--cancelled';
                                $statusText = 'Đã hủy';
                            }

                            $canConfirm = ($status === 'pending');
                            $canCancel  = in_array($status, ['pending', 'confirmed'], true) && ($b['check_in'] >= $today);
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--navy); font-family: monospace; font-size: 0.95rem;">#BK-<?= (int)$b['id'] ?></strong>
                                </td>
                                <td>
                                    <strong style="color: var(--navy); font-size: 0.92rem;"><?= e($b['customer_name']) ?></strong>
                                    <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                            <?= icon('phone', '', 12) ?> <?= e($b['customer_phone'] ?? 'Chưa có SĐT') ?>
                                        </span>
                                        <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                            <?= icon('mail', '', 12) ?> <?= e($b['customer_email']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: var(--navy);"><?= e($b['room_type_name']) ?></strong><br>
                                    <span class="badge" style="background:#f1f5f9; color:#334155; font-weight: 700; margin-top: 0.2rem;">
                                        Phòng <?= e($b['room_number']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--navy);"><?= date('d/m/Y', strtotime($b['check_in'])) ?></div>
                                    <small style="color: var(--text-muted);">đến <?= date('d/m/Y', strtotime($b['check_out'])) ?></small>
                                </td>
                                <td><?= (int)$b['guests'] ?> khách</td>
                                <td style="font-weight: 700; color: var(--navy); font-size: 1rem;">
                                    <?= number_format((float)$b['total_price'], 0, ',', '.') ?> ₫
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                </td>
                                <td>
                                    <small style="color: var(--text-muted);"><?= date('H:i d/m/Y', strtotime($b['created_at'])) ?></small>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 0.35rem; align-items: center; justify-content: center; flex-wrap: wrap;">
                                        <!-- Xem Voucher -->
                                        <a href="<?= base_url('booking_voucher.php?id=' . (int)$b['id']) ?>" 
                                           class="btn btn--secondary btn--sm" 
                                           target="_blank" 
                                           title="Xem và in phiếu xác nhận đặt phòng"
                                           style="display: inline-flex; align-items: center; gap: 0.3rem;">
                                            <?= icon('printer', '', 13) ?> Phiếu
                                        </a>

                                        <?php if ($canConfirm): ?>
                                            <form method="POST" action="<?= base_url('admin/bookings.php') ?>" style="display: inline;" 
                                                  onsubmit="return confirm('Xác nhận duyệt giữ phòng cho đơn #BK-<?= (int)$b['id'] ?>?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="confirm">
                                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                                <input type="hidden" name="q" value="<?= e($search) ?>">
                                                <button type="submit" class="btn btn--primary btn--sm" style="background: var(--success); border-color: var(--success); display: inline-flex; align-items: center; gap: 0.3rem;">
                                                    <?= icon('check', '', 13) ?> Duyệt
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($canCancel): ?>
                                            <form method="POST" action="<?= base_url('admin/bookings.php') ?>" style="display: inline;" 
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn đặt phòng #BK-<?= (int)$b['id'] ?>?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                                <input type="hidden" name="q" value="<?= e($search) ?>">
                                                <button type="submit" class="btn btn--danger btn--sm" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                                                    <?= icon('close', '', 13) ?> Hủy
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
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
