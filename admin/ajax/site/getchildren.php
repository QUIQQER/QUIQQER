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
function ajax_site_getchildren($project, $lang, $id, $select, $start)
{
    $Project = QUI::getProject( $project, $lang );
    $Site    = new Projects_Site_Edit( $Project, (int)$id );

    if ( !empty( $select ) ) {
        $select = explode( ',', trim($select, ',') );
    }

    if ( !is_array( $select ) ) {
        $select = array();
    }

    if ( isset( $start ) )
    {
        $max = $Project->getConfig( 'sheets' );

        if ( $max == false ) {
            $max = 10;
        }

        $s = 0;

        if ( (int)$start ) {
            $s = $start;
        }

        $children = $Site->getChildren(array(
            'limit' => $s .','. $max
        ));

    } else
    {
        $children = $Site->getChildren();
    }

    $childs = array();

    for ( $i = 0, $len = count( $children ); $i < $len; $i++ )
    {
        $Child = $children[ $i ]; /* @var $Child Projects_Site_Edit */

        if ( empty( $select ) )
        {
            $childs[ $i ] = $Child->getAllAttributes();
        } else
        {
            /* @todo BEAH Lösung Select muss in Projects_Site_Edit verlagert werden */
            foreach ( $select as $att ) {
                $childs[ $i ][ $att ] = $Child->getAttribute( $att );
            }
        }

        $childs[$i]['id'] = $Child->getId();

        if ( empty( $select ) || in_array( 'has_children', $select ) ) {
            $childs[ $i ]['has_children'] = $Child->hasChildren( true );
        }

        if ( empty( $select ) || in_array( 'config', $select ) ) {
            $childs[ $i ]['config'] = $Child->conf;
        }

        if ( $Child->isLinked() && $Child->isLinked() != $Site->getId() ) {
            $childs[ $i ]['linked'] = 1;
        }

        // Projekt Objekt muss da nicht mit
        if ( isset( $childs[ $i ]['project'] ) && is_object( $childs[ $i ]['project'] ) ) {
            unset($childs[ $i ]['project']);
        }
    }

    return $childs;
}

QUI::$Ajax->register(
    'ajax_site_getchildren',
    array('project', 'lang', 'id', 'select', 'start'),
    'Permission::checkAdminUser'
);

?>