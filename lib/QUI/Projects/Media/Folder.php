<?php

/**
 * This file contains the \QUI\Projects\Media\Folder
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\System\File as FileUtils;
use QUI\Utils\String as StringUtils;

/**
 * A media folder
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 */

class Folder extends Item implements QUI\Interfaces\Projects\Media\File
{
    /**
     * direct children of the folder
     * @var array
     */
    protected $_children = array();

    /**
     * (non-PHPdoc)
     * @see QUI\Interfaces\Projects\Media\File::activate()
     */
    public function activate()
    {
        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array('active' => 1),
            array('id' => $this->getId())
        );

        $this->setAttribute('active', 1);

        // activate resursive to the top
        $Media       = $this->_Media;
        $parents_ids = $this->getParentIds();

        foreach ( $parents_ids as $id )
        {
            try
            {
                $Item = $Media->get( $id );
                $Item->activate();

            } catch ( QUI\Exception $Exception )
            {
                QUI\System\Log::writeException( $Exception );
            }
        }

        // Cacheordner erstellen
        $this->createCache();
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Interfaces\Projects\Media\File::deactivate()
     */
    public function deactivate()
    {
        if ( $this->isActive() === false ) {
            return;
        }

        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array('active' => 0),
            array('id' => $this->getId())
        );

        $this->setAttribute('active', 0);

        // Images / Folders / Files rekursive deactivasion
        $ids   = $this->_getAllRecursiveChildrenIds();
        $Media = $this->_Media;

        foreach ( $ids as $id )
        {
            try
            {
                $Item = $Media->get( $id );
                $Item->deactivate();

            } catch ( QUI\Exception $Exception )
            {
                QUI\System\Log::writeException( $Exception );
            }
        }

