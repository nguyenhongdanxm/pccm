<?php
require_once __DIR__ . '/config.php';

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'Xinman@2021');

if (!defined('TAP_SU_QUOTA_REDUCTION')) define('TAP_SU_QUOTA_REDUCTION', 2);
if (!defined('QUOTA_HIEU_TRUONG')) define('QUOTA_HIEU_TRUONG', 2);
if (!defined('QUOTA_PHO_HIEU_TRUONG')) define('QUOTA_PHO_HIEU_TRUONG', 4);

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
    if (!file_exists(SETTINGS_FILE)) {
        save_json(SETTINGS_FILE, [
            'quota_thcs' => DEFAULT_QUOTA_THCS,
            'quota_thpt' => DEFAULT_QUOTA_THPT,
            'tap_su_reduction' => TAP_SU_QUOTA_REDUCTION,
            'quota_hieu_truong' => QUOTA_HIEU_TRUONG,
            'quota_pho_hieu_truong' => QUOTA_PHO_HIEU_TRUONG,
        ]);
    }
    migrate_legacy_if_needed();
}

function get_settings() {
    return load_json(SETTINGS_FILE, [
        'quota_thcs' => DEFAULT_QUOTA_THCS,
        'quota_thpt' => DEFAULT_QUOTA_THPT,
        'tap_su_reduction' => TAP_SU_QUOTA_REDUCTION,
        'quota_hieu_truong' => QUOTA_HIEU_TRUONG,
        'quota_pho_hieu_truong' => QUOTA_PHO_HIEU_TRUONG,
    ]);
}
function save_settings($s) { save_json(SETTINGS_FILE, $s); }
function get_quota_thcs() {
    $s = get_settings();
    return floatval($s['quota_thcs'] ?? DEFAULT_QUOTA_THCS);
}
function get_quota_thpt() {
    $s = get_settings();
    return floatval($s['quota_thpt'] ?? DEFAULT_QUOTA_THPT);
}
function get_tap_su_reduction() {
    $s = get_settings();
    $r = $s['tap_su_reduction'] ?? TAP_SU_QUOTA_REDUCTION;
    return is_numeric($r) ? floatval($r) : TAP_SU_QUOTA_REDUCTION;
}
function get_quota_hieu_truong() {
    $s = get_settings();
    $r = $s['quota_hieu_truong'] ?? QUOTA_HIEU_TRUONG;
    return is_numeric($r) ? floatval($r) : QUOTA_HIEU_TRUONG;
}
function get_quota_pho_hieu_truong() {
    $s = get_settings();
    $r = $s['quota_pho_hieu_truong'] ?? QUOTA_PHO_HIEU_TRUONG;
    return is_numeric($r) ? floatval($r) : QUOTA_PHO_HIEU_TRUONG;
}

function get_teachers() { global $DEFAULT_TEACHERS; return load_json(TEACHERS_FILE, $DEFAULT_TEACHERS); }
function get_subjects() { global $DEFAULT_SUBJECTS; return load_json(SUBJECTS_FILE, $DEFAULT_SUBJECTS); }
function get_classes() { global $DEFAULT_CLASSES; return load_json(CLASSES_FILE, $DEFAULT_CLASSES); }
function get_roles() { global $DEFAULT_ROLES; return load_json(ROLES_FILE, $DEFAULT_ROLES); }
function get_groups() { global $DEFAULT_GROUPS; return load_json(GROUPS_FILE, $DEFAULT_GROUPS); }
function save_groups($g) { save_json(GROUPS_FILE, $g); }

function get_teacher_meta() { return load_json(TEACHER_META_FILE, []); }
function save_teacher_meta($meta) { save_json(TEACHER_META_FILE, $meta); }

