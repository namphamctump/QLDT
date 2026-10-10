<?php
// Dashboard tong quan Ky tuc xa — tham khao muc "DASHBOARD" cua qtsoftware.vn: thong ke so
// phong/trang thai phong, tinh hinh o, tinh hinh dong phi noi tru + dien nuoc.
//
// (2026-10-10) TRANG CHINH cua nhanh KTX la "Ho so noi tru" (ktx_hoc_vien.php): moi lien ket/tab
// dang tro toi ktx_dashboard.php (tab "Quan ly KTX", chon nhanh o bia.php, nut "Tong quan KTX" cu)
// se duoc CHUYEN sang ktx_hoc_vien.php. Trang tong quan nay van giu nguyen, mo bang
// ktx_dashboard.php?xem=tong_quan (menu trai "Tổng quan KTX", xem includes/ktx_khung.php). Giu
// nguyen ten file de admin_nhanh_cua_trang()/nhom tab cua header.php van nhan dung nhanh KTX.
if (($_GET['xem'] ?? '') !== 'tong_quan') {
    header('Location: ktx_hoc_vien.php');
    exit;
}
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

$tongPhong = (int)$pdo->query('SELECT COUNT(*) FROM ktx_phong')->fetchColumn();
$phongDangSua = (int)$pdo->query("SELECT COUNT(*) FROM ktx_phong WHERE tinh_trang = 'dang_sua_chua'")->fetchColumn();
$tongSucChua = (int)$pdo->query('SELECT COALESCE(SUM(suc_chua),0) FROM ktx_phong')->fetchColumn();
$dangO = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien WHERE trang_thai = 'dang_o'")->fetchColumn();
$tyLeLapDay = $tongSucChua > 0 ? round($dangO / $tongSucChua * 100, 1) : 0;

$thang = (int)($_GET['thang'] ?? date('n')); if ($thang < 1 || $thang > 12) { $thang = (int)date('n'); }
$nam = (int)($_GET['nam'] ?? date('Y')); if ($nam < 2000 || $nam > 2100) { $nam = (int)date('Y'); }

