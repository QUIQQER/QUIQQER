<?php

/**
 * Return a xml category
 *
 * @param array $file - list of xml files
 * @param $category
 * @param $windowName
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_settings_category',
    static function ($file, $category, $windowName) {
        if (file_exists($file)) {
            $files = [$file];
        } else {
            $files = json_decode($file, true);
        }

        $cacheName = 'quiqqer/package/quiqqer/core/menu/categories/' . md5(json_encode($files)) . '/' . $category;

        try {
            $result = QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
            $Settings = QUI\Utils\XML\Settings::getInstance();

            if (!empty($windowName) && $windowName !== 'qui-desktop-panel') {
                $Settings->setXMLPath('//quiqqer/settings/window[@name="' . $windowName . '"]');
            } else {
                $Settings->setXMLPath('//quiqqer/settings/window');
            }

            try {
                $result = $Settings->getCategoriesHtml($files, $category);
                QUI\Cache\Manager::set($cacheName, $result);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                throw $Exception;
            }
        }

        return $result;
    },
    ['file', 'category', 'windowName'],
    [
        'Permission::checkAdminUser',
        'quiqqer.settings'
    ]
);
