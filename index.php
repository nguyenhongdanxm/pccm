<?php
$page_title = 'Trang chủ';
require_once 'includes/functions.php';

if (is_logged_in()) {
    require_once 'includes/header.php';
    $teachers = get_teachers_sorted();
    $subjects = get_subjects();
    $classes = get_classes();
    $assignments = get_assignments();
    $role_assignments = get_role_assignments();
    $loads = get_teacher_loads();

    uasort($loads, fn($a, $b) => $b['total'] <=> $a['total']);
    $max_load = $loads ? max(array_column($loads, 'total')) : 1;
    if ($max_load <= 0) $max_load = 1;

    $total_periods = array_sum(array_column($loads, 'total'));
    $total_day = array_sum(array_column($loads, 'day'));
    $total_role = array_sum(array_column($loads, 'role'));
    $active = get_version(get_active_version_id());
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-speedometer2"></i> Tổng quan<?= $active ? ' – ' . e($active['name']) : '' ?></h3>
    </div>
    <div class="row g-3 mb-4">
    <div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= count($teachers) ?></div><div class="label">Giáo viên</div></div></div>
    <div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= count($classes) ?></div><div class="label">Lớp</div></div></div>
    <div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= count($assignments) ?></div><div class="label">PC dạy môn</div></div></div>
    <div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= count($role_assignments) ?></div><div class="label">Kiêm nhiệm</div></div></div>
    <div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= number_format($total_day, 1) ?></div><div class="label">Tiết dạy</div></div></div>
    <div class="col-6 col-md-2"><div class="card stat-card"><div class="number"><?= number_format($total_role, 1) ?></div><div class="label">Tiết kiêm nhiệm</div></div></div>
    </div>

    <?php if ($loads): ?>
    <div class="card">
    <div class="card-header"><i class="bi bi-people"></i> Tải theo giáo viên (dạy + kiêm nhiệm = <?= number_format($total_periods, 1) ?> tiết)</div>
    <div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
    <thead><tr>
        <th>#</th><th>Giáo viên</th>
        <th class="text-end">Tiết dạy</th>
        <th class="text-end">Kiêm nhiệm</th>
        <th class="text-end">Tổng tiết</th>
        <th class="text-center">Số lớp</th>
        <th style="width:28%">Biểu đồ</th>
    </tr></thead>
    <tbody>
    <?php $i=1; foreach ($loads as $teacher => $row): ?>
    <tr>
    <td><?= $i++ ?></td>
    <td><?= e($teacher) ?></td>
    <td class="text-end"><?= number_format($row['day'], 1) ?></td>
    <td class="text-end text-info"><?= number_format($row['role'], 1) ?></td>
    <td class="text-end fw-bold"><?= number_format($row['total'], 1) ?></td>
    <td class="text-center"><?= $row['class_count'] ?></td>
    <td><div class="progress" style="height:18px"><div class="progress-bar bg-primary" style="width:<?= round($row['total']/$max_load*100) ?>%"><?= number_format($row['total'],1) ?></div></div></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div></div></div>
    <?php else: ?>
    <div class="alert alert-info"><i class="bi bi-info-circle"></i> Chưa có phân công nào. <a href="<?= BASE_URL ?>them.php" class="alert-link">Thêm phân công ngay</a></div>
    <?php endif; ?>
    <?php require_once 'includes/footer.php';
    exit;
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center mt-3">
<div class="col-lg-8">

<div class="card shadow-sm mb-4">
<div class="card-body p-4 p-md-5">
    <div class="text-center mb-4">
        <i class="bi bi-journal-bookmark-fill" style="font-size:3rem;color:#1F4E79"></i>
        <h2 class="mt-2 mb-1" style="color:#1F4E79">Ứng dụng Phân công Chuyên môn</h2>
        <p class="text-muted mb-0">Năm học 2026 – 2027</p>
    </div>
    <hr>
    <h5 class="mb-3"><i class="bi bi-info-circle text-primary"></i> Giới thiệu chức năng</h5>
    <ul class="list-unstyled ms-1">
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Phiên bản phân công:</strong> Lần 1, lần 2… kèm ngày tháng</li>
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Thêm phân công:</strong> Dạy môn + kiêm nhiệm, số tiết tự động</li>
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Kết quả:</strong> Xem từng phiên bản, tổng tiết theo GV</li>
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Sao chép phiên bản:</strong> Tạo lần mới từ dữ liệu lần trước</li>
    </ul>
    <div class="alert alert-light border mt-3 mb-0">
        <small class="text-muted"><i class="bi bi-shield-lock"></i> <strong>Phân quyền:</strong> Ai cũng xem được <em>Kết quả</em>. Chỉnh sửa cần đăng nhập quản trị.</small>
    </div>
</div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <a href="<?= BASE_URL ?>ketqua.php" class="btn btn-success btn-lg w-100 py-3 shadow-sm">
            <i class="bi bi-clipboard-data d-block mb-1" style="font-size:1.8rem"></i>
            <strong>Xem Kết quả</strong>
            <div class="small fw-normal opacity-75">Không cần đăng nhập</div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="<?= BASE_URL ?>login.php" class="btn btn-primary btn-lg w-100 py-3 shadow-sm">
            <i class="bi bi-box-arrow-in-right d-block mb-1" style="font-size:1.8rem"></i>
            <strong>Đăng nhập Quản trị</strong>
            <div class="small fw-normal opacity-75">Thêm / Sửa / Xóa phân công</div>
        </a>
    </div>
</div>

</div>
</div>

<?php require_once 'includes/footer.php'; ?>
