<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Table;

use Lemonade\Pdf\Templates\OutputTable;
use Lemonade\Pdf\Data\{Schema, Table};
use Lemonade\Pdf\Renderers\{Color, PDFRenderer, PdfRendererInterface, Settings};
use Lemonade\Pdf\Templates\TableInterface;
use Nette\Utils\Strings;

/**
 * @ProductSheet
 * @\Lemonade\Pdf\Templates\Table\ProductSheet
 */
class ProductSheet extends OutputTable
{

    /**
     * Velikost popisku
     * @var integer
     */
    private int $fontSizeText = 8;

    /**
     * Velikost textu
     * @var integer
     */
    private int $fontSizeNormal = 10;

    /**
     * Vlevo okraj
     * @var integer
     */
    private int $bodyLeft = 40;

    /**
     * Vlevo okraj (dva)
     * @var integer
     */
    private int $bodyLeft2 = 420;

    /**
     * Osazeni obsah
     * @var integer
     */
    private int $bodyStart = 365;

    /**
     * Radek
     * @var integer
     */
    private int $bodyLine = 20;

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
        
        $this->renderer->addFont(family: "sans", file: "OpenSans-Regular.php");
        $this->renderer->addFont(family: "sans", file: "OpenSans-Semibold.php", fontStyle: Settings::FONT_STYLE_BOLD);
        
        $this->buildHeader();
        $this->buildHeaderLine();
        $this->buildContentName();
        $this->buildContentText();
        $this->buildContentInfo();
        $this->buildContentData();
        $this->buildFooter();
        
