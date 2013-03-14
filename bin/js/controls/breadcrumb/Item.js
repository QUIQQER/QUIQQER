/**
 * A breadcrumb bar item
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 *
 * @module controls/breadcrumb/Item
 * @class QUI.controls.breadcrumb.Item
 * @package com.pcsg.qui.js.controls.breadcrumb
 *
 * @event onClick [this, event]
 */

define('controls/breadcrumb/Item', [

    'controls/Control',
    'css!controls/breadcrumb/Item.css'

], function(Control, QUI_ContextMenuItem)
{
    QUI.namespace( 'controls.breadcrumb' );

    /**
     * @class QUI.controls.breadcrumb.Item
     * @desc A breadcrumb item
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.breadcrumb.Item = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.breadcrumb.Item',

        options : {
            text : '',
            icon : false
        },

        initialize : function(options)
        {
            this.init( options );
        },

        /**
         * Create the DOMNode for the Item
         *
         * @method QUI.controls.breadcrumb.Item#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'qui-breadcrumb-item box smooth radius5',
                html    : '<span>'+ this.getAttribute( 'text' ) +'</span>',
                alt     : this.getAttribute( 'text' ),
                title   : this.getAttribute( 'text' ),
                events  :
                {
                    click : function(event)
                    {
                        this.fireEvent('click', [this, event]);
                    }.bind( this )
                }
            });

            if ( this.getAttribute( 'icon' ) )
            {
                this.$Elm.getElement( 'span' ).setStyles({
                    backgroundImage : 'url('+ this.getAttribute('icon') +')',
                    paddingLeft     : 20
                });
            }

            return this.$Elm;
        }
    });

    return QUI.controls.breadcrumb.Item;
});
