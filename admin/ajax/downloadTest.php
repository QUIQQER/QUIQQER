<?php

/**
 * QUIQQER Download Manager
 * Its only a test, here you can get the main README File ;-)
 *
 * @throws \QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_downloadTest',
    function () {
        sleep(2);
        QUI\Utils\System\File::downloadHeader(OPT_DIR . 'quiqqer/quiqqer/README.md');
    },
    false,
    'Permission::checkAdminUser'
);
