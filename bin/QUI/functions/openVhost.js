/**
 * click event and opens the vhost panel
 */
define('functions/openVhost', function () {
    "use strict";

    return function () {
        require([
            'utils/Panels',
            'controls/system/VHosts'
        ], function (PanelUtils, VhostPanel) {
            PanelUtils.openPanelInTasks(new VhostPanel());
        });
    };
});
