<?php

/**
 * Tabs bekommen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return Array
 */
function ajax_project_panel_categories_get($project)
{
    $Project = \QUI::getProject( $project );

    $buttonList = array();
    $templates  = array();

    $templates[ $Project->getAttribute('template') ] = true;

    // vhosts und templates schauen
    $vhosts = \QUI::getRewrite()->getVHosts();

    foreach ( $vhosts as $vhost )
    {
        if ( !isset( $vhost[ 'project' ] ) ) {
            continue;
        }

        if ( $vhost[ 'project' ] == $project ) {
            $templates[ $vhost[ 'template' ] ] = true;
        }
    }

    // read template config
    foreach ( $templates as $template => $b )
    {
        $file = OPT_DIR . $template .'/settings.xml';

        if ( !file_exists( $file ) ) {
            continue;
        }

        $windows = \QUI\Utils\XML::getSettingWindowsFromXml( $file );

        foreach ( $windows as $Window )
        {
            $buttons = \QUI\Utils\DOM::getButtonsFromWindow( $Window );

            foreach ( $buttons as $Button )
            {
                $Button->setAttribute( 'file', $file );

                $buttonList[] = $Button->toArray();
            }
        }
    }

    return $buttonList;
}

\QUI::$Ajax->register(
    'ajax_project_panel_categories_get',
    array( 'project', 'lang' ),
    'Permission::checkAdminUser'
);
