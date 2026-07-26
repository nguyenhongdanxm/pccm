<?php
/** Văn bản / thông báo / chỉ tiêu / báo cáo chuyên môn */
if (!defined('CM_DOCS_FILE')) define('CM_DOCS_FILE', DATA_PATH . '/cm_docs.json');
if (!defined('CM_UPLOAD_DIR')) define('CM_UPLOAD_DIR', DATA_PATH . '/uploads');

function cm_docs_all() {
    if (!is_dir(CM_UPLOAD_DIR)) @mkdir(CM_UPLOAD_DIR, 0755, true);
    return load_json(CM_DOCS_FILE, []);
}

function cm_docs_save_all(array $rows) {
    save_json(CM_DOCS_FILE, array_values($rows));
}

function cm_docs_by_section($section) {
    $out = [];
    foreach (cm_docs_all() as $r) {
        if (($r['section'] ?? '') === $section) $out[] = $r;
    }
    usort($out, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $out;
}

function cm_doc_uid() {
    return 'doc_' . bin2hex(random_bytes(4));
}

function cm_doc_save(array $data) {
    $rows = cm_docs_all();
    $id = $data['id'] ?? '';
    $found = false;
    foreach ($rows as &$r) {
        if (($r['id'] ?? '') === $id) {
            $r = array_merge($r, $data);
            $r['updated_at'] = date('c');
            $found = true;
            break;
        }
    }
    unset($r);
    if (!$found) {
        $id = $id ?: cm_doc_uid();
        $data['id'] = $id;
        $data['created_at'] = date('c');
        $rows[] = $data;
    }
    cm_docs_save_all($rows);
    return $id;
}

function cm_doc_delete($id) {
    $rows = array_values(array_filter(cm_docs_all(), fn($r) => ($r['id'] ?? '') !== $id));
    cm_docs_save_all($rows);
}

/** Xử lý upload file (nếu có) → trả path tương đối trong data/uploads */
function cm_handle_upload($field = 'file') {
    if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return '';
    }
    if (!is_dir(CM_UPLOAD_DIR)) @mkdir(CM_UPLOAD_DIR, 0755, true);
    $name = $_FILES[$field]['name'] ?? 'file';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allow = ['pdf','doc','docx','xls','xlsx','ppt','pptx','png','jpg','jpeg','gif','zip','rar'];
    if ($ext && !in_array($ext, $allow, true)) return '';
    $safe = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $name);
    $dest = CM_UPLOAD_DIR . '/' . $safe;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) return '';
    return 'uploads/' . $safe;
}

function cm_file_url($rel) {
    if (!$rel) return '';
    if (preg_match('#^https?://#i', $rel)) return $rel;
    return BASE_URL . 'data/' . ltrim($rel, '/');
}
