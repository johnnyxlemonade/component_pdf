<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Components;

use function bcmod;
use function implode;
use function str_split;
use function preg_match;
use function sprintf;
use const STR_PAD_LEFT;

/**
 * @Bank
 * @\Lemonade\Pdf\Components\Bank
 */
final class Bank
{

    /**
     * @var array<int|string, array<string, string>>
     */
    private static array $banks = [
        "0100" => ["name" => "Komerční banka, a.s.", "bic" => "KOMB CZ PP"],
        "0300" => ["name" => "Československá obchodní banka, a.s.", "bic" => "CEKO CZ PP"],
        "0600" => ["name" => "MONETA Money Bank, a.s.", "bic" => "AGBA CZ PP"],
        "0710" => ["name" => "Česká národní banka", "bic" => "CNBA CZ PP"],
        "0800" => ["name" => "Česká spořitelna, a.s.", "bic" => "GIBA CZ PX"],
        "2010" => ["name" => "Fio banka, a.s.", "bic" => "FIOB CZ PP"],
        "2020" => ["name" => "MUFG Bank (Europe) N.V. Prague Branch", "bic" => "BOTK CZ PP"],
        "2030" => ["name" => "Československé úvěrní družstvo", "bic" => ""],
        "2060" => ["name" => "Citfin, spořitelní družstvo", "bic" => "CITF CZ PP"],
        "2070" => ["name" => "Moravský Peněžní Ústav – spořitelní družstvo", "bic" => "MPUB CZ PP"],
        "2100" => ["name" => "Hypoteční banka, a.s.", "bic" => ""],
        "2200" => ["name" => "Peněžní dům, spořitelní družstvo", "bic" => ""],
        "2220" => ["name" => "Artesa, spořitelní družstvo", "bic" => "ARTT CZ PP"],
        "2240" => ["name" => "Poštová banka, a.s., pobočka Česká republika", "bic" => "POBN CZ PP"],
        "2250" => ["name" => "Banka CREDITAS a.s.", "bic" => "CTAS CZ 22"],
        "2260" => ["name" => "NEY spořitelní družstvo", "bic" => ""],
        "2275" => ["name" => "Podnikatelská družstevní záložna", "bic" => ""],
        "2600" => ["name" => "Citibank Europe plc, organizační složka", "bic" => "CITI CZ PX"],
        "2700" => ["name" => "UniCredit Bank Czech Republic and Slovakia, a.s.", "bic" => "BACX CZ PP"],
        "3030" => ["name" => "Air Bank a.s.", "bic" => "AIRA CZ PP"],
        "3050" => ["name" => "BNP Paribas Personal Finance SA, odštěpný závod", "bic" => "BPPF CZ P1"],
        "3060" => ["name" => "PKO BP S.A., Czech Branch", "bic" => "BPKO CZ PP"],
        "3500" => ["name" => "ING Bank N.V.", "bic" => "INGB CZ PP"],
        "4000" => ["name" => "Expobank CZ a.s.", "bic" => "EXPN CZ PP"],
        "4300" => ["name" => "Českomoravská záruční a rozvojová banka, a.s.", "bic" => "CMZR CZ P1"],
        "5500" => ["name" => "Raiffeisenbank a.s.", "bic" => "RZBC CZ PP"],
        "5800" => ["name" => "J & T BANKA, a.s.", "bic" => "JTBP CZ PP"],
        "6000" => ["name" => "PPF banka a.s.", "bic" => "PMBP CZ PP"],
        "6100" => ["name" => "Equa bank a.s.", "bic" => "EQBK CZ PP"],
        "6200" => ["name" => "COMMERZBANK Aktiengesellschaft, pobočka Praha", "bic" => "COBA CZ PX"],
        "6210" => ["name" => "mBank S.A., organizační složka", "bic" => "BREX CZ PP"],
        "6300" => ["name" => "BNP Paribas S.A., pobočka Česká republika", "bic" => "GEBA CZ PP"],
        "6700" => ["name" => "Všeobecná úverová banka a.s., pobočka Praha", "bic" => "SUBA CZ PP"],
        "6800" => ["name" => "Sberbank CZ, a.s.", "bic" => "VBOE CZ 2X"],
        "7910" => ["name" => "Deutsche Bank Aktiengesellschaft Filiale Prag, organizační složka", "bic" => "DEUT CZ PX"],
        "7940" => ["name" => "Waldviertler Sparkasse Bank AG", "bic" => "SPWT CZ 21"],
        "7950" => ["name" => "Raiffeisen stavební spořitelna a.s.", "bic" => ""],
        "7960" => ["name" => "ČSOB Stavební spořitelna, a.s.", "bic" => ""],
        "7970" => ["name" => "Wüstenrot - stavební spořitelna a.s.", "bic" => ""],
        "7980" => ["name" => "Wüstenrot hypoteční banka a.s.", "bic" => ""],
        "7990" => ["name" => "Modrá pyramida stavební spořitelna, a.s.", "bic" => ""],
        "8030" => ["name" => "Volksbank Raiffeisenbank Nordoberpfalz eG pobočka Cheb", "bic" => "GENO CZ 21"],
        "8040" => ["name" => "Oberbank AG pobočka Česká republika", "bic" => "OBKL CZ 2X"],
        "8060" => ["name" => "Stavební spořitelna České spořitelny, a.s.", "bic" => ""],
        "8090" => ["name" => "Česká exportní banka, a.s.", "bic" => "CZEE CZ PP"],
        "8150" => ["name" => "HSBC Bank plc - pobočka Praha", "bic" => "MIDL CZ PP"],
        "8200" => ["name" => "PRIVAT BANK der Raiffeisenlandesbank Oberösterreich Aktiengesellschaft, pobočka Česká republika", "bic" => ""],
        "8215" => ["name" => "ALTERNATIVE PAYMENT SOLUTIONS, s.r.o.", "bic" => ""],
        "8220" => ["name" => "Payment Execution s.r.o.", "bic" => "PAER CZ P1"],
        "8230" => ["name" => "EEPAYS s. r. o.", "bic" => "EEPS CZ PP"],
        "8240" => ["name" => "Družstevní záložna Kredit", "bic" => ""],
        "8250" => ["name" => "Bank of China (Hungary) Close Ltd. Prague branch, odštěpný závod", "bic" => "BKCH CZ PP"],
        "8260" => ["name" => "PAYMASTER a.s.", "bic" => ""],
        "8265" => ["name" => "Industrial and Commercial Bank of China Limited Prague Branch, odštěpný závod", "bic" => "ICBK CZ PP"],
        "8270" => ["name" => "Fairplay Pay s.r.o.", "bic" => ""],
        "8280" => ["name" => "B-Efekt a.s.", "bic" => "BEFK CZ P1"],
        "8290" => ["name" => "EUROPAY s.r.o.", "bic" => "ERSO CZ PP"]
    ];

