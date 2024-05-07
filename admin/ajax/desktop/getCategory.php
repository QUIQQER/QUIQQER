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
        $cache = 'quiqqer/package/quiqqer/core/desktopCategories/category/' . md5($type . $category);

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception) {
        }

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

        QUI\Cache\Manager::set($cache, $result);

        return $result;
    },
    ['type', 'category'],
    'Permission::checkUser'
);
