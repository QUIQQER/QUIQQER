<?php

/**
 * Baut das obere Menü auf
 *
 * @return Array
 */
function ajax_menu()
{
    $User = \QUI::getUserBySession();
    $Menu = new \QUI\Controls\Contextmenu\Bar(array(
        'name'   => 'menu',
        'parent' => 'menubar',
        'id'	 => 'menu'
    ));

    \QUI\Utils\XML::addXMLFileToMenu( $Menu, SYS_DIR .'menu.xml' );

    // projects settings
    $projects = \QUI\Projects\Manager::getProjects();
    $Settings = $Menu->getElementByName( 'settings' );
    $Projects = $Settings->getElementByName( 'projects' );

    foreach ( $projects as $project )
    {
        if ( !$Projects ) {
            continue;
        }

        $Projects->appendChild(
            new \QUI\Controls\Contextmenu\Menuitem(array(
                'text'    => $project,
                'icon'    => 'icon-home',
                'onclick' => '',
                'require' => 'admin/projects/Settings',
                'onMouseDown' => 'QUI.Menu.click',
                'project' => $project
            ))
        );
    }

    // read the settings.xmls
    $dir      = SYS_DIR .'settings/';
    $files    = \QUI\Utils\System\File::readDir( $dir );
    $Settings = $Menu->getElementByName( 'settings' );

    foreach ( $files as $file )
    {
        $windows = \QUI\Utils\XML::getSettingWindowsFromXml( $dir . $file );

        foreach ( $windows as $Window )
        {
            $Win = new \QUI\Controls\Contextmenu\Menuitem(array(
                'onclick' => ''
            ));

            $Win->setAttribute( 'name', '/settings/'. $Window->getAttribute( 'name' ) .'/' );
            $Win->setAttribute( 'onMouseDown', 'QUI.Menu.click' );
            $Win->setAttribute( 'qui-window', true );
            $Win->setAttribute( 'qui-xml-file', 'settings/'. $file );

            // titel
            $titles = $Window->getElementsByTagName( 'title' );

            if ( $titles->item( 0 ) )
            {
                $Title = $titles->item( 0 );
                $text  = trim( $titles->item( 0 )->nodeValue );

                if ( $Title->getAttribute( 'group' ) && $Title->getAttribute( 'var' ) )
                {
                    $text = \QUI::getLocale()->get(
                        $Title->getAttribute( 'group' ),
                        $Title->getAttribute( 'var' )
                    );
                }

                $Win->setAttribute( 'text', $text );
            }

            $params = $Window->getElementsByTagName( 'params' );

            if ( $params->item( 0 ) )
            {
                $icon = $params->item( 0 )->getElementsByTagName( 'icon' );

                if ( $icon->item( 0 ) )
                {
                    $Win->setAttribute(
                        'icon',
                        \QUI\Utils\DOM::parseVar(
                            $icon->item( 0 )->nodeValue
                        )
                    );
                }
            }

            $Settings->appendChild( $Win );
        }
    }

    // read the menu.xmls
    $dir = VAR_DIR .'cache/menu/';

    if ( !is_dir( $dir ) ) {
        \QUI\Update::importAllMenuXMLs();
    }

    $files = \QUI\Utils\System\File::readDir( $dir );

    foreach ( $files as $file ) {
        \QUI\Utils\XML::addXMLFileToMenu( $Menu, $dir . $file );
    }

    return $Menu->toArray();

    /*


    return;


    $Settings->appendChild(
        new Controls_Contextmenu_Menuitem(array(
            'text'   => 'Wartungsarbeiten',
            'name'   => 'settings_maintenance',
            'image'  => URL_BIN_DIR .'16x16/configure.png',
            'needle' => 'lib/Maintenance',
            'click'  => 'QUI.lib.Maintenance.open'
        ))
    );

    // Menüpunkt für Plugineinstellungen
    $SettingsPlugins = new Controls_Contextmenu_Menuitem(array(
        'text'  => 'Plugins',
        'name'  => 'settings_plugins',
        'image' => URL_BIN_DIR .'16x16/plugins.png'
    ));
    $Settings->appendChild($SettingsPlugins);

    $SettingsPlugins->appendChild(
        new Controls_Contextmenu_Menuitem(array(
            'text'    => 'Dienste Verwaltung',
            'name'    => 'cron_manager',
            'image'   => URL_BIN_DIR .'16x16/tasks.png',
            'onclick' => defined('ADMIN2') ? '_pcsg.crons.open();' : '_pcsg.crons.open'
        ))
    );

    $SettingsPlugins->appendChild(
        new Controls_Contextmenu_Menuitem(array(
            'text'    => 'robot.txt',
            'name'    => 'robot_txt_manager',
            'image'   => URL_BIN_DIR .'16x16/robottxt.png',
            'onclick' => defined('ADMIN2') ? '_pcsg.crons.robottxt.open();': '_pcsg.crons.robottxt.open'
        ))
    );

    */
}

\QUI::$Ajax->register(
    'ajax_menu',
    false,
    'Permission::checkAdminUser'
);
