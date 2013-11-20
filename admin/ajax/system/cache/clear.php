<?php

/**
 * cache clearing
 */
function ajax_system_cache_clear($params)
{
    $params = json_decode( $params, true );

    if ( isset($params['compile']) && $params['compile'] == 1 ) {
        \QUI\Utils\System\File::unlink( VAR_DIR .'cache/compile' );
    }

    if ( isset($params['plugins']) && $params['plugins'] == 1 ) {
        \QUI_Plugins_Manager::clearCache();
    }

    if ( !isset($params['compile']) || $params['compile'] != 1 &&
         !isset($params['plugins']) || $params['plugins'] != 1 )
    {
        return;
    }

    \QUI\Cache\Manager::clearAll();
    \QUI_Plugins_Manager::clearCache();
}

\QUI::$Ajax->register(
    'ajax_system_cache_clear',
    array('params'),
    'Permission::checkSU'
);
