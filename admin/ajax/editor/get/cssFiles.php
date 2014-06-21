<?php

/**
 * Konfiguration bekommen welche zur Verfügung stehen
 *
 * @return Array
 */
function ajax_editor_get_cssFiles($project)
{
    $css = array();

    try
    {
        $Project = \QUI::getProject( $project );
        $file    = USR_DIR . $Project->getName() .'/settings.xml';

        if ( !file_exists( $file ) ) {
            return $css;
        }

        $files = \QUI\Utils\XML::getWysiwygCSSFromXml( $file );

        foreach ( $files as $file ) {
            $css[] = URL_USR_DIR . $project .'/'. $file;
        }

    } catch ( \QUI\Exception $Exception )
    {

    }

    return $css;
}

\QUI::$Ajax->register(
    'ajax_editor_get_cssFiles',
    array('project'),
    'Permission::checkSU'
);
