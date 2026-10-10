<?php
// Quan tri Menu (thanh dieu huong) RIENG cho nhanh Hoi CCB (2026-09-18) — CHI anh huong menu hien
// thi khi $_SESSION['ph_nhanh'] === 'hccb' (xem public/header_public.php), KHONG dung chung voi
// menu_item cua nhanh Dao tao (admin/menu_quan_ly.php) va KHONG anh huong nhanh KTX.
// "Điểm danh HCCB" (CTA noi bat) va "Liên hệ" van GIU CO DINH ngoai bang nay vi la 2 chuc nang loi
// (check-in + lien he) dung chung toan he thong, tranh admin lo xoa mat duong dan chuc nang chinh.
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh Hoi CCB (includes/hccb_khung.php), the so lieu, xem
// truoc thanh menu cong khai, nhan loai duong dan (Tu dong / Trang cong khai / Lien ket ngoai), nut chen
// nhanh AUTO_TIN_TUC / AUTO_THONG_BAO. Xu ly them / sua / xoa GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'update'], true)) {
    $nhan = trim($_POST['nhan'] ?? '');
    $duongDan = trim($_POST['duong_dan'] ?? '');
    $thuTu = (int)($_POST['thu_tu'] ?? 0);
    $hienThi = !empty($_POST['hien_thi']) ? 1 : 0;
    if ($nhan !== '' && $duongDan !== '') {
        if ($_POST['action'] === 'add') {
            $pdo->prepare('INSERT INTO hccb_menu_item (nhan, duong_dan, thu_tu, hien_thi) VALUES (?,?,?,?)')
                ->execute([$nhan, $duongDan, $thuTu, $hienThi]);
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE hccb_menu_item SET nhan=?, duong_dan=?, thu_tu=?, hien_thi=? WHERE id=?')
                ->execute([$nhan, $duongDan, $thuTu, $hienThi, $id]);
        }
    }
    header('Location: hccb_menu.php?saved=1'); exit;
}
if (($_GET['action'] ?? '') === 'xoa' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM hccb_menu_item WHERE id = ?')->execute([(int)$_GET['id']]);
    header('Location: hccb_menu.php'); exit;
}

