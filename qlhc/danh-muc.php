<?php
require __DIR__ . '/includes/bootstrap.php';

require_admin();
$tab = in_str('tab', 'cai_dat');
$tabs = ['cai_dat' => 'Thông tin cơ quan', 'don_vi' => 'Đơn vị', 'loai' => 'Loại văn bản', 'nguoi_ky' => 'Người ký', 'bo_dem' => 'Bộ đếm số'];
if (!isset($tabs[$tab])) {
    $tab = 'cai_dat';
}

const KHOA_CAI_DAT = [
    'co_quan_chu_quan' => ['Tên cơ quan chủ quản (HC)', 'In hoa, vd: TRƯỜNG ĐẠI HỌC Y DƯỢC CẦN THƠ'],
    'ten_co_quan'      => ['Tên cơ quan ban hành (HC)', 'In hoa'],
    'ten_co_quan_thuong' => ['Tên cơ quan viết thường', 'Dùng trong câu văn của mẫu nội dung, viết hoa đúng quy tắc: vd Trung tâm Dịch vụ và Đào tạo theo nhu cầu xã hội'],
    'viet_tat_co_quan' => ['Viết tắt cơ quan (HC)', 'Dùng trong ký hiệu: 05/QĐ-[viết tắt]'],
    'dia_danh'         => ['Địa danh', 'vd: Cần Thơ'],
    'danh_so_hc'       => ['Cách đánh số văn bản đi (HC)', ''],
    'co_chu_noi_dung'  => ['Cỡ chữ nội dung (HC)', '13 hoặc 14'],
    'dang_cap_tren'    => ['Cơ quan cấp trên trực tiếp (Đảng)', 'vd: ĐẢNG BỘ TRƯỜNG ĐẠI HỌC Y DƯỢC CẦN THƠ'],
    'dang_co_quan'     => ['Tên cơ quan ban hành (Đảng)', 'vd: CHI BỘ ...'],
    'dang_viet_tat'    => ['Viết tắt cơ quan (Đảng)', 'Dùng trong ký hiệu: 05-BC/[viết tắt]'],
    'dang_nhiem_ky'    => ['Nhiệm kỳ cấp ủy hiện tại', 'vd: 2025-2030 — đổi nhiệm kỳ thì số Đảng đánh lại từ 01'],
    'canh_bao_han_ngay'=> ['Cảnh báo sắp đến hạn (ngày)', 'Số ngày trước hạn xử lý để đánh dấu "sắp đến hạn"'],
];

