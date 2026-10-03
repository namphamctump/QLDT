<?php
/**
 * Khung giao diện chung.
 */

function nav_items(): array
{
    return [
        ['index.php',       'Tổng quan',          'home',   'login'],
        ['soan-thao.php',   'Soạn thảo văn bản',  'pen',    'du_thao.soan'],
        ['van-ban-di.php',  'Sổ văn bản đi',      'out',    'vb_di.xem'],
        ['van-ban-den.php', 'Sổ văn bản đến',     'in',     'vb_den.xu_ly'],
        ['kiem-tra.php',    'Kiểm tra thể thức',  'check',  'login'],
        ['tra-cuu.php',     'Tra cứu thể thức',   'book',   'public'],
        ['bieu-mau.php',    'Văn bản - Biểu mẫu', 'folder', 'public'],
        ['danh-muc.php',    'Danh mục & Cài đặt', 'gear',   'admin'],
        ['nguoi-dung.php',  'Người dùng',         'users',  'admin'],
        ['nhat-ky.php',     'Nhật ký',            'log',    'admin'],
    ];
}

function nav_icon(string $name): string
{
    $p = [
        'home'   => 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
        'pen'    => 'M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4',
        'out'    => 'M14 4h6v6M20 4l-9 9M18 14v6H4V6h6',
        'in'     => 'M10 20H4V4h16v6M20 14l-9 0M14 10l-4 4 4 4',
        'check'  => 'M4 12l5 5L20 6',
        'book'   => 'M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2V5zM4 19a2 2 0 012-2h13',
        'folder' => 'M3 6h6l2 2h10v11H3z',
        'gear'   => 'M12 9a3 3 0 100 6 3 3 0 000-6zM19 12l2-1-1-3-2 .5-1.5-1.5.5-2-3-1-1 2h-2l-1-2-3 1 .5 2L6 7.5 4 7 3 10l2 1v2l-2 1 1 3 2-.5 1.5 1.5-.5 2 3 1 1-2h2l1 2 3-1-.5-2 1.5-1.5 2 .5 1-3-2-1z',
        'users'  => 'M9 11a4 4 0 100-8 4 4 0 000 8zM2 21v-2a5 5 0 015-5h4a5 5 0 015 5v2M17 3a4 4 0 010 8M22 21v-2a5 5 0 00-3-4.6',
        'log'    => 'M6 3h9l4 4v14H6zM9 12h7M9 16h7M9 8h3',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . ($p[$name] ?? '') . '"/></svg>';
}

function page_header(string $title, array $opt = []): void
{
    $u = user();
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $co_quan = '';
    try {
        $co_quan = setting('ten_co_quan');
    } catch (Throwable $e) {
    }
    ?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Quản lý hành chính</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="assets/qlhc.css?v=<?= QLHC_VERSION ?>">
</head>
<body class="<?= $u ? 'has-user' : 'guest' ?>">
<header class="topbar">
  <button class="nav-toggle" type="button" aria-label="Mở menu" data-nav-toggle>☰</button>
  <a class="brand" href="<?= $u ? 'index.php' : 'tra-cuu.php' ?>">
    <span class="brand-mark">HC</span>
    <span class="brand-text">
      <small>Trường Đại học Y Dược Cần Thơ</small>
      <b>QUẢN LÝ HÀNH CHÍNH</b>
    </span>
  </a>
  <div class="spacer"></div>
  <a class="toplink" href="<?= e(cfg('portal_url', '../index_QLDT_CSCE.html')) ?>">← Cổng Đào tạo</a>
  <?php if ($u): ?>
    <div class="userbox">
      <span class="avatar"><?= e(mb_substr($u['ho_ten'], 0, 1)) ?></span>
      <span class="who"><b><?= e($u['ho_ten']) ?></b><small><?= e(VAI_TRO[$u['vai_tro']] ?? '') ?></small></span>
      <div class="usermenu">
        <a href="doi-mat-khau.php">Đổi mật khẩu</a>
        <form method="post" action="logout.php"><?= csrf_field() ?><button type="submit">Đăng xuất</button></form>
      </div>
    </div>
  <?php else: ?>
    <a class="btn btn-sm" href="login.php">Đăng nhập</a>
  <?php endif; ?>
</header>
<div class="shell">
  <aside class="sidenav" data-nav>
    <nav>
    <?php foreach (nav_items() as [$href, $label, $icon, $perm]):
        $show = $perm === 'public'
            || ($perm === 'login' && $u)
            || ($perm === 'admin' && is_admin())
            || ($perm !== 'public' && $perm !== 'login' && $perm !== 'admin' && can($perm));
        if (!$show) continue; ?>
      <a href="<?= e($href) ?>" class="<?= $current === $href ? 'active' : '' ?>"><?= nav_icon($icon) ?><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
    </nav>
    <?php if ($co_quan): ?><div class="sidenav-foot"><?= e($co_quan) ?></div><?php endif; ?>
  </aside>
  <main class="content">
    <div class="page-head">
      <div>
        <?php if (!empty($opt['crumb'])): ?><div class="crumb"><?= $opt['crumb'] ?></div><?php endif; ?>
        <h1><?= e($title) ?></h1>
        <?php if (!empty($opt['sub'])): ?><p class="sub"><?= e($opt['sub']) ?></p><?php endif; ?>
      </div>
      <?php if (!empty($opt['actions'])): ?><div class="page-actions"><?= $opt['actions'] ?></div><?php endif; ?>
    </div>
    <?php foreach (flash_take() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach;
}

function page_footer(): void
{
    ?>
    <footer class="foot">Module Quản lý hành chính v<?= QLHC_VERSION ?> · Thể thức theo Nghị định 30/2020/NĐ-CP và Hướng dẫn 05-HD/VPTW</footer>
  </main>
</div>
<script src="assets/qlhc.js?v=<?= QLHC_VERSION ?>"></script>
</body>
</html>
    <?php
}

/** Hộp chọn (select) từ mảng key => label. */
function select_options(array $opts, $selected): string
{
    $h = '';
    foreach ($opts as $k => $v) {
        $h .= '<option value="' . e($k) . '"' . ((string)$k === (string)$selected ? ' selected' : '') . '>' . e($v) . '</option>';
    }
    return $h;
}
