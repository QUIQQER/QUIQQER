<?php

/**
 * Search for the desktop
 *
 * @param string $search
 * @param string $params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_workspace_getEntry',
    function ($id, $provider) {
        $Search  = QUI\Workspace\Search\Search::getInstance();
        $Builder = QUI\Workspace\Search\Builder::getInstance();

        if (empty($provider)) {
            return $Search->getEntry($id);
        }

        $Provider = $Builder->getProvider($provider);

        return $Provider->getEntry($id);
    },
    array('id', 'provider')
);
