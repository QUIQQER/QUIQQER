<?php

/**
 * Cancel the setup for the specific providers
 */

use QUI\InstallationWizard\InstallationWizardInterface;
use QUI\InstallationWizard\ProviderHandler;

QUI::$Ajax->registerFunction(
    'ajax_installationWizard_cancel',
    static function ($providers): void {
        $providers = json_decode($providers, true);

        foreach ($providers as $provider) {
            if (!class_exists($provider)) {
                continue;
            }

            $interfaces = class_implements($provider);

            if (!isset($interfaces[InstallationWizardInterface::class])) {
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
