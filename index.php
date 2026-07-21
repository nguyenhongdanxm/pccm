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

// ===== KHÁCH (chưa đăng nhập) =====
require_once 'includes/header.php';
?>
<style>
.landing-hero{
  background:linear-gradient(145deg,#0d3a5c 0%,#1F4E79 40%,#2E6DA4 100%);
  color:#fff;border-radius:20px;padding:2.5rem 2rem;text-align:center;
  box-shadow:0 16px 40px rgba(13,58,92,.28);margin-bottom:1.75rem;position:relative;overflow:hidden
}
.landing-hero::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(circle at 20% 20%,rgba(255,255,255,.12),transparent 45%),
             radial-gradient(circle at 80% 70%,rgba(255,193,7,.12),transparent 40%);
  pointer-events:none
}
.landing-hero .logo-icon{font-size:3.2rem;opacity:.95}
.landing-hero h1{font-size:clamp(1.6rem,4vw,2.35rem);font-weight:800;letter-spacing:-.02em;margin:.6rem 0 .35rem;position:relative}
.landing-hero .app-code{display:inline-block;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:.2rem .9rem;font-weight:600;font-size:.85rem;letter-spacing:.06em}
.landing-hero .tagline{opacity:.92;max-width:520px;margin:.75rem auto 0;font-size:1.05rem;position:relative}
.landing-hero .author-block{margin-top:1.25rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,.2);position:relative}
.landing-hero .author-block .by{font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;opacity:.7}
.landing-hero .author-block .name{font-size:1.15rem;font-weight:700}
.landing-hero .author-block .school{font-size:.9rem;opacity:.85}
.cta-card{border:none;border-radius:16px;box-shadow:0 4px 18px rgba(0,0,0,.07);transition:transform .15s,box-shadow .15s;height:100%;text-decoration:none;display:block;color:inherit}
.cta-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,.12);color:inherit}
.cta-card .cta-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:.75rem}
.cta-green .cta-icon{background:#d1e7dd;color:#0f5132}
.cta-blue .cta-icon{background:#cfe2ff;color:#084298}
.feat-panel{border:none;border-radius:16px;box-shadow:0 4px 18px rgba(0,0,0,.06)}
.feat-panel .accordion-button{font-weight:600;color:#1F4E79;background:#fff}
.feat-panel .accordion-button:not(.collapsed){background:#e8f0fe;color:#1F4E79;box-shadow:none}
.feat-panel .accordion-button:focus{box-shadow:none;border-color:transparent}
.feat-panel .feat-icon{width:36px;height:36px;border-radius:10px;background:#e8f0fe;color:#1F4E79;display:inline-flex;align-items:center;justify-content:center;margin-right:.65rem;flex-shrink:0}
.feat-toggle-btn{border-radius:999px;font-weight:600}
</style>

<div class="row justify-content-center">
<div class="col-lg-9 col-xl-8">

<div class="landing-hero">
  <div class="logo-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
  <div class="app-code"><?= e($app['name']) ?> · v<?= e($app['version']) ?></div>
  <h1><?= e($app['full_name']) ?></h1>
  <p class="tagline mb-0"><?= e($app['tagline']) ?></p>
  <p class="mb-0 mt-2" style="opacity:.8">Năm học <?= e($app['year']) ?></p>
  <div class="author-block">
    <div class="by">Thiết kế & phát triển bởi</div>
    <div class="name"><?= e($app['author']) ?></div>
    <div class="school"><?= e($app['school']) ?></div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <a href="<?= BASE_URL ?>ketqua.php" class="cta-card cta-green card">
      <div class="card-body p-4">
        <div class="cta-icon"><i class="bi bi-clipboard-data"></i></div>
        <h5 class="fw-bold mb-1">Xem Kết quả</h5>
        <p class="text-muted small mb-0">Xem các phiên bản phân công — không cần đăng nhập</p>
      </div>
    </a>
  </div>
  <div class="col-md-6">
    <a href="<?= BASE_URL ?>login.php" class="cta-card cta-blue card">
      <div class="card-body p-4">
        <div class="cta-icon"><i class="bi bi-box-arrow-in-right"></i></div>
        <h5 class="fw-bold mb-1">Đăng nhập Quản trị</h5>
        <p class="text-muted small mb-0">Thêm · sửa · rà soát · xuất bảng phân công</p>
      </div>
    </a>
  </div>
</div>

<div class="card feat-panel mb-3">
  <div class="card-body p-3 p-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div>
        <h5 class="mb-0 fw-bold" style="color:#1F4E79"><i class="bi bi-stars"></i> Tính năng nổi bật</h5>
        <div class="text-muted small"><?= count($features) ?> nhóm chức năng · tự cập nhật khi phần mềm có thêm tính năng</div>
      </div>
      <button class="btn btn-outline-primary btn-sm feat-toggle-btn" type="button" data-bs-toggle="collapse" data-bs-target="#featList" aria-expanded="false">
        <i class="bi bi-chevron-down"></i> Xem chi tiết
      </button>
    </div>

    <div class="collapse" id="featList">
      <div class="accordion accordion-flush mt-2" id="accPublic">
      <?php foreach ($features as $idx => $f): ?>
        <div class="accordion-item border-0 border-bottom">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed px-0" type="button" data-bs-toggle="collapse" data-bs-target="#fp<?= $idx ?>">
              <span class="feat-icon"><i class="bi <?= e($f['icon']) ?>"></i></span>
              <?= e($f['title']) ?>
            </button>
          </h2>
          <div id="fp<?= $idx ?>" class="accordion-collapse collapse" data-bs-parent="#accPublic">
            <div class="accordion-body px-0 pt-0">
              <p class="text-muted small mb-2"><?= e($f['desc']) ?></p>
              <ul class="small mb-0">
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

<div class="text-center text-muted small mb-2">
  <i class="bi bi-shield-check"></i> Khách xem Kết quả tự do · Chỉnh sửa cần tài khoản quản trị
</div>

</div>
</div>

<script>
(function(){
  var btn = document.querySelector('[data-bs-target="#featList"]');
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
