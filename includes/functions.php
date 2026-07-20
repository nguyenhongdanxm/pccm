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

function assignments_file($vid) { return DATA_PATH . '/assignments_' . $vid . '.json'; }
function role_assignments_file($vid) { return DATA_PATH . '/roles_' . $vid . '.json'; }

function get_versions() { return load_json(VERSIONS_FILE, []); }
function save_versions($list) { save_json(VERSIONS_FILE, $list); }

function get_active_version_id() {
    $data = load_json(ACTIVE_VERSION_FILE, []);
    if (!empty($data['id'])) return $data['id'];
    $versions = get_versions();
    return $versions[0]['id'] ?? null;
}
function set_active_version_id($id) {
    save_json(ACTIVE_VERSION_FILE, ['id' => $id]);
}
function get_version($id) {
    foreach (get_versions() as $v) {
        if ($v['id'] === $id) return $v;
    }
    return null;
}

function create_version($name, $date, $note = '', $copy_from = null) {
    $id = 'v' . date('YmdHis');
    $versions = get_versions();
    $versions[] = [
        'id' => $id,
        'name' => $name,
        'date' => $date,
        'note' => $note,
        'created_at' => date('c'),
    ];
    save_versions($versions);

    if ($copy_from && get_version($copy_from)) {
        $srcA = load_json(assignments_file($copy_from), []);
        $srcR = load_json(role_assignments_file($copy_from), []);
        // Đổi id mới để tránh trùng
        foreach ($srcA as &$a) { $a['id'] = $id . '_' . ($a['id'] ?? uniqid()); }
        unset($a);
        foreach ($srcR as &$a) { $a['id'] = $id . '_' . ($a['id'] ?? uniqid()); }
        unset($a);
        save_json(assignments_file($id), $srcA);
        save_json(role_assignments_file($id), $srcR);
    } else {
        save_json(assignments_file($id), []);
        save_json(role_assignments_file($id), []);
    }
    set_active_version_id($id);
    return $id;
}

function migrate_legacy_if_needed() {
    $versions = get_versions();
    if (!empty($versions)) return;

    $legacyA = load_json(LEGACY_ASSIGNMENTS_FILE, []);
    $legacyR = load_json(LEGACY_ROLE_ASSIGNMENTS_FILE, []);

    // Nếu có dữ liệu cũ hoặc chưa có phiên bản nào → tạo lần 1
    $id = 'v' . date('YmdHis');
    $versions[] = [
        'id' => $id,
        'name' => 'Phân công lần 1',
        'date' => date('Y-m-d'),
        'note' => 'Tự tạo từ dữ liệu hiện có',
        'created_at' => date('c'),
    ];
    save_versions($versions);
    save_json(assignments_file($id), $legacyA);
    save_json(role_assignments_file($id), $legacyR);
    set_active_version_id($id);
}

function init_data() {
    global $DEFAULT_TEACHERS, $DEFAULT_SUBJECTS, $DEFAULT_CLASSES, $DEFAULT_ROLES;
    if (!file_exists(TEACHERS_FILE)) save_json(TEACHERS_FILE, $DEFAULT_TEACHERS);
    if (!file_exists(SUBJECTS_FILE)) save_json(SUBJECTS_FILE, $DEFAULT_SUBJECTS);
    if (!file_exists(CLASSES_FILE)) save_json(CLASSES_FILE, $DEFAULT_CLASSES);
    if (!file_exists(ROLES_FILE)) save_json(ROLES_FILE, $DEFAULT_ROLES);
    migrate_legacy_if_needed();
}

function get_teachers() { global $DEFAULT_TEACHERS; return load_json(TEACHERS_FILE, $DEFAULT_TEACHERS); }
function get_subjects() { global $DEFAULT_SUBJECTS; return load_json(SUBJECTS_FILE, $DEFAULT_SUBJECTS); }
function get_classes() { global $DEFAULT_CLASSES; return load_json(CLASSES_FILE, $DEFAULT_CLASSES); }
function get_roles() { global $DEFAULT_ROLES; return load_json(ROLES_FILE, $DEFAULT_ROLES); }

function get_assignments($vid = null) {
    $vid = $vid ?: get_active_version_id();
    if (!$vid) return [];
    return load_json(assignments_file($vid), []);
}
function save_assignments($data, $vid = null) {
    $vid = $vid ?: get_active_version_id();
    save_json(assignments_file($vid), $data);
}
function get_role_assignments($vid = null) {
    $vid = $vid ?: get_active_version_id();
    if (!$vid) return [];
    $items = load_json(role_assignments_file($vid), []);
    $roles = get_roles();
    $map = [];
    foreach ($roles as $r) $map[$r['name']] = floatval($r['periods'] ?? 0);
    $changed = false;
    foreach ($items as &$a) {
        if (!isset($a['periods']) || $a['periods'] === '' || $a['periods'] === null) {
            $a['periods'] = $map[$a['role']] ?? 0;
            $changed = true;
        }
    }
    unset($a);
    if ($changed && $vid) save_json(role_assignments_file($vid), $items);
    return $items;
}
function save_role_assignments($data, $vid = null) {
    $vid = $vid ?: get_active_version_id();
    save_json(role_assignments_file($vid), $data);
}

// Tương thích code cũ dùng constant ASSIGNMENTS_FILE
function save_json_compat($file, $data) {
    // Nếu gọi save_json với path cũ, redirect sang version
    save_json($file, $data);
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

function get_teacher_loads($vid = null) {
    $load = [];
    foreach (get_assignments($vid) as $a) {
        $t = $a['teacher'];
        if (!isset($load[$t])) $load[$t] = ['day' => 0, 'role' => 0, 'total' => 0, 'classes' => []];
        $load[$t]['day'] += floatval($a['periods'] ?? 0);
        if (!empty($a['class'])) $load[$t]['classes'][$a['class']] = true;
    }
    foreach (get_role_assignments($vid) as $a) {
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

// Compatibility: define constants for active version files
$__vid = get_active_version_id();
if ($__vid) {
    if (!defined('ASSIGNMENTS_FILE')) define('ASSIGNMENTS_FILE', assignments_file($__vid));
    if (!defined('ROLE_ASSIGNMENTS_FILE')) define('ROLE_ASSIGNMENTS_FILE', role_assignments_file($__vid));
} else {
    if (!defined('ASSIGNMENTS_FILE')) define('ASSIGNMENTS_FILE', LEGACY_ASSIGNMENTS_FILE);
    if (!defined('ROLE_ASSIGNMENTS_FILE')) define('ROLE_ASSIGNMENTS_FILE', LEGACY_ROLE_ASSIGNMENTS_FILE);
}
