<?php

/**
 * This file contains the \QUI\Projects\Media\Utils
 */

namespace QUI\Projects\Media;

/**
 * Helper for the Media Center Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 */

class Utils
{
    /**
     * Returns the item array
     * the array is specially adapted for the media center
     *
     * @param \QUI\Projects\Media\Item $Item
     * @return array
     */
    static function parseForMediaCenter($Item)
    {
        if ( $Item->getId() === 1 )
        {
            return array(
                'icon'      => 'icon-picture',
                'icon80x80' => URL_BIN_DIR .'80x80/media.png',
                'id'        => $Item->getId(),
                'name'      => $Item->getAttribute('name'),
                'title'     => $Item->getAttribute('title'),
                'type'      => 'folder',
                'hasChildren'   => $Item->hasChildren(),
                'hasSubfolders' => $Item->hasSubFolders(),
                'active'        => true,
                'e_date'        => $Item->getAttribute('e_date')
            );
        }

        if ( $Item->getType() == 'QUI\\Projects\\Media\\Folder' )
        {
            /* @var $Item \QUI\Projects\Media\Folder */
            return array(
                'icon'          => 'icon-folder-close-alt',
                'icon80x80'     => URL_BIN_DIR .'80x80/extensions/folder.png',
                'id'            => $Item->getId(),
                'name'          => $Item->getAttribute('name'),
                'title'         => $Item->getAttribute('title'),
                'type'          => 'folder',
                'hasChildren'   => $Item->hasChildren(),
                'hasSubfolders' => $Item->hasSubfolders(),
                'active'        => $Item->isActive(),
                'e_date'        => $Item->getAttribute('e_date')
            );
        }


        $extension = self::getExtension( $Item->getAttribute('file') );

        $result = array(
            'icon'      => self::getIconByExtension( $extension ),
            'icon80x80' => self::getIconByExtension( $extension, '80x80' ),
            'id'        => $Item->getId(),
            'name'      => $Item->getAttribute('name'),
            'title'     => $Item->getAttribute('title'),
            'type'      => $Item->getType() === 'QUI\\Projects\\Media\\Image' ? 'image' : 'file',
            'url'       => $Item->getUrl(),
            'active'    => $Item->isActive(),
            'e_date'    => $Item->getAttribute('e_date'),
            'mimetype'  => $Item->getAttribute('mime_type')
        );

        return $result;
    }

    /**
     * Returns a suitable icon of a certain extension
     *
     * @param String $ext  - extenstion
     * @param String $size - 16x16, 80x80 (default = 16x16); optional
     *
     * @return String - Icon url
     *
     * @todo icons in config auslagern, somit einfacher erweiterbar
     */
    static function getIconByExtension($ext, $size='16x16')
    {
        switch ( $size )
        {
            case '16x16':
            case '80x80':
            break;

            // set default size
            default:
                $size = '16x16';
        }

        $extensions['16x16'] = array(
            'folder' => URL_BIN_DIR .'16x16/extensions/folder.png',
            'pdf'    => URL_BIN_DIR .'16x16/extensions/pdf.png',

            // Images
            'jpg'  => URL_BIN_DIR .'16x16/extensions/image.png',
            'jpeg' => URL_BIN_DIR .'16x16/extensions/image.png',
            'gif'  => URL_BIN_DIR .'16x16/extensions/image.png',
            'png'  => URL_BIN_DIR .'16x16/extensions/image.png',

            // Movie
            'avi'  => URL_BIN_DIR .'16x16/extensions/film.png',
            'mpeg' => URL_BIN_DIR .'16x16/extensions/film.png',
            'mpg'  => URL_BIN_DIR .'16x16/extensions/film.png',

            // Archiv
            'tar' => URL_BIN_DIR .'16x16/extensions/archive.png',
            'rar' => URL_BIN_DIR .'16x16/extensions/archive.png',
            'zip' => URL_BIN_DIR .'16x16/extensions/archive.png',
            'gz'  => URL_BIN_DIR .'16x16/extensions/archive.png',
            '7z'  => URL_BIN_DIR .'16x16/extensions/archive.png',

            //Office

            // Music
            'mp3' => URL_BIN_DIR .'16x16/extensions/sound.png',
            'ogg' => URL_BIN_DIR .'16x16/extensions/sound.png',
        );

        $extensions['80x80'] = array(
            'folder' => URL_BIN_DIR .'80x80/extensions/folder.png',
            'pdf'    => URL_BIN_DIR .'80x80/extensions/pdf.png',

            // Images
            'jpg'  => URL_BIN_DIR .'80x80/extensions/image.png',
            'jpeg' => URL_BIN_DIR .'80x80/extensions/image.png',
            'gif'  => URL_BIN_DIR .'80x80/extensions/image.png',
            'png'  => URL_BIN_DIR .'80x80/extensions/image.png',

            // Movie
            'avi'  => URL_BIN_DIR .'80x80/extensions/film.png',
            'mpeg' => URL_BIN_DIR .'80x80/extensions/film.png',
            'mpg'  => URL_BIN_DIR .'80x80/extensions/film.png',

            // Archiv
            'tar' => URL_BIN_DIR .'80x80/extensions/archive.png',
            'rar' => URL_BIN_DIR .'80x80/extensions/archive.png',
            'zip' => URL_BIN_DIR .'80x80/extensions/archive.png',
            'gz'  => URL_BIN_DIR .'80x80/extensions/archive.png',
            '7z'  => URL_BIN_DIR .'80x80/extensions/archive.png',

            //Office

            // Music
            'mp3' => URL_BIN_DIR .'80x80/extensions/sound.png',
        );

        if ( isset( $extensions[ $size ][ $ext ] ) ) {
            return $extensions[ $size ][ $ext ];
        }

        return URL_BIN_DIR . $size .'/extensions/empty.png';
    }

