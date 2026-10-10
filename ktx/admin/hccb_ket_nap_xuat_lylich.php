<?php
// Xuat file Word "TOM TAT LY LICH" theo dung 24 muc cua mau that (2026 - MẪU 2), 2026-09-19. Cac
// muc co du lieu san trong ho so (ho ten, ngay sinh, que quan, dan toc, dang vien, nhap/xuat ngu,
// cap bac...) duoc DIEN SAN; cac muc chi danh cho can bo Hoi lau nam/da nghi huu (thanh phan xuat
// than, cap uy Dang cao nhat, thang nam nghi huu, qua truong, khen thuong chi tiet...) GIU NGUYEN
// dong cham vi hoi vien la sinh vien vua xuat ngu, hau het se khong co du lieu that cho cac muc do.
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

function hkn_ll_loi(string $msg): void {
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Không xuất được file Tóm tắt lý lịch.\nLý do: $msg\n";
    exit;
}

function hkn_ll_muc(int $so, string $noiDung): array {
    return ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => $so . '. ' . $noiDung]]];
}

function hkn_ll_ngay(?string $ymd): string {
    return $ymd ? date('d/m/Y', strtotime($ymd)) : '……………………';
}

try {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { hkn_ll_loi('Thiếu tham số id.'); }
    // Cho phep ca Admin toan quyen LAN chinh nguoi trong ho so tu xuat qua "Tra cứu CCB-CQN" tu
    // phuc vu (2026-09-23, xem hccb_tra_cuu_co_quyen() trong includes/functions.php).
    if (!empty($_SESSION['admin_id'])) {
        require_full_admin();
    } elseif (!hccb_tra_cuu_co_quyen($id)) {
        hkn_ll_loi('Bạn không có quyền xem hồ sơ này. Vui lòng đăng nhập lại tại trang "Tra cứu CCB-CQN".');
    }
    $stmt = $pdo->prepare('SELECT * FROM hccb_ho_so_ket_nap WHERE id = ?');
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if (!$r) { hkn_ll_loi('Không tìm thấy hồ sơ.'); }

    $tenTruong = get_site_setting('ten_don_vi', 'TRƯỜNG ĐẠI HỌC Y DƯỢC CẦN THƠ');
    $thangNamKetNap = $r['ngay_ky_qd'] ? ('Tháng ' . (int)date('n', strtotime($r['ngay_ky_qd'])) . '/' . date('Y', strtotime($r['ngay_ky_qd']))) : '……………';
    $capBacChucVu = trim(($r['cap_bac'] ?: '') . (($r['cap_bac'] && $r['chuc_vu_chinh_quyen']) ? ' — ' : '') . ($r['chuc_vu_chinh_quyen'] ?: ''));
    $donViCongTac = trim($capBacChucVu . (($capBacChucVu && $r['don_vi_truoc_xuat_ngu']) ? ', ' : '') . ($r['don_vi_truoc_xuat_ngu'] ?: ''));
    $thoiGianCongTac = ($r['ngay_nhap_ngu'] || $r['ngay_xuat_ngu'])
        ? (hkn_ll_ngay($r['ngay_nhap_ngu']) . ' — ' . hkn_ll_ngay($r['ngay_xuat_ngu']))
        : '';

    // Kich thuoc do CHINH XAC tu file that "2026 - MẪU 2 -TÓM TẮT LÝ LỊCH CCB.doc" (2026-09-19).
    $headerTbl = ['type' => 'table', 'cols' => [4819, 4819], 'rows' => [[
        ['blocks' => [
            ['type' => 'p', 'align' => 'center', 'spacingAfter' => 0, 'runs' => [['t' => 'HỘI CỰU CHIẾN BINH TP. CẦN THƠ', 'size' => 26]]],
            ['type' => 'p', 'align' => 'center', 'spacingAfter' => 0, 'runs' => [['t' => 'HỘI CCB ' . $tenTruong, 'b' => true, 'size' => 28]]],
        ]],
        ['blocks' => [
            ['type' => 'p', 'align' => 'center', 'spacingAfter' => 0, 'runs' => [['t' => 'CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM', 'b' => true, 'size' => 26]]],
            ['type' => 'p', 'align' => 'center', 'spacingAfter' => 0, 'runs' => [['t' => 'Độc lập - Tự do - Hạnh phúc', 'b' => true, 'size' => 26]]],
        ]],
    ]]];

    $bangCongTac = ['type' => 'table', 'borders' => true, 'cols' => [2400, 2400, 3200, 1638], 'rows' => array_merge(
        [[
            ['blocks' => [['type' => 'p', 'align' => 'center', 'runs' => [['t' => 'Thời gian', 'b' => true]]]]],
            ['blocks' => [['type' => 'p', 'align' => 'center', 'runs' => [['t' => 'Cấp bậc', 'b' => true]]]]],
            ['blocks' => [['type' => 'p', 'align' => 'center', 'runs' => [['t' => 'Chức vụ, đơn vị và chức vụ cán bộ Hội đã qua', 'b' => true]]]]],
            ['blocks' => [['type' => 'p', 'align' => 'center', 'runs' => [['t' => 'Chức vụ Đảng', 'b' => true]]]]],
        ]],
        [[
            ['blocks' => [['type' => 'p', 'align' => 'left', 'runs' => [['t' => $thoiGianCongTac]]]]],
            ['blocks' => [['type' => 'p', 'align' => 'left', 'runs' => [['t' => $r['cap_bac'] ?: '']]]]],
            ['blocks' => [['type' => 'p', 'align' => 'left', 'runs' => [['t' => trim(($r['chuc_vu_chinh_quyen'] ?: '') . ' ' . ($r['don_vi_truoc_xuat_ngu'] ?: ''))]]]]],
            ['blocks' => [['type' => 'p', 'align' => 'left', 'runs' => [['t' => '']]]]],
        ]],
        array_fill(0, 6, [
            ['blocks' => [['type' => 'p', 'align' => 'left', 'runs' => [['t' => '']]]]],
            ['blocks' => [['type' => 'p', 'align' => 'left', 'runs' => [['t' => '']]]]],
            ['blocks' => [['type' => 'p', 'align' => 'left', 'runs' => [['t' => '']]]]],
            ['blocks' => [['type' => 'p', 'align' => 'left', 'runs' => [['t' => '']]]]],
        ])
    )];

    $xacNhanTbl = ['type' => 'table', 'cols' => [4819, 4819], 'rows' => [[
        ['blocks' => [
            ['type' => 'p', 'align' => 'center', 'runs' => [['t' => 'Xác nhận của Tổ chức cơ sở Hội', 'b' => true]]],
        ]],
        ['blocks' => [
            ['type' => 'p', 'align' => 'center', 'runs' => [['t' => 'Ngày ….. tháng ….. năm …..']]],
            ['type' => 'p', 'align' => 'center', 'runs' => [['t' => 'NGƯỜI KHAI KÝ TÊN', 'b' => true]]],
        ]],
    ]]];

    $blocks = [
        $headerTbl,
        ['type' => 'p', 'align' => 'center', 'spacingAfter' => 20, 'runs' => [['t' => '']]],
        ['type' => 'p', 'align' => 'center', 'spacingAfter' => 200, 'runs' => [['t' => 'TÓM TẮT LÝ LỊCH', 'b' => true, 'size' => 28]]],
        hkn_ll_muc(1, 'Họ và tên thường dùng: ' . $r['ho_ten'] . ', Điện thoại liên lạc: ' . ($r['so_dt'] ?: '……………')),
        hkn_ll_muc(2, 'Họ và tên khai sinh (nếu khác): ……………………………………………'),
        hkn_ll_muc(3, 'Chức vụ cán bộ Hội hiện nay: Hội viên'),
        hkn_ll_muc(4, 'Ngày, tháng, năm sinh: ' . hkn_ll_ngay($r['ngay_sinh'])),
        hkn_ll_muc(5, 'Quê quán: ' . ($r['que_quan'] ?: '……………………………………………')),
        hkn_ll_muc(6, 'Chỗ ở hiện nay: ' . ($r['dia_chi_o'] ?: '……………………………………………')),
        hkn_ll_muc(7, 'Dân tộc: ' . ($r['dan_toc'] ?: '……………') . '; Tôn giáo: ' . ($r['ton_giao'] ?: '……………')),
        hkn_ll_muc(8, 'Thành phần: + Xuất thân: ……………; + Bản thân: ……………'),
        hkn_ll_muc(9, 'Trình độ: + Văn hoá: ……………; + Lý luận chính trị: ……………; + Ngoại ngữ: ……………; + Tin học: ……………'),
        hkn_ll_muc(10, 'Ngày tham gia cách mạng: ……………………………………………'),
        hkn_ll_muc(11, 'Nhập ngũ: ' . hkn_ll_ngay($r['ngay_nhap_ngu']) . '; Xuất ngũ: ' . hkn_ll_ngay($r['ngay_xuat_ngu']) . '; Tái ngũ: ……………'),
        hkn_ll_muc(12, 'Ngày vào Đảng: ' . hkn_ll_ngay($r['ngay_vao_dang']) . ', Ngày chính thức: ' . hkn_ll_ngay($r['ngay_chinh_thuc_dang'])),
        hkn_ll_muc(13, 'Cấp bậc, chức vụ trong Quân đội trước khi nghỉ hưu, xuất ngũ, chuyển ngành: ' . ($donViCongTac ?: '……………………………………………') . '. Ngạch công chức (nếu có): ……………'),
        hkn_ll_muc(14, 'Chức vụ cao nhất đã qua: + Trong Quân đội: ……………; + Ngoài Quân đội: ……………'),
        hkn_ll_muc(15, 'Cấp uỷ Đảng cao nhất đã qua: + Trong Quân đội: ……………; + Ngoài Quân đội: ……………. Cấp uỷ Đảng hiện nay: ……………'),
        hkn_ll_muc(16, 'Tháng, năm nghỉ hưu: ……………………………………………'),
        hkn_ll_muc(17, 'Tháng, năm kết nạp hội viên Hội CCB: ' . $thangNamKetNap),
        hkn_ll_muc(18, 'Qua trường (tên trường, thời gian) trong và ngoài Quân đội: ……………………………………………'),
        hkn_ll_muc(19, 'Khen thưởng (hình thức, lý do, thời gian): ……………………………………………'),
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '+ Anh hùng Lực lượng vũ trang: ……………………………………………']]],
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '+ Anh hùng Lao động: ……………………………………………']]],
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '+ Huân chương các loại: ……………………………………………']]],
        ['type' => 'p', 'align' => 'justify', 'runs' => [['t' => '+ Các danh hiệu vinh dự: ……………………………………………']]],
        hkn_ll_muc(20, 'Kỷ luật (hình thức, lý do, thời gian): ……………………………………………'),
        hkn_ll_muc(21, 'Họ tên, năm sinh, nghề nghiệp vợ, con: ……………………………………………'),
        hkn_ll_muc(22, 'Sức khoẻ, thương tật: ……………………………………………'),
        ['type' => 'p', 'align' => 'justify', 'spacingAfter' => 100, 'runs' => [['t' => '23. Tóm tắt quá trình công tác:']]],
        $bangCongTac,
        hkn_ll_muc(24, 'Tóm tắt tự nhận xét: ……………………………………………'),
        ['type' => 'p', 'align' => 'justify', 'spacingAfter' => 400, 'runs' => [['t' => '……………………………………………………………………………………………']]],
        $xacNhanTbl,
    ];

    $tmpFile = @tempnam(sys_get_temp_dir(), 'hll');
    if ($tmpFile === false) {
        $fallbackDir = UPLOAD_DIR . 'tmp/';
        if (!is_dir($fallbackDir)) { @mkdir($fallbackDir, 0755, true); }
        $tmpFile = $fallbackDir . 'hll_' . uniqid() . '.tmp';
    }
    docx_write($tmpFile, $blocks);

    if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
        hkn_ll_loi('Không tạo được file tạm trên máy chủ (có thể do quyền ghi thư mục).');
    }

    $safeName = 'TOM_TAT_LY_LICH_' . hkn_ten_file((string)$r['ho_ten']) . '_' . date('YmdHis') . '.docx';
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    @unlink($tmpFile);
    exit;
} catch (Throwable $e) {
    hkn_ll_loi($e->getMessage());
}
