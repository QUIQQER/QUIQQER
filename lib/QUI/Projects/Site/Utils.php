<?php

/**
 * This file contains \QUI\Projects\Site\Utils
 */

namespace QUI\Projects\Site;

use QUI;
use QUI\Utils\String as StringUtils;
use QUI\Utils\XML;
use QUI\Utils\DOM;

/**
 * Site Utils - Site Helper
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class Utils
{
    /**
     * Prüft ob der Name erlaubt ist
     *
     * @param String $name
     * @throws \QUI\Exception
     * @return Bool
     */
    static function checkName($name)
    {
        if ( !isset( $name ) )
        {
            throw new \QUI\Exception(
                'Bitte gebe einen Titel ein'
            );
        }

        if ( strlen( $name ) <= 2 )
        {
            throw new \QUI\Exception(
                'Die URL muss mehr als 2 Zeichen lang sein',
                701
            );
        }

        if ( strlen( $name ) > 200 )
        {
            throw new \QUI\Exception(
                'Die URL darf nicht länger als 200 Zeichen lang sein',
                704
            );
        }

        $signs = '@[.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+\-]@';


        if ( \QUI\Rewrite::URL_SPACE_CHARACTER == '-' ) {
            $signs = '@[.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]@';
        }

        // Prüfung des Namens - Sonderzeichen
        if ( preg_match( $signs, $name ) )
        {
            throw new \QUI\Exception(
                'In der URL "'. $name .'" dürfen folgende Zeichen nicht verwendet werden: _-.,:;#@`!§$%&/?<>=\'"[]+',
                702
            );
        }

        return true;
    }

    /**
     * Säubert eine URL macht sie schön
     *
     * @param String $url
     * @param \QUI\Projects\Project $Project - Project clear extension
     * @return String
     */
    static function clearUrl($url, \QUI\Projects\Project $Project)
    {
        // space seperator
        $url = str_replace( \QUI\Rewrite::URL_SPACE_CHARACTER , ' ', $url );

        // clear
        $signs = array(
            '-', '.', ',', ':', ';',
            '#', '`', '!', '§', '$',
            '%', '&', '?', '<', '>',
            '=', '\'', '"', '@', '_',
            ']', '[', '+', '/'
        );

        $url = str_replace($signs, '', $url);
        //$url = preg_replace('[-.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]', '', $url);

        // doppelte leerzeichen löschen
        $url = preg_replace('/([ ]){2,}/', "$1", $url);

        // @todo als event
        // URL Filter
        $name   = $Project->getAttribute('name');
        $filter = USR_DIR .'lib/'. $name .'/url.filter.php';
        $func   = 'url_filter_'. $name;

        if ( file_exists( $filter ) )
        {
            require_once $filter;

            if ( function_exists( $func ) ) {
                $url = $func( $url );
            }
        }

        $url = str_replace( ' ', \QUI\Rewrite::URL_SPACE_CHARACTER, $url );

        return $url;
    }

    /**
     * Return database.xml list for the Site Object
     *
     * @param \QUI\Projects\Site $Site
     * @return Array
     */
    static function getDataBaseXMLListForSite($Site)
    {
        $Project  = $Site->getProject();
        $name     = $Project->getName();
        $lang     = $Project->getLang();
        $siteType = $Site->getAttribute( 'type' );
        $cache    = "site/dbxml/project/{$name}-{$lang}/type/{$siteType}";

        try
        {
            return \QUI\Cache\Manager::get( $cache );

        } catch ( \QUI\Exception $Exception )
        {

        }

        $dbXmlList = \QUI::getPackageManager()->getPackageDatabaseXmlList();
        $result    = array();

        $Project  = $Site->getProject();
        $name     = $Project->getName();
        $lang     = $Project->getLang();
        $siteType = $Site->getAttribute( 'type' );


        foreach ( $dbXmlList as $package )
        {
            $file = OPT_DIR . $package .'/database.xml';

            if ( !file_exists( $file ) ) {
                continue;
            }

            $Dom  = XML::getDomFromXml( $file );
            $Path = new \DOMXPath( $Dom );

            $tableList = $Path->query( "//database/projects/table" );

            for ( $i = 0, $len = $tableList->length; $i < $len; $i++ )
            {
                $Table = $tableList->item( $i );

                if ( $Table->getAttribute( 'no-auto-update' ) ) {
                    continue;
                }

                if ( $Table->getAttribute( 'no-project-lang' ) ) {
                    continue;
                }


                // types check
                $types = $Table->getAttribute( 'site-types' );

                if ( $types ) {
                    $types = explode( ',', $types );
                }

                if ( !empty( $types ) )
                {
                    foreach ( $types as $allowedType )
                    {
                        if ( !StringUtils::match( $allowedType, $siteType ) ) {
                            continue 2;
                        }
                    }
                }

                // table is ok
                $result[] = array(
                    'file'    => $file,
                    'package' => $package
                );
            }
        }

        \QUI\Cache\Manager::set( $cache , $result );

        return $result;
    }

    /**
     * Return data table array for the Site Object
     * a list of the extra database and extra attributes for saving the site
     * the extra attributes are all from database.xml files
     *
     * @param \QUI\Projects\Site $Site
     * @return Array
     */
    static function getDataListForSite($Site)
    {
        $dbXmlList = self::getDataBaseXMLListForSite( $Site );

        $Project  = $Site->getProject();
        $name     = $Project->getName();
        $lang     = $Project->getLang();
        $siteType = $Site->getAttribute( 'type' );
        $cache    = "site/datalist/project/{$name}-{$lang}/type/{$siteType}";

        try
        {
            return \QUI\Cache\Manager::get( $cache );

        } catch ( \QUI\Exception $Exception )
        {

        }

        $result = array();

        foreach ( $dbXmlList as $dbXml )
        {
            $Dom     = XML::getDomFromXml( $dbXml['file'] );
            $Path    = new \DOMXPath( $Dom );
            $package = $dbXml['package'];

            $tableList = $Path->query( "//database/projects/table" );

            for ( $i = 0, $len = $tableList->length; $i < $len; $i++ )
            {
                $Table = $tableList->item( $i );

                if ( $Table->getAttribute( 'no-auto-update' ) ) {
                    continue;
                }

                if ( $Table->getAttribute( 'no-project-lang' ) ) {
                    continue;
                }


                // types check
                $types = $Table->getAttribute( 'site-types' );

                if ( $types ) {
                    $types = explode( ',', $types );
                }

                if ( !empty( $types ) )
                {
                    foreach ( $types as $allowedType )
                    {
                        if ( !StringUtils::match( $allowedType, $siteType ) ) {
                            continue 2;
                        }
                    }
                }


                $suffix = $Table->getAttribute( 'name' );
                $fields = $Table->getElementsByTagName( 'field' );

                $table = \QUI::getDBTableName( $name .'_'. $lang .'_'. $suffix );
                $data  = array();


                for ( $f = 0, $flen = $fields->length; $f < $flen; $f++ )
                {
                    $Field     = $fields->item( $f );
                    $attribute = trim( $Field->nodeValue );

                    $data[] = $attribute;
                }

                if ( !isset( $data ) || empty( $data ) ) {
                    continue;
                }

                $result[] = array(
                    'table'   => $table,
                    'data'    => $data,
                    'package' => $package,
                    'suffix'  => $suffix
                );
            }
        }

        \QUI\Cache\Manager::set( $cache , $result );


        return $result;
    }


     /**
     * Return database.xml list for the Site Object
     *
     * @param \QUI\Projects\Site $Site
     * @return Array
     */
    static function getExtraAttributeListForSite($Site)
    {
        $Project  = $Site->getProject();
        $name     = $Project->getName();
        $lang     = $Project->getLang();
        $siteType = $Site->getAttribute( 'type' );
        $cache    = "site/site-attribute-list/project/{$name}-{$lang}/type/{$siteType}";

        try
        {
            return \QUI\Cache\Manager::get( $cache );

        } catch ( \QUI\Exception $Exception )
        {

        }


        // global extra attributes
        $siteXmlList = \QUI::getPackageManager()->getPackageSiteXmlList();
        $result      = array();

        $Project = $Site->getProject();
        $name    = $Project->getName();
        $lang    = $Project->getLang();

        foreach ( $siteXmlList as $package )
        {
            $file = OPT_DIR . $package .'/site.xml';

            if ( !file_exists( $file ) ) {
                continue;
            }

            $Dom  = XML::getDomFromXml( $file );
            $Path = new \DOMXPath( $Dom );

            $attributes = $Path->query( '//site/attributes/attribute' );

            foreach ( $attributes as $Attribute ) {
                $result[] = trim( $Attribute->nodeValue );
            }
        }


        // extra type attributes
        $type = explode( ':', $siteType );

        if ( isset( $type[ 1 ] ) )
        {
            $expr = '//site/types/type[@type="'. $type[ 1 ] .'"]/attributes/attribute';

            $siteXmlFile = OPT_DIR . $type[ 0 ] .'/site.xml';

            $Dom  = XML::getDomFromXml( $siteXmlFile );
            $Path = new \DOMXPath( $Dom );

            $attributes = $Path->query( $expr );

            foreach ( $attributes as $Attribute ) {
                $result[] = trim( $Attribute->nodeValue );
            }
        }

        \QUI\Cache\Manager::set( $cache , $result );

        return $result;
    }

    /**
     * Return the extra settings from site.xml's
     *
     * @param \QUI\Projects\Site $Site
     * @return String
     */
    static function getExtraSettingsForSite($Site)
    {
        $Project  = $Site->getProject();
        $name     = $Project->getName();
        $lang     = $Project->getLang();
        $siteType = $Site->getAttribute( 'type' );
        $cache    = "site/site-extra-settings/project/{$name}-{$lang}/type/{$siteType}";

        try
        {
            return \QUI\Cache\Manager::get( $cache );

        } catch ( \QUI\Exception $Exception )
        {

        }


        // global extra
        $siteXmlList = \QUI::getPackageManager()->getPackageSiteXmlList();
        $result      = '';

        foreach ( $siteXmlList as $package )
        {
            $file = OPT_DIR . $package .'/site.xml';

            if ( !file_exists( $file ) ) {
                continue;
            }

            $Dom  = XML::getDomFromXml( $file );
            $Path = new \DOMXPath( $Dom );
            $cats = $Path->query( "//site/settings/category" );

            foreach ( $cats as $Category ) {
                $result .= DOM::parseCategorieToHTML( $Category );
            }
        }


        // site type extra xml
        $type    = explode( ':', $Site->getAttribute( 'type' ) );
        $dir     = OPT_DIR . $type[ 0 ];
        $siteXML = $dir .'/site.xml';

        if ( file_exists( $siteXML ) )
        {
            $Dom    = XML::getDomFromXml( $siteXML );
            $Path   = new \DOMXPath( $Dom );

            // type extra
            $cats = $Path->query(
                "//site/types/type[@type='". $type[ 1 ] ."']/settings/category"
            );

            foreach ( $cats as $Category ) {
                $result .= DOM::parseCategorieToHTML( $Category );
            }
        }

        \QUI\Cache\Manager::set( $cache , $result );

        return $result;
    }

    /**
     * is the object one of the site objects
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit|QUI\Projects\Site\OnlyDB $Site
     * @return boolean
     */
    static function isSiteObject($Site)
    {
        switch ( get_class( $Site ) )
        {
            case 'QUI\\Projects\\Site':
            case 'QUI\\Projects\\Site\\Edit':
            case 'QUI\\Projects\\Site\\OnlyDB':
            break;

            default:
                return false;
        }

        return true;
    }
}
