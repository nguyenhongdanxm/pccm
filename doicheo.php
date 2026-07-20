<?php
$page_title = 'Đổi chéo phân công';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
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
            $nA = 0; $nR = 0;
            foreach ($assignments as &$a) {
                if ($a['teacher'] === $t1) { $a['teacher'] = $t2; $nA++; }
                elseif ($a['teacher'] === $t2) { $a['teacher'] = $t1; $nA++; }
            }
            unset($a);
            foreach ($roles as &$a) {
                if ($a['teacher'] === $t1) { $a['teacher'] = $t2; $nR++; }
                elseif ($a['teacher'] === $t2) { $a['teacher'] = $t1; $nR++; }
            }
            unset($a);
            save_assignments($assignments);
            save_role_assignments($roles);
            flash("Đã đổi chéo $nA mục dạy + $nR kiêm nhiệm giữa $t1 ↔ $t2", 'success');
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
            flash('Chọn GV nguồn, đích và ít nhất 1 mục.', 'warning');
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
$loads = get_teacher_loads();
?>

<h3 class="mb-2"><i class="bi bi-arrow-left-right"></i> Đổi chéo phân công</h3>
<div class="warn-box">
<strong><i class="bi bi-exclamation-triangle-fill"></i> Cảnh báo:</strong>
Thao tác đổi chéo / chuyển phân công <strong>không thể hoàn tác</strong> tự động.
Hãy kiểm tra kỹ trước khi xác nhận. Nên <a href="<?= BASE_URL ?>ketqua.php">tạo phiên bản Kết quả</a> trước khi đổi hàng loạt.
</div>

<div class="row g-3">
<!-- ĐỔI TOÀN BỘ -->
<div class="col-lg-6">
<div class="card h-100">
<div class="card-header bg-warning"><i class="bi bi-people"></i> Đổi chéo toàn bộ giữa 2 GV</div>
<div class="card-body">
<div class="danger-box small mb-3">Đổi <strong>tất cả</strong> dạy môn + kiêm nhiệm của 2 giáo viên cho nhau.</div>
<form method="post" id="formSwapAll">
<input type="hidden" name="mode" value="swap_all">
<div class="mb-2">
<label class="form-label fw-semibold">Giáo viên A *</label>
<select name="teacher1" id="swA" class="form-select" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t):
    $ld = $loads[$t] ?? null;
    $info = $ld ? number_format($ld['total'],2).'t' : '0t';
?>
<option value="<?= e($t) ?>"><?= e($t) ?> (<?= $info ?>)</option>
<?php endforeach; ?>
</select>
</div>
<div class="text-center text-muted my-1"><i class="bi bi-arrow-down-up"></i></div>
<div class="mb-3">
<label class="form-label fw-semibold">Giáo viên B *</label>
<select name="teacher2" id="swB" class="form-select" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t):
    $ld = $loads[$t] ?? null;
    $info = $ld ? number_format($ld['total'],2).'t' : '0t';
?>
<option value="<?= e($t) ?>"><?= e($t) ?> (<?= $info ?>)</option>
<?php endforeach; ?>
</select>
</div>
<button type="button" class="btn btn-warning w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSwapAll">
<i class="bi bi-arrow-left-right"></i> Đổi chéo toàn bộ
</button>
</form>
</div></div>
</div>

<!-- CHUYỂN MỘT PHẦN -->
<div class="col-lg-6">
<div class="card h-100">
<div class="card-header bg-success"><i class="bi bi-box-arrow-right"></i> Chuyển một số phân công dạy</div>
<div class="card-body">
<div class="info-box small mb-3">Chỉ chuyển các mục dạy môn được chọn từ GV nguồn sang GV đích.</div>
<form method="post" id="formTransfer">
<input type="hidden" name="mode" value="transfer">
<div class="row g-2 mb-2">
<div class="col-6">
<label class="form-label small fw-semibold">Từ GV</label>
<select name="from" id="fromT" class="form-select form-select-sm" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-6">
<label class="form-label small fw-semibold">Sang GV</label>
<select name="to" class="form-select form-select-sm" required>
<option value="">-- Chọn --</option>
<?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
</select>
</div>
</div>
<div class="mb-2 border rounded p-2" id="transferList" style="max-height:200px;overflow:auto;background:#fafbfc">
<p class="text-muted small mb-0">Chọn GV nguồn để hiện danh sách.</p>
</div>
<button type="button" class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalTransfer">
Chuyển các mục đã chọn
</button>
</form>
</div></div>
</div>

