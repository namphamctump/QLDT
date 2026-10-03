<?php
/**
 * Kiểm tra & chuẩn hóa thể thức file .docx theo NĐ 30/2020 hoặc HD 05-HD/VPTW.
 *
 * Kiểm tra: khổ giấy, lề, phông chữ, cỡ chữ, canh đều nội dung, Quốc hiệu/Tiêu ngữ,
 *           số và ký hiệu, ngày tháng, nơi nhận, học hàm/học vị ở người ký,
 *           số trang, lỗi gõ (khoảng trắng thừa, dấu câu).
 * Chuẩn hóa: đổi toàn bộ phông sang Times New Roman, đặt A4 + lề 20/20/30/15 mm,
 *           sửa số 0 ở ngày/tháng và số văn bản, bỏ khoảng trắng thừa.
 */

class KiemTraTheThuc
{
    const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** @var string */
    private $he;
    /** @var ZipArchive */
    private $zip;
    /** @var DOMDocument */
    private $doc;
    /** @var DOMXPath */
    private $xp;
    /** @var array */
    private $kq = [];

    public function __construct(string $path, string $he = 'hc')
    {
        $this->he = $he === 'dang' ? 'dang' : 'hc';
        $this->zip = new ZipArchive();
        if ($this->zip->open($path) !== true) {
            throw new RuntimeException('Không mở được file. Hãy chắc chắn đây là file .docx (Word 2007 trở lên).');
        }
        $xml = $this->zip->getFromName('word/document.xml');
        if ($xml === false) {
            throw new RuntimeException('File không phải tài liệu Word hợp lệ (thiếu word/document.xml).');
        }
        $this->doc = new DOMDocument();
        $this->doc->preserveWhiteSpace = true;
        if (!@$this->doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
            throw new RuntimeException('Không đọc được nội dung file.');
        }
        $this->xp = new DOMXPath($this->doc);
        $this->xp->registerNamespace('w', self::W);
    }

    public function __destruct()
    {
        if ($this->zip) {
            @$this->zip->close();
        }
    }

    private function add(string $muc, string $muc_do, string $noi_dung, string $goi_y = ''): void
    {
        $this->kq[] = ['muc' => $muc, 'muc_do' => $muc_do, 'noi_dung' => $noi_dung, 'goi_y' => $goi_y];
    }

    private static function mm(int $tw): float
    {
        return round($tw / 56.7, 1);
    }

    /** Văn bản thuần của từng đoạn. */
    private function paragraphs(): array
    {
        $out = [];
        foreach ($this->xp->query('//w:body//w:p') as $p) {
            $t = '';
            foreach ($this->xp->query('.//w:t|.//w:tab', $p) as $n) {
                $t .= $n->localName === 'tab' ? "\t" : $n->textContent;
            }
            $jc = $this->xp->query('./w:pPr/w:jc', $p);
            $out[] = [
                'node' => $p,
                'text' => $t,
                'jc'   => $jc->length ? $jc->item(0)->getAttributeNS(self::W, 'val') : '',
                'in_table' => $this->xp->query('ancestor::w:tbl', $p)->length > 0,
            ];
        }
        return $out;
    }

    public function chay(): array
    {
        $this->kq = [];
        $this->kiemTraTrang();
        $this->kiemTraPhongCo();
        $paras = $this->paragraphs();
        $this->kiemTraNoiDung($paras);
        $this->kiemTraSoTrang();
        return $this->kq;
    }

    /* ---------------- Khổ giấy, lề ---------------- */

