<?php

/**
 * Gruppen Erweiterungen laden
 *
 * @param unknown_type $id
 * @param unknown_type $eproject
 */
function ajax_groups_extend($id, $eproject)
{
    $Group   = \QUI::getGroups()->get( $id );
    $Engine  = \QUI::getTemplateManager()->getEngine( true );
    $Project = \QUI::getProject( $eproject );

    $Rights = \QUI::getPermissionManager();
    $rights = $Rights->getProjectRightGroups( $Project );

    $Engine->assign(array(
        'Group'  => $Group,
        'rights' => $rights
    ));

    return $Engine->fetch(CMS_DIR .'admin/template/groups/extend.html');
}

\QUI::$Ajax->register( 'ajax_groups_extend', array('id', 'eproject') );
