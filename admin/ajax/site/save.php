<?php

/**
 * Saves a site
 *
 * @param string $project - project data
 * @param integer $id - Site ID
 * @param string $attributes - JSON Array
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_save',
    function ($project, $id, $attributes) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        QUI::getEvents()->fireEvent('onSiteSaveAjaxBegin', [$Site]);

        $attributes = \json_decode($attributes, true);

        try {
            $Site->setAttributes($attributes);
            $Site->save();
            $Site->refresh();
        } catch (QUI\Exception $Exception) {
            QUI::getMessagesHandler()->addError($Exception->getMessage());
        }

        QUI::getEvents()->fireEvent('onSiteSaveAjaxEnd', [$Site]);

        try {
            require_once 'get.php';

            $result = QUI::$Ajax->callRequestFunction('ajax_site_get', [
                'project' => \json_encode($Project->toArray()),
                'id'      => $id
            ]);

            return $result['result'];
        } catch (QUI\Exception $Exception) {
            QUI::getMessagesHandler()->addError($Exception->getMessage());
        }

        return [];
    },
    ['project', 'id', 'attributes'],
    'Permission::checkAdminUser'
);
