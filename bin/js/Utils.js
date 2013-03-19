/**
 * Global utils object
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Utils
 * @package com.pcsg.qui.js
 * @namespace QUI
 */

define('Utils', [

    'classes/utils/Utils'

], function(QUI_Utils)
{
    if ( typeof QUI.Utils !== 'undefined' ) {
        return QUI.Utils;
    }

    QUI.Utils = new QUI_Utils();

    return QUI.Utils;
});