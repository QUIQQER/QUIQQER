<?php

/**
 * This file contains \QUI\Upload\Manager
 */

namespace QUI\Upload;

use QUI;
use QUI\Exception;
use QUI\Permissions\Permission;
use QUI\QDOM;
use QUI\Utils\System\File;
use QUI\Utils\System\File as QUIFile;

use function array_merge;
use function array_key_exists;
use function count;
use function dirname;
use function explode;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function flush;
use function fnmatch;
use function fstat;
use function fopen;
use function fwrite;
use function implode;
use function is_a;
use function is_array;
use function is_callable;
use function is_dir;
use function is_file;
use function is_link;
use function is_object;
use function is_scalar;
use function is_string;
use function json_decode;
use function json_encode;
use function lstat;
use function move_uploaded_file;
use function ob_flush;
use function preg_match;
use function realpath;
use function rtrim;
use function str_replace;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

use const DIRECTORY_SEPARATOR;

/**
 * Upload Manager
 * Manage Uploads from Users to the media
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Manager
{
    private const MAX_FILESYSTEM_NAME_BYTES = 255;

    /**
     * Initialized the upload
     *
     * @return bool|string|array<array-key, mixed>
     *
     * @throws Exception
     */
    public function init(): bool | string | array
    {
        if (!empty($_REQUEST['onstart']) && is_callable($_REQUEST['onstart'])) {
            $this->callFunction($_REQUEST['onstart'], $_REQUEST);
        }

        return $this->upload();
    }

    /**
     * call a function
     *
     * @param callable|string $function - Function
     * @param array<string, mixed> $params - function parameter
     *
     * @return mixed
     * @throws Exception
     */
    protected function callFunction(callable | string $function, array $params = []): mixed
    {
        if ($function instanceof \Closure) {
            return $function();
        }

        if (!is_string($function)) {
            throw new Exception(
                'Unsupported upload callback type: ' . get_debug_type($function),
                400
            );
        }

        $callbackFile = $this->getCallbackFile($function);
        require_once $callbackFile;

        $_REQUEST = array_merge($_REQUEST, $params, [
            '_rf' => '["' . $function . '"]'
        ]);

        return QUI::getAjax()->callRequestFunction($function, $_REQUEST);
    }

    /**
     * Resolve an upload callback only inside a registered Core or package AJAX directory.
     *
     * @throws Exception
     */
    protected function getCallbackFile(string $function): string
    {
        if (str_starts_with($function, 'ajax_')) {
            return $this->resolveCallbackFile(
                OPT_DIR . 'quiqqer/core/admin/ajax',
                substr($function, strlen('ajax_')),
                $function
            );
        }

        if (str_starts_with($function, 'package_')) {
            $package = $this->getCallbackPackage($function);
            $prefix = 'package_' . str_replace('/', '_', $package) . '_ajax_';

            return $this->resolveCallbackFile(
                OPT_DIR . $package . '/ajax',
                substr($function, strlen($prefix)),
                $function
            );
        }

        throw new Exception('Function ' . $function . ' not found', 404);
    }

    /**
     * @throws Exception
     */
    private function resolveCallbackFile(string $root, string $handler, string $function): string
    {
        if (!preg_match('/\A[A-Za-z0-9-]+(?:_[A-Za-z0-9-]+)*\z/D', $handler)) {
            throw new Exception('Function ' . $function . ' not found', 404);
        }

        $canonicalRoot = realpath($root);

        if ($canonicalRoot === false || !is_dir($canonicalRoot)) {
            throw new Exception('Function ' . $function . ' not found', 404);
        }

        $callbackFile = $canonicalRoot . DIRECTORY_SEPARATOR
            . str_replace('_', DIRECTORY_SEPARATOR, $handler) . '.php';
        $canonicalCallbackFile = realpath($callbackFile);
        $rootPrefix = rtrim($canonicalRoot, '/\\') . DIRECTORY_SEPARATOR;

        if (
            $canonicalCallbackFile === false
            || !is_file($canonicalCallbackFile)
            || !str_starts_with($canonicalCallbackFile, $rootPrefix)
        ) {
            throw new Exception('Function ' . $function . ' not found', 404);
        }

        return $canonicalCallbackFile;
    }

    /**
     * Determine the package belonging to a package callback. Older upload clients
     * do not always send the package parameter, so installed packages are used as
     * the server-side registry for that compatibility path.
     *
     * @throws Exception
     */
    private function getCallbackPackage(string $function): string
    {
        if (array_key_exists('package', $_REQUEST)) {
            if (!is_string($_REQUEST['package'])) {
                throw new Exception('Function ' . $function . ' not found', 404);
            }

            $package = $_REQUEST['package'];
            $this->validateCallbackPackage($package, $function);

            return $package;
        }

        $matchedPackage = '';
        $matchedPrefixLength = 0;

        foreach (QUI::getPackageManager()->getInstalled() as $installedPackage) {
            $package = $installedPackage['name'] ?? null;

            if (!is_string($package)) {
                continue;
            }

            $prefix = 'package_' . str_replace('/', '_', $package) . '_ajax_';

            if (!str_starts_with($function, $prefix) || strlen($prefix) <= $matchedPrefixLength) {
                continue;
            }

            $matchedPackage = $package;
            $matchedPrefixLength = strlen($prefix);
        }

        if ($matchedPackage === '') {
            throw new Exception('Function ' . $function . ' not found', 404);
        }

        $this->validateCallbackPackage($matchedPackage, $function);

        return $matchedPackage;
    }

    /**
     * @throws Exception
     */
    private function validateCallbackPackage(string $package, string $function): void
    {
        $parts = explode('/', $package);
        $isValidPackage = count($parts) === 2;

        foreach ($parts as $part) {
            if (
                $part === ''
                || $part === '.'
                || $part === '..'
                || !preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\z/D', $part)
            ) {
                $isValidPackage = false;
                break;
            }
        }

        $prefix = 'package_' . str_replace('/', '_', $package) . '_ajax_';

        if (!$isValidPackage || !str_starts_with($function, $prefix)) {
            throw new Exception('Function ' . $function . ' not found', 404);
        }
    }

    /**
     * Upload the file data,
     * read the PUT data and write it to the filesystem or read the $_FILES
     *
     * @return bool|string|array<array-key, mixed>
     *
     * @throws Exception
     * @throws QUI\Permissions\Exception
     */
    public function upload(): bool | string | array
    {
        QUIFile::mkdir($this->getUserUploadDir());
        $canonicalUploadDir = $this->getCanonicalUserUploadDir();

        $filename = null;
        $fileSize = 0;
        $fileType = false;

        $params = [];
        $onfinish = false;

        if (isset($_REQUEST['filetype'])) {
            $fileType = $_REQUEST['filetype'];
        }

        if (array_key_exists('filename', $_REQUEST)) {
            $filename = $this->validateUploadFilename($_REQUEST['filename']);
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

        $requestClass = $_REQUEST['callable'] ?? null;

        if (is_string($requestClass) && is_a($requestClass, Form::class, true)) {
            $UploadForm = new $requestClass();
        }

        // check file count
        $configMaxFileCount = Permission::getPermission('quiqqer.upload.maxUploadCount');

        if ($configMaxFileCount) {
            $files = File::readDir($canonicalUploadDir);
            $count = count($files) / 2;

            if ($count + 1 >= $configMaxFileCount) {
                throw new QUI\Permissions\Exception([
                    'quiqqer/core',
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


        /**
         * no html5 upload
         */
        if ($filename === null) {
            try {
                $this->formUpload(
                    $onfinish,
                    $params,
                    $configAllowedTypes,
                    $configAllowedEndings
                );
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

        if (!is_scalar($fileType)) {
            throw new Exception('Invalid upload MIME type.', 400);
        }

        $this->checkAllowedUpload($filename, (string)$fileType, $configAllowedTypes, $configAllowedEndings);

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


        $uploadPaths = $this->getUploadPaths($filename);
        $uploaddir = $uploadPaths['directory'];
        $tmp_name = $uploadPaths['file'];

        /* PUT REQUEST */
        $putdata = file_get_contents('php://input');
        $this->appendUploadData($filename, (string)$putdata);

        // upload finish?
        $tmp_name = $this->getUploadPaths($filename)['file'];
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
                'quiqqer/core',
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
     * @throws QUI\Permissions\Exception
     */
    protected function getUserUploadDir(null | QUI\Interfaces\Users\User $User = null): string
    {
        if ($User === null || !QUI::getUsers()->isUser($User)) {
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
    protected function checkUserPermissions(null | QUI\Interfaces\Users\User $User = null): void
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
                'quiqqer/core',
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
     * Validate and normalize one client-provided upload filename.
     *
     * @throws Exception
     */
    public function validateUploadFilename(mixed $filename): string
    {
        if (!is_string($filename) || $filename === '') {
            throw new Exception('Invalid upload filename.', 400);
        }

        if (
            preg_match('/[\x00-\x1F\x7F]/', $filename)
            || str_contains($filename, '/')
            || str_contains($filename, '\\')
            || str_contains($filename, ':')
        ) {
            throw new Exception('Invalid upload filename.', 400);
        }

        $filename = trim($filename);
        $filename = trim($filename, '.');

        if (
            $filename === ''
            || $filename === '.'
            || $filename === '..'
            || strlen($filename . '.json') > self::MAX_FILESYSTEM_NAME_BYTES
        ) {
            throw new Exception('Invalid upload filename.', 400);
        }

        return $filename;
    }

    /**
     * @param QUI\Interfaces\Users\User|null $User
     * @return array{filename: string, directory: string, file: string, metadata: string}
     *
     * @throws Exception
     */
    protected function getUploadPaths(
        mixed $filename,
        null | QUI\Interfaces\Users\User $User = null
    ): array {
        $filename = $this->validateUploadFilename($filename);
        $uploadDirPrefix = $this->getCanonicalUserUploadDir($User);
        $canonicalUploadDir = rtrim($uploadDirPrefix, DIRECTORY_SEPARATOR);
        $file = $uploadDirPrefix . $filename;
        $metadata = $file . '.json';

        $this->assertSafeUploadPath($file, $canonicalUploadDir, $uploadDirPrefix);
        $this->assertSafeUploadPath($metadata, $canonicalUploadDir, $uploadDirPrefix);

        return [
            'filename' => $filename,
            'directory' => $uploadDirPrefix,
            'file' => $file,
            'metadata' => $metadata
        ];
    }

    /**
     * @throws Exception
     */
    protected function getCanonicalUserUploadDir(
        null | QUI\Interfaces\Users\User $User = null
    ): string {
        $uploadDir = rtrim($this->getUserUploadDir($User), '/\\');

        QUIFile::mkdir($uploadDir . DIRECTORY_SEPARATOR);

        if (!is_dir($uploadDir) || is_link($uploadDir)) {
            throw new Exception('Invalid user upload directory.', 400);
        }

        $canonicalUploadDir = realpath($uploadDir);
        $canonicalUploadRoot = realpath(rtrim($this->getDir(), '/\\'));

        if (
            $canonicalUploadDir === false
            || $canonicalUploadRoot === false
            || dirname($canonicalUploadDir) !== $canonicalUploadRoot
        ) {
            throw new Exception('Invalid user upload directory.', 400);
        }

        $uploadRootPrefix = $canonicalUploadRoot . DIRECTORY_SEPARATOR;
        $uploadDirPrefix = $canonicalUploadDir . DIRECTORY_SEPARATOR;

        if (!str_starts_with($uploadDirPrefix, $uploadRootPrefix)) {
            throw new Exception('Invalid user upload directory.', 400);
        }

        return $uploadDirPrefix;
    }

    /**
     * @throws Exception
     */
    private function assertSafeUploadPath(string $path, string $uploadDir, string $uploadDirPrefix): void
    {
        $parent = realpath(dirname($path));

        if (
            $parent === false
            || $parent !== $uploadDir
            || !str_starts_with($path, $uploadDirPrefix)
        ) {
            throw new Exception('Invalid upload path.', 400);
        }

        if (is_link($path)) {
            throw new Exception('Invalid upload path.', 400);
        }

        if (!file_exists($path)) {
            return;
        }

        $canonicalPath = realpath($path);
        $pathStat = lstat($path);

        if (
            !is_file($path)
            || $canonicalPath === false
            || !is_array($pathStat)
            || $pathStat['nlink'] !== 1
            || dirname($canonicalPath) !== $uploadDir
            || !str_starts_with($canonicalPath, $uploadDirPrefix)
        ) {
            throw new Exception('Invalid upload path.', 400);
        }
    }

    /**
     * @param mixed $allowedTypes
     * @param mixed $allowedEndings
     *
     * @throws Exception
     */
    protected function checkAllowedUpload(
        string $filename,
        string $fileType,
        mixed $allowedTypes,
        mixed $allowedEndings
    ): void {
        if (is_array($allowedTypes)) {
            $allowedTypes = implode(',', $allowedTypes);
        }

        if (is_array($allowedEndings)) {
            $allowedEndings = implode(',', $allowedEndings);
        }

        if (!is_scalar($allowedTypes)) {
            $allowedTypes = '';
        }

        if (!is_scalar($allowedEndings)) {
            $allowedEndings = '';
        }

        if ($this->checkFnMatch((string)$allowedTypes, $fileType) === false) {
            throw new Exception([
                'quiqqer/core',
                'exception.upload.not.allowed.mimetype'
            ]);
        }

        if ($this->checkFnMatch((string)$allowedEndings, $filename) === false) {
            throw new Exception([
                'quiqqer/core',
                'exception.upload.not.allowed.ending'
            ]);
        }
    }

    /**
     * @throws Exception
     */
    protected function appendUploadData(string $filename, string $data): void
    {
        $path = $this->getUploadPaths($filename)['file'];
        $Handle = fopen($path, 'ab');

        if ($Handle === false) {
            throw new Exception('Could not open upload file.', 500);
        }

        $handleStat = fstat($Handle);
        $pathStat = lstat($path);
        $isRegularFile = is_array($handleStat)
            && is_array($pathStat)
            && ($handleStat['mode'] & 0170000) === 0100000
            && ($pathStat['mode'] & 0170000) === 0100000
            && $handleStat['dev'] === $pathStat['dev']
            && $handleStat['ino'] === $pathStat['ino']
            && $handleStat['nlink'] === 1
            && $pathStat['nlink'] === 1;

        if (!$isRegularFile) {
            fclose($Handle);
            throw new Exception('Invalid upload file.', 400);
        }

        if ($data !== '' && fwrite($Handle, $data) !== strlen($data)) {
            fclose($Handle);
            throw new Exception('Could not write upload file.', 500);
        }

        fclose($Handle);
    }

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
     * @param callable|string $onfinish - Function
     * @param mixed $params - extra params for the \QUI\QDOM File Object
     * @param mixed $allowedTypes
     * @param mixed $allowedEndings
     *
     * @throws Exception
     */
    protected function formUpload(
        callable | string $onfinish,
        mixed $params,
        mixed $allowedTypes,
        mixed $allowedEndings
    ): void {
        if (empty($_FILES) || !isset($_FILES['files'])) {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.upload.no.data'),
                400
            );
        }

        $list = $_FILES['files'];

        if (!is_array($list) || !array_key_exists('error', $list)) {
            throw new Exception('Invalid upload data.', 400);
        }

        if (!is_array($list['error'])) {
            $this->checkUpload($list['error']);

            $filename = $this->validateUploadFilename($list['name'] ?? null);
            $fileType = is_string($list['type'] ?? null) ? $list['type'] : '';
            $tmpName = $list['tmp_name'] ?? null;
            $this->checkAllowedUpload($filename, $fileType, $allowedTypes, $allowedEndings);

            if (!is_string($tmpName)) {
                throw new Exception('Invalid temporary upload file.', 400);
            }

            $uploadPaths = $this->getUploadPaths($filename);
            $uploadDir = $uploadPaths['directory'];
            $file = $uploadPaths['file'];

            if (!move_uploaded_file($tmpName, $file)) {
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/core', 'exception.media.move', [
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

        if (
            !is_array($list['name'] ?? null)
            || !is_array($list['tmp_name'] ?? null)
            || !is_array($list['type'] ?? null)
        ) {
            throw new Exception('Invalid upload data.', 400);
        }

        foreach ($list['error'] as $key => $error) {
            $this->checkUpload($error);

            $filename = $this->validateUploadFilename($list['name'][$key] ?? null);
            $fileType = is_string($list['type'][$key] ?? null) ? $list['type'][$key] : '';
            $tmpName = $list['tmp_name'][$key] ?? null;
            $this->checkAllowedUpload($filename, $fileType, $allowedTypes, $allowedEndings);

            if (!is_string($tmpName)) {
                throw new Exception('Invalid temporary upload file.', 400);
            }

            $uploadPaths = $this->getUploadPaths($filename);
            $uploadDir = $uploadPaths['directory'];
            $file = $uploadPaths['file'];

            if (!move_uploaded_file($tmpName, $file)) {
                throw new Exception(QUI::getLocale()->get('quiqqer/core', 'exception.media.move', [
                    'file' => $filename
                ]));
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
                    QUI::getLocale()->get('quiqqer/core', 'exception.media.upload.max.filesize')
                );

            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/core', 'exception.media.upload.max.form.filesize')
                );

            case UPLOAD_ERR_PARTIAL:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/core', 'exception.media.upload.partially.uploaded')
                );

            case UPLOAD_ERR_NO_FILE:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/core', 'exception.media.upload.no.data')
                );

            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception(
                    QUI::getLocale()->get('quiqqer/core', 'exception.media.upload.missing.temp')
                );
        }

        return true;
    }

    /**
     * Extract the Archive
     *
     * @throws Exception
     * @todo more archive types
     */
    protected function extract(string $filename): QDOM
    {
        $fileInfo = QUIFile::getInfo($filename);

        if ($fileInfo['mime_type'] != 'application/zip') {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.upload.unsupported.archive')
            );
        }

        $filename = $this->validateUploadFilename($fileInfo['filename'] ?? null);
        $uploadDir = $this->getCanonicalUserUploadDir();
        $canonicalUploadDir = rtrim($uploadDir, DIRECTORY_SEPARATOR);
        $to = $uploadDir . $filename;
        $canonicalTarget = file_exists($to) ? realpath($to) : false;

        if (
            is_link($to)
            || realpath(dirname($to)) !== $canonicalUploadDir
            || (
                file_exists($to)
                && (
                    $canonicalTarget === false
                    || dirname($canonicalTarget) !== $canonicalUploadDir
                    || (!is_file($to) && !is_dir($to))
                )
            )
        ) {
            throw new Exception('Invalid upload extraction path.', 400);
        }

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
     * @param array<array-key, mixed>|string $message
     */
    public function flushMessage(array | string $message): void
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
     * @throws Exception
     */
    protected function delete(string $filename): void
    {
        $uploadPaths = $this->getUploadPaths($filename);

        QUIFile::unlink($uploadPaths['file']);
        QUIFile::unlink($uploadPaths['metadata']);
    }

    /**
     * Add a file to the Upload Manager
     *
     * @param string $filename - filename
     * @param array<string, mixed> $params - optional
     *
     * @throws Exception
     */
    protected function add(string $filename, array $params): void
    {
        $uploadPaths = $this->getUploadPaths($filename);
        $conf = $uploadPaths['metadata'];

        if (file_exists($conf)) {
            return;
        }

        file_put_contents(
            $conf,
            json_encode([
                'file' => $uploadPaths['filename'],
                'user' => QUI::getUserBySession()->getUUID(),
                'params' => $params
            ])
        );
    }

    /**
     * Return a \QUI\QDOM Object of the file entry
     *
     * @throws Exception
     */
    protected function getFileData(string $filename): QDOM
    {
        $uploadPaths = $this->getUploadPaths($filename);
        $conf = $uploadPaths['metadata'];

        if (!file_exists($conf)) {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.file.not.found'),
                404
            );
        }

        $data = json_decode(
            (string)file_get_contents($conf),
            true
        );

        if (
            !is_array($data)
            || ($data['file'] ?? null) !== $uploadPaths['filename']
        ) {
            throw new Exception('Invalid upload metadata.', 400);
        }

        $File = new QDOM();
        $File->setAttributes($data);

        return $File;
    }

    /**
     * Flush an exception to the UploadManager
     */
    public function flushException(\Throwable $Exception): void
    {
        $exception = $Exception->getMessage();

        if (method_exists($Exception, 'toArray')) {
            $exception = $Exception->toArray();
        }

        $message = [
            'Exception' => $exception
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
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     * @throws QUI\Permissions\Exception
     */
    public function getUnfinishedUploadsFromUser(null | QUI\Interfaces\Users\User $User = null): array
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUserBySession();
        }

        $this->checkUserPermissions($User);

        // read user upload dir
        $userUploadDir = $this->getUserUploadDir($User);

        if (!file_exists($userUploadDir)) {
            return [];
        }

        $dir = $this->getCanonicalUserUploadDir($User);

        $files = QUIFile::readDir($dir);
        $result = [];

        foreach ($files as $file) {
            try {
                $File = $this->getFileData($file);
                $attributes = $File->getAttributes();

                if (isset($attributes['params'])) {
                    $params = $attributes['params'];
                    $uploadPath = $this->getUploadPaths($file, $User)['file'];
                    $file_info = QUIFile::getInfo($uploadPath);

                    $params['file']['uploaded'] = $file_info['filesize'];

                    $attributes['params'] = $params;
                }

                $result[] = $attributes;
            } catch (Exception $Exception) {
                if ($Exception->getCode() === 404) {
                    $uploadPath = $this->getUploadPaths($file, $User)['file'];
                    QUIFile::unlink($uploadPath);
                }
            }
        }

        return $result;
    }
}
