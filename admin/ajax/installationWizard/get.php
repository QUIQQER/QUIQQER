<?php

/**
 * Return list of packages which needs a setup
 */
QUI::$Ajax->registerFunction(
    'ajax_installationWizard_get',
    function () {
        $list = QUI\InstallationWizard\ProviderHandler::getNotSetUpProviderList();
        $list = \array_map(function ($Provider) {
            return $Provider->toArray();
        }, $list);

        return $list;
    },
    false,
    'Permission::checkSU'
);
