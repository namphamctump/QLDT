<?php
/**
 * Dựng văn bản đúng thể thức:
 *  - Hành chính: Nghị định 30/2020/NĐ-CP (Phụ lục I)
 *  - Đảng:       Hướng dẫn 05-HD/VPTW
 *
 * VanBanLayout tạo danh sách "khối" (đoạn, bảng, đường kẻ) dùng chung cho
 * DocxWriter (xuất .docx) và HtmlWriter (xem trước trên trình duyệt), nên
 * bản xem trước và file Word luôn khớp nhau.
 *
 * Đơn vị: chiều rộng tính bằng twip (1 mm = 56.7 twip); cỡ chữ tính bằng pt.
 */

class VanBanLayout
{
    // A4 và lề 20/20/30/15 mm (giá trị công cụ dùng, nằm trong khoảng NĐ 30 cho phép)
    const PAGE_W = 11906;
    const PAGE_H = 16838;
    const M_TOP = 1134;
    const M_BOTTOM = 1134;
    const M_LEFT = 1701;
    const M_RIGHT = 850;
    const TEXT_W = 9355; // 11906 - 1701 - 850

    /** @var array */
    private $d;
    /** @var bool */
    private $dang;
    /** @var int */
    private $sz;

    /**
     * $d gồm:
     *  he, loai_ten, co_ten_loai, loai_vt, co_quan_chu_quan, co_quan,
     *  so_text, dia_danh_ngay, trich_yeu, kinh_gui[], noi_dung,
     *  quyen_han, thay_mat, chuc_vu, ho_ten, noi_nhan[], do_khan, co_chu
     */
    public function __construct(array $d)
    {
        $this->d = $d;
        $this->dang = ($d['he'] ?? 'hc') === 'dang';
        $this->sz = $this->dang ? 14 : (int)($d['co_chu'] ?? 14);
        if ($this->sz < 13 || $this->sz > 15) {
            $this->sz = 14;
        }
    }

    public function isDang(): bool
    {
        return $this->dang;
    }

    public function bodySize(): int
    {
        return $this->sz;
    }

    /* ---------- Tạo khối ---------- */

    private static function r(string $t, array $o = []): array
    {
        return ['t' => $t] + $o;
    }

    private static function p(array $runs, array $o = []): array
    {
        return ['type' => 'p', 'runs' => $runs] + $o;
    }

    /** Đường kẻ ngang nét liền, canh giữa, rộng $w twip. */
    private static function rule(int $w, int $container): array
    {
        return ['type' => 'rule', 'w' => $w, 'container' => $container];
    }

    private static function blank(int $sz = 6): array
    {
        return self::p([], ['sz' => $sz, 'after' => 0, 'before' => 0]);
    }

    private function d(string $k, $def = '')
    {
        return $this->d[$k] ?? $def;
    }

    public function blocks(): array
    {
        return $this->dang ? $this->blocksDang() : $this->blocksHanhChinh();
    }

    /* ======================= HÀNH CHÍNH (NĐ 30) ======================= */

