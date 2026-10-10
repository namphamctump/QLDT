<?php
// Quan ly "Ban quan ly Ky tuc xa" (can bo phu trach, KHONG phai hoc vien) — CRUD + xuat danh
// sach Excel + xuat Quyet dinh thanh lap (Word) theo dung mau QD 3868/QD-DHYDCT (2026-09-14).
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh KTX (includes/ktx_khung.php), the so lieu theo vai
// tro, form them/sua thu gon, bang co nhan vai tro. Xu ly luu/xoa GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

$VAI_TRO_LIST = ['Trưởng ban', 'Phó trưởng ban', 'Thành viên'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'luu') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $hocHam = trim($_POST['hoc_ham_hoc_vi'] ?? '') ?: null;
    $hoTen = trim($_POST['ho_ten'] ?? '');
    $donVi = trim($_POST['don_vi_chuc_vu'] ?? '') ?: null;
    $vaiTro = in_array($_POST['vai_tro'] ?? '', $VAI_TRO_LIST, true) ? $_POST['vai_tro'] : 'Thành viên';
    $thuTu = (int)($_POST['thu_tu'] ?? 0);
    $ghiChu = trim($_POST['ghi_chu'] ?? '') ?: null;
    if ($hoTen !== '') {
        if ($id) {
            $pdo->prepare('UPDATE ktx_ban_quan_ly SET hoc_ham_hoc_vi=?, ho_ten=?, don_vi_chuc_vu=?, vai_tro=?, thu_tu=?, ghi_chu=? WHERE id=?')
                ->execute([$hocHam, $hoTen, $donVi, $vaiTro, $thuTu, $ghiChu, $id]);
        } else {
            $pdo->prepare('INSERT INTO ktx_ban_quan_ly (hoc_ham_hoc_vi, ho_ten, don_vi_chuc_vu, vai_tro, thu_tu, ghi_chu) VALUES (?,?,?,?,?,?)')
                ->execute([$hocHam, $hoTen, $donVi, $vaiTro, $thuTu, $ghiChu]);
        }
    }
    header('Location: ktx_ban_quan_ly.php?saved=1'); exit;
}
if (($_GET['action'] ?? '') === 'xoa' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM ktx_ban_quan_ly WHERE id = ?')->execute([(int)$_GET['id']]);
    header('Location: ktx_ban_quan_ly.php'); exit;
}

