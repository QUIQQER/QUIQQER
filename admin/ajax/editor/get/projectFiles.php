<?php

/**
 * Konfiguration bekommen welche zur Verfügung stehen
 *
 * @param string $project - project data
 * @return Array
 * @todo move logic to wysiwyg class
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
        $Project = QUI::getProject( $project );

    } catch ( QUI\Exception $Exception )
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
        $files = QUI\Utils\XML::getWysiwygCSSFromXml( $file );

        foreach ( $files as $cssfile ) {
            $css[] = URL_USR_DIR . $project .'/'. $cssfile;
        }

        // id and css class
        $Dom  = QUI\Utils\XML::getDomFromXml( $file );
        $Path = new \DOMXPath( $Dom );

        $WYSIWYG = $Path->query( "//wysiwyg" );

        if ( $WYSIWYG->length )
        {
            $bodyId    = $WYSIWYG->item( 0 )->getAttribute( 'id' );
            $bodyClass = $WYSIWYG->item( 0 )->getAttribute( 'class' );
        }
    }

    // template files
    $templates = array();

    if ( $Project->getAttribute( 'template' ) ) {
        $templates[] = OPT_DIR . $Project->getAttribute('template') .'/settings.xml';
    }

    // project vhosts
    $VHosts       = new QUI\System\VhostManager();
    $projectHosts = $VHosts->getHostsByProject( $Project->getName() );

    foreach ( $projectHosts as $host )
    {
        $data = $VHosts->getVhost( $host );

        if ( !isset( $data['template'] )) {
            continue;
        }

        if ( empty( $data['template'] ) ) {
            continue;
        }

        $file = OPT_DIR . $data['template'] .'/settings.xml';

        if ( file_exists( $file ) ) {
            $templates[] = $file;
        }
    }

    $templates = array_unique( $templates );


    foreach ( $templates as $file )
    {
        if ( !file_exists( $file ) ) {
            continue;
        }

        if ( empty( $css ) )
        {
            $cssFiles = QUI\Utils\XML::getWysiwygCSSFromXml( $file );

            foreach ( $cssFiles as $cssFile )
            {
                // external file
                if ( strpos( $cssFile, '//' ) === 0 ||
                     strpos( $cssFile, 'https://' ) === 0 ||
                     strpos( $cssFile, 'http://' ) === 0 )
                {
                    $css[] = $cssFile;
                    continue;
                }

                $css[] = \QUI\Utils\DOM::parseVar( $cssFile );
            }
        }

        // id and css class
        if ( !$bodyId && !$bodyClass )
        {
            $Dom  = QUI\Utils\XML::getDomFromXml( $file );
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

QUI::$Ajax->register(
    'ajax_editor_get_projectFiles',
    array('project'),
    'Permission::checkSU'
);
