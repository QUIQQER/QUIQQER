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
    return function()
    {
        QUI.Controls.get( 'content-panel' )[ 0 ].appendChild(
            new Manager()
        );
    };
});