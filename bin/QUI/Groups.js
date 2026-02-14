/**
 * Global Group Manager object
 */
define('Groups', [

    'qui/QUI',
    'classes/groups/Manager'

], function (QUI, Groups) {
    "use strict";

    if (typeof QUI.Groups !== 'undefined') {
        return QUI.Groups;
    }

    QUI.Groups = new Groups();

    return QUI.Groups;
});
