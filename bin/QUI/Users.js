/**
 * Global User Manager object
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