function get_teacher_flags($name) {
    $m = get_teacher_meta()[$name] ?? [];
    $thcs = !empty($m['thcs']);
    $thpt = !empty($m['thpt']);
    if (!$thcs && !$thpt) {
        $lv = $m['level'] ?? 'THCS';
        $thcs = ($lv !== 'THPT');
        $thpt = ($lv === 'THPT');
    }
    $khxh = !empty($m['khxh']);
    $khtn = !empty($m['khtn']);
    if (!$khxh && !$khtn && !empty($m['group'])) {
        $g = mb_strtoupper($m['group'], 'UTF-8');
        if (strpos($g, 'KHXH') !== false) $khxh = true;
        if (strpos($g, 'KHTN') !== false) $khtn = true;
    }
    $cm = $m['chuyen_mon'] ?? [];
    if (is_string($cm) && $cm !== '') $cm = [$cm];
    if (!is_array($cm)) $cm = [];
    $cm = array_values(array_filter(array_map('strval', $cm)));
    return [
        'khxh' => $khxh,
        'khtn' => $khtn,
        'thcs' => $thcs,
        'thpt' => $thpt,
        'tap_su' => !empty($m['tap_su']),
        'hieu_truong' => !empty($m['hieu_truong']),
        'pho_hieu_truong' => !empty($m['pho_hieu_truong']),
        'group' => $m['group'] ?? '',
        'chuyen_mon' => $cm,
    ];
}

function set_teacher_flags($name, $flags) {
    $meta = get_teacher_meta();
    if (!isset($meta[$name])) $meta[$name] = [];
    $meta[$name]['khxh'] = !empty($flags['khxh']);
    $meta[$name]['khtn'] = !empty($flags['khtn']);
    $meta[$name]['thcs'] = !empty($flags['thcs']);
    $meta[$name]['thpt'] = !empty($flags['thpt']);
    $meta[$name]['tap_su'] = !empty($flags['tap_su']);
    $meta[$name]['hieu_truong'] = !empty($flags['hieu_truong']);
    $meta[$name]['pho_hieu_truong'] = !empty($flags['pho_hieu_truong']);
    if (!empty($meta[$name]['hieu_truong'])) $meta[$name]['pho_hieu_truong'] = false;
    if (!empty($flags['thpt']) && empty($flags['thcs'])) $meta[$name]['level'] = 'THPT';
    elseif (!empty($flags['thcs'])) $meta[$name]['level'] = 'THCS';
    else $meta[$name]['level'] = 'THCS';
    save_teacher_meta($meta);
}

function get_teacher_chuyen_mon($name) {
    return get_teacher_flags($name)['chuyen_mon'] ?? [];
}
function set_teacher_chuyen_mon($name, $subjects) {
    if (!is_array($subjects)) {
        $subjects = $subjects === '' || $subjects === null ? [] : [$subjects];
    }
    $subjects = array_values(array_unique(array_filter(array_map('trim', $subjects))));
    set_teacher_meta_field($name, 'chuyen_mon', $subjects);
}
function get_teacher_level($name) {
    $f = get_teacher_flags($name);
    if ($f['thpt'] && !$f['thcs']) return 'THPT';
    if ($f['thcs'] && !$f['thpt']) return 'THCS';
    if ($f['thpt'] && $f['thcs']) return 'THCS+THPT';
    return 'THCS';
}
function get_teacher_group($name) {
    return get_teacher_flags($name)['group'] ?? '';
}
function set_teacher_meta_field($name, $field, $value) {
    $meta = get_teacher_meta();
    if (!isset($meta[$name])) $meta[$name] = [];
    $meta[$name][$field] = $value;
    save_teacher_meta($meta);
}
function set_teacher_level($name, $level) {
    $f = get_teacher_flags($name);
    if ($level === 'THPT') { $f['thpt'] = true; $f['thcs'] = false; }
    else { $f['thcs'] = true; $f['thpt'] = false; }
    set_teacher_flags($name, $f);
}
function set_teacher_group($name, $group) {
    set_teacher_meta_field($name, 'group', $group);
}

