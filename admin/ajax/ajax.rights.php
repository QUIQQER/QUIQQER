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
 * GROUPS
 */

/**
 * Enter description here...
 *
 * @param Integer $id
 * @return String
 */
function ajax_rights_group_settings($id)
{
    return QUI::getGroups()->get($id)->getJSButtons();
}
$ajax->register('ajax_rights_group_settings', array('id'));

/**
 * Die Toolbar einer Gruppe bekommen
 *
 * @return Array
 */
/*
function ajax_rights_group_tabs()
{
    $Groups   = QUI::getGroups();
    $Toolbar  = $Groups->getToolbar();
    $children = $Toolbar->getChildren();

    $result = array();

    foreach ($children as $Child) {
        $result[] = $Child->jsObject();
    }

    return $result;
}
$ajax->register('ajax_rights_group_tabs');
*/
/**
 * Enter description here...
 *
 * @param Integer $id
 * @return String
 */
function ajax_rights_group_template($id)
{
    $Groups = QUI::getGroups();
    $Group  = $Groups->get($id);
    $Smarty = QUI_Template::getEngine(true);

    $Smarty->assign(array(
        'Group'    => $Group,
        'toolbars' => QUI_Wysiwyg::getToolbars()
    ));

    return $Smarty->fetch(SYS_DIR .'template/group_settings.html');
}
$ajax->register('ajax_rights_group_template', array('id'));

/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @return unknown
 */
function ajax_rights_group_save($id, $attributes)
{
    $Groups = QUI::getGroups();
    $Group  = $Groups->get($id);

    $rights = json_decode($attributes, true);

    // attribute
    $attr = array(
        "name", "admin", "parent",
        "active", "hasChildren", "toolbar"
    );

    foreach ($attr as $a)
    {
        if (isset($rights[$a])) {
            $Group->setAttribute($a, $rights[$a]);
        }
    }

    $Group->setRights( $rights );

    return $Group->save();
}
QUI::$Ajax->register('ajax_rights_group_save', array('id', 'attributes'), 'Permission::checkSU');

/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @param unknown_type $name
 */
function ajax_rights_group_create_child($id, $value)
{
    $Groups = QUI::getGroups();
    $Group  = $Groups->get($id);
    $Group->createChild($value);
}
$ajax->register('ajax_rights_group_create_child', array('id', 'value'));


/**
 * USERS
 */

/**
 * Enter description here...
 *
 */
function ajax_rights_users_settings()
{
    return QUI::getUsers()->getJSButtons();
}
$ajax->register('ajax_rights_users_settings');

/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @param unknown_type $data
 * @return unknown
 */
function ajax_rights_users_set_settings($id, $data)
{
    $Users = QUI::getUsers();
    $User  = $Users->get($id); /* @var $User User */
    $data  = json_decode($data, true);

    if (is_array($data)) {
        $data = Utils_Security_Orthos::clearArray($data);
    }

    if (isset($data['password']))
    {
        $User->setPassword($data['password']);
        unset($data['password']);
    }

    if (isset($data['usergroup']))
    {
        $User->setGroups($data['usergroup']);
        unset($data['usergroup']);
    }

    // avatar wird beim hochladen gesetzt und gespeichert, würde sonnst wieder überschrieben werden
    if (isset($data['avatar'])) {
        unset($data['avatar']);
    }


    foreach ($data as $key => $value) {
        $User->setAttribute($key, $value);
    }

    if ($User->getAttribute('phone')) {
        $phones = json_decode($User->getAttribute('phone'), true);
    }

    if ($User->getAttribute('mails'))
    {
        $mails = json_decode($User->getAttribute('mails'), true);

        foreach ($mails as $mail) {
            $User->addAdress($mail);
        }
    }

    if (isset($data['wysiwyg-toolbar'])) {
        $User->setExtra('wysiwyg-toolbar', $data['wysiwyg-toolbar']);
    };

    $User->save();

    if (isset($data['active']))
    {
        if ($data['active'] == 1)
        {
            $User->activate();
        } else
        {
            $User->deactivate();
        }

        unset($data['active']);
    }

    return true;
}
$ajax->register('ajax_rights_users_set_settings', array('id', 'data'));

/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @return unknown
 */
function ajax_rights_userpopup_template($id)
{
    $Users  = QUI::getUsers();
    $Groups = QUI::getGroups();
    $Smarty = QUI_Template::getEngine(true);
    $User   = $Users->get($id);

    try
    {
        $adresses = $User->getAdressList();
        $Smarty->assign('adresses', $adresses);

    } catch (QException $e)
    {

    }

    try
    {
        $Standard = $User->getStandardAdress();
        $Smarty->assign('Standard', $Standard);

    } catch (QException $e)
    {

    }

    $Smarty->assign(array(
        'User'     => $User,
        'Groups'   => $Groups,
        'countrys' => Utils_Countries_Manager::getList(),
        'toolbars' => QUI_Wysiwyg::getToolbars()
    ));

    $Smarty->assign('countrys', Utils_Countries_Manager::getList());

    return $Smarty->fetch(SYS_DIR .'template/user_popup.html');
}
$ajax->register('ajax_rights_userpopup_template', array('id'));

?>