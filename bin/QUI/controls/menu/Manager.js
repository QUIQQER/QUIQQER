
/**
 * QUIQQER Main Menu
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/menu/Manager', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/contextmenu/Bar',
    'qui/controls/contextmenu/BarItem',
    'qui/controls/contextmenu/Item',
    'qui/controls/desktop/Panel',
    'Ajax',
    'utils/Panels'

], function(QUI, Control, ContextmenuBar, ContextmenuBarItem, ContextmenuItem, Panel, Ajax, PanelUtils)
{
    "use strict";

    return new Class({

        Extends : Control,
        Type    : 'controls/menu/Manager',

        initialize : function(options)
        {
            this.$Bar = null;
            this.parent( options );

            this.$isLoaded = false;
        },

        /**
         * Create the topic menu
         */
        create : function()
        {
            var self = this;

            this.$Bar = new ContextmenuBar({
                dragable : true,
                name     : 'quiqqer-menu-bar'
            });

            this.$Bar.setParent( this );

            Ajax.get('ajax_menu', function(result)
            {
                self.$Bar.insert( result );

                self.$isLoaded = true;
                self.fireEvent( 'menuLoaded' );
            });

            return this.$Bar.create();
        },

        /**
         * Is the menu loaded?
         *
         * @return {Bool}
         */
        isLoaded : function()
        {
            return self.$isLoaded;
        },

        /**
         * Return the ContextBar
         *
         * @return qui/controls/contextmenu/Bar
         */
        getChildren : function()
        {
            return this.$Bar;
        },

        /**
         * Menu click helper method
         *
         * @param {qui/controls/contextmenu/Item} Item - Menu Item
         */
        menuClick : function(Item)
        {
            var self        = this,
                menuRequire = Item.getAttribute( 'require' ),
                exec        = Item.getAttribute( 'exec' ),
                xmlFile     = Item.getAttribute( 'qui-xml-file' );

            // js require
            if ( menuRequire )
            {
                require([ menuRequire ], function(Control)
                {
                    var attributes = Object.merge(
                        Item.getStorageAttributes(),
                        Item.getAttributes()
                    );

                    var Ctrl = new Control( attributes );

                    if ( instanceOf( Ctrl, Panel ) )
                    {
                        self.openPanelInTasks( Ctrl );

                        return;
                    }

                    Ctrl.open();
                });
            }

            // xml setting file
            if ( xmlFile )
            {
                require(['controls/desktop/panels/XML'], function(XMLPanel)
                {
                    self.openPanelInTasks(
                        new XMLPanel( xmlFile )
                    );
                });

                return;
            }

            // js function
            try
            {
                eval( exec );
            } catch ( e )
            {
                QUI.getMessageHandler(function(MessageHandler) {
                    MessageHandler.addError( e );
                });
            }
        },

        /**
         * Open a Panel in a taskpanel
         */
        openPanelInTasks : function(Panel)
        {
            PanelUtils.openPanelInTasks( Panel );
        }
    });

});