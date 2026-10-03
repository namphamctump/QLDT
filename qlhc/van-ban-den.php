<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/nghiepvu.php';

$u = require_perm('vb_den.xu_ly');

function co_the_xem_den(array $r): bool
{
    return can('vb_den.xem_tat_ca') || (int)$r['nguoi_xu_ly_id'] === uid();
}

const SQL_DEN = 'SELECT v.*, u.ho_ten AS nguoi_xu_ly, d.ten AS don_vi_xu_ly
    FROM hc_van_ban_den v
    LEFT JOIN hc_nguoi_dung u ON u.id = v.nguoi_xu_ly_id
    LEFT JOIN hc_don_vi d ON d.id = v.don_vi_xu_ly_id';

function loc_den(): array
{
    $w = ['1=1'];
    $p = [];
    if (!can('vb_den.xem_tat_ca')) {
        $w[] = 'v.nguoi_xu_ly_id = ?';
        $p[] = uid();
    }
    $loc = in_str('loc');
    $nam = in_int('nam', $loc !== '' ? 0 : (int)date('Y'));
    if ($nam > 0) { $w[] = 'v.nam = ?'; $p[] = $nam; }
    if (isset(TRANG_THAI_DEN[in_str('tt')])) { $w[] = 'v.trang_thai = ?'; $p[] = in_str('tt'); }
    if ($loc === 'qua_han') {
        $w[] = "v.trang_thai <> 'hoan_thanh' AND v.han_xu_ly IS NOT NULL AND v.han_xu_ly < CURDATE()";
    } elseif ($loc === 'sap_han') {
        $w[] = "v.trang_thai <> 'hoan_thanh' AND v.han_xu_ly BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
        $p[] = max(0, (int)setting('canh_bao_han_ngay', '3'));
    } elseif ($loc === 'cua_toi') {
        $w[] = 'v.nguoi_xu_ly_id = ?';
        $p[] = uid();
    }
    if (in_int('xl')) { $w[] = 'v.nguoi_xu_ly_id = ?'; $p[] = in_int('xl'); }
    if (array_key_exists(in_str('khan'), DO_KHAN) && in_str('khan') !== '') { $w[] = 'v.do_khan = ?'; $p[] = in_str('khan'); }
    $kw = in_str('q');
    if ($kw !== '') {
        $w[] = '(v.co_quan_gui LIKE ? OR v.so_ky_hieu_goc LIKE ? OR v.trich_yeu LIKE ? OR v.so_den = ?)';
        array_push($p, "%$kw%", "%$kw%", "%$kw%", (int)$kw);
    }
    return [implode(' AND ', $w), $p];
}

function han_badge(array $r): string
{
    if ($r['trang_thai'] === 'hoan_thanh') {
        return badge('Hoàn thành', 'ok');
    }
    if (!$r['han_xu_ly']) {
        return badge(TRANG_THAI_DEN[$r['trang_thai']] ?? '', 'muted');
    }
    $con = (int)floor((strtotime($r['han_xu_ly']) - strtotime(date('Y-m-d'))) / 86400);
    if ($con < 0) return badge('Quá hạn ' . (-$con) . ' ngày', 'bad');
    if ($con === 0) return badge('Hạn hôm nay', 'warn');
    if ($con <= (int)setting('canh_bao_han_ngay', '3')) return badge('Còn ' . $con . ' ngày', 'warn');
    return badge('Hạn ' . fmt_date($r['han_xu_ly']), 'muted');
}

/* ---------- Tải file ---------- */
if (in_str('tai') !== '') {
    $r = q_one('SELECT * FROM hc_van_ban_den WHERE id = ?', [in_int('tai')]);
    if (!$r || !$r['tep_tin'] || !co_the_xem_den($r)) {
        http_response_code(404);
        exit('Không có file.');
    }
    gui_tep(QLHC_ROOT . '/uploads/' . $r['tep_tin'], $r['ten_tep_goc'] ?: basename($r['tep_tin']), mime_of($r['tep_tin']), in_str('xem_file') !== '');
}

