<?php

namespace Bliskapaczka\Prestashop\Core;

use Bliskapaczka\ApiClient;

/**
 * Bliskapaczka helper
 *
 * @author Mateusz Koszutowski (mkoszutowski@divante.pl)
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

    const API_KEY = 'BLISKAPACZKA_API_KEY';
    const TEST_MODE = 'BLISKAPACZKA_TEST_MODE';

    const GOOGLE_MAP_API_KEY = 'BLISKAPACZKA_GOOGLE_MAP_API_KEY';

    const BLISKAPACZKA_CARRIER_ID = 'BLISKAPACZKA_CARRIER_ID';

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
     * @param array $priceList - price list
     * @param bool $taxInc - return price with tax
     * @return float
     */
    public function getLowestPrice($priceList, $taxInc = true)
    {
        $lowestPriceTaxExc = null;
        $lowestPriceTaxInc = null;

        foreach ($priceList as $carrier) {
            if ($carrier->availabilityStatus == false) {
                continue;
            }

            if ($lowestPriceTaxInc == null || $lowestPriceTaxInc > $carrier->price->gross) {
                $lowestPriceTaxExc = $carrier->price->net;
                $lowestPriceTaxInc = $carrier->price->gross;
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
     * @param array $priceList
     * @param string $carrierName
     * @param bool $taxInc
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
        $apiClient = $this->getApiClient();
        $priceList = $apiClient->getPricing(
            array("parcel" => array('dimensions' => $this->getParcelDimensions()))
        );

        return json_decode($priceList);
    }

    /**
     * Get widget configuration
     *
     * @param array $priceList
     * @param bool $freeShipping
     * @return array
     */
    public function getOperatorsForWidget($priceList = array(), $freeShipping = false)
    {
        if (!$priceList) {
            $priceList = $this->getPriceList();
        }
        $operators = array();

        foreach ($priceList as $operator) {
            if ($operator->availabilityStatus != false) {
                $price = $operator->price->gross;
                if ($freeShipping == true) {
                    $price = 0;
                }

                $operators[] = array(
                    "operator" => $operator->operatorName,
                    "price" => $price
                );
            }
        }

        return json_encode($operators);
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
            && (!isset($freeShipping) || (isset($freeShipping) && !$freeShipping))
        ) {
            $bliskapaczkaFreeShipping = false;
        } else {
            $bliskapaczkaFreeShipping = true;
        }

        return $bliskapaczkaFreeShipping;
    }

    /**
     * Return bliskapaczka.pl module settings like in cart
     *
     * @param Cart $cart
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
     * Remove all non numeric chars from phone number
     *
     * @param string $phoneNumber
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
     * @param string $configValue
     * @return string
     */
    public function getApiMode($configValue = '')
    {
        $mode = '';

        switch ($configValue) {
            case '1':
                $mode = 'test';
                break;

            default:
                $mode = 'prod';
                break;
        }

        return $mode;
    }
}
