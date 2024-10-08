<?php

/**
 * Returns not finished uploads for resume
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_uploads_unfinished',
    static function (): array {
        $UploadManager = new QUI\Upload\Manager();

        return $UploadManager->getUnfinishedUploadsFromUser();
    },
    false,
    'Permission::checkAdminUser'
);
