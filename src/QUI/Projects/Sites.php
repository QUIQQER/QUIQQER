<?php

/**
 * This file contains the \QUI\Projects\Sites
 */

namespace QUI\Projects;

use DOMXPath;
use PDO;
use QUI;
use QUI\Controls\Buttons\Button;
use QUI\Controls\Buttons\Separator;
use QUI\Controls\Toolbar\Bar;
use QUI\Controls\Toolbar\Tab;
use QUI\Exception;
use QUI\Projects\Site\Edit;
use QUI\Utils\Text\XML;

use function count;
use function end;
use function explode;
use function file_exists;
use function implode;
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
    public static function getTab(string $tabname, Edit $Site): bool|Tab
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
    public static function search(string $search, array $params = []): array|int
    {
        $DataBase = QUI::getDataBase();

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

        $search = '%' . $search . '%';
        $query = '';

        foreach ($tables as $table) {
            $where = '';

            foreach ($fields as $field) {
                $where .= $field . ' LIKE :search';

                if ($field !== end($fields)) {
                    $where .= ' OR ';
                }
            }

            $query .= '(SELECT
                            "' . $table['project'] . ' (' . $table['lang'] . ')" as "project",
                            ' . implode(',', $selectList) . '
                        FROM `' . $table['table'] . '`
                        WHERE (' . $where . ') AND deleted = 0) ';

            if ($table !== end($tables)) {
                $query .= ' UNION ';
            }
        }

        // limit, pages
        if (!isset($params['count'])) {
            $page = $page - 1;

            if ($page <= 0) {
                $page = 0;
            }

            $query .= ' LIMIT ' . ($page * $limit) . ',' . $limit;
        }


        $PDO = $DataBase->getPDO();
        $Stmt = $PDO->prepare($query);

        $Stmt->execute([
            ':search' => $search
        ]);

        $result = $Stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($params['count'])) {
            return count($result);
        }

        return $result;
    }
}
