<?php

/**
 * This file contains \QUI\Interfaces\Users\Auth
 */

namespace QUI\Interfaces\Users;

/**
 * Interface für Authentifizierung
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.interface.users
 * @licence For copyright and license information, please view the /README.md
 */

interface Auth
{
    /**
     * Authentifiziert einen Benutzer
     *
     * @param string $username
     * @param string $password
     *
     * @return Bool
     */
    public function auth($username, $password);

    /**
     * Gibt die Daten eines Benutzers zurück
     *
     * @param string      $username
     * @param string|bool $fields
     */
    public function getUser($username, $fields = false);

    /**
     * Gibt alle Benutzer zurück
     */
    public function getUsers();

    /**
     * Gibt die Daten einer Gruppe zurück
     *
     * @param string $groupname
     */
    public function getGroup($groupname);

    /**
     * Gibt alle Gruppen zurück
     */
    public function getGroups();
}
