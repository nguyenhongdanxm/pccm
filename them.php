<?php
$page_title = 'Thêm phân công';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete_day') {
        $id = $_POST['id'] ?? '';
        $assignments = array_values(array_filter(get_assignments(), fn($a) => $a['id'] !== $id));
        save_assignments($assignments);
        flash('Đã xóa phân công dạy.', 'success');
        header('Location: ' . BASE_URL . 'them.php'); exit;
    }
    if ($action === 'delete_role') {
        $id = $_POST['id'] ?? '';
        $items = array_values(array_filter(get_role_assignments(), fn($a) => $a['id'] !== $id));
        save_role_assignments($items);
        flash('Đã xóa kiêm nhiệm.', 'success');
        header('Location: ' . BASE_URL . 'them.php'); exit;
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
            $dup_teacher = false;
            $conflict = null;
            foreach ($assignments as $a) {
                if ($a['teacher'] === $teacher && $a['subject'] === $subject && $a['class'] === $class_name) {
                    $dup_teacher = true; break;
                }
                if ($a['subject'] === $subject && $a['class'] === $class_name && $a['teacher'] !== $teacher) {
                    $conflict = $a['teacher'];
                }
            }
            if ($dup_teacher) {
                flash("Đã tồn tại: $teacher – $subject – $class_name", 'warning');
            } elseif ($conflict && !$force) {
                flash("CẢNH BÁO: Môn $subject lớp $class_name đã giao cho $conflict. Tick \"Vẫn thêm\" nếu cố ý.", 'warning');
            } else {
                $periods = get_periods($subject, $class_name);
                if ($periods === null) {
                    $periods = is_numeric($periods_manual) ? round(floatval($periods_manual), 2) : 0;
                }
                $assignments[] = [
                    'id' => date('YmdHis') . substr(microtime(), 2, 6),
                    'teacher' => $teacher,
                    'subject' => $subject,
                    'class' => $class_name,
                    'periods' => $periods,
                    'note' => $note,
                    'created_at' => date('c'),
                ];
                save_assignments($assignments);
                $msg = "Đã thêm: $teacher dạy $subject lớp $class_name ($periods tiết)";
                if ($conflict) $msg .= " — trùng với $conflict";
                flash($msg, $conflict ? 'warning' : 'success');
            }
        }
        header('Location: ' . BASE_URL . 'them.php'); exit;
    }

    if ($action === 'add_multi') {
        $assignments = get_assignments();
        $teacher = trim($_POST['teacher'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $class_list = $_POST['classes'] ?? [];
        $force = !empty($_POST['force']);
        if (!$teacher || !$subject || empty($class_list)) {
            flash('Vui lòng chọn đầy đủ.', 'danger');
        } else {
            $added = 0; $warns = [];
            foreach ($class_list as $cls) {
                $exists = false; $conflict = null;
                foreach ($assignments as $a) {
                    if ($a['teacher'] === $teacher && $a['subject'] === $subject && $a['class'] === $cls) { $exists = true; break; }
                    if ($a['subject'] === $subject && $a['class'] === $cls && $a['teacher'] !== $teacher) $conflict = $a['teacher'];
                }
                if ($exists) continue;
                if ($conflict && !$force) { $warns[] = "$subject-$cls → $conflict"; continue; }
                $p = get_periods($subject, $cls) ?? 0;
                $assignments[] = [
                    'id' => date('YmdHis') . $cls . substr(microtime(), 2, 4),
                    'teacher' => $teacher,
                    'subject' => $subject,
                    'class' => $cls,
                    'periods' => $p,
                    'note' => '',
                    'created_at' => date('c'),
                ];
                $added++;
            }
            save_assignments($assignments);
            $msg = "Đã thêm $added phân công.";
            if ($warns) $msg .= ' Bỏ qua (đã có GV khác): ' . implode('; ', $warns);
            flash($msg, $warns ? 'warning' : 'success');
        }
        header('Location: ' . BASE_URL . 'them.php'); exit;
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
        foreach ($roles as $r) { if ($r['name'] === $role) { $roleInfo = $r; break; } }
        $need_class = $roleInfo && !empty($roleInfo['need_class']);
        $periods = isset($roleInfo['periods']) ? floatval($roleInfo['periods']) : 0;
        if ($periods_manual !== '' && is_numeric($periods_manual)) {
            $periods = round(floatval($periods_manual), 2);
        } else {
            $periods = round($periods, 2);
        }

        if (!$teacher || !$role) {
            flash('Vui lòng chọn Giáo viên và Chức vụ.', 'danger');
        } elseif ($need_class && !$class_name) {
            flash('Chức vụ này cần chọn Lớp.', 'danger');
        } else {
            $exists = false; $conflict = null;
            foreach ($items as $a) {
                if ($a['teacher'] === $teacher && $a['role'] === $role && ($a['class'] ?? '') === $class_name) { $exists = true; break; }
                if ($need_class && ($a['role'] === $role) && ($a['class'] ?? '') === $class_name && $a['teacher'] !== $teacher) {
                    $conflict = $a['teacher'];
                }
            }
            if ($exists) {
                flash('Kiêm nhiệm này đã tồn tại.', 'warning');
            } elseif ($conflict && !$force) {
                flash("CẢNH BÁO: $role lớp $class_name đã giao cho $conflict. Tick \"Vẫn thêm\" nếu cố ý.", 'warning');
            } else {
                $items[] = [
                    'id' => date('YmdHis') . substr(microtime(), 2, 4),
                    'teacher' => $teacher,
                    'role' => $role,
                    'class' => $class_name,
                    'periods' => $periods,
                    'note' => $note,
                    'created_at' => date('c'),
                ];
                save_role_assignments($items);
                flash("Đã thêm kiêm nhiệm: $teacher – $role" . ($class_name ? " ($class_name)" : '') . " ($periods tiết)", 'success');
            }
        }
        header('Location: ' . BASE_URL . 'them.php#kiemnhiem'); exit;
    }
}

require_once 'includes/header.php';
$teachers = get_teachers_sorted();
$subjects = array_keys(get_subjects()); sort($subjects);
$classes = get_classes();
$roles = get_roles();
$assignments = get_assignments();
$role_items = get_role_assignments();
$loads = get_teacher_loads();

$day_by_t = [];
foreach ($assignments as $a) $day_by_t[$a['teacher']][] = $a;
$role_by_t = [];
foreach ($role_items as $a) $role_by_t[$a['teacher']][] = $a;
$board_names = sort_teachers_by_ten(array_unique(array_merge(array_keys($day_by_t), array_keys($role_by_t))));
?>

<ul class="nav nav-tabs mb-4">
<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#daymon">Dạy môn</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#kiemnhiem">Kiêm nhiệm</a></li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="daymon">
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle"></i> Thêm 1 phân công dạy</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="add_one">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giáo viên *</label>
                        <select name="teacher" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Môn học *</label>
                        <select name="subject" id="subject" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach ($subjects as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lớp *</label>
                        <select name="class_name" id="class_name" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach ($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiết</label>
                        <div id="periods-display" class="alert alert-success py-2 d-none">Số tiết tự động: <strong id="periods-value">0</strong></div>
                        <div id="periods-manual-wrap" class="d-none">
                            <input type="number" name="periods_manual" class="form-control" step="0.01" min="0" max="20" placeholder="Nhập số tiết (lẻ đến 0,01)">
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Ghi chú</label>
                        <input type="text" name="note" class="form-control" placeholder="Tùy chọn"></div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="force" id="force1" value="1">
                        <label class="form-check-label text-danger small" for="force1">Vẫn thêm nếu môn+lớp đã giao người khác</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Lưu</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-success"><i class="bi bi-lightning"></i> Thêm nhanh nhiều lớp</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="add_multi">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giáo viên *</label>
                        <select name="teacher" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Môn học *</label>
                        <select name="subject" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach ($subjects as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn nhiều lớp *</label>
                        <div class="row g-2">
                            <?php foreach ($classes as $c): ?>
                            <div class="col-4 col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="classes[]" value="<?= e($c) ?>" id="cls_<?= e($c) ?>">
                                    <label class="form-check-label" for="cls_<?= e($c) ?>"><?= e($c) ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="force" id="force2" value="1">
                        <label class="form-check-label text-danger small" for="force2">Vẫn thêm lớp đã giao người khác</label>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-lightning-fill"></i> Thêm tất cả</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<div class="tab-pane fade" id="kiemnhiem">
<div class="row">
<div class="col-lg-6 mb-4">
<div class="card">
<div class="card-header bg-info"><i class="bi bi-person-badge"></i> Thêm kiêm nhiệm</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="add_role">
<div class="mb-3">
<label class="form-label fw-semibold">Giáo viên *</label>
<select name="teacher" class="form-select" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-semibold">Chức vụ *</label>
<select name="role" id="roleSelect" class="form-select" required>
<option value="">-- Chọn --</option>
<?php foreach ($roles as $r): ?>
<option value="<?= e($r['name']) ?>" data-need-class="<?= !empty($r['need_class']) ? '1' : '0' ?>" data-periods="<?= e($r['periods'] ?? 0) ?>">
<?= e($r['name']) ?> (<?= e($r['periods'] ?? 0) ?> tiết)<?= !empty($r['note']) ? ' – ' . e($r['note']) : '' ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3" id="roleClassWrap">
<label class="form-label fw-semibold">Lớp</label>
<select name="class_name" class="form-select">
<option value="">-- Chọn lớp (nếu cần) --</option>
<?php foreach ($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-semibold">Số tiết</label>
<div id="rolePeriodsDisplay" class="alert alert-success py-2 d-none">Số tiết chuẩn: <strong id="rolePeriodsValue">0</strong></div>
<input type="number" name="periods_manual" class="form-control" step="0.01" min="0" max="20" placeholder="Để trống = dùng số tiết chuẩn (lẻ đến 0,01)">
</div>
<div class="mb-3"><label class="form-label">Ghi chú</label>
<input type="text" name="note" class="form-control" placeholder="Tùy chọn"></div>
<div class="form-check mb-3">
<input class="form-check-input" type="checkbox" name="force" id="force3" value="1">
<label class="form-check-label text-danger small" for="force3">Vẫn thêm nếu chức vụ+lớp đã giao người khác</label>
</div>
<button type="submit" class="btn btn-info w-100"><i class="bi bi-save"></i> Lưu kiêm nhiệm</button>
</form>
</div></div></div>
</div>
</div>
</div>

<div class="card mt-2">
<div class="card-header d-flex justify-content-between align-items-center">
<span><i class="bi bi-table"></i> Bảng theo dõi phân công (<?= count($board_names) ?> GV)</span>
<a href="<?= BASE_URL ?>rasoat.php" class="btn btn-sm btn-outline-light">Rà soát thiếu/thừa</a>
</div>
<div class="card-body">
<?php if (!$board_names): ?>
<p class="text-muted mb-0">Chưa có phân công. Thêm ở form phía trên.</p>
<?php else: ?>
<?php foreach ($board_names as $t):
    $load = $loads[$t] ?? ['day'=>0,'role'=>0,'total'=>0,'quota'=>get_quota($t),'diff'=>-get_quota($t),'level'=>get_teacher_level($t)];
    $diffClass = abs($load['diff']) < 0.01 ? 'diff-ok' : ($load['diff'] > 0 ? 'diff-over' : 'diff-under');
?>
<div class="board-row">
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
<strong><?= e($t) ?></strong>
<span class="small">
<span class="badge bg-secondary"><?= e($load['level'] ?? 'THCS') ?></span>
Dạy: <b><?= number_format($load['day'], 2) ?></b>
· KN: <b><?= number_format($load['role'], 2) ?></b>
· Tổng: <b><?= number_format($load['total'], 2) ?></b>
/ ĐM <?= $load['quota'] ?>
<span class="<?= $diffClass ?>"><?= $load['diff'] > 0 ? '+' : '' ?><?= number_format($load['diff'], 2) ?></span>
</span>
</div>
<div class="mb-1">
<span class="text-muted small me-1">Dạy:</span>
<?php if (!empty($day_by_t[$t])): foreach ($day_by_t[$t] as $a): ?>
<span class="chip">
<?= e($a['subject']) ?> <?= e($a['class']) ?> (<?= e($a['periods']) ?>t)
<form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
<input type="hidden" name="action" value="delete_day">
<input type="hidden" name="id" value="<?= e($a['id']) ?>"><button type="submit" class="chip-x" title="Xóa">×</button>
</form>
</span>
<?php endforeach; else: ?><span class="text-muted small">—</span><?php endif; ?>
</div>
<div>
<span class="text-muted small me-1">KN:</span>
<?php if (!empty($role_by_t[$t])): foreach ($role_by_t[$t] as $a): ?>
<span class="chip chip-role">
<?= e($a['role']) ?><?= !empty($a['class']) ? ' '.e($a['class']) : '' ?> (<?= e($a['periods']??0) ?>t)
<form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
<input type="hidden" name="action" value="delete_role">
<input type="hidden" name="id" value="<?= e($a['id']) ?>"><button type="submit" class="chip-x" title="Xóa">×</button>
</form>
</span>
<?php endforeach; else: ?><span class="text-muted small">—</span><?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div>

<script>
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
                periodsValue.textContent = data.periods;
                periodsDisplay.classList.remove('d-none');
                periodsManualWrap.classList.add('d-none');
            } else {
                periodsDisplay.classList.add('d-none');
                periodsManualWrap.classList.remove('d-none');
            }
        });
}
if (subjectSelect) { subjectSelect.addEventListener('change', fetchPeriods); classSelect.addEventListener('change', fetchPeriods); }
const roleSelect = document.getElementById('roleSelect');
const roleClassWrap = document.getElementById('roleClassWrap');
const rolePeriodsDisplay = document.getElementById('rolePeriodsDisplay');
const rolePeriodsValue = document.getElementById('rolePeriodsValue');
function onRoleChange() {
    const opt = roleSelect?.options[roleSelect.selectedIndex];
    if (!opt || !opt.value) { rolePeriodsDisplay?.classList.add('d-none'); return; }
    roleClassWrap.style.opacity = opt.dataset.needClass === '1' ? '1' : '0.55';
    rolePeriodsValue.textContent = opt.dataset.periods || 0;
    rolePeriodsDisplay.classList.remove('d-none');
}
if (roleSelect) roleSelect.addEventListener('change', onRoleChange);
if (location.hash === '#kiemnhiem') {
    const tab = document.querySelector('a[href="#kiemnhiem"]');
    if (tab) new bootstrap.Tab(tab).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
