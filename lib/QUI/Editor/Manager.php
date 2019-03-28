<?php

/**
 * This file contains \QUI\Editor\Manager
 */

namespace QUI\Editor;

use QUI;
use QUI\Utils\Security\Orthos;
use QUI\Utils\System\File as QUIFile;
use QUI\Utils\System\File;
use QUI\Utils\Text\XML;

/**
 * Wysiwyg manager
 *
 * manages all wysiwyg editors and the settings for them
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package quiqqer/quiqqer
 * @licence For copyright and license information, please view the /README.md
 */
class Manager
{
    /**
     * WYSIWYG editor config
     *
     * @var \QUI\Config
     */
    public static $Config = null;

    /**
     * Editor plugins
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * @var null|array
     */
    protected static $toolbars = null;

    /**
     * Setup
     */
    public static function setup()
    {
        QUIFile::mkdir(self::getToolbarsPath());

        if (!\file_exists(CMS_DIR.'etc/wysiwyg/conf.ini.php')) {
            \file_put_contents(CMS_DIR.'etc/wysiwyg/conf.ini.php', '');
        }

        if (!\file_exists(CMS_DIR.'etc/wysiwyg/editors.ini.php')) {
            \file_put_contents(CMS_DIR.'etc/wysiwyg/editors.ini.php', '');
        }

        // If toolbar path is empty, use default toolbars
        $path = self::getToolbarsPath();

        if (!\is_dir($path)) {
            File::mkdir($path);
        }

        // Remove old standard.xml toolbar for compatibility
        if (\file_exists($path."standard.xml")) {
            \rename($path."standard.xml", CMS_DIR."var/backup/standard.xml");
        }

        $toolbars = File::readDir($path);

        if (empty($toolbars)) {
            $defaultBarDir = \dirname(__FILE__).'/toolbars/';
            $toolbars      = File::readDir($defaultBarDir);

            foreach ($toolbars as $toolbar) {
                File::copy(
                    $defaultBarDir.$toolbar,
                    $path.$toolbar
                );
            }

            // Prepare the root (admin) group for the new toolbars
            $rootGroupID = QUI::conf("globals", "root");
            $rootToolbar = "advanced.xml";

            // Fallback in case the "redakteur.xml" toolbar does not exist.
            // Should never happen in properly configured systems!
            if (!\in_array("advanced.xml", $toolbars)) {
                $rootToolbar = $toolbars[0];
            }

            QUI::getDataBase()->update(
                QUI::getDBTableName("groups"),
                [
                    "toolbar"          => $rootToolbar,
                    "assigned_toolbar" => \implode(",", $toolbars)
                ],
                [
                    "id" => $rootGroupID
                ]
            );

            // Set "minimal.xml" as new default toolbar for the everyone group
            if (\in_array("minimal.xml", $toolbars)) {
                QUI::getDataBase()->update(
                    QUI::getDBTableName("groups"),
                    [
                        "toolbar"          => "minimal.xml",
                        "assigned_toolbar" => "minimal.xml"
                    ],
                    [
                        "id" => 1
                    ]
                );
            }
        }
    }

    /**
     * Path to the toolbar xml files
     *
     * @return string
     */
    public static function getPath()
    {
        return CMS_DIR.'etc/wysiwyg/';
    }

    /**
     * Return the path to the toolbars
     *
     * @return string
     */
    public static function getToolbarsPath()
    {
        return self::getPath().'toolbars/';
    }

    /**
     * Return the main editor manager (wyiswyg) config object
     *
     * @return QUI\Config
     */
    public static function getConf()
    {
        if (!self::$Config) {
            self::$Config = QUI::getConfig('etc/wysiwyg/conf.ini.php');
        }

        return self::$Config;
    }

    /**
     * Return all settings of the manager
     *
     * @return array
     */
    public static function getConfig()
    {
        $config             = self::getConf()->toArray();
        $config['toolbars'] = self::getToolbars();
        $config['editors']  = QUI::getConfig('etc/wysiwyg/editors.ini.php')->toArray();

        return $config;
    }

    /**
     * Register a js editor
     *
     * @param string $name - name of the editor
     * @param string $package - js modul/package name
     */
    public static function registerEditor($name, $package)
    {
        $Conf = QUI::getConfig('etc/wysiwyg/editors.ini.php');
        $Conf->setValue($name, null, $package);
        $Conf->save();
    }