<!-- ĐỔI 2 MỤC -->
<div class="col-12">
<div class="card">
<div class="card-header"><i class="bi bi-shuffle"></i> Đổi chéo 2 mục dạy môn cụ thể</div>
<div class="card-body">
<form method="post" class="row g-2 align-items-end" id="formSwapOne">
<input type="hidden" name="mode" value="swap_one">
<input type="hidden" name="type" value="day">
<div class="col-md-5">
<label class="form-label small fw-semibold">Phân công 1</label>
<select name="id1" class="form-select form-select-sm" required>
<option value="">-- Chọn --</option>
<?php foreach ($assignments as $a): ?>
<option value="<?= e($a['id']) ?>"><?= e($a['teacher']) ?> · <?= e($a['subject']) ?> · <?= e($a['class']) ?> (<?= e($a['periods']) ?>t)</option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-5">
<label class="form-label small fw-semibold">Phân công 2</label>
<select name="id2" class="form-select form-select-sm" required>
<option value="">-- Chọn --</option>
<?php foreach ($assignments as $a): ?>
<option value="<?= e($a['id']) ?>"><?= e($a['teacher']) ?> · <?= e($a['subject']) ?> · <?= e($a['class']) ?> (<?= e($a['periods']) ?>t)</option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-2">
<button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalSwapOne">Đổi</button>
</div>
</form>
</div></div>
</div>
</div>

<!-- MODALS CẢNH BÁO -->
<div class="modal fade" id="modalSwapAll" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Xác nhận đổi chéo toàn bộ</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<p>Bạn sắp <strong>đổi toàn bộ</strong> phân công dạy và kiêm nhiệm giữa 2 giáo viên.</p>
<p class="text-danger mb-0"><strong>Thao tác này không thể hoàn tác.</strong> Tiếp tục?</p>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
<button type="button" class="btn btn-warning" onclick="document.getElementById('formSwapAll').submit()">Đồng ý đổi chéo</button>
</div>
</div></div></div>

<div class="modal fade" id="modalTransfer" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<div class="modal-header bg-success text-white"><h5 class="modal-title">Xác nhận chuyển phân công</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<p>Các mục dạy môn đã chọn sẽ được chuyển sang giáo viên đích.</p>
<p class="text-danger mb-0"><strong>Không thể hoàn tác tự động.</strong></p>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
<button type="button" class="btn btn-success" onclick="document.getElementById('formTransfer').submit()">Đồng ý chuyển</button>
</div>
</div></div></div>

<div class="modal fade" id="modalSwapOne" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Xác nhận đổi 2 mục</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">Hai giáo viên của 2 phân công đã chọn sẽ được đổi cho nhau. Tiếp tục?</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
<button type="button" class="btn btn-primary" onclick="document.getElementById('formSwapOne').submit()">Đồng ý</button>
</div>
</div></div></div>

<script>
const assignData = <?= json_encode(array_map(function($a) {
    return ['id'=>$a['id'],'teacher'=>$a['teacher'],'label'=>$a['subject'].' · '.$a['class'].' ('.$a['periods'].'t)'];
}, $assignments), JSON_UNESCAPED_UNICODE) ?>;
document.getElementById('fromT')?.addEventListener('change', function() {
    const t = this.value;
    const box = document.getElementById('transferList');
    const items = assignData.filter(a => a.teacher === t);
    if (!items.length) { box.innerHTML = '<p class="text-muted small mb-0">GV này chưa có phân công dạy.</p>'; return; }
    box.innerHTML = items.map(a =>
        `<div class="form-check"><input class="form-check-input" type="checkbox" name="ids[]" value="${a.id}" id="i${a.id}" form="formTransfer">` +
        `<label class="form-check-label small" for="i${a.id}">${a.label}</label></div>`
    ).join('');
});
</script>
<?php require_once 'includes/footer.php'; ?>
