<?php

namespace QUI\Upload;

use QUI;
use QUI\Permissions\Permission;

use function json_encode;
use function str_replace;

/**
 * Class Form
 */
class Form extends QUI\QDOM
{
    /**
     * Form constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        // defaults
        $this->setAttributes([
            'name' => 'test-upload',
            'contextMenu' => true,
            'multiple' => true,
            'sendbutton' => true,
            'uploads' => 1,
            'hasFile' => false,
            'deleteFile' => true,

            'allowedFileTypes' => false, // eq: ['image/jpeg', 'image/png']
            'allowedFileEnding' => false, // eq: ['.gif', '.jpg']
            'maxFileSize' => false, // eq: 20000000 = 20mb

            'typeOfLook' => 'DragDrop', // DragDrop, Icon, Single
            'typeOfLookIcon' => 'fa fa-upload'
        ]);

        // set default allowed file types
        if (!isset($params['allowedFileTypes'])) {
            $allowedTypes = Permission::getPermission(
                'quiqqer.upload.allowedTypes'
            );

            $this->setAttribute('allowedFileTypes', $allowedTypes);
        }

        // set default allowed file endings
        if (!isset($params['allowedFileEnding'])) {
            $allowedEndings = Permission::getPermission(
                'quiqqer.upload.allowedEndings'
            );

            $this->setAttribute('allowedFileEnding', $allowedEndings);
        }

        parent::setAttributes($params);
    }

    /**
     * Return the generated JS control
     */
    public function create(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $typeOfLook = match ($this->getAttribute('typeOfLook')) {
            'DragDrop', 'Icon', 'Single' => $this->getAttribute('typeOfLook'),
            default => 'DragDrop',
        };


        $Engine->assign([
            'this' => $this,
            'name' => $this->getAttribute('name'),
            'id' => QUI\Utils\Uuid::get(),
            'uploads' => (int)$this->getAttribute('uploads'),
            'contextMenu' => $this->phpBool2JsBool((bool)$this->getAttribute('contextMenu')),
            'multiple' => $this->phpBool2JsBool((bool)$this->getAttribute('multiple')),
            'sendbutton' => $this->phpBool2JsBool((bool)$this->getAttribute('sendbutton')),
            'hasFile' => $this->phpBool2JsBool((bool)$this->getAttribute('hasFile')),
            'deleteFile' => $this->phpBool2JsBool((bool)$this->getAttribute('deleteFile')),
            'callable' => str_replace('\\', '\\\\', $this->getType()),
            'typeOfLook' => $typeOfLook
        ]);

        $maxFileSize = $this->getAttribute('maxFileSize');
        $allowedFileTypes = $this->getAttribute('allowedFileTypes');

        if (!$maxFileSize) {
            $Engine->assign('maxFileSize', $this->phpBool2JsBool($maxFileSize));
        } else {
            $Engine->assign('maxFileSize', (int)$maxFileSize);
        }

        if (!$allowedFileTypes) {
            $Engine->assign('allowedFileTypes', '[]');
        } else {
            $Engine->assign('allowedFileTypes', json_encode($allowedFileTypes));
        }

        return $Engine->fetch(__DIR__ . '/Form.html');
    }

    /**
     * Return a php bool var for js bool
     *
     * @param $var
     * @return string
     */
    public function phpBool2JsBool($var): string
    {
        return $var ? 'true' : 'false';
    }

    //region API Events

    /**
     * Can be overwritten - will be called if the upload is finished
     *
     * @param $file
     * @param $params
     */
    public function onFileFinish($file, $params)
    {
    }

    //endregion
}
