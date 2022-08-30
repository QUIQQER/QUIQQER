<?php

/**
 * Delete workspaces
 *
 * @param string $ids - Workspace IDs, json array
 */

use QUI\Package\Package;

QUI::$Ajax->registerFunction(
    'ajax_desktop_getCategory',
    function ($type, $category) {
        $Settings = QUI\Utils\XML\Settings::getInstance();

        $PackageHandler = QUI::getPackageManager();
        $packages       = $PackageHandler->getInstalled();
        $result         = '';

        foreach ($packages as $package) {
            $Package = $PackageHandler->getInstalledPackage($package['name']);

            if (!$Package->isQuiqqerPackage()) {
                continue;
            }

            $panelXml = $Package->getXMLFilePath(Package::PANEL_XML);

            if (!$panelXml) {
                continue;
            }

            $Settings->setXMLPath('//quiqqer/window[@name="' . $type . '"]');

            $result .= $Settings->getCategoriesHtml($panelXml, $category);
        }

        return $result;
    },
    ['type', 'category'],
    'Permission::checkUser'
);
