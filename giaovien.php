<?php
$page_title = 'Quản lý Giáo viên';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teachers = get_teachers();

    if ($action === 'save_quota') {
        $s = get_settings();
        $s['quota_thcs'] = is_numeric($_POST['quota_thcs'] ?? '') ? floatval($_POST['quota_thcs']) : DEFAULT_QUOTA_THCS;
        $s['quota_thpt'] = is_numeric($_POST['quota_thpt'] ?? '') ? floatval($_POST['quota_thpt']) : DEFAULT_QUOTA_THPT;
        save_settings($s);
        flash('Đã lưu định mức tiết/tuần.', 'success');
    }

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name && !in_array($name, $teachers)) {
            $teachers[] = $name;
            $teachers = sort_teachers_by_ten($teachers);
            save_json(TEACHERS_FILE, $teachers);
            set_teacher_flags($name, [
                'khxh' => !empty($_POST['khxh']),
                'khtn' => !empty($_POST['khtn']),
                'thcs' => !empty($_POST['thcs']) || (empty($_POST['thcs']) && empty($_POST['thpt'])),
                'thpt' => !empty($_POST['thpt']),
                'tap_su' => !empty($_POST['tap_su']),
            ]);
            $cm = $_POST['chuyen_mon'] ?? [];
            if (!is_array($cm)) $cm = $cm ? [$cm] : [];
            set_teacher_chuyen_mon($name, $cm);
            flash("Đã thêm: $name", 'success');
        } else {
            flash($name ? 'Giáo viên đã tồn tại.' : 'Nhập họ tên.', 'warning');
        }
    }

    if ($action === 'delete') {
        $name = trim($_POST['name'] ?? '');
        $teachers = array_values(array_filter($teachers, fn($t) => $t !== $name));
        save_json(TEACHERS_FILE, $teachers);
        $meta = get_teacher_meta(); unset($meta[$name]); save_teacher_meta($meta);
        flash("Đã xóa: $name", 'success');
    }

    if ($action === 'rename') {
        $old = trim($_POST['old_name'] ?? '');
        $new = trim($_POST['new_name'] ?? '');
        if ($old && $new && $old !== $new) {
            $idx = array_search($old, $teachers);
            if ($idx !== false) {
                $teachers[$idx] = $new;
                save_json(TEACHERS_FILE, sort_teachers_by_ten($teachers));
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

    if ($action === 'set_flags') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            set_teacher_flags($name, [
                'khxh' => !empty($_POST['khxh']),
                'khtn' => !empty($_POST['khtn']),
                'thcs' => !empty($_POST['thcs']),
                'thpt' => !empty($_POST['thpt']),
                'tap_su' => !empty($_POST['tap_su']),
            ]);
            flash("Đã cập nhật: $name", 'success');
        }
    }

    if ($action === 'set_chuyen_mon') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $cm = $_POST['chuyen_mon'] ?? [];
            if (!is_array($cm)) $cm = $cm ? [$cm] : [];
            set_teacher_chuyen_mon($name, $cm);
            flash("Đã cập nhật chuyên môn: $name", 'success');
        }
    }

    $q = array_filter([
        'f_khxh' => $_POST['keep_f_khxh'] ?? '',
        'f_khtn' => $_POST['keep_f_khtn'] ?? '',
        'f_thcs' => $_POST['keep_f_thcs'] ?? '',
        'f_thpt' => $_POST['keep_f_thpt'] ?? '',
        'f_tap_su' => $_POST['keep_f_tap_su'] ?? '',
        'f_cm' => $_POST['keep_f_cm'] ?? '',
        'q' => $_POST['keep_q'] ?? '',
    ], fn($v) => $v !== '' && $v !== null);
    header('Location: ' . BASE_URL . 'giaovien.php' . ($q ? '?' . http_build_query($q) : ''));
    exit;
}

require_once 'includes/header.php';
$teachers = get_teachers_sorted();
$subject_names = array_keys(get_subjects());
sort($subject_names);

