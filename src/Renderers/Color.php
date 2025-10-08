<?php declare(strict_types=1);

namespace Lemonade\Pdf\Renderers;

/**
 * Class Color
 *
 * Immutable RGB color representation.
 * Provides helpers for common palette colors and brightness adjustment.
 *
 * @package     Lemonade Framework
 * @subpackage  Pdf\Renderers
 * @category    Pdf
 * @link        https://lemonadeframework.cz/
 * @since       1.0
 */
class Color
{
    private static ?self $BLACK     = null;
    private static ?self $GRAY      = null;
    private static ?self $LIGHTGRAY = null;
    private static ?self $CORE      = null;
    private static ?self $WHITE     = null;

    public function __construct(
        protected readonly int $red,
        protected readonly int $green,
        protected readonly int $blue
    ) {}

    // --- Predefined colors ---------------------------------------------------

    public static function black(): Color
    {
        return self::$BLACK ??= new self(0, 0, 0);
    }

    public static function gray(): Color
    {
        return self::$GRAY ??= new self(105, 105, 105);
    }

    public static function lightgray(): Color
    {
        return self::$LIGHTGRAY ??= new self(241, 241, 241);
    }

    public static function core(): Color
    {
        return self::$CORE ??= new self(235, 43, 46);
    }

    public static function white(): Color
    {
        return self::$WHITE ??= new self(255, 255, 255);
    }

    // --- Accessors -----------------------------------------------------------

    public function getRed(): int
    {
        return $this->red;
    }

    public function getGreen(): int
    {
        return $this->green;
    }

    public function getBlue(): int
    {
        return $this->blue;
    }

    /**
     * Returns hexadecimal string representation of the color.
     */
    public function toHex(bool $withHash = true): string
    {
        $hex = sprintf('%02X%02X%02X', $this->red, $this->green, $this->blue);
        return $withHash ? "#{$hex}" : $hex;
    }

    // --- Color transformations ----------------------------------------------
    public function lighten(int $percentage): Color
    {
        $percentage = max(0, min(100, $percentage)) / 100;

        return new self(
            $this->clamp((int)($this->red + (255 - $this->red) * $percentage)),
            $this->clamp((int)($this->green + (255 - $this->green) * $percentage)),
            $this->clamp((int)($this->blue + (255 - $this->blue) * $percentage))
        );
    }

    public function darken(int $percentage): Color
    {
        $percentage = max(0, min(100, $percentage)) / 100;

        return new self(
            $this->clamp((int)($this->red * (1 - $percentage))),
            $this->clamp((int)($this->green * (1 - $percentage))),
            $this->clamp((int)($this->blue * (1 - $percentage)))
        );
    }

    // --- Internal ------------------------------------------------------------

    protected function clamp(int $value): int
    {
        return max(0, min(255, $value));
    }


}
