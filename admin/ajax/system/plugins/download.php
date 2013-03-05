<?php

/**
 * Downloads a update file
 *
 * @param String $file
 */

function ajax_system_plugins_download($file)
{
    $Update = new \QUI\Update();

    global $oldpercent;
    $oldpercent = 0;

    $Update->download($file, function($percent, $file)
    {
        global $oldpercent;

        // nur bei veränderung ausgeben, performanter
        if ( $oldpercent != (int)$percent )
        {
            $oldpercent = (int)$percent;
            Update::flushMessage($percent, 'message', $file);
        }
    });
}
QUI::$Ajax->register('ajax_system_plugins_download', array('file'), 'Permission::checkSU');

?>