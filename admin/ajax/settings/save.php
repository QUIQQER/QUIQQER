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
                    QUI::getLocale()->get(
                        'quiqqer/quiqqer',
                        'exception.config.save.file.not.found'
                    )
                );

                continue;
            }


            $params = json_decode($params, true);

            // csp data
            if (strpos($file, 'quiqqer/quiqqer/admin/settings/conf.xml') !== false
                && isset($params['securityHeaders_csp'])
            ) {
                $cspData = $params['securityHeaders_csp'];
                unset($params['securityHeaders_csp']);
            }

            QUI\Utils\Text\XML::setConfigFromXml($file, $params);


            // save csp data -> workaround
            if (isset($cspData)) {
                $CSP = QUI\System\CSP::getInstance();
                $CSP->clearCSPDirectives();

                foreach ($cspData as $key => $value) {
                    try {
                        $CSP->setCSPDirectiveToConfig($key, $value);
                    } catch (QUI\Exception $Exception) {
                        \QUI\System\Log::addWarning($Exception->getMessage());
                    }
                }
            }
        }


        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/quiqqer', 'message.config.saved')
        );
    },
    array('file', 'params'),
    'Permission::checkAdminUser'
);
