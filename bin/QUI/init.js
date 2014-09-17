
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
   'qui/controls/buttons/Button',
   'qui/controls/contextmenu/Item',
   'qui/controls/contextmenu/Seperator'
].append( QUIQQER_LOCALE || [] );


require( requireList, function()
{
    "use strict";

    var QUI       = arguments[ 0 ],
        Locale    = arguments[ 1 ],
        Ajax      = arguments[ 2 ],
        WSManager = arguments[ 3 ],
        QUIButton = arguments[ 4 ],

        QUIContextmenuItem      = arguments[ 5 ],
        QUIContextmenuSeperator = arguments[ 6 ];


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

    /**
     * Workspace
     */
    var Workspace = new WSManager({
        autoResize : false,
        events     :
        {
            onLoadWorkspace : function(WS) {
                WS.load();
            },

            onWorkspaceLoaded : function(WS)
            {
                var createMenu = function(Menu)
                {
                    var list = WS.getList(),
                        Bar  = Menu.getChildren(),

                        Workspaces = Bar.getChildren( 'profile' )
                                        .getChildren( 'workspaces' );

                    Workspaces.clear();

                    Workspaces.appendChild(
                        new QUIContextmenuItem({
                            text   : 'Arbeitsbereiche bearbeiten',
                            icon   : 'icon-edit',
                            events :
                            {
                                onClick : function(Item) {
                                    WS.openWorkspaceEdit();
                                }
                            }
                        })
                    );

                    Workspaces.appendChild(
                        new QUIContextmenuItem({
                            text   : 'Arbeitsbereich erstellen',
                            icon   : 'icon-plus',
                            events :
                            {
                                onClick : function(Item) {
                                    WS.openCreateWindow();
                                }
                            }
                        })
                    );

                    Workspaces.appendChild(
                        new QUIContextmenuSeperator({})
                    );

                    Object.each(list, function(Entry)
                    {
                        Workspaces.appendChild(
                            new QUIContextmenuItem({
                                text   : Entry.title,
                                wid    : Entry.id,
                                icon   : ( Entry.standard ).toInt() ? 'icon-laptop' : false,
                                events :
                                {
                                    onClick : function(Item) {
                                        WS.loadWorkspace( Item.getAttribute( 'wid' ) );
                                    }
                                }
                            })
                        );
                    });
                }

                require(['Menu'], function(Menu)
                {
                    if ( !Menu.isLoaded() )
                    {
                        Menu.addEvent('onMenuLoaded', function() {
                            createMenu( Menu );
                        });

                        return;
                    }

                    createMenu( Menu );
                });
            }
        }
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

    /**
     * Menu
     */
    require(['Menu']);

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

});
