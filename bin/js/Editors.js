/**
 * Global Editor manager
 * define: QUI.Editors
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Editors
 * @package com.pcsg.qui.js
 * @namespace QUI
 */

define('Editors', [

    'classes/editor/Manager'

], function(QUI_Editors)
{
    if ( typeof QUI.Editors !== 'undefined' ) {
        return QUI.Editors;
    }

    QUI.Editors = new QUI_Editors();

    return QUI.Editors;
});