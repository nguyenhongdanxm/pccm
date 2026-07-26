<?php
$page_title = 'Báo cáo chuyên môn';
require_once 'includes/functions.php';
require_once 'includes/cm_docs.php';
require_login();

$tabs = [
    'dinhky' => ['Báo cáo định kỳ', 'bi-calendar-month'],
    'tiendo' => ['Tiến độ chương trình', 'bi-graph-up'],
    'dugio' => ['Dự giờ', 'bi-eye'],
    'kythi' => ['Kết quả cuộc thi', 'bi-trophy'],
];
$tab = $_GET['tab'] ?? 'dinhky';
if (!isset($tabs[$tab])) $tab = 'dinhky';
if ($tab === 'thang') $tab = 'dinhky';
$section = 'bc_' . $tab;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $file = cm_handle_upload('file');
        $oldFile = trim($_POST['file_path'] ?? '');
        $kind = trim($_POST['kind'] ?? 'report');
        cm_doc_save([
            'id' => trim($_POST['id'] ?? ''),
            'section' => $section,
            'kind' => $kind,
            'parent_id' => trim($_POST['parent_id'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'date' => trim($_POST['date'] ?? date('Y-m-d')),
            'month' => trim($_POST['month'] ?? ''),
            'due_date' => trim($_POST['due_date'] ?? ''),
            'day_from' => trim($_POST['day_from'] ?? ''),
            'day_to' => trim($_POST['day_to'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'link' => trim($_POST['link'] ?? ''),
            'file_path' => $file !== '' ? $file : $oldFile,
            'by' => $_SESSION['cds_user']['name'] ?? ($_SESSION['pccm_admin'] ? 'admin' : ''),
        ]);
        flash('Đã lưu.');
        $redir = BASE_URL . 'baocao.php?tab=' . urlencode($tab);
        if (!empty($_POST['parent_id'])) $redir .= '&contest=' . urlencode($_POST['parent_id']);
        header('Location: ' . $redir);
        exit;
    }
    if ($action === 'delete') {
        cm_doc_delete(trim($_POST['id'] ?? ''));
        flash('Đã xóa.', 'warning');
        header('Location: ' . BASE_URL . 'baocao.php?tab=' . urlencode($tab));
        exit;
    }
}

$all = cm_docs_by_section($section);
if ($tab === 'dinhky') {
    $all = array_merge($all, cm_docs_by_section('bc_thang'));
    usort($all, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
}

$contest_id = $_GET['contest'] ?? '';
$contests = [];
$results = [];
$items = [];
if ($tab === 'kythi') {
    foreach ($all as $r) {
        if (($r['kind'] ?? 'contest') === 'result' || !empty($r['parent_id'])) $results[] = $r;
        else $contests[] = $r;
    }
} else {
    $items = $all;
}

require_once 'includes/header.php';

function cm_view_btns($it) {
    $html = '';
    if (!empty($it['content']) || !empty($it['link']) || !empty($it['file_path'])) {
        $html .= '<button type="button" class="btn btn-sm btn-outline-success" title="Xem" onclick=\'viewDoc(' . json_encode($it, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) . ')\'><i class="bi bi-eye"></i> Xem</button> ';
    }
    return $html;
}
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

<?php if ($tab !== 'kythi'): ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header">Ghi nhận — <?= e($tabs[$tab][0]) ?></div><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="kind" value="report">
        <input type="hidden" name="id" id="doc_id" value="">
        <input type="hidden" name="file_path" id="doc_file" value="">
        <div class="mb-2"><label class="form-label small fw-semibold">Tiêu đề</label>
          <input type="text" name="title" id="doc_title" class="form-control form-control-sm" required></div>
        <div class="row g-2 mb-2">
          <div class="col-6"><label class="form-label small fw-semibold">Ngày</label>
            <input type="date" name="date" id="doc_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
          <div class="col-6"><label class="form-label small fw-semibold">Kỳ / tháng</label>
            <input type="month" name="month" id="doc_month" class="form-control form-control-sm"></div>
        </div>
        <div class="mb-2"><label class="form-label small fw-semibold">Hạn nộp (ngày cụ thể)</label>
          <input type="date" name="due_date" id="doc_due" class="form-control form-control-sm"></div>
        <?php if ($tab === 'dinhky'): ?>
        <div class="row g-2 mb-2">
          <div class="col-6"><label class="form-label small fw-semibold">Từ ngày (hàng tháng)</label>
            <input type="number" name="day_from" id="doc_from" class="form-control form-control-sm" min="1" max="31" placeholder="22"></div>
          <div class="col-6"><label class="form-label small fw-semibold">Đến ngày</label>
            <input type="number" name="day_to" id="doc_to" class="form-control form-control-sm" min="1" max="31" placeholder="25"></div>
        </div>
        <div class="form-text mb-2">VD: 22–25 → nhắc mỗi tháng trong kỳ nộp.</div>
        <?php else: ?>
        <input type="hidden" name="day_from" id="doc_from" value="">
        <input type="hidden" name="day_to" id="doc_to" value="">
        <?php endif; ?>
        <div class="mb-2"><label class="form-label small fw-semibold">Nội dung</label>
          <textarea name="content" id="doc_content" class="form-control form-control-sm" rows="4"></textarea></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Chèn link</label>
          <input type="url" name="link" id="doc_link" class="form-control form-control-sm" placeholder="https://…"></div>
        <div class="mb-3"><label class="form-label small fw-semibold">Tải file</label>
          <input type="file" name="file" class="form-control form-control-sm"></div>
        <button class="btn btn-primary btn-sm w-100" type="submit">Lưu</button>
        <button class="btn btn-outline-secondary btn-sm w-100 mt-1" type="button" onclick="resetForm()">Làm mới</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card"><div class="card-header"><?= e($tabs[$tab][0]) ?> (<?= count($items) ?>)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead><tr><th>Ngày</th><th>Hạn</th><th>Tiêu đề</th><th>Tài liệu</th><th></th></tr></thead>
        <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="5" class="text-muted text-center py-4">Chưa có mục nào.</td></tr>
        <?php else: foreach ($items as $it):
          $dl = cm_resolve_deadline($it);
        ?>
          <tr>
            <td class="small text-nowrap"><?= e($it['date'] ?? '') ?><?php if (!empty($it['month'])): ?><div class="text-muted"><?= e($it['month']) ?></div><?php endif; ?></td>
            <td class="small">
              <?php if ($dl): ?>
                <?= e(date('d/m/Y', strtotime($dl['due_date']))) ?>
                <?php if (!empty($dl['window'])): ?><div class="text-muted"><?= e($dl['window']) ?></div><?php endif; ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><strong><?= e($it['title'] ?? '') ?></strong>
              <?php if (!empty($it['content'])): ?><div class="small text-muted"><?= e(mb_strimwidth($it['content'],0,100,'…','UTF-8')) ?></div><?php endif; ?>
            </td>
            <td class="small">
              <?php if (!empty($it['link'])): ?><a href="<?= e($it['link']) ?>" target="_blank">Link</a><?php endif; ?>
              <?php if (!empty($it['file_path'])): ?><?= !empty($it['link'])?' · ':'' ?><a href="<?= e(cm_file_url($it['file_path'])) ?>" target="_blank">File</a><?php endif; ?>
              <?php if (empty($it['link']) && empty($it['file_path'])): ?>—<?php endif; ?>
            </td>
            <td class="text-nowrap">
              <?= cm_view_btns($it) ?>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick='editDoc(<?= json_encode($it, JSON_UNESCAPED_UNICODE) ?>)'><i class="bi bi-pencil"></i></button>
              <form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($it['id']) ?>">
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

<?php else: ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-header">Tạo kỳ thi / cuộc thi</div><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="kind" value="contest">
        <input type="hidden" name="id" id="ct_id" value="">
        <input type="hidden" name="file_path" id="ct_file" value="">
        <div class="mb-2"><label class="form-label small fw-semibold">Tên kỳ thi</label>
          <input type="text" name="title" id="ct_title" class="form-control form-control-sm" required></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Ngày</label>
          <input type="date" name="date" id="ct_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Mô tả</label>
          <textarea name="content" id="ct_content" class="form-control form-control-sm" rows="3"></textarea></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Link</label>
          <input type="url" name="link" id="ct_link" class="form-control form-control-sm"></div>
        <div class="mb-3"><label class="form-label small fw-semibold">File</label>
          <input type="file" name="file" class="form-control form-control-sm"></div>
        <button class="btn btn-primary btn-sm w-100" type="submit">Lưu kỳ thi</button>
      </form>
    </div></div>
    <?php if ($contest_id):
      $ct = null;
      foreach ($contests as $c) if (($c['id']??'') === $contest_id) { $ct = $c; break; }
    ?>
    <div class="card"><div class="card-header bg-success">Nhập kết quả — <?= e($ct['title'] ?? '') ?></div><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="kind" value="result">
        <input type="hidden" name="parent_id" value="<?= e($contest_id) ?>">
        <div class="mb-2"><label class="form-label small fw-semibold">Tiêu đề kết quả</label>
          <input type="text" name="title" class="form-control form-control-sm" required></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Ngày</label>
          <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Nội dung</label>
          <textarea name="content" class="form-control form-control-sm" rows="4"></textarea></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Link</label>
          <input type="url" name="link" class="form-control form-control-sm"></div>
        <div class="mb-3"><label class="form-label small fw-semibold">File</label>
          <input type="file" name="file" class="form-control form-control-sm"></div>
        <button class="btn btn-success btn-sm w-100" type="submit">Lưu kết quả</button>
        <a href="?tab=kythi" class="btn btn-outline-secondary btn-sm w-100 mt-1">Đóng</a>
      </form>
    </div></div>
    <?php endif; ?>
  </div>
  <div class="col-lg-8">
    <div class="card"><div class="card-header">Danh sách kỳ thi (<?= count($contests) ?>)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead><tr><th>Ngày</th><th>Kỳ thi</th><th>Kết quả</th><th></th></tr></thead>
        <tbody>
        <?php if (!$contests): ?>
          <tr><td colspan="4" class="text-muted text-center py-4">Chưa có kỳ thi.</td></tr>
        <?php else: foreach ($contests as $c):
          $nRes = count(array_filter($results, fn($r) => ($r['parent_id']??'') === ($c['id']??'')));
        ?>
          <tr class="<?= $contest_id===($c['id']??'')?'table-success':'' ?>">
            <td class="small"><?= e($c['date']??'') ?></td>
            <td><strong><?= e($c['title']??'') ?></strong></td>
            <td><span class="badge bg-secondary"><?= $nRes ?></span></td>
            <td class="text-nowrap">
              <?= cm_view_btns($c) ?>
              <a class="btn btn-sm btn-success" href="?tab=kythi&contest=<?= urlencode($c['id']) ?>"><i class="bi bi-plus-lg"></i> Kết quả</a>
              <form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($c['id']) ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php if ($contest_id === ($c['id']??'')): foreach ($results as $r): if (($r['parent_id']??'') !== $c['id']) continue; ?>
          <tr class="table-light">
            <td class="small ps-4"><?= e($r['date']??'') ?></td>
            <td class="ps-4"><?= e($r['title']??'') ?></td>
            <td class="small"><?php if (!empty($r['link'])): ?><a href="<?= e($r['link']) ?>" target="_blank">Link</a><?php endif; ?></td>
            <td><?= cm_view_btns($r) ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Xóa?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($r['id']) ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div></div>
  </div>
</div>
<?php endif; ?>

<div class="modal fade" id="viewModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="viewTitle">Xem</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="small text-muted mb-2" id="viewMeta"></div>
    <div id="viewContent" style="white-space:pre-wrap"></div>
    <div class="mt-3" id="viewLinks"></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
</div></div></div>

<script>
function resetForm(){
  ['doc_id','doc_file','doc_title','doc_content','doc_link','doc_month','doc_due','doc_from','doc_to'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});
  var d=document.getElementById('doc_date'); if(d) d.value='<?= date('Y-m-d') ?>';
}
function editDoc(it){
  document.getElementById('doc_id').value=it.id||'';
  document.getElementById('doc_file').value=it.file_path||'';
  document.getElementById('doc_title').value=it.title||'';
  document.getElementById('doc_date').value=it.date||'';
  var m=document.getElementById('doc_month'); if(m) m.value=it.month||'';
  var due=document.getElementById('doc_due'); if(due) due.value=it.due_date||'';
  var f=document.getElementById('doc_from'); if(f) f.value=it.day_from||'';
  var t=document.getElementById('doc_to'); if(t) t.value=it.day_to||'';
  document.getElementById('doc_content').value=it.content||'';
  document.getElementById('doc_link').value=it.link||'';
  window.scrollTo({top:0,behavior:'smooth'});
}
function viewDoc(it){
  document.getElementById('viewTitle').textContent=it.title||'Xem';
  document.getElementById('viewMeta').textContent=(it.date||'')+(it.due_date?' · Hạn '+it.due_date:'')+(it.day_from?' · Kỳ '+it.day_from+'-'+it.day_to+'/tháng':'');
  document.getElementById('viewContent').textContent=it.content||'(Không có nội dung)';
  var links='';
  if(it.link) links+='<a class="btn btn-sm btn-outline-primary me-2" href="'+it.link+'" target="_blank"><i class="bi bi-link-45deg"></i> Link</a>';
  if(it.file_path) links+='<a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>data/'+it.file_path+'" target="_blank"><i class="bi bi-download"></i> File</a>';
  document.getElementById('viewLinks').innerHTML=links||'<span class="text-muted">Không có file/link</span>';
  new bootstrap.Modal(document.getElementById('viewModal')).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