    /**
     * Load the html for an editor and clean it up
     *
     * @param string $html
     *
     * @return string
     */
    public function load($html)
    {
        // Bilder umschreiben
        $html = \preg_replace_callback(
            '#(src)="([^"]*)"#',
            [$this, "cleanAdminSrc"],
            $html
        );

        foreach ($this->plugins as $p) {
            if (\method_exists($p, 'onLoad')) {
                $html = $p->onLoad($html);
            }
        }

        return $html;
    }

    /**
     * Search toolbars
     *
     * @param $search
     *
     * @return array
     */
    public static function search($search)
    {
        return \array_filter(self::getToolbars(), function ($toolbar) use ($search) {
            return \strpos($toolbar, $search) !== false;
        });
    }

    /**
     * Checks if the toolbar exists
     *
     * @param $toolbar
     *
     * @return bool
     */
    public static function existsToolbar($toolbar)
    {
        $toolbars = self::getToolbars();
        $toolbars = \array_flip($toolbars);

        return isset($toolbars[$toolbar]);
    }

    /**
     * Return all available toolbars
     *
     * @return array
     */
    public static function getToolbars()
    {
        if (self::$toolbars !== null) {
            return self::$toolbars;
        }

        $folder = self::getToolbarsPath();
        $files  = QUIFile::readDir($folder, true);

        self::$toolbars = $files;

        return $files;
    }

    /**
     * Return all available toolbars for an user
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return array
     */
    public static function getToolbarsFromUser(QUI\Interfaces\Users\User $User)
    {
        $result = [];
        $groups = $User->getGroups();

        if (!\is_array($groups)) {
            $groups = [];
        }

        /* @var $Group QUI\Groups\Group */
        foreach ($groups as $Group) {
            if ($Group->getAttribute('assigned_toolbar')) {
                $toolbars = \explode(',', $Group->getAttribute('assigned_toolbar'));

                foreach ($toolbars as $toolbar) {
                    $result[] = $toolbar;
                }
            }
        }

        $userSpecific = $User->getAttribute('assigned_toolbar');

        if ($userSpecific) {
            $userSpecific = \explode(',', $userSpecific);

            foreach ($userSpecific as $toolbar) {
                $result[] = $toolbar;
            }
        }

        $result = \array_unique($result);
        \sort($result);

        return $result;
    }

    /**
     * Return all available toolbars for a group
     *
     * @param QUI\Groups\Group $Group
     *
     * @return array
     */
    public static function getToolbarsFromGroup(QUI\Groups\Group $Group)
    {
        $result = [];

        if ($Group->getAttribute('toolbar') &&
            self::existsToolbar($Group->getAttribute('toolbar'))
        ) {
            $result[] = $Group->getAttribute('toolbar');
        }

        $groupSpecific = $Group->getAttribute('assigned_toolbar');

        if ($groupSpecific) {
            $groupSpecific = \explode(',', $groupSpecific);

            foreach ($groupSpecific as $toolbar) {
                if (self::existsToolbar($toolbar)) {
                    $result[] = $toolbar;
                }
            }
        }

        $result = \array_unique($result);
        \sort($result);

        return $result;
    }

    /**
     * Return the Editor Settings for a specific Project
     *
     * @param QUI\Projects\Project $Project
     *
     * @return array
     */
    public static function getSettings(QUI\Projects\Project $Project)
    {
        $project   = $Project->getName();
        $cacheName = $Project->getName().'/'.$Project->getLang().'/wysiwyg/settings';

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
        }

        // css files
        $css    = [];
        $styles = [];
        $file   = USR_DIR.$Project->getName().'/settings.xml';

        $bodyId    = false;
        $bodyClass = false;

        // project files
        if (\file_exists($file)) {
            $files = XML::getWysiwygCSSFromXml($file);

            foreach ($files as $cssfile) {
                $css[] = URL_USR_DIR.$project.'/'.$cssfile;
            }

            // id and css class
            $Dom  = XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

            $WYSIWYG = $Path->query("//wysiwyg");

            if ($WYSIWYG->length) {
                $bodyId    = $WYSIWYG->item(0)->getAttribute('id');
                $bodyClass = $WYSIWYG->item(0)->getAttribute('class');
            }

            // styles
            $styles = \array_merge(
                QUI\Utils\DOM::getWysiwygStyles($Dom),
                $styles
            );
        }

        // template files
        $templates = [];

        if ($Project->getAttribute('template')) {
            $templates[] = OPT_DIR.$Project->getAttribute('template').'/settings.xml';
        }

