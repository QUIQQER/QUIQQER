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

        $result    = '';
        $cacheName = 'qui/admin/menu/categories/' . md5($file) . '/' . $category;

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
        }

        if (!is_array($files)) {
            $files = array($files);
        }

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $categories = array();

            if ($category) {
                $Category = QUI\Utils\Text\XML::getSettingCategoryFromXml($file, $category);

                if ($Category) {
                    $categories[] = $Category;
                }
            } else {
                $categories = QUI\Utils\Text\XML::getSettingCategoriesFromXml($file);
            }

            foreach ($categories as $Category) {
                $result .= QUI\Utils\DOM::parseCategorieToHTML($Category);
            }
        }

        QUI\Cache\Manager::set($cacheName, $result);

        return $result;
    },
    array('file', 'category'),
    'Permission::checkAdminUser'
);
