<?php
/**
 * Dữ liệu tra cứu thể thức văn bản.
 *  - Hành chính: Nghị định 30/2020/NĐ-CP
 *  - Đảng: Hướng dẫn 05-HD/VPTW ngày 27/5/2026 của Văn phòng Trung ương Đảng
 *
 * Mỗi mục: ['tieu_de', 'loai' => 'kv'|'bang'|'doan', ...]
 */

return [
    'hc' => [
        'ten'  => 'Văn bản hành chính',
        'can_cu' => 'Nghị định 30/2020/NĐ-CP',
        'mo_ta' => 'Tóm tắt Nghị định số 30/2020/NĐ-CP ngày 05/3/2020 của Chính phủ về công tác văn thư (Phụ lục I – Thể thức, kỹ thuật trình bày văn bản hành chính).',
        'muc' => [
            [
                'tieu_de' => 'Khổ giấy, định lề, phông chữ',
                'loai' => 'kv',
                'dong' => [
                    ['Khổ giấy', 'A4 (210 x 297 mm), trình bày theo chiều dài; chỉ dùng chiều ngang khi bảng, biểu rộng không tách được phụ lục'],
                    ['Lề trang', 'Trên và dưới 20–25 mm; trái 30–35 mm; phải 15–20 mm (công cụ dùng 20/20/30/15 mm)'],
                    ['Phông chữ', 'Times New Roman, Unicode (TCVN 6909:2001), màu đen'],
                    ['Số trang', 'Chữ số Ả Rập, cỡ 13–14, kiểu đứng, canh giữa lề trên, không hiển thị ở trang đầu'],
                ],
            ],
            [
                'tieu_de' => 'Cỡ chữ, kiểu chữ từng thành phần',
                'loai' => 'bang',
                'cot' => ['Thành phần', 'Loại chữ', 'Cỡ', 'Kiểu', 'Ví dụ, ghi chú'],
                'dong' => [
                    ['Quốc hiệu', 'In hoa', '12–13', 'Đứng, đậm', 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM'],
                    ['Tiêu ngữ', 'In thường', '13–14', 'Đứng, đậm', 'Độc lập - Tự do - Hạnh phúc (có đường kẻ ngang dài bằng dòng chữ)'],
                    ['Tên cơ quan chủ quản', 'In hoa', '12–13', 'Đứng', 'ỦY BAN NHÂN DÂN TỈNH ...'],
                    ['Tên cơ quan ban hành', 'In hoa', '12–13', 'Đứng, đậm', 'Có đường kẻ ngang dài 1/3 đến 1/2 dòng chữ'],
                    ['Số, ký hiệu', 'In thường', '13', 'Đứng', 'Số: 15/QĐ-UBND (số nhỏ hơn 10 thêm số 0)'],
                    ['Địa danh, ngày tháng năm', 'In thường', '13–14', 'Nghiêng', 'An Giang, ngày 05 tháng 01 năm 2026'],
                    ['Tên loại văn bản', 'In hoa', '13–14', 'Đứng, đậm', 'KẾ HOẠCH'],
                    ['Trích yếu (có tên loại)', 'In thường', '13–14', 'Đứng, đậm', 'Về việc ... (có đường kẻ ngang dưới)'],
                    ['Trích yếu công văn (V/v)', 'In thường', '12–13', 'Đứng', 'V/v ... (cách dòng 6 pt dưới số, ký hiệu)'],
                    ['Nội dung văn bản', 'In thường', '13–14', 'Đứng', 'Canh đều hai bên; lùi đầu dòng 1 cm hoặc 1,27 cm; cách đoạn tối thiểu 6 pt; cách dòng đơn đến 1,5 lines'],
                    ['Phần, Chương (số)', 'In thường', '13–14', 'Đứng, đậm', 'Phần I, Chương I (số La Mã), tiêu đề in hoa bên dưới'],
                    ['Điều', 'In thường', '13–14', 'Đứng, đậm', 'Điều 1. Tiêu đề (số Ả Rập, sau số có dấu chấm)'],
                    ['Khoản, điểm', 'In thường', '13–14', 'Đứng', '1. ...; a) ... (điểm dùng chữ cái tiếng Việt, có đ)'],
                    ['Quyền hạn, chức vụ người ký', 'In hoa', '13–14', 'Đứng, đậm', 'TM. ỦY BAN NHÂN DÂN / CHỦ TỊCH'],
                    ['Họ tên người ký', 'In thường', '13–14', 'Đứng, đậm', 'Nguyễn Văn A (không ghi học hàm, học vị)'],
                    ['"Nơi nhận:"', 'In thường', '12', 'Nghiêng, đậm', 'Nơi nhận:'],
                    ['Danh sách nơi nhận', 'In thường', '11', 'Đứng', '- Như trên; - Lưu: VT, TCCB.'],
                    ['Số trang', 'In thường', '13–14', 'Đứng', 'Chữ số Ả Rập, canh giữa lề trên, không hiện ở trang 1'],
                ],
                'ghi_chu' => 'Cỡ chữ trong một văn bản phải thống nhất, ví dụ Quốc hiệu 13, Tiêu ngữ 14, địa danh và ngày tháng 14; hoặc 12, 13, 13.',
            ],
            [
                'tieu_de' => 'Chữ viết tắt tên loại văn bản',
                'loai' => 'viet_tat',
                'dong' => [
                    ['Quyết định', 'QĐ'], ['Nghị quyết', 'NQ'], ['Chỉ thị', 'CT'], ['Quy chế', 'QC'],
                    ['Quy định', 'QyĐ'], ['Thông cáo', 'TC'], ['Thông báo', 'TB'], ['Hướng dẫn', 'HD'],
                    ['Chương trình', 'CTr'], ['Kế hoạch', 'KH'], ['Phương án', 'PA'], ['Đề án', 'ĐA'],
                    ['Dự án', 'DA'], ['Báo cáo', 'BC'], ['Tờ trình', 'TTr'], ['Biên bản', 'BB'],
                    ['Công điện', 'CĐ'], ['Giấy mời', 'GM'], ['Giấy giới thiệu', 'GGT'], ['Giấy nghỉ phép', 'GNP'],
                    ['Hợp đồng', 'HĐ'], ['Bản ghi nhớ', 'BGN'], ['Bản thỏa thuận', 'BTT'], ['Giấy ủy quyền', 'GUQ'],
                    ['Phiếu gửi', 'PG'], ['Phiếu chuyển', 'PC'], ['Phiếu báo', 'PB'], ['Bản sao (sao y, sao lục, trích sao)', 'SY'],
                    ['Bản sao lục', 'SL'], ['Bản trích sao', 'TrS'],
                ],
                'ghi_chu' => 'Công văn không có chữ viết tắt tên loại; ký hiệu = viết tắt cơ quan - viết tắt đơn vị soạn thảo (125/SGDĐT-VP).',
            ],
            [
                'tieu_de' => 'Quy tắc viết hoa thường gặp',
                'loai' => 'bang',
                'cot' => ['Trường hợp', 'Quy tắc', 'Ví dụ'],
                'dong' => [
                    ['Đầu câu', 'Viết hoa chữ cái đầu sau dấu chấm, chấm hỏi, chấm than và khi xuống dòng', 'Sở đã nhận được báo cáo. Đề nghị...'],
                    ['Đơn vị hành chính', 'Danh từ chung viết thường, tên riêng viết hoa; trừ Thủ đô Hà Nội, Thành phố Hồ Chí Minh; kết hợp số thì viết hoa danh từ chung', 'tỉnh Nam Định; Quận 1, Phường Điện Biên Phủ'],
                    ['Cơ quan, tổ chức', 'Viết hoa chữ cái đầu các từ chỉ loại hình, chức năng, lĩnh vực', 'Bộ Giáo dục và Đào tạo, Ủy ban nhân dân tỉnh Sơn La'],
                    ['Văn bản cụ thể', 'Viết hoa chữ cái đầu tên loại và âm tiết đầu của tên gọi', 'Luật Giáo dục, Quyết định số 12/QĐ-UBND'],
                    ['Ngày tháng bằng chữ', 'Viết hoa âm tiết chỉ thứ, tháng; ngày tết viết hoa âm tiết đầu của tên', 'thứ Hai, tháng Tám, tết Nguyên đán'],
                ],
            ],
            [
                'tieu_de' => 'Ghi ngày tháng năm và quyền hạn ký',
                'loai' => 'doan_bang',
                'doan' => 'Ngày nhỏ hơn 10 và tháng 1, 2 ghi thêm số 0 phía trước; tháng 3 đến 12 không thêm số 0. Ví dụ: <i>Hà Nội, ngày 05 tháng 01 năm 2020</i>; <i>Thành phố Hồ Chí Minh, ngày 29 tháng 6 năm 2019</i>.',
                'cot' => ['Viết tắt', 'Trường hợp'],
                'dong' => [
                    ['TM.', 'Thay mặt tập thể lãnh đạo hoặc cơ quan, tổ chức'],
                    ['KT.', 'Cấp phó ký thay người đứng đầu'],
                    ['Q.', 'Được giao quyền cấp trưởng'],
                    ['TL.', 'Ký thừa lệnh'],
                    ['TUQ.', 'Ký thừa ủy quyền'],
                ],
            ],
        ],
    ],

    'dang' => [
        'ten'  => 'Văn bản của Đảng',
        'can_cu' => 'Hướng dẫn 05-HD/VPTW',
        'mo_ta' => 'Tóm tắt Hướng dẫn số 05-HD/VPTW ngày 27/5/2026 của Văn phòng Trung ương Đảng.',
        'muc' => [
            [
                'tieu_de' => 'Khổ giấy, định lề, phông chữ',
                'loai' => 'kv',
                'dong' => [
                    ['Khổ giấy', 'A4 (210 x 297 mm), chiều dọc; có bảng, biểu rộng thì được trình bày chiều ngang'],
                    ['Lề trang', 'Trên 20 mm; dưới 20 mm; trái 30 mm; phải 15 mm. In hai mặt: mặt sau trái 15 mm, phải 30 mm'],
                    ['Phông chữ', 'Times New Roman, Unicode (TCVN 6909:2001), màu đen'],
                    ['Số trang', 'Chữ số Ả Rập, cỡ 14, giữa trang, cách mép trên 10 mm; không hiển thị ở trang thứ nhất'],
                ],
            ],
            [
                'tieu_de' => 'Cỡ chữ, kiểu chữ từng thành phần',
                'loai' => 'bang',
                'cot' => ['Thành phần', 'Loại chữ', 'Cỡ', 'Kiểu', 'Ví dụ, ghi chú'],
                'dong' => [
                    ['Tiêu đề "Đảng Cộng sản Việt Nam"', 'In hoa', '15', 'Đứng, đậm', 'ĐẢNG CỘNG SẢN VIỆT NAM (góc phải, dòng đầu, có đường kẻ ngang nét liền dài bằng tiêu đề)'],
                    ['Tên cơ quan cấp trên trực tiếp', 'In hoa', '14', 'Đứng', 'ĐẢNG BỘ TỈNH LÂM ĐỒNG'],
                    ['Tên cơ quan ban hành', 'In hoa', '14', 'Đứng, đậm', 'ĐẢNG ỦY ĐẶC KHU PHÚ QUÝ; dưới có dấu sao (*)'],
                    ['Số và ký hiệu', 'In thường', '14', 'Đứng', 'Số 127-QĐ/TW (không có dấu hai chấm; số nhỏ hơn 10 thêm số 0)'],
                    ['Địa danh, ngày tháng năm', 'In thường', '14', 'Nghiêng', 'Hà Nội, ngày 03 tháng 02 năm 2026'],
                    ['Tên loại văn bản', 'In hoa', '15–16', 'Đứng, đậm', 'BÁO CÁO'],
                    ['Trích yếu (có tên loại)', 'In thường', '14–15', 'Đứng, đậm', 'kết quả đại hội chi bộ nhiệm kỳ ...; dưới có 5 dấu gạch nối (-----)'],
                    ['Trích yếu công văn', 'In thường', '12', 'Nghiêng', 'về việc ... (dưới số và ký hiệu)'],
                    ['Nội dung văn bản', 'In thường', '14–15', 'Đứng', 'Dàn đều hai lề; đầu dòng lùi khoảng 10 mm; cách đoạn tối thiểu 6 pt; cách dòng 18–22 pt (Exactly)'],
                    ['Phần, Chương, Mục, Điều', 'In thường', '14–15', 'Đứng, đậm', 'Phần I; Chương II; Mục 1; Điều 1. (tên phần, chương, mục in hoa)'],
                    ['Quyền hạn ký', 'In hoa', '14', 'Đứng, đậm', 'T/M BAN THƯỜNG VỤ; K/T TRƯỞNG BAN; T/L ...; Q. ...'],
                    ['Chức vụ người ký', 'In hoa', '14', 'Đứng', 'PHÓ BÍ THƯ'],
                    ['Họ tên người ký', 'In thường', '14', 'Đứng, đậm', 'Nguyễn Văn A'],
                    ['"Nơi nhận:"', 'In thường', '14', 'Đứng, gạch chân', 'Nơi nhận:'],
                    ['Danh sách nơi nhận', 'In thường', '12', 'Đứng', '- Ban Bí thư Trung ương; - Lưu Văn phòng Tỉnh ủy.'],
                    ['Dấu chỉ mức độ khẩn', 'In hoa', '14', 'Đứng, đậm', 'KHẨN; THƯỢNG KHẨN; HỎA TỐC (dưới số và ký hiệu)'],
                    ['Số trang', 'In thường', '14', 'Đứng', 'Chữ số Ả Rập, giữa trang, cách mép trên 10 mm, không hiện ở trang 1'],
                ],
            ],
            [
                'tieu_de' => 'Số, ký hiệu và quyền hạn ký',
                'loai' => 'doan_viet_tat',
                'doan' => 'Số văn bản ghi liên tục từ 01 cho mỗi tên loại trong một nhiệm kỳ cấp ủy. Ký hiệu gồm chữ viết tắt tên loại, dấu gạch chéo (/), chữ viết tắt tên cơ quan. Ví dụ: <i>Số 23-QĐ/UBKTTW</i>, <i>Số 06-CT/TU</i>.',
                'dong' => [
                    ['Nghị quyết', 'NQ'], ['Chỉ thị', 'CT'], ['Kết luận', 'KL'], ['Quy chế', 'QC'],
                    ['Quyết định, Quy định', 'QĐ'], ['Báo cáo', 'BC'], ['Chương trình', 'CTr'], ['Thông tri', 'TT'],
                    ['Tờ trình', 'TTr'],
                ],
                'cot' => ['Viết tắt', 'Trường hợp'],
                'dong2' => [
                    ['T/M', 'Thay mặt tập thể (ban thường vụ, ban chấp hành, hội đồng...)'],
                    ['K/T', 'Cấp phó ký thay cấp trưởng'],
                    ['T/L', 'Ký thừa lệnh'],
                    ['Q.', 'Quyền cấp trưởng'],
                ],
            ],
            [
                'tieu_de' => 'Nơi nhận, chữ ký, dấu',
                'loai' => 'doan',
                'doan' => 'Nơi nhận ở góc trái, dưới nội dung; công văn có "Kính gửi" ở chính giữa trang đầu, tờ trình có "Kính trình"; hai loại này có "Như trên" ở dòng đầu nơi nhận. Chữ ký trên văn bản điện tử màu xanh (.png nền trong suốt); dấu cơ quan mực đỏ tươi, trùm khoảng 1/3 chữ ký về bên trái.',
            ],
        ],
    ],
];
