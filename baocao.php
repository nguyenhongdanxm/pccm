<?php
$page_title = 'Báo cáo tổng hợp';
require_once 'includes/header.php';
$assignments = get_assignments();
$by_teacher = [];
foreach ($assignments as $a) { $by_teacher[$a['teacher']][] = $a; }
ksort($by_teacher);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-bar-chart"></i> Báo cáo Tổng hợp</h3>
    <a href="xuat.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-download"></i> Xuất CSV</a>
</div>
<?php if ($by_teacher): ?>
<div class="accordion" id="acc">
<?php $idx = 0; foreach ($by_teacher as $teacher => $items):
    $idx++;
    $by_subject = []; $total = 0;
    foreach ($items as $a) {
        $by_subject[$a['subject']][] = $a['class'] . '(' . $a['periods'] . ')';
        $total += floatval($a['periods']);
    }
    ksort($by_subject);
    $lines = [];
    foreach ($by_subject as $s => $parts) $lines[] = $s . ': ' . implode(', ', $parts);
?>
<div class="accordion-item mb-2 border-0 shadow-sm rounded">
    <h2 class="accordion-header">
        <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#t<?= $idx ?>">
            <strong class="me-2"><?= e($teacher) ?></strong>
            <span class="badge bg-primary ms-auto me-2"><?= number_format($total, 1) ?> tiết</span>
        </button>
    </h2>
    <div id="t<?= $idx ?>" class="accordion-collapse collapse" data-bs-parent="#acc">
        <div class="accordion-body">
            <pre class="summary-text"><?= e(implode("\n", $lines)) ?></pre>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Môn</th><th>Lớp</th><th class="text-center">Tiết</th><th>Ghi chú</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $a): ?>
                        <tr>
                            <td><?= e($a['subject']) ?></td>
                            <td><?= e($a['class']) ?></td>
                            <td class="text-center"><?= e($a['periods']) ?></td>
                            <td class="text-muted"><?= e($a['note'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-info">Chưa có dữ liệu phân công để báo cáo.</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
