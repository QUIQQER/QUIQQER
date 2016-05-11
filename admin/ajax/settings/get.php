<?php

/**
 * Return config params from a xml file
 *
 * @param string $file
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_settings_get',
    function ($file) {
        $files  = json_decode($file, true);
        $config = array();

        if (is_string($files)) {
            $files = array($files);
        }

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $Config = QUI\Utils\XML::getConfigFromXml($file);

            if ($Config) {
                $config = array_merge_recursive($config, $Config->toArray());
            }
        }

        return $config;
    },
    array('file'),
    'Permission::checkAdminUser'
);
