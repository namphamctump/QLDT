<?php
/**
 * Nghiệp vụ văn thư dùng chung: vào sổ văn bản đi (cấp số), vào sổ văn bản đến.
 */

/**
 * Vào sổ văn bản đi và cấp số. Phải gọi bên trong transaction.
 *
 * $v: he, loai_id, ngay_ban_hanh, trich_yeu, nguoi_ky, chuc_vu_ky, don_vi_soan_id,
 *     noi_nhan, so_ban, do_khan, ghi_chu, du_thao_id, so_tay (0 = tự động)
 * Trả về bản ghi vừa tạo.
 */
function vao_so_di(array $v): array
{
    $he = ($v['he'] ?? 'hc') === 'dang' ? 'dang' : 'hc';
    $loai = loai_by_id((int)$v['loai_id']);
    if (!$loai || $loai['he'] !== $he) {
        throw new RuntimeException('Loại văn bản không hợp lệ.');
    }
    $ngay = $v['ngay_ban_hanh'] ?: date('Y-m-d');
    $nam = (int)substr($ngay, 0, 4);
    $pham_vi = pham_vi_so_di($he, $nam, $loai);

    $so_tay = (int)($v['so_tay'] ?? 0);
    if ($so_tay > 0) {
        if (q_val('SELECT id FROM hc_van_ban_di WHERE pham_vi = ? AND so = ?', [$pham_vi, $so_tay])) {
            throw new RuntimeException('Số ' . so_pad($so_tay) . ' đã được cấp trong sổ này.');
        }
        $so = $so_tay;
        dong_bo_bo_dem($pham_vi, $so);
    } else {
        $so = cap_so_ke_tiep($pham_vi, max_so_di($pham_vi));
    }

    $dv_vt = '';
    if (!empty($v['don_vi_soan_id'])) {
        $dv_vt = (string)q_val('SELECT viet_tat FROM hc_don_vi WHERE id = ?', [(int)$v['don_vi_soan_id']]);
    }
    $skh = so_ky_hieu($he, $so, $loai, $dv_vt);

    q('INSERT INTO hc_van_ban_di (he, nam, so, pham_vi, so_ky_hieu, loai_id, ngay_ban_hanh, trich_yeu, nguoi_ky, chuc_vu_ky, don_vi_soan_id, noi_nhan, so_ban, do_khan, du_thao_id, ghi_chu, nguoi_tao)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
        $he, $nam, $so, $pham_vi, $skh, $loai['id'], $ngay, (string)$v['trich_yeu'],
        (string)($v['nguoi_ky'] ?? ''), (string)($v['chuc_vu_ky'] ?? ''),
        !empty($v['don_vi_soan_id']) ? (int)$v['don_vi_soan_id'] : null,
        (string)($v['noi_nhan'] ?? ''), max(1, (int)($v['so_ban'] ?? 1)), (string)($v['do_khan'] ?? ''),
        !empty($v['du_thao_id']) ? (int)$v['du_thao_id'] : null, (string)($v['ghi_chu'] ?? ''), uid() ?: null,
    ]);
    $id = (int)db()->lastInsertId();
    return q_one('SELECT * FROM hc_van_ban_di WHERE id = ?', [$id]);
}

/** Số đến kế tiếp của năm (gọi trong transaction). */
function cap_so_den(int $nam): int
{
    $max = (int)q_val('SELECT COALESCE(MAX(so_den),0) FROM hc_van_ban_den WHERE nam = ?', [$nam]);
    return cap_so_ke_tiep('den:' . $nam, $max);
}

