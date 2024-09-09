<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

use Lemonade\Pdf\Calculators\FloatCalculator;
use Lemonade\Pdf\Helper\HelperVat;

/**
 * Item
 * \Lemonade\Pdf\Data\Item
 */
class Item
{

    /**
     * @var array<string, int|float>
     */
    protected array $taxData = [];

    /**
     * @var array<string, int|float>
     */
    protected array $taxSale = [];

    /**
     * @var float|null
     */
    protected float|null $totalPrice = null;

    /**
     * @param string $name
     * @param float|int $price
     * @param float|int $amount
     * @param float|int|null $vatRate
     * @param string|null $amountName
     * @param string|null $linkUrl
     * @param string|null $catalog
     * @param bool $isSale
     */
    public function __construct(

        protected readonly string          $name,
        protected readonly float|int       $price,
        protected readonly float|int       $amount,
        protected readonly float|int|null  $vatRate = null,
        protected readonly string|null     $amountName = null,
        protected readonly string|null     $linkUrl = null,
        protected readonly string|null     $catalog = null,
        protected readonly bool            $isSale = false

    ) {

        if(!$this->isSale) {

            $this->taxData = $this->_taxUnit(price: $price, amount: $amount, vatRate: $vatRate);

        } else {

            $this->taxSale = $this->_taxUnit(price: $price, amount: $amount, vatRate: $vatRate);
        }

    }


    /**
     * @return bool
     */
    public function isSale(): bool
    {
        return $this->isSale;
    }

    /**
     * @return float|null
     */
    public function getTax(): ?float
    {

        return $this->vatRate;
    }

    /**
     * @return string
     */
    public function getName(): string
    {

        return $this->name;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {

        return $this->amount;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {

        return ($this->isSale() ? (-1 * $this->price) : $this->price);
    }

    /**
     * @return mixed
     */
    public function getUnitPrice(): mixed
    {

        return ($this->taxSale["unitPrice"] ?? $this->taxData["unitPrice"]);
    }

    /**
     * @return string|null
     */
    public function getItemLink(): ?string
    {

        return $this->linkUrl;
    }

    /**
     * @return float
     */
    public function getVatRate(): float
    {

        return (float) ($this->taxData["vatRate"] ?? 0);
    }

    /**
     * @return float
     */
    public function getAmountTax(): float
    {

        return (float) ($this->taxData["amountTax"] ?? 0);
    }

    /**
     * @return float
     */
    public function getUnitTax(): float
    {

        return (float) ($this->taxData["unitTax"] ?? 0);
    }

    /**
     * @return array<string, int|float>
     */
    public function getVatSize(): array
    {

        return [
            "vat" => ($this->vatRate ?? 0),
            "val" => $this->taxData["amountTax"]
        ];
    }

    /**
     * @return string
     */
    public function getAmountName(): string
    {

        return ((string) $this->amountName !== "" ? sprintf(" %s", $this->amountName) : "");
    }

    /**
     * @return string
     */
    public function getCatalogName(): string
    {

        return ($this->catalog ?? "");
    }

    /**
     * @param FloatCalculator $calculator
     * @param bool $useTax
     * @return mixed
     */
    public function getTotalPrice(FloatCalculator $calculator, bool $useTax = false): mixed
    {

        if ($this->totalPrice !== null) {

            return $calculator->add($this->totalPrice, 0);
        }


        if (!$useTax) {

            return $calculator->mul($this->price, $this->amount);

        } else {

            return $calculator->add($this->taxData["amountPrice"], $this->taxData["amountTax"]);
        }
    }

    /**
     * @param int|float|null $totalPrice
     * @return $this
     */
    public function setTotalPrice(int|float $totalPrice = null): self
    {

        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * @param float|int|string|null $price
     * @param float|int|string|null $amount
     * @param float|int|string|null $vatRate
     * @return array<string,float|int>
     */
    protected function _taxUnit(float|int|string $price = null, float|int|string $amount = null, float|int|string $vatRate = null): array
    {

        if ((float) $vatRate > 0) {

            return [
                "hasTax" => 1,
                "vatRate" => (float) $vatRate,
                "unitPrice" => HelperVat::unitPrice((float) $price, (float) $vatRate),
                "unitTax" => HelperVat::unitTax((float) $price, (float) $vatRate),
                "amountPrice" => HelperVat::totalPrice((float) $price, (float) $vatRate, (float) $amount),
                "amountTax" => HelperVat::totalTax((float) $price, (float) $vatRate, (float) $amount)
            ];

        } else {

            return [
                "hasTax" => 0,
                "vatRate" => (float) $vatRate,
                "unitPrice" => (float) $price,
                "unitTax" => 0,
                "amountPrice" => ((float)$price * (float)$amount),
                "amountTax" => 0
            ];
        }

    }

}
