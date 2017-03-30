<?php

/**
 * Search last edited sites
 *
 * @param string $params - search string
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_search_lastEditSites',
    function ($params) {
        $params   = json_decode($params, true);
        $projects = QUI::getProjectManager()->getProjects(true);

        /* @var $Project QUI\Projects\Project */

        $PDO     = QUI::getDataBase()->getPDO();
        $selects = array();

        foreach ($projects as $Project) {
            $table   = $Project->table();
            $lang    = $Project->getLang();
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

        $query = 'SELECT id, e_date, name, title, project, lang
                 FROM (' . implode(' UNION ', $selects) . ') AS merged
                 ORDER BY e_date DESC LIMIT 0,10';

        $Statement = $PDO->prepare($query);
        $Statement->execute();

        return $Statement->fetchAll(\PDO::FETCH_ASSOC);
    },
    array('params'),
    'Permission::checkAdminUser'
);
