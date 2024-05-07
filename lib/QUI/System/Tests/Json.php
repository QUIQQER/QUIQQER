<?php

/**
 * This class contains \QUI\System\Tests\Json
 */

namespace QUI\System\Tests;

use QUI;

use function function_exists;

/**
 * JSON Test
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Json extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes([
            'title' => 'json_decode and json_encode',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if json_encode and json_decode available
     *
     * @return int self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        if (function_exists('json_decode') && function_exists('json_encode')) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
