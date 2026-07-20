<?php
$page_title = 'Đổi chéo phân công';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'swap_all';
    $t1 = trim($_POST['teacher1'] ?? '');
    $t2 = trim($_POST['teacher2'] ?? '');
    $id1 = $_POST['id1'] ?? '';
    $id2 = $_POST['id2'] ?? '';

    if ($mode === 'swap_all') {
        if (!$t1 || !$t2 || $t1 === $t2) {
            flash('Chọn 2 giáo viên khác nhau.', 'danger');
        } else {
            $assignments = get_assignments();
            $roles = get_role_assignments();
            foreach ($assignments as &$a) {
                if ($a['teacher'] === $t1) $a['teacher'] = $t2;
                elseif ($a['teacher'] === $t2) $a['teacher'] = $t1;
            }
            unset($a);
            foreach ($roles as &$a) {
                if ($a['teacher'] === $t1) $a['teacher'] = $t2;
                elseif ($a['teacher'] === $t2) $a['teacher'] = $t1;
            }
            unset($a);
            save_assignments($assignments);
            save_role_assignments($roles);
            flash("Đã đổi chéo toàn bộ phân công giữa $t1 ↔ $t2", 'success');
        }
    }

    if ($mode === 'swap_one') {
        $type = $_POST['type'] ?? 'day';
        if ($type === 'day') {
            $list = get_assignments();
            $i1 = $i2 = null;
            foreach ($list as $i => $a) {
                if ($a['id'] === $id1) $i1 = $i;
                if ($a['id'] === $id2) $i2 = $i;
            }
            if ($i1 === null || $i2 === null) {
                flash('Không tìm thấy phân công.', 'danger');
            } else {
                $tmp = $list[$i1]['teacher'];
                $list[$i1]['teacher'] = $list[$i2]['teacher'];
                $list[$i2]['teacher'] = $tmp;
                save_assignments($list);
                flash('Đã đổi chéo 2 phân công dạy môn.', 'success');
            }
        } else {
            $list = get_role_assignments();
            $i1 = $i2 = null;
            foreach ($list as $i => $a) {
                if ($a['id'] === $id1) $i1 = $i;
                if ($a['id'] === $id2) $i2 = $i;
            }
            if ($i1 === null || $i2 === null) {
                flash('Không tìm thấy kiêm nhiệm.', 'danger');
            } else {
                $tmp = $list[$i1]['teacher'];
                $list[$i1]['teacher'] = $list[$i2]['teacher'];
                $list[$i2]['teacher'] = $tmp;
                save_role_assignments($list);
                flash('Đã đổi chéo 2 kiêm nhiệm.', 'success');
            }
        }
    }

    if ($mode === 'transfer') {
        $from = trim($_POST['from'] ?? '');
        $to = trim($_POST['to'] ?? '');
        $ids = $_POST['ids'] ?? [];
        if (!$from || !$to || $from === $to || empty($ids)) {
            flash('Chọn GV nguồn, đích và ít nhất 1 mục.', 'danger');
        } else {
            $assignments = get_assignments();
            $n = 0;
            foreach ($assignments as &$a) {
                if ($a['teacher'] === $from && in_array($a['id'], $ids)) {
                    $a['teacher'] = $to;
                    $n++;
                }
            }
            unset($a);
            save_assignments($assignments);
            flash("Đã chuyển $n phân công từ $from → $to", 'success');
        }
    }

    header('Location: ' . BASE_URL . 'doicheo.php');
    exit;
}

require_once 'includes/header.php';
$teachers = get_teachers_sorted();
$assignments = get_assignments();
$roles = get_role_assignments();
?>

<h3 class="mb-3"><i class="bi bi-arrow-left-right"></i> Đổi chéo phân công</h3>

<div class="row">
<div class="col-lg-6 mb-4">
<div class="card">
<div class="card-header">Đổi chéo toàn bộ giữa 2 giáo viên</div>
<div class="card-body">
<form method="post" onsubmit="return confirm('Đổi toàn bộ dạy môn + kiêm nhiệm giữa 2 GV?')">
<input type="hidden" name="mode" value="swap_all">
<div class="mb-3">
<label class="form-label fw-semibold">Giáo viên A *</label>
<select name="teacher1" class="form-select" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-semibold">Giáo viên B *</label>
<select name="teacher2" class="form-select" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
</select>
</div>
<button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-left-right"></i> Đổi chéo toàn bộ</button>
</form>
</div></div>
</div>

<div class="col-lg-6 mb-4">
<div class="card">
<div class="card-header bg-success">Chuyển một số phân công dạy A → B</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="mode" value="transfer">
<div class="mb-2">
<label class="form-label small fw-semibold">Từ giáo viên</label>
<select name="from" id="fromT" class="form-select form-select-sm" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
</select>
</div>
<div class="mb-2">
<label class="form-label small fw-semibold">Sang giáo viên</label>
<select name="to" class="form-select form-select-sm" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
</select>
</div>
<div class="mb-2" id="transferList" style="max-height:220px;overflow:auto">
<p class="text-muted small">Chọn GV nguồn để hiện danh sách phân công.</p>
</div>
<button type="submit" class="btn btn-success btn-sm w-100">Chuyển đã chọn</button>
</form>
</div></div>
</div>
</div>

<div class="card">
<div class="card-header">Đổi chéo 2 mục cụ thể (dạy môn)</div>
<div class="card-body">
<form method="post" class="row g-2 align-items-end">
<input type="hidden" name="mode" value="swap_one">
<input type="hidden" name="type" value="day">
<div class="col-md-5">
<label class="form-label small">Phân công 1</label>
<select name="id1" class="form-select form-select-sm" required>
<option value="">-- Chọn --</option>
<?php foreach ($assignments as $a): ?>
<option value="<?= e($a['id']) ?>"><?= e($a['teacher']) ?> · <?= e($a['subject']) ?> · <?= e($a['class']) ?> (<?= e($a['periods']) ?>t)</option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-5">
<label class="form-label small">Phân công 2</label>
<select name="id2" class="form-select form-select-sm" required>
<option value="">-- Chọn --</option>
<?php foreach ($assignments as $a): ?>
<option value="<?= e($a['id']) ?>"><?= e($a['teacher']) ?> · <?= e($a['subject']) ?> · <?= e($a['class']) ?> (<?= e($a['periods']) ?>t)</option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-2">
<button type="submit" class="btn btn-outline-primary btn-sm w-100">Đổi</button>
</div>
</form>
</div></div>

<script>
const assignData = <?= json_encode(array_map(fn($a) => [
    'id' => $a['id'],
    'teacher' => $a['teacher'],
    'label' => $a['subject'] . ' · ' . $a['class'] . ' (' . $a['periods'] . 't)',
], $assignments), JSON_UNESCAPED_UNICODE) ?>;
document.getElementById('fromT')?.addEventListener('change', function() {
    const t = this.value;
    const box = document.getElementById('transferList');
    const items = assignData.filter(a => a.teacher === t);
    if (!items.length) { box.innerHTML = '<p class="text-muted small">GV này chưa có phân công dạy.</p>'; return; }
    box.innerHTML = items.map(a =>
        `<div class="form-check"><input class="form-check-input" type="checkbox" name="ids[]" value="${a.id}" id="i${a.id}">` +
        `<label class="form-check-label small" for="i${a.id}">${a.label}</label></div>`
    ).join('');
});
</script>
<?php require_once 'includes/footer.php'; ?>
