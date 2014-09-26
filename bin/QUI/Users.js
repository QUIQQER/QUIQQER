
/**
 * Global User Manager object
 *
 * @module Users
 * @author www.pcsg.de (Henning Leutz)
 */

define(['classes/users/Manager'], function(Users)
{
    "use strict";

    if ( typeof QUI.Users !== 'undefined' ) {
        return QUI.Users;
    }

    QUI.Users = new Users();

    return QUI.Users;
});
