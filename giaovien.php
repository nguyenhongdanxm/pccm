<?php
$page_title = 'Quản lý Giáo viên';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teachers = get_teachers();

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $level = $_POST['level'] ?? 'THCS';
        $group = trim($_POST['group'] ?? '');
        if ($name && !in_array($name, $teachers)) {
            $teachers[] = $name;
            $teachers = sort_teachers_by_ten($teachers);
            save_json(TEACHERS_FILE, $teachers);
            set_teacher_level($name, $level);
            if ($group !== '') set_teacher_group($name, $group);
            flash("Đã thêm: $name", 'success');
        } else {
            flash($name ? 'Giáo viên đã tồn tại.' : 'Nhập họ tên.', 'warning');
        }
    }

    if ($action === 'delete') {
        $name = trim($_POST['name'] ?? '');
        $teachers = array_values(array_filter($teachers, fn($t) => $t !== $name));
        save_json(TEACHERS_FILE, $teachers);
        $meta = get_teacher_meta();
        unset($meta[$name]);
        save_teacher_meta($meta);
        flash("Đã xóa: $name", 'success');
    }

    if ($action === 'rename') {
        $old = trim($_POST['old_name'] ?? '');
        $new = trim($_POST['new_name'] ?? '');
        if ($old && $new && $old !== $new) {
            $idx = array_search($old, $teachers);
            if ($idx !== false) {
                $teachers[$idx] = $new;
                $teachers = sort_teachers_by_ten($teachers);
                save_json(TEACHERS_FILE, $teachers);
                $meta = get_teacher_meta();
                if (isset($meta[$old])) { $meta[$new] = $meta[$old]; unset($meta[$old]); save_teacher_meta($meta); }
                $assignments = get_assignments();
                foreach ($assignments as &$a) if ($a['teacher'] === $old) $a['teacher'] = $new;
                unset($a); save_assignments($assignments);
                $roles = get_role_assignments();
                foreach ($roles as &$a) if ($a['teacher'] === $old) $a['teacher'] = $new;
                unset($a); save_role_assignments($roles);
                flash("Đã đổi tên: $old → $new", 'success');
            }
        }
    }

    if ($action === 'set_meta') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            if (isset($_POST['level'])) set_teacher_level($name, $_POST['level']);
            if (isset($_POST['group'])) set_teacher_group($name, trim($_POST['group']));
            flash("Đã cập nhật: $name", 'success');
        }
    }

    if ($action === 'add_group') {
        $g = trim($_POST['group_name'] ?? '');
        $groups = get_groups();
        if ($g && !in_array($g, $groups)) {
            $groups[] = $g;
            save_groups($groups);
            flash("Đã thêm tổ: $g", 'success');
        }
    }

    if ($action === 'delete_group') {
        $g = trim($_POST['group_name'] ?? '');
        $groups = array_values(array_filter(get_groups(), fn($x) => $x !== $g));
        save_groups($groups);
        $meta = get_teacher_meta();
        foreach ($meta as $n => &$m) if (($m['group'] ?? '') === $g) $m['group'] = '';
        unset($m); save_teacher_meta($meta);
        flash("Đã xóa tổ: $g", 'success');
    }

    $q = [];
    if (!empty($_POST['keep_level'])) $q['level'] = $_POST['keep_level'];
    if (!empty($_POST['keep_group'])) $q['group'] = $_POST['keep_group'];
    header('Location: ' . BASE_URL . 'giaovien.php' . ($q ? '?' . http_build_query($q) : ''));
    exit;
}

require_once 'includes/header.php';
$teachers = get_teachers_sorted();
$groups = get_groups();
$f_level = $_GET['level'] ?? '';
$f_group = $_GET['group'] ?? '';

$filtered = array_values(array_filter($teachers, function($t) use ($f_level, $f_group) {
    if ($f_level && get_teacher_level($t) !== $f_level) return false;
    if ($f_group && get_teacher_group($t) !== $f_group) return false;
    return true;
}));

$count_thcs = count(array_filter($teachers, fn($t) => get_teacher_level($t) === 'THCS'));
$count_thpt = count($teachers) - $count_thcs;
?>

<h3 class="mb-3"><i class="bi bi-people"></i> Quản lý Giáo viên</h3>

<div class="row g-3 mb-3">
<div class="col-6 col-md-3"><div class="card stat-card py-2"><div class="number fs-4"><?= count($teachers) ?></div><div class="label">Tổng GV</div></div></div>
<div class="col-6 col-md-3"><div class="card stat-card py-2"><div class="number fs-4"><?= $count_thcs ?></div><div class="label">THCS (ĐM <?= QUOTA_THCS ?>t)</div></div></div>
<div class="col-6 col-md-3"><div class="card stat-card py-2"><div class="number fs-4"><?= $count_thpt ?></div><div class="label">THPT (ĐM <?= QUOTA_THPT ?>t)</div></div></div>
<div class="col-6 col-md-3"><div class="card stat-card py-2"><div class="number fs-4"><?= count($groups) ?></div><div class="label">Tổ chuyên môn</div></div></div>
</div>

