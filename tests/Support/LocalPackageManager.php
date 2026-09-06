<?php

namespace QUITests\Support;

use QUI;

/** Installed applications are available as libraries; their setup belongs to CI. */
final class LocalPackageManager extends QUI\Package\Manager
{
    public function refreshServerList(): void
    {
    }

    public function getInstalledPackage(string $package): QUI\Package\Package
    {
        if (!isset($this->packages[$package])) {
            $this->packages[$package] = new class ($package) extends QUI\Package\Package {
                public function setup(array $params = []): void
                {
                    if ($this->getName() === 'quiqqer/core') {
                        // Core translations are initialized once in the private runtime.
                        parent::setup(array_replace($params, ['localeImport' => false, 'localePublish' => false]));
                    }
                }
            };
        }
        return $this->packages[$package];
    }
}
