<?php
$page_title = 'Danh sách phân công';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $assignments = get_assignments();
        $assignments = array_values(array_filter($assignments, fn($a) => $a['id'] !== $_POST['delete_id']));
        save_json(ASSIGNMENTS_FILE, $assignments);
        flash('Đã xóa phân công.', 'success');
    }
    if (isset($_POST['delete_ids'])) {
        $ids = $_POST['ids'] ?? [];
        $assignments = get_assignments();
        $assignments = array_values(array_filter($assignments, fn($a) => !in_array($a['id'], $ids)));
        save_json(ASSIGNMENTS_FILE, $assignments);
        flash('Đã xóa ' . count($ids) . ' phân công.', 'success');
    }
    if (isset($_POST['delete_role_id'])) {
        $items = get_role_assignments();
        $items = array_values(array_filter($items, fn($a) => $a['id'] !== $_POST['delete_role_id']));
        save_json(ROLE_ASSIGNMENTS_FILE, $items);
        flash('Đã xóa kiêm nhiệm.', 'success');
        header('Location: ' . BASE_URL . 'danhsach.php?tab=kiemnhiem'); exit;
    }
    header('Location: ' . BASE_URL . 'danhsach.php'); exit;
}

require_once 'includes/header.php';
$assignments = get_assignments();
$role_items = get_role_assignments();
$tab = $_GET['tab'] ?? 'day';

$f_teacher = $_GET['teacher'] ?? '';
$f_subject = $_GET['subject'] ?? '';
$f_class = $_GET['class'] ?? '';

$filtered = $assignments;
if ($f_teacher) $filtered = array_filter($filtered, fn($a) => $a['teacher'] === $f_teacher);
if ($f_subject) $filtered = array_filter($filtered, fn($a) => $a['subject'] === $f_subject);
if ($f_class) $filtered = array_filter($filtered, fn($a) => $a['class'] === $f_class);
$filtered = array_values($filtered);

// Sort filtered by teacher name (ten)
usort($filtered, fn($a, $b) => strcmp(ten_cuoi($a['teacher']), ten_cuoi($b['teacher'])) ?: strcmp($a['teacher'], $b['teacher']));

$all_teachers = sort_teachers_by_ten(array_unique(array_column($assignments, 'teacher')));
$all_subjects = array_unique(array_column($assignments, 'subject')); sort($all_subjects);
$all_classes = array_unique(array_column($assignments, 'class')); sort($all_classes);

// Role items sorted
usort($role_items, fn($a, $b) => strcmp(ten_cuoi($a['teacher']), ten_cuoi($b['teacher'])) ?: strcmp($a['teacher'], $b['teacher']));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-list-ul"></i> Danh sách phân công</h3>
    <a href="<?= BASE_URL ?>them.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Thêm mới</a>
</div>

<ul class="nav nav-tabs mb-3">
<li class="nav-item"><a class="nav-link <?= $tab !== 'kiemnhiem' ? 'active' : '' ?>" href="<?= BASE_URL ?>danhsach.php">Dạy môn (<?= count($assignments) ?>)</a></li>
<li class="nav-item"><a class="nav-link <?= $tab === 'kiemnhiem' ? 'active' : '' ?>" href="<?= BASE_URL ?>danhsach.php?tab=kiemnhiem">Kiêm nhiệm (<?= count($role_items) ?>)</a></li>
</ul>

<?php if ($tab !== 'kiemnhiem'): ?>

