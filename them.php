<?php
$page_title = 'Bàn làm việc phân công';
require_once 'includes/functions.php';
require_login();

function redirect_ws($hash = '') {
    header('Location: ' . BASE_URL . 'them.php' . ($hash ? '#' . $hash : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete_day') {
        $id = $_POST['id'] ?? '';
        save_assignments(array_values(array_filter(get_assignments(), fn($a) => $a['id'] !== $id)));
        flash('Đã xóa phân công dạy.', 'success');
        redirect_ws('bang');
    }
    if ($action === 'delete_role') {
        $id = $_POST['id'] ?? '';
        save_role_assignments(array_values(array_filter(get_role_assignments(), fn($a) => $a['id'] !== $id)));
        flash('Đã xóa kiêm nhiệm.', 'success');
        redirect_ws('bang');
    }

    if ($action === 'add_one') {
        $assignments = get_assignments();
        $teacher = trim($_POST['teacher'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $class_name = trim($_POST['class_name'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $periods_manual = trim($_POST['periods_manual'] ?? '');
        $force = !empty($_POST['force']);
        if (!$teacher || !$subject || !$class_name) {
            flash('Vui lòng chọn đầy đủ Giáo viên, Môn học và Lớp.', 'danger');
        } else {
            $dup = false; $conflict = null;
            foreach ($assignments as $a) {
                if ($a['teacher'] === $teacher && $a['subject'] === $subject && $a['class'] === $class_name) { $dup = true; break; }
                if ($a['subject'] === $subject && $a['class'] === $class_name && $a['teacher'] !== $teacher) $conflict = $a['teacher'];
            }
            if ($dup) flash("Đã tồn tại: $teacher – $subject – $class_name", 'warning');
            elseif ($conflict && !$force) flash("CẢNH BÁO: Môn $subject lớp $class_name đã giao cho $conflict. Tick \"Vẫn thêm\" nếu cố ý.", 'warning');
            else {
                $periods = get_periods($subject, $class_name);
                if ($periods === null) $periods = is_numeric($periods_manual) ? round(floatval($periods_manual), 2) : 0;
                $assignments[] = ['id'=>date('YmdHis').substr(microtime(),2,6),'teacher'=>$teacher,'subject'=>$subject,'class'=>$class_name,'periods'=>$periods,'note'=>$note,'created_at'=>date('c')];
                save_assignments($assignments);
                flash("Đã thêm: $teacher dạy $subject lớp $class_name ($periods tiết)" . ($conflict ? " — trùng với $conflict" : ''), $conflict ? 'warning' : 'success');
            }
        }
        redirect_ws('them');
    }

    if ($action === 'add_multi') {
        $assignments = get_assignments();
        $teacher = trim($_POST['teacher'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $class_list = $_POST['classes'] ?? [];
        $force = !empty($_POST['force']);
        if (!$teacher || !$subject || empty($class_list)) flash('Vui lòng chọn đầy đủ.', 'danger');
        else {
            $added = 0; $warns = [];
            foreach ($class_list as $cls) {
                $exists = false; $conflict = null;
                foreach ($assignments as $a) {
                    if ($a['teacher'] === $teacher && $a['subject'] === $subject && $a['class'] === $cls) { $exists = true; break; }
                    if ($a['subject'] === $subject && $a['class'] === $cls && $a['teacher'] !== $teacher) $conflict = $a['teacher'];
                }
                if ($exists) continue;
                if ($conflict && !$force) { $warns[] = "$subject-$cls → $conflict"; continue; }
                $assignments[] = ['id'=>date('YmdHis').$cls.substr(microtime(),2,4),'teacher'=>$teacher,'subject'=>$subject,'class'=>$cls,'periods'=>get_periods($subject,$cls)??0,'note'=>'','created_at'=>date('c')];
                $added++;
            }
            save_assignments($assignments);
            flash("Đã thêm $added phân công." . ($warns ? ' Bỏ qua: '.implode('; ',$warns) : ''), $warns ? 'warning' : 'success');
        }
        redirect_ws('them');
    }

    if ($action === 'add_role') {
        $items = get_role_assignments();
        $roles = get_roles();
        $teacher = trim($_POST['teacher'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $class_name = trim($_POST['class_name'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $periods_manual = trim($_POST['periods_manual'] ?? '');
        $force = !empty($_POST['force']);
        $roleInfo = null;
        foreach ($roles as $r) if ($r['name'] === $role) { $roleInfo = $r; break; }
        $need_class = $roleInfo && !empty($roleInfo['need_class']);
        $periods = isset($roleInfo['periods']) ? floatval($roleInfo['periods']) : 0;
        if ($periods_manual !== '' && is_numeric($periods_manual)) $periods = round(floatval($periods_manual), 2);
        else $periods = round($periods, 2);
        if (!$teacher || !$role) flash('Vui lòng chọn Giáo viên và Chức vụ.', 'danger');
        elseif ($need_class && !$class_name) flash('Chức vụ này cần chọn Lớp.', 'danger');
        else {
            $exists = false; $conflict = null;
            foreach ($items as $a) {
                if ($a['teacher'] === $teacher && $a['role'] === $role && ($a['class'] ?? '') === $class_name) { $exists = true; break; }
                if ($need_class && ($a['role'] === $role) && ($a['class'] ?? '') === $class_name && $a['teacher'] !== $teacher) $conflict = $a['teacher'];
            }
            if ($exists) flash('Kiêm nhiệm này đã tồn tại.', 'warning');
            elseif ($conflict && !$force) flash("CẢNH BÁO: $role lớp $class_name đã giao cho $conflict.", 'warning');
            else {
                $items[] = ['id'=>date('YmdHis').substr(microtime(),2,4),'teacher'=>$teacher,'role'=>$role,'class'=>$class_name,'periods'=>$periods,'note'=>$note,'created_at'=>date('c')];
                save_role_assignments($items);
                flash("Đã thêm kiêm nhiệm: $teacher – $role" . ($class_name ? " ($class_name)" : '') . " ($periods tiết)", 'success');
            }
        }
        redirect_ws('them');
    }

    // ===== ĐỔI CHÉO =====
    if ($action === 'swap_all') {
        $t1 = trim($_POST['teacher1'] ?? ''); $t2 = trim($_POST['teacher2'] ?? '');
        if (!$t1 || !$t2 || $t1 === $t2) flash('Chọn 2 giáo viên khác nhau.', 'danger');
        else {
            $assignments = get_assignments(); $roles = get_role_assignments(); $nA = 0; $nR = 0;
            foreach ($assignments as &$a) {
                if ($a['teacher'] === $t1) { $a['teacher'] = $t2; $nA++; }
                elseif ($a['teacher'] === $t2) { $a['teacher'] = $t1; $nA++; }
            } unset($a);
            foreach ($roles as &$a) {
                if ($a['teacher'] === $t1) { $a['teacher'] = $t2; $nR++; }
                elseif ($a['teacher'] === $t2) { $a['teacher'] = $t1; $nR++; }
            } unset($a);
            save_assignments($assignments); save_role_assignments($roles);
            flash("Đã đổi chéo $nA dạy + $nR KN: $t1 ↔ $t2", 'success');
        }
        redirect_ws('doicheo');
    }

    if ($action === 'transfer') {
        $from = trim($_POST['from'] ?? ''); $to = trim($_POST['to'] ?? ''); $ids = $_POST['ids'] ?? [];
        if (!$from || !$to || $from === $to || empty($ids)) flash('Chọn GV nguồn, đích và ít nhất 1 mục.', 'warning');
        else {
            $assignments = get_assignments(); $n = 0;
            foreach ($assignments as &$a) {
                if ($a['teacher'] === $from && in_array($a['id'], $ids)) { $a['teacher'] = $to; $n++; }
            } unset($a);
            save_assignments($assignments);
            flash("Đã chuyển $n phân công: $from → $to", 'success');
        }
        redirect_ws('doicheo');
    }

    if ($action === 'swap_one') {
        $id1 = $_POST['id1'] ?? ''; $id2 = $_POST['id2'] ?? '';
        $list = get_assignments(); $i1 = $i2 = null;
        foreach ($list as $i => $a) { if ($a['id'] === $id1) $i1 = $i; if ($a['id'] === $id2) $i2 = $i; }
        if ($i1 === null || $i2 === null) flash('Không tìm thấy phân công.', 'danger');
        else {
            $tmp = $list[$i1]['teacher']; $list[$i1]['teacher'] = $list[$i2]['teacher']; $list[$i2]['teacher'] = $tmp;
            save_assignments($list);
            flash('Đã đổi chéo 2 phân công dạy môn.', 'success');
        }
        redirect_ws('doicheo');
    }
}

require_once 'includes/header.php';

$teachers = get_teachers_sorted();
$subjects = array_keys(get_subjects()); sort($subjects);
$all_subjects = get_subjects();
$classes = get_classes();
$roles = get_roles();
$assignments = get_assignments();
$role_items = get_role_assignments();
$loads = get_teacher_loads();

$day_by_t = [];
foreach ($assignments as $a) $day_by_t[$a['teacher']][] = $a;
$role_by_t = [];
foreach ($role_items as $a) $role_by_t[$a['teacher']][] = $a;
$board_names = sort_teachers_by_ten(array_unique(array_merge(array_keys($day_by_t), array_keys($role_by_t), $teachers)));

// --- Rà soát nhanh ---
$slot = [];
foreach ($assignments as $a) $slot[$a['subject'].'|'.$a['class']][] = $a['teacher'];
$conflicts = [];
foreach ($slot as $k => $list) {
    $u = array_values(array_unique($list));
    if (count($u) > 1) { [$s,$c] = explode('|',$k,2); $conflicts[] = ['subject'=>$s,'class'=>$c,'teachers'=>$u]; }
}
$role_slot = [];
foreach ($role_items as $a) {
    if (empty($a['class'])) continue;
    $role_slot[$a['role'].'|'.$a['class']][] = $a['teacher'];
}
$role_conflicts = [];
foreach ($role_slot as $k => $list) {
    $u = array_values(array_unique($list));
    if (count($u) > 1) { [$r,$c] = explode('|',$k,2); $role_conflicts[] = ['role'=>$r,'class'=>$c,'teachers'=>$u]; }
}
$missing = [];
foreach ($classes as $cls) {
    $grade = get_grade($cls);
    foreach ($all_subjects as $sub => $grades) {
        if (!isset($grades[$grade]) || floatval($grades[$grade]) <= 0) continue;
        $found = false;
        foreach ($assignments as $a) if ($a['subject']===$sub && $a['class']===$cls) { $found = true; break; }
        if (!$found) $missing[] = ['subject'=>$sub,'class'=>$cls,'periods'=>$grades[$grade]];
    }
}
$under = []; $over = [];
foreach ($teachers as $t) {
    $total = $loads[$t]['total'] ?? 0;
    $quota = get_quota($t);
    $diff = $total - $quota;
    $item = ['teacher'=>$t,'level'=>get_teacher_level($t),'total'=>$total,'quota'=>$quota,'diff'=>$diff];
    if ($diff < -0.05) $under[] = $item;
    elseif ($diff > 0.05) $over[] = $item;
}
$n_conflict = count($conflicts) + count($role_conflicts);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
<h3 class="mb-0"><i class="bi bi-clipboard-check"></i> Bàn làm việc phân công</h3>
<span class="text-muted small">Thêm · Theo dõi · Đổi chéo · Rà soát trên cùng màn hình</span>
</div>

<!-- TÓM TẮT CẢNH BÁO -->
<div class="row g-2 mb-3">
<a href="#rasoat" class="col text-decoration-none"><div class="card stat-card py-2 <?= $n_conflict?'border border-danger':'' ?>"><div class="number fs-5 <?= $n_conflict?'text-danger':'' ?>"><?= $n_conflict ?></div><div class="label">Trùng</div></div></a>
<a href="#rasoat" class="col text-decoration-none"><div class="card stat-card py-2"><div class="number fs-5 text-warning"><?= count($missing) ?></div><div class="label">Thiếu môn</div></div></a>
<a href="#rasoat" class="col text-decoration-none"><div class="card stat-card py-2"><div class="number fs-5 text-warning"><?= count($under) ?></div><div class="label">Thiếu tiết</div></div></a>
<a href="#rasoat" class="col text-decoration-none"><div class="card stat-card py-2"><div class="number fs-5 text-danger"><?= count($over) ?></div><div class="label">Thừa tiết</div></div></a>
<a href="#bang" class="col text-decoration-none"><div class="card stat-card py-2"><div class="number fs-5"><?= count($assignments) ?></div><div class="label">PC dạy</div></div></a>
<a href="#bang" class="col text-decoration-none"><div class="card stat-card py-2"><div class="number fs-5"><?= count($role_items) ?></div><div class="label">Kiêm nhiệm</div></div></a>
</div>

<?php if ($n_conflict > 0): ?>
<div class="danger-box"><i class="bi bi-exclamation-octagon-fill"></i> <strong>Cảnh báo:</strong> Có <?= $n_conflict ?> trường hợp trùng phân công. <a href="#rasoat" class="fw-semibold">Xem chi tiết ↓</a></div>
<?php endif; ?>

<!-- TAB ĐIỀU HƯỚNG NỘI BỘ -->
<ul class="nav nav-pills flex-wrap gap-1 mb-3" id="wsTabs">
<li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#them"><i class="bi bi-plus-circle"></i> Thêm</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#bang"><i class="bi bi-table"></i> Bảng GV</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#doicheo"><i class="bi bi-arrow-left-right"></i> Đổi chéo</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#rasoat"><i class="bi bi-search"></i> Rà soát</a></li>
</ul>

<div class="tab-content">

<!-- ===== THÊM ===== -->
<div class="tab-pane fade show active" id="them">
<ul class="nav nav-tabs mb-3">
<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#daymon">Dạy môn</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#kiemnhiem">Kiêm nhiệm</a></li>
</ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="daymon">
<div class="row">
<div class="col-lg-6 mb-3">
<div class="card"><div class="card-header"><i class="bi bi-plus-circle"></i> Thêm 1 phân công dạy</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="add_one">
<div class="mb-2"><label class="form-label fw-semibold small">Giáo viên *</label>
<select name="teacher" class="form-select form-select-sm" required><option value="">-- Chọn --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label fw-semibold small">Môn *</label>
<select name="subject" id="subject" class="form-select form-select-sm" required><option value="">-- Chọn --</option>
<?php foreach ($subjects as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label fw-semibold small">Lớp *</label>
<select name="class_name" id="class_name" class="form-select form-select-sm" required><option value="">-- Chọn --</option>
<?php foreach ($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><div id="periods-display" class="alert alert-success py-1 px-2 d-none small">Số tiết: <strong id="periods-value">0</strong></div>
<div id="periods-manual-wrap" class="d-none"><input type="number" name="periods_manual" class="form-control form-control-sm" step="0.01" min="0" placeholder="Số tiết thủ công"></div></div>
<div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="force" id="force1" value="1">
<label class="form-check-label text-danger small" for="force1">Vẫn thêm nếu đã giao người khác</label></div>
<button class="btn btn-primary btn-sm w-100"><i class="bi bi-save"></i> Lưu</button>
</form></div></div></div>

<div class="col-lg-6 mb-3">
<div class="card"><div class="card-header bg-success"><i class="bi bi-lightning"></i> Thêm nhanh nhiều lớp</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="add_multi">
<div class="mb-2"><select name="teacher" class="form-select form-select-sm" required><option value="">-- Giáo viên --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><select name="subject" class="form-select form-select-sm" required><option value="">-- Môn --</option>
<?php foreach ($subjects as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?></select></div>
<div class="mb-2 row g-1">
<?php foreach ($classes as $c): ?>
<div class="col-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="classes[]" value="<?= e($c) ?>" id="c<?= e($c) ?>">
<label class="form-check-label small" for="c<?= e($c) ?>"><?= e($c) ?></label></div></div>
<?php endforeach; ?>
</div>
<div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="force" id="force2" value="1">
<label class="form-check-label text-danger small" for="force2">Vẫn thêm lớp đã giao người khác</label></div>
<button class="btn btn-success btn-sm w-100"><i class="bi bi-lightning-fill"></i> Thêm tất cả</button>
</form></div></div></div>
</div></div>

<div class="tab-pane fade" id="kiemnhiem">
<div class="col-lg-6">
<div class="card"><div class="card-header bg-info"><i class="bi bi-person-badge"></i> Thêm kiêm nhiệm</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="add_role">
<div class="mb-2"><select name="teacher" class="form-select form-select-sm" required><option value="">-- Giáo viên --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><select name="role" id="roleSelect" class="form-select form-select-sm" required><option value="">-- Chức vụ --</option>
<?php foreach ($roles as $r): ?>
<option value="<?= e($r['name']) ?>" data-need-class="<?= !empty($r['need_class'])?'1':'0' ?>" data-periods="<?= e($r['periods']??0) ?>"><?= e($r['name']) ?> (<?= e($r['periods']??0) ?>t)</option>
<?php endforeach; ?></select></div>
<div class="mb-2" id="roleClassWrap"><select name="class_name" class="form-select form-select-sm"><option value="">-- Lớp (nếu cần) --</option>
<?php foreach ($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><div id="rolePeriodsDisplay" class="alert alert-success py-1 px-2 d-none small">Chuẩn: <strong id="rolePeriodsValue">0</strong></div>
<input type="number" name="periods_manual" class="form-control form-control-sm" step="0.01" min="0" placeholder="Để trống = số tiết chuẩn"></div>
<div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="force" id="force3" value="1">
<label class="form-check-label text-danger small" for="force3">Vẫn thêm nếu đã giao người khác</label></div>
<button class="btn btn-info btn-sm w-100"><i class="bi bi-save"></i> Lưu kiêm nhiệm</button>
</form></div></div></div>
</div></div>
</div>

<!-- ===== BẢNG GV ===== -->
<div class="tab-pane fade" id="bang">
<div class="card">
<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
<span><i class="bi bi-table"></i> Bảng theo dõi (<?= count($board_names) ?> GV)</span>
<input type="search" id="boardFilter" class="form-control form-control-sm" style="max-width:220px" placeholder="Lọc tên / môn / lớp...">
</div>
<div class="card-body" id="boardBody" style="max-height:70vh;overflow:auto">
<?php foreach ($board_names as $t):
    $load = $loads[$t] ?? ['day'=>0,'role'=>0,'total'=>0,'quota'=>get_quota($t),'diff'=>-get_quota($t),'level'=>get_teacher_level($t)];
    $diffClass = abs($load['diff']) < 0.01 ? 'diff-ok' : ($load['diff'] > 0 ? 'diff-over' : 'diff-under');
    $search = mb_strtolower($t . ' ' . ($load['mon_day']??'') . ' ' . implode(' ', array_column($day_by_t[$t]??[], 'subject')) . ' ' . implode(' ', array_column($day_by_t[$t]??[], 'class')), 'UTF-8');
?>
<div class="board-row" data-search="<?= e($search) ?>">
<div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
<strong><?= e($t) ?></strong>
<span class="small">
<span class="badge bg-secondary"><?= e($load['level']??'THCS') ?></span>
Dạy <b><?= number_format($load['day'],2) ?></b> · KN <b><?= number_format($load['role'],2) ?></b> · Tổng <b><?= number_format($load['total'],2) ?></b>/<?= number_format($load['quota'],0) ?>
<span class="<?= $diffClass ?>"><?= $load['diff']>0?'+':'' ?><?= number_format($load['diff'],2) ?></span>
</span></div>
<div class="mb-1"><span class="text-muted small me-1">Dạy:</span>
<?php if (!empty($day_by_t[$t])): foreach ($day_by_t[$t] as $a): ?>
<span class="chip"><?= e($a['subject']) ?> <?= e($a['class']) ?> (<?= e($a['periods']) ?>t)
<form method="post" class="d-inline" onsubmit="return confirm('Xóa phân công này?')">
<input type="hidden" name="action" value="delete_day"><input type="hidden" name="id" value="<?= e($a['id']) ?>">
<button type="submit" class="chip-x" title="Xóa">×</button></form></span>
<?php endforeach; else: ?><span class="text-muted small">—</span><?php endif; ?></div>
<div><span class="text-muted small me-1">KN:</span>
<?php if (!empty($role_by_t[$t])): foreach ($role_by_t[$t] as $a): ?>
<span class="chip chip-role"><?= e($a['role']) ?><?= !empty($a['class'])?' '.e($a['class']):'' ?> (<?= e($a['periods']??0) ?>t)
<form method="post" class="d-inline" onsubmit="return confirm('Xóa kiêm nhiệm này?')">
<input type="hidden" name="action" value="delete_role"><input type="hidden" name="id" value="<?= e($a['id']) ?>">
<button type="submit" class="chip-x" title="Xóa">×</button></form></span>
<?php endforeach; else: ?><span class="text-muted small">—</span><?php endif; ?></div>
</div>
<?php endforeach; ?>
</div></div></div>

<!-- ===== ĐỔI CHÉO ===== -->
<div class="tab-pane fade" id="doicheo">
<div class="warn-box"><i class="bi bi-exclamation-triangle-fill"></i> <strong>Cảnh báo:</strong> Đổi chéo / chuyển phân công <strong>không hoàn tác</strong> tự động. Nên tạo phiên bản ở Kết quả trước khi đổi hàng loạt.</div>
<div class="row g-3">
<div class="col-lg-6">
<div class="card h-100"><div class="card-header bg-warning"><i class="bi bi-people"></i> Đổi toàn bộ 2 GV</div>
<div class="card-body">
<form method="post" id="formSwapAll"><input type="hidden" name="action" value="swap_all">
<select name="teacher1" class="form-select form-select-sm mb-2" required><option value="">-- GV A --</option>
<?php foreach ($teachers as $t): $ld=$loads[$t]??null; ?><option value="<?= e($t) ?>"><?= e($t) ?> (<?= $ld?number_format($ld['total'],2):'0' ?>t)</option><?php endforeach; ?></select>
<div class="text-center text-muted small my-1"><i class="bi bi-arrow-down-up"></i></div>
<select name="teacher2" class="form-select form-select-sm mb-3" required><option value="">-- GV B --</option>
<?php foreach ($teachers as $t): $ld=$loads[$t]??null; ?><option value="<?= e($t) ?>"><?= e($t) ?> (<?= $ld?number_format($ld['total'],2):'0' ?>t)</option><?php endforeach; ?></select>
<button type="button" class="btn btn-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalSwapAll">Đổi chéo toàn bộ</button>
</form></div></div></div>

<div class="col-lg-6">
<div class="card h-100"><div class="card-header bg-success"><i class="bi bi-box-arrow-right"></i> Chuyển một phần dạy môn</div>
<div class="card-body">
<form method="post" id="formTransfer"><input type="hidden" name="action" value="transfer">
<div class="row g-2 mb-2">
<div class="col-6"><select name="from" id="fromT" class="form-select form-select-sm" required><option value="">Từ GV</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select></div>
<div class="col-6"><select name="to" class="form-select form-select-sm" required><option value="">Sang GV</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select></div>
</div>
<div id="transferList" class="border rounded p-2 mb-2 small" style="max-height:160px;overflow:auto;background:#fafbfc">Chọn GV nguồn…</div>
<button type="button" class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalTransfer">Chuyển đã chọn</button>
</form></div></div></div>

<div class="col-12">
<div class="card"><div class="card-header"><i class="bi bi-shuffle"></i> Đổi chéo 2 mục dạy cụ thể</div>
<div class="card-body">
<form method="post" id="formSwapOne" class="row g-2 align-items-end"><input type="hidden" name="action" value="swap_one">
<div class="col-md-5"><select name="id1" class="form-select form-select-sm" required><option value="">-- Mục 1 --</option>
<?php foreach ($assignments as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['teacher']) ?> · <?= e($a['subject']) ?> · <?= e($a['class']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-5"><select name="id2" class="form-select form-select-sm" required><option value="">-- Mục 2 --</option>
<?php foreach ($assignments as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['teacher']) ?> · <?= e($a['subject']) ?> · <?= e($a['class']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalSwapOne">Đổi</button></div>
</form></div></div></div>
</div></div>

<!-- ===== RÀ SOÁT ===== -->
<div class="tab-pane fade" id="rasoat">
<div class="row g-3">
<div class="col-lg-6">
<div class="card mb-3"><div class="card-header bg-danger"><i class="bi bi-exclamation-triangle"></i> Trùng môn + lớp (<?= count($conflicts) ?>)</div>
<div class="card-body p-0" style="max-height:220px;overflow:auto">
<?php if ($conflicts): ?><table class="table table-sm mb-0"><thead><tr><th>Môn</th><th>Lớp</th><th>GV</th></tr></thead><tbody>
<?php foreach ($conflicts as $c): ?><tr class="table-warning"><td><?= e($c['subject']) ?></td><td><?= e($c['class']) ?></td><td><?= e(implode(', ',$c['teachers'])) ?></td></tr><?php endforeach; ?>
</tbody></table><?php else: ?><div class="p-3 text-success small"><i class="bi bi-check-circle"></i> Không trùng</div><?php endif; ?>
</div></div>
<div class="card mb-3"><div class="card-header bg-danger">Trùng kiêm nhiệm + lớp (<?= count($role_conflicts) ?>)</div>
<div class="card-body p-0">
<?php if ($role_conflicts): ?><table class="table table-sm mb-0"><thead><tr><th>Chức vụ</th><th>Lớp</th><th>GV</th></tr></thead><tbody>
<?php foreach ($role_conflicts as $c): ?><tr class="table-warning"><td><?= e($c['role']) ?></td><td><?= e($c['class']) ?></td><td><?= e(implode(', ',$c['teachers'])) ?></td></tr><?php endforeach; ?>
</tbody></table><?php else: ?><div class="p-3 text-success small"><i class="bi bi-check-circle"></i> Không trùng</div><?php endif; ?>
</div></div>
</div>
<div class="col-lg-6">
<div class="card mb-3"><div class="card-header">Thiếu môn + lớp (<?= count($missing) ?>)
<input type="search" id="missFilter" class="form-control form-control-sm d-inline-block ms-2" style="max-width:160px;float:right" placeholder="Lọc..."></div>
<div class="card-body p-0" style="max-height:280px;overflow:auto">
<?php if ($missing): ?><table class="table table-sm table-striped mb-0" id="missTable"><thead><tr><th>Môn</th><th>Lớp</th><th>Tiết</th></tr></thead><tbody>
<?php foreach ($missing as $m): ?><tr data-s="<?= e(mb_strtolower($m['subject'].' '.$m['class'],'UTF-8')) ?>"><td><?= e($m['subject']) ?></td><td><?= e($m['class']) ?></td><td><?= e($m['periods']) ?></td></tr><?php endforeach; ?>
</tbody></table><?php else: ?><div class="p-3 text-success small">Không thiếu</div><?php endif; ?>
</div></div>
</div>
<div class="col-md-6">
<div class="card"><div class="card-header bg-warning text-dark">Thiếu tiết (<?= count($under) ?>)</div>
<div class="card-body p-0" style="max-height:240px;overflow:auto"><table class="table table-sm mb-0"><thead><tr><th>GV</th><th>Tổng</th><th>ĐM</th><th>Thiếu</th></tr></thead><tbody>
<?php foreach ($under as $u): ?><tr><td><?= e($u['teacher']) ?></td><td><?= number_format($u['total'],2) ?></td><td><?= number_format($u['quota'],0) ?></td><td class="diff-under"><?= number_format($u['diff'],2) ?></td></tr><?php endforeach; ?>
<?php if (!$under): ?><tr><td colspan="4" class="text-muted text-center">Không có</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="col-md-6">
<div class="card"><div class="card-header bg-danger">Thừa tiết (<?= count($over) ?>)</div>
<div class="card-body p-0" style="max-height:240px;overflow:auto"><table class="table table-sm mb-0"><thead><tr><th>GV</th><th>Tổng</th><th>ĐM</th><th>Thừa</th></tr></thead><tbody>
<?php foreach ($over as $u): ?><tr><td><?= e($u['teacher']) ?></td><td><?= number_format($u['total'],2) ?></td><td><?= number_format($u['quota'],0) ?></td><td class="diff-over">+<?= number_format($u['diff'],2) ?></td></tr><?php endforeach; ?>
<?php if (!$over): ?><tr><td colspan="4" class="text-muted text-center">Không có</td></tr><?php endif; ?>
</tbody></table></div></div></div>
</div></div>

</div><!-- tab-content -->

<!-- MODALS -->
<div class="modal fade" id="modalSwapAll" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header bg-warning"><h5 class="modal-title">Xác nhận đổi chéo toàn bộ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Đổi <strong>toàn bộ</strong> dạy môn + kiêm nhiệm giữa 2 GV.</p><p class="text-danger mb-0"><strong>Không thể hoàn tác.</strong></p></div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
<button class="btn btn-warning" onclick="document.getElementById('formSwapAll').submit()">Đồng ý</button></div>
</div></div></div>

<div class="modal fade" id="modalTransfer" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header bg-success text-white"><h5 class="modal-title">Xác nhận chuyển</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Chuyển các mục đã chọn sang GV đích.</p><p class="text-danger mb-0"><strong>Không thể hoàn tác tự động.</strong></p></div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
<button class="btn btn-success" onclick="document.getElementById('formTransfer').submit()">Đồng ý</button></div>
</div></div></div>

<div class="modal fade" id="modalSwapOne" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Xác nhận đổi 2 mục</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">Hai giáo viên của 2 phân công sẽ đổi cho nhau?</div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
<button class="btn btn-primary" onclick="document.getElementById('formSwapOne').submit()">Đồng ý</button></div>
</div></div></div>

<script>
// Số tiết dạy
const subjectSelect = document.getElementById('subject');
const classSelect = document.getElementById('class_name');
const periodsDisplay = document.getElementById('periods-display');
const periodsValue = document.getElementById('periods-value');
const periodsManualWrap = document.getElementById('periods-manual-wrap');
function fetchPeriods() {
  const subject = subjectSelect?.value, cls = classSelect?.value;
  if (!subject || !cls) { periodsDisplay?.classList.add('d-none'); periodsManualWrap?.classList.add('d-none'); return; }
  fetch('<?= BASE_URL ?>api/periods.php?subject=' + encodeURIComponent(subject) + '&class=' + encodeURIComponent(cls))
    .then(r => r.json()).then(data => {
      if (data.periods !== null && data.periods !== undefined) {
        periodsValue.textContent = data.periods; periodsDisplay.classList.remove('d-none'); periodsManualWrap.classList.add('d-none');
      } else { periodsDisplay.classList.add('d-none'); periodsManualWrap.classList.remove('d-none'); }
    });
}
if (subjectSelect) { subjectSelect.addEventListener('change', fetchPeriods); classSelect.addEventListener('change', fetchPeriods); }
const roleSelect = document.getElementById('roleSelect');
function onRoleChange() {
  const opt = roleSelect?.options[roleSelect.selectedIndex];
  if (!opt || !opt.value) { document.getElementById('rolePeriodsDisplay')?.classList.add('d-none'); return; }
  document.getElementById('roleClassWrap').style.opacity = opt.dataset.needClass === '1' ? '1' : '0.55';
  document.getElementById('rolePeriodsValue').textContent = opt.dataset.periods || 0;
  document.getElementById('rolePeriodsDisplay').classList.remove('d-none');
}
if (roleSelect) roleSelect.addEventListener('change', onRoleChange);

// Lọc bảng GV
document.getElementById('boardFilter')?.addEventListener('input', function() {
  const q = this.value.toLowerCase().trim();
  document.querySelectorAll('#boardBody .board-row').forEach(row => {
    row.style.display = !q || (row.dataset.search || '').includes(q) ? '' : 'none';
  });
});
// Lọc thiếu môn
document.getElementById('missFilter')?.addEventListener('input', function() {
  const q = this.value.toLowerCase().trim();
  document.querySelectorAll('#missTable tbody tr').forEach(tr => {
    tr.style.display = !q || (tr.dataset.s || '').includes(q) ? '' : 'none';
  });
});

// Transfer list
const assignData = <?= json_encode(array_map(fn($a)=>['id'=>$a['id'],'teacher'=>$a['teacher'],'label'=>$a['subject'].' · '.$a['class'].' ('.$a['periods'].'t)'], $assignments), JSON_UNESCAPED_UNICODE) ?>;
document.getElementById('fromT')?.addEventListener('change', function() {
  const t = this.value;
  const box = document.getElementById('transferList');
  const items = assignData.filter(a => a.teacher === t);
  if (!items.length) { box.innerHTML = '<span class="text-muted">Chưa có phân công dạy.</span>'; return; }
  box.innerHTML = items.map(a => `<div class="form-check"><input class="form-check-input" type="checkbox" name="ids[]" value="${a.id}" id="i${a.id}" form="formTransfer"><label class="form-check-label" for="i${a.id}">${a.label}</label></div>`).join('');
});

// Hash → tab
function showHashTab() {
  const h = (location.hash || '#them').replace('#','');
  const map = {them:'#them', bang:'#bang', doicheo:'#doicheo', rasoat:'#rasoat', kiemnhiem:'#them'};
  const sel = map[h] || '#them';
  const tab = document.querySelector('#wsTabs a[href="'+sel+'"]');
  if (tab) new bootstrap.Tab(tab).show();
  if (h === 'kiemnhiem') {
    const t2 = document.querySelector('a[href="#kiemnhiem"]');
    if (t2) new bootstrap.Tab(t2).show();
  }
}
showHashTab();
window.addEventListener('hashchange', showHashTab);
document.querySelectorAll('#wsTabs a').forEach(a => a.addEventListener('shown.bs.tab', e => {
  history.replaceState(null,'', e.target.getAttribute('href'));
}));
</script>
<?php require_once 'includes/footer.php'; ?>
