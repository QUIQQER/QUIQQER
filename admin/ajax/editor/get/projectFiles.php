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
    static function ($project): array {
        try {
            $Project = QUI::getProject($project);
        } catch (QUI\Exception) {
            return [
                'cssFiles' => '',
                'bodyId' => '',
                'bodyClass' => ''
            ];
        }

        return QUI\Editor\Manager::getSettings($Project);
    },
    ['project']
);
