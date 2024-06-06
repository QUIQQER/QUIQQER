<?php

/**
 * Returns not finished uploads for resume
 *
 * @param string $file
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_uploads_cancel',
    static function ($file): void {
        $UploadManager = new QUI\Upload\Manager();
        $UploadManager->cancel($file);
    },
    ['file'],
    'Permission::checkAdminUser'
);
