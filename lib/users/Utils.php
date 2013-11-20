<?php

/**
 * This file contains Users_Utils
 */

/**
 * Helper for users
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.users
 */

class Users_Utils
{
    /**
     * JavaScript Buttons / Tabs from a user
     *
     * @param Users_User $User
     * @return Controls_Toolbar_Bar
     */
    static function getUserToolbar($User)
    {
        $Tabbar = new Controls_Toolbar_Bar(array(
            'name'  => 'UserToolbar'
        ));

        \QUI\Utils\DOM::addTabsToToolbar(
            \QUI\Utils\XML::getTabsFromXml( LIB_DIR .'xml/user.xml' ),
            $Tabbar,
            'pcsg'
        );

        if ( !$User->getId() ) {
            return $Tabbar;
        }

        /**
         * user extention from plugins
         */

        $Plugin  = \QUI::getPlugins();
        $plugins = $Plugin->get();

        // user.xml auslesen
        foreach ( $plugins as $Plugin ) {
            $Plugin->loadUserTabs( $Tabbar, $User );
        }

        /**
         * user extention from projects
         */
        $projects = Projects_Manager::getProjects();

        foreach ( $projects as $project )
        {
            \QUI\Utils\DOM::addTabsToToolbar(
                \QUI\Utils\XML::getTabsFromXml( USR_DIR .'lib/'. $project .'/user.xml' ),
                $Tabbar,
                'project.'. $project
            );
        }

        return $Tabbar;
    }

    /**
     * Tab contents of a user Tabs / Buttons
     *
     * @param Integer $uid
     * @param String $plugin
     * @param String $tab
     *
     * @return String
     */
    static function getTab($uid, $plugin, $tab)
    {
        $Users = \QUI::getUsers();
        $User  = $Users->get( (int)$uid );

        // System
        if ( $plugin === 'pcsg' )
        {
            return \QUI\Utils\DOM::getTabHTML(
                $tab,
                LIB_DIR .'xml/user.xml'
            );
        }

        // project extention
        if ( strpos($plugin, 'project.') !== false )
        {
            $project = explode( 'project.', $plugin );

            return \QUI\Utils\DOM::getTabHTML(
                $tab,
                \QUI::getProject( $project[1] )
            );
        }

           // Plugin extention
        $Plugins = \QUI::getPlugins();
        $Plugin  = $Plugins->get( $plugin );

        return \QUI\Utils\DOM::getTabHTML( $tab, $Plugin );
    }
}
