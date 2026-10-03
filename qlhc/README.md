# Module Quản lý hành chính (QLHC)

Module PHP + MySQL gắn vào Cổng Đào tạo CTUMP (`index_QLDT_CSCE.html`). Module phục vụ công tác văn thư theo **Nghị định 30/2020/NĐ-CP** (văn bản hành chính) và **Hướng dẫn 05-HD/VPTW** (văn bản của Đảng).

## Chức năng

| Trang | Chức năng | Ai dùng |
|---|---|---|
| `tra-cuu.php` | Tra cứu thể thức NĐ 30 / HD 05: khổ giấy, lề, cỡ chữ từng thành phần, chữ viết tắt, viết hoa, quyền hạn ký. Có tìm nhanh và in trang. | Công khai |
| `bieu-mau.php` | Thư viện văn bản – biểu mẫu cho học viên tải về, có đếm lượt tải. Văn thư quản lý tại `?quan_ly=1`. | Công khai / Văn thư |
| `soan-thao.php` | Soạn văn bản theo mẫu, xem trước khổ A4 trực tiếp, xuất **.docx đúng thể thức**. Quy trình: Soạn → Trình ký → Lãnh đạo duyệt/trả lại → Văn thư cấp số & ban hành. | Mọi người dùng |
| `van-ban-di.php` | Sổ văn bản đi: cấp số tự động không trùng, vào sổ thủ công, đính kèm file, lọc, xuất Excel. | Văn thư (sửa), mọi người (xem) |
| `van-ban-den.php` | Sổ văn bản đến: vào sổ, phân công, ý kiến chỉ đạo, hạn xử lý, cảnh báo quá hạn, báo cáo kết quả, xuất Excel. | Văn thư, Lãnh đạo, người được giao |
| `kiem-tra.php` | Tải lên file .docx để kiểm tra thể thức. Tải bản **chuẩn hóa tự động** (phông, khổ A4, lề, số 0 ở ngày tháng/số văn bản). | Người dùng đã đăng nhập |
| `danh-muc.php` | Cài đặt tên cơ quan, viết tắt, cách đánh số, nhiệm kỳ Đảng; danh mục đơn vị, loại văn bản, người ký; xem bộ đếm số. | Quản trị |
| `nguoi-dung.php`, `nhat-ky.php` | Quản lý tài khoản, vai trò; nhật ký thao tác. | Quản trị |

**Vai trò:**
- **Quản trị**: toàn quyền.
- **Văn thư**: vào sổ đi/đến, cấp số, ban hành, quản lý biểu mẫu.
- **Lãnh đạo**: duyệt dự thảo, chỉ đạo văn bản đến.
- **Chuyên viên**: soạn dự thảo, xử lý văn bản được giao.

## Yêu cầu hosting

- PHP **7.4 – 8.3**, có các extension `pdo_mysql`, `zip`, `mbstring`, `dom` (hosting cPanel/DirectAdmin thường có sẵn).
- MySQL 5.7+ hoặc MariaDB 10.3+.
- Apache/LiteSpeed (đọc được `.htaccess`). Nếu dùng Nginx, xem mục cuối.
- Không cần Composer hay thư viện ngoài. File Word được tạo trực tiếp bằng `ZipArchive`.

## Cài đặt lên hosting

1. **Tải mã nguồn lên.** Chép cả thư mục `qlhc/` vào **cùng thư mục** với `index_QLDT_CSCE.html` (vd. `public_html/`). Dùng File Manager hoặc FTP.
   ```
   public_html/
   ├── index_QLDT_CSCE.html   (đã có link menu "Hành chính")
   └── qlhc/
   ```
2. **Tạo database.** Trong cPanel → *MySQL Databases*: tạo database và user, rồi gán **ALL PRIVILEGES**. Có thể dùng chung database với web hiện có vì mọi bảng có tiền tố `hc_`.
3. **Cài đặt.** Chọn **một** trong hai cách:
   - **Cách A (khuyến nghị):** mở `https://ten-mien/qlhc/install.php`, nhập thông tin MySQL và tài khoản quản trị, rồi bấm *Cài đặt*. Hệ thống tự tạo bảng, dữ liệu mặc định và file `config.php`.
   - **Cách B (thủ công):** vào phpMyAdmin → chọn database → *Import* file `install.sql`. Sau đó sao chép `config.sample.php` thành `config.php` và điền thông tin MySQL. Tài khoản mặc định là `admin` / `Admin@123`; hệ thống bắt đổi mật khẩu khi đăng nhập lần đầu.
4. **Phân quyền thư mục.** Thư mục `qlhc/uploads/` cần quyền ghi (755; nếu hosting yêu cầu thì 775).
5. **Xóa `install.php`** sau khi cài xong, cho an toàn.
6. Đăng nhập rồi vào **Danh mục & Cài đặt** để sửa:
   - tên cơ quan chủ quản, cơ quan ban hành, chữ viết tắt (mặc định `TTDV`);
   - đơn vị và chữ viết tắt đơn vị (dùng trong ký hiệu công văn);
   - mẫu người ký;
   - thông tin văn bản Đảng (cơ quan, viết tắt, nhiệm kỳ).
