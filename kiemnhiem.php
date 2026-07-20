<?php
$page_title = 'Kiêm nhiệm';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = get_role_assignments();
    $roles = get_roles();

    if (($_POST['action'] ?? '') === 'add') {
        $teacher = trim($_POST['teacher'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $class_name = trim($_POST['class_name'] ?? '');
        $note = trim($_POST['note'] ?? '');

        $need_class = false;
        foreach ($roles as $r) {
            if ($r['name'] === $role) { $need_class = !empty($r['need_class']); break; }
        }

        if (!$teacher || !$role) {
            flash('Vui lòng chọn đầy đủ Giáo viên và Chức vụ.', 'danger');
        } elseif ($need_class && !$class_name) {
            flash('Chức vụ này cần chọn Lớp (ví dụ GVCN).', 'danger');
        } else {
            $exists = false;
            foreach ($items as $a) {
                if ($a['teacher'] === $teacher && $a['role'] === $role && ($a['class'] ?? '') === $class_name) {
                    $exists = true; break;
                }
            }
            if ($exists) {
                flash('Phân công kiêm nhiệm này đã tồn tại.', 'warning');
            } else {
                $items[] = [
                    'id' => date('YmdHis') . substr(microtime(), 2, 4),
                    'teacher' => $teacher,
                    'role' => $role,
                    'class' => $class_name,
                    'note' => $note,
                    'created_at' => date('c'),
                ];
                save_json(ROLE_ASSIGNMENTS_FILE, $items);
                $msg = "$teacher – $role" . ($class_name ? " ($class_name)" : '');
                flash("Đã thêm kiêm nhiệm: $msg", 'success');
            }
        }
        header('Location: ' . BASE_URL . 'kiemnhiem.php'); exit;
    }

    if (($_POST['action'] ?? '') === 'delete') {
        $id = $_POST['id'] ?? '';
        $items = array_values(array_filter($items, fn($a) => $a['id'] !== $id));
        save_json(ROLE_ASSIGNMENTS_FILE, $items);
        flash('Đã xóa kiêm nhiệm.', 'success');
        header('Location: ' . BASE_URL . 'kiemnhiem.php'); exit;
    }

    if (($_POST['action'] ?? '') === 'add_role') {
        $name = trim($_POST['role_name'] ?? '');
        $need_class = isset($_POST['need_class']);
        $note = trim($_POST['role_note'] ?? '');
        if ($name) {
            $roles = get_roles();
            $exists = false;
            foreach ($roles as $r) { if ($r['name'] === $name) { $exists = true; break; } }
            if (!$exists) {
                $roles[] = ['name' => $name, 'need_class' => $need_class, 'note' => $note];
                save_json(ROLES_FILE, $roles);
                flash("Đã thêm chức vụ: $name", 'success');
            } else {
                flash('Chức vụ đã tồn tại.', 'warning');
            }
        }
        header('Location: ' . BASE_URL . 'kiemnhiem.php'); exit;
    }
}

require_once 'includes/header.php';
$teachers = get_teachers(); sort($teachers);
$classes = get_classes();
$roles = get_roles();
$items = get_role_assignments();

// Group by teacher for display
$by_teacher = [];
foreach ($items as $a) {
    $by_teacher[$a['teacher']][] = $a;
}
ksort($by_teacher);
?>

<h3 class="mb-4"><i class="bi bi-person-badge"></i> Phân công Kiêm nhiệm</h3>

<div class="row">
<div class="col-lg-5 mb-4">
<div class="card">
<div class="card-header"><i class="bi bi-plus-circle"></i> Thêm kiêm nhiệm</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="add">
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
<option value="<?= e($r['name']) ?>" data-need-class="<?= !empty($r['need_class']) ? '1' : '0' ?>">
<?= e($r['name']) ?><?= !empty($r['note']) ? ' – ' . e($r['note']) : '' ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3" id="classWrap">
<label class="form-label fw-semibold">Lớp <span class="text-muted small">(bắt buộc với GVCN)</span></label>
<select name="class_name" id="classSelect" class="form-select">
<option value="">-- Chọn lớp --</option>
<?php foreach ($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label">Ghi chú</label>
<input type="text" name="note" class="form-control" placeholder="Tùy chọn">
</div>
<button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Lưu</button>
</form>
</div>
</div>

<div class="card mt-3">
<div class="card-header bg-secondary"><i class="bi bi-plus"></i> Thêm chức vụ mới</div>
<div class="card-body">
<form method="post" class="row g-2">
<input type="hidden" name="action" value="add_role">
<div class="col-12"><input type="text" name="role_name" class="form-control form-control-sm" placeholder="Tên chức vụ" required></div>
<div class="col-12"><input type="text" name="role_note" class="form-control form-control-sm" placeholder="Mô tả (tùy chọn)"></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="need_class" id="needClass"><label class="form-check-label small" for="needClass">Cần chọn lớp (như GVCN)</label></div></div>
<div class="col-12"><button type="submit" class="btn btn-outline-secondary btn-sm w-100">Thêm chức vụ</button></div>
</form>
</div>
</div>
</div>

<div class="col-lg-7">
<div class="card">
<div class="card-header"><i class="bi bi-list-ul"></i> Danh sách kiêm nhiệm (<?= count($items) ?>)</div>
<div class="card-body p-0">
<?php if ($by_teacher): ?>
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Giáo viên</th><th>Chức vụ</th><th>Lớp</th><th>Ghi chú</th><th></th></tr></thead>
<tbody>
<?php foreach ($by_teacher as $teacher => $list): ?>
<?php foreach ($list as $i => $a): ?>
<tr>
<td><?= $i === 0 ? e($teacher) : '' ?></td>
<td><span class="badge bg-info text-dark"><?= e($a['role']) ?></span></td>
<td><?= e($a['class'] ?? '') ?></td>
<td class="text-muted small"><?= e($a['note'] ?? '') ?></td>
<td>
<form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= e($a['id']) ?>">
<button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<div class="p-4 text-muted text-center">Chưa có phân công kiêm nhiệm.</div>
<?php endif; ?>
</div>
</div>
</div>
</div>

<script>
const roleSelect = document.getElementById('roleSelect');
const classWrap = document.getElementById('classWrap');
function toggleClass() {
    const opt = roleSelect.options[roleSelect.selectedIndex];
    const need = opt && opt.dataset.needClass === '1';
    classWrap.style.opacity = need ? '1' : '0.6';
}
roleSelect.addEventListener('change', toggleClass);
toggleClass();
</script>

<?php require_once 'includes/footer.php'; ?>
