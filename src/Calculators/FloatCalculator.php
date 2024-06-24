<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Calculators;
/**
 * FloatCalculator
 * \Lemonade\Pdf\Calculators\FloatCalculator
 */
final class FloatCalculator
{

    /**
     * @param float|int|string|null $input
     * @return float
     */
    public static function getFloat(float|int|string $input = null): float
    {

        return match(true) {
            default => 0.0,
            is_string($input) => (float) str_replace(",", ".", $input),
            is_float($input), is_int($input) => (float) $input
        };
    }

    /**
     * @param float|int|string|null $op1
     * @param float|int|string|null $op2
     * @return float
     */
    public function add(float|int|string $op1 = null, float|int|string $op2 = null): float
    {

        $op1 = self::getFloat($op1);
        $op2 = self::getFloat($op2);

        return $op1 + $op2;
    }

    /**
     * @param float|int|string|null $op1
     * @param float|int|string|null $op2
     * @return float
     */
    public function mul(float|int|string $op1 = null, float|int|string $op2 = null): float
    {

        $op1 = self::getFloat($op1);
        $op2 = self::getFloat($op2);

        return $op1 * $op2;
    }

    /**
     * @param float|int|string|null $op1
     * @param float|int|string|null $op2
     * @return float
     */
    public function div(float|int|string $op1 = null, float|int|string $op2 = null): float
    {

        $op1 = self::getFloat($op1);
        $op2 = self::getFloat($op2);

        if($op2 === 0.0) {

            return 0.0;
        }

        return $op1 / $op2;
    }

    /**
     * @param float|int|string|null $op1
     * @param float|int|string|null $op2
     * @return float
     */
    public function sub(float|int|string $op1 = null, float|int|string $op2 = null): float
    {

        $op1 = self::getFloat($op1);
        $op2 = self::getFloat($op2);

        return $op1 - $op2;
    }

    /**
     * @param float|int|string|null $op1
     * @param float|int|string|null $op2
     * @return int
     */
    function comp(float|int|string $op1 = null, float|int|string $op2 = null): int
    {

        $op1 = self::getFloat($op1);
        $op2 = self::getFloat($op2);

        return $op1 <=> $op2;
    }


}