        return $this->renderer->output();
    }

    /**
     * @return void
     */
    protected function buildHeader(): void
    {
        
        $renderer = $this->renderer;

        // logo
        if($this->schema->hasLogoPath()) {

            $renderer->placeImage(imgPath: $this->schema->getLogoPath(), x: $this->renderer->x(), y: -25, containerWidth: 300, containerHeight: 150);
        }

        // barevny polygon
        $renderer->polygon(points:
            [
                396, 0,
                396, 100,
                $renderer->width(), 100,
                $renderer->height(), 0,
            ],
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor($this->primaryColor);
            }
        );

        // cena bez DPH - popisek
        $renderer->cell(x: $this->bodyLeft2, y: 30, width: 150, height: null, text: "Cena (bez DPH): ", setCallback:

            function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });

        // cena bez DPH - hodnota
        $renderer->cell(x: $this->bodyLeft2, y: 55, width: 150, height: null, text: $this->_getFromArray(index: "price_base"), setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fontSize = 18;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });

        // cena s DPH - popisek
        $renderer->cell(x: $this->bodyLeft2 + ($this->bodyLeft2 / 2), y: 30, width: 150, height: null, text: "Cena (s DPH): ", setCallback:

            function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });

        // cena s DPH - hodnota
        $renderer->cell(x: $this->bodyLeft2 + ($this->bodyLeft2 / 2), y: 55, width: 150, height: null, text: $this->_getFromArray(index: "price_unit"), setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fontSize = 18;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
    }


    /**
     * @return void
     */
    protected function buildHeaderLine(): void
    {

        // barevny pruh
        $this->renderer->rect(x: 0, y: 100, width: $this->renderer->width() / 2, height:  0.1, setCallback: function (Settings $settings) {
            $settings->setFillDrawColor(color: $this->lineColor);
        });

    }


    /**
     * @return void
     */
    protected function buildImage(): void
    {

        $renderer = $this->renderer;

        try {

            // logo
            if(!empty($mainImage = $this->schema->getMainImage())) {

                $renderer->addImage(logo: $mainImage, x: 40, y: 125, width: 100, height: 100);
            }

        } catch (\Exception $e) {


        }


    }

    /**
     * @return void
     */
    protected function buildContentName(): void
    {

        $this->renderer->cell(x: $this->bodyLeft, y: $this->renderer->y() + 80 , width: $this->renderer->width() - 80, height: 30, text: $this->_getFromArray(index: "title"), setCallback:
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontSize = 20;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });

    }

    /**
     * @return void
     */
    protected function buildContentText(): void
    {

        if($this->schema->getMainImage()) {

            $this->renderer->Ln(height: 20);
            $this->renderer->placeImage(imgPath: $this->schema->getMainImage(), x: 300, y: $this->renderer->y(), alignment: "C");
            $this->renderer->Ln(height: 200);
        }

        if($this->data instanceof Table) {
            if(!empty($content = $this->data->getConfig(index: "content"))) {

                $this->renderer->Ln(height: 20);

                $this->renderer->cell(x: $this->bodyLeft, y: $this->renderer->y() , width: $this->renderer->width(), height: 30, text: "Popis produktu", setCallback:
                    function (Settings $settings) {
                        $settings->align = $settings::ALIGN_LEFT;
                        $settings->fontSize = $this->fontSizeText;
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    });

                $this->renderer->cell(x: $this->bodyLeft, y: $this->renderer->y(), text: $this->_cleanText(text: $content), height: 20, width: ($this->renderer->width() - 80), setCallback:
                    function (Settings $settings) {
                        $settings->align = $settings::ALIGN_JUSTIFY;
                        $settings->fontFamily = "sans";
                        $settings->fill = $settings::NO_FILL;
                        $settings->fontSize = $this->fontSizeText;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->fontColor = $this->fontColor;
                    });

            }

        }

        $this->renderer->Ln();
    }


    /**
     * @return void
     */
    protected function buildContentInfo(): void
    {

        if($this->data instanceof Table) {

            $this->renderer->cell(x: $this->bodyLeft, y: $this->renderer->y() , width: $this->renderer->width(), height: 30, text: "Informace", setCallback:
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = $this->fontSizeText;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });


            foreach ($this->data->getData() as $key => $val) {
                if(in_array($key, ["catalog", "brand"])) {

                    $this->renderer->cellFallback(x: $this->bodyLeft, y: $this->renderer->y(), text: ($this->translator[$key] ?? $key), height: 20, width: ($this->renderer->width() / 5 * 2), setCallback:

                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_LEFT;
                            $settings->fontFamily = "sans";
                            $settings->fill = $settings::FILL;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->fontColor = $this->fontColor;
                        });

                    $this->renderer->Ln(height: 1);

                    $this->renderer->cellFallback(x: ($this->renderer->width() / 2) - 40, y: $this->renderer->y(), text: $val, height: 20, width: ($this->renderer->width() / 2), setCallback:

                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_RIGHT;
                            $settings->fontFamily = "sans";
                            $settings->border = $settings::BORDER_RIGHT;
                            $settings->fill = $settings::NO_FILL;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                            $settings->fontColor = $this->fontColor;
                        });

                    $this->renderer->Ln();
                }

            }
        }

    }

    /**
     * @return void
     */
    protected function buildContentData(): void
    {

        if($this->property instanceof Table) {

            $this->renderer->Ln(height: 30);
            $this->renderer->cell(x: $this->bodyLeft, y: $this->renderer->y() , width: $this->renderer->width(), height: 30, text: "Parametry", setCallback:
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = $this->fontSizeText;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });

            foreach ($this->property->getData() as $property) {

                if($this->renderer->isEndPage()) {

                    $this->renderer->addPage();
                    $this->renderer->cell(x: $this->bodyLeft, y: $this->renderer->y() , width: $this->renderer->width(), height: 30, text: "Parametry", setCallback:
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_LEFT;
                            $settings->fontSize = $this->fontSizeText;
                            $settings->fontColor = $this->fontColor;
                            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                        });
                }

                $this->renderer->cellFallback(x: $this->bodyLeft, y: $this->renderer->y(), text: $property->labelId, height: 20, width: ($this->renderer->width() / 5 * 2), setCallback:

                    function (Settings $settings) {
                        $settings->align = $settings::ALIGN_LEFT;
                        $settings->fontFamily = "sans";
                        $settings->fill = $settings::FILL;
                        $settings->fontSize = $this->fontSizeText;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->fontColor = $this->fontColor;
                    });

                $this->renderer->Ln(height: 1);

                $this->renderer->cellFallback(x: ($this->renderer->width() / 2) - 40, y: $this->renderer->y(), text: $property->valueId, height: 20, width: ($this->renderer->width() / 2), setCallback:

                    function (Settings $settings) {
                        $settings->align = $settings::ALIGN_RIGHT;
                        $settings->fontFamily = "sans";
                        $settings->border = $settings::BORDER_RIGHT;
                        $settings->fill = $settings::NO_FILL;
                        $settings->fontSize = $this->fontSizeText;
                        $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                        $settings->fontColor = $this->fontColor;
                    });

                $this->renderer->Ln();

            }
        }

    }


}
