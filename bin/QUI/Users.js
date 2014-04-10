/**
 * Global User Manager object
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Users
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