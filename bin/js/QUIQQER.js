/**
 * QUIQQER Main Object
 *
 * @requires classes/QUIQQER
 */

// if console not exist
if ( typeof console === 'undefined' )
{
    var console =
    {
        log   : function() {},
        error : function() {},
        info  : function() {},
        warn  : function() {}
    };
}

// IE Flickering Bug
try
{
    document.execCommand( "BackgroundImageCache", false, true );
} catch ( err )
{
    // Nothing to do
}

define('QUIQQER', ['classes/QUIQQER'], function(QUI)
{
    // require config
    require.config({
        baseUrl : URL_BIN_DIR +'js/',
        paths   : {
            "package" : URL_OPT_DIR,
            "admin"   : URL_SYS_DIR +'bin/',
            "locale"  : URL_VAR_DIR +'locale/bin/'
        },

        waitSeconds : 0,
        locale      : "de-de",
        catchError  : true,
        map: {
            '*': {
                'css': 'mvc/css'
            }
        }
    });

    // quiqqer
    window.QUI = new QUI();
    window.QUI.config( 'dir', URL_BIN_DIR +'js/' );

    // @todo maybe save with an intervall
    window.onunload = window.onbeforeunload = (function()
    {
        if ( typeof window.QUI !== 'undefined' &&
             typeof window.QUI.Workspace !== 'undefined' )
        {
            return window.QUI.Workspace.save();
        }
    });

    require([ 'Controls' ], function()
    {
        window.addEvent('domready', function() {
            window.QUI.load();
        });
    });

    return window.QUI;
});
