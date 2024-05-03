<?php

/**
 * This file contains \QUI\Upload\Manager
 */

namespace QUI\Upload;

use QUI;
use QUI\Exception;
use QUI\Permissions\Permission;
use QUI\QDOM;
use QUI\Utils\Security\Orthos;
use QUI\Utils\System\File;
use QUI\Utils\System\File as QUIFile;

use function array_merge;
use function class_exists;
use function count;
use function explode;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function flush;
use function fnmatch;
use function fopen;
use function fwrite;
use function implode;
use function is_array;
use function is_callable;
use function is_dir;
use function is_object;
use function json_decode;
use function json_encode;
use function move_uploaded_file;
use function ob_flush;
use function realpath;
use function str_replace;
use function substr;
use function trim;

/**
 * Upload Manager
 * Manage Uploads from Users to the media
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Manager
{
    /**
     * Initialized the upload
     *
     * @return bool|string
     * @throws Exception
     */
    public function init(): bool|string
    {
        if (!empty($_REQUEST['onstart']) && is_callable($_REQUEST['onstart'])) {
            $this->callFunction($_REQUEST['onstart'], $_REQUEST);
        }

        return $this->upload();
    }

    /**
     * call a function
     *
     * @param callback|string $function - Function
     * @param array $params - function parameter
     *
     * @return mixed
     * @throws Exception
     */
    protected function callFunction(callable|string $function, array $params = []): mixed
    {
        if (is_object($function) && ($function)::class === 'Closure') {
            return $function();
        }

        if (str_starts_with($function, 'ajax_')) {
            // if the function is an ajax_function
            $_rf_file = OPT_DIR . 'quiqqer/quiqqer/admin/' . str_replace('_', '/', $function) . '.php';
            $_rf_file = Orthos::clearPath(realpath($_rf_file));

            if (file_exists($_rf_file)) {
                require_once $_rf_file;
            }

            $_REQUEST = array_merge($_REQUEST, $params, [
                '_rf' => '["' . $function . '"]'
            ]);

            return QUI::getAjax()->callRequestFunction($function, $_REQUEST);
        }

        if (str_starts_with($function, 'package_')) {
            $dir = OPT_DIR;
            $file = substr(str_replace('_', '/', $function), 8) . '.php';

            $_rf_file = $dir . $file;
            $_rf_file = Orthos::clearPath(realpath($_rf_file));

            if (file_exists($_rf_file)) {
                require_once $_rf_file;
            }

            $_REQUEST = array_merge($_REQUEST, $params, [
                '_rf' => '["' . $function . '"]'
            ]);

            return QUI::getAjax()->callRequestFunction($function, $_REQUEST);
        }

        throw new Exception(
            'Function ' . $function . ' not found',
            404
        );
    }

    /**
     * Upload the file data,
     * read the PUT data and write it to the filesystem or read the $_FILES
     *
     * @return bool|string
     * @throws Exception
     * @throws QUI\Permissions\Exception
     */
    public function upload(): bool|string
    {
        QUIFile::mkdir($this->getUserUploadDir());

        $filename = false;
        $fileSize = 0;
        $fileType = false;

        $params = [];
        $onfinish = false;

        if (isset($_REQUEST['filetype'])) {
            $fileType = $_REQUEST['filetype'];
        }

        if (isset($_REQUEST['filename'])) {
            $filename = $_REQUEST['filename'];
        }

        if (isset($_REQUEST['filesize'])) {
            $fileSize = (int)$_REQUEST['filesize'];
        }

        if (isset($_REQUEST['fileparams'])) {
            $params = json_decode($_REQUEST['fileparams'], true);
        }

        if (isset($_REQUEST['onfinish'])) {
            $onfinish = $_REQUEST['onfinish'];
        }

        if (isset($_REQUEST['extract'])) {
            $_REQUEST['extract'] = QUI\Utils\BoolHelper::JSBool($_REQUEST['extract']);
        }

        $UploadForm = null;

        if (isset($_REQUEST['callable']) && class_exists($_REQUEST['callable'])) {
            $Instance = new $_REQUEST['callable']();

            if ($Instance instanceof Form) {
                $UploadForm = $Instance;
            }
        }

        // check file count
        $configMaxFileCount = Permission::getPermission('quiqqer.upload.maxUploadCount');

        if ($configMaxFileCount) {
            $userDir = $this->getUserUploadDir();
            $files = File::readDir($userDir);
            $count = count($files) / 2;

            if ($count + 1 >= $configMaxFileCount) {
                throw new QUI\Permissions\Exception([
                    'quiqqer/quiqqer',
                    'exception.upload.count.limit'
                ]);
            }
        }

        // check mime type and file endings
        $configAllowedTypes = Permission::getPermission(
            'quiqqer.upload.allowedTypes'
        );

        $configAllowedEndings = Permission::getPermission(
            'quiqqer.upload.allowedEndings'
        );

        if ($UploadForm) {
            $configAllowedTypes = $UploadForm->getAttribute('allowedFileTypes');
            $configAllowedEndings = $UploadForm->getAttribute('allowedFileEnding');

            if (is_array($configAllowedTypes)) {
                $configAllowedTypes = implode(',', $configAllowedTypes);
            }

            if (is_array($configAllowedEndings)) {
                $configAllowedEndings = implode(',', $configAllowedEndings);
            }
        }


        if ($this->checkFnMatch($configAllowedTypes, $fileType) === false) {
            throw new Exception([
                'quiqqer/quiqqer',
                'exception.upload.not.allowed.mimetype'
            ]);
        }

        if ($this->checkFnMatch($configAllowedEndings, $filename) === false) {
            throw new Exception([
                'quiqqer/quiqqer',
                'exception.upload.not.allowed.ending'
            ]);
        }

        /**
         * no html5 upload
         */
        if (!$filename) {
            try {
                $this->formUpload($onfinish, $params);
            } catch (Exception $Exception) {
                $this->flushMessage($Exception->toArray());

                return '';
            }

            $uploadId = 0;

            if (isset($_REQUEST['uploadid'])) {
                $uploadId = $_REQUEST['uploadid'];
            }

            $this->flushAction('UploadManager.isFinish("' . $uploadId . '")');

            return '';
        }

        // cleanup file name
        $filename = trim($filename);
        $filename = trim($filename, '.');

        /**
         * html5 upload
         */
        if (isset($_REQUEST['file'])) {
            $file = json_decode($_REQUEST['file'], true);
        }

        if (isset($file['chunkstart']) && $file['chunkstart'] == 0) {
            $this->delete($filename);
        }

        // add the file to the database
        $this->add($filename, $params);


        $uploaddir = $this->getUserUploadDir();
        $tmp_name = $uploaddir . $filename;

        /* PUT REQUEST */
        $putdata = file_get_contents('php://input');
        $Handle = fopen($tmp_name, 'a');

        if ($Handle) {
            fwrite($Handle, $putdata);
        }

        fclose($Handle);

        // upload finish?
        $fileinfo = QUIFile::getInfo($tmp_name, [
            'filesize' => true
        ]);

        $User = QUI::getUserBySession();
        $configMaxFileSize = $User->getPermission('quiqqer.upload.maxFileUploadSize', 'maxInteger');

        if ((int)QUI\Projects\Manager::get()->getConfig('media_maxUploadFileSize')) {
            $configMaxFileSize = (int)QUI\Projects\Manager::get()->getConfig('media_maxUploadFileSize');
        }


        if ($configMaxFileSize && (int)$fileinfo['filesize'] > $configMaxFileSize) {
            QUIFile::unlink($tmp_name);

            throw new Exception([
                'quiqqer/quiqqer',
                'exception.media.upload.fileSize.is.to.big',
                [
                    'size' => QUI\Utils\System\File::formatSize($configMaxFileSize),
                    'file' => $filename
                ]
            ]);
        }

        // finish? then upload to folder
        if ((int)$fileinfo['filesize'] == $fileSize) {
            // extract if the extract file is set
            if (isset($_REQUEST['extract']) && $_REQUEST['extract']) {
                $File = $this->extract($tmp_name);
            }

            $Data = $this->getFileData($filename);

            if (!isset($File)) {
                $File = $Data;

                $File->setAttribute(
                    'filepath',
                    $uploaddir . $File->getAttribute('file')
                );
            }

            $File->setAttribute('upload-dir', $uploaddir);
            $File->setAttribute('params', $Data->getAttribute('params'));

            $result = [];

            if (!empty($onfinish)) {
                $result = $this->callFunction($onfinish, [
                    'File' => $File
                ]);
            }

            // delete the file from the database
            $this->delete($filename);

            // delete the real file
            QUIFile::unlink($tmp_name);

            if (isset($result['Exception'])) {
                throw new Exception(
                    $result['Exception']['message'],
                    $result['Exception']['code']
                );
            }

            return $result['result'] ?? true;
        }

        return '';
    }

    /**
     * Return the Path to the User upload directory
     *
     * @param QUI\Interfaces\Users\User|null $User |boolean $User - optional, standard is the session user
     * @return string
     *
     * @throws QUI\Permissions\Exception
     */
    protected function getUserUploadDir(QUI\Interfaces\Users\User $User = null): string
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUserBySession();
        }

        $this->checkUserPermissions($User);

        // for nobody, we use the session id
        if ($User instanceof QUI\Users\Nobody) {
            $Session = QUI::getSession();
            $uuid = $Session->get('uuid');

            if (!$uuid) {
                $uuid = QUI\Utils\Uuid::get();
                $Session->set('uuid', $uuid);
            }

            $id = $uuid;
        } else {
            $id = $User->getUUID();
        }

        return $this->getDir() . $id . '/';
    }

    /**
     * @param QUI\Interfaces\Users\User|null $User
     * @throws QUI\Permissions\Exception
     */
    protected function checkUserPermissions(QUI\Interfaces\Users\User $User = null): void
    {
        $SessionUser = QUI::getUserBySession();

        if ($SessionUser->isSU()) {
            return;
        }

        if (QUI::getUsers()->isSystemUser($User)) {
            return;
        }

        if (!$User) {
            $User = QUI::getUserBySession();
        }

        if ($SessionUser->getUUID() !== $User->getUUID()) {
            throw new QUI\Permissions\Exception([
                'quiqqer/quiqqer',
                'exceptions.upload.no.permissions.'
            ]);
        }
    }

    /**
     * Return the main upload dir
     */
    public function getDir(): string
    {
        return VAR_DIR . 'uploads/';
    }

    /**
     * @param string $values
     * @param string $str
     *
     * @return bool
     */
    protected function checkFnMatch(string $values, string $str): bool
    {
        if (empty($values)) {
            return true;
        }

        $values = explode(',', $values);

        foreach ($values as $type) {
            if (fnmatch($type, $str)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Internal form upload method
     * If the upload is not over HTML5
     *
     * @param callback|string $onfinish - Function
     * @param mixed $params - extra params for the \QUI\QDOM File Object
     *
     * @throws Exception
     */
    protected function formUpload(callable|string $onfinish, mixed $params): void
    {
        if (empty($_FILES) || !isset($_FILES['files'])) {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.upload.no.data'),
                400
            );
        }

        $list = $_FILES['files'];

        if (!is_array($list['error'])) {
            $this->checkUpload($list['error']);

            $uploadDir = $this->getUserUploadDir();
            $filename = $list['name'];

            // cleanup file name
            $filename = trim($filename);
            $filename = trim($filename, '.');

            $file = $uploadDir . $filename;

            if (!move_uploaded_file($list["tmp_name"], $file)) {
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.move', [
                        'file' => $file
                    ])
                );
            }

            // extract if the  extract file is set
            if (isset($_REQUEST['extract']) && $_REQUEST['extract']) {
                $File = $this->extract($file);
            }

            if (!isset($File)) {
                $File = new QDOM();
                $File->setAttribute('name', $filename);
                $File->setAttribute('filepath', $file);
            }

            $File->setAttribute('params', $params);
            $File->setAttribute('upload-dir', $uploadDir);

            $this->callFunction($onfinish, [
                'File' => $File
            ]);

            // delete the real file
            QUIFile::unlink($file);

            return;
        }

        foreach ($list['error'] as $key => $error) {
            $this->checkUpload($error);

            $uploadDir = $this->getUserUploadDir();
            $filename = $list['name'][$key];
            $file = $uploadDir . $filename;

            if (!move_uploaded_file($list["tmp_name"], $file)) {
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.move', [
                    'file' => $filename
                ]);
            }

            if (isset($_REQUEST['extract']) && $_REQUEST['extract']) {
                $File = $this->extract($file);
            }

            if (!isset($File)) {
                $File = new QDOM();
                $File->setAttribute('name', $filename);
                $File->setAttribute('filepath', $file);
            }

            $File->setAttribute('params', $params);
            $File->setAttribute('upload-dir', $uploadDir);

            $this->callFunction($onfinish, [
                'File' => $File
            ]);

            // delete the real file
            QUIFile::unlink($file);
        }
    }

    /**
     * Check if some errors occurred on the upload entry
     *
     * @param integer $error
     *
     * @return bool
     * @throws Exception
     */
    protected function checkUpload(int $error): bool
    {
        switch ($error) {
            // There is no error, the file upload was successful
            case UPLOAD_ERR_OK:
                return true;

            case UPLOAD_ERR_INI_SIZE:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.upload.max.filesize')
                );

            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.upload.max.form.filesize')
                );

            case UPLOAD_ERR_PARTIAL:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.upload.partially.uploaded')
                );

            case UPLOAD_ERR_NO_FILE:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.upload.no.data')
                );

            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.upload.missing.temp')
                );
        }

        return true;
    }

    /**
     * Extract the Archive
     *
     * @param string $filename
     *
     * @return QDOM
     *
     * @throws Exception
     * @todo more archive types
     */
    protected function extract(string $filename): QDOM
    {
        $fileInfo = QUIFile::getInfo($filename);

        if ($fileInfo['mime_type'] != 'application/zip') {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.upload.unsupported.archive')
            );
        }

        $to = $this->getUserUploadDir() . $fileInfo['filename'];

        QUIFile::unlink($to);
        QUIFile::mkdir($to);

        QUI\Archiver\Zip::unzip($filename, $to);

        $File = new QDOM();
        $File->setAttribute('name', $fileInfo['filename']);
        $File->setAttribute('filepath', $to);

        return $File;
    }

    /**
     * Flush a Message to the JavaScript UploadManager
     *
     * @param array|string $message
     */
    public function flushMessage(array|string $message): void
    {
        $message = '<script type="text/javascript">
            let UploadManager = false;

            if (typeof window.parent !== "undefined" &&
                typeof window.parent.QUI !== "undefined" &&
                typeof window.parent.QUI.UploadManager !== "undefined")
            {
                UploadManager = window.parent.QUI.UploadManager;
            }

            if (UploadManager) {
                UploadManager.sendMessage(' . json_encode($message) . ');
            }
        </script>';

        echo $message;
        ob_flush();
        flush();
    }

    /**
     * Flush a javascript call to the UploadManager
     *
     * @param string $call - eq: alert(1);
     */
    public function flushAction(string $call): void
    {
        $message = '<script type="text/javascript">
            let UploadManager = false;

            if (typeof window.parent !== "undefined" &&
                typeof window.parent.QUI !== "undefined" &&
                typeof window.parent.QUI.UploadManager !== "undefined")
            {
                UploadManager = window.parent.QUI.UploadManager;
            }

            if (UploadManager) {
                ' . $call . '
            }
        </script>';

        echo $message;
        ob_flush();
        flush();
    }

    /**
     * Delete the file entry and the uploaded temp file
     *
     * @param string $filename
     *
     * @throws Exception
     */
    protected function delete(string $filename): void
    {
        $file = $this->getUserUploadDir() . $filename;
        $conf = $this->getUserUploadDir() . $filename . '.json';

        QUIFile::unlink($file);
        QUIFile::unlink($conf);
    }

    /**
     * Add a file to the Upload Manager
     *
     * @param string $filename - filename
     * @param array $params - optional
     *
     * @throws Exception
     */
    protected function add(string $filename, array $params): void
    {
        $conf = $this->getUserUploadDir() . $filename . '.json';

        if (file_exists($conf)) {
            return;
        }

        file_put_contents(
            $conf,
            json_encode([
                'file' => $filename,
                'user' => QUI::getUserBySession()->getUUID(),
                'params' => $params
            ])
        );
    }

    /**
     * Return a \QUI\QDOM Object of the file entry
     *
     * @param string $filename
     *
     * @return QDOM
     * @throws Exception
     */
    protected function getFileData(string $filename): QDOM
    {
        $conf = $this->getUserUploadDir() . $filename . '.json';

        if (!file_exists($conf)) {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.file.not.found'),
                404
            );
        }

        $data = json_decode(
            file_get_contents($conf),
            true
        );

        $File = new QDOM();
        $File->setAttributes($data);

        return $File;
    }

    /**
     * Flush an exception to the UploadManager
     *
     * @param Exception $Exception
     */
    public function flushException(Exception $Exception): void
    {
        $message = [
            'Exception' => $Exception->toArray()
        ];

        echo '<quiqqer>' . json_encode($message) . '</quiqqer>';
        ob_flush();
        flush();
    }

    /**
     * Cancel the upload
     *
     * @param string $filename - the filename of the file
     *
     * @throws Exception
     */
    public function cancel(string $filename): void
    {
        $this->delete($filename);
    }

    /**
     * Get unfinished uploads from a specific user
     * so, you can resume the upload
     *
     * @param QUI\Interfaces\Users\User|null $User - optional, if null = the session user
     * @return array
     *
     * @throws Exception
     * @throws QUI\Permissions\Exception
     */
    public function getUnfinishedUploadsFromUser(QUI\Interfaces\Users\User $User = null): array
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUserBySession();
        }

        $this->checkUserPermissions($User);


        // read user upload dir
        $dir = $this->getUserUploadDir($User);

        if (!file_exists($dir) || !is_dir($dir)) {
            return [];
        }

        $files = QUIFile::readDir($dir);
        $result = [];

        foreach ($files as $file) {
            try {
                $File = $this->getFileData($file);
                $attributes = $File->getAttributes();

                if (isset($attributes['params'])) {
                    $params = $attributes['params'];
                    $file_info = QUIFile::getInfo($dir . $file);

                    $params['file']['uploaded'] = $file_info['filesize'];

                    $attributes['params'] = $params;
                }

                $result[] = $attributes;
            } catch (Exception $Exception) {
                if ($Exception->getCode() === 404) {
                    QUIFile::unlink($dir . $file);
                }
            }
        }

        return $result;
    }
}
