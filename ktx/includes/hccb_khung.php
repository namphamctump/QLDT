<?php
// KHUNG GIAO DIEN nhanh Hoi Cuu chien binh (2026-10-10) — dung lai CSS/bieu tuong/bo cuc cua
// includes/ktx_khung.php, chi doi MENU trai + logo Hoi. Muc menu nao CHUA co file tren may chu thi
// tu an (de khong tao link hong). Dung trong admin/hccb_*.php SAU require header.php:
//     hccb_khung_mo('hccb_van_ban.php', 'VĂN BẢN – BIỂU MẪU', 'Hội Cựu chiến binh', $giuaHtml, $phaiHtml);
//     ... noi dung ...
//     ktx_khung_dong();
require_once __DIR__ . '/ktx_khung.php';

function hccb_khung_menu(): array {
    $menu = [
        ['Hội Cựu chiến binh', [
            ['hccb_dashboard.php', 'Trang chủ quản trị', 'home'],
            ['ktx_hccb_can_bo.php', 'Danh sách HCCB', 'users'],
            ['hccb_ket_nap.php', 'Kết nạp hội viên', 'medal'],
            ['hccb_tai_khoan.php', 'Tài khoản tra cứu', 'key'],
        ]],
        ['Trang công khai', [
            ['hccb_van_ban.php', 'Văn bản – Biểu mẫu', 'file'],
            ['hccb_menu.php', 'Menu Hội CCB', 'menu'],
            ['hccb_hero_banner.php', 'Banner Tiêu điểm', 'image'],
        ]],
    ];
    $dir = dirname($_SERVER['SCRIPT_FILENAME'] ?? __FILE__);
    foreach ($menu as &$nhom) {
        $nhom[1] = array_values(array_filter($nhom[1], fn($m) => is_file($dir . '/' . $m[0])));
    }
    unset($nhom);
    return array_values(array_filter($menu, fn($n) => $n[1]));
}

function hccb_khung_mo(string $trangHienTai, string $tieuDe, string $phuDe, string $giuaHtml = '', string $phaiHtml = ''): void {
    $logo = function_exists('hccb_logo_img') ? hccb_logo_img(30) : '';
    ktx_khung_mo($trangHienTai, $tieuDe, $phuDe, $giuaHtml, $phaiHtml, hccb_khung_menu(), $logo, null);
}