    /**
     * @var string|null
     */
    protected ?string $bankAccount;

    /**
     * @var string|null
     */
    protected ?string $bankPrefix;

    /**
     * @var int|null
     */
    protected ?int $bankPrefixInt;

    /**
     * @var string|null
     */
    protected ?string $bankNumber;

    /**
     * @var int|null
     */
    protected ?int $bankNumberInt;

    /**
     * @var string|null
     */
    protected ?string $bankCode;

    /**
     * @var bool
     */
    protected bool $ibanValid = false;

    /**
     * Prazdny prefix
     * @var string
     */
    protected string $emptyPrefix = "000000";

    /**
     * @var string|null
     */
    protected ?string $accountNumber;

    /**
     * @param string|null $accountNumber
     */
    public function __construct(?string $accountNumber = null) {

        $this->accountNumber = (string) $accountNumber;

        if (!(bool) preg_match('/((\d{0,6})-)?(\d{2,10})\/(\d{4})/', $this->accountNumber, $matchList)) {

            $this->ibanValid = false;

        } else {

            // instance - string
            $this->bankPrefix = str_pad($matchList["2"], 6, "0", STR_PAD_LEFT);
            $this->bankNumber = str_pad($matchList["3"], 10, "0", STR_PAD_LEFT);
            $this->bankCode   = str_pad($matchList["4"], 4, "0", STR_PAD_LEFT);

            // instance - int
            $this->bankPrefixInt = (int) $matchList["2"];
            $this->bankNumberInt = (int) $matchList["3"];

            // cislo uctu
            $this->bankAccount = ($this->bankPrefix === $this->emptyPrefix ? $this->bankNumber : sprintf("%s-%s", $this->bankPrefix, $this->bankNumber)) . "/" . $this->bankCode;

            // validace
            $this->_runValidation();
        }

    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {

        return $this->ibanValid;
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {

        return $this->bankPrefix;
    }

    /**
     * @return string|null
     */
    public function getPrefixInt(): ?string
    {

        return $this->bankPrefix;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {

        return $this->bankNumber;
    }

    /**
     * @return int|null
     */
    public function getNumberInt(): ?int
    {

        return $this->bankNumberInt;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {

        return $this->bankCode;
    }

    /**
     * @return string|null
     */
    public function getAccount(): ?string
    {

        return  $this->bankAccount;
    }

    /**
     * @param bool $formatted
     * @return string
     */
    public function getIban(bool $formatted = FALSE): string
    {

        if($this->isValid()) {

            $iban = "CZ" . $this->_generateIbanVerifyCode() . $this->_getIbanFormat();

            return ($formatted ? implode(" ", str_split($iban, 4)) : $iban);
        }

        return "";
    }

    /**
     * @return string
     */
    public function getBic(): string
    {

        return str_replace(" ", "", (self::$banks[$this->getCode()]["bic"] ?? ""));
    }

    /**
     * @param string|null $code
     * @return string
     */
    public static function getName(string $code = null): string
    {

        return (self::$banks[$code]["name"] ?? "");
    }

    /**
     * @return void
     */
    private function _runValidation(): void
    {

        $prefixWeight = [10, 5, 8, 4, 2, 1];
        $numberWeight = [6, 3, 7, 9, 10, 5, 8, 4, 2, 1];

        if ($this->_runControlSum($this->getPrefix(), $prefixWeight) && $this->_runControlSum( $this->getNumber(), $numberWeight)) {

            $this->ibanValid = TRUE;
        }

    }

    /**
     * @param string|null $number
     * @param array<int> $controlData
     * @return bool
     */
    private function _runControlSum(string $number = null, array $controlData = []): bool
    {

        $sum = 0;

        foreach($controlData as $key => $val) {
            $sum += (int) ($number[$key] ?? 0) * $val;
        }

        $sum = (string) $sum;

        return bcmod($sum, "11") === "0";
    }

    /**
     * @return string
     */
    private function _generateIbanVerifyCode(): string
    {

        for ($i = 0; $i < 100; $i++) {

            $vc = str_pad(strval($i), 2, "0", STR_PAD_LEFT);

            if (bcmod($this->_getIbanFormat() . "1235" . $vc, "97") === "1") {

                return $vc;
            }
        }

        return "";
    }

    /**
     * @return string
     */
    private function _getIbanFormat(): string
    {

        return sprintf("%s%s%s", $this->getCode(), $this->getPrefix(), $this->getNumber());
    }


}