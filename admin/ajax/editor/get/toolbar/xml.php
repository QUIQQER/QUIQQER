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

        if (!\is_dir($file) && \file_exists($file)) {
            return \file_get_contents($file);
        }

        QUI\System\Log::addError('Toolbar not found', [
            'toolbar' => $toolbar
        ]);

        $files = QUI\Utils\System\File::readDir(
            QUI\Editor\Manager::getToolbarsPath()
        );

        return \file_get_contents(QUI\Editor\Manager::getToolbarsPath().'/'.$files[0]);
    },
    ['toolbar']
);
