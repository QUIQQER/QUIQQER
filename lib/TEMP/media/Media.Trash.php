<?php

/**
 * Mülleimer für das MediaCenter
 *
 * @author PCSG - Henning
 * @package com.pcsg.pms.media
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 4366 $
 * @since      Class available since Release P.MS 0.9
 */

class MC_Trash
{
    private $_Project;
    private $_Media;
    private $_TABLE;

    /**
     * Konstruktor
     *
     * @param Media $Media
     */
    public function __construct(\QUI\Projects\Project $Project)
    {
        $this->_Project = $Project;
        $this->_Media   = $Project->getMedia();
        $this->_TABLE   = $Project->getAttribute('media_table');
    }

    /**
     * Gibt die gelöschten Seiten zurück
     *
     * @return MC_Children
     */
    public function getSites()
    {
        $Media = $this->_Media;

        $result = \QUI::getDB()->select(array(
            'from' 	=> $this->_TABLE,
            'where' => $this->_TABLE.'.deleted = 1',
            'order'	=> $this->_TABLE.'.type ASC, '.$this->_TABLE.'.name'
        ));

        $children = new MC_Children();

        foreach ($result as $field) {
            $children->add( $Media->get( (int)$field['id'] ), $field );
        }

        return $children;
    }
}

?>