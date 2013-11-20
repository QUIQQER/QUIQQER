<?php

/**
 * Gruppen suchen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 */
function ajax_groups_search($params)
{
    $Groups = \QUI::getGroups();
    $params = json_decode( $params, true );
    $page   = 1;
	$limit  = 10;

	$params['start'] = 0;

	if ( isset( $params['limit'] ) ) {
		$limit = $params['limit'];
	}

	if ( isset( $params['page'] ) )
	{
		$page = (int)$params['page'];
		$params['start'] = ($page-1) * $limit;
	}

	$search = $Groups->search( $params );

	return array(
		'total' => $Groups->count( $params ),
		'page'  => $page,
		'data'  => $search
	);
}
QUI::$Ajax->register( 'ajax_groups_search', array('params'), 'Permission::checkAdminUser' );

?>