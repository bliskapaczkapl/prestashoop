<?php
namespace Bliskapaczka\Prestashop\Core\Mapper;

/**
 * Class to map order data to data acceptable by Sendit Bliskapaczka API
 */
class Order
{
    /**
     * Prepare mapped data
     *
     * @param Order $order
     * @param Address $shippingAddress
     * @param Customer $customer
     * @param Bliskapaczka\Prestashop\Core\Helper $helper
     * @param \Configuration $configuration
     * @return array
     */
    public function getData($order, $shippingAddress, $customer, $helper, $configuration)
    {
        $data = [];

        $data['receiverFirstName'] = $shippingAddress->firstname;
        $data['receiverLastName'] = $shippingAddress->lastname;
        $data['receiverPhoneNumber'] = $helper->telephoneNumberCleaning($shippingAddress->phone_mobile);
        $data['receiverEmail'] = $customer->email;

        $data['operatorName'] = $order->pos_operator;
        $data['destinationCode'] = $order->pos_code;

        $data['parcel'] = [
            'dimensions' => $this->getParcelDimensions($helper)
        ];

        $data = $this->prepareSenderData($data, $helper, $configuration);

        return $data;
    }

    /**
     * Get parcel dimensions in format accptable by Bliskapaczka API
     *
     * @param \Bliskapaczka\Prestashop\Core\$helper $helper
     * @return array
     */
    private function getParcelDimensions($helper)
    {
        return $helper->getParcelDimensions();
    }

    /**
     * Prepare sender data in fomrat accptable by Bliskapaczka API
     *
     * @param array $data
     * @param \Bliskapaczka\Prestashop\Core\$helper $helper
     * @param \Configuration $configuration
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareSenderData($data, $helper, $configuration)
    {
        if ($configuration::get($helper::SENDER_EMAIL)) {
            $data['senderEmail'] = $configuration::get($helper::SENDER_EMAIL);
        }

        if ($configuration::get($helper::SENDER_FIRST_NAME)) {
            $data['senderFirstName'] = $configuration::get($helper::SENDER_FIRST_NAME);
        }

        if ($configuration::get($helper::SENDER_LAST_NAME)) {
            $data['senderLastName'] = $configuration::get($helper::SENDER_LAST_NAME);
        }

        if ($configuration::get($helper::SENDER_PHONE_NUMBER)) {
            $data['senderPhoneNumber'] = $helper->telephoneNumberCleaning(
                $configuration::get($helper::SENDER_PHONE_NUMBER)
            );
        }

        if ($configuration::get($helper::SENDER_STREET)) {
            $data['senderStreet'] = $configuration::get($helper::SENDER_STREET);
        }

        if ($configuration::get($helper::SENDER_BUILDING_NUMBER)) {
            $data['senderBuildingNumber'] = $configuration::get($helper::SENDER_BUILDING_NUMBER);
        }

        if ($configuration::get($helper::SENDER_FLAT_NUMBER)) {
            $data['senderFlatNumber'] = $configuration::get($helper::SENDER_FLAT_NUMBER);
        }

        if ($configuration::get($helper::SENDER_POST_CODE)) {
            $data['senderPostCode'] = $configuration::get($helper::SENDER_POST_CODE);
        }

        if ($configuration::get($helper::SENDER_CITY)) {
            $data['senderCity'] = $configuration::get($helper::SENDER_CITY);
        }

        return $data;
    }
}
