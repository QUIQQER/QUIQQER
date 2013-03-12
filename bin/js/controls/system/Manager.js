/**
 * System Manager
 * Manage Systemcheck, Updates, Settings, Plugins, Packages
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Settings
 *
 * @module controls/system/Manager
 * @package com.pcsg.qui.js.controls.system.Manager
 * @namespace QUI.controls.system
 */

define('controls/system/Manager', [

    'controls/Control',
    'controls/Settings',

    'css!controls/system/Manager.css'

], function(Control, Settings)
{
    QUI.namespace('controls.system');

    /**
     * @class QUI.controls.system.Manager
     */
    QUI.controls.system.Manager = new Class({

        Implements: [Control],
        Type      : 'QUI.controls.system.Manager',

        create : function()
        {
            this.$Settings = new QUI.controls.Settings({
                name   : 'update',
                title  : 'System Verwaltung',
                icon   : URL_BIN_DIR +'16x16/system_update.png',
                onopen : function(Win)
                {
                    //Win.Loader.show();
                },
                submit : false,
                events :
                {
                    onInit : function()
                    {
                        this.appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Systemcheck',
                                text   : 'Systemcheck',
                                image  : URL_BIN_DIR +'22x22/system.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Panel)
                                {
                                    Panel.Loader.show();

                                    require([
                                        'classes/system/Check'
                                    ], function(Check)
                                    {
                                        new Check().openInPanel( this );
                                    }.bind( Panel ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'System aktualisieren',
                                text   : 'System aktualisieren',
                                image  : URL_BIN_DIR +'22x22/system.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Panel)
                                {
                                    Panel.Loader.show();

                                    require([
                                        'classes/system/Update'
                                    ], function(Update)
                                    {
                                        new Update().openInPanel( this );
                                    }.bind( Panel ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Plugins verwalten und aktualisieren',
                                alt    : 'Plugins verwalten und aktualisieren',
                                text   : 'Plugins',
                                image  : URL_BIN_DIR +'22x22/configure.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Panel)
                                {
                                    Panel.Loader.show();

                                    require([
                                        'classes/system/Plugins'
                                    ], function(Plugins)
                                    {
                                        new Plugins().openInPanel( this );
                                    }.bind( Panel ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Packete / Systemerweiterungen',
                                text   : 'Packete / Systemerweiterungen',
                                image  : URL_BIN_DIR +'22x22/packages.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Plugins)
                                {
                                    Plugins.Loader.show();

                                    require([
                                        'classes/system/Packages'
                                    ], function(Packages)
                                    {
                                        new Packages().openInPanel( this );
                                    }.bind( Plugins ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Cache',
                                text   : 'Cache',
                                image  : URL_BIN_DIR +'22x22/cache.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Plugins)
                                {
                                    Plugins.Loader.show();

                                    require([
                                        'classes/system/Cache'
                                    ], function(Cache)
                                    {
                                        new Cache().openInPanel( this );
                                    }.bind( Plugins ));
                                }
                            })
                        );


                        /*
                        .appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Neue Plugins installieren',
                                text   : 'Neue Plugins installieren',
                                image  : URL_BIN_DIR +'22x22/plugins.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Win)
                                {
                                    Win.Loader.show();

                                    require(['classes/system/NewPlugins'], function(NewPlugins) {
                                        NewPlugins.load( Win );
                                    }.bind( Win ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Plugins löschen',
                                text   : 'Plugins löschen',
                                image  : URL_BIN_DIR +'22x22/trashcan_empty.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Win)
                                {
                                    Win.Loader.show();

                                    require(['classes/system/DeletePlugins'], function(DeletePlugins) {
                                        DeletePlugins.load( Win );
                                    }.bind( Win ));
                                }
                            })
                        );
                        */

                        this.firstChild().click();
                    }
                }
            });
        }
    });

    return QUI.controls.system.Manager;
});