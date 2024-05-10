<?php

/**
 * Upload callback if a file is finished uploaded
 */

QUI::$Ajax->registerFunction(
    'ajax_uploads_callback',
    static function ($File, $callable) {
        if (!isset($callable)) {
            return;
        }

        if (!class_exists($callable)) {
            return;
        }

        QUI\Permissions\Permission::checkPermission('quiqqer.frontend.upload');

        $Callable = new $callable();

        if ($Callable instanceof QUI\Upload\Form) {
            /* @var $File \QUI\QDOM */
            $Callable->onFileFinish(
                $File->getAttribute('filepath'),
                $File->getAttributes()
            );
        }
    },
    ['File', 'callable']
);
