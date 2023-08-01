<?php

/**
 * Cancel the setup for the specific providers
 */

use QUI\InstallationWizard\ProviderHandler;

QUI::$Ajax->registerFunction(
    'ajax_installationWizard_cancel',
    function ($providers) {
        $providers = json_decode($providers, true);

        foreach ($providers as $provider) {
            if (!class_exists($provider)) {
                continue;
            }

            $interfaces = class_implements($provider);

            if (!isset($interfaces['QUI\InstallationWizard\InstallationWizardInterface'])) {
                continue;
            }

            ProviderHandler::setProviderStatus(
                new $provider(),
                ProviderHandler::STATUS_SET_UP_DONE
            );
        }
    },
    ['providers'],
    'Permission::checkSU'
);
