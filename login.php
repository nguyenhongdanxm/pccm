<?php
$page_title = 'Đăng nhập';
require_once 'includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (attempt_login($user, $pass)) {
        flash('Đăng nhập thành công!', 'success');
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    } else {
        flash('Sai tên đăng nhập hoặc mật khẩu.', 'danger');
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-md-5">
<div class="card">
<div class="card-header text-center"><i class="bi bi-lock"></i> Đăng nhập Quản trị</div>
<div class="card-body">
<form method="post">
<div class="mb-3">
<label class="form-label">Tên đăng nhập</label>
<input type="text" name="username" class="form-control" required autofocus value="admin">
</div>
<div class="mb-3">
<label class="form-label">Mật khẩu</label>
<input type="password" name="password" class="form-control" required>
</div>
<button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</button>
</form>
</div>
</div>
<div class="text-center mt-3">
<a href="<?= BASE_URL ?>ketqua.php" class="text-muted small">← Xem Kết quả (không cần đăng nhập)</a>
</div>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
