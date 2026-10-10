<?php
// "Quản lý tài khoản Hội viên HCCB" (2026-09-23, mo rong 2026-09-23) — muc RIENG de Quan tri vien
// dat/doi Mat khau tra cuu, gop CHUNG 2 nguon du lieu hoi vien cua nhanh Hoi CCB:
//   - "ket_nap": bang hccb_ho_so_ket_nap (ho so qua quy trinh Ket nap hoi vien, xem hccb_ket_nap.php)
//   - "can_bo" : bang ktx_hccb_can_bo (danh sach hoi vien/Ban Chap hanh chinh thuc, xem
//                ktx_hccb_can_bo.php — "Danh sách HCCB")
// Tai khoan dang nhap = Ma so (mssv/ma_so_can_bo o nguon ket_nap, ma_so_cb o nguon can_bo) + Mat
// khau (xem public/hccb_tra_cuu_dangnhap.php) — trang nay CHI quan ly Ma so + Mat khau, khong dong
// cham cac truong ly lich khac cua 2 bang tren.
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh Hoi CCB (includes/hccb_khung.php), the so lieu dem tren
// toan bo 2 bang, loc nhanh Da cap / Chua cap / Thieu ma so + theo nguon, nut "= Ma so" dien nhanh mat
// khau. Xu ly dat / xoa / cap dong loat mat khau GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'dat_mat_khau') {
    $id = (int)($_POST['id'] ?? 0);
    $nguon = ($_POST['nguon'] ?? '') === 'can_bo' ? 'can_bo' : 'ket_nap';
    $bang = $nguon === 'can_bo' ? 'ktx_hccb_can_bo' : 'hccb_ho_so_ket_nap';
    $matKhau = trim($_POST['mat_khau'] ?? '');
    if ($id && $matKhau !== '') {
        $pdo->prepare("UPDATE $bang SET mat_khau_hash = ? WHERE id = ?")
            ->execute([password_hash($matKhau, PASSWORD_DEFAULT), $id]);
    }
    header('Location: hccb_tai_khoan.php?saved=1&tim=' . urlencode($_GET['tim'] ?? $_POST['tim'] ?? '')); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'xoa_mat_khau') {
    $id = (int)($_POST['id'] ?? 0);
    $nguon = ($_POST['nguon'] ?? '') === 'can_bo' ? 'can_bo' : 'ket_nap';
    $bang = $nguon === 'can_bo' ? 'ktx_hccb_can_bo' : 'hccb_ho_so_ket_nap';
    if ($id) {
        $pdo->prepare("UPDATE $bang SET mat_khau_hash = NULL WHERE id = ?")->execute([$id]);
    }
    header('Location: hccb_tai_khoan.php?saved=1&tim=' . urlencode($_GET['tim'] ?? $_POST['tim'] ?? '')); exit;
}
// Cap tai khoan tra cuu DONG LOAT (2026-09-23) — mac dinh mat khau = chinh Ma so cua tung nguoi
// (moi nguoi 1 mat khau khac nhau trung Ma so cua ho, KHONG phai 1 mat khau chung cho tat ca), CHI
// ap dung cho ho so: (1) co san Ma so, VA (2) CHUA co mat khau — khong ghi de mat khau da cap truoc
// do, tranh vo tinh doi mat khau nguoi da dang dung. Gom ca 2 nguon (ket_nap + can_bo) trong 1 lan
// bam. set_time_limit rong ra vi co the phai bam hash (password_hash) cho hang tram ho so 1 luc.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cap_hang_loat') {
    @set_time_limit(180);
    $soLuongCap = 0;

    $stmtDsKn = $pdo->query("SELECT id, doi_tuong, mssv, ma_so_can_bo FROM hccb_ho_so_ket_nap
        WHERE mat_khau_hash IS NULL AND (NULLIF(TRIM(mssv), '') IS NOT NULL OR NULLIF(TRIM(ma_so_can_bo), '') IS NOT NULL)");
    $upKn = $pdo->prepare('UPDATE hccb_ho_so_ket_nap SET mat_khau_hash = ? WHERE id = ?');
    foreach ($stmtDsKn->fetchAll() as $row) {
        $laVcNld = ($row['doi_tuong'] ?? 'hv_sv') === 'vc_nld';
        $maSo = trim((string)($laVcNld ? $row['ma_so_can_bo'] : $row['mssv']));
        if ($maSo === '') { continue; }
        $upKn->execute([password_hash($maSo, PASSWORD_DEFAULT), $row['id']]);
        $soLuongCap++;
    }

    try {
        $stmtDsCb = $pdo->query("SELECT id, ma_so_cb FROM ktx_hccb_can_bo
            WHERE mat_khau_hash IS NULL AND NULLIF(TRIM(ma_so_cb), '') IS NOT NULL");
        $upCb = $pdo->prepare('UPDATE ktx_hccb_can_bo SET mat_khau_hash = ? WHERE id = ?');
        foreach ($stmtDsCb->fetchAll() as $row) {
            $maSo = trim((string)$row['ma_so_cb']);
            if ($maSo === '') { continue; }
            $upCb->execute([password_hash($maSo, PASSWORD_DEFAULT), $row['id']]);
            $soLuongCap++;
        }
    } catch (Throwable $e) { /* chua chay migration_2026_09_23_hccb_canbo_taikhoan.sql */ }

    header('Location: hccb_tai_khoan.php?cap_hang_loat=' . $soLuongCap); exit;
}

$tim = trim($_GET['tim'] ?? '');
$gioiHan = 500;

// Nguon 1: hccb_ho_so_ket_nap — gom vao 1 hinh dang chung de hien thi cung bang voi nguon 2.
$whereKn = ''; $paramsKn = [];
if ($tim !== '') { $whereKn = 'WHERE ho_ten LIKE ? OR mssv LIKE ? OR ma_so_can_bo LIKE ?'; $paramsKn = array_fill(0, 3, "%$tim%"); }
$stmtKn = $pdo->prepare("SELECT id, ho_ten, doi_tuong, mssv, ma_so_can_bo, trang_thai, mat_khau_hash FROM hccb_ho_so_ket_nap $whereKn ORDER BY ho_ten LIMIT $gioiHan");
$stmtKn->execute($paramsKn);
$list = [];
$catBot = false; // (2026-10-10) bao "chi hien 500 dau" khi MOT nguon cham gioi han — truoc day dieu kien >= 900 gan nhu khong bao gio dung
$dsKn = $stmtKn->fetchAll();
if (count($dsKn) >= $gioiHan) { $catBot = true; }
foreach ($dsKn as $r) {
    $laVcNld = ($r['doi_tuong'] ?? 'hv_sv') === 'vc_nld';
    $list[] = [
        'nguon' => 'ket_nap', 'id' => (int)$r['id'], 'ho_ten' => $r['ho_ten'],
        'nhan' => 'Kết nạp — ' . ($laVcNld ? 'VC-NLĐ' : 'HV/SV'),
        'ma_so' => $laVcNld ? $r['ma_so_can_bo'] : $r['mssv'],
        'trang_thai' => $r['trang_thai'] === 'da_ket_nap' ? ['Đã kết nạp', 'green'] : ['Chờ kết nạp', 'orange'],
        'mat_khau_hash' => $r['mat_khau_hash'],
    ];
}

// Nguon 2: ktx_hccb_can_bo — chi chay neu bang da co cot mat_khau_hash (da chay migration).
$coNguonCanBo = true;
try {
    $whereCb = ''; $paramsCb = [];
    if ($tim !== '') { $whereCb = 'WHERE ho_ten LIKE ? OR ma_so_cb LIKE ?'; $paramsCb = array_fill(0, 2, "%$tim%"); }
    $stmtCb = $pdo->prepare("SELECT id, ho_ten, ma_so_cb, loai_hoi_vien, trang_thai, mat_khau_hash FROM ktx_hccb_can_bo $whereCb ORDER BY ho_ten LIMIT $gioiHan");
    $stmtCb->execute($paramsCb);
    $dsCb = $stmtCb->fetchAll();
    if (count($dsCb) >= $gioiHan) { $catBot = true; }
    foreach ($dsCb as $r) {
        $list[] = [
            'nguon' => 'can_bo', 'id' => (int)$r['id'], 'ho_ten' => $r['ho_ten'],
            'nhan' => 'Danh sách HCCB — ' . (($r['loai_hoi_vien'] ?? 'can_bo') === 'sinh_vien' ? 'Sinh viên' : 'Cán bộ/VC-NLĐ'),
            'ma_so' => $r['ma_so_cb'],
            'trang_thai' => ($r['trang_thai'] ?? '') === 'ra_khoi_hoi' ? ['Đã ra khỏi Hội', 'gray'] : ['Đang hoạt động', 'green'],
            'mat_khau_hash' => $r['mat_khau_hash'] ?? null,
        ];
    }
} catch (Throwable $e) { $coNguonCanBo = false; /* chua chay migration_2026_09_23_hccb_canbo_taikhoan.sql */ }

usort($list, fn($a, $b) => strcmp($a['ho_ten'], $b['ho_ten']));

// The so lieu: dem tren TOAN BO 2 bang (khong phu thuoc o tim kiem / gioi han 500 dong)
$tk = ['tong' => 0, 'da_cap' => 0, 'cho_cap' => 0, 'thieu_ma' => 0, 'kn' => 0, 'cb' => 0];
$congDem = function (?array $d, string $khoa) use (&$tk) {
    if (!$d) { return; }
    $tk['tong'] += (int)$d['tong']; $tk[$khoa] += (int)$d['tong'];
    $tk['da_cap'] += (int)$d['da_cap']; $tk['cho_cap'] += (int)$d['cho_cap']; $tk['thieu_ma'] += (int)$d['thieu_ma'];
};
$maKn = "NULLIF(TRIM(CASE WHEN doi_tuong = 'vc_nld' THEN ma_so_can_bo ELSE mssv END), '')";
$congDem($pdo->query("SELECT COUNT(*) tong, SUM(mat_khau_hash IS NOT NULL) da_cap,
    SUM(mat_khau_hash IS NULL AND $maKn IS NOT NULL) cho_cap, SUM($maKn IS NULL) thieu_ma FROM hccb_ho_so_ket_nap")->fetch() ?: null, 'kn');
if ($coNguonCanBo) {
    try {
        $congDem($pdo->query("SELECT COUNT(*) tong, SUM(mat_khau_hash IS NOT NULL) da_cap,
            SUM(mat_khau_hash IS NULL AND NULLIF(TRIM(ma_so_cb), '') IS NOT NULL) cho_cap, SUM(NULLIF(TRIM(ma_so_cb), '') IS NULL) thieu_ma FROM ktx_hccb_can_bo")->fetch() ?: null, 'cb');
    } catch (Throwable $e) { }
}
$tyLe = $tk['tong'] ? round($tk['da_cap'] * 100 / $tk['tong']) : 0;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/hccb_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.tk-note{font-size:13px;color:var(--ink2);padding:10px 14px;border-bottom:1px solid var(--line2);background:#fbfcfe;line-height:1.55}
.tk-seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden;background:#fff}
.tk-seg button{border:0;background:none;font:inherit;font-size:12.5px;padding:0 11px;height:30px;color:var(--ink2);cursor:pointer;white-space:nowrap}
.tk-seg button+button{border-left:1px solid var(--line)}
.tk-seg button.on{background:var(--pri-soft);color:var(--pri);font-weight:700}
.tk-seg button b{font-weight:700;margin-left:3px}
td.tk-ten b{display:block}td.tk-ten small{color:var(--ink3);font-size:12px}
.tk-ma{font-family:ui-monospace,Consolas,monospace;font-size:13px}
.tk-pw{display:flex;gap:6px;align-items:center;margin:0}
.tk-pw input{height:28px;width:170px;border:1px solid var(--line);border-radius:6px;padding:0 8px;font:inherit;font-size:13px;min-width:0}
.tk-pw input:focus{outline:0;border-color:var(--pri);box-shadow:0 0 0 3px rgba(31,111,214,.15)}
.tk-x{display:inline;margin:0}
.tk-bar{height:6px;border-radius:9px;background:var(--line2);overflow:hidden;flex:1;align-self:center;margin-left:10px}
.tk-bar i{display:block;height:100%;background:var(--green)}
@media (max-width:760px){.tk-pw input{width:120px}}
</style>
<?php ob_start(); ?>
    <form class="hs-gs" method="get" action="hccb_tai_khoan.php"><input name="tim" value="<?= h($tim) ?>" placeholder="Tìm theo họ tên hoặc mã số…" autocomplete="off" oninput="tkLocTuDong(this.form)"><button type="submit" title="Tìm"><svg class="i"><use href="#hi-search"/></svg></button></form>
<?php $khungGiua = ob_get_clean(); ob_start(); ?>
      <a class="hs-tb" href="../public/hccb_tra_cuu_dangnhap.php" target="_blank"><svg class="i"><use href="#hi-link"/></svg>Trang đăng nhập tra cứu</a>
<?php $khungPhai = ob_get_clean(); ?>
<?php hccb_khung_mo('hccb_tai_khoan.php', 'TÀI KHOẢN TRA CỨU', 'Hội Cựu chiến binh · Tra cứu CCB-CQN', $khungGiua, $khungPhai); ?>

      <?php if (!empty($_GET['saved'])): ?><div class="hs-msg ok"><span>✅ Đã lưu.</span></div><?php endif; ?>
      <?php if (isset($_GET['cap_hang_loat'])): ?>
        <div class="hs-msg ok"><span>✅ Đã cấp tài khoản tra cứu đồng loạt cho <b><?= (int)$_GET['cap_hang_loat'] ?></b> hồ sơ (mật khẩu mặc định = Mã số của từng người) — những hồ sơ đã có mật khẩu từ trước hoặc chưa có Mã số không bị ảnh hưởng.</span></div>
      <?php endif; ?>
      <?php if (!$coNguonCanBo): ?><div class="hs-msg warn"><span>⚠️ Nguồn "Danh sách HCCB" chưa có cột mật khẩu — cần chạy <code>migration_2026_09_23_hccb_canbo_taikhoan.sql</code>.</span></div><?php endif; ?>

      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num"><?= $tk['tong'] ?></div><div class="lb">Tổng hồ sơ hội viên</div></div></div>
          <div class="sb"><div><span>Kết nạp hội viên</span><b><?= $tk['kn'] ?></b></div><div><span>Danh sách HCCB</span><b><?= $tk['cb'] ?></b></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-key"/></svg></div>
            <div><div class="num" style="color:var(--green)"><?= $tk['da_cap'] ?></div><div class="lb">Đã cấp tài khoản</div></div></div>
          <div class="sb"><span style="font-size:12.5px;color:var(--ink2)"><?= $tyLe ?>%</span><span class="tk-bar"><i style="width:<?= $tyLe ?>%"></i></span></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-clock"/></svg></div>
            <div><div class="num" style="color:var(--orange)"><?= $tk['cho_cap'] ?></div><div class="lb">Chưa cấp (đã có mã số)</div></div></div>
          <div class="sb"><div><span>Có thể cấp đồng loạt ngay</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--red-soft);color:var(--red)"><svg class="i"><use href="#hi-alert"/></svg></div>
            <div><div class="num" style="color:var(--red)"><?= $tk['thieu_ma'] ?></div><div class="lb">Chưa có mã số</div></div></div>
          <div class="sb"><div><span>Cần bổ sung ở hồ sơ gốc</span></div></div>
        </div>
      </div>

      <div class="hs-card">
        <div class="hs-tool">
          <h2>DANH SÁCH TÀI KHOẢN</h2>
          <form method="post" style="margin:0" onsubmit="return confirm('Cấp tài khoản tra cứu cho TẤT CẢ hồ sơ đang CHƯA có mật khẩu, dùng chính Mã số của từng người làm mật khẩu mặc định?\n\nCác hồ sơ đã có mật khẩu sẽ KHÔNG bị đổi. Tiếp tục?');">
            <input type="hidden" name="action" value="cap_hang_loat">
            <button type="submit" class="hs-btn pri<?= $tk['cho_cap'] ? '' : ' off' ?>"><svg class="i"><use href="#hi-key"/></svg>Cấp đồng loạt<?= $tk['cho_cap'] ? ' (' . $tk['cho_cap'] . ')' : '' ?></button>
          </form>
          <span class="hs-sp"></span>
          <div class="tk-seg" id="tkLoc">
            <button type="button" data-loc="" class="on">Tất cả</button><button type="button" data-loc="da">Đã cấp</button><button type="button" data-loc="chua">Chưa cấp</button><button type="button" data-loc="thieu">Thiếu mã số</button>
          </div>
          <div class="tk-seg" id="tkNguon">
            <button type="button" data-loc="" class="on">Mọi nguồn</button><button type="button" data-loc="ket_nap">Kết nạp</button><button type="button" data-loc="can_bo">DS HCCB</button>
          </div>
          <span style="font-size:12.5px;color:var(--ink2)"><b id="tkDem"><?= count($list) ?></b> hồ sơ<?= $tim !== '' ? ' khớp “' . h($tim) . '” · <a href="hccb_tai_khoan.php" style="color:var(--pri)">Xoá lọc</a>' : '' ?></span>
        </div>
        <div class="tk-note">Hội viên/Cựu quân nhân tự đăng nhập trang công khai <b>“Tra cứu CCB-CQN”</b> bằng chính <b>Mã số</b> (MSSV / Mã số cán bộ / Mã số HCCB tuỳ nguồn) và <b>Mật khẩu tra cứu</b> đặt ở đây. Trang này gộp cả 2 nguồn (Kết nạp hội viên + Danh sách HCCB) và không thay đổi thông tin lý lịch khác.</div>
        <div class="hs-tw">
          <table class="hs-grid">
            <thead><tr><th>Họ và tên / nguồn</th><th class="c">Mã số</th><th class="c">Trạng thái</th><th class="c">Tài khoản</th><th>Đặt / đổi mật khẩu</th><th></th></tr></thead>
            <tbody id="tkBody">
            <?php foreach ($list as $r): $coMk = !empty($r['mat_khau_hash']); $coMa = trim((string)$r['ma_so']) !== ''; ?>
            <tr data-q="<?= $coMk ? 'da' : ($coMa ? 'chua' : 'thieu') ?>" data-n="<?= h($r['nguon']) ?>">
              <td class="tk-ten"><b><?= h($r['ho_ten']) ?></b><small><?= h($r['nhan']) ?></small></td>
              <td class="c"><?= $coMa ? '<span class="tk-ma">' . h($r['ma_so']) . '</span>' : '<span class="hs-pill red">Chưa có mã số</span>' ?></td>
              <td class="c"><span class="hs-pill <?= $r['trang_thai'][1] ?>"><?= h($r['trang_thai'][0]) ?></span></td>
              <td class="c"><?= $coMk ? '<span class="hs-pill green"><svg class="i"><use href="#hi-key"/></svg>Đã cấp</span>' : '<span class="hs-pill orange">Chưa cấp</span>' ?></td>
              <td>
                <?php if (!$coMa): ?>
                  <span style="color:var(--ink3);font-size:12.5px">Nhập Mã số ở hồ sơ gốc trước khi cấp mật khẩu.</span>
                <?php else: ?>
                <form method="post" class="tk-pw">
                  <input type="hidden" name="action" value="dat_mat_khau">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="nguon" value="<?= h($r['nguon']) ?>">
                  <input type="hidden" name="tim" value="<?= h($tim) ?>">
                  <input type="text" name="mat_khau" required placeholder="<?= $coMk ? 'Mật khẩu mới…' : 'Đặt mật khẩu…' ?>" autocomplete="off">
                  <button type="button" class="hs-btn sm" title="Dùng Mã số làm mật khẩu" data-ma="<?= h($r['ma_so']) ?>" onclick="this.form.mat_khau.value=this.dataset.ma">= Mã số</button>
                  <button type="submit" class="hs-btn sm b"><svg class="i"><use href="#hi-save"/></svg>Lưu</button>
                </form>
                <?php endif; ?>
              </td>
              <td class="c" style="white-space:nowrap">
                <?php if ($coMk): ?>
                <form method="post" class="tk-x" onsubmit="return confirm('Xoá mật khẩu tra cứu của người này? Họ sẽ không đăng nhập được cho tới khi được cấp lại.');">
                  <input type="hidden" name="action" value="xoa_mat_khau">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="nguon" value="<?= h($r['nguon']) ?>">
                  <input type="hidden" name="tim" value="<?= h($tim) ?>">
                  <button type="submit" class="hs-btn sm r" title="Xoá mật khẩu"><svg class="i"><use href="#hi-trash"/></svg>Thu hồi</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$list): ?><tr><td colspan="6" class="hs-empty">Không tìm thấy hồ sơ nào.</td></tr><?php endif; ?>
            <tr id="tkRong" hidden><td colspan="6" class="hs-empty">Không có hồ sơ nào khớp bộ lọc.</td></tr>
            </tbody>
          </table>
        </div>
        <?php if ($catBot): ?><div class="tk-note" style="border-top:1px solid var(--line2)">Chỉ hiển thị <?= $gioiHan ?> kết quả đầu mỗi nguồn — thu hẹp tìm kiếm để xem chính xác hơn.</div><?php endif; ?>
      </div>
<?php ktx_khung_dong(); ?>
<script>
let tkLocTuDongTimer;
function tkLocTuDong(form) { clearTimeout(tkLocTuDongTimer); tkLocTuDongTimer = setTimeout(() => form.submit(), 600); }
(function () {
  var loc = '', nguon = '', dem = document.getElementById('tkDem'), rong = document.getElementById('tkRong');
  function ap() {
    var n = 0, rows = document.querySelectorAll('#tkBody tr[data-q]');
    rows.forEach(function (tr) { var ok = (!loc || tr.dataset.q === loc) && (!nguon || tr.dataset.n === nguon); tr.hidden = !ok; if (ok) n++; });
    dem.textContent = n; rong.hidden = n > 0 || !rows.length;
  }
  function seg(id, set) {
    var el = document.getElementById(id);
    el.addEventListener('click', function (ev) {
      var b = ev.target.closest('button'); if (!b) return;
      el.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      set(b.dataset.loc); ap();
    });
  }
  seg('tkLoc', function (v) { loc = v; }); seg('tkNguon', function (v) { nguon = v; });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
