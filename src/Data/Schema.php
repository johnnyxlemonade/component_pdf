<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Data;
use Lemonade\Pdf\Renderers\Color;

use function file_exists;
use function is_file;

/**
 * Schema
 * \Lemonade\Pdf\Data\Schema
 */
class Schema
{

    /**
     * @var array|string[]
     */
    private array $billImage = [
        "cs" => "cs.png",
        "en" => "en.png",
        "de" => "de.png"
    ];

    /**
     * @var Color|null
     */
    protected ?Color $primaryColor;

    /**
     * @var Color|null
     */
    protected ?Color $fontColor;

    /**
     * @var Color|null
     */
    protected ?Color $evenColor;

    /**
     * @var Color|null
     */
    protected ?Color $oddColor;

    /**
     * @var Color|null
     */
    protected ?Color $lineColor;
    /**
     * @var Color|null
     */
    protected ?Color $whiteColor;

    /**
     * @var Color|null
     */
    protected ?Color $blackColor;

    /**
     * @var Color|null
     */
    protected ?Color $grayColor;

    /**
     * @var string|null
     */
    protected ?string $imageLogoPath;

    /**
     * @var string|null
     */
    protected ?string $imageStampPath;

    /**
     * @var string|null
     */
    protected ?string $imageCodePath;

    /**
     * @var string|null
     */
    protected ?string $imageUrlPath;

    /**
     * @var string|null
     */
    protected ?string $imageBillPath;

    /**
     * @var string|null
     */
    protected ?string $imageMain = null;

    /**
     * @var string|null
     */
    protected ?string $imageBackground = null;

    /**
     * @return void
     */
    public function __construct() {
        
        $this->primaryColor = new Color(red: 235, green: 43, blue: 46);
        $this->fontColor = new Color(red: 52, green: 52, blue: 53);
        $this->evenColor = new Color(red: 241, green: 240, blue: 240);
        $this->whiteColor = Color::white();
        $this->grayColor = Color::gray();
        $this->oddColor = Color::white();
        $this->blackColor = Color::black();
        $this->lineColor = Color::lightgray();
        $this->imageLogoPath = null;
        $this->imageStampPath = null;
        $this->imageCodePath = null;
        $this->imageUrlPath = null;
    }

    /**
     * @return Color
     */
    public function getPrimaryColor(): Color
    {
        
        return $this->primaryColor;
    }

    /**
     * @return Color
     */
    public function getFontColor(): Color
    {
        
        return $this->fontColor;
    }

    /**
     * @return Color
     */
    public function getWhiteColor(): Color
    {
        
        return $this->whiteColor;
    }

    /**
     * @return Color
     */
    public function getBlackColor(): Color
    {
        
        return $this->blackColor;
    }

    /**
     * @return Color
     */
    public function getGrayColor(): Color
    {
        
        return $this->grayColor;
    }

    /**
     * @return Color
     */
    public function getEvenColor(): Color
    {
        
        return $this->evenColor;
    }

    /**
     * @return Color
     */
    public function getOddColor(): Color
    {
        
        return $this->oddColor;
    }

    /**
     * @return Color
     */
    public function getLineColor(): Color
    {
        
        return $this->lineColor;
    }

    /**
     * @param Color $color
     * @return $this
     */
    public function setPrimaryColor(Color $color): static
    {
        
        $this->primaryColor = $color;
        
        return $this;
    }

    /**
     * @return bool
     */
    public function hasLogoPath(): bool
    {
        
        return !empty($this->getLogoPath());
    }

    /**
     * @param string|null $imageUrl
     * @return $this
     */
    public function setLogoPath(string $imageUrl = null): static
    {
        
        if(!empty($imageUrl) && is_file($imageUrl) && file_exists($imageUrl)) {
            
            $this->imageLogoPath = $imageUrl;
        }
                
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogoPath(): ?string
    {
        
        return $this->imageLogoPath;
    }

    /**
     * @param string|null $imageUrl
     * @return $this
     */
    public function setStampPath(string $imageUrl = null): static
    {
        
        if(!empty($imageUrl) && is_file($imageUrl) && file_exists($imageUrl)) {
            
            $this->imageStampPath = $imageUrl;           
        }
        
        return $this;
    }

    /**
     * @return bool
     */
    public function hasStampPath(): bool
    {
        
        return !empty($this->getStampPath());
    }

    /**
     * @return string|null
     */
    public function getStampPath(): ?string
    {
        
        return $this->imageStampPath;
    }

    /**
     * @param string $lang
     * @return string|null
     */
    public function getPaidImage(string $lang): ?string
    {
        
        $file = dirname(__FILE__) . "/../../assets/paid_$lang.png";
                
        if(file_exists($file) && is_file($file)) {
            
            return $file;
        }
        
        return null;
    }

    /**
     * @return bool
     */
    public function hasCodePath(): bool
    {
        
        return !empty($this->getCodePath());
    }

    /**
     * @param Account $account
     * @param Order $order
     * @param Payment $payment
     * @return $this
     */
    public function setCodePath(Account $account, Order $order, Payment $payment): static
    {
                
        $image = new ImagePayment(account: $account, order: $order, payment: $payment);
        $image->setModuleValues(color: $this->primaryColor);
        
        $this->imageCodePath = $image->generateImage();
        
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCodePath(): ?string
    {
        
        return $this->imageCodePath;
    }

    /**
     * @return bool
     */
    public function hasUrlPath(): bool
    {
        
        return !empty($this->getUrlPath());
    }

    /**
     * @param string|int|null $statusId
     * @param string|null $statusUrl
     * @return $this
     */
    public function setUrlPath(string|int $statusId = null, string $statusUrl = null): static
    {
        
        $image = new ImageStatus(statusId: $statusId, statusUrl: $statusUrl);
        $image->setModuleValues($this->primaryColor);

        $this->imageUrlPath = $image->generateImage();
        
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrlPath(): ?string
    {
        
        return $this->imageUrlPath;
    }

    /**
     * @param string|null $imagePath
     * @return $this
     */
    public function setMainImage(string $imagePath = null): static
    {
        
        if(!empty($imagePath) && is_file($imagePath) && file_exists($imagePath)) {
            
            $this->imageMain = $imagePath;
        }
        
        return $this;
    }

    /**
     * @param string|null $imagePath
     * @return $this
     */
    public function setBackgroundImage(string $imagePath = null): static
    {
        
        if(!empty($imagePath) && is_file($imagePath) && file_exists($imagePath)) {
            
            $this->imageBackground = $imagePath;
        }
        
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMainImage(): ?string
    {
        
        return $this->imageMain;
    }

    /**
     * @return string|null
     */
    public function getBackgroundImage(): ?string
    {
        
        return $this->imageBackground;
    }

}