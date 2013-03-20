/**
 * Opens the media manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 */

define('admin/packages/Manager', [

    'controls/packages/Panel'

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