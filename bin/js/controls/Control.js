/**
 * Control standard parent class
 * All controls should inherit {QUI.classes.Control}
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/Control
 * @package com.pcsg.qui.js.controls
 * @namespace QUI.classes
 */

define('controls/Control', [

    'classes/DOM',
    'lib/Controls',
    'controls/Utils'

], function(DOM, Controls)
{
    "use strict";

    QUI.namespace( 'classes' );

    /**
     * @class QUI.classes.Control
     *
     * @fires onDrawBegin - if inject() is used, the Event will be triggered
     * @fires onDrawEnd   - if inject() is used, the Event will be triggered
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.classes.Control = new Class({

        Implements : [ DOM ],
        Type       : 'QUI.classes.Control',

        $Parent : null,

        /**
         * Init function for inherited classes
         * If a Class inherit from QUI.classes.Control, please use init()
         * so the control are registered in QUI.Controls
         * and you can get the control with QUI.Controls.get()
         *
         * @method QUI.classes.Control#init
         * @param {Object} option - option params
         */
        init : function(options)
        {
            options = options || {};

            if ( options.events )
            {
                this.addEvents( options.events );
                delete options.events;
            }

            if ( options.methods )
            {
                Object.append( this, options.methods );
                delete options.methods;
            }

            this.setAttributes( options );

            if ( typeof QUI.Controls !== 'undefined' ) {
                QUI.Controls.add( this );
            }

            this.fireEvent( 'init', [ this ] );
        },

        /**
         * Create Method, can be overwritten for an own DOM creation
         *
         * @method QUI.classes.Control#create
         * @return {DOMNode}
         */
        create : function()
        {
            if ( this.$Elm ) {
                return this.$Elm;
            }

            this.$Elm = new Element( 'div.QUI-control' );

            return this.$Elm;
        },

        /**
         * Inject the DOMNode of the Control to a Parent
         *
         * @method QUI.classes.Control#inject
         *
         * @param {DOMNode|QUI.classes.Control}
         * @param {pos} [optional]
         *
         * @return {this}
         */
        inject : function(Parent, pos)
        {
            this.fireEvent( 'drawBegin', [ this ] );

            if ( typeof this.$Elm === 'undefined' || !this.$Elm ) {
                this.$Elm = this.create();
            }

            if ( QUI.controls.Utils.isControl( Parent ) )
            {
                // QUI Control insertion
                Parent.appendChild( this );
            } else
            {
                // DOMNode insertion
                this.$Elm.inject( Parent, pos );
            }

            this.fireEvent( 'drawEnd', [ this ] );

            return this;
        },

        /**
         * Save the control
         * Placeholder method for sub controls
         *
         * The save method returns all needed attributes for saving the control to the workspace
         * You can overwrite the method in sub classes to save specific attributes
         *
         * @return {Object}
         */
        serialize : function()
        {
            return {
                attributes : this.getAttributes(),
                type       : this.getType()
            };
        },

        /**
         * import the saved attributes and the data
         * You can overwrite the method in sub classes to import specific attributes
         *
         * @param {Object} data
         */
        unserialize : function(data)
        {
            if ( data.attributes ) {
                this.setAttributes( data.attributes );
            }
        },

        /**
         * Destroys the DOMNode of the Control
         *
         * @method QUI.classes.Control#destroy
         * @fires onDestroy
         */
        destroy : function()
        {
            this.fireEvent( 'destroy', [ this ] );

            if ( typeof this.$Elm !== 'undefined' && this.$Elm ) {
                this.$Elm.destroy();
            }

            this.$Elm = null;

            // destroy it from the controls
            QUI.Controls.destroy( this );
        },

        /**
         * Get the DOMNode from the Button
         *
         * @method QUI.classes.Control#getElm
         * @return {DOMNode}
         */
        getElm : function()
        {
            if ( typeof this.$Elm === 'undefined' || !this.$Elm ) {
                this.create();
            }

            return this.$Elm;
        },

        /**
         * If the control have a QUI_Object Parent
         *
         * @method QUI.classes.Control#getParent
         * @return {QUI.classes.Control|false}
         */
        getParent : function()
        {
            return this.$Parent ? this.$Parent : false;
        },

        /**
         * Set the Parent to the Button
         *
         * @method QUI.classes.Control#setParent
         *
         * @param {QUI.classes.Control} Parent
         * @return {this}
         */
        setParent : function(Parent)
        {
            this.$Parent = Parent;
            return this;
        },

        /**
         * Return a path string from the parent names
         *
         * @return {String}
         */
        getPath : function()
        {
            var path   = '/'+ this.getAttribute( 'name' ),
                Parent = this.getParent();

            if ( !Parent ) {
                return path;
            }

            return Parent.getPath() + path;
        },

        /**
         * Hide the control
         *
         * @method QUI.classes.Control#hide
         * @return {this}
         */
        hide : function()
        {
            if ( this.$Elm ) {
                this.$Elm.setStyle( 'display', 'none' );
            }

            return this;
        },

        /**
         * Display / Show the control
         *
         * @method QUI.classes.Control#show
         * @return {this}
         */
        show : function()
        {
            if ( this.$Elm ) {
                this.$Elm.setStyle( 'display', null );
            }

            return this;
        },

        /**
         * Highlight the control
         *
         * @method QUI.classes.Control#highlight
         * @return {this}
         */
        highlight : function()
        {
            this.fireEvent( 'highlight', [ this ] );
            return this;
        },

        /**
         * Dehighlight / Normalize the control
         *
         * @method QUI.classes.Control#normalize
         * @return {this}
         */
        normalize : function()
        {
            this.fireEvent( 'normalize', [ this ] );
            return this;
        }
    });

    return QUI.classes.Control;
});
