/**
 * Display a password result for QUIQQER Desktop Search
 *
 * @module package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/Sites
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/Sites', [

    'utils/Panels'

], function (PanelUtils) {
    "use strict";

    return new Class({
        Type: 'package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/Sites',

        initialize: function (options) {
            PanelUtils.openSitePanel(options.projectName, options.projectLang, options.siteId);
        }
    });
});
