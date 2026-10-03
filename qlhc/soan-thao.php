<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/vanban.php';
require __DIR__ . '/includes/nghiepvu.php';

$u = require_perm('du_thao.soan');

const TRUONG_DU_THAO = ['he', 'loai_id', 'don_vi_id', 'trich_yeu', 'kinh_gui', 'noi_dung', 'nguoi_ky_id',
    'quyen_han', 'thay_mat', 'chuc_vu', 'ho_ten', 'noi_nhan', 'do_khan'];

function du_thao_tu_post(): array
{
    $f = [];
    foreach (TRUONG_DU_THAO as $k) {
        $v = $_POST[$k] ?? '';
        $f[$k] = is_string($v) ? trim(str_replace("\r\n", "\n", $v)) : '';
    }
    $f['he'] = $f['he'] === 'dang' ? 'dang' : 'hc';
    $f['loai_id'] = (int)$f['loai_id'];
    $f['don_vi_id'] = (int)$f['don_vi_id'];
    if (!array_key_exists($f['do_khan'], DO_KHAN)) {
        $f['do_khan'] = '';
    }
    return $f;
}

function co_the_xem(array $d): bool
{
    return (int)$d['nguoi_tao'] === uid() || can('du_thao.xem_tat_ca');
}

function co_the_sua(array $d): bool
{
    if ($d['trang_thai'] === 'da_ban_hanh') {
        return false;
    }
    if (is_admin()) {
        return true;
    }
    if ((int)$d['nguoi_tao'] === uid() && in_array($d['trang_thai'], ['nhap', 'tra_lai'], true)) {
        return true;
    }
    // Lãnh đạo được chỉnh khi đang duyệt
    return $d['trang_thai'] === 'trinh_ky' && can('du_thao.duyet');
}

function lay_du_thao(int $id): array
{
    $d = q_one('SELECT * FROM hc_du_thao WHERE id = ?', [$id]);
    if (!$d || !co_the_xem($d)) {
        flash('error', 'Không tìm thấy dự thảo hoặc bạn không có quyền xem.');
        redirect('soan-thao.php');
    }
    $d['f'] = json_decode((string)$d['du_lieu'], true) ?: [];
    return $d;
}

/* ============================ Xem trước (AJAX) ============================ */
if (is_post() && in_str('action') === 'xem_truoc') {
    csrf_check();
    $f = du_thao_tu_post();
    header('Content-Type: text/html; charset=utf-8');
    echo HtmlWriter::build(new VanBanLayout(chuan_bi_van_ban($f)));
    exit;
}

/* ============================ Tải .docx ============================ */
if (in_str('xuat') === 'docx') {
    if (is_post()) {
        // Xuất trực tiếp từ form (chưa lưu)
        csrf_check();
        $f = du_thao_tu_post();
        $vb = chuan_bi_van_ban($f);
        gui_noi_dung(DocxWriter::build(new VanBanLayout($vb), $vb['trich_yeu']), 'Du thao - ' . ten_file_van_ban($vb), mime_of('a.docx'));
    }
    $d = lay_du_thao(in_int('id'));
    $so = 0;
    $ngay = null;
    if ($d['van_ban_di_id']) {
        $di = q_one('SELECT * FROM hc_van_ban_di WHERE id = ?', [$d['van_ban_di_id']]);
        if ($di) {
            $so = (int)$di['so'];
            $ngay = $di['ngay_ban_hanh'];
        }
    }
    $vb = chuan_bi_van_ban($d['f'], $so, $ngay);
    if ($so > 0) {
        $vb['so_text'] = so_label($vb['he'], $di['so_ky_hieu']);
        $vb['so_ky_hieu'] = $di['so_ky_hieu'];
    }
    gui_noi_dung(DocxWriter::build(new VanBanLayout($vb), $vb['trich_yeu']), ($so ? '' : 'Du thao - ') . ten_file_van_ban($vb), mime_of('a.docx'));
}

