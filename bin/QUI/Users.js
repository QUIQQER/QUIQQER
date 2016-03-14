/**
 * Global User Manager object
 *
 * @module Users
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require classes/users/Manager
 */
define('Users', [

    'qui/QUI',
    'classes/users/Manager'

], function (QUI, Users) {
    "use strict";

    if (typeof QUI.Users !== 'undefined') {
        return QUI.Users;
    }

    QUI.Users = new Users();

    return QUI.Users;
});
