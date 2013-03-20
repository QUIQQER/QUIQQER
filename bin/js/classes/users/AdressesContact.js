/**
 * Kontakt Tel / Fax / E-Mail von Benutzern
 *
 * Events:
 * - onDestroy
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/users/AdressesContact', [

    'classes/DOM'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.users' );


    QUI.classes.users.AdressesContact = new Class({

        Implements : [DOM],

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

        getId : function()
        {
            return this.$id;
        },

        destroy : function()
        {
            this.$Type.destroy();
            this.$Data.destroy();
            this.$Elm.destroy();

            this.$Elm  = null;
            this.$Type = null;
            this.$Data = null;

            this.fireEvent('destroy', [this]);
        },

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

        getElm : function()
        {
            return this.$Elm;
        },

        getValue : function()
        {
            return this.$Data.value;
        },

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