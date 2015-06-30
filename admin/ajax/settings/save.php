<?php

/**
 * @param String $file
 * @param String $params - JSON Params
 *
 * @throws \QUI\Exception
 */
function ajax_settings_save($file, $params)
{
    if (!file_exists($file)) {
        // # locale
        throw new QUI\Exception(
            'Could not save the data. the config file was not found'
        );
    }

    QUI\Utils\XML::setConfigFromXml(
        $file,
        json_decode($params, true)
    );

    // # locale
    QUI::getMessagesHandler()->addSuccess(
        'Konfiguration erfolgreich gespeichert'
    );
}

QUI::$Ajax->register(
    'ajax_settings_save',
    array('file', 'params'),
    'Permission::checkAdminUser'
);