if (is_post()) {
    csrf_check();
    $a = in_str('action');
    $id = in_int('id');
    try {
        switch ($a) {
            case 'cai_dat':
                foreach (KHOA_CAI_DAT as $k => $_) {
                    $v = in_str($k);
                    if (in_array($k, ['co_quan_chu_quan', 'ten_co_quan', 'dang_cap_tren', 'dang_co_quan'], true)) {
                        $v = upper($v);
                    }
                    if ($k === 'danh_so_hc' && !in_array($v, ['chung', 'theo_loai'], true)) $v = 'chung';
                    if ($k === 'co_chu_noi_dung' && !in_array($v, ['13', '14'], true)) $v = '14';
                    setting_save($k, $v);
                }
                log_action('cai_dat', 'he_thong');
                break;
            case 'don_vi':
                if (in_str('ten') === '') throw new RuntimeException('Nhập tên đơn vị.');
                if ($id) q('UPDATE hc_don_vi SET ten=?, viet_tat=?, thu_tu=? WHERE id=?', [in_str('ten'), in_str('viet_tat'), in_int('thu_tu'), $id]);
                else q('INSERT INTO hc_don_vi (ten, viet_tat, thu_tu) VALUES (?,?,?)', [in_str('ten'), in_str('viet_tat'), in_int('thu_tu')]);
                break;
            case 'xoa_don_vi':
                if (q_val('SELECT COUNT(*) FROM hc_van_ban_di WHERE don_vi_soan_id = ?', [$id])) throw new RuntimeException('Đơn vị đã có văn bản, không xóa được.');
                q('DELETE FROM hc_don_vi WHERE id = ?', [$id]);
                break;
            case 'loai':
                if (in_str('ten') === '') throw new RuntimeException('Nhập tên loại văn bản.');
                $he = in_str('he') === 'dang' ? 'dang' : 'hc';
                $co = in_int('co_ten_loai') ? 1 : 0;
                if ($co && in_str('viet_tat') === '') throw new RuntimeException('Loại văn bản có tên loại phải có chữ viết tắt.');
                if ($id) q('UPDATE hc_loai_van_ban SET he=?, ten=?, viet_tat=?, co_ten_loai=?, thu_tu=? WHERE id=?', [$he, in_str('ten'), in_str('viet_tat'), $co, in_int('thu_tu'), $id]);
                else q('INSERT INTO hc_loai_van_ban (he, ten, viet_tat, co_ten_loai, thu_tu) VALUES (?,?,?,?,?)', [$he, in_str('ten'), in_str('viet_tat'), $co, in_int('thu_tu')]);
                break;
            case 'xoa_loai':
                if (q_val('SELECT COUNT(*) FROM hc_van_ban_di WHERE loai_id = ?', [$id]) || q_val('SELECT COUNT(*) FROM hc_du_thao WHERE loai_id = ?', [$id])) {
                    throw new RuntimeException('Loại văn bản đã được sử dụng, không xóa được.');
                }
                q('DELETE FROM hc_loai_van_ban WHERE id = ?', [$id]);
                break;
            case 'nguoi_ky':
                if (in_str('ho_ten') === '' || in_str('chuc_vu') === '') throw new RuntimeException('Nhập họ tên và chức vụ.');
                $vals = [in_str('he') === 'dang' ? 'dang' : 'hc', in_str('ho_ten'), upper(in_str('chuc_vu')), in_str('quyen_han'), upper(in_str('thay_mat')), in_int('thu_tu')];
                if ($id) q('UPDATE hc_nguoi_ky SET he=?, ho_ten=?, chuc_vu=?, quyen_han=?, thay_mat=?, thu_tu=? WHERE id=?', array_merge($vals, [$id]));
                else q('INSERT INTO hc_nguoi_ky (he, ho_ten, chuc_vu, quyen_han, thay_mat, thu_tu) VALUES (?,?,?,?,?,?)', $vals);
                break;
            case 'xoa_nguoi_ky':
                q('DELETE FROM hc_nguoi_ky WHERE id = ?', [$id]);
                break;
        }
        if ($a !== 'cai_dat') log_action($a, 'danh_muc', $id ?: null);
        flash('ok', 'Đã lưu.');
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
    }
    redirect('danh-muc.php?tab=' . $tab);
}

page_header('Danh mục & Cài đặt');
?>
<div class="tabs">
  <?php foreach ($tabs as $k => $v): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="?tab=<?= $k ?>"><?= e($v) ?></a><?php endforeach; ?>
</div>

<?php if ($tab === 'cai_dat'): ?>
  <form class="card form" method="post">
    <?= csrf_field() ?>
    <?php foreach (KHOA_CAI_DAT as $k => [$label, $hint]): ?>
      <label><?= e($label) ?>
        <?php if ($k === 'danh_so_hc'): ?>
          <select name="<?= $k ?>"><?= select_options(['chung' => 'Một dãy số chung cho mọi văn bản trong năm (theo NĐ 30)', 'theo_loai' => 'Mỗi loại văn bản một dãy số riêng trong năm'], setting($k, 'chung')) ?></select>
        <?php else: ?>
          <input name="<?= $k ?>" value="<?= e(setting($k)) ?>">
        <?php endif; ?>
        <?php if ($hint): ?><small><?= e($hint) ?></small><?php endif; ?>
      </label>
    <?php endforeach; ?>
    <div class="form-actions"><button class="btn" name="action" value="cai_dat">Lưu cài đặt</button></div>
  </form>

