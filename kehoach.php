<?php
$page_title = 'Kế hoạch chuyên môn';
require_once 'includes/functions.php';
require_once 'includes/cm_docs.php';
require_login();

$tabs = [
    'vanban' => ['Văn bản kế hoạch', 'bi-file-earmark-pdf'],
    'thongbao' => ['Thông báo chuyên môn', 'bi-megaphone'],
    'chitieu' => ['Chỉ tiêu', 'bi-bullseye'],
];
$tab = $_GET['tab'] ?? 'vanban';
if (!isset($tabs[$tab])) $tab = 'vanban';
$section = 'kh_' . $tab;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $file = cm_handle_upload('file');
        $oldFile = trim($_POST['file_path'] ?? '');
        cm_doc_save([
            'id' => trim($_POST['id'] ?? ''),
            'section' => $section,
            'title' => trim($_POST['title'] ?? ''),
            'date' => trim($_POST['date'] ?? date('Y-m-d')),
            'due_date' => trim($_POST['due_date'] ?? ''),
            'day_from' => trim($_POST['day_from'] ?? ''),
            'day_to' => trim($_POST['day_to'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'link' => trim($_POST['link'] ?? ''),
            'file_path' => $file !== '' ? $file : $oldFile,
            'by' => $_SESSION['cds_user']['name'] ?? 'admin',
        ]);
        flash('Đã lưu.');
        header('Location: ' . BASE_URL . 'kehoach.php?tab=' . urlencode($tab));
        exit;
    }
    if ($action === 'delete') {
        cm_doc_delete(trim($_POST['id'] ?? ''));
        flash('Đã xóa.', 'warning');
        header('Location: ' . BASE_URL . 'kehoach.php?tab=' . urlencode($tab));
        exit;
    }
}

$items = cm_docs_by_section($section);
require_once 'includes/header.php';
?>

<h3 class="mb-3"><i class="bi bi-calendar2-week"></i> Kế hoạch chuyên môn</h3>

