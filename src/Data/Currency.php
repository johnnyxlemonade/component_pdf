<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

/**
 * Currency
 * \Lemonade\Pdf\Data\Currency
 */
enum Currency: string
{

    case CURRENCY_CZK = "CZK";
    case CURRENCY_EUR = "EUR";
    case CURRENCY_USD = "USD";
    case CURRENCY_GBP = "GBP";

    /**
     * @param string|null $currency
     * @return string
     */
    public static function toHumanCode(string $currency = null): string
    {

        return match ($currency) {
            default => "Kč",
            Currency::CURRENCY_EUR->value => "€",
            Currency::CURRENCY_GBP->value => "£",
            Currency::CURRENCY_USD->value => "$"
        };
    }

    /**
     * @param string|null $currency
     * @return int
     */
    public static function getDecimal(string $currency = null): int
    {

        return match ($currency) {
            default => 1,
            Currency::CURRENCY_CZK->value => 1
        };
    }

    /**
     * @param string|null $currency
     * @return string
     */
    public static function getDateFormat(string $currency = null): string
    {

        return match ($currency) {
            default => "d/m/Y",
            Currency::CURRENCY_CZK->value => "j.n.Y"
        };
    }

    /**
     * @param string|null $currency
     * @return string
     */
    public static function getTimeFormat(string $currency = null): string
    {

        return match ($currency) {
            default => "d/m/Y, g:i A",
            Currency::CURRENCY_CZK->value => "j.n.Y, G:i"
        };
    }

    /**
     * @param string|null $currency
     * @return string
     */
    public static function getDecimalSeparator(string $currency = null): string
    {

        return match ($currency) {
            default => ".",
            Currency::CURRENCY_CZK->value => ","
        };
    }

    /**
     * @param string|null $currency
     * @return string
     */
    public static function getThousandsSepatator(string $currency = null): string
    {

        return match ($currency) {
            default => ",",
            Currency::CURRENCY_CZK->value => " "
        };
    }


}
