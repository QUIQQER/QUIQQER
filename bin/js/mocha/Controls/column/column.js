/*
 ---

 name: Column

 script: column.js

 description: MUI.Column - Column control for horizontal layouts.

 copyright: (c) 2011 Contributors in (/AUTHORS.txt).

 license: MIT-style license in (/MIT-LICENSE.txt).

 requires:
 - MochaUI/MUI
 - MUI.Desktop
 - MUI.Panel

 provides: [MUI.Column]

 ...
 */

define('mocha/Controls/column/column', [

    'controls/Control',
    'mocha/Controls/desktop/desktop',
    'mocha/Controls/panel/panel'

], function(QUI_Control)
{
    MUI.Column = new NamedClass('MUI.Column', {

        Implements: [QUI_Control],

        options : {
            id             : null,
            container      : null,
            drawOnInit     : true,

            placement      : null,
            width          : null,
            resizeLimit    : [],
            sortable       : true,
            isCollapsed    : false,
            keep1PanelOpen : false,

            panels         : [],
            cssClass       : '',
            closable       : false

            //onDrawBegin:    null,
            //onDrawEnd:    null,
            //onResize:        null,
            //onCollapse:    null,
            //onExpand:        null
        },

        initialize: function(options)
        {
            this.init( options );

            this.isCollapsed = false;
            this.oldWidth    = 0;
            this.el          = {};
            this.$panels     = [];


            // If column has no ID, give it one.
            this.id = this.options.id = this.options.id || 'column' + (++MUI.idCount);
            MUI.set( this.id, this );

            this.$ContextMenu = null;

            if ( this.getAttribute('drawOnInit') ) {
                this.draw();
            }
        },

        /**
         * Destroy the Column
         */
        destroy : function()
        {
            if ( this.$ContextMenu ) {
                this.$ContextMenu.destroy();
            }
        },

        /**
         * Draw the Column
         *
         * @return {this}
         */
        draw: function()
        {
            var options = this.options;

            this.fireEvent('drawBegin', [this]);

            if ( options.container === null )
            {
                options.container = MUI.desktop.el.content;
            } else
            {
                $( options.container ).setStyle( 'overflow', 'hidden' );
            }

            if ( typeOf( options.container ) === 'string' ) {
                options.container = $( options.container );
            }

            // Check if column already exists
            if ( this.el.column ) {
                return this;
            }

            MUI.set( options.id, this );

            var parentInstance = MUI.get( options.container );

            if ( parentInstance &&
                 (parentInstance.isTypeOf('MUI.Panel') || parentInstance.isTypeOf('MUI.Window')))
            {
                // If loading columns into a panel, hide the regular content container.
                if ( parentInstance.el.element.getElement('.pad') !== null ) {
                    parentInstance.el.element.getElement('.pad').hide();
                }

                // If loading columns into a window, hide the regular content container.
                if ( parentInstance.el.element.getElement('.mochaContent') !== null ) {
                    parentInstance.el.element.getElement('.mochaContent').hide();
                }
            }

            // make or use existing element
            if ( options.element )
            {
                this.el.column = options.element;
            } else if ( $( options.id ) )
            {
                this.el.column = $( options.id );
            } else
            {
                this.el.column = new Element('div', {
                    'id': options.id
                }).inject( $( options.container ) );
            }

            this.el.element = this.el.column;

            // parent container's height
            var parent       = this.el.column.getParent();
            var columnHeight = parent.getStyle('height').toInt();

            // format column element correctly
            this.el.column
                   .addClass('column expanded')
                   .setStyle('width', options.placement == 'main' ? null : options.width)
                   .store('instance', this)
                   .setStyle('height', columnHeight);

            this.el.column.addClass( options.cssClass );

            if ( options.sortable )
            {
                if ( !options.container.retrieve('sortables') )
                {
                    var sortables = new Sortables(this.el.column, {
                        opacity   : 0.2,
                        handle    : '.panel-header',
                        constrain : false,
                        clone     : false,
                        revert    : {
                            duration: 500,
                            transition: 'quad:in'
                        },
                        onStart: function(element, clone)
                        {
                            var pos = element.getPosition( document.body );

                            clone.inject( document.body ).setStyles({
                                'z-index'     : 1999,
                                'opacity'     : 0.65,
                                'margin-left' : pos.x,
                                'margin-top'  : pos.y - clone.getStyle('top').toInt()
                            });
                        },

                        onSort: function()
                        {
                            $$('.column').each(function(column)
                            {
                                column.getChildren('.panelWrapper').removeClass('bottomPanel');

                                if ( column.getChildren('.panelWrapper').getLast() ) {
                                    column.getChildren('.panelWrapper').getLast().addClass('bottomPanel');
                                }

                                column.getChildren('.panelWrapper').each(function(panelWrapper)
                                {
                                    var panel    = panelWrapper.getElement('.panel');
                                    var column   = panelWrapper.getParent().id;
                                    var instance = MUI.get(panel.id);

                                    if ( instance )
                                    {
                                        instance.options.column = column;
                                        var nextPanel = panel.getParent().getNext('.expanded');

                                        if ( nextPanel ) {
                                            nextPanel = nextPanel.getElement('.panel');
                                        }

                                        instance.partner = nextPanel;
                                    }
                                });

                                MUI.panelHeight();

                            }.bind( this ));
                        }.bind( this )
                    });

                    options.container.store('sortables', sortables);
                } else
                {
                    options.container.retrieve('sortables').addLists(this.el.column);
                }
            }

            if ( options.placement === 'main' ) {
                this.el.column.addClass('rWidth');
            }

            var Handle = false;

            switch ( options.placement )
            {
                case 'left':
                    Handle = new Element('div', {
                        'id'    : options.id + '_handle',
                        'class' : 'columnHandle'
                    }).inject( this.el.column, 'after' );

                    this.el.handleIcon = new Element('div', {
                        'id'   : options.id + '_handle_icon',
                        'class': 'handleIcon'
                    }).inject( Handle );

                    this._addResize(
                        this.el.column,
                        options.resizeLimit[0],
                        options.resizeLimit[1],
                        'right'
                    );
                break;

                case 'right':
                    Handle = new Element('div', {
                        'id'    : options.id + '_handle',
                        'class' : 'columnHandle'
                    }).inject( this.el.column, 'before' );

                    this.el.handleIcon = new Element('div', {
                        'id'    : options.id + '_handle_icon',
                        'class' : 'handleIcon'
                    }).inject( Handle );

                    this._addResize(
                        this.el.column,
                        options.resizeLimit[0],
                        options.resizeLimit[1],
                        'left'
                    );
                break;
            }

            // handle height
            if ( Handle )
            {
                var handleHeight = Handle.getParent().getStyle('height').toInt() -
                                   Handle.getStyle('border-top').toInt() -
                                   Handle.getStyle('border-bottom').toInt();


                Handle.setStyle( 'height', handleHeight );

                this.el.handle = Handle;

                Handle.addEvent('contextMenu', function(event)
                {
                    this.showContextMenu();
                }.bind( this ));
            }

            if ( options.isCollapsed && this.options.placement != 'main' ) {
                this.expand();
            }

            if ( typeof this.el.handle !== 'undefined' )
            {
                this.el.handle.addEvent('dblclick', function()
                {
                    this.toggle();
                }.bind(this));
            }

            MUI.rWidth( options.container );

            if ( options.panels )
            {
                for ( var i = 0, len = options.panels.length; i < len; i++ )
                {
                    var panel = options.panels[i];

                    if ( !panel.id ) {
                        panel.id = options.id + 'Panel' + i;
                    }

                    panel.container = this.el.column;
                    panel.column    = options.id;
                    panel.control   = 'MUI.Panel';

                    panel.element = new Element('div', {
                        'id' : panel.id +'_wrapper'
                    }).inject(this.el.column);

                    MUI.create( panel );
                }
            }

            this.fireEvent( 'drawEnd', [this] );
            return this;
        },

        /**
         * Show the contextmenu
         *
         * @return {QUI.controls.contextmenu.Menu}
         */
        showContextMenu : function(event)
        {
            if ( this.getAttribute('closable') === false ) {
                return;
            }

            if ( !this.$ContextMenu )
            {
                this.$ContextMenu = new QUI.controls.contextmenu.Menu({
                    events :
                    {
                        onBlur : function(Menu) {
                            Menu.hide();
                        }
                    }
                }).inject( document.body );
            }

            if ( typeof event !== 'undefined' )
            {
                this.$ContextMenu.setPosition(
                    event.page.x,
                    event.page.y
                );
            }

            this.$ContextMenu.show().focus();

            return this.$ContextMenu;
        },

        /**
         * Return all Panels in the Column
         *
         * @return {Array}
         */
        getPanels: function()
        {
            var i, len, Panel;

            var panels = [],
                list   = $( this.el.column ).getElements('.panel');

            for ( i = 0, len = list.length; i < len; i++ )
            {
                Panel = MUI.get( list[ i ].id );

                if ( Panel ) {
                    panels.push( Panel );
                }
            }

            return panels;
        },

        appendPanel : function(Panel)
        {
            console.log( Panel );
        },

        collapse: function()
        {
            var column = this.el.column;

            this.oldWidth = column.getStyle('width').toInt();

            this.el.handle.removeEvents( 'dblclick' );
            this.el.handle.addEvent('click', function()
            {
                this.expand();
            }.bind( this ));

            this.el.handle.setStyle( 'cursor', 'pointer' ).addClass( 'detached' );

            column.setStyle( 'width', 0 );
            this.isCollapsed = true;
            column.addClass( 'collapsed' );
            column.removeClass( 'expanded' );
            MUI.rWidth( this.options.container );
            this.fireEvent( 'collapse', [this] );

            return this;
        },

        expand : function()
        {
            var column = this.el.column;

            column.setStyle('width', this.oldWidth);
            this.isCollapsed = false;
            column.addClass('expanded');
            column.removeClass('collapsed');

            this.el.handle.removeEvents('click');
            this.el.handle.addEvent('dblclick', function()
            {
                this.collapse();
            }.bind( this ));

            this.el.handle.setStyle('cursor', Browser.webkit ? 'col-resize' : 'e-resize').addClass('attached');

            MUI.rWidth( this.options.container );
            this.fireEvent('expand', [this]);

            return this;
        },

        toggle: function()
        {
            if ( !this.isCollapsed )
            {
                this.collapse();
            } else
            {
                this.expand();
            }

            return this;
        },

        close: function()
        {
            this.isClosing = true;

            // Destroy all the panels in the column.
            var i, len;
            var panels = this.getPanels();

            for ( i = 0, len = panels.length; i < len; i++ ) {
                panels[ i ].close();
            }

            if ( Browser.ie )
            {
                this.el.column.dispose();

                if ( this.el.handle !== null ) {
                    this.el.handle.dispose();
                }
            } else
            {
                this.el.column.destroy();

                if ( this.el.handle !== null ) {
                    this.el.handle.destroy();
                }
            }

            if ( MUI.desktop ) {
                MUI.desktop.resizePanels();
            }

            var sortables = this.options.container.retrieve('sortables');

            if ( sortables ) {
                sortables.removeLists( this.el.column );
            }

            for ( i in this.el ) {
                this.el[ i ].destroy();
            }

            this.el = {};

            MUI.erase( this.options.id );
            return this;
        },

        _addResize: function(element, min, max, where)
        {
            var instance = this;

            if ( !$(element) ) {
                return;
            }

            element = $(element);

            var Handle = (where == 'left') ?
                    element.getPrevious('.columnHandle') :
                    element.getNext('.columnHandle');

            Handle.setStyle( 'cursor', Browser.webkit ? 'col-resize' : 'e-resize' );

            if ( !min ) {
                min = 50;
            }

            if ( !max ) {
                max = 250;
            }

            require(['classes/utils/DragDrop'], function(DragDrop)
            {
                var handlepos = Handle.getPosition().y;

                new DragDrop( Handle, {
                    limit  : {
                        x: [min, max],
                        y: [handlepos, handlepos]
                    },
                    events :
                    {
                        onStart : function(Dragable, DragDrop)
                        {
                            var pos = Handle.getPosition();

                            Dragable.setStyles({
                                width   : 5,
                                padding : 0,
                                top     : pos.y,
                                left    : pos.x
                            });
                        },

                        onStop : function(Dragable, DragDrop)
                        {
                            var change, Prev, Next, prev_width, next_width,
                                PrevColumn, PrevElm, NextColumn, NextElm;

                            var pos  = Dragable.getPosition(),
                                hpos = Handle.getPosition();

                            change = pos.x - hpos.x,
                            Prev   = Handle.getPrevious('.column');
                            Next   = Handle.getNext('.column'),

                            PrevColumn = MUI.get( Prev );
                            NextColumn = MUI.get( Next );

                            // new width
                            PrevElm = PrevColumn.el.column;
                            NextElm = NextColumn.el.column;

                            prev_width = PrevElm.getSize().x;
                            next_width = NextElm.getSize().x;

                            PrevElm.setStyle( 'width', PrevElm.getSize().x + change );
                            NextElm.setStyle( 'width', NextElm.getSize().x - change );

                            PrevColumn.options.width = PrevElm.getSize().x;
                            NextColumn.options.width = NextElm.getSize().x;

                            // resize of all subpanels
                            // dont know if it is needed
                            /*

                            var panels = [].combine( PrevColumn.getPanels() )
                                           .combine( NextColumn.getPanels() );

                            for ( var i = 0, len = panels.length; i < len; i++ )
                            {
                                Panel = panels[ i ];

                                if ( Panel.el.panel &&
                                     Panel.el.panel.getElement('.mochaIframe') !== null )
                                {
                                    MUI.resizeChildren( Panel.el.panel );
                                }
                            }
                            */
                        }
                    }
                });
            });
        }

    });

    return MUI.Column;
});