<?php

/**
 * This file contains the \QUI\Projects\Sites
 */

namespace QUI\Projects;

/**
 * Helper for the Site Object
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects
 */

class Sites
{
    /**
     * JavaScript buttons, depending on the side of the user
     *
     * @param \QUI\Projects\Site\Edit $Site
     * @return \QUI\Controls\Toolbar\Bar
     */
    static function getButtons(\QUI\Projects\Site\Edit $Site)
    {
        $Toolbar = new \QUI\Controls\Toolbar\Bar(array(
            'name' => '_Toolbar'
        ));

        // Standard Buttons
        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Button(array(
                'name'      => '_New',
                'textimage' => 'icon-page-alt',
                'text'      => 'Neu',
                'onclick'   => 'Panel.createNewChild',
                'help'      => 'Erzeugen Sie eine neue Unterseite',
                'alt'       => 'Erzeugen Sie eine neue Unterseite',
                'title'     => 'Erzeugen Sie eine neue Unterseite'
            ))
        );

        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Button(array(
                'name'      => '_Save',
                'textimage' => 'icon-save',
                'text'      => 'Speichern',
                'onclick'   => 'Panel.save',
                'help'      => 'Speichern Sie Ihre Änderungen.',
                'alt'       => 'Speichern Sie Ihre Änderungen.',
                'title'     => 'Speichern Sie Ihre Änderungen.'
            ))
        );

        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Button(array(
                'name'      => '_Del',
                'textimage' => 'icon-trash',
                'text'      => 'Löschen',
                'onclick'   => 'Panel.del',
                'help'      => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden',
                'title'     => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden',
                'alt'       => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden'
            ))
        );

        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Seperator(array(
                'name' => '_sep'
            ))
        );

        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Button(array(
                'name'      => '_Preview',
                'textimage' => 'icon-eye-open',
                'text'      => 'Vorschau',
                'onclick'   => 'Panel.openPreview'
            ))
        );

        $New  = $Toolbar->getElementByName( '_New' );
        $Save = $Toolbar->getElementByName( '_Save' );
        $Del  = $Toolbar->getElementByName( '_Del' );

        if ( $Site->isMarcate() ) // wenn die Seite bearbeitet wird
        {
            $Save->setDisable();
            $Del->setDisable();
        }

        // Wenn das Kind erstellen Recht nicht vorhanden ist
        if ( $Site->hasPermission( 'quiqqer.projects.site.new' ) ) {
            $New->setDisable();
        }

        // Wenn das Bearbeiten Recht nicht vorhanden ist
        if ( $Site->hasPermission( 'quiqqer.projects.site.edit' ) == false ) {
            $Save->setDisable();
        }

        // Wenn das Löschen Recht nicht vorhanden ist
        if ( $Site->hasPermission( 'quiqqer.projects.site.del' ) == false ) {
            $Del->setDisable();
        }

        // Wenn das Bearbeiten Recht vorhanden ist
        if ( $Site->hasPermission( 'quiqqer.projects.site.edit') && !$Site->isMarcate() )
        {
            $Toolbar->appendChild(
                new \QUI\Controls\Buttons\Seperator(array(
                    'name' => '_sep'
                ))
            );

            $Status = new \QUI\Controls\Buttons\Button(array(
                'name'     => 'status',
                'aimage'   => 'icon-ok',
                'atext'    => 'Aktivieren',
                'aonclick' => 'Panel.getSite().activate',
                'dimage'   => 'icon-remove',
                'dtext'    => 'Deaktivieren',
                'donclick' => 'Panel.getSite().activate'
            ));

            if ( $Site->getAttribute( 'active' ) )
            {
                $Status->setAttributes(array(
                    'textimage' => 'icon-remove',
                    'text'      => 'Deaktivieren',
                    'onclick'   => 'Panel.getSite().deactivate'
                ));

            } else
            {
                $Status->setAttributes(array(
                    'textimage' => 'icon-ok',
                    'text'      => 'Aktivieren',
                    'onclick'   => 'Panel.getSite().activate'
                ));
            }

            $Toolbar->appendChild( $Status );
        }

        // Tabs der Plugins hohlen
        $Plugins = self::getPlugins( $Site );

        foreach ( $Plugins as $Plugin )
        {
            if ( method_exists( $Plugin, 'setButtons' ) ) {
                $Plugin->setButtons( $Toolbar, $Site );
            }
        }

        return $Toolbar;
    }

    /**
     * Return the tabs of a site
     *
     * @param \QUI\Projects\Site\Edit $Site
     * @return \QUI\Controls\Toolbar\Bar
     */
    static function getTabs(\QUI\Projects\Site\Edit $Site)
    {
        $Tabbar = new \QUI\Controls\Toolbar\Bar(array(
            'name' => '_Tabbar'
        ));

        try
        {
            //self::checkRights($Site, $User);
        } catch ( \QUI\Exception $Exception )
        {
            $Tabbar->appendChild(
                new \QUI\Controls\Toolbar\Tab(array(
                    'name' => 'information',
                    'text' => 'Information',
                    'tpl'  => SYS_DIR .'template/site/information_edit.html',
                    'icon' => URL_BIN_DIR .'16x16/page.png'
                ))
            );

            return $Tabbar;
        }


        // Wenn die Seite bearbeitet wird
        if ( $Site->isMarcate() )
        {
            $Tabbar->appendChild(
                new \QUI\Controls\Toolbar\Tab(array(
                    'name'     => 'information',
                    'text'     => 'Information',
                    'template' => SYS_DIR .'template/site/information_edit.html',
                    'icon'     => URL_BIN_DIR .'16x16/page.png'
                ))
            );

            return $Tabbar;
        }


        if ( $Site->hasPermission( 'quiqqer.projects.site.view' ) &&
             $Site->hasPermission( 'quiqqer.projects.site.edit' ) )
        {
            $Tabbar->appendChild(
                new \QUI\Controls\Toolbar\Tab(array(
                    'name'     => 'information',
                    'text'     => 'Information',
                    'template' => SYS_DIR .'template/site/information.html',
                    'icon'     => 'icon-file-alt'
                ))
            );

        } elseif ( $Site->hasPermission( 'quiqqer.projects.site.view' ) === false )
        {
            $Tabbar->appendChild(
                new \QUI\Controls\Toolbar\Tab(array(
                    'name'     => 'information',
                    'text'     => 'Information',
                    'template' => SYS_DIR .'template/site/noview.html',
                    'icon'     => 'icon-file-alt'
                ))
            );

            return $Tabbar;

        } else
        // Wenn kein Bearbeitungsrecht aber Ansichtsrecht besteht
        {
            $Tabbar->appendChild(
                new \QUI\Controls\Toolbar\Tab(array(
                    'name'     => 'information',
                    'text'     => 'Information',
                    'template' => SYS_DIR .'template/site/information_norights.html',
                    'icon'     => 'icon-file-alt'
                ))
            );

            return $Tabbar;
        }

        // Inhaltsreiter
        $Tabbar->appendChild(
            new \QUI\Controls\Toolbar\Tab(array(
                'name' => 'content',
                'text' => 'Inhalt',
                'icon' => 'icon-file-text-alt'
            ))
        );

        // Einstellungen
        $Tabbar->appendChild(
            new \QUI\Controls\Toolbar\Tab(array(
                'name'     => 'settings',
                'text'     => 'Einstellungen',
                'icon'     => 'icon-cog',
                'template' => SYS_DIR .'template/site/settings.html'
            ))
        );

        // Global Plugins hohlen
        $Plugins = self::getPlugins( $Site );

        foreach ( $Plugins as $Plugin )
        {
            $file = $Plugin->getAttribute( '_folder_' ) .'site.xml';

            if ( !file_exists( $file ) ) {
                continue;
            }

            \QUI\Utils\DOM::addTabsToToolbar(
                \QUI\Utils\XML::getTabsFromXml( $file ),
                $Tabbar
            );

            //\QUI\System\Log::writeRecursive( $result, 'error' );

            /* old api
            if ( method_exists( $Plugin, 'setTabs' ) ) {
                $Plugin->setTabs($Tabbar, $Site);
            }
            */

            /*
            if ( method_exists( $Plugin, 'onAdminLoad' ) ) {
                $adminload .= $Plugin->onAdminLoad($Site);
            }
            */
        }

        return $Tabbar;
    }

    /**
     * Get the tab of a site
     *
     * @param String $tabname - Name of the Tab
     * @param \QUI\Projects\Site $Site
     *
     * @throws \QUI\Exception
     * @return \QUI\Controls\Toolbar\Tab
     */
    static function getTab($tabname, $Site)
    {
        $Toolbar = self::getTabs( $Site );
        $Tab     = $Toolbar->getElementByName( $tabname );

        if ( $Tab === false ) {
            throw new \QUI\Exception( 'The tab could not be found.' );
        }

        return $Tab;
    }

    /**
     * Returns all plugins that has the site
     *
     * @param \QUI\Projects\Site $Site
     * @return Array
     *
     * @todo schauen wegen admin bereich
     * @deprecated
     */
    static function getPlugins($Site)
    {
        // Globale requireds
        $Project = $Site->getProject();
        $Plugins = \QUI::getPluginManager();

        $types  = $Project->getTypes();
        $result = array();

        // Main Plugins
        foreach ( $types as $key => $type ) {
            $result[] = $Plugins->get( $key );
        }

        // Seitentypen Einbindungen
        if ( $Site->getAttribute( 'type' ) &&
             $Site->getAttribute( 'type' ) != 'standard' )
        {
            $result[] = $Plugins->getPluginByType(
                $Site->getAttribute( 'type' )
            );
        }

        return $result;
    }

    /**
     * Search sites
     *
     * @param String $search
     * @param Array $params
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
    static function search($search, $params=array())
    {
        $DataBase = \QUI::getDataBase();

        $page     = 1;
        $limit    = 50;
        $Project  = null;
        $projects = array();
        $fields   = array( 'id', 'title', 'name' );

        $selectList = array(
            'id', 'name', 'title', 'short', 'content', 'type',
            'c_date', 'e_date', 'c_user', 'e_user', 'active'
        );

        // projekt
        if ( isset( $params['Project'] ) && !empty( $params['Project'] ) )
        {
            $projects[] = $params['Project'];

        } else if ( isset( $params['project'] ) && !empty( $params['project'] ) )
        {
            $projects[] = \QUI::getProject( $params['project'] );

        } else
        {
            // search all projects
            $projects = \QUI::getProjectManager()->getProjects( true );
        }

        // limits
        if ( isset( $params['limit'] ) ) {
            $limit = (int)$params['limit'];
        }

        if ( isset( $params['page'] ) && (int)$params['page'] ) {
            $page = (int)$params['page'];
        }

        // fields
        if ( isset( $params['fields'] ) && !empty( $params['fields'] ) )
        {
            $fields  = array();
            $_fields = explode( ',', $params['fields'] );

            foreach ( $_fields as $field )
            {
                switch ( $field )
                {
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

        foreach ( $projects as $Project )
        {
            $langs = $Project->getAttribute('langs');
            $name  = $Project->getName();

            foreach ( $langs as $lang )
            {
                $tables[] = array(
                    'table'   => QUI_DB_PRFX . $name .'_'. $lang .'_sites',
                    'lang'    => $lang,
                    'project' => $name
                );
            }
        }

        $search = '%'. $search .'%';
        $query  = '';

        foreach ( $tables as $table )
        {
            $where = '';

            foreach ( $fields as $field )
            {
                $where .= $field .' LIKE :search';

                if ( $field !== end( $fields ) ) {
                    $where .= ' OR ';
                }
            }

            $query .= '(SELECT
                            "'. $table['project'] .' ('. $table['lang'] .')" as "project",
                            '. implode(',', $selectList) .'
                        FROM `'. $table['table'] .'`
                        WHERE ('. $where .') AND deleted = 0) ';

            if ( $table !== end( $tables ) ) {
                $query .= ' UNION ';
            }
        }

        // limit, pages
        if ( !isset( $params['count'] ) )
        {

            $page = $page - 1;

            if ( $page <= 0 ) {
                $page = 0;
            }

            $query .= ' LIMIT '. ($page * $limit) .','. $limit;
        }


        $PDO  = $DataBase->getPDO();
        $Stmt = $PDO->prepare( $query );

        $Stmt->execute(array(
            ':search' => $search
        ));

        $result = $Stmt->fetchAll( \PDO::FETCH_ASSOC );

        if ( isset( $params['count'] ) ) {
            return count( $result );
        }

        // \QUI\System\Log::write( $query );
        // \QUI\System\Log::writeRecursive( $result );

        return $result;
    }
}