    /**
     * Return the extension of a file
     *
     * @param String $filename - filename
     * @return String
     */
    static function getExtension($filename)
    {
        $explode = explode('.', $filename);
        $last    = array_pop( $explode );

        return $last;
    }

    /**
     * Return the media type by a file mime type
     *
     * @example \QUI\Projects\Media\Utils::getMediaTypeByMimeType( 'image/jpeg' )
     *
     * @param String $mime_type
     * @return String file|image
     */
    static function getMediaTypeByMimeType($mime_type)
    {
        if ( strpos( $mime_type, 'image/' ) !== false &&
             strpos( $mime_type, 'vnd.adobe' ) === false )
        {
            return 'image';
        }

        return 'file';
    }

    /**
     * Return the media item
     *
     * @param String $url - image.php? url
     * @return \QUI\Projects\Media\Item || \QUI\Exception
     */
    static function getImageByUrl($url)
    {
        if ( self::isMediaUrl( $url ) === false ) {
            throw new \QUI\Exception( 'Its not a QUIQQER image url', 400 );
        }

        // Parameter herrausfinden
        $params = \QUI\Utils\String::getUrlAttributes( $url );

        $Project = \QUI::getProject( $params['project'] );
        $Media   = $Project->getMedia();
        $Obj     = $Media->get( (int)$params['id'] ); /* @var $Obj MediaFile */

        return $Obj;
    }

    /**
     * Statische getHTML Methode
     * Gibt einen komplette HTML Tag <img /> von dem Bild aus
     *
     * @param String $src
     * @param String $attributes
     * @return String
     *
     * @todo do we really need this? think about it
     */
    static function getImageHTML($src, $attributes)
    {
        $size = array();
        $img  = '';

        if ( isset( $attributes['style'] ) )
        {
            $style = \QUI\Utils\String::splitStyleAttributes(
                $attributes['style']
            );

            if ( isset( $style['width'] ) ) {
                $size['width'] = (int)$style['width'];
            }

            if ( isset( $style['height'] ) ) {
                $size['height'] = (int)$style['height'];
            }

        } elseif ( isset( $attributes['width'] ) )
        {
            $size['width'] = (int)$attributes['width'];
        } elseif ( isset( $attributes['height'] ) )
        {
            $size['height'] = (int)$attributes['height'];
        }


        $src = self::getRewritedUrl( $src, $size );

        if ( $src )
        {
            $img = '<img src="'. $src .'" ';

            foreach ( $attributes as $key => $value ) {
                $img .= $key .'="'. $value .'" ';
            }

            $img .= ' />';
        }

        return $img;
    }

