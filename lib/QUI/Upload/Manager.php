<?php

/**
 * This file contains \QUI\Upload\Manager
 */

namespace QUI\Upload;

/**
 * Upload Manager
 * Manage Uploads from Users to the media
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.upload
 */

class Manager
{
    /**
     * DataBase table name
     * @var String
     */
    protected $_table = 'uploads';

    /**
     * constructor
     */
    public function __construct()
    {
        $this->_table = \QUI_DB_PRFX .'uploads';
    }

    /**
     * Return the main upload dir
     */
    public function getDir()
    {
        return VAR_DIR .'uploads/';
    }

    /**
     * Execute the setup and create the table for the upload manager
     */
    public function setup()
    {
        \QUI::getDataBase()->Table()->appendFields(
            $this->_table,
            array(
                'file'   => 'varchar(50)',
                'user'   => 'int(11)',
                'params' => 'text'
            )
        );

        \QUI::getDataBase()->Table()->setIndex(
            $this->_table,
            array('file', 'user')
        );
    }

    /**
     * Initialized the upload
     *
     * @throws \QUI\Exception
     */
    public function init()
    {
        // if a onstart function
        if ( isset( $_REQUEST['onstart'] ) && !empty($_REQUEST['onstart'] ) ) {
            $this->_callFunction( $_REQUEST['onstart'], $_REQUEST );
        }

        $this->upload();
    }

    /**
     * Flush a Message to the JavaScript UploadManager
     *
     * @param unknown_type $message
     */
    public function flushMessage($message)
    {
        $message = '<script type="text/javascript">
            var UploadManager = false;

            if ( typeof window.parent !== "undefined" &&
                 typeof window.parent.QUI !== "undefined" &&
                 typeof window.parent.QUI.UploadManager !== "undefined" )
            {
                UploadManager = window.parent.QUI.UploadManager;
            }

            if ( UploadManager ) {
                UploadManager.sendMessage('. json_encode($message) .');
            }
        </script>';

        echo $message;
        ob_flush();
        flush();
    }

    /**
     * Flush a javascript call to the UploadManager
     *
     * @param String $call - eq: alert(1);
     */
    public function flushAction($call)
    {
        $message = '<script type="text/javascript">
            var UploadManager = false;

            if ( typeof window.parent !== "undefined" &&
                 typeof window.parent.QUI !== "undefined" &&
                 typeof window.parent.QUI.UploadManager !== "undefined" )
            {
                UploadManager = window.parent.QUI.UploadManager;
            }

            if ( UploadManager ) {
                '. $call .'
            }
        </script>';

        echo $message;
        ob_flush();
        flush();
    }

