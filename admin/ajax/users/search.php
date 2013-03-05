<?php

/**
 * Seite suchen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 */
function ajax_users_search($params)
{
    $params = json_decode( $params, true );

	$Groups = QUI::getGroups();
	$Users  = QUI::getUsers();
	$page   = 1;
	$limit  = 10;

	$params['start'] = 0;

	if ( isset( $params['limit'] ) ) {
		$limit = $params['limit'];
	}

	if ( isset( $params['field'] ) &&
	     $params['field'] == 'activebtn' )
    {
		$params['field'] = 'active';
	}

	if ( isset( $params['page'] ) )
	{
		$page = (int)$params['page'];

		$params['start'] = ($page-1)*$limit;
	}

	// System_Log::writeRecursive($params, 'error');

	$search = $Users->search( $params );
	$result = array();

	foreach ( $search as $user )
	{
	    $image  = URL_BIN_DIR .'16x16/cancel.png';
		$title  = 'Benutzer aktivieren';
		$status = 0;

		if ( !isset( $user['usergroup'] ) )
		{
			$result[] = $user;
			continue;
		}

		$usergroups = explode( ',', trim($user['usergroup'], ',' ) );
		$groupnames = '';

		foreach ( $usergroups as $gid )
		{
			if ( !$gid ) {
				continue;
			}

			try
			{
			    $groupnames .= $Groups->getGroupNameById( $gid ) .',';
			} catch (QException $e)
			{
                $groupnames .= $gid .',';
			}
		}

		$user['usergroup'] = trim( $groupnames, ',' );

		if ( $user['regdate'] != 0 ) {
			$user['regdate']  = date( 'd.m.Y H:i:s', $user['regdate'] );
		}

		$result[] = $user;
	}

	return array(
		'total' => $Users->count( $params ),
		'page'  => $page,
		'data'  => $result
	);
}
QUI::$Ajax->register('ajax_users_search', array('params'), 'Permission::checkAdminUser');

?>