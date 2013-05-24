
/**
 * Opens the about window
 */

define('admin/quiqqer/About', [

    'controls/windows/Alert'

], function(QUI_Window)
{
    "use strict";

    return function()
    {
        new QUI_Window({
            title  : 'About QUIQQER',
            icon   : URL_BIN_DIR +'16x16/quiqqer.png',
            height : 170,
            body   : '<div style="text-align: center; margin-top: 30px;">' +
                        '<h2>QUIQQER Management System</h2>' +
                        '<p><a href="http://www.quiqqer.com" target="_blank">www.quiqqer.com</a></p>' +
                        '<br />' +
                        'Version: ' + QUI_VERSION +
                        '<br />' +
                        '<p>Copyright <a href="http://www.pcsg.de" target="_blank">www.pcsg.de</a></p>' +
                        '<p>Author: Henning Leutz & Moritz Scholz</p>' +
                    '</div>'
        }).create();
    };
});
