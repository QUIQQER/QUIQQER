<?php

/**
 * Return all update servers
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_server_list',
    function () {
        $list = QUI::getPackageManager()->getServerList();
        $data = array();

        foreach ($list as $server => $params) {
            $active = 0;
            $type   = '';

            if (isset($params['active'])) {
                $active = (int)$params['active'];
            }

            if (isset($params['type'])) {
                $type = $params['type'];
            }

            $data[] = array(
                'server' => $server,
                'type' => $type,
                'active' => $active
            );
        }

        return $data;
    },
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
