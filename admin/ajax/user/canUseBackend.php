<?php

/**
 * User is admin check
 */

QUI::$Ajax->registerFunction('ajax_user_canUseBackend', static fn(): bool => QUI::getUserBySession()->canUseBackend());
