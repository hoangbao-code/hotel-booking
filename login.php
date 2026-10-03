<?php
/**
 * TRANG ĐĂNG NHẬP HỆ THỐNG
 * File: login.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Nếu có yêu cầu chuyển tài khoản (switch) hoặc đăng nhập lại quyền admin
if (isset($_GET['switch']) || isset($_GET['admin'])) {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
} elseif (!empty($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Nếu đã đăng nhập: admin vào trang quản trị, customer về trang chủ
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        redirect(base_url('admin/index.php'));
    } else {
        redirect(base_url('index.php'));
    }
}

$pageTitle = 'Đăng Nhập';

// XỬ LÝ POST ĐĂNG NHẬP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        flash('error', 'Vui lòng nhập đầy đủ email và mật khẩu.');
        redirect(base_url('login.php'));
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Đăng nhập thành công: Chống Session Fixation
            session_regenerate_id(true);

            $_SESSION['user_id']    = (int)$user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];

            flash('success', "Xin chào {$user['name']}, bạn đã đăng nhập thành công!");

            // Phân luồng điều hướng theo Role
            if ($user['role'] === 'admin') {
                if (!empty($_SESSION['redirect_after_login']) && str_contains($_SESSION['redirect_after_login'], 'admin/')) {
                    $target = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirect($target);
                }
                unset($_SESSION['redirect_after_login']);
                redirect(base_url('admin/index.php'));
            } else {
                if (!empty($_SESSION['redirect_after_login'])) {
                    $target = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirect($target);
                }
                redirect(base_url('index.php'));
            }

        } else {
            flash('error', 'Email hoặc mật khẩu không chính xác.');
            redirect(base_url('login.php'));
        }

    } catch (PDOException $e) {
        error_log('Login Error: ' . $e->getMessage());
        flash('error', 'Hệ thống đang bận. Vui lòng thử lại sau.');
        redirect(base_url('login.php'));
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 440px; margin: 2.5rem auto;">
    <div class="card" style="box-shadow: var(--shadow); border: 1px solid var(--border);">
        <div class="card__body" style="padding: 2rem;">
            <div style="text-align: center; margin-bottom: 1.75rem;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; background: var(--primary-light); color: var(--primary); border-radius: 50%; margin-bottom: 0.75rem;">
                    <?= icon('user', '', 22) ?>
                </div>
                <h1 style="font-size: 1.45rem; font-weight: 700; color: var(--navy); margin-bottom: 0.25rem;">
                    Đăng Nhập Tài Khoản
                </h1>
                <p style="color: var(--text-muted); font-size: 0.88rem;">
                    Truy cập hệ thống đặt phòng Khách sạn Grand Oasis
                </p>
            </div>

            <form action="<?= base_url('login.php') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="login_email" class="form-label">Địa chỉ email</label>
                    <input type="email" id="login_email" name="email" class="form-control" 
                           placeholder="name@example.com" required autofocus>
                </div>

                <div class="form-group">
                    <label for="login_password" class="form-label">Mật khẩu</label>
                    <input type="password" id="login_password" name="password" class="form-control" 
                           placeholder="••••••••" required>
                </div>

                <div style="margin-top: 1.25rem; margin-bottom: 1.25rem;">
                    <button type="submit" class="btn btn--primary btn--block" style="height: 44px; font-size: 0.95rem;">
                        Đăng nhập
                    </button>
                </div>
            </form>

            <!-- Tài khoản thử nghiệm nhanh (Hỗ trợ chấm bài / test) -->
            <div style="background: #f8fafc; border-radius: var(--radius-sm); padding: 1rem; border: 1px dashed var(--border-dark); font-size: 0.82rem; margin-bottom: 1.25rem;">
                <div style="font-weight: 600; color: var(--text); margin-bottom: 0.5rem;">
                    Tài khoản thử nghiệm hệ thống (Bấm để điền nhanh):
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
                    <button type="button" class="btn btn--navy btn--sm" onclick="quickFill('admin@hotel.vn', 'admin123')">
                        Quản trị viên (Admin)
                    </button>
                    <button type="button" class="btn btn--outline btn--sm" onclick="quickFill('khach@hotel.vn', 'khach123')">
                        Khách hàng (Customer)
                    </button>
                </div>
                <div style="color: var(--text-muted); font-size: 0.78rem;">
                    &bull; Admin: <code>admin@hotel.vn</code> / <code>admin123</code><br>
                    &bull; Khách: <code>khach@hotel.vn</code> / <code>khach123</code>
                </div>
            </div>

            <script>
            function quickFill(email, pass) {
                var emailInput = document.getElementById('login_email');
                var passInput = document.getElementById('login_password');
                if (emailInput && passInput) {
                    emailInput.value = email;
                    passInput.value = pass;
                    emailInput.focus();
                }
            }
            </script>

            <div style="text-align: center; font-size: 0.88rem; color: var(--text-muted);">
                Chưa có tài khoản? 
                <a href="<?= base_url('register.php') ?>" style="color: var(--primary); font-weight: 600;">
                    Đăng ký tài khoản mới &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
