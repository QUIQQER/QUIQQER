<?php

exit;

/**
 * @deprecated
 */

// Benutzerrechte Prüfung
if (!$User->getId()) {
    exit;
}

if ($User->isAdmin() == false) {
    exit;
}

/**
 * Speichert ein Benutzer Setting
 *
 * @param unknown_type $uid
 * @param unknown_type $name
 * @param unknown_type $value
 * @return unknown
 */
function ajax_user_setsetting($uid, $name, $value)
{
    $Users = \QUI::getUsers();
    $User  = $Users->get((int)$uid);
    $extra = $User->getAttribute('extra');

    if ($extra == false) {
        return false;
    }

    $extra = json_decode($extra, true);

    if (!is_array($extra)) {
        $extra = array();
    }

    $extra['settings'][$name] = $value;

    $User->setAttribute('extra', json_encode($extra));
    $User->save();

    return true;
}
$ajax->register('ajax_user_setsetting', array('uid', 'name', 'value'));

/**
 * Gibt eine Benutzereinstellung zurück
 *
 * @param unknown_type $uid
 * @param unknown_type $name
 * @return unknown
 */
function ajax_user_getsetting($uid, $name)
{
    $Users = \QUI::getUsers();
    $User  = $Users->get((int)$uid);
    $extra = $User->getAttribute('extra');

    if ($extra == false) {
        return false;
    }

    $extra = json_decode($extra, true);

    if (!isset($extra['settings']) ||
        !isset($extra['settings'][$name]))
    {
        return false;
    }

    return $extra['settings'][$name];
}
$ajax->register('ajax_user_getsetting', array('uid', 'name'));

// AD Import
/**
 * Gibt eine Benutzereinstellung zurück
 * @return false | user Count
 */
function ajax_user_importad_getusers($groupid, $username, $pass, $adgroup)
{
    $Users = \QUI::getUsers();
    $Auth  = new \QUI\AuthActiveDirectory();

    $userCount       = 0;
    $userUpdateCount = 0;

    $server = \QUI::conf('auth', 'server');
    $server = explode(';', $server);

    $Auth->setAttribute('dc', $server);
    $Auth->setAttribute('base_dn', \QUI::conf('auth', 'base_dn'));
    $Auth->setAttribute('domain', \QUI::conf('auth', 'domain'));
    $Auth->setAttribute('auth_user', $username);
    $Auth->setAttribute('auth_password', $pass);

    try
    {
        if (empty($adgroup))
        {
            $importUsers = $Auth->getUsers();
        } else
        {
            $importUsers = $Auth->getUsers($adgroup);
        }

        if ($importUsers)
        {
            $Auth->setAttribute('_sortUser', true);

            foreach ($importUsers as $user)
            {
                $userData = $Auth->getUser($user);

                if ($Users->existsUsername($user))
                {
                    // update User
                    $User = $Users->getUserByName($user);

                    if (!empty($userData['mail'])) {
                        $User->setAttribute('email', $userData['mail']);
                    }

                    if (!empty($userData['firstname'])){
                        $User->setAttribute('firstname', $userData['firstname']);
                    }

                    if (!empty($userData['lastname'])){
                        $User->setAttribute('lastname', $userData['lastname']);
                    }

                    if (!empty($userData['title'])){
                        $User->setAttribute('usertitle', $userData['title']);
                    }

                    $User->save();
                    $userUpdateCount++;
                } else
                {
                    // neuen User
                    $User = $Users->createChild();

                    $User->setAttribute('username', $user);
                    $User->setGroups($groupid);
                    $User->setPassword(\QUI\Utils\Security\Orthos::getPassword());

                    if (!empty($userData['mail'])) {
                        $User->setAttribute('email',$userData['mail']);
                    }

                    if (!empty($userData['firstname'])) {
                        $User->setAttribute('firstname',$userData['firstname']);
                    }

                    if (!empty($userData['lastname'])) {
                        $User->setAttribute('lastname',$userData['lastname']);
                    }

                    if (!empty($userData['title'])) {
                        $User->setAttribute('usertitle',$userData['title']);
                    }

                    $User->save();
                    $User->activate();
                    $userCount++;
                }
            }

            return array(
                'newUser'   => $userCount,
                'updateUser'=> $userUpdateCount
            );
        }

    } catch (\QUI\Exception $e)
    {
        \QUI\System\Log::writeException($e);
        return false;
    }
}
$ajax->register('ajax_user_importad_getusers', array('groupid', 'username', 'pass', 'adgroup'));

?>