    private function blocksHanhChinh(): array
    {
        $B = [];
        $wL = 4000;
        $wR = self::TEXT_W - $wL;
        $laCongVan = !(int)$this->d('co_ten_loai', 1);

        // Ô trái: cơ quan chủ quản (12–13, đứng), cơ quan ban hành (12–13, đứng, đậm) + đường kẻ 1/3–1/2
        $left = [];
        if (trim($this->d('co_quan_chu_quan')) !== '') {
            $left[] = self::p([self::r(upper($this->d('co_quan_chu_quan')), ['sz' => 12])], ['align' => 'center', 'line' => 1.0]);
        }
        $left[] = self::p([self::r(upper($this->d('co_quan')), ['sz' => 12, 'b' => true])], ['align' => 'center', 'line' => 1.0]);
        $left[] = self::rule((int)($wL * 0.4), $wL);

        // Ô phải: Quốc hiệu (12–13, đậm), Tiêu ngữ (13–14, đậm) + đường kẻ dài bằng dòng chữ
        $right = [
            self::p([self::r('CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', ['sz' => 12, 'b' => true])], ['align' => 'center', 'line' => 1.0]),
            self::p([self::r('Độc lập - Tự do - Hạnh phúc', ['sz' => 13, 'b' => true])], ['align' => 'center', 'line' => 1.0]),
            self::rule(3250, $wR),
        ];

        // Hàng 2: Số, ký hiệu (13, đứng) | Địa danh, ngày tháng (13–14, nghiêng)
        $left2 = [self::p([self::r($this->d('so_text'), ['sz' => 13])], ['align' => 'center', 'before' => 120])];
        if ($laCongVan && trim($this->d('trich_yeu')) !== '') {
            // V/v ... (12–13, đứng) cách dòng số ký hiệu 6pt
            $left2[] = self::p([self::r('V/v ' . $this->d('trich_yeu'), ['sz' => 12])], ['align' => 'center', 'before' => 120, 'line' => 1.0]);
        }
        if ($this->d('do_khan') !== '') {
            $left2[] = self::p([self::r(upper($this->d('do_khan')), ['sz' => 13, 'b' => true])], ['align' => 'center', 'before' => 120]);
        }
        $right2 = [self::p([self::r($this->d('dia_danh_ngay'), ['sz' => 14, 'i' => true])], ['align' => 'center', 'before' => 120])];

        $B[] = ['type' => 'table', 'widths' => [$wL, $wR], 'rows' => [[$left, $right], [$left2, $right2]]];

        if (!$laCongVan) {
            // Tên loại (13–14, IN HOA, đậm) + trích yếu (13–14, đậm) + đường kẻ
            $B[] = self::p([self::r(upper($this->d('loai_ten')), ['sz' => 14, 'b' => true])], ['align' => 'center', 'before' => 360, 'after' => 0, 'keep' => true]);
            if (trim($this->d('trich_yeu')) !== '') {
                $B[] = self::p([self::r($this->trichYeuCoTenLoai(), ['sz' => 14, 'b' => true])], ['align' => 'center', 'after' => 0, 'keep' => true, 'line' => 1.0]);
            }
            $B[] = self::rule(2200, self::TEXT_W);
            $B[] = self::blank();
        } else {
            $B[] = self::p([], ['sz' => 8, 'before' => 120]);
        }

        $this->kinhGui($B, 'Kính gửi');
        $this->noiDung($B);
        $this->chuKy($B, false);
        return $B;
    }

    /* ======================= ĐẢNG (HD 05) ======================= */

    private function blocksDang(): array
    {
        $B = [];
        $wL = 4250;
        $wR = self::TEXT_W - $wL;
        $laCongVan = !(int)$this->d('co_ten_loai', 1);

        // Trái: cơ quan cấp trên (14, đứng) / cơ quan ban hành (14, đứng, đậm) / dấu sao
        $left = [];
        if (trim($this->d('co_quan_chu_quan')) !== '') {
            $left[] = self::p([self::r(upper($this->d('co_quan_chu_quan')), ['sz' => 14])], ['align' => 'center', 'line' => 1.0]);
        }
        $left[] = self::p([self::r(upper($this->d('co_quan')), ['sz' => 14, 'b' => true])], ['align' => 'center', 'line' => 1.0]);
        $left[] = self::p([self::r('*', ['sz' => 14])], ['align' => 'center', 'line' => 1.0]);
        $left[] = self::p([self::r($this->d('so_text'), ['sz' => 14])], ['align' => 'center', 'line' => 1.0]);
        if ($laCongVan && trim($this->d('trich_yeu')) !== '') {
            // Trích yếu công văn (12, nghiêng) dưới số và ký hiệu
            $left[] = self::p([self::r($this->trichYeuCongVanDang(), ['sz' => 12, 'i' => true])], ['align' => 'center', 'line' => 1.0]);
        }
        if ($this->d('do_khan') !== '') {
            $left[] = self::p([self::r(upper($this->d('do_khan')), ['sz' => 14, 'b' => true])], ['align' => 'center', 'before' => 60]);
        }

        // Phải: ĐẢNG CỘNG SẢN VIỆT NAM (15, đậm) + đường kẻ dài bằng tiêu đề / địa danh ngày tháng (14, nghiêng)
        $right = [
            self::p([self::r('ĐẢNG CỘNG SẢN VIỆT NAM', ['sz' => 15, 'b' => true])], ['align' => 'right', 'line' => 1.0]),
            ['type' => 'rule', 'w' => 4300, 'container' => $wR, 'align' => 'right'],
            self::p([self::r($this->d('dia_danh_ngay'), ['sz' => 14, 'i' => true])], ['align' => 'right', 'before' => 120]),
        ];
        $B[] = ['type' => 'table', 'widths' => [$wL, $wR], 'rows' => [[$left, $right]]];

        if (!$laCongVan) {
            // Tên loại (15–16, IN HOA, đậm), trích yếu (14–15, đậm), dưới có 5 dấu gạch nối
            $B[] = self::p([self::r(upper($this->d('loai_ten')), ['sz' => 16, 'b' => true])], ['align' => 'center', 'before' => 360, 'after' => 0, 'keep' => true]);
            if (trim($this->d('trich_yeu')) !== '') {
                $B[] = self::p([self::r($this->trichYeuCoTenLoai(), ['sz' => 14, 'b' => true])], ['align' => 'center', 'after' => 0, 'keep' => true, 'line' => 1.0]);
            }
            $B[] = self::p([self::r('-----', ['sz' => 14])], ['align' => 'center', 'after' => 120]);
        } else {
            $B[] = self::p([], ['sz' => 8, 'before' => 120]);
        }

        $ltt = mb_strtolower($this->d('loai_ten'));
        $this->kinhGui($B, $ltt === 'tờ trình' ? 'Kính trình' : 'Kính gửi');
        $this->noiDung($B);
        $this->chuKy($B, true);
        return $B;
    }

