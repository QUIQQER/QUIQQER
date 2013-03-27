/**
 * Opens the groups manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 */

define('admin/groups/Manager', [

    'controls/groups/Panel'

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