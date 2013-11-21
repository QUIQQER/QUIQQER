<?php

/**
 * This file contains \QUI\Plugins\Factory
 */

namespace QUI\Plugins;

/**
 * Factory Klasse eines Plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.plugins
 *
 * @todo Factory Klasse für Plugins umsetzen
 * @todo ???
 */
class Factory extends \QUI\QDOM
{
    /**
     * Erweitert die Tabs im Admin
     * (optional)
     *
     * @param \QUI\Controls\Toolbar\Bar $Tabbar - Tabbar / Toolbar Objekt
     * @param \QUI\Projects\Site $Site          - Aktuelle Seite
     */
    public function setTabs(\QUI\Controls\Toolbar\Bar $Tabbar, \QUI\Projects\Site $Site) {}

    /**
     * Methode welche aufgerufen wird wenn eine Seite gespeichert wird
     * (optional)
     *
     * @param \QUI\Projects\Site $Site
     * @param \QUI\Projects\Project $Project
     * @return unknown
     */
    public function onSave(\QUI\Projects\Site $Site, \QUI\Projects\Project $Project) {}

    /**
     * Methode welche aufgerufen wird wenn eine Seite initialisiert wird
     * (optional)
     *
     * @param \QUI\Projects\Site $Site
     * @param \QUI\Projects\Project $Project
     */
    public function onLoad(\QUI\Projects\Site $Site, \QUI\Projects\Project $Project) {}

    /**
     * Methode welche aufgerufen wird, wenn eine Seite zerstört wird
     * (optional)
     *
     * @param \QUI\Projects\Site $Site       - Seite die zerstört wird
     * @param \QUI\Projects\Project $Project - Project in welchem die Seite liegt
     */
    public function onDestroy(\QUI\Projects\Site $Site, \QUI\Projects\Project $Project) {}

    /**
     * Methode die aufgerufen wird beim Setup des Plugins
     * Zusätzlich zur database.xml
     * (optional)
     *
     * @param \QUI\Projects\Project $Project
     */
    public function setup(\QUI\Projects\Project $Project) {}

    /**
     * Methode die aufgerufen wird beim Deinstallieren des Plugins
     * (optional)
     */
    public function onUninstall() {}

    /**
     * Crons registrieren welche das Plugin zur Verfügung stellt
     * (optional)
     *
     * @param System_Cron_Manager $CronManager
     */
    public function registerCrons(System_Cron_Manager $CronManager) {}
}
