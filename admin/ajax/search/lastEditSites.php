<?php

/**
 * Search last edited sites
 *
 * @param String $params - search string
 *
 * @return Array
 */
function ajax_search_lastEditSites($params)
{
    $params = json_decode($params, true);
    $projects = QUI::getProjectManager()->getProjects(true);
    $tables = array();

    /* @var $Project QUI\Projects\Project */

    $PDO = QUI::getDataBase()->getPDO();
    $selects = array();

    foreach ($projects as $Project) {

        $table = $Project->getAttribute('db_table');
        $lang = $Project->getLang();
        $project = $Project->getName();

        $selects[] = "
            SELECT id, e_date, name, title,
                '{$project}' as project,
                '{$lang}' as lang,
                '{$table}' as table_name
            FROM
                `{$table}`
        ";
    }

    $query = 'SELECT id, e_date, name, title, project, lang FROM ('. implode(' UNION ', $selects) .') AS merged ORDER BY e_date DESC LIMIT 0,10';
    $Statement = $PDO->prepare($query);
    $Statement->execute();

    $result = $Statement->fetchAll(\PDO::FETCH_ASSOC);

    return $result;
}

QUI::$Ajax->register(
    'ajax_search_lastEditSites',
    array('params'),
    'Permission::checkAdminUser'
);