<ul class="nav nav-pills gap-1 mb-4 flex-wrap">
  <?php foreach ($tabs as $k => $info): ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab===$k?'active':'' ?>" href="?tab=<?= urlencode($k) ?>">
      <i class="bi <?= e($info[1]) ?>"></i> <?= e($info[0]) ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header">Thêm / cập nhật — <?= e($tabs[$tab][0]) ?></div><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="doc_id" value="">
        <input type="hidden" name="file_path" id="doc_file" value="">
        <div class="mb-2">
          <label class="form-label small fw-semibold">Tiêu đề</label>
          <input type="text" name="title" id="doc_title" class="form-control form-control-sm" required>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Ngày ban hành / sự kiện</label>
          <input type="date" name="date" id="doc_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Hạn thực hiện (ngày cụ thể)</label>
          <input type="date" name="due_date" id="doc_due" class="form-control form-control-sm">
          <div class="form-text">Ưu tiên hơn khung hàng tháng. Để trống nếu dùng khung ngày.</div>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label small fw-semibold">Từ ngày (hàng tháng)</label>
            <input type="number" name="day_from" id="doc_from" class="form-control form-control-sm" min="1" max="31" placeholder="VD: 22">
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Đến ngày</label>
            <input type="number" name="day_to" id="doc_to" class="form-control form-control-sm" min="1" max="31" placeholder="VD: 25">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Nội dung / ghi chú</label>
          <textarea name="content" id="doc_content" class="form-control form-control-sm" rows="3"></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Link (Drive, website…)</label>
          <input type="url" name="link" id="doc_link" class="form-control form-control-sm" placeholder="https://…">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Hoặc tải file lên</label>
          <input type="file" name="file" class="form-control form-control-sm">
        </div>
        <button class="btn btn-primary btn-sm w-100" type="submit">Lưu</button>
        <button class="btn btn-outline-secondary btn-sm w-100 mt-1" type="button" onclick="resetForm()">Làm mới</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card"><div class="card-header"><?= e($tabs[$tab][0]) ?> (<?= count($items) ?>)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead><tr><th>Ngày</th><th>Hạn</th><th>Tiêu đề</th><th>Tài liệu</th><th style="min-width:140px"></th></tr></thead>
        <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="5" class="text-muted text-center py-4">Chưa có mục nào.</td></tr>
        <?php else: foreach ($items as $it):
          $dl = cm_resolve_deadline($it);
        ?>
          <tr>
            <td class="small text-nowrap"><?= e($it['date'] ?? '') ?></td>
            <td class="small">
              <?php if ($dl): ?>
                <?= e(date('d/m/Y', strtotime($dl['due_date']))) ?>
                <?php if (!empty($dl['window'])): ?><div class="text-muted"><?= e($dl['window']) ?></div><?php endif; ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <strong><?= e($it['title'] ?? '') ?></strong>
              <?php if (!empty($it['content'])): ?><div class="small text-muted"><?= e(mb_strimwidth($it['content'],0,100,'…','UTF-8')) ?></div><?php endif; ?>
            </td>
            <td class="small">
              <?php if (!empty($it['link'])): ?><a href="<?= e($it['link']) ?>" target="_blank" rel="noopener">Link</a><?php endif; ?>
              <?php if (!empty($it['file_path'])): ?>
                <?= !empty($it['link']) ? ' · ' : '' ?>
                <a href="<?= e(cm_file_url($it['file_path'])) ?>" target="_blank">File</a>
              <?php endif; ?>
              <?php if (empty($it['link']) && empty($it['file_path'])): ?>—<?php endif; ?>
            </td>
            <td class="text-nowrap">
              <button type="button" class="btn btn-sm btn-outline-success" onclick='viewDoc(<?= json_encode($it, JSON_UNESCAPED_UNICODE) ?>)'><i class="bi bi-eye"></i> Xem</button>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick='editDoc(<?= json_encode($it, JSON_UNESCAPED_UNICODE) ?>)'><i class="bi bi-pencil"></i></button>
              <form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= e($it['id']) ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div></div>
  </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="viewTitle">Xem văn bản</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="small text-muted mb-2" id="viewMeta"></div>
    <div id="viewContent" style="white-space:pre-wrap"></div>
    <div class="mt-3" id="viewLinks"></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
</div></div></div>

<script>
function resetForm(){
  ['doc_id','doc_file','doc_title','doc_content','doc_link','doc_due','doc_from','doc_to'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});
  document.getElementById('doc_date').value='<?= date('Y-m-d') ?>';
}
function editDoc(it){
  document.getElementById('doc_id').value=it.id||'';
  document.getElementById('doc_file').value=it.file_path||'';
  document.getElementById('doc_title').value=it.title||'';
  document.getElementById('doc_date').value=it.date||'';
  document.getElementById('doc_due').value=it.due_date||'';
  document.getElementById('doc_from').value=it.day_from||'';
  document.getElementById('doc_to').value=it.day_to||'';
  document.getElementById('doc_content').value=it.content||'';
  document.getElementById('doc_link').value=it.link||'';
  window.scrollTo({top:0,behavior:'smooth'});
}
function viewDoc(it){
  document.getElementById('viewTitle').textContent=it.title||'Xem văn bản';
  var meta=(it.date||'')+(it.due_date?' · Hạn '+it.due_date:'')+(it.day_from?' · Kỳ '+it.day_from+'-'+it.day_to+' hàng tháng':'');
  document.getElementById('viewMeta').textContent=meta;
  document.getElementById('viewContent').textContent=it.content||'(Không có nội dung chữ)';
  var links='';
  if(it.link) links+='<a class="btn btn-sm btn-outline-primary me-2" target="_blank" href="'+it.link+'"><i class="bi bi-link-45deg"></i> Mở link</a>';
  if(it.file_path) links+='<a class="btn btn-sm btn-outline-success" target="_blank" href="<?= BASE_URL ?>data/'+it.file_path+'"><i class="bi bi-download"></i> Tải / xem file</a>';
  document.getElementById('viewLinks').innerHTML=links||'<span class="text-muted">Không có file hoặc link</span>';
  new bootstrap.Modal(document.getElementById('viewModal')).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
