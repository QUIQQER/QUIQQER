/**
 *
 */

define('controls/users/Address', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'Ajax',

    'css!controls/users/Address.css'

], function(QUI, QUIControl, QUILoader, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/users/Address',

        Binds : [
            '$onInject'
        ],

        options : {
            uid       : false,
            addressId : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.Loader = new QUILoader();

            this.addEvents({
                onInject : this.$onInject
            });
        },

        /**
         * create the node element
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'control-users-address box'
            });

            this.Loader.inject( this.$Elm );


            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_users_address_template', function(result)
            {
                self.getElm().set( 'html', result );
                self.Loader.hide();
            });
        }

    });
});