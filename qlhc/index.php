<?php
require __DIR__ . '/includes/bootstrap.php';

$u = require_login();
$nam = (int)date('Y');
$canh_bao = max(0, (int)setting('canh_bao_han_ngay', '3'));

$stat = [
    'di_nam'  => (int)q_val('SELECT COUNT(*) FROM hc_van_ban_di WHERE nam = ?', [$nam]),
    'di_thang' => (int)q_val('SELECT COUNT(*) FROM hc_van_ban_di WHERE YEAR(ngay_ban_hanh) = ? AND MONTH(ngay_ban_hanh) = ?', [$nam, (int)date('n')]),
    'den_nam' => (int)q_val('SELECT COUNT(*) FROM hc_van_ban_den WHERE nam = ?', [$nam]),
    'den_cho' => (int)q_val("SELECT COUNT(*) FROM hc_van_ban_den WHERE trang_thai <> 'hoan_thanh'"),
];

// Văn bản đến cần xử lý của tôi / quá hạn
$where = "trang_thai <> 'hoan_thanh'";
$params = [];
if (!can('vb_den.xem_tat_ca')) {
    $where .= ' AND nguoi_xu_ly_id = ?';
    $params[] = $u['id'];
}
$viec = q("SELECT * FROM hc_van_ban_den WHERE $where ORDER BY (han_xu_ly IS NULL), han_xu_ly, ngay_den DESC LIMIT 10", $params)->fetchAll();
$qua_han = (int)q_val("SELECT COUNT(*) FROM hc_van_ban_den WHERE $where AND han_xu_ly IS NOT NULL AND han_xu_ly < CURDATE()", $params);

// Dự thảo chờ tôi xử lý
$dt_where = [];
$dt_params = [];
if (can('du_thao.duyet')) $dt_where[] = "trang_thai = 'trinh_ky'";
if (can('du_thao.ban_hanh')) $dt_where[] = "trang_thai = 'da_duyet'";
$dt_where[] = "(nguoi_tao = ? AND trang_thai IN ('nhap','tra_lai'))";
$dt_params[] = $u['id'];
$du_thao = q('SELECT d.*, l.ten AS loai_ten FROM hc_du_thao d LEFT JOIN hc_loai_van_ban l ON l.id = d.loai_id WHERE ' . implode(' OR ', $dt_where) . ' ORDER BY COALESCE(d.cap_nhat_luc, d.tao_luc) DESC LIMIT 8', $dt_params)->fetchAll();

$moi_di = q('SELECT v.*, l.ten AS loai_ten FROM hc_van_ban_di v LEFT JOIN hc_loai_van_ban l ON l.id = v.loai_id ORDER BY v.ngay_ban_hanh DESC, v.id DESC LIMIT 6')->fetchAll();

// Thống kê theo tháng (12 tháng của năm hiện tại)
$theo_thang = array_fill(1, 12, ['di' => 0, 'den' => 0]);
foreach (q('SELECT MONTH(ngay_ban_hanh) m, COUNT(*) c FROM hc_van_ban_di WHERE YEAR(ngay_ban_hanh) = ? GROUP BY m', [$nam])->fetchAll() as $r) {
    $theo_thang[(int)$r['m']]['di'] = (int)$r['c'];
}
foreach (q('SELECT MONTH(ngay_den) m, COUNT(*) c FROM hc_van_ban_den WHERE YEAR(ngay_den) = ? GROUP BY m', [$nam])->fetchAll() as $r) {
    $theo_thang[(int)$r['m']]['den'] = (int)$r['c'];
}
$max_thang = max(1, max(array_map(function ($x) { return max($x['di'], $x['den']); }, $theo_thang)));

$actions = '';
if (can('du_thao.soan')) $actions .= '<a class="btn" href="soan-thao.php?moi=1">+ Soạn văn bản</a> ';
if (can('vb_den.sua')) $actions .= '<a class="btn btn-ghost" href="van-ban-den.php?sua=0">+ Vào sổ văn bản đến</a>';

page_header('Tổng quan', ['sub' => 'Xin chào, ' . $u['ho_ten'] . ' · ' . (VAI_TRO[$u['vai_tro']] ?? ''), 'actions' => $actions]);
?>
<div class="stats">
  <a class="stat" href="van-ban-di.php"><small>Văn bản đi năm <?= $nam ?></small><b><?= $stat['di_nam'] ?></b><span><?= $stat['di_thang'] ?> trong tháng này</span></a>
  <a class="stat" href="van-ban-den.php"><small>Văn bản đến năm <?= $nam ?></small><b><?= $stat['den_nam'] ?></b><span><?= $stat['den_cho'] ?> chưa hoàn thành</span></a>
  <a class="stat <?= $qua_han ? 'stat-bad' : '' ?>" href="van-ban-den.php?loc=qua_han"><small>Quá hạn xử lý</small><b><?= $qua_han ?></b><span><?= can('vb_den.xem_tat_ca') ? 'toàn đơn vị' : 'được giao cho tôi' ?></span></a>
  <a class="stat" href="soan-thao.php"><small>Dự thảo chờ xử lý</small><b><?= count($du_thao) ?></b><span>soạn / duyệt / ban hành</span></a>
