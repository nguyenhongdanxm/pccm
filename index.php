<?php
$page_title = 'Trang chủ';
require_once 'includes/functions.php';

// Đã đăng nhập → vào Tổng quan quản trị
if (is_logged_in()) {
    require_once 'includes/header.php';
    $teachers = get_teachers();
    $subjects = get_subjects();
    $classes = get_classes();
    $assignments = get_assignments();
    $teacher_load = [];
    foreach ($assignments as $a) {
        $t = $a['teacher'];
        $teacher_load[$t] = ($teacher_load[$t] ?? 0) + floatval($a['periods'] ?? 0);
    }
    arsort($teacher_load);
    $max_load = $teacher_load ? max($teacher_load) : 1;
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-speedometer2"></i> Tổng quan Phân công 2026-2027</h3>
    </div>
    <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="number"><?= count($teachers) ?></div><div class="label">Giáo viên</div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="number"><?= count($subjects) ?></div><div class="label">Môn học</div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="number"><?= count($classes) ?></div><div class="label">Lớp</div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="number"><?= count($assignments) ?></div><div class="label">Phân công</div></div></div>
    </div>
    <?php if ($teacher_load): ?>
    <div class="card">
    <div class="card-header"><i class="bi bi-people"></i> Tải dạy theo giáo viên</div>
    <div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
    <thead><tr><th>#</th><th>Giáo viên</th><th class="text-end">Tổng tiết</th><th style="width:40%">Biểu đồ</th></tr></thead>
    <tbody>
    <?php $i=1; foreach ($teacher_load as $teacher => $total): ?>
    <tr>
    <td><?= $i++ ?></td>
    <td><?= e($teacher) ?></td>
    <td class="text-end fw-bold"><?= number_format($total,1) ?></td>
    <td><div class="progress" style="height:18px"><div class="progress-bar bg-primary" style="width:<?= round($total/$max_load*100) ?>%"><?= number_format($total,1) ?></div></div></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div></div></div>
    <?php else: ?>
    <div class="alert alert-info"><i class="bi bi-info-circle"></i> Chưa có phân công nào. <a href="<?= BASE_URL ?>them.php" class="alert-link">Thêm phân công ngay</a></div>
    <?php endif; ?>
    <?php require_once 'includes/footer.php';
    exit;
}

// Chưa đăng nhập → trang chào mừng
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
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Thêm phân công:</strong> Chọn giáo viên + môn + lớp → số tiết tự động</li>
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Thêm nhanh nhiều lớp:</strong> Gán cùng lúc nhiều lớp cho một giáo viên</li>
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Danh sách & Lọc:</strong> Xem, lọc, xóa phân công</li>
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Báo cáo tổng hợp:</strong> Xem tải dạy theo từng giáo viên</li>
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Quản lý danh mục:</strong> Giáo viên, Môn học & số tiết chuẩn, Lớp</li>
        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Xuất CSV:</strong> Tải file Excel để in / lưu trữ</li>
    </ul>

    <div class="alert alert-light border mt-3 mb-0">
        <small class="text-muted">
            <i class="bi bi-shield-lock"></i>
            <strong>Phân quyền:</strong> Ai cũng xem được <em>Báo cáo</em>.
            Các chức năng chỉnh sửa cần đăng nhập quản trị.
        </small>
    </div>
</div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <a href="<?= BASE_URL ?>baocao.php" class="btn btn-success btn-lg w-100 py-3 shadow-sm">
            <i class="bi bi-bar-chart-fill d-block mb-1" style="font-size:1.8rem"></i>
            <strong>Xem Báo cáo</strong>
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
