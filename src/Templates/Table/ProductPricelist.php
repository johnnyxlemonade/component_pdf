<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Table;

use Lemonade\Pdf\Templates\OutputTable;
use Lemonade\Pdf\Data\{Schema, Table};
use Lemonade\Pdf\Renderers\{Color, PDFRenderer, PdfRendererInterface, Settings};
use Lemonade\Pdf\Templates\TableInterface;
use Nette\Utils\Strings;

/**
 * @ProductPricelist
 * @\Lemonade\Pdf\Templates\Table\ProductPricelist
 */
class ProductPricelist extends OutputTable
{

    /**
     * Velikost popisku
     * @var integer
     */
    private int $fontSizeText = 8;

    /**
     * Vlevo okraj
     * @var integer
     */
    private int $bodyLeft = 150;

    /**
     * Osazeni obsah
     * @var integer
     */
    private int $itemLeft = 25;

    /**
     * Radek
     * @var integer
     */
    private int $itemLine = 30;

    /**
     * Translator
     * @var array|string[]
     */
    private array $translator = [
        "catalog" => "Katalogové číslo",
        "brand" => "Značka"
    ];

    /**
     * @param PDFRenderer $renderer
     * @param Table $meta
     * @param Table $data
     * @param Table|null $property
     * @return string
     */
    public function generateOutput(PDFRenderer $renderer, Table $meta, Table $data, Table $property = null): string
    {
        
        $this->renderer = $renderer;
        $this->meta = $meta;
        $this->property = $property;
        $this->data = $data;


        $this->renderer->createNew();
        $this->renderer->setDocumentMeta(data:
            [
                "title" => $this->meta->getConfig(index: "title"),
                "subject" => $this->meta->getConfig(index: "subject"),
                "author" => "core1.agency"
            ]
        );

        // fonty
        $this->renderer->registerDefaultFont();

        $this->_displayHeader();
        $this->_displayContent();
        
        return $this->renderer->output();
    }