</div>

<div class="grid-2">
  <section class="card">
    <h2>Văn bản đến cần xử lý</h2>
    <?php if (!$viec): ?><p class="empty">Không có văn bản nào đang chờ xử lý.</p><?php else: ?>
    <ul class="list">
      <?php foreach ($viec as $v):
          $han = $v['han_xu_ly'];
          $tone = 'muted';
          if ($han) {
              $con = (int)floor((strtotime($han) - strtotime(date('Y-m-d'))) / 86400);
              $tone = $con < 0 ? 'bad' : ($con <= $canh_bao ? 'warn' : 'ok');
          } ?>
        <li>
          <a href="van-ban-den.php?xem=<?= (int)$v['id'] ?>"><b>Số đến <?= (int)$v['so_den'] ?></b> · <?= e($v['co_quan_gui']) ?></a>
          <p><?= e(mb_strimwidth($v['trich_yeu'], 0, 140, '…')) ?></p>
          <small><?= $han ? badge('Hạn ' . fmt_date($han), $tone) : badge('Không hạn') ?> <?= badge(TRANG_THAI_DEN[$v['trang_thai']] ?? '', 'muted') ?> <?php if ($v['do_khan']): ?><?= badge(DO_KHAN[$v['do_khan']], 'bad') ?><?php endif; ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>Dự thảo cần thao tác</h2>
    <?php if (!$du_thao): ?><p class="empty">Không có dự thảo nào cần thao tác.</p><?php else: ?>
    <ul class="list">
      <?php foreach ($du_thao as $d): ?>
        <li>
          <a href="soan-thao.php?id=<?= (int)$d['id'] ?>"><b><?= e($d['loai_ten'] ?? 'Văn bản') ?></b> · <?= e(mb_strimwidth($d['trich_yeu'] ?: '(chưa có trích yếu)', 0, 90, '…')) ?></a>
          <small><?= badge(TRANG_THAI_DU_THAO[$d['trang_thai']] ?? '', $d['trang_thai'] === 'tra_lai' ? 'bad' : ($d['trang_thai'] === 'da_duyet' ? 'ok' : 'warn')) ?> <?= badge(HE_VAN_BAN[$d['he']] ?? '') ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>

<div class="grid-2">
  <section class="card">
    <h2>Số lượng văn bản theo tháng (<?= $nam ?>)</h2>
    <div class="bars" role="img" aria-label="Biểu đồ văn bản đi và đến theo tháng">
      <?php foreach ($theo_thang as $m => $c): ?>
        <div class="bar-col" title="Tháng <?= $m ?>: <?= $c['di'] ?> đi, <?= $c['den'] ?> đến">
          <div class="bar-pair">
            <span class="bar bar-di" style="height:<?= round($c['di'] / $max_thang * 100) ?>%"></span>
            <span class="bar bar-den" style="height:<?= round($c['den'] / $max_thang * 100) ?>%"></span>
          </div>
          <small><?= $m ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="legend"><span class="sw sw-di"></span> Văn bản đi <span class="sw sw-den"></span> Văn bản đến</p>
  </section>

  <section class="card">
    <h2>Văn bản đi mới ban hành</h2>
    <?php if (!$moi_di): ?><p class="empty">Chưa có văn bản đi.</p><?php else: ?>
    <ul class="list">
      <?php foreach ($moi_di as $v): ?>
        <li><a href="van-ban-di.php?xem=<?= (int)$v['id'] ?>"><b><?= e($v['so_ky_hieu']) ?></b> · <?= e($v['loai_ten'] ?? '') ?></a>
          <p><?= e(mb_strimwidth($v['trich_yeu'], 0, 120, '…')) ?></p><small><?= fmt_date($v['ngay_ban_hanh']) ?></small></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>

<section class="card quick">
  <h2>Công cụ nhanh</h2>
  <div class="quick-grid">
    <a href="tra-cuu.php"><b>Tra cứu thể thức</b><span>NĐ 30/2020 và HD 05-HD/VPTW</span></a>
    <a href="kiem-tra.php"><b>Kiểm tra file Word</b><span>Phát hiện lỗi & chuẩn hóa tự động</span></a>
    <a href="soan-thao.php?moi=1"><b>Soạn theo mẫu</b><span>Xuất .docx đúng thể thức</span></a>
    <a href="bieu-mau.php"><b>Biểu mẫu</b><span>Thư viện đơn từ, mẫu văn bản</span></a>
  </div>
</section>
<?php
page_footer();
