/**
 * Opens the media manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 */

define('admin/media/Manager', [

    'controls/projects/media/Manager'

], function(Manager)
{
    "use strict";

    return function()
    {
        QUI.Workspace.appendPanel(
            new Manager()
        );
    };
});