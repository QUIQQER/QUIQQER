/**
 * Global locale object
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Locale
 * @package com.pcsg.qui.js
 * @namespace QUI
 */

define('Locale', [

    'classes/utils/Locale'

], function(QUI_Locale)
{
    if ( typeof QUI.Locale !== 'undefined' ) {
        return QUI.Locale;
    }

    QUI.Locale = QUI.L = new QUI_Locale({
        events :
        {
            onError : function(str) {
                console.error( 'Locale Error:: '+ str );
            }
        }
    });

    return QUI.Locale;
});