<?php

/**
 * User profile template
 *
 * @return String
 */
QUI::$Ajax->registerFunction('ajax_user_profileTemplate', function () {
    $Engine = QUI::getTemplateManager()->getEngine(true);

    $Engine->assign(array(
        'QUI' => new QUI()
    ));

    return $Engine->fetch(SYS_DIR . 'template/users/profile.html');
});
