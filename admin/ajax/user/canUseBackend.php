<?php

/**
 * User is admin check
 */

QUI::$Ajax->registerFunction('ajax_user_canUseBackend', fn() => QUI::getUserBySession()->canUseBackend());
