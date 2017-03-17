/**
 * Display a password result for QUIQQER Desktop Search
 *
 * @module package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/Media
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/Media', [

    'utils/Panels'

], function (PanelUtils) {
    "use strict";

    return new Class({
        Type: 'package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/Media',

        initialize: function (options) {
            PanelUtils.openMediaPanel(options.project, {'fileid':options.id});
        }
    });
});
