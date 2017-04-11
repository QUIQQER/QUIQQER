<?php

/**
 * Return a xml category
 *
 * @param array $file - list of xml files
 * @param $category
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_settings_category',
    function ($file, $category) {
        if (file_exists($file)) {
            $files = array($file);
        } else {
            $files = json_decode($file, true);
        }

        $cacheName = 'qui/admin/menu/categories/' . md5(json_encode($files)) . '/' . $category;
        $Settings  = QUI\Utils\XML\Settings::getInstance();
        $Settings->setXMLPath('//quiqqer/settings/window');

        try {
            $result = QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
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
    array('file', 'category'),
    'Permission::checkAdminUser'
);
