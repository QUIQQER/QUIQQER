<?php

/**
 * Return all installed packages
 *
 * @param string $str - Search string
 * @param string|integer $from - Sheet start
 * @param string|integer $max - Limit of the results
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_search',
    function ($str, $from, $max) {
        if (!isset($from) || !$from) {
            $from = 1;
        }

        if (!isset($max) || !$max) {
            $max = 20;
        }

        $from = (int)$from;
        $max  = (int)$max;

        $result = QUI::getPackageManager()->searchPackage($str);
        $result = QUI\Utils\Grid::getResult($result, $from, $max);

        $data = array();

        $list      = QUI::getPackageManager()->getInstalled();
        $installed = array();

        foreach ($list as $package) {
            $installed[$package['name']] = true;
        }


        foreach ($result['data'] as $package => $description) {
            $data[] = array(
                'package' => $package,
                'description' => $description,
                'isInstalled' => isset($installed[$package]) ? true : false
            );
        }

        $result['data'] = $data;

        return $result;
    },
    array('str', 'from', 'max'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
