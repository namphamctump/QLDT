<?php
require __DIR__ . '/includes/bootstrap.php';

$quan_ly = can('bieu_mau.quan_ly');

/* ---------- Tải file (công khai nếu biểu mẫu công khai) ---------- */
if (in_int('tai')) {
    $r = q_one('SELECT * FROM hc_bieu_mau WHERE id = ?', [in_int('tai')]);
    if (!$r || (!(int)$r['cong_khai'] && !user())) {
        http_response_code(404);
        exit('Không tìm thấy biểu mẫu.');
    }
    q('UPDATE hc_bieu_mau SET luot_tai = luot_tai + 1 WHERE id = ?', [$r['id']]);
    gui_tep(QLHC_ROOT . '/uploads/' . $r['tep_tin'], $r['ten_tep_goc'], mime_of($r['tep_tin']), in_str('xem') !== '');
}

/* ---------- POST quản lý ---------- */
if (is_post()) {
    require_perm('bieu_mau.quan_ly');
    csrf_check();
    $id = in_int('id');
    $action = in_str('action');
    try {
        if ($action === 'luu') {
            $ten = in_str('ten');
            if ($ten === '') throw new RuntimeException('Vui lòng nhập tên biểu mẫu.');
            $nhom = in_str('nhom') ?: 'Khác';
            $tep = luu_tep('tep', 'bieu-mau');
            if ($id) {
                $old = q_one('SELECT * FROM hc_bieu_mau WHERE id = ?', [$id]);
                if (!$old) throw new RuntimeException('Không tìm thấy biểu mẫu.');
                q('UPDATE hc_bieu_mau SET ten=?, nhom=?, mo_ta=?, cong_khai=?, thu_tu=?, cap_nhat_luc=NOW() WHERE id=?',
                    [$ten, $nhom, in_str('mo_ta'), in_int('cong_khai') ? 1 : 0, in_int('thu_tu'), $id]);
                if ($tep) {
                    xoa_tep($old['tep_tin']);
                    q('UPDATE hc_bieu_mau SET tep_tin=?, ten_tep_goc=? WHERE id=?', [$tep[0], $tep[1], $id]);
                }
                log_action('sua', 'bieu_mau', $id, $ten);
            } else {
                if (!$tep) throw new RuntimeException('Vui lòng chọn file biểu mẫu.');
                q('INSERT INTO hc_bieu_mau (ten, nhom, mo_ta, tep_tin, ten_tep_goc, cong_khai, thu_tu, nguoi_tao, cap_nhat_luc) VALUES (?,?,?,?,?,?,?,?,NOW())',
                    [$ten, $nhom, in_str('mo_ta'), $tep[0], $tep[1], in_int('cong_khai') ? 1 : 0, in_int('thu_tu'), uid()]);
                $id = (int)db()->lastInsertId();
                log_action('tao', 'bieu_mau', $id, $ten);
            }
            flash('ok', 'Đã lưu biểu mẫu "' . $ten . '".');
            redirect('bieu-mau.php?quan_ly=1');
        }
        if ($action === 'xoa') {
            $old = q_one('SELECT * FROM hc_bieu_mau WHERE id = ?', [$id]);
            if ($old) {
                xoa_tep($old['tep_tin']);
                q('DELETE FROM hc_bieu_mau WHERE id = ?', [$id]);
                log_action('xoa', 'bieu_mau', $id, $old['ten']);
                flash('ok', 'Đã xóa biểu mẫu.');
            }
            redirect('bieu-mau.php?quan_ly=1');
        }
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        redirect('bieu-mau.php?quan_ly=1' . ($id ? '&sua=' . $id : ''));
    }
}

$nhoms = q('SELECT DISTINCT nhom FROM hc_bieu_mau ORDER BY nhom')->fetchAll(PDO::FETCH_COLUMN);
$nhom_mac_dinh = ['Đơn từ học viên', 'Hồ sơ đăng ký khóa học', 'Mẫu văn bản hành chính', 'Biểu mẫu nội bộ', 'Văn bản quy định'];
$nhom_goi_y = array_values(array_unique(array_merge($nhoms, $nhom_mac_dinh)));

