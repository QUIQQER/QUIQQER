<?php

/**
 * Return the data of the parents
 *
 * @param string $project - Name of the project
 * @param string $fileid - File-ID
 * @return array
 */

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_breadcrumb',
    static function ($project, $fileid): array {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($fileid);
        $parents = [];

        if ($File instanceof Folder) {
            $parents = $File->getParents();
        }

        $breadcrumb = [];

        // create breadcrumb data
        foreach ($parents as $Parent) {
            $breadcrumb[] = QUI\Projects\Media\Utils::parseForMediaCenter($Parent);
        }

        $breadcrumb[] = QUI\Projects\Media\Utils::parseForMediaCenter($File);

        return $breadcrumb;
    },
    ['project', 'fileid'],
    'Permission::checkAdminUser'
);
