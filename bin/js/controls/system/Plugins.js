/**
 * Plugin Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires classes/system/Plugins
 * @requires controls/toolbar/Bar
 * @requires controls/toolbar/Tab
 *
 * @module controls/system/Plugins
 * @package com.pcsg.qui.js.controls.system.Manager
 * @namespace QUI.controls.system
 */

define('controls/system/Plugins', [

    'controls/Control',
    'classes/system/Plugins'

], function(Control)
{
    QUI.namespace('controls.system');

    /**
     * @class QUI.controls.system.Plugins
     */
    QUI.controls.system.Plugins = new Class({

        Implements: [Control],
        Type      : 'QUI.controls.system.Plugins',

        initialize : function(Control, Panel)
        {
            this.$Control = Control;
            this.$Panel   = Panel;

            this.load();
        },

        /**
         * Load the Plugin Manager
         *
         * @method QUI.controls.system.Plugins#load
         */
        load : function()
        {
            this.$Panel.Loader.show();

            var Tabbar;
            var Body = this.$Panel.getBody();

            Body.set('html', '<div class="system-plugin-tabs"></div>' +
                             '<div class="system-plugin-content"></div>');

            Tabbar = new QUI.controls.toolbar.Bar({
                width         : Body.getSize().x - 30,
                type          : 'tabbar',
                'menu-button' : false,
                'slide'       : false,
                styles        : {
                    borderBottom : '1px solid #C9C8C5',
                    height       : 20
                }
            });

            Tabbar.appendChild(
                new QUI.controls.toolbar.Tab({
                    text    : 'Plugins',
                    Control : this,
                    name    : 'pluginlist',
                    require : 'classes/system/plugins/List',
                    events  : {
                        onEnter : this.$tabOnEnter.bind( this ),
                        onLeave : this.$tabOnLeave.bind( this )
                    }
                })
            ).appendChild(
                new QUI.controls.toolbar.Tab({
                    text    : 'Neue Plugins installieren',
                    Control : this,
                    name    : 'newplugins',
                    require : 'classes/system/plugins/NewPlugins',
                    events  : {
                        onEnter : this.$tabOnEnter.bind( this ),
                        onLeave : this.$tabOnLeave.bind( this )
                    }
                })
            ).appendChild(
                new QUI.controls.toolbar.Tab({
                    text    : 'Plugins löschen',
                    Control : this,
                    name    : 'deleteplugins',
                    require : 'classes/system/plugins/DeletePlugins',
                    events  : {
                        onEnter : this.$tabOnEnter.bind( this ),
                        onLeave : this.$tabOnLeave.bind( this )
                    }
                })
            );

            Tabbar.inject( Body.getElement('.system-plugin-tabs') );
            Tabbar.resize();

            Tabbar.firstChild().click();
        },

        /**
         * Tab on Enter event
         *
         * @method QUI.controls.system.Plugins#$tabOnEnter
         * @param {QUI.controls.toolbar.Tab} Tab
         */
        $tabOnEnter : function(Tab)
        {
            var Body    = this.$Panel.getBody(),
                Content = Body.getElement('.system-plugin-content');

            if ( !Content ) {
                return;
            }

            requirejs([ Tab.getAttribute('require') ], function(Module)
            {
                var Body    = this.$Panel.getBody(),
                    Content = Body.getElement('.system-plugin-content');

                if ( !Content ) {
                    return;
                }

                new Module().openIn( Content, this.$Panel, this );

            }.bind( this ) );
        },

        /**
         * Tab on Enter event
         *
         * @method QUI.controls.system.Plugins#$tabOnLeave
         * @param {QUI.controls.toolbar.Tab} Tab
         */
        $tabOnLeave : function(Tab)
        {
            var Body    = this.$Panel.getBody(),
                Content = Body.getElement('.system-plugin-content');

            if ( !Content ) {
                return;
            }

            Content.set('html', '');
        }
    });

    return QUI.controls.system.Plugins;
});