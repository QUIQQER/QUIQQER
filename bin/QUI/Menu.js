
/**
 * Global Menu manager
 *
 * @module Menu
 * @author www.pcsg.de (Henning Leutz)
 */

define(['controls/menu/Manager'], function(Menu)
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
