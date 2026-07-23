<?php
$page_title = 'Quản lý Môn học';
require_once 'includes/functions.php';
require_login();

/**
 * Các môn THPT cần nhập số tiết theo TỪNG LỚP (10A, 10B…)
 * vì chuyên đề / tổ hợp khác nhau giữa các lớp.
 * Các môn khác: nhập theo KHỐI (6–12).
 */
function subjects_per_class() {
    return [
        'CĐ Lịch sử',
        'Địa lí',
        'Địa lý',
        'CĐ Địa lí',
        'CĐ Địa lý',
        'Sinh học',
        'CĐ Sinh',
        'CĐ Sinh học',
        'GD&KTPL',
        'GDKT&PL',
        'GD KT&PL',
        'Vật lí',
        'CĐ Vật lí',
        'CĐ Lí',
        'Hóa học',
        'Hoá học',
        'CĐ Hoá',
        'CĐ Hóa',
        'CĐ Hoá học',
        'CĐ Hóa học',
        'Tin học',
        'Âm nhạc',
        'Mỹ thuật',
        'Mỹ Thuật',
    ];
}

function is_per_class_subject($name) {
    $list = subjects_per_class();
    foreach ($list as $s) {
        if (mb_strtolower(trim($s), 'UTF-8') === mb_strtolower(trim($name), 'UTF-8')) {
            return true;
        }
    }
    return false;
}

/** Cột nhập cho 1 môn */
function period_keys_for_subject($subject_name) {
    $keys = [];
    // THCS luôn theo khối
    foreach (['6', '7', '8', '9'] as $g) {
        $keys[] = ['key' => $g, 'label' => $g, 'group' => 'THCS'];
    }
    if (is_per_class_subject($subject_name)) {
        $classes = get_classes();
        $thpt = [];
        foreach ($classes as $c) {
            if (intval(get_grade($c)) >= 10) $thpt[] = $c;
        }
        if (!$thpt) $thpt = ['10A', '10B', '11A', '11B', '12A', '12B'];
        foreach ($thpt as $c) {
            $keys[] = ['key' => $c, 'label' => $c, 'group' => 'THPT'];
        }
    } else {
        foreach (['10', '11', '12'] as $g) {
            $keys[] = ['key' => $g, 'label' => $g, 'group' => 'THPT'];
        }
    }
    return $keys;
}

function period_display_value($grades_data, $key) {
    if (isset($grades_data[$key]) && $grades_data[$key] !== '' && $grades_data[$key] !== null) {
        return $grades_data[$key];
    }
    $grade = preg_replace('/[^0-9]/', '', $key);
    if ($grade !== '' && isset($grades_data[$grade]) && $grades_data[$grade] !== '' && $grades_data[$grade] !== null) {
        return $grades_data[$grade];
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjects = get_subjects();

    if (($_POST['action'] ?? '') === 'update') {
        $subject = $_POST['subject'] ?? '';
        if (isset($subjects[$subject])) {
            $keys = period_keys_for_subject($subject);
            $new = [];
            foreach ($keys as $ik) {
                $k = $ik['key'];
                $val = trim($_POST['p_' . $k] ?? '');
                if ($val !== '' && is_numeric($val)) {
                    $new[$k] = floatval($val);
                }
            }
            $subjects[$subject] = $new;
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã cập nhật số tiết: $subject", 'success');
        }
    }

    if (($_POST['action'] ?? '') === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name && !isset($subjects[$name])) {
            $subjects[$name] = [];
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã thêm môn: $name", 'success');
        } else {
            flash($name ? 'Môn đã tồn tại.' : 'Nhập tên môn.', 'warning');
        }
    }

    if (($_POST['action'] ?? '') === 'delete') {
        $name = trim($_POST['name'] ?? '');
        if ($name && isset($subjects[$name])) {
            unset($subjects[$name]);
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã xóa môn: $name", 'success');
        }
    }

    header('Location: ' . BASE_URL . 'monhoc.php');
    exit;
}

require_once 'includes/header.php';
$subjects = get_subjects();
ksort($subjects);

// Tách 2 nhóm để hiển thị rõ
$subjects_special = []; // nhập theo lớp THPT
$subjects_normal = [];
foreach ($subjects as $name => $data) {
    if (is_per_class_subject($name)) $subjects_special[$name] = $data;
    else $subjects_normal[$name] = $data;
}