    /* ======================= Thành phần dùng chung ======================= */

    private function trichYeuCoTenLoai(): string
    {
        $t = trim($this->d('trich_yeu'));
        return $t === '' ? '' : mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
    }

    private function trichYeuCongVanDang(): string
    {
        $t = trim($this->d('trich_yeu'));
        if (!preg_match('/^về\s/ui', $t)) {
            $t = 'về ' . $t;
        }
        return mb_strtolower(mb_substr($t, 0, 1)) . mb_substr($t, 1);
    }

    private function kinhGui(array &$B, string $nhan): void
    {
        $ds = array_values(array_filter(array_map('trim', (array)$this->d('kinh_gui', [])), 'strlen'));
        if (!$ds) {
            return;
        }
        $sz = $this->sz;
        if (count($ds) === 1) {
            $B[] = self::p([self::r($nhan . ': ' . rtrim($ds[0], " \t.;") . '.', ['sz' => $sz])], ['align' => 'center', 'after' => 120, 'before' => 120]);
            return;
        }
        $B[] = self::p([self::r($nhan . ':', ['sz' => $sz])], ['align' => 'left', 'ind_left' => 2000, 'before' => 120, 'after' => 0]);
        $n = count($ds);
        foreach ($ds as $i => $x) {
            $x = rtrim($x, " \t.;");
            $x = (strpos($x, '- ') === 0 ? '' : '- ') . $x . ($i === $n - 1 ? '.' : ';');
            $B[] = self::p([self::r($x, ['sz' => $sz])], ['align' => 'left', 'ind_left' => 3100, 'after' => 0]);
        }
        $B[] = self::blank();
    }

