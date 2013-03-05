/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/system/Packages
 * @package com.pcsg.qui.js.controls.system
 * @namespace QUI.controls.system
 */


define('controls/system/Packages', [

    'controls/Control',
    'controls/grid/Grid'

], function(QUI_Control)
{
    QUI.namespace('controls.system');

    QUI.controls.system.Packages = new Class({

        Implements: [QUI_Control],
        Type      : 'QUI.controls.system.Packages',

        initialize : function(Control, Panel)
        {
            this.$Control = Control;
            this.$Panel   = Panel;

            this.load();
        },

        /**
         * Load the Package Manager
         *
         * @method QUI.controls.system.Packages#load
         */
        load : function()
        {
            var Body = this.$Panel.getBody();

            Body.set( 'html', '<div class="system-packages-list"></div>' );

            this.$Grid = new QUI.controls.grid.Grid( Body.getElement('.system-packages-list') , {
                columnModel : [{
                    header    : 'Package',
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
                    width     : 300
               }],
               width  : Body.getSize().x + 10,
               height : Body.getSize().y - 50
            });

            this.$Control.getList(function(result, Request)
            {
                if ( !this.$Grid )
                {
                    this.$Panel.Loader.hide();
                    return;
                }

                this.$Grid.setData({
                    data : result
                });

                this.$Panel.Loader.hide();

            }.bind( this ));
        }
    });

    return QUI.controls.system.Packages;
});