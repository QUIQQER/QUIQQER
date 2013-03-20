/**
 * Opens the media manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 */

define('admin/permissions/Manager', [

    'controls/permissions/Panel'

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