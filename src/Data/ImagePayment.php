<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Data;

use Exception;
use Nette\Utils\Image;
use Nette\Utils\FileSystem;
use Lemonade\Pdf\Renderers\PdfImage;
use Lemonade\Pdf\Calculators\FloatCalculator;

/**
 * ImageQrPayment
 * \Lemonade\Pdf\Data\ImagePayment
 */
final class ImagePayment extends PdfImage
{

    /**
     * @var array|string[]
     */
    private array $json = [
        "SPD", "1.0"
    ];

    /**
     * @var string
     */
    protected string $defaultType = "payment";

    /**
     * @param Account $account
     * @param Order $order
     * @param Payment $payment
     * @param FloatCalculator $calculator
     */
    public function __construct(

        protected readonly Account $account,
        protected readonly Order $order,
        protected readonly Payment $payment,
        protected readonly FloatCalculator $calculator = new FloatCalculator()

    ) {}

    /**
     * @param bool $toString
     * @return string
     */
    public function generateImage(bool $toString = false): string
    {

        $data = [
            "ACC" => $this->account->getIBan(),
            "AM"  => $this->order->getTotalPrice(calculator: $this->calculator),
            "CC"  => $this->order->getPayment()->getCurrency(),
            "X-VS" => $this->payment->getVariableSymbol(),
            "DT"  => $this->order->getDueDate()->format(format: "Ymd")
        ];

        foreach ($data as $key => $val) {
            $this->json[] = $key . ":" . $val;
        }

        // generovani obrazku doFS
        return $this->_generateImageFile(fileId: $this->order->getOrderIdentificator(),  fileText: implode(separator: "*", array: $this->json), fileDesc: "QR platba");
        
    }

}
