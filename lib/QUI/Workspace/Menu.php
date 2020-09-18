<?php

/**
 * This file contains QUI\Workspace\Menu
 */

namespace QUI\Workspace;

use QUI;
use QUI\Permissions\Permission;
use QUI\Controls\Contextmenu\Bar;
use QUI\Controls\Contextmenu\Menuitem;
use QUI\Utils\Text\XML;

/**
 * Class Menu
 */
class Menu
{
    /**
     * Return the menu für the session user
     *
     * @return array
     */
    public function getMenu()
    {
        try {
            $cache = QUI\Cache\Manager::get(
                $this->getCacheName()
            );

            if (!empty($cache)) {
                return $cache;
            }
        } catch (QUI\Exception $Exception) {
        }

        try {
            return $this->createMenu();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return [];
    }

    /**
     * Create the menu
     * no caches use
     *
     * @return array
     * @throws QUI\Exception
     */
    public function createMenu()
    {
        $User = QUI::getUserBySession();

        QUI::getLocale()->setCurrent(
            $User->getLocale()->getCurrent()
        );

        $Menu = new Bar([
            'name'   => 'menu',
            'parent' => 'menubar',
            'id'     => 'menu'
        ]);

        XML::addXMLFileToMenu($Menu, SYS_DIR.'menu.xml');

        if (!$User->isSU()) {
            if ($Menu->getElementByName('quiqqer')) {
                $Menu->getElementByName('quiqqer')->clear();
            }

            if ($Menu->getElementByName('apps')) {
                $Menu->getElementByName('apps')->clear();
            }

            if ($Menu->getElementByName('settings')) {
                $Menu->getElementByName('settings')->clear();
            }
        }

        if ($Menu->getElementByName('extras')) {
            // Benutzerverwaltung
            $canSeeGroups      = Permission::hasPermission('quiqqer.admin.groups.view');
            $canSeeUsers       = Permission::hasPermission('quiqqer.admin.users.view');
            $canSeePermissions = false;

            if ($User->isSU()) {
                $canSeeGroups      = true;
                $canSeeUsers       = true;
                $canSeePermissions = true;
            }

            $Extras = $Menu->getElementByName('extras');
            $Rights = $Extras->getElementByName('rights');

            if (!$canSeeGroups && $Rights) {
                $Rights->removeChild('groups');
            }

            if (!$canSeeUsers && $Rights) {
                $Rights->removeChild('users');
            }

            if (!$canSeePermissions && $Rights) {
                $Rights->removeChild('permissions');
            }

            // Projektverwaltung
            if (!$User->isSU()) {
                $Extras->removeChild('projects');
            }
        }


        // projects settings
        $projects = QUI\Projects\Manager::getProjects();
        $Settings = $Menu->getElementByName('settings');
        $Projects = $Settings->getElementByName('projects');

        foreach ($projects as $project) {
            if (!$User->isSU()) {
                continue;
            }

            if (!$Projects) {
                continue;
            }

            $Projects->appendChild(
                new Menuitem([
                    'text'    => $project,
                    'icon'    => 'fa fa-home',
                    'onclick' => '',
                    'require' => 'controls/projects/project/Settings',
                    'onClick' => 'QUI.Menu.menuClick',
                    'project' => $project,
                    'name'    => $project,
                    '#id'     => 'settings-'.$project
                ])
            );
        }

        // read the settings.xml's
        $files = [];

        if ($User->isSU()) {
            $dir   = SYS_DIR.'settings/';
            $files = QUI\Utils\System\File::readDir($dir);

            foreach ($files as $key => $file) {
                $files[$key] = \str_replace(CMS_DIR, '', $dir.$file);
            }
        }

        // plugin settings
        $packages = QUI::getPackageManager()->getInstalled();

        foreach ($packages as $package) {
            try {
                $Package = QUI::getPackage($package['name']);
            } catch (QUI\Exception $Exception) {
                continue;
            }

            if (!$Package->isQuiqqerPackage()) {
                continue;
            }

            if (!$Package->hasPermission()) {
                continue;
            }

            $setting_file = $Package->getXMLFilePath(QUI\Package\Package::SETTINGS_XML);

            if (\file_exists($setting_file)) {
                $files[] = \str_replace(CMS_DIR, '', $setting_file);
            }
        }


        // create the menu setting entries
        $Settings   = $Menu->getElementByName('settings');
        $windowList = [];

        foreach ($files as $file) {
            $windows = XML::getSettingWindowsFromXml(CMS_DIR.$file);

            if (!$windows) {
                continue;
            }

            foreach ($windows as $Window) {
                /* @var $Window \DOMElement */
                /* @var $Win \DOMElement */
                $winName    = $Window->getAttribute('name');
                $menuParent = $Window->getAttribute('menu-parent');

                if (isset($windowList[$winName])) {
                    /* @var $Item Menuitem */
                    $Item  = $windowList[$winName];
                    $files = $Item->getAttribute('qui-xml-file');

                    if (!\is_array($files)) {
                        $files = [$files];
                    }

                    $files[] = $file;

                    $Item->setAttribute('qui-xml-file', $files);
                    $this->setWindowTitle($Item, $Window);
                    $this->setWindowIcon($Item, $Window);
                    continue;
                }


                $Item = new Menuitem();

                $Item->setAttribute(
                    'name',
                    $menuParent.$Window->getAttribute('name').'/'
                );

                $Item->setAttribute('onClick', 'QUI.Menu.menuClick');
                $Item->setAttribute('qui-window', true);
                $Item->setAttribute('qui-xml-file', $file);

                if (!empty($winName)) {
                    $windowList[$winName] = $Item;
                }

                // titel
                /* @var $Title \DOMElement */
                if (!$Item->getAttribute('text')) {
                    $this->setWindowTitle($Item, $Window);
                }

                $params = $Window->getElementsByTagName('params');

                if ($params->item(0)) {
                    $this->setWindowIcon($Item, $Window);
                }

                if (!$menuParent) {
                    $Settings->appendChild($Item);
                    continue;
                }

                $Parent = $Menu->getElementByPath($menuParent);

                if (!$Parent) {
                    $Settings->appendChild($Item);
                    continue;
                }

                $Parent->appendChild($Item);
            }
        }

        // read the menu.xml's
        $packages = QUI::getPackageManager()->getInstalled();

        foreach ($packages as $package) {
            try {
                $Package = QUI::getPackage($package['name']);
            } catch (QUI\Exception $Exception) {
                continue;
            }

            $menuXml = $Package->getXMLFilePath(QUI\Package\Package::MENU_XML);

            if (!$menuXml) {
                continue;
            }

            if (!$Package->hasPermission()) {
                continue;
            }

            XML::addXMLFileToMenu($Menu, $menuXml, $User);
        }

        $menu = $Menu->toArray();
        $menu = \array_values($menu);

        // sort
        foreach ($menu as $key => $item) {
            if ($item['name'] != 'settings'
                && $item['name'] != 'extras'
                && $item['name'] != 'apps'
            ) {
                continue;
            }

            $menu[$key]['items'] = $this->sortItems($menu[$key]['items']);
        }

        QUI\Cache\Manager::set($this->getCacheName(), $menu);

        return $menu;
    }

    /**
     * Set window title / menu item title
     * only if no title is set
     *
     * @param Menuitem $MenuItem
     * @param \DOMElement $Node
     */
    public function setWindowTitle($MenuItem, $Node)
    {
        if ($MenuItem->getAttribute('text')) {
            return;
        }

        $titles = $Node->getElementsByTagName('title');
        $Title  = $titles->item(0);

        /* @var $Title \DOMElement */
        if ($Title) {
            $MenuItem->setAttribute(
                'text',
                QUI\Utils\DOM::getTextFromNode($Title)
            );
        }
    }

    /**
     * Set window icon / menu item icon
     * only if no icon is set
     *
     * @param Menuitem $MenuItem
     * @param \DOMElement $Node
     */
    public function setWindowIcon($MenuItem, $Node)
    {
        if ($MenuItem->getAttribute('icon')) {
            return;
        }

        $params = $Node->getElementsByTagName('params');

        if (!$params->item(0)) {
            return;
        }

        $icon = $params->item(0)->getElementsByTagName('icon');

        if (!$icon->item(0)) {
            return;
        }

        $MenuItem->setAttribute(
            'icon',
            QUI\Utils\DOM::parseVar($icon->item(0)->nodeValue)
        );
    }

    /**
     * Cachename for the menu
     * The name of the menu cache is user dependent
     */
    protected function getCacheName()
    {
        $User  = QUI::getUserBySession();
        $cache = 'settings/backend-menu/'.$User->getId().'/'.$User->getLang();

        return $cache;
    }

    /**
     * Clear the menu cache for an user
     *
     * @param QUI\Interfaces\Users\User $User
     */
    public static function clearMenuCache(QUI\Interfaces\Users\User $User)
    {
        QUI\Cache\Manager::clear(
            'qui/admin/menu/'.$User->getId().'/'.$User->getLang()
        );
    }

    /**
     * Sort the menu items
     *
     * @param $items
     * @return array
     */
    protected function sortItems($items)
    {
        \usort($items, [$this, 'sortByTitle']);

        foreach ($items as $key => $item) {
            if (isset($item['items']) && !empty($item['items'])) {
                $items[$key]['items'] = $this->sortItems($item['items']);
            }
        }

        return $items;
    }

    /**
     * usort helper function / method
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortByTitle($a, $b)
    {
        if ($a['name'] == 'quiqqer') {
            return -1;
        }

        if ($b['name'] == 'quiqqer') {
            return 1;
        }

        return \strcmp($a["text"], $b["text"]);
    }
}