7. Vào **Người dùng** để tạo tài khoản văn thư, lãnh đạo, chuyên viên.

Nếu đổi tên file cổng (vd. thành `index.html`), sửa `portal_url` trong `config.php` để nút "← Cổng Đào tạo" trỏ đúng.

## Quy tắc cấp số

- **Văn bản hành chính:** số bắt đầu từ 01 vào đầu năm, kết thúc ngày 31/12. Có thể chọn một trong hai cách tại *Danh mục → Thông tin cơ quan*:
  - một dãy chung cho mọi loại văn bản (theo NĐ 30);
  - mỗi loại văn bản một dãy riêng.

  Ký hiệu có dạng `05/QĐ-TTDV`; công văn có dạng `125/TTDV-ĐT`.
- **Văn bản Đảng:** số liên tục từ 01 cho mỗi tên loại trong một nhiệm kỳ cấp ủy. Ký hiệu có dạng `Số 05-BC/CB` (không có dấu hai chấm). Khi sang nhiệm kỳ mới, sửa trường *Nhiệm kỳ* thì số tự đánh lại từ 01.
- Cấp số dùng khóa dòng (`SELECT … FOR UPDATE`) nên không trùng khi nhiều người cấp cùng lúc. Văn thư vẫn có thể nhập số thủ công; hệ thống chặn số trùng.

## Bảo mật đã áp dụng

- Mật khẩu băm bằng `password_hash`. Khóa đăng nhập 5 phút sau 5 lần sai liên tiếp. Phiên hết hạn sau 8 giờ không thao tác.
- Mọi form có mã CSRF. Mọi truy vấn dùng prepared statement (PDO).
- File tải lên kiểm tra đuôi và dung lượng, được đổi tên ngẫu nhiên. Thư mục `uploads/` chặn truy cập trực tiếp; file chỉ tải qua PHP sau khi kiểm tra quyền.
- `.htaccess` chặn truy cập `config.php`, `install.sql`, `includes/`.
- Nhật ký ghi lại mọi thao tác quan trọng (cấp số, ban hành, sửa, xóa, đăng nhập).

## Sao lưu

- **Database:** phpMyAdmin → *Export* các bảng `hc_*` (nên làm hằng tuần).
- **File:** tải về thư mục `qlhc/uploads/`.

## Ghi chú cho Nginx

Thêm vào cấu hình server:
```nginx
location ~ ^/qlhc/(config\.php|install\.sql|README\.md)$ { deny all; }
location ^~ /qlhc/includes/ { deny all; }
location ^~ /qlhc/uploads/  { deny all; }
client_max_body_size 25m;
```

## Xử lý sự cố

| Hiện tượng | Cách xử lý |
|---|---|
| Trắng trang / lỗi 500 | Đặt `'debug' => true` trong `config.php` để xem lỗi. Kiểm tra phiên bản PHP ≥ 7.4. |
| Không tải được file lớn | Tăng `upload_max_filesize` và `post_max_size` trong cPanel → *Select PHP Version → Options*. |
| Lỗi "ZipArchive" | Bật extension `zip` trong cPanel → *Select PHP Version*. |
| Quên mật khẩu admin | Trong phpMyAdmin, chạy lệnh SQL dưới đây. Mật khẩu sẽ về `Admin@123` và hệ thống bắt đổi khi đăng nhập. |

Lệnh đặt lại mật khẩu admin:

```sql
UPDATE hc_nguoi_dung SET mat_khau='$2y$10$7wJASDUk85JKjDcgyGiK4Oez9knioWY6pe6.JHrMbTNNGKqrZCuZG', doi_mat_khau=1, kich_hoat=1 WHERE ten_dang_nhap='admin';
```

## Cấu trúc thư mục

```
qlhc/
├── install.php / install.sql / config.sample.php
├── index.php            Tổng quan
├── soan-thao.php        Soạn thảo + quy trình duyệt, ban hành
├── van-ban-di.php       Sổ văn bản đi
├── van-ban-den.php      Sổ văn bản đến
├── kiem-tra.php         Kiểm tra & chuẩn hóa .docx
├── tra-cuu.php          Tra cứu thể thức (công khai)
├── bieu-mau.php         Văn bản - Biểu mẫu (công khai)
├── danh-muc.php, nguoi-dung.php, nhat-ky.php   Quản trị
├── login.php, logout.php, doi-mat-khau.php
├── includes/
│   ├── bootstrap.php, helpers.php, auth.php, layout.php
│   ├── vanban.php       Dựng bố cục văn bản → .docx và xem trước HTML
│   ├── kiemtra.php      Bộ kiểm tra & chuẩn hóa thể thức
│   ├── nghiepvu.php     Cấp số, mẫu nội dung soạn sẵn
│   └── thethuc.php      Dữ liệu tra cứu thể thức
├── assets/qlhc.css, qlhc.js
└── uploads/             File tải lên (chặn truy cập trực tiếp)
```
