/**
 * Column for panels and apppanels
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/Column
 * @package com.pcsg.qui.js.controls.desktop
 * @namespace QUI.controls.desktop
 */

define('controls/desktop/Column', [

    'controls/Control',
    'classes/utils/DragDrop',
    'controls/desktop/Panel',
    'controls/loader/Loader',
    'controls/contextmenu/Menu',

    'css!controls/desktop/Column.css'

], function(Control)
{
    QUI.namespace( 'controls.desktop' );

    /**
     * @class QUI.controls.desktop.Column
     *
     * @event onCreate [this]
     */
    QUI.controls.desktop.Column = new Class({

        Type       : 'QUI.controls.desktop.Column',
        Implements : [ Control ],

        Binds : [
            '$onContextMenu',
            '$clickAddPanelToColumn'
        ],

        options : {
            name        : 'column',
            width       : false,
            resizeLimit : [],
            sortable    : true,
            closable    : false,
            placement   : 'left'
        },

        initialize: function(options)
        {
            this.init( options );

            this.$ContextMenu = null;
            this.$Elm         = null;
            this.$Handle      = null;
            this.$Content     = null;
            this.$panels      = {};

            this.addEvent('onDestroy', function()
            {
                if ( this.$ContextMenu ) {
                    this.$ContextMenu.destroy();
                }

                if ( this.$Handle ) {
                    this.$Handle.destroy();
                }

                if ( this.$Content ) {
                    this.$Content.destroy();
                }

                if ( this.$Elm ) {
                    this.$Elm.destroy();
                }

            }.bind( this ));
        },

        /**
         * Create the DOMNode for the Column
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class'      : 'qui-column box',
                'data-quiid' : this.getId()
            });

            this.$Content = new Element('div', {
                'class' : 'qui-column-content box'
            }).inject( this.$Elm );

            this.$Handle = new Element('div', {
                'class' : 'qui-column-handle',
                styles  : {
                    width       : 4,
                    borderWidth : '0 1px'
                }
            });

            // contextmenu
            this.$ContextMenu = new QUI.controls.contextmenu.Menu({
                events :
                {
                    onBlur : function(Menu) {
                        Menu.hide()
                    }
                }
            }).inject(
                document.body
            );

            this.$ContextMenu.hide();

            this.$Elm.addEvents({
                contextmenu : this.$onContextMenu
            });

            switch ( this.getAttribute( 'placement' ) )
            {
                case 'left':
                    this.$Handle.inject( this.$Content, 'after' );
                    this.$addResize();
                break;

                case 'right':
                    this.$Handle.inject( this.$Content, 'before' );
                    this.$addResize();
                break;

                default:
                    this.$Handle.destroy();
                    this.$Handle = null;
            }

            if ( typeof this.$serialize !== 'undefined' ) {
                this.unserialize( this.$serialize );
            }

            this.resize();
            this.fireEvent( 'create', [ this ] );

            return this.$Elm;
        },

        /**
         * Return the data for the workspace
         *
         * @return Object
         */
        serialize : function()
        {
            var panels   = this.getChildren(),
                children = [];

            for ( var p in panels ) {
                children.push( panels[ p ].serialize() );
            }

            return {
                attributes : this.getAttributes(),
                children   : children
            };
        },

        /**
         * Import the saved data
         *
         * @param {Object} data
         */
        unserialize : function(data)
        {
            this.setAttribute( data.attributes );

            if ( !this.$Elm )
            {
                this.$serialize = data;
                return this;
            }

            var i, len,
                child_type, child_modul;

            var children = data.children;

            if ( !children ) {
                return;
            }

            var req = [];

            for ( i = 0, len = children.length; i < len; i++ )
            {
                child_type  = children[ i ].type;
                child_modul = child_type.replace('QUI.', '')
                                        .replace(/\./g, '/');

                req.push( child_modul );
            }

            require(req, function()
            {
                var i, len, attr, height, Child, Control;

                for ( i = 0, len = children.length; i < len; i++ )
                {
                    Child  = children[ i ];
                    attr   = Child.attributes;
                    height = attr.height;

                    try
                    {
                        Control = eval(
                            'new '+ Child.type +'( attr )'
                        );

                        Control.unserialize( Child );

                        this.appendChild( Control );

                    } catch ( Exception )
                    {
                        QUI.MH.addException( Exception );
                    }
                    // on append child we set the height by calculation
                    // but we need the old height
                    /*
                    if ( height )
                    {
                        Control.setAttribute( 'height', height );
                        Control.resize();
                    }*/
                }

            }.bind( this ));
        },

        /**
         * Append a child to the Column
         *
         * @param {QUI.controls.desktop.Panel|QUI.controls.desktop.Apppanel} Panel
         * @return {this}
         */
        appendChild : function(Panel)
        {
            var Handler = false,
                height  = false;

            if ( this.count() )
            {
                Handler = new Element('div', {
                    'class' : 'qui-column-hor-handle'
                }).inject( this.$Content );

                this.$addHorResize( Handler );
            }

            Panel.inject( this.$Content );
            Panel.setParent( this );

            // if no height, use the column height
            if ( !Panel.getAttribute( 'height' ) ) {
                Panel.setAttribute( 'height', this.$Elm.getSize().y );
            }

            Panel.resize();

            /*
            if ( !this.count() )
            {
                height = this.$Elm.getSize().y;

                if ( height )
                {
                    Panel.setAttribute( 'height', this.$Elm.getSize().y );
                    Panel.resize();
                }
            } else
            {
                var Prev = this.getPreviousPanel( Panel );

                height = Prev.getAttribute( 'height' ) - Panel.getAttribute('height');

                if ( Handler ) {
                    height = height - Handler.getSize().y;
                }

                Prev.setAttribute( 'height', height );
                Prev.resize();
            }
            */

            Panel.addEvents({
                onMinimize : this.$onPanelClose.bind( this ),
                onOpen     : this.$onPanelOpen.bind( this )
            });

            this.$panels[ Panel.getId() ] = Panel;

            return this;
        },

        /**
         * Return the column children
         *
         * @return Object
         */
        getChildren : function()
        {
            return this.$panels;
        },

        /**
         * Panel count
         * How many panels are in the coulumn?
         *
         * @return {Integer}
         */
        count : function()
        {
            var c, i = 0;

            for ( c in this.$panels ) {
                i++;
            }

            return i;
        },

        /**
         * Resize the column and all panels in the column
         *
         * @return {this}
         */
        resize : function()
        {
            if ( !this.isOpen() ) {
                return this;
            }

            if ( this.getAttribute( 'width' ) )
            {
                this.$Elm.setStyle( 'width', this.getAttribute('width') );

                if ( this.$Handle )
                {
                    this.$Content.setStyle( 'width', this.getAttribute('width') - 6 );
                } else
                {
                    this.$Content.setStyle( 'width', this.getAttribute('width') );
                }
            }


            // all panels resize
            var i, Panel;

            var size = this.$Elm.getSize();

            for ( i in this.$panels )
            {
                Panel = this.$panels[ i ];

                Panel.setAttribute( 'width', size.x );
                Panel.resize();
            }

            return this;
        },

        /**
         * Open the column
         *
         * @return {this}
         */
        open : function()
        {
            this.$Content.setStyle( 'display', null );

            // sibling resize
            var Sibling = this.getSibling();

            Sibling.setAttribute(
                'width',
                Sibling.getAttribute('width') - this.getAttribute('width') + 6
            );

            Sibling.resize();

            // self reresh
            this.resize();

            return this;
        },

        /**
         * Close the column
         *
         * @return {this}
         */
        close : function()
        {
            if ( this.getAttribute( 'closable' ) === false ) {
                return this;
            }

            var content_width = this.$Content.getSize().x,
                Sibling       = this.getSibling();

            this.$Content.setStyle( 'display', 'none' );

            if ( this.$Handle ) {
                this.$Elm.setStyle( 'width', this.$Handle.getSize().x );
            }

            // resize the sibling column
            Sibling.setAttribute(
                'width',
                Sibling.getAttribute('width') + content_width
            );

            Sibling.resize();

            return this;
        },

        /**
         * toggle the open status of the column
         * if open, then close
         * if close, the open ;-)
         *
         * @return {this}
         */
        toggle : function()
        {
            if ( this.isOpen() )
            {
                this.close();
            } else
            {
                this.open();
            }

            return this;
        },

        /**
         * Return the open status of the colum
         * is the column open?
         *
         * @return {Bool}
         */
        isOpen : function()
        {
            return this.$Content.getStyle( 'display' ) == 'none' ? false : true;
        },

        /**
         * Return the Sibling column control
         *
         * @return {false|QUI.controls.desktop.Column}
         */
        getSibling : function()
        {
            var Next;

            if ( this.getAttribute('placement') == 'left' )
            {
                Next = this.getElm().getNext('.qui-column');
            } else if( this.getAttribute('placement') == 'right' )
            {
                Next = this.getElm().getPrevious('.qui-column');
            }

            return QUI.Controls.getById( Next.get('data-quiid') );
        },

        /**
         * return the next panel sibling
         *
         * @return {false|QUI.controls.desktop.Panel|QUI.controls.desktop.Apppanel}
         */
        getNextPanel : function(Panel)
        {
            var NextElm = Panel.getElm().getNext( '.qui-panel' );

            if ( !NextElm ) {
                return false;
            }

            var Next = QUI.Controls.getById( NextElm.get( 'data-quiid' ) );

            return Next ? Next : false;
        },

        /**
         * Get the next panel sibling which is opened
         *
         * @return {false|QUI.controls.desktop.Panel|QUI.controls.desktop.Apppanel}
         */
        getNextOpenedPanel : function(Panel)
        {
            var list = Panel.getElm().getAllNext( '.qui-panel' );

            if ( !list.length ) {
                return false;
            }

            var i, len, Control;

            for ( i = 0, len = list.length; i < len; i++ )
            {
                Control = QUI.Controls.getById(
                    list.get( 'data-quiid' )
                );

                if ( Control && Control.isOpen() ) {
                    return Control;
                }
            }

            return false;
        },

        /**
         * return the previous panel sibling
         *
         * @return {false|QUI.controls.desktop.Panel|QUI.controls.desktop.Apppanel}
         */
        getPreviousPanel : function(Panel)
        {
            var PrevElm = Panel.getElm().getPrevious( '.qui-panel' );

            if ( !PrevElm ) {
                return false;
            }

            var Prev = QUI.Controls.getById( PrevElm.get( 'data-quiid' ) );

            return Prev ? Prev : false;
        },

        /**
         * return the previous panel sibling
         *
         * @return {false|QUI.controls.desktop.Panel|QUI.controls.desktop.Apppanel}
         */
        getPreviousOpenedPanel : function(Panel)
        {
            var list = Panel.getElm().getAllPrevious( '.qui-panel' );

            if ( !list.length ) {
                return false;
            }


            var i, len, Control;

            for ( i = 0, len = list.length; i < len; i++ )
            {
                Control = QUI.Controls.getById(
                    list[ i ].get( 'data-quiid' )
                );

                if ( Control && Control.isOpen() ) {
                    return Control;
                }
            }

            return false;
        },

        /**
         * Panel close event
         *
         * @ignore
         * @param {QUI.controls.desktop.Panel} Panel
         */
        $onPanelClose : function(Panel)
        {
            var Next = this.getNextOpenedPanel( Panel );

            if ( !Next ) {
                Next = this.getPreviousOpenedPanel( Panel );
            }

            if ( !Next )
            {
                this.close();
                return;
            }

            var Elm    = Panel.getElm(),
                height = Elm.getSize().y;

            height = Panel.getAttribute( 'height' ) - height;

            Next.setAttribute(
                'height',
                Next.getAttribute( 'height' ) + height
            );

            Next.resize();
        },

        /**
         * Panel open event
         *
         * @ignore
         * @param {QUI.controls.desktop.Panel} Panel
         */
        $onPanelOpen : function(Panel)
        {
            var Prev = this.getPreviousOpenedPanel( Panel );

            if ( !Prev ) {
                Prev = this.getNextOpenedPanel( Panel );
            }

            if ( !Prev ) {
                return;
            }

            Prev.setAttribute(
                'height',
                Prev.getAttribute( 'height' ) - Panel.getBody().getSize().y
            );

            Prev.resize();
        },

        /**
         * Add the vertical resizing events to the column
         */
        $addResize: function()
        {
            if ( !this.$Handle ) {
                return;
            }


            var Handle = this.$Handle;

            // dbl click
            Handle.addEvent('dblclick', function() {
                this.toggle();
            }.bind( this ));

            // Drag & Drop event
            var min = this.getAttribute( 'resizeLimit' )[0],
                max = this.getAttribute( 'resizeLimit' )[1];

            if ( !min ) {
                min = 50;
            }

            if ( !max ) {
                max = 250;
            }

            var handlepos = Handle.getPosition().y;

            new QUI.classes.utils.DragDrop(Handle, {
                limit  : {
                    x: [min, max],
                    y: [handlepos, handlepos]
                },
                events :
                {
                    onStart : function(Dragable, DragDrop)
                    {
                        var pos   = Handle.getPosition(),
                            limit = DragDrop.getAttribute( 'limit' );

                        limit.y = [ pos.y, pos.y ];

                        DragDrop.setAttribute( 'limit', limit );

                        Dragable.setStyles({
                            width   : 5,
                            padding : 0,
                            top     : pos.y,
                            left    : pos.x
                        });
                    },

                    onStop : function(Dragable, DragDrop)
                    {
                        if ( this.isOpen() === false ) {
                            this.open();
                        }

                        var change, Next, next_width, this_width;

                        var pos  = Dragable.getPosition(),
                            hpos = Handle.getPosition();


                        change = pos.x - hpos.x - Handle.getSize().x;
                        Next   = this.getSibling();

                        this_width = this.getAttribute('width');
                        next_width = Next.getAttribute('width');

                        if ( this.getAttribute('placement') == 'left' )
                        {
                            this.setAttribute( 'width', this_width + change );
                            Next.setAttribute( 'width', next_width - change );

                        } else if ( this.getAttribute('placement') == 'right' )
                        {
                            this.setAttribute( 'width', this_width - change );
                            Next.setAttribute( 'width', next_width + change );
                        }

                        Next.resize();
                        this.resize();
                    }.bind( this )
                }
            });
        },

        /**
         * Add the horizental resizing events to the column
         *
         * @param {DOMNode} Handle
         */
        $addHorResize : function(Handle)
        {
            var pos = Handle.getPosition();

            var DragDrop = new QUI.classes.utils.DragDrop(Handle, {
                limit  : {
                    x: [ pos.x, pos.x ],
                    y: [ pos.y, pos.y ]
                },
                events :
                {
                    onStart : function(Dragable, DragDrop)
                    {
                        if ( !this.$Elm ) {
                            return;
                        }

                        var pos   = this.$Elm.getPosition(),
                            hpos  = Handle.getPosition(),
                            limit = DragDrop.getAttribute( 'limit' );

                        limit.y = [
                            pos.y,
                            pos.y + this.$Elm.getSize().y
                        ];

                        limit.x = [ hpos.x, hpos.x ];

                        DragDrop.setAttribute( 'limit', limit );

                        Dragable.setStyles({
                            height  : 5,
                            padding : 0,
                            top     : hpos.y,
                            left    : hpos.x
                        });

                    }.bind( this ),

                    onStop : this.$horResizeStop.bind( this )
                }
            });

            DragDrop.setAttribute( 'Control', this );
            DragDrop.setAttribute( 'Handle', Handle );
        },

        /**
         * Horizontal Drag Drop Stop
         * Helper Function
         */
        $horResizeStop : function(Dragable, DragDrop)
        {
            var i, len, change;

            var Handle   = DragDrop.getAttribute('Handle'),
                pos      = Dragable.getPosition(),
                hpos     = Handle.getPosition(),
                size     = this.$Content.getSize(),
                children = this.$Content.getChildren();

            change = pos.y - hpos.y;

            var Next = Handle.getNext(),
                Prev = Handle.getPrevious();

            var NextInstance = QUI.Controls.getById(
                Next.get( 'data-quiid' )
            );

            var PrevInstance = QUI.Controls.getById(
                Prev.get( 'data-quiid' )
            );


            if ( !NextInstance.isOpen() )
            {
                var NextOpened = this.getNextOpenedPanel( NextInstance );

                if ( !NextOpened )
                {
                    NextInstance.setAttribute( 'height', 30 );
                    NextInstance.open();
                } else
                {
                    NextInstance = NextOpened;
                }
            }

            if ( !PrevInstance.isOpen() )
            {
                var PrevOpened = this.getPreviousOpenedPanel( PrevInstance );

                if ( !PrevOpened )
                {
                    PrevInstance.setAttribute( 'height', 30 );
                    PrevInstance.open();
                } else
                {
                    PrevInstance = PrevOpened;
                }
            }


            NextInstance.setAttribute(
                'height',
                NextInstance.getAttribute( 'height' ) - change
            );

            PrevInstance.setAttribute(
                'height',
                PrevInstance.getAttribute( 'height' ) + change
            );

            NextInstance.resize();
            PrevInstance.resize();


            // check if a rest height exist
            var children_height = 0;

            for ( i = 0, len = children.length; i < len; i++ ) {
                children_height = children_height + children[i].getSize().y;
            }

            if ( children_height == size.y ) {
                return;
            }

            PrevInstance.setAttribute(
                'height',
                PrevInstance.getAttribute( 'height' ) + (size.y - children_height)
            );

            PrevInstance.resize();
        },

        /**
         * event : on context menu
         *
         * @param {DOMEvent} event
         */
        $onContextMenu : function(event)
        {
            event.stop();

            var Parent = this.getParent(),
                panels = Parent.getAvailablePanel();

            this.$ContextMenu.clearChildren();
            this.$ContextMenu.setTitle( 'Column' );

            var Panels = new QUI.controls.contextmenu.Item({
                text : 'Panel hinzufügen',
                name : 'add_panels_to_column'
            });

            this.$ContextMenu.appendChild( Panels );

            for ( var i = 0, len = panels.length; i < len; i++ )
            {
                Panels.appendChild(
                    new QUI.controls.contextmenu.Item({
                        text   : panels[ i ].text,
                        icon   : panels[ i ].icon,
                        name   : 'add_panels_to_column',
                        params : panels[ i ],
                        events : {
                            onMouseDown : this.$clickAddPanelToColumn
                        }
                    })
                );
            }

            this.$ContextMenu.setPosition(
                event.page.x,
                event.page.y
            ).show().focus();
        },

        /**
         * @param {QUI.controls.contextmenu.Item} ContextMenuItem
         */
        $clickAddPanelToColumn : function(ContextMenuItem)
        {
            var Column = this,
                params = ContextMenuItem.getAttribute( 'params' );

            if ( !params.require ) {
                return;
            }

            require([ params.require ], function(Panel) {
                Column.appendChild( new Panel() );
            });
        }
    });

    return QUI.controls.desktop.Column;
});