    /** Nội dung: canh đều, lùi đầu dòng 1 cm, cách đoạn 6pt. */
    private function noiDung(array &$B): void
    {
        $sz = $this->sz;
        $base = $this->dang
            ? ['align' => 'both', 'first' => 567, 'before' => 0, 'after' => 120, 'exact' => 20]
            : ['align' => 'both', 'first' => 567, 'before' => 0, 'after' => 120, 'line' => 1.15];

        $lines = preg_split('/\R/u', (string)$this->d('noi_dung'));
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (trim($line) === '') {
                continue;
            }
            $line = trim($line);

            // Phần, Chương, Mục: canh giữa, đậm
            if (preg_match('/^(Phần|Chương|Mục)\s+[IVXLCDM\d]+\b/u', $line)) {
                $B[] = self::p([self::r($line, ['sz' => $sz, 'b' => true])], ['align' => 'center', 'before' => 120, 'after' => 0, 'keep' => true] + ($this->dang ? ['exact' => 20] : []));
                continue;
            }
            // Dòng viết hoa toàn bộ (vd: QUYẾT ĐỊNH:, HIỆU TRƯỞNG ..., tên chương): canh giữa, đậm
            if (preg_match('/\p{L}/u', $line) && $line === upper($line) && mb_strlen($line) <= 160) {
                $B[] = self::p([self::r($line, ['sz' => $sz, 'b' => true])], ['align' => 'center', 'before' => 60, 'after' => 60, 'keep' => true] + ($this->dang ? ['exact' => 20] : []));
                continue;
            }
            // Điều N. ... : "Điều N." in đậm
            if (preg_match('/^(Điều\s+\d+[a-zđ]?\.)\s*(.*)$/u', $line, $m)) {
                $B[] = self::p(array_merge([self::r($m[1] . ' ', ['sz' => $sz, 'b' => true])], self::inline($m[2], $sz)), $base);
                continue;
            }
            // Căn cứ ban hành: in nghiêng
            if (preg_match('/^(Căn cứ|Xét đề nghị|Theo đề nghị|Xét)\b/u', $line) && (int)$this->d('co_ten_loai', 1)) {
                $B[] = self::p(self::inline($line, $sz, ['i' => true]), $base);
                continue;
            }
            $B[] = self::p(self::inline($line, $sz), $base);
        }
    }

    /** Hỗ trợ **đậm** và _nghiêng_ trong nội dung. */
    private static function inline(string $s, int $sz, array $extra = []): array
    {
        $out = [];
        $parts = preg_split('/(\*\*[^*]+\*\*|__[^_]+__|_[^_\s][^_]*_)/u', $s, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            if (preg_match('/^\*\*(.+)\*\*$/u', $part, $m)) {
                $out[] = self::r($m[1], ['sz' => $sz, 'b' => true] + $extra);
            } elseif (preg_match('/^__(.+)__$/u', $part, $m)) {
                $out[] = self::r($m[1], ['sz' => $sz, 'u' => true] + $extra);
            } elseif (preg_match('/^_(.+)_$/u', $part, $m)) {
                $out[] = self::r($m[1], ['sz' => $sz, 'i' => true] + $extra);
            } else {
                $out[] = self::r($part, ['sz' => $sz] + $extra);
            }
        }
        return $out;
    }

    /** Nơi nhận (trái) + quyền hạn, chức vụ, họ tên người ký (phải). */
    private function chuKy(array &$B, bool $dang): void
    {
        $wL = 4400;
        $wR = self::TEXT_W - $wL;

        $left = [];
        $ds = array_values(array_filter(array_map('trim', (array)$this->d('noi_nhan', [])), 'strlen'));
        if ($dang) {
            $left[] = self::p([self::r('Nơi nhận:', ['sz' => 14, 'u' => true])], ['align' => 'left', 'line' => 1.0]);
            $szItem = 12;
        } else {
            $left[] = self::p([self::r('Nơi nhận:', ['sz' => 12, 'b' => true, 'i' => true])], ['align' => 'left', 'line' => 1.0]);
            $szItem = 11;
        }
        $n = count($ds);
        foreach ($ds as $i => $x) {
            $x = rtrim($x, " \t.;");
            $x = (strpos($x, '-') === 0 ? $x : '- ' . $x) . ($i === $n - 1 ? '.' : ';');
            $left[] = self::p([self::r($x, ['sz' => $szItem])], ['align' => 'left', 'line' => 1.0]);
        }

        $right = [];
        $qh = trim($this->d('quyen_han'));
        $tm = trim($this->d('thay_mat'));
        $cv = trim($this->d('chuc_vu'));
        if ($qh === 'Q.') {
            $right[] = self::p([self::r('Q. ' . upper($cv), ['sz' => 14, 'b' => true])], ['align' => 'center', 'line' => 1.0]);
        } else {
            if ($qh !== '' && $tm !== '') {
                $right[] = self::p([self::r($qh . ' ' . upper($tm), ['sz' => 14, 'b' => true])], ['align' => 'center', 'line' => 1.0]);
            }
            if ($cv !== '') {
                // Đảng: chức vụ in hoa, đứng, không đậm; Hành chính: in hoa, đậm
                $right[] = self::p([self::r(upper($cv), ['sz' => 14, 'b' => !$dang])], ['align' => 'center', 'line' => 1.0]);
            }
        }
        for ($i = 0; $i < 4; $i++) {
            $right[] = self::p([], ['sz' => 14, 'line' => 1.0]);
        }
        if (trim($this->d('ho_ten')) !== '') {
            $right[] = self::p([self::r(trim($this->d('ho_ten')), ['sz' => 14, 'b' => true])], ['align' => 'center', 'line' => 1.0]);
        }

        $B[] = self::p([], ['sz' => 8, 'after' => 0]);
        $B[] = ['type' => 'table', 'widths' => [$wL, $wR], 'rows' => [[$left, $right]], 'keep' => true];
    }
}

