<?php
// "QUẢN LÝ THIẾT BỊ - CSVC" (2026-09-16) — theo dung mo hinh 3 cot da dung o ktx_day_phong.php /
// ktx_so_do_phong.php: cay Day nha - Tang ben trai, "Danh sach phong" o giua (kem cot "Tong dinh
// muc Dien/Nuoc" = dinh muc/nguoi (site_setting) x so nguoi dang o phong), "Danh sach thiet bi"
// cua phong dang chon o phai (dong ho Dien/Nuoc voi Ma thiet bi, Loai, Chi so ban dau, Su dung,
// Ghi chu). Moi phong CHUA CO thiet bi nao se duoc TU DONG SINH 1 dong ho Dien + 1 dong ho Nuoc
// mac dinh ngay khi mo (ma dang "D"+so_phong+".001" / "N"+so_phong+".001", vd DA01.001/NA01.001)
// de khop dung vi du trong yeu cau — Quan tri co the sua/xoa/them them sau do.
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh KTX (includes/ktx_khung.php), 4 the so lieu (the
// "Dinh muc / nguoi / thang" sua truc tiep), 3 cot Day – Phong – Thiet bi dang the, thiet bi hien
// dang the gon (khong can cuon ngang). Toan bo xu ly POST/GET va viec tu sinh dong ho GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

$LOAI_LABEL = ['dien' => 'Điện', 'nuoc' => 'Nước'];
$DANH_MUC_GOI_Y = ktx_danh_muc_thiet_bi();
$DANH_MUC_GOI_Y_PHANG = array_merge($DANH_MUC_GOI_Y['dien'], $DANH_MUC_GOI_Y['nuoc']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_dinh_muc') {
    set_site_setting('ktx_dinh_muc_dien_nguoi', (string)max(0, (float)($_POST['dinh_muc_dien'] ?? 0)));
    set_site_setting('ktx_dinh_muc_nuoc_nguoi', (string)max(0, (float)($_POST['dinh_muc_nuoc'] ?? 0)));
    header('Location: ktx_thiet_bi.php?saved_dm=1&day=' . (int)($_POST['cur_day'] ?? 0) . '&phong=' . (int)($_POST['cur_phong'] ?? 0)); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_thiet_bi') {
    $phongId = (int)($_POST['phong_id'] ?? 0);
    $ma = trim($_POST['ma_thiet_bi'] ?? '');
    $loai = in_array($_POST['loai_thiet_bi'] ?? '', ['dien', 'nuoc'], true) ? $_POST['loai_thiet_bi'] : 'dien';
    $danhMuc = trim($_POST['danh_muc'] ?? '') ?: null;
    if ($phongId && $ma !== '') {
        try {
            $pdo->prepare('INSERT INTO ktx_thiet_bi (phong_id, ma_thiet_bi, loai_thiet_bi, danh_muc, chi_so_ban_dau, ngay_lap_dat, ghi_chu) VALUES (?,?,?,?,?,CURDATE(),?)')
                ->execute([$phongId, $ma, $loai, $danhMuc, (float)($_POST['chi_so_ban_dau'] ?? 0), trim($_POST['ghi_chu'] ?? '') ?: null]);
        } catch (PDOException $e) {
            header('Location: ktx_thiet_bi.php?phong=' . $phongId . '&err=trung_ma'); exit;
        }
    }
    header('Location: ktx_thiet_bi.php?phong=' . $phongId); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_thiet_bi') {
    $phongId = (int)($_POST['phong_id'] ?? 0);
    $ids = $_POST['tbid'] ?? [];
    $upd = $pdo->prepare('UPDATE ktx_thiet_bi SET ma_thiet_bi=?, loai_thiet_bi=?, danh_muc=?, chi_so_ban_dau=?, su_dung=?, ghi_chu=? WHERE id=?');
    foreach ($ids as $id) {
        $id = (int)$id;
        $upd->execute([
            trim($_POST['ma_thiet_bi'][$id] ?? ''),
            in_array($_POST['loai_thiet_bi'][$id] ?? '', ['dien', 'nuoc'], true) ? $_POST['loai_thiet_bi'][$id] : 'dien',
            trim($_POST['danh_muc'][$id] ?? '') ?: null,
            (float)($_POST['chi_so_ban_dau'][$id] ?? 0),
            !empty($_POST['su_dung'][$id]) ? 1 : 0,
            trim($_POST['ghi_chu'][$id] ?? '') ?: null,
            $id,
        ]);
    }
    header('Location: ktx_thiet_bi.php?phong=' . $phongId . '&saved_tb=1'); exit;
}

if (($_GET['action'] ?? '') === 'xoa_thiet_bi' && !empty($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT phong_id FROM ktx_thiet_bi WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    $phongId = (int)$stmt->fetchColumn();
    $pdo->prepare('DELETE FROM ktx_thiet_bi WHERE id = ?')->execute([(int)$_GET['id']]);
    header('Location: ktx_thiet_bi.php?phong=' . $phongId); exit;
}

// ---- Dinh muc / nguoi (dung chung cho toan KTX) ----
$dinhMucDien = (float)get_site_setting('ktx_dinh_muc_dien_nguoi', '50000');
$dinhMucNuoc = (float)get_site_setting('ktx_dinh_muc_nuoc_nguoi', '50000');

// ---- Day nha (kem so phong) ----
$dayList = $pdo->query('SELECT d.*, COUNT(p.id) so_phong FROM ktx_day d LEFT JOIN ktx_phong p ON p.day_id = d.id GROUP BY d.id ORDER BY d.ten')->fetchAll();

$curPhongId = (int)($_GET['phong'] ?? 0);
$curPhong = null;
$thietBiList = [];
if ($curPhongId) {
    $stmtP = $pdo->prepare("SELECT p.*, d.ten AS ten_day,
            (SELECT COUNT(*) FROM ktx_hoc_vien hv WHERE hv.phong_id = p.id AND hv.trang_thai = 'dang_o') dang_o
        FROM ktx_phong p JOIN ktx_day d ON d.id = p.day_id WHERE p.id = ?");
    $stmtP->execute([$curPhongId]);
    $curPhong = $stmtP->fetch();
    if ($curPhong) {
        $stmtTb = $pdo->prepare('SELECT * FROM ktx_thiet_bi WHERE phong_id = ? ORDER BY loai_thiet_bi, id');
        $stmtTb->execute([$curPhongId]);
        $thietBiList = $stmtTb->fetchAll();
        // Phong chua co thiet bi nao — tu dong sinh 1 dong ho Dien + 1 dong ho Nuoc mac dinh.
        if (!$thietBiList) {
            $maD = 'D' . $curPhong['so_phong'] . '.001';
            $maN = 'N' . $curPhong['so_phong'] . '.001';
            try {
                $pdo->prepare('INSERT INTO ktx_thiet_bi (phong_id, ma_thiet_bi, loai_thiet_bi, danh_muc, chi_so_ban_dau, ngay_lap_dat) VALUES (?,?,?,?,0,CURDATE())')->execute([$curPhongId, $maD, 'dien', 'Đồng hồ điện']);
                $pdo->prepare('INSERT INTO ktx_thiet_bi (phong_id, ma_thiet_bi, loai_thiet_bi, danh_muc, chi_so_ban_dau, ngay_lap_dat) VALUES (?,?,?,?,0,CURDATE())')->execute([$curPhongId, $maN, 'nuoc', 'Đồng hồ nước']);
            } catch (PDOException $e) { /* trung ma o phong khac — bo qua, admin tu them thu cong */ }
            $stmtTb->execute([$curPhongId]);
            $thietBiList = $stmtTb->fetchAll();
        }
    }
}
// (2026-10-10) Mo 1 phong (?phong=) ma khong kem ?day= -> tu chon dung day cua phong do
$curDayId = (int)($_GET['day'] ?? 0) ?: ($curPhong ? (int)$curPhong['day_id'] : (int)($dayList[0]['id'] ?? 0));
$curDay = null;
foreach ($dayList as $d) { if ((int)$d['id'] === $curDayId) { $curDay = $d; break; } }
$tangList = [];
if ($curDayId) {
    $stmtTang = $pdo->prepare("SELECT DISTINCT tang FROM ktx_phong WHERE day_id = ? AND tang IS NOT NULL AND tang <> '' ORDER BY tang");
    $stmtTang->execute([$curDayId]);
    $tangList = array_column($stmtTang->fetchAll(), 'tang');
}
$curTang = (string)($_GET['tang'] ?? '');

// ---- Danh sach phong (kem Tong dinh muc Dien/Nuoc = dinh muc/nguoi x so nguoi dang o) ----
$phongList = [];
if ($curDayId) {
    $where = 'p.day_id = ?';
    $params = [$curDayId];
    if ($curTang !== '') { $where .= ' AND p.tang = ?'; $params[] = $curTang; }
    $stmt = $pdo->prepare("SELECT p.*,
            (SELECT COUNT(*) FROM ktx_hoc_vien hv WHERE hv.phong_id = p.id AND hv.trang_thai = 'dang_o') dang_o,
            (SELECT COUNT(*) FROM ktx_giuong g WHERE g.phong_id = p.id) tong_giuong,
            (SELECT COUNT(*) FROM ktx_thiet_bi t WHERE t.phong_id = p.id) so_thiet_bi
        FROM ktx_phong p WHERE $where ORDER BY p.so_phong");
    $stmt->execute($params);
    $phongList = $stmt->fetchAll();
}

// ---- LIEN KET voi "Nhap chi so dien, nuoc theo phong" (ktx_dien_nuoc.php) (2026-09-16) ----
// Lay ky (thang/nam) GAN NHAT co du lieu cua phong dang chon de hien "Chi so ky gan nhat" ngay
// tren bang Danh sach thiet bi — doi chieu truc tiep chi so dong ho dang quan ly voi chi so da nhap.
$dnLatest = null;
if ($curPhongId) {
    // Sap xep theo "moc" (nam*12 + thang, quy tinh = so quy x3) de lay dung ban ghi GAN NHAT theo
    // thoi gian ke ca khi ban ghi do la nhap theo QUY (2026-09-16).
    $stmtDnLatest = $pdo->prepare('SELECT * FROM ktx_dien_nuoc WHERE phong_id = ? ORDER BY (nam * 12 + IF(la_quy = 1, thang * 3, thang)) DESC LIMIT 1');
    $stmtDnLatest->execute([$curPhongId]);
    $dnLatest = $stmtDnLatest->fetch() ?: null;
}

$tongSoPhong = (int)$pdo->query('SELECT COUNT(*) FROM ktx_phong')->fetchColumn();
$tongSoGiuong = (int)$pdo->query('SELECT COUNT(*) FROM ktx_giuong WHERE su_dung = 1')->fetchColumn();
$tongDangO = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien WHERE trang_thai = 'dang_o'")->fetchColumn();
$demTb = ['dien' => 0, 'nuoc' => 0, 'ngung' => 0, 'tong' => 0];
foreach ($pdo->query('SELECT loai_thiet_bi, su_dung, COUNT(*) n FROM ktx_thiet_bi GROUP BY loai_thiet_bi, su_dung')->fetchAll() as $r) {
    $demTb['tong'] += (int)$r['n'];
    if (isset($demTb[$r['loai_thiet_bi']])) { $demTb[$r['loai_thiet_bi']] += (int)$r['n']; }
    if (!(int)$r['su_dung']) { $demTb['ngung'] += (int)$r['n']; }
}
$phongChuaCoTb = (int)$pdo->query('SELECT COUNT(*) FROM ktx_phong p WHERE NOT EXISTS (SELECT 1 FROM ktx_thiet_bi t WHERE t.phong_id = p.id)')->fetchColumn();
$tien = fn($n) => number_format((float)$n, 0, ',', '.');
$GT_MAU = ['Nam' => 'var(--nam)', 'Nữ' => 'var(--nu)', 'Khác' => '#98a4b5'];
$urlPhong = fn(int $pid) => '?' . http_build_query(['day' => $curDayId, 'tang' => $curTang, 'phong' => $pid]) . '#thiet-bi';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/ktx_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.tb-wrap{display:grid;grid-template-columns:210px minmax(0,1fr) minmax(0,1.1fr);gap:12px;align-items:start}
.tb-wrap>*{min-width:0}
.tb-h{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 12px;border-bottom:1px solid var(--line)}
.tb-h h2{margin:0;font-size:15px;color:var(--navy)}
.tb-h .sub{font-size:12.5px;color:var(--ink2);font-weight:400}
.tb-days{list-style:none;margin:0;padding:6px}
.tb-days a.day{display:flex;flex-wrap:wrap;align-items:center;gap:1px 8px;padding:8px 10px;border-radius:8px;text-decoration:none;color:var(--ink)}
.tb-days a.day:hover{background:var(--pri-soft)}
.tb-days a.day.on{background:var(--pri);color:#fff}.tb-days a.day.on small{color:rgba(255,255,255,.85)}
.tb-days a.day b{font-weight:600;flex:1}.tb-days a.day small{flex-basis:100%;padding-left:16px;font-size:12px;color:var(--ink3)}
.tb-tangs{display:flex;flex-wrap:wrap;gap:4px;padding:4px 10px 8px 26px}
.tb-chip{display:inline-flex;align-items:center;height:26px;padding:0 10px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:12.5px;text-decoration:none;color:var(--ink2)}
.tb-chip:hover{border-color:var(--pri-line);color:var(--pri)}.tb-chip.on{background:var(--pri-soft);border-color:var(--pri-line);color:var(--pri);font-weight:600}
table.tb-rooms tr.sel{background:#d9e8fb!important}
table.tb-rooms tr.off{color:var(--ink3)}
table.tb-rooms a.ph{font-weight:700;color:var(--pri);text-decoration:none}
.tb-dm{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;gap:8px;align-items:end;border-top:1px solid var(--line2);padding-top:10px}
.tb-dm label{display:flex;flex-direction:column;gap:3px;font-size:12px;color:var(--ink2);font-weight:600;margin:0}
.tb-dm input{width:100%}
.tb-dn{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:9px 12px;border-bottom:1px solid var(--line2);background:#f8fbff;font-size:12.5px;color:var(--ink2)}
.tb-dn b{color:var(--ink)}
.tb-dn a{margin-left:auto}
.tb-list{list-style:none;margin:0;padding:10px;display:grid;gap:8px}
.tb-it{border:1px solid var(--line);border-left:4px solid var(--orange);border-radius:10px;padding:8px 10px;display:grid;grid-template-columns:28px 1fr 96px 1.4fr;gap:6px 8px;align-items:center}
.tb-it.nuoc{border-left-color:var(--pri)}
.tb-it.off{background:#f5f6f8;opacity:.85}
.tb-it .stt{font-size:12px;color:var(--ink3);text-align:center}
.tb-it input[type=text],.tb-it input[type=number],.tb-it select{height:30px;border:1px solid var(--line);border-radius:6px;padding:0 8px;font:inherit;font-size:13px;color:var(--ink);background:#fff;width:100%;min-width:0}
.tb-it input:focus,.tb-it select:focus{outline:0;border-color:var(--pri);box-shadow:0 0 0 3px rgba(31,111,214,.15)}
.tb-it .r2{grid-column:2/-1;display:grid;grid-template-columns:110px 1fr 1.2fr auto;gap:8px;align-items:center}
.tb-it .r2 label{display:flex;flex-direction:column;gap:2px;font-size:11px;color:var(--ink3);margin:0}
.tb-cs{font-size:12px;color:var(--ink3);line-height:1.25}.tb-cs b{display:block;font-size:14px;color:var(--navy)}
.tb-sw{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--ink2);white-space:nowrap;cursor:pointer}
.tb-sw input{width:16px;height:16px;accent-color:var(--green)}
.tb-x{display:inline-grid;place-items:center;width:28px;height:28px;border-radius:6px;color:var(--ink2);text-decoration:none}
.tb-x:hover{background:var(--red-soft);color:var(--red)}.tb-x svg{width:15px;height:15px}
.tb-foot{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 12px;border-top:1px solid var(--line);background:#fbfcfe}
.tb-add{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;padding:10px 12px;border-top:1px solid var(--line)}
.tb-add label{flex:1 1 140px;min-width:0}.tb-add label.lo{flex:0 0 96px}.tb-add label.cs{flex:0 1 110px}.tb-add .hs-f{width:100%}
.tb-add label{display:flex;flex-direction:column;gap:3px;font-size:12px;color:var(--ink2);font-weight:600;margin:0}
.tb-place{padding:30px 16px;text-align:center;color:var(--ink3)}
@media (max-width:1800px){.tb-wrap{grid-template-columns:210px minmax(0,1fr)}.tb-col3{grid-column:1/-1}}
@media (max-width:980px){.tb-wrap{grid-template-columns:1fr}.tb-it{grid-template-columns:24px 1fr}.tb-it .r2{grid-template-columns:1fr 1fr}.tb-dm{grid-template-columns:1fr 1fr}}
@media print{.tb-add,.tb-foot,.tb-dm button{display:none!important}.tb-wrap{grid-template-columns:1fr}}
</style>
<?php ob_start(); ?>
      <a class="hs-tb" href="ktx_day_phong.php<?= $curDayId ? '?day=' . $curDayId : '' ?>"><svg class="i"><use href="#hi-list"/></svg>Quản lý dãy / phòng</a>
      <a class="hs-tb" href="ktx_thiet_bi_export.php<?= $curDayId ? '?day=' . $curDayId : '' ?>"><svg class="i"><use href="#hi-down"/></svg>Xuất Excel</a>
      <button type="button" class="hs-tb" onclick="window.print()"><svg class="i"><use href="#hi-print"/></svg>In</button>
<?php $khungPhai = ob_get_clean(); ?>
<?php ktx_khung_mo('ktx_thiet_bi.php', 'QUẢN LÝ THIẾT BỊ – CSVC', 'Đồng hồ điện, nước và thiết bị theo phòng', '', $khungPhai); ?>

      <?php if (!empty($_GET['saved_dm'])): ?><div class="hs-msg ok"><span>✅ Đã lưu định mức.</span></div><?php endif; ?>
      <?php if (!empty($_GET['saved_tb'])): ?><div class="hs-msg ok"><span>✅ Đã lưu danh sách thiết bị.</span></div><?php endif; ?>
      <?php if (($_GET['err'] ?? '') === 'trung_ma'): ?><div class="hs-msg warn"><span>⚠️ Mã thiết bị này đã tồn tại.</span></div><?php endif; ?>

      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-door"/></svg></div>
            <div><div class="num"><?= $tongSoPhong ?></div><div class="lb">Phòng</div></div></div>
          <div class="sb"><div><b><?= $tongSoGiuong ?></b><span>Giường sử dụng</span></div><div><b><?= $tongDangO ?></b><span>Người đang ở</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-box"/></svg></div>
            <div><div class="num"><?= $demTb['tong'] ?></div><div class="lb">Thiết bị đang quản lý</div></div></div>
          <div class="sb"><div><b style="color:var(--orange)"><?= $demTb['dien'] ?></b><span>Điện</span></div><div><b style="color:var(--pri)"><?= $demTb['nuoc'] ?></b><span>Nước</span></div><div><b style="color:var(--ink3)"><?= $demTb['ngung'] ?></b><span>Ngừng dùng</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-coin"/></svg></div>
            <div><div class="num"><?= $tien(($dinhMucDien + $dinhMucNuoc) * $tongDangO) ?></div><div class="lb">Tổng định mức điện + nước / tháng</div></div></div>
          <form method="post" class="tb-dm">
            <input type="hidden" name="action" value="save_dinh_muc">
            <input type="hidden" name="cur_day" value="<?= $curDayId ?>">
            <input type="hidden" name="cur_phong" value="<?= $curPhongId ?>">
            <label>Điện / người (đ)<input class="hs-f" type="number" name="dinh_muc_dien" value="<?= h((string)$dinhMucDien) ?>" min="0" step="1000"></label>
            <label>Nước / người (đ)<input class="hs-f" type="number" name="dinh_muc_nuoc" value="<?= h((string)$dinhMucNuoc) ?>" min="0" step="1000"></label>
            <button type="submit" class="hs-btn" title="Lưu định mức"><svg class="i"><use href="#hi-save"/></svg>Lưu</button>
          </form>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:<?= $phongChuaCoTb ? 'var(--red-soft);color:var(--red)' : 'var(--green-soft);color:var(--green)' ?>"><svg class="i"><use href="#hi-alert"/></svg></div>
            <div><div class="num" style="color:<?= $phongChuaCoTb ? 'var(--red)' : 'var(--green)' ?>"><?= $phongChuaCoTb ?></div><div class="lb">Phòng chưa khai báo thiết bị</div></div></div>
          <div class="sb"><div><span>Mở phòng để hệ thống tự tạo đồng hồ điện + nước mặc định</span></div></div>
        </div>
      </div>

      <datalist id="dsDanhMucThietBi"><?php foreach ($DANH_MUC_GOI_Y_PHANG as $dm): ?><option value="<?= h($dm) ?>"><?php endforeach; ?></datalist>

      <div class="tb-wrap">
        <!-- ===== Cot 1: Day nha – Tang ===== -->
        <div class="hs-card">
          <div class="tb-h"><h2>Dãy nhà</h2></div>
          <ul class="tb-days">
            <?php foreach ($dayList as $d): $on = (int)$d['id'] === $curDayId; $g = isset($GT_MAU[$d['gioi_tinh']]) ? $d['gioi_tinh'] : 'Khác'; ?>
            <li>
              <a class="day <?= $on ? 'on' : '' ?>" href="?day=<?= (int)$d['id'] ?>"><span class="hs-dot" style="background:<?= $on ? '#fff' : $GT_MAU[$g] ?>"></span><b><?= h($d['ten']) ?></b><small><?= (int)$d['so_phong'] ?> phòng</small></a>
              <?php if ($on && $tangList): ?>
                <div class="tb-tangs">
                  <a class="tb-chip <?= $curTang === '' ? 'on' : '' ?>" href="?day=<?= $curDayId ?>">Tất cả tầng</a>
                  <?php foreach ($tangList as $t): ?><a class="tb-chip <?= $curTang === (string)$t ? 'on' : '' ?>" href="?day=<?= $curDayId ?>&amp;tang=<?= urlencode((string)$t) ?>">Tầng <?= h($t) ?></a><?php endforeach; ?>
                </div>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
            <?php if (!$dayList): ?><li class="tb-place">Chưa có dãy nào — thêm ở <a href="ktx_day_phong.php">Quản lý dãy / phòng</a>.</li><?php endif; ?>
          </ul>
        </div>

        <!-- ===== Cot 2: Danh sach phong ===== -->
        <div class="hs-card">
          <div class="tb-h"><h2>Phòng <span class="sub">— <?= $curDay ? h($curDay['ten']) : 'chưa chọn dãy' ?><?= $curTang !== '' ? ' · Tầng ' . h($curTang) : '' ?> · <?= count($phongList) ?> phòng</span></h2></div>
          <div class="hs-tw">
            <table class="hs-grid tb-rooms">
              <thead><tr><th class="c">Tầng</th><th>Phòng</th><th class="c">Giường</th><th class="c">Đang ở</th><th class="r">Định mức điện</th><th class="r">Định mức nước</th><th class="c">Thiết bị</th><th>Sử dụng</th></tr></thead>
              <tbody>
              <?php foreach ($phongList as $p): $id = (int)$p['id']; $dangO = (int)$p['dang_o']; $dung = $p['tinh_trang'] !== 'ngung_su_dung'; ?>
              <tr class="<?= $curPhongId === $id ? 'sel' : '' ?> <?= $dung ? '' : 'off' ?>">
                <td class="c"><?= $p['tang'] !== null && $p['tang'] !== '' ? h($p['tang']) : '<span class="hs-mut">—</span>' ?></td>
                <td><a class="ph" href="<?= h($urlPhong($id)) ?>"><?= h($p['so_phong']) ?></a></td>
                <td class="c"><?= (int)$p['tong_giuong'] ?></td>
                <td class="c"><?= $dangO ?></td>
                <td class="r"><?= $tien($dinhMucDien * $dangO) ?></td>
                <td class="r"><?= $tien($dinhMucNuoc * $dangO) ?></td>
                <td class="c"><?= (int)$p['so_thiet_bi'] ? (int)$p['so_thiet_bi'] : '<span class="hs-no" title="Chưa khai báo thiết bị">0</span>' ?></td>
                <td><?= $dung ? ($p['tinh_trang'] === 'dang_sua_chua' ? '<span class="hs-pill orange">Sửa chữa</span>' : '<span class="hs-pill green">Có</span>') : '<span class="hs-pill gray">Ngừng</span>' ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$phongList): ?><tr><td colspan="8" class="hs-empty">Chưa có phòng nào.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
          <p style="margin:0;padding:8px 12px;font-size:12px;color:var(--ink2)">Định mức phòng = định mức / người × số người đang ở. Bấm số phòng để xem / sửa thiết bị.</p>
        </div>

        <!-- ===== Cot 3: Thiet bi cua phong dang chon ===== -->
        <div class="hs-card tb-col3" id="thiet-bi">
          <?php if (!$curPhong): ?>
            <div class="tb-h"><h2>Thiết bị</h2></div>
            <div class="tb-place">Bấm vào số phòng để xem / sửa danh sách thiết bị (đồng hồ điện, nước…).</div>
          <?php else: ?>
            <div class="tb-h"><h2>Thiết bị – Phòng <?= h($curPhong['so_phong']) ?> <span class="sub"><?= h($curPhong['ten_day']) ?> · <?= count($thietBiList) ?> thiết bị · <?= (int)$curPhong['dang_o'] ?> người ở</span></h2></div>
            <div class="tb-dn">
              <?php if ($dnLatest): ?>
                <span><svg class="i" style="width:15px;height:15px;vertical-align:-3px;color:var(--pri)"><use href="#hi-cal"/></svg> Kỳ gần nhất: <b><?= h(ktx_dn_nhan_ky((int)$dnLatest['thang'], (bool)$dnLatest['la_quy'], (int)$dnLatest['nam'])) ?></b></span>
                <span>Điện: <b><?= h((string)$dnLatest['chi_so_dien_moi']) ?></b></span>
                <span>Nước: <b><?= h((string)$dnLatest['chi_so_nuoc_moi']) ?></b></span>
              <?php else: ?>
                <span>Phòng này <b>chưa có dữ liệu</b> chỉ số điện, nước.</span>
              <?php endif; ?>
              <a class="hs-btn sm" href="ktx_dien_nuoc.php?day=<?= (int)($curPhong['day_id'] ?? $curDayId) ?><?= $dnLatest ? ('&amp;nam=' . (int)$dnLatest['nam'] . ($dnLatest['la_quy'] ? '&amp;quys[]=' : '&amp;thangs[]=') . (int)$dnLatest['thang']) : '' ?>"><svg class="i"><use href="#hi-bolt"/></svg>Nhập / xem chỉ số</a>
            </div>
            <form method="post">
              <input type="hidden" name="action" value="save_thiet_bi">
              <input type="hidden" name="phong_id" value="<?= $curPhongId ?>">
              <ul class="tb-list">
                <?php foreach ($thietBiList as $i => $tb): $tid = (int)$tb['id'];
                  $laDongHo = empty($tb['danh_muc']) || mb_stripos($tb['danh_muc'], 'đồng hồ') !== false;
                  $chiSoGanNhat = null;
                  if ($laDongHo && $dnLatest) { $chiSoGanNhat = $tb['loai_thiet_bi'] === 'dien' ? $dnLatest['chi_so_dien_moi'] : $dnLatest['chi_so_nuoc_moi']; }
                ?>
                <li class="tb-it <?= $tb['loai_thiet_bi'] === 'nuoc' ? 'nuoc' : '' ?> <?= $tb['su_dung'] ? '' : 'off' ?>">
                  <span class="stt"><?= $i + 1 ?><input type="hidden" name="tbid[]" value="<?= $tid ?>"></span>
                  <input type="text" name="ma_thiet_bi[<?= $tid ?>]" value="<?= h($tb['ma_thiet_bi']) ?>" aria-label="Mã thiết bị" style="font-weight:600">
                  <select name="loai_thiet_bi[<?= $tid ?>]" aria-label="Loại"><?php foreach ($LOAI_LABEL as $val => $label): ?><option value="<?= $val ?>" <?= $tb['loai_thiet_bi'] === $val ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select>
                  <input type="text" list="dsDanhMucThietBi" name="danh_muc[<?= $tid ?>]" value="<?= h($tb['danh_muc'] ?? '') ?>" placeholder="Danh mục, VD: Bóng điện, Ổ cắm…" aria-label="Danh mục thiết bị">
                  <div class="r2">
                    <label>Chỉ số ban đầu<input type="number" step="0.1" name="chi_so_ban_dau[<?= $tid ?>]" value="<?= h((string)$tb['chi_so_ban_dau']) ?>"></label>
                    <span class="tb-cs">Chỉ số kỳ gần nhất<b><?= $chiSoGanNhat !== null ? h((string)$chiSoGanNhat) : '—' ?></b></span>
                    <label>Ghi chú<input type="text" name="ghi_chu[<?= $tid ?>]" value="<?= h($tb['ghi_chu']) ?>"></label>
                    <span style="display:flex;align-items:center;gap:4px">
                      <label class="tb-sw"><input type="checkbox" name="su_dung[<?= $tid ?>]" value="1" <?= $tb['su_dung'] ? 'checked' : '' ?>>Dùng</label>
                      <a class="tb-x" href="?action=xoa_thiet_bi&amp;id=<?= $tid ?>&amp;phong=<?= $curPhongId ?>" title="Xoá thiết bị" onclick="return confirm('Xoá thiết bị <?= h($tb['ma_thiet_bi']) ?>?')"><svg class="i"><use href="#hi-trash"/></svg></a>
                    </span>
                  </div>
                </li>
                <?php endforeach; ?>
                <?php if (!$thietBiList): ?><li class="tb-place">Chưa có thiết bị nào.</li><?php endif; ?>
              </ul>
              <?php if ($thietBiList): ?>
              <div class="tb-foot"><button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg>Lưu thay đổi</button>
                <span style="font-size:12px;color:var(--ink2)">“Chỉ số kỳ gần nhất” chỉ áp dụng cho đồng hồ điện/nước, lấy từ trang Nhập chỉ số — không sửa ở đây.</span></div>
              <?php endif; ?>
            </form>
            <form method="post" class="tb-add">
              <input type="hidden" name="action" value="add_thiet_bi">
              <input type="hidden" name="phong_id" value="<?= $curPhongId ?>">
              <label>Mã thiết bị *<input class="hs-f" type="text" name="ma_thiet_bi" placeholder="VD: D<?= h($curPhong['so_phong']) ?>.002" required></label>
              <label class="lo">Loại<select class="hs-f" name="loai_thiet_bi"><?php foreach ($LOAI_LABEL as $val => $label): ?><option value="<?= $val ?>"><?= $label ?></option><?php endforeach; ?></select></label>
              <label>Danh mục<input class="hs-f" type="text" list="dsDanhMucThietBi" name="danh_muc" placeholder="VD: Bóng điện, Máy lạnh, Vòi tắm…"></label>
              <label class="cs">Chỉ số ban đầu<input class="hs-f" type="number" step="0.1" name="chi_so_ban_dau" value="0"></label>
              <button type="submit" class="hs-btn"><svg class="i"><use href="#hi-plus"/></svg>Thêm</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
<?php ktx_khung_dong(); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
