<?php

/**
 * This file contains the \QUI\Projects\Sites
 */

namespace QUI\Projects;

use QUI;
use QUI\Controls\Toolbar;
use QUI\Controls\Buttons\Button;
use QUI\Controls\Buttons\Seperator;

/**
 * Helper for the Site Object
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects
 * @licence For copyright and license information, please view the /README.md
 */
class Sites
{
    /**
     * JavaScript buttons, depending on the side of the user
     *
     * @param \QUI\Projects\Site\Edit $Site
     *
     * @return \QUI\Controls\Toolbar\Bar
     */
    static function getButtons(Site\Edit $Site)
    {
        $Toolbar = new Toolbar\Bar(array(
            'name' => '_Toolbar'
        ));

        $gl = 'quiqqer/system';

        $Toolbar->appendChild(
            new Button(array(
                'name'      => '_Save',
                'textimage' => 'icon-save',
                'text'      => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.save.text'),
                'onclick'   => 'Panel.save',
                'help'      => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.save.help'),
                'alt'       => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.save.alt'),
                'title'     => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.save.title')
            ))
        );

        // wenn die Seite bearbeitet wird
        if ($Site->isLockedFromOther()
            || $Site->hasPermission('quiqqer.projects.site.edit') == false
        ) {
            $Toolbar->getElementByName('_Save')->setDisable();
        }

        // Wenn das Bearbeiten Recht vorhanden ist
        if ($Site->hasPermission('quiqqer.projects.site.edit')
            && !$Site->isLockedFromOther()
        ) {
            $Toolbar->appendChild(
                new Seperator(array(
                    'name' => '_sep'
                ))
            );

            $Status = new Button(array(
                'name'     => 'status',
                'aimage'   => 'fa fa-check icon-ok',
                'atext'    => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.activate.text'),
                'aonclick' => 'Panel.getSite().activate',
                'dimage'   => 'icon-remove',
                'dtext'    => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.deactivate.text'),
                'donclick' => 'Panel.getSite().activate'
            ));

            if ($Site->getAttribute('active')) {
                $Status->setAttributes(array(
                    'textimage' => 'icon-remove',
                    'text'      => QUI::getLocale()->get($gl,
                        'projects.project.site.btn.deactivate.text'),
                    'onclick'   => 'Panel.getSite().deactivate'
                ));

            } else {
                $Status->setAttributes(array(
                    'textimage' => 'fa fa-check icon-ok',
                    'text'      => QUI::getLocale()->get($gl,
                        'projects.project.site.btn.activate.text'),
                    'onclick'   => 'Panel.getSite().activate'
                ));
            }

            $Toolbar->appendChild($Status);
        }

        // preview
        $Toolbar->appendChild(
            new Seperator(array(
                'name' => '_sep'
            ))
        );

        $Toolbar->appendChild(
            new Button(array(
                'name'      => '_Preview',
                'textimage' => 'fa fa-eye icon-eye-open',
                'text'      => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.preview.text'),
                'onclick'   => 'Panel.openPreview'
            ))
        );

        // delete site
        $Toolbar->appendChild(
            new Button(array(
                'name'    => '_Del',
                'icon'    => 'fa fa-trash-o icon-trash',
                //'text'      => QUI::getLocale()->get( $gl, 'projects.project.site.btn.delete.text' ),
                'onclick' => 'Panel.del',
                'help'    => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.delete.help'),
                'title'   => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.delete.title'),
                'alt'     => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.delete.alt')
            ))
        );

        // Wenn die Seite bearbeitet wird
        // oder wenn das Löschen Recht nicht vorhanden ist
        if ($Site->isLockedFromOther()
            || $Site->hasPermission('quiqqer.projects.site.del') == false
        ) {
            $Toolbar->getElementByName('_Del')->setDisable();
        }

        // new sub site
        $Toolbar->appendChild(
            new Button(array(
                'name'    => '_New',
                'icon'    => 'fa fa-file-o icon-file-alt',
                //'text'      => QUI::getLocale()->get( $gl, 'projects.project.site.btn.new.text' ),
                'onclick' => 'Panel.createNewChild',
                'help'    => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.new.help'),
                'alt'     => QUI::getLocale()
                    ->get($gl, 'projects.project.site.btn.new.alt'),
                'title'   => QUI::getLocale()->get($gl,
                    'projects.project.site.btn.new.title')
            ))
        );

        if ($Site->hasPermission('quiqqer.projects.site.new') == false) {
            $Toolbar->getElementByName('_New')->setDisable();
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
     * Return the tabs of a site
     *
     * @param \QUI\Projects\Site\Edit $Site
     *
     * @return \QUI\Controls\Toolbar\Bar
     */
    static function getTabs(Site\Edit $Site)
    {
        $Tabbar = new Toolbar\Bar(array(
            'name' => '_Tabbar'
        ));


        // Wenn die Seite bearbeitet wird
        if ($Site->isLockedFromOther()) {
            $Tabbar->appendChild(
                new Toolbar\Tab(array(
                    'name'     => 'information',
                    'text'     => \QUI::getLocale()->get(
                        'quiqqer/system',
                        'projects.project.site.information'
                    ),
                    'template' => SYS_DIR
                                  . 'template/site/information_norights.html',
                    'icon'     => URL_BIN_DIR . '16x16/page.png'
                ))
            );

            return $Tabbar;
        }


        if ($Site->hasPermission('quiqqer.projects.site.view')
            && $Site->hasPermission('quiqqer.projects.site.edit')
        ) {
            $Tabbar->appendChild(
                new Toolbar\Tab(array(
                    'name'     => 'information',
                    'text'     => QUI::getLocale()->get(
                        'quiqqer/system',
                        'projects.project.site.information'
                    ),
                    'template' => SYS_DIR . 'template/site/information.html',
                    'icon'     => 'fa fa-file-o icon-file-alt'
                ))
            );

        } elseif ($Site->hasPermission('quiqqer.projects.site.view') === false
        ) {
            $Tabbar->appendChild(
                new Toolbar\Tab(array(
                    'name'     => 'information',
                    'text'     => QUI::getLocale()->get(
                        'quiqqer/system',
                        'projects.project.site.information'
                    ),
                    'template' => SYS_DIR . 'template/site/noview.html',
                    'icon'     => 'fa fa-file-o icon-file-alt'
                ))
            );

            return $Tabbar;

        } else // Wenn kein Bearbeitungsrecht aber Ansichtsrecht besteht
        {
            $Tabbar->appendChild(
                new Toolbar\Tab(array(
                    'name'     => 'information',
                    'text'     => QUI::getLocale()->get(
                        'quiqqer/system',
                        'projects.project.site.information'
                    ),
                    'template' => SYS_DIR
                                  . 'template/site/information_norights.html',
                    'icon'     => 'fa fa-file-o icon-file-alt'
                ))
            );

            return $Tabbar;
        }

        // Inhaltsreiter
        $Tabbar->appendChild(
            new Toolbar\Tab(array(
                'name' => 'content',
                'text' => QUI::getLocale()->get(
                    'quiqqer/system',
                    'projects.project.site.content'
                ),
                'icon' => 'fa fa-file-text-o icon-file-alt'
            ))
        );

        // Einstellungen
        $Tabbar->appendChild(
            new Toolbar\Tab(array(
                'name'     => 'settings',
                'text'     => \QUI::getLocale()->get(
                    'quiqqer/system',
                    'projects.project.site.settings'
                ),
                'icon'     => 'icon-cog',
                'template' => SYS_DIR . 'template/site/settings.html'
            ))
        );

        // site type tabs
        $type  = $Site->getAttribute('type');
        $types = explode(':', $type);

        $file = OPT_DIR . $types[0] . '/site.xml';

        if (file_exists($file)) {
            $Dom  = QUI\Utils\XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

            QUI\Utils\DOM::addTabsToToolbar(
                $Path->query("//site/types/type[@type='" . $types[1] . "']/tab"),
                $Tabbar
            );
        }


        // Global tabs
        $packages = QUI::getPackageManager()->getInstalled();

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
                QUI\Utils\XML::getSiteTabsFromDom(
                    QUI\Utils\XML::getDomFromXml($file)
                ),
                $Tabbar
            );
        }

        // project template tabs
        $Project   = $Site->getProject();
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
                QUI\Utils\XML::getSiteTabsFromDom(
                    QUI\Utils\XML::getDomFromXml($file)
                ),
                $Tabbar
            );
        }


        return $Tabbar;
    }

    /**
     * Get the tab of a site
     *
     * @param string $tabname - Name of the Tab
     * @param Site\Edit $Site
     *
     * @throws \QUI\Exception
     * @return \QUI\Controls\Toolbar\Tab
     */
    static function getTab($tabname, $Site)
    {
        $Toolbar = self::getTabs($Site);
        $Tab     = $Toolbar->getElementByName($tabname);

        if ($Tab === false) {
            throw new QUI\Exception('The tab could not be found.');
        }

        return $Tab;
    }

    /**
     * Returns all plugins that has the site
     *
     * @param \QUI\Projects\Site $Site
     *
     * @return array
     *
     * @todo schauen wegen admin bereich
     * @deprecated
     */
    static function getPlugins($Site)
    {
        // Globale requireds
        $Project = $Site->getProject();
        $Plugins = QUI::getPluginManager();

        $types  = $Project->getTypes();
        $result = array();

        // Main Plugins
        foreach ($types as $key => $type) {
            $result[] = $Plugins->get($key);
        }

        // Seitentypen Einbindungen
        if ($Site->getAttribute('type')
            && $Site->getAttribute('type') != 'standard'
        ) {
            $result[] = $Plugins->getPluginByType(
                $Site->getAttribute('type')
            );
        }

        return $result;
    }

    /**
     * Search sites
     *
     * @param string $search
     * @param array $params
     *
     * $params['Project'] - \QUI\Projects\Project
     * $params['project'] - string - project name
     *
     * $params['limit'] - max entries
     * $params['page'] - number of the page
     * $params['fields'] - searchable fields
     * $params['count'] - true/false result as a count?
     *
     * @return array
     */
    static function search($search, $params = array())
    {
        $DataBase = QUI::getDataBase();

        $page     = 1;
        $limit    = 50;
        $Project  = null;
        $projects = array();
        $fields   = array('id', 'title', 'name');

        $selectList = array(
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
        );

        // projekt
        if (isset($params['Project']) && !empty($params['Project'])) {
            $projects[] = $params['Project'];

        } else {
            if (isset($params['project']) && !empty($params['project'])) {
                $projects[] = QUI::getProject($params['project']);

            } else {
                // search all projects
                $projects = QUI::getProjectManager()->getProjects(true);
            }
        }

        // limits
        if (isset($params['limit'])) {
            $limit = (int)$params['limit'];
        }

        if (isset($params['page']) && (int)$params['page']) {
            $page = (int)$params['page'];
        }

        // fields
        if (isset($params['fields']) && !empty($params['fields'])) {
            $fields  = array();
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

                    default:
                        continue;
                }

            }
        }


        // find the search tables
        $tables = array();

        foreach ($projects as $Project) {
            /* @var $Project Project */
            $langs = $Project->getAttribute('langs');
            $name  = $Project->getName();

            foreach ($langs as $lang) {
                $tables[] = array(
                    'table'   => QUI_DB_PRFX . $name . '_' . $lang . '_sites',
                    'lang'    => $lang,
                    'project' => $name
                );
            }
        }

        $search = '%' . $search . '%';
        $query  = '';

        foreach ($tables as $table) {
            $where = '';

            foreach ($fields as $field) {
                $where .= $field . ' LIKE :search';

                if ($field !== end($fields)) {
                    $where .= ' OR ';
                }
            }

            $query
                .= '(SELECT
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


        $PDO  = $DataBase->getPDO();
        $Stmt = $PDO->prepare($query);

        $Stmt->execute(array(
            ':search' => $search
        ));

        $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (isset($params['count'])) {
            return count($result);
        }

        // \QUI\System\Log::write( $query );
        // \QUI\System\Log::writeRecursive( $result );

        return $result;
    }
}