/* ============================================================================
 *  Xuất .docx (WordprocessingML) — chỉ cần ZipArchive, không cần thư viện ngoài
 * ========================================================================== */

class DocxWriter
{
    public static function x(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function rPr(array $r): string
    {
        $x = '';
        if (!empty($r['b'])) $x .= '<w:b/><w:bCs/>';
        if (!empty($r['i'])) $x .= '<w:i/><w:iCs/>';
        if (!empty($r['u'])) $x .= '<w:u w:val="single"/>';
        if (!empty($r['sz'])) {
            $hp = (int)round($r['sz'] * 2);
            $x .= '<w:sz w:val="' . $hp . '"/><w:szCs w:val="' . $hp . '"/>';
        }
        return $x === '' ? '' : '<w:rPr>' . $x . '</w:rPr>';
    }

    private static function para(array $p): string
    {
        $ppr = '';
        if (!empty($p['keep'])) $ppr .= '<w:keepNext/>';
        $before = (int)($p['before'] ?? 0);
        $after = (int)($p['after'] ?? 0);
        if (!empty($p['exact'])) {
            $ppr .= '<w:spacing w:before="' . $before . '" w:after="' . $after . '" w:line="' . ((int)$p['exact'] * 20) . '" w:lineRule="exact"/>';
        } else {
            $line = (int)round(240 * (float)($p['line'] ?? 1.0));
            $ppr .= '<w:spacing w:before="' . $before . '" w:after="' . $after . '" w:line="' . $line . '" w:lineRule="auto"/>';
        }
        $ind = '';
        if (!empty($p['ind_left'])) $ind .= ' w:left="' . (int)$p['ind_left'] . '"';
        if (!empty($p['ind_right'])) $ind .= ' w:right="' . (int)$p['ind_right'] . '"';
        if (!empty($p['first'])) $ind .= ' w:firstLine="' . (int)$p['first'] . '"';
        if ($ind !== '') $ppr .= '<w:ind' . $ind . '/>';
        $map = ['left' => 'left', 'center' => 'center', 'right' => 'right', 'both' => 'both'];
        $ppr .= '<w:jc w:val="' . ($map[$p['align'] ?? 'left'] ?? 'left') . '"/>';
        if (!empty($p['sz']) && empty($p['runs'])) {
            $hp = (int)round($p['sz'] * 2);
            $ppr .= '<w:rPr><w:sz w:val="' . $hp . '"/><w:szCs w:val="' . $hp . '"/></w:rPr>';
        }
        $runs = '';
        foreach ($p['runs'] as $r) {
            $runs .= '<w:r>' . self::rPr($r) . '<w:t xml:space="preserve">' . self::x($r['t']) . '</w:t></w:r>';
        }
        return '<w:p><w:pPr>' . $ppr . '</w:pPr>' . $runs . '</w:p>';
    }

    /** Đường kẻ: đoạn trống có viền trên, thụt hai bên để đạt độ dài mong muốn. */
    private static function rule(array $b): string
    {
        $c = (int)$b['container'];
        $w = min((int)$b['w'], $c);
        if (($b['align'] ?? 'center') === 'right') {
            $l = $c - $w; $r = 0;
        } else {
            $l = (int)(($c - $w) / 2); $r = $l;
        }
        return '<w:p><w:pPr><w:pBdr><w:top w:val="single" w:sz="6" w:space="1" w:color="000000"/></w:pBdr>'
            . '<w:spacing w:before="60" w:after="0" w:line="240" w:lineRule="auto"/>'
            . '<w:ind w:left="' . $l . '" w:right="' . $r . '"/>'
            . '<w:rPr><w:sz w:val="4"/><w:szCs w:val="4"/></w:rPr></w:pPr></w:p>';
    }

    private static function table(array $t): string
    {
        $total = array_sum($t['widths']);
        $x = '<w:tbl><w:tblPr><w:tblW w:w="' . $total . '" w:type="dxa"/><w:tblInd w:w="0" w:type="dxa"/>'
            . '<w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders>'
            . '<w:tblLayout w:type="fixed"/><w:tblCellMar><w:left w:w="0" w:type="dxa"/><w:right w:w="0" w:type="dxa"/></w:tblCellMar>'
            . '<w:tblLook w:val="0000"/></w:tblPr><w:tblGrid>';
        foreach ($t['widths'] as $w) {
            $x .= '<w:gridCol w:w="' . (int)$w . '"/>';
        }
        $x .= '</w:tblGrid>';
        foreach ($t['rows'] as $row) {
            $x .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>';
            foreach ($row as $i => $cell) {
                $x .= '<w:tc><w:tcPr><w:tcW w:w="' . (int)$t['widths'][$i] . '" w:type="dxa"/></w:tcPr>';
                $inner = '';
                foreach ($cell as $b) {
                    $inner .= self::block($b);
                }
                $x .= $inner !== '' ? $inner : '<w:p/>';
                $x .= '</w:tc>';
            }
            $x .= '</w:tr>';
        }
        return $x . '</w:tbl>';
    }

    private static function block(array $b): string
    {
        switch ($b['type']) {
            case 'p': return self::para($b);
            case 'rule': return self::rule($b);
            case 'table': return self::table($b);
        }
        return '';
    }

    /** Trả về nội dung nhị phân của file .docx. */
    public static function build(VanBanLayout $L, string $title = 'Văn bản'): string
    {
        $body = '';
        foreach ($L->blocks() as $b) {
            $body .= self::block($b);
        }
        $pageSz = $L->isDang() ? 28 : 28; // số trang cỡ 14 (HC cho phép 13–14)
        $sect = '<w:sectPr><w:headerReference w:type="default" r:id="rIdH1"/>'
            . '<w:pgSz w:w="' . VanBanLayout::PAGE_W . '" w:h="' . VanBanLayout::PAGE_H . '"/>'
            . '<w:pgMar w:top="' . VanBanLayout::M_TOP . '" w:right="' . VanBanLayout::M_RIGHT . '" w:bottom="' . VanBanLayout::M_BOTTOM . '" w:left="' . VanBanLayout::M_LEFT . '" w:header="567" w:footer="567" w:gutter="0"/>'
            . '<w:titlePg/></w:sectPr>';

        $ns = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document ' . $ns . '><w:body>' . $body . $sect . '</w:body></w:document>';

        // Số trang: chữ số Ả Rập, giữa lề trên, không hiện ở trang đầu (titlePg)
        $header = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:hdr ' . $ns . '><w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:rPr><w:sz w:val="' . $pageSz . '"/></w:rPr><w:fldChar w:fldCharType="begin"/></w:r>'
            . '<w:r><w:rPr><w:sz w:val="' . $pageSz . '"/></w:rPr><w:instrText xml:space="preserve"> PAGE </w:instrText></w:r>'
            . '<w:r><w:rPr><w:sz w:val="' . $pageSz . '"/></w:rPr><w:fldChar w:fldCharType="separate"/></w:r>'
            . '<w:r><w:rPr><w:sz w:val="' . $pageSz . '"/></w:rPr><w:t>2</w:t></w:r>'
            . '<w:r><w:rPr><w:sz w:val="' . $pageSz . '"/></w:rPr><w:fldChar w:fldCharType="end"/></w:r>'
            . '</w:p></w:hdr>';

        $bodyHp = $L->bodySize() * 2;
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:color w:val="000000"/><w:sz w:val="' . $bodyHp . '"/><w:szCs w:val="' . $bodyHp . '"/><w:lang w:val="vi-VN" w:eastAsia="en-US" w:bidi="ar-SA"/>'
            . '</w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Header"><w:name w:val="header"/><w:basedOn w:val="Normal"/></w:style>'
            . '<w:style w:type="table" w:default="1" w:styleId="TableNormal"><w:name w:val="Normal Table"/><w:tblPr><w:tblInd w:w="0" w:type="dxa"/><w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:left w:w="108" w:type="dxa"/><w:bottom w:w="0" w:type="dxa"/><w:right w:w="108" w:type="dxa"/></w:tblCellMar></w:tblPr></w:style>'
            . '</w:styles>';

        $settings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:defaultTabStop w:val="720"/><w:characterSpacingControl w:val="doNotCompress"/>'
            . '<w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>'
            . '</w:settings>';

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>'
            . '<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';

        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rIdS" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rIdSet" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>'
            . '<Relationship Id="rIdH1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/>'
            . '</Relationships>';

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . self::x($title) . '</dc:title><dc:creator>QLHC</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
        $app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>QLHC</Application></Properties>';

        $tmp = tempnam(sys_get_temp_dir(), 'qlhc');
        if ($tmp === false) {
            $tmp = QLHC_ROOT . '/uploads/tmp_' . bin2hex(random_bytes(6));
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file .docx (ZipArchive).');
        }
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);
        $zip->addFromString('word/styles.xml', $styles);
        $zip->addFromString('word/settings.xml', $settings);
        $zip->addFromString('word/header1.xml', $header);
        $zip->addFromString('docProps/core.xml', $core);
        $zip->addFromString('docProps/app.xml', $app);
        $zip->close();
        $data = file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }
}