function get_quota($name) {
    $f = get_teacher_flags($name);
    if (!empty($f['hieu_truong'])) return get_quota_hieu_truong();
    if (!empty($f['pho_hieu_truong'])) return get_quota_pho_hieu_truong();
    if ($f['thpt'] && !$f['thcs']) $q = get_quota_thpt();
    else $q = get_quota_thcs();
    if (!empty($f['tap_su'])) $q = max(0, $q - get_tap_su_reduction());
    return $q;
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
        $row['flags'] = get_teacher_flags($t);
        $row['chuyen_mon'] = get_teacher_chuyen_mon($t);
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
            'chuyen_mon' => $r['chuyen_mon'] ?? get_teacher_chuyen_mon($t),
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
            $rows[] = ['name'=>$t,'level'=>$r['level'],'group'=>$r['group']??'','chuyen_mon'=>$r['chuyen_mon']??[],'mon_day'=>$r['mon_day'],'kiem_nhiem'=>$r['kiem_nhiem'],'day'=>$r['day'],'role'=>$r['role'],'total'=>$r['total'],'quota'=>$r['quota'],'diff'=>$r['diff']];
        }
    }
    return $rows;
}

function get_grade($class_name) { return preg_replace('/[^0-9]/', '', $class_name); }
function get_periods($subject, $class_name) {
    $subjects = get_subjects();
    return $subjects[$subject][get_grade($class_name)] ?? null;
}

/**
 * Thống kê phân công so với tiết chuẩn chương trình (theo môn–khối).
 */
