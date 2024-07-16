<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Standard;
use Lemonade\Pdf\BasicFormatter;
use Lemonade\Pdf\Calculators\FloatCalculator;
use Lemonade\Pdf\Components\Paginator;
use Lemonade\Pdf\Renderers\Color;
use Lemonade\Pdf\Renderers\PDFRenderer;
use Lemonade\Pdf\Renderers\Settings;
use Lemonade\Pdf\BasicTranslator;
use Lemonade\Pdf\Data\Company;
use Lemonade\Pdf\Data\Customer;
use Lemonade\Pdf\Data\Order;
use Lemonade\Pdf\Data\Schema;
use Lemonade\Pdf\Data\Item;
use Nette\Utils\Strings;
use Nette\Utils\DateTime;


/**
 * PdfCustomerOrder
 * \Lemonade\Pdf\Templates\PdfCustomerOrder
 */
class PdfCustomerOrder implements PdfTemplateInterface
{

    /**
     * Pocet zaznamu na stranku
     * @var integer
     */
    const ITEMS_PER_PAGE = 15;

    /**
     * @var Color|null
     */
    private ?Color $primaryColor;

    /**
     * @var Color|null
     */
    private ?Color $fontColor;

    /**
     * @var Color|null
     */
    private ?Color $evenColor;

    /**
     * @var Color|null
     */
    private ?Color $oddColor;

    /**
     * @var Color|null
     */
    private ?Color $lineColor;

    /**
     * @var Color|null
     */
    private ?Color $whiteColor;

    /**
     * @var Color|null
     */
    private ?Color $grayColor;

    /**
     * @var PDFRenderer|null
     */
    private ?PDFRenderer $renderer;

    /**
     * @var Customer|null
     */
    private ?Customer $customerBilling;

    /**
     * @var Customer|null
     */
    private ?Customer $customerDelivery;

    /**
     * @var Order|null
     */
    private ?Order $order;

    /**
     * @var Company|null
     */
    private ?Company $company;

    /**
     * @var FloatCalculator|null
     */
    private ?FloatCalculator $calculator;

    /**
     *
     * @var int
     */
    private int $itemsPerPage = self::ITEMS_PER_PAGE;

    /**
     * @var string
     */
    private string $creatorLink = "https://core1.agency";

    /**
     * @var array
     */
    public $onBuild = [];

    /**
     * @param BasicTranslator $translator
     * @param BasicFormatter $formatter
     * @param Schema $schema
     * @param string|bool|null $customName
     */
    public function __construct(

        protected readonly BasicTranslator $translator = new BasicTranslator(),
        protected readonly BasicFormatter  $formatter = new BasicFormatter(),
        protected readonly Schema          $schema = new Schema(),
        public string|bool|null            $customName = false

    ) {

        $this->primaryColor = $this->schema->getPrimaryColor();
        $this->fontColor = $this->schema->getFontColor();
        $this->evenColor = $this->schema->getEvenColor();
        $this->whiteColor = $this->schema->getWhiteColor();
        $this->grayColor = $this->schema->getGrayColor();
        $this->oddColor = $this->schema->getOddColor();
        $this->lineColor = $this->schema->getLineColor();
        $this->customName = $customName ?: false;
        
    }
    
    
    /**
     * Nastavuje pocet stranek
     * @param int $itemsPerPage
     * @return self
     */
    public function setItemsPerPage(int $itemsPerPage): self {
        
        $this->itemsPerPage = $itemsPerPage;
        
        return $this;
    }


    /**
     * @param FloatCalculator $calculator
     * @param PDFRenderer $renderer
     * @param Company $company
     * @param Customer $billing
     * @param Order $order
     * @param Customer|null $delivery
     * @return string
     */
    public function buildFromTemplate(

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
            $this->buildCustomerBillingInformation();
            $this->buildCustomerDeliveryInformation();
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
            
            foreach ($this->onBuild as $build) {
                
                $build($paginator, $this->renderer, $this->formatter);
            }
        }
        
