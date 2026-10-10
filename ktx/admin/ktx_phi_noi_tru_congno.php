<?php
// "Dien bien cong no" cua 1 hoc vien noi tru — mo tu nut cung ten o ktx_phi_noi_tru.php (tab
// "Thu phi nguoi noi tru"): liet ke cac khoan CHUA NOP (phi noi tru theo thang, dien nuoc theo
// phong da phan bo binh quan dau nguoi) + lich su cac phieu thu da lap cho hoc vien nay (2026-09-15).
//
// GIAO DIEN MOI (2026-10-10): dung bang mau/lop CSS chung nhanh KTX (includes/ktx_khung.php) nhung
// GIU la trang doc lap (mo o tab moi, khong menu) — dai tieu de, 3 the tong no, 3 bang co dong cong,
// nut In / Lap phieu thu / Ho so. Cach tinh cong no GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

$hocVienId = (int)($_GET['hoc_vien_id'] ?? 0);
$hv = $pdo->prepare('SELECT hv.*, p.so_phong, d.ten AS ten_day FROM ktx_hoc_vien hv
    LEFT JOIN ktx_phong p ON p.id = hv.phong_id LEFT JOIN ktx_day d ON d.id = p.day_id WHERE hv.id = ?');
$hv->execute([$hocVienId]);
$hv = $hv->fetch();
if (!$hv) { http_response_code(404); echo 'Không tìm thấy học viên.'; exit; }

$phiChuaNop = $pdo->prepare("SELECT * FROM ktx_phi_noi_tru WHERE hoc_vien_id = ? AND da_nop = 0 ORDER BY nam, thang");
$phiChuaNop->execute([$hocVienId]);
$phiChuaNop = $phiChuaNop->fetchAll();

$dienNuocChuaThu = [];
if ($hv['phong_id']) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ktx_hoc_vien WHERE phong_id = ? AND trang_thai = 'dang_o'");
    $stmt->execute([$hv['phong_id']]);
    $soNguoiCungPhong = max(1, (int)$stmt->fetchColumn());

    $stmtDn = $pdo->prepare('SELECT * FROM ktx_dien_nuoc WHERE phong_id = ? AND da_thu = 0 AND la_quy = 0 ORDER BY nam, thang');
    $stmtDn->execute([$hv['phong_id']]);
    foreach ($stmtDn->fetchAll() as $dn) {
        $tienDien = ktx_tieu_thu_dien($dn) * (float)$dn['don_gia_dien'];
        $tienNuoc = ktx_tieu_thu_nuoc($dn) * (float)$dn['don_gia_nuoc'];
        $dienNuocChuaThu[] = ['thang' => $dn['thang'], 'nam' => $dn['nam'], 'tien_dien' => $tienDien, 'tien_nuoc' => $tienNuoc, 'tong_phong' => $tienDien + $tienNuoc, 'phan_bo' => ($tienDien + $tienNuoc) / $soNguoiCungPhong, 'so_nguoi' => $soNguoiCungPhong];
    }
}

$tongNoPhi = array_sum(array_column($phiChuaNop, 'so_tien'));
$tongNoDn = array_sum(array_column($dienNuocChuaThu, 'phan_bo'));
$tongNo = $tongNoPhi + $tongNoDn;

