<?php

/**
 * mongoDB data check
 */

use QUI\Cache\QuiqqerMongoDriver;

QUI::$Ajax->registerFunction(
    'ajax_system_cache_mongoCheck',
    function ($host, $database, $collection, $username, $password) {
        try {
            QUI::getPackage('mongodb/mongodb');
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception('MongoDB Client not installed');
        }

        if (!\class_exists('\MongoDB\Client')) {
            throw new QUI\Exception('MongoDB Client not installed');
        }

        // database server
        if (empty($host)) {
            $host = 'localhost';
        }

        if (empty($database)) {
            $database = 'local';
        }

        if (empty($collection)) {
            $collection = 'quiqqer.longterm';
        }

        if (\strpos($host, 'mongodb://') === false) {
            $host = 'mongodb://'.$host;
        }

        if (!empty($username) && !empty($password)) {
            $Client = new \MongoDB\Client($host, [
                "username" => $username,
                "password" => $password
            ]);
        } else {
            $Client = new \MongoDB\Client($host);
        }

        $CacheDriver = new QuiqqerMongoDriver([
            'mongo'      => $Client,
            'database'   => $database,
            'collection' => $collection
        ]);

        $CacheDriver->storeData(['db-test'], 1, false);
        $result = $CacheDriver->getData(['db-test']);

        if ($result['data'] === 1) {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get('quiqqer/quiqqer', 'message.quiqqer.mongo.success')
            );
        } else {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get('quiqqer/quiqqer', 'message.quiqqer.mongo.error')
            );
        }
    },
    ['host', 'database', 'collection', 'username', 'password'],
    'Permission::checkSU'
);
