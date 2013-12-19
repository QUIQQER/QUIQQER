/**
 * The main loading script for the quiqqer administration
 *
 * @author www.namerobot.com (Henning Leutz)
 */

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
            'css': URL_OPT_DIR +'bin/qui/src/lib/css'
        }
    }
});

require([

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

], function(Locale, Ajax, Welcome, Workspace, Column, Panel, TaskPanel, Button, BookmarkPanel, ProjectPanel)
{
    "use strict";

    Locale.setCurrent( USER.lang );

    // load the default workspace
    var doc_size  = document.body.getSize(),
        Container = document.getElement( '.qui-workspace-container' ),
        Logo      = document.getElement( '.qui-logo-container' ),
        Menu      = document.getElement( '.qui-menu-container' );

    Container.setStyles({
        height : doc_size.y - Logo.getSize().y - Menu.getSize().y
    });

    var MyWorkspace = new Workspace().inject( Container );

    // Columns
    var LeftColumn   = new Column(),

        MiddleColumn = new Column({
            width : doc_size.x * 0.8
        });


    MyWorkspace.appendChild( LeftColumn );
    MyWorkspace.appendChild( MiddleColumn );

    // projects panel
    LeftColumn.appendChild(
        new ProjectPanel()
    );

    // bookmarks panel
    var Bookmarks = new BookmarkPanel({
        title : 'Bookmarks'
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
    console.log( QUIQQER_LOCALE );

    require( QUIQQER_LOCALE, function()
    {
        require(['Locale'], function(Locale) {
            console.log( Locale );
        });
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