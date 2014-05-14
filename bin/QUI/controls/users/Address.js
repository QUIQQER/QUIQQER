/**
 *
 */

define('controls/users/Address', [

    'qui/QUI',
    'qui/controls/Control'

], function(QUI, QUIControl)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/users/Address',

        options : {
            uid       : false,
            addressId : false
        },

        initialize : function(options)
        {
            this.parent( options );
        },

        /**
         * create the node element
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'qui-control-users-address',
                html    : '------'
            });



            return this.$Elm;
        }

    });
});