<?php

/**
 * This file contains the Projects_Media
 */

/**
 * Media Manager for a project
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 */

class Projects_Media extends \QUI\QDOM
{
    /**
     * internal project object
     * @var Projects_Project
     */
    protected $_Project;

    /**
     * internal child cache
     * @var array
     */
    protected $_children = array();

    /**
     * constructor
     * @param Projects_Project $Project
     */
    public function __construct(Projects_Project $Project)
    {
        $this->_Project = $Project;
    }

    /**
     * Return the project of the media
     * @return Projects_Project
     */
    public function getProject()
    {
        return $this->_Project;
    }

    /**
     * Return the main media directory
     * Here are all files located
     * (without the CMS_DIR)
     *
     * @return String - path to the directory, relative to the system
     */
    public function getPath()
    {
        return 'media/sites/'. $this->getProject()->getAttribute('name') .'/';
    }

    /**
     * Return the main media directory
     * Here are all files located
     * (with the CMS_DIR)
     *
     * @return String - path to the directory
     */
    public function getFullPath()
    {
        return CMS_DIR . $this->getPath();
    }

    /**
     * Return the cache directory from the media
     *
     * @return String - path to the directory, relative to the system
     */
    public function getCacheDir()
    {
        return 'media/cache/'. $this->getProject()->getAttribute('name') .'/';
    }

    /**
     * Return the DataBase table name
     *
     * @param String $type - standard=false (relations)
     * @return String
     */
    public function getTable($type=false)
    {
        if ( $type == 'relations' ) {
            return $this->_Project->getAttribute( 'name' ) .'_de_media_relations';
        }

        return $this->_Project->getAttribute( 'name' ) .'_de_media';
    }

    /**
     * Setup for a media table
     */
    public function setup()
    {
        /**
         * Media Center
         */
        $table = $this->getTable();

        $DataBase = QUI::getDataBase();
        $DataBase->Table()->appendFields($table, array(
            'id'           => 'bigint(20) NOT NULL',
            'name'         => 'varchar(200) NOT NULL',
            'title'        => 'tinytext',
            'short'        => 'text',
            'type'         => 'varchar(32) default NULL',
            'active'       => 'tinyint(1) NOT NULL',
            'deleted'      => 'tinyint(1) NOT NULL',
            'c_date'       => 'timestamp NULL default NULL',
            'e_date'       => 'timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            'file'         => 'text',
            'alt'          => 'text',
            'mime_type'    => 'text',
            'image_height' => 'int(6) default NULL',
            'image_width'  => 'int(6) default NULL',
            'roundcorners' => 'text',
            'watermark'    => 'text',
            'c_user'       => 'int(11) default NULL',
            'e_user'       => 'int(11) default NULL',
            'rate_users'   => 'text',
            'rate_count'   => 'float default NULL',

            'md5hash'  => 'varchar(32)',
            'sha1hash' => 'varchar(40)'
        ));

        $DataBase->Table()->setIndex( $table, 'id' );
        $DataBase->Table()->setAutoIncrement( $table, 'id' );

        // Media Relations
        $table = $this->getTable();

        $DataBase->Table()->appendFields($table, array(
            'parent' => 'bigint(20) NOT NULL',
            'child'  => 'bigint(20) NOT NULL'
        ));

        $DataBase->Table()->setIndex( $table, 'parent' );
        $DataBase->Table()->setIndex( $table, 'child' );
    }

    /**
     * methods for usage
     */

    /**
     * Return the first child in the media
     *
     * @return Projects_Media_Folder
     */
    public function firstChild()
    {
        return $this->get(1);
    }

    /**
     * Return a media object
     *
     * @param Integer $id - media id
     * @return Projects_Media_Item
     * @throws \QUI\Exception
     */
    public function get($id)
    {
        $id = (int)$id;

        if ( isset( $this->_children[ $id ] ) ) {
            return $this->_children[ $id ];
        }

        // If the RAM is full objects was once empty
        if ( \QUI\Utils\System::memUsageToHigh() ) {
            $this->_children = array();
        }

        $result = \QUI::getDataBase()->fetch(array(
            'from' 	=> $this->getTable(),
            'where' => array(
                'id' => $id
            ),
            'limit' => 1
        ));

        if ( !isset( $result[0] ) ) {
            throw new \QUI\Exception( 'ID '. $id .' not found', 404 );
        }


        $this->_children[ $id ] = $this->_parseResultToItem( $result[0] );

        return $this->_children[ $id ];
    }

