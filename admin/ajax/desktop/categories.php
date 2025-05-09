<?php

/**
 * Delete workspaces
 *
 * @param string $ids - Workspace IDs, json array
 */

use QUI\Package\Package;

QUI::$Ajax->registerFunction(
    'ajax_desktop_categories',
    static function ($type) {
        $cache = 'quiqqer/package/quiqqer/core/desktopCategories/list/' . md5($type);

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception) {
        }

        $Settings = QUI\Utils\XML\Settings::getInstance();
        $PackageHandler = QUI::getPackageManager();

        $categories = [];
        $packages = $PackageHandler->getInstalled();

        foreach ($packages as $package) {
            $Package = $PackageHandler->getInstalledPackage($package['name']);

            if (!$Package->isQuiqqerPackage()) {
                continue;
            }

            $panelXml = $Package->getXMLFilePath(Package::PANEL_XML);

            if (!$panelXml) {
                continue;
            }

            $type = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $type);
            $type = str_replace(['"', "'"], '-', $type);
            $type = str_replace(['&', '<', '>'], '-', $type);

            $Settings->setXMLPath('//quiqqer/window[@name="' . $type . '"]');

            $Collection = $Settings->getCategories($panelXml);
            $categories = array_merge($categories, $Collection->toArray());
        }

        QUI\Cache\Manager::set($cache, $categories);

        return $categories;
    },
    ['type'],
    'Permission::checkUser'
);
