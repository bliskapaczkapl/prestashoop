<?php

namespace Bliskapaczka\Prestashop\Core;

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testClassHasMethods()
    {
        $this->assertTrue(method_exists('\Bliskapaczka\Prestashop\Core\Validator', 'sender'));
    }
}
