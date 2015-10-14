/**
 * Global Group Manager object
 *
 * @module Groups
 * @author www.pcsg.de (Henning Leutz)
 * @require classes/groups/Manager
 */
define(['classes/groups/Manager'], function (Groups) {
    "use strict";

    if (typeof QUI.Groups !== 'undefined') {
        return QUI.Groups;
    }

    QUI.Groups = new Groups();

    return QUI.Groups;
});
