<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/nghiepvu.php';

$u = require_perm('vb_di.xem');

/* ---------- Số tiếp theo (AJAX) ---------- */
if (in_str('so_tiep') !== '') {
    header('Content-Type: application/json; charset=utf-8');
    $loai = loai_by_id(in_int('loai_id'));
    if (!$loai) {
        echo json_encode(['ok' => false]);
        exit;
    }
    $ngay = in_date('ngay') ?: date('Y-m-d');
    $pv = pham_vi_so_di($loai['he'], (int)substr($ngay, 0, 4), $loai);
    $cur = (int)q_val('SELECT gia_tri FROM hc_bo_dem WHERE khoa = ?', [$pv]);
    $next = max($cur, max_so_di($pv)) + 1;
    $dv = in_int('don_vi_id') ? (string)q_val('SELECT viet_tat FROM hc_don_vi WHERE id = ?', [in_int('don_vi_id')]) : '';
    echo json_encode(['ok' => true, 'so' => $next, 'text' => so_label($loai['he'], so_ky_hieu($loai['he'], $next, $loai, $dv))], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- Tải file ---------- */
if (in_str('tai') !== '') {
    $r = q_one('SELECT * FROM hc_van_ban_di WHERE id = ?', [in_int('tai')]);
    if (!$r || !$r['tep_tin']) {
        http_response_code(404);
        exit('Không có file.');
    }
    gui_tep(QLHC_ROOT . '/uploads/' . $r['tep_tin'], $r['ten_tep_goc'] ?: basename($r['tep_tin']), mime_of($r['tep_tin']), in_str('xem_file') !== '');
}

/* ---------- Bộ lọc dùng chung cho danh sách & xuất Excel ---------- */
function loc_di(): array
{
    $w = ['1=1'];
    $p = [];
    $nam = in_int('nam', (int)date('Y'));
    if ($nam > 0) { $w[] = 'v.nam = ?'; $p[] = $nam; }
    $he = in_str('he');
    if (isset(HE_VAN_BAN[$he])) { $w[] = 'v.he = ?'; $p[] = $he; }
    if (in_int('loai')) { $w[] = 'v.loai_id = ?'; $p[] = in_int('loai'); }
    if (in_int('dv')) { $w[] = 'v.don_vi_soan_id = ?'; $p[] = in_int('dv'); }
    if (in_date('tu')) { $w[] = 'v.ngay_ban_hanh >= ?'; $p[] = in_date('tu'); }
    if (in_date('den')) { $w[] = 'v.ngay_ban_hanh <= ?'; $p[] = in_date('den'); }
    $kw = in_str('q');
    if ($kw !== '') {
        $w[] = '(v.so_ky_hieu LIKE ? OR v.trich_yeu LIKE ? OR v.nguoi_ky LIKE ? OR v.noi_nhan LIKE ?)';
        array_push($p, "%$kw%", "%$kw%", "%$kw%", "%$kw%");
    }
    return [implode(' AND ', $w), $p];
}

const SQL_DI = 'SELECT v.*, l.ten AS loai_ten, d.ten AS don_vi_ten, d.viet_tat AS don_vi_vt, u.ho_ten AS nguoi_nhap
    FROM hc_van_ban_di v
    LEFT JOIN hc_loai_van_ban l ON l.id = v.loai_id
    LEFT JOIN hc_don_vi d ON d.id = v.don_vi_soan_id
    LEFT JOIN hc_nguoi_dung u ON u.id = v.nguoi_tao';

/* ---------- Xuất Excel (CSV UTF-8) ---------- */
if (in_str('xuat') === 'csv') {
    [$w, $p] = loc_di();
    $rows = q(SQL_DI . " WHERE $w ORDER BY v.ngay_ban_hanh, v.so", $p)->fetchAll();
    $fh = fopen('php://temp', 'w+');
    fwrite($fh, "\xEF\xBB\xBF");
    fputcsv($fh, ['STT', 'Số, ký hiệu', 'Ngày ban hành', 'Loại', 'Trích yếu', 'Người ký', 'Chức vụ', 'Đơn vị soạn', 'Nơi nhận', 'Số bản', 'Độ khẩn', 'Ghi chú']);
    foreach ($rows as $i => $r) {
        fputcsv($fh, [$i + 1, $r['so_ky_hieu'], fmt_date($r['ngay_ban_hanh']), $r['loai_ten'], $r['trich_yeu'], $r['nguoi_ky'], $r['chuc_vu_ky'],
            $r['don_vi_ten'], str_replace("\n", '; ', (string)$r['noi_nhan']), $r['so_ban'], DO_KHAN[$r['do_khan']] ?? '', $r['ghi_chu']]);
    }
    rewind($fh);
    gui_noi_dung(stream_get_contents($fh), 'So van ban di ' . in_int('nam', (int)date('Y')) . '.csv', 'text/csv; charset=utf-8');
}

/* ---------- POST: thêm / sửa / xóa ---------- */
if (is_post()) {
    csrf_check();
    if (!can('vb_di.sua')) {
        http_response_code(403);
        exit('Không có quyền.');
    }
    $action = in_str('action');
    $id = in_int('id');
    try {
        if ($action === 'luu') {
            $trich_yeu = in_str('trich_yeu');
            if ($trich_yeu === '') {
                throw new RuntimeException('Vui lòng nhập trích yếu.');
            }
            $tep = luu_tep('tep', 'van-ban-di');
            if ($id) {
                $old = q_one('SELECT * FROM hc_van_ban_di WHERE id = ?', [$id]);
                if (!$old) throw new RuntimeException('Không tìm thấy văn bản.');
                $ngay = in_date('ngay_ban_hanh') ?: $old['ngay_ban_hanh'];
                q('UPDATE hc_van_ban_di SET ngay_ban_hanh=?, trich_yeu=?, nguoi_ky=?, chuc_vu_ky=?, noi_nhan=?, so_ban=?, do_khan=?, ghi_chu=? WHERE id=?', [
                    $ngay, $trich_yeu, in_str('nguoi_ky'), in_str('chuc_vu_ky'), in_str('noi_nhan'),
                    max(1, in_int('so_ban', 1)), array_key_exists(in_str('do_khan'), DO_KHAN) ? in_str('do_khan') : '', in_str('ghi_chu'), $id,
                ]);
                if ($tep) {
                    xoa_tep($old['tep_tin']);
                    q('UPDATE hc_van_ban_di SET tep_tin=?, ten_tep_goc=? WHERE id=?', [$tep[0], $tep[1], $id]);
                }
                log_action('sua', 'van_ban_di', $id, $old['so_ky_hieu']);
                flash('ok', 'Đã cập nhật văn bản ' . $old['so_ky_hieu'] . '.');
            } else {
                db()->beginTransaction();
                try {
                    $di = vao_so_di([
                        'he' => in_str('he'), 'loai_id' => in_int('loai_id'), 'ngay_ban_hanh' => in_date('ngay_ban_hanh') ?: date('Y-m-d'),
                        'trich_yeu' => $trich_yeu, 'nguoi_ky' => in_str('nguoi_ky'), 'chuc_vu_ky' => in_str('chuc_vu_ky'),
                        'don_vi_soan_id' => in_int('don_vi_soan_id'), 'noi_nhan' => in_str('noi_nhan'), 'so_ban' => in_int('so_ban', 1),
                        'do_khan' => array_key_exists(in_str('do_khan'), DO_KHAN) ? in_str('do_khan') : '', 'ghi_chu' => in_str('ghi_chu'),
                        'so_tay' => in_int('so_tay'),
                    ]);
                    if ($tep) {
                        q('UPDATE hc_van_ban_di SET tep_tin=?, ten_tep_goc=? WHERE id=?', [$tep[0], $tep[1], $di['id']]);
                    }
                    db()->commit();
                } catch (Throwable $ex) {
                    db()->rollBack();
                    if ($tep) xoa_tep($tep[0]);
                    throw $ex;
                }
                $id = (int)$di['id'];
                log_action('vao_so', 'van_ban_di', $id, $di['so_ky_hieu']);
                flash('ok', 'Đã cấp số: ' . so_label($di['he'], $di['so_ky_hieu']));
            }
            redirect('van-ban-di.php?xem=' . $id);
        }
        if ($action === 'xoa') {
            if (!is_admin()) throw new RuntimeException('Chỉ quản trị viên được xóa văn bản đã vào sổ.');
            $old = q_one('SELECT * FROM hc_van_ban_di WHERE id = ?', [$id]);
            if ($old) {
                xoa_tep($old['tep_tin']);
                q('DELETE FROM hc_van_ban_di WHERE id = ?', [$id]);
                q("UPDATE hc_du_thao SET trang_thai='da_duyet', van_ban_di_id=NULL WHERE van_ban_di_id = ?", [$id]);
                log_action('xoa', 'van_ban_di', $id, $old['so_ky_hieu'] . ' – ' . $old['trich_yeu']);
                flash('ok', 'Đã xóa ' . $old['so_ky_hieu'] . '. Lưu ý: số này sẽ để trống trong sổ.');
            }
            redirect('van-ban-di.php');
        }
        if ($action === 'xoa_tep') {
            $old = q_one('SELECT * FROM hc_van_ban_di WHERE id = ?', [$id]);
            if ($old) {
                xoa_tep($old['tep_tin']);
                q('UPDATE hc_van_ban_di SET tep_tin=NULL, ten_tep_goc=NULL WHERE id=?', [$id]);
                log_action('xoa_tep', 'van_ban_di', $id);
            }
            redirect('van-ban-di.php?sua=' . $id);
        }
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        redirect('van-ban-di.php?sua=' . $id);
    }
}

/* ---------- Xem chi tiết ---------- */
if (in_int('xem')) {
    $r = q_one(SQL_DI . ' WHERE v.id = ?', [in_int('xem')]);
    if (!$r) {
        flash('error', 'Không tìm thấy văn bản.');
        redirect('van-ban-di.php');
    }
    $actions = '<a class="btn btn-ghost" href="van-ban-di.php?nam=' . (int)$r['nam'] . '">← Sổ văn bản đi</a>';
    if (can('vb_di.sua')) $actions .= ' <a class="btn" href="van-ban-di.php?sua=' . (int)$r['id'] . '">Sửa</a>';
    page_header(so_label($r['he'], $r['so_ky_hieu']), ['sub' => $r['loai_ten'] . ' · ' . (HE_VAN_BAN[$r['he']] ?? ''), 'actions' => $actions]);
    ?>
    <div class="card">
      <table class="tbl tbl-kv"><tbody>
        <tr><th>Số, ký hiệu</th><td><b><?= e($r['so_ky_hieu']) ?></b></td></tr>
        <tr><th>Ngày ban hành</th><td><?= fmt_date($r['ngay_ban_hanh']) ?></td></tr>
        <tr><th>Loại văn bản</th><td><?= e($r['loai_ten']) ?></td></tr>
        <tr><th>Trích yếu</th><td><?= nl2br(e($r['trich_yeu'])) ?></td></tr>
        <tr><th>Người ký</th><td><?= e($r['nguoi_ky']) ?><?= $r['chuc_vu_ky'] ? ' – ' . e($r['chuc_vu_ky']) : '' ?></td></tr>
        <tr><th>Đơn vị soạn thảo</th><td><?= e($r['don_vi_ten']) ?></td></tr>
        <tr><th>Nơi nhận</th><td><?= nl2br(e($r['noi_nhan'])) ?></td></tr>
        <tr><th>Số bản · Độ khẩn</th><td><?= (int)$r['so_ban'] ?> · <?= e(DO_KHAN[$r['do_khan']] ?? '') ?></td></tr>
        <?php if ($r['ghi_chu']): ?><tr><th>Ghi chú</th><td><?= nl2br(e($r['ghi_chu'])) ?></td></tr><?php endif; ?>
        <tr><th>File văn bản</th><td><?php if ($r['tep_tin']): ?>
            <a class="btn btn-sm" href="van-ban-di.php?tai=<?= (int)$r['id'] ?>">Tải về: <?= e($r['ten_tep_goc']) ?></a>
            <?php if (preg_match('/\.(pdf|png|jpe?g)$/i', $r['tep_tin'])): ?><a class="btn btn-sm btn-ghost" target="_blank" href="van-ban-di.php?tai=<?= (int)$r['id'] ?>&amp;xem_file=1">Xem</a><?php endif; ?>
          <?php else: ?><span class="muted">Chưa đính kèm</span><?php endif; ?></td></tr>
        <?php if ($r['du_thao_id']): ?><tr><th>Dự thảo gốc</th><td><a href="soan-thao.php?id=<?= (int)$r['du_thao_id'] ?>">Dự thảo #<?= (int)$r['du_thao_id'] ?></a></td></tr><?php endif; ?>
        <tr><th>Người vào sổ</th><td><?= e($r['nguoi_nhap']) ?> · <?= fmt_datetime($r['tao_luc']) ?></td></tr>
      </tbody></table>
      <?php if ($r['du_thao_id'] && preg_match('/\.docx$/i', (string)$r['tep_tin'])): ?>
        <p class="note">File Word do hệ thống tạo khi ban hành. Sau khi ký, nên tải lên bản scan/ký số (PDF) qua nút "Sửa" để lưu trữ.</p>
      <?php endif; ?>
    </div>
    <?php
    page_footer();
    exit;
}

/* ---------- Form thêm / sửa ---------- */
if (isset($_GET['sua'])) {
    if (!can('vb_di.sua')) {
        redirect('van-ban-di.php');
    }
    $id = in_int('sua');
    $r = $id ? q_one('SELECT * FROM hc_van_ban_di WHERE id = ?', [$id]) : null;
    if ($id && !$r) {
        redirect('van-ban-di.php');
    }
    $he = $r['he'] ?? (in_str('he') === 'dang' ? 'dang' : 'hc');
    $r = $r ?: ['id' => 0, 'he' => $he, 'loai_id' => 0, 'ngay_ban_hanh' => date('Y-m-d'), 'trich_yeu' => '', 'nguoi_ky' => '', 'chuc_vu_ky' => '',
        'don_vi_soan_id' => 0, 'noi_nhan' => '', 'so_ban' => 1, 'do_khan' => '', 'ghi_chu' => '', 'tep_tin' => null, 'ten_tep_goc' => null];
    page_header($id ? 'Sửa văn bản đi ' . $r['so_ky_hieu'] : 'Vào sổ văn bản đi', [
        'crumb' => '<a href="van-ban-di.php">Sổ văn bản đi</a> / ' . ($id ? 'Sửa' : 'Thêm mới'),
        'sub' => $id ? 'Không đổi được số, loại văn bản sau khi đã cấp số.' : 'Dùng cho văn bản không soạn bằng công cụ (vd. soạn ngoài, văn bản ký số). Số được cấp tự động theo sổ.',
    ]);
    ?>
    <form class="card form" method="post" enctype="multipart/form-data" data-so-tiep>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <?php if (!$id): ?>
      <div class="seg seg-sm">
        <?php foreach (HE_VAN_BAN as $k => $v): ?>
          <label class="<?= $he === $k ? 'on' : '' ?>"><input type="radio" name="he" value="<?= e($k) ?>" <?= $he === $k ? 'checked' : '' ?> data-he-switch> <?= e($v) ?></label>
        <?php endforeach; ?>
      </div>
      <div class="row row-3">
        <label>Loại văn bản
          <select name="loai_id" required data-loai>
            <?php foreach (loai_van_ban() as $l): ?>
              <option value="<?= (int)$l['id'] ?>" data-he="<?= e($l['he']) ?>"><?= e($l['ten']) ?><?= $l['viet_tat'] ? ' (' . e($l['viet_tat']) . ')' : '' ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Đơn vị soạn thảo
          <select name="don_vi_soan_id" data-dv>
            <option value="0">—</option>
            <?php foreach (don_vi_list() as $dv): ?><option value="<?= (int)$dv['id'] ?>"><?= e($dv['ten']) ?> (<?= e($dv['viet_tat']) ?>)</option><?php endforeach; ?>
          </select>
        </label>
        <label>Số (để trống = tự cấp)<input type="number" name="so_tay" min="1" placeholder="tự động"><small data-so-preview></small></label>
      </div>
      <?php endif; ?>
      <div class="row row-3">
        <label>Ngày ban hành<input type="date" name="ngay_ban_hanh" value="<?= e($r['ngay_ban_hanh']) ?>" required data-ngay></label>
        <label>Số bản<input type="number" name="so_ban" min="1" value="<?= (int)$r['so_ban'] ?>"></label>
        <label>Độ khẩn<select name="do_khan"><?= select_options(DO_KHAN, $r['do_khan']) ?></select></label>
      </div>
      <label>Trích yếu<textarea name="trich_yeu" rows="2" required><?= e($r['trich_yeu']) ?></textarea></label>
      <div class="row">
        <label>Người ký<input name="nguoi_ky" value="<?= e($r['nguoi_ky']) ?>"></label>
        <label>Chức vụ người ký<input name="chuc_vu_ky" value="<?= e($r['chuc_vu_ky']) ?>"></label>
      </div>
      <label>Nơi nhận <small>(mỗi nơi một dòng)</small><textarea name="noi_nhan" rows="3"><?= e($r['noi_nhan']) ?></textarea></label>
      <label>Ghi chú<textarea name="ghi_chu" rows="2"><?= e($r['ghi_chu']) ?></textarea></label>
      <label>File văn bản (PDF, Word…, tối đa <?= (int)cfg('upload_max_mb', 20) ?> MB)
        <input type="file" name="tep" accept=".pdf,.doc,.docx,.odt,.jpg,.jpeg,.png,.zip">
        <?php if ($r['tep_tin']): ?><small>Hiện có: <?= e($r['ten_tep_goc']) ?> — chọn file mới để thay thế.</small><?php endif; ?>
      </label>
      <div class="form-actions">
        <button class="btn" type="submit" name="action" value="luu"><?= $id ? 'Lưu thay đổi' : 'Cấp số & vào sổ' ?></button>
        <a class="btn btn-ghost" href="<?= $id ? 'van-ban-di.php?xem=' . $id : 'van-ban-di.php' ?>">Hủy</a>
      </div>
    </form>
    <?php if ($id): ?>
      <div class="danger-zone">
        <?php if ($r['tep_tin']): ?>
        <form method="post" onsubmit="return confirm('Gỡ file đính kèm?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-danger-ghost btn-sm" name="action" value="xoa_tep">Gỡ file đính kèm</button></form>
        <?php endif; ?>
        <?php if (is_admin()): ?>
        <form method="post" onsubmit="return confirm('Xóa văn bản khỏi sổ? Số đã cấp sẽ để trống.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-danger-ghost btn-sm" name="action" value="xoa">Xóa khỏi sổ</button></form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php
    page_footer();
    exit;
}

/* ---------- Danh sách ---------- */
[$w, $p] = loc_di();
$pg = paginate((int)q_val("SELECT COUNT(*) FROM hc_van_ban_di v WHERE $w", $p), 25);
$rows = q(SQL_DI . " WHERE $w ORDER BY v.ngay_ban_hanh DESC, v.so DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p)->fetchAll();
$qs = $_GET;
$qs['xuat'] = 'csv';
unset($qs['page']);

$actions = '<a class="btn btn-ghost" href="?' . e(http_build_query($qs)) . '">Xuất Excel</a>';
if (can('vb_di.sua')) $actions .= ' <a class="btn" href="van-ban-di.php?sua=0">+ Vào sổ</a>';
page_header('Sổ văn bản đi', ['sub' => 'Tổng: ' . $pg['total'] . ' văn bản', 'actions' => $actions]);
?>
<form class="card filters" method="get">
  <select name="nam"><?php foreach (nam_options('hc_van_ban_di') as $y): ?><option value="<?= $y ?>" <?= in_int('nam', (int)date('Y')) === $y ? 'selected' : '' ?>>Năm <?= $y ?></option><?php endforeach; ?><option value="0" <?= isset($_GET['nam']) && in_int('nam') === 0 ? 'selected' : '' ?>>Tất cả năm</option></select>
  <select name="he"><option value="">Tất cả hệ</option><?= select_options(HE_VAN_BAN, in_str('he')) ?></select>
  <select name="loai"><option value="0">Tất cả loại</option><?php foreach (loai_van_ban() as $l): ?><option value="<?= (int)$l['id'] ?>" <?= in_int('loai') === (int)$l['id'] ? 'selected' : '' ?>><?= e($l['ten']) ?><?= $l['he'] === 'dang' ? ' (Đảng)' : '' ?></option><?php endforeach; ?></select>
  <select name="dv"><option value="0">Tất cả đơn vị</option><?php foreach (don_vi_list() as $dv): ?><option value="<?= (int)$dv['id'] ?>" <?= in_int('dv') === (int)$dv['id'] ? 'selected' : '' ?>><?= e($dv['ten']) ?></option><?php endforeach; ?></select>
  <input type="date" name="tu" value="<?= e(in_str('tu')) ?>" title="Từ ngày">
  <input type="date" name="den" value="<?= e(in_str('den')) ?>" title="Đến ngày">
  <input type="search" name="q" value="<?= e(in_str('q')) ?>" placeholder="Số, trích yếu, người ký…">
  <button class="btn btn-ghost" type="submit">Lọc</button>
</form>

<div class="card">
  <?php if (!$rows): ?><p class="empty">Không có văn bản nào phù hợp.</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Số, ký hiệu</th><th>Ngày</th><th>Loại</th><th>Trích yếu</th><th>Người ký</th><th>Đơn vị</th><th>File</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td class="nowrap"><a href="van-ban-di.php?xem=<?= (int)$r['id'] ?>"><b><?= e($r['so_ky_hieu']) ?></b></a><?php if ($r['do_khan']): ?><br><?= badge(DO_KHAN[$r['do_khan']], 'bad') ?><?php endif; ?></td>
        <td class="nowrap"><?= fmt_date($r['ngay_ban_hanh']) ?></td>
        <td><?= e($r['loai_ten']) ?></td>
        <td><?= e(mb_strimwidth($r['trich_yeu'], 0, 160, '…')) ?></td>
        <td><?= e($r['nguoi_ky']) ?></td>
        <td><?= e($r['don_vi_vt']) ?></td>
        <td><?php if ($r['tep_tin']): ?><a href="van-ban-di.php?tai=<?= (int)$r['id'] ?>" title="<?= e($r['ten_tep_goc']) ?>">Tải</a><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?= pager_html($pg) ?>
  <?php endif; ?>
</div>
<?php
page_footer();
