<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Templates\Table;

use Lemonade\Pdf\Templates\OutputTable;
use Lemonade\Pdf\Data\{Schema, Table};
use Lemonade\Pdf\Renderers\{Color, PDFRenderer, PdfRendererInterface, Settings};
use Lemonade\Pdf\Templates\TableInterface;
use Nette\Utils\Strings;
use DateTimeImmutable;

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

        // fonty
        $this->renderer->registerDefaultFont();

        $this->buildImage();

        return $this->renderer->output();
    }

    /**
     * @return void
     */
    protected function buildImage(): void
    {
        $renderer = $this->renderer;

        // pozadí
        $renderer->addBackground(
            background: $this->data->getConfig(index: "image")
        );

        // kód kupónu
        $renderer->cell(
            x: $this->property->getConfig(index: "positionX"),
            y: $this->property->getConfig(index: "positionY"),
            width: $this->property->getConfig(index: "codeWidth"),
            height: $this->property->getConfig(index: "codeHeight"),
            text: $this->data->getConfig(index: "code"),
            setCallback: function (Settings $settings) {
                $settings->align      = $settings::ALIGN_CENTER;
                $settings->fontFamily = "sans";
                $settings->fontSize   = $this->property->getConfig(index: "fontSize");
                $settings->fontStyle  = $settings::FONT_STYLE_BOLD;
                $settings->fontColor  = $this->schema->getBlackColor();
            }
        );

        // platnost kuponu (validFrom / validTo)
        $validFrom = $this->data->getConfig("validFrom");
        $validTo   = $this->data->getConfig("validTo");

        if ($validFrom instanceof DateTimeImmutable || $validTo instanceof DateTimeImmutable) {
            $fromStr = $validFrom?->format("d.m.Y");
            $toStr   = $validTo?->format("d.m.Y");

            $validityText = match (true) {
                $fromStr && $toStr => sprintf("Platný od %s do %s", $fromStr, $toStr),
                $fromStr           => sprintf("Platný od %s", $fromStr),
                $toStr             => sprintf("Platný do %s", $toStr),
                default            => "Bez omezení platnosti",
            };

            // vykresluj jen pokud mám nastavené souřadnice
            $validityX = $this->property->getConfig("validityX");
            $validityY = $this->property->getConfig("validityY");

            if ($validityX !== null && $validityY !== null) {
                $renderer->cell(
                    x: (int) $validityX,
                    y: (int) $validityY,
                    width: $renderer->textWidth($validityText),
                    height: 10,
                    text: $validityText,
                    setCallback: function (Settings $settings) {
                        $settings->align      = $settings::ALIGN_CENTER;
                        $settings->fontFamily = "sans";
                        $settings->fontSize   = (int) ($this->property->getConfig("validitySize") ?: 8);
                        $settings->fontColor  = $this->schema->getWhiteColor();
                    }
                );
            }
        }
    }
}
