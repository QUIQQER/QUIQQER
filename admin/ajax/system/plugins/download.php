<?php

/**
 * Downloads a update file
 *
 * @param string $file
 */
QUI::$Ajax->registerFunction(
    'ajax_system_plugins_download',
    function ($file) {
        $Update = new \QUI\Update();

        global $oldpercent;
        $oldpercent = 0;

        $Update->download($file, function ($percent, $file) {
            global $oldpercent;

            // nur bei veränderung ausgeben, performanter
            if ($oldpercent != (int)$percent) {
                $oldpercent = (int)$percent;
                \QUI\Update::flushMessage($percent, 'message', $file);
            }
        });
    },
    array('file'),
    'Permission::checkSU'
);
