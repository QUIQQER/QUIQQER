/**
 * Window
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 *
 * @module controls/windows/Window
 * @package com.pcsg.qui.js.controls.windows
 * @namespace QUI.controls.windows
 */

define('controls/windows/Window', [

    'controls/Control',
    'controls/loader/Loader'

], function(Control)
{
    QUI.namespace('controls.windows');

    /**
     * @class QUI.controls.windows.Window
     *
     * @fires onDrawEnd
     * @fires onClose
     *
     * @param {Object} options
     */
    QUI.controls.windows.Window = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.windows.Window',

        options : {
            'name'  : false,
            'type'  : 'modal',
            'title' : '',

            'left'   : false,
            'top'    : false,
            'width'  : false,
            'height' : false,
            'icon'   : false,

            'body' : false,
            'footerHeight' : false
        },

        initialize : function(options)
        {
            this.init( options );

            // defaults
            if ( this.getAttribute('name') === false ) {
                this.setAttribute('name', 'win'+ new Date().getMilliseconds());
            }

            if ( this.getAttribute('width') === false ) {
                this.setAttribute('width', 600);
            }

            if ( this.getAttribute('height') === false ) {
                this.setAttribute('height', 340);
            }

            this.$Body   = null;
            this.$Win    = null;
            this.$Loader = null;
        },

        $windowInit : function()
        {
            this.Loader =
            {
                show : function()
                {
                    if ( !this.$Win ) {
                        return;
                    }

                    if ( !this.$Win.el.windowEl ) {
                        return;
                    }

                    if ( !this.$Loader )
                    {
                        this.$Loader = new QUI.controls.loader.Loader();
                        this.$Loader.inject( this.$Win.el.windowEl );
                    }

                    this.$Loader.show();

                    //this.getWin().showSpinner();
                }.bind( this ),

                hide : function()
                {
                    this.$Loader.hide();

                    //this.getWin().hideSpinner();
                }.bind( this )
            };
        },

        /**
         * Get the MUI Window Element
         *
         * @method QUI.controls.windows.Window#getWin
         * @return {false|QUI.Window}
         */
        getWin : function()
        {
            if ( typeof this.$Win !== 'undefined' ) {
                return this.$Win;
            }

            return false;
        },

        /**
         * Create the window
         *
         * @method QUI.controls.windows.Window#create
         */
        create : function()
        {
            var control = 'MUI.Modal',
                type    = 'modal';

            if ( this.getAttribute('type') === 'window' )
            {
                control = 'MUI.Window',
                type    = 'window';
            }

            MUI.create({
                control   : control,
                type      : type,
                container : 'content',
                id : this.getAttribute('name'),
                x  : this.getAttribute('left') || null,
                y  : this.getAttribute('top') || null,

                draggable    : true,
                footerHeight : this.getAttribute('footerHeight') ? this.getAttribute('footerHeight') : 28,

                title   : this.getAttribute('title') || '',
                icon    : this.getAttribute('icon') || false,
                width   : this.getAttribute('width'),
                height  : this.getAttribute('height'),
                content : this.getAttribute('body') || '',
                Parent  : this,
                onDrawEnd : function()
                {
                    var Parent = this.options.Parent;

                    Parent.$Win = this;
                    Parent.$windowInit( Parent );

                    if ( typeOf( Parent.onCreate ) === 'function' ) {
                        Parent.onCreate( this );
                    }

                    if ( this.options.content ) {
                        Parent.getBody().set( 'html', this.options.content );
                    }

                    Parent.fireEvent( 'drawEnd', [ Parent, this ] );
                },

                onClose : function()
                {
                    this.$close();
                }.bind( this )
            });
        },

        /**
         * Close the window
         *
         * @method QUI.controls.windows.Window#close
         * @return {this}
         */
        close : function()
        {
            if ( !this.$Win )
            {
                this.$close();
                return this;
            }

            this.$Win.close();
            return this;
        },

        /**
         * Close the window helper method
         *
         * @method QUI.controls.windows.Window#$close
         */
        $close : function()
        {
            this.destroy();
            this.fireEvent( 'close', [this] );
        },

        /**
         * Set the window body with html
         *
         * @method QUI.controls.windows.Window#setBody
         *
         * @param {String} html - HTML String
         * @return {this}
         */
        setBody : function(html)
        {
            this.getBody().set( 'html', html );
            return this;
        },

        /**
         * Return the Body Element
         *
         * @method QUI.controls.windows.Window#getBody
         *
         * @return {DOMNode}
         */
        getBody : function()
        {
            return this.$Body;
        }
    });

    return QUI.controls.windows.Window;
});