<div class="card mb-3"><div class="card-body py-2">
<form method="get" class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label small mb-0">Giáo viên</label>
        <select name="teacher" class="form-select form-select-sm"><option value="">Tất cả</option>
        <?php foreach ($all_teachers as $t): ?><option value="<?= e($t) ?>" <?= $f_teacher===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select></div>
    <div class="col-md-3"><label class="form-label small mb-0">Môn học</label>
        <select name="subject" class="form-select form-select-sm"><option value="">Tất cả</option>
        <?php foreach ($all_subjects as $s): ?><option value="<?= e($s) ?>" <?= $f_subject===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select></div>
    <div class="col-md-2"><label class="form-label small mb-0">Lớp</label>
        <select name="class" class="form-select form-select-sm"><option value="">Tất cả</option>
        <?php foreach ($all_classes as $c): ?><option value="<?= e($c) ?>" <?= $f_class===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?>
        </select></div>
    <div class="col-md-2"><button type="submit" class="btn btn-outline-primary btn-sm w-100">Lọc</button></div>
    <div class="col-md-2"><a href="<?= BASE_URL ?>danhsach.php" class="btn btn-outline-secondary btn-sm w-100">Xóa lọc</a></div>
</form></div></div>

<?php if ($filtered): ?>
<form method="post">
<input type="hidden" name="delete_ids" value="1">
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover table-striped mb-0">
<thead><tr><th width="40"><input type="checkbox" id="check-all"></th><th>Giáo viên</th><th>Môn học</th><th>Lớp</th><th class="text-center">Số tiết</th><th>Ghi chú</th><th width="100"></th></tr></thead>
<tbody>
<?php foreach ($filtered as $a): ?>
<tr>
    <td><input type="checkbox" name="ids[]" value="<?= e($a['id']) ?>" class="check-item"></td>
    <td><?= e($a['teacher']) ?></td>
    <td><?= e($a['subject']) ?></td>
    <td><span class="badge bg-secondary"><?= e($a['class']) ?></span></td>
    <td class="text-center"><span class="badge badge-periods"><?= e($a['periods']) ?></span></td>
    <td class="text-muted small"><?= e($a['note'] ?? '') ?></td>
    <td class="text-nowrap">
        <a href="<?= BASE_URL ?>sua.php?type=day&id=<?= e($a['id']) ?>" class="btn btn-outline-primary btn-sm" title="Sửa"><i class="bi bi-pencil"></i></a>
        <button type="submit" name="delete_id" value="<?= e($a['id']) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xóa?')" title="Xóa"><i class="bi bi-trash"></i></button>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<div class="card-footer d-flex justify-content-between">
    <span class="text-muted small">Tổng: <?= count($filtered) ?> · Sắp theo tên</span>
    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa các mục đã chọn?')"><i class="bi bi-trash"></i> Xóa đã chọn</button>
</div></div></form>
<?php else: ?>
<div class="alert alert-info">Không có dữ liệu phù hợp.</div>
<?php endif; ?>

<?php else: /* tab kiem nhiem */ ?>

<?php if ($role_items): ?>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover table-striped mb-0">
<thead><tr><th>Giáo viên</th><th>Chức vụ</th><th>Lớp</th><th class="text-center">Tiết</th><th>Ghi chú</th><th width="100"></th></tr></thead>
<tbody>
<?php foreach ($role_items as $a): ?>
<tr>
    <td><?= e($a['teacher']) ?></td>
    <td><span class="badge bg-info text-dark"><?= e($a['role']) ?></span></td>
    <td><?= e($a['class'] ?? '') ?></td>
    <td class="text-center"><span class="badge badge-periods"><?= e($a['periods'] ?? '') ?></span></td>
    <td class="text-muted small"><?= e($a['note'] ?? '') ?></td>
    <td class="text-nowrap">
        <a href="<?= BASE_URL ?>sua.php?type=role&id=<?= e($a['id']) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
        <form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
            <button type="submit" name="delete_role_id" value="<?= e($a['id']) ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<div class="card-footer"><span class="text-muted small">Tổng: <?= count($role_items) ?> · Sắp theo tên</span></div>
</div>
<?php else: ?>
<div class="alert alert-info">Chưa có phân công kiêm nhiệm. <a href="<?= BASE_URL ?>them.php#kiemnhiem">Thêm ngay</a></div>
<?php endif; ?>

<?php endif; ?>

<script>
document.getElementById('check-all')?.addEventListener('change', function() {
    document.querySelectorAll('.check-item').forEach(cb => cb.checked = this.checked);
});
</script>
<?php require_once 'includes/footer.php'; ?>
