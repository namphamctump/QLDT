<?php
// Quan ly Day nha + Tang + Phong + Giuong cua Ky tuc xa — BAN NANG CAP (2026-09-14) theo dung
// mo hinh "QUẢN LÝ PHÒNG - GIƯỜNG" (cay Day/Tang ben trai, danh sach Phong o giua, danh sach
// Giuong cua phong dang chon o phai, dong tong ket cuoi trang). Diem khac biet quan trong: sức
// chứa (ktx_phong.suc_chua) gio DUOC DONG BO TU DONG tu so GIUONG dang bat "su dung" (xem
// ktx_dong_bo_suc_chua() trong includes/functions.php) — cho phep danh dau 1 giuong cu the la
// "Không sử dụng" (hong, khong con dat...) ma khong can tinh nham suc chua thu cong.
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh KTX (includes/ktx_khung.php), 4 the so lieu, 3 cot
// Day – Phong – Giuong dang the, chip loc tang, form them day/phong thu gon, giuong hien ten nguoi
// dang nam. Toan bo xu ly POST/GET (add_day, add_phong, save_phong, xoa_phong, add_giuong,
// save_giuong, xoa_giuong) GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_day') {
    $ten = trim($_POST['ten'] ?? '');
    $gioiTinh = in_array($_POST['gioi_tinh'] ?? '', ['Nam', 'Nữ', 'Khác'], true) ? $_POST['gioi_tinh'] : 'Khác';
    if ($ten !== '') {
        $pdo->prepare('INSERT INTO ktx_day (ten, gioi_tinh, ghi_chu) VALUES (?,?,?)')
            ->execute([$ten, $gioiTinh, trim($_POST['ghi_chu'] ?? '') ?: null]);
        header('Location: ktx_day_phong.php?day=' . (int)$pdo->lastInsertId()); exit;
    }
    header('Location: ktx_day_phong.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_phong') {
    $dayId = (int)($_POST['day_id'] ?? 0);
    $soPhong = trim($_POST['so_phong'] ?? '');
    $sucChua = max(1, (int)($_POST['suc_chua'] ?? 4));
    if ($dayId && $soPhong !== '') {
        try {
            $pdo->prepare('INSERT INTO ktx_phong (day_id, so_phong, tang, suc_chua) VALUES (?,?,?,?)')
                ->execute([$dayId, $soPhong, trim($_POST['tang'] ?? '') ?: null, $sucChua]);
            $phongId = (int)$pdo->lastInsertId();
            // Tu dong sinh du giuong theo dung suc chua vua nhap (VD suc_chua=4 -> 4 giuong .01-.04).
            $insG = $pdo->prepare('INSERT INTO ktx_giuong (phong_id, ma_giuong, su_dung, thu_tu) VALUES (?,?,1,?)');
            for ($i = 1; $i <= $sucChua; $i++) { $insG->execute([$phongId, $soPhong . '.' . str_pad((string)$i, 2, '0', STR_PAD_LEFT), $i]); }
        } catch (PDOException $e) {
            header('Location: ktx_day_phong.php?err=trung&day=' . $dayId); exit;
        }
    }
    header('Location: ktx_day_phong.php?day=' . $dayId); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_phong') {
    $ids = $_POST['id'] ?? [];
    $upd = $pdo->prepare('UPDATE ktx_phong SET so_phong=?, tang=?, tinh_trang=?, ghi_chu=? WHERE id=?');
    foreach ($ids as $id) {
        $id = (int)$id;
        $upd->execute([
            trim($_POST['so_phong'][$id] ?? ''),
            trim($_POST['tang'][$id] ?? '') ?: null,
            in_array($_POST['tinh_trang'][$id] ?? '', ['binh_thuong', 'dang_sua_chua', 'ngung_su_dung'], true) ? $_POST['tinh_trang'][$id] : 'binh_thuong',
            trim($_POST['ghi_chu'][$id] ?? '') ?: null,
            $id,
        ]);
    }
    header('Location: ktx_day_phong.php?saved=1&day=' . (int)($_POST['cur_day'] ?? 0) . '&tang=' . urlencode((string)($_POST['cur_tang'] ?? '')) . (!empty($_POST['cur_phong']) ? '&phong=' . (int)$_POST['cur_phong'] : '')); exit;
}
if (($_GET['action'] ?? '') === 'xoa_phong' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $chk = $pdo->prepare("SELECT COUNT(*) FROM ktx_hoc_vien WHERE phong_id = ? AND trang_thai = 'dang_o'");
    $chk->execute([$id]);
    if ((int)$chk->fetchColumn() === 0) {
        $pdo->prepare('DELETE FROM ktx_phong WHERE id = ?')->execute([$id]); // ktx_giuong tu xoa theo (ON DELETE CASCADE)
    } else {
        header('Location: ktx_day_phong.php?err=conguoi&day=' . (int)($_GET['day'] ?? 0)); exit;
    }
    header('Location: ktx_day_phong.php?day=' . (int)($_GET['day'] ?? 0)); exit;
}