    private function kiemTraTrang(): void
    {
        $sect = $this->xp->query('//w:body/w:sectPr|//w:body//w:pPr/w:sectPr');
        if (!$sect->length) {
            $this->add('Khổ giấy', 'canh_bao', 'Không đọc được thiết lập trang (sectPr).');
            return;
        }
        $s = $sect->item($sect->length - 1);
        $pg = $this->xp->query('./w:pgSz', $s)->item(0);
        if ($pg) {
            $w = (int)$pg->getAttributeNS(self::W, 'w');
            $h = (int)$pg->getAttributeNS(self::W, 'h');
            $a4 = (abs($w - 11906) < 60 && abs($h - 16838) < 60) || (abs($h - 11906) < 60 && abs($w - 16838) < 60);
            if (!$a4) {
                $this->add('Khổ giấy', 'loi', sprintf('Khổ giấy %.0f × %.0f mm, không phải A4 (210 × 297 mm).', self::mm($w), self::mm($h)), 'Chọn Layout → Size → A4.');
            } elseif ($w > $h) {
                $this->add('Khổ giấy', 'canh_bao', 'Trang đang để chiều ngang.', 'Chỉ trình bày chiều ngang khi bảng, biểu rộng không tách được thành phụ lục.');
            } else {
                $this->add('Khổ giấy', 'dat', 'A4 (210 × 297 mm), chiều dọc.');
            }
        }
        $m = $this->xp->query('./w:pgMar', $s)->item(0);
        if ($m) {
            $g = function ($k) use ($m) {
                return self::mm((int)$m->getAttributeNS(self::W, $k));
            };
            $top = $g('top'); $bot = $g('bottom'); $left = $g('left'); $right = $g('right');
            $loi = [];
            if ($this->he === 'dang') {
                // HD 05: trên 20, dưới 20, trái 30, phải 15 mm
                foreach (['trên' => [$top, 20], 'dưới' => [$bot, 20], 'trái' => [$left, 30], 'phải' => [$right, 15]] as $k => [$v, $chuan]) {
                    if (abs($v - $chuan) > 1) $loi[] = "lề $k $v mm (chuẩn $chuan mm)";
                }
            } else {
                // NĐ 30: trên, dưới 20–25; trái 30–35; phải 15–20 mm
                foreach (['trên' => [$top, 20, 25], 'dưới' => [$bot, 20, 25], 'trái' => [$left, 30, 35], 'phải' => [$right, 15, 20]] as $k => [$v, $a, $b]) {
                    if ($v < $a - 0.6 || $v > $b + 0.6) $loi[] = "lề $k $v mm (cho phép {$a}–{$b} mm)";
                }
            }
            if ($loi) {
                $this->add('Lề trang', 'loi', 'Sai: ' . implode('; ', $loi) . '.', 'Đặt lề trên 20, dưới 20, trái 30, phải 15 mm (hoặc dùng "Chuẩn hóa tự động").');
            } else {
                $this->add('Lề trang', 'dat', "Trên $top · dưới $bot · trái $left · phải $right mm.");
            }
        }
    }

    /* ---------------- Phông, cỡ chữ ---------------- */

    private function fontsTrongStyles(): array
    {
        $fonts = [];
        $xml = $this->zip->getFromName('word/styles.xml');
        if ($xml) {
            $d = new DOMDocument();
            if (@$d->loadXML($xml, LIBXML_NONET)) {
                $x = new DOMXPath($d);
                $x->registerNamespace('w', self::W);
                foreach ($x->query('//w:docDefaults//w:rFonts|//w:style[@w:type="paragraph" and @w:default="1"]//w:rFonts') as $f) {
                    $n = $f->getAttributeNS(self::W, 'ascii') ?: $f->getAttributeNS(self::W, 'hAnsi');
                    if ($n) $fonts[$n] = true;
                    if ($f->getAttributeNS(self::W, 'asciiTheme')) $fonts['(phông theo theme: Calibri/Cambria…)'] = true;
                }
            }
        }
        return array_keys($fonts);
    }

