<?php

/**
 * Update Window Template für das CMS
 *
 * @return String
 * @deprecated
 */
QUI::$Ajax->registerFunction(
    'ajax_system_update_template',
    function ($tpltype) {
        $Engine  = QUI::getTemplateManager()->getEngine();
        $Plugins = QUI::getPlugins();

        if ($tpltype == 'plugin') {
            $Engine->assign([
                'plugins' => $Plugins->getAvailablePlugins(true)
            ]);
        }

        $Engine->assign([
            'tpltype' => $tpltype
        ]);

        return $Engine->fetch(CMS_DIR.'admin/ajax/system/update/template.html');
    },
    ['tpltype'],
    'Permission::checkSU'
);
