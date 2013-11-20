<?php

/**
 * This file contains QUI_Menu
 */

/**
 * QUI_Menu helper class / menu for the admin
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.menu
 */

class QUI_Menu
{
    /**
     * Add a menu.xml file to a contextmenu bar item
     *
     * @param Controls_Contextmenu_Bar $Menu - Menu Object
     * @param String $file - Path to XML File
     */
    static function addXMLFile(Controls_Contextmenu_Bar $Menu, $file)
    {
        if ( !file_exists( $file ) ) {
            return;
        }

        // read the xml
        $items = \QUI\Utils\XML::getMenuItemsXml( $file );

        foreach ( $items as $Item )
        {
            if ( !$Item->getAttribute( 'parent' ) ) {
                continue;
            }

            $text = trim( $Item->nodeValue );

            if ( $Item->getAttribute( 'group' ) && $Item->getAttribute( 'var' ) )
            {
                $text = \QUI::getLocale()->get(
                    $Item->getAttribute( 'group' ),
                    $Item->getAttribute( 'var' )
                );
            }

            $params = array(
                'text'    => $text,
                'name'    => $Item->getAttribute( 'name' ),
                'icon'    => \QUI\Utils\DOM::parseVar( $Item->getAttribute( 'icon' ) ),
                'require' => $Item->getAttribute( 'require' ),
                'onClick' => 'QUI.Menu.click',
                'click'   => $Item->getAttribute( 'onclick' )
            );

            $Parent = $Menu;

            if ( $Item->getAttribute( 'parent' ) == '/' )
            {
                $MenuItem = new Controls_Contextmenu_Baritem( $params );
            } else
            {
                $MenuItem    = new Controls_Contextmenu_Menuitem( $params );
                $parent_path = explode( '/', trim( $Item->getAttribute( 'parent' ), '/' ) );

                foreach ( $parent_path as $parent )
                {
                    if ( $Parent ) {
                        $Parent = $Parent->getElementByName( $parent );
                    }
                }
            }

            if ( $Item->getAttribute( 'type' ) == 'seperator' ) {
                $MenuItem = new Controls_Contextmenu_Seperator( $params );
            }

            if ( $Item->getAttribute( 'disabled' ) == 1 ) {
                $MenuItem->setDisable();
            }

            if ( $Parent ) {
                $Parent->appendChild( $MenuItem );
            }
        }
    }
}

?>