function get_assignment_stats($vid = null) {
    $vid = $vid ?: get_active_version_id();
    $subjects = get_subjects();
    $classes = get_classes();
    $assignments = get_assignments($vid);
    $role_items = get_role_assignments($vid);

    // Map: subject|class => periods, teachers
    $map = [];
    $by_class_assigned = [];
    $by_subject_assigned = [];
    $total_day = 0;
    foreach ($assignments as $a) {
        $p = floatval($a['periods'] ?? 0);
        $total_day += $p;
        $cls = $a['class'] ?? '';
        $sub = $a['subject'] ?? '';
        $key = $sub . '|' . $cls;
        if (!isset($map[$key])) $map[$key] = ['periods' => 0, 'teachers' => []];
        $map[$key]['periods'] += $p;
        if (!empty($a['teacher'])) $map[$key]['teachers'][] = $a['teacher'];
        $by_class_assigned[$cls] = ($by_class_assigned[$cls] ?? 0) + $p;
        $by_subject_assigned[$sub] = ($by_subject_assigned[$sub] ?? 0) + $p;
    }

    $total_role = 0;
    foreach ($role_items as $a) $total_role += floatval($a['periods'] ?? 0);

    $by_class = [];
    $by_grade = [];
    $by_subject = [];
    $missing_list = [];
    $diff_list = [];
    $conflict_list = [];
    $slots_ok = 0;
    $slots_missing = 0;
    $slots_diff = 0;
    $total_std = 0;
    $classes_ok = 0;

    // Conflicts: more than 1 unique teacher for same subject+class
    foreach ($map as $key => $info) {
        $teachers = array_values(array_unique($info['teachers']));
        if (count($teachers) > 1) {
            [$sub, $cls] = explode('|', $key, 2);
            $conflict_list[] = ['subject' => $sub, 'class' => $cls, 'teachers' => $teachers];
        }
    }

    foreach ($classes as $cls) {
        $grade = get_grade($cls);
        $level = (intval($grade) >= 10) ? 'THPT' : 'THCS';
        $std = 0;
        $assigned = floatval($by_class_assigned[$cls] ?? 0);
        $miss = [];
        $pdiffs = [];
        $class_ok = true;

        foreach ($subjects as $sub => $grades) {
            if (!is_array($grades) || !isset($grades[$grade])) continue;
            $sp = floatval($grades[$grade]);
            if ($sp <= 0) continue;

            $std += $sp;
            if (!isset($by_subject[$sub])) {
                $by_subject[$sub] = ['std' => 0, 'assigned' => 0, 'ok' => 0, 'missing' => 0, 'diff_count' => 0];
            }
            $by_subject[$sub]['std'] += $sp;

            $key = $sub . '|' . $cls;
            $ap = floatval($map[$key]['periods'] ?? 0);

            if ($ap <= 0.0001) {
                $slots_missing++;
                $class_ok = false;
                $miss[] = $sub . ' (' . rtrim(rtrim(number_format($sp, 2, '.', ''), '0'), '.') . 't)';
                $missing_list[] = ['class' => $cls, 'subject' => $sub, 'std' => $sp];
                $by_subject[$sub]['missing']++;
            } elseif (abs($ap - $sp) > 0.05) {
                $slots_diff++;
                $class_ok = false;
                $pdiffs[] = $sub . ': ' . $ap . '/' . $sp;
                $diff_list[] = [
                    'class' => $cls,
                    'subject' => $sub,
                    'std' => $sp,
                    'assigned' => $ap,
                    'teachers' => $map[$key]['teachers'] ?? [],
                ];
                $by_subject[$sub]['diff_count']++;
            } else {
                $slots_ok++;
                $by_subject[$sub]['ok']++;
            }
        }

        $total_std += $std;
        $diff = $assigned - $std;
        $status = 'ok';
        if ($miss) $status = 'missing';
        elseif ($pdiffs) $status = 'diff';
        if ($status === 'ok') $classes_ok++;

        $by_class[] = [
            'class' => $cls,
            'grade' => $grade,
            'level' => $level,
            'std' => $std,
            'assigned' => $assigned,
            'diff' => $diff,
            'missing' => $miss,
            'period_diffs' => $pdiffs,
            'status' => $status,
        ];

        if (!isset($by_grade[$grade])) {
            $by_grade[$grade] = [
                'level' => $level,
                'class_count' => 0,
                'std' => 0,
                'assigned' => 0,
                'std_per_class' => 0,
                'classes_ok' => 0,
            ];
        }
        $by_grade[$grade]['class_count']++;
        $by_grade[$grade]['std'] += $std;
        $by_grade[$grade]['assigned'] += $assigned;
        if ($status === 'ok') $by_grade[$grade]['classes_ok']++;
        // std per class = curriculum for one class of this grade
        $by_grade[$grade]['std_per_class'] = $std; // last class same grade should match
    }

    // Fill subject assigned totals
    foreach ($by_subject as $sub => &$row) {
        $row['assigned'] = floatval($by_subject_assigned[$sub] ?? 0);
        $row['diff'] = $row['assigned'] - $row['std'];
    }
    unset($row);
    ksort($by_subject);

    foreach ($by_grade as $g => &$row) {
        $row['diff'] = $row['assigned'] - $row['std'];
        if ($row['class_count'] > 0 && $row['std_per_class'] <= 0) {
            $row['std_per_class'] = $row['std'] / $row['class_count'];
        }
    }
    unset($row);
    ksort($by_grade, SORT_NUMERIC);

    return [
        'total_assigned' => $total_day,
        'total_std' => $total_std,
        'total_diff' => $total_day - $total_std,
        'total_role' => $total_role,
        'slots_ok' => $slots_ok,
        'slots_missing' => $slots_missing,
        'slots_diff' => $slots_diff,
        'slots_conflict' => count($conflict_list),
        'classes_ok' => $classes_ok,
        'classes_total' => count($classes),
        'by_class' => $by_class,
        'by_grade' => $by_grade,
        'by_subject' => $by_subject,
        'missing_list' => $missing_list,
        'diff_list' => $diff_list,
        'conflict_list' => $conflict_list,
    ];
}

function flash($message, $type = 'success') { $_SESSION['flash'] = ['message'=>$message,'type'=>$type]; }

function show_flash() {
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = $f['type'] ?? 'success';
    $msg = htmlspecialchars($f['message'] ?? '', ENT_QUOTES, 'UTF-8');
    $icon = 'bi-check-circle-fill';
    if ($type === 'danger') $icon = 'bi-x-circle-fill';
    elseif ($type === 'warning') $icon = 'bi-exclamation-triangle-fill';
    elseif ($type === 'info') $icon = 'bi-info-circle-fill';
    echo '<div id="pccm-toast" class="pccm-toast pccm-toast-' . htmlspecialchars($type) . '" role="status">'
        . '<i class="bi ' . $icon . ' pccm-toast-icon"></i>'
        . '<span class="pccm-toast-msg">' . $msg . '</span>'
        . '<button type="button" class="pccm-toast-close" onclick="this.parentElement.remove()" aria-label="Đóng">&times;</button>'
        . '</div>';
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
if (!defined('QUOTA_THCS')) define('QUOTA_THCS', get_quota_thcs());
if (!defined('QUOTA_THPT')) define('QUOTA_THPT', get_quota_thpt());
