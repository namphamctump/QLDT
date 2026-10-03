<?php
/**
 * Hàm tiện ích dùng chung.
 */

/** Kết nối PDO dùng chung. */
function db(): PDO
{
    static $pdo = null;
    global $CONFIG;
    if ($pdo === null) {
        $c = $CONFIG['db'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'], (int)($c['port'] ?? 3306), $c['name'], $c['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function q_one(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function q_val(string $sql, array $params = [])
{
    $r = q($sql, $params)->fetchColumn();
    return $r === false ? null : $r;
}

/** Thoát HTML. */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cfg(string $key, $default = null)
{
    global $CONFIG;
    return $CONFIG[$key] ?? $default;
}

/** Đọc cài đặt trong bảng hc_cai_dat (có cache). */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (q('SELECT khoa, gia_tri FROM hc_cai_dat')->fetchAll() as $r) {
            $cache[$r['khoa']] = (string)$r['gia_tri'];
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function setting_save(string $key, string $value): void
{
    q('INSERT INTO hc_cai_dat (khoa, gia_tri) VALUES (?, ?) ON DUPLICATE KEY UPDATE gia_tri = VALUES(gia_tri)', [$key, $value]);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_take(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------------------- CSRF ---------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng tải lại trang.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ---------------------------- Input ---------------------------- */

function in_str(string $k, string $default = ''): string
{
    $v = $_POST[$k] ?? $_GET[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function in_int(string $k, int $default = 0): int
{
    $v = $_POST[$k] ?? $_GET[$k] ?? null;
    return (is_string($v) && preg_match('/^-?\d+$/', trim($v))) ? (int)$v : $default;
}

function in_date(string $k): ?string
{
    $v = in_str($k);
    if ($v === '') {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : null;
}

/* ---------------------------- Định dạng ---------------------------- */

/** dd/mm/yyyy để hiển thị trong bảng. */
function fmt_date(?string $d): string
{
    if (!$d || strpos($d, '0000') === 0) {
        return '';
    }
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : '';
}

function fmt_datetime(?string $d): string
{
    $t = $d ? strtotime($d) : false;
    return $t ? date('d/m/Y H:i', $t) : '';
}

/** Số văn bản: nhỏ hơn 10 thêm số 0 phía trước (NĐ 30 & HD 05). */
function so_pad(int $n): string
{
    return $n < 10 ? '0' . $n : (string)$n;
}

/**
 * Dòng địa danh, ngày tháng năm theo NĐ 30:
 * ngày < 10 và tháng 1, 2 thêm số 0.
 */
function ngay_van_ban(string $dia_danh, string $ymd): string
{
    $t = strtotime($ymd) ?: time();
    $d = (int)date('j', $t);
    $m = (int)date('n', $t);
    $ngay  = $d < 10 ? '0' . $d : (string)$d;
    $thang = $m <= 2 ? '0' . $m : (string)$m;
    return $dia_danh . ', ngày ' . $ngay . ' tháng ' . $thang . ' năm ' . date('Y', $t);
}

/** Viết hoa toàn bộ (tiếng Việt). */
function upper(string $s): string
{
    return mb_strtoupper($s, 'UTF-8');
}

/**
 * Ghép số và ký hiệu.
 *  - Hành chính, có tên loại: 15/QĐ-TTDV
 *  - Hành chính, công văn:    125/TTDV-ĐT
 *  - Đảng:                     23-QĐ/CB
 */
function so_ky_hieu(string $he, int $so, array $loai, string $don_vi_vt = ''): string
{
    $s = so_pad($so);
    if ($he === 'dang') {
        $vt = $loai['viet_tat'] !== '' ? $loai['viet_tat'] : 'CV';
        return $s . '-' . $vt . '/' . setting('dang_viet_tat');
    }
    $cq = setting('viet_tat_co_quan');
    if ((int)$loai['co_ten_loai'] === 0) {
        return $s . '/' . $cq . ($don_vi_vt !== '' ? '-' . $don_vi_vt : '');
    }
    return $s . '/' . $loai['viet_tat'] . '-' . $cq;
}

/** Nhãn "Số: ..." (HC có dấu hai chấm, Đảng không). */
function so_label(string $he, string $skh): string
{
    return $he === 'dang' ? 'Số ' . $skh : 'Số: ' . $skh;
}

/* ---------------------------- Cấp số ---------------------------- */

/** Khóa phạm vi đánh số văn bản đi. */
function pham_vi_so_di(string $he, int $nam, array $loai): string
{
    if ($he === 'dang') {
        // Đảng: liên tục từ 01 cho mỗi tên loại trong một nhiệm kỳ cấp ủy
        return 'di:dang:' . setting('dang_nhiem_ky') . ':' . $loai['id'];
    }
    if (setting('danh_so_hc', 'chung') === 'theo_loai') {
        return 'di:hc:' . $nam . ':' . $loai['id'];
    }
    return 'di:hc:' . $nam;
}

/**
 * Lấy số kế tiếp trong phạm vi (gọi bên trong transaction).
 * $max_hien_co: số lớn nhất đang có trong sổ để đồng bộ khi có số nhập tay.
 */
function cap_so_ke_tiep(string $khoa, int $max_hien_co = 0): int
{
    q('INSERT IGNORE INTO hc_bo_dem (khoa, gia_tri) VALUES (?, 0)', [$khoa]);
    $cur = (int)q_val('SELECT gia_tri FROM hc_bo_dem WHERE khoa = ? FOR UPDATE', [$khoa]);
    $next = max($cur, $max_hien_co) + 1;
    q('UPDATE hc_bo_dem SET gia_tri = ? WHERE khoa = ?', [$next, $khoa]);
    return $next;
}

/** Cập nhật bộ đếm khi nhập số thủ công lớn hơn bộ đếm. */
function dong_bo_bo_dem(string $khoa, int $so): void
{
    q('INSERT INTO hc_bo_dem (khoa, gia_tri) VALUES (?, ?) ON DUPLICATE KEY UPDATE gia_tri = GREATEST(gia_tri, VALUES(gia_tri))', [$khoa, $so]);
}

/** Số lớn nhất hiện có trong sổ đi theo phạm vi. */
function max_so_di(string $pham_vi): int
{
    return (int)q_val('SELECT COALESCE(MAX(so),0) FROM hc_van_ban_di WHERE pham_vi = ?', [$pham_vi]);
}

/* ---------------------------- Danh mục ---------------------------- */

function loai_van_ban(string $he = ''): array
{
    if ($he === '') {
        return q('SELECT * FROM hc_loai_van_ban ORDER BY he, thu_tu, ten')->fetchAll();
    }
    return q('SELECT * FROM hc_loai_van_ban WHERE he = ? ORDER BY thu_tu, ten', [$he])->fetchAll();
}

function loai_by_id(int $id): ?array
{
    return q_one('SELECT * FROM hc_loai_van_ban WHERE id = ?', [$id]);
}

function don_vi_list(): array
{
    return q('SELECT * FROM hc_don_vi ORDER BY thu_tu, ten')->fetchAll();
}

function nguoi_dung_list(bool $chi_kich_hoat = true): array
{
    return q('SELECT id, ho_ten, vai_tro, don_vi_id FROM hc_nguoi_dung' . ($chi_kich_hoat ? ' WHERE kich_hoat = 1' : '') . ' ORDER BY ho_ten')->fetchAll();
}

const DO_KHAN = [
    ''            => 'Thường',
    'khan'        => 'Khẩn',
    'thuong_khan' => 'Thượng khẩn',
    'hoa_toc'     => 'Hỏa tốc',
];

const TRANG_THAI_DEN = [
    'moi'        => 'Mới đến',
    'dang_xu_ly' => 'Đang xử lý',
    'hoan_thanh' => 'Hoàn thành',
];

const TRANG_THAI_DU_THAO = [
    'nhap'        => 'Đang soạn',
    'trinh_ky'    => 'Trình ký',
    'da_duyet'    => 'Đã duyệt',
    'tra_lai'     => 'Trả lại',
    'da_ban_hanh' => 'Đã ban hành',
];

const HE_VAN_BAN = [
    'hc'   => 'Hành chính (NĐ 30/2020)',
    'dang' => 'Đảng (HD 05-HD/VPTW)',
];

function badge(string $text, string $tone = 'muted'): string
{
    return '<span class="badge badge-' . e($tone) . '">' . e($text) . '</span>';
}

/* ---------------------------- Tệp tin ---------------------------- */

const UPLOAD_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'jpg', 'jpeg', 'png', 'zip', 'rar', 'ppt', 'pptx', 'txt'];

/**
 * Lưu file tải lên vào uploads/<thu_muc>/. Trả về [đường dẫn tương đối, tên gốc] hoặc null nếu không có file.
 * Ném RuntimeException khi file không hợp lệ.
 */
function luu_tep(string $field, string $thu_muc, array $ext_cho_phep = UPLOAD_EXT): ?array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Tải file thất bại (mã lỗi ' . (int)$f['error'] . '). File có thể vượt dung lượng cho phép của hosting.');
    }
    $max = (int)cfg('upload_max_mb', 20) * 1024 * 1024;
    if ($f['size'] > $max) {
        throw new RuntimeException('File vượt quá ' . cfg('upload_max_mb', 20) . ' MB.');
    }
    $goc = basename((string)$f['name']);
    $ext = strtolower(pathinfo($goc, PATHINFO_EXTENSION));
    if (!in_array($ext, $ext_cho_phep, true)) {
        throw new RuntimeException('Định dạng .' . $ext . ' không được phép. Cho phép: ' . implode(', ', $ext_cho_phep));
    }
    $dir = QLHC_ROOT . '/uploads/' . $thu_muc . '/' . date('Y');
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Không tạo được thư mục lưu trữ. Kiểm tra quyền ghi của thư mục uploads/.');
    }
    $ten = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $ten)) {
        throw new RuntimeException('Không lưu được file.');
    }
    return [$thu_muc . '/' . date('Y') . '/' . $ten, $goc];
}

/** Ghi nội dung (vd. file .docx tạo ra) vào uploads/. */
function luu_noi_dung(string $noi_dung, string $thu_muc, string $ext): string
{
    $dir = QLHC_ROOT . '/uploads/' . $thu_muc . '/' . date('Y');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $ten = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    file_put_contents($dir . '/' . $ten, $noi_dung);
    return $thu_muc . '/' . date('Y') . '/' . $ten;
}

function xoa_tep(?string $rel): void
{
    if (!$rel) {
        return;
    }
    $p = realpath(QLHC_ROOT . '/uploads/' . $rel);
    $base = realpath(QLHC_ROOT . '/uploads');
    if ($p && $base && strpos($p, $base . DIRECTORY_SEPARATOR) === 0 && is_file($p)) {
        @unlink($p);
    }
}

/** Gửi file cho trình duyệt tải về. */
function gui_tep(string $path, string $ten, string $mime = 'application/octet-stream', bool $inline = false): void
{
    if (!is_file($path)) {
        http_response_code(404);
        exit('Không tìm thấy file.');
    }
    $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', khong_dau($ten));
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($ten));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function gui_noi_dung(string $data, string $ten, string $mime): void
{
    $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', khong_dau($ten));
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . strlen($data));
    header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($ten));
    echo $data;
    exit;
}

function mime_of(string $file): string
{
    $map = [
        'pdf' => 'application/pdf', 'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'zip' => 'application/zip',
        'rar' => 'application/vnd.rar', 'txt' => 'text/plain; charset=utf-8',
    ];
    return $map[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
}

/** Bỏ dấu tiếng Việt (dùng cho tên file). */
function khong_dau(string $s): string
{
    $map = [
        'a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ', 'o' => 'òóọỏõôồốộổỗơờớợởỡ',
        'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ',
        'A' => 'ÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴ', 'E' => 'ÈÉẸẺẼÊỀẾỆỂỄ', 'I' => 'ÌÍỊỈĨ', 'O' => 'ÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠ',
        'U' => 'ÙÚỤỦŨƯỪỨỰỬỮ', 'Y' => 'ỲÝỴỶỸ', 'D' => 'Đ',
    ];
    foreach ($map as $to => $from) {
        $s = preg_replace('/[' . $from . ']/u', $to, $s);
    }
    return $s;
}

/** Ghi nhật ký thao tác. */
function log_action(string $hanh_dong, string $doi_tuong = '', ?int $id = null, string $chi_tiet = ''): void
{
    try {
        q('INSERT INTO hc_nhat_ky (nguoi_dung_id, hanh_dong, doi_tuong, doi_tuong_id, chi_tiet, ip) VALUES (?,?,?,?,?,?)', [
            $_SESSION['uid'] ?? null, $hanh_dong, $doi_tuong, $id, mb_substr($chi_tiet, 0, 2000), $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (Throwable $e) {
        // Không chặn nghiệp vụ nếu ghi nhật ký lỗi
    }
}

/* ---------------------------- Phân trang ---------------------------- */

function paginate(int $total, int $per = 20): array
{
    $page = max(1, in_int('page', 1));
    $pages = max(1, (int)ceil($total / $per));
    $page = min($page, $pages);
    return ['page' => $page, 'pages' => $pages, 'per' => $per, 'offset' => ($page - 1) * $per, 'total' => $total];
}

function pager_html(array $p): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $qs = $_GET;
    $h = '<nav class="pager">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        if ($p['pages'] > 12 && abs($i - $p['page']) > 3 && $i !== 1 && $i !== $p['pages']) {
            if ($i === 2 || $i === $p['pages'] - 1) {
                $h .= '<span>…</span>';
            }
            continue;
        }
        $qs['page'] = $i;
        $h .= $i === $p['page']
            ? '<b>' . $i . '</b>'
            : '<a href="?' . e(http_build_query($qs)) . '">' . $i . '</a>';
    }
    return $h . '</nav>';
}

/** Danh sách năm có dữ liệu để lọc. */
function nam_options(string $table): array
{
    $years = q("SELECT DISTINCT nam FROM $table ORDER BY nam DESC")->fetchAll(PDO::FETCH_COLUMN);
    $now = (int)date('Y');
    if (!in_array($now, array_map('intval', $years), true)) {
        array_unshift($years, $now);
    }
    return array_map('intval', $years);
}
