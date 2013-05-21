<?php

/**
 * This file contains QUI_Desktop
 */

/**
 *
 *
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Desktop
{
    /**
     * Return all Widgets
     *
     * @return Array
     */
    static function getWidgetList()
    {
        $result = array();
        $list   = self::readWidgetsFiles();

        foreach ( $list as $DOM ) {
            $result[] = self::DOMToWidget( $DOM );
        }

        return $result;
    }

    /**
     * parse an Widget DOM-Node to an QUI_Desktop_Widget
     *
     * @param DOMNode $Node
     * @return QUI_Desktop_Widget
     */
    static function DOMToWidget(DOMNode $Node)
    {
        $Widget = new \QUI_Desktop_Widget();

        $atributes = $Node->getElementsByTagName( 'attributes' );
        $require   = $Node->getElementsByTagName( 'require' );
        $content   = $Node->getElementsByTagName( 'content' );
        $title     = $Node->getElementsByTagName( 'title' );

        if ( $atributes->length )
        {
            $Attributes = $atributes->item( 0 );

            if ( $Attributes->getAttribute('height') ) {
                $Widget->setAttribute( 'height', $Attributes->getAttribute('height') );
            }

            if ( $Attributes->getAttribute('width') ) {
                $Widget->setAttribute( 'width', $Attributes->getAttribute('width') );
            }

            if ( $Attributes->getAttribute('icon') ) {
                $Widget->setAttribute( 'icon', $Attributes->getAttribute('icon') );
            }

            if ( $Attributes->getAttribute('refresh') ) {
                $Widget->setAttribute( 'refresh', $Attributes->getAttribute('refresh') );
            }
        }

        if ( $require->length )
        {
            $requires = array();

            for ( $i = 0, $len = $require->length; $i < $len; $i++ ) {
                $requires[] = $require->item( $i )->getAttribute('src');
            }

            $Widget->setAttribute( 'require', $requires );
        }

        if ( $content->length )
        {
            $Content = $content->item( 0 );

            $Widget->setAttribute(
                'content',
                array(
                    'type'    => $Content->getAttribute('type'),
                    'func'    => $Content->getAttribute('func'),
                    'content' => $Content->nodeValue
                )
            );
        }

        if ( $title->length )
        {
            $Widget->setAttribute(
                'title',
                $title->item( 0 )->nodeValue
            );
        }

        return $Widget;
    }

    /**
     * Read all Widgets Files and return all Widget DOM Nodes
     *
     * @return Array
     */
    static function readWidgetsFiles()
    {
        // system widgets
        $dir   = SYS_DIR .'widgets/';
        $files = \Utils_System_File::readDir( $dir );

        $result = array();

        foreach ( $files as $file )
        {
            $result = array_merge(
                $result,
                \Utils_Xml::getWidgetsFromXml( $dir .'/'. $file )
            );
        }

        return $result;
    }
}
