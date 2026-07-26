<?php
$page_title = 'Báo cáo chuyên môn';
require_once 'includes/functions.php';
require_once 'includes/cm_docs.php';
require_login();

$tabs = [
    'thang' => ['Báo cáo tháng', 'bi-calendar-month'],
    'tiendo' => ['Tiến độ chương trình', 'bi-graph-up'],
    'ncbh' => ['NCBH', 'bi-people'],
    'steam' => ['STEAM', 'bi-lightbulb'],
    'khkt' => ['KHKT', 'bi-cpu'],
    'clb' => ['CLB', 'bi-collection'],
    'dugio' => ['Dự giờ', 'bi-eye'],
    'kythi' => ['Kỳ thi / cuộc thi GV–HS', 'bi-trophy'],
];
$tab = $_GET['tab'] ?? 'thang';
if (!isset($tabs[$tab])) $tab = 'thang';
$section = 'bc_' . $tab;

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
            'month' => trim($_POST['month'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'link' => trim($_POST['link'] ?? ''),
            'file_path' => $file !== '' ? $file : $oldFile,
            'by' => $_SESSION['cds_user']['name'] ?? 'admin',
        ]);
        flash('Đã lưu báo cáo.');
        header('Location: ' . BASE_URL . 'baocao.php?tab=' . urlencode($tab));
        exit;
    }
    if ($action === 'delete') {
        cm_doc_delete(trim($_POST['id'] ?? ''));
        flash('Đã xóa.', 'warning');
        header('Location: ' . BASE_URL . 'baocao.php?tab=' . urlencode($tab));
        exit;
    }
}

$items = cm_docs_by_section($section);
require_once 'includes/header.php';
?>

<h3 class="mb-3"><i class="bi bi-file-earmark-text"></i> Báo cáo chuyên môn</h3>

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
    <div class="card"><div class="card-header">Ghi nhận — <?= e($tabs[$tab][0]) ?></div><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="doc_id" value="">
        <input type="hidden" name="file_path" id="doc_file" value="">
        <div class="mb-2">
          <label class="form-label small fw-semibold">Tiêu đề</label>
          <input type="text" name="title" id="doc_title" class="form-control form-control-sm" required
                 placeholder="<?= $tab==='thang'?'VD: Báo cáo tháng 9/2025':'' ?>">
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label small fw-semibold">Ngày</label>
            <input type="date" name="date" id="doc_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Tháng (nếu có)</label>
            <input type="month" name="month" id="doc_month" class="form-control form-control-sm">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Nội dung (gõ trực tiếp)</label>
          <textarea name="content" id="doc_content" class="form-control form-control-sm" rows="5" placeholder="Nhập nội dung báo cáo…"></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Chèn link</label>
          <input type="url" name="link" id="doc_link" class="form-control form-control-sm" placeholder="https://…">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Tải file lên</label>
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
        <thead><tr><th>Ngày</th><th>Tiêu đề / nội dung</th><th>Tài liệu</th><th></th></tr></thead>
        <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="4" class="text-muted text-center py-4">Chưa có báo cáo.</td></tr>
        <?php else: foreach ($items as $it): ?>
          <tr>
            <td class="small text-nowrap">
              <?= e($it['date'] ?? '') ?>
              <?php if (!empty($it['month'])): ?><div class="text-muted"><?= e($it['month']) ?></div><?php endif; ?>
            </td>
            <td>
              <strong><?= e($it['title'] ?? '') ?></strong>
              <?php if (!empty($it['content'])): ?>
                <div class="small text-muted mt-1" style="white-space:pre-wrap"><?= e(mb_strimwidth($it['content'],0,200,'…','UTF-8')) ?></div>
              <?php endif; ?>
            </td>
            <td class="small">
              <?php if (!empty($it['link'])): ?><a href="<?= e($it['link']) ?>" target="_blank" rel="noopener">Link</a><?php endif; ?>
              <?php if (!empty($it['file_path'])): ?>
                <?= !empty($it['link'])?' · ':'' ?>
                <a href="<?= e(cm_file_url($it['file_path'])) ?>" target="_blank">File</a>
              <?php endif; ?>
              <?php if (empty($it['link']) && empty($it['file_path'])): ?>—<?php endif; ?>
            </td>
            <td class="text-nowrap">
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

<script>
function resetForm(){
  ['doc_id','doc_file','doc_title','doc_content','doc_link','doc_month'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});
  document.getElementById('doc_date').value='<?= date('Y-m-d') ?>';
}
function editDoc(it){
  document.getElementById('doc_id').value=it.id||'';
  document.getElementById('doc_file').value=it.file_path||'';
  document.getElementById('doc_title').value=it.title||'';
  document.getElementById('doc_date').value=it.date||'';
  document.getElementById('doc_month').value=it.month||'';
  document.getElementById('doc_content').value=it.content||'';
  document.getElementById('doc_link').value=it.link||'';
  window.scrollTo({top:0,behavior:'smooth'});
}
</script>
<?php require_once 'includes/footer.php'; ?>
