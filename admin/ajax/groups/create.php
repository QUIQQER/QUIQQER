<?php

/**
 * Create a new group
 *
 * @param String $groupname - Gruppennamen
 * @param Integer $pid - Gruppen-ID des Parents
 * @return Integer - the new group id
 */
function ajax_groups_create($groupname, $pid)
{
    $Groups = \QUI::getGroups();
    $Parent = $Groups->get( (int)$pid );
	$Group  = $Parent->createChild( $groupname );

	return $Group->getId();
}

QUI::$Ajax->register(
	'ajax_groups_create',
    array('groupname', 'pid'),
    'Permission::checkUser'
);

?>