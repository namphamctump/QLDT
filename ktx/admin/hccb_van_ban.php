<?php
// Quan tri "Van ban - Bieu mau" cong khai cho nhanh Hoi CCB (2026-09-18) — CRUD don gian: tieu de,
// mo ta, ngay dang, thu tu hien thi + file dinh kem (PDF/DOC/DOCX/XLS/XLSX). Trang cong khai doc
// tuong ung o public/hccb_van_ban.php.
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh Hoi CCB (includes/hccb_khung.php), the so lieu,
// form them/sua dang the (giu duoc noi dung khi bao loi), bang co bieu tuong loai file + tim nhanh.
// Xu ly luu/xoa/tai file GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'update'], true)) {
    $tieuDe = trim($_POST['tieu_de'] ?? '');
    $moTa = trim($_POST['mo_ta'] ?? '') ?: null;
    $ngayDang = trim($_POST['ngay_dang'] ?? '') ?: date('Y-m-d');
    $thuTu = (int)($_POST['thu_tu'] ?? 0);
    $id = (int)($_POST['id'] ?? 0);

    $filePath = trim($_POST['file_path_hien_tai'] ?? '');
    if (!empty($_FILES['file_dinh_kem']) && $_FILES['file_dinh_kem']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['pdf' => 1, 'doc' => 1, 'docx' => 1, 'xls' => 1, 'xlsx' => 1];
        $ext = strtolower(pathinfo($_FILES['file_dinh_kem']['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowed)) {
            $error = 'File đính kèm chỉ nhận PDF, DOC, DOCX, XLS hoặc XLSX.';
        } elseif ($_FILES['file_dinh_kem']['size'] > 15 * 1024 * 1024) {
            $error = 'File đính kèm tối đa 15MB.';
        } else {
            $dir = UPLOAD_DIR . 'site/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            $filename = 'hccbvb_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($_FILES['file_dinh_kem']['tmp_name'], $dir . $filename)) {
                $filePath = 'site/' . $filename;
            } else {
                $error = 'Không lưu được file đính kèm, kiểm tra quyền ghi thư mục uploads/.';
            }
        }
    } elseif (!empty($_FILES['file_dinh_kem']) && $_FILES['file_dinh_kem']['error'] === UPLOAD_ERR_INI_SIZE) {
        // (2026-10-10) file vuot gioi han upload_max_filesize cua may chu — truoc day bi bao nham "chua chon file"
        $error = 'File đính kèm vượt quá dung lượng máy chủ cho phép.';
    }

    if ($error === '' && $tieuDe === '') {
        $error = 'Vui lòng nhập tiêu đề.';
    }
    if ($error === '' && $filePath === '') {
        $error = 'Vui lòng chọn file đính kèm.';
    }

    if ($error === '') {
        if ($_POST['action'] === 'add') {
            $pdo->prepare('INSERT INTO hccb_van_ban (tieu_de, mo_ta, file_path, ngay_dang, thu_tu) VALUES (?,?,?,?,?)')
                ->execute([$tieuDe, $moTa, $filePath, $ngayDang, $thuTu]);
        } else {
            $pdo->prepare('UPDATE hccb_van_ban SET tieu_de=?, mo_ta=?, file_path=?, ngay_dang=?, thu_tu=? WHERE id=?')
                ->execute([$tieuDe, $moTa, $filePath, $ngayDang, $thuTu, $id]);
        }
        header('Location: hccb_van_ban.php?saved=1'); exit;
    }
}
if (($_GET['action'] ?? '') === 'xoa' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM hccb_van_ban WHERE id = ?')->execute([(int)$_GET['id']]);
    header('Location: hccb_van_ban.php'); exit;
}

