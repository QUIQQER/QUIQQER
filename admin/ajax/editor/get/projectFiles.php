<?php

/**
 * Return the editor settings for a specific project
 *
 * @param string $project - project data
 *
 * @return array
 */
function ajax_editor_get_projectFiles($project)
{
    try {
        $Project = QUI::getProject($project);

    } catch (QUI\Exception $Exception) {
        return array(
            'cssFiles'  => '',
            'bodyId'    => '',
            'bodyClass' => ''
        );
    }

    return QUI\Editor\Manager::getSettings($Project);
}

QUI::$Ajax->register('ajax_editor_get_projectFiles', array('project'));
