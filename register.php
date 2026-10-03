<?php
/**
 * TRANG ĐĂNG KÝ TÀI KHOẢN KHÁCH HÀNG
 * File: register.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Nếu đã đăng nhập, chuyển về trang chủ
if (!empty($_SESSION['user_id'])) {
    redirect(base_url('index.php'));
}

$pageTitle = 'Đăng Ký Thành Viên';

// XỬ LÝ POST ĐĂNG KÝ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim((string)($_POST['name'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $phone    = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    $errors = [];

    if ($name === '') {
        $errors[] = 'Họ và tên không được để trống.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Địa chỉ email không hợp lệ.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có độ dài ít nhất 6 ký tự.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    if (!empty($errors)) {
        flash('error', implode('<br>', $errors));
        redirect(base_url('register.php'));
    }

    try {
        // Kiểm tra email đã tồn tại chưa
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtCheck->execute(['email' => $email]);
        if ($stmtCheck->fetch()) {
            flash('error', 'Email đã tồn tại trên hệ thống. Vui lòng chọn email khác hoặc đăng nhập.');
            redirect(base_url('register.php'));
        }

        // Băm mật khẩu an toàn bằng PASSWORD_DEFAULT (BCrypt)
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password_hash, phone, role) 
                VALUES (:name, :email, :password_hash, :phone, 'customer')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'phone'         => $phone !== '' ? $phone : null
        ]);

        $userId = (int)$pdo->lastInsertId();

        // Tự động đăng nhập sau khi đăng ký thành công
        session_regenerate_id(true);
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email']= $email;
        $_SESSION['user_role'] = 'customer';

        flash('success', 'Đăng ký tài khoản thành công! Chào mừng bạn đến với Grand Oasis Resort.');
        redirect(base_url('index.php'));

    } catch (PDOException $e) {
        error_log('Register Error: ' . $e->getMessage());
        if ($e->getCode() === '23000') {
            flash('error', 'Email đã tồn tại trên hệ thống.');
        } else {
            flash('error', 'Có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại sau.');
        }
        redirect(base_url('register.php'));
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 460px; margin: 2.5rem auto;">
    <div class="card" style="box-shadow: var(--shadow); border: 1px solid var(--border);">
        <div class="card__body" style="padding: 2rem;">
            <div style="text-align: center; margin-bottom: 1.75rem;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; background: var(--primary-light); color: var(--primary); border-radius: 50%; margin-bottom: 0.75rem;">
                    <?= icon('user', '', 22) ?>
                </div>
                <h1 style="font-size: 1.45rem; font-weight: 700; color: var(--navy); margin-bottom: 0.25rem;">
                    Đăng Ký Tài Khoản
                </h1>
                <p style="color: var(--text-muted); font-size: 0.88rem;">
                    Tạo tài khoản để quản lý đơn đặt phòng và nhận ưu đãi lưu trú
                </p>
            </div>

            <form action="<?= base_url('register.php') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="reg_name" class="form-label">Họ và tên quý khách <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="reg_name" name="name" class="form-control" 
                           placeholder="Nguyễn Văn A" required autofocus>
                </div>

                <div class="form-group">
                    <label for="reg_email" class="form-label">Địa chỉ email <span style="color: var(--danger);">*</span></label>
                    <input type="email" id="reg_email" name="email" class="form-control" 
                           placeholder="name@example.com" required>
                </div>

                <div class="form-group">
                    <label for="reg_phone" class="form-label">Số điện thoại liên hệ</label>
                    <input type="tel" id="reg_phone" name="phone" class="form-control" 
                           placeholder="0912 345 678">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="form-group">
                        <label for="reg_password" class="form-label">Mật khẩu <span style="color: var(--danger);">*</span></label>
                        <input type="password" id="reg_password" name="password" class="form-control" 
                               placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_confirm" class="form-label">Xác nhận lại <span style="color: var(--danger);">*</span></label>
                        <input type="password" id="reg_confirm" name="password_confirm" class="form-control" 
                               placeholder="Nhập lại mật khẩu" minlength="6" required>
                    </div>
                </div>

                <div style="margin-top: 1.25rem; margin-bottom: 1.25rem;">
                    <button type="submit" class="btn btn--primary btn--block" style="height: 44px; font-size: 0.95rem;">
                        Tạo tài khoản mới
                    </button>
                </div>
            </form>

            <div style="text-align: center; font-size: 0.88rem; color: var(--text-muted);">
                Đã có tài khoản? 
                <a href="<?= base_url('login.php') ?>" style="color: var(--primary); font-weight: 600;">
                    Đăng nhập ngay &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
