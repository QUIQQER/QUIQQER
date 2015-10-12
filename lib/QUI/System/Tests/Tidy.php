<?php

/**
 * This class contains \QUI\System\Tests\Tidy
 */

namespace QUI\System\Tests;

use QUI;

/**
 * Tidy Test
 *
 * @package quiqqer/quiqqer
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Tidy extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setAttributes(array(
            'title'       => 'Tidy',
            'description' => ''
        ));

        $this->_isRequired = self::TEST_IS_OPTIONAL;
    }

    /**
     * Check, if zlib available
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        if (class_exists('tidy')) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