/* ============================================================================
 *  Xem trước HTML (khổ A4, mô phỏng đúng bố cục)
 * ========================================================================== */

class HtmlWriter
{
    private static function mm(int $tw): string
    {
        return round($tw / 56.7, 2) . 'mm';
    }

    private static function para(array $p): string
    {
        $st = 'text-align:' . (($p['align'] ?? 'left') === 'both' ? 'justify' : ($p['align'] ?? 'left')) . ';';
        $st .= 'margin:' . round(($p['before'] ?? 0) / 20, 1) . 'pt 0 ' . round(($p['after'] ?? 0) / 20, 1) . 'pt 0;';
        if (!empty($p['exact'])) {
            $st .= 'line-height:' . (int)$p['exact'] . 'pt;';
        } else {
            $st .= 'line-height:' . round(1.15 * (float)($p['line'] ?? 1.0), 3) . ';';
        }
        if (!empty($p['first'])) $st .= 'text-indent:' . self::mm((int)$p['first']) . ';';
        if (!empty($p['ind_left'])) $st .= 'padding-left:' . self::mm((int)$p['ind_left']) . ';';
        if (empty($p['runs'])) {
            $st .= 'font-size:' . (int)($p['sz'] ?? 6) . 'pt;min-height:1em;';
            return '<p style="' . $st . '">&nbsp;</p>';
        }
        $h = '';
        foreach ($p['runs'] as $r) {
            $s = 'font-size:' . ($r['sz'] ?? 14) . 'pt;white-space:pre-wrap;';
            if (!empty($r['b'])) $s .= 'font-weight:700;';
            if (!empty($r['i'])) $s .= 'font-style:italic;';
            if (!empty($r['u'])) $s .= 'text-decoration:underline;';
            $h .= '<span style="' . $s . '">' . e($r['t']) . '</span>';
        }
        return '<p style="' . $st . '">' . $h . '</p>';
    }

