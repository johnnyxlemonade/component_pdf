<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;
use function str_replace;

/**
 * Payment
 * \Lemonade\Pdf\Data\Payment
 */
final class Payment
{

    /**
     * @param Currency $currency
     * @param PaymentName $paymentName
     * @param string|null $variableSymbol
     */
    public function __construct(

        protected readonly Currency $currency = Currency::CURRENCY_CZK,
        protected readonly PaymentName $paymentName = PaymentName::TYPE_BANK,
        protected string|null $variableSymbol = null

    ) {}

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency->value;
    }

    /**
     * @return string
     */
    public function getVariableSymbol(): string
    {

        if((string) $this->variableSymbol !== "") {

            return str_pad(string: str_replace(search: "/", replace: "", subject: (string) $this->variableSymbol), length: 10, pad_string: "0", pad_type:  STR_PAD_LEFT);
        }

        return "";
    }

    /**
     * @return string
     */
    public function getPaymentName(): string
    {

        return $this->paymentName::toHuman(type: $this->paymentName->value);
    }


}
