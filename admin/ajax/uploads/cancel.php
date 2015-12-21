<?php

/**
 * Returns not finished uploads for resume
 *
 * @param string $file
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_uploads_cancel',
    function ($file) {
        $UploadManager = new QUI\Upload\Manager();
        $UploadManager->cancel($file);
    },
    array('file'),
    'Permission::checkAdminUser'
);
