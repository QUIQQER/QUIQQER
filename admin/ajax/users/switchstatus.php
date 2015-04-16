<?php

/**
 * Switch the user status of the users
 *
 * @param String $uid - JSON Array | JSON Integer
 * @return Array
 */
function ajax_users_switchstatus($uid)
{
    $uid = json_decode( $uid, true );

    if ( !is_array($uid) ) {
        $uid = array( $uid );
    }

    $Users  = \QUI::getUsers();
    $result = array();

    foreach ( $uid as $_uid )
    {
        try
        {
            $User = $Users->get( $_uid );

            if ( $User->isActive() )
            {
                $User->deactivate();

                \QUI::getMessagesHandler()->addSuccess(
                    \QUI::getLocale()->get(
                        'quiqqer/system',
                        'message.user.deactivate'
                    )
                );

            } else
            {
                $User->activate();

                \QUI::getMessagesHandler()->addSuccess(
                    \QUI::getLocale()->get(
                        'quiqqer/system',
                        'message.user.activate'
                    )
                );
            }

            $result[ $_uid ] = $User->isActive() ? 1 : 0;

        } catch ( \QUI\Exception $Exception )
        {
            \QUI::getMessagesHandler()->addAttention(
                $Exception->getMessage()
            );

            continue;
        }
    }

    return $result;
}

\QUI::$Ajax->register(
    'ajax_users_switchstatus',
    array('uid'),
    'Permission::checkSU'
);
