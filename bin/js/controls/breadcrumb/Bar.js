/**
 * A breadcrumb bar
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/breadcrumb/Item
 *
 * @module controls/breadcrumb/Bar
 * @class QUI.controls.breadcrumb.Bar
 * @package com.pcsg.qui.js.controls.breadcrumb
 */

define('controls/breadcrumb/Bar', [

    'controls/Control',
    'controls/breadcrumb/Item',

    'css!controls/breadcrumb/Bar.css'

], function(Control, QUI_ContextMenuItem)
{
    QUI.namespace( 'controls.breadcrumb' );

    /**
     * @class QUI.controls.breadcrumb.Bar
     *
     * @param {Object} options
     */
    QUI.controls.breadcrumb.Bar = new Class({

        Implements : [Control],
        Type       : 'QUI.controls.breadcrumb.Bar',

        options : {
            width : false
        },

        initialize : function(options)
        {
            this.$items = [];
            this.init( options );
        },

        /**
         * Create the DOMNode for the Bar
         *
         * @method QUI.controls.breadcrumb.Bar#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'qui-breadcrumb box'
            });

            if ( this.getAttribute( 'width' ) ) {
                this.$Elm.setStyle( 'width', this.getAttribute( 'width' ) );
            }

            return this.$Elm;
        },

        /**
         * append a child to the end of the breadcrumb
         *
         * @method QUI.controls.breadcrumb.Bar#appendChild
         *
         * @param {QUI.controls.breadcrumb.Item} Item - breadcrumb item
         * @return {this}
         */
        appendChild : function(Item)
        {
            if ( Item.getType() !== 'QUI.controls.breadcrumb.Item' ) {
                return this;
            }

            this.$items.push( Item );

            Item.inject( this.getElm() );


            return this;
        },

        /**
         * Return the first child of the breadcrumb
         *
         * @method QUI.controls.breadcrumb.Bar#firstChild
         * @return {QUI.controls.breadcrumb.Item|false}
         */
        firstChild : function()
        {
            if ( typeof this.$items[0] !== 'undefined' ) {
                return this.$items[0];
            }

            return false;
        },

        /**
         * Return the last child of the breadcrumb
         *
         * @method QUI.controls.breadcrumb.Bar#lastChild
         * @return {QUI.controls.breadcrumb.Item|false}
         */
        lastChild : function()
        {
            return this.$items.getLast();
        },

        /**
         * Return all children
         *
         * @method QUI.controls.breadcrumb.Bar#getChildren
         * @return {Array}
         */
        getChildren : function()
        {
            return this.$items;
        },

        /**
         * Clears the complete breadcrumb
         *
         * @method QUI.controls.breadcrumb.Bar#clear
         */
        clear : function()
        {
            for ( var i = 0, len = this.$items.length; i < len; i++ ) {
                this.$items[i].destroy();
            }

            this.$items = [];
        },

        /**
         * Resize the Breadcrumb with the new attributes
         *
         * @method QUI.controls.breadcrumb.Bar#resize
         */
        resize : function()
        {
            if ( this.getAttribute( 'width' ) ) {
                this.getElm().setStyle( 'width', this.getAttribute( 'width' ) );
            }
        }
    });

    return QUI.controls.breadcrumb.Bar;
});
