/**
 * Global Editor manager
 * define: QUI.Editors
 *
 * @module Editors
 * @author www.pcsg.de (Henning Leutz)
 * @require classes/editor/Manager
 */
define(['classes/editor/Manager'], function (Editors) {
    "use strict";

    if (typeof QUI.Editors !== 'undefined') {
        return QUI.Editors;
    }

    QUI.Editors = new Editors();

    return QUI.Editors;
});
