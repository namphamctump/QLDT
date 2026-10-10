<?php
// KHUNG GIAO DIEN CHUNG cho cac trang nhanh Ky tuc xa (2026-10-10): dai tieu de xanh, menu KTX ben
// trai, bien mau + lop CSS dung chung (tien to hs-) va bo bieu tuong SVG (#hi-...). Dung trong cac
// trang admin/ktx_*.php SAU require header.php:
//     ktx_khung_mo('ktx_hoc_vien.php', 'HỒ SƠ NỘI TRÚ KTX', 'Quản lý người học ở nội trú', $giuaHtml, $phaiHtml);
//     ... noi dung trang ...
//     ktx_khung_dong();
// $giuaHtml / $phaiHtml: HTML da escape san (o tim kiem, nut lien ket...) dat giua / ben phai dai tieu de.

// Trang chinh cua nhanh KTX = Ho so noi tru; trang Tong quan mo bang ktx_dashboard.php?xem=tong_quan.
const KTX_TRANG_TONG_QUAN = 'ktx_dashboard.php?xem=tong_quan';

function ktx_khung_menu(): array {
    return [
        ['Ký túc xá', [
            ['ktx_hoc_vien.php', 'Hồ sơ nội trú', 'id'],
            [KTX_TRANG_TONG_QUAN, 'Tổng quan KTX', 'chart'],
            ['ktx_so_do_phong.php', 'Sơ đồ Phòng – Giường', 'bed'],
            ['ktx_don_dang_ky.php', 'Duyệt đăng ký', 'inbox'],
            ['ktx_diem_danh.php', 'Điểm danh theo đợt', 'check'],
        ]],
        ['Thu phí – Điện nước', [
            ['ktx_phi_noi_tru.php', 'Thu phí nội trú', 'coin'],
            ['ktx_dien_nuoc.php', 'Điện, nước', 'bolt'],
        ]],
        ['Cơ sở vật chất', [
            ['ktx_day_phong.php', 'Quản lý dãy / phòng', 'list'],
            ['ktx_sua_chua.php', 'Sửa chữa', 'tool'],
            ['ktx_thiet_bi.php', 'Thiết bị – CSVC', 'box'],
        ]],
        ['Quản lý – Liên lạc', [
            ['ktx_luu_tru_tam_tru.php', 'Khai báo lưu trú / tạm trú', 'pin'],
            ['ktx_ban_quan_ly.php', 'Ban Quản lý KTX', 'shield'],
            ['ktx_doi_sv.php', 'Cán sự / Đội SV', 'star'],
            ['ktx_doi_chieu_ds.php', 'Đối chiếu danh sách', 'swap'],
            ['ktx_thong_bao_email.php', 'Gửi thông báo email', 'mail'],
        ]],
    ];
}

function ktx_khung_mo(string $trangHienTai, string $tieuDe, string $phuDe, string $giuaHtml = '', string $phaiHtml = ''): void {
    ktx_khung_css();
    ktx_khung_icon();
    ?>
<div class="hs">
  <div class="hs-top">
    <a class="hs-brand" href="<?= h($trangHienTai) ?>"><span class="lg"><svg class="i"><use href="#hi-bed"/></svg></span><span><b><?= h($tieuDe) ?></b><small><?= h($phuDe) ?></small></span></a>
    <?= $giuaHtml ?>
    <?php if ($phaiHtml !== ''): ?><div class="hs-tr"><?= $phaiHtml ?></div><?php endif; ?>
  </div>
  <div class="hs-shell">
    <nav class="hs-side" aria-label="Chức năng Ký túc xá">
      <?php foreach (ktx_khung_menu() as [$nhom, $muc]): ?>
        <div class="cap"><?= h($nhom) ?></div>
        <?php foreach ($muc as [$f, $t, $ic]): ?><a class="<?= $f === $trangHienTai ? 'on' : '' ?>" href="<?= h($f) ?>"<?= $f === $trangHienTai ? ' aria-current="page"' : '' ?>><svg class="i"><use href="#hi-<?= $ic ?>"/></svg><?= h($t) ?></a><?php endforeach; ?>
      <?php endforeach; ?>
      <div class="sep"></div>
      <a href="ktx_hoc_vien.php?edit=new"><svg class="i"><use href="#hi-plus"/></svg>Thêm học viên</a>
    </nav>
    <div class="hs-main">
    <?php
}

function ktx_khung_dong(): void {
    echo "\n    </div>\n  </div>\n</div>\n";
}

