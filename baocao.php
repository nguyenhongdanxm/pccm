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
$teachers = get_teachers_sorted();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $file = cm_handle_upload('file');
        $oldFile = trim($_POST['file_path'] ?? '');
        $kind = trim($_POST['kind'] ?? 'report');
        $hasDeadline = !empty($_POST['has_deadline']);
        $hasAssignees = !empty($_POST['has_assignees']);
        $assignees = [];
        if ($hasAssignees && !empty($_POST['assignees']) && is_array($_POST['assignees'])) {
            $assignees = array_values(array_filter(array_map('trim', $_POST['assignees'])));
        }
        cm_doc_save([
            'id' => trim($_POST['id'] ?? ''),
            'section' => $section,
            'kind' => $kind,
            'parent_id' => trim($_POST['parent_id'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'date' => trim($_POST['date'] ?? date('Y-m-d')),
            'month' => trim($_POST['month'] ?? ''),
            'has_deadline' => $hasDeadline,
            'due_date' => $hasDeadline ? trim($_POST['due_date'] ?? '') : '',
            'day_from' => $hasDeadline ? trim($_POST['day_from'] ?? '') : '',
            'day_to' => $hasDeadline ? trim($_POST['day_to'] ?? '') : '',
            'has_assignees' => $hasAssignees,
            'assignees' => $assignees,
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
    $html = '<button type="button" class="btn btn-sm btn-outline-success" title="Xem" onclick=\'viewDoc(' . json_encode($it, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) . ')\'><i class="bi bi-eye"></i></button> ';
    return $html;
}
?>

<h3 class="mb-3"><i class="bi bi-file-earmark-text"></i> Báo cáo chuyên môn</h3>

<ul class="nav nav-pills gap-1 mb-4 flex-wrap">
  <?php foreach ($tabs as $k => $info): ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab===$k?'active':'' ?>" href="<?= BASE_URL ?>baocao.php?tab=<?= urlencode($k) ?>">
      <i class="bi <?= e($info[1]) ?>"></i> <?= e($info[0]) ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<?php if ($tab !== 'kythi'): ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header">Ghi nhận — <?= e($tabs[$tab][0]) ?></div><div class="card-body">
      <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>baocao.php?tab=<?= urlencode($tab) ?>">
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

        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="has_deadline" value="1" id="chkDeadline" onchange="toggleDeadline()">
          <label class="form-check-label small fw-semibold" for="chkDeadline">Có hạn nộp / hạn báo cáo</label>
        </div>
        <div id="boxDeadline" class="border rounded p-2 mb-2 bg-light" style="display:none">
          <div class="mb-2">
            <label class="form-label small">Hạn (ngày cụ thể)</label>
            <input type="date" name="due_date" id="doc_due" class="form-control form-control-sm">
          </div>
          <?php if ($tab === 'dinhky'): ?>
          <div class="row g-2">
            <div class="col-6"><label class="form-label small">Từ ngày (hàng tháng)</label>
              <input type="number" name="day_from" id="doc_from" class="form-control form-control-sm" min="1" max="31" placeholder="22"></div>
            <div class="col-6"><label class="form-label small">Đến ngày</label>
              <input type="number" name="day_to" id="doc_to" class="form-control form-control-sm" min="1" max="31" placeholder="25"></div>
          </div>
          <?php else: ?>
          <input type="hidden" name="day_from" id="doc_from" value="">
          <input type="hidden" name="day_to" id="doc_to" value="">
          <?php endif; ?>
        </div>

        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="has_assignees" value="1" id="chkAssign" onchange="toggleAssign()">
          <label class="form-check-label small fw-semibold" for="chkAssign">Chỉ định người thực hiện</label>
        </div>
        <div id="boxAssign" class="border rounded p-2 mb-2 bg-light" style="display:none">
          <select name="assignees[]" id="doc_assignees" class="form-select form-select-sm" multiple size="7">
            <?php foreach ($teachers as $t): ?>
            <option value="<?= e($t) ?>"><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Ctrl/Cmd + click để chọn nhiều GV.</div>
        </div>

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
        <thead><tr><th>Ngày</th><th>Hạn</th><th>Tiêu đề</th><th>Người TH</th><th></th></tr></thead>
        <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="5" class="text-muted text-center py-4">Chưa có mục nào.</td></tr>
        <?php else: foreach ($items as $it):
          $dl = (!empty($it['has_deadline']) || !empty($it['due_date']) || !empty($it['day_from'])) ? cm_resolve_deadline($it) : null;
          $asg = $it['assignees'] ?? []; if (!is_array($asg)) $asg = $asg ? [$asg] : [];
        ?>
          <tr>
            <td class="small text-nowrap"><?= e($it['date'] ?? '') ?></td>
            <td class="small"><?php if ($dl): ?><?= e(date('d/m/Y', strtotime($dl['due_date']))) ?><?php if (!empty($dl['window'])): ?><div class="text-muted"><?= e($dl['window']) ?></div><?php endif; ?><?php else: ?>—<?php endif; ?></td>
            <td><strong><?= e($it['title'] ?? '') ?></strong></td>
            <td class="small"><?= $asg ? e(implode(', ', $asg)) : '—' ?></td>
            <td class="text-nowrap">
              <?= cm_view_btns($it) ?>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick='editDoc(<?= json_encode($it, JSON_UNESCAPED_UNICODE) ?>)'><i class="bi bi-pencil"></i></button>
              <form method="post" class="d-inline" action="<?= BASE_URL ?>baocao.php?tab=<?= urlencode($tab) ?>" onsubmit="return confirm('Xóa?')">
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
      <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>baocao.php?tab=kythi">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="kind" value="contest">
        <div class="mb-2"><label class="form-label small fw-semibold">Tên kỳ thi</label>
          <input type="text" name="title" class="form-control form-control-sm" required></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Ngày</label>
          <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Mô tả</label>
          <textarea name="content" class="form-control form-control-sm" rows="3"></textarea></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Link</label>
          <input type="url" name="link" class="form-control form-control-sm"></div>
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
      <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>baocao.php?tab=kythi&contest=<?= urlencode($contest_id) ?>">
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
        <a href="<?= BASE_URL ?>baocao.php?tab=kythi" class="btn btn-outline-secondary btn-sm w-100 mt-1">Đóng</a>
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
              <a class="btn btn-sm btn-success" href="<?= BASE_URL ?>baocao.php?tab=kythi&contest=<?= urlencode($c['id']) ?>"><i class="bi bi-plus-lg"></i> Kết quả</a>
              <form method="post" class="d-inline" action="<?= BASE_URL ?>baocao.php?tab=kythi" onsubmit="return confirm('Xóa?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($c['id']) ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php if ($contest_id === ($c['id']??'')): foreach ($results as $r): if (($r['parent_id']??'') !== $c['id']) continue; ?>
          <tr class="table-light">
            <td class="small ps-4"><?= e($r['date']??'') ?></td>
            <td class="ps-4"><?= e($r['title']??'') ?></td>
            <td></td>
            <td><?= cm_view_btns($r) ?>
              <form method="post" class="d-inline" action="<?= BASE_URL ?>baocao.php?tab=kythi&contest=<?= urlencode($contest_id) ?>" onsubmit="return confirm('Xóa?')">
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
    <div id="viewAssignees" class="mb-2 small"></div>
    <div id="viewContent" style="white-space:pre-wrap"></div>
    <div class="mt-3" id="viewLinks"></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
</div></div></div>

<script>
function toggleDeadline(){
  var b=document.getElementById('boxDeadline');
  if(b) b.style.display=document.getElementById('chkDeadline').checked?'block':'none';
}
function toggleAssign(){
  var b=document.getElementById('boxAssign');
  if(b) b.style.display=document.getElementById('chkAssign').checked?'block':'none';
}
function resetForm(){
  ['doc_id','doc_file','doc_title','doc_content','doc_link','doc_month','doc_due','doc_from','doc_to'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});
  var d=document.getElementById('doc_date'); if(d) d.value='<?= date('Y-m-d') ?>';
  var c1=document.getElementById('chkDeadline'); if(c1){c1.checked=false;toggleDeadline();}
  var c2=document.getElementById('chkAssign'); if(c2){c2.checked=false;toggleAssign();}
  var sel=document.getElementById('doc_assignees'); if(sel) Array.from(sel.options).forEach(function(o){o.selected=false;});
}
function editDoc(it){
  document.getElementById('doc_id').value=it.id||'';
  document.getElementById('doc_file').value=it.file_path||'';
  document.getElementById('doc_title').value=it.title||'';
  document.getElementById('doc_date').value=it.date||'';
  var m=document.getElementById('doc_month'); if(m) m.value=it.month||'';
  document.getElementById('doc_content').value=it.content||'';
  document.getElementById('doc_link').value=it.link||'';
  var hasDl=!!(it.has_deadline||it.due_date||it.day_from);
  var c1=document.getElementById('chkDeadline'); if(c1){c1.checked=hasDl;toggleDeadline();}
  var due=document.getElementById('doc_due'); if(due) due.value=it.due_date||'';
  var f=document.getElementById('doc_from'); if(f) f.value=it.day_from||'';
  var t=document.getElementById('doc_to'); if(t) t.value=it.day_to||'';
  var asg=it.assignees||[]; if(typeof asg==='string') asg=asg?[asg]:[];
  var c2=document.getElementById('chkAssign'); if(c2){c2.checked=!!(it.has_assignees||asg.length);toggleAssign();}
  var sel=document.getElementById('doc_assignees');
  if(sel) Array.from(sel.options).forEach(function(o){o.selected=asg.indexOf(o.value)>=0;});
  window.scrollTo({top:0,behavior:'smooth'});
}
function viewDoc(it){
  document.getElementById('viewTitle').textContent=it.title||'Xem';
  document.getElementById('viewMeta').textContent=(it.date||'')+(it.due_date?' · Hạn '+it.due_date:'');
  var asg=it.assignees||[];
  document.getElementById('viewAssignees').innerHTML=asg.length?'<strong>Người TH:</strong> '+asg.join(', '):'';
  document.getElementById('viewContent').textContent=it.content||'(Không có nội dung)';
  var links='';
  if(it.link) links+='<a class="btn btn-sm btn-outline-primary me-2" href="'+it.link+'" target="_blank">Link</a>';
  if(it.file_path) links+='<a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>data/'+it.file_path+'" target="_blank">File</a>';
  document.getElementById('viewLinks').innerHTML=links||'<span class="text-muted">Không có file/link</span>';
  new bootstrap.Modal(document.getElementById('viewModal')).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
