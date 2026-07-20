<?php
$page_title = 'Rà soát · Lọc phân công';
require_once 'includes/functions.php';
require_login();
require_once 'includes/header.php';

$assignments = get_assignments();
$role_items = get_role_assignments();
$subjects = get_subjects();
$classes = get_classes();
$roles = get_roles();
$loads = get_teacher_loads();
$teachers = get_teachers_sorted();

// Trùng môn+lớp
$slot = [];
foreach ($assignments as $a) {
    $k = $a['subject'] . '|' . $a['class'];
    $slot[$k][] = $a['teacher'];
}
$conflicts = [];
foreach ($slot as $k => $list) {
    $uniq = array_values(array_unique($list));
    if (count($uniq) > 1) {
        [$s, $c] = explode('|', $k, 2);
        $conflicts[] = ['subject' => $s, 'class' => $c, 'teachers' => $uniq];
    }
}

// Trùng kiêm nhiệm+lớp
$role_slot = [];
foreach ($role_items as $a) {
    if (empty($a['class'])) continue;
    $k = $a['role'] . '|' . $a['class'];
    $role_slot[$k][] = $a['teacher'];
}
$role_conflicts = [];
foreach ($role_slot as $k => $list) {
    $uniq = array_values(array_unique($list));
    if (count($uniq) > 1) {
        [$r, $c] = explode('|', $k, 2);
        $role_conflicts[] = ['role' => $r, 'class' => $c, 'teachers' => $uniq];
    }
}

// Thiếu môn+lớp
$missing = [];
foreach ($classes as $cls) {
    $grade = get_grade($cls);
    foreach ($subjects as $sub => $grades) {
        if (!isset($grades[$grade]) || floatval($grades[$grade]) <= 0) continue;
        $found = false;
        foreach ($assignments as $a) {
            if ($a['subject'] === $sub && $a['class'] === $cls) { $found = true; break; }
        }
        if (!$found) $missing[] = ['subject' => $sub, 'class' => $cls, 'periods' => $grades[$grade]];
    }
}

// Thiếu GVCN
$gvcn_missing = [];
$has_gvcn = false;
foreach ($roles as $r) { if ($r['name'] === 'GVCN') { $has_gvcn = true; break; } }
if ($has_gvcn) {
    foreach ($classes as $cls) {
        $ok = false;
        foreach ($role_items as $a) {
            if (($a['role'] ?? '') === 'GVCN' && ($a['class'] ?? '') === $cls) { $ok = true; break; }
        }
        if (!$ok) $gvcn_missing[] = $cls;
    }
}

// Thiếu / thừa tiết
$under = []; $over = [];
foreach ($teachers as $t) {
    $row = $loads[$t] ?? null;
    $total = $row['total'] ?? 0;
    $quota = get_quota($t);
    $diff = $total - $quota;
    $item = ['teacher' => $t, 'level' => get_teacher_level($t), 'total' => $total, 'quota' => $quota, 'diff' => $diff,
        'khxh' => get_teacher_flags($t)['khxh'], 'khtn' => get_teacher_flags($t)['khtn'], 'tap_su' => get_teacher_flags($t)['tap_su']];
    if ($diff < -0.05) $under[] = $item;
    elseif ($diff > 0.05) $over[] = $item;
}
usort($under, fn($a,$b) => $a['diff'] <=> $b['diff']);
usort($over, fn($a,$b) => $b['diff'] <=> $a['diff']);

// Lọc
$f = $_GET['f'] ?? 'all';
$q = trim($_GET['q'] ?? '');
$f_level = $_GET['level'] ?? '';
$f_flag = $_GET['flag'] ?? ''; // khxh|khtn|tap_su
$f_subject = $_GET['subject'] ?? '';
$f_class = $_GET['class'] ?? '';

// Lọc danh sách thiếu môn
$missing_view = $missing;
if ($f_subject) $missing_view = array_values(array_filter($missing_view, fn($m) => $m['subject'] === $f_subject));
if ($f_class) $missing_view = array_values(array_filter($missing_view, fn($m) => $m['class'] === $f_class));
if ($q) $missing_view = array_values(array_filter($missing_view, fn($m) => mb_stripos($m['subject'].' '.$m['class'], $q) !== false));

// Lọc under/over
$filter_load = function($list) use ($q, $f_level, $f_flag) {
    return array_values(array_filter($list, function($u) use ($q, $f_level, $f_flag) {
        if ($q && mb_stripos($u['teacher'], $q) === false) return false;
        if ($f_level && strpos($u['level'], $f_level) === false) return false;
        if ($f_flag === 'khxh' && empty($u['khxh'])) return false;
        if ($f_flag === 'khtn' && empty($u['khtn'])) return false;
        if ($f_flag === 'tap_su' && empty($u['tap_su'])) return false;
        return true;
    }));
};
$under_v = $filter_load($under);
$over_v = $filter_load($over);

