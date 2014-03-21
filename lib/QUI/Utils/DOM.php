<?php

/**
 * This file contains the \QUI\Utils\DOM
 */

namespace QUI\Utils;

/**
 * QUIQQER DOM Helper
 *
 * \QUI\Utils\DOM helps with quiqqer .xml files and DOMNode Elements
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils
 */

class DOM
{
    /**
     * Fügt DOM XML Tabs in eine Toolbar ein
     *
     * @param Array $tabs
     * @param \QUI\Controls\Toolbar\Bar $Tabbar
     * @param $plugin - optional
     */
    static function addTabsToToolbar($tabs, \QUI\Controls\Toolbar\Bar $Tabbar, $plugin='')
    {
        foreach ( $tabs as $Tab )
        {
            $text  = '';
            $image = '';

            $Images   = $Tab->getElementsByTagName( 'image' );
            $Texts    = $Tab->getElementsByTagName( 'text' );
            $Onload   = $Tab->getElementsByTagName( 'onload' );
            $OnUnload = $Tab->getElementsByTagName( 'onunload' );
            $Template = $Tab->getElementsByTagName( 'template' );

            if ( $Images && $Images->item( 0 ) ) {
                $image = self::parseVar( $Images->item( 0 )->nodeValue );
            }

            if ( $Texts && $Texts->item( 0 ) ) {
                $text = self::parseVar( $Texts->item( 0 )->nodeValue );
            }

            $ToolbarTab = new \QUI\Controls\Toolbar\Tab(array(
                'name'   => $Tab->getAttribute( 'name' ),
                'text'   => $text,
                'image'  => $image,
                'plugin' => $plugin
            ));

            foreach ( $Tab->attributes as $attr )
            {
                $name = $attr->nodeName;

                if ( $name !== 'name' && $name !== 'text' ||
                     $name !== 'image' && $name !== 'plugin' )
                {
                    $ToolbarTab->setAttribute( $name, $attr->nodeValue );
                }
            }

            if ( $Onload && $Onload->item( 0 ) )
            {
                $ToolbarTab->setAttribute(
                    'onload',
                    $Onload->item( 0 )->nodeValue
                );

                $ToolbarTab->setAttribute(
                    'onload_require',
                    $Onload->item( 0 )->getAttribute( 'require' )
                );
            }

            if ( $OnUnload && $OnUnload->item( 0 ) )
            {
                $ToolbarTab->setAttribute(
                    'onunload',
                    $OnUnload->item( 0 )->nodeValue
                );

                $ToolbarTab->setAttribute(
                    'onunload_require',
                    $Onload->item( 0 )->getAttribute( 'require' )
                );
            }

            if ( $Template  && $Template->item( 0 ) )
            {
                $ToolbarTab->setAttribute(
                    'template',
                    $Template->item( 0 )->nodeValue
                );
            }

            $Tabbar->appendChild( $ToolbarTab );
        }
    }

    /**
     * HTML eines DOM Tabs
     *
     * @param String $name
     * @param Plugin | \QUI\Projects\Project | String $Object - String = user.xml File
     *
     * @return String
     */
    static function getTabHTML($name, $Object)
    {
        $tabs = array();

        if ( is_string( $Object ) )
        {
            if ( file_exists( $Object ) ) {
                $tabs = \QUI\Utils\XML::getTabsFromXml( $Object );
            }

        } else if ( get_class( $Object ) === 'QUI\\Projects\\Project' )
        {
            /* @var $Object \QUI\Projects\Project */
            // tabs welche ein projekt zur Verfügung stellt
            $tabs = \QUI\Utils\XML::getTabsFromUserXml(
                USR_DIR .'lib/'. $Object->getAttribute( 'name' ) .'/user.xml'
            );

        } else if (
            get_class( $Object ) === 'QUI\\Projects\\Site' ||
            get_class( $Object ) === 'QUI\\Projects\\Site\\Edit' )
        {
            $Tabbar = \QUI\Projects\Sites::getTabs( $Object );
            $Tab    = $Tabbar->getElementByName( $name );

            if ( $Tab->getAttribute( 'template' ) )
            {
                $file = self::parseVar( $Tab->getAttribute( 'template' ) );

                if ( file_exists( $file ) )
                {
                    $Engine = \QUI\Template::getEngine( true );

                    $Engine->assign(array(
                        'Site'    => $Object,
                        'Project' => $Object->getProject(),
                        'Plugins' => \QUI::getPlugins(),
                        'QUI'     => new \QUI()
                    ));

                    return $Engine->fetch( $file );
                }
            }

            return '';

        } else
        {
            /* @var $Object Plugin */
            $tabs = $Object->getUserTabs();
        }

        $str  = '';

        foreach ( $tabs as $Tab )
        {
            if ( $Tab->getAttribute( 'name' ) != $name ) {
                continue;
            }

            $str .= self::parseCategorieToHTML( $Tab );
        }

        return $str;
    }

