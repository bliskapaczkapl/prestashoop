<?php

namespace Bliskapaczka\Prestashop\Core;

use Bliskapaczka\ApiClient;

/**
 * Bliskapaczka helper
 *
 * @author Mateusz Koszutowski (mkoszutowski@divante.pl)
 * @SuppressWarnings(PHPMD)
 */
class Helper
{
    const DEFAULT_GOOGLE_API_KEY =  'AIzaSyCUyydNCGhxGi5GIt5z5I-X6hofzptsRjE';

    const SIZE_TYPE_FIXED_SIZE_X = 'BLISKAPACZKA_PARCEL_SIZE_TYPE_FIXED_SIZE_X';
    const SIZE_TYPE_FIXED_SIZE_Y = 'BLISKAPACZKA_PARCEL_SIZE_TYPE_FIXED_SIZE_Y';
    const SIZE_TYPE_FIXED_SIZE_Z = 'BLISKAPACZKA_PARCEL_SIZE_TYPE_FIXED_SIZE_Z';
    const SIZE_TYPE_FIXED_SIZE_WEIGHT = 'BLISKAPACZKA_PARCEL_SIZE_TYPE_FIXED_SIZE_WEIGHT';

    const SENDER_EMAIL = 'BLISKAPACZKA_SENDER_EMAIL';
    const SENDER_FIRST_NAME = 'BLISKAPACZKA_SENDER_FIRST_NAME';
    const SENDER_LAST_NAME = 'BLISKAPACZKA_SENDER_LAST_NAME';
    const SENDER_PHONE_NUMBER = 'BLISKAPACZKA_SENDER_PHONE_NUMBER';
    const SENDER_STREET = 'BLISKAPACZKA_SENDER_STREET';
    const SENDER_BUILDING_NUMBER = 'BLISKAPACZKA_SENDER_BUILDING_NUMBER';
    const SENDER_FLAT_NUMBER = 'BLISKAPACZKA_SENDER_FLAT_NUMBER';
    const SENDER_POST_CODE = 'BLISKAPACZKA_SENDER_POST_CODE';
    const SENDER_CITY = 'BLISKAPACZKA_SENDER_CITY';
    const BANK_ACCOUNT_NUMBER = 'BLISKAPACZKA_BANK_ACCOUNT_NUMBER';

    const API_KEY = 'BLISKAPACZKA_API_KEY';
    const TEST_MODE = 'BLISKAPACZKA_TEST_MODE';

    const AUTO_ADVICE = 'BLISKAPACZKA_AUTO_ADVICE';

    const GOOGLE_MAP_API_KEY = 'BLISKAPACZKA_GOOGLE_MAP_API_KEY';

    const BLISKAPACZKA_CARRIER_ID = 'BLISKAPACZKA_CARRIER_ID';
    const BLISKAPACZKA_COURIER_CARRIER_ID = 'BLISKAPACZKA_COURIER_CARRIER_ID';

    const BLISKAPACZKA_TAB_ID = 'BLISKAPACZKA_TAB_ID';

    const WIDGET_VERSION = 'v5';

    /**
     * Get parcel dimensions in format accptable by Bliskapaczka API
     *
     * @return array
     */
    public function getParcelDimensions()
    {
        $height = \Configuration::get(self::SIZE_TYPE_FIXED_SIZE_X);
        $length = \Configuration::get(self::SIZE_TYPE_FIXED_SIZE_Y);
        $width = \Configuration::get(self::SIZE_TYPE_FIXED_SIZE_Z);
        $weight = \Configuration::get(self::SIZE_TYPE_FIXED_SIZE_WEIGHT);

        $dimensions = array(
            "height" => $height,
            "length" => $length,
            "width" => $width,
            "weight" => $weight
        );

        return $dimensions;
    }

    /**
     * Get Google API key. If key is not defined return default.
     *
     * @return string
     */
    public function getGoogleMapApiKey()
    {
        $googleApiKey = self::DEFAULT_GOOGLE_API_KEY;

        if (\Configuration::get(self::GOOGLE_MAP_API_KEY)) {
            $googleApiKey = \Configuration::get(self::GOOGLE_MAP_API_KEY);
        }

        return $googleApiKey;
    }

    /**
     * Get lowest price from pricing list
     *
     * @param  array $priceList - price list
     * @param  bool  $taxInc    - return price with tax
     * @return float
     */
    public function getLowestPrice($priceList, $taxInc = true)
    {
        $lowestPriceTaxExc = null;
        $lowestPriceTaxInc = null;

        if (!empty($priceList)) {
            foreach ($priceList as $carrier) {
                if ($carrier->availabilityStatus == false) {
                    continue;
                }

                if ($lowestPriceTaxInc == null || $lowestPriceTaxInc > $carrier->price->gross) {
                    $lowestPriceTaxExc = $carrier->price->net;
                    $lowestPriceTaxInc = $carrier->price->gross;
                }
            }
        }

        if ($taxInc) {
            $lowestPrice = $lowestPriceTaxInc;
        } else {
            $lowestPrice = $lowestPriceTaxExc;
        }

        return $lowestPrice;
    }

