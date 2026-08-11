<?php

/**
 * Save localized project titles as user-edit locale values.
 *
 * @param string $project
 * @param string $translations
 *
 * @return array<string, mixed>
 * @throws QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_project_set_title_locale_data',
    static function (string $project, string $translations): array {
        $translations = json_decode($translations, true);

        if (!is_array($translations)) {
            throw new QUI\Exception('Invalid project title translations.');
        }

        $Project = QUI\Projects\Manager::getProject($project);
        $Project->setTitleLocaleData($translations);

        return $Project->getTitleLocaleData();
    },
    ['project', 'translations'],
    'Permission::checkAdminUser'
);