    private static function block(array $b): string
    {
        if ($b['type'] === 'p') {
            return self::para($b);
        }
        if ($b['type'] === 'rule') {
            $w = min((int)$b['w'], (int)$b['container']);
            $ml = ($b['align'] ?? 'center') === 'right' ? 'auto' : 'auto';
            $mr = ($b['align'] ?? 'center') === 'right' ? '0' : 'auto';
            return '<div style="border-top:0.75pt solid #000;width:' . self::mm($w) . ';margin:2pt ' . $mr . ' 0 ' . $ml . ';height:0"></div>';
        }
        if ($b['type'] === 'table') {
            $h = '<table style="width:100%;border-collapse:collapse;table-layout:fixed">';
            foreach ($b['rows'] as $row) {
                $h .= '<tr>';
                foreach ($row as $i => $cell) {
                    $h .= '<td style="width:' . self::mm((int)$b['widths'][$i]) . ';vertical-align:top;padding:0">';
                    foreach ($cell as $c) {
                        $h .= self::block($c);
                    }
                    $h .= '</td>';
                }
                $h .= '</tr>';
            }
            return $h . '</table>';
        }
        return '';
    }

    public static function build(VanBanLayout $L): string
    {
        $h = '<div class="a4"><div class="a4-inner">';
        foreach ($L->blocks() as $b) {
            $h .= self::block($b);
        }
        return $h . '</div></div>';
    }
}

