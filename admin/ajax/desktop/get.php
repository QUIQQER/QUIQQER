<?php

/**
 * Return the desktop list from the session user
 *
 * @param array
 * @deprecated
 */
function ajax_desktop_get()
{
    $list   = \QUI::getDesktopManager()->getDesktopsFromUser();
    $result = array();

    foreach ( $list as $Desktop ) {
        $result[] = $Desktop->getAttributes();
    }

    return $result;
}

\QUI::$Ajax->register(
    'ajax_desktop_get',
    false,
    'Permission::checkUser'
);
