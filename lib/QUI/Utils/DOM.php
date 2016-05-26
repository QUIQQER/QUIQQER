<?php

/**
 * This file contains the \QUI\Utils\DOM
 */

namespace QUI\Utils;

use QUI;
use QUI\Projects\Site\Utils;
use QUI\Controls\Toolbar;
use QUI\Utils\Security\Orthos;

/**
 * QUIQQER DOM Helper
 *
 * QUI\Utils\DOM helps with quiqqer .xml files and DOMNode Elements
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class DOM
{
    /**
     * Converts an array into an QUI\QDOM object
     *
     * @param array $array
     *
     * @return QUI\QDOM
     */
    public static function arrayToQDOM(array $array)
    {
        $DOM = new QUI\QDOM();
        $DOM->setAttributes($array);

        return $DOM;
    }

    /**
     * Fügt DOM XML Tabs in eine Toolbar ein
     *
     * @param array|\DOMNodeList $tabs
     * @param QUI\Controls\Toolbar\Bar $Tabbar
     * @param                          $plugin - optional
     */
    public static function addTabsToToolbar($tabs, Toolbar\Bar $Tabbar, $plugin = '')
    {
        foreach ($tabs as $Tab) {
            /* @var $Tab \DOMElement */
            $text  = '';
            $image = '';
            $type  = '';

            $Images   = $Tab->getElementsByTagName('image');
            $Texts    = $Tab->getElementsByTagName('text');
            $Onload   = $Tab->getElementsByTagName('onload');
            $OnUnload = $Tab->getElementsByTagName('onunload');
            $Template = $Tab->getElementsByTagName('template');

            if ($Images && $Images->item(0)) {
                $image = self::parseVar($Images->item(0)->nodeValue);
            }

            if ($Texts && $Texts->item(0)) {
                $text = self::getTextFromNode($Texts->item(0));
            }

            if ($Tab->getAttribute('type')) {
                $type = $Tab->getAttribute('type');
            }

            $ToolbarTab = new Toolbar\Tab(array(
                'name'    => $Tab->getAttribute('name'),
                'text'    => $text,
                'image'   => $image,
                'plugin'  => $plugin,
                'wysiwyg' => $type == 'wysiwyg' ? true : false
            ));

            foreach ($Tab->attributes as $attr) {
                $name = $attr->nodeName;

                if ($name !== 'name' && $name !== 'text'
                    || $name !== 'image' && $name !== 'plugin'
                ) {
                    $ToolbarTab->setAttribute($name, $attr->nodeValue);
                }
            }

            if ($Onload && $Onload->item(0)) {
                $Element = $Onload->item(0);
                /* @var $Element \DOMElement */

                $ToolbarTab->setAttribute(
                    'onload',
                    $Onload->item(0)->nodeValue
                );

                $ToolbarTab->setAttribute(
                    'onload_require',
                    $Element->getAttribute('require')
                );
            }

            if ($OnUnload && $OnUnload->item(0)) {
                $Element = $Onload->item(0);
                /* @var $Element \DOMElement */

                $ToolbarTab->setAttribute(
                    'onunload',
                    $OnUnload->item(0)->nodeValue
                );

                $ToolbarTab->setAttribute(
                    'onunload_require',
                    $Element->getAttribute('require')
                );
            }

            if ($Template && $Template->item(0)) {
                $ToolbarTab->setAttribute(
                    'template',
                    $Template->item(0)->nodeValue
                );
            }

            $Tabbar->appendChild($ToolbarTab);
        }
    }

    /**
     * Button Element
     *
     * @param \DOMNode|\DOMElement $Button
     *
     * @return string
     */
    public static function buttonDomToString(\DOMNode $Button)
    {
        if ($Button->nodeName != 'button') {
            return '';
        }

        $text = '';
        $Text = $Button->getElementsByTagName('text');

        if ($Text->length) {
            $text = self::getTextFromNode($Text->item(0));
        }


        $string = '<p>';
        $string .= '<div class="btn-button" ';

        $string .= 'data-text="' . $text . '" ';
        $string .= 'data-click="' . $Button->getAttribute('onclick') . '" ';
        $string .= 'data-image="' . $Button->getAttribute('image') . '" ';

        $string .= '></div>';
        $string .= '</p>';

        return $string;
    }

    /**
     * Table Datenbank DOmNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode|\DOMElement $Table
     *
     * @return array
     */
    public static function dbTableDomToArray(\DOMNode $Table)
    {
        $result = array(
            'suffix'            => $Table->getAttribute('name'),
            'engine'            => $Table->getAttribute('engine'),
            'no-site-reference' => false,
            'no-project-lang'   => false,
            'no-auto-update'    => false,
            'site-types'        => false
        );

        if ((int)$Table->getAttribute('no-site-reference') === 1) {
            $result['no-site-reference'] = true;
        }

        if ((int)$Table->getAttribute('no-project-lang') === 1) {
            $result['no-project-lang'] = true;
        }

        if ((int)$Table->getAttribute('no-auto-update') === 1) {
            $result['no-auto-update'] = true;
        }

        if ($Table->getAttribute('site-types')) {
            $result['site-types'] = explode(
                ',',
                $Table->getAttribute('site-types')
            );
        }

        $_fields = array();

        // table fields
        $fields = $Table->getElementsByTagName('field');

        for ($i = 0; $i < $fields->length; $i++) {
            $_fields = array_merge(
                $_fields,
                self::dbFieldDomToArray($fields->item($i))
            );
        }

        // primary key
        $primary = $Table->getElementsByTagName('primary');

        for ($i = 0; $i < $primary->length; $i++) {
            $result = array_merge(
                $result,
                self::dbPrimaryDomToArray($primary->item($i))
            );
        }

        // unique
        $unique = $Table->getElementsByTagName('unique');

        for ($i = 0; $i < $unique->length; $i++) {
            $result = array_merge(
                $result,
                self::dbUniqueDomToArray($unique->item($i))
            );
        }

        // index
        $index = $Table->getElementsByTagName('index');

        for ($i = 0; $i < $index->length; $i++) {
            $result = array_merge(
                $result,
                self::dbIndexDomToArray($index->item($i))
            );
        }

        // auto increment
        $autoincrement = $Table->getElementsByTagName('auto_increment');

        for ($i = 0; $i < $autoincrement->length; $i++) {
            $result = array_merge(
                $result,
                self::dbAutoIncrementDomToArray($autoincrement->item($i))
            );
        }

        // fulltext
        $fulltext = $Table->getElementsByTagName('fulltext');

        for ($i = 0; $i < $fulltext->length; $i++) {
            $result = array_merge(
                $result,
                self::dbAutoFullextDomToArray($fulltext->item($i))
            );
        }


        $result['fields'] = $_fields;


        return $result;
    }

    /**
     * Field Datenbank DOmNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode|\DOMElement $Field
     *
     * @return array
     */
    public static function dbFieldDomToArray(\DOMNode $Field)
    {
        $str = '';
        $str .= $Field->getAttribute('type');

        if (empty($str)) {
            $str .= 'text';
        }

        if ($Field->getAttribute('length')) {
            $str .= '(' . $Field->getAttribute('length') . ')';
        }

        $str .= ' ';

        if ($Field->getAttribute('null') == 1) {
            $str .= 'NULL';
        } else {
            $structure = QUI\Utils\StringHelper::toLower(
                $Field->getAttribute('type')
            );

            // if NULL is not mentioned (neither "NULL" nor "NOT NULL") assume "NOT NULL"
            if (mb_strpos($structure, 'null') === false) {
                $str .= 'NOT NULL';
            }
        }

        return array(
            trim($Field->nodeValue) => $str
        );
    }

    /**
     * Primary Datenbank DOmNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Primary
     *
     * @return array
     */
    public static function dbPrimaryDomToArray(\DOMNode $Primary)
    {
        return array(
            'primary' => explode(',', $Primary->nodeValue)
        );
    }

    /**
     * Unique Datenbank DOmNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Unique
     *
     * @return array
     */
    public static function dbUniqueDomToArray(\DOMNode $Unique)
    {
        return array(
            'unique' => explode(',', $Unique->nodeValue)
        );
    }

    /**
     * Index Datenbank DOMNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Index
     *
     * @return array
     */
    public static function dbIndexDomToArray(\DOMNode $Index)
    {
        return array(
            'index' => trim($Index->nodeValue)
        );
    }

    /**
     * AUTO_INCREMENT Datenbank DOMNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $AI
     *
     * @return array
     */
    public static function dbAutoIncrementDomToArray(\DOMNode $AI)
    {
        return array(
            'auto_increment' => trim($AI->nodeValue)
        );
    }

    /**
     * FULLTEXT Datenbank DOMNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Fulltext
     *
     * @return array
     */
    public static function dbAutoFullextDomToArray(\DOMNode $Fulltext)
    {
        return array(
            'fulltext' => trim($Fulltext->nodeValue)
        );
    }

    /**
     * Return the tabs
     *
     * @param \DOMElement|\DOMNode $DOMNode
     * @return array
     */
    public static function getTabs(\DOMElement $DOMNode)
    {
        $tablist = $DOMNode->getElementsByTagName('tab');

        if (!$tablist->length) {
            return array();
        }

        $tabs = array();

        for ($c = 0; $c < $tablist->length; $c++) {
            $Tab = $tablist->item($c);

            if ($Tab->nodeName == '#text') {
                continue;
            }

            $tabs[] = $Tab;
        }

        return $tabs;
    }

    /**
     * HTML eines DOM Tabs
     *
     * @param string $name
     * @param QUI\Projects\Project|string|QUI\Projects\Site|QUI\Projects\Site\Edit
     *        $Object - string = path to user.xml File
     *
     * @return string
     */
    public static function getTabHTML($name, $Object)
    {
        $tabs = array();

        if (is_string($Object)) {
            if (file_exists($Object)) {
                $tabs = XML::getTabsFromXml($Object);
            }

        } else {
            if (get_class($Object) === 'QUI\\Projects\\Project') {
                /* @var $Object QUI\Projects\Project */
                // tabs welche ein projekt zur Verfügung stellt
                $tabs = XML::getTabsFromXml(
                    USR_DIR . 'lib/' . $Object->getAttribute('name') . '/user.xml'
                );

            } else {
                if (get_class($Object) === 'QUI\\Projects\\Site'
                    || get_class($Object) === 'QUI\\Projects\\Site\\Edit'
                ) {
                    /* @var $Object QUI\Projects\Site */
                    $Tabbar = QUI\Projects\Sites::getTabs($Object);
                    $Tab    = $Tabbar->getElementByName($name);

                    if ($Tab->getAttribute('template')) {
                        $file = self::parseVar($Tab->getAttribute('template'));

                        if (file_exists($file)) {
                            // site extra settings
                            $extra = '';

                            if ($file == SYS_DIR . 'template/site/settings.html') {
                                $extra = Utils::getExtraSettingsForSite($Object);
                            }

                            // generate html
                            $Engine = QUI::getTemplateManager()
                                ->getEngine(true);

                            $Engine->assign(array(
                                'Site'    => $Object,
                                'Project' => $Object->getProject(),
                                'Plugins' => QUI::getPluginManager(),
                                'QUI'     => new QUI()
                            ));

                            return $Engine->fetch($file) . $extra;
                        }
                    }

                    return '';
                }
            }
        }

        $str = '';

        foreach ($tabs as $Tab) {
            /* @var $Tab \DOMElement */
            if ($Tab->getAttribute('name') != $name) {
                continue;
            }

            $str .= self::parseCategorieToHTML($Tab);
        }

        return $str;
    }

    /**
     * Return the buttons from <categories>
     *
     * @param \DomDocument|\DomElement $Dom
     *
     * @return array
     */
    public static function getButtonsFromWindow($Dom)
    {
        $btnlist = $Dom->getElementsByTagName('categories');

        if (!$btnlist->length) {
            return array();
        }

        $result   = array();
        $children = $btnlist->item(0)->childNodes;

        for ($i = 0; $i < $children->length; $i++) {
            /* @var $Param \DOMElement */
            $Param = $children->item($i);

            if ($Param->nodeName != 'category') {
                continue;
            }

            $index = $Param->getAttribute('index');

            if (!$index) {
                $index = 1;
            }

            $Button = new QUI\Controls\Buttons\Button();
            $Button->setAttribute('name', $Param->getAttribute('name'));
            $Button->setAttribute('require', $Param->getAttribute('require'));
            $Button->setAttribute('index', $index);

//            $onload   = $Param->getElementsByTagName( 'onload' );
//            $onunload = $Param->getElementsByTagName( 'onunload' );

            $btnParams = $Param->childNodes;

            for ($b = 0; $b < $btnParams->length; $b++) {
                switch ($btnParams->item($b)->nodeName) {
                    case 'text':
                    case 'title':
                        $Button->setAttribute(
                            $btnParams->item($b)->nodeName,
                            self::getTextFromNode($btnParams->item($b))
                        );
                        break;

                    case 'onclick':
                        $Button->setAttribute(
                            $btnParams->item($b)->nodeName,
                            $btnParams->item($b)->nodeValue
                        );
                        break;

                    case 'icon':
                        $value = $btnParams->item($b)->nodeValue;

                        $Button->setAttribute(
                            $btnParams->item($b)->nodeName,
                            self::parseVar($value)
                        );
                        break;
                }
            }

            if ($Param->getAttribute('type') == 'projects') {
                $projects = QUI\Projects\Manager::getProjects();

                foreach ($projects as $project) {
                    $Button->setAttribute(
                        'text',
                        str_replace('{$project}', $project, $Button->getAttribute('text'))
                    );

                    $Button->setAttribute(
                        'title',
                        str_replace('{$project}', $project, $Button->getAttribute('title'))
                    );

                    $Button->setAttribute('section', $project);

                    $result[] = $Button;
                }

                continue;
            }

            $result[] = $Button;
        }

        return $result;
    }

    /**
     * Search a <locale> node into the DOMNode and parse it
     * if no <locale exist, it return the nodeValue
     *
     * @param \DOMNode|\DOMElement $Node
     * @param boolean $translate - direct translation? default = true
     *
     * @return string|array
     */
    public static function getTextFromNode(\DOMNode $Node, $translate = true)
    {
        $loc = $Node->getElementsByTagName('locale');

        if (!$loc->length) {
            return self::parseVar(trim($Node->nodeValue));
        }

        /* @var $Element \DOMElement */
        $Element = $loc->item(0);

        if ($translate === false) {
            return array(
                $Element->getAttribute('group'),
                $Element->getAttribute('var')
            );
        }

        return QUI::getLocale()->get(
            $Element->getAttribute('group'),
            $Element->getAttribute('var')
        );
    }

    /**
     * Return all //wysiwyg/styles/style elements
     *
     * @param \DOMDocument $Dom
     * @param boolean $translate
     *
     * @return array
     */
    public static function getWysiwygStyles(\DOMDocument $Dom, $translate = true)
    {
        $Path   = new \DOMXPath($Dom);
        $Styles = $Path->query("//wysiwyg/styles/style");

        if (!$Styles->length) {
            return array();
        }

        $result = array();

        /* @var $Style \DOMElement */
        foreach ($Styles as $Style) {
            $attributeList = array();
            $attributes    = $Style->getElementsByTagName('attribute');

            /* @var $Attribute \DOMElement */
            foreach ($attributes as $Attribute) {
                $attributeList[$Attribute->getAttribute('name')]
                    = trim($Attribute->nodeValue);
            }

            $result[] = array(
                'text'       => self::getTextFromNode($Style, $translate),
                'element'    => $Style->getAttribute('element'),
                'attributes' => $attributeList
            );
        }

        return $result;
    }

    /**
     * Wandelt <group> in einen string für die Einstellung um
     *
     * @param \DOMNode|\DOMElement $Group
     *
     * @return string
     */
    public static function groupDomToString(\DOMNode $Group)
    {
        if ($Group->nodeName != 'group') {
            return '';
        }

        $string = '<p>';
        $string .= '<div class="btn-groups" name="' . $Group->getAttribute('conf')
                   . '"></div>';

        $text = $Group->getElementsByTagName('text');

        if ($text->length) {
            $string .= '<span>' .
                       self::getTextFromNode($text->item(0)) .
                       '</span>';
        }

        $desc = $Group->getElementsByTagName('description');

        if ($desc->length) {
            $string .= '<div class="description">' .
                       self::getTextFromNode($desc->item(0)) .
                       '</div>';
        }

        $string .= '</p>';

        return $string;
    }

    /**
     * Returns the string between <body> and </body>
     *
     * @param string $html
     *
     * @return string
     */
    public static function getInnerBodyFromHTML($html)
    {
        return preg_replace('/(.*)<body>(.*)<\/body>(.*)/si', '$2', $html);
    }

    /**
     * Returns the innerHTML from a PHP DOMNode
     * Equivalent to the JavaScript innerHTML property
     *
     * @param \DOMNode $Node
     *
     * @return string
     */
    public static function getInnerHTML(\DOMNode $Node)
    {
        $Dom      = new \DOMDocument();
        $Children = $Node->childNodes;

        foreach ($Children as $Child) {
            $Dom->appendChild($Dom->importNode($Child, true));
        }

        return $Dom->saveHTML();
    }

    /**
     * Return the config parameter from an DOMNode Element
     *
     * @param \DOMDocument|\DOMNode $Dom
     *
     * @return array
     */
    public static function getConfigParamsFromDOM($Dom)
    {
        $Settings = $Dom;

        if ($Dom->nodeName != 'settings') {
            $settings = $Dom->getElementsByTagName('settings');
            $Settings = $settings->item(0);

            if (!$settings->length) {
                return array();
            }
        }

        $configs = $Settings->getElementsByTagName('config');

        if (!$configs->length) {
            return array();
        }

        $projects = QUI\Projects\Manager::getProjects();
        $children = $configs->item(0)->childNodes;
        $result   = array();

        for ($i = 0; $i < $children->length; $i++) {
            /* @var $Param \DOMElement */
            $Param = $children->item($i);

            if ($Param->nodeName == '#text') {
                continue;
            }

            if ($Param->nodeName == 'section') {
                $name  = $Param->getAttribute('name');
                $confs = $Param->getElementsByTagName('conf');

                if ($Param->getAttribute('type') == 'project') {
                    foreach ($projects as $project) {
                        $result[$project] = self::parseConfs($confs);
                    }

                    continue;
                }

                $result[$name] = self::parseConfs($confs);
            }
        }

        return $result;
    }

    /**
     * Parse a DOMDocument to a settings window
     * if a settings window exist in it
     *
     * @param \DomDocument|\DOMElement $Dom
     *
     * @return QUI\Controls\Windows\Window|bool
     */
    public static function parseDomToWindow($Dom)
    {
        $settings = $Dom->getElementsByTagName('settings');

        if (!$settings->length) {
            return false;
        }

        /* @var $Settings \DOMElement */
        $Settings = $settings->item(0);
        $winlist  = $Settings->getElementsByTagName('window');

        if (!$winlist->length) {
            return false;
        }

        /* @var $Window \DOMElement */
        $Window = $winlist->item(0);
        $Win    = new QUI\Controls\Windows\Window();

        // name
        if ($Window->getAttribute('name')) {
            $Win->setAttribute('name', $Window->getAttribute('name'));
        }

        // titel
        $titles = $Settings->getElementsByTagName('title');

        if ($titles->item(0)) {
            $Win->setAttribute(
                'title',
                self::getTextFromNode($titles->item(0))
            );
        }

        // window parameter
        $params = $Window->getElementsByTagName('params');

        if ($params->item(0)) {
            /* @var $Element \DOMElement */
            $Element = $params->item(0);
            $icon    = $Element->getElementsByTagName('icon');

            if ($Element) {
                $Win->setAttribute(
                    'icon',
                    self::parseVar($icon->item(0)->nodeValue)
                );
            }
        }

        // Window buttons
        $btnList = self::getButtonsFromWindow($Window);

        foreach ($btnList as $Button) {
            $Win->appendCategory($Button);
        }

        return $Win;
    }

    /**
     *
     * @param \DOMNode|\DOMElement $Node
     *
     * @return array
     */
    public static function parsePanelToArray(\DOMNode $Node)
    {
        if ($Node->nodeName != 'panel') {
            return array();
        }

        $require = $Node->getAttribute('require');
        $Titles  = $Node->getElementsByTagName('title');
        $Texts   = $Node->getElementsByTagName('text');
        $Images  = $Node->getElementsByTagName('image');

        $image = '';
        $title = '';
        $text  = '';

        if ($Titles && $Titles->length) {
            $title = self::getTextFromNode($Titles->item(0));
        }

        if ($Texts && $Texts->length) {
            $text = self::getTextFromNode($Texts->item(0));
        }

        if ($Images && $Images->item(0)) {
            $image = self::parseVar($Images->item(0)->nodeValue);
        }

        return array(
            'image'   => $image,
            'title'   => $title,
            'text'    => $text,
            'require' => $require
        );
    }

    /**
     * Parse a DOMNode permission to an array
     *
     * @param \DOMNode|\DOMElement $Node
     *
     * @return array
     */
    public static function parsePermissionToArray(\DOMNode $Node)
    {
        if ($Node->nodeName != 'permission') {
            return array();
        }

        $perm    = $Node->getAttribute('name');
        $default = false;

        $Default = $Node->getElementsByTagName('defaultvalue');

        if ($Default && $Default->length) {
            $default = $Default->item(0)->nodeValue;
        }

        $type = QUI\Permissions\Manager::parseType($Node->getAttribute('type'));
        $area = QUI\Permissions\Manager::parseArea($Node->getAttribute('area'));

        return array(
            'name'    => $perm,
            'area'    => $area,
            'type'    => $type,
            'default' => $default
        );
    }

    /**
     * Wandelt ein Kategorie DomNode in entsprechendes HTML um
     *
     * @param \DOMNode $Category
     * @param          $Plugin - optional
     *
     * @return string
     */
    public static function parseCategorieToHTML($Category, $Plugin = false)
    {
        if (is_bool($Category)) {
            return '';
        }

        $children = $Category->childNodes;

        if (!$children->length) {
            return '';
        }

        $Engine = QUI::getTemplateManager()->getEngine(true);
        $result = '';
        $odd    = 'odd';
        $even   = 'even';

        for ($c = 0; $c < $children->length; $c++) {
            /* @var $Entry \DOMElement */
            $Entry = $children->item($c);

            if ($Entry->nodeName == '#text' || $Entry->nodeName == 'text'
                || $Entry->nodeName == 'image'
            ) {
                continue;
            }

            if ($Entry->nodeName == 'template') {
                $file = self::parseVar($Entry->nodeValue);

                if (file_exists($file)) {
                    $Engine->assign(array(
                        'Plugin'  => $Plugin,
                        'Plugins' => QUI::getPluginManager(),
                        'QUI'     => new QUI()
                    ));

                    $result .= $Engine->fetch($file);
                }

                continue;
            }

            if ($Entry->nodeName == 'title') {
                $result .= '<table class="data-table"><thead><tr><th>';
                $result .= self::getTextFromNode($Entry);
                $result .= '</th></tr></thead></table>';

                continue;
            }

            if ($Entry->nodeName == 'settings') {
                $row      = 0;
                $settings = $Entry->childNodes;

                $result .= '<table class="data-table">';

                // title
                $titles = $Entry->getElementsByTagName('title');

                if ($titles->length) {
                    $result .= '<thead><tr><th>';
                    $result .= self::getTextFromNode($titles->item(0));
                    $result .= '</th></tr></thead>';
                }

                $result .= '<tbody>';

                // entries
                for ($s = 0; $s < $settings->length; $s++) {
                    $Set = $settings->item($s);

                    if ($Set->nodeName == '#text'
                        || $Set->nodeName == '#comment'
                        || $Set->nodeName == 'title'
                    ) {
                        continue;
                    }

                    $result .= '<tr class="' . ($row % 2 ? $even : $odd) . ' qui-xml-panel-row"><td>';

                    switch ($Set->nodeName) {
                        case 'text':
                            $result .= '<div>' . self::getTextFromNode($Set) . '</div>';
                            break;

                        case 'input':
                            $result .= self::inputDomToString($Set);
                            break;

                        case 'select':
                            $result .= self::selectDomToString($Set);
                            break;

                        case 'textarea':
                            $result .= self::textareaDomToString($Set);
                            break;

                        case 'group':
                            $result .= self::groupDomToString($Set);
                            break;

                        case 'button':
                            $result .= self::buttonDomToString($Set);
                            break;

                        case 'template':
                            $file = self::parseVar($Set->nodeValue);

                            if (file_exists($file)) {
                                $Engine->assign(array(
                                    'Plugin'  => $Plugin,
                                    'Plugins' => QUI::getPluginManager(),
                                    'QUI'     => new QUI()
                                ));

                                $result .= $Engine->fetch($file);
                            }
                            break;
                    }

                    $result .= '</td></tr>';
                    $row++;
                }

                $result .= '</tbody></table>';
                continue;
            }

            if ($Entry->nodeName == 'input') {
                $result .= self::inputDomToString($Entry);
                continue;
            }

            if ($Entry->nodeName == 'select') {
                $result .= self::selectDomToString($Entry);
                continue;
            }

            if ($Entry->nodeName == 'textarea') {
                $result .= self::textareaDomToString($Entry);
                continue;
            }

            if ($Entry->nodeName == 'group') {
                $result .= self::groupDomToString($Entry);
                continue;
            }

            if ($Entry->nodeName == 'button') {
                $result .= self::buttonDomToString($Entry);
                continue;
            }
        }

        if (!empty($result)) {
            $result .= '</table>';
        }

        return $result;
    }

    /**
     * Eingabe Element Input in einen string für die Einstellung umwandeln
     *
     * @param \DOMNode|\DOMElement $Input
     *
     * @return string
     */
    public static function inputDomToString(\DOMNode $Input)
    {
        if ($Input->nodeName != 'input') {
            return '';
        }

        $type    = 'text';
        $class   = '';
        $dataQui = '';
        $data    = '';

        if ($Input->getAttribute('type')) {
            $type = $Input->getAttribute('type');
        }

        $attributes = $Input->attributes;

        foreach ($attributes as $Attribute) {
            /* @var $Attribute \DOMAttr */
            $name  = htmlspecialchars($Attribute->name);
            $value = htmlspecialchars($Attribute->value);

            if (strpos($name, 'data-') !== false) {
                $data .= " {$name}=\"{$value}\"";
                continue;
            }

            switch ($name) {
                case 'title':
                case 'placeholder':
                    $data .= " {$name}=\"{$value}\"";
                    break;
            }
        }

        if ($Input->getAttribute('class')) {
            $class = ' class="' . $Input->getAttribute('class') . '"';
        }

        switch ($type) {
            case 'group':
            case 'groups':
            case 'user':
            case 'users':
                $class = ' class="' . $type . '"';
                $type  = 'text';
                break;
        }


        $id = $Input->getAttribute('conf') . '-' . time();

        $string = '<div class="qui-xml-panel-row-item">';
        $text   = $Input->getElementsByTagName('text');
        $input  = '<input type="' . $type . '"
                           name="' . $Input->getAttribute('conf') . '"
                           id="' . $id . '"
                           ' . $class . '
                           ' . $dataQui . '
                           ' . $data . '
                    />';

        if ($type == 'checkbox' || $type == 'radio') {
            if ($text->length) {
                $string .= '<label for="' . $id . '" class="checkbox-label">' .
                           $input .
                           self::getTextFromNode($text->item(0)) .
                           '</label>';
            } else {
                $string .= $input;
            }

        } else {
            if ($text->length) {
                $string .= '<label for="' . $id . '">' .
                           self::getTextFromNode($text->item(0)) .
                           '</label>';
            }

            $string .= $input;
        }

        $desc = $Input->getElementsByTagName('description');

        $string .= '</div>';

        if ($desc->length) {
            $string .= '<div class="description qui-xml-panel-row-item">' .
                       self::getTextFromNode($desc->item(0)) .
                       '</div>';
        }

        return $string;
    }

    /**
     * Eingabe Element Textarea in einen string für die Einstellung umwandeln
     *
     * @param \DOMNode|\DOMElement $Textarea
     *
     * @return string
     */
    public static function textareaDomToString(\DOMNode $Textarea)
    {
        if ($Textarea->nodeName != 'textarea') {
            return '';
        }

        $id   = $Textarea->getAttribute('conf') . '-' . time();
        $text = $Textarea->getElementsByTagName('text');

        $dataQui = '';

        if ($Textarea->getAttribute('data-qui')) {
            $dataQui = ' data-qui="' . $Textarea->getAttribute('data-qui') . '"';
        }

        $textarea
            = '<textarea
            name="' . $Textarea->getAttribute('conf') . '"
            id="' . $id . '"
            ' . $dataQui . '
        ></textarea>';


        $string = '<p>';

        if ($text->length) {
            $string .= '<label for="' . $id . '">' .
                       self::getTextFromNode($text->item(0)) .
                       '</label>';
        }

        $string .= $textarea;
        $string .= '</p>';

        return $string;
    }

    /**
     * Parse config entries to an array
     *
     * @param \DOMNode|\DOMNodeList $confs
     *
     * @return array
     */
    public static function parseConfs($confs)
    {
        $result = array();

        foreach ($confs as $Conf) {
            /* @var $Conf \DOMElement */
            $type    = 'string';
            $default = '';

            $types    = $Conf->getElementsByTagName('type');
            $defaults = $Conf->getElementsByTagName('defaultvalue');

            // type
            if ($types && $types->length) {
                $type = $types->item(0)->nodeValue;
            }

            // default
            if ($defaults && $defaults->length) {
                $default = self::parseVar(
                    $defaults->item(0)->nodeValue
                );
            }

            $result[$Conf->getAttribute('name')] = array(
                'type'    => $type,
                'default' => $default
            );
        }

        return $result;
    }

    /**
     * Ersetzt Variablen im XML
     *
     * @param string $value
     *
     * @return string
     */
    public static function parseVar($value)
    {
        $value = trim($value);

        $value = str_replace(
            array(
                'URL_BIN_DIR',
                'URL_OPT_DIR',
                'URL_USR_DIR',
                'BIN_DIR',
                'OPT_DIR',
                'URL_DIR',
                'SYS_DIR',
                'CMS_DIR',
                'USR_DIR'
            ),
            array(
                URL_BIN_DIR,
                URL_OPT_DIR,
                URL_USR_DIR,
                BIN_DIR,
                OPT_DIR,
                URL_DIR,
                SYS_DIR,
                CMS_DIR,
                USR_DIR
            ),
            $value
        );

        $value = StringHelper::replaceDblSlashes($value);

        return $value;
    }

    /**
     * Eingabe Element Select in einen string für die Einstellung umwandeln
     *
     * @param \DOMNode|\DOMElement $Select
     *
     * @return string
     */
    public static function selectDomToString(\DOMNode $Select)
    {
        if ($Select->nodeName != 'select') {
            return '';
        }

        $id      = $Select->getAttribute('conf') . '-' . time();
        $dataQui = '';

        if ($Select->getAttribute('data-qui')) {
            $dataQui = ' data-qui="' . $Select->getAttribute('data-qui') . '"';
        }

        $select
            = '<select
            name="' . $Select->getAttribute('conf') . '"
            id="' . $id . '"
            ' . $dataQui . '
        >';

        // Options
        $options = $Select->getElementsByTagName('option');

        foreach ($options as $Option) {
            /* @var $Option \DOMElement */
            $value = $Option->getAttribute('value');
            $html  = self::getTextFromNode($Option);

            $select .= '<option value="' . $value . '">' . $html . '</option>';
        }

        $select .= '</select>';


        $text   = $Select->getElementsByTagName('text');
        $result = '<p>';

        if ($text->length) {
            $result .= '<label for="' . $id . '">' .
                       self::getTextFromNode($text->item(0)) .
                       '</label>';
        }

        $result .= $select;
        $result .= '</p>';

        return $result;
    }
}
