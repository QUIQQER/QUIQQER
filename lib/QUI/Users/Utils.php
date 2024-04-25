<?php

/**
 * This file contains \QUI\Users\Utils
 */

namespace QUI\Users;

use QUI;
use QUI\Controls\Toolbar\Bar;
use QUI\Utils\DOM;
use QUI\Utils\Text\XML;

use function explode;
use function file_exists;
use function str_replace;

/**
 * Helper for users
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Utils
{
    /**
     * JavaScript Buttons / Tabs from a user
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return Bar
     */
    public static function getUserToolbar(QUI\Interfaces\Users\User $User): Bar
    {
        $TabBar = new Bar([
            'name' => 'UserToolbar'
        ]);

        DOM::addTabsToToolbar(
            XML::getTabsFromXml(OPT_DIR . 'quiqqer/quiqqer/user.xml'),
            $TabBar,
            'quiqqer/quiqqer'
        );

        if (!$User->getUUID()) {
            return $TabBar;
        }

        /**
         * user extension from plugins
         */

        // tabs xml
        $list = QUI::getPackageManager()->getInstalled();
        $userXmlFiles = [];

        foreach ($list as $entry) {
            if ($entry['name'] == 'quiqqer/quiqqer') {
                continue;
            }

            $userXml = OPT_DIR . $entry['name'] . '/user.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            $userXmlFiles[] = $userXml;

            DOM::addTabsToToolbar(
                XML::getTabsFromXml($userXml),
                $TabBar,
                $entry['name']
            );
        }

        // category xml
        $Settings = QUI\Utils\XML\Settings::getInstance();
        $Settings->setXMLPath('//user/window');

        $result = $Settings->getPanel($userXmlFiles);
        $categories = $result['categories']->toArray();

        foreach ($categories as $category) {
            $TabBar->appendChild(
                new QUI\Controls\Toolbar\Tab([
                    'name' => $category['name'],
                    'text' => QUI::getLocale()->parseLocaleString($category['title']),
                    'image' => $category['icon'],
                    'wysiwyg' => false,
                    'type' => 'xml',
                    'plugin' => $category['file']
                ])
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
     * @param integer|string $uid
     * @param string $plugin
     * @param string $tab
     *
     * @return string
     *
     * @throws QUI\Exception
     *
     * @todo kick <tab> as xml in user.xml
     */
    public static function getTab($uid, string $plugin, string $tab): string
    {
        $Users = QUI::getUsers();
        $User = $Users->get($uid);
        $AuthHandler = Auth\Handler::getInstance();

        // assign user as global var
        QUI::getTemplateManager()->assignGlobalParam('User', $User);

        // authenticators
        $userAuthenticators = [];
        $authenticators = $AuthHandler->getAvailableAuthenticators();

        foreach ($authenticators as $authenticator) {
            try {
                if (Auth\Helper::hasUserPermissionToUseAuthenticator($User, $authenticator)) {
                    $userAuthenticators[] = new $authenticator($User->getName());
                }
            } catch (QUI\Exception) {
            }
        }

        QUI::getTemplateManager()->assignGlobalParam('authenticators', $authenticators);
        QUI::getTemplateManager()->assignGlobalParam('userAuthenticators', $userAuthenticators);

        // <category>
        if (file_exists($plugin)) {
            $Settings = QUI\Utils\XML\Settings::getInstance();
            $Settings->setXMLPath('//user/window');

            return $Settings->getCategoriesHtml([$plugin], $tab);
        }


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
            $plugin = str_replace('plugin.', '', $plugin);
            $Package = QUI::getPackage($plugin);

            return DOM::getTabHTML(
                $tab,
                OPT_DIR . $Package->getName() . '/user.xml'
            );
        } catch (QUI\Exception) {
        }


        return '';
    }
}
