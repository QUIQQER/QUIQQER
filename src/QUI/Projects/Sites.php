<?php

/**
 * This file contains the \QUI\Projects\Sites
 */

namespace QUI\Projects;

use DOMElement;
use DOMXPath;
use QUI;
use QUI\Controls\Buttons\Button;
use QUI\Controls\Buttons\Separator;
use QUI\Controls\Toolbar\Bar;
use QUI\Controls\Toolbar\Tab;
use QUI\Exception;
use QUI\Projects\Site\Edit;
use QUI\Utils\Text\XML;

use function count;
use function explode;
use function file_exists;
use function implode;
use function in_array;
use function is_numeric;
use function preg_match;
use function strtolower;
use function trim;
use function method_exists;

/**
 * Helper for the Site Object
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Sites
{
    /**
     * JavaScript buttons, depending on the side of the user
     */
    public static function getButtons(Site\Edit $Site): Bar
    {
        $Toolbar = new Bar([
            'name' => '_Toolbar'
        ]);

        $gl = 'quiqqer/core';

        $Toolbar->appendChild(
            new Button([
                'name' => 'save',
                'textimage' => 'fa fa-save',
                'text' => QUI::getLocale()->get($gl, 'projects.project.site.btn.save.text'),
                'onclick' => 'Panel.save',
                'help' => QUI::getLocale()->get($gl, 'projects.project.site.btn.save.help'),
                'alt' => QUI::getLocale()->get($gl, 'projects.project.site.btn.save.alt'),
                'title' => QUI::getLocale()->get($gl, 'projects.project.site.btn.save.title')
            ])
        );

        // wenn die Seite bearbeitet wird
        if (
            $Site->isLockedFromOther()
            || !$Site->hasPermission('quiqqer.projects.site.edit')
        ) {
            $Toolbar->getElementByName('save')->setDisable();
        }

        // Wenn das Bearbeiten Recht vorhanden ist
        if (
            $Site->hasPermission('quiqqer.projects.site.edit')
            && !$Site->isLockedFromOther()
        ) {
            $Toolbar->appendChild(
                new Separator([
                    'name' => 'separator'
                ])
            );

            $Status = new Button([
                'name' => 'status',
                'aimage' => 'fa fa-check',
                'atext' => QUI::getLocale()->get($gl, 'projects.project.site.btn.activate.text'),
                'aonclick' => 'Panel.getSite().activate',
                'dimage' => 'fa fa-remove',
                'dtext' => QUI::getLocale()->get($gl, 'projects.project.site.btn.deactivate.text'),
                'donclick' => 'Panel.getSite().activate'
            ]);

            if ($Site->getAttribute('active')) {
                $Status->setAttributes([
                    'textimage' => 'fa fa-remove',
                    'text' => QUI::getLocale()->get($gl, 'projects.project.site.btn.deactivate.text'),
                    'onclick' => 'Panel.deactivate'
                ]);
            } else {
                $Status->setAttributes([
                    'textimage' => 'fa fa-check',
                    'text' => QUI::getLocale()->get($gl, 'projects.project.site.btn.activate.text'),
                    'onclick' => 'Panel.activate'
                ]);
            }

            $Toolbar->appendChild($Status);
        }

        // preview
        $Toolbar->appendChild(
            new Separator([
                'name' => 'separator'
            ])
        );

        $Toolbar->appendChild(
            new Button([
                'name' => 'preview',
                'textimage' => 'fa fa-eye',
                'text' => QUI::getLocale()->get($gl, 'projects.project.site.btn.preview.text'),
                'onclick' => 'Panel.openPreview'
            ])
        );

        // delete site
        $Toolbar->appendChild(
            new Button([
                'name' => 'delete',
                'icon' => 'fa fa-trash-o',
                //'text'      => QUI::getLocale()->get( $gl, 'projects.project.site.btn.delete.text' ),
                'onclick' => 'Panel.del',
                'help' => QUI::getLocale()->get($gl, 'projects.project.site.btn.delete.help'),
                'title' => QUI::getLocale()->get($gl, 'projects.project.site.btn.delete.title'),
                'alt' => QUI::getLocale()->get($gl, 'projects.project.site.btn.delete.alt')
            ])
        );

        // Wenn die Seite bearbeitet wird
        // oder wenn das Löschen Recht nicht vorhanden ist
        if (
            $Site->isLockedFromOther()
            || !$Site->hasPermission('quiqqer.projects.site.del')
        ) {
            $Toolbar->getElementByName('delete')->setDisable();
        }

        // new sub site
        $Toolbar->appendChild(
            new Button([
                'name' => 'new',
                'icon' => 'fa fa-file-o',
                //'text'      => QUI::getLocale()->get( $gl, 'projects.project.site.btn.new.text' ),
                'onclick' => 'Panel.createNewChild',
                'help' => QUI::getLocale()->get($gl, 'projects.project.site.btn.new.help'),
                'alt' => QUI::getLocale()->get($gl, 'projects.project.site.btn.new.alt'),
                'title' => QUI::getLocale()->get($gl, 'projects.project.site.btn.new.title')
            ])
        );

        if (!$Site->hasPermission('quiqqer.projects.site.new')) {
            $Toolbar->getElementByName('new')->setDisable();
        }


        // Tabs der Plugins hohlen
        // @todo über xml's oder neue apis
        /*
        $Plugins = self::getPlugins( $Site );

        foreach ( $Plugins as $Plugin )
        {
            if ( method_exists( $Plugin, 'setButtons' ) ) {
                $Plugin->setButtons( $Toolbar, $Site );
            }
        }
        */

        return $Toolbar;
    }

    /**
     * Get the tab of a site
     *
     * @throws Exception
     */
    public static function getTab(string $tabname, Edit $Site): bool | Tab
    {
        $Toolbar = self::getTabs($Site);
        $Tab = $Toolbar->getElementByName($tabname);

        if ($Tab === false) {
            throw new Exception('The tab could not be found.');
        }

        return $Tab;
    }

    /**
     * Return the tabs of a site
     */
    public static function getTabs(QUI\Interfaces\Projects\Site $Site): Bar
    {
        $Tabbar = new Bar([
            'name' => '_Tabbar'
        ]);

        if (method_exists($Site, 'isLockedFromOther') && $Site->isLockedFromOther()) {
            $Tabbar->appendChild(
                new Tab([
                    'name' => 'information',
                    'text' => QUI::getLocale()->get(
                        'quiqqer/core',
                        'projects.project.site.information'
                    ),
                    'template' => SYS_DIR . 'template/site/information_norights.html',
                    'icon' => URL_BIN_DIR . '16x16/page.png'
                ])
            );

            return $Tabbar;
        }


        if (
            $Site->hasPermission('quiqqer.projects.site.view')
            && $Site->hasPermission('quiqqer.projects.site.edit')
        ) {
            $Tabbar->appendChild(
                new Tab([
                    'name' => 'information',
                    'text' => QUI::getLocale()->get(
                        'quiqqer/core',
                        'projects.project.site.information'
                    ),
                    'template' => SYS_DIR . 'template/site/information.html',
                    'icon' => 'fa fa-file-o'
                ])
            );
        } elseif ($Site->hasPermission('quiqqer.projects.site.view') === false) {
            $Tabbar->appendChild(
                new Tab([
                    'name' => 'information',
                    'text' => QUI::getLocale()->get(
                        'quiqqer/core',
                        'projects.project.site.information'
                    ),
                    'template' => SYS_DIR . 'template/site/noview.html',
                    'icon' => 'fa fa-file-o'
                ])
            );

            return $Tabbar;
        } else // Wenn kein Bearbeitungsrecht aber Ansichtsrecht besteht
        {
            $Tabbar->appendChild(
                new Tab([
                    'name' => 'information',
                    'text' => QUI::getLocale()->get(
                        'quiqqer/core',
                        'projects.project.site.information'
                    ),
                    'template' => SYS_DIR
                        . 'template/site/information_norights.html',
                    'icon' => 'fa fa-file-o'
                ])
            );

            return $Tabbar;
        }

        $showDefaultContentTab = true;
        $type = $Site->getAttribute('type');
        $siteTypeParts = explode(':', $type, 2);

        if (isset($siteTypeParts[0], $siteTypeParts[1])) {
            $siteXmlFile = OPT_DIR . $siteTypeParts[0] . '/site.xml';

            if (file_exists($siteXmlFile)) {
                $Dom = XML::getDomFromXml($siteXmlFile);
                $Path = new DOMXPath($Dom);
                $TypeNodes = $Path->query(
                    "//site/types/type[@type='" . $siteTypeParts[1] . "' or @type='" . $type . "']"
                );

                foreach ($TypeNodes as $TypeNode) {
                    if (!$TypeNode instanceof DOMElement) {
                        continue;
                    }

                    if (
                        $TypeNode->hasAttribute('content')
                        && (int)$TypeNode->getAttribute('content') === 0
                    ) {
                        $showDefaultContentTab = false;
                        break;
                    }
                }
            }
        }

        // Inhaltsreiter
        if ($showDefaultContentTab) {
            // Inhaltsreiter
            $Tabbar->appendChild(
                new Tab([
                    'name' => 'content',
                    'text' => QUI::getLocale()->get(
                        'quiqqer/core',
                        'projects.project.site.content'
                    ),
                    'icon' => 'fa fa-file-text-o'
                ])
            );
        }

        // Einstellungen
        $Tabbar->appendChild(
            new Tab([
                'name' => 'settings',
                'text' => QUI::getLocale()->get(
                    'quiqqer/core',
                    'projects.project.site.settings'
                ),
                'icon' => 'fa fa-cog',
                'template' => SYS_DIR . 'template/site/settings.html'
            ])
        );

        // site type tabs
        $type = $Site->getAttribute('type');
        $types = explode(':', $type);

        $file = OPT_DIR . $types[0] . '/site.xml';

        if (file_exists($file)) {
            $Dom = XML::getDomFromXml($file);
            $Path = new DOMXPath($Dom);

            QUI\Utils\DOM::addTabsToToolbar(
                $Path->query("//site/types/type[@type='" . $types[1] . "']/tab"),
                $Tabbar
            );

            QUI\Utils\DOM::addTabsToToolbar(
                $Path->query("//site/types/type[@type='" . $type . "']/tab"),
                $Tabbar
            );
        }

        // module / package extensions
        $packages = QUI::getPackageManager()->getInstalled();


        // packages site types
        foreach ($packages as $package) {
            // templates would be seperated
            if ($package['type'] == 'quiqqer-template') {
                continue;
            }

            if ($package['name'] === $types[0]) {
                continue;
            }


            $file = OPT_DIR . $package['name'] . '/site.xml';

            if (!file_exists($file)) {
                continue;
            }

            $Dom = XML::getDomFromXml($file);
            $Path = new DOMXPath($Dom);

            QUI\Utils\DOM::addTabsToToolbar(
                $Path->query("//site/types/type[@type='" . $type . "']/tab"),
                $Tabbar
            );
        }


        // Global tabs
        foreach ($packages as $package) {
            // templates would be seperated
            if ($package['type'] == 'quiqqer-template') {
                continue;
            }

            $file = OPT_DIR . $package['name'] . '/site.xml';

            if (!file_exists($file)) {
                continue;
            }

            QUI\Utils\DOM::addTabsToToolbar(
                XML::getSiteTabsFromDom(
                    XML::getDomFromXml($file)
                ),
                $Tabbar
            );
        }

        // project template tabs
        $Project = $Site->getProject();
        $templates = Manager::getRelatedTemplates($Project);

        foreach ($templates as $template) {
            if (empty($template)) {
                continue;
            }

            if (!isset($template['name'])) {
                continue;
            }

            $file = OPT_DIR . $template['name'] . '/site.xml';

            if (!file_exists($file)) {
                continue;
            }

            QUI\Utils\DOM::addTabsToToolbar(
                XML::getSiteTabsFromDom(
                    XML::getDomFromXml($file)
                ),
                $Tabbar
            );
        }


        return $Tabbar;
    }

    /**
     * Search sites
     *
     * $params['Project'] - \QUI\Projects\Project
     * $params['project'] - string - project name
     *
     * $params['limit'] - max entries
     * $params['page'] - number of the page
     * $params['fields'] - searchable fields
     * $params['count'] - true/false result as a count?
     *
     * @throws Exception
     */
    public static function search(string $search, array $params = []): array | int
    {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();

        $page = 1;
        $limit = 50;
        $projects = [];
        $fields = ['id', 'title', 'name'];

        $selectList = [
            'id',
            'name',
            'title',
            'short',
            'content',
            'type',
            'c_date',
            'e_date',
            'c_user',
            'e_user',
            'active'
        ];

        // projekt
        if (!empty($params['Project'])) {
            $projects[] = $params['Project'];
        } elseif (!empty($params['project'])) {
            $projects[] = QUI::getProject($params['project']);
        } else {
            // search all projects
            $projects = QUI::getProjectManager()->getProjects(true);
        }

        // limits
        if (isset($params['limit'])) {
            $limit = (int)$params['limit'];
        }

        if (isset($params['page']) && (int)$params['page']) {
            $page = (int)$params['page'];
        }

        // fields
        if (!empty($params['fields'])) {
            $fields = [];
            $_fields = explode(',', $params['fields']);

            foreach ($_fields as $field) {
                switch ($field) {
                    case 'id':
                    case 'name':
                    case 'title':
                    case 'short':
                    case 'content':
                    case 'c_date':
                    case 'e_date':
                    case 'c_user':
                    case 'e_user':
                    case 'active':
                        $fields[] = $field;
                        break;
                }
            }
        }


        // find the search tables
        $tables = [];

        foreach ($projects as $Project) {
            /* @var $Project Project */
            $langs = $Project->getAttribute('langs');
            $name = $Project->getName();

            foreach ($langs as $lang) {
                $tables[] = [
                    'table' => QUI_DB_PRFX . $name . '_' . $lang . '_sites',
                    'lang' => $lang,
                    'project' => $name
                ];
            }
        }

        $rawSearch = $search;
        $likeSearch = "%" . $rawSearch . "%";
        $textFields = ["name", "title", "short", "content", "c_user", "e_user"];
        $numericFields = ["id"];
        $dateFields = ["c_date", "e_date"];
        $booleanSearch = null;
        $normalizedSearch = strtolower(trim($rawSearch));

        if (in_array($normalizedSearch, ["1", "true", "yes", "y", "on"], true)) {
            $booleanSearch = 1;
        } elseif (in_array($normalizedSearch, ["0", "false", "no", "n", "off"], true)) {
            $booleanSearch = 0;
        }

        $dateSearch = false;

        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $rawSearch)) {
            $dateSearch = \DateTimeImmutable::createFromFormat("!Y-m-d", $rawSearch);
        }

        $queryParts = [];
        $queryParams = [];
        $tableIndex = 0;

        foreach ($tables as $table) {
            $whereParts = [];
            $projectParam = "project" . $tableIndex;

            foreach ($fields as $field) {
                $quotedField = $Platform->quoteSingleIdentifier($field);
                $searchParam = "search" . $tableIndex . "_" . $field;

                if (in_array($field, $textFields, true)) {
                    $whereParts[] = $quotedField . " LIKE :" . $searchParam;
                    $queryParams[$searchParam] = $likeSearch;
                    continue;
                }

                if (in_array($field, $numericFields, true) && is_numeric($rawSearch)) {
                    $whereParts[] = $quotedField . " = :" . $searchParam;
                    $queryParams[$searchParam] = (int)$rawSearch;
                    continue;
                }

                if ($field === "active" && $booleanSearch !== null) {
                    $whereParts[] = $quotedField . " = :" . $searchParam;
                    $queryParams[$searchParam] = $booleanSearch;
                    continue;
                }

                if (in_array($field, $dateFields, true) && $dateSearch instanceof \DateTimeImmutable) {
                    $dateStartParam = $searchParam . "_start";
                    $dateEndParam = $searchParam . "_end";
                    $whereParts[] = "(" . $quotedField . " >= :" . $dateStartParam . " AND " . $quotedField . " < :" . $dateEndParam . ")";
                    $queryParams[$dateStartParam] = $dateSearch->format("Y-m-d 00:00:00");
                    $queryParams[$dateEndParam] = $dateSearch->modify("+1 day")->format("Y-m-d 00:00:00");
                }
            }

            if (empty($whereParts)) {
                $tableIndex++;
                continue;
            }

            $queryParams[$projectParam] = $table["project"] . " (" . $table["lang"] . ")";

            $queryParts[] = "(SELECT\n                    :" . $projectParam . " AS " . $Platform->quoteSingleIdentifier("project") . ",\n                    " . implode(
                ",",
                array_map(
                    static fn($field) => $Platform->quoteSingleIdentifier($field),
                    $selectList
                )
            ) . "\n                FROM " . $Platform->quoteSingleIdentifier($table["table"]) . "\n                WHERE (" . implode(" OR ", $whereParts) . ")\n                    AND " . $Platform->quoteSingleIdentifier("deleted") . " = 0)";

            $tableIndex++;
        }

        if (empty($queryParts)) {
            return isset($params["count"]) ? 0 : [];
        }

        $query = implode(" UNION ", $queryParts);

        // limit, pages
        if (!isset($params['count'])) {
            $page = $page - 1;

            if ($page <= 0) {
                $page = 0;
            }

            $query = $Platform->modifyLimitQuery($query, $limit, $page * $limit);
        }


        $result = $Connection->executeQuery($query, $queryParams)->fetchAllAssociative();

        if (isset($params['count'])) {
            return count($result);
        }

        return $result;
    }
}
