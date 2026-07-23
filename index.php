<?php
$page_title = 'Trang chủ';
require_once 'includes/functions.php';
require_once 'includes/features.php';
$app = get_app_info();
$features = get_app_features();

if (is_logged_in()) {
    require_once 'includes/header.php';
    $teachers = get_teachers_sorted();
    $classes = get_classes();
    $assignments = get_assignments();
    $role_assignments = get_role_assignments();
    $loads = get_teacher_loads();
    uasort($loads, fn($a, $b) => $b['total'] <=> $a['total']);
    $max_load = $loads ? max(array_column($loads, 'total')) : 1;
    if ($max_load <= 0) $max_load = 1;
    $total_day = array_sum(array_column($loads, 'day'));
    $total_role = array_sum(array_column($loads, 'role'));
    $total_periods = $total_day + $total_role;
    $active = get_version(get_active_version_id());
    ?>
<style>
.hero-admin{background:linear-gradient(135deg,#1F4E79 0%,#2E6DA4 55%,#3d8fd1 100%);color:#fff;border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;box-shadow:0 8px 28px rgba(31,78,121,.25)}
.hero-admin h1{font-size:1.75rem;font-weight:700;margin:0}
.hero-admin .sub{opacity:.9;font-size:.95rem}
.quick-link{display:block;background:#fff;border-radius:12px;padding:1rem 1.1rem;text-decoration:none;color:#1F4E79;box-shadow:0 2px 10px rgba(0,0,0,.06);transition:transform .15s,box-shadow .15s;height:100%}
.quick-link:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,.1);color:#1F4E79}
.quick-link i{font-size:1.6rem;display:block;margin-bottom:.35rem}
.feat-summary .accordion-button{font-weight:600;color:#1F4E79}
.feat-summary .accordion-button:not(.collapsed){background:#e8f0fe;color:#1F4E79;box-shadow:none}
</style>

<div class="hero-admin">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
<div>
<div class="small text-uppercase opacity-75 mb-1"><?= e($app['name']) ?> · v<?= e($app['version']) ?></div>
<h1><?= e($app['full_name']) ?></h1>
<div class="sub mt-1">Năm học <?= e($app['year']) ?><?= $active ? ' · Đang làm: <strong>'.e($active['name']).'</strong>' : '' ?></div>
<div class="sub mt-2" style="opacity:.85"><i class="bi bi-person-badge"></i> <?= e($app['author']) ?></div>
</div>
<div class="d-flex flex-wrap gap-2">
<a href="<?= BASE_URL ?>them.php" class="btn btn-warning fw-semibold text-dark"><i class="bi bi-clipboard-check"></i> Phân công</a>
<a href="<?= BASE_URL ?>ketqua.php" class="btn btn-outline-light"><i class="bi bi-folder2-open"></i> Kết quả</a>
</div>
</div>
</div>

<div class="row g-3 mb-4">
<div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= count($teachers) ?></div><div class="label">Giáo viên</div></div></div>
<div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= count($classes) ?></div><div class="label">Lớp</div></div></div>
<div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= count($assignments) ?></div><div class="label">PC dạy môn</div></div></div>
<div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= count($role_assignments) ?></div><div class="label">Kiêm nhiệm</div></div></div>
<div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= number_format($total_day,1) ?></div><div class="label">Tiết dạy</div></div></div>
<div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= number_format($total_role,1) ?></div><div class="label">Tiết KN</div></div></div>
</div>

<div class="row g-3 mb-4">
<div class="col-6 col-md-3"><a class="quick-link" href="<?= BASE_URL ?>them.php"><i class="bi bi-plus-circle text-primary"></i><strong>Phân công</strong><div class="small text-muted">Thêm · bảng · đổi chéo · rà soát</div></a></div>
<div class="col-6 col-md-3"><a class="quick-link" href="<?= BASE_URL ?>danhsach.php"><i class="bi bi-list-ul text-primary"></i><strong>Danh sách</strong><div class="small text-muted">Toàn bộ phân công</div></a></div>
<div class="col-6 col-md-3"><a class="quick-link" href="<?= BASE_URL ?>giaovien.php"><i class="bi bi-people text-primary"></i><strong>Giáo viên</strong><div class="small text-muted">Chuyên môn · tổ · cấp</div></a></div>
<div class="col-6 col-md-3"><a class="quick-link" href="<?= BASE_URL ?>xuat_bang.php"><i class="bi bi-printer text-primary"></i><strong>Xuất bảng</strong><div class="small text-muted">In phân công</div></a></div>
</div>

<?php if ($loads): ?>
<div class="card mb-4">
<div class="card-header"><i class="bi bi-bar-chart"></i> Tải theo giáo viên (tổng <?= number_format($total_periods,1) ?> tiết)</div>
<div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>#</th><th>Giáo viên</th><th class="text-end">Dạy</th><th class="text-end">KN</th><th class="text-end">Tổng</th><th class="text-center">Lớp</th><th style="width:26%">Biểu đồ</th></tr></thead>
<tbody>
<?php $i=1; foreach ($loads as $teacher => $row): ?>
<tr>
<td><?= $i++ ?></td><td><?= e($teacher) ?></td>
<td class="text-end"><?= number_format($row['day'],1) ?></td>
<td class="text-end text-info"><?= number_format($row['role'],1) ?></td>
<td class="text-end fw-bold"><?= number_format($row['total'],1) ?></td>
<td class="text-center"><?= $row['class_count'] ?></td>
<td><div class="progress" style="height:16px"><div class="progress-bar" style="width:<?= round($row['total']/$max_load*100) ?>%"><?= number_format($row['total'],1) ?></div></div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle"></i> Chưa có phân công. <a href="<?= BASE_URL ?>them.php" class="alert-link">Bắt đầu phân công</a></div>
<?php endif; ?>

<div class="card feat-summary">
<div class="card-header d-flex justify-content-between align-items-center">
<span><i class="bi bi-stars"></i> Tính năng phần mềm</span>
<button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#featAdmin">Xem / ẩn</button>
</div>
<div class="collapse" id="featAdmin">
<div class="card-body">
<div class="accordion accordion-flush" id="accAdmin">
<?php foreach ($features as $idx => $f): ?>
<div class="accordion-item">
<h2 class="accordion-header">
<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#fa<?= $idx ?>">
<i class="bi <?= e($f['icon']) ?> me-2"></i> <?= e($f['title']) ?>
</button></h2>
<div id="fa<?= $idx ?>" class="accordion-collapse collapse" data-bs-parent="#accAdmin">
<div class="accordion-body small">
<p class="mb-2 text-muted"><?= e($f['desc']) ?></p>
<ul class="mb-0"><?php foreach ($f['items'] as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul>
</div></div></div>
<?php endforeach; ?>
</div>
<p class="text-muted small mb-0 mt-2">Danh sách cập nhật tự động từ cấu hình phần mềm (v<?= e($app['version']) ?>).</p>
</div></div></div>

<?php require_once 'includes/footer.php';
    exit;
}

// ===== KHÁCH (chưa đăng nhập) – giao diện sạch, chuyên nghiệp =====
require_once 'includes/header.php';
?>
<style>
.home-wrap{max-width:640px;margin:2.5rem auto 2rem;text-align:center;padding:0 1rem}
.home-title{
  font-size:clamp(1.55rem,4vw,2.1rem);
  font-weight:700;
  color:#1F4E79;
  margin:0 0 .65rem;
  line-height:1.3;
  letter-spacing:-.01em;
}
.home-author{
  color:#495057;
  font-size:1rem;
  font-weight:500;
  margin:0 0 2rem;
  line-height:1.5;
  /* không nền, không bo góc, không viền */
  background:none;
  border:none;
  padding:0;
}
.home-author i{color:#1F4E79;margin-right:.35rem}
.home-actions{display:flex;flex-wrap:wrap;justify-content:center;gap:.75rem;margin-bottom:2.25rem}
.home-actions .btn{
  min-width:180px;
  font-weight:600;
  padding:.65rem 1.25rem;
  border-radius:8px;
}
.home-features{text-align:left;border-top:1px solid #dee2e6;padding-top:1.25rem}
.home-features .feat-head{
  display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.5rem
}
.home-features .feat-head h2{
  font-size:1.1rem;font-weight:700;color:#1F4E79;margin:0
}
.home-features .feat-head .hint{font-size:.85rem;color:#6c757d}
.home-features .accordion-button{
  font-weight:600;color:#1F4E79;background:transparent;box-shadow:none;padding:.7rem 0
}
.home-features .accordion-button:not(.collapsed){background:transparent;color:#1F4E79;box-shadow:none}
.home-features .accordion-button:focus{box-shadow:none;border-color:transparent}
.home-features .accordion-item{border:none;border-bottom:1px solid #eee;background:transparent}
.home-features .accordion-body{padding:.25rem 0 .85rem;font-size:.9rem}
.home-features .feat-ico{color:#1F4E79;margin-right:.5rem}
</style>

<div class="home-wrap">

  <h1 class="home-title">Ứng dụng Phân công chuyên môn</h1>
  <p class="home-author"><i class="bi bi-person-badge"></i>Thiết kế bởi thầy giáo Nguyễn Hồng Dân</p>

  <div class="home-actions">
    <a href="<?= BASE_URL ?>ketqua.php" class="btn btn-success">
      <i class="bi bi-clipboard-data"></i> Xem phân công
    </a>
    <a href="<?= BASE_URL ?>login.php" class="btn btn-primary">
      <i class="bi bi-box-arrow-in-right"></i> Đăng nhập quản trị
    </a>
  </div>

  <div class="home-features">
    <div class="feat-head">
      <div>
        <h2><i class="bi bi-stars"></i> Tính năng nổi bật</h2>
        <div class="hint"><?= count($features) ?> nhóm · tự cập nhật khi có chức năng mới</div>
      </div>
      <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#featList" aria-expanded="false" id="featToggle">
        <i class="bi bi-chevron-down"></i> Xem chi tiết
      </button>
    </div>

    <div class="collapse" id="featList">
      <div class="accordion accordion-flush" id="accPublic">
      <?php foreach ($features as $idx => $f): ?>
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fp<?= $idx ?>">
              <i class="bi <?= e($f['icon']) ?> feat-ico"></i><?= e($f['title']) ?>
            </button>
          </h2>
          <div id="fp<?= $idx ?>" class="accordion-collapse collapse" data-bs-parent="#accPublic">
            <div class="accordion-body text-muted">
              <p class="mb-2"><?= e($f['desc']) ?></p>
              <ul class="mb-0 ps-3">
                <?php foreach ($f['items'] as $it): ?><li class="mb-1"><?= e($it) ?></li><?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<script>
(function(){
  var btn = document.getElementById('featToggle');
  var box = document.getElementById('featList');
  if (!btn || !box) return;
  box.addEventListener('shown.bs.collapse', function(){
    btn.innerHTML = '<i class="bi bi-chevron-up"></i> Thu gọn';
  });
  box.addEventListener('hidden.bs.collapse', function(){
    btn.innerHTML = '<i class="bi bi-chevron-down"></i> Xem chi tiết';
  });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
