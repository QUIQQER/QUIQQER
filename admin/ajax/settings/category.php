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

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
        }

        try {
            $result = QUI\Utils\XML\Settings::getCategoriesHtml($files, $category);

            QUI\Cache\Manager::set($cacheName, $result);

            return $result;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        throw $Exception;
    },
    array('file', 'category'),
    'Permission::checkAdminUser'
);
