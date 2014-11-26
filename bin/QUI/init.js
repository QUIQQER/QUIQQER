
/**
 * The main loading script for the quiqqer administration
 *
 * @author www.pcsg.de (Henning Leutz)
 */

//monitorEvents(document.body,'click');
//monitorEvents(document.body,'mousedown');
//monitorEvents(document.body,'dblclick');

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
    "use strict";

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
        window.requestAnimationFrame = function(callback)
        {
            var currTime   = new Date().getTime();
            var timeToCall = Math.max(0, 16 - (currTime - lastTime));

            var id = window.setTimeout(function() {
                callback(currTime + timeToCall);
            }, timeToCall);

            lastTime = currTime + timeToCall;

            return id;
        };
    }

    if ( !window.cancelAnimationFrame )
    {
        window.cancelAnimationFrame = function(id) {
            clearTimeout(id);
        };
    }
}());

// require config
require.config({
    baseUrl : URL_BIN_DIR +'QUI/',
    paths : {
        "package" : URL_OPT_DIR,
        "qui"     : URL_OPT_DIR +'bin/qui/qui',
        "locale"  : URL_VAR_DIR +'locale/bin',
        "URL_OPT_DIR" : URL_OPT_DIR,
        "URL_BIN_DIR" : URL_BIN_DIR
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
   'Projects',
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
        Projects  = arguments[ 3 ],
        WSManager = arguments[ 4 ],
        QUIButton = arguments[ 5 ],

        QUIContextmenuItem      = arguments[ 6 ],
        QUIContextmenuSeperator = arguments[ 7 ];

    Locale.setCurrent( USER.lang );

    QUI.addEvent('onError', function( err, url, line )
    {
        console.error( err +' - '+ url +' - '+ line );

        if ( typeof Error !== 'undefined' ) {
            console.warn( new Error().stack );
        }
    });

    // load the default workspace
    var doc_size  = document.body.getSize(),
        Container = document.getElement( '.qui-workspace-container' ),
        Menu      = document.getElement( '.qui-menu-container' );

    var menuY = Menu.getComputedSize().height;

    Container.setStyles({
        overflow : 'hidden',
        height   : doc_size.y - menuY,
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
                        Bar  = Menu.getChildren();

                    // logo
                    if ( Bar.getChildren( 'quiqqer' ) )
                    {
                        var Quiqqer = Bar.getChildren( 'quiqqer' );

                        if ( Quiqqer )
                        {
                            var Img = Quiqqer.getElm().getElement( 'img' );

                            Img.setStyles({
                                height   : 22,
                                position : 'relative',
                                top      : 6
                            });
                        }
                    }


                    if ( !Bar.getChildren( 'profile' ) ) {
                        return;
                    }

                    if ( !Bar.getChildren( 'profile' ).getChildren( 'workspaces' ) ) {
                        return;
                    }

                    var Workspaces = Bar.getChildren( 'profile' )
                                        .getChildren( 'workspaces' );

                    Workspaces.clear();

                    Workspaces.appendChild(
                        new QUIContextmenuItem({
                            text   : 'Arbeitsbereiche bearbeiten',
                            icon   : 'icon-edit',
                            events :
                            {
                                onClick : function() {
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
                                onClick : function() {
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
                        var standard = false;

                        if ( "standard" in Entry && Entry.standard && ( Entry.standard ).toInt() ) {
                            standard = true;
                        }

                        Workspaces.appendChild(
                            new QUIContextmenuItem({
                                text   : Entry.title,
                                wid    : Entry.id,
                                icon   : standard ? 'icon-laptop' : false,
                                events :
                                {
                                    onClick : function(Item) {
                                        WS.loadWorkspace( Item.getAttribute( 'wid' ) );
                                    }
                                }
                            })
                        );
                    });
                };

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
        window.requestAnimationFrame(function()
        {
            Container.setStyles({
                height : document.body.getSize().y - logoY - menuY
            });

            Workspace.resize();
        });
    });


    /**
     * Menu
     */
    require(['Menu'], function(QuiMenu)
    {
        // workspace edit
        new Element('div', {
            'class' : 'qui-contextmenu-baritem smooth ',
            html    : '<span class="qui-contextmenu-baritem-text">'+
                          '<span class="icon-stack">'+
                              '<i class="icon-laptop icon-stack-base"></i>'+
                              '<i class="icon-pencil" style="font-size: 0.8em; margin: -3px 0 0 1px;"></i>'+
                          '</span>'+
                      '</span>',
            title   : 'Arbeitsbereich ist festgesetzt',
            styles  : {
                'borderLeft' : '1px solid #d1d4da',
                'float'      : 'right',
                'marginLeft' : 5
            },
            events :
            {
                click : function()
                {
                    if ( this.hasClass( 'qui-contextmenu-baritem-active' ) )
                    {
                        this.removeClass( 'qui-contextmenu-baritem-active' );

                        Workspace.fix();
                        this.set( 'title' , 'Arbeitsbereich ist festgesetzt' );

                        return;
                    }

                    this.addClass( 'qui-contextmenu-baritem-active' );

                    Workspace.unfix();
                    this.set( 'title' , 'Arbeitsbereich ist flexibel' );
                }
            }
        }).inject( Menu );

        // logout
        new Element('div', {
            'class' : 'qui-contextmenu-baritem smooth ',
            html    : '<span class="qui-contextmenu-baritem-text">Abmelden</span>',
            title   : 'Angemeldet als: '+ USER.name,
            styles  : {
                'float' : 'right'
            },
            events  : {
                click : window.logout
            }
        }).inject( Menu );
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


    window.onbeforeunload = function()
    {
        Workspace.save();

        return "Bitte melden Sie sich vor dem schließen der Administration ab." +
               "Ansonsten können bestehende Daten verloren gehen." +
               "Möchten Sie trotzdem weiter fortfahren?";
    };

    // logout function
    window.logout = function()
    {
        Workspace.Loader.show();

        // save workspace
        Workspace.save(true, function()
        {
            // logout
            Ajax.post('ajax_user_logout', function()
            {
                window.onbeforeunload = null;
                window.location = '/admin/';
            });
        });
    };

});
