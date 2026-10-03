<?php
/**
 * SCRIPT KHỞI TẠO TÀI KHOẢN MẪU (CHẠY 1 LẦN)
 * File: create_admin.php
 * 
 * LƯU Ý BẢO MẬT: SAU KHI CHẠY FILE NÀY ĐỂ TẠO TÀI KHOẢN XONG, HÃY XÓA FILE NÀY KHỎI DỰ ÁN!
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$accounts = [
    [
        'name'     => 'Quản Trị Viên',
        'email'    => 'admin@hotel.vn',
        'password' => 'admin123',
        'phone'    => '0988888888',
        'role'     => 'admin'
    ],
    [
        'name'     => 'Nguyễn Văn Khách',
        'email'    => 'khach@hotel.vn',
        'password' => 'khach123',
        'phone'    => '0901234567',
        'role'     => 'customer'
    ]
];

$results = [];

foreach ($accounts as $acc) {
    try {
        $hash = password_hash($acc['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password_hash, phone, role) 
                VALUES (:name, :email, :password_hash, :phone, :role)
                ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    password_hash = VALUES(password_hash),
                    phone = VALUES(phone),
                    role = VALUES(role)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name'          => $acc['name'],
            'email'         => $acc['email'],
            'password_hash' => $hash,
            'phone'         => $acc['phone'],
            'role'          => $acc['role']
        ]);

        $results[] = [
            'status' => 'success',
            'email'  => $acc['email'],
            'pass'   => $acc['password'],
            'role'   => $acc['role'],
            'msg'    => "Khởi tạo thành công tài khoản {$acc['role']} ({$acc['email']})"
        ];
    } catch (PDOException $e) {
        $results[] = [
            'status' => 'error',
            'email'  => $acc['email'],
            'msg'    => "Lỗi khi tạo tài khoản: " . $e->getMessage()
        ];
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Khởi tạo tài khoản mẫu - Grand Oasis Hotel</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        .setup-card { max-width: 650px; margin: 3rem auto; padding: 2rem; }
        .setup-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .setup-table th, .setup-table td { padding: 0.75rem; border: 1px solid var(--border); }
        .notice-danger { background-color: var(--danger-bg); color: var(--danger); padding: 1rem; border-radius: var(--radius); margin-top: 1.5rem; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card setup-card">
            <h2 class="card__title">Khởi Tạo Tài Khoản Demo Thành Công!</h2>
            <p class="card__text">Dưới đây là thông tin 2 tài khoản mẫu đã được nạp an toàn vào cơ sở dữ liệu (sử dụng <code>password_hash</code>):</p>

            <table class="setup-table">
                <thead>
                    <tr style="background:#f3f4f6">
                        <th>Vai trò</th>
                        <th>Email</th>
                        <th>Mật khẩu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $res): ?>
                        <?php if ($res['status'] === 'success'): ?>
                            <tr>
                                <td><span class="badge badge--<?= $res['role'] === 'admin' ? 'admin' : 'confirmed' ?>"><?= e(strtoupper($res['role'])) ?></span></td>
                                <td><code><?= e($res['email']) ?></code></td>
                                <td><code><?= e($res['pass']) ?></code></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="notice-danger">
                <strong>LƯU Ý BẢO MẬT:</strong> Sau khi hoàn tất kiểm tra, bạn hãy XÓA FILE <code>create_admin.php</code> khỏi thư mục dự án để tránh rủi ro bảo mật hệ thống!
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <a href="<?= base_url('login.php') ?>" class="btn btn--primary">Đến trang Đăng nhập →</a>
                <a href="<?= base_url('index.php') ?>" class="btn btn--secondary">Về Trang chủ</a>
            </div>
        </div>
    </div>
</body>
</html>
