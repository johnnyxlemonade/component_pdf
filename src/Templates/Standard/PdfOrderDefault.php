<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Standard;
use Lemonade\Pdf\BasicFormatter;
use Lemonade\Pdf\BasicTranslator;
use Lemonade\Pdf\Calculators\FloatCalculator;
use Lemonade\Pdf\Components\Paginator;
use Lemonade\Pdf\Renderers\Color;
use Lemonade\Pdf\Renderers\PDFRenderer;
use Lemonade\Pdf\Renderers\Settings;
use Lemonade\Pdf\Data\Company;
use Lemonade\Pdf\Data\Customer;
use Lemonade\Pdf\Data\Order;
use Lemonade\Pdf\Data\Schema;
use Lemonade\Pdf\Data\Item;
use Lemonade\Pdf\Templates\OutputStandard;
use Nette\Utils\Strings;
use Nette\Utils\DateTime;

/**
 * PdfOrderDefault
 * \Lemonade\Pdf\Templates\PdfOrderDefault
 */
final class PdfOrderDefault extends OutputStandard
{

    /**
     * @param FloatCalculator $calculator
     * @param PDFRenderer $renderer
     * @param Company $company
     * @param Customer $billing
     * @param Order $order
     * @param Customer|null $delivery
     * @return string
     */
    public function generateOutput(

        FloatCalculator $calculator,
        PDFRenderer $renderer,
        Company $company,
        Customer $billing,
        Order $order,
        Customer $delivery = null

    ): string {
        
        $this->renderer = $renderer;
        $this->customerBilling = $billing;
        $this->customerDelivery = $delivery;
        $this->order = $order;
        $this->company = $company;
        $this->calculator = $calculator;
               
        $this->renderer->createNew();    
        $this->renderer->setDocumentMeta([
            "title" => Strings::firstUpper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate($this->customName) : $this->customName) . " " .$this->order->getNumber(),
            "subject" => Strings::firstUpper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate($this->customName) : $this->customName) . " " .$this->order->getNumber(),
            "author" => $this->translator->translate("metaAuthor"),            
        ]);

        // fonty
        $this->renderer->registerDefaultFont();
        
        $paginator = new Paginator($this->order->getItems(), $this->itemsPerPage);
       
        while ($paginator->nextPage()) {
            
            // prvniStrana
            if (!$paginator->isFirstPage()) {         
                
                $this->renderer->addPage();                                           
            } 
            
            $this->buildHeader();
            $this->buildSupplierBox();
            $this->buildSubscriberBox();
            $this->buildPaymentBox();
            $this->buildVerticalLine();
            
            
            $offset = 380;
            $this->buildBody($offset, 30);
            $offset = $this->buildItems($offset + 35, $paginator->getItems());
                    
            // posledniStrana
            if ($paginator->isLastPage()) {
                
                $this->buildTotal($offset + 20);
                $this->buildNotice();
                $this->buildMessage();
            }
                        
            // paticka
            $this->buildFooter($paginator);
            
        }
        
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
            396, 100,
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
        
        // nazev
        $renderer->cell(420, 45, 1, null, Strings::upper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate($this->customName) : $this->customName) . " " . $this->order->getNumber(),
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fontSize = 18;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // odkaz na stav objednavky
        if($this->order->getOrderStatusUrl() !== null) {
            
            $renderer->link(420, 70, 1, null, Strings::upper($this->order->getOrderIdentificator()), $this->order->getOrderStatusUrl(),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontFamily = 'sans';
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->fontColor = $this->whiteColor;
                });
            
        } else {
            
            // popis
            $renderer->cell(420, 70, 1, null, Strings::upper((string) $this->order->getOrderIdentificator()),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontFamily = 'sans';
                    $settings->fontSize = 10;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->fontColor = $this->whiteColor;
                });
        }
        
    }

    /**
     * @return void
     */
    protected function buildSupplierBox(): void
    {
        
        $renderer = $this->renderer;
        
        // dodavatelPopis
        $renderer->cell(40, 120, 1, null, Strings::upper($this->translator->translate("supplierName")),
            function (Settings $settings) {
                $settings->fontSize = 6;
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        
        // dodavalJmeno
        $renderer->cell(40, 140, 1, null, $this->company->getName(),
            function (Settings $settings) {
                $settings->fontSize = 10;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        $renderer->cell(40, 160, 1, null, $this->company->getAddress(),
            function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        
        // dodavatelMestoPsc
        $renderer->cell(40, 175, 1, null, $this->company->getZip() . " " . $this->company->getTown(),
            function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        
        $positionY  = 210;
        $multiplier = 0;
        
        // dodavatelICO
        if (!empty($this->company->getTin())) {

            // dodavatelIcoNazev
            $renderer->cell(40, $positionY, 1, null, $this->translator->translate("vatName"),
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // dodavalIcoHodnota
            $renderer->cell(380, $positionY, 1, null, $this->company->getTin(),
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                });
            
            $multiplier++;
        }
        
        // dodavatelDIC
        if ($this->company->hasTax()) {
            
            // dodavatelDicNazev
            $renderer->cell(40,  $positionY + ($multiplier * 20), 1, null, $this->translator->translate("vaTinName"),
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // dodavalDicHodnota
            $renderer->cell(380,  $positionY + ($multiplier * 20), 1, null, $this->company->getVaTin(),
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                });
            
        } else {
            
            // dodavatelDicNazev
            $renderer->cell(40,  $positionY + ($multiplier * 20), 1, null, $this->translator->translate("vaTinName"),
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // dodavatelDicNeplatce
            $renderer->cell(380,  $positionY + ($multiplier * 20), 1, null, $this->translator->translate("notTax"),
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                });
        }
    }

    /**
     * @return void
     */
    protected function buildSubscriberBox(): void
    {
        
        $renderer = $this->renderer;
        
        // odberatelNazev
        $renderer->cell(420, 120, 1, null, Strings::upper($this->translator->translate("subscriberLabel")), function (Settings $settings) {
            $settings->fontSize = 6;
            $settings->align = $settings::ALIGN_LEFT;
            $settings->fontColor = $this->grayColor;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
        });
        
        
        // odberatelJmeno
        $renderer->cell(420, 140, 1, null, $this->customerBilling->getName(), function (Settings $settings) {
            $settings->fontSize = 10;
            $settings->fontColor = $this->fontColor;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
        });
        
        // odberatelUlice
        $renderer->cell(420, 160, 1, null, $this->customerBilling->getAddress(), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
        });
        
        // odberatelMesto
        $renderer->cell(420, 175, 1, null, $this->customerBilling->getZip() . " " . $this->customerBilling->getTown(), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
        });
        
        $positionY  = 210;
        $multiplier = 0;
        
        // odberatelICO
        if (!empty($ico = $this->customerBilling->getTin())) {
            
            // odberatelIcoNazev
            $renderer->cell(420, $positionY, 1, null, $this->translator->translate("vatName"), function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->align = $settings::ALIGN_LEFT;
            });
            
            // odberatelIcoHodnota
            $renderer->cell(760, $positionY, 1, null, $ico, function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->align = $settings::ALIGN_RIGHT;
            });
            
            $multiplier++;
        }
        
        // odberatelDIC
        if ($this->customerBilling->hasTax()) {
            
            // odberatelDicNazev
            $renderer->cell(420,  $positionY + ($multiplier * 20), 1, null, $this->translator->translate("vaTinName"), function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->align = $settings::ALIGN_LEFT;
            });
            
            // odberatelDicHodnota
            $renderer->cell(760,  $positionY + ($multiplier * 20), 1, null, $this->customerBilling->getVaTin(), function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->align = $settings::ALIGN_RIGHT;
            });
        }
        
    }

    /**
     * @return void
     */
    protected function buildPaymentBox(): void
    {
        
        $renderer = $this->renderer;
        $half = ($renderer->width()) / 2;
        
        // barevny pruh
        $renderer->rect(0, 245, $half, 100, function (Settings $settings) {
            $settings->setFillDrawColor($this->primaryColor);
        });
        
        // sedy pruh
        $renderer->rect($half, 245, $half, 100, function (Settings $settings) {
            $settings->setFillDrawColor($this->lineColor);
        });
        
        
        // vlevo - bankovni ucet
        if ($this->order->getAccount()->getBank()) {
            
            // ucetNazev
            $renderer->cell(40, 265, 1, null, $this->translator->translate("bankAccount"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = 'sans';
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // ucetHodnota
            $renderer->cell(380, 265, 1, null, $this->order->getAccount()->getBank(), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        }
        
        // vlevo - iban
        if ($this->order->getAccount()->isValid()) {
            
            // ibanNazev
            $renderer->cell(40, 285, 1, null, Strings::upper($this->translator->translate("ibanName")), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = 'sans';
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // ibanHodnota
            $renderer->cell(380, 285, 1, null, $this->order->getAccount()->getIBan(true), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        }
        
        // vlevo - swift
        if ($this->order->getAccount()->isValid()) {
            
            // swiftNazev
            $renderer->cell(40, 305, 1, null, $this->translator->translate("swiftName"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = 'sans';
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // swiftHodnota
            $renderer->cell(380, 305, 1, null, $this->order->getAccount()->getSwift(), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        }
        
        // vlevo - platba
        if ($this->order->getPayment()->getPaymentName()) {
            
            // platbaNazev
            $renderer->cell(40, 325, 1, null, $this->translator->translate("paymentName"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = 'sans';
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // platbaHodnota
            $renderer->cell(380, 325, 1, null, $this->order->getPayment()->getPaymentName(), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        }

        // vpravo
        if($this->order->getNumber()) {
            
            // interniCisloObjednavky (nazev)
            $renderer->cell(420, 265, 1, null, $this->translator->translate("documentOrderName"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->grayColor;
                $settings->fontFamily = 'sans';
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // interniCisloObjednavky (hodnota)
            $renderer->cell(760, 265, 1, null, $this->order->getPayment()->getVariableSymbol(), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
        }
        
        // vpravo - datum objednavky
        if ($this->order->getAccount()->getBank()) {
            
            // vystaveniNazev
            $renderer->cell(420, 285, 1, null, $this->translator->translate("orderDate"),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // vystaveniHodnota
            $renderer->cell(760, 285, 1, null, $this->formatter->formatDate($this->order->getCreated()),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
        }
        
        // vpravo - datum objednavky
        if ($this->order->getExternalDealerId()) {
            
            // vystaveniNazev
            $renderer->cell(420, 305, 1, null, $this->translator->translate("documentExternalDealerId"),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // vystaveniHodnota
            $renderer->cell(760, 305, 1, null, $this->order->getExternalDealerId(),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
        }
        
        // vpravo - variabilniSymbol
        if ($this->order->getExternalId()) {
            
            // variabilniSymbol (nazev)
            $renderer->cell(420, 325, 1, null, $this->translator->translate("varSymbol"),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // externiCisloObjednavky (hodnota)
            $renderer->cell(760, 325, 1, null, $this->order->getExternalId(),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            
        } else {
            
            // variabilniSymbol (nazev)
            $renderer->cell(420, 325, 1, null, $this->translator->translate("varSymbol"),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // interCisloObjednavky (hodnota)
            $renderer->cell(760, 325, 1, null, $this->order->getNumber(),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
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
        $renderer->rect($renderer->width() / 2 - 1, 101, 1, 143, function (Settings $settings) {
            $settings->setFillDrawColor($this->lineColor);
        });

    }

    /**
     * @param int $offsetHead
     * @param int $offsetBody
     * @return void
     */
    protected function buildBody(int $offsetHead, int $offsetBody): void
    {
        
        $renderer = $this->renderer;
        
        $renderer->rect(0, $offsetHead, $renderer->width(), 30, function (Settings $settings) {
            $settings->setFillDrawColor($this->evenColor);
        });
        
        $renderer->rect(0, $offsetHead + $offsetBody, $renderer->width(), 1, function (Settings $settings) {
            $settings->setFillDrawColor($this->primaryColor);
        });
        
        // vypis polozek
        $renderer->cell(40, $offsetHead - 32, 360, 30, Strings::firstUpper($this->translator->translate("orderBefore")),
            function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            });

        // pokud je dodavatel platce DPH
        if ($this->company->hasTax()) {
            
            // kodZbozi
            $renderer->cell(40, $offsetHead, 360, 30, Strings::firstUpper($this->translator->translate("itemCatalog")),
                function (Settings $settings) {
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->fontSize = 8;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // nazevPolozky
            $renderer->cell(140, $offsetHead, 360, 30, Strings::firstUpper($this->translator->translate("itemName")),
                function (Settings $settings) {
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->fontSize = 8;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // dphSazba
            $renderer->cell(560, $offsetHead, 40, 30, Strings::firstUpper($this->translator->translate("itemTax")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                });
            
            // pocetJednotek
            $renderer->cell(620, $offsetHead, 60, 30, Strings::firstUpper($this->translator->translate("itemCount")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                });
            
            
            // mezisoucet
            $renderer->cell(600, $offsetHead, 160, 30, Strings::firstUpper($this->translator->translate("itemUnitTotal")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                });
            
        } else {
            
            // kodZbozi
            $renderer->cell(40, $offsetHead, 360, 30, Strings::firstUpper($this->translator->translate("itemCatalog")),
                function (Settings $settings) {
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->fontSize = 8;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // nazevPolozky
            $renderer->cell(140, $offsetHead, 360, 30, Strings::firstUpper($this->translator->translate("itemName")),
                function (Settings $settings) {
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->fontSize = 8;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // pocetJednotek
            $renderer->cell(620, $offsetHead, 60, 30, Strings::firstUpper($this->translator->translate("itemCount")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                });
            
            
            // mezisoucet
            $renderer->cell(600, $offsetHead, 160, 30, Strings::firstUpper($this->translator->translate("itemUnitTotal")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                });
        }
        
    }

    /**
     * @param int $offset
     * @param array $items
     * @return int
     */
    protected function buildItems(int $offset, array $items): int
    {
        
        $renderer = $this->renderer;

        /**
         * @var Item $item
         */
        foreach ($items as $i => $item) {

            // radekBarva1
            $renderer->rect(0, $offset, $renderer->width(), 27.0,
                function (Settings $settings) use ($i) {
                    $settings->setFillDrawColor($i % 2 === 1 ? $this->evenColor : $this->oddColor);
                });

            // radekBarva2
            $renderer->rect(0, $offset + 27, $renderer->width(), 0.1,
                function (Settings $settings) {
                    $settings->setFillDrawColor($this->evenColor->darken(10));
                });


            // katalogoveCislo
            $catName = $item->getCatalogName();
            $catLine = 17;
            $catSize = Strings::length($catName);

            // kodPolozky
            $renderer->cell(40, ($catSize > $catLine ? $offset : $offset + 15), 100, ($catSize > $catLine ? 12 : null), $catName,
                function (Settings $settings) {
                    $settings->fontFamily = "sans";
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 7;
                });

            // nazevPolozky
            $itemName  = $item->getName();
            $itemSize  = Strings::length($itemName);

            if($itemSize > 160) {
                $itemName  = Strings::substring($itemName . "...", 0, 140);
            }

            $itemLine  = 420;
            $itemWidth = $renderer->textWidth($itemName);

            // nazevPolozky
            $renderer->cell(140, $offset, 420, ($itemWidth > $itemLine ? 13 : 27), $itemName,
                function (Settings $settings) {
                    $settings->fontFamily = "sans";
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 7;
                });

            // pokud je dodavatel platce DPH
            if ($this->company->hasTax()) {

                // dphSazba
                $renderer->cell(560, $offset, 40, 27, $item->getTax() . "%",
                    function (Settings $settings) {
                        $settings->align = $settings::ALIGN_CENTER;
                        $settings->fontSize = 7;
                    });

                // pocetJednotek (vcetne jednotky)
                $renderer->cell(610, $offset, 80, 27, $this->formatter->formatNumber(number: $item->getAmount(), decimal: 0) . "x",
                    function (Settings $settings) {
                        $settings->align = $settings::ALIGN_CENTER;
                        $settings->fontSize = 7;
                    });

                if($item->isSale()) {

                    // mezisoucet
                    $renderer->cell(600, $offset, 160, 27, $this->formatter->formatMoney(number: $item->getPrice(), formatDecimal: true),
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_RIGHT;
                            $settings->fontSize = 7;
                        });

                } else {

                    // mezisoucet
                    $renderer->cell(600, $offset, 160, 27, $this->formatter->formatMoney(number: $item->getTotalPrice(calculator: $this->calculator, useTax: true), formatDecimal: true),
                        function (Settings $settings) {
                            $settings->align = $settings::ALIGN_RIGHT;
                            $settings->fontSize = 7;
                        });
                }


                $offset += 27;

            }  else {

                // pocetJednotek (vcetne jednotky)
                $renderer->cell(610, $offset, 80, 27, $this->formatter->formatNumber(number: $item->getAmount(), decimal: 0) . "x",
                    function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                    $settings->fontSize = 7;
                });

                // mezisoucet
                $renderer->cell(600, $offset, 160, 27, $this->formatter->formatMoney(number: $item->getTotalPrice(calculator: $this->calculator, useTax: true), formatDecimal: true),
                    function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontSize = 7;
                });

                $offset += 27;
            }
        }
        
        return $offset;
    }

    /**
     * @param int $offset
     * @return void
     */
    protected function buildTotal(int $offset): void
    {

        $renderer = $this->renderer;
        $half = ($renderer->width() - 553) / 2;

        // qrkod - stav
        if($this->schema->hasUrlPath()) {

            $renderer->addImage($this->schema->getUrlPath(), $half - 85, $offset);
        }

        // dphSouhrny
        if($this->company->hasTax()) {

            //  dph sazby
            $vatLines = $this->order->getVatLines($this->company->hasTax());

            //  dph sazby
            if(count($vatLines) > 0) {

                $dphBase = 0;

                foreach($vatLines as $key => $val) {
                    $dphBase += $val;

                    // dphSazba
                    $renderer->cell(420, $offset, $half, 15, sprintf("%s %s %s", $this->translator->translate("vatRate"), $key, "%"),
                        function (Settings $settings) {
                            $settings->fontFamily = "sans";
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->align = $settings::ALIGN_LEFT;
                        });

                    // dphHodnota
                    $renderer->cell(640, $offset, $half, 15, $this->formatter->formatMoney(number: $val, formatDecimal: true),
                        function (Settings $settings) {
                            $settings->fontFamily = "sans";
                            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                            $settings->fontColor = $this->fontColor;
                            $settings->align = $settings::ALIGN_RIGHT;
                        });

                    $offset += 15;
                }

                // bez DPH celkem
                if((float) $dphBase > 0) {

                    $offset += 5;

                    // dphSazba
                    $renderer->cell(420, $offset, $half, 15, $this->translator->translate("summaryTaxBase"),
                        function (Settings $settings) {
                            $settings->fontFamily = "sans";
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->align = $settings::ALIGN_LEFT;
                        });

                    // dphHodnota
                    $renderer->cell(640, $offset, $half, 15, $this->formatter->formatMoney(number: ($this->order->getTotalPrice($this->calculator, $this->company->hasTax()) - $dphBase), isTotal: false),
                        function (Settings $settings) {
                            $settings->fontFamily = "sans";
                            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                            $settings->fontColor = $this->fontColor;
                            $settings->align = $settings::ALIGN_RIGHT;
                        });

                    $offset += 15;
                }

                if((float) $dphBase > 0) {

                    $totalGoods    = $this->order->getTotalPrice($this->calculator, $this->company->hasTax());

                    if($this->order->getPayment()->getCurrency() === "EUR") {

                        $articleTotal  = round(num: $totalGoods, precision: 1);

                    } else {

                        $articleTotal  = round(num: $totalGoods);
                    }

                    $articleHaller = round(num: ($articleTotal - $totalGoods), precision: 3);


                    if($articleHaller !== 0.0) {

                        // dphSazba
                        $renderer->cell(420, $offset, $half, 15, $this->translator->translate("summaryRounding"),
                            function (Settings $settings) {
                                $settings->fontFamily = "sans";
                                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                                $settings->align = $settings::ALIGN_LEFT;
                            });

                        // dphHodnota
                        $renderer->cell(640, $offset, $half, 15, $this->formatter->formatMoney(number: $articleHaller, isTotal: false),
                            function (Settings $settings) {
                                $settings->fontFamily = "sans";
                                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                                $settings->fontColor = $this->fontColor;
                                $settings->align = $settings::ALIGN_RIGHT;
                            });

                        $offset += 15;
                    }
                }

                $offset += 10;
            }
        }

        $renderer->rect(420, $offset, $renderer->width(), 24, function (Settings $settings) {
                $settings->setFillDrawColor($this->primaryColor);
            });
                
            $renderer->cell(425, $offset, $half, 24, $this->translator->translate("totalPrice"), function (Settings $settings) {
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontColor = $this->whiteColor;
                $settings->fontSize = 8;
            });

            $totalPrice = $this->order->getTotalPrice($this->calculator, $this->company->hasTax());
            $salesPrice = $this->order->getSalesPrice($this->calculator);
            $finalPrice = ($totalPrice + $salesPrice);

            $renderer->cell(640, $offset, $half, 24, $this->formatter->formatMoney(number: $finalPrice, isTotal: true),
                function (Settings $settings) {
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
                $settings->fontSize = 10;
                $settings->align = $settings::ALIGN_RIGHT;
            });
            
            // stamp
            if($this->schema->hasStampPath()) {
                $renderer->addImage($this->schema->getStampPath(), 500 + $half, $offset + 42, 148);
            }
    }


}
