/**
 * The main loading script for the quiqqer administration
 *
 * @author www.pcsg.de (Henning Leutz)
 */

// extend mootools with desktop drag drop
Object.append(Element.NativeEvents, {
    dragenter: 2,
    dragleave: 2,
    dragover: 2,
    dragend: 2,
    drop: 2
});

// custome select
// eq: getElements( 'input:display(inline)' )
Slick.definePseudo('display', function(value) {
    "use strict";
    return Element.getStyle(this, 'display') == value;
});

// IE Flickering Bug
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
        "package" : URL_OPT_DIR +'bin/',
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


/**
 * Init quiqqer
 */

// main require list + locale translations
var requireList = [
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
].append( QUIQQER_LOCALE || [] );


require( requireList, function()
{
    "use strict";

    /**
     *
     */
    var QUI           = arguments[ 0 ],
        Locale        = arguments[ 1 ],
        Ajax          = arguments[ 2 ],
        Welcome       = arguments[ 3 ],
        Workspace     = arguments[ 4 ],
        Column        = arguments[ 5 ],
        Panel         = arguments[ 6 ],
        TaskPanel     = arguments[ 7 ],
        Button        = arguments[ 8 ],
        BookmarkPanel = arguments[ 9 ],
        ProjectPanel  = arguments[ 10 ];

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
    var LeftColumn = new Column(),

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
        title  : 'Bookmarks',
        icon   : 'icon-bookmark',
        name   : 'qui-bookmarks',
        events :
        {
            onInject : function(Panel)
            {
                Panel.Loader.show();

                require(['Users'], function(Users)
                {
                    var User = Users.get( USER.id );

                    User.load(function()
                    {
                        var data = User.getAttribute( 'qui-bookmarks' );

                        if ( !data )
                        {
                            Panel.Loader.hide();
                            return;
                        }

                        Panel.unserialize( data );
                        Panel.Loader.hide();
                    });
                });
            },

            onAppendChild : function(Panel, Item)
            {
                Panel.Loader.show();

                require(['Users'], function(Users)
                {
                    var User = Users.get( USER.id );

                    User.setAttribute( 'qui-bookmarks', Panel.serialize() );

                    User.save(function() {
                        Panel.Loader.hide();
                    });
                });
            },

            onRemoveChild : function(Panel)
            {
                Panel.Loader.show();

                require(['Users'], function(Users)
                {
                    var User = Users.get( USER.id );

                    User.setExtra( 'qui-bookmarks', Panel.serialize() );

                    User.save(function() {
                        Panel.Loader.hide();
                    });
                });
            }
        }
    });

    LeftColumn.appendChild( Bookmarks );

    // Bookmarks.toggle();

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

    window.addEvent('resize', function()
    {
        // load the default workspace
        var doc_size  = document.body.getSize(),
            Container = document.getElement( '.qui-workspace-container' ),
            Logo      = document.getElement( '.qui-logo-container' ),
            Menu      = document.getElement( '.qui-menu-container' );

        Container.setStyles({
            height : doc_size.y - Logo.getSize().y - Menu.getSize().y,
            width  : doc_size.x - 10 // -10, wegen der scrollbar
        });

        (function() {
            MyWorkspace.resize();
        }).delay( 100 );
    });

    /**
     * Menu
     */
    require(['Menu']);

    /**
     * UploadManager && MessageHandler
     */
    require([

        'UploadManager',
        'qui/controls/messages/Panel',
        'controls/desktop/panels/Help'

    ], function(UploadManager, MessagePanel, Help)
    {
        new MessagePanel().inject( RightColumn );
        new Help().inject( RightColumn );

        UploadManager.inject( RightColumn );
        UploadManager.toggle();

        QUI.getMessageHandler(function(MessageHandler)
        {
            // if 404 -> not loged in, than login pop
            MessageHandler.addEvent('onAdd', function(MH, Message)
            {
                if ( Message.getAttribute( 'code' ) == 401 )
                {
                    require(['controls/system/Login'], function(Login) {
                        new Login().open();
                    });
                }
            });
        });
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

    // logout function
    window.logout = function()
    {
        Ajax.post('ajax_user_logout', function() {
            window.location = '/admin/admin.php';
        });
    };

//    require(['controls/projects/Popup'], function(Popup) {
//        new Popup({
//            events :
//            {
//                onSubmit : function(Control, result)
//                {
//                    console.warn( result );
//                }
//            }
//        }).open();
//    });

    // media popup test
//    require(['controls/projects/project/media/Popup'], function(Popup)
//    {
//        new Popup({
//            events :
//            {
//                onSubmit : function(Popup, imageData)
//                {
//                    console.warn( imageData );
//                }
//            }
//        }).open();
//    });

    // contextmenu
//    require([
//        'Menu',
//        'qui/controls/contextmenu/Item'
//    ], function(Menu, ContextmenuItem)
//    {
//        // Bookmar text
//        Bookmarks.appendChild(
//            new ContextmenuItem({
//                text : 'test'
//            })
//        );
//
//        Bookmarks.Loader.hide();
//    });
});