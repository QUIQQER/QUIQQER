<?php

/**
 * Return config params from a xml file
 *
 * @param String $file
 * @return Array
 */
function ajax_settings_get($file)
{
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
            $config = array_merge($config, $Config->toArray());
        }
    }

    return $config;
}

QUI::$Ajax->register(
    'ajax_settings_get',
    array('file'),
    'Permission::checkAdminUser'
);
