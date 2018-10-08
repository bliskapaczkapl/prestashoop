<?php

use Bliskapaczka\Prestashop\Core\Config;

/**
 * Bliskapaczka shipping module
 * @SuppressWarnings(PHPMD)
 */
class Bliskapaczka extends CarrierModule
{
    public $id_carrier;
    protected $html = '';
    private $config = null;

    /**
     * Constructor, nothing more
     */
    public function __construct()
    {
        $this->config = new Bliskapaczka\Prestashop\Core\Config();

        $this->name = $this->config->name;
        $this->tab = $this->config->tab;
        $this->version = $this->config->version;
        $this->author = 'Mateusz Koszutowski';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l($this->config->displayName);
        $this->description = $this->l($this->config->description);

        $this->confirmUninstall = $this->l($this->config->confirmUninstall);
        $this->limited_countries =$this->config->limitedCountries;

        if (!Configuration::get('bliskapaczka')) {
            $this->warning = $this->l('No name provided');
        }
    }

    /**
     * Instalation proccess
     */
    public function install()
    {
        /* @var Bliskapaczka\Prestashop\Core\Helper $bliskapaczkaHelper */
        $bliskapaczkaHelper = new Bliskapaczka\Prestashop\Core\Helper();

        /* @var Bliskapaczka\Prestashop\Core\Installer */
        $installer = new Bliskapaczka\Prestashop\Core\Installer($this->config);

        if (parent::install() == false ||
            $installer->install($bliskapaczkaHelper) == false ||
            $this->registerHook('actionCarrierUpdate') == false ||
            $this->registerHook('header') == false ||
            $this->registerHook('actionCarrierProcess') == false ||
            $this->registerHook('actionValidateOrder') == false
        ) {
            return false;
        }

        return true;
    }

    /**
     * Uninstalation proccess
     */
    public function uninstall()
    {
        /* @var Bliskapaczka\Prestashop\Core\Helper $bliskapaczkaHelper */
        $bliskapaczkaHelper = new Bliskapaczka\Prestashop\Core\Helper();

        /* @var Bliskapaczka\Prestashop\Core\Installer */
        $installer = new Bliskapaczka\Prestashop\Core\Installer($this->config);

        $id_carrier = \Configuration::get($bliskapaczkaHelper::BLISKAPACZKA_CARRIER_ID);
        $id_courier_carrier = \Configuration::get($bliskapaczkaHelper::BLISKAPACZKA_COURIER_CARRIER_ID);
        $id_tab = \Configuration::get($bliskapaczkaHelper::BLISKAPACZKA_TAB_ID);

        if (parent::uninstall() == false ||
            $installer->uninstall($id_carrier) == false ||
            $installer->deleteToOrderInfoAboutBliskapaczkaOrder() == false ||
            $installer->deleteAdminPanel($id_tab) == false ||
            $installer->deleteCourier($id_courier_carrier) == false
        ) {
            return false;
        }
    }

    /**
     * Add javascripts and csss to header
     *
     * @param array $params
     */
    public function hookHeader($params)
    {
        /* @var Bliskapaczka\Prestashop\Core\Helper $bliskapaczkaHelper */
        $bliskapaczkaHelper = new Bliskapaczka\Prestashop\Core\Helper();

        if (get_class($this->context->controller) == 'OrderController') {
            $this->context->controller->addJs(
                'https://widget.bliskapaczka.pl/' . $bliskapaczkaHelper::WIDGET_VERSION . '/main.js'
            );
            $this->context->controller->addCSS(
                'https://widget.bliskapaczka.pl/' . $bliskapaczkaHelper::WIDGET_VERSION . '/main.css'
            );
            $this->context->controller->addJs($this->_path . 'views/js/' . $this->name . '.js');
        }
    }

    /**
     * Action after carrier change
     *
     * @param array $params
     */
    public function hookActionCarrierUpdate($params)
    {
        $id_carrier_old = (int)($params['id_carrier']);
        $id_carrier_new = (int)($params['carrier']->id);
    }

    /**
     * Update cart if setted carrier is bliskapaczka
     *
     * @param array $params
     */
    public function hookActionCarrierProcess($params)
    {
        $saveCart = false;
        $cart = $params['cart'];
        $posCode = Tools::getValue('bliskapaczka_posCode');
        $posOperator = Tools::getValue('bliskapaczka_posOperator');

        if ($cart->pos_code != $posCode) {
            $cart->pos_code = $posCode;
            $saveCart = true;
        }

        if ($cart->pos_operator != $posOperator) {
            $cart->pos_operator = $posOperator;
            $saveCart = true;
        }

        if ($saveCart == true) {
            $cart->save();
        }
    }

