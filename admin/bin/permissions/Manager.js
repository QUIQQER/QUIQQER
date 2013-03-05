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
    return function()
    {
        QUI.Controls.get( 'content-panel' )[ 0 ].appendChild(
            new QUI.controls.permissions.Panel()
        );
    };
});