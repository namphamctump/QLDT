<?php
// Quan ly cac DOT DIEM DANH cho hoc vien noi tru KTX (VD "Tập huấn PCCC", hop dan cu, sinh hoat
// dau khoa...) — moi dot la 1 su kien rieng, tach biet hoan toan voi "Dot khai bao hong hoc"
// (ktx_sua_chua.php > Quản lý đợt khai báo). Bam vao 1 dot de qua trang diem danh chi tiet
// (ktx_diem_danh_chi_tiet.php).
//
// GIAO DIEN MOI (2026-10-10): khung chung nhanh KTX (includes/ktx_khung.php), 4 the so lieu, form
// "Tao dot" thu gon (mo khi bam nut / khi dang sua), bang dot co thanh ty le co mat – vang – chua
// diem danh, hop "Link tu diem danh" co nut sao chep + ma QR. Xu ly luu/xoa GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'luu') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $ten = trim($_POST['ten'] ?? '');
    $ngayToChuc = $_POST['ngay_to_chuc'] ?: null;
    $hanDiemDanh = trim($_POST['han_diem_danh'] ?? '') ? str_replace('T', ' ', $_POST['han_diem_danh']) . ':00' : null;
    $diaDiem = trim($_POST['dia_diem'] ?? '') ?: null;
    $ghiChu = trim($_POST['ghi_chu'] ?? '') ?: null;
    if ($ten !== '') {
        if ($id) {
            $pdo->prepare('UPDATE ktx_dot_diem_danh SET ten=?, ngay_to_chuc=?, han_diem_danh=?, dia_diem=?, ghi_chu=? WHERE id=?')
                ->execute([$ten, $ngayToChuc, $hanDiemDanh, $diaDiem, $ghiChu, $id]);
        } else {
            $pdo->prepare('INSERT INTO ktx_dot_diem_danh (ten, ngay_to_chuc, han_diem_danh, dia_diem, ghi_chu) VALUES (?,?,?,?,?)')
                ->execute([$ten, $ngayToChuc, $hanDiemDanh, $diaDiem, $ghiChu]);
        }
    }
    header('Location: ktx_diem_danh.php?saved=1'); exit;
}
if (($_GET['action'] ?? '') === 'xoa' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM ktx_dot_diem_danh WHERE id = ?')->execute([(int)$_GET['id']]);
    header('Location: ktx_diem_danh.php'); exit;
}

