/**
 * Global Editor manager
 * define: QUI.Editors
 *
 * @module Editors
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require classes/editor/Manager
 */
define('Editors', [

    'qui/QUI',
    'classes/editor/Manager'

], function (QUI, Editors) {
    "use strict";

    if (typeof QUI.Editors !== 'undefined') {
        return QUI.Editors;
    }

    QUI.Editors = new Editors();

    return QUI.Editors;
});
