
/**
 * QUIQQER Contact Controle
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module Controls\Contact
 */

define([

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/loader/Loader',
    'Ajax',
    'Locale'

], function(QUI, QUIControl, QUIButton, QUILoader, Ajax, Locale)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'Controls\Contact',

        Binds : ['$onImport'],

        initialize : function(options)
        {
            this.parent( options );

            this.Loader = new QUILoader();

            this.$Text  = null;
            this.$Email = null;
            this.$Name  = null;

            this.addEvents({
                onImport : this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport : function()
        {
            var self = this;

            this.Loader.inject( this.$Elm );
            this.Loader.show();

            new QUIButton({
                text   : 'senden',
                textimage : 'fa fa-envelope-o',
                events :
                {
                    onClick : function() {
                        self.$Elm.getElement('form').fireEvent('submit');
                    }
                }
            }).inject( this.$Elm );


            this.$Elm.getElement('form').addEvent('submit', function(event)
            {
                if ( typeof event !== 'undefined' ) {
                    event.stop();
                }

                self.send();
            });

            this.$Text  = this.$Elm.getElement( '[name="message"]' );
            this.$Email = this.$Elm.getElement( '[name="email"]' );
            this.$Name  = this.$Elm.getElement( '[name="name"]' );

            this.Loader.hide();
        },

        /**
         * Send contact message
         */
        send : function()
        {
            if ( this.$Text.value === '' )
            {
                this.$Text.focus();
                return;
            }

            if ( this.$Email.value === '' )
            {
                this.$Email.focus();
                return;
            }

            if ( this.$Name.value === '' )
            {
                this.$Name.focus();
                return;
            }

            var self = this;

            this.Loader.show();



            Ajax.post('ajax_contact', function(result)
            {
                if ( result ) {
                    self.$Elm.set( 'html', Locale.get( 'quiqqer/system', 'message.contact.successful' ) );
                }

                self.Loader.hide();

            }, {
                message   : this.$Text.value,
                email     : this.$Email.value,
                name      : this.$Name.value,
                showError : false,
                onError   : function(Exception)
                {
                    self.Loader.hide();

                    QUI.getMessageHandler(function(MH) {
                        MH.addError( Exception.getMessage(), self.$Elm );
                    });
                }
            });
        }
    });
});