<?php
$page_title = 'Quản lý Môn học';
require_once 'includes/functions.php';
require_login();

/** Khóa cột THCS — theo khối */
function keys_thcs() {
    $k = [];
    foreach (['6', '7', '8', '9'] as $g) {
        $k[] = ['key' => $g, 'label' => 'Khối ' . $g];
    }
    return $k;
}

/** Khóa cột THPT — tất cả theo từng lớp */
function keys_thpt() {
    $k = [];
    $classes = get_classes();
    $thpt = [];
    foreach ($classes as $c) {
        if (intval(get_grade($c)) >= 10) $thpt[] = $c;
    }
    if (!$thpt) $thpt = ['10A', '10B', '11A', '11B', '12A', '12B'];
    foreach ($thpt as $c) {
        $k[] = ['key' => $c, 'label' => $c];
    }
    return $k;
}

function period_val($data, $key) {
    if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
        return $data[$key];
    }
    // fallback khối cho lớp (10A ← 10) — dữ liệu cũ
    $g = preg_replace('/[^0-9]/', '', $key);
    if ($g !== '' && isset($data[$g]) && $data[$g] !== '' && $data[$g] !== null) {
        return $data[$g];
    }
    return '';
}

function is_key_active($data, $key) {
    $v = period_val($data, $key);
    return $v !== '' && is_numeric($v) && floatval($v) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjects = get_subjects();
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $subject = $_POST['subject'] ?? '';
        $level = $_POST['level'] ?? '';
        if (isset($subjects[$subject])) {
            $existing = $subjects[$subject];
            if (!is_array($existing)) $existing = [];

            // Giữ phần không thuộc level đang sửa
            $keep = [];
            foreach ($existing as $k => $v) {
                $g = intval(preg_replace('/[^0-9]/', '', (string)$k));
                $is_thpt_key = ($g >= 10) || (preg_match('/^(10|11|12)[A-Za-z]/', (string)$k));
                if ($level === 'thcs' && $is_thpt_key) $keep[$k] = $v;
                elseif ($level === 'thpt' && !$is_thpt_key && $g >= 6 && $g <= 9) $keep[$k] = $v;
            }

            $new = $keep;
            $checked = $_POST['on'] ?? [];
            if (!is_array($checked)) $checked = [];

            $keys = ($level === 'thcs') ? keys_thcs() : keys_thpt();

            foreach ($keys as $ik) {
                $k = $ik['key'];
                if (!in_array($k, $checked, true)) {
                    unset($new[$k]);
                    continue;
                }
                $val = trim($_POST['p_' . $k] ?? '');
                if ($val !== '' && is_numeric($val) && floatval($val) > 0) {
                    $new[$k] = floatval($val);
                } else {
                    $new[$k] = ($val !== '' && is_numeric($val)) ? floatval($val) : 1;
                }
            }

            // Khi lưu THPT theo lớp: bỏ khóa khối cũ 10/11/12 nếu đã có dữ liệu lớp
            if ($level === 'thpt') {
                foreach (['10', '11', '12'] as $og) {
                    unset($new[$og]);
                }
            }

            $subjects[$subject] = $new;
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã lưu: $subject", 'success');
        }
    }

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name && !isset($subjects[$name])) {
            $subjects[$name] = [];
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã thêm môn: $name", 'success');
        } else {
            flash($name ? 'Môn đã tồn tại.' : 'Nhập tên môn.', 'warning');
        }
    }

    if ($action === 'delete') {
        $name = trim($_POST['name'] ?? '');
        if ($name && isset($subjects[$name])) {
            unset($subjects[$name]);
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã xóa môn: $name", 'success');
        }
    }

    $tab = $_POST['tab'] ?? 'thcs';
    header('Location: ' . BASE_URL . 'monhoc.php?tab=' . urlencode($tab));
    exit;
}

require_once 'includes/header.php';
$subjects = get_subjects();
ksort($subjects);
$tab = $_GET['tab'] ?? 'thcs';
if (!in_array($tab, ['thcs', 'thpt'], true)) $tab = 'thcs';

function render_grade_row($subject, $data, $keys, $level, $tab) {
    $uid = md5($subject . $level);
    ?>
<form method="post" class="grade-form" id="f<?= e($uid) ?>">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="subject" value="<?= e($subject) ?>">
  <input type="hidden" name="level" value="<?= e($level) ?>">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">
  <div class="d-flex flex-wrap gap-2 align-items-end">
    <?php
    $prev_g = '';
    foreach ($keys as $ik):
      $k = $ik['key'];
      $active = is_key_active($data, $k);
      $val = period_val($data, $k);
      $id = 'c_' . $uid . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $k);
      $g = get_grade($k);
      if ($level === 'thpt' && $g !== $prev_g && $prev_g !== ''):
    ?>
    <div class="grade-sep" title="Khối <?= e($g) ?>"></div>
    <?php endif; $prev_g = $g; ?>
    <div class="grade-cell <?= $active ? 'is-on' : '' ?>" data-cell>
      <div class="form-check mb-1 text-center">
        <input class="form-check-input grade-check" type="checkbox" name="on[]" value="<?= e($k) ?>"
          id="<?= e($id) ?>" <?= $active ? 'checked' : '' ?>
          onchange="toggleCell(this)">
        <label class="form-check-label small fw-semibold" for="<?= e($id) ?>"><?= e($ik['label']) ?></label>
      </div>
      <input type="number" name="p_<?= e($k) ?>" class="form-control form-control-sm text-center period-input"
        step="0.01" min="0" max="20"
        value="<?= $active ? e($val) : '' ?>"
        placeholder="tiết"
        <?= $active ? '' : 'disabled' ?>>
    </div>
    <?php endforeach; ?>
    <div class="ms-1">
      <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Lưu</button>
    </div>
  </div>
