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
    private int $lineHeight = 35;

    /**
     * @var array
     */
    private array $configItems = [
        "itemCatalog" => [
            "x" => 40,
            "y" => 100
        ],
        "itemName" => [
            "x" => 140,
            "y" => 290
        ],
        "itemTax" => [
            "x" => 420,
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
        $this->renderer->setDocumentMeta(
            data: [
                "title" => Strings::firstUpper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate(message: $this->customName) : $this->customName) . " " .$this->order->getNumber(),
                "subject" => Strings::firstUpper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate(message: $this->customName) : $this->customName) . " " .$this->order->getNumber(),
                "author" => $this->translator->translate(message: "metaAuthor")
            ]
        );
        
        // fonty
        $this->renderer->registerDefaultFont();
        
        // strankovani
        $paginator = new Paginator(items: $this->order->getItems(), itemsPerPage:  $this->itemsPerPage);
       
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
            $renderer->cell(
                x: 0,
                y: - 40,
                width: $this->renderer->width(),
                height: null,
                text: Strings::firstUpper($this->company->getFileNumberDesc() ?? $this->translator->translate(message: "footerText")),
                setCallback: function (Settings $settings) {
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->fontSize = 6;
                    $settings->align = $settings::ALIGN_CENTER;
                }
            );
            
            // paticka
            $this->buildFooter(paginator: $paginator);
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
        $renderer->polygon(
            points: [
                0, 0,
                0, 100,
                396, 100,
                396, 0,
            ],
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->whiteColor);
            }
        );

        // logo
        if($this->schema->hasLogoPath()) {

            $renderer->addImage(logo: $this->schema->getLogoPath(), x: 40, y:  40, width:  374/2);
        }

        // barevny polygon
        $renderer->polygon(
            points: [
                396, 0,
                396, 100,
                $renderer->width(), 100,
                $renderer->height(), 0,
            ],
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->primaryColor);
            }
        );
        
        // nazev
        $renderer->cell(
            x: 420,
            y: 45,
            width: 1,
            height: null,
            text: Strings::upper($this->customName && $this->translator->hasMessage(message: $this->customName) ? $this->translator->translate(message: $this->customName) : $this->customName) . " " .$this->order->getNumber(),
            setCallback: function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = ($this->order->hasStorno() ? 16 : 22);
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            }
        );
        
        // popis
        $renderer->cell(
            x: 420,
            y: 70,
            width: 1,
            height: null,
            text: Strings::upper($this->translator->translate(message: "documentDescription")),
            setCallback: function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            }
        );
    }

    /**
     * @return void
     */
    protected function buildSupplierBox(): void
    {
        
        $renderer = $this->renderer;
        
        // dodavatelPopis
        $renderer->cell(
            x: 40,
            y: 120,
            width: 1,
            height: null,
            text: Strings::upper($this->translator->translate(message: "supplierName")),
            setCallback: function (Settings $settings) {
                $settings->fontSize = 6;
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            }
        );

        // dodavalJmeno
        $renderer->cell(
            x: 40,
            y: 140,
            width: 1,
            height: null,
            text: $this->company->getName(),
            setCallback: function (Settings $settings) {
                $settings->fontSize = 10;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            }
        );

        // adresaUlice
        $renderer->cell(
            x: 40,
            y: 160,
            width: 1,
            height: null,
            text: $this->company->getAddress(),
            setCallback: function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            }
        );

        // adresaMestoPsc
        $renderer->cell(
            x: 40,
            y: 175,
            width: 1,
            height: null,
            text: $this->company->getZip() . ' ' . $this->company->getTown(),
            setCallback: function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            }
        );

        
        $positionY  = 210;
        $multiplier = 0;
        
        // dodavatelICO
        if ($this->company->getTin()) {

            // dodavatelIcoNazev
            $renderer->cell(
                x: 40,
                y: $positionY,
                width: 1,
                height: null,
                text: $this->translator->translate(message: "vatName"),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );

            // dodavatelIcoHodnota
            $renderer->cell(
                x: 380,
                y: $positionY,
                width: 1,
                height: null,
                text: $this->company->getTin(),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );


            $multiplier++;
        }
        
        // dodavatelDIC
        if ($this->company->hasTax()) {

            // dodavatelDICNazev
            $renderer->cell(
                x: 40,
                y: $positionY + ($multiplier * 20),
                width: 1,
                height: null,
                text: $this->translator->translate(message: "vaTinName"),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontColor = $this->fontColor;
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );

            // dodavatelDICHodnota
            $renderer->cell(
                x: 380,
                y: $positionY + ($multiplier * 20),
                width: 1,
                height: null,
                text: $this->company->getTin(),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

        } else {

            // dodavatelDICNazev
            $renderer->cell(
                x: 40,
                y: $positionY + ($multiplier * 20),
                width: 1,
                height: null,
                text: $this->translator->translate(message: "vaTinName"),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                }
            );

            // dodavatelDICHodnota
            $renderer->cell(
                x: 380,
                y: $positionY + ($multiplier * 20),
                width: 1,
                height: null,
                text: $this->translator->translate(message: "notTax"),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

        }
    }

    /**
     * @return void
     */
    protected function buildSubscriberBox(): void
    {
        
        $renderer = $this->renderer;

        // odberatelNazev
        $renderer->cell(
            x: 420,
            y: 120,
            width: 1,
            height: null,
            text: Strings::upper($this->translator->translate(message: "subscriberLabel")),
            setCallback: function (Settings $settings) {
                $settings->fontSize = 6;
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            }
        );

        // odberatelJmeno
        $renderer->cell(
            x: 420,
            y: 140,
            width: 1,
            height: null,
            text: $this->customerBilling->getName(),
            setCallback: function (Settings $settings) {
                $settings->fontSize = 10;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            }
        );

        // odberatelUlice
        $renderer->cell(
            x: 420,
            y: 160,
            width: 1,
            height: null,
            text: $this->customerBilling->getAddress(),
            setCallback: function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            }
        );

        // odberatelMestoPSC
        $renderer->cell(
            x: 420,
            y: 175,
            width: 1,
            height: null,
            text: $this->customerBilling->getZip() . " " . $this->customerBilling->getTown(),
            setCallback: function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            }
        );


        $positionY  = 210;
        $multiplier = 0;
        
        // odberatelICO
        if ($this->customerBilling->getTin()) {
            
            // odberatelIcoNazev
            $renderer->cell(
                x: 420,
                y: $positionY,
                width: 1,
                height: null,
                text: $this->translator->translate(message: "vatName"),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                }
            );
            
            // odberatelIcoHodnota
            $renderer->cell(
                x: 760,
                y: $positionY,
                width: 1,
                height: null,
                text: $this->customerBilling->getTin(),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );
            
            $multiplier++;
        }
        
        // odberatelDIC
        if ($this->customerBilling->getVaTin()) {
            
            // odberatelDicNazev
            $renderer->cell(
                x: 420,
                y: $positionY + ($multiplier * 20),
                width: 1,
                height: null,
                text: $this->translator->translate(message: "vaTinName"),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                }
            );
            
            // odberatelDicHodnota
            $renderer->cell(
                x: 760,
                y: $positionY + ($multiplier * 20),
                width: 1,
                height: null,
                text: $this->customerBilling->getVaTin(),
                setCallback: function (Settings $settings) {
                    $settings->fontSize = 8;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

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
        $renderer->rect(
            x: 0,
            y: 245,
            width: $half,
            height: 100,
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->primaryColor);
            }
        );
        
        // sedy pruh
        $renderer->rect(
            x: $half,
            y: 245,
            width: $half,
            height: 100,
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->lineColor);
            }
        );

        // vlevo - platba
        if ($this->order->getPayment()->getPaymentName()) {

            // platbaNazev
            $renderer->cell(
                x: 40,
                y: 265,
                width: 1,
                height: null,
                text: $this->translator->translate(message: "paymentName"),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->whiteColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );

            // platbaHodnota
            $renderer->cell(
                x: 380,
                y: 265,
                width: 1,
                height: null,
                text: $this->order->getPayment()->getPaymentName(),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                }
            );
        }

        // vlevo - bankovni ucet
        if ($this->order->getAccount()->getBank()) {

            // ucetNazev
            $renderer->cell(
                x: 40,
                y: 285,
                width: 1,
                height: null,
                text: $this->translator->translate(message: "bankAccount"),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->whiteColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );

            // ucetHodnota
            $renderer->cell(
                x: 380,
                y: 285,
                width: 1,
                height: null,
                text: $this->order->getAccount()->getBank(),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                }
            );
        }

        // vlevo - iban
        if ($this->order->getAccount()->isValid()) {

            // ibanNazev
            $renderer->cell(
                x: 40,
                y: 305,
                width: 1,
                height: null,
                text: Strings::upper($this->translator->translate(message: "ibanName")),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->whiteColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );

            // ibanHodnota
            $renderer->cell(
                x: 380,
                y: 305,
                width: 1,
                height: null,
                text: $this->order->getAccount()->getIBan(format: true),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                }
            );
        }

        // vlevo - swift
        if ($this->order->getAccount()->isValid()) {

            // swiftNazev
            $renderer->cell(
                x: 40,
                y: 325,
                width: 1,
                height: null,
                text: $this->translator->translate(message: "swiftName"),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->whiteColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );

            // swiftHodnota
            $renderer->cell(
                x: 380,
                y: 325,
                width: 1,
                height: null,
                text: $this->order->getAccount()->getSwift(),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                }
            );

        }

        // vpravo - variabilni symbol
        if ($this->order->getPayment()->getVariableSymbol() || $this->order->hasStorno()) {
            
            // vsNazev
            $renderer->cell(
                x: 420,
                y: 265,
                width: 1,
                height: null,
                text: $this->translator->translate(message: ($this->order->hasStorno() ? "documentInvoiceId" : "varSymbol")),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );
            
            // vsHodnota - storno ma Id faktury, jinak VS cislo
            $renderer->cell(
                x: 760,
                y: 265,
                width: 1,
                height: null,
                text: ($this->order->hasStorno() ? ($this->order->getExtraData()["stornoInvoiceId"] ?? $this->order->getPayment()->getVariableSymbol()) : $this->order->getPayment()->getVariableSymbol()),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                }
            );
        }
        
        // vpravo - datum vystaveni
        if ($this->order->getAccount()->getBank()) {
            
            // vystaveniNazev
            $renderer->cell(
                x: 420,
                y: 285,
                width: 1,
                height: null,
                text: $this->translator->translate(message: "date"),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );
            
            // vystaveniHodnota
            $renderer->cell(
                x: 760,
                y: 285,
                width: 1,
                height: null,
                text: $this->formatter->formatDate(date: $this->order->getCreated()),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                }
            );
        }
        
        // vpravo - datum splatnosti
        if ($this->order->getDueDate() !== null) {
            
            // splatnostNazev
            $renderer->cell(
                x: 420,
                y: 305,
                width: 1,
                height: null,
                text: $this->translator->translate(message: "dueDate"),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
            );
            
            // splatnostHodnota
            $renderer->cell(
                x: 760,
                y: 305,
                width: 1,
                height: null,
                text: $this->formatter->formatDate($this->order->getDueDate()),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                }
            );
        }
        
        // vpravo - datum zdan. plneni
        if ($this->order->getDueDate() !== null) {
            
            // plneniNazev
            $renderer->cell(
                x: 420,
                y: 325,
                width: 1,
                height: null,
                text: $this->translator->translate(message: "taxDate"),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 10;
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                }
                );
            
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
        $renderer->rect(
            x: $renderer->width() / 2 - 1,
            y: 102,
            width: 1,
            height: 140,
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->lineColor);
            }
        );
    }

    /**
     * @param int $offsetHead
     * @param int $offsetBody
     * @return void
     */
    protected function buildBody(int $offsetHead, int $offsetBody): void
    {
        
        $renderer = $this->renderer;
        
        $renderer->rect(
            x: 0,
            y: $offsetHead,
            width: $renderer->width(),
            height: 30,
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->evenColor);
        });
        
        $renderer->rect(
            x: 0,
            y: $offsetHead + $offsetBody - 1,
            width: $renderer->width(),
            height: 1,
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->primaryColor);
        });
        
        // vypis polozek
        $renderer->cell(
            x: 40,
            y: $offsetHead - 32,
            width: 480,
            height: 30,
            text: Strings::firstUpper(($this->order->hasStorno() ? sprintf($this->translator->translate(message: "bodyBeforeStorno"), $this->order->getExtraData()["stornoInvoiceId"] ?? "") : $this->translator->translate(message: "bodyBefore"))),
            setCallback: function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            });

        // kodPozky
        $renderer->cell(
            x: $this->configItems["itemCatalog"]["x"],
            y: $offsetHead,
            width: $this->configItems["itemCatalog"]["y"],
            height: $this->lineHeight,
            text: Strings::firstUpper($this->translator->translate(message: "itemCatalog")),
            setCallback: function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            }
        );

        // nazevPolozky
        $renderer->cell(
            x: $this->configItems["itemName"]["x"],
            y: $offsetHead,
            width: $this->configItems["itemName"]["y"],
            height: $this->lineHeight,
            text: Strings::firstUpper($this->translator->translate(message: "itemName")),
            setCallback: function (Settings $settings) {
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
                x: $this->configItems["itemTax"]["x"],
                y: $offsetHead,
                width: $this->configItems["itemTax"]["y"],
                height: $this->lineHeight,
                text: Strings::firstUpper($this->translator->translate(message: "itemTax")),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                }
            );

            // pocetJednotek
            $renderer->cell(
                x: $this->configItems["itemCount"]["x"],
                y: $offsetHead,
                width: $this->configItems["itemCount"]["y"],
                height: $this->lineHeight,
                text: Strings::firstUpper($this->translator->translate(message: "itemCount")),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                }
            );

            // cenaJednotka
            $renderer->cell(
                x: $this->configItems["itemUnitPrice"]["x"],
                y: $offsetHead,
                width: $this->configItems["itemUnitPrice"]["y"],
                height: $this->lineHeight,
                text: Strings::firstUpper($this->translator->translate(message: "itemUnitPrice")),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

            // mezisoucet
            $renderer->cell(
                x: $this->configItems["itemUnitTotal"]["x"],
                y: $offsetHead,
                width: $this->configItems["itemUnitTotal"]["y"],
                height: $this->lineHeight,
                text: Strings::firstUpper($this->translator->translate(message: "itemUnitTotal")),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

        } else {

            // pocetJednotek
            $renderer->cell(
                x: $this->configItems["itemCount"]["x"],
                y: $offsetHead,
                width: $this->configItems["itemCount"]["y"],
                height: $this->lineHeight,
                text: Strings::firstUpper($this->translator->translate(message: "itemCount")),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                }
            );

            // cenaJednotka
            $renderer->cell(
                x: $this->configItems["itemUnitPrice"]["x"],
                y: $offsetHead,
                width: $this->configItems["itemUnitPrice"]["y"],
                height: $this->lineHeight,
                text: Strings::firstUpper($this->translator->translate(message: "itemUnitPrice")),
                setCallback: function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                }
            );

            // mezisoucet
            $renderer->cell(
                x: $this->configItems["itemUnitTotal"]["x"],
                y: $offsetHead,
                width: $this->configItems["itemUnitTotal"]["y"],
                height: $this->lineHeight,
                text: Strings::firstUpper($this->translator->translate(message: "itemUnitTotal")),
                setCallback: function (Settings $settings) {
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
            $renderer->rect(
                x: 0,
                y: $offset,
                width: $renderer->width(),
                height: $this->lineHeight,
                setCallback: function (Settings $settings) use ($i) {
                    $settings->setFillDrawColor(color: $i % 2 === 1 ? $this->evenColor : $this->oddColor);
            });
            
            // radekBarva2
            $renderer->rect(
                x:0,
                y: $offset + $this->lineHeight,
                width: $renderer->width(),
                height: 0.1,
                setCallback: function (Settings $settings) {
                    $settings->setFillDrawColor(color: $this->evenColor->darken(percentage: 10));
            });

            // katalogoveCislo
            $catalogName   = Strings::upper($item->getCatalogName());
            $catalogLine   = $this->lineHeight;
            $catalogWidth  = $this->configItems["itemCatalog"]["y"];
            $catalogSize   = $renderer->textWidth(text: $catalogName);
            $catalogLength = Strings::length($catalogName);
            $catalogHeight = $this->lineHeight;

            if($renderer->textWidth(text:$catalogName) > $catalogWidth ) {

                $catalogHeight = $catalogHeight / 2;

                $i = 0;

                do {

                    $catalogName = Strings::substring($catalogName, 0, $catalogLength - $i++);

                } while (($renderer->textWidth(text: $catalogName)/2) > $catalogWidth);

            }

            // kodPozky
            $renderer->cell(
                x: $this->configItems["itemCatalog"]["x"],
                y: $offset,
                width: $this->configItems["itemCatalog"]["y"],
                height: $catalogHeight,
                text: $catalogName,
                setCallback: function (Settings $settings) {
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->fontSize = 7;
                    $settings->align = $settings::ALIGN_LEFT;
                }
            );

            // nazevPolozky
            $itemName   = Strings::firstUpper($item->getName());
            $itemLine   = $this->lineHeight;
            $itemWidth  = $this->configItems["itemName"]["y"];
            $itemSize   = $renderer->textWidth(text: $itemName);
            $itemLength = Strings::length($itemName);
            $itemHeight = $this->lineHeight;

            if($renderer->textWidth(text: $itemName) > $itemWidth) {

                $itemHeight = $itemHeight / 2;
                $i = 0;

                do {

                    $itemName = Strings::substring($itemName, 0, $itemLength - $i++);

                } while (($renderer->textWidth(text: $itemName)/2) > $itemWidth);

            }

            // nazevPolozky
            $renderer->cell(
                x: $this->configItems["itemName"]["x"],
                y: $offset,
                width: $this->configItems["itemName"]["y"],
                height: $itemHeight,
                text: $itemName,
                setCallback: function (Settings $settings) {
                    $settings->fontColor = $this->grayColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->fontSize = 7;
                    $settings->align = $settings::ALIGN_LEFT;
                }
            );

            // pokud je dodavatel VAT
            if ($this->company->hasTax()) {

                // dphSazba
                $renderer->cell(
                    x: $this->configItems["itemTax"]["x"],
                    y: $offset,
                    width: $this->configItems["itemTax"]["y"],
                    height: $this->lineHeight,
                    text: $item->getTax() . "%",
                    setCallback: function (Settings $settings) {
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_CENTER;
                        $settings->fontSize = 7;
                    }
                );

                // pocetJednotek
                $renderer->cell(
                    x: $this->configItems["itemCount"]["x"],
                    y: $offset,
                    width: $this->configItems["itemCount"]["y"],
                    height: $this->lineHeight,
                    text: $this->formatter->formatNumber($item->getAmount(), 0) . "x",
                    setCallback: function (Settings $settings) {
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_CENTER;
                        $settings->fontSize = 7;
                    }
                );

                // cenaJednotka
                $renderer->cell(
                    x: $this->configItems["itemUnitPrice"]["x"],
                    y: $offset,
                    width: $this->configItems["itemUnitPrice"]["y"],
                    height: $this->lineHeight,
                    text: $this->formatter->formatMoney(number: $item->getUnitPrice()),
                    setCallback: function (Settings $settings) {
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_RIGHT;
                        $settings->fontSize = 7;
                    }
                );

                // mezisoucet
                $renderer->cell(
                    x: $this->configItems["itemUnitTotal"]["x"],
                    y: $offset,
                    width: $this->configItems["itemUnitTotal"]["y"],
                    height: $this->lineHeight,
                    text: $this->formatter->formatMoney(number: $item->getTotalPrice(calculator: $this->calculator), isStorno: $this->order->hasStorno()),
                    setCallback: function (Settings $settings) {
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_RIGHT;
                        $settings->fontSize = 7;
                    }
                );

                
            }  else {

                // pocetJednotek
                $renderer->cell(
                    x: $this->configItems["itemCount"]["x"],
                    y: $offset,
                    width: $this->configItems["itemCount"]["y"],
                    height: $this->lineHeight,
                    text: $this->formatter->formatNumber($item->getAmount(), 0) . "x",
                    setCallback: function (Settings $settings) {
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_CENTER;
                        $settings->fontSize = 7;
                    }
                );

                // cenaJednotka
                $renderer->cell(
                    x: $this->configItems["itemUnitPrice"]["x"],
                    y: $offset,
                    width: $this->configItems["itemUnitPrice"]["y"],
                    height: $this->lineHeight,
                    text: $this->formatter->formatMoney(number: $item->getPrice()),
                    setCallback: function (Settings $settings) {
                        $settings->fontColor = $this->fontColor;
                        $settings->fontStyle = $settings::FONT_STYLE_NONE;
                        $settings->align = $settings::ALIGN_RIGHT;
                        $settings->fontSize = 7;
                    }
                );

                // mezisoucet
                $renderer->cell(
                    x: $this->configItems["itemUnitTotal"]["x"],
                    y: $offset,
                    width: $this->configItems["itemUnitTotal"]["y"],
                    height: $this->lineHeight,
                    text: $this->formatter->formatMoney(number: $item->getTotalPrice(calculator: $this->calculator, useTax: false), isStorno: $this->order->hasStorno()),
                    setCallback: function (Settings $settings) {
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
                    
                    $renderer->filterAlpha(alpha: 0.5);
                    $renderer->addImage(logo: $paid, x: $half - 85, y: $offset + 30, width: 320);
                    $renderer->filterAlpha(alpha: 1);
                }
                
            } else {
                
                if($this->schema->hasCodePath()) {
                    
                    $renderer->addImage(logo: $this->schema->getCodePath(), x: $half - 90, y: $offset, width: 140);
                }
                
            }
            
        }

        // dphSouhrny
        if($this->company->hasTax()) {

            //  dph sazby
            $vatLines = $this->order->getVatLines(useTax: $this->company->hasTax());

            //  dph sazby
            if(count($vatLines) > 0) {

                $dphBase = 0;

                foreach($vatLines as $key => $val) {
                    $dphBase += $val;

                    // dphSazba
                    $renderer->cell(
                        x: 420,
                        y: $offset,
                        width: $half,
                        height: 15,
                        text: sprintf("%s %s %s", $this->translator->translate(message: "vatRate"), $key, "%"),
                        setCallback: function (Settings $settings) {
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->align = $settings::ALIGN_LEFT;
                        }
                    );

                    // dphHodnota
                    $renderer->cell(
                        x: 640,
                        y: $offset,
                        width: $half,
                        height: 15,
                        text: $this->formatter->formatMoney(number: $val, formatDecimal: true),
                        setCallback: function (Settings $settings) {
                            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                            $settings->fontColor = $this->fontColor;
                            $settings->align = $settings::ALIGN_RIGHT;
                        }
                    );

                    $offset += 15;
                }

                // bez DPH celkem
                if((float) $dphBase > 0) {

                    $offset += 5;

                    // dphSazba
                    $renderer->cell(
                        x: 420,
                        y: $offset,
                        width: $half,
                        height: 15,
                        text: $this->translator->translate(message: "summaryTaxBase"),
                        setCallback: function (Settings $settings) {
                            $settings->fontColor = $this->fontColor;
                            $settings->fontStyle = $settings::FONT_STYLE_NONE;
                            $settings->align = $settings::ALIGN_LEFT;
                        }
                    );

                    // dphHodnota
                    $renderer->cell(
                        x: 640,
                        y: $offset,
                        width: $half,
                        height: 15,
                        text: $this->formatter->formatMoney(number: ($this->order->getTotalPrice(calculator: $this->calculator, useTax: $this->company->hasTax()) - $dphBase), isTotal: false),
                        setCallback: function (Settings $settings) {
                            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                            $settings->fontColor = $this->fontColor;
                            $settings->align = $settings::ALIGN_RIGHT;
                        }
                    );

                    $offset += 15;
                }

                if((float) $dphBase > 0) {

                    $totalGoods = $this->order->getTotalPrice(calculator: $this->calculator, useTax: $this->company->hasTax());

                    if($this->order->getPayment()->getCurrency() === "EUR") {

                        $articleTotal = round(num: $totalGoods, precision: 1);

                    } else {

                        $articleTotal = round(num: $totalGoods);
                    }

                    $articleHaller = round(num: ($articleTotal - $totalGoods), precision: 3);


                    if($articleHaller !== 0.0) {

                        // dphSazba
                        $renderer->cell(
                            x: 420,
                            y: $offset,
                            width: $half,

                            height: 15,
                            text: $this->translator->translate(message: "summaryRounding"),
                            setCallback: function (Settings $settings) {
                                $settings->fontColor = $this->fontColor;
                                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                                $settings->align = $settings::ALIGN_LEFT;
                            }
                        );

                        // dphHodnota
                        $renderer->cell(
                            x: 640,
                            y: $offset,
                            width: $half,
                            height: 15,
                            text: $this->formatter->formatMoney(number: $articleHaller, isTotal: false),
                            setCallback: function (Settings $settings) {
                                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                                $settings->fontColor = $this->fontColor;
                                $settings->align = $settings::ALIGN_RIGHT;
                            }
                        );

                        $offset += 15;
                    }
                }

                $offset += 10;
            }
        }

        
        // primarniBarva
        $renderer->rect(
            x: 420,
            y: $offset,
            width: $renderer->width(),
            height: 24,
            setCallback: function (Settings $settings) {
                $settings->setFillDrawColor(color: $this->primaryColor);
            }
        );
        
        // celkovaCena - popisek
        $renderer->cell(
            x: 425,
            y: $offset,
            width: $half,
            height: 24,
            text: $this->translator->translate(message: "totalPrice"),
            setCallback: function (Settings $settings) {
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontColor = $this->whiteColor;
                $settings->fontSize = 8;
            }
        );
        
        // celkovaCena - hodnota
        $renderer->cell(
            x: 600,
            y: $offset,
            width: $half + 40,
            height: 24,
            text: $this->formatter->formatMoney(number: $this->order->getTotalPrice(calculator: $this->calculator, useTax: $this->company->hasTax()), isStorno: $this->order->hasStorno()),
            setCallback: function (Settings $settings) {
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
                $settings->fontSize = 9;
                $settings->align = $settings::ALIGN_RIGHT;
            }
        );
        
        // stamp
        if($this->schema->hasStampPath()) {
            
            $renderer->addImage(logo: $this->schema->getStampPath(), x: 500 + $half, y: $offset + 42, width: 148);
        }
    }

}
