<?php declare(strict_types=1);

namespace Lemonade\Pdf\Renderers;

/**
 * Settings
 * \Lemonade\Pdf\Renderers\Settings
 */
final class Settings
{

    const DEFAULT_FONT_SIZE = null;
    const BORDER_LEFT = "L";
    const BORDER_RIGHT = "R";
    const BORDER_TOP = "T";
    const BORDER_BOTTOM = "B";
    const NO_BORDER = 0;
    const BORDER = 1;
    const ALIGN_LEFT = "L";
    const ALIGN_CENTER = "C";
    const ALIGN_RIGHT = "R";
    const ALIGN_JUSTIFY = "J";
    const FILL = true;
    const NO_FILL = false;
    const FONT_STYLE_NONE = "";
    const FONT_STYLE_ITALIC = "I";
    const FONT_STYLE_BOLD = "B";
    const FONT_STYLE_BOLD_ITALIC = "BI";

    /**
     * @var string|int
     */
    public string|int $border = self::NO_BORDER;

    /**
     * @var string|null
     */
    public ?string $align = null;

    /**
     * @var bool
     */
    public bool $fill = self::NO_FILL;

    /**
     * @var int|null
     */
    public ?int $fontSize = self::DEFAULT_FONT_SIZE;

    /**
     * @var string
     */
    public string $fontStyle = self::FONT_STYLE_NONE;

    /**
     * @var string|null
     */
    public ?string $fontFamily = null;

    /**
     * @var Color|null
     */
    public ?Color $drawColor = null;

    /**
     * @var Color|null
     */
    public ?Color $fontColor = null;

    /**
     * @var Color|null
     */
    public ?Color $fillColor = null;

    /**
     * @param Color $color
     * @return $this
     */
    public function setFillDrawColor(Color $color): static
    {
        $this->drawColor = $this->fillColor = $color;

        return $this;
    }

}