        // project vhosts
        $VHosts       = new QUI\System\VhostManager();
        $projectHosts = $VHosts->getHostsByProject($Project->getName());

        foreach ($projectHosts as $host) {
            $data = $VHosts->getVhost($host);

            if (!isset($data['template'])) {
                continue;
            }

            if (empty($data['template'])) {
                continue;
            }

            $file = OPT_DIR.$data['template'].'/settings.xml';

            if (\file_exists($file)) {
                $templates[] = $file;
            }
        }

        $templates = \array_unique($templates);


        foreach ($templates as $file) {
            if (!\file_exists($file)) {
                continue;
            }

            if (empty($css)) {
                $cssFiles = XML::getWysiwygCSSFromXml($file);

                foreach ($cssFiles as $cssFile) {
                    // external file
                    if (\strpos($cssFile, '//') === 0
                        || \strpos($cssFile, 'https://') === 0
                        || \strpos($cssFile, 'http://') === 0
                    ) {
                        $css[] = $cssFile;
                        continue;
                    }

                    $css[] = QUI\Utils\DOM::parseVar($cssFile);
                }
            }

            // id and css class
            if (!$bodyId && !$bodyClass) {
                $Dom  = XML::getDomFromXml($file);
                $Path = new \DOMXPath($Dom);

                $WYSIWYG = $Path->query("//wysiwyg");

                if ($WYSIWYG->length) {
                    $bodyId    = $WYSIWYG->item(0)->getAttribute('id');
                    $bodyClass = $WYSIWYG->item(0)->getAttribute('class');
                }

                $styles = \array_merge(
                    QUI\Utils\DOM::getWysiwygStyles($Dom),
                    $styles
                );
            }
        }

        // read wysiwyg styles && css files from packages files
        $packages = QUI::getPackageManager()->getInstalled();

        foreach ($packages as $package) {
            if ($package['type'] != 'quiqqer-plugin'
                && $package['type'] != 'quiqqer-module'
            ) {
                continue;
            }

            $settings = OPT_DIR.$package['name'].'/settings.xml';

            if (!\file_exists($settings)) {
                continue;
            }

            $Dom = XML::getDomFromXml($settings);

            // styles
            $styles = \array_merge(
                QUI\Utils\DOM::getWysiwygStyles($Dom),
                $styles
            );

            // css files
            $cssFiles = XML::getWysiwygCSSFromXml($settings);

            foreach ($cssFiles as $cssFile) {
                // external file
                if (\strpos($cssFile, '//') === 0
                    || \strpos($cssFile, 'https://') === 0
                    || \strpos($cssFile, 'http://') === 0
                ) {
                    $css[] = $cssFile;
                    continue;
                }

                $css[] = QUI\Utils\DOM::parseVar($cssFile);
            }
        }

        // custom css file
        if (\file_exists(USR_DIR.$project.'/bin/custom.css')) {
            $css[] = URL_USR_DIR.$project.'/bin/custom.css';
        }

        $result = [
            'cssFiles'  => $css,
            'bodyId'    => $bodyId,
            'bodyClass' => $bodyClass,
            'styles'    => $styles
        ];

        try {
            QUI\Cache\Manager::set($cacheName, $result);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $result;
    }


    /**
     * Return the available styles
     *
     * @param \QUI\Projects\Project|boolean $Project - (optional)
     *
     * @return array
     */
    public static function getStyles($Project = false)
    {
        $styles = [];

        if ($Project) {
        }

        return $styles;
    }

    /**
     * Delete a toolbar
     *
     * @param string $toolbar - Name of the tools (toolbar.xml)
     */
    public static function deleteToolbar($toolbar)
    {
        QUI\Permissions\Permission::hasPermission(
            'quiqqer.editors.toolbar.delete'
        );

        $folder = self::getToolbarsPath();
        $path   = $folder.$toolbar;

        $path = Orthos::clearPath($path);

        if (\file_exists($path)) {
            \unlink($path);
        }
    }

