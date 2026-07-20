<?php
$page_title = 'Rà soát phân công';
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

// 1) Trùng môn+lớp (nhiều GV)
$slot = []; // "subject|class" => [teachers]
foreach ($assignments as $a) {
    $k = $a['subject'] . '|' . $a['class'];
    $slot[$k][] = $a['teacher'];
}
$conflicts = [];
foreach ($slot as $k => $list) {
    $uniq = array_unique($list);
    if (count($uniq) > 1) {
        [$s, $c] = explode('|', $k, 2);
        $conflicts[] = ['subject' => $s, 'class' => $c, 'teachers' => $uniq];
    }
}

// 2) Trùng kiêm nhiệm cần lớp (VD 2 GVCN cùng lớp)
$role_slot = [];
foreach ($role_items as $a) {
    if (empty($a['class'])) continue;
    $k = $a['role'] . '|' . $a['class'];
    $role_slot[$k][] = $a['teacher'];
}
$role_conflicts = [];
foreach ($role_slot as $k => $list) {
    $uniq = array_unique($list);
    if (count($uniq) > 1) {
        [$r, $c] = explode('|', $k, 2);
        $role_conflicts[] = ['role' => $r, 'class' => $c, 'teachers' => $uniq];
    }
}

// 3) Thiếu phân công môn theo lớp (môn có quy định tiết cho khối nhưng chưa giao)
$missing = [];
foreach ($classes as $cls) {
    $grade = get_grade($cls);
    foreach ($subjects as $sub => $grades) {
        if (!isset($grades[$grade]) || floatval($grades[$grade]) <= 0) continue;
        $found = false;
        foreach ($assignments as $a) {
            if ($a['subject'] === $sub && $a['class'] === $cls) { $found = true; break; }
        }
        if (!$found) {
            $missing[] = ['subject' => $sub, 'class' => $cls, 'periods' => $grades[$grade]];
        }
    }
}

// 4) GVCN thiếu theo lớp (nếu có chức vụ GVCN need_class)
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

// 5) Thiếu / thừa tiết so định mức
$under = []; $over = []; $ok = [];
foreach ($teachers as $t) {
    $row = $loads[$t] ?? null;
    $total = $row['total'] ?? 0;
    $quota = get_quota($t);
    $diff = $total - $quota;
    $item = ['teacher' => $t, 'level' => get_teacher_level($t), 'total' => $total, 'quota' => $quota, 'diff' => $diff];
    if ($diff < -0.05) $under[] = $item;
    elseif ($diff > 0.05) $over[] = $item;
    else $ok[] = $item;
}
usort($under, fn($a,$b) => $a['diff'] <=> $b['diff']);
usort($over, fn($a,$b) => $b['diff'] <=> $a['diff']);

$filter = $_GET['f'] ?? 'all';
?>

<h3 class="mb-3"><i class="bi bi-search"></i> Rà soát phân công</h3>

<ul class="nav nav-tabs mb-3">
<li class="nav-item"><a class="nav-link <?= $filter==='all'?'active':'' ?>" href="?f=all">Tổng quan</a></li>
<li class="nav-item"><a class="nav-link <?= $filter==='conflict'?'active':'' ?>" href="?f=conflict">Trùng phân công (<?= count($conflicts)+count($role_conflicts) ?>)</a></li>
<li class="nav-item"><a class="nav-link <?= $filter==='missing'?'active':'' ?>" href="?f=missing">Thiếu môn/lớp (<?= count($missing) ?>)</a></li>
<li class="nav-item"><a class="nav-link <?= $filter==='gvcn'?'active':'' ?>" href="?f=gvcn">Thiếu GVCN (<?= count($gvcn_missing) ?>)</a></li>
<li class="nav-item"><a class="nav-link <?= $filter==='load'?'active':'' ?>" href="?f=load">Thiếu/thừa tiết</a></li>
</ul>

<?php if ($filter === 'all' || $filter === 'conflict'): ?>
<div class="card mb-3">
<div class="card-header bg-danger">Cảnh báo trùng môn + lớp</div>
<div class="card-body p-0">
<?php if ($conflicts): ?>
<table class="table table-sm mb-0"><thead><tr><th>Môn</th><th>Lớp</th><th>Đã giao cho</th></tr></thead><tbody>
<?php foreach ($conflicts as $c): ?>
<tr class="table-warning"><td><?= e($c['subject']) ?></td><td><?= e($c['class']) ?></td><td><?= e(implode(', ', $c['teachers'])) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?><div class="p-3 text-success">Không có trùng môn+lớp.</div><?php endif; ?>
</div></div>

