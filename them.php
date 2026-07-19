<?php
$page_title = 'Thêm phân công';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $assignments = get_assignments();

    if ($_POST['action'] === 'add_one') {
        $teacher = trim($_POST['teacher'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $class_name = trim($_POST['class_name'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $periods_manual = trim($_POST['periods_manual'] ?? '');

        if (!$teacher || !$subject || !$class_name) {
            flash('Vui lòng chọn đầy đủ Giáo viên, Môn học và Lớp.', 'danger');
        } else {
            $exists = false;
            foreach ($assignments as $a) {
                if ($a['teacher'] === $teacher && $a['subject'] === $subject && $a['class'] === $class_name) {
                    $exists = true; break;
                }
            }
            if ($exists) {
                flash("Đã tồn tại: $teacher – $subject – $class_name", 'warning');
            } else {
                $periods = get_periods($subject, $class_name);
                if ($periods === null) {
                    $periods = is_numeric($periods_manual) ? floatval($periods_manual) : 0;
                }
                $assignments[] = [
                    'id' => date('YmdHis') . substr(microtime(), 2, 6),
                    'teacher' => $teacher,
                    'subject' => $subject,
                    'class' => $class_name,
                    'periods' => $periods,
                    'note' => $note,
                    'created_at' => date('c'),
                ];
                save_json(ASSIGNMENTS_FILE, $assignments);
                flash("Đã thêm: $teacher dạy $subject lớp $class_name ($periods tiết)", 'success');
            }
        }
        header('Location: ' . BASE_URL . 'them.php');
        exit;
    }

    if ($_POST['action'] === 'add_multi') {
        $teacher = trim($_POST['teacher'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $classes = $_POST['classes'] ?? [];
        if (!$teacher || !$subject || empty($classes)) {
            flash('Vui lòng chọn đầy đủ.', 'danger');
        } else {
            $added = 0;
            foreach ($classes as $cls) {
                $exists = false;
                foreach ($assignments as $a) {
                    if ($a['teacher'] === $teacher && $a['subject'] === $subject && $a['class'] === $cls) {
                        $exists = true; break;
                    }
                }
                if (!$exists) {
                    $p = get_periods($subject, $cls) ?? 0;
                    $assignments[] = [
                        'id' => date('YmdHis') . $cls . substr(microtime(), 2, 4),
                        'teacher' => $teacher,
                        'subject' => $subject,
                        'class' => $cls,
                        'periods' => $p,
                        'note' => '',
                        'created_at' => date('c'),
                    ];
                    $added++;
                }
            }
            save_json(ASSIGNMENTS_FILE, $assignments);
            flash("Đã thêm $added phân công mới.", 'success');
        }
        header('Location: ' . BASE_URL . 'danhsach.php');
        exit;
    }
}

require_once 'includes/header.php';
$teachers = get_teachers(); sort($teachers);
$subjects = array_keys(get_subjects()); sort($subjects);
$classes = get_classes();
?>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle"></i> Thêm 1 phân công</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="add_one">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giáo viên *</label>
                        <select name="teacher" class="form-select" required>
                            <option value="">-- Chọn giáo viên --</option>
                            <?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Môn học *</label>
                        <select name="subject" id="subject" class="form-select" required>
                            <option value="">-- Chọn môn --</option>
                            <?php foreach ($subjects as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lớp *</label>
                        <select name="class_name" id="class_name" class="form-select" required>
                            <option value="">-- Chọn lớp --</option>
                            <?php foreach ($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiết</label>
                        <div id="periods-display" class="alert alert-success py-2 d-none">
                            Số tiết tự động: <strong id="periods-value">0</strong>
                        </div>
                        <div id="periods-manual-wrap" class="d-none">
                            <input type="number" name="periods_manual" class="form-control" step="0.1" min="0" max="10" placeholder="Nhập số tiết thủ công">
                            <div class="form-text text-warning">Không tìm thấy số tiết chuẩn, vui lòng nhập tay.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <input type="text" name="note" class="form-control" placeholder="Tùy chọn">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Lưu phân công</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-success"><i class="bi bi-lightning"></i> Thêm nhanh nhiều lớp</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="add_multi">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giáo viên *</label>
                        <select name="teacher" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach ($teachers as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Môn học *</label>
                        <select name="subject" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach ($subjects as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn nhiều lớp *</label>
                        <div class="row g-2">
                            <?php foreach ($classes as $c): ?>
                            <div class="col-4 col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="classes[]" value="<?= e($c) ?>" id="cls_<?= e($c) ?>">
                                    <label class="form-check-label" for="cls_<?= e($c) ?>"><?= e($c) ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-lightning-fill"></i> Thêm tất cả lớp đã chọn</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const subjectSelect = document.getElementById('subject');
const classSelect = document.getElementById('class_name');
const periodsDisplay = document.getElementById('periods-display');
const periodsValue = document.getElementById('periods-value');
const periodsManualWrap = document.getElementById('periods-manual-wrap');

function fetchPeriods() {
    const subject = subjectSelect.value;
    const cls = classSelect.value;
    if (!subject || !cls) {
        periodsDisplay.classList.add('d-none');
        periodsManualWrap.classList.add('d-none');
        return;
    }
    fetch('<?= BASE_URL ?>api/periods.php?subject=' + encodeURIComponent(subject) + '&class=' + encodeURIComponent(cls))
        .then(r => r.json())
        .then(data => {
            if (data.periods !== null && data.periods !== undefined) {
                periodsValue.textContent = data.periods;
                periodsDisplay.classList.remove('d-none');
                periodsManualWrap.classList.add('d-none');
            } else {
                periodsDisplay.classList.add('d-none');
                periodsManualWrap.classList.remove('d-none');
            }
        });
}
subjectSelect.addEventListener('change', fetchPeriods);
classSelect.addEventListener('change', fetchPeriods);
</script>

<?php require_once 'includes/footer.php'; ?>