/* ============================ Xử lý POST ============================ */
if (is_post()) {
    csrf_check();
    $action = in_str('action');
    $id = in_int('id');

    try {
        if ($action === 'luu' || $action === 'luu_trinh') {
            $f = du_thao_tu_post();
            $loai = loai_by_id($f['loai_id']);
            if (!$loai || $loai['he'] !== $f['he']) {
                throw new RuntimeException('Vui lòng chọn loại văn bản.');
            }
            if ($f['trich_yeu'] === '') {
                throw new RuntimeException('Vui lòng nhập trích yếu.');
            }
            $json = json_encode($f, JSON_UNESCAPED_UNICODE);
            if ($id) {
                $d = lay_du_thao($id);
                if (!co_the_sua($d)) {
                    throw new RuntimeException('Dự thảo này không còn được chỉnh sửa.');
                }
                q('UPDATE hc_du_thao SET he=?, loai_id=?, trich_yeu=?, du_lieu=?, cap_nhat_luc=NOW() WHERE id=?',
                    [$f['he'], $f['loai_id'], mb_substr($f['trich_yeu'], 0, 500), $json, $id]);
                log_action('sua', 'du_thao', $id, $f['trich_yeu']);
            } else {
                q('INSERT INTO hc_du_thao (he, loai_id, trich_yeu, du_lieu, trang_thai, nguoi_tao, cap_nhat_luc) VALUES (?,?,?,?,?,?,NOW())',
                    [$f['he'], $f['loai_id'], mb_substr($f['trich_yeu'], 0, 500), $json, 'nhap', uid()]);
                $id = (int)db()->lastInsertId();
                log_action('tao', 'du_thao', $id, $f['trich_yeu']);
            }
            if ($action === 'luu_trinh') {
                q("UPDATE hc_du_thao SET trang_thai='trinh_ky', cap_nhat_luc=NOW() WHERE id=? AND trang_thai IN ('nhap','tra_lai')", [$id]);
                log_action('trinh_ky', 'du_thao', $id);
                flash('ok', 'Đã lưu và trình ký dự thảo.');
            } else {
                flash('ok', 'Đã lưu dự thảo.');
            }
            redirect('soan-thao.php?id=' . $id);
        }

        $d = lay_du_thao($id);

        if ($action === 'duyet' || $action === 'tra_lai') {
            if (!can('du_thao.duyet') || $d['trang_thai'] !== 'trinh_ky') {
                throw new RuntimeException('Không thể thực hiện thao tác này.');
            }
            $moi = $action === 'duyet' ? 'da_duyet' : 'tra_lai';
            q('UPDATE hc_du_thao SET trang_thai=?, y_kien=?, nguoi_duyet=?, cap_nhat_luc=NOW() WHERE id=?', [$moi, in_str('y_kien'), uid(), $id]);
            log_action($action, 'du_thao', $id, in_str('y_kien'));
            flash('ok', $action === 'duyet' ? 'Đã duyệt dự thảo, chuyển văn thư ban hành.' : 'Đã trả lại dự thảo để chỉnh sửa.');
            redirect('soan-thao.php?id=' . $id);
        }

        if ($action === 'trinh_ky') {
            if ((int)$d['nguoi_tao'] !== uid() && !is_admin()) {
                throw new RuntimeException('Chỉ người soạn mới trình ký được.');
            }
            q("UPDATE hc_du_thao SET trang_thai='trinh_ky', cap_nhat_luc=NOW() WHERE id=? AND trang_thai IN ('nhap','tra_lai')", [$id]);
            log_action('trinh_ky', 'du_thao', $id);
            flash('ok', 'Đã trình ký.');
            redirect('soan-thao.php?id=' . $id);
        }

        if ($action === 'ban_hanh') {
            $duoc = (can('du_thao.ban_hanh') && $d['trang_thai'] === 'da_duyet') || (is_admin() && $d['trang_thai'] !== 'da_ban_hanh');
            if (!$duoc) {
                throw new RuntimeException('Dự thảo phải được lãnh đạo duyệt trước khi ban hành.');
            }
            $f = $d['f'];
            $ngay = in_date('ngay_ban_hanh') ?: date('Y-m-d');
            db()->beginTransaction();
            try {
                $di = vao_so_di([
                    'he' => $f['he'] ?? 'hc', 'loai_id' => (int)$f['loai_id'], 'ngay_ban_hanh' => $ngay,
                    'trich_yeu' => $f['trich_yeu'] ?? '', 'nguoi_ky' => $f['ho_ten'] ?? '', 'chuc_vu_ky' => $f['chuc_vu'] ?? '',
                    'don_vi_soan_id' => (int)($f['don_vi_id'] ?? 0), 'noi_nhan' => $f['noi_nhan'] ?? '',
                    'so_ban' => in_int('so_ban', 1), 'do_khan' => $f['do_khan'] ?? '', 'du_thao_id' => $id,
                    'so_tay' => in_int('so_tay', 0),
                ]);
                $vb = chuan_bi_van_ban($f, (int)$di['so'], $ngay);
                $vb['so_text'] = so_label($vb['he'], $di['so_ky_hieu']);
                $vb['so_ky_hieu'] = $di['so_ky_hieu'];
                $rel = luu_noi_dung(DocxWriter::build(new VanBanLayout($vb), $vb['trich_yeu']), 'van-ban-di', 'docx');
                q('UPDATE hc_van_ban_di SET tep_tin=?, ten_tep_goc=? WHERE id=?', [$rel, ten_file_van_ban($vb), $di['id']]);
                q("UPDATE hc_du_thao SET trang_thai='da_ban_hanh', van_ban_di_id=?, cap_nhat_luc=NOW() WHERE id=?", [$di['id'], $id]);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            log_action('ban_hanh', 'van_ban_di', (int)$di['id'], $di['so_ky_hieu']);
            flash('ok', 'Đã ban hành: ' . so_label($di['he'], $di['so_ky_hieu']) . '. File Word có số đã lưu vào sổ văn bản đi.');
            redirect('van-ban-di.php?xem=' . $di['id']);
        }

        if ($action === 'nhan_ban') {
            $f = $d['f'];
            q('INSERT INTO hc_du_thao (he, loai_id, trich_yeu, du_lieu, trang_thai, nguoi_tao, cap_nhat_luc) VALUES (?,?,?,?,?,?,NOW())',
                [$f['he'] ?? 'hc', (int)($f['loai_id'] ?? 0), $d['trich_yeu'], $d['du_lieu'], 'nhap', uid()]);
            $nid = (int)db()->lastInsertId();
            log_action('nhan_ban', 'du_thao', $nid, 'từ #' . $id);
            flash('ok', 'Đã tạo bản sao dự thảo.');
            redirect('soan-thao.php?id=' . $nid);
        }

        if ($action === 'xoa') {
            $duoc = $d['trang_thai'] !== 'da_ban_hanh'
                && (is_admin() || ((int)$d['nguoi_tao'] === uid() && in_array($d['trang_thai'], ['nhap', 'tra_lai'], true)));
            if (!$duoc) {
                throw new RuntimeException('Không thể xóa dự thảo này.');
            }
            q('DELETE FROM hc_du_thao WHERE id = ?', [$id]);
            log_action('xoa', 'du_thao', $id, $d['trich_yeu']);
            flash('ok', 'Đã xóa dự thảo.');
            redirect('soan-thao.php');
        }
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        redirect('soan-thao.php' . ($id ? '?id=' . $id : '?moi=1'));
    }
}

/* ============================ Biên tập / xem ============================ */
if (in_int('id') || in_str('moi') !== '') {
    $id = in_int('id');
    if ($id) {
        $d = lay_du_thao($id);
        $f = $d['f'];
        $sua = co_the_sua($d);
    } else {
        $d = ['id' => 0, 'trang_thai' => 'nhap', 'nguoi_tao' => uid(), 'y_kien' => '', 'van_ban_di_id' => null];
        $he = in_str('he', 'hc') === 'dang' ? 'dang' : 'hc';
        $first = q_one('SELECT * FROM hc_loai_van_ban WHERE he = ? ORDER BY thu_tu LIMIT 1', [$he]);
        $nk = q_one('SELECT * FROM hc_nguoi_ky WHERE he = ? ORDER BY thu_tu LIMIT 1', [$he]);
        $f = [
            'he' => $he, 'loai_id' => $first['id'] ?? 0, 'don_vi_id' => (int)($u['don_vi_id'] ?? 0),
            'trich_yeu' => '', 'kinh_gui' => '', 'noi_dung' => '',
            'nguoi_ky_id' => $nk['id'] ?? '', 'quyen_han' => $nk['quyen_han'] ?? '', 'thay_mat' => $nk['thay_mat'] ?? '',
            'chuc_vu' => $nk['chuc_vu'] ?? '', 'ho_ten' => $nk['ho_ten'] ?? '',
            'noi_nhan' => $he === 'dang' ? "Lưu Văn phòng" : "Như trên\nLưu: VT", 'do_khan' => '',
        ];
        $sua = true;
    }
    $f += array_fill_keys(TRUONG_DU_THAO, '');

    $loais = loai_van_ban();
    $nguoi_ky = q('SELECT * FROM hc_nguoi_ky ORDER BY he, thu_tu')->fetchAll();
    $don_vis = don_vi_list();
    $mau = mau_noi_dung();
    $preview = HtmlWriter::build(new VanBanLayout(chuan_bi_van_ban($f)));
    $nguoi_tao = $d['nguoi_tao'] ? q_val('SELECT ho_ten FROM hc_nguoi_dung WHERE id = ?', [$d['nguoi_tao']]) : '';

    $tt = $d['trang_thai'];
    page_header($id ? 'Dự thảo #' . $id : 'Soạn văn bản mới', [
        'crumb' => '<a href="soan-thao.php">Soạn thảo</a> / ' . ($id ? 'Dự thảo #' . $id : 'Mới'),
        'sub' => $id ? ('Người soạn: ' . $nguoi_tao . ' · Trạng thái: ' . (TRANG_THAI_DU_THAO[$tt] ?? $tt)) : 'Điền các trường bên trái, bản xem trước bên phải cập nhật trực tiếp theo đúng thể thức.',
    ]);
    if ($id && $d['y_kien']): ?>
      <div class="alert alert-<?= $tt === 'tra_lai' ? 'error' : 'info' ?>"><b>Ý kiến lãnh đạo:</b> <?= nl2br(e($d['y_kien'])) ?></div>
    <?php endif; ?>

<div class="editor" data-editor>
  <form class="card form editor-form" method="post" id="frm-du-thao" data-preview-url="soan-thao.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
    <fieldset <?= $sua ? '' : 'disabled' ?>>
    <div class="seg seg-sm">
      <?php foreach (HE_VAN_BAN as $k => $v): ?>
        <label class="<?= $f['he'] === $k ? 'on' : '' ?>"><input type="radio" name="he" value="<?= e($k) ?>" <?= $f['he'] === $k ? 'checked' : '' ?> data-he-switch> <?= e($v) ?></label>
      <?php endforeach; ?>
    </div>

    <div class="row">
      <label>Loại văn bản
        <select name="loai_id" required data-loai>
          <?php foreach ($loais as $l): ?>
            <option value="<?= (int)$l['id'] ?>" data-he="<?= e($l['he']) ?>" data-cv="<?= (int)$l['co_ten_loai'] ? 0 : 1 ?>" data-ten="<?= e($l['ten']) ?>" <?= (int)$f['loai_id'] === (int)$l['id'] ? 'selected' : '' ?>><?= e($l['ten']) ?><?= $l['viet_tat'] ? ' (' . e($l['viet_tat']) . ')' : '' ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Đơn vị soạn thảo
        <select name="don_vi_id">
          <option value="0">—</option>
          <?php foreach ($don_vis as $dv): ?>
            <option value="<?= (int)$dv['id'] ?>" <?= (int)$f['don_vi_id'] === (int)$dv['id'] ? 'selected' : '' ?>><?= e($dv['ten']) ?> (<?= e($dv['viet_tat']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <?php if ($sua): ?>
    <label>Dùng mẫu nội dung
      <select data-mau>
        <option value="">— Chọn mẫu soạn sẵn theo loại văn bản —</option>
      </select>
      <small>Mẫu sẽ thay trích yếu, nội dung và nơi nhận hiện tại.</small>
    </label>
    <?php endif; ?>

    <label><span data-ty-label>Trích yếu</span>
      <input name="trich_yeu" value="<?= e($f['trich_yeu']) ?>" required maxlength="500" placeholder="Về việc ...">
      <small data-ty-hint>Văn bản có tên loại: "Về việc ..." (in đậm). Công văn: chỉ ghi nội dung, hệ thống tự thêm "V/v".</small>
    </label>

    <label data-kinh-gui>Kính gửi <small>(mỗi nơi một dòng — dùng cho công văn, tờ trình)</small>
      <textarea name="kinh_gui" rows="2"><?= e($f['kinh_gui']) ?></textarea>
    </label>

    <label>Nội dung
      <textarea name="noi_dung" rows="16" placeholder="Mỗi đoạn một dòng."><?= e($f['noi_dung']) ?></textarea>
      <small>Mỗi dòng là một đoạn. Dòng VIẾT HOA → canh giữa, in đậm. "Điều 1." tự in đậm. "Căn cứ…" tự in nghiêng. Dùng **chữ đậm**, _chữ nghiêng_.</small>
    </label>

    <div class="row">
      <label>Mức độ khẩn
        <select name="do_khan"><?= select_options(DO_KHAN, $f['do_khan']) ?></select>
      </label>
      <label>Mẫu người ký
        <select name="nguoi_ky_id" data-nguoi-ky>
          <option value="">— Tự nhập —</option>
          <?php foreach ($nguoi_ky as $nk): ?>
            <option value="<?= (int)$nk['id'] ?>" data-he="<?= e($nk['he']) ?>" data-qh="<?= e($nk['quyen_han']) ?>" data-tm="<?= e($nk['thay_mat']) ?>" data-cv="<?= e($nk['chuc_vu']) ?>" data-ht="<?= e($nk['ho_ten']) ?>" <?= (string)$f['nguoi_ky_id'] === (string)$nk['id'] ? 'selected' : '' ?>><?= e(trim($nk['quyen_han'] . ' ' . $nk['thay_mat']) . ($nk['quyen_han'] ? ' / ' : '') . $nk['chuc_vu'] . ' – ' . $nk['ho_ten']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="row row-4">
      <label>Quyền hạn ký
        <select name="quyen_han" data-qh>
          <?php foreach (['' => '(Ký trực tiếp)', 'TM.' => 'TM.', 'KT.' => 'KT.', 'TL.' => 'TL.', 'TUQ.' => 'TUQ.', 'Q.' => 'Q.', 'T/M' => 'T/M (Đảng)', 'K/T' => 'K/T (Đảng)', 'T/L' => 'T/L (Đảng)'] as $k => $v): ?>
            <option value="<?= e($k) ?>" <?= $f['quyen_han'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Thay mặt / ký thay cho<input name="thay_mat" value="<?= e($f['thay_mat']) ?>" placeholder="vd: GIÁM ĐỐC"></label>
      <label>Chức vụ người ký<input name="chuc_vu" value="<?= e($f['chuc_vu']) ?>" placeholder="vd: PHÓ GIÁM ĐỐC"></label>
      <label>Họ tên người ký<input name="ho_ten" value="<?= e($f['ho_ten']) ?>" placeholder="Không ghi học hàm, học vị"></label>
    </div>

    <label>Nơi nhận <small>(mỗi nơi một dòng; dòng cuối "Lưu: VT, ...")</small>
      <textarea name="noi_nhan" rows="3"><?= e($f['noi_nhan']) ?></textarea>
    </label>
    </fieldset>

    <div class="form-actions sticky-actions">
      <?php if ($sua): ?>
        <button class="btn" name="action" value="luu" type="submit">Lưu dự thảo</button>
        <?php if (in_array($tt, ['nhap', 'tra_lai'], true)): ?>
          <button class="btn btn-ghost" name="action" value="luu_trinh" type="submit">Lưu &amp; trình ký</button>
        <?php endif; ?>
        <button class="btn btn-ghost" type="submit" formaction="soan-thao.php?xuat=docx" formnovalidate>Tải Word (.docx)</button>
      <?php elseif ($id): ?>
        <a class="btn btn-ghost" href="soan-thao.php?id=<?= (int)$id ?>&amp;xuat=docx">Tải Word (.docx)</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="editor-preview">
    <div class="preview-bar"><b>Xem trước</b><span data-preview-status></span></div>
    <div class="preview-scroll" data-preview><?= $preview ?></div>
  </div>
</div>

<?php if ($id): ?>
<section class="card workflow">
  <h2>Xử lý dự thảo</h2>
  <div class="wf-steps">
    <?php foreach (['nhap' => 'Soạn', 'trinh_ky' => 'Trình ký', 'da_duyet' => 'Duyệt', 'da_ban_hanh' => 'Ban hành'] as $k => $v):
        $order = ['nhap' => 0, 'tra_lai' => 0, 'trinh_ky' => 1, 'da_duyet' => 2, 'da_ban_hanh' => 3]; ?>
      <span class="wf <?= $order[$tt] >= $order[$k] ? 'done' : '' ?> <?= ($order[$tt] === $order[$k]) ? 'cur' : '' ?>"><?= e($v) ?></span>
    <?php endforeach; ?>
  </div>

  <div class="wf-actions">
    <?php if (in_array($tt, ['nhap', 'tra_lai'], true) && ((int)$d['nguoi_tao'] === uid() || is_admin())): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn" name="action" value="trinh_ky">Trình ký</button></form>
    <?php endif; ?>

    <?php if ($tt === 'trinh_ky' && can('du_thao.duyet')): ?>
      <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <input name="y_kien" placeholder="Ý kiến (không bắt buộc khi duyệt)">
        <button class="btn" name="action" value="duyet">Duyệt</button>
        <button class="btn btn-danger" name="action" value="tra_lai">Trả lại</button>
      </form>
    <?php endif; ?>

    <?php if (($tt === 'da_duyet' && can('du_thao.ban_hanh')) || (is_admin() && $tt !== 'da_ban_hanh')): ?>
      <form method="post" class="inline-form" onsubmit="return confirm('Cấp số và ban hành văn bản này?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <label>Ngày ban hành<input type="date" name="ngay_ban_hanh" value="<?= date('Y-m-d') ?>" required></label>
        <label>Số (để trống = tự cấp)<input type="number" name="so_tay" min="1" placeholder="tự động"></label>
        <label>Số bản<input type="number" name="so_ban" min="1" value="1"></label>
        <button class="btn" name="action" value="ban_hanh">Cấp số &amp; ban hành</button>
      </form>
    <?php endif; ?>

    <?php if ($tt === 'da_ban_hanh' && $d['van_ban_di_id']): ?>
      <a class="btn" href="van-ban-di.php?xem=<?= (int)$d['van_ban_di_id'] ?>">Xem trong sổ văn bản đi →</a>
    <?php endif; ?>

    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-ghost" name="action" value="nhan_ban">Nhân bản</button></form>
    <?php if ($tt !== 'da_ban_hanh' && (is_admin() || ((int)$d['nguoi_tao'] === uid() && in_array($tt, ['nhap', 'tra_lai'], true)))): ?>
      <form method="post" onsubmit="return confirm('Xóa dự thảo này?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-danger-ghost" name="action" value="xoa">Xóa</button></form>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<script type="application/json" id="mau-noi-dung"><?= json_encode($mau, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php
    page_footer();
    exit;
}

/* ============================ Danh sách ============================ */
$loc = in_str('tt');
$kw = in_str('q');
$where = ['1=1'];
$params = [];
if (!can('du_thao.xem_tat_ca')) {
    $where[] = 'd.nguoi_tao = ?';
    $params[] = uid();
}
if ($loc !== '' && isset(TRANG_THAI_DU_THAO[$loc])) {
    $where[] = 'd.trang_thai = ?';
    $params[] = $loc;
}
if ($kw !== '') {
    $where[] = 'd.trich_yeu LIKE ?';
    $params[] = '%' . $kw . '%';
}
$w = implode(' AND ', $where);
$pg = paginate((int)q_val("SELECT COUNT(*) FROM hc_du_thao d WHERE $w", $params));
$rows = q("SELECT d.*, l.ten AS loai_ten, u.ho_ten AS nguoi_soan, v.so_ky_hieu
           FROM hc_du_thao d
           LEFT JOIN hc_loai_van_ban l ON l.id = d.loai_id
           LEFT JOIN hc_nguoi_dung u ON u.id = d.nguoi_tao
           LEFT JOIN hc_van_ban_di v ON v.id = d.van_ban_di_id
           WHERE $w ORDER BY COALESCE(d.cap_nhat_luc, d.tao_luc) DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params)->fetchAll();

page_header('Soạn thảo văn bản', [
    'sub' => 'Soạn theo mẫu đúng thể thức → trình ký → lãnh đạo duyệt → văn thư cấp số & ban hành.',
    'actions' => '<a class="btn" href="soan-thao.php?moi=1&amp;he=hc">+ Văn bản hành chính</a> <a class="btn btn-ghost" href="soan-thao.php?moi=1&amp;he=dang">+ Văn bản Đảng</a>',
]);
?>
<form class="card filters" method="get">
  <input type="search" name="q" value="<?= e($kw) ?>" placeholder="Tìm theo trích yếu">
  <select name="tt"><option value="">Tất cả trạng thái</option><?= select_options(TRANG_THAI_DU_THAO, $loc) ?></select>
  <button class="btn btn-ghost" type="submit">Lọc</button>
</form>

<div class="card">
  <?php if (!$rows): ?>
    <p class="empty">Chưa có dự thảo nào. Bấm "+ Văn bản hành chính" để bắt đầu.</p>
  <?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>#</th><th>Loại</th><th>Trích yếu</th><th>Người soạn</th><th>Trạng thái</th><th>Cập nhật</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r):
        $tone = ['nhap' => 'muted', 'trinh_ky' => 'warn', 'da_duyet' => 'ok', 'tra_lai' => 'bad', 'da_ban_hanh' => 'blue'][$r['trang_thai']] ?? 'muted'; ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= e($r['loai_ten']) ?><br><small class="muted"><?= $r['he'] === 'dang' ? 'Đảng' : 'Hành chính' ?></small></td>
        <td><a href="soan-thao.php?id=<?= (int)$r['id'] ?>"><?= e($r['trich_yeu'] ?: '(chưa có trích yếu)') ?></a><?php if ($r['so_ky_hieu']): ?><br><small>Số: <?= e($r['so_ky_hieu']) ?></small><?php endif; ?></td>
        <td><?= e($r['nguoi_soan']) ?></td>
        <td><?= badge(TRANG_THAI_DU_THAO[$r['trang_thai']] ?? $r['trang_thai'], $tone) ?></td>
        <td><?= fmt_datetime($r['cap_nhat_luc'] ?: $r['tao_luc']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?= pager_html($pg) ?>
  <?php endif; ?>
</div>
<?php
page_footer();
