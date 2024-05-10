<?php

/**
 * QUIQQER Download Manager
 * It's only a test, here you can get the main README File ;-)
 *
 * @throws \QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_downloadTest',
    static function (): void {
        sleep(2);
        QUI\Utils\System\File::downloadHeader(OPT_DIR . 'quiqqer/core/README.md');
    },
    false,
    'Permission::checkAdminUser'
);
