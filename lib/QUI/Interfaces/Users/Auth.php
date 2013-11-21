<?php

/**
 * This file contains \QUI\Interfaces\Users\Auth
 */

namespace QUI\Interfaces\Users;

/**
 * Interface für Authentifizierung
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.interface.users
 */

interface Auth
{
    /**
     * Authentifiziert einen Benutzer
     *
     * @param unknown_type $username
     * @param unknown_type $password
     * @return Bool
     */
    public function auth($username, $password);

    /**
     * Gibt die Daten eines Benutzers zurück
     *
     * @param String $username
     * @param String $fields
     */
    public function getUser($username, $fields=false);

    /**
     * Gibt alle Benutzer zurück
     */
    public function getUsers();

    /**
     * Gibt die Daten einer Gruppe zurück
     *
     * @param String $groupname
     */
    public function getGroup($groupname);

    /**
     * Gibt alle Gruppen zurück
     */
    public function getGroups();
}