</form>
    <?php
}
?>

<style>
.grade-cell{
  width:88px;padding:.5rem .4rem;border:1px solid #dee2e6;border-radius:10px;
  background:#f8f9fa;text-align:center;transition:all .15s
}
.grade-cell.is-on{
  background:#e8f0fe;border-color:#1F4E79;box-shadow:0 0 0 1px #1F4E79 inset
}
.grade-cell .period-input:disabled{background:#eee;opacity:.55}
.grade-cell .form-check-input{margin:0 auto .15rem;float:none;display:block}
.grade-cell .form-check-label{cursor:pointer;font-size:.8rem}
.grade-sep{width:1px;align-self:stretch;background:#ced4da;margin:0 .25rem}
.subject-row{
  background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.06);
  padding:1rem 1.1rem;margin-bottom:.75rem
}
.subject-row .sub-name{font-weight:700;color:#1F4E79;min-width:140px}
.nav-level .nav-link{
  font-weight:700;color:#1F4E79;border:2px solid #dee2e6;border-radius:10px!important;
  padding:.6rem 1.25rem;margin-right:.5rem;background:#fff
}
.nav-level .nav-link.active{
  background:#1F4E79!important;color:#fff!important;border-color:#1F4E79!important
}
.hint{font-size:.88rem;color:#6c757d}
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h3 class="mb-0"><i class="bi bi-book"></i> Môn học & Số tiết</h3>
</div>

<ul class="nav nav-level mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $tab==='thcs'?'active':'' ?>" href="<?= BASE_URL ?>monhoc.php?tab=thcs">
      <i class="bi bi-mortarboard"></i> THCS <span class="badge bg-light text-dark">khối 6–9</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab==='thpt'?'active':'' ?>" href="<?= BASE_URL ?>monhoc.php?tab=thpt">
      <i class="bi bi-building"></i> THPT <span class="badge bg-light text-dark">theo lớp</span>
    </a>
  </li>
</ul>

<div class="hint mb-3">
  <i class="bi bi-info-circle"></i>
  <strong>Tích</strong> = môn dạy ở khối/lớp đó · Nhập <strong>số tiết</strong> bên dưới · Bỏ tích = không dạy.
  <?php if ($tab === 'thpt'): ?>
  <br><strong>THPT:</strong> tất cả môn nhập theo <em>từng lớp</em> (10A, 10B, 11A, 11B, 12A, 12B).
  <?php else: ?>
  <br><strong>THCS:</strong> nhập theo <em>khối</em> (6, 7, 8, 9).
  <?php endif; ?>
</div>

<div class="row mb-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header py-2">Thêm môn mới</div>
      <div class="card-body py-2">
        <form method="post" class="d-flex gap-2">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="tab" value="<?= e($tab) ?>">
          <input type="text" name="name" class="form-control form-control-sm" placeholder="Tên môn" required>
          <button class="btn btn-primary btn-sm text-nowrap">Thêm</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($tab === 'thcs'): ?>

<div class="mb-2 text-muted small fw-semibold text-uppercase">THCS — tích khối & số tiết</div>

<?php foreach ($subjects as $subject => $data):
  $keys = keys_thcs();
?>
<div class="subject-row">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
    <div class="sub-name"><?= e($subject) ?></div>
    <form method="post" onsubmit="return confirm('Xóa môn này?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="name" value="<?= e($subject) ?>">
      <input type="hidden" name="tab" value="thcs">
      <button class="btn btn-outline-danger btn-sm py-0" type="submit"><i class="bi bi-trash"></i></button>
    </form>
  </div>
  <?php render_grade_row($subject, $data, $keys, 'thcs', 'thcs'); ?>
</div>
<?php endforeach; ?>

<?php else: ?>

<div class="mb-2 text-muted small fw-semibold text-uppercase">THPT — tích từng lớp & số tiết (10A · 10B · 11A · 11B · 12A · 12B)</div>

<?php
$keys = keys_thpt();
foreach ($subjects as $subject => $data):
?>
<div class="subject-row">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
    <div class="sub-name"><?= e($subject) ?></div>
    <form method="post" onsubmit="return confirm('Xóa môn này?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="name" value="<?= e($subject) ?>">
      <input type="hidden" name="tab" value="thpt">
      <button class="btn btn-outline-danger btn-sm py-0" type="submit"><i class="bi bi-trash"></i></button>
    </form>
  </div>
  <?php render_grade_row($subject, $data, $keys, 'thpt', 'thpt'); ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php if (!$subjects): ?>
<div class="alert alert-warning">Chưa có môn học. Hãy thêm môn mới.</div>
<?php endif; ?>

<script>
function toggleCell(cb) {
  var cell = cb.closest('[data-cell]');
  if (!cell) return;
  var input = cell.querySelector('.period-input');
  if (cb.checked) {
    cell.classList.add('is-on');
    if (input) {
      input.disabled = false;
      if (input.value === '') input.value = '1';
      input.focus();
    }
  } else {
    cell.classList.remove('is-on');
    if (input) {
      input.disabled = true;
      input.value = '';
    }
  }
}
</script>

<?php require_once 'includes/footer.php'; ?>
