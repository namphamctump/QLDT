<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/kiemtra.php';

$u = require_login();
$dir = QLHC_ROOT . '/uploads/kiem-tra';
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
// Dọn file kiểm tra cũ hơn 1 ngày
foreach (glob($dir . '/*.docx') ?: [] as $old) {
    if (filemtime($old) < time() - 86400) {
        @unlink($old);
    }
}

/* ---------- Tải bản chuẩn hóa ---------- */
$tk = in_str('chuan_hoa');
if ($tk !== '') {
    $info = $_SESSION['kiem_tra'][$tk] ?? null;
    if (!$info || !preg_match('/^[a-f0-9]{24}$/', $tk) || !is_file($dir . '/' . $tk . '.docx')) {
        flash('error', 'File kiểm tra đã hết hạn, vui lòng tải lên lại.');
        redirect('kiem-tra.php');
    }
    $data = KiemTraTheThuc::chuanHoa($dir . '/' . $tk . '.docx', $info['he']);
    log_action('chuan_hoa', 'kiem_tra', null, $info['ten']);
    gui_noi_dung($data, preg_replace('/\.docx$/i', '', $info['ten']) . ' (chuan hoa).docx', mime_of('a.docx'));
}

$ket_qua = null;
$ten = '';
$he = in_str('he', 'hc') === 'dang' ? 'dang' : 'hc';
$token = '';
if (is_post()) {
    csrf_check();
    try {
        if (empty($_FILES['tep']) || $_FILES['tep']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Vui lòng chọn file .docx.');
        }
        $f = $_FILES['tep'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Tải file thất bại (mã ' . (int)$f['error'] . ').');
        }
        $ten = basename((string)$f['name']);
        if (strtolower(pathinfo($ten, PATHINFO_EXTENSION)) !== 'docx') {
            throw new RuntimeException('Chỉ hỗ trợ file .docx. Với file .doc, hãy mở bằng Word và "Lưu thành" .docx.');
        }
        if ($f['size'] > (int)cfg('upload_max_mb', 20) * 1048576) {
            throw new RuntimeException('File quá lớn.');
        }
        $token = bin2hex(random_bytes(12));
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $token . '.docx')) {
            throw new RuntimeException('Không lưu được file tạm. Kiểm tra quyền ghi thư mục uploads/.');
        }
        $ket_qua = (new KiemTraTheThuc($dir . '/' . $token . '.docx', $he))->chay();
        $_SESSION['kiem_tra'][$token] = ['he' => $he, 'ten' => $ten];
        log_action('kiem_tra', 'kiem_tra', null, $ten);
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        redirect('kiem-tra.php');
    }
}

page_header('Kiểm tra thể thức văn bản', [
    'sub' => 'Tải lên file Word (.docx) để rà soát theo Nghị định 30/2020 hoặc Hướng dẫn 05-HD/VPTW, rồi tải bản đã chuẩn hóa.',
]);
?>
<form class="card form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="seg seg-sm">
    <?php foreach (HE_VAN_BAN as $k => $v): ?>
      <label class="<?= $he === $k ? 'on' : '' ?>"><input type="radio" name="he" value="<?= e($k) ?>" <?= $he === $k ? 'checked' : '' ?> data-seg-radio> <?= e($v) ?></label>
    <?php endforeach; ?>
  </div>
  <label class="dropzone" data-dropzone>
    <input type="file" name="tep" accept=".docx" required>
    <b>Chọn hoặc kéo thả file .docx vào đây</b>
    <span data-file-name>Tối đa <?= (int)cfg('upload_max_mb', 20) ?> MB</span>
  </label>
  <div class="form-actions"><button class="btn" type="submit">Kiểm tra</button></div>
</form>

<?php if ($ket_qua !== null):
    $dem = ['loi' => 0, 'canh_bao' => 0, 'dat' => 0];
    foreach ($ket_qua as $k) $dem[$k['muc_do']]++;
    $tong = count($ket_qua);
    $diem = $tong ? round(($dem['dat'] + 0.5 * $dem['canh_bao']) / $tong * 100) : 0; ?>
<section class="card">
  <div class="report-head">
    <div class="score <?= $dem['loi'] ? 'score-bad' : ($dem['canh_bao'] ? 'score-warn' : 'score-ok') ?>"><b><?= $diem ?></b><small>/100</small></div>
    <div>
      <h2><?= e($ten) ?></h2>
      <p><?= badge($dem['loi'] . ' lỗi', 'bad') ?> <?= badge($dem['canh_bao'] . ' cảnh báo', 'warn') ?> <?= badge($dem['dat'] . ' đạt', 'ok') ?> · <?= e(HE_VAN_BAN[$he]) ?></p>
    </div>
    <div class="spacer"></div>
    <a class="btn" href="kiem-tra.php?chuan_hoa=<?= e($token) ?>">Tải bản chuẩn hóa (.docx)</a>
  </div>
  <div class="tbl-wrap"><table class="tbl report">
    <thead><tr><th>Mục</th><th>Kết quả</th><th>Nhận xét</th><th>Gợi ý</th></tr></thead>
    <tbody>
    <?php
    usort($ket_qua, function ($a, $b) {
        $o = ['loi' => 0, 'canh_bao' => 1, 'dat' => 2];
        return $o[$a['muc_do']] <=> $o[$b['muc_do']];
    });
    foreach ($ket_qua as $k): ?>
      <tr class="lv-<?= e($k['muc_do']) ?>">
        <td><b><?= e($k['muc']) ?></b></td>
        <td><?= $k['muc_do'] === 'loi' ? badge('Lỗi', 'bad') : ($k['muc_do'] === 'canh_bao' ? badge('Cảnh báo', 'warn') : badge('Đạt', 'ok')) ?></td>
        <td><?= e($k['noi_dung']) ?></td>
        <td class="muted"><?= e($k['goi_y']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="note"><b>Bản chuẩn hóa tự động sửa:</b> phông chữ → Times New Roman; khổ A4; lề 20/20/30/15 mm; thêm số 0 cho ngày &lt; 10, tháng 1–2 và số văn bản &lt; 10; bỏ khoảng trắng kép.
  Các mục khác (bố cục, viết hoa, nơi nhận, học hàm học vị…) cần chỉnh tay theo gợi ý. Kiểm tra tự động chỉ hỗ trợ, không thay thế việc rà soát của văn thư.</p>
</section>
<?php endif; ?>

<section class="card">
  <h2>Các nội dung được kiểm tra</h2>
  <ul class="checklist">
    <li>Khổ giấy A4, hướng trang; lề trên/dưới/trái/phải</li>
    <li>Phông chữ Times New Roman (kể cả phông mặc định, phông theo theme)</li>
    <li>Cỡ chữ nằm trong khoảng cho phép</li>
    <li>Quốc hiệu, Tiêu ngữ (văn bản Đảng: tiêu đề "ĐẢNG CỘNG SẢN VIỆT NAM", dấu sao)</li>
    <li>Số và ký hiệu: dấu hai chấm, số 0 khi số &lt; 10</li>
    <li>Địa danh, ngày tháng năm: quy tắc thêm số 0</li>
    <li>Nơi nhận, "Như trên", "Lưu: VT"</li>
    <li>Học hàm, học vị trước họ tên người ký</li>
    <li>Canh đều hai bên phần nội dung; cách dòng Exactly (văn bản Đảng)</li>
    <li>Số trang ở đầu trang, ẩn ở trang 1</li>
    <li>Khoảng trắng kép, dấu câu, một số lỗi viết hoa thường gặp</li>
  </ul>
</section>
<?php
page_footer();