    /**
     * Get price for specific carrier
     *
     * @param  array  $priceList
     * @param  string $carrierName
     * @param  bool   $taxInc
     * @return float
     */
    public function getPriceForCarrier($priceList, $carrierName, $taxInc = true)
    {
        $price = null;

        foreach ($priceList as $carrier) {
            if ($carrier->operatorName == $carrierName) {
                if ($taxInc) {
                    $price = $carrier->price->gross;
                } else {
                    $price = $carrier->price->net;
                }
            }
        }

        return $price;
    }

    /**
     * Get operators and prices from Bliskapaczka API
     *
     * @return string
     */
    public function getPriceList()
    {
        try {
            $apiClient = $this->getApiClientPricing();
            $priceList = $apiClient->get(
                array("parcel" => array('dimensions' => $this->getParcelDimensions()))
            );

            return json_decode($priceList);
        } catch (\Exception $e) {
            Logger::debug($e->getMessage());
            return array();
        }
    }

    /**
     * Get widget configuration
     *
     * @param array $priceList
     * @param bool $freeShipping
     * @param null $cods
     *
     * @return string
     */
    public function getOperatorsForWidget($priceList = array(), $freeShipping = false, $cods = null)
    {
        try {
            if (empty($priceList)) {
                $priceList = $this->getPriceList();
            }
            $operators = array();
            if ($cods === null) {
                $cods = $this->makeCODStructure($this->getConfig()->configModel);
            }
            foreach ($priceList as $operator) {
                if ($operator->availabilityStatus != false) {
                    $price = $operator->price->gross;
                    if ($freeShipping == true) {
                        $price = 0;
                    }

                    $operators[] = array(
                        "operator" => $operator->operatorName,
                        "price" => $price,
                        "cod" => $cods[$operator->operatorName]
                    );
                }
            }

            return json_encode($operators);
        } catch (\Exception $e) {
            Logger::debug($e->getMessage());
            return '{}';
        }
    }

    /**
     * @param int $cartPrice
     * @param string $name
     * @param string $operator
     * @param boolean $freeShipping *
     * @param boolean $isCod
     *
     * @return array
     */
    public function getTotalShippingCostByCarrierNameAndOperatorAndIsCod(
        $cartPrice,
        $name,
        $operator,
        $freeShipping,
        $isCod
    ) {

        if ($freeShipping === true) {
            return array('net' => 0, 'vat' => 0, 'gross' => 0);
        }
        $deliveryType = 'P2P';
        if ($name === 'bliskapaczka_courier') {
            $deliveryType = 'D2D';
        }
        if ($name === 'bliskapaczka' && $operator === 'FEDEX') {
            $deliveryType = 'D2P';
        }

        $data = array(
            "parcel" => array('dimensions' => $this->getParcelDimensions()),
            "deliveryType" => $deliveryType
        );
        if ($isCod ==  1) {
            $data['codValue'] = $cartPrice;
        }
        $apiClient = $this->getApiClientPricing();
        $result = json_decode($apiClient->get($data));
        
        foreach ($result as $item) {
            if ($item->operatorName === $operator && $item->availabilityStatus === true) {
                return array(
                    'net' => $item->price->net,
                    'vat' => $item->price->vat,
                    'gross' => $item->price->gross,
                );
            }
        }
    }
    /**
     * @param boolean $freeShipping
     *
     * @return string
     */
    public function getCouriersForWidget($freeShipping)
    {
        $data = array(
            "parcel" => array('dimensions' => $this->getParcelDimensions()),
            "deliveryType" => 'D2D'
        );

        $apiClient = $this->getApiClientPricing();
        $priceList = $apiClient->get($data);

        return $this->getOperatorsForWidget(json_decode($priceList), $freeShipping);
    }
    /**
     * @param array $configs
     *
     * @return array
     */
    public function makeCODStructure($configs)
    {
        $result = array();
        foreach ($configs as $config) {
            if (!empty($config->cod)) {
                $result[$config->operator] = $config->cod;
            } else {
                $result[$config->operator] = 0;
            }
        }

        return $result;
    }

    /**
     * Method for managing free shipping.
     * Required for calculating package price for operators. Used in method self->getOperatorsForWidget
     *
     * @param bool $freeShipping
     * @param Cart $cart
     */
    public function freeShipping($freeShipping, $cart)
    {
        $option = $this->carrierSettings($cart);

        // Ligic coppied from override/views/front/order-carrier.tpl
        if ($option['total_price_with_tax']
            && !$option['is_free']
            && empty($freeShipping)
        ) {
            return false;
        }
        return true;
    }

