<?php

/**
 * Return the sub sites from a site
 *
 * @param string $project -JSON Array, Project Data
 * @param integer|String $id - Site ID
 * @return array
 */
function ajax_project_sites_navigation($project, $id)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = $Project->get($id);

    $result = array();
    $list   = $Site->getNavigation();

    foreach ($list as $Child) {
        /* @var $Child \QUI\Projects\Site */
        $result[] = array(
            'id' => $Child->getAttribute('id'),
            'name' => $Child->getAttribute('name'),
            'title' => $Child->getAttribute('title'),
            'type' => $Child->getAttribute('type'),
            'url' => URL_DIR . $Child->getUrlRewritten(),
            'image_site' => $Child->getAttribute('image_site'),
            'image_emotion' => $Child->getAttribute('image_emotion'),
            'hasChildren' => $Child->hasChildren()
        );
    }

    return $result;
}

QUI::$Ajax->register(
    'ajax_project_sites_navigation',
    array('project', 'id')
);
