<?php
/**
 * Trình cài đặt module QLHC.
 * Chạy một lần: tạo bảng, dữ liệu mặc định, tài khoản quản trị và file config.php.
 * Sau khi cài xong, file này tự khóa (vì đã có config.php). Có thể xóa file này cho an toàn.
 */
require __DIR__ . '/includes/bootstrap.php';
qlhc_session_start();

$installed = is_file(__DIR__ . '/config.php');

$checks = [
    'PHP ≥ 7.4'           => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO MySQL'           => extension_loaded('pdo_mysql'),
    'ZipArchive (zip)'    => class_exists('ZipArchive'),
    'mbstring'            => extension_loaded('mbstring'),
    'DOM / XML'           => class_exists('DOMDocument'),
    'Ghi được thư mục qlhc/ (tạo config.php)' => is_writable(__DIR__),
    'Ghi được thư mục uploads/' => is_dir(__DIR__ . '/uploads') ? is_writable(__DIR__ . '/uploads') : is_writable(__DIR__),
];
$ok_env = !in_array(false, $checks, true);

$err = null;
$done = false;
$v = [
    'host' => 'localhost', 'port' => '3306', 'name' => '', 'user' => '', 'pass' => '',
    'admin' => 'admin', 'admin_name' => 'Quản trị hệ thống', 'admin_pass' => '',
];

