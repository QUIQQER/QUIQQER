<?php

/**
 * This file contains QUI\System;
 */

namespace QUI\System;

use QUI;

/**
 * System test
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
abstract class Test extends QUI\QDOM implements QUI\Interfaces\System\Test
{
    const STATUS_OK = 1;
    const STATUS_ERROR = -1;

    const TEST_IS_REQUIRED = 1;
    const TEST_IS_OPTIONAL = 2;

    /**
     * @var int
     */
    protected int $isRequired = self::TEST_IS_REQUIRED;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setAttributes([
            'title' => '',
            'description' => ''
        ]);
    }

    /**
     * Is the test require?
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return ($this->isRequired == self::TEST_IS_REQUIRED);
    }

    /**
     * Is the test optional?
     *
     * @return bool
     */
    public function isOptional(): bool
    {
        return ($this->isRequired == self::TEST_IS_OPTIONAL);
    }

    /**
     * Return the test title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getAttribute('title');
    }

    /**
     * Return the test description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getAttribute('description');
    }
}
