<?php

namespace Bliskapaczka\Prestashop\Core;

use Bliskapaczka\ApiClient;

/**
 * Bliskapaczka data validator
 *
 * @author Mateusz Koszutowski (mkoszutowski@divante.pl)
 */
class Validator
{
    /**
     * Validate sende data
     *
     * @param array $data
     * @param Bliskapaczka\ApiClien\Mapper\Order\Validator $apiValidator
     * @return bool
     */
    public static function sender($data, $apiValidator)
    {
        # Email validation
        if ($data['senderEmail']) {
            $apiValidator::email($data['senderEmail']);
        }

        // # Phone number validation
        if ($data['senderPhoneNumber']) {
            $apiValidator::phone($data['senderPhoneNumber']);
        }

        // # Post code validation
        if ($data['postCode']) {
            $apiValidator::postCode($data['postCode']);
        }

        return true;
    }
}
