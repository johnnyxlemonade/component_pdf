<?php

namespace Lemonade\Pdf\Generator;

use DateTime;
use Exception;
use Lemonade\Pdf\HtmlParser;
use function imagejpeg;
use function imagedestroy;
use function imagecreatefromwebp;
use function mkdir;
use function is_dir;
use function imagepng;
use function sprintf;
use function is_string;
use function strtolower;
use function gzcompress;
use const PATHINFO_FILENAME;

/**
 * BaseFPDF
 * \Lemonade\Pdf\Generator\BaseFPDF
 */
abstract class BaseFPDF
{

    /**
     * Format
     */
    public const DEFAULT_DATE_FORMAT = 'Y-m-d';

    /**
     * Dny
     */
    public const DEFAULT_DAY_NAME = [
        "neděle",
        "pondělí",
        "úterý",
        "středa",
        "čtvrtek",
        "pátek",
        "sobota"
    ];

    const DPI = 300;
    const MM_IN_INCH = 25.4;
    const A4_HEIGHT = 297;
    const A4_WIDTH = 210;

    public const VERSION = "1.33";
    protected $unifontSubset;
    protected $page;               // current page number
    protected $n;                  // current object number
    protected $offsets;            // array of object offsets
    protected $buffer;             // buffer holding in-memory PDF
    protected $pages;              // array containing pages
    protected $state;              // current document state
    protected $compress;           // compression flag
    protected $k;                  // scale factor (number of points in user unit)
    protected $DefOrientation;     // default orientation
    protected $CurOrientation;     // current orientation
    protected $StdPageSizes;       // standard page sizes
    protected $DefPageSize;        // default page size
    protected $CurPageSize;        // current page size
    protected $CurRotation;        // current page rotation
    protected $PageInfo;           // page-related data
    protected $wPt, $hPt;          // dimensions of current page in points
    protected $w, $h;              // dimensions of current page in user unit
    protected $lMargin;            // left margin
    protected $tMargin;            // top margin
    protected $rMargin;            // right margin
    protected $bMargin;            // page break margin
    protected $cMargin;            // cell margin
    protected $x, $y;              // current position in user unit
    protected $lasth;              // height of last printed cell
    protected $LineWidth;          // line width in user unit
    protected $fontpath;           // path containing fonts
    protected $CoreFonts;          // array of core font names
    protected $fonts;              // array of used fonts
    protected $FontFiles;          // array of font files
    protected $encodings;          // array of encodings
    protected $cmaps;              // array of ToUnicode CMaps
    protected $FontFamily;         // current font family
    protected $FontStyle;          // current font style
    protected $underline;          // underlining flag
    protected $CurrentFont;        // current font info
    protected $FontSizePt;         // current font size in points
    protected $FontSize;           // current font size in user unit
    protected $DrawColor;          // commands for drawing color
    protected $FillColor;          // commands for filling color
    protected $TextColor;          // commands for text color
    protected $ColorFlag;          // indicates whether fill and text colors are different
    protected $WithAlpha;          // indicates whether alpha channel is used
    protected $ws;                 // word spacing
    protected $images;             // array of used images
    protected $PageLinks;          // array of links in pages
    protected $links;              // array of internal links
    protected $AutoPageBreak;      // automatic page breaking
    protected $PageBreakTrigger;   // threshold used to trigger page breaks
    protected $InHeader;           // flag set when processing header
    protected $InFooter;           // flag set when processing footer
    protected $AliasNbPages;       // alias for total number of pages
    protected $ZoomMode;           // zoom display mode
    protected $LayoutMode;         // layout display mode
    protected $metadata;           // document properties
    protected $CreationDate;       // document creation date
    protected $PDFVersion;         // PDF version number


    /**
     * @param string $orientation
     * @param string $unit
     * @param string $size
     * @throws Exception
     */
    public function __construct(string $orientation = "P", string $unit = "mm", string $size = "A4")
    {

        // Initialization of properties
        $this->state = 0;
        $this->page = 0;
        $this->n = 2;
        $this->buffer = "";
        $this->pages = [];
        $this->PageInfo = [];
        $this->fonts = [];
        $this->FontFiles = [];
        $this->encodings = [];
        $this->cmaps = [];
        $this->images = [];
        $this->links = [];
        $this->InHeader = false;
        $this->InFooter = false;
        $this->lasth = 0;
        $this->FontFamily = "";
        $this->FontStyle = "";
        $this->FontSizePt = 12;
        $this->underline = false;
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->WithAlpha = false;
        $this->ws = 0;

        // Font path
        $this->fontpath = dirname(__FILE__) . "/font/";

        // Core fonts
        $this->CoreFonts = [
            "courier",
            "helvetica",
            "times",
            "symbol",
            "zapfdingbats"
        ];

        // Scale factor
        if($unit == "pt") {

            $this->k = 1;

        } elseif($unit == "mm") {

            $this->k = 72/25.4;

        } elseif($unit == "cm") {

            $this->k = 72/2.54;

        } elseif($unit == "in") {

            $this->k = 72;

        } else {

            $this->Error('Incorrect unit: '.$unit);
        }

        // Page sizes
        $this->StdPageSizes = [
            "a3" => [841.89,1190.55],
            "a4" => [595.28,841.89],
            "a5" => [420.94,595.28],
            "letter" => [612,792],
            "legal" => [612,1008]
        ];


        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;

        // Page orientation
        $orientation = strtolower($orientation);

        if($orientation === "p" || $orientation == "portrait") {

            $this->DefOrientation = "P";
            $this->w = $size[0];
            $this->h = $size[1];

        } elseif($orientation === "l" || $orientation === "landscape") {

            $this->DefOrientation = "L";
            $this->w = $size[1];
            $this->h = $size[0];

        } else {

            $this->Error('Incorrect orientation: '.$orientation);
        }

        $this->CurOrientation = $this->DefOrientation;
        $this->wPt = $this->w*$this->k;
        $this->hPt = $this->h*$this->k;

        // Page rotation
        $this->CurRotation = 0;

        // Page margins (1 cm)
        $margin = 28.35 / $this->k;
        $this->SetMargins($margin, $margin);

        // Interior cell margin (1 mm)
        $this->cMargin = $margin / 10;

        // Line width (0.2 mm)
        $this->LineWidth = .567 / $this->k;

        // Automatic page break
        $this->SetAutoPageBreak(true, 2 * $margin);

        // Default display mode
        $this->SetDisplayMode('default');

        // Enable compression
        $this->SetCompression(true);

        // Metadata
        $this->metadata = [
            'Producer' => 'tFPDF ' . self::VERSION
        ];

        // Set default PDF version number
        $this->PDFVersion = "1.3";
    }

    /*******************************************************************************
     *                              Protected methods                               *
     *******************************************************************************/

    /**
     * @throws Exception
     */
    function Error($msg)
    {
        // Fatal error
        throw new Exception(sprintf("\Lemonade\Pdf\Generator\BaseFPDF error: %s", $msg));
    }

    /**
     * @throws Exception
     */
    protected function _getpagesize($size)
    {
        if (is_string($size)) {
            $size = strtolower($size);
            if (!isset($this->StdPageSizes[$size]))
                $this->Error('Unknown page size: ' . $size);
            $a = $this->StdPageSizes[$size];
            return array($a[0] / $this->k, $a[1] / $this->k);
        } else {
            if ($size[0] > $size[1])
                return array($size[1], $size[0]);
            else
                return $size;
        }
    }

    function SetMargins($left, $top, $right = null): void
    {
        // Set left, top and right margins
        $this->lMargin = $left;
        $this->tMargin = $top;
        if ($right === null)
            $right = $left;
        $this->rMargin = $right;
    }

    function SetAutoPageBreak($auto, $margin = 0): void
    {
        // Set auto page break mode and triggering margin
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }

    /**
     * @throws Exception
     */
    function SetDisplayMode($zoom, $layout = 'default'): void
    {
        // Set display mode in viewer
        if ($zoom == 'fullpage' || $zoom == 'fullwidth' || $zoom == 'real' || $zoom == 'default' || !is_string($zoom))
            $this->ZoomMode = $zoom;
        else
            $this->Error('Incorrect zoom display mode: ' . $zoom);
        if ($layout == 'single' || $layout == 'continuous' || $layout == 'two' || $layout == 'default')
            $this->LayoutMode = $layout;
        else
            $this->Error('Incorrect layout display mode: ' . $layout);
    }

    /**
     * @param $compress
     * @return void
     */
    function SetCompression($compress): void
    {
        // Set page compression
        $this->compress = function_exists("gzcompress") ? $compress : false;
    }

    function SetLeftMargin($margin): void
    {
        // Set left margin
        $this->lMargin = $margin;
        if ($this->page > 0 && $this->x < $margin)
            $this->x = $margin;
    }

    function SetTopMargin($margin): void
    {
        // Set top margin
        $this->tMargin = $margin;
    }

    function SetRightMargin($margin): void
    {
        // Set right margin
        $this->rMargin = $margin;
    }

    function SetTitle($title, $isUTF8 = false): void
    {
        // Title of document
        $this->metadata['Title'] = $isUTF8 ? $title : $this->_UTF8encode($title);
    }

    protected function _UTF8encode($s): bool|array|string
    {
        // Convert ISO-8859-1 to UTF-8
        return mb_convert_encoding((string) $s, 'UTF-8', 'ISO-8859-1');
    }

    function SetAuthor($author, $isUTF8 = false): void
    {
        // Author of document
        $this->metadata['Author'] = $isUTF8 ? $author : $this->_UTF8encode($author);
    }

    function SetSubject($subject, $isUTF8 = false): void
    {
        // Subject of document
        $this->metadata['Subject'] = $isUTF8 ? $subject : $this->_UTF8encode($subject);
    }

