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
        if (\file_exists($file)) {
            $files = [$file];
        } else {
            $files = \json_decode($file, true);
        }

        $cacheName = 'quiqqer/package/quiqqer/quiqqer/menu/windows/'.\md5(\json_encode($files));
        $Settings  = QUI\Utils\XML\Settings::getInstance();

        try {
            $result = QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
            $windowName = false;

            if (is_array($files) &&
                in_array('packages/quiqqer/quiqqer/admin/settings/cache.xml', $files)) {
                $windowName = 'quiqqer-cache';
            }

            if (!empty($windowName) && $windowName !== 'qui-desktop-panel') {
                $Settings->setXMLPath('//quiqqer/settings/window[@name="'.$windowName.'"]');
            } else {
                $Settings->setXMLPath('//quiqqer/settings/window');
            }

            $result = $Settings->getPanel($files, $windowName);

            $result['name']       = $windowName;
            $result['categories'] = $result['categories']->toArray();

            foreach ($result['categories'] as $key => $category) {
                $result['categories'][$key]['items'] = $result['categories'][$key]['items']->toArray();
            }

            QUI\Cache\Manager::set($cacheName, $result);
        }

        // category translation
        $categories = $result['categories'];

        $result['categories'] = [];

        foreach ($categories as $key => $category) {
            if (isset($category['title']) && \is_array($category['title'])) {
                $category['text'] = QUI::getLocale()->get(
                    $category['title'][0],
                    $category['title'][1]
                );

                $category['title'] = QUI::getLocale()->get(
                    $category['title'][0],
                    $category['title'][1]
                );
            }

            if (empty($category['text']) && !empty($category['title'])) {
                $category['text'] = $category['title'];
            }

            $result['categories'][] = $category;
        }

        return $result;
    },
    ['file'],
    'Permission::checkAdminUser'
);
