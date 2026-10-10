<?php
// Danh sach hoc vien/can bo o noi tru KTX — them/sua/xep phong, tim kiem, loc theo day/phong/
// trang thai. Du lieu ban dau duoc nhap that tu file Excel cua Trung tam (xem ktx_import_data.sql).
//
// GIAO DIEN MOI (2026-10-10): bo cuc kieu phan mem quan tri — dai tieu de, menu chuc nang KTX ben
// trai, 4 the so lieu/canh bao, bang danh sach co thanh cong cu + bo loc, khung "Ho so noi tru" chi
// tiet dang tab ben duoi (chon 1 dong = ?id=). Toan bo xu ly POST/GET (them, sua, xoa, thanh ly HD,
// huy dang ky) va ten tham so loc (q, day_id, trang_thai, page) GIU NGUYEN nhu ban truoc.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'update'], true)) {
    $hoTen = trim($_POST['ho_ten'] ?? '');
    $phongId = !empty($_POST['phong_id']) ? (int)$_POST['phong_id'] : null;
    $data = [
        trim($_POST['ma_so'] ?? '') ?: null,
        trim($_POST['loai_doi_tuong'] ?? '') ?: 'Sinh viên CTUMP',
        $hoTen,
        in_array($_POST['gioi_tinh'] ?? '', ['Nam', 'Nữ', 'Khác'], true) ? $_POST['gioi_tinh'] : 'Khác',
        $_POST['ngay_sinh'] ?: null,
        trim($_POST['noi_sinh'] ?? '') ?: null,
        trim($_POST['cccd'] ?? '') ?: null,
        in_array($_POST['loai_giay_to'] ?? '', ['Thẻ CCCD', 'Thẻ CMND', 'Hộ chiếu'], true) ? $_POST['loai_giay_to'] : 'Thẻ CCCD',
        trim($_POST['lop_phong_ban'] ?? '') ?: null,
        trim($_POST['noi_cong_tac'] ?? '') ?: null,
        trim($_POST['khoa_hoc'] ?? '') ?: null,
        $phongId,
        trim($_POST['giuong'] ?? '') ?: null,
        trim($_POST['dan_toc'] ?? '') ?: null,
        trim($_POST['ton_giao'] ?? '') ?: null,
        trim($_POST['quoc_tich'] ?? '') ?: null,
        trim($_POST['doi_tuong_uu_tien'] ?? '') ?: null,
        trim($_POST['lien_he_khan_cap'] ?? '') ?: null,
        trim($_POST['dia_chi'] ?? '') ?: null,
        trim($_POST['nguyen_quan'] ?? '') ?: null,
        trim($_POST['so_dt'] ?? '') ?: null,
        trim($_POST['nhan_su_dang_ky_ma'] ?? '') ?: null,
        trim($_POST['nhan_su_dang_ky_ten'] ?? '') ?: null,
        $_POST['ngay_dang_ky'] ?: null,
        $_POST['ngay_ky_hd'] ?: null,
        $_POST['ngay_thanh_ly_hd'] ?: null,
        in_array($_POST['trang_thai'] ?? '', ['dang_o', 'da_roi'], true) ? $_POST['trang_thai'] : 'dang_o',
        trim($_POST['ghi_chu'] ?? '') ?: null,
        $_POST['han_nop_dien_nuoc'] ?? '' ?: null,
        $_POST['han_nop_phi_noi_tru'] ?? '' ?: null,
        trim($_POST['ghi_chu_gia_han'] ?? '') ?: null,
        ($emailSv = mb_strtolower(trim((string)($_POST['email'] ?? '')))) !== '' && filter_var($emailSv, FILTER_VALIDATE_EMAIL) ? mb_substr($emailSv, 0, 150) : null,
    ];
    if ($hoTen !== '') {
        if ($_POST['action'] === 'add') {
            $pdo->prepare('INSERT INTO ktx_hoc_vien
                (ma_so, loai_doi_tuong, ho_ten, gioi_tinh, ngay_sinh, noi_sinh, cccd, loai_giay_to, lop_phong_ban, noi_cong_tac, khoa_hoc, phong_id, giuong,
                 dan_toc, ton_giao, quoc_tich, doi_tuong_uu_tien, lien_he_khan_cap, dia_chi, nguyen_quan, so_dt, nhan_su_dang_ky_ma, nhan_su_dang_ky_ten,
                 ngay_dang_ky, ngay_ky_hd, ngay_thanh_ly_hd, trang_thai, ghi_chu, han_nop_dien_nuoc, han_nop_phi_noi_tru, ghi_chu_gia_han, email)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute($data);
            // (2026-10-10) mo luon ho so vua them trong khung chi tiet
            header('Location: ktx_hoc_vien.php?saved=1&id=' . (int)$pdo->lastInsertId() . '#ho-so'); exit;
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE ktx_hoc_vien SET
                ma_so=?, loai_doi_tuong=?, ho_ten=?, gioi_tinh=?, ngay_sinh=?, noi_sinh=?, cccd=?, loai_giay_to=?, lop_phong_ban=?, noi_cong_tac=?, khoa_hoc=?, phong_id=?, giuong=?,
                dan_toc=?, ton_giao=?, quoc_tich=?, doi_tuong_uu_tien=?, lien_he_khan_cap=?, dia_chi=?, nguyen_quan=?, so_dt=?, nhan_su_dang_ky_ma=?, nhan_su_dang_ky_ten=?,
                ngay_dang_ky=?, ngay_ky_hd=?, ngay_thanh_ly_hd=?, trang_thai=?, ghi_chu=?, han_nop_dien_nuoc=?, han_nop_phi_noi_tru=?, ghi_chu_gia_han=?, email=? WHERE id=?')
                ->execute(array_merge($data, [$id]));
            header('Location: ktx_hoc_vien.php?saved=1&edit=' . $id); exit;
        }
    }
    header('Location: ktx_hoc_vien.php'); exit;
}
if (($_GET['action'] ?? '') === 'xoa' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM ktx_hoc_vien WHERE id = ?')->execute([(int)$_GET['id']]);
    header('Location: ktx_hoc_vien.php'); exit;
}
// Thanh ly hop dong: hoc vien THUC SU da tung o, nay ket thuc dung hop dong — ghi nhan Ngay thanh
// ly HD, chuyen trang_thai sang "da_roi", VA GIAI PHONG phong/giuong dang xu de phong hien lai
// con cho trong cho nguoi khac (ho so hoc vien VAN GIU NGUYEN, khong xoa) (2026-09-15).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'thanh_ly_hd') {
    $id = (int)($_POST['id'] ?? 0);
    $ngayThanhLy = trim($_POST['ngay_thanh_ly_hd'] ?? '') ?: date('Y-m-d');
    if ($id) {
        $pdo->prepare('UPDATE ktx_hoc_vien SET trang_thai = ?, ngay_thanh_ly_hd = ?, phong_id = NULL, giuong = NULL WHERE id = ?')
            ->execute(['da_roi', $ngayThanhLy, $id]);
    }
    // Nhac ban quan ly bao Check-out/Tra phong tren Cong Bo Cong an cho hoc vien vua roi (2026-09-17).
    header('Location: ktx_hoc_vien.php?edit=' . $id . '&saved=1&nhac_checkout=1'); exit;
}
// Huy dang ky: dang ky/xep phong NHAM hoac SV doi y khong o nua — go khoi phong/giuong va chuyen
// trang_thai sang "da_roi" NHUNG KHONG ghi Ngay thanh ly HD (vi chua thuc su tung o), ho so van
// duoc giu lai (khong xoa) de con tra cuu lich su neu can (2026-09-15).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'huy_dang_ky') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare('UPDATE ktx_hoc_vien SET trang_thai = ?, phong_id = NULL, giuong = NULL WHERE id = ?')
            ->execute(['da_roi', $id]);
    }
    header('Location: ktx_hoc_vien.php?edit=' . $id . '&saved=1&nhac_checkout=1'); exit;
}

