<?php

/**
 * This file contains the Projects_Sites
 */

/**
 * Helper for the Site Object
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects
 */

class Projects_Sites
{
    /**
     * JavaScript buttons, depending on the side of the user
     *
     * @param Projects_Site_Edit $Site
     * @param Users_User|Bool $User - optional
     *
     * @return Controls_Toolbar_Bar
     */
    static function getButtons(Projects_Site_Edit $Site, $User=false)
    {
        if (!$User) {
            $User = QUI::getUserBySession();
        }

        $Toolbar = new Controls_Toolbar_Bar(array(
            'name' => '_Toolbar'
        ));

        // Standard Buttons
        $Toolbar->appendChild(
            new Controls_Buttons_Button(array(
                'name'      => '_New',
                'textimage' => URL_BIN_DIR .'16x16/page_white.png',
                'text'      => 'Neu',
                'onclick'   => 'QUI.lib.Sites.PanelButton.createChild',
            	'help'      => 'Erzeugen Sie eine neue Unterseite',
            	'alt'       => 'Erzeugen Sie eine neue Unterseite',
            	'title'     => 'Erzeugen Sie eine neue Unterseite'
            ))
        );

        $Toolbar->appendChild(
            new Controls_Buttons_Button(array(
                'name'      => '_Save',
                'textimage' => URL_BIN_DIR .'16x16/save.png',
                'text'      => 'Speichern',
                'onclick'   => 'QUI.lib.Sites.PanelButton.save',
            	'help'      => 'Speichern Sie Ihre Änderungen.',
            	'alt'       => 'Speichern Sie Ihre Änderungen.',
            	'title'     => 'Speichern Sie Ihre Änderungen.'
            ))
        );

        $Toolbar->appendChild(
            new Controls_Buttons_Button(array(
                'name'      => '_Del',
                'textimage' => URL_BIN_DIR .'16x16/trashcan_empty.png',
                'text'      => 'Löschen',
                'onclick'   => 'QUI.lib.Sites.PanelButton.del',
            	'help'      => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden',
                'title'     => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden',
                'alt'       => 'Löschen Sie die Seite, die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden'
            ))
        );

        $Toolbar->appendChild(
        	new Controls_Buttons_Seperator(array(
            	'name' => '_sep'
        	))
        );

        $Toolbar->appendChild(
            new Controls_Buttons_Button(array(
                'name'      => '_Preview',
                'textimage' => URL_BIN_DIR .'16x16/preview.png',
                'text'      => 'Vorschau',
                'onclick'   => 'QUI.lib.Sites.PanelButton.preview'
            ))
        );

        try
        {
            self::checkRights($Site, $User);
        } catch (QException $e)
        {
            $New->setDisable();
            $Save->setDisable();
            $Del->setDisable();

            return $Toolbar;
        }

		if ($Site->isMarcate()) // wenn die Seite bearbeitet wird
        {
            $Save->setDisable();
            $Del->setDisable();
        }

        $Rights = QUI::getRights();

        // Wenn das Kind erstellen Recht nicht vorhanden ist
        if (!$Rights->hasRights($User, $Site, 'new')) {
            $New->setDisable();
        }

        // Wenn das Bearbeiten Recht nicht vorhanden ist
        if (!$Rights->hasRights($User, $Site, 'edit')) {
            $Save->setDisable();
        }

        // Wenn das Löschen Recht nicht vorhanden ist
        if (!$Rights->hasRights($User, $Site, 'del')) {
            $Del->setDisable();
        }

        // Wenn das Bearbeiten Recht vorhanden ist
        if ($Rights->hasRights($User, $Site, 'edit') && !$Site->isMarcate())
        {
            $Toolbar->appendChild(
            	new Controls_Buttons_Seperator(array(
	                'name' => '_sep'
	            ))
            );

            $Status = new Controls_Buttons_Button(array(
                'name'     => 'status',
                'aimage'   => URL_BIN_DIR .'16x16/active.png',
            	'atext'    => 'Aktivieren',
            	'aonclick' => 'QUI.lib.Sites.PanelButton.activate',
                'dimage'   => URL_BIN_DIR .'16x16/deactive.png',
				'dtext'    => 'Deaktivieren',
            	'donclick' => 'QUI.lib.Sites.PanelButton.deactivate'
            ));

            if ($Site->getAttribute('active'))
            {
                $Status->setAttributes(array(
                    'textimage' => URL_BIN_DIR .'16x16/deactive.png',
                    'text'      => 'Deaktivieren',
                    'onclick'   => 'QUI.lib.Sites.PanelButton.deactivate'
	            ));

            } else
            {
                $Status->setAttributes(array(
                    'textimage' => URL_BIN_DIR .'16x16/active.png',
                    'text'      => 'Aktivieren',
                    'onclick'   => 'QUI.lib.Sites.PanelButton.activate'
                ));
            }

            $Toolbar->appendChild($Status);
        }

        // Tabs der Plugins hohlen
        $Plugins = self::getPlugins($Site);

        foreach ($Plugins as $Plugin)
        {
        	if (method_exists($Plugin, 'setButtons')) {
                $Plugin->setButtons($Toolbar, $Site);
            }
        }

    	return $Toolbar;
    }

