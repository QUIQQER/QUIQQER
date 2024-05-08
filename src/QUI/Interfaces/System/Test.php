<?php

/**
 * This file contains \QUI\Interfaces\System\Test
 */

namespace QUI\Interfaces\System;

/**
 * Interface Test
 *
 * @licence For copyright and license information, please view the /README.md
 */
interface Test
{
    /**
     * @return \QUI\System\Test::STATUS_OK|\QUI\System\Tests\Test::STATUS_ERROR
     */
    public function execute();

    public function isRequired(): bool;

    public function isOptional(): bool;

    public function getTitle(): string;

    public function getDescription(): string;
}
