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
    static function ($project, $id, $attributes) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        QUI::getEvents()->fireEvent('onSiteSaveAjaxBegin', [$Site]);

        $attributes = json_decode($attributes, true);

        try {
            $Site->setAttributes($attributes);

            if ($Site->getAttribute('release_from') || $Site->getAttribute('release_to')) {
                $Site->setAttribute('auto_release', 1);
            } else {
                $Site->setAttribute('auto_release', 0);
            }

            $Site->save();
            $Site->refresh();
        } catch (QUI\Exception $Exception) {
            QUI::getMessagesHandler()->addError($Exception->getMessage());
        }

        QUI::getEvents()->fireEvent('onSiteSaveAjaxEnd', [$Site]);

        try {
            require_once __DIR__ . '/get.php';

            $result = QUI::$Ajax->callRequestFunction('ajax_site_get', [
                'project' => json_encode($Project->toArray()),
                'id' => $id
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