    function SetKeywords($keywords, $isUTF8 = false): void
    {
        // Keywords of document
        $this->metadata['Keywords'] = $isUTF8 ? $keywords : $this->_UTF8encode($keywords);
    }

    function SetCreator($creator, $isUTF8 = false): void
    {
        // Creator of document
        $this->metadata['Creator'] = $isUTF8 ? $creator : $this->_UTF8encode($creator);
    }

    function AliasNbPages($alias = '{nb}'): void
    {
        // Define an alias for total number of pages
        $this->AliasNbPages = $alias;
    }

    /**
     * @param string|NULL $index
     * @return string
     */
    public function getDateName(string $index = NULL): string
    {
        return (self::DEFAULT_DAY_NAME[$index] ?? "");
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {

        return $this->page;
    }

    /**
     * @param string|NULL $date
     * @param string|NULL $format
     * @return bool
     */
    public function isValidDate(string $date = NULL, string $format = NULL): bool
    {
        try {

            $f = (empty($format) ? self::DEFAULT_DATE_FORMAT : $format);
            $d = DateTime::createFromFormat($f, $date);

            return $d && $d->format($f) === $date;

        } catch (Exception) {}

        return FALSE;
    }

    /**
     * @return int
     */
    public function PageNo(): int
    {
        return $this->page;
    }

    /**
     * @throws Exception
     */
    function SetDrawColor($r, $g = null, $b = null): void
    {
        // Set color for all stroking operations
        if (($r == 0 && $g == 0 && $b == 0) || $g === null)
            $this->DrawColor = sprintf('%.3F G', $r / 255);
        else
            $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        if ($this->page > 0)
            $this->_out($this->DrawColor);
    }

    /**
     * @throws Exception
     */
    protected function _out($s): void
    {
        // Add a line to the document
        if ($this->state == 2)
            $this->pages[$this->page] .= $s . "\n";
        elseif ($this->state == 1)
            $this->_put($s);
        elseif ($this->state == 0)
            $this->Error('No page has been added yet');
        elseif ($this->state == 3)
            $this->Error('The document is closed');
    }

    protected function _put($s): void
    {
        $this->buffer .= $s . "\n";
    }

    /**
     * @throws Exception
     */
    function SetFillColor($r, $g = null, $b = null): void
    {
        // Set color for all filling operations
        if (($r == 0 && $g == 0 && $b == 0) || $g === null)
            $this->FillColor = sprintf('%.3F g', $r / 255);
        else
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
        if ($this->page > 0)
            $this->_out($this->FillColor);
    }

    function SetTextColor($r, $g = null, $b = null): void
    {
        // Set color for text
        if (($r == 0 && $g == 0 && $b == 0) || $g === null)
            $this->TextColor = sprintf('%.3F g', $r / 255);
        else
            $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
    }

    /**
     * @throws Exception
     */
    function SetLineWidth($width): void
    {
        // Set line width
        $this->LineWidth = $width;
        if ($this->page > 0)
            $this->_out(sprintf('%.2F w', $width * $this->k));
    }

    /**
     * @throws Exception
     */
    function Line($x1, $y1, $x2, $y2): void
    {
        // Draw a line
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
    }

    /**
     * @throws Exception
     */
    function Rect($x, $y, $w, $h, $style = ''): void
    {
        // Draw a rectangle
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
    }

    /**
     * @throws Exception
     */
    function SetFontSize($size): void
    {
        // Set font size in points
        if ($this->FontSizePt == $size)
            return;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        if ($this->page > 0)
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    function AddLink(): int
    {
        // Create a new internal link
        $n = count($this->links) + 1;
        $this->links[$n] = array(0, 0);
        return $n;
    }

    function SetLink($link, $y = 0, $page = -1): void
    {
        // Set destination of internal link
        if ($y == -1)
            $y = $this->y;
        if ($page == -1)
            $page = $this->page;
        $this->links[$link] = array($page, $y);
    }

    /**
     * @throws Exception
     */
    function Text($x, $y, $txt): void
    {
        // Output a string
        $txt = (string)$txt;
        if (!isset($this->CurrentFont))
            $this->Error('No font has been set');
        if ($this->unifontSubset) {
            $txt2 = '(' . $this->_escape($this->UTF8ToUTF16BE($txt, false)) . ')';
            foreach ($this->UTF8StringToArray($txt) as $uni)
                $this->CurrentFont['subset'][$uni] = $uni;
        } else
            $txt2 = '(' . $this->_escape($txt) . ')';
        $s = sprintf('BT %.2F %.2F Td %s Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $txt2);
        if ($this->underline && $txt != '')
            $s .= ' ' . $this->_dounderline($x, $y, $txt);
        if ($this->ColorFlag)
            $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
        $this->_out($s);
    }

    protected function _escape($s)
    {
        // Escape special characters
        if (str_contains($s, '(') || str_contains($s, ')') || str_contains($s, '\\') || str_contains($s, "\r"))
            return str_replace(array('\\', '(', ')', "\r"), array('\\\\', '\\(', '\\)', '\\r'), $s);
        else
            return $s;
    }

    protected function UTF8ToUTF16BE($str, $setbom = true): string
    {
        $outstr = "";
        if ($setbom) {
            $outstr .= "\xFE\xFF"; // Byte Order Mark (BOM)
        }
        $outstr .= mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
        return $outstr;
    }

    /**
     * @param $str
     * @return array
     */
    protected function UTF8StringToArray($str): array
    {
        $out = array();
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $uni = -1;
            $h = ord($str[$i]);
            if ($h <= 0x7F)
                $uni = $h;
            elseif ($h >= 0xC2) {
                if (($h <= 0xDF) && ($i < $len - 1))
                    $uni = ($h & 0x1F) << 6 | (ord($str[++$i]) & 0x3F);
                elseif (($h <= 0xEF) && ($i < $len - 2))
                    $uni = ($h & 0x0F) << 12 | (ord($str[++$i]) & 0x3F) << 6
                        | (ord($str[++$i]) & 0x3F);
                elseif (($h <= 0xF4) && ($i < $len - 3))
                    $uni = ($h & 0x0F) << 18 | (ord($str[++$i]) & 0x3F) << 12
                        | (ord($str[++$i]) & 0x3F) << 6
                        | (ord($str[++$i]) & 0x3F);
            }
            if ($uni >= 0) {
                $out[] = $uni;
            }
        }
        return $out;
    }

    protected function _dounderline($x, $y, $txt): string
    {
        // Underline text
        $up = $this->CurrentFont['up'];
        $ut = $this->CurrentFont['ut'];
        $w = $this->GetStringWidth($txt) + $this->ws * substr_count($txt, ' ');

        return sprintf('%.2F %.2F %.2F %.2F re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
    }

    /**
     * @param $s
     * @return float|int
     */
    function GetStringWidth($s): float|int
    {
        // Get width of a string in the current font
        $s = (string)$s;
        $cw = $this->CurrentFont['cw'];
        $w = 0;
        if ($this->unifontSubset) {
            $unicode = $this->UTF8StringToArray($s);
            foreach ($unicode as $char) {
                if (isset($cw[2 * $char])) {
                    $w += (ord($cw[2 * $char]) << 8) + ord($cw[2 * $char + 1]);
                } else if ($char > 0 && $char < 128 && isset($cw[chr($char)])) {
                    $w += $cw[chr($char)];
                } else $w += $this->CurrentFont['desc']['MissingWidth'] ?? $this->CurrentFont['MissingWidth'] ?? 500;
            }
        } else {
            $l = strlen($s);
            for ($i = 0; $i < $l; $i++)
                $w += $cw[$s[$i]];
        }
        return $w * $this->FontSize / 1000;
    }

    /**
     * @throws Exception
     */
    function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false): void
    {
        // Output text with automatic or explicit line breaks
        if (!isset($this->CurrentFont))
            $this->Error('No font has been set');
        $cw = $this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin);
        //$wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        if ($this->unifontSubset) {
            $nb = mb_strlen($s, 'utf-8');
            while ($nb > 0 && mb_substr($s, $nb - 1, 1, 'utf-8') == "\n") $nb--;
        } else {
            $nb = strlen($s);
            if ($nb > 0 && $s[$nb - 1] == "\n")
                $nb--;
        }
        $b = 0;
        if ($border) {
            if ($border == 1) {
                $border = 'LTRB';
                $b = 'LRT';
                $b2 = 'LR';
            } else {
                $b2 = '';
                if (str_contains($border, 'L'))
                    $b2 .= 'L';
                if (str_contains($border, 'R'))
                    $b2 .= 'R';
                $b = (str_contains($border, 'T')) ? $b2 . 'T' : $b2;
            }
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while ($i < $nb) {
            // Get next character
            if ($this->unifontSubset) {
                $c = mb_substr($s, $i, 1, 'UTF-8');
            } else {
                $c = $s[$i];
            }
            if ($c == "\n") {
                // Explicit line break
                if ($this->ws > 0) {
                    $this->ws = 0;
                    $this->_out('0 Tw');
                }
                if ($this->unifontSubset) {
                    $this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), $b, 2, $align, $fill);
                } else {
                    $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                }
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2)
                    $b = $b2;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }

            if ($this->unifontSubset) {
                $l += $this->GetStringWidth($c);
            } else {
                $l += $cw[$c] * $this->FontSize / 1000;
            }

