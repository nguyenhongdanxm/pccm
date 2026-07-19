<?php
$page_title = 'Tổng quan';
require_once 'includes/functions.php';
require_login();
require_once 'includes/header.php';
$teachers = get_teachers();
$subjects = get_subjects();
$classes = get_classes();
$assignments = get_assignments();
$teacher_load = [];
foreach ($assignments as $a) {
    $t = $a['teacher'];
    $teacher_load[$t] = ($teacher_load[$t] ?? 0) + floatval($a['periods'] ?? 0);
}
arsort($teacher_load);
$max_load = $teacher_load ? max($teacher_load) : 1;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
<h3 class="mb-0"><i class="bi bi-speedometer2"></i> Tổng quan Phân công 2026-2027</h3>
</div>
<div class="row g-3 mb-4">
<div class="col-6 col-md-3"><div class="card stat-card"><div class="number"><?= count($teachers) ?></div><div class="label">Giáo viên</div></div></div>
<div class="col-6 col-md-3"><div class="card stat-card"><div class="number"><?= count($subjects) ?></div><div class="label">Môn học</div></div></div>
<div class="col-6 col-md-3"><div class="card stat-card"><div class="number"><?= count($classes) ?></div><div class="label">Lớp</div></div></div>
<div class="col-6 col-md-3"><div class="card stat-card"><div class="number"><?= count($assignments) ?></div><div class="label">Phân công</div></div></div>
</div>
<?php if ($teacher_load): ?>
<div class="card">
<div class="card-header"><i class="bi bi-people"></i> Tải dạy theo giáo viên</div>
<div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>#</th><th>Giáo viên</th><th class="text-end">Tổng tiết</th><th style="width:40%">Biểu đồ</th></tr></thead>
<tbody>
<?php $i=1; foreach ($teacher_load as $teacher => $total): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= e($teacher) ?></td>
<td class="text-end fw-bold"><?= number_format($total,1) ?></td>
<td><div class="progress" style="height:18px"><div class="progress-bar bg-primary" style="width:<?= round($total/$max_load*100) ?>%"><?= number_format($total,1) ?></div></div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle"></i> Chưa có phân công nào. <a href="<?= BASE_URL ?>them.php" class="alert-link">Thêm phân công ngay</a></div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
