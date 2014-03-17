<?php

/**
 * Kinder einer Seite bekommen
 *
 * @param Integer $id
 * @param String $lang
 * @param String $project_name
 *
 * @return Array
 */
function ajax_site_getchildren($project, $lang, $id, $params)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );
    $params  = json_decode( $params, true );

    $PackageManager = \QUI::getPackageManager();
    $PluginManager  = \QUI::getPluginManager();

    $attributes = false;

    if ( isset( $params['attributes'] ) ) {
        $attributes = explode( ',', $params['attributes'] );
    }

    // forerst kein limit
    if ( isset( $params['limit'] ) )
    {
        $children = $Site->getChildren(array(
            'limit' => $params['limit']
        ));

    } else
    {
        $children = $Site->getChildren();
    }

    $childs = array();

    for ( $i = 0, $len = count( $children ); $i < $len; $i++ )
    {
        $Child = $children[ $i ]; /* @var $Child \QUI\Projects\Site\Edit */

        if ( !$attributes )
        {
            $childs[ $i ] = $Child->getAttributes();

        } else
        {
            foreach ( $attributes as $attribute ) {
                $childs[ $i ][ $attribute ] = $Child->getAttribute( $attribute );
            }
        }

        $childs[ $i ][ 'id' ] = $Child->getId();

        if ( !$attributes || in_array( 'has_children', $attributes ) ) {
            $childs[ $i ]['has_children'] = $Child->hasChildren( true );
        }

        if ( !$attributes || in_array( 'config', $attributes ) ) {
            $childs[ $i ]['config'] = $Child->conf; // old??
        }

        if ( $Child->isLinked() && $Child->isLinked() != $Site->getId() ) {
            $childs[ $i ]['linked'] = 1;
        }

        // Projekt Objekt muss da nicht mit
        if ( isset( $childs[ $i ]['project'] ) && is_object( $childs[ $i ]['project'] ) ) {
            unset( $childs[ $i ]['project'] );
        }

        // icon
        if ( !$attributes || in_array( 'icon', $attributes ) )
        {
            if ( $Site->getAttribute('type') != 'standard' )
            {
                $childs[ $i ]['icon'] = $PluginManager->getIconByType(
                    $Site->getAttribute('type')
                );
            }


        }
    }

    return $childs;
}

QUI::$Ajax->register(
    'ajax_site_getchildren',
    array('project', 'lang', 'id', 'params'),
    'Permission::checkAdminUser'
);
