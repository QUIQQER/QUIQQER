<?php

/**
 * User logout
 */
function ajax_user_logout()
{
    \QUI::getUserBySession()->logout();
}

\QUI::$Ajax->register( 'ajax_user_logout' );
