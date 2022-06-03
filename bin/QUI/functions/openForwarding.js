/**
 * click event and opens the forwarding panel
 */
define('functions/openForwarding', function () {
    "use strict";

    return function () {
        require([
            'utils/Panels',
            'controls/system/forwarding/Panel'
        ], function (PanelUtils, Forwarding) {
            PanelUtils.openPanelInTasks(new Forwarding());
        });
    };
});
