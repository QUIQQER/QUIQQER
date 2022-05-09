<?php

/**
 * Return list of packages which needs a setup
 */
QUI::$Ajax->registerFunction(
    'ajax_installationWizard_get',
    function () {
        if (!QUI::getUserBySession()->isSU()) {
            return [];
        }

        $list = QUI\InstallationWizard\ProviderHandler::getNotSetUpProviderList();

        foreach ($list as $Provider) {
            $Provider->onListInit($list);
        }

        usort($list, function ($a, $b) {
            return $a->getPriority() > $b->getPriority() ? 1 : 0;
        });

        $list = array_map(function ($Provider) {
            return $Provider->toArray();
        }, $list);

        return $list;
    },
    false,
    ''
);