    /**
     * Parse a DOMDocument to a settings window
     * if a settings window exist in it
     *
     * @param \DomDocument|DomElement $Dom
     * @return \QUI\Controls\Windows\Setting|false
     */
    static function parseDomToWindow($Dom)
    {
        $settings = $Dom->getElementsByTagName( 'settings' );

        if ( !$settings->length ) {
            return false;
        }

        $Settings = $settings->item( 0 );
        $winlist  = $Settings->getElementsByTagName( 'window' );

        if ( !$winlist->length ) {
            return false;
        }

        $Window = $winlist->item( 0 );
        $Win    = new \QUI\Controls\Windows\Window();

        // name
        if ( $Window->getAttribute( 'name' ) ) {
            $Win->setAttribute( 'name', $Window->getAttribute( 'name' ) );
        }

        // titel
        $titles = $Settings->getElementsByTagName('title');

        if ( $titles->item( 0 ) ) {
            $Win->setAttribute( 'title', $titles->item( 0 )->nodeValue );
        }

        // Window Parameter
        $params = $Window->getElementsByTagName('params');

        if ( $params->length )
        {
            $children = $params->item( 0 )->childNodes;

            for ( $i = 0; $i < $children->length; $i++ )
            {
                $Param = $children->item( $i );

                if ( $Param->nodeName == '#text' ) {
                    continue;
                }

                if ( $Param->nodeName == 'icon' )
                {
                    $Win->setAttribute(
                        'icon',
                        \QUI\Utils\DOM::parseVar( $Param->nodeValue )
                    );

                    continue;
                }

                $Win->setAttribute( $Param->nodeName, $Param->nodeValue );
            }
        }

        // buttons bauen
        $btnlist = $Settings->getElementsByTagName( 'categories' );

        if ( $btnlist->length )
        {
            $children = $btnlist->item( 0 )->childNodes;

            for ( $i = 0; $i < $children->length; $i++ )
            {
                $Param = $children->item( $i );

                if ( $Param->nodeName != 'category' ) {
                    continue;
                }

                $Button = new \QUI\Controls\Buttons\Button();
                $Button->setAttribute( 'name', $Param->getAttribute( 'name' ) );
                //$Button->setAttribute( 'onclick', '_pcsg.Plugins.Settings.getButtonContent' );
                //$Button->setAttribute( 'onload', '_pcsg.Plugins.Settings.onload' );
                //$Button->setAttribute( 'onunload', '_pcsg.Plugins.Settings.onunload' );

                $onload   = $Param->getElementsByTagName( 'onload' );
                $onunload = $Param->getElementsByTagName( 'onunload' );

                // Extra on / unload
                /*
                if ( $onload && $onload->length ) {
                    $Button->setAttribute( 'onloadExtra', $onload->item(0)->nodeValue );
                }

                if ( $onunload && $onunload->length ) {
                    $Button->setAttribute( 'onunloadExtra', $onunload->item(0)->nodeValue );
                }
                */

                $btnParams = $Param->childNodes;

                for ( $b = 0; $b < $btnParams->length; $b++ )
                {
                    switch ( $btnParams->item( $b )->nodeName )
                    {
                        case 'text':
                        case 'title':
                        case 'onclick':
                            $Button->setAttribute(
                                $btnParams->item( $b )->nodeName,
                                $btnParams->item( $b )->nodeValue
                            );
                        break;

                        case 'icon':
                            $value = $btnParams->item( $b )->nodeValue;

                            $Button->setAttribute(
                                $btnParams->item( $b )->nodeName,
                                \QUI\Utils\DOM::parseVar( $value )
                            );
                        break;
                    }
                }

                if ( $Param->getAttribute( 'type' ) == 'projects' )
                {
                    $projects = \QUI\Projects\Manager::getProjects();

                    foreach ( $projects as $project )
                    {
                        $Button->setAttribute(
                            'text',
                            str_replace( '{$project}', $project, $Button->getAttribute('text') )
                        );

                        $Button->setAttribute(
                            'title',
                            str_replace( '{$project}', $project, $Button->getAttribute('title') )
                        );

                        $Button->setAttribute( 'section', $project );

                        $Win->appendCategory( $Button );
                    }

                    continue;
                }

                $Win->appendCategory( $Button );
            }
        }

        return $Win;
    }

