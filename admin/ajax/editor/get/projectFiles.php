<?php

/**
 * Konfiguration bekommen welche zur Verfügung stehen
 *
 * @return Array
 */
function ajax_editor_get_projectFiles($project)
{
    $result = array(
        'cssFiles'  => array(),
        'bodyId'    => '',
        'bodyClass' => ''
    );

    try
    {
        $Project = \QUI::getProject( $project );

    } catch ( \QUI\Exception $Exception )
    {
        return $result;
    }


    // css files
    $css  = array();
    $file = USR_DIR . $Project->getName() .'/settings.xml';

    $bodyId    = false;
    $bodyClass = false;

    // project files
    if ( file_exists( $file ) )
    {
        $files = \QUI\Utils\XML::getWysiwygCSSFromXml( $file );

        foreach ( $files as $cssfile ) {
            $css[] = URL_USR_DIR . $project .'/'. $cssfile;
        }

        // id and css class
        $Dom  = \QUI\Utils\XML::getDomFromXml( $file );
        $Path = new \DOMXPath( $Dom );

        $WYSIWYG = $Path->query( "//wysiwyg" );

        if ( $WYSIWYG->length )
        {
            $bodyId    = $WYSIWYG->item( 0 )->getAttribute( 'id' );
            $bodyClass = $WYSIWYG->item( 0 )->getAttribute( 'class' );
        }
    }

    // template files
    $file = OPT_DIR . $Project->getAttribute('template') .'/settings.xml';

    if ( file_exists( $file ) )
    {
        if ( empty( $css ) )
        {
            $files = \QUI\Utils\XML::getWysiwygCSSFromXml( $file );

            foreach ( $files as $cssfile ) {
                $css[] = URL_OPT_DIR . $Project->getAttribute('template') .'/'. $cssfile;
            }
        }

        // id and css class
        if ( !$bodyId && !$bodyClass )
        {
            $Dom  = \QUI\Utils\XML::getDomFromXml( $file );
            $Path = new \DOMXPath( $Dom );

            $WYSIWYG = $Path->query( "//wysiwyg" );

            if ( $WYSIWYG->length )
            {
                $bodyId    = $WYSIWYG->item( 0 )->getAttribute( 'id' );
                $bodyClass = $WYSIWYG->item( 0 )->getAttribute( 'class' );
            }
        }
    }


    $result = array(
        'cssFiles'  => $css,
        'bodyId'    => $bodyId,
        'bodyClass' => $bodyClass
    );


    return $result;
}

\QUI::$Ajax->register(
    'ajax_editor_get_projectFiles',
    array('project'),
    'Permission::checkSU'
);
