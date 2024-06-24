<?php declare(strict_types=1);

namespace Lemonade\Pdf;
use DateTime;
use Lemonade\Pdf\Data\Currency;

final class BasicFormatter
{

    /**
     * Cestina
     * @var string
     */
    const FORMAT_CZECH = "CZK";

    /**
     * Anglictina
     * @var string
     */
    const FORMAT_EURO = "EUR";

    /**
     * @param string $currency
     */
    public function __construct(protected readonly string $currency = self::FORMAT_CZECH)
    {
    }

    /**
     * @param float|int|string|null $number
     * @param bool $isStorno
     * @param bool $isTotal
     * @param bool $formatDecimal
     * @return string
     */
    public function formatMoney(float|int|string $number = null, bool $isStorno = false, bool $isTotal = false, bool $formatDecimal = false): string
    {

        return ($isStorno ? "- " : "") .  $this->_formatPrice(input: $number, isTotal: $isTotal, formatDecimal: $formatDecimal);
    }

    /**
     * @param float|int|string|null $number
     * @param int $decimal
     * @param bool $isStorno
     * @param bool $formatDecimal
     * @return string
     */
    public function formatNumber(float|int|string $number = null, int $decimal = 1, bool $isStorno = false, bool $formatDecimal = false): string
    {

        return ($isStorno ? "- " : "") . $this->_formatNumber(input: $number, formatDecimal: $formatDecimal);
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function formatDate(DateTime $date): string
    {

        return $date->format(format: Currency::getDateFormat(currency: $this->currency));
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function formatDatetime(DateTime $date): string
    {

        return $date->format(format: Currency::getTimeFormat(currency: $this->currency));
    }

    /**
     * @param float|int|string|null $input
     * @param bool $isTotal
     * @param bool $formatDecimal
     * @return string
     */
    protected function _formatPrice(float|int|string $input = null, bool $isTotal = false, bool $formatDecimal = false): string
    {

        return $this->_formatNumber(input: $input, isTotal: $isTotal, pretty: true, formatDecimal: $formatDecimal) . " " . Currency::toHumanCode(currency: $this->currency);
    }

    /**
     * @param float|int|string|null $input
     * @param bool $isTotal
     * @param bool $pretty
     * @param bool $formatDecimal
     * @return string
     */
    protected function _formatNumber(float|int|string $input = null, bool $isTotal = false, bool $pretty = false, bool $formatDecimal = false): string
    {

        $numberDecimal = 0;
        $numberRounded = (float) str_replace(search: ",", replace: ".", subject: (string) $input);

        if(fmod(num1: $numberRounded, num2: 1) !== 0.00) {

            $numberDecimal = (int) strpos(haystack: strrev(string: (string) $numberRounded), needle: ".");

            if($numberDecimal >= 2) {

                $numberDecimal = 2;
            }
        }

        if($isTotal) {

            if($this->currency === self::FORMAT_EURO) {

                $numberDecimal = 1;

            } else {

                $numberDecimal = 0;
            }
        }

        if($formatDecimal) {

            $numberDecimal = 2;
        }

        return number_format(num: $numberRounded, decimals: $numberDecimal, decimal_separator: ($pretty ? Currency::getDecimalSeparator(currency: $this->currency) : ","), thousands_separator: ($pretty ? Currency::getThousandsSepatator(currency: $this->currency) : ","));
    }

}
