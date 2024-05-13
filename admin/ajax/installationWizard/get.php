<?php

/**
 * Return list of packages which needs a setup
 */

QUI::$Ajax->registerFunction(
    'ajax_installationWizard_get',
    static function (): array {
        if (!QUI::getUserBySession()->isSU()) {
            return [];
        }

        $list = QUI\InstallationWizard\ProviderHandler::getNotSetUpProviderList();

        foreach ($list as $Provider) {
            $Provider->onListInit($list);
        }

        usort($list, static function ($a, $b): int {
            return $a->getPriority() > $b->getPriority() ? 1 : 0;
        });

        return array_map(static function ($Provider) {
            return $Provider->toArray();
        }, $list);
    },
    false,
    ''
);
