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
        "Mustache"   : URL_OPT_DIR + 'bin/quiqqer-asset/mustache/mustache/mustache.min',

        "URI"               : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/URI',
        'IPv6'              : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/IPv6',
        'punycode'          : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/punycode',
        'SecondLevelDomains': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/SecondLevelDomains',
        'Navigo'            : URL_OPT_DIR + 'bin/quiqqer-asset/navigo/navigo/lib/navigo.min',
        'HistoryEvents'     : URL_OPT_DIR + 'bin/quiqqer-asset/history-events/history-events/dist/history-events.min',
        '@popperjs/core'    : URL_OPT_DIR + 'quiqqer/quiqqer/bin/QUI/lib/tippy/popper.min'
    },

    waitSeconds: 0,
    locale     : USER.lang + "-" + USER.lang,
    catchError : true,
    urlArgs    : "d=" + QUIQQER_VERSION.replace(/\./g, '_') + '_' + QUIQQER.lu,

    map: {
        '*': {
            'css'  : URL_OPT_DIR + 'bin/qui/qui/lib/css.min.js',
            'image': URL_OPT_DIR + 'bin/qui/qui/lib/image.min.js',
            'text' : URL_OPT_DIR + 'bin/qui/qui/lib/text.min.js'
        }
    }
});


/**
 * Init quiqqer
 */

// main require list + locale translations
let requireList = [
    'qui/QUI',
    'Locale',
    'Ajax',
    'Projects',
    'controls/workspace/Manager',
    'qui/controls/buttons/Button',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Separator'
].append(window.QUIQQER_LOCALE || []);

if (typeof window.Intl === "undefined") {
    console.error("Intl is not supported");
}

require(requireList, function () {
    "use strict";

    let QUI                     = arguments[0],
        Locale                  = arguments[1],
        Ajax                    = arguments[2],
        Projects                = arguments[3],
        WSManager               = arguments[4],
        QUIButton               = arguments[5],

        QUIContextmenuItem      = arguments[6],
        QUIContextmenuSeparator = arguments[7];

    Locale.setCurrent(window.USER.lang);

    // workaround, because the QUI framework has sometimes its own Locale :-/
    require(['qui/Locale'], function (QUIsOwnLocale) {
        QUIsOwnLocale.setCurrent(window.USER.lang);
    });

    require(['InstallationWizard'], function (InstallationWizard) {
        InstallationWizard.load();
    });

    QUI.setAttributes({
        'control-loader-type' : 'line-scale',
        'control-loader-color': '#2f8fc8',

        'control-desktop-panel-sheet-closetext': Locale.get('quiqqer/quiqqer', 'close'),
        'control-windows-popup-closetext'      : Locale.get('quiqqer/quiqqer', 'close'),
        'control-windows-confirm-canceltext'   : Locale.get('quiqqer/quiqqer', 'cancel'),
        'control-windows-confirm-submittext'   : Locale.get('quiqqer/quiqqer', 'accept'),
        'control-windows-prompt-canceltext'    : Locale.get('quiqqer/quiqqer', 'cancel'),
        'control-windows-prompt-submittext'    : Locale.get('quiqqer/quiqqer', 'accept'),
        'control-windows-submit-canceltext'    : Locale.get('quiqqer/quiqqer', 'cancel'),
        'control-windows-submit-submittext'    : Locale.get('quiqqer/quiqqer', 'accept'),

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

    // taskbar tooltips
    require([
        URL_OPT_DIR + 'quiqqer/quiqqer/bin/QUI/lib/tippy/tippy.min.js',
        'css!' + URL_OPT_DIR + 'quiqqer/quiqqer/bin/QUI/lib/tippy/tippy.css'
    ], function (tippy) {
        QUI.addEvent('onParseBegin', function (QUI, Parent) {
            // parse title
            if (!Parent) {
                return;
            }

            // workaround
            if (typeof Parent.length !== 'undefined') {
                return;
            }

            let titleElms = Parent.querySelectorAll('[title]');

            titleElms = Array.from(titleElms);
            titleElms = titleElms.filter(function (node) {
                return node.get('title') !== '';
            });

            tippy(titleElms, {
                animateFill: false,
                animation  : 'shift-away',
                allowHTML  : true,
                content    : function (reference) {
                    const title = reference.getAttribute('title');
                    reference.removeAttribute('title');

                    return title;
                }
            });
        });

        QUI.addEvent('onQuiTaskBarTaskCreate', function (Instance) {
            if (typeof tippy === 'undefined') {
                return;
            }

            const Node = Instance.getElm();

            tippy(Node, {
                animateFill: false,
                animation  : 'shift-away',
                content    : '<span class="fa fa-circle-o-notch fa-spin"></span>',
                allowHTML  : true,
                onShow     : function (TippyInstance) {
                    Instance.getToolTipText().then(function (text) {
                        if (!text) {
                            TippyInstance.setContent(
                                Instance.getInstance().getAttribute('title')
                            );

                            return;
                        }

                        TippyInstance.setContent(text);
                    });
                }
            });
        });
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

    let menuLoaded             = false,
        workspaceLoaded        = false,
        quiqqerLoadedTriggered = false;

    const quiqqerIsLoaded = function () {
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
        let doc_size      = document.body.getSize(),
            Container     = document.getElement('.qui-workspace-container'),
            MenuContainer = document.getElement('.qui-menu-container');

        let menuY = MenuContainer.getComputedSize().height;

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
        const Workspace = new WSManager({
            autoResize: false,
            events    : {
                onLoadWorkspace: function (WS) {
                    WS.load();
                },

                onWorkspaceLoaded: function (WS) {
                    const createMenu = function (Menu) {
                        const list = WS.getList(),
                              Bar  = Menu.getChildren();

                        // logo
                        if (Bar.getChildren('quiqqer')) {
                            const Quiqqer = Bar.getChildren('quiqqer');

                            if (Quiqqer) {
                                const Img = Quiqqer.getElm().getElement('img');

                                if (Img) {
                                    Img.setStyles({
                                        height  : 22,
                                        position: 'relative',
                                        top     : 6,
                                        width   : null
                                    });
                                }
                            }
                        }

                        workspaceLoaded = true;
                        WS.Loader.hide();
                        quiqqerIsLoaded();

                        // message
                        require(['controls/messages/Button'], function (MessageButton) {
                            let Profile = MenuContainer.getElement('.qui-profile-button');

                            if (Profile) {
                                new MessageButton().inject(Profile, 'after');
                            } else {
                                new MessageButton().inject(MenuContainer);
                            }
                        });

                        if (!Bar.getChildren('profile')) {
                            return;
                        }

                        if (!Bar.getChildren('profile').getChildren('workspaces')) {
                            return;
                        }

                        const Workspaces = Bar.getChildren('profile')
                                              .getChildren('workspaces');

                        Workspaces.clear();

                        Object.each(list, function (Entry) {
                            let standard = false;

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
        require(['libs/HistoryTabs']);

        /**
         * If files were dropped to quiqqer
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
