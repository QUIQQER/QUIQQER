/**
 * Global Group Manager object
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Groups
 */

define('Groups', ['classes/groups/Manager'], function(Groups)
{
    "use strict";

    if ( typeof QUI.Groups !== 'undefined' ) {
        return QUI.Groups;
    }

    QUI.Groups = new Groups();

    return QUI.Groups;
});