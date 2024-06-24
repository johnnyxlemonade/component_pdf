<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates;

use Lemonade\Pdf\BasicFormatter;
use Lemonade\Pdf\BasicTranslator;
use Lemonade\Pdf\Calculators\FloatCalculator;
use Lemonade\Pdf\Components\Paginator;
use Lemonade\Pdf\Data\Schema;
use Lemonade\Pdf\Renderers\Color;
use Lemonade\Pdf\Renderers\PDFRenderer;
use Lemonade\Pdf\Data\Company;
use Lemonade\Pdf\Data\Customer;
use Lemonade\Pdf\Data\Order;
use Lemonade\Pdf\Renderers\Settings;
use Nette\Utils\DateTime;

/**
 * PdfTemplateInterface
 * \Lemonade\Pdf\Templates\PdfTemplateInterface
 */
abstract class OutputStandard
{

    /**
     * @var int
     */
    protected int $itemsPerPage = 15;

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
    protected ?Color $evenColor;

    /**
     * @var Color|null
     */
    protected ?Color $oddColor;

    /**
     * @var Color|null
     */
    protected ?Color $lineColor;

    /**
     * @var Color|null
     */
    protected ?Color $whiteColor;

    /**
     * @var Color|null
     */
    protected ?Color $grayColor;

    /**
     * @var string
     */
    protected string $creatorLink = "https://core1.agency";


    /**
     * @var PDFRenderer|null
     */
    protected ?PDFRenderer $renderer;

    /**
     * @var Customer|null
     */
    protected ?Customer $customerBilling;

    /**
     * @var Customer|null
     */
    protected ?Customer $customerDelivery;

    /**
     * @var Order|null
     */
    protected ?Order $order;

    /**
     * @var Company|null
     */
    protected ?Company $company;

    /**
     * @var FloatCalculator|null
     */
    protected ?FloatCalculator $calculator;

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
    abstract function generateOutput(

        FloatCalculator $calculator,
        PDFRenderer $renderer,
        Company $company,
        Customer $billing,
        Order $order,
        Customer $delivery = null

    ): string;

    /**
     * @param Paginator $paginator
     * @return void
     */
    protected function buildFooter(Paginator $paginator): void
    {

        $renderer = $this->renderer;

        // barevny pruh
        $renderer->rect(0, $renderer->height() - 20, $renderer->width(), 20,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->fontColor);
            });

        // vlevo - vytisknuto
        $renderer->cell(15, -10, $renderer->width() - 10, null, $this->translator->translate("pageCreated") . " " . $this->formatter->formatDatetime(new DateTime()),
            function (Settings $settings) {
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontSize = 6;
            });

        // stred - author
        $renderer->link(15, -10, $renderer->width() - 10, null, $this->translator->translate("pdfCreator"), $this->creatorLink,
            function (Settings $settings) {
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontSize = 6;
            });

        // vpravo - stranky
        $renderer->cell(0, -10, $renderer->width() - 10, null, $this->translator->translate("pageName") . " " . $paginator->getCurrentPage() . "/" . $paginator->getTotalPages(),
            function (Settings $settings) {
                $settings->fontColor = $this->whiteColor;
                $settings->fontFamily = "sans";
                $settings->align = $settings::ALIGN_RIGHT;
                $settings->fontSize = 6;
            });
    }

    /**
     * @return void
     */
    protected function buildNotice(): void
    {

        $renderer = $this->renderer;

        if(!empty($lines = $this->order->getMessageLine())) {

            foreach($lines as $key => $val) {
                $renderer->cell(40, $renderer->y() + 120, 360, 10, $key, function (Settings $settings) {
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
     * @return void
     */
    protected function buildMessage(): void
    {

        $renderer = $this->renderer;

        if(!empty($message = $this->order->getMessage())) {

            $renderer->cell(40, $renderer->y() + 120, 360, 10, $this->translator->translate("messageOrder"), function (Settings $settings) {
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

}
