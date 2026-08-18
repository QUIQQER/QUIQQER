<?php

/**
 * Search last edited sites
 *
 * @param string $params - search string
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_search_lastEditSites',
    static function ($params) {
        $params = json_decode($params, true);
        $projects = QUI::getProjectManager()->getProjects(true);

        /* @var $Project QUI\Projects\Project */

        if (empty($projects)) {
            return [];
        }

        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $selects = [];
        $parameters = [];
        $index = 0;

        foreach ($projects as $Project) {
            $projectParameter = 'project' . $index;
            $langParameter = 'lang' . $index;
            $table = $Platform->quoteSingleIdentifier($Project->table());

            $selects[] = 'SELECT ' . implode(', ', [
                $Platform->quoteSingleIdentifier('id'),
                $Platform->quoteSingleIdentifier('e_date'),
                $Platform->quoteSingleIdentifier('name'),
                $Platform->quoteSingleIdentifier('title'),
                ':' . $projectParameter . ' AS project',
                ':' . $langParameter . ' AS lang'
            ]) . ' FROM ' . $table;

            $parameters[$projectParameter] = $Project->getName();
            $parameters[$langParameter] = $Project->getLang();
            $index++;
        }

        $QueryBuilder = $Connection->createQueryBuilder();
        $QueryBuilder
            ->select('id', 'e_date', 'name', 'title', 'project', 'lang')
            ->from('(' . implode(' UNION ALL ', $selects) . ')', 'merged')
            ->orderBy('e_date', 'DESC')
            ->setMaxResults(10);

        foreach ($parameters as $parameter => $value) {
            $QueryBuilder->setParameter($parameter, $value);
        }

        return $QueryBuilder->executeQuery()->fetchAllAssociative();
    },
    ['params'],
    'Permission::checkAdminUser'
);
