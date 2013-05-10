/**
 * Sitemap Search Control
 * The control searches a Sitemap Control
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/sitemap/Filter
 * @package com.pcsg.qui.js.controls.sitemap
 * @namespace QUI.controls.project
 *
 * @event onFocus [this]
 * @event onResultNotViewable [this, Item] <-- delete
 * @event onFilter  [this, results]
 */

define('controls/sitemap/Filter', [

    'controls/Control',
    'controls/buttons/Button',

    'css!controls/sitemap/Filter.css'

], function(QUI_Control)
{
    "use strict";

    QUI.namespace( 'controls.sitemap' );

    /**
     * A project sitemap
     *
     * @class QUI.controls.projects.Filter
     *
     * @param {QUI.controls.sitemap.Map}
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.sitemap.Filter = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.sitemap.Filter',

        Binds : [
            'filter',
            '$filter'
        ],

        options : {
            styles      : false,
            placeholder : 'Filter ...',
            withbutton  : false
        },

        initialize : function(Sitemap, options)
        {
            this.init( options );

            this.$Elm   = null;
            this.$Input = null;
            this.$maps  = [];

            this.bindSitemap( Sitemap );

            this.$timeoutID = false;
        },

        /**
         * Create the DOMNode of the sitemap filter
         *
         * @method QUI.controls.sitemap.Filter#create
         * @return {DOMNode} DOM-Element
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'qui-sitemap-filter box',
                html    : '<input type="text" placeholder="'+ this.getAttribute('placeholder') +'" />'
            });

            this.$Input = this.$Elm.getElement( 'input' );

            this.$Input.addEvents({

                keyup : function(event)
                {
                    if ( event.key == 'enter' )
                    {
                        this.filter( this.getInput().value );
                        return;
                    }

                    if ( this.$timeoutID ) {
                        clearTimeout( this.$timeoutID );
                    }

                    this.$timeoutID = function()
                    {
                        this.filter( this.getInput().value );
                        this.$timeoutID = false;

                    }.delay( 250, this );

                }.bind( this ),

                focus : function(event)
                {
                    this.fireEvent( 'focus', [ this ] );
                }.bind( this ),

                blur : function()
                {
                    if ( this.getInput().value === '' ) {
                        this.filter();
                    }

                }.bind( this )
            });


            if ( this.getAttribute( 'withbutton' ) )
            {
                this.$Search = new QUI.controls.buttons.Button({
                    image  : URL_BIN_DIR +'16x16/search.png',
                    events :
                    {
                        onClick : function() {
                            this.filter( this.getInput().value );
                        }.bind( this )
                    }
                }).inject( this.$Elm );
            }


            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            return this.$Elm;
        },

        /**
         * Sets the sitemap which is to be searched
         * Older Sitemap binds persist
         *
         * @method QUI.controls.sitemap.Filter#bindSitemap
         * @param {QUI.controls.sitemap.Map} Sitemap
         * @return {this} self
         */
        bindSitemap : function(Sitemap)
        {
            if ( typeof Sitemap === 'undefined' || !Sitemap ) {
                return;
            }

            this.$maps.push( Sitemap );

            return this;
        },

        /**
         * all binds would be resolved
         *
         * @method QUI.controls.sitemap.Filter#clearBinds
         * @return {this} self
         */
        clearBinds : function()
        {
            this.$maps = [];

            return this;
        },

        /**
         * Return the filter input DOMNode Element
         *
         * @method QUI.controls.sitemap.Filter#getInput
         * @return {DOMNode}
         */
        getInput : function()
        {
            return this.$Input;
        },

        /**
         * Filter the Sitemaps
         *
         * @method QUI.controls.sitemap.Filter#filter
         * @param {String} str - the filter value
         * @return {this}
         */
        filter : function(str)
        {
            str = str || '';

            for ( var i = 0, len = this.$maps.length; i < len; i++ ) {
                this.$filter( this.$maps[ i ], str );
            }

            return this;
        },

        /**
         * Helper Function for the filter
         *
         * @method QUI.controls.sitemap.Filter#$filter
         * @param {QUI.controls.sitemap.Map} Map - the Sitemap
         * @param {String} str - the filter value
         */
        $filter : function(Map, str)
        {
            if ( typeof Map === 'undefined' || !Map ) {
                return;
            }

            var i, len;
            var children = Map.getChildren();

            if ( str === '' )
            {
                for ( i = 0, len = children.length; i < len; i++ ) {
                    children[ i ].normalize();
                }

                this.fireEvent( 'filter',  [ this, [] ] );

                return;
            }


            for ( i = 0, len = children.length; i < len; i++ ) {
                children[ i ].holdBack();
            }

            var result = Map.search( str );

            for ( i = 0, len = result.length; i < len; i++ ) {
                result[ i ].normalize();
            }

            this.fireEvent( 'filter', [ this, result ] );
        }
    });

    return QUI.controls.sitemap.Filter;
});
