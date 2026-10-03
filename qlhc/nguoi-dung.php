<?php
require __DIR__ . '/includes/bootstrap.php';

require_admin();

if (is_post()) {
    csrf_check();
    $id = in_int('id');
    $a = in_str('action');
    try {
        if ($a === 'luu') {
            $ten = in_str('ten_dang_nhap');
            $ho_ten = in_str('ho_ten');
            $vai_tro = isset(VAI_TRO[in_str('vai_tro')]) ? in_str('vai_tro') : 'chuyen_vien';
            if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $ten)) throw new RuntimeException('Tên đăng nhập 3–60 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch.');
            if ($ho_ten === '') throw new RuntimeException('Nhập họ tên.');
            if (q_val('SELECT id FROM hc_nguoi_dung WHERE ten_dang_nhap = ? AND id <> ?', [$ten, $id])) throw new RuntimeException('Tên đăng nhập đã tồn tại.');
            $pw = (string)($_POST['mat_khau'] ?? '');
            if ($id === uid() && $vai_tro !== 'admin') throw new RuntimeException('Không thể tự bỏ quyền quản trị của chính mình.');
            $kich_hoat = in_int('kich_hoat') ? 1 : 0;
            if ($id === uid()) $kich_hoat = 1;
            $vals = [$ten, $ho_ten, in_str('email'), $vai_tro, in_int('don_vi_id') ?: null, $kich_hoat];
            if ($id) {
                q('UPDATE hc_nguoi_dung SET ten_dang_nhap=?, ho_ten=?, email=?, vai_tro=?, don_vi_id=?, kich_hoat=? WHERE id=?', array_merge($vals, [$id]));
                if ($pw !== '') {
                    if ($err = kiem_tra_mat_khau($pw)) throw new RuntimeException($err);
                    q('UPDATE hc_nguoi_dung SET mat_khau=?, doi_mat_khau=1 WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
                }
                log_action('sua', 'nguoi_dung', $id, $ten);
            } else {
                if ($err = kiem_tra_mat_khau($pw)) throw new RuntimeException($err);
                q('INSERT INTO hc_nguoi_dung (ten_dang_nhap, ho_ten, email, vai_tro, don_vi_id, kich_hoat, mat_khau, doi_mat_khau) VALUES (?,?,?,?,?,?,?,1)',
                    array_merge($vals, [password_hash($pw, PASSWORD_DEFAULT)]));
                log_action('tao', 'nguoi_dung', (int)db()->lastInsertId(), $ten);
            }
            flash('ok', 'Đã lưu tài khoản ' . $ten . '.' . ($pw !== '' ? ' Người dùng sẽ phải đổi mật khẩu khi đăng nhập.' : ''));
        }
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        redirect('nguoi-dung.php?sua=' . $id);
    }
    redirect('nguoi-dung.php');
}

$s = in_int('sua');
$r = $s ? q_one('SELECT * FROM hc_nguoi_dung WHERE id = ?', [$s]) : null;
$ds = q('SELECT u.*, d.ten AS don_vi FROM hc_nguoi_dung u LEFT JOIN hc_don_vi d ON d.id = u.don_vi_id ORDER BY u.kich_hoat DESC, u.vai_tro, u.ho_ten')->fetchAll();
page_header('Người dùng', ['sub' => count($ds) . ' tài khoản']);
?>
<form class="card form" method="post">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
  <h2><?= $r ? 'Sửa tài khoản' : 'Thêm tài khoản' ?></h2>
  <div class="row row-3">
    <label>Tên đăng nhập<input name="ten_dang_nhap" value="<?= e($r['ten_dang_nhap'] ?? '') ?>" required pattern="[a-zA-Z0-9._\-]{3,60}"></label>
    <label>Họ tên<input name="ho_ten" value="<?= e($r['ho_ten'] ?? '') ?>" required></label>
    <label>Email<input type="email" name="email" value="<?= e($r['email'] ?? '') ?>"></label>
  </div>
  <div class="row row-3">
    <label>Vai trò<select name="vai_tro"><?= select_options(VAI_TRO, $r['vai_tro'] ?? 'chuyen_vien') ?></select></label>
    <label>Đơn vị<select name="don_vi_id"><option value="0">—</option><?php foreach (don_vi_list() as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (int)($r['don_vi_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['ten']) ?></option><?php endforeach; ?></select></label>
    <label><?= $r ? 'Đặt lại mật khẩu (để trống nếu giữ nguyên)' : 'Mật khẩu ban đầu' ?><input type="password" name="mat_khau" <?= $r ? '' : 'required' ?> minlength="8" autocomplete="new-password"></label>
  </div>
  <label class="check"><input type="checkbox" name="kich_hoat" value="1" <?= ($r === null || (int)$r['kich_hoat']) ? 'checked' : '' ?>> Đang hoạt động</label>
  <div class="form-actions"><button class="btn" name="action" value="luu"><?= $r ? 'Cập nhật' : 'Tạo tài khoản' ?></button><?php if ($r): ?> <a class="btn btn-ghost" href="nguoi-dung.php">Hủy</a><?php endif; ?></div>
</form>

<div class="card">
  <p class="note"><b>Vai trò:</b> Quản trị – toàn quyền · Văn thư – vào sổ đi/đến, cấp số, ban hành, biểu mẫu · Lãnh đạo – duyệt dự thảo, chỉ đạo văn bản đến · Chuyên viên – soạn dự thảo, xử lý văn bản được giao.</p>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Tên đăng nhập</th><th>Họ tên</th><th>Vai trò</th><th>Đơn vị</th><th>Đăng nhập gần nhất</th><th>Trạng thái</th><th></th></tr></thead><tbody>
    <?php foreach ($ds as $x): ?><tr class="<?= (int)$x['kich_hoat'] ? '' : 'row-off' ?>">
      <td><?= e($x['ten_dang_nhap']) ?></td><td><?= e($x['ho_ten']) ?></td><td><?= e(VAI_TRO[$x['vai_tro']] ?? $x['vai_tro']) ?></td><td><?= e($x['don_vi']) ?></td>
      <td><?= fmt_datetime($x['dang_nhap_luc']) ?></td><td><?= (int)$x['kich_hoat'] ? badge('Hoạt động', 'ok') : badge('Đã khóa', 'bad') ?></td>
      <td><a class="btn btn-sm btn-ghost" href="nguoi-dung.php?sua=<?= (int)$x['id'] ?>">Sửa</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php
page_footer();
