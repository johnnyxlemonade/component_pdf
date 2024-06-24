<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Helper;
use function round;

/**
 * HelperVat
 * \Lemonade\Pdf\Helper\HelperVat
 */
final class HelperVat
{

    /**
     * cena za KUS s DPH
     * @param float|int $price
     * @param float|int $vatRate
     * @return float
     */
    public static function unitPrice(float|int $price, float|int $vatRate): float
    {

        return round($price - ($price - ($price / (1 + $vatRate / 100))), 2);
    }

    /**
     * DPH za KUS
     * @param float|int $price
     * @param float|int $vatRate
     * @return float
     */
    public static function unitTax(float|int $price, float|int $vatRate): float
    {

        return round(($price - ($price / (1 + $vatRate / 100))), 2);
    }

    /**
     * Celkova cena s DPH
     * @param float|int $price
     * @param float|int $vatRate
     * @param float|int $amount
     * @return float
     */
    public static function totalPrice(float|int $price, float|int $vatRate, float|int $amount): float
    {

        return ((HelperVat::unitPrice($price, $vatRate)) * $amount);
    }

    /**
     * Celkova vyse DPH vsech kusu
     * @param float|int $price
     * @param float|int $vatRate
     * @param float|int $amount
     * @return float
     */
    public static function totalTax(float|int $price, float|int $vatRate, float|int $amount): float
    {

        return ((HelperVat::unitTax($price, $vatRate)) * $amount);
    }

}
