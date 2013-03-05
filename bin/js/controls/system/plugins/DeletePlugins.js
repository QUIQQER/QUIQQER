/**
 * List of plugins which could be deleted
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/grid/Grid
 * @requires controls/progressbar/Progressbar
 * @requires classes/system/plugins/DeletePlugins
 *
 * @module controls/system/plugins/DeletePlugins
 * @package com.pcsg.qui.js.controls.system.Manager
 * @namespace QUI.controls.system.plugins
 */

define('controls/system/plugins/DeletePlugins', [

    'controls/Control',
    'controls/grid/Grid',
    'controls/progressbar/Progressbar',
    'classes/system/plugins/DeletePlugins'

], function(Control)
{
    QUI.namespace( 'controls.system.plugins' );

    /**
     * @class QUI.controls.system.plugins.DeletePlugins
     *
     * @param {QUI.classes.system.plugins.DeletePlugins} Control
     * @param {DomNode} DomNode    - DOMNode were to insert the list
     * @param {MUI.Apppanel} Panel - [optional] MUI Panel
     */
    QUI.controls.system.plugins.DeletePlugins = new Class({

        Implements: [Control],
        Type      : 'QUI.controls.system.plugins.DeletePlugins',

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
         */
        load : function()
        {
            if ( this.$Panel ) {
                this.$Panel.Loader.show();
            }

            var Body = this.$Panel.getBody();


            this.$Parent.set('html', '<div class="system-plugin-delete"></div>');

            this.$Grid = new QUI.controls.grid.Grid(

                this.$Parent.getElement('.system-plugin-delete'),

                {
                    width  : this.$Parent.getSize().x + 10,
                    height : Body.getSize().y - 80,

                    columnModel : [{
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
                        dataIndex : 'delete',
                        dataType  : 'button',
                        width     : 160
                    }]
                }
            );

            this.$Control.getList(function(result, Request)
            {
                var i, len, plugin;
                var data = [];

                if ( !result.length )
                {
                    data.push({
                        name        : '---',
                        description : 'Keine Plugins gefunden'
                    });
                }

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    plugin = result[ i ].config;

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

                    plugin['delete'] = {
                        name      : 'plugin_delete',
                        text      : 'Plugin löschen',
                        textimage : URL_BIN_DIR +'16x16/trashcan_empty.png',
                        plugin    : result[i].name,
                        events    :
                        {
                            onClick : this.$clickDelete.bind( this )
                        }
                    };

                    data.push( plugin );
                }


                this.$Grid.setData({
                    data : data
                });

                if ( this.$Panel ) {
                    this.$Panel.Loader.hide();
                }

            }.bind( this ));
        },

        /**
         *
         */
        $clickDelete : function()
        {

        }
    });

    return QUI.controls.system.plugins.DeletePlugins;
});