$q_search = trim($_GET['q'] ?? '');
$f_khxh = !empty($_GET['f_khxh']);
$f_khtn = !empty($_GET['f_khtn']);
$f_thcs = !empty($_GET['f_thcs']);
$f_thpt = !empty($_GET['f_thpt']);
$f_tap_su = !empty($_GET['f_tap_su']);
$f_cm = trim($_GET['f_cm'] ?? '');

$filtered = array_values(array_filter($teachers, function($t) use ($q_search, $f_khxh, $f_khtn, $f_thcs, $f_thpt, $f_tap_su, $f_cm) {
    if ($q_search && mb_stripos($t, $q_search) === false) return false;
    $f = get_teacher_flags($t);
    if ($f_khxh && !$f['khxh']) return false;
    if ($f_khtn && !$f['khtn']) return false;
    if ($f_thcs && !$f['thcs']) return false;
    if ($f_thpt && !$f['thpt']) return false;
    if ($f_tap_su && !$f['tap_su']) return false;
    if ($f_cm !== '') {
        $cm = $f['chuyen_mon'] ?? [];
        if (!in_array($f_cm, $cm, true)) return false;
    }
    return true;
}));

$n_khxh = count(array_filter($teachers, fn($t) => get_teacher_flags($t)['khxh']));
$n_khtn = count(array_filter($teachers, fn($t) => get_teacher_flags($t)['khtn']));
$n_thcs = count(array_filter($teachers, fn($t) => get_teacher_flags($t)['thcs']));
$n_thpt = count(array_filter($teachers, fn($t) => get_teacher_flags($t)['thpt']));
$n_tap = count(array_filter($teachers, fn($t) => get_teacher_flags($t)['tap_su']));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
<h3 class="mb-0"><i class="bi bi-people"></i> Quản lý Giáo viên</h3>
<form method="post" class="d-flex flex-wrap align-items-end gap-2 border rounded px-3 py-2 bg-white shadow-sm">
<input type="hidden" name="action" value="save_quota">
<div>
<label class="form-label small mb-0 fw-semibold">Định mức THCS</label>
<div class="input-group input-group-sm" style="width:140px">
<input type="number" name="quota_thcs" class="form-control" step="0.5" min="1" max="30" value="<?= e(get_quota_thcs()) ?>">
<span class="input-group-text">tiết/tuần</span>
</div>
</div>
<div>
<label class="form-label small mb-0 fw-semibold">Định mức THPT</label>
<div class="input-group input-group-sm" style="width:140px">
<input type="number" name="quota_thpt" class="form-control" step="0.5" min="1" max="30" value="<?= e(get_quota_thpt()) ?>">
<span class="input-group-text">tiết/tuần</span>
</div>
</div>
<button type="submit" class="btn btn-primary btn-sm">Lưu định mức</button>
</form>
</div>

<div class="row g-2 mb-3">
<div class="col"><div class="card stat-card py-2"><div class="number fs-5"><?= count($teachers) ?></div><div class="label">Tổng GV</div></div></div>
<div class="col"><div class="card stat-card py-2"><div class="number fs-5"><?= $n_khxh ?></div><div class="label">Tổ KHXH</div></div></div>
<div class="col"><div class="card stat-card py-2"><div class="number fs-5"><?= $n_khtn ?></div><div class="label">Tổ KHTN</div></div></div>
<div class="col"><div class="card stat-card py-2"><div class="number fs-5"><?= $n_thcs ?></div><div class="label">THCS</div></div></div>
<div class="col"><div class="card stat-card py-2"><div class="number fs-5"><?= $n_thpt ?></div><div class="label">THPT</div></div></div>
<div class="col"><div class="card stat-card py-2"><div class="number fs-5"><?= $n_tap ?></div><div class="label">Tập sự</div></div></div>
</div>

