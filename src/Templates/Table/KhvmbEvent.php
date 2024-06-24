<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Table;

use Lemonade\Pdf\Templates\OutputTable;
use Lemonade\Pdf\Data\{Schema, Table};
use Lemonade\Pdf\Renderers\{Color, PDFRenderer, PdfRendererInterface, Settings};
use Lemonade\Pdf\Templates\TableInterface;
use Nette\Utils\Strings;

/**
 * KhvmbEvent
 * \Lemonade\Pdf\Custom\KhvmbEvent
 */
class KhvmbEvent extends OutputTable
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
     * @param PDFRenderer $renderer
     * @param Table $meta
     * @param Table $data
     * @param Table|null $property
     * @return string
     */
    public function generateOutput(PDFRenderer $renderer, Table $meta, Table $data, Table $property = null): string
    {
        
        $this->renderer = $renderer;
        $this->data = $data;

        $this->renderer->createNew();
        $this->renderer->setDocumentMeta(data:
            [
                "title" => $meta->getConfig(index: "title"),
                "subject" => $meta->getConfig(index: "subject"),
                "author" => "core1.agency"
            ]
        );

        $this->renderer->addFont(family: "sans", file: "OpenSans-Regular.php");
        $this->renderer->addFont(family: "sans", file: "OpenSans-Semibold.php", fontStyle: Settings::FONT_STYLE_BOLD);
        
        $this->buildHeader();
        $this->buildHorizontalLineBefore();
        $this->builderLeftContent();
        $this->builderRightContent();
        $this->buildVerticalLine();
        $this->buildHorizontalLine();
        $this->buildBody();        
        $this->buildFooter();
        
        return $this->renderer->output();
    }

    /**
     * @return void
     */
    protected function buildHeader(): void
    {
        
        $renderer = $this->renderer;
        
        // bily polygon
        $renderer->polygon([
            0, 0,
            0, 100,
            396, 50,
            396, 0,
        ], function (Settings $settings) {
            $settings->setFillDrawColor($this->whiteColor);
        });
        
        
        // logo
        if($this->schema->hasLogoPath()) {
            $renderer->addImage($this->schema->getLogoPath(), 40, 20, 250);
        }
        
        // barevny polygon
        $renderer->polygon([
            396, 0,
            396, 100,
            $renderer->width(), 100,
            $renderer->height(), 0,
        ], function (Settings $settings) {
            $settings->setFillDrawColor($this->primaryColor);
        });
        
        
        // kod prihlasky
        $renderer->cell($this->bodyLeft2, 30, 1, null, "Kód přihlášky",
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = 'sans';
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // nazev
        $renderer->cell($this->bodyLeft2, 50, 1, null, $this->_getFromArray("application_code"),
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = 'sans';
                $settings->fontSize = 18;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // nazev
        $renderer->cell($this->bodyLeft2, 80, 1, null, sprintf("Vytvořeno: %s", Date("j.n.Y, G:i", strtotime($this->_getFromArray("application_created_on")))),
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = 'sans';
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
    }

    /**
     * @return void
     */
    protected function builderLeftContent(): void
    {
        
        $renderer = $this->renderer;
        
        $start = 150;
        $line = 20;
        $iterator = 1;
        
        $renderer->cell($this->bodyLeft, $start, 1, null, Strings::upper("Jezdec"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::upper($this->_getFromArray("application_driver_name")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $iterator++;
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::lower($this->_getFromArray("application_driver_street")),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        $iterator++;
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::lower($this->_getFromArray("application_driver_city")),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        $iterator++;
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::lower($this->_getFromArray("application_driver_zip")),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        $iterator++;
        $iterator++;
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::lower($this->_getFromArray("application_driver_email")),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        $iterator++;
       
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::lower(($this->_getFromArray("application_driver_phone") ?? "--- --- --- --- ---")),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
      
    }

    /**
     * @return void
     */
    protected function builderRightContent(): void
    {

        $renderer = $this->renderer;
        
        $start = 150;
        $iterator = 1;
        
        // nazevUdalosti
        if(!empty($event = $this->_getFromArray("event_title"))) {

            $renderer->cell($this->bodyLeft2, $start, 1, null, Strings::upper("Událost"),
                function (Settings $settings) {
                    $settings->fontSize = $this->fontSizeText;
                    $settings->fontColor = $this->primaryColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
            
            $renderer->cell($this->bodyLeft2, ($start + ($this->bodyLine * $iterator)), 1, null, Strings::upper($event),
                function (Settings $settings) {
                    $settings->fontSize = 12;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
            
            $iterator++;
        }
        
        // datumUdalosti
        if(!empty($event = $this->_getFromArray("event_from_date"))) {
            
            $renderer->cell($this->bodyLeft2, ($start + ($this->bodyLine * $iterator)), 1, null, Strings::lower(Date("j. n. Y", strtotime($event))),
                function (Settings $settings) {
                    $settings->fontSize = $this->fontSizeNormal;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
        
        }
    }

    /**
     * @return void
     */
    protected function buildBody(): void
    {
        
        $renderer = $this->renderer;
        $start = $this->bodyStart;

        $renderer->cell($this->bodyLeft, $start, 1, null, Strings::upper("Druh vozidla"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::upper($this->_getFromArray("application_vehicle_id")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine + 10), 1, null, Strings::upper("Značka"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::upper($this->_getFromArray("application_vehicle_brand")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });

        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine + 10), 1, null, Strings::upper("Rok výroby"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::upper($this->_getFromArray("application_vehicle_year")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine + 10), 1, null, Strings::upper("Typ vozidla"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::upper($this->_getFromArray("application_vehicle_typ")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine + 10), 1, null, Strings::upper("Objem motoru"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::upper($this->_getFromArray("application_vehicle_engine")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine + 10), 1, null, Strings::upper("SPZ vozidla"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::upper($this->_getFromArray("application_vehicle_licence_plate")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });

        
        
    }

    /**
     * @return void
     */
    protected function buildHorizontalLineBefore(): void
    {
        
        $renderer = $this->renderer;
        
        // barevny pruh
        $renderer->rect(40, 100, (($renderer->width() / 2) - 43), 0.1,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->lineColor);
            });
        
    }

    /**
     * @return void
     */
    protected function buildHorizontalLine(): void
    {
        
        $renderer = $this->renderer;
        
        // barevny pruh
        $renderer->rect(40, 330, $renderer->width() - 80, 0.1,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->lineColor);
            });
        
    }

    /**
     * @return void
     */
    protected function buildVerticalLine(): void
    {
        
        $renderer = $this->renderer;
        
        // barevny pruh
        $renderer->rect($renderer->width() / 2 - 2, 100, 1, 230,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->lineColor);
            });
    }



    
}
