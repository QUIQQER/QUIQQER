<?php

/**
 * Return the available demodata sets for a template.
 *
 * @param string $template
 *
 * @return array<string, array<string, string>>
 */

QUI::$Ajax->registerFunction(
    'ajax_project_get_demodataSets',
    static function ($template): array {
        if (empty($template)) {
            return [];
        }

        $template = QUI\Utils\Security\Orthos::removeHTML($template);
        $template = QUI\Utils\Security\Orthos::clearPath($template);

        return QUI\Utils\Project::getDemoDataSetsForTemplate($template);
    },
    ['template'],
    'Permission::checkAdminUser'
);