    /**
     * Update cart if carrier bliskapaczka is set
     *
     * @param array $params
     */
    public function hookActionValidateOrder($params)
    {
        $saveOrder = false;
        $cart = $params['cart'];
        $order = $params['order'];

        if ($cart->pos_code != $order->pos_code) {
            $order->pos_code = $cart->pos_code;
            $saveOrder = true;
        }

        if ($cart->pos_operator != $order->pos_operator) {
            $order->pos_operator = $cart->pos_operator;
            $saveOrder = true;
        }

        if ($saveOrder == true) {
            $order->save();
        }

        $shippingAddress = new \Address((int)$order->id_address_delivery);
        $customer = new \Customer((int)$order->id_customer);
        $configuration = new \Configuration();

        /* @var Bliskapaczka\Prestashop\Core\Helper $bliskapaczkaHelper */
        $bliskapaczkaHelper = new Bliskapaczka\Prestashop\Core\Helper();

        $carrier = new Carrier($order->id_carrier, $order->id_lang);
        $method = $carrier->name;

        if ($method == 'bliskapaczka') {
            /* @var Bliskapaczka\Prestashop\Core\Mapper\Order $mapper */
            $mapper = new Bliskapaczka\Prestashop\Core\Mapper\Order();
        }
        if ($method == 'bliskapaczka_courier') {
            /* @var Bliskapaczka\Prestashop\Core\Mapper\Todoor $mapper */
            $mapper = new Bliskapaczka\Prestashop\Core\Mapper\Todoor();
        }

        $data = $mapper->getData($order, $shippingAddress, $customer, $bliskapaczkaHelper, $configuration);

        /* @var \Bliskapaczka\ApiClient\Bliskapaczka $apiClient */
        $apiClient = $bliskapaczkaHelper->getApiClientForOrder($method);
        $response = $apiClient->create($data);

        if ($response) {
            $this->saveResponse($order, $response);
        }
    }

    /**
     * @param Order $order
     * @param json $response
     * @return bool
     */
    protected function saveResponse($order, $response)
    {
        $decodedResponse = json_decode($response);

        //checking reposponce
        if ($response && $decodedResponse instanceof stdClass && empty($decodedResponse->errors)) {
            $order->number = strip_tags($decodedResponse->number);
            $order->status = strip_tags($decodedResponse->status);
            $order->delivery_type = strip_tags($decodedResponse->deliveryType);
            $order->creation_date = strip_tags($decodedResponse->creationDate);
            $order->advice_date = strip_tags($decodedResponse->adviceDate);
            $order->tracking_number = strip_tags($decodedResponse->trackingNumber);

            $order->save();
            return true;
        }

        return false;
    }

    /**
     * Get shipping cost for order. Shipping cost depends on operator. If operator isn't set method returns lowest cost
     *
     * @param Cart $cart
     * @param float $shipping_cost
     * @return float
     */
    public function getOrderShippingCost($cart, $shipping_cost)
    {
        $bliskapaczkaHelper = new Bliskapaczka\Prestashop\Core\Helper();

        /* @var $apiClient \Bliskapaczka\ApiClient\Bliskapaczka */
        $apiClient = $bliskapaczkaHelper->getApiClientPricing();
        $priceList = $apiClient->get(
            array("parcel" => array('dimensions' => $bliskapaczkaHelper->getParcelDimensions()))
        );

        // Fix for shipping price on step payment on checkout
        $posOperator = Tools::getValue('bliskapaczka_posOperator');
        if ($posOperator) {
            $cart->pos_operator = $posOperator;
        }

        $taxInc = false;

        if ((int)\Carrier::getIdTaxRulesGroupByIdCarrier((int)$this->id_carrier) === 0) {
            $taxInc = true;
        }

        if ($cart->pos_operator) {
            $shippingPrice = round(
                $bliskapaczkaHelper->getPriceForCarrier(
                    json_decode($priceList),
                    $cart->pos_operator,
                    $taxInc
                ),
                2
            );
        } else {
            $shippingPrice = round($bliskapaczkaHelper->getLowestPrice(json_decode($priceList), $taxInc), 2);
        }

        return $shippingPrice;
    }

    /**
     * Shippin external cost
     *
     * @param array $params
     */
    public function getOrderShippingCostExternal($params)
    {
        // return 7.0;
    }