    /**
     * Upload the file data,
     * read the PUT data and write it to the filesystem or read the $_FILES
     */
    public function upload()
    {
        \QUI\Utils\System\File::mkdir(
            $this->_getUserUploadDir()
        );

        $filename = false;
        $filesize = 0;
        $params   = array();
        $onfinish = false;

        if ( isset( $_REQUEST['filename'] ) ) {
            $filename = $_REQUEST['filename'];
        }

        if ( isset( $_REQUEST['filesize'] ) ) {
            $filesize = (int)$_REQUEST['filesize'];
        }

        if ( isset( $_REQUEST['fileparams'] ) ) {
            $params = json_decode($_REQUEST['fileparams'], true);
        }

        if ( isset( $_REQUEST['onfinish'] ) ) {
            $onfinish = $_REQUEST['onfinish'];
        }

        if ( isset( $_REQUEST['extract'] ) ) {
            $_REQUEST['extract'] = \QUI\Utils\Bool::JSBool( $_REQUEST['extract'] );
        }


        /**
         * no html5 upload
         */
        if ( !$filename )
        {
            try
            {
                $this->_formUpload( $onfinish, $params );

            } catch ( \QUI\Exception $Exception )
            {
                $this->flushMessage( $Exception->toArray() );
                return;
            }

            $uploadid = 0;

            if ( isset( $_REQUEST['uploadid'] ) ) {
                $uploadid = $_REQUEST['uploadid'];
            }

            $this->flushAction('UploadManager.isFinish("'. $uploadid .'")');
            return;
        }

        /**
         * html5 upload
         */
        if ( isset( $_REQUEST['file'] ) ) {
            $file = json_decode( $_REQUEST['file'], true );
        }

        if ( isset( $file['chunkstart'] ) && $file['chunkstart'] == 0 ) {
            $this->_delete( $filename );
        }

        // add the file to the database
        $this->_add( $filename, $params );


        $uploaddir = $this->_getUserUploadDir();
        $tmp_name  = $uploaddir . $filename;

        /* PUT REQUEST */
        $putdata = file_get_contents( 'php://input' );
        $Handle  = fopen( $tmp_name, 'a' );

        if ( $Handle ) {
            fwrite( $Handle, $putdata );
        }

        fclose( $Handle );

        // upload finish?
        $fileinfo = \QUI\Utils\System\File::getInfo($tmp_name, array(
            'filesize' => true
        ));

        // finish? then upload to folder
        if ( (int)$fileinfo['filesize'] == $filesize )
        {
            // extract if the the extract file is set
            if ( isset( $_REQUEST['extract'] ) && $_REQUEST['extract'] ) {
                $File = $this->_extract( $tmp_name );
            }

            $Data = $this->_getFileData( $filename );

            if ( !isset( $File ) )
            {
                $File = $Data;

                $File->setAttribute(
                    'filepath',
                    $uploaddir . $File->getAttribute('file')
                );
            }

            $File->setAttribute( 'upload-dir', $uploaddir );
            $File->setAttribute( 'params', $Data->getAttribute('params') );

            $result = $this->_callFunction($onfinish, array(
                'File' => $File
            ));


            // delete the file from the database
            $this->_delete( $filename );

            // delete the real file
            \QUI\Utils\System\File::unlink( $tmp_name );

            echo $result;
        }
    }

    /**
     * call a function
     *
     * @param {String|Function} $function - Function
     * @param {Array} $params			  - function parameter
     * @throws \QUI\Exception
     */
    protected function _callFunction($function, $params=array())
    {
        if ( is_object( $function ) && get_class( $function ) === 'Closure' ) {
            return $function();
        }

        if ( function_exists( $function ) ) {
            return call_user_func_array( $function, $params );
        }

        if ( strpos( $function, 'ajax_' ) === 0 )
        {
            // if the function is a ajax_function
            $_rf_file = CMS_DIR .'admin/'. str_replace( '_', '/', $function ) .'.php';

            if ( file_exists( $_rf_file ) ) {
                require_once $_rf_file;
            }

            if ( !function_exists( $function ) )
            {
                throw new \QUI\Exception(
                    'Function '. $function .' not found',
                    404
                );
            }

            $_REQUEST = array_merge( $_REQUEST, $params, array(
                '_rf' => '["'. $function .'"]'
            ));

            return \QUI::$Ajax->call();
        }

        if ( strpos( $function, 'package_' ) === 0 )
        {
            $dir  = CMS_DIR .'packages/';
            $file = substr( str_replace('_', '/', $function), 8 ) .'.php';

            $_rf_file = $dir . $file;

            if ( file_exists( $_rf_file ) ) {
                require_once $_rf_file;
            }

            $_REQUEST = array_merge( $_REQUEST, $params, array(
                '_rf' => '["'. $function .'"]'
            ));

            return \QUI::$Ajax->call();
        }

        throw new \QUI\Exception(
            'Function '. $function .' not found',
            404
        );
    }

