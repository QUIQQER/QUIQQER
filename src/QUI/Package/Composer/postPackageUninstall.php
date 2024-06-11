<?php

require "header.php";

QUI\Cache\Manager::clear('quiqqer');
QUI\Cache\Manager::clearPackagesCache();
QUI\Cache\Manager::clearSettingsCache();
QUI\Cache\Manager::clearCompleteQuiqqerCache();
QUI\Cache\LongTermCache::clear('quiqqer');