    /**
     * Add a new toolbar
     *
     * @param string $toolbar - Name of the tools (myNewToolbar)
     *
     * @throws QUI\Exception
     */
    public static function addToolbar($toolbar)
    {
        QUI\Permissions\Permission::hasPermission(
            'quiqqer.editors.toolbar.add'
        );

        $toolbar = \str_replace('.xml', '', $toolbar);

        $folder = self::getToolbarsPath();
        $file   = $folder.$toolbar.'.xml';

        if (\file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.editor.manager.toolbar.exist'
                )
            );
        }

        QUIFile::mkfile($file);
    }

    /**
     * Save the Toolbar
     *
     * @param string $toolbar - toolbar name
     * @param string $xml - toolbar xml
     *
     * @throws QUI\Exception
     */
    public static function saveToolbar($toolbar, $xml)
    {
        QUI\Permissions\Permission::hasPermission(
            'quiqqer.editors.toolbar.save'
        );

        if (empty($xml)) {
            throw new QUI\Exception([
                'quiqqer/system',
                'exception.lib.qui.editor.manager.toolbar.empty'
            ]);
        }

        $toolbar = \str_replace('.xml', '', $toolbar);

        $folder = self::getToolbarsPath();
        $file   = $folder.$toolbar.'.xml';

        if (!\file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.editor.manager.toolbar.exist'
                )
            );
        }

        // check the xml
        \libxml_use_internal_errors(true);

        $Doc = new \DOMDocument('1.0', 'utf-8');
        $Doc->loadXML($xml);

        $errors = \libxml_get_errors();

        if (!empty($errors)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.editor.manager.toolbar.xml.error',
                    ['error' => $errors[0]->message]
                )
            );
        }

        \file_put_contents($file, $xml);


        QUI\Cache\Manager::clear('editor/xml/file');
    }

    /**
     * Return the toolbar buttons for an user
     * Used the right user toolbar
     *
     * @return array
     */
    public static function getToolbarButtonsFromUser()
    {
        $Users = QUI::getUsers();
        $User  = $Users->getUserBySession();

        if (!$Users->isAuth($User)) {
            return [];
        }

        // Benutzer spezifische Toolbar
        $toolbar     = $User->getAttribute('toolbar');
        $toolbarPath = self::getToolbarsPath();

        if (!empty($toolbar)) {
            $toolbar = $toolbarPath.$User->getAttribute('toolbar');

            if (\file_exists($toolbar)) {
                return self::parseXmlFileToArray($toolbar);
            }
        }

        // Gruppenspezifische Toolbar
        $groups = $User->getGroups();

        /* @var $Group QUI\Groups\Group */
        foreach ($groups as $Group) {
            $toolbar = $Group->getAttribute('toolbar');

            if (!empty($toolbar)) {
                $toolbar = $toolbarPath.$Group->getAttribute('toolbar');

                if (\file_exists($toolbar)) {
                    return self::parseXmlFileToArray($toolbar);
                }
            }
        }


        $Config  = self::getConf();
        $toolbar = $Config->get('toolbars', 'standard');

        // standard
        if ($toolbar === false) {
            return [];
        }

        if (\strpos($toolbar, '.xml') !== false) {
            if (\file_exists($toolbarPath.$toolbar)) {
                return self::parseXmlFileToArray($toolbarPath.$toolbar);
            }
        }

        return \explode(',', $Config->get('toolbars', 'standard'));
    }

    /**
     * Reads a toolbar xml and return and return it as array
     *
     * @param string $file - path to the file
     *
     * @return array
     */
    public static function parseXmlFileToArray($file)
    {
        $cache = 'editor/xml/file/'.\md5($file);

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        $Dom     = XML::getDomFromXml($file);
        $toolbar = $Dom->getElementsByTagName('toolbar');

        if (!$toolbar->length) {
            return [];
        }

        $children = $toolbar->item(0)->childNodes;
        $result   = [];

        for ($i = 0; $i < $children->length; $i++) {
            $Param = $children->item($i);

            if ($Param->nodeName == '#text') {
                continue;
            }

            if ($Param->nodeName == 'line') {
                $result['lines'][] = self::parseXMLLineNode($Param);
            }

            if ($Param->nodeName == 'group') {
                $result['groups'][] = self::parseXMLGroupNode($Param);
            }
        }

        QUI\Cache\Manager::set($cache, $result);

        return $result;
    }

    /**
     * Parse an XML <line> node
     *
     * @param \DOMNode $Node
     *
     * @return boolean|array
     */
    public static function parseXMLLineNode($Node)
    {
        if ($Node->nodeName != 'line') {
            return false;
        }

        $children = $Node->childNodes;
        $result   = [];

        for ($i = 0; $i < $children->length; $i++) {
            $Param = $children->item($i);

            if ($Param->nodeName == '#text') {
                continue;
            }

            if ($Param->nodeName == 'group') {
                $result[] = self::parseXMLGroupNode($Param);
            }
        }

        return $result;
    }

    /**
     * Parse an XML <group> node
     *
     * @param \DOMNode $Node
     *
     * @return boolean|array
     */
    public static function parseXMLGroupNode($Node)
    {
        if ($Node->nodeName != 'group') {
            return false;
        }

        $children = $Node->childNodes;
        $result   = [];

        for ($i = 0; $i < $children->length; $i++) {
            $Param = $children->item($i);

            if ($Param->nodeName == 'separator') {
                $result[] = [
                    'type' => 'separator'
                ];

                continue;
            }

            if ($Param->nodeName == 'button') {
                $result[] = [
                    'type'   => 'button',
                    'button' => \trim($Param->nodeValue)
                ];
            }
        }

        return $result;
    }

    /**
     * Clean up methods
     */

    /**
     * Cleanup HTML
     *
     * @uses Tidy, if enabled
     *
     * @param string $html
     *
     * @return string
     */
    public function cleanHTML($html)
    {
        $html = \preg_replace('/<!--\[if gte mso.*?-->/s', '', $html);

        $search = [
            'font-family: Arial',
            'class="MsoNormal"'
        ];

        $html = \str_ireplace($search, '', $html);

        if (\class_exists('tidy')) {
            $Tidy = new \Tidy();

            $config = [
                "char-encoding"       => "utf8",
                'output-xhtml'        => true,
                'indent-attributes'   => false,
                'wrap'                => 0,
                'word-2000'           => 1,
                // html 5 Tags registrieren
                'new-blocklevel-tags' => 'header, footer, article, section, hgroup, nav, figure'
            ];

            $Tidy->parseString($html, $config, 'utf8');
            $Tidy->cleanRepair();
            $html = $Tidy;
        }

        return $html;
    }

    /**
     * Prepare html for saving
     * Clean it up
     *
     * @param string $html
     *
     * @return string
     */
    public function prepareHTMLForSave($html)
    {
        // Bilder umschreiben
        $html = \preg_replace_callback(
            '#(src)="([^"]*)"#',
            [$this, "cleanSrc"],
            $html
        );

        $html = \preg_replace_callback(
            '#(href)="([^"]*)"#',
            [$this, "cleanHref"],
            $html
        );

        foreach ($this->plugins as $p) {
            if (\method_exists($p, 'onSave')) {
                $html = $p->onSave($html);
            }
        }

        $html = $this->cleanHTML($html);

        // Zeilenumbrüche in HTML löschen
        $html = \preg_replace_callback(
            '#(<)(.*?)(>)#',
            [$this, "deleteLineBreaksInHtml"],
            $html
        );

        return $html;
    }

    /**
     * Delete line breaks in html content
     *
     * @param array $params
     *
     * @return string
     */
    protected function deleteLineBreaksInHtml($params)
    {
        if (!isset($params[0])) {
            return $params[0];
        }

        return \str_replace(
            ["\r\n", "\n", "\r"],
            "",
            $params[0]
        );
    }

    /**
     * Cleanup image src
     *
     * @param array $html
     *
     * @return string
     */
    public function cleanSrc($html)
    {
        if (isset($html[2]) && \strpos($html[2], 'image.php') !== false) {
            $html[2] = \str_replace('&amp;', '&', $html[2]);
            $src_    = \explode('image.php?', $html[2]);

            return ' '.$html[1].'="image.php?'.$src_[1].'"';
        }

        return $html[0];
    }

    /**
     * Cleanup image href
     *
     * @param array $html
     *
     * @return string
     */
    public function cleanHref($html)
    {
        if (isset($html[2]) && \strpos($html[2], 'index.php') !== false) {
            $index = \explode('index.php?', $html[2]);

            return $html[1].'="index.php?'.$index[1].'"';
        }


        if (isset($html[2]) && \strpos($html[2], 'image.php') !== false) {
            $index = \explode('image.php?', $html[2]);

            return ' '.$html[1].'="image.php?'.$index[1].'"';
        }

        return $html[0];
    }

    /**
     * Cleanup image.php? paths from the admin
     *
     * @param array $html
     *
     * @return string
     */
    public function cleanAdminSrc($html)
    {
        if (isset($html[2]) && \strpos($html[2], 'image.php') !== false) {
            $src_ = \explode('image.php?', $html[2]);

            return ' '.$html[1].'="'.URL_DIR.'image.php?'.$src_[1].'" ';
        }

        return $html[0];
    }
}
