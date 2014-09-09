<?php

/**
 * User profile template
 *
 * @return String
 */
function ajax_user_profileTemplate()
{
    $Engine = \QUI::getTemplateManager()->getEngine( true );

    $Engine->assign(array(
        'QUI' => new QUI()
    ));

    return $Engine->fetch( SYS_DIR .'template/users/profile.html' );
}

\QUI::$Ajax->register( 'ajax_user_profileTemplate' );
