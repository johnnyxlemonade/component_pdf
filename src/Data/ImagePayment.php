<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Data;

use Exception;
use Nette\Utils\Image;
use Nette\Utils\FileSystem;
use Lemonade\Pdf\Renderers\PdfImage;
use Lemonade\Pdf\Calculators\FloatCalculator;

/**
 * Class ImagePayment
 *
 * Generates a QR code for payment using the SPD 1.0 standard.
 * Designed for embedding payment data into PDF documents.
 * Uses the internal PdfImage renderer for image creation.
 *
 * @package     Lemonade Framework
 * @subpackage  Pdf\Data
 * @category    Pdf
 * @link        https://lemonadeframework.cz/
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0
 */
final class ImagePayment extends PdfImage
{
    /** Image type identifier. */
    protected string $defaultType = "payment";

    public function __construct(
        protected readonly Account $account,
        protected readonly Order $order,
        protected readonly Payment $payment,
        protected readonly FloatCalculator $calculator = new FloatCalculator()
    ) {}

    /**
     * Generates a QR payment image in SPD format.
     */
    public function generateImage(bool $toString = false, bool $includeDt = false): string
    {
        $payload = $this->buildSpdPayload($includeDt);

        return $this->_generateImageFile(
            fileId: $this->order->getOrderIdentificator(),
            fileCurrency: $this->payment->getCurrency(),
            fileText: $payload,
            fileDesc: 'QR Platba'
        );
    }

    /**
     * Builds the SPD-formatted payload string for the QR code.
     */
    private function buildSpdPayload(bool $includeDt): string
    {
        $data = [
            'ACC'  => $this->account->getIBan(),
            'AM'   => $this->order->getTotalPrice(calculator: $this->calculator),
            'CC'   => $this->order->getPayment()->getCurrency(),
            'X-VS' => $this->payment->getVariableSymbol(),
        ];

        if ($includeDt) {
            $data['DT'] = $this->order->getDueDate()?->format('Ymd'); // optional inclusion
        }

        $filtered = array_filter($data, fn($v) => !empty($v));
        $mapped   = array_map(fn($k, $v) => "$k:$v", array_keys($filtered), $filtered);

        // Direct merge
        return implode('*', array_merge(['SPD', '1.0'], $mapped));
    }

}
