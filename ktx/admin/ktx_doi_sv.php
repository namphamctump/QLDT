<?php
// Quan ly "Can su phong" VA "Doi Sinh vien tu quan" — dung CHUNG 1 trang, chuyen doi qua lai
// bang tham so ?loai= (2 gia tri: can_su_phong | doi_tu_quan). Moi thanh vien LIEN KET truc tiep
// toi 1 hoc_vien_id de luon lay dung day/phong/ma so/ho ten HIEN TAI (khong bi lech khi hoc vien
// doi phong) — CRUD + xuat danh sach Excel + xuat Quyet dinh thanh lap (Word) theo dung mau
// "QĐ Thành lập Đội tự quản tại KTX" (2026-09-14).
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh KTX (includes/ktx_khung.php), 2 tab dang nut co so
// dem, the so lieu theo chuc vu (+ day chua co Truong day / phong chua co Can su), form them/sua thu
// gon voi O CHON HOC VIEN LOC NGAY KHI GO (khong tai lai trang, khong mat cac o da nhap), "Giao bo
// sung" dang nut bat/tat. Xu ly luu/xoa, danh muc chuc vu va bang ktx_doi_sv_day_bo_sung GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

$loai = ($_GET['loai'] ?? 'doi_tu_quan') === 'can_su_phong' ? 'can_su_phong' : 'doi_tu_quan';
$LOAI_NHAN = ['can_su_phong' => 'Cán sự phòng', 'doi_tu_quan' => 'Đội Sinh viên tự quản'];

// Danh muc CHUC VU de bo nhiem cho Doi Sinh vien tu quan (2026-09-20) — thay cho o nhap tu do
// truoc day (tung co cac gia tri le nhu "Trưởng dãy A", "Đội trưởng - Trưởng dãy A"...). Day CHI
// la nhan hien thi — dãy phu trach da hien rieng o cot "Dãy" (lay tu phong o cua hoc vien), nen
// khong can nhet chu cai day vao ten chuc vu nua, TRU 4 chuc danh "Đội trưởng - Trưởng dãy X"
// (2026-09-20, bo sung theo yeu cau) danh rieng cho truong hop 1 Đội trưởng dong thoi la Trưởng
// dãy chinh thuc cua dung 1 day cu the — dung cho van ban/quyet dinh chinh thuc can ghi ro. Bat ky
// chuc vu nao trong Doi Sinh vien tu quan (ke ca "Thành viên") DEU tu dong duoc quyen ghi chi so
// dien nuoc cho day minh dang o — xem public/ktx_dien_nuoc_nhap.php ($laDoiTuQuan). Rieng cac chuc
// vu chua "Đội trưởng" duoc quyen ca 4 day (nhan dien qua chuoi con "đội trưởng" trong chuc_vu).
$CHUC_VU_DOI_TU_QUAN = [
    'Đội trưởng',
    'Đội trưởng - Trưởng dãy A',
    'Đội trưởng - Trưởng dãy B',
    'Đội trưởng - Trưởng dãy C',
    'Đội trưởng - Trưởng dãy D',
    'Trưởng dãy',
    'Phó dãy',
    'Thành viên',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'luu') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $loaiPost = ($_POST['loai'] ?? 'doi_tu_quan') === 'can_su_phong' ? 'can_su_phong' : 'doi_tu_quan';
    $hocVienId = !empty($_POST['hoc_vien_id']) ? (int)$_POST['hoc_vien_id'] : 0;
    $chucVu = trim($_POST['chuc_vu'] ?? '') ?: 'Thành viên';
    $namHoc = trim($_POST['nam_hoc'] ?? '') ?: null;
    $thuTu = (int)($_POST['thu_tu'] ?? 0);
    $ghiChu = trim($_POST['ghi_chu'] ?? '') ?: null;
    if ($hocVienId) {
        if ($id) {
            $pdo->prepare('UPDATE ktx_doi_sv SET hoc_vien_id=?, chuc_vu=?, nam_hoc=?, thu_tu=?, ghi_chu=? WHERE id=?')
                ->execute([$hocVienId, $chucVu, $namHoc, $thuTu, $ghiChu, $id]);
            $doiSvId = $id;
        } else {
            $pdo->prepare('INSERT INTO ktx_doi_sv (loai, hoc_vien_id, chuc_vu, nam_hoc, thu_tu, ghi_chu) VALUES (?,?,?,?,?,?)')
                ->execute([$loaiPost, $hocVienId, $chucVu, $namHoc, $thuTu, $ghiChu]);
            $doiSvId = (int)$pdo->lastInsertId();
        }
        // Giao BO SUNG day khac day dang o (chi ap dung Doi Sinh vien tu quan, 2026-09-20) — luu
        // toan bo lai (xoa het roi them lai theo danh sach checkbox da chon) cho don gian.
        if ($loaiPost === 'doi_tu_quan') {
            try {
                $pdo->prepare('DELETE FROM ktx_doi_sv_day_bo_sung WHERE doi_sv_id = ?')->execute([$doiSvId]);
                $dayBoSungChon = array_unique(array_map('intval', $_POST['day_bo_sung'] ?? []));
                if ($dayBoSungChon) {
                    $insBoSung = $pdo->prepare('INSERT IGNORE INTO ktx_doi_sv_day_bo_sung (doi_sv_id, day_id) VALUES (?,?)');
                    foreach ($dayBoSungChon as $dbs) { $insBoSung->execute([$doiSvId, $dbs]); }
                }
            } catch (Throwable $e) { /* bang chua ton tai — chua chay migration */ }
        }
    }
    header('Location: ktx_doi_sv.php?loai=' . $loaiPost . '&saved=1'); exit;
}
if (($_GET['action'] ?? '') === 'xoa' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM ktx_doi_sv WHERE id = ?')->execute([(int)$_GET['id']]);
    header('Location: ktx_doi_sv.php?loai=' . $loai); exit;
}

