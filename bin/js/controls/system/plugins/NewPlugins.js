/**
 * Plugin list
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/grid/Grid
 * @requires controls/progressbar/Progressbar
 * @requires classes/system/plugins/NewPlugins
 *
 * @module controls/system/plugins/NewPlugins
 * @package com.pcsg.qui.js.controls.system.Manager
 * @namespace QUI.controls.system.plugins
 */

define('controls/system/plugins/NewPlugins', [

    'controls/Control',
    'controls/grid/Grid',
    'controls/progressbar/Progressbar',
    'classes/system/plugins/NewPlugins'

], function(Control)
{
    QUI.namespace( 'controls.system.plugins' );

    /**
     * @class QUI.controls.system.plugins.NewPlugins
     *
     * @param {QUI.classes.system.plugins.NewPlugins} Control
     * @param {DomNode} DomNode    - DOMNode were to insert the list
     * @param {MUI.Apppanel} Panel - [optional] MUI Panel
     */
    QUI.controls.system.plugins.NewPlugins = new Class({

        Implements: [Control],
        Type      : 'QUI.controls.system.plugins.NewPlugins',

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
         * Load the plugin list of new plugins
         */
        load : function()
        {
            if ( this.$Panel ) {
                this.$Panel.Loader.show();
            }

            var Body = this.$Panel.getBody();


            this.$Parent.set('html', '<div class="system-plugin-new"></div>');

            this.$Grid = new QUI.controls.grid.Grid(

                this.$Parent.getElement('.system-plugin-new'),

                {
                    width  : this.$Parent.getSize().x + 10,
                    height : Body.getSize().y - 80,

                    columnModel : [{
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
                        dataIndex : 'update',
                        dataType  : 'button',
                        width     : 160
                    }]
                }
            );

            this.$Control.getList(function(result, Request)
            {
                var data = [];

                if ( !result.length )
                {
                    data.push({
                        name        : '---',
                        description : 'Keine Plugins gefunden'
                    });
                }

                for ( var i = 0, len = result.length; i < len; i++ )
                {

                }


                this.$Grid.setData({
                    data : data
                });

                if ( this.$Panel ) {
                    this.$Panel.Loader.hide();
                }

            }.bind( this ));
        }
    });

    return QUI.controls.system.plugins.NewPlugins;
});