<?php

/**
 * Saves the address
 *
 * @param String $uid - User ID
 * @param String $aid - Address ID
 * @param String JSON Array $data
 */
function ajax_users_address_save($uid, $aid, $data)
{
    $data = json_decode($data, true);
    $User = \QUI::getUsers()->get((int)$uid);

    try
    {
        $Address = $User->getAddress( (int)$aid );

    } catch ( \QUI\Exception $Exception )
    {
        $Address = $User->addAddress( $data );
    }

    $Address->clearMail();
    $Address->clearPhone();

    if ( isset( $data['mails'] ) && is_array( $data['mails'] ) )
    {
        foreach ( $data['mails'] as $mail ) {
            $Address->addMail( $mail );
        }
    }

    if ( isset( $data['phone'] ) && is_array( $data['phone'] ) )
    {
        foreach ( $data['phone'] as $phone ) {
            $Address->addPhone( $phone );
        }
    }

    unset( $data['mails'] );
    unset( $data['phone'] );

    $Address->setAttributes( $data );
    $Address->save();

    return $Address->getId();
}

\QUI::$Ajax->register(
    'ajax_users_address_save',
    array('uid', 'aid', 'data'),
    'Permission::checkSU'
);
