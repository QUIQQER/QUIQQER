<?php

/**
 * Systemcheck
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_check',
    function () {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Engine->assign([
            'checks' => QUI\System\Checks\Manager::standard()
        ]);

        return $Engine->fetch(SYS_DIR . 'ajax/system/check.html');
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