/* ---------- Xuất Excel ---------- */
if (in_str('xuat') === 'csv') {
    [$w, $p] = loc_den();
    $rows = q(SQL_DEN . " WHERE $w ORDER BY v.nam, v.so_den", $p)->fetchAll();
    $fh = fopen('php://temp', 'w+');
    fwrite($fh, "\xEF\xBB\xBF");
    fputcsv($fh, ['Số đến', 'Ngày đến', 'Cơ quan gửi', 'Số, ký hiệu', 'Ngày văn bản', 'Loại', 'Trích yếu', 'Độ khẩn', 'Người xử lý', 'Đơn vị xử lý', 'Hạn xử lý', 'Trạng thái', 'Ý kiến chỉ đạo', 'Kết quả']);
    foreach ($rows as $r) {
        fputcsv($fh, [$r['so_den'], fmt_date($r['ngay_den']), $r['co_quan_gui'], $r['so_ky_hieu_goc'], fmt_date($r['ngay_van_ban']), $r['loai_ten'],
            $r['trich_yeu'], DO_KHAN[$r['do_khan']] ?? '', $r['nguoi_xu_ly'], $r['don_vi_xu_ly'], fmt_date($r['han_xu_ly']),
            TRANG_THAI_DEN[$r['trang_thai']] ?? '', $r['y_kien_chi_dao'], $r['ket_qua']]);
    }
    rewind($fh);
    gui_noi_dung(stream_get_contents($fh), 'So van ban den.csv', 'text/csv; charset=utf-8');
}