$n_conflict = count($conflicts) + count($role_conflicts);
$sub_names = array_keys($subjects); sort($sub_names);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
<h3 class="mb-0"><i class="bi bi-search"></i> Rà soát · Lọc</h3>
<a href="<?= BASE_URL ?>them.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-circle"></i> Thêm phân công</a>
</div>

<?php if ($n_conflict > 0): ?>
<div class="danger-box">
<strong><i class="bi bi-exclamation-octagon-fill"></i> Cảnh báo trùng:</strong>
Có <strong><?= $n_conflict ?></strong> trường hợp môn/chức vụ + lớp đã giao cho nhiều người.
<a href="?f=conflict" class="fw-semibold">Xem ngay →</a>
</div>
<?php endif; ?>

<div class="row g-2 mb-3">
<div class="col-6 col-md"><div class="card stat-card py-2 <?= $n_conflict?'border border-danger':'' ?>"><div class="number fs-4 <?= $n_conflict?'text-danger':'' ?>"><?= $n_conflict ?></div><div class="label">Trùng phân công</div></div></div>
<div class="col-6 col-md"><div class="card stat-card py-2"><div class="number fs-4 text-warning"><?= count($missing) ?></div><div class="label">Thiếu môn+lớp</div></div></div>
<div class="col-6 col-md"><div class="card stat-card py-2"><div class="number fs-4"><?= count($gvcn_missing) ?></div><div class="label">Thiếu GVCN</div></div></div>
<div class="col-6 col-md"><div class="card stat-card py-2"><div class="number fs-4 text-warning"><?= count($under) ?></div><div class="label">Thiếu tiết</div></div></div>
<div class="col-6 col-md"><div class="card stat-card py-2"><div class="number fs-4 text-danger"><?= count($over) ?></div><div class="label">Thừa tiết</div></div></div>
</div>

<ul class="nav nav-tabs mb-3">
<li class="nav-item"><a class="nav-link <?= $f==='all'?'active':'' ?>" href="?f=all">Tổng quan</a></li>
<li class="nav-item"><a class="nav-link <?= $f==='conflict'?'active':'' ?>" href="?f=conflict">Trùng (<?= $n_conflict ?>)</a></li>
<li class="nav-item"><a class="nav-link <?= $f==='missing'?'active':'' ?>" href="?f=missing">Thiếu môn (<?= count($missing) ?>)</a></li>
<li class="nav-item"><a class="nav-link <?= $f==='gvcn'?'active':'' ?>" href="?f=gvcn">GVCN (<?= count($gvcn_missing) ?>)</a></li>
<li class="nav-item"><a class="nav-link <?= $f==='load'?'active':'' ?>" href="?f=load">Thiếu/thừa tiết</a></li>
</ul>

<?php if (in_array($f, ['missing','load'], true)): ?>
<form method="get" class="card card-body py-2 mb-3">
<input type="hidden" name="f" value="<?= e($f) ?>">
<div class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label small mb-0">Tìm</label>
<input type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="Tên / môn / lớp..."></div>
<?php if ($f === 'missing'): ?>
<div class="col-md-3"><label class="form-label small mb-0">Môn</label>
<select name="subject" class="form-select form-select-sm">
<option value="">Tất cả môn</option>
<?php foreach ($sub_names as $s): ?><option value="<?= e($s) ?>" <?= $f_subject===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-2"><label class="form-label small mb-0">Lớp</label>
<select name="class" class="form-select form-select-sm">
<option value="">Tất cả lớp</option>
<?php foreach ($classes as $c): ?><option value="<?= e($c) ?>" <?= $f_class===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?>
</select></div>
<?php else: ?>
<div class="col-md-2"><label class="form-label small mb-0">Cấp</label>
<select name="level" class="form-select form-select-sm">
<option value="">Tất cả</option>
<option value="THCS" <?= $f_level==='THCS'?'selected':'' ?>>THCS</option>
<option value="THPT" <?= $f_level==='THPT'?'selected':'' ?>>THPT</option>
</select></div>
<div class="col-md-2"><label class="form-label small mb-0">Nhóm</label>
<select name="flag" class="form-select form-select-sm">
<option value="">Tất cả</option>
<option value="khxh" <?= $f_flag==='khxh'?'selected':'' ?>>Tổ KHXH</option>
<option value="khtn" <?= $f_flag==='khtn'?'selected':'' ?>>Tổ KHTN</option>
<option value="tap_su" <?= $f_flag==='tap_su'?'selected':'' ?>>Tập sự</option>
</select></div>
<?php endif; ?>
<div class="col-auto"><button class="btn btn-primary btn-sm">Lọc</button>
<a href="?f=<?= e($f) ?>" class="btn btn-outline-secondary btn-sm">Xóa lọc</a></div>
</div>
</form>
<?php endif; ?>

