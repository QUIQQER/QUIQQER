<?php

/**
 * Cron Schnittstelle
 */

define('SYSTEM_INTERN', true);

require_once "header.php";

ignore_user_abort(true);
System_Cron_Manager::exec($Users->getSystemUser());

?>