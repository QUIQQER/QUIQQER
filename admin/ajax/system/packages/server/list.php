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
                'type'   => $type,
                'active' => $active
            );
        }

        usort($data, function ($a, $b) {
            if ($a['server'] == $b['server']) {
                return 0;
            }
            return $a['server'] < $b['server'] ? -1 : 1;
        });

        return $data;
    },
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
