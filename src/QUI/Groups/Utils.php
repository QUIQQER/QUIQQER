<?php

/**
 * This file contains QUI\Groups\Utils
 */

namespace QUI\Groups;

use QUI;
use QUI\Controls\Toolbar\Bar;
use QUI\Exception;
use QUI\Utils\DOM;
use QUI\Utils\Text\XML;

use function explode;
use function file_exists;
use function str_replace;

/**
 * Helper for groups
 *
 * @author  Henning Leutz (PCSG)
 * @licence For copyright and license information, please view the /README.md
 */
class Utils
{
    /**
     * JavaScript Buttons / Tabs for a group
     *
     * @param Group $Group
     * @return Bar
     */
    public static function getGroupToolbar(Group $Group): Bar
    {
        $TabBar = new Bar(['name' => 'UserToolbar']);

        DOM::addTabsToToolbar(
            XML::getTabsFromXml(OPT_DIR . 'quiqqer/core/group.xml'),
            $TabBar,
            'quiqqer/core'
        );

        /**
         * user extension from plugins
         */
        $list = QUI::getPackageManager()->getInstalled();

        foreach ($list as $entry) {
            if ($entry['name'] == 'quiqqer/core') {
                continue;
            }

            $userXml = OPT_DIR . $entry['name'] . '/group.xml';

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
                XML::getTabsFromXml(USR_DIR . 'lib/' . $project . '/group.xml'),
                $TabBar,
                'project.' . $project
            );
        }

        return $TabBar;
    }

    /**
     * Tab contents of a group Tab / Button
     *
     * @param integer|string $gid - Group ID
     * @param string $plugin - Plugin
     * @param string $tab - Tab name
     *
     * @return string
     * @throws Exception
     */
    public static function getTab(int|string $gid, string $plugin, string $tab): string
    {
        $Groups = QUI::getGroups();
        $Group = $Groups->get($gid);

        // assign group as global var
        QUI::getTemplateManager()->assignGlobalParam('Group', $Group);

        // project
        if (str_contains($plugin, 'project.')) {
            $project = explode('project.', $plugin);

            return DOM::getTabHTML(
                $tab,
                QUI::getProject($project[1])
            );
        }

        // plugin
        try {
            $plugin = str_replace('plugin.', '', $plugin);
            $Package = QUI::getPackage($plugin);

            return DOM::getTabHTML(
                $tab,
                OPT_DIR . $Package->getName() . '/group.xml'
            );
        } catch (QUI\Exception) {
        }

        return '';
    }
}
