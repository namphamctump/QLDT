<?php
// SO DO TRUC QUAN QUAN LY PHONG - GIUONG KTX — luoi the phong dang mau theo trang thai lap day,
// bo loc Khu/Tang/Loai phong, Drawer chi tiet (danh sach sinh vien + so do giuong + hanh dong
// nhanh), tooltip nhanh khi hover. XAY DUNG BANG PHP + VANILLA JS + CSS THUAN (khong React/Vue/
// Tailwind/AntD) DE PHU HOP VOI MOI TRUONG TRIEN KHAI THUC TE cua he thong nay (hosting PHP chia
// se qua cPanel, tai file .php/.js/.css truc tiep, KHONG co Node.js/build step de chay duoc 1 SPA
// React/Vue that su) — du lieu van "render dong tu API tra ve JSON" (xem ajax_so_do_phong.php) (2026-09-15).
//
// GIAO DIEN MOI (2026-10-10), cung phong cach trang Ho so noi tru (ktx_hoc_vien.php): dai tieu de,
// menu KTX ben trai, 4 the so lieu (phong theo trang thai, giuong nam/nu, nguoi o nam/nu, giuong
// trong co the xep), so do NHOM THEO DAY, moi phong 1 o gon co cham giuong, loc them theo trang thai
// va tim nhanh. API ajax_so_do_phong.php va cac tham so day_id/tang/loai_phong GIU NGUYEN.
require_once __DIR__ . '/../includes/functions.php';
require_full_admin();
$pdo = get_pdo();

$dayList = $pdo->query('SELECT * FROM ktx_day ORDER BY ten')->fetchAll();
$tangList = $pdo->query("SELECT DISTINCT tang FROM ktx_phong WHERE tang IS NOT NULL AND tang <> '' ORDER BY tang")->fetchAll(PDO::FETCH_COLUMN);
$loaiPhongList = $pdo->query('SELECT DISTINCT suc_chua FROM ktx_phong WHERE suc_chua > 0 ORDER BY suc_chua')->fetchAll(PDO::FETCH_COLUMN);
// Gioi tinh cua tung Day — de tach giuong/nguoi o Nam/Nu va ghi nhan tren tieu de nhom
$dayGioiTinh = [];
foreach ($dayList as $d) { $dayGioiTinh[(int)$d['id']] = ['ten' => $d['ten'], 'gt' => in_array($d['gioi_tinh'], ['Nam', 'Nữ'], true) ? $d['gioi_tinh'] : 'Khác']; }

$MENU = [
    ['ktx_hoc_vien.php', 'Hồ sơ nội trú', 'id', false],
    ['ktx_so_do_phong.php', 'Sơ đồ Phòng – Giường', 'bed', true],
    ['ktx_diem_danh.php', 'Điểm danh theo đợt', 'check', false],
    ['ktx_phi_noi_tru.php', 'Thu phí nội trú', 'coin', false],
    ['ktx_dien_nuoc.php', 'Điện, nước', 'bolt', false],
    ['ktx_luu_tru_tam_tru.php', 'Khai báo lưu trú / tạm trú', 'pin', false],
    ['ktx_ban_quan_ly.php', 'Ban Quản lý KTX', 'shield', false],
    ['ktx_doi_sv.php', 'Cán sự / Đội SV', 'star', false],
    ['ktx_doi_chieu_ds.php', 'Đối chiếu danh sách', 'swap', false],
    ['ktx_thong_bao_email.php', 'Gửi thông báo email', 'mail', false],
];

