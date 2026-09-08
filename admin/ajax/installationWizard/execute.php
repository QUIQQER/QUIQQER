<?php

/**
 * Execute the setup for the specific provider
 */

use QUI\InstallationWizard\ProviderHandler;

QUI::getAjax()->registerFunction(
    'ajax_installationWizard_execute',
    static fn(string $provider, string $data): bool => ProviderHandler::prepareExecution($provider, $data),
    ['provider', 'data'],
    'Permission::checkSU'
);
