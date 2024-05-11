<?php

/**
 * Returns the file data
 *
 * @param string $project - Name of the project
 * @param string $fileid - File-ID
 *
 * @return array
 */

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_get',
    static function ($project, $fileid) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($fileid);
        $parents = [];

        if ($File instanceof Folder) {
            $parents = $File->getParents();
        }

        $breadcrumb = [];
        $children = [];
        $_children = [];

        if ($File instanceof Folder) {
            $_children = $File->getChildren();
        }

        // create breadcrumb data
        foreach ($parents as $Parent) {
            $breadcrumb[] = QUI\Projects\Media\Utils::parseForMediaCenter($Parent);
        }

        $breadcrumb[] = QUI\Projects\Media\Utils::parseForMediaCenter($File);

        // create children data
        foreach ($_children as $Child) {
            $children[] = QUI\Projects\Media\Utils::parseForMediaCenter($Child);
        }

        return [
            'file' => QUI\Projects\Media\Utils::parseForMediaCenter($File),
            'breadcrumb' => $breadcrumb,
            'children' => $children
        ];
    },
    ['project', 'fileid'],
    'Permission::checkAdminUser'
);