$editRow = null;
if (!empty($_GET['edit']) && $_GET['edit'] !== 'new') {
    $stmt = $pdo->prepare('SELECT * FROM hccb_van_ban WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editRow = $stmt->fetch();
}
$showForm = $editRow || (($_GET['edit'] ?? '') === 'new') || $error !== '';

$list = $pdo->query('SELECT * FROM hccb_van_ban ORDER BY thu_tu DESC, ngay_dang DESC, id DESC')->fetchAll();

// Khi bao loi: giu lai noi dung vua nhap thay vi hien lai gia tri cu
$e = $editRow ?: [];
if ($error !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $e = ['tieu_de' => $_POST['tieu_de'] ?? '', 'mo_ta' => $_POST['mo_ta'] ?? '', 'ngay_dang' => $_POST['ngay_dang'] ?? '', 'thu_tu' => $_POST['thu_tu'] ?? 0, 'file_path' => $filePath ?? ($_POST['file_path_hien_tai'] ?? '')] + $e;
}
$demLoai = ['pdf' => 0, 'word' => 0, 'excel' => 0];
$loaiFile = function (string $path): array {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'pdf') return ['pdf', 'PDF', 'red'];
    if ($ext === 'doc' || $ext === 'docx') return ['word', strtoupper($ext), 'blue'];
    if ($ext === 'xls' || $ext === 'xlsx') return ['excel', strtoupper($ext), 'green'];
    return ['', strtoupper($ext ?: '?'), 'gray'];
};
$moiNhat = null;
foreach ($list as $r) { $l = $loaiFile((string)$r['file_path'])[0]; if (isset($demLoai[$l])) { $demLoai[$l]++; } if (!$moiNhat || $r['ngay_dang'] > $moiNhat) { $moiNhat = $r['ngay_dang']; } }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/hccb_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.vb-form{padding:4px 14px 14px;border-bottom:1px solid var(--line);background:#fbfcfe}
.vb-form h3{margin:12px 0 10px;font-size:13px;color:var(--navy);text-transform:uppercase;letter-spacing:.3px}
.vb-form .hs-fg{grid-template-columns:minmax(0,2fr) 160px 120px}
.vb-form input[type=file]{height:auto;padding:6px}
.vb-cur{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;padding:6px 10px;border:1px dashed var(--line);border-radius:7px;background:#fff}
.vb-form .hs-fg label{justify-content:flex-end}
.vb-form .act{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
.vb-ext{display:inline-grid;place-items:center;min-width:44px;height:22px;padding:0 6px;border-radius:5px;font-size:11px;font-weight:700;letter-spacing:.3px}
.vb-ext.red{background:var(--red-soft);color:var(--red)}.vb-ext.blue{background:var(--pri-soft);color:var(--pri)}.vb-ext.green{background:var(--green-soft);color:var(--green)}.vb-ext.gray{background:#eef1f5;color:var(--ink2)}
td.vb-td b{display:block}td.vb-td small{color:var(--ink3);font-size:12.5px}
.vb-act{white-space:nowrap;text-align:right}
@media (max-width:760px){.vb-form .hs-fg{grid-template-columns:1fr}}
</style>
<?php ob_start(); ?>
    <label class="hs-gs"><input id="vbQ" placeholder="Tìm văn bản theo tiêu đề, mô tả…" autocomplete="off"><span class="ico"><svg class="i"><use href="#hi-search"/></svg></span></label>
<?php $khungGiua = ob_get_clean(); ob_start(); ?>
      <a class="hs-tb" href="../public/hccb_van_ban.php" target="_blank"><svg class="i"><use href="#hi-link"/></svg>Xem trang công khai</a>
<?php $khungPhai = ob_get_clean(); ?>
<?php hccb_khung_mo('hccb_van_ban.php', 'VĂN BẢN – BIỂU MẪU', 'Hội Cựu chiến binh · trang công khai', $khungGiua, $khungPhai); ?>

      <?php if (!empty($_GET['saved'])): ?><div class="hs-msg ok"><span>✅ Đã lưu.</span></div><?php endif; ?>
      <?php if ($error): ?><div class="hs-msg warn"><span>⚠️ <?= h($error) ?></span></div><?php endif; ?>

      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-file"/></svg></div>
            <div><div class="num"><?= count($list) ?></div><div class="lb">Văn bản đang đăng</div></div></div>
          <div class="sb"><div><span>Mới nhất: <?= $moiNhat ? h(date('d/m/Y', strtotime($moiNhat))) : '—' ?></span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--red-soft);color:var(--red)"><svg class="i"><use href="#hi-file"/></svg></div>
            <div><div class="num" style="color:var(--red)"><?= $demLoai['pdf'] ?></div><div class="lb">File PDF</div></div></div>
          <div class="sb"><div><span>Xem trực tiếp trên trình duyệt</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-file"/></svg></div>
            <div><div class="num"><?= $demLoai['word'] ?></div><div class="lb">File Word</div></div></div>
          <div class="sb"><div><span>Biểu mẫu để tải về điền</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-file"/></svg></div>
            <div><div class="num" style="color:var(--green)"><?= $demLoai['excel'] ?></div><div class="lb">File Excel</div></div></div>
          <div class="sb"><div><span>Danh sách, bảng biểu</span></div></div>
        </div>
      </div>

      <div class="hs-card">
        <div class="hs-tool">
          <h2>DANH SÁCH VĂN BẢN</h2>
          <a class="hs-btn pri" href="?edit=new"><svg class="i"><use href="#hi-plus"/></svg>Thêm văn bản</a>
          <span class="hs-sp"></span><span style="font-size:12.5px;color:var(--ink2)"><span id="vbDem"><?= count($list) ?></span> văn bản · số thứ tự lớn hiện trước</span>
        </div>
        <?php if ($showForm): ?>
        <form method="post" enctype="multipart/form-data" class="vb-form">
          <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'add' ?>">
          <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>
          <input type="hidden" name="file_path_hien_tai" value="<?= h($e['file_path'] ?? '') ?>">
          <h3><?= $editRow ? 'Sửa văn bản' : 'Thêm văn bản' ?></h3>
          <div class="hs-fg">
            <label>Tiêu đề *<input type="text" name="tieu_de" value="<?= h($e['tieu_de'] ?? '') ?>" required></label>
            <label>Ngày đăng<input type="date" name="ngay_dang" value="<?= h($e['ngay_dang'] ?? date('Y-m-d')) ?>"></label>
            <label>Thứ tự <span class="hint">lớn hiện trước</span><input type="number" name="thu_tu" value="<?= h((string)($e['thu_tu'] ?? 0)) ?>"></label>
            <label class="s3">Mô tả ngắn <span class="hint">tuỳ chọn</span><input type="text" name="mo_ta" value="<?= h($e['mo_ta'] ?? '') ?>"></label>
            <label class="s3">File đính kèm <span class="hint">PDF, DOC, DOCX, XLS, XLSX — tối đa 15MB<?= !empty($e['file_path']) ? '; chọn file mới nếu muốn thay thế' : '' ?></span>
              <?php if (!empty($e['file_path'])): $lf = $loaiFile((string)$e['file_path']); ?><span class="vb-cur"><span class="vb-ext <?= $lf[2] ?>"><?= h($lf[1]) ?></span>File hiện tại<a href="<?= UPLOAD_URL . h($e['file_path']) ?>" target="_blank" style="margin-left:auto;color:var(--pri)">Mở ↗</a></span><?php endif; ?>
              <input type="file" name="file_dinh_kem" accept=".pdf,.doc,.docx,.xls,.xlsx"<?= empty($e['file_path']) ? ' required' : '' ?>></label>
          </div>
          <div class="act"><a href="hccb_van_ban.php" class="hs-btn">Huỷ</a><button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg>Lưu</button></div>
        </form>
        <?php endif; ?>
        <div class="hs-tw">
          <table class="hs-grid">
            <thead><tr><th class="c" style="width:70px">Loại</th><th>Tiêu đề / mô tả</th><th class="c">Ngày đăng</th><th class="c">Thứ tự</th><th></th></tr></thead>
            <tbody id="vbBody">
            <?php foreach ($list as $r): $lf = $loaiFile((string)$r['file_path']); ?>
            <tr data-q="<?= h(mb_strtolower($r['tieu_de'] . ' ' . $r['mo_ta'], 'UTF-8')) ?>"<?= $editRow && (int)$editRow['id'] === (int)$r['id'] ? ' style="background:#d9e8fb"' : '' ?>>
              <td class="c"><span class="vb-ext <?= $lf[2] ?>"><?= h($lf[1]) ?></span></td>
              <td class="vb-td"><b><?= h($r['tieu_de']) ?></b><?= $r['mo_ta'] ? '<small>' . h($r['mo_ta']) . '</small>' : '' ?></td>
              <td class="c" style="white-space:nowrap"><?= h(date('d/m/Y', strtotime($r['ngay_dang']))) ?></td>
              <td class="c"><?= (int)$r['thu_tu'] ?></td>
              <td class="vb-act">
                <a class="hs-btn sm" href="<?= UPLOAD_URL . h($r['file_path']) ?>" target="_blank"><svg class="i"><use href="#hi-down"/></svg>Tải về</a>
                <a class="hs-btn sm" href="?edit=<?= (int)$r['id'] ?>"><svg class="i"><use href="#hi-edit"/></svg>Sửa</a>
                <a class="hs-btn sm r" href="?action=xoa&amp;id=<?= (int)$r['id'] ?>" onclick="return confirm('Xoá văn bản này?')"><svg class="i"><use href="#hi-trash"/></svg>Xoá</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$list): ?><tr><td colspan="5" class="hs-empty">Chưa có văn bản nào — bấm <b>Thêm văn bản</b>.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php ktx_khung_dong(); ?>
<script>
(function () {
  var q = document.getElementById('vbQ'), dem = document.getElementById('vbDem');
  q.addEventListener('input', function () {
    var t = q.value.trim().toLowerCase(), n = 0;
    document.querySelectorAll('#vbBody tr[data-q]').forEach(function (tr) { var ok = !t || tr.dataset.q.indexOf(t) >= 0; tr.hidden = !ok; if (ok) n++; });
    dem.textContent = n;
  });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
