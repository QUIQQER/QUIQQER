/**
 * A Panel
 * A Panel is a container for apps.
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/Panel
 * @package com.pcsg.qui.js.controls.desktop
 * @namespace QUI.controls.desktop
 *
 * @todo create footer
 *
 * @event onCreate [this]
 * @event onOpen [this]
 * @event onMinimize [this]
 * @event onRefresh [this]
 * @event onResize [this]
 */

define('controls/desktop/Panel', [

    'controls/Control',
    'controls/loader/Loader',
    'controls/toolbar/Bar',
    'controls/buttons/Seperator',
    'controls/buttons/Button',
    'controls/desktop/panels/Sheet',
    'controls/breadcrumb/Bar',

    'css!controls/desktop/Panel.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.desktop' );

    /**
     * @class QUI.controls.desktop.Panel
     *
     * @memberof! <global>
     */
    QUI.controls.desktop.Panel = new Class({

        Extends : Control,
        Type    : 'QUI.controls.desktop.Panel',

        options :
        {
            name    : 'qui-desktop-panel',
            content : false,

            // header
            header : true,      // true to create a panel header when panel is created
            title  : false,     // the title inserted into the panel's header
            icon   : false,

            // footer
            footer : false,     // true to create a panel footer when panel is created

            // Style options:
            height     : 125,        // the desired height of the panel
            cssClass   : '',         // css class to add to the main panel div
            scrollbars : true,       // true to allow scrollbars to be shown
            padding    : 8,          // default padding for the panel

            // Other:
            collapsible    : true,   // can the panel be collapsed
            collapseFooter : true,   // collapse footer when panel is collapsed

            closeable  : true, // can be the panel destroyed?
            dragable   : true,  // is the panel dragable to another column?
            breadcrumb : false
        },

        initialize: function(options)
        {
            this.$uid = String.uniqueID();
            this.init( options );

            this.Loader = new QUI.controls.loader.Loader();

            this.$Elm     = null;
            this.$Header  = null;
            this.$Title   = null;
            this.$Footer  = null;
            this.$Content = null;

            this.$Buttons     = null;
            this.$Categories  = null;
            this.$Breadcrumb  = null;
            this.$ContextMenu = null;

            this.$ButtonBar     = null;
            this.$CategoryBar   = null;
            this.$BreadcrumbBar = null;
            this.$ActiveCat     = null;

            this.addEvent( 'onDestroy', this.$onDestroy );
        },

        /**
         * destroy the panel
         *
         * @method QUI.controls.desktop.Panel#destroy
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
         * Create the DOMNode Element for the panel
         *
         * @method QUI.controls.desktop.Panel#create
         * @return {DOMNode}
         */
        create : function()
        {
            if ( this.$Elm ) {
                return this.$Elm;
            }

            this.$Elm = new Element('div', {
                'data-quiid' : this.getId(),
                'class'      : 'qui-panel',
                styles       : {
                    height : this.getAttribute('height')
                },

                html : '<div class="qui-panel-header"></div>' +
                       '<div class="qui-panel-buttons box"></div>' +
                       '<div class="qui-panel-categories box"></div>' +
                       '<div class="qui-panel-content box"></div>' +
                       '<div class="qui-panel-footer"></div>'
            });

            this.Loader.inject( this.$Elm );

            this.$Header     = this.$Elm.getElement( '.qui-panel-header' );
            this.$Footer     = this.$Elm.getElement( '.qui-panel-footer' );
            this.$Content    = this.$Elm.getElement( '.qui-panel-content' );
            this.$Buttons    = this.$Elm.getElement( '.qui-panel-buttons' );
            this.$Categories = this.$Elm.getElement( '.qui-panel-categories' );

            if ( this.getAttribute( 'breadcrumb' ) )
            {
                this.$Breadcrumb = new Element('div', {
                    'class' : 'qui-panel-breadcrumb box'
                }).inject( this.$Buttons, 'after' );
            }

            this.$Content.setStyle( 'display', null );
            this.$Buttons.setStyle( 'display', 'none' );
            this.$Categories.setStyle( 'display', 'none' );

            if ( this.getAttribute( 'content' ) ) {
                this.$Content.set( 'html', this.getAttribute('content') );
            }

            if ( this.getAttribute('collapsible') )
            {
                this.$Collaps = new Element('div', {
                    'class' : 'qui-panel-collapse'
                }).inject( this.$Header );

                this.$Header.setStyle( 'cursor', 'pointer' );

                this.$Header.addEvent('click', function()
                {
                    this.toggle();
                }.bind( this ));
            }

            // drag & drop
            if ( this.getAttribute('dragable') )
            {
                this.$Header.setStyle( 'cursor', 'move' );

                new QUI.classes.utils.DragDrop(this.$Header, {
                    dropables : '.qui-panel-drop',
                    cssClass  : 'radius5',
                    styles    : {
                        width  : 300,
                        height : 100
                    },
                    events    :
                    {
                        onEnter : function(Element, Droppable) {
                            QUI.controls.Utils.highlight( Droppable );
                        },

                        onLeave : function(Element, Droppable) {
                            QUI.controls.Utils.normalize( Droppable );
                        },

                        onDrop : function(Element, Droppable, event)
                        {
                            if ( !Droppable ) {
                                return;
                            }
                            var quiid = Droppable.get( 'data-quiid' );

                            if ( !quiid ) {
                                return;
                            }

                            var Parent = QUI.Controls.getById( quiid );

                            Parent.normalize();
                            Parent.appendChild( this );

                        }.bind( this )
                    }
                });

            }

            // content params
            this.$refresh();
            this.fireEvent( 'create', [ this ] );

            return this.$Elm;
        },

        /**
         * Refresh the panel
         *
         * @method QUI.controls.desktop.Panel#refresh
         * @return {this}
         */
        refresh : function()
        {
            this.resize();
            this.fireEvent( 'refresh', [ this ] );
            this.$refresh();

            return this;
        },

        /**
         * Refresh helper
         *
         * @method QUI.controls.desktop.Panel#$refresh
         * @ignore
         * @private
         */
        $refresh : function()
        {
            if ( !this.$Title )
            {
                this.$Title = new Element( 'h2.qui-panel-title' ).inject(
                    this.$Header
                );
            }

            if ( this.getAttribute( 'title' ) ) {
                this.$Title.set( 'html', this.getAttribute('title') );
            }

            if ( this.getAttribute( 'icon' ) )
            {
                this.$Title.setStyles({
                    background  : 'url('+ this.getAttribute('icon') +') no-repeat 25px center',
                    paddingLeft : 50
                });
            }
        },

        /**
         * Execute a resize and repaint
         *
         * @method QUI.controls.desktop.Panel#resize
         * @return {this}
         */
        resize : function()
        {
            if ( this.getAttribute( 'header' ) === false )
            {
                this.$Header.setStyle( 'display', 'none' );
            } else
            {
                this.$Header.setStyle( 'display', null );
            }

            if ( this.getAttribute( 'footer' ) === false )
            {
                this.$Footer.setStyle( 'display', 'none' );
            } else
            {
                this.$Footer.setStyle( 'display', null );
            }

            if ( this.isOpen() === false )
            {
                this.fireEvent( 'resize', [this] );
                return this;
            }

            var content_height = this.getAttribute( 'height' ),
                content_width  = this.$Elm.getSize().x,
                overflow       = 'auto';

            content_height = content_height - 31;

            if ( this.$Buttons.getStyle( 'display' ) != 'none' )
            {
                content_height = content_height - this.$Buttons.getSize().y;
                content_height = content_height - 4; // -4 => borders
            }

            if ( this.getAttribute( 'breadcrumb' ) ) {
                content_height = content_height - 40;
            }

            if ( this.$Categories.getSize().x ) {
                content_width = content_width - this.$Categories.getSize().x;
            }


            if ( this.getAttribute( 'scrollbars' ) === false ) {
                overflow = 'hidden';
            }

            this.$Content.setStyles({
                overflow : overflow,
                height   : content_height,
                width    : content_width
            });

            this.$Elm.setStyle( 'height', this.getAttribute( 'height' ) );

            if ( this.$ButtonBar )
            {
                this.$ButtonBar.setAttribute( 'width', '98%' );
                this.$ButtonBar.resize();
            }

            this.fireEvent( 'resize', [ this ] );
            return this;
        },

        /**
         * Open the Panel
         *
         * @method QUI.controls.desktop.Panel#open
         * @return {this}
         */
        open : function()
        {
            this.$Content.setStyle( 'display', null );
            this.$Elm.setStyle( 'height', this.getAttribute( 'height' ) );

            this.$Collaps.removeClass( 'qui-panel-expand' );
            this.$Collaps.addClass( 'qui-panel-collapse' );

            this.fireEvent( 'open', [ this ] );

            return this;
        },

        /**
         * Minimize / Collapse the panel
         *
         * @method QUI.controls.desktop.Panel#minimize
         * @return {this}
         */
        minimize : function()
        {
            this.$Content.setStyle( 'display', 'none' );
            this.$Elm.setStyle( 'height', this.$Header.getSize().y );

            this.$Collaps.removeClass( 'qui-panel-collapse' );
            this.$Collaps.addClass( 'qui-panel-expand' );

            this.fireEvent( 'minimize', [ this ] );

            return this;
        },

        /**
         * Toggle the panel
         * Close the panel if the panel is opened and open the panel if the panel is closed
         *
         * @method QUI.controls.desktop.Panel#toggle
         * @return {this}
         */
        toggle : function()
        {
            if ( this.getAttribute( 'collapsible' ) === false ) {
                return this;
            }

            if ( this.isOpen() )
            {
                this.minimize();
            } else
            {
                this.open();
            }

            return this;
        },

        /**
         * Is the Panel open?
         *
         * @method QUI.controls.desktop.Panel#isOpen
         * @return {Bool}
         */
        isOpen : function()
        {
            return this.$Content.getStyle( 'display' ) == 'none' ? false : true;
        },

        /**
         * Highlight the column
         *
         * @return {this}
         */
        highlight : function()
        {
            if ( !this.getElm() ) {
                return this;
            }

            new Element( 'div.qui-panel-highlight' ).inject(
                this.getElm()
            );

            return this;
        },

        /**
         * Dehighlight the column
         *
         * @return {this}
         */
        normalize : function()
        {
            if ( !this.getElm() ) {
                return this;
            }

            this.getElm().getElements( '.qui-panel-highlight' ).destroy();

            return this;
        },

        /**
         * Return the Body DOMNode Element
         *
         * @method QUI.controls.desktop.Panel#getBody
         * @return {null|DOMNode}
         */
        getBody : function()
        {
            return this.$Content;
        },

        /**
         * Return the Title DOMNode Element
         *
         * @method QUI.controls.desktop.Panel#getHeader
         * @return {null|DOMNode}
         */
        getHeader : function()
        {
            return this.$Header;
        },

        /**
         * Add an action button to the Panel
         * This is a button top of the panel
         *
         * @method QUI.controls.desktop.Panel#addButton
         * @param {QUI.controls.buttons.Button|QUI.controls.buttons.Seperator|Object} Btn
         * @return {this}
         */
        addButton : function(Btn)
        {
            if ( typeof Btn.getType === 'undefined' )
            {
                if ( Btn.type == 'seperator' ||
                     Btn.type == 'QUI.controls.buttons.Seperator' )
                {
                    Btn = new QUI.controls.buttons.Seperator( Btn );
                } else
                {
                    Btn = new QUI.controls.buttons.Button( Btn );
                }
            }

            this.getButtonBar().appendChild( Btn );

            return this;
        },

        /**
         * Return the children
         *
         * @method @method QUI.controls.desktop.Panel#getButtons
         * @param {String} name - [optional] name of the wanted Element
         *                        if no name given, all children will be return
         * @return {Array}
         */
        getButtons : function(name)
        {
            if ( !this.$ButtonBar ) {
                return [];
            }

            return this.$ButtonBar.getChildren( name );
        },

        /**
         * Return the button bar of the pannel
         *
         * @method QUI.controls.desktop.Panel#getButtonBar
         * @return {QUI.controls.toolbar.Bar}
         */
        getButtonBar : function()
        {
            if ( !this.$ButtonBar )
            {
                this.$Buttons.setStyle( 'display', null );

                this.$ButtonBar = new QUI.controls.toolbar.Bar({
                    width : this.$Buttons.getSize().x,
                    slide : false,
                    type  : 'buttons',
                    'menu-button' : false
                }).inject( this.$Buttons );
            }

            return this.$ButtonBar;
        },

        /**
         * Add an category button to the Panel
         * This is a button left of the panel
         *
         * @method QUI.controls.desktop.Panel#addCategory
         * @param {QUI.controls.buttons.Button|Object} Btn
         * @return {this}
         */
        addCategory : function(Btn)
        {
            if ( typeof Btn.getType === 'undefined' )   {
                Btn = new QUI.controls.buttons.Button( Btn );
            }

            Btn.addEvents({

                onClick : function(Btn)
                {
                    if ( this.$ActiveCat && this.$ActiveCat == Btn ) {
                        return;
                    }

                    Btn.setActive();
                }.bind( this ),

                onActive : function(Btn)
                {
                    if ( this.$ActiveCat ) {
                        this.$ActiveCat.setNormal();
                    }

                    this.$ActiveCat = Btn;

                }.bind( this )

            });

            this.getCategoryBar().appendChild( Btn );

            return this;
        },

        /**
         * Return a category children
         *
         * @method QUI.controls.desktop.Panel#getCategory
         * @param {String} name - [optional] name of the wanted Element
         *                        if no name given, all children will be return
         * @return {Array}
         */
        getCategory : function(name)
        {
            if ( !this.$CategoryBar ) {
                return [];
            }

            return this.$CategoryBar.getChildren( name );
        },

        /**
         * Return the Category bar object
         *
         * @method QUI.controls.desktop.Panel#getCategoryBar
         * @return {QUI:controls.toolbar.Bar}
         */
        getCategoryBar : function()
        {
            if ( !this.$CategoryBar )
            {
                this.$Categories.setStyle( 'display', null );

                this.$CategoryBar = new QUI.controls.toolbar.Bar({
                    width : 190,
                    slide : false,
                    type  : 'buttons',
                    'menu-button' : false,
                    events :
                    {
                        onClear : function(Bar)
                        {
                            this.$ActiveCat = null;
                        }.bind( this )
                    }
                }).inject( this.$Categories );
            }

            return this.$CategoryBar;
        },

        /**
         * Return the active category
         *
         * @method QUI.controls.desktop.Panel#getActiveCategory
         * @return {QUI.controls.buttons.Button}
         */
        getActiveCategory : function()
        {
            return this.$ActiveCat;
        },

        /**
         * Return the Breacrumb bar object
         *
         * @method QUI.controls.desktop.Panel#getBreadcrumb
         * @return {QUI:controls.breadcrumb.Bar}
         */
        getBreadcrumb : function()
        {
            if ( !this.$BreadcrumbBar )
            {
                this.$BreadcrumbBar = new QUI.controls.breadcrumb.Bar({
                    name : 'panel-breadcrumb-' + this.getId()
                }).inject( this.$Breadcrumb );
            }

            return this.$BreadcrumbBar;
        },

        /**
         * Return the panel contextmenu
         *
         * @method QUI.controls.desktop.Panel#getContextMenu
         * @return {QUI:controls.contextmenu.Menu}
         */
        getContextMenu : function()
        {
            if ( this.$ContextMenu ) {
                return this.$ContextMenu;
            }

            // context menu
            this.$ContextMenu = new QUI.controls.contextmenu.Menu({
                title  : this.options.title,
                events :
                {
                    blur : function(Menu) {
                        Menu.hide();
                    }
                }
            });

            this.$ContextMenu.inject( document.body );

            return this.$ContextMenu;
        },

        /**
         * Create a sheet in the panel and open it
         *
         * @method QUI.controls.desktop.Panel#createSheet
         * @return {QUI.controls.panels.Sheet}
         */
        createSheet : function()
        {
            var Sheet = new QUI.controls.desktop.panels.Sheet().inject(
                this.$Elm
            );

            return Sheet;
        },

        /**
         * Event: on panel destroy
         *
         * @method QUI.controls.desktop.Panel#$onDestroy
         */
        $onDestroy : function()
        {
            if ( this.$Elm ) {
                this.$Elm.destroy();
            }

            if ( this.$Elm ) {
                this.$Header.destroy();
            }

            if ( this.$Title ) {
                this.$Title.destroy();
            }

            if ( this.$Footer ) {
                this.$Footer.destroy();
            }

            if ( this.$Content ) {
                this.$Content.destroy();
            }

            if ( this.$Buttons ) {
                this.$Buttons.destroy();
            }

            if ( this.$ButtonBar ) {
                this.$ButtonBar.destroy();
            }

            if ( this.$CategoryBar ) {
                this.$CategoryBar.destroy();
            }

            if ( this.$ActiveCat ) {
                this.$ActiveCat.destroy();
            }

            if ( this.$BreadcrumbBar ) {
                this.$BreadcrumbBar.destroy();
            }

            if ( this.$ContextMenu ) {
                this.$ContextMenu.destroy();
            }
        }

    });

    return QUI.controls.desktop.Panel;
});