$list = $pdo->prepare("SELECT ds.*, hv.ma_so, hv.ho_ten, hv.trang_thai AS hv_trang_thai, p.so_phong, d.ten AS ten_day, d.id AS day_id, d.gioi_tinh AS day_gioi_tinh
    FROM ktx_doi_sv ds
    JOIN ktx_hoc_vien hv ON hv.id = ds.hoc_vien_id
    LEFT JOIN ktx_phong p ON p.id = hv.phong_id
    LEFT JOIN ktx_day d ON d.id = p.day_id
    WHERE ds.loai = ?
    ORDER BY ds.thu_tu, d.ten, p.so_phong, hv.ho_ten");
$list->execute([$loai]);
$list = $list->fetchAll();

// Day duoc giao BO SUNG cho tung thanh vien (chi co y nghia voi doi_tu_quan) — nap 1 lan, gom
// theo doi_sv_id, de hien cot "Dãy bổ sung" va tick san khi sua (2026-09-20).
$dayBoSungTheoThanhVien = [];
if ($loai === 'doi_tu_quan' && $list) {
    try {
        $ids = array_column($list, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmtBs = $pdo->prepare("SELECT b.doi_sv_id, d.id AS day_id, d.ten FROM ktx_doi_sv_day_bo_sung b
            JOIN ktx_day d ON d.id = b.day_id WHERE b.doi_sv_id IN ($in) ORDER BY d.ten");
        $stmtBs->execute($ids);
        foreach ($stmtBs->fetchAll() as $bs) { $dayBoSungTheoThanhVien[(int)$bs['doi_sv_id']][] = $bs; }
    } catch (Throwable $e) { /* bang chua ton tai — chua chay migration */ }
}

$editId = !empty($_GET['sua']) ? (int)$_GET['sua'] : 0;
$editRow = null;
if ($editId) { foreach ($list as $r) { if ((int)$r['id'] === $editId) { $editRow = $r; break; } } }
$editDayBoSungIds = $editId ? array_map('intval', array_column($dayBoSungTheoThanhVien[$editId] ?? [], 'day_id')) : [];
$dayListChon = $pdo->query('SELECT id, ten, gioi_tinh FROM ktx_day ORDER BY ten')->fetchAll();

// Danh sach hoc vien DANG O de chon vao form (loc ngay tren trinh duyet, khong tai lai trang).
// Giu ?tim= cu: neu co thi dien san vao o tim.
$timKiem = trim($_GET['tim'] ?? '');
$hvChon = $pdo->query("SELECT hv.id, hv.ma_so, hv.ho_ten, p.so_phong, d.ten AS ten_day
    FROM ktx_hoc_vien hv LEFT JOIN ktx_phong p ON p.id = hv.phong_id LEFT JOIN ktx_day d ON d.id = p.day_id
    WHERE hv.trang_thai = 'dang_o' ORDER BY hv.ho_ten")->fetchAll();
$hvDangSua = null;
if ($editRow) {
    $stmtHv = $pdo->prepare("SELECT hv.id, hv.ma_so, hv.ho_ten, p.so_phong, d.ten AS ten_day
        FROM ktx_hoc_vien hv LEFT JOIN ktx_phong p ON p.id = hv.phong_id LEFT JOIN ktx_day d ON d.id = p.day_id WHERE hv.id = ?");
    $stmtHv->execute([$editRow['hoc_vien_id']]);
    $hvDangSua = $stmtHv->fetch();
}
$daCoTrongDanhSach = array_map('intval', array_column($list, 'hoc_vien_id'));

// ---- So lieu cho the ----
$demLoai = ['can_su_phong' => 0, 'doi_tu_quan' => 0];
foreach ($pdo->query('SELECT loai, COUNT(*) n FROM ktx_doi_sv GROUP BY loai')->fetchAll() as $r) { $demLoai[$r['loai']] = (int)$r['n']; }
$laDoiTruong = fn(string $cv) => mb_stripos($cv, 'đội trưởng') !== false;
$demCv = ['doi_truong' => 0, 'truong_day' => 0, 'pho_day' => 0, 'thanh_vien' => 0];
$dayCoTruong = [];
foreach ($list as $r) {
    $cv = (string)$r['chuc_vu'];
    if ($laDoiTruong($cv)) { $demCv['doi_truong']++; }
    elseif (mb_stripos($cv, 'trưởng dãy') !== false) { $demCv['truong_day']++; }
    elseif (mb_stripos($cv, 'phó dãy') !== false) { $demCv['pho_day']++; }
    else { $demCv['thanh_vien']++; }
    // Day da co Truong day: "Trưởng dãy" (theo day dang o) hoac "Đội trưởng - Trưởng dãy X" (theo chu cai X)
    if (preg_match('/trưởng dãy\s+(\S+)\s*$/iu', $cv, $m)) {
        foreach ($dayListChon as $dl) { if (preg_match('/\s' . preg_quote($m[1], '/') . '$/iu', $dl['ten'])) { $dayCoTruong[(int)$dl['id']] = true; } }
    } elseif (mb_stripos($cv, 'trưởng dãy') !== false && $r['day_id']) { $dayCoTruong[(int)$r['day_id']] = true; }
}
$dayChuaCoTruong = array_values(array_filter($dayListChon, fn($dl) => empty($dayCoTruong[(int)$dl['id']])));
$phongChuaCoCanSu = 0;
if ($loai === 'can_su_phong') {
    $phongChuaCoCanSu = (int)$pdo->query("SELECT COUNT(*) FROM ktx_phong p
        WHERE EXISTS (SELECT 1 FROM ktx_hoc_vien hv WHERE hv.phong_id = p.id AND hv.trang_thai = 'dang_o')
        AND NOT EXISTS (SELECT 1 FROM ktx_doi_sv ds JOIN ktx_hoc_vien h2 ON h2.id = ds.hoc_vien_id WHERE ds.loai = 'can_su_phong' AND h2.phong_id = p.id)")->fetchColumn();
}
$pillCv = function (string $cv) use ($laDoiTruong): string {
    if ($laDoiTruong($cv)) return 'red';
    if (mb_stripos($cv, 'trưởng dãy') !== false) return 'blue';
    if (mb_stripos($cv, 'phó dãy') !== false) return 'green';
    return 'gray';
};
$moForm = $editRow || $timKiem !== '' || !$list;
$GT_MAU = ['Nam' => 'var(--nam)', 'Nữ' => 'var(--nu)'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/ktx_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.ds-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.ds-tabs a{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 16px;border-radius:10px;border:1px solid var(--line);background:#fff;text-decoration:none;color:var(--ink);font-weight:600}
.ds-tabs a:hover{border-color:var(--pri-line)}
.ds-tabs a.on{background:var(--pri);border-color:var(--pri);color:#fff;box-shadow:0 2px 6px rgba(31,111,214,.3)}
.ds-tabs .n{min-width:22px;height:20px;padding:0 6px;border-radius:10px;background:var(--pri-soft);color:var(--pri);font-size:12px;display:grid;place-items:center}
.ds-tabs a.on .n{background:#fff}
.ds-form{padding:4px 14px 14px;border-bottom:1px solid var(--line);background:#fbfcfe}
.ds-form[hidden]{display:none}
.ds-form h3{margin:12px 0 10px;font-size:13px;color:var(--navy);text-transform:uppercase;letter-spacing:.3px}
.ds-form .hs-fg{grid-template-columns:minmax(0,1.6fr) minmax(0,1fr) 130px 90px}
.ds-form .act{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
.ds-pick{position:relative}
.ds-pick .chosen{display:flex;align-items:center;gap:8px;min-height:34px;padding:4px 10px;border:1px solid var(--pri-line);border-radius:7px;background:var(--pri-soft);font-weight:400;color:var(--ink)}
.ds-pick .chosen b{color:var(--navy)}
.ds-pick .chosen button{margin-left:auto;border:0;background:none;color:var(--pri);cursor:pointer;font:inherit;font-size:12.5px}
.ds-pick ul{list-style:none;margin:4px 0 0;padding:4px;max-height:240px;overflow:auto;border:1px solid var(--line);border-radius:8px;background:#fff;box-shadow:0 8px 20px rgba(16,42,84,.12);position:absolute;left:0;right:0;z-index:5}
.ds-pick ul[hidden]{display:none}
.ds-pick li{padding:7px 10px;border-radius:6px;cursor:pointer;font-weight:400;font-size:13px;color:var(--ink);display:flex;gap:8px;align-items:center}
.ds-pick li:hover,.ds-pick li.act{background:var(--pri-soft)}
.ds-pick li small{color:var(--ink3);margin-left:auto;white-space:nowrap}
.ds-pick li.da{opacity:.55}
.ds-bs{margin-top:12px;padding-top:10px;border-top:1px dashed var(--line)}
.ds-bs .ttl{font-size:12px;color:var(--ink2);font-weight:600;margin-bottom:6px}
.ds-tg{display:flex;gap:6px;flex-wrap:wrap}
.ds-tg label{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border:1px solid var(--line);border-radius:999px;background:#fff;cursor:pointer;font-size:13px;font-weight:400;color:var(--ink2)}
.ds-tg input{position:absolute;opacity:0;pointer-events:none}
.ds-tg label:has(input:checked){background:var(--pri);border-color:var(--pri);color:#fff}
.ds-tg label:has(input:focus-visible){outline:2px solid var(--pri);outline-offset:2px}
.ds-bs p{margin:6px 0 0;font-size:12px;color:var(--ink3)}
.ds-chip{display:inline-flex;align-items:center;height:22px;padding:0 8px;border-radius:999px;background:#eef1f5;color:var(--ink2);font-size:12px;margin:1px 2px 1px 0;white-space:nowrap}
.ds-chip.all{background:#fdeaea;color:var(--red)}
.ds-act{white-space:nowrap;text-align:right}
table.hs-grid td a.nm{color:var(--ink);text-decoration:none;font-weight:600}table.hs-grid td a.nm:hover{color:var(--pri)}
@media (max-width:1100px){.ds-form .hs-fg{grid-template-columns:1fr 1fr}}
@media (max-width:640px){.ds-form .hs-fg{grid-template-columns:1fr}}
</style>
<?php ob_start(); ?>
      <a class="hs-tb" href="ktx_doi_sv_export.php?loai=<?= $loai ?>&amp;dinh_dang=excel"><svg class="i"><use href="#hi-down"/></svg>Xuất danh sách (Excel)</a>
      <a class="hs-tb" href="ktx_doi_sv_export.php?loai=<?= $loai ?>&amp;dinh_dang=word"><svg class="i"><use href="#hi-file"/></svg>Xuất Quyết định thành lập (Word)</a>
<?php $khungPhai = ob_get_clean(); ?>
<?php ktx_khung_mo('ktx_doi_sv.php', mb_strtoupper($LOAI_NHAN[$loai]), 'Cán sự phòng · Đội Sinh viên tự quản', '', $khungPhai); ?>

      <?php if (!empty($_GET['saved'])): ?><div class="hs-msg ok"><span>✅ Đã lưu.</span></div><?php endif; ?>

      <div class="ds-tabs" role="tablist">
        <a href="?loai=doi_tu_quan" class="<?= $loai === 'doi_tu_quan' ? 'on' : '' ?>" role="tab"<?= $loai === 'doi_tu_quan' ? ' aria-selected="true"' : '' ?>><svg class="i"><use href="#hi-star"/></svg>Đội Sinh viên tự quản<span class="n"><?= $demLoai['doi_tu_quan'] ?></span></a>
        <a href="?loai=can_su_phong" class="<?= $loai === 'can_su_phong' ? 'on' : '' ?>" role="tab"<?= $loai === 'can_su_phong' ? ' aria-selected="true"' : '' ?>><svg class="i"><use href="#hi-door"/></svg>Cán sự phòng<span class="n"><?= $demLoai['can_su_phong'] ?></span></a>
      </div>

      <div class="hs-kpis">
      <?php if ($loai === 'doi_tu_quan'): ?>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num"><?= count($list) ?></div><div class="lb">Thành viên Đội</div></div></div>
          <div class="sb"><div><span>Mọi thành viên đều được ghi chỉ số điện, nước ở dãy mình đang ở</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--red-soft);color:var(--red)"><svg class="i"><use href="#hi-star"/></svg></div>
            <div><div class="num" style="color:var(--red)"><?= $demCv['doi_truong'] ?></div><div class="lb">Đội trưởng</div></div></div>
          <div class="sb"><div><span><?= $demCv['doi_truong'] ? 'Được ghi chỉ số tất cả các dãy' : '<span class="hs-no">Chưa có Đội trưởng</span>' ?></span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-home"/></svg></div>
            <div><div class="num"><?= $demCv['truong_day'] + $demCv['pho_day'] ?></div><div class="lb">Trưởng / Phó dãy</div></div></div>
          <div class="sb"><div><b style="color:var(--pri)"><?= $demCv['truong_day'] ?></b><span>Trưởng dãy</span></div><div><b style="color:var(--green)"><?= $demCv['pho_day'] ?></b><span>Phó dãy</span></div><div><b><?= $demCv['thanh_vien'] ?></b><span>Thành viên</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:<?= $dayChuaCoTruong ? 'var(--orange-soft);color:var(--orange)' : 'var(--green-soft);color:var(--green)' ?>"><svg class="i"><use href="#hi-alert"/></svg></div>
            <div><div class="num" style="color:<?= $dayChuaCoTruong ? 'var(--orange)' : 'var(--green)' ?>"><?= count($dayChuaCoTruong) ?></div><div class="lb">Dãy chưa có Trưởng dãy</div></div></div>
          <div class="sb"><div><span><?= $dayChuaCoTruong ? h(implode(', ', array_column($dayChuaCoTruong, 'ten'))) : '✓ Dãy nào cũng đã có Trưởng dãy' ?></span></div></div>
        </div>
      <?php else: ?>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num"><?= count($list) ?></div><div class="lb">Cán sự phòng</div></div></div>
          <div class="sb"><div><span>Liên kết trực tiếp hồ sơ học viên — luôn đúng phòng hiện tại</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-door"/></svg></div>
            <div><div class="num"><?= count(array_unique(array_filter(array_map(fn($r) => $r['so_phong'] ? $r['ten_day'] . $r['so_phong'] : null, $list)))) ?></div><div class="lb">Phòng đã có Cán sự</div></div></div>
          <div class="sb"><div><span>Tính theo phòng đang ở của Cán sự</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:<?= $phongChuaCoCanSu ? 'var(--orange-soft);color:var(--orange)' : 'var(--green-soft);color:var(--green)' ?>"><svg class="i"><use href="#hi-alert"/></svg></div>
            <div><div class="num" style="color:<?= $phongChuaCoCanSu ? 'var(--orange)' : 'var(--green)' ?>"><?= $phongChuaCoCanSu ?></div><div class="lb">Phòng có người ở chưa có Cán sự</div></div></div>
          <div class="sb"><div><a href="ktx_so_do_phong.php" style="font-size:12.5px;color:var(--pri)">Xem sơ đồ phòng →</a></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-star"/></svg></div>
            <div><div class="num"><?= $demLoai['doi_tu_quan'] ?></div><div class="lb">Thành viên Đội SV tự quản</div></div></div>
          <div class="sb"><div><a href="?loai=doi_tu_quan" style="font-size:12.5px;color:var(--pri)">Mở danh sách Đội →</a></div></div>
        </div>
      <?php endif; ?>
      </div>

      <div class="hs-card">
        <div class="hs-tool">
          <h2>DANH SÁCH <?= mb_strtoupper($LOAI_NHAN[$loai]) ?></h2>
          <button type="button" class="hs-btn pri" id="dsMoForm"><svg class="i"><use href="#hi-plus"/></svg>Thêm thành viên</button>
          <span class="hs-sp"></span><span style="font-size:12.5px;color:var(--ink2)"><?= count($list) ?> người</span>
        </div>

        <form method="post" class="ds-form" id="dsForm"<?= $moForm ? '' : ' hidden' ?>>
          <input type="hidden" name="action" value="luu">
          <input type="hidden" name="id" value="<?= $editRow ? (int)$editRow['id'] : '' ?>">
          <input type="hidden" name="loai" value="<?= $loai ?>">
          <input type="hidden" name="hoc_vien_id" id="dsHvId" value="<?= $hvDangSua ? (int)$hvDangSua['id'] : '' ?>">
          <h3><?= $editRow ? 'Sửa thành viên: ' . h($editRow['ho_ten']) : 'Thêm thành viên — ' . h($LOAI_NHAN[$loai]) ?></h3>
          <div class="hs-fg">
            <label>Học viên * <span class="hint">gõ tên hoặc mã số để chọn (chỉ người đang ở)</span>
              <div class="ds-pick">
                <div class="chosen" id="dsChosen"<?= $hvDangSua ? '' : ' hidden' ?>><span id="dsChosenTxt"><?= $hvDangSua ? '<b>' . h($hvDangSua['ho_ten']) . '</b> — ' . h((string)$hvDangSua['ma_so']) . ' (' . h($hvDangSua['ten_day'] ? $hvDangSua['ten_day'] . ' ' . $hvDangSua['so_phong'] : 'chưa xếp phòng') . ')' : '' ?></span><button type="button" id="dsDoi">Đổi</button></div>
                <input type="text" id="dsTim" value="<?= h($timKiem) ?>" placeholder="VD: Khánh hoặc 2253010034" autocomplete="off"<?= $hvDangSua ? ' hidden' : '' ?> role="combobox" aria-expanded="false" aria-controls="dsKq">
                <ul id="dsKq" role="listbox" hidden></ul>
              </div>
            </label>
            <label>Chức vụ
              <?php if ($loai === 'doi_tu_quan'): $chucVuHienTai = $editRow['chuc_vu'] ?? 'Thành viên'; ?>
              <select name="chuc_vu">
                <?php foreach ($CHUC_VU_DOI_TU_QUAN as $cv): ?><option value="<?= h($cv) ?>" <?= $chucVuHienTai === $cv ? 'selected' : '' ?>><?= h($cv) ?></option><?php endforeach; ?>
                <?php if (!in_array($chucVuHienTai, $CHUC_VU_DOI_TU_QUAN, true)): ?><option value="<?= h($chucVuHienTai) ?>" selected><?= h($chucVuHienTai) ?> (giá trị cũ, chọn lại để chuẩn hoá)</option><?php endif; ?>
              </select>
              <?php else: ?>
              <input type="text" name="chuc_vu" value="<?= h($editRow['chuc_vu'] ?? 'Cán sự phòng') ?>" placeholder="VD: Cán sự phòng">
              <?php endif; ?>
            </label>
            <label>Năm học<input type="text" name="nam_hoc" value="<?= h($editRow['nam_hoc'] ?? '') ?>" placeholder="VD: 2025 - 2026"></label>
            <label>Thứ tự<input type="number" name="thu_tu" value="<?= h((string)($editRow['thu_tu'] ?? '0')) ?>"></label>
            <label class="s3">Ghi chú<input type="text" name="ghi_chu" value="<?= h($editRow['ghi_chu'] ?? '') ?>"></label>
          </div>
          <?php if ($loai === 'doi_tu_quan'): ?>
          <div class="ds-bs">
            <div class="ttl">Giao bổ sung — được ghi chỉ số điện, nước ở dãy KHÁC dãy đang ở</div>
            <div class="ds-tg">
              <?php foreach ($dayListChon as $dl): ?><label><input type="checkbox" name="day_bo_sung[]" value="<?= (int)$dl['id'] ?>" <?= in_array((int)$dl['id'], $editDayBoSungIds, true) ? 'checked' : '' ?>><?= h($dl['ten']) ?></label><?php endforeach; ?>
            </div>
            <p>Không cần chọn nếu chức vụ là “Đội trưởng” (đã mặc định được tất cả các dãy). Dãy đang ở của học viên tự động được phép.</p>
          </div>
          <?php endif; ?>
          <div class="act">
            <?php if ($editRow): ?><a class="hs-btn" href="ktx_doi_sv.php?loai=<?= $loai ?>">Huỷ sửa</a><?php else: ?><button type="button" class="hs-btn" id="dsDongForm">Đóng</button><?php endif; ?>
            <button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg><?= $editRow ? 'Lưu thay đổi' : 'Thêm' ?></button>
          </div>
        </form>

        <div class="hs-tw">
          <table class="hs-grid">
            <thead><tr><th class="c" style="width:50px">STT</th><th>Dãy</th><th class="c">Phòng</th><th>Họ và tên</th><th>Chức vụ</th><th class="c">Năm học</th><?php if ($loai === 'doi_tu_quan'): ?><th>Được ghi chỉ số dãy</th><?php endif; ?><th>Ghi chú</th><th></th></tr></thead>
            <tbody>
            <?php $stt = 0; foreach ($list as $r): $stt++; $bs = $dayBoSungTheoThanhVien[(int)$r['id']] ?? []; ?>
            <tr<?= $editId === (int)$r['id'] ? ' style="background:#d9e8fb"' : '' ?>>
              <td class="c"><?= $stt ?></td>
              <td style="white-space:nowrap"><?php if ($r['ten_day']): ?><span class="hs-dot" style="background:<?= $GT_MAU[$r['day_gioi_tinh']] ?? '#98a4b5' ?>"></span> <?= h($r['ten_day']) ?><?php else: ?><span class="hs-no"><?= $r['hv_trang_thai'] === 'dang_o' ? 'Chưa xếp phòng' : 'Đã rời KTX' ?></span><?php endif; ?></td>
              <td class="c"><?= $r['so_phong'] ? h($r['so_phong']) : '<span class="hs-mut">—</span>' ?></td>
              <td><a class="nm" href="ktx_hoc_vien.php?id=<?= (int)$r['hoc_vien_id'] ?>#ho-so"><?= h($r['ho_ten']) ?></a><br><small class="hs-mut"><?= h((string)$r['ma_so']) ?></small></td>
              <td><span class="hs-pill <?= $pillCv((string)$r['chuc_vu']) ?>"><?= h($r['chuc_vu']) ?></span></td>
              <td class="c"><?= $r['nam_hoc'] ? h($r['nam_hoc']) : '<span class="hs-mut">—</span>' ?></td>
              <?php if ($loai === 'doi_tu_quan'): ?>
              <td><?php if ($laDoiTruong((string)$r['chuc_vu'])): ?><span class="ds-chip all">Tất cả các dãy</span><?php else: ?><?php if ($r['ten_day']): ?><span class="ds-chip"><?= h($r['ten_day']) ?></span><?php endif; ?><?php foreach ($bs as $b): if ((int)$b['day_id'] === (int)$r['day_id']) continue; ?><span class="ds-chip" style="background:var(--pri-soft);color:var(--pri)" title="Giao bổ sung">+ <?= h($b['ten']) ?></span><?php endforeach; ?><?php endif; ?></td>
              <?php endif; ?>
              <td><?= $r['ghi_chu'] ? h($r['ghi_chu']) : '<span class="hs-mut">—</span>' ?></td>
              <td class="ds-act">
                <a class="hs-btn sm" href="?loai=<?= $loai ?>&amp;sua=<?= (int)$r['id'] ?>"><svg class="i"><use href="#hi-edit"/></svg>Sửa</a>
                <a class="hs-btn sm r" href="?loai=<?= $loai ?>&amp;action=xoa&amp;id=<?= (int)$r['id'] ?>" onclick="return confirm('Xoá thành viên này?')"><svg class="i"><use href="#hi-trash"/></svg>Xoá</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$list): ?><tr><td colspan="<?= $loai === 'doi_tu_quan' ? 9 : 8 ?>" class="hs-empty">Chưa có thành viên nào — bấm <b>Thêm thành viên</b>.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php ktx_khung_dong(); ?>
<script>
(function () {
  var HV = <?= json_encode(array_map(fn($x) => [(int)$x['id'], (string)$x['ho_ten'], (string)$x['ma_so'], $x['ten_day'] ? $x['ten_day'] . ' ' . $x['so_phong'] : 'chưa xếp phòng'], $hvChon), JSON_UNESCAPED_UNICODE) ?>;
  var DA = <?= json_encode($daCoTrongDanhSach) ?>;
  var form = document.getElementById('dsForm'), tim = document.getElementById('dsTim'), kq = document.getElementById('dsKq');
  var hid = document.getElementById('dsHvId'), chosen = document.getElementById('dsChosen'), chosenTxt = document.getElementById('dsChosenTxt');
  var idx = -1;
  function norm(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase(); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function ve() {
    var t = norm(tim.value.trim()); idx = -1;
    if (!t) { kq.hidden = true; tim.setAttribute('aria-expanded', 'false'); return; }
    var ds = HV.filter(function (h) { return norm(h[1] + ' ' + h[2]).indexOf(t) >= 0; }).slice(0, 30);
    kq.innerHTML = ds.length ? ds.map(function (h) {
      var da = DA.indexOf(h[0]) >= 0;
      return '<li role="option" data-id="' + h[0] + '" class="' + (da ? 'da' : '') + '"><b>' + esc(h[1]) + '</b> ' + esc(h[2]) + '<small>' + esc(h[3]) + (da ? ' · đã có trong danh sách' : '') + '</small></li>';
    }).join('') : '<li style="cursor:default;color:var(--ink3)">Không tìm thấy học viên đang ở phù hợp.</li>';
    kq.hidden = false; tim.setAttribute('aria-expanded', 'true');
  }
  function chon(id) {
    var h = HV.filter(function (x) { return x[0] === id; })[0]; if (!h) return;
    hid.value = h[0]; chosenTxt.innerHTML = '<b>' + esc(h[1]) + '</b> — ' + esc(h[2]) + ' (' + esc(h[3]) + ')';
    chosen.hidden = false; tim.hidden = true; kq.hidden = true;
  }
  tim.addEventListener('input', ve);
  tim.addEventListener('focus', ve);
  tim.addEventListener('keydown', function (e) {
    var li = kq.querySelectorAll('li[data-id]');
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); if (!li.length) return; idx = (idx + (e.key === 'ArrowDown' ? 1 : -1) + li.length) % li.length; li.forEach(function (x, i) { x.classList.toggle('act', i === idx); }); li[idx].scrollIntoView({ block: 'nearest' }); }
    else if (e.key === 'Enter') { e.preventDefault(); if (idx >= 0 && li[idx]) chon(+li[idx].dataset.id); else if (li.length === 1) chon(+li[0].dataset.id); }
    else if (e.key === 'Escape') { kq.hidden = true; }
  });
  kq.addEventListener('mousedown', function (e) { var li = e.target.closest('li[data-id]'); if (li) { e.preventDefault(); chon(+li.dataset.id); } });
  tim.addEventListener('blur', function () { setTimeout(function () { kq.hidden = true; }, 150); });
  document.getElementById('dsDoi').addEventListener('click', function () { chosen.hidden = true; tim.hidden = false; tim.value = ''; tim.focus(); });
  form.addEventListener('submit', function (e) { if (!hid.value) { e.preventDefault(); alert('Vui lòng gõ tên hoặc mã số rồi chọn 1 học viên.'); tim.hidden = false; chosen.hidden = true; tim.focus(); } });
  document.getElementById('dsMoForm').addEventListener('click', function () { form.hidden = false; (tim.hidden ? form.querySelector('[name=chuc_vu]') : tim).focus(); });
  var dong = document.getElementById('dsDongForm'); if (dong) dong.addEventListener('click', function () { form.hidden = true; });
  if (tim.value && !hid.value) { ve(); }
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
