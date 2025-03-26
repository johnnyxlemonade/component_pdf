# Priklad vygenerovani faktury

```php

    use Lemonade\Pdf\{BasicTranslator, BasicFormatter, PdfOutput};
    use Lemonade\Pdf\Data\{Account, Currency, Customer, Company, Order, Payment, Schema};
    use Lemonade\Pdf\Templates\PdfInvoiceTemplate;
    use Lemonade\Pdf\Renderers\Color;
    use Nette\Utils\DateTime AS dtm;
    
    // cislo objednavky
    $orderId = "202300001";
    $dateCreate = new dtm();
    
    // data    
    $company  = new Company(name: "Jan Mudrák", town: "Děčín", address: "Husovo nám. 80/7", zip: "405 02", country: "Česká republika", tin: "05641802");
    $customer = new Customer(name: "core1 s.r.o.", town: "Praha 2", address: "Karlovo náměstí 290/16", zip: "120 00", country: "Česká republika", tin: "04976959", vaTin: "CZ04976959");
    $account  = new Account(account: "CISLO_UCTU");
    $payment  = new Payment(currency: Currency::CURRENCY_CZK, variableSymbol: $orderId, paymentName: "převodem");

    // hlavicka
    $order = new Order(
        account: $account,
        payment: $payment,
        created: $dateCreate,
        dueDate: null,
        orderId: $orderId,
        number: $orderId,
        identificator: null,
        urlStatus: null);
        
    // $order->setPaid(); 
    // $order->disableCatalogColumn();
    // $order->disableAmountVolume();
       
    // polozky - redudantni parametry nejsou vyplneny
    $order->addItem(name: "Položka za nulovou cenu", price: 0, amount: 1, tax: 21, amoutName: "ks", catalog: "KATALOGOVE_CISLO_1");
    $order->addItem(name: "Položka za desetikorunu", price: 10, tax: 21, amoutName: "licence", catalog: "KATALOGOVE_CISLO_2");
    
    // schema    
    $schema = new Schema();
    $schema->setPrimaryColor(color: new Color(red: 226, green: 11, blue: 26));
    $schema->setLogoPath(imageUrl: "./logo.png");
    $schema->setStampPath(imageUrl: "./razitko.png");
    $schema->setCodePath(account: $account, order: $order, payment: $payment); // QRPLATBA

    // pdf - sablona
    $template = new PdfInvoiceTemplate(translator: new BasicTranslator("cs"), formatter: new BasicFormatter(), schema: $schema, customName: "documentInvoice");
    
    // zobrazit    
    $file = new PdfOutput($template);
    $file->display(
        company: $company,
        billing: $customer,
        order: $order
    );
    
    // ulozit
    $file = new PdfOutput($template);
    $file->save(
        company: $company,
        billing: $customer,
        order: $order,
        delivery: null,
        file: "CESTA_K_ULOZENI.pdf" 
    );
    
    // data
    $file = new PdfOutput($template);
    $file->source(
        company: $company,
        billing: $customer,
        order: $order
    );

```