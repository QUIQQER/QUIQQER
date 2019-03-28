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

        $PluginManager = QUI::getPluginManager();
        $attributes    = false;

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

        $childs = [];

        for ($i = 0, $len = \count($children); $i < $len; $i++) {
            $Child = $children[$i];
            /* @var $Child \QUI\Projects\Site\Edit */

            if (!$attributes) {
                $childs[$i] = $Child->getAttributes();
            } else {
                foreach ($attributes as $attribute) {
                    $childs[$i][$attribute] = $Child->getAttribute($attribute);
                }
            }

            $childs[$i]['id'] = $Child->getId();

            if (!$attributes || \in_array('has_children', $attributes)) {
                $childs[$i]['has_children'] = $Child->hasChildren(true);
            }

            if (!$attributes || \in_array('config', $attributes)) {
                $childs[$i]['config'] = $Child->conf; // old??
            }

            if ($Child->isLinked() && $Child->isLinked() != $Site->getId()) {
                $childs[$i]['linked'] = 1;
            }

            // Projekt Objekt muss da nicht mit
            if (isset($childs[$i]['project'])
                && \is_object($childs[$i]['project'])
            ) {
                unset($childs[$i]['project']);
            }

            // icon
            if (!$attributes || \in_array('icon', $attributes)) {
                if ($Child->getAttribute('type') != 'standard') {
                    $childs[$i]['icon'] = $PluginManager->getIconByType(
                        $Child->getAttribute('type')
                    );
                }
            }
        }

        return [
            'count'    => $Site->hasChildren(true),
            'children' => $childs
        ];
    },
    ['project', 'id', 'params'],
    'Permission::checkAdminUser'
);
