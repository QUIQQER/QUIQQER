<?php

/**
 * This file contains QUI\Workspace\Menu
 */
namespace QUI\Workspace;

use QUI;
use QUI\Controls\Contextmenu\Bar;
use QUI\Controls\Contextmenu\Baritem;
use QUI\Controls\Contextmenu\Menuitem;
use QUI\Utils\XML;

/**
 * Class Menu
 *
 * @package QUI\Workspace
 */
class Menu
{
    /**
     * @return array
     */
    public function getMenu()
    {
        try {

            return QUI\Cache\Manager::get(
                $this->_getCacheName()
            );

        } catch (QUI\Exception $Exception) {

        }

        return $this->createMenu();
    }

    /**
     * Create the menu
     * no caches use
     *
     * @return array
     */
    public function createMenu()
    {
        $User = QUI::getUserBySession();

        QUI::getLocale()->setCurrent(
            $User->getLocale()->getCurrent()
        );

        $Menu = new Bar(array(
            'name'   => 'menu',
            'parent' => 'menubar',
            'id'     => 'menu'
        ));

        XML::addXMLFileToMenu($Menu, SYS_DIR . 'menu.xml');

        // projects settings
        $projects = QUI\Projects\Manager::getProjects();
        $Settings = $Menu->getElementByName('settings');
        $Projects = $Settings->getElementByName('projects');

        foreach ($projects as $project) {

            if (!$Projects) {
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
                    'name'    => $project,
                    '#id'     => 'settings-' . $project
                ))
            );
        }

        // read the settings.xmls
        $dir      = SYS_DIR . 'settings/';
        $files    = QUI\Utils\System\File::readDir($dir);
        $Settings = $Menu->getElementByName('settings');

        foreach ($files as $key => $file) {
            $files[$key] = $dir . $file;
        }

        // plugin settings
        $plugins = QUI::getPackageManager()->getInstalled();

        foreach ($plugins as $plugin) {
            $setting_file = OPT_DIR . $plugin['name'] . '/settings.xml';

            if (file_exists($setting_file)) {
                $files[] = $setting_file;
            }
        }


        // create the menu setting entries
        $windowList = array();

        foreach ($files as $file) {
            $windows = XML::getSettingWindowsFromXml($file);

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

                    if (!is_array($files)) {
                        $files = array($files);
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
                    '/settings/' . $Window->getAttribute('name') . '/'
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

        // read the menu.xmls
        $dir = VAR_DIR . 'cache/menu/';

        if (!is_dir($dir)) {
            QUI\Update::importAllMenuXMLs();
        }

        $files = QUI\Utils\System\File::readDir($dir);

        foreach ($files as $file) {
            XML::addXMLFileToMenu($Menu, $dir . $file);
        }

        QUI\Cache\Manager::set($this->_getCacheName(), $Menu->toArray());

        return $Menu->toArray();
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
    protected function _getCacheName()
    {
        $User  = QUI::getUserBySession();
        $cache = 'qui/admin/menu/' . $User->getId() . '/' . $User->getLang();

        return $cache;
    }
}