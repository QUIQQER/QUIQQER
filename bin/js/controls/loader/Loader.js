
/**
 * Loading DIV Class
 * Creates a DIV with a Loading
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/loader/Loader', [

    'controls/Control',
    'css!controls/loader/Loader.css'

], function(QUI_Control)
{
    QUI.namespace( 'controls.loader.Loader' );

    /**
     * @class QUI.controls.loader.Loader
     */
    QUI.controls.loader.Loader = new Class({

        Implements : [ QUI_Control ],
        Type       : 'QUI.controls.loader.Loader',

        options : {
            cssclass  : 'box-loader', // CSS class
            closetime : 50000,         // seconds if the closing window showed
            styles    : false         // extra styles
        },

        $delay : null,

        initialize : function(options)
        {
            this.init( options );
        },

        /**
         * Create the DOMNode Element of the loader
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class'  : this.options.cssclass,
                'styles' : {
                    display : 'none',
                    opacity : 0.8
                }
            });

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            return this.$Elm;
        },

        /**
         * Shows the loader
         */
        show : function(str)
        {
            if ( !this.$Elm ) {
                return;
            }

            this.$Elm.set( 'html', '' );

            if ( typeof str !== 'undefined' ) {
                this.$Elm.set( 'html', str );
            }

            this.$Elm.setStyle( 'display', '' );
            this.$Elm.getParent().addClass( 'loader-wait' );

            // sicherheitsabfrage nach 10 sekunden
            if ( this.$delay ) {
                clearTimeout( this.$delay );
            }

            this.$delay = (function()
            {
                this.showCloseButton();
            }).delay( this.options.closetime , this );
        },

        /**
         * Hide the loader
         */
        hide : function()
        {
            if ( this.$delay ) {
                clearTimeout( this.$delay );
            }

            if ( !this.$Elm ) {
                return;
            }

            this.$Elm.setStyle( 'display', 'none' );
            this.$Elm.getParent().removeClass( 'loader-wait' );
        },

        /**
         * Destroy the DOMNode of the loader
         */
        destroy : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            this.$Elm.destroy();
        },

        /**
         * Shows the closing text in the loader
         * if the timeout is triggered
         */
        showCloseButton : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            this.$Elm.set({
                html   : '',
                styles : {
                    cursor : 'pointer'
                }
            });

            this.$Elm.getParent().removeClass( 'loader-wait' );
            this.$Elm.setStyle( 'opacity', 0.9 );

            new Element('div', {
                text   : QUI.Locale.get( 'quiqqer/controls', 'loader.close' ),
                styles : {
                    'font-weight' : 'bold',
                    'text-align'  : 'center',
                    'margin-top'  : (this.$Elm.getSize().y / 2) - 100
                },
                events :
                {
                    click : function()
                    {
                        this.hide();
                    }.bind( this )
                }
            }).inject( this.$Elm );
        }
    });

    return QUI.controls.loader.Loader;
});
