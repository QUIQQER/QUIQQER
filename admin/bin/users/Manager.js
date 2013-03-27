/**
 * Opens the users manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 */

define('admin/users/Manager', [

    'controls/users/Panel'

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