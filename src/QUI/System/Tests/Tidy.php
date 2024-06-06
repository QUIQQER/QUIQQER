<?php

/**
 * This class contains \QUI\System\Tests\Tidy
 */

namespace QUI\System\Tests;

use QUI;

use function class_exists;

/**
 * Tidy Test
 *
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
        parent::__construct();

        $this->setAttributes([
            'title' => 'Tidy',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_OPTIONAL;
    }

    /**
     * Check, if zlib available
     *
     * @return int self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute(): int
    {
        if (class_exists('tidy')) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
