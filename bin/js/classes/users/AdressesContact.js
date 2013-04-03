/**
 * Kontakt Tel / Fax / E-Mail von Benutzern
 *
 * Events:
 * - onDestroy
 *
 * @author www.pcsg.de (Henning Leutz)
 * @todo translation
 */

define('classes/users/AdressesContact', [

    'classes/DOM'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.users' );

    /**
     * Kontakt Tel / Fax / E-Mail von Benutzern
     *
     * @class QUI.classes.users.AdressesContact
     * @memberof! <global>
     */
    QUI.classes.users.AdressesContact = new Class({

        Extends : DOM,
        Type    : 'QUI.classes.users.AdressesContact',

        options : {
            value : '',
            type  : 'tel' // tel, fax, mobile, email
        },

        initialize : function(Container, options)
        {
            this.init( options );

            this.$Elm  = null;
            this.$Type = null;
            this.$Data = null;

            this.$Container = Container;
            this.$id        = Slick.uidOf(this);

            this.addEvent('onSetAttribute', function(k, v)
            {
                if (k === 'value' && this.$Data)
                {
                    this.$Data.value = v;
                    return;
                }

                if (k === 'type' && this.$Type)
                {
                    this.$Type.value = v;
                    return;
                }
            });

            this.draw();
        },

        /**
         * Return the contact id
         *
         * @method QUI.classes.users.AdressesContact#getId
         * @return {Integer} ID
         */
        getId : function()
        {
            return this.$id;
        },

        /**
         * Destroy the Adress Contact
         *
         * @method QUI.classes.users.AdressesContact#destroy
         */
        destroy : function()
        {
            this.$Type.destroy();
            this.$Data.destroy();
            this.$Elm.destroy();

            this.$Elm  = null;
            this.$Type = null;
            this.$Data = null;

            this.fireEvent( 'destroy', [ this ] );
        },

        /**
         * draw the adress contact
         *
         * @method QUI.classes.users.AdressesContact#draw
         */
        draw : function()
        {
            this.$Elm  = new Element('div');
            this.$Type = new Element('select', {
                value : this.getAttribute('type'),
                html  : '<option value="tel">Telefon</option>'+
                    '<option value="mobile">Mobile</option>'+
                    '<option value="fax">Fax</option>'+
                    '<option value="email">E-Mail</option>',
                styles : {
                    'float' : 'left'
                },
                events :
                {
                    change : function(event)
                    {
                        this.$Data.focus();
                        this.setAttribute('type', event.target.value);
                    }.bind(this)
                }
            });

            this.$Data = new Element('input', {
                type   : 'text',
                value  : this.getAttribute('value'),
                styles : {
                    'float' : 'left'
                }
            });

            this.$Type.inject( this.$Elm );
            this.$Data.inject( this.$Elm );

            new QUI.controls.buttons.Button({
                name    : 'destroy-contact',
                image   : URL_BIN_DIR +'16x16/cancel.png',
                Contact : this,
                events  :
                {
                    onClick : function(Btn)
                    {
                        Btn.getAttribute('Contact').destroy();
                    }
                }
            }).create().inject( this.$Elm );

            if ( this.getAttribute('type') ) {
                this.$Type.value = this.getAttribute('type');
            }

            if ( this.getAttribute('value') ) {
                this.$Type.value = this.getAttribute('value');
            }

            this.$Elm.inject( this.$Container );
        },

        /**
         * Returns the main DOM-Node Element
         *
         * @method QUI.classes.users.AdressesContact#getElm
         * @return {DOMNode} Element
         */
        getElm : function()
        {
            return this.$Elm;
        },

        /**
         * Return the value
         *
         * @method QUI.classes.users.AdressesContact#getValue
         * @return {String} value
         */
        getValue : function()
        {
            return this.$Data.value;
        },

        /**
         * Return the data
         *
         * @method QUI.classes.users.AdressesContact#getData
         * @return {Object} {type:'', no:''}
         */
        getData : function()
        {
            return {
                type : this.$Type.value,
                no   : this.getValue()
            };
        }
    });

    return QUI.classes.users.AdressesContact;
});