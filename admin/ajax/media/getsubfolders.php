<?php

/**
 * Returns the children folders
 *
 * @param string $project - Name of the project
 * @param string $fileid - FileID
 *
 * @return array
 * @throws \QUI\Exception
 */

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_getsubfolders',
    static function ($project, $fileid, $params) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($fileid);
        $params = json_decode($params, true);

        if (!($File instanceof Folder)) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.media.not.a.folder'
            ]);
        }

        $children = [];
        $folders = $File->getFolders($params);

        // count
        $params['count'] = true;
        unset($params['limit']);

        $count = $File->getFolders($params);

        // create children data
        foreach ($folders as $Child) {
            $children[] = QUI\Projects\Media\Utils::parseForMediaCenter($Child);
        }

        return [
            'children' => $children,
            'count' => $count
        ];
    },
    ['project', 'fileid', 'params'],
    'Permission::checkAdminUser'
);
