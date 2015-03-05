
/**
 * Available language list control
 * list all available languages from the system
 *
 * @module controls/system/AvailableLanguages
 * @author www.pcsg.de (Henning Leutz)
 *
 */

define('controls/system/AvailableLanguages', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'Ajax',
    'Locale',

    'css!controls/system/AvailableLanguages.css'

], function(QUI, QUIControl, QUILoader, QUIAjax, QUILocale)
{
    "use strict";

    return new Class({

        Type    : 'controls/system/AvailableLanguages',
        Extends : QUIControl,

        Binds : [
            '$onInject',
            '$onImport'
        ],

        initialize : function(options)
        {
            this.parent( options );

            this.$Input = null;

            this.Loader = new QUILoader();

            this.addEvents({
                onInject : this.$onInject,
                onImport : this.$onImport
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLElement}
         */
        create : function()
        {
            if ( !this.$Elm )
            {
                this.$Elm = this.parent();

                this.$Input = new Element('input', {
                    type : 'hidden'
                }).inject( this.$Elm );
            }

            this.Loader.inject( this.$Elm );

            return this.$Elm;
        },

        /**
         * event on inject
         */
        $onInject : function()
        {
            var self = this;

            this.Loader.show();

            this.getAvailableLanguages(function(list)
            {
                var i, len, flag, langtext;

                for ( i = 0, len = list.length; i < len; i++ )
                {
                    flag = '<span class="quiqqer-available-flag">' +
                               '<img src="'+ URL_BIN_DIR +'16x16/flags/'+ list[ i ] +'.png" />' +
                           '</span>';

                    langtext = QUILocale.get( 'quiqqer/system', 'lang.'+list[ i ] );

                    new Element('label', {
                        'class'     : 'quiqqer-available-languages-entry',
                        'data-lang' : list[ i ],
                        html        : '<input type="text" placeholder="D M j" />'+
                                      '<span class="quiqqer-available-languages-entry-text">'+
                                          flag + langtext +
                                      '</span>'
                    }).inject( self.getElm() );
                }

                self.Loader.hide();
            });
        },

        /**
         * event on import
         */
        $onImport : function()
        {
            if ( this.$Elm.nodeName === 'INPUT' )
            {
                this.$Elm.set( 'type', 'hidden' );

                var Elm = new Element('div', {
                    'class' : 'quiqqer-availableLanguages'
                });

                Elm.wraps( this.$Elm );

                this.$Elm = Elm;
                this.$Input = this.$Elm.getElement( 'input' );
            }

            this.create();
            this.$onInject();
        },

        /**
         * Return the available languages
         * @param {Function} callback
         */
        getAvailableLanguages : function(callback)
        {
            QUIAjax.get( 'ajax_system_getAvailableLanguages', callback );
        }
    });
});