    /**
     * Return the wanted children ids
     *
     * @param Array $params - DataBase params
     * @return id list
     */
    public function getChildrenIds($params=array())
    {
        $params['select'] = 'id';
        $params['from']   = $this->getTable();

        $result = QUI::getDataBase()->fetch( $params );
        $ids    = array();

        foreach ( $result as $entry )  {
            $ids[] = $entry['id'];
        }

        return $ids;
    }

    /**
     * Return a file from its file oath
     *
     * @param String $filename
     * @return Projects_Media_Item
     */
    public function getChildByPath($filepath)
    {
        $table     = $this->getTable();
        $table_rel = $this->getTable( 'relations' );

        $result = \QUI::getDataBase()->fetch(array(
            'select' => array(
                $table .'.id'
            ),
            'from'  => array(
                $table,
                $table_rel
            ),
            'where' => array(
                $table .'.deleted' => 0,
                $table .'.file'	   => $filepath
            ),
            'limit' => 1
        ));

        if ( !isset( $result[0] ) ) {
            throw new \QUI\Exception('File '. $filepath .' not found', 404);
        }

        return $this->get( (int)$result[0]['id'] );
    }

    /**
     * Replace a file with another
     *
     * @param Integer $id  -
     * @param String $file - Path to the new file
     * @throws \QUI\Exception
     */
    public function replace($id, $file)
    {
        if ( !file_exists($file) ) {
            throw new \QUI\Exception( 'File could not be found', 404 );
        }

        // use direct db not the objects, because
        // if file is not ok you can replace the file though
        $result = QUI::getDataBase()->fetch(array(
            'from' 	=> $this->getTable(),
            'where' => array(
                'id' => $id
            ),
            'limit' => 1
        ));


        if ( !isset($result[0]) ) {
            throw new \QUI\Exception( 'File entry not found', 404 );
        }

        if ( $result[0]['type'] == 'folder' ) {
            throw new \QUI\Exception( 'Only Files can be replaced', 403 );
        }

        $data = $result[0];

        $name = $data['name'];
        $info = \QUI\Utils\System\File::getInfo( $file );

        if ( $info['mime_type'] != $data['mime_type'] ) {
            $name = $info['basename'];
        }

        /**
         * get the parent and check if a file like the replace file exist
         */
        $parentid = $this->getParentIdFrom( $data['id'] );

        if ( !$parentid ) {
            throw new \QUI\Exception( 'No Parent found.', 404 );
        }

        /* @var $Parent Projects_Media_Folder */
        $Parent = $this->get( $parentid );

        if ( $data['name'] != $name &&
             $Parent->childWithNameExists($name) )
        {
            throw new \QUI\Exception(
                'A file with the name '. $name .' already exist.',
                403
            );
        }

        // delete the file
        if ( isset($data['file']) && !empty($data['file']) )
        {
            \QUI\Utils\System\File::unlink(
                $this->getFullPath() . $data['file']
            );
        }

        $new_file  = $Parent->getPath() . $name;
        $real_file = $Parent->getFullPath() . $name;

        QUI::getDataBase()->update(
            $this->getTable(),
            array(
                'file'         => $new_file,
                'name'         => $name,
                'mime_type'    => $info['mime_type'],
                'image_height' => $info['height'],
                'image_width'  => $info['width'],
                'type'         => Projects_Media_Utils::getMediaTypeByMimeType(
                    $info['mime_type']
                )
            ),
            array('id' => $id)
        );

        \QUI\Utils\System\File::move($file, $real_file);

        $File = $this->get( $id );
        $File->deleteCache();

        return $File;
    }

    /**
     * Return the parent id
     *
     * @param Integer $id
     * @return Integer|false
     */
    public function getParentIdFrom($id)
    {
        $id = (int)$id;

        if ( $id <= 1 ) {
            return false;
        }

        $result = QUI::getDataBase()->fetch(array(
            'select' => 'parent',
            'from' 	 => $this->getTable('relations'),
            'where'  => array(
                'child' => $id
            ),
            'limit' => 1
        ));

        if ( is_array( $result ) && isset( $result[0] ) ) {
            return (int)$result[0]['parent'];
        }

        return false;
    }

    /**
     * Returns the Media Trash
     *
     * @return Projects_Media_Trash
     */
    public function getTrash()
    {
        return new Projects_Media_Trash( $this );
    }

    /**
     * Parse a database entry to a media object
     *
     * @param array $result
     * @return Projects_Media_Item
     */
    public function _parseResultToItem($result)
    {
        switch ( $result['type'] )
        {
            case "image":
                return new Projects_Media_Image( $result, $this );
            break;

            case "folder":
                return new Projects_Media_Folder( $result, $this );
            break;
        }

        return new Projects_Media_File( $result, $this );
    }
}

?>