    private function kiemTraPhongCo(): void
    {
        $fonts = [];
        foreach ($this->xp->query('//w:r[w:t]') as $r) {
            $f = $this->xp->query('./w:rPr/w:rFonts', $r)->item(0);
            if ($f) {
                $n = $f->getAttributeNS(self::W, 'ascii') ?: $f->getAttributeNS(self::W, 'hAnsi');
                if (!$n && $f->getAttributeNS(self::W, 'asciiTheme')) {
                    $n = '(phông theo theme)';
                }
                if ($n) {
                    $t = trim($this->xp->evaluate('string(./w:t)', $r));
                    if (!isset($fonts[$n])) $fonts[$n] = ['n' => 0, 'vd' => ''];
                    $fonts[$n]['n']++;
                    if ($fonts[$n]['vd'] === '' && $t !== '') $fonts[$n]['vd'] = mb_substr($t, 0, 40);
                }
            }
        }
        $sai = [];
        foreach ($fonts as $n => $info) {
            if (stripos($n, 'Times New Roman') === false) {
                $sai[] = $n . ' (' . $info['n'] . ' đoạn chữ, vd: "' . $info['vd'] . '")';
            }
        }
        foreach ($this->fontsTrongStyles() as $n) {
            if (stripos($n, 'Times New Roman') === false) {
                $sai[] = $n . ' (phông mặc định của tài liệu)';
            }
        }
        if ($sai) {
            $this->add('Phông chữ', 'loi', 'Có phông không phải Times New Roman: ' . implode('; ', array_unique($sai)) . '.', 'Dùng phông Times New Roman, bộ mã Unicode (TCVN 6909:2001).');
        } else {
            $this->add('Phông chữ', 'dat', 'Times New Roman.');
        }

        // Cỡ chữ
        $min = $this->he === 'dang' ? 12 : 11;
        $max = 16;
        $bad = [];
        foreach ($this->xp->query('//w:r[w:t]/w:rPr/w:sz') as $sz) {
            $pt = ((int)$sz->getAttributeNS(self::W, 'val')) / 2;
            if ($pt < $min || $pt > $max) {
                $t = trim($this->xp->evaluate('string(../../w:t)', $sz));
                if ($t !== '') {
                    $bad[(string)$pt] = $bad[(string)$pt] ?? mb_substr($t, 0, 40);
                }
            }
        }
        if ($bad) {
            $ds = [];
            foreach ($bad as $pt => $vd) $ds[] = "cỡ $pt (\"$vd\")";
            $this->add('Cỡ chữ', 'canh_bao', 'Có cỡ chữ ngoài khoảng ' . $min . '–' . $max . ': ' . implode('; ', array_slice($ds, 0, 6)) . '.', 'Nội dung 13–14 (Đảng 14–15); nơi nhận 11–12; tên loại 13–16.');
        } else {
            $this->add('Cỡ chữ', 'dat', 'Các cỡ chữ nằm trong khoảng ' . $min . '–' . $max . '.');
        }
    }

    /* ---------------- Nội dung ---------------- */

