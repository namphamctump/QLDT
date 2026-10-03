<?php
require __DIR__ . '/includes/bootstrap.php';

require_admin();
$where = '1=1';
$p = [];
if (in_int('u')) { $where .= ' AND k.nguoi_dung_id = ?'; $p[] = in_int('u'); }
if (in_str('dt') !== '') { $where .= ' AND k.doi_tuong = ?'; $p[] = in_str('dt'); }
if (in_str('q') !== '') { $where .= ' AND k.chi_tiet LIKE ?'; $p[] = '%' . in_str('q') . '%'; }
$pg = paginate((int)q_val("SELECT COUNT(*) FROM hc_nhat_ky k WHERE $where", $p), 50);
$rows = q("SELECT k.*, u.ho_ten FROM hc_nhat_ky k LEFT JOIN hc_nguoi_dung u ON u.id = k.nguoi_dung_id WHERE $where ORDER BY k.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p)->fetchAll();
$dts = q('SELECT DISTINCT doi_tuong FROM hc_nhat_ky ORDER BY doi_tuong')->fetchAll(PDO::FETCH_COLUMN);
page_header('Nhật ký thao tác', ['sub' => $pg['total'] . ' bản ghi']);
?>
<form class="card filters" method="get">
  <select name="u"><option value="0">Mọi người dùng</option><?php foreach (nguoi_dung_list(false) as $x): ?><option value="<?= (int)$x['id'] ?>" <?= in_int('u') === (int)$x['id'] ? 'selected' : '' ?>><?= e($x['ho_ten']) ?></option><?php endforeach; ?></select>
  <select name="dt"><option value="">Mọi đối tượng</option><?php foreach ($dts as $d): ?><option <?= in_str('dt') === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select>
  <input type="search" name="q" value="<?= e(in_str('q')) ?>" placeholder="Nội dung…">
  <button class="btn btn-ghost">Lọc</button>
</form>
<div class="card">
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Thời gian</th><th>Người dùng</th><th>Hành động</th><th>Đối tượng</th><th>Chi tiết</th><th>IP</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td class="nowrap"><?= fmt_datetime($r['thoi_gian']) ?></td><td><?= e($r['ho_ten'] ?? '—') ?></td><td><?= e($r['hanh_dong']) ?></td>
    <td><?= e($r['doi_tuong']) ?><?= $r['doi_tuong_id'] ? ' #' . (int)$r['doi_tuong_id'] : '' ?></td><td><?= e(mb_strimwidth((string)$r['chi_tiet'], 0, 160, '…')) ?></td><td><small><?= e($r['ip']) ?></small></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?= pager_html($pg) ?>
</div>
<?php
page_footer();
