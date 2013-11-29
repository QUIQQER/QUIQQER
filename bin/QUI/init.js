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

    'Ajax',
    'qui/controls/desktop/Workspace',
    'qui/controls/desktop/Column',
    'qui/controls/desktop/Panel',
    'qui/controls/desktop/Tasks',
    'qui/controls/buttons/Button',
    'qui/controls/bookmarks/Panel',
    'controls/projects/project/Panel'

], function(Ajax, Workspace, Column, Panel, TaskPanel, Button, BookmarkPanel, ProjectPanel)
{
    "use strict";

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
            title   : 'My Panel 1',
            icon    : 'icon-heart'
        })
    );

    // resize the worksapce
    // we have a resize bug
    // because the scrollbar have 16 pixel
    MyWorkspace.resize();

    (function() {
        MyWorkspace.resize();
    }).delay( 100 );



    // contextmenu
    require([
        'qui/controls/contextmenu/Bar',
        'qui/controls/contextmenu/BarItem',
        'qui/controls/contextmenu/Item'
    ], function(ContextmenuBar, ContextmenuBarItem, ContextmenuItem)
    {
        var Bar = new ContextmenuBar().inject(
            document.getElement( '.qui-menu-container' )
        );

        Ajax.get('ajax_menu', function(result) {
            Bar.insert( result );
        });


        // Bookmar text
        Bookmarks.appendChild(
            new ContextmenuItem({
                text : 'test'
            })
        );

        Bookmarks.Loader.hide();
    });
});