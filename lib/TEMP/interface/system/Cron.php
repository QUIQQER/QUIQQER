<?php

/**
 * This file contains Interface_System_Cron
 */

/**
 * Cron Interface
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.interface.system
 */

interface Interface_System_Cron
{
    /**
     * Exceute the Cron
     *
     * @param Array $params - Parameter for the cron
     */
	public function execute($params=array());
}

?>