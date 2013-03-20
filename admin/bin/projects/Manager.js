/**
 * Opens the project manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module admin/projects/Manager
 * @package
 * @namespace
 */

define('admin/projects/Manager', [

    'controls/projects/Manager'

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