    /**
     * Parse a DOMNode permission to an array
     *
     * @param \DOMNode $Node
     * @return Array
     */
    static function parsePermissionToArray(\DOMNode $Node)
    {
        if ( $Node->nodeName != 'permission' ) {
            return array();
        }

        $perm    = $Node->getAttribute( 'name' );
        $desc    = '';
        $title   = '';
        $default = false;

        $Default = $Node->getElementsByTagName( 'defaultvalue' );

        if ( $Default && $Default->length ) {
            $default = $Default->item(0)->nodeValue;
        }

        $title = \QUI::getLocale()->get(
            'locale/permissions',
            $perm .'._title'
        );

        $desc = \QUI::getLocale()->get(
            'locale/permissions',
            $perm .'._description'
        );

        $type = \QUI\Rights\Manager::parseType(
            $Node->getAttribute( 'type' )
        );

        $area = \QUI\Rights\Manager::parseArea(
            $Node->getAttribute( 'area' )
        );

        return array(
            'name'    => $perm,
            'desc'    => $desc,
            'area'    => $area,
            'title'   => $title,
            'type'    => $type,
            'default' => $default
        );
    }

    /**
     * Wandelt ein Kategorie DomNode in entsprechendes HTML um
     *
     * @param \DOMNode $Category
     * @param $Plugin - optional
     * @return String
     */
    static function parseCategorieToHTML($Category, $Plugin=false)
    {
        if ( is_bool( $Category ) ) {
            return '';
        }

        $result   = '';
        $children = $Category->childNodes;

        if ( !$children->length ) {
            return '';
        }

        $Engine   = \QUI\Template::getEngine( true );
        $template = $Category->getElementsByTagName( 'template' );

        // Falls ein Template angegeben wurde
        if ( $template && $template->length )
        {
            $Template = $template->item( 0 );
            $file     = self::parseVar( $Template->nodeValue );

            if ( file_exists( $file ) )
            {
                $Engine->assign(array(
                    'Plugin'  => $Plugin,
                    'Plugins' => \QUI::getPlugins(),
                    'QUI'     => new \QUI()
                ));

                return $Engine->fetch( $file );
            }

            return '';
        }


        $result = '';
        $odd    = 'odd';
        $even   = 'even';

        for ( $c = 0; $c < $children->length; $c++ )
        {
            $Entry = $children->item( $c );

            if ( $Entry->nodeName == '#text' ||
                 $Entry->nodeName == 'text' ||
                 $Entry->nodeName == 'image' )
            {
                continue;
            }

            if ( $Entry->nodeName == 'title' )
            {
                $result .= '<table class="data-table"><thead><tr><th>';
                $result .= $Entry->nodeValue;
                $result .= '</th></tr></thead></table>';

                continue;
            }

            if ( $Entry->nodeName == 'settings' )
            {
                $row      = 0;
                $settings = $Entry->childNodes;

                $result .= '<table class="data-table">';

                // title
                $titles = $Entry->getElementsByTagName( 'title' );

                if ( $titles->length )
                {
                    $result .= '<thead><tr><th>';
                    $result .= $titles->item( 0 )->nodeValue;
                    $result .= '</th></tr></thead>';
                }

                $result .= '<tbody>';

                // entries
                for ( $s = 0; $s < $settings->length; $s++ )
                {
                    $Set = $settings->item( $s );

                    if ( $Set->nodeName == '#text' ||
                         $Set->nodeName == 'title' )
                    {
                        continue;
                    }

                    $result .= '<tr class="'. ( $row % 2 ? $even : $odd ) .'"><td>';

                    switch ( $Set->nodeName )
                    {
                        case 'text':
                            $result .= smarty_function_title(array(
                                'text' => $Set->nodeValue
                            ), $Engine);
                        break;

                        case 'input':
                            $result .= self::inputDomToString( $Set );
                        break;

                        case 'select':
                            $result .= self::selectDomToString( $Set );
                        break;

                        case 'textarea':
                            $result .= self::textareaDomToString( $Set );
                        break;

                        case 'group':
                            $result .= self::groupDomToString( $Set );
                        break;

                        case 'button':
                            $result .= self::buttonDomToString( $Set );
                        break;
                    }

                    $result .= '</td></tr>';
                    $row++;
                }

                $result .= '</tbody></table>';

                continue;
            }

            if ( $Entry->nodeName == 'input' )
            {
                $result .= self::inputDomToString( $Entry );
                continue;
            }

            if ( $Entry->nodeName == 'select' )
            {
                $result .= self::selectDomToString( $Entry );
                continue;
            }

            if ( $Entry->nodeName == 'textarea' )
            {
                $result .= self::textareaDomToString( $Entry );
                continue;
            }

            if ( $Entry->nodeName == 'group' )
            {
                $result .= self::groupDomToString( $Entry );
                continue;
            }

            if ( $Entry->nodeName == 'button' )
            {
                $result .= self::buttonDomToString( $Entry );
                continue;
            }
        }

        $result .= '</table>';

        return $result;
    }

