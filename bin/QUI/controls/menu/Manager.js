/**
 *
 */

define('controls/menu/Manager', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/contextmenu/Bar',
    'qui/controls/contextmenu/BarItem',
    'qui/controls/contextmenu/Item',
    'Ajax',
    'qui/controls/desktop/Panel'

], function(QUI, Control, ContextmenuBar, ContextmenuBarItem, ContextmenuItem, Ajax, Panel)
{
    "use strict";

    return new Class({

        Extends : Control,
        Type    : 'classes/menu/Manager',

        initialize : function(options)
        {
            this.$Bar = null;
            this.parent( options );
        },

        /**
         * Create the topic menu
         */
        create : function()
        {
            var self = this;

            this.$Bar = new ContextmenuBar();

            Ajax.get('ajax_menu', function(result) {
                self.$Bar.insert( result );
            });

            return this.$Bar.create();
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
                exec        = Item.getAttribute( 'exec' );

            if ( menuRequire )
            {
                require([ menuRequire ], function(Control)
                {
                    var Ctrl = new Control();

                    if ( instanceOf( Ctrl, Panel ) )
                    {
                        self.openPanelInTasks( Ctrl );

                        return;
                    }

                    Ctrl.open();
                });
            }

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
            // if panel not exists
            var panels = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

            if ( !panels.length ) {
                return;
            }

            panels[ 0 ].appendChild( Panel );
        }
    });

});