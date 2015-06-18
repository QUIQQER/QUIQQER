<?php

/**
 * Returns the file data
 *
 * @param String $project - Name of the project
 * @param String $fileid  - File-ID
 *
 * @return Array
 */
function ajax_media_get($project, $fileid)
{
    $Project = QUI\Projects\Manager::getProject($project);
    $Media = $Project->getMedia();
    $File = $Media->get($fileid);

    $parents = $File->getParents();
    $breadcrumb = array();
    $children = array();
    $_children = array();

    if ($File->getType() === 'QUI\\Projects\\Media\\Folder') {
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


    return array(
        'file'       => QUI\Projects\Media\Utils::parseForMediaCenter($File),
        'breadcrumb' => $breadcrumb,
        'children'   => $children
    );
}

QUI::$Ajax->register(
    'ajax_media_get',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
