
/**
 * The main loading script for the quiqqer administration
 *
 * @author www.pcsg.de (Henning Leutz)
 */

// extend mootools with desktop drag drop
Object.append(Element.NativeEvents, {
    dragenter : 2,
    dragleave : 2,
    dragover  : 2,
    dragend   : 2,
    drop      : 2
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

//requestAnimationFrame polyfill
(function()
{
    var lastTime = 0;
    var vendors = ['webkit', 'moz'];

    for ( var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x )
    {
        window.requestAnimationFrame = window[ vendors[ x ] +'RequestAnimationFrame' ];
        window.cancelAnimationFrame  = window[ vendors[ x ] +'CancelAnimationFrame' ] ||
                                       window[ vendors[ x ] +'CancelRequestAnimationFrame' ];
    }

    if ( !window.requestAnimationFrame )
    {
        window.requestAnimationFrame = function(callback, element)
        {
            var currTime   = new Date().getTime();
            var timeToCall = Math.max(0, 16 - (currTime - lastTime));

            var id = window.setTimeout(function() {
                callback(currTime + timeToCall);
            }, timeToCall);

            lastTime = currTime + timeToCall;

            return id;
        }
    }

    if ( !window.cancelAnimationFrame )
    {
        window.cancelAnimationFrame = function(id) {
            clearTimeout(id);
        }
    }
}());

// require config
require.config({
    baseUrl : URL_BIN_DIR +'QUI/',
    paths : {
        "package" : URL_OPT_DIR +'bin/',
        "qui"     : URL_OPT_DIR +'bin/qui/qui',
        "locale"  : URL_VAR_DIR +'locale/bin',
        "URL_OPT_DIR" : URL_OPT_DIR,
        "URL_BIN_DIR" : URL_BIN_DIR,
    },

    waitSeconds : 0,
    locale      : USER.lang +"-"+ USER.lang,
    catchError  : true,
    urlArgs     : "d="+ QUIQQER_VERSION.replace(/\./g, '_'),

    map : {
        '*': {
            'css': URL_OPT_DIR +'bin/qui/qui/lib/css.js'
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
   'controls/workspace/Manager',
   'qui/controls/buttons/Button'
].append( QUIQQER_LOCALE || [] );


require( requireList, function()
{
    "use strict";

    var QUI       = arguments[ 0 ],
        Locale    = arguments[ 1 ],
        Ajax      = arguments[ 2 ],
        WSManager = arguments[ 3 ],
        QUIButton = arguments[ 4 ];

    Locale.setCurrent( USER.lang );

    QUI.addEvent('onError', function( err, url, line ) {
        console.error( err +' - '+ url +' - '+ line );
    });

    // load the default workspace
    var doc_size  = document.body.getSize(),
        Container = document.getElement( '.qui-workspace-container' ),
        Logo      = document.getElement( '.qui-logo-container' ),
        Menu      = document.getElement( '.qui-menu-container' );

    var logoY = Logo.getSize().y,
        menuY = Menu.getSize().y;

    Container.setStyles({
        overflow : 'hidden',
        height   : doc_size.y - logoY - menuY,
        width    : '100%'
    });

    document.id( 'wrapper' ).setStyle( 'height', '100%' );

    // load workspace
    var Workspace = new WSManager({
        autoResize : false
    }).inject( Container );

    // resizing
    window.addEvent( 'resize', function()
    {
        window.requestAnimationFrame(function(time)
        {
            Container.setStyles({
                height : document.body.getSize().y - logoY - menuY
            });

            Workspace.resize();
        });
    });





//    var MyWorkspace = new Workspace().inject( Container );
//
//    // Columns
//    var LeftColumn = new Column(),
//
//        MiddleColumn = new Column({
//            width : doc_size.x * 0.8
//        }),
//
//        RightColumn = new Column({
//            width : doc_size.x * 0.2
//        });
//
//    MyWorkspace.appendChild( LeftColumn );
//    MyWorkspace.appendChild( MiddleColumn );
//    MyWorkspace.appendChild( RightColumn );
//    MyWorkspace.fix();
//
//    LeftColumn.setAttribute( 'width', 300 );
//    LeftColumn.resize();

    // workspace button
    new QUIButton({
        icon   : 'icon-rocket',
        title  : 'Arbeitsbereich festsetzen',
        styles : {
            'float'      : 'right',
            'fontSize'   : 20,
            'fontWeight' : 'normal',
            'lineHeight' : 40
        },
        events :
        {
            onClick : function(Btn)
            {
                if ( Btn.isActive() )
                {
                    Btn.setNormal();
                } else
                {
                    Btn.setActive();
                }
            },

            onActive : function(Btn)
            {
                Workspace.unfix();
                Btn.setAttribute( 'title' , 'Arbeitsbereich ist flexibel' );
            },

            onNormal : function(Btn)
            {
                Workspace.fix();
                Btn.setAttribute( 'title' , 'Arbeitsbereich ist festgesetzt' );
            }
        }
    }).inject( document.getElement( '.qui-logo-container' ) )
      .getElm()
      .style.borderBottomLeftRadius = '40px';


//    // projects panel
//    LeftColumn.appendChild(
//        new ProjectPanel()
//    );
//
//    // bookmarks panel
//    var Bookmarks = new BookmarkPanel({
//        title  : 'Bookmarks',
//        icon   : 'icon-bookmark',
//        name   : 'qui-bookmarks',
//        events :
//        {
//            onInject : function(Panel)
//            {
//                Panel.Loader.show();
//
//                require(['Users'], function(Users)
//                {
//                    var User = Users.get( USER.id );
//
//                    User.load(function()
//                    {
//                        var data = JSON.decode( User.getAttribute( 'qui-bookmarks' ) );
//
//                        if ( !data )
//                        {
//                            Panel.Loader.hide();
//                            return;
//                        }
//
//                        Panel.unserialize( data );
//                        Panel.Loader.hide();
//                    });
//                });
//            },
//
//            onAppendChild : function(Panel, Item)
//            {
//                Panel.Loader.show();
//
//                require(['Users'], function(Users)
//                {
//                    var User = Users.get( USER.id );
//
//                    User.setAttribute( 'qui-bookmarks', JSON.encode( Panel.serialize() ) );
//
//                    User.save(function() {
//                        Panel.Loader.hide();
//                    });
//                });
//            },
//
//            onRemoveChild : function(Panel)
//            {
//                Panel.Loader.show();
//
//                require(['Users'], function(Users)
//                {
//                    var User = Users.get( USER.id );
//
//                    User.setExtra( 'qui-bookmarks', JSON.encode( Panel.serialize() ) );
//
//                    User.save(function() {
//                        Panel.Loader.hide();
//                    });
//                });
//            }
//        }
//    });
//
//    LeftColumn.appendChild( Bookmarks );
//
//    // Bookmarks.toggle();
//
//    // task panel
//    MiddleColumn.appendChild(
//        new TaskPanel({
//            title : 'My Panel 1',
//            icon  : 'icon-heart',
//            name  : 'tasks'
//        })
//    );
//
//    MiddleColumn.getChildren( 'tasks' ).appendChild(
//        new Welcome()
//    );
//
//    // resize the worksapce
//    // we have a resize bug
//    // because the scrollbar have 16 pixel
//    MyWorkspace.resize();
//
//    (function() {
//        MyWorkspace.resize();
//    }).delay( 100 );
//
//
//    var resizeWorkspaceDelay = null;
//
//    window.addEvent('resize', function()
//    {
//        // load the default workspace
//        var docSize = document.body.getSize();
//
//        Container.setStyles({
//            height : docSize.y - Logo.getSize().y - Menu.getSize().y
//        });
//
//        resizeWorkspaceDelay = (function() {
//            MyWorkspace.resize();
//        }).delay( 100 );
//    });

    /**
     * Menu
     */
    require(['Menu']);

    /**
     * UploadManager && MessageHandler
     */
//    require([
//
//        'UploadManager',
//        'qui/controls/messages/Panel',
//        'controls/desktop/panels/Help'
//
//    ], function(UploadManager, MessagePanel, Help)
//    {
//        new MessagePanel({
//            height : doc_size.y / 2
//        }).inject( RightColumn );
//
//        UploadManager.inject( RightColumn );
//
//        new Help().inject( RightColumn ).minimize();
//
//        QUI.getMessageHandler(function(MessageHandler)
//        {
//            // if 404 -> not loged in, than login pop
//            MessageHandler.addEvent('onAdd', function(MH, Message)
//            {
//                if ( Message.getAttribute( 'code' ) == 401 )
//                {
//                    require(['controls/system/Login'], function(Login) {
//                        new Login().open();
//                    });
//                }
//            });
//        });
//    });

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
        // save workspace
        Workspace.save();

        // logout
        Ajax.post('ajax_user_logout', function() {
            window.location = '/admin/';
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