<?php

/**
 * Return list of packages which needs a setup
 */
QUI::$Ajax->registerFunction(
    'ajax_installationWizard_getStep',
    function ($provider, $step) {
        if (!\class_exists($provider)) {
            return '';
        }

        $interfaces = \class_implements($provider);

        if (!isset($interfaces['QUI\InstallationWizard\InstallationWizardInterface'])) {
            return '';
        }

        /* @var $Provider QUI\InstallationWizard\InstallationWizardInterface */
        $Provider = new $provider();
        $Step     = $Provider->getStep($step);

        return $Step->create();
    },
    ['provider', 'step'],
    'Permission::checkSU'
);