    /**
     * Eingabe Element Input in einen String für die Einstellung umwandeln
     *
     * @param \DOMNode $Input
     * @return String
     */
    static function inputDomToString(\DOMNode $Input)
    {
        if ( $Input->nodeName != 'input' ) {
            return '';
        }

        $type = 'text';

        if ( $Input->getAttribute( 'type' ) ) {
            $type = $Input->getAttribute( 'type' );
        }

        $id = $Input->getAttribute( 'conf' ) .'-'. time();

        $string  = '<p>';
        $string .= '<input
            type="'. $type .'"
            name="'. $Input->getAttribute( 'conf' ) .'"
            id="'. $id .'"
        />';

        $text = $Input->getElementsByTagName( 'text' );

        if ( $text->length )
        {
            $string .= '<label for="'. $id .'">'.
                $text->item( 0 )->nodeValue .
            '</label>';
        }

        $desc = $Input->getElementsByTagName( 'description' );

        if ( $desc->length ) {
            $string .= '<div class="description">'. $desc->item( 0 )->nodeValue .'</div>';
        }

        $string .= '</p>';

        return $string;
    }

    /**
     * Button Element
     *
     * @param \DOMNode $Button
     * @return String
     */
    static function buttonDomToString(\DOMNode $Button)
    {
        if ( $Button->nodeName != 'button' ) {
            return '';
        }

        $text = '';
        $Text = $Button->getElementsByTagName( 'text' );

        if ( $Text->length ) {
            $text = $Text->item( 0 )->nodeValue;
        }


        $string  = '<p>';
        $string .= '<div class="btn-button" ';

        $string .= 'data-text="'. $text .'" ';
        $string .= 'data-click="'. $Button->getAttribute( 'onclick' ) .'" ';
        $string .= 'data-image="'. $Button->getAttribute( 'image' ) .'" ';

        $string .= '></div>';
        $string .= '</p>';

        return $string;
    }

