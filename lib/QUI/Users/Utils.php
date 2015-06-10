<?php

/**
 * This file contains \QUI\Users\Utils
 */

namespace QUI\Users;

use QUI;
use QUI\Utils\DOM;
use QUI\Utils\XML;


/**
 * Helper for users
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package com.pcsg.qui.users
 */
class Utils
{
    /**
     * JavaScript Buttons / Tabs from a user
     *
     * @param \QUI\Users\User $User
     *
     * @return \QUI\Controls\Toolbar\Bar
     */
    static function getUserToolbar($User)
    {
        $Tabbar = new QUI\Controls\Toolbar\Bar(array(
            'name' => 'UserToolbar'
        ));

        DOM::addTabsToToolbar(
            XML::getTabsFromXml(LIB_DIR.'xml/user.xml'),
            $Tabbar,
            'pcsg'
        );

        if (!$User->getId()) {
            return $Tabbar;
        }

        /**
         * user extention from plugins
         */
        $list = QUI::getPackageManager()->getInstalled();

        foreach ($list as $entry) {
            $userXml = OPT_DIR.$entry['name'].'/user.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            DOM::addTabsToToolbar(
                XML::getTabsFromXml($userXml),
                $Tabbar,
                'plugin.'.$entry['name']
            );
        }

        /*
        $Plugin  = \QUI::getPlugins();
        $plugins = $Plugin->get();

        // user.xml auslesen
        foreach ( $plugins as $Plugin ) {
            $Plugin->loadUserTabs( $Tabbar, $User );
        }
        */

        /**
         * user extention from projects
         */
        $projects = QUI\Projects\Manager::getProjects();

        foreach ($projects as $project) {
            DOM::addTabsToToolbar(
                XML::getTabsFromXml(USR_DIR.'lib/'.$project.'/user.xml'),
                $Tabbar,
                'project.'.$project
            );
        }

        return $Tabbar;
    }

    /**
     * Tab contents of a user Tabs / Buttons
     *
     * @param Integer $uid
     * @param String  $plugin
     * @param String  $tab
     *
     * @return String
     */
    static function getTab($uid, $plugin, $tab)
    {
        $Users = QUI::getUsers();
        $User = $Users->get((int)$uid);

        // assign user as global var
        QUI::getTemplateManager()->assignGlobalParam('User', $User);

        // System
        if ($plugin === 'pcsg') {
            return DOM::getTabHTML(
                $tab,
                LIB_DIR.'xml/user.xml'
            );
        }

        // project extention
        if (strpos($plugin, 'project.') !== false) {
            $project = explode('project.', $plugin);

            return DOM::getTabHTML(
                $tab,
                QUI::getProject($project[1])
            );
        }

        // Plugin extention
        $plugin = str_replace('plugin.', '', $plugin);
        $package = QUI::getPackageManager()->getPackage($plugin);

        if (!$package || !isset($package['name'])) {
            return '';
        }

        return DOM::getTabHTML(
            $tab,
            OPT_DIR.$package['name'].'/user.xml'
        );
    }
}