/* ---------- POST ---------- */
if (is_post()) {
    csrf_check();
    $action = in_str('action');
    $id = in_int('id');
    $back = 'van-ban-den.php?xem=' . $id;
    try {
        if ($action === 'luu') {
            if (!can('vb_den.sua')) throw new RuntimeException('Không có quyền vào sổ văn bản đến.');
            $co_quan = in_str('co_quan_gui');
            $trich_yeu = in_str('trich_yeu');
            if ($co_quan === '' || $trich_yeu === '') throw new RuntimeException('Vui lòng nhập cơ quan gửi và trích yếu.');
            $ngay_den = in_date('ngay_den') ?: date('Y-m-d');
            $khan = array_key_exists(in_str('do_khan'), DO_KHAN) ? in_str('do_khan') : '';
            $xl = in_int('nguoi_xu_ly_id') ?: null;
            $dv = in_int('don_vi_xu_ly_id') ?: null;
            $tep = luu_tep('tep', 'van-ban-den');
            $fields = [$ngay_den, $co_quan, in_str('so_ky_hieu_goc'), in_date('ngay_van_ban'), in_str('loai_ten'), $trich_yeu, $khan, $xl, $dv, in_date('han_xu_ly')];
            if ($id) {
                $old = q_one('SELECT * FROM hc_van_ban_den WHERE id = ?', [$id]);
                if (!$old) throw new RuntimeException('Không tìm thấy văn bản.');
                q('UPDATE hc_van_ban_den SET ngay_den=?, co_quan_gui=?, so_ky_hieu_goc=?, ngay_van_ban=?, loai_ten=?, trich_yeu=?, do_khan=?, nguoi_xu_ly_id=?, don_vi_xu_ly_id=?, han_xu_ly=? WHERE id=?', array_merge($fields, [$id]));
                if ($xl && $old['trang_thai'] === 'moi' && (int)$old['nguoi_xu_ly_id'] !== $xl) {
                    q("UPDATE hc_van_ban_den SET trang_thai='dang_xu_ly' WHERE id=?", [$id]);
                }
                if ($tep) {
                    xoa_tep($old['tep_tin']);
                    q('UPDATE hc_van_ban_den SET tep_tin=?, ten_tep_goc=? WHERE id=?', [$tep[0], $tep[1], $id]);
                }
                log_action('sua', 'van_ban_den', $id, 'Số đến ' . $old['so_den']);
                flash('ok', 'Đã cập nhật văn bản đến số ' . $old['so_den'] . '.');
            } else {
                $nam = (int)substr($ngay_den, 0, 4);
                db()->beginTransaction();
                try {
                    $so_tay = in_int('so_tay');
                    if ($so_tay > 0) {
                        if (q_val('SELECT id FROM hc_van_ban_den WHERE nam = ? AND so_den = ?', [$nam, $so_tay])) {
                            throw new RuntimeException('Số đến ' . $so_tay . ' đã có trong sổ năm ' . $nam . '.');
                        }
                        $so = $so_tay;
                        dong_bo_bo_dem('den:' . $nam, $so);
                    } else {
                        $so = cap_so_den($nam);
                    }
                    q('INSERT INTO hc_van_ban_den (nam, so_den, ngay_den, co_quan_gui, so_ky_hieu_goc, ngay_van_ban, loai_ten, trich_yeu, do_khan, nguoi_xu_ly_id, don_vi_xu_ly_id, han_xu_ly, trang_thai, tep_tin, ten_tep_goc, nguoi_tao)
                       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                        array_merge([$nam, $so], $fields, [$xl ? 'dang_xu_ly' : 'moi', $tep[0] ?? null, $tep[1] ?? null, uid()]));
                    $id = (int)db()->lastInsertId();
                    db()->commit();
                } catch (Throwable $ex) {
                    db()->rollBack();
                    if ($tep) xoa_tep($tep[0]);
                    throw $ex;
                }
                log_action('vao_so', 'van_ban_den', $id, 'Số đến ' . $so . ' – ' . $co_quan);
                flash('ok', 'Đã vào sổ: số đến ' . $so . '/' . $nam . '.');
            }
            redirect('van-ban-den.php?xem=' . $id);
        }

        $r = q_one('SELECT * FROM hc_van_ban_den WHERE id = ?', [$id]);
        if (!$r) throw new RuntimeException('Không tìm thấy văn bản.');

        if ($action === 'chi_dao') {
            if (!can('vb_den.chi_dao') && !can('vb_den.sua')) throw new RuntimeException('Không có quyền phân công.');
            $xl = in_int('nguoi_xu_ly_id') ?: null;
            q('UPDATE hc_van_ban_den SET y_kien_chi_dao=?, nguoi_xu_ly_id=?, don_vi_xu_ly_id=?, han_xu_ly=?, trang_thai=IF(trang_thai=\'moi\' AND ? IS NOT NULL, \'dang_xu_ly\', trang_thai) WHERE id=?',
                [in_str('y_kien_chi_dao'), $xl, in_int('don_vi_xu_ly_id') ?: null, in_date('han_xu_ly'), $xl, $id]);
            log_action('chi_dao', 'van_ban_den', $id, in_str('y_kien_chi_dao'));
            flash('ok', 'Đã lưu phân công / ý kiến chỉ đạo.');
            redirect($back);
        }

        if ($action === 'xu_ly') {
            if ((int)$r['nguoi_xu_ly_id'] !== uid() && !can('vb_den.sua')) throw new RuntimeException('Bạn không phải người được giao xử lý.');
            $tt = isset(TRANG_THAI_DEN[in_str('trang_thai')]) ? in_str('trang_thai') : $r['trang_thai'];
            q('UPDATE hc_van_ban_den SET ket_qua=?, trang_thai=?, hoan_thanh_luc=IF(?=\'hoan_thanh\', COALESCE(hoan_thanh_luc, NOW()), NULL) WHERE id=?',
                [in_str('ket_qua'), $tt, $tt, $id]);
            log_action('xu_ly', 'van_ban_den', $id, (TRANG_THAI_DEN[$tt] ?? $tt) . ': ' . in_str('ket_qua'));
            flash('ok', 'Đã cập nhật kết quả xử lý.');
            redirect($back);
        }

        if ($action === 'xoa') {
            if (!is_admin()) throw new RuntimeException('Chỉ quản trị viên được xóa.');
            xoa_tep($r['tep_tin']);
            q('DELETE FROM hc_van_ban_den WHERE id = ?', [$id]);
            log_action('xoa', 'van_ban_den', $id, 'Số đến ' . $r['so_den'] . ' – ' . $r['trich_yeu']);
            flash('ok', 'Đã xóa văn bản đến số ' . $r['so_den'] . '.');
            redirect('van-ban-den.php');
        }
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        redirect($id ? $back : 'van-ban-den.php?sua=0');
    }
}

