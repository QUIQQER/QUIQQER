<?php

/**
 * Create a Desktop for the user
 *
 * @param String $title - Desktop title
 * @deprecated
 */
function ajax_desktop_widgets_content($name)
{
    $DesktopManager = \QUI::getDesktopManager();

    $list   = $DesktopManager->readWidgetsFiles();
    $Widget = false;

    foreach ( $list as $Node )
    {
        if ( $Node->getAttribute( 'name' ) != $name ) {
            continue;
        }

        $Widget = $DesktopManager->DOMToWidget( $Node );
    }

    if ( !$Widget ) {
        return '';
    }

    $content = $Widget->getAttribute( 'content' );

    if ( !isset( $content[ 'content' ] ) ) {
        return '';
    }

    return $content[ 'content' ];
}

\QUI::$Ajax->register(
    'ajax_desktop_widgets_content',
    array( 'name' ),
    'Permission::checkUser'
);
