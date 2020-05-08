<?php

/**
 * cache purging
 */
QUI::$Ajax->registerFunction(
    'ajax_system_cache_mongoAvailable',
    function () {
        try {
            QUI::getPackage('mongodb/mongodb');
        } catch (QUI\Exception $Exception) {
            return false;
        }

        return \class_exists('\MongoDB\Client');
    },
    false,
    'Permission::checkSU'
);
