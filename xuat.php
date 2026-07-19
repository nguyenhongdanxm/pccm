<?php
require_once 'includes/functions.php';
$assignments = get_assignments();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="phan_cong_' . date('Ymd') . '.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['Giáo viên', 'Môn học', 'Lớp', 'Số tiết', 'Ghi chú']);
foreach ($assignments as $a) {
    fputcsv($out, [$a['teacher'], $a['subject'], $a['class'], $a['periods'], $a['note'] ?? '']);
}
fclose($out);
exit;
