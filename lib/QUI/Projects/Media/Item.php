<?php

/**
 * This file contains the \QUI\Projects\Media\Item
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Projects\Media;
use QUI\Utils\System\File as QUIFile;

/**
 * A media item
 * the parent class of each media entry
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 */

abstract class Item extends QUI\QDOM
{
    /**
     * internal media object
     * @var QUI\Projects\Media
     */
    protected $_Media = null;

    /**
     * internal parent id (use ->getParentId())
     * @var Integer
     */
    protected $_parent_id = false;

    /**
     * Path to the real file
     * @var String
     */
    protected $_file;

    /**
     * constructor
     *
     * @param array $params 		- item attributes
     * @param \QUI\Projects\Media $Media - Media of the file
     */
    public function __construct($params, Media $Media)
    {
        $this->_Media = $Media;
        $this->setAttributes( $params );

        $this->_file = CMS_DIR . $this->_Media->getPath() . $this->getPath();

        if ( !file_exists( $this->_file ) )
        {
            QUI::getMessagesHandler()->addAttention(
                'File '. $this->_file .' ('. $this->getId() .') doesn\'t exist'
            );

            return;
        }

        $this->setAttribute( 'filesize', QUIFile::getFileSize( $this->_file ) );
        $this->setAttribute( 'cache_url', URL_DIR . $this->_Media->getCacheDir() . $this->getPath() );
        $this->setAttribute( 'url', $this->getUrl() );
    }

    /**
     * Returns the id of the item
     * @return Integer
     */
    public function getId()
    {
        return (int)$this->getAttribute('id');
    }

    /**
     * API Methods - Generell important file operations
     */

    /**
     * Activate the file
     * The file is now public
     */
    public function activate()
    {
        try
        {
            // activate the parents, otherwise the file is not accessible
            $this->getParent()->activate();

        } catch ( QUI\Exception $Exception )
        {
            // has no parent
        }

        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array('active' => 1),
            array('id' => $this->getId())
        );

        $this->setAttribute('active', 1);


        if ( method_exists( $this, 'deleteCache' ) ) {
            $this->deleteCache();
        }

