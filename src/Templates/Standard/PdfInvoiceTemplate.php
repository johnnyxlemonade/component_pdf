<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Standard;

use Lemonade\Pdf\Calculators\FloatCalculator;
use Lemonade\Pdf\Renderers\PDFRenderer;
use Lemonade\Pdf\Renderers\Settings;
use Lemonade\Pdf\Components\Paginator;
use Lemonade\Pdf\Data\Company;
use Lemonade\Pdf\Data\Customer;
use Lemonade\Pdf\Data\Order;
use Lemonade\Pdf\Data\Item;
use Lemonade\Pdf\Templates\OutputStandard;
use Nette\Utils\Strings;
use Nette\Utils\DateTime;
use function sprintf;


/**
 * PdfInvoiceTemplate
 * \Lemonade\Pdf\Templates\PdfInvoiceTemplate
 */
final class PdfInvoiceTemplate extends OutputStandard
{

    /**
     * @var int
     */
    private int $lineHeight = 25;

    /**
     * @var array
     */
    private array $configItems = [
        "itemName" => [
            "x" => 40,
            "y" => 360
        ],
        "itemTax" => [
            "x" => 410,
            "y" => 40
        ],
        "itemCount" => [
            "x" => 480,
            "y" => 40
        ],
        "itemUnitPrice" => [
            "x" => 540,
            "y" => 80
        ],
        "itemUnitTotal" => [
            "x" => 600,
            "y" => 160
        ]
    ];

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
        $this->renderer->setDocumentMeta(data:
            [
                "title" => Strings::firstUpper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate($this->customName) : $this->customName) . " " .$this->order->getNumber(),
                "subject" => Strings::firstUpper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate($this->customName) : $this->customName) . " " .$this->order->getNumber(),
                "author" => $this->translator->translate("metaAuthor")
            ]
        );
        
        // fonty
        $this->renderer->registerDefaultFont();
        
        // strankovani
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
            $this->buildBody(offsetHead: $offset, offsetBody: 30);
            $offset = $this->buildItems(offset: $offset + 35, items: $paginator->getItems());

            // posledniStrana
            if ($paginator->isLastPage()) {

                $this->buildTotal();
            }
                        
            // footerText - rejstrik
            $renderer->cell(0, - 40, $this->renderer->width(), null, Strings::firstUpper($this->company->getFileNumberDesc() ?? $this->translator->translate("footerText")),
                function (Settings $settings) {
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->fontSize = 6;
                    $settings->align = $settings::ALIGN_CENTER;
                });
            
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
        $renderer->cell(420, 45, 1, null, Strings::upper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate($this->customName) : $this->customName) . " " .$this->order->getNumber(), 
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fontSize = ($this->order->hasStorno() ? 16 : 22);
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // popis
        $renderer->cell(420, 70, 1, null, Strings::upper($this->translator->translate("documentDescription")), 
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = 'sans';
                $settings->fontSize = 10;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
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
        $renderer->cell(40, 175, 1, null, $this->company->getZip() . ' ' . $this->company->getTown(), 
            function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        
        $positionY  = 210;
        $multiplier = 0;
        
        // dodavatelICO
        if ($this->company->getTin()) {
            
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
        $renderer->cell(420, 120, 1, null, Strings::upper($this->translator->translate("subscriberLabel")),
            function (Settings $settings) {
                $settings->fontSize = 6;
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        
        // odberatelJmeno
        $renderer->cell(420, 140, 1, null, $this->customerBilling->getName(), 
            function (Settings $settings) {
                $settings->fontSize = 10;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        
        // odberatelUlice
        $renderer->cell(420, 160, 1, null, $this->customerBilling->getAddress(), 
            function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        // odberatelMesto
        $renderer->cell(420, 175, 1, null, $this->customerBilling->getZip() . ' ' . $this->customerBilling->getTown(), 
            function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
        
        $positionY  = 210;
        $multiplier = 0;
        
        // odberatelICO
        if ($this->customerBilling->getTin()) {
            
            // odberatelIcoNazev
            $renderer->cell(420, $positionY, 1, null, $this->translator->translate("vatName"), 
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // odberatelIcoHodnota
            $renderer->cell(760, $positionY, 1, null, $this->customerBilling->getTin(),
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                });
            
            $multiplier++;
        }
        
        // odberatelDIC
        if ($this->customerBilling->getVaTin()) {
            
            // odberatelDicNazev
            $renderer->cell(420,  $positionY + ($multiplier * 20), 1, null, $this->translator->translate("vaTinName"), 
                function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                });
            
            // odberatelDicHodnota
            $renderer->cell(760,  $positionY + ($multiplier * 20), 1, null, $this->customerBilling->getVaTin(), 
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
    protected function buildPaymentBox(): void
    {
        
        $renderer = $this->renderer;
        $half = ($renderer->width()) / 2;
        
        // barevny pruh
        $renderer->rect(0, 245, $half, 100,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->primaryColor);
            });
        
        // sedy pruh
        $renderer->rect($half, 245, $half, 100, 
            function (Settings $settings) {
                $settings->setFillDrawColor($this->lineColor);
            });
        
        
        // vlevo - bankovni ucet
        if ($this->order->getAccount()->getBank()) {
            
            // ucetNazev
            $renderer->cell(40, 265, 1, null, $this->translator->translate("bankAccount"), 
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->whiteColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // ucetHodnota
            $renderer->cell(380, 265, 1, null, $this->order->getAccount()->getBank(), 
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
        }
        
        // vlevo - iban
        if ($this->order->getAccount()->isValid()) {
            
            // ibanNazev
            $renderer->cell(40, 285, 1, null, Strings::upper($this->translator->translate("ibanName")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->whiteColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // ibanHodnota
            $renderer->cell(380, 285, 1, null, $this->order->getAccount()->getIBan(true),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
        }
        
        // vlevo - swift
        if ($this->order->getAccount()->isValid()) {
            
            // swiftNazev
            $renderer->cell(40, 305, 1, null, $this->translator->translate("swiftName"),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->whiteColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // swiftHodnota
            $renderer->cell(380, 305, 1, null, $this->order->getAccount()->getSwift(),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
        }
        
        // vlevo - platba
        if ($this->order->getPayment()->getPaymentName()) {
            
            // platbaNazev
            $renderer->cell(40, 325, 1, null, $this->translator->translate("paymentName"),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->whiteColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // platbaHodnota
            $renderer->cell(380, 325, 1, null, $this->order->getPayment()->getPaymentName(),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
        }
        
        
        // vpravo - variabilni symbol
        if ($this->order->getPayment()->getVariableSymbol() || $this->order->hasStorno()) {
            
            // vsNazev
            $renderer->cell(420, 265, 1, null, $this->translator->translate(($this->order->hasStorno() ? "documentInvoiceId" : "varSymbol")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // vsHodnota - storno ma Id faktury, jinak VS cislo
            $renderer->cell(760, 265, 1, null, ($this->order->hasStorno() ? ($this->order->getExtraData()["stornoInvoiceId"] ?? $this->order->getPayment()->getVariableSymbol()) : $this->order->getPayment()->getVariableSymbol()),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
        }
        
        // vpravo - datum vystaveni
        if ($this->order->getAccount()->getBank()) {
            
            // vystaveniNazev
            $renderer->cell(420, 285, 1, null, $this->translator->translate("date"),
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
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
        }
        
        // vpravo - datum splatnosti
        if ($this->order->getDueDate() !== null) {
            
            // splatnostNazev
            $renderer->cell(420, 305, 1, null, $this->translator->translate("dueDate"),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // splatnostHodnota
            $renderer->cell(760, 305, 1, null, $this->formatter->formatDate($this->order->getDueDate()),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                });
        }
        
        // vpravo - datum zdan. plneni
        if ($this->order->getDueDate() !== null) {
            
            // plneniNazev
            $renderer->cell(420, 325, 1, null, $this->translator->translate("taxDate"),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontFamily = 'sans';
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                });
            
            // plneniHodnota
            $renderer->cell(760, 325, 1, null, $this->formatter->formatDate($this->order->getDueDate()),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
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
        $renderer->rect($renderer->width() / 2 - 1, 102, 1, 140, 
            function (Settings $settings) {
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
        
        $renderer->rect(0, $offsetHead, $renderer->width(), 30, 
            function (Settings $settings) {
                $settings->setFillDrawColor($this->evenColor);
        });
        
        $renderer->rect(0, $offsetHead + $offsetBody - 1, $renderer->width(), 1,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->primaryColor);
        });
        
        // vypis polozek
        $renderer->cell(40, $offsetHead - 32, 480, 30, Strings::firstUpper(($this->order->hasStorno() ? sprintf($this->translator->translate("bodyBeforeStorno"), $this->order->getExtraData()["stornoInvoiceId"] ?? "") : $this->translator->translate("bodyBefore"))),
            function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            });

        // nazevPolozky
        $renderer->cell(
            $this->configItems["itemName"]["x"],
            $offsetHead,
            $this->configItems["itemName"]["y"],
            $this->lineHeight,
            Strings::firstUpper($this->translator->translate("itemName")),
            function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            }
        );
        
        
        // pokud je dodavatel platce DPC
        if ($this->company->hasTax()) {

            // dphSazba
            $renderer->cell(
                $this->configItems["itemTax"]["x"],
                $offsetHead,
                $this->configItems["itemTax"]["y"],
                $this->lineHeight,
                Strings::firstUpper($this->translator->translate("itemTax")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                }
            );

            // pocetJednotek
            $renderer->cell(
                $this->configItems["itemCount"]["x"],
                $offsetHead,
                $this->configItems["itemCount"]["y"],
                $this->lineHeight,
                Strings::firstUpper($this->translator->translate("itemCount")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                }
            );

            // cenaJednotka
            $renderer->cell(
                $this->configItems["itemUnitPrice"]["x"],
                $offsetHead,
                $this->configItems["itemUnitPrice"]["y"],
                $this->lineHeight,
                Strings::firstUpper($this->translator->translate("itemUnitPrice")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

            // mezisoucet
            $renderer->cell(
                $this->configItems["itemUnitTotal"]["x"],
                $offsetHead,
                $this->configItems["itemUnitTotal"]["y"],
                $this->lineHeight,
                Strings::firstUpper($this->translator->translate("itemUnitTotal")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

        } else {

            // pocetJednotek
            $renderer->cell(
                $this->configItems["itemCount"]["x"],
                $offsetHead,
                $this->configItems["itemCount"]["y"],
                $this->lineHeight,
                Strings::firstUpper($this->translator->translate("itemCount")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                }
            );

            // cenaJednotka
            $renderer->cell(
                $this->configItems["itemUnitPrice"]["x"],
                $offsetHead,
                $this->configItems["itemUnitPrice"]["y"],
                $this->lineHeight,
                Strings::firstUpper($this->translator->translate("itemUnitPrice")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

            // mezisoucet
            $renderer->cell(
                $this->configItems["itemUnitTotal"]["x"],
                $offsetHead,
                $this->configItems["itemUnitTotal"]["y"],
                $this->lineHeight,
                Strings::firstUpper($this->translator->translate("itemUnitTotal")),
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );
        }
        
    }
   
    /**
     * Polozky
     * @param int $offset
     * @param array $items
     * @return int
     */
    protected function buildItems(int $offset, array $items): int {
        
        $renderer = $this->renderer;
        
        /**
         * @var Item $item
         */
        foreach ($items as $i => $item) {
            
            // radekBarva1
            $renderer->rect(0, $offset, $renderer->width(), $this->lineHeight,
                function (Settings $settings) use ($i) {
                    $settings->setFillDrawColor($i % 2 === 1 ? $this->evenColor : $this->oddColor);
            });
            
            // radekBarva2
            $renderer->rect(0, $offset + $this->lineHeight, $renderer->width(), 0.1,
                function (Settings $settings) {
                    $settings->setFillDrawColor($this->evenColor->darken(10));
            });

            // nazevPolozky
            $renderer->cell(
                $this->configItems["itemName"]["x"],
                $offset,
                $this->configItems["itemName"]["y"],
                $this->lineHeight,
                $item->getName(),
                function (Settings $settings) {
                    $settings->fontFamily = "sans";
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 7;
                }
            );

            // pokud je dodavatel VAT
            if ($this->company->hasTax()) {

                // dphSazba
                $renderer->cell(
                    $this->configItems["itemTax"]["x"],
                    $offset,
                    $this->configItems["itemTax"]["y"],
                    $this->lineHeight,
                    $item->getTax() . "%",
                    function (Settings $settings) {
                        $settings->fontFamily = "sans";
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_CENTER;
                        $settings->fontSize = 7;
                    }
                );

                // pocetJednotek
                $renderer->cell(
                    $this->configItems["itemCount"]["x"],
                    $offset,
                    $this->configItems["itemCount"]["y"],
                    $this->lineHeight,
                    $this->formatter->formatNumber($item->getAmount(), 0),
                    function (Settings $settings) {
                        $settings->fontFamily = "sans";
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_CENTER;
                        $settings->fontSize = 7;
                    }
                );

                // cenaJednotka
                $renderer->cell(
                    $this->configItems["itemUnitPrice"]["x"],
                    $offset,
                    $this->configItems["itemUnitPrice"]["y"],
                    $this->lineHeight,
                    $this->formatter->formatMoney(number: $item->getPrice()),
                    function (Settings $settings) {
                        $settings->fontFamily = "sans";
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_RIGHT;
                        $settings->fontSize = 7;
                    }
                );

                // mezisoucet
                $renderer->cell(
                    $this->configItems["itemUnitTotal"]["x"],
                    $offset,
                    $this->configItems["itemUnitTotal"]["y"],
                    $this->lineHeight,
                    $this->formatter->formatMoney(number: $item->getTotalPrice(calculator: $this->calculator), isStorno: $this->order->hasStorno()),
                    function (Settings $settings) {
                        $settings->fontFamily = "sans";
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_RIGHT;
                        $settings->fontSize = 7;
                    }
                );

                
            }  else {

                // pocetJednotek
                $renderer->cell(
                    $this->configItems["itemCount"]["x"],
                    $offset,
                    $this->configItems["itemCount"]["y"],
                    $this->lineHeight,
                    $this->formatter->formatNumber($item->getAmount(), 0),
                    function (Settings $settings) {
                        $settings->fontFamily = "sans";
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_CENTER;
                        $settings->fontSize = 7;
                    }
                );

                // cenaJednotka
                $renderer->cell(
                    $this->configItems["itemUnitPrice"]["x"],
                    $offset,
                    $this->configItems["itemUnitPrice"]["y"],
                    $this->lineHeight,
                    $this->formatter->formatMoney(number: $item->getPrice()),
                    function (Settings $settings) {
                        $settings->fontFamily = "sans";
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_RIGHT;
                        $settings->fontSize = 7;
                    }
                );

                // mezisoucet
                $renderer->cell(
                    $this->configItems["itemUnitTotal"]["x"],
                    $offset,
                    $this->configItems["itemUnitTotal"]["y"],
                    $this->lineHeight,
                    $this->formatter->formatMoney(number: $item->getTotalPrice(calculator: $this->calculator, useTax: false), isStorno: $this->order->hasStorno()),
                    function (Settings $settings) {
                        $settings->fontFamily = "sans";
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_RIGHT;
                        $settings->fontSize = 7;
                    }
                );

            }

            $offset += $this->lineHeight;
        }
        
        return $offset;
    }

    /**
     * @return void
     */
    protected function buildTotal(): void
    {
        
        $renderer = $this->renderer;
        $half = ($renderer->width() - 553) / 2;

        $offset = $renderer->y() + $this->lineHeight;
        
        // QRplatba - nebo uhrazeno - pouze pokud neni storno
        if(!$this->order->hasStorno()) {
            if($this->order->isPaid()) {
                
                if(!empty($paid = $this->schema->getPaidImage($this->translator->getCode()))) {
                    
                    $renderer->filterAlpha(0.5);
                    $renderer->addImage($paid, $half - 85, $offset + 30, 320);
                    $renderer->filterAlpha(1);
                }
                
            } else {
                
                if($this->schema->hasCodePath()) {
                    
                    $renderer->addImage($this->schema->getCodePath(), $half - 90, $offset, 140);
                }
                
            }
            
        }
        
        //  dph sazby
        if ($this->company->hasTax()) {
            
            if(!empty($vatLines = $this->order->getVatLines(true))) {
                foreach($vatLines as $key => $val) {
                    if($val > 0) {
                        
                        // dphSazba
                        $renderer->cell(420, $offset, $half, 15, sprintf("%s %s %s", $this->translator->translate("vatRate"), $key, "%"),
                            function (Settings $settings) {
                                $settings->fontFamily = "sans";
                                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                                $settings->align = $settings::ALIGN_LEFT;
                            });
                        
                        // dphHodnota
                        $renderer->cell(640, $offset, $half, 15, $this->formatter->formatMoney(number: $val, isStorno: $this->order->hasStorno()),
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
        
        // primarniBarva
        $renderer->rect(420, $offset, $renderer->width(), 24, 
            function (Settings $settings) {
                $settings->setFillDrawColor($this->primaryColor);
        });
        
        // celkovaCena - popisek
        $renderer->cell(425, $offset, $half, 24, $this->translator->translate("totalPrice"), 
            function (Settings $settings) {   
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontColor = $this->whiteColor;
                $settings->fontSize = 8;
        });
        
        // celkovaCena - hodnota
        $renderer->cell(600, $offset, $half + 40, 24, $this->formatter->formatMoney(number: $this->order->getTotalPrice(calculator: $this->calculator, useTax: $this->company->hasTax()), isStorno: $this->order->hasStorno()),
            function (Settings $settings) {
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
                $settings->fontSize = 9;
                $settings->align = $settings::ALIGN_RIGHT;
        });
        
        // stamp
        if($this->schema->hasStampPath()) {
            
            $renderer->addImage(logo: $this->schema->getStampPath(), x: 500 + $half, y: $offset + 42, width: 148);
        }
    }


    
}
