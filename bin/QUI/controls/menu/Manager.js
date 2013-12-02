/**
 *
 */

define('controls/menu/Manager', [

    'qui/controls/Control',
    'qui/controls/contextmenu/Bar',
    'qui/controls/contextmenu/BarItem',
    'qui/controls/contextmenu/Item',
    'Ajax'

], function(Control, ContextmenuBar, ContextmenuBarItem, ContextmenuItem, Ajax)
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


        create : function()
        {
            var self = this;

            this.$Bar = new ContextmenuBar();

            Ajax.get('ajax_menu', function(result) {
                self.$Bar.insert( result );
            });

            return this.$Bar.create();
        },


        menuClick : function(Item)
        {
            var menuRequire = Item.getAttribute( 'require' );

            if ( menuRequire )
            {
                require([ menuRequire ], function(Control)
                {
                    var Ctrl = new Control();

                    Ctrl.open();
                });
            }
        }

    });

});