        if ( method_exists( $this, 'createCache' ) ) {
            $this->createCache();
        }
    }

    /**
     * Deactivate the file
     * the file is no longer public
     */
    public function deactivate()
    {
        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array('active' => 0),
            array('id' => $this->getId())
        );

        $this->setAttribute('active', 0);

        if ( method_exists( $this, 'deleteCache' ) ) {
            $this->deleteCache();
        }
    }

    /**
     * Save the file to the database
     * The id attribute can not be overwritten
     */
    public function save()
    {
        // Rename the file, if necessary
        $this->rename( $this->getAttribute('name') );


        $watermark = $this->getAttribute('watermark');

        if ( is_array( $watermark ) ) {
            $watermark = json_encode( $watermark );
        }

        $roundcorners = $this->getAttribute('roundcorners');

        if ( is_array( $roundcorners ) ) {
            $roundcorners = json_encode( $roundcorners );
        }

        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array(
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

        if ( method_exists( $this, 'deleteCache' ) ) {
            $this->deleteCache();
        }

        if ( method_exists( $this, 'createCache' ) ) {
            $this->createCache();
        }
    }

    /**
     * Delete the file and move it to the trash
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        if ( $this->isDeleted() ) {
            throw new QUI\Exception( 'File is already deleted', 400 );
        }


        $Media = $this->_Media;
        $First = $Media->firstChild();

        // Move file to the temp folder
        $original   = $this->getFullPath();
        $var_folder = VAR_DIR .'media/'. $Media->getProject()->getAttribute('name') .'/';

        if ( !is_file( $original ) ) {
            throw new QUI\Exception( 'Original File is not a File', 400 );
        }

        if ( $First->getFullPath() == $original ) {
            throw new QUI\Exception( 'You cannot delete the root file', 400 );
        }


        try
        {
            QUIFile::unlink( $var_folder . $this->getId() );

        } catch ( QUI\Exception $Exception )
        {
            \QUI::getMessagesHandler()->addAttention(
                $Exception->getMessage()
            );
        }

        try
        {
            QUIFile::mkdir( $var_folder );
            QUIFile::move( $original, $var_folder . $this->getId() );

        } catch ( QUI\Exception $Exception )
        {
            \QUI::getMessagesHandler()->addAttention(
                $Exception->getMessage()
            );
        }


        // change db entries
        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array(
                'deleted' => 1,
                'active'  => 0,
                'file'    => ''
            ),
            array(
                'id' => $this->getId()
            )
        );

        QUI::getDataBase()->delete(
            $this->_Media->getTable('relations'),
            array('child' => $this->getId())
        );

        // Cache vom File löschen
        if ( method_exists( $this, 'deleteCache' ) ) {
            $this->deleteCache();
        }
    }

    /**
     * Destroy the File complete from the DataBase and from the Filesystem
     *
     * @throws QUI\Exception
     * @todo muss in den trash
     */
    public function destroy()
    {
        if ( $this->isActive() ) {
            throw new QUI\Exception( 'Only inactive files can be destroyed' );
        }

        if ( $this->isDeleted() ) {
            throw new QUI\Exception( 'Only deleted files can be destroyed' );
        }

        $Media = $this->_Media;

        // get the trash file and destroy it
        $var_folder = VAR_DIR .'media/'. $Media->getProject()->getAttribute('name') .'/';
        $var_file   = $var_folder . $this->getId();

        QUIFile::unlink( $var_file );

        QUI::getDataBase()->delete($this->_Media->getTable(), array(
            'id' => $this->getId()
        ));
    }

    /**
     * Returns if the file is active or not
     *
     * @return Bool
     */
    public function isActive()
    {
        return $this->getAttribute('active') ? true : false;
    }

    /**
     * Returns if the file is deleted or not
     *
     * @return Bool
     */
    public function isDeleted()
    {
        return $this->getAttribute('deleted') ? true : false;
    }

    /**
     * Rename the File
     *
     * @param String $newname - The new name what the file get
     * @throws \QUI\Exception
     */
    public function rename($newname)
    {
        $original  = $this->getFullPath();
        $extension = QUI\Utils\String::pathinfo( $original, PATHINFO_EXTENSION );
        $Parent    = $this->getParent();

        $new_full_file = $Parent->getFullPath() . $newname .'.'. $extension;
        $new_file      = $Parent->getPath() . $newname .'.'. $extension;

        if ( $new_full_file == $original ) {
            return;
        }

        // throws the \QUI\Exception
        $fileParts = explode( '/', $new_file );

        foreach ( $fileParts as $filePart ) {
            Utils::checkMediaName( $filePart );
        }


        if ( $Parent->childWithNameExists( $newname ) )
        {
            throw new QUI\Exception(
                'Eine Datei mit dem Namen '. $newname .'existiert bereits.
                Bitte wählen Sie einen anderen Namen.'
            );
        }

        if ( $Parent->fileWithNameExists( $newname .'.'. $extension ) )
        {
            throw new QUI\Exception(
                'Eine Datei mit dem Namen '. $newname .'existiert bereits.
                Bitte wählen Sie einen anderen Namen.'
            );
        }



        if ( method_exists( $this, 'deleteCache' ) ) {
            $this->deleteCache();
        }

        \QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array(
                'name' => $newname,
                'file' => $new_file
            ),
            array(
                'id' => $this->getId()
            )
        );

        $this->setAttribute( 'name', $newname );
        $this->setAttribute( 'file', $new_file );

        QUIFile::move( $original, $new_full_file );

        if ( method_exists($this, 'createCache') ) {
            $this->createCache();
        }
    }

    /**
     * Get Parent Methods
     */

    /**
     * Return the parent id
     *
     * @return Integer
     */
    public function getParentId()
    {
        if ( $this->_parent_id ) {
            return $this->_parent_id;
        }

        $id = $this->getId();

        if ( $id === 1 ) {
            return false;
        }

        $this->_parent_id = $this->_Media->getParentIdFrom( $id );

        return $this->_parent_id;
    }

    /**
     * Return all parent ids
     *
     * @return array
     */
    public function getParentIds()
    {
        if ( $this->getId() === 1 ) {
            return array();
        }

        $parents = array();
        $id      = $this->getId();

        while ( $id = $this->_Media->getParentIdFrom($id) ) {
            $parents[] = $id;
        }

        return array_reverse( $parents );
    }

    /**
     * Return the Parent Media Item Object
     *
     * @return \QUI\Projects\Media\Folder
     * @throws \QUI\Exception
     */
    public function getParent()
    {
        return $this->_Media->get( $this->getParentId() );
    }

    /**
     * Return all Parents
     *
     * @return array
     */
    public function getParents()
    {
        $ids     = $this->getParentIds();
        $parents = array();

        foreach ( $ids as $id ) {
            $parents[] = $this->_Media->get( $id );
        }

        return $parents;
    }

    /**
     * Path and URL Methods
     */

    /**
     * Return the path of the file, without host, url dir or cms dir
     *
     * @return String
     */
    public function getPath()
    {
        return $this->getAttribute('file');
    }

    /**
     * Return the fullpath of the file
     *
     * @return String
     */
    public function getFullPath()
    {
        return $this->_Media->getFullPath() . $this->getAttribute('file');
    }

    /**
     * Returns the url from the file
     *
     * @param Bool $rewrite - false = image.php, true = rewrited URL
     * @return String
     */
    public function getUrl($rewrite=false)
    {
        if ( $rewrite == false )
        {
            $Project = $this->_Media->getProject();

            $str = 'image.php?id='. $this->getId() .'&project='. $Project->getAttribute('name') .'&qui=1';

            if ( $this->getAttribute('maxheight') ) {
                $str .= '&maxheight='. $this->getAttribute('maxheight');
            }

            if ( $this->getAttribute('maxwidth') ) {
                $str .= '&maxwidth='. $this->getAttribute('maxwidth');
            }

            return $str;
        }

        if ( $this->getAttribute('active') == 1 ) {
            return  URL_DIR . $this->_Media->getCacheDir() . $this->getAttribute('file');
        }

        return '';
    }

    /**
     * move the item to another folder
     * @param \QUI\Projects\Media\Folder $Folder - the new folder of the file
     * @throws QUI\Exception
     */
    public function moveTo(Folder $Folder)
    {
        // check if a child with the same name exist
        if ( $Folder->fileWithNameExists( $this->getAttribute('name') ) )
        {
            throw new QUI\Exception(
                'File with a same Name exist in folder '. $Folder->getAttribute('name')
            );
        }

        $Parent   = $this->getParent();
        $old_path = $this->getFullPath();

        $new_file = str_replace(
            $Parent->getAttribute('file'),
            $Folder->getAttribute('file'),
            $this->getAttribute('file')
        );

        $new_path = $this->_Media->getFullPath() . $new_file;

        // update file path
        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array(
                'file' => $new_file
            ),
            array(
                'id' => $this->getId()
            )
        );

        // set the new parent relationship
        QUI::getDataBase()->update(
            $this->_Media->getTable('relations'),
            array(
                'parent' => $Folder->getId()
            ),
            array(
                'parent' => $Parent->getId(),
                'child'  => $this->getId()
            )
        );

        // move file on the real directory
        QUIFile::move( $old_path, $new_path );


        // delete the file cache
        // @todo move the cache too
        if ( method_exists( $this, 'deleteCache' ) ) {
            $this->deleteCache();
        }

        // update internal references
        $this->setAttribute( 'file', $new_file );

        $this->_parent_id = $Folder->getId();
    }

    /**
     * copy the item to another folder
     * @param \QUI\Projects\Media\Folder $Folder
     * @return \QUI\Projects\Media\Item - The new file
     */
    public function copyTo(Folder $Folder)
    {
        $File = $Folder->uploadFile( $this->getFullPath() );

        $File->setAttribute( 'title', $this->getAttribute('title') );
        $File->setAttribute( 'alt', $this->getAttribute('alt') );
        $File->setAttribute( 'short', $this->getAttribute('short') );

        $File->setAttribute( 'watermark', $this->getAttribute('watermark') );
        $File->setAttribute( 'roundcorners', $this->getAttribute('roundcorners') );
        $File->save();

        return $File;
    }

    /**
     * Return the Media of the item
     *
     * @return QUI\Projects\Media
     */
    public function getMedia()
    {
        return $this->_Media;
    }

    /**
     * Return the Project of the item
     */
    public function getProject()
    {
        return $this->getMedia()->getProject();
    }
}
