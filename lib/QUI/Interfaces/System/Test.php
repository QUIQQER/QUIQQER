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

    /**
     * @return boolean
     */
    public function isRequired();

    /**
     * @return boolean
     */
    public function isOptional();

    /**
     * Return the name of the Test
     *
     * @return mixed
     */
    public function getTitle();

    /**
     * Return the description of the Test
     *
     * @return string
     */
    public function getDescription();
}
