<?php

/**
 * Activate a site
 *
 * @param string $project
 * @param string $id
 * @return bool
 */
QUI::$Ajax->registerFunction(
    'ajax_site_activate',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        try {
            $Site->activate();
        } catch (QUI\Exception $Exception) {
            switch ($Exception->getCode()) {
                case 1119:
                case 1120:
                    QUI::getMessagesHandler()->addAttention(
                        $Exception->getMessage()
                    );
                    break;

                default:
                    throw $Exception;
            }
        }

        return $Site->getAttribute('active') ? 1 : 0;
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