/* ---------- Giao diện quản lý ---------- */
if ($quan_ly && in_str('quan_ly') !== '') {
    $sua = in_int('sua');
    $r = $sua ? q_one('SELECT * FROM hc_bieu_mau WHERE id = ?', [$sua]) : null;
    $r = $r ?: ['id' => 0, 'ten' => '', 'nhom' => '', 'mo_ta' => '', 'cong_khai' => 1, 'thu_tu' => 0, 'ten_tep_goc' => ''];
    $rows = q('SELECT * FROM hc_bieu_mau ORDER BY nhom, thu_tu, ten')->fetchAll();
    page_header('Quản lý biểu mẫu', ['crumb' => '<a href="bieu-mau.php">Văn bản - Biểu mẫu</a> / Quản lý', 'actions' => '<a class="btn btn-ghost" href="bieu-mau.php">Xem trang công khai</a>']);
    ?>
    <form class="card form" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <h2><?= $r['id'] ? 'Sửa biểu mẫu' : 'Thêm biểu mẫu' ?></h2>
      <div class="row">
        <label>Tên biểu mẫu<input name="ten" value="<?= e($r['ten']) ?>" required></label>
        <label>Nhóm<input name="nhom" value="<?= e($r['nhom']) ?>" list="dl-nhom" placeholder="vd: Đơn từ học viên">
          <datalist id="dl-nhom"><?php foreach ($nhom_goi_y as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist></label>
      </div>
      <label>Mô tả<textarea name="mo_ta" rows="2"><?= e($r['mo_ta']) ?></textarea></label>
      <div class="row row-3">
        <label>File<input type="file" name="tep" <?= $r['id'] ? '' : 'required' ?> accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.zip,.ppt,.pptx">
          <?php if ($r['ten_tep_goc']): ?><small>Hiện có: <?= e($r['ten_tep_goc']) ?></small><?php endif; ?></label>
        <label>Thứ tự<input type="number" name="thu_tu" value="<?= (int)$r['thu_tu'] ?>"></label>
        <label class="check"><input type="checkbox" name="cong_khai" value="1" <?= (int)$r['cong_khai'] ? 'checked' : '' ?>> Công khai (không cần đăng nhập)</label>
      </div>
      <div class="form-actions"><button class="btn" name="action" value="luu">Lưu</button><?php if ($r['id']): ?> <a class="btn btn-ghost" href="bieu-mau.php?quan_ly=1">Hủy</a><?php endif; ?></div>
    </form>
    <div class="card">
      <?php if (!$rows): ?><p class="empty">Chưa có biểu mẫu.</p><?php else: ?>
      <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>Nhóm</th><th>Tên</th><th>File</th><th>Công khai</th><th>Lượt tải</th><th></th></tr></thead>
        <tbody><?php foreach ($rows as $x): ?>
          <tr><td><?= e($x['nhom']) ?></td><td><?= e($x['ten']) ?></td><td><a href="bieu-mau.php?tai=<?= (int)$x['id'] ?>"><?= e($x['ten_tep_goc']) ?></a></td>
            <td><?= (int)$x['cong_khai'] ? badge('Công khai', 'ok') : badge('Nội bộ') ?></td><td><?= (int)$x['luot_tai'] ?></td>
            <td class="nowrap"><a class="btn btn-sm btn-ghost" href="bieu-mau.php?quan_ly=1&amp;sua=<?= (int)$x['id'] ?>">Sửa</a>
              <form method="post" class="inline" onsubmit="return confirm('Xóa biểu mẫu này?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-sm btn-danger-ghost" name="action" value="xoa">Xóa</button></form></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php endif; ?>
    </div>
    <?php
    page_footer();
    exit;
}

/* ---------- Trang công khai ---------- */
$kw = in_str('q');
$where = user() ? '1=1' : 'cong_khai = 1';
$params = [];
if ($kw !== '') {
    $where .= ' AND (ten LIKE ? OR mo_ta LIKE ? OR nhom LIKE ?)';
    array_push($params, "%$kw%", "%$kw%", "%$kw%");
}
$rows = q("SELECT * FROM hc_bieu_mau WHERE $where ORDER BY nhom, thu_tu, ten", $params)->fetchAll();
$by = [];
foreach ($rows as $x) {
    $by[$x['nhom']][] = $x;
}
page_header('Văn bản - Biểu mẫu', [
    'sub' => 'Tải các mẫu đơn, hồ sơ và văn bản dùng cho học viên và cán bộ.',
    'actions' => $quan_ly ? '<a class="btn" href="bieu-mau.php?quan_ly=1">Quản lý biểu mẫu</a>' : '',
]);
?>
<form class="card filters" method="get"><input type="search" name="q" value="<?= e($kw) ?>" placeholder="Tìm biểu mẫu…"><button class="btn btn-ghost">Tìm</button></form>
<?php if (!$by): ?>
  <div class="card"><p class="empty"><?= $kw !== '' ? 'Không tìm thấy biểu mẫu phù hợp.' : 'Chưa có biểu mẫu nào được đăng.' ?></p></div>
<?php endif; ?>
<?php foreach ($by as $nhom => $ds): ?>
  <section class="card">
    <h2><?= e($nhom) ?> <small class="muted">(<?= count($ds) ?>)</small></h2>
    <ul class="files">
      <?php foreach ($ds as $x): $ext = strtolower(pathinfo($x['tep_tin'], PATHINFO_EXTENSION)); ?>
        <li>
          <span class="ext ext-<?= e($ext) ?>"><?= e(strtoupper($ext)) ?></span>
          <div><b><?= e($x['ten']) ?></b><?php if (!(int)$x['cong_khai']): ?> <?= badge('Nội bộ') ?><?php endif; ?>
            <?php if ($x['mo_ta']): ?><p><?= e($x['mo_ta']) ?></p><?php endif; ?>
            <small class="muted">Cập nhật <?= fmt_date($x['cap_nhat_luc'] ?: $x['tao_luc']) ?> · <?= (int)$x['luot_tai'] ?> lượt tải</small></div>
          <div class="file-actions">
            <?php if ($ext === 'pdf'): ?><a class="btn btn-sm btn-ghost" target="_blank" href="bieu-mau.php?tai=<?= (int)$x['id'] ?>&amp;xem=1">Xem</a><?php endif; ?>
            <a class="btn btn-sm" href="bieu-mau.php?tai=<?= (int)$x['id'] ?>">Tải về</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>
<?php
page_footer();