        return $this->renderer->output();
    }
    
    
    
    
    
    
    
    /**
     * Hlavicka
     */
    protected function buildHeader() {
        
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
        
        // nazev-webu
        if(!empty($web = $this->order->getWebName())) {
            $renderer->cell(420, 20, 1, null, $web, function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fontSize = 6;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontColor = $this->whiteColor;
            });
        }
        
        // nazev
        $renderer->cell(420, 40, 1, null, Strings::upper($this->customName && $this->translator->hasMessage($this->customName) ? $this->translator->translate($this->customName) : $this->customName) . " " . $this->order->getNumber(), function (Settings $settings) {
            $settings->align = $settings::ALIGN_LEFT;
            $settings->fontFamily = "sans";
            $settings->fontSize = 20;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->fontColor = $this->whiteColor;
        });
        
        // obchodnik - popis
        $renderer->cell(420, 63, 1, null, $this->translator->translate("supplierName"), function (Settings $settings) {
            $settings->align = $settings::ALIGN_LEFT;
            $settings->fontSize = 5;
            $settings->fontColor = $this->whiteColor;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
        });
            
        // obchodnik - jmeno
        $renderer->cell(420, 73, 1, null, $this->company->getName(), function (Settings $settings) {
            $settings->align = $settings::ALIGN_LEFT;
            $settings->fontFamily = "sans";
            $settings->fontSize = 9;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->fontColor = $this->whiteColor;
        });
        
        // obchodnik - email
        $renderer->cell(420, 87, 1, null, ($this->company->getEmail() ?? ""), function (Settings $settings) {
            $settings->align = $settings::ALIGN_LEFT;
            $settings->fontFamily = "sans";
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->fontColor = $this->whiteColor;
        });
        
        // obchodnik - telefon
        $renderer->cell(720, 87, 1, null, ($this->company->getPhone() ?? ""), function (Settings $settings) {
            $settings->align = $settings::ALIGN_RIGHT;
            $settings->fontFamily = "sans";
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->fontColor = $this->whiteColor;
        });
        
    }
    
    
    /**
     * Fakturacni adresa
     */
    protected function buildCustomerBillingInformation() {
        
        $renderer = $this->renderer;
        
        // popisek
        $renderer->cell(40, 120, 1, null, $this->translator->translate("billingAddress"), function (Settings $settings) {
            $settings->align = $settings::ALIGN_LEFT;
            $settings->fontSize = 6;
            $settings->fontColor = $this->grayColor;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
        });
        
        // jmeno
        $renderer->cell(40, 140, 1, null, $this->customerBilling->getName(), function (Settings $settings) {
            $settings->fontSize = 10;
            $settings->fontColor = $this->fontColor;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
        });
        
        $renderer->cell(40, 160, 1, null, $this->customerBilling->getAddress(), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
        });
                
        // mesto psc
        $renderer->cell(40, 175, 1, null, $this->customerBilling->getZip() . " " . $this->customerBilling->getTown(), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
        });
                
        // IC
        $renderer->cell(40,  200, 1, null, $this->translator->translate("vatName"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->align = $settings::ALIGN_LEFT;
        });
        
        $renderer->cell(380,  200, 1, null, ($this->customerBilling->getTin() ?? "--------"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->align = $settings::ALIGN_RIGHT;
        });
        
        // DIC
        $renderer->cell(40,  220, 1, null, $this->translator->translate("vaTinName"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->align = $settings::ALIGN_LEFT;
        });

        $renderer->cell(380,  220, 1, null, ($this->customerBilling->getVaTin() ?? "--------"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->align = $settings::ALIGN_RIGHT;
        });
        
        // email
        $renderer->cell(40,  250, 1, null, $this->translator->translate("email"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->align = $settings::ALIGN_LEFT;
        });
        
        $renderer->cell(380,  250, 1, null, ($this->customerBilling->getEmail() ?? ""), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->align = $settings::ALIGN_RIGHT;
        });
        
        // telefon
        $renderer->cell(40,  265, 1, null, $this->translator->translate("phone"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->align = $settings::ALIGN_LEFT;
        });

        $renderer->cell(380,  265, 1, null, ($this->customerBilling->getPhone() ?? ""), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->align = $settings::ALIGN_RIGHT;
        });
        
    }
    
    
    
    /**
     * Dorucovaci adresa
     */
    protected function buildCustomerDeliveryInformation() {
        
        $renderer = $this->renderer;
        
        // nazev
        $renderer->cell(420, 120, 1, null, $this->translator->translate("deliveryAddress"), function (Settings $settings) {
            $settings->fontSize = 6;
            $settings->align = $settings::ALIGN_LEFT;
            $settings->fontColor = $this->grayColor;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
        });

        // jmeno
        if(!empty($this->customerDelivery->getDeliveryCompany())) {
            
            $renderer->cell(420, 140, 1, null, $this->customerDelivery->getDeliveryCompany(), function (Settings $settings) {
                $settings->fontSize = 10;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
            
            $renderer->cell(420, 155, 1, null, $this->customerDelivery->getName(), function (Settings $settings) {
                $settings->fontSize = 8;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
        } else {
            
            $renderer->cell(420, 140, 1, null, $this->customerDelivery->getName(), function (Settings $settings) {
                $settings->fontSize = 10;
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        }
        
        // ulice
        $renderer->cell(420, $renderer->y() + 20, 1, null, $this->customerDelivery->getAddress(), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
        });
        
        // mesto psc
        $renderer->cell(420, $renderer->y() + 15, 1, null, $this->customerDelivery->getZip() . " " . $this->customerDelivery->getTown(), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
        });
        
        
        // email
        $renderer->cell(420, 250, 1, null, $this->translator->translate("email"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->align = $settings::ALIGN_LEFT;
        });
        
        $renderer->cell(760,  250, 1, null, ($this->customerDelivery->getEmail() ?? "----"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->align = $settings::ALIGN_RIGHT;
        });
        
        
        // telefon
        $renderer->cell(420,  265, 1, null, $this->translator->translate("phone"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->align = $settings::ALIGN_LEFT;
        });

        $renderer->cell(760,  265, 1, null, ($this->customerDelivery->getPhone() ?? "----"), function (Settings $settings) {
            $settings->fontSize = 8;
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->align = $settings::ALIGN_RIGHT;
        });
        
        
    }
    
    
    /**
     * Popis platby
     */
    protected function buildPaymentBox() {
        
        $renderer = $this->renderer;
        $half = ($renderer->width()) / 2;
        
        // barevny pruh
        $renderer->rect(0, 280, $half, 80, function (Settings $settings) {
            $settings->setFillDrawColor($this->primaryColor);
        });
        
        // sedy pruh
        $renderer->rect($half, 280, $half, 80, function (Settings $settings) {
            $settings->setFillDrawColor($this->lineColor);
        });
        
        
        // vlevo - bankovni ucet
        if ($this->order->getAccount()->getBank()) {
            
            // ucetNazev
            $renderer->cell(40, 300, 1, null, $this->translator->translate("bankAccount"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // ucetHodnota
            $renderer->cell(380, 300, 1, null, $this->order->getAccount()->getBank(), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        }
        
        // vlevo - iban
        if ($this->order->getAccount()->isValid()) {
            
            // ibanNazev
            $renderer->cell(40, 320, 1, null, Strings::upper($this->translator->translate("ibanName")), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // ibanHodnota
            $renderer->cell(380, 320, 1, null, $this->order->getAccount()->getIBan(true), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        }
        
        
        // vlevo - platba
        if ($this->order->getPayment()->getPaymentName()) {
            
            // platbaNazev
            $renderer->cell(40, 340, 1, null, $this->translator->translate("paymentName"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // platbaHodnota
            $renderer->cell(380, 340, 1, null, $this->order->getPayment()->getPaymentName(), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        }
        
                
        // vpravo - datum objednavky
        if ($this->order->getExternalDealerId()) {
            
            // vystaveniNazev
            $renderer->cell(420, 300, 1, null, $this->translator->translate("documentExternalDealerId"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->grayColor;
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // vystaveniHodnota
            $renderer->cell(760, 300, 1, null, $this->order->getExternalDealerId(), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        }
        
        // vpravo
        if($this->order->getNumber()) {
            
            // interniCisloObjednavky (nazev)
            $renderer->cell(420, 320, 1, null, $this->translator->translate("varSymbol"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->grayColor;
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // interniCisloObjednavky (hodnota)
            $renderer->cell(760, 320, 1, null, $this->order->getPayment()->getVariableSymbol(), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
            
        }
        
        // vpravo - datum objednavky
        if ($this->order->getAccount()->getBank()) {
            
            // vystaveniNazev
            $renderer->cell(420, 340, 1, null, $this->translator->translate("orderDate"), function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 10;
                $settings->fontColor = $this->grayColor;
                $settings->fontFamily = "sans";
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
            });
            
            // vystaveniHodnota
            $renderer->cell(760, 340, 1, null, $this->formatter->formatDate($this->order->getCreated()), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            });
        }
                
    }
    
    
    
    
    /**
     * Oddelovac hlavicka
     */
    protected function buildVerticalLine() {
        
        $renderer = $this->renderer;
        
        // barevny pruh
        $renderer->rect($renderer->width() / 2 - 1, 104, 1, 170, function (Settings $settings) {
            $settings->setFillDrawColor($this->lineColor);
        });
    }
    

    
    
    
    
    /**
     * Polozky - hlavicka
     */
    protected function buildBody(int $offsetHead, int $offsetBody) {
        
        $renderer = $this->renderer;
        
        $renderer->rect(0, $offsetHead, $renderer->width(), 30, function (Settings $settings) {
            $settings->setFillDrawColor($this->evenColor);
        });
        
        $renderer->rect(0, $offsetHead + $offsetBody, $renderer->width(), 1, function (Settings $settings) {
            $settings->setFillDrawColor($this->primaryColor);
        });
                
        // vypis polozek
        //$renderer->cell(40, $offsetHead - 27, ($renderer->width() - 80), 30, "Zákazník vyjádřil souhlas s obchodními podmínkami: ANO. Zákazník má zájem o členství v klubu CL100 a souhlasí s jejich podmínkami: ANO",  function (Settings $settings) {
        $renderer->cell(40, $offsetHead - 25, 360, 30, Strings::upper($this->translator->translate("orderBefore")), function (Settings $settings) {
            $settings->fontColor = $this->grayColor;
            $settings->fontStyle = $settings::FONT_STYLE_NONE;
            $settings->fontSize = 7;
            $settings->align = $settings::ALIGN_LEFT;
        });
        
        // pokud je odberal platce DPC
        if ($this->customerBilling->hasTax()) {
            
            // katalogoveCislo
            $renderer->cell(40, $offsetHead, 360, 30, Strings::firstUpper($this->translator->translate("itemCatalog")), function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            });
            
            // nazevPolozky
            $renderer->cell(140, $offsetHead, 360, 30, Strings::firstUpper($this->translator->translate("itemName")), function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            });
            
            // dphSazba
            $renderer->cell(560, $offsetHead, 40, 30, Strings::firstUpper($this->translator->translate("itemTax")), function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
            });
            
            // pocetJednotek
            $renderer->cell(620, $offsetHead, 60, 30, Strings::firstUpper($this->translator->translate("itemCount")), function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
            });
            
            
            // mezisoucet
            $renderer->cell(600, $offsetHead, 160, 30, Strings::firstUpper($this->translator->translate("itemUnitTotal")), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
            });
            
        } else {
            
            // katalogoveCislo
            $renderer->cell(40, $offsetHead, 360, 30, Strings::firstUpper($this->translator->translate("itemCatalog")), function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            });
            
            // nazevPolozky
            $renderer->cell(140, $offsetHead, 360, 30, Strings::firstUpper($this->translator->translate("itemName")), function (Settings $settings) {
                $settings->fontColor = $this->grayColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontSize = 8;
                $settings->align = $settings::ALIGN_LEFT;
            });
            
            // pocetJednotek
            $renderer->cell(620, $offsetHead, 60, 30, Strings::firstUpper($this->translator->translate("itemCount")), function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
            });
            
            
            // mezisoucet
            $renderer->cell(600, $offsetHead, 160, 30, Strings::firstUpper($this->translator->translate("itemUnitTotal")), function (Settings $settings) {
                $settings->align = $settings::ALIGN_RIGHT;
            });
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
            $renderer->rect(0, $offset, $renderer->width(), 27.0, function (Settings $settings) use ($i) {
                $settings->setFillDrawColor($i % 2 === 1 ? $this->evenColor : $this->oddColor);
            });
            
            // radekBarva2
            $renderer->rect(0, $offset + 27, $renderer->width(), 0.1, function (Settings $settings) {
                $settings->setFillDrawColor($this->evenColor->darken(10));
            });
            
    
            
            // katalogoveCislo
            $catName = $item->getCatalogName();
            $catLine = 17;
            $catSize = Strings::length($catName);
            
            $renderer->cell(40, ($catSize > $catLine ? $offset : $offset + 15), 100, ($catSize > $catLine ? 12 : null), $catName, function (Settings $settings) {
                $settings->fontFamily = "sans";
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 7;
            });
            
            
            // pokud je platce DPC
            if ($this->customerBilling->hasTax()) {
                
                // nazevPolozky
                $renderer->cell(140, $offset, 360, 30, $item->getName(), function (Settings $settings) {
                    $settings->fontFamily = "sans";
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 7;
                });
                
                
                // dphSazba
                $renderer->cell(560, $offset, 40, 27, $item->getTax() . "%", function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                    $settings->fontSize = 7;
                });
                
                // pocetJednotek (vcetne jednotky)
                $renderer->cell(610, $offset, 80, 27, $this->formatter->formatNumber(number: $item->getAmount(), decimal: 0) . Strings::lower($item->getAmountName()), function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                    $settings->fontSize = 7;
                });
                
                // mezisoucet
                $renderer->cell(600, $offset, 160, 27, $this->formatter->formatMoney(number: $item->getTotalPrice(calculator: $this->calculator, useTax: true)), function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontSize = 7;
                });
                
                $offset += 27;
                
            }  else {
                
                // nazevPolozky
                $renderer->cell(140, $offset, 360, 30, $item->getName(), function (Settings $settings) {
                    $settings->fontFamily = "sans";
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_NONE;
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 7;
                });
                                
                // pocetJednotek (vcetne jednotky)
                $renderer->cell(610, $offset, 80, 27, $this->formatter->formatNumber(number: $item->getAmount(), decimal: 0) . Strings::lower($item->getAmountName()), function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                    $settings->fontSize = 7;
                });
                
                // mezisoucet
                $renderer->cell(600, $offset, 160, 27, $this->formatter->formatMoney(number: $item->getTotalPrice(calculator: $this->calculator, useTax: true)), function (Settings $settings) {
                    $settings->align = $settings::ALIGN_RIGHT;
                    $settings->fontSize = 7;
                });
                
                $offset += 27;
            }
        }
        
        return $offset;
    }
    
    
    
    /**
     * Souhrny
     * @param int $offset
     */
    protected function buildTotal(int $offset) {
        
        $renderer = $this->renderer;
        $half = ($renderer->width() - 553) / 2;
        
        //  dph sazby
        if ($this->customerBilling->hasTax()) {
            
            if(!empty($vatLines = $this->order->getVatLines($this->customerBilling->hasTax()))) {
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
                        $renderer->cell(640, $offset, $half, 15, $this->formatter->formatMoney($val, $this->order->getPayment()->getCurrency()),
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
                
        $renderer->cell(640, $offset, $half, 24, $this->formatter->formatMoney($this->order->getTotalPrice($this->calculator, $this->customerBilling->hasTax()), $this->order->getPayment()->getCurrency()), function (Settings $settings) {
            $settings->fontFamily = "sans";
            $settings->fontStyle = $settings::FONT_STYLE_BOLD;
            $settings->fontColor = $this->whiteColor;
            $settings->fontSize = 10;
            $settings->align = $settings::ALIGN_RIGHT;
        });
                    
    }
    
    
    /**
     * Extra zprava
     */
    protected function buildNotice() {
        
        $renderer = $this->renderer;
        
        if(!empty($lines = $this->order->getMessageLine())) {
            
            foreach($lines as $key => $val) {
                $renderer->cell(40, $renderer->y() + 20, 360, 10, $key, function (Settings $settings) {
                    $settings->fontFamily = "sans";
                    $settings->fontColor = $this->fontColor;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->align = $settings::ALIGN_LEFT;
                    $settings->fontSize = 7;
                });
            }
            
            $renderer->cell(40, $renderer->y(), $renderer->width() - 80, 12, implode("\n", $val), function (Settings $settings) {
                $settings->fontColor = $this->fontColor;
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 6;
            });
        }
        
    }
    
    /**
     * Zprava od zakaznika
     */
    protected function buildMessage() {
        
        $renderer = $this->renderer;
        
        if(!empty($message = $this->order->getMessage())) {
            
            $renderer->cell(40, $renderer->y() + 20, 360, 10, $this->translator->translate("messageOrder"), function (Settings $settings) {
                $settings->fontFamily = "sans";
                $settings->fontColor = $this->fontColor;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 7;
            });
            
            $renderer->cell(40, $renderer->y(), $renderer->width() - 80, 10, $message, function (Settings $settings) {
                $settings->fontColor = $this->fontColor;
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_JUSTIFY;
                $settings->fontSize = 6;
            });
        }
        
    }
    

    /**
     * Paticka
     * @param Paginator $paginator
     */
    protected function buildFooter(Paginator $paginator) {
        
        $renderer = $this->renderer;
        
        // barevny pruh
        $renderer->rect(0, $renderer->height() - 20, $renderer->width(), 20,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->fontColor);
            });
        
        // vlevo - vytisknuto
        $renderer->cell(15, -10, $renderer->width() - 10, null, $this->translator->translate("pageCreated") . " " . $this->formatter->formatDatetime(new DateTime()), function (Settings $settings) {
            $settings->fontColor = $this->whiteColor;
            $settings->fontFamily = "sans";
            $settings->align = $settings::ALIGN_LEFT;
            $settings->fontSize = 6;
        });
            
        // stred - author
        $renderer->link(15, -10, $renderer->width() - 10, null, $this->translator->translate("pdfCreator"), $this->creatorLink, function (Settings $settings) {
            $settings->fontColor = $this->whiteColor;
            $settings->fontFamily = "sans";
            $settings->align = $settings::ALIGN_CENTER;
            $settings->fontSize = 6;
        });
        
        // vpravo - stranky
        $renderer->cell(0, -10, $renderer->width() - 10, null, $this->translator->translate("pageName") . " " . $paginator->getCurrentPage() . "/" . $paginator->getTotalPages(), function (Settings $settings) {
            $settings->fontColor = $this->whiteColor;
            $settings->fontFamily = "sans";
            $settings->align = $settings::ALIGN_RIGHT;
            $settings->fontSize = 6;
        });
    }
    
}
