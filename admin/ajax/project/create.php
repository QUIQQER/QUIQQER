<?php

/**
 * Create a new project
 *
 * @param string $params - JSON Array
 *
 * @return string - Name of the project
 */

QUI::$Ajax->registerFunction(
    'ajax_project_create',
    static function ($params): string {
        $params = json_decode($params, true);
        $template = '';

        // @todo check if template is allowed
        if (!empty($params['template'])) {
            $template = $params['template'];
            $template = QUI\Utils\Security\Orthos::removeHTML($template);
            $template = QUI\Utils\Security\Orthos::clearPath($template);
        }

        $Project = QUI\Projects\Manager::createProject(
            $params['project'],
            $params['lang'],
            [],
            $template
        );

        if (isset($params['demodata']) && $params['demodata'] && !empty($template)) {
            QUI\Utils\Project::applyDemoDataToProject($Project, $template);
        }

        return $Project->getName();
    },
    ['params'],
    'Permission::checkAdminUser'
);
