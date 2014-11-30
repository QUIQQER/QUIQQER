<?php

/**
 * Return the root ID
 *
 * @return Integer
 */
function ajax_groups_root()
{
	require_once 'get.php';

    return ajax_groups_get(
        (int)QUI::conf('globals', 'root')
    );
}

\QUI::$Ajax->register('ajax_groups_root', false, 'Permission::checkSU');
