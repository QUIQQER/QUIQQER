
/**
 * Global Group Manager object
 *
 * @module Groups
 * @author www.pcsg.de (Henning Leutz)
 */

define(['classes/groups/Manager'], function(Groups)
{
    "use strict";

    if ( typeof QUI.Groups !== 'undefined' ) {
        return QUI.Groups;
    }

    QUI.Groups = new Groups();

    return QUI.Groups;
});