// ---- Giuong: them / bat-tat su dung / xoa / luu hang loat cho 1 phong ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_giuong') {
    $phongId = (int)($_POST['phong_id'] ?? 0);
    $maGiuong = trim($_POST['ma_giuong'] ?? '');
    if ($phongId && $maGiuong !== '') {
        try {
            $maxThuTu = (int)$pdo->query('SELECT COALESCE(MAX(thu_tu),0) FROM ktx_giuong WHERE phong_id = ' . $phongId)->fetchColumn();
            $pdo->prepare('INSERT INTO ktx_giuong (phong_id, ma_giuong, su_dung, thu_tu) VALUES (?,?,1,?)')->execute([$phongId, $maGiuong, $maxThuTu + 1]);
            ktx_dong_bo_suc_chua($pdo, $phongId);
        } catch (PDOException $e) { /* trung ma giuong — bo qua */ }
    }
    header('Location: ktx_day_phong.php?phong=' . $phongId); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_giuong') {
    $phongId = (int)($_POST['phong_id'] ?? 0);
    $ids = $_POST['gid'] ?? [];
    $upd = $pdo->prepare('UPDATE ktx_giuong SET ma_giuong=?, su_dung=?, ghi_chu=? WHERE id=?');
    foreach ($ids as $gid) {
        $gid = (int)$gid;
        $upd->execute([
            trim($_POST['ma_giuong'][$gid] ?? ''),
            !empty($_POST['su_dung'][$gid]) ? 1 : 0,
            trim($_POST['ghi_chu_giuong'][$gid] ?? '') ?: null,
            $gid,
        ]);
    }
    if ($phongId) { ktx_dong_bo_suc_chua($pdo, $phongId); }
    header('Location: ktx_day_phong.php?phong=' . $phongId . '&saved_giuong=1'); exit;
}
if (($_GET['action'] ?? '') === 'xoa_giuong' && !empty($_GET['id'])) {
    $stmtG = $pdo->prepare('SELECT phong_id FROM ktx_giuong WHERE id = ?');
    $stmtG->execute([(int)$_GET['id']]);
    $phongId = (int)$stmtG->fetchColumn();
    $pdo->prepare('DELETE FROM ktx_giuong WHERE id = ?')->execute([(int)$_GET['id']]);
    if ($phongId) { ktx_dong_bo_suc_chua($pdo, $phongId); }
    header('Location: ktx_day_phong.php?phong=' . $phongId); exit;
}

