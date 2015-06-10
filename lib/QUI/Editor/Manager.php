<?php

/**
 * This file contains \QUI\Editor\Manager
 */

namespace QUI\Editor;

use QUI;
use QUI\Utils\Security\Orthos;
use QUI\Utils\System\File as QUIFile;

/**
 * Wysiwyg manager
 *
 * manages all wysiwyg editors and the settings for them
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package quiqqer/quiqqer
 * @licence For copyright and license information, please view the /README.md
 *
 * @todo    docu translation
 */
class Manager
{
    /**
     * WYSIWYG editor config
     *
     * @var \QUI\Config
     */
    static $Config = null;

    /**
     * Editor plugins
     *
     * @var array
     */
    protected $_plugins = array();

    /**
     * Setup
     */
    static function setup()
    {
        QUIFile::mkdir(self::getToolbarsPath());

        if (!file_exists(CMS_DIR.'etc/wysiwyg/conf.ini.php')) {
            file_put_contents(CMS_DIR.'etc/wysiwyg/conf.ini.php', '');
        }

        if (!file_exists(CMS_DIR.'etc/wysiwyg/editors.ini.php')) {
            file_put_contents(CMS_DIR.'etc/wysiwyg/editors.ini.php', '');
        }
    }

    /**
     * Pfad zu den XML Dateien
     *
     * @return String
     */
    static function getPath()
    {
        return CMS_DIR.'etc/wysiwyg/';
    }

    /**
     * Return the path to the toolbars
     *
     * @return String
     */
    static function getToolbarsPath()
    {
        return self::getPath().'toolbars/';
    }

    /**
     * Return the main editor manager (wyiswyg) config object
     *
     * @return QUI\Config
     */
    static function getConf()
    {
        if (!self::$Config) {
            self::$Config = \QUI::getConfig('etc/wysiwyg/conf.ini.php');
        }

        return self::$Config;
    }

    /**
     * Return all settings of the manager
     *
     * @return Array
     */
    static function getConfig()
    {
        $config = self::getConf()->toArray();
        $config['toolbars'] = self::getToolbars();

        $config['editors'] = \QUI::getConfig('etc/wysiwyg/editors.ini.php')
                                 ->toArray();

        return $config;
    }

    /**
     * Register a js editor
     *
     * @param String $name    - name of the editor
     * @param String $package - js modul/package name
     */
    static function registerEditor($name, $package)
    {
        $Conf = \QUI::getConfig('etc/wysiwyg/editors.ini.php');
        $Conf->setValue($name, null, $package);
        $Conf->save();
    }

    /**
     * Bereitet HTML für den Editor
     * URL bei Bildern richtig setzen damit diese im Admin angezeigt werden
     *
     * @param String $html
     *
     * @return String
     */
    public function load($html)
    {
        // Bilder umschreiben
        $html = preg_replace_callback(
            '#(src)="([^"]*)"#',
            array($this, "cleanAdminSrc"),
            $html
        );

        foreach ($this->_plugins as $p) {
            if (method_exists($p, 'onLoad')) {
                $html = $p->onLoad($html);
            }
        }

        return $html;
    }

    /**
     * Alle Toolbars bekommen, welche zur Verfügung stehen
     *
     * @return array
     */
    static function getToolbars()
    {
        $folder = self::getToolbarsPath();
        $files = QUIFile::readDir($folder, true);

        return $files;
    }

