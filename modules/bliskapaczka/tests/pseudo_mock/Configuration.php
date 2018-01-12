<?php

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class Configuration
{
    public static function get($key)
    {
        switch ($key) {
            case 'BLISKAPACZKA_SENDER_EMAIL':
                return 'jozek.bliskopaczkowy@sendit.pl';
            break;

            case 'BLISKAPACZKA_SENDER_FIRST_NAME':
                return 'Józek';
            break;

            case 'BLISKAPACZKA_SENDER_LAST_NAME':
                return 'Bliskopaczkowy';
            break;

            case 'BLISKAPACZKA_SENDER_PHONE_NUMBER':
                return '504 435 665';
            break;

            case 'BLISKAPACZKA_SENDER_STREET':
                return 'Ulicowa';
            break;

            case 'BLISKAPACZKA_SENDER_BUILDING_NUMBER':
                return '33b';
            break;

            case 'BLISKAPACZKA_SENDER_FLAT_NUMBER':
                return '11';
            break;

            case 'BLISKAPACZKA_SENDER_POST_CODE':
                return '55-100';
            break;

            case 'BLISKAPACZKA_SENDER_CITY':
                return 'Miastowe';
            break;
        }
    }
}
