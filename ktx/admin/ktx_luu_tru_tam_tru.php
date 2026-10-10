<?php
// "Quản lý khai báo lưu trú / tạm trú" (2026-09-17, bo sung Check-out 2026-09-17) — so theo doi +
// nhac lich NOI BO viec: (1) khai bao luu tru (cong dan Viet Nam) / tam tru (nguoi nuoc ngoai) khi
// hoc vien VAO O, va (2) bao Check-out/Tra phong khi hoc vien RA KHOI KTX (thanh ly HD/huy dang
// ky) — dung CHUNG 1 bang lich su ktx_khai_bao_luu_tru, phan biet qua cot "loai".
//
// QUAN TRONG: trang nay KHONG ket noi/tu dong khai bao len Cong dich vu cong cua Bo Cong an (khong
// co API cong khai cho don vi ngoai nganh, va he thong TUYET DOI khong duoc phep tu dong nhap tai
// khoan/mat khau thay nguoi dung). Quy trinh thuc te: Ban quan ly bam nut "Khai báo trên Cổng Bộ
// Công an ↗" de tu dang nhap va thao tac THAT tren cong đó, roi quay lai trang nay bam "📝 Ghi
// nhận" de luu lai ngay da khai/da bao — trang se tu nhac khi sap/qua han hoac khi co hoc vien roi
// KTX ma chua bao Check-out.
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh KTX (includes/ktx_khung.php), 4 the so lieu bam duoc,
// 2 tab dang nut, khoi "Quy trinh & cau hinh" thu gon, bo loc tu ap dung, khung xuat Excel thu gon,
// thanh thao tac hang loat ghim tren bang (dem so dong da chon), tim nhanh trong bang. Toan bo xu ly
// POST/GET (ghi_nhan, ghi_nhan_hang_loat, ghi_nhan_hang_loat_checkout, luu_cau_hinh, xoa_lich_su),
// cach tinh trang thai, sap xep va cac form xuat Excel GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

const KTX_CONG_BCA_URL = 'https://tbltkbtt.bocongan.gov.vn/temporary-residence-declaration-management/temporary-residence-declaration';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ghi_nhan') {
    $hocVienId = (int)($_POST['hoc_vien_id'] ?? 0);
    $ngayKhaiBao = trim($_POST['ngay_khai_bao'] ?? '') ?: date('Y-m-d');
    $ngayHetHan = trim($_POST['ngay_het_han'] ?? '') ?: null;
    if ($hocVienId) {
        if (($_POST['loai_ghi_nhan'] ?? '') === 'checkout') {
            $loai = 'checkout';
            $ngayHetHan = null; // Check-out khong co "han khai lai"
        } else {
            $stmtHv = $pdo->prepare('SELECT quoc_tich FROM ktx_hoc_vien WHERE id = ?');
            $stmtHv->execute([$hocVienId]);
            $loai = ktx_loai_khai_bao($stmtHv->fetchColumn() ?: null);
        }
        $pdo->prepare('INSERT INTO ktx_khai_bao_luu_tru (hoc_vien_id, loai, ngay_khai_bao, ngay_het_han, nguoi_khai_bao, ghi_chu) VALUES (?,?,?,?,?,?)')
            ->execute([$hocVienId, $loai, $ngayKhaiBao, $ngayHetHan, $_SESSION['admin_name'] ?? 'Quản trị viên', trim($_POST['ghi_chu'] ?? '') ?: null]);
    }
    header('Location: ktx_luu_tru_tam_tru.php?saved=1' . (!empty($_POST['redirect_qs']) ? '&' . $_POST['redirect_qs'] : '')); exit;
}
// Ghi nhan HANG LOAT (tick chon nhieu hoc vien, cung 1 ngay khai bao/het han/ghi chu) — dung cho
// tab "dang_o" (2026-09-17). Moi hoc vien van tu suy loai (VN/NN) rieng theo quoc tich cua chinh ho.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ghi_nhan_hang_loat') {
    $ids = array_values(array_unique(array_map('intval', $_POST['chon'] ?? [])));
    $ngayKhaiBao = trim($_POST['ngay_khai_bao'] ?? '') ?: date('Y-m-d');
    $ngayHetHan = trim($_POST['ngay_het_han'] ?? '') ?: null;
    $ghiChu = trim($_POST['ghi_chu'] ?? '') ?: null;
    $nguoiKhaiBao = $_SESSION['admin_name'] ?? 'Quản trị viên';
    if ($ids) {
        $stmtQt = $pdo->prepare('SELECT quoc_tich FROM ktx_hoc_vien WHERE id = ?');
        $ins = $pdo->prepare('INSERT INTO ktx_khai_bao_luu_tru (hoc_vien_id, loai, ngay_khai_bao, ngay_het_han, nguoi_khai_bao, ghi_chu) VALUES (?,?,?,?,?,?)');
        foreach ($ids as $hvId) {
            $stmtQt->execute([$hvId]);
            $loai = ktx_loai_khai_bao($stmtQt->fetchColumn() ?: null);
            $ins->execute([$hvId, $loai, $ngayKhaiBao, $ngayHetHan, $nguoiKhaiBao, $ghiChu]);
        }
    }
    header('Location: ktx_luu_tru_tam_tru.php?saved=1&so_luong=' . count($ids) . (!empty($_POST['redirect_qs']) ? '&' . $_POST['redirect_qs'] : '')); exit;
}
// Ghi nhan HANG LOAT cho tab "checkout" — cung logic nhung loai luon la 'checkout', khong co han.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ghi_nhan_hang_loat_checkout') {
    $ids = array_values(array_unique(array_map('intval', $_POST['chon'] ?? [])));
    $ngayKhaiBao = trim($_POST['ngay_khai_bao'] ?? '') ?: date('Y-m-d');
    $ghiChu = trim($_POST['ghi_chu'] ?? '') ?: null;
    $nguoiKhaiBao = $_SESSION['admin_name'] ?? 'Quản trị viên';
    if ($ids) {
        $ins = $pdo->prepare("INSERT INTO ktx_khai_bao_luu_tru (hoc_vien_id, loai, ngay_khai_bao, ngay_het_han, nguoi_khai_bao, ghi_chu) VALUES (?, 'checkout', ?, NULL, ?, ?)");
        foreach ($ids as $hvId) { $ins->execute([$hvId, $ngayKhaiBao, $nguoiKhaiBao, $ghiChu]); }
    }
    header('Location: ktx_luu_tru_tam_tru.php?tab=checkout&saved=1&so_luong=' . count($ids)); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'luu_cau_hinh') {
    set_site_setting('ktx_han_khai_bao_ngay', (string)max(0, (int)($_POST['han_khai_bao_ngay'] ?? 3)));
    set_site_setting('ktx_han_nhac_het_han_ngay', (string)max(0, (int)($_POST['han_nhac_het_han_ngay'] ?? 7)));
    header('Location: ktx_luu_tru_tam_tru.php?saved_cf=1'); exit;
}
if (($_GET['action'] ?? '') === 'xoa_lich_su' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM ktx_khai_bao_luu_tru WHERE id = ?')->execute([(int)$_GET['id']]);
    header('Location: ktx_luu_tru_tam_tru.php'); exit;
}

