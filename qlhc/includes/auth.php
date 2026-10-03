<?php
/**
 * Phiên đăng nhập & phân quyền.
 *
 * Vai trò:
 *  - admin       : toàn quyền, quản lý danh mục, người dùng, nhật ký
 *  - van_thu     : vào sổ văn bản đi/đến, cấp số, ban hành, quản lý biểu mẫu
 *  - lanh_dao    : duyệt dự thảo, cho ý kiến chỉ đạo văn bản đến, xem toàn bộ
 *  - chuyen_vien : soạn dự thảo, xử lý văn bản đến được giao
 */

const VAI_TRO = [
    'admin'       => 'Quản trị',
    'van_thu'     => 'Văn thư',
    'lanh_dao'    => 'Lãnh đạo',
    'chuyen_vien' => 'Chuyên viên',
];

const QUYEN = [
    'van_thu' => [
        'vb_di.xem', 'vb_di.sua', 'vb_den.xem_tat_ca', 'vb_den.sua', 'vb_den.xu_ly',
        'du_thao.soan', 'du_thao.xem_tat_ca', 'du_thao.ban_hanh', 'bieu_mau.quan_ly',
    ],
    'lanh_dao' => [
        'vb_di.xem', 'vb_den.xem_tat_ca', 'vb_den.chi_dao', 'vb_den.xu_ly',
        'du_thao.soan', 'du_thao.xem_tat_ca', 'du_thao.duyet',
    ],
    'chuyen_vien' => [
        'vb_di.xem', 'vb_den.xu_ly', 'du_thao.soan',
    ],
];

function qlhc_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('QLHCSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Hết hạn sau 8 giờ không thao tác
    $now = time();
    if (isset($_SESSION['last']) && $now - $_SESSION['last'] > 8 * 3600) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last'] = $now;
}

/** Người dùng hiện tại (hoặc null). */
function user(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $u = q_one('SELECT * FROM hc_nguoi_dung WHERE id = ? AND kich_hoat = 1', [$_SESSION['uid']]);
            if (!$u) {
                unset($_SESSION['uid']);
            }
        }
    }
    return $u;
}

function uid(): int
{
    return (int)($_SESSION['uid'] ?? 0);
}

function can(string $perm): bool
{
    $u = user();
    if (!$u) {
        return false;
    }
    if ($u['vai_tro'] === 'admin') {
        return true;
    }
    return in_array($perm, QUYEN[$u['vai_tro']] ?? [], true);
}

function is_admin(): bool
{
    $u = user();
    return $u && $u['vai_tro'] === 'admin';
}

function require_login(): array
{
    $u = user();
    if (!$u) {
        $back = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php' . ($back ? '?next=' . rawurlencode($back) : ''));
    }
    if ((int)$u['doi_mat_khau'] === 1 && basename($_SERVER['SCRIPT_NAME']) !== 'doi-mat-khau.php') {
        flash('warn', 'Vui lòng đổi mật khẩu trước khi tiếp tục.');
        redirect('doi-mat-khau.php');
    }
    return $u;
}

function require_perm(string $perm): array
{
    $u = require_login();
    if (!can($perm)) {
        http_response_code(403);
        page_header('Không có quyền');
        echo '<div class="card"><h2>Không có quyền truy cập</h2><p>Tài khoản của bạn không được phép thực hiện chức năng này.</p><p><a class="btn" href="index.php">← Về trang tổng quan</a></p></div>';
        page_footer();
        exit;
    }
    return $u;
}

function require_admin(): array
{
    return require_perm('__admin_only__');
}

/**
 * Đăng nhập. Chặn tạm 5 phút sau 5 lần sai liên tiếp (theo phiên + IP).
 */
function attempt_login(string $username, string $password): ?string
{
    $key = 'login_fail_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $fail = $_SESSION[$key] ?? ['n' => 0, 't' => 0];
    if ($fail['n'] >= 5 && time() - $fail['t'] < 300) {
        return 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 5 phút.';
    }
    $u = q_one('SELECT * FROM hc_nguoi_dung WHERE ten_dang_nhap = ?', [$username]);
    if (!$u || !password_verify($password, $u['mat_khau'])) {
        $_SESSION[$key] = ['n' => $fail['n'] + 1, 't' => time()];
        usleep(300000);
        return 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
    if ((int)$u['kich_hoat'] !== 1) {
        return 'Tài khoản đã bị khóa. Liên hệ quản trị viên.';
    }
    unset($_SESSION[$key]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    if (password_needs_rehash($u['mat_khau'], PASSWORD_DEFAULT)) {
        q('UPDATE hc_nguoi_dung SET mat_khau = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    q('UPDATE hc_nguoi_dung SET dang_nhap_luc = NOW() WHERE id = ?', [$u['id']]);
    log_action('dang_nhap', 'nguoi_dung', (int)$u['id']);
    return null;
}

function logout(): void
{
    log_action('dang_xuat', 'nguoi_dung', uid());
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function kiem_tra_mat_khau(string $pw): ?string
{
    if (mb_strlen($pw) < 8) {
        return 'Mật khẩu phải có ít nhất 8 ký tự.';
    }
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
        return 'Mật khẩu phải có cả chữ và số.';
    }
    return null;
}
