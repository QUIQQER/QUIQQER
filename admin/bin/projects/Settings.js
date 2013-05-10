/**
 * Opens the project settings
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module admin/projects/Settings
 * @package
 * @namespace
 */

define('admin/projects/Settings', [

    'controls/projects/Settings'

], function(Settings)
{
    "use strict";

    return function(Item)
    {
        if ( Item.getAttribute( 'project' ) )
        {
            QUI.Workspace.appendPanel(
                new Settings( Item.getAttribute( 'project' ) )
            );
        }
    };
});
