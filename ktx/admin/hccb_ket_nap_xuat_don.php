<?php
// Xuat file Word "DON XIN VAO HOI CUU CHIEN BINH VIET NAM" DIEN SAN cac truong da biet (2026 -
// MẪU 1), 2026-09-19. Cac muc chua co du lieu san (Trinh do van hoa, Khen thuong, Ky luat, Vo/con,
// Qua trinh cong tac) GIU NGUYEN dang dong cham cho ung vien tu tay dien/ky nhu ban goc.
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/docx.php';
$pdo = get_pdo();

// (2026-10-10) Ten file tai ve: bo dau tieng Viet thay vi xoa han chu co dau — truoc day
// "Nguyễn Văn Đức" thanh "Nguy_n_V_n_c".
if (!function_exists('hkn_ten_file')) {
    function hkn_ten_file(string $s): string {
        $bang = ['a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ', 'o' => 'òóọỏõôồốộổỗơờớợởỡ',
                 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ'];
        foreach ($bang as $khong => $co) {
            $s = preg_replace('/[' . $co . ']/u', $khong, $s);
            $s = preg_replace('/[' . mb_strtoupper($co, 'UTF-8') . ']/u', strtoupper($khong), $s);
        }
        $s = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $s), '_');
        return $s !== '' ? $s : 'ho_so';
    }
}

function hkn_don_loi(string $msg): void {
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Không xuất được file Đơn xin gia nhập.\nLý do: $msg\n";
    exit;
}

function hkn_don_dong(string $nhan, string $gtri): array {
    return ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => $nhan . $gtri]]];
}