<?php elseif ($tab === 'don_vi'):
    $ds = don_vi_list(); $s = in_int('sua'); $r = $s ? q_one('SELECT * FROM hc_don_vi WHERE id=?', [$s]) : null; ?>
  <form class="card form" method="post">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
    <div class="row row-3">
      <label>Tên đơn vị<input name="ten" value="<?= e($r['ten'] ?? '') ?>" required></label>
      <label>Viết tắt<input name="viet_tat" value="<?= e($r['viet_tat'] ?? '') ?>" placeholder="vd: ĐT"><small>Dùng trong ký hiệu công văn</small></label>
      <label>Thứ tự<input type="number" name="thu_tu" value="<?= (int)($r['thu_tu'] ?? 0) ?>"></label>
    </div>
    <div class="form-actions"><button class="btn" name="action" value="don_vi"><?= $r ? 'Cập nhật' : 'Thêm đơn vị' ?></button><?php if ($r): ?> <a class="btn btn-ghost" href="?tab=don_vi">Hủy</a><?php endif; ?></div>
  </form>
  <div class="card"><table class="tbl"><thead><tr><th>Tên</th><th>Viết tắt</th><th>Thứ tự</th><th></th></tr></thead><tbody>
    <?php foreach ($ds as $x): ?><tr><td><?= e($x['ten']) ?></td><td><b><?= e($x['viet_tat']) ?></b></td><td><?= (int)$x['thu_tu'] ?></td>
      <td class="nowrap"><a class="btn btn-sm btn-ghost" href="?tab=don_vi&amp;sua=<?= (int)$x['id'] ?>">Sửa</a>
      <form class="inline" method="post" onsubmit="return confirm('Xóa?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-sm btn-danger-ghost" name="action" value="xoa_don_vi">Xóa</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div>

<?php elseif ($tab === 'loai'):
    $ds = loai_van_ban(); $s = in_int('sua'); $r = $s ? loai_by_id($s) : null; ?>
  <form class="card form" method="post">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
    <div class="row row-4">
      <label>Hệ văn bản<select name="he"><?= select_options(HE_VAN_BAN, $r['he'] ?? 'hc') ?></select></label>
      <label>Tên loại<input name="ten" value="<?= e($r['ten'] ?? '') ?>" required></label>
      <label>Viết tắt<input name="viet_tat" value="<?= e($r['viet_tat'] ?? '') ?>"></label>
      <label>Thứ tự<input type="number" name="thu_tu" value="<?= (int)($r['thu_tu'] ?? 0) ?>"></label>
    </div>
    <label class="check"><input type="checkbox" name="co_ten_loai" value="1" <?= ($r === null || (int)$r['co_ten_loai']) ? 'checked' : '' ?>> Có tên loại trên văn bản (bỏ chọn nếu là Công văn)</label>
    <div class="form-actions"><button class="btn" name="action" value="loai"><?= $r ? 'Cập nhật' : 'Thêm loại' ?></button><?php if ($r): ?> <a class="btn btn-ghost" href="?tab=loai">Hủy</a><?php endif; ?></div>
  </form>
  <div class="card"><div class="tbl-wrap"><table class="tbl"><thead><tr><th>Hệ</th><th>Tên loại</th><th>Viết tắt</th><th>Kiểu</th><th>Thứ tự</th><th></th></tr></thead><tbody>
    <?php foreach ($ds as $x): ?><tr><td><?= $x['he'] === 'dang' ? 'Đảng' : 'Hành chính' ?></td><td><?= e($x['ten']) ?></td><td><b><?= e($x['viet_tat']) ?></b></td><td><?= (int)$x['co_ten_loai'] ? 'Có tên loại' : 'Công văn' ?></td><td><?= (int)$x['thu_tu'] ?></td>
      <td class="nowrap"><a class="btn btn-sm btn-ghost" href="?tab=loai&amp;sua=<?= (int)$x['id'] ?>">Sửa</a>
      <form class="inline" method="post" onsubmit="return confirm('Xóa?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-sm btn-danger-ghost" name="action" value="xoa_loai">Xóa</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div></div>

