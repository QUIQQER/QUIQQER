<?php

/**
 * Returns not finished uploads for resume
 *
 * @param string $file
 * @return array
 */
function ajax_uploads_cancel($file)
{
    $UploadManager = new \QUI\Upload\Manager();
    $UploadManager->cancel($file);
}

QUI::$Ajax->register(
    'ajax_uploads_cancel',
    array('file'),
    'Permission::checkAdminUser'
);
