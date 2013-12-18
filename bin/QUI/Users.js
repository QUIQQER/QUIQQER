/**
 * Global Group Manager Object object
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Groups
 * @package com.pcsg.qui.js
 * @namespace QUI
 */

define('Users', ['classes/users/Manager'], function(Users)
{
    "use strict";

    if ( typeof QUI.Users !== 'undefined' ) {
        return QUI.Users;
    }

    QUI.Users = new Users();

    return QUI.Users;
});