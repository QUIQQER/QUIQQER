<?php

/**
 * @param string $file
 * @param string $params - JSON Params
 *
 * @throws \QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_settings_save',
    function ($file, $params) {
        $jsonFiles = json_decode($file, true);
        $files     = array();

        if ($jsonFiles) {
            if (is_string($jsonFiles)) {
                $files = array($jsonFiles);
            } else {
                $files = $jsonFiles;
            }
        }

        foreach ($files as $file) {
            if (!file_exists($file)) {
                // #locale
                QUI\Log\Logger::getLogger()->addError(
                    "Could not save the data. the config file {$file} was not found" // #locale
                );

                continue;
            }

            QUI\Utils\Text\XML::setConfigFromXml(
                $file,
                json_decode($params, true)
            );
        }

        // # locale
        QUI::getMessagesHandler()->addSuccess(
            'Konfiguration erfolgreich gespeichert'
        );
    },
    array('file', 'params'),
    'Permission::checkAdminUser'
);
