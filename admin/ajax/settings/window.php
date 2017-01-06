<?php

/**
 * Return XML Window fromm a xml settings file
 *
 * @param string $file - Path to file, or JSON Array with xml files
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_settings_window',
    function ($file) {
        if (file_exists($file)) {
            $files = array($file);
        } else {
            $files = json_decode($file, true);
        }

        $cacheName = 'qui/admin/menu/windows/' . md5(json_encode($files));
        $Settings  = QUI\Utils\XML\Settings::getInstance();

        try {
            $result = QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
            $result = $Settings->getPanel($files);

            $result['categories'] = $result['categories']->toArray();

            foreach ($result['categories'] as $key => $category) {
                $result['categories'][$key]['items'] = $result['categories'][$key]['items']->toArray();
            }

            QUI\Cache\Manager::set($cacheName, $result);
        }

        // category translation
        $categories = $result['categories'];

        $result['categories'] = array();

        foreach ($categories as $key => $category) {
            if (isset($category['text']) && is_array($category['text'])) {
                $category['text'] = QUI::getLocale()->get(
                    $category['text'][0],
                    $category['text'][1]
                );
            }

            if (isset($category['title']) && is_array($category['title'])) {
                $category['text'] = QUI::getLocale()->get(
                    $category['title'][0],
                    $category['title'][1]
                );
            }

            $result['categories'][] = $category;
        }

        return $result;
    },
    array('file'),
    'Permission::checkAdminUser'
);
