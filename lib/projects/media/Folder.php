<?php

/**
 * This file contains the Projects_Media_Folder
 */

/**
 * A media folder
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 */

class Projects_Media_Folder
      extends Projects_Media_Item
      implements Interface_Projects_Media_File
{
    /**
     * direct children of the folder
     * @var array
     */
    protected $_children = array();

    /**
     * (non-PHPdoc)
     * @see Interface_Projects_Media_File::activate()
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

            } catch ( QException $Exception )
            {
                System_Log::writeException( $Exception );
            }
        }

        // Cacheordner erstellen
        $this->createCache();
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Projects_Media_File::deactivate()
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

            } catch ( QException $Exception )
            {
                System_Log::writeException( $Exception );
            }
        }

        $this->deleteCache();
    }

    /**
     * (non-PHPdoc)
     * @see Projects_Media_Item::delete()
     */
    public function delete()
    {
        $children = $this->_getAllRecursiveChildrenIds();

        // move files to the temp folder
        // and delete the files first
        foreach ( $children as $id )
        {
            try
            {
                $File = $this->_Media->get( $id );

                if ( Projects_Media_Utils::isFolder( $File ) === false ) {
                    $File->delete();
                }

            } catch ( QException $Exception )
            {
                System_Log::writeException( $Exception );
            }
        }

        // now delete all sub folders
        $folders = $this->_getAllRecursiveChildrenIds();

        foreach ( $children as $id )
        {
            try
            {
                $File = $this->_Media->get( $id );

                if ( Projects_Media_Utils::isFolder( $File ) === false ) {
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


            } catch ( QException $Exception )
            {
                System_Log::writeException( $Exception );
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

        Utils_System_File::unlink( $this->getFullPath() );


        // delete cache
        $this->deleteCache();
    }

    /**
     * (non-PHPdoc)
     * @see Projects_Media_Item::destroy()
     */
    public function destroy()
    {
        // nothing
        // folders are not in the trash
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Projects_Media_File::restore()
     *
     * @param Projects_Media_Folder $Parent
     */
    public function restore(Projects_Media_Folder $Parent)
    {
        // nothing
        // folders are not in the trash
    }

    /**
     * (non-PHPdoc)
     * @see Projects_Media_Item::save()
     */
    public function save()
    {

    }

    /**
     * (non-PHPdoc)
     * @see Projects_Media_Item::rename()
     *
     * @param String $newname - new name for the folder
     */
    public function rename($newname)
    {
        if ( $newname == $this->getAttribute('name') ) {
            return;
        }

        // check if a folder with the new name exist
        $Parent = $this->getParent();

        if ( $Parent->childWithNameExists( $newname ) )
        {
            throw new QException(
                'Ein Ordner mit dem gleichen Namen existiert bereits.', 403
            );
        }

        $PDO      = QUI::getDataBase()->getPDO();
        $old_path = $this->getPath();
        $new_path = $Parent->getPath() . $newname;

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
                'name' => $newname,
                'file' => $new_path
            ),
            array('id' => $this->getId())
        );

        Utils_System_File::move(
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
     * @see Projects_Media_Item::moveTo()
     *
     * @param Projects_Media_Folder $Folder
     */
    public function moveTo(Projects_Media_Folder $Folder)
    {
        $Parent = $this->getParent();

        if ( $Folder->getId() === $Parent->getId() ) {
            return;
        }


        if ( $Folder->childWithNameExists( $this->getAttribute('name') ) )
        {
            throw new QException(
                'Ein Ordner mit dem gleichen Namen existiert bereits.', 403
            );
        }

        $PDO      = QUI::getDataBase()->getPDO();
        $old_path = $this->getPath();
        $new_path = $Folder->getPath() .'/'. $this->getAttribute('name');

        $old_path = Utils_String::replaceDblSlashes( $old_path );
        $new_path = Utils_String::replaceDblSlashes( $new_path );


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

        Utils_System_File::move(
            $this->_Media->getFullPath() . $old_path,
            $this->_Media->getFullPath() . $new_path
        );

        // @todo rename cache instead of delete
        $this->deleteCache();

        $this->setAttribute('file', $new_path);
    }

    /**
     * (non-PHPdoc)
     * @see Projects_Media_Item::copyTo()
     *
     * @param Projects_Media_Folder $Folder
     */
    public function copyTo(Projects_Media_Folder $Folder)
    {
        if ( $Folder->childWithNameExists( $this->getAttribute('name') ) )
        {
            throw new QException(
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

            } catch ( QException $Exception )
            {
                System_Log::writeException( $Exception );
            }
        }
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

            } catch ( QException $Exception )
            {
                System_Log::writeException( $Exception );
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
            } catch ( QException $Exception )
            {
                System_Log::writeException( $Exception );
            }
        }

        return $folders;
    }

    /**
     * Return a file from the folder by name
     *
     * @param String $filename
     * @return Projects_Media_Item
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
            throw new QException('File '. $filename .' not found', 404);
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
            $Child = $this->getChildByName( $name );

            return true;
        } catch ( \QException $e )
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
     * @see Interface_Projects_Media_File::createCache()
     */
    public function createCache()
    {
        if ( !$this->getAttribute('active') ) {
            return true;
        }

        $cache_dir = CMS_DIR . $this->_Media->getCacheDir() . $this->getAttribute('file');

        if ( Utils_System_File::mkdir($cache_dir) ) {
            return true;
        }

        throw new QException(
            'createCache() Error; Could not create Folder '. $cache_dir,
            506
        );
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Projects_Media_File::deleteCache()
     */
    public function deleteCache()
    {
        Utils_System_File::unlink(
            $this->_Media->getAttribute('cache_dir') . $this->getAttribute('file')
        );

        return true;
    }

    /**
     * Adds / create a subfolder
     *
     * @param String $foldername - Name of the new folder
     * @return Projects_Media_Folder
     * @throws QException
     */
    public function createFolder($foldername)
    {
        // Namensprüfung wegen unerlaubten Zeichen
        Projects_Media_Utils::checkFolderName( $foldername );

        // Whitespaces am Anfang und am Ende rausnehmen
        $new_name = trim( $foldername );


        $User = QUI::getUserBySession();
        $dir  = $this->_Media->getFullPath() . $this->getPath();

        if ( is_dir($dir . $new_name) )
        {
            throw new QException(
                'Der Ordner existiert schon ' . $dir . $new_name,
                701
            );
        }

        Utils_System_File::mkdir( $dir . $new_name );

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

        throw new QException(
            'Der Ordner konnte nicht erstellt werden',
            507
        );
    }

    /**
     * Uploads a file to the Folder
     *
     * @param String $file - Path to the File
     *
     * @return Projects_Media_Item
     * @throws QException
     */
    public function uploadFile($file)
    {
        if ( !file_exists($file) ) {
            throw new QException( 'Datei existiert nicht.', 404 );
        }

        if ( is_dir($file) ) {
            return $this->_uploadFolder( $file );
        }

        $fileinfo = Utils_System_File::getInfo( $file );
        $filename = Projects_Media_Utils::stripMediaName( $fileinfo['basename'] );

        // if no ending, we search for one
        if ( !isset($fileinfo['extension']) || empty($fileinfo['extension']) )
        {
            $filename .= Utils_System_File::getEndingByMimeType(
                $fileinfo['mime_type']
            );
        }

        $new_file = $this->getFullPath() . $filename;

        if ( file_exists( $new_file ) ) {
            throw new QException( $filename .' existiert bereits', 705 );
        }

        // copy the file to the media
        Utils_System_File::copy( $file, $new_file );


        // create the database entry
        $User      = QUI::getUserBySession();
        $table     = $this->_Media->getTable();
        $table_rel = $this->_Media->getTable( 'relations' );

        $new_file_info = Utils_System_File::getInfo( $new_file );
        $title         = str_replace( '_', ' ', $new_file_info['filename'] );

        if ( empty($new_file_info['filename']) ) {
            $new_file_info['filename'] = time();
        }

        QUI::getDataBase()->insert($table, array(
            'name' 	    => $new_file_info['filename'],
            'title'     => $title,
            'short'     => $title,
            'file' 	    => $this->getAttribute('file') . $new_file_info['basename'],
            'alt' 	    => $title,
            'c_date'    => date('Y-m-d h:i:s'),
            'e_date'    => date('Y-m-d h:i:s'),
            'c_user'    => $User->getId(),
            'e_user'    => $User->getId(),
            'mime_type' => $new_file_info['mime_type'],

            'type' => Projects_Media_Utils::getMediaTypeByMimeType(
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

        /* @var $File Projects_Media_File */
        $File = $this->_Media->get( $id );
        $File->generateMD5();
        $File->generateSHA1();

        return $File;
    }

    /**
     * If the file is a folder
     *
     * @param String $path - Path to the dir
     * @param Projects_Media_Folder $Folder - Uploaded Folder
     *
     * @return Projects_Media_Item
     */
    protected function _uploadFolder($path, $Folder=false)
    {
        $files = Utils_System_File::readDir( $path );

        foreach ( $files as $file )
        {
            // subfolders
            if ( is_dir( $path .'/'. $file ) )
            {
                $foldername = Projects_Media_Utils::stripFolderName( $file );

                try
                {
                    $NewFolder = $this->getChildByName( $foldername );

                } catch ( QException $Exception )
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

?>