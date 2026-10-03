<?php
require __DIR__ . '/includes/bootstrap.php';

if (user()) {
    redirect('index.php');
}
$err = null;
$next = in_str('next');
if (!preg_match('#^/[^/\\\\]#', $next) && $next !== '') {
    $next = ''; // chỉ cho phép đường dẫn nội bộ
}
if (is_post()) {
    csrf_check();
    $err = attempt_login(in_str('username'), (string)($_POST['password'] ?? ''));
    if ($err === null) {
        redirect($next !== '' ? $next : 'index.php');
    }
}
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập · Quản lý hành chính</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="assets/qlhc.css?v=<?= QLHC_VERSION ?>">
</head>
<body class="login-page">
  <form class="login-card" method="post" autocomplete="on">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="login-brand">
      <span class="brand-mark">HC</span>
      <div><small>Trường Đại học Y Dược Cần Thơ</small><b>QUẢN LÝ HÀNH CHÍNH</b></div>
    </div>
    <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
    <?php foreach (flash_take() as $f): ?><div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>
    <label>Tên đăng nhập<input name="username" required autofocus value="<?= e(in_str('username')) ?>"></label>
    <label>Mật khẩu<input type="password" name="password" required></label>
    <button class="btn btn-block" type="submit">Đăng nhập</button>
    <p class="login-links"><a href="tra-cuu.php">Tra cứu thể thức</a> · <a href="bieu-mau.php">Văn bản - Biểu mẫu</a> · <a href="<?= e(cfg('portal_url', '../index_QLDT_CSCE.html')) ?>">Cổng Đào tạo</a></p>
  </form>
</body>
</html>
