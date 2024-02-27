<?php

/**
 * This file contains \QUI\Editor\Manager
 */

namespace QUI\Editor;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use QUI;
use QUI\Config;
use QUI\Projects\Project;
use QUI\Utils\Security\Orthos;
use QUI\Utils\System\File;
use QUI\Utils\System\File as QUIFile;
use QUI\Utils\Text\XML;
use Tidy;

use function array_filter;
use function array_flip;
use function array_merge;
use function array_unique;
use function class_exists;
use function dirname;
use function explode;
use function file_exists;
use function file_put_contents;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function md5;
use function method_exists;
use function preg_replace;
use function preg_replace_callback;
use function rename;
use function sort;
use function str_ireplace;
use function str_replace;
use function strpos;
use function trim;
use function unlink;

use const OPT_DIR;
use const URL_DIR;
use const USR_DIR;

/**
 * Wysiwyg manager
 *
 * manages all wysiwyg editors and the settings for them
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Manager
{
    /**
     * WYSIWYG editor config
     *
     * @var Config|null
     */
    public static ?Config $Config = null;
    /**
     * @var null|array
     */
    protected static ?array $toolbars = null;
    /**
     * Editor plugins
     *
     * @var array
     */
    protected array $plugins = [];

    /**
     * Setup
     *
     * @throws QUI\Exception
     * @throws QUI\DataBase\Exception
     */
    public static function setup()
    {
        QUIFile::mkdir(self::getToolbarsPath());

        if (!file_exists(CMS_DIR . 'etc/wysiwyg/conf.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/wysiwyg/conf.ini.php', '');
        }

        if (!file_exists(CMS_DIR . 'etc/wysiwyg/editors.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/wysiwyg/editors.ini.php', '');
        }

        // If toolbar path is empty, use default toolbars
        $path = self::getToolbarsPath();

        if (!is_dir($path)) {
            File::mkdir($path);
        }

        // Remove old standard.xml toolbar for compatibility
        if (file_exists($path . "standard.xml")) {
            rename($path . "standard.xml", CMS_DIR . "var/backup/standard.xml");
        }

        $toolbars = File::readDir($path);

        if (empty($toolbars)) {
            $defaultBarDir = __DIR__ . '/toolbars/';
            $toolbars = File::readDir($defaultBarDir);

            foreach ($toolbars as $toolbar) {
                File::copy(
                    $defaultBarDir . $toolbar,
                    $path . $toolbar
                );
            }

            // Prepare the root (admin) group for the new toolbars
            $rootGroupID = QUI::conf("globals", "root");
            $rootToolbar = "advanced.xml";

            // Fallback in case the "redakteur.xml" toolbar does not exist.
            // Should never happen in properly configured systems!
            if (!in_array("advanced.xml", $toolbars)) {
                $rootToolbar = $toolbars[0];
            }

            QUI::getDataBase()->update(
                QUI::getDBTableName("groups"),
                [
                    "toolbar" => $rootToolbar,
                    "assigned_toolbar" => implode(",", $toolbars)
                ],
                [
                    "id" => $rootGroupID
                ]
            );

            // Set "minimal.xml" as new default toolbar for the everyone group
            if (in_array("minimal.xml", $toolbars)) {
                QUI::getDataBase()->update(
                    QUI::getDBTableName("groups"),
                    [
                        "toolbar" => "minimal.xml",
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
     * Return the path to the toolbars
     *
     * @return string
     */
    public static function getToolbarsPath(): string
    {
        return self::getPath() . 'toolbars/';
    }

    /**
     * Path to the toolbar xml files
     *
     * @return string
     */
    public static function getPath(): string
    {
        return CMS_DIR . 'etc/wysiwyg/';
    }

    /**
     * Register a js editor
     *
     * @param string $name - name of the editor
     * @param string $package - js modul/package name
     *
     * @throws QUI\Exception
     */
    public static function registerEditor(string $name, string $package)
    {
        $Conf = QUI::getConfig('etc/wysiwyg/editors.ini.php');
        $Conf->setValue($name, null, $package);
        $Conf->save();
    }

    /**
     * Return all settings of the manager
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public static function getConfig(): array
    {
        $config = self::getConf()->toArray();
        $config['toolbars'] = self::getToolbars();
        $config['editors'] = QUI::getConfig('etc/wysiwyg/editors.ini.php')->toArray();

        return $config;
    }

    /**
     * Return the main editor manager (wyiswyg) config object
     *
     * @return Config
     *
     * @throws QUI\Exception
     */
    public static function getConf(): ?Config
    {
        if (!self::$Config) {
            self::$Config = QUI::getConfig('etc/wysiwyg/conf.ini.php');
        }

        return self::$Config;
    }

    /**
     * Return all available toolbars
     *
     * @return array
     */
    public static function getToolbars(): ?array
    {
        if (self::$toolbars !== null) {
            return self::$toolbars;
        }

        $folder = self::getToolbarsPath();
        $files = QUIFile::readDir($folder, true);

        self::$toolbars = $files;

        return $files;
    }

    /**
     * Search toolbars
     *
     * @param $search
     *
     * @return array
     */
    public static function search($search): array
    {
        return array_filter(self::getToolbars(), function ($toolbar) use ($search) {
            return strpos($toolbar, $search) !== false;
        });
    }

    /**
     * Return all available toolbars for an user
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return array
     */
    public static function getToolbarsFromUser(QUI\Interfaces\Users\User $User): array
    {
        $result = [];
        $groups = $User->getGroups();

        if (!is_array($groups)) {
            $groups = [];
        }

        /* @var $Group QUI\Groups\Group */
        foreach ($groups as $Group) {
            if ($Group->getAttribute('assigned_toolbar')) {
                $toolbars = explode(',', $Group->getAttribute('assigned_toolbar'));

                foreach ($toolbars as $toolbar) {
                    $result[] = $toolbar;
                }
            }
        }

        $userSpecific = $User->getAttribute('assigned_toolbar');

        if ($userSpecific) {
            $userSpecific = explode(',', $userSpecific);

            foreach ($userSpecific as $toolbar) {
                $result[] = $toolbar;
            }
        }

        $result = array_unique($result);
        sort($result);

        return $result;
    }

    /**
     * Return all available toolbars for a group
     *
     * @param QUI\Groups\Group $Group
     *
     * @return array
     */
    public static function getToolbarsFromGroup(QUI\Groups\Group $Group): array
    {
        $result = [];

        if (
            $Group->getAttribute('toolbar') &&
            self::existsToolbar($Group->getAttribute('toolbar'))
        ) {
            $result[] = $Group->getAttribute('toolbar');
        }

        $groupSpecific = $Group->getAttribute('assigned_toolbar');

        if ($groupSpecific) {
            $groupSpecific = explode(',', $groupSpecific);

            foreach ($groupSpecific as $toolbar) {
                if (self::existsToolbar($toolbar)) {
                    $result[] = $toolbar;
                }
            }
        }

        $result = array_unique($result);
        sort($result);

        return $result;
    }

    /**
     * Checks if the toolbar exists
     *
     * @param $toolbar
     *
     * @return bool
     */
    public static function existsToolbar($toolbar): bool
    {
        $toolbars = self::getToolbars();
        $toolbars = array_flip($toolbars);

        return isset($toolbars[$toolbar]);
    }

    /**
     * Return the Editor Settings for a specific Project
     *
     * @param Project $Project
     *
     * @return array
     */
    public static function getSettings(Project $Project): array
    {
        $project = $Project->getName();
        $cacheName = $Project->getCachePath() . '/wysiwyg-settings';

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
        }

        // css files
        $css = [];
        $styles = [];
        $file = USR_DIR . $Project->getName() . '/settings.xml';

        $bodyId = false;
        $bodyClass = false;

        // project files
        if (file_exists($file)) {
            $files = XML::getWysiwygCSSFromXml($file);

            foreach ($files as $cssfile) {
                $css[] = URL_USR_DIR . $project . '/' . $cssfile;
            }

            // id and css class
            $Dom = XML::getDomFromXml($file);
            $Path = new DOMXPath($Dom);

            $WYSIWYG = $Path->query("//wysiwyg");

            if ($WYSIWYG->length) {
                $bodyId = $WYSIWYG->item(0)->getAttribute('id');
                $bodyClass = $WYSIWYG->item(0)->getAttribute('class');
            }

            // styles
            $styles = array_merge(
                QUI\Utils\DOM::getWysiwygStyles($Dom),
                $styles
            );
        }

        // template files
        $templates = [];

        if ($Project->getAttribute('template')) {
            try {
                $Package = QUI::getPackage($Project->getAttribute('template'));
                $templates[] = OPT_DIR . $Package->getName() . '/settings.xml';

                $TemplateParent = $Package->getTemplateParent();

                if ($TemplateParent) {
                    $templates[] = OPT_DIR . $TemplateParent->getName() . '/settings.xml';
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        // project vhosts
        $VHosts = new QUI\System\VhostManager();
        $projectHosts = $VHosts->getHostsByProject($Project->getName());

        foreach ($projectHosts as $host) {
            $data = $VHosts->getVhost($host);

            if (!isset($data['template'])) {
                continue;
            }

            if (empty($data['template'])) {
                continue;
            }

            $file = OPT_DIR . $data['template'] . '/settings.xml';

            if (file_exists($file)) {
                $templates[] = $file;
            }
        }

        $templates = array_unique($templates);


        foreach ($templates as $file) {
            if (!file_exists($file)) {
                continue;
            }

            if (empty($css)) {
                $cssFiles = XML::getWysiwygCSSFromXml($file);

                foreach ($cssFiles as $cssFile) {
                    // external file
                    if (
                        strpos($cssFile, '//') === 0
                        || strpos($cssFile, 'https://') === 0
                        || strpos($cssFile, 'http://') === 0
                    ) {
                        $css[] = $cssFile;
                        continue;
                    }

                    $css[] = QUI\Utils\DOM::parseVar($cssFile);
                }
            }

            // id and css class
            if (!$bodyId && !$bodyClass) {
                $Dom = XML::getDomFromXml($file);
                $Path = new DOMXPath($Dom);

                $WYSIWYG = $Path->query("//wysiwyg");

                if ($WYSIWYG->length) {
                    $bodyId = $WYSIWYG->item(0)->getAttribute('id');
                    $bodyClass = $WYSIWYG->item(0)->getAttribute('class');
                }

                $styles = array_merge(
                    QUI\Utils\DOM::getWysiwygStyles($Dom),
                    $styles
                );
            }
        }

        // read wysiwyg styles && css files from packages files
        $packages = QUI::getPackageManager()->getInstalled();

        foreach ($packages as $package) {
            if (
                $package['type'] != 'quiqqer-plugin'
                && $package['type'] != 'quiqqer-module'
            ) {
                continue;
            }

            $settings = OPT_DIR . $package['name'] . '/settings.xml';

            if (!file_exists($settings)) {
                continue;
            }

            $Dom = XML::getDomFromXml($settings);

            // styles
            $styles = array_merge(
                QUI\Utils\DOM::getWysiwygStyles($Dom),
                $styles
            );

            // css files
            $cssFiles = XML::getWysiwygCSSFromXml($settings);

            foreach ($cssFiles as $cssFile) {
                // external file
                if (
                    strpos($cssFile, '//') === 0
                    || strpos($cssFile, 'https://') === 0
                    || strpos($cssFile, 'http://') === 0
                ) {
                    $css[] = $cssFile;
                    continue;
                }

                $css[] = QUI\Utils\DOM::parseVar($cssFile);
            }
        }

        // custom css file
        if (file_exists(USR_DIR . $project . '/bin/custom.css')) {
            $css[] = URL_USR_DIR . $project . '/bin/custom.css';
        }

        $result = [
            'cssFiles' => $css,
            'bodyId' => $bodyId,
            'bodyClass' => $bodyClass,
            'styles' => $styles
        ];

        try {
            QUI\Cache\Manager::set($cacheName, $result);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $result;
    }

    /**
     * Return the available styles
     *
     * @param Project|boolean $Project - (optional)
     *
     * @return array
     */
    public static function getStyles($Project = false): array
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
    public static function deleteToolbar(string $toolbar)
    {
        QUI\Permissions\Permission::hasPermission(
            'quiqqer.editors.toolbar.delete'
        );

        $folder = self::getToolbarsPath();
        $path = $folder . $toolbar;

        $path = Orthos::clearPath($path);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Add a new toolbar
     *
     * @param string $toolbar - Name of the tools (myNewToolbar)
     *
     * @throws QUI\Exception
     */
    public static function addToolbar(string $toolbar)
    {
        QUI\Permissions\Permission::hasPermission(
            'quiqqer.editors.toolbar.add'
        );

        $toolbar = str_replace('.xml', '', $toolbar);

        $folder = self::getToolbarsPath();
        $file = $folder . $toolbar . '.xml';

        if (file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
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
    public static function saveToolbar(string $toolbar, string $xml)
    {
        QUI\Permissions\Permission::hasPermission(
            'quiqqer.editors.toolbar.save'
        );

        if (empty($xml)) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.lib.qui.editor.manager.toolbar.empty'
            ]);
        }

        $toolbar = str_replace('.xml', '', $toolbar);

        $folder = self::getToolbarsPath();
        $file = $folder . $toolbar . '.xml';

        if (!file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.qui.editor.manager.toolbar.exist'
                )
            );
        }

        // check the xml
        libxml_use_internal_errors(true);

        $Doc = new DOMDocument('1.0', 'utf-8');
        $Doc->loadXML($xml);

        $errors = libxml_get_errors();

        if (!empty($errors)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.qui.editor.manager.toolbar.xml.error',
                    ['error' => $errors[0]->message]
                )
            );
        }

        file_put_contents($file, $xml);

        QUI\Cache\Manager::clear('settings/editor/xml');
    }

    /**
     * Return the toolbar buttons for an user
     * Used the right user toolbar
     *
     * @return array
     */
    public static function getToolbarButtonsFromUser(): array
    {
        $Users = QUI::getUsers();
        $User = $Users->getUserBySession();

        if (!$Users->isAuth($User)) {
            return [];
        }

        // Benutzer spezifische Toolbar
        $toolbar = $User->getAttribute('toolbar');
        $toolbarPath = self::getToolbarsPath();

        if (!empty($toolbar)) {
            $toolbar = $toolbarPath . $User->getAttribute('toolbar');

            if (file_exists($toolbar)) {
                return self::parseXmlFileToArray($toolbar);
            }
        }

        // Gruppenspezifische Toolbar
        $groups = $User->getGroups();

        /* @var $Group QUI\Groups\Group */
        foreach ($groups as $Group) {
            $toolbar = $Group->getAttribute('toolbar');

            if (!empty($toolbar)) {
                $toolbar = $toolbarPath . $Group->getAttribute('toolbar');

                if (file_exists($toolbar)) {
                    return self::parseXmlFileToArray($toolbar);
                }
            }
        }

        try {
            $Config = self::getConf();
            $toolbar = $Config->get('toolbars', 'standard');
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());

            return [];
        }

        // standard
        if ($toolbar === false) {
            return [];
        }

        if (strpos($toolbar, '.xml') !== false) {
            if (file_exists($toolbarPath . $toolbar)) {
                return self::parseXmlFileToArray($toolbarPath . $toolbar);
            }
        }

        return explode(',', $Config->get('toolbars', 'standard'));
    }

    /**
     * Reads a toolbar xml and return and return it as array
     *
     * @param string $file - path to the file
     *
     * @return array
     */
    public static function parseXmlFileToArray(string $file): array
    {
        $cache = 'settings/editor/xml/' . md5($file);

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception) {
        }

        $Dom = XML::getDomFromXml($file);
        $toolbar = $Dom->getElementsByTagName('toolbar');

        if (!$toolbar->length) {
            return [];
        }

        $children = $toolbar->item(0)->childNodes;
        $result = [];

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
     * @param DOMNode $Node
     *
     * @return boolean|array
     */
    public static function parseXMLLineNode(DOMNode $Node)
    {
        if ($Node->nodeName != 'line') {
            return false;
        }

        $children = $Node->childNodes;
        $result = [];

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
     * @param DOMNode $Node
     *
     * @return boolean|array
     */
    public static function parseXMLGroupNode(DOMNode $Node)
    {
        if ($Node->nodeName != 'group') {
            return false;
        }

        $children = $Node->childNodes;
        $result = [];

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
                    'type' => 'button',
                    'button' => trim($Param->nodeValue)
                ];
            }
        }

        return $result;
    }

    /**
     * Load the html for an editor and clean it up
     *
     * @param string $html
     *
     * @return string
     */
    public function load(string $html): string
    {
        // Bilder umschreiben
        $html = preg_replace_callback(
            '#(src)="([^"]*)"#',
            [$this, "cleanAdminSrc"],
            $html
        );

        foreach ($this->plugins as $p) {
            if (method_exists($p, 'onLoad')) {
                $html = $p->onLoad($html);
            }
        }

        return $html;
    }

    /**
     * Clean up methods
     */

    /**
     * Prepare html for saving
     * Clean it up
     *
     * @param string $html
     *
     * @return string
     */
    public function prepareHTMLForSave(string $html): string
    {
        // Bilder umschreiben
        $html = preg_replace_callback(
            '#(src)="([^"]*)"#',
            [$this, "cleanSrc"],
            $html
        );

        $html = preg_replace_callback(
            '#(href)="([^"]*)"#',
            [$this, "cleanHref"],
            $html
        );

        foreach ($this->plugins as $p) {
            if (method_exists($p, 'onSave')) {
                $html = $p->onSave($html);
            }
        }

        $html = $this->cleanHTML($html);

        // Zeilenumbrüche in HTML löschen
        $html = preg_replace_callback(
            '#(<)(.*?)(>)#',
            [$this, "deleteLineBreaksInHtml"],
            $html
        );

        return $html;
    }

    /**
     * Cleanup HTML
     *
     * @param string $html
     *
     * @return string
     * @uses Tidy, if enabled
     *
     */
    public function cleanHTML(string $html)
    {
        $html = preg_replace('/<!--\[if gte mso.*?-->/s', '', $html);

        $search = [
            'font-family: Arial',
            'class="MsoNormal"'
        ];

        $html = str_ireplace($search, '', $html);

        if (class_exists('tidy')) {
            $Tidy = new Tidy();

            $config = [
                "char-encoding" => "utf8",
                'output-xhtml' => true,
                'indent-attributes' => false,
                'wrap' => 0,
                'word-2000' => 1,
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
     * Cleanup image src
     *
     * @param array $html
     *
     * @return string
     */
    public function cleanSrc(array $html): string
    {
        if (isset($html[2]) && strpos($html[2], 'image.php') !== false) {
            $html[2] = str_replace('&amp;', '&', $html[2]);
            $src_ = explode('image.php?', $html[2]);

            return ' ' . $html[1] . '="image.php?' . $src_[1] . '"';
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
    public function cleanHref(array $html): string
    {
        if (isset($html[2]) && strpos($html[2], 'index.php') !== false) {
            $index = explode('index.php?', $html[2]);

            return $html[1] . '="index.php?' . $index[1] . '"';
        }


        if (isset($html[2]) && strpos($html[2], 'image.php') !== false) {
            $index = explode('image.php?', $html[2]);

            return ' ' . $html[1] . '="image.php?' . $index[1] . '"';
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
    public function cleanAdminSrc(array $html): string
    {
        if (isset($html[2]) && strpos($html[2], 'image.php') !== false) {
            $src_ = explode('image.php?', $html[2]);

            return ' ' . $html[1] . '="' . URL_DIR . 'image.php?' . $src_[1] . '" ';
        }

        return $html[0];
    }

    /**
     * Delete line breaks in html content
     *
     * @param array $params
     *
     * @return string
     */
    protected function deleteLineBreaksInHtml(array $params): string
    {
        if (!isset($params[0])) {
            return $params[0];
        }

        return str_replace(
            ["\r\n", "\n", "\r"],
            "",
            $params[0]
        );
    }
}
