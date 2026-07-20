<?php
$page_title = 'Báo cáo tổng hợp';
require_once 'includes/header.php';
$assignments = get_assignments();
$role_assignments = get_role_assignments();
$loads = get_teacher_loads();

$by_teacher = [];
foreach ($assignments as $a) { $by_teacher[$a['teacher']][] = $a; }

$roles_by_teacher = [];
foreach ($role_assignments as $a) { $roles_by_teacher[$a['teacher']][] = $a; }

$all_names = array_unique(array_merge(array_keys($by_teacher), array_keys($roles_by_teacher)));
$all_names = sort_teachers_by_ten($all_names);

$total_all = array_sum(array_column($loads, 'total'));
$total_day = array_sum(array_column($loads, 'day'));
$total_role = array_sum(array_column($loads, 'role'));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-bar-chart"></i> Báo cáo Tổng hợp</h3>
    <?php if (is_logged_in()): ?>
    <a href="<?= BASE_URL ?>xuat.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-download"></i> Xuất CSV</a>
    <?php endif; ?>
</div>

<div class="row g-2 mb-4">
    <div class="col-md-4"><div class="card stat-card py-2"><div class="number fs-4"><?= number_format($total_day, 1) ?></div><div class="label">Tổng tiết dạy môn</div></div></div>
    <div class="col-md-4"><div class="card stat-card py-2"><div class="number fs-4 text-info"><?= number_format($total_role, 1) ?></div><div class="label">Tổng tiết kiêm nhiệm</div></div></div>
    <div class="col-md-4"><div class="card stat-card py-2"><div class="number fs-4 text-primary"><?= number_format($total_all, 1) ?></div><div class="label">Tổng cộng tiết</div></div></div>
</div>

<?php if ($all_names): ?>
<div class="accordion" id="acc">
<?php $idx = 0; foreach ($all_names as $teacher):
    $idx++;
    $items = $by_teacher[$teacher] ?? [];
    $roles = $roles_by_teacher[$teacher] ?? [];
    $load = $loads[$teacher] ?? ['day' => 0, 'role' => 0, 'total' => 0, 'class_count' => 0];

    $by_subject = [];
    foreach ($items as $a) {
        $by_subject[$a['subject']][] = $a['class'] . '(' . $a['periods'] . ')';
    }
    ksort($by_subject);
    $lines = [];
    foreach ($by_subject as $s => $parts) $lines[] = $s . ': ' . implode(', ', $parts);
?>
<div class="accordion-item mb-2 border-0 shadow-sm rounded">
    <h2 class="accordion-header">
        <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#t<?= $idx ?>">
            <strong class="me-2"><?= e($teacher) ?></strong>
            <?php if ($roles): ?>
            <span class="badge bg-info text-dark me-1"><?= count($roles) ?> KN</span>
            <?php endif; ?>
            <span class="badge bg-secondary me-1"><?= $load['class_count'] ?> lớp</span>
            <span class="badge bg-primary ms-auto me-2"><?= number_format($load['total'], 1) ?> tiết</span>
            <small class="text-muted me-2 d-none d-md-inline">(dạy <?= number_format($load['day'], 1) ?> + KN <?= number_format($load['role'], 1) ?>)</small>
        </button>
    </h2>
    <div id="t<?= $idx ?>" class="accordion-collapse collapse" data-bs-parent="#acc">
        <div class="accordion-body">
            <?php if ($roles): ?>
            <div class="mb-3">
                <strong><i class="bi bi-person-badge"></i> Kiêm nhiệm:</strong>
                <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Chức vụ</th><th>Lớp</th><th class="text-center">Tiết</th><th>Ghi chú</th></tr></thead>
                    <tbody>
                    <?php foreach ($roles as $r): ?>
                    <tr>
                        <td><span class="badge bg-info text-dark"><?= e($r['role']) ?></span></td>
                        <td><?= e($r['class'] ?? '') ?></td>
                        <td class="text-center"><?= e($r['periods'] ?? 0) ?></td>
                        <td class="text-muted"><?= e($r['note'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($lines): ?>
            <strong><i class="bi bi-book"></i> Dạy môn:</strong>
            <pre class="summary-text mt-2"><?= e(implode("\n", $lines)) ?></pre>
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
            <?php elseif (!$roles): ?>
            <p class="text-muted mb-0">Chưa có phân công.</p>
            <?php endif; ?>

            <div class="mt-3 text-end">
                <span class="badge bg-light text-dark border">Tiết dạy: <?= number_format($load['day'], 1) ?></span>
                <span class="badge bg-info text-dark">Kiêm nhiệm: <?= number_format($load['role'], 1) ?></span>
                <span class="badge bg-primary">Tổng: <?= number_format($load['total'], 1) ?> tiết</span>
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
