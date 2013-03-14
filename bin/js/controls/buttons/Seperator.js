/**
 * Button Seperator
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 *
 * @module controls/buttons/Seperator
 * @package com.pcsg.qui.js.controls.buttons
 * @namespace QUI.controls.buttons
 */

define('controls/buttons/Seperator', [

    'controls/Control'

], function(Control)
{
    QUI.namespace( 'controls.buttons.Seperator' );

    /**
     * @class QUI.controls.buttons.Seperator
     *
     * @param {Object} options
     *
     * @event onResize [this]
     * @event onCreate [this]
     *
     * @memberof! <global>
     */
    QUI.controls.buttons.Seperator = new Class({

        Implements : [Control],
        Type       : 'QUI.controls.buttons.Seperator',

        options : {
            height : false
        },

        initialize : function(options)
        {
            options = options || {};

            if ( options.events )
            {
                this.addEvents( options.events );
                delete options.events;
            }

            this.init( options );

            // Events
            this.addEvent('resize', function(Toolbar)
            {
                var Elm = this.getElm();

                if ( Elm && Elm.getParent() ) {
                    Elm.setStyle( 'height', Elm.getParent().getSize().y );
                }
            });
        },

        /**
         * Create the DOMNode
         *
         * @method QUI.controls.buttons.Seperator#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div.qui-btn-seperator', {
                'data-quiid' : this.getId()
            });

            if ( this.getAttribute( 'height' ) ) {
                this.$Elm.setStyle( 'height', this.getAttribute( 'height' ) );
            }

            this.fireEvent( 'create', [ this ] );

            return this.$Elm;
        }
    });

    return QUI.controls.buttons.Seperator;
});
