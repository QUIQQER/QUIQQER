
/**
 * Opens the changelog window
 */

define('admin/quiqqer/Changelog', [

    'controls/windows/Alert'

], function(QUI_Window)
{
    "use strict";

    return function()
    {
        new QUI_Window({
            title  : 'QUIQQER Changelog',
            icon   : URL_BIN_DIR +'16x16/quiqqer.png',
            height : 250,
            width  : 600,
            body   : '',
            events :
            {
                onCreate : function(Win)
                {
                    Win.Loader.show();

                    QUI.Ajax.get('ajax_system_changelog', function(result, Request)
                    {
                        Win.setBody(
                            '<pre><code>'+ result +'</code></pre>'
                        );

                        Win.getBody().getElement( 'pre' ).setStyles({
                            height     : 190,
                            whiteSpace : 'pre-wrap'
                        });

                        Win.Loader.hide();
                    });
                }
            }
        }).create();
    };
});
