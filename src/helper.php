<?php 

if (!function_exists(function: "getComposerCurrency")) {
    /**
     * Vraci dostupne meny
     * @return string
     */
    function getComposerCurrency(): string
    {

        $data = "";

        if(!empty($app = _modelEshopOrder())) {

            $data = (method_exists(object_or_class: $app, method: "getComposerCurrency") ? $app->getComposerCurrency() : "");
        }

        return $data;
    }
}

if (!function_exists(function: "getComposerPayment")) {
    /**
     * Vraci dostupne platby
     * @return string
     */
    function getComposerPayment(): string
    {

        $data = "";

        if(!empty($app = _modelEshopOrder())) {

            $data = (method_exists(object_or_class: $app, method: "getComposerPayment") ? $app->getComposerPayment() : "");
        }

        return $data;
    }
}

if (!function_exists(function: "getComposerShipping")) {
    /**
     * Vraci dostupne dopravy
     * @return string
     */
    function getComposerShipping(): string
    {

        $data = "";

        if(!empty($app = _modelEshopOrder())) {

            $data = (method_exists(object_or_class: $app, method: "getComposerShipping") ? $app->getComposerShipping() : "");
        }

        return $data;
    }
}

if (!function_exists(function: "createOrderCustomerPdf")) {
    /**
     * Vytvoreni pdf s udaji komplet udaji zakaznika
     * @param string|int $orderId
     * @return array|string[]
     */
    function createOrderCustomerPdf(string|int $orderId): array
    {

        $data = [];

        if(!empty($app = _modelEshopPdf())) {

            $data = (method_exists(object_or_class: $app, method: "createOrderCustomerPdf") ? $app->createOrderCustomerPdf(orderId: $orderId) : []);
        }

        return $data;
    }
}

if (!function_exists(function: "createOrderPdf")) {
    /**
     * PDF objednavka
     * @param string|int $orderId
     * @return array|string[]
     */
    function createOrderPdf(string|int $orderId): array
    {

        $data = [];

        if(!empty($app = _modelEshopPdf())) {

            $data = (method_exists(object_or_class: $app, method: "createOrderPdf") ? $app->createOrderPdf(orderId: $orderId) : []);
        }

        return $data;
    }
}

if (!function_exists(function: "createProformaPdf")) {
    /**
     * Vytvori proformu z objednavky
     * @param string|int $orderId
     * @return array
     */
    function createProformaPdf(string|int $orderId): array
    {

        $data = [];

        if(!empty($app = _modelEshopPdf())) {

            $data = (method_exists(object_or_class: $app, method: "createProformaPdf") ? $app->createProformaPdf(orderId: $orderId) : []);
        }

        return $data;

    }
}

if (!function_exists(function: "createInvoicePdf")) {
    /**
     * Vytvori fakturu z objednavky
     * @param string|int $orderId
     * @return array
     */
    function createInvoicePdf(string|int $orderId): array
    {

        $data = [];

        if(!empty($app = _modelEshopPdf())) {

            $data = (method_exists(object_or_class: $app, method: "createInvoicePdf") ? $app->createInvoicePdf(orderId: $orderId) : []);
        }

        return $data;
    }
}

if (!function_exists(function: "createStornoPdf")) {
    /**
     * Vytvori opravny danovy doklad
     * @param string|int $orderId
     * @return array
     */
    function createStornoPdf(string|int $orderId): array
    {
        
        $data = [];

        if(!empty($app = _modelEshopPdf())) {

            $data = (method_exists(object_or_class: $app, method: "createStornoPdf") ? $app->createStornoPdf(orderId: $orderId) : []);
        }
        
        return $data;
    }
}


if (!function_exists(function: "_modelEshopOrder")) {
    /**
     * @return Model_eshop_order|null
     */
    function _modelEshopOrder(): ?Model_eshop_order
    {

        if(function_exists(function: "get_instance")) {

            // instanceCodeigniter
            $app =& get_instance();

            // model
            return $app->load->loadModel(model: "admin/eshop/Model_eshop_order");
        }

        return null;
    }
}

if (!function_exists(function: "_modelEshopPdf")) {
    /**
     * @return Model_eshop_pdf|null
     */
    function _modelEshopPdf(): ?Model_eshop_pdf
    {

        if(function_exists(function: "get_instance")) {

            // instanceCodeigniter
            $app =& get_instance();

            // model
            return $app->load->loadModel(model: "admin/eshop/Model_eshop_pdf");
        }

        return null;
    }
}










/* End of file helper.php */
/* helpers/helper.php */