    /**
     * Wandelt <group> in einen String für die Einstellung um
     *
     * @param \DOMNode $Group
     * @return String
     */
    static function groupDomToString(\DOMNode $Group)
    {
        if ( $Group->nodeName != 'group' ) {
            return '';
        }

        $string  = '<p>';
        $string .= '<div class="btn-groups" name="'. $Group->getAttribute( 'conf' ) .'"></div>';

        $text = $Group->getElementsByTagName( 'text' );

        if ( $text->length ) {
            $string .= '<span>'. $text->item( 0 )->nodeValue .'</span>';
        }

        $desc = $Group->getElementsByTagName( 'description' );

        if ( $desc->length ) {
            $string .= '<div class="description">'. $desc->item( 0 )->nodeValue .'</div>';
        }

        $string .= '</p>';

        return $string;
    }

    /**
     * Eingabe Element Textarea in einen String für die Einstellung umwandeln
     *
     * @param \DOMNode $Textarea
     *
     * @return String
     */
    static function textareaDomToString(\DOMNode $Textarea)
    {
        if ( $Textarea->nodeName != 'textarea' ) {
            return '';
        }

        $string  = '<p>';
        $string .= '<textarea
            name="'. $Textarea->getAttribute( 'conf' ) .'"
        ></textarea>';

        $text = $Textarea->getElementsByTagName( 'text' );

        if ( $text->length ) {
            $string .= '<span>'. $text->item( 0 )->nodeValue .'</span>';
        }

        $string .= '</p>';

        return $string;
    }

    /**
     * Eingabe Element Select in einen String für die Einstellung umwandeln
     *
     * @param \DOMNode $Select
     * @return String
     */
    static function selectDomToString(\DOMNode $Select)
    {
        if ( $Select->nodeName != 'select' ) {
            return '';
        }

        $string  = '<p>';
        $string .= '<select
            name="'. $Select->getAttribute( 'conf' ) .'"
        >';

        // Options
        $Dom = new \DOMDocument();

        foreach ( $Select->childNodes as $Child )
        {
            if ( $Dom->nodeName == 'text' ) {
                continue;
            }

            $Dom->appendChild( $Dom->importNode( $Child, true ) );
        }

        $string .= $Dom->saveHtml();
        $string .= '</select>';

        $text = $Select->getElementsByTagName( 'text' );

        if ( $text->length ) {
            $string .= '<span>'. $text->item(0)->nodeValue .'</span>';
        }

        $string .= '</p>';

        return $string;
    }

    /**
     * Table Datenbank DOmNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Table
     * @return Array
     */
    static function dbTableDomToArray(\DOMNode $Table)
    {
        $result = array(
            'suffix' => $Table->getAttribute( 'name' )
        );

        $_fields = array();

        // table fields
        $fields = $Table->getElementsByTagName( 'field' );

        for ( $i = 0; $i < $fields->length; $i++ )
        {
            $_fields = array_merge(
                $_fields,
                self::dbFieldDomToArray( $fields->item( $i ) )
            );
        }

        // primary key
        $primary = $Table->getElementsByTagName( 'primary' );

        for ( $i = 0; $i < $primary->length; $i++ )
        {
            $result = array_merge(
                $result,
                self::dbPrimaryDomToArray( $primary->item( $i ) )
            );
        }

        // index
        $index = $Table->getElementsByTagName( 'index' );

        for ( $i = 0; $i < $index->length; $i++ )
        {
            $result = array_merge(
                $result,
                self::dbIndexDomToArray( $index->item( $i ) )
            );
        }

        $autoincrement = $Table->getElementsByTagName( 'auto_increment' );

        for ( $i = 0; $i < $autoincrement->length; $i++ )
        {
            $result = array_merge(
                $result,
                self::dbAutoIncrementDomToArray( $autoincrement->item( $i ) )
            );
        }

        $result['fields'] = $_fields;


        return $result;
    }

