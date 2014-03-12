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
                'textimage'      => 'icon-page-alt',
                'text'      => 'Neu',
                'onclick'   => 'Panel.createNewChild',
                'help'      => 'Erzeugen Sie eine neue Unterseite',
                'alt'       => 'Erzeugen Sie eine neue Unterseite',
                'title'     => 'Erzeugen Sie eine neue Unterseite'
            ))
        );

        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Button(array(
                'name'    => '_Save',
                'textimage'    => 'icon-save',
                'text'    => 'Speichern',
                'onclick' => 'Panel.save',
                'help'    => 'Speichern Sie Ihre Änderungen.',
                'alt'     => 'Speichern Sie Ihre Änderungen.',
                'title'   => 'Speichern Sie Ihre Änderungen.'
            ))
        );

        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Button(array(
                'name'    => '_Del',
                'textimage'    => 'icon-trash',
                'text'    => 'Löschen',
                'onclick' => 'Panel.del',
                'help'    => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden',
                'title'   => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden',
                'alt'     => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden'
            ))
        );

        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Seperator(array(
                'name' => '_sep'
            ))
        );

        $Toolbar->appendChild(
            new \QUI\Controls\Buttons\Button(array(
                'name'    => '_Preview',
                'textimage'    => 'icon-eye-open',
                'text'    => 'Vorschau',
                'onclick' => 'QUI.lib.Sites.PanelButton.preview'
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
     */
    static function getPlugins($Site)
    {
        // Globale requireds
        $Project = $Site->getProject();
        $Plugins = \QUI::getPlugins();

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
}