/** Mẫu nội dung soạn sẵn theo loại văn bản (khóa = tên loại). */
function mau_noi_dung(): array
{
    $cq = setting('ten_co_quan');
    $cq_thuong = setting('ten_co_quan_thuong');
    if ($cq_thuong === '') {
        $t = mb_strtolower($cq);
        $cq_thuong = mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
    }
    return [
        'Quyết định' => [
            'Mở lớp đào tạo' => [
                'trich_yeu' => 'Về việc mở lớp ... khóa ...',
                'noi_dung' => "GIÁM ĐỐC " . upper($cq) . "\nCăn cứ Quyết định số .../QĐ-ĐHYDCT ngày ... tháng ... năm ... của Hiệu trưởng Trường Đại học Y Dược Cần Thơ về việc quy định chức năng, nhiệm vụ, quyền hạn của Trung tâm;\nCăn cứ Kế hoạch số .../KH-... ngày ... tháng ... năm ... về việc tổ chức lớp ...;\nXét đề nghị của Bộ phận Đào tạo.\nQUYẾT ĐỊNH:\nĐiều 1. Mở lớp ... khóa ..., thời gian đào tạo từ ngày ... tháng ... năm ... đến ngày ... tháng ... năm ..., gồm ... học viên (có danh sách kèm theo).\nĐiều 2. Giao Bộ phận Đào tạo chủ trì, phối hợp các bộ phận liên quan tổ chức lớp học theo đúng chương trình đã được phê duyệt.\nĐiều 3. Quyết định này có hiệu lực kể từ ngày ký. Bộ phận Đào tạo, Bộ phận Kế toán, các bộ phận liên quan và các học viên có tên tại Điều 1 chịu trách nhiệm thi hành Quyết định này./.",
                'noi_nhan' => "Như Điều 3\nBan Giám hiệu (để báo cáo)\nLưu: VT, ĐT",
            ],
            'Công nhận hoàn thành khóa học' => [
                'trich_yeu' => 'Về việc công nhận học viên hoàn thành khóa đào tạo ...',
                'noi_dung' => "GIÁM ĐỐC " . upper($cq) . "\nCăn cứ Quyết định số .../QĐ-ĐHYDCT ngày ... tháng ... năm ... của Hiệu trưởng Trường Đại học Y Dược Cần Thơ về việc quy định chức năng, nhiệm vụ, quyền hạn của Trung tâm;\nCăn cứ kết quả học tập của học viên lớp ... khóa ...;\nXét đề nghị của Bộ phận Đào tạo.\nQUYẾT ĐỊNH:\nĐiều 1. Công nhận ... học viên hoàn thành khóa đào tạo ... (có danh sách kèm theo).\nĐiều 2. Các học viên có tên tại Điều 1 được cấp chứng chỉ/giấy chứng nhận theo quy định.\nĐiều 3. Quyết định này có hiệu lực kể từ ngày ký. Bộ phận Đào tạo, các bộ phận liên quan và các học viên có tên tại Điều 1 chịu trách nhiệm thi hành Quyết định này./.",
                'noi_nhan' => "Như Điều 3\nLưu: VT, ĐT",
            ],
            'Thành lập Hội đồng thẩm định' => [
                'trich_yeu' => 'Về việc thành lập Hội đồng thẩm định chương trình và tài liệu đào tạo ...',
                'noi_dung' => "GIÁM ĐỐC " . upper($cq) . "\nCăn cứ ...;\nXét đề nghị của Bộ phận Đào tạo.\nQUYẾT ĐỊNH:\nĐiều 1. Thành lập Hội đồng thẩm định chương trình và tài liệu đào tạo ... gồm các ông (bà) có tên sau:\n1. ..., Chủ tịch Hội đồng;\n2. ..., Thư ký;\n3. ..., Ủy viên.\nĐiều 2. Hội đồng có nhiệm vụ thẩm định chương trình và tài liệu đào tạo ... theo quy định. Hội đồng tự giải thể sau khi hoàn thành nhiệm vụ.\nĐiều 3. Quyết định này có hiệu lực kể từ ngày ký. Bộ phận Đào tạo, các bộ phận liên quan và các ông (bà) có tên tại Điều 1 chịu trách nhiệm thi hành Quyết định này./.",
                'noi_nhan' => "Như Điều 3\nLưu: VT, ĐT",
            ],
        ],
        'Thông báo' => [
            'Chiêu sinh' => [
                'trich_yeu' => 'Chiêu sinh lớp ... khóa ...',
                'noi_dung' => "{$cq_thuong} thông báo chiêu sinh lớp ... như sau:\n1. Đối tượng: ...\n2. Thời gian đào tạo: từ ngày ... đến ngày ...\n3. Địa điểm: ...\n4. Học phí: ... đồng/học viên.\n5. Hồ sơ đăng ký: đăng ký trực tuyến tại Cổng dịch vụ công của Trường hoặc nộp trực tiếp tại Trung tâm.\nThông tin chi tiết liên hệ: ..., điện thoại: ...\nTrân trọng thông báo./.",
                'noi_nhan' => "Các đơn vị liên quan\nWebsite Trung tâm\nLưu: VT, ĐT",
            ],
        ],
        'Giấy mời' => [
            'Mời dự khai giảng/họp' => [
                'trich_yeu' => 'Dự lễ khai giảng lớp ...',
                'noi_dung' => "{$cq_thuong} trân trọng kính mời: ...\nTới dự: ...\nThời gian: ... giờ ..., ngày ... tháng ... năm ...\nĐịa điểm: ...\nRất mong ... đến dự đúng giờ./.",
                'noi_nhan' => "Như trên\nLưu: VT, ĐT",
            ],
        ],
        'Kế hoạch' => [
            'Tổ chức lớp đào tạo' => [
                'trich_yeu' => 'Tổ chức lớp ... năm ...',
                'noi_dung' => "I. MỤC ĐÍCH, YÊU CẦU\n1. Mục đích\n...\n2. Yêu cầu\n...\nII. NỘI DUNG\n1. Đối tượng: ...\n2. Thời gian, địa điểm: ...\n3. Chương trình: ...\nIII. KINH PHÍ\n...\nIV. TỔ CHỨC THỰC HIỆN\n1. Bộ phận Đào tạo: ...\n2. Bộ phận Kế toán: ...\nTrên đây là Kế hoạch tổ chức lớp ... năm .../.",
                'noi_nhan' => "Ban Giám hiệu (để báo cáo)\nCác bộ phận thuộc Trung tâm\nLưu: VT, ĐT",
            ],
        ],
        'Báo cáo' => [
            'Báo cáo kết quả' => [
                'trich_yeu' => 'Kết quả thực hiện nhiệm vụ ... năm ...',
                'noi_dung' => "I. KẾT QUẢ THỰC HIỆN\n1. Công tác đào tạo\n...\n2. Công tác dịch vụ\n...\nII. TỒN TẠI, HẠN CHẾ VÀ NGUYÊN NHÂN\n...\nIII. PHƯƠNG HƯỚNG, NHIỆM VỤ THỜI GIAN TỚI\n...\nIV. KIẾN NGHỊ, ĐỀ XUẤT\n...\nTrên đây là báo cáo kết quả thực hiện nhiệm vụ ... của {$cq_thuong}./.",
                'noi_nhan' => "Ban Giám hiệu (để báo cáo)\nLưu: VT, HCTH",
            ],
        ],
        'Tờ trình' => [
            'Đề nghị phê duyệt' => [
                'trich_yeu' => 'Về việc đề nghị phê duyệt ...',
                'kinh_gui' => 'Hiệu trưởng Trường Đại học Y Dược Cần Thơ',
                'noi_dung' => "Căn cứ ...;\n{$cq_thuong} kính trình Hiệu trưởng xem xét, phê duyệt ... với các nội dung sau:\n1. ...\n2. ...\n{$cq_thuong} kính trình Hiệu trưởng xem xét, phê duyệt./.",
                'noi_nhan' => "Như trên\nLưu: VT, HCTH",
            ],
        ],
        'Công văn' => [
            'Đề nghị cử giảng viên' => [
                'trich_yeu' => 'cử giảng viên tham gia giảng dạy lớp ...',
                'kinh_gui' => "Khoa ...\nBộ môn ...",
                'noi_dung' => "{$cq_thuong} dự kiến tổ chức lớp ... từ ngày ... đến ngày ... tại ...\nĐể lớp học đạt chất lượng, Trung tâm trân trọng đề nghị Quý đơn vị cử giảng viên tham gia giảng dạy các nội dung sau: ...\nDanh sách đề nghị gửi về Trung tâm (qua Bộ phận Đào tạo) trước ngày ... tháng ... năm ...\nTrân trọng./.",
                'noi_nhan' => "Như trên\nLưu: VT, ĐT",
            ],
        ],
    ];
}
