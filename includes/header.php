<?php
require_once __DIR__ . '/functions.php';
$current = basename($_SERVER['PHP_SELF'], '.php');
$logged = is_logged_in();
$active_ver = get_version(get_active_version_id());
$pc_pages = ['them','danhsach','doicheo','rasoat','sua'];
$pc_active = in_array($current, $pc_pages, true);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?= BASE_URL ?>">
<title><?= e($page_title ?? 'PCCM') ?> – Phân công Chuyên môn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#1F4E79;--primary-light:#2E6DA4}
body{background:#f0f4f8;font-family:'Segoe UI',system-ui,sans-serif;color:#212529}
.navbar{background:var(--primary)!important}
.navbar .navbar-brand,.navbar .nav-link{color:#fff!important}
.navbar .nav-link:hover,.navbar .nav-link.active{color:#ffc107!important}
.navbar .dropdown-menu{border:none;box-shadow:0 8px 24px rgba(0,0,0,.12);border-radius:10px}
.navbar .dropdown-item{font-weight:500;padding:.55rem 1rem}
.navbar .dropdown-item:hover{background:#e8f0fe;color:var(--primary)}
.navbar .dropdown-item.active{background:var(--primary);color:#fff}
.nav-tabs{border-bottom:2px solid #dee2e6}
.nav-tabs .nav-link,.nav-pills .nav-link{color:#1F4E79!important;font-weight:600}
.nav-tabs .nav-link{background:#fff;border:1px solid transparent}
.nav-tabs .nav-link:hover{color:#0d3a5c!important;background:#e8f0fe;border-color:#dee2e6 #dee2e6 #fff}
.nav-tabs .nav-link.active{color:#fff!important;background:var(--primary)!important;border-color:var(--primary)!important}
.nav-pills .nav-link{background:#fff;border:1px solid #dee2e6}
.nav-pills .nav-link:hover{background:#e8f0fe}
.nav-pills .nav-link.active{color:#fff!important;background:var(--primary)!important;border-color:var(--primary)}
.card{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
.card-header{background:var(--primary);color:#fff!important;border-radius:12px 12px 0 0!important;font-weight:600}
.card-header.bg-success{background:#198754!important;color:#fff!important}
.card-header.bg-info{background:#0dcaf0!important;color:#053b4a!important}
.card-header.bg-secondary{background:#6c757d!important;color:#fff!important}
.card-header.bg-warning{background:#ffc107!important;color:#664d03!important}
.card-header.bg-danger{background:#dc3545!important;color:#fff!important}
.btn-primary{background:var(--primary);border-color:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-light);border-color:var(--primary-light);color:#fff}
.btn-info{color:#053b4a!important}
.table th{background:#e8f0fe;color:var(--primary);white-space:nowrap}
.stat-card{text-align:center;padding:1.25rem}
.stat-card .number{font-size:2rem;font-weight:700;color:var(--primary)}
.stat-card .label{color:#555;font-size:.9rem}
.chip{display:inline-flex;align-items:center;gap:4px;background:#e8f0fe;color:#1F4E79;border:1px solid #b6d0f0;border-radius:20px;padding:2px 8px 2px 10px;margin:2px;font-size:.85rem;font-weight:500}
.chip-role{background:#d1f0f7;border-color:#9ad8e8;color:#055160}
.chip .chip-x{border:none;background:#dc3545;color:#fff;border-radius:50%;width:18px;height:18px;line-height:16px;font-size:12px;padding:0;cursor:pointer}
.chip .chip-x:hover{background:#bb2d3b}
.version-bar{background:#fff8e1;border:1px solid #ffc107;border-radius:8px;padding:.5rem 1rem;margin-bottom:1rem;font-size:.95rem;color:#664d03}
.version-bar a{color:#1F4E79;font-weight:600}
.diff-over{color:#dc3545;font-weight:700}
.diff-under{color:#fd7e14;font-weight:700}
.diff-ok{color:#198754;font-weight:600}
.board-row{border-bottom:1px solid #e9ecef;padding:.6rem 0}
.board-row:last-child{border-bottom:none}
.warn-box{background:#fff3cd;border-left:4px solid #ffc107;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;color:#664d03}
.danger-box{background:#f8d7da;border-left:4px solid #dc3545;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;color:#58151c}
.info-box{background:#e8f0fe;border-left:4px solid var(--primary);border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;color:#1F4E79}
.pccm-toast{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;align-items:flex-start;gap:10px;min-width:260px;max-width:360px;padding:12px 14px;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.18);font-size:.95rem;line-height:1.4;animation:pccmToastIn .25s ease-out}
.pccm-toast-success{background:#d1e7dd;color:#0a3622;border:1px solid #a3cfbb}
.pccm-toast-danger{background:#f8d7da;color:#58151c;border:1px solid #f1aeb5}
.pccm-toast-warning{background:#fff3cd;color:#664d03;border:1px solid #ffe69c}
.pccm-toast-info{background:#cff4fc;color:#055160;border:1px solid #9eeaf9}
.pccm-toast-icon{font-size:1.2rem;flex-shrink:0;margin-top:1px}
.pccm-toast-msg{flex:1}
.pccm-toast-close{border:none;background:transparent;font-size:1.25rem;line-height:1;cursor:pointer;opacity:.6;padding:0 2px;color:inherit}
.pccm-toast-close:hover{opacity:1}
@keyframes pccmToastIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
@media (max-width:576px){.pccm-toast{left:12px;right:12px;bottom:12px;max-width:none;min-width:0}}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
<div class="container">
<a class="navbar-brand fw-bold" href="<?= BASE_URL ?>index.php"><i class="bi bi-journal-bookmark-fill"></i> PCCM</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="nav">
<ul class="navbar-nav me-auto">
<?php if ($logged): ?>
<li class="nav-item"><a class="nav-link <?= $current=='index'?'active':'' ?>" href="<?= BASE_URL ?>index.php"><i class="bi bi-house"></i> Tổng quan</a></li>
<li class="nav-item"><a class="nav-link <?= in_array($current,['them','doicheo','rasoat'])?'active':'' ?>" href="<?= BASE_URL ?>them.php"><i class="bi bi-clipboard-check"></i> Phân công</a></li>
<li class="nav-item"><a class="nav-link <?= $current=='danhsach'?'active':'' ?>" href="<?= BASE_URL ?>danhsach.php"><i class="bi bi-list-ul"></i> Danh sách</a></li>
<li class="nav-item"><a class="nav-link <?= in_array($current,['ketqua','baocao','thongke'],true)?'active':'' ?>" href="<?= BASE_URL ?>ketqua.php"><i class="bi bi-clipboard-data"></i> Kết quả</a></li>
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-gear"></i> Quản lý</a>
<ul class="dropdown-menu">
<li><a class="dropdown-item" href="<?= BASE_URL ?>giaovien.php">Giáo viên</a></li>
<li><a class="dropdown-item" href="<?= BASE_URL ?>monhoc.php">Môn học & Số tiết</a></li>
<li><a class="dropdown-item" href="<?= BASE_URL ?>lop.php">Lớp</a></li>
<li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item" href="<?= BASE_URL ?>kiemnhiem.php">Chức vụ kiêm nhiệm & Số tiết</a></li>
</ul></li>
<?php else: ?>
<li class="nav-item"><a class="nav-link <?= $current=='tracuu'?'active':'' ?>" href="<?= BASE_URL ?>tracuu.php"><i class="bi bi-search"></i> Tra cứu phân công</a></li>
<?php endif; ?>
</ul>
<?php if ($logged): ?>
<a href="<?= BASE_URL ?>xuat_bang.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-printer"></i> Xuất bảng</a>
<a href="<?= BASE_URL ?>logout.php" class="btn btn-warning btn-sm text-dark fw-semibold"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
<?php else: ?>
<a href="<?= BASE_URL ?>login.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a>
<?php endif; ?>
</div></div></nav>
<div class="container pb-5">
<?php show_flash(); ?>
<?php if ($logged && $active_ver && in_array($current, ['them','danhsach','index','sua','doicheo','rasoat'])): ?>
<div class="version-bar">
    <i class="bi bi-folder2-open"></i>
    Đang làm việc trên: <strong><?= e($active_ver['name']) ?></strong>
    (ngày <?= e($active_ver['date'] ?? '') ?>)
    · <a href="<?= BASE_URL ?>ketqua.php">Đổi phiên bản</a>
</div>
<?php endif; ?>
