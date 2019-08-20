<?php

/**
 * Return the site children
 *
 * @param string $project
 * @param integer $id
 * @param string $params - JSON Array
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_getchildren',
    function ($project, $id, $params) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);
        $params  = \json_decode($params, true);

        $Packages   = QUI::getPackageManager();
        $attributes = false;

        if (isset($params['attributes'])) {
            $attributes = \explode(',', $params['attributes']);
        }

        // forerst kein limit
        if (isset($params['limit']) && $params['limit']) {
            $children = $Site->getChildren([
                'limit' => $params['limit']
            ]);
        } else {
            $children = $Site->getChildren();
        }

        $result = [];

        for ($i = 0, $len = \count($children); $i < $len; $i++) {
            $Child = $children[$i];
            /* @var $Child \QUI\Projects\Site\Edit */

            if (!$attributes) {
                $result[$i] = $Child->getAttributes();
            } else {
                foreach ($attributes as $attribute) {
                    $result[$i][$attribute] = $Child->getAttribute($attribute);
                }
            }

            $result[$i]['id'] = $Child->getId();

            if (!$attributes || \in_array('has_children', $attributes)) {
                $result[$i]['has_children'] = $Child->hasChildren(true);
            }

            if (!$attributes || \in_array('config', $attributes)) {
                $result[$i]['config'] = $Child->conf; // old??
            }

            if ($Child->isLinked() && $Child->isLinked() != $Site->getId()) {
                $result[$i]['linked'] = 1;
            }

            // Projekt Objekt muss da nicht mit
            if (isset($result[$i]['project'])
                && \is_object($result[$i]['project'])
            ) {
                unset($result[$i]['project']);
            }

            // icon
            if (!$attributes || \in_array('icon', $attributes)) {
                if ($Child->getAttribute('type') != 'standard') {
                    $result[$i]['icon'] = $Packages->getIconBySiteType($Child->getAttribute('type'));
                }
            }
        }

        return [
            'count'    => $Site->hasChildren(true),
            'children' => $result
        ];
    },
    ['project', 'id', 'params'],
    'Permission::checkAdminUser'
);
