<?php

/**
 * User profile template
 *
 * @return String
 */

QUI::$Ajax->registerFunction('ajax_user_profileTemplate', static fn(): string => QUI::getUsers()->getProfileTemplate());