$dsDot = $pdo->query("SELECT dd.*,
        (SELECT COUNT(*) FROM ktx_diem_danh_chi_tiet c WHERE c.dot_id = dd.id AND c.co_mat = 1) AS so_co_mat,
        (SELECT COUNT(*) FROM ktx_diem_danh_chi_tiet c WHERE c.dot_id = dd.id AND c.co_mat = 0) AS so_vang_ghi_nhan
    FROM ktx_dot_diem_danh dd
    ORDER BY COALESCE(dd.ngay_to_chuc, dd.created_at) DESC, dd.id DESC")->fetchAll();

$dotEditId = !empty($_GET['sua']) ? (int)$_GET['sua'] : 0;
$dotEditRow = null;
if ($dotEditId) {
    foreach ($dsDot as $d) { if ((int)$d['id'] === $dotEditId) { $dotEditRow = $d; break; } }
}
$tongDangO = (int)$pdo->query("SELECT COUNT(*) FROM ktx_hoc_vien WHERE trang_thai = 'dang_o'")->fetchColumn();

// Tinh so co mat / vang / chua diem danh cho tung dot (GIU DUNG cong thuc cu: qua han thi nguoi
// chua ghi nhan duoc tinh la vang mat) + so lieu cho cac the tong quan.
$bayGio = date('Y-m-d H:i:s');
$soDangMo = 0; $soDaDong = 0; $tongTyLe = 0; $soDotTinhTyLe = 0;
foreach ($dsDot as &$d) {
    $d['_qua_han'] = $d['han_diem_danh'] && $bayGio >= $d['han_diem_danh'];
    $soVangGhiNhan = (int)$d['so_vang_ghi_nhan'];
    $soDaGhiNhan = (int)$d['so_co_mat'] + $soVangGhiNhan;
    $soChuaGhiNhan = max(0, $tongDangO - $soDaGhiNhan);
    $d['_vang'] = $d['_qua_han'] ? $soVangGhiNhan + $soChuaGhiNhan : $soVangGhiNhan;
    $d['_chua'] = $d['_qua_han'] ? 0 : $soChuaGhiNhan;
    $d['_tong'] = (int)$d['so_co_mat'] + $d['_vang'] + $d['_chua'];
    if ($d['_qua_han']) {
        $soDaDong++;
        if ($d['_tong'] > 0) { $tongTyLe += (int)$d['so_co_mat'] / $d['_tong']; $soDotTinhTyLe++; }
    } else {
        $soDangMo++;
    }
}
unset($d);
$dotGanNhat = $dsDot[0] ?? null;
$tyLeTb = $soDotTinhTyLe ? round($tongTyLe / $soDotTinhTyLe * 100) : null;
$moForm = $dotEditRow || !$dsDot || !empty($_GET['tao']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/ktx_khung.php';
?>
<?php ktx_khung_css(); ?>
<style>
.dd-form{padding:4px 14px 14px;border-bottom:1px solid var(--line);background:#fbfcfe}
.dd-form h3{margin:12px 0 10px;font-size:13px;color:var(--navy);text-transform:uppercase;letter-spacing:.3px}
.dd-form .act{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
.dd-form[hidden]{display:none}
.dd-ten b{display:block;font-size:14px}.dd-ten small{display:block;color:var(--ink3);font-size:12px;margin-top:2px}
.dd-kq{display:flex;flex-direction:column;gap:5px;min-width:200px}
.dd-kq .so{display:flex;gap:12px;font-size:12.5px;color:var(--ink2);white-space:nowrap}
.dd-kq .so b{font-size:14px}
.dd-act{display:flex;gap:6px;align-items:center;white-space:nowrap}
.dd-act a.ic,.dd-act button.ic{display:inline-grid;place-items:center;width:30px;height:30px;border-radius:7px;border:1px solid var(--line);background:#fff;color:var(--ink2);cursor:pointer;text-decoration:none}
.dd-act a.ic:hover,.dd-act button.ic:hover{border-color:var(--pri-line);color:var(--pri);background:#f7faff}
.dd-act a.ic.del:hover{border-color:#f3c2c2;color:var(--red);background:var(--red-soft)}
.dd-act svg{width:15px;height:15px}
.dd-note{padding:8px 12px;font-size:12.5px;color:var(--ink2);border-bottom:1px solid var(--line2);line-height:1.5}
/* hop link tu diem danh */
.dd-ov{position:fixed;inset:0;background:rgba(10,20,40,.45);z-index:2000;display:none;align-items:center;justify-content:center;padding:16px}
.dd-ov.on{display:flex}
.dd-box{width:min(460px,100%);background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;font-family:"Segoe UI",Roboto,Arial,sans-serif;color:var(--ink)}
.dd-box header{display:flex;align-items:center;gap:10px;padding:14px 16px;background:linear-gradient(90deg,var(--navy),var(--navy2));color:#fff}
.dd-box header h3{margin:0;font-size:16px;color:#fff}.dd-box header small{display:block;opacity:.85;font-size:12.5px}
.dd-box header button{margin-left:auto;background:rgba(255,255,255,.15);border:0;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:16px}
.dd-box .bd{padding:14px 16px;display:flex;flex-direction:column;gap:12px}
.dd-box .lk{display:flex;gap:6px}.dd-box .lk input{flex:1;height:34px;border:1px solid var(--line);border-radius:7px;padding:0 10px;font:inherit;font-size:13px;color:var(--ink);background:#f8fbff}
.dd-qr{display:grid;place-items:center;padding:10px;border:1px dashed var(--line);border-radius:10px;min-height:60px}
.dd-qr img,.dd-qr canvas{display:block}
.dd-box p{margin:0;font-size:12.5px;color:var(--ink2);line-height:1.5}
@media print{body.dd-in-qr .hs,body.dd-in-qr #admin-sticky{display:none!important}body.dd-in-qr .dd-ov{position:static;background:none;display:flex!important}body.dd-in-qr .dd-box{box-shadow:none}body.dd-in-qr .dd-box header button,body.dd-in-qr .dd-box .lk,body.dd-in-qr .dd-box .no-print{display:none!important}}
</style>
<?php ob_start(); ?>
    <label class="hs-gs"><input id="ddQ" placeholder="Tìm đợt điểm danh (tên, địa điểm…)" autocomplete="off"><span class="ico"><svg class="i"><use href="#hi-search"/></svg></span></label>
<?php $khungGiua = ob_get_clean(); ob_start(); ?>
      <a class="hs-tb" href="ktx_sua_chua.php"><svg class="i"><use href="#hi-tool"/></svg>Theo dõi sửa chữa</a>
      <a class="hs-tb" href="<?= h(KTX_TRANG_TONG_QUAN) ?>"><svg class="i"><use href="#hi-chart"/></svg>Tổng quan KTX</a>
<?php $khungPhai = ob_get_clean(); ?>
<?php ktx_khung_mo('ktx_diem_danh.php', 'ĐIỂM DANH THEO ĐỢT', 'Tập huấn PCCC, họp dân cư, sinh hoạt đầu khoá…', $khungGiua, $khungPhai); ?>

      <?php if (!empty($_GET['saved'])): ?><div class="hs-msg ok"><span>✅ Đã lưu đợt điểm danh.</span></div><?php endif; ?>

      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-cal"/></svg></div>
            <div><div class="num"><?= count($dsDot) ?></div><div class="lb">Đợt điểm danh</div></div></div>
          <div class="sb">
            <div><b style="color:var(--green)"><?= $soDangMo ?></b><span>Đang mở</span></div>
            <div><b><?= $soDaDong ?></b><span>Đã đóng (quá hạn)</span></div>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-check"/></svg></div>
            <div><div class="num"><?= $dotGanNhat && $dotGanNhat['_tong'] ? round($dotGanNhat['so_co_mat'] / $dotGanNhat['_tong'] * 100) . '%' : '—' ?></div><div class="lb">Có mặt – đợt gần nhất</div></div></div>
          <div class="sb">
            <?php if ($dotGanNhat): ?>
            <div><b style="color:var(--green)"><?= (int)$dotGanNhat['so_co_mat'] ?></b><span>Có mặt</span></div>
            <div><b style="color:var(--red)"><?= $dotGanNhat['_vang'] ?></b><span>Vắng</span></div>
            <div><b style="color:var(--ink3)"><?= $dotGanNhat['_chua'] ?></b><span>Chưa điểm danh</span></div>
            <?php else: ?><div><span>Chưa có đợt nào</span></div><?php endif; ?>
          </div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-chart"/></svg></div>
            <div><div class="num"><?= $tyLeTb === null ? '—' : $tyLeTb . '%' ?></div><div class="lb">Tỉ lệ có mặt trung bình</div></div></div>
          <div class="sb"><div><span>Tính trên <?= $soDotTinhTyLe ?> đợt đã đóng</span></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num"><?= $tongDangO ?></div><div class="lb">Học viên đang ở KTX</div></div></div>
          <div class="sb"><div><span>Là số người cần điểm danh ở mỗi đợt</span></div></div>
        </div>
      </div>

      <div class="hs-card">
        <div class="hs-tool">
          <h2>DANH SÁCH ĐỢT ĐIỂM DANH</h2>
          <button type="button" class="hs-btn pri" id="ddMoForm"><svg class="i"><use href="#hi-plus"/></svg>Tạo đợt</button>
          <span class="hs-sp"></span>
          <span id="ddDem" style="font-size:12.5px;color:var(--ink2)"><?= count($dsDot) ?> đợt</span>
        </div>
        <form method="post" class="dd-form" id="ddForm"<?= $moForm ? '' : ' hidden' ?>>
          <input type="hidden" name="action" value="luu">
          <input type="hidden" name="id" value="<?= $dotEditRow ? (int)$dotEditRow['id'] : '' ?>">
          <h3><?= $dotEditRow ? 'Sửa đợt điểm danh' : 'Tạo đợt điểm danh mới' ?></h3>
          <div class="hs-fg">
            <label class="s2">Tên đợt *<input type="text" name="ten" value="<?= h($dotEditRow['ten'] ?? '') ?>" placeholder="VD: Tập huấn PCCC đợt 1" required></label>
            <label>Ngày tổ chức<input type="date" name="ngay_to_chuc" value="<?= h($dotEditRow['ngay_to_chuc'] ?? '') ?>"></label>
            <label>Hạn điểm danh <span class="hint">qua giờ này, ai chưa điểm danh tự ghi "Vắng mặt"</span>
              <input type="datetime-local" name="han_diem_danh" value="<?= $dotEditRow && $dotEditRow['han_diem_danh'] ? str_replace(' ', 'T', substr($dotEditRow['han_diem_danh'], 0, 16)) : '' ?>"></label>
            <label>Địa điểm<input type="text" name="dia_diem" value="<?= h($dotEditRow['dia_diem'] ?? '') ?>" placeholder="VD: Hội trường KTX"></label>
            <label>Ghi chú<input type="text" name="ghi_chu" value="<?= h($dotEditRow['ghi_chu'] ?? '') ?>"></label>
          </div>
          <div class="act">
            <?php if ($dotEditRow): ?><a class="hs-btn" href="ktx_diem_danh.php">Huỷ sửa</a><?php else: ?><button type="button" class="hs-btn" id="ddDongForm">Đóng</button><?php endif; ?>
            <button type="submit" class="hs-btn pri"><svg class="i"><use href="#hi-save"/></svg><?= $dotEditRow ? 'Lưu thay đổi' : 'Tạo đợt' ?></button>
          </div>
        </form>
        <div class="dd-note">Mỗi đợt là một sự kiện điểm danh riêng. Bấm <b>Link</b> ở mỗi đợt để lấy đường dẫn và mã QR cho học viên <b>tự điểm danh</b> (chỉ cần nhập Mã số, không cần đăng nhập) — in mã QR dán tại sự kiện.</div>
        <div class="hs-tw">
          <table class="hs-grid">
            <thead><tr><th>Đợt điểm danh</th><th>Trạng thái</th><th>Ngày tổ chức</th><th>Hạn điểm danh</th><th>Địa điểm</th><th>Kết quả</th><th></th></tr></thead>
            <tbody id="ddBody">
            <?php foreach ($dsDot as $d): $tong = max(1, $d['_tong']); $link = '../public/ktx_diem_danh_congkhai.php?dot_id=' . (int)$d['id']; ?>
            <tr data-q="<?= h(mb_strtolower($d['ten'] . ' ' . $d['dia_diem'] . ' ' . $d['ghi_chu'])) ?>">
              <td class="dd-ten"><b><?= h($d['ten']) ?></b><?= $d['ghi_chu'] ? '<small>' . h($d['ghi_chu']) . '</small>' : '' ?></td>
              <td><?php if (!$d['han_diem_danh']): ?><span class="hs-pill blue">Đang mở · chưa đặt hạn</span><?php elseif ($d['_qua_han']): ?><span class="hs-pill gray">Đã đóng</span><?php else: ?><span class="hs-pill green">Đang mở</span><?php endif; ?></td>
              <td style="white-space:nowrap"><?= $d['ngay_to_chuc'] ? h(format_date_vn($d['ngay_to_chuc'])) : '<span class="hs-mut">—</span>' ?></td>
              <td style="white-space:nowrap"><?= $d['han_diem_danh'] ? h(format_datetime_vn($d['han_diem_danh'])) : '<span class="hs-mut">—</span>' ?></td>
              <td><?= $d['dia_diem'] ? h($d['dia_diem']) : '<span class="hs-mut">—</span>' ?></td>
              <td>
                <div class="dd-kq">
                  <div class="hs-bar3" title="Có mặt / Vắng / Chưa điểm danh">
                    <span style="width:<?= (int)$d['so_co_mat'] / $tong * 100 ?>%;background:var(--green)"></span><span style="width:<?= $d['_vang'] / $tong * 100 ?>%;background:var(--red)"></span><span style="width:<?= $d['_chua'] / $tong * 100 ?>%;background:#c9d3e0"></span>
                  </div>
                  <div class="so"><span><b class="hs-ok"><?= (int)$d['so_co_mat'] ?></b> có mặt</span><span><b class="hs-no"><?= $d['_vang'] ?></b> vắng</span><span><b style="color:var(--ink3)"><?= $d['_chua'] ?></b> chưa</span></div>
                </div>
              </td>
              <td>
                <div class="dd-act">
                  <a href="ktx_diem_danh_chi_tiet.php?dot_id=<?= (int)$d['id'] ?>" class="hs-btn pri sm"><svg class="i"><use href="#hi-check"/></svg>Điểm danh</a>
                  <button type="button" class="ic" title="Link / mã QR tự điểm danh" data-link="<?= h($link) ?>" data-ten="<?= h($d['ten']) ?>"><svg class="i"><use href="#hi-link"/></svg></button>
                  <a class="ic" href="?sua=<?= (int)$d['id'] ?>" title="Sửa đợt"><svg class="i"><use href="#hi-edit"/></svg></a>
                  <a class="ic del" href="?action=xoa&amp;id=<?= (int)$d['id'] ?>" title="Xoá đợt" onclick="return confirm('Xoá đợt điểm danh này? Toàn bộ dữ liệu điểm danh của đợt sẽ mất.')"><svg class="i"><use href="#hi-trash"/></svg></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$dsDot): ?><tr><td colspan="7" class="hs-empty">Chưa có đợt điểm danh nào — bấm <b>Tạo đợt</b> để bắt đầu.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php ktx_khung_dong(); ?>

<div class="dd-ov hs-pop" id="ddOv" role="dialog" aria-modal="true" aria-labelledby="ddOvTen">
  <div class="dd-box">
    <header><div><h3>Link tự điểm danh</h3><small id="ddOvTen"></small></div><button type="button" id="ddOvDong" aria-label="Đóng">✕</button></header>
    <div class="bd">
      <div class="lk"><input id="ddOvLink" readonly><button type="button" class="hs-btn pri" id="ddOvCopy"><svg class="i"><use href="#hi-copy"/></svg>Sao chép</button></div>
      <div class="dd-qr" id="ddOvQr"><span class="hs-mut" style="font-size:12.5px">Đang tạo mã QR…</span></div>
      <p class="no-print">Học viên quét mã hoặc mở link, nhập <b>Mã số</b> để điểm danh. Bấm <b>In mã QR</b> để in dán tại sự kiện.</p>
      <div class="no-print" style="display:flex;gap:8px;justify-content:flex-end">
        <a class="hs-btn" id="ddOvMo" href="#" target="_blank"><svg class="i"><use href="#hi-link"/></svg>Mở thử</a>
        <button type="button" class="hs-btn" id="ddOvIn"><svg class="i"><use href="#hi-print"/></svg>In mã QR</button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<script>
(function () {
  var form = document.getElementById('ddForm');
  document.getElementById('ddMoForm').addEventListener('click', function () { form.hidden = false; form.querySelector('[name=ten]').focus(); });
  var dong = document.getElementById('ddDongForm'); if (dong) dong.addEventListener('click', function () { form.hidden = true; });

  // Tim nhanh trong danh sach dot
  var q = document.getElementById('ddQ'), dem = document.getElementById('ddDem');
  q.addEventListener('input', function () {
    var t = q.value.trim().toLowerCase(), n = 0;
    document.querySelectorAll('#ddBody tr[data-q]').forEach(function (tr) { var ok = !t || tr.dataset.q.indexOf(t) >= 0; tr.hidden = !ok; if (ok) n++; });
    dem.textContent = n + ' đợt' + (t ? ' khớp từ khoá' : '');
  });

  // Hop link + ma QR tu diem danh
  var ov = document.getElementById('ddOv'), inp = document.getElementById('ddOvLink'), qr = document.getElementById('ddOvQr');
  function mo(link, ten) {
    var url = new URL(link, location.href).href;
    inp.value = url; document.getElementById('ddOvTen').textContent = ten; document.getElementById('ddOvMo').href = url;
    qr.innerHTML = '';
    if (window.QRCode) { new QRCode(qr, { text: url, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M }); }
    else { qr.innerHTML = '<span class="hs-mut" style="font-size:12.5px">Không tải được thư viện mã QR — dán link vào trang tạo QR bất kỳ.</span>'; }
    ov.classList.add('on');
  }
  function dongOv() { ov.classList.remove('on'); }
  document.getElementById('ddBody').addEventListener('click', function (e) { var b = e.target.closest('[data-link]'); if (b) mo(b.dataset.link, b.dataset.ten); });
  document.getElementById('ddOvDong').addEventListener('click', dongOv);
  ov.addEventListener('click', function (e) { if (e.target === ov) dongOv(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') dongOv(); });
  document.getElementById('ddOvCopy').addEventListener('click', function () {
    var btn = this;
    function xong() { btn.lastChild.textContent = 'Đã chép'; setTimeout(function () { btn.lastChild.textContent = 'Sao chép'; }, 1500); }
    if (navigator.clipboard) { navigator.clipboard.writeText(inp.value).then(xong, function () { inp.select(); document.execCommand('copy'); xong(); }); }
    else { inp.select(); document.execCommand('copy'); xong(); }
  });
  document.getElementById('ddOvIn').addEventListener('click', function () { document.body.classList.add('dd-in-qr'); window.print(); document.body.classList.remove('dd-in-qr'); });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
