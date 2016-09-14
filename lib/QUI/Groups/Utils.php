<?php

/**
 * This file contains QUI\Groups\Utils
 */

namespace QUI\Groups;

use QUI;
use QUI\Utils\DOM;
use QUI\Utils\Text\XML;

/**
 * Helper for groups
 *
 * @author  Henning Leutz (PCSG)
 * @package com.pcsg.qui.groups
 * @licence For copyright and license information, please view the /README.md
 */
class Utils
{
    /**
     * JavaScript Buttons / Tabs for a group
     *
     * @param \QUI\Groups\Group $Group
     * @return \QUI\Controls\Toolbar\Bar
     */
    public static function getGroupToolbar($Group)
    {
        $Tabbar = new QUI\Controls\Toolbar\Bar(array(
            'name' => 'UserToolbar'
        ));

        DOM::addTabsToToolbar(
            XML::getTabsFromXml(OPT_DIR . 'quiqqer/quiqqer/group.xml'),
            $Tabbar,
            'quiqqer/quiqqer'
        );

        /**
         * user extention from plugins
         */
        $list = QUI::getPackageManager()->getInstalled();

        foreach ($list as $entry) {
            if ($entry['name'] == 'quiqqer/quiqqer') {
                continue;
            }

            $userXml = OPT_DIR . $entry['name'] . '/group.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            DOM::addTabsToToolbar(
                XML::getTabsFromXml($userXml),
                $Tabbar,
                $entry['name']
            );
        }

        /**
         * user extension from projects
         */
        $projects = QUI\Projects\Manager::getProjects();

        foreach ($projects as $project) {
            DOM::addTabsToToolbar(
                XML::getTabsFromXml(USR_DIR . 'lib/' . $project . '/group.xml'),
                $Tabbar,
                'project.' . $project
            );
        }

        return $Tabbar;
    }

    /**
     * Tab contents of a group Tab / Button
     *
     * @param integer $gid - Group ID
     * @param string $plugin - Plugin
     * @param string $tab - Tabname
     *
     * @return string
     */
    public static function getTab($gid, $plugin, $tab)
    {
        $Groups = QUI::getGroups();
        $Group  = $Groups->get($gid);

        // assign group as global var
        QUI::getTemplateManager()->assignGlobalParam('Group', $Group);

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
                OPT_DIR . $Package->getName() . '/group.xml'
            );
        } catch (QUI\Exception $Exception) {
        }

        return '';
    }
}
