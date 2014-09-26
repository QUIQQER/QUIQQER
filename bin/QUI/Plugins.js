
/**
 * Plugins
 *
 * @module Plugins
 * @author www.pcsg.de (Henning Leutz)
 */

define(['classes/plugins/Manager'], function(PluginManager)
{
    "use strict";

    if ( typeof QUI.Plugins !== 'undefined' ) {
        return QUI.Plugins;
    }

    QUI.Plugins = new PluginManager();

    return QUI.Plugins;
});
