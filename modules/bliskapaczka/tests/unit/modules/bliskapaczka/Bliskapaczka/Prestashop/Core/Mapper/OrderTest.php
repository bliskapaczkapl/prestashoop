<?php

namespace Bliskapaczka\Prestashop\Core\Mapper;

use PHPUnit\Framework\TestCase;
use Bliskapaczka\Prestashop\Core\Helper;

class OrderTest extends TestCase
{

    protected function setUp()
    {
        $this->receiverFirstName = 'Zenek';
        $this->receiverLastName = 'Bliskopaczki';
        $this->receiverPhoneNumber = '504 445 665';
        $this->receiverEmail = 'zenek.bliskopaczki@sendit.pl';
        $this->operatorName = 'INPOST';
        $this->destinationCode = 'KRA010';

        $this->senderEmail = 'jozek.bliskopaczkowy@sendit.pl';
        $this->senderFirstName = 'Józek';
        $this->senderLastName = 'Bliskopaczkowy';
        $this->senderPhoneNumber = '504 435 665';
        $this->senderStreet = 'Ulicowa';
        $this->senderBuildingNumber = '33b';
        $this->senderFlatNumber = '11';
        $this->senderPostCode = '55-100';
        $this->senderCity = 'Miastowe';

        $this->addressMock = $this->getMockBuilder(\Address::class)
                                    ->disableOriginalConstructor()
                                    ->disableOriginalClone()
                                    ->disableArgumentCloning()
                                    ->disallowMockingUnknownTypes()
                                    ->setMethods(array())
                                    ->getMock();

        $this->addressMock->firstname = $this->receiverFirstName;
        $this->addressMock->lastname = $this->receiverLastName;
        $this->addressMock->phone_mobile = $this->receiverPhoneNumber;

        $this->orderMock = $this->getMockBuilder(Order::class)
                                     ->disableOriginalConstructor()
                                     ->disableOriginalClone()
                                     ->disableArgumentCloning()
                                     ->disallowMockingUnknownTypes()
                                     ->setMethods(array())
                                     ->getMock();

        $this->orderMock->pos_operator = $this->operatorName;
        $this->orderMock->pos_code = $this->destinationCode;

        $this->customerMock = $this->getMockBuilder(\Customer::class)
                                     ->disableOriginalConstructor()
                                     ->disableOriginalClone()
                                     ->disableArgumentCloning()
                                     ->disallowMockingUnknownTypes()
                                     ->setMethods(array())
                                     ->getMock();

        $this->customerMock->email = $this->receiverEmail;

        $this->helperMock = $this->getMockBuilder(\Bliskapaczka\Prestashop\Core\Helper::class)
                                     ->disableOriginalConstructor()
                                     ->disableOriginalClone()
                                     ->disableArgumentCloning()
                                     ->disallowMockingUnknownTypes()
                                     ->setMethods(
                                         array(
                                             'getParcelDimensions',
                                             'telephoneNumberCleaning'
                                         )
                                     )
                                     ->getMock();

        $dimensions = array(
            "height" => 12,
            "length" => 12,
            "width" => 12,
            "weight" => 1
        );

        $phoneMap = [
            ['504 445 665', '504445665'],
            ['504 435 665', '504435665']
        ];

        $this->helperMock->method('getParcelDimensions')->will($this->returnValue($dimensions));
        $this->helperMock->method('telephoneNumberCleaning')
            ->will($this->returnValueMap($phoneMap));

        $this->configurationPseudoMock = new \Configuration();
    }

    public function testClassExists()
    {
        $this->assertTrue(class_exists('\Bliskapaczka\Prestashop\Core\Mapper\Order'));
    }

    public function testTypeOfReturnedData()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertTrue(is_array($data));
    }

    public function testMapperForReceiverFirstName()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertEquals($this->receiverFirstName, $data['receiverFirstName']);
    }

    public function testMapperForReceiverLastName()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertEquals($this->receiverLastName, $data['receiverLastName']);
    }

    public function testMapperForReceiverPhoneNumber()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertEquals('504445665', $data['receiverPhoneNumber']);
    }

    public function testMapperForReceiverEmail()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertEquals($this->receiverEmail, $data['receiverEmail']);
    }

    public function testMapperForOperatorName()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertEquals($this->operatorName, $data['operatorName']);
    }

    public function testMapperForDestinationCode()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertEquals($this->destinationCode, $data['destinationCode']);
    }

    public function testMapperForParcel()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertTrue(is_array($data['parcel']));
    }

    public function testMapperForSenderData()
    {
        $mapper = new \Bliskapaczka\Prestashop\Core\Mapper\Order();
        $data = $mapper->getData(
            $this->orderMock,
            $this->addressMock,
            $this->customerMock,
            $this->helperMock,
            $this->configurationPseudoMock
        );

        $this->assertEquals($this->senderEmail, $data['senderEmail']);
        $this->assertEquals($this->senderFirstName, $data['senderFirstName']);
        $this->assertEquals($this->senderLastName, $data['senderLastName']);
        $this->assertEquals('504435665', $data['senderPhoneNumber']);
        $this->assertEquals($this->senderStreet, $data['senderStreet']);
        $this->assertEquals($this->senderBuildingNumber, $data['senderBuildingNumber']);
        $this->assertEquals($this->senderFlatNumber, $data['senderFlatNumber']);
        $this->assertEquals($this->senderPostCode, $data['senderPostCode']);
        $this->assertEquals($this->senderCity, $data['senderCity']);
    }
}
