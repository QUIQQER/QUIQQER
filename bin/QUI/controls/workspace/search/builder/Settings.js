/**
 * Open a settings panel
 *
 * @module package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/builder/Settings
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/builder/Settings', [

    'utils/Panels',
    'controls/desktop/panels/XML'

], function (PanelUtils, XMLPanel) {
    "use strict";

    return new Class({
        Type: 'package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/builder/Settings',

        initialize: function (options) {
            PanelUtils.openPanelInTasks(new XMLPanel(options.xmlFile, {
                category: options.category
            }));
        }
    });
});
