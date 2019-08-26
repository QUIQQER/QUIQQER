/**
 * Global Menu manager
 *
 * @module Menu
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require controls/menu/Manager
 */
define('Menu', [

    'qui/QUI',
    'controls/menu/Manager'

], function (QUI, Menu) {
    "use strict";

    if (typeof QUI.Menu !== 'undefined') {
        return QUI.Menu;
    }

    QUI.Menu = new Menu({
        name: 'QUIQQER-Menu'
    }).inject(
        document.getElement('.qui-menu-container')
    );

    return QUI.Menu;
});