    /**
     * Return the rewrited url from a image.php? url
     *
     * @param unknown $output
     * @param unknown $size
     * @return String
     */
    static function getRewritedUrl($output, $size=array())
    {
        if ( self::isMediaUrl( $output ) === false ) {
            return $output;
        }

        // Parameter herrausfinden
        $params = \QUI\Utils\String::getUrlAttributes( $output );

        $id      = $params['id'];
        $project = $params['project'];

        $cache = 'cache/links/'. $project .'/media/'. $id;
        $url   = '';

        // exist cache?
        try
        {
            $url = \QUI\Cache\Manager::get( $cache );

        } catch ( \QUI\Cache\Exception $Exception )
        {

        }

        if ( empty( $url ) )
        {
            try
            {
                $Obj = self::getImageByUrl( $output );
                $url = $Obj->getUrl( true );

            } catch ( \QUI\Exception $Exception )
            {
                \QUI\System\Log::writeException( $Exception );

                return URL_DIR . $output;

            } catch ( \Exception $Exception )
            {
                \QUI\System\Log::writeException( $Exception );

                return URL_DIR . $output;
            }
        }


        // Falls Grösse mit eingebaut wurde diese mit einbauen
        if ( count( $size ) )
        {
            $url_explode = explode( '.', $url );

            if ( !isset( $size['height'] ) ) {
                $size['height'] = '';
            }

            if ( !isset( $size['width'] ) ) {
                $size['width'] = '';
            }

            if ( !isset( $url_explode[1] ) ) {
                $url_explode[1] = '';
            }

            $url = $url_explode[0] .'__'. $size['width'] .'x'. $size['height'] .'.'. $url_explode[1];
        }

        if ( !file_exists( CMS_DIR . $url ) )
        {
            $Project = \QUI::getProject( $project );
            $Media   = $Project->getMedia();
            $Obj     = $Media->get( (int)$id );

            if ( $Obj->getType() == 'IMAGE' )
            {
                if ( !isset( $size['width'] ) ) {
                    $size['width'] = false;
                }

                if ( !isset( $size['height'] ) ) {
                    $size['height'] = false;
                }

                $Obj->createSizeCache( $size['width'], $size['height'] );

            } else
            {
                $Obj->createCache();
            }
        }

        return $url;
    }

    /**
     * checks if the string can be used for a media folder name
     *
     * @param String $str - foldername
     * @throws \QUI\Exception
     */
    static function checkFolderName($str)
    {
        // Prüfung des Namens - Sonderzeichen
        if ( preg_match('/[^0-9_a-zA-Z -]/', $str) )
        {
            throw new \QUI\Exception(
                'Nicht erlaubte Zeichen wurden im Namen "'. $str .'" gefunden.
                Folgende Zeichen sind erlaubt: 0-9 a-z A-Z _ -',
                702
            );
        }

        if ( strpos($str, '__') !== false )
        {
            throw new \QUI\Exception(
                'Nicht erlaubte Zeichen wurden im Namen gefunden.
                Doppelte __ dürfen nicht verwendet werden.',
                702
            );
        }

        return true;
    }

    /**
     * Deletes characters which are not allowed for folders
     *
     * @param String $str - Folder name
     * @return String
     */
    static function stripFolderName($str)
    {
        $str = preg_replace('/[^0-9a-zA-Z\-]/', '_', $str);

        // Umlaute
        $str = str_replace(
            array('ä',  'ö',  'ü'),
            array('ar', 'oe', 'ue'),
            $str
        );

        // clean double _
        $str = preg_replace('/[_]{2,}/', "_", $str);

        return $str;
    }

     /**
     * checks if the string can be used for a media item
     *
     * @param String $filename - the complete filename: my_file.jpg
     * @throws \QUI\Exception
     */
    static function checkMediaName($filename)
    {
        // Prüfung des Namens - Sonderzeichen
        if ( preg_match('/[^0-9_a-zA-Z -.]/', $filename) )
        {
            throw new \QUI\Exception(
                'Nicht erlaubte Zeichen wurden im Namen "'. $filename .'" gefunden.
                Folgende Zeichen sind erlaubt: 0-9 a-z A-Z _ -',
                702
            );
        }

        // mehr als zwei punkte
         if ( substr_count($filename, '.') > 1 )
         {
             throw new \QUI\Exception(
                'Punkte dürfe nicht im Namen enthalten sein',
                702
            );
         }

        if ( strpos($filename, '__') !== false )
        {
            throw new \QUI\Exception(
                'Nicht erlaubte Zeichen wurden im Namen gefunden.
                Doppelte __ dürfen nicht verwendet werden.',
                702
            );
        }
    }

    /**
     * Deletes characters which are not allowed in the media center
     *
     * @param String $str
     * @return String
     */
    static function stripMediaName($str)
    {
        $str = preg_replace('/[^0-9a-zA-Z\.\-]/', '_', $str);

        // Umlaute
        $str = str_replace(
            array('ä',  'ö',  'ü'),
            array('ar', 'oe', 'ue'),
            $str
        );

        // delete the dots but not the last dot
        $str = str_replace('.', '_', $str);
        $str = \QUI\Utils\String::replaceLast('_', '.', $str);

        // FIX
        $str = preg_replace('/[_]{2,}/', "_", $str);

        return $str;
    }