    private function kiemTraNoiDung(array $paras): void
    {
        $all = '';
        foreach ($paras as $p) {
            $all .= $p['text'] . "\n";
        }
        $flat = preg_replace('/[ \t\x{00A0}]+/u', ' ', $all);

        if ($this->he === 'hc') {
            if (!preg_match('/CỘNG H[ÒO]A XÃ HỘI CHỦ NGH[ĨI]A VIỆT NAM/u', $flat)) {
                $this->add('Quốc hiệu', 'loi', 'Không tìm thấy Quốc hiệu "CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM".', 'Quốc hiệu in hoa, cỡ 12–13, đứng, đậm, ở ô phải phía trên.');
            } else {
                $this->add('Quốc hiệu', 'dat', 'Có Quốc hiệu.');
            }
            if (preg_match('/Độc lập\s*([-–—])\s*Tự do\s*([-–—])\s*Hạnh phúc/u', $flat, $m)) {
                if ($m[1] !== '-' || $m[2] !== '-' || !preg_match('/Độc lập - Tự do - Hạnh phúc/u', $flat)) {
                    $this->add('Tiêu ngữ', 'canh_bao', 'Tiêu ngữ nên dùng gạch nối "-" có cách chữ hai bên: "Độc lập - Tự do - Hạnh phúc".');
                } else {
                    $this->add('Tiêu ngữ', 'dat', 'Đúng "Độc lập - Tự do - Hạnh phúc".');
                }
            } else {
                $this->add('Tiêu ngữ', 'loi', 'Không tìm thấy Tiêu ngữ "Độc lập - Tự do - Hạnh phúc".');
            }
            if (preg_match('/Số\s*:?\s*(\d+)\s*\/\s*([\p{L}\d\-]+)/u', $flat, $m)) {
                $loi = [];
                if (!preg_match('/Số:\s?\d/u', $m[0])) $loi[] = 'thiếu dấu hai chấm sau "Số"';
                if ((int)$m[1] < 10 && strlen($m[1]) < 2) $loi[] = 'số nhỏ hơn 10 phải thêm số 0 (vd: 05)';
                if ($loi) {
                    $this->add('Số, ký hiệu', 'loi', '"' . trim($m[0]) . '": ' . implode('; ', $loi) . '.', 'Định dạng: Số: 05/QĐ-TTDV');
                } else {
                    $this->add('Số, ký hiệu', 'dat', '"' . trim($m[0]) . '".');
                }
            } else {
                $this->add('Số, ký hiệu', 'canh_bao', 'Không nhận diện được số và ký hiệu văn bản (dạng "Số: 05/QĐ-TTDV").');
            }
        } else {
            if (mb_strpos($flat, 'ĐẢNG CỘNG SẢN VIỆT NAM') === false) {
                $this->add('Tiêu đề', 'loi', 'Không tìm thấy tiêu đề "ĐẢNG CỘNG SẢN VIỆT NAM".', 'In hoa, cỡ 15, đứng, đậm, góc phải dòng đầu; dưới có đường kẻ ngang.');
            } else {
                $this->add('Tiêu đề', 'dat', 'Có tiêu đề "ĐẢNG CỘNG SẢN VIỆT NAM".');
            }
            if (preg_match('/Số\s*(:?)\s*(\d+)\s*-\s*([\p{L}]+)\s*\/\s*([\p{L}\d]+)/u', $flat, $m)) {
                $loi = [];
                if ($m[1] === ':') $loi[] = 'văn bản Đảng không có dấu hai chấm sau "Số"';
                if ((int)$m[2] < 10 && strlen($m[2]) < 2) $loi[] = 'số nhỏ hơn 10 phải thêm số 0';
                if ($loi) {
                    $this->add('Số, ký hiệu', 'loi', '"' . trim($m[0]) . '": ' . implode('; ', $loi) . '.', 'Định dạng: Số 23-QĐ/ĐU');
                } else {
                    $this->add('Số, ký hiệu', 'dat', '"' . trim($m[0]) . '".');
                }
            } else {
                $this->add('Số, ký hiệu', 'canh_bao', 'Không nhận diện được số và ký hiệu (dạng "Số 23-QĐ/ĐU").');
            }
            if (!preg_match('/^\s*\*\s*$/mu', $all)) {
                $this->add('Dấu sao', 'canh_bao', 'Không thấy dấu sao (*) dưới tên cơ quan ban hành.');
            }
        }

        // Ngày tháng năm
        if (preg_match_all('/ngày\s+(\d{1,2})\s+tháng\s+(\d{1,2})\s+năm\s+(\d{4})/u', $flat, $mm, PREG_SET_ORDER)) {
            $sai = [];
            foreach ($mm as $m) {
                $d = (int)$m[1];
                $t = (int)$m[2];
                $ok = ($d < 10 ? strlen($m[1]) === 2 : true)
                    && ($t <= 2 ? strlen($m[2]) === 2 : strlen($m[2]) === strlen((string)$t));
                if (!$ok) {
                    $sai[] = '"' . $m[0] . '"';
                }
            }
            if ($sai) {
                $this->add('Ngày tháng', 'loi', 'Ghi sai số 0: ' . implode(', ', array_slice(array_unique($sai), 0, 5)) . '.', 'Ngày < 10 và tháng 1, 2 thêm số 0; tháng 3–12 không thêm (vd: ngày 05 tháng 01 năm 2026; ngày 29 tháng 6 năm 2026).');
            } else {
                $this->add('Ngày tháng', 'dat', 'Cách ghi ngày tháng năm đúng quy định.');
            }
        } else {
            $this->add('Ngày tháng', 'canh_bao', 'Không tìm thấy dòng địa danh, ngày tháng năm.');
        }

        // Nơi nhận
        if (!preg_match('/Nơi nhận\s*:/u', $flat)) {
            $this->add('Nơi nhận', 'canh_bao', 'Không thấy mục "Nơi nhận:".');
        } elseif (!preg_match('/Lưu\s*:?\s*VT/u', $flat) && $this->he === 'hc') {
            $this->add('Nơi nhận', 'canh_bao', 'Nơi nhận chưa có dòng "- Lưu: VT, ..." (văn thư và đơn vị soạn thảo).');
        } else {
            $this->add('Nơi nhận', 'dat', 'Có mục "Nơi nhận".');
        }
        if (preg_match('/Kính (gửi|trình)\s*:/u', $flat) && preg_match('/Nơi nhận\s*:/u', $flat) && !preg_match('/Như trên/u', $flat)) {
            $this->add('Nơi nhận', 'canh_bao', 'Văn bản có "Kính gửi" nhưng nơi nhận chưa có "- Như trên;" ở dòng đầu.');
        }

        // Học hàm, học vị ở họ tên người ký (vùng cuối văn bản)
        $cuoi = array_slice($paras, -25);
        foreach ($cuoi as $p) {
            $t = trim($p['text']);
            if ($t !== '' && mb_strlen($t) < 60 && preg_match('/^(GS|PGS|TS|ThS|BS|CKI|CKII|BSCKI|BSCKII|DS|KS|CN)\.\s*(TS\.|ThS\.|BS\.)?\s*\p{Lu}/u', $t)) {
                $this->add('Người ký', 'canh_bao', 'Họ tên người ký có học hàm/học vị: "' . $t . '".', 'Không ghi học hàm, học vị trước họ tên người ký trên văn bản hành chính.');
                break;
            }
        }

        // Canh đều hai bên phần nội dung
        $dai = 0;
        $khongDeu = 0;
        foreach ($paras as $p) {
            if ($p['in_table'] || mb_strlen($p['text']) < 120) continue;
            $dai++;
            if ($p['jc'] !== 'both' && $p['jc'] !== 'distribute') $khongDeu++;
        }
        if ($dai > 0) {
            if ($khongDeu > 0) {
                $this->add('Canh lề nội dung', 'canh_bao', "$khongDeu/$dai đoạn nội dung chưa canh đều hai bên.", 'Chọn Justify (Ctrl+J) cho phần nội dung.');
            } else {
                $this->add('Canh lề nội dung', 'dat', 'Các đoạn nội dung đã canh đều hai bên.');
            }
        }

        // Đảng: cách dòng Exactly 18–22pt
        if ($this->he === 'dang') {
            $sai = 0;
            $tong = 0;
            foreach ($paras as $p) {
                if ($p['in_table'] || mb_strlen($p['text']) < 120) continue;
                $tong++;
                $sp = $this->xp->query('./w:pPr/w:spacing', $p['node'])->item(0);
                $rule = $sp ? $sp->getAttributeNS(self::W, 'lineRule') : '';
                $line = $sp ? (int)$sp->getAttributeNS(self::W, 'line') : 0;
                if ($rule !== 'exact' || $line < 360 || $line > 440) $sai++;
            }
            if ($tong && $sai) {
                $this->add('Cách dòng', 'canh_bao', "$sai/$tong đoạn nội dung chưa đặt cách dòng Exactly 18–22 pt.");
            }
        }

        // Lỗi gõ thường gặp
        $loiGo = [];
        if (preg_match('/\S {2,}\S/u', $all)) $loiGo[] = 'có khoảng trắng kép';
        if (preg_match('/\p{L} +[,.;:](?!\.)/u', $all)) $loiGo[] = 'có khoảng trắng trước dấu câu';
        if (preg_match('/[,;:](?=\p{L})/u', $all)) $loiGo[] = 'thiếu khoảng trắng sau dấu phẩy/chấm phẩy/hai chấm';
        if (preg_match('/\b(Ủy Ban Nhân Dân|Thành Phố|Bộ Y Tế|Trường Đại Học)\b/u', $all, $m)) $loiGo[] = 'viết hoa sai "' . $m[1] . '"';
        if ($loiGo) {
            $this->add('Chính tả, trình bày', 'canh_bao', ucfirst(implode('; ', $loiGo)) . '.', 'Xem mục "Quy tắc viết hoa" trong Tra cứu thể thức.');
        }
    }