$list = $pdo->query("SELECT * FROM ktx_ban_quan_ly ORDER BY
    FIELD(vai_tro, 'Trưởng ban', 'Phó trưởng ban', 'Thành viên'), thu_tu, id")->fetchAll();

$editId = !empty($_GET['sua']) ? (int)$_GET['sua'] : 0;
$editRow = null;
if ($editId) { foreach ($list as $r) { if ((int)$r['id'] === $editId) { $editRow = $r; break; } } }

$demVaiTro = array_fill_keys($VAI_TRO_LIST, 0);
foreach ($list as $r) { $demVaiTro[$r['vai_tro']] = ($demVaiTro[$r['vai_tro']] ?? 0) + 1; }
$PILL_VAI_TRO = ['Trưởng ban' => 'red', 'Phó trưởng ban' => 'orange', 'Thành viên' => 'blue'];
$moForm = $editRow || !$list;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/ktx_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.bq-form{padding:4px 14px 14px;border-bottom:1px solid var(--line);background:#fbfcfe}
.bq-form[hidden]{display:none}
.bq-form h3{margin:12px 0 10px;font-size:13px;color:var(--navy);text-transform:uppercase;letter-spacing:.3px}
.bq-form .hs-fg{grid-template-columns:110px minmax(0,1.3fr) minmax(0,1.5fr) 160px 90px}
.bq-form .act{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
.bq-av{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;font-weight:700;font-size:13px;background:var(--pri-soft);color:var(--pri);flex:none}
.bq-nm{display:flex;align-items:center;gap:10px}.bq-nm b{display:block}.bq-nm small{color:var(--ink3);font-size:12px}
.bq-act{white-space:nowrap;text-align:right}
@media (max-width:1200px){.bq-form .hs-fg{grid-template-columns:1fr 1fr 1fr}}
@media (max-width:640px){.bq-form .hs-fg{grid-template-columns:1fr}}
</style>
<?php ob_start(); ?>
      <a class="hs-tb" href="ktx_ban_quan_ly_export.php?dinh_dang=excel"><svg class="i"><use href="#hi-down"/></svg>Xuất danh sách (Excel)</a>
      <a class="hs-tb" href="ktx_ban_quan_ly_export.php?dinh_dang=word"><svg class="i"><use href="#hi-file"/></svg>Xuất Quyết định thành lập (Word)</a>
<?php $khungPhai = ob_get_clean(); ?>
<?php ktx_khung_mo('ktx_ban_quan_ly.php', 'BAN QUẢN LÝ KÝ TÚC XÁ', 'Cán bộ phụ trách — dùng cho Quyết định thành lập', '', $khungPhai); ?>

      <?php if (!empty($_GET['saved'])): ?><div class="hs-msg ok"><span>✅ Đã lưu.</span></div><?php endif; ?>

      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-shield"/></svg></div>
            <div><div class="num"><?= count($list) ?></div><div class="lb">Thành viên Ban Quản lý</div></div></div>
          <div class="sb"><div><span>Cán bộ phụ trách KTX (không phải học viên)</span></div></div>
        </div>
        <?php foreach ([['Trưởng ban', 'var(--red-soft)', 'var(--red)', 'star'], ['Phó trưởng ban', 'var(--orange-soft)', 'var(--orange)', 'star'], ['Thành viên', 'var(--pri-soft)', 'var(--pri)', 'users']] as [$vt, $bg, $mau, $ic]): ?>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:<?= $bg ?>;color:<?= $mau ?>"><svg class="i"><use href="#hi-<?= $ic ?>"/></svg></div>
            <div><div class="num" style="color:<?= $mau ?>"><?= $demVaiTro[$vt] ?></div><div class="lb"><?= h($vt) ?></div></div></div>
          <div class="sb"><div><span><?= $vt === 'Trưởng ban' && !$demVaiTro[$vt] ? '<span class="hs-no">Chưa có Trưởng ban</span>' : h(implode(', ', array_map(fn($r) => trim(($r['hoc_ham_hoc_vi'] ?? '') . ' ' . $r['ho_ten']), array_filter($list, fn($r) => $r['vai_tro'] === $vt))) ?: '—') ?></span></div></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="hs-card">
        <div class="hs-tool">
          <h2>DANH SÁCH THÀNH VIÊN</h2>
          <button type="button" class="hs-btn pri" onclick="var f=document.getElementById('bqForm');f.hidden=false;f.querySelector('[name=ho_ten]').focus()"><svg class="i"><use href="#hi-plus"/></svg>Thêm thành viên</button>
          <span class="hs-sp"></span><span style="font-size:12.5px;color:var(--ink2)"><?= count($list) ?> người</span>
        </div>
        <form method="post" class="bq-form" id="bqForm"<?= $moForm ? '' : ' hidden' ?>>
          <input type="hidden" name="action" value="luu">
          <input type="hidden" name="id" value="<?= $editRow ? (int)$editRow['id'] : '' ?>">
          <h3><?= $editRow ? 'Sửa thành viên: ' . h(trim(($editRow['hoc_ham_hoc_vi'] ?? '') . ' ' . $editRow['ho_ten'])) : 'Thêm thành viên' ?></h3>
          <div class="hs-fg">
            <label>Học hàm/học vị<input type="text" name="hoc_ham_hoc_vi" value="<?= h($editRow['hoc_ham_hoc_vi'] ?? '') ?>" placeholder="TS., ThS., CN."></label>
            <label>Họ và tên *<input type="text" name="ho_ten" value="<?= h($editRow['ho_ten'] ?? '') ?>" required></label>
            <label>Đơn vị / Chức vụ công tác<input type="text" name="don_vi_chuc_vu" value="<?= h($editRow['don_vi_chuc_vu'] ?? '') ?>" placeholder="VD: Phó GĐ TTDV&ĐTTNCXH"></label>
            <label>Vai trò trong Ban<select name="vai_tro"><?php foreach ($VAI_TRO_LIST as $v): ?><option value="<?= h($v) ?>" <?= ($editRow['vai_tro'] ?? 'Thành viên') === $v ? 'selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select></label>
            <label>Thứ tự<input type="number" name="thu_tu" value="<?= h((string)($editRow['thu_tu'] ?? '0')) ?>"></label>
            <label class="s3">Ghi chú<input type="text" name="ghi_chu" value="<?= h($editRow['ghi_chu'] ?? '') ?>"></label>
          </div>
          <div class="act">
            <?php if ($editRow): ?><a class="hs-btn" href="ktx_ban_quan_ly.php">Huỷ sửa</a><?php else: ?><button type="button" class="hs-btn" onclick="document.getElementById('bqForm').hidden=true">Đóng</button><?php endif; ?>
            <button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg><?= $editRow ? 'Lưu thay đổi' : 'Thêm' ?></button>
          </div>
        </form>
        <div class="hs-tw">
          <table class="hs-grid">
            <thead><tr><th class="c" style="width:50px">STT</th><th>Họ và tên</th><th>Đơn vị / Chức vụ công tác</th><th>Vai trò trong Ban</th><th>Ghi chú</th><th></th></tr></thead>
            <tbody>
            <?php $stt = 0; foreach ($list as $r): $stt++; $tach = preg_split('/\s+/u', trim((string)$r['ho_ten'])); ?>
            <tr<?= $editId === (int)$r['id'] ? ' style="background:#d9e8fb"' : '' ?>>
              <td class="c"><?= $stt ?></td>
              <td><div class="bq-nm"><span class="bq-av"><?= h(mb_strtoupper(mb_substr((string)end($tach), 0, 1))) ?></span><span><b><?= h(trim(($r['hoc_ham_hoc_vi'] ?? '') . ' ' . $r['ho_ten'])) ?></b><?php if ((int)$r['thu_tu']): ?><small>Thứ tự <?= (int)$r['thu_tu'] ?></small><?php endif; ?></span></div></td>
              <td><?= $r['don_vi_chuc_vu'] ? h($r['don_vi_chuc_vu']) : '<span class="hs-mut">—</span>' ?></td>
              <td><span class="hs-pill <?= $PILL_VAI_TRO[$r['vai_tro']] ?? 'gray' ?>"><?= h($r['vai_tro']) ?></span></td>
              <td><?= $r['ghi_chu'] ? h($r['ghi_chu']) : '<span class="hs-mut">—</span>' ?></td>
              <td class="bq-act">
                <a class="hs-btn sm" href="?sua=<?= (int)$r['id'] ?>"><svg class="i"><use href="#hi-edit"/></svg>Sửa</a>
                <a class="hs-btn sm r" href="?action=xoa&amp;id=<?= (int)$r['id'] ?>" onclick="return confirm('Xoá thành viên này khỏi Ban Quản lý?')"><svg class="i"><use href="#hi-trash"/></svg>Xoá</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$list): ?><tr><td colspan="6" class="hs-empty">Chưa có thành viên nào — bấm <b>Thêm thành viên</b>.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php ktx_khung_dong(); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