$lichSuPhieu = $pdo->prepare("SELECT pt.*, GROUP_CONCAT(ct.noi_dung SEPARATOR '; ') AS cac_khoan
    FROM ktx_phieu_thu pt LEFT JOIN ktx_phieu_thu_ct ct ON ct.phieu_thu_id = pt.id
    WHERE pt.loai = 'ca_nhan' AND pt.hoc_vien_id = ?
    GROUP BY pt.id ORDER BY pt.ngay_thu DESC, pt.id DESC");
$lichSuPhieu->execute([$hocVienId]);
$lichSuPhieu = $lichSuPhieu->fetchAll();
$tongDaNop = array_sum(array_column($lichSuPhieu, 'so_tien_nop'));

require_once __DIR__ . '/../includes/ktx_khung.php';
$dangO = ($hv['trang_thai'] ?? '') === 'dang_o';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diễn biến công nợ — <?= h($hv['ho_ten']) ?></title>
<?php ktx_khung_css(); ?>
<style>
body{margin:0;background:#eef3fa}
.cn{max-width:1100px}
.cn-top{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.cn-top .who b{display:block;font-size:19px;line-height:1.2}.cn-top .who small{display:block;font-size:12.5px;opacity:.85;margin-top:2px}
.cn-top .acts{margin-left:auto;display:flex;gap:6px;flex-wrap:wrap}
.cn-top .acts .hs-btn{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff!important}
.cn-top .acts .hs-btn:hover{background:rgba(255,255,255,.22)}
.cn-top .acts .hs-btn.pri{background:#fff;color:var(--navy)!important;border-color:#fff}
.hs-kpis.cn-kpis{grid-template-columns:repeat(3,1fr)}
.cn-sec{margin-top:12px}
.cn-sec .hs-tool h2{display:flex;align-items:center;gap:8px;white-space:normal}
.cn-sec .hs-tool h2 svg{width:18px;height:18px}
table.hs-grid tfoot td{font-weight:700;background:#f1f5fb;border-top:1px solid var(--line)}
.cn-ok{padding:18px!important;text-align:center;color:var(--green);font-weight:600}
.cn-foot{text-align:center;color:var(--ink3);font-size:11.5px;padding:16px 10px 4px}
@media (max-width:760px){.hs-kpis.cn-kpis{grid-template-columns:1fr}.cn-top .acts{margin-left:0}}
@media print{body{background:#fff}.cn-top .acts{display:none}.hs-top{background:none;color:var(--ink);box-shadow:none;border-bottom:2px solid var(--navy);border-radius:0}.hs-top .lg{display:none}.hs-kpis{display:grid!important}.hs-tool{display:flex!important}.hs-card{break-inside:avoid}}
</style>
</head>
<body>
<?php ktx_khung_icon(); $tien = fn($n) => format_money($n); ?>
<div class="hs cn">
  <div class="hs-top cn-top">
    <span class="hs-brand"><span class="lg"><svg class="i"><use href="#hi-receipt"/></svg></span></span>
    <div class="who"><b>Diễn biến công nợ — <?= h($hv['ho_ten']) ?></b>
      <small>Mã số <?= h((string)$hv['ma_so']) ?> · <?= h($hv['ten_day'] ? $hv['ten_day'] . ' - Phòng ' . $hv['so_phong'] : 'Chưa xếp phòng') ?> · <?= $dangO ? 'Đang ở' : 'Đã rời KTX' ?> · Xem lúc <?= date('H:i d/m/Y') ?></small></div>
    <div class="acts">
      <a class="hs-btn pri" href="ktx_phi_noi_tru.php?tab=ca_nhan&amp;ma_so=<?= urlencode((string)$hv['ma_so']) ?>"><svg class="i"><use href="#hi-coin"/></svg>Lập phiếu thu</a>
      <a class="hs-btn" href="ktx_hoc_vien.php?id=<?= (int)$hv['id'] ?>#ho-so"><svg class="i"><use href="#hi-id"/></svg>Hồ sơ</a>
      <button type="button" class="hs-btn" onclick="window.print()"><svg class="i"><use href="#hi-print"/></svg>In</button>
    </div>
  </div>

  <div class="hs-kpis cn-kpis">
    <div class="hs-card hs-kpi">
      <div class="tp"><div class="ic" style="background:<?= $tongNo > 0 ? 'var(--red-soft);color:var(--red)' : 'var(--green-soft);color:var(--green)' ?>"><svg class="i"><use href="#hi-alert"/></svg></div>
        <div><div class="num" style="color:<?= $tongNo > 0 ? 'var(--red)' : 'var(--green)' ?>"><?= $tien($tongNo) ?></div><div class="lb">Tổng dư nợ hiện tại</div></div></div>
      <div class="sb"><div><b><?= $tien($tongDaNop) ?></b><span>Đã nộp qua <?= count($lichSuPhieu) ?> phiếu thu</span></div></div>
    </div>
    <div class="hs-card hs-kpi">
      <div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-coin"/></svg></div>
        <div><div class="num"><?= $tien($tongNoPhi) ?></div><div class="lb">Phí nội trú chưa nộp</div></div></div>
      <div class="sb"><div><b><?= count($phiChuaNop) ?></b><span>tháng chưa nộp</span></div></div>
    </div>
    <div class="hs-card hs-kpi">
      <div class="tp"><div class="ic" style="background:#fdeee6;color:#d4561f"><svg class="i"><use href="#hi-bolt"/></svg></div>
        <div><div class="num"><?= $tien($tongNoDn) ?></div><div class="lb">Điện, nước chưa nộp (phần của học viên)</div></div></div>
      <div class="sb"><div><b><?= count($dienNuocChuaThu) ?></b><span>kỳ chưa thu</span></div></div>
    </div>
  </div>

  <div class="hs-card cn-sec">
    <div class="hs-tool"><h2><svg class="i" style="color:var(--orange)"><use href="#hi-coin"/></svg>PHÍ NỘI TRÚ CHƯA NỘP</h2></div>
    <div class="hs-tw"><table class="hs-grid">
      <thead><tr><th class="c">Kỳ</th><th class="r">Số tiền</th><th>Ghi chú</th></tr></thead>
      <tbody>
      <?php foreach ($phiChuaNop as $p): ?>
        <tr><td class="c">Tháng <?= (int)$p['thang'] ?>/<?= (int)$p['nam'] ?></td><td class="r"><?= $tien($p['so_tien']) ?></td><td><?= h((string)$p['ghi_chu']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$phiChuaNop): ?><tr><td colspan="3" class="cn-ok">✓ Không còn nợ phí nội trú.</td></tr><?php endif; ?>
      </tbody>
      <?php if ($phiChuaNop): ?><tfoot><tr><td class="c">Cộng <?= count($phiChuaNop) ?> tháng</td><td class="r" style="color:var(--red)"><?= $tien($tongNoPhi) ?></td><td></td></tr></tfoot><?php endif; ?>
    </table></div>
  </div>

  <div class="hs-card cn-sec">
    <div class="hs-tool"><h2><svg class="i" style="color:#d4561f"><use href="#hi-bolt"/></svg>ĐIỆN, NƯỚC CHƯA THU (PHẦN PHÂN BỔ CỦA HỌC VIÊN)</h2></div>
    <div class="hs-tw"><table class="hs-grid">
      <thead><tr><th class="c">Kỳ</th><th class="r">Tiền điện</th><th class="r">Tiền nước</th><th class="r">Tổng cả phòng</th><th class="c">Số người ở</th><th class="r">Phần của học viên</th></tr></thead>
      <tbody>
      <?php foreach ($dienNuocChuaThu as $d): ?>
        <tr><td class="c">Tháng <?= (int)$d['thang'] ?>/<?= (int)$d['nam'] ?></td><td class="r"><?= $tien($d['tien_dien']) ?></td><td class="r"><?= $tien($d['tien_nuoc']) ?></td><td class="r"><?= $tien($d['tong_phong']) ?></td><td class="c"><?= (int)$d['so_nguoi'] ?></td><td class="r" style="font-weight:600"><?= $tien($d['phan_bo']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$dienNuocChuaThu): ?><tr><td colspan="6" class="cn-ok">✓ Không còn nợ điện, nước.</td></tr><?php endif; ?>
      </tbody>
      <?php if ($dienNuocChuaThu): ?><tfoot><tr><td class="c">Cộng <?= count($dienNuocChuaThu) ?> kỳ</td><td colspan="4"></td><td class="r" style="color:var(--red)"><?= $tien($tongNoDn) ?></td></tr></tfoot><?php endif; ?>
    </table></div>
    <?php if ($dienNuocChuaThu): ?><p style="margin:0;padding:8px 12px;font-size:12px;color:var(--ink2)">Phần của học viên = tổng tiền điện, nước cả phòng ÷ số người đang ở phòng hiện tại.</p><?php endif; ?>
  </div>

  <div class="hs-card cn-sec">
    <div class="hs-tool"><h2><svg class="i" style="color:var(--pri)"><use href="#hi-receipt"/></svg>LỊCH SỬ PHIẾU THU ĐÃ LẬP</h2></div>
    <div class="hs-tw"><table class="hs-grid">
      <thead><tr><th class="c">Số phiếu</th><th class="c">Ngày thu</th><th>Các khoản</th><th class="r">Tổng tiền</th><th class="r">Đã nộp</th><th class="r">Còn lại</th></tr></thead>
      <tbody>
      <?php foreach ($lichSuPhieu as $p): ?>
        <tr>
          <td class="c"><a href="ktx_phieu_thu_in.php?id=<?= (int)$p['id'] ?>" target="_blank" style="color:var(--pri);font-weight:600">PT<?= str_pad((string)$p['so_phieu'], 6, '0', STR_PAD_LEFT) ?></a></td>
          <td class="c"><?= h(format_date_vn($p['ngay_thu'])) ?></td>
          <td><?= h((string)$p['cac_khoan']) ?></td>
          <td class="r"><?= $tien($p['tong_tien']) ?></td>
          <td class="r"><?= $tien($p['so_tien_nop']) ?></td>
          <td class="r <?= $p['con_lai'] > 0 ? 'hs-no' : 'hs-ok' ?>"><?= $tien($p['con_lai']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$lichSuPhieu): ?><tr><td colspan="6" class="hs-empty">Chưa có phiếu thu nào.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="cn-foot">Bản quyền thuộc về Phạm Trần Nam - CV, Trường Đại học Y Dược Cần Thơ</div>
</div>
</body>
</html>
