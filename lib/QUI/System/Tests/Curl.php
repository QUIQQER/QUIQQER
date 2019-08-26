<?php

/**
 * This class contains \QUI\System\Tests\Curl
 */

namespace QUI\System\Tests;

use QUI;

/**
 * Curl Test
 *
 * @package quiqqer/quiqqer
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Curl extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes(array(
            'title'       => 'curl test',
            'description' => ''
        ));

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if curl available
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        if (function_exists('curl_version') && function_exists('curl_init')) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
