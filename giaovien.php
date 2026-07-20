<?php
$page_title = 'Quản lý Giáo viên';
require_once 'includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teachers = get_teachers();
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name && !in_array($name, $teachers)) {
            $teachers[] = $name;
            $teachers = sort_teachers_by_ten($teachers);
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
    if ($_POST['action'] === 'rename') {
        $old = trim($_POST['old_name'] ?? '');
        $new = trim($_POST['new_name'] ?? '');
        if ($old && $new && $old !== $new) {
            $idx = array_search($old, $teachers);
            if ($idx !== false) {
                $teachers[$idx] = $new;
                $teachers = sort_teachers_by_ten($teachers);
                save_json(TEACHERS_FILE, $teachers);
                // Cập nhật trong phân công dạy
                $assignments = get_assignments();
                foreach ($assignments as &$a) {
                    if ($a['teacher'] === $old) $a['teacher'] = $new;
                }
                unset($a);
                save_json(ASSIGNMENTS_FILE, $assignments);
                // Cập nhật kiêm nhiệm
                $roles = get_role_assignments();
                foreach ($roles as &$a) {
                    if ($a['teacher'] === $old) $a['teacher'] = $new;
                }
                unset($a);
                save_json(ROLE_ASSIGNMENTS_FILE, $roles);
                flash("Đã đổi tên: $old → $new", 'success');
            }
        }
    }
    header('Location: ' . BASE_URL . 'giaovien.php'); exit;
}
require_once 'includes/header.php';
$teachers = get_teachers_sorted();
?>
<h3 class="mb-4"><i class="bi bi-people"></i> Quản lý Giáo viên <small class="text-muted fs-6">(sắp theo tên)</small></h3>
<div class="row">
<div class="col-md-4 mb-4"><div class="card"><div class="card-header">Thêm giáo viên mới</div>
<div class="card-body"><form method="post"><input type="hidden" name="action" value="add">
<div class="mb-3"><input type="text" name="name" class="form-control" placeholder="Họ tên giáo viên" required></div>
<button type="submit" class="btn btn-primary w-100">Thêm</button></form></div></div></div>
<div class="col-md-8"><div class="card"><div class="card-header">Danh sách (<?= count($teachers) ?>)</div>
<div class="card-body p-0"><ul class="list-group list-group-flush">
<?php foreach ($teachers as $i => $t): ?>
<li class="list-group-item">
<div class="d-flex justify-content-between align-items-center">
<span><?= $i+1 ?>. <?= e($t) ?> <small class="text-muted">(<?= e(ten_cuoi($t)) ?>)</small></span>
<div class="d-flex gap-1">
<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#edit<?= $i ?>"><i class="bi bi-pencil"></i></button>
<form method="post" class="d-inline" onsubmit="return confirm('Xóa giáo viên này?')">
<input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="<?= e($t) ?>">
<button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form>
</div></div>
<div class="collapse mt-2" id="edit<?= $i ?>">
<form method="post" class="row g-2">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="old_name" value="<?= e($t) ?>">
<div class="col"><input type="text" name="new_name" class="form-control form-control-sm" value="<?= e($t) ?>" required></div>
<div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Lưu tên</button></div>
</form>
</div>
</li><?php endforeach; ?>
</ul></div></div></div></div>
<?php require_once 'includes/footer.php'; ?>
