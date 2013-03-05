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
    return function()
    {
        QUI.Controls.get( 'content-panel' )[ 0 ].appendChild(
            new QUI.controls.packages.Panel()
        );
    };
});