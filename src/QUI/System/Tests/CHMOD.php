<?php

/**
 * This class contains \QUI\System\Tests\CHMOD
 */

namespace QUI\System\Tests;

use QUI;

/**
 * CHMOD Test
 */
class CHMOD extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes([
            'title' => 'QUIQQER Directory writable',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if mod rewrite is enabled
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute(): int
    {
        // check if cms dir is writable
        if (is_writable(CMS_DIR)) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
