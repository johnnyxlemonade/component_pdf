<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

/**
 * PaymentName
 * \Lemonade\Pdf\Data\PaymentName
 */
enum PaymentName: string
{

    case TYPE_BANK = "bank";
    case TYPE_CARD = "card";
    case TYPE_CASH = "cash";
    case TYPE_ON_DELIVERY = "ondelivery";
    case TYPE_CREDIT = "credit";
    case TYPE_DEPOSIT = "deposit";
    case TYPE_ONLINE = "online";

    /**
     * @param string|null $type
     * @return string
     */
    public static function toHuman(string $type = null): string
    {

        return match ($type) {
            default => "převodem",
            PaymentName::TYPE_CARD->value => "kartou",
            PaymentName::TYPE_CASH->value => "hotově",
            PaymentName::TYPE_ON_DELIVERY->value => "dobírka",
            PaymentName::TYPE_CREDIT->value => "zápočtem",
            PaymentName::TYPE_DEPOSIT->value => "zálohou",
            PaymentName::TYPE_ONLINE->value => "platební brána",
        };

    }

    /**
     * @param string|null $code
     * @return PaymentName
     */
    public static function fromCode(string $code = null): PaymentName
    {

        return match ($code) {
            default => PaymentName::TYPE_BANK,
            "CASH", "hotově", "hotovost" => PaymentName::TYPE_CASH,
            "CASH_ON_DELIVERY", "ON_DELIVERY", "dobírka", "dobírkou" => PaymentName::TYPE_ON_DELIVERY,
            "zápočtem", "zápočet" => PaymentName::TYPE_CREDIT,
            "záloha", "zálohou" => PaymentName::TYPE_DEPOSIT,
            "online", "platební brána", "CSOB", "COMGATE", "GOPAY" => PaymentName::TYPE_ONLINE,
            "kartou", "karta", "card" => PaymentName::TYPE_CARD
        };

    }

}
