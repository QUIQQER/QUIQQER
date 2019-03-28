<?php

/**
 * Return the toolbar xml
 *
 * @param string $toolbar - toolbar name
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_editor_get_toolbar_xml',
    function ($toolbar) {
        $file = QUI\Editor\Manager::getToolbarsPath().$toolbar;
        $file = QUI\Utils\Security\Orthos::clearPath($file);

        if (\file_exists($file)) {
            return \file_get_contents($file);
        }

        return '';
    },
    ['toolbar']
);