<?php elseif ($tab === 'nguoi_ky'):
    $ds = q('SELECT * FROM hc_nguoi_ky ORDER BY he, thu_tu')->fetchAll(); $s = in_int('sua'); $r = $s ? q_one('SELECT * FROM hc_nguoi_ky WHERE id=?', [$s]) : null; ?>
  <form class="card form" method="post">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
    <div class="row row-3">
      <label>Hệ văn bản<select name="he"><?= select_options(HE_VAN_BAN, $r['he'] ?? 'hc') ?></select></label>
      <label>Họ tên<input name="ho_ten" value="<?= e($r['ho_ten'] ?? '') ?>" required placeholder="Không ghi học hàm, học vị"></label>
      <label>Chức vụ<input name="chuc_vu" value="<?= e($r['chuc_vu'] ?? '') ?>" required placeholder="vd: PHÓ GIÁM ĐỐC"></label>
    </div>
    <div class="row row-3">
      <label>Quyền hạn ký<select name="quyen_han"><?= select_options(['' => '(Ký trực tiếp)', 'TM.' => 'TM.', 'KT.' => 'KT.', 'TL.' => 'TL.', 'TUQ.' => 'TUQ.', 'Q.' => 'Q.', 'T/M' => 'T/M (Đảng)', 'K/T' => 'K/T (Đảng)', 'T/L' => 'T/L (Đảng)'], $r['quyen_han'] ?? '') ?></select></label>
      <label>Thay mặt / ký thay cho<input name="thay_mat" value="<?= e($r['thay_mat'] ?? '') ?>" placeholder="vd: GIÁM ĐỐC, BAN THƯỜNG VỤ"></label>
      <label>Thứ tự<input type="number" name="thu_tu" value="<?= (int)($r['thu_tu'] ?? 0) ?>"></label>
    </div>
    <div class="form-actions"><button class="btn" name="action" value="nguoi_ky"><?= $r ? 'Cập nhật' : 'Thêm người ký' ?></button><?php if ($r): ?> <a class="btn btn-ghost" href="?tab=nguoi_ky">Hủy</a><?php endif; ?></div>
  </form>
  <div class="card"><table class="tbl"><thead><tr><th>Hệ</th><th>Khối chữ ký</th><th>Họ tên</th><th></th></tr></thead><tbody>
    <?php foreach ($ds as $x): ?><tr><td><?= $x['he'] === 'dang' ? 'Đảng' : 'Hành chính' ?></td>
      <td><?php if ($x['quyen_han'] && $x['quyen_han'] !== 'Q.'): ?><b><?= e($x['quyen_han'] . ' ' . $x['thay_mat']) ?></b><br><?php endif; ?><?= e(($x['quyen_han'] === 'Q.' ? 'Q. ' : '') . $x['chuc_vu']) ?></td>
      <td><?= e($x['ho_ten']) ?></td>
      <td class="nowrap"><a class="btn btn-sm btn-ghost" href="?tab=nguoi_ky&amp;sua=<?= (int)$x['id'] ?>">Sửa</a>
      <form class="inline" method="post" onsubmit="return confirm('Xóa?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-sm btn-danger-ghost" name="action" value="xoa_nguoi_ky">Xóa</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div>

<?php else:
    $ds = q('SELECT * FROM hc_bo_dem ORDER BY khoa DESC')->fetchAll();
    $loai_map = [];
    foreach (loai_van_ban() as $l) $loai_map[$l['id']] = $l['ten']; ?>
  <div class="card">
    <p>Bộ đếm lưu số lớn nhất đã cấp của từng sổ. Số tiếp theo = max(bộ đếm, số lớn nhất trong sổ) + 1, nên số không bị trùng kể cả khi nhiều người cấp số cùng lúc.</p>
    <table class="tbl"><thead><tr><th>Sổ</th><th>Số đã cấp gần nhất</th></tr></thead><tbody>
      <?php foreach ($ds as $x):
          $p = explode(':', $x['khoa']);
          if ($p[0] === 'den') $ten = 'Văn bản đến năm ' . ($p[1] ?? '');
          elseif (($p[1] ?? '') === 'dang') $ten = 'Văn bản Đảng – nhiệm kỳ ' . ($p[2] ?? '') . ' – ' . ($loai_map[$p[3] ?? 0] ?? '');
          else $ten = 'Văn bản đi năm ' . ($p[2] ?? '') . (isset($p[3]) ? ' – ' . ($loai_map[$p[3]] ?? '') : ' (dãy chung)'); ?>
        <tr><td><?= e($ten) ?><br><small class="muted"><?= e($x['khoa']) ?></small></td><td><b><?= (int)$x['gia_tri'] ?></b></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
<?php endif; ?>
<?php
page_footer();
