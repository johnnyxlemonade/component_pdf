<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Table;

use Lemonade\Pdf\Data\Table;
use Lemonade\Pdf\Renderers\PDFRenderer;
use Lemonade\Pdf\Templates\OutputTable;
use Lemonade\Pdf\Data\Schema;
use Lemonade\Pdf\Renderers\Color;
use Lemonade\Pdf\Renderers\Settings;
use Nette\Utils\Strings;

/**
 * @Diteceskatable
 * @\Lemonade\Pdf\Templates\Table\Diteceskatable
 */
final class Diteceskatable extends OutputTable
{

    /**
     * @var int
     */
    private int $fontSizeText = 8;

    /**
     * @var int
     */
    private int $fontSizeNormal = 10;

    /**
     * @var int
     */
    private int $bodyLeft = 40;

    /**
     * @var int
     */
    private int $bodyLeft2 = 420;

    /**
     * @var int
     */
    private int $bodyStart = 280;

    /**
     * @var int
     */
    private int $bodyLine = 20;

    /**
     * @param PDFRenderer $renderer
     * @param Table $table
     * @return string
     */
    public function generateOutput(PDFRenderer $renderer, Table $table): string
    {
        
        $this->renderer = $renderer;
        $this->table = $table;

        $this->renderer->createNew();
        $this->renderer->setDocumentMeta(
            data: [
                "title" => sprintf("Nominace - %s", $this->_getFromArray(index: "nomination_code")),
                "subject" => "nominace",
                "author" => "core1.agency"
            ]
        );
        
        $this->renderer->addFont("sans", "OpenSans-Regular.php");
        $this->renderer->addFont("sans", "OpenSans-Semibold.php", Settings::FONT_STYLE_BOLD);
        
        $this->buildHeader();
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
            $renderer->addImage($this->schema->getLogoPath(), 40, 40, 374/2);
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
        $renderer->cell($this->bodyLeft2, 30, 1, null, "Kód nominační přihlášky",
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = 'sans';
                $settings->fontSize = $this->fontSizeText;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // nazev
        $renderer->cell($this->bodyLeft2, 50, 1, null, $this->_getFromArray(index: "nomination_code"),
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = 'sans';
                $settings->fontSize = 18;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // nazev
        $renderer->cell($this->bodyLeft2, 80, 1, null, sprintf("Vytvořeno: %s", Date("j.n.Y, G:i", strtotime($this->_getFromArray(index: "nomination_created_on")))),
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
        
        $renderer->cell($this->bodyLeft, $start, 1, null, Strings::upper("Nominované dítě"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::upper($this->_getFromArray(index: "nomination_candidate_name")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $iterator++;
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::lower($this->_getFromArray(index: "nomination_candidate_email")),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        $iterator++;
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::lower($this->_getFromArray(index: "nomination_candidate_city")),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        $iterator++;
        
        $renderer->cell($this->bodyLeft, ($start + ($line * $iterator)), 1, null, Strings::lower($this->_getFromArray(index: "nomination_candidate_birthday")),
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
        
        $renderer->cell($this->bodyLeft2, $start, 1, null, Strings::upper("Zákonný zástupce"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft2, ($start + ($this->bodyLine * $iterator)), 1, null, Strings::upper($this->_getFromArray(index: "nomination_representative_name")),
            function (Settings $settings) {
                $settings->fontSize = 12;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $iterator++;
        
        $renderer->cell($this->bodyLeft2, ($start + ($this->bodyLine * $iterator)), 1, null, Strings::lower($this->_getFromArray(index: "nomination_representative_email")),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });

    }

    /**
     * @return void
     */
    protected function buildBody(): void
    {
        
        $renderer = $this->renderer;
        $start = $this->bodyStart;        
        
        if(!empty($this->_getFromArray(index: "nomination_name"))) {
        
            $renderer->cell($this->bodyLeft, $start, 1, null, Strings::upper("Nominující osoba"),
                function (Settings $settings) {
                    $settings->fontSize = $this->fontSizeText;
                    $settings->fontColor = $this->primaryColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
            
            $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::upper($this->_getFromArray(index: "nomination_name")),
                function (Settings $settings) {
                    $settings->fontSize = 12;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
            
            $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::lower($this->_getFromArray(index: "nomination_email")),
                function (Settings $settings) {
                    $settings->fontSize = $this->fontSizeNormal;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            
            $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, Strings::lower($this->_getFromArray(index: "nomination_phone")),
                function (Settings $settings) {
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            
            $renderer->rect($this->bodyLeft, ($renderer->y() + $this->bodyLine), ($renderer->width() - 80), 1,
                function (Settings $settings) {
                    $settings->setFillDrawColor($this->lineColor);
                });
            
            $start = ($renderer->y() + ($this->bodyLine * 2));
            
        } else {
            
            $start = $this->bodyStart; 
        }

        
        // obory
        $renderer->cell($this->bodyLeft, $start, 1, null, "Vybrané obory, ve kterých se dítě profiluje",
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });

        foreach(explode(separator: "###", string: $this->_getFromArray(index: "nomination_specialization_data")) as $val) {

            $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, $val,
                function (Settings $settings) {
                    $settings->fontSize = $this->fontSizeNormal;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
        }
        
        // aktivity
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, "Aktivity prospěšné pro společnost, okolí, životní prostředí, potřebné osoby či jiné oblasti veřejného života nominovaný rozvíjí",
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + ($this->bodyLine / 2 )), ($renderer->width() - ($this->bodyLeft * 2)), ($this->fontSizeNormal + 5), $this->_getFromArray(index: "nomination_candidate_activity"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        if($renderer->y() > 2000) {
            $renderer->addPage();
        }
        
        // zpusob
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, "Způsob, jakým inspiruje své okolí a vrstevníky",
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell($this->bodyLeft, ($renderer->y() + ($this->bodyLine / 2 )), ($renderer->width() - ($this->bodyLeft * 2)), ($this->fontSizeNormal + 5), $this->_getFromArray(index: "nomination_candidate_inspiration"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        
        
        if($renderer->y() > 2000) {
            $renderer->addPage();
        }

        // dalsi
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, "Další úspěchy a aktivity nominovaného dítěte, které by měla porota zvážit",
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });

        $renderer->cell($this->bodyLeft, ($renderer->y() + ($this->bodyLine / 2 )), ($renderer->width() - ($this->bodyLeft * 2)), ($this->fontSizeNormal + 5), $this->_getFromArray(index: "nomination_candidate_another"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });

        if($renderer->y() > 2000) {
            $renderer->addPage();
        }

        // projekt vyhra
        $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, "Návrh veřejně prospěšného projektu či aktivity iniciované dítětem v případě výhry finanční odměny v hodnotě 15 000 Kč",
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeText;
                $settings->fontColor = $this->primaryColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });

        $renderer->cell($this->bodyLeft, ($renderer->y() + ($this->bodyLine / 2 )), ($renderer->width() - ($this->bodyLeft * 2)), ($this->fontSizeNormal + 5), $this->_getFromArray(index: "nomination_candidate_project"),
            function (Settings $settings) {
                $settings->fontSize = $this->fontSizeNormal;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });

        if($renderer->y() > 2000) {
            $renderer->addPage();
        }
        
        // odkazy
        if(!empty($links = $this->_getFromArray(index: "nomination_external_links"))) {
            
            $renderer->cell($this->bodyLeft, ($renderer->y() + $this->bodyLine), 1, null, "Externí odkazy",
                function (Settings $settings) {
                    $settings->fontSize = $this->fontSizeText;
                    $settings->fontColor = $this->primaryColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
            
            $renderer->cell($this->bodyLeft, ($renderer->y() + ($this->bodyLine / 2 )), ($renderer->width() - ($this->bodyLeft * 2)), ($this->fontSizeNormal + 5), $links,
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
    protected function buildVerticalLine(): void
    {
        
        $renderer = $this->renderer;
        
        // barevny pruh
        $renderer->rect($renderer->width() / 2 - 1, 108, 1, 140,
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
        $renderer->rect(40, 255, $renderer->width() - 80, 0.1,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->lineColor);
            });
        
    }

}
