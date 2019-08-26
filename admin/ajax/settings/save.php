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
        $jsonFiles = \json_decode($file, true);
        $files     = [];

        if ($jsonFiles) {
            if (\is_string($jsonFiles)) {
                $files = [$jsonFiles];
            } else {
                $files = $jsonFiles;
            }
        }

        foreach ($files as $file) {
            if (!\file_exists($file)) {
                $file = CMS_DIR.$file;
            }

            if (!\file_exists($file)) {
                QUI\Log\Logger::getLogger()->addError(
                    QUI::getLocale()->get(
                        'quiqqer/quiqqer',
                        'exception.config.save.file.not.found'
                    )
                );

                continue;
            }

            if (\is_string($params)) {
                $params = \json_decode($params, true);
            }

            // csp data
            if (\strpos($file, 'quiqqer/quiqqer/admin/settings/conf.xml') !== false
                && isset($params['securityHeaders_csp'])
            ) {
                unset($params['securityHeaders_csp']);
            }

            // more bad workaround by hen
            // @todo need to fix that
            if (\strpos($file, 'quiqqer/quiqqer/admin/settings/cache.xml') !== false) {
                if (!empty($params['general']['cacheType'])) {
                    $cacheType = $params['general']['cacheType'];

                    $params['handlers'] = \array_fill_keys([
                        'apc',
                        'filesystem',
                        'redis',
                        'memcache'
                    ], 0);

                    if (isset($params['handlers'][$cacheType])) {
                        $params['handlers'][$cacheType] = 1;
                    } else {
                        $params['handlers']['filesystem'] = 1;
                    }
                }
            }

            QUI\Utils\Text\XML::setConfigFromXml($file, $params);

            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get('quiqqer/quiqqer', 'message.config.saved')
            );

            // bad workaround by hen
            if (\strpos($file, 'quiqqer/quiqqer/admin/settings/conf.xml') === false) {
                continue;
            }

            if (isset($params['globals']['quiqqer_version'])) {
                try {
                    QUI::getPackageManager()->setQuiqqerVersion(
                        $params['globals']['quiqqer_version']
                    );
                } catch (\UnexpectedValueException $Exception) {
                    QUI::getMessagesHandler()->addError($Exception->getMessage());
                }
            }

            # Save the current .htaccess content to see if the config changed
            $oldContent = "";

            if (\file_exists(CMS_DIR.".htaccess")) {
                $oldContent = \file_get_contents(CMS_DIR.".htaccess");
            }

            $Htaccess = new QUI\System\Console\Tools\Htaccess();
            $Htaccess->execute();


            $webserverConfig = QUI::conf("webserver", "type");

            if ($webserverConfig !== false && is_string($webserverConfig)
                && \strpos($webserverConfig, "apache") !== false) {
                continue;
            }

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

            if (!\file_exists(CMS_DIR.".htaccess")) {
                continue;
            }

            $newContent = \file_get_contents(CMS_DIR.".htaccess");

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
    ['file', 'params'],
    'Permission::checkAdminUser'
);
