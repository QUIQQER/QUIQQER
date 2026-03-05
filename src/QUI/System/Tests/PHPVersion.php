<?php

/**
 * This class contains \QUI\System\Tests\PHPVersion
 */

namespace QUI\System\Tests;

use QUI;

/**
 * CHMOD Test
 */
class PHPVersion extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes([
            'title' => 'QUIQQER - PHP Version - Higher 5.3',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if PHP version is high enough
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute(): int
    {
        if (version_compare(phpversion(), '5.3', '<')) {
            return self::STATUS_ERROR;
        }

        return self::STATUS_OK;
    }
}