    /**
     * Field Datenbank DOmNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Field
     * @return Array
     */
    static function dbFieldDomToArray(\DOMNode $Field)
    {
        $str  = '';
        $str .= $Field->getAttribute( 'type' );

        if ( empty( $str ) ) {
            $str .= 'text';
        }

        if ( $Field->getAttribute( 'length' ) ) {
            $str .= '('. $Field->getAttribute( 'length' ) .')';
        }

        $str .= ' ';

        if ( $Field->getAttribute( 'null' ) == 1 )
        {
            $str .= 'NULL';
        } else
        {
            $str .= 'NOT NULL';
        }

        return array(
            trim( $Field->nodeValue ) => $str
        );
    }

    /**
     * Primary Datenbank DOmNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Primary
     * @return Array
     */
    static function dbPrimaryDomToArray(\DOMNode $Primary)
    {
        return array(
            'primary' => explode( ',', $Primary->nodeValue )
        );
    }

    /**
     * Index Datenbank DOMNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Index
     * @return Array
     */
    static function dbIndexDomToArray(\DOMNode $Index)
    {
        return array(
            'index' => $Index->nodeValue
        );
    }

    /**
     * AUTO_INCREMENT Datenbank DOMNode Objekt in ein Array umwandeln
     *
     * @param \DOMNode $Index
     * @return Array
     */
    static function dbAutoIncrementDomToArray(\DOMNode $AI)
    {
        return array(
            'auto_increment' => $AI->nodeValue
        );
    }

    /**
     * Parse config entries to an array
     *
     * @param \DOMNode $confs
     * @return Array
     */
    static function parseConfs($confs)
    {
        $result = array();

        foreach ( $confs as $Conf )
        {
            $type    = 'string';
            $default = '';

            $types    = $Conf->getElementsByTagName( 'type' );
            $defaults = $Conf->getElementsByTagName( 'defaultvalue' );

            // type
            if ( $types && $types->length ) {
                $type = $types->item( 0 )->nodeValue;
            }

            // default
            if ( $defaults && $defaults->length )
            {
                $default = self::parseVar(
                    $defaults->item( 0 )->nodeValue
                );
            }

            $result[ $Conf->getAttribute( 'name' ) ] = array(
                'type'    => $type,
                'default' => $default
            );
        }

        return $result;
    }

    /**
     * Ersetzt Variablen im XML
     *
     * @param String $value
     * @return String
     */
    static function parseVar($value)
    {
        $value = str_replace(
            array(
                'URL_BIN_DIR', 'URL_OPT_DIR', 'URL_USR_DIR',
                'BIN_DIR', 'OPT_DIR', 'URL_DIR', 'SYS_DIR', 'CMS_DIR'
            ),
            array(
                URL_BIN_DIR, URL_OPT_DIR, URL_USR_DIR,
                BIN_DIR, OPT_DIR, URL_DIR, SYS_DIR, CMS_DIR
            ),
            $value
        );

        $value = \QUI\Utils\String::replaceDblSlashes( $value );

        return $value;
    }

    /**
     * Returns the String between <body> and </body>
     *
     * @param String $html
     * @return String
     */
    static function getInnerBodyFromHTML($html)
    {
        return preg_replace( '/(.*)<body>(.*)<\/body>(.*)/si', '$2', $html );
    }

    /**
     * Returns the innerHTML from a PHP DOMNode
     * Equivalent to the JavaScript innerHTML property
     *
     * @param \DOMNode $Node
     * @return String
     */
    static function getInnerHTML(\DOMNode $Node)
    {
        $Dom      = new \DOMDocument();
        $Children = $Node->childNodes;

        foreach ( $Children as $Child ) {
            $Dom->appendChild( $Dom->importNode( $Child, true ) );
        }

        return $Dom->saveHTML();
    }

    /**
     * Converts an array into an \QUI\QDOM object
     *
     * @param array $array
     * @return \QUI\QDOM
     */
    static function arrayToQDOM(array $array)
    {
        $DOM = new \QUI\QDOM();
        $DOM->setAttributes( $array );

        return $DOM;
    }
}
