<?php

/**
 * Return the editor settings for a specific project
 *
 * @param string $project - project data
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_editor_get_projectFiles',
    function ($project) {
        try {
            $Project = QUI::getProject($project);

        } catch (QUI\Exception $Exception) {
            return array(
                'cssFiles' => '',
                'bodyId' => '',
                'bodyClass' => ''
            );
        }

        return QUI\Editor\Manager::getSettings($Project);
    },
    array('project')
);