$editRow = null;
if (!empty($_GET['edit']) && $_GET['edit'] !== 'new') {
    $stmt = $pdo->prepare('SELECT * FROM ktx_hoc_vien WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editRow = $stmt->fetch();
}
$showForm = $editRow || (($_GET['edit'] ?? '') === 'new');

// ---- Ky dang xem (de tinh "phi noi tru thang nay") ----
$thangXem = (int)($_GET['thang'] ?? date('n')); if ($thangXem < 1 || $thangXem > 12) { $thangXem = (int)date('n'); }
$namXem = (int)($_GET['nam'] ?? date('Y')); if ($namXem < 2000 || $namXem > 2100) { $namXem = (int)date('Y'); }

// ---- Bo loc (giu ten tham so cu: q, day_id, trang_thai, page) ----
$q = trim($_GET['q'] ?? '');
$dayId = (int)($_GET['day_id'] ?? 0);
$trangThai = $_GET['trang_thai'] ?? 'dang_o';
$gioiTinhLoc = in_array($_GET['gioi_tinh'] ?? '', ['Nam', 'Nữ', 'Khác'], true) ? $_GET['gioi_tinh'] : '';
$LOC_NHANH = [
    'chua_phi' => 'Chưa đóng phí nội trú T' . $thangXem . '/' . $namXem,
    'chua_xep' => 'Đang ở nhưng chưa xếp phòng',
    'chua_hd' => 'Chưa có ngày ký hợp đồng',
    'thieu_cccd' => 'Thiếu số CCCD/giấy tờ',
    'thieu_lh' => 'Thiếu liên hệ khẩn cấp',
    'thieu_email' => 'Thiếu email',
    'gia_han' => 'Đang được gia hạn nộp phí',
    'chua_luu_tru' => 'Chưa khai báo lưu trú/tạm trú',
    'chua_checkout' => 'Đã rời nhưng chưa báo Check-out',
];
$locNhanh = isset($LOC_NHANH[$_GET['nhanh'] ?? '']) ? $_GET['nhanh'] : '';
$sqlChuaPhi = 'NOT EXISTS (SELECT 1 FROM ktx_phi_noi_tru f WHERE f.hoc_vien_id = hv.id AND f.thang = ? AND f.nam = ? AND f.da_nop = 1)';
$DIEU_KIEN_NHANH = [
    'chua_phi' => "hv.trang_thai = 'dang_o' AND $sqlChuaPhi",
    'chua_xep' => "hv.trang_thai = 'dang_o' AND hv.phong_id IS NULL",
    'chua_hd' => "hv.trang_thai = 'dang_o' AND hv.ngay_ky_hd IS NULL",
    'thieu_cccd' => "hv.trang_thai = 'dang_o' AND (hv.cccd IS NULL OR hv.cccd = '')",
    'thieu_lh' => "hv.trang_thai = 'dang_o' AND (hv.lien_he_khan_cap IS NULL OR hv.lien_he_khan_cap = '')",
    'thieu_email' => "hv.trang_thai = 'dang_o' AND (hv.email IS NULL OR hv.email = '')",
    'gia_han' => "hv.trang_thai = 'dang_o' AND (hv.han_nop_dien_nuoc IS NOT NULL OR hv.han_nop_phi_noi_tru IS NOT NULL)",
    // 2 nhom lay tu so khai bao luu tru (ktx_luu_tru_tam_tru.php, bang ktx_khai_bao_luu_tru)
    'chua_luu_tru' => "hv.trang_thai = 'dang_o' AND NOT EXISTS (SELECT 1 FROM ktx_khai_bao_luu_tru k WHERE k.hoc_vien_id = hv.id AND k.loai != 'checkout')",
    'chua_checkout' => "hv.trang_thai = 'da_roi' AND NOT EXISTS (SELECT 1 FROM ktx_khai_bao_luu_tru k WHERE k.hoc_vien_id = hv.id AND k.loai = 'checkout')",
];

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(hv.ho_ten LIKE ? OR hv.ma_so LIKE ? OR hv.cccd LIKE ? OR hv.so_dt LIKE ? OR p.so_phong LIKE ? OR hv.giuong LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($dayId) { $where[] = 'p.day_id = ?'; $params[] = $dayId; }
if ($trangThai !== '') { $where[] = 'hv.trang_thai = ?'; $params[] = $trangThai; }
if ($gioiTinhLoc !== '') { $where[] = 'hv.gioi_tinh = ?'; $params[] = $gioiTinhLoc; }
if ($locNhanh !== '') { $where[] = $DIEU_KIEN_NHANH[$locNhanh]; if ($locNhanh === 'chua_phi') { array_push($params, $thangXem, $namXem); } }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$perPage = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ktx_hoc_vien hv LEFT JOIN ktx_phong p ON p.id = hv.phong_id $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$sql = "SELECT hv.*, p.so_phong, d.ten AS ten_day
        FROM ktx_hoc_vien hv
        LEFT JOIN ktx_phong p ON p.id = hv.phong_id
        LEFT JOIN ktx_day d ON d.id = p.day_id
        $whereSql
        ORDER BY d.ten, p.so_phong, hv.giuong
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

$dayList = $pdo->query('SELECT * FROM ktx_day ORDER BY ten')->fetchAll();
$phongList = $pdo->query("SELECT p.*, d.ten AS ten_day, d.gioi_tinh AS day_gioi_tinh,
        (SELECT COUNT(*) FROM ktx_hoc_vien hv2 WHERE hv2.phong_id = p.id AND hv2.trang_thai = 'dang_o') AS dang_o
    FROM ktx_phong p JOIN ktx_day d ON d.id = p.day_id ORDER BY d.ten, p.so_phong")->fetchAll();

// ---- So lieu cho 4 the tong quan ----
$demDangO = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien WHERE trang_thai = 'dang_o'")->fetchColumn();
$demDaRoi = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien WHERE trang_thai = 'da_roi'")->fetchColumn();
$demDaXep = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien WHERE trang_thai = 'dang_o' AND phong_id IS NOT NULL")->fetchColumn();
$sucChua = 0; $soPhongSd = 0;
foreach ($phongList as $p) { if (($p['tinh_trang'] ?? '') !== 'ngung_su_dung') { $sucChua += (int)$p['suc_chua']; $soPhongSd++; } }
$demRoiThangNay = $pdo->prepare("SELECT COUNT(*) FROM ktx_hoc_vien WHERE trang_thai = 'da_roi' AND ngay_thanh_ly_hd >= ? AND ngay_thanh_ly_hd < ?");
$dauThang = sprintf('%04d-%02d-01', $namXem, $thangXem);
$dauThangSau = $thangXem === 12 ? sprintf('%04d-01-01', $namXem + 1) : sprintf('%04d-%02d-01', $namXem, $thangXem + 1);
$demRoiThangNay->execute([$dauThang, $dauThangSau]); $demRoiThangNay = (int)$demRoiThangNay->fetchColumn();
$demVaoThangNay = $pdo->prepare("SELECT COUNT(*) FROM ktx_hoc_vien WHERE ngay_dang_ky >= ? AND ngay_dang_ky < ?");
$demVaoThangNay->execute([$dauThang, $dauThangSau]); $demVaoThangNay = (int)$demVaoThangNay->fetchColumn();
$stPhi = $pdo->prepare('SELECT COUNT(*) AS n, COALESCE(SUM(so_tien),0) AS t FROM ktx_phi_noi_tru WHERE thang = ? AND nam = ? AND da_nop = 1');
$stPhi->execute([$thangXem, $namXem]); $phiThang = $stPhi->fetch();
$demCanhBao = [];
foreach ($DIEU_KIEN_NHANH as $k => $dk) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM ktx_hoc_vien hv WHERE $dk");
    $st->execute($k === 'chua_phi' ? [$thangXem, $namXem] : []);
    $demCanhBao[$k] = (int)$st->fetchColumn();
}
$tongCanhBao = $demCanhBao['chua_phi'] + $demCanhBao['chua_xep'] + $demCanhBao['chua_luu_tru'] + $demCanhBao['chua_checkout'] + $demCanhBao['chua_hd'] + $demCanhBao['thieu_cccd'];

// Hoc vien da dong phi thang dang xem — de gan co canh bao tren tung dong
$stDaNop = $pdo->prepare('SELECT hoc_vien_id FROM ktx_phi_noi_tru WHERE thang = ? AND nam = ? AND da_nop = 1');
$stDaNop->execute([$thangXem, $namXem]);
$daNop = array_fill_keys(array_map('intval', $stDaNop->fetchAll(PDO::FETCH_COLUMN)), true);
$daKhaiLuuTru = array_fill_keys(array_map('intval', $pdo->query("SELECT DISTINCT hoc_vien_id FROM ktx_khai_bao_luu_tru WHERE loai != 'checkout'")->fetchAll(PDO::FETCH_COLUMN)), true);
$daCheckout = array_fill_keys(array_map('intval', $pdo->query("SELECT DISTINCT hoc_vien_id FROM ktx_khai_bao_luu_tru WHERE loai = 'checkout'")->fetchAll(PDO::FETCH_COLUMN)), true);
$canhBaoCua = function (array $r) use ($daNop, $daKhaiLuuTru, $daCheckout, $thangXem): array {
    if (($r['trang_thai'] ?? '') === 'da_roi') { return empty($daCheckout[(int)$r['id']]) ? ['Chưa báo Check-out/Trả phòng'] : []; }
    if (($r['trang_thai'] ?? '') !== 'dang_o') return [];
    $f = [];
    if (empty($daNop[(int)$r['id']])) $f[] = 'Chưa đóng phí nội trú T' . $thangXem;
    if (empty($r['phong_id'])) $f[] = 'Chưa xếp phòng';
    if (empty($daKhaiLuuTru[(int)$r['id']])) $f[] = 'Chưa khai báo lưu trú/tạm trú';
    if (empty($r['ngay_ky_hd'])) $f[] = 'Chưa có ngày ký HĐ';
    if (trim((string)($r['cccd'] ?? '')) === '') $f[] = 'Thiếu số CCCD/giấy tờ';
    if (trim((string)($r['lien_he_khan_cap'] ?? '')) === '') $f[] = 'Thiếu liên hệ khẩn cấp';
    if (trim((string)($r['email'] ?? '')) === '') $f[] = 'Thiếu email';
    return $f;
};

// ---- Ho so dang chon (khung chi tiet) ----
$idChon = (int)($_GET['id'] ?? 0);
if (!$idChon && $list) { $idChon = (int)$list[0]['id']; }
$hv = null; $banCungPhong = []; $phiNam = []; $phieuGanDay = []; $dnPhong = []; $duNo = null; $luuTru = [];
if (!$showForm && $idChon) {
    $st = $pdo->prepare('SELECT hv.*, p.so_phong, p.suc_chua, d.ten AS ten_day FROM ktx_hoc_vien hv LEFT JOIN ktx_phong p ON p.id = hv.phong_id LEFT JOIN ktx_day d ON d.id = p.day_id WHERE hv.id = ?');
    $st->execute([$idChon]); $hv = $st->fetch() ?: null;
}
if ($hv) {
    $hid = (int)$hv['id'];
    if (!empty($hv['phong_id'])) {
        $st = $pdo->prepare("SELECT id, ma_so, ho_ten, giuong, so_dt FROM ktx_hoc_vien WHERE phong_id = ? AND trang_thai = 'dang_o' AND id <> ? ORDER BY giuong, ho_ten");
        $st->execute([(int)$hv['phong_id'], $hid]); $banCungPhong = $st->fetchAll();
        $st = $pdo->prepare('SELECT * FROM ktx_dien_nuoc WHERE phong_id = ?'); $st->execute([(int)$hv['phong_id']]);
        $dnPhong = $st->fetchAll();
        usort($dnPhong, fn($a, $b) => ktx_dn_moc((int)$b['thang'], (bool)$b['la_quy'], (int)$b['nam']) <=> ktx_dn_moc((int)$a['thang'], (bool)$a['la_quy'], (int)$a['nam']));
        $dnPhong = array_slice($dnPhong, 0, 6);
    }
    $st = $pdo->prepare('SELECT * FROM ktx_phi_noi_tru WHERE hoc_vien_id = ? AND nam = ?'); $st->execute([$hid, $namXem]);
    foreach ($st->fetchAll() as $r) { $phiNam[(int)$r['thang']] = $r; }
    $st = $pdo->prepare('SELECT * FROM ktx_phieu_thu WHERE hoc_vien_id = ? OR id IN (SELECT phieu_thu_id FROM ktx_phieu_thu_ct WHERE hoc_vien_id = ?) ORDER BY ngay_thu DESC, id DESC LIMIT 10');
    $st->execute([$hid, $hid]); $phieuGanDay = $st->fetchAll();
    $duNo = ktx_du_no_hoc_vien($pdo, $hid);
    $st = $pdo->prepare('SELECT * FROM ktx_khai_bao_luu_tru WHERE hoc_vien_id = ? ORDER BY ngay_khai_bao DESC, id DESC'); $st->execute([$hid]);
    $luuTru = $st->fetchAll();
}

// Link giu nguyen bo loc dang xem
$giuLoc = function (array $them = [], array $bo = []): string {
    $g = $_GET; foreach (array_merge(['saved', 'nhac_checkout', 'edit', 'action'], $bo) as $k) { unset($g[$k]); }
    return '?' . http_build_query(array_merge($g, $them));
};
$o = fn($x) => ($x === null || $x === '') ? '<span class="hs-mut">—</span>' : h((string)$x);
$ngay = fn($x) => ($x === null || $x === '' || $x === '0000-00-00') ? '<span class="hs-mut">—</span>' : h(format_date_vn($x));
$tien = fn($n) => number_format((float)$n, 0, ',', '.');

// Menu chuc nang KTX ben trai (giu dung cac trang da co tren thanh nut cu)
$MENU = [
    ['ktx_hoc_vien.php', 'Hồ sơ nội trú', 'id', true],
    ['ktx_so_do_phong.php', 'Sơ đồ Phòng – Giường', 'bed', false],
    ['ktx_diem_danh.php', 'Điểm danh theo đợt', 'check', false],
    ['ktx_phi_noi_tru.php', 'Thu phí nội trú', 'coin', false],
    ['ktx_dien_nuoc.php', 'Điện, nước', 'bolt', false],
    ['ktx_luu_tru_tam_tru.php', 'Khai báo lưu trú / tạm trú', 'pin', false],
    ['ktx_ban_quan_ly.php', 'Ban Quản lý KTX', 'shield', false],
    ['ktx_doi_sv.php', 'Cán sự / Đội SV', 'star', false],
    ['ktx_doi_chieu_ds.php', 'Đối chiếu danh sách', 'swap', false],
    ['ktx_thong_bao_email.php', 'Gửi thông báo email', 'mail', false],
];

require_once __DIR__ . '/../includes/header.php';
?>
<style>
/* ===== Ho so noi tru KTX — giao dien 2026-10-10. Moi lop deu co tien to hs- de khong dung CSS chung cua trang quan tri. ===== */
.hs{--navy:#0d3a78;--navy2:#134b96;--pri:#1f6fd6;--pri-soft:#e7f0fc;--pri-line:#bcd3f3;--bg:#eef3fa;--line:#dfe7f2;--line2:#edf2f8;
  --ink:#1c2b41;--ink2:#4b5d75;--ink3:#8494aa;--green:#1f9d55;--green-soft:#e3f6eb;--orange:#f08c00;--orange-soft:#fff3e0;--red:#e03e3e;--red-soft:#fdeaea;--yellow:#f5b400;--violet:#6d4fd8;
  --shadow:0 1px 2px rgba(16,42,84,.06),0 4px 14px rgba(16,42,84,.06);
  max-width:1900px;margin:0 auto;padding:12px 14px 24px;background:var(--bg);color:var(--ink);font-family:"Segoe UI",Roboto,Arial,sans-serif;font-size:14px;border-radius:14px}
.hs *{box-sizing:border-box}.hs a{color:inherit}
.hs svg.i{width:18px;height:18px;flex:none;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.hs-mut{color:var(--ink3)}
/* dai tieu de */
.hs-top{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 16px;border-radius:12px;color:#fff;background:linear-gradient(90deg,var(--navy),var(--navy2));box-shadow:0 2px 8px rgba(13,58,120,.25);margin-bottom:12px}
.hs-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff!important}
.hs-brand .lg{width:38px;height:38px;border-radius:50%;border:2px solid rgba(255,255,255,.85);display:grid;place-items:center}
.hs-brand b{display:block;font-size:19px;letter-spacing:.3px;line-height:1.1}.hs-brand small{display:block;font-size:10.5px;opacity:.8;text-transform:uppercase;letter-spacing:.4px}
.hs-gs{flex:1;min-width:220px;max-width:460px;position:relative;margin:0}
.hs-gs input{width:100%;height:34px;border:0;border-radius:6px;padding:0 38px 0 12px;background:#fff;color:var(--ink);font:inherit}
.hs-gs button{position:absolute;right:4px;top:4px;height:26px;width:30px;border:0;background:none;color:var(--ink2);cursor:pointer}
.hs-tr{margin:0 0 0 auto;display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.hs-tb{display:flex;align-items:center;gap:6px;height:34px;padding:0 10px;border:0;border-radius:6px;background:transparent;color:#fff!important;text-decoration:none;white-space:nowrap;font:inherit;cursor:pointer}
.hs-tb:hover{background:rgba(255,255,255,.12)}
.hs-tb select{background:transparent;border:0;color:#fff;font:inherit;font-weight:600;cursor:pointer}.hs-tb select option{color:var(--ink)}
.hs-bell{position:relative}.hs-bell .n{position:absolute;top:1px;right:0;min-width:17px;height:17px;border-radius:9px;background:var(--red);font-size:10px;font-weight:700;display:grid;place-items:center;padding:0 4px}
/* khung 2 cot */
.hs-shell{display:grid;grid-template-columns:224px 1fr;gap:12px;align-items:start}
.hs-side{background:#fff;border:1px solid var(--line);border-radius:12px;padding:8px;position:sticky;top:10px}
.hs-side a{display:flex;align-items:center;gap:11px;padding:9px 11px;margin-bottom:2px;border-radius:8px;text-decoration:none;font-size:14px;color:var(--ink)}
.hs-side a svg{width:19px;height:19px;color:var(--navy2)}.hs-side a:hover{background:var(--pri-soft)}
.hs-side a.on{background:var(--pri);color:#fff;box-shadow:0 2px 6px rgba(31,111,214,.35)}.hs-side a.on svg{color:#fff}
.hs-side .sep{height:1px;background:var(--line);margin:8px 4px}
.hs-side .cap{font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;color:var(--ink3);padding:6px 11px 4px}
.hs-main{min-width:0}
.hs-card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow)}
.hs-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
.hs-msg.ok{background:var(--green-soft);border:1px solid #b7e3c8;color:#14653a}.hs-msg.warn{background:#fff6da;border:1px solid #e0c46a;color:#6b5200}
/* the so lieu */
.hs-kpis{display:grid;grid-template-columns:1fr 1fr 1fr 1.2fr;gap:12px;margin-bottom:12px}
.hs-kpi{padding:14px 16px;display:flex;flex-direction:column;gap:12px}
.hs-kpi .tp{display:flex;align-items:center;gap:14px}
.hs-kpi .ic{width:52px;height:52px;border-radius:50%;display:grid;place-items:center;flex:none}.hs-kpi .ic svg{width:26px;height:26px}
.hs-kpi .num{font-size:28px;font-weight:700;line-height:1.05;color:var(--navy)}.hs-kpi .lb{color:var(--ink2)}
.hs-kpi .sb{display:flex;border-top:1px solid var(--line2);padding-top:10px}
.hs-kpi .sb div{flex:1;padding:0 8px;border-left:1px solid var(--line2)}.hs-kpi .sb div:first-child{border-left:0;padding-left:0}
.hs-kpi .sb b{display:block;font-size:18px}.hs-kpi .sb span{font-size:12.5px;color:var(--ink2)}
.hs-wl{list-style:none;margin:0;padding:0;display:grid;gap:5px}
.hs-wl a{display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--ink2);text-decoration:none}.hs-wl a:hover{color:var(--pri)}
.hs-wl b{min-width:24px;text-align:right}
.hs-dot{width:8px;height:8px;border-radius:50%;flex:none;display:inline-block}
.hs-wh{display:flex;align-items:center;gap:10px}.hs-wh .bd{width:36px;height:36px;border-radius:8px;background:var(--red);color:#fff;display:grid;place-items:center}.hs-wh h3{margin:0;font-size:17px;color:var(--ink)}
.hs-wh a{margin-left:auto;font-size:12.5px;color:var(--pri)}
/* bao cao */
.hs-rep{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:10px 12px;border-bottom:1px solid var(--line);background:#fbfcfe}
.hs-rep[hidden]{display:none}
.hs-rep>*{border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:#fff;margin:0}
.hs-rep h4{margin:0 0 6px;font-size:13.5px;color:var(--navy)}.hs-rep.red h4{color:var(--red)}
.hs-rep p{margin:6px 0 0;font-size:12px;color:var(--ink2);line-height:1.45}
.hs-rep .row{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
/* thanh cong cu + loc + bang */
.hs-tool{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 12px;border-bottom:1px solid var(--line)}
.hs-tool h2{margin:0 8px 0 2px;font-size:16px;color:var(--navy);white-space:nowrap}
.hs-btn{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border:1px solid var(--line);border-radius:7px;background:#fff;color:var(--ink)!important;cursor:pointer;font:inherit;font-size:13px;white-space:nowrap;text-decoration:none!important}
.hs-btn svg{width:15px;height:15px}.hs-btn:hover{border-color:var(--pri-line);background:#f7faff}
.hs-btn.pri{background:var(--pri);border-color:var(--pri);color:#fff!important}.hs-btn.pri:hover{background:#185fbd}
.hs-btn.b svg{color:var(--pri)}.hs-btn.g svg{color:var(--green)}.hs-btn.o svg{color:var(--orange)}.hs-btn.v svg{color:var(--violet)}.hs-btn.r svg{color:var(--red)}
.hs-btn.r{color:var(--red)!important}.hs-btn.off{opacity:.45;pointer-events:none}
.hs-sp{flex:1}
.hs-f{height:32px;border:1px solid var(--line);border-radius:7px;padding:0 10px;background:#fff;font:inherit;color:var(--ink);min-width:0}
.hs-flt{display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding:10px 12px;border-bottom:1px solid var(--line);background:#f8fbff;margin:0}
.hs-flt label{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--ink2);margin:0;font-weight:400}
.hs-flt .ttl{display:flex;align-items:center;gap:6px;font-weight:600;color:var(--navy)}
.hs-tw{overflow:auto;max-height:440px}
table.hs-grid{width:100%;border-collapse:collapse;font-size:13.5px;margin:0}
table.hs-grid th{position:sticky;top:0;z-index:1;background:#f1f5fb;color:var(--ink);font-weight:600;text-align:left;padding:9px 10px;border:0;border-bottom:1px solid var(--line);border-right:1px solid var(--line2);white-space:nowrap}
table.hs-grid td{padding:0;border:0;border-bottom:1px solid var(--line2);border-right:1px solid var(--line2);white-space:nowrap;background:transparent}
table.hs-grid td>a.cell{display:block;padding:7px 10px;text-decoration:none;color:var(--ink)}
table.hs-grid tbody tr:nth-child(even){background:#fbfcfe}table.hs-grid tbody tr:hover{background:#f2f7ff}table.hs-grid tbody tr.sel{background:#d9e8fb}
table.hs-grid .c{text-align:center}
table.hs-grid td.act{padding:4px 8px}table.hs-grid td.act a{display:inline-grid;place-items:center;width:26px;height:26px;border-radius:6px;text-decoration:none;color:var(--ink2)}
table.hs-grid td.act a:hover{background:var(--pri-soft);color:var(--pri)}table.hs-grid td.act a.del:hover{background:var(--red-soft);color:var(--red)}
table.hs-grid td.act svg{width:15px;height:15px}
.hs-empty{padding:28px!important;text-align:center;color:var(--ink3)}
.hs-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:8px 12px;font-size:12.5px;color:var(--ink2)}
.hs-pg{display:flex;gap:4px;flex-wrap:wrap}.hs-pg a,.hs-pg span{min-width:28px;height:26px;display:grid;place-items:center;padding:0 6px;border:1px solid var(--line);border-radius:6px;text-decoration:none;background:#fff}
.hs-pg span.cur{background:var(--pri);color:#fff;border-color:var(--pri)}.hs-pg span.gap{border:0;background:none}
/* ho so chi tiet */
.hs-detail{display:grid;grid-template-columns:200px 1fr;gap:12px;margin-top:12px}
.hs-tabs{padding:6px;align-self:start}
.hs-tabs button{display:flex;align-items:center;gap:10px;width:100%;border:0;background:none;padding:10px;border-radius:8px;text-align:left;cursor:pointer;font:inherit;font-size:13.5px;color:var(--ink)}
.hs-tabs button svg{width:17px;height:17px;color:var(--navy2)}.hs-tabs button:hover{background:var(--pri-soft)}
.hs-tabs button.on{background:var(--pri);color:#fff}.hs-tabs button.on svg{color:#fff}
.hs-dh{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid var(--line)}
.hs-dh h2{margin:0;font-size:17px;color:var(--navy)}.hs-dh .nm{font-size:18px;font-weight:700;color:var(--pri)}
.hs-pill{display:inline-flex;align-items:center;padding:3px 12px;border-radius:999px;font-size:13px;font-weight:600}
.hs-pill.green{background:var(--green-soft);color:var(--green)}.hs-pill.gray{background:#eef1f5;color:var(--ink2)}.hs-pill.orange{background:var(--orange-soft);color:var(--orange)}
.hs-dh .acts{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.hs-db{padding:12px}.hs-pane{display:none}.hs-pane.on{display:block}
.hs-dg{display:grid;grid-template-columns:132px 1fr 1fr 1fr;gap:12px}
.hs-ph{width:132px;height:160px;border-radius:8px;border:1px solid var(--line);background:linear-gradient(180deg,#e8eef7,#d6e1f0);display:grid;place-items:center;color:#7d93b4;font-size:40px;font-weight:700;letter-spacing:1px}
.hs-sub{border:1px solid var(--line);border-radius:10px;overflow:hidden;min-width:0}
.hs-sh{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 12px;border-bottom:1px solid var(--line2);font-weight:700;color:var(--navy);font-size:13.5px;text-transform:uppercase;letter-spacing:.2px}
.hs-sh .hs-btn{height:26px;padding:0 9px;font-size:12px;text-transform:none;letter-spacing:0;font-weight:400}
.hs-kv{display:grid;grid-template-columns:120px 1fr;gap:7px 10px;padding:10px 12px;font-size:13.3px;margin:0}
.hs-kv dt{color:var(--ink2);font-weight:400}.hs-kv dd{margin:0;font-weight:500;overflow-wrap:anywhere}
.hs-r2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
table.hs-mini{width:100%;border-collapse:collapse;font-size:13px;margin:0}
table.hs-mini th{background:#f1f5fb;color:var(--ink);text-align:left;padding:7px 9px;border:0;border-bottom:1px solid var(--line);font-weight:600;white-space:nowrap}
table.hs-mini td{padding:7px 9px;border:0;border-bottom:1px solid var(--line2)}
table.hs-mini .r{text-align:right}
.hs-ok{color:var(--green);font-weight:600}.hs-no{color:var(--red);font-weight:600}
.hs-mo{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;padding:12px}
.hs-mo div{border:1px solid var(--line);border-radius:8px;padding:8px;text-align:center;font-size:12.5px}
.hs-mo b{display:block;font-size:13.5px;margin-bottom:2px}.hs-mo .paid{background:var(--green-soft);border-color:#b7e3c8}.hs-mo .due{background:var(--red-soft);border-color:#f3c2c2}
.hs-note{padding:10px 12px;margin:0;font-size:13.3px;line-height:1.5}
.hs-place{padding:30px;text-align:center;color:var(--ink3)}
/* form them/sua */
.hs-form{padding:4px 14px 14px}
.hs-form h3{margin:14px 0 8px;font-size:13px;color:var(--navy);text-transform:uppercase;letter-spacing:.3px;border-bottom:1px solid var(--line2);padding-bottom:6px}
.hs-fg{display:grid;grid-template-columns:repeat(3,1fr);gap:10px 14px}
.hs-fg label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--ink2);font-weight:600;margin:0}
.hs-fg input,.hs-fg select,.hs-fg textarea{width:100%;height:34px;border:1px solid var(--line);border-radius:7px;padding:0 10px;background:#fff;font:inherit;font-weight:400;color:var(--ink)}
.hs-fg textarea{height:auto;padding:8px 10px}
.hs-fg input:focus,.hs-fg select:focus,.hs-fg textarea:focus{outline:0;border-color:var(--pri);box-shadow:0 0 0 3px rgba(31,111,214,.15)}
.hs-fg .s2{grid-column:span 2}.hs-fg .s3{grid-column:1/-1}
.hs-gh{background:#fff8e8;border:1px solid #f0d78c;border-radius:10px;padding:4px 12px 12px;margin-top:14px}.hs-gh h3{color:#8a6d00;border-color:#f3e3b0}
.hs-fa{display:flex;gap:8px;justify-content:flex-end;padding:12px 14px;border-top:1px solid var(--line)}
.hs-tl{display:flex;gap:16px;align-items:center;flex-wrap:wrap;padding:10px 14px;background:#fff8e8;border-bottom:1px solid #f0d78c}
.hs-tl form{display:flex;gap:8px;align-items:center;margin:0}.hs-tl label{font-size:12.5px;color:var(--ink2);margin:0}
.hs-tl input{height:32px;border:1px solid var(--line);border-radius:7px;padding:0 8px;font:inherit}
@media (max-width:1300px){.hs-kpis{grid-template-columns:1fr 1fr}.hs-dg{grid-template-columns:132px 1fr 1fr}.hs-dg>.hs-sub:last-child{grid-column:2/-1}.hs-rep{grid-template-columns:1fr}}
@media (max-width:980px){.hs-shell{grid-template-columns:1fr}.hs-side{position:static;display:flex;overflow:auto;padding:6px}.hs-side a{flex:none;margin:0 4px 0 0}.hs-side .sep,.hs-side .cap{display:none}
  .hs-detail{grid-template-columns:1fr}.hs-tabs{display:flex;overflow:auto}.hs-tabs button{flex:none;width:auto}.hs-dg,.hs-r2{grid-template-columns:1fr}.hs-fg{grid-template-columns:1fr 1fr}.hs-mo{grid-template-columns:repeat(3,1fr)}}
@media (max-width:640px){.hs{padding:8px}.hs-kpis{grid-template-columns:1fr}.hs-fg{grid-template-columns:1fr}.hs-fg .s2{grid-column:auto}.hs-brand small{display:none}}
@media print{.hs-top,.hs-side,.hs-tool,.hs-flt,.hs-rep,.hs-tabs,.hs-dh .acts,.hs-kpis,.hs-foot,.hs-detail,table.hs-grid td.act,table.hs-grid th.act{display:none!important}
  .hs-shell{display:block}.hs-tw{max-height:none;overflow:visible}.hs-card{box-shadow:none}.hs{background:#fff}
  body.hs-in-ho-so .hs-list{display:none!important}body.hs-in-ho-so .hs-detail{display:block!important}body.hs-in-ho-so .hs-pane{display:block!important;margin-bottom:12px}}
</style>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="hi-id" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2.4"/><path d="M5.5 17c.6-2 2-3 3.5-3s2.9 1 3.5 3M14.5 9.5h4M14.5 13h4"/></symbol>
  <symbol id="hi-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.3-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><circle cx="17" cy="9" r="2.6"/><path d="M16 14.2c2.9.1 4.8 1.9 5.4 5.3"/></symbol>
  <symbol id="hi-bed" viewBox="0 0 24 24"><path d="M3 18V7M3 13h18v5M21 18v-3a3 3 0 0 0-3-3h-7v1"/><circle cx="7" cy="10.5" r="1.8"/></symbol>
  <symbol id="hi-check" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="m8.5 12 2.3 2.3L15.5 9.6"/></symbol>
  <symbol id="hi-coin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M14.8 9.2c-.5-.9-1.6-1.4-2.8-1.4-1.6 0-2.8.8-2.8 2.1 0 2.8 5.8 1.4 5.8 4.2 0 1.3-1.3 2.1-3 2.1-1.3 0-2.5-.6-3-1.6M12 6v1.8M12 16.2V18"/></symbol>
  <symbol id="hi-bolt" viewBox="0 0 24 24"><path d="M13 3 5 13.5h6L10 21l8-10.5h-6z"/></symbol>
  <symbol id="hi-pin" viewBox="0 0 24 24"><path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></symbol>
  <symbol id="hi-shield" viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6z"/><path d="M12 9v6M9 12h6"/></symbol>
  <symbol id="hi-star" viewBox="0 0 24 24"><path d="m12 3 2.7 5.6 6.1.8-4.5 4.2 1.1 6.1L12 16.8l-5.4 2.9 1.1-6.1-4.5-4.2 6.1-.8z"/></symbol>
  <symbol id="hi-swap" viewBox="0 0 24 24"><path d="M4 8h13l-3.5-3.5M20 16H7l3.5 3.5"/></symbol>
  <symbol id="hi-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/></symbol>
  <symbol id="hi-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></symbol>
  <symbol id="hi-bell" viewBox="0 0 24 24"><path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/></symbol>
  <symbol id="hi-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="hi-edit" viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/></symbol>
  <symbol id="hi-trash" viewBox="0 0 24 24"><path d="M4 7h16M9.5 7V4.5h5V7M6.5 7l1 13h9l1-13"/></symbol>
  <symbol id="hi-down" viewBox="0 0 24 24"><path d="M12 4v12M7 11l5 5 5-5M4 20h16"/></symbol>
  <symbol id="hi-print" viewBox="0 0 24 24"><path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="1.5"/><path d="M7 14h10v7H7z"/></symbol>
  <symbol id="hi-filter" viewBox="0 0 24 24"><path d="M4 5h16l-6 7.5V19l-4 1.5v-8z"/></symbol>
  <symbol id="hi-alert" viewBox="0 0 24 24"><path d="M12 3 2 20h20z"/><path d="M12 10v4.5M12 17.2h.01"/></symbol>
  <symbol id="hi-home" viewBox="0 0 24 24"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/></symbol>
  <symbol id="hi-file" viewBox="0 0 24 24"><path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M8.5 13h7M8.5 16.5h5"/></symbol>
  <symbol id="hi-folder" viewBox="0 0 24 24"><path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H9l2 2.5h8.5A1.5 1.5 0 0 1 21 9v9.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z"/></symbol>
  <symbol id="hi-receipt" viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></symbol>
  <symbol id="hi-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
  <symbol id="hi-save" viewBox="0 0 24 24"><path d="M5 4h11l3 3v13H5z"/><path d="M8 4v5h7V4M8 20v-6h8v6"/></symbol>
</svg>

<div class="hs">
  <div class="hs-top">
    <a class="hs-brand" href="ktx_hoc_vien.php"><span class="lg"><svg class="i"><use href="#hi-bed"/></svg></span><span><b>HỒ SƠ NỘI TRÚ KTX</b><small>Quản lý người học ở nội trú</small></span></a>
    <form class="hs-gs" method="get" action="ktx_hoc_vien.php">
      <input type="hidden" name="trang_thai" value="">
      <input name="q" value="<?= h($q) ?>" placeholder="Tìm người học (mã số, họ tên, CCCD, SĐT, phòng, giường…)">
      <button title="Tìm"><svg class="i"><use href="#hi-search"/></svg></button>
    </form>
    <form class="hs-tr" method="get">
      <?php foreach (['q' => $q, 'day_id' => $dayId ?: '', 'trang_thai' => $trangThai, 'gioi_tinh' => $gioiTinhLoc, 'nhanh' => $locNhanh, 'id' => $showForm ? '' : ($idChon ?: '')] as $k => $val): ?><input type="hidden" name="<?= $k ?>" value="<?= h((string)$val) ?>"><?php endforeach; ?>
      <a class="hs-tb hs-bell" href="#hs-canh-bao" title="Cảnh báo hồ sơ"><svg class="i" style="width:22px;height:22px"><use href="#hi-bell"/></svg><span class="n"><?= $tongCanhBao ?></span></a>
      <span class="hs-tb">Năm <select name="nam" onchange="this.form.submit()"><?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?><option <?= $y === $namXem ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?></select></span>
      <span class="hs-tb">Tháng <select name="thang" onchange="this.form.submit()"><?php for ($m = 1; $m <= 12; $m++): ?><option <?= $m === $thangXem ? 'selected' : '' ?>><?= $m ?></option><?php endfor; ?></select></span>
    </form>
  </div>

  <div class="hs-shell">
    <nav class="hs-side">
      <div class="cap">Ký túc xá</div>
      <?php foreach ($MENU as [$f, $t, $ic, $on]): ?><a class="<?= $on ? 'on' : '' ?>" href="<?= h($f) ?>"><svg class="i"><use href="#hi-<?= $ic ?>"/></svg><?= h($t) ?></a><?php endforeach; ?>
      <div class="sep"></div>
      <a href="?edit=new"><svg class="i"><use href="#hi-plus"/></svg>Thêm học viên</a>
    </nav>

    <div class="hs-main">
      <?php if (!empty($_GET['saved'])): ?>
      <div class="hs-msg ok"><span>✅ Đã lưu.</span><?php if ($showForm): ?><a class="hs-btn" href="ktx_hoc_vien.php<?= $editRow ? '?id=' . (int)$editRow['id'] . '#ho-so' : '' ?>">← Về danh sách / hồ sơ</a><?php endif; ?></div>
      <?php endif; ?>
      <?php if (!empty($_GET['nhac_checkout'])): ?>
      <div class="hs-msg warn"><span>⚠️ Đừng quên báo <b>Check-out/Trả phòng</b> cho học viên này trên Cổng Bộ Công an, sau đó vào <a href="ktx_luu_tru_tam_tru.php?tab=checkout"><b>Khai báo lưu trú/tạm trú → tab "Đã rời"</b></a> để ghi nhận lại.</span></div>
      <?php endif; ?>

      <?php if ($showForm): $e = $editRow ?: []; if (!$editRow && !empty($_GET['phong_id'])) { $e['phong_id'] = (int)$_GET['phong_id']; } ?>
      <!-- ===================== FORM THEM / SUA ===================== -->
      <div class="hs-card">
        <div class="hs-dh">
          <h2><?= $editRow ? 'CẬP NHẬT HỒ SƠ NỘI TRÚ' : 'THÊM HỌC VIÊN NỘI TRÚ' ?></h2>
          <?php if ($editRow): ?><span class="nm"><?= h($editRow['ho_ten']) ?></span>
            <span class="hs-pill <?= $editRow['trang_thai'] === 'dang_o' ? 'green' : 'gray' ?>"><?= $editRow['trang_thai'] === 'dang_o' ? 'Đang ở' : 'Đã rời KTX' ?></span><?php endif; ?>
          <div class="acts"><a class="hs-btn" href="ktx_hoc_vien.php<?= $editRow ? '?id=' . (int)$editRow['id'] . '#ho-so' : '' ?>">← Quay lại</a></div>
        </div>
        <?php if ($editRow && $editRow['trang_thai'] === 'dang_o'): ?>
        <div class="hs-tl">
          <form method="post" onsubmit="return confirm('Xác nhận THANH LÝ HỢP ĐỒNG cho học viên này?\n\nHọc viên sẽ chuyển sang trạng thái \'Đã rời KTX\', ghi nhận Ngày thanh lý HĐ, và phòng/giường đang ở sẽ được giải phóng cho người khác. Hồ sơ vẫn được giữ lại.');">
            <input type="hidden" name="action" value="thanh_ly_hd">
            <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
            <label>Ngày thanh lý HĐ</label>
            <input type="date" name="ngay_thanh_ly_hd" value="<?= date('Y-m-d') ?>">
            <button type="submit" class="hs-btn o"><svg class="i"><use href="#hi-file"/></svg>Thanh lý hợp đồng</button>
          </form>
          <form method="post" onsubmit="return confirm('Xác nhận HỦY ĐĂNG KÝ cho học viên này?\n\nHọc viên sẽ được gỡ khỏi phòng/giường hiện tại và chuyển sang trạng thái \'Đã rời KTX\' — dùng cho trường hợp xếp phòng nhầm hoặc chưa thực sự vào ở. Hồ sơ vẫn được giữ lại, KHÔNG xóa.');">
            <input type="hidden" name="action" value="huy_dang_ky">
            <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
            <button type="submit" class="hs-btn r"><svg class="i"><use href="#hi-trash"/></svg>Huỷ đăng ký</button>
          </form>
        </div>
        <?php endif; ?>
        <form method="post" id="hsForm">
          <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'add' ?>">
          <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>
          <div class="hs-form">
            <h3>Thông tin cá nhân</h3>
            <div class="hs-fg">
              <label>Mã số (SV/GV)<input type="text" name="ma_so" value="<?= h($e['ma_so'] ?? '') ?>"></label>
              <label>Loại đối tượng<input type="text" name="loai_doi_tuong" value="<?= h($e['loai_doi_tuong'] ?? 'Sinh viên CTUMP') ?>"></label>
              <label>Họ và tên *<input type="text" name="ho_ten" value="<?= h($e['ho_ten'] ?? '') ?>" required></label>
              <label>Giới tính
                <select name="gioi_tinh" id="hvGioiTinh" onchange="hvLocPhongTheoGioiTinh()">
                  <?php foreach (['Nam', 'Nữ', 'Khác'] as $g): ?><option value="<?= $g ?>" <?= ($e['gioi_tinh'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?>
                </select></label>
              <label>Ngày sinh<input type="date" name="ngay_sinh" value="<?= h($e['ngay_sinh'] ?? '') ?>"></label>
              <label>Nơi sinh<input type="text" name="noi_sinh" value="<?= h($e['noi_sinh'] ?? '') ?>"></label>
              <label>CCCD/CMND/Hộ chiếu<input type="text" name="cccd" value="<?= h($e['cccd'] ?? '') ?>"></label>
              <label>Loại giấy tờ
                <select name="loai_giay_to">
                  <?php foreach (['Thẻ CCCD', 'Thẻ CMND', 'Hộ chiếu'] as $lgt): ?><option value="<?= h($lgt) ?>" <?= ($e['loai_giay_to'] ?? 'Thẻ CCCD') === $lgt ? 'selected' : '' ?>><?= h($lgt) ?></option><?php endforeach; ?>
                </select></label>
              <label>Số điện thoại<input type="text" name="so_dt" value="<?= h($e['so_dt'] ?? '') ?>"></label>
              <label>Email SV<input type="email" name="email" maxlength="150" value="<?= h($e['email'] ?? '') ?>" placeholder="mã số@student.ctump.edu.vn"></label>
              <label>Dân tộc<input type="text" name="dan_toc" value="<?= h($e['dan_toc'] ?? '') ?>"></label>
              <label>Tôn giáo<input type="text" name="ton_giao" value="<?= h($e['ton_giao'] ?? '') ?>"></label>
              <label>Quốc tịch<input type="text" name="quoc_tich" value="<?= h($e['quoc_tich'] ?? 'Việt Nam') ?>"></label>
              <label class="s2">Địa chỉ<input type="text" name="dia_chi" value="<?= h($e['dia_chi'] ?? '') ?>"></label>
              <label class="s3">Nguyên quán<input type="text" name="nguyen_quan" value="<?= h($e['nguyen_quan'] ?? '') ?>"></label>
              <label class="s3">Liên hệ khẩn cấp<input type="text" name="lien_he_khan_cap" value="<?= h($e['lien_he_khan_cap'] ?? '') ?>" placeholder="Họ tên – quan hệ – số điện thoại"></label>
            </div>

            <h3>Học tập / công tác</h3>
            <div class="hs-fg">
              <label>Lớp học / Phòng ban<input type="text" name="lop_phong_ban" value="<?= h($e['lop_phong_ban'] ?? '') ?>"></label>
              <label>Khoá học<input type="text" name="khoa_hoc" value="<?= h($e['khoa_hoc'] ?? '') ?>"></label>
              <label>Nơi công tác<input type="text" name="noi_cong_tac" value="<?= h($e['noi_cong_tac'] ?? '') ?>"></label>
              <label class="s3">Đối tượng ưu tiên<input type="text" name="doi_tuong_uu_tien" value="<?= h($e['doi_tuong_uu_tien'] ?? '') ?>"></label>
            </div>

            <h3>Nội trú – hợp đồng</h3>
            <div class="hs-fg">
              <label>Phòng ở <span id="hvPhongGoiY" style="font-weight:400;color:var(--green)"></span>
                <select name="phong_id" id="hvPhongId">
                  <option value="">— Chưa xếp phòng —</option>
                  <?php foreach ($phongList as $p):
                      $trong = (int)$p['suc_chua'] - (int)$p['dang_o'];
                      $nhanTrong = $trong > 0 ? sprintf('(trống %02d)', $trong) : '(đủ)';
                  ?>
                    <option value="<?= (int)$p['id'] ?>" data-gioi-tinh="<?= h($p['day_gioi_tinh']) ?>" <?= (int)($e['phong_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= h($p['ten_day'] . ' — ' . $p['so_phong'] . ' ' . $nhanTrong) ?></option>
                  <?php endforeach; ?>
                </select></label>
              <label>Giường<input type="text" name="giuong" value="<?= h($e['giuong'] ?? '') ?>" placeholder="VD: A01.01"></label>
              <label>Trạng thái
                <select name="trang_thai">
                  <option value="dang_o" <?= ($e['trang_thai'] ?? 'dang_o') === 'dang_o' ? 'selected' : '' ?>>Đang ở</option>
                  <option value="da_roi" <?= ($e['trang_thai'] ?? '') === 'da_roi' ? 'selected' : '' ?>>Đã rời KTX</option>
                </select></label>
              <label>Ngày đăng ký<input type="date" name="ngay_dang_ky" value="<?= h($e['ngay_dang_ky'] ?? '') ?>"></label>
              <label>Ngày ký HĐ<input type="date" name="ngay_ky_hd" value="<?= h($e['ngay_ky_hd'] ?? '') ?>"></label>
              <label>Ngày thanh lý HĐ<input type="date" name="ngay_thanh_ly_hd" value="<?= h($e['ngay_thanh_ly_hd'] ?? '') ?>"></label>
              <label>Mã nhân sự đăng ký<input type="text" name="nhan_su_dang_ky_ma" value="<?= h($e['nhan_su_dang_ky_ma'] ?? '') ?>"></label>
              <label class="s2">Tên nhân sự đăng ký<input type="text" name="nhan_su_dang_ky_ten" value="<?= h($e['nhan_su_dang_ky_ten'] ?? '') ?>"></label>
              <label class="s3">Ghi chú<textarea name="ghi_chu" rows="2"><?= h($e['ghi_chu'] ?? '') ?></textarea></label>
            </div>

            <div class="hs-gh">
              <h3>🕒 Gia hạn nộp phí (hiển thị cho cả quản trị và sinh viên xem)</h3>
              <div class="hs-fg">
                <label>Hạn nộp phí điện, nước<input type="date" name="han_nop_dien_nuoc" value="<?= h($e['han_nop_dien_nuoc'] ?? '') ?>"></label>
                <label>Hạn nộp phí nội trú<input type="date" name="han_nop_phi_noi_tru" value="<?= h($e['han_nop_phi_noi_tru'] ?? '') ?>"></label>
                <label>Ghi chú gia hạn<input type="text" name="ghi_chu_gia_han" value="<?= h($e['ghi_chu_gia_han'] ?? '') ?>" placeholder="VD: Gia đình khó khăn, xin gia hạn đến hết tháng 10"></label>
              </div>
              <p style="color:#8a6d00;font-size:11.5px;margin:8px 0 0">Để trống nếu không gia hạn. Sinh viên sẽ thấy thông báo gia hạn này ngay trên trang cá nhân của mình.</p>
            </div>
          </div>
          <div class="hs-fa">
            <a href="ktx_hoc_vien.php<?= $editRow ? '?id=' . (int)$editRow['id'] . '#ho-so' : '' ?>" class="hs-btn">Huỷ</a>
            <button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg>Lưu</button>
          </div>
        </form>
      </div>
      <script>
      // Khi doi Gioi tinh (Nam/Nu), tu dong LOC danh sach Phong o chi con hien cac phong thuoc Day
      // TRUNG gioi tinh (dua vao ktx_day.gioi_tinh cua tung phong, dinh kem qua data-gioi-tinh cua
      // moi <option>) — Day gan "Khac" (neu co) van luon hien cho ca 2 gioi. CHI chay khi nguoi dung
      // TU TAY doi Gioi tinh (khong chay luc tai trang), tranh vo tinh xoa mat phong dang xep san khi
      // dang sua 1 ho so co san (2026-09-15).
      function hvLocPhongTheoGioiTinh() {
        var gioiTinh = document.getElementById('hvGioiTinh').value;
        var sel = document.getElementById('hvPhongId');
        var goiY = document.getElementById('hvPhongGoiY');
        Array.prototype.forEach.call(sel.options, function (opt) {
          if (!opt.value) { return; }
          var dgt = opt.getAttribute('data-gioi-tinh');
          var phuHop = !dgt || dgt === 'Khác' || dgt === gioiTinh;
          opt.hidden = !phuHop;
          opt.disabled = !phuHop;
        });
        var chonHienTai = sel.options[sel.selectedIndex];
        if (chonHienTai && chonHienTai.hidden) { sel.value = ''; }
        goiY.textContent = gioiTinh === 'Nam' ? '(đang lọc: Dãy Nam)' : (gioiTinh === 'Nữ' ? '(đang lọc: Dãy Nữ)' : '');
      }
      </script>

      <?php else: ?>
      <!-- ===================== TONG QUAN ===================== -->
      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num"><?= $demDangO ?></div><div class="lb">Người học đang ở</div></div></div>
          <div class="sb">
            <div><b style="color:var(--green)"><?= $demVaoThangNay ?></b><span>Đăng ký T<?= $thangXem ?></span></div>
            <div><b style="color:var(--orange)"><?= $demRoiThangNay ?></b><span>Thanh lý T<?= $thangXem ?></span></div>
            <div><b><?= $demDaRoi ?></b><span>Đã rời KTX</span></div>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-bed"/></svg></div>
            <div><div class="num"><?= $sucChua ? round($demDaXep / $sucChua * 100) : 0 ?>%</div><div class="lb">Công suất giường</div></div></div>
          <div class="sb">
            <div><b><?= $demDaXep ?></b><span>Giường đã ở</span></div>
            <div><b><?= max(0, $sucChua - $demDaXep) ?></b><span>Giường trống</span></div>
            <div><b><?= $soPhongSd ?></b><span>Phòng sử dụng</span></div>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-coin"/></svg></div>
            <div><div class="num"><?= $tien($phiThang['t']) ?></div><div class="lb">Phí nội trú đã thu T<?= $thangXem ?>/<?= $namXem ?></div></div></div>
          <div class="sb">
            <div><b style="color:var(--green)"><?= (int)$phiThang['n'] ?></b><span>Người đã nộp</span></div>
            <div><b style="color:var(--red)"><?= $demCanhBao['chua_phi'] ?></b><span>Đang ở chưa nộp</span></div>
          </div>
        </div>
        <div class="hs-card hs-kpi" id="hs-canh-bao">
          <div class="hs-wh"><div class="bd"><svg class="i"><use href="#hi-alert"/></svg></div><h3>Cảnh báo</h3>
            <?php if ($locNhanh): ?><a href="<?= h($giuLoc([], ['nhanh', 'page', 'id'])) ?>">Bỏ lọc cảnh báo</a><?php endif; ?></div>
          <ul class="hs-wl">
            <?php foreach ([['chua_phi', 'var(--red)'], ['chua_xep', 'var(--red)'], ['chua_luu_tru', 'var(--red)'], ['chua_checkout', 'var(--orange)'], ['chua_hd', 'var(--orange)'], ['thieu_cccd', 'var(--yellow)']] as [$k, $mau]): ?>
            <li><a href="<?= h($giuLoc(['nhanh' => $k, 'trang_thai' => $k === 'chua_checkout' ? 'da_roi' : 'dang_o'], ['page', 'id'])) ?>"<?= $k === $locNhanh ? ' style="color:var(--pri);font-weight:600"' : '' ?>><span class="hs-dot" style="background:<?= $mau ?>"></span><b style="color:<?= $mau ?>"><?= $demCanhBao[$k] ?></b><?= h($LOC_NHANH[$k]) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- ===================== DANH SACH ===================== -->
      <div class="hs-card hs-list">
        <div class="hs-tool">
          <h2>DANH SÁCH NGƯỜI HỌC NỘI TRÚ</h2>
          <a class="hs-btn pri" href="?edit=new"><svg class="i"><use href="#hi-plus"/></svg>Thêm học viên</a>
          <a class="hs-btn b <?= $hv ? '' : 'off' ?>" href="<?= $hv ? '?edit=' . (int)$hv['id'] : '#' ?>"><svg class="i"><use href="#hi-edit"/></svg>Sửa</a>
          <a class="hs-btn g <?= $hv ? '' : 'off' ?>" href="<?= $hv ? 'ktx_phi_noi_tru.php?tab=ca_nhan&ma_so=' . urlencode((string)$hv['ma_so']) : '#' ?>"><svg class="i"><use href="#hi-coin"/></svg>Thu phí</a>
          <button class="hs-btn o" type="button" onclick="var r=document.getElementById('hsRep');r.hidden=!r.hidden"><svg class="i"><use href="#hi-down"/></svg>Xuất báo cáo ▾</button>
          <button class="hs-btn v" type="button" onclick="window.print()"><svg class="i"><use href="#hi-print"/></svg>In danh sách</button>
          <span class="hs-sp"></span>
          <span style="font-size:12.5px;color:var(--ink2)"><?= $total ?> hồ sơ phù hợp</span>
        </div>
        <div class="hs-rep" id="hsRep" hidden>
          <form method="get" action="ktx_export_danh_sach_noi_tru.php">
            <h4>Danh sách người học ở nội trú theo quý</h4>
            <div class="row">
              <label>Tháng</label>
              <select class="hs-f" name="thang"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>" <?= $thangXem === $m ? 'selected' : '' ?>><?= $m ?></option><?php endfor; ?></select>
              <label>Năm</label>
              <input class="hs-f" type="number" name="nam" value="<?= $namXem ?>" style="width:84px">
              <button type="submit" class="hs-btn g"><svg class="i"><use href="#hi-down"/></svg>Xuất Excel</button>
            </div>
            <p>Mẫu sheet “Q4_TỔNG HỢP KTX”: nhóm Dãy Nam – Dãy Nữ, dòng Tổng cộng, Nơi nhận và chữ ký. Quý tính theo tháng chọn.</p>
          </form>
          <div>
            <h4>Hồ sơ tổng hợp (27 trường thông tin)</h4>
            <div class="row">
              <a href="ktx_export_ho_so_tong_hop.php?pham_vi=toan_bo" class="hs-btn b"><svg class="i"><use href="#hi-down"/></svg>Toàn bộ (đang ở)</a>
              <a href="ktx_export_ho_so_tong_hop.php?pham_vi=loc&amp;<?= h(http_build_query(['q' => $q, 'day_id' => $dayId, 'trang_thai' => $trangThai])) ?>" class="hs-btn"><svg class="i"><use href="#hi-filter"/></svg>Theo lọc hiện tại</a>
            </div>
            <p>“Toàn bộ” chỉ gồm người học đang ở thực tế — không gồm người đã thanh lý HĐ/huỷ đăng ký.</p>
          </div>
          <div style="border-color:#f3c2c2;background:#fffafa">
            <h4 style="color:var(--red)">Đã thanh lý HĐ / huỷ đăng ký</h4>
            <div class="row"><a href="ktx_export_ho_so_thanh_ly.php" class="hs-btn r"><svg class="i"><use href="#hi-down"/></svg>Xuất danh sách</a></div>
            <p>Gồm cả 2 trường hợp đã rời KTX, có cột “Lý do rời” phân biệt Thanh lý HĐ / Huỷ đăng ký.</p>
          </div>
        </div>
        <form class="hs-flt" method="get">
          <input type="hidden" name="thang" value="<?= $thangXem ?>"><input type="hidden" name="nam" value="<?= $namXem ?>">
          <span class="ttl"><svg class="i" style="width:15px;height:15px"><use href="#hi-filter"/></svg>Bộ lọc</span>
          <input class="hs-f" type="text" name="q" value="<?= h($q) ?>" placeholder="Tên, mã số, CCCD, SĐT, phòng…" style="width:230px">
          <label>Dãy <select class="hs-f" name="day_id"><option value="">Tất cả các dãy</option>
            <?php foreach ($dayList as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $dayId === (int)$d['id'] ? 'selected' : '' ?>><?= h($d['ten']) ?><?= !empty($d['gioi_tinh']) ? ' (' . h($d['gioi_tinh']) . ')' : '' ?></option><?php endforeach; ?></select></label>
          <label>Trạng thái <select class="hs-f" name="trang_thai">
            <option value="dang_o" <?= $trangThai === 'dang_o' ? 'selected' : '' ?>>Đang ở</option>
            <option value="da_roi" <?= $trangThai === 'da_roi' ? 'selected' : '' ?>>Đã rời KTX</option>
            <option value="" <?= $trangThai === '' ? 'selected' : '' ?>>Tất cả</option></select></label>
          <label>Giới tính <select class="hs-f" name="gioi_tinh"><option value="">Tất cả</option><?php foreach (['Nam', 'Nữ', 'Khác'] as $g): ?><option <?= $g === $gioiTinhLoc ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></label>
          <label>Cảnh báo <select class="hs-f" name="nhanh"><option value="">—</option><?php foreach ($LOC_NHANH as $k => $t): ?><option value="<?= $k ?>" <?= $k === $locNhanh ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?></select></label>
          <button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-search"/></svg>Tìm</button>
          <a class="hs-btn" href="ktx_hoc_vien.php?thang=<?= $thangXem ?>&amp;nam=<?= $namXem ?>">Xoá lọc</a>
        </form>
        <div class="hs-tw">
          <table class="hs-grid">
            <thead><tr><th class="c">STT</th><th>Mã số</th><th>Họ và tên</th><th class="c">GT</th><th>Ngày sinh</th><th>Lớp</th><th>Dãy</th><th class="c">Phòng</th><th class="c">Giường</th><th>SĐT</th><th>Phí T<?= $thangXem ?></th><th>Trạng thái</th><th class="act"></th></tr></thead>
            <tbody>
            <?php foreach ($list as $i => $r): $u = h($giuLoc(['id' => (int)$r['id']])) . '#ho-so'; $cb = $canhBaoCua($r); $dangORow = $r['trang_thai'] === 'dang_o'; ?>
              <tr class="<?= (int)$r['id'] === $idChon ? 'sel' : '' ?>">
                <td class="c"><a class="cell" href="<?= $u ?>"><?= $offset + $i + 1 ?></a></td>
                <td><a class="cell" href="<?= $u ?>"><?= $o($r['ma_so']) ?></a></td>
                <td><a class="cell" href="<?= $u ?>"><?= h($r['ho_ten']) ?><?php if (!empty($r['han_nop_dien_nuoc']) || !empty($r['han_nop_phi_noi_tru'])): ?> <span title="Đang gia hạn nộp phí<?= !empty($r['han_nop_dien_nuoc']) ? ' — Điện nước đến ' . h(format_date_vn($r['han_nop_dien_nuoc'])) : '' ?><?= !empty($r['han_nop_phi_noi_tru']) ? ' — Nội trú đến ' . h(format_date_vn($r['han_nop_phi_noi_tru'])) : '' ?>">🕒</span><?php endif; ?></a></td>
                <td class="c"><a class="cell" href="<?= $u ?>"><?= h($r['gioi_tinh']) ?></a></td>
                <td><a class="cell" href="<?= $u ?>"><?= $ngay($r['ngay_sinh']) ?></a></td>
                <td><a class="cell" href="<?= $u ?>"><?= $o($r['lop_phong_ban']) ?></a></td>
                <td><a class="cell" href="<?= $u ?>"><?= $r['ten_day'] ? h($r['ten_day']) : ($dangORow ? '<span class="hs-no">Chưa xếp phòng</span>' : '<span class="hs-mut">—</span>') ?></a></td>
                <td class="c"><a class="cell" href="<?= $u ?>"><?= $o($r['so_phong']) ?></a></td>
                <td class="c"><a class="cell" href="<?= $u ?>"><?= $o($r['giuong']) ?></a></td>
                <td><a class="cell" href="<?= $u ?>"><?= $o($r['so_dt']) ?></a></td>
                <td><a class="cell" href="<?= $u ?>"><?= $dangORow ? (!empty($daNop[(int)$r['id']]) ? '<span class="hs-ok">Đã nộp</span>' : '<span class="hs-no">Chưa nộp</span>') : '<span class="hs-mut">—</span>' ?></a></td>
                <td><a class="cell" href="<?= $u ?>" title="<?= h(implode(' · ', $cb)) ?>"><span class="hs-dot" style="background:<?= $dangORow ? 'var(--green)' : '#98a4b5' ?>"></span> <?= $dangORow ? 'Đang ở' : 'Đã rời' ?><?= $cb ? ' <span style="color:var(--red)" aria-label="có cảnh báo">●</span>' : '' ?></a></td>
                <td class="act">
                  <a href="?edit=<?= (int)$r['id'] ?>" title="Sửa"><svg class="i"><use href="#hi-edit"/></svg></a>
                  <a href="ktx_ho_so_sinh_vien.php?id=<?= (int)$r['id'] ?>" title="Hồ sơ sinh viên đầy đủ"><svg class="i"><use href="#hi-folder"/></svg></a>
                  <a href="ktx_don_export.php?nguon=hocvien&amp;id=<?= (int)$r['id'] ?>" title="Xuất đơn đăng ký ở KTX (Word)" target="_blank"><svg class="i"><use href="#hi-file"/></svg></a>
                  <a href="?action=xoa&amp;id=<?= (int)$r['id'] ?>" class="del" title="Xoá" onclick="return confirm('Xoá học viên này khỏi danh sách KTX?')"><svg class="i"><use href="#hi-trash"/></svg></a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$list): ?><tr><td colspan="13" class="hs-empty">Không có dữ liệu phù hợp.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="hs-foot">
          <span>Hiển thị <?= count($list) ?> / <?= $total ?> học viên · <span style="color:var(--red)">●</span> có cảnh báo (rê chuột để xem) · 🕒 đang gia hạn nộp phí</span>
          <?php if ($totalPages > 1): ?><span class="hs-pg">
            <?php for ($p = 1; $p <= $totalPages; $p++):
                if ($p > 2 && $p < $totalPages - 1 && abs($p - $page) > 2) { if ($p === 3 || $p === $totalPages - 2) { echo '<span class="gap">…</span>'; } continue; } ?>
              <?php if ($p == $page): ?><span class="cur"><?= $p ?></span><?php else: ?><a href="<?= h($giuLoc(['page' => $p], ['id'])) ?>"><?= $p ?></a><?php endif; ?>
            <?php endfor; ?></span><?php endif; ?>
        </div>
      </div>

      <!-- ===================== HO SO CHI TIET ===================== -->
      <div class="hs-detail" id="ho-so">
        <div class="hs-card hs-tabs" id="hsTabs">
          <button type="button" data-t="chung" class="on"><svg class="i"><use href="#hi-home"/></svg>Thông tin chung</button>
          <button type="button" data-t="hd"><svg class="i"><use href="#hi-file"/></svg>Đăng ký – Hợp đồng</button>
          <button type="button" data-t="lt"><svg class="i"><use href="#hi-pin"/></svg>Lưu trú – Check-out</button>
          <button type="button" data-t="phi"><svg class="i"><use href="#hi-coin"/></svg>Phí nội trú <?= $namXem ?></button>
          <button type="button" data-t="phieu"><svg class="i"><use href="#hi-receipt"/></svg>Phiếu thu</button>
          <button type="button" data-t="dn"><svg class="i"><use href="#hi-bolt"/></svg>Phòng – Điện nước</button>
        </div>
        <div class="hs-card">
        <?php if (!$hv): ?>
          <div class="hs-place">Chọn một người học trong danh sách để xem hồ sơ.</div>
        <?php else: $cbHv = $canhBaoCua($hv); $hvDangO = $hv['trang_thai'] === 'dang_o';
            $tachTen = preg_split('/\s+/u', trim((string)$hv['ho_ten'])); $viTat = mb_strtoupper(mb_substr((string)end($tachTen), 0, 1)); ?>
          <div class="hs-dh">
            <h2>HỒ SƠ NỘI TRÚ – <?= $o($hv['ma_so']) ?></h2><span class="nm"><?= h($hv['ho_ten']) ?></span>
            <span class="hs-pill <?= $hvDangO ? 'green' : 'gray' ?>"><?= $hvDangO ? 'Đang ở' : (!empty($hv['ngay_thanh_ly_hd']) ? 'Đã thanh lý HĐ' : 'Đã rời KTX') ?></span>
            <?php if (!empty($hv['han_nop_dien_nuoc']) || !empty($hv['han_nop_phi_noi_tru'])): ?><span class="hs-pill orange">🕒 Đang gia hạn nộp phí</span><?php endif; ?>
            <div class="acts">
              <a class="hs-btn pri" href="?edit=<?= (int)$hv['id'] ?>"><svg class="i"><use href="#hi-edit"/></svg>Sửa hồ sơ</a>
              <a class="hs-btn g" href="ktx_phi_noi_tru.php?tab=ca_nhan&amp;ma_so=<?= urlencode((string)$hv['ma_so']) ?>"><svg class="i"><use href="#hi-coin"/></svg>Lập phiếu thu</a>
              <a class="hs-btn" href="ktx_ho_so_sinh_vien.php?id=<?= (int)$hv['id'] ?>"><svg class="i"><use href="#hi-folder"/></svg>Hồ sơ đầy đủ</a>
              <a class="hs-btn" href="ktx_don_export.php?nguon=hocvien&amp;id=<?= (int)$hv['id'] ?>" target="_blank"><svg class="i"><use href="#hi-file"/></svg>Đơn (Word)</a>
              <button class="hs-btn" type="button" onclick="document.body.classList.add('hs-in-ho-so');window.print();document.body.classList.remove('hs-in-ho-so')"><svg class="i"><use href="#hi-print"/></svg>In hồ sơ</button>
            </div>
          </div>
          <div class="hs-db">
            <div class="hs-pane on" data-p="chung">
              <div class="hs-dg">
                <div class="hs-ph" aria-hidden="true"><?= h($viTat) ?></div>
                <div class="hs-sub"><div class="hs-sh">Thông tin cá nhân</div>
                  <dl class="hs-kv">
                    <dt>Mã số</dt><dd><?= $o($hv['ma_so']) ?></dd><dt>Họ và tên</dt><dd><?= h($hv['ho_ten']) ?></dd>
                    <dt>Giới tính</dt><dd><?= $o($hv['gioi_tinh']) ?></dd><dt>Ngày sinh</dt><dd><?= $ngay($hv['ngay_sinh']) ?></dd>
                    <dt>Nơi sinh</dt><dd><?= $o($hv['noi_sinh']) ?></dd><dt><?= h($hv['loai_giay_to'] ?: 'CCCD') ?></dt><dd><?= $o($hv['cccd']) ?></dd>
                    <dt>Dân tộc</dt><dd><?= $o($hv['dan_toc']) ?></dd><dt>Tôn giáo</dt><dd><?= $o($hv['ton_giao']) ?></dd>
                    <dt>Quốc tịch</dt><dd><?= $o($hv['quoc_tich']) ?></dd><dt>Số điện thoại</dt><dd><?= $o($hv['so_dt']) ?></dd>
                    <dt>Email</dt><dd><?= $o($hv['email']) ?></dd><dt>Địa chỉ</dt><dd><?= $o($hv['dia_chi']) ?></dd>
                    <dt>Nguyên quán</dt><dd><?= $o($hv['nguyen_quan']) ?></dd>
                  </dl></div>
                <div class="hs-sub"><div class="hs-sh">Thông tin nội trú</div>
                  <dl class="hs-kv">
                    <dt>Loại đối tượng</dt><dd><?= $o($hv['loai_doi_tuong']) ?></dd>
                    <dt>Lớp / phòng ban</dt><dd><?= $o($hv['lop_phong_ban']) ?></dd><dt>Khoá học</dt><dd><?= $o($hv['khoa_hoc']) ?></dd>
                    <dt>Nơi công tác</dt><dd><?= $o($hv['noi_cong_tac']) ?></dd>
                    <dt>Dãy</dt><dd><?= $o($hv['ten_day']) ?></dd><dt>Phòng</dt><dd><?= $o($hv['so_phong']) ?><?= !empty($hv['phong_id']) ? ' <span class="hs-mut">(' . (count($banCungPhong) + 1) . '/' . (int)$hv['suc_chua'] . ' người)</span>' : '' ?></dd>
                    <dt>Giường</dt><dd><?= $o($hv['giuong']) ?></dd>
                    <dt>Đối tượng ƯT</dt><dd><?= $o($hv['doi_tuong_uu_tien']) ?></dd>
                    <dt>Phí T<?= $thangXem ?>/<?= $namXem ?></dt><dd><?= $hvDangO ? (!empty($daNop[(int)$hv['id']]) ? '<span class="hs-ok">Đã nộp</span>' : '<span class="hs-no">Chưa nộp</span>') : '<span class="hs-mut">—</span>' ?></dd>
                    <dt>Dư nợ</dt><dd><b class="<?= (float)$duNo > 0 ? 'hs-no' : 'hs-ok' ?>"><?= $tien($duNo) ?> đ</b> <a href="ktx_phi_noi_tru_congno.php?hoc_vien_id=<?= (int)$hv['id'] ?>" target="_blank" style="font-size:12px;color:var(--pri)">Diễn biến</a></dd>
                  </dl></div>
                <div class="hs-sub"><div class="hs-sh">Cần bổ sung / cảnh báo</div>
                  <?php if ($cbHv): ?><ul class="hs-wl" style="padding:10px 12px"><?php foreach ($cbHv as $c): ?><li style="display:flex;gap:8px;align-items:center;font-size:13.3px"><span class="hs-dot" style="background:var(--red)"></span><?= h($c) ?></li><?php endforeach; ?></ul>
                  <?php elseif ($hvDangO): ?><p class="hs-note hs-ok">✓ Hồ sơ đầy đủ, không có cảnh báo.</p>
                  <?php else: ?><p class="hs-note hs-mut">Người học đã rời KTX.</p><?php endif; ?>
                  <?php if (!empty($hv['ghi_chu'])): ?><div class="hs-sh" style="border-top:1px solid var(--line2)">Ghi chú</div><p class="hs-note"><?= nl2br(h($hv['ghi_chu'])) ?></p><?php endif; ?>
                </div>
              </div>
              <div class="hs-r2">
                <div class="hs-sub"><div class="hs-sh">Liên hệ khẩn cấp<a class="hs-btn" href="?edit=<?= (int)$hv['id'] ?>"><svg class="i"><use href="#hi-edit"/></svg>Sửa</a></div>
                  <p class="hs-note"><?= trim((string)$hv['lien_he_khan_cap']) !== '' ? nl2br(h($hv['lien_he_khan_cap'])) : '<span class="hs-no">Chưa có người liên hệ khẩn cấp</span>' ?></p></div>
                <div class="hs-sub"><div class="hs-sh">Bạn cùng phòng</div>
                  <table class="hs-mini"><thead><tr><th>Giường</th><th>Mã số</th><th>Họ và tên</th><th>SĐT</th></tr></thead><tbody>
                  <?php foreach ($banCungPhong as $b): ?><tr><td><?= $o($b['giuong']) ?></td><td><a href="<?= h($giuLoc(['id' => (int)$b['id']])) ?>#ho-so" style="color:var(--pri)"><?= $o($b['ma_so']) ?></a></td><td><?= h($b['ho_ten']) ?></td><td><?= $o($b['so_dt']) ?></td></tr><?php endforeach; ?>
                  <?php if (!$banCungPhong): ?><tr><td colspan="4" class="hs-mut"><?= empty($hv['phong_id']) ? 'Chưa xếp phòng' : 'Hiện ở một mình' ?></td></tr><?php endif; ?>
                  </tbody></table></div>
              </div>
            </div>

            <div class="hs-pane" data-p="hd">
              <div class="hs-r2" style="margin-top:0">
                <div class="hs-sub"><div class="hs-sh">Đăng ký – hợp đồng</div>
                  <dl class="hs-kv">
                    <dt>Ngày đăng ký</dt><dd><?= $ngay($hv['ngay_dang_ky']) ?></dd>
                    <dt>Ngày ký HĐ</dt><dd><?= $ngay($hv['ngay_ky_hd']) ?></dd>
                    <dt>Ngày thanh lý HĐ</dt><dd><?= $ngay($hv['ngay_thanh_ly_hd']) ?></dd>
                    <dt>Trạng thái</dt><dd><?= $hvDangO ? 'Đang ở' : (!empty($hv['ngay_thanh_ly_hd']) ? 'Đã rời – Thanh lý HĐ' : 'Đã rời – Huỷ đăng ký') ?></dd>
                    <dt>NS đăng ký</dt><dd><?= $o(trim(($hv['nhan_su_dang_ky_ma'] ? $hv['nhan_su_dang_ky_ma'] . ' – ' : '') . ($hv['nhan_su_dang_ky_ten'] ?? ''))) ?></dd>
                  </dl>
                  <?php if ($hvDangO): ?><p class="hs-note"><a class="hs-btn o" href="?edit=<?= (int)$hv['id'] ?>"><svg class="i"><use href="#hi-file"/></svg>Thanh lý HĐ / Huỷ đăng ký…</a></p><?php endif; ?>
                </div>
                <div class="hs-sub"><div class="hs-sh">Gia hạn nộp phí</div>
                  <dl class="hs-kv">
                    <dt>Điện, nước</dt><dd><?= $ngay($hv['han_nop_dien_nuoc']) ?></dd>
                    <dt>Phí nội trú</dt><dd><?= $ngay($hv['han_nop_phi_noi_tru']) ?></dd>
                    <dt>Ghi chú</dt><dd><?= $o($hv['ghi_chu_gia_han']) ?></dd>
                  </dl></div>
              </div>
            </div>

            <div class="hs-pane" data-p="lt">
              <div class="hs-sub"><div class="hs-sh">Khai báo lưu trú / tạm trú – Check-out
                <a class="hs-btn" href="ktx_luu_tru_tam_tru.php?tab=<?= $hvDangO ? 'dang_o' : 'checkout' ?>&amp;ghi_nhan=<?= (int)$hv['id'] ?>#top"><svg class="i"><use href="#hi-edit"/></svg>Ghi nhận</a></div>
                <?php if ($hvDangO && !$luuTru): ?><p class="hs-note hs-no">Chưa có lần khai báo lưu trú/tạm trú nào.</p><?php endif; ?>
                <?php if (!$hvDangO && !array_filter($luuTru, fn($k) => $k['loai'] === 'checkout')): ?><p class="hs-note hs-no">Đã rời KTX nhưng chưa ghi nhận báo Check-out/Trả phòng trên Cổng Bộ Công an.</p><?php endif; ?>
                <table class="hs-mini"><thead><tr><th>Loại</th><th>Ngày khai báo</th><th>Hết hạn / khai lại</th><th>Ghi chú</th></tr></thead><tbody>
                <?php foreach ($luuTru as $k): ?><tr>
                  <td><?= $k['loai'] === 'checkout' ? 'Check-out / Trả phòng' : h(defined('KTX_LOAI_KHAI_BAO_LABEL') ? (KTX_LOAI_KHAI_BAO_LABEL[$k['loai']] ?? $k['loai']) : $k['loai']) ?></td>
                  <td><?= $ngay($k['ngay_khai_bao']) ?></td>
                  <td><?php if (!empty($k['ngay_het_han'])): ?><?= $ngay($k['ngay_het_han']) ?><?= $k['ngay_het_han'] < date('Y-m-d') ? ' <span class="hs-no">(quá hạn)</span>' : '' ?><?php else: ?><span class="hs-mut">—</span><?php endif; ?></td>
                  <td><?= $o($k['ghi_chu'] ?? '') ?></td></tr><?php endforeach; ?>
                <?php if (!$luuTru): ?><tr><td colspan="4" class="hs-mut">Chưa có bản ghi.</td></tr><?php endif; ?>
                </tbody></table></div>
            </div>

            <div class="hs-pane" data-p="phi">
              <div class="hs-sub"><div class="hs-sh">Phí nội trú năm <?= $namXem ?><a class="hs-btn" href="ktx_phi_noi_tru.php?tab=ca_nhan&amp;ma_so=<?= urlencode((string)$hv['ma_so']) ?>"><svg class="i"><use href="#hi-plus"/></svg>Lập phiếu thu</a></div>
                <div class="hs-mo">
                  <?php for ($m = 1; $m <= 12; $m++): $f = $phiNam[$m] ?? null; $paid = $f && (int)$f['da_nop'] === 1; ?>
                  <div class="<?= $paid ? 'paid' : ($f ? 'due' : '') ?>"><b>Tháng <?= $m ?></b><?php if ($f): ?><?= $tien($f['so_tien']) ?> đ<br><?= $paid ? '<span class="hs-ok">Đã nộp</span>' . (!empty($f['ngay_nop']) ? '<br><small>' . h(format_date_vn($f['ngay_nop'])) . '</small>' : '') : '<span class="hs-no">Chưa nộp</span>' ?><?php else: ?><span class="hs-mut">Chưa phát sinh</span><?php endif; ?></div>
                  <?php endfor; ?>
                </div></div>
            </div>

            <div class="hs-pane" data-p="phieu">
              <div class="hs-sub"><div class="hs-sh">10 phiếu thu gần nhất<a class="hs-btn" href="ktx_phi_noi_tru_congno.php?hoc_vien_id=<?= (int)$hv['id'] ?>" target="_blank"><svg class="i"><use href="#hi-clock"/></svg>Diễn biến công nợ</a></div>
                <table class="hs-mini"><thead><tr><th>Số phiếu</th><th>Ngày thu</th><th>Hình thức</th><th class="r">Tổng tiền</th><th class="r">Đã nộp</th><th class="r">Còn lại</th><th></th></tr></thead><tbody>
                <?php foreach ($phieuGanDay as $pt): ?><tr><td><?= h((string)$pt['so_phieu']) ?></td><td><?= $ngay($pt['ngay_thu']) ?></td><td><?= $o($pt['hinh_thuc_tt']) ?></td>
                  <td class="r"><?= $tien($pt['tong_tien']) ?></td><td class="r"><?= $tien($pt['so_tien_nop']) ?></td><td class="r <?= (float)$pt['con_lai'] > 0 ? 'hs-no' : '' ?>"><?= $tien($pt['con_lai']) ?></td>
                  <td><a href="ktx_phieu_thu_in.php?id=<?= (int)$pt['id'] ?>" target="_blank" style="color:var(--pri)">In</a></td></tr><?php endforeach; ?>
                <?php if (!$phieuGanDay): ?><tr><td colspan="7" class="hs-mut">Chưa có phiếu thu.</td></tr><?php endif; ?>
                </tbody></table></div>
            </div>

            <div class="hs-pane" data-p="dn">
              <div class="hs-sub"><div class="hs-sh">Điện, nước phòng <?= h((string)($hv['so_phong'] ?? '')) ?> — 6 kỳ gần nhất<?php if (!empty($hv['phong_id'])): ?><a class="hs-btn" href="ktx_dien_nuoc.php"><svg class="i"><use href="#hi-bolt"/></svg>Nhập chỉ số</a><?php endif; ?></div>
                <table class="hs-mini"><thead><tr><th>Kỳ</th><th class="r">Điện (kWh)</th><th class="r">Nước (m³)</th><th class="r">Tiền cả phòng</th><th>Đã thu</th></tr></thead><tbody>
                <?php foreach ($dnPhong as $dn): $kwh = ktx_tieu_thu_dien($dn); $m3 = ktx_tieu_thu_nuoc($dn); ?>
                  <tr><td><?= h(ktx_dn_nhan_ky((int)$dn['thang'], (bool)$dn['la_quy'], (int)$dn['nam'])) ?></td><td class="r"><?= h((string)$kwh) ?></td><td class="r"><?= h((string)$m3) ?></td>
                    <td class="r"><?= $tien($kwh * (float)$dn['don_gia_dien'] + $m3 * (float)$dn['don_gia_nuoc']) ?></td><td><?= (int)$dn['da_thu'] ? '<span class="hs-ok">Đã thu</span>' : '<span class="hs-no">Chưa thu</span>' ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$dnPhong): ?><tr><td colspan="5" class="hs-mut"><?= empty($hv['phong_id']) ? 'Chưa xếp phòng.' : 'Chưa có số liệu điện, nước.' ?></td></tr><?php endif; ?>
                </tbody></table></div>
            </div>
          </div>
        <?php endif; ?>
        </div>
      </div>
      <script>
      document.getElementById('hsTabs').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-t]'); if (!b) return;
        this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
        document.querySelectorAll('.hs-pane').forEach(function (p) { p.classList.toggle('on', p.dataset.p === b.dataset.t); });
      });
      </script>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
