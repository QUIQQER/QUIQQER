<?php

/**
 * This file contains Groups_Utils
 */

/**
 * Helper for groups
 *
 * @author Henning Leutz (PCSG)
 * @package com.pcsg.qui.groups
 */

class Groups_Utils
{
    /**
     * JavaScript Buttons / Tabs for a group
     *
     * @param Groups_Group $Group
     * @return Controls_Toolbar_Bar
     */
    static function getGroupToolbar($Group)
    {
        $Tabbar = new Controls_Toolbar_Bar(array(
            'name'  => 'UserToolbar'
        ));

        Utils_Dom::addTabsToToolbar(
            Utils_Xml::getTabsFromXml( SYS_DIR .'groups.xml' ),
            $Tabbar,
            'pcsg'
        );

        // @todo load plugin / project / package extensions


        // Projekt Tabs
        /*
		$projects = Projects_Manager::getProjects( true );

		foreach ( $projects as $Project )
		{
            $rights = QUI::getRights()->getProjectRights( $Project );

            if ( empty( $rights ) ) {
                continue;
            }

            $Tabbar->appendChild(
    			new Controls_Toolbar_Tab(array(
    				'name'    => 'project_'. $Project->getAttribute('name'),
    			    'project' => $Project->getAttribute('name'),
    				'text'    => $Project->getAttribute('name') .' Rechte'
    			))
    		);
		}
		*/
        return $Tabbar;
    }

    /**
     * Tab contents of a group Tab / Button
     *
     * @param Integer $gid - Group ID
     * @param String $plugin - Plugin
     * @param String $tab - Tabname
     *
     * @return String
     */
    static function getTab($gid, $plugin, $tab)
    {
        $Groups = QUI::getGroups();
    	$Group  = $Groups->get( $gid );
    	$Engine = QUI_Template::getEngine( true );

    	$Engine->assign(array(
    	    'Group' => $Group
    	));

        // System
    	if ( $plugin === 'pcsg' )
    	{
            return Utils_Dom::getTabHTML(
                $tab, SYS_DIR .'groups.xml'
            );
    	}

    	/*
    	if ( strpos($tab, 'project_') !== false )
    	{
            $tab     = explode( 'project_', $tab );
            $Project = Projects_Manager::getProject( $tab[1] );
            $file    = USR_DIR .'lib/'. $Project->getAttribute('name') .'/rights.xml';

            $Engine = QUI_Template::getEngine( true );
            $rights = QUI_Rights_Parser::getGroups(
    	        QUI_Rights_Parser::parse( $file )
    	    );

    	    $Engine->assign(array(
    	        'rights' => $rights
    	    ));

    	    return $Engine->fetch( SYS_DIR .'template/groups/extend.html' );
    	}
		*/

    	return '';
    }
}

?>