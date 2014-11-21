<?php

/**
 * This file includes ajax_menu
 */

use \QUI\Controls\Contextmenu\Bar;
use \QUI\Controls\Contextmenu\Baritem;
use \QUI\Controls\Contextmenu\Menuitem;

use \QUI\Utils\XML;

/**
 * Return the administration menu
 * @return Array
 */

function ajax_menu()
{
    $User  = \QUI::getUserBySession();
    $cache = 'qui/admin/menu/'. $User->getLang();

    try
    {
        return \QUI\Cache\Manager::get( $cache );

    } catch ( \QUI\Exception $Exception )
    {

    }


    \QUI::getLocale()->setCurrent(
        $User->getLocale()->getCurrent()
    );

    $Menu = new Bar(array(
        'name'   => 'menu',
        'parent' => 'menubar',
        'id'	 => 'menu'
    ));

    XML::addXMLFileToMenu( $Menu, SYS_DIR .'menu.xml' );

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
            new Menuitem(array(
                'text'    => $project,
                'icon'    => 'icon-home',
                'onclick' => '',
                'require' => 'controls/projects/project/Settings',
                'onClick' => 'QUI.Menu.menuClick',
                'project' => $project,
                'name'    => $project
            ))
        );
    }

    // read the settings.xmls
    $dir      = SYS_DIR .'settings/';
    $files    = \QUI\Utils\System\File::readDir( $dir );
    $Settings = $Menu->getElementByName( 'settings' );

    foreach ( $files as $key => $file ) {
        $files[ $key ] = $dir . $file;
    }

    // plugin settings
    $plugins = \QUI::getPackageManager()->getInstalled();

    foreach ( $plugins as $plugin )
    {
        $setting_file = OPT_DIR . $plugin['name'] .'/settings.xml';

        if ( file_exists( $setting_file ) ) {
            $files[] = $setting_file;
        }
    }


    // create the menu setting entries
    foreach ( $files as $file )
    {
        $windows = XML::getSettingWindowsFromXml( $file );

        if ( !$windows ) {
            continue;
        }

        foreach ( $windows as $Window )
        {
            $Win = new Menuitem();

            $menuParent = $Window->getAttribute( 'menu-parent' );

            $Win->setAttribute( 'name', '/settings/'. $Window->getAttribute( 'name' ) .'/' );
            $Win->setAttribute( 'onClick', 'QUI.Menu.menuClick' );
            $Win->setAttribute( 'qui-window', true );
            $Win->setAttribute( 'qui-xml-file', $file );

            // titel
            $titles = $Window->getElementsByTagName( 'title' );

            if ( $titles->item( 0 ) )
            {
                $Title = $titles->item( 0 );
                $text  = \QUI\Utils\DOM::getTextFromNode( $titles->item( 0 ) );

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

            if ( !$menuParent )
            {
                $Settings->appendChild( $Win );
                continue;
            }

            $Parent = $Menu->getElementByPath( $menuParent );

            if ( !$Parent )
            {
                $Settings->appendChild( $Win );
                continue;
            }

            $Parent->appendChild( $Win );
        }
    }

    // read the menu.xmls
    $dir = VAR_DIR .'cache/menu/';

    if ( !is_dir( $dir ) ) {
        \QUI\Update::importAllMenuXMLs();
    }

    $files = \QUI\Utils\System\File::readDir( $dir );

    foreach ( $files as $file ) {
        XML::addXMLFileToMenu( $Menu, $dir . $file );
    }

    \QUI\Cache\Manager::set( $cache , $Menu->toArray() );

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
