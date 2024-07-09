<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Table;

use Lemonade\Pdf\Templates\OutputTable;
use Lemonade\Pdf\Data\{Schema, Table};
use Lemonade\Pdf\Renderers\{Color, PDFRenderer, PdfRendererInterface, Settings};
use Lemonade\Pdf\Templates\TableInterface;
use Nette\Utils\Strings;

/**
 * @VoucherCode
 * @\Lemonade\Pdf\Templates\Table\VoucherCode
 */
class VoucherCode extends OutputTable
{


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
        $this->data     = $data;
        $this->property = $property;

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

        $this->buildImage();
        
        return $this->renderer->output();
    }

    /**
     * @return void
     */
    protected function buildImage(): void
    {


        $renderer = $this->renderer;
        $renderer->addBackground(background: $this->data->getConfig(index: "image"));
        $renderer->cell(
            x: $this->property->getConfig(index: "positionX"),
            y: $this->property->getConfig(index: "positionY"),
            width: $this->property->getConfig(index: "codeWidth"),
            height: $this->property->getConfig(index: "codeHeight"),
            text: $this->data->getConfig(index: "code"),
            setCallback: function (Settings $settings) {
                $settings->align = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fontSize = $this->property->getConfig(index: "fontSize");
                $settings->fontStyle = $settings::FONT_STYLE_BOLD;
                $settings->fontColor = $this->schema->getBlackColor();
           }
        );
    }

}
