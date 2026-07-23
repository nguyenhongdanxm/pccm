<?php
$page_title = 'Quản lý Môn học';
require_once 'includes/functions.php';
require_login();

/** Khóa nhập số tiết: THCS theo khối, THPT theo từng lớp */
function period_input_keys() {
    $keys = [];
    foreach (['6', '7', '8', '9'] as $g) {
        $keys[] = ['key' => $g, 'label' => 'Khối ' . $g, 'group' => 'THCS'];
    }
    $classes = get_classes();
    foreach ($classes as $c) {
        $g = intval(get_grade($c));
        if ($g >= 10) {
            $keys[] = ['key' => $c, 'label' => $c, 'group' => 'THPT'];
        }
    }
    // Nếu chưa có lớp THPT trong danh mục, vẫn hiện cột mặc định
    $has_thpt = false;
    foreach ($keys as $k) if ($k['group'] === 'THPT') { $has_thpt = true; break; }
    if (!$has_thpt) {
        foreach (['10A', '10B', '11A', '11B', '12A', '12B'] as $c) {
            $keys[] = ['key' => $c, 'label' => $c, 'group' => 'THPT'];
        }
    }
    return $keys;
}

/** Lấy giá trị hiển thị: ưu tiên đúng lớp, fallback khối (dữ liệu cũ) */
function period_display_value($grades_data, $key) {
    if (isset($grades_data[$key]) && $grades_data[$key] !== '' && $grades_data[$key] !== null) {
        return $grades_data[$key];
    }
    // Fallback: 10A → 10
    $grade = preg_replace('/[^0-9]/', '', $key);
    if ($grade !== '' && isset($grades_data[$grade]) && $grades_data[$grade] !== '' && $grades_data[$grade] !== null) {
        return $grades_data[$grade];
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjects = get_subjects();
    $input_keys = period_input_keys();

    if (($_POST['action'] ?? '') === 'update') {
        $subject = $_POST['subject'] ?? '';
        if (isset($subjects[$subject])) {
            $new = [];
            foreach ($input_keys as $ik) {
                $k = $ik['key'];
                $field = 'p_' . $k;
                $val = trim($_POST[$field] ?? '');
                if ($val !== '' && is_numeric($val)) {
                    $new[$k] = floatval($val);
                }
            }
            $subjects[$subject] = $new;
            save_json(SUBJECTS_FILE, $subjects);
            flash("Đã cập nhật số tiết môn $subject", 'success');
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
$input_keys = period_input_keys();
$thcs_keys = array_values(array_filter($input_keys, fn($k) => $k['group'] === 'THCS'));
$thpt_keys = array_values(array_filter($input_keys, fn($k) => $k['group'] === 'THPT'));
?>

<h3 class="mb-3"><i class="bi bi-book"></i> Quản lý Môn học & Số tiết chuẩn</h3>

<div class="alert alert-info small">
  <i class="bi bi-info-circle"></i>
  <strong>THCS:</strong> số tiết theo <em>khối</em> (áp dụng mọi lớp cùng khối) ·
  <strong>THPT:</strong> số tiết theo <em>từng lớp</em> (10A, 10B… khác nhau vì môn / chuyên đề khác nhau) ·
  Dùng để <strong>tự động điền</strong> khi chọn Môn + Lớp và để <strong>Thống kê</strong> so sánh chuẩn.
</div>

<div class="row mb-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">Thêm môn mới</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="add">
          <div class="mb-3"><input type="text" name="name" class="form-control" placeholder="Tên môn học" required></div>
          <button type="submit" class="btn btn-primary w-100">Thêm môn</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php foreach ($subjects as $subject => $grades_data): ?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><?= e($subject) ?></span>
    <form method="post" class="d-inline" onsubmit="return confirm('Xóa môn <?= e($subject) ?>?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="name" value="<?= e($subject) ?>">
      <button class="btn btn-sm btn-outline-light" type="submit"><i class="bi bi-trash"></i></button>
    </form>
  </div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="subject" value="<?= e($subject) ?>">

      <div class="mb-2"><span class="badge bg-secondary">THCS — theo khối</span></div>
      <div class="row g-2 mb-3">
        <?php foreach ($thcs_keys as $ik): ?>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small mb-0 fw-semibold"><?= e($ik['label']) ?></label>
          <input type="number" name="p_<?= e($ik['key']) ?>" class="form-control form-control-sm"
            step="0.01" min="0" max="20"
            value="<?= e(period_display_value($grades_data, $ik['key'])) ?>">
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mb-2"><span class="badge bg-primary">THPT — theo lớp (môn / chuyên đề có thể khác nhau)</span></div>
      <div class="row g-2 mb-3">
        <?php
        $cur_grade = '';
        foreach ($thpt_keys as $ik):
          $g = get_grade($ik['key']);
          if ($g !== $cur_grade):
            $cur_grade = $g;
        ?>
        <div class="col-12"><div class="text-muted small mt-1">Khối <?= e($g) ?></div></div>
        <?php endif; ?>
        <div class="col-6 col-md-2 col-lg-2">
          <label class="form-label small mb-0 fw-semibold text-primary"><?= e($ik['label']) ?></label>
          <input type="number" name="p_<?= e($ik['key']) ?>" class="form-control form-control-sm"
            step="0.01" min="0" max="20"
            value="<?= e(period_display_value($grades_data, $ik['key'])) ?>"
            placeholder="tiết">
        </div>
        <?php endforeach; ?>
      </div>

      <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save"></i> Lưu <?= e($subject) ?></button>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php if (!$subjects): ?>
<div class="alert alert-warning">Chưa có môn học nào.</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
