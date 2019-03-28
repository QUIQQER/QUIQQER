<?php

/**
 * Checks if terms of use for the backend package store have been accepted
 *
 * @return boolean
 *
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_packages_getStoreUrl',
    function () {
        $packageStoreUrls = QUI::conf('packagestore', 'url');
        $packageStoreUrls = \json_decode($packageStoreUrls, true);
        $lang             = QUI::getUserBySession()->getLang();

        if (empty($packageStoreUrls) || empty($packageStoreUrls[$lang])) {
            return 'https://store.quiqqer.com';
        }

        return $packageStoreUrls[$lang];
    },
    [],
    'Permission::checkAdminUser'
);
