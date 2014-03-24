

define('controls/editors/ToolbarConfigurator', [

    'qui/QUI',
    'qui/controls/Control'

], function(QUI, QUIControl)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/editors/ToolbarConfigurator',

        initialize : function(options)
        {
            this.parent( options );
        },

        /**
         * Create the DOMNode Element
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'control-editors-configurator'
                html    : '<div></div>'
            });



            return this.$Elm;
        }
    });

});