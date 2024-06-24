<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

use Exception;
use Lemonade\Pdf\Components\Bank;

/**
 * Account
 * \Lemonade\Pdf\Data\Account
 */
class Account
{

    /**
     * @var Bank|null
     */
    private ?Bank $bank;

    /**
     * @param string $account
     */
    public function __construct(string $account)
    {

        $this->bank = new Bank($account);
    }

    /**
     * @return string
     */
    public function getBank(): string
    {

        return ($this->bank?->getAccount() ?? "");
    }

    /**
     * @return string
     */
    public function getBankName(): string
    {

        if($this->bank instanceOf Bank) {

            return $this->bank::getName($this->bank->getCode());
        }

        return "";
    }


    /**
     * @param bool $format
     * @return string
     */
    public function getIBan(bool $format = false): string
    {

        return ($this->bank?->getIban($format) ?? "");
    }

    /**
     *
     * @return bool
     */
    public function isValid(): bool
    {

        if($this->bank instanceOf Bank) {

            return $this->bank->isValid();
        }

        return false;
    }

    /**
     * @return string
     */
    public function getSwift(): string
    {

        return ($this->bank?->getBic() ?? "");
    }


}
