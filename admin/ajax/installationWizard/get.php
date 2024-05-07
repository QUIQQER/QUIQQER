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

        usort($list, fn($a, $b) => $a->getPriority() > $b->getPriority() ? 1 : 0);

        return array_map(fn($Provider) => $Provider->toArray(), $list);
    },
    false,
    ''
);
