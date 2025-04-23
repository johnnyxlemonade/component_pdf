<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

use Lemonade\Pdf\Calculators\FloatCalculator;
use DateTime;

class Order
{

    /**
     * @var array<Item>
     */
    private array $items = [];

    /**
     * @var string|int|float|null
     */
    private string|int|null|float $totalPrice = null;

    /**
     * @var int
     */
    private int $interval = 14;

    /**
     * @var bool
     */
    private bool $isPaid = false;

    /**
     * @var string|null
     */
    private ?string $message = null;

    /**
     * @var array
     */
    private array $messageLine = [];

    /**
     * @var string|null
     */
    private ?string $webName = null;


    /**
     * @param Account $account
     * @param Payment $payment
     * @param DateTime $created
     * @param DateTime|null $dueDate
     * @param DateTime|null $taxDate
     * @param string|int|null $orderId
     * @param string|null $number
     * @param string|null $identificator
     * @param string|null $urlStatus
     * @param bool $storno
     * @param array $extraData
     * @throws \DateMalformedStringException
     */
    public function __construct(

        private readonly Account  $account,
        private readonly Payment  $payment,
        private readonly DateTime $created,
        protected ?DateTime       $dueDate = null,
        protected ?DateTime       $taxDate = null,
        protected string|int|null $orderId = null,
        protected string|null     $number = null,
        protected string|null     $identificator = null,
        protected string|null     $urlStatus = null,
        protected bool            $storno = false,
        protected array           $extraData = []

    )
    {
        $this->identificator = ($identificator ?? null);
        $this->urlStatus = ($urlStatus ?? null);

        $this->dueDate = $dueDate ?? (clone $this->created)->modify("+ {$this->interval} days");
        $this->taxDate = $taxDate ?? (clone $this->created)->modify("+ {$this->interval} days");
    }

    /**
     * @return $this
     */
    public function setPaid(): static
    {

        $this->isPaid = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPaid(): bool
    {

        return $this->isPaid;
    }


    /**
     * @param string $name
     * @param float|int|string $price
     * @param int|float $amount
     * @param float|null $tax
     * @param string|null $amoutName
     * @param string|null $linkItem
     * @param string|null $catalog
     * @param bool $isSale
     * @return void
     */
    public function addItem(string $name, float|int|string $price, int|float $amount = 1, float $tax = null, string $amoutName = null, string $linkItem = null, string $catalog = null, bool $isSale = false): void
    {

        $this->items[] = new Item(name: $name, price: (float) $price, amount: (float) $amount, vatRate: $tax, amountName: $amoutName, linkUrl: $linkItem, catalog: $catalog, isSale: $isSale);
    }

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {

        return $this->orderId;
    }

    /**
     * @return string|null
     */
    public function getOrderIdentificator(): ?string
    {

        return $this->identificator;
    }

    /**
     * @return string|null
     */
    public function getOrderStatusUrl(): ?string
    {

        return $this->urlStatus;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {

        return $this->number;
    }

    /**
     * @return string|null
     */
    public function getExternalId(): ?string
    {

        return ($this->extraData["externalNumberId"] ?? null);
    }

    /**
     * @return string|null
     */
    public function getExternalDealerId(): ?string
    {

        return ($this->extraData["externalDealerId"] ?? null);
    }

    /**
     * Datum splatnosti
     * @return DateTime
     */
    public function getDueDate(): DateTime
    {

        return $this->dueDate;
    }


    /**
     * Datum zdan. plnění
     * @return DateTime
     */
    public function getTaxDate(): DateTime
    {

        return $this->taxDate;
    }

    /**
     * Ucet
     * @return Account
     */
    public function getAccount(): Account
    {

        return $this->account;
    }

    /**
     * Platba
     * @return Payment
     */
    public function getPayment(): Payment
    {

        return $this->payment;
    }

    /**
     * Vytvoreno
     * @return DateTime
     */
    public function getCreated(): DateTime
    {

        return $this->created;
    }

    /**
     * Storno doklad
     * @return boolean
     */
    public function hasStorno(): bool
    {

        return $this->storno;
    }

    /**
     * Extra data
     * @return array
     */
    public function getExtraData(): array
    {

        return $this->extraData;
    }

    /**
     * @param string|null $message
     * @return void
     */
    public function addMessage(string $message = null): void
    {

        $this->message = $message;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {

        return $this->message;
    }

    /**
     * @param string|null $head
     * @param string|null $body
     * @return void
     */
    public function addMessageLine(string $head = null, string $body = null): void
    {

        if (isset($this->messageLine[$head])) {

            $this->messageLine[$head][] = $body;

        } else {

            $this->messageLine[$head] = [$body];
        }

    }

    /**
     * @param string|null $web
     * @return void
     */
    public function addWebName(string $web = null): void
    {

        $this->webName = $web;
    }

    /**
     * @return string|null
     */
    public function getWebName(): ?string
    {

        return $this->webName;
    }

    /**
     * @return array
     */
    public function getMessageLine(): array
    {

        return $this->messageLine;
    }

    /**
     * @param bool $useTax
     * @param bool $displayZero
     * @return int[]
     */
    public function getVatLines(bool $useTax = false, bool $displayZero = true): array
    {
        if (!$useTax) {
            return [];
        }

        // výchozí hodnoty
        $rates = [
            0 => 0,
            12 => 0,
            21 => 0,
        ];

        foreach ($this->getItems() as $item) {
            $vat = $item->getVatRate();
            $tax = $item->getAmountTax();

            if ((float) $vat > 0) {
                if (!isset($rates[$vat])) {
                    $rates[$vat] = 0;
                }

                $rates[$vat] += $tax;
            }
        }

        if (!$displayZero) {
            $rates = array_filter($rates, static fn($amount) => (float) $amount > 0);
        }

        ksort($rates);

        return $rates;
    }


    /**
     * @return Item[]
     */
    public function getItems(): array
    {

        return $this->items;
    }

    /**
     * @param FloatCalculator $calculator
     * @param bool $useTax
     * @return float
     */
    public function getSalesPrice(FloatCalculator $calculator): float
    {

        $total = 0;

        foreach ($this->getItems() as $item) {
            if($item->isSale()) {
                $total = $calculator->add($total, $item->getPrice());
            }
        }

        return round(num: $total, precision: 2);
    }

    /**
     * @param FloatCalculator $calculator
     * @param bool $useTax
     * @return float
     */
    public function getTotalPrice(FloatCalculator $calculator, bool $useTax = false): float
    {

        if ($this->totalPrice !== null) {

            return (float) $this->totalPrice;
        }

        $total = 0;

        foreach ($this->getItems() as $item) {

            $total = $calculator->add($total, $item->getTotalPrice($calculator, $useTax));
        }

        return round(num: $total, precision: 2);
    }

    /**
     * @param string|int|float|null $totalPrice
     * @return $this
     */
    public function setTotalPrice(string|int|float|null $totalPrice = null): static
    {

        $this->totalPrice = $totalPrice;

        return $this;
    }

}