try {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { hkn_don_loi('Thiếu tham số id.'); }
    // Cho phep ca Admin toan quyen LAN chinh nguoi trong ho so tu xuat qua "Tra cứu CCB-CQN" tu
    // phuc vu (2026-09-23, xem hccb_tra_cuu_co_quyen() trong includes/functions.php).
    if (!empty($_SESSION['admin_id'])) {
        require_full_admin();
    } elseif (!hccb_tra_cuu_co_quyen($id)) {
        hkn_don_loi('Bạn không có quyền xem hồ sơ này. Vui lòng đăng nhập lại tại trang "Tra cứu CCB-CQN".');
    }
    $stmt = $pdo->prepare('SELECT * FROM hccb_ho_so_ket_nap WHERE id = ?');
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if (!$r) { hkn_don_loi('Không tìm thấy hồ sơ.'); }

    $tenTruong = get_site_setting('ten_don_vi', 'TRƯỜNG ĐẠI HỌC Y DƯỢC CẦN THƠ');
    $ngaySinhStr = $r['ngay_sinh'] ? date('d/m/Y', strtotime($r['ngay_sinh'])) : '……………………';
    $dangVienStr = !empty($r['dang_vien']) ? 'Có' : 'Chưa';
    $ngayVaoDangStr = $r['ngay_vao_dang'] ? date('d/m/Y', strtotime($r['ngay_vao_dang'])) : '……………';
    $ngayChinhThucStr = $r['ngay_chinh_thuc_dang'] ? date('d/m/Y', strtotime($r['ngay_chinh_thuc_dang'])) : '……………';
    $ngayNhapNguStr = $r['ngay_nhap_ngu'] ? date('d/m/Y', strtotime($r['ngay_nhap_ngu'])) : '……………';
    $ngayXuatNguStr = $r['ngay_xuat_ngu'] ? date('d/m/Y', strtotime($r['ngay_xuat_ngu'])) : '……………';

    $blocks = [
        ['type' => 'p', 'align' => 'center', 'runs' => [['t' => 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', 'b' => true]]],
        ['type' => 'p', 'align' => 'center', 'spacingAfter' => 160, 'runs' => [['t' => 'Độc lập - Tự do - Hạnh phúc', 'b' => true]]],
        ['type' => 'p', 'align' => 'center', 'spacingAfter' => 160, 'runs' => [['t' => 'ĐƠN XIN VÀO HỘI CỰU CHIẾN BINH VIỆT NAM', 'b' => true, 'size' => 28]]],
        ['type' => 'p', 'align' => 'justify', 'spacingAfter' => 160, 'runs' => [['t' => 'Kính gửi: Ban Chấp hành Hội Cựu chiến binh ' . $tenTruong]]],
        hkn_don_dong('Tôi tên là: ', $r['ho_ten']),
        hkn_don_dong('- Ngày tháng năm sinh: ', $ngaySinhStr),
        hkn_don_dong('- Quê quán: ', $r['que_quan'] ?: '……………………………………………'),
        hkn_don_dong('- Chỗ ở hiện nay: ', $r['dia_chi_o'] ?: '……………………………………………'),
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '- Dân tộc: ' . ($r['dan_toc'] ?: '……….') . ', Tôn giáo: ' . ($r['ton_giao'] ?: '……….') . ', Trình độ văn hoá: ……….']]],
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '- Hiện đang là đảng viên: ' . $dangVienStr . ' — Ngày vào Đảng: ' . $ngayVaoDangStr . '  Chính thức: ' . $ngayChinhThucStr]]],
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '- Ngày nhập ngũ: ' . $ngayNhapNguStr . '  Xuất ngũ (chuyển ngành): ' . $ngayXuatNguStr . '  Tái ngũ: ……….']]],
        hkn_don_dong('- Khen thưởng: ', '……………………………………………'),
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '- Lớp: ' . ($r['lop'] ?: '……………') . '   Khoá: ……………']]],
        hkn_don_dong('- Kỷ luật: ', '……………………………………………'),
        hkn_don_dong('- Họ tên, tuổi, nghề nghiệp vợ, con: ', '……………………………………………'),
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '……………………………………………………………………………………………']]],
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '- Quá trình công tác (thời gian, cấp bậc, chức vụ, cơ quan, đơn vị): ' . ($r['don_vi_truoc_xuat_ngu'] ? ($r['cap_bac'] ?: '') . ' ' . $r['don_vi_truoc_xuat_ngu'] : '')]]],
        ['type' => 'p', 'align' => 'justify', 'spacingAfter' => 200, 'runs' => [['t' => '……………………………………………………………………………………………']]],
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => 'Sau khi được nghiên cứu Điều lệ Hội Cựu chiến binh Việt Nam tôi hoàn toàn tán thành Điều lệ Hội và xét thấy bản thân có đủ điều kiện tiêu chuẩn để trở thành hội viên Hội Cựu chiến binh Việt Nam.']]],
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => 'Tôi làm đơn này kính đề nghị Ban Chấp hành Hội Cựu chiến binh ' . $tenTruong . ' xem xét kết nạp tôi vào Hội. Tôi xin hứa tích cực học tập, phấn đấu hoàn thành tốt nhiệm vụ của người hội viên, góp phần xây dựng Hội trong sạch, vững mạnh toàn diện.']]],
        ['type' => 'p', 'align' => 'justify', 'spacingAfter' => 300, 'runs' => [['t' => 'Tôi xin chân thành cám ơn!']]],
        ['type' => 'p', 'align' => 'right', 'runs' => [['t' => 'Cần Thơ, ngày ...... tháng .... năm.....']]],
        ['type' => 'p', 'align' => 'right', 'runs' => [['t' => 'Người làm đơn', 'b' => true]]],
        ['type' => 'p', 'align' => 'right', 'runs' => [['t' => '(Ký, ghi rõ họ tên)', 'i' => true]]],
    ];

    $tmpFile = @tempnam(sys_get_temp_dir(), 'hkd');
    if ($tmpFile === false) {
        $fallbackDir = UPLOAD_DIR . 'tmp/';
        if (!is_dir($fallbackDir)) { @mkdir($fallbackDir, 0755, true); }
        $tmpFile = $fallbackDir . 'hkd_' . uniqid() . '.tmp';
    }
    docx_write($tmpFile, $blocks);

    if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
        hkn_don_loi('Không tạo được file tạm trên máy chủ (có thể do quyền ghi thư mục).');
    }

    $safeName = 'DON_XIN_GIA_NHAP_HOI_' . hkn_ten_file((string)$r['ho_ten']) . '_' . date('YmdHis') . '.docx';
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    @unlink($tmpFile);
    exit;
} catch (Throwable $e) {
    hkn_don_loi($e->getMessage());
}
