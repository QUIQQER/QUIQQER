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
    return function()
    {
        QUI.Controls.get( 'content-panel' )[ 0 ].appendChild(
            new Manager()
        );
    };
});