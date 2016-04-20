<?php

/**
 * Gruppen suchen
 *
 * @param string $params - json array
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_search',
    function ($fields, $params) {
        $Groups = QUI::getGroups();
        $params = json_decode($params, true);
        $fields = json_decode($fields, true);
        $query  = array();

        if (!is_array($fields)) {
            $fields = array();
        }

        if (isset($params['order'])) {
            $query['order'] = $params['order'];
        }

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        foreach ($fields as $field => $value) {
            $query['where_or'][$field] = array(
                'type' => '%LIKE%',
                'value' => $value
            );
        }

        return $Groups->search($query);
    },
    array('fields', 'params'),
    'Permission::checkAdminUser'
);
