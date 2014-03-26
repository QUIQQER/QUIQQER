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

define('Menu', [

    'controls/menu/Manager'

], function(Menu)
{
    "use strict";

    if ( typeof QUI.Menu !== 'undefined' ) {
        return QUI.Menu;
    }

    QUI.Menu = new Menu({
        name : 'QUIQQER-Menu'
    }).inject(
        document.getElement( '.qui-menu-container' )
    );

    return QUI.Menu;
});