    /**
     * Save module configuration
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $data = $this->prepareSenderData();

            // Sender data validation
            try {
                $apiValidator = new \Bliskapaczka\ApiClient\Validator\Order\Sender;
                $apiValidator->setData($data);
                $apiValidator->validate();
            } catch (Exception $e) {
                $this->html .= $this->displayError($e->getMessage());
                return;
            }

            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::API_KEY,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::API_KEY)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::TEST_MODE,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::TEST_MODE)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_X,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_X)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Y,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Y)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Z,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Z)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_WEIGHT,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_WEIGHT)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_EMAIL,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_EMAIL)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_FIRST_NAME,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_FIRST_NAME)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_LAST_NAME,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_LAST_NAME)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_PHONE_NUMBER,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_PHONE_NUMBER)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_STREET,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_STREET)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_BUILDING_NUMBER,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_BUILDING_NUMBER)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_FLAT_NUMBER,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_FLAT_NUMBER)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_POST_CODE,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_POST_CODE)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_CITY,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::SENDER_CITY)
            );
            Configuration::updateValue(
                Bliskapaczka\Prestashop\Core\Helper::GOOGLE_MAP_API_KEY,
                Tools::getValue(Bliskapaczka\Prestashop\Core\Helper::GOOGLE_MAP_API_KEY)
            );
        }

        $this->html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * Prepare sneder data to validation before save configuration
     */
    protected function prepareSenderData()
    {
        $data = array();
        $dataToGet = array(
            'senderEmail' => Bliskapaczka\Prestashop\Core\Helper::SENDER_EMAIL,
            'senderPhoneNumber' => Bliskapaczka\Prestashop\Core\Helper::SENDER_PHONE_NUMBER,
            'senderPostCode' => Bliskapaczka\Prestashop\Core\Helper::SENDER_POST_CODE,
            'senderFirstName' => Bliskapaczka\Prestashop\Core\Helper::SENDER_FIRST_NAME,
            'senderLastName' => Bliskapaczka\Prestashop\Core\Helper::SENDER_LAST_NAME,
            'senderStreet' => Bliskapaczka\Prestashop\Core\Helper::SENDER_STREET,
            'senderBuildingNumber' => Bliskapaczka\Prestashop\Core\Helper::SENDER_BUILDING_NUMBER,
            'senderFlatNumber' => Bliskapaczka\Prestashop\Core\Helper::SENDER_FLAT_NUMBER,
            'senderCity' => Bliskapaczka\Prestashop\Core\Helper::SENDER_CITY
        );

        foreach ($dataToGet as $key => $value) {
            if (Tools::getValue($value)) {
                $data[$key] = Tools::getValue($value);
            }
        }

        return $data;
    }

    /**
     * Prepare module configuration page
     */
    public function getContent()
    {
        $this->html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->postProcess();
        } else {
            $this->html .= '<br />';
        }

        $this->html .= $this->renderForm();

        return $this->html;
    }

    /**
     * Prepare module confoguration form
     */
    public function renderForm()
    {
        $fieldsForm = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configure'),
                    'icon' => 'icon-user'
                ),
                'input' => $this->getFormInput(),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        
        // Land
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fieldsForm));
    }

    /**
     * Return configuration module form input
     *
     * @return array
     */
    protected function getFormInput()
    {
        return array(
            array(
                'type' => 'text',
                'label' => $this->l('API Key'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::API_KEY,
                'required' => true
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Test mode enabled'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::TEST_MODE,
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Disabled')
                    )
                ),
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Google Map API Key'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::GOOGLE_MAP_API_KEY
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Fixed parcel type size X (cm)'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_X
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Fixed parcel type size Y (cm)'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Y
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Fixed parcel type size Z (cm)'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Z
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Fixed parcel type weight (kg)'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_WEIGHT
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender email'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_EMAIL
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender first name'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_FIRST_NAME
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender last name'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_LAST_NAME
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender phone number'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_PHONE_NUMBER
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender street'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_STREET
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender building number'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_BUILDING_NUMBER
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender flat number'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_FLAT_NUMBER
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender post code'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_POST_CODE
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Sender city'),
                'name' => Bliskapaczka\Prestashop\Core\Helper::SENDER_CITY
            )
        );
    }

    /**
     * Get module configuration
     */
    public function getConfigFieldsValues()
    {
        return array(
            Bliskapaczka\Prestashop\Core\Helper::API_KEY => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::API_KEY,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::API_KEY)
            ),
            Bliskapaczka\Prestashop\Core\Helper::TEST_MODE => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::TEST_MODE,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::TEST_MODE)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_X => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_X,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_X)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Y => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Y,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Y)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Z => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Z,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_Z)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_WEIGHT => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_WEIGHT,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SIZE_TYPE_FIXED_SIZE_WEIGHT)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_EMAIL => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_EMAIL,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_EMAIL)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_FIRST_NAME => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_FIRST_NAME,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_FIRST_NAME)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_LAST_NAME => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_LAST_NAME,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_LAST_NAME)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_PHONE_NUMBER => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_PHONE_NUMBER,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_PHONE_NUMBER)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_STREET => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_STREET,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_STREET)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_BUILDING_NUMBER => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_BUILDING_NUMBER,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_BUILDING_NUMBER)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_FLAT_NUMBER => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_FLAT_NUMBER,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_FLAT_NUMBER)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_POST_CODE => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_POST_CODE,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_POST_CODE)
            ),
            Bliskapaczka\Prestashop\Core\Helper::SENDER_CITY => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::SENDER_CITY,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::SENDER_CITY)
            ),
            Bliskapaczka\Prestashop\Core\Helper::GOOGLE_MAP_API_KEY => Tools::getValue(
                Bliskapaczka\Prestashop\Core\Helper::GOOGLE_MAP_API_KEY,
                Configuration::get(Bliskapaczka\Prestashop\Core\Helper::GOOGLE_MAP_API_KEY)
            )
        );
    }
}
