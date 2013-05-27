
/**
 * Opens the changelog window
 */

define('admin/quiqqer/Logs', [

    'controls/system/logs/Panel'

], function(Panel)
{
    "use strict";

    return function()
    {
        QUI.Workspace.appendPanel(
            new Panel()
        );
    };
});