        $this->deleteCache();
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Projects\Media\Item::delete()
     */
    public function delete()
    {
        if ( $this->isDeleted() ) {
            throw new QUI\Exception( 'Folder is already deleted', 400 );
        }

        if ( $this->getId() == 1 ) {
            throw new QUI\Exception( 'Root cannot deleted', 400 );
        }


        $children = $this->_getAllRecursiveChildrenIds();

        // move files to the temp folder
        // and delete the files first
        foreach ( $children as $id )
        {
            try
            {
                $File = $this->_Media->get( $id );

                if ( MediaUtils::isFolder( $File ) === false ) {
                    $File->delete();
                }

            } catch ( QUI\Exception $Exception )
            {
                QUI\System\Log::writeException( $Exception );
            }
        }

        // now delete all sub folders
        foreach ( $children as $id )
        {
            try
            {
                $File = $this->_Media->get( $id );

                if ( MediaUtils::isFolder( $File ) === false ) {
                    continue;
                }

                 // delete database entries
                QUI::getDataBase()->delete(
                    $this->_Media->getTable(),
                    array('id' => $id)
                );

                QUI::getDataBase()->delete(
                    $this->_Media->getTable('relations'),
                    array('child' => $id)
                );


            } catch ( QUI\Exception $Exception )
            {
                QUI\System\Log::writeException( $Exception );
            }
        }

        // delete the own database entries
        QUI::getDataBase()->delete(
            $this->_Media->getTable(),
            array('id' => $this->getId())
        );

        QUI::getDataBase()->delete(
            $this->_Media->getTable('relations'),
            array('child'  => $this->getId())
        );

        FileUtils::unlink( $this->getFullPath() );


        // delete cache
        $this->deleteCache();
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Projects\Media\Item::destroy()
     */
    public function destroy()
    {
        // nothing
        // folders are not in the trash
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Interfaces\Projects\Media\File::restore()
     *
     * @param QUI\Projects\Media\Folder $Parent
     */
    public function restore(QUI\Projects\Media\Folder $Parent)
    {
        // nothing
        // folders are not in the trash
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Projects\Media\Item::save()
     */
    public function save()
    {

    }

    /**
     * (non-PHPdoc)
     * @see QUI\Projects\Media\Item::rename()
     *
     * @param String $newname - new name for the folder
     * @throws QUI\Exception
     */
    public function rename($newname)
    {
        if ( $newname == $this->getAttribute('name') ) {
            return;
        }

        if ( $this->getId() == 1 )
        {
            throw new QUI\Exception(
                'Der Media-Root-Verzeichnis eines Projektes kann nicht umbenannt werden'
            );
        }

        // check if a folder with the new name exist
        $Parent = $this->getParent();

        if ( $Parent->childWithNameExists( $newname ) )
        {
            throw new QUI\Exception(
                'Ein Ordner mit dem gleichen Namen existiert bereits.', 403
            );
        }

        $PDO      = QUI::getDataBase()->getPDO();
        $old_path = $this->getPath() .'/';
        $new_path = $Parent->getPath() .'/'. $newname;

        $new_path = StringUtils::replaceDblSlashes( $new_path );
        $old_path = StringUtils::replaceDblSlashes( $old_path );


        // update children paths
        $Statement = $PDO->prepare(
            "UPDATE ". $this->_Media->getTable() ."
             SET file = REPLACE(file, :oldpath, :newpath)
             WHERE file LIKE :search"
        );

        $Statement->bindValue( 'oldpath', $old_path .'/' );
        $Statement->bindValue( 'newpath', $new_path .'/' );
        $Statement->bindValue( 'search', $old_path ."/%" );

        $Statement->execute();

        $title = $this->getAttribute( 'title' );

        if ( $title == $this->getAttribute( 'name' ) ) {
            $title = $newname;
        }

        // update me
        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array(
                'name'  => $newname,
                'file'  => StringUtils::replaceDblSlashes( $new_path .'/' ),
                'title' => $title
            ),
            array('id' => $this->getId())
        );

        FileUtils::move(
            $this->_Media->getFullPath() . $old_path,
            $this->_Media->getFullPath() . $new_path
        );

        // @todo rename cache instead of delete
        $this->deleteCache();

        $this->setAttribute('name', $newname);
        $this->setAttribute('file', $new_path);
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Projects\Media\Item::moveTo()
     *
     * @param QUI\Projects\Media\Folder $Folder
     * @throws QUI\Exception
     */
    public function moveTo(QUI\Projects\Media\Folder $Folder)
    {
        $Parent = $this->getParent();

        if ( $Folder->getId() === $Parent->getId() ) {
            return;
        }


        if ( $Folder->childWithNameExists( $this->getAttribute('name') ) )
        {
            throw new QUI\Exception(
                'Ein Ordner mit dem gleichen Namen existiert bereits.', 403
            );
        }

        $PDO      = QUI::getDataBase()->getPDO();
        $old_path = $this->getPath();
        $new_path = $Folder->getPath() .'/'. $this->getAttribute('name');

        $old_path = StringUtils::replaceDblSlashes( $old_path );
        $new_path = StringUtils::replaceDblSlashes( $new_path );


        // update children paths
        $Statement = $PDO->prepare(
            "UPDATE ". $this->_Media->getTable() ."
             SET file = REPLACE(file, :oldpath, :newpath)
             WHERE file LIKE :search"
        );

        $Statement->bindValue( 'oldpath', $old_path .'/' );
        $Statement->bindValue( 'newpath', $new_path .'/' );
        $Statement->bindValue( 'search', $old_path ."%" );

        $Statement->execute();

        // update me
        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array(
                'file' => $new_path
            ),
            array('id' => $this->getId())
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

        FileUtils::move(
            $this->_Media->getFullPath() . $old_path,
            $this->_Media->getFullPath() . $new_path
        );

        // @todo rename cache instead of delete
        $this->deleteCache();

        $this->setAttribute('file', $new_path);
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Projects\Media\Item::copyTo()
     *
     * @param QUI\Projects\Media\Folder $Folder
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception
     */
    public function copyTo(QUI\Projects\Media\Folder $Folder)
    {
        if ( $Folder->childWithNameExists( $this->getAttribute('name') ) )
        {
            throw new QUI\Exception(
                'Ein Ordner mit dem gleichen Namen existiert bereits.', 403
            );
        }

        // copy me
        $Copy = $Folder->createFolder( $this->getAttribute('name') );

        $Copy->setAttributes( $this->getAttributes() );
        $Copy->save();


        // copy the children
        $ids = $this->getChildrenIds();

        foreach ( $ids as $id )
        {
            try
            {
                $Item = $this->_Media->get( $id );
                $Item->copyTo( $Copy );

            } catch ( QUI\Exception $Exception )
            {
                QUI\System\Log::writeException( $Exception );
            }
        }

        return $Copy;
    }

    /**
     * Returns all children in the folder
     *
     * @todo implement order
     * @return array
     */
    public function getChildren()
    {
        $this->_children = array();

        $ids = $this->getChildrenIds();

        foreach ( $ids as $id )
        {
            try
            {
                $this->_children[] = $this->_Media->get( $id );

            } catch ( QUI\Exception $Exception )
            {
                QUI\System\Log::writeException( $Exception );
            }
        }

        return $this->_children;
    }

    /**
     * Return the children ids ( not resursive )
     *
     * @todo implement order
     * @return array
     */
    public function getChildrenIds()
    {
        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable('relations');

        // Sortierung
        $order = 'name';

        switch ( $order )
        {
            case 'c_date':
                $order_by = 'find_in_set('. $table .'.type, \'folder\') DESC, '. $table .'.c_date';
            break;

            case 'c_date DESC':
                $order_by = 'find_in_set('. $table .'.type, \'folder\') DESC, '. $table .'.c_date DESC';
            break;

            default:
            case 'name':
                $order_by = 'find_in_set('. $table .'.type, \'folder\') DESC, '. $table .'.name';
            break;
        }


        $fetch = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from'   => array(
                $table,
                $table_rel
            ),
            'where' => array(
                $table_rel .'.parent' => $this->getId(),
                $table_rel .'.child'  => '`'. $table .'.id`',
                $table .'.deleted'    => 0
            ),
            'order' => $order_by
        ));

        $result = array();

        foreach ( $fetch as $entry ) {
            $result[] = (int)$entry['id'];
        }

        return $result;
    }

    /**
     * Returns the count of the children
     *
     * @return Integer
     */
    public function hasChildren()
    {
        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable('relations');

        $result = QUI::getDataBase()->fetch(array(
            'count' => 'children',
            'from'  => array(
                $table,
                $table_rel
            ),
            'where' => array(
                $table_rel .'.parent' => $this->getId(),
                $table_rel .'.child'  => '`'. $table .'.id`',
                $table .'.deleted'    => 0
            )
        ));

        if ( isset($result[0]) && isset($result[0]['children']) ) {
            return (int)$result[0]['children'];
        }

        return 0;
    }

    /**
     * Returns the count of the children
     *
     * @return Integer
     */
    public function hasSubFolders()
    {
        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable('relations');

        $result = QUI::getDataBase()->fetch(array(
            'count' => 'children',
            'from'  => array(
                $table,
                $table_rel
            ),
            'where' => array(
                $table_rel .'.parent' => $this->getId(),
                $table_rel .'.child'  => '`'. $table .'.id`',
                $table .'.deleted'    => 0,
                $table .'.type'       => 'folder'
            )
        ));

        if ( isset($result[0]) && isset($result[0]['children']) ) {
            return (int)$result[0]['children'];
        }

        return 0;
    }

    /**
     * Returns only the sub folders
     *
     * @return array
     */
    public function getSubFolders()
    {
        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable('relations');

        $result = QUI::getDataBase()->fetch(array(
            'from'  => array(
                $table,
                $table_rel
            ),
            'where' => array(
                $table_rel .'.parent' => $this->getId(),
                $table_rel .'.child'  => '`'. $table .'.id`',
                $table .'.deleted'    => 0,
                $table .'.type'       => 'folder'
            ),
            'order' => 'name'
        ));

        $folders = array();

        foreach ( $result as $entry )
        {
            try
            {
                $folders[] = $this->_Media->get( (int)$entry['id'] );
            } catch ( QUI\Exception $Exception )
            {
                QUI\System\Log::writeException( $Exception );
            }
        }

        return $folders;
    }

    /**
     * Return a file from the folder by name
     *
     * @param String $filename
     * @return QUI\Projects\Media\Item
     * @throws QUI\Exception
     */
    public function getChildByName($filename)
    {
        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable( 'relations' );

        $result = QUI::getDataBase()->fetch(array(
            'select' => array(
                $table .'.id'
            ),
            'from'  => array(
                $table,
                $table_rel
            ),
            'where' => array(
                $table_rel .'.parent' => $this->getId(),
                $table_rel .'.child'  => '`'. $table .'.id`',
                $table .'.deleted'    => 0,
                $table .'.name'		  => $filename
            ),
            'limit' => 1
        ));

        if ( !isset( $result[0] ) ) {
            throw new QUI\Exception('File '. $filename .' not found', 404);
        }

        return $this->_Media->get( (int)$result[0]['id'] );
    }

    /**
     * Return true if a child with the name exist
     *
     * @param String $name - name (my_holiday)
     * @return Bool
     */
    public function childWithNameExists($name)
    {
        try
        {
            $this->getChildByName( $name );
            return true;

        } catch ( QUI\Exception $Exception )
        {

        }

        return false;
    }

    /**
     * Return true if a file with the filename in the folder exists
     *
     * @param String $file - filename (my_holiday.png)
     * @return Bool
     */
    public function fileWithNameExists($file)
    {
        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable('relations');

        $result = QUI::getDataBase()->fetch(array(
            'select' => array(
                $table .'.id'
            ),
            'from'  => array(
                $table,
                $table_rel
            ),
            'where' => array(
                $table_rel .'.parent' => $this->getId(),
                $table_rel .'.child'  => '`'. $table .'.id`',
                $table .'.file'		  => $this->getPath() . $file
            ),
            'limit' => 1
        ));

        return isset($result[0]) ? true : false;
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Interfaces\Projects\Media\File::createCache()
     */
    public function createCache()
    {
        if ( !$this->getAttribute('active') ) {
            return true;
        }

        $cache_dir = CMS_DIR . $this->_Media->getCacheDir() . $this->getAttribute('file');

        if ( FileUtils::mkdir($cache_dir) ) {
            return true;
        }

        throw new QUI\Exception(
            'createCache() Error; Could not create Folder '. $cache_dir,
            506
        );
    }

    /**
     * (non-PHPdoc)
     * @see QUI\Interfaces\Projects\Media\File::deleteCache()
     */
    public function deleteCache()
    {
        FileUtils::unlink(
            $this->_Media->getAttribute('cache_dir') . $this->getAttribute('file')
        );

        return true;
    }

    /**
     * Adds / create a subfolder
     *
     * @param String $foldername - Name of the new folder
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception
     */
    public function createFolder($foldername)
    {
        // Namensprüfung wegen unerlaubten Zeichen
        MediaUtils::checkFolderName( $foldername );

        // Whitespaces am Anfang und am Ende rausnehmen
        $new_name = trim( $foldername );


        $User = QUI::getUserBySession();
        $dir  = $this->_Media->getFullPath() . $this->getPath();

        if ( is_dir( $dir . $new_name ) )
        {
            // prüfen ob dieser ordner schon als kind existiert
            // wenn nein, muss dieser ordner in der DB angelegt werden

            try
            {
                $children = $this->getChildByName( $new_name );

            } catch ( QUI\Exception $Exception )
            {
                $children = false;
            }

            if ( $children )
            {
                throw new QUI\Exception(
                    'Der Ordner existiert schon ' . $dir . $new_name,
                    701
                );
            }
        }

        FileUtils::mkdir( $dir . $new_name );

        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable('relations');

        // In die DB legen
        QUI::getDataBase()->insert($table, array(
            'name' 	    => $new_name,
            'title'     => $new_name,
            'short'     => $new_name,
            'type' 	    => 'folder',
            'file' 	    => $this->getAttribute('file') . $new_name .'/',
            'alt' 	    => $new_name,
            'c_date'    => date('Y-m-d h:i:s'),
            'e_date'    => date('Y-m-d h:i:s'),
            'c_user'    => $User->getId(),
            'e_user'    => $User->getId(),
            'mime_type' => 'folder',

            'watermark'    => $this->getAttribute('watermark'),
            'roundcorners' => $this->getAttribute('roundcorners')
        ));

        $id = QUI::getDataBase()->getPDO()->lastInsertId();

        QUI::getDataBase()->insert($table_rel, array(
            'parent' => $this->getId(),
            'child'  => $id
        ));

        if ( is_dir($dir.$new_name) ) {
            return $this->_Media->get( $id );
        }

        throw new QUI\Exception(
            'Der Ordner konnte nicht erstellt werden',
            507
        );
    }

    /**
     * Uploads a file to the Folder
     *
     * @param String $file - Path to the File
     *
     * @return QUI\Projects\Media\Item
     * @throws QUI\Exception
     */
    public function uploadFile($file)
    {
        if ( !file_exists( $file ) ) {
            throw new QUI\Exception( 'Datei existiert nicht.', 404 );
        }

        if ( is_dir( $file ) ) {
            return $this->_uploadFolder( $file );
        }

        $fileinfo = FileUtils::getInfo( $file );
        $filename = MediaUtils::stripMediaName( $fileinfo['basename'] );

        // if no ending, we search for one
        if ( !isset( $fileinfo['extension'] ) || empty( $fileinfo['extension'] ) )
        {
            $filename .= FileUtils::getEndingByMimeType(
                $fileinfo['mime_type']
            );
        }

        $new_file = $this->getFullPath() .'/'. $filename;

        if ( file_exists( $new_file ) ) {
            throw new QUI\Exception( $filename .' existiert bereits', 705 );
        }

        // copy the file to the media
        FileUtils::copy( $file, $new_file );


        // create the database entry
        $User      = QUI::getUserBySession();
        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable( 'relations' );

        $new_file_info = FileUtils::getInfo( $new_file );
        $title         = str_replace( '_', ' ', $new_file_info['filename'] );

        if ( empty( $new_file_info['filename'] ) ) {
            $new_file_info['filename'] = time();
        }

        $filePath = $this->getAttribute('file') .'/'. $new_file_info['basename'];

        if ( $this->getId() == 1 ) {
            $filePath = $new_file_info['basename'];
        }

        $filePath    = StringUtils::replaceDblSlashes( $filePath );
        $imageWidth  = '';
        $imageHeight = '';

        if ( isset( $new_file_info['width'] ) && $new_file_info['width'] ) {
            $imageWidth = $new_file_info['width'];
        }

        if ( isset( $new_file_info['height'] ) && $new_file_info['height'] ) {
            $imageHeight = $new_file_info['height'];
        }


        QUI::getDataBase()->insert($table, array(
            'name' 	    => $new_file_info['filename'],
            'title'     => $title,
            'short'     => $title,
            'file' 	    => $filePath,
            'alt' 	    => $title,
            'c_date'    => date('Y-m-d h:i:s'),
            'e_date'    => date('Y-m-d h:i:s'),
            'c_user'    => $User->getId(),
            'e_user'    => $User->getId(),
            'mime_type' => $new_file_info['mime_type'],
            'image_width'  => $imageWidth,
            'image_height' => $imageHeight,

            'type' => MediaUtils::getMediaTypeByMimeType(
                $new_file_info['mime_type']
            ),

            'watermark'    => $this->getAttribute('watermark'),
            'roundcorners' => $this->getAttribute('roundcorners')
        ));

        $id = QUI::getDataBase()->getPDO()->lastInsertId();

        QUI::getDataBase()->insert($table_rel, array(
            'parent' => $this->getId(),
            'child'  => $id
        ));

        /* @var $File QUI\Projects\Media\File */
        $File = $this->_Media->get( $id );
        $File->generateMD5();
        $File->generateSHA1();


        // if it is an image, than resize -> if needed
        if ( Utils::isImage( $File ) )
        {
            /* @var $File Image */
            $resizeData = $File->getResizeSize( 1200, 1200 );

            if ( $new_file_info['width'] > 1200 ||
                 $new_file_info['height'] > 1200 )
            {
                try
                {
                    QUI\Utils\Image::resize(
                        $new_file,
                        $new_file,
                        $resizeData['width'],
                        $resizeData['height']
                    );

                    QUI::getDataBase()->update($table, array(
                        'image_width'  => $resizeData['width'],
                        'image_height' => $resizeData['height'],
                    ), array(
                        'id' => $id
                    ));

                } catch ( QUI\Exception $Exception )
                {
                    QUI\System\Log::writeException( $Exception );
                }
            }
        }

        return $File;
    }

    /**
     * If the file is a folder
     *
     * @param String $path - Path to the dir
     * @param QUI\Projects\Media\Folder|Bool $Folder - (optional) Uploaded Folder
     * @return QUI\Projects\Media\Item
     */
    protected function _uploadFolder($path, $Folder=false)
    {
        $files = FileUtils::readDir( $path );

        foreach ( $files as $file )
        {
            // subfolders
            if ( is_dir( $path .'/'. $file ) )
            {
                $foldername = MediaUtils::stripFolderName( $file );

                try
                {
                    $NewFolder = $this->getChildByName( $foldername );

                } catch ( QUI\Exception $Exception )
                {
                    $NewFolder = $this->createFolder($foldername);
                }

                $this->_uploadFolder( $path .'/'. $file, $NewFolder );
                continue;
            }

            // import files
            if ( $Folder )
            {
                $Folder->uploadFile( $path .'/'. $file );
            } else
            {
                $this->uploadFile( $path .'/'. $file );
            }
        }

        return $this;
    }

    /**
     * Returns all ids from children under the folder
     *
     * @return Array
     */
    protected function _getAllRecursiveChildrenIds()
    {
        // own sql statement, not over the getChildren() method,
        // its better for performance
        $children = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from'   => $this->_Media->getTable(),
            'where'  => array(
                'file' => array(
                    'value' => $this->getAttribute('file'),
                    'type'  => 'LIKE%'
                )
            )
        ));

        $result = array();

        foreach ( $children as $child ) {
            $result[] = $child['id'];
        }

        return $result;
    }
}
