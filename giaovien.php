<?php
$page_title = 'Quản lý Giáo viên';
require_once 'includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teachers = get_teachers();
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name && !in_array($name, $teachers)) {
            $teachers[] = $name; sort($teachers);
            save_json(TEACHERS_FILE, $teachers);
            flash("Đã thêm giáo viên: $name", 'success');
        } elseif (in_array($name, $teachers)) {
            flash('Giáo viên đã tồn tại.', 'warning');
        }
    }
    if ($_POST['action'] === 'delete') {
        $name = trim($_POST['name'] ?? '');
        $teachers = array_values(array_filter($teachers, fn($t) => $t !== $name));
        save_json(TEACHERS_FILE, $teachers);
        flash("Đã xóa: $name", 'success');
    }
    header('Location: ' . BASE_URL . 'giaovien.php'); exit;
}
require_once 'includes/header.php';
$teachers = get_teachers(); sort($teachers);
?>
<h3 class="mb-4"><i class="bi bi-people"></i> Quản lý Giáo viên</h3>
<div class="row">
<div class="col-md-4 mb-4"><div class="card"><div class="card-header">Thêm giáo viên mới</div>
<div class="card-body"><form method="post"><input type="hidden" name="action" value="add">
<div class="mb-3"><input type="text" name="name" class="form-control" placeholder="Họ tên giáo viên" required></div>
<button type="submit" class="btn btn-primary w-100">Thêm</button></form></div></div></div>
<div class="col-md-8"><div class="card"><div class="card-header">Danh sách (<?= count($teachers) ?>)</div>
<div class="card-body p-0"><ul class="list-group list-group-flush">
<?php foreach ($teachers as $i => $t): ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
<span><?= $i+1 ?>. <?= e($t) ?></span>
<form method="post" class="d-inline" onsubmit="return confirm('Xóa giáo viên này?')">
<input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="<?= e($t) ?>">
<button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form>
</li><?php endforeach; ?>
</ul></div></div></div></div>
<?php require_once 'includes/footer.php'; ?>
