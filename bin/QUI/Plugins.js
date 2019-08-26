/**
 * Plugins
 *
 * @module Plugins
 * @author www.pcsg.de (Henning Leutz)
 * @require classes/plugins/Manager
 * @deprecated -> package manager
 */
define(['classes/plugins/Manager'], function (PluginManager) {
    "use strict";

    if (typeof QUI.Plugins !== 'undefined') {
        return QUI.Plugins;
    }

    QUI.Plugins = new PluginManager();

    return QUI.Plugins;
});
