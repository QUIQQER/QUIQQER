<?php

/**
 * This file contains \QUI\Interfaces\System\Patch
 */
namespace QUI\Interfaces\System;

/**
 * Interface Test
 *
 * @package quiqqer/quiqqer
 */
interface Patch
{
    /**
     * Execute the patch
     *
     * @return Bool
     */
    public function execute();


    /**
     * Return the minimum version for the patch
     *
     * @return String
     */
    public function minimumVersion();
}
