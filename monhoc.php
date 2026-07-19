<?php
$page_title = 'Quản lý Môn học';
require_once 'includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjects = get_subjects();
    if ($_POST['action'] === 'update') {
        $subject = $_POST['subject'] ?? '';
        if (isset($subjects[$subject])) {
            $new = [];
            foreach (['6','7','8','9','10','11','12'] as $g) {
                $val = trim($_POST["grade_$g"] ?? '');
                if ($val !== '' && is_numeric($val)) $new[$g] = floatval($val);
            }
            $subjects[$subject] = $new;
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã cập nhật số tiết môn $subject", 'success');
        }
    }
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name && !isset($subjects[$name])) {
            $subjects[$name] = [];
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã thêm môn: $name", 'success');
        }
    }
    header('Location: monhoc.php'); exit;
}
require_once 'includes/header.php';
$subjects = get_subjects(); ksort($subjects);
$grades = ['6','7','8','9','10','11','12'];
?>
<h3 class="mb-4"><i class="bi bi-book"></i> Quản lý Môn học & Số tiết chuẩn</h3>
<div class="alert alert-info"><i class="bi bi-info-circle"></i> Số tiết chuẩn được dùng để <strong>tự động điền</strong> khi chọn Môn + Lớp.</div>
<div class="row mb-4"><div class="col-md-4"><div class="card"><div class="card-header">Thêm môn mới</div>
<div class="card-body"><form method="post"><input type="hidden" name="action" value="add">
<div class="mb-3"><input type="text" name="name" class="form-control" placeholder="Tên môn học" required></div>
<button type="submit" class="btn btn-primary w-100">Thêm môn</button></form></div></div></div></div>
<?php foreach ($subjects as $subject => $grades_data): ?>
<div class="card mb-3"><div class="card-header"><?= e($subject) ?></div>
<div class="card-body"><form method="post" class="row g-2 align-items-end">
<input type="hidden" name="action" value="update"><input type="hidden" name="subject" value="<?= e($subject) ?>">
<?php foreach ($grades as $g): ?>
<div class="col"><label class="form-label small">Khối <?= $g ?></label>
<input type="number" name="grade_<?= $g ?>" class="form-control form-control-sm" step="0.1" min="0" max="10"
value="<?= isset($grades_data[$g]) ? e($grades_data[$g]) : '' ?>"></div>
<?php endforeach; ?>
<div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Lưu</button></div>
</form></div></div>
<?php endforeach; ?>
<?php require_once 'includes/footer.php'; ?>
