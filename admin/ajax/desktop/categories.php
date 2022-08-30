<?php

use QUI\Package\Package;

/**
 * Delete workspaces
 *
 * @param string $ids - Workspace IDs, json array
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_categories',
    function ($type) {
        $Settings       = QUI\Utils\XML\Settings::getInstance();
        $PackageHandler = QUI::getPackageManager();

        $categories = [];
        $packages   = $PackageHandler->getInstalled();

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

            $Collection = $Settings->getCategories($panelXml);
            $categories = array_merge($categories, $Collection->toArray());
        }

        return $categories;
    },
    ['type'],
    'Permission::checkUser'
);