    /**
     * Return the Editor Settings for a specific Project
     *
     * @param QUI\Projects\Project $Project
     *
     * @return Array
     */
    static function getSettings(QUI\Projects\Project $Project)
    {
        $project = $Project->getName();

        // css files
        $css = array();
        $styles = array();
        $file = USR_DIR.$Project->getName().'/settings.xml';

        $bodyId = false;
        $bodyClass = false;

        // project files
        if (file_exists($file)) {
            $files = QUI\Utils\XML::getWysiwygCSSFromXml($file);

            foreach ($files as $cssfile) {
                $css[] = URL_USR_DIR.$project.'/'.$cssfile;
            }

            // id and css class
            $Dom = QUI\Utils\XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

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
        $templates = array();

        if ($Project->getAttribute('template')) {
            $templates[]
                = OPT_DIR.$Project->getAttribute('template').'/settings.xml';
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

            $file = OPT_DIR.$data['template'].'/settings.xml';

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
                $cssFiles = QUI\Utils\XML::getWysiwygCSSFromXml($file);

                foreach ($cssFiles as $cssFile) {
                    // external file
                    if (strpos($cssFile, '//') === 0
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
                $Dom = QUI\Utils\XML::getDomFromXml($file);
                $Path = new \DOMXPath($Dom);

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

        $result = array(
            'cssFiles'  => $css,
            'bodyId'    => $bodyId,
            'bodyClass' => $bodyClass,
            'styles'    => $styles
        );

        return $result;
    }


    /**
     * Return the available styles
     *
     * @param \QUI\Projects\Project|Bool $Project - (optional)
     *
     * @return array
     */
    static function getStyles($Project = false)
    {
        $styles = array();

        if ($Project) {

        }

        return $styles;
    }

    /**
     * Delete a toolbar
     *
     * @param String $toolbar - Name of the tools (toolbar.xml)
     */
    static function deleteToolbar($toolbar)
    {
        QUI\Rights\Permission::hasPermission(
            'quiqqer.editors.toolbar.delete'
        );

        $folder = self::getToolbarsPath();
        $path = $folder.$toolbar;

        $path = Orthos::clearPath($path);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Add a new toolbar
     *
     * @param String $toolbar - Name of the tools (myNewToolbar)
     *
     * @throws QUI\Exception
     */
    static function addToolbar($toolbar)
    {
        QUI\Rights\Permission::hasPermission(
            'quiqqer.editors.toolbar.add'
        );

        $toolbar = str_replace('.xml', '', $toolbar);

        $folder = self::getToolbarsPath();
        $file = $folder.$toolbar.'.xml';

        if (file_exists($file)) {
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
     * @param String $toolbar - toolbar name
     * @param String $xml     - toolbar xml
     *
     * @throws QUI\Exception
     */
    static function saveToolbar($toolbar, $xml)
    {
        QUI\Rights\Permission::hasPermission(
            'quiqqer.editors.toolbar.save'
        );

        $toolbar = str_replace('.xml', '', $toolbar);

        $folder = self::getToolbarsPath();
        $file = $folder.$toolbar.'.xml';

        if (!file_exists($file)) {
            throw new QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.editor.manager.toolbar.exist'
                )
            );
        }

        // check the xml
        libxml_use_internal_errors(true);

        $Doc = new \DOMDocument('1.0', 'utf-8');
        $Doc->loadXML($xml);

        $errors = libxml_get_errors();

        if (!empty($errors)) {
            throw new QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.editor.manager.toolbar.xml.error',
                    array('error' => $errors[0]->message)
                )
            );
        }

        file_put_contents($file, $xml);
    }

    /**
     * Buttonliste vom aktuellen Benutzer bekommen
     *
     * @return Array
     */
    static function getToolbarButtonsFromUser()
    {
        // Erste Benutzer spezifische Toolbar
        $Users = \QUI::getUsers();
        $User = $Users->getUserBySession();

        $toolbar = $User->getAttribute('wysiwyg-toolbar');
        $toolbarPath = self::getToolbarsPath();

        if (!empty($toolbar)) {
            $toolbar = $toolbarPath.$User->getAttribute('wysiwyg-toolbar');

            if (file_exists($toolbar)) {
                return self::parseXmlFileToArray($toolbar);
            }
        }

        // Dann Gruppenspezifische Toolbar
        $groups = $User->getGroups();

        if ($groups) {
            $Group = end($groups);
            $toolbar = $Group->getAttribute('toolbar');

            if (!empty($toolbar)) {
                $toolbar = $toolbarPath.$Group->getAttribute('toolbar');

                if (file_exists($toolbar)) {
                    return self::parseXmlFileToArray($toolbar);
                }
            }
        }

        $Config = self::getConf();
        $toolbar = $Config->get('toolbars', 'standard');

        // standard
        if ($toolbar === false) {
            return array();
        }

        if (strpos($toolbar, '.xml') !== false) {
            if (file_exists($toolbarPath.$toolbar)) {
                return self::parseXmlFileToArray($toolbarPath.$toolbar);
            }
        }

        return explode(',', $Config->get('toolbars', 'standard'));
    }

    /**
     * Toolbar auslesen
     *
     * @param String $file - path to the file
     *
     * @return array
     */
    static function parseXmlFileToArray($file)
    {
        $cache = 'editor/xml/file/'.md5($file);

        try {
            return QUI\Cache\Manager::get($cache);

        } catch (QUI\Exception $Exception) {

        }

        $Dom = QUI\Utils\XML::getDomFromXml($file);
        $toolbar = $Dom->getElementsByTagName('toolbar');

        if (!$toolbar->length) {
            return array();
        }

        $children = $toolbar->item(0)->childNodes;
        $result = array();

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
    static function parseXMLLineNode($Node)
    {
        if ($Node->nodeName != 'line') {
            return false;
        }

        $children = $Node->childNodes;
        $result = array();

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
    static function parseXMLGroupNode($Node)
    {
        if ($Node->nodeName != 'group') {
            return false;
        }

        $children = $Node->childNodes;
        $result = array();

        for ($i = 0; $i < $children->length; $i++) {
            $Param = $children->item($i);

            if ($Param->nodeName == 'seperator') {
                $result[] = array(
                    'type' => 'seperator'
                );

                continue;
            }

            if ($Param->nodeName == 'button') {
                $result[] = array(
                    'type'   => 'button',
                    'button' => trim($Param->nodeValue)
                );
            }
        }

        return $result;
    }

    /**
     * Clean up methods
     */

    /**
     * Cleanup HTML - Saubermachen des HTML Codes
     *
     * @uses Tidy, if enabled
     *
     * @param String $html
     *
     * @return String
     */
    public function cleanHTML($html)
    {
        $html = preg_replace('/<!--\[if gte mso.*?-->/s', '', $html);

        $search = array(
            'font-family: Arial',
            'class="MsoNormal"'
        );

        $html = str_ireplace($search, '', $html);

        if (class_exists('tidy')) {
            $Tidy = new \Tidy();

            $config = array(
                "char-encoding"       => "utf8",
                'output-xhtml'        => true,
                'indent-attributes'   => false,
                'wrap'                => 0,
                'word-2000'           => 1,
                // html 5 Tags registrieren
                'new-blocklevel-tags' => 'header, footer, article, section, hgroup, nav, figure'
            );

            $Tidy->parseString($html, $config, 'utf8');
            $Tidy->cleanRepair();
            $html = $Tidy;
        }

        return $html;
    }

    /**
     * HTML Speichern
     *
     * @param String $html
     *
     * @return String
     */
    public function prepareHTMLForSave($html)
    {
        // Bilder umschreiben
        $html = preg_replace_callback(
            '#(src)="([^"]*)"#',
            array($this, "cleanSrc"),
            $html
        );

        $html = preg_replace_callback(
            '#(href)="([^"]*)"#',
            array($this, "cleanHref"),
            $html
        );

        foreach ($this->_plugins as $p) {
            if (method_exists($p, 'onSave')) {
                $html = $p->onSave($html);
            }
        }

        $html = $this->cleanHTML($html);

        // Zeilenumbrüche in HTML löschen
        $html = preg_replace_callback(
            '#(<)(.*?)(>)#',
            array($this, "_deleteLineBreaksInHtml"),
            $html
        );

        return $html;
    }

    /**
     * Entfernt Zeilenumbrüche in HTML
     *
     * @param Array $params
     *
     * @return String
     */
    protected function _deleteLineBreaksInHtml($params)
    {
        if (!isset($params[0])) {
            return $params[0];
        }

        return str_replace(
            array("\r\n", "\n", "\r"),
            "",
            $params[0]
        );
    }

    /**
     * Image Src sauber machen
     *
     * @param Array $html
     *
     * @return String
     */
    public function cleanSrc($html)
    {
        if (isset($html[2]) && strpos($html[2], 'image.php') !== false) {
            $html[2] = str_replace('&amp;', '&', $html[2]);
            $src_ = explode('image.php?', $html[2]);

            return ' '.$html[1].'="image.php?'.$src_[1].'"';
        }

        return $html[0];
    }

    /**
     * HREF Src sauber machen
     *
     * @param Array $html
     *
     * @return String
     */
    public function cleanHref($html)
    {
        if (isset($html[2]) && strpos($html[2], 'index.php') !== false) {
            $index = explode('index.php?', $html[2]);

            return $html[1].'="index.php?'.$index[1].'"';
        }


        if (isset($html[2]) && strpos($html[2], 'image.php') !== false) {
            $index = explode('image.php?', $html[2]);

            return ' '.$html[1].'="image.php?'.$index[1].'"';
        }

        return $html[0];
    }

    /**
     * Bereitet HTML für den Editor
     *
     * @param Array $html
     *
     * @return Array
     */
    public function cleanAdminSrc($html)
    {
        if (isset($html[2]) && strpos($html[2], 'image.php') !== false) {
            $src_ = explode('image.php?', $html[2]);

            return ' '.$html[1].'="'.URL_DIR.'image.php?'.$src_[1].'" ';
        }

        return $html[0];
    }
}
