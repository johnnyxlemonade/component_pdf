<?php declare(strict_types=1);

namespace Lemonade\Pdf\Renderers;

/**
 * Color
 * \Lemonade\Pdf\Renderers\Color
 */
class Color
{

    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     */
    public function __construct(protected readonly int $red, protected readonly int $green, protected readonly int $blue)
    {
    }

    /**
     * @return Color
     */
    public static function black(): Color
    {
        return new Color(0, 0, 0);
    }

    /**
     * @return Color
     */
    public static function gray(): Color
    {
        return new Color(105, 105, 105);
    }

    /**
     * @return Color
     */
    public static function lightgray(): Color
    {
        return new Color(241, 241, 241);
    }

    /**
     * @return Color
     */
    public static function core(): Color
    {
        return new Color(235, 43, 46);
    }

    /**
     * @return Color
     */
    public static function white(): Color
    {
        return new Color(255, 255, 255);
    }

    /**
     * @return int
     */
    public function getRed(): int
    {
        return $this->red;
    }

    /**
     * @return int
     */
    public function getGreen(): int
    {

        return $this->green;
    }

    /**
     * @return int
     */
    public function getBlue(): int
    {
        return $this->blue;
    }

    /**
     * @param int $percentage
     * @return Color
     */
    public function lighten(int $percentage): Color
    {
        $percentage = max(0, min(100, $percentage));

        return $this->lightenDarken(-$percentage);
    }

    /**
     * @param int $percentage
     * @return Color
     */
    protected function lightenDarken(int $percentage): Color
    {
        $percentage = round($percentage / 100, 2);

        return new Color(
            $this->adjustColor((int)($this->red - ($this->red * $percentage))),
            $this->adjustColor((int)($this->green - ($this->green * $percentage))),
            $this->adjustColor((int)($this->blue - ($this->blue * $percentage)))
        );
    }

    /**
     * @param int $dimension
     * @return int
     */
    protected function adjustColor(int $dimension): int
    {
        return max(0, min(255, $dimension));
    }

    /**
     * @param int $percentage
     * @return Color
     */
    public function darken(int $percentage): Color
    {
        $percentage = max(0, min(100, $percentage));

        return $this->lightenDarken($percentage);
    }

}