<div class="row">
<div class="col-lg-4 mb-4">
<div class="card mb-3">
<div class="card-header">Thêm giáo viên</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="add">
<div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Họ tên" required></div>
<div class="mb-2">
<label class="form-label small mb-0">Cấp dạy</label>
<div class="btn-group w-100" role="group">
<input type="radio" class="btn-check" name="level" id="lvTHCS" value="THCS" checked>
<label class="btn btn-outline-primary btn-sm" for="lvTHCS">THCS (17t)</label>
<input type="radio" class="btn-check" name="level" id="lvTHPT" value="THPT">
<label class="btn btn-outline-primary btn-sm" for="lvTHPT">THPT (15t)</label>
</div>
</div>
<div class="mb-2">
<label class="form-label small mb-0">Tổ chuyên môn</label>
<select name="group" class="form-select form-select-sm">
<option value="">-- Chưa gán --</option>
<?php foreach ($groups as $g): ?><option value="<?= e($g) ?>"><?= e($g) ?></option><?php endforeach; ?>
</select>
</div>
<button type="submit" class="btn btn-primary w-100">Thêm</button>
</form>
</div></div>

<div class="card">
<div class="card-header">Quản lý tổ chuyên môn</div>
<div class="card-body">
<form method="post" class="input-group input-group-sm mb-2">
<input type="hidden" name="action" value="add_group">
<input type="text" name="group_name" class="form-control" placeholder="Tên tổ mới" required>
<button class="btn btn-outline-primary">Thêm tổ</button>
</form>
<?php foreach ($groups as $g): ?>
<div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1">
<span class="small"><?= e($g) ?></span>
<form method="post" onsubmit="return confirm('Xóa tổ này?')">
<input type="hidden" name="action" value="delete_group">
<input type="hidden" name="group_name" value="<?= e($g) ?>">
<button class="btn btn-sm btn-outline-danger py-0 px-1">×</button>
</form>
</div>
<?php endforeach; ?>
</div></div>
</div>

<div class="col-lg-8">
<div class="card">
<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
<span>Danh sách (<?= count($filtered) ?>/<?= count($teachers) ?>)</span>
<form method="get" class="d-flex flex-wrap gap-1">
<select name="level" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
<option value="">Mọi cấp</option>
<option value="THCS" <?= $f_level==='THCS'?'selected':'' ?>>THCS</option>
<option value="THPT" <?= $f_level==='THPT'?'selected':'' ?>>THPT</option>
</select>
<select name="group" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
<option value="">Mọi tổ</option>
<?php foreach ($groups as $g): ?>
<option value="<?= e($g) ?>" <?= $f_group===$g?'selected':'' ?>><?= e($g) ?></option>
<?php endforeach; ?>
</select>
<?php if ($f_level || $f_group): ?><a href="<?= BASE_URL ?>giaovien.php" class="btn btn-sm btn-outline-light">Xóa lọc</a><?php endif; ?>
</form>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover table-sm mb-0 align-middle">
<thead><tr><th>#</th><th>Họ tên</th><th>Cấp</th><th>Tổ</th><th>ĐM</th><th></th></tr></thead>
<tbody>
<?php foreach ($filtered as $i => $t):
    $lv = get_teacher_level($t);
    $gr = get_teacher_group($t);
?>
<tr>
<td><?= $i+1 ?></td>
<td>
<strong><?= e($t) ?></strong>
<div class="collapse mt-1" id="rn<?= $i ?>">
<form method="post" class="input-group input-group-sm">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="old_name" value="<?= e($t) ?>">
<input type="hidden" name="keep_level" value="<?= e($f_level) ?>">
<input type="hidden" name="keep_group" value="<?= e($f_group) ?>">
<input type="text" name="new_name" class="form-control" value="<?= e($t) ?>" required>
<button class="btn btn-primary">Lưu tên</button>
</form>
</div>
</td>
<td>
<form method="post" class="d-inline">
<input type="hidden" name="action" value="set_meta">
<input type="hidden" name="name" value="<?= e($t) ?>">
<input type="hidden" name="keep_level" value="<?= e($f_level) ?>">
<input type="hidden" name="keep_group" value="<?= e($f_group) ?>">
<select name="level" class="form-select form-select-sm" style="width:auto;min-width:90px" onchange="this.form.submit()">
<option value="THCS" <?= $lv==='THCS'?'selected':'' ?>>THCS</option>
<option value="THPT" <?= $lv==='THPT'?'selected':'' ?>>THPT</option>
</select>
</form>
</td>
<td>
<form method="post" class="d-inline">
<input type="hidden" name="action" value="set_meta">
<input type="hidden" name="name" value="<?= e($t) ?>">
<input type="hidden" name="keep_level" value="<?= e($f_level) ?>">
<input type="hidden" name="keep_group" value="<?= e($f_group) ?>">
<select name="group" class="form-select form-select-sm" style="width:auto;min-width:120px" onchange="this.form.submit()">
<option value="">—</option>
<?php foreach ($groups as $g): ?>
<option value="<?= e($g) ?>" <?= $gr===$g?'selected':'' ?>><?= e($g) ?></option>
<?php endforeach; ?>
</select>
</form>
</td>
<td><span class="badge bg-secondary"><?= get_quota($t) ?>t</span></td>
<td class="text-nowrap">
<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#rn<?= $i ?>"><i class="bi bi-pencil"></i></button>
<form method="post" class="d-inline" onsubmit="return confirm('Xóa GV này?')">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="name" value="<?= e($t) ?>">
<button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$filtered): ?><tr><td colspan="6" class="text-center text-muted">Không có giáo viên phù hợp bộ lọc.</td></tr><?php endif; ?>
</tbody></table>
</div></div></div>
</div></div>

<?php require_once 'includes/footer.php'; ?>
