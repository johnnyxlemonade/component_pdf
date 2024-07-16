<?php declare(strict_types=1);

namespace Lemonade\Pdf\Renderers;
use Exception;

/**
 * PDFRenderer
 * \Lemonade\Pdf\Renderers\PDFRenderer
 */
final class PDFRenderer
{

    const ASSETS_PATH = __DIR__ . "/../../assets/";

    /**
     * @var array
     */
    protected array $_coreFont = [
        "sans" => [
            "OpenSans-Regular.php" => Settings::FONT_STYLE_NONE,
            "OpenSans-Semibold.php" => Settings::FONT_STYLE_BOLD,
            "OpenSans-Italic.php" => Settings::FONT_STYLE_ITALIC,
            "OpenSans-Semibolditalic.php" => Settings::FONT_STYLE_BOLD_ITALIC
        ]
    ];

    /**
     * @var PdfBuilder|null
     */
    protected ?PdfBuilder $pdf = null;

    /**
     * @var array
     */
    protected array $cache = [
        "family" => "sans",
        "size" => 15,
        "color" => null,
        "align" => Settings::ALIGN_JUSTIFY,
    ];

    /**
     * @return void
     */
    public function registerDefaultFont(): void
    {

        if($this->pdf instanceof PDFBuilder) {

            foreach($this->_coreFont as $family => $fonts) {
                foreach($fonts as $font => $style) {
                    $this->pdf->AddFont(family: $family, style: $style, file: $font);
                }
            }
        }

    }

    /**
     * @param int $x
     * @return void
     */
    public function setX(int $x): void
    {
        $this->pdf->SetX(x: $x);
    }

    /**
     * @return int
     */
    public function x(): int
    {

        return (int) $this->pdf->GetX();
    }

    /**
     * @param int $y
     * @return void
     */
    public function setY(int $y): void
    {
        $this->pdf->setY(y: $y);
    }

    /**
     * @return int
     */
    public function y(): int
    {

        return (int) $this->pdf->GetY();
    }

    /**
     * @param string $text
     * @param callable|null $setCallback
     * @return int
     */
    public function textWidth(string $text, callable $setCallback = null): int
    {

        $settings = $this->extractSettings(callback: $setCallback);
        $this->setFont(settings: $settings);

        return (int) $this->pdf->GetStringWidth(s: $text);
    }

    /**
     * @param callable|null $callback
     * @return Settings
     */
    protected function extractSettings(callable $callback = null): Settings
    {
        $settings = new Settings();

        if ($callback !== null) {

            $callback($settings);
        }

        return $settings;
    }

    /**
     * @param Settings $settings
     * @return void
     */
    protected function setFont(Settings $settings): void
    {

        if ($settings->fontFamily === null && $settings->fontSize === null && $settings->fontColor === null) {
            return;
        }

        $this->restoreSettings(settings: $settings, name: "fontFamily");
        $this->restoreSettings(settings: $settings, name: "fontSize");

        $this->pdf->SetFont(family: $settings->fontFamily, style: $settings->fontStyle, size: $settings->fontSize);

        if ($settings->fontColor) {

            $color = $settings->fontColor;
            $this->pdf->SetTextColor(r: $color->getRed(), g: $color->getGreen(), b: $color->getBlue());
        }
    }

    /**
     * @param Settings $settings
     * @param string $name
     * @return void
     */
    protected function restoreSettings(Settings $settings, string $name): void
    {
        if ($settings->$name === null) {

            $settings->$name = $this->cache[$name];

        } else {

            $this->cache[$name] = $settings->$name;
        }
    }

    /**
     * @return int
     */
    public function width(): int
    {

        return (int) $this->pdf->GetPageWidth();
    }

    /**
     * @return int
     */
    public function height(): int
    {

        return (int) $this->pdf->GetPageHeight();
    }

    /**
     * @return bool
     */
    public function isEndPage(): bool
    {

        return $this->pdf->GetY() > 1050;
    }


    /**
     * @return int
     */
    public function getCurrentPage(): int
    {

        return $this->pdf->getCurrentPage();
    }

    /**
     * @return void
     */
    public function createNew(): void
    {

        $this->pdf = new PdfBuilder(orientation: "P", unit:  "px",  size: "A4");
        $this->pdf->SetFontPath(fontPath: self::ASSETS_PATH);
        $this->pdf->SetAutoPageBreak(auto: false);

        $this->addPage();
    }

    /**
     * @return void
     */
    public function addPage(): void
    {

        $this->pdf->AddPage(orientation: "P", size: "A4");
    }

    /**
     * @return void
     */
    public function createLandscape(): void
    {

        $this->pdf = new PdfBuilder(orientation: "L", unit: "px", size: "A4");
        $this->pdf->SetFontPath(fontPath: self::ASSETS_PATH);
        $this->pdf->SetAutoPageBreak(auto: false);

        $this->addLanscapePage();
    }

    /**
     * @return void
     */
    public function addLanscapePage(): void
    {

        $this->pdf->AddPage(orientation: "L", size: "A4");
    }

    /**
     * @return PdfBuilder
     */
    public function getBuilder(): PdfBuilder
    {
        return $this->pdf;
    }

