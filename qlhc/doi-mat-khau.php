<?php
require __DIR__ . '/includes/bootstrap.php';

$u = require_login();
$err = null;
if (is_post()) {
    csrf_check();
    $cu = (string)($_POST['cu'] ?? '');
    $moi = (string)($_POST['moi'] ?? '');
    $lai = (string)($_POST['lai'] ?? '');
    if (!password_verify($cu, $u['mat_khau'])) {
        $err = 'Mật khẩu hiện tại không đúng.';
    } elseif ($moi !== $lai) {
        $err = 'Mật khẩu nhập lại không khớp.';
    } elseif ($moi === $cu) {
        $err = 'Mật khẩu mới phải khác mật khẩu cũ.';
    } elseif ($e = kiem_tra_mat_khau($moi)) {
        $err = $e;
    } else {
        q('UPDATE hc_nguoi_dung SET mat_khau = ?, doi_mat_khau = 0 WHERE id = ?', [password_hash($moi, PASSWORD_DEFAULT), $u['id']]);
        log_action('doi_mat_khau', 'nguoi_dung', (int)$u['id']);
        flash('ok', 'Đã đổi mật khẩu.');
        redirect('index.php');
    }
}
page_header('Đổi mật khẩu');
?>
<form class="card form narrow" method="post">
  <?= csrf_field() ?>
  <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
  <label>Mật khẩu hiện tại<input type="password" name="cu" required autocomplete="current-password"></label>
  <label>Mật khẩu mới<input type="password" name="moi" required minlength="8" autocomplete="new-password"><small>Ít nhất 8 ký tự, có cả chữ và số.</small></label>
  <label>Nhập lại mật khẩu mới<input type="password" name="lai" required minlength="8" autocomplete="new-password"></label>
  <div class="form-actions"><button class="btn" type="submit">Lưu mật khẩu</button></div>
</form>
<?php
page_footer();