    /**
     * Return the tabs of a site
     *
     * @param Projects_Site_Edit $Site
     * @param Users_User $User - optional; Ansonsten wird Session Benutzer verwendet
     *
     * @return Controls_Toolbar_Bar
     */
    static function getTabs(Projects_Site_Edit $Site, $User=false)
    {
        if ( !$User ) {
            $User = QUI::getUserBySession();
        }


        $Tabbar = new Controls_Toolbar_Bar(array(
            'name' => '_Tabbar'
        ));

        try
        {
            self::checkRights($Site, $User);
        } catch (QException $e)
        {
            $Tabbar->appendChild(
                new Controls_Toolbar_Tab(array(
                    'name' => 'information',
                    'text' => 'Information',
                    'tpl'  => SYS_DIR .'template/site/information_edit.html',
                	'icon' => URL_BIN_DIR .'16x16/page.png'
                ))
            );

            return $Tabbar;
        }


        // Wenn die Seite bearbeitet wird
        if ($Site->isMarcate())
        {
            $Tabbar->appendChild(
                new Controls_Toolbar_Tab(array(
                    'name'     => 'information',
                    'text'     => 'Information',
                    'template' => SYS_DIR .'template/site/information_edit.html',
                	'icon'     => URL_BIN_DIR .'16x16/page.png'
                ))
            );

            return $Tabbar;
        }

        $Rights = $Site->getRights();

        if ($Rights->hasRights($User, $Site, 'view') &&
            $Rights->hasRights($User, $Site, 'edit'))
        {
            $Tabbar->appendChild(
                new Controls_Toolbar_Tab(array(
                    'name'     => 'information',
                    'text'     => 'Information',
                    'template' => SYS_DIR .'template/site/information.html',
                    'icon'     => URL_BIN_DIR .'16x16/page.png'
                ))
            );

        } elseif (!$Rights->hasRights($User, $Site, 'view'))
        // Wenn kein Ansichtsrecht besteht dann keine weiteren Tabs
        {
            $Tabbar->appendChild(
                new Controls_Toolbar_Tab(array(
                    'name'     => 'information',
                    'text'     => 'Information',
                    'template' => SYS_DIR .'template/site/noview.html',
                    'icon'     => URL_BIN_DIR .'16x16/page.png'
                ))
            );

            return $Tabbar;

        } else
        // Wenn kein Bearbeitungsrecht aber Ansichtsrecht besteht
        {
            $Tabbar->appendChild(
                new Controls_Toolbar_Tab(array(
                    'name'     => 'information',
                    'text'     => 'Information',
                    'template' => SYS_DIR .'template/site/information_norights.html',
                    'icon'     => URL_BIN_DIR .'16x16/page.png'
                ))
            );

            return $Tabbar;
        }

        // Inhaltsreiter
        $Tabbar->appendChild(
        	new Controls_Toolbar_Tab(array(
	            'name' => 'content',
	            'text' => 'Inhalt',
        	    'icon' => URL_BIN_DIR .'16x16/edit.png'
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

            Utils_Dom::addTabsToToolbar(
                Utils_Xml::getTabsFromXml( $file ),
                $Tabbar
            );

            //System_Log::writeRecursive( $result, 'error' );

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

        /*
        $Tabbar->appendChild(
        	new Controls_Toolbar_Tab(array(
	            'name' 	    => '_rights',
	            'text'      => 'Rechte',
	            'tpl' 	    => SYS_DIR .'template/rights.html',
	            'onload' 	=> 'on_tab_load_rights = function () { _base.rights.load(); }; insert_tab_tpl',
	            'onclick'   => "_Tabbar.getElementByName('_rights').setActive",
	            'onunload' 	=> '_base.rights.unload(); _Site.setTempFile'
	        ))
        );
		*/

        return $Tabbar;
    }

    /**
     * Get the tab of a site
     *
     * @param String $tabname - Name of the Tab
     * @param Projects_Site $Site
     * @param Users_User $User
     *
     * @throws QException
     * @return Controls_Toolbar_Tab
     */
    static function getTab($tabname, $Site, $User=false)
    {
        if ( !$User ) {
            $User = QUI::getUserBySession();
        }

        $Toolbar = self::getTabs( $Site, $User );
        $Tab     = $Toolbar->getElementByName( $tabname );

        if ( $Tab === false ) {
            throw new QException( 'The tab could not be found.' );
        }

        return $Tab;
    }

    /**
     * Checks a site on the first admin rights
     *
     * @param Projects_Site_Edit|Projects_Site $Site
     * @param Users_User $User
     *
     * @return Bool
     * @throws QException
     */
    static function checkRights($Site, $User=false)
    {
        /* @var $Site Projects_Site_Edit */
        if ( $User == false ) {
        	$User = QUI::getUserBySession();
    	}

        // System Benutzer darf editieren und sehen
        if ( $User->getType() == 'SystemUser' ) {
			return true;
        }

        if ( !$Site->getRights()->hasRights( $User, $Site, 'view' ) ||
             !$Site->getRights()->hasRights( $User, $Site, 'edit' ) )
        {
            throw new QException( 'No Rights', self::EACCES );
        }

        return true;
    }

    /**
     * Return all plugins having a site
     *
     * @param Projects_Site $Site
     * @return Array
     *
     * @todo schauen wegen admin bereich
     */
    static function getPlugins($Site)
    {
        // Globale requireds
		$Project = $Site->getProject();
		$Plugins = QUI::getPlugins();

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

?>