    /**
     * @param string $family
     * @param string $file
     * @param string $fontStyle
     * @return void
     */
    public function addFont(string $family, string $file, string $fontStyle = Settings::FONT_STYLE_NONE): void
    {

        $this->pdf->AddFont(family: $family, style: $fontStyle, file: $file);
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     * @param callable|null $setCallback
     * @return void
     */
    public function rect(float $x, float $y, float $width, float $height, callable $setCallback = null): void
    {

        $settings = $this->extractSettings(callback: $setCallback);

        $this->setDrawing(settings: $settings);

        $this->pdf->Rect(x: $x, y: $y, w: $width, h: $height, style: "DF");
    }

    /**
     * @param Settings $settings
     * @return void
     */
    protected function setDrawing(Settings $settings): void
    {

        if ($settings->drawColor !== null) {

            $color = $settings->drawColor;

            $this->pdf->SetDrawColor(r: $color->getRed(), g: $color->getGreen(), b: $color->getBlue());
        }

        if ($settings->fillColor !== null) {

            $color = $settings->fillColor;

            $this->pdf->SetFillColor(r: $color->getRed(), g: $color->getGreen(), b: $color->getBlue());
        }
    }

    /**
     * @param array $points
     * @param callable|null $setCallback
     * @return void
     */
    public function polygon(array $points, callable $setCallback = null): void
    {

        $settings = $this->extractSettings(callback: $setCallback);

        $this->setDrawing(settings: $settings);

        $this->pdf->Polygon(points: $points, style: "DF");
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float|null $height
     * @param string|null $text
     * @param string|null $link
     * @param callable|null $setCallback
     * @return void
     */
    public function link(float $x, float $y, float $width, float $height = null, string $text = null, string $link = null, callable $setCallback = null): void
    {

        $text = $this->_doConversionIconv(text: $text);

        $settings = $this->extractSettings(callback: $setCallback);
        $this->restoreSettings(settings: $settings, name: "align");

        $this->pdf->SetXY(x: $x, y: $y);
        $this->setFont(settings: $settings);

        if ($height) {

            $this->pdf->MultiCell(w: $width, h: $height, txt: $text, border: $settings->border, align: $settings->align, fill: $settings->fill);

        } else {

            $this->pdf->Cell(w: $width, h: 0, txt: $text, border: $settings->border, ln: 0, align: $settings->align, fill: $settings->fill, link: $link);
        }
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float|null $height
     * @param string|null $text
     * @param callable|null $setCallback
     * @return void
     */
    public function cell(float $x, float $y, float $width, float $height = null, string $text = null, callable $setCallback = null): void
    {

        $text = $this->_doConversionIconv(text: $text);

        $settings = $this->extractSettings(callback: $setCallback);
        $this->restoreSettings(settings: $settings, name: "align");

        $this->pdf->SetXY(x: $x, y: $y);
        $this->setFont(settings: $settings);

        if ($height) {

            $this->pdf->MultiCell(w: $width, h: $height, txt: $text, border: $settings->border, align: $settings->align, fill: $settings->fill);

        } else {

            $this->pdf->Cell(w: $width, h: 0, txt: $text, border: $settings->border, ln: 0, align: $settings->align, fill: $settings->fill);

        }
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

        $this->pdf->placeImage(imgPath: $imgPath, x: $x, y: $y, containerWidth: $containerWidth, containerHeight: $containerHeight, alignment: $alignment);
    }


    /**
     * @param string|int|float|null $height
     * @return void
     */
    public function Ln(string|int|float $height = null): void
    {

        $this->pdf->Ln(h: $height);
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float|null $height
     * @param string|null $text
     * @param callable|null $setCallback
     * @return void
     */
    public function cellFallback(float $x, float $y, float $width, float $height = null, string $text = null, callable $setCallback = null): void
    {

        $text = $this->_doConversionIconv(text: $text);

        $settings = $this->extractSettings(callback: $setCallback);
        $this->restoreSettings(settings: $settings, name: "align");

        $this->pdf->SetXY(x: $x, y: $y);
        $this->setFont(settings: $settings);

        $this->pdf->Cell(w: $width, h: $height, txt: $text, border: $settings->border, ln: 0, align: $settings->align, fill: $settings->fill);
    }


    /**
     * {@inheritDoc}
     * @see PdfRendererInterface::output
     */
    public function output(): string
    {

        return $this->pdf->Output(dest: "S");
    }

    /**
     * @return PdfBuilder
     */
    public function getSource(): PdfBuilder
    {

        return $this->pdf;
    }

    /**
     * @param string $logo
     * @param float|int $x
     * @param float|int $y
     * @param float|int|null $width
     * @param float|int|null $height
     * @return void
     */
    public function addImage(string $logo, float|int $x, float|int $y, float|int $width = null, float|int $height = null): void
    {

        try {

            $this->pdf->Image(file: $logo, x: $x, y: $y, w: $width, h: $height);

        } catch (Exception $e) {

        }

    }

    /**
     * @param string $background
     * @return void
     */
    public function addBackground(string $background): void
    {

        try {

            $this->pdf->placeImage(imgPath: $background, x: 0, y: 0, containerWidth: $this->width(), containerHeight: $this->height(), alignment: "FULLIMAGE");

        } catch (Exception $e) {

        }

    }

    /**
     * @param array $data
     * @return void
     */
    public function setDocumentMeta(array $data = []): void
    {

        $this->pdf->SetCreator(creator: $data["author"], isUTF8: true);
        $this->pdf->SetAuthor(author: $data["author"], isUTF8: true);
        $this->pdf->SetSubject(subject: $data["subject"], isUTF8: true);
        $this->pdf->SetTitle(title: $data["title"], isUTF8: true);
    }

    /**
     * @param $alpha
     * @return void
     */
    public function filterAlpha($alpha): void
    {

        $this->pdf->addFilterAplha(alpha: $alpha);
    }

    /**
     * @param string|null $text
     * @return false|string
     */
    protected function _doConversionIconv(string $text = null): bool|string
    {

        return iconv(from_encoding: "UTF-8", to_encoding: "WINDOWS-1250//TRANSLIT//IGNORE", string:  (string) $text);

    }

}
