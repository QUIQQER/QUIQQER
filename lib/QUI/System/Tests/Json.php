<?php

/**
 * This class contains \QUI\System\Tests\Json
 */

namespace QUI\System\Tests;

use QUI;

/**
 * JSON Test
 *
 * @package quiqqer/quiqqer
 * @author www.pcsg.de (Henning Leutz)
 */
class Json extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setAttributes(array(
            'title'       => 'json_decode and json_encode',
            'description' => ''
        ));

        $this->_isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if json_encode and json_decode available
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        if ( function_exists('json_decode') && function_exists('json_encode') ) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
