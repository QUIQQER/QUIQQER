/**
 * Plugin list
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/grid/Grid
 * @requires controls/progressbar/Progressbar
 * @requires classes/system/plugins/List
 *
 * @module controls/system/plugins/List
 * @package com.pcsg.qui.js.controls.system.Manager
 * @namespace QUI.controls.system.plugins
 */

define('controls/system/plugins/List', [

    'controls/Control',
    'controls/grid/Grid',
    'controls/progressbar/Progressbar',
    'classes/system/plugins/List'

], function(Control)
{
    QUI.namespace( 'controls.system.plugins' );
    QUI.css( QUI.config('dir') +'controls/system/plugins/List.css' );

    /**
     * @class QUI.controls.system.List
     *
     * @param {QUI.classes.system.plugins.List} Control
     * @param {DomNode} DomNode                    - DOMNode were to insert the list
     * @param {MUI.Apppanel} Panel                - [optional] MUI Panel
     */
    QUI.controls.system.plugins.List = new Class({

        Implements: [Control],
        Type      : 'QUI.controls.system.plugins.List',

        initialize : function(Control, DomNode, Panel, Plugins)
        {
            this.$Control = Control;
            this.$Parent  = DomNode;
            this.$Panel   = Panel || null;
            this.$Plugins = Plugins;
            this.$Grid    = null;

            this.load();
        },

        /**
         * Load the plugin list
         *
         * @param {QUI.controls.Control} Parent
         */
        load : function()
        {
            if ( this.$Panel ) {
                this.$Panel.Loader.show();
            }

            var Body = this.$Panel.getBody();


            this.$Parent.set('html', '<div class="system-plugin-list"></div>');

            this.$Grid = new QUI.controls.grid.Grid(

                this.$Parent.getElement('.system-plugin-list'),

                {
                    width     : this.$Parent.getSize().x + 10,
                    height    : Body.getSize().y - 80,

                    accordion            : true,
                    openAccordionOnClick : false,

                    columnModel : [{
                        header    : '&nbsp;',
                        dataIndex : 'status',
                        dataType  : 'image',
                        width     : 30,
                        style     : {
                            margin : '5px 0'
                        }
                    }, {
                        header    : '&nbsp;',
                        dataIndex : 'icon',
                        dataType  : 'image',
                        width     : 30,
                        style     : {
                            margin : '5px 0'
                        }
                    }, {
                        header    : 'Plugin',
                        dataIndex : 'name',
                        dataType  : 'string',
                        width     : 150
                    }, {
                        header    : 'Beschreibung',
                        dataIndex : 'description',
                        dataType  : 'string',
                        width     : 300
                    }, {
                        header    : 'Version',
                        dataIndex : 'version',
                        dataType  : 'string',
                        width     : 80
                    }, {
                        header    : 'Author',
                        dataIndex : 'author',
                        dataType  : 'string',
                        width     : 150
                    }, {
                        header    : '&nbsp;',
                        dataIndex : 'changestatus',
                        dataType  : 'button',
                        width     : 180
                    }, {
                        header    : '&nbsp;',
                        dataIndex : 'update',
                        dataType  : 'button',
                        width     : 160
                    }],

                    buttons : [{
                        text      : 'Alle Plugins auf Updates prüfen',
                        textimage : URL_BIN_DIR +'16x16/search.png',
                        events    :
                        {
                            onClick   : function()
                            {
                               // QUI.extras.system.Update.Plugins.getAllVersions();
                            }
                        }
                    }
                ]
            });


            this.$Control.getList(function(result, Request)
            {
                var plugin;
                var list = [];

                for ( var i = 0, len = result.length; i < len; i++ )
                {
                    plugin = result[i].config;

                    plugin.types  = plugin.types;
                    plugin.active = plugin.active;
                    plugin.status = URL_BIN_DIR +'16x16/cancel.png';

                    if ( typeof plugin.icon_16x16 !== 'undefined')
                    {
                        plugin.icon = URL_OPT_DIR + plugin.icon_16x16;
                    } else
                    {
                        plugin.icon = URL_BIN_DIR +'16x16/plugins.png';
                    }

                    plugin.changestatus = {
                        name      : 'change_status',
                        text      : 'Plugin aktivieren',
                        alt       : 'Per Klick Plugin aktivieren.',
                        title     : 'Per Klick Plugin aktivieren.',
                        textimage : URL_BIN_DIR +'16x16/apply.png',
                        plugin    : result[i].name,
                        events    : {
                            onClick : this.$clickButtonActivate.bind( this )
                        },
                        styles : {
                            width : 165
                        }
                    };


                    if ( plugin.active )
                    {
                        plugin.status = URL_BIN_DIR +'16x16/apply.png';

                        plugin.changestatus = {
                            name      : 'change_status',
                            text      : 'Plugin deaktivieren',
                            alt       : 'Per Klick Plugin deaktivieren.',
                            title     : 'Per Klick Plugin deaktivieren.',
                            textimage : URL_BIN_DIR +'16x16/cancel.png',
                            plugin    : result[i].name,
                            events    : {
                                onClick : this.$clickButtonDeactivate.bind( this )
                            },
                            styles : {
                                width : 165
                            }
                        };
                    }

                    plugin.update = {
                        name      : 'update_check',
                        text      : 'Updates suchen',
                        textimage : URL_BIN_DIR +'16x16/search.png',
                        plugin    : result[i].name,
                        events    :
                        {
                            onClick : this.$clickSearchUpdates.bind( this )
                        }
                    };


                    list.push( plugin );
                }

                this.$Grid.setData({
                    data : list
                });

                if ( this.$Panel ) {
                    this.$Panel.Loader.hide();
                }

            }.bind( this ));
        },

        /**
         * The deactivate button click
         *
         * @param {QUI.controls.buttons.Button} Btn
         */
        $clickButtonDeactivate : function(Btn)
        {
            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

            this.$Plugins.$Control.deactivate(
                Btn.getAttribute('plugin'),

                function(result, Ajax) {
                    this.load();
                }.bind( this )
            );
        },

        /**
         * The activate button click
         *
         * @param {QUI.controls.buttons.Button} Btn
         */
        $clickButtonActivate : function(Btn)
        {
            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

            this.$Plugins.$Control.activate(
                Btn.getAttribute('plugin'),

                function(result, Ajax)
                {
                    this.load();
                }.bind( this )
            );
        },

        /**
         * Opens the Settings if the Plugin have settings
         *
         * @param {QUI.controls.buttons.Button} Btn
         */
        $openSettings : function(Btn)
        {
            QUI.Plugins.Settings.open(
                Btn.getAttribute('plugin')
            );
        },

        /**
         * Searches for updates and list it
         *
         * @param {QUI.controls.buttons.Button} Btn
         */
        $clickSearchUpdates : function(Btn)
        {
            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');
            Btn.setAttribute('Control', this);

            this.$Plugins.$Control.getVersions(
                Btn.getAttribute('plugin'),

                function(result, Request)
                {
                    if ( !result.length )
                    {
                        QUI.MH.addInformation(
                            'Es wurden keine Updates für das Plugin '+ Request.getAttribute('plugin') +' gefunden'
                        );

                        return;
                    }

                    var i, len, Elm, Section;

                    var row     = Btn.getAttribute('data').row,
                        List    = Btn.getAttribute('data').List,
                        Control = Btn.getAttribute('Control');

                    List.accordianOpen( List.elements[ row ] );
                    Section = List.getSection( row );

                    // console.log( Section );
                    for ( i = 0, len = result.length; i < len; i++ )
                    {
                        Elm = new Element('div.project-version', {
                            html : '<span class="version">'+ result[i].version +'</span>'
                        }).inject( Section );


                        new QUI.controls.buttons.Button({
                            textimage : URL_BIN_DIR +'16x16/down.png',
                            text      : 'download',
                            alt       : 'Update herrunter laden',
                            title     : 'Update herrunter laden',
                            events    : {
                                onClick : Control.$download.bind( Control )
                            }
                        }).inject( Elm );

                        if ( result[i].downloaded )
                        {
                            new QUI.controls.buttons.Button({
                                textimage : URL_BIN_DIR +'16x16/install.png',
                                text      : 'install',
                                alt       : 'Update installieren',
                                title     : 'Update installieren',
                                events    : {
                                    onClick : Control.$install.bind( Control )
                                }
                            }).inject( Elm );
                        }
                    }

                    Btn.setAttribute('textimage', URL_BIN_DIR +'16x16/search.png');

                }.bind( Btn )
            );
        },

        /**
         * Download an update for a plugin
         */
        $download : function()
        {

        },

        /**
         * install the update
         */
        $install : function()
        {

        }
    });

    return QUI.controls.system.plugins.List;
});