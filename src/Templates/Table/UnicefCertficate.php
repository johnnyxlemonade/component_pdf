<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Table;

use Lemonade\Pdf\Components\Paginator;
use Lemonade\Pdf\Templates\OutputTable;
use Lemonade\Pdf\Data\{Schema, Table};
use Lemonade\Pdf\Renderers\{Color, PDFRenderer, PdfRendererInterface, Settings};
use Lemonade\Pdf\Templates\TableInterface;

/**
 * @UnicefCertficate
 * @\Lemonade\Pdf\Templates\Table\UnicefCertficate
 * @deprecated
 */
class UnicefCertficate extends OutputTable {

    const ITEMS_PER_PAGE = 1;

    /**
     * @var Color|null
     */
    private ?Color $bgPrimary;

    /**
     * @var Color|null
     */
    private ?Color $bgSecondary;

    /**
     * @var int
     */
    private int $bodyLeftMargin = 40;

    /**
     * @var int
     */
    private int $certificateName = 22;

    /**
     * @var int
     */
    private int $certificateText = 12;

    /**
     * @var string
     */
    private string $link_one = "darkyprozivot.cz";

    /**
     * @var string
     */
    private string $link_two = "unicef.cz";

    /**
     * @var array|string[]
     */
    private array $default_text = [
        "1" => "Děkujeme za Vaši podporu."
    ];

    /**
     * @param Schema $schema
     */
    public function __construct(protected readonly Schema $schema = new Schema()) {

        $this->bgSecondary = new Color(0, 173, 234);
        $this->bgPrimary = new Color(0, 80, 139);
        $this->whiteColor = $this->schema->getWhiteColor();
    }

    /**
     * @param PDFRenderer $renderer
     * @param Table $table
     * @return string
     */
    public function generateOutput(PDFRenderer $renderer, Table $table): string
    {
        
        $this->renderer = $renderer;
        $this->table = $table;

        $this->renderer->createLandscape();
        $this->renderer->setDocumentMeta([
            "title" => $this->table->getConfig("title"),
            "subject" => $this->table->getConfig("title"),
            "author" => "core1.agency"
        ]);
        
        $this->renderer->addFont("sans", "OpenSans-Regular.php");
        $this->renderer->addFont("sans", "OpenSans-Semibold.php", Settings::FONT_STYLE_BOLD);

        // strankovani
        $paginator = new Paginator($this->table->getConfig("isTotal"), self::ITEMS_PER_PAGE);
        
        while ($paginator->nextPage()) {
        
            if (!$paginator->isFirstPage()) {                
                
                $this->renderer->addLanscapePage();
            }
            
            $this->_buildColumns();
        }
        
        return $this->renderer->output();
    }

    /**
     * @return void
     */
    protected function _buildColumns(): void
    {
        
        $renderer = $this->renderer;
        
        // sidebar - podklad
        $renderer->rect(($renderer->width() / 12 * 8), 0, ($renderer->width() / 12 * 4), $renderer->height(), 
            function (Settings $settings) {
                $settings->setFillDrawColor($this->bgSecondary);
        });
        
        // footer - podklad
        $renderer->rect(0, 0, ($renderer->width() / 12 * 8), $renderer->height(), 
            function (Settings $settings) {
                $settings->setFillDrawColor($this->bgPrimary);
        });
        
        // sidebar - logo
        if(!empty($logo = $this->schema->getLogoPath())) {
            
           $renderer->addImage($logo, ($renderer->width() / 12 * 8) + ($renderer->width() / 24 *2), ($renderer->height() / 12 * 2), ($renderer->width() / 6));
        }
        
        // sidebar - bg image
        if(!empty($bg = $this->schema->getBackgroundImage())) {
                        
            $renderer->filterAlpha(0.1);
            $renderer->addImage($bg, ($renderer->width() / 12 * 8) + 50, ($renderer->height() / 12 * 3), 600);
            $renderer->filterAlpha(1);
        }
        
        // sidebar - text
        $renderer->cell(($renderer->width() / 12 * 9), $renderer->height() / 12 * 5, ($renderer->width() / 12 * 2), 35, $this->default_text["1"],
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fontSize = 20;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // sidebar - cena
        if(!empty($price = $this->_getFromArray("price"))) {
            
            $renderer->cell(($renderer->width() / 12 * 9), $renderer->height() / 12 * 8, ($renderer->width() / 12 * 2), 35, $price,
                function (Settings $settings) {
                    $settings->align = $settings::ALIGN_CENTER;
                    $settings->fontFamily = "sans";
                    $settings->fontSize = 40;
                    $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                    $settings->fontColor = $this->whiteColor;
                });
        }
        
        
        // sidebar - link darkyprozivot
        $renderer->cell(($renderer->width() / 12 * 9) - $this->bodyLeftMargin,  ($renderer->height() / 12 * 11) + 15, ($renderer->width() / 12 * 2), 35, $this->link_one,
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fontSize = 12;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // sidebar - oddelovac
        $renderer->rect(($renderer->width() / 12 * 10) + 20, ($renderer->height() / 12 * 11) + 10, 1, ($renderer->height() / 12) - 20,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->whiteColor);
            });
        
        // sidebar - link unicef
        $renderer->cell(($renderer->width() / 12 * 11) - $this->bodyLeftMargin, ($renderer->height() / 12 * 11) + 15, ($renderer->width() / 12 * 3), 35, $this->link_two,
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fontSize = 12;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        
        // main - image
        if(!empty($image = $this->schema->getMainImage())) {
            
            $renderer->addImage($image, 0, 0, ($renderer->width() / 12 * 8) + 1, ($renderer->height() / 12 * 8));
        }
        
        // main - image
        $renderer->rect(($renderer->width() / 12 * 8)+1,  ($renderer->height() / 12 * 11), ($renderer->width() / 12 * 4), 0.1,
            function (Settings $settings) {
                $settings->setFillDrawColor($this->whiteColor);
            });
        
        
        // footer - name
        $name = $this->table->getConfig("name");
        
        if(!empty($this->table->getConfig("total"))) {
            
            $name = sprintf("%sx %s", $this->table->getConfig("total"), $this->table->getConfig("name"));
        }
        
        $renderer->cell($this->bodyLeftMargin, ($renderer->height() / 12 * 9), 1, null, $name,
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fontSize = $this->certificateName;
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->whiteColor;
            });
        
        // footer - text
        $renderer->cell($this->bodyLeftMargin, $renderer->y() + 30, ($renderer->height() / 12 * 10), ($this->certificateText + 8), $this->table->getConfig("text"),
            function (Settings $settings) {
                $settings->align = $settings::ALIGN_LEFT;
                $settings->fontFamily = "sans";
                $settings->fontSize = $this->certificateText;
                $settings->fontStyle = $settings::FONT_STYLE_NONE;
                $settings->fontColor = $this->whiteColor;
            });
        
    }
    
    
    
    
}