if (!$installed && is_post()) {
    csrf_check();
    foreach ($v as $k => $_) {
        $v[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $v['pass'] = (string)($_POST['pass'] ?? '');
    $v['admin_pass'] = (string)($_POST['admin_pass'] ?? '');
    try {
        if (!$ok_env) {
            throw new RuntimeException('Máy chủ chưa đáp ứng yêu cầu (xem bảng kiểm tra).');
        }
        if ($v['name'] === '' || $v['user'] === '') {
            throw new RuntimeException('Nhập tên database và tài khoản MySQL.');
        }
        if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $v['admin'])) {
            throw new RuntimeException('Tên đăng nhập quản trị không hợp lệ.');
        }
        if ($e = kiem_tra_mat_khau($v['admin_pass'])) {
            throw new RuntimeException('Mật khẩu quản trị: ' . $e);
        }
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $v['host'], (int)$v['port'], $v['name']), $v['user'], $v['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $exists = (bool)$pdo->query("SHOW TABLES LIKE 'hc_nguoi_dung'")->fetchColumn();
        if (!$exists) {
            $sql = file_get_contents(__DIR__ . '/install.sql');
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
                if (trim($stmt) !== '') {
                    $pdo->exec($stmt);
                }
            }
        }
        // Tài khoản quản trị
        $hash = password_hash($v['admin_pass'], PASSWORD_DEFAULT);
        $st = $pdo->prepare('SELECT id FROM hc_nguoi_dung WHERE ten_dang_nhap = ?');
        $st->execute([$v['admin']]);
        if ($id = $st->fetchColumn()) {
            $pdo->prepare("UPDATE hc_nguoi_dung SET mat_khau=?, ho_ten=?, vai_tro='admin', kich_hoat=1, doi_mat_khau=0 WHERE id=?")->execute([$hash, $v['admin_name'], $id]);
        } else {
            $pdo->prepare("INSERT INTO hc_nguoi_dung (ten_dang_nhap, mat_khau, ho_ten, vai_tro, kich_hoat, doi_mat_khau) VALUES (?,?,?,'admin',1,0)")->execute([$v['admin'], $hash, $v['admin_name']]);
        }
        if ($v['admin'] !== 'admin') {
            // Khóa tài khoản admin mặc định có mật khẩu công khai trong install.sql
            $pdo->exec("UPDATE hc_nguoi_dung SET kich_hoat = 0 WHERE ten_dang_nhap = 'admin' AND mat_khau = '\$2y\$10\$7wJASDUk85JKjDcgyGiK4Oez9knioWY6pe6.JHrMbTNNGKqrZCuZG'");
        }

        $config = [
            'db' => ['host' => $v['host'], 'port' => (int)$v['port'], 'name' => $v['name'], 'user' => $v['user'], 'pass' => $v['pass'], 'charset' => 'utf8mb4'],
            'timezone' => 'Asia/Ho_Chi_Minh',
            'upload_max_mb' => 20,
            'debug' => false,
            'portal_url' => '../index_QLDT_CSCE.html',
        ];
        $php = "<?php\n// Cấu hình module QLHC — tạo bởi install.php ngày " . date('d/m/Y H:i') . "\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents(__DIR__ . '/config.php', $php) === false) {
            throw new RuntimeException('Không ghi được config.php. Hãy tạo thủ công từ config.sample.php.');
        }
        @chmod(__DIR__ . '/config.php', 0640);
        if (!is_dir(__DIR__ . '/uploads')) {
            @mkdir(__DIR__ . '/uploads', 0755, true);
        }
        $done = true;
    } catch (PDOException $e) {
        $err = 'Lỗi CSDL: ' . $e->getMessage();
    } catch (RuntimeException $e) {
        $err = $e->getMessage();
    }
}
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cài đặt · Quản lý hành chính</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="assets/qlhc.css?v=<?= QLHC_VERSION ?>">
</head>
<body class="login-page">
<div class="login-card install-card">
  <div class="login-brand"><span class="brand-mark">HC</span><div><small>Module Quản lý hành chính</small><b>CÀI ĐẶT</b></div></div>

  <?php if ($installed && !$done): ?>
    <div class="alert alert-info">Module đã được cài đặt (đã có <code>config.php</code>). Để cài lại, hãy xóa <code>config.php</code> trên hosting.</div>
    <a class="btn btn-block" href="login.php">Đến trang đăng nhập</a>
  <?php elseif ($done): ?>
    <div class="alert alert-ok">Cài đặt thành công!</div>
    <ol>
      <li>Đăng nhập bằng tài khoản <b><?= e($v['admin']) ?></b>.</li>
      <li>Vào <b>Danh mục &amp; Cài đặt</b> để sửa tên cơ quan, viết tắt, đơn vị, người ký.</li>
      <li>Vào <b>Người dùng</b> để tạo tài khoản văn thư, lãnh đạo, chuyên viên.</li>
      <li><b>Nên xóa file <code>install.php</code></b> khỏi hosting.</li>
    </ol>
    <a class="btn btn-block" href="login.php">Đăng nhập</a>
  <?php else: ?>
    <table class="tbl tbl-kv"><tbody>
      <?php foreach ($checks as $k => $ok): ?><tr><th><?= e($k) ?></th><td><?= $ok ? badge('Đạt', 'ok') : badge('Chưa đạt', 'bad') ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <h3>Cơ sở dữ liệu MySQL</h3>
      <div class="row"><label>Máy chủ<input name="host" value="<?= e($v['host']) ?>" required></label><label>Cổng<input name="port" value="<?= e($v['port']) ?>" required></label></div>
      <label>Tên database<input name="name" value="<?= e($v['name']) ?>" required><small>Database phải tạo sẵn trong cPanel/DirectAdmin (collation utf8mb4_unicode_ci). Có thể dùng chung database với web hiện có — bảng có tiền tố <code>hc_</code>.</small></label>
      <div class="row"><label>Tài khoản<input name="user" value="<?= e($v['user']) ?>" required autocomplete="off"></label><label>Mật khẩu<input type="password" name="pass" autocomplete="off"></label></div>
      <h3>Tài khoản quản trị</h3>
      <div class="row"><label>Tên đăng nhập<input name="admin" value="<?= e($v['admin']) ?>" required></label><label>Họ tên<input name="admin_name" value="<?= e($v['admin_name']) ?>" required></label></div>
      <label>Mật khẩu (≥ 8 ký tự, có chữ và số)<input type="password" name="admin_pass" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-block" type="submit" <?= $ok_env ? '' : 'disabled' ?>>Cài đặt</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
