<?php

/**
 * Return the project panel categories / tabs
 *
 * @param string $project - name of the project
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_project_panel_categories_category',
    function ($file, $category) {
        if (\file_exists($file)) {
            $files = [$file];
        } else {
            $files = \json_decode($file, true);
        }

        $cacheName = 'quiqqer/package/quiqqer/quiqqer/menu/categories/'.\md5(\json_encode($files)).'/'.$category;
        $Settings  = QUI\Utils\XML\Settings::getInstance();
        $Settings->setXMLPath('//quiqqer/project/settings/window');

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
    ['file', 'category'],
    'Permission::checkAdminUser'
);
