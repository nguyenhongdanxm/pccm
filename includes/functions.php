<?php
require_once __DIR__ . '/config.php';

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'Xinman@2021');

function load_json($file, $default = []) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : $default;
    }
    return $default;
}
function save_json($file, $data) {
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
function init_data() {
    global $DEFAULT_TEACHERS, $DEFAULT_SUBJECTS, $DEFAULT_CLASSES, $DEFAULT_ROLES;
    if (!file_exists(TEACHERS_FILE)) save_json(TEACHERS_FILE, $DEFAULT_TEACHERS);
    if (!file_exists(SUBJECTS_FILE)) save_json(SUBJECTS_FILE, $DEFAULT_SUBJECTS);
    if (!file_exists(CLASSES_FILE)) save_json(CLASSES_FILE, $DEFAULT_CLASSES);
    if (!file_exists(ASSIGNMENTS_FILE)) save_json(ASSIGNMENTS_FILE, []);
    if (!file_exists(ROLES_FILE)) save_json(ROLES_FILE, $DEFAULT_ROLES);
    if (!file_exists(ROLE_ASSIGNMENTS_FILE)) save_json(ROLE_ASSIGNMENTS_FILE, []);
}
function get_teachers() { global $DEFAULT_TEACHERS; return load_json(TEACHERS_FILE, $DEFAULT_TEACHERS); }
function get_subjects() { global $DEFAULT_SUBJECTS; return load_json(SUBJECTS_FILE, $DEFAULT_SUBJECTS); }
function get_classes() { global $DEFAULT_CLASSES; return load_json(CLASSES_FILE, $DEFAULT_CLASSES); }
function get_assignments() { return load_json(ASSIGNMENTS_FILE, []); }
function get_roles() { global $DEFAULT_ROLES; return load_json(ROLES_FILE, $DEFAULT_ROLES); }
function get_role_assignments() {
    $items = load_json(ROLE_ASSIGNMENTS_FILE, []);
    $roles = get_roles();
    $map = [];
    foreach ($roles as $r) $map[$r['name']] = floatval($r['periods'] ?? 0);
    // Đồng bộ số tiết nếu bản ghi cũ thiếu
    $changed = false;
    foreach ($items as &$a) {
        if (!isset($a['periods']) || $a['periods'] === '' || $a['periods'] === null) {
            $a['periods'] = $map[$a['role']] ?? 0;
            $changed = true;
        }
    }
    unset($a);
    if ($changed) save_json(ROLE_ASSIGNMENTS_FILE, $items);
    return $items;
}

function ten_cuoi($hoten) {
    $parts = preg_split('/\s+/u', trim($hoten));
    return mb_strtolower(end($parts) ?: $hoten, 'UTF-8');
}
function sort_teachers_by_ten($teachers) {
    usort($teachers, function($a, $b) {
        $cmp = strcmp(ten_cuoi($a), ten_cuoi($b));
        return $cmp !== 0 ? $cmp : strcmp(mb_strtolower($a, 'UTF-8'), mb_strtolower($b, 'UTF-8'));
    });
    return $teachers;
}
function get_teachers_sorted() { return sort_teachers_by_ten(get_teachers()); }

/** Tải tiết theo GV: dạy môn + kiêm nhiệm + số lớp */
function get_teacher_loads() {
    $load = [];
    foreach (get_assignments() as $a) {
        $t = $a['teacher'];
        if (!isset($load[$t])) $load[$t] = ['day' => 0, 'role' => 0, 'total' => 0, 'classes' => []];
        $load[$t]['day'] += floatval($a['periods'] ?? 0);
        if (!empty($a['class'])) $load[$t]['classes'][$a['class']] = true;
    }
    foreach (get_role_assignments() as $a) {
        $t = $a['teacher'];
        if (!isset($load[$t])) $load[$t] = ['day' => 0, 'role' => 0, 'total' => 0, 'classes' => []];
        $load[$t]['role'] += floatval($a['periods'] ?? 0);
        if (!empty($a['class'])) $load[$t]['classes'][$a['class']] = true;
    }
    foreach ($load as $t => &$row) {
        $row['total'] = $row['day'] + $row['role'];
        $row['class_count'] = count($row['classes']);
        unset($row['classes']);
    }
    unset($row);
    return $load;
}

function get_grade($class_name) { return preg_replace('/[^0-9]/', '', $class_name); }
function get_periods($subject, $class_name) {
    $subjects = get_subjects();
    $grade = get_grade($class_name);
    return $subjects[$subject][$grade] ?? null;
}
function flash($message, $type = 'success') { $_SESSION['flash'] = ['message' => $message, 'type' => $type]; }
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert alert-' . htmlspecialchars($f['type']) . ' alert-dismissible fade show">' . htmlspecialchars($f['message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

function is_logged_in() { return !empty($_SESSION['pccm_admin']); }
function require_login() {
    if (!is_logged_in()) {
        flash('Vui lòng đăng nhập để sử dụng chức năng này.', 'warning');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}
function attempt_login($user, $pass) {
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['pccm_admin'] = true;
        return true;
    }
    return false;
}
function logout() {
    unset($_SESSION['pccm_admin']);
    session_destroy();
}

if (session_status() === PHP_SESSION_NONE) session_start();
init_data();
