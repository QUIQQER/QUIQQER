/**
 * Global Group Manager Object object
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Groups
 * @package com.pcsg.qui.js
 * @namespace QUI
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