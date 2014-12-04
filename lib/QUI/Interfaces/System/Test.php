<?php

/**
 * This file contains \QUI\Interfaces\System\Test
 */
namespace QUI\Interfaces\System;

/**
 * Interface Test
 * @package quiqqer/quiqqer
 */
interface Test
{
    /**
     * @return \QUI\System\Tests\Test::STATUS_OK|\QUI\System\Tests\Test::STATUS_ERROR
     */
    public function execute();

    /**
     * @return Bool
     */
    public function isRequired();

    /**
     * @return Bool
     */
    public function isOptional();

    /**
     * Return the name of the Test
     * @return mixed
     */
    public function getTitle();

    /**
     * Return the description of the Test
     * @return string
     */
    public function getDescription();
}
