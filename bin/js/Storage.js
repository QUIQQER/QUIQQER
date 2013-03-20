/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 */

define('Storage', [

    'classes/users/Storage',
    'Users'

], function(UserStorage)
{
    "use strict";

    QUI.Storage = new UserStorage(
        QUI.Users.getUserBySession()
    );

    return QUI.Storage;
});