<div class="row">
<div class="col-lg-3 mb-4">
<div class="card">
<div class="card-header">Thêm giáo viên</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="add">
<div class="mb-2"><input type="text" name="name" class="form-control form-control-sm" placeholder="Họ tên" required></div>
<div class="mb-2">
<label class="form-label small mb-0 fw-semibold">Chuyên môn</label>
<select name="chuyen_mon[]" class="form-select form-select-sm" multiple size="5">
<?php foreach ($subject_names as $s): ?>
<option value="<?= e($s) ?>"><?= e($s) ?></option>
<?php endforeach; ?>
</select>
<div class="form-text">Giữ Ctrl để chọn nhiều môn</div>
</div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="khxh" id="a_khxh" value="1"><label class="form-check-label" for="a_khxh">Tổ KHXH</label></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="khtn" id="a_khtn" value="1"><label class="form-check-label" for="a_khtn">Tổ KHTN</label></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="thcs" id="a_thcs" value="1" checked><label class="form-check-label" for="a_thcs">THCS</label></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="thpt" id="a_thpt" value="1"><label class="form-check-label" for="a_thpt">THPT</label></div>
<div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="tap_su" id="a_tap" value="1"><label class="form-check-label" for="a_tap">Tập sự</label></div>
<button type="submit" class="btn btn-primary btn-sm w-100">Thêm</button>
</form>
</div></div>
</div>

<div class="col-lg-9">
<div class="card">
<div class="card-header">
<form method="get" class="row g-2 align-items-end">
<div class="col-md-3">
<input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm tên..." value="<?= e($q_search) ?>">
</div>
<div class="col-md-3">
<select name="f_cm" class="form-select form-select-sm">
<option value="">Mọi chuyên môn</option>
<?php foreach ($subject_names as $s): ?>
<option value="<?= e($s) ?>" <?= $f_cm===$s?'selected':'' ?>><?= e($s) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-auto">
<div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" name="f_khxh" value="1" id="fk" <?= $f_khxh?'checked':'' ?>><label class="form-check-label small" for="fk">KHXH</label></div>
<div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" name="f_khtn" value="1" id="fn" <?= $f_khtn?'checked':'' ?>><label class="form-check-label small" for="fn">KHTN</label></div>
<div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" name="f_thcs" value="1" id="fc" <?= $f_thcs?'checked':'' ?>><label class="form-check-label small" for="fc">THCS</label></div>
<div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" name="f_thpt" value="1" id="fp" <?= $f_thpt?'checked':'' ?>><label class="form-check-label small" for="fp">THPT</label></div>
<div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" name="f_tap_su" value="1" id="ft" <?= $f_tap_su?'checked':'' ?>><label class="form-check-label small" for="ft">Tập sự</label></div>
</div>
<div class="col-auto">
<button class="btn btn-sm btn-light text-dark">Lọc</button>
<a href="<?= BASE_URL ?>giaovien.php" class="btn btn-sm btn-outline-light">Xóa lọc</a>
</div>
</form>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0 align-middle text-center">
<thead>
<tr>
<th class="text-start">#</th>
<th class="text-start">Họ tên</th>
<th class="text-start" style="min-width:160px">Chuyên môn</th>
<th title="Tổ Khoa học xã hội">KHXH</th>
<th title="Tổ Khoa học tự nhiên">KHTN</th>
<th>THCS</th>
<th>THPT</th>
<th>Tập sự</th>
<th>ĐM</th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach ($filtered as $i => $t):
    $f = get_teacher_flags($t);
    $cm = $f['chuyen_mon'] ?? [];
    $quota = get_quota($t);
?>
<tr>
<td class="text-start"><?= $i+1 ?></td>
<td class="text-start">
<strong><?= e($t) ?></strong>
<div class="collapse mt-1" id="rn<?= $i ?>">
<form method="post" class="input-group input-group-sm">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="old_name" value="<?= e($t) ?>">
<input type="text" name="new_name" class="form-control" value="<?= e($t) ?>" required>
<button class="btn btn-primary">Lưu</button>
</form>
</div>
</td>
<td class="text-start p-1">
<form method="post" id="cm<?= $i ?>">
<input type="hidden" name="action" value="set_chuyen_mon">
<input type="hidden" name="name" value="<?= e($t) ?>">
<input type="hidden" name="keep_q" value="<?= e($q_search) ?>">
<input type="hidden" name="keep_f_cm" value="<?= e($f_cm) ?>">
<?php if ($f_khxh): ?><input type="hidden" name="keep_f_khxh" value="1"><?php endif; ?>
<?php if ($f_khtn): ?><input type="hidden" name="keep_f_khtn" value="1"><?php endif; ?>
<?php if ($f_thcs): ?><input type="hidden" name="keep_f_thcs" value="1"><?php endif; ?>
<?php if ($f_thpt): ?><input type="hidden" name="keep_f_thpt" value="1"><?php endif; ?>
<?php if ($f_tap_su): ?><input type="hidden" name="keep_f_tap_su" value="1"><?php endif; ?>
<select name="chuyen_mon[]" class="form-select form-select-sm" multiple size="3" style="min-width:140px"
  onchange="document.getElementById('cm<?= $i ?>').submit()" title="Ctrl+click chọn nhiều">
