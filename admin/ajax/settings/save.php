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
                $file = CMS_DIR . $file;
            }

            if (!file_exists($file)) {
                QUI\Log\Logger::getLogger()->addError(
                    QUI::getLocale()->get(
                        'quiqqer/quiqqer',
                        'exception.config.save.file.not.found'
                    )
                );

                continue;
            }

            if (is_string($params)) {
                $params = json_decode($params, true);
            }

            // csp data
            if (strpos($file, 'quiqqer/quiqqer/admin/settings/conf.xml') !== false
                && isset($params['securityHeaders_csp'])
            ) {
                unset($params['securityHeaders_csp']);
            }

            QUI\Utils\Text\XML::setConfigFromXml($file, $params);

            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get('quiqqer/quiqqer', 'message.config.saved')
            );


            // Böser workaround by hen
            if (strpos($file, 'quiqqer/quiqqer/admin/settings/conf.xml') === false) {
                continue;
            }

            # Save the current .htaccess content to see if the config changed
            $oldContent = "";

            if (file_exists(CMS_DIR . ".htaccess")) {
                $oldContent = file_get_contents(CMS_DIR . ".htaccess");
            }

            $Htaccess = new QUI\System\Console\Tools\Htaccess();
            $Htaccess->execute();


            # Compare new and old .htaccess
            try {
                $webServer = QUI\Utils\System\Webserver::detectInstalledWebserver();
            } catch (\Exception $Exception) {
                $webServer = "";
            }

            if ($webServer === QUI\Utils\System\Webserver::WEBSERVER_APACHE) {
                continue;
            }

            if (empty($oldContent)) {
                continue;
            }

            if (!file_exists(CMS_DIR . ".htaccess")) {
                continue;
            }

            $newContent = file_get_contents(CMS_DIR . ".htaccess");

            if ($newContent != $oldContent) {
                QUI::getMessagesHandler()->addInformation(
                    QUI::getLocale()->get(
                        "quiqqer/quiqqer",
                        "message.config.webserver.changed"
                    )
                );
            }
        }
    },
    array('file', 'params'),
    'Permission::checkAdminUser'
);