$phiThangNay = $pdo->prepare("SELECT COUNT(*) tong, SUM(da_nop) da_nop, SUM(so_tien) tong_tien, SUM(CASE WHEN da_nop=1 THEN so_tien ELSE 0 END) da_thu
    FROM ktx_phi_noi_tru WHERE thang = ? AND nam = ?");
$phiThangNay->execute([$thang, $nam]);
$phi = $phiThangNay->fetch();
$phiConNo = (float)($phi['tong_tien'] ?? 0) - (float)($phi['da_thu'] ?? 0);

// Tinh tien co xu ly truong hop THAY DONG HO GIUA KY (chi so ve 0) — xem ktx_tieu_thu_dien()/
// ktx_tieu_thu_nuoc() trong includes/functions.php, viet lai bang SQL CASE cho dung cong thuc.
$dienNuocThangNay = $pdo->prepare("SELECT COUNT(*) tong,
    SUM(
        (CASE WHEN dien_thay_moi = 1 AND dien_chi_so_cu_truoc_thay IS NOT NULL
              THEN GREATEST(0, dien_chi_so_cu_truoc_thay - chi_so_dien_cu) + GREATEST(0, chi_so_dien_moi)
              ELSE GREATEST(0, chi_so_dien_moi - chi_so_dien_cu) END) * don_gia_dien
        +
        (CASE WHEN nuoc_thay_moi = 1 AND nuoc_chi_so_cu_truoc_thay IS NOT NULL
              THEN GREATEST(0, nuoc_chi_so_cu_truoc_thay - chi_so_nuoc_cu) + GREATEST(0, chi_so_nuoc_moi)
              ELSE GREATEST(0, chi_so_nuoc_moi - chi_so_nuoc_cu) END) * don_gia_nuoc
    ) tong_tien,
    SUM(da_thu) da_thu
    FROM ktx_dien_nuoc WHERE thang = ? AND nam = ? AND la_quy = 0");
$dienNuocThangNay->execute([$thang, $nam]);
$dn = $dienNuocThangNay->fetch();

// So hoc vien dang o CHUA KHAI BAO luu tru/tam tru lan nao, VA so hoc vien DA ROI nhung CHUA bao
// Check-out (canh bao don gian cho o dashboard — logic day du xem admin/ktx_luu_tru_tam_tru.php).
$chuaKhaiBaoLuuTru = 0;
$chuaBaoCheckout = 0;
try {
    $chuaKhaiBaoLuuTru = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien hv WHERE hv.trang_thai = 'dang_o'
        AND NOT EXISTS (SELECT 1 FROM ktx_khai_bao_luu_tru k WHERE k.hoc_vien_id = hv.id AND k.loai != 'checkout')")->fetchColumn();
    $chuaBaoCheckout = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien hv WHERE hv.trang_thai = 'da_roi'
        AND NOT EXISTS (SELECT 1 FROM ktx_khai_bao_luu_tru k WHERE k.hoc_vien_id = hv.id AND k.loai = 'checkout')")->fetchColumn();
} catch (Throwable $e) { /* chua chay migration_2026_09_17_ktx_khai_bao_luu_tru.sql */ }

$suaChuaMoi = (int)$pdo->query("SELECT COUNT(*) FROM ktx_sua_chua WHERE trang_thai != 'hoan_thanh'")->fetchColumn();
$donChoDuyet = (int)$pdo->query("SELECT COUNT(*) FROM ktx_don_dang_ky WHERE trang_thai = 'cho_duyet'")->fetchColumn();
$dotDiemDanhGanNhat = null;
try {
    $dotDiemDanhGanNhat = $pdo->query("SELECT dd.*, (SELECT COUNT(*) FROM ktx_diem_danh_chi_tiet c WHERE c.dot_id = dd.id) AS so_da_diem_danh,
            (SELECT COUNT(*) FROM ktx_diem_danh_chi_tiet c WHERE c.dot_id = dd.id AND c.co_mat = 1) AS so_co_mat
        FROM ktx_dot_diem_danh dd ORDER BY COALESCE(dd.ngay_to_chuc, dd.created_at) DESC, dd.id DESC LIMIT 1")->fetch();
} catch (Throwable $e) { /* bang chua ton tai — chua chay migration_2026_09_13_ktx_diem_danh.sql */ }
$soThanhVienBql = 0; $soCanSu = 0; $soDoiTuQuan = 0;
try {
    $soThanhVienBql = (int)$pdo->query('SELECT COUNT(*) FROM ktx_ban_quan_ly')->fetchColumn();
    $soCanSu = (int)$pdo->query("SELECT COUNT(*) FROM ktx_doi_sv WHERE loai = 'can_su_phong'")->fetchColumn();
    $soDoiTuQuan = (int)$pdo->query("SELECT COUNT(*) FROM ktx_doi_sv WHERE loai = 'doi_tu_quan'")->fetchColumn();
} catch (Throwable $e) { /* bang chua ton tai — chua chay migration_2026_09_14_ktx_bqlkt_doisv.sql */ }

$theoDay = $pdo->query("SELECT d.id, d.ten, d.gioi_tinh, COUNT(DISTINCT p.id) so_phong, COALESCE(SUM(p.suc_chua),0) suc_chua,
        COUNT(hv.id) dang_o
    FROM ktx_day d
    LEFT JOIN ktx_phong p ON p.day_id = d.id
    LEFT JOIN ktx_hoc_vien hv ON hv.phong_id = p.id AND hv.trang_thai = 'dang_o'
    GROUP BY d.id ORDER BY d.ten")->fetchAll();
// (Luu y: SUM(p.suc_chua) o tren bi nhan len theo so nguoi o vi JOIN hoc vien — tinh lai suc chua
// RIENG theo day, dung truy van khong JOIN hoc vien.)
$sucChuaTheoDay = [];
foreach ($pdo->query('SELECT d.id, COALESCE(SUM(p.suc_chua),0) sc FROM ktx_day d LEFT JOIN ktx_phong p ON p.day_id = d.id GROUP BY d.id')->fetchAll() as $r) { $sucChuaTheoDay[(int)$r['id']] = (int)$r['sc']; }

// (2026-10-10) Nguoi o + giuong theo gioi tinh (theo gioi tinh cua Day) cho 2 the dau trang
$theoGt = ['Nam' => ['o' => 0, 'sc' => 0], 'Nữ' => ['o' => 0, 'sc' => 0], 'Khác' => ['o' => 0, 'sc' => 0]];
foreach ($theoDay as $d) {
    $g = isset($theoGt[$d['gioi_tinh']]) ? $d['gioi_tinh'] : 'Khác';
    $theoGt[$g]['o'] += (int)$d['dang_o'];
    $theoGt[$g]['sc'] += $sucChuaTheoDay[(int)$d['id']] ?? 0;
}
$nguoiTheoGt = ['Nam' => 0, 'Nữ' => 0, 'Khác' => 0];
foreach ($pdo->query("SELECT gioi_tinh, COUNT(*) n FROM ktx_hoc_vien WHERE trang_thai = 'dang_o' GROUP BY gioi_tinh")->fetchAll() as $r) {
    $nguoiTheoGt[isset($nguoiTheoGt[$r['gioi_tinh']]) ? $r['gioi_tinh'] : 'Khác'] += (int)$r['n'];
}
$chuaNhapDn = max(0, $tongPhong - (int)($dn['tong'] ?? 0));

$tien = fn($n) => number_format((float)$n, 0, ',', '.');
$GT_MAU = ['Nam' => 'var(--nam)', 'Nữ' => 'var(--nu)', 'Khác' => '#98a4b5'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/ktx_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.kd-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px}
.kd-card{padding:14px 16px;display:flex;flex-direction:column;gap:10px}
.kd-h{display:flex;align-items:center;gap:10px}
.kd-h .ic{width:36px;height:36px;border-radius:9px;display:grid;place-items:center;flex:none}
.kd-h h3{margin:0;font-size:15px;color:var(--ink)}.kd-h small{display:block;color:var(--ink2);font-size:12.5px;font-weight:400;margin-top:1px}
.kd-n{display:flex;border-top:1px solid var(--line2);padding-top:10px}
.kd-n div{flex:1;padding:0 8px;border-left:1px solid var(--line2)}.kd-n div:first-child{border-left:0;padding-left:0}
.kd-n b{display:block;font-size:20px;line-height:1.2}.kd-n span{font-size:12.5px;color:var(--ink2)}
.kd-l{display:flex;gap:6px;flex-wrap:wrap;margin-top:auto;padding-top:4px}
.kd-todo{list-style:none;margin:0;padding:0;border-top:1px solid var(--line2)}
.kd-todo li{border-bottom:1px solid var(--line2)}.kd-todo li:last-child{border-bottom:0}
.kd-todo a{display:flex;align-items:center;gap:10px;padding:8px 2px;text-decoration:none;color:var(--ink)}
.kd-todo a:hover{background:#f7faff}
.kd-todo b{min-width:34px;text-align:center;font-size:15px;padding:1px 6px;border-radius:7px}
.kd-todo .warn b{background:var(--red-soft);color:var(--red)}.kd-todo .ok b{background:var(--green-soft);color:var(--green)}
.kd-todo .go{margin-left:auto;color:var(--ink3);font-size:12.5px;white-space:nowrap}
.kd-todo .ok .go{color:var(--green)}
.kd-fill{display:flex;align-items:center;gap:8px;min-width:150px}.kd-fill .hs-bar3{flex:1}
@media (max-width:1300px){.kd-grid{grid-template-columns:1fr 1fr}}
@media (max-width:640px){.kd-grid{grid-template-columns:1fr}}
</style>
<?php ob_start(); ?>
    <form method="get" style="display:flex;align-items:center;gap:4px;margin:0">
      <input type="hidden" name="xem" value="tong_quan">
      <span class="hs-tb">Năm <select name="nam" onchange="this.form.submit()"><?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?><option <?= $y === $nam ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?></select></span>
      <span class="hs-tb">Tháng <select name="thang" onchange="this.form.submit()"><?php for ($m = 1; $m <= 12; $m++): ?><option <?= $m === $thang ? 'selected' : '' ?>><?= $m ?></option><?php endfor; ?></select></span>
    </form>
<?php $khungPhai = ob_get_clean(); ?>
<?php ktx_khung_mo(KTX_TRANG_TONG_QUAN, 'TỔNG QUAN KÝ TÚC XÁ', 'Số liệu tháng ' . $thang . '/' . $nam, '', $khungPhai); ?>

      <!-- ===== Hang 1: 4 the so lieu chinh ===== -->
      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num"><?= $dangO ?></div><div class="lb">Người học đang ở</div></div></div>
          <div class="hs-gt" style="border-top:1px solid var(--line2);padding-top:10px">
            <div class="lg">
              <?php foreach ($nguoiTheoGt as $g => $n): if ($g === 'Khác' && !$n) continue; ?>
              <a href="ktx_hoc_vien.php?trang_thai=dang_o&amp;gioi_tinh=<?= urlencode($g) ?>"><span class="hs-dot" style="background:<?= $GT_MAU[$g] ?>"></span><?= h($g) ?> <b><?= $n ?></b><?= $dangO ? ' <span class="hs-mut">(' . round($n / $dangO * 100) . '%)</span>' : '' ?></a>
              <?php endforeach; ?>
            </div>
            <div class="bar" aria-hidden="true"><?php foreach ($nguoiTheoGt as $g => $n): if ($dangO && $n): ?><span style="width:<?= round($n / $dangO * 100, 2) ?>%;background:<?= $GT_MAU[$g] ?>"></span><?php endif; endforeach; ?></div>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-bed"/></svg></div>
            <div><div class="num"><?= $tyLeLapDay ?>%</div><div class="lb">Công suất giường</div></div></div>
          <table class="hs-gtb">
            <thead><tr><th></th><th>Đã ở</th><th>Trống</th><th>Tổng</th></tr></thead>
            <tbody>
            <?php foreach ($theoGt as $g => $x): if (!$x['sc'] && !$x['o']) continue; ?>
              <tr><th><span class="hs-dot" style="background:<?= $GT_MAU[$g] ?>"></span><?= $g === 'Khác' ? 'Dãy chung' : 'Giường ' . h(mb_strtolower($g)) ?></th><td><?= $x['o'] ?></td><td class="<?= $x['sc'] > $x['o'] ? 'tr' : '' ?>"><?= max(0, $x['sc'] - $x['o']) ?></td><td><?= $x['sc'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th>Cộng (<?= $tongPhong ?> phòng)</th><td><?= $dangO ?></td><td class="<?= $tongSucChua > $dangO ? 'tr' : '' ?>"><?= max(0, $tongSucChua - $dangO) ?></td><td><?= $tongSucChua ?></td></tr></tfoot>
          </table>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-coin"/></svg></div>
            <div><div class="num"><?= $tien($phi['da_thu'] ?? 0) ?></div><div class="lb">Phí nội trú đã thu T<?= $thang ?>/<?= $nam ?></div></div></div>
          <div class="sb">
            <div><b style="color:<?= $phiConNo > 0 ? 'var(--red)' : 'var(--green)' ?>"><?= $tien($phiConNo) ?></b><span>Còn nợ (đ)</span></div>
            <div><b><?= (int)($phi['da_nop'] ?? 0) ?>/<?= (int)($phi['tong'] ?? 0) ?></b><span>Hồ sơ đã nộp</span></div>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:#fdeee6;color:#d4561f"><svg class="i"><use href="#hi-bolt"/></svg></div>
            <div><div class="num"><?= $tien($dn['tong_tien'] ?? 0) ?></div><div class="lb">Điện, nước phải thu T<?= $thang ?>/<?= $nam ?></div></div></div>
          <div class="sb">
            <div><b><?= (int)($dn['tong'] ?? 0) ?>/<?= $tongPhong ?></b><span>Phòng đã nhập chỉ số</span></div>
            <div><b style="color:var(--green)"><?= (int)($dn['da_thu'] ?? 0) ?></b><span>Phòng đã thu</span></div>
          </div>
        </div>
      </div>

      <!-- ===== Hang 2: viec can xu ly + cac khoi chuc nang ===== -->
      <div class="kd-grid">
        <div class="hs-card kd-card" style="grid-row:span 2">
          <div class="kd-h"><span class="ic" style="background:var(--red);color:#fff"><svg class="i"><use href="#hi-alert"/></svg></span><h3>Việc cần xử lý<small>Bấm từng dòng để đi tới trang xử lý</small></h3></div>
          <ul class="kd-todo">
            <?php foreach ([
                [$donChoDuyet, 'Đơn đăng ký KTX chờ duyệt', 'ktx_don_dang_ky.php'],
                [$suaChuaMoi, 'Yêu cầu sửa chữa chưa hoàn thành', 'ktx_sua_chua.php'],
                [$chuaKhaiBaoLuuTru, 'Đang ở chưa khai báo lưu trú/tạm trú', 'ktx_luu_tru_tam_tru.php?tab=dang_o&trang_thai=chua_khai_rieng'],
                [$chuaBaoCheckout, 'Đã rời KTX chưa báo Check-out', 'ktx_luu_tru_tam_tru.php?tab=checkout'],
                [$chuaNhapDn, 'Phòng chưa nhập chỉ số điện, nước T' . $thang, 'ktx_dien_nuoc.php?nam=' . $nam . '&thangs[]=' . $thang],
                [$phongDangSua, 'Phòng đang sửa chữa', 'ktx_so_do_phong.php'],
            ] as [$n, $nhan, $href]): ?>
            <li class="<?= $n ? 'warn' : 'ok' ?>"><a href="<?= h($href) ?>"><b><?= $n ?></b><span><?= h($nhan) ?></span><span class="go"><?= $n ? 'Xử lý →' : '✓ Không có' ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="hs-card kd-card">
          <div class="kd-h"><span class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-door"/></svg></span><h3>Phòng – Cơ sở vật chất<small>Tổng số phòng đang quản lý</small></h3></div>
          <div class="kd-n">
            <div><b><?= $tongPhong ?></b><span>Tổng phòng</span></div>
            <div><b style="color:<?= $phongDangSua ? 'var(--red)' : 'var(--green)' ?>"><?= $phongDangSua ?></b><span>Đang sửa chữa</span></div>
            <div><b><?= $tongSucChua ?></b><span>Tổng chỗ ở</span></div>
          </div>
          <div class="kd-l"><a class="hs-btn sm" href="ktx_day_phong.php"><svg class="i"><use href="#hi-list"/></svg>Dãy / phòng</a><a class="hs-btn sm" href="ktx_so_do_phong.php"><svg class="i"><use href="#hi-bed"/></svg>Sơ đồ</a><a class="hs-btn sm" href="ktx_thiet_bi.php"><svg class="i"><use href="#hi-box"/></svg>Thiết bị</a></div>
        </div>

        <div class="hs-card kd-card">
          <div class="kd-h"><span class="ic" style="background:#fdeee6;color:#d4561f"><svg class="i"><use href="#hi-bolt"/></svg></span><h3>Điện, nước<small>Tháng <?= $thang ?>/<?= $nam ?></small></h3></div>
          <div class="kd-n">
            <div><b><?= $tien($dn['tong_tien'] ?? 0) ?></b><span>Tổng phải thu (đ)</span></div>
            <div><b><?= (int)($dn['da_thu'] ?? 0) ?></b><span>Phòng đã thu</span></div>
          </div>
          <div class="kd-l"><a class="hs-btn sm" href="ktx_dien_nuoc.php">Nhập chỉ số</a><a class="hs-btn sm" href="ktx_dien_nuoc_tonghop.php">Tổng hợp</a><a class="hs-btn sm" href="ktx_ky_thu.php">Kỳ thu (Excel)</a></div>
        </div>

        <div class="hs-card kd-card">
          <div class="kd-h"><span class="ic" style="background:#e3f5f7;color:#1f7f8f"><svg class="i"><use href="#hi-check"/></svg></span><h3>Điểm danh theo đợt<small><?= $dotDiemDanhGanNhat ? 'Gần nhất: ' . h($dotDiemDanhGanNhat['ten']) : 'Chưa có đợt nào (VD: Tập huấn PCCC)' ?></small></h3></div>
          <?php if ($dotDiemDanhGanNhat): ?>
          <div class="kd-n">
            <div><b style="color:var(--green)"><?= (int)$dotDiemDanhGanNhat['so_co_mat'] ?></b><span>Có mặt</span></div>
            <div><b><?= (int)$dotDiemDanhGanNhat['so_da_diem_danh'] ?></b><span>Đã ghi nhận</span></div>
            <div><b><?= $dotDiemDanhGanNhat['ngay_to_chuc'] ? h(format_date_vn($dotDiemDanhGanNhat['ngay_to_chuc'])) : '—' ?></b><span>Ngày tổ chức</span></div>
          </div>
          <?php endif; ?>
          <div class="kd-l"><a class="hs-btn sm" href="ktx_diem_danh.php"><svg class="i"><use href="#hi-check"/></svg>Quản lý điểm danh</a><?php if ($dotDiemDanhGanNhat): ?><a class="hs-btn sm" href="ktx_diem_danh_chi_tiet.php?dot_id=<?= (int)$dotDiemDanhGanNhat['id'] ?>">Mở đợt gần nhất</a><?php endif; ?></div>
        </div>

        <div class="hs-card kd-card">
          <div class="kd-h"><span class="ic" style="background:#eceefb;color:#3f52ad"><svg class="i"><use href="#hi-shield"/></svg></span><h3>Ban Quản lý &amp; Đội SV<small>Ban Quản lý KTX, Cán sự phòng, Đội SV tự quản</small></h3></div>
          <div class="kd-n">
            <div><b><?= $soThanhVienBql ?></b><span>Ban Quản lý</span></div>
            <div><b><?= $soCanSu ?></b><span>Cán sự phòng</span></div>
            <div><b><?= $soDoiTuQuan ?></b><span>Đội tự quản</span></div>
          </div>
          <div class="kd-l"><a class="hs-btn sm" href="ktx_ban_quan_ly.php">Ban Quản lý</a><a class="hs-btn sm" href="ktx_doi_sv.php">Cán sự / Đội SV</a></div>
        </div>
      </div>

      <!-- ===== Tinh hinh theo day nha ===== -->
      <div class="hs-card">
        <div class="hs-tool"><h2>TÌNH HÌNH THEO DÃY NHÀ</h2><span class="hs-sp"></span><a class="hs-btn sm" href="ktx_so_do_phong.php"><svg class="i"><use href="#hi-bed"/></svg>Xem sơ đồ</a></div>
        <div class="hs-tw">
          <table class="hs-grid">
            <thead><tr><th>Dãy nhà</th><th>Giới tính</th><th class="c">Số phòng</th><th class="c">Tổng chỗ</th><th class="c">Đang ở</th><th class="c">Còn trống</th><th>Tỷ lệ lấp đầy</th></tr></thead>
            <tbody>
            <?php $tc = 0; $to = 0; $tp = 0;
            foreach ($theoDay as $d): $sc = $sucChuaTheoDay[(int)$d['id']] ?? 0; $o = (int)$d['dang_o']; $tl = $sc > 0 ? round($o / $sc * 100) : 0; $tc += $sc; $to += $o; $tp += (int)$d['so_phong'];
                $mau = $tl > 100 ? 'var(--red)' : ($tl >= 90 ? 'var(--orange)' : 'var(--green)'); ?>
            <tr>
              <td><b><?= h($d['ten']) ?></b></td>
              <td><span class="hs-pill <?= $d['gioi_tinh'] === 'Nam' ? 'blue' : ($d['gioi_tinh'] === 'Nữ' ? 'red' : 'gray') ?>" style="<?= $d['gioi_tinh'] === 'Nữ' ? 'background:#fce8f0;color:var(--nu)' : '' ?>"><?= h($d['gioi_tinh'] ?: '—') ?></span></td>
              <td class="c"><?= (int)$d['so_phong'] ?></td>
              <td class="c"><?= $sc ?></td>
              <td class="c"><?= $o ?></td>
              <td class="c" style="color:<?= $sc > $o ? 'var(--green)' : 'var(--ink3)' ?>;font-weight:600"><?= max(0, $sc - $o) ?></td>
              <td><div class="kd-fill"><div class="hs-bar3"><span style="width:<?= min(100, $tl) ?>%;background:<?= $mau ?>"></span></div><b style="min-width:42px;text-align:right"><?= $tl ?>%</b></div></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$theoDay): ?><tr><td colspan="7" class="hs-empty">Chưa có dữ liệu — vào <a href="ktx_day_phong.php">Quản lý dãy/phòng</a> để thêm.</td></tr>
            <?php else: $tlc = $tc ? round($to / $tc * 100) : 0; ?>
            <tr style="font-weight:700;background:#f1f5fb"><td colspan="2">Toàn KTX</td><td class="c"><?= $tp ?></td><td class="c"><?= $tc ?></td><td class="c"><?= $to ?></td><td class="c"><?= max(0, $tc - $to) ?></td><td><?= $tlc ?>%</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php ktx_khung_dong(); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