/* ---------- Xem chi tiết ---------- */
if (in_int('xem')) {
    $r = q_one(SQL_DEN . ' WHERE v.id = ?', [in_int('xem')]);
    if (!$r || !co_the_xem_den($r)) {
        flash('error', 'Không tìm thấy văn bản hoặc bạn không được giao xử lý văn bản này.');
        redirect('van-ban-den.php');
    }
    $users = nguoi_dung_list();
    $dvs = don_vi_list();
    $actions = '<a class="btn btn-ghost" href="van-ban-den.php">← Sổ văn bản đến</a>';
    if (can('vb_den.sua')) $actions .= ' <a class="btn" href="van-ban-den.php?sua=' . (int)$r['id'] . '">Sửa</a>';
    page_header('Văn bản đến số ' . (int)$r['so_den'] . '/' . (int)$r['nam'], ['sub' => $r['co_quan_gui'], 'actions' => $actions]);
    ?>
    <div class="grid-2">
      <section class="card">
        <h2>Thông tin văn bản</h2>
        <table class="tbl tbl-kv"><tbody>
          <tr><th>Số đến · Ngày đến</th><td><b><?= (int)$r['so_den'] ?></b> · <?= fmt_date($r['ngay_den']) ?></td></tr>
          <tr><th>Cơ quan gửi</th><td><?= e($r['co_quan_gui']) ?></td></tr>
          <tr><th>Số, ký hiệu</th><td><?= e($r['so_ky_hieu_goc']) ?></td></tr>
          <tr><th>Ngày văn bản</th><td><?= fmt_date($r['ngay_van_ban']) ?></td></tr>
          <tr><th>Loại</th><td><?= e($r['loai_ten']) ?></td></tr>
          <tr><th>Trích yếu</th><td><?= nl2br(e($r['trich_yeu'])) ?></td></tr>
          <tr><th>Độ khẩn</th><td><?= $r['do_khan'] ? badge(DO_KHAN[$r['do_khan']], 'bad') : 'Thường' ?></td></tr>
          <tr><th>Trạng thái</th><td><?= badge(TRANG_THAI_DEN[$r['trang_thai']] ?? '', $r['trang_thai'] === 'hoan_thanh' ? 'ok' : 'warn') ?> <?= han_badge($r) ?></td></tr>
          <tr><th>File</th><td><?php if ($r['tep_tin']): ?>
              <a class="btn btn-sm" href="van-ban-den.php?tai=<?= (int)$r['id'] ?>">Tải: <?= e($r['ten_tep_goc']) ?></a>
              <?php if (preg_match('/\.(pdf|png|jpe?g)$/i', $r['tep_tin'])): ?><a class="btn btn-sm btn-ghost" target="_blank" href="van-ban-den.php?tai=<?= (int)$r['id'] ?>&amp;xem_file=1">Xem</a><?php endif; ?>
            <?php else: ?><span class="muted">Chưa đính kèm</span><?php endif; ?></td></tr>
        </tbody></table>
      </section>

      <section class="card">
        <h2>Phân công &amp; chỉ đạo</h2>
        <?php if (can('vb_den.chi_dao') || can('vb_den.sua')): ?>
        <form class="form" method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <label>Ý kiến chỉ đạo<textarea name="y_kien_chi_dao" rows="3"><?= e($r['y_kien_chi_dao']) ?></textarea></label>
          <div class="row row-3">
            <label>Người xử lý<select name="nguoi_xu_ly_id"><option value="0">—</option><?php foreach ($users as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$r['nguoi_xu_ly_id'] === (int)$x['id'] ? 'selected' : '' ?>><?= e($x['ho_ten']) ?></option><?php endforeach; ?></select></label>
            <label>Đơn vị xử lý<select name="don_vi_xu_ly_id"><option value="0">—</option><?php foreach ($dvs as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$r['don_vi_xu_ly_id'] === (int)$x['id'] ? 'selected' : '' ?>><?= e($x['ten']) ?></option><?php endforeach; ?></select></label>
            <label>Hạn xử lý<input type="date" name="han_xu_ly" value="<?= e($r['han_xu_ly']) ?>"></label>
          </div>
          <div class="form-actions"><button class="btn" name="action" value="chi_dao">Lưu phân công</button></div>
        </form>
        <?php else: ?>
          <table class="tbl tbl-kv"><tbody>
            <tr><th>Ý kiến chỉ đạo</th><td><?= nl2br(e($r['y_kien_chi_dao'])) ?: '<span class="muted">—</span>' ?></td></tr>
            <tr><th>Người xử lý</th><td><?= e($r['nguoi_xu_ly']) ?></td></tr>
            <tr><th>Đơn vị xử lý</th><td><?= e($r['don_vi_xu_ly']) ?></td></tr>
            <tr><th>Hạn xử lý</th><td><?= fmt_date($r['han_xu_ly']) ?></td></tr>
          </tbody></table>
        <?php endif; ?>
      </section>
    </div>

    <section class="card">
      <h2>Kết quả xử lý</h2>
      <?php if ((int)$r['nguoi_xu_ly_id'] === uid() || can('vb_den.sua')): ?>
      <form class="form" method="post">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <label>Kết quả / báo cáo xử lý<textarea name="ket_qua" rows="3" placeholder="vd: Đã tham mưu Công văn số 125/TTDV-ĐT ngày ..."><?= e($r['ket_qua']) ?></textarea></label>
        <div class="row row-3">
          <label>Trạng thái<select name="trang_thai"><?= select_options(TRANG_THAI_DEN, $r['trang_thai']) ?></select></label>
        </div>
        <div class="form-actions"><button class="btn" name="action" value="xu_ly">Cập nhật</button>
          <?php if (can('du_thao.soan')): ?><a class="btn btn-ghost" href="soan-thao.php?moi=1">Soạn văn bản trả lời →</a><?php endif; ?></div>
      </form>
      <?php else: ?>
        <p><?= nl2br(e($r['ket_qua'])) ?: '<span class="muted">Chưa có kết quả.</span>' ?></p>
      <?php endif; ?>
      <?php if ($r['hoan_thanh_luc']): ?><p class="note">Hoàn thành lúc <?= fmt_datetime($r['hoan_thanh_luc']) ?>.</p><?php endif; ?>
    </section>

    <?php if (is_admin()): ?>
      <div class="danger-zone"><form method="post" onsubmit="return confirm('Xóa văn bản đến này khỏi sổ?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger-ghost btn-sm" name="action" value="xoa">Xóa khỏi sổ</button></form></div>
    <?php endif; ?>
    <?php
    page_footer();
    exit;
}

/* ---------- Form thêm / sửa ---------- */
if (isset($_GET['sua'])) {
    if (!can('vb_den.sua')) {
        redirect('van-ban-den.php');
    }
    $id = in_int('sua');
    $r = $id ? q_one('SELECT * FROM hc_van_ban_den WHERE id = ?', [$id]) : null;
    if ($id && !$r) {
        redirect('van-ban-den.php');
    }
    $r = $r ?: ['id' => 0, 'ngay_den' => date('Y-m-d'), 'co_quan_gui' => '', 'so_ky_hieu_goc' => '', 'ngay_van_ban' => '', 'loai_ten' => '',
        'trich_yeu' => '', 'do_khan' => '', 'nguoi_xu_ly_id' => 0, 'don_vi_xu_ly_id' => 0, 'han_xu_ly' => '', 'tep_tin' => null, 'ten_tep_goc' => null];
    $nam = (int)date('Y');
    $so_tiep = max((int)q_val('SELECT gia_tri FROM hc_bo_dem WHERE khoa = ?', ['den:' . $nam]), (int)q_val('SELECT COALESCE(MAX(so_den),0) FROM hc_van_ban_den WHERE nam = ?', [$nam])) + 1;
    $co_quan_cu = q('SELECT co_quan_gui, COUNT(*) c FROM hc_van_ban_den GROUP BY co_quan_gui ORDER BY c DESC LIMIT 100')->fetchAll(PDO::FETCH_COLUMN);
    page_header($id ? 'Sửa văn bản đến số ' . $r['so_den'] : 'Vào sổ văn bản đến', [
        'crumb' => '<a href="van-ban-den.php">Sổ văn bản đến</a> / ' . ($id ? 'Sửa' : 'Thêm mới'),
        'sub' => $id ? '' : 'Số đến tiếp theo năm ' . $nam . ': ' . $so_tiep,
    ]);
    ?>
    <form class="card form" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <div class="row row-3">
        <label>Ngày đến<input type="date" name="ngay_den" value="<?= e($r['ngay_den']) ?>" required></label>
        <?php if (!$id): ?><label>Số đến (để trống = tự cấp)<input type="number" min="1" name="so_tay" placeholder="<?= $so_tiep ?>"></label><?php endif; ?>
        <label>Độ khẩn<select name="do_khan"><?= select_options(DO_KHAN, $r['do_khan']) ?></select></label>
      </div>
      <label>Cơ quan gửi<input name="co_quan_gui" value="<?= e($r['co_quan_gui']) ?>" required list="dl-co-quan">
        <datalist id="dl-co-quan"><?php foreach ($co_quan_cu as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></label>
      <div class="row row-3">
        <label>Số, ký hiệu văn bản<input name="so_ky_hieu_goc" value="<?= e($r['so_ky_hieu_goc']) ?>" placeholder="vd: 123/ĐHYDCT-TCCB"></label>
        <label>Ngày văn bản<input type="date" name="ngay_van_ban" value="<?= e($r['ngay_van_ban']) ?>"></label>
        <label>Loại văn bản<input name="loai_ten" value="<?= e($r['loai_ten']) ?>" list="dl-loai">
          <datalist id="dl-loai"><?php foreach (q("SELECT DISTINCT ten FROM hc_loai_van_ban ORDER BY ten")->fetchAll(PDO::FETCH_COLUMN) as $l): ?><option value="<?= e($l) ?>"><?php endforeach; ?></datalist></label>
      </div>
      <label>Trích yếu<textarea name="trich_yeu" rows="2" required><?= e($r['trich_yeu']) ?></textarea></label>
      <div class="row row-3">
        <label>Người xử lý<select name="nguoi_xu_ly_id"><option value="0">— Chờ phân công —</option><?php foreach (nguoi_dung_list() as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$r['nguoi_xu_ly_id'] === (int)$x['id'] ? 'selected' : '' ?>><?= e($x['ho_ten']) ?> (<?= e(VAI_TRO[$x['vai_tro']] ?? '') ?>)</option><?php endforeach; ?></select></label>
        <label>Đơn vị xử lý<select name="don_vi_xu_ly_id"><option value="0">—</option><?php foreach (don_vi_list() as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$r['don_vi_xu_ly_id'] === (int)$x['id'] ? 'selected' : '' ?>><?= e($x['ten']) ?></option><?php endforeach; ?></select></label>
        <label>Hạn xử lý<input type="date" name="han_xu_ly" value="<?= e($r['han_xu_ly']) ?>"></label>
      </div>
      <label>File văn bản (scan PDF, ảnh, Word…)<input type="file" name="tep" accept=".pdf,.doc,.docx,.odt,.jpg,.jpeg,.png,.zip,.rar,.xls,.xlsx">
        <?php if ($r['tep_tin']): ?><small>Hiện có: <?= e($r['ten_tep_goc']) ?> — chọn file mới để thay thế.</small><?php endif; ?></label>
      <div class="form-actions">
        <button class="btn" name="action" value="luu"><?= $id ? 'Lưu thay đổi' : 'Vào sổ' ?></button>
        <a class="btn btn-ghost" href="<?= $id ? 'van-ban-den.php?xem=' . $id : 'van-ban-den.php' ?>">Hủy</a>
      </div>
    </form>
    <?php
    page_footer();
    exit;
}

/* ---------- Danh sách ---------- */
[$w, $p] = loc_den();
$pg = paginate((int)q_val("SELECT COUNT(*) FROM hc_van_ban_den v WHERE $w", $p), 25);
$rows = q(SQL_DEN . " WHERE $w ORDER BY v.nam DESC, v.so_den DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p)->fetchAll();
$qs = $_GET;
$qs['xuat'] = 'csv';
unset($qs['page']);
$actions = '<a class="btn btn-ghost" href="?' . e(http_build_query($qs)) . '">Xuất Excel</a>';
if (can('vb_den.sua')) $actions .= ' <a class="btn" href="van-ban-den.php?sua=0">+ Vào sổ</a>';
page_header('Sổ văn bản đến', ['sub' => (can('vb_den.xem_tat_ca') ? 'Toàn bộ văn bản đến' : 'Văn bản được giao cho bạn') . ' · ' . $pg['total'] . ' văn bản', 'actions' => $actions]);
$loc = in_str('loc');
?>
<div class="tabs">
  <a class="<?= $loc === '' ? 'on' : '' ?>" href="van-ban-den.php">Tất cả</a>
  <a class="<?= $loc === 'cua_toi' ? 'on' : '' ?>" href="?loc=cua_toi">Giao cho tôi</a>
  <a class="<?= $loc === 'sap_han' ? 'on' : '' ?>" href="?loc=sap_han">Sắp đến hạn</a>
  <a class="<?= $loc === 'qua_han' ? 'on' : '' ?>" href="?loc=qua_han">Quá hạn</a>
</div>
<form class="card filters" method="get">
  <input type="hidden" name="loc" value="<?= e($loc) ?>">
  <select name="nam"><option value="0">Tất cả năm</option><?php foreach (nam_options('hc_van_ban_den') as $y): ?><option value="<?= $y ?>" <?= in_int('nam', $loc !== '' ? 0 : (int)date('Y')) === $y ? 'selected' : '' ?>>Năm <?= $y ?></option><?php endforeach; ?></select>
  <select name="tt"><option value="">Mọi trạng thái</option><?= select_options(TRANG_THAI_DEN, in_str('tt')) ?></select>
  <select name="khan"><option value="">Mọi độ khẩn</option><?= select_options(array_slice(DO_KHAN, 1, null, true), in_str('khan')) ?></select>
  <?php if (can('vb_den.xem_tat_ca')): ?>
  <select name="xl"><option value="0">Mọi người xử lý</option><?php foreach (nguoi_dung_list() as $x): ?><option value="<?= (int)$x['id'] ?>" <?= in_int('xl') === (int)$x['id'] ? 'selected' : '' ?>><?= e($x['ho_ten']) ?></option><?php endforeach; ?></select>
  <?php endif; ?>
  <input type="search" name="q" value="<?= e(in_str('q')) ?>" placeholder="Số đến, cơ quan gửi, trích yếu…">
  <button class="btn btn-ghost" type="submit">Lọc</button>
</form>
<div class="card">
  <?php if (!$rows): ?><p class="empty">Không có văn bản nào.</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Số đến</th><th>Ngày đến</th><th>Cơ quan gửi · Số ký hiệu</th><th>Trích yếu</th><th>Người xử lý</th><th>Tình trạng</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['do_khan'] ? 'row-urgent' : '' ?>">
        <td><a href="van-ban-den.php?xem=<?= (int)$r['id'] ?>"><b><?= (int)$r['so_den'] ?></b></a><small class="muted">/<?= (int)$r['nam'] ?></small></td>
        <td class="nowrap"><?= fmt_date($r['ngay_den']) ?></td>
        <td><?= e($r['co_quan_gui']) ?><br><small class="muted"><?= e($r['so_ky_hieu_goc']) ?><?= $r['ngay_van_ban'] ? ' · ' . fmt_date($r['ngay_van_ban']) : '' ?></small></td>
        <td><a href="van-ban-den.php?xem=<?= (int)$r['id'] ?>"><?= e(mb_strimwidth($r['trich_yeu'], 0, 150, '…')) ?></a><?php if ($r['do_khan']): ?> <?= badge(DO_KHAN[$r['do_khan']], 'bad') ?><?php endif; ?></td>
        <td><?= e($r['nguoi_xu_ly'] ?: '—') ?></td>
        <td><?= han_badge($r) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?= pager_html($pg) ?>
  <?php endif; ?>
</div>
<?php
page_footer();
