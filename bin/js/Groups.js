/**
 * Global groups manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Groups
 * @package com.pcsg.qui.js
 * @namespace QUI
 */

define('Groups', [

    'classes/groups/Groups'

], function(QUI_Groups)
{
    if ( typeof QUI.Groups !== 'undefined' ) {
        return QUI.Groups;
    }

    QUI.Groups = new QUI_Groups();

    return QUI.Groups;
});