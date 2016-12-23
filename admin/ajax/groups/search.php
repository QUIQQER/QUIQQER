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
        $page   = 1;

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
                'type'  => '%LIKE%',
                'value' => $value
            );
        }

        $Grid = new QUI\Utils\Grid();
        $Grid->setAttribute('page', $page);

        return $Grid->parseResult($Groups->search($query), $Groups->count($params));
    },
    array('fields', 'params'),
    'Permission::checkAdminUser'
);
