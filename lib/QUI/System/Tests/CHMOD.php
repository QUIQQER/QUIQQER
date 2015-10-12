<?php

/**
 * This class contains \QUI\System\Tests\CHMOD
 */

namespace QUI\System\Tests;

use QUI;

/**
 * CHMOD Test
 *
 * @package quiqqer/quiqqer
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class CHMOD extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setAttributes(array(
            'title'       => 'QUIQQER Directory writable',
            'description' => ''
        ));

        $this->_isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if mod rewrite is enabled
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        // check if cms dir is writable
        if (is_writable(CMS_DIR)) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
