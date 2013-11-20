<?php

/**
 * MediaFile
 * Objekt im Media Center - Parent Objekt
 *
 * @author PCSG - henning
 * @package com.pcsg.pms.media
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 4722 $
 * @since      Class available since Release P.MS 0.9
 */

interface iMF
{
    public function getAttribute($name);
    public function getId();
    public function getParent();
    public function getParentId();
    public function getParentIds();
    public function getPath();
    public function getType();
    public function getUrl();

    public function activate();
    public function deactivate();
    public function delete();
    public function destroy();
    public function restore(MF_Folder $Parent);
    public function save();

    public function toArray();

    public function createCache();
}

class MediaFile extends \QUI\QDOM
{
    protected $_id;
    protected $_attributes;

    protected $_Project;
    protected $_Media;

    protected $_TABLE;
    protected $_RELTABLE;

    protected $_pid;

    /**
     * Konstruktor
     *
     * @param Media $Media
     * @param Array $attributes
     */
    public function __construct(Media $Media, array $attributes)
    {
        if (!isset($attributes['id'])) {
            throw new \QUI\Exception('No ID given');
        }

        $this->_id = $attributes['id'];

        $this->_Project = $Media->getProject();
        $this->_Media   = $Media;

        $this->_TABLE    = $this->_Project->getAttribute('media_table');
        $this->_RELTABLE = $this->_TABLE.'_relations';

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        //$this->_attributes = $attributes;
    }

    /**
     * Gibt die Attribute als Array zurück
     *
     * @return Array
     */
    public function toArray()
    {
        //$attributes = $this->_attributes;
        $attributes = $this->getAllAttributes();
        $attributes['url'] = $this->getUrl();

        return $attributes;
    }

    /**
     * Gibt die ID der Datei zurück
     *
     * @return unknown
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Gibt die Parent ID zurück
     * return Integer
     */
    public function getParentId()
    {
        if (!$this->_pid) {
            $this->_pid = $this->_getParentId( $this->getId() );
        }

        return $this->_pid;
    }

    /**
     * Gibt die Parent ID der übergebenen ID zurück
     * @param Integer $id
     * return Integer
     */
    protected function _getParentId($id)
    {
        $result = \QUI::getDB()->select(array(
            'from' 	=> $this->_RELTABLE,
            'where' => array(
                'child' => $id
            ),
            'limit' => '1'
        ));

        if (isset($result) && is_array($result) && isset($result[0])) {
            return (int)$result[0]['parent'];
        }

        return false;
    }

    /**
     * Gibt rekursiv die Parent IDs zurück
     * return Integer
     */
    public function getParentIds()
    {
        $parents = array();
        $pid     = $this->getId();

        while ($pids = $this->_getParentId($pid))
        {
            $parents[] = $pid;
            $pid       = $pids;
        }

        return array_reverse($parents);
    }

    /**
     * Gibt das Parent Objekt zurück
     *
     * @return MediaFile
     */
    public function getParent()
    {
        try
        {
            return $this->_Media->get( $this->getParentId() );

        } catch (\QUI\Exception $e)
        {
            // nothing
        };

        return false;
    }

    /**
     * Gibt den Pfad des Files im Filesystem zurück
     *
     * @return String || false
     */
    public function getPath()
    {
        return $this->getAttribute('file');
    }

    /**
     * Gibt die URL zurück
     *
     * @param Bool $type - false = image.php, true = rewrited URL
     * @return String
     */
    public function getUrl($rewrite=false)
    {
        if ( $rewrite == false )
        {
            $str = 'image.php?id='. $this->getId() .
                   '&project='. $this->_Project->getAttribute( 'name' ) .
                   '&qui=1';

            if ( $this->getAttribute( 'maxheight' ) ) {
                $str .= '&maxheight='. $this->getAttribute( 'maxheight' );
            }

            if ( $this->getAttribute( 'maxwidth' ) ) {
                $str .= '&maxwidth='. $this->getAttribute( 'maxwidth' );
            }

            return $str;
        }

        if ( $this->getAttribute( 'active' ) == 1 ) {
            return $this->_Media->getAttribute( 'url_cache_dir' ) . $this->getAttribute( 'file' );
        }

        return '';
    }

    /**
     * Speichern
     *
     * @return Bool
     */
    public function save()
    {
        // Namen Prüfung
        $name = $this->getAttribute('name');

        // Namensprüfung wegen unerlaubten Zeichen
        $this->_Media->checkMediaName($name);

        // Rename
        if (method_exists($this, 'rename')) {
            $u_name = $this->rename($this->getAttribute('name'));
        }

        $watermark = $this->getAttribute('watermark');

        if (is_array($watermark)) {
            $watermark = json_encode($watermark);
        }

        $roundcorners = $this->getAttribute('roundcorners');

        if (is_array($roundcorners)) {
            $roundcorners = json_encode($roundcorners);
        }

        // Standard Felder ändern
        \QUI::getDB()->updateData(
            $this->_TABLE,
            array(
                'name'  => $this->getAttribute('name'),
                'title' => $this->getAttribute('title'),
                'alt' 	=> $this->getAttribute('alt'),
                'short' => $this->getAttribute('short'),

                'watermark'    => $watermark,
                'roundcorners' => $roundcorners
            ),
            array(
                'id' => $this->getId()
            )
        );

        // Pfad Cache löschen
        if (method_exists($this, '_deleteLinkCache')) {
            $this->_deleteLinkCache();
        }

        // Cache löschen, da sich das Bild geändert haben könnte
        if (method_exists($this, 'deleteCache')) {
            $this->deleteCache();
        }

        /**
         * Cache erzeugen
         */
        if (method_exists($this, 'createCache'))
        {
            try
            {
                $this->createCache();
            } catch (\QUI\Exception $e)
            {
                // Manchmal ist das rename noch nicht fertig
                // daher wird exception geworfen
                // ist nicht schlimm
                // Cache wird beim nächsten Aufruf der Datei erzeugt
                return false;
            }
        }

        return true;
    }

    /**
     * Bewertung für ein Bild vornehmen (1-5)
     *
     * @param Integer $rate
     */
    public function rate($rate)
    {
        $Users = \QUI::getUsers();
        $User  = $Users->getUserBySession();
        $rate  = (int)$rate;

        if (!$User->getId()) {
            throw new \QUI\Exception('Nur angemeldete Benutzer können Bilder bewerten');
        }

        // Bewertung von 1 bis 5 sind zugelassen
        switch ($rate)
        {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
                // nothing
            break;

            default:
                $rate = 0;
            break;
        }

        $ratings = json_decode($this->getAttribute('rate_users'), true);

        if (!is_array($ratings)) {
            $ratings = array();
        }

        $ratings[ $User->getId() ] = $rate;

        \QUI::getDB()->updateData(
            $this->_TABLE,
            array(
                'rate_users' => json_encode($ratings),
                'rate_count' => round(array_sum($ratings) / count($ratings), 2)
            ),
            array(
                'id' => $this->getId()
            )
        );
    }
}

?>