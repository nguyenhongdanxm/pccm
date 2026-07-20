<?php
require_once 'includes/functions.php';
require_login();

$vid = $_GET['v'] ?? get_active_version_id();
$ver = get_version($vid);
if (!$ver) { $vid = get_active_version_id(); $ver = get_version($vid); }
$rows = get_export_rows($vid);
$f_level = $_GET['level'] ?? '';
if ($f_level) $rows = array_values(array_filter($rows, fn($r) => $r['level'] === $f_level));

$title_lan = $ver['name'] ?? 'Phân công';
$date_str = $ver['date'] ?? date('Y-m-d');
// Format ngày d/m/Y
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_str, $m)) {
    $date_display = $m[3] . '/' . $m[2] . '/' . $m[1];
} else {
    $date_display = $date_str;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Bảng phân công – <?= htmlspecialchars($title_lan) ?></title>
<style>
@page { size: A4 landscape; margin: 12mm; }
body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; margin: 0; padding: 16px; }
.no-print { margin-bottom: 16px; }
.header-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
.header-left { text-align: center; line-height: 1.35; }
.header-left .so { text-transform: uppercase; font-size: 11pt; }
.header-left .truong { font-weight: bold; text-transform: uppercase; font-size: 12pt; text-decoration: underline; }
.main-title { text-align: center; margin: 16px 0 12px; }
.main-title h1 { font-size: 16pt; margin: 0 0 4px; text-transform: uppercase; letter-spacing: .5px; }
.main-title .sub { font-size: 12pt; }
table.pc { width: 100%; border-collapse: collapse; font-size: 11pt; }
table.pc th, table.pc td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
table.pc th { background: #f0f0f0; text-align: center; font-weight: bold; }
table.pc td.num { text-align: center; }
table.pc td.right { text-align: right; }
.sign { display: flex; justify-content: space-between; margin-top: 28px; page-break-inside: avoid; }
.sign .box { width: 40%; text-align: center; }
.sign .muted { font-style: italic; font-size: 11pt; }
@media print {
  .no-print { display: none !important; }
  body { padding: 0; }
}
</style>
</head>
<body>

<div class="no-print">
  <a href="<?= BASE_URL ?>ketqua.php?v=<?= urlencode($vid) ?>" style="margin-right:8px">← Kết quả</a>
  <button onclick="window.print()" style="padding:6px 14px;cursor:pointer">🖨 In / Lưu PDF</button>
  <span style="margin-left:12px">Lọc:</span>
  <a href="?v=<?= urlencode($vid) ?>">Tất cả</a> |
  <a href="?v=<?= urlencode($vid) ?>&level=THCS">THCS</a> |
  <a href="?v=<?= urlencode($vid) ?>&level=THPT">THPT</a>
</div>

<div class="header-row">
  <div class="header-left">
    <div class="so"><?= htmlspecialchars(SCHOOL_SO) ?></div>
    <div class="truong"><?= htmlspecialchars(SCHOOL_NAME) ?></div>
  </div>
  <div style="width:40%"></div>
</div>

<div class="main-title">
  <h1>PHÂN CÔNG CHUYÊN MÔN <?= htmlspecialchars(mb_strtoupper($title_lan, 'UTF-8')) ?></h1>
  <div class="sub">(Ngày <?= htmlspecialchars($date_display) ?>)</div>
  <?php if ($f_level): ?><div class="sub">Khối: <?= htmlspecialchars($f_level) ?></div><?php endif; ?>
</div>

<table class="pc">
<thead>
<tr>
  <th style="width:4%">STT</th>
  <th style="width:16%">Họ tên</th>
  <th style="width:28%">Môn dạy</th>
  <th style="width:18%">Kiêm nhiệm</th>
  <th style="width:8%">Tổng giờ dạy</th>
  <th style="width:10%">Tổng giờ kiêm nhiệm</th>
  <th style="width:8%">Tổng số giờ</th>
  <th style="width:8%">Định mức</th>
</tr>
</thead>
<tbody>
<?php $stt = 0; foreach ($rows as $r): $stt++; ?>
<tr>
  <td class="num"><?= $stt ?></td>
  <td><?= htmlspecialchars($r['name']) ?><?php if (!empty($r['level'])): ?> <small>(<?= htmlspecialchars($r['level']) ?>)</small><?php endif; ?></td>
  <td><?= htmlspecialchars($r['mon_day']) ?></td>
  <td><?= htmlspecialchars($r['kiem_nhiem']) ?></td>
  <td class="right"><?= number_format($r['day'], 1) ?></td>
  <td class="right"><?= number_format($r['role'], 1) ?></td>
  <td class="right"><strong><?= number_format($r['total'], 1) ?></strong></td>
  <td class="num"><?= (int)$r['quota'] ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?>
<tr><td colspan="8" style="text-align:center">Chưa có dữ liệu</td></tr>
<?php endif; ?>
</tbody>
</table>

<div class="sign">
  <div class="box">
    <div class="muted">Ngày ...... tháng ...... năm ......</div>
    <div><strong>NGƯỜI LẬP</strong></div>
    <div class="muted">(Ký, ghi rõ họ tên)</div>
  </div>
  <div class="box">
    <div class="muted">Ngày ...... tháng ...... năm ......</div>
    <div><strong>HIỆU TRƯỞNG</strong></div>
    <div class="muted">(Ký, ghi rõ họ tên)</div>
  </div>
</div>

</body>
</html>
