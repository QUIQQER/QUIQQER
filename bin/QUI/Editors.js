/**
 * Global Editor manager
 * define: QUI.Editors
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Editors
 */

define('Editors', ['classes/editor/Manager'], function(Editors)
{
    "use strict";

    if ( typeof QUI.Editors !== 'undefined' ) {
        return QUI.Editors;
    }

    QUI.Editors = new Editors();

    return QUI.Editors;
});