function render_subject_card($subject, $grades_data) {
    $keys = period_keys_for_subject($subject);
    $thcs = array_values(array_filter($keys, fn($k) => $k['group'] === 'THCS'));
    $thpt = array_values(array_filter($keys, fn($k) => $k['group'] === 'THPT'));
    $per_class = is_per_class_subject($subject);
    ?>
<div class="card mb-3 subject-card <?= $per_class ? 'border-primary' : '' ?>">
  <div class="card-header d-flex justify-content-between align-items-center <?= $per_class ? 'bg-primary' : '' ?>">
    <span>
      <?= e($subject) ?>
      <?php if ($per_class): ?>
      <span class="badge bg-warning text-dark ms-2">THPT theo lớp</span>
      <?php endif; ?>
    </span>
    <form method="post" class="d-inline" onsubmit="return confirm('Xóa môn <?= e(addslashes($subject)) ?>?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="name" value="<?= e($subject) ?>">
      <button class="btn btn-sm btn-outline-light" type="submit" title="Xóa môn"><i class="bi bi-trash"></i></button>
    </form>
  </div>
  <div class="card-body py-3">
    <form method="post">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="subject" value="<?= e($subject) ?>">

      <div class="row g-3 align-items-end">
        <div class="col-auto">
          <div class="small text-muted fw-semibold mb-1">THCS</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($thcs as $ik): ?>
            <div style="width:72px">
              <label class="form-label small text-center d-block mb-0 text-muted"><?= e($ik['label']) ?></label>
              <input type="number" name="p_<?= e($ik['key']) ?>" class="form-control form-control-sm text-center"
                step="0.01" min="0" max="20"
                value="<?= e(period_display_value($grades_data, $ik['key'])) ?>">
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-auto d-none d-md-block"><div style="width:1px;height:48px;background:#dee2e6"></div></div>

        <div class="col">
          <div class="small fw-semibold mb-1 <?= $per_class ? 'text-primary' : 'text-muted' ?>">
            THPT <?= $per_class ? '· từng lớp' : '· theo khối' ?>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php
            $prev_g = '';
            foreach ($thpt as $ik):
              $g = get_grade($ik['key']);
              if ($per_class && $g !== $prev_g && $prev_g !== ''):
            ?>
              <div class="vr mx-1"></div>
            <?php endif; $prev_g = $g; ?>
            <div style="width:72px">
              <label class="form-label small text-center d-block mb-0 <?= $per_class ? 'text-primary fw-semibold' : 'text-muted' ?>"><?= e($ik['label']) ?></label>
              <input type="number" name="p_<?= e($ik['key']) ?>" class="form-control form-control-sm text-center <?= $per_class ? 'border-primary' : '' ?>"
                step="0.01" min="0" max="20"
                value="<?= e(period_display_value($grades_data, $ik['key'])) ?>">
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Lưu</button>
        </div>
      </div>
    </form>
  </div>
</div>
    <?php
}
?>

<style>
.subject-card .form-control-sm{padding:.25rem .35rem}
.section-title{
  font-size:1.05rem;font-weight:700;color:#1F4E79;
  border-left:4px solid #1F4E79;padding-left:.75rem;margin:1.5rem 0 .75rem
}
.section-title.special{border-color:#0d6efd;color:#0d6efd}
.hint-box{
  background:#f8f9fa;border:1px solid #e9ecef;border-radius:10px;
  padding:.85rem 1rem;font-size:.9rem;color:#495057
}
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h3 class="mb-0"><i class="bi bi-book"></i> Môn học & Số tiết chuẩn</h3>
</div>

<div class="hint-box mb-4">
  <div class="row g-2">
    <div class="col-md-6">
      <strong><i class="bi bi-grid"></i> Môn thường</strong> — nhập theo <em>khối</em> 6→12
      <div class="text-muted small">Áp dụng giống nhau cho mọi lớp trong khối</div>
    </div>
    <div class="col-md-6">
      <strong class="text-primary"><i class="bi bi-layout-three-columns"></i> Môn THPT theo lớp</strong> — nhập <em>10A, 10B, 11A…</em>
      <div class="text-muted small">Dùng khi các lớp học số tiết / chuyên đề khác nhau</div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-5 col-lg-4">
    <div class="card">
      <div class="card-header">Thêm môn mới</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="add">
          <input type="text" name="name" class="form-control form-control-sm mb-2" placeholder="Tên môn học" required>
          <button type="submit" class="btn btn-primary btn-sm w-100">Thêm môn</button>
        </form>
        <div class="form-text mt-2">Muốn nhập theo lớp THPT: đặt đúng tên trong danh sách bên dưới (vd: CĐ Vật lí).</div>
      </div>
    </div>
  </div>
</div>

<!-- NHÓM THEO LỚP -->
<div class="section-title special">
  <i class="bi bi-layout-three-columns"></i> Môn THPT nhập theo từng lớp
  <span class="badge bg-primary ms-1"><?= count($subjects_special) ?></span>
</div>
<p class="text-muted small mb-3">
  CĐ Lịch sử · Địa lí · CĐ Địa lí · Sinh học · CĐ Sinh học · GD&KTPL · Vật lí · CĐ Vật lí · Hoá học · CĐ Hoá học · Tin học · Âm nhạc · Mỹ thuật
</p>

<?php if ($subjects_special):
  foreach ($subjects_special as $subject => $data) render_subject_card($subject, $data);
else: ?>
<div class="alert alert-light border small">
  Chưa có môn nào trong danh sách trên. Thêm môn với đúng tên (vd: <code>CĐ Vật lí</code>, <code>Mỹ thuật</code>) để hiện cột 10A, 10B…
</div>
<?php endif; ?>

<!-- NHÓM THƯỜNG -->
<div class="section-title">
  <i class="bi bi-grid"></i> Các môn còn lại (theo khối)
  <span class="badge bg-secondary ms-1"><?= count($subjects_normal) ?></span>
</div>

<?php if ($subjects_normal):
  foreach ($subjects_normal as $subject => $data) render_subject_card($subject, $data);
else: ?>
<div class="alert alert-light border">Không có môn nào.</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
