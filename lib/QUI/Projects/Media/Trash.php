<?php

/**
 * This file contains \QUI\Projects\Media\Trash
 */

namespace QUI\Projects\Media;

/**
 * The media trash
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 */

class Trash implements \QUI\Interfaces\Projects\Trash
{
    /**
     * The media
     * @var \QUI\Projects\Media
     */
    protected $_Media;

    /**
     * Konstruktor
     *
     * @param \QUI\Projects\Media $Media
     */
    public function __construct(\QUI\Projects\Media $Media)
    {
        $this->_Media = $Media;

        \QUI\Utils\System\File::mkdir( $this->getPath() );
    }

    /**
     * Returns the trash path for the Media
     *
     * @return String
     */
    public function getPath()
    {
        return VAR_DIR .'media/'. $this->_Media->getProject()->getAttribute('name') .'/';
    }

    /**
     * Returns the items in the trash
     *
     * @param Array $params - \QUI\Utils\Grid parameters
     * @return array
     */
    public function getList($params=array())
    {
        $Grid  = new \QUI\Utils\Grid();

        $query = $Grid->parseDBParams( $params );
        $query['from']  = $this->_Media->getTable();
        $query['where'] = array(
            'deleted' => 1
        );

        // count
        $count = \QUI::getDataBase()->fetch(array(
            'from'  => $this->_Media->getTable(),
            'count' => true,
            'where' => array(
                'deleted' => 1
            )
        ));

        $data = \QUI::getDataBase()->fetch( $query );

        foreach ( $data as $key => $entry )
        {
            $data[ $key ]['icon'] = \QUI\Projects\Media\Utils::getIconByExtension(
                \QUI\Projects\Media\Utils::getExtension( $entry['file'] )
            );
        }

        return $Grid->parseResult( $data, $count );
    }

    /**
     * Destroy the file item from the filesystem
     * After it, its impossible to restore the item
     *
     * @param Integer $id
     */
    public function destroy($id)
    {
        \QUI::getDataBase()->delete(
            $this->_Media->getTable(),
            array('id' => $id)
        );

        \QUI\Utils\System\File::unlink( $this->getPath() . $id );
    }

    /**
     * Restore a item to a folder
     *
     * @param Integer $id
     * @param \QUI\Projects\Media\Folder $Folder
     */
    public function restore($id, \QUI\Projects\Media\Folder $Folder)
    {
        $file = $this->getPath() . $id;

        if ( !file_exists($file) ) {
            throw new \QUI\Exception( 'Could not find the file '. $id .' in the Trash' );
        }

        $Item = $Folder->uploadFile( $file );

        // change old db entry, if one exist
        $data = \QUI::getDataBase()->fetch(array(
            'from' 	=> $this->_Media->getTable(),
            'where' => array(
                'id' => $id
            ),
            'limit' => 1
        ));

        if ( !isset($data[0]) ) {
            return $Item;
        }

        $fields = $data[0];

        try
        {
            $Item->rename( $fields['name'] );
        } catch ( \QUI\Exception $Exception )
        {

        }

        $Item->setAttributes(array(
            'title' => $fields['title'],
            'alt' 	=> $fields['alt'],
            'short' => $fields['short']
        ));

        $Item->save();

        \QUI::getDataBase()->delete(
            $this->_Media->getTable(),
            array('id' => $id)
        );
    }
}
