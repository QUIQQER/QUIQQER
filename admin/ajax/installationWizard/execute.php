<?php

use QUI\InstallationWizard\ProviderHandler;

/**
 * Execute the setup for the specific provider
 */
QUI::$Ajax->registerFunction(
    'ajax_installationWizard_execute',
    function ($provider, $data) {
        if (!class_exists($provider)) {
            return;
        }

        $interfaces = class_implements($provider);

        if (!isset($interfaces['QUI\InstallationWizard\InstallationWizardInterface'])) {
            return;
        }

        /* @var $Provider QUI\InstallationWizard\InstallationWizardInterface */
        $Provider = new $provider();
        $Provider->execute(json_decode($data, true));

        ProviderHandler::setProviderStatus($Provider, ProviderHandler::STATUS_SET_UP_DONE);
    },
    ['provider', 'data'],
    'Permission::checkSU'
);
