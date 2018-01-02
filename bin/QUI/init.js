/**
 * The main loading script for the quiqqer administration
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * globale events
 * window -> onLogin
 */

// extend mootools with desktop drag drop
Object.append(Element.NativeEvents, {
    dragenter: 2,
    dragleave: 2,
    dragover : 2,
    dragend  : 2,
    drop     : 2
});

// custome select
// eq: getElements( 'input:display(inline)' )
Slick.definePseudo('display', function (value) {
    "use strict";
    return Element.getStyle(this, 'display') == value;
});

// IE Flickering Bug
try {
    document.execCommand("BackgroundImageCache", false, true);
} catch (err) {
    // Nothing to do
}

// require config
require.config({
    baseUrl: URL_BIN_DIR + 'QUI/',
    paths  : {
        "package"    : URL_OPT_DIR,
        "qui"        : URL_OPT_DIR + 'bin/qui/qui',
        "locale"     : URL_VAR_DIR + 'locale/bin',
        "URL_OPT_DIR": URL_OPT_DIR,
        "URL_BIN_DIR": URL_BIN_DIR,
        "Mustache"   : URL_OPT_DIR + 'bin/mustache/mustache.min',

        "URI"               : URL_OPT_DIR + 'bin/urijs/src/URI',
        'IPv6'              : URL_OPT_DIR + 'bin/urijs/src/IPv6',
        'punycode'          : URL_OPT_DIR + 'bin/urijs/src/punycode',
        'SecondLevelDomains': URL_OPT_DIR + 'bin/urijs/src/SecondLevelDomains'
    },

    waitSeconds: 0,
    locale     : USER.lang + "-" + USER.lang,
    catchError : true,
    urlArgs    : "d=" + QUIQQER_VERSION.replace(/\./g, '_') + '_' + QUIQQER.lu,

    map: {
        '*': {
            'css'  : URL_OPT_DIR + 'bin/qui/qui/lib/css.js',
            'image': URL_OPT_DIR + 'bin/qui/qui/lib/image.js',
            'text' : URL_OPT_DIR + 'bin/qui/qui/lib/text.js'
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
    'qui/controls/contextmenu/Separator'
].append(QUIQQER_LOCALE || []);

if (typeof window.Intl === "undefined") {
    console.error("Intl is not supported");
}

require(requireList, function () {
    "use strict";

    var QUI                     = arguments[0],
        Locale                  = arguments[1],
        Ajax                    = arguments[2],
        Projects                = arguments[3],
        WSManager               = arguments[4],
        QUIButton               = arguments[5],

        QUIContextmenuItem      = arguments[6],
        QUIContextmenuSeparator = arguments[7];

    Locale.setCurrent(USER.lang);

    QUI.setAttributes({
        'control-loader-type' : 'line-scale',
        'control-loader-color': '#2f8fc8',

        'control-desktop-panel-sheet-closetext': Locale.get('quiqqer/system', 'close'),
        'control-windows-popup-closetext'      : Locale.get('quiqqer/system', 'close'),
        'control-windows-confirm-canceltext'   : Locale.get('quiqqer/system', 'cancel'),
        'control-windows-confirm-submittext'   : Locale.get('quiqqer/system', 'accept'),
        'control-windows-prompt-canceltext'    : Locale.get('quiqqer/system', 'cancel'),
        'control-windows-prompt-submittext'    : Locale.get('quiqqer/system', 'accept'),
        'control-windows-submit-canceltext'    : Locale.get('quiqqer/system', 'cancel'),
        'control-windows-submit-submittext'    : Locale.get('quiqqer/system', 'accept'),

        'control-task-panel-limit'        : 50,
        'control-task-panel-limit-message': Locale.get('quiqqer/quiqqer', 'message.to.much.tasks')
    });

    QUI.addEvent('onError', function (err, url, line) {
        console.error(err + ' - ' + url + ' - ' + line);

        if (parseInt(QUIQQER_CONFIG.globals.development) &&
            typeof Error !== 'undefined') {
            console.warn(new Error().stack);
        }
    });

    if ("gui" in QUIQQER_CONFIG && QUIQQER_CONFIG.gui && QUIQQER_CONFIG.gui.panelTaskLimit) {
        QUI.setAttribute('control-task-panel-limit', parseInt(QUIQQER_CONFIG.gui.panelTaskLimit));
    }

    QUI.getMessageHandler(function (MH) {
        if (!("gui" in QUIQQER_CONFIG) || !QUIQQER_CONFIG.gui) {
            return;
        }

        if (!("displayTimeMessages" in QUIQQER_CONFIG.gui) || !QUIQQER_CONFIG.gui.displayTimeMessages) {
            return;
        }

        MH.setAttribute(
            'displayTimeMessages',
            QUIQQER_CONFIG.gui.displayTimeMessages
        );
    });

    var menuLoaded             = false,
        workspaceLoaded        = false,
        quiqqerLoadedTriggered = false;

    var quiqqerIsLoaded = function () {
        if (quiqqerLoadedTriggered) {
            return;
        }

        if (menuLoaded && workspaceLoaded) {
            quiqqerLoadedTriggered = true;
            QUI.fireEvent('quiqqerLoaded');
            window.fireEvent('quiqqerLoaded');
        }
    };

    window.addEvent('load', quiqqerIsLoaded);

    Ajax.get('ajax_isAuth', function (userId) {

        if (!userId) {
            window.location.reload();
            return;
        }

        // load the default workspace
        var doc_size  = document.body.getSize(),
            Container = document.getElement('.qui-workspace-container'),
            Menu      = document.getElement('.qui-menu-container');

        var menuY = Menu.getComputedSize().height;

        Container.setStyles({
            overflow: 'hidden',
            height  : doc_size.y - menuY,
            width   : '100%'
        });

        document.id('wrapper').setStyles({
            height  : '100%',
            overflow: 'hidden',
            width   : '100%'
        });

        /**
         * Workspace
         */
        var Workspace = new WSManager({
            autoResize: false,
            events    : {
                onLoadWorkspace: function (WS) {
                    WS.load();
                },

                onWorkspaceLoaded: function (WS) {
                    var createMenu = function (Menu) {
                        var list = WS.getList(),
                            Bar  = Menu.getChildren();

                        // logo
                        if (Bar.getChildren('quiqqer')) {
                            var Quiqqer = Bar.getChildren('quiqqer');

                            if (Quiqqer) {
                                var Img = Quiqqer.getElm().getElement('img');

                                if (Img) {
                                    Img.setStyles({
                                        height  : 22,
                                        position: 'relative',
                                        top     : 6
                                    });
                                }
                            }
                        }

                        workspaceLoaded = true;
                        WS.Loader.hide();
                        quiqqerIsLoaded();

                        if (!Bar.getChildren('profile')) {
                            return;
                        }

                        if (!Bar.getChildren('profile').getChildren('workspaces')) {
                            return;
                        }

                        var Workspaces = Bar.getChildren('profile')
                            .getChildren('workspaces');

                        Workspaces.clear();

                        Object.each(list, function (Entry) {
                            var standard = false;

                            if ("standard" in Entry && Entry.standard &&
                                (Entry.standard).toInt()) {
                                standard = true;
                            }

                            Workspaces.appendChild(
                                new QUIContextmenuItem({
                                    text  : Entry.title,
                                    wid   : Entry.id,
                                    icon  : standard ? 'fa fa-check' : 'fa fa-minus',
                                    events: {
                                        onClick: function (Item) {
                                            WS.loadWorkspace(Item.getAttribute('wid'));
                                        }
                                    }
                                })
                            );
                        });


                        Workspaces.appendChild(
                            new QUIContextmenuSeparator({})
                        );

                        Workspaces.appendChild(
                            new QUIContextmenuItem({
                                text  : Locale.get('quiqqer/quiqqer', 'menu.workspaces.edit'),
                                icon  : 'fa fa-edit',
                                events: {
                                    onClick: function () {
                                        WS.openWorkspaceEdit();
                                    }
                                }
                            })
                        );
                    };

                    require(['Menu'], function (Menu) {
                        if (!Menu.isLoaded()) {
                            Menu.addEvent('onMenuLoaded', function () {
                                menuLoaded = true;
                                createMenu(Menu);
                            });

                            return;
                        }

                        menuLoaded = true;
                        createMenu(Menu);
                    });

                    quiqqerIsLoaded();
                }
            }
        }).inject(Container);

        // resizing
        QUI.addEvent('resize', function () {

            Container.setStyles({
                height: QUI.getWindowSize().y - menuY,
                width : QUI.getWindowSize().x
            });

            Workspace.resize();
        });

        /**
         * Menu
         */
        require(['Menu']);

        /**
         * If files were droped to quiqqer
         * dont show it
         */
        document.id(document.body).addEvents({
            drop: function (event) {
                event.preventDefault();
            },

            dragend: function (event) {
                event.preventDefault();
            },

            dragover: function (event) {
                event.preventDefault();
            }
        });


        window.onbeforeunload = function () {
            Workspace.save();

            return Locale.get('quiqqer/quiqqer', 'log.out.message');
        };

        // logout function
        window.logout = function () {
            Workspace.Loader.show();

            // save workspace
            Workspace.save(true, function () {
                // logout
                Ajax.post('ajax_user_logout', function () {
                    window.onbeforeunload = null;
                    window.location = URL_DIR + 'admin/';
                });
            });
        };

    });
});
