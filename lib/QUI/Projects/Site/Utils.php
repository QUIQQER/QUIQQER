<?php

/**
 * This file contains \QUI\Projects\Site\Utils
 */

namespace QUI\Projects\Site;

use QUI;
use QUI\Projects;
use QUI\Projects\Project;
use QUI\Utils\StringHelper as StringUtils;
use QUI\Utils\Text\XML;
use QUI\Utils\DOM;
use QUI\Utils\Security\Orthos;

/**
 * Site Utils - Site Helper
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Utils
{
    /**
     * Prüft ob der Name erlaubt ist
     *
     * @param string $name
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public static function checkName($name)
    {
        if (!isset($name)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.url.missing.title')
            );
        }

        if (\strlen($name) <= 2) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.url.2.signs'),
                701
            );
        }

        if (\strlen($name) > 200) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.url.200.signs'),
                704
            );
        }

        $signs = '@[.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+\-]@';


        if (QUI\Rewrite::URL_SPACE_CHARACTER == '-') {
            $signs = '@[.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]@';
        }

        // Prüfung des Namens - Sonderzeichen
        if (\preg_match($signs, $name)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.url.wrong.signs', [
                    'name'  => $name,
                    'signs' => $signs
                ]),
                702
            );
        }

        return true;
    }

    /**
     * Clean a URL -> makes it beautiful
     * unwanted signs will be converted or filtered
     *
     * @param string $url
     * @param QUI\Projects\Project|null $Project - optional, Project clear extension
     *
     * @return string
     */
    public static function clearUrl($url, QUI\Projects\Project $Project = null)
    {
        // space separator
        $url = \str_replace(QUI\Rewrite::URL_SPACE_CHARACTER, ' ', $url);

        // clear
        $signs = [
            '-',
            '.',
            ',',
            ':',
            ';',
            '#',
            '`',
            '!',
            '§',
            '$',
            '%',
            '&',
            '?',
            '<',
            '>',
            '=',
            '\'',
            '"',
            '@',
            '_',
            ']',
            '[',
            '+',
            '/'
        ];

        $url = \str_replace($signs, '', $url);
        //$url = preg_replace('[-.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]', '', $url);

        // doppelte leerzeichen löschen
        $url = \preg_replace('/([ ]){2,}/', "$1", $url);

        // URL Filter
        if ($Project !== null) {
            $name   = $Project->getAttribute('name');
            $filter = USR_DIR.'lib/'.$name.'/url.filter.php';
            $func   = 'url_filter_'.$name;

            $filter = Orthos::clearPath(\realpath($filter));

            if (\file_exists($filter)) {
                require_once $filter;

                if (\function_exists($func)) {
                    $url = $func($url);
                }
            }
        }

        $url = \str_replace(' ', QUI\Rewrite::URL_SPACE_CHARACTER, $url);

        return $url;
    }

    /**
     * Return database.xml list for the Site Object
     *
     * @param QUI\Projects\Site $Site
     *
     * @return array
     */
    public static function getDataBaseXMLListForSite($Site)
    {
        $siteType = $Site->getAttribute('type');
        $cache    = $Site->getCachePath().'/xml-database-list/'.$siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        $dbXmlList = QUI::getPackageManager()->getPackageDatabaseXmlList();
        $result    = [];

        foreach ($dbXmlList as $package) {
            $file = OPT_DIR.$package.'/database.xml';

            if (!\file_exists($file)) {
                continue;
            }

            $Dom  = XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

            $tableList = $Path->query("//database/projects/table");

            for ($i = 0, $len = $tableList->length; $i < $len; $i++) {
                /* @var $Table \DOMElement */
                $Table = $tableList->item($i);

                if ($Table->getAttribute('no-auto-update')) {
                    continue;
                }

                if ($Table->getAttribute('no-project-lang')) {
                    continue;
                }


                // types check
                $types = $Table->getAttribute('site-types');

                if ($types) {
                    $types = \explode(',', $types);
                }

                if (!empty($types) && \is_array($types)) {
                    foreach ($types as $allowedType) {
                        if (!StringUtils::match($allowedType, $siteType)) {
                            continue 2;
                        }
                    }
                }

                // table is ok
                $result[] = [
                    'file'    => $file,
                    'package' => $package
                ];
            }
        }

        try {
            QUI\Cache\Manager::set($cache, $result);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        return $result;
    }

    /**
     * Return data table array for the Site Object
     * a list of the extra database and extra attributes for saving the site
     * the extra attributes are all from database.xml files
     *
     * @param QUI\Projects\Site $Site
     *
     * @return array
     */
    public static function getDataListForSite($Site)
    {
        $siteType = $Site->getAttribute('type');
        $cache    = $Site->getCachePath().'/xml-database-tables/'.$siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        $dbXmlList = self::getDataBaseXMLListForSite($Site);
        $Project   = $Site->getProject();
        $name      = $Project->getName();
        $lang      = $Project->getLang();

        $result = [];

        foreach ($dbXmlList as $dbXml) {
            $Dom     = XML::getDomFromXml($dbXml['file']);
            $Path    = new \DOMXPath($Dom);
            $package = $dbXml['package'];

            $tableList = $Path->query("//database/projects/table");

            for ($i = 0, $len = $tableList->length; $i < $len; $i++) {
                /* @var $Table \DOMElement */
                $Table = $tableList->item($i);

                if ($Table->getAttribute('no-auto-update')) {
                    continue;
                }

                if ($Table->getAttribute('no-project-lang')) {
                    continue;
                }


                // types check
                $types = $Table->getAttribute('site-types');

                if ($types) {
                    $types = \explode(',', $types);
                }

                if (!empty($types) && \is_array($types)) {
                    foreach ($types as $allowedType) {
                        if (!StringUtils::match($allowedType, $siteType)) {
                            continue 2;
                        }
                    }
                }


                $suffix = $Table->getAttribute('name');
                $fields = $Table->getElementsByTagName('field');

                $table = QUI::getDBTableName($name.'_'.$lang.'_'.$suffix);
                $data  = [];


                for ($f = 0, $flen = $fields->length; $f < $flen; $f++) {
                    $Field     = $fields->item($f);
                    $attribute = \trim($Field->nodeValue);

                    $data[] = $attribute;
                }

                if (!isset($data) || empty($data)) {
                    continue;
                }

                $result[] = [
                    'table'   => $table,
                    'data'    => $data,
                    'package' => $package,
                    'suffix'  => $suffix
                ];
            }
        }

        try {
            QUI\Cache\Manager::set($cache, $result);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        return $result;
    }


    /**
     * Return database.xml list for the Site Object
     *
     * @param QUI\Projects\Site $Site
     *
     * @return array
     */
    public static function getExtraAttributeListForSite($Site)
    {
        $siteType = $Site->getAttribute('type');
        $cache    = $Site->getCachePath().'/xml-database-attributes/'.$siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }


        // global extra attributes
        $siteXmlList = QUI::getPackageManager()->getPackageSiteXmlList();
        $result      = [];


        foreach ($siteXmlList as $package) {
            $file = OPT_DIR.$package.'/site.xml';

            if (!\file_exists($file)) {
                continue;
            }

            $Dom  = XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

            $attributes = $Path->query('//site/attributes/attribute');

            /* @var $Attribute \DOMElement */
            foreach ($attributes as $Attribute) {
                $result[] = [
                    'attribute' => \trim($Attribute->nodeValue),
                    'default'   => $Attribute->getAttribute('default')
                ];
            }
        }


        // extra type attributes
        $type = \explode(':', $siteType);

        if (isset($type[1])) {
            $expr
                =
                '//site/types/type[@type="'.$type[1].'"]/attributes/attribute';

            $siteXmlFile = OPT_DIR.$type[0].'/site.xml';

            $Dom  = XML::getDomFromXml($siteXmlFile);
            $Path = new \DOMXPath($Dom);

            $attributes = $Path->query($expr);

            /* @var $Attribute \DOMElement */
            foreach ($attributes as $Attribute) {
                $result[] = [
                    'attribute' => \trim($Attribute->nodeValue),
                    'default'   => $Attribute->getAttribute('default')
                ];
            }
        }

        try {
            QUI\Cache\Manager::set($cache, $result);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        return $result;
    }

    /**
     * Return the extra settings from site.xml's
     *
     * @param QUI\Projects\Site $Site
     *
     * @return string
     */
    public static function getExtraSettingsForSite($Site)
    {
        $siteType = $Site->getAttribute('type');
        $cache    = $Site->getCachePath().'/xml-database-settings/'.$siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }


        // global extra
        $siteXmlList = QUI::getPackageManager()->getPackageSiteXmlList();
        $result      = '';

        foreach ($siteXmlList as $package) {
            $file = OPT_DIR.$package.'/site.xml';

            if (!\file_exists($file)) {
                continue;
            }

            $Dom  = XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);
            $cats = $Path->query("//site/settings/category");

            foreach ($cats as $Category) {
                $result .= DOM::parseCategoryToHTML($Category);
            }
        }


        // site type extra xml
        $type    = \explode(':', $Site->getAttribute('type'));
        $dir     = OPT_DIR.$type[0];
        $siteXML = $dir.'/site.xml';

        if (\file_exists($siteXML)) {
            $Dom  = XML::getDomFromXml($siteXML);
            $Path = new \DOMXPath($Dom);

            // type extra
            $cats = $Path->query(
                "//site/types/type[@type='".$type[1]."']/settings/category"
            );

            foreach ($cats as $Category) {
                $result .= DOM::parseCategoryToHTML($Category);
            }
        }

        try {
            QUI\Cache\Manager::set($cache, $result);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        return $result;
    }

    /**
     * Return the admin site modules from site.xml's
     *
     * @param QUI\Projects\Site $Site
     *
     * @return array|boolean
     */
    public static function getAdminSiteModulesFromSite($Site)
    {
        $siteType = $Site->getAttribute('type');
        $cache    = $Site->getCachePath().'/xml-admin-modules/'.$siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        // site type extra xml
        $type    = \explode(':', $Site->getAttribute('type'));
        $dir     = OPT_DIR.$type[0];
        $siteXML = $dir.'/site.xml';

        $result = [];

        if (\file_exists($siteXML)) {
            $Dom  = XML::getDomFromXml($siteXML);
            $Path = new \DOMXPath($Dom);

            // type extra
            $modules = $Path->query(
                "//site/types/type[@type='".$type[1]."']/admin/js"
            );

            foreach ($modules as $Module) {
                foreach ($Module->attributes as $Attr) {
                    $result['js'][$Attr->nodeName][] = $Attr->nodeValue;
                }
            }
        }

        try {
            QUI\Cache\Manager::set($cache, $result);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        return $result;
    }


    /**
     * is the object one of the site objects
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit|QUI\Projects\Site\OnlyDB $Site
     *
     * @return boolean
     */
    public static function isSiteObject($Site)
    {
        switch (\get_class($Site)) {
            case 'QUI\\Projects\\Site':
            case 'QUI\\Projects\\Site\\Edit':
            case 'QUI\\Projects\\Site\\OnlyDB':
                break;

            default:
                return false;
        }

        return true;
    }

    /**
     * is the link a quiqqer site link?
     * eq: index.php?project=test&lang=de&id=1
     *
     * @param string $link - index.php?project=test&lang=de&id=1
     *
     * @return boolean
     */
    public static function isSiteLink($link)
    {
        if (\strpos($link, 'index.php') === false) {
            return false;
        }

        if (\strpos($link, 'project=') === false) {
            return false;
        }

        if (\strpos($link, 'lang=') === false) {
            return false;
        }

        if (\strpos($link, 'id=') === false) {
            return false;
        }

        return true;
    }

    /**
     * Return the site object of the quiqqer site link
     * eq: getSiteByLink( index.php?project=test&lang=de&id=1 )
     *
     * @param string $link - index.php?project=test&lang=de&id=1
     *
     * @return Projects\Site
     * @throws QUI\Exception
     */
    public static function getSiteByLink($link)
    {
        if (!self::isSiteLink($link)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.not.found'
                ),
                705,
                [
                    'method' => 'getSiteByLink',
                    'class'  => 'QUI/projects/Site/Utils',
                    'link'   => $link
                ]
            );
        }

        $parseUrl = \parse_url($link);

        if (!isset($parseUrl['query']) || empty($parseUrl['query'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.not.found'
                ),
                705,
                [
                    'method' => 'getSiteByLink',
                    'class'  => 'QUI/projects/Site/Utils',
                    'link'   => $link
                ]
            );
        }

        \parse_str($parseUrl['query'], $urlQueryParams);

        $Project = QUI::getProject(
            $urlQueryParams['project'],
            $urlQueryParams['lang']
        );

        return $Project->get($urlQueryParams['id']);
    }

    /**
     * Return a site by an url (relative url)
     *
     * @param Project $Project
     * @param $link
     * @return Projects\Site
     *
     * @throws QUI\Exception
     */
    public static function getSiteByUrl(Project $Project, $link)
    {
        $link  = \str_replace('.html', '', $link);
        $link  = \trim($link);
        $link  = \trim($link, '/');
        $parts = \explode('/', $link);

        $Site = $Project->firstChild();

        foreach ($parts as $part) {
            $id   = $Site->getChildIdByName($part);
            $Site = $Project->get($id);
        }

        return $Site;
    }

    /**
     * Return sites from a site list
     * sitelist from controls/projects/project/site/Select
     *
     * @param Project $Project - Project of the sites
     * @param array|string $list - list from controls/projects/project/site/Select
     * @param array $params - order / sort params
     *
     * @return array
     */
    public static function getSitesByInputList(
        Project $Project,
        $list,
        $params = []
    ) {
        $limit = 2;
        $order = 'release_from ASC';

        if (isset($params['limit'])) {
            $limit = $params['limit'];
        }

        if (isset($params['order'])) {
            $order = $params['order'];
        }

//        if (empty($order)) {
        // @todo eigener select, rückgabe dann wie in liste übergeben
//        }

        if (\is_string($list)) {
            $sitetypes = \explode(';', $list);
        } else {
            if (\is_array($list)) {
                $sitetypes = $list;
            } else {
                return [];
            }
        }

        $ids     = [];
        $types   = [];
        $parents = [];
        $where   = [];

        foreach ($sitetypes as $sitetypeEntry) {
            if (\is_numeric($sitetypeEntry)) {
                $ids[] = (int)$sitetypeEntry;
                continue;
            }

            if (\strpos($sitetypeEntry, 'p') === 0
                && \strpos($sitetypeEntry, '/') === false
                && \strpos($sitetypeEntry, ':') === false
            ) {
                $parents[] = \str_replace('p', '', $sitetypeEntry);
                continue;
            }

            $types[] = $sitetypeEntry;
        }

        // query params
        if (!empty($ids)) {
            $where['id'] = [
                'type'  => 'IN',
                'value' => $ids
            ];
        }

        if (!empty($types)) {
            $where['type'] = [
                'type'  => 'IN',
                'value' => $types
            ];
        }

        // parents are set
        if (\count($parents)) {
            foreach ($parents as $parentId) {
                try {
                    $Parent = $Project->get((int)$parentId);

                    $children = $Parent->getChildrenIds([
                        'order' => $order
                    ]);

                    $ids = \array_merge($ids, $children);
                } catch (QUI\Exception $Exception) {
                }
            }

            if (!\count($ids)) {
                if (isset($params['count']) && $params['count']) {
                    return [['count' => 0]];
                }

                return [];
            }

            $where['id'] = [
                'type'  => 'IN',
                'value' => $ids
            ];


            if (isset($params['count']) && $params['count']) {
                return $Project->getSitesIds([
                    'count' => true,
                    'where' => $where
                ]);
            }

            // by with parents, we use WHERE AND
            return $Project->getSites([
                'where' => $where,
                'limit' => $limit,
                'order' => $order
            ]);
        }

        if (isset($params['count']) && $params['count']) {
            return $Project->getSitesIds([
                'count' => true,
                'where' => $where
            ]);
        }

        // by no parents, we use WHERE OR
        return $Project->getSites([
            'where_or' => $where,
            'limit'    => $limit,
            'order'    => $order
        ]);
    }

    /**
     * Return the rewrited link
     * eq: rewriteSiteLink( index.php?project=test&lang=de&id=1 )
     *
     * @param string $link - Project of the sites
     *
     * @return string
     *
     * @throws \QUI\Exception
     */
    public static function rewriteSiteLink($link)
    {
        if (!self::isSiteLink($link)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.not.found'
                ),
                705,
                [
                    'method' => 'rewriteSiteLink',
                    'class'  => 'QUI/projects/Site/Utils',
                    'link'   => $link
                ]
            );
        }

        $parseUrl = \parse_url($link);

        if (!isset($parseUrl['query']) || empty($parseUrl['query'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.not.found'
                ),
                705,
                [
                    'method' => 'rewriteSiteLink',
                    'class'  => 'QUI/projects/Site/Utils',
                    'link'   => $link
                ]
            );
        }

        // html_entity_decode because -> &nbsp; in index.php links
        \parse_str(\html_entity_decode($parseUrl['query']), $urlQueryParams);

        return QUI::getRewrite()->getOutput()->getSiteUrl($urlQueryParams);
    }
}
