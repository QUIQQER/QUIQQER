/**
 * The main loading script for the quiqqer administration
 *
 * @author www.namerobot.com (Henning Leutz)
 */

// extend mootools with desktop drag drop
Object.append(Element.NativeEvents, {
    dragenter: 2,
    dragleave: 2,
    dragover: 2,
    dragend: 2,
    drop: 2
});

//IE Flickering Bug
try
{
    document.execCommand( "BackgroundImageCache", false, true );
} catch ( err )
{
    // Nothing to do
}

// require config
require.config({
    baseUrl : URL_BIN_DIR +'QUI/',
    paths : {
        "package" : URL_OPT_DIR,
        "qui"     : URL_OPT_DIR +'bin/qui/src',
        "locale"  : URL_VAR_DIR +'locale/bin'
    },

    waitSeconds : 0,
    locale : USER.lang +"-"+ USER.lang,
    catchError : true,

    map : {
        '*': {
            'css': URL_OPT_DIR +'bin/qui/src/lib/css.js'
        }
    }
});

require([

    'qui/QUI',
    'Locale',
    'Ajax',
    'controls/welcome/Panel',
    'qui/controls/desktop/Workspace',
    'qui/controls/desktop/Column',
    'qui/controls/desktop/Panel',
    'qui/controls/desktop/Tasks',
    'qui/controls/buttons/Button',
    'qui/controls/bookmarks/Panel',
    'controls/projects/project/Panel'

], function(QUI, Locale, Ajax, Welcome, Workspace, Column, Panel, TaskPanel, Button, BookmarkPanel, ProjectPanel)
{
    "use strict";

    Locale.setCurrent( USER.lang );

    // load the default workspace
    var doc_size  = document.body.getSize(),
        Container = document.getElement( '.qui-workspace-container' ),
        Logo      = document.getElement( '.qui-logo-container' ),
        Menu      = document.getElement( '.qui-menu-container' );

    Container.setStyles({
        height : doc_size.y - Logo.getSize().y - Menu.getSize().y,
        width  : doc_size.x - 10 // -10, wegen der scrollbar
    });

    var MyWorkspace = new Workspace().inject( Container );

    // Columns
    var LeftColumn   = new Column(),

        MiddleColumn = new Column({
            width : doc_size.x * 0.8
        }),

        RightColumn = new Column({
            width : doc_size.x * 0.2
        });


    MyWorkspace.appendChild( LeftColumn );
    MyWorkspace.appendChild( MiddleColumn );
    MyWorkspace.appendChild( RightColumn );

    // projects panel
    LeftColumn.appendChild(
        new ProjectPanel()
    );

    // bookmarks panel
    var Bookmarks = new BookmarkPanel({
        title : 'Bookmarks',
        icon  : ''
    });

    LeftColumn.appendChild( Bookmarks );
    Bookmarks.Loader.show();

    // task panel
    MiddleColumn.appendChild(
        new TaskPanel({
            title : 'My Panel 1',
            icon  : 'icon-heart',
            name  : 'tasks'
        })
    );

    MiddleColumn.getChildren( 'tasks' ).appendChild(
        new Welcome()
    );

    // resize the worksapce
    // we have a resize bug
    // because the scrollbar have 16 pixel
    MyWorkspace.resize();

    (function() {
        MyWorkspace.resize();
    }).delay( 100 );

    /**
     * Locale
     */
    require( QUIQQER_LOCALE, function() {});

    /**
     * UploadManager
     */
    require(['UploadManager'], function(UploadManager) {
        UploadManager.inject( RightColumn );
    });

    /**
     * MessageHandler
     */
    QUI.getMessageHandler(function(MessageHandler)
    {
        new Panel({
            title  : 'Nachrichten',
            name   : 'message-handler',
            events :
            {
                onCreate : function(Panel)
                {
                    var Content = Panel.getContent();

                    Content.setStyles({
                        padding : 5
                    });

                    MessageHandler.bindParent( Content );
                    MessageHandler.open();

                    Content.getElement( '.message-handler-container' ).setStyles({
                        zIndex : 100
                    });
                }
            }
        }).inject( RightColumn );
    });

    /**
     * If files were droped to quiqqer
     * dont show it
     */
    document.id( document.body ).addEvents({
        drop : function(event) {
            event.preventDefault();
        },

        dragend : function(event) {
            event.preventDefault();
        },

        dragover: function(event) {
            event.preventDefault();
        }
    });


    // contextmenu
    require([
        'Menu',
        'qui/controls/contextmenu/Item'
    ], function(Menu, ContextmenuItem)
    {
        // Bookmar text
        Bookmarks.appendChild(
            new ContextmenuItem({
                text : 'test'
            })
        );

        Bookmarks.Loader.hide();
    });
});