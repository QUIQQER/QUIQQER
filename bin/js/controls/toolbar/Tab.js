/**
 * A Toolbar Tab
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 *
 * @module controls/toolbar/Tab
 * @class QUI.controls.toolbar.Tab
 * @package com.pcsg.qui.js.controls.toolbar
 */

define('controls/toolbar/Tab', [

    'controls/Control',

    'css!controls/toolbar/Tab.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.toolbar' );

    /**
     * @class QUI.controls.toolbar.Tab
     *
     * @param {Object} options
     *
     * @fires onCreate - this
     * @fires onEnter  - this
     * @fires onLeave  - this
     * @fires onClick  - this
     *
     * @memberof! <global>
     */
    QUI.controls.toolbar.Tab = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.toolbar.Tab',

        options : {
            text    : '',
            'class' : false,
            icon    : ''
        },

        initialize : function(options)
        {
            this.$items   = [];
            this.$active  = false;
            this.disabled = false;

            this.$Elm  = null;
            this.$Text = null;
            this.$Menu = null;

            this.init( options );
        },

        /**
         * Create the DOMNode for the Tab
         *
         * @method QUI.controls.toolbar.Tab#create
         * @return {DOMNode}
         */
        create : function()
        {
            if ( this.$Elm ) {
                return this.$Elm;
            }

            this.$Elm = new Element('div', {
                'class'      : 'qui-toolbar-tab',
                'data-quiid' : this.getId(),

                events  :
                {
                    click : function() {
                        this.click();
                    }.bind(this),

                    mouseenter : function()
                    {
                        this.mouseenter();
                    }.bind(this),

                    mouseleave : function()
                    {
                        this.mouseleave();
                    }.bind(this)
                }
            });

            if ( this.getAttribute( 'class' ) ) {
                this.$Elm.addClass( this.getAttribute( 'class' ) );
            }

            this.$Text = new Element('span', {
                text : this.getAttribute('text')
            });

            this.$Text.inject( this.$Elm );


            if ( typeof MooNoSelect != 'undefined' ) {
                new MooNoSelect( this.$Elm );
            }

            this.fireEvent( 'create', [ this ] );

            return this.$Elm;
        },

        /**
         * If the Tab is Active, you can set to normal status
         *
         * @method QUI.controls.toolbar.Tab#setNormal
         * @return {this}
         */
        setNormal : function()
        {
            this.$disabled = false;
            this.leave();

            return this;
        },

        /**
         * Set the tab active
         *
         * @method QUI.controls.toolbar.Tab#setActive
         * @return {this}
         */
        setActive : function()
        {
            if ( this.$disabled === true ) {
                return this;
            }

            if ( this.$active === true ) {
                return this;
            }

            this.$active = true;

            this.getElm().addClass( 'qui-toolbar-active' );

            if ( this.getParent() ) {
                this.getParent().setItemActive( this );
            }

            return this;
        },

        /**
         * Is the Tab active?
         *
         * @method QUI.controls.toolbar.Tab#isActive
         * @return {Bool}
         */
        isActive : function()
        {
            return this.$active ? true : false;
        },

        /**
         * Is the Tab disabled?
         *
         * @method QUI.controls.toolbar.Tab#isDisabled
         * @return {Bool}
         */
        isDisabled : function()
        {
            return this.$disabled ? true : false;
        },

        /**
         * Mouseenter
         *
         * @method QUI.controls.toolbar.Tab#mouseenter
         * @return {this}
         */
        mouseenter : function()
        {
            if ( this.isDisabled() === true ) {
                return this;
            }

            this.getElm().addClass('qui-toolbar-hover');
            return this;
        },

        /**
         * Mouseleave
         *
         * @method QUI.controls.toolbar.Tab#mouseleave
         * @return {this}
         */
        mouseleave : function()
        {
            if ( this.isDisabled() === true ) {
                return this;
            }

            this.getElm().removeClass( 'qui-toolbar-hover' );
            return this;
        },

        /**
         * Fire mouseenter
         *
         * @method QUI.controls.toolbar.Tab#enter
         * @return {this}
         */
        enter : function()
        {
            if ( this.isDisabled() === true ) {
                return this;
            }

            this.fireEvent( 'enter', [ this ] );
            return this;
        },

        /**
         * Set the Button normal and fires onLeave Event,
         * it fires only if the Tab was active
         *
         * @method QUI.controls.toolbar.Tab#leave
         * @return {this}
         */
        leave : function()
        {
            if ( this.isDisabled() === true ) {
                return this;
            }

            if ( !this.isActive() ) {
                return this;
            }

            this.$active = false;
            this.getElm().removeClass( 'qui-toolbar-active' );
            this.fireEvent( 'leave', [ this ] );

            return this;
        },

        /**
         * Disable the button
         * all actions are not performed
         *
         * @method QUI.controls.toolbar.Tab#disable
         * @return {this}
         */
        disable : function()
        {
            this.$disabled = true;
            this.getElm().addClass( 'qui-toolbar-disable' );

            return this;
        },

        /**
         * Enable the button
         * all actions are performed
         *
         * @method QUI.controls.toolbar.Tab#enable
         * @return {this}
         */
        enable : function()
        {
            this.$disabled = false;
            this.getElm().removeClass( 'qui-toolbar-disable' );

            return this;
        },

        /**
         * Fires a click event
         * If the tab have a menu, the menu is displayed
         *
         * @method QUI.controls.toolbar.Tab#click
         * @return {this}
         */
        click : function()
        {
            this.setActive();

            if ( this.$Menu ) {
                this.$Menu.show();
            }

            this.fireEvent( 'click', [ this ] );
            this.enter();

            return this;
        }
    });

    return QUI.controls.toolbar.Tab;
});