    /**
     * Return bliskapaczka.pl module settings like in cart
     *
     * @param  Cart $cart
     * @return array
     */
    private function carrierSettings($cart)
    {
        // Ligic coppied from override/views/front/order-carrier.tpl
        $delivery_option_list = $cart->getDeliveryOptionList();
        foreach ($delivery_option_list as $id_address => $option_list) {
            foreach ($option_list as $key => $option) {
                foreach ($option['carrier_list'] as $carrier) {
                    if ($carrier['instance']->external_module_name == 'bliskapaczka') {
                        return $option;
                    }
                }
            }
        };

        return array();
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClient()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientOrder()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Order(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     * @return ApiClient\Bliskapaczka\Order\Confirm
     * @throws ApiClient\Exception
     */
    public function getApiClientConfirm()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Order\Confirm(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );
        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientOrderAdvice()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Order\Advice(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientTodoorAdvice()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Todoor\Advice(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientCancel()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Order\Cancel(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientPricing()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Pricing(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientTodoor()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Todoor(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientPricingTodoor()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Pricing\Todoor(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientWaybill()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Order\Waybill(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @return \Bliskapaczka\ApiClient\Bliskapaczka
     */
    public function getApiClientReport()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Report(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * @return ApiClient\Bliskapaczka\Config
     * @throws ApiClient\Exception
     */
    public function getApiClientConfig()
    {
        $apiClient = new \Bliskapaczka\ApiClient\Bliskapaczka\Config(
            \Configuration::get(self::API_KEY),
            $this->getApiMode(\Configuration::get(self::TEST_MODE))
        );

        return $apiClient;
    }

    /**
     * @return array|mixed
     * @throws ApiClient\Exception
     */
    public function getConfig()
    {
        $apiClient = $this->getApiClientConfig();
        $config = $apiClient->get();
        if (json_decode($config) === null) {
            return array();
        }
        return json_decode($config);
    }


    /**
     * Return FEDEX config.
     * @return false|mixed|string|void
     * @throws \Bliskapaczka\ApiClient\Exception
     */
    public function getFedexConfigurationForWidget()
    {
        $config = $this->getConfig();
        $result = array();
        foreach ($config->configModel as $item) {
            if ($item->operator === 'FEDEX' && isset($item->prices->D2P)) {
                $result[0]['operator'] = $item->operator;
                $result[0]['price'] = $item->prices->D2P[0]->price;
                $result[0]['cod'] = $item->cod;
                $result[0]['availabilityStatus'] = true;
            }
        }
        return json_encode($result);
    }
    /**
     * Remove all non numeric chars from phone number
     *
     * @param  string $phoneNumber
     * @return string
     */
    public function telephoneNumberCleaning($phoneNumber)
    {
        $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);

        if (strlen($phoneNumber) > 9) {
            $phoneNumber = preg_replace("/^48/", "", $phoneNumber);
        }
        
        return $phoneNumber;
    }

    /**
     * Get API mode
     *
     * @param  string $configValue
     * @return string
     */
    public function getApiMode($configValue = '')
    {
        $mode = 'prod';

        switch ($configValue) {
            case '1':
                $mode = 'test';
                break;
        }

        return $mode;
    }

    /**
     * Get Bliskapaczka API Client
     *
     * @param string $method
     * @param Configuration $configuration
     * @param bool $advice
     * @return mixed
     */
    public function getApiClientForOrder($method, $configuration)
    {
        $advice = $configuration::get(self::AUTO_ADVICE);

        $methodName = $this->getApiClientForOrderMethodName($method, $advice);

        return $this->{$methodName}();
    }

    /**
     * Get method name to bliskapaczka api client create order action
     *
     * @param string $method
     * @param string $autoAdvice
     * @param Sendit_Bliskapaczka_Helper_Data $senditHelper
     * @return string
     */
    public function getApiClientForOrderMethodName($method, $autoAdvice)
    {
        $type = 'Todoor';

        if ($this->isPoint($method)) {
            $type = 'Order';
        }

        $methodName = 'getApiClient' . $type;

        if ($autoAdvice) {
            $methodName .= 'Advice';
        }

        return $methodName;
    }


    /**
     * Check if shipping method is to point
     *
     * @param string $method
     * @return string
     */
    public function isPoint($method)
    {
        $shortMethodName = $this->getShortMethodName($method);

        if ($shortMethodName == 'point') {
            return true;
        }

        return false;
    }

    /**
     * Short name for shipping method
     *
     * @param string $method
     * @return string
     */
    protected function getShortMethodName($method)
    {
        switch ($method) {
            case 'bliskapaczka':
                $shortMethod = 'point';
                break;

            default:
                $shortMethod = 'courier';
        }

        return $shortMethod;
    }
}