$editRow = null;
if (!empty($_GET['edit']) && $_GET['edit'] !== 'new') {
    $stmt = $pdo->prepare('SELECT * FROM hccb_menu_item WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editRow = $stmt->fetch();
}
$showForm = $editRow || (($_GET['edit'] ?? '') === 'new');

$list = $pdo->query('SELECT * FROM hccb_menu_item ORDER BY thu_tu, id')->fetchAll();

// Phan loai duong dan de hien thi nhan: muc tu dong / trang trong public/ / lien ket ngoai
$loaiLink = function (string $d): array {
    if ($d === 'AUTO_TIN_TUC' || $d === 'AUTO_THONG_BAO') return ['Tự động', 'violet'];
    if (preg_match('~^(https?:)?//~i', $d)) return ['Liên kết ngoài', 'orange'];
    return ['Trang công khai', 'blue'];
};
$soHien = 0; $soTuDong = 0;
foreach ($list as $r) { if ($r['hien_thi']) $soHien++; if ($loaiLink((string)$r['duong_dan'])[0] === 'Tự động') $soTuDong++; }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/hccb_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.mn-form{padding:4px 14px 14px;border-bottom:1px solid var(--line);background:#fbfcfe}
.mn-form h3{margin:12px 0 10px;font-size:13px;color:var(--navy);text-transform:uppercase;letter-spacing:.3px}
.mn-form .hs-fg{grid-template-columns:minmax(0,1fr) minmax(0,1.4fr) 120px}
.mn-form .chk{flex-direction:row;align-items:center;gap:8px;font-size:13.5px;color:var(--ink);font-weight:600}
.mn-form .chk input{width:auto;height:auto}
.mn-form .hs-fg label{justify-content:flex-end}.mn-form .hs-fg label.chk{justify-content:flex-start}
.mn-form .act{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
.mn-auto{display:flex;gap:6px;flex-wrap:wrap;align-items:center;font-weight:400;color:var(--ink3)}
.mn-auto button{border:1px dashed var(--pri-line);background:#fff;color:var(--pri);border-radius:6px;font:inherit;font-size:12px;padding:2px 8px;cursor:pointer}
.mn-pv{padding:12px 14px;border-bottom:1px solid var(--line2)}
.mn-pv .cap{font-size:12px;color:var(--ink2);font-weight:600;margin-bottom:8px}
.mn-nav{display:flex;flex-wrap:wrap;gap:2px;align-items:center;background:var(--navy);border-radius:8px;padding:6px}
.mn-nav span{color:#fff;font-size:13px;padding:6px 12px;border-radius:6px;white-space:nowrap}
.mn-nav span:first-child{background:rgba(255,255,255,.14)}
.mn-nav .cta{margin-left:auto;background:#c8102e;font-weight:700;display:inline-flex;align-items:center;gap:6px}
.mn-nav .cta img{width:16px;height:16px}
.mn-nav .rong{color:#c9d6ea;font-style:italic}
.hs-pill.violet{background:#f0e9fb;color:var(--violet)}
td.mn-ten b{display:block}
.mn-dd{font-family:ui-monospace,Consolas,monospace;font-size:12.5px;color:var(--ink2);word-break:break-all}
.mn-tt{display:inline-grid;place-items:center;width:30px;height:26px;border-radius:6px;background:#eef1f5;font-weight:700;color:var(--navy)}
tr.an td{opacity:.55}tr.an td:last-child{opacity:1}
.mn-act{white-space:nowrap;text-align:right}
@media (max-width:760px){.mn-form .hs-fg{grid-template-columns:1fr}.mn-nav .cta{margin-left:0}}
</style>
<?php hccb_khung_mo('hccb_menu.php', 'MENU HỘI CCB', 'Thanh điều hướng trang công khai nhánh Hội CCB', '<span class="hs-sp"></span>'); ?>

      <?php if (!empty($_GET['saved'])): ?><div class="hs-msg ok"><span>✅ Đã lưu.</span></div><?php endif; ?>

      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-menu"/></svg></div>
            <div><div class="num"><?= count($list) ?></div><div class="lb">Mục menu</div></div></div>
          <div class="sb"><div><span>Chưa có mục nào → dùng menu mặc định</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-check"/></svg></div>
            <div><div class="num" style="color:var(--green)"><?= $soHien ?></div><div class="lb">Đang hiển thị</div></div></div>
          <div class="sb"><div><span>Trên thanh menu công khai</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:#eef1f5;color:var(--ink2)"><svg class="i"><use href="#hi-shield"/></svg></div>
            <div><div class="num" style="color:var(--ink2)"><?= count($list) - $soHien ?></div><div class="lb">Đang ẩn</div></div></div>
          <div class="sb"><div><span>Giữ lại, bật lại khi cần</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:#f0e9fb;color:var(--violet)"><svg class="i"><use href="#hi-bolt"/></svg></div>
            <div><div class="num" style="color:var(--violet)"><?= $soTuDong ?></div><div class="lb">Mục tự động</div></div></div>
          <div class="sb"><div><span>AUTO_TIN_TUC / AUTO_THONG_BAO</span></div></div>
        </div>
      </div>

      <div class="hs-card">
        <div class="hs-tool">
          <h2>CÁC MỤC MENU</h2>
          <a class="hs-btn pri" href="?edit=new"><svg class="i"><use href="#hi-plus"/></svg>Thêm mục menu</a>
          <span class="hs-sp"></span><span style="font-size:12.5px;color:var(--ink2)">Số thứ tự nhỏ hiện trước · không ảnh hưởng menu Đào tạo / KTX</span>
        </div>
        <div class="mn-pv">
          <div class="cap">Xem trước thanh menu công khai</div>
          <div class="mn-nav">
            <?php $coHien = false; foreach ($list as $r): if (!$r['hien_thi']) continue; $coHien = true; ?><span><?= h($r['nhan']) ?></span><?php endforeach; ?>
            <?php if (!$coHien): ?><span class="rong">(menu mặc định của hệ thống)</span><?php endif; ?>
            <span class="cta"><?= hccb_logo_img(16) ?>Điểm danh HCCB</span>
          </div>
          <div style="font-size:12px;color:var(--ink3);margin-top:6px">Mục “Điểm danh HCCB” luôn cố định (nổi bật) ở cuối thanh menu, không nằm trong danh sách này, để tránh vô tình xoá mất đường dẫn điểm danh.</div>
        </div>
        <?php if ($showForm): $e = $editRow ?: []; ?>
        <form method="post" class="mn-form">
          <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'add' ?>">
          <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>
          <h3><?= $editRow ? 'Sửa mục menu' : 'Thêm mục menu' ?></h3>
          <div class="hs-fg">
            <label>Tên hiển thị trên menu *<input type="text" name="nhan" value="<?= h($e['nhan'] ?? '') ?>" required></label>
            <label>Đường dẫn * <span class="hint">file .php trong public/ hoặc URL đầy đủ</span><input type="text" id="mnDd" name="duong_dan" value="<?= h($e['duong_dan'] ?? '') ?>" required placeholder="VD: hccb_gioi_thieu.php"></label>
            <label>Thứ tự <span class="hint">nhỏ hiện trước</span><input type="number" name="thu_tu" value="<?= h((string)($e['thu_tu'] ?? (count($list) ? max(array_column($list, 'thu_tu')) + 1 : 1))) ?>"></label>
            <div class="s3 mn-auto" style="font-size:12px">Chuyên mục tự động:
              <button type="button" onclick="document.getElementById('mnDd').value='AUTO_TIN_TUC'">AUTO_TIN_TUC</button>
              <button type="button" onclick="document.getElementById('mnDd').value='AUTO_THONG_BAO'">AUTO_THONG_BAO</button>
              — tự trỏ đến đúng chuyên mục “Tin tức - Sự kiện” / “Thông báo” hiện có của hệ thống (không cần biết link cụ thể).</div>
            <label class="s3 chk"><input type="checkbox" name="hien_thi" value="1" <?= (!isset($e['hien_thi']) || $e['hien_thi']) ? 'checked' : '' ?>> Hiển thị mục này trên menu</label>
          </div>
          <div class="act"><a href="hccb_menu.php" class="hs-btn">Huỷ</a><button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg>Lưu</button></div>
        </form>
        <?php endif; ?>
        <div class="hs-tw">
          <table class="hs-grid">
            <thead><tr><th class="c" style="width:70px">Thứ tự</th><th>Tên hiển thị</th><th>Đường dẫn</th><th class="c">Loại</th><th class="c">Hiển thị</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($list as $r): $ll = $loaiLink((string)$r['duong_dan']); $dangSua = $editRow && (int)$editRow['id'] === (int)$r['id']; ?>
            <tr class="<?= $r['hien_thi'] ? '' : 'an' ?>"<?= $dangSua ? ' style="background:#d9e8fb"' : '' ?>>
              <td class="c"><span class="mn-tt"><?= (int)$r['thu_tu'] ?></span></td>
              <td class="mn-ten"><b><?= h($r['nhan']) ?></b></td>
              <td><span class="mn-dd"><?= h($r['duong_dan']) ?></span></td>
              <td class="c"><span class="hs-pill <?= $ll[1] ?>"><?= $ll[0] ?></span></td>
              <td class="c"><?= $r['hien_thi'] ? '<span class="hs-pill green">Hiện</span>' : '<span class="hs-pill gray">Ẩn</span>' ?></td>
              <td class="mn-act">
                <a class="hs-btn sm" href="?edit=<?= (int)$r['id'] ?>"><svg class="i"><use href="#hi-edit"/></svg>Sửa</a>
                <a class="hs-btn sm r" href="?action=xoa&amp;id=<?= (int)$r['id'] ?>" onclick="return confirm('Xoá mục menu này?')"><svg class="i"><use href="#hi-trash"/></svg>Xoá</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$list): ?><tr><td colspan="6" class="hs-empty">Chưa có mục menu nào — hệ thống sẽ tạm dùng menu mặc định cho đến khi bạn thêm ít nhất 1 mục.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php ktx_khung_dong(); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
