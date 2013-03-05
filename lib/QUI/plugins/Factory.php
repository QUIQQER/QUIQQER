<?php

/**
 * This file contains QUI_Plugins_Factory
 */

/**
 * Factory Klasse eines Plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.plugins
 *
 * @todo Factory Klasse für Plugins umsetzen
 */
class QUI_Plugins_Factory extends QDOM
{
	/**
	 * Erweitert die Tabs im Admin
	 * (optional)
	 *
	 * @param Controls_Toolbar_Bar $Tabbar - Tabbar / Toolbar Objekt
	 * @param Projects_Site $Site          - Aktuelle Seite
	 */
	public function setTabs(Controls_Toolbar_Bar $Tabbar, Projects_Site $Site) {}

	/**
	 * Methode welche aufgerufen wird wenn eine Seite gespeichert wird
	 * (optional)
	 *
	 * @param Projects_Site $Site
	 * @param Projects_Project $Project
	 * @return unknown
	 */
	public function onSave(Projects_Site $Site, Projects_Project $Project) {}

	/**
	 * Methode welche aufgerufen wird wenn eine Seite initialisiert wird
	 * (optional)
	 *
	 * @param Projects_Site $Site
	 * @param Projects_Project $Project
	 */
	public function onLoad(Projects_Site $Site, Projects_Project $Project) {}

	/**
	 * Methode welche aufgerufen wird, wenn eine Seite zerstört wird
	 * (optional)
	 *
	 * @param Projects_Site $Site       - Seite die zerstört wird
	 * @param Projects_Project $Project - Project in welchem die Seite liegt
	 */
	public function onDestroy(Projects_Site $Site, Projects_Project $Project) {}

	/**
	 * Methode die aufgerufen wird beim Setup des Plugins
	 * Zusätzlich zur database.xml
	 * (optional)
	 *
	 * @param Projects_Project $Project
	 */
	public function setup(Projects_Project $Project) {}

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


?>