/**
 * Chuẩn bị dữ liệu hiển thị cho VanBanLayout từ dữ liệu dự thảo.
 * $f: mảng trường soạn thảo; $so: số đã cấp (0 = chưa cấp); $ngay: Y-m-d hoặc null.
 */
function chuan_bi_van_ban(array $f, int $so = 0, ?string $ngay = null): array
{
    $he = ($f['he'] ?? 'hc') === 'dang' ? 'dang' : 'hc';
    $loai = loai_by_id((int)($f['loai_id'] ?? 0)) ?: ['id' => 0, 'ten' => 'Công văn', 'viet_tat' => '', 'co_ten_loai' => 0];
    $dv = null;
    if (!empty($f['don_vi_id'])) {
        $dv = q_one('SELECT * FROM hc_don_vi WHERE id = ?', [(int)$f['don_vi_id']]);
    }
    $dvvt = $dv['viet_tat'] ?? '';

    if ($so > 0) {
        $skh = so_ky_hieu($he, $so, $loai, $dvvt);
    } else {
        // Chưa cấp số: để trống phần số, giữ nguyên ký hiệu
        $mau = so_ky_hieu($he, 1, $loai, $dvvt);
        $skh = preg_replace('/^01/', '      ', $mau);
    }

    $dia_danh = setting('dia_danh', 'Cần Thơ');
    if ($ngay) {
        $dd = ngay_van_ban($dia_danh, $ngay);
    } else {
        $dd = $dia_danh . ', ngày      tháng      năm ' . date('Y');
    }

    $lines = function ($s) {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)$s)), 'strlen'));
    };

    return [
        'he'               => $he,
        'loai_ten'         => $loai['ten'],
        'loai_vt'          => $loai['viet_tat'],
        'co_ten_loai'      => (int)$loai['co_ten_loai'],
        'co_quan_chu_quan' => $he === 'dang' ? setting('dang_cap_tren') : setting('co_quan_chu_quan'),
        'co_quan'          => $he === 'dang' ? setting('dang_co_quan') : setting('ten_co_quan'),
        'so_text'          => so_label($he, $skh),
        'so_ky_hieu'       => $skh,
        'dia_danh_ngay'    => $dd,
        'trich_yeu'        => (string)($f['trich_yeu'] ?? ''),
        'kinh_gui'         => $lines($f['kinh_gui'] ?? ''),
        'noi_dung'         => (string)($f['noi_dung'] ?? ''),
        'quyen_han'        => (string)($f['quyen_han'] ?? ''),
        'thay_mat'         => (string)($f['thay_mat'] ?? ''),
        'chuc_vu'          => (string)($f['chuc_vu'] ?? ''),
        'ho_ten'           => (string)($f['ho_ten'] ?? ''),
        'noi_nhan'         => $lines($f['noi_nhan'] ?? ''),
        'do_khan'          => !empty($f['do_khan']) ? (DO_KHAN[$f['do_khan']] ?? '') : '',
        'co_chu'           => (int)setting('co_chu_noi_dung', '14'),
    ];
}

/** Tên file .docx gợi ý. */
function ten_file_van_ban(array $vb): string
{
    $s = $vb['so_ky_hieu'] ?? 'du-thao';
    $s = trim(str_replace(['/', ' '], ['_', ''], $s), '_');
    $t = mb_substr(trim($vb['trich_yeu'] ?? ''), 0, 60);
    return ($s !== '' ? $s . ' - ' : '') . ($vb['loai_ten'] ?? 'Van ban') . ($t !== '' ? ' ' . $t : '') . '.docx';
}
