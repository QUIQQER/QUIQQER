<?php

/**
 * Alle Seitentypen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return Array
 */
function ajax_project_types_get_list($project)
{
    if ( empty( $project ) ) {
        $Project = Projects_Manager::getStandard();
    }

    $Plugins = \QUI::getPlugins();
	$types   = $Plugins->getAvailableTypes();
	$result  = array();

	foreach ( $types as $type )
	{
	    $type   = explode('/', $type);
        $Plugin = $Plugins->get( $type[0] );

	    if ( !$Plugin->getAttribute( 'name' ) ) {
	        continue;
	    }

	    $config = $Plugin->getAttribute( 'config' );
	    $types  = $Plugin->getAttribute( 'types' );

        if ( !isset( $result[ $Plugin->getAttribute( 'name' ) ] ) )
        {
            $result[ $Plugin->getAttribute( 'name' ) ] = $config;
            $result[ $Plugin->getAttribute( 'name' ) ]['types'] = array();
        }

        $types[ $type[1] ]['type'] = implode('/', $type);

        $result[ $Plugin->getAttribute( 'name' ) ]['types'][] = $types[ $type[1] ];
	}

    return $result;
}

QUI::$Ajax->register(
	'ajax_project_types_get_list',
    array( 'project' ),
    'Permission::checkAdminUser'
);

?>