// ---- Du lieu hien thi ----
$dayList = $pdo->query("SELECT d.*, COUNT(p.id) so_phong,
        (SELECT COUNT(*) FROM ktx_hoc_vien hv JOIN ktx_phong p2 ON p2.id = hv.phong_id WHERE p2.day_id = d.id AND hv.trang_thai = 'dang_o') dang_o
    FROM ktx_day d LEFT JOIN ktx_phong p ON p.day_id = d.id GROUP BY d.id ORDER BY d.ten")->fetchAll();

$curPhongId = (int)($_GET['phong'] ?? 0);
$curPhong = null;
$giuongList = [];
if ($curPhongId) {
    $stmtP = $pdo->prepare('SELECT p.*, d.ten AS ten_day, d.gioi_tinh AS day_gioi_tinh FROM ktx_phong p JOIN ktx_day d ON d.id = p.day_id WHERE p.id = ?');
    $stmtP->execute([$curPhongId]);
    $curPhong = $stmtP->fetch();
    if ($curPhong) {
        $stmtG = $pdo->prepare('SELECT * FROM ktx_giuong WHERE phong_id = ? ORDER BY thu_tu, ma_giuong');
        $stmtG->execute([$curPhongId]);
        $giuongList = $stmtG->fetchAll();
    }
}
// (2026-10-10) Mo 1 phong (?phong=) ma khong kem ?day= -> tu chon dung day cua phong do
$curDayId = (int)($_GET['day'] ?? 0) ?: ($curPhong ? (int)$curPhong['day_id'] : (int)($dayList[0]['id'] ?? 0));
$curDay = null;
foreach ($dayList as $d) { if ((int)$d['id'] === $curDayId) { $curDay = $d; break; } }

// Danh sach TANG co that trong day dang chon (de lam cay con "Day > Tang").
$tangList = [];
if ($curDayId) {
    $stmtTang = $pdo->prepare("SELECT DISTINCT tang FROM ktx_phong WHERE day_id = ? AND tang IS NOT NULL AND tang <> '' ORDER BY tang");
    $stmtTang->execute([$curDayId]);
    $tangList = array_column($stmtTang->fetchAll(), 'tang');
}
$curTang = (string)($_GET['tang'] ?? '');

$phongList = [];
if ($curDayId) {
    $where = 'p.day_id = ?';
    $params = [$curDayId];
    if ($curTang !== '') { $where .= ' AND p.tang = ?'; $params[] = $curTang; }
    $stmt = $pdo->prepare("SELECT p.*,
            (SELECT COUNT(*) FROM ktx_hoc_vien hv WHERE hv.phong_id = p.id AND hv.trang_thai = 'dang_o') dang_o,
            (SELECT COUNT(*) FROM ktx_giuong g WHERE g.phong_id = p.id) tong_giuong,
            (SELECT COUNT(*) FROM ktx_giuong g WHERE g.phong_id = p.id AND g.su_dung = 1) giuong_dung
        FROM ktx_phong p WHERE $where ORDER BY p.so_phong");
    $stmt->execute($params);
    $phongList = $stmt->fetchAll();
}

// Nguoi dang nam o tung giuong cua phong dang chon (khop ktx_hoc_vien.giuong voi ma giuong)
$nguoiTheoGiuong = []; $nguoiChuaGanGiuong = [];
if ($curPhong) {
    $stmtHv = $pdo->prepare("SELECT id, ho_ten, ma_so, giuong FROM ktx_hoc_vien WHERE phong_id = ? AND trang_thai = 'dang_o' ORDER BY giuong, ho_ten");
    $stmtHv->execute([$curPhongId]);
    $maGiuongCo = array_column($giuongList, 'ma_giuong');
    foreach ($stmtHv->fetchAll() as $hvG) {
        if ($hvG['giuong'] !== null && in_array($hvG['giuong'], $maGiuongCo, true) && !isset($nguoiTheoGiuong[$hvG['giuong']])) { $nguoiTheoGiuong[$hvG['giuong']] = $hvG; }
        else { $nguoiChuaGanGiuong[] = $hvG; }
    }
}

// Thong ke TOAN BO (khong phu thuoc bo loc dang xem).
$tongSoPhong = (int)$pdo->query('SELECT COUNT(*) FROM ktx_phong')->fetchColumn();
$tongSoGiuong = (int)$pdo->query('SELECT COUNT(*) FROM ktx_giuong WHERE su_dung = 1')->fetchColumn();
$tongGiuongNgung = (int)$pdo->query('SELECT COUNT(*) FROM ktx_giuong WHERE su_dung = 0')->fetchColumn();
$tongDangO = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien WHERE trang_thai = 'dang_o'")->fetchColumn();
$demTinhTrang = ['binh_thuong' => 0, 'dang_sua_chua' => 0, 'ngung_su_dung' => 0];
foreach ($pdo->query('SELECT tinh_trang, COUNT(*) n FROM ktx_phong GROUP BY tinh_trang')->fetchAll() as $r) { $demTinhTrang[$r['tinh_trang'] ?: 'binh_thuong'] = ($demTinhTrang[$r['tinh_trang'] ?: 'binh_thuong'] ?? 0) + (int)$r['n']; }
$demDayGt = ['Nam' => 0, 'Nữ' => 0, 'Khác' => 0];
foreach ($dayList as $d) { $demDayGt[isset($demDayGt[$d['gioi_tinh']]) ? $d['gioi_tinh'] : 'Khác']++; }

$TINH_TRANG_LABEL = ['binh_thuong' => 'Bình thường', 'dang_sua_chua' => 'Đang sửa chữa', 'ngung_su_dung' => 'Ngừng sử dụng'];
$GT_MAU = ['Nam' => 'var(--nam)', 'Nữ' => 'var(--nu)', 'Khác' => '#98a4b5'];
$urlPhong = fn(int $pid) => '?' . http_build_query(['day' => $curDayId, 'tang' => $curTang, 'phong' => $pid]) . '#giuong';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/ktx_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.dp-wrap{display:grid;grid-template-columns:220px minmax(0,1fr) 340px;gap:12px;align-items:start}
.dp-wrap>*{min-width:0}
.dp-col-h{display:flex;align-items:center;gap:8px;padding:10px 12px;border-bottom:1px solid var(--line)}
.dp-col-h h2{margin:0;font-size:15px;color:var(--navy);white-space:nowrap}
.dp-col-h .sub{font-size:12.5px;color:var(--ink2);font-weight:400}
/* cot day */
.dp-days{list-style:none;margin:0;padding:6px}
.dp-days a.day{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;color:var(--ink)}
.dp-days a.day:hover{background:var(--pri-soft)}
.dp-days a.day.on{background:var(--pri);color:#fff}.dp-days a.day.on small{color:rgba(255,255,255,.85)}
.dp-days a.day{flex-wrap:wrap;row-gap:1px}.dp-days a.day b{font-weight:600;flex:1}.dp-days a.day small{flex-basis:100%;padding-left:16px;font-size:12px;color:var(--ink3);white-space:nowrap}
.dp-tangs{display:flex;flex-wrap:wrap;gap:4px;padding:4px 10px 8px 26px}
.dp-chip{display:inline-flex;align-items:center;height:26px;padding:0 10px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:12.5px;text-decoration:none;color:var(--ink2)}
.dp-chip:hover{border-color:var(--pri-line);color:var(--pri)}.dp-chip.on{background:var(--pri-soft);border-color:var(--pri-line);color:var(--pri);font-weight:600}
.dp-add{padding:10px 12px;border-top:1px solid var(--line)}
.dp-add[hidden]{display:none}
.dp-add .hs-fg{grid-template-columns:1fr}
/* bang phong */
.dp-tbl input[type=text],.dp-tbl select{height:30px;border:1px solid var(--line);border-radius:6px;padding:0 8px;font:inherit;font-size:13px;color:var(--ink);background:#fff;width:100%}
.dp-tbl input[type=text]:focus,.dp-tbl select:focus{outline:0;border-color:var(--pri);box-shadow:0 0 0 3px rgba(31,111,214,.15)}
.dp-tbl td{padding:6px 8px!important}
.dp-tbl tr.sel{background:#d9e8fb!important}
.dp-tbl tr.st-dang_sua_chua{background:#fffaf0}.dp-tbl tr.st-ngung_su_dung{background:#f5f6f8;color:var(--ink3)}
.dp-tbl a.ph{font-weight:700;color:var(--pri);text-decoration:none;font-size:14px}
.dp-occ{display:flex;flex-direction:column;gap:3px;min-width:80px}
.dp-occ .hs-bar3{min-width:0;height:6px}
.dp-occ span.t{font-size:12.5px;white-space:nowrap}
.dp-ic{display:inline-grid;place-items:center;width:28px;height:28px;border-radius:6px;color:var(--ink2);text-decoration:none}
.dp-ic:hover{background:var(--pri-soft);color:var(--pri)}.dp-ic.del:hover{background:var(--red-soft);color:var(--red)}
.dp-ic svg{width:15px;height:15px}
.dp-save{display:flex;align-items:center;gap:8px;padding:10px 12px;border-top:1px solid var(--line);background:#fbfcfe}
.dp-newroom{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;padding:10px 12px;border-bottom:1px solid var(--line);background:#fbfcfe}
.dp-newroom[hidden]{display:none}
.dp-newroom label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--ink2);font-weight:600}
/* cot giuong */
.dp-bed-col{position:sticky;top:calc(var(--adm-h,0px) + 10px)}
.dp-beds{list-style:none;margin:0;padding:8px 10px;display:grid;gap:6px}
.dp-bed{display:grid;grid-template-columns:92px 1fr auto;gap:6px 8px;align-items:center;border:1px solid var(--line);border-radius:9px;padding:7px 8px}
.dp-bed.off{background:#f5f6f8}
.dp-bed input[type=text]{height:28px;border:1px solid var(--line);border-radius:6px;padding:0 7px;font:inherit;font-size:12.5px;width:100%;min-width:0}
.dp-bed .who{grid-column:1/-1;font-size:12.5px;display:flex;align-items:center;gap:6px}
.dp-bed .who a{color:var(--pri);text-decoration:none}
.dp-sw{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--ink2);white-space:nowrap;cursor:pointer}
.dp-sw input{width:16px;height:16px;accent-color:var(--green)}
.dp-place{padding:30px 16px;text-align:center;color:var(--ink3)}
@media (max-width:1400px){.dp-wrap{grid-template-columns:220px minmax(0,1fr)}.dp-bed-col{grid-column:1/-1;position:static}}
@media (max-width:980px){.dp-wrap{grid-template-columns:1fr}}
</style>
<?php ob_start(); ?>
      <a class="hs-tb" href="ktx_so_do_phong.php"><svg class="i"><use href="#hi-bed"/></svg>Sơ đồ trực quan</a>
      <a class="hs-tb" href="ktx_thiet_bi.php"><svg class="i"><use href="#hi-box"/></svg>Thiết bị – CSVC</a>
      <a class="hs-tb" href="ktx_day_phong_export.php<?= $curDayId ? '?day=' . $curDayId : '' ?>"><svg class="i"><use href="#hi-down"/></svg>Xuất Excel</a>
<?php $khungPhai = ob_get_clean(); ?>
<?php ktx_khung_mo('ktx_day_phong.php', 'QUẢN LÝ PHÒNG – GIƯỜNG', 'Dãy nhà · tầng · phòng · giường', '', $khungPhai); ?>

      <?php if (!empty($_GET['saved'])): ?><div class="hs-msg ok"><span>✅ Đã lưu thay đổi danh sách phòng.</span></div><?php endif; ?>
      <?php if (!empty($_GET['saved_giuong'])): ?><div class="hs-msg ok"><span>✅ Đã lưu danh sách giường (sức chứa phòng tự cập nhật).</span></div><?php endif; ?>
      <?php if (($_GET['err'] ?? '') === 'trung'): ?><div class="hs-msg warn"><span>⚠️ Số phòng này đã tồn tại trong dãy nhà đã chọn.</span></div><?php endif; ?>
      <?php if (($_GET['err'] ?? '') === 'conguoi'): ?><div class="hs-msg warn"><span>⚠️ Không thể xoá — phòng đang có học viên ở.</span></div><?php endif; ?>

      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-home"/></svg></div>
            <div><div class="num"><?= count($dayList) ?></div><div class="lb">Dãy nhà</div></div></div>
          <div class="sb">
            <div><b style="color:var(--nam)"><?= $demDayGt['Nam'] ?></b><span>Dãy nam</span></div>
            <div><b style="color:var(--nu)"><?= $demDayGt['Nữ'] ?></b><span>Dãy nữ</span></div>
            <?php if ($demDayGt['Khác']): ?><div><b><?= $demDayGt['Khác'] ?></b><span>Dãy chung</span></div><?php endif; ?>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-door"/></svg></div>
            <div><div class="num"><?= $tongSoPhong ?></div><div class="lb">Phòng</div></div></div>
          <div class="sb">
            <div><b style="color:var(--green)"><?= $demTinhTrang['binh_thuong'] ?></b><span>Bình thường</span></div>
            <div><b style="color:var(--orange)"><?= $demTinhTrang['dang_sua_chua'] ?></b><span>Sửa chữa</span></div>
            <div><b style="color:var(--ink3)"><?= $demTinhTrang['ngung_su_dung'] ?></b><span>Ngừng dùng</span></div>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-bed"/></svg></div>
            <div><div class="num"><?= $tongSoGiuong ?></div><div class="lb">Giường đang sử dụng</div></div></div>
          <div class="sb">
            <div><b style="color:var(--green)"><?= max(0, $tongSoGiuong - $tongDangO) ?></b><span>Giường trống</span></div>
            <div><b style="color:var(--ink3)"><?= $tongGiuongNgung ?></b><span>Giường ngừng dùng</span></div>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num"><?= $tongDangO ?></div><div class="lb">Người đang ở</div></div></div>
          <div class="sb"><div><b><?= $tongSoGiuong ? round($tongDangO / $tongSoGiuong * 100) : 0 ?>%</b><span>Lấp đầy so với giường sử dụng</span></div></div>
        </div>
      </div>

      <div class="dp-wrap">
        <!-- ===== Cot 1: Day nha – Tang ===== -->
        <div class="hs-card">
          <div class="dp-col-h"><h2>Dãy nhà</h2><span class="hs-sp"></span><button type="button" class="hs-btn sm" onclick="var f=document.getElementById('dpAddDay');f.hidden=!f.hidden;if(!f.hidden)f.querySelector('input').focus()"><svg class="i"><use href="#hi-plus"/></svg>Thêm</button></div>
          <form method="post" class="dp-add" id="dpAddDay" hidden>
            <input type="hidden" name="action" value="add_day">
            <div class="hs-fg">
              <label>Tên dãy mới<input type="text" name="ten" placeholder="VD: Dãy nhà E" required></label>
              <label>Giới tính<select name="gioi_tinh"><option value="Nam">Nam</option><option value="Nữ">Nữ</option><option value="Khác">Khác (dãy chung)</option></select></label>
            </div>
            <button type="submit" class="hs-btn pri" style="margin-top:8px;width:100%;justify-content:center"><svg class="i"><use href="#hi-plus"/></svg>Thêm dãy</button>
          </form>
          <ul class="dp-days">
            <?php foreach ($dayList as $d): $on = (int)$d['id'] === $curDayId; $g = isset($GT_MAU[$d['gioi_tinh']]) ? $d['gioi_tinh'] : 'Khác'; ?>
            <li>
              <a class="day <?= $on ? 'on' : '' ?>" href="?day=<?= (int)$d['id'] ?>"><span class="hs-dot" style="background:<?= $on ? '#fff' : $GT_MAU[$g] ?>"></span><b><?= h($d['ten']) ?></b><small><?= (int)$d['so_phong'] ?> phòng · <?= (int)$d['dang_o'] ?> người</small></a>
              <?php if ($on && $tangList): ?>
                <div class="dp-tangs">
                  <a class="dp-chip <?= $curTang === '' ? 'on' : '' ?>" href="?day=<?= $curDayId ?>">Tất cả tầng</a>
                  <?php foreach ($tangList as $t): ?><a class="dp-chip <?= $curTang === (string)$t ? 'on' : '' ?>" href="?day=<?= $curDayId ?>&amp;tang=<?= urlencode((string)$t) ?>">Tầng <?= h($t) ?></a><?php endforeach; ?>
                </div>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
            <?php if (!$dayList): ?><li class="dp-place">Chưa có dãy nào — bấm <b>Thêm</b>.</li><?php endif; ?>
          </ul>
        </div>

        <!-- ===== Cot 2: Danh sach phong ===== -->
        <div class="hs-card">
          <div class="dp-col-h">
            <h2>Phòng <span class="sub">— <?= $curDay ? h($curDay['ten']) . ($curDay['gioi_tinh'] ? ' (' . h($curDay['gioi_tinh']) . ')' : '') : 'chưa chọn dãy' ?><?= $curTang !== '' ? ' · Tầng ' . h($curTang) : '' ?> · <?= count($phongList) ?> phòng</span></h2>
            <span class="hs-sp"></span>
            <?php if ($curDayId): ?><button type="button" class="hs-btn sm" onclick="var f=document.getElementById('dpNewRoom');f.hidden=!f.hidden;if(!f.hidden)f.querySelector('[name=so_phong]').focus()"><svg class="i"><use href="#hi-plus"/></svg>Thêm phòng</button><?php endif; ?>
          </div>
          <?php if ($curDayId): ?>
          <form method="post" class="dp-newroom" id="dpNewRoom" hidden>
            <input type="hidden" name="action" value="add_phong">
            <input type="hidden" name="day_id" value="<?= $curDayId ?>">
            <label>Số phòng *<input class="hs-f" type="text" name="so_phong" placeholder="VD: A26" required style="width:120px"></label>
            <label>Tầng<input class="hs-f" type="text" name="tang" value="<?= h($curTang) ?>" placeholder="VD: 1" style="width:80px"></label>
            <label>Số giường<input class="hs-f" type="number" name="suc_chua" value="4" min="1" style="width:90px"></label>
            <button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-plus"/></svg>Thêm phòng vào <?= h($curDay['ten'] ?? 'dãy') ?></button>
            <span style="font-size:12px;color:var(--ink3)">Hệ thống tự tạo đủ giường theo số giường nhập.</span>
          </form>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="action" value="save_phong">
            <input type="hidden" name="cur_day" value="<?= $curDayId ?>">
            <input type="hidden" name="cur_tang" value="<?= h($curTang) ?>">
            <input type="hidden" name="cur_phong" value="<?= $curPhongId ?>">
            <div class="hs-tw">
              <table class="hs-grid dp-tbl">
                <thead><tr><th style="width:70px">Tầng</th><th>Phòng</th><th class="c">Giường</th><th>Đang ở</th><th>Loại</th><th style="min-width:140px">Tình trạng</th><th>Ghi chú</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($phongList as $p): $id = (int)$p['id']; $gd = (int)$p['giuong_dung']; $o = (int)$p['dang_o']; $tl = $gd ? min(100, $o / $gd * 100) : ($o ? 100 : 0);
                    $mau = $o > $gd ? 'var(--red)' : ($o === $gd && $gd ? 'var(--navy)' : 'var(--green)'); ?>
                <tr class="<?= $curPhongId === $id ? 'sel' : '' ?> st-<?= h($p['tinh_trang'] ?: 'binh_thuong') ?>">
                  <td><input type="text" name="tang[<?= $id ?>]" value="<?= h($p['tang']) ?>" aria-label="Tầng phòng <?= h($p['so_phong']) ?>"><input type="hidden" name="id[]" value="<?= $id ?>"></td>
                  <td><a class="ph" href="<?= h($urlPhong($id)) ?>" title="Xem / sửa giường"><?= h($p['so_phong']) ?></a><input type="hidden" name="so_phong[<?= $id ?>]" value="<?= h($p['so_phong']) ?>"></td>
                  <td class="c"><b><?= $gd ?></b><?= $p['tong_giuong'] > $gd ? '<br><span style="color:var(--red);font-size:11px">' . ((int)$p['tong_giuong'] - $gd) . ' ngừng dùng</span>' : '' ?></td>
                  <td><div class="dp-occ"><span class="t" style="color:<?= $mau ?>;font-weight:600"><?= $o ?>/<?= $gd ?> người<?= $o > $gd ? ' · vượt' : ($o < $gd ? ' · trống ' . ($gd - $o) : ' · đủ') ?></span><div class="hs-bar3"><span style="width:<?= $tl ?>%;background:<?= $mau ?>"></span></div></div></td>
                  <td style="font-size:12.5px;white-space:nowrap" title="Đối tượng: Sinh viên CTUMP">Phòng <?= $gd ?> người<br><span style="color:var(--ink3)">Phòng <?= h($curDay['gioi_tinh'] ?? '') ?></span></td>
                  <td><select name="tinh_trang[<?= $id ?>]" aria-label="Tình trạng phòng <?= h($p['so_phong']) ?>"><?php foreach ($TINH_TRANG_LABEL as $val => $label): ?><option value="<?= $val ?>" <?= $p['tinh_trang'] === $val ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></td>
                  <td><input type="text" name="ghi_chu[<?= $id ?>]" value="<?= h($p['ghi_chu']) ?>" aria-label="Ghi chú phòng <?= h($p['so_phong']) ?>"></td>
                  <td style="white-space:nowrap">
                    <a class="dp-ic" href="<?= h($urlPhong($id)) ?>" title="Giường của phòng"><svg class="i"><use href="#hi-bed"/></svg></a>
                    <a class="dp-ic" href="ktx_phong_in.php?id=<?= $id ?>" target="_blank" title="In danh sách phòng"><svg class="i"><use href="#hi-print"/></svg></a>
                    <a class="dp-ic del" href="?action=xoa_phong&amp;id=<?= $id ?>&amp;day=<?= $curDayId ?>" title="Xoá phòng" onclick="return confirm('Xoá phòng <?= h($p['so_phong']) ?>?')"><svg class="i"><use href="#hi-trash"/></svg></a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$phongList): ?><tr><td colspan="8" class="hs-empty">Chưa có phòng nào<?= $curDayId ? ' — bấm <b>Thêm phòng</b>.' : '.' ?></td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php if ($phongList): ?>
            <div class="dp-save"><button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg>Lưu thay đổi</button><span style="font-size:12.5px;color:var(--ink2)">Lưu tầng, tình trạng và ghi chú của tất cả phòng trong bảng.</span></div>
            <?php endif; ?>
          </form>
        </div>

        <!-- ===== Cot 3: Giuong cua phong dang chon ===== -->
        <div class="hs-card dp-bed-col" id="giuong">
          <?php if (!$curPhong): ?>
            <div class="dp-col-h"><h2>Giường</h2></div>
            <div class="dp-place">Bấm vào số phòng trong danh sách để xem / sửa giường.</div>
          <?php else: $soDung = count(array_filter($giuongList, fn($g) => (int)$g['su_dung'] === 1)); ?>
            <div class="dp-col-h"><h2>Giường – Phòng <?= h($curPhong['so_phong']) ?> <span class="sub"><?= $soDung ?>/<?= count($giuongList) ?> đang dùng</span></h2></div>
            <form method="post">
              <input type="hidden" name="action" value="save_giuong">
              <input type="hidden" name="phong_id" value="<?= $curPhongId ?>">
              <ul class="dp-beds">
                <?php foreach ($giuongList as $g): $gid = (int)$g['id']; $ng = $nguoiTheoGiuong[$g['ma_giuong']] ?? null; ?>
                <li class="dp-bed <?= $g['su_dung'] ? '' : 'off' ?>">
                  <input type="text" name="ma_giuong[<?= $gid ?>]" value="<?= h($g['ma_giuong']) ?>" aria-label="Mã giường"><input type="hidden" name="gid[]" value="<?= $gid ?>">
                  <input type="text" name="ghi_chu_giuong[<?= $gid ?>]" value="<?= h($g['ghi_chu']) ?>" placeholder="Ghi chú (VD: hỏng)" aria-label="Ghi chú giường">
                  <span style="display:flex;align-items:center;gap:2px">
                    <label class="dp-sw"><input type="checkbox" name="su_dung[<?= $gid ?>]" value="1" <?= $g['su_dung'] ? 'checked' : '' ?>>Dùng</label>
                    <a class="dp-ic del" href="?action=xoa_giuong&amp;id=<?= $gid ?>&amp;phong=<?= $curPhongId ?>" title="Xoá giường" onclick="return confirm('Xoá giường <?= h($g['ma_giuong']) ?>?')"><svg class="i"><use href="#hi-trash"/></svg></a>
                  </span>
                  <span class="who"><?php if ($ng): ?><span class="hs-dot" style="background:var(--pri)"></span><a href="ktx_hoc_vien.php?id=<?= (int)$ng['id'] ?>#ho-so"><?= h($ng['ho_ten']) ?></a><span class="hs-mut"><?= h((string)$ng['ma_so']) ?></span><?php elseif ($g['su_dung']): ?><span class="hs-dot" style="background:var(--green)"></span><span class="hs-ok" style="font-weight:400">Trống</span><?php else: ?><span class="hs-mut">Ngừng sử dụng</span><?php endif; ?></span>
                </li>
                <?php endforeach; ?>
                <?php if (!$giuongList): ?><li class="dp-place">Chưa có giường nào.</li><?php endif; ?>
              </ul>
              <?php if ($nguoiChuaGanGiuong): ?>
                <p style="margin:0 10px 8px;font-size:12.5px;color:var(--orange)">⚠ <?= count($nguoiChuaGanGiuong) ?> người đang ở phòng này chưa khớp mã giường: <?= h(implode(', ', array_map(fn($x) => $x['ho_ten'] . ($x['giuong'] ? ' (' . $x['giuong'] . ')' : ''), $nguoiChuaGanGiuong))) ?>.</p>
              <?php endif; ?>
              <?php if ($giuongList): ?><div class="dp-save"><button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg>Lưu giường</button><span style="font-size:12px;color:var(--ink2)">Sức chứa phòng tự cập nhật.</span></div><?php endif; ?>
            </form>
            <form method="post" class="dp-save" style="border-top:1px solid var(--line)">
              <input type="hidden" name="action" value="add_giuong">
              <input type="hidden" name="phong_id" value="<?= $curPhongId ?>">
              <input class="hs-f" type="text" name="ma_giuong" placeholder="VD: <?= h($curPhong['so_phong']) ?>.<?= str_pad((string)(count($giuongList) + 1), 2, '0', STR_PAD_LEFT) ?>" style="flex:1" required aria-label="Mã giường mới">
              <button type="submit" class="hs-btn"><svg class="i"><use href="#hi-plus"/></svg>Thêm giường</button>
            </form>
            <div class="dp-save" style="border-top:1px solid var(--line)">
              <a class="hs-btn sm" href="ktx_hoc_vien.php?edit=new&amp;phong_id=<?= $curPhongId ?>"><svg class="i"><use href="#hi-plus"/></svg>Xếp sinh viên</a>
              <a class="hs-btn sm" href="ktx_phong_in.php?id=<?= $curPhongId ?>" target="_blank"><svg class="i"><use href="#hi-print"/></svg>In danh sách</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
<?php ktx_khung_dong(); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
