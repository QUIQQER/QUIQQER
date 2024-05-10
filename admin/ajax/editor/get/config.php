<?php

/**
 * Return the main editor config
 *
 * @return array
 */

QUI::$Ajax->registerFunction('ajax_editor_get_config', static fn(): array => QUI\Editor\Manager::getConfig());
