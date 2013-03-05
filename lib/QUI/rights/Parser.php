<?php

/**
 * This file contains the QUI_Rights_Parser
 */

/**
 * XML Parser für die Rechte
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.rights
 */
class QUI_Rights_Parser
{
    /**
     * Gibt das DomDocument einer Datei zurück
     *
     * @param String $filename - Datei
     * @return DOMDocument
     */
    static function parse($filename)
    {
        if (!file_exists($filename)) {
            return new DOMDocument();;
	    }

	    $Dom = new DOMDocument();
        $Dom->load($filename);

        return $Dom;
    }

    /**
     * Gibt die Rechte des DOMDocuments als Array zurück
     *
     * @param DOMElement $Dom
     * @return Array
     */
    static function getRights($Dom)
    {
        $permissions = $Dom->getElementsByTagName( 'permissions' );

        if ( !$permissions || !$permissions->length ) {
            return array();
        }

        $Permissions = $permissions->item( 0 );
        $permission  = $Permissions->getElementsByTagName( 'permission' );

        if ( !$permission || !$permission->length ) {
            return array();
        }

        $result = array();

        for ( $i = 0; $i < $permission->length; $i++ ) {
            $result[] = self::nodeToArray( $permission->item( $i ) );
        }

        return $result;
    }

    /**
     * Wandelt ein Rechte Node zu einem Array um
     *
     * @param DOMNode $Node
     * @return Array
     */
    static function nodeToArray(DOMNode $Node)
    {
        $desc    = '';
        $title   = '';
        $default = false;

        if (($Desc = $Node->getElementsByTagName('desc')) && $Desc->length) {
            $desc = $Desc->item(0)->nodeValue;
        }

        if (($Title = $Node->getElementsByTagName('title')) && $Title->length) {
            $title = $Title->item(0)->nodeValue;
        }

        if (($Default = $Node->getElementsByTagName('defaultvalue')) && $Default->length) {
            $default = $Default->item(0)->nodeValue;
        }


        $type = 'bool';

        switch ($Node->getAttribute('type'))
        {
            case 'bool':
            case 'string':
            case 'int':
            case 'group':
            case 'array':
                $type = $Node->getAttribute('type');
            break;
        }

        return array(
            'name'    => $Node->getAttribute('name'),
        	'type'    => $Node->getAttribute('type'),
            'desc'    => $desc,
            'title'   => $title,
            'site'    => $Node->getAttribute('site') ? 1 : 0,
            'type'    => $type, // bool, string, int, group, array
            'default' => $default
        );
    }
}


?>