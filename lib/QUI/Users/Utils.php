<?php

/**
 * This file contains \QUI\Users\Utils
 */

namespace QUI\Users;

use QUI;
use QUI\Utils\DOM;
use QUI\Utils\Text\XML;

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
    public static function getUserToolbar($User)
    {
        $TabBar = new QUI\Controls\Toolbar\Bar(array(
            'name' => 'UserToolbar'
        ));

        DOM::addTabsToToolbar(
            XML::getTabsFromXml(OPT_DIR . 'quiqqer/quiqqer/user.xml'),
            $TabBar,
            'quiqqer/quiqqer'
        );

        if (!$User->getId()) {
            return $TabBar;
        }

        /**
         * user extension from plugins
         */
        $list = QUI::getPackageManager()->getInstalled();

        foreach ($list as $entry) {
            if ($entry['name'] == 'quiqqer/quiqqer') {
                continue;
            }

            $userXml = OPT_DIR . $entry['name'] . '/user.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            DOM::addTabsToToolbar(
                XML::getTabsFromXml($userXml),
                $TabBar,
                $entry['name']
            );
        }

        /**
         * user extension from projects
         */
        $projects = QUI\Projects\Manager::getProjects();

        foreach ($projects as $project) {
            DOM::addTabsToToolbar(
                XML::getTabsFromXml(USR_DIR . 'lib/' . $project . '/user.xml'),
                $TabBar,
                'project.' . $project
            );
        }

        return $TabBar;
    }

    /**
     * Tab contents of a user Tabs / Buttons
     *
     * @param integer $uid
     * @param string $plugin
     * @param string $tab
     *
     * @return string
     * @deprecated
     */
    public static function getTab($uid, $plugin, $tab)
    {
        $Users = QUI::getUsers();
        $User  = $Users->get((int)$uid);

        // assign user as global var
        QUI::getTemplateManager()->assignGlobalParam('User', $User);

        // authenticators
        $userAuthenticators = array();
        $authenticators     = array();
        $available          = QUI::getUsers()->getAvailableAuthenticators();

        foreach ($available as $auth) {
            $authenticators[] = new $auth($User->getName());
        }

        QUI::getTemplateManager()->assignGlobalParam('authenticators', $authenticators);


        $User->getAuthenticators();

        QUI::getTemplateManager()->assignGlobalParam('userAuthenticators', $userAuthenticators);


        // project
        if (strpos($plugin, 'project.') !== false) {
            $project = explode('project.', $plugin);

            return DOM::getTabHTML(
                $tab,
                QUI::getProject($project[1])
            );
        }


        // plugin
        try {
            $plugin  = str_replace('plugin.', '', $plugin);
            $Package = QUI::getPackage($plugin);

            return DOM::getTabHTML(
                $tab,
                OPT_DIR . $Package->getName() . '/user.xml'
            );
        } catch (QUI\Exception $Exception) {
        }

        return '';
    }
}
