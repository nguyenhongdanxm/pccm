<?php
$page_title = 'Thêm phân công';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ===== Phân công dạy môn =====
    if ($_POST['action'] === 'add_one') {
        $assignments = get_assignments();
        $teacher = trim($_POST['teacher'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $class_name = trim($_POST['class_name'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $periods_manual = trim($_POST['periods_manual'] ?? '');

        if (!$teacher || !$subject || !$class_name) {
            flash('Vui lòng chọn đầy đủ Giáo viên, Môn học và Lớp.', 'danger');
        } else {
            $exists = false;
            foreach ($assignments as $a) {
                if ($a['teacher'] === $teacher && $a['subject'] === $subject && $a['class'] === $class_name) {
                    $exists = true; break;
                }
            }
            if ($exists) {
                flash("Đã tồn tại: $teacher – $subject – $class_name", 'warning');
            } else {
                $periods = get_periods($subject, $class_name);
                if ($periods === null) {
                    $periods = is_numeric($periods_manual) ? floatval($periods_manual) : 0;
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
                save_json(ASSIGNMENTS_FILE, $assignments);
                flash("Đã thêm: $teacher dạy $subject lớp $class_name ($periods tiết)", 'success');
            }
        }
        header('Location: ' . BASE_URL . 'them.php'); exit;
    }

    if ($_POST['action'] === 'add_multi') {
        $assignments = get_assignments();
        $teacher = trim($_POST['teacher'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $classes = $_POST['classes'] ?? [];
        if (!$teacher || !$subject || empty($classes)) {
            flash('Vui lòng chọn đầy đủ.', 'danger');
        } else {
            $added = 0;
            foreach ($classes as $cls) {
                $exists = false;
                foreach ($assignments as $a) {
                    if ($a['teacher'] === $teacher && $a['subject'] === $subject && $a['class'] === $cls) {
                        $exists = true; break;
                    }
                }
                if (!$exists) {
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
            }
            save_json(ASSIGNMENTS_FILE, $assignments);
            flash("Đã thêm $added phân công mới.", 'success');
        }
        header('Location: ' . BASE_URL . 'danhsach.php'); exit;
    }

    // ===== Kiêm nhiệm =====
    if ($_POST['action'] === 'add_role') {
        $items = get_role_assignments();
        $roles = get_roles();
        $teacher = trim($_POST['teacher'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $class_name = trim($_POST['class_name'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $periods_manual = trim($_POST['periods_manual'] ?? '');

        $roleInfo = null;
        foreach ($roles as $r) {
            if ($r['name'] === $role) { $roleInfo = $r; break; }
        }
        $need_class = $roleInfo && !empty($roleInfo['need_class']);
        $periods = $roleInfo['periods'] ?? 0;
        if ($periods_manual !== '' && is_numeric($periods_manual)) {
            $periods = floatval($periods_manual);
        }

        if (!$teacher || !$role) {
            flash('Vui lòng chọn Giáo viên và Chức vụ.', 'danger');
        } elseif ($need_class && !$class_name) {
            flash('Chức vụ này cần chọn Lớp.', 'danger');
        } else {
            $exists = false;
            foreach ($items as $a) {
                if ($a['teacher'] === $teacher && $a['role'] === $role && ($a['class'] ?? '') === $class_name) {
                    $exists = true; break;
                }
            }
            if ($exists) {
                flash('Kiêm nhiệm này đã tồn tại.', 'warning');
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
                save_json(ROLE_ASSIGNMENTS_FILE, $items);
                $msg = "$teacher – $role" . ($class_name ? " ($class_name)" : '') . " ($periods tiết)";
                flash("Đã thêm kiêm nhiệm: $msg", 'success');
            }
        }
        header('Location: ' . BASE_URL . 'them.php#kiemnhiem'); exit;
    }
}

require_once 'includes/header.php';
$teachers = get_teachers(); sort($teachers);
$subjects = array_keys(get_subjects()); sort($subjects);
$classes = get_classes();
$roles = get_roles();
?>

<ul class="nav nav-tabs mb-4">
<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#daymon">Dạy môn</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#kiemnhiem">Kiêm nhiệm</a></li>
</ul>

<div class="tab-content">

<!-- ===== TAB DẠY MÔN ===== -->
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
                            <input type="number" name="periods_manual" class="form-control" step="0.1" min="0" max="10" placeholder="Nhập số tiết thủ công">
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Ghi chú</label>
                        <input type="text" name="note" class="form-control" placeholder="Tùy chọn"></div>
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
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-lightning-fill"></i> Thêm tất cả</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<!-- ===== TAB KIÊM NHIỆM ===== -->
<div class="tab-pane fade" id="kiemnhiem">
<div class="row">
<div class="col-lg-6 mb-4">
<div class="card">
<div class="card-header bg-info text-dark"><i class="bi bi-person-badge"></i> Thêm kiêm nhiệm</div>
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
<div class="form-text">Quản lý danh mục chức vụ tại <a href="<?= BASE_URL ?>kiemnhiem.php">Quản lý → Chức vụ kiêm nhiệm</a></div>
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
<input type="number" name="periods_manual" id="rolePeriodsManual" class="form-control" step="0.5" min="0" max="20" placeholder="Để trống = dùng số tiết chuẩn">
</div>
<div class="mb-3"><label class="form-label">Ghi chú</label>
<input type="text" name="note" class="form-control" placeholder="Tùy chọn"></div>
<button type="submit" class="btn btn-info w-100"><i class="bi bi-save"></i> Lưu kiêm nhiệm</button>
</form>
</div></div></div>

<div class="col-lg-6 mb-4">
<div class="card">
<div class="card-header">Đã phân công kiêm nhiệm gần đây</div>
<div class="card-body p-0">
<?php
$recent = array_slice(array_reverse(get_role_assignments()), 0, 15);
if ($recent):
?>
<table class="table table-sm table-hover mb-0">
<thead><tr><th>GV</th><th>Chức vụ</th><th>Lớp</th><th>Tiết</th></tr></thead>
<tbody>
<?php foreach ($recent as $a): ?>
<tr>
<td><?= e($a['teacher']) ?></td>
<td><span class="badge bg-info text-dark"><?= e($a['role']) ?></span></td>
<td><?= e($a['class'] ?? '') ?></td>
<td><?= e($a['periods'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?>
<div class="p-3 text-muted">Chưa có dữ liệu.</div>
<?php endif; ?>
</div></div></div>
</div>
</div>

</div><!-- tab-content -->

<script>
// Số tiết dạy môn
const subjectSelect = document.getElementById('subject');
const classSelect = document.getElementById('class_name');
const periodsDisplay = document.getElementById('periods-display');
const periodsValue = document.getElementById('periods-value');
const periodsManualWrap = document.getElementById('periods-manual-wrap');
function fetchPeriods() {
    const subject = subjectSelect.value, cls = classSelect.value;
    if (!subject || !cls) { periodsDisplay.classList.add('d-none'); periodsManualWrap.classList.add('d-none'); return; }
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

// Kiêm nhiệm
const roleSelect = document.getElementById('roleSelect');
const roleClassWrap = document.getElementById('roleClassWrap');
const rolePeriodsDisplay = document.getElementById('rolePeriodsDisplay');
const rolePeriodsValue = document.getElementById('rolePeriodsValue');
function onRoleChange() {
    const opt = roleSelect.options[roleSelect.selectedIndex];
    if (!opt || !opt.value) {
        rolePeriodsDisplay.classList.add('d-none');
        return;
    }
    const need = opt.dataset.needClass === '1';
    const p = opt.dataset.periods || 0;
    roleClassWrap.style.opacity = need ? '1' : '0.55';
    rolePeriodsValue.textContent = p;
    rolePeriodsDisplay.classList.remove('d-none');
}
if (roleSelect) roleSelect.addEventListener('change', onRoleChange);

// Mở tab kiemnhiem nếu có hash
if (location.hash === '#kiemnhiem') {
    const tab = document.querySelector('a[href="#kiemnhiem"]');
    if (tab) new bootstrap.Tab(tab).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