require_once __DIR__ . '/../includes/header.php';
?>
<style>
/* ===== So do Phong – Giuong (2026-10-10). Moi lop co tien to hs- (dung chung voi ktx_hoc_vien.php) hoac sd- ===== */
.hs,.sd-dr{--navy:#0d3a78;--navy2:#134b96;--pri:#1f6fd6;--pri-soft:#e7f0fc;--pri-line:#bcd3f3;--bg:#eef3fa;--line:#dfe7f2;--line2:#edf2f8;
  --ink:#1c2b41;--ink2:#4b5d75;--ink3:#8494aa;--green:#1f9d55;--green-soft:#e3f6eb;--orange:#f08c00;--orange-soft:#fff3e0;--red:#e03e3e;--red-soft:#fdeaea;--yellow:#f5b400;
  --nam:#1f6fd6;--nu:#d6457a;--shadow:0 1px 2px rgba(16,42,84,.06),0 4px 14px rgba(16,42,84,.06);
}
.hs{max-width:1900px;margin:0 auto;padding:12px 14px 24px;background:var(--bg);color:var(--ink);font-family:"Segoe UI",Roboto,Arial,sans-serif;font-size:14px;border-radius:14px}
.hs *{box-sizing:border-box}.hs a{color:inherit}
.hs svg.i{width:18px;height:18px;flex:none;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.hs-mut{color:var(--ink3)}
.hs-top{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 16px;border-radius:12px;color:#fff;background:linear-gradient(90deg,var(--navy),var(--navy2));box-shadow:0 2px 8px rgba(13,58,120,.25);margin-bottom:12px}
.hs-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff!important}
.hs-brand .lg{width:38px;height:38px;border-radius:50%;border:2px solid rgba(255,255,255,.85);display:grid;place-items:center}
.hs-brand b{display:block;font-size:19px;letter-spacing:.3px;line-height:1.1}.hs-brand small{display:block;font-size:10.5px;opacity:.8;text-transform:uppercase;letter-spacing:.4px}
.hs-gs{flex:1;min-width:220px;max-width:460px;position:relative;margin:0}
.hs-gs input{width:100%;height:34px;border:0;border-radius:6px;padding:0 38px 0 12px;background:#fff;color:var(--ink);font:inherit}
.hs-gs svg{position:absolute;right:10px;top:8px;color:var(--ink2)}
.hs-tr{margin-left:auto;display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.hs-tb{display:flex;align-items:center;gap:6px;height:34px;padding:0 10px;border-radius:6px;color:#fff!important;text-decoration:none;white-space:nowrap}
.hs-tb:hover{background:rgba(255,255,255,.12)}
.hs-shell{display:grid;grid-template-columns:224px 1fr;gap:12px;align-items:start}
.hs-side{background:#fff;border:1px solid var(--line);border-radius:12px;padding:8px;position:sticky;top:10px}
.hs-side a{display:flex;align-items:center;gap:11px;padding:9px 11px;margin-bottom:2px;border-radius:8px;text-decoration:none;font-size:14px;color:var(--ink)}
.hs-side a svg{width:19px;height:19px;color:var(--navy2)}.hs-side a:hover{background:var(--pri-soft)}
.hs-side a.on{background:var(--pri);color:#fff;box-shadow:0 2px 6px rgba(31,111,214,.35)}.hs-side a.on svg{color:#fff}
.hs-side .sep{height:1px;background:var(--line);margin:8px 4px}
.hs-side .cap{font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;color:var(--ink3);padding:6px 11px 4px}
.hs-main{min-width:0}
.hs-card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow)}
.hs-dot{width:8px;height:8px;border-radius:50%;flex:none;display:inline-block}
.hs-btn{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border:1px solid var(--line);border-radius:7px;background:#fff;color:var(--ink)!important;cursor:pointer;font:inherit;font-size:13px;white-space:nowrap;text-decoration:none!important}
.hs-btn svg{width:15px;height:15px}.hs-btn:hover{border-color:var(--pri-line);background:#f7faff}
.hs-btn.pri{background:var(--pri);border-color:var(--pri);color:#fff!important}.hs-btn.pri:hover{background:#185fbd}
.hs-f{height:32px;border:1px solid var(--line);border-radius:7px;padding:0 10px;background:#fff;font:inherit;color:var(--ink);min-width:0}
/* the so lieu */
.hs-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:12px}
.hs-kpi{padding:14px 16px;display:flex;flex-direction:column;gap:10px}
.hs-kpi .tp{display:flex;align-items:center;gap:14px}
.hs-kpi .ic{width:52px;height:52px;border-radius:50%;display:grid;place-items:center;flex:none}.hs-kpi .ic svg{width:26px;height:26px}
.hs-kpi .num{font-size:28px;font-weight:700;line-height:1.05;color:var(--navy)}.hs-kpi .lb{color:var(--ink2)}
.sd-stl{list-style:none;margin:0;padding:8px 0 0;border-top:1px solid var(--line2);display:grid;grid-template-columns:1fr 1fr;gap:6px 12px}
.sd-stl button{display:flex;align-items:center;gap:8px;width:100%;border:0;background:none;padding:2px 0;font:inherit;font-size:13px;color:var(--ink2);cursor:pointer;text-align:left}
.sd-stl button:hover,.sd-stl button.on{color:var(--pri)}.sd-stl button.on{font-weight:600}
.sd-stl b{min-width:26px;text-align:right;color:var(--ink)}
table.hs-gtb{width:100%;border-collapse:collapse;font-size:13px;margin:0;border-top:1px solid var(--line2)}
table.hs-gtb th,table.hs-gtb td{padding:5px 4px;border:0;background:none;text-align:right;font-weight:600;color:var(--ink)}
table.hs-gtb thead th{font-size:12px;font-weight:400;color:var(--ink2);padding-top:8px}
table.hs-gtb tbody th,table.hs-gtb tfoot th{text-align:left;font-weight:400;color:var(--ink2);white-space:nowrap}
table.hs-gtb tbody th .hs-dot{margin-right:6px}
table.hs-gtb tfoot th,table.hs-gtb tfoot td{border-top:1px solid var(--line2);font-weight:700}table.hs-gtb tfoot th{color:var(--ink)}
table.hs-gtb td.tr{color:var(--green)}
.hs-gt{display:flex;flex-direction:column;gap:8px;border-top:1px solid var(--line2);padding-top:10px}
.hs-gt .lg{display:flex;gap:14px;flex-wrap:wrap;font-size:13px;color:var(--ink2)}
.hs-gt .lg span{display:inline-flex;align-items:center;gap:6px}.hs-gt .lg b{font-size:16px;color:var(--ink)}
.hs-gt .bar{display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--line2)}.hs-gt .bar span{display:block;height:100%}
.sd-free{display:grid;gap:6px;border-top:1px solid var(--line2);padding-top:10px;font-size:13px;color:var(--ink2)}
.sd-free div{display:flex;align-items:center;gap:8px}.sd-free b{color:var(--green);font-size:16px}
/* thanh cong cu */
.sd-tool{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 12px;border-bottom:1px solid var(--line)}
.sd-tool h2{margin:0 8px 0 2px;font-size:16px;color:var(--navy);white-space:nowrap}
.sd-tool label{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--ink2);margin:0;font-weight:400}
.sd-leg{display:flex;gap:14px;flex-wrap:wrap;margin-left:auto;font-size:12.5px;color:var(--ink2)}
.sd-leg span{display:inline-flex;align-items:center;gap:6px}
.sd-sw{width:14px;height:14px;border-radius:4px;display:inline-block;border:1px solid transparent}
.sd-count{padding:8px 12px;font-size:12.5px;color:var(--ink2);border-bottom:1px solid var(--line2)}
/* so do */
.sd-body{padding:4px 12px 12px}
.sd-grp{margin-top:12px}
.sd-gh{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:8px 2px;border-bottom:2px solid var(--line);margin-bottom:10px}
.sd-gh h3{margin:0;font-size:15px;color:var(--navy)}
.sd-gh .tag{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600}
.sd-gh .tag.nam{background:#e7f0fc;color:var(--nam)}.sd-gh .tag.nu{background:#fce8f0;color:var(--nu)}.sd-gh .tag.khac{background:#eef1f5;color:var(--ink2)}
.sd-gh .st{font-size:12.5px;color:var(--ink2);margin-left:auto}.sd-gh .st b{color:var(--green)}
.sd-floor{font-size:12px;color:var(--ink3);text-transform:uppercase;letter-spacing:.4px;margin:10px 0 6px}
.sd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.sd-room{position:relative;background:#fff;border:1px solid var(--line);border-left:4px solid var(--pri);border-radius:10px;padding:9px 10px 10px;cursor:pointer;text-align:left;font:inherit;color:var(--ink);transition:transform .12s,box-shadow .12s}
.sd-room:hover,.sd-room:focus-visible{transform:translateY(-2px);box-shadow:0 6px 16px rgba(16,42,84,.14);outline:0}
.sd-room .h{display:flex;align-items:center;justify-content:space-between;gap:6px}
.sd-room .code{font-weight:700;font-size:15.5px}
.sd-room .pill{font-size:11px;font-weight:600;padding:1px 8px;border-radius:999px;white-space:nowrap}
.sd-room .meta{font-size:12px;color:var(--ink2);margin-top:3px}
.sd-beds{display:flex;flex-wrap:wrap;gap:4px;margin-top:8px}
.sd-beds i{width:14px;height:14px;border-radius:4px;display:block}
.sd-beds i.o{background:var(--pri)}.sd-beds i.e{background:#fff;border:1.5px dashed #9fb6d6}.sd-beds i.x{background:repeating-linear-gradient(45deg,#d5dbe3 0 3px,#eef1f5 3px 6px)}.sd-beds i.v{background:var(--red)}
/* mau theo trang thai */
.sd-room.du_nguoi{border-left-color:var(--navy);background:#f3f6fb}.sd-room.du_nguoi .pill{background:#dfe7f4;color:var(--navy)}
.sd-room.con_trong{border-left-color:var(--green)}.sd-room.con_trong .pill{background:var(--green-soft);color:var(--green)}
.sd-room.con_trong.rong{border-left-color:#7ccf9e}
.sd-room.vuot_tai{border-left-color:var(--red);background:#fff8f8}.sd-room.vuot_tai .pill{background:var(--red-soft);color:var(--red)}
.sd-room.sua_chua{border-left-color:#9aa5b4;background:repeating-linear-gradient(135deg,#fafbfc 0 8px,#f2f4f7 8px 16px);color:var(--ink2)}.sd-room.sua_chua .pill{background:#e6e9ee;color:var(--ink2)}
.sd-empty{padding:40px 0;text-align:center;color:var(--ink3)}
/* tooltip + drawer */
.sd-tip{position:fixed;z-index:500;background:#1a2233;color:#fff;padding:8px 12px;border-radius:8px;font-size:12px;max-width:260px;pointer-events:none;box-shadow:0 6px 18px rgba(0,0,0,.25);display:none;line-height:1.5}
.sd-tip b{display:block;margin-bottom:3px}
.sd-ov{position:fixed;inset:0;background:rgba(10,20,40,.4);z-index:600;display:none}
.sd-dr{position:fixed;top:0;right:-560px;width:540px;max-width:94vw;height:100vh;background:#fff;z-index:601;box-shadow:-8px 0 30px rgba(0,0,0,.2);transition:right .25s ease;overflow-y:auto;font-family:"Segoe UI",Roboto,Arial,sans-serif;color:#1c2b41;font-size:14px}
.sd-dr.open{right:0}
.sd-dr *{box-sizing:border-box}
.sd-dh{background:linear-gradient(90deg,#0d3a78,#134b96);color:#fff;padding:14px 18px;display:flex;align-items:center;gap:10px}
.sd-dh h3{margin:0;font-size:17px;color:#fff}.sd-dh small{display:block;opacity:.85;font-size:12.5px;margin-top:2px}
.sd-dh button{margin-left:auto;background:rgba(255,255,255,.15);border:0;color:#fff;width:32px;height:32px;border-radius:50%;font-size:18px;cursor:pointer}
.sd-db{padding:14px 18px}
.sd-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px}
.sd-stats div{border:1px solid #dfe7f2;border-radius:10px;padding:8px 10px}
.sd-stats b{display:block;font-size:20px;color:#0d3a78}.sd-stats span{font-size:12px;color:#4b5d75}
.sd-acts{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.sd-sec{border:1px solid #dfe7f2;border-radius:10px;overflow:hidden;margin-bottom:12px}
.sd-sec h4{margin:0;padding:9px 12px;border-bottom:1px solid #edf2f8;font-size:13.5px;color:#0d3a78;text-transform:uppercase;letter-spacing:.2px}
.sd-bedg{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;padding:10px 12px}
.sd-bed{border:1px solid #dfe7f2;border-radius:8px;padding:7px 6px;text-align:center;font-size:12px}
.sd-bed b{display:block;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600}
.sd-bed.full{background:#e7f0fc;border-color:#bcd3f3;color:#0d3a78}.sd-bed.empty{background:#fafcfa;border-style:dashed;color:#1f9d55}.sd-bed.off{background:#f3f4f6;color:#8494aa;text-decoration:line-through}
table.sd-st{width:100%;border-collapse:collapse;font-size:12.5px;margin:0}
table.sd-st th{background:#f1f5fb;text-align:left;padding:7px 8px;border:0;border-bottom:1px solid #dfe7f2;font-weight:600;white-space:nowrap;color:#1c2b41}
table.sd-st td{padding:7px 8px;border:0;border-bottom:1px solid #edf2f8;vertical-align:middle}
table.sd-st a{color:#1f6fd6}
.sd-av{width:30px;height:38px;border-radius:4px;object-fit:cover;display:block;background:#e8eef7}
.sd-avp{width:30px;height:38px;border-radius:4px;background:linear-gradient(180deg,#e8eef7,#d6e1f0);display:grid;place-items:center;color:#7d93b4;font-weight:700;font-size:13px}
.sd-ok{color:#1f9d55;font-weight:600}.sd-no{color:#e03e3e;font-weight:600}
@media (max-width:1300px){.hs-kpis{grid-template-columns:1fr 1fr}}
@media (max-width:980px){.hs-shell{grid-template-columns:1fr}.hs-side{position:static;display:flex;overflow:auto;padding:6px}.hs-side a{flex:none;margin:0 4px 0 0}.hs-side .sep,.hs-side .cap{display:none}.sd-leg{margin-left:0}}
@media (max-width:640px){.hs{padding:8px}.hs-kpis{grid-template-columns:1fr}.hs-brand small{display:none}.sd-grid{grid-template-columns:repeat(2,1fr)}.sd-bedg{grid-template-columns:repeat(3,minmax(0,1fr))}.sd-stats{grid-template-columns:1fr 1fr 1fr}}
@media print{.hs-top,.hs-side,.sd-tool,.hs-kpis{display:none!important}.hs-shell{display:block}.hs{background:#fff}.sd-room{break-inside:avoid}}
</style>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="hi-id" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2.4"/><path d="M5.5 17c.6-2 2-3 3.5-3s2.9 1 3.5 3M14.5 9.5h4M14.5 13h4"/></symbol>
  <symbol id="hi-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.3-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><circle cx="17" cy="9" r="2.6"/><path d="M16 14.2c2.9.1 4.8 1.9 5.4 5.3"/></symbol>
  <symbol id="hi-bed" viewBox="0 0 24 24"><path d="M3 18V7M3 13h18v5M21 18v-3a3 3 0 0 0-3-3h-7v1"/><circle cx="7" cy="10.5" r="1.8"/></symbol>
  <symbol id="hi-door" viewBox="0 0 24 24"><path d="M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17M3 21h18"/><circle cx="15" cy="12" r="1"/></symbol>
  <symbol id="hi-check" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="m8.5 12 2.3 2.3L15.5 9.6"/></symbol>
  <symbol id="hi-coin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M14.8 9.2c-.5-.9-1.6-1.4-2.8-1.4-1.6 0-2.8.8-2.8 2.1 0 2.8 5.8 1.4 5.8 4.2 0 1.3-1.3 2.1-3 2.1-1.3 0-2.5-.6-3-1.6M12 6v1.8M12 16.2V18"/></symbol>
  <symbol id="hi-bolt" viewBox="0 0 24 24"><path d="M13 3 5 13.5h6L10 21l8-10.5h-6z"/></symbol>
  <symbol id="hi-pin" viewBox="0 0 24 24"><path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></symbol>
  <symbol id="hi-shield" viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6z"/><path d="M12 9v6M9 12h6"/></symbol>
  <symbol id="hi-star" viewBox="0 0 24 24"><path d="m12 3 2.7 5.6 6.1.8-4.5 4.2 1.1 6.1L12 16.8l-5.4 2.9 1.1-6.1-4.5-4.2 6.1-.8z"/></symbol>
  <symbol id="hi-swap" viewBox="0 0 24 24"><path d="M4 8h13l-3.5-3.5M20 16H7l3.5 3.5"/></symbol>
  <symbol id="hi-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/></symbol>
  <symbol id="hi-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></symbol>
  <symbol id="hi-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="hi-list" viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"/></symbol>
  <symbol id="hi-tool" viewBox="0 0 24 24"><path d="M14.5 6.5a4 4 0 0 0-5.3 5.3L4 17l3 3 5.2-5.2a4 4 0 0 0 5.3-5.3l-2.5 2.5-2.5-2.5z"/></symbol>
  <symbol id="hi-home" viewBox="0 0 24 24"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/></symbol>
  <symbol id="hi-print" viewBox="0 0 24 24"><path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="1.5"/><path d="M7 14h10v7H7z"/></symbol>
</svg>

<div class="hs">
  <div class="hs-top">
    <a class="hs-brand" href="ktx_so_do_phong.php"><span class="lg"><svg class="i"><use href="#hi-bed"/></svg></span><span><b>SƠ ĐỒ PHÒNG – GIƯỜNG</b><small>Ký túc xá · trực quan theo dãy</small></span></a>
    <label class="hs-gs"><input id="sdQ" placeholder="Tìm phòng hoặc người đang ở (mã phòng, họ tên, MSSV…)" autocomplete="off"><svg class="i"><use href="#hi-search"/></svg></label>
    <div class="hs-tr">
      <a class="hs-tb" href="ktx_day_phong.php"><svg class="i"><use href="#hi-list"/></svg>Quản lý dạng bảng</a>
      <a class="hs-tb" href="ktx_thiet_bi.php"><svg class="i"><use href="#hi-tool"/></svg>Thiết bị – CSVC</a>
      <a class="hs-tb" href="ktx_dashboard.php"><svg class="i"><use href="#hi-home"/></svg>Tổng quan KTX</a>
    </div>
  </div>

  <div class="hs-shell">
    <nav class="hs-side">
      <div class="cap">Ký túc xá</div>
      <?php foreach ($MENU as [$f, $t, $ic, $on]): ?><a class="<?= $on ? 'on' : '' ?>" href="<?= h($f) ?>"><svg class="i"><use href="#hi-<?= $ic ?>"/></svg><?= h($t) ?></a><?php endforeach; ?>
      <div class="sep"></div>
      <a href="ktx_hoc_vien.php?edit=new"><svg class="i"><use href="#hi-plus"/></svg>Thêm học viên</a>
    </nav>

    <div class="hs-main">
      <div class="hs-kpis">
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-door"/></svg></div>
            <div><div class="num" id="kPhong">–</div><div class="lb">Phòng <span class="sd-scope"></span></div></div></div>
          <ul class="sd-stl" id="kTrangThai"></ul>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--green-soft);color:var(--green)"><svg class="i"><use href="#hi-bed"/></svg></div>
            <div><div class="num" id="kCongSuat">–</div><div class="lb">Công suất giường <span class="sd-scope"></span></div></div></div>
          <table class="hs-gtb"><thead><tr><th></th><th>Đã ở</th><th>Trống</th><th>Tổng</th></tr></thead><tbody id="kGiuong"></tbody><tfoot id="kGiuongTong"></tfoot></table>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--pri-soft);color:var(--pri)"><svg class="i"><use href="#hi-users"/></svg></div>
            <div><div class="num" id="kNguoi">–</div><div class="lb">Người đang ở <span class="sd-scope"></span></div></div></div>
          <div class="hs-gt"><div class="lg" id="kNguoiGt"></div><div class="bar" id="kNguoiBar"></div></div>
        </div>
        <div class="hs-card hs-kpi">
          <div class="tp"><div class="ic" style="background:var(--orange-soft);color:var(--orange)"><svg class="i"><use href="#hi-plus"/></svg></div>
            <div><div class="num" id="kTrong" style="color:var(--green)">–</div><div class="lb">Giường trống có thể xếp</div></div></div>
          <div class="sd-free" id="kTrongGt"></div>
        </div>
      </div>

      <div class="hs-card">
        <div class="sd-tool">
          <h2>SƠ ĐỒ PHÒNG</h2>
          <label>Khu <select class="hs-f" id="sdpDay"><option value="">Tất cả khu KTX</option>
            <?php foreach ($dayList as $d): ?><option value="<?= (int)$d['id'] ?>"><?= h($d['ten']) ?><?= !empty($d['gioi_tinh']) ? ' (' . h($d['gioi_tinh']) . ')' : '' ?></option><?php endforeach; ?></select></label>
          <label>Tầng <select class="hs-f" id="sdpTang"><option value="">Tất cả</option>
            <?php foreach ($tangList as $t): ?><option value="<?= h($t) ?>">Tầng <?= h($t) ?></option><?php endforeach; ?></select></label>
          <label>Loại phòng <select class="hs-f" id="sdpLoai"><option value="">Tất cả</option>
            <?php foreach ($loaiPhongList as $lp): ?><option value="<?= (int)$lp ?>">Phòng <?= (int)$lp ?> người</option><?php endforeach; ?></select></label>
          <label>Trạng thái <select class="hs-f" id="sdSt"><option value="">Tất cả</option><option value="con_trong">Còn giường trống</option><option value="du_nguoi">Đủ người</option><option value="vuot_tai">Vượt tải</option><option value="sua_chua">Đang sửa chữa</option></select></label>
          <div class="sd-leg">
            <span><i class="sd-sw" style="background:#f3f6fb;border-color:var(--navy);border-left-width:4px"></i>Đủ người</span>
            <span><i class="sd-sw" style="background:#fff;border-color:var(--green);border-left-width:4px"></i>Còn trống</span>
            <span><i class="sd-sw" style="background:#fff8f8;border-color:var(--red);border-left-width:4px"></i>Vượt tải</span>
            <span><i class="sd-sw" style="background:#f2f4f7;border-color:#9aa5b4;border-left-width:4px"></i>Sửa chữa</span>
            <span><i class="sd-sw" style="background:var(--pri)"></i>Giường có người</span>
            <span><i class="sd-sw" style="border:1.5px dashed #9fb6d6"></i>Giường trống</span>
          </div>
        </div>
        <div class="sd-count" id="sdCount">Đang tải sơ đồ phòng…</div>
        <div class="sd-body" id="sdpGrid"><div class="sd-empty">Đang tải sơ đồ phòng…</div></div>
      </div>
    </div>
  </div>
</div>

<div class="sd-tip" id="sdpTooltip"></div>
<div class="sd-ov" id="sdpOverlay" onclick="sdpDongDrawer()"></div>
<div class="sd-dr" id="sdpDrawer" role="dialog" aria-modal="true" aria-labelledby="sdpDrawerTitle">
  <div class="sd-dh"><div><h3 id="sdpDrawerTitle">Phòng</h3><small id="sdpDrawerSub"></small></div><button type="button" onclick="sdpDongDrawer()" aria-label="Đóng">✕</button></div>
  <div class="sd-db" id="sdpDrawerBody"></div>
</div>

<script>
var SDP_DATA = [];
var SDP_DAY = <?= json_encode($dayGioiTinh, JSON_UNESCAPED_UNICODE) ?>;
var STATUS_LABEL = { du_nguoi: 'Đủ người', con_trong: 'Còn trống', vuot_tai: 'Vượt tải', sua_chua: 'Sửa chữa' };
var GT_MAU = { 'Nam': '#1f6fd6', 'Nữ': '#d6457a', 'Khác': '#98a4b5' };
// Escape truoc khi noi chuoi vao innerHTML — phong ho ten/ghi chu chua ky tu dac biet lam vo HTML/XSS.
function sdpEsc(s) {
  if (s === null || s === undefined) return '';
  return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
}
function sdNorm(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase(); }
function sdGt(p) { var d = SDP_DAY[p.day_id]; return d ? d.gt : 'Khác'; }
function sdTrong(p) { return p.status === 'sua_chua' ? 0 : Math.max(0, (p.capacity || 0) - (p.current_occupants || 0)); }
function sdCoLoc() { return !!(document.getElementById('sdpDay').value || document.getElementById('sdpTang').value || document.getElementById('sdpLoai').value); }

function sdpTaiDuLieu() {
  var qs = new URLSearchParams();
  if (document.getElementById('sdpDay').value) qs.set('day_id', document.getElementById('sdpDay').value);
  if (document.getElementById('sdpTang').value) qs.set('tang', document.getElementById('sdpTang').value);
  if (document.getElementById('sdpLoai').value) qs.set('loai_phong', document.getElementById('sdpLoai').value);
  document.getElementById('sdpGrid').innerHTML = '<div class="sd-empty">Đang tải sơ đồ phòng…</div>';
  fetch('ajax_so_do_phong.php?' + qs.toString())
    .then(function (r) { return r.json(); })
    .then(function (data) { SDP_DATA = Array.isArray(data) ? data : []; sdVeTheSoLieu(); sdpVeGrid(); })
    .catch(function () { document.getElementById('sdpGrid').innerHTML = '<div class="sd-empty">Không tải được dữ liệu.</div>'; document.getElementById('sdCount').textContent = ''; });
}

// ---------- 4 the so lieu (tinh tren cac phong dang tai — theo Khu/Tang/Loai dang chon) ----------
function sdVeTheSoLieu() {
  document.querySelectorAll('.sd-scope').forEach(function (e) { e.textContent = sdCoLoc() ? '(theo bộ lọc)' : ''; });
  var dem = { con_trong: 0, du_nguoi: 0, vuot_tai: 0, sua_chua: 0 }, gt = {}, rong = 0;
  SDP_DATA.forEach(function (p) {
    dem[p.status] = (dem[p.status] || 0) + 1;
    if (p.status !== 'sua_chua' && !p.current_occupants) rong++;
    var g = sdGt(p); gt[g] = gt[g] || { o: 0, trong: 0, tong: 0, phongTrong: 0 };
    gt[g].o += p.current_occupants || 0;
    if (p.status !== 'sua_chua') { gt[g].tong += p.capacity || 0; gt[g].trong += sdTrong(p); if (sdTrong(p)) gt[g].phongTrong++; }
  });
  var dangDung = SDP_DATA.length - dem.sua_chua;
  document.getElementById('kPhong').textContent = SDP_DATA.length;
  var stSel = document.getElementById('sdSt').value;
  document.getElementById('kTrangThai').innerHTML = [['con_trong', 'var(--green)', 'Còn trống'], ['du_nguoi', 'var(--navy)', 'Đủ người'], ['vuot_tai', 'var(--red)', 'Vượt tải'], ['sua_chua', '#9aa5b4', 'Sửa chữa']].map(function (x) {
    return '<li><button type="button" data-st="' + x[0] + '" class="' + (stSel === x[0] ? 'on' : '') + '"><span class="hs-dot" style="background:' + x[1] + '"></span><b>' + dem[x[0]] + '</b>' + x[2] + '</button></li>';
  }).join('') + '<li style="grid-column:1/-1;font-size:12.5px;color:var(--ink2)">' + dangDung + ' phòng đang dùng · ' + rong + ' phòng chưa có ai ở</li>';

  var thuTu = ['Nam', 'Nữ', 'Khác'].filter(function (g) { return gt[g]; });
  var tO = 0, tTrong = 0, tTong = 0;
  document.getElementById('kGiuong').innerHTML = thuTu.map(function (g) {
    var x = gt[g]; tO += x.o; tTrong += x.trong; tTong += x.tong;
    return '<tr><th><span class="hs-dot" style="background:' + GT_MAU[g] + '"></span>Giường ' + g.toLowerCase() + '</th><td>' + x.o + '</td><td class="' + (x.trong ? 'tr' : '') + '">' + x.trong + '</td><td>' + x.tong + '</td></tr>';
  }).join('');
  document.getElementById('kGiuongTong').innerHTML = '<tr><th>Cộng (' + dangDung + ' phòng)</th><td>' + tO + '</td><td class="' + (tTrong ? 'tr' : '') + '">' + tTrong + '</td><td>' + tTong + '</td></tr>';
  document.getElementById('kCongSuat').textContent = (tTong ? Math.round(tO / tTong * 100) : 0) + '%';

  document.getElementById('kNguoi').textContent = tO;
  document.getElementById('kNguoiGt').innerHTML = thuTu.filter(function (g) { return g !== 'Khác' || gt[g].o; }).map(function (g) {
    return '<span><i class="hs-dot" style="background:' + GT_MAU[g] + '"></i>' + g + ' <b>' + gt[g].o + '</b><span class="hs-mut">(' + (tO ? Math.round(gt[g].o / tO * 100) : 0) + '%)</span></span>';
  }).join('') + '<span class="hs-mut" style="font-size:12px">theo giới tính của dãy</span>';
  document.getElementById('kNguoiBar').innerHTML = thuTu.map(function (g) { return tO && gt[g].o ? '<span style="width:' + (gt[g].o / tO * 100).toFixed(2) + '%;background:' + GT_MAU[g] + '"></span>' : ''; }).join('');

  document.getElementById('kTrong').textContent = tTrong;
  document.getElementById('kTrongGt').innerHTML = thuTu.map(function (g) {
    return '<div><span class="hs-dot" style="background:' + GT_MAU[g] + '"></span>' + g + ': <b>' + gt[g].trong + '</b> giường trong ' + gt[g].phongTrong + ' phòng</div>';
  }).join('') + '<div><button type="button" class="hs-btn" id="kChiTrong" style="height:28px"><svg class="i"><use href="#hi-bed"/></svg>Chỉ xem phòng còn trống</button></div>';
}
document.querySelector('.hs-kpis').addEventListener('click', function (e) {
  var b = e.target.closest('[data-st]'); var sel = document.getElementById('sdSt');
  if (b) { sel.value = sel.value === b.dataset.st ? '' : b.dataset.st; sdVeTheSoLieu(); sdpVeGrid(); }
  if (e.target.closest('#kChiTrong')) { sel.value = 'con_trong'; sdVeTheSoLieu(); sdpVeGrid(); document.getElementById('sdpGrid').scrollIntoView({ behavior: 'smooth' }); }
});

// ---------- So do: nhom theo Day -> Tang ----------
function sdLocPhong() {
  var st = document.getElementById('sdSt').value, q = sdNorm(document.getElementById('sdQ').value.trim());
  return SDP_DATA.map(function (p, i) { return { p: p, i: i }; }).filter(function (x) {
    var p = x.p;
    if (st && p.status !== st) return false;
    if (q) {
      var hay = sdNorm([p.room_code, p.ten_day].concat((p.student_list || []).map(function (s) { return s.ho_ten + ' ' + s.ma_so; })).join(' '));
      if (hay.indexOf(q) < 0) return false;
    }
    return true;
  });
}
function sdOPhong(x) {
  var p = x.p, cap = p.capacity || 0, cur = p.current_occupants || 0, dots = '';
  if (p.beds && p.beds.length) {
    p.beds.forEach(function (b) { dots += '<i class="' + (!b.su_dung ? 'x' : (b.occupant_name ? 'o' : 'e')) + '" title="' + sdpEsc(b.ma_giuong + ': ' + (!b.su_dung ? 'Ngừng dùng' : (b.occupant_name || 'Trống'))) + '"></i>'; });
    for (var k = p.beds.filter(function (b) { return b.su_dung; }).length; k < cur; k++) dots += '<i class="v" title="Vượt sức chứa"></i>';
  } else {
    for (var j = 0; j < Math.max(cap, cur); j++) dots += '<i class="' + (p.status === 'sua_chua' ? 'x' : (j < cur ? (j < cap ? 'o' : 'v') : 'e')) + '"></i>';
  }
  var trong = sdTrong(p);
  var pill = p.status === 'con_trong' ? ('Trống ' + trong) : STATUS_LABEL[p.status];
  return '<button type="button" class="sd-room ' + p.status + (p.status === 'con_trong' && !cur ? ' rong' : '') + '" data-i="' + x.i + '" aria-label="Phòng ' + sdpEsc(p.room_code) + ', ' + cur + ' trên ' + cap + ' người, ' + sdpEsc(STATUS_LABEL[p.status]) + '">' +
    '<div class="h"><span class="code">' + sdpEsc(p.room_code) + '</span><span class="pill">' + sdpEsc(pill) + '</span></div>' +
    '<div class="meta">Đang ở ' + cur + '/' + cap + ' người</div>' +
    '<div class="sd-beds">' + dots + '</div></button>';
}
function sdpVeGrid() {
  var ds = sdLocPhong(), grid = document.getElementById('sdpGrid');
  document.getElementById('sdCount').textContent = 'Hiển thị ' + ds.length + ' / ' + SDP_DATA.length + ' phòng' + (document.getElementById('sdQ').value.trim() ? ' — khớp từ khoá tìm' : '');
  if (!ds.length) { grid.innerHTML = '<div class="sd-empty">Không có phòng nào khớp bộ lọc.</div>'; return; }
  var nhom = {}, thuTuDay = [];
  ds.forEach(function (x) {
    var k = x.p.day_id; if (!nhom[k]) { nhom[k] = []; thuTuDay.push(k); } nhom[k].push(x);
  });
  thuTuDay.sort(function (a, b) {
    var ga = ['Nam', 'Nữ', 'Khác'].indexOf((SDP_DAY[a] || {}).gt), gb = ['Nam', 'Nữ', 'Khác'].indexOf((SDP_DAY[b] || {}).gt);
    return ga - gb || String((SDP_DAY[a] || {}).ten || '').localeCompare(String((SDP_DAY[b] || {}).ten || ''), 'vi');
  });
  grid.innerHTML = thuTuDay.map(function (k) {
    var ph = nhom[k], d = SDP_DAY[k] || { ten: ph[0].p.ten_day, gt: 'Khác' };
    var o = 0, tong = 0, trong = 0;
    ph.forEach(function (x) { o += x.p.current_occupants || 0; if (x.p.status !== 'sua_chua') { tong += x.p.capacity || 0; trong += sdTrong(x.p); } });
    var cls = d.gt === 'Nam' ? 'nam' : (d.gt === 'Nữ' ? 'nu' : 'khac');
    var theoTang = {}, tangs = [];
    ph.forEach(function (x) { var t = x.p.tang || ''; if (!theoTang[t]) { theoTang[t] = []; tangs.push(t); } theoTang[t].push(x); });
    tangs.sort(function (a, b) { return String(a).localeCompare(String(b), 'vi', { numeric: true }); });
    var body = tangs.map(function (t) {
      return (tangs.length > 1 || t ? '<div class="sd-floor">' + (t ? 'Tầng ' + sdpEsc(t) : 'Chưa ghi tầng') + '</div>' : '') +
        '<div class="sd-grid">' + theoTang[t].map(sdOPhong).join('') + '</div>';
    }).join('');
    return '<section class="sd-grp"><div class="sd-gh"><h3>' + sdpEsc(d.ten) + '</h3><span class="tag ' + cls + '"><i class="hs-dot" style="background:' + GT_MAU[d.gt] + '"></i>' + sdpEsc(d.gt === 'Khác' ? 'Dãy chung' : 'Dãy ' + d.gt.toLowerCase()) + '</span>' +
      '<span class="st">' + ph.length + ' phòng · ' + o + '/' + tong + ' người · <b>' + trong + ' giường trống</b></span></div>' + body + '</section>';
  }).join('');
}
document.getElementById('sdpGrid').addEventListener('click', function (e) { var r = e.target.closest('.sd-room'); if (r) { sdpAnTooltip(); sdpMoDrawer(+r.dataset.i); } });
document.getElementById('sdpGrid').addEventListener('mouseover', function (e) { var r = e.target.closest('.sd-room'); if (r && !r.contains(e.relatedTarget)) sdpHienTooltip(e, SDP_DATA[+r.dataset.i]); });
document.getElementById('sdpGrid').addEventListener('mousemove', function (e) { if (e.target.closest('.sd-room')) sdpDiChuyenTooltip(e); });
document.getElementById('sdpGrid').addEventListener('mouseout', function (e) { var r = e.target.closest('.sd-room'); if (r && !r.contains(e.relatedTarget)) sdpAnTooltip(); });

function sdpHienTooltip(e, p) {
  var tip = document.getElementById('sdpTooltip');
  var ten = (p.student_list || []).map(function (s) { return sdpEsc(s.ho_ten); });
  tip.innerHTML = '<b>Phòng ' + sdpEsc(p.room_code) + ' (' + p.current_occupants + '/' + p.capacity + ') — ' + sdpEsc(STATUS_LABEL[p.status]) + '</b>' + (ten.length ? ten.join('<br>') : 'Chưa có sinh viên nào');
  tip.style.display = 'block'; sdpDiChuyenTooltip(e);
}
function sdpDiChuyenTooltip(e) {
  var tip = document.getElementById('sdpTooltip'), x = e.clientX + 16, y = e.clientY + 16;
  if (x + 260 > window.innerWidth) { x = e.clientX - 270; }
  if (y + tip.offsetHeight > window.innerHeight) { y = e.clientY - tip.offsetHeight - 10; }
  tip.style.left = x + 'px'; tip.style.top = y + 'px';
}
function sdpAnTooltip() { document.getElementById('sdpTooltip').style.display = 'none'; }

// ---------- Drawer chi tiet phong (giu dung noi dung + hanh dong cu) ----------
function sdpMoDrawer(idx) {
  var p = SDP_DATA[idx], g = sdGt(p), list = p.student_list || [], beds = p.beds || [];
  document.getElementById('sdpDrawerTitle').textContent = 'Phòng ' + p.room_code;
  document.getElementById('sdpDrawerSub').textContent = p.ten_day + (p.tang ? ' · Tầng ' + p.tang : '') + ' · ' + (g === 'Khác' ? 'Dãy chung' : 'Dãy ' + g.toLowerCase()) + ' · ' + (p.tinh_trang_nhan || STATUS_LABEL[p.status]);
  var stats = '<div class="sd-stats"><div><b>' + p.capacity + '</b><span>Sức chứa</span></div><div><b>' + p.current_occupants + '</b><span>Đang ở</span></div><div><b style="color:' + (sdTrong(p) ? 'var(--green,#1f9d55)' : '#0d3a78') + '">' + sdTrong(p) + '</b><span>Giường trống</span></div></div>';
  var acts = '<div class="sd-acts">' +
    '<a class="hs-btn pri" href="ktx_hoc_vien.php?edit=new&phong_id=' + encodeURIComponent(p.phong_id) + '" target="_blank"><svg class="i"><use href="#hi-plus"/></svg>Xếp thêm sinh viên</a>' +
    '<a class="hs-btn" href="ktx_hoc_vien.php?day_id=' + encodeURIComponent(p.day_id) + '" target="_blank"><svg class="i"><use href="#hi-swap"/></svg>Chuyển phòng</a>' +
    '<a class="hs-btn" href="ktx_phong_in.php?id=' + encodeURIComponent(p.phong_id) + '" target="_blank"><svg class="i"><use href="#hi-print"/></svg>In danh sách phòng</a></div>';
  var bedsHtml = '<div class="sd-sec"><h4>Sơ đồ giường</h4>' + (beds.length ? '<div class="sd-bedg">' + beds.map(function (b) {
      var cls = !b.su_dung ? 'off' : (b.occupant_name ? 'full' : 'empty');
      return '<div class="sd-bed ' + cls + '">' + sdpEsc(b.ma_giuong) + '<b>' + (!b.su_dung ? 'Ngừng dùng' : (b.occupant_name ? sdpEsc(b.occupant_name) : 'Trống')) + '</b></div>';
    }).join('') + '</div>' : '<p style="padding:10px 12px;margin:0;color:#8494aa;font-size:12.5px">Chưa có dữ liệu giường.</p>') + '</div>';
  var rows = list.map(function (s, i) {
    var tach = String(s.ho_ten || '').trim().split(/\s+/), vt = (tach[tach.length - 1] || '').charAt(0).toUpperCase();
    var avatar = s.anh_dai_dien ? '<img src="' + sdpEsc(s.anh_dai_dien) + '" class="sd-av" alt="">' : '<div class="sd-avp">' + sdpEsc(vt) + '</div>';
    var phi = s.da_dong_thang_nay === null || s.da_dong_thang_nay === undefined ? '<span style="color:#8494aa">Chưa có dữ liệu</span>' : (s.da_dong_thang_nay ? '<span class="sd-ok">Đã đóng</span>' : '<span class="sd-no">Nợ phí</span>');
    return '<tr><td>' + (i + 1) + '</td><td>' + avatar + '</td><td><a href="ktx_hoc_vien.php?id=' + encodeURIComponent(s.id) + '#ho-so" target="_blank">' + sdpEsc(s.ho_ten) + '</a><br><span style="color:#8494aa">' + sdpEsc(s.ma_so || '') + '</span></td>' +
      '<td>' + sdpEsc(s.lop || '—') + '</td><td>' + sdpEsc(s.ngay_nhan_phong || '—') + '</td><td>' + phi + '</td>' +
      '<td><a href="ktx_hoc_vien.php?edit=' + encodeURIComponent(s.id) + '" target="_blank" title="Sửa hồ sơ">Sửa</a></td></tr>';
  }).join('');
  var stHtml = '<div class="sd-sec"><h4>Sinh viên đang ở (' + list.length + ')</h4><div style="overflow-x:auto"><table class="sd-st"><thead><tr><th>#</th><th>Ảnh</th><th>Họ tên / MSSV</th><th>Lớp/Ngành</th><th>Nhận phòng</th><th>Phí tháng này</th><th></th></tr></thead><tbody>' +
    (rows || '<tr><td colspan="7" style="text-align:center;color:#8494aa">Phòng chưa có sinh viên nào.</td></tr>') + '</tbody></table></div></div>';
  document.getElementById('sdpDrawerBody').innerHTML = stats + acts + bedsHtml + stHtml;
  document.getElementById('sdpDrawer').classList.add('open');
  document.getElementById('sdpOverlay').style.display = 'block';
}
function sdpDongDrawer() {
  document.getElementById('sdpDrawer').classList.remove('open');
  document.getElementById('sdpOverlay').style.display = 'none';
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') sdpDongDrawer(); });

document.getElementById('sdpDay').addEventListener('change', sdpTaiDuLieu);
document.getElementById('sdpTang').addEventListener('change', sdpTaiDuLieu);
document.getElementById('sdpLoai').addEventListener('change', sdpTaiDuLieu);
document.getElementById('sdSt').addEventListener('change', function () { sdVeTheSoLieu(); sdpVeGrid(); });
document.getElementById('sdQ').addEventListener('input', sdpVeGrid);
sdpTaiDuLieu();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
