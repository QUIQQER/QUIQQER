<?php

/**
 * This file contains the \QUI\Update class
 */

namespace QUI\package;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

/**
 * QUIQQER Installation
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class Installer extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {

    }
}

?>