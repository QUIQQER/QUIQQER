<?php

/**
 * Test memcached serves
 *
 * @param String $data - JSON data
 */
function ajax_settings_memcachedTest($data)
{
    $data = json_decode($data, true);

    if (!class_exists('Memcached')) {
        QUI::getMessagesHandler()->addError(
            QUI::getLocale(
                'quiqqer/system',
                'message.session.auth.memcached.notinstalled'
            )
        );

        return;
    }

    if (!count($data)) {
        QUI::getMessagesHandler()->addError(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.session.auth.memcached.missing.servers'
            )
        );

        return;
    }

    $errors = 0;

    foreach ($data as $entry) {

        if (!isset($entry['server'])) {
            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'message.session.auth.memcached.empty.server'
                )
            );

            $errors++;
            continue;
        }

        if (!isset($entry['port'])) {
            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'message.session.auth.memcached.empty.port'
                )
            );

            $errors++;
            continue;
        }

        $server = $entry['server'];
        $port = $entry['port'];

        $Memcached = new Memcached();
        $Memcached->addServer($server, $port);

        $status = $Memcached->getStats();

        if (!isset($status[$server.":".$port])
            || $status[$server.":".$port]['pid'] <= 0
        ) {
            $errors++;

            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'message.session.auth.memcached.error',
                    array(
                        'server' => $server,
                        'port'   => $port
                    )
                )
            );
        }
    }

    if (!$errors) {
        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.session.auth.memcached.success'
            )
        );
    }
}

QUI::$Ajax->register(
    'ajax_settings_memcachedTest',
    array('data'),
    'Permission::checkAdminUser'
);
