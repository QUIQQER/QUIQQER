<?php

/**
 * Returns not finished uploads for resume
 *
 * @return Array
 */
function ajax_uploads_unfinished()
{
    $UploadManager = new QUI_Upload_Manager();

	return $UploadManager->getUnfinishedUploadsFromUser();
}
QUI::$Ajax->register('ajax_uploads_unfinished', false, 'Permission::checkAdminUser');

?>