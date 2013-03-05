<?php

/**
 * Returns not finished uploads for resume
 *
 * @return Array
 */
function ajax_uploads_cancel($file)
{
    $UploadManager = new QUI_Upload_Manager();
    $UploadManager->cancel( $file );
}
QUI::$Ajax->register('ajax_uploads_cancel', array('file'), 'Permission::checkAdminUser');

?>