<?php foreach ($subject_names as $s): ?>
<option value="<?= e($s) ?>" <?= in_array($s, $cm, true)?'selected':'' ?>><?= e($s) ?></option>
<?php endforeach; ?>
</select>
</form>
<?php if ($cm): ?>
<div class="mt-1"><?php foreach ($cm as $s): ?><span class="badge bg-primary me-1 mb-1"><?= e($s) ?></span><?php endforeach; ?></div>
<?php else: ?>
<span class="text-muted small">Chưa chọn</span>
<?php endif; ?>
</td>
<td colspan="5" class="p-1">
<form method="post" id="flag<?= $i ?>" class="d-contents">
<input type="hidden" name="action" value="set_flags">
<input type="hidden" name="name" value="<?= e($t) ?>">
<input type="hidden" name="keep_q" value="<?= e($q_search) ?>">
<input type="hidden" name="keep_f_cm" value="<?= e($f_cm) ?>">
<?php if ($f_khxh): ?><input type="hidden" name="keep_f_khxh" value="1"><?php endif; ?>
<?php if ($f_khtn): ?><input type="hidden" name="keep_f_khtn" value="1"><?php endif; ?>
<?php if ($f_thcs): ?><input type="hidden" name="keep_f_thcs" value="1"><?php endif; ?>
<?php if ($f_thpt): ?><input type="hidden" name="keep_f_thpt" value="1"><?php endif; ?>
<?php if ($f_tap_su): ?><input type="hidden" name="keep_f_tap_su" value="1"><?php endif; ?>
</form>
<div class="d-flex justify-content-around">
<input form="flag<?= $i ?>" class="form-check-input" type="checkbox" name="khxh" value="1" <?= $f['khxh']?'checked':'' ?> onchange="document.getElementById('flag<?= $i ?>').submit()">
<input form="flag<?= $i ?>" class="form-check-input" type="checkbox" name="khtn" value="1" <?= $f['khtn']?'checked':'' ?> onchange="document.getElementById('flag<?= $i ?>').submit()">
<input form="flag<?= $i ?>" class="form-check-input" type="checkbox" name="thcs" value="1" <?= $f['thcs']?'checked':'' ?> onchange="document.getElementById('flag<?= $i ?>').submit()">
<input form="flag<?= $i ?>" class="form-check-input" type="checkbox" name="thpt" value="1" <?= $f['thpt']?'checked':'' ?> onchange="document.getElementById('flag<?= $i ?>').submit()">
<input form="flag<?= $i ?>" class="form-check-input" type="checkbox" name="tap_su" value="1" <?= $f['tap_su']?'checked':'' ?> onchange="document.getElementById('flag<?= $i ?>').submit()">
</div>
</td>
<td><span class="badge bg-secondary"><?= number_format($quota,0) ?>t</span></td>
<td class="text-nowrap">
<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#rn<?= $i ?>"><i class="bi bi-pencil"></i></button>
<form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="name" value="<?= e($t) ?>">
<button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$filtered): ?>
<tr><td colspan="10" class="text-muted py-3">Không có giáo viên phù hợp.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="card-footer small text-muted">
Hiển thị <?= count($filtered) ?>/<?= count($teachers) ?> · Chuyên môn lấy từ danh mục Môn học · Giữ Ctrl để chọn nhiều môn
</div>
</div></div>
</div></div>

<?php require_once 'includes/footer.php'; ?>
