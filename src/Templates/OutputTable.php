<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates;
use Lemonade\Pdf\Data\Schema;
use Lemonade\Pdf\Data\Table;
use Lemonade\Pdf\Renderers\Color;
use Lemonade\Pdf\Renderers\PDFRenderer;
use Lemonade\Pdf\Renderers\PdfRendererInterface;
use Lemonade\Pdf\Renderers\Settings;

abstract class OutputTable
{

    /**
     * @var PDFRenderer|null
     */
    protected ?PDFRenderer $renderer;

    /**
     * @var Table|null
     */
    protected ?Table $meta = null;

    /**
     * @var Table|null
     */
    protected ?Table $data = null;

    /**
     * @var Table|null
     */
    protected ?Table $property = null;

    /**
     * @var Color|null
     */
    protected ?Color $primaryColor;

    /**
     * @var Color|null
     */
    protected ?Color $fontColor;

    /**
     * @var Color|null
     */
    protected ?Color $lineColor;

    /**
     * @var Color|null
     */
    protected ?Color $whiteColor;

    /**
     * @var string
     */
    protected string $creatorLink = "https://core1.agency";

    /**
     * @var string
     */
    protected string $documentAuthor = "";

    /**
     * @var string
     */
    protected string $documentWeb = "";

    /**
     * @param Schema $schema
     */
    public function __construct(protected readonly Schema $schema = new Schema()) {

        $this->primaryColor = $this->schema->getPrimaryColor();
        $this->fontColor = $this->schema->getFontColor();
        $this->whiteColor = $this->schema->getWhiteColor();
        $this->lineColor = $this->schema->getLineColor();
    }

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthorName(string $author): static
    {

        $this->documentAuthor = $author;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorName(): string
    {

        return $this->documentAuthor;
    }

    /**
     * @param string $web
     * @return $this
     */
    public function setAuthorLink(string $web): static
    {

        $this->documentWeb = $web;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorLink(): string
    {

        return $this->documentWeb;
    }

    /**
     * @return void
     */
    protected function buildFooter(): void
    {

        $renderer = $this->renderer;

        // barevny pruh
        $renderer->rect(0, $renderer->height() - 20, $renderer->width(), 20,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->fontColor);
            });

        // vlevo - vytisknuto
        $renderer->cell(15, -10, $renderer->width() - 10, null, sprintf("Vytisknuto %s", Date(format: "j. n. Y")),
            function (Settings $settings) {
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 6;
            });

        // stred - author
        $renderer->link(15, -10, $renderer->width() - 10, null, $this->getAuthorName(), $this->getAuthorLink(),
            function (Settings $settings) {
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontSize = 6;
            });

        // vpravo - stranky
        $renderer->cell(0, -10, $renderer->width() - 10, null, $this->getAuthorLink(),
            function (Settings $settings) {
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontSize = 6;
            });
    }

    /**
     * @param string|null $index
     * @return string
     */
    protected function _getFromArray(string $index = null): string
    {

        if($this->data instanceof Table) {

            return $this->data->getConfig(index: $index);
        }

        return (string) $index;
    }

    /**
     * @param string|null $text
     * @return string
     */
    protected function _cleanText(string $text = null): string
    {

        $text = html_entity_decode(($text ?? ""));
        $text = trim(strip_tags($text));
        $text = trim(preg_replace('/\s\s+/', ' ', $text));

        return $text;
    }

    /**
     * @param PDFRenderer $renderer
     * @param Table $meta
     * @param Table $data
     * @param Table|null $property
     * @return string
     */
    abstract function generateOutput(PDFRenderer $renderer, Table $meta, Table $data, Table $property = null): string;
    
}
