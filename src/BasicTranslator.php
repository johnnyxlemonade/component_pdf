<?php declare(strict_types=1);

namespace Lemonade\Pdf;

final class BasicTranslator
{

    /**
     * Cestina
     * @var string
     */
    const CZECH = "cs";

    /**
     * Anglictina
     * @var string
     */
    const ENGLISH = "en";

    /**
     * Nemcina
     * @var string
     */
    const GERMANY = "de";

    /**
     * Data
     * @var array<string, array<string, string>>
     */
    private static array $data = [
        "cs" => [
            "documentInvoice" => "Faktura",
            "documentInvoiceId" => "Faktura číslo",
            "documentInvoiceProforma" => "Proforma",
            "documentOrder" => "Objednávka",
            "documentOrderName" => "Číslo objednávky",
            "documentExternalOrderId" => "Externí číslo objednávky",
            "documentExternalDealerId" => "Číslo obchodního konzultanta",
            "documentStorno" => "Opravný daň. dokl.",
            "documentDescription" => "daňový doklad",
            "documentNoDph" => "neslouží jako daňový doklad",
            "bodyBefore" => "Fakturujeme Vám za dodané zboží či služby",
            "bodyBeforeStorno" => "Vracíme Vám celou částku za doklad %s.",
            "orderBefore" => "Výpis objednaných položek",
            "footerText" => "Fyzická osoba zapsaná v živnostenském rejstříku",
            "subscriberLabel" => "Odběratel",
            "supplierName" => "Dodavatel",
            "vatName" => "IČO",
            "vaTinName" => "DIČ",
            "bankAccount" => "Bankovní účet",
            "paymentName" => "Způsob platby",
            "invoice" => "Faktura",
            "invoiceNumber" => "Číslo faktury",
            "taxPay" => "Plátce DPH",
            "notTax" => "Neplátce DPH",
            "paymentData" => "Platební údaje",
            "pageCreated" => "Vytisknuto",
            "pageName" => "Stránka",
            "pagefrom" => "z",
            "pdfCreator" => "Vygenerováno fakturační službou Lemonade [PDF]",
            "metaAuthor" => "Lemonade [PDF]",
            "totalPrice" => "Celková částka",
            "itemName" => "Název položky",
            "itemCatalog" => "Kód zboží",
            "itemCount" => "MJ",
            "itemTax" => "DPH",
            "itemUnitPrice" => "Cena / MJ",
            "itemUnitTotal" => "Celkem S DPH",
            "accountNumber" => "Číslo účtu",
            "swiftName" => "Swift",
            "ibanName" => "Iban",
            "varSymbol" => "Variabilní symbol",
            "constSymbol" => "Konstant. symbol",
            "subtotal" => "Mezisoučet",
            "date" => "Datum vystavení",
            "orderDate" => "Datum objednání",
            "dueDate" => "Datum splatnosti",
            "taxDate" => "Datum zdan. plnění",
            "vatRate" => "DPH sazba",
            "email" => "Email",
            "phone" => "Telefon",
            "messageOrder" => "Poznámka k objednávce",
            "billingAddress" => "Fakturační adresa",
            "deliveryAddress" => "Doručovací adresa",
            "summaryTaxBase" => "Základ daně",
            "summaryRounding" => "Zaokrouhlení",
        ],
        "en" => [
            "documentInvoice" => "Invoice",
            "documentInvoiceId" => "Invoice number",
            "documentInvoiceProforma" => "Proforma Inv.",
            "documentOrder" => "Order",
            "documentOrderName" => "Order Number",
            "documentExternalOrderId" => "External order number",
            "documentExternalDealerId" => "Vendor identifier",
            "documentStorno" => "Tax doc. corr",
            "documentDescription" => "tax document",
            "documentNoDph" => "it is not a tax document",
            "bodyBefore" => "We invoice you for delivered goods or services",
            "bodyBeforeStorno" => "We will refund you the full amount for document %s.",
            "orderBefore" => "List of ordered items",
            "footerText" => "The entrepreneur is registered in the trade register",
            "subscriberLabel" => "Customer",
            "supplierName" => "Supplier",
            "vatName" => "CIN",
            "vaTinName" => "VATIN",
            "bankAccount" => "Bank account",
            "paymentName" => "Method of payment",
            "invoice" => "Invoice",
            "invoiceNumber" => "Invoice number",
            "taxPay" => "The payer of VAT",
            "notTax" => "Non VAT",
            "paymentData" => "Payment information",
            "pageCreated" => "Printed on",
            "pageName" => "Page",
            "pagefrom" => "from",
            "pdfCreator" => "Generated by billing service Lemonade [PDF]",
            "metaAuthor" => "Lemonade [PDF]",
            "totalPrice" => "Total to pay",
            "itemName" => "Item name",
            "itemCatalog" => "Catalog",
            "itemCount" => "Quantity",
            "itemTax" => "VAT",
            "itemUnitPrice" => "Unit Price",
            "itemUnitTotal" => "Total (inc. VAT)",
            "accountNumber" => "Account number",
            "swiftName" => "Swift",
            "ibanName" => "IBAN",
            "varSymbol" => "Variable symbol",
            "constSymbol" => "Constant symbol",
            "subtotal" => "Subtotal",
            "date" => "Date of Issue",
            "orderDate" => "Date of order",
            "dueDate" => "Due date",
            "taxDate" => "Tax date",
            "vatRate" => "VAT rate",
            "email" => "Email",
            "phone" => "Phone",
            "messageOrder" => "Order note",
            "billingAddress" => "Billing address",
            "deliveryAddress" => "Mailing address",
            "summaryTaxBase" => "Tax base",
            "summaryRounding" => "Rounding",
        ],
        "de" => [
            "documentInvoice" => "Rechnung",
            "documentInvoiceId" => "Rechnungsnummer",
            "documentInvoiceProforma" => "Proforma-Rech.",
            "documentOrder" => "Bestellung",
            "documentOrderName" => "Bestellnummer",
            "documentExternalOrderId" => "Externe Bestellnummer",
            "documentExternalDealerId" => "Anbieterkennung",
            "documentStorno" => "Korrektur",
            "documentDescription" => "Steuerdokument",
            "documentNoDph" => "ist kein Steuerdokument",
            "bodyBefore" => "Wir erstatten Ihnen den vollen Betrag für das Dokument %s.",
            "bodyBeforeStorno" => "Rücksendenummer %s für das Dokument %s",
            "orderBefore" => "Liste der bestellten Artikel",
            "footerText" => "Der Unternehmer ist im Handelsregister eingetragen",
            "subscriberLabel" => "Teilnehmer",
            "supplierName" => "Lieferant",
            "vatName" => "W-IdNr.",
            "vaTinName" => "UID",
            "bankAccount" => "Bankkonto",
            "paymentName" => "Zahlungsart",
            "invoice" => "Rechnung",
            "invoiceNumber" => "Rechnungsnummer",
            "taxPay" => "Der Zahler der Mehrwertsteuer",
            "notTax" => "Ohne Mehrwertsteuer",
            "paymentData" => "Zahlungsinformationen",
            "pageCreated" => "Gedruckt auf",
            "pageName" => "Seite",
            "pagefrom" => "von",
            "pdfCreator" => "Abrechnungsservice generiert Lemonade [PDF]",
            "metaAuthor" => "Lemonade [PDF]",
            "totalPrice" => "Gesamt",
            "itemName" => "Artikelname",
            "itemCatalog" => "Katalognummer",
            "itemCount" => "Menge",
            "itemTax" => "MwSt",
            "itemUnitPrice" => "Einzelpreis",
            "itemUnitTotal" => "Summe (MwSt)",
            "accountNumber" => "Accountnummer",
            "swiftName" => "SWIFT-Code",
            "ibanName" => "IBAN",
            "varSymbol" => "Variablensymbol",
            "constSymbol" => "Konstantes symbol",
            "subtotal" => "Subtotal",
            "date" => "Datum der Ausstellung",
            "orderDate" => "Datum der Bestellung",
            "dueDate" => "Geburtstermin",
            "taxDate" => "Tax date",
            "vatRate" => "Mehrwertsteuersatz",
            "email" => "Email",
            "phone" => "Telefon",
            "messageOrder" => "Bestellhinweis",
            "billingAddress" => "Rechnungsadresse",
            "deliveryAddress" => "Postanschrift",
            "summaryTaxBase" => "Tax base",
            "summaryRounding" => "Rundung",
        ]
    ];

    /**
     * @param string $lang
     */
    public function __construct(protected readonly string $lang = self::CZECH)
    {
    }

    /**
     * @return string
     */
    public function getCode(): string
    {

        return $this->lang;
    }

    /**
     * @param string $message
     * @return string
     */
    public function translate(string $message): string
    {

        return (BasicTranslator::$data[$this->lang][$message] ?? $message);

    }

    /**
     * @param string $message
     * @return bool
     */
    public function hasMessage(string $message): bool
    {

        return (isset(self::$data[$this->lang][$message]));
    }

    /**
     * @return array|string[]
     */
    public function getItems(): array
    {

        return (BasicTranslator::$data[$this->lang] ?? []);
    }


}
