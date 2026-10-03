<?php
/**
 * Cấu hình module QLHC.
 * Cách dùng thủ công: sao chép file này thành config.php và điền thông tin MySQL của hosting.
 * (Nếu chạy install.php thì file config.php được tạo tự động.)
 */
return [
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'ten_database',
        'user'    => 'tai_khoan_mysql',
        'pass'    => 'mat_khau_mysql',
        'charset' => 'utf8mb4',
    ],
    'timezone'      => 'Asia/Ho_Chi_Minh',
    'upload_max_mb' => 20,     // dung lượng tối đa mỗi file tải lên (MB)
    'debug'         => false,  // true để hiện lỗi PHP khi cần dò lỗi
    'portal_url'    => '../index_QLDT_CSCE.html', // link nút "← Cổng Đào tạo"
];
