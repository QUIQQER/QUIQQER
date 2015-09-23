<?php

/**
 * @param String $file
 * @param String $params - JSON Params
 *
 * @throws \QUI\Exception
 */
function ajax_settings_save($file, $params)
{
    $files = json_decode($file, true);

    foreach ($files as $file) {

        if (!file_exists($file)) {
            QUI\Log\Logger::getLogger()->addError(
                "Could not save the data. the config file {$file} was not found"
            );

            continue;
        }

        QUI\Utils\XML::setConfigFromXml(
            $file,
            json_decode($params, true)
        );
    }

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
