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
    function ($params) {
        $params = json_decode($params, true);

        $Project = QUI\Projects\Manager::createProject(
            $params['project'],
            $params['lang']
        );

        
        if (isset($params['template']) && !empty($params['template'])) {
            $Config = QUI::getProjectManager()->getConfig();

            $installedTemplates = QUI::getPackageManager()->getInstalled([
                'type' => 'quiqqer-template'
            ]);

            $template = $params['template'];
            $template = \QUI\Utils\Security\Orthos::removeHTML($template);
            $template = \QUI\Utils\Security\Orthos::clearPath($template);
            $Config->set($Project->getName(), 'template', $template);
            $Config->save();
        }

        if ($params['demoData']) {
            \QUI\Utils\Project::applyDemoDataToProject($Project, $template);
        }

        return $Project->getName();
    },
    ['params'],
    'Permission::checkAdminUser'
);