// Goi ktx_khung_css() TRUOC <style> rieng cua trang de CSS rieng duoc uu tien (cascade); ktx_khung_mo()
// tu bo qua neu da in roi.
function ktx_khung_css(): void {
    static $daIn = false;
    if ($daIn) { return; }
    $daIn = true;
    ?>
<style>
/* ===== Khung chung nhanh KTX (includes/ktx_khung.php). Moi lop co tien to hs- de khong dung CSS chung admin.css ===== */
.hs,.hs-pop{--navy:#0d3a78;--navy2:#134b96;--pri:#1f6fd6;--pri-soft:#e7f0fc;--pri-line:#bcd3f3;--bg:#eef3fa;--line:#dfe7f2;--line2:#edf2f8;
  --ink:#1c2b41;--ink2:#4b5d75;--ink3:#8494aa;--green:#1f9d55;--green-soft:#e3f6eb;--orange:#f08c00;--orange-soft:#fff3e0;--red:#e03e3e;--red-soft:#fdeaea;--yellow:#f5b400;--violet:#6d4fd8;
  --nam:#1f6fd6;--nu:#d6457a;--shadow:0 1px 2px rgba(16,42,84,.06),0 4px 14px rgba(16,42,84,.06)}
.hs{max-width:1900px;margin:0 auto;padding:12px 14px 24px;background:var(--bg);color:var(--ink);font-family:"Segoe UI",Roboto,Arial,sans-serif;font-size:14px;border-radius:14px}
.hs *,.hs-pop *{box-sizing:border-box}.hs a{color:inherit}
.hs svg.i,.hs-pop svg.i{width:18px;height:18px;flex:none;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.hs-mut{color:var(--ink3)}
/* dai tieu de */
.hs-top{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 16px;border-radius:12px;color:#fff;background:linear-gradient(90deg,var(--navy),var(--navy2));box-shadow:0 2px 8px rgba(13,58,120,.25);margin-bottom:12px}
.hs-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff!important}
.hs-brand .lg{width:38px;height:38px;border-radius:50%;border:2px solid rgba(255,255,255,.85);display:grid;place-items:center}
.hs-brand b{display:block;font-size:19px;letter-spacing:.3px;line-height:1.1}.hs-brand small{display:block;font-size:10.5px;opacity:.8;text-transform:uppercase;letter-spacing:.4px}
.hs-gs{flex:1;min-width:220px;max-width:460px;position:relative;margin:0}
.hs-gs input{width:100%;height:34px;border:0;border-radius:6px;padding:0 38px 0 12px;background:#fff;color:var(--ink);font:inherit}
.hs-gs button,.hs-gs .ico{position:absolute;right:4px;top:4px;height:26px;width:30px;border:0;background:none;color:var(--ink2);cursor:pointer;display:grid;place-items:center}
.hs-tr{margin:0 0 0 auto;display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.hs-tb{display:flex;align-items:center;gap:6px;height:34px;padding:0 10px;border:0;border-radius:6px;background:transparent;color:#fff!important;text-decoration:none;white-space:nowrap;font:inherit;cursor:pointer}
.hs-tb:hover{background:rgba(255,255,255,.12)}
.hs-tb select{background:transparent;border:0;color:#fff;font:inherit;font-weight:600;cursor:pointer}.hs-tb select option{color:var(--ink)}
.hs-bell{position:relative}.hs-bell .n{position:absolute;top:1px;right:0;min-width:17px;height:17px;border-radius:9px;background:var(--red);font-size:10px;font-weight:700;display:grid;place-items:center;padding:0 4px}
/* khung 2 cot + menu */
.hs-shell{display:grid;grid-template-columns:224px 1fr;gap:12px;align-items:start}
.hs-side{background:#fff;border:1px solid var(--line);border-radius:12px;padding:8px;position:sticky;top:calc(var(--adm-h,0px) + 10px)}
.hs-side a{display:flex;align-items:center;gap:11px;padding:8px 11px;margin-bottom:1px;border-radius:8px;text-decoration:none;font-size:14px;color:var(--ink)}
.hs-side a svg{width:19px;height:19px;color:var(--navy2)}.hs-side a:hover{background:var(--pri-soft)}
.hs-side a.on{background:var(--pri);color:#fff;box-shadow:0 2px 6px rgba(31,111,214,.35)}.hs-side a.on svg{color:#fff}
.hs-side .sep{height:1px;background:var(--line);margin:8px 4px}
.hs-side .cap{font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--ink3);padding:10px 11px 4px}.hs-side .cap:first-child{padding-top:4px}
.hs-main{min-width:0}
/* the, nut, o nhap */
.hs-card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow)}
.hs-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
.hs-msg.ok{background:var(--green-soft);border:1px solid #b7e3c8;color:#14653a}.hs-msg.warn{background:#fff6da;border:1px solid #e0c46a;color:#6b5200}
.hs-msg.info{background:var(--pri-soft);border:1px solid var(--pri-line);color:var(--navy)}
.hs-btn{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border:1px solid var(--line);border-radius:7px;background:#fff;color:var(--ink)!important;cursor:pointer;font:inherit;font-size:13px;white-space:nowrap;text-decoration:none!important}
.hs-btn svg{width:15px;height:15px}.hs-btn:hover{border-color:var(--pri-line);background:#f7faff}
.hs-btn.pri{background:var(--pri);border-color:var(--pri);color:#fff!important}.hs-btn.pri:hover{background:#185fbd}
.hs-btn.b svg{color:var(--pri)}.hs-btn.g svg{color:var(--green)}.hs-btn.o svg{color:var(--orange)}.hs-btn.v svg{color:var(--violet)}.hs-btn.r svg{color:var(--red)}
.hs-btn.r{color:var(--red)!important}.hs-btn.off{opacity:.45;pointer-events:none}
.hs-btn.sm{height:28px;padding:0 9px;font-size:12.5px}
.hs-f{height:32px;border:1px solid var(--line);border-radius:7px;padding:0 10px;background:#fff;font:inherit;color:var(--ink);min-width:0}
.hs-dot{width:8px;height:8px;border-radius:50%;flex:none;display:inline-block}
.hs-pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:12.5px;font-weight:600;white-space:nowrap}
.hs-pill.green{background:var(--green-soft);color:var(--green)}.hs-pill.gray{background:#eef1f5;color:var(--ink2)}.hs-pill.orange{background:var(--orange-soft);color:var(--orange)}
.hs-pill.red{background:var(--red-soft);color:var(--red)}.hs-pill.blue{background:var(--pri-soft);color:var(--pri)}
.hs-ok{color:var(--green);font-weight:600}.hs-no{color:var(--red);font-weight:600}
/* the so lieu */
.hs-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:12px}
.hs-kpi{padding:14px 16px;display:flex;flex-direction:column;gap:10px}
.hs-kpi .tp{display:flex;align-items:center;gap:14px}
.hs-kpi .ic{width:52px;height:52px;border-radius:50%;display:grid;place-items:center;flex:none}.hs-kpi .ic svg{width:26px;height:26px}
.hs-kpi .num{font-size:28px;font-weight:700;line-height:1.05;color:var(--navy)}.hs-kpi .lb{color:var(--ink2)}
.hs-kpi .sb{display:flex;border-top:1px solid var(--line2);padding-top:10px}
.hs-kpi .sb div{flex:1;padding:0 8px;border-left:1px solid var(--line2)}.hs-kpi .sb div:first-child{border-left:0;padding-left:0}
.hs-kpi .sb b{display:block;font-size:18px}.hs-kpi .sb span{font-size:12.5px;color:var(--ink2)}
.hs-wl{list-style:none;margin:0;padding:0;display:grid;gap:5px}
.hs-wl a{display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--ink2);text-decoration:none}.hs-wl a:hover{color:var(--pri)}
.hs-wl b{min-width:24px;text-align:right}
.hs-wh{display:flex;align-items:center;gap:10px}.hs-wh .bd{width:36px;height:36px;border-radius:8px;background:var(--red);color:#fff;display:grid;place-items:center}.hs-wh h3{margin:0;font-size:17px;color:var(--ink)}
.hs-wh a{margin-left:auto;font-size:12.5px;color:var(--pri)}
table.hs-gtb{width:100%;border-collapse:collapse;font-size:13px;margin:0;border-top:1px solid var(--line2)}
table.hs-gtb th,table.hs-gtb td{padding:5px 4px;border:0;background:none;text-align:right;font-weight:600;color:var(--ink)}
table.hs-gtb thead th{font-size:12px;font-weight:400;color:var(--ink2);padding-top:8px}
table.hs-gtb tbody th,table.hs-gtb tfoot th{text-align:left;font-weight:400;color:var(--ink2);white-space:nowrap}
table.hs-gtb tbody th .hs-dot{margin-right:6px}
table.hs-gtb tfoot th,table.hs-gtb tfoot td{border-top:1px solid var(--line2);font-weight:700}table.hs-gtb tfoot th{color:var(--ink)}
table.hs-gtb td.tr{color:var(--green)}
.hs-gt{display:flex;flex-direction:column;gap:6px}
.hs-gt .lg{display:flex;gap:14px;flex-wrap:wrap;font-size:13px;color:var(--ink2)}
.hs-gt .lg a,.hs-gt .lg span{display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:inherit}.hs-gt .lg a:hover{color:var(--pri)}
.hs-gt .lg b{font-size:16px;color:var(--ink)}
.hs-gt .bar{display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--line2)}.hs-gt .bar span{display:block;height:100%}
/* thanh cong cu + bang du lieu */
.hs-tool{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 12px;border-bottom:1px solid var(--line)}
.hs-tool h2{margin:0 8px 0 2px;font-size:16px;color:var(--navy);white-space:nowrap}
.hs-sp{flex:1}
.hs-tw{overflow:auto}
table.hs-grid{width:100%;border-collapse:collapse;font-size:13.5px;margin:0}
table.hs-grid th{position:sticky;top:0;z-index:1;background:#f1f5fb;color:var(--ink);font-weight:600;text-align:left;padding:9px 10px;border:0;border-bottom:1px solid var(--line);border-right:1px solid var(--line2);white-space:nowrap}
table.hs-grid td{padding:8px 10px;border:0;border-bottom:1px solid var(--line2);border-right:1px solid var(--line2);background:transparent;vertical-align:middle}
table.hs-grid tbody tr:nth-child(even){background:#fbfcfe}table.hs-grid tbody tr:hover{background:#f2f7ff}
table.hs-grid .c{text-align:center}table.hs-grid .r{text-align:right;white-space:nowrap}
.hs-empty{padding:28px!important;text-align:center;color:var(--ink3)}
.hs-bar3{display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--line2);min-width:90px}.hs-bar3 span{display:block;height:100%}
/* form */
.hs-fg{display:grid;grid-template-columns:repeat(3,1fr);gap:10px 14px}
.hs-fg label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--ink2);font-weight:600;margin:0}
.hs-fg input,.hs-fg select,.hs-fg textarea{width:100%;height:34px;border:1px solid var(--line);border-radius:7px;padding:0 10px;background:#fff;font:inherit;font-weight:400;color:var(--ink)}
.hs-fg textarea{height:auto;padding:8px 10px}
.hs-fg input:focus,.hs-fg select:focus,.hs-fg textarea:focus{outline:0;border-color:var(--pri);box-shadow:0 0 0 3px rgba(31,111,214,.15)}
.hs-fg .s2{grid-column:span 2}.hs-fg .s3{grid-column:1/-1}
.hs-fg .hint{font-weight:400;color:var(--ink3)}
@media (max-width:1300px){.hs-kpis{grid-template-columns:1fr 1fr}}
@media (max-width:980px){.hs-shell{grid-template-columns:1fr}.hs-side{position:static;display:flex;overflow:auto;padding:6px}.hs-side a{flex:none;margin:0 4px 0 0}.hs-side .sep,.hs-side .cap{display:none}.hs-fg{grid-template-columns:1fr 1fr}}
@media (max-width:640px){.hs{padding:8px}.hs-kpis{grid-template-columns:1fr}.hs-fg{grid-template-columns:1fr}.hs-fg .s2{grid-column:auto}.hs-brand small{display:none}}
@media print{.hs-top,.hs-side,.hs-tool,.hs-kpis{display:none!important}.hs-shell{display:block}.hs{background:#fff}.hs-card{box-shadow:none}}
</style>
    <?php
}

function ktx_khung_icon(): void {
    static $daIn = false;
    if ($daIn) { return; }
    $daIn = true;
    ?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="hi-id" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2.4"/><path d="M5.5 17c.6-2 2-3 3.5-3s2.9 1 3.5 3M14.5 9.5h4M14.5 13h4"/></symbol>
  <symbol id="hi-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.3-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><circle cx="17" cy="9" r="2.6"/><path d="M16 14.2c2.9.1 4.8 1.9 5.4 5.3"/></symbol>
  <symbol id="hi-bed" viewBox="0 0 24 24"><path d="M3 18V7M3 13h18v5M21 18v-3a3 3 0 0 0-3-3h-7v1"/><circle cx="7" cy="10.5" r="1.8"/></symbol>
  <symbol id="hi-door" viewBox="0 0 24 24"><path d="M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17M3 21h18"/><circle cx="15" cy="12" r="1"/></symbol>
  <symbol id="hi-chart" viewBox="0 0 24 24"><path d="M4 20h16M7 16v-5M12 16V7M17 16v-8"/></symbol>
  <symbol id="hi-inbox" viewBox="0 0 24 24"><path d="M4 13 6.5 5h11L20 13v6H4z"/><path d="M4 13h4.5l1 2h5l1-2H20"/></symbol>
  <symbol id="hi-check" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="m8.5 12 2.3 2.3L15.5 9.6"/></symbol>
  <symbol id="hi-coin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M14.8 9.2c-.5-.9-1.6-1.4-2.8-1.4-1.6 0-2.8.8-2.8 2.1 0 2.8 5.8 1.4 5.8 4.2 0 1.3-1.3 2.1-3 2.1-1.3 0-2.5-.6-3-1.6M12 6v1.8M12 16.2V18"/></symbol>
  <symbol id="hi-bolt" viewBox="0 0 24 24"><path d="M13 3 5 13.5h6L10 21l8-10.5h-6z"/></symbol>
  <symbol id="hi-list" viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"/></symbol>
  <symbol id="hi-tool" viewBox="0 0 24 24"><path d="M14.5 6.5a4 4 0 0 0-5.3 5.3L4 17l3 3 5.2-5.2a4 4 0 0 0 5.3-5.3l-2.5 2.5-2.5-2.5z"/></symbol>
  <symbol id="hi-box" viewBox="0 0 24 24"><path d="M3.5 7.5 12 3l8.5 4.5v9L12 21l-8.5-4.5z"/><path d="M3.5 7.5 12 12l8.5-4.5M12 12v9"/></symbol>
  <symbol id="hi-pin" viewBox="0 0 24 24"><path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></symbol>
  <symbol id="hi-shield" viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6z"/><path d="M12 9v6M9 12h6"/></symbol>
  <symbol id="hi-star" viewBox="0 0 24 24"><path d="m12 3 2.7 5.6 6.1.8-4.5 4.2 1.1 6.1L12 16.8l-5.4 2.9 1.1-6.1-4.5-4.2 6.1-.8z"/></symbol>
  <symbol id="hi-swap" viewBox="0 0 24 24"><path d="M4 8h13l-3.5-3.5M20 16H7l3.5 3.5"/></symbol>
  <symbol id="hi-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/></symbol>
  <symbol id="hi-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></symbol>
  <symbol id="hi-bell" viewBox="0 0 24 24"><path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/></symbol>
  <symbol id="hi-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="hi-edit" viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/></symbol>
  <symbol id="hi-trash" viewBox="0 0 24 24"><path d="M4 7h16M9.5 7V4.5h5V7M6.5 7l1 13h9l1-13"/></symbol>
  <symbol id="hi-down" viewBox="0 0 24 24"><path d="M12 4v12M7 11l5 5 5-5M4 20h16"/></symbol>
  <symbol id="hi-print" viewBox="0 0 24 24"><path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="1.5"/><path d="M7 14h10v7H7z"/></symbol>
  <symbol id="hi-filter" viewBox="0 0 24 24"><path d="M4 5h16l-6 7.5V19l-4 1.5v-8z"/></symbol>
  <symbol id="hi-alert" viewBox="0 0 24 24"><path d="M12 3 2 20h20z"/><path d="M12 10v4.5M12 17.2h.01"/></symbol>
  <symbol id="hi-home" viewBox="0 0 24 24"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/></symbol>
  <symbol id="hi-file" viewBox="0 0 24 24"><path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M8.5 13h7M8.5 16.5h5"/></symbol>
  <symbol id="hi-folder" viewBox="0 0 24 24"><path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H9l2 2.5h8.5A1.5 1.5 0 0 1 21 9v9.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z"/></symbol>
  <symbol id="hi-receipt" viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></symbol>
  <symbol id="hi-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
  <symbol id="hi-save" viewBox="0 0 24 24"><path d="M5 4h11l3 3v13H5z"/><path d="M8 4v5h7V4M8 20v-6h8v6"/></symbol>
  <symbol id="hi-link" viewBox="0 0 24 24"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></symbol>
  <symbol id="hi-copy" viewBox="0 0 24 24"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/></symbol>
  <symbol id="hi-cal" viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/></symbol>
</svg>
    <?php
}
