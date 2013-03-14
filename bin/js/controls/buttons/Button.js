/**
 * QUI Control - Button
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 *
 * @module controls/buttons/Button
 * @package com.pcsg.qui.js.controls.buttons
 * @namespace QUI.controls.buttons
 */

define('controls/buttons/Button', [

    'controls/Control',
    'css!controls/buttons/Button.css'

], function(Control)
{
    QUI.namespace( 'controls.buttons.Button' );

    /**
     * @class QUI.controls.buttons.Button
     *
     * @event onClick
     * @event onCreate
     * @event onDrawBegin
     * @event onDrawEnd
     * @event onSetNormal
     * @event onSetDisable
     * @event onSetActive
     *
     * @event onEnter     - event triggerd if button is not disabled
     * @event onLeave     - event triggerd if button is not disabled
     * @event onMousedown - event triggerd if button is not disabled
     * @event onMouseUp   - event triggerd if button is not disabled
     * @event onFocus
     * @event onBlur
     * @event onActive
     * @event onDisable
     * @event onEnable
     *
     * @memberof! <global>
     */
    QUI.controls.buttons.Button = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.buttons.Button',

        Binds : [
            'onSetAttribute'
        ],

        options : {
            'type'      : 'button',
            'image'     : false,   // (@depricated) use the icon attribute
            'icon'      : false,   // icon top of the text
            'style'     : {},      // mootools css style attributes
            'textimage' : false,   // Image left from text
            'text'      : false,   // Button text
            'title'     : false,
            'class'     : false    // extra CSS Class
        },

        params : {},

        initialize : function(options)
        {
            options = options || {};

            this.init( options );

            this.$Menu  = null;
            this.$Drop  = null;
            this.$items = [];


            if ( options.events ) {
                delete options.events;
            }

            this.setAttributes(
                this.initV2(options)
            );

            this.addEvent('onSetAttribute', this.onSetAttribute);
            this.addEvent('onDestroy', function()
            {
                if ( this.$Menu ) {
                    this.$Menu.destroy();
                }
            }.bind( this ));
        },

        /**
         * Compatible to _ptools::Button v2
         *
         * @method QUI.controls.buttons.Button#initV2
         * @ignore
         */
        initV2: function(options)
        {
            if ( options.onclick )
            {
                if ( typeOf(options.onclick) === 'string' )
                {
                    options.onclick = function(p) {
                        eval(p +'(this);');
                    }.bind(this, [options.onclick]);
                }

                this.addEvent( 'onClick', options.onclick );
                delete options.onclick;
            }

            if ( options.oncreate )
            {
                this.addEvent( 'onCreate', options.oncreate );
                delete options.oncreate;
            }

            return options;
        },

        /**
         * Create the DOM Element
         *
         * @method QUI.controls.buttons.Button#create
         * @return {DOMNode}
         */
        create : function()
        {
            var i, len;

            var Elm = new Element('button.qui-btn2', {
                'type' : this.getAttribute('type'),
                'data-status' : 0,
                'data-quiid'  : this.getId()
            });

            if ( this.getAttribute( 'width' ) ) {
                Elm.setStyle( 'width', this.getAttribute( 'width' ) );
            }

            if ( this.getAttribute('height') ) {
                Elm.setStyle( 'height', this.getAttribute( 'height' ) );
            }

            if ( this.getAttribute( 'styles' ) ) {
                Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            if ( this.getAttribute( 'class' ) ) {
                Elm.addClass( this.getAttribute( 'class' ) );
            }

            Elm.style.outline = 0;
            Elm.setAttribute('tabindex', "-1");

            Elm.addEvents({

                click : function(event)
                {
                    if ( this.isDisabled() ) {
                        return;
                    }

                    this.onclick( event );
                }.bind( this ),

                mouseenter : function()
                {
                    if ( this.isDisabled() ) {
                        return;
                    }

                    if ( !this.isActive() )
                    {
                        this.getElm().className = 'qui-btn2-over';

                        if ( this.getAttribute('class') ) {
                            this.getElm().addClass( this.getAttribute('class') );
                        }
                    }

                    this.fireEvent('enter', [this]);
                }.bind(this),

                mouseleave : function()
                {
                    if ( this.isDisabled() ) {
                        return;
                    }

                    if ( !this.isActive() )
                    {
                        this.getElm().className = 'qui-btn2';

                        if ( this.getAttribute('class') ) {
                            this.getElm().addClass( this.getAttribute('class') );
                        }
                    }

                    this.fireEvent('leave', [this]);
                }.bind( this ),

                mousedown : function(event)
                {
                    if ( this.isDisabled() ) {
                        return;
                    }

                    this.fireEvent( 'mousedown', [ this, event ] );

                }.bind(this),

                mouseup : function(event)
                {
                    if ( this.isDisabled() ) {
                        return;
                    }

                    this.fireEvent( 'mouseup', [ this, event ] );
                }.bind(this),

                blur : function(event)
                {
                    this.fireEvent( 'blur', [ this, event ] );
                }.bind(this),

                focus : function(event)
                {
                    this.fireEvent( 'focus', [ this, event ] );
                }.bind(this)
            });

            this.$Elm = Elm;

            // Elemente aufbauen
            if ( this.getAttribute( 'icon' ) ) {
                this.setAttribute( 'icon', this.getAttribute( 'icon' ) );
            }

            if ( !this.getAttribute( 'icon' ) && this.getAttribute( 'image' ) ) {
                this.setAttribute( 'icon', this.getAttribute( 'image' ) );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.setAttribute( 'styles', this.getAttribute( 'styles' ) );
            }

            if ( this.getAttribute( 'textimage' ) ) {
                this.setAttribute( 'textimage', this.getAttribute( 'textimage' ) );
            }

            if ( this.getAttribute( 'text' ) ) {
                this.setAttribute( 'text', this.getAttribute( 'text' ) );
            }

            if ( this.getAttribute( 'title' ) ) {
                this.$Elm.setAttribute( 'title', this.getAttribute( 'title' ) );
            }

            if ( this.getAttribute( 'disabled' ) ) {
                this.setDisable();
            }


            // sub menu
            len = this.$items.length;

            if ( len )
            {
                var Menu = this.getContextMenu();

                for ( i = 0; i < len; i++ ) {
                    Menu.appendChild( this.$items[i] );
                }

                this.$Drop = new Element('div.qui-btn2-drop').inject(
                    this.$Elm
                );
            }

            this.fireEvent('create', [this]);

            if ( typeof MooNoSelect !== 'undefined' ) {
                new MooNoSelect( Elm );
            }

            return this.$Elm;
        },

        /**
         * Trigger the Click Event
         *
         * @method QUI.controls.buttons.Button#onclick
         * @param {DOMEvent} event
         */
        click : function(event)
        {
            if ( this.isDisabled() ) {
                return;
            }

            this.fireEvent( 'click', [this, event] );
        },

        /**
         * @see QUI.controls.buttons.Button#onclick
         */
        onclick : function(event)
        {
            this.click( event );
        },

        /**
         * Set the Button Active
         *
         * @method QUI.controls.buttons.Button#setActive
         */
        setActive : function()
        {
            if ( this.isDisabled() ) {
                return;
            }

            var Elm = this.getElm();

            if ( !Elm ) {
                return;
            }

            Elm.className = 'qui-btn2-active';
            Elm.set('data-status', 1);

            if ( this.getAttribute('class') ) {
                Elm.addClass( this.getAttribute('class') );
            }

            this.fireEvent('active', [this]);
        },

        /**
         * is Button Active?
         *
         * @method QUI.controls.buttons.Button#isActive
         * @return {Bool}
         */
        isActive : function()
        {
            if ( !this.getElm() ) {
                return false;
            }

            if ( this.getElm().get('data-status') == 1 ) {
                return true;
            }

            return false;
        },

        /**
         * Disable the Button
         * Most Events are no more triggered
         *
         * @method QUI.controls.buttons.Button#disable
         * @return {QUI.controls.buttons.Button}
         */
        disable : function()
        {
            var Elm = this.getElm();

            if ( !Elm ) {
                return;
            }

            Elm.className = 'qui-btn2-disable';
            Elm.set('data-status', -1);

            if ( this.getAttribute('class') ) {
                Elm.addClass( this.getAttribute('class') );
            }

            this.fireEvent('disable', [this]);

            return this;
        },

        /**
         * @depricated use disable
         * @method QUI.controls.buttons.Button#setDisable
         * @return {QUI.controls.buttons.Button}
         */
        setDisable : function()
        {
            return this.disable();
        },

        /**
         * is Button Disabled?
         *
         * @method QUI.controls.buttons.Button#isDisabled
         * @return {Bool}
         */
        isDisabled : function()
        {
            if ( !this.getElm() ) {
                return false;
            }

            if ( this.getElm().get('data-status') == -1 ) {
                return true;
            }

            return false;
        },

        /**
         * If the Button was disabled, you can enable the Button
         *
         * @method QUI.controls.buttons.Button#setEnable
         * @return {this}
         */
        enable : function()
        {
            if ( !this.getElm() ) {
                return false;
            }

            this.getElm().set('data-status', 0);
            this.setNormal();

            return this;
        },

        /**
         * @depricated
         *
         * @method QUI.controls.buttons.Button#setEnable
         * @return {this}
         */
        setEnable : function()
        {
            return this.enable();
        },

        /**
         * If the Button was active, you can normalize the Button
         * The Button must be enabled.
         *
         * @method QUI.controls.buttons.Button#setNormal
         * @return {this}
         */
        setNormal : function()
        {
            if ( this.isDisabled() ) {
                return;
            }

            if ( !this.getElm() ) {
                return false;
            }

            var Elm = this.getElm();

            Elm.className = 'qui-btn2';
            Elm.set('data-status', 0);

            if ( this.getAttribute('class') ) {
                Elm.addClass( this.getAttribute('class') );
            }

            this.fireEvent('normal', [this]);

            return this;
        },

        /**
         * Adds a Children to an Button Menu
         *
         * @method QUI.controls.buttons.Button#appendChild
         *
         * @param {QUI.controls.contextmenu.Item} Itm
         * @return {this}
         */
        appendChild : function(Itm)
        {
            this.$items.push( Itm );

            Itm.setAttribute('Button', this);

            if ( this.$Elm )
            {
                this.getContextMenu().appendChild( Itm );

                if ( !this.$Drop )
                {
                    this.$Drop = new Element('div.qui-btn2-drop').inject(
                        this.$Elm
                    );
                }
            }

            return this;
        },

        /**
         * All Context Menu Items
         *
         * @method QUI.controls.buttons.Button#getChildren
         * @return {Array}
         */
        getChildren : function()
        {
            return this.$items;
        },

        /**
         * Clear the Context Menu Items
         *
         * @method QUI.controls.buttons.Button#clear
         * @return {this}
         */
        clear : function()
        {
            this.getContextMenu().clearChildren();
            this.$items = [];

            return this;
        },

        /**
         * Create the Context Menu if not exist
         *
         * @method QUI.controls.buttons.Button#getContextMenu
         * @return {QUI.controls.contextmenu.Menu}
         */
        getContextMenu : function()
        {
            if ( this.$Menu ) {
                return this.$Menu;
            }

            this.$Menu = new QUI.controls.contextmenu.Menu({
                name : this.getAttribute('name') +'-menu'
            });

            this.$Menu.inject( document.body );

            this.addEvents({
                onClick : function()
                {
                    if ( this.isDisabled() ) {
                        return;
                    }

                    var pos = this.$Elm.getPosition();

                    this.$Menu.setPosition( pos.x, pos.y+20 );
                    this.$Menu.show();

                    this.$Elm.focus();
                }.bind( this ),

                onBlur : function()
                {
                    this.$Menu.hide();
                }
            });

            this.$Menu.setParent( this );

            return this.$Menu;
        },

        /**
         * Method for changing the DOMNode if attributes are changed
         *
         * @method QUI.controls.buttons.Button#onSetAttribute
         *
         * @param {String} k             - Attribute name
         * @param {unknown_type} value     - Attribute value
         *
         * @ignore
         */
        onSetAttribute : function(k, value)
        {
            var Elm = this.getElm();

            //this.options[k] = value;

            if ( !Elm ) {
                return;
            }

            // onclick overwrite
            if ( k === 'onclick' )
            {
                this.removeEvents('click');

                this.addEvent('click', function(p)
                {
                    eval(p +'(this);');
                }.bind( this, [value] ));

                return;
            }

            if ( k == 'image' ) {
                k = 'icon';
            }

            // Image
            if ( k === 'icon' )
            {
                if ( !Elm.getElement('.image-container') )
                {
                    new Element('div.image-container', {
                        align : 'center'
                    }).inject( Elm );
                }

                if ( !Elm.getElement('.qui-btn2-image') )
                {
                    new Element('img.qui-btn2-image', {
                        src    : value,
                        styles : {
                            'display' : 'block' // only image, fix
                        }
                    }).inject( Elm.getElement('.image-container') );

                    return;
                }

                Elm.getElement('.qui-btn2-image').set( 'src', value );
                return;
            }

            // Style Attributes
            if ( k === "styles" )
            {
                Elm.setStyles( value );
                return;
            }

            // Text
            if ( k === "title" )
            {
                Elm.setAttribute( 'title', value );
                return;
            }

            // Text and Text-Image
            if ( k !== 'textimage' && k !== 'text' ) {
                return;
            }

            // Text + Text Image
            if ( !Elm.getElement('.qui-btn2-text') )
            {
                new Element('div.qui-btn2-text', {
                    html : '<span></span>'
                }).inject( Elm );
            }

            var Txt  = Elm.getElement('.qui-btn2-text'),
                Span = Txt.getElement('span'),
                Img  = Txt.getElement('img');

            // Text
            if ( k === 'text' ) {
                Span.set('html', value);
            }

            if ( k === 'textimage' )
            {
                if ( !Elm.getElement('.qui-btn2-text-image') )
                {
                    new Element('img.qui-btn2-text-image', {
                        styles : {
                            'margin-right': 0
                        }
                    }).inject( Span, 'before' );
                }

                Img = Elm.getElement('.qui-btn2-text-image');
                Img.set( 'src', value );

                if ( this.getAttribute('text') ) {
                    Img.setStyle( 'margin-right', 5 );
                }
            }
        }
    });

    return QUI.controls.buttons.Button;
});
