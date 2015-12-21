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

        QUI\Projects\Manager::setConfigForProject($project, $params);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.project.config.save.success'
            )
        );
    },
    array('project', 'params'),
    'Permission::checkAdminUser'
);
