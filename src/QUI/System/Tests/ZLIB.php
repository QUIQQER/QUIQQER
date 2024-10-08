<?php

/**
 * This class contains \QUI\System\Tests\ZLIB
 */

namespace QUI\System\Tests;

use QUI;

/**
 * ZLIB Test
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class ZLIB extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes([
            'title' => 'ZLIB',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if zlib available
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute(): int
    {
        if (function_exists('gzcompress')) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