    /**
     * Internal form upload method
     * If the upload is not over HTML5
     *
     * @param {String|Function} $onfinish - Function
     * @param $params - extra params for the \QUI\QDOM File Object
     *
     * @throws \QUI\Exception
     */
    protected function _formUpload($onfinish, $params)
    {
        if ( empty( $_FILES ) || !isset( $_FILES['files'] ) )
        {
            throw new \QUI\Exception(
                'No files where send', 400
            );
        }

        $list  = $_FILES['files'];
        $files = array();

        if ( !is_array( $list['error'] ) )
        {
            $this->_checkUpload( $list['error'] );

            $uploaddir = $this->_getUserUploadDir();
            $filename  = $list['name'];
            $file      = $uploaddir . $filename;

            if ( !move_uploaded_file( $list["tmp_name"], $file ) ) {
                throw new \QUI\Exception( 'Could not move file'. $filename );
            }

            // extract if the the extract file is set
            if ( isset( $_REQUEST['extract'] ) && $_REQUEST['extract'] ) {
                $File = $this->_extract( $file );
            }

            if ( !isset( $File ) )
            {
                $File = new \QUI\QDOM();
                $File->setAttribute( 'name', $filename );
                $File->setAttribute( 'filepath', $file );
            }

            $File->setAttribute( 'params', $params );
            $File->setAttribute( 'upload-dir', $uploaddir );

            $this->_callFunction($onfinish, array(
                'File' => $File
            ));

            // delete the real file
            \QUI\Utils\System\File::unlink( $file );

            return;
        }

        foreach ( $list['error'] as $key => $error )
        {
            $this->_checkUpload( $error );

            $uploaddir = $this->_getUserUploadDir();
            $filename  = $list['name'][ $key ];
            $file      = $uploaddir . $filename;

            if ( !move_uploaded_file( $list["tmp_name"], $file ) ) {
                throw new \QUI\Exception( 'Could not move file'. $filename );
            }

            // extract if the the extract file is set
            if ( isset( $_REQUEST['extract'] ) && $_REQUEST['extract'] ) {
                $File = $this->_extract( $file );
            }

            if ( !isset( $File ) )
            {
                $File = new \QUI\QDOM();
                $File->setAttribute( 'name', $filename );
                $File->setAttribute( 'filepath', $file );
            }

            $File->setAttribute( 'params', $params );
            $File->setAttribute( 'upload-dir', $uploaddir );

            $this->_callFunction($onfinish, array(
                'File' => $File
            ));

            // delete the real file
            \QUI\Utils\System\File::unlink( $file );
        }
    }

    /**
     * Check if some errors occured on the upload entry
     *
     * @param unknown_type $error
     * @return Bool
     * @throws \QUI\Exception
     */
    protected function _checkUpload($error)
    {
        switch ( $error )
        {
            // There is no error, the file upload was successfull
            case UPLOAD_ERR_OK:
                return true;
            break;

            case UPLOAD_ERR_INI_SIZE:
                throw new \QUI\Exception(
                    'The uploaded file exceeds the upload_max_filesize'
                );
            break;

            case UPLOAD_ERR_FORM_SIZE:
                throw new \QUI\Exception(
                    'The uploaded file exceeds the MAX_FILE_SIZE'
                );
            break;

            case UPLOAD_ERR_PARTIAL:
                throw new \QUI\Exception(
                    'The uploaded file was only partially uploaded.'
                );
            break;

            case UPLOAD_ERR_NO_FILE:
                throw new \QUI\Exception(
                    'No file was uploaded'
                );
            break;

            case UPLOAD_ERR_NO_TMP_DIR:
                throw new \QUI\Exception(
                    'Missing a temporary folder'
                );
            break;
        }

        return true;
    }

    /**
     * Extract the Archiv
     *
     * @param String $filename
     * @throws \QUI\Exception
     * @return \QUI\QDOM
     *
     * @todo more archive types
     */
    protected function _extract($filename)
    {
        $fileinfo = \QUI\Utils\System\File::getInfo( $filename );

        if ( $fileinfo['mime_type'] != 'application/zip' )
        {
            throw new \QUI\Exception(
                'No supported archive was uploaded. Could not extract the File'
            );
        }

        $to = $this->_getUserUploadDir() . $fileinfo['filename'];

        \QUI\Utils\System\File::unlink( $to );
        \QUI\Utils\System\File::mkdir( $to );

        \QUI\Archiver\Zip::unzip( $filename, $to );

        $File = new \QUI\QDOM();
        $File->setAttribute( 'name', $fileinfo['filename'] );
        $File->setAttribute( 'filepath', $to );

        return $File;
    }

    /**
     * Return the Path to the User upload directory
     *
     * @param \QUI\Users\User $User - optional, standard is the session user
     * @return String
     */
    protected function _getUserUploadDir($User=false)
    {
        if ( !\QUI::getUsers()->isUser( $User ) ) {
            $User = \QUI::getUserBySession();
        }

        return $this->getDir() . $User->getId() .'/';
    }

    /**
     * Cancel the upload
     *
     * @param String $filename - the filename of the file
     */
    public function cancel($filename)
    {
        $this->_delete( $filename );
    }

    /**
     * Add a file to the Upload Manager
     *
     * @param unknown_type $filename - filename
     * @param unknown_type $params   - optional
     */
    protected function _add($filename, $params)
    {
        $file = $this->_getUserUploadDir() . $filename;

        if ( file_exists($file) ) {
            return;
        }


        \QUI::getDataBase()->insert($this->_table, array(
            'file'   => $filename,
            'user'   => \QUI::getUserBySession()->getId(),
            'params' => json_encode( $params )
        ));
    }

    /**
     * Delete the file entry and the uploaded temp file
     *
     * @param String $filename
     * @throws \QUI\Exception
     */
    protected function _delete($filename)
    {
        \QUI::getDataBase()->exec(array(
            'delete' => true,
            'from'   => $this->_table,
            'where'  => array(
                'user' => \QUI::getUserBySession()->getId(),
                'file' => $filename
            )
        ));

        \QUI\Utils\System\File::unlink(
            $this->_getUserUploadDir() . $filename
        );
    }

    /**
     * Return a \QUI\QDOM Object of the file entry
     *
     * @param String $filename
     *
     * @return \QUI\QDOM
     * @throws \QUI\Exception
     */
    protected function _getFileData($filename)
    {
        $db_result = \QUI::getDataBase()->fetch(array(
            'from'   => $this->_table,
            'where'  => array(
                'user' => \QUI::getUserBySession()->getId(),
                'file' => $filename
            )
        ));

        if ( !isset($db_result[0]) ) {
            throw new \QUI\Exception('File not found', 404);
        }

        $File = new \QUI\QDOM();
        $File->setAttributes( $db_result[0] );

        if ( $File->getAttribute('params') )
        {
            $File->setAttribute(
                'params',
                json_decode( $File->getAttribute('params'), true )
            );
        }

        return $File;
    }

    /**
     * Get unfinish uploads from a specific user
     * so you can resume the upload
     *
     * @param \QUI\Users\User $User - optional, if false = the session user
     * @return array
     */
    public function getUnfinishedUploadsFromUser($User=false)
    {
        if ( !\QUI::getUsers()->isUser($User) ) {
            $User = \QUI::getUserBySession();
        }

        // read user upload dir
        $dir = $this->getDir() . $User->getId() .'/';

        if ( !file_exists( $dir ) || !is_dir( $dir ) ) {
            return array();
        }

        $files  = \QUI\Utils\System\File::readDir( $dir );
        $result = array();

        foreach ( $files as $file )
        {
            try
            {
                $File       = $this->_getFileData( $file, $User );
                $attributes = $File->getAttributes();

                if ( isset($attributes['params']) && is_string($attributes['params']) )
                {
                    $params    = json_decode( $attributes['params'], true );
                    $file_info = \QUI\Utils\System\File::getInfo( $dir . $file );

                    $params['file']['uploaded'] = $file_info['filesize'];

                    $attributes['params'] = $params;
                }

                $result[] = $attributes;

            } catch ( \QUI\Exception $e )
            {
                if ( $e->getCode() === 404 ) {
                    \QUI\Utils\System\File::unlink( $dir . $file );
                }
            }
        }

        return $result;
    }
}
