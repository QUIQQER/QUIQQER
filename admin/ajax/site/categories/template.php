<?php

/**
 * Return the tab content
 *
 * @param string $project
 * @param string $id
 * @param string $tab
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_site_categories_template',
    function ($project, $id, $tab) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $templates = QUI::getPackageManager()->searchInstalledPackages(['type' => "quiqqer-template"]);
        $layouts   = $Project->getLayouts();

        $html = QUI\Utils\DOM::getTabHTML($tab, $Site, [
            'templates' => $templates,
            'layouts'   => $layouts
        ]);

        return QUI\Utils\StringHelper::removeLineBreaks($html);
    },
    ['project', 'id', 'tab'],
    'Permission::checkAdminUser'
);
