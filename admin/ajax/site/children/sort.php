<?php

/**
 * Erzeugt ein kind
 *
 * @param String $project	- Project name
 * @param String $lang 		- Project lang
 * @param Integer $id 		- Parent ID
 * @param JSON Array $attributes - child attributes
 */
function ajax_site_children_sort($project, $lang, $ids, $from)
{
    $Project = \QUI::getProject( $project, $lang );
    $ids     = json_decode( $ids, true );

    $from = (int)$from;

    foreach ( $ids as $id )
    {
        $from  = $from + 1;
        $Child = $Project->get( $id );

        $Child->setAttribute( 'order_field', $from );
        $Child->save();
    }
}

\QUI::$Ajax->register(
    'ajax_site_children_sort',
    array('project', 'lang', 'ids', 'from')
);