            if ($l > $wmax) {
                // Automatic line break
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                    if ($this->ws > 0) {
                        $this->ws = 0;
                        $this->_out('0 Tw');
                    }
                    if ($this->unifontSubset) {
                        $this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), $b, 2, $align, $fill);
                    } else {
                        $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                    }
                } else {
                    if ($align == 'J') {
                        $this->ws = ($ns > 1) ? ($wmax - $ls) / ($ns - 1) : 0;
                        $this->_out(sprintf('%.3F Tw', $this->ws * $this->k));
                    }
                    if ($this->unifontSubset) {
                        $this->Cell($w, $h, mb_substr($s, $j, $sep - $j, 'UTF-8'), $b, 2, $align, $fill);
                    } else {
                        $this->Cell($w, $h, substr($s, $j, $sep - $j), $b, 2, $align, $fill);
                    }
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2)
                    $b = $b2;
            } else
                $i++;
        }
        // Last chunk
        if ($this->ws > 0) {
            $this->ws = 0;
            $this->_out('0 Tw');
        }
        if ($border && str_contains($border, 'B'))
            $b .= 'B';
        if ($this->unifontSubset) {
            $this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), $b, 2, $align, $fill);
        } else {
            $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
        }
        $this->x = $this->lMargin;
    }


    /**
     * @throws Exception
     */
    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = ''): void
    {
        // Output a cell
        $txt = (string)$txt;
        $k = $this->k;
        if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            // Automatic page break
            $x = $this->x;
            $ws = $this->ws;
            if ($ws > 0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation, $this->CurPageSize, $this->CurRotation);
            $this->x = $x;
            if ($ws > 0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3F Tw', $ws * $k));
            }
        }
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $s = '';
        if ($fill || $border == 1) {
            if ($fill)
                $op = ($border == 1) ? 'B' : 'f';
            else
                $op = 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
        }
        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (str_contains($border, 'L'))
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
            if (str_contains($border, 'T'))
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
            if (str_contains($border, 'R'))
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            if (str_contains($border, 'B'))
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
        }
        if ($txt !== '') {
            if (!isset($this->CurrentFont))
                $this->Error('No font has been set');
            if ($align == 'R')
                $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
            elseif ($align == 'C')
                $dx = ($w - $this->GetStringWidth($txt)) / 2;
            else
                $dx = $this->cMargin;
            if ($this->ColorFlag)
                $s .= 'q ' . $this->TextColor . ' ';
            // If multibyte, Tw has no effect - do word spacing using an adjustment before each space
            if ($this->ws && $this->unifontSubset) {
                foreach ($this->UTF8StringToArray($txt) as $uni)
                    $this->CurrentFont['subset'][$uni] = $uni;
                $space = $this->_escape($this->UTF8ToUTF16BE(' ', false));
                $s .= sprintf('BT 0 Tw %.2F %.2F Td [', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k);
                $t = explode(' ', $txt);
                $numt = count($t);
                for ($i = 0; $i < $numt; $i++) {
                    $tx = $t[$i];
                    $tx = '(' . $this->_escape($this->UTF8ToUTF16BE($tx, false)) . ')';
                    $s .= sprintf('%s ', $tx);
                    if (($i + 1) < $numt) {
                        $adj = -($this->ws * $this->k) * 1000 / $this->FontSizePt;
                        $s .= sprintf('%d(%s) ', $adj, $space);
                    }
                }
                $s .= '] TJ';
                $s .= ' ET';
            } else {
                if ($this->unifontSubset) {
                    $txt2 = '(' . $this->_escape($this->UTF8ToUTF16BE($txt, false)) . ')';
                    foreach ($this->UTF8StringToArray($txt) as $uni)
                        $this->CurrentFont['subset'][$uni] = $uni;
                } else
                    $txt2 = '(' . $this->_escape($txt) . ')';
                $s .= sprintf('BT %.2F %.2F Td %s Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txt2);
            }
            if ($this->underline)
                $s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + .5 * $h + .3 * $this->FontSize, $txt);
            if ($this->ColorFlag)
                $s .= ' Q';
            if ($link)
                $this->Link($this->x + $dx, $this->y + .5 * $h - .5 * $this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
        }
        if ($s)
            $this->_out($s);
        $this->lasth = $h;
        if ($ln > 0) {
            // Go to next line
            $this->y += $h;
            if ($ln == 1)
                $this->x = $this->lMargin;
        } else
            $this->x += $w;
    }

    function AcceptPageBreak()
    {
        // Accept automatic page break or not
        return $this->AutoPageBreak;
    }

    /**
     * @throws Exception
     */
    function AddPage($orientation = '', $size = '', $rotation = 0): void
    {
        // Start a new page
        if ($this->state == 3)
            $this->Error('The document is closed');
        $family = $this->FontFamily;
        $style = $this->FontStyle . ($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;
        if ($this->page > 0) {
            // Page footer
            $this->InFooter = true;
            $this->appFooter();
            $this->InFooter = false;
            // Close page
            $this->_endpage();
        }
        // Start new page
        $this->_beginpage($orientation, $size, $rotation);
        // Set line cap style to square
        $this->_out('2 J');
        // Set line width
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w', $lw * $this->k));
        // Set font
        if ($family)
            $this->SetFont($family, $style, $fontsize);
        // Set colors
        $this->DrawColor = $dc;
        if ($dc != '0 G')
            $this->_out($dc);
        $this->FillColor = $fc;
        if ($fc != '0 g')
            $this->_out($fc);
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
        // Page header
        $this->InHeader = true;
        $this->appHeader();
        $this->InHeader = false;
        // Restore line width
        if ($this->LineWidth != $lw) {
            $this->LineWidth = $lw;
            $this->_out(sprintf('%.2F w', $lw * $this->k));
        }
        // Restore font
        if ($family)
            $this->SetFont($family, $style, $fontsize);
        // Restore colors
        if ($this->DrawColor != $dc) {
            $this->DrawColor = $dc;
            $this->_out($dc);
        }
        if ($this->FillColor != $fc) {
            $this->FillColor = $fc;
            $this->_out($fc);
        }
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
    }

    /**
     * @return void
     */
    function appFooter(): void
    {
    }

    protected function _endpage(): void
    {
        $this->state = 1;
    }

    /**
     * @throws Exception
     */
    protected function _beginpage($orientation, $size, $rotation): void
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->PageLinks[$this->page] = array();
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        // Check page size and orientation
        if ($orientation == '')
            $orientation = $this->DefOrientation;
        else
            $orientation = strtoupper($orientation[0]);
        if ($size == '')
            $size = $this->DefPageSize;
        else
            $size = $this->_getpagesize($size);
        if ($orientation != $this->CurOrientation || $size[0] != $this->CurPageSize[0] || $size[1] != $this->CurPageSize[1]) {
            // New size or orientation
            if ($orientation == 'P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w * $this->k;
            $this->hPt = $this->h * $this->k;
            $this->PageBreakTrigger = $this->h - $this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
        }
        if ($orientation != $this->DefOrientation || $size[0] != $this->DefPageSize[0] || $size[1] != $this->DefPageSize[1])
            $this->PageInfo[$this->page]['size'] = array($this->wPt, $this->hPt);
        if ($rotation != 0) {
            if ($rotation % 90 != 0)
                $this->Error('Incorrect rotation value: ' . $rotation);
            $this->PageInfo[$this->page]['rotation'] = $rotation;
        }
        $this->CurRotation = $rotation;
    }

    /**
     * @throws Exception
     */
    function SetFont($family, $style = '', $size = 0): void
    {
        // Select a font; size given in points
        if ($family == '')
            $family = $this->FontFamily;
        else
            $family = strtolower($family);
        $style = strtoupper($style);
        if (str_contains($style, 'U')) {
            $this->underline = true;
            $style = str_replace('U', '', $style);
        } else
            $this->underline = false;
        if ($style == 'IB')
            $style = 'BI';
        if ($size == 0)
            $size = $this->FontSizePt;
        // Test if font is already selected
        if ($this->FontFamily == $family && $this->FontStyle == $style && $this->FontSizePt == $size)
            return;

        // Test if font is already loaded
        $fontkey = $family . $style;
        if (!isset($this->fonts[$fontkey])) {
            // Test if one of the core fonts
            if ($family == 'arial')
                $family = 'helvetica';
            if (in_array($family, $this->CoreFonts)) {
                if ($family == 'symbol' || $family == 'zapfdingbats')
                    $style = '';
                $fontkey = $family . $style;
                if (!isset($this->fonts[$fontkey]))
                    $this->AddFont($family, $style);
            } else
                $this->Error('Undefined font: ' . $family . ' ' . $style);
        }
        // Select it
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $this->CurrentFont = &$this->fonts[$fontkey];
        if ($this->fonts[$fontkey]['type'] == 'TTF') {
            $this->unifontSubset = true;
        } else {
            $this->unifontSubset = false;
        }
        if ($this->page > 0)
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    /**
     * @throws Exception
     */
    function AddFont($family, $style = '', $file = '', $uni = false): void
    {
        // Add a TrueType, OpenType or Type1 font
        $family = strtolower($family);
        $style = strtoupper($style);
        if ($style == 'IB')
            $style = 'BI';
        if ($file == '') {
            if ($uni) {
                $file = str_replace(' ', '', $family) . strtolower($style) . '.ttf';
            } else {
                $file = str_replace(' ', '', $family) . strtolower($style) . '.php';
            }
        }
        $fontkey = $family . $style;
        if (isset($this->fonts[$fontkey]))
            return;

        if ($uni) {

            $ttffilename = $this->fontpath . 'unifont/' . $file;
            $unifilename = $this->fontpath . 'unifont/' . strtolower(substr($file, 0, (strpos($file, '.'))));
            $name = '';
            $originalsize = 0;
            $ttfstat = stat($ttffilename);
            if (file_exists($unifilename . '.mtx.php')) {
                include($unifilename . '.mtx.php');
            }
            if (!isset($type) || !isset($name) || $originalsize != $ttfstat['size']) {

                $ttffile = $ttffilename;

                $ttf = new BaseTTFontFile();
                $ttf->getMetrics($ttffile);
                $cw = $ttf->charWidths;
                $name = preg_replace('/[ ()]/', '', $ttf->fullName);

                $desc = [
                    'Ascent' => round($ttf->ascent),
                    'Descent' => round($ttf->descent),
                    'CapHeight' => round($ttf->capHeight),
                    'Flags' => $ttf->flags,
                    'FontBBox' => '[' . round($ttf->bbox[0]) . " " . round($ttf->bbox[1]) . " " . round($ttf->bbox[2]) . " " . round($ttf->bbox[3]) . ']',
                    'ItalicAngle' => $ttf->italicAngle,
                    'StemV' => round($ttf->stemV),
                    'MissingWidth' => round($ttf->defaultWidth)
                ];
                $up = round($ttf->underlinePosition);
                $ut = round($ttf->underlineThickness);
                $originalsize = $ttfstat['size'] + 0;
                $type = 'TTF';
                // Generate metrics .php file
                $s = '<?php' . "\n";
                $s .= '$name=\'' . $name . "';\n";
                $s .= '$type=\'' . $type . "';\n";
                $s .= '$desc=' . var_export($desc, true) . ";\n";
                $s .= '$up=' . $up . ";\n";
                $s .= '$ut=' . $ut . ";\n";
                $s .= '$ttffile=\'' . $ttffile . "';\n";
                $s .= '$originalsize=' . $originalsize . ";\n";
                $s .= '$fontkey=\'' . $fontkey . "';\n";
                $s .= "?>";

                if (is_writable(dirname($this->fontpath . 'unifont/' . 'x'))) {
                    $fh = fopen($unifilename . '.mtx.php', "w");
                    fwrite($fh, $s, strlen($s));
                    fclose($fh);
                    $fh = fopen($unifilename . '.cw.dat', "wb");
                    fwrite($fh, $cw, strlen($cw));
                    fclose($fh);
                    @unlink($unifilename . '.cw127.php');
                }

                unset($ttf);

            } else {

                $cw = @file_get_contents($unifilename . '.cw.dat');
            }

            $i = count($this->fonts) + 1;

            if (!empty($this->AliasNbPages))
                $sbarr = range(0, 57);
            else
                $sbarr = range(0, 32);
            $this->fonts[$fontkey] = array('i' => $i, 'type' => $type, 'name' => $name, 'desc' => $desc, 'up' => $up, 'ut' => $ut, 'cw' => $cw, 'ttffile' => $ttffile, 'fontkey' => $fontkey, 'subset' => $sbarr, 'unifilename' => $unifilename);

            $this->FontFiles[$fontkey] = array('length1' => $originalsize, 'type' => "TTF", 'ttffile' => $ttffile);
            $this->FontFiles[$file] = array('type' => "TTF");

            unset($cw);

        } else {

            $info = $this->_loadfont($file);
            $info['i'] = count($this->fonts) + 1;

            if (!empty($info['file'])) {
                // Embedded font
                if ($info['type'] == 'TrueType')
                    $this->FontFiles[$info['file']] = array('length1' => $info['originalsize']);
                else
                    $this->FontFiles[$info['file']] = array('length1' => $info['size1'], 'length2' => $info['size2']);
            }
            $this->fonts[$fontkey] = $info;
        }
    }

    /**
     * @throws Exception
     */
    protected function _loadfont($font): array
    {
        // Load a font definition file from the font directory
        if (str_contains($font, '/') || str_contains($font, "\\"))
            $this->Error('Incorrect font definition file name: ' . $font);
        include($this->fontpath . $font);
        if (!isset($name))
            $this->Error('Could not include font definition file');
        if (isset($enc))
            $enc = strtolower($enc);
        if (!isset($subsetted))
            $subsetted = false;
        return get_defined_vars();
    }

    /**
     * @return void
     */
    function appHeader(): void
    {
    }

    function Link($x, $y, $w, $h, $link): void
    {
        // Put a link on the page
        $this->PageLinks[$this->page][] = array($x * $this->k, $this->hPt - $y * $this->k, $w * $this->k, $h * $this->k, $link);
    }

    /**
     * @throws Exception
     */
    function Write($h, $txt, $link = ''): void
    {
        // Output text in flowing mode
        if (!isset($this->CurrentFont))
            $this->Error('No font has been set');
        $cw = $this->CurrentFont['cw'];
        $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin);
        $s = str_replace("\r", '', (string)$txt);
        if ($this->unifontSubset) {
            $nb = mb_strlen($s, 'UTF-8');
            if ($nb == 1 && $s == " ") {
                $this->x += $this->GetStringWidth($s);
                return;
            }
        } else {
            $nb = strlen($s);
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            // Get next character
            if ($this->unifontSubset) {
                $c = mb_substr($s, $i, 1, 'UTF-8');
            } else {
                $c = $s[$i];
            }
            if ($c == "\n") {
                // Explicit line break
                if ($this->unifontSubset) {
                    $this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), 0, 2, '', false, $link);
                } else {
                    $this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', false, $link);
                }
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $this->x = $this->lMargin;
                    $w = $this->w - $this->rMargin - $this->x;
                    $wmax = ($w - 2 * $this->cMargin);
                }
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;

            if ($this->unifontSubset) {
                $l += $this->GetStringWidth($c);
            } else {
                $l += $cw[$c] * $this->FontSize / 1000;
            }

            if ($l > $wmax) {
                // Automatic line break
                if ($sep == -1) {
                    if ($this->x > $this->lMargin) {
                        // Move to next line
                        $this->x = $this->lMargin;
                        $this->y += $h;
                        $w = $this->w - $this->rMargin - $this->x;
                        $wmax = ($w - 2 * $this->cMargin);
                        $i++;
                        $nl++;
                        continue;
                    }
                    if ($i == $j)
                        $i++;
                    if ($this->unifontSubset) {
                        $this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), 0, 2, '', false, $link);
                    } else {
                        $this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', false, $link);
                    }
                } else {
                    if ($this->unifontSubset) {
                        $this->Cell($w, $h, mb_substr($s, $j, $sep - $j, 'UTF-8'), 0, 2, '', false, $link);
                    } else {
                        $this->Cell($w, $h, substr($s, $j, $sep - $j), 0, 2, '', false, $link);
                    }
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $this->x = $this->lMargin;
                    $w = $this->w - $this->rMargin - $this->x;
                    $wmax = ($w - 2 * $this->cMargin);
                }
                $nl++;
            } else
                $i++;
        }
        // Last chunk
        if ($i != $j) {
            if ($this->unifontSubset) {
                $this->Cell($l, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), 0, 0, '', false, $link);
            } else {
                $this->Cell($l, $h, substr($s, $j), 0, 0, '', false, $link);
            }
        }
    }

    function Ln($h = null): void
    {
        // Line feed; default value is the last cell height
        $this->x = $this->lMargin;
        if ($h === null)
            $this->y += $this->lasth;
        else
            $this->y += $h;
    }

    /**
     * @throws Exception
     */
    function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = ''): void
    {
        // Put an image on the page
        if ($file == '')
            $this->Error('Image file name is empty');
        if (!isset($this->images[$file])) {
            // First use of this image, get info
            if ($type == '') {
                $pos = strrpos($file, '.');
                if (!$pos)
                    $this->Error('Image file has no extension and no type was specified: ' . $file);
                $type = substr($file, $pos + 1);
            }
            $type = strtolower($type);
            if ($type == 'jpeg')
                $type = 'jpg';
            $mtd = '_parse' . $type;
            if (!method_exists($this, $mtd))
                $this->Error('Unsupported image type: ' . $type);


            $info = $this->$mtd($file);

            $info['i'] = count($this->images) + 1;
            $this->images[$file] = $info;
        } else
            $info = $this->images[$file];

        // Automatic width and height calculation if needed
        if ($w == 0 && $h == 0) {
            // Put image at 96 dpi
            $w = -96;
            $h = -96;
        }
        if ($w < 0)
            $w = -$info['w'] * 72 / $w / $this->k;
        if ($h < 0)
            $h = -$info['h'] * 72 / $h / $this->k;
        if ($w == 0)
            $w = $h * $info['w'] / $info['h'];
        if ($h == 0)
            $h = $w * $info['h'] / $info['w'];

        // Flowing mode
        if ($y === null) {
            if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
                // Automatic page break
                $x2 = $this->x;
                $this->AddPage($this->CurOrientation, $this->CurPageSize, $this->CurRotation);
                $this->x = $x2;
            }
            $y = $this->y;
            $this->y += $h;
        }

        if ($x === null)
            $x = $this->x;
        $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q', $w * $this->k, $h * $this->k, $x * $this->k, ($this->h - ($y + $h)) * $this->k, $info['i']));
        if ($link)
            $this->Link($x, $y, $w, $h, $link);
    }

    function GetPageWidth()
    {
        // Get current page width
        return $this->w;
    }

    function GetPageHeight()
    {
        // Get current page height
        return $this->h;
    }

    function SetXY($x, $y): void
    {
        // Set x and y positions
        $this->SetX($x);
        $this->SetY($y, false);
    }

    function SetY($y, $resetX = true): void
    {
        // Set y position and optionally reset x
        if ($y >= 0)
            $this->y = $y;
        else
            $this->y = $this->h + $y;
        if ($resetX)
            $this->x = $this->lMargin;
    }

    /**
     * @throws Exception
     */
    function Output($dest = '', $name = '', $isUTF8 = false): string
    {
        // Output PDF to some destination
        $this->Close();
        if (strlen($name) == 1 && strlen($dest) != 1) {
            // Fix parameter order
            $tmp = $dest;
            $dest = $name;
            $name = $tmp;
        }
        if ($dest == '')
            $dest = 'I';
        if ($name == '')
            $name = 'doc.pdf';
        switch (strtoupper($dest)) {
            case 'I':
                // Send to standard output
                $this->_checkoutput();
                if (PHP_SAPI != 'cli') {
                    // We send to a browser
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; ' . $this->_httpencode('filename', $name, $isUTF8));
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                }
                echo $this->buffer;
                break;
            case 'D':
                // Download file
                $this->_checkoutput();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; ' . $this->_httpencode('filename', $name, $isUTF8));
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $this->buffer;
                break;
            case 'F':
                // Save to local file
                if (!file_put_contents($name, $this->buffer))
                    $this->Error('Unable to create output file: ' . $name);
                break;
            case 'S':
                // Return as a string
                return $this->buffer;
            default:
                $this->Error('Incorrect output destination: ' . $dest);
        }
        return '';
    }

    /**
     * @throws Exception
     */
    function Close(): void
    {
        // Terminate document
        if ($this->state == 3)
            return;
        if ($this->page == 0)
            $this->AddPage();
        // Page footer
        $this->InFooter = true;
        $this->appFooter();
        $this->InFooter = false;
        // Close page
        $this->_endpage();
        // Close document
        $this->_enddoc();
    }

    /**
     * @throws Exception
     */
    protected function _enddoc()
    {
        $this->CreationDate = time();
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        // Info
        $this->_newobj();
        $this->_put('<<');
        $this->_putinfo();
        $this->_put('>>');
        $this->_put('endobj');
        // Catalog
        $this->_newobj();
        $this->_put('<<');
        $this->_putcatalog();
        $this->_put('>>');
        $this->_put('endobj');
        // Cross-ref
        $offset = $this->_getoffset();
        $this->_put('xref');
        $this->_put('0 ' . ($this->n + 1));
        $this->_put('0000000000 65535 f ');
        for ($i = 1; $i <= $this->n; $i++)
            $this->_put(sprintf('%010d 00000 n ', $this->offsets[$i]));
        // Trailer
        $this->_put('trailer');
        $this->_put('<<');
        $this->_puttrailer();
        $this->_put('>>');
        $this->_put('startxref');
        $this->_put($offset);
        $this->_put('%%EOF');
        $this->state = 3;
    }

    protected function _putheader(): void
    {
        $this->_put('%PDF-' . $this->PDFVersion);
    }

    protected function _putpages(): void
    {
        $nb = $this->page;
        $n = $this->n;
        for ($i = 1; $i <= $nb; $i++) {
            $this->PageInfo[$i]['n'] = ++$n;
            $n++;
            foreach ($this->PageLinks[$i] as &$pl)
                $pl[5] = ++$n;
            unset($pl);
        }
        for ($i = 1; $i <= $nb; $i++)
            $this->_putpage($i);
        // Pages root
        $this->_newobj(1);
        $this->_put('<</Type /Pages');
        $kids = '/Kids [';
        for ($i = 1; $i <= $nb; $i++)
            $kids .= $this->PageInfo[$i]['n'] . ' 0 R ';
        $kids .= ']';
        $this->_put($kids);
        $this->_put('/Count ' . $nb);
        if ($this->DefOrientation == 'P') {
            $w = $this->DefPageSize[0];
            $h = $this->DefPageSize[1];
        } else {
            $w = $this->DefPageSize[1];
            $h = $this->DefPageSize[0];
        }
        $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]', $w * $this->k, $h * $this->k));
        $this->_put('>>');
        $this->_put('endobj');
    }

    protected function _putpage($n): void
    {
        $this->_newobj();
        $this->_put('<</Type /Page');
        $this->_put('/Parent 1 0 R');
        if (isset($this->PageInfo[$n]['size']))
            $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->PageInfo[$n]['size'][0], $this->PageInfo[$n]['size'][1]));
        if (isset($this->PageInfo[$n]['rotation']))
            $this->_put('/Rotate ' . $this->PageInfo[$n]['rotation']);
        $this->_put('/Resources 2 0 R');
        if (!empty($this->PageLinks[$n])) {
            $s = '/Annots [';
            foreach ($this->PageLinks[$n] as $pl)
                $s .= $pl[5] . ' 0 R ';
            $s .= ']';
            $this->_put($s);
        }
        if ($this->WithAlpha)
            $this->_put('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
        $this->_put('/Contents ' . ($this->n + 1) . ' 0 R>>');
        $this->_put('endobj');
        // Page content
        if (!empty($this->AliasNbPages)) {
            $alias = $this->UTF8ToUTF16BE($this->AliasNbPages, false);
            $r = $this->UTF8ToUTF16BE($this->page, false);
            $this->pages[$n] = str_replace($alias, $r, $this->pages[$n]);
            // Now repeat for no pages in non-subset fonts
            $this->pages[$n] = str_replace($this->AliasNbPages, $this->page, $this->pages[$n]);
        }
        $this->_putstreamobject($this->pages[$n]);
        // Link annotations
        $this->_putlinks($n);
    }

    protected function _newobj($n = null): void
    {
        // Begin a new object
        if ($n === null)
            $n = ++$this->n;
        $this->offsets[$n] = $this->_getoffset();
        $this->_put($n . ' 0 obj');
    }

    protected function _getoffset(): int
    {
        return strlen($this->buffer);
    }

    protected function _putstreamobject($data): void
    {
        if ($this->compress) {
            $entries = '/Filter /FlateDecode ';
            $data = gzcompress($data);
        } else
            $entries = '';
        $entries .= '/Length ' . strlen($data);
        $this->_newobj();
        $this->_put('<<' . $entries . '>>');
        $this->_putstream($data);
        $this->_put('endobj');
    }

    protected function _putstream($data): void
    {
        $this->_put('stream');
        $this->_put($data);
        $this->_put('endstream');
    }

    protected function _putlinks($n): void
    {
        foreach ($this->PageLinks[$n] as $pl) {
            $this->_newobj();
            $rect = sprintf('%.2F %.2F %.2F %.2F', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);
            $s = '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';
            if (is_string($pl[4]))
                $s .= '/A <</S /URI /URI ' . $this->_textstring($pl[4]) . '>>>>';
            else {
                $l = $this->links[$pl[4]];
                if (isset($this->PageInfo[$l[0]]['size']))
                    $h = $this->PageInfo[$l[0]]['size'][1];
                else
                    $h = ($this->DefOrientation == 'P') ? $this->DefPageSize[1] * $this->k : $this->DefPageSize[0] * $this->k;
                $s .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>', $this->PageInfo[$l[0]]['n'], $h - $l[1] * $this->k);
            }
            $this->_put($s);
            $this->_put('endobj');
        }
    }

    protected function _textstring($s): string
    {
        // Format a text string
        if (!$this->_isascii($s))
            $s = $this->_UTF8toUTF16($s);
        return '(' . $this->_escape($s) . ')';
    }

    protected function _isascii($s): bool
    {
        // Test if string is ASCII
        $nb = strlen($s);
        for ($i = 0; $i < $nb; $i++) {
            if (ord($s[$i]) > 127)
                return false;
        }
        return true;
    }

    protected function _UTF8toUTF16($s): string
    {
        // Convert UTF-8 to UTF-16BE with BOM
        return "\xFE\xFF" . mb_convert_encoding($s, 'UTF-16BE', 'UTF-8');
    }

    /**
     * @throws Exception
     */
    protected function _putresources(): void
    {
        $this->_putfonts();
        $this->_putimages();
        // Resource dictionary
        $this->_newobj(2);
        $this->_put('<<');
        $this->_putresourcedict();
        $this->_put('>>');
        $this->_put('endobj');
    }

    /**
     * @throws Exception
     */
    protected function _putfonts(): void
    {
        foreach ($this->FontFiles as $file => $info) {
            if (!isset($info['type']) || $info['type'] != 'TTF') {
                // Font file embedding
                $this->_newobj();
                $this->FontFiles[$file]['n'] = $this->n;
                $font = file_get_contents($this->fontpath . $file, true);
                if (!$font)
                    $this->Error('Font file not found: ' . $file);
                $compressed = (str_ends_with($file, '.z'));
                if (!$compressed && isset($info['length2']))
                    $font = substr($font, 6, $info['length1']) . substr($font, 6 + $info['length1'] + 6, $info['length2']);
                $this->_put('<</Length ' . strlen($font));
                if ($compressed)
                    $this->_put('/Filter /FlateDecode');
                $this->_put('/Length1 ' . $info['length1']);
                if (isset($info['length2']))
                    $this->_put('/Length2 ' . $info['length2'] . ' /Length3 0');
                $this->_put('>>');
                $this->_putstream($font);
                $this->_put('endobj');
            }
        }
        foreach ($this->fonts as $k => $font) {
            // Encoding
            if (isset($font['diff'])) {
                if (!isset($this->encodings[$font['enc']])) {
                    $this->_newobj();
                    $this->_put('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $font['diff'] . ']>>');
                    $this->_put('endobj');
                    $this->encodings[$font['enc']] = $this->n;
                }
            }
            // ToUnicode CMap
            if (isset($font['uv'])) {
                $cmapkey = $font['enc'] ?? $font['name'];
                if (!isset($this->cmaps[$cmapkey])) {
                    $cmap = $this->_tounicodecmap($font['uv']);
                    $this->_putstreamobject($cmap);
                    $this->cmaps[$cmapkey] = $this->n;
                }
            }
            // Font object
            $type = $font['type'];
            $name = $font['name'];
            if ($type == 'Core') {
                // Core font
                $this->fonts[$k]['n'] = $this->n + 1;
                $this->_newobj();
                $this->_put('<</Type /Font');
                $this->_put('/BaseFont /' . $name);
                $this->_put('/Subtype /Type1');
                if ($name != 'Symbol' && $name != 'ZapfDingbats')
                    $this->_put('/Encoding /WinAnsiEncoding');
                if (isset($font['uv']))
                    $this->_put('/ToUnicode ' . $this->cmaps[$cmapkey] . ' 0 R');
                $this->_put('>>');
                $this->_put('endobj');
            } elseif ($type == 'Type1' || $type == 'TrueType') {
                // Additional Type1 or TrueType/OpenType font
                if (isset($font['subsetted']) && $font['subsetted'])
                    $name = 'AAAAAA+' . $name;
                $this->fonts[$k]['n'] = $this->n + 1;
                $this->_newobj();
                $this->_put('<</Type /Font');
                $this->_put('/BaseFont /' . $name);
                $this->_put('/Subtype /' . $type);
                $this->_put('/FirstChar 32 /LastChar 255');
                $this->_put('/Widths ' . ($this->n + 1) . ' 0 R');
                $this->_put('/FontDescriptor ' . ($this->n + 2) . ' 0 R');

                if ($font['enc']) {
                    if (isset($font['diff']))
                        $this->_put('/Encoding ' . $this->encodings[$font['enc']] . ' 0 R');
                    else
                        $this->_put('/Encoding /WinAnsiEncoding');
                }

                if (isset($font['uv']))
                    $this->_put('/ToUnicode ' . $this->cmaps[$cmapkey] . ' 0 R');
                $this->_put('>>');
                $this->_put('endobj');
                // Widths
                $this->_newobj();
                $cw = $font['cw'];
                $s = '[';
                for ($i = 32; $i <= 255; $i++)
                    $s .= $cw[chr($i)] . ' ';
                $this->_put($s . ']');
                $this->_put('endobj');
                // Descriptor
                $this->_newobj();
                $s = '<</Type /FontDescriptor /FontName /' . $name;
                foreach ($font['desc'] as $kk => $v)
                    $s .= ' /' . $kk . ' ' . $v;

                if (!empty($font['file']))
                    $s .= ' /FontFile' . ($type == 'Type1' ? '' : '2') . ' ' . $this->FontFiles[$font['file']]['n'] . ' 0 R';
                $this->_put($s . '>>');
                $this->_put('endobj');
            } // TrueType embedded SUBSETS or FULL
            else if ($type == 'TTF') {
                $this->fonts[$k]['n'] = $this->n + 1;

                $ttf = new BaseTTFontFile();
                $fontname = 'MPDFAA' . '+' . $font['name'];
                $subset = $font['subset'];
                unset($subset[0]);
                $ttfontstream = $ttf->makeSubset($font['ttffile'], $subset);
                $ttfontsize = strlen($ttfontstream);
                $fontstream = gzcompress($ttfontstream);
                $codeToGlyph = $ttf->codeToGlyph;
                unset($codeToGlyph[0]);

                // Type0 Font
                // A composite font - a font composed of other fonts, organized hierarchically
                $this->_newobj();
                $this->_put('<</Type /Font');
                $this->_put('/Subtype /Type0');
                $this->_put('/BaseFont /' . $fontname . '');
                $this->_put('/Encoding /Identity-H');
                $this->_put('/DescendantFonts [' . ($this->n + 1) . ' 0 R]');
                $this->_put('/ToUnicode ' . ($this->n + 2) . ' 0 R');
                $this->_put('>>');
                $this->_put('endobj');

                // CIDFontType2
                // A CIDFont whose glyph descriptions are based on TrueType font technology
                $this->_newobj();
                $this->_put('<</Type /Font');
                $this->_put('/Subtype /CIDFontType2');
                $this->_put('/BaseFont /' . $fontname);
                $this->_put('/CIDSystemInfo ' . ($this->n + 2) . ' 0 R');
                $this->_put('/FontDescriptor ' . ($this->n + 3) . ' 0 R');
                if (isset($font['desc']['MissingWidth'])) {
                    $this->_out('/DW ' . $font['desc']['MissingWidth']);
                }

                $this->_putTTfontwidths($font, $ttf->maxUni);

                $this->_put('/CIDToGIDMap ' . ($this->n + 4) . ' 0 R');
                $this->_put('>>');
                $this->_put('endobj');

                // ToUnicode
                $this->_newobj();
                $toUni = "/CIDInit /ProcSet findresource begin\n";
                $toUni .= "12 dict begin\n";
                $toUni .= "begincmap\n";
                $toUni .= "/CIDSystemInfo\n";
                $toUni .= "<</Registry (Adobe)\n";
                $toUni .= "/Ordering (UCS)\n";
                $toUni .= "/Supplement 0\n";
                $toUni .= ">> def\n";
                $toUni .= "/CMapName /Adobe-Identity-UCS def\n";
                $toUni .= "/CMapType 2 def\n";
                $toUni .= "1 begincodespacerange\n";
                $toUni .= "<0000> <FFFF>\n";
                $toUni .= "endcodespacerange\n";
                $toUni .= "1 beginbfrange\n";
                $toUni .= "<0000> <FFFF> <0000>\n";
                $toUni .= "endbfrange\n";
                $toUni .= "endcmap\n";
                $toUni .= "CMapName currentdict /CMap defineresource pop\n";
                $toUni .= "end\n";
                $toUni .= "end";
                $this->_put('<</Length ' . (strlen($toUni)) . '>>');
                $this->_putstream($toUni);
                $this->_put('endobj');

                // CIDSystemInfo dictionary
                $this->_newobj();
                $this->_put('<</Registry (Adobe)');
                $this->_put('/Ordering (UCS)');
                $this->_put('/Supplement 0');
                $this->_put('>>');
                $this->_put('endobj');

                // Font descriptor
                $this->_newobj();
                $this->_put('<</Type /FontDescriptor');
                $this->_put('/FontName /' . $fontname);
                foreach ($font['desc'] as $kd => $v) {
                    if ($kd == 'Flags') {
                        $v = $v | 4;
                        $v = $v & ~32;
                    }    // SYMBOLIC font flag
                    $this->_out(' /' . $kd . ' ' . $v);
                }
                $this->_put('/FontFile2 ' . ($this->n + 2) . ' 0 R');
                $this->_put('>>');
                $this->_put('endobj');

                // Embed CIDToGIDMap
                // A specification of the mapping from CIDs to glyph indices
                $cidtogidmap = str_pad('', 256 * 256 * 2, "\x00");

                foreach ($codeToGlyph as $cc => $glyph) {
                    $cidtogidmap[$cc * 2] = chr($glyph >> 8);
                    $cidtogidmap[$cc * 2 + 1] = chr($glyph & 0xFF);
                }
                $cidtogidmap = gzcompress($cidtogidmap);
                $this->_newobj();
                $this->_put('<</Length ' . strlen($cidtogidmap) . '');
                $this->_put('/Filter /FlateDecode');
                $this->_put('>>');
                $this->_putstream($cidtogidmap);
                $this->_put('endobj');

                //Font file
                $this->_newobj();
                $this->_put('<</Length ' . strlen($fontstream));
                $this->_put('/Filter /FlateDecode');
                $this->_put('/Length1 ' . $ttfontsize);
                $this->_put('>>');
                $this->_putstream($fontstream);
                $this->_put('endobj');
                unset($ttf);
            } else {
                // Allow for additional types
                $this->fonts[$k]['n'] = $this->n + 1;
                $mtd = '_put' . strtolower($type);
                if (!method_exists($this, $mtd))
                    $this->Error('Unsupported font type: ' . $type);
                $this->$mtd($font);
            }
        }
    }

    protected function _tounicodecmap($uv): string
    {
        $ranges = '';
        $nbr = 0;
        $chars = '';
        $nbc = 0;
        foreach ($uv as $c => $v) {
            if (is_array($v)) {
                $ranges .= sprintf("<%02X> <%02X> <%04X>\n", $c, $c + $v[1] - 1, $v[0]);
                $nbr++;
            } else {
                $chars .= sprintf("<%02X> <%04X>\n", $c, $v);
                $nbc++;
            }
        }
        $s = "/CIDInit /ProcSet findresource begin\n";
        $s .= "12 dict begin\n";
        $s .= "begincmap\n";
        $s .= "/CIDSystemInfo\n";
        $s .= "<</Registry (Adobe)\n";
        $s .= "/Ordering (UCS)\n";
        $s .= "/Supplement 0\n";
        $s .= ">> def\n";
        $s .= "/CMapName /Adobe-Identity-UCS def\n";
        $s .= "/CMapType 2 def\n";
        $s .= "1 begincodespacerange\n";
        $s .= "<00> <FF>\n";
        $s .= "endcodespacerange\n";
        if ($nbr > 0) {
            $s .= "$nbr beginbfrange\n";
            $s .= $ranges;
            $s .= "endbfrange\n";
        }
        if ($nbc > 0) {
            $s .= "$nbc beginbfchar\n";
            $s .= $chars;
            $s .= "endbfchar\n";
        }
        $s .= "endcmap\n";
        $s .= "CMapName currentdict /CMap defineresource pop\n";
        $s .= "end\n";
        $s .= "end";
        return $s;
    }

    /**
     * @throws Exception
     */
    protected function _putTTfontwidths($font, $maxUni): void
    {
        if (file_exists($font['unifilename'] . '.cw127.php')) {
            include($font['unifilename'] . '.cw127.php');
            $startcid = 128;
        } else {
            $rangeid = 0;
            $range = array();
            $prevcid = -2;
            $prevwidth = -1;
            $interval = false;
            $startcid = 1;
        }
        $cwlen = $maxUni + 1;

        // for each character
        for ($cid = $startcid; $cid < $cwlen; $cid++) {
            if ($cid == 128 && (!file_exists($font['unifilename'] . '.cw127.php'))) {
                if (is_writable(dirname($this->fontpath . 'unifont/x'))) {
                    $fh = fopen($font['unifilename'] . '.cw127.php', "wb");
                    $cw127 = '<?php' . "\n";
                    $cw127 .= '$rangeid=' . $rangeid . ";\n";
                    $cw127 .= '$prevcid=' . $prevcid . ";\n";
                    $cw127 .= '$prevwidth=' . $prevwidth . ";\n";
                    if ($interval) {
                        $cw127 .= '$interval=true' . ";\n";
                    } else {
                        $cw127 .= '$interval=false' . ";\n";
                    }
                    $cw127 .= '$range=' . var_export($range, true) . ";\n";
                    $cw127 .= "?>";
                    fwrite($fh, $cw127, strlen($cw127));
                    fclose($fh);
                }
            }
            if ((!isset($font['cw'][$cid * 2]) || !isset($font['cw'][$cid * 2 + 1])) ||
                ($font['cw'][$cid * 2] == "\00" && $font['cw'][$cid * 2 + 1] == "\00")) {
                continue;
            }

            $width = (ord($font['cw'][$cid * 2]) << 8) + ord($font['cw'][$cid * 2 + 1]);
            if ($width == 65535) {
                $width = 0;
            }
            if ($cid > 255 && (!isset($font['subset'][$cid]) || !$font['subset'][$cid])) {
                continue;
            }
            if (!isset($font['dw']) || (isset($font['dw']) && $width != $font['dw'])) {
                if ($cid == ($prevcid + 1)) {
                    if ($width == $prevwidth) {
                        if ($width == $range[$rangeid][0]) {
                            $range[$rangeid][] = $width;
                        } else {
                            array_pop($range[$rangeid]);
                            // new range
                            $rangeid = $prevcid;
                            $range[$rangeid] = array();
                            $range[$rangeid][] = $prevwidth;
                            $range[$rangeid][] = $width;
                        }
                        $interval = true;
                        $range[$rangeid]['interval'] = true;
                    } else {
                        if ($interval) {
                            // new range
                            $rangeid = $cid;
                            $range[$rangeid] = array();
                            $range[$rangeid][] = $width;
                        } else {
                            $range[$rangeid][] = $width;
                        }
                        $interval = false;
                    }
                } else {
                    $rangeid = $cid;
                    $range[$rangeid] = array();
                    $range[$rangeid][] = $width;
                    $interval = false;
                }
                $prevcid = $cid;
                $prevwidth = $width;
            }
        }
        $prevk = -1;
        $nextk = -1;
        $prevint = false;
        foreach ($range as $k => $ws) {
            $cws = count($ws);
            if (($k == $nextk) and (!$prevint) and ((!isset($ws['interval'])) or ($cws < 4))) {
                if (isset($range[$k]['interval'])) {
                    unset($range[$k]['interval']);
                }
                $range[$prevk] = array_merge($range[$prevk], $range[$k]);
                unset($range[$k]);
            } else {
                $prevk = $k;
            }
            $nextk = $k + $cws;
            if (isset($ws['interval'])) {
                if ($cws > 3) {
                    $prevint = true;
                } else {
                    $prevint = false;
                }
                unset($range[$k]['interval']);
                --$nextk;
            } else {
                $prevint = false;
            }
        }
        $w = '';
        foreach ($range as $k => $ws) {
            if (count(array_count_values($ws)) == 1) {
                $w .= ' ' . $k . ' ' . ($k + count($ws) - 1) . ' ' . $ws[0];
            } else {
                $w .= ' ' . $k . ' [ ' . implode(' ', $ws) . ' ]' . "\n";
            }
        }
        $this->_out('/W [' . $w . ' ]');
    }

    protected function _putimages(): void
    {
        foreach (array_keys($this->images) as $file) {
            $this->_putimage($this->images[$file]);
            unset($this->images[$file]['data']);
            unset($this->images[$file]['smask']);
        }
    }

    protected function _putimage(&$info): void
    {
        $this->_newobj();
        $info['n'] = $this->n;
        $this->_put('<</Type /XObject');
        $this->_put('/Subtype /Image');
        $this->_put('/Width ' . $info['w']);
        $this->_put('/Height ' . $info['h']);
        if ($info['cs'] == 'Indexed')
            $this->_put('/ColorSpace [/Indexed /DeviceRGB ' . (strlen($info['pal']) / 3 - 1) . ' ' . ($this->n + 1) . ' 0 R]');
        else {
            $this->_put('/ColorSpace /' . $info['cs']);
            if ($info['cs'] == 'DeviceCMYK')
                $this->_put('/Decode [1 0 1 0 1 0 1 0]');
        }
        $this->_put('/BitsPerComponent ' . $info['bpc']);
        if (isset($info['f']))
            $this->_put('/Filter /' . $info['f']);
        if (isset($info['dp']))
            $this->_put('/DecodeParms <<' . $info['dp'] . '>>');
        if (isset($info['trns']) && is_array($info['trns'])) {
            $trns = '';
            for ($i = 0; $i < count($info['trns']); $i++)
                $trns .= $info['trns'][$i] . ' ' . $info['trns'][$i] . ' ';
            $this->_put('/Mask [' . $trns . ']');
        }
        if (isset($info['smask']))
            $this->_put('/SMask ' . ($this->n + 1) . ' 0 R');
        $this->_put('/Length ' . strlen($info['data']) . '>>');
        $this->_putstream($info['data']);
        $this->_put('endobj');
        // Soft mask
        if (isset($info['smask'])) {
            $dp = '/Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns ' . $info['w'];
            $smask = array('w' => $info['w'], 'h' => $info['h'], 'cs' => 'DeviceGray', 'bpc' => 8, 'f' => $info['f'], 'dp' => $dp, 'data' => $info['smask']);
            $this->_putimage($smask);
        }
        // Palette
        if ($info['cs'] == 'Indexed')
            $this->_putstreamobject($info['pal']);
    }

    protected function _putresourcedict(): void
    {
        $this->_put('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_put('/Font <<');
        foreach ($this->fonts as $font)
            $this->_put('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
        $this->_put('>>');
        $this->_put('/XObject <<');
        $this->_putxobjectdict();
        $this->_put('>>');
    }

    protected function _putxobjectdict(): void
    {
        foreach ($this->images as $image)
            $this->_put('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
    }

    protected function _putinfo(): void
    {
        $date = @date('YmdHisO', $this->CreationDate);
        $this->metadata['CreationDate'] = 'D:' . substr($date, 0, -2) . "'" . substr($date, -2) . "'";
        foreach ($this->metadata as $key => $value)
            $this->_put('/' . $key . ' ' . $this->_textstring($value));
    }

    protected function _putcatalog(): void
    {
        $n = $this->PageInfo[1]['n'];
        $this->_put('/Type /Catalog');
        $this->_put('/Pages 1 0 R');
        if ($this->ZoomMode == 'fullpage')
            $this->_put('/OpenAction [' . $n . ' 0 R /Fit]');
        elseif ($this->ZoomMode == 'fullwidth')
            $this->_put('/OpenAction [' . $n . ' 0 R /FitH null]');
        elseif ($this->ZoomMode == 'real')
            $this->_put('/OpenAction [' . $n . ' 0 R /XYZ null null 1]');
        elseif (!is_string($this->ZoomMode))
            $this->_put('/OpenAction [' . $n . ' 0 R /XYZ null null ' . sprintf('%.2F', $this->ZoomMode / 100) . ']');
        if ($this->LayoutMode == 'single')
            $this->_put('/PageLayout /SinglePage');
        elseif ($this->LayoutMode == 'continuous')
            $this->_put('/PageLayout /OneColumn');
        elseif ($this->LayoutMode == 'two')
            $this->_put('/PageLayout /TwoColumnLeft');
    }

    protected function _puttrailer(): void
    {
        $this->_put('/Size ' . ($this->n + 1));
        $this->_put('/Root ' . $this->n . ' 0 R');
        $this->_put('/Info ' . ($this->n - 1) . ' 0 R');
    }

    /**
     * @throws Exception
     */
    protected function _checkoutput(): void
    {
        if (PHP_SAPI != 'cli') {
            if (headers_sent($file, $line))
                $this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
        }
        if (ob_get_length()) {
            // The output buffer is not empty
            if (preg_match('/^(\xEF\xBB\xBF)?\s*$/', ob_get_contents())) {
                // It contains only a UTF-8 BOM and/or whitespace, let's clean it
                ob_clean();
            } else
                $this->Error("Some data has already been output, can't send PDF file");
        }
    }

    protected function _httpencode($param, $value, $isUTF8): string
    {
        // Encode HTTP header field parameter
        if ($this->_isascii($value))
            return $param . '="' . $value . '"';
        if (!$isUTF8)
            $value = $this->_UTF8encode($value);
        return $param . "*=UTF-8''" . rawurlencode($value);
    }

    /**
     * @return array|array[]
     * @throws Exception
     */
    protected function getDocumentProperty(): array
    {

        $size = $this->_getpagesize($this->CurPageSize);
        $data = [];

        if (!empty($size)) {

            $data = [
                "normal" => [
                    "width" => round($size[0], 2),
                    "height" => round($size[1], 2)
                ],
                "margin" => [
                    "width" => round($size[0] - ($this->lMargin + $this->rMargin), 2),
                    "height" => round($size[1] - ($this->tMargin), 2)
                ],
                "actual" => [
                    "y" => $this->GetY(),
                    "x" => $this->GetX()
                ]
            ];

        }

        return $data;
    }

    function GetY()
    {
        // Get y position
        return $this->y;
    }

    function GetX()
    {
        // Get x position
        return $this->x;
    }

    function SetX($x): void
    {
        // Set x position
        if ($x >= 0)
            $this->x = $x;
        else
            $this->x = $this->w + $x;
    }

    /**
     * @param string|int|float|NULL $val
     * @return float
     */
    protected function _toMM(string|int|float $val = NULL): float
    {
        return ((float)$val * 25.4) / 96;
    }

    /**
     * @throws Exception
     */
    protected function _parsejpg($file): array
    {
        // Extract info from a JPEG file
        $a = getimagesize($file);
        if (!$a)
            $this->Error('Missing or incorrect image file: ' . $file);
        if ($a[2] != 2)
            $this->Error('Not a JPEG file: ' . $file);
        if (!isset($a['channels']) || $a['channels'] == 3)
            $colspace = 'DeviceRGB';
        elseif ($a['channels'] == 4)
            $colspace = 'DeviceCMYK';
        else
            $colspace = 'DeviceGray';
        $bpc = $a['bits'] ?? 8;
        $data = file_get_contents($file);
        return array('w' => $a[0], 'h' => $a[1], 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'DCTDecode', 'data' => $data);
    }

    /**
     * @param $file
     * @return array
     * @throws Exception
     */
    protected function _parsewebp($file): array
    {

        $save = null;

        try {

            $img = imagecreatefromwebp(filename: $file);

            if($img instanceof \GdImage) {

                if(!is_dir(filename: "./storage/0/cache/pdf")) {

                    mkdir(directory: "./storage/0/cache/pdf", permissions: 0777, recursive: true);
                }

                $save = sprintf("./storage/0/cache/pdf/%s.png", pathinfo(path: $file, flags: PATHINFO_FILENAME));

                imagepng(image: $img, file: $save);
                imagealphablending(image: $img, enable: true);
                imagesavealpha(image: $img, enable: true);
                imagedestroy(image: $img);

                return $this->_parsepng($save);

            }

        } catch (\Exception) {


        }

        return [];
    }

    /**
     * @throws Exception
     */
    protected function _parsepng($file): array
    {
        // Extract info from a PNG file
        $f = fopen($file, 'rb');
        if (!$f)
            $this->Error('Can\'t open image file: ' . $file);
        $info = $this->_parsepngstream($f, $file);
        fclose($f);
        return $info;
    }

    /**
     * @throws Exception
     */
    protected function _parsepngstream($f, $file): array
    {
        // Check signature
        if ($this->_readstream($f, 8) != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10))
            $this->Error('Not a PNG file: ' . $file);

        // Read header chunk
        $this->_readstream($f, 4);
        if ($this->_readstream($f, 4) != 'IHDR')
            $this->Error('Incorrect PNG file: ' . $file);
        $w = $this->_readint($f);
        $h = $this->_readint($f);
        $bpc = ord($this->_readstream($f, 1));
        if ($bpc > 8)
            $this->Error('16-bit depth not supported: ' . $file);
        $ct = ord($this->_readstream($f, 1));
        if ($ct == 0 || $ct == 4)
            $colspace = 'DeviceGray';
        elseif ($ct == 2 || $ct == 6)
            $colspace = 'DeviceRGB';
        elseif ($ct == 3)
            $colspace = 'Indexed';
        else
            $this->Error('Unknown color type: ' . $file);
        if (ord($this->_readstream($f, 1)) != 0)
            $this->Error('Unknown compression method: ' . $file);
        if (ord($this->_readstream($f, 1)) != 0)
            $this->Error('Unknown filter method: ' . $file);
        if (ord($this->_readstream($f, 1)) != 0)
            $this->Error('Interlacing not supported: ' . $file);
        $this->_readstream($f, 4);
        $dp = '/Predictor 15 /Colors ' . ($colspace == 'DeviceRGB' ? 3 : 1) . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $w;

        // Scan chunks looking for palette, transparency and image data
        $pal = '';
        $trns = '';
        $data = '';
        do {
            $n = $this->_readint($f);
            $type = $this->_readstream($f, 4);
            if ($type == 'PLTE') {
                // Read palette
                $pal = $this->_readstream($f, $n);
                $this->_readstream($f, 4);
            } elseif ($type == 'tRNS') {
                // Read transparency info
                $t = $this->_readstream($f, $n);
                if ($ct == 0)
                    $trns = array(ord(substr($t, 1, 1)));
                elseif ($ct == 2)
                    $trns = array(ord(substr($t, 1, 1)), ord(substr($t, 3, 1)), ord(substr($t, 5, 1)));
                else {
                    $pos = strpos($t, chr(0));
                    if ($pos !== false)
                        $trns = array($pos);
                }
                $this->_readstream($f, 4);
            } elseif ($type == 'IDAT') {
                // Read image data block
                $data .= $this->_readstream($f, $n);
                $this->_readstream($f, 4);
            } elseif ($type == 'IEND')
                break;
            else
                $this->_readstream($f, $n + 4);
        } while ($n);

        if ($colspace == 'Indexed' && empty($pal))
            $this->Error('Missing palette in ' . $file);
        $info = array('w' => $w, 'h' => $h, 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'FlateDecode', 'dp' => $dp, 'pal' => $pal, 'trns' => $trns);
        if ($ct >= 4) {
            // Extract alpha channel
            if (!function_exists('gzuncompress'))
                $this->Error('Zlib not available, can\'t handle alpha channel: ' . $file);
            $data = gzuncompress($data);
            $color = '';
            $alpha = '';
            if ($ct == 4) {
                // Gray image
                $len = 2 * $w;
                for ($i = 0; $i < $h; $i++) {
                    $pos = (1 + $len) * $i;
                    $color .= $data[$pos];
                    $alpha .= $data[$pos];
                    $line = substr($data, $pos + 1, $len);
                    $color .= preg_replace('/(.)./s', '$1', $line);
                    $alpha .= preg_replace('/.(.)/s', '$1', $line);
                }
            } else {
                // RGB image
                $len = 4 * $w;
                for ($i = 0; $i < $h; $i++) {
                    $pos = (1 + $len) * $i;
                    $color .= $data[$pos];
                    $alpha .= $data[$pos];
                    $line = substr($data, $pos + 1, $len);
                    $color .= preg_replace('/(.{3})./s', '$1', $line);
                    $alpha .= preg_replace('/.{3}(.)/s', '$1', $line);
                }
            }
            unset($data);
            $data = gzcompress($color);
            $info['smask'] = gzcompress($alpha);
            $this->WithAlpha = true;
            if ($this->PDFVersion < '1.4')
                $this->PDFVersion = '1.4';
        }
        $info['data'] = $data;
        return $info;
    }

    /**
     * @throws Exception
     */
    protected function _readstream($f, $n): string
    {
        // Read n bytes from stream
        $res = '';
        while ($n > 0 && !feof($f)) {
            $s = fread($f, $n);
            if ($s === false)
                $this->Error('Error while reading stream');
            $n -= strlen($s);
            $res .= $s;
        }
        if ($n > 0)
            $this->Error('Unexpected end of stream');
        return $res;
    }

// ********* NEW FUNCTIONS *********
// Converts UTF-8 strings to UTF16-BE.

    /**
     * @throws Exception
     */
    protected function _readint($f)
    {
        // Read a 4-byte integer from stream
        $a = unpack('Ni', $this->_readstream($f, 4));
        return $a['i'];
    }

    /**
     * @throws Exception
     */
    protected function _parsegif($file): array
    {
        // Extract info from a GIF file (via PNG conversion)
        if (!function_exists('imagepng'))
            $this->Error('GD extension is required for GIF support');
        if (!function_exists('imagecreatefromgif'))
            $this->Error('GD has no GIF read support');
        $im = imagecreatefromgif($file);
        if (!$im)
            $this->Error('Missing or incorrect image file: ' . $file);
        imageinterlace($im, 0);
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);
        $f = fopen('php://temp', 'rb+');
        if (!$f)
            $this->Error('Unable to create memory stream');
        fwrite($f, $data);
        rewind($f);
        $info = $this->_parsepngstream($f, $file);
        fclose($f);
        return $info;
    }


    /**
     * @param string $imgPath
     * @param int|float $x
     * @param int|float $y
     * @param int|float $containerWidth
     * @param int|float $containerHeight
     * @param string $alignment
     * @return void
     */
    public function placeImage(string $imgPath, int|float $x = 0, int|float $y = 0, int|float $containerWidth = 210, int|float $containerHeight = 210, string $alignment = 'C')
    {
        try {

            list($width, $height) = $this->resizeToFit(imgPath: $imgPath, maxWidth: $containerWidth, maxHeight: $containerHeight);

            match ($alignment) {

                default => $this->Image(file: $imgPath, x: $x, y: $y, w: $width, h: $height),
                "R" => $this->Image(file: $imgPath, x: $x+$containerWidth-$width, y: $y+($containerHeight-$height)/2, w: $width, h: $height),
                "B" => $this->Image(file: $imgPath, x: $x, y: $y+$containerHeight-$height, w: $width, h: $height),
                "C" => $this->Image(file: $imgPath, x: $x+($containerWidth-$width)/2, y: $y+($containerHeight-$height)/2, w: $width, h: $height),

            };

        } catch (Exception) {


        }

    }

    /**
     * @param int|float $val
     * @return int
     */
    protected function pixelsToMm(int|float $val): int
    {

        return (int) (round(($val * $this::MM_IN_INCH / $this::DPI)));
    }

    /**
     * @param int|float $val
     * @return int
     */
    protected function mmToPixels(int|float $val) : int
    {

        return (int) (round(($this::DPI * $val / $this::MM_IN_INCH)));
    }


    /**
     * @param string $imgPath
     * @param int|float $maxWidth
     * @param int|float $maxHeight
     * @return int[]
     */
    protected function resizeToFit(string $imgPath, int|float $maxWidth = 210, int|float $maxHeight = 297) : array
    {

        list($width, $height) = getimagesize($imgPath);

        $widthScale = $this->mmtopixels($maxWidth) / $width;
        $heightScale = $this->mmToPixels($maxHeight) / $height;
        $scale = min($widthScale, $heightScale);

        return [
            $this->pixelsToMM($scale * $width),
            $this->pixelsToMM($scale * $height)
        ];
    }

}