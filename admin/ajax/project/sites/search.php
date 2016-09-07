<?php

/**
 * Search sites in a project
 *
 * @param string $project - Project data; JSON Array
 * @param string $search - search string
 * @param string $params - JSON Array, search parameter
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_project_sites_search',
    function ($project, $search, $params) {
        $params  = json_decode($params, true);
        $Project = QUI::getProjectManager()->decode($project);

        $sites  = $Project->search($search, $params['fields']);
        $result = array();

        foreach ($sites as $Site) {
            /* @var $Site \QUI\Projects\Site */
            $result[] = array(
                'id'     => $Site->getId(),
                'name'   => $Site->getAttribute('name'),
                'title'  => $Site->getAttribute('title'),
                'c_date' => $Site->getAttribute('c_date'),
                'c_user' => $Site->getAttribute('c_user'),
                'e_date' => $Site->getAttribute('e_date'),
                'e_user' => $Site->getAttribute('e_user')
            );
        }

        return QUI\Utils\Grid::getResult($result, 1, 10);
    },
    array('project', 'search', 'params'),
    'Permission::checkAdminUser'
);