    /**
     * @return void
     */
    protected function _displayContent(): void
    {

        if($this->property instanceof Table) {

            foreach ($this->property->getData() as $key => $property) {

                // dalsi strana
                if($this->renderer->isEndPage()) {

                    $this->renderer->addPage();
                    $this->_displayHeader();
                    $this->_displayFooter();
                }


                // nazevProduktu
                $productName = $property->productName;
                $offset = $this->renderer->y();

                if(Strings::length(s: $productName) > 80) {
                    $productName  = Strings::substring($productName . "...", 0, 80);
                }

                if($key % 2 === 1) {

                    // productCode
                    $this->renderer->cell(x: $this->itemLeft, y: $offset, text: $property->productCode, height: $this->itemLine, width: 60, setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_CENTER;
                            $settings->fontFamily = "sans";
                            $settings->border = "LRTB";
                            $settings->fill = $settings::FILL;
                            $settings->fillColor = $this->lineColor;
                            $settings->drawColor = $this->lineColor;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                            $settings->fontColor = $this->fontColor;
                        });

                    // productName
                    $this->renderer->cell(x: 85, y: $offset, width: 520, height: $this->itemLine, text: $productName, setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_LEFT;
                            $settings->fontFamily = "sans";
                            $settings->border = "LRTB";
                            $settings->fill = $settings::FILL;
                            $settings->fillColor = $this->lineColor;
                            $settings->drawColor = $this->lineColor;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->fontColor = $this->fontColor;
                        });

                    // productPriceBase
                    $this->renderer->cell(x: 605, y: $offset, text: $property->productPriceBase, height: $this->itemLine, width: 80, setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_RIGHT;
                            $settings->fontFamily = "sans";
                            $settings->border = "LRTB";
                            $settings->fill = $settings::FILL;
                            $settings->fillColor = $this->lineColor;
                            $settings->drawColor = $this->lineColor;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->fontColor = $this->fontColor;
                        });

                    // productPriceUnit
                    $this->renderer->cell(x: 685, y: $offset, text: $property->productPriceUnit, height: $this->itemLine, width: 80, setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_RIGHT;
                            $settings->fontFamily = "sans";
                            $settings->border = "RTB";
                            $settings->fill = $settings::FILL;
                            $settings->fillColor = $this->lineColor;
                            $settings->drawColor = $this->lineColor;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->fontColor = $this->fontColor;
                        });

                } else {

                    // productCode
                    $this->renderer->cell(x: $this->itemLeft, y: $offset, text: $property->productCode, height: $this->itemLine, width: 60, setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_CENTER;
                            $settings->fontFamily = "sans";
                            $settings->border = "LRTB";
                            $settings->fill = $settings::NO_FILL;
                            $settings->fillColor = $this->lineColor;
                            $settings->drawColor = $this->lineColor;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                            $settings->fontColor = $this->fontColor;
                        });

                    // productName
                    $this->renderer->cell(x: 85, y: $offset, width: 520, height: $this->itemLine, text: $productName, setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_LEFT;
                            $settings->fontFamily = "sans";
                            $settings->border = "LRTB";
                            $settings->fill = $settings::NO_FILL;
                            $settings->fillColor = $this->lineColor;
                            $settings->drawColor = $this->lineColor;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->fontColor = $this->fontColor;
                        });


                    // productPriceBase
                    $this->renderer->cell(x: 605, y: $offset, text: $property->productPriceBase, height: $this->itemLine, width: 80, setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_RIGHT;
                            $settings->fontFamily = "sans";
                            $settings->border = "LRTB";
                            $settings->fill = $settings::NO_FILL;
                            $settings->fillColor = $this->lineColor;
                            $settings->drawColor = $this->lineColor;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->fontColor = $this->fontColor;
                        });

                    // productPriceUnit
                    $this->renderer->cell(x: 685, y: $offset, text: $property->productPriceUnit, height: $this->itemLine, width: 80, setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_RIGHT;
                            $settings->fontFamily = "sans";
                            $settings->border = "RTB";
                            $settings->fill = $settings::NO_FILL;
                            $settings->fillColor = $this->lineColor;
                            $settings->drawColor = $this->lineColor;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->fontColor = $this->fontColor;
                        });

                }

            }
        }

    }

    /**
     * @return void
     */
    protected function _displayHeader(): void
    {

        // barevny polygon
        $this->renderer->polygon(points:
            [
                0, 0,
                0, 100,
                $this->renderer->width(), 100,
                $this->renderer->height(), 0,
            ],
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor($this->primaryColor);
            }
        );

        // logo
        if($this->schema->hasLogoPath()) {

            $this->renderer->placeImage(imgPath: $this->schema->getLogoPath(), x: $this->renderer->x(), y: 0, containerWidth: round(269/4), containerHeight: 100);
        }

        // nazev
        $this->renderer->cell(x: $this->bodyLeft, y: 40, width: 600, height: null, text: $this->_getFromArray(index: "documentHead"), setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontSize = 16;
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });


        // popis
        $this->renderer->cell(x: $this->bodyLeft, y: 55, width: 600, height: 12, text: $this->_getFromArray(index: "documentName"), setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontSize = 10;
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
        });

        // barevny pruh
        $this->renderer->rect(x: 0, y: 100, width: $this->renderer->width(), height:  0.1, setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->lineColor);
        });


        // odradkovani
       $this->renderer->Ln(height: 40);

       $offset = $this->renderer->y();

        // productCode
        $this->renderer->cell(x: $this->itemLeft, y: $this->renderer->y(), text: "Kód", height: $this->itemLine, width: 80, setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fill = $settings::NO_FILL;
                $settings->fillColor = $this->lineColor;
                $settings->drawColor = $this->lineColor;
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontColor = $this->fontColor;
            });

        // productName
        $this->renderer->cell(x: 85, y: $offset, width: 520, height: $this->itemLine, text: "Produkt", setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fill = $settings::NO_FILL;
                $settings->fillColor = $this->lineColor;
                $settings->drawColor = $this->lineColor;
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontColor = $this->fontColor;
            });

        // productPriceBase
        $this->renderer->cell(x: 605, y: $offset, text: "bez DPH", height: $this->itemLine, width: 80, setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fill = $settings::NO_FILL;
                $settings->fillColor = $this->lineColor;
                $settings->drawColor = $this->lineColor;
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontColor = $this->fontColor;
            });

        // productPriceUnit
        $this->renderer->cell(x: 685, y: $offset, text: "s DPH", height: $this->itemLine, width: 80, setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fill = $settings::NO_FILL;
                $settings->fillColor = $this->lineColor;
                $settings->drawColor = $this->lineColor;
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontColor = $this->fontColor;
            });

        //$this->renderer->Ln();

    }

    /**
     * @return void
     */
    protected function _displayFooter(): void
    {

        $x = $this->renderer->x();
        $y = $this->renderer->y();

        // barevny pruh
        $this->renderer->rect(0, $this->renderer->height() - 20, $this->renderer->width(), 20,
            function (Settings $settings) {
                $settings->fillColor = $this->lineColor;
                $settings->drawColor = $this->lineColor;
            });

        // vlevo - vytisknuto
        $this->renderer->cell(15, -10, $this->renderer->width() - 10, null, sprintf("Vytisknuto %s", Date(format: "j. n. Y")),
            function (Settings $settings) {
                $settings->fontColor = Color::gray();
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 6;
            });

        // stred - author
        $this->renderer->link(15, -10, $this->renderer->width() - 10, null, $this->getAuthorName(), $this->getAuthorLink(),
            function (Settings $settings) {
                $settings->fontColor = Color::gray();
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontSize = 6;
            });

        // vpravo - stranky
        $this->renderer->cell(0, -10, $this->renderer->width() - 10, null, $this->getAuthorLink(),
            function (Settings $settings) {
                $settings->fontColor = Color::gray();
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontSize = 6;
            });

        $this->renderer->setX($x);
        $this->renderer->setY($y);
    }


}
