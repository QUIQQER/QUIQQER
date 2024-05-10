<?php

/**
 * Return the sub sites from a site
 *
 * @param string $project -JSON Array, Project Data
 * @param integer|String $id - Site ID
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_project_sites_navigation',
    static function ($project, $id): array {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = $Project->get($id);

        $result = [];
        $list = $Site->getNavigation();

        foreach ($list as $Child) {
            /* @var $Child \QUI\Projects\Site */
            $result[] = [
                'id' => $Child->getAttribute('id'),
                'name' => $Child->getAttribute('name'),
                'title' => $Child->getAttribute('title'),
                'type' => $Child->getAttribute('type'),
                'url' => URL_DIR . $Child->getUrlRewritten(),
                'image_site' => $Child->getAttribute('image_site'),
                'image_emotion' => $Child->getAttribute('image_emotion'),
                'hasChildren' => $Child->hasChildren()
            ];
        }

        return $result;
    },
    ['project', 'id']
);
