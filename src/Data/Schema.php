<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Data;
use Lemonade\Pdf\Renderers\Color;

/**
 * Class Schema
 *
 * Defines color palette and image resources used in PDF rendering.
 * Provides accessors and helpers for logo, stamp, QR code, and status images.
 *
 * @package     Lemonade Framework
 * @subpackage  Pdf\Data
 * @category    Pdf
 * @link        https://lemonadeframework.cz/
 * @since       1.0
 */
class Schema
{
    /** Default “paid” image filenames by language. */
    private const BILL_IMAGE = [
        'cs' => 'paid_cs.png',
        'en' => 'paid_en.png',
        'de' => 'paid_de.png',
    ];

    protected ?Color $primaryColor;
    protected ?Color $fontColor;
    protected ?Color $evenColor;
    protected ?Color $oddColor;
    protected ?Color $lineColor;
    protected ?Color $whiteColor;
    protected ?Color $blackColor;
    protected ?Color $grayColor;

    protected ?string $imageLogoPath = null;
    protected ?string $imageStampPath = null;
    protected ?string $imageCodePath = null;
    protected ?string $imageUrlPath = null;
    protected ?string $imageBillPath = null;
    protected ?string $imageMain = null;
    protected ?string $imageBackground = null;

    public function __construct()
    {
        $this->primaryColor = new Color(235, 43, 46);
        $this->fontColor    = new Color(52, 52, 53);
        $this->evenColor    = new Color(241, 240, 240);
        $this->whiteColor   = Color::white();
        $this->grayColor    = Color::gray();
        $this->oddColor     = Color::white();
        $this->blackColor   = Color::black();
        $this->lineColor    = Color::lightgray();
    }

    // --- Colors --------------------------------------------------------------
    public function getPrimaryColor(): Color
    {
        return $this->primaryColor;
    }

    public function getFontColor(): Color
    {
        return $this->fontColor;
    }

    public function getWhiteColor(): Color
    {
        return $this->whiteColor;
    }

    public function getBlackColor(): Color
    {
        return $this->blackColor;
    }

    public function getGrayColor(): Color
    {
        return $this->grayColor;
    }

    public function getEvenColor(): Color
    {
        return $this->evenColor;
    }

    public function getOddColor(): Color
    {
        return $this->oddColor;
    }

    public function getLineColor(): Color
    {
        return $this->lineColor;
    }

    public function setPrimaryColor(Color $color): static
    {
        $this->primaryColor = $color;
        return $this;
    }

    // --- Image helpers -------------------------------------------------------
    public function setLogoPath(?string $path = null): static
    {
        $this->setImageProperty('imageLogoPath', $path);
        return $this;
    }

    public function setStampPath(?string $path = null): static
    {
        $this->setImageProperty('imageStampPath', $path);
        return $this;
    }

    public function setMainImage(?string $path = null): static
    {
        $this->setImageProperty('imageMain', $path);
        return $this;
    }

    public function setBackgroundImage(?string $path = null): static
    {
        $this->setImageProperty('imageBackground', $path);
        return $this;
    }

    public function hasLogoPath(): bool
    {
        return $this->hasPath('imageLogoPath');
    }

    public function hasStampPath(): bool
    {
        return $this->hasPath('imageStampPath');
    }

    public function hasCodePath(): bool
    {
        return $this->hasPath('imageCodePath');
    }

    public function hasUrlPath(): bool
    {
        return $this->hasPath('imageUrlPath');
    }

    public function getLogoPath(): ?string
    {
        return $this->imageLogoPath;
    }

    public function getStampPath(): ?string
    {
        return $this->imageStampPath;
    }

    public function getMainImage(): ?string
    {
        return $this->imageMain;
    }

    public function getBackgroundImage(): ?string
    {
        return $this->imageBackground;
    }


    // --- Dynamic image generation -------------------------------------------

    public function setCodePath(
        Account $account,
        Order $order,
        Payment $payment,
        bool $toString = false,
        bool $includeDt = true
    ): static {
        $image = new ImagePayment($account, $order, $payment);
        $image->setModuleValues($this->primaryColor);

        $this->imageCodePath = $image->generateImage($toString, $includeDt);
        return $this;
    }

    public function getCodePath(): ?string
    {
        return $this->imageCodePath;
    }

    public function setUrlPath(string|int $statusId = null, ?string $statusUrl = null): static
    {
        $image = new ImageStatus($statusId, $statusUrl);
        $image->setModuleValues($this->primaryColor);

        $this->imageUrlPath = $image->generateImage();
        return $this;
    }

    public function getUrlPath(): ?string
    {
        return $this->imageUrlPath;
    }

    // --- Paid image ----------------------------------------------------------

    public function getPaidImage(string $lang, ?string $fallbackLang = 'en'): ?string
    {
        $filename = self::BILL_IMAGE[$lang] ?? self::BILL_IMAGE[$fallbackLang] ?? null;
        if ($filename === null) {
            return null;
        }

        $file = __DIR__ . '/../../assets/' . $filename;
        return $this->isValidFile($file) ? $file : null;
    }

    // --- Internal ----------------------------------------------------------

    private function isValidFile(?string $path): bool
    {
        return !empty($path) && is_file($path);
    }

    /**
     * @param-keyof self $property
     */
    private function setImageProperty(string $property, ?string $path): void
    {
        if ($this->isValidFile($path)) {
            $this->$property = $path;
        }
    }

    private function hasPath(string $property): bool
    {
        return !empty($this->$property);
    }

}
