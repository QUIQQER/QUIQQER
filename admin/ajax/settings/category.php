<?php

/**
 * Return a xml category
 *
 * @param array $file - list of xml files
 * @param $category
 * @return String
 */
function ajax_settings_category($file, $category)
{
    $files  = json_decode($file, true);
    $result = '';

    $cacheName = 'qui/admin/menu/categories/' . md5($file);

    try {
        return QUI\Cache\Manager::get($cacheName);

    } catch (QUI\Exception $Exception) {

    }

    foreach ($files as $file) {

        if (!file_exists($file)) {
            continue;
        }

        $Category = QUI\Utils\XML::getSettingCategoriesFromXml(
            $file,
            $category
        );

        if (!$Category) {
            continue;
        }

        $result .= QUI\Utils\DOM::parseCategorieToHTML($Category);
    }


    QUI\Cache\Manager::set($cacheName, $result);

    return $result;
}

QUI::$Ajax->register(
    'ajax_settings_category',
    array('file', 'category'),
    'Permission::checkAdminUser'
);