    /* ---------------- Số trang ---------------- */

    private function kiemTraSoTrang(): void
    {
        $coPage = false;
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $n = $this->zip->getNameIndex($i);
            if (preg_match('#^word/(header|footer)\d*\.xml$#', $n)) {
                $x = (string)$this->zip->getFromIndex($i);
                if (preg_match('/PAGE/', $x)) {
                    $coPage = true;
                    if (strpos($n, 'footer') !== false) {
                        $this->add('Số trang', 'canh_bao', 'Số trang đang đặt ở chân trang.', 'Đánh số trang ở giữa lề trên (đầu trang).');
                        return;
                    }
                }
            }
        }
        $titlePg = $this->xp->query('//w:sectPr/w:titlePg')->length > 0;
        $soTrang = (int)$this->xp->evaluate('count(//w:br[@w:type="page"])') + 1;
        if (!$coPage && $soTrang > 1) {
            $this->add('Số trang', 'canh_bao', 'Văn bản nhiều trang nhưng chưa đánh số trang.', 'Chèn số trang giữa lề trên, không hiện ở trang đầu.');
        } elseif ($coPage && !$titlePg) {
            $this->add('Số trang', 'canh_bao', 'Số trang đang hiện cả ở trang đầu.', 'Bật "Different First Page" để ẩn số trang ở trang 1.');
        } elseif ($coPage) {
            $this->add('Số trang', 'dat', 'Có số trang ở đầu trang, ẩn ở trang 1.');
        }
    }

    /* ======================= Chuẩn hóa tự động ======================= */

    /** Trả về nội dung .docx đã chuẩn hóa. */
    public static function chuanHoa(string $src, string $he = 'hc'): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'qlhcfix');
        copy($src, $tmp);
        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            throw new RuntimeException('Không mở được file.');
        }
        $tnr = 'Times New Roman';

        $fixFonts = function (DOMXPath $x) use ($tnr) {
            foreach ($x->query('//w:rFonts') as $f) {
                foreach (['asciiTheme', 'hAnsiTheme', 'eastAsiaTheme', 'cstheme'] as $a) {
                    if ($f->hasAttributeNS(self::W, $a)) $f->removeAttributeNS(self::W, $a);
                }
                foreach (['ascii', 'hAnsi', 'cs', 'eastAsia'] as $a) {
                    $f->setAttributeNS(self::W, 'w:' . $a, $tnr);
                }
            }
        };

        // document.xml
        $d = new DOMDocument();
        $d->preserveWhiteSpace = true;
        $d->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET | LIBXML_PARSEHUGE);
        $x = new DOMXPath($d);
        $x->registerNamespace('w', self::W);
        $fixFonts($x);

        foreach ($x->query('//w:sectPr') as $s) {
            $pg = $x->query('./w:pgSz', $s)->item(0);
            if ($pg) {
                $land = $pg->getAttributeNS(self::W, 'orient') === 'landscape';
                $pg->setAttributeNS(self::W, 'w:w', $land ? '16838' : '11906');
                $pg->setAttributeNS(self::W, 'w:h', $land ? '11906' : '16838');
            }
            $m = $x->query('./w:pgMar', $s)->item(0);
            if ($m) {
                $m->setAttributeNS(self::W, 'w:top', '1134');
                $m->setAttributeNS(self::W, 'w:bottom', '1134');
                $m->setAttributeNS(self::W, 'w:left', '1701');
                $m->setAttributeNS(self::W, 'w:right', '850');
            }
        }

        // Sửa trong từng đoạn chữ (w:t): ngày tháng, số văn bản, khoảng trắng kép
        foreach ($x->query('//w:t') as $t) {
            $v = $t->nodeValue;
            $n = preg_replace_callback('/ngày(\s+)(\d{1,2})(\s+)tháng(\s+)(\d{1,2})(\s+)năm/u', function ($m) {
                $d = (int)$m[2];
                $t = (int)$m[5];
                return 'ngày ' . ($d < 10 ? '0' . $d : $d) . ' tháng ' . ($t <= 2 ? '0' . $t : $t) . ' năm';
            }, $v);
            if ($he === 'dang') {
                $n = preg_replace_callback('/Số\s*:?\s*(\d)(\s*-)/u', function ($m) {
                    return 'Số 0' . $m[1] . $m[2];
                }, $n);
            } else {
                $n = preg_replace_callback('/Số\s*:?\s*(\d)(\s*\/)/u', function ($m) {
                    return 'Số: 0' . $m[1] . $m[2];
                }, $n);
            }
            $n = preg_replace('/(?<=\S) {2,}(?=\S)/u', ' ', $n);
            if ($n !== $v) {
                $t->nodeValue = '';
                $t->appendChild($d->createTextNode($n));
                $t->setAttribute('xml:space', 'preserve');
            }
        }
        $zip->addFromString('word/document.xml', $d->saveXML());

        // styles.xml, header/footer, numbering: đổi phông
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('#^word/(styles|numbering|header\d*|footer\d*|footnotes|endnotes)\.xml$#', $name)) continue;
            $sd = new DOMDocument();
            $sd->preserveWhiteSpace = true;
            if (!@$sd->loadXML((string)$zip->getFromIndex($i), LIBXML_NONET)) continue;
            $sx = new DOMXPath($sd);
            $sx->registerNamespace('w', self::W);
            $fixFonts($sx);
            if ($name === 'word/styles.xml') {
                // Đảm bảo docDefaults có rFonts TNR
                $rpr = $sx->query('//w:docDefaults/w:rPrDefault/w:rPr')->item(0);
                if ($rpr && !$sx->query('./w:rFonts', $rpr)->length) {
                    $f = $sd->createElementNS(self::W, 'w:rFonts');
                    foreach (['ascii', 'hAnsi', 'cs', 'eastAsia'] as $a) $f->setAttributeNS(self::W, 'w:' . $a, $tnr);
                    $rpr->insertBefore($f, $rpr->firstChild);
                }
            }
            $zip->addFromString($name, $sd->saveXML());
        }
        $zip->close();
        $data = file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }
}