$hanKhaiBaoNgay = (int)get_site_setting('ktx_han_khai_bao_ngay', '3');
$hanNhacHetHanNgay = (int)get_site_setting('ktx_han_nhac_het_han_ngay', '7');

// ---- Tab + bo loc ----
$tabChon = ($_GET['tab'] ?? 'dang_o') === 'checkout' ? 'checkout' : 'dang_o';
$loaiChon = $_GET['loai'] ?? 'tat_ca'; // tat_ca | vn | nn
$trangThaiChon = $_GET['trang_thai'] ?? 'tat_ca'; // tat_ca | can_nhac | qua_han | da_xong
$dayChon = (int)($_GET['day'] ?? 0);

$dayList = $pdo->query('SELECT * FROM ktx_day ORDER BY ten')->fetchAll();

// So hoc vien DA ROI (thanh ly HD/huy dang ky) nhung CHUA co ban ghi "checkout" nao — dung chung
// cho ca so dem canh bao lan danh sach chi tiet cua tab "checkout" ben duoi.
$stmtCheckout = $pdo->query("SELECT hv.* FROM ktx_hoc_vien hv WHERE hv.trang_thai = 'da_roi'
    AND NOT EXISTS (SELECT 1 FROM ktx_khai_bao_luu_tru k WHERE k.hoc_vien_id = hv.id AND k.loai = 'checkout')
    ORDER BY COALESCE(hv.ngay_thanh_ly_hd, hv.created_at) DESC");
$canBaoCheckoutList = $stmtCheckout->fetchAll();


// ---- Tab "dang_o" (mac dinh) ----
$where = "hv.trang_thai = 'dang_o'";
$params = [];
if ($dayChon) { $where .= ' AND p.day_id = ?'; $params[] = $dayChon; }
$stmtHv = $pdo->prepare("SELECT hv.*, p.so_phong, d.ten AS ten_day FROM ktx_hoc_vien hv
    LEFT JOIN ktx_phong p ON p.id = hv.phong_id LEFT JOIN ktx_day d ON d.id = p.day_id
    WHERE $where ORDER BY d.ten, p.so_phong, hv.ho_ten");
$stmtHv->execute($params);
$hocVienAll = $stmtHv->fetchAll();

// Lay ban ghi khai bao GAN NHAT (loai luu_tru_vn/tam_tru_nn, KHONG tinh checkout) cho tung hoc vien.
$hocVienIds = array_column($hocVienAll, 'id');
$khaiBaoMoiNhat = [];
if ($hocVienIds) {
    $in = implode(',', array_fill(0, count($hocVienIds), '?'));
    $stmtKb = $pdo->prepare("SELECT * FROM ktx_khai_bao_luu_tru WHERE hoc_vien_id IN ($in) AND loai != 'checkout' ORDER BY hoc_vien_id, ngay_khai_bao DESC, id DESC");
    $stmtKb->execute($hocVienIds);
    foreach ($stmtKb->fetchAll() as $r) {
        $hvId = (int)$r['hoc_vien_id'];
        if (!isset($khaiBaoMoiNhat[$hvId])) { $khaiBaoMoiNhat[$hvId] = $r; } // dong DAU TIEN gap = gan nhat (da ORDER BY DESC)
    }
}

$today = date('Y-m-d');
$hanNhacNgay = date('Y-m-d', strtotime("+{$hanNhacHetHanNgay} days"));

// Tinh trang thai + loc theo bo loc dang chon.
$hocVienList = [];
$demTong = ['vn' => 0, 'nn' => 0, 'chua_khai' => 0, 'qua_han' => 0, 'sap_het_han' => 0];
foreach ($hocVienAll as $hv) {
    $loai = ktx_loai_khai_bao($hv['quoc_tich']);
    $demTong[$loai === 'luu_tru_vn' ? 'vn' : 'nn']++;
    $kb = $khaiBaoMoiNhat[(int)$hv['id']] ?? null;

    if (!$kb) {
        $trangThai = 'chua_khai';
        $demTong['chua_khai']++;
    } elseif ($kb['ngay_het_han'] && $kb['ngay_het_han'] < $today) {
        $trangThai = 'qua_han';
        $demTong['qua_han']++;
    } elseif ($kb['ngay_het_han'] && $kb['ngay_het_han'] <= $hanNhacNgay) {
        $trangThai = 'sap_het_han';
        $demTong['sap_het_han']++;
    } else {
        $trangThai = 'da_xong';
    }

    if ($loaiChon === 'vn' && $loai !== 'luu_tru_vn') { continue; }
    if ($loaiChon === 'nn' && $loai !== 'tam_tru_nn') { continue; }
    if ($trangThaiChon === 'can_nhac' && !in_array($trangThai, ['chua_khai', 'sap_het_han', 'qua_han'], true)) { continue; }
    if ($trangThaiChon === 'qua_han' && !in_array($trangThai, ['chua_khai', 'qua_han'], true)) { continue; }
    if ($trangThaiChon === 'da_xong' && $trangThai !== 'da_xong') { continue; }
    // 2 loc "sach" theo dung 2 nhan nguoi dung yeu cau (2026-09-30) — CHUA KHAI BAO = chua tung co
    // ban ghi khai bao nao; DA KHAI BAO = da co it nhat 1 ban ghi (bat ke con han hay sap/da qua
    // han), khac voi loc "qua_han"/"da_xong" o tren (chia nho hon theo han con lai).
    if ($trangThaiChon === 'chua_khai_rieng' && $trangThai !== 'chua_khai') { continue; }
    if ($trangThaiChon === 'da_khai_rieng' && $trangThai === 'chua_khai') { continue; }
    // Loc CHINH XAC tung nhom cho cac dong so lieu canh bao co the bam vao (2026-09-30) — khac voi
    // 'qua_han' o tren (gop ca chua_khai), 2 gia tri nay CHI khop DUNG 1 nhom, dung so voi $demTong.
    if ($trangThaiChon === 'qua_han_rieng' && $trangThai !== 'qua_han') { continue; }
    if ($trangThaiChon === 'sap_het_han_rieng' && $trangThai !== 'sap_het_han') { continue; }

    $hv['_loai'] = $loai;
    $hv['_kb'] = $kb;
    $hv['_trang_thai'] = $trangThai;
    $hocVienList[] = $hv;
}

$TRANG_THAI_LABEL = [
    'chua_khai' => ['🔴 Chưa khai báo', '#fdeaea'],
    'qua_han' => ['🔴 Đã quá hạn khai lại', '#fdeaea'],
    'sap_het_han' => ['🟡 Sắp hết hạn', '#fff6da'],
    'da_xong' => ['✅ Đã khai báo', '#eef8ee'],
];

// Sap xep bang khi bam vao tieu de cot (2026-09-30) — mac dinh (chua bam gi) giu nguyen thu tu cu
// (Day/Phong/Ho ten tu SQL); bam 1 cot se sap theo cot do, bam lai lan nua dao chieu tang/giam.
$ltCotSapXep = ['ho_ten', 'phong', 'quoc_tich', 'loai', 'ngay_vao_o', 'cccd', 'khai_bao', 'han_khai_lai', 'trang_thai'];
$sortKey = $_GET['sort'] ?? '';
if (!in_array($sortKey, $ltCotSapXep, true)) { $sortKey = ''; }
$sortDir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

function ktx_ltt_gia_tri_sap_xep(array $hv, string $key) {
    $kb = $hv['_kb'] ?? null;
    switch ($key) {
        case 'ho_ten': return mb_strtolower($hv['ho_ten'] ?? '', 'UTF-8');
        case 'phong': return mb_strtolower(($hv['ten_day'] ?? '') . '-' . ($hv['so_phong'] ?? ''), 'UTF-8');
        case 'quoc_tich': return mb_strtolower($hv['quoc_tich'] ?? '', 'UTF-8');
        case 'loai': return $hv['_loai'] ?? '';
        case 'ngay_vao_o': return $hv['ngay_dang_ky'] ?? '';
        case 'cccd': return $hv['cccd'] ?? '';
        case 'khai_bao': return $kb['ngay_khai_bao'] ?? '';
        case 'han_khai_lai': return $kb['ngay_het_han'] ?? '';
        case 'trang_thai': return $hv['_trang_thai'] ?? '';
        default: return '';
    }
}
if ($sortKey !== '') {
    usort($hocVienList, function ($a, $b) use ($sortKey, $sortDir) {
        $va = ktx_ltt_gia_tri_sap_xep($a, $sortKey);
        $vb = ktx_ltt_gia_tri_sap_xep($b, $sortKey);
        $cmp = strcmp((string)$va, (string)$vb);
        return $sortDir === 'desc' ? -$cmp : $cmp;
    });
}

// Link tieu de cot: bam de chon cot sap xep, bam lai lan nua (dang la cot hien tai) thi dao chieu.
function ktx_ltt_link_tieu_de(string $key, string $nhan, string $sortKeyHienTai, string $sortDirHienTai): string {
    $dirMoi = ($sortKeyHienTai === $key && $sortDirHienTai === 'asc') ? 'desc' : 'asc';
    $muiTen = $sortKeyHienTai === $key ? ($sortDirHienTai === 'asc' ? ' ▲' : ' ▼') : '';
    $qs = array_merge($_GET, ['sort' => $key, 'dir' => $dirMoi]);
    return '<a class="lt-sort' . ($sortKeyHienTai === $key ? ' on' : '') . '" href="?' . h(http_build_query($qs)) . '">' . h($nhan) . $muiTen . '</a>';
}

// Hoc vien dang mo form "Ghi nhan" don le (?ghi_nhan=) — tim trong dung danh sach cua tab dang xem.
$ghiNhanChoId = (int)($_GET['ghi_nhan'] ?? 0);
$ghiNhanCho = null;
if ($ghiNhanChoId) {
    foreach ($tabChon === 'checkout' ? $canBaoCheckoutList : $hocVienAll as $hv) { if ((int)$hv['id'] === $ghiNhanChoId) { $ghiNhanCho = $hv; break; } }
}
$TT_PILL = ['chua_khai' => ['red', 'Chưa khai báo'], 'qua_han' => ['red', 'Quá hạn khai lại'], 'sap_het_han' => ['orange', 'Sắp hết hạn'], 'da_xong' => ['green', 'Đã khai báo']];
$hrefCanhBao = fn(string $tt) => '?' . http_build_query(['tab' => 'dang_o', 'day' => $dayChon, 'loai' => 'tat_ca', 'trang_thai' => $tt]);
$tongDangO = $demTong['vn'] + $demTong['nn'];
$coLoc = $dayChon || $loaiChon !== 'tat_ca' || $trangThaiChon !== 'tat_ca';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/ktx_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.lt-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.lt-tabs a{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 16px;border-radius:10px;border:1px solid var(--line);background:#fff;text-decoration:none;color:var(--ink);font-weight:600}
.lt-tabs a:hover{border-color:var(--pri-line)}
.lt-tabs a.on{background:var(--pri);border-color:var(--pri);color:#fff;box-shadow:0 2px 6px rgba(31,111,214,.3)}
.lt-tabs .n{min-width:22px;height:20px;padding:0 6px;border-radius:10px;background:var(--red);color:#fff;font-size:12px;display:grid;place-items:center}
.lt-tabs a.on .n{background:#fff;color:var(--red)}
.lt-cong{background:#fff!important;color:var(--navy)!important;font-weight:600}
.lt-cong:hover{background:#eaf1fc!important}
.lt-kl a{text-decoration:none;color:inherit;display:block}
.lt-kl a:hover .lb{color:var(--pri)}
details.lt-info{margin-bottom:12px}
details.lt-info>summary{cursor:pointer;list-style:none;display:flex;align-items:center;gap:8px;padding:10px 14px;font-size:13.5px;color:var(--ink2)}
details.lt-info>summary::-webkit-details-marker{display:none}
details.lt-info>summary b{color:var(--navy)}
details.lt-info>summary::after{content:'▾';margin-left:auto;color:var(--ink3)}
details.lt-info[open]>summary::after{content:'▴'}
.lt-info .bd{display:grid;grid-template-columns:1.4fr 1fr;gap:12px;padding:0 14px 14px}
.lt-info p{margin:0;font-size:13px;line-height:1.55;color:var(--ink2)}
.lt-cfg{border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:#fbfcfe}
.lt-cfg h4{margin:0 0 8px;font-size:13px;color:var(--navy)}
.lt-cfg .hs-fg{grid-template-columns:1fr 1fr}
.lt-flt{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0}
.lt-flt label{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--ink2);margin:0;font-weight:400}
.lt-exp{padding:12px;border-bottom:1px solid var(--line);background:#fffaf0}
.lt-exp[hidden]{display:none}
.lt-exp h4{margin:0 0 8px;font-size:13.5px;color:#8a6d00}
.lt-exp form{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin:0}
.lt-exp label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--ink2);font-weight:600;margin:0}
.lt-exp p{margin:8px 0 0;font-size:12px;color:#7a6200;line-height:1.5}
.lt-one{margin:12px;border:1px solid var(--pri-line);background:#f4f8ff;border-radius:10px;padding:12px}
.lt-one h4{margin:0 0 10px;font-size:14px;color:var(--navy)}
.lt-one form{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin:0}
.lt-one label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--ink2);font-weight:600;margin:0}
.lt-bulk{position:sticky;top:var(--adm-h,0px);z-index:3;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;padding:10px 12px;background:#f4f8ff;border-bottom:1px solid var(--pri-line)}
.lt-bulk label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--ink2);font-weight:600;margin:0}
.lt-bulk .cnt{align-self:center;font-size:13px;color:var(--navy);font-weight:600;min-width:96px}
.lt-bulk .cnt b{display:inline-block;min-width:24px;text-align:center;background:var(--pri);color:#fff;border-radius:6px;padding:1px 6px;margin-right:4px}
table.lt-tbl td{white-space:nowrap}
table.lt-tbl td.nm b{display:block}table.lt-tbl td.nm small{color:var(--ink3);font-size:12px}
table.lt-tbl tr.r-chua_khai td:first-child,table.lt-tbl tr.r-qua_han td:first-child,table.lt-tbl tr.r-checkout td:first-child{box-shadow:inset 3px 0 0 var(--red)}
table.lt-tbl tr.r-sap_het_han td:first-child{box-shadow:inset 3px 0 0 var(--orange)}
table.lt-tbl tr.sel{background:#e7f0fc!important}
table.lt-tbl th input,table.lt-tbl td input[type=checkbox]{width:16px;height:16px;accent-color:var(--pri)}
a.lt-sort{color:inherit;text-decoration:none}a.lt-sort:hover,a.lt-sort.on{color:var(--pri)}
@media (max-width:1100px){.lt-info .bd{grid-template-columns:1fr}}
</style>
<?php ob_start(); ?>
    <label class="hs-gs"><input id="ltQ" placeholder="Tìm nhanh trong bảng (họ tên, mã số, phòng, CCCD…)" autocomplete="off"><span class="ico"><svg class="i"><use href="#hi-search"/></svg></span></label>
<?php $khungGiua = ob_get_clean(); ob_start(); ?>
      <a class="hs-btn lt-cong" href="<?= h(KTX_CONG_BCA_URL) ?>" target="_blank" rel="noopener"><svg class="i"><use href="#hi-link"/></svg>Khai báo trên Cổng Bộ Công an ↗</a>
<?php $khungPhai = ob_get_clean(); ?>
<?php ktx_khung_mo('ktx_luu_tru_tam_tru.php', 'KHAI BÁO LƯU TRÚ / TẠM TRÚ', 'Nhắc lịch khai báo & Check-out với Bộ Công an', $khungGiua, $khungPhai); ?>
      <a id="top"></a>
      <?php if (!empty($_GET['saved'])): ?><div class="hs-msg ok"><span>✅ <?= !empty($_GET['so_luong']) ? 'Đã ghi nhận ' . ($tabChon === 'checkout' ? 'Check-out' : 'khai báo') . ' cho ' . (int)$_GET['so_luong'] . ' học viên.' : 'Đã ghi nhận.' ?></span></div><?php endif; ?>
      <?php if (!empty($_GET['saved_cf'])): ?><div class="hs-msg ok"><span>✅ Đã lưu cấu hình nhắc lịch.</span></div><?php endif; ?>

      <div class="hs-kpis lt-kl">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num"><?= $tongDangO ?></div><div class="lb">Đang ở cần theo dõi</div></div></div>
          <div class="sb"><div><b><?= $demTong['vn'] ?></b><span>Công dân VN (lưu trú)</span></div><div><b><?= $demTong['nn'] ?></b><span>Người nước ngoài (tạm trú)</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <a href="<?= h($hrefCanhBao('chua_khai_rieng')) ?>"><div class="tp"><div class="ic" style="background:var(--red-soft);color:var(--red)"><svg class="i"><use href="#hi-alert"/></svg></div>
            <div><div class="num" style="color:<?= $demTong['chua_khai'] ? 'var(--red)' : 'var(--green)' ?>"><?= $demTong['chua_khai'] ?></div><div class="lb">Chưa khai báo</div></div></div></a>
          <div class="sb"><div><span>Nhắc sau <?= $hanKhaiBaoNgay ?> ngày kể từ lúc vào ở</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <a href="<?= h($hrefCanhBao('qua_han_rieng')) ?>"><div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-clock"/></svg></div>
            <div><div class="num" style="color:<?= $demTong['qua_han'] ? 'var(--red)' : 'var(--green)' ?>"><?= $demTong['qua_han'] ?></div><div class="lb">Quá hạn khai lại</div></div></div></a>
          <div class="sb"><div><a href="<?= h($hrefCanhBao('sap_het_han_rieng')) ?>"><b style="color:var(--orange)"><?= $demTong['sap_het_han'] ?></b><span>Sắp hết hạn (≤ <?= $hanNhacHetHanNgay ?> ngày)</span></a></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <a href="?tab=checkout"><div class="tp"><div class="ic" style="background:#fdeee6;color:#d4561f"><svg class="i"><use href="#hi-door"/></svg></div>
            <div><div class="num" style="color:<?= $canBaoCheckoutList ? 'var(--red)' : 'var(--green)' ?>"><?= count($canBaoCheckoutList) ?></div><div class="lb">Đã rời chưa báo Check-out</div></div></div></a>
          <div class="sb"><div><span>Thanh lý HĐ / huỷ đăng ký chưa ghi nhận</span></div></div>
        </div>
      </div>

      <div class="lt-tabs" role="tablist">
        <a href="?tab=dang_o" class="<?= $tabChon === 'dang_o' ? 'on' : '' ?>" role="tab"<?= $tabChon === 'dang_o' ? ' aria-selected="true"' : '' ?>><svg class="i"><use href="#hi-home"/></svg>Đang ở — Khai báo lưu trú / tạm trú<?= ($demTong['chua_khai'] + $demTong['qua_han']) ? '<span class="n">' . ($demTong['chua_khai'] + $demTong['qua_han']) . '</span>' : '' ?></a>
        <a href="?tab=checkout" class="<?= $tabChon === 'checkout' ? 'on' : '' ?>" role="tab"<?= $tabChon === 'checkout' ? ' aria-selected="true"' : '' ?>><svg class="i"><use href="#hi-door"/></svg>Đã rời — Báo Check-out / Trả phòng<?= $canBaoCheckoutList ? '<span class="n">' . count($canBaoCheckoutList) . '</span>' : '' ?></a>
      </div>

      <details class="hs-card lt-info">
        <summary><svg class="i" style="color:var(--pri)"><use href="#hi-alert"/></svg><span><b>Quy trình &amp; cấu hình nhắc lịch</b> — trang này không tự khai báo hộ lên Cổng Bộ Công an</span></summary>
        <div class="bd">
          <p>Trang này <b>không</b> tự động khai báo lên Cổng dịch vụ công của Bộ Công an (không có API công khai, và hệ thống không được nhập tài khoản/mật khẩu thay bạn).<br>
            <b>Quy trình:</b> bấm <b>“Khai báo trên Cổng Bộ Công an ↗”</b> để tự đăng nhập và khai báo / báo Check-out trực tiếp trên cổng đó → quay lại đây bấm <b>“Ghi nhận”</b> để lưu ngày đã khai. Hệ thống sẽ nhắc khi sắp/quá hạn theo số ngày cấu hình bên cạnh (vui lòng đối chiếu quy định hiện hành để đặt đúng số ngày).</p>
          <form method="post" class="lt-cfg">
            <input type="hidden" name="action" value="luu_cau_hinh">
            <h4>Cấu hình nhắc lịch</h4>
            <div class="hs-fg">
              <label>Nhắc “chưa khai báo” sau <span class="hint">số ngày kể từ lúc vào ở</span><input type="number" name="han_khai_bao_ngay" value="<?= $hanKhaiBaoNgay ?>" min="0"></label>
              <label>Nhắc trước khi hết hạn <span class="hint">số ngày</span><input type="number" name="han_nhac_het_han_ngay" value="<?= $hanNhacHetHanNgay ?>" min="0"></label>
            </div>
            <div style="margin-top:8px;text-align:right"><button type="submit" class="hs-btn"><svg class="i"><use href="#hi-save"/></svg>Lưu cấu hình</button></div>
          </form>
        </div>
      </details>

<?php if ($tabChon === 'checkout'): ?>
      <!-- ===================== TAB DA ROI — CHECK-OUT ===================== -->
      <div class="hs-card">
        <div class="hs-tool"><h2>ĐÃ RỜI KTX — CHƯA BÁO CHECK-OUT</h2><span class="hs-sp"></span><span style="font-size:12.5px;color:var(--ink2)"><span id="ltDem"><?= count($canBaoCheckoutList) ?></span> học viên</span></div>
        <p style="margin:0;padding:8px 12px;font-size:12.5px;color:var(--ink2);border-bottom:1px solid var(--line2)">Học viên đã <b>Thanh lý hợp đồng</b> / <b>Huỷ đăng ký</b> nhưng chưa ghi nhận đã báo Check-out/Trả phòng trên Cổng Bộ Công an. Sau khi báo thật trên cổng, bấm <b>Ghi nhận</b> để xoá khỏi danh sách nhắc.</p>

        <?php if ($ghiNhanCho): ?>
        <div class="lt-one">
          <h4>Ghi nhận đã báo Check-out cho: <?= h($ghiNhanCho['ho_ten']) ?></h4>
          <form method="post">
            <input type="hidden" name="action" value="ghi_nhan">
            <input type="hidden" name="loai_ghi_nhan" value="checkout">
            <input type="hidden" name="hoc_vien_id" value="<?= (int)$ghiNhanCho['id'] ?>">
            <input type="hidden" name="redirect_qs" value="tab=checkout">
            <label>Ngày đã báo Check-out<input class="hs-f" type="date" name="ngay_khai_bao" value="<?= date('Y-m-d') ?>" required></label>
            <label style="flex:1;min-width:200px">Ghi chú<input class="hs-f" type="text" name="ghi_chu"></label>
            <button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg>Lưu ghi nhận</button>
            <a href="?tab=checkout" class="hs-btn">Huỷ</a>
          </form>
        </div>
        <?php endif; ?>

        <?php if ($canBaoCheckoutList): ?>
        <form method="post" id="ltForm">
          <input type="hidden" name="action" value="ghi_nhan_hang_loat_checkout">
          <div class="lt-bulk">
            <span class="cnt"><b id="ltChon">0</b>đã chọn</span>
            <label>Ngày đã báo Check-out<input class="hs-f" type="date" name="ngay_khai_bao" value="<?= date('Y-m-d') ?>" required></label>
            <label style="flex:1;min-width:180px">Ghi chú (áp dụng chung)<input class="hs-f" type="text" name="ghi_chu"></label>
            <button type="submit" class="hs-btn pri" data-xn="Xác nhận ghi nhận đã báo Check-out cho các học viên đã chọn?"><svg class="i"><use href="#hi-check"/></svg>Ghi nhận Check-out cho mục đã chọn</button>
          </div>
          <div class="hs-tw">
            <table class="hs-grid lt-tbl">
              <thead><tr><th class="c" style="width:40px"><input type="checkbox" id="ltAll" aria-label="Chọn tất cả"></th><th>Họ tên / Mã số</th><th>Quốc tịch</th><th>Ngày thanh lý HĐ</th><th>Hình thức rời</th><th>Trạng thái</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($canBaoCheckoutList as $hv): ?>
              <tr class="r-checkout" data-q="<?= h(mb_strtolower($hv['ho_ten'] . ' ' . $hv['ma_so'] . ' ' . $hv['cccd'], 'UTF-8')) ?>">
                <td class="c"><input type="checkbox" class="lt-chon" name="chon[]" value="<?= (int)$hv['id'] ?>" aria-label="Chọn <?= h($hv['ho_ten']) ?>"></td>
                <td class="nm"><b><?= h($hv['ho_ten']) ?></b><small><?= h((string)$hv['ma_so']) ?></small></td>
                <td><?= $hv['quoc_tich'] ? h($hv['quoc_tich']) : '<span class="hs-mut">(chưa ghi)</span>' ?></td>
                <td><?= $hv['ngay_thanh_ly_hd'] ? date('d/m/Y', strtotime($hv['ngay_thanh_ly_hd'])) : '<span class="hs-mut">—</span>' ?></td>
                <td><?= $hv['ngay_thanh_ly_hd'] ? 'Thanh lý HĐ' : 'Huỷ đăng ký' ?></td>
                <td><span class="hs-pill red">Chưa báo Check-out</span></td>
                <td><a class="hs-btn sm" href="?tab=checkout&amp;ghi_nhan=<?= (int)$hv['id'] ?>#top"><svg class="i"><use href="#hi-edit"/></svg>Ghi nhận</a></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </form>
        <?php else: ?>
        <div class="hs-empty" style="color:var(--green);font-weight:600">✓ Không còn học viên nào cần báo Check-out.</div>
        <?php endif; ?>
      </div>

<?php else: ?>
      <!-- ===================== TAB DANG O — KHAI BAO ===================== -->
      <div class="hs-card">
        <div class="hs-tool">
          <h2>ĐANG Ở — KHAI BÁO LƯU TRÚ / TẠM TRÚ</h2>
          <form method="get" class="lt-flt">
            <input type="hidden" name="tab" value="dang_o">
            <label>Dãy <select class="hs-f" name="day" onchange="this.form.submit()"><option value="0">Tất cả</option><?php foreach ($dayList as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (int)$d['id'] === $dayChon ? 'selected' : '' ?>><?= h($d['ten']) ?></option><?php endforeach; ?></select></label>
            <label>Loại <select class="hs-f" name="loai" onchange="this.form.submit()">
              <option value="tat_ca" <?= $loaiChon === 'tat_ca' ? 'selected' : '' ?>>Tất cả</option>
              <option value="vn" <?= $loaiChon === 'vn' ? 'selected' : '' ?>>Công dân Việt Nam</option>
              <option value="nn" <?= $loaiChon === 'nn' ? 'selected' : '' ?>>Người nước ngoài</option></select></label>
            <label>Trạng thái <select class="hs-f" name="trang_thai" onchange="this.form.submit()">
              <?php foreach (['tat_ca' => 'Tất cả', 'chua_khai_rieng' => 'Chưa khai báo', 'da_khai_rieng' => 'Đã khai báo', 'can_nhac' => 'Cần nhắc (chưa/sắp/quá hạn)', 'qua_han_rieng' => 'Quá hạn khai lại', 'sap_het_han_rieng' => 'Sắp hết hạn', 'qua_han' => 'Chưa khai báo / quá hạn', 'da_xong' => 'Đã khai báo, còn hiệu lực'] as $v => $t): ?>
              <option value="<?= $v ?>" <?= $trangThaiChon === $v ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?></select></label>
            <?php if ($sortKey !== ''): ?><input type="hidden" name="sort" value="<?= h($sortKey) ?>"><input type="hidden" name="dir" value="<?= h($sortDir) ?>"><?php endif; ?>
            <noscript><button class="hs-btn pri">Lọc</button></noscript>
            <?php if ($coLoc): ?><a class="hs-btn sm" href="?tab=dang_o">Xoá lọc</a><?php endif; ?>
          </form>
          <span class="hs-sp"></span>
          <button type="button" class="hs-btn o" onclick="var e=document.getElementById('ltExp');e.hidden=!e.hidden"><svg class="i"><use href="#hi-down"/></svg>Xuất theo ngày khai báo ▾</button>
        </div>

        <div class="lt-exp" id="ltExp" hidden>
          <h4>Xuất danh sách khai báo — biểu mẫu hành chính chuẩn (nộp/đối chiếu Cổng Dịch vụ công / Công an)</h4>
          <form method="get" action="ktx_export_khai_bao_bca.php" target="_blank">
            <label>Từ ngày (ngày khách đến)<input class="hs-f" type="date" name="tu_ngay" value="<?= date('Y-m-d', strtotime('-1 day')) ?>"></label>
            <label>Đến ngày<input class="hs-f" type="date" name="den_ngay" value="<?= date('Y-m-d') ?>"></label>
            <label>Đối tượng cư trú
              <select class="hs-f" name="loai" onchange="location.href='?tab=dang_o&loai=' + (this.value === 'tam_tru_nn' ? 'nn' : 'vn') + '&day=<?= $dayChon ?>&trang_thai=<?= h(rawurlencode($trangThaiChon)) ?>'">
                <option value="luu_tru_vn" <?= $loaiChon !== 'nn' ? 'selected' : '' ?>>Khách Việt Nam</option>
                <option value="tam_tru_nn" <?= $loaiChon === 'nn' ? 'selected' : '' ?>>Người nước ngoài</option>
              </select></label>
            <label>Dãy / Toà nhà
              <select class="hs-f" name="day_id"><option value="0">Tất cả</option><?php foreach ($dayList as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (int)$d['id'] === $dayChon ? 'selected' : '' ?>><?= h($d['ten']) ?></option><?php endforeach; ?></select></label>
            <button type="submit" name="mau" value="dvc" class="hs-btn pri"><svg class="i"><use href="#hi-down"/></svg>Khai báo tạm trú BCA</button>
            <button type="submit" name="mau" value="noi_bo" class="hs-btn"><svg class="i"><use href="#hi-down"/></svg>Danh sách nội bộ</button>
          </form>
          <p>Lấy theo <b>ngày khai báo</b> đã ghi nhận (cột “Khai báo gần nhất”), không phải ngày đăng ký KTX. “Khai báo tạm trú BCA” xuất đúng khổ hành chính (Quốc hiệu, Tiêu ngữ, 13 cột chuẩn); “Danh sách nội bộ” là bản đơn giản để theo dõi riêng. Đổi “Đối tượng cư trú” sẽ lọc luôn bảng bên dưới.<br>
            Muốn xuất những người <b>chưa từng khai báo</b>? Tick chọn trong bảng rồi dùng nút <b>Xuất BCA / Xuất nội bộ</b> trên thanh chọn.</p>
        </div>

        <?php if ($ghiNhanCho): ?>
        <div class="lt-one">
          <h4>Ghi nhận khai báo cho: <?= h($ghiNhanCho['ho_ten']) ?> <span style="font-weight:400;color:var(--ink2)">(<?= h(KTX_LOAI_KHAI_BAO_LABEL[ktx_loai_khai_bao($ghiNhanCho['quoc_tich'])]) ?>)</span></h4>
          <form method="post">
            <input type="hidden" name="action" value="ghi_nhan">
            <input type="hidden" name="hoc_vien_id" value="<?= (int)$ghiNhanCho['id'] ?>">
            <input type="hidden" name="redirect_qs" value="<?= h(http_build_query(['loai' => $loaiChon, 'trang_thai' => $trangThaiChon, 'day' => $dayChon])) ?>">
            <label>Ngày đã khai báo<input class="hs-f" type="date" name="ngay_khai_bao" value="<?= date('Y-m-d') ?>" required></label>
            <label>Ngày hết hạn / cần khai lại (nếu có)<input class="hs-f" type="date" name="ngay_het_han"></label>
            <label style="flex:1;min-width:200px">Ghi chú<input class="hs-f" type="text" name="ghi_chu"></label>
            <button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg>Lưu ghi nhận</button>
            <a href="ktx_luu_tru_tam_tru.php" class="hs-btn">Huỷ</a>
          </form>
        </div>
        <?php endif; ?>

        <?php if ($hocVienList): ?>
        <form method="post" id="ltForm">
          <input type="hidden" name="action" value="ghi_nhan_hang_loat">
          <input type="hidden" name="redirect_qs" value="<?= h(http_build_query(['tab' => 'dang_o', 'loai' => $loaiChon, 'trang_thai' => $trangThaiChon, 'day' => $dayChon])) ?>">
          <div class="lt-bulk">
            <span class="cnt"><b id="ltChon">0</b>đã chọn</span>
            <label>Ngày khai báo<input class="hs-f" type="date" name="ngay_khai_bao" value="<?= date('Y-m-d') ?>" required></label>
            <label>Ngày hết hạn / khai lại<input class="hs-f" type="date" name="ngay_het_han"></label>
            <label style="flex:1;min-width:160px">Ghi chú (áp dụng chung)<input class="hs-f" type="text" name="ghi_chu"></label>
            <button type="submit" class="hs-btn pri" data-xn="Xác nhận ghi nhận khai báo cho các học viên đã chọn?"><svg class="i"><use href="#hi-check"/></svg>Ghi nhận cho mục đã chọn</button>
            <button type="button" class="hs-btn" onclick="ktxXuatMucDaChon('dvc')"><svg class="i"><use href="#hi-down"/></svg>Xuất BCA</button>
            <button type="button" class="hs-btn" onclick="ktxXuatMucDaChon('noi_bo')"><svg class="i"><use href="#hi-down"/></svg>Xuất nội bộ</button>
          </div>
          <div class="hs-tw">
            <table class="hs-grid lt-tbl">
              <thead><tr>
                <th class="c" style="width:40px"><input type="checkbox" id="ltAll" aria-label="Chọn tất cả"></th>
                <th><?= ktx_ltt_link_tieu_de('ho_ten', 'Họ tên', $sortKey, $sortDir) ?></th>
                <th><?= ktx_ltt_link_tieu_de('phong', 'Phòng', $sortKey, $sortDir) ?></th>
                <th><?= ktx_ltt_link_tieu_de('quoc_tich', 'Quốc tịch', $sortKey, $sortDir) ?></th>
                <th><?= ktx_ltt_link_tieu_de('loai', 'Loại', $sortKey, $sortDir) ?></th>
                <th><?= ktx_ltt_link_tieu_de('ngay_vao_o', 'Ngày vào ở', $sortKey, $sortDir) ?></th>
                <th><?= ktx_ltt_link_tieu_de('cccd', 'CCCD/Hộ chiếu', $sortKey, $sortDir) ?></th>
                <th><?= ktx_ltt_link_tieu_de('khai_bao', 'Khai báo gần nhất', $sortKey, $sortDir) ?></th>
                <th><?= ktx_ltt_link_tieu_de('han_khai_lai', 'Hạn / khai lại', $sortKey, $sortDir) ?></th>
                <th><?= ktx_ltt_link_tieu_de('trang_thai', 'Trạng thái', $sortKey, $sortDir) ?></th>
                <th></th>
              </tr></thead>
              <tbody>
              <?php foreach ($hocVienList as $hv): $kb = $hv['_kb']; $tt = $TT_PILL[$hv['_trang_thai']]; ?>
              <tr class="r-<?= h($hv['_trang_thai']) ?>" data-q="<?= h(mb_strtolower($hv['ho_ten'] . ' ' . $hv['ma_so'] . ' ' . $hv['so_phong'] . ' ' . $hv['cccd'], 'UTF-8')) ?>">
                <td class="c"><input type="checkbox" class="lt-chon" name="chon[]" value="<?= (int)$hv['id'] ?>" aria-label="Chọn <?= h($hv['ho_ten']) ?>"></td>
                <td class="nm"><b><?= h($hv['ho_ten']) ?></b><small><?= h((string)$hv['ma_so']) ?></small></td>
                <td><?= $hv['so_phong'] ? h($hv['ten_day'] . ' – ' . $hv['so_phong']) : '<span class="hs-no">Chưa xếp phòng</span>' ?></td>
                <td><?= $hv['quoc_tich'] ? h($hv['quoc_tich']) : '<span class="hs-mut">(chưa ghi)</span>' ?></td>
                <td><span class="hs-pill <?= $hv['_loai'] === 'luu_tru_vn' ? 'blue' : 'orange' ?>"><?= $hv['_loai'] === 'luu_tru_vn' ? 'Lưu trú (VN)' : 'Tạm trú (NN)' ?></span></td>
                <td><?= $hv['ngay_dang_ky'] ? date('d/m/Y', strtotime($hv['ngay_dang_ky'])) : '<span class="hs-mut">—</span>' ?></td>
                <td><?= $hv['cccd'] ? h($hv['cccd']) : '<span class="hs-no">Thiếu</span>' ?></td>
                <td><?= $kb ? date('d/m/Y', strtotime($kb['ngay_khai_bao'])) : '<span class="hs-mut">—</span>' ?></td>
                <td><?= ($kb && $kb['ngay_het_han']) ? date('d/m/Y', strtotime($kb['ngay_het_han'])) : '<span class="hs-mut">—</span>' ?></td>
                <td><span class="hs-pill <?= $tt[0] ?>"><?= h($tt[1]) ?></span></td>
                <td><a class="hs-btn sm" href="?tab=dang_o&amp;ghi_nhan=<?= (int)$hv['id'] ?>&amp;loai=<?= h($loaiChon) ?>&amp;trang_thai=<?= h($trangThaiChon) ?>&amp;day=<?= $dayChon ?>#top"><svg class="i"><use href="#hi-edit"/></svg>Ghi nhận</a></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </form>
        <?php else: ?>
        <div class="hs-empty">Không có học viên nào khớp bộ lọc.</div>
        <?php endif; ?>
        <div style="padding:8px 12px;font-size:12.5px;color:var(--ink2)"><span id="ltDem"><?= count($hocVienList) ?></span> / <?= $tongDangO ?> học viên đang ở<?= $coLoc ? ' (theo bộ lọc)' : '' ?></div>
      </div>

      <!-- Xuat Excel DUNG cac dong da tick (2026-09-30) — dung cho nguoi CHUA TUNG khai bao (khong loc
           duoc theo khoang ngay nhu form ben tren vi ho chua co ban ghi nao trong ktx_khai_bao_luu_tru).
           Doi tuong (VN/NN) lay theo dung bo loc "loai" dang xem — neu dang "Tat ca", mac dinh xuat kieu
           VN (nguoi NN se tu dong bi loai khoi file do khac schema, xem ktx_export_khai_bao_bca.php). -->
      <form method="post" action="ktx_export_khai_bao_bca.php" target="_blank" id="formXuatMucDaChon" style="display:none">
        <input type="hidden" name="mode" value="chon">
        <input type="hidden" name="mau" id="xuatMucDaChonMau" value="dvc">
        <input type="hidden" name="loai" value="<?= $loaiChon === 'nn' ? 'tam_tru_nn' : 'luu_tru_vn' ?>">
        <input type="hidden" name="day_id" value="<?= $dayChon ?>">
        <input type="hidden" name="hoc_vien_ids" id="xuatMucDaChonIds" value="">
      </form>
<?php endif; ?>
<?php ktx_khung_dong(); ?>
<script>
function ktxXuatMucDaChon(mau) {
  var ids = Array.from(document.querySelectorAll('.lt-chon:checked')).map(function (cb) { return cb.value; });
  if (!ids.length) { alert('Vui lòng tick chọn ít nhất 1 học viên ở bảng danh sách.'); return; }
  document.getElementById('xuatMucDaChonMau').value = mau;
  document.getElementById('xuatMucDaChonIds').value = ids.join(',');
  document.getElementById('formXuatMucDaChon').submit();
}
(function () {
  var all = document.getElementById('ltAll'), dem = document.getElementById('ltChon');
  function hien() { return Array.from(document.querySelectorAll('.lt-chon')).filter(function (c) { return !c.closest('tr').hidden; }); }
  function capNhat() {
    var n = document.querySelectorAll('.lt-chon:checked').length;
    if (dem) dem.textContent = n;
    document.querySelectorAll('.lt-chon').forEach(function (c) { c.closest('tr').classList.toggle('sel', c.checked); });
    if (all) { var h = hien(), k = h.filter(function (c) { return c.checked; }).length; all.checked = h.length > 0 && k === h.length; all.indeterminate = k > 0 && k < h.length; }
  }
  if (all) all.addEventListener('change', function () { hien().forEach(function (c) { c.checked = all.checked; }); capNhat(); });
  document.addEventListener('change', function (e) { if (e.target.classList && e.target.classList.contains('lt-chon')) capNhat(); });
  var form = document.getElementById('ltForm');
  if (form) form.addEventListener('submit', function (e) {
    var btn = form.querySelector('[data-xn]');
    if (!document.querySelectorAll('.lt-chon:checked').length) { e.preventDefault(); alert('Vui lòng tick chọn ít nhất 1 học viên.'); return; }
    if (btn && !confirm(btn.dataset.xn)) e.preventDefault();
  });
  // Tim nhanh trong bang dang xem (khong tai lai trang)
  var q = document.getElementById('ltQ'), lbl = document.getElementById('ltDem');
  if (q) q.addEventListener('input', function () {
    var t = q.value.trim().toLowerCase(), n = 0;
    document.querySelectorAll('tr[data-q]').forEach(function (tr) { var ok = !t || tr.dataset.q.indexOf(t) >= 0; tr.hidden = !ok; if (ok) n++; });
    if (lbl) lbl.textContent = n;
    capNhat();
  });
  capNhat();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