<?php if ($f === 'all' || $f === 'conflict'): ?>
<div class="card mb-3">
<div class="card-header bg-danger"><i class="bi bi-exclamation-triangle"></i> Trùng môn + lớp</div>
<div class="card-body p-0">
<?php if ($conflicts): ?>
<table class="table table-sm mb-0"><thead><tr><th>Môn</th><th>Lớp</th><th>Đã giao cho</th><th></th></tr></thead><tbody>
<?php foreach ($conflicts as $c): ?>
<tr class="table-warning">
<td><?= e($c['subject']) ?></td><td><?= e($c['class']) ?></td>
<td><?= e(implode(', ', $c['teachers'])) ?></td>
<td><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>doicheo.php">Đổi chéo</a></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?><div class="p-3 text-success"><i class="bi bi-check-circle"></i> Không có trùng môn+lớp.</div><?php endif; ?>
</div></div>

<div class="card mb-3">
<div class="card-header bg-danger"><i class="bi bi-exclamation-triangle"></i> Trùng kiêm nhiệm + lớp</div>
<div class="card-body p-0">
<?php if ($role_conflicts): ?>
<table class="table table-sm mb-0"><thead><tr><th>Chức vụ</th><th>Lớp</th><th>Đã giao cho</th></tr></thead><tbody>
<?php foreach ($role_conflicts as $c): ?>
<tr class="table-warning"><td><?= e($c['role']) ?></td><td><?= e($c['class']) ?></td><td><?= e(implode(', ', $c['teachers'])) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?><div class="p-3 text-success"><i class="bi bi-check-circle"></i> Không có trùng kiêm nhiệm+lớp.</div><?php endif; ?>
</div></div>
<?php endif; ?>

<?php if ($f === 'all' || $f === 'missing'): ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between">
<span><i class="bi bi-journal-x"></i> Môn + lớp chưa phân công (<?= count($missing_view) ?>)</span>
<a href="<?= BASE_URL ?>them.php" class="btn btn-sm btn-outline-light">Thêm ngay</a>
</div>
<div class="card-body p-0" style="max-height:380px;overflow:auto">
<?php if ($missing_view): ?>
<table class="table table-sm table-striped mb-0"><thead><tr><th>Môn</th><th>Lớp</th><th>Tiết chuẩn</th></tr></thead><tbody>
<?php foreach ($missing_view as $m): ?>
<tr><td><?= e($m['subject']) ?></td><td><?= e($m['class']) ?></td><td><?= e($m['periods']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?><div class="p-3 text-success"><i class="bi bi-check-circle"></i> Không còn thiếu (theo bộ lọc).</div><?php endif; ?>
</div></div>
<?php endif; ?>

<?php if ($f === 'all' || $f === 'gvcn'): ?>
<div class="card mb-3">
<div class="card-header">Lớp chưa có GVCN</div>
<div class="card-body">
<?php if ($gvcn_missing): ?>
<?php foreach ($gvcn_missing as $c): ?><span class="badge bg-warning text-dark me-1 mb-1 fs-6"><?= e($c) ?></span><?php endforeach; ?>
<a href="<?= BASE_URL ?>them.php#kiemnhiem" class="btn btn-sm btn-outline-primary ms-2">Gán GVCN</a>
<?php else: ?><span class="text-success"><i class="bi bi-check-circle"></i> Mọi lớp đã có GVCN (hoặc chưa cấu hình chức vụ GVCN).</span><?php endif; ?>
</div></div>
<?php endif; ?>

<?php if ($f === 'all' || $f === 'load'): ?>
<div class="row">
<div class="col-md-6 mb-3">
<div class="card h-100">
<div class="card-header bg-warning text-dark">Thiếu tiết — <?= count($under_v) ?></div>
<div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>GV</th><th>Cấp</th><th>Tổng</th><th>ĐM</th><th>Thiếu</th></tr></thead><tbody>
<?php foreach ($under_v as $u): ?>
<tr><td><?= e($u['teacher']) ?></td><td><?= e($u['level']) ?></td><td><?= number_format($u['total'],2) ?></td><td><?= number_format($u['quota'],0) ?></td><td class="diff-under"><?= number_format($u['diff'],2) ?></td></tr>
<?php endforeach; ?>
<?php if (!$under_v): ?><tr><td colspan="5" class="text-muted text-center">Không có</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="col-md-6 mb-3">
<div class="card h-100">
<div class="card-header bg-danger">Thừa tiết — <?= count($over_v) ?></div>
<div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>GV</th><th>Cấp</th><th>Tổng</th><th>ĐM</th><th>Thừa</th></tr></thead><tbody>
<?php foreach ($over_v as $u): ?>
<tr><td><?= e($u['teacher']) ?></td><td><?= e($u['level']) ?></td><td><?= number_format($u['total'],2) ?></td><td><?= number_format($u['quota'],0) ?></td><td class="diff-over">+<?= number_format($u['diff'],2) ?></td></tr>
<?php endforeach; ?>
<?php if (!$over_v): ?><tr><td colspan="5" class="text-muted text-center">Không có</td></tr><?php endif; ?>
</tbody></table></div></div></div>
</div>
<p class="text-muted small">Định mức: THCS = <?= number_format(get_quota_thcs(),0) ?>t · THPT = <?= number_format(get_quota_thpt(),0) ?>t. Sửa tại <a href="<?= BASE_URL ?>giaovien.php">Quản lý Giáo viên</a>.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
