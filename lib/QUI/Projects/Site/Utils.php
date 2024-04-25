<?php

/**
 * This file contains \QUI\Projects\Site\Utils
 */

namespace QUI\Projects\Site;

use DOMElement;
use DOMXPath;
use QUI;
use QUI\Exception;
use QUI\Projects;
use QUI\Projects\Project;
use QUI\Utils\DOM;
use QUI\Utils\Security\Orthos;
use QUI\Utils\StringHelper as StringUtils;
use QUI\Utils\Text\XML;

use function array_merge;
use function count;
use function explode;
use function file_exists;
use function function_exists;
use function html_entity_decode;
use function is_array;
use function is_numeric;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_replace;
use function realpath;
use function str_replace;
use function strlen;
use function trim;

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
     * @throws Exception
     */
    public static function checkName(string $name): bool
    {
        if (strlen($name) <= 2) {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.url.2.signs'),
                701
            );
        }

        if (strlen($name) > 200) {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.url.200.signs'),
                704
            );
        }

        $signs = '@[.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+\-]@';


        if (QUI\Rewrite::URL_SPACE_CHARACTER == '-') {
            $signs = '@[.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]@';
        }

        // Prüfung des Namens - Sonderzeichen
        if (preg_match($signs, $name)) {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.url.wrong.signs', [
                    'name' => $name,
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
    public static function clearUrl(string $url, QUI\Projects\Project $Project = null): string
    {
        // space separator
        $url = str_replace(QUI\Rewrite::URL_SPACE_CHARACTER, ' ', $url);

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

        $url = str_replace($signs, '', $url);
        //$url = preg_replace('[-.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]', '', $url);

        // doppelte leerzeichen löschen
        $url = preg_replace('/([ ]){2,}/', "$1", $url);

        // URL Filter
        if ($Project !== null) {
            $name = $Project->getAttribute('name');
            $filter = USR_DIR . 'lib/' . $name . '/url.filter.php';
            $func = 'url_filter_' . $name;

            $filter = Orthos::clearPath(realpath($filter));

            if (file_exists($filter)) {
                require_once $filter;

                if (function_exists($func)) {
                    $url = $func($url);
                }
            }
        }

        return str_replace(' ', QUI\Rewrite::URL_SPACE_CHARACTER, $url);
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
    public static function getDataListForSite(Projects\Site $Site): array
    {
        $siteType = $Site->getAttribute('type');
        $cache = $Site->getCachePath() . '/xml-database-tables/' . $siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (Exception $Exception) {
        }

        $dbXmlList = self::getDataBaseXMLListForSite($Site);
        $Project = $Site->getProject();
        $name = $Project->getName();
        $lang = $Project->getLang();

        $result = [];

        foreach ($dbXmlList as $dbXml) {
            $Dom = XML::getDomFromXml($dbXml['file']);
            $Path = new DOMXPath($Dom);
            $package = $dbXml['package'];

            $tableList = $Path->query("//database/projects/table");

            for ($i = 0, $len = $tableList->length; $i < $len; $i++) {
                /* @var $Table DOMElement */
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
                    $types = explode(',', $types);
                }

                if (!empty($types) && is_array($types)) {
                    foreach ($types as $allowedType) {
                        if (!StringUtils::match($allowedType, $siteType)) {
                            continue 2;
                        }
                    }
                }


                $suffix = $Table->getAttribute('name');
                $fields = $Table->getElementsByTagName('field');

                $table = QUI::getDBTableName($name . '_' . $lang . '_' . $suffix);
                $data = [];


                for ($f = 0, $fLen = $fields->length; $f < $fLen; $f++) {
                    $Field = $fields->item($f);
                    $attribute = trim($Field->nodeValue);

                    $data[] = $attribute;
                }

                if (empty($data)) {
                    continue;
                }

                $result[] = [
                    'table' => $table,
                    'data' => $data,
                    'package' => $package,
                    'suffix' => $suffix
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
    public static function getDataBaseXMLListForSite(Projects\Site $Site): array
    {
        $siteType = $Site->getAttribute('type');
        $cache = $Site->getCachePath() . '/xml-database-list/' . $siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (Exception $Exception) {
        }

        $dbXmlList = QUI::getPackageManager()->getPackageDatabaseXmlList();
        $result = [];

        foreach ($dbXmlList as $package) {
            $file = OPT_DIR . $package . '/database.xml';

            if (!file_exists($file)) {
                continue;
            }

            $Dom = XML::getDomFromXml($file);
            $Path = new DOMXPath($Dom);

            $tableList = $Path->query("//database/projects/table");

            for ($i = 0, $len = $tableList->length; $i < $len; $i++) {
                /* @var $Table DOMElement */
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
                    $types = explode(',', $types);
                }

                if (!empty($types) && is_array($types)) {
                    foreach ($types as $allowedType) {
                        if (!StringUtils::match($allowedType, $siteType)) {
                            continue 2;
                        }
                    }
                }

                // table is ok
                $result[] = [
                    'file' => $file,
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
     * Return database.xml list for the Site Object
     *
     * @param QUI\Projects\Site $Site
     *
     * @return array
     */
    public static function getExtraAttributeListForSite(Projects\Site $Site): array
    {
        $siteType = $Site->getAttribute('type');
        $cache = $Site->getCachePath() . '/xml-database-attributes/' . $siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (Exception $Exception) {
        }


        // global extra attributes
        $siteXmlList = QUI::getPackageManager()->getPackageSiteXmlList();
        $result = [];


        foreach ($siteXmlList as $package) {
            $file = OPT_DIR . $package . '/site.xml';

            if (!file_exists($file)) {
                continue;
            }

            $Dom = XML::getDomFromXml($file);
            $Path = new DOMXPath($Dom);

            $attributes = $Path->query('//site/attributes/attribute');

            /* @var $Attribute DOMElement */
            foreach ($attributes as $Attribute) {
                $result[] = [
                    'attribute' => trim($Attribute->nodeValue),
                    'default' => $Attribute->getAttribute('default')
                ];
            }
        }


        // extra type attributes
        $type = explode(':', $siteType);

        if (isset($type[1])) {
            // Query for site type attributes in the original package of the site type
            $exprPackage = '//site/types/type[@type="' . $type[1] . '"]/attributes/attribute';

            $originalPackageSiteXmlFile = OPT_DIR . $type[0] . '/site.xml';

            $Dom = XML::getDomFromXml($originalPackageSiteXmlFile);
            $Path = new DOMXPath($Dom);

            $attributes = $Path->query($exprPackage);

            /* @var $Attribute DOMElement */
            foreach ($attributes as $Attribute) {
                $result[] = [
                    'attribute' => trim($Attribute->nodeValue),
                    'default' => $Attribute->getAttribute('default')
                ];
            }

            // Query for site type attributes in other packages than the original package of the site type
            $exprOtherPackage = '//site/types/type[@type="' . $type[0] . ':' . $type[1] . '"]/attributes/attribute';

            foreach ($siteXmlList as $package) {
                $siteXmlFile = OPT_DIR . $package . '/site.xml';

                if ($siteXmlFile === $originalPackageSiteXmlFile) {
                    continue;
                }

                if (!file_exists($siteXmlFile)) {
                    continue;
                }

                $Dom = XML::getDomFromXml($siteXmlFile);
                $Path = new DOMXPath($Dom);

                $attributes = $Path->query($exprOtherPackage);

                /* @var $Attribute DOMElement */
                foreach ($attributes as $Attribute) {
                    $result[] = [
                        'attribute' => trim($Attribute->nodeValue),
                        'default' => $Attribute->getAttribute('default')
                    ];
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
     * Return the extra settings from site.xml`s
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit $Site
     * @param string $current
     *
     * @return string
     */
    public static function getExtraSettingsForSite($Site, string $current = ''): string
    {
        if (empty($current)) {
            $current = QUI::getLocale()->getCurrent();
        }

        $siteType = $Site->getAttribute('type');
        $cache = $Site->getCachePath() . '/xml-database-settings/' . $current . '/' . $siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (Exception $Exception) {
        }


        // global extra
        $siteXmlList = QUI::getPackageManager()->getPackageSiteXmlList();
        $result = '';

        foreach ($siteXmlList as $package) {
            $file = OPT_DIR . $package . '/site.xml';

            if (!file_exists($file)) {
                continue;
            }

            $Dom = XML::getDomFromXml($file);
            $Path = new DOMXPath($Dom);
            $cats = $Path->query("//site/settings/category");

            foreach ($cats as $Category) {
                $result .= DOM::parseCategoryToHTML($Category, $current);
            }
        }


        // site type extra xml
        $type = explode(':', $Site->getAttribute('type'));
        $dir = OPT_DIR . $type[0];
        $siteXML = $dir . '/site.xml';

        if (file_exists($siteXML)) {
            $Dom = XML::getDomFromXml($siteXML);
            $Path = new DOMXPath($Dom);

            // type extra
            $cats = $Path->query(
                "//site/types/type[@type='" . $type[1] . "']/settings/category"
            );

            foreach ($cats as $Category) {
                $result .= DOM::parseCategoryToHTML($Category, $current);
            }
        }

        if (!empty($type[1])) {
            // site type extra xml from OTHER packages
            foreach ($siteXmlList as $package) {
                $file = OPT_DIR . $package . '/site.xml';

                if ($file === $siteXML) {
                    continue;
                }

                if (!file_exists($file)) {
                    continue;
                }

                $Dom = XML::getDomFromXml($file);
                $Path = new DOMXPath($Dom);

                // type extra
                $cats = $Path->query(
                    "//site/types/type[@type='" . $type[0] . ':' . $type[1] . "']/settings/category"
                );

                foreach ($cats as $Category) {
                    $result .= DOM::parseCategoryToHTML($Category, $current);
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
     * Return the admin site modules from site.xml`s
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit $Site
     * @return array|boolean
     */
    public static function getAdminSiteModulesFromSite($Site)
    {
        $siteType = $Site->getAttribute('type');
        $cache = $Site->getCachePath() . '/xml-admin-modules/' . $siteType;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (Exception $Exception) {
        }

        // site type extra xml
        $type = explode(':', $Site->getAttribute('type'));
        $dir = OPT_DIR . $type[0];
        $siteXML = $dir . '/site.xml';

        $result = [];

        if (file_exists($siteXML)) {
            $Dom = XML::getDomFromXml($siteXML);
            $Path = new DOMXPath($Dom);

            // type extra
            $modules = $Path->query(
                "//site/types/type[@type='" . $type[1] . "']/admin/js"
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
    public static function isSiteObject($Site): bool
    {
        switch ($Site::class) {
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
     * Return the site object of the quiqqer site link
     * eq: getSiteByLink( index.php?project=test&lang=de&id=1 )
     *
     * @param string $link - index.php?project=test&lang=de&id=1
     *
     * @return Projects\Site
     * @throws Exception
     */
    public static function getSiteByLink(string $link): Projects\Site
    {
        if (!self::isSiteLink($link)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.not.found'
                ),
                705,
                [
                    'method' => 'getSiteByLink',
                    'class' => 'QUI/projects/Site/Utils',
                    'link' => $link
                ]
            );
        }

        $parseUrl = parse_url($link);

        if (empty($parseUrl['query'])) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.not.found'
                ),
                705,
                [
                    'method' => 'getSiteByLink',
                    'class' => 'QUI/projects/Site/Utils',
                    'link' => $link
                ]
            );
        }

        parse_str($parseUrl['query'], $urlQueryParams);

        $Project = QUI::getProject(
            $urlQueryParams['project'],
            $urlQueryParams['lang']
        );

        return $Project->get($urlQueryParams['id']);
    }

    /**
     * is the link a quiqqer site link?
     * eq: index.php?project=test&lang=de&id=1
     *
     * @param string $link - index.php?project=test&lang=de&id=1
     *
     * @return boolean
     */
    public static function isSiteLink(string $link): bool
    {
        if (!str_contains($link, 'index.php')) {
            return false;
        }

        if (!str_contains($link, 'project=')) {
            return false;
        }

        if (!str_contains($link, 'lang=')) {
            return false;
        }

        if (!str_contains($link, 'id=')) {
            return false;
        }

        return true;
    }

    /**
     * Return a site by an url (relative url)
     *
     * @param Project $Project
     * @param $link
     * @return Projects\Site
     *
     * @throws Exception
     */
    public static function getSiteByUrl(Project $Project, $link): Projects\Site
    {
        $link = str_replace('.html', '', $link);
        $link = trim($link);
        $link = trim($link, '/');
        $parts = explode('/', $link);

        $Site = $Project->firstChild();

        foreach ($parts as $part) {
            $id = $Site->getChildIdByName($part);
            $Site = $Project->get($id);
        }

        return $Site;
    }

    /**
     * Return sites from a site list
     * site list from controls/projects/project/site/Select
     *
     * @param Project $Project - Project of the sites
     * @param array|string $list - list from controls/projects/project/site/Select
     * @param array $params - order / sort params
     *
     * @return array
     * @throws QUI\Database\Exception
     */
    public static function getSitesByInputList(
        Project $Project,
        $list,
        array $params = []
    ): array {
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

        if (is_string($list)) {
            $sitetypes = explode(';', $list);
        } else {
            if (is_array($list)) {
                $sitetypes = $list;
            } else {
                return [];
            }
        }

        $ids = [];
        $types = [];
        $parents = [];
        $where = [];

        foreach ($sitetypes as $sitetypeEntry) {
            if (is_numeric($sitetypeEntry)) {
                $ids[] = (int)$sitetypeEntry;
                continue;
            }

            if (
                str_starts_with($sitetypeEntry, 'p')
                && !str_contains($sitetypeEntry, '/')
                && !str_contains($sitetypeEntry, ':')
            ) {
                $parents[] = str_replace('p', '', $sitetypeEntry);
                continue;
            }

            $types[] = $sitetypeEntry;
        }

        // query params
        if (!empty($ids)) {
            $where['id'] = [
                'type' => 'IN',
                'value' => $ids
            ];
        }

        if (!empty($types)) {
            $where['type'] = [
                'type' => 'IN',
                'value' => $types
            ];
        }

        // parents are set
        if (count($parents)) {
            foreach ($parents as $parentId) {
                try {
                    $Parent = $Project->get((int)$parentId);

                    $children = $Parent->getChildrenIds([
                        'order' => $order
                    ]);

                    $ids = array_merge($ids, $children);
                } catch (Exception) {
                }
            }

            if (!count($ids)) {
                if (isset($params['count']) && $params['count']) {
                    return [['count' => 0]];
                }

                return [];
            }

            $where['id'] = [
                'type' => 'IN',
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
            'limit' => $limit,
            'order' => $order
        ]);
    }

    /**
     * Return the rewritten link
     * eq: rewriteSiteLink( index.php?project=test&lang=de&id=1 )
     *
     * @param string $link - Project of the sites
     *
     * @return string
     *
     * @throws Exception
     */
    public static function rewriteSiteLink(string $link): string
    {
        if (!self::isSiteLink($link)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.not.found'
                ),
                705,
                [
                    'method' => 'rewriteSiteLink',
                    'class' => 'QUI/projects/Site/Utils',
                    'link' => $link
                ]
            );
        }

        $parseUrl = parse_url($link);

        if (empty($parseUrl['query'])) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.not.found'
                ),
                705,
                [
                    'method' => 'rewriteSiteLink',
                    'class' => 'QUI/projects/Site/Utils',
                    'link' => $link
                ]
            );
        }

        // html_entity_decode because -> &nbsp; in index.php links
        parse_str(html_entity_decode($parseUrl['query']), $urlQueryParams);

        return QUI::getRewrite()->getOutput()->getSiteUrl($urlQueryParams);
    }
}
