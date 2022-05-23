<?php

/**
 * Set the config of an project
 *
 * @param string $project - project name
 * @param string $params - JSON Array
 */
QUI::$Ajax->registerFunction(
    'ajax_project_set_config',
    function ($project, $params) {
        $Project = QUI\Projects\Manager::getProject($project);
        $params  = json_decode($params, true);

        if (isset($params['project-custom-css'])) {
            $Project->setCustomCSS($params['project-custom-css']);
            unset($params['project-custom-css']);
        }

        if (isset($params['project-custom-javascript'])) {
            $Project->setCustomJavaScript($params['project-custom-javascript']);
            unset($params['project-custom-javascript']);
        }

        QUI\Projects\Manager::setConfigForProject($project, $params);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'message.project.config.save.success'
            )
        );
    },
    ['project', 'params'],
    'Permission::checkAdminUser'
);