    /**
     * is methods
     */

    /**
     * Is the variable a folder object?
     *
     * @param unknown_type $Unknown
     * @return Bool
     */
    static function isFolder($Unknown)
    {
        if ( !is_object( $Unknown ) ) {
            return false;
        }

        if ( !method_exists( $Unknown, 'getType' ) ) {
            return false;
        }

        if ( $Unknown->getType() === 'QUI\\Projects\\Media\\Folder' ) {
            return true;
        }

        return false;
    }

    /**
     * Is the variable a image object?
     *
     * @param unknown_type $Unknown
     * @return Bool
     */
    static function isImage($Unknown)
    {
        if ( !is_object( $Unknown ) ) {
            return false;
        }

        if ( !method_exists( $Unknown, 'getType' ) ) {
            return false;
        }

        if ( $Unknown->getType() === 'QUI\\Projects\\Media\\Image' ) {
            return true;
        }

        return false;
    }

    /**
     * Is the URL a media url?
     *
     * @param String $url
     * @return Bool
     */
    static function isMediaUrl($url)
    {
        if ( strpos( $url, 'image.php' ) !== false &&
             strpos( $url, 'project=' ) !== false &&
             strpos( $url, 'id=' ) !== false )
        {
            return true;
        }

        return false;
    }

    /**
     * Returns a media item by an url
     *
     * @param String $url
     * @return \QUI\Projects\Media\Item
     * @throws \QUI\Exception
     */
    static function getElement($url)
    {
        $parts = explode( 'media/cache/', $url );

        if ( !isset( $parts[1] ) ) {
            throw new \QUI\Exception( 'File not found', 404 );
        }

        $parts   = explode( '/', $parts[1] );
        $project = array_shift( $parts );

        $Project = \QUI::getProject( $project );
        $Media   = $Project->getMedia();

        // if the element (image) is resized resize
        $file_name = array_pop( $parts );

        if ( strpos($file_name, '__') !== false )
        {
            $lastpos_ul = strrpos( $file_name, '__' ) + 2;
            $pos_dot    = strpos( $file_name, '.', $lastpos_ul );

            $size      = substr( $file_name, $lastpos_ul, ( $pos_dot - $lastpos_ul ) );
            $part_size = explode( 'x', $size );

            $file_name = substr( $file_name, 0, ( $lastpos_ul - 2 ) ) .
                         substr( $file_name, $pos_dot );
        }

        $parts[] = $file_name;

        return $Media->getChildByPath( '/'. implode( '/', $parts ) );
    }

    /**
     * Check the upload params if a replacement can do
     *
     * @param \QUI\Projects\Media $Media
     * @param Integer $fileid 	  - The File which will be replaced
     * @param Array $uploadparams - Array with file information array('name' => '', 'type' => '')
     *
     * @throws \QUI\Exception
     */
    static function checkReplace(\QUI\Projects\Media $Media, $fileid, $uploadparams)
    {
        $fileid = (int)$fileid;

        $result = \QUI::getDataBase()->fetch(array(
            'from' 	=> $Media->getTable(),
            'where' => array(
                'id' => $fileid
            ),
            'limit' => 1
        ));

        if ( !isset( $result[0] ) ) {
            throw new \QUI\Exception( 'File entry not found', 404 );
        }

        $data = $result[0];

        // if the mimetype is the same, no check for renaming
        // so, the check is finish
        if ( $data['mime_type'] == $uploadparams['type'] ) {
            return;
        }

        $File = $Media->get( $fileid );

        if ( $File->getAttribute('name') == $uploadparams['name'] ) {
            return;
        }

        $Parent = $File->getParent();

        if ( $Parent->fileWithNameExists( $uploadparams['name'] ) )
        {
            throw new \QUI\Exception(
                'A file with the name '. $uploadparams['name'] .' already exist.',
                403
            );
        }
    }

    /**
     * Generate the MD5 hash of a file object
     *
     * @param \QUI\Projects\Media\File|\QUI\Projects\Media\Image $File
     */
    static function generateMD5($File)
    {
        /* @var $File \QUI\Projects\Media\Image */
        return md5_file( $File->getFullPath() );
    }

    /**
     * Generate the SHA1 hash of a file object
     *
     * @param \QUI\Projects\Media\File|\QUI\Projects\Media\Image $File
     */
    static function generateSHA1($File)
    {
        /* @var $File \QUI\Projects\Media\Image */
        return sha1_file( $File->getFullPath() );
    }
}
