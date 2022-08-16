<?php

use QUI\InstallationWizard\ProviderHandler;

/**
 * Execute the setup for the specific provider
 */
QUI::$Ajax->registerFunction(
    'ajax_installationWizard_execute',
    function ($provider, $data) {
        if (!class_exists($provider)) {
            return false;
        }

        $interfaces = class_implements($provider);

        if (!isset($interfaces['QUI\InstallationWizard\InstallationWizardInterface'])) {
            return false;
        }

        ProviderHandler::getConfig()->set('execute', 'provider', $provider);
        ProviderHandler::getConfig()->set('execute', 'data', $data);
        ProviderHandler::getConfig()->save();
        return true;
    },
    ['provider', 'data'],
    'Permission::checkSU'
);
