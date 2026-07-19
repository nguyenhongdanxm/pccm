<?php
require_once __DIR__ . '/functions.php';
$current = basename($_SERVER['PHP_SELF'], '.php');
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
body{background:#f0f4f8;font-family:'Segoe UI',system-ui,sans-serif}
.navbar{background:var(--primary)!important}
.navbar-brand,.nav-link{color:#fff!important}
.nav-link:hover,.nav-link.active{color:#ffc107!important}
.card{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
.card-header{background:var(--primary);color:#fff;border-radius:12px 12px 0 0!important;font-weight:600}
.btn-primary{background:var(--primary);border-color:var(--primary)}
.btn-primary:hover{background:var(--primary-light);border-color:var(--primary-light)}
.table th{background:#e8f0fe;color:var(--primary);white-space:nowrap}
.stat-card{text-align:center;padding:1.25rem}
.stat-card .number{font-size:2rem;font-weight:700;color:var(--primary)}
.stat-card .label{color:#666;font-size:.9rem}
.badge-periods{background:#198754}
pre.summary-text{background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:1rem;white-space:pre-wrap;font-size:.95rem}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
<div class="container">
<a class="navbar-brand fw-bold" href="<?= BASE_URL ?>index.php"><i class="bi bi-journal-bookmark-fill"></i> PCCM</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="nav">
<ul class="navbar-nav me-auto">
<li class="nav-item"><a class="nav-link <?= $current=='index'?'active':'' ?>" href="<?= BASE_URL ?>index.php"><i class="bi bi-house"></i> Tổng quan</a></li>
<li class="nav-item"><a class="nav-link <?= $current=='them'?'active':'' ?>" href="<?= BASE_URL ?>them.php"><i class="bi bi-plus-circle"></i> Thêm phân công</a></li>
<li class="nav-item"><a class="nav-link <?= $current=='danhsach'?'active':'' ?>" href="<?= BASE_URL ?>danhsach.php"><i class="bi bi-list-ul"></i> Danh sách</a></li>
<li class="nav-item"><a class="nav-link <?= $current=='baocao'?'active':'' ?>" href="<?= BASE_URL ?>baocao.php"><i class="bi bi-bar-chart"></i> Báo cáo</a></li>
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-gear"></i> Quản lý</a>
<ul class="dropdown-menu">
<li><a class="dropdown-item" href="<?= BASE_URL ?>giaovien.php">Giáo viên</a></li>
<li><a class="dropdown-item" href="<?= BASE_URL ?>monhoc.php">Môn học & Số tiết</a></li>
<li><a class="dropdown-item" href="<?= BASE_URL ?>lop.php">Lớp</a></li>
</ul></li>
</ul>
<a href="<?= BASE_URL ?>xuat.php" class="btn btn-outline-light btn-sm"><i class="bi bi-download"></i> Xuất CSV</a>
</div></div></nav>
<div class="container pb-5">
<?php show_flash(); ?>
