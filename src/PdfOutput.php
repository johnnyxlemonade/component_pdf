<?php declare(strict_types = 1);

namespace Lemonade\Pdf;

use JetBrains\PhpStorm\NoReturn;
use Nette\Utils\FileSystem;
use Exception;
use Lemonade\Pdf\Templates\OutputStandard;
use Lemonade\Pdf\Calculators\FloatCalculator;
use Lemonade\Pdf\Data\{Company, Customer, Order};
use Lemonade\Pdf\Renderers\PDFRenderer;

/**
 * PdfOutput
 * \Lemonade\Pdf\PdfOutput
 */
final class PdfOutput
{

    /**
     * @var Company|null
     */
	protected ?Company $company;

    /**
     * @param OutputStandard $template
     * @param PDFRenderer $renderer
     * @param FloatCalculator $calculator
     */
	public function __construct(

        protected readonly OutputStandard $template,
        protected readonly PDFRenderer $renderer = new PDFRenderer(),
        protected readonly FloatCalculator $calculator = new FloatCalculator()

    ) {}


    /**
     * @param Company $company
     * @param Customer $billing
     * @param Order $order
     * @param Customer|null $delivery
     * @param string|null $file
     * @return array|string[]
     */
	public function save(Company $company, Customer $billing, Order $order, Customer $delivery = null, string $file = null): array
    {


        if(!empty($pdf = $this->_processGenerate(company: $company, billing: $billing, order: $order, delivery: $delivery))) {

            if(!empty($file)) {

                FileSystem::write(file: $file, content: $pdf);

                return [
                    "id" => $order->getOrderId(),
                    "full" => realpath(path: $file),
                    "path" => ltrim(string: $file, characters: ".")
                ];
            }

        }

        return [];
	    
	}

    /**
     * @param Company $company
     * @param Customer $billing
     * @param Order $order
     * @param Customer|null $delivery
     * @return void
     */
	#[NoReturn]
    public function display(Company $company, Customer $billing, Order $order, Customer $delivery = null): void
    {

        if(!empty($pdf = $this->_processGenerate(company: $company, billing: $billing, order: $order, delivery: $delivery))) {

            header(header: "Content-type: application/pdf");

            echo $pdf;
            exit();
        }
	}

    /**
     * @param Company $company
     * @param Customer $billing
     * @param Order $order
     * @param Customer|null $delivery
     * @return array
     */
	public function source(Company $company, Customer $billing, Order $order, Customer $delivery = null): array
    {

        if(!empty($pdf = $this->_processGenerate(company: $company, billing: $billing, order: $order, delivery: $delivery))) {

            return [
                "data" => $pdf,
                "length" => mb_strlen(string: $pdf)
            ];

        } else {

            return [
                "data" => 0,
                "length" => null
            ];
        }
	}


    /**
     * @param Company $company
     * @param Customer $billing
     * @param Order $order
     * @param Customer|null $delivery
     * @return string|null
     */
    protected function _processGenerate(Company $company, Customer $billing, Order $order, Customer $delivery = null): ?string
    {

        $data = null;

        try {

            $data = $this->template->generateOutput(
                calculator: $this->calculator,
                renderer: $this->renderer,
                company: $company,
                billing: $billing,
                order: $order,
                delivery: $delivery
            );

        } catch (Exception) {}

        return $data;
    }
}
