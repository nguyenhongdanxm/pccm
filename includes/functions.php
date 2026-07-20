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
function set_active_version_id($id) { save_json(ACTIVE_VERSION_FILE, ['id' => $id]); }
function get_version($id) {
    foreach (get_versions() as $v) if ($v['id'] === $id) return $v;
    return null;
}
function create_version($name, $date, $note = '', $copy_from = null) {
    $id = 'v' . date('YmdHis');
    $versions = get_versions();
    $versions[] = ['id' => $id, 'name' => $name, 'date' => $date, 'note' => $note, 'created_at' => date('c')];
    save_versions($versions);
    if ($copy_from && get_version($copy_from)) {
        $srcA = load_json(assignments_file($copy_from), []);
        $srcR = load_json(role_assignments_file($copy_from), []);
        foreach ($srcA as &$a) { $a['id'] = $id . '_' . ($a['id'] ?? uniqid()); } unset($a);
        foreach ($srcR as &$a) { $a['id'] = $id . '_' . ($a['id'] ?? uniqid()); } unset($a);
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
    if (!empty(get_versions())) return;
    $id = 'v' . date('YmdHis');
    save_versions([['id'=>$id,'name'=>'Phân công lần 1','date'=>date('Y-m-d'),'note'=>'Tự tạo từ dữ liệu hiện có','created_at'=>date('c')]]);
    save_json(assignments_file($id), load_json(LEGACY_ASSIGNMENTS_FILE, []));
    save_json(role_assignments_file($id), load_json(LEGACY_ROLE_ASSIGNMENTS_FILE, []));
    set_active_version_id($id);
}
function init_data() {
    global $DEFAULT_TEACHERS, $DEFAULT_SUBJECTS, $DEFAULT_CLASSES, $DEFAULT_ROLES, $DEFAULT_GROUPS;
    if (!file_exists(TEACHERS_FILE)) save_json(TEACHERS_FILE, $DEFAULT_TEACHERS);
    if (!file_exists(SUBJECTS_FILE)) save_json(SUBJECTS_FILE, $DEFAULT_SUBJECTS);
    if (!file_exists(CLASSES_FILE)) save_json(CLASSES_FILE, $DEFAULT_CLASSES);
    if (!file_exists(ROLES_FILE)) save_json(ROLES_FILE, $DEFAULT_ROLES);
    if (!file_exists(TEACHER_META_FILE)) save_json(TEACHER_META_FILE, []);
    if (!file_exists(GROUPS_FILE)) save_json(GROUPS_FILE, $DEFAULT_GROUPS);
    migrate_legacy_if_needed();
}

function get_teachers() { global $DEFAULT_TEACHERS; return load_json(TEACHERS_FILE, $DEFAULT_TEACHERS); }
function get_subjects() { global $DEFAULT_SUBJECTS; return load_json(SUBJECTS_FILE, $DEFAULT_SUBJECTS); }
function get_classes() { global $DEFAULT_CLASSES; return load_json(CLASSES_FILE, $DEFAULT_CLASSES); }
function get_roles() { global $DEFAULT_ROLES; return load_json(ROLES_FILE, $DEFAULT_ROLES); }
function get_groups() { global $DEFAULT_GROUPS; return load_json(GROUPS_FILE, $DEFAULT_GROUPS); }
function save_groups($g) { save_json(GROUPS_FILE, $g); }

function get_teacher_meta() { return load_json(TEACHER_META_FILE, []); }
function save_teacher_meta($meta) { save_json(TEACHER_META_FILE, $meta); }
function get_teacher_level($name) {
    $lv = get_teacher_meta()[$name]['level'] ?? 'THCS';
    return in_array($lv, ['THCS','THPT']) ? $lv : 'THCS';
}
function get_teacher_group($name) {
    return get_teacher_meta()[$name]['group'] ?? '';
}
function set_teacher_meta_field($name, $field, $value) {
    $meta = get_teacher_meta();
    if (!isset($meta[$name])) $meta[$name] = [];
    $meta[$name][$field] = $value;
    save_teacher_meta($meta);
}
function set_teacher_level($name, $level) {
    set_teacher_meta_field($name, 'level', in_array($level, ['THCS','THPT']) ? $level : 'THCS');
}
function set_teacher_group($name, $group) {
    set_teacher_meta_field($name, 'group', $group);
}
function get_quota($name) {
    return get_teacher_level($name) === 'THPT' ? QUOTA_THPT : QUOTA_THCS;
}

function get_assignments($vid = null) {
    $vid = $vid ?: get_active_version_id();
    return $vid ? load_json(assignments_file($vid), []) : [];
}
function save_assignments($data, $vid = null) {
    save_json(assignments_file($vid ?: get_active_version_id()), $data);
}
function get_role_assignments($vid = null) {
    $vid = $vid ?: get_active_version_id();
    if (!$vid) return [];
    $items = load_json(role_assignments_file($vid), []);
    $map = [];
    foreach (get_roles() as $r) $map[$r['name']] = floatval($r['periods'] ?? 0);
    $changed = false;
    foreach ($items as &$a) {
        if (!isset($a['periods']) || $a['periods'] === '' || $a['periods'] === null) {
            $a['periods'] = $map[$a['role']] ?? 0; $changed = true;
        }
    }
    unset($a);
    if ($changed) save_json(role_assignments_file($vid), $items);
    return $items;
}
function save_role_assignments($data, $vid = null) {
    save_json(role_assignments_file($vid ?: get_active_version_id()), $data);
}

function ten_cuoi($hoten) {
    $parts = preg_split('/\s+/u', trim($hoten));
    return mb_strtolower(end($parts) ?: $hoten, 'UTF-8');
}
function sort_teachers_by_ten($teachers) {
    usort($teachers, function($a, $b) {
        $cmp = strcmp(ten_cuoi($a), ten_cuoi($b));
        return $cmp !== 0 ? $cmp : strcmp(mb_strtolower($a,'UTF-8'), mb_strtolower($b,'UTF-8'));
    });
    return $teachers;
}
function get_teachers_sorted() { return sort_teachers_by_ten(get_teachers()); }

function get_teacher_loads($vid = null) {
    $load = [];
    foreach (get_assignments($vid) as $a) {
        $t = $a['teacher'];
        if (!isset($load[$t])) $load[$t] = ['day'=>0,'role'=>0,'total'=>0,'classes'=>[],'subjects'=>[],'roles'=>[]];
        $load[$t]['day'] += floatval($a['periods'] ?? 0);
        if (!empty($a['class'])) $load[$t]['classes'][$a['class']] = true;
        $sub = $a['subject'] ?? '';
        if ($sub !== '') {
            if (!isset($load[$t]['subjects'][$sub])) $load[$t]['subjects'][$sub] = [];
            $load[$t]['subjects'][$sub][] = ($a['class'] ?? '') . '(' . ($a['periods'] ?? 0) . ')';
        }
    }
    foreach (get_role_assignments($vid) as $a) {
        $t = $a['teacher'];
        if (!isset($load[$t])) $load[$t] = ['day'=>0,'role'=>0,'total'=>0,'classes'=>[],'subjects'=>[],'roles'=>[]];
        $load[$t]['role'] += floatval($a['periods'] ?? 0);
        if (!empty($a['class'])) $load[$t]['classes'][$a['class']] = true;
        $label = $a['role'] ?? '';
        if (!empty($a['class'])) $label .= ' (' . $a['class'] . ')';
        if ($label !== '') $load[$t]['roles'][] = $label;
    }
    foreach ($load as $t => &$row) {
        $row['total'] = $row['day'] + $row['role'];
        $row['class_count'] = count($row['classes']);
        $row['level'] = get_teacher_level($t);
        $row['group'] = get_teacher_group($t);
        $row['quota'] = get_quota($t);
        $row['diff'] = $row['total'] - $row['quota'];
        $parts = []; ksort($row['subjects']);
        foreach ($row['subjects'] as $s => $cls) $parts[] = $s . ': ' . implode(', ', $cls);
        $row['mon_day'] = implode('; ', $parts);
        $row['kiem_nhiem'] = implode('; ', $row['roles']);
        unset($row['classes'], $row['subjects'], $row['roles']);
    }
    unset($row);
    return $load;
}

function get_export_rows($vid = null) {
    $loads = get_teacher_loads($vid);
    $teachers = get_teachers_sorted();
    $rows = [];
    foreach ($teachers as $t) {
        $r = $loads[$t] ?? null;
        $rows[] = [
            'name' => $t,
            'level' => $r['level'] ?? get_teacher_level($t),
            'group' => $r['group'] ?? get_teacher_group($t),
            'mon_day' => $r['mon_day'] ?? '',
            'kiem_nhiem' => $r['kiem_nhiem'] ?? '',
            'day' => $r['day'] ?? 0,
            'role' => $r['role'] ?? 0,
            'total' => $r['total'] ?? 0,
            'quota' => $r['quota'] ?? get_quota($t),
            'diff' => $r['diff'] ?? -get_quota($t),
        ];
    }
    foreach ($loads as $t => $r) {
        if (!in_array($t, $teachers, true)) {
            $rows[] = ['name'=>$t,'level'=>$r['level'],'group'=>$r['group']??'','mon_day'=>$r['mon_day'],'kiem_nhiem'=>$r['kiem_nhiem'],'day'=>$r['day'],'role'=>$r['role'],'total'=>$r['total'],'quota'=>$r['quota'],'diff'=>$r['diff']];
        }
    }
    return $rows;
}

function get_grade($class_name) { return preg_replace('/[^0-9]/', '', $class_name); }
function get_periods($subject, $class_name) {
    $subjects = get_subjects();
    return $subjects[$subject][get_grade($class_name)] ?? null;
}
function flash($message, $type = 'success') { $_SESSION['flash'] = ['message'=>$message,'type'=>$type]; }
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert alert-'.htmlspecialchars($f['type']).' alert-dismissible fade show">'.htmlspecialchars($f['message']).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function is_logged_in() { return !empty($_SESSION['pccm_admin']); }
function require_login() {
    if (!is_logged_in()) {
        flash('Vui lòng đăng nhập để sử dụng chức năng này.', 'warning');
        header('Location: ' . BASE_URL . 'login.php'); exit;
    }
}
function attempt_login($user, $pass) {
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) { $_SESSION['pccm_admin'] = true; return true; }
    return false;
}
function logout() { unset($_SESSION['pccm_admin']); session_destroy(); }

if (session_status() === PHP_SESSION_NONE) session_start();
init_data();
$__vid = get_active_version_id();
if ($__vid) {
    if (!defined('ASSIGNMENTS_FILE')) define('ASSIGNMENTS_FILE', assignments_file($__vid));
    if (!defined('ROLE_ASSIGNMENTS_FILE')) define('ROLE_ASSIGNMENTS_FILE', role_assignments_file($__vid));
} else {
    if (!defined('ASSIGNMENTS_FILE')) define('ASSIGNMENTS_FILE', LEGACY_ASSIGNMENTS_FILE);
    if (!defined('ROLE_ASSIGNMENTS_FILE')) define('ROLE_ASSIGNMENTS_FILE', LEGACY_ROLE_ASSIGNMENTS_FILE);
}
