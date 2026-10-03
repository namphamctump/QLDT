<?php
require __DIR__ . '/includes/bootstrap.php';

$DATA = require __DIR__ . '/includes/thethuc.php';
$tab = in_str('he', 'hc') === 'dang' ? 'dang' : 'hc';

function render_viet_tat(array $dong): string
{
    $h = '<table class="tbl tbl-tt"><thead><tr><th>Tên loại</th><th>Viết tắt</th><th>Tên loại</th><th>Viết tắt</th></tr></thead><tbody>';
    foreach (array_chunk($dong, 2) as $pair) {
        $h .= '<tr>';
        foreach ($pair as [$ten, $vt]) {
            $h .= '<td>' . e($ten) . '</td><td><b>' . e($vt) . '</b></td>';
        }
        if (count($pair) === 1) {
            $h .= '<td></td><td></td>';
        }
        $h .= '</tr>';
    }
    return $h . '</tbody></table>';
}

function render_bang(array $cot, array $dong): string
{
    $h = '<div class="tbl-wrap"><table class="tbl tbl-tt"><thead><tr>';
    foreach ($cot as $c) {
        $h .= '<th>' . e($c) . '</th>';
    }
    $h .= '</tr></thead><tbody>';
    foreach ($dong as $r) {
        $h .= '<tr>';
        foreach ($r as $i => $c) {
            $h .= $i === 0 && count($r) === 2 ? '<td><b>' . e($c) . '</b></td>' : '<td>' . e($c) . '</td>';
        }
        $h .= '</tr>';
    }
    return $h . '</tbody></table></div>';
}

$T = $DATA[$tab];
page_header('Tra cứu thể thức ' . mb_strtolower($T['ten']), [
    'sub' => $T['mo_ta'],
    'actions' => '<button class="btn btn-ghost" type="button" data-print>In trang</button>',
]);
?>
<div class="seg">
  <?php foreach ($DATA as $k => $v): ?>
    <a class="<?= $k === $tab ? 'on' : '' ?>" href="?he=<?= e($k) ?>"><b><?= e($v['ten']) ?></b><small><?= e($v['can_cu']) ?></small></a>
  <?php endforeach; ?>
</div>

<div class="search-line">
  <input type="search" placeholder="Tìm nhanh: vd. trích yếu, nơi nhận, QyĐ, lề trái…" data-filter=".tt-section">
  <button class="btn btn-ghost btn-sm" type="button" data-expand-all>Mở tất cả</button>
  <button class="btn btn-ghost btn-sm" type="button" data-collapse-all>Thu gọn</button>
</div>

<?php foreach ($T['muc'] as $i => $m): ?>
  <details class="card tt-section" <?= $i < 2 ? 'open' : '' ?>>
    <summary><?= e($m['tieu_de']) ?></summary>
    <div class="tt-body">
    <?php
    switch ($m['loai']) {
        case 'kv':
            echo '<table class="tbl tbl-kv"><tbody>';
            foreach ($m['dong'] as [$k, $v]) {
                echo '<tr><th>' . e($k) . '</th><td>' . e($v) . '</td></tr>';
            }
            echo '</tbody></table>';
            break;
        case 'bang':
            echo render_bang($m['cot'], $m['dong']);
            break;
        case 'viet_tat':
            echo render_viet_tat($m['dong']);
            break;
        case 'doan':
            echo '<p>' . $m['doan'] . '</p>';
            break;
        case 'doan_bang':
            echo '<p>' . $m['doan'] . '</p>' . render_bang($m['cot'], $m['dong']);
            break;
        case 'doan_viet_tat':
            echo '<p>' . $m['doan'] . '</p>' . render_viet_tat($m['dong']) . render_bang($m['cot'], $m['dong2']);
            break;
    }
    if (!empty($m['ghi_chu'])) {
        echo '<p class="note">' . e($m['ghi_chu']) . '</p>';
    }
    ?>
    </div>
  </details>
<?php endforeach; ?>

<details class="card tt-section" open>
  <summary>Áp dụng tại đơn vị</summary>
  <div class="tt-body">
    <?php if ($tab === 'hc'): ?>
      <table class="tbl tbl-kv"><tbody>
        <tr><th>Cơ quan chủ quản</th><td><?= e(setting('co_quan_chu_quan')) ?></td></tr>
        <tr><th>Cơ quan ban hành</th><td><?= e(setting('ten_co_quan')) ?></td></tr>
        <tr><th>Viết tắt cơ quan</th><td><b><?= e(setting('viet_tat_co_quan')) ?></b> — vd: Số: 05/QĐ-<?= e(setting('viet_tat_co_quan')) ?>; công văn: Số: 125/<?= e(setting('viet_tat_co_quan')) ?>-ĐT</td></tr>
        <tr><th>Đánh số</th><td><?= setting('danh_so_hc') === 'theo_loai' ? 'Liên tục từ 01 theo từng loại văn bản trong năm' : 'Liên tục từ 01 cho tất cả văn bản trong năm (01/01 – 31/12)' ?></td></tr>
      </tbody></table>
    <?php else: ?>
      <table class="tbl tbl-kv"><tbody>
        <tr><th>Cơ quan cấp trên</th><td><?= e(setting('dang_cap_tren')) ?></td></tr>
        <tr><th>Cơ quan ban hành</th><td><?= e(setting('dang_co_quan')) ?></td></tr>
        <tr><th>Viết tắt</th><td><b><?= e(setting('dang_viet_tat')) ?></b> — vd: Số 05-BC/<?= e(setting('dang_viet_tat')) ?></td></tr>
        <tr><th>Nhiệm kỳ</th><td><?= e(setting('dang_nhiem_ky')) ?> (số liên tục theo từng tên loại trong nhiệm kỳ)</td></tr>
      </tbody></table>
    <?php endif; ?>
    <?php $dvs = don_vi_list(); if ($dvs): ?>
      <p><b>Viết tắt đơn vị soạn thảo:</b></p>
      <div class="chips"><?php foreach ($dvs as $dv): ?><span class="chip"><b><?= e($dv['viet_tat']) ?></b> <?= e($dv['ten']) ?></span><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</details>

<p class="note">Công cụ chuẩn hóa các thành phần trên. Bản sao văn bản, dấu, chữ ký số và việc in hai mặt cần làm theo hướng dẫn của cơ quan có thẩm quyền.
<?php if (user()): ?> · <a href="soan-thao.php?moi=1&amp;he=<?= e($tab) ?>">Soạn văn bản theo mẫu →</a> · <a href="kiem-tra.php">Kiểm tra file Word →</a><?php endif; ?></p>
<?php
page_footer();
