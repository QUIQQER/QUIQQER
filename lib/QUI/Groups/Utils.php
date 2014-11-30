<?php

/**
 * This file contains QUI\Groups\Utils
 */

namespace QUI\Groups;

use QUI;
use QUI\Utils\DOM;
use QUI\Utils\XML;

/**
 * Helper for groups
 *
 * @author Henning Leutz (PCSG)
 * @package com.pcsg.qui.groups
 */

class Utils
{
    /**
     * JavaScript Buttons / Tabs for a group
     *
     * @param \QUI\Groups\Group $Group
     * @return \QUI\Controls\Toolbar\Bar
     */
    static function getGroupToolbar($Group)
    {
        $Tabbar = new QUI\Controls\Toolbar\Bar(array(
            'name'  => 'UserToolbar'
        ));

        DOM::addTabsToToolbar(
            XML::getTabsFromXml( SYS_DIR .'groups.xml' ),
            $Tabbar,
            'pcsg'
        );

        // @todo load plugin / project / package extensions


        // Projekt Tabs
        /*
        $projects = \QUI\Projects\Manager::getProjects( true );

        foreach ( $projects as $Project )
        {
            $rights = \QUI::getRights()->getProjectRights( $Project );

            if ( empty( $rights ) ) {
                continue;
            }

            $Tabbar->appendChild(
                new Controls_Toolbar_Tab(array(
                    'name'    => 'project_'. $Project->getAttribute('name'),
                    'project' => $Project->getAttribute('name'),
                    'text'    => $Project->getAttribute('name') .' Rechte'
                ))
            );
        }
        */
        return $Tabbar;
    }

    /**
     * Tab contents of a group Tab / Button
     *
     * @param Integer $gid - Group ID
     * @param String $plugin - Plugin
     * @param String $tab - Tabname
     *
     * @return String
     */
    static function getTab($gid, $plugin, $tab)
    {
        $Groups = QUI::getGroups();
        $Group  = $Groups->get( $gid );
        $Engine = QUI::getTemplateManager()->getEngine( true );

        $Engine->assign(array(
            'Group' => $Group
        ));

        // System
        if ( $plugin === 'pcsg' )
        {
            return DOM::getTabHTML(
                $tab, SYS_DIR .'groups.xml'
            );
        }

        /*
        if ( strpos($tab, 'project_') !== false )
        {
            $tab     = explode( 'project_', $tab );
            $Project = \QUI\Projects\Manager::getProject( $tab[1] );
            $file    = USR_DIR .'lib/'. $Project->getAttribute('name') .'/rights.xml';

            $Engine = \QUI::getTemplateManager()->getEngine( true );
            $rights = QUI_Rights_Parser::getGroups(
                QUI_Rights_Parser::parse( $file )
            );

            $Engine->assign(array(
                'rights' => $rights
            ));

            return $Engine->fetch( SYS_DIR .'template/groups/extend.html' );
        }
        */

        return '';
    }
}
