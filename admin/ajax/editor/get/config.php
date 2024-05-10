<?php

/**
 * Return the main editor config
 *
 * @return array
 */

QUI::$Ajax->registerFunction('ajax_editor_get_config', static fn() => QUI\Editor\Manager::getConfig());
