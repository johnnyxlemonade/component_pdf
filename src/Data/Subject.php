<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

/**
 * Subject
 * \Lemonade\Pdf\Data\Subject
 */
abstract class Subject
{

    /**
     * @var bool
     */
    protected bool $hasTax = false;

    /**
     * Email
     * @var string|null
     */
    private ?string $email = null;

    /**
     * Telefon
     * @var string|null
     */
    private ?string $phone = null;

    /**
     * Delivery company name
     * @var string|null
     */
    private ?string $deliveryCompanyName = null;

    /**
     *
     * @param string $name
     * @param string|null $town
     * @param string|null $address
     * @param string|null $zip
     * @param string|null $country
     * @param string|null $tin
     * @param string|null $vaTin
     * @param string|null $fileNumberDesc
     */
    public function __construct(
        protected readonly string      $name,
        protected readonly string|null $town = null,
        protected readonly string|null $address = null,
        protected readonly string|null $zip = null,
        protected readonly string|null $country = null,
        protected readonly string|null $tin = null,
        protected readonly string|null $vaTin = null,
        protected string|null          $fileNumberDesc = null)
    {
    }

    /**
     * Vraci nazev
     * @return string|null
     */
    public function getName(): ?string
    {

        return $this->name;
    }

    /**
     * Vraci mesto
     * @return string|NULL
     */
    public function getTown(): ?string
    {

        return $this->town;
    }

    /**
     * Vraci adresu
     * @return string|NULL
     */
    public function getAddress(): ?string
    {

        return $this->address;
    }

    /**
     * Vraci PSC
     * @return string|NULL
     */
    public function getZip(): ?string
    {

        return $this->zip;
    }

    /**
     * Vraci zemi
     * @return string|NULL
     */
    public function getCountry(): ?string
    {

        return $this->country;
    }

    /**
     * Vraci spisovou znacku
     * @return string|null
     */
    public function getFileNumberDesc(): ?string
    {

        return $this->fileNumberDesc;
    }

    /**
     * @param string|null $text
     * @return $this
     */
    public function setFileNumberDesc(string $text = null): static
    {

        $this->fileNumberDesc = $text;

        return $this;
    }

    /**
     * Testovani IC
     * @return boolean
     */
    public function hasTin(): bool
    {

        return ((string) $this->getTin() !== "");
    }

    /**
     * Vraci IC
     * @return string|NULL
     */
    public function getTin(): ?string
    {

        return $this->tin;
    }

    /**
     * Testovani DIC
     * @return boolean
     */
    public function hasTax(): bool
    {

        return ((string) $this->getVaTin() !== "");
    }

    /**
     * Vraci DIC
     * @return string|NULL
     */
    public function getVaTin(): ?string
    {

        return $this->vaTin;
    }

    /**
     * Delivery name
     * @param string|null $name
     * @return Subject
     */
    public function setDeliveryCompany(string $name = null): self
    {

        $this->deliveryCompanyName = $name;

        return $this;
    }

    /**
     * Email
     * @return string|null
     */
    public function getEmail(): ?string
    {

        return $this->email;
    }

    /**
     * Email
     * @param string|null $email
     * @return Subject
     */
    public function setEmail(string $email = null): self
    {

        $this->email = $email;

        return $this;
    }

    /**
     * Telefon
     * @return string|null
     */
    public function getPhone(): ?string
    {

        return $this->phone;
    }

    /**
     * Telefon
     * @param string|null $phone
     * @return Subject
     */
    public function setPhone(string $phone = null): self
    {

        $this->phone = $phone;

        return $this;
    }

    /**
     * DeliveryName
     * @return string|null
     */
    public function getDeliveryCompany(): ?string
    {

        return $this->deliveryCompanyName;
    }
}