<div class="card mb-3">
<div class="card-header bg-danger">Cảnh báo trùng kiêm nhiệm + lớp</div>
<div class="card-body p-0">
<?php if ($role_conflicts): ?>
<table class="table table-sm mb-0"><thead><tr><th>Chức vụ</th><th>Lớp</th><th>Đã giao cho</th></tr></thead><tbody>
<?php foreach ($role_conflicts as $c): ?>
<tr class="table-warning"><td><?= e($c['role']) ?></td><td><?= e($c['class']) ?></td><td><?= e(implode(', ', $c['teachers'])) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?><div class="p-3 text-success">Không có trùng kiêm nhiệm+lớp.</div><?php endif; ?>
</div></div>
<?php endif; ?>

<?php if ($filter === 'all' || $filter === 'missing'): ?>
<div class="card mb-3">
<div class="card-header">Môn + lớp chưa được phân công (<?= count($missing) ?>)</div>
<div class="card-body p-0" style="max-height:360px;overflow:auto">
<?php if ($missing): ?>
<table class="table table-sm table-striped mb-0"><thead><tr><th>Môn</th><th>Lớp</th><th>Tiết chuẩn</th></tr></thead><tbody>
<?php foreach ($missing as $m): ?>
<tr><td><?= e($m['subject']) ?></td><td><?= e($m['class']) ?></td><td><?= e($m['periods']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?><div class="p-3 text-success">Mọi môn có quy định tiết đều đã được giao lớp.</div><?php endif; ?>
</div></div>
<?php endif; ?>

<?php if ($filter === 'all' || $filter === 'gvcn'): ?>
<div class="card mb-3">
<div class="card-header">Lớp chưa có GVCN</div>
<div class="card-body">
<?php if ($gvcn_missing): ?>
<?php foreach ($gvcn_missing as $c): ?><span class="badge bg-warning text-dark me-1 mb-1"><?= e($c) ?></span><?php endforeach; ?>
<?php else: ?><span class="text-success">Mọi lớp đã có GVCN (hoặc chưa cấu hình chức vụ GVCN).</span><?php endif; ?>
</div></div>
<?php endif; ?>

<?php if ($filter === 'all' || $filter === 'load'): ?>
<div class="row">
<div class="col-md-6 mb-3">
<div class="card h-100">
<div class="card-header bg-warning text-dark">Thiếu tiết (dưới định mức) — <?= count($under) ?></div>
<div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>GV</th><th>Cấp</th><th>Tổng</th><th>ĐM</th><th>Thiếu</th></tr></thead><tbody>
<?php foreach ($under as $u): ?>
<tr><td><?= e($u['teacher']) ?></td><td><?= e($u['level']) ?></td><td><?= number_format($u['total'],1) ?></td><td><?= $u['quota'] ?></td><td class="diff-under"><?= number_format($u['diff'],1) ?></td></tr>
<?php endforeach; ?>
<?php if (!$under): ?><tr><td colspan="5" class="text-muted text-center">Không có</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="col-md-6 mb-3">
<div class="card h-100">
<div class="card-header bg-danger">Thừa tiết (trên định mức) — <?= count($over) ?></div>
<div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>GV</th><th>Cấp</th><th>Tổng</th><th>ĐM</th><th>Thừa</th></tr></thead><tbody>
<?php foreach ($over as $u): ?>
<tr><td><?= e($u['teacher']) ?></td><td><?= e($u['level']) ?></td><td><?= number_format($u['total'],1) ?></td><td><?= $u['quota'] ?></td><td class="diff-over">+<?= number_format($u['diff'],1) ?></td></tr>
<?php endforeach; ?>
<?php if (!$over): ?><tr><td colspan="5" class="text-muted text-center">Không có</td></tr><?php endif; ?>
</tbody></table></div></div></div>
</div>
<p class="text-muted small">Định mức: THCS = <?= QUOTA_THCS ?> tiết/tuần · THPT = <?= QUOTA_THPT ?> tiết/tuần. Gán cấp tại <a href="<?= BASE_URL ?>giaovien.php">